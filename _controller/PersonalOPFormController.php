<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

require_once 'MainModel.php';

/**
 * Controlador para la interfaz del formulario de Personal Operativo (Alta y Edición).
 * Gestiona la validación de seguridad de la URL (Anti-Tampering) y la recolección 
 * de datos del empleado, departamentos y dispositivos asociados (asignados y resguardos).
 */
class PersonalOPFormController{

/** @var string Ruta al archivo de la vista HTML del formulario */
const VISTA = "_view/personalOperativoForm.html.php";

/** @var string Ruta al archivo JavaScript asociado al formulario */
const JS = "js/personalOperativoForm.js";

/** @var int|null ID del personal operativo (Null para nuevo registro) */
private $id_op;//id del personal operativo

/** @var int|null ID numérico del submódulo extraído de la URL */
private $id_submod;

/** @var string|null Slug identificador del submódulo en curso */
private $slug;

/**
 * Constructor del controlador del formulario.
 * Verifica la existencia de una sesión activa e inicializa el contexto de la petición.
 *
 * @param string|null $slug Nombre corto del módulo para construcción de la ruta.
 * @param int|null $id_submod ID del submódulo proveniente de la URL.
 * @param int|null $id ID del registro del personal operativo (modo edición).
 */
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

/**
 * Construye y renderiza el contenido del formulario.
 * Ejecuta validación de seguridad para prevenir la alteración manual del ID de submódulo
 * en la barra de direcciones (Anti-Tampering).
 *
 * @return void Renderiza el HTML directamente o detiene la ejecución si hay anomalías.
 */
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


   /**
    * Extrae la información necesaria para construir el formulario.
    * Obtiene el catálogo de departamentos y, en caso de edición, los datos
    * del empleado junto con los equipos tecnológicos bajo su uso o resguardo.
    *
    * @param MainModel $model Instancia activa del modelo de datos.
    * @return array Matriz de datos listos para ser inyectados en la vista.
    */
   private function traerContenido($model) {
        // Catálogos necesarios para los <select> del formulario
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"]);
        $consultaDisp = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", ["dispositivos"]);
        // Si lo encuentra, guarda el ID, de lo contrario guarda 8 como medida de seguridad temporal
        $id_sub_dispositivos = (!empty($consultaDisp)) ? $consultaDisp[0]['id_sub'] : 8;

        $personal = null;
        $equipos_asignados = [];
        $equipos_responsables = [];

        // Si recibimos un ID de empleado, es MODO EDICIÓN
        if (!empty($this->id_op)) {
            $resultado = $model->seleccionaRegistros("Personal_Operativo", ["*"], "id_po = ?", [$this->id_op]);
            
            if (!empty($resultado)) {
                $personal = $resultado[0];
                
                // =================================================================
                // OBTENER DISPOSITIVOS + RED (Igual que en DispositivosController)
                // =================================================================
                $tablaDisp = "dispositivos d";
                $joinsDisp = [
                    "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp"
                ];
                $camposDisp = [
                    "d.*",                                             // Trae todo
                    "COALESCE(d.mac_ethernet, d.mac_wifi) as dir_mac", // Compatibilidad temporal con vista actual
                    "d.mac_ethernet",                                  // Extracción directa
                    "d.mac_wifi",                                      // Extracción directa
                    "r.ip as dir_ip"                                   // Asignamos alias a la IP de la tabla red
                ];
                
                // 1. Equipos que usa (Asignados)
                $equipos_asignados = $model->seleccionaRegistrosJoin($tablaDisp, $joinsDisp, $camposDisp, "d.asig_po_id = ?", [$this->id_op]);
                
                // 2. Equipos que firmó (Resguardos/Responsivas)
                $equipos_responsables = $model->seleccionaRegistrosJoin($tablaDisp, $joinsDisp, $camposDisp, "d.resp_po_id = ?", [$this->id_op]);
                
            } else {
                die("<div class='alert alert-warning m-4 fw-bold'>El empleado no existe.</div>");
            }
        }

        return [
            'departamentos'        => $departamentos,
            'personal'             => $personal,
            'equipos_asignados'    => $equipos_asignados,     // <--- Arreglo 1 (con IP y MAC)
            'equipos_responsables' => $equipos_responsables,  // <--- Arreglo 2 (con IP y MAC)
            'id_sub_actual'        => $this->id_submod,
            'id_sub_dispositivos'  => $id_sub_dispositivos,
            'id_po'                => $this->id_op
        ];
    }

/**
 * Inserta el archivo JavaScript necesario para la funcionalidad del formulario.
 *
 * @return void
 */
public function renderJS(){
    include self::JS;
}







}
?>