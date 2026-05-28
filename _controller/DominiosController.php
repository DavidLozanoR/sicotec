<?php
/**
 * Controlador para la gestión del catálogo de Dominios Institucionales.
 * @author Angel David Lozano Rojas
 */

require_once "MainModel.php";

class DominiosController {

    /** @var string Ruta a la vista correspondiente */
    const VISTA = "_view/dominios.html.php";
    
    /** @var string Ruta al archivo JS correspondiente */
    const JS    = "js/dominios.js";
    
    /** @var string|null Slug identificador del submódulo */
    private $slug;

    /**
     * Constructor del controlador.
     * Valida la existencia de una sesión activa.
     * * @param string|null $slug Slug del submódulo.
     */
    public function __construct($slug = null) { 
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['usuario'])) { die("Error: Sesión expirada."); }
        $this->slug = $slug;
    }

    /**
     * Renderiza el contenido principal de la vista del catálogo.
     */
    public function renderContent() {
        $model = new MainModel();

        // Obtener el ID del submódulo de manera dinámica por su slug
        $id_sub = $model->obtenerIdSubmoduloPorSlug($this->slug) ?? 12; // Fallback por si acaso

        // 1. Verificación del permiso de consulta (Base de seguridad)
        if (!$this->verificarPermiso($id_sub, PERM_CONSULTAR)) {
            echo '<div class="alert alert-danger shadow-sm border-0 border-start border-5 border-danger m-4">
                    <h4 class="fw-bold"><i class="fas fa-lock me-2"></i>Acceso Denegado</h4>
                    <p>No cuenta con los privilegios necesarios para acceder al catálogo de Dominios.</p>
                  </div>';
            return; 
        }

        // 2. Extraer los dominios activos (estatus = 1) organizados alfabéticamente
        $dominios = $model->seleccionaRegistros("dominios", ["id_dom", "nom_dom"], "estatus = 1 ORDER BY nom_dom ASC");
        
        // 3. Evaluar permisos de escritura y borrado para la interfaz visual
        $id_sub_actual  = $id_sub;
        $puede_editar   = $this->verificarPermiso($id_sub, PERM_EDITAR);
        $puede_eliminar = $this->verificarPermiso($id_sub, PERM_ELIMINAR);

        // Hace que las variables estén disponibles directamente dentro del archivo de la vista
        include self::VISTA;
    }

    /**
     * Incluye el archivo JavaScript correspondiente a la vista.
     */
    public function renderJS() {
        if (file_exists(self::JS)) { 
            include self::JS; 
        }
    }

    /**
     * Verifica si el rol del usuario en sesión cuenta con un permiso específico.
     * * @param int $id_sub ID del submódulo.
     * @param int $id_permiso ID del permiso (Consultar, Editar, Eliminar).
     * @return bool True si tiene permiso, false en caso contrario.
     */
    public function verificarPermiso($id_sub, $id_permiso) {
        $model = new MainModel();
        $id_rol = $_SESSION['usuario']['id_rol'] ?? null;
        if (!$id_rol) return false;

        $permiso = $model->seleccionaRegistros("rol_submodulo_permiso", ["id_perm"], "id_rol = ? AND id_sub = ? AND id_perm = ?", [$id_rol, $id_sub, $id_permiso]);
        return ($permiso && count($permiso) > 0);
    }

    // ==========================================
    // MÉTODOS DE OPERACIÓN DEL CRUD (PROCESAMIENTO ASÍNCRONO)
    // ==========================================

    /**
     * Guarda o actualiza un dominio validando duplicados e historiales lógicos (inactivos).
     * * @param int $id_sub ID del submódulo origen.
     * @param string $nom_dom Nombre del dominio enviado desde el cliente.
     * @param int|null $id_dom ID del dominio en caso de ser edición.
     * @return array Respuesta estructurada JSON para el enrutador asíncrono.
     */
    public function guardarDominio($id_sub, $nom_dom, $id_dom = null) {
        $model = new MainModel();
        
        // Sanitización estricta: Limpieza de espacios múltiples y conversión a minúsculas para dominios
        $nom_dom = trim(preg_replace('/\s+/', '', strtolower($nom_dom)));

        // Validaciones básicas de integridad
        if (empty($nom_dom) || preg_match('/^\d+$/', $nom_dom)) {
            return ["status" => "error", "msg" => "El nombre de dominio no es válido. No puede estar vacío ni contener solo números."];
        }
        if (strlen($nom_dom) > 100) {
            return ["status" => "error", "msg" => "El nombre de dominio supera el límite permitido de 100 caracteres."];
        }

        if (!empty($id_dom)) {
            // -------------------------------------------
            // CASO A: MODIFICACIÓN DE UN DOMINIO EXISTENTE
            // -------------------------------------------
            $existe = $model->seleccionaRegistros("dominios", ["id_dom", "estatus"], "nom_dom = ? AND id_dom != ?", [$nom_dom, $id_dom]);
            if (!empty($existe)) {
                if ($existe[0]['estatus'] == 1) {
                    return ["status" => "error", "msg" => "Ya existe otro dominio activo con ese mismo nombre."];
                } else {
                    return [
                        "status" => "exists_inactive", 
                        "id_reactivar" => $existe[0]['id_dom'], 
                        "tabla" => "dominios", 
                        "col_id" => "id_dom", 
                        "msg" => "Ya existe ese dominio registrado pero se encuentra eliminado (inactivo). ¿Desea reactivarlo en su lugar?"
                    ];
                }
            }

            $exito = $model->actualizaRegistroConBitacora("dominios", ["nom_dom"], "id_dom = ?", [$nom_dom, $id_dom], $id_sub, "Edición de dominio a: $nom_dom");
            return $exito ? ["status" => "success", "msg" => "Dominio actualizado correctamente."] : ["status" => "error", "msg" => "Error al actualizar el dominio en la base de datos."];
        
        } else {
            // -------------------------------------------
            // CASO B: ALTA DE UN NUEVO DOMINIO
            // -------------------------------------------
            $existe = $model->seleccionaRegistros("dominios", ["id_dom", "estatus"], "nom_dom = ?", [$nom_dom]);
            if (!empty($existe)) {
                if ($existe[0]['estatus'] == 1) {
                    return ["status" => "error", "msg" => "El dominio ya se encuentra registrado y activo en el catálogo."];
                } else {
                    return [
                        "status" => "exists_inactive", 
                        "id_reactivar" => $existe[0]['id_dom'], 
                        "tabla" => "dominios", 
                        "col_id" => "id_dom", 
                        "msg" => "Este dominio ya fue registrado previamente pero fue eliminado. ¿Desea volver a activarlo?"
                    ];
                }
            }

            $id = $model->agregaRegistroConBitacora("dominios", ["nom_dom", "estatus"], [$nom_dom, 1], $id_sub, "Alta de nuevo dominio institucional: $nom_dom");
            return $id ? ["status" => "success", "msg" => "Dominio registrado exitosamente."] : ["status" => "error", "msg" => "Error al registrar el dominio."];
        }
    }

    /**
     * Realiza la baja lógica (estatus = 0) de un dominio institucional.
     */
    public function eliminarDominio($id_sub, $id_dom) {
        $model = new MainModel();
        
        // Obtener el nombre del dominio antes de borrarlo para dejar rastro claro en la bitácora
        $info = $model->seleccionaRegistros("dominios", ["nom_dom"], "id_dom = ?", [$id_dom]);
        $nom_dom = !empty($info) ? $info[0]['nom_dom'] : "ID: $id_dom";

        $exito = $model->actualizaRegistroConBitacora("dominios", ["estatus"], "id_dom = ?", [0, $id_dom], $id_sub, "Baja lógica de dominio institucional: $nom_dom");
        return $exito ? ["status" => "success", "msg" => "Dominio eliminado correctamente del catálogo activo."] : ["status" => "error", "msg" => "Error al intentar eliminar el dominio."];
    }

    /**
     * Reactiva un dominio previamente dado de baja (estatus = 1).
     */
    public function reactivarDominio($id_sub, $id_dom) {
        $model = new MainModel();
        
        $info = $model->seleccionaRegistros("dominios", ["nom_dom"], "id_dom = ?", [$id_dom]);
        $nom_dom = !empty($info) ? $info[0]['nom_dom'] : "ID: $id_dom";

        $exito = $model->actualizaRegistroConBitacora("dominios", ["estatus"], "id_dom = ?", [1, $id_dom], $id_sub, "Reactivación de dominio institucional: $nom_dom");
        return $exito ? ["status" => "success", "msg" => "El dominio ha sido reactivado y restaurado con éxito."] : ["status" => "error", "msg" => "Error al intentar reactivar el dominio."];
    }
}
?>