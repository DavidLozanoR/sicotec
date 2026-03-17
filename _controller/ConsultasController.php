<?php
require_once "MainModel.php";

class ConsultasController {

    const VISTA = "_view/consultas.html.php";
    const JS = "js/consultas.js";

    private $slug;
    const PERMISO_CONSULTAR = 4; 

    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 5; 

        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a las Consultas.</p>
                    </div>
                  </div>';
            return; 
        }

        $datos = $this->traerCatalogosFiltros($model);
        $datos['id_sub_actual'] = $id_sub;
        extract($datos);
        
        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'];
        $permiso = $model->seleccionaRegistros(
            "rol_submodulo_permiso", 
            ["id_perm"], 
            "id_rol = ? AND id_sub = ? AND id_perm = ?", 
            [$id_rol, $id_sub, $id_permiso]
        );
        return ($permiso && count($permiso) > 0);
    }

    private function traerCatalogosFiltros($model) {
        $departamentos = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        // Filtramos para que solo salgan los servicios activos según la nueva columna estatus
        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom"], "estatus = 1 ORDER BY nom ASC");
        
        return [
            'departamentos' => $departamentos ?: [],
            'servicios' => $servicios ?: []
        ];
    }

    // ==========================================
    // MOTOR DE AUDITORÍA CRUZADA (AJAX)
    // ==========================================
    public function ejecutarConsulta($filtros) {
        $model = new MainModel();

        $tabla = "dispositivos d";
        
        // CORRECCIÓN DE ESTRUCTURA:
        // 1. infraestructura_red tiene el id_disp, no al revés.
        // 2. El personal operativo se enlaza al resp_po_id
        $joins = [
            "LEFT JOIN personal_operativo po ON d.resp_po_id = po.id_po",
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp", 
            "LEFT JOIN segmentos seg ON r.id_seg = seg.id_seg",
            "LEFT JOIN departamentos dep ON seg.id_dep = dep.id_dep"
        ];
        
        $campos = [
            "d.id_disp", "d.nom_host", "d.marca", "d.modelo", "d.num_inv",
            "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable",
            "r.ip", "dep.nom_dep AS departamento"
        ];

        $condicion = "1=1";
        $parametros = [];

        // 1. FILTRO DE ASIGNACIÓN (Ocioso vs Asignado)
        if (!empty($filtros['estado_asig']) && $filtros['estado_asig'] !== 'todos') {
            if ($filtros['estado_asig'] === 'sin_asignar') {
                $condicion .= " AND d.resp_po_id IS NULL";
            } else if ($filtros['estado_asig'] === 'asignados') {
                $condicion .= " AND d.resp_po_id IS NOT NULL";
            }
        }

        // 2. FILTRO DE UBICACIÓN / DEPARTAMENTO
        if (!empty($filtros['id_dep'])) {
            $condicion .= " AND seg.id_dep = ?";
            $parametros[] = $filtros['id_dep'];
        }

        // 3. FILTRO DE SOFTWARE Y OBSOLESCENCIA
        if (!empty($filtros['id_serv'])) {
            // Añadimos los JOINs de software dinámicamente solo si el usuario audita un programa
            $joins[] = "JOIN dispositivo_servicio ds ON d.id_disp = ds.id_disp";
            $joins[] = "JOIN versiones v ON ds.id_ver_instalada = v.id_ver";
            $joins[] = "JOIN servicios s ON ds.id_serv = s.id_serv";
            
            $condicion .= " AND ds.id_serv = ?";
            $parametros[] = $filtros['id_serv'];

            // Evaluamos la obsolescencia usando la columna 'obs' de tu tabla versiones
            if (!empty($filtros['estado_soft']) && $filtros['estado_soft'] !== 'todos') {
                if ($filtros['estado_soft'] === 'obsoleto') {
                    $condicion .= " AND v.obs = 1";
                } else if ($filtros['estado_soft'] === 'actualizado') {
                    $condicion .= " AND v.obs = 0";
                }
            }

            $campos[] = "s.nom AS nombre_software";
            $campos[] = "v.ver AS version_instalada";
            $campos[] = "v.obs AS es_obsoleta";
        }

        // Ejecutamos la super consulta
        $resultados = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, $condicion, $parametros);
        
        return ["result" => 1, "data" => $resultados ?: []];
    }
}
?>