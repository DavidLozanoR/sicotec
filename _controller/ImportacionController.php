<?php
require_once "MainModel.php";

class ImportacionController {

    // Definición de constantes para rutas
    const VISTA = "_view/importar.html.php";
    const JS    = "js/importar.js";

    private $slug;

    // Constructor estándar para validación de sesión
    function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    /**
     * Renderiza el contenido HTML de la vista
     */
    public function renderContent() {
        $model = new MainModel();
        
        // Buscamos el ID del submódulo padre para heredar sus permisos o contexto
        $id_sub_personal = $model->obtenerIdSubmoduloPorSlug('personalop');
        
        // Definimos los datos que la vista necesitará
        $datos = [
            'id_sub_personal' => $id_sub_personal,
            'slug'            => $this->slug
        ];

        // Extraemos las variables para que estén disponibles directamente en el include
        extract($datos);
        include self::VISTA;
    }

    /**
     * Incluye el archivo JavaScript correspondiente
     */
    public function renderJS() {
        if (file_exists(self::JS)) { 
            include self::JS; 
        }
    }

    // ==========================================
    // MANEJO DE PERMISOS (RBAC)
    // ==========================================
    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $model->seleccionaRegistros(
            "rol_submodulo_permiso", 
            ["id_perm"], 
            "id_rol = ? AND id_sub = ? AND id_perm = ?", 
            [$id_rol, $id_sub, $id_permiso]
        );
        return ($permiso && count($permiso) > 0);
    }

    // ==========================================
    // PROCESAMIENTO TÉCNICO DEL CSV
    // ==========================================
    public function procesarCSV($ruta_temporal, $id_sub) {
        $model = new MainModel();

        // 1. Limpiar la "Aduana" antes de recibir nueva carga
        $model->ejecutarConsultaLibre("TRUNCATE TABLE temp_importacion_po");

        // 2. Abrir y leer el archivo
        $archivo = fopen($ruta_temporal, "r");
        if (!$archivo) return ["status" => "error", "msg" => "No se pudo leer el archivo CSV."];

        // 3. Leer la primera línea (Encabezados)
        $encabezados_crudos = fgetcsv($archivo, 10000, ",");
        $encabezados = array_map([$this, 'limpiarTexto'], $encabezados_crudos);

        // 4. Análisis Heurístico de columnas
        // Detectamos el consecutivo estrictamente para usarlo como filtro
        $idx_consecutivo = $this->buscarIndiceColumna($encabezados, ['consecutivo']);
        
        // DEJAMOS SOLO 'cobro' para que apunte directamente a "No. De cobro" y no se confunda
        $idx_cobro  = $this->buscarIndiceColumna($encabezados, ['cobro']);
        $idx_nombre = $this->buscarIndiceColumna($encabezados, ['nombre', 'empleado', 'personal']);
        $idx_puesto = $this->buscarIndiceColumna($encabezados, ['puesto', 'cargo', 'funcion']);
        $idx_correo = $this->buscarIndiceColumna($encabezados, ['correo', 'email', 'e-mail']);
        $idx_dep    = $this->buscarIndiceColumna($encabezados, ['area', 'departamento', 'depto', 'adscripcion']);

        if ($idx_cobro === -1 || $idx_nombre === -1) {
            fclose($archivo);
            return ["status" => "error", "msg" => "El CSV no tiene las columnas mínimas (Cobro y Nombre)."];
        }

        $registros_validos = 0;
        $valores_insert = [];

        // 5. Lectura fila por fila
        while (($fila = fgetcsv($archivo, 10000, ",")) !== FALSE) {
            // --- NUEVO FILTRO MATA-FANTASMAS ---
            // Si existe la columna Consecutivo, y está vacía, sabemos que es un título de Departamento. La ignoramos.
            if ($idx_consecutivo !== -1) {
                $val_consecutivo = trim($fila[$idx_consecutivo] ?? '');
                if (empty($val_consecutivo)) {
                    continue;
                }
            }

            $num_cobro = $this->limpiarTexto($fila[$idx_cobro] ?? '');
            if (empty($num_cobro) || !is_numeric($num_cobro)) continue;

            $nombre_completo    = $this->limpiarTexto($fila[$idx_nombre] ?? '');
            $puesto             = ($idx_puesto !== -1) ? $this->limpiarTexto($fila[$idx_puesto] ?? '') : '';
            $correo             = ($idx_correo !== -1) ? $this->limpiarTexto($fila[$idx_correo] ?? '') : '';
            $departamento_crudo = ($idx_dep    !== -1) ? $this->limpiarTexto($fila[$idx_dep]    ?? '') : '';

            $nombres_separados = $this->dividirNombreEspacial($nombre_completo);
            $dep_separado      = $this->dividirDepartamento($departamento_crudo);

            $num_cobro_esc = addslashes($num_cobro);
            $nombres_esc   = addslashes($nombres_separados['nombres']);
            $ape_pa_esc    = addslashes($nombres_separados['ape_pa']);
            $ape_ma_esc    = addslashes($nombres_separados['ape_ma']);
            $puesto_esc    = addslashes($puesto);
            $correo_esc    = addslashes($correo);
            $clave_dep_esc = addslashes($dep_separado['clave']);
            $nom_dep_esc   = addslashes($dep_separado['nombre']);

            $valores_insert[] = "('$num_cobro_esc', '$nombres_esc', '$ape_pa_esc', '$ape_ma_esc', '$puesto_esc', '$correo_esc', '$clave_dep_esc', '$nom_dep_esc')";
            $registros_validos++;
        }
        fclose($archivo);

        if ($registros_validos === 0) {
            return ["status" => "error", "msg" => "No se encontraron registros válidos."];
        }

        // 6. Volcado Masivo (Bulk Insert)
        $queryBulk = "INSERT INTO temp_importacion_po (num_cobro, nombres, ape_pa, ape_ma, puesto, correo, clave_dep_raw, nom_dep_raw) VALUES " . implode(", ", $valores_insert);
        
        $exito_volcado = $model->ejecutarConsultaLibre($queryBulk);

        if (is_string($exito_volcado) && strpos($exito_volcado, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Fallo al insertar en Temporal: " . $exito_volcado];
        }

        if ($exito_volcado) {
            return [
                "status" => "success", 
                "msg" => "Archivo procesado correctamente.", 
                "total_procesados" => $registros_validos
            ];
        } else {
            return ["status" => "error", "msg" => "Error desconocido al volcar los datos a la tabla temporal."];
        }
    }

    // ==========================================
    // AYUDANTES (HELPERS)
    // ==========================================

    private function limpiarTexto($texto) {
        $texto = mb_convert_encoding(trim($texto), 'UTF-8', 'auto');
        return str_replace('"', '', $texto);
    }

    private function buscarIndiceColumna($encabezados, $palabras_clave) {
        foreach ($encabezados as $index => $encabezado) {
            $enc_limpio = strtolower($this->quitarAcentos($encabezado));
            foreach ($palabras_clave as $clave) {
                if (strpos($enc_limpio, $clave) !== false) { return $index; }
            }
        }
        return -1;
    }

    private function quitarAcentos($cadena) {
        $originales = [
        'À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß',
        'à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ'
        ];
        $modificadas = [
            'A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','TH','ss',
            'a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','th','y'
        ];
        return str_replace($originales, $modificadas, $cadena);
    }

    private function dividirDepartamento($texto_dept) {
        $resultado = ['clave' => '', 'nombre' => $texto_dept];
        if (preg_match('/^(\d+)\s*(?:-)?\s*(.+)$/', $texto_dept, $coincidencias)) {
            $resultado['clave'] = trim($coincidencias[1]);
            $resultado['nombre'] = trim($coincidencias[2]);
        }
        return $resultado;
    }

    private function dividirNombreEspacial($nombre_completo) {
        $nombre_completo = mb_strtoupper(trim($nombre_completo), 'UTF-8');
        $prefijos = ['DE LAS ', 'DE LOS ', 'DE LA ', 'DEL ', 'DE ', 'SAN ', 'MAC ', 'MC '];
        foreach ($prefijos as $prefijo) {
            $prefijo_unido = str_replace(' ', '_', trim($prefijo)) . '_';
            $nombre_completo = str_replace($prefijo, $prefijo_unido, $nombre_completo);
        }

        $partes = array_values(array_filter(explode(' ', $nombre_completo)));
        $nombres = ''; $ape_pa = ''; $ape_ma = '';
        $cantidad = count($partes);

        if ($cantidad == 1) {
            $nombres = $partes[0];
        } elseif ($cantidad == 2) {
            $nombres = $partes[0];
            $ape_pa = $partes[1];
        } elseif ($cantidad >= 3) {
            $ape_ma = array_pop($partes);
            $ape_pa = array_pop($partes);
            $nombres = implode(' ', $partes);
        }

        return [
            'nombres' => str_replace('_', ' ', $nombres),
            'ape_pa'  => str_replace('_', ' ', $ape_pa),
            'ape_ma'  => str_replace('_', ' ', $ape_ma)
        ];
    }

    /**
     * Obtiene discrepancias de departamentos y personal que ya no está en el CSV
     */
    public function obtenerConflictos() {
        $model = new MainModel();

        // 1. Departamentos: Buscamos claves/nombres en temp que no existen en la tabla real
        $queryDepts = "SELECT DISTINCT clave_dep_raw, nom_dep_raw 
                       FROM temp_importacion_po t
                       LEFT JOIN departamentos d ON t.clave_dep_raw = d.clave_dep AND t.nom_dep_raw = d.nom_dep
                       WHERE d.id_dep IS NULL AND t.clave_dep_raw != ''";
        $conflictosDepts = $model->consultarLibre($queryDepts);
        
        $listaDepts = [];
        if($conflictosDepts) {
            foreach($conflictosDepts as $conf) {
                // Buscamos si la clave ya existe (sugerencia para vincular)
                $sugerencia = [];
                $sugerencia = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep AS nom"], "clave_dep = ?", [$conf['clave_dep_raw']]);
                $listaDepts[] = [
                    "clave_dep_raw" => $conf['clave_dep_raw'],
                    "nom_dep_raw" => $conf['nom_dep_raw'],
                    "sugerencias" => $sugerencia ?: []
                ];
            }
        }

        // 2. Bajas: Personal con estatus 1 que no vino en el archivo temporal
        $queryBajas = "SELECT po.id_po, po.num_cobro, po.nombres, po.ape_pa, po.ape_ma,
                       (SELECT COUNT(*) FROM dispositivos d WHERE d.asig_po_id = po.id_po OR d.resp_po_id = po.id_po) as total_dispositivos
                       FROM personal_operativo po
                       LEFT JOIN temp_importacion_po t ON po.num_cobro = t.num_cobro
                       WHERE t.num_cobro IS NULL AND po.estatus = 1";
        $bajas = $model->consultarLibre($queryBajas);
        // 3. Extraer TODO el catálogo de departamentos para el selector manual
        $catalogo_completo = $model->seleccionaRegistros("departamentos", ["id_dep", "clave_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        return [
            "status" => "success",
            "depts" => $listaDepts,
            "bajas" => $bajas ?: [],
            "catalogo_depts" => $catalogo_completo ?: [] // Lo enviamos al frontend
        ];
    }

    /**
     * Procesa las resoluciones del usuario, ejecuta el SP final y registra en bitácora
     */
    public function finalizarSincronizacion($resolucionesDepts, $numCobrosBaja, $id_sub = null) {
        $model = new MainModel();

        // Si no se pasó el id_sub (llamada antigua desde el router), lo resolvemos
        if (!$id_sub) {
            $id_sub = $model->obtenerIdSubmoduloPorSlug('personal') ?? 1;
        }

        // A. Resolver EXCEPCIONES de Departamentos
        foreach ($resolucionesDepts as $res) {
            if ($res['accion'] !== 'CREAR' && strpos($res['accion'], 'VINCULAR_') === 0) {
                $id_existente = str_replace('VINCULAR_', '', $res['accion']);
                $model->ejecutarConsultaLibre("UPDATE temp_importacion_po SET id_dep_final = ? WHERE clave_dep_raw = ? AND nom_dep_raw = ?", [$id_existente, $res['clave'], $res['nombre']]);
            }
        }

        // B. Llamar al Procedimiento Almacenado de Upsert
        $exito_sp = $model->ejecutarConsultaLibre("CALL sp_sincronizar_personal_csv()");
        if (is_string($exito_sp) && strpos($exito_sp, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Error SQL: " . $exito_sp];
        }
        if (!$exito_sp) {
            return ["status" => "error", "msg" => "Hubo un problema al ejecutar el motor de sincronización en la base de datos."];
        }

        // C. Aplicar Bajas seleccionadas manualmente
        if (!empty($numCobrosBaja)) {
            foreach($numCobrosBaja as $cobro) {
                $model->ejecutarConsultaLibre("UPDATE personal_operativo SET estatus = 0 WHERE num_cobro = ?", [$cobro]);
            }
        }

        // D. REGISTRO EN BITÁCORA (INTEGRADO EN EL CONTROLADOR)
        $id_usuario  = $_SESSION['usuario']['id_usuar'] ?? 1;
        $total_bajas = is_array($numCobrosBaja) ? count($numCobrosBaja) : 0;
        $total_depts = is_array($resolucionesDepts) ? count($resolucionesDepts) : 0;
        
        $detalle = "Sincronizó masivamente al Personal Operativo vía CSV. (Bajas procesadas: $total_bajas | Resoluciones de departamentos: $total_depts)";
        
        $model->agregaRegistro("bitacora", 
            ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
            [$id_usuario, $id_sub, 'IMPORT', $detalle, date('Y-m-d'), date('H:i:s')]
        );

        return ["status" => "success", "msg" => "Sincronización finalizada exitosamente."];
    }

}
?>