<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "AdminUsuariosController.php";

// Validación de seguridad: Verifica que exista una sesión activa antes de procesar la petición
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

/** @var AdminUsuariosController Instancia del controlador de administración de usuarios */
$controller = new AdminUsuariosController();

/** @var string Cadena de control para enrutamiento de la petición AJAX */
$peticion = $_POST['peticion'] ?? '';

/** @var string|int ID del submódulo origen para registro en bitácora y validación de permisos */
$id_sub = $_POST['id_sub'] ?? '';

// Enrutador principal de peticiones
switch ($peticion) {
    
    /**
     * Caso: INSERT_USUARIO
     * Captura, sanitiza (eliminación de espacios redundantes) y valida los datos para registrar un nuevo usuario.
     */
    case 'INSERT_USUARIO':
        $nom_usuar = trim($_POST['nom_usuar'] ?? '');
        $nombres = preg_replace('/\s+/', ' ', trim($_POST['nombres'] ?? ''));
        $ape_pa = preg_replace('/\s+/', ' ', trim($_POST['ape_pa'] ?? ''));
        $ape_ma = preg_replace('/\s+/', ' ', trim($_POST['ape_ma'] ?? '')); 
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';

        if (empty($id_sub) || empty($nom_usuar) || empty($nombres) || empty($ape_pa) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para crear el usuario."]);
            exit;
        }

        // Llamamos a la validación desde el controlador (true indica que es una inserción)
        $validacion = $controller->validarDatosUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, true);
        if ($validacion !== true) {
            echo json_encode(["result" => 0, "msg" => $validacion]);
            exit;
        }

        echo json_encode($controller->insertaUsuario($id_sub, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol));
        break;

    /**
     * Caso: UPDATE_USUARIO
     * Actualiza los datos de un usuario existente. Contiene lógica de protección para contraseñas de Administradores.
     */
    case 'UPDATE_USUARIO':
        $id_usuar = $_POST['id_usuar'] ?? '';
        $nom_usuar = trim($_POST['nom_usuar'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $ape_pa = trim($_POST['ape_pa'] ?? '');
        $ape_ma = trim($_POST['ape_ma'] ?? '');
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';
        
        // Si es el Administrador (rol 1), vaciamos la contraseña automáticamente
        // para asegurar que el controlador asuma que NO hubo intento de cambio.
        if ($id_rol == 1) {
            $password = ''; 
        }

        if (empty($id_sub) || empty($id_usuar) || empty($nom_usuar) || empty($nombres) || empty($ape_pa) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para actualizar."]);
            exit;
        }

        // Llamamos a la validación desde el controlador (false indica que es actualización)
        $validacion = $controller->validarDatosUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, false);
        if ($validacion !== true) {
            echo json_encode(["result" => 0, "msg" => $validacion]);
            exit;
        }

        echo json_encode($controller->actualizaUsuario($id_sub, $id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol));
        break;

    /**
     * Caso: BAJA_USUARIO
     * Procesa la baja lógica (desactivación) de un usuario en el sistema.
     */
    case 'BAJA_USUARIO':
        $id_usuar = $_POST['id_usuar'] ?? '';
        if (empty($id_sub) || empty($id_usuar)) {
            echo json_encode(["result" => 0, "msg" => "ID de usuario o módulo no válido."]);
            exit;
        }
        echo json_encode($controller->bajaUsuario($id_sub, $id_usuar));
        break;

    /**
     * Caso: REACTIVATE_USER
     * Revierte la baja lógica de un usuario, permitiendo la actualización simultánea de sus credenciales y rol.
     */
    case 'REACTIVATE_USER':
        $id_usuar = $_POST['id_usuar'] ?? '';
        $nom_usuar = trim($_POST['nom_usuar'] ?? ''); // El nickname que escribió en el formulario
        $password = $_POST['password'] ?? ''; // Por si quiere cambiarle la contraseña al reactivarlo
        $id_rol = $_POST['id_rol'] ?? ''; // El nuevo rol asignado

        if (empty($id_sub) || empty($id_usuar) || empty($nom_usuar) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para reactivar al usuario."]);
            exit;
        }

        echo json_encode($controller->reactivarUsuario($id_sub, $id_usuar, $id_rol, $nom_usuar, $password));
        break;

    default:
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}
?>