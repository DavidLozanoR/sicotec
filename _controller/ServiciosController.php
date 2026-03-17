<?php
require_once "MainModel.php";

class ServiciosController {

    const VISTA = "_view/servicios.html.php";
    const JS = "js/servicios.js";

    private $slug;
    
    // Permisos
    const PERMISO_EDITAR = 1;
    const PERMISO_AGREGAR = 2;
    const PERMISO_ELIMINAR = 3;
    const PERMISO_CONSULTAR = 4;

    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        $id_sub = $_SESSION['submodulo_actual'] ?? 2; 

        // Candado de Vista
        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para consultar el Catálogo de Servicios.</p>
                    </div>
                  </div>';
            return; 
        }

        $datos = $this->traerContenido($model);
        extract($datos);
        include self::VISTA;
    }

    public function renderJS() {
        include self::JS;
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

    private function traerContenido($model) {
        $tabla = "servicios s";
        
        // DOBLE CANDADO + OBS: Solo cruzamos con la versión que le pertenece (id_serv) y que no es obsoleta (obs = 0)
        $joins = ["LEFT JOIN versiones v ON s.id_serv = v.id_serv AND v.obs = 0"];
        $campos = ["s.*", "v.ver AS version_actual"];
        
        $servicios = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, "s.estatus = 1", []);

        $lista_servicios = [];

        if ($servicios) {
            foreach ($servicios as $serv) {
                // Instalaciones actuales
                $uso = $model->seleccionaRegistros("dispositivo_servicio", ["id_disp"], "id_serv = ?", [$serv['id_serv']]);
                $serv['total_instalaciones'] = $uso ? count($uso) : 0;
                
                // Total de versiones históricas
                $versiones = $model->seleccionaRegistros("versiones", ["id_ver"], "id_serv = ?", [$serv['id_serv']]);
                $serv['total_versiones'] = $versiones ? count($versiones) : 0;

                // Control de N/A si la versión no existe
                $serv['version_actual'] = !empty($serv['version_actual']) ? $serv['version_actual'] : 'N/A';

                $lista_servicios[] = $serv;
            }
        }
        return ['servicios' => $lista_servicios];
    }

// ==========================================================
    // LÓGICA DE SERVICIOS (CRUD)
    // ==========================================================
    public function insertarServicio($id_sub, $tipo_serv, $nom, $especs, $version_inicial, $icono) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para agregar registros."];
        }

        $fecha_actual = date('Y-m-d H:i:s');
        
        // Si viene vacío, asignamos el cubo por defecto
        $icono = empty($icono) ? 'fas fa-cube' : $icono;

        // 1. Insertamos el Servicio Maestro
        $id_serv_nuevo = $model->agregaRegistroConBitacora(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act", "estatus", "icono"], 
            [$tipo_serv, $nom, $especs, $fecha_actual, 1, $icono],
            $id_sub, "Registró el catálogo de servicio: {$nom}"
        );

        if ($id_serv_nuevo) {
            // 2. Insertamos la versión inicial apuntando al servicio y con obs = 0 (Activa)
            $model->agregaRegistro(
                "versiones", 
                ["ver", "id_serv", "obs"], 
                [$version_inicial, $id_serv_nuevo, 0]
            );

            return ["result" => 1, "msg" => "Servicio y versión inicial registrados correctamente."];
        }
        return ["result" => 0, "msg" => "Error al guardar el servicio en la base de datos."];
    }

