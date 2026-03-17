<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada o no válida."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Formato de datos no válido."]);
    exit;
}

$modelo = new MainModel();

$peticion = $input['peticion'] ?? '';
$id_sub = $input['id_sub'] ?? null;
$id_disp = $input['id_disp'] ?? null;

$id_u_int = $_SESSION['usuario']['id_usuar'] ?? null;
date_default_timezone_set('America/Mexico_City');
$fec_u_int = date('Y-m-d H:i:s');

// Sanitización de llaves foráneas para evitar cadenas vacías
$asig_po_id = !empty($input['asig_po_id']) ? $input['asig_po_id'] : null;
$resp_po_id = !empty($input['resp_po_id']) ? $input['resp_po_id'] : null;

// Arreglo puro compatible con MariaDBAux (sin "?")
$camposDispositivo = [
    "nom_host", "marca", "modelo", "num_ser", "num_inv", "mac", "dom", 
    "especs_hw", "resp_po_id", "asig_po_id", "id_u_int", "fec_u_int"
];

$paramsDispositivo = [
    trim($input['nom_host'] ?? ''),
    trim($input['marca'] ?? ''),
    trim($input['modelo'] ?? ''),
    trim($input['num_ser'] ?? ''),
    trim($input['num_inv'] ?? ''),
    trim($input['mac'] ?? ''),
    trim($input['dom'] ?? ''),
    trim($input['especs_hw'] ?? ''),
    $resp_po_id,
    $asig_po_id,
    $id_u_int,
    $fec_u_int
];

try {
    // ==============================================================
    // A. TABLA PRINCIPAL (dispositivos)
    // ==============================================================
    if ($peticion === 'INSERT') {
        $detalle = "Registro de nuevo dispositivo. Host: " . ($input['nom_host'] ?? 'N/A');
        
        $id_disp = $modelo->agregaRegistroConBitacora("dispositivos", $camposDispositivo, $paramsDispositivo, $id_sub, $detalle);
        
        if (!$id_disp) {
            throw new Exception("Error al registrar el dispositivo en la base de datos.");
        }

    } elseif ($peticion === 'UPDATE') {
        if (empty($id_disp)) {
            throw new Exception("ID de dispositivo requerido para actualizar.");
        }
        
        $detalle = "Actualización de dispositivo. Host: " . ($input['nom_host'] ?? 'N/A');
        $paramsUpdate = array_merge($paramsDispositivo, [$id_disp]);
        
        $exito = $modelo->actualizaRegistroConBitacora("dispositivos", $camposDispositivo, "id_disp = ?", $paramsUpdate, $id_sub, $detalle);
        
        if (!$exito) {
            throw new Exception("No se detectaron cambios o hubo un error al actualizar.");
        }
    } elseif ($peticion === 'BAJA_DISP') {
        
        if (empty($id_disp) || empty($id_sub)) {
            throw new Exception("Datos insuficientes para procesar la baja del dispositivo.");
        }

        // 1. RECOPILAR INFORMACIÓN PARA LA BITÁCORA (FOTOGRAFÍA PRE-BAJA)
        $disp_actual = $modelo->seleccionaRegistros("dispositivos", ["*"], "id_disp = ?", [$id_disp]);
        if (empty($disp_actual)) {
            throw new Exception("El dispositivo no existe o ya fue eliminado.");
        }
        $d = $disp_actual[0];

        // Obtener la IP vinculada actual
        $red_actual = $modelo->seleccionaRegistros("infraestructura_red", ["ip"], "id_disp = ?", [$id_disp]);
        $ip_snapshot = !empty($red_actual) ? $red_actual[0]['ip'] : 'Ninguna';

        // Obtener los servicios actuales y formatearlos
        $servicios_actuales = $modelo->seleccionaRegistros("dispositivo_servicio", ["id_serv", "id_ver_instalada"], "id_disp = ?", [$id_disp]);
        $serv_snapshot = empty($servicios_actuales) ? 'Ninguno' : json_encode($servicios_actuales);

        // Compilar cadena de respaldo
        $detalle_fotografia = "RESPALDO PRE-BAJA | Host: {$d['nom_host']} | Marca: {$d['marca']} | Modelo: {$d['modelo']} | Serie: {$d['num_ser']} | Inv: {$d['num_inv']} | MAC: {$d['mac']} | IP Liberada: {$ip_snapshot} | Asig_PO: {$d['asig_po_id']} | Resp_PO: {$d['resp_po_id']} | Software: {$serv_snapshot}";

        // Escribir la fotografía en bitácora ANTES de destruir nada
        $modelo->registrarBitacora($id_sub, 'BACKUP_BAJA', $detalle_fotografia);

        // 2. LIBERAR RECURSOS EN CASCADA
        // A) Liberar IP (poner id_disp en null en la red)
        $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_disp = ?", [null, $id_disp]);

        // B) Borrar servicios asignados
        $modelo->borraRegistroConBitacora("dispositivo_servicio", "id_disp = ?", [$id_disp], $id_sub, "Limpieza de software vinculada al dispositivo: {$d['nom_host']}");

        // 3. BORRAR DISPOSITIVO (Baja física)
        $exito_baja = $modelo->borraRegistroConBitacora("dispositivos", "id_disp = ?", [$id_disp], $id_sub, "Baja definitiva del dispositivo: {$d['nom_host']}");

        if (!$exito_baja) {
            throw new Exception("Ocurrió un error al intentar eliminar el dispositivo físico.");
        }

        echo json_encode(["status" => "success", "msg" => "El dispositivo fue dado de baja. La información fue respaldada en bitácora."]);
        exit;

    } 
    else {
        throw new Exception("Petición no reconocida.");
    }

    // ==============================================================
    // B. GESTIÓN DE RED (infraestructura_red)
    // ==============================================================
    $id_red = !empty($input['id_red']) ? $input['id_red'] : null;
    
    $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_disp = ?", [null, $id_disp]);

    if ($id_red) {
        $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_red = ?", [$id_disp, $id_red]);
    }

    // ==============================================================
    // C. GESTIÓN DE SERVICIOS (dispositivo_servicio)
    // ==============================================================
    $modelo->borraRegistro("dispositivo_servicio", "id_disp = ?", [$id_disp]);

    $asignaciones = $input['asignaciones_software'] ?? [];
    if (!empty($asignaciones) && is_array($asignaciones)) {
        foreach ($asignaciones as $software) {
            if (!empty($software['id_serv']) && !empty($software['id_ver_instalada'])) {
                $modelo->agregaRegistro(
                    "dispositivo_servicio", 
                    ["id_disp", "id_serv", "id_ver_instalada"], 
                    [$id_disp, $software['id_serv'], $software['id_ver_instalada']]
                );
            }
        }
    }

    echo json_encode(["status" => "success", "msg" => "El dispositivo fue guardado correctamente."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
?>