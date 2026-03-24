<?php
require_once "_model/MainModel.php";

class DispositivosController {

    const VISTA = "_view/dispositivos.html.php";
    const JS = "js/dispositivos.js";
    private $slug;

    function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
        $datos = $this->traerContenido($model);
        extract($datos);
        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    private function traerContenido($model) {
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;

        // Mantenemos el catálogo para el select de filtro superior solamente
        $vlans = $model->seleccionaRegistros("vlans", ["id_vlan", "nom_vlan"], "1=1 ORDER BY nom_vlan ASC");
        $tablaPrincipal = "dispositivos d";
        
        // JOINS: Eliminamos la unión con la tabla departamentos en el flujo de red
        $joins = [
            "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
            "LEFT JOIN segmentos seg ON r.id_seg = seg.id_seg",
            "LEFT JOIN vlans v ON seg.id_vlan = v.id_vlan",
            "LEFT JOIN personal_operativo po ON d.asig_po_id = po.id_po"
        ];

        $campos = [
            "d.id_disp", "d.nom_host", "d.marca", "d.modelo", "d.num_ser", "d.num_inv",
            "d.mac_ethernet", "d.mac_wifi", "d.resp_po_id", "d.asig_po_id",
            // LÓGICA DINÁMICA: Generamos el bloque de Red (Icono + IP + VLAN) directamente en SQL
            "GROUP_CONCAT(
                CONCAT(
                    '<div class=\"mb-2 border-bottom border-light pb-1\">',
                        '<i class=\"fas ', IF(r.tipo_conexion = 'WiFi', 'fa-wifi', 'fa-ethernet'), ' me-2 text-secondary\"></i>',
                        '<span class=\"fw-bold\">', r.ip, '</span><br>',
                        '<small class=\"text-muted\" style=\"font-size: 0.7rem;\">', COALESCE(v.nom_vlan, 'Sin VLAN'), '</small>',
                    '</div>'
                ) SEPARATOR ''
            ) AS info_red_html",
            "po.id_dep", // Lo mantenemos solo para el data-attribute de filtrado JS
            "TRIM(CONCAT(po.nombres, ' ', po.ape_pa, ' ', COALESCE(po.ape_ma, ''))) AS responsable"
        ];
        
        $dispositivos = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, "1=1 GROUP BY d.id_disp ORDER BY d.id_disp DESC");

        if (!$dispositivos) {
            $dispositivos = [];
        } else {
            foreach ($dispositivos as &$disp) {
                // Lógica de software (se mantiene intacta)
                $servicios = $model->seleccionaRegistrosJoin(
                    "dispositivo_servicio ds",
                    ["JOIN servicios s ON ds.id_serv = s.id_serv", "JOIN versiones v ON ds.id_ver_instalada = v.id_ver"],
                    ["s.nom", "v.ver", "v.obs"],
                    "ds.id_disp = ?",
                    [$disp['id_disp']]
                );
                
                $disp['tiene_obsoleto'] = false;
                $str_software = "";
                if ($servicios) {
                    foreach ($servicios as $s) {
                        if ($s['obs'] == 1) $disp['tiene_obsoleto'] = true;
                        $str_software .= $s['nom'] . " " . $s['ver'] . " ";
                    }
                }
                $disp['str_software'] = trim($str_software);
                $disp['estado_asig'] = (!empty($disp['resp_po_id']) || !empty($disp['asig_po_id'])) ? 'asignado' : 'sin_asignar';
            }
            unset($disp); 
        }

        return [
            'id_sub_actual' => $id_sub_actual,
            'departamentos' => $vlans ?: [],
            'dispositivos' => $dispositivos
        ];
    }
}
?>