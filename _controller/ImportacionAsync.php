<?php
// Configuración estricta para evitar salidas de texto que corrompan el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once __DIR__ . "/../config/Global.php";
require_once "ImportacionController.php";
require_once "MainModel.php";

// Manejador global de excepciones para asegurar que siempre se responda un JSON
set_exception_handler(function($e) {
    echo json_encode(["status" => "error", "msg" => "Error interno: " . $e->getMessage()]);
    exit;
});

// 1. Validación de sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada o no iniciada."]);
    exit;
}

// 2. Consolidación de entradas (Soporta fetch JSON y FormData tradicional)
$input_json = json_decode(file_get_contents('php://input'), true) ?? [];
$peticion   = $_POST['peticion'] ?? $input_json['peticion'] ?? '';
$id_sub     = $_POST['id_sub']   ?? $input_json['id_sub']   ?? null;

$controller = new ImportacionController();
$modelo     = new MainModel();

// 3. Resolución automática del submódulo si no se provee en la petición
if (!$id_sub) {
    $id_sub = $modelo->obtenerIdSubmoduloPorSlug('personal') ?? 1;
}

// 4. Validación centralizada de permisos (Permiso 2: Crear/Importar)
if (!$controller->verificarPermiso($id_sub, PERM_AGREGAR)) {
    echo json_encode(["status" => "error", "msg" => "Acceso denegado: Privilegios insuficientes para importar."]);
    exit;
}

// 5. Enrutador de peticiones
switch ($peticion) {
    
    case 'SUBIR_ARCHIVO':
        // Validación de existencia e integridad del archivo subido
        if (!isset($_FILES['archivo_masivo']) || $_FILES['archivo_masivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["status" => "error", "msg" => "Archivo no recibido o corrupto durante la transmisión."]);
            exit;
        }
        
        $ruta_temporal   = $_FILES['archivo_masivo']['tmp_name'];
        $nombre_original = $_FILES['archivo_masivo']['name'];
        
        echo json_encode($controller->procesarArchivoMasivo($ruta_temporal, $nombre_original, $id_sub));
        break;

    case 'GET_CONFLICTOS':
        echo json_encode($controller->obtenerConflictos());
        break;

    case 'EJECUTAR_FINAL':
        // Extracción directa desde el payload JSON
        $resDepts = $input_json['resolucionesDepts'] ?? [];
        $bajas    = $input_json['numCobrosBaja']     ?? [];
        
        echo json_encode($controller->finalizarSincronizacion($resDepts, $bajas, $id_sub));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición '$peticion' no reconocida por el servidor."]);
        break;
}
?>