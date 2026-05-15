<?php
/**
 * Controlador para la Auditoría y Conciliación del Servidor DHCP vs SICOSOTE.
 * Implementa OpenSpout para la lectura nativa de CSV, Excel y XML.
 */

require_once __DIR__ . "/../vendor/autoload.php"; 
require_once "MainModel.php";

use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use OpenSpout\Reader\CSV\Reader as CSVReader;

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
        
        $extension = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));
        $filas_crudas = $this->extraerDatosNativos($ruta_temporal, $extension);
        
        if (isset($filas_crudas['status'])) {
            return $filas_crudas; // Retorna si hubo error de lectura
        }

        $dhcp_records = [];
        $headerEncontrado = false;
        $cHost = -1; $cIp = -1; $cMac = -1; $cUser = -1;

        // Búsqueda inteligente de cabeceras en toda la matriz
        foreach ($filas_crudas as $row) {
            
            // Si la fila no es un array indexado (XML genérico), sacamos sus valores
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

            // Extracción de datos de la fila
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
        $joins = ["JOIN dispositivos d ON r.id_disp = d.id_disp"];
        $campos = ["r.ip", "d.id_disp", "d.nom_host", "d.mac_ethernet", "d.mac_wifi", "d.num_inv"];
        
        $db_records_raw = $model->seleccionaRegistrosJoin("infraestructura_red r", $joins, $campos, "r.id_disp IS NOT NULL AND LOWER(r.est) != 'inactivo'");

        $db_records = [];
        $mac_to_ip_db = []; 

        if ($db_records_raw) {
            foreach ($db_records_raw as $row) {
                $ip = trim($row['ip']);
                $mac_eth = !empty($row['mac_ethernet']) ? strtoupper(str_replace('-', ':', trim($row['mac_ethernet']))) : null;
                $mac_wifi = !empty($row['mac_wifi']) ? strtoupper(str_replace('-', ':', trim($row['mac_wifi']))) : null;

                $db_records[$ip] = [
                    'id_disp'      => $row['id_disp'],
                    'hostname'     => strtolower(trim($row['nom_host'])),
                    'nom_host_raw' => $row['nom_host'],
                    'mac_eth'      => $mac_eth,
                    'mac_wifi'     => $mac_wifi,
                    'num_inv'      => $row['num_inv']
                ];

                if ($mac_eth) $mac_to_ip_db[$mac_eth] = $ip;
                if ($mac_wifi) $mac_to_ip_db[$mac_wifi] = $ip;
            }
        }

        $fantasmas = [];  
        $conflictos = []; 
        $huerfanas = [];  

        // 3. Lógica de Conciliación
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
                        'detalle'           => "Conflicto de Hardware. Posible suplantación de IP o equipo reemplazado sin notificar."
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
    }

    // ==========================================
    // EXTRACTOR NATIVO CON OPENSPOUT
    // ==========================================
    private function extraerDatosNativos($ruta, $extension) {
        try {
            if (in_array($extension, ['xlsx', 'csv'])) {
                $reader = ($extension === 'csv') ? new CSVReader() : new XLSXReader(); 
                $reader->open($ruta);
                $datos = [];
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        $datos[] = $row->toArray();
                    }
                    break; // Solo la primera hoja
                }
                $reader->close();
                return $datos;
            } 
            elseif ($extension === 'xml') {
                $xml = simplexml_load_file($ruta);
                if ($xml === false) { return ["status" => "error", "msg" => "El archivo XML no se pudo leer."]; }

                $xmlString = file_get_contents($ruta);
                // Detección del formato Spreadsheet XML de Windows Server
                if (strpos($xmlString, 'urn:schemas-microsoft-com:office:spreadsheet') !== false) {
                    $datosExcel = [];
                    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
                    $rows = $xml->xpath('//ss:Row') ?: $xml->xpath('//Row'); 
                    
                    if ($rows) {
                        foreach ($rows as $row) {
                            $filaPlana = [];
                            $cells = $row->xpath('ss:Cell') ?: $row->xpath('Cell');
                            foreach ($cells as $cell) {
                                $data = $cell->xpath('ss:Data') ?: $cell->xpath('Data');
                                $filaPlana[] = !empty($data) ? trim((string)$data[0]) : '';
                            }
                            if (!empty(array_filter($filaPlana))) { $datosExcel[] = $filaPlana; }
                        }
                        if (!empty($datosExcel)) return $datosExcel; 
                    }
                }
                return ["status" => "error", "msg" => "Formato XML no soportado. Por favor expórtelo como CSV o Excel."];
            }
            return ["status" => "error", "msg" => "Formato no soportado ($extension)."];
            
        } catch (\Exception $e) {
            return ["status" => "error", "msg" => "Error interno al procesar el archivo: " . $e->getMessage()];
        }
    }
}
?>