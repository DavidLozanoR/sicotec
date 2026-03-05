<?php
    ini_set("display_errors", E_ALL);

require_once "MainModel.php";

class AdminUsuariosController {

    private $id;

    public $id_user;
    private $pass_hash;
    public $id_rol;
    public $nom_user;
    public $ap_pa;
    public $ap_ma;
    
    const VISTA = '_view/adminUsuarios.html.php';
    const JS = 'js/adminUsuarios.js';

    // Roles completamente protegidos contra cualquier modificación
    const ROLES_PROTEGIDOS = [1];

    function __construct($id=null){ 
        $this->id = $id;

         if(!isset($_SESSION['usuario']) && $this->id!=1){
            die("Error: Su usuario no tiene acceso a este módulo");
        }
    }

    function renderContent(){
        $model = new MainModel();
        
        // --- DATOS PARA USUARIOS ---

        // Catálogos para el Modal de Usuarios
        $roles_disponibles   = $model->seleccionaRegistros("Roles",    ["id_rol", "nom_rol"]);
        $permisos_disponibles = $model->seleccionaRegistros("Permisos", ["id_perm", "nom_perm"]);

        // Lista de usuarios activos con su rol
        $tablaPrincipal = "Usuarios u";
        $joins = ["JOIN Roles r ON u.id_rol = r.id_rol"];
        $campos = [
            "u.id_usuar", 
            "u.nom_usuar", 
            "u.nombres", 
            "u.ape_pa", 
            "u.ape_ma", 
            "u.id_rol",
            "r.nom_rol"
        ];
        $condicion = "u.status = ?";
        $params    = [1];
        
        $usuarios = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
        
        // Permisos por usuario
        if ($usuarios) {
            foreach ($usuarios as $index => $usr) {
                $permisosUsuario = $model->seleccionaRegistrosJoin(
                    "Rol_Permiso rp",
                    ["JOIN Permisos p ON rp.id_perm = p.id_perm"],
                    ["p.id_perm", "p.nom_perm"],
                    "rp.id_rol = ?",
                    [$usr['id_rol']]
                );

                $nombres_permisos = [];
                $ids_permisos     = [];

                if ($permisosUsuario) {
                    foreach ($permisosUsuario as $perm) {
                        $nombres_permisos[] = $perm['nom_perm'];
                        $ids_permisos[]     = $perm['id_perm'];
                    }
                }

                $usuarios[$index]['permisos_lista'] = !empty($nombres_permisos)
                    ? implode(", ", $nombres_permisos)
                    : "Sin permisos asignados";
                $usuarios[$index]['permisos_ids'] = $ids_permisos;
            }
        }

        // --- DATOS PARA ROLES ---

        $roles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"]);

        if ($roles) {
            foreach ($roles as $index => $rol) {
                // Permisos asignados al rol
                $permisosRol = $model->seleccionaRegistrosJoin(
                    "Rol_Permiso rp",
                    ["JOIN Permisos p ON rp.id_perm = p.id_perm"],
                    ["p.id_perm", "p.nom_perm"],
                    "rp.id_rol = ?",
                    [$rol['id_rol']]
                );

                $nombres = [];
                $ids     = [];

                if ($permisosRol) {
                    foreach ($permisosRol as $perm) {
                        $nombres[] = $perm['nom_perm'];
                        $ids[]     = (int)$perm['id_perm'];
                    }
                }

                $roles[$index]['permisos_lista'] = !empty($nombres)
                    ? implode(", ", $nombres)
                    : "Sin permisos asignados";
                $roles[$index]['permisos_ids'] = $ids;

                // Cuántos usuarios activos tienen este rol
                $usuariosAsignados = $model->seleccionaRegistros(
                    "Usuarios",
                    ["id_usuar"],
                    "id_rol = ? AND status = ?",
                    [$rol['id_rol'], 1]
                );
                $roles[$index]['total_usuarios'] = $usuariosAsignados ? count($usuariosAsignados) : 0;
            }
        }

        include self::VISTA;
    }

    public function renderJS(){
        include self::JS;
    }

    // -------------------------------------------------------
    // LÓGICA DE NEGOCIO — USUARIOS
    // -------------------------------------------------------

    public function validaAtributos($nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol, $permisos) {
        if (empty(trim($nom_usuar)) || empty(trim($nombres)) || empty(trim($ape_pa)) || empty(trim($ape_ma))) {
            return false;
        }

        if (empty($id_rol) || !is_numeric($id_rol)) {
            return false;
        }

        if (!is_array($permisos)) {
            return false;
        }

        $model = new MainModel();
        $permisosValidosBD = [];
        $resPermisos = $model->seleccionaRegistros("permisos", ["id_perm"]);
        
        if ($resPermisos) {
            foreach ($resPermisos as $p) {
                $permisosValidosBD[] = (int)$p['id_perm'];
            }
        }

        foreach ($permisos as $perm) {
            if (!is_numeric($perm) || !in_array((int)$perm, $permisosValidosBD, true)) {
                return false;
            }
        }

        return true;
    }

