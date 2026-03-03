<?php 

    //Redirigir directorios de inclusion de archivos 

    set_include_path(
        get_include_path() .
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/_model'.
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/_controller'.
        PATH_SEPARATOR . realpath(__DIR__ . '/..') . '/class'

    );

    //variables de entorno para el server en infinity FREe

    define("SITE_URL","http://localhost/Seguimiento-INEA/SiCoSoTe/");
    define("RUTA_DEFAULT", "inicio");
    define("DB_HOST", "sql306.infinityfree.com");
    define("DB_PORT", "3306");
    define("DB_BASE", "if0_41288649_sicosote");
    define("DB_USR", "if0_41288649");
    define("DB_PASS", "AzftNoLizRdWSI");
	define("CLAVE_SECRETA","test");

?>
