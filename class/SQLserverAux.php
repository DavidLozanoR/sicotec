<?php

/**
 * Rutinas comunes para CRUD elemental de una BD. Versión Maria DB
 *
 * @author Lozano Rojas Angel David Servicio Social 
 * @fecha 2026-02-25
 */
 
class SQLserverAux {
	private $server;
	private $port;
	private $db;
	private $user;
	private $pass;
	
	public function __construct($server, $port,$db, $user,$pass) {
		$this->server = $server;
		$this->port = $port;
	    $this->db = $db;
	    $this->user = $user;
	    $this->pass = $pass;
	}
	
	/**
     * Devuelve 'viva' la conexión a BD. PDO la cierra cuando el script termina. Igualarla a null la cierra manualmente. 
     */
private function getConnection($cnx=null) {
		
        try {
            // Se incluye el puerto y los parámetros de cifrado necesarios para el Driver 18
            $dsn = "sqlsrv:Server=" . $this->server . "," . $this->port . ";Database=" . $this->db . ";Encrypt=yes;TrustServerCertificate=yes";
            
            $cnx = new PDO($dsn, $this->user, $this->pass);
            
            // Configuración para capturar excepciones SQL
            $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $cnx->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            
            return $cnx;
        } catch (PDOException $ex) {
            // Registra el error técnico para depuración en el servidor
            error_log("Error de conexión SQL Server (ODBC 18): " . $ex->getMessage());
            return null;
        }
    }

    /**
     * Devuelve una simple matriz de datos, la condición omite la palabra clave WHERE y es parametrizada. 
     * La conexión se abre, usa y cierra.
     * @param string $tabla
     * @param array $campos
     * @param string|null $condicion
     * @param array|null $params
     * @return array|null
     */
    public function selectRows ($tabla, $campos, $condicion = null, $params = null) {
        $res = [];

		$strCampos = implode(',', $campos); // Une los campos con comas
        $query = "SELECT $strCampos FROM $tabla" . ($condicion ? " WHERE $condicion" : "");
        

		
        try{
            $cnx = $this->getConnection();
            if ($cnx === null) {
            // Si la conexión es null (por falta de driver), salimos sin tronar
            error_log("Error: No se pudo recuperar la conexión en selectRows.");
            return null; 
        }
			$pcmd = $cnx->prepare($query);
            if ($params) {
				foreach ($params as $index => $param) {
					$pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
				}
			}
            
            $pcmd->execute();

			while ($row = $pcmd->fetch(PDO::FETCH_ASSOC)) {
				$res[] = $row; // Cada fila es un arreglo asociativo
			}

			$pcmd = null; // Cierra el statement/command
			$cnx = null; // Cierra la conexión

			return $res;
		} catch (PDOException $ex) {
			error_log("Error al ejecutar la consulta: " . $ex->getMessage());
			return null;
		}
    }

    /**
	 * Inserta una fila en la tabla especificada y devuelve el ID generado automáticamente.
	 *
	 * @param string $tabla
	 * @param array $campos
	 * @param array $params
	 * @return int|false Devuelve el ID generado o false en caso de error.
	 */
	public function insertRow($tabla, $campos, $params) {
		$strCampos = implode(',', $campos); // Une los campos con comas
		$strParams = rtrim(str_repeat('?,', count($campos)), ','); // Genera una lista de placeholders

		$query = "INSERT INTO $tabla ($strCampos) VALUES ($strParams)";

		try {
			$cnx = $this->getConnection();

			// Desactiva el autocommit para manejo manual de transacciones.
			$cnx->beginTransaction();

			$pcmd = $cnx->prepare($query);

			foreach ($params as $index => $param) {
				$pcmd->bindValue($index + 1, $param, PDO::PARAM_STR); // Vincula cada parámetro.
			}

			$pcmd->execute();
			$lastInsertId = $cnx->lastInsertId(); // Obtén el ID generado.

			$cnx->commit(); // Confirma la transacción.

			$pcmd = null; // Cierra el statement.
			$cnx = null; // Cierra la conexión.

			return $lastInsertId;
		} catch (PDOException $ex) {
			// Si ocurre un error, hacemos rollback.
			if ($cnx->inTransaction()) {
				$cnx->rollBack();
			}
			error_log("Error al insertar la fila: " . $ex->getMessage());
			return false;
		}
	}


    /**
	 * Inserta una matriz de registros en una tabla especificada.
	 *
	 * @param string $tabla
	 * @param array $campos
	 * @param array $valores
	 * @return bool Devuelve true si todas las filas se insertaron correctamente, false en caso contrario.
	 */
	public function insertRows($tabla, $campos, $valores) {
		$strCampos = implode(',', $campos); // Une los nombres de los campos con comas.
		$strParams = rtrim(str_repeat('?,', count($campos)), ','); // Genera placeholders (?,?...) para los valores.

		$query = "INSERT INTO $tabla ($strCampos) VALUES ($strParams)";

		try {
			$cnx = $this->getConnection();

			// Desactiva el autocommit para manejo manual de transacciones.
			$cnx->beginTransaction();

			$pcmd = $cnx->prepare($query);
			$success = true;

			foreach ($valores as $fila) {
				foreach ($fila as $index => $valor) {
					$pcmd->bindValue($index + 1, $valor, PDO::PARAM_STR); // Vincula cada valor.
				}
				$success = $success && ($pcmd->execute() ? true : false); // Verifica cada ejecución.
				$pcmd->closeCursor(); // Limpia los parámetros para la siguiente iteración.
			}

			$cnx->commit(); // Confirma la transacción si todo salió bien.
			return $success;
		} catch (PDOException $ex) {
			// Si ocurre un error, hacemos rollback.
			if ($cnx->inTransaction()) {
				$cnx->rollBack();
			}
			error_log("Error al insertar filas: " . $ex->getMessage());
			return false;
		} finally {
			// Limpia los recursos abiertos.
			$pcmd = null;
			$cnx = null;
		}
	}

