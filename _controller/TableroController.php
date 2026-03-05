<?php

require_once "MainModel.php";

class TableroController {

    const VISTA = "_view/tableroPrincipal.html.php";
    const JS = "js/tablero.js";



    public function renderContent(){
        include self::VISTA;
    }

    public function renderJS(){
        include self::JS;
    }

    







}





?>
