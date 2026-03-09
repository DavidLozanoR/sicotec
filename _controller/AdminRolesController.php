<?php
require_once "MainModel.php";

class AdminRolesController {

    // Roles completamente protegidos contra cualquier modificación
    const ROLES_PROTEGIDOS = [1];

    public function insertaRol($id_sub, $nom_rol, $permisos) {
        $model = new MainModel();

        // 1. Validar si ya existe un rol con ese mismo NOMBRE
        $rol_nombre = $model->seleccionaRegistros("Roles", ["id_rol", "status"], "nom_rol = ?", [trim($nom_rol)]);
        if ($rol_nombre && count($rol_nombre) > 0) {
            if ($rol_nombre[0]['status'] == 0) {
                return ["result" => 2, "id_rol_reactivar" => $rol_nombre[0]['id_rol'], "msg" => "Existe un rol inactivo con el nombre '{$nom_rol}'. ¿Desea reactivarlo y usar sus permisos?"];
            } else {
                return ["result" => 0, "msg" => "Ya existe un rol activo con ese nombre."];
            }
        }

        // 2. Validar si ya existe un rol con estos mismos PERMISOS
        $rol_similar = $this->buscarRolSimilar($permisos);
        if ($rol_similar) {
            if ($rol_similar['status'] == 0) {
                return ["result" => 2, "id_rol_reactivar" => $rol_similar['id_rol'], "msg" => "Existe un rol inactivo llamado '{$rol_similar['nom_rol']}' con exactamente estos mismos accesos. ¿Desea reactivarlo usando su nuevo nombre?"];
            } else {
                return ["result" => 0, "msg" => "Ya existe un rol activo llamado '{$rol_similar['nom_rol']}' con exactamente estos mismos accesos."];
            }
        }

        // 3. Inserción normal si todo es nuevo
        $res = $model->agregaRegistro("Roles", ["nom_rol", "status"], [trim($nom_rol), 1]);
        if ($res) {
            $nuevoRol = $model->seleccionaRegistros("Roles", ["id_rol"], "nom_rol = ?", [trim($nom_rol)]);
            if ($nuevoRol) {
                $id_rol_nuevo = (int)end($nuevoRol)['id_rol'];
                $this->guardarPermisosSubmodulo($id_rol_nuevo, $permisos, $model);
                $this->sincronizarModulosPadre($id_rol_nuevo, $permisos, $model);
                
                // Bitácora
                $bitacora = $model->registrarBitacora($id_sub, 'INSERT', "Creó el nuevo rol '{$nom_rol}' con sus permisos");
                $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";

                return ["result" => 1, "msg" => "Rol creado correctamente." . $msg_extra];
            }
        }
        return ["result" => 0, "msg" => "Error al crear el rol en la base de datos."];
    }

