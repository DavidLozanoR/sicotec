<?php
require_once "MainModel.php";

class AdminRolesController {

    private $slug;
    const VISTA = '_view/adminRoles.html.php';
    const JS = 'js/adminRoles.js';
    const ROLES_PROTEGIDOS = [1];

    function __construct($slug = null){ 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    public function renderContent(){
        $model = new MainModel();
         $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 3; 

        if (!$this->verificarPermiso($id_sub,PERM_CONSULTAR )) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a Roles.</p>
                    </div>
                  </div>';
            return; 
        }

        $permisos_catalogo = $model->seleccionaRegistros("permisos", ["id_perm", "nom_perm"]);
        
        $modulos = $model->seleccionaRegistros("modulos", ["*"], "id_mod != 4 AND nom_mod != 'Administración de Roles' ORDER BY orden");
        $submodulos = $model->seleccionaRegistros("submodulos", ["*"], "slug NOT IN ('usuarios', 'roles') ORDER BY orden");

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
                if (count($mod['submodulos']) > 0) {
                    $arbol_modulos[] = $mod;
                }
            }
        }

        $roles = $model->seleccionaRegistros("roles", ["id_rol", "nom_rol"], "status = 1");

        if ($roles) {
            foreach ($roles as $index => $rol) {
                $permisosRol = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_sub", "id_perm"], "id_rol = ?", [$rol['id_rol']]);
                
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

                $usuariosAsignados = $model->seleccionaRegistros("usuarios", ["id_usuar"], "id_rol = ? AND status = ?", [$rol['id_rol'], 1]);
                $roles[$index]['total_usuarios'] = $usuariosAsignados ? count($usuariosAsignados) : 0;
            }
        }

        include self::VISTA;
    }

    public function renderJS(){ 
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'];
        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    public function validarRol($nom_rol, $permisos) {
        if (strlen($nom_rol) > 25) return "El nombre del rol supera los 25 caracteres.";
        if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/', $nom_rol)) return "El nombre del rol contiene caracteres no permitidos.";
        
        // NUEVA VALIDACIÓN DE NEGOCIO: Obligar a tener permisos
        if (empty($permisos) || !is_array($permisos)) {
            return "Debe seleccionar al menos el permiso de 'Consultar' en algún submódulo.";
        }

        $model = new MainModel();
        $permisos_db = $model->seleccionaRegistros("permisos", ["id_perm"]);
        
        $permisos_validos = [];
        if ($permisos_db) {
            foreach ($permisos_db as $p) { $permisos_validos[] = (int)$p['id_perm']; }
        }

        $tiene_consultar = false;

        foreach ($permisos as $id_sub => $lista_permisos) {
            if (!is_numeric($id_sub) || (int)$id_sub <= 0) return "ALERTA DE SEGURIDAD: Origen de módulos alterado.";
            if (!is_array($lista_permisos)) return "Formato de permisos inválido.";
            
            foreach ($lista_permisos as $id_perm) {
                if (!is_numeric($id_perm) || !in_array((int)$id_perm, $permisos_validos)) {
                    return "ALERTA DE SEGURIDAD: Permiso no reconocido.";
                }
                
                // Si el permiso es Consultar (ID 4 en tu base de datos)
                if ((int)$id_perm === 4) {
                    $tiene_consultar = true;
                }
            }
        }

        // Si recorrió todos los permisos y ninguno fue Consultar (4)
        if (!$tiene_consultar) {
            return "Debe asignar al menos el permiso de 'Consultar' en algún submódulo para que el rol sea funcional.";
        }

        return true; 
    }

    public function insertaRol($id_sub, $nom_rol, $permisos) {
        // Blindaje: Validamos antes de tocar la BD
        $validacion = $this->validarRol($nom_rol, $permisos);
        if ($validacion !== true) return ["result" => 0, "msg" => $validacion];

        $model = new MainModel();
        $rol_nombre = $model->seleccionaRegistros("roles", ["id_rol", "status"], "nom_rol = ?", [trim($nom_rol)]);
        if ($rol_nombre && count($rol_nombre) > 0) {
            if ($rol_nombre[0]['status'] == 0) return ["result" => 2, "id_rol_reactivar" => $rol_nombre[0]['id_rol'], "msg" => "Existe un rol inactivo con el nombre '{$nom_rol}'. ¿Desea reactivarlo y usar sus permisos?"];
            else return ["result" => 0, "msg" => "Ya existe un rol activo con ese nombre."];
        }

        $rol_similar = $this->buscarRolSimilar($permisos);
        if ($rol_similar) {
            if ($rol_similar['status'] == 0) return ["result" => 2, "id_rol_reactivar" => $rol_similar['id_rol'], "msg" => "Existe un rol inactivo llamado '{$rol_similar['nom_rol']}' con exactamente estos accesos. ¿Desea reactivarlo?"];
            else return ["result" => 0, "msg" => "Ya existe un rol activo llamado '{$rol_similar['nom_rol']}' con exactamente estos accesos."];
        }

        $res = $model->agregaRegistro("roles", ["nom_rol", "status"], [trim($nom_rol), 1]);
        if ($res) {
            $nuevoRol = $model->seleccionaRegistros("roles", ["id_rol"], "nom_rol = ?", [trim($nom_rol)]);
            if ($nuevoRol) {
                $id_rol_nuevo = (int)end($nuevoRol)['id_rol'];
                $this->guardarPermisosSubmodulo($id_rol_nuevo, $permisos, $model);
                $this->sincronizarModulosPadre($id_rol_nuevo, $permisos, $model);
                
                $model->registrarBitacora($id_sub, 'INSERT', "Creó el nuevo rol '{$nom_rol}' con sus permisos");
                return ["result" => 1, "msg" => "Rol creado correctamente."];
            }
        }
        return ["result" => 0, "msg" => "Error al crear el rol en la base de datos."];
    }

    public function actualizaRol($id_sub, $id_rol, $nom_rol, $permisos) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) return ["result" => 0, "msg" => "Error: No se pueden modificar los permisos del rol administrador."];

        // Blindaje: Validamos antes de tocar la BD
        $validacion = $this->validarRol($nom_rol, $permisos);
        if ($validacion !== true) return ["result" => 0, "msg" => $validacion];

        $model = new MainModel();

        $rol_nombre = $model->seleccionaRegistros("roles", ["id_rol", "status"], "nom_rol = ? AND id_rol != ?", [trim($nom_rol), $id_rol]);
        if ($rol_nombre && count($rol_nombre) > 0) {
            if ($rol_nombre[0]['status'] == 0) {
                return ["result" => 2, "id_rol_reactivar" => $rol_nombre[0]['id_rol'], "msg" => "Existe un rol inactivo con el nombre '{$nom_rol}'. ¿Desea reactivarlo y usar sus permisos?"];
            } else {
                return ["result" => 0, "msg" => "Ya existe otro rol activo con ese nombre. Por favor elija uno distinto."];
            }
        }

        $rol_similar = $this->buscarRolSimilar($permisos, $id_rol);
        if ($rol_similar) return ["result" => 0, "msg" => "Esta configuración de permisos es idéntica al rol '{$rol_similar['nom_rol']}'. Cambie el rol de los usuarios en lugar de duplicar configuraciones."];

        $res = $model->actualizaRegistro("roles", ["nom_rol"], "id_rol = ?", [trim($nom_rol), $id_rol]);

        if ($res !== false) {
            $model->borraRegistro("rol_submodulo_permiso", "id_rol = ?", [$id_rol]);
            $this->guardarPermisosSubmodulo($id_rol, $permisos, $model);
            $this->sincronizarModulosPadre($id_rol, $permisos, $model);

            $model->registrarBitacora($id_sub, 'UPDATE', "Actualizó los datos y permisos del rol '{$nom_rol}'");
            return ["result" => 1, "msg" => "Rol actualizado correctamente."];
        }
        return ["result" => 0, "msg" => "Error al actualizar el rol."];
    }

    public function eliminaRol($id_sub, $id_rol) {
        if (in_array((int)$id_rol, self::ROLES_PROTEGIDOS)) return ["result" => 0, "msg" => "ERROR: Este rol está protegido."];

        $model = new MainModel();
        $rol_info = $model->seleccionaRegistros("roles", ["nom_rol"], "id_rol = ?", [$id_rol]);
        $nom_rol_baja = $rol_info ? $rol_info[0]['nom_rol'] : "ID: $id_rol";

        $usuarios_activos = $model->seleccionaRegistros("usuarios", ["id_usuar"], "id_rol = ? AND status = 1", [$id_rol]);
        if ($usuarios_activos && count($usuarios_activos) > 0) {
            $total = count($usuarios_activos);
            return ["result" => 0, "msg" => "No se puede dar de baja. Hay {$total} usuario(s) activo(s) usando este rol. Cámbieles el rol primero."];
        }

        $res = $model->actualizaRegistro("roles", ["status"], "id_rol = ?", [0, $id_rol]);
        if ($res !== false) {
            $model->registrarBitacora($id_sub, 'DELETE', "Dio de baja el rol '{$nom_rol_baja}'");
            return ["result" => 1, "msg" => "Rol dado de baja correctamente."];
        }
        return ["result" => 0, "msg" => "Error al dar de baja el rol."];
    }

    public function reactivaRol($id_sub, $id_rol, $nom_rol, $permisos) {
        $model = new MainModel();
        $res = $model->actualizaRegistro("roles", ["status"], "id_rol = ?", [1, $id_rol]);
        if ($res !== false) {
            $model->registrarBitacora($id_sub, 'REACTIVATE', "Reactivó el rol '{$nom_rol}'");
            return ["result" => 1, "msg" => "Rol reactivado con éxito."];
        }
        return ["result" => 0, "msg" => "Error al reactivar el rol."];
    }

    // AUXILIARES
    private function guardarPermisosSubmodulo($id_rol, $permisos, $model) {
        if (empty($permisos) || !is_array($permisos)) return;
        foreach ($permisos as $id_sub => $lista_permisos) {
            foreach ($lista_permisos as $id_perm) {
                $model->agregaRegistro("rol_submodulo_permiso", ["id_rol", "id_sub", "id_perm"], [(int)$id_rol, (int)$id_sub, (int)$id_perm]);
            }
        }
    }

    private function sincronizarModulosPadre($id_rol, $permisos, $model) {
        $model->borraRegistro("rol_modulo", "id_rol = ?", [$id_rol]);
        if (empty($permisos) || !is_array($permisos)) return;

        $ids_subs = array_keys($permisos);
        if(count($ids_subs) == 0) return;

        $in_subs = implode(',', array_map('intval', $ids_subs));
        $modulos_padre = $model->seleccionaRegistros("submodulos", ["DISTINCT id_mod"], "id_sub IN ($in_subs)");

        if ($modulos_padre) {
            foreach ($modulos_padre as $mp) {
                $model->agregaRegistro("rol_modulo", ["id_rol", "id_mod"], [$id_rol, $mp['id_mod']]);
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

        $todos_roles = $model->seleccionaRegistros("roles", ["id_rol", "nom_rol", "status"]);
        if ($todos_roles) {
            foreach ($todos_roles as $r) {
                if ($ignorar_id_rol && $r['id_rol'] == $ignorar_id_rol) continue;

                $perms_rol = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_sub", "id_perm"], "id_rol = ?", [$r['id_rol']]);
                $firma_rol = [];
                if ($perms_rol) {
                    foreach ($perms_rol as $pr) { $firma_rol[] = $pr['id_sub'] . "_" . $pr['id_perm']; }
                }
                sort($firma_rol);
                $str_firma_rol = implode("|", $firma_rol);

                if ($str_firma_solicitada === $str_firma_rol) return $r;
            }
        }
        return false;
    }
}
?>