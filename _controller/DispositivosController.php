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

        $tablaPrincipal = "dispositivos d";
        
        $joins = [
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
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
            "r.ip",
            "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable"
        ];
        
        $dispositivos = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, "1=1");

        if (!$dispositivos) {
            $dispositivos = [];
        } else {
            // Evaluamos el software de cada dispositivo
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
                        // Guardamos el nombre y versión para que el JS pueda buscarlos
                        $str_software .= $s['nom'] . " " . $s['ver'] . " ";
                    }
                }
                $disp['str_software'] = trim($str_software);
            }
            unset($disp); // Rompemos la referencia de memoria
        }

        return [
            'id_sub_actual' => $id_sub_actual,
            'dispositivos' => $dispositivos
        ];
    }
}
?>