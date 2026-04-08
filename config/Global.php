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

 define("SITE_URL", "http://localhost/sico/Seguimiento-INEA/Seguimiento%20INEA/sicosote/");
    define("RUTA_DEFAULT", "inicio");
    define("DB_HOST", "192.168.250.24");
    define("DB_PORT", "5075");
    define("DB_BASE", "sicosote2");
    define("DB_USR", "bd_seguimiento");
    define("DB_PASS", "QEWoyA56xEse");
	define("CLAVE_SECRETA","test");

?>
