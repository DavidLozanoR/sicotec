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
	//Determinar el slug / accion / id del usuario
	$querystring = isset($_GET["querystring"]) ? $_GET["querystring"] : RUTA_DEFAULT;
	if ( !str_ends_with($querystring, "/") ) {
		$querystring = $querystring."/"; //le falta "/", se la pongo
	} 
	$peticion = explode("/", $querystring);
	$slug = isset($peticion[0]) ? $peticion[0] : "";
	$accion = isset($peticion[1]) ? $peticion[1] : "";
	$id_submod = isset($peticion[2]) ? $peticion[2] : "";
	$id_usrop = isset($peticion[3]) ? $peticion[3] : "";
	$id_disp = isset($peticion[3]) ? $peticion[3] : "";
	 $id_user = $_SESSION["usuario"]["id_usuar"];
	 $id_rol=$_SESSION["usuario"]["id_rol"]; //TODO: Verificar logica con requerimientos
	 $nom_user = $_SESSION["usuario"]["nom_usuar"]; //TODO: verificar logia con requerimientos
	//Procesar la peticion cargando el slug adecuado
	switch ($slug) {
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
				$ctrl = new PersonalOPController($slug);
			}
			if($accion=="formulario"){
				require_once "_controller/PersonalOPFormController.php";
				$ctrl = new PersonalOPFormController($slug,$id_submod,$id_usrop);//pasamos el Id del personal O y del submodulo Para saber en qué submodulo estamos
			}
		break;
		case "dispositivos":
            if($accion == ""){
                require_once "_controller/DispositivosController.php";
                $ctrl = new DispositivosController($slug);
            }
            if ($accion == "formulario") {
                require_once "_controller/DispositivosFormController.php";
                // Ahora $id_disp sí existe y tiene el valor correcto
                $ctrl = new DispositivosFormController($slug, $id_submod, $id_disp); 
            }
        break;
		case "red":
			if($accion == ""){
                require_once "_controller/RedController.php";
                $ctrl = new RedController($slug);
            }
		break;
		case "servicios":
			if($accion == ""){
                require_once "_controller/ServiciosController.php";
                $ctrl = new ServiciosController($slug);
            }
		break;
		case "responsables":
			if($accion == ""){
                require_once "_controller/ResponsivaController.php";
                $ctrl = new ResponsivaController($slug);
            }
		break;
		case "consultas":
			if($accion == ""){
                require_once "_controller/ConsultasController.php";
                $ctrl = new ConsultasController($slug);
            }
		break;
        default:
            
		break;
    }
	
	//Incluir la plantilla contenedora principal
	include "_view/master.html.php";
//hola
?>
