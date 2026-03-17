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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$modelo = new MainModel();
$id_sub_diccionario = $modelo->obtenerIdSubmoduloPorSlug('diccionario');

// If this triggers, the server won't be empty anymore; it will tell you the problem.
if (!$id_sub_diccionario) {
    echo json_encode(['status' => 'error', 'message' => 'Submodule slug "diccionario" not found in database.']);
    exit;
}

switch ($action) {
    case 'leer':
        $datos = $modelo->seleccionaRegistros('Diccionario_Nomenclaturas', ['Id_nom', 'nom', 'signif', 'notas'], '1=1 ORDER BY Id_nom DESC');
        $datos = $datos ? $datos : ['error'];
        echo json_encode(['status' => 'success', 'data' => $datos]);
        break;

    case 'insertar':
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
}