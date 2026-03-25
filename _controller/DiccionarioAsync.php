<?php
// Add this at the top to debug silent failures
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

// ==========================================================
// VALIDACIÓN ESTRICTA DE SESIÓN
// ==========================================================
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada o no válida.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$modelo = new MainModel();
$id_sub_diccionario = $modelo->obtenerIdSubmoduloPorSlug('diccionario');

// If this triggers, the server won't be empty anymore; it will tell you the problem.
if (!$id_sub_diccionario) {
    echo json_encode(['status' => 'error', 'message' => 'Submodule slug "diccionario" not found in database.']);
    exit;
}

// ==========================================================
// FUNCIÓN DE VERIFICACIÓN DE PERMISOS (RBAC)
// ==========================================================
$verificarPermiso = function($id_permiso) use ($modelo, $id_sub_diccionario) {
    $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
    if (!$id_rol) return false;
    
    $permiso = $modelo->seleccionaRegistros(
        "rol_submodulo_permiso", 
        ["id_perm"], 
        "id_rol = ? AND id_sub = ? AND id_perm = ?", 
        [$id_rol, $id_sub_diccionario, $id_permiso]
    );
    return ($permiso && count($permiso) > 0);
};

// ==========================================================
// PROCESAMIENTO DE ACCIONES
// ==========================================================
switch ($action) {
    case 'leer':
        // Validación Permiso 4 = Consultar
        if (!$verificarPermiso(4)) {
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No cuenta con permisos para consultar nomenclaturas.']);
            exit;
        }
        $datos = $modelo->seleccionaRegistros('Diccionario_Nomenclaturas', ['Id_nom', 'nom', 'signif', 'notas'], '1=1 ORDER BY Id_nom DESC');
        // Usamos array vacío en vez de ['error'] para que DataTables o tu frontend no falle al iterar
        $datos = $datos ? $datos : []; 
        echo json_encode(['status' => 'success', 'data' => $datos]);
        break;

    case 'insertar':
        // Validación Permiso 2 = Agregar / Insertar
        if (!$verificarPermiso(2)) {
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No cuenta con permisos para agregar nomenclaturas.']);
            exit;
        }

        $exito = $modelo->agregaRegistroConBitacora(
            'Diccionario_Nomenclaturas', 
            ['nom', 'signif', 'notas'], // Array of column names
            ['Nueva', 'Significado', 'Notas'],
            $id_sub_diccionario,
            "Nueva nomenclatura"
        );
        echo json_encode(['status' => $exito ? 'success' : 'error']);
        break;
    
    case 'actualizar':
        // Validación Permiso 1 = Editar / Actualizar
        if (!$verificarPermiso(1)) {
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No cuenta con permisos para editar nomenclaturas.']);
            exit;
        }

        $id = $input['Id_nom'] ?? null;
        $campo = $input['campo'] ?? '';
        $valor = $input['valor'] ?? '';

        $camposPermitidos = ['nom', 'signif', 'notas'];
        if (!$id || !in_array($campo, $camposPermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $exito = $modelo->actualizaRegistroConBitacora(
            'Diccionario_Nomenclaturas', 
            [$campo], // Array containing only the column name to update
            "Id_nom = ?", 
            [$valor, $id],
            $id_sub_diccionario,
            "Actualizó $campo"
        );
        echo json_encode(['status' => $exito ? 'success' : 'error']);
        break;

    case 'borrar':
        // Validación Permiso 3 = Eliminar / Dar de Baja
        if (!$verificarPermiso(3)) {
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: No cuenta con los privilegios para eliminar nomenclaturas.']);
            exit;
        }

        $id = $input['Id_nom'] ?? null;
        $exito = $modelo->borraRegistroConBitacora(
            'Diccionario_Nomenclaturas', 
            'Id_nom = ?', 
            [$id],
            $id_sub_diccionario,
            "Eliminó ID: $id"
        );
        echo json_encode(['status' => $exito ? 'success' : 'error']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o no especificada.']);
        break;
}