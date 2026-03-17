<?php
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "ResponsivaController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

$controller = new ResponsivaController();

$peticion = $_POST['peticion'] ?? '';
$id_sub = (int)($_POST['id_sub'] ?? 0);

switch ($peticion) {
    
    case 'GET_EQUIPOS':
        $id_po = (int)($_POST['id_po'] ?? 0);
        
        if (empty($id_po)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó al usuario."]); 
            exit;
        }

        echo json_encode($controller->obtenerEquipos($id_sub, $id_po));
        break;

    case 'TRANSFERIR_EQUIPO':
        $id_disp = (int)($_POST['id_disp'] ?? 0);
        $id_po_origen = (int)($_POST['id_po_origen'] ?? 0);
        $id_po_destino = (int)($_POST['id_po_destino'] ?? 0);

        if (empty($id_disp) || empty($id_po_origen) || empty($id_po_destino)) {
            echo json_encode(["result" => 0, "msg" => "Faltan identificadores para realizar la transferencia."]); 
            exit;
        }

        echo json_encode($controller->transferirEquipo($id_sub, $id_disp, $id_po_origen, $id_po_destino));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida por el servidor."]);
        break;
}
?>