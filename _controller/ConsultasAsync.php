<?php
session_start();
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
            'id_serv' => $_POST['id_serv'] ?? '',
            'estado_soft' => $_POST['estado_soft'] ?? 'todos'
        ];

        echo json_encode($controller->ejecutarConsulta($filtros));
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}
?>