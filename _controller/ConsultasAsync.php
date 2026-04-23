<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

// Ocultar warnings de PHP para no corromper la respuesta JSON
error_reporting(0); 
session_start();

// Forzar al servidor a responder estrictamente en formato JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../config/Global.php";
require_once "ConsultasController.php";

// Validación de seguridad: Verifica que exista una sesión activa antes de procesar la petición
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

/** @var ConsultasController Instancia del controlador de consultas e informes */
$controller = new ConsultasController();

/** @var string Cadena de control para enrutamiento de la petición AJAX */
$peticion = $_POST['peticion'] ?? '';

// Enrutador principal de peticiones
switch ($peticion) {
    
    /**
     * Caso: EJECUTAR_CONSULTA
     * Recopila los parámetros de filtrado (asignación, área, red y software) 
     * enviados desde el frontend para generar un reporte dinámico de inventario.
     */
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

    /**
     * Caso: OBTENER_BITACORA
     * Recopila los parámetros de rango de fechas y usuario específico 
     * para extraer el historial de auditoría del sistema.
     */
    case 'OBTENER_BITACORA':
        $filtros = [
            'fecha_ini' => $_POST['fecha_ini'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? '',
            'id_usuar' => $_POST['id_usuar'] ?? ''
        ];
        echo json_encode($controller->obtenerBitacora($filtros));
        break;

    default:
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}
?>