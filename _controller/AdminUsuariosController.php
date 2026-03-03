<?php
	ini_set("display_errors", E_ALL);

require_once "MainModel.php";

class AdminUsuariosController {

    private $id;
    private $admin;

    public $id_user;
    private $pass_hash;
    public $id_rol;
    public $nom_user;
    public $ap_pa;
    public $ap_ma;
    
    const VISTA = '_view/adminUsuarios.html.php';
    const JS = 'js/adminUsuarios.js';

    function __construct($id=null){ 
        $this->id = $id;


         if(!isset($_SESSION['usuario']) && $this->id!=1){//solo acceso a administrador
            
            die("Error: Su usuario no tiene acceso a este módulo");
        }

    }

    /*function renderContent(){
        $model=new MainModel;
        //solo usuarios activos, status == 1 
       // Preparamos el JOIN para traer los datos del usuario + el nombre de su rol
        $tablaPrincipal = "Usuarios u";
        $joins = [
            "JOIN Roles r ON u.id_rol = r.id_rol"
        ];
        $campos = [
            "u.id_usuar", 
            "u.nom_usuar", 
            "u.nombres", 
            "u.ape_pa", 
            "u.ape_ma", 
            "r.nom_rol" // Traemos el nombre en texto del rol
        ];
        
        // Filtramos solo los usuarios activos (borrado lógico)
        $condicion = "u.status = ?";
        $params = [1];
        
        // Ejecutamos la consulta y la guardamos en $usuarios
        $usuarios = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
        
        //incluimos vista
        include self::VISTA;


    }*/
    

    function renderContent(){
        $model = new MainModel();
        
        // 1. Obtener catálogos para llenar el Modal (Select y Checkboxes)
        // (Ajusta los nombres de las tablas y campos según tu BD real)
        $roles_disponibles = $model->seleccionaRegistros("Roles", ["id_rol", "nom_rol"]);
        $permisos_disponibles = $model->seleccionaRegistros("Permisos", ["id_perm", "nom_perm"]);

        // 2. Preparamos el JOIN para traer los datos del usuario + el nombre de su rol
        $tablaPrincipal = "Usuarios u";
        $joins = [
            "JOIN Roles r ON u.id_rol = r.id_rol"
        ];
        $campos = [
            "u.id_usuar", 
            "u.nom_usuar", 
            "u.nombres", 
            "u.ape_pa", 
            "u.ape_ma", 
            "u.id_rol",  // <- ¡AGREGADO! Es vital para que el botón "Editar" sepa qué rol preseleccionar
            "r.nom_rol"  // Traemos el nombre en texto del rol para la tabla
        ];
        
        // Filtramos solo los usuarios activos (borrado lógico)
        $condicion = "u.status = ?";
        $params = [1];
        
        // Ejecutamos la consulta y la guardamos en $usuarios
        $usuarios = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
        
        // 3. (Opcional pero recomendado) Buscar los permisos de cada usuario para la tabla
        if ($usuarios) {
            foreach ($usuarios as $index => $usr) {
                // Aquí consultas la tabla puente (ej. Rol_Permiso o Usuario_Permiso)
                // Para obtener los permisos en texto (para la tabla) y en IDs (para el Modal)
                $permisosUsuario = $model->seleccionaRegistrosJoin(
                    "Rol_Permiso rp", // Asumiendo que vienen ligados por el rol
                    ["JOIN Permisos p ON rp.id_perm = p.id_perm"],
                    ["p.id_perm", "p.nom_perm"],
                    "rp.id_rol = ?",
                    [$usr['id_rol']]
                );

                $nombres_permisos = [];
                $ids_permisos = [];

                if ($permisosUsuario) {
                    foreach ($permisosUsuario as $perm) {
                        $nombres_permisos[] = $perm['nom_perm'];
                        $ids_permisos[] = $perm['id_perm'];
                    }
                }

                // Guardamos en el arreglo del usuario para que la vista los imprima
                $usuarios[$index]['permisos_lista'] = !empty($nombres_permisos) ? implode(", ", $nombres_permisos) : "Sin permisos asignados";
                $usuarios[$index]['permisos_ids'] = $ids_permisos; // Arreglo [1, 3, 4] para las palomitas
            }
        }

        // Incluimos vista
        include self::VISTA;
    }


    public function renderJS(){
        include self::JS;
    }

    // --- LÓGICA DE NEGOCIO PARA USUARIOS ---

    public function validaAtributos($nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol) {
        // Verifica que ningún campo obligatorio esté vacío
        if (empty($nom_usuar) || empty($nombres) || empty($ape_pa) || empty($ape_ma) || empty($id_rol)) {
            return false;
        }
        return true;
    }

    public function insertaUsuario($nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos) {
        $model = new MainModel();
        
        // Encriptar la contraseña antes de guardarla
        $pass_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // 1. Insertar en la tabla Usuarios
        $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol", "status"];
        $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, 1];
        
        $res = $model->agregaRegistro("Usuarios", $campos, $params);
        
        // TODO: Aquí agregaremos la lógica para registrar los $permisos en la tabla puente
        
        return $res;
    }

    public function actualizaUsuario($id_usuar, $nom_usuar, $nombres, $ape_pa, $ape_ma, $password, $id_rol, $permisos) {
        $model = new MainModel();
        
        if (!empty($password)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "pass_hash", "id_rol"];
            // El ID ($id_usuar) va al final del arreglo
            $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $pass_hash, $id_rol, $id_usuar];
        } else {
            $campos = ["nom_usuar", "nombres", "ape_pa", "ape_ma", "id_rol"];
            // El ID ($id_usuar) va al final del arreglo
            $params = [$nom_usuar, $nombres, $ape_pa, $ape_ma, $id_rol, $id_usuar];
        }

        $res = $model->actualizaRegistro("Usuarios", $campos, "id_usuar=?", $params);
        
        // TODO: Aquí actualizaremos los $permisos en la tabla puente

        return $res;
    }

    // Y ya puedes agregar tu función para dar de baja
    public function bajaUsuario($id_usuar) {
        $model = new MainModel();
        // Borrado lógico 
        return $model->actualizaRegistro("Usuarios", ["status"], "id_usuar=?", [0, $id_usuar]);
        
    }






}





?>