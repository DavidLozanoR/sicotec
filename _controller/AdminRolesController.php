<?php
require_once __DIR__ . "/../_model/MainModel.php";


class AdminRolesController {

    // Roles completamente protegidos contra cualquier modificación
    const ROLES_PROTEGIDOS = [1];

    public function insertaRol($nom_rol, $permisos) {
        $model = new MainModel();

        $rol_similar = $this->buscarRolSimilar($permisos);
        if ($rol_similar) {
            return ["result" => 0, "msg" => "Ya existe un rol llamado '{$rol_similar}' con exactamente estos mismos accesos. Por favor, asigne ese rol a los usuarios en lugar de crear uno nuevo."];
        }

        $res = $model->agregaRegistro("Roles", ["nom_rol"], [trim($nom_rol)]);
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

        $model = new MainModel();
        $usuarios = $model->seleccionaRegistros("Usuarios", ["id_usuar"], "id_rol = ? AND status = ?", [$id_rol, 1]);
        if ($usuarios && count($usuarios) > 0) {
            return ["result" => 0, "msg" => "No se puede eliminar porque tiene " . count($usuarios) . " usuario(s) activo(s)."];
        }

        $model->borraRegistro("Rol_Submodulo_Permiso", "id_rol = ?", [$id_rol]);
        $model->borraRegistro("Rol_Modulo", "id_rol = ?", [$id_rol]);
        $res = $model->borraRegistro("Roles", "id_rol = ?", [$id_rol]);

        return $res ? ["result" => 1, "msg" => "Rol eliminado correctamente."] : ["result" => 0, "msg" => "Error al eliminar el rol."];
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
                foreach($perms as $p) {
                    $firma_solicitada[] = $id_sub . "_" . $p;
                }
            }
        }
        sort($firma_solicitada);
        $str_firma_solicitada = implode("|", $firma_solicitada);

        $todos_roles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"]);
        if ($todos_roles) {
            foreach ($todos_roles as $r) {
                if ($ignorar_id_rol && $r['id_rol'] == $ignorar_id_rol) continue;

                $perms_rol = $model->seleccionaRegistros("Rol_Submodulo_Permiso", ["id_sub", "id_perm"], "id_rol = ?", [$r['id_rol']]);
                $firma_rol = [];
                if ($perms_rol) {
                    foreach ($perms_rol as $pr) {
                         $firma_rol[] = $pr['id_sub'] . "_" . $pr['id_perm'];
                    }
                }
                sort($firma_rol);
                $str_firma_rol = implode("|", $firma_rol);

                if ($str_firma_solicitada === $str_firma_rol) {
                    return $r['nom_rol'];
                }
            }
        }
        return false;
    }
}
?>