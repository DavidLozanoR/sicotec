<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador principal para el módulo de Responsivas.
 * Gestiona la asignación, consulta y transferencia de equipos (dispositivos)
 * bajo el resguardo del personal operativo del sistema.
 */
class ResponsivaController {

    /** @var string Ruta al archivo de la vista HTML de responsivas */
    const VISTA = "_view/responsiva.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado a la vista */
    const JS = "js/responsiva.js";

    /** @var string|null Slug identificador del submódulo en curso */
    private $slug;
    
    /** @var int Identificador del permiso de edición en la BD */
    const PERMISO_EDITAR = 1; 
    
    /** @var int Identificador del permiso de consulta en la BD */
    const PERMISO_CONSULTAR = 4;

    /**
     * Constructor del controlador.
     * Valida que exista una sesión activa; de lo contrario, detiene la ejecución.
     *
     * @param string|null $slug Nombre corto del módulo para la navegación.
     */
    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    /**
     * Prepara y renderiza el contenido central del módulo de Responsivas.
     * Realiza validaciones de seguridad mediante RBAC antes de extraer
     * la lista de personal y empaquetarla para la vista.
     *
     * @return void Renderiza HTML directamente o muestra alerta de acceso denegado.
     */
    public function renderContent() {
        $model = new MainModel();
        
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 4; 

        // Primera verificación de permisos (Protección base de vista)
        if (!$this->verificarPermiso($id_sub,PERM_CONSULTAR )) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a la Responsiva.</p>
                    </div>
                  </div>';
            return; 
        }
        
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 4; 

        // Segunda verificación (Redundancia de seguridad para carga de responsivas)
        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a las Responsivas.</p>
                    </div>
                  </div>';
            return; 
        }

        $datos = $this->traerContenido($model);
        $datos['id_sub_actual'] = $id_sub;
        extract($datos);
        
        include self::VISTA;
    }

    /**
     * Inyecta el archivo JavaScript asociado a la vista en el DOM.
     *
     * @return void
     */
    public function renderJS() {
        if (file_exists(self::JS)) {
            include self::JS;
        }
    }

    /**
     * Comprueba si el rol del usuario posee un permiso en específico.
     *
     * @param int $id_sub ID del submódulo en el que se intenta la acción.
     * @param int $id_permiso ID numérico del permiso requerido.
     * @return bool True si el permiso es concedido, false de lo contrario.
     */
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

    // ==========================================
    // CARGA INICIAL (LISTA DE PERSONAL)
    // ==========================================
    
    /**
     * Extrae el catálogo optimizado de personal operativo y su conteo de equipos.
     * Utiliza una vista SQL para mitigar problemas de rendimiento (N+1 queries).
     *
     * @param MainModel $model Instancia activa del modelo.
     * @return array Matriz asociativa con la lista del personal.
     */
    private function traerContenido($model) {
        
        // Consumo directo de la vista optimizada. Eliminamos el foreach y las consultas anidadas.
        $lista_personal = $model->seleccionaRegistros(
            "v_responsivas_personal", 
            ["id_po", "nombre_completo", "num_cobro", "total_equipos"], 
            "estatus = 1 ORDER BY nombre_completo ASC"
        );

        if (!$lista_personal) {
            $lista_personal = [];
        }

        return ['personal' => $lista_personal];
    }
    
    // ==========================================
    // MÉTODOS PARA ASYNC (AJAX)
    // ==========================================
    
    /**
     * Obtiene el listado de dispositivos bajo la responsiva de un empleado específico.
     * Diseñado para ser consumido vía peticiones AJAX.
     *
     * @param int $id_sub Contexto de seguridad del submódulo.
     * @param int $id_po Identificador del Personal Operativo.
     * @return array Respuesta estructurada con resultado y colección de equipos.
     */
    public function obtenerEquipos($id_sub, $id_po) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        $campos = ["id_disp", "nom_host", "marca", "modelo", "num_ser", "num_inv"];
        
        // CERO JOINS: Evita el Crash 500 que causaba el "Error de Red"
        $equipos = $model->seleccionaRegistros("dispositivos", $campos, "resp_po_id = ?", [$id_po]);
        
        return ["result" => 1, "data" => $equipos ? $equipos : []];
    }
}
?>

    public function transferirEquipo($id_sub, $id_disp, $id_po_origen, $id_po_destino) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "No tiene permisos para transferir responsivas."];
        }

        if ($id_po_origen === $id_po_destino) {
            return ["result" => 0, "msg" => "El usuario origen y destino no pueden ser el mismo."];
        }

        $disp = $model->seleccionaRegistros("dispositivos", ["id_disp", "nom_host"], "id_disp = ? AND resp_po_id = ?", [$id_disp, $id_po_origen]);
        
        if (!$disp) {
            return ["result" => 0, "msg" => "Inconsistencia de datos: El equipo seleccionado ya no pertenece a este usuario."];
        }

        $id_usuario_sesion = $_SESSION['usuario']['id_usuar'];
        $fecha_actual = date('Y-m-d H:i:s');

        $res = $model->actualizaRegistro(
            "dispositivos", 
            ["resp_po_id", "asig_po_id", "id_u_int", "fec_u_int"], 
            "id_disp = ?", 
            [$id_po_destino, $id_po_destino, $id_usuario_sesion, $fecha_actual, $id_disp]
        );
        
        if ($res !== false) {
            $model->registrarBitacora($id_sub, 'UPDATE', "Transfirió responsiva y uso del equipo ID: {$id_disp} (PO Origen: {$id_po_origen} -> PO Destino: {$id_po_destino})");
            return ["result" => 1, "msg" => "La responsiva ha sido transferida exitosamente."];
        }
        
        return ["result" => 0, "msg" => "Ocurrió un error en la base de datos al intentar transferir."];
    }
}
?>