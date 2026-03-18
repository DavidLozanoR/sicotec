<?php
require_once "_model/MainModel.php";

class DispositivosController {

    const VISTA = "_view/dispositivos.html.php";
    const JS = "js/dispositivos.js";

    private $slug;

    function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario'])) {
            die("Error: Su sesión ha expirado o no tiene acceso a este módulo");
        }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        
        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) {
            include self::JS;
        }
    }

    private function traerContenido($model) {
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;

        // Catálogo de departamentos para el Filtro
        $departamentos = $model->seleccionaRegistros("departamentos", ["id_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");

        $tablaPrincipal = "dispositivos d";
        
        // Hacemos el Join hasta llegar a Departamentos
        $joins = [
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
            "LEFT JOIN segmentos seg ON r.id_seg = seg.id_seg",
            "LEFT JOIN departamentos dep ON seg.id_dep = dep.id_dep",
            "LEFT JOIN personal_operativo po ON d.resp_po_id = po.id_po"
        ];

        $campos = [
            "d.id_disp", 
            "d.nom_host", 
            "d.marca", 
            "d.modelo", 
            "d.num_ser", 
            "d.num_inv",
            "d.mac",
            "d.resp_po_id",
            "d.asig_po_id",
            "r.ip",
            "dep.id_dep",
            "dep.nom_dep AS departamento",
            "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable"
        ];
        
        $dispositivos = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, "1=1");

        if (!$dispositivos) {
            $dispositivos = [];
        } else {
            foreach ($dispositivos as &$disp) {
                $servicios = $model->seleccionaRegistrosJoin(
                    "dispositivo_servicio ds",
                    [
                        "JOIN servicios s ON ds.id_serv = s.id_serv",
                        "JOIN versiones v ON ds.id_ver_instalada = v.id_ver"
                    ],
                    ["s.nom", "v.ver", "v.obs"],
                    "ds.id_disp = ?",
                    [$disp['id_disp']]
                );
                
                $disp['tiene_obsoleto'] = false;
                $str_software = "";

                if ($servicios) {
                    foreach ($servicios as $s) {
                        if ($s['obs'] == 1) {
                            $disp['tiene_obsoleto'] = true;
                        }
                        $str_software .= $s['nom'] . " " . $s['ver'] . " ";
                    }
                }
                $disp['str_software'] = trim($str_software);

                // LÓGICA DE ASIGNACIÓN: Si no tiene responsable ni usuario asignado, está en Almacén
                $disp['estado_asig'] = (!empty($disp['resp_po_id']) || !empty($disp['asig_po_id'])) ? 'asignado' : 'sin_asignar';
            }
            unset($disp); 
        }

        return [
            'id_sub_actual' => $id_sub_actual,
            'departamentos' => $departamentos ?: [],
            'dispositivos' => $dispositivos
        ];
    }
}
?>