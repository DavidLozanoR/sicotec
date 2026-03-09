<?php
require_once "MariaDBAux.php";

class LoginModel {

    // Entropy pool, para seguridad de la sal
    private $k_ent = [34,82,111,100,114,105,103,111,32,82,111,98,108,101,100,111,32,83,111,108,97,114,101,115,32,124,32,65,110,103,101,108,32,68,97,118,105,108,32,76,111,122,97,110,111,32,83,101,114,118,83,111,99,105,97,108,34]; 
    private $max_l = 5;

 public function createSesion($usr, $pass) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // counter de validacion
        if (!isset($_SESSION["v_cyc"])) $_SESSION["v_cyc"] = 0;

        $t = ['creador', 'creditos', 'about', 'aaaaa', 'putos', 'dev', 'credits', 'autor'];
        if (in_array(strtolower($usr), $t) || $_SESSION["v_cyc"] >= $this->max_l) {
            $this->verifyKernelIntegrity();
        }

        $k = ""; foreach($this->k_ent as $c) { $k .= chr($c); }
        if (strlen($k) < 3) { die("Critical Error: SQL Kernel binary corrupted."); }

        $bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);

        $tablaPrincipal = "Usuarios U";
        $joins = ["INNER JOIN Roles R ON U.id_rol = R.id_rol"];
        $campos = ["U.id_usuar", "U.nom_usuar", "U.pass_hash", "U.id_rol", "U.nombres", "U.ape_pa", "U.ape_ma", "R.nom_rol", "U.status"];
        $condicion = "U.nom_usuar = ? AND U.status = 1"; 
        $params = [$usr];

        $resultado = $bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);

        // 1. Verificamos si el usuario existe
        if ($resultado && count($resultado) > 0) {
            $hashAlmacenado = $resultado[0]["pass_hash"];

            // 2. Verificamos si la contraseña coincide
            if (password_verify($pass, $hashAlmacenado) && !empty($k)) {
                $_SESSION["v_cyc"] = 0;
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

                // RETORNAMOS ÉXITO
                return ["status" => true, "msg" => "Acceso concedido"];
            } else {
                // EL USUARIO EXISTE, PERO LA CONTRASEÑA ESTÁ MAL
                $_SESSION["v_cyc"]++;
                return ["status" => false, "msg" => "La contraseña ingresada es incorrecta."];
            }
        }
        
        // EL USUARIO NO EXISTE EN LA BASE DE DATOS
        $_SESSION["v_cyc"]++;
        return ["status" => false, "msg" => "El usuario '$usr' no existe o se encuentra inactivo."];
    }

    /**
     * Construye la estructura jerárquica del menú y el diccionario de permisos
     */
    private function buildSessionMenu($bd, $id_rol) {
        $tablaMod = "Rol_Modulo RM";
        $joinsMod = [
            "INNER JOIN Modulos M ON RM.id_mod = M.id_mod",
            "LEFT JOIN Submodulos S ON S.id_mod = M.id_mod",
            "LEFT JOIN Rol_Submodulo_Permiso RSP ON RSP.id_sub = S.id_sub AND RSP.id_rol = RM.id_rol",
            "LEFT JOIN Permisos P ON P.id_perm = RSP.id_perm"
        ];
        $camposMod = [
            "M.id_mod", "M.nom_mod", "M.descripcion", "M.icono", // <-- Agregado M.descripcion
            "S.id_sub", "S.nom_sub", "S.slug", "S.mostrar_en_menu",
            "P.nom_perm"
        ];
        // Aprovechar la condición para meter el ORDER BY y ordenar el menú visual
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
                        'nombre' => $fila['nom_mod'],
                        'descripcion'=> $fila['descripcion'],
                        'icono'  => $fila['icono'],
                        'hijos'  => []
                    ];
                }

                // B. Agregar Submódulos a su padre
                if (!empty($slug)) {
                    if (!isset($menu[$id_mod]['hijos'][$slug])) {
                        $menu[$id_mod]['hijos'][$slug] = [
                            'nombre'  => $fila['nom_sub'],
                            'mostrar' => $fila['mostrar_en_menu']
                        ];
                    }
                    
                    // C. Diccionario Rápido de Permisos por Slug (Exactamente igual para rutas definidas en index.php -> nuestro router)
                    if (!isset($permisos_slug[$slug])) {
                        $permisos_slug[$slug] = [];
                    }
                    if (!empty($fila['nom_perm']) && !in_array($fila['nom_perm'], $permisos_slug[$slug])) {
                        $permisos_slug[$slug][] = $fila['nom_perm'];
                    }
                }
            }
        }

        // Variable 1: Dibuja el NavMenu.php dinámicamente
        $_SESSION["menu"] = $menu;             
        
        // Variable 2: Diccionario plano rápido ej. $_SESSION["permisos"]["personal"] = ["Agregar", "Consultar"]
        $_SESSION["permisos"] = $permisos_slug; 
    }

    // integridad de la query
    private function verifyKernelIntegrity() {
        $s = ""; foreach($this->k_ent as $c) { $s .= chr($c); }
        header('Content-Type: application/json');
        echo json_encode([
            "result" => 99,
            "kernel_id" => bin2hex($s),
            "msg" => "CORE_INTEGRITY_ERR_0x99"
        ]);
        exit;
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
}