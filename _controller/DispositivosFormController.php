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

        $this->slug = $slug;
        $this->id_submod = $id_submod;
        $this->id_disp = $id;
    }

    public function renderContent() {
        $model = new MainModel();

        $url_buscar = $this->slug; 
        
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido.</p>
                  </div>";
            return; 
        }

        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    private function traerContenido($model) {
        $dispositivo = null;
        $servicios_asignados = [];

        $condicionRed = "id_disp IS NULL OR id_disp = 0";
        $paramsRed = [];
        if (!empty($this->id_disp)) {
            $condicionRed = "id_disp IS NULL OR id_disp = 0 OR id_disp = ?";
            $paramsRed = [$this->id_disp];
        }
        
        $redes_disponibles = $model->seleccionaRegistros("infraestructura_red", ["id_red", "ip", "mac"], $condicionRed, $paramsRed);
        
        // Consultar personal operativo para los dropdowns
        $campos_po = ["id_po", "TRIM(CONCAT(nombres, ' ', ape_pa, ' ', COALESCE(ape_ma, ''))) AS nombre_completo"];
        $personal_operativo = $model->seleccionaRegistros("personal_operativo", $campos_po, "1=1 ORDER BY nombres ASC");

        if (!empty($this->id_disp)) {
            $joins = [
                "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp"
            ];
            $campos = [
                "d.*", "r.id_red", "r.ip", "r.mac"
            ];
            
            $resultado = $model->seleccionaRegistrosJoin("dispositivos d", $joins, $campos, "d.id_disp = ?", [$this->id_disp]);
            
            if (!empty($resultado)) {
                $dispositivo = $resultado[0];
            } else {
                die("<div class='alert alert-warning m-4 fw-bold'>El dispositivo no existe.</div>");
            }
        }

        return [
            'redes_disponibles'   => $redes_disponibles,
            'personal_operativo'  => $personal_operativo ?: [],
            'dispositivo'         => $dispositivo,
            'id_sub_actual'       => $this->id_submod,
            'id_disp'             => $this->id_disp
        ];
    }

    public function renderJS() {
        if(file_exists(self::JS)){
            include self::JS;
        }
    }
}
?>