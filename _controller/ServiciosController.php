<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mariadb
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador principal para la gestión del Catálogo de Servicios.
 * Coordina la visualización de software/herramientas, el conteo de sus 
 * instalaciones (equipos asociados) y el manejo de su historial de versiones.
 */
class ServiciosController {

    /** @var string Ruta al archivo de la vista HTML */
    const VISTA = "_view/servicios.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado */
    const JS = "js/servicios.js";

    /** @var string|null Slug identificador del submódulo en curso */
    private $slug;
    
    /** @var int Identificador del permiso de edición en BD */
    const PERMISO_EDITAR = 1;
    
    /** @var int Identificador del permiso de inserción en BD */
    const PERMISO_AGREGAR = 2;
    
    /** @var int Identificador del permiso de baja/eliminación en BD */
    const PERMISO_ELIMINAR = 3;
    
    /** @var int Identificador del permiso de visualización en BD */
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
     * Prepara y renderiza el contenido del módulo de Servicios.
     * Realiza verificaciones de seguridad mediante RBAC antes de extraer
     * el catálogo completo de servicios para enviarlo a la vista.
     *
     * @return void Renderiza HTML directamente o muestra alerta de acceso denegado.
     */
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

    /**
     * Inyecta el archivo JavaScript asociado a la vista en el DOM.
     *
     * @return void
     */
    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    /**
     * Comprueba si el rol del usuario posee un permiso específico (RBAC).
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

    /**
     * Extrae el catálogo de servicios desde la base de datos.
     * Realiza conteos iterativos para calcular la cantidad de instalaciones 
     * por cada servicio y filtra las versiones activas/aceptadas.
     *
     * @param MainModel $model Instancia activa del modelo.
     * @return array Matriz asociativa que contiene la lista de servicios enriquecida.
     */
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