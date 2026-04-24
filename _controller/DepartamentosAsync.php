<?php

/**
 * Enrutador asíncrono para la gestión de la Estructura Organizacional.
 * Procesa peticiones AJAX para el catálogo de Departamentos.
 */

session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "DepartamentosController.php";

// Validación de seguridad para prevenir accesos no autorizados sin sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada. Por favor, vuelva a iniciar sesión."]);
    exit;
}

/** @var DepartamentosController Instancia del controlador de estructura organizacional */
$controller = new DepartamentosController();

/** @var string Petición de acción a ejecutar enviada vía POST */
$peticion = $_POST['peticion'] ?? '';

/** @var string|int ID del submódulo origen para validación de permisos y bitácora */
$id_sub = $_POST['id_sub'] ?? '';

// Enrutador de peticiones AJAX
switch ($peticion) {
    
    /**
     * Caso: INSERT_DEP
     * Registra un nuevo departamento en el sistema.
     * Nota: La clave del departamento es opcional.
     */
    case 'INSERT_DEP':
        $clave_dep = trim($_POST['clave_dep'] ?? '');
        $nom_dep   = trim($_POST['nom_dep'] ?? '');

        if (empty($nom_dep)) {
            echo json_encode(["result" => 0, "msg" => "El nombre del departamento es obligatorio."]); 
            exit;
        }

        echo json_encode($controller->insertarDepartamento($id_sub, $clave_dep, $nom_dep));
        break;

    /**
     * Caso: UPDATE_DEP
     * Actualiza la clave o el nombre de un departamento existente.
     */
    case 'UPDATE_DEP':
        $id_dep    = $_POST['id_dep'] ?? '';
        $clave_dep = trim($_POST['clave_dep'] ?? '');
        $nom_dep   = trim($_POST['nom_dep'] ?? '');

        if (empty($id_dep) || empty($nom_dep)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para la actualización."]); 
            exit;
        }

        echo json_encode($controller->actualizarDepartamento($id_sub, $id_dep, $clave_dep, $nom_dep));
        break;

    default:
        // Manejo de peticiones corruptas o no mapeadas
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida o acción no válida."]);
        break;
}
?>