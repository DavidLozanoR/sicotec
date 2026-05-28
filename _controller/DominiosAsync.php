<?php
/**
 * Router Asíncrono para el Catálogo de Dominios Institucionales
 * @author Angel David Lozano Rojas
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "DominiosController.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

// 1. Validación de sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada. Vuelva a iniciar sesión."]);
    exit;
}

// 2. Extracción de Payload (Fetch API)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Formato de datos no válido o vacío."]);
    exit;
}

$peticion = $input['peticion'] ?? '';
$id_sub = $input['id_sub'] ?? 12; // ID de submódulo 

$controller = new DominiosController();

// 3. Enrutador de Acciones con Validación de Permisos
switch ($peticion) {
    
    case 'GUARDAR_DOMINIO':
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) { 
            echo json_encode(["status" => "error", "msg" => "Permiso denegado para editar o crear registros."]); 
            exit; 
        }
        $id_edicion = !empty($input['id_edicion']) ? (int)$input['id_edicion'] : null;
        echo json_encode($controller->guardarDominio($id_sub, $input['nom_dom'], $id_edicion));
        break;

    case 'ELIMINAR_DOMINIO':
        if (!$controller->verificarPermiso($id_sub, PERM_ELIMINAR)) { 
            echo json_encode(["status" => "error", "msg" => "Permiso denegado para eliminar."]); 
            exit; 
        }
        echo json_encode($controller->eliminarDominio($id_sub, (int)$input['id_dom']));
        break;

    case 'REACTIVAR_DOMINIO':
        // Reactivar cuenta como una edición al sistema
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) { 
            echo json_encode(["status" => "error", "msg" => "Permiso denegado para reactivar registros."]); 
            exit; 
        }
        echo json_encode($controller->reactivarDominio($id_sub, (int)$input['id_dom']));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición asíncrona desconocida."]);
        break;
}
?>