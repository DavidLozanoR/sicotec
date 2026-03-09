<?php
require_once "MainModel.php";


class AdminRolesController {

    // Roles completamente protegidos contra cualquier modificación
    const ROLES_PROTEGIDOS = [1];

    public function insertaRol($nom_rol, $permisos) {
        $model = new MainModel();

        // 1. Validar si ya existe un rol con ese mismo NOMBRE
        $rol_nombre = $model->seleccionaRegistros("Roles", ["id_rol", "status"], "nom_rol = ?", [trim($nom_rol)]);
        if ($rol_nombre && count($rol_nombre) > 0) {
            if ($rol_nombre[0]['status'] == 0) {
                // Caso: Existe pero está dado de baja (Código 2)
                return ["result" => 2, "id_rol_reactivar" => $rol_nombre[0]['id_rol'], "msg" => "Existe un rol inactivo con el nombre '{$nom_rol}'. ¿Desea reactivarlo y actualizar sus permisos?"];
            } else {
                return ["result" => 0, "msg" => "Ya existe un rol activo con ese nombre."];
            }
        }

        // 2. Validar si ya existe un rol con estos mismos PERMISOS
        $rol_similar = $this->buscarRolSimilar($permisos);
        if ($rol_similar) {
            if ($rol_similar['status'] == 0) {
                // Caso: Existe con mismos permisos pero dado de baja (Código 2)
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
            }
        }
        return ["result" => 1, "msg" => "Rol creado correctamente"];
    }

    public function actualizaRol($id_rol, $nom_rol, $permisos) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return ["result" => 0, "msg" => "Error: No se pueden modificar los permisos del rol administrador."];
        }

        $rol_similar = $this->buscarRolSimilar($permisos, $id_rol);
        if ($rol_similar) {
            return ["result" => 0, "msg" => "Esta configuración de permisos es idéntica al rol '{$rol_similar}'. Cambie el rol de los usuarios en lugar de duplicar configuraciones."];
        }

        $model = new MainModel();
        $res = $model->actualizaRegistro("Roles", ["nom_rol"], "id_rol = ?", [trim($nom_rol), $id_rol]);

        if ($res) {
            $model->borraRegistro("Rol_Submodulo_Permiso", "id_rol = ?", [$id_rol]);
            $this->guardarPermisosSubmodulo($id_rol, $permisos, $model);
            $this->sincronizarModulosPadre($id_rol, $permisos, $model);
        }
        return ["result" => 1, "msg" => "Rol actualizado correctamente"];
    }

    public function eliminaRol($id_rol) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return ["result" => 0, "msg" => "ERROR: Este rol está protegido y no puede eliminarse."];
        }
        //comprobar si un usuario Activo esta usando el ROL
        $model = new MainModel();
        $usuarios_activos = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "id_rol = ? AND status = 1", [$id_rol]);
        
        if ($usuarios_activos && count($usuarios_activos) > 0) {
            $total = count($usuarios_activos);
            return ["result" => 0, "msg" => "No se puede dar de baja. Hay {$total} usuario(s) activo(s) usando este rol. Por favor reasigna el Rol a cada usuario que lo este ocupando."];
        }

        // Baja Lógica 
        $res = $model->actualizaRegistro("Roles", ["status"], "id_rol = ?", [0, $id_rol]);

        return $res ? ["result" => 1, "msg" => "Rol dado de baja (inactivo) correctamente."] : ["result" => 0, "msg" => "Error al dar de baja el rol."];
    }
    public function reactivaRol($id_rol, $nom_rol, $permisos) {
        $model = new MainModel();
        // Reactivamos y le ponemos el nuevo nombre que el usuario tecleó
        $res = $model->actualizaRegistro("Roles", ["nom_rol", "status"], "id_rol = ?", [trim($nom_rol), 1, $id_rol]);
        
        if ($res) {
            // Limpiamos los permisos viejos y le ponemos los nuevos del formulario
            return ["result" => 1, "msg" => "Rol reactivado y actualizado con éxito."];
        }
        return ["result" => 0, "msg" => "Error al reactivar el rol."];
    }

    // -------------------------------------------------------
    // MÉTODOS PRIVADOS AUXILIARES
    // -------------------------------------------------------
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
                    return $r; // Devolvemos todo el arreglo, incluyendo el 'status'
                }
            }
        }
        return false;
    }
}
?>