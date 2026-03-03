<?php
//se llama a la clase de coneccion de mariadb en lugar de sql
require_once "MariadbAux.php";

class MainModel {
    private $bd;

    public function __construct() {
        // Se instancia una sola vez para ser reutilizada en todos los métodos
        $this->bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);
    }
    
    public function seleccionaRegistros($tabla, $campos, $condicion = null, $params = null) {
        return $this->bd->selectRows($tabla, $campos, $condicion, $params);
    }
    
    public function agregaRegistro($tabla, $campos, $params) {
        // Devuelve true si el ID generado es mayor a 0
        return $this->bd->insertRow($tabla, $campos, $params) > 0;
    }

    public function actualizaRegistro($tabla, $campos, $condicion, $params) {
        return $this->bd->updateRow($tabla, $campos, $condicion, $params);
    }
    
    public function seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion = null, $params = null) {
        return $this->bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
    }
}