public function actualizarServicio($id_sub, $id_serv, $tipo_serv, $nom, $especs, $icono) {
        $model = new MainModel();

        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }
        
        $icono = empty($icono) ? 'fas fa-cube' : $icono;

        // Usamos la función unificada con bitácora
        $res = $model->actualizaRegistroConBitacora(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act", "icono"], 
            "id_serv = ?", 
            [$tipo_serv, $nom, $especs, date('Y-m-d H:i:s'), $icono, $id_serv],
            $id_sub,
            "Actualizó detalles del servicio ID: {$id_serv} | {$nom}"
        );

        if ($res) {
            return ["result" => 1, "msg" => "Datos del servicio actualizados exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar actualizar o no hubo cambios detectados."];
    }

    public function eliminarServicio($id_sub, $id_serv) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_ELIMINAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        $uso = $model->seleccionaRegistros("dispositivo_servicio", ["id_disp"], "id_serv = ?", [$id_serv]);
        if ($uso && count($uso) > 0) {
            return ["result" => 0, "msg" => "Operación bloqueada. Existen " . count($uso) . " equipos usando este software."];
        }

        // Borrado lógico del catálogo usando la función unificada
        $res = $model->actualizaRegistroConBitacora(
            "servicios", 
            ["estatus"], 
            "id_serv = ?", 
            [0, $id_serv],
            $id_sub,
            "Desactivó (borrado lógico) el servicio ID: {$id_serv}"
        );

        if ($res) {
            return ["result" => 1, "msg" => "Servicio desactivado correctamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar desactivar el servicio."];
    }


    // ==========================================================
    // HISTORIAL DE VERSIONES
    // ==========================================================
    public function obtenerVersiones($id_sub, $id_serv) {
        $model = new MainModel();

        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        $tabla = "versiones v";
        $campos = [
            "v.id_ver", 
            "v.ver", 
            "v.obs", 
            "IF(v.obs = 0, 1, 0) AS es_actual" // Transformamos obs a un booleano para el Frontend
        ];
        $condicion = "v.id_serv = ? ORDER BY v.id_ver DESC";
        
        // Ejecución limpia a través de tu MainModel
        $versiones = $model->seleccionaRegistrosJoin($tabla, [], $campos, $condicion, [$id_serv]);
        
        return ["result" => 1, "data" => $versiones ? $versiones : []];
    }

    public function agregarVersion($id_sub, $id_serv, $nueva_version, $marcar_actual) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para agregar versiones."];
        }

        // Si la marcarán como actual (1), entra con obs = 0. Si no, entra con obs = 1.
        $estado_obs = ($marcar_actual == '1') ? 0 : 1;

        // Si será la actual, apagamos las demás primero
        if ($marcar_actual == '1') {
            $model->actualizaRegistro("versiones", ["obs"], "id_serv = ?", [1, $id_serv]);
        }

        $id_ver_nueva = $model->agregaRegistro(
            "versiones", ["ver", "id_serv", "obs"], [$nueva_version, $id_serv, $estado_obs]
        );

        if ($id_ver_nueva) {
            $model->registrarBitacora($id_sub, 'INSERT', "Agregó la versión '{$nueva_version}' al servicio ID: {$id_serv}");
            
            if ($marcar_actual == '1') {
                $model->actualizaRegistro("servicios", ["u_act"], "id_serv = ?", [date('Y-m-d H:i:s'), $id_serv]);
            }
            
            return ["result" => 1, "msg" => "Versión agregada al historial exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al guardar la nueva versión."];
    }

    public function marcarVersionActual($id_sub, $id_serv, $id_ver) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        // 1. Apagamos todas las versiones (obs = 1)
        $model->actualizaRegistro("versiones", ["obs"], "id_serv = ?", [1, $id_serv]);

        // 2. Encendemos únicamente la seleccionada (obs = 0)
        $res = $model->actualizaRegistro("versiones", ["obs"], "id_ver = ?", [0, $id_ver]);

        if ($res) {
            $model->actualizaRegistro("servicios", ["u_act"], "id_serv = ?", [date('Y-m-d H:i:s'), $id_serv]);
            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó la versión desplegada del servicio ID: {$id_serv}");
            return ["result" => 1, "msg" => "La versión seleccionada ahora es la actual."];
        }
        return ["result" => 0, "msg" => "Error al actualizar la versión."];
    }
}
?>