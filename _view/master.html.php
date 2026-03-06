<?php
// Asegurarnos de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Extraer datos de la sesión (Según lo que guardamos en tu LoginModel)
$datos_usuario = $_SESSION['usuario'] ?? [];
// Usamos el nombre, o si no, el nom_usuar como respaldo
$nombre_usuario = $datos_usuario['nombres'] ?? $datos_usuario['nom_usuar'] ?? 'Usuario';
$rol_usuario = $datos_usuario['nom_rol'] ?? 'Invitado';
$permisos_usuario = $_SESSION['permisos'] ?? [];

// Helper para validar permisos
if (!function_exists('tiene_permiso')) {
    function tiene_permiso($permiso_requerido, $mis_permisos) {
        return in_array($permiso_requerido, $mis_permisos);
    }
}

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
                    
                    <a href="personal/" class="nav-link <?php echo ($controlador == 'personal') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Gestión de Personal
                    </a>
                    
                    <div class="dropdown">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-1 <?php echo ($controlador == 'configuracion') ? 'active' : ''; ?>" id="dropConfig" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <ul class="dropdown-menu shadow-sm border-0 mt-2" aria-labelledby="dropConfig">
                            <li><a class="dropdown-item" href="configuracion/">Vista General</a></li>
                            <li><a class="dropdown-item" href="configuracion/servicios/">Catálogo de Servicios</a></li>
                            <li><a class="dropdown-item" href="configuracion/red/">Infraestructura de Red</a></li>
                            <li><a class="dropdown-item" href="configuracion/responsiva/">Responsiva de Equipos</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDiccionario">Diccionario de Nomenclaturas</a></li>
                        </ul>
                    </div>
                    
                    <?php if (tiene_permiso('Consultar', $permisos_usuario)): ?>
                    <a href="consultas/" class="nav-link <?php echo ($controlador == 'Consultas') ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i> Consultas
                    </a>
                    <?php endif; ?>
                    
                    <a href="usuarios/" class="nav-link <?php echo ($controlador == 'usuarios') ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i> Usuarios
                    </a>
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
            <a href="dashboard/" class="nav-link <?php echo ($controlador == 'dashboard' || $controlador == 'inicio' || $controlador == '') ? 'active' : ''; ?>">
                <i class="fas fa-th-large me-2"></i> Dashboard
            </a>
            
            <a href="personal/" class="nav-link <?php echo ($controlador == 'personal') ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> Gestión de Personal
            </a>
            
            <a href="configuracion/" class="nav-link <?php echo ($controlador == 'configuracion') ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i> Configuración Global
            </a>
            
            <?php if (tiene_permiso('Consultar', $permisos_usuario)): ?>
            <a href="consultas/" class="nav-link <?php echo ($controlador == 'consultas') ? 'active' : ''; ?>">
                <i class="fas fa-search me-2"></i> Consultas
            </a>
            <?php endif; ?>
            
            <a href="usuarios/" class="nav-link <?php echo ($controlador == 'usuarios') ? 'active' : ''; ?>">
                <i class="fas fa-shield-alt me-2"></i> Usuarios
            </a>
        </div>
    </div>

    <main id="main-content" class="flex-grow-1 py-4">
        <div class="container-fluid px-4">
            <?php 
                // Renderizado seguro del contenido
                if (isset($ctrl) && is_object($ctrl) && method_exists($ctrl, 'renderContent')) {
                    $ctrl->renderContent(); 
                } else {
                    $nombre_modulo = ucfirst($controlador ?? 'desconocido'); 
                    // Si tienes una vista de enConstruccion.html.php se mostrará, de lo contrario imprimimos un mensaje amigable
                    if(file_exists("_view/enConstruccion.html.php")) {
                        include "_view/enConstruccion.html.php";
                    } else {
                        echo "<div class='alert alert-warning'>El módulo se encuentra en construcción o no fue encontrado.</div>";
                    }
                }
            ?>
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
    
    <script>
        <?php 
            // Renderizado seguro del JS específico de la vista
            if (isset($ctrl) && is_object($ctrl) && method_exists($ctrl, 'renderJS')) {
                $ctrl->renderJS(); 
            } 
        ?>
    </script>
</body>
</html>