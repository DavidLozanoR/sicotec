<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// HABILITAR EL USO DE VARIABLES SESION
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "LoginModel.php";

$model = new LoginModel();

// 1. Intercept Logout Request
if (isset($_POST["action"]) && $_POST["action"] === "logout") {
    if ($model->logout()) {
        echo json_encode(["result" => 1, "msg" => "Sesion finalizada"]);
    } else {
        echo json_encode(["result" => 0, "msg" => "Error al finalizar sesion"]);
    }
    exit;
}

// 2. Process Login Request (Default Execution)
$user = isset($_POST["usr"]) ? $_POST["usr"]  : "";
$pass = isset($_POST["pass"]) ? $_POST["pass"] : "";

// Validacion de los datos ingresados
    // Llamamos al modelo y guardamos su respuesta 
    $respuestaLogin = $model->createSesion($user, $pass);

    if ($respuestaLogin["status"]){
        echo json_encode(["result" => 1, "msg" => $respuestaLogin["msg"]]);
    } else {
        echo json_encode(["result" => 0, "msg" => $respuestaLogin["msg"]]);
    }