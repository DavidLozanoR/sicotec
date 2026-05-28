<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once '_model/MainModel.php';

/**
 * Controlador para la interfaz del formulario de Dispositivos (Alta y Edición).
 * Gestiona la validación de acceso al formulario y la recolección de los
 * catálogos necesarios para renderizar la vista.
 */
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

    public function renderContent() {
        $model = new MainModel();
        $url_buscar = $this->slug; 
        
        // Validación de seguridad para prevenir inyección de IDs en la URL
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido.</p>
                  </div>";
            return; 
        }

        // Auto-ensamblaje del contenido de la vista
        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    /**
     * Orquestador principal que une los bloques de datos.
     */
    private function traerContenido($model) {
        // 1. Carga automática de catálogos estáticos
        $datosBase = $this->obtenerCatalogosBase($model);

        // 2. Carga de redes (Depende del modo edición/alta)
        $datosRed = ['redes_disponibles' => $this->obtenerRedesDisponibles($model)];

        // 3. Carga de datos del equipo (Solo si estamos en edición)
        $datosEquipo = $this->obtenerDatosDispositivo($model);

        // 4. Variables de contexto de la vista
        $contexto = [
            'id_sub_actual'  => $this->id_submod,
            'id_disp'        => $this->id_disp,
            'puede_eliminar' => $this->verificarPermiso($this->id_submod, PERM_ELIMINAR)
        ];

        // Fusionamos todos los arreglos limpiamente
        return array_merge($datosBase, $datosRed, $datosEquipo, $contexto);
    }

    /**
     * Extrae todos los catálogos base necesarios para popular los <select>.
     */
    private function obtenerCatalogosBase($model) {
        $campos_po = ["id_po", "id_dep", "TRIM(CONCAT(nombres, ' ', ape_pa, ' ', COALESCE(ape_ma, ''))) AS nombre_completo"];
        
        return [
            'departamentos'      => $model->seleccionaRegistros("departamentos", ["id_dep","clave_dep","nom_dep"]) ?: [],
            'marcas'             => $model->seleccionaRegistros("marcas", ["id_marca", "nom_marca"], "estatus = 1 ORDER BY nom_marca ASC") ?: [],
            'dominios'           => $model->seleccionaRegistros("dominios", ["id_dom", "nom_dom"], "estatus = 1 ORDER BY nom_dom ASC") ?: [],
            'personal_operativo' => $model->seleccionaRegistros("personal_operativo", $campos_po, "1=1 ORDER BY nombres ASC") ?: [],
            'servicios'          => $model->seleccionaRegistros("servicios", ["id_serv", "nom"], "estatus = 1") ?: [],
            'versiones'          => $model->seleccionaRegistros("versiones", ["id_ver", "ver", "id_serv", "obs"]) ?: []
        ];
    }

    /**
     * Lógica de Redes y VLANs.
     */
    private function obtenerRedesDisponibles($model) {
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
        
        // Si estamos editando, permitimos traer también las IPs que ya tiene asignadas este equipo
        if (!empty($this->id_disp)) {
            $condicionRed = "(r.id_disp IS NULL OR r.id_disp = ?)";
            $paramsRed = [$this->id_disp];
        }
        
        $condicionRed .= " AND LOWER(r.est) != 'inactivo' AND LOWER(r.est) != 'reservado' ORDER BY v.nom_vlan ASC, INET_ATON(r.ip) ASC";
        
        return $model->seleccionaRegistrosJoin("infraestructura_red r", $joinsRed, $camposRed, $condicionRed, $paramsRed) ?: [];
    }

    /**
     * Extrae el dispositivo, sus interfaces actuales y sus servicios instalados.
     */
    private function obtenerDatosDispositivo($model) {
        // Estructura por defecto para cuando es un registro nuevo (Alta)
        $data = [
            'dispositivo'         => null,
            'modelos_marca'       => [],
            'servicios_asignados' => []
        ];

        // Si no hay ID, retornamos la estructura vacía
        if (empty($this->id_disp)) return $data;

        $resultado = $model->seleccionaRegistros("dispositivos", ["*"], "id_disp = ?", [$this->id_disp]);
        
        if (empty($resultado)) {
            die("<div class='alert alert-warning m-4 fw-bold'>El dispositivo no existe.</div>");
        }

        $dispositivo = $resultado[0];
        
        // Modelos de la marca actual
        if (!empty($dispositivo['id_marca'])) {
            $data['modelos_marca'] = $model->seleccionaRegistros("modelos", ["id_modelo", "nom_modelo"], "id_marca = ? AND estatus = 1 ORDER BY nom_modelo ASC", [$dispositivo['id_marca']]) ?: [];
        }
        
        // Interfaces de red instaladas
        $interfaces_actuales = $model->seleccionaRegistrosJoin(
            "infraestructura_red r",
            ["LEFT JOIN segmentos s ON r.id_seg = s.id_seg"],
            ["r.id_red", "r.ip", "r.tipo_conexion", "s.id_seg"],
            "r.id_disp = ?",
            [$this->id_disp]
        );
        $dispositivo['interfaces'] = $interfaces_actuales ?: [];
        
        // Software y servicios instalados
        $joins_serv = [
            "JOIN servicios s ON ds.id_serv = s.id_serv",
            "JOIN versiones v_inst ON ds.id_ver_instalada = v_inst.id_ver"
        ];
        $campos_serv = [
            "ds.id_serv", "ds.id_ver_instalada", "s.nom AS nombre_servicio", 
            "v_inst.ver AS version_instalada", "v_inst.obs AS es_obsoleta"
        ];
        $data['servicios_asignados'] = $model->seleccionaRegistrosJoin("dispositivo_servicio ds", $joins_serv, $campos_serv, "ds.id_disp = ?", [$this->id_disp]) ?: [];
        
        $data['dispositivo'] = $dispositivo;

        return $data;
    }
    

    public function renderJS() {
        if(file_exists(self::JS)){ include self::JS; }
    }
}
?>