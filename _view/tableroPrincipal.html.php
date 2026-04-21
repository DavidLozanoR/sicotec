<?php
// Asegurarnos de tener las variables de sesión listas
$nombres = $_SESSION['usuario']['nombres'] ?? "";
$ape_pa  = $_SESSION['usuario']['ape_pa']  ?? '';
$ape_ma  = $_SESSION['usuario']['ape_ma']  ?? '';
$nombre_completo = trim("$nombres $ape_pa $ape_ma");
$menu_dinamico = $_SESSION['menu'] ?? [];
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="fw-bold text-dark mb-1">Tablero Principal</h2>
        <p class="text-muted" style="text-transform: capitalize;">Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?></p>
    </div>
    
    <button class="btn btn-outline-secondary d-none shadow-sm px-3 fw-bold" id="btnVolverModulos">
        <i class="fas fa-arrow-left me-2"></i> Volver a Módulos
    </button>
</div>

<div class="row g-4" id="panelModulos">
    
    <?php 
    // Dibujamos las tarjetas dinámicamente basadas 100% en la Base de Datos
    foreach ($menu_dinamico as $id_mod => $modulo): 
        $nombre_mod  = $modulo['nombre'];
        $descripcion = $modulo['descripcion'] ?? 'Módulo del sistema.'; // Lo toma de la BD
        $icono_mod   = $modulo['icono'];
        
        $hijos = $modulo['hijos'] ?? [];
        $num_hijos = count($hijos);
        
        // Lógica de ruteo: Si hay 1 submódulo, va directo. Si hay varios, usamos JS.
        if ($num_hijos === 1) {
            $ruta_destino = array_key_first($hijos) . '/';
            $texto_boton = 'Acceder al Módulo';
            $clase_js = '';
            $datos_hijos = '';
        } else {
            $ruta_destino = '#';
            $texto_boton = 'Ver Opciones';
            $clase_js = 'btn-drilldown';
            // Empaquetamos los submódulos en formato JSON seguro para que el JS los lea
            $datos_hijos = htmlspecialchars(json_encode($hijos), ENT_QUOTES, 'UTF-8');
        }
    ?>
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm card-module">
            <div class="card-body text-center p-4 d-flex flex-column">
                <div class="mb-3">
                    <i class="<?php echo htmlspecialchars($icono_mod); ?> icon-module"></i>
                </div>
                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($nombre_mod); ?></h5>
                <p class="card-text text-muted small mb-4">
                    <?php echo htmlspecialchars($descripcion); ?>
                </p>
                <a href="<?php echo $ruta_destino; ?>" 
                   class="btn btn-inea w-100 mt-auto <?php echo $clase_js; ?>"
                   data-nombre="<?php echo htmlspecialchars($nombre_mod); ?>"
                   data-hijos="<?php echo $datos_hijos; ?>">
                    <?php echo $texto_boton; ?>
                </a>
            </div>
        </div>
    </div>
    
    <?php endforeach; ?>

</div>

<div class="d-none animate__animated animate__fadeIn" id="panelSubmodulos">
    <h4 class="fw-bold mb-4 border-bottom pb-2" id="tituloSubmodulos" style="color: var(--guinda, #9D2449);">
        Opciones
    </h4>
    
    <div class="row g-4" id="contenedorSubmodulos"></div>
</div>