    public function actualizaRol($id_sub, $id_rol, $nom_rol, $permisos) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return ["result" => 0, "msg" => "Error: No se pueden modificar los permisos del rol administrador."];
        }

        $rol_similar = $this->buscarRolSimilar($permisos, $id_rol);
        if ($rol_similar) {
            return ["result" => 0, "msg" => "Esta configuración de permisos es idéntica al rol '{$rol_similar['nom_rol']}'. Cambie el rol de los usuarios en lugar de duplicar configuraciones."];
        }

        $model = new MainModel();
        $res = $model->actualizaRegistro("Roles", ["nom_rol"], "id_rol = ?", [trim($nom_rol), $id_rol]);

        if ($res) {
            $model->borraRegistro("Rol_Submodulo_Permiso", "id_rol = ?", [$id_rol]);
            $this->guardarPermisosSubmodulo($id_rol, $permisos, $model);
            $this->sincronizarModulosPadre($id_rol, $permisos, $model);

            // Bitácora
            $bitacora = $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó los datos y permisos del rol '{$nom_rol}'");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";

            return ["result" => 1, "msg" => "Rol actualizado correctamente." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al actualizar el rol."];
    }

    public function eliminaRol($id_sub, $id_rol) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return ["result" => 0, "msg" => "ERROR: Este rol está protegido y no puede eliminarse."];
        }

        $model = new MainModel();
        
        // Obtenemos el nombre del rol para la bitácora antes de darlo de baja
        $rol_info = $model->seleccionaRegistros("Roles", ["nom_rol"], "id_rol = ?", [$id_rol]);
        $nom_rol_baja = $rol_info ? $rol_info[0]['nom_rol'] : "ID: $id_rol";

        // 1. Verificar si existen usuarios ACTIVOS usando este rol
        $usuarios_activos = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "id_rol = ? AND status = 1", [$id_rol]);
        if ($usuarios_activos && count($usuarios_activos) > 0) {
            $total = count($usuarios_activos);
            return ["result" => 0, "msg" => "No se puede dar de baja. Hay {$total} usuario(s) activo(s) usando este rol. Cámbieles el rol primero."];
        }

        // 2. Baja Lógica 
        $res = $model->actualizaRegistro("Roles", ["status"], "id_rol = ?", [0, $id_rol]);

        if ($res) {
            // Bitácora
            $bitacora = $model->registrarBitacora($id_sub, 'DELETE', "Dio de baja el rol '{$nom_rol_baja}'");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";

            return ["result" => 1, "msg" => "Rol dado de baja (inactivo) correctamente." . $msg_extra];
        }
        
        return ["result" => 0, "msg" => "Error al dar de baja el rol."];
    }

    public function reactivaRol($id_sub, $id_rol, $nom_rol, $permisos) {
        $model = new MainModel();
        // Reactivamos y le ponemos el nuevo nombre que el usuario tecleó
        $res = $model->actualizaRegistro("Roles", ["status"], "id_rol = ?", [1, $id_rol]);
        
        if ($res) {

            // Bitácora
            $bitacora = $model->registrarBitacora($id_sub, 'REACTIVATE', "Reactivó el rol '{$nom_rol}'");
            $msg_extra = ($bitacora !== true) ? "\n\n" . $bitacora : "";

            return ["result" => 1, "msg" => "Rol reactivado con éxito." . $msg_extra];
        }
        return ["result" => 0, "msg" => "Error al reactivar el rol."];
    }

    // MÉTODOS PRIVADOS AUXILIARES 
   
    private function guardarPermisosSubmodulo($id_rol, $permisos, $model) {
        if (empty($permisos) || !is_array($permisos)) return;
        
        foreach ($permisos as $id_sub => $lista_permisos) {
            foreach ($lista_permisos as $id_perm) {
                $model->agregaRegistro("Rol_Submodulo_Permiso", 
                    ["id_rol", "id_sub", "id_perm"], 
                    [(int)$id_rol, (int)$id_sub, (int)$id_perm]
                );
            }
        }
    }

    private function sincronizarModulosPadre($id_rol, $permisos, $model) {
        $model->borraRegistro("Rol_Modulo", "id_rol = ?", [$id_rol]);
        if (empty($permisos) || !is_array($permisos)) return;

        $ids_subs = array_keys($permisos);
        if(count($ids_subs) == 0) return;

        $in_subs = implode(',', array_map('intval', $ids_subs));
        $modulos_padre = $model->seleccionaRegistros("Submodulos", ["DISTINCT id_mod"], "id_sub IN ($in_subs)");

        if ($modulos_padre) {
            foreach ($modulos_padre as $mp) {
                $model->agregaRegistro("Rol_Modulo", ["id_rol", "id_mod"], [$id_rol, $mp['id_mod']]);
            }
        }
    }

    private function buscarRolSimilar($permisos_solicitados, $ignorar_id_rol = null) {
        $model = new MainModel();
        $firma_solicitada = [];
        if (is_array($permisos_solicitados)) {
            foreach($permisos_solicitados as $id_sub => $perms) {
                foreach($perms as $p) { $firma_solicitada[] = $id_sub . "_" . $p; }
            }
        }
        sort($firma_solicitada);
        $str_firma_solicitada = implode("|", $firma_solicitada);

        $todos_roles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol", "status"]);
        if ($todos_roles) {
            foreach ($todos_roles as $r) {
                if ($ignorar_id_rol && $r['id_rol'] == $ignorar_id_rol) continue;

                $perms_rol = $model->seleccionaRegistros("Rol_Submodulo_Permiso", ["id_sub", "id_perm"], "id_rol = ?", [$r['id_rol']]);
                $firma_rol = [];
                if ($perms_rol) {
                    foreach ($perms_rol as $pr) { $firma_rol[] = $pr['id_sub'] . "_" . $pr['id_perm']; }
                }
                sort($firma_rol);
                $str_firma_rol = implode("|", $firma_rol);

                if ($str_firma_solicitada === $str_firma_rol) {
                    return $r;
                }
            }
        }
        return false;
    }
}
?>