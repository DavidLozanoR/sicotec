<?php
ini_set("display_errors", E_ALL);
require_once "MainModel.php";


class AdminUsuariosController {

    private $id;
    const VISTA = '_view/adminUsuarios.html.php';
    const JS = 'js/adminUsuarios.js';

    function __construct($id=null){ 
        $this->id = $id;
         if(!isset($_SESSION['usuario']) && $this->id!=1){
            die("Error: Su usuario no tiene acceso a este módulo");
        }
    }

    // Dibuja la vista completa (Tablas de Usuarios y Roles)
    function renderContent(){
        $model = new MainModel();
        
        // 1. DATOS PARA USUARIOS
        //ocultamos rol id=1 administrador
        $roles_disponibles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"], "id_rol != ? AND status = 1", [1]);
        $usuarios = $model->seleccionaRegistrosJoin(
            "Usuarios u",
            ["JOIN Roles r ON u.id_rol = r.id_rol"],
            ["u.id_usuar", "u.nom_usuar", "u.nombres", "u.ape_pa", "u.ape_ma", "u.id_rol", "r.nom_rol"],
            "u.status = ?",
            [1]
        );

        // 2. CONSTRUCCIÓN DEL ÁRBOL PARA EL MODAL DE ROLES
        $permisos_catalogo = $model->seleccionaRegistros("Permisos", ["id_perm", "nom_perm"]);
        $modulos = $model->seleccionaRegistros("Modulos", ["*"], "id_mod != ? ORDER BY orden", [4]);
        $submodulos = $model->seleccionaRegistros("Submodulos", ["*"], "1=1 ORDER BY orden");

        $arbol_modulos = [];
        if ($modulos) {
            foreach ($modulos as $mod) {
                $mod['submodulos'] = [];
                if ($submodulos) {
                    foreach ($submodulos as $sub) {
                        if ($sub['id_mod'] == $mod['id_mod']) {
                            $mod['submodulos'][] = $sub;
                        }
                    }
                }
                $arbol_modulos[] = $mod;
            }
        }

        // 3. DATOS PARA LA TABLA DE ROLES
        $roles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"], "status = 1");//solo activos

        if ($roles) {
            foreach ($roles as $index => $rol) {
                $permisosRol = $model->seleccionaRegistros("Rol_Submodulo_Permiso", ["id_sub", "id_perm"], "id_rol = ?", [$rol['id_rol']]);
                
                $total_permisos = 0;
                $submodulos_unicos = [];
                $permisos_json = []; 

                if ($permisosRol) {
                    $total_permisos = count($permisosRol);
                    foreach ($permisosRol as $pr) {
                        $submodulos_unicos[$pr['id_sub']] = true;
                        if(!isset($permisos_json[$pr['id_sub']])) $permisos_json[$pr['id_sub']] = [];
                        $permisos_json[$pr['id_sub']][] = $pr['id_perm'];
                    }
                }

                $roles[$index]['total_permisos'] = $total_permisos;
                $roles[$index]['total_submodulos'] = count($submodulos_unicos);
                $roles[$index]['permisos_json'] = $permisos_json;

                $usuariosAsignados = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "id_rol = ? AND status = ?", [$rol['id_rol'], 1]);
                $roles[$index]['total_usuarios'] = $usuariosAsignados ? count($usuariosAsignados) : 0;
            }
        }

        include self::VISTA;
    }

    public function renderJS(){ include self::JS; }

    // LÓGICA EXCLUSIVA DE USUARIOS
   
    public function insertaUsuario($id_sub, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol) {
        if ($id_rol == 1) {
            return ["result" => 0, "msg" => "Seguridad: No se permite asignar el rol de Administrador a nuevos usuarios."];
        }

        $model = new MainModel();
        
        $existe = $model->seleccionaRegistros("Usuarios", ["id_usuar", "status"], "nom_usuar = ?", [$nom_usuar]);
        if ($existe && count($existe) > 0) {
            if ($existe[0]['status'] == 1) {
                return ["result" => 0, "msg" => "El usuario '$nom_usuar' ya existe. Si desea cambiar sus accesos, edite su rol."];
            } else {
                return ["result" => 0, "msg" => "El usuario '$nom_usuar' existe pero está dado de baja."];
            }
        }
        
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $res = $model->agregaRegistro("Usuarios", 
            ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol", "status"], 
            [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, 1]
        );
        
        if ($res) {
            $bitacora = $model->registrarBitacora($id_sub, 'INSERT', "Creó al nuevo usuario '{$nom_usuar}' (Rol ID: {$id_rol})");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";
            
            return ["result" => 1, "msg" => "Usuario creado con éxito." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al guardar el usuario"];
    }

    public function actualizaUsuario($id_sub, $id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol) {
        $model = new MainModel();
        
        $usuario_actual = $model->seleccionaRegistros("Usuarios", ["id_rol"], "id_usuar = ?", [$id_usuar]);
        $rol_actual = $usuario_actual ? $usuario_actual[0]['id_rol'] : 0;

        if ($id_rol == 1 && $rol_actual != 1) return ["result" => 0, "msg" => "Seguridad: No puedes ascender a un usuario a Administrador."];
        if ($rol_actual == 1 && $id_rol != 1) return ["result" => 0, "msg" => "Seguridad: El Administrador principal no puede perder su rol."];
        
        $existe = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "nom_usuar = ? AND id_usuar != ?", [$nom_usuar, $id_usuar]);
        if ($existe && count($existe) > 0) return ["result" => 0, "msg" => "El nickname '$nom_usuar' ya está siendo utilizado."];

        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $res = $model->actualizaRegistro("Usuarios", ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol"], "id_usuar=?", [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, $id_usuar]);
        } else {
            $res = $model->actualizaRegistro("Usuarios", ["nom_usuar", "nombres", "ape_pa", "ape_ma", "id_rol"], "id_usuar=?", [$nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol, $id_usuar]);
        }

        if ($res) {
            $bitacora = $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó datos del usuario '{$nom_usuar}'");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";

            if (isset($_SESSION['usuario']['id_usuar']) && $_SESSION['usuario']['id_usuar'] == $id_usuar) {
                $_SESSION['usuario']['nom_usuar'] = $nom_usuar;
                $_SESSION['usuario']['nombres'] = $nombres;
                $_SESSION['usuario']['ape_pa'] = $ape_pa;
                $_SESSION['usuario']['ape_ma'] = $ape_ma;
                return ["result" => 1, "msg" => "Usuario actualizado con éxito." . $msg_extra, "update_current_user" => true, "nuevo_nombre" => $nombres];
            }
            return ["result" => 1, "msg" => "Usuario actualizado con éxito." . $msg_extra];
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
            $bitacora = $model->registrarBitacora($id_sub, 'DELETE', "Dio de baja al usuario '{$nom_baja}'");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";
            return ["result" => 1, "msg" => "Usuario dado de baja exitosamente." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al dar de baja al usuario."];
    }
}
?>