<?php
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "ServiciosController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

$controller = new ServiciosController();

$peticion = $_POST['peticion'] ?? '';
$id_sub = $_POST['id_sub'] ?? '';

switch ($peticion) {
    
    case 'INSERT_SERV':
        $tipo_serv = trim($_POST['tipo_serv'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $especs = trim($_POST['especs'] ?? '');
        $version_inicial = trim($_POST['version_inicial'] ?? '');
        $icono = trim($_POST['icono'] ?? 'fas fa-cube'); // Se captura el icono

        if (empty($nom) || empty($tipo_serv) || empty($version_inicial)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para crear el servicio."]); 
            exit;
        }

        echo json_encode($controller->insertarServicio($id_sub, $tipo_serv, $nom, $especs, $version_inicial, $icono));
        break;

    case 'UPDATE_SERV':
        $id_serv = $_POST['id_serv'] ?? '';
        $tipo_serv = trim($_POST['tipo_serv'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $especs = trim($_POST['especs'] ?? '');
        $icono = trim($_POST['icono'] ?? 'fas fa-cube'); // Se captura el icono

        if (empty($id_serv) || empty($nom) || empty($tipo_serv)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para la actualización."]); 
            exit;
        }

        echo json_encode($controller->actualizarServicio($id_sub, $id_serv, $tipo_serv, $nom, $especs, $icono));
        break;
    case 'DELETE_SERV':
        $id_serv = $_POST['id_serv'] ?? '';
        
        if (empty($id_serv)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el servicio a desactivar."]); 
            exit;
        }

        echo json_encode($controller->eliminarServicio($id_sub, $id_serv));
        break;

    case 'GET_VERSIONS':
        $id_serv = $_POST['id_serv'] ?? '';
        
        if (empty($id_serv)) {
            echo json_encode(["result" => 0, "msg" => "ID de servicio no válido."]); 
            exit;
        }

        echo json_encode($controller->obtenerVersiones($id_sub, $id_serv));
        break;

    case 'ADD_VERSION':
        $id_serv = $_POST['id_serv'] ?? '';
        $nueva_version = trim($_POST['nueva_version'] ?? '');
        $marcar_actual = isset($_POST['marcar_actual']) ? $_POST['marcar_actual'] : '0';

        if (empty($id_serv) || empty($nueva_version)) {
            echo json_encode(["result" => 0, "msg" => "Debe escribir el nombre de la nueva versión."]); 
            exit;
        }

        echo json_encode($controller->agregarVersion($id_sub, $id_serv, $nueva_version, $marcar_actual));
        break;

    case 'SET_CURRENT_VERSION':
        $id_serv = $_POST['id_serv'] ?? '';
        $id_ver = $_POST['id_ver'] ?? '';

        if (empty($id_serv) || empty($id_ver)) {
            echo json_encode(["result" => 0, "msg" => "Faltan identificadores para realizar el cambio."]); 
            exit;
        }

        echo json_encode($controller->marcarVersionActual($id_sub, $id_serv, $id_ver));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}
?>