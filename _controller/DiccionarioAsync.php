<?php
/**
 * DiccionarioAsync Controller
 * Handles asynchronous CRUD operations for the Nomenclature Dictionary.
 * Integrates with the global Audit System (Bitacora) via MainModel.
 */

require_once __DIR__ . "/../config/Global.php";
require_once "MainModel.php";

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$modelo = new MainModel();

/**
 * Resolve context: Fetch the submodule ID dynamically using the module slug.
 * This ensures audit logs are correctly attributed without hardcoded IDs.
 */
$id_sub_diccionario = $modelo->obtenerIdSubmoduloPorSlug('diccionario');

if (!$id_sub_diccionario) {
    echo json_encode(['status' => 'error', 'message' => 'Contexto de submódulo no encontrado.']);
    exit;
}

switch ($action) {
    case 'leer':
        /** Fetch records ordered by ID descending to show newest entries at the top of the UI */
        $datos = $modelo->seleccionaRegistros('Diccionario_Nomenclaturas', ['Id_nom', 'nom', 'signif', 'notas'], '1=1 ORDER BY Id_nom DESC');
        echo json_encode(['status' => 'success', 'data' => $datos]);
        break;

    case 'insertar':
        /** Creates a template record and logs the event */
        $exito = $modelo->agregaRegistroConBitacora(
            'Diccionario_Nomenclaturas', 
            'nom, signif, notas', 
            ['Nueva', 'Significado', 'Notas'],
            $id_sub_diccionario,
            "Creación de nueva entrada en el diccionario"
        );
        echo json_encode(['status' => $exito ? 'success' : 'error']);
        break;

    case 'actualizar':
        /** * Partial update logic: Updates a specific column for a given ID.
         * Validates column names against a whitelist to prevent SQL injection.
         */
        $id = $input['Id_nom'] ?? null;
        $campo = $input['campo'] ?? '';
        $valor = $input['valor'] ?? '';

        $camposPermitidos = ['nom', 'signif', 'notas'];
        if (!$id || !in_array($campo, $camposPermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Atributos de actualización inválidos.']);
            exit;
        }

        $exito = $modelo->actualizaRegistroConBitacora(
            'Diccionario_Nomenclaturas', 
            "$campo = ?", 
            "Id_nom = ?", 
            [$valor, $id],
            $id_sub_diccionario,
            "Modificó campo [$campo] en la nomenclatura ID: $id"
        );
        echo json_encode(['status' => $exito ? 'success' : 'error']);
        break;
}