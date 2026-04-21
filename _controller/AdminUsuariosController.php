<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

ini_set("display_errors", E_ALL);
require_once "MainModel.php";

/**
 * Controlador para la gestión de usuarios administrativos del sistema.
 * Maneja el renderizado de la interfaz, validación de privilegios RBAC 
 * y la integridad de datos para cuentas de acceso.
 */
class AdminUsuariosController {

    /** @var string|null Identificador del submódulo para la navegación */
    private $slug;

    /** @var string Ruta del archivo de la vista asociada */
    const VISTA = '_view/adminUsuarios.html.php';

    /** @var string Ruta del archivo JavaScript asociado */
    const JS = 'js/adminUsuarios.js';

    /**
     * Constructor de la clase.
     * Inicializa la sesión y verifica la identidad del usuario antes de permitir el acceso.
     * * @param string|null $slug Slug dinámico para identificar el contexto del submódulo.
     */
    function __construct($slug = null){ 
        if(session_status() === PHP_SESSION_NONE) { session_start(); }
        if(!isset($_SESSION['usuario'])) { die("Error: Su sesión ha expirado."); }
        $this->slug = $slug;
    }

    /**
     * Procesa y genera el contenido principal de la gestión de usuarios.
     * Valida permisos de consulta, filtra roles protegidos y extrae la lista de usuarios activos.
     * * @return void Renderiza la vista o muestra mensaje de denegación.
     */
    function renderContent(){
        $model = new MainModel();
        
        // Obtención del ID de submódulo basado en el slug actual
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug);
        if (!$id_sub) $id_sub = 7; // Fallback al ID de submódulo Usuarios

        // Verificación de privilegios de consulta mediante RBAC
        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para acceder a Usuarios.</p>
                    </div>
                  </div>';
            return; 
        }

        // Roles disponibles para asignación (Excluye el rol con ID 1 - Administrador raíz)
        $roles_disponibles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"], "id_rol != ? AND status = 1", [1]);
        
        // Extracción de usuarios con JOIN para obtener el nombre del rol asociado
        $usuarios = $model->seleccionaRegistrosJoin(
            "Usuarios u",
            ["JOIN Roles r ON u.id_rol = r.id_rol"],
            ["u.id_usuar", "u.nom_usuar", "u.nombres", "u.ape_pa", "u.ape_ma", "u.id_rol", "r.nom_rol"],
            "u.status = ?",
            [1]
        );

        include self::VISTA;
    }

    /**
     * Carga el script frontend correspondiente al controlador.
     * @return void
     */
    public function renderJS(){ 
        if (file_exists(self::JS)) { include self::JS; }
    }

    /**
     * Verifica si el usuario en sesión posee un permiso específico en un submódulo dado.
     * * @param int $id_sub ID del submódulo a validar.
     * @param int $id_permiso ID del tipo de permiso solicitado.
     * @return bool True si el permiso está concedido, False en caso contrario.
     */
    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'];
        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    /**
     * Valida la integridad y formato de los campos de usuario mediante expresiones regulares.
     * Verifica longitudes, ausencia de caracteres especiales y reglas de contraseñas.
     * * @param string $nom_usuar Nickname/Nombre de acceso.
     * @param string $nombres Nombre(s) de la persona.
     * @param string $ape_pa Apellido paterno.
     * @param string $ape_ma Apellido materno.
     * @param string $password Cadena de contraseña.
     * @param bool $esInsercion Flag para determinar si la contraseña es obligatoria.
     * @return string|bool Retorna el mensaje de error o True si la validación es exitosa.
     */
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

    /**
     * Procesa la inserción de un nuevo usuario en la base de datos.
     * Bloquea la asignación manual del rol Administrador por motivos de seguridad.
     * * @param int $id_sub Contexto del submódulo.
     * @param string $nom_usuar Nickname.
     * @param string $nombres Nombre real.
     * @param string $ape_pa Apellido Paterno.
     * @param string $ape_ma Apellido Materno.
     * @param string $password Contraseña (será procesada por el modelo).
     * @param int $id_rol Rol a asignar.
     * @return array Resultado de la operación.
     */
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

        //verifica que sea mas de 3 caracter de largo el nombre o apellidos
        if (strlen($nom_usuar) < 3 || strlen($ape_ma) < 3 || strlen($ape_pa) < 3 ) return ["result" => 0, "msg" => "Seguridad: El nombre o apellidos deben de tener al menos 3 letras."];  

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