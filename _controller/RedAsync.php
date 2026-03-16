<?php
session_start();
// Asegúrate de que la ruta a Global.php sea la correcta según la estructura de tu proyecto
require_once __DIR__ . "/../config/Global.php"; 
require_once "RedController.php";

// Validación de seguridad básica: Evitar acceso si no hay sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

$controller = new RedController();

// Recibimos qué acción quiere hacer el frontend y desde qué submódulo
$peticion = $_POST['peticion'] ?? '';
$id_sub = $_POST['id_sub'] ?? '';

switch ($peticion) {
    
    // -------------------------------------------------------------------
    // 1. CREAR NUEVO SEGMENTO
    // -------------------------------------------------------------------
    case 'INSERT_SEG':
        // Validamos permiso 2: Agregar
        if (!$controller->verificarPermiso($id_sub, 2)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para agregar segmentos."]); 
            exit;
        }

        $id_dep = $_POST['id_dep'] ?? '';
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');

        if (empty($id_dep) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios del formulario."]); 
            exit;
        }

        echo json_encode($controller->insertarSegmento($id_sub, $id_dep, $ip_ini, $ip_lim));
        break;

    // -------------------------------------------------------------------
    // 2. ACTUALIZAR SEGMENTO EXISTENTE
    // -------------------------------------------------------------------
    case 'UPDATE_SEG':
        // Validamos permiso 1: Editar
        if (!$controller->verificarPermiso($id_sub, 1)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para editar segmentos."]); 
            exit;
        }

        $id_seg = $_POST['id_seg'] ?? '';
        $id_dep = $_POST['id_dep'] ?? '';
        $ip_ini = trim($_POST['ip_ini'] ?? '');
        $ip_lim = trim($_POST['ip_lim'] ?? '');

        if (empty($id_seg) || empty($id_dep) || empty($ip_ini) || empty($ip_lim)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para la actualización."]); 
            exit;
        }

        echo json_encode($controller->actualizarSegmento($id_sub, $id_seg, $id_dep, $ip_ini, $ip_lim));
        break;

    // -------------------------------------------------------------------
    // 3. DESACTIVAR (BORRADO LÓGICO) SEGMENTO
    // -------------------------------------------------------------------
    case 'DELETE_SEG':
        // Validamos permiso 3: Eliminar
        if (!$controller->verificarPermiso($id_sub, 3)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No tiene permisos para eliminar o desactivar segmentos."]); 
            exit;
        }

        $id_seg = $_POST['id_seg'] ?? '';
        
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento a eliminar."]); 
            exit;
        }

        echo json_encode($controller->eliminarSegmento($id_sub, $id_seg));
        break;

    // -------------------------------------------------------------------
    // 4. OBTENER IPs PARA EL MODAL EXPLORADOR
    // -------------------------------------------------------------------
    case 'GET_IPS':
        // Para consultar IPs no exigimos un permiso estricto de escritura, 
        // pero puedes agregar la validación del permiso 4 (Consultar) si lo deseas.
        $id_seg = $_POST['id_seg'] ?? '';
        
        if (empty($id_seg)) {
            echo json_encode(["result" => 0, "msg" => "No se identificó el segmento solicitado."]); 
            exit;
        }

        echo json_encode($controller->obtenerIpsSegmento($id_seg));
        break;

    // -------------------------------------------------------------------
    // POR DEFECTO
    // -------------------------------------------------------------------
    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida por el servidor."]);
        break;
}
?>