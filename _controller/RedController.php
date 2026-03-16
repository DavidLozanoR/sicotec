<?php
require_once "MainModel.php";

class RedController {

    const VISTA = "_view/red.html.php";
    const JS = "js/red.js";

    private $slug;

    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();
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
        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    private function traerContenido($model) {
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"]);

        // Solo traemos los segmentos que NO han sido borrados lógicamente (estatus = 1)
        $tabla = "segmentos s";
        $joins = ["LEFT JOIN departamentos d ON s.id_dep = d.id_dep"];
        $campos = ["s.*", "d.nom_dep"];
        $segmentos = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, "s.estatus = 1", []);

        $lista_segmentos = [];

        if ($segmentos) {
            foreach ($segmentos as $seg) {
                // Contamos las IPs físicas que le pertenecen a este segmento y no están inactivas
                $ips = $model->seleccionaRegistros("infraestructura_red", ["est"], "id_seg = ? AND LOWER(est) != 'inactivo'", [$seg['id_seg']]);
                
                $total_ips = $ips ? count($ips) : 0;
                $ocupadas = 0; $reservadas = 0; $libres = 0;

                if ($ips) {
                    foreach ($ips as $ip) {
                        $estado = strtolower(trim($ip['est']));
                        if ($estado === 'ocupado') { $ocupadas++; }
                        elseif ($estado === 'reservado') { $reservadas++; }
                        elseif ($estado === 'libre') { $libres++; }
                    }
                }

                $seg['estadisticas'] = [
                    'total' => $total_ips,
                    'ocupadas' => $ocupadas,
                    'reservadas' => $reservadas,
                    'libres' => $libres,
                    'pct_ocupadas' => ($total_ips > 0) ? round(($ocupadas * 100) / $total_ips) : 0,
                    'pct_reservadas' => ($total_ips > 0) ? round(($reservadas * 100) / $total_ips) : 0,
                    'pct_libres' => ($total_ips > 0) ? round(($libres * 100) / $total_ips) : 0
                ];
                $lista_segmentos[] = $seg;
            }
        }
        return ['segmentos' => $lista_segmentos, 'departamentos' => $departamentos];
    }

    // ==========================================================
    // CRUD DE SEGMENTOS
    // ==========================================================
    public function insertarSegmento($id_sub, $id_dep, $ip_ini, $ip_lim) {
        $model = new MainModel();
        
        if (!filter_var($ip_ini, FILTER_VALIDATE_IP) || !filter_var($ip_lim, FILTER_VALIDATE_IP)) {
            return ["result" => 0, "msg" => "Las direcciones IP proporcionadas no tienen un formato válido."];
        }

        // =========================================================
        // ESCUDO: PREVENIR TRASLAPE CON SEGMENTOS ACTIVOS
        // =========================================================
        $nuevo_ini_num = ip2long($ip_ini);
        $nuevo_lim_num = ip2long($ip_lim);

        if ($nuevo_ini_num > $nuevo_lim_num) {
            return ["result" => 0, "msg" => "La IP Inicial no puede ser mayor que la IP Límite."];
        }

        $segmentos_activos = $model->seleccionaRegistros("segmentos", ["id_seg", "ip_ini", "ip_lim"], "estatus = 1");
        
        if ($segmentos_activos) {
            foreach ($segmentos_activos as $seg) {
                $activo_ini_num = ip2long($seg['ip_ini']);
                $activo_lim_num = ip2long($seg['ip_lim']);

                // Si los rangos se cruzan en cualquier punto
                if ($nuevo_ini_num <= $activo_lim_num && $nuevo_lim_num >= $activo_ini_num) {
                    return ["result" => 0, "msg" => "Conflicto de Red: El rango choca con el Segmento Activo ID {$seg['id_seg']} ({$seg['ip_ini']} - {$seg['ip_lim']})."];
                }
            }
        }
        // =========================================================

        // Al insertar, el Trigger de la BD dibujará las IPs o las reciclará si estaban inactivas
        $id_seg_nuevo = $model->agregaRegistroConBitacora(
            "segmentos", ["id_dep", "ip_ini", "ip_lim", "estatus"], [$id_dep, $ip_ini, $ip_lim, 1],
            $id_sub, "Registró el segmento de red: {$ip_ini} al {$ip_lim}"
        );

        if ($id_seg_nuevo) return ["result" => 1, "msg" => "El segmento y sus direcciones IP se generaron exitosamente."];
        return ["result" => 0, "msg" => "No se pudo crear el segmento en la base de datos."];
    }

