<?php
require_once "MainModel.php";

class ServiciosController {

    const VISTA = "_view/servicios.html.php";
    const JS = "js/servicios.js";

    private $slug;
    
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
        if (file_exists(self::JS)) { include self::JS; }
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
        // Consulta plana sin JOINs para mayor estabilidad
        $servicios = $model->seleccionaRegistros("servicios", ["*"], "estatus = 1 ORDER BY nom ASC");
        $lista_servicios = [];

        if ($servicios) {
            foreach ($servicios as $serv) {
                // Instalaciones actuales
                $uso = $model->seleccionaRegistros("dispositivo_servicio", ["id_disp"], "id_serv = ?", [$serv['id_serv']]);
                $serv['total_instalaciones'] = $uso ? count($uso) : 0;
                
                // Procesar versiones (Históricas y Aceptadas)
                $versiones = $model->seleccionaRegistros("versiones", ["id_ver", "ver", "obs"], "id_serv = ? ORDER BY id_ver DESC", [$serv['id_serv']]);
                $serv['total_versiones'] = $versiones ? count($versiones) : 0;

                $aceptadas = [];
                if ($versiones) {
                    foreach ($versiones as $v) {
                        if ($v['obs'] == 0) {
                            $aceptadas[] = $v['ver'];
                        }
                    }
                }
                
                // Mostrar una lista de todas las versiones aceptadas o N/A
                $serv['versiones_aceptadas'] = count($aceptadas) > 0 ? implode(', ', $aceptadas) : 'Ninguna';

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
        $icono = empty($icono) ? 'fas fa-cube' : $icono;

        $id_serv_nuevo = $model->agregaRegistroConBitacora(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act", "estatus", "icono"], 
            [$tipo_serv, $nom, $especs, $fecha_actual, 1, $icono],
            $id_sub, "Registró el catálogo de servicio: {$nom}"
        );

        if ($id_serv_nuevo) {
            // Versión inicial aceptada (obs = 0)
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

        $res = $model->actualizaRegistroConBitacora(
            "servicios", 
            ["tipo_serv", "nom", "especs", "u_act", "icono"], 
            "id_serv = ?", 
            [$tipo_serv, $nom, $especs, date('Y-m-d H:i:s'), $icono, $id_serv],
            $id_sub,
            "Actualizó detalles del servicio ID: {$id_serv} | {$nom}"
        );

        if ($res !== false) {
            return ["result" => 1, "msg" => "Datos del servicio actualizados exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar actualizar."];
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

        $res = $model->actualizaRegistroConBitacora(
            "servicios", 
            ["estatus"], 
            "id_serv = ?", 
            [0, $id_serv],
            $id_sub,
            "Desactivó (borrado lógico) el servicio ID: {$id_serv}"
        );

        if ($res !== false) {
            return ["result" => 1, "msg" => "Servicio desactivado correctamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar desactivar el servicio."];
    }

    // ==========================================================
    // HISTORIAL DE VERSIONES (MODIFICADO PARA MÚLTIPLES ACEPTADAS)
    // ==========================================================
    public function obtenerVersiones($id_sub, $id_serv) {
        $model = new MainModel();

        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        $versiones = $model->seleccionaRegistros("versiones", ["id_ver", "ver", "obs"], "id_serv = ? ORDER BY id_ver DESC", [$id_serv]);
        return ["result" => 1, "data" => $versiones ? $versiones : []];
    }

    public function agregarVersion($id_sub, $id_serv, $nueva_version, $marcar_aceptada) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "Permiso denegado para agregar versiones."];
        }

        // 0 = Aceptada, 1 = Obsoleta
        $estado_obs = ($marcar_aceptada == '1') ? 0 : 1;

        $id_ver_nueva = $model->agregaRegistro(
            "versiones", ["ver", "id_serv", "obs"], [$nueva_version, $id_serv, $estado_obs]
        );

        if ($id_ver_nueva) {
            $model->registrarBitacora($id_sub, 'INSERT', "Agregó la versión '{$nueva_version}' al servicio ID: {$id_serv}");
            $model->actualizaRegistro("servicios", ["u_act"], "id_serv = ?", [date('Y-m-d H:i:s'), $id_serv]);
            return ["result" => 1, "msg" => "Versión agregada al historial exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al guardar la nueva versión."];
    }

    public function cambiarEstadoVersion($id_sub, $id_serv, $id_ver, $nuevo_estado) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "Permiso denegado."];
        }

        // Solo cambiamos el estado de esta versión específica (no afecta a las demás)
        $res = $model->actualizaRegistro("versiones", ["obs"], "id_ver = ?", [$nuevo_estado, $id_ver]);

        if ($res !== false) {
            $model->actualizaRegistro("servicios", ["u_act"], "id_serv = ?", [date('Y-m-d H:i:s'), $id_serv]);
            $estadoStr = ($nuevo_estado == 1) ? 'Obsoleta' : 'Aceptada';
            $model->registrarBitacora($id_sub, 'UPDATE', "Marcó versión ID: {$id_ver} como {$estadoStr}");
            return ["result" => 1, "msg" => "El estado de la versión se actualizó correctamente."];
        }
        return ["result" => 0, "msg" => "Error al actualizar la versión."];
    }
}
?>