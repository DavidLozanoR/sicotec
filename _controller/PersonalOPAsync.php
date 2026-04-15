<?php
session_start();
require_once __DIR__ . "/../config/Global.php";
require_once "PersonalOPController.php";

if (!isset($_SESSION['usuario'])) {
    echo json_encode(["result" => 0, "msg" => "Sesión expirada o no válida."]);
    exit;
}

$controller = new PersonalOPController();
$peticion = $_POST['peticion'] ?? '';
$id_sub = $_POST['id_sub'] ?? '';

switch ($peticion) {
    case 'INSERT_PO':
        // Verificación estricta de permiso por Rol (Permiso 2 = Agregar)
        if (!$controller->verificarPermiso($id_sub, PERM_AGREGAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No cuenta con permisos para agregar personal."]);
            exit;
        }

        $num_cobro = trim($_POST['num_cobro'] ?? '');
        $nombres = preg_replace('/\s+/', ' ', trim($_POST['nombres'] ?? ''));
        $ape_pa = preg_replace('/\s+/', ' ', trim($_POST['ape_pa'] ?? ''));
        $ape_ma = preg_replace('/\s+/', ' ', trim($_POST['ape_ma'] ?? ''));
        $dir = trim($_POST['dir'] ?? '');
        $correo_prefijo = trim($_POST['correo_prefijo'] ?? '');
        $puesto = trim($_POST['puesto'] ?? '');
        $ext = trim($_POST['ext'] ?? '');
        $cta_dom = trim($_POST['cta_dom'] ?? '');
        $estatus = $_POST['estatus'] ?? 1;
        $id_dep = $_POST['id_dep'] ?? '';

        if (empty($id_sub) || empty($num_cobro) || empty($nombres) || empty($id_dep)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios (No. Cobro, Nombre o Departamento)."]);
            exit;
        }

        // Validación desde el controlador
        $validacion = $controller->validarDatosPO($num_cobro, $nombres, $ape_pa, $ape_ma, $correo_prefijo, $ext, $puesto, $id_dep);
        if ($validacion !== true) {
            echo json_encode(["result" => 0, "msg" => $validacion]);
            exit;
        }

        $correo = !empty($correo_prefijo) ? $correo_prefijo . '@inea.gob.mx' : null;
        $ext = !empty($ext) ? $ext : null;

        echo json_encode($controller->insertaPO($id_sub, $num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep));
        break;

    case 'UPDATE_PO':
        // Verificación estricta de permiso por Rol (Permiso 1 = Editar)
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No cuenta con permisos para editar personal."]);
            exit;
        }

        $id_po = $_POST['id_po'] ?? '';
        $num_cobro = trim($_POST['num_cobro'] ?? '');
        $nombres = preg_replace('/\s+/', ' ', trim($_POST['nombres'] ?? ''));
        $ape_pa = preg_replace('/\s+/', ' ', trim($_POST['ape_pa'] ?? ''));
        $ape_ma = preg_replace('/\s+/', ' ', trim($_POST['ape_ma'] ?? ''));
        $dir = trim($_POST['dir'] ?? '');
        $correo_prefijo = trim($_POST['correo_prefijo'] ?? '');
        $puesto = trim($_POST['puesto'] ?? '');
        $ext = trim($_POST['ext'] ?? '');
        $cta_dom = trim($_POST['cta_dom'] ?? '');
        $estatus = $_POST['estatus'] ?? 1;
        $id_dep = $_POST['id_dep'] ?? '';

        if (empty($id_sub) || empty($id_po) || empty($num_cobro) || empty($nombres) || empty($id_dep)) {
            echo json_encode(["result" => 0, "msg" => "Faltan datos obligatorios para actualizar."]);
            exit;
        }
        $validacion = $controller->validarDatosPO($num_cobro, $nombres, $ape_pa, $ape_ma, $correo_prefijo, $ext, $puesto, $id_dep);
;
        if ($validacion !== true) {
            echo json_encode(["result" => 0, "msg" => $validacion]);
            exit;
        }

        $correo = !empty($correo_prefijo) ? $correo_prefijo . '@inea.gob.mx' : null;
        $ext = !empty($ext) ? $ext : null;

        echo json_encode($controller->actualizaPO($id_sub, $id_po, $num_cobro, $nombres, $ape_pa, $ape_ma, $dir, $correo, $puesto, $ext, $cta_dom, $estatus, $id_dep));
        break;

    case 'BAJA_PO':
        // Verificación estricta de permiso por Rol (Permiso 3 = Eliminar/Baja)
        if (!$controller->verificarPermiso($id_sub, PERM_ELIMINAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No cuenta con permisos para dar de baja."]);
            exit;
        }

        $id_po = $_POST['id_po'] ?? '';
        if (empty($id_sub) || empty($id_po)) {
            echo json_encode(["result" => 0, "msg" => "ID de personal o módulo no válido."]);
            exit;
        }

        echo json_encode($controller->bajaPO($id_sub, $id_po));
        break;
case 'DESVINCULAR_EQUIPO':
        // Verificación de permiso (Usamos el Permiso 1 = Editar)
        if (!$controller->verificarPermiso($id_sub, PERM_EDITAR)) {
            echo json_encode(["result" => 0, "msg" => "Acceso denegado: No cuenta con permisos para modificar las asignaciones de los empleados."]);
            exit;
        }

        $id_po = $_POST['id_po'] ?? '';
        $id_disp = $_POST['id_disp'] ?? '';
        $tipo_vinculo = $_POST['tipo_vinculo'] ?? ''; // 'quitar_uso' o 'quitar_resguardo'

        if(empty($id_po) || empty($id_disp) || empty($tipo_vinculo)){
            echo json_encode(["result" => 0, "msg" => "Faltan datos para procesar la desvinculación del equipo."]);
            exit;
        }

        echo json_encode($controller->desvincularEquipo($id_sub, $id_po, $id_disp, $tipo_vinculo));
        break;
    default:
        echo json_encode(["result" => 0, "msg" => "Petición no reconocida."]);
        break;
}

?>