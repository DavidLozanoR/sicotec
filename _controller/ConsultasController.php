<?php
require_once "MainModel.php";

class ConsultasController {

    const VISTA = "_view/consultas.html.php";
    const JS = 'js/consultas.js';

    private $slug;
    

    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 6; 

        if (!$this->verificarPermiso($id_sub,PERM_CONSULTAR )) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a las Consultas.</p>
                    </div>
                  </div>';
            return; 
        }

        $departamentos = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom", "tipo_serv"], "estatus = 1 ORDER BY tipo_serv, nom ASC");
        $vlans = $model->seleccionaRegistros("vlans", ["id_vlan", "nom_vlan"], "1=1 ORDER BY nom_vlan ASC");
        $usuarios = $model->seleccionaRegistros("usuarios", ["id_usuar", "nom_usuar"], "status = 1 ORDER BY nom_usuar ASC");

        include self::VISTA;
    }
    public function renderJS(){ 
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function ejecutarConsulta($filtros) {
        $model = new MainModel();
        
        $sql = "SELECT 
                    d.id_disp, d.nom_host, d.marca, d.modelo, d.num_ser, d.num_inv,
                    CONCAT(po.nombres, ' ', IFNULL(po.ape_pa,'')) AS responsable,
                    de.nom_dep AS departamento, 
                    red.ip,
                    vlan.nom_vlan ";
        
        $from = " FROM dispositivos d
                  LEFT JOIN personal_operativo po ON d.resp_po_id = po.id_po
                  LEFT JOIN departamentos de ON po.id_dep = de.id_dep
                  LEFT JOIN infraestructura_red red ON d.id_disp = red.id_disp
                  LEFT JOIN segmentos seg ON red.id_seg = seg.id_seg
                  LEFT JOIN vlans vlan ON seg.id_vlan = vlan.id_vlan ";
                  
        $where = " WHERE 1=1 ";
        $parametros = [];

        if ($filtros['estado_asig'] === 'asignados') {
            $where .= " AND d.resp_po_id IS NOT NULL ";
        } else if ($filtros['estado_asig'] === 'almacen') {
            $where .= " AND d.resp_po_id IS NULL ";
        }

        if (!empty($filtros['id_dep'])) {
            $where .= " AND po.id_dep = ? ";
            $parametros[] = $filtros['id_dep'];
        }

        if (!empty($filtros['id_vlan'])) {
            $where .= " AND vlan.id_vlan = ? ";
            $parametros[] = $filtros['id_vlan'];
        }

        if (!empty($filtros['id_serv'])) {
            $from .= " JOIN dispositivo_servicio ds ON d.id_disp = ds.id_disp
                       JOIN versiones v ON ds.id_ver_instalada = v.id_ver
                       JOIN servicios s ON ds.id_serv = s.id_serv ";
            
            $sql .= ", s.nom AS nombre_software, v.ver AS version_instalada, v.obs AS es_obsoleto ";
            
            $where .= " AND ds.id_serv = ? ";
            $parametros[] = $filtros['id_serv'];

            if (!empty($filtros['estado_soft']) && $filtros['estado_soft'] !== 'todos') {
                if ($filtros['estado_soft'] === 'obsoleto') {
                    $where .= " AND v.obs = 1 ";
                } else if ($filtros['estado_soft'] === 'actualizado') {
                    $where .= " AND v.obs = 0 ";
                }
            }
        }

        $queryFinal = $sql . $from . $where;
        $res = $model->consultarLibre($queryFinal, $parametros);

        if (is_array($res)) {
            return ["result" => 1, "data" => $res];
        } else {
            return ["result" => 0, "msg" => "Error al ejecutar la consulta de equipos."];
        }
    }

    public function obtenerBitacora($filtros) {
        $model = new MainModel();
        
        $sql = "SELECT 
                    b.id_bitacora, u.nom_usuar, s.nom_sub, b.accion, b.detalle, 
                    DATE_FORMAT(b.fecha, '%d/%m/%Y') AS fecha_fmt, b.hora
                FROM bitacora b
                JOIN usuarios u ON b.id_usuar = u.id_usuar
                JOIN submodulos s ON b.id_sub = s.id_sub
                WHERE 1=1 ";
        
        $parametros = [];

        if (!empty($filtros['fecha_ini']) && !empty($filtros['fecha_fin'])) {
            $sql .= " AND b.fecha BETWEEN ? AND ? ";
            $parametros[] = $filtros['fecha_ini'];
            $parametros[] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['id_usuar'])) {
            $sql .= " AND b.id_usuar = ? ";
            $parametros[] = $filtros['id_usuar'];
        }

        $sql .= " ORDER BY b.fecha DESC, b.hora DESC LIMIT 1000";
        $res = $model->consultarLibre($sql, $parametros);

        if (is_array($res)) {
            return ["result" => 1, "data" => $res];
        } else {
            return ["result" => 0, "msg" => "Error al obtener los registros de la bitácora."];
        }
    }

    private function verificarPermiso($id_sub, $id_perm) {
        if (!isset($_SESSION['usuario']['id_rol'])) return false;
        $id_rol = $_SESSION['usuario']['id_rol'];
        
        $model = new MainModel();
        $res = $model->seleccionaRegistros("rol_submodulo_permiso", ["1"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_perm]);
        return !empty($res);
    }
}
?>