<?php

// Asegurar inicio de sesión si hay validaciones de seguridad posteriores
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/Global.php";
require_once "PersonalOPController.php";


$controller = new PersonalOPController();
$peticion = $_POST['peticion'] ?? '';

// Capturamos el ID del submódulo dinámicamente
$id_sub = $_POST['id_sub'] ?? '';

switch($peticion){

    


}









?>