<?php
require_once 'MainModel.php';

class PersonalOPFormController{

const VISTA = "_view/personalOperativoForm.html.php";
const JS = "js/personalOperativoForm.js";

private $id_op;//id del personal operativo
private $id_submod;
private $slug;


function __construct($slug=null,$id_submod=null,$id=null){

     // Validamos que exista una sesión activa
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(!isset($_SESSION['usuario'])){
            die("Error: Su sesión ha expirado o no tiene acceso a este módulo");
        }

        $this->id_op=$id;
        $this->id_submod=$id_submod;
        $this->slug=$slug;

}

public function renderContent(){
        $model = new MainModel();

        // =================================================================
        // 1. VALIDACIÓN DE SEGURIDAD DINÁMICA (ANTI-TAMPERING)
        // =================================================================
        // Concatenamos el slug con "/" para que coincida con la BD (ej. "personal/")
        $url_buscar = $this->slug; 
        
        // Consultamos en BD cuál es el ID real asociado a esta URL
        $consultaSub = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        // Si el submódulo no existe o el usuario modificó el ID en la URL, bloqueamos el acceso
        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido. La acción ha sido bloqueada para proteger la integridad del sistema.</p>
                  </div>";
            return; // Detenemos la ejecución, no cargamos la vista
        }

        // =================================================================
        // 2. OBTENER DATOS Y CATÁLOGOS (SI PASÓ LA VALIDACIÓN)
        // =================================================================
        $datos = $this->traerContenido($model);
        
        // Extraemos para usar en la vista ($departamentos, $personal, $dispositivos, $id_sub_actual, etc.)
        extract($datos);

        include self::VISTA;
    }


    private function traerContenido($model) {
        // Catálogos necesarios para los <select> del formulario
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"]);
        
        $personal = null;
        $equipos_asignados = [];
        $equipos_responsables = [];

        // Si recibimos un ID de empleado, es MODO EDICIÓN
        if (!empty($this->id_op)) {
            $resultado = $model->seleccionaRegistros("Personal_Operativo", ["*"], "id_po = ?", [$this->id_op]);
            
            if (!empty($resultado)) {
                $personal = $resultado[0];
                
                // 1. Equipos que usa (Asignados)
                $equipos_asignados = $model->seleccionaRegistros("Dispositivos", ["*"], "asig_po_id = ?", [$this->id_op]);
                
                // 2. Equipos que firmó (Resguardos/Responsivas)
                $equipos_responsables = $model->seleccionaRegistros("Dispositivos", ["*"], "resp_po_id = ?", [$this->id_op]);
            } else {
                die("<div class='alert alert-warning m-4 fw-bold'>El empleado no existe.</div>");
            }
        }

        return [
            'departamentos' => $departamentos,
            'personal'      => $personal,
            'equipos_asignados'    => $equipos_asignados,     // <--- Arreglo 1
            'equipos_responsables' => $equipos_responsables,  // <--- Arreglo 2
            'id_sub_actual' => $this->id_submod,
            'id_po'         => $this->id_op
        ];

    }

public function renderJS(){
    include self::JS;
}







}
?>