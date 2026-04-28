<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mariadb
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador para el módulo de Infraestructura de Red.
 * Gestiona la visualización de segmentos de red, asignaciones de VLANs 
 * y el cálculo estadístico de disponibilidad de direcciones IP.
 */
class RedController {

    /** @var string Ruta al archivo de la vista HTML */
    const VISTA = "_view/red.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado */
    const JS = "js/red.js";

    /** @var string|null Slug identificador del submódulo en curso */
    private $slug;

    /**
     * Constructor del controlador.
     * Valida la existencia de una sesión activa antes de inicializar la clase.
     *
     * @param string|null $slug Slug amigable del submódulo.
     */
    function __construct($slug = null) { 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    /**
     * Prepara y renderiza la vista principal del módulo de Red.
     * Verifica los permisos de consulta del usuario y carga las estadísticas de los segmentos.
     *
     * @return void Renderiza HTML directamente o muestra una alerta de acceso denegado.
     */
    public function renderContent() {
        $model = new MainModel();
         $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 3; 

        if (!$this->verificarPermiso($id_sub,PERM_CONSULTAR )) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a la Infraestructura de Red.</p>
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
     * Verifica si el rol del usuario actual cuenta con un permiso específico (RBAC).
     *
     * @param int $id_sub ID del submódulo en evaluación.
     * @param int $id_permiso ID del tipo de permiso solicitado.
     * @return bool True si el permiso existe en la base de datos, false de lo contrario.
     */
    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'];
        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    /**
     * Extrae y procesa la información de los segmentos de red y VLANs.
     * Calcula métricas de uso (IPs libres, ocupadas, reservadas) por segmento 
     * y los ordena lógicamente basados en su valor numérico de IP.
     *
     * @param MainModel $model Instancia activa del modelo.
     * @return array Matriz de datos con VLANs y segmentos procesados.
     */
    private function traerContenido($model) {
      
        // CAMBIO: Ahora traemos el catálogo de VLANs en lugar de Departamentos
        $vlans = $model->seleccionaRegistros("vlans", ["id_vlan", "nom_vlan", "descripcion"], "1=1 ORDER BY nom_vlan ASC");
        
        // CAMBIO: Ajustamos el JOIN para relacionar con vlans
        $segmentos_db = $model->seleccionaRegistrosJoin(
            "segmentos s",
            ["LEFT JOIN vlans v ON s.id_vlan = v.id_vlan"],
            ["s.id_seg", "s.ip_ini", "s.ip_lim", "s.id_vlan", "v.nom_vlan"],
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
            'vlans' => $vlans ?: [], // CAMBIO: Exportamos vlans a la vista
            'segmentos' => $segmentos
        ];
    }
    
    // CAMBIO: Renombrado de parámetro $id_dep a $id_vlan
    public function insertarSegmento($id_sub, $id_vlan, $ip_ini, $ip_lim, $reservas) {
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

        // CAMBIO: Guardamos en id_vlan
        $model->agregaRegistro("segmentos", ["id_vlan", "ip_ini", "ip_lim", "estatus"], [$id_vlan, trim($ip_ini), trim($ip_lim), 1]);
        $seg_creado = $model->seleccionaRegistros("segmentos", ["id_seg"], "ip_ini = ? AND ip_lim = ? AND estatus = 1", [trim($ip_ini), trim($ip_lim)]);
        
        if ($seg_creado && count($seg_creado) > 0) {
            $id_seg_real = $seg_creado[0]['id_seg'];
            $model->registrarBitacora($id_sub, 'CREAR', "Creó segmento ID: {$id_seg_real} | Rango: {$ip_ini} a {$ip_lim}");

            if (!empty($ips_a_reservar)) {
                $count_reservas = 0;
                foreach ($ips_a_reservar as $ip_res) {
                    $res = $model->actualizaRegistro("infraestructura_red", ["est"], "id_seg = ? AND ip = ?", ['Reservado', $id_seg_real, $ip_res]);
                    if($res !== false) $count_reservas++;
                }
                $model->registrarBitacora($id_sub, 'MODIFICAR', "Se reservaron $count_reservas IPs al crear el segmento ID: $id_seg_real");
                return ["result" => 1, "msg" => "Segmento creado exitosamente con $count_reservas IPs reservadas."];
            }
            return ["result" => 1, "msg" => "Segmento creado exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al localizar el segmento recién creado."];
    }

    // : Renombrado de parámetro $id_dep a $id_vlan
    public function actualizarSegmento($id_sub, $id_seg, $id_vlan, $ip_ini, $ip_lim, $reservas) {
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
        // CAMBIO: Se actualiza usando $id_vlan
        $model->actualizaRegistro("segmentos", ["id_vlan", "ip_ini", "ip_lim"], "id_seg = ?", [$id_vlan, trim($ip_ini), trim($ip_lim), $id_seg]);

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

        $model->registrarBitacora($id_sub, 'MODIFICAR', "Actualizó rango y reservas del segmento ID: {$id_seg}");
        return ["result" => 1, "msg" => $msg];
    }

    public function eliminarSegmento($id_sub, $id_seg) {
        $model = new MainModel();
        
        // 1. Validación: Evitar borrar si hay dispositivos conectados
        $uso = $model->seleccionaRegistros("infraestructura_red", ["id_disp"], "id_seg = ? AND id_disp IS NOT NULL", [$id_seg]);
        if ($uso && count($uso) > 0) {
            return ["result" => 0, "msg" => "No puede eliminar este segmento porque tiene " . count($uso) . " dispositivo(s) conectado(s)."];
        }

        // 2. Borrado lógico del segmento
        $res = $model->actualizaRegistro("segmentos", ["estatus"], "id_seg = ?", [0, $id_seg]);
        
        if ($res !== false) {
            // 3. Desvincular y liberar las IPs en la tabla infraestructura_red
            // Esto permite que el SP "sp_generar_ips_segmento" pueda reciclar estas IPs en el futuro
            $model->actualizaRegistro(
                "infraestructura_red", 
                ["id_seg", "est"], 
                "id_seg = ?", 
                [null, 'Inactivo', $id_seg]
            );

            $model->registrarBitacora($id_sub, 'BAJA', "Realizó borrado lógico del segmento de red ID: {$id_seg} y liberó sus IPs");
            
            return ["result" => 1, "msg" => "Segmento desactivado y direcciones IP liberadas correctamente."];
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

    // ==========================================
    // GESTIÓN DE VLANS
    // ==========================================

    public function insertarVlan($id_sub, $nom_vlan, $descripcion) {
        $model = new MainModel();

        // 1. Validar unicidad (No repetir nombres)
        $existe = $model->seleccionaRegistros("vlans", ["id_vlan"], "nom_vlan = ?", [$nom_vlan]);
        if ($existe && count($existe) > 0) {
            return ["result" => 0, "msg" => "Ya existe una VLAN registrada con el nombre proporcionado."];
        }

        // 2. Inserción
        $res = $model->agregaRegistro("vlans", ["nom_vlan", "descripcion"], [$nom_vlan, $descripcion]);
        if ($res) {
            $model->registrarBitacora($id_sub, 'CREAR', "Creó nueva VLAN de red: {$nom_vlan}");
            return ["result" => 1, "msg" => "VLAN registrada correctamente."];
        }
        return ["result" => 0, "msg" => "Ocurrió un error al registrar la VLAN en la base de datos."];
    }
    public function actualizarVlan($id_sub, $id_vlan, $nom_vlan, $descripcion) {
        $model = new MainModel();

        // 1. Validar unicidad (Asegurarnos que el nuevo nombre no lo tenga OTRA vlan)
        $existe = $model->seleccionaRegistros("vlans", ["id_vlan"], "nom_vlan = ? AND id_vlan != ?", [$nom_vlan, $id_vlan]);
        if ($existe && count($existe) > 0) {
            return ["result" => 0, "msg" => "El nombre proporcionado ya está siendo usado por otra VLAN."];
        }

        // 2. Actualización
        $res = $model->actualizaRegistro("vlans", ["nom_vlan", "descripcion"], "id_vlan = ?", [$nom_vlan, $descripcion, $id_vlan]);
        
        if ($res !== false) { // usamos !== false porque si no cambian nada, updateRow devuelve 0 pero no error
            $model->registrarBitacora($id_sub, 'MODIFICAR', "Actualizó información de la VLAN ID {$id_vlan}: {$nom_vlan}");
            return ["result" => 1, "msg" => "VLAN actualizada correctamente."];
        }
        
        return ["result" => 0, "msg" => "Ocurrió un error al actualizar la VLAN en la base de datos."];
    }

    public function eliminarVlan($id_sub, $id_vlan) {
        $model = new MainModel();

        // 1. RESTRICCIÓN DE INTEGRIDAD: Validar si la VLAN está asignada a algún segmento
        $dependencias = $model->seleccionaRegistros("segmentos", ["id_seg", "ip_ini", "ip_lim"], "id_vlan = ? AND estatus=1", [$id_vlan]);
        
        if ($dependencias && count($dependencias) > 0) {
            $rango = $dependencias[0]['ip_ini'] . ' - ' . $dependencias[0]['ip_lim'];
            return [
                "result" => 0, 
                "msg" => "No se puede eliminar porque está en uso por " . count($dependencias) . " segmento(s) (Ej. Rango: $rango). Desvincúlelos o elimínelos primero."
            ];
        }

        // Recuperar nombre para bitácora
        $info = $model->seleccionaRegistros("vlans", ["nom_vlan"], "id_vlan = ?", [$id_vlan]);
        $nombre = $info ? $info[0]['nom_vlan'] : "ID: $id_vlan";

        // 2. BORRADO FÍSICO
        $res = $model->borraRegistro("vlans", "id_vlan = ?", [$id_vlan]);
        if ($res) {
            $model->registrarBitacora($id_sub, 'BAJA', "Eliminó la VLAN: {$nombre}");
            return ["result" => 1, "msg" => "VLAN eliminada."];
        }
        return ["result" => 0, "msg" => "Fallo de servidor al intentar eliminar la VLAN."];
    }
}
?>