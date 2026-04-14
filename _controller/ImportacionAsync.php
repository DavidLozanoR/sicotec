<?php
// Ocultar advertencias de PHP para evitar que corrompan la respuesta JSON
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "ImportacionController.php";
require_once "MainModel.php";

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
    $id_sub = $modelo->obtenerIdSubmoduloPorSlug('personal') ?? 1;
}

// Validación de Permisos
if (!$controller->verificarPermiso($id_sub, 2)) {
    echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con los privilegios para importar registros."]);
    exit;
}

switch ($peticion) {
    case 'SUBIR_ARCHIVO': // Modificado para aceptar múltiples formatos
        if (!isset($_FILES['archivo_masivo'])) {
            echo json_encode(["status" => "error", "msg" => "No se recibió el archivo."]);
            exit;
        }
        $ruta_temporal = $_FILES['archivo_masivo']['tmp_name'];
        $nombre_original = $_FILES['archivo_masivo']['name'];
        
        echo json_encode($controller->procesarArchivoMasivo($ruta_temporal, $nombre_original, $id_sub));
        break;

    case 'GET_CONFLICTOS':
        echo json_encode($controller->obtenerConflictos());
        break;

    case 'EJECUTAR_FINAL':
        $resDepts = $input['resolucionesDepts'] ?? [];
        $bajas = $input['numCobrosBaja'] ?? [];
        
        echo json_encode($controller->finalizarSincronizacion($resDepts, $bajas, $id_sub));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición '$peticion' no reconocida."]);
        break;
}
?>