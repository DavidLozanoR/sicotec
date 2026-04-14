<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../vendor/autoload.php"; 
require_once "MainModel.php";

use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\ODS\Reader as ODSReader;

class ImportacionController {

    const VISTA = "_view/importar.html.php";
    const JS    = "js/importar.js";

    private $model;
    private $slug;

    public function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->model = new MainModel();
        $this->slug = $slug;
    }

    public function renderContent() {
        $id_sub_personal = $this->model->obtenerIdSubmoduloPorSlug('personalop');
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
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $this->model->seleccionaRegistros(
            "rol_submodulo_permiso", 
            ["id_perm"], 
            "id_rol = ? AND id_sub = ? AND id_perm = ?", 
            [$id_rol, $id_sub, $id_permiso]
        );
        return ($permiso && count($permiso) > 0);
    }

    // ==========================================
    // FASE 1: CONTROLADOR PRINCIPAL DE IMPORTACIÓN
    // ==========================================
    public function procesarArchivoMasivo($ruta_temporal, $nombre_archivo_original, $id_sub) {
        $this->model->ejecutarConsultaLibre("TRUNCATE TABLE temp_importacion_po");

        $extension = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));

        // 1. Extracción de datos crudos
        $datosCrudos = $this->extraerDatosNativos($ruta_temporal, $extension);
        if (isset($datosCrudos['status'])) return $datosCrudos; // Retorna si hay error

        if (empty($datosCrudos) || !is_array($datosCrudos)) {
            return ["status" => "error", "msg" => "El archivo está vacío o tiene una estructura inválida."];
        }

        // 2. Separación de Encabezados y Datos
        $estructura = $this->estructurarMatriz($datosCrudos, $extension);
        if (isset($estructura['status'])) return $estructura; // Error formato XML

        // 3. Mapeo Heurístico (Inteligencia de columnas)
        $indices = $this->mapearIndicesColumnas($estructura['encabezados']);
        if ($indices['cobro'] === -1 || $indices['nombre'] === -1) {
            return ["status" => "error", "msg" => "El archivo no cuenta con las columnas mínimas obligatorias (Cobro y Nombre)."];
        }

        // 4. Transformación y Limpieza de Filas
        $valores_insert = [];
        foreach ($estructura['filas'] as $fila_cruda) {
            $fila = array_values($fila_cruda);
            if (empty(array_filter($fila))) continue; // Salta filas vacías

            $registroSQL = $this->procesarFilaSQL($fila, $indices);
            if ($registroSQL) {
                $valores_insert[] = $registroSQL;
            }
        }

        if (empty($valores_insert)) {
            return ["status" => "error", "msg" => "No se encontraron registros válidos para importar."];
        }

        // 5. Carga (Volcado Masivo BD)
        $queryBulk = "INSERT INTO temp_importacion_po (num_cobro, nombres, ape_pa, ape_ma, puesto, correo, clave_dep_raw, nom_dep_raw) VALUES " . implode(", ", $valores_insert);
        $exito_volcado = $this->model->ejecutarConsultaLibre($queryBulk);

        if (is_string($exito_volcado) && strpos($exito_volcado, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Fallo al insertar en la tabla Temporal: " . $exito_volcado];
        }

        return $exito_volcado 
            ? ["status" => "success", "msg" => "Archivo procesado correctamente.", "total_procesados" => count($valores_insert)]
            : ["status" => "error", "msg" => "Error desconocido al volcar los datos a la base de datos."];
    }

    // ==========================================
    // FASE 2: MÉTODOS PRIVADOS DE EXTRACCIÓN Y MAPEO (ETL)
    // ==========================================

    private function extraerDatosNativos($ruta, $extension) {
        try {
            if (in_array($extension, ['xlsx', 'csv', 'ods'])) {
                if ($extension === 'csv') { $reader = new CSVReader(); } 
                elseif ($extension === 'ods') { $reader = new ODSReader(); } 
                else { $reader = new XLSXReader(); }

                $reader->open($ruta);
                $datos = [];
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $datos[] = $row->toArray();
                    }
                    break; 
                }
                $reader->close();
                return $datos;
            } 
            elseif ($extension === 'xls') {
                return ["status" => "error", "msg" => "Formato Excel antiguo (.xls) no soportado."];
            } 
            elseif ($extension === 'xml') {
                $xml = simplexml_load_file($ruta);
                $json = json_decode(json_encode($xml), true);
                return is_array(reset($json)) ? reset($json) : $json;
            } 
            return ["status" => "error", "msg" => "Formato de archivo no soportado ($extension)."];
        } catch (\Exception $e) {
            return ["status" => "error", "msg" => "Error al leer el archivo: " . $e->getMessage()];
        }
    }

    private function estructurarMatriz($datosCrudos, $extension) {
        $encabezados = [];
        $filas = [];

        if (in_array($extension, ['xlsx', 'csv', 'ods'])) {
            $encabezados = array_map([$this, 'limpiarTexto'], array_shift($datosCrudos));
            $filas = $datosCrudos;
        } else {
            $primer_registro = reset($datosCrudos);
            if (is_array($primer_registro)) {
                $encabezados = array_map([$this, 'limpiarTexto'], array_keys($primer_registro));
                $filas = $datosCrudos;
            } else {
                return ["status" => "error", "msg" => "El XML no tiene un formato tabular válido."];
            }
        }
        return ['encabezados' => $encabezados, 'filas' => $filas];
    }

    private function mapearIndicesColumnas($encabezados) {
        return [
            'consecutivo' => $this->buscarIndiceColumna($encabezados, ['consecutivo']),
            'cobro'       => $this->buscarIndiceColumna($encabezados, ['cobro']),
            'nombre'      => $this->buscarIndiceColumna($encabezados, ['nombre', 'empleado', 'personal']),
            'puesto'      => $this->buscarIndiceColumna($encabezados, ['puesto', 'cargo', 'funcion']),
            'correo'      => $this->buscarIndiceColumna($encabezados, ['correo', 'email', 'e-mail']),
            'dep'         => $this->buscarIndiceColumna($encabezados, ['area', 'departamento', 'depto', 'adscrip'])
        ];
    }

    private function procesarFilaSQL($fila, $idx) {
        // Validación de Consecutivo Fantasma
        if ($idx['consecutivo'] !== -1 && empty(trim($fila[$idx['consecutivo']] ?? ''))) return null;

        $num_cobro = $this->limpiarTexto($fila[$idx['cobro']] ?? '');
        if (empty($num_cobro) || !is_numeric($num_cobro)) return null;

        // Extracción
        $nombre_full = $this->limpiarTexto($fila[$idx['nombre']] ?? '');
        $puesto      = ($idx['puesto'] !== -1) ? $this->limpiarTexto($fila[$idx['puesto']] ?? '') : '';
        $correo      = ($idx['correo'] !== -1) ? $this->limpiarTexto($fila[$idx['correo']] ?? '') : '';
        $dep_crudo   = ($idx['dep']    !== -1) ? $this->limpiarTexto($fila[$idx['dep']]    ?? '') : '';

        // Transformación
        $nombres = $this->dividirNombreEspacial($nombre_full);
        $depto   = $this->dividirDepartamento($dep_crudo);

        // Construcción de registro para Query
        return sprintf(
            "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            addslashes($num_cobro),
            addslashes($nombres['nombres']),
            addslashes($nombres['ape_pa']),
            addslashes($nombres['ape_ma']),
            addslashes($puesto),
            addslashes($correo),
            addslashes($depto['clave']),
            addslashes($depto['nombre'])
        );
    }

    // ==========================================
    // FASE 3: HELPERS DE TEXTO Y REGLAS DE NEGOCIO
    // ==========================================

    private function limpiarTexto($texto) {
        if (!is_string($texto)) return '';
        if (strpos($texto, '') !== false || strpos($texto, 'Ã') !== false) {
            $_SESSION['alerta_csv_formato'] = true;
        }
        $texto = str_replace('"', '', trim($texto));
        return $this->quitarAcentos(mb_strtoupper($texto, 'UTF-8'));
    }

    private function buscarIndiceColumna($encabezados, $palabras_clave) {
        foreach ($encabezados as $index => $encabezado) {
            $enc_limpio = strtoupper($encabezado); 
            foreach ($palabras_clave as $clave) {
                if (strpos($enc_limpio, strtoupper($clave)) !== false) return $index; 
            }
        }
        return -1;
    }

    private function quitarAcentos($cadena) {
        $originales = ['À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß'];
        $modificadas = ['A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','TH','ss'];
        // Se omiten minúsculas en el array de búsqueda por rendimiento, ya que limpiarTexto hace mb_strtoupper primero.
        return str_replace($originales, $modificadas, $cadena);
    }

    private function dividirDepartamento($texto_dept) {
        $resultado = ['clave' => '', 'nombre' => $texto_dept];
        if (preg_match('/^(\d+)\s*(?:-)?\s*(.+)$/', $texto_dept, $coincidencias)) {
            $resultado['clave']  = trim($coincidencias[1]);
            $resultado['nombre'] = trim($coincidencias[2]);
        }
        return $resultado;
    }

    private function dividirNombreEspacial($nombre_completo) {
        $nombre_completo = trim($nombre_completo);
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

    // ==========================================
    // FASE 4: MÉTODOS DE SINCRONIZACIÓN Y CONSULTA
    // ==========================================

    public function obtenerConflictos() {
        $queryDepts = "SELECT DISTINCT clave_dep_raw, nom_dep_raw 
                       FROM temp_importacion_po t
                       LEFT JOIN departamentos d ON t.clave_dep_raw = d.clave_dep AND t.nom_dep_raw = d.nom_dep
                       WHERE d.id_dep IS NULL AND t.clave_dep_raw != ''";
        $conflictosDepts = $this->model->consultarLibre($queryDepts);
        
        $listaDepts = [];
        if($conflictosDepts) {
            foreach($conflictosDepts as $conf) {
                $sugerencia = $this->model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep AS nom"], "clave_dep = ?", [$conf['clave_dep_raw']]);
                $listaDepts[] = [
                    "clave_dep_raw" => $conf['clave_dep_raw'],
                    "nom_dep_raw"   => $conf['nom_dep_raw'],
                    "sugerencias"   => $sugerencia ?: []
                ];
            }
        }

        $queryBajas = "SELECT po.id_po, po.num_cobro, po.nombres, po.ape_pa, po.ape_ma,
                       (SELECT COUNT(*) FROM dispositivos d WHERE d.asig_po_id = po.id_po OR d.resp_po_id = po.id_po) as total_dispositivos
                       FROM personal_operativo po
                       LEFT JOIN temp_importacion_po t ON po.num_cobro = t.num_cobro
                       WHERE t.num_cobro IS NULL AND po.estatus = 1";
        $bajas = $this->model->consultarLibre($queryBajas);
        
        $catalogo_completo = $this->model->seleccionaRegistros("departamentos", ["id_dep", "clave_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        $resumen = $this->model->consultarLibre("SELECT COUNT(*) as total_filas, SUM(CASE WHEN nom_dep_raw = '' THEN 1 ELSE 0 END) as sin_depto FROM temp_importacion_po");
        $muestra = $this->model->consultarLibre("SELECT DISTINCT clave_dep_raw, nom_dep_raw FROM temp_importacion_po LIMIT 5");

        $alerta = false;
        if (isset($_SESSION['alerta_csv_formato'])) {
            $alerta = true;
            unset($_SESSION['alerta_csv_formato']);
        }

        return [
            "status"         => "success",
            "depts"          => $listaDepts,
            "bajas"          => $bajas ?: [],
            "catalogo_depts" => $catalogo_completo ?: [],
            "diagnostico"    => [
                "total"   => $resumen[0]['total_filas'] ?? 0,
                "vacios"  => $resumen[0]['sin_depto']   ?? 0,
                "muestra" => $muestra ?: []
            ],
            "alerta_csv"     => $alerta
        ];
    }

    public function finalizarSincronizacion($resolucionesDepts, $numCobrosBaja, $id_sub = null) {
        if (!$id_sub) {
            $id_sub = $this->model->obtenerIdSubmoduloPorSlug('personal') ?? 1;
        }

        foreach ($resolucionesDepts as $res) {
            if ($res['accion'] !== 'CREAR' && strpos($res['accion'], 'VINCULAR_') === 0) {
                $id_existente = str_replace('VINCULAR_', '', $res['accion']);
                $this->model->ejecutarConsultaLibre("UPDATE temp_importacion_po SET id_dep_final = ? WHERE clave_dep_raw = ? AND nom_dep_raw = ?", [$id_existente, $res['clave'], $res['nombre']]);
            }
        }

        $exito_sp = $this->model->ejecutarConsultaLibre("CALL sp_sincronizar_personal_csv()");
        if (is_string($exito_sp) && strpos($exito_sp, 'ERROR_BD') !== false) {
            return ["status" => "error", "msg" => "Error SQL: " . $exito_sp];
        }
        if (!$exito_sp) {
            return ["status" => "error", "msg" => "Hubo un problema al ejecutar el motor de sincronización en la base de datos."];
        }

        if (!empty($numCobrosBaja)) {
            foreach($numCobrosBaja as $cobro) {
                $this->model->ejecutarConsultaLibre("UPDATE personal_operativo SET estatus = 0 WHERE num_cobro = ?", [$cobro]);
            }
        }

        $id_usuario  = $_SESSION['usuario']['id_usuar'] ?? 1;
        $total_bajas = is_array($numCobrosBaja) ? count($numCobrosBaja) : 0;
        $total_depts = is_array($resolucionesDepts) ? count($resolucionesDepts) : 0;
        
        $detalle = "Sincronizó masivamente al Personal Operativo (Multiformato). Bajas procesadas: $total_bajas | Resoluciones: $total_depts";
        
        $this->model->agregaRegistro("bitacora", 
            ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
            [$id_usuario, $id_sub, 'IMPORT', $detalle, date('Y-m-d'), date('H:i:s')]
        );

        return ["status" => "success", "msg" => "Sincronización finalizada exitosamente."];
    }
}
?>