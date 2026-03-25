<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "DispositivosController.php"; // Corrección de ruta crítica

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada o no válida."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Formato de datos no válido o vacío."]);
    exit;
}

$controller = new DispositivosController();

$peticion = $input['peticion'] ?? '';
$id_sub = $input['id_sub'] ?? null;
$id_disp = $input['id_disp'] ?? null;

switch ($peticion) {
    
    case 'INSERT':
        if (!$controller->verificarPermiso($id_sub, 2)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para agregar nuevos dispositivos."]);
            exit;
        }
        $interfaces = $input['interfaces_red'] ?? [];
        $asignaciones = $input['asignaciones_software'] ?? [];
        echo json_encode($controller->insertarDispositivo($id_sub, $input, $interfaces, $asignaciones));
        break;

    case 'UPDATE':
        if (!$controller->verificarPermiso($id_sub, 1)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para editar dispositivos."]);
            exit;
        }
        if (empty($id_disp)) {
            echo json_encode(["status" => "error", "msg" => "ID de dispositivo requerido para actualizar."]);
            exit;
        }
        $interfaces = $input['interfaces_red'] ?? [];
        $asignaciones = $input['asignaciones_software'] ?? [];
        echo json_encode($controller->actualizarDispositivo($id_sub, $id_disp, $input, $interfaces, $asignaciones));
        break;

    case 'BAJA_DISP':
        if (!$controller->verificarPermiso($id_sub, 3)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con los privilegios para dar de baja equipos."]);
            exit;
        }
        if (empty($id_disp) || empty($id_sub)) {
            echo json_encode(["status" => "error", "msg" => "Datos insuficientes para procesar la baja del dispositivo."]);
            exit;
        }
        echo json_encode($controller->bajaDispositivo($id_sub, $id_disp));
        break;

    case 'GET_VERSIONES_ACTUALIZAR':
        $id_serv = $input['id_serv'] ?? null;
        if (empty($id_serv)) {
            echo json_encode(["status" => "error", "msg" => "ID de servicio no proporcionado."]);
            exit;
        }
        echo json_encode($controller->obtenerVersionesActualizar($id_serv));
        break;

    case 'UPDATE_VERSION_DISPOSITIVO':
        if (!$controller->verificarPermiso($id_sub, 1)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para modificar el software instalado."]);
            exit;
        }
        $id_serv = $input['id_serv'] ?? null;
        $id_ver_nueva = $input['id_ver_nueva'] ?? null;

        if (empty($id_disp) || empty($id_serv) || empty($id_ver_nueva)) {
            echo json_encode(["status" => "error", "msg" => "Faltan datos para actualizar la versión."]);
            exit;
        }
        echo json_encode($controller->actualizarVersionDispositivo($id_sub, $id_disp, $id_serv, $id_ver_nueva));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición no reconocida o inválida."]);
        break;
}
?>