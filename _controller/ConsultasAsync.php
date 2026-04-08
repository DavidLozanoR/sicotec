<?php
// Ocultar warnings de PHP para no corromper la respuesta JSON
error_reporting(0); 
session_start();

// Forzar al servidor a responder estrictamente en formato JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../config/Global.php";
require_once "ConsultasController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

$controller = new ConsultasController();
$peticion = $_POST['peticion'] ?? '';

switch ($peticion) {
    case 'EJECUTAR_CONSULTA':
        $filtros = [
            'estado_asig' => $_POST['estado_asig'] ?? 'todos',
            'id_dep' => $_POST['id_dep'] ?? '',
            'id_vlan' => $_POST['id_vlan'] ?? '', 
            'id_serv' => $_POST['id_serv'] ?? '',
            'estado_soft' => $_POST['estado_soft'] ?? 'todos'
        ];
        echo json_encode($controller->ejecutarConsulta($filtros));
        break;

    case 'OBTENER_BITACORA':
        $filtros = [
            'fecha_ini' => $_POST['fecha_ini'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'id_usuar' => $_POST['id_usuar'] ?? ''
        ];
        echo json_encode($controller->obtenerBitacora($filtros));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}
?>