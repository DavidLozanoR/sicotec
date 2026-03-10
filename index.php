<?php 

	//verificar sesion valida
	session_start();

	ini_set("display_errors", E_ALL);

	require_once "config/Global.php";


	if(!isset($_SESSION["token"])|| $_SESSION["token"]!==CLAVE_SECRETA){
		//incluir vista de login
		include "_view/login.html.php";
		die();
    	//fin de verificación de sesion
	}
	//Determinar el controlador / accion / id del usuario
	$querystring = isset($_GET["querystring"]) ? $_GET["querystring"] : RUTA_DEFAULT;
	if ( !str_ends_with($querystring, "/") ) {
		$querystring = $querystring."/"; //le falta "/", se la pongo
	} 
	$peticion = explode("/", $querystring);
	$controlador = isset($peticion[0]) ? $peticion[0] : "";
	$accion = isset($peticion[1]) ? $peticion[1] : "";
	$id = isset($peticion[2]) ? $peticion[2] : "";
	 $id_user = $_SESSION["usuario"]["id_usuar"];
	 $id_rol=$_SESSION["usuario"]["id_rol"]; //TODO: Verificar logica con requerimientos
	 $nom_user = $_SESSION["usuario"]["nom_usuar"]; //TODO: verificar logia con requerimientos
	//Procesar la peticion cargando el controlador adecuado
	switch ($controlador) {
        case "inicio" :
            if ($accion == "") {
                require_once "_controller/TableroController.php";
                $ctrl = new TableroController();
            } 
        break;

        case "usuarios":
            if ($accion == "") {
                require_once "_controller/AdminUsuariosController.php";
                $ctrl = new AdminUsuariosController($id_user);
            }   
        break;

		case "personal":
			if($accion== ""){
				require_once "_controller/PersonalOPController.php";
				$ctrl = new PersonalOPController();
			}

        default:
            
		break;
    }
	
	//Incluir la plantilla contenedora principal
	include "_view/master.html.php";
//hola
?>
