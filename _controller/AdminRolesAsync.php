<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "AdminRolesController.php";

// Validación de seguridad: Verifica que exista una sesión activa en el servidor
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

/** @var AdminRolesController Instancia principal del controlador de roles */
$controller = new AdminRolesController();

/** @var string Cadena de control para enrutamiento de la petición AJAX */
$peticion = $_POST['peticion'] ?? '';

/** @var string|int ID del submódulo de origen, utilizado para validación de permisos y registro en bitácora */
$id_sub = $_POST['id_sub'] ?? '';

// Enrutador de peticiones
switch ($peticion) {
    
    /**
     * Caso: INSERT_ROL
     * Inserta un nuevo rol en el catálogo junto con la matriz de permisos asociados.
     */
    case 'INSERT_ROL':
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id_sub) || empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "El nombre del rol y el origen del módulo son obligatorios."]);
            exit;
        }

        echo json_encode($controller->insertaRol($id_sub, $nom_rol, $permisos));
        break;

    /**
     * Caso: UPDATE_ROL
     * Actualiza la descripción (nombre) de un rol y reemplaza sus privilegios en la tabla de relaciones.
     */
    case 'UPDATE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id_sub) || empty($id_rol) || empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para actualizar el rol."]);
            exit;
        }

        echo json_encode($controller->actualizaRol($id_sub, $id_rol, $nom_rol, $permisos));
        break;

    /**
     * Caso: DELETE_ROL
     * Cambia el estatus de un rol para realizar una baja lógica del catálogo.
     */
    case 'DELETE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        
        if (empty($id_sub) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ID de rol o módulo de origen no válido."]);
            exit;
        }
        
        echo json_encode($controller->eliminaRol($id_sub, $id_rol));
        break;

    /**
     * Caso: REACTIVATE_ROL
     * Revierte la baja lógica de un rol y reescribe la configuración de sus permisos asignados.
     */
    case 'REACTIVATE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id_sub) || empty($id_rol) || empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos para la reactivación."]);
            exit;
        }

        echo json_encode($controller->reactivaRol($id_sub, $id_rol, $nom_rol, $permisos));
        break;

    default:
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición de rol no reconocida."]);
        break;
}
?>