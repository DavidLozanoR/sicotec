<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión MariaDB
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

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

/**
 * Manejador global de excepciones.
 * Intercepta cualquier fallo crítico no controlado y garantiza que el cliente 
 * siempre reciba una respuesta JSON estructurada para evitar bloqueos en el frontend.
 */
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
/** @var array Arreglo decodificado desde el cuerpo de la petición (Payload JSON) */
$input_json = json_decode(file_get_contents('php://input'), true) ?? [];

/** @var string Acción a enrutar, extraída de POST tradicional o de JSON fallback */
$peticion   = $_POST['peticion'] ?? $input_json['peticion'] ?? '';

/** @var int|null ID del submódulo actual */
$id_sub     = $_POST['id_sub']   ?? $input_json['id_sub']   ?? null;

/** @var ImportacionController Instancia del controlador de importación */
$controller = new ImportacionController();

/** @var MainModel Instancia del modelo principal */
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
    
    /**
     * Caso: SUBIR_ARCHIVO
     * Recibe el archivo físico multiformato (.xlsx, .csv, .xml, etc.), valida su 
     * integridad en la transmisión y lo pasa al controlador para su parseo a memoria temporal.
     */
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

    /**
     * Caso: GET_CONFLICTOS
     * Consulta la tabla temporal tras el análisis para devolver al frontend las 
     * anomalías detectadas (departamentos sin coincidir, personal dado de baja).
     */
    case 'GET_CONFLICTOS':
        echo json_encode($controller->obtenerConflictos());
        break;

    /**
     * Caso: EJECUTAR_FINAL
     * Ejecuta el volcado final hacia la tabla de producción (Personal Operativo),
     * inyectando las resoluciones manuales hechas por el usuario en el frontend.
     */
    case 'EJECUTAR_FINAL':
        // Extracción directa desde el payload JSON
        $resDepts = $input_json['resolucionesDepts'] ?? [];
        $bajas    = $input_json['numCobrosBaja']     ?? [];
        
        $ignorarCambio = $input_json['ignorarCambioCobros'] ?? [];
        $liberarEquipos = $input_json['liberarEquiposCobros'] ?? [];
        
        echo json_encode($controller->finalizarSincronizacion($resDepts, $bajas, $ignorarCambio, $liberarEquipos, $id_sub));
        break;

    default:
        // Catcher para peticiones mal formadas
        echo json_encode(["status" => "error", "msg" => "Petición '$peticion' no reconocida por el servidor."]);
        break;
}
?>