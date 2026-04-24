<?php

/**
 * Controlador para la gestión de la Estructura Organizacional.
 * Maneja el catálogo de Departamentos y la consulta de Puestos existentes.
 * * @author Rodrigo Robledo Solares
 * @author Angel David Lozano Rojas
 */

require_once "MainModel.php";

class DepartamentosController {

    /** @var string Rutas de archivos */
    const VISTA = "_view/departamentos.html.php";
    const JS    = "js/departamentos.js";

    /** @var string|null Identificador para navegación */
    private $slug;
    
    /** @var int Constantes de permisos (RBAC) */
    const PERMISO_EDITAR    = 1;
    const PERMISO_AGREGAR   = 2;
    const PERMISO_ELIMINAR  = 3;
    const PERMISO_CONSULTAR = 4;

    public function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    /**
     * Renderiza el contenido principal validando permisos.
     */
    public function renderContent() {
        $model = new MainModel();
        // Se asume ID 3 para el submódulo de Departamentos/Estructura
        $id_sub = $_SESSION['submodulo_actual'] ?? 3; 

        if (!$this->verificarPermiso($id_sub, self::PERMISO_CONSULTAR)) {
            echo '<div class="container mt-5">
                    <div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger">
                        <h4 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                        <p>No cuenta con los privilegios necesarios para consultar este catálogo.</p>
                    </div>
                  </div>';
            return; 
        }

        $datos = $this->traerContenido($model);
        extract($datos);
        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    /**
     * Valida permisos contra la tabla rol_submodulo_permiso.
     */
    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $model->seleccionaRegistros(
            "rol_submodulo_permiso", 
            ["id_perm"], 
            "id_rol = ? AND id_sub = ? AND id_perm = ?", 
            [$id_rol, $id_sub, $id_permiso]
        );
        return ($permiso && count($permiso) > 0);
    }

    /**
     * Obtiene los datos de departamentos y puestos únicos.
     */
    private function traerContenido($model) {
        // 1. Departamentos (Tabla existente)
        $departamentos = $model->seleccionaRegistros("departamentos", ["*"], "1=1 ORDER BY nom_dep ASC");
        
        // 2. Puestos (Consulta dinámica para evitar crear tablas nuevas)
        // Obtenemos los puestos únicos que ya están asignados al personal operativo
        $puestosRaw = $model->consultarLibre("SELECT DISTINCT puesto FROM personal_operativo WHERE puesto IS NOT NULL AND puesto != '' ORDER BY puesto ASC");

        return [
            'departamentos' => $departamentos ?: [],
            'puestos'       => $puestosRaw ?: []
        ];
    }

    // ==========================================================
    // LÓGICA DE DEPARTAMENTOS
    // ==========================================================
    
    public function insertarDepartamento($id_sub, $clave_dep, $nom_dep) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, self::PERMISO_AGREGAR)) {
            return ["result" => 0, "msg" => "No tiene permiso para agregar departamentos."];
        }

        // VALIDACIÓN DE UNICIDAD: La clave no debe repetirse
        if (!empty($clave_dep)) {
            $existe = $model->seleccionaRegistros("departamentos", ["id_dep"], "clave_dep = ?", [$clave_dep]);
            if ($existe) {
                return ["result" => 0, "msg" => "La clave '$clave_dep' ya está asignada a otro departamento."];
            }
        }

        $res = $model->agregaRegistroConBitacora(
            "departamentos", 
            ["clave_dep", "nom_dep"], 
            [$clave_dep, $nom_dep],
            $id_sub, "Creó departamento: $nom_dep"
        );

        return $res ? ["result" => 1, "msg" => "Departamento guardado."] : ["result" => 0, "msg" => "Error de BD."];
    }

    public function actualizarDepartamento($id_sub, $id_dep, $clave_dep, $nom_dep) {
        $model = new MainModel();

        if (!$this->verificarPermiso($id_sub, self::PERMISO_EDITAR)) {
            return ["result" => 0, "msg" => "No tiene permiso para editar."];
        }

        // VALIDACIÓN DE UNICIDAD: La clave no debe estar en otro registro
        if (!empty($clave_dep)) {
            $existe = $model->seleccionaRegistros("departamentos", ["id_dep"], "clave_dep = ? AND id_dep != ?", [$clave_dep, $id_dep]);
            if ($existe) {
                return ["result" => 0, "msg" => "La clave '$clave_dep' ya pertenece a otro departamento."];
            }
        }

        $res = $model->actualizaRegistroConBitacora(
            "departamentos", 
            ["clave_dep", "nom_dep"], 
            "id_dep = ?", 
            [$clave_dep, $nom_dep, $id_dep],
            $id_sub, "Editó departamento ID: $id_dep"
        );

        return $res ? ["result" => 1, "msg" => "Cambios aplicados."] : ["result" => 0, "msg" => "Sin cambios o error."];
    }
}