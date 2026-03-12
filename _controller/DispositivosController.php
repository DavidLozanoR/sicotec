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
        $consultaSub = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;

        $tablaPrincipal = "dispositivos d";
        $joins = [
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
            "LEFT JOIN servicios s ON d.id_serv = s.id_serv"
        ];
        $campos = [
            "d.id_disp", 
            "d.nom_host", 
            "d.marca", 
            "d.modelo", 
            "d.num_ser", 
            "r.ip", 
            "s.nom as nom_servicio"
        ];
        
        $dispositivos = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, "1=1");

        if (!$dispositivos) {
            $dispositivos = [];
        }

        return [
            'id_sub_actual' => $id_sub_actual,
            'dispositivos' => $dispositivos
        ];
    }
}
?>