<?php
// Ocultar advertencias de PHP para evitar que corrompan la respuesta JSON
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "ImportacionController.php";
require_once "MainModel.php"; // Requerido para buscar el id_sub en caso de emergencia

header('Content-Type: application/json; charset=utf-8');

// Validación de sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada."]);
    exit;
}

// Captura de entrada JSON para peticiones fetch
$input = json_decode(file_get_contents('php://input'), true);
$peticion = $_POST['peticion'] ?? ($input['peticion'] ?? '');

$controller = new ImportacionController();
$modelo = new MainModel();

// Obtención inteligente del submódulo
$id_sub = $_POST['id_sub'] ?? ($input['id_sub'] ?? null);
if (!$id_sub) {
    // Si no llega por POST/JSON, lo buscamos por su slug exacto en la base de datos
    $id_sub = $modelo->obtenerIdSubmoduloPorSlug('personal') ?? 1;
}

// =========================================================
// VALIDACIÓN DE PERMISOS (RBAC)
// Permiso 2 = Agregar / Importar
// =========================================================
if (!$controller->verificarPermiso($id_sub, 2)) {
    echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con los privilegios para importar registros."]);
    exit;
}

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
        
        // Pasamos el id_sub al controlador para que lo use al momento de guardar en la bitácora
        echo json_encode($controller->finalizarSincronizacion($resDepts, $bajas, $id_sub));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición '$peticion' no reconocida."]);
        break;
}
?>