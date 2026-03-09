<?php
// Asegurarnos de tener las variables de sesión listas
$nombre_usuario = $_SESSION['usuario']['nombres'] ?? $_SESSION['usuario']['nom_usuar'] ?? 'Usuario';
$menu_dinamico = $_SESSION['menu'] ?? [];
?>

<div class="mb-4">
    <h2 class="fw-bold text-dark mb-1">Dashboard</h2>
    <p class="text-muted" style="text-transform: capitalize;">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
</div>

<div class="row g-4">
    
    <?php 
    // Dibujamos las tarjetas dinámicamente basadas 100% en la Base de Datos
    foreach ($menu_dinamico as $id_mod => $modulo): 
        $nombre_mod  = $modulo['nombre'];
        $descripcion = $modulo['descripcion'] ?? 'Módulo del sistema.'; // Lo toma de la BD
        $icono_mod   = $modulo['icono'];
        
        // Calculamos a dónde debe llevar el botón (El primer submódulo al que tenga acceso)
        $ruta_destino = '#';
        if (!empty($modulo['hijos'])) {
            $ruta_destino = array_key_first($modulo['hijos']) . '/';
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
                <a href="<?php echo htmlspecialchars($ruta_destino); ?>" class="btn btn-inea w-100 mt-auto">Acceder al Módulo</a>
            </div>
        </div>
    </div>
    
    <?php endforeach; ?>

</div>