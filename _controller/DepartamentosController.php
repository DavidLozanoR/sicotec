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

        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
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
    /**
     * Obtiene los datos enriquecidos con el contador de empleados.
     */
    private function traerContenido($model) {
        // 1. Departamentos (Subconsulta para contar empleados activos)
        $queryDeptos = "
            SELECT d.*, 
                   (SELECT COUNT(id_po) FROM personal_operativo po WHERE po.id_dep = d.id_dep AND po.estatus = 1) AS total_empleados
            FROM departamentos d 
            ORDER BY d.nom_dep ASC
        ";
        $departamentos = $model->consultarLibre($queryDeptos);
        
        // 2. Puestos (Agrupamos y contamos directamente del personal)
        $queryPuestos = "
            SELECT puesto, COUNT(id_po) AS total_empleados 
            FROM personal_operativo 
            WHERE puesto IS NOT NULL AND puesto != '' AND estatus = 1 
            GROUP BY puesto 
            ORDER BY puesto ASC
        ";
        $puestosRaw = $model->consultarLibre($queryPuestos);

        return [
            'departamentos' => $departamentos ?: [],
            'puestos'       => $puestosRaw ?: []
        ];
    }

    // ... (Mantén insertarDepartamento y actualizarDepartamento igual) ...

    /**
     * Elimina físicamente un departamento solo si está vacío.
     */
    public function eliminarDepartamento($id_sub, $id_dep) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, PERM_ELIMINAR)) {
            return ["result" => 0, "msg" => "No tiene permiso para eliminar registros."];
        }

        // VALIDACIÓN CRÍTICA: Verificar que no tenga empleados activos o inactivos atados a él
        $empleados = $model->seleccionaRegistros("personal_operativo", ["id_po"], "id_dep = ?", [$id_dep]);
        
        if ($empleados && count($empleados) > 0) {
            return ["result" => 0, "msg" => "Operación denegada. Hay " . count($empleados) . " empleado(s) vinculado(s) a este departamento."];
        }

        // Borrado Físico Seguro
        $res = $model->borraRegistroConBitacora(
            "departamentos", 
            "id_dep = ?", 
            [$id_dep],
            $id_sub, "Eliminó definitivamente el departamento ID: $id_dep"
        );

        return $res ? ["result" => 1, "msg" => "Departamento eliminado correctamente."] : ["result" => 0, "msg" => "Error al intentar eliminar de la base de datos."];
    }

    // ==========================================================
    // LÓGICA DE DEPARTAMENTOS
    // ==========================================================
    
    public function insertarDepartamento($id_sub, $clave_dep, $nom_dep) {
        $model = new MainModel();
        
        if (!$this->verificarPermiso($id_sub, PERM_AGREGAR)) {
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

        if (!$this->verificarPermiso($id_sub, PERM_EDITAR)) {
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
    /**
     * Valida las reglas de negocio para los datos de un departamento.
     * 
     * @param string $clave_dep Clave numérica (opcional).
     * @param string $nom_dep Nombre del departamento (obligatorio).
     * @return bool|string True si pasa la validación, o un string con el mensaje de error.
     */
    public function validarAtributos($clave_dep, $nom_dep) {
        if (empty($nom_dep)) {
            return "El nombre del departamento es obligatorio.";
        }

        if (mb_strlen($nom_dep) < 5 || mb_strlen($nom_dep) > 80) {
            return "El nombre del departamento debe tener entre 5 y 80 caracteres.";
        }

        if (!preg_match('/^[A-ZÁÉÍÓÚÑa-záéíóúñ\s]+$/', $nom_dep)) {
            return "El nombre solo puede contener letras y espacios.";
        }

        if (!empty($clave_dep) && !preg_match('/^[0-9]+$/', $clave_dep)) {
            return "La clave del departamento solo debe contener números.";
        }

        if ($clave_dep == 0){
            return "La clave del departamento debe ser mayor a 0";
        }
        if ($clave_dep == null){
            return "La clave del departamento no puede ser nula";
        }

        if (mb_strlen($clave_dep) < 1 || mb_strlen($clave_dep) > 20) {
            return "La clave del departamento debe tener entre 1 y 20 caracteres.";
        }

        return true;
    }
}