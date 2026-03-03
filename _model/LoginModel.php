<?php
require_once "MariadbAux.php";

class LoginModel{

public function createSesion($usr, $pass) {
    
    $bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);

    // 1. Configurar la consulta con JOINs según tu diagrama ER
    $tablaPrincipal = "Usuarios U";
    $joins = [
        "INNER JOIN Roles R ON U.Id_rol = R.Id_rol",
        "LEFT JOIN Rol_Permiso RP ON R.Id_rol = RP.Id_rol",
        "LEFT JOIN Permisos P ON RP.Id_perm = P.Id_perm"
    ];
    $campos = [
        "U.Id_usuar", 
        "U.nom_usuar", 
        "U.pass_hash", 
        "U.Id_rol", 
        "U.nombres", 
        "U.ape_pa", 
        "U.ape_ma", 
        "R.nom_rol", // Traemos el nombre del rol directamente
        "P.nom_perm" // Traemos el nombre del permiso
    ];
    $condicion = "U.nom_usuar = ?"; // Podrías agregar " AND U.status = 1" si manejas bajas lógicas
    $params = [$usr];

    // 2. Ejecutar consulta utilizando tu función selectJoin
    $resultado = $bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);

    // Si hay resultados (al menos una fila)
    if ($resultado && count($resultado) > 0) {
        
        // Extraer el hash de la primera fila (se repite en todas, así que basta con la [0])
        $hashAlmacenado = $resultado[0]["pass_hash"];

        // Todo: Eliminar esta línea cuando se implemente el password_verify
        if ($pass === $hashAlmacenado) {
        // if (password_verify($pass, $hashAlmacenado)) {
            
            $_SESSION["token"] = CLAVE_SECRETA;
            
            // 3. Crear el arreglo de usuario limpio (sin pass_hash y agrupado)
            $_SESSION["usuario"] = [
                "Id_usuar"  => $resultado[0]["Id_usuar"],
                "nom_usuar" => $resultado[0]["nom_usuar"],
                "Id_rol"    => $resultado[0]["Id_rol"],
                "nom_rol"   => $resultado[0]["nom_rol"], // Ya tenemos el nombre real del rol!
                "nombres"   => $resultado[0]["nombres"],
                "ape_pa"    => $resultado[0]["ape_pa"],
                "ape_ma"    => $resultado[0]["ape_ma"]
            ];

            // 4. Recorrer las filas devueltas para recolectar todos los permisos en un solo array
            $permisos = [];
            foreach ($resultado as $fila) {
                // Verificamos que no sea nulo (por si un rol no tiene permisos asignados)
                if (!empty($fila["nom_perm"])) {
                    $permisos[] = $fila["nom_perm"];
                }
            }
            $_SESSION["permisos"] = $permisos;

            return true;
        }
    }
    // Credenciales inválidas o usuario no encontrado
    return false;
}
	//TODO: Modificar logica para el registro de los Usuarios
	/* public function registerUser($nombre_completo,$email,$pass,$edad,$id_perfil){
		$pass_hash=password_hash($pass,PASSWORD_DEFAULT);
		$bd = new SQLserverAux(DB_HOST,DB_PORT, DB_BASE, DB_USR, DB_PASS);
		return $bd->insertRow("usuarios",["nombre","email","contrasena","edad","id_perfil"],[$nombre_completo,$email,$pass_hash,$edad,$id_perfil]);
	} */


}





?>
