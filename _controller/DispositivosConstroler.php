<?php
ini_set("display_errors", 1);
require_once "_model/MainModel.php";

class DispositivosController {

    private $id;
    const VISTA = '_view/dispositivos.html.php';
    const JS = 'js/dispositivos.js';

    function __construct($id=null){ 
        $this->id = $id;
        if(!isset($_SESSION['usuario']) && $this->id!=1){
            die("Error: Su usuario no tiene acceso a este módulo");
        }
    }

    function renderContent(){
        $model = new MainModel();
        
        $dispositivos = $model->seleccionaRegistrosJoin(
            "dispositivos d",
            [
                "LEFT JOIN infraestructura_red r ON d.id_disp = r.id_disp",
                "LEFT JOIN servicios s ON d.id_serv = s.id_serv"
            ],
            [
                "d.id_disp", "d.nom_host", "d.marca", "d.modelo", 
                "d.num_ser", "d.num_inv", "d.dom", "d.especs_hw", 
                "d.resp_po_id", "d.asig_po_id", "d.id_serv",
                "r.id_red", "r.ip", "r.mac", "s.nom as nom_servicio"
            ],
            "1=1"
        );

        $redes_disponibles = $model->seleccionaRegistros(
            "infraestructura_red", 
            ["id_red", "ip", "mac"], 
            "id_disp IS NULL OR id_disp = 0"
        );

        $servicios = $model->seleccionaRegistros(
            "servicios", 
            ["id_serv", "nom"]
        );

        include self::VISTA;
    }

    public function renderJS(){ 
        if(file_exists(self::JS)) {
            include self::JS; 
        }
    }
}
?>