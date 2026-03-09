<?php
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "AdminUsuariosController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

$controller = new AdminUsuariosController();
$peticion = $_POST['peticion'] ?? '';

switch ($peticion) {
    case 'INSERT_USUARIO':
        $nom_usuar = trim($_POST['nom_usuar'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $ape_pa = trim($_POST['ape_pa'] ?? '');
        $ape_ma = trim($_POST['ape_ma'] ?? '');
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';

        if (empty($nom_usuar) || empty($nombres) || empty($ape_pa) || empty($id_rol) || empty($password)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para crear el usuario."]);
            exit;
        }

        echo json_encode($controller->insertaUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol));
        break;

    case 'UPDATE_USUARIO':
        $id_usuar = $_POST['id_usuar'] ?? '';
        $nom_usuar = trim($_POST['nom_usuar'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $ape_pa = trim($_POST['ape_pa'] ?? '');
        $ape_ma = trim($_POST['ape_ma'] ?? '');
        $password = $_POST['password'] ?? '';
        $id_rol = $_POST['id_rol'] ?? '';

        if (empty($id_usuar) || empty($nom_usuar) || empty($nombres) || empty($ape_pa) || empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para actualizar."]);
            exit;
        }

        echo json_encode($controller->actualizaUsuario($id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol));
        break;

    case 'BAJA_USUARIO':
        $id_usuar = $_POST['id_usuar'] ?? '';
        if (empty($id_usuar)) {
            echo json_encode(["result" => 0, "msg" => "ID de usuario no válido."]);
            exit;
        }
        echo json_encode($controller->bajaUsuario($id_usuar));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición de usuario no reconocida."]);
        break;
}