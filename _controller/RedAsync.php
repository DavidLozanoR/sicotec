<?php
session_start();
require_once __DIR__ . "/../config/Global.php"; 
require_once "RedController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

$controller = new RedController();

$peticion = $_POST['peticion'] ?? '';
$id_sub = $_POST['id_sub'] ?? '';

switch ($peticion) {
    
    case 'INSERT_SEG':
        if (!$controller->verificarPermiso($id_sub, 2)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para agregar."]); exit;
        }

        // CAMBIO: Capturamos id_vlan en lugar de id_dep
        $id_vlan = $_POST['id_vlan'] ?? '';
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');
        $reservas = trim($_POST['reservas'] ?? '');

        if (empty($id_vlan) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Por favor complete todos los campos obligatorios."]); exit;
        }

        echo json_encode($controller->insertarSegmento($id_sub, $id_vlan, $ip_ini, $ip_lim, $reservas));
        break;

    case 'UPDATE_SEG':
        if (!$controller->verificarPermiso($id_sub, 1)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para editar."]); exit;
        }

        $id_seg = $_POST['id_seg'] ?? '';
        // CAMBIO: Capturamos id_vlan en lugar de id_dep
        $id_vlan = $_POST['id_vlan'] ?? '';
        
        // CAPTURAMOS LOS LIMITES PARA PERMITIR EDITAR RANGOS
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');
        $reservas = trim($_POST['reservas'] ?? '');

        if (empty($id_seg) || empty($id_vlan) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos para actualizar."]); exit;
        }
        
        echo json_encode($controller->actualizarSegmento($id_sub, $id_seg, $id_vlan, $ip_ini, $ip_lim, $reservas));
        break;

    case 'DELETE_SEG':
        if (!$controller->verificarPermiso($id_sub, 3)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado."]); exit;
        }
        $id_seg = $_POST['id_seg'] ?? '';
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento a eliminar."]); exit;
        }
        echo json_encode($controller->eliminarSegmento($id_sub, $id_seg));
        break;

    case 'GET_IPS':
        $id_seg = $_POST['id_seg'] ?? '';
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento solicitado."]); exit;
        }
        echo json_encode($controller->obtenerIpsSegmento($id_seg));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición no válida."]);
        break;
}
?>