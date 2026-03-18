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

$asig_po_id = !empty($input['asig_po_id']) ? $input['asig_po_id'] : null;
$resp_po_id = !empty($input['resp_po_id']) ? $input['resp_po_id'] : null;

$camposDispositivo = [
    "nom_host", "marca", "modelo", "num_ser", "num_inv", "mac", "dom", 
    "especs_hw", "resp_po_id", "asig_po_id", "id_u_int", "fec_u_int"
];

$paramsDispositivo = [
    trim($input['nom_host'] ?? ''), trim($input['marca'] ?? ''), trim($input['modelo'] ?? ''),
    trim($input['num_ser'] ?? ''), trim($input['num_inv'] ?? ''), trim($input['mac'] ?? ''),
    trim($input['dom'] ?? ''), trim($input['especs_hw'] ?? ''), $resp_po_id, $asig_po_id, $id_u_int, $fec_u_int
];

try {
    if ($peticion === 'INSERT') {
        $detalle = "Registro de nuevo dispositivo. Host: " . ($input['nom_host'] ?? 'N/A');
        $id_disp = $modelo->agregaRegistroConBitacora("dispositivos", $camposDispositivo, $paramsDispositivo, $id_sub, $detalle);
        if (!$id_disp) throw new Exception("Error al registrar el dispositivo en la base de datos.");

    } elseif ($peticion === 'UPDATE') {
        if (empty($id_disp)) throw new Exception("ID de dispositivo requerido para actualizar.");
        
        $detalle = "Actualización de dispositivo. Host: " . ($input['nom_host'] ?? 'N/A');
        $paramsUpdate = array_merge($paramsDispositivo, [$id_disp]);
        $exito = $modelo->actualizaRegistroConBitacora("dispositivos", $camposDispositivo, "id_disp = ?", $paramsUpdate, $id_sub, $detalle);
        if (!$exito) throw new Exception("No se detectaron cambios o hubo un error al actualizar.");

    } elseif ($peticion === 'BAJA_DISP') {
        if (empty($id_disp) || empty($id_sub)) throw new Exception("Datos insuficientes para procesar la baja del dispositivo.");

        $disp_actual = $modelo->seleccionaRegistros("dispositivos", ["*"], "id_disp = ?", [$id_disp]);
        if (empty($disp_actual)) throw new Exception("El dispositivo no existe o ya fue eliminado.");
        $d = $disp_actual[0];

        $red_actual = $modelo->seleccionaRegistros("infraestructura_red", ["ip"], "id_disp = ?", [$id_disp]);
        $ip_snapshot = !empty($red_actual) ? $red_actual[0]['ip'] : 'Ninguna';

        $servicios_actuales = $modelo->seleccionaRegistros("dispositivo_servicio", ["id_serv", "id_ver_instalada"], "id_disp = ?", [$id_disp]);
        $serv_snapshot = empty($servicios_actuales) ? 'Ninguno' : json_encode($servicios_actuales);

        $detalle_fotografia = "RESPALDO PRE-BAJA | Host: {$d['nom_host']} | Marca: {$d['marca']} | Modelo: {$d['modelo']} | Serie: {$d['num_ser']} | Inv: {$d['num_inv']} | MAC: {$d['mac']} | IP Liberada: {$ip_snapshot} | Asig_PO: {$d['asig_po_id']} | Resp_PO: {$d['resp_po_id']} | Software: {$serv_snapshot}";

        $modelo->registrarBitacora($id_sub, 'BACKUP_BAJA', $detalle_fotografia);
        $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_disp = ?", [null, $id_disp]);
        $modelo->borraRegistroConBitacora("dispositivo_servicio", "id_disp = ?", [$id_disp], $id_sub, "Limpieza de software vinculada al dispositivo: {$d['nom_host']}");
        
        $exito_baja = $modelo->borraRegistroConBitacora("dispositivos", "id_disp = ?", [$id_disp], $id_sub, "Baja definitiva del dispositivo: {$d['nom_host']}");
        if (!$exito_baja) throw new Exception("Ocurrió un error al intentar eliminar el dispositivo físico.");

        echo json_encode(["status" => "success", "msg" => "El dispositivo fue dado de baja. La información fue respaldada en bitácora."]);
        exit;

    } 
    // ==============================================================
    // NUEVAS RUTAS PARA ACTUALIZAR SOFTWARE DIRECTO DESDE LA TABLA
    // ==============================================================
    elseif ($peticion === 'GET_VERSIONES_ACTUALIZAR') {
        $id_serv = $input['id_serv'] ?? null;
        if (empty($id_serv)) throw new Exception("ID de servicio no proporcionado.");
        
        // Consultamos únicamente las versiones "Aceptadas" (obs = 0)
        $versiones = $modelo->seleccionaRegistros("versiones", ["id_ver", "ver"], "id_serv = ? AND obs = 0 ORDER BY ver DESC", [$id_serv]);
        echo json_encode(["status" => "success", "data" => $versiones ?: []]);
        exit;

    } elseif ($peticion === 'UPDATE_VERSION_DISPOSITIVO') {
        $id_disp = $input['id_disp'] ?? null;
        $id_serv = $input['id_serv'] ?? null;
        $id_ver_nueva = $input['id_ver_nueva'] ?? null;

        if (empty($id_disp) || empty($id_serv) || empty($id_ver_nueva)) {
            throw new Exception("Faltan datos para actualizar la versión.");
        }

        $disp_info = $modelo->seleccionaRegistros("dispositivos", ["nom_host"], "id_disp = ?", [$id_disp]);
        $serv_info = $modelo->seleccionaRegistros("servicios", ["nom"], "id_serv = ?", [$id_serv]);
        $ver_info = $modelo->seleccionaRegistros("versiones", ["ver"], "id_ver = ?", [$id_ver_nueva]);

        $nom_host = $disp_info[0]['nom_host'] ?? "ID $id_disp";
        $nom_serv = $serv_info[0]['nom'] ?? "ID $id_serv";
        $nom_ver = $ver_info[0]['ver'] ?? "ID $id_ver_nueva";

        $exito = $modelo->actualizaRegistro(
            "dispositivo_servicio",
            ["id_ver_instalada"],
            "id_disp = ? AND id_serv = ?",
            [$id_ver_nueva, $id_disp, $id_serv]
        );

        if ($exito !== false) {
            $modelo->registrarBitacora($id_sub, 'UPDATE', "Actualizó '{$nom_serv}' a la versión '{$nom_ver}' en el equipo '{$nom_host}'");
            echo json_encode(["status" => "success", "msg" => "El software fue actualizado a la versión {$nom_ver} correctamente."]);
        } else {
            throw new Exception("Error al intentar actualizar la versión.");
        }
        exit;
    }
    else {
        throw new Exception("Petición no reconocida.");
    }

    $id_red = !empty($input['id_red']) ? $input['id_red'] : null;
    $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_disp = ?", [null, $id_disp]);

    if ($id_red) {
        $modelo->actualizaRegistro("infraestructura_red", ["id_disp"], "id_red = ?", [$id_disp, $id_red]);
    }

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