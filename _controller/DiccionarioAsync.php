<?php
// Asegurar inicio de sesión si hay validaciones de seguridad posteriores
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

// Leer payload JSON de la petición Fetch
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$modelo = new MainModel();

switch ($action) {
    case 'leer':
        $datos = $modelo->seleccionaRegistros('Diccionario_Nomenclaturas', ['Id_nom', 'nom', 'signif', 'notas']);
        echo json_encode(['status' => 'success', 'data' => $datos]);
        break;

    case 'insertar':
        // Insertar valores por defecto (Opción A solicitada)
        $exito = $modelo->agregaRegistro(
            'Diccionario_Nomenclaturas', 
            'nom, signif, notas', 
            ['Nueva', 'Significado', 'Notas']
        );
        
        if ($exito) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al insertar el registro.']);
        }
        break;

    case 'actualizar':
        $id = $input['Id_nom'] ?? null;
        $campo = $input['campo'] ?? '';
        $valor = $input['valor'] ?? '';

        // Validación estricta del nombre de la columna permitida (Prevención SQLi)
        $camposPermitidos = ['nom', 'signif', 'notas'];
        if (!$id || !in_array($campo, $camposPermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $exito = $modelo->actualizaRegistro(
            'Diccionario_Nomenclaturas', 
            "$campo = ?", 
            "Id_nom = ?", 
            [$valor, $id]
        );

        if ($exito) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el registro.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no definida.']);
        break;
}