<?php
require_once '_model/MainModel.php';

class DispositivosFormController {

    const VISTA = "_view/dispositivosForm.html.php";
    const JS = "js/dispositivosForm.js";

    private $id_disp;
    private $id_submod;
    private $slug;

    function __construct($slug = null, $id_submod = null, $id = null) {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(!isset($_SESSION['usuario'])){
            die("Error: Su sesión ha expirado o no tiene acceso a este módulo");
        }

        $this->id_disp = $id;
        $this->id_submod = $id_submod;
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();

        // 1. VALIDACIÓN DE SEGURIDAD DINÁMICA (ANTI-TAMPERING)
        $url_buscar = $this->slug; 
        
        $consultaSub = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido. La acción ha sido bloqueada para proteger la integridad del sistema.</p>
                  </div>";
            return; 
        }

        // 2. OBTENER DATOS Y CATÁLOGOS
        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    private function traerContenido($model) {
        $dispositivo = null;

        // Extraer redes: Disponibles (NULL o 0) o la que ya tiene asignada este dispositivo.
        $condicionRed = "id_disp IS NULL OR id_disp = 0";
        $paramsRed = [];
        if (!empty($this->id_disp)) {
            $condicionRed = "id_disp IS NULL OR id_disp = 0 OR id_disp = ?";
            $paramsRed = [$this->id_disp];
        }
        $redes_disponibles = $model->seleccionaRegistros("infraestructura_red", ["id_red", "ip", "mac"], $condicionRed, $paramsRed);
        
        // Extraer servicios disponibles en catálogo
        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom"]);

        // MODO EDICIÓN
        if (!empty($this->id_disp)) {
            $joins = [
                "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
                "LEFT JOIN servicios s ON d.id_serv = s.id_serv"
            ];
            $campos = [
                "d.*", "r.id_red", "r.ip", "r.mac", "s.nom as nom_servicio"
            ];
            
            $resultado = $model->seleccionaRegistrosJoin("dispositivos d", $joins, $campos, "d.id_disp = ?", [$this->id_disp]);
            
            if (!empty($resultado)) {
                $dispositivo = $resultado[0];
            } else {
                die("<div class='alert alert-warning m-4 fw-bold'>El dispositivo no existe.</div>");
            }
        }

        return [
            'redes_disponibles' => $redes_disponibles,
            'servicios'         => $servicios,
            'dispositivo'       => $dispositivo,
            'id_sub_actual'     => $this->id_submod,
            'id_disp'           => $this->id_disp
        ];
    }

    public function renderJS() {
        if(file_exists(self::JS)){
            include self::JS;
        }
    }
}
?>