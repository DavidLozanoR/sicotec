<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mariadb
 * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas  
 * @fecha 2026-02-25
 */

require_once "MainModel.php";

/**
 * Controlador para el Tablero Principal (Dashboard).
 * Gestiona el renderizado de la interfaz inicial y la inyección 
 * de los scripts necesarios para la vista principal del sistema.
 */
class TableroController {

    /** @var string Ruta al archivo de la vista HTML del tablero */
    const VISTA = "_view/tableroPrincipal.html.php";
    
    /** @var string Ruta al archivo JavaScript asociado al tablero */
    const JS = "js/tablero.js";

    /**
     * Carga y renderiza el contenido de la vista principal del tablero.
     *
     * @return void Renderiza HTML directamente.
     */
    public function renderContent(){
        include self::VISTA;
    }

    /**
     * Carga e inyecta el script JavaScript asociado a la vista en el DOM.
     *
     * @return void
     */
    public function renderJS(){
        include self::JS;
    }

}

?>