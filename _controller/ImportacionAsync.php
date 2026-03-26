<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "ImportacionController.php";


header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada."]);
    exit;
}

// Captura de entrada JSON para peticiones fetch
$input = json_decode(file_get_contents('php://input'), true);
$peticion = $_POST['peticion'] ?? ($input['peticion'] ?? '');
$id_sub = $_POST['id_sub'] ?? ($input['id_sub'] ?? null);

$controller = new ImportacionController();

// Validación de permisos para todas las acciones (Permiso 2: Agregar/Importar)
/*if (!$controller->verificarPermiso($id_sub, 2)) {
    echo json_encode(["status" => "error", "msg" => "No cuenta con permisos para esta acción."]);
    exit;
}*/

switch ($peticion) {
    case 'SUBIR_CSV':
        if (!isset($_FILES['archivo_csv'])) {
            echo json_encode(["status" => "error", "msg" => "No se recibió el archivo."]);
            exit;
        }
        echo json_encode($controller->procesarCSV($_FILES['archivo_csv']['tmp_name'], $id_sub));
        break;

    case 'GET_CONFLICTOS':
        echo json_encode($controller->obtenerConflictos());
        break;

    case 'EJECUTAR_FINAL':
        $resDepts = $input['resolucionesDepts'] ?? [];
        $bajas = $input['numCobrosBaja'] ?? [];
        echo json_encode($controller->finalizarSincronizacion($resDepts, $bajas));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición '$peticion' no reconocida."]);
        break;
}
?>