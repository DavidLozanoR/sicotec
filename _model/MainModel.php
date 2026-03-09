<?php
require_once __DIR__ . "/../config/Global.php";
require_once "MariaDBAux.php";
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
    public function borraRegistro($tabla, $condicion, $params) {
		return $this->bd->deleteRow($tabla, $condicion, $params);
	}

    
    // FUNCIÓN PARA REGISTRAR ACCIONES EN LA BITÁCORA
    /**
     * @param int $id_sub El ID del submódulo donde ocurre la acción (Obligatorio).
     * @param string $accion El tipo de acción ('INSERT', 'UPDATE', 'DELETE', 'REACTIVATE').
     * @param string $detalle Descripción detallada de los datos afectados.
     * @return bool|string Devuelve true en caso de éxito, o un string con el mensaje de error.
     */
    public function registrarBitacora($id_sub, $accion, $detalle) {
        // Obtenemos el ID del usuario actual de la sesión
        $id_usuar = $_SESSION['usuario']['id_usuar'] ?? null;

        // Validamos que exista una sesión activa y que se haya especificado el submódulo
        if (!$id_usuar || empty($id_sub)) {
            return "ATENCIÓN: Hubo un error de sesión y no pudo guardarse en la Bitácora. Por favor, contacte al Administrador del sistema.";
        }

        // Generamos la fecha y hora desde PHP
        date_default_timezone_set('America/Mexico_City'); // Aseguramos la zona horaria correcta
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        try {
            // Utilizamos tu propio método agregaRegistro
            $exito = $this->agregaRegistro(
                "Bitacora", 
                ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
                [$id_usuar, $id_sub, $accion, $detalle, $fecha, $hora]
            );
            
            if ($exito) {
                return true; // Se guardó correctamente
            } else {
                return "ATENCIÓN: La acción principal se completó, pero el sistema falló al intentar registrarla en la Bitácora. Por favor, contacte al Administrador del sistema.";
            }
        } catch (Exception $e) {
            // Capturamos la excepción y enviamos la alerta
            return "ATENCIÓN: Ocurrió un fallo en la base de datos al guardar la auditoría. Por favor, contacte al Administrador del sistema."; 
        }
    }
}