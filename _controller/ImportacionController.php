<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// NOTA IMPORTANTE: Esta ruta asume que instalaste Composer dentro de SiCoSoTe
require_once __DIR__ . "/../vendor/autoload.php"; 
require_once "MainModel.php";

use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\ODS\Reader as ODSReader;

class ImportacionController {

    const VISTA = "_view/importar.html.php";
    const JS    = "js/importar.js";

    private $slug;

    function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        $id_sub_personal = $model->obtenerIdSubmoduloPorSlug('personalop');
        
        $datos = [
            'id_sub_personal' => $id_sub_personal,
            'slug'            => $this->slug
        ];

        extract($datos);
        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

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
    // PROCESAMIENTO MULTIFORMATO (Excel, CSV, ODS, JSON, XML)
    // ==========================================
    public function procesarArchivoMasivo($ruta_temporal, $nombre_archivo_original, $id_sub) {
        $model = new MainModel();

        // 1. Limpiar la tabla temporal
        $model->ejecutarConsultaLibre("TRUNCATE TABLE temp_importacion_po");

        // 2. Detectar la extensión real del archivo
        $extension = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));
        $datosCrudos = [];

        try {
            // --- RUTA A: EXCEL, CSV, ODS (Vía Openspout) ---
            if (in_array($extension, ['xlsx', 'csv'])) {
                
                // Le decimos a Openspout qué lector usar
                if ($extension === 'csv') {
                    $reader = new CSVReader();;
                } else {
                    $reader = new XLSXReader();
                }

                $reader->open($ruta_temporal);
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $datosCrudos[] = $row->toArray();
                    }
                    break; // Solo leemos la primera pestaña del documento
                }
                $reader->close();
            }
             // RUTA B: EXCEL ANTIGUO 
            elseif ($extension === 'xls') {
                return [
                    "status" => "error", 
                    "msg" => "El formato Excel antiguo (.xls) no está soportado. Por favor, abre tu archivo original, ve a 'Guardar como' y elige 'Libro de Excel (*.xlsx)' o 'CSV UTF-8'."
                ];
            }
            
            // --- RUTA C: XML (Vía PHP Nativo) ---
            elseif ($extension === 'xml') {
                $xml = simplexml_load_file($ruta_temporal);
                $datos_json = json_decode(json_encode($xml), true);
                
                // Normalmente el XML viene envuelto en un nodo padre, esto toma los datos internos
                $datosCrudos = is_array(reset($datos_json)) ? reset($datos_json) : $datos_json;
            } 
            else {
                return ["status" => "error", "msg" => "Formato de archivo no soportado ($extension)."];
            }

        } catch (\Exception $e) {
            return ["status" => "error", "msg" => "Error al leer el archivo: " . $e->getMessage()];
        }

        if (empty($datosCrudos) || !is_array($datosCrudos)) {
            return ["status" => "error", "msg" => "El archivo está vacío o tiene una estructura inválida."];
        }

        // 3. Normalizar Encabezados y Datos
        $encabezados = [];
        $filas_datos = [];

        if (in_array($extension, ['xlsx', 'csv', 'ods'])) {
            $encabezados_crudos = array_shift($datosCrudos); // Extrae la fila 1 como encabezados
            $encabezados = array_map([$this, 'limpiarTexto'], $encabezados_crudos);
            $filas_datos = $datosCrudos;
        } else {
            // Para JSON/XML, asumimos que los encabezados son las llaves del primer objeto
            $primer_registro = reset($datosCrudos);
            if (is_array($primer_registro)) {
                $encabezados_crudos = array_keys($primer_registro);
                $encabezados = array_map([$this, 'limpiarTexto'], $encabezados_crudos);
                $filas_datos = $datosCrudos;
            } else {
                return ["status" => "error", "msg" => "El XML no tiene un formato tabular válido."];
            }
        }

        // 4. Búsqueda Heurística de Índices
        $idx_consecutivo = $this->buscarIndiceColumna($encabezados, ['consecutivo']);
        $idx_cobro       = $this->buscarIndiceColumna($encabezados, ['cobro']);
        $idx_nombre      = $this->buscarIndiceColumna($encabezados, ['nombre', 'empleado', 'personal']);
        $idx_puesto      = $this->buscarIndiceColumna($encabezados, ['puesto', 'cargo', 'funcion']);
        $idx_correo      = $this->buscarIndiceColumna($encabezados, ['correo', 'email', 'e-mail']);
        $idx_dep         = $this->buscarIndiceColumna($encabezados, ['area', 'departamento', 'depto', 'adscrip']);

        if ($idx_cobro === -1 || $idx_nombre === -1) {
            return ["status" => "error", "msg" => "El archivo no cuenta con las columnas mínimas obligatorias (Cobro y Nombre)."];
        }

        $registros_validos = 0;
        $valores_insert = [];

        // 5. Procesamiento y Extracción Fila por Fila
        foreach ($filas_datos as $fila_cruda) {
            
            // Truco: Convierte llaves {"cobro":"123"} a array posicional [0 => "123"] para compatibilidad
            $fila = array_values($fila_cruda);

            if (empty(array_filter($fila))) continue; // Saltar filas completamente vacías

            // Filtro Mata-Fantasmas de Excel (saltar títulos intermedios)
            if ($idx_consecutivo !== -1) {
                $val_consecutivo = trim($fila[$idx_consecutivo] ?? '');
                if (empty($val_consecutivo)) continue;
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

        if ($registros_validos === 0) {
            return ["status" => "error", "msg" => "No se encontraron registros válidos para importar."];
        }

        // 6. Volcado Masivo (Bulk Insert) a Temporal
        $queryBulk = "INSERT INTO temp_importacion_po (num_cobro, nombres, ape_pa, ape_ma, puesto, correo, clave_dep_raw, nom_dep_raw) VALUES " . implode(", ", $valores_insert);
        
        $exito_volcado = $model->ejecutarConsultaLibre($queryBulk);

        if (is_string($exito_volcado) && strpos($exito_volcado, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Fallo al insertar en la tabla Temporal: " . $exito_volcado];
        }

        if ($exito_volcado) {
            return [
                "status" => "success", 
                "msg" => "Archivo procesado correctamente.", 
                "total_procesados" => $registros_validos
            ];
        } else {
            return ["status" => "error", "msg" => "Error desconocido al volcar los datos a la base de datos."];
        }
    }

    // ==========================================
    // AYUDANTES (HELPERS)
    // ==========================================

    private function limpiarTexto($texto) {
        if (!is_string($texto)) return '';
        
        // --- DETECTOR DE SÍMBOLOS RAROS ---
        if (strpos($texto, '') !== false || strpos($texto, 'Ã') !== false) {
            $_SESSION['alerta_csv_formato'] = true;
        }

        $texto = str_replace('"', '', trim($texto));
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = $this->quitarAcentos($texto);
        return $texto;
    }

    private function buscarIndiceColumna($encabezados, $palabras_clave) {
        foreach ($encabezados as $index => $encabezado) {
            $enc_limpio = strtoupper($encabezado); 
            foreach ($palabras_clave as $clave) {
                if (strpos($enc_limpio, strtoupper($clave)) !== false) { 
                    return $index; 
                }
            }
        }
        return -1;
    }

    private function quitarAcentos($cadena) {
        $originales = [
            'à','','µ','¥','¢','¡','£','¤','¦','§',
            'À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß',
            'á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ'
        ];
        
        $modificadas = [
            'O','E','A','N','O','I','U','N','A','O',
            'A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','TH','ss',
            'a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','th','y'
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

    public function obtenerConflictos() {
        $model = new MainModel();

        $queryDepts = "SELECT DISTINCT clave_dep_raw, nom_dep_raw 
                       FROM temp_importacion_po t
                       LEFT JOIN departamentos d ON t.clave_dep_raw = d.clave_dep AND t.nom_dep_raw = d.nom_dep
                       WHERE d.id_dep IS NULL AND t.clave_dep_raw != ''";
        $conflictosDepts = $model->consultarLibre($queryDepts);
        
        $listaDepts = [];
        if($conflictosDepts) {
            foreach($conflictosDepts as $conf) {
                $sugerencia = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep AS nom"], "clave_dep = ?", [$conf['clave_dep_raw']]);
                $listaDepts[] = [
                    "clave_dep_raw" => $conf['clave_dep_raw'],
                    "nom_dep_raw" => $conf['nom_dep_raw'],
                    "sugerencias" => $sugerencia ?: []
                ];
            }
        }

        $queryBajas = "SELECT po.id_po, po.num_cobro, po.nombres, po.ape_pa, po.ape_ma,
                       (SELECT COUNT(*) FROM dispositivos d WHERE d.asig_po_id = po.id_po OR d.resp_po_id = po.id_po) as total_dispositivos
                       FROM personal_operativo po
                       LEFT JOIN temp_importacion_po t ON po.num_cobro = t.num_cobro
                       WHERE t.num_cobro IS NULL AND po.estatus = 1";
        $bajas = $model->consultarLibre($queryBajas);
        
        $catalogo_completo = $model->seleccionaRegistros("departamentos", ["id_dep", "clave_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");

        $resumen = $model->consultarLibre("SELECT COUNT(*) as total_filas, SUM(CASE WHEN nom_dep_raw = '' THEN 1 ELSE 0 END) as sin_depto FROM temp_importacion_po");
        $muestra = $model->consultarLibre("SELECT DISTINCT clave_dep_raw, nom_dep_raw FROM temp_importacion_po LIMIT 5");

        $alerta = false;
        if (isset($_SESSION['alerta_csv_formato'])) {
            $alerta = true;
            unset($_SESSION['alerta_csv_formato']);
        }

        return [
            "status" => "success",
            "depts" => $listaDepts,
            "bajas" => $bajas ?: [],
            "catalogo_depts" => $catalogo_completo ?: [],
            "diagnostico" => [
                "total" => $resumen[0]['total_filas'] ?? 0,
                "vacios" => $resumen[0]['sin_depto'] ?? 0,
                "muestra" => $muestra ?: []
            ],
            "alerta_csv" => $alerta
        ];
    }

    public function finalizarSincronizacion($resolucionesDepts, $numCobrosBaja, $id_sub = null) {
        $model = new MainModel();

        if (!$id_sub) {
            $id_sub = $model->obtenerIdSubmoduloPorSlug('personal') ?? 1;
        }

        foreach ($resolucionesDepts as $res) {
            if ($res['accion'] !== 'CREAR' && strpos($res['accion'], 'VINCULAR_') === 0) {
                $id_existente = str_replace('VINCULAR_', '', $res['accion']);
                $model->ejecutarConsultaLibre("UPDATE temp_importacion_po SET id_dep_final = ? WHERE clave_dep_raw = ? AND nom_dep_raw = ?", [$id_existente, $res['clave'], $res['nombre']]);
            }
        }

        $exito_sp = $model->ejecutarConsultaLibre("CALL sp_sincronizar_personal_csv()");
        if (is_string($exito_sp) && strpos($exito_sp, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Error SQL: " . $exito_sp];
        }
        if (!$exito_sp) {
            return ["status" => "error", "msg" => "Hubo un problema al ejecutar el motor de sincronización en la base de datos."];
        }

        if (!empty($numCobrosBaja)) {
            foreach($numCobrosBaja as $cobro) {
                $model->ejecutarConsultaLibre("UPDATE personal_operativo SET estatus = 0 WHERE num_cobro = ?", [$cobro]);
            }
        }

        $id_usuario  = $_SESSION['usuario']['id_usuar'] ?? 1;
        $total_bajas = is_array($numCobrosBaja) ? count($numCobrosBaja) : 0;
        $total_depts = is_array($resolucionesDepts) ? count($resolucionesDepts) : 0;
        
        $detalle = "Sincronizó masivamente al Personal Operativo (Multiformato). Bajas procesadas: $total_bajas | Resoluciones: $total_depts";
        
        $model->agregaRegistro("bitacora", 
            ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
            [$id_usuario, $id_sub, 'IMPORT', $detalle, date('Y-m-d'), date('H:i:s')]
        );

        return ["status" => "success", "msg" => "Sincronización finalizada exitosamente."];
    }

}
?>