<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mariadb
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

session_start();
require_once __DIR__ . "/../config/Global.php"; 
require_once "RedController.php";

// Validación de seguridad para evitar accesos no autorizados sin sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

/** @var RedController Instancia del controlador de infraestructura de red */
$controller = new RedController();

/** @var string Petición de acción a ejecutar enviada vía POST */
$peticion = $_POST['peticion'] ?? '';

/** @var string|int ID del submódulo origen para la validación de roles y permisos */
$id_sub = $_POST['id_sub'] ?? '';

// Enrutador de peticiones AJAX
switch ($peticion) {
    
    /**
     * Caso: INSERT_SEG
     * Registra un nuevo segmento de red asignado a una VLAN específica,
     * definiendo el rango de IPs (inicio y fin) y exclusiones (reservas).
     */
    case 'INSERT_SEG':
        if (!$controller->verificarPermiso($id_sub, PERM_AGREGAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para agregar."]); exit;
        }

        // CAMBIO: Capturamos id_vlan en lugar de id_dep
        $id_vlan = $_POST['id_vlan'] ?? '';
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');
        $reservas = trim($_POST['reservas'] ?? '');

        if (empty($id_vlan) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Por favor complete todos los campos obligatorios."]); exit;
        }

        echo json_encode($controller->insertarSegmento($id_sub, $id_vlan, $ip_ini, $ip_lim, $reservas));
        break;

    /**
     * Caso: UPDATE_SEG
     * Modifica los parámetros de un segmento de red existente (VLAN, límites y reservas).
     */
    case 'UPDATE_SEG':
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para editar."]); exit;
        }

        $id_seg = $_POST['id_seg'] ?? '';
        // CAMBIO: Capturamos id_vlan en lugar de id_dep
        $id_vlan = $_POST['id_vlan'] ?? '';
        
        // CAPTURAMOS LOS LIMITES PARA PERMITIR EDITAR RANGOS
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');
        $reservas = trim($_POST['reservas'] ?? '');

        if (empty($id_seg) || empty($id_vlan) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos para actualizar."]); exit;
        }
        
        echo json_encode($controller->actualizarSegmento($id_sub, $id_seg, $id_vlan, $ip_ini, $ip_lim, $reservas));
        break;

    /**
     * Caso: DELETE_SEG
     * Elimina un segmento de red y por cascada o lógica interna libera las IPs asociadas.
     */
    case 'DELETE_SEG':
        if (!$controller->verificarPermiso($id_sub, PERM_ELIMINAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado."]); exit;
        }
        $id_seg = $_POST['id_seg'] ?? '';
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento a eliminar."]); exit;
        }
        echo json_encode($controller->eliminarSegmento($id_sub, $id_seg));
        break;

    /**
     * Caso: GET_IPS
     * Recupera el listado detallado de IPs generadas para un segmento en particular.
     */
    case 'GET_IPS':
        $id_seg = $_POST['id_seg'] ?? '';
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento solicitado."]); exit;
        }
        echo json_encode($controller->obtenerIpsSegmento($id_seg));
        break;

    default:
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición no válida."]);
        break;
}
?>