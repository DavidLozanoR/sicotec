<?php
/**
 * Asynchronous Controller: Dispositivos
 * Handles CRUD operations for devices via fetch API.
 * Manages relationships across 3 tables: dispositivos, infraestructura_red, and dispositivo_servicio.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

// 1. Session Security Check
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Unauthorized access or session expired."]);
    exit;
}

// 2. Payload Extraction (Decoding JSON from fetch)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Invalid data format received."]);
    exit;
}

$modelo = new MainModel();

// Core variables for routing and tracking
$peticion = $input['peticion'] ?? '';
$id_sub = $input['id_sub'] ?? null;
$id_disp = $input['id_disp'] ?? null;

// System audit variables
$id_u_int = $_SESSION['usuario']['id_usuar'] ?? null;
date_default_timezone_set('America/Mexico_City');
$fec_u_int = date('Y-m-d H:i:s');

// Foreign Key Sanitization: Convert empty string inputs to null to avoid DB constraint failures
$asig_po_id = !empty($input['asig_po_id']) ? $input['asig_po_id'] : null;
$resp_po_id = !empty($input['resp_po_id']) ? $input['resp_po_id'] : null;

// 3. Data Mapping for Prepared Statements
$camposDispositivo = [
    "nom_host", "marca", "modelo", "num_ser", "num_inv", "dom", 
    "especs_hw", "resp_po_id", "asig_po_id", "id_u_int", "fec_u_int"
];

$paramsDispositivo = [
    trim($input['nom_host'] ?? ''),
    trim($input['marca'] ?? ''),
    trim($input['modelo'] ?? ''),
    trim($input['num_ser'] ?? ''),
    trim($input['num_inv'] ?? ''),
    trim($input['dom'] ?? ''),
    trim($input['especs_hw'] ?? ''),
    $resp_po_id,
    $asig_po_id,
    $id_u_int,
    $fec_u_int
];

try {
    // ==============================================================
    // PHASE A: MAIN TABLE OPERATION (dispositivos)
    // ==============================================================
    if ($peticion === 'INSERT') {
        $detalle = "Registered new device. Host: " . ($input['nom_host'] ?? 'Unknown');
        
        // Utilize the new method to get the ID required for Phases B and C
        $id_disp = $modelo->agregaRegistroYBitacora("dispositivos", $camposDispositivo, $paramsDispositivo, $id_sub, $detalle);
        
        if (!$id_disp) {
            throw new Exception("Database failed to register the primary device record.");
        }

    } elseif ($peticion === 'UPDATE') {
        if (empty($id_disp)) throw new Exception("Device ID is required for updates.");
        
        // Append WHERE clause parameter to the existing array
        $paramsUpdate = array_merge($paramsDispositivo, [$id_disp]);
        $exito = $modelo->actualizaRegistro("dispositivos", $camposDispositivo, "id_disp = ?", $paramsUpdate);
        
        if (!$exito) throw new Exception("No changes detected or database update failed.");

        // Manual audit logging for the update action
        $detalle = "Updated device configurations. ID: $id_disp, Host: " . ($input['nom_host'] ?? 'Unknown');
        $modelo->registrarBitacora($id_sub, $peticion, $detalle);

    } else {
        throw new Exception("Invalid request type.");
    }

    // ==============================================================
    // PHASE B: NETWORK ALLOCATION (infraestructura_red)
    // ==============================================================
    $id_red = !empty($input['id_red']) ? $input['id_red'] : null;
    
    // Step 1: Release any IP currently held by this device to prevent ghost mappings
    $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_disp = ?", [null, $id_disp]);

    // Step 2: Assign the newly selected IP
    if ($id_red) {
        $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_red = ?", [$id_disp, $id_red]);
    }

    // ==============================================================
    // PHASE C: SOFTWARE ASSIGNMENT (dispositivo_servicio)
    // ==============================================================
    // Hard delete of existing software links to maintain an exact mirror of the frontend state
    $modelo->borraRegistro("dispositivo_servicio", "id_disp = ?", [$id_disp]);

    $asignaciones = $input['asignaciones_software'] ?? [];
    if (!empty($asignaciones) && is_array($asignaciones)) {
        foreach ($asignaciones as $software) {
            // Bypass empty arrays from the frontend dynamically
            if (!empty($software['id_serv']) && !empty($software['id_ver_instalada'])) {
                $modelo->agregaRegistro(
                    "dispositivo_servicio", 
                    ["id_disp", "id_serv", "id_ver_instalada"], 
                    [$id_disp, $software['id_serv'], $software['id_ver_instalada']]
                );
            }
        }
    }

    // Return standard success format consumed by SweetAlert
    echo json_encode(["status" => "success", "msg" => "Device information saved successfully."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>