    /**
	 * Actualiza una fila en la tabla especificada con base en los campos y la condición proporcionados.
	 *
	 * @param string $tabla
	 * @param array $campos
	 * @param string|null $condicion
	 * @param array $params
	 * @return bool Devuelve true si la actualización fue exitosa, false en caso contrario.
	 */
	public function updateRow($tabla, $campos, $condicion, $params) {		
		// Construye la parte de SET con los campos.
		$strCampos = implode('=?, ', $campos) . '=?';
		$query = "UPDATE $tabla SET $strCampos" . ($condicion ? " WHERE $condicion" : "");

		try {
			$cnx = $this->getConnection();

			// Desactiva el autocommit para manejo manual de transacciones.
			$cnx->beginTransaction();

			$pcmd = $cnx->prepare($query);

			// Vincula los parámetros en el orden correcto.
			foreach ($params as $index => $param) {
				$pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
			}

			$success = $pcmd->execute(); // Ejecuta la consulta.
			$cnx->commit(); // Confirma la transacción.

			$pcmd = null; // Cierra el statement.
			$cnx = null; // Cierra la conexión.

			return $success;
		} catch (PDOException $ex) {
			// Si ocurre un error, hacemos rollback.
			if ($cnx->inTransaction()) {
				$cnx->rollBack();
			}
			error_log("Error al actualizar la fila: " . $ex->getMessage());
			return false;
		}
	}


    /**
	 * Actualiza múltiples filas en una tabla con base en los campos, valores y condición proporcionados.
	 *
	 * @param string $tabla
	 * @param array $campos
	 * @param array $valores Matriz de valores a actualizar (cada subarray representa una fila).
	 * @param string|null $condicion
	 * @param array|null $params Parámetros para la condición.
	 * @return bool Devuelve true si todas las filas se actualizaron correctamente, false si hubo algún error.
	 */
	public function updateRows($tabla, $campos, $valores, $condicion = null, $params = []) {
		// Construye la parte de SET con los campos.
		$strCampos = implode('=?, ', $campos) . '=?';
		$query = "UPDATE $tabla SET $strCampos" . ($condicion ? " WHERE $condicion" : "");

		try {
			$cnx = $this->getConnection();

			// Desactiva el autocommit para manejo manual de transacciones.
			$cnx->beginTransaction();

			$pcmd = $cnx->prepare($query);
			$resultado = true;

			foreach ($valores as $fila) {
				$indice = 1;

				// Vincula los valores de la fila actual.
				foreach ($fila as $valor) {
					$pcmd->bindValue($indice++, $valor, PDO::PARAM_STR);
				}

				// Vincula los parámetros de la condición si existen.
				foreach ($params as $param) {
					$pcmd->bindValue($indice++, $param, PDO::PARAM_STR);
				}

				// Ejecuta la actualización.
				$resultado = $resultado && $pcmd->execute();

				// Limpia los parámetros para la siguiente iteración.
				$pcmd->closeCursor();
			}

			// Confirma la transacción si todas las actualizaciones fueron exitosas.
			if ($resultado) {
				$cnx->commit();
			} else {
				$cnx->rollBack();
			}

			$pcmd = null; // Cierra el statement.
			$cnx = null; // Cierra la conexión.

			return $resultado;
		} catch (PDOException $ex) {
			// Si ocurre un error, hacemos rollback.
			if ($cnx->inTransaction()) {
				$cnx->rollBack();
			}
			error_log("Error al actualizar las filas: " . $ex->getMessage());
			return false;
		}
	}


	/**
     * Realiza una consulta con JOINs entre múltiples tablas y devuelve una matriz de datos.
     * Permite una estructura más clara para consultas complejas de permisos o relaciones.
     * * @param string $tablaPrincipal Tabla base para el FROM (ej: "Usuarios u").
     * @param array $joins Arreglo de uniones (ej: ["JOIN Roles r ON u.rol_id = r.Id_rol", "LEFT JOIN..."]).
     * @param array $campos Arreglo de campos a seleccionar (ej: ["u.nombre_usuario", "r.nombre_rol"]).
     * @param string|null $condicion Condición para el WHERE sin la palabra clave (ej: "u.Id_user = ?").
     * @param array|null $params Parámetros para la vinculación (Placeholders).
     * @return array|null Matriz asociativa o null en caso de fallo.
     */
    public function selectJoin($tablaPrincipal, $joins, $campos, $condicion = null, $params = null) {
        $res = [];
        $strCampos = implode(', ', $campos);
        $strJoins = implode(' ', $joins);
        
        // Construcción de la sentencia SQL
        $query = "SELECT $strCampos FROM $tablaPrincipal $strJoins" . ($condicion ? " WHERE $condicion" : "");

        try {
            $cnx = $this->getConnection();
            if ($cnx === null) {
                error_log("Error: No se pudo recuperar la conexión en selectJoin.");
                return null;
            }

            $pcmd = $cnx->prepare($query);

            if ($params) {
                foreach ($params as $index => $param) {
                    $pcmd->bindValue($index + 1, $param, PDO::PARAM_STR);
                }
            }

            $pcmd->execute();

            while ($row = $pcmd->fetch(PDO::FETCH_ASSOC)) {
                $res[] = $row;
            }

            // Cierre manual de recursos según tu estándar
            $pcmd = null;
            $cnx = null;

            return $res;
        } catch (PDOException $ex) {
            error_log("Error en selectJoin: " . $ex->getMessage());
            return null;
        }
    }
    
}