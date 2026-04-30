<?php
require_once "MariaDBAux.php";

class LoginModel {

    private $max_intentos = 5;
    private $tiempo_bloqueo = 300; // 5 minutos expresados en segundos

    public function createSesion($usr, $pass) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // 1. Inicializar contadores de validación si no existen
        if (!isset($_SESSION["v_cyc"])) $_SESSION["v_cyc"] = 0;
        if (!isset($_SESSION["lockout_time"])) $_SESSION["lockout_time"] = 0;

        // 2. Verificar si el usuario está bloqueado temporalmente
        if ($_SESSION["v_cyc"] >= $this->max_intentos) {
            $tiempo_transcurrido = time() - $_SESSION["lockout_time"];
            
            if ($tiempo_transcurrido < $this->tiempo_bloqueo) {
                $minutos_restantes = ceil(($this->tiempo_bloqueo - $tiempo_transcurrido) / 60);
                return ["status" => false, "msg" => "Demasiados intentos fallidos. Por favor, espere $minutos_restantes minuto(s) para volver a intentar."];
            } else {
                // Ya pasó el tiempo de castigo, reiniciamos los contadores
                $_SESSION["v_cyc"] = 0;
                $_SESSION["lockout_time"] = 0;
            }
        }

        $bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);

        $tablaPrincipal = "Usuarios U";
        $joins = ["INNER JOIN Roles R ON U.id_rol = R.id_rol"];
        $campos = ["U.id_usuar", "U.nom_usuar", "U.pass_hash", "U.id_rol", "U.nombres", "U.ape_pa", "U.ape_ma", "R.nom_rol", "U.status"];
        $condicion = "U.nom_usuar = ? AND U.status = 1"; 
        $params = [$usr];

        $resultado = $bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);

        // 3. Verificamos si la base de datos encontró al usuario
        if ($resultado && count($resultado) > 0) {
            
            // VALIDACIÓN ESTRICTA: Sensible a mayúsculas y minúsculas (Case-sensitive)
            if ($resultado[0]["nom_usuar"] !== $usr) {
                $this->registrarIntentoFallido();
                return ["status" => false, "msg" => "El usuario '$usr' no existe o se encuentra inactivo."];
            }

            $hashAlmacenado = $resultado[0]["pass_hash"];

            // 4. Verificamos si la contraseña coincide
            if (password_verify($pass, $hashAlmacenado)) {
                
                // LOGIN EXITOSO: Limpiamos historial de fallos
                $_SESSION["v_cyc"] = 0;
                $_SESSION["lockout_time"] = 0;
                
                $_SESSION["token"] = CLAVE_SECRETA; 
                
                $_SESSION["usuario"] = [
                    "id_usuar"  => $resultado[0]["id_usuar"],
                    "nom_usuar" => $resultado[0]["nom_usuar"],
                    "id_rol"    => $resultado[0]["id_rol"],
                    "nom_rol"   => $resultado[0]["nom_rol"],
                    "nombres"   => $resultado[0]["nombres"],
                    "ape_pa"    => $resultado[0]["ape_pa"],
                    "ape_ma"    => $resultado[0]["ape_ma"]
                ];

                $this->buildSessionMenu($bd, $resultado[0]["id_rol"]);

                return ["status" => true, "msg" => "Acceso concedido"];
            } else {
                // EL USUARIO EXISTE, PERO LA CONTRASEÑA ESTÁ MAL
                $this->registrarIntentoFallido();
                return ["status" => false, "msg" => "La contraseña ingresada es incorrecta."];
            }
        }
        
        // EL USUARIO NO EXISTE EN LA BASE DE DATOS
        $this->registrarIntentoFallido();
        return ["status" => false, "msg" => "El usuario '$usr' no existe o se encuentra inactivo."];
    }

    /**
     * Función auxiliar para incrementar el contador y marcar la hora del bloqueo
     */
    private function registrarIntentoFallido() {
        $_SESSION["v_cyc"]++;
        if ($_SESSION["v_cyc"] >= $this->max_intentos) {
            $_SESSION["lockout_time"] = time();
        }
    }

    private function buildSessionMenu($bd, $id_rol) {
        $tablaMod = "Rol_Modulo RM";
        $joinsMod = [
            "INNER JOIN Modulos M ON RM.id_mod = M.id_mod",
            "LEFT JOIN Submodulos S ON S.id_mod = M.id_mod",
            "LEFT JOIN Rol_Submodulo_Permiso RSP ON RSP.id_sub = S.id_sub AND RSP.id_rol = RM.id_rol",
            "LEFT JOIN Permisos P ON P.id_perm = RSP.id_perm"
        ];
        
        // 1. Añadimos P.id_perm a la consulta para poder validarlo con nuestra constante
        $camposMod = [
            "M.id_mod", "M.nom_mod", "M.descripcion", "M.icono",
            "S.id_sub", "S.nom_sub", "S.slug", "S.mostrar_en_menu",
            "P.id_perm", "P.nom_perm" 
        ];
        
        $condicionMod = "RM.id_rol = ? ORDER BY M.orden, S.orden";
        $paramsMod = [$id_rol];

        $registros = $bd->selectJoin($tablaMod, $joinsMod, $camposMod, $condicionMod, $paramsMod);

        $menu = [];
        $permisos_slug = [];

        if ($registros) {
            foreach ($registros as $fila) {
                $id_mod = $fila['id_mod'];
                $slug = $fila['slug'];

                // A. Crear o encontrar el Módulo Principal en el array
                if (!isset($menu[$id_mod])) {
                    $menu[$id_mod] = [
                        'nombre'      => $fila['nom_mod'],
                        'descripcion' => $fila['descripcion'],
                        'icono'       => $fila['icono'],
                        'hijos'       => []
                    ];
                }

                if (!empty($slug)) {
                    // B. Diccionario Rápido de Permisos por Slug
                    if (!isset($permisos_slug[$slug])) {
                        $permisos_slug[$slug] = [];
                    }
                    if (!empty($fila['nom_perm']) && !in_array($fila['nom_perm'], $permisos_slug[$slug])) {
                        $permisos_slug[$slug][] = $fila['nom_perm'];
                    }

                    // C. Agregar Submódulos a su padre (con bandera temporal de acceso)
                    if (!isset($menu[$id_mod]['hijos'][$slug])) {
                        $menu[$id_mod]['hijos'][$slug] = [
                            'nombre'  => $fila['nom_sub'],
                            'mostrar' => $fila['mostrar_en_menu'],
                            'acceso'  => false // Asumimos falso hasta demostrar lo contrario
                        ];
                    }
                    
                    // D. Si la fila actual tiene el permiso de Consultar, le damos luz verde
                    if ($fila['id_perm'] == PERM_CONSULTAR) {
                        $menu[$id_mod]['hijos'][$slug]['acceso'] = true;
                    }
                }
            }
        }

        // ====================================================================
        // FASE DE LIMPIEZA: Podar lo que no tiene permiso
        // ====================================================================
        foreach ($menu as $id_mod => $modulo) {
            foreach ($modulo['hijos'] as $slug => $submod) {
                if ($submod['acceso'] === false) {
                    // Si nunca encontró el permiso de consultar, lo destruimos del menú
                    unset($menu[$id_mod]['hijos'][$slug]);
                } else {
                    // Si sí tiene permiso, solo le borramos la bandera para que no ensucie la sesión
                    unset($menu[$id_mod]['hijos'][$slug]['acceso']);
                }
            }

            // EXTRA: Si después de limpiar submódulos, el módulo padre se quedó vacío, 
            // también lo eliminamos para que no se dibuje un módulo inútil en la vista.
            if (empty($menu[$id_mod]['hijos'])) {
                unset($menu[$id_mod]);
            }
        }

        // Variable 1: Dibuja el NavMenu dinámicamente y ya filtrado
        $_SESSION["menu"] = $menu;            
        
        // Variable 2: Diccionario plano rápido
        $_SESSION["permisos"] = $permisos_slug; 
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        return true;
    }


    /**
     * Registra una acción del usuario en la bitacora del sistema.
     */
    public function registrarBitacora($id_sub, $accion, $detalle) {
        $bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);
        $id_usuar = $_SESSION['usuario']['id_usuar'] ?? null;

        // CORRECCIÓN AQUÍ: Usamos !isset en lugar de empty para permitir el número 0
        if (!$id_usuar || !isset($id_sub)) {
            return "ATENCIÓN: Error de sesión. La acción no pudo ser registrada en Bitácora.";
        }

        date_default_timezone_set('America/Mexico_City');
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        try {
            $exito = $bd->insertRow(
                "Bitacora", 
                ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
                [$id_usuar, $id_sub, $accion, $detalle, $fecha, $hora]
            );
            return $exito ?: "ATENCIÓN: Fallo al registrar entrada en Bitácora.";
        } catch (Exception $e) {
            return "ATENCIÓN: Excepción de base de datos durante el registro de auditoría."; 
        }
    }
}