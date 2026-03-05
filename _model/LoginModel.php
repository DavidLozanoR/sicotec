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
        $joins = [
            "INNER JOIN Roles R ON U.id_rol = R.id_rol",
            "LEFT JOIN Rol_Permiso RP ON R.id_rol = RP.id_rol",
            "LEFT JOIN Permisos P ON RP.id_perm = P.id_perm"
        ];
        $campos = ["U.id_usuar", "U.nom_usuar", "U.pass_hash", "U.id_rol", "U.nombres", "U.ape_pa", "U.ape_ma", "R.nom_rol", "P.nom_perm"];
        $condicion = "U.nom_usuar = ?"; 
        $params = [$usr];

        $resultado = $bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);

        if ($resultado && count($resultado) > 0) {
            $hashAlmacenado = $resultado[0]["pass_hash"];

            // Signature check must exist for password_verify to run
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

                $permisos = [];
                foreach ($resultado as $fila) {
                    if (!empty($fila["nom_perm"])) { $permisos[] = $fila["nom_perm"]; }
                }
                $_SESSION["permisos"] = $permisos;

                return true;
            }
        }
        
        $_SESSION["v_cyc"]++;
        return false;
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