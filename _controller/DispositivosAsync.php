<?php

/**
 * Modulo para manejar las funciones asincronas 
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "DispositivosController.php"; // Corrección de ruta crítica

header('Content-Type: application/json; charset=utf-8');

// Validación de seguridad para asegurar que la petición provenga de un usuario logueado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["status" => "error", "msg" => "Sesión expirada o no válida."]);
    exit;
}

/** @var array|null Captura y decodificación del payload JSON enviado vía fetch */
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(["status" => "error", "msg" => "Formato de datos no válido o vacío."]);
    exit;
}

/** @var DispositivosController Instancia principal del controlador de inventario */
$controller = new DispositivosController();

/** @var string Acción solicitada desde el frontend */
$peticion = $input['peticion'] ?? '';

/** @var string|int Identificador del submódulo origen para validación RBAC */
$id_sub = $input['id_sub'] ?? null;

/** @var string|int ID del dispositivo de red, requerido para actualizaciones y bajas */
$id_disp = $input['id_disp'] ?? null;

// Enrutador de peticiones AJAX
switch ($peticion) {
    
    /**
     * Caso: INSERT
     * Valida permisos y transfiere el payload completo (datos generales, interfaces y software)
     * al controlador para registrar un nuevo equipo.
     */
    case 'INSERT':
        if (!$controller->verificarPermiso($id_sub, PERM_AGREGAR)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para agregar nuevos dispositivos."]);
            exit;
        }
        $interfaces = $input['interfaces_red'] ?? [];
        $asignaciones = $input['asignaciones_software'] ?? [];
        echo json_encode($controller->insertarDispositivo($id_sub, $input, $interfaces, $asignaciones));
        break;

    /**
     * Caso: UPDATE
     * Valida permisos y actualiza la información general, interfaces de red (creación/edición)
     * y el inventario de software del equipo especificado.
     */
    case 'UPDATE':
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para editar dispositivos."]);
            exit;
        }
        if (empty($id_disp)) {
            echo json_encode(["status" => "error", "msg" => "ID de dispositivo requerido para actualizar."]);
            exit;
        }
        $interfaces = $input['interfaces_red'] ?? [];
        $asignaciones = $input['asignaciones_software'] ?? [];
        echo json_encode($controller->actualizarDispositivo($id_sub, $id_disp, $input, $interfaces, $asignaciones));
        break;

    /**
     * Caso: BAJA_DISP
     * Ejecuta la baja lógica de un dispositivo, inhabilitándolo en los catálogos activos.
     */
    case 'BAJA_DISP':
        if (!$controller->verificarPermiso($id_sub, PERM_ELIMINAR)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con los privilegios para dar de baja equipos."]);
            exit;
        }
        if (empty($id_disp) || empty($id_sub)) {
            echo json_encode(["status" => "error", "msg" => "Datos insuficientes para procesar la baja del dispositivo."]);
            exit;
        }
        echo json_encode($controller->bajaDispositivo($id_sub, $id_disp));
        break;

    /**
     * Caso: GET_VERSIONES_ACTUALIZAR
     * Devuelve el catálogo de versiones disponibles para un software específico, 
     * utilizado para poblar el modal de actualización rápida en la vista.
     */
    case 'GET_VERSIONES_ACTUALIZAR':
        $id_serv = $input['id_serv'] ?? null;
        if (empty($id_serv)) {
            echo json_encode(["status" => "error", "msg" => "ID de servicio no proporcionado."]);
            exit;
        }
        echo json_encode($controller->obtenerVersionesActualizar($id_serv));
        break;

    /**
     * Caso: UPDATE_VERSION_DISPOSITIVO
     * Realiza una actualización rápida de la versión de un software ya instalado 
     * en el dispositivo, sin modificar el resto de los parámetros.
     */
    case 'UPDATE_VERSION_DISPOSITIVO':
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) {
            echo json_encode(["status" => "error", "msg" => "Acceso denegado: No cuenta con permisos para modificar el software instalado."]);
            exit;
        }
        $id_serv = $input['id_serv'] ?? null;
        $id_ver_nueva = $input['id_ver_nueva'] ?? null;

        if (empty($id_disp) || empty($id_serv) || empty($id_ver_nueva)) {
            echo json_encode(["status" => "error", "msg" => "Faltan datos para actualizar la versión."]);
            exit;
        }
        echo json_encode($controller->actualizarVersionDispositivo($id_sub, $id_disp, $id_serv, $id_ver_nueva));
        break;

    default:
        // Manejo de peticiones corruptas
        echo json_encode(["status" => "error", "msg" => "Petición no reconocida o inválida."]);
        break;
}
?>