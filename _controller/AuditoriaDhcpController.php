<?php
/**
 * Controlador para la Auditoría y Conciliación del Servidor DHCP vs SICOSOTE.
 * Implementa OpenSpout para Excel, extracción nativa blindada para CSV 
 * y un algoritmo recursivo inteligente para XML genéricos.
 */

require_once __DIR__ . "/../vendor/autoload.php"; 
require_once __DIR__ . "/../_model/MainModel.php"; 

use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class AuditoriaDhcpController {

    const VISTA = "_view/auditoriaDhcp.html.php";
    const JS    = "js/auditoriaDhcp.js";
    private $slug;

    public function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug) ?? 11;

        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger m-4">
                    <h4 class="fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                  </div>';
            return; 
        }
        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    // ==========================================
    // ALGORITMO CORE: PROCESAR ARCHIVO
    // ==========================================

    public function procesarArchivoConciliacion($ruta_temporal, $nombre_archivo_original, $id_sub) {
        try {
            $extension = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));
            
            $filas_crudas = $this->extraerDatosNativos($ruta_temporal, $extension);
            
            if (isset($filas_crudas['status'])) {
                return $filas_crudas; 
            }

            $dhcp_records = [];
            $headerEncontrado = false;
            $cHost = -1; $cIp = -1; $cMac = -1; $cUser = -1;

            foreach ($filas_crudas as $row) {
                if (!isset($row[0])) { $row = array_values($row); }

                if (!$headerEncontrado) {
                    foreach ($row as $index => $columna) {
                        $val = strtoupper(trim((string)$columna));
                        if (strpos($val, 'HOSTNAME') !== false) $cHost = $index;
                        if ($val === 'IP' || strpos($val, 'DIRECCIÓN IP') !== false || strpos($val, 'DIRECCION IP') !== false) $cIp = $index;
                        if (strpos($val, 'MAC') !== false) $cMac = $index;
                        if (strpos($val, 'USUARIO') !== false) $cUser = $index;
                    }
                    if ($cIp !== -1 && $cMac !== -1) { $headerEncontrado = true; }
                    continue;
                }

                $ip = isset($row[$cIp]) ? trim((string)$row[$cIp]) : '';
                $mac = isset($row[$cMac]) ? trim((string)$row[$cMac]) : '';

                if (empty($ip) || empty($mac)) continue;

                $hostname = ($cHost !== -1 && isset($row[$cHost])) ? trim((string)$row[$cHost]) : '';
                $usuario  = ($cUser !== -1 && isset($row[$cUser])) ? trim((string)$row[$cUser]) : '';

                $mac_norm = strtoupper(str_replace('-', ':', $mac)); 
                $host_norm = strtolower(str_ireplace('.ineadom.gob.mx', '', $hostname));

                $dhcp_records[$ip] = [
                    'hostname'     => $host_norm,
                    'hostname_raw' => $hostname,
                    'ip'           => $ip,
                    'mac'          => $mac_norm,
                    'usuario'      => $usuario
                ];
            }

            if (empty($dhcp_records)) {
                return ["status" => "error", "msg" => "El archivo no contenía registros válidos o no se detectaron las columnas IP y MAC."];
            }

            // ==========================================
            // 2. Extraer datos de la Base de Datos (SICOSOTE)
            // ==========================================
            $model = new MainModel();
            
            $joins = [
                "JOIN dispositivos d ON r.id_disp = d.id_disp",
                "LEFT JOIN departamentos dep ON d.id_dep = dep.id_dep",
                "LEFT JOIN personal_operativo po ON po.id_po = d.resp_po_id",
                "LEFT JOIN dominios dm ON d.id_dom = dm.id_dom" // <--  JOIN A LA TABLA DOMINIOS
            ];
            
            // Se agregó "d.id_dom" a la consulta
            $campos = [
                "r.ip", "d.id_disp", "d.nom_host", 
                "dm.nom_dom AS dom", 
                "d.mac_ethernet", "d.mac_wifi", "d.num_inv",
                "dep.nom_dep",
                "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable"
            ];
            
            $db_records_raw = $model->seleccionaRegistrosJoin("infraestructura_red r", $joins, $campos, "r.id_disp IS NOT NULL AND LOWER(r.est) != 'inactivo'");

            $db_records = [];
            $mac_to_ip_db = []; 

            if (is_array($db_records_raw)) {
                foreach ($db_records_raw as $row) {
                    // 3. Blindaje de variables (casteo a string) para evitar que trim(null) colapse PHP 8
                    $ip = trim((string)$row['ip']);
                    $mac_eth = !empty($row['mac_ethernet']) ? strtoupper(str_replace('-', ':', trim((string)$row['mac_ethernet']))) : null;
                    $mac_wifi = !empty($row['mac_wifi']) ? strtoupper(str_replace('-', ':', trim((string)$row['mac_wifi']))) : null;

                    //  Concatenamos el dominio traído dinámicamente de la BD (ahora sí funciona $row['dom'])
                    $nom_host_db = trim((string)$row['nom_host']);
                    $dominio_db = !empty(trim((string)$row['dom'])) ? '.' . trim((string)$row['dom']) : '';
                    $nom_host_visual = !empty($nom_host_db) ? $nom_host_db . $dominio_db : 'Sin Hostname';

                    $db_records[$ip] = [
                        'id_disp'      => $row['id_disp'],
                        'hostname'     => strtolower($nom_host_db),
                        'nom_host_raw' => $nom_host_visual, 
                        'mac_eth'      => $mac_eth,
                        'mac_wifi'     => $mac_wifi,
                        'num_inv'      => $row['num_inv'],
                        'departamento' => !empty($row['nom_dep']) ? trim((string)$row['nom_dep']) : 'Sin Asignar',
                        'responsable'  => !empty($row['responsable']) ? trim((string)$row['responsable']) : 'Almacén'
                    ];

                    if ($mac_eth) $mac_to_ip_db[$mac_eth] = $ip;
                    if ($mac_wifi) $mac_to_ip_db[$mac_wifi] = $ip;
                }
            }

            $fantasmas = [];  
            $conflictos = []; 
            $huerfanas = [];  

            foreach ($dhcp_records as $ip => $dhcp) {
                if (!isset($db_records[$ip])) {
                    if (isset($mac_to_ip_db[$dhcp['mac']])) {
                        $ip_correcta_db = $mac_to_ip_db[$dhcp['mac']];
                        $fantasmas[] = [
                            'tipo'          => 'IP Desactualizada',
                            'ip_dhcp'       => $ip,
                            'mac'           => $dhcp['mac'],
                            'hostname_dhcp' => $dhcp['hostname_raw'],
                            'usuario_dhcp'  => $dhcp['usuario'],
                            'detalle'       => "La MAC existe en SICOSOTE, pero tiene asignada la IP: {$ip_correcta_db}"
                        ];
                    } else {
                        $fantasmas[] = [
                            'tipo'          => 'Equipo Fantasma',
                            'ip_dhcp'       => $ip,
                            'mac'           => $dhcp['mac'],
                            'hostname_dhcp' => $dhcp['hostname_raw'],
                            'usuario_dhcp'  => $dhcp['usuario'],
                            'detalle'       => "Equipo detectado en la red no registrado en el inventario."
                        ];
                    }
                } else {
                    $db = $db_records[$ip];
                    if ($dhcp['mac'] !== $db['mac_eth'] && $dhcp['mac'] !== $db['mac_wifi']) {
                        $conflictos[] = [
                            'ip'                => $ip,
                            'mac_dhcp'          => $dhcp['mac'],
                            'hostname_dhcp'     => $dhcp['hostname_raw'],
                            'mac_sicosote'      => $db['mac_eth'] . ($db['mac_wifi'] ? ' / ' . $db['mac_wifi'] : ''),
                            'hostname_sicosote' => $db['nom_host_raw'],
                            'departamento'      => $db['departamento'],
                            'responsable'       => $db['responsable'],
                            'detalle'           => "Verifique si fue reemplazado, si usa un adaptador de red diferente o si es un dispositivo no autorizado."
                        ];
                    }
                }
            }

            foreach ($db_records as $ip => $db) {
                if (!isset($dhcp_records[$ip])) {
                    $huerfanas[] = [
                        'ip'                => $ip,
                        'hostname_sicosote' => $db['nom_host_raw'],
                        'mac_sicosote'      => $db['mac_eth'] . ($db['mac_wifi'] ? ' / ' . $db['mac_wifi'] : ''),
                        'inventario'        => $db['num_inv'],
                        'departamento'      => $db['departamento'],
                        'responsable'       => $db['responsable'],
                        'detalle'           => "Equipo en inventario pero el servidor DHCP no lo detecta en la red activa."
                    ];
                }
            }

            $total_analizados = count($dhcp_records);
            $model->registrarBitacora($id_sub, 'AUDITORIA', "Conciliación DHCP: {$total_analizados} registros cruzados.");

            return [
                "status" => "success", 
                "data" => [
                    "resumen" => ["total_dhcp" => $total_analizados, "total_db" => count($db_records)],
                    "fantasmas"  => $fantasmas,
                    "conflictos" => $conflictos,
                    "huerfanas"  => $huerfanas
                ]
            ];

        } catch (\Throwable $e) {
            return ["status" => "error", "msg" => "Fallo durante el análisis: " . $e->getMessage()];
        }
    }

    // ==========================================
    // EXTRACTOR MULTIFORMATO
    // ==========================================
    private function extraerDatosNativos($ruta, $extension) {
        try {
            if ($extension === 'xlsx') {
                $reader = new XLSXReader(); 
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
            
            elseif ($extension === 'csv') {
                $datos = [];
                if (($handle = fopen($ruta, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                        $datos[] = array_map(function($val) {
                            $val_utf8 = mb_convert_encoding((string)$val, 'UTF-8', 'Windows-1252');
                            return trim($val_utf8);
                        }, $data);
                    }
                    fclose($handle);
                }
                return $datos;
            } 
            
            elseif ($extension === 'xml') {
                $xmlString = file_get_contents($ruta);
                if (empty($xmlString)) return ["status" => "error", "msg" => "El archivo XML está vacío."];

                // ==========================================
                // ESCENARIO 1: XML SPREADSHEET (Exportado de Excel)
                // ==========================================
                if (strpos($xmlString, 'urn:schemas-microsoft-com:office:spreadsheet') !== false) {
                    $xml = simplexml_load_string($xmlString);
                    if ($xml !== false) {
                        $datosExcel = [];
                        $ns_ss = 'urn:schemas-microsoft-com:office:spreadsheet';
                        $xml->registerXPathNamespace('ss', $ns_ss);
                        $rows = $xml->xpath('//ss:Row') ?: $xml->xpath('//Row'); 
                        
                        if ($rows) {
                            foreach ($rows as $row) {
                                $filaPlana = [];
                                $cells = $row->xpath('ss:Cell') ?: $row->xpath('Cell');
                                $colIndex = 0; // Puntero real de columna
                                
                                foreach ($cells as $cell) {
                                    // 1. Respetar los saltos de índice de Excel
                                    $attrs = $cell->attributes($ns_ss);
                                    if (isset($attrs['Index'])) {
                                        $colIndex = (int)$attrs['Index'] - 1;
                                    }
                                    // 2. Extraer el dato
                                    $data = $cell->xpath('ss:Data') ?: $cell->xpath('Data');
                                    $filaPlana[$colIndex] = !empty($data) ? trim((string)$data[0]) : '';
                                    $colIndex++;
                                }
                                
                                // 3. Rellenar huecos para evitar desalineación
                                if (!empty($filaPlana)) {
                                    $maxKey = max(array_keys($filaPlana));
                                    $filaLimpia = [];
                                    for ($i = 0; $i <= $maxKey; $i++) {
                                        $filaLimpia[] = $filaPlana[$i] ?? '';
                                    }
                                    if (!empty(array_filter($filaLimpia))) { 
                                        $datosExcel[] = $filaLimpia; 
                                    }
                                }
                            }
                            if (!empty($datosExcel)) return $datosExcel; 
                        }
                    }
                }

                // ==========================================
                // ESCENARIO 2: XML ESTRUCTURADO GENÉRICO (<Registro><IP>...)
                // ==========================================
                $xmlString = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlString);
                $xml = simplexml_load_string($xmlString);
                if ($xml === false) return ["status" => "error", "msg" => "Formato XML corrupto o no estructurado."]; 

                $json = json_decode(json_encode($xml), true);
                $datosFilas = $this->encontrarMatrizXML($json);
                
                if (empty($datosFilas)) {
                    return ["status" => "error", "msg" => "El XML no tiene un formato tabular válido."];
                }

                $filasLimpias = [];
                $encabezadosExtraidos = false;

                foreach ($datosFilas as $fila) {
                    if (!is_array($fila)) continue;
                    
                    $filaLimpia = [];
                    foreach ($fila as $k => $v) {
                        if ($k === '@attributes' && is_array($v)) {
                            $filaLimpia = array_merge($filaLimpia, $v);
                        } elseif (!is_array($v)) { 
                            $filaLimpia[$k] = $v;
                        }
                    }

                    // 1. Inyectar las etiquetas XML como Fila 0 (Encabezados)
                    if (!$encabezadosExtraidos && !empty($filaLimpia)) {
                        $filasLimpias[] = array_keys($filaLimpia);
                        $encabezadosExtraidos = true;
                    }

                    // 2. Insertar los valores
                    $filasLimpias[] = array_values($filaLimpia); 
                }
                return $filasLimpias;
            }
            return ["status" => "error", "msg" => "Formato no soportado ($extension)."];
            
        } catch (\Exception $e) {
            return ["status" => "error", "msg" => "Error interno al extraer los datos: " . $e->getMessage()];
        }
    }

    private function encontrarMatrizXML($array) {
        if (!is_array($array)) return [];
        if (isset($array[0]) && is_array($array[0])) { return $array; }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $resultado = $this->encontrarMatrizXML($value);
                if (!empty($resultado)) return $resultado;
            }
        }
        $esFilaUnica = true;
        $claves = array_keys($array);
        if (in_array('@attributes', $claves)) {
            $claves = array_merge($claves, array_keys($array['@attributes']));
        }
        $claves_str = strtoupper(implode('_', $claves));
        if (strpos($claves_str, 'IP') !== false || strpos($claves_str, 'MAC') !== false || strpos($claves_str, 'HOST') !== false) {
            foreach ($array as $k => $v) {
                if (is_array($v) && $k !== '@attributes') {
                    $esFilaUnica = false;
                    break;
                }
            }
            if ($esFilaUnica && !empty($array)) { return [$array]; }
        }
        return [];
    }
}
?>