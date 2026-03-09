<?php
// Asegurarnos de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Extraer datos de la sesión
$datos_usuario = $_SESSION['usuario'] ?? [];
$nombre_usuario = $datos_usuario['nombres'] ?? $datos_usuario['nom_usuar'] ?? 'Usuario';
$rol_usuario = $datos_usuario['nom_rol'] ?? 'Invitado';

// EXTRAEMOS EL MENÚ DINÁMICO QUE ARMÓ EL LOGINMODEL
$menu_dinamico = $_SESSION['menu'] ?? [];

// Prevenir error si $controlador no está definido en index.php
$controlador = $controlador ?? '';
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INEA | Sistema de Gestión</title>
    
    <base href="<?php echo SITE_URL; ?>">
    <link rel="shortcut icon" type="image/x-icon" href="data:image/x-icon;base64,AAABAA...">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="utils/sweetalert2_v11.11.1/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/master.css">
    <link rel="stylesheet" href="css/tablero.css">

</head>
<body class="d-flex flex-column min-vh-100">

    <header class="navbar-inea sticky-top shadow-sm z-index-master">
        <div class="container-fluid d-flex align-items-center justify-content-between py-2 px-4" style="height: 64px;">
            
            <div class="d-flex align-items-center gap-5">
                
                <a href="inicio/" class="d-flex align-items-center text-decoration-none">
                    <div class="logo-box-inea me-2">INEA</div>
                    <div class="logo-text-inea d-none d-xl-block">
                        <small>INSTITUTO NACIONAL PARA LA</small><br>
                        <strong>EDUCACIÓN DE LOS ADULTOS</strong>
                    </div>
                </a>

                <nav class="d-none d-lg-flex align-items-center nav-main-inea gap-1">
                    
                    <a href="inicio/" class="nav-link <?php echo ($controlador == 'dashboard' || $controlador == 'inicio' || $controlador == '') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                    
                    <?php foreach ($menu_dinamico as $id_mod => $modulo): 
                        // Filtramos si este módulo tiene submenús configurados para mostrarse
                        $hijos_visibles = array_filter($modulo['hijos'], function($h) { return $h['mostrar'] == 1; });
                        $tiene_dropdown = count($hijos_visibles) > 0;
                    ?>

                        <?php if ($tiene_dropdown): ?>
                            <div class="dropdown">
                                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-1" id="dropMod_<?php echo $id_mod; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="<?php echo $modulo['icono']; ?>"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                                </a>
                                <ul class="dropdown-menu shadow-sm border-0 mt-2" aria-labelledby="dropMod_<?php echo $id_mod; ?>">
                                    <?php foreach ($modulo['hijos'] as $slug => $sub): ?>
                                        <?php if ($sub['mostrar'] == 1): ?>
                                            <li>
                                                <?php if ($slug === 'diccionario'): ?>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDiccionario"><?php echo htmlspecialchars($sub['nombre']); ?></a>
                                                <?php else: ?>
                                                    <a class="dropdown-item" href="<?php echo $slug; ?>/"><?php echo htmlspecialchars($sub['nombre']); ?></a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                        <?php else: 
                            // B. DIBUJAR ENLACE DIRECTO (Ej. Gestión de Personal)
                            // Tomamos el primer slug de sus hijos (aunque estén ocultos) para la URL
                            $slug_directo = !empty($modulo['hijos']) ? array_key_first($modulo['hijos']) : '#';
                        ?>
                            <a href="<?php echo $slug_directo; ?>/" class="nav-link <?php echo ($controlador == $slug_directo) ? 'active' : ''; ?>">
                                <i class="<?php echo $modulo['icono']; ?>"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                            </a>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </nav>
            </div>

            <div class="user-profile-inea d-flex align-items-center gap-3">
                <div class="d-none d-md-flex align-items-center gap-2">
                    <i class="far fa-user text-muted"></i>
                    <span class="text-dark" style="font-size: 0.85rem; font-weight: 500; text-transform: capitalize;">
                        <?php echo htmlspecialchars($nombre_usuario); ?>
                    </span>
                    <span class="text-muted">|</span>
                    <span class="text-muted extra-small" style="text-transform: capitalize;">
                        <?php echo htmlspecialchars($rol_usuario); ?>
                    </span>
                </div>

                <div class="dropdown">
                    <div class="profile-avatar cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" style="width: 220px;">
                        <li class="px-3 py-2">
                            <p class="mb-0 fw-bold fs-6" style="text-transform: capitalize;"><?php echo htmlspecialchars($nombre_usuario); ?></p>
                            <p class="mb-0 text-muted extra-small" style="text-transform: capitalize;"><?php echo htmlspecialchars($rol_usuario); ?></p>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="#" id="btnLogout">
                                <i class="fas fa-sign-out-alt">Cerrar Sesión</i>
                            </a>
                        </li>
                    </ul>
                </div>

                <button class="btn btn-light d-lg-none border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title mb-0" style="text-transform: capitalize;"><?php echo htmlspecialchars($nombre_usuario); ?></h5>
                <small class="text-muted" style="text-transform: capitalize;"><?php echo htmlspecialchars($rol_usuario); ?></small>
            </div>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        
        <div class="offcanvas-body nav-main-inea d-flex flex-column gap-2 px-3">
            <a href="inicio/" class="nav-link <?php echo ($controlador == 'dashboard' || $controlador == 'inicio' || $controlador == '') ? 'active' : ''; ?>">
                <i class="fas fa-th-large me-2"></i> Dashboard
            </a>
            
            <?php foreach ($menu_dinamico as $id_mod => $modulo): 
                $hijos_visibles = array_filter($modulo['hijos'], function($h) { return $h['mostrar'] == 1; });
                $tiene_dropdown = count($hijos_visibles) > 0;
            ?>
                <?php if ($tiene_dropdown): ?>
                    <div class="fw-bold text-muted mt-2 mb-1 border-bottom pb-1">
                        <i class="<?php echo $modulo['icono']; ?> me-2"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                    </div>
                    <?php foreach ($modulo['hijos'] as $slug => $sub): ?>
                        <?php if ($sub['mostrar'] == 1): ?>
                            <?php if ($slug === 'diccionario'): ?>
                                <a href="#" class="nav-link ms-3" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDiccionario"><i class="fas fa-caret-right me-1"></i> <?php echo htmlspecialchars($sub['nombre']); ?></a>
                            <?php else: ?>
                                <a href="<?php echo $slug; ?>/" class="nav-link ms-3 <?php echo ($controlador == $slug) ? 'active' : ''; ?>"><i class="fas fa-caret-right me-1"></i> <?php echo htmlspecialchars($sub['nombre']); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: 
                    $slug_directo = !empty($modulo['hijos']) ? array_key_first($modulo['hijos']) : '#';
                ?>
                    <a href="<?php echo $slug_directo; ?>/" class="nav-link mt-1 <?php echo ($controlador == $slug_directo) ? 'active' : ''; ?>">
                        <i class="<?php echo $modulo['icono']; ?> me-2"></i> <?php echo htmlspecialchars($modulo['nombre']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
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

    <footer class="bg-gob-dark text-white pt-5 pb-3 footer-inea mt-auto" style="background-color: #6C1D45;">
        <div class="container-fluid px-4">
            <div class="row gy-4">
                <div class="col-12 col-md-3">
                    <h5 class="fw-bold mb-3">INEA</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Portal INEA</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Programas</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Documentos</a></li>
                    </ul>
                </div>
                
                <div class="col-12 col-md-3">
                    <h5 class="fw-bold mb-3">Enlaces</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="https://www.gob.mx" target="_blank" class="text-white text-decoration-none">gob.mx</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Datos Abiertos</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Trámites</a></li>
                    </ul>
                </div>
                
                <div class="col-12 col-md-3">
                    <h5 class="fw-bold mb-3">Información</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Aviso de Privacidad</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Términos y Condiciones</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Política de Seguridad</a></li>
                    </ul>
                </div>
                
                <div class="col-12 col-md-3">
                    <h5 class="fw-bold mb-3">Contacto</h5>
                    <ul class="list-unstyled text-light small">
                        <li class="mb-2">Tel: 01 800 00 32 466</li>
                        <li class="mb-2">contacto.inea@inea.gob.mx</li>
                        <li class="mb-2">Atención de Lunes a Viernes<br>9:00 - 18:00 hrs</li>
                    </ul>
                </div>
            </div>
            
            <hr class="mt-4 mb-4" style="background-color: rgba(255,255,255,0.2); height: 1px; border: none;">
            
            <div class="text-center" style="font-size: 0.85rem; color: rgba(255,255,255,0.8);">
                © 2026 Instituto Nacional para la Educación de los Adultos (INEA). Sistema de Gestión de Personal Operativo y Activos Tecnológicos.
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="utils/sweetalert2_v11.11.1/sweetalert2.min.js"></script>
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