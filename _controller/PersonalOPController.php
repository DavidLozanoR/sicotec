<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador principal para la gestión del Personal Operativo.
 * Coordina la visualización del listado de personal y la validación
 * de privilegios de acceso al módulo mediante RBAC.
 */
class PersonalOPController{

    /** @var string Ruta constante al archivo de la vista HTML */
    const VISTA = "_view/personalOperativo.html.php";
    
    /** @var string Ruta constante al script JavaScript de la vista */
    const JS = "js/personalOperativo.js";

    /** @var string|null Slug identificador del submódulo en curso */
    private $slug;

    /**
     * Constructor del controlador.
     * Garantiza la inicialización de la sesión y bloquea el acceso si no hay usuario autenticado.
     *
     * @param string|null $slug Nombre corto del módulo para construcción de rutas.
     */
    function __construct($slug=null){ 
        // Validamos que exista una sesión activa
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(!isset($_SESSION['usuario'])){
            die("Error: Su sesión ha expirado o no tiene acceso a este módulo");
        }
        $this->slug=$slug;
    }

    /**
     * Prepara y renderiza el contenido central del módulo.
     * Evalúa permisos de consulta y empaqueta la información del personal y catálogos.
     *
     * @return void Renderiza el HTML directamente o muestra mensaje de bloqueo.
     */
    public function renderContent(){
        $model = new MainModel();

        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 1; 

        // Verificación de permiso base para poder ver la pantalla
        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder al Personal Operativo.</p>
                    </div>
                  </div>';
            return; 
        }
        
        $datos = $this->traerContenido($model); // trae el contenido a renderizar
        // extraemos los datos para consumirlos en la vista
        extract($datos);

