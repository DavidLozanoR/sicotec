<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador para el módulo de Consultas y Reportes dinámicos.
 * Gestiona la carga de la interfaz de filtros y la construcción de sentencias SQL 
 * complejas para extraer información cruzada del inventario y red.
 */
class ConsultasController {

    /** @var string Ruta al archivo de la vista HTML */
    const VISTA = "_view/consultas.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado */
    const JS = 'js/consultas.js';

    /** @var string|null Slug identificador del submódulo actual */
    private $slug;
    
    /**
     * Constructor del controlador.
     * Valida la existencia de una sesión activa de usuario.
     *
     * @param string|null $slug Slug amigable del submódulo para navegación.
     */
    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    /**
     * Prepara y renderiza la vista principal del módulo de Consultas.
     * Verifica permisos RBAC y precarga los catálogos necesarios para poblar los 
     * controles de filtrado (selects) en el frontend.
     *
     * @return void Renderiza HTML o muestra bloque de acceso denegado.
     */
    public function renderContent() {
        $model = new MainModel();
        
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 6; // Fallback al ID base de consultas

        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a las Consultas.</p>
                    </div>
                  </div>';
            return; 
        }

        // Extracción de catálogos para llenar los filtros de búsqueda en la UI
        $departamentos = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom", "tipo_serv"], "estatus = 1 ORDER BY tipo_serv, nom ASC");
        $vlans = $model->seleccionaRegistros("vlans", ["id_vlan", "nom_vlan"], "1=1 ORDER BY nom_vlan ASC");
        $usuarios = $model->seleccionaRegistros("usuarios", ["id_usuar", "nom_usuar"], "status = 1 ORDER BY nom_usuar ASC");

        include self::VISTA;
    }

    /**
     * Carga el script frontend correspondiente al controlador.
     *
     * @return void
     */
    public function renderJS(){ 
        if (file_exists(self::JS)) { include self::JS; }
    }

    /**
     * Construye y ejecuta dinámicamente una consulta SQL basada en los parámetros 
     * recibidos desde la vista. Une (JOIN) múltiples tablas de infraestructura, red y personal.
     *
     * @param array $filtros Matriz asociativa con los criterios de búsqueda (asig, dep, vlan, serv, soft).
     * @return array Estructura de respuesta con estado y conjunto de datos obtenidos.
     */
    public function ejecutarConsulta($filtros) {
        $model = new MainModel();
        
        // Base de selección (Proyección de columnas)
        $sql = "SELECT 
                    d.id_disp, d.nom_host, d.marca, d.modelo, d.num_ser, d.num_inv,
                    CONCAT(po.nombres, ' ', IFNULL(po.ape_pa,'')) AS responsable,
                    de.nom_dep AS departamento, 
                    red.ip,
                    vlan.nom_vlan ";
        
        // Origen de datos base (Relaciones estándar del dispositivo)
        $from = " FROM dispositivos d
                  LEFT JOIN personal_operativo po ON d.resp_po_id = po.id_po
                  LEFT JOIN departamentos de ON po.id_dep = de.id_dep
                  LEFT JOIN infraestructura_red red ON d.id_disp = red.id_disp
                  LEFT JOIN segmentos seg ON red.id_seg = seg.id_seg
                  LEFT JOIN vlans vlan ON seg.id_vlan = vlan.id_vlan ";
                  
        $where = " WHERE 1=1 ";
        $parametros = [];

        // Filtro de estado de asignación (Almacén vs Operativo)
        if ($filtros['estado_asig'] === 'asignados') {
            $where .= " AND d.resp_po_id IS NOT NULL ";
        } else if ($filtros['estado_asig'] === 'almacen') {
            $where .= " AND d.resp_po_id IS NULL ";
        }

        // Filtro por departamento
        if (!empty($filtros['id_dep'])) {
            $where .= " AND po.id_dep = ? ";
            $parametros[] = $filtros['id_dep'];
        }

        // Filtro por VLAN de red
        if (!empty($filtros['id_vlan'])) {
            $where .= " AND vlan.id_vlan = ? ";
            $parametros[] = $filtros['id_vlan'];
        }

        // Filtro complejo por software/servicio instalado y su estado de obsolescencia
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

        // Ensamblaje final de la sentencia y ejecución segura
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