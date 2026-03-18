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
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'];
        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    private function traerContenido($model) {
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"], "1=1 ORDER BY nom_dep ASC");
        
        $segmentos_db = $model->seleccionaRegistrosJoin(
            "segmentos s",
            ["LEFT JOIN departamentos d ON s.id_dep = d.id_dep"],
            ["s.id_seg", "s.ip_ini", "s.ip_lim", "s.id_dep", "d.nom_dep"],
            "s.estatus = 1" // Se quita el ORDER BY para hacerlo con PHP
        );

        $segmentos = [];

        if ($segmentos_db) {
            foreach ($segmentos_db as $seg) {
                $redes = $model->seleccionaRegistros("infraestructura_red", ["ip", "est"], "id_seg = ? AND LOWER(est) != 'inactivo'", [$seg['id_seg']]);
                
                $total = 0; $libres = 0; $ocupadas = 0; $reservadas = 0;
                $lista_reservas = []; 

                if ($redes) {
                    foreach ($redes as $r) {
                        $total++;
                        $estado = strtolower(trim($r['est']));
                        
                        if ($estado === 'libre') $libres++;
                        elseif ($estado === 'ocupado') $ocupadas++;
                        elseif ($estado === 'reservado') {
                            $reservadas++;
                            $lista_reservas[] = $r['ip']; 
                        }
                    }
                }

                $pct_libres = $total > 0 ? round(($libres / $total) * 100) : 0;
                $pct_ocupadas = $total > 0 ? round(($ocupadas / $total) * 100) : 0;
                $pct_reservadas = $total > 0 ? round(($reservadas / $total) * 100) : 0;

                $seg['estadisticas'] = [
                    'total' => $total,
                    'libres' => $libres,
                    'ocupadas' => $ocupadas,
                    'reservadas' => $reservadas,
                    'pct_libres' => $pct_libres,
                    'pct_ocupadas' => $pct_ocupadas,
                    'pct_reservadas' => $pct_reservadas
                ];

                $seg['str_reservas'] = implode(', ', $lista_reservas);
                $segmentos[] = $seg;
            }

            // ORDENAMIENTO MATEMÁTICO DE TARJETAS
            usort($segmentos, function($a, $b) {
                return ip2long($a['ip_ini']) - ip2long($b['ip_ini']);
            });
        }

        return [
            'departamentos' => $departamentos ?: [],
            'segmentos' => $segmentos
        ];
    }

    public function insertarSegmento($id_sub, $id_dep, $ip_ini, $ip_lim, $reservas) {
        $model = new MainModel();

        if (!$this->verificarPermiso($id_sub, 2)) return ["result" => 0, "msg" => "Permiso denegado."];

        $long_ini = ip2long(trim($ip_ini));
        $long_fin = ip2long(trim($ip_lim));

        if ($long_ini === false || $long_fin === false || $long_ini > $long_fin) {
            return ["result" => 0, "msg" => "El rango del segmento no es válido matemáticamente."];
        }

        // BARRERA DE OVERLAP (Solapamiento de rangos)
        $segmentos_activos = $model->seleccionaRegistros("segmentos", ["id_seg", "ip_ini", "ip_lim"], "estatus = 1");
        if ($segmentos_activos) {
            foreach ($segmentos_activos as $s) {
                $ext_ini = ip2long($s['ip_ini']);
                $ext_fin = ip2long($s['ip_lim']);
                if ($long_ini <= $ext_fin && $long_fin >= $ext_ini) {
                    return ["result" => 0, "msg" => "El rango ingresado choca con el Segmento {$s['id_seg']} ({$s['ip_ini']} - {$s['ip_lim']}). Las IPs no pueden cruzarse."];
                }
            }
        }

        $ips_a_reservar = [];
        if (!empty(trim($reservas))) {
            $bloques = explode(',', $reservas);
            foreach ($bloques as $bloque) {
                $bloque = trim($bloque, " \t\n\r\0\x0B-");
                if (empty($bloque)) continue;

                if (strpos($bloque, '-') !== false) {
                    $partes = explode('-', $bloque);
                    if (count($partes) !== 2) return ["result" => 0, "msg" => "Error de sintaxis en el rango: $bloque"];
                    $l_rini = ip2long(trim($partes[0]));
                    $l_rfin = ip2long(trim($partes[1]));

                    if ($l_rini === false || $l_rfin === false || $l_rini > $l_rfin) return ["result" => 0, "msg" => "Rango de reserva inválido: $bloque"];
                    if ($l_rini < $long_ini || $l_rfin > $long_fin) return ["result" => 0, "msg" => "El rango a reservar '$bloque' escapa de los límites de la red."];
                    for ($i = $l_rini; $i <= $l_rfin; $i++) { $ips_a_reservar[] = long2ip($i); }
                } else {
                    $l_ip = ip2long($bloque);
                    if ($l_ip === false) return ["result" => 0, "msg" => "La dirección '$bloque' no es una IP válida."];
                    if ($l_ip < $long_ini || $l_ip > $long_fin) return ["result" => 0, "msg" => "La IP '$bloque' no pertenece al rango."];
                    $ips_a_reservar[] = $bloque;
                }
            }
            $ips_a_reservar = array_unique($ips_a_reservar); 
        }

        $model->agregaRegistro("segmentos", ["id_dep", "ip_ini", "ip_lim", "estatus"], [$id_dep, trim($ip_ini), trim($ip_lim), 1]);
        $seg_creado = $model->seleccionaRegistros("segmentos", ["id_seg"], "ip_ini = ? AND ip_lim = ? AND estatus = 1", [trim($ip_ini), trim($ip_lim)]);
        
        if ($seg_creado && count($seg_creado) > 0) {
            $id_seg_real = $seg_creado[0]['id_seg'];
            $model->registrarBitacora($id_sub, 'INSERT', "Creó segmento ID: {$id_seg_real} | Rango: {$ip_ini} a {$ip_lim}");

            if (!empty($ips_a_reservar)) {
                $count_reservas = 0;
                foreach ($ips_a_reservar as $ip_res) {
                    $res = $model->actualizaRegistro("infraestructura_red", ["est"], "id_seg = ? AND ip = ?", ['Reservado', $id_seg_real, $ip_res]);
                    if($res !== false) $count_reservas++;
                }
                $model->registrarBitacora($id_sub, 'UPDATE', "Se reservaron $count_reservas IPs al crear el segmento ID: $id_seg_real");
                return ["result" => 1, "msg" => "Segmento creado exitosamente con $count_reservas IPs reservadas."];
            }
            return ["result" => 1, "msg" => "Segmento creado exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al localizar el segmento recién creado."];
    }

    public function actualizarSegmento($id_sub, $id_seg, $id_dep, $ip_ini, $ip_lim, $reservas) {
        $model = new MainModel();
        if (!$this->verificarPermiso($id_sub, 1)) return ["result" => 0, "msg" => "Permiso denegado."];

        $long_ini = ip2long(trim($ip_ini));
        $long_fin = ip2long(trim($ip_lim));

        if ($long_ini === false || $long_fin === false || $long_ini > $long_fin) {
            return ["result" => 0, "msg" => "El rango del segmento no es válido matemáticamente."];
        }

        // VERIFICAMOS EMPALMES IGNORANDO AL PROPIO SEGMENTO QUE ESTAMOS EDITANDO
        $segmentos_activos = $model->seleccionaRegistros("segmentos", ["id_seg", "ip_ini", "ip_lim"], "estatus = 1 AND id_seg != ?", [$id_seg]);
        if ($segmentos_activos) {
            foreach ($segmentos_activos as $s) {
                $ext_ini = ip2long($s['ip_ini']);
                $ext_fin = ip2long($s['ip_lim']);
                if ($long_ini <= $ext_fin && $long_fin >= $ext_ini) {
                    return ["result" => 0, "msg" => "El nuevo rango choca con el Segmento {$s['id_seg']} ({$s['ip_ini']} - {$s['ip_lim']}). Las IPs no pueden cruzarse."];
                }
            }
        }

        $ips_nuevas = [];
        if (!empty(trim($reservas))) {
            $bloques = explode(',', $reservas);
            foreach ($bloques as $bloque) {
                $bloque = trim($bloque, " \t\n\r\0\x0B-");
                if (empty($bloque)) continue;

                if (strpos($bloque, '-') !== false) {
                    $partes = explode('-', $bloque);
                    if (count($partes) !== 2) return ["result" => 0, "msg" => "Error de sintaxis en el rango: $bloque"];
                    $l_rini = ip2long(trim($partes[0]));
                    $l_rfin = ip2long(trim($partes[1]));
                    if ($l_rini === false || $l_rfin === false || $l_rini > $l_rfin) return ["result" => 0, "msg" => "Rango de reserva inválido: $bloque"];
                    if ($l_rini < $long_ini || $l_rfin > $long_fin) return ["result" => 0, "msg" => "El rango '$bloque' escapa de los nuevos límites de la red."];
                    for ($i = $l_rini; $i <= $l_rfin; $i++) { $ips_nuevas[] = long2ip($i); }
                } else {
                    $l_ip = ip2long($bloque);
                    if ($l_ip === false) return ["result" => 0, "msg" => "La dirección '$bloque' no es una IP válida."];
                    if ($l_ip < $long_ini || $l_ip > $long_fin) return ["result" => 0, "msg" => "La IP '$bloque' no pertenece al nuevo rango."];
                    $ips_nuevas[] = $bloque;
                }
            }
            $ips_nuevas = array_unique($ips_nuevas); 
        }

        // AL ACTUALIZAR LOS LÍMITES AQUÍ, EL TRIGGER DE LA BD HARÁ SU TRABAJO 
        $model->actualizaRegistro("segmentos", ["id_dep", "ip_ini", "ip_lim"], "id_seg = ?", [$id_dep, trim($ip_ini), trim($ip_lim), $id_seg]);

        // LOGICA DE RESERVAS
        $actuales_db = $model->seleccionaRegistros("infraestructura_red", ["ip"], "id_seg = ? AND LOWER(est) = 'reservado'", [$id_seg]);
        $ips_viejas = [];
        if ($actuales_db) {
            foreach ($actuales_db as $row) { $ips_viejas[] = $row['ip']; }
        }

        $ips_a_liberar = array_diff($ips_viejas, $ips_nuevas);
        $ips_a_reservar = array_diff($ips_nuevas, $ips_viejas);

        if (!empty($ips_a_liberar)) {
            foreach ($ips_a_liberar as $ip) {
                $model->actualizaRegistro("infraestructura_red", ["est"], "id_seg = ? AND ip = ? AND LOWER(est) = 'reservado'", ['Libre', $id_seg, $ip]);
            }
        }

        $fallos = 0;
        if (!empty($ips_a_reservar)) {
            foreach ($ips_a_reservar as $ip) {
                $check = $model->seleccionaRegistros("infraestructura_red", ["est"], "id_seg = ? AND ip = ?", [$id_seg, $ip]);
                if ($check && strtolower($check[0]['est']) === 'libre') {
                    $model->actualizaRegistro("infraestructura_red", ["est"], "id_seg = ? AND ip = ?", ['Reservado', $id_seg, $ip]);
                } else {
                    $fallos++;
                }
            }
        }

        $msg = "Segmento y reservas actualizadas correctamente.";
        if ($fallos > 0) $msg .= " (Aviso: $fallos IP(s) no se reservaron por estar ocupadas o fuera del nuevo rango).";

        $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó rango y reservas del segmento ID: {$id_seg}");
        return ["result" => 1, "msg" => $msg];
    }

    public function eliminarSegmento($id_sub, $id_seg) {
        $model = new MainModel();
        $uso = $model->seleccionaRegistros("infraestructura_red", ["id_disp"], "id_seg = ? AND id_disp IS NOT NULL", [$id_seg]);
        if ($uso && count($uso) > 0) return ["result" => 0, "msg" => "No puede eliminar este segmento porque tiene " . count($uso) . " dispositivo(s) conectado(s)."];

        $res = $model->actualizaRegistro("segmentos", ["estatus"], "id_seg = ?", [0, $id_seg]);
        if ($res !== false) {
            $model->registrarBitacora($id_sub, 'DELETE', "Realizó borrado lógico del segmento de red ID: {$id_seg}");
            return ["result" => 1, "msg" => "Segmento desactivado correctamente."];
        }
        return ["result" => 0, "msg" => "Error al intentar desactivar el segmento."];
    }

    public function obtenerIpsSegmento($id_seg) {
        $model = new MainModel();
        $tabla = "infraestructura_red r";
        $joins = ["LEFT JOIN dispositivos d ON r.id_disp = d.id_disp"];
        $campos = ["r.id_red", "r.ip", "r.est", "d.nom_host", "d.num_inv"];
        $ips = $model->seleccionaRegistrosJoin($tabla, $joins, $campos, "r.id_seg = ? AND LOWER(r.est) != 'inactivo'", [$id_seg]);
        
        if ($ips) {
            usort($ips, function($a, $b) { return ip2long($a['ip']) - ip2long($b['ip']); });
            return ["result" => 1, "data" => $ips];
        }
        return ["result" => 0, "msg" => "No se encontraron IPs activas para este segmento."];
    }
}
?>