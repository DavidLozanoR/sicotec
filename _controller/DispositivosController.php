<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

class DispositivosController {

    const VISTA = "_view/dispositivos.html.php";
    const JS = "js/dispositivos.js";
    private $slug;

    function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();

        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 6; 

        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a los Dispositivos.</p>
                    </div>
                  </div>';
            return; 
        }

        $datos = $this->traerContenido($model);
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

    private function traerContenido($model) {
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;

        $vlans = $model->seleccionaRegistros("vlans", ["id_vlan", "nom_vlan"], "1=1 ORDER BY nom_vlan ASC");

        $tablaPrincipal = "dispositivos d";
        
        // ACTUALIZACIÓN: Hacemos JOIN con marcas y modelos para extraer los nombres en texto real
        $joins = [
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
            "LEFT JOIN segmentos seg ON r.id_seg = seg.id_seg",
            "LEFT JOIN vlans v ON seg.id_vlan = v.id_vlan",
            "LEFT JOIN personal_operativo po ON d.resp_po_id = po.id_po",
            "LEFT JOIN marcas m ON d.id_marca = m.id_marca",
            "LEFT JOIN modelos modl ON d.id_modelo = modl.id_modelo"
        ];

        // ACTUALIZACIÓN: Proyectamos m.nom_marca y modl.nom_modelo en lugar de d.marca
        $campos = [
            "d.id_disp", "d.nom_host", "m.nom_marca AS marca", "modl.nom_modelo AS modelo", "d.num_ser", "d.num_inv",
            "d.mac_ethernet", "d.mac_wifi", "d.resp_po_id", "d.asig_po_id",
            "GROUP_CONCAT(
                CONCAT(
                    '<div class=\"mb-2 border-bottom border-light pb-1\">',
                        '<i class=\"fas ', IF(r.tipo_conexion = 'WiFi', 'fa-wifi', 'fa-ethernet'), ' me-2 text-secondary\"></i>',
                        '<span class=\"fw-bold\">', r.ip, '</span><br>',
                        '<small class=\"text-muted\" style=\"font-size: 0.7rem;\">', COALESCE(v.nom_vlan, 'Sin VLAN'), '</small>',
                    '</div>'
                ) SEPARATOR ''
            ) AS info_red_html",
            "d.id_dep",
            "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable"
        ];
        
        $dispositivos = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, "1=1 GROUP BY d.id_disp ORDER BY d.id_disp DESC");
        if (!$dispositivos) {
            $dispositivos = [];
        } else {
            foreach ($dispositivos as &$disp) {
                $servicios = $model->seleccionaRegistrosJoin(
                    "dispositivo_servicio ds",
                    ["JOIN servicios s ON ds.id_serv = s.id_serv", "JOIN versiones v ON ds.id_ver_instalada = v.id_ver"],
                    ["s.nom", "v.ver", "v.obs"],
                    "ds.id_disp = ?",
                    [$disp['id_disp']]
                );
                
                $disp['tiene_obsoleto'] = false;
                $str_software = "";
                if ($servicios) {
                    foreach ($servicios as $s) {
                        if ($s['obs'] == 1) $disp['tiene_obsoleto'] = true;
                        $str_software .= $s['nom'] . " " . $s['ver'] . " ";
                    }
                }
                $disp['str_software'] = trim($str_software);
                $disp['estado_asig'] = (!empty($disp['resp_po_id']) || !empty($disp['asig_po_id'])) ? 'asignado' : 'sin_asignar';
            }
            unset($disp); 
        }

        return [
            'id_sub_actual' => $id_sub_actual,
            'departamentos' => $vlans ?: [],
            'dispositivos' => $dispositivos
        ];
    }

    public function insertarDispositivo($id_sub, $datos, $interfaces, $asignaciones_software) {
        $model = new MainModel();

        // Recibimos id_marca e id_modelo en lugar de los textos
        $nom_host = !empty(trim($datos['nom_host'] ?? '')) ? trim($datos['nom_host']) : null;
        $id_marca = !empty(trim($datos['id_marca'] ?? '')) ? trim($datos['id_marca']) : null;
        $id_modelo = !empty(trim($datos['id_modelo'] ?? '')) ? trim($datos['id_modelo']) : null;
        $num_ser = !empty(trim($datos['num_ser'] ?? '')) ? trim($datos['num_ser']) : null;
        $num_inv = !empty(trim($datos['num_inv'] ?? '')) ? trim($datos['num_inv']) : null;
        $id_dom = !empty($datos['id_dom']) ? (int)$datos['id_dom'] : null;
        $especs_hw = !empty(trim($datos['especs_hw'] ?? '')) ? trim($datos['especs_hw']) : null;
        $id_dep= !empty(trim($datos['id_dep'] ?? '')) ? trim($datos['id_dep']) : null;

        $resp_po_id = !empty($datos['resp_po_id']) ? $datos['resp_po_id'] : null;
        $asig_po_id = !empty($datos['asig_po_id']) ? $datos['asig_po_id'] : null;
        $id_u_int = $_SESSION['usuario']['id_usuar'] ?? null;
        $fec_u_int = date('Y-m-d H:i:s');

        $mac_ethernet = null;
        $mac_wifi = null;

        if ($this->verificarExistenciaHostname($nom_host)) {
            return ["status" => "error", "msg" => "El Hostname '{$nom_host}' ya se encuentra registrado. Por favor, asigne un nombre único."];
        }

        foreach ($interfaces as $intf) {
            if ($intf['tipo_conexion'] === 'Ethernet') $mac_ethernet = trim($intf['mac']);
            if ($intf['tipo_conexion'] === 'WiFi') $mac_wifi = trim($intf['mac']);
        }

        // Mapeo exacto hacia las columnas de la Base de Datos
        $camposDispositivo = ["nom_host", "id_marca", "id_modelo", "num_ser", "num_inv", "mac_ethernet", "mac_wifi", "id_dom", "especs_hw", "resp_po_id","id_dep", "asig_po_id", "id_u_int", "fec_u_int"];
        $paramsDispositivo = [$nom_host, $id_marca, $id_modelo, $num_ser, $num_inv, $mac_ethernet, $mac_wifi, $id_dom, $especs_hw, $resp_po_id,$id_dep,$asig_po_id, $id_u_int, $fec_u_int];

        $detalle = "Registro de nuevo dispositivo. Host: " . ($nom_host ?: 'N/A');
        $id_disp = $model->agregaRegistroConBitacora("dispositivos", $camposDispositivo, $paramsDispositivo, $id_sub, $detalle);
        $this->procesarInterfacesYSoftware($model, $id_disp, $interfaces, $asignaciones_software);
        
        if (!$id_disp) return ["status" => "error", "msg" => "Error al registrar el dispositivo en la base de datos."];

        return ["status" => "success", "result" => 1, "msg" => "El dispositivo fue guardado correctamente."];
    }

    public function actualizarDispositivo($id_sub, $id_disp, $datos, $interfaces, $asignaciones_software) {
        $model = new MainModel();

        $nom_host = !empty(trim($datos['nom_host'] ?? '')) ? trim($datos['nom_host']) : null;
        $id_marca = !empty(trim($datos['id_marca'] ?? '')) ? trim($datos['id_marca']) : null;
        $id_modelo = !empty(trim($datos['id_modelo'] ?? '')) ? trim($datos['id_modelo']) : null;
        $num_ser = !empty(trim($datos['num_ser'] ?? '')) ? trim($datos['num_ser']) : null;
        $num_inv = !empty(trim($datos['num_inv'] ?? '')) ? trim($datos['num_inv']) : null;
        $id_dom = !empty($datos['id_dom']) ? (int)$datos['id_dom'] : null;
        $especs_hw = !empty(trim($datos['especs_hw'] ?? '')) ? trim($datos['especs_hw']) : null;
        $id_dep= !empty(trim($datos['id_dep'] ?? '')) ? trim($datos['id_dep']) : null;

        $resp_po_id = !empty($datos['resp_po_id']) ? $datos['resp_po_id'] : null;
        $asig_po_id = !empty($datos['asig_po_id']) ? $datos['asig_po_id'] : null;

        $id_u_int = $_SESSION['usuario']['id_usuar'] ?? null;
        $fec_u_int = date('Y-m-d H:i:s');

        $mac_ethernet = null;
        $mac_wifi = null;
        
        if ($this->verificarExistenciaHostname($nom_host,$id_disp)) {
            return ["status" => "error", "msg" => "El Hostname '{$nom_host}' ya se encuentra registrado. Por favor, asigne un nombre único."];
        }
        foreach ($interfaces as $intf) {
            if ($intf['tipo_conexion'] === 'Ethernet') $mac_ethernet = trim($intf['mac']);
            if ($intf['tipo_conexion'] === 'WiFi') $mac_wifi = trim($intf['mac']);
        }

        $resultadoProceso = $this->procesarInterfacesYSoftware($model, $id_disp, $interfaces, $asignaciones_software);

        if (isset($resultadoProceso['status']) && $resultadoProceso['status'] === 'error') {
            return $resultadoProceso;
        }

        $camposDispositivo = ["nom_host", "id_marca", "id_modelo", "num_ser", "num_inv", "mac_ethernet", "mac_wifi", "id_dom", "especs_hw", "resp_po_id","id_dep", "asig_po_id", "id_u_int", "fec_u_int"];
        $paramsUpdate = [$nom_host, $id_marca, $id_modelo, $num_ser, $num_inv, $mac_ethernet, $mac_wifi, $id_dom, $especs_hw, $resp_po_id,$id_dep, $asig_po_id, $id_u_int, $fec_u_int, $id_disp];

        $detalle = "Actualización de dispositivo. Host: " . ($nom_host ?: 'N/A');
        $model->actualizaRegistroConBitacora("dispositivos", $camposDispositivo, "id_disp = ?", $paramsUpdate, $id_sub, $detalle);

        return ["status" => "success", "result" => 1, "msg" => "El dispositivo fue actualizado correctamente."];
    }

    private function verificarExistenciaHostname($nom_host, $id_disp_actual = null) {
        if (empty($nom_host)) return false;

        $model = new MainModel();
        $where = "nom_host = ?";
        $params = [$nom_host];

        if (!empty($id_disp_actual)) {
            $where .= " AND id_disp != ?";
            $params[] = $id_disp_actual;
        }

        $resultado = $model->seleccionaRegistros("dispositivos", ["id_disp"], $where, $params);
        return !empty($resultado);
    }

    private function procesarInterfacesYSoftware($model, $id_disp, $interfaces, $asignaciones_software) {
        if (!empty($interfaces) && is_array($interfaces)) {
            foreach ($interfaces as $intf) {
                if (!empty($intf['mac'])) {
                    $mac = $intf['mac'];
                    $duplicado = $model->seleccionaRegistros(
                        "dispositivos", 
                        ["nom_host"], 
                        "(mac_ethernet = ? OR mac_wifi = ?) AND id_disp != ?", 
                        [$mac, $mac, $id_disp]
                    );
                    
                    if (!empty($duplicado)) {
                        return [
                            "status" => "error", 
                            "msg" => "La dirección MAC ({$mac}) ya está registrada en el dispositivo: " . $duplicado[0]['nom_host']
                        ];
                    }
                }
            }
        }
        
        $model->actualizaRegistro("infraestructura_red", ["id_disp", "tipo_conexion"], "id_disp = ?", [null, null, $id_disp]);
        if (!empty($interfaces) && is_array($interfaces)) {
            foreach ($interfaces as $intf) {
                if (!empty($intf['id_red'])) {
                    $model->actualizaRegistro("infraestructura_red", ["id_disp", "tipo_conexion"], "id_red = ?", [$id_disp, $intf['tipo_conexion'], $intf['id_red']]);
                }
            }
        }
        
        $model->borraRegistro("dispositivo_servicio", "id_disp = ?", [$id_disp]);
        if (!empty($asignaciones_software) && is_array($asignaciones_software)) {
            foreach ($asignaciones_software as $software) {
                if (!empty($software['id_serv']) && !empty($software['id_ver_instalada'])) {
                    $model->agregaRegistro("dispositivo_servicio", ["id_disp", "id_serv", "id_ver_instalada"], [$id_disp, $software['id_serv'], $software['id_ver_instalada']]);
                }
            }
        }
    }
    
    public function bajaDispositivo($id_sub, $id_disp) {
        $model = new MainModel();
        
        // ACTUALIZACIÓN: Traemos los nombres de marca y modelo para una bitácora legible
        $disp_actual = $model->seleccionaRegistrosJoin(
            "dispositivos d", 
            ["LEFT JOIN marcas m ON d.id_marca = m.id_marca", "LEFT JOIN modelos modl ON d.id_modelo = modl.id_modelo"],
            ["d.*", "m.nom_marca", "modl.nom_modelo"], 
            "d.id_disp = ?", 
            [$id_disp]
        );
        
        if (empty($disp_actual)) return ["status" => "error", "msg" => "El dispositivo no existe o ya fue eliminado."];
        $d = $disp_actual[0];

        $red_actual = $model->seleccionaRegistros("infraestructura_red", ["tipo_conexion", "ip"], "id_disp = ?", [$id_disp]);
        $ip_snapshot = '';
        if (!empty($red_actual)) {
            foreach($red_actual as $r) {
                $ip_snapshot .= $r['tipo_conexion'] . ": " . $r['ip'] . " | ";
            }
            $ip_snapshot = rtrim($ip_snapshot, " | ");
        } else {
            $ip_snapshot = 'Ninguna';
        }

        $servicios_actuales = $model->seleccionaRegistros("dispositivo_servicio", ["id_serv", "id_ver_instalada"], "id_disp = ?", [$id_disp]);
        $serv_snapshot = empty($servicios_actuales) ? 'Ninguno' : json_encode($servicios_actuales);

        $nom_marca = $d['nom_marca'] ?? 'N/A';
        $nom_modelo = $d['nom_modelo'] ?? 'N/A';

        $detalle_fotografia = "RESPALDO PRE-BAJA | Host: {$d['nom_host']} | Marca: {$nom_marca} | Modelo: {$nom_modelo} | Serie: {$d['num_ser']} | Inv: {$d['num_inv']} | MAC Eth: {$d['mac_ethernet']} | MAC WiFi: {$d['mac_wifi']} | IP Liberadas: {$ip_snapshot} | Asig_PO: {$d['asig_po_id']} | Resp_PO: {$d['resp_po_id']} | Software: {$serv_snapshot}";

        $model->registrarBitacora($id_sub, 'BACKUP_BAJA', $detalle_fotografia);
        
        $model->actualizaRegistro("infraestructura_red", ["id_disp", "tipo_conexion"], "id_disp = ?", [null, null, $id_disp]);
        $model->borraRegistroConBitacora("dispositivo_servicio", "id_disp = ?", [$id_disp], $id_sub, "Limpieza de software vinculada al dispositivo: {$d['nom_host']}");
        
        $exito_baja = $model->borraRegistroConBitacora("dispositivos", "id_disp = ?", [$id_disp], $id_sub, "Baja definitiva del dispositivo: {$d['nom_host']}");
        if (!$exito_baja) return ["status" => "error", "msg" => "Ocurrió un error al intentar eliminar el dispositivo físico."];

        return ["status" => "success", "msg" => "El dispositivo fue dado de baja. La información fue respaldada en bitácora."];
    }

    public function obtenerVersionesActualizar($id_serv) {
        $model = new MainModel();
        $versiones = $model->seleccionaRegistros("versiones", ["id_ver", "ver"], "id_serv = ? AND obs = 0 ORDER BY ver DESC", [$id_serv]);
        return ["status" => "success", "data" => $versiones ?: []];
    }

    public function actualizarVersionDispositivo($id_sub, $id_disp, $id_serv, $id_ver_nueva) {
        $model = new MainModel();
        
        $disp_info = $model->seleccionaRegistros("dispositivos", ["nom_host"], "id_disp = ?", [$id_disp]);
        $serv_info = $model->seleccionaRegistros("servicios", ["nom"], "id_serv = ?", [$id_serv]);
        $ver_info = $model->seleccionaRegistros("versiones", ["ver"], "id_ver = ?", [$id_ver_nueva]);

        $nom_host = $disp_info[0]['nom_host'] ?? "ID $id_disp";
        $nom_serv = $serv_info[0]['nom'] ?? "ID $id_serv";
        $nom_ver = $ver_info[0]['ver'] ?? "ID $id_ver_nueva";

        $exito = $model->actualizaRegistro("dispositivo_servicio", ["id_ver_instalada"], "id_disp = ? AND id_serv = ?", [$id_ver_nueva, $id_disp, $id_serv]);

        if ($exito !== false) {
            $model->registrarBitacora($id_sub, 'MODIFICAR', "Actualizó '{$nom_serv}' a la versión '{$nom_ver}' en el equipo '{$nom_host}'");
            return ["status" => "success", "msg" => "El software fue actualizado a la versión {$nom_ver} correctamente."];
        } else {
            return ["status" => "error", "msg" => "Error al intentar actualizar la versión."];
        }
    }

    public function obtenerModelosPorMarca($id_marca) {
        try {
            $model = new MainModel();
            $modelos = $model->seleccionaRegistros("modelos", ["id_modelo", "nom_modelo"], "id_marca = ? AND estatus = 1 ORDER BY nom_modelo ASC", [$id_marca]);
            if ($modelos === false) { return ["status" => "success", "data" => []]; }
            return ["status" => "success", "data" => $modelos];
        } catch (Exception $e) {
            return ["status" => "error", "msg" => "Error interno: " . $e->getMessage()];
        }
    }
    public function obtenerCatalogosActualizados() {
        $model = new MainModel();
        
        $marcas = $model->seleccionaRegistros("marcas", ["id_marca", "nom_marca"], "estatus = 1 ORDER BY nom_marca ASC");
        $departamentos = $model->seleccionaRegistros("departamentos", ["id_dep", "clave_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        $dominios = $model->seleccionaRegistros("dominios", ["id_dom", "nom_dom"], "estatus = 1 ORDER BY nom_dom ASC");
        
        return [
            "status" => "success",
            "data" => [
                "marcas" => $marcas ?: [],
                "departamentos" => $departamentos ?: [],
                "dominios" => $dominios ?: []
            ]
        ];
    }
}
?>