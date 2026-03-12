<?php
require_once "MainModel.php";

class PersonalOPController{


const VISTA = "_view/personalOperativo.html.php";
const JS = "js/personalOperativo.js";

private $slug;


function __construct($slug=null){ 
        // Validamos que exista una sesión activa
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if(!isset($_SESSION['usuario'])){
            die("Error: Su sesión ha expirado o no tiene acceso a este módulo");
        }
        $this->slug=$slug;


    }

    public function renderContent(){
        $model = new MainModel();
        
        $datos= $this->traerContenido($model);//trae el contenido a renderizar
        //estraemos los datos para consumirlos es la vista
        extract($datos);

        // 4. Incluimos la vista
        include self::VISTA;
    }

    public function renderJS(){
        // Incluimos el JavaScript específico para esta vista
        // (Donde programaremos los filtros en vivo y el modal)
        include self::JS;
    }

    private function traerContenido($model){
        // Buscamos en la tabla Submodulos aquel que coincida con la ruta de este módulo
        $consultaSub = $model->seleccionaRegistros("Submodulos", ["id_sub"], "slug = ?", ["$this->slug"]);
        
        // Si la consulta arroja resultados, extraemos el ID. Si por alguna razón falla, usamos 1 como respaldo de seguridad.
        $id_sub_actual = (!empty($consultaSub)) ? $consultaSub[0]['id_sub'] : 1;
        // 1. Obtener catálogos para los filtros (Selects)
        $departamentos = $model->seleccionaRegistros("Departamentos", ["id_dep", "nom_dep"]);
        //$segmentos = $model->seleccionaRegistros("Segmentos", ["id_seg", "ip_ini", "ip_lim"]);

        // 2. Preparamos el JOIN para traer los datos del personal + el nombre de su departamento
        $tablaPrincipal = "Personal_Operativo po";
        $joins = [
            "JOIN Departamentos d ON po.id_dep = d.id_dep"
        ];
        $campos = [
            "po.id_po", 
            "po.num_cobro", 
            "po.nombres", 
            "po.ape_pa", 
            "po.ape_ma", 
            "po.puesto",
            "po.estatus",
            "d.nom_dep" 
        ];
        
        // Filtramos solo el personal activo (borrado lógico)
        $condicion = "po.estatus = ?";
        $params = [1];
        
        $lista_personal = $model->seleccionaRegistrosJoin($tablaPrincipal, $joins, $campos, $condicion, $params);
        
        // 3. Contar los equipos asignados a cada empleado
        if ($lista_personal) {
            foreach ($lista_personal as $index => $po) {
                // Buscamos cuántos dispositivos tiene asignados este empleado
                $equipos = $model->seleccionaRegistros("Dispositivos", ["id_disp"], "asig_po_id = ?", [$po['id_po']]);
                
                // Si la consulta devuelve resultados, contamos el arreglo, si no, es 0
                $lista_personal[$index]['total_equipos'] = $equipos ? count($equipos) : 0;
            }
        } else {
            // Si no hay personal, aseguramos que sea un arreglo vacío para no romper la vista
            $lista_personal = [];
        }
        return [
            'departamentos' => $departamentos,
            'lista_personal' => $lista_personal
        ];

    }
    


}





?>