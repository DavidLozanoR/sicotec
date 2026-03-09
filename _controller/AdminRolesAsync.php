<?php
session_start();
require_once "AdminRolesController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

$controller = new AdminRolesController();
$peticion = $_POST['peticion'] ?? '';

switch ($peticion) {
    case 'INSERT_ROL':
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "El nombre del rol es obligatorio."]);
            exit;
        }

        echo json_encode($controller->insertaRol($nom_rol, $permisos));
        break;

    case 'UPDATE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        $nom_rol = trim($_POST['nom_rol'] ?? '');
        $permisos = $_POST['permisos'] ?? [];

        if (empty($id_rol) || empty($nom_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para actualizar el rol."]);
            exit;
        }

        echo json_encode($controller->actualizaRol($id_rol, $nom_rol, $permisos));
        break;

    case 'DELETE_ROL':
        $id_rol = $_POST['id_rol'] ?? '';
        if (empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ID de rol no válido."]);
            exit;
        }
        echo json_encode($controller->eliminaRol($id_rol));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición de rol no reconocida."]);
        break;
}