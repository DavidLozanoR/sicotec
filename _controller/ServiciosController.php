<?php
require_once "MainModel.php";

class ServiciosController {

    const VISTA = "_view/servicios.html.php";
    const JS = "js/servicios.js";

    private $slug;
    
    // Mapeo estándar de permisos (Ajusta los números si en tu BD son diferentes)
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
        
        // Obtenemos el ID del submódulo actual (Asumiendo que lo guardas en sesión al navegar)
        $id_sub = $_SESSION['submodulo_actual'] ?? 2; 

        // VALIDACIÓN DE PERMISO DE CONSULTA ANTES DE RENDERIZAR LA VISTA
        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para consultar el Catálogo de Servicios.</p>
                    </div>
                  </div>';
            return; // Detenemos la ejecución aquí, la vista no se carga
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
        // Traemos solo los servicios ACTIVOS (estatus = 1) junto con su versión desplegada
        $tabla = "servicios s";
        $joins = ["LEFT JOIN versiones v ON s.id_ver = v.id_ver"];
        $campos = ["s.*", "v.ver AS version_actual"];
        
        // Filtramos por borrado lógico
        $servicios = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, "s.estatus = 1", []);

        $lista_servicios = [];

        if ($servicios) {
            foreach ($servicios as $serv) {
                // Contamos cuántos dispositivos lo tienen asignado actualmente
                $uso = $model->seleccionaRegistros("dispositivo_servicio", ["id_disp"], "id_serv = ?", [$serv['id_serv']]);
                $serv['total_instalaciones'] = $uso ? count($uso) : 0;
                
                // Contamos su historial de versiones
                $versiones = $model->seleccionaRegistros("versiones", ["id_ver"], "id_serv = ?", [$serv['id_serv']]);
                $serv['total_versiones'] = $versiones ? count($versiones) : 0;

                $lista_servicios[] = $serv;
            }
        }
        return ['servicios' => $lista_servicios];
    }

    // ==========================================================
    // LÓGICA DE SERVICIOS (CRUD)
    // ==========================================================
    public function insertarServicio($id_sub, $tipo_serv, $nom, $especs, $version_inicial) {
        $model = new MainModel();
        
        // 1. Validamos Permiso de Agregar
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para agregar registros."];
        }

        $fecha_actual = date('Y-m-d H:i:s');

        // Insertamos el Servicio como Activo (estatus = 1) y con id_ver en NULL temporalmente
        $id_serv_nuevo = $model->agregaRegistroConBitacora(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act", "estatus"], 
            [$tipo_serv, $nom, $especs, $fecha_actual, 1],
            $id_sub, "Registró el catálogo de servicio: {$nom}"
        );

        if ($id_serv_nuevo) {
            // Insertamos la versión inicial en la tabla versiones
            $id_ver_nueva = $model->agregaRegistro(
                "versiones", ["ver", "id_serv", "obs"], [$version_inicial, $id_serv_nuevo, 0]
            );

            // Cerramos el ciclo actualizando el servicio para que apunte a esta versión
            if ($id_ver_nueva) {
                $model->actualizaRegistro("servicios", ["id_ver"], "id_serv = ?", [$id_ver_nueva, $id_serv_nuevo]);
            }
            return ["result" => 1, "msg" => "Servicio y versión inicial registrados correctamente."];
        }
        return ["result" => 0, "msg" => "Error al guardar el servicio en la base de datos."];
    }

    public function actualizarServicio($id_sub, $id_serv, $tipo_serv, $nom, $especs) {
        $model = new MainModel();

        // 1. Validamos Permiso de Edición
        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para modificar registros."];
        }

        $res = $model->actualizaRegistro(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act"], 
            "id_serv = ?", 
            [$tipo_serv, $nom, $especs, date('Y-m-d H:i:s'), $id_serv]
        );

        if ($res) {
            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó detalles del servicio ID: {$id_serv}");
            return ["result" => 1, "msg" => "Datos del servicio actualizados exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar actualizar el servicio."];
    }

    public function eliminarServicio($id_sub, $id_serv) {
        $model = new MainModel();
        
        // 1. Validamos Permiso de Eliminar
        if (!$this->verificarPermiso($id_sub, self::PERMISO_ELIMINAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para desactivar registros."];
        }

        // 2. Candado de Integridad: Revisamos si hay equipos usándolo
        $uso = $model->seleccionaRegistros("dispositivo_servicio", ["id_disp"], "id_serv = ?", [$id_serv]);
        if ($uso && count($uso) > 0) {
            $cantidad = count($uso);
            return ["result" => 0, "msg" => "Operación bloqueada. Existen {$cantidad} equipos que tienen asignado este software. Desvincúlelos primero."];
        }

        // 3. Procedemos al Borrado Lógico (Ocultarlo)
        $res = $model->actualizaRegistro("servicios", ["estatus"], "id_serv = ?", [0, $id_serv]);

        if ($res) {
            $model->registrarBitacora($id_sub, 'DELETE', "Desactivó (borrado lógico) el servicio ID: {$id_serv}");
            return ["result" => 1, "msg" => "Servicio desactivado correctamente. Se mantendrá el historial."];
        }
        return ["result" => 0, "msg" => "Error al intentar desactivar el servicio."];
    }

  // ==========================================================
    // HISTORIAL DE VERSIONES
    // ==========================================================
    public function obtenerVersiones($id_sub, $id_serv) {
        $model = new MainModel();

        // Aunque es una consulta, verificamos permiso por seguridad
        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para consultar historial."];
        }

        // Armamos la consulta usando los métodos nativos de tu modelo
        $tabla = "versiones v";
        $joins = ["LEFT JOIN servicios s ON v.id_serv = s.id_serv"];
        $campos = [
            "v.id_ver", 
            "v.ver", 
            "v.obs", 
            "IF(s.id_ver = v.id_ver, 1, 0) AS es_actual"
        ];
        // Añadimos el ORDER BY directamente en la condición
        $condicion = "v.id_serv = ? ORDER BY v.id_ver DESC";
        $params = [$id_serv];

        $versiones = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, $condicion, $params);
        
        // Retornamos los datos o un arreglo vacío si no hay registros
        return ["result" => 1, "data" => $versiones ? $versiones : []];
    }

    public function agregarVersion($id_sub, $id_serv, $nueva_version, $marcar_actual) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para agregar versiones."];
        }

        $id_ver_nueva = $model->agregaRegistro(
            "versiones", ["ver", "id_serv", "obs"], [$nueva_version, $id_serv, 0]
        );

        if ($id_ver_nueva) {
            $model->registrarBitacora($id_sub, 'INSERT', "Agregó la versión '{$nueva_version}' al servicio ID: {$id_serv}");
            
            if ($marcar_actual == '1') {
                $this->marcarVersionActual($id_sub, $id_serv, $id_ver_nueva);
            }
            return ["result" => 1, "msg" => "Versión agregada al historial."];
        }
        return ["result" => 0, "msg" => "Error al guardar la nueva versión."];
    }

    public function marcarVersionActual($id_sub, $id_serv, $id_ver) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para modificar la versión actual."];
        }

        $res = $model->actualizaRegistro(
            "servicios", ["id_ver", "u_act"], "id_serv = ?", [$id_ver, date('Y-m-d H:i:s'), $id_serv]
        );

        if ($res) {
            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó la versión desplegada del servicio ID: {$id_serv}");
            return ["result" => 1, "msg" => "Versión establecida como la actual exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al actualizar el registro."];
    }
}
?>