    public function actualizaUsuario($id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos) {
        $model = new MainModel();
        
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol"];
            $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, $id_usuar];
        } else {
            $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "id_rol"];
            $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol, $id_usuar];
        }

        $res = $model->actualizaRegistro("Usuarios", $campos, "id_usuar=?", $params);
        
        if ($res) {
            if ($id_rol == 1 || $id_rol == 2) {
                // Roles protegidos: permisos inmutables
            } else {
                $model->borraRegistro("Rol_Permiso", "id_rol=?", [$id_rol]);
                
                if (!empty($permisos) && is_array($permisos)) {
                    foreach ($permisos as $id_perm) {
                        $id_perm_limpio = (int)$id_perm;
                        $model->agregaRegistro("Rol_Permiso", ["id_rol", "id_perm"], [$id_rol, $id_perm_limpio]);
                    }
                }
            }
        }
        return $res;
    }

    public function insertaUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos) {
        $model = new MainModel();
        
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol", "status"];
        $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, 1];
        
        $res = $model->agregaRegistro("Usuarios", $campos, $params);
        
        if ($res) {
            if ($id_rol == 1 || $id_rol == 2) {
                // Roles protegidos: permisos inmutables
            } else {
                $model->borraRegistro("Rol_Permiso", "id_rol=?", [$id_rol]);
                if (!empty($permisos) && is_array($permisos)) {
                    foreach ($permisos as $id_perm) {
                        $model->agregaRegistro("Rol_Permiso", ["id_rol", "id_perm"], [$id_rol, (int)$id_perm]);
                    }
                }
            }
        }
        return $res;
    }

    public function bajaUsuario($id_usuar) {
        $model = new MainModel();
        return $model->actualizaRegistro("Usuarios", ["status"], "id_usuar=?", [0, $id_usuar]);
    }

    public function obtenerPermisosPorRol($id_rol) {
        $model   = new MainModel();
        $permisos = [];
        
        $res = $model->seleccionaRegistros("Rol_Permiso", ["id_perm"], "id_rol=?", [$id_rol]);
        
        if ($res) {
            foreach ($res as $rp) {
                $permisos[] = (int)$rp['id_perm'];
            }
        }
        
        return $permisos;
    }

    // -------------------------------------------------------
    // LÓGICA DE NEGOCIO — ROLES
    // -------------------------------------------------------

    public function validaAtributosRol($nom_rol, $permisos) {
        if (empty(trim($nom_rol))) {
            return false;
        }

        if (!is_array($permisos)) {
            return false;
        }

        if (!empty($permisos)) {
            $model = new MainModel();
            $permisosValidosBD = [];
            $resPermisos = $model->seleccionaRegistros("Permisos", ["id_perm"]);

            if ($resPermisos) {
                foreach ($resPermisos as $p) {
                    $permisosValidosBD[] = (int)$p['id_perm'];
                }
            }

            foreach ($permisos as $perm) {
                if (!is_numeric($perm) || !in_array((int)$perm, $permisosValidosBD, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function insertaRol($nom_rol, $permisos) {
        $model = new MainModel();

        $res = $model->agregaRegistro("Roles", ["nom_rol"], [trim($nom_rol)]);

        if ($res) {
            $nuevoRol = $model->seleccionaRegistros("Roles", ["id_rol"], "nom_rol = ?", [trim($nom_rol)]);

            if ($nuevoRol && !empty($permisos)) {
                $id_rol_nuevo = (int)end($nuevoRol)['id_rol'];

                foreach ($permisos as $id_perm) {
                    $model->agregaRegistro("Rol_Permiso", ["id_rol", "id_perm"], [$id_rol_nuevo, (int)$id_perm]);
                }
            }
        }

        return $res;
    }

    public function actualizaRol($id_rol, $nom_rol, $permisos) {
        // Rol 1 (Admin) nunca se toca
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return false;
        }

        $model = new MainModel();

        $res = $model->actualizaRegistro("Roles", ["nom_rol"], "id_rol = ?", [trim($nom_rol), $id_rol]);

        if ($res) {
            // Delete & Insert en Rol_Permiso — propaga a todos los usuarios del rol
            $model->borraRegistro("Rol_Permiso", "id_rol = ?", [$id_rol]);

            if (!empty($permisos) && is_array($permisos)) {
                foreach ($permisos as $id_perm) {
                    $model->agregaRegistro("Rol_Permiso", ["id_rol", "id_perm"], [(int)$id_rol, (int)$id_perm]);
                }
            }
        }

        return $res;
    }

    public function eliminaRol($id_rol) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) {
            return ["result" => 0, "msg" => "ERROR: Este rol está protegido y no puede eliminarse."];
        }

        $model = new MainModel();

        $usuariosAsignados = $model->seleccionaRegistros(
            "Usuarios",
            ["id_usuar"],
            "id_rol = ? AND status = ?",
            [(int)$id_rol, 1]
        );

        if ($usuariosAsignados && count($usuariosAsignados) > 0) {
            return [
                "result" => 0,
                "msg"    => "ERROR: No se puede eliminar el rol porque tiene " . count($usuariosAsignados) . " usuario(s) activo(s) asignado(s)."
            ];
        }

        $model->borraRegistro("Rol_Permiso", "id_rol = ?", [(int)$id_rol]);
        $res = $model->borraRegistro("Roles",      "id_rol = ?", [(int)$id_rol]);

        if ($res) {
            return ["result" => 1, "msg" => "Rol eliminado correctamente."];
        } else {
            return ["result" => 0, "msg" => "ERROR: No se pudo eliminar el rol de la base de datos."];
        }
    }
}
?>