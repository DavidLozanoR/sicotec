<?php

require_once "MainModel.php";

class TableroController {

    const VISTA = "_view/tableroPrincipal.html.php";
    const JS = "";
    //VISTA DE BAJO CONSTRUCCION
    const CONSTRUCCION ="_view/enConstruccion.html.php";

    private $id_rol; //consumimos id rol para consultar permisos 

    function __construct($id_rol){
        $this->id_rol=$id_rol;

    }

    public function renderContent(){
        if (self::VISTA != ""){
        include self::VISTA; }
        else{include self::CONSTRUCCION;}
    }

    public function renderJS(){
        //include self::JS;
    }

    //funciones para manejo de permisos

    public function buscarPermisos($id_rol){

        $model = new MainModel();

        //datos para consultar JOIN

        $tablaPrincipal = "Permisos P";
        $joins = [
            "JOIN Rol_Permiso rp ON p.Id_perm = rp.id_perm"
        ];
        $campos = ["p.nombre_permiso"];
        $condicion = "rp.Id_rol = ?";
        $params = $id_rol;

        $res = $model->seleccionaRegistrosJoin($tablaPrincipal,$joins,$campos,$condicion,$params);
        // Convertimos la matriz en un array simple: ['acceso_personal_OP', 'acceso_red']
        return $res ? array_column($res, 'nombre_permiso') : [];

    }
}
?>
