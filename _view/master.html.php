<?php
// Asegurarnos de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$datos_usuario = $_SESSION['usuario'] ?? [];

// Construcción del Nombre Completo
$nombres = $datos_usuario['nombres'] ?? '';
$ape_pa  = $datos_usuario['ape_pa'] ?? '';
$ape_ma  = $datos_usuario['ape_ma'] ?? '';
$nombre_completo = trim("$nombres $ape_pa $ape_ma");

// Si por alguna razón no tiene nombre completo registrado, usamos el nickname
if (empty($nombre_completo)) {
    $nombre_completo = $datos_usuario['nom_usuar'] ?? 'Usuario';
}

$rol_usuario = $datos_usuario['nom_rol'] ?? 'Invitado';
$menu_dinamico = $_SESSION['menu'] ?? [];
$controlador = $controlador ?? '';
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INEA | Sistema de Gestión</title>
    
    <base href="<?php echo SITE_URL; ?>">
    <link rel="shortcut icon" type="image/x-icon" href="https://framework-gb.cdn.gob.mx/gm/v3/assets/images/favicon.ico">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="utils/sweetalert2_v11.11.1/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/master.css">
    <link rel="stylesheet" href="css/tablero.css">

</head>
<body class="d-flex flex-column min-vh-100">

    <div style="width: 100%; display: block; background-color: #0b231e;">
        <iframe src="_view/header_gob.html" id="iframeHeaderGob" style="width: 100%; height: 90px; border: none; overflow: hidden; display: block;" scrolling="no" title="Header Gobierno de México"></iframe>
    </div>

    <header class="navbar-inea sticky-top shadow-sm z-index-master">
        <div class="container-fluid d-flex align-items-center justify-content-between py-2 px-3" style="height: 64px;">
            
            <div class="d-flex align-items-center">
                <a href="inicio/" class="d-flex align-items-center text-decoration-none">
                    <div class="logo-box-inea me-2">INEA</div>
                    <div class="logo-text-inea d-none d-sm-block">
                        <small>INSTITUTO NACIONAL PARA LA</small><br>
                        <strong>EDUCACIÓN DE LOS ADULTOS</strong>
                    </div>
                </a>
            </div>

            <div class="user-profile-inea d-flex align-items-center gap-3">
                
                <div class="d-flex flex-column align-items-end text-end" style="line-height: 1.2; max-width: 180px;">
                    <span class="text-dark fw-bold text-truncate w-100" style="font-size: 0.85rem; text-transform: capitalize;" title="<?php echo htmlspecialchars($nombre_completo); ?>">
                        <?php echo htmlspecialchars($nombre_completo); ?>
                    </span>
                    <span class="text-muted extra-small text-truncate w-100" style="text-transform: capitalize;">
                        <?php echo htmlspecialchars($rol_usuario); ?>
                    </span>
                </div>

                <div class="dropdown">
                    <div class="profile-avatar cursor-pointer shadow-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Opciones de cuenta">
                        <i class="fas fa-user"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="width: 250px;">
                        <li class="px-3 py-2 text-center">
                            <div class="profile-avatar mx-auto mb-2" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <p class="mb-0 fw-bold fs-6 lh-sm" style="text-transform: capitalize;"><?php echo htmlspecialchars($nombre_completo); ?></p>
                            <p class="mb-0 text-muted small mt-1" style="text-transform: capitalize;"><?php echo htmlspecialchars($rol_usuario); ?></p>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center justify-content-center gap-2 py-2" href="#" id="btnLogout">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>

                <button class="btn btn-light border-0 shadow-sm rounded-3 px-3 py-2 ms-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenu" title="Abrir Menú">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

        </div>
    </header>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mainMenu" style="width: 320px;">
        <div class="offcanvas-header border-bottom bg-light">
            <div class="d-flex align-items-center gap-2">
                <div class="logo-box-inea" style="width: 35px; height: 35px; font-size: 0.7rem;">INEA</div>
                <div>
                    <h6 class="mb-0 fw-bold">Menú del Sistema</h6>
                </div>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        
        <div class="offcanvas-body nav-main-inea p-0">
            <div class="d-flex flex-column px-3 py-3 gap-2">
                
                <a href="inicio/" class="nav-link rounded-2 px-3 py-2 <?php echo ($controlador == 'dashboard' || $controlador == 'inicio' || $controlador == '') ? 'active bg-guinda text-white' : ''; ?>">
                    <i class="fas fa-th-large me-2"></i> Dashboard Principal
                </a>
                
                <?php foreach ($menu_dinamico as $id_mod => $modulo): 
                    $hijos_visibles = array_filter($modulo['hijos'], function($h) { return $h['mostrar'] == 1; });
                    $tiene_dropdown = count($hijos_visibles) > 0;
                ?>
                    <?php if ($tiene_dropdown): ?>
                        <div class="fw-bold text-guinda mt-3 mb-1 border-bottom pb-1 small text-uppercase">
                            <i class="<?php echo $modulo['icono']; ?> me-2"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                        </div>
                        <?php foreach ($modulo['hijos'] as $slug => $sub): ?>
                            <?php if ($sub['mostrar'] == 1): ?>
                                <?php if ($slug === 'diccionario'): ?>
                                    <a href="#" class="nav-link rounded-2 px-3 py-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDiccionario">
                                        <i class="fas fa-caret-right me-2 text-muted"></i> <?php echo htmlspecialchars($sub['nombre']); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $slug; ?>/" class="nav-link rounded-2 px-3 py-2 <?php echo ($controlador == $slug) ? 'active bg-light fw-bold text-guinda' : ''; ?>">
                                        <i class="fas fa-caret-right me-2 text-muted"></i> <?php echo htmlspecialchars($sub['nombre']); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: 
                        $slug_directo = !empty($modulo['hijos']) ? array_key_first($modulo['hijos']) : '#';
                    ?>
                        <a href="<?php echo $slug_directo; ?>/" class="nav-link rounded-2 px-3 py-2 mt-2 <?php echo ($controlador == $slug_directo) ? 'active bg-light fw-bold text-guinda' : ''; ?>">
                            <i class="<?php echo $modulo['icono']; ?> me-2"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <main id="main-content" class="flex-grow-1 py-4">
        <div class="container-fluid px-4">
            <?php 
                if (isset($ctrl) && is_object($ctrl) && method_exists($ctrl, 'renderContent')) {
                    $ctrl->renderContent(); 
                } else {
                    $nombre_modulo = ucfirst($controlador ?? 'desconocido'); 
                    if(file_exists("_view/enConstruccion.html.php")) {
                        include "_view/enConstruccion.html.php";
                    } else {
                        echo "<div class='alert alert-warning'>El módulo se encuentra en construcción o no fue encontrado.</div>";
                    }
                }
            ?>
        </div>

        <div class="offcanvas offcanvas-end shadow" tabindex="-1" id="offcanvasDiccionario" aria-labelledby="offcanvasDiccionarioLabel" style="width: 500px;">
            <div class="offcanvas-header border-bottom bg-light">
                <h5 class="offcanvas-title fw-bold" id="offcanvasDiccionarioLabel">
                    <i class="fas fa-book me-2 text-dark"></i>Diccionario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAgregarNomenclatura">
                        <i class="fas fa-plus me-1"></i> Agregar Nomenclatura
                    </button>
                </div>
                <div class="table-responsive flex-grow-1">
                    <table class="table table-bordered table-hover table-sm" id="tablaDiccionario">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">Nomenclatura</th>
                                <th style="width: 40%;">Significado</th>
                                <th style="width: 30%;">Notas</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDiccionario">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div style="width: 100%; background-color: #13322B; margin-top: auto; display: flex;">
        <iframe src="_view/footer_gob.html" id="iframeFooterGob" style="width: 100%; height: 350px; border: none; overflow: hidden;" scrolling="no" title="Footer Gobierno de México"></iframe>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="utils/sweetalert2_v11.11.1/sweetalert2.min.js"></script>
    
    <script>
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'resizeFooter') {
                document.getElementById('iframeFooterGob').style.height = event.data.height + 'px';
            }
            
        });
    </script>

    <script src="js/site.js"></script>
    <script src="js/logout.js"></script>
    <script src="js/diccionario.js"></script>
    
    <script>
        <?php 
            if (isset($ctrl) && is_object($ctrl) && method_exists($ctrl, 'renderJS')) {
                $ctrl->renderJS(); 
            } 
        ?>
    </script>
</body>
</html>