<?php 

    // Configurar la zona horaria por defecto para todo el sistema
    date_default_timezone_set('America/Mexico_City');


    //Redirigir directorios de inclusion de archivos 

    set_include_path(
        get_include_path() .
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/_model'.
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/_controller'.
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/class'

    );

    

    //variables de entorno

    define("SITE_URL", "http://localhost/work/Seguimiento-INEA/Seguimiento INEA/sicosote/"); //importante que esto sea correcto; todo se basa en esto
    define("RUTA_DEFAULT", "inicio");
    define("DB_HOST", "localhost");
    define("DB_PORT", "3306");
    define("DB_BASE", "sicosote");
    define("DB_USR", "root");
    define("DB_PASS", "1234");
	define("CLAVE_SECRETA","test");


    //variables para permisos (modificar si es necesario)

    define("PERM_EDITAR", 1); 

    define("PERM_AGREGAR", 2); 

    define("PERM_ELIMINAR", 3); 

    define("PERM_CONSULTAR", 4); 

?>