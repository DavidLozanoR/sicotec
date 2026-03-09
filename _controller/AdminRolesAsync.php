<?php
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "AdminRolesController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

$controller = new AdminRolesController();
$peticion = $_POST['peticion'] ?? '';

// Capturamos el ID del submódulo dinámicamente
$id_sub = $_POST['id_sub'] ?? '';

switch ($peticion) {
    case 'INSERT_ROL':
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id_sub) || empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "El nombre del rol y el origen del módulo son obligatorios."]);
            exit;
        }

        echo json_encode($controller->insertaRol($id_sub, $nom_rol, $permisos));
        break;

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

    case 'DELETE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        
        if (empty($id_sub) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ID de rol o módulo de origen no válido."]);
            exit;
        }
        
        echo json_encode($controller->eliminaRol($id_sub, $id_rol));
        break;

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
        echo json_encode(["result" => 0, "msg" => "Petición de rol no reconocida."]);
        break;
}
?>