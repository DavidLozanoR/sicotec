<?php
ini_set("display_errors", E_ALL);
require_once "MainModel.php";

class AdminUsuariosController {

    private $slug;
    const VISTA = '_view/adminUsuarios.html.php';
    const JS = 'js/adminUsuarios.js';

    function __construct($slug = null){ 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    function renderContent(){
        $model = new MainModel();
        
        $consultaSub = $model->seleccionaRegistros("submodulos", ["id_sub"], "slug = ?", [$this->slug]);
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 7; // Ajusta el ID default si es necesario

        // Roles disponibles para el select de nuevo usuario (ocultamos Administrador)
        $roles_disponibles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"], "id_rol != ? AND status = 1", [1]);
        
        // Usuarios activos
        $usuarios = $model->seleccionaRegistrosJoin(
            "Usuarios u",
            ["JOIN Roles r ON u.id_rol = r.id_rol"],
            ["u.id_usuar", "u.nom_usuar", "u.nombres", "u.ape_pa", "u.ape_ma", "u.id_rol", "r.nom_rol"],
            "u.status = ?",
            [1]
        );

        include self::VISTA;
    }

    public function renderJS(){ 
        if (file_exists(self::JS)) { include self::JS; }
    }

    // ==========================================
    // VALIDACIONES Y LÓGICA DE USUARIOS
    // ==========================================
    public function validarDatosUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $esInsercion = false) {
        $regex_usuario = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ][^\s]*$/';
        if (strlen($nom_usuar) > 50 || !preg_match($regex_usuario, $nom_usuar)) {
            return "El nombre de usuario es inválido.";
        }
        $regex_nombres = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+( [a-zA-ZáéíóúÁÉÍÓÚñÑ]+)*$/';
        if (strlen($nombres) > 100 || !preg_match($regex_nombres, $nombres)) return "El nombre es inválido.";
        if (strlen($ape_pa) > 100 || !preg_match($regex_nombres, $ape_pa)) return "El apellido paterno es inválido.";
        if (!empty($ape_ma) && (strlen($ape_ma) > 100 || !preg_match($regex_nombres, $ape_ma))) return "El apellido materno es inválido.";

