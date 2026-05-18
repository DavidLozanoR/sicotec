<?php
/**
 * Router Asíncrono para Catálogo de Marcas y Modelos
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "MarcasModelosController.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Formato de datos no válido."]);
    exit;
}

$peticion = $input['peticion'] ?? '';
$id_sub = $input['id_sub'] ?? 1; 

$controller = new MarcasModelosController();
$model = new MainModel();

// Validar permiso de edición para acciones de escritura (Editar, Reactivar)
$tiene_permiso_editar = $controller->verificarPermiso($id_sub, PERM_EDITAR);

switch ($peticion) {
    
    case 'GUARDAR_MARCA':
        if (!$tiene_permiso_editar) { echo json_encode(["status" => "error", "msg" => "Permiso denegado."]); exit; }
        echo json_encode($controller->guardarMarca($id_sub, $input['nom_marca'], $input['id_edicion'] ?? null));
        break;

    case 'OBTENER_MODELOS':
        echo json_encode($controller->obtenerModelosPorMarca($input['id_marca']));
        break;

    case 'GUARDAR_MODELO':
        if (!$tiene_permiso_editar) { echo json_encode(["status" => "error", "msg" => "Permiso denegado."]); exit; }
        echo json_encode($controller->guardarModelo($id_sub, $input['id_marca'], $input['nom_modelo'], $input['id_edicion'] ?? null));
        break;

    case 'ELIMINAR_REGISTRO':
        if (!$controller->verificarPermiso($id_sub, PERM_ELIMINAR)) { echo json_encode(["status" => "error", "msg" => "Permiso denegado para eliminar."]); exit; }
        echo json_encode($controller->eliminarRegistro($id_sub, $input['tabla'], $input['columna_id'], $input['id']));
        break;

    // Reactivación
    case 'REACTIVAR_REGISTRO':
        if (!$tiene_permiso_editar) { echo json_encode(["status" => "error", "msg" => "Permiso denegado para reactivar."]); exit; }
        echo json_encode($controller->reactivarRegistro($id_sub, $input['tabla'], $input['columna_id'], $input['id']));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición desconocida."]);
        break;
}
?>