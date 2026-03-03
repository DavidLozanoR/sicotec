<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Mysql
 * El servidor de hosteo de esta rama es infinity free, asi que se modifica para usarlo, en produccion no se usa este servidor
 * @author Robledo Solares Rodrigo Servicio Social 
 * @fecha 2026-02-25
 */
 


class MariaDBAux {
    private $server;
    private $port;
    private $db;
    private $user;
    private $pass;
    
    public function __construct($server, $port, $db, $user, $pass) {
        $this->server = $server;
        $this->port = $port;
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
    }
    
    /**
     * Establece la conexión PDO para MariaDB/MySQL.
     */
    private function getConnection() {
        try {
            // El DSN cambia de 'sqlsrv' a 'mysql'
            $dsn = "mysql:host=" . $this->server . ";port=" . $this->port . ";dbname=" . $this->db . ";charset=utf8mb4";
            
            $cnx = new PDO($dsn, $this->user, $this->pass);
            
            // Configuración estándar de seguridad y errores
            $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $cnx->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $cnx;
        } catch (PDOException $ex) {
            error_log("Error de conexión MariaDB: " . $ex->getMessage());
            return null;
        }
    }

    /**
     * SELECT: Devuelve matriz de datos.
     */
    public function selectRows($tabla, $campos, $condicion = null, $params = null) {
        $strCampos = implode(',', $campos);
        $query = "SELECT $strCampos FROM $tabla" . ($condicion ? " WHERE $condicion" : "");
        
        try {
            $cnx = $this->getConnection();
            if ($cnx === null) return null;

            $pcmd = $cnx->prepare($query);
            if ($params) {
                foreach ($params as $index => $param) {
                    $pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
                }
            }
            
            $pcmd->execute();
            $res = $pcmd->fetchAll(); // Más eficiente que el bucle while para MariaDB

            $pcmd = null;
            $cnx = null;
            return $res;
        } catch (PDOException $ex) {
            error_log("Error en selectRows: " . $ex->getMessage());
            return null;
        }
    }

    /**
     * INSERT: Inserta fila y devuelve el ID generado.
     */
    public function insertRow($tabla, $campos, $params) {
        $strCampos = implode(',', $campos);
        $strParams = rtrim(str_repeat('?,', count($campos)), ',');

        $query = "INSERT INTO $tabla ($strCampos) VALUES ($strParams)";

        try {
            $cnx = $this->getConnection();
            $cnx->beginTransaction();

            $pcmd = $cnx->prepare($query);
            foreach ($params as $index => $param) {
                $pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
            }

            $pcmd->execute();
            $lastInsertId = $cnx->lastInsertId();

            $cnx->commit();
            $pcmd = null;
            $cnx = null;

            return $lastInsertId;
        } catch (PDOException $ex) {
            if (isset($cnx) && $cnx->inTransaction()) $cnx->rollBack();
            error_log("Error en insertRow: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * UPDATE: Actualiza fila con base en condición.
     */
    public function updateRow($tabla, $campos, $condicion, $params) {       
        $strCampos = implode('=?, ', $campos) . '=?';
        $query = "UPDATE $tabla SET $strCampos" . ($condicion ? " WHERE $condicion" : "");

        try {
            $cnx = $this->getConnection();
            $cnx->beginTransaction();

            $pcmd = $cnx->prepare($query);
            foreach ($params as $index => $param) {
                $pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
            }

            $success = $pcmd->execute();
            $cnx->commit();

            $pcmd = null;
            $cnx = null;
            return $success;
        } catch (PDOException $ex) {
            if (isset($cnx) && $cnx->inTransaction()) $cnx->rollBack();
            error_log("Error en updateRow: " . $ex->getMessage());
            return false;
        }
    }

    /**
     * SELECT JOIN: Consulta compleja entre tablas.
     */
    public function selectJoin($tablaPrincipal, $joins, $campos, $condicion = null, $params = null) {
        $strCampos = implode(', ', $campos);
        $strJoins = implode(' ', $joins);
        $query = "SELECT $strCampos FROM $tablaPrincipal $strJoins" . ($condicion ? " WHERE $condicion" : "");

        try {
            $cnx = $this->getConnection();
            if ($cnx === null) return null;

            $pcmd = $cnx->prepare($query);
            if ($params) {
                foreach ($params as $index => $param) {
                    $pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
                }
            }

            $pcmd->execute();
            $res = $pcmd->fetchAll();

            $pcmd = null;
            $cnx = null;
            return $res;
        } catch (PDOException $ex) {
            error_log("Error en selectJoin: " . $ex->getMessage());
            return null;
        }
    }
}