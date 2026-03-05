<?php
ini_set("display_errors", E_ALL);
require_once "../config/Global.php";
require_once "AdminUsuariosController.php";

// Solo aceptamos peticiones POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["result" => 0, "msg" => "Método no permitido"]);
    die();
}

session_start();

// Solo el Rol 1 (Admin) puede administrar roles
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["id_rol"] != 1) {
    echo json_encode(["result" => 0, "msg" => "No tienes permiso para realizar esta acción"]);
    die();
}

// Recepción de datos
$peticion = isset($_POST['peticion']) ? $_POST['peticion'] : "";
$id_rol   = isset($_POST['id_rol'])   ? $_POST['id_rol']   : null;
$nom_rol  = isset($_POST['nom_rol'])  ? $_POST['nom_rol']  : "";
$permisos = isset($_POST['permisos']) ? $_POST['permisos'] : []; // Arreglo de checkboxes

$ctrl = new AdminRolesController($_SESSION["usuario"]["id_rol"]);

switch ($peticion) {

    case "GET_PERMISOS_ROL":
        if (empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ID de rol no proporcionado"]);
        } else {
            $permisos_del_rol = $ctrl->obtenerPermisosPorRol($id_rol);
            echo json_encode(["result" => 1, "permisos" => $permisos_del_rol]);
        }
        break;

    case "INSERT":
        if (!$ctrl->validaAtributos($nom_rol, $permisos)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: El nombre del rol es obligatorio y los permisos deben ser válidos."]);
        } else if ($ctrl->insertaRol($nom_rol, $permisos)) {
            echo json_encode(["result" => 1, "msg" => "Rol creado correctamente."]);
        } else {
            echo json_encode(["result" => 0, "msg" => "ERROR: No se pudo guardar el rol en la base de datos."]);
        }
        break;

    case "UPDATE":
        if (empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: Identificador de rol no proporcionado."]);
        } else if (!$ctrl->validaAtributos($nom_rol, $permisos)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: Datos inválidos. Revise los campos obligatorios."]);
        } else if ($ctrl->actualizaRol($id_rol, $nom_rol, $permisos)) {
            echo json_encode(["result" => 1, "msg" => "Rol actualizado correctamente."]);
        } else {
            echo json_encode(["result" => 0, "msg" => "ERROR: No se pudo actualizar el rol. Puede estar protegido."]);
        }
        break;

    case "DELETE":
        if (empty($id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: Identificador de rol no proporcionado."]);
        } else {
            // eliminaRol devuelve el arreglo con result y msg directamente
            $resultado = $ctrl->eliminaRol($id_rol);
            echo json_encode($resultado);
        }
        break;

    default:
        echo json_encode(["result" => 0, "msg" => "ERROR: Petición inválida."]);
        die();
}
?>