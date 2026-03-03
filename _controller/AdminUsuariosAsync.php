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

// Verificamos permisos (Solo el Rol 1 puede administrar usuarios)
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["id_rol"] != 1) {
    echo json_encode(["result" => 0, "msg" => "No tienes permiso para realizar esta acción"]);
    die();
}

// Recepción de datos del formulario (Modal)
$peticion = isset($_POST['peticion']) ? $_POST['peticion'] : ""; 
$id_usuar = isset($_POST['id_usuar']) ? $_POST['id_usuar'] : null;
$nom_usuar = isset($_POST['nom_usuar']) ? $_POST['nom_usuar'] : "";
$nombres = isset($_POST['nombres']) ? $_POST['nombres'] : "";
$ape_pa = isset($_POST['ape_pa']) ? $_POST['ape_pa'] : "";
$ape_ma = isset($_POST['ape_ma']) ? $_POST['ape_ma'] : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";
$id_rol = isset($_POST['id_rol']) ? $_POST['id_rol'] : "";
$permisos = isset($_POST['permisos']) ? $_POST['permisos'] : []; // Arreglo con los checkboxes marcados

// Instanciamos el Controlador para usar sus métodos
$ctrl = new AdminUsuariosController($_SESSION["usuario"]["id_rol"]);

// Procesamiento con Switch (Igual a tu arquitectura de referencia)
switch ($peticion) {
    
    case "INSERT":
        if (!$ctrl->validaAtributos($nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol) || empty($password)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: Todos los campos marcados con * son obligatorios."]);
        } 
        else if ($ctrl->insertaUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos)) {
            echo json_encode(["result" => 1, "msg" => "Usuario registrado correctamente."]);
        } else {
            echo json_encode(["result" => 0, "msg" => "ERROR: Problema al guardar el usuario en la BD."]);
        }
        break;

    case "UPDATE":
        if (!$ctrl->validaAtributos($nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol)) {
            echo json_encode(["result" => 0, "msg" => "ERROR: Datos inválidos. Revise los campos obligatorios."]);
        } 
        else if ($ctrl->actualizaUsuario($id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos)) {
            echo json_encode(["result" => 1, "msg" => "Usuario actualizado correctamente."]);
        } else {
            echo json_encode(["result" => 0, "msg" => "ERROR: Problema al actualizar en BD."]);
        }
        break;
    case "DELETE": // Baja del usuario
            if (empty($id_usuar)) {
                echo json_encode(["result" => 0, "msg" => "ERROR: Identificador de usuario no proporcionado."]);
            } 
            else if ($ctrl->bajaUsuario($id_usuar)) {
                echo json_encode(["result" => 1, "msg" => "Usuario dado de baja exitosamente del sistema."]);
            } else {
                echo json_encode(["result" => 0, "msg" => "ERROR: Hubo un problema al procesar la baja en la base de datos."]);
            }
            break;
    default:
        echo json_encode(["result" => 0, "msg" => "ERROR: Petición inválida."]);
        die();
}
?>