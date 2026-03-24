<?php
require_once '_model/MainModel.php';

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
        
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$url_buscar]);
        $id_sub_real = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : null;

        if (!$id_sub_real || $this->id_submod != $id_sub_real) {
            echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-5'>
                    <h5 class='fw-bold mb-1'><i class='fas fa-shield-alt me-2'></i> Alerta de Seguridad</h5>
                    <p class='mb-0 small'>El identificador del submódulo en la URL ha sido alterado o es inválido.</p>
                  </div>";
            return; 
        }

        $es_edicion = !empty($this->id_disp);
        $permiso_requerido = $es_edicion ? 1 : 2; 
        $accion_texto = $es_edicion ? "editar" : "registrar nuevos";

        $datos = $this->traerContenido($model);
        extract($datos);

        include self::VISTA;
    }

    private function traerContenido($model) {
        $dispositivo = null;
        $servicios_asignados = [];

        // ==============================================================
        // LÓGICA NUEVA: REDES Y VLANS (Sin dependencias de departamento)
        // ==============================================================
        $tablaRed = "infraestructura_red r";
        $joinsRed = [
            "LEFT JOIN segmentos s ON r.id_seg = s.id_seg",
            "LEFT JOIN vlans v ON s.id_vlan = v.id_vlan" // Solo unimos con VLANs
        ];
        
        $camposRed = [
            "r.id_red", "r.ip", "s.id_seg", 
            "COALESCE(v.nom_vlan, 'Sin VLAN asignada') AS nom_vlan" // Mostramos la VLAN o un texto por defecto
        ];
        
        $condicionRed = "r.id_disp IS NULL";
        $paramsRed = [];
        // Si estamos editando, permitimos traer también las IPs que ya tiene asignadas este equipo
        if (!empty($this->id_disp)) {
            $condicionRed = "(r.id_disp IS NULL OR r.id_disp = ?)";
            $paramsRed = [$this->id_disp];
        }
        
        $condicionRed .= " AND LOWER(r.est) != 'inactivo' ORDER BY v.nom_vlan ASC, INET_ATON(r.ip) ASC";
        $redes_disponibles = $model->seleccionaRegistrosJoin($tablaRed, $joinsRed, $camposRed, $condicionRed, $paramsRed);
        
        $campos_po = ["id_po", "id_dep", "TRIM(CONCAT(nombres, ' ', ape_pa, ' ', COALESCE(ape_ma, ''))) AS nombre_completo"];
        $personal_operativo = $model->seleccionaRegistros("personal_operativo", $campos_po, "1=1 ORDER BY nombres ASC");

        $servicios = $model->seleccionaRegistros("servicios", ["id_serv", "nom"], "estatus = 1");
        $versiones = $model->seleccionaRegistros("versiones", ["id_ver", "ver", "id_serv", "obs"]);

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