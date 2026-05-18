<?php
/**
 * Controlador para la gestión del catálogo de Marcas y Modelos.
 * @author Angel David Lozano Rojas
 * @author Rodrigo Robledo Solares
 */

require_once "MainModel.php";

class MarcasModelosController {

    const VISTA = "_view/marcasModelos.html.php";
    const JS    = "js/marcasModelos.js";
    private $slug;

    public function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    public function renderContent() {
        $model = new MainModel();

        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug) ?? 1;

        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger m-4">
                    <h4 class="fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                    <p>No cuenta con privilegios para acceder al catálogo de Marcas y Modelos.</p>
                  </div>';
            return; 
        }

        $marcas = $model->seleccionaRegistros("marcas", ["id_marca", "nom_marca"], "estatus = 1 ORDER BY nom_marca ASC");
        
        $puede_editar = $this->verificarPermiso($id_sub, PERM_EDITAR) ? true : false;
        $puede_eliminar = $this->verificarPermiso($id_sub, PERM_ELIMINAR) ? true : false;

        include self::VISTA;
    }

    public function renderJS() {
        if (file_exists(self::JS)) { include self::JS; }
    }

    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    // ==========================================
    // METODOS ASÍNCRONOS (Llamados desde Async)
    // ==========================================

    public function guardarMarca($id_sub, $nom_marca, $id_marca = null) {
        $model = new MainModel();
        // Limpieza estricta backend
        $nom_marca = trim(preg_replace('/\s+/', ' ', strtoupper($nom_marca)));

        // Validaciones de seguridad
        if(empty($nom_marca) || preg_match('/^\d+$/', $nom_marca)) {
            return ["status" => "error", "msg" => "El nombre no es válido. No puede estar vacío ni contener solo números."];
        }
        if(strlen($nom_marca) > 25) return ["status" => "error", "msg" => "El nombre supera el límite permitido de 25 caracteres."];

        if (!empty($id_marca)) {
            // Lógica de EDICIÓN (UPDATE)
            $existe = $model->seleccionaRegistros("marcas", ["id_marca"], "nom_marca = ? AND id_marca != ? AND estatus = 1", [$nom_marca, $id_marca]);
            if (!empty($existe)) return ["status" => "error", "msg" => "Ya existe otra marca con ese nombre."];

            $exito = $model->actualizaRegistroConBitacora("marcas", ["nom_marca"], "id_marca = ?", [$nom_marca, $id_marca], $id_sub, "Edición de marca a: $nom_marca");
            return $exito ? ["status" => "success", "msg" => "Marca actualizada correctamente."] : ["status" => "error", "msg" => "Error al actualizar la marca."];
        } else {
            // Lógica de NUEVO (INSERT)
            $existe = $model->seleccionaRegistros("marcas", ["id_marca"], "nom_marca = ? AND estatus = 1", [$nom_marca]);
            if (!empty($existe)) return ["status" => "error", "msg" => "La marca ya existe en el catálogo."];

            $id = $model->agregaRegistroConBitacora("marcas", ["nom_marca", "estatus"], [$nom_marca, 1], $id_sub, "Alta de nueva marca: $nom_marca");
            return $id ? ["status" => "success", "msg" => "Marca registrada correctamente."] : ["status" => "error", "msg" => "Error al registrar la marca."];
        }
    }

    public function obtenerModelosPorMarca($id_marca) {
        $model = new MainModel();
        $modelos = $model->seleccionaRegistros("modelos", ["id_modelo", "nom_modelo"], "id_marca = ? AND estatus = 1 ORDER BY nom_modelo ASC", [$id_marca]);
        return ["status" => "success", "data" => $modelos ?: []];
    }

    public function guardarModelo($id_sub, $id_marca, $nom_modelo, $id_modelo = null) {
        $model = new MainModel();
        // Limpieza estricta backend
        $nom_modelo = trim(preg_replace('/\s+/', ' ', strtoupper($nom_modelo)));

        // Validaciones de seguridad
        if(empty($nom_modelo) || preg_match('/^\d+$/', $nom_modelo)) {
            return ["status" => "error", "msg" => "El nombre no es válido. No puede estar vacío ni contener solo números."];
        }
        if(strlen($nom_modelo) > 25) return ["status" => "error", "msg" => "El nombre supera el límite permitido de 25 caracteres."];

        if (!empty($id_modelo)) {
            // Lógica de EDICIÓN (UPDATE)
            $existe = $model->seleccionaRegistros("modelos", ["id_modelo"], "id_marca = ? AND nom_modelo = ? AND id_modelo != ? AND estatus = 1", [$id_marca, $nom_modelo, $id_modelo]);
            if (!empty($existe)) return ["status" => "error", "msg" => "Este modelo ya existe para la marca seleccionada."];

            $exito = $model->actualizaRegistroConBitacora("modelos", ["nom_modelo"], "id_modelo = ?", [$nom_modelo, $id_modelo], $id_sub, "Edición de modelo a: $nom_modelo");
            return $exito ? ["status" => "success", "msg" => "Modelo actualizado correctamente."] : ["status" => "error", "msg" => "Error al actualizar el modelo."];
        } else {
            // Lógica de NUEVO (INSERT)
            $existe = $model->seleccionaRegistros("modelos", ["id_modelo"], "id_marca = ? AND nom_modelo = ? AND estatus = 1", [$id_marca, $nom_modelo]);
            if (!empty($existe)) return ["status" => "error", "msg" => "Este modelo ya existe para la marca seleccionada."];

            $id = $model->agregaRegistroConBitacora("modelos", ["id_marca", "nom_modelo", "estatus"], [$id_marca, $nom_modelo, 1], $id_sub, "Alta de nuevo modelo: $nom_modelo");
            return $id ? ["status" => "success", "msg" => "Modelo registrado correctamente."] : ["status" => "error", "msg" => "Error al registrar el modelo."];
        }
    }

    public function eliminarRegistro($id_sub, $tabla, $columna_id, $id_registro) {
        $model = new MainModel();
        // Baja lógica
        $exito = $model->actualizaRegistroConBitacora($tabla, ["estatus"], "$columna_id = ?", [0, $id_registro], $id_sub, "Baja lógica en $tabla. ID: $id_registro");
        return $exito ? ["status" => "success", "msg" => "Registro eliminado correctamente."] : ["status" => "error", "msg" => "Error al intentar eliminar el registro."];
    }
}
?>