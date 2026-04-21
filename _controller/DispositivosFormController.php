<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

require_once '_model/MainModel.php';

/**
 * Controlador para la interfaz de formulario de Dispositivos (Alta y Edición).
 * Gestiona la recolección de catálogos necesarios (redes, software, personal) 
 * y la validación de seguridad de la URL para evitar inyecciones de ID de submódulo.
 */
class DispositivosFormController {

    /** @var string Ruta al archivo de la vista HTML del formulario */
    const VISTA = "_view/dispositivosForm.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado al formulario */
    const JS = "js/dispositivosForm.js";

    /** @var int|null ID del dispositivo a editar (Null si es un alta nueva) */
    private $id_disp;
    
    /** @var int|null ID numérico del submódulo extraído de la URL */
    private $id_submod;
    
    /** @var string|null Slug identificador del submódulo */
    private $slug;

    /**
     * Constructor del controlador del formulario.
     * Inicializa la sesión y captura los parámetros de contexto desde el enrutador.
     *
     * @param string|null $slug Nombre corto del módulo.
     * @param int|null $id_submod ID del submódulo en BD.
     * @param int|null $id ID del dispositivo para casos de edición.
     */
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

    /**
     * Valida si el rol activo del usuario posee un permiso específico (RBAC).
     *
     * @param int $id_sub ID del submódulo.
     * @param int $id_permiso ID numérico del permiso requerido.
     * @return bool True si el permiso existe, false en caso contrario.
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
     * Construye y renderiza la vista del formulario.
     * Ejecuta una validación de seguridad estricta para asegurar que el `$id_submod` 
     * proporcionado en la URL coincide estructuralmente con el `$slug`.
     *
     * @return void
     */
    public function renderContent() {
        $model = new MainModel();

        $url_buscar = $this->slug; 
        
        // Validación Anti-Tampering (Alteración de URL)
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido.</p>
                  </div>";
            return; 
        }

        // Definición de contexto (Alta vs Edición)
        $es_edicion = !empty($this->id_disp);
        $permiso_requerido = $es_edicion ? 1 : 2; 
        $accion_texto = $es_edicion ? "editar" : "registrar nuevos";

        // Extracción de catálogos y datos del dispositivo (si es edición)
        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    /**
     * Carga los catálogos y la información base requerida por el formulario.
     * Extrae IPs disponibles o asociadas al equipo actual.
     *
     * @param MainModel $model Instancia activa del modelo.
     * @return array Arreglo asociativo con los datos listos para extract(). (Cortado a linea 100).
     */
    private function traerContenido($model) {
        $dispositivo = null;
        $servicios_asignados = [];

        // ==============================================================
        // LÓGICA DE EXTRACCIÓN: REDES Y VLANS
        // Extrae IPs libres o aquellas que ya pertenecen al dispositivo actual.
        // ==============================================================
        $tablaRed = "infraestructura_red r";
        $joinsRed = [
            "LEFT JOIN segmentos s ON r.id_seg = s.id_seg",
            "LEFT JOIN vlans v ON s.id_vlan = v.id_vlan" 
        ];
        
        $camposRed = [
            "r.id_red", "r.ip", "s.id_seg", 
            "COALESCE(v.nom_vlan, 'Sin VLAN asignada') AS nom_vlan" 
        ];
        
        $condicionRed = "r.id_disp IS NULL";
        $paramsRed = [];
        
        // Contexto de Edición: Incluir IPs que ya son propiedad de este equipo
        if (!empty($this->id_disp)) {
            $condicionRed = "(r.id_disp IS NULL OR r.id_disp = ?)";
            $paramsRed = [$this->id_disp];
            $condicionRed .= " AND LOWER(r.est) != 'inactivo' AND LOWER(r.est) != 'reservado' ORDER BY v.nom_vlan ASC, INET_ATON(r.ip) ASC";
            $redes_disponibles = $model->seleccionaRegistrosJoin($tablaRed, $joinsRed, $camposRed, $condicionRed, $paramsRed);
        
            $campos_po = ["id_po", "id_dep", "TRIM(CONCAT(nombres, ' ', ape_pa, ' ', COALESCE(ape_ma, ''))) AS nombre_completo"];
            $personal_operativo = $model->seleccionaRegistros("personal_operativo", $campos_po, "1=1 ORDER BY nombres ASC");

        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom"], "estatus = 1");
        $versiones = $model->seleccionaRegistros("versiones", ["id_ver", "ver", "id_serv", "obs"]);
        }
        if (!empty($this->id_disp)) {
            $campos = ["*"];
            $resultado = $model->seleccionaRegistros("dispositivos", $campos, "id_disp = ?", [$this->id_disp]);
            
            if (!empty($resultado)) {
                $dispositivo = $resultado[0];
                
                // Traemos las interfaces exactas que tiene asignadas el equipo actualmente
                $interfaces_actuales = $model->seleccionaRegistrosJoin(
                    "infraestructura_red r",
                    ["LEFT JOIN segmentos s ON r.id_seg = s.id_seg"],
                    ["r.id_red", "r.ip", "r.tipo_conexion", "s.id_seg"],
                    "r.id_disp = ?",
                    [$this->id_disp]
                );
                $dispositivo['interfaces'] = $interfaces_actuales ?: [];
                
                $joins_serv = [
                    "JOIN servicios s ON ds.id_serv = s.id_serv",
                    "JOIN versiones v_inst ON ds.id_ver_instalada = v_inst.id_ver"
                ];
                $campos_serv = [
                    "ds.id_serv", "ds.id_ver_instalada", "s.nom AS nombre_servicio", 
                    "v_inst.ver AS version_instalada", "v_inst.obs AS es_obsoleta"
                ];
                $servicios_asignados = $model->seleccionaRegistrosJoin("dispositivo_servicio ds", $joins_serv, $campos_serv, "ds.id_disp = ?", [$this->id_disp]);
            } else {
                die("<div class='alert alert-warning m-4 fw-bold'>El dispositivo no existe.</div>");
            }
        }

        $puede_eliminar = $this->verificarPermiso($this->id_submod, 3);

        return [
            'redes_disponibles'   => $redes_disponibles ?: [],
            'personal_operativo'  => $personal_operativo ?: [],
            'servicios'           => $servicios ?: [],
            'versiones'           => $versiones ?: [],
            'servicios_asignados' => $servicios_asignados ?: [],
            'dispositivo'         => $dispositivo,
            'id_sub_actual'       => $this->id_submod,
            'id_disp'             => $this->id_disp,
            'puede_eliminar'      => $puede_eliminar
        ];
    }

    public function renderJS() {
        if(file_exists(self::JS)){ include self::JS; }
    }
}
?>