        // 4. Incluimos la vista
        include self::VISTA;
    }

    /**
     * Inyecta en el DOM el archivo JS asociado al controlador.
     *
     * @return void
     */
    public function renderJS(){
        // Incluimos el JavaScript específico para esta vista
        // (Donde programaremos los filtros en vivo y el modal)
        include self::JS;
    }

    /**
     * Extrae de la base de datos la información necesaria para poblar la vista.
     * Utiliza vistas de BD optimizadas para evitar consultas N+1 en listados complejos.
     *
     * @param MainModel $model Instancia activa del modelo.
     * @return array Arreglo asociativo con catálogos (departamentos) y datos crudos (personal).
     */
    private function traerContenido($model){
        // Buscamos en la tabla Submodulos aquel que coincida con la ruta de este módulo
        $consultaSub = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", ["$this->slug"]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;
        
        // 1. Obtener catálogos para los filtros (Selects)
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"]);

        // 2. Consulta optimizada usando la vista (elimina el problema N+1 y el bucle)
        $lista_personal = $model->seleccionaRegistros(
            "v_personal_operativo_completo", 
            ["*"], 
            "estatus = ?", 
            [1]
        );

        if (!$lista_personal) {
            $lista_personal = [];
        }

        return [
            'departamentos' => $departamentos,
            'lista_personal' => $lista_personal
        ];
    }

    // ==========================================================
    // LÓGICA DE SEGURIDAD (RBAC)
    // ==========================================================
    
    /**
     * Comprueba si el rol del usuario posee un permiso en específico.
     *
     * @param int $id_sub ID del submódulo en el que se intenta la acción.
     * @param int $id_permiso ID numérico del permiso.
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
    
    // ==========================================================
    // VALIDACIÓN DE ATRIBUTOS
    // ==========================================================
public function validarDatosPO($num_cobro, $nombres, $ape_pa, $ape_ma, $correo_prefijo, $ext, $puesto, $id_dep) {        
   
        if (empty($num_cobro) || empty($nombres) || empty($ape_pa) || empty($puesto) || empty($id_dep) || empty($correo_prefijo)) {
            return "Faltan campos obligatorios. Asegúrese de llenar No. Cobro, Nombres, Apellido Paterno, Puesto, Departamento y Correo.";
        }    

        if (!is_numeric($num_cobro)) {
            return "El Número de Cobro debe ser un valor numérico.";
        }
        //if (strlen($nombres) < 3 || strlen($ape_ma) < 3 || strlen($ape_pa) < 3 ) return ["result" => 0, "msg" => "El nombre o apellidos deben de tener al menos 3 letras."]; 
        
        // REGEX: Empieza con letra, permite espacios entre palabras, termina con letra.
        $regex_nombres = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+( [a-zA-ZáéíóúÁÉÍÓÚñÑ]+)*$/';

        if (strlen($nombres) > 100 || !preg_match($regex_nombres, $nombres)) {
            return "El nombre es inválido: solo debe contener letras y sin espacios dobles.";
        }
        if (!empty($ape_pa) && (strlen($ape_pa) > 100 || !preg_match($regex_nombres, $ape_pa))) {
            return "El apellido paterno es inválido: solo letras y sin espacios dobles.";
        }
        if (!empty($ape_ma) && (strlen($ape_ma) > 100 || !preg_match($regex_nombres, $ape_ma))) {
            return "El apellido materno es inválido.";
        }
        if (!empty($correo_prefijo) && !preg_match('/^[a-zA-Z0-9._-]+$/', $correo_prefijo)) {
            return "El prefijo del correo es inválido. No use espacios ni caracteres especiales ajenos al estándar.";
        }
        if (!empty($ext) && !is_numeric($ext)) {
            return "La extensión debe ser un número válido.";
        }

        return true;
    }

    // ==========================================================
    // LÓGICA EXCLUSIVA DE PERSONAL OPERATIVO (CRUD)
    // ==========================================================
    public function insertaPO($id_sub, $num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep) {
        $model = new MainModel();
        
        // Evitar duplicados de número de cobro o correo
        $existe = $model->seleccionaRegistros("personal_operativo", ["id_po"], "num_cobro = ? OR (correo = ? AND correo IS NOT NULL)", [$num_cobro, $correo]);
        if ($existe && count($existe) > 0) {
            return ["result" => 0, "msg" => "El Número de Cobro o el Correo Institucional ya existen en el sistema."];
        }
        // evita que se registren como baja
        if ($estatus == 0) {return ["result" => 0, "msg" => "No se puede registrar a un usuario como baja."];}  

        //registra un limite a los nombres
        if (strlen($nombres) < 3 || strlen($ape_ma) < 3 || strlen($ape_pa) < 3 ) {return ["result" => 0, "msg" => "El nombre o apellidos deben de tener al menos 3 letras."];  }

        $id_u_int = $_SESSION['usuario']['id_usuar'];
        $fec_u_int = date('Y-m-d H:i:s');

        // agragamos a bitacora y retornamos id
        $id_generado = $model->agregaRegistroConBitacora(
            "personal_operativo", 
            ["num_cobro", "nombres", "ape_pa", "ape_ma", "dir", "correo", "puesto", "ext", "cta_dom", "estatus", "id_dep", "id_u_int", "fec_u_int"], 
            [$num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep, $id_u_int, $fec_u_int],
            $id_sub,
            "Dio de alta al empleado: {$nombres} {$ape_pa} (Cobro: {$num_cobro})"
        );
        
        if ($id_generado) {
            // RETORNAMOS EL ID_GENERADO EN EL JSON
            return [
                "result" => 1, 
                "msg" => "Personal guardado con éxito. Ahora puede asignarle equipos.",
                "id_nuevo" => $id_generado 
            ];
        }
        return ["result" => 0, "msg" => "Error al guardar el registro en la base de datos."];
    }

    public function actualizaPO($id_sub, $id_po, $num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep) {
        $model = new MainModel();
        
        // Evitar que le asigne un número de cobro de otro usuario
        $existe = $model->seleccionaRegistros("personal_operativo", ["id_po"], "(num_cobro = ? OR (correo = ? AND correo IS NOT NULL)) AND id_po != ?", [$num_cobro, $correo, $id_po]);
        if ($existe && count($existe) > 0) {
            return ["result" => 0, "msg" => "El Número de Cobro o el Correo ya están asignados a otro empleado."];
        }

        //registra un limite a los nombres
        if (strlen($nombres) < 3 || strlen($ape_ma) < 3 || strlen($ape_pa) < 3 ) return ["result" => 0, "msg" => "El nombre o apellidos deben de tener al menos 3 letras."];  

        $id_u_int = $_SESSION['usuario']['id_usuar'];
        $fec_u_int = date('Y-m-d H:i:s');

        // Usa la firma exacta de tu actualizaRegistro (Notar cómo los params mezclan los SET y el WHERE)
        $res = $model->actualizaRegistro(
            "personal_operativo", 
            ["num_cobro", "nombres", "ape_pa", "ape_ma", "dir", "correo", "puesto", "ext", "cta_dom", "estatus", "id_dep", "id_u_int", "fec_u_int"], 
            "id_po=?", 
            [$num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep, $id_u_int, $fec_u_int, $id_po]
        );

        if ($res) {
            $bitacora = $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó datos del empleado ID: {$id_po} (Cobro: {$num_cobro})");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";
            
            return ["result" => 1, "msg" => "Personal actualizado con éxito." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al actualizar el registro."];
    }

    public function bajaPO($id_sub, $id_po) {
        $model = new MainModel();
        
        // Validación extra: No dar de baja si tiene equipos asignados (Regla de negocio)
        $equipos = $model->seleccionaRegistros("dispositivos", ["id_disp"], "asig_po_id = ? OR resp_po_id = ?", [$id_po, $id_po]);
        if ($equipos && count($equipos) > 0) {
            return ["result" => 0, "msg" => "No se puede dar de baja. El empleado aún tiene dispositivos asignados o bajo su resguardo."];
        }

        $id_u_int = $_SESSION['usuario']['id_usuar'];
        $fec_u_int = date('Y-m-d H:i:s');

        // Baja lógica (estatus = 0)
        $res = $model->actualizaRegistro(
            "personal_operativo", 
            ["estatus", "id_u_int", "fec_u_int"], 
            "id_po=?", 
            [0, $id_u_int, $fec_u_int, $id_po]
        );
        
        if ($res) {
            $bitacora = $model->registrarBitacora($id_sub, 'DELETE', "Realizó baja lógica del empleado ID: {$id_po}");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";
            return ["result" => 1, "msg" => "Empleado dado de baja exitosamente." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al dar de baja al empleado."];
    }
    // ==========================================================
    // MÉTODO PARA DESVINCULAR EQUIPOS DEL EMPLEADO
    // ==========================================================
    public function desvincularEquipo($id_sub, $id_po, $id_disp, $tipo_vinculo) {
        $model = new MainModel();
        
        // Determinamos qué columna de la tabla "dispositivos" vamos a poner en NULL
        $campo_a_limpiar = ($tipo_vinculo === 'quitar_uso') ? 'asig_po_id' : 'resp_po_id';
        $texto_bitacora = ($tipo_vinculo === 'quitar_uso') ? 'quitó el uso diario' : 'liberó el resguardo legal';

        // Actualizamos la tabla dispositivos poniendo en NULL el campo correspondiente
        $res = $model->actualizaRegistro(
            "dispositivos", 
            [$campo_a_limpiar], 
            "id_disp = ?", 
            [null, $id_disp] // Importante: pasamos null literal
        );

        if ($res) {
            // Dejamos registro en la auditoría
            $model->registrarBitacora($id_sub, 'UPDATE', "Se {$texto_bitacora} del equipo ID: {$id_disp} asociado al empleado ID: {$id_po}");
            
            return ["result" => 1, "msg" => "El equipo ha sido desvinculado correctamente."];
        }
        
        return ["result" => 0, "msg" => "No se pudo desvincular el equipo de la base de datos."];
    }
    


}





?>