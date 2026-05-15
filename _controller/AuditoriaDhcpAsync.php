<?php
/**
 * Router Asíncrono para Auditoría DHCP
 * Utiliza FormData en lugar de Payload JSON.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../config/Global.php";
require_once "AuditoriaDhcpController.php";


if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada."]);
    exit;
}

$peticion = $_POST['peticion'] ?? '';
$id_sub = $_POST['id_sub'] ?? 11; 

$controller = new AuditoriaDhcpController();

switch ($peticion) {
    
    case 'PROCESAR_DHCP':
        if (!isset($_FILES['archivo_dhcp']) || $_FILES['archivo_dhcp']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["status" => "error", "msg" => "El archivo no se subió correctamente o está dañado."]);
            exit;
        }

        $ruta_temporal = $_FILES['archivo_dhcp']['tmp_name'];
        $nombre_original = $_FILES['archivo_dhcp']['name'];
        
        echo json_encode($controller->procesarArchivoConciliacion($ruta_temporal, $nombre_original, $id_sub));
        break;

    default:
        echo json_encode(["status" => "error", "msg" => "Petición desconocida."]);
        break;
}
?>