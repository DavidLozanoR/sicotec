<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author placeholdername1
 * @author placeholdername2 
 * @fecha 2026-02-25
 */

session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "ResponsivaController.php";

// Validación de seguridad para evitar accesos no autorizados sin sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

/** @var ResponsivaController Instancia del controlador de cartas responsivas y asignaciones */
$controller = new ResponsivaController();

/** @var string Petición de acción a ejecutar enviada vía POST */
$peticion = $_POST['peticion'] ?? '';

/** @var int ID del submódulo origen para registro en bitácora y validación de permisos */
$id_sub = (int)($_POST['id_sub'] ?? 0);

// Enrutador de peticiones AJAX
switch ($peticion) {
    
    /**
     * Caso: GET_EQUIPOS
     * Consulta y retorna la lista de dispositivos tecnológicos que están
     * vinculados (en uso o bajo resguardo) a un elemento del personal operativo.
     */
    case 'GET_EQUIPOS':
        $id_po = (int)($_POST['id_po'] ?? 0);
        
        if (empty($id_po)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó al usuario."]); 
            exit;
        }

        echo json_encode($controller->obtenerEquipos($id_sub, $id_po));
        break;

    /**
     * Caso: TRANSFERIR_EQUIPO
     * Procesa el cambio de asignación/responsiva de un equipo, quitándolo
     * del inventario del usuario origen y asignándolo al usuario destino.
     */
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
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida por el servidor."]);
        break;
}
?>