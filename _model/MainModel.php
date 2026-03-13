<?php
require_once __DIR__ . "/../config/Global.php";
require_once "MariaDBAux.php";

/**
 * MainModel Class
 * Centralizes database operations and integrates the automated Audit Log (Bitacora) system.
 */
class MainModel {
    private $bd;

    public function __construct() {
        /** Initialize the database abstraction layer once per instance */
        $this->bd = new MariaDBAux(DB_HOST, DB_PORT, DB_BASE, DB_USR, DB_PASS);
    }

    /* ==========================================================================
       CORE DATABASE METHODS
       ========================================================================== */

    public function seleccionaRegistros($tabla, $campos, $condicion = null, $params = null) {
        return $this->bd->selectRows($tabla, $campos, $condicion, $params);
    }

    public function seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion = null, $params = null) {
        return $this->bd->selectJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
    }

    public function agregaRegistro($tabla, $campos, $params) {
        return $this->bd->insertRow($tabla, $campos, $params) > 0;
    }

    public function actualizaRegistro($tabla, $campos, $condicion, $params) {
        return $this->bd->updateRow($tabla, $campos, $condicion, $params);
    }

    public function borraRegistro($tabla, $condicion, $params) {
        return $this->bd->deleteRow($tabla, $condicion, $params);
    }

    /* ==========================================================================
       AUDIT LOG (BITACORA) INTEGRATION
       ========================================================================== */

    /**
     * Registers a system action in the Bitacora table.
     * @param int $id_sub The submodule ID where the action occurred.
     * @param string $accion The operation type ('INSERT', 'UPDATE', 'DELETE', etc.).
     * @param string $detalle Specific description of the change.
     * @return bool|string True on success, or a detailed error message.
     */
    public function registrarBitacora($id_sub, $accion, $detalle) {
        $id_usuar = $_SESSION['usuario']['id_usuar'] ?? null;

        if (!$id_usuar || empty($id_sub)) {
            return "ATENCIÓN: Error de sesión. La acción no pudo ser registrada en Bitácora.";
        }

        date_default_timezone_set('America/Mexico_City');
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        try {
            $exito = $this->agregaRegistro(
                "Bitacora", 
                ["id_usuar", "id_sub", "accion", "detalle", "fecha", "hora"], 
                [$id_usuar, $id_sub, $accion, $detalle, $fecha, $hora]
            );
            return $exito ?: "ATENCIÓN: Fallo al registrar entrada en Bitácora.";
        } catch (Exception $e) {
            return "ATENCIÓN: Excepción de base de datos durante el registro de auditoría."; 
        }
    }

    /**
     * Wraps an INSERT operation with automatic Bitacora logging.
     * @param string $tabla Target table.
     * @param array|string $campos Columns to populate.
     * @param array $params Values for the insertion.
     * @param int $id_sub Submodule ID for audit association.
     * @param string $detalle Human-readable log description.
     * @return int|bool The new Primary Key ID on success, false otherwise.
     */
    public function agregaRegistroConBitacora($tabla, $campos, $params, $id_sub, $detalle) {
        $camposArray = is_string($campos) ? explode(',', $campos) : $campos;
        // Clean spaces if it was a string like 'nom, signif'
        $camposArray = array_map('trim', $camposArray);

        $id_generado = $this->bd->insertRow($tabla, $camposArray, $params);
        
        if ($id_generado > 0) {
            $this->registrarBitacora($id_sub, 'INSERT', "$detalle (ID Asignado: $id_generado)");
            return $id_generado;
        }
        return false;
    }

    /**
     * Wraps an UPDATE operation with automatic Bitacora logging.
     * @param string $tabla Target table.
     * @param array|string $campos The column names to update.
     * @param string $condicion The WHERE clause (e.g., "id = ?").
     * @param array $params Values for SET and WHERE clauses.
     * @param int $id_sub Submodule ID for audit association.
     * @param string $detalle Human-readable log description.
     * @return bool True if update was successful.
     */
    public function actualizaRegistroConBitacora($tabla, $campos, $condicion, $params, $id_sub, $detalle) {
        // FIX: Ensure $campos is an array for MariaDBAux->updateRow compatibility
        $camposArray = is_string($campos) ? explode(',', $campos) : $campos;
        $camposArray = array_map('trim', $camposArray);

        $exito = $this->bd->updateRow($tabla, $camposArray, $condicion, $params);
        
        if ($exito) {
            $this->registrarBitacora($id_sub, 'UPDATE', $detalle);
            return true;
        }
        return false;
    }

    /**
     * Wraps a DELETE operation with automatic Bitacora logging.
     * @param string $tabla Target table.
     * @param string $condicion The WHERE clause.
     * @param array $params Values for the WHERE clause.
     * @param int $id_sub Submodule ID for audit association.
     * @param string $detalle Human-readable log description.
     * @return bool True if deletion was successful.
     */
    public function borraRegistroConBitacora($tabla, $condicion, $params, $id_sub, $detalle) {
        $exito = $this->bd->deleteRow($tabla, $condicion, $params);
        if ($exito) {
            $this->registrarBitacora($id_sub, 'DELETE', $detalle);
            return true;
        }
        return false;
    }

    /* ==========================================================================
       UTILITY METHODS
       ========================================================================== */

    /**
     * Resolves a submodule primary key by its unique slug identifier.
     * Avoids hardcoding IDs in controllers to maintain system portability.
     * @param string $slug Unique slug defined in the 'Submodulos' table.
     * @return int|null The id_sub if found, otherwise null.
     */
    public function obtenerIdSubmoduloPorSlug($slug) {
        $res = $this->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", [$slug]);
        return ($res && count($res) > 0) ? (int)$res[0]['id_sub'] : null;
    }
}