public function actualizarSegmento($id_sub, $id_seg, $id_dep, $ip_ini, $ip_lim) {
        $model = new MainModel();

        if (!filter_var($ip_ini, FILTER_VALIDATE_IP) || !filter_var($ip_lim, FILTER_VALIDATE_IP)) {
            return ["result" => 0, "msg" => "Formato de IP inválido."];
        }

        // =========================================================
        // ESCUDO: PREVENIR TRASLAPE CON OTROS SEGMENTOS ACTIVOS
        // =========================================================
        $nuevo_ini_num = ip2long($ip_ini);
        $nuevo_lim_num = ip2long($ip_lim);

        if ($nuevo_ini_num > $nuevo_lim_num) {
            return ["result" => 0, "msg" => "La IP Inicial no puede ser mayor que la IP Límite."];
        }

        // Traemos todos los segmentos activos EXCEPTO el segmento que estamos editando
        $segmentos_activos = $model->seleccionaRegistros("segmentos", ["id_seg", "ip_ini", "ip_lim"], "estatus = 1 AND id_seg != ?", [$id_seg]);
        
        if ($segmentos_activos) {
            foreach ($segmentos_activos as $seg) {
                $activo_ini_num = ip2long($seg['ip_ini']);
                $activo_lim_num = ip2long($seg['ip_lim']);

                // Si los rangos se cruzan en cualquier punto
                if ($nuevo_ini_num <= $activo_lim_num && $nuevo_lim_num >= $activo_ini_num) {
                    return ["result" => 0, "msg" => "Conflicto de Red: El nuevo rango choca con el Segmento Activo ID {$seg['id_seg']} ({$seg['ip_ini']} - {$seg['ip_lim']})."];
                }
            }
        }
        // =========================================================

        // Proteger IPs ocupadas si el rango se encoge
        $ips_en_uso = $model->seleccionaRegistros("infraestructura_red", ["ip", "est"], "id_seg = ? AND LOWER(est) NOT IN ('libre', 'inactivo')", [$id_seg]);
        if ($ips_en_uso) {
            foreach ($ips_en_uso as $ip) {
                $ip_num = ip2long($ip['ip']);
                // Reutilizamos las variables numéricas que ya calculamos arriba
                if ($ip_num < $nuevo_ini_num || $ip_num > $nuevo_lim_num) {
                    return ["result" => 0, "msg" => "La IP {$ip['ip']} está fuera del nuevo rango y se encuentra '{$ip['est']}'. Libérela antes de recortar el segmento."];
                }
            }
        }

        // Actualizamos el registro (El Trigger ajustará las IPs automáticamente)
        $res = $model->actualizaRegistro("segmentos", ["id_dep", "ip_ini", "ip_lim"], "id_seg = ?", [$id_dep, $ip_ini, $ip_lim, $id_seg]);
        if ($res) {
            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó el segmento ID: {$id_seg}. Rango: {$ip_ini} a {$ip_lim}");
            return ["result" => 1, "msg" => "El segmento se actualizó correctamente. Las IPs se ajustaron automáticamente."];
        }
        return ["result" => 0, "msg" => "Error al actualizar el segmento."];
    }

    public function eliminarSegmento($id_sub, $id_seg) {
        $model = new MainModel();
        
        // 1. Validar que no haya IPs ocupadas por dispositivos
        $uso = $model->seleccionaRegistros("infraestructura_red", ["id_red"], "id_seg = ? AND LOWER(est) NOT IN ('libre', 'inactivo')", [$id_seg]);
        if ($uso && count($uso) > 0) {
            return ["result" => 0, "msg" => "No se puede desactivar. Existen equipos conectados a este segmento."];
        }

        // 2. Borrado Lógico de las IPs (Cambiamos las libres a 'Inactivo')
        $model->actualizaRegistro(
            "infraestructura_red", 
            ["est"], 
            "id_seg = ? AND LOWER(est) = 'libre'", 
            ['Inactivo', $id_seg]
        );

        // 3. Borrado Lógico del Segmento
        $res = $model->actualizaRegistro(
            "segmentos", 
            ["estatus"], 
            "id_seg = ?", 
            [0, $id_seg]
        );

        if ($res) {
            $model->registrarBitacora($id_sub, 'DELETE', "Realizó borrado lógico del segmento de red ID: {$id_seg}");
            return ["result" => 1, "msg" => "Segmento desactivado correctamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar desactivar el segmento."];
    }

    // ==========================================================
    // MÉTODO PARA EL MODAL EXPLORADOR DE IPs
    // ==========================================================
    public function obtenerIpsSegmento($id_seg) {
        $model = new MainModel();
        $tabla = "infraestructura_red r";
        $joins = ["LEFT JOIN dispositivos d ON r.id_disp = d.id_disp"];
        // Solo mostramos las IPs que no están inactivas
        $campos = ["r.id_red", "r.ip", "r.est", "d.nom_host", "d.num_inv"];
        
        $ips = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, "r.id_seg = ? AND LOWER(r.est) != 'inactivo'", [$id_seg]);
        
        if ($ips) {
            // Ordenamos las IPs matemáticamente para que se muestren en secuencia perfecta
            usort($ips, function($a, $b) {
                return ip2long($a['ip']) - ip2long($b['ip']);
            });
        }

        return ["result" => 1, "data" => $ips ? $ips : []];
    }
}
?>