        if ($esInsercion && empty($password)) return "La contraseña es obligatoria para usuarios nuevos.";
        if (!empty($password)) {
            if (strlen($password) > 8) return "La contraseña no puede tener más de 8 caracteres.";
            if (strpos($password, ' ') !== false) return "La contraseña no puede contener espacios.";
        }
        return true; 
    }

    public function insertaUsuario($id_sub, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol) {
        if ($id_rol == 1) return ["result" => 0, "msg" => "Seguridad: No se permite asignar el rol de Administrador."];

        $model = new MainModel();
        
        // 1. Lógica Original: Verificación de Nickname (Usuario)
        $existe = $model->seleccionaRegistros("Usuarios", ["id_usuar", "status"], "nom_usuar = ?", [$nom_usuar]);
        
        if ($existe && count($existe) > 0) {
            if ($existe[0]['status'] == 1) return ["result" => 0, "msg" => "El usuario '$nom_usuar' ya existe."];
            else return ["result" => 0, "msg" => "El usuario '$nom_usuar' existe pero está dado de baja."];
        }

        // 2. Nueva Lógica: Verificación de Nombre Completo para Reactivación
        $existeNombre = $model->seleccionaRegistros("Usuarios", ["id_usuar", "status"], "nombres = ? AND ape_pa = ? AND ape_ma = ?", [$nombres, $ape_pa, $ape_ma]);
        if ($existeNombre && count($existeNombre) > 0) {
            if ($existeNombre[0]['status'] == 1) {
                return ["result" => 0, "msg" => "Ya existe un usuario activo registrado con el nombre exacto: $nombres $ape_pa $ape_ma."];
            } else {
                return [
                    "result" => 2, 
                    "msg" => "El usuario '$nombres $ape_pa $ape_ma' ya fue registrado en el pasado pero se encuentra inactivo. ¿Desea reactivar su cuenta y actualizar sus accesos?", 
                    "id_usuar_reactivar" => $existeNombre[0]['id_usuar']
                ];
            }
        }
        
        // 3. Inserción normal si todo está libre
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $res = $model->agregaRegistro("Usuarios", 
            ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol", "status"], 
            [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, 1]
        );
        
        if ($res) {
            $model->registrarBitacora($id_sub, 'INSERT', "Creó al nuevo usuario '{$nom_usuar}'");
            return ["result" => 1, "msg" => "Usuario creado con éxito."];
        }
        return ["result" => 0, "msg" => "Error al guardar el usuario"];
    }

    public function actualizaUsuario($id_sub, $id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol) {
        $model = new MainModel();
        $usuario_actual = $model->seleccionaRegistros("Usuarios", ["id_rol"], "id_usuar = ?", [$id_usuar]);
        $rol_actual = $usuario_actual ? $usuario_actual[0]['id_rol'] : 0;

        if ($id_rol == 1 && $rol_actual != 1) return ["result" => 0, "msg" => "Seguridad: No puedes ascender a un usuario a Administrador."];
        if ($rol_actual == 1 && $id_rol != 1) return ["result" => 0, "msg" => "Seguridad: El Administrador principal no puede perder su rol."];
        
        // 1. Verificación de Nickname (Original)
        $existe = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "nom_usuar = ? AND id_usuar != ?", [$nom_usuar, $id_usuar]);
        if ($existe && count($existe) > 0) return ["result" => 0, "msg" => "El nickname '$nom_usuar' ya está en uso."];

        // 2. Verificación de Nombre Completo
        $existeNombre = $model->seleccionaRegistros("Usuarios", ["id_usuar", "status"], "nombres = ? AND ape_pa = ? AND ape_ma = ? AND id_usuar != ?", [$nombres, $ape_pa, $ape_ma, $id_usuar]);
        if ($existeNombre && count($existeNombre) > 0) {
            if ($existeNombre[0]['status'] == 1) {
                return ["result" => 0, "msg" => "Ya existe otro usuario activo registrado con el nombre exacto: $nombres $ape_pa $ape_ma."];
            } else {
                return [
                    "result" => 2, 
                    "msg" => "Ya existe un registro inactivo coincidente con el nombre '$nombres $ape_pa $ape_ma'. ¿Desea reactivarlo y usar esa cuenta en su lugar?", 
                    "id_usuar_reactivar" => $existeNombre[0]['id_usuar']
                ];
            }
        }

        // 3. Actualización normal
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $res = $model->actualizaRegistro("Usuarios", ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol"], "id_usuar=?", [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, $id_usuar]);
        } else {
            $res = $model->actualizaRegistro("Usuarios", ["nom_usuar", "nombres", "ape_pa", "ape_ma", "id_rol"], "id_usuar=?", [$nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol, $id_usuar]);
        }

        if ($res) {
            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó datos del usuario '{$nom_usuar}'");
            if (isset($_SESSION['usuario']['id_usuar']) && $_SESSION['usuario']['id_usuar'] == $id_usuar) {
                $_SESSION['usuario']['nom_usuar'] = $nom_usuar;
                $_SESSION['usuario']['nombres'] = $nombres;
                return ["result" => 1, "msg" => "Usuario actualizado con éxito.", "update_current_user" => true, "nuevo_nombre" => $nombres];
            }
            return ["result" => 1, "msg" => "Usuario actualizado con éxito."];
        }
        return ["result" => 0, "msg" => "Error al actualizar el usuario."];
    }

    public function bajaUsuario($id_sub, $id_usuar) {
        $model = new MainModel();
        $usuario = $model->seleccionaRegistros("Usuarios", ["id_rol", "nom_usuar"], "id_usuar = ?", [$id_usuar]);
        if ($usuario && $usuario[0]['id_rol'] == 1) return ["result" => 0, "msg" => "Seguridad: No se puede dar de baja al Administrador."];

        $nom_baja = $usuario ? $usuario[0]['nom_usuar'] : 'Desconocido';
        $res = $model->actualizaRegistro("Usuarios", ["status"], "id_usuar=?", [0, $id_usuar]);
        
        if ($res) {
            $model->registrarBitacora($id_sub, 'DELETE', "Dio de baja al usuario '{$nom_baja}'");
            return ["result" => 1, "msg" => "Usuario dado de baja exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al dar de baja al usuario."];
    }

    // ==========================================
    // NUEVO MÉTODO PARA REACTIVACIÓN DE USUARIOS
    // ==========================================
    public function reactivarUsuario($id_sub, $id_usuar, $id_rol, $nuevo_nickname, $password_nueva) {
        $model = new MainModel();

        // Si se va a reactivar, debemos asegurar que el nuevo nickname no choque con otro usuario distinto
        $existe = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "nom_usuar = ? AND id_usuar != ?", [$nuevo_nickname, $id_usuar]);
        if ($existe && count($existe) > 0) return ["result" => 0, "msg" => "El nickname '$nuevo_nickname' ya está en uso por otro usuario."];

        $campos_actualizar = ["status", "id_rol", "nom_usuar"];
        $valores_actualizar = [1, $id_rol, $nuevo_nickname];

        // Si le asignaron una contraseña nueva al reactivarlo
        if (!empty($password_nueva)) {
            $campos_actualizar[] = "pass_hash";
            $valores_actualizar[] = password_hash($password_nueva, PASSWORD_DEFAULT);
        }

        $valores_actualizar[] = $id_usuar; // Al final para el WHERE

        $res = $model->actualizaRegistro("Usuarios", $campos_actualizar, "id_usuar = ?", $valores_actualizar);
        
        if ($res) {
            $model->registrarBitacora($id_sub, 'REACTIVATE', "Reactivó al usuario '{$nuevo_nickname}' y actualizó sus accesos");
            return ["result" => 1, "msg" => "Usuario reactivado exitosamente."];
        }
        return ["result" => 0, "msg" => "Error al reactivar el usuario."];
    }
}
?>