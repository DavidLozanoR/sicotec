<?php
// TODO: AGREGAREMOS LOGICA DE SESIONES AQUI
// session_start();
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | INEA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>utils/sweetalert2_v11.11.1/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo SITE_URL; ?>./css/login.css">


</head>
<body>

    <div class="wrapper">
        <header class="header-inea">
            <div class="container d-flex align-items-center">
                <div class="d-flex align-items-center">
                    <div class="inea-logo-box">INEA</div>
                    <div class="inea-logo-text">
                        INSTITUTO NACIONAL PARA LA<br>EDUCACIÓN DE LOS ADULTOS
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="card login-card">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h3 class="fw-semibold mb-2" style="color: #333;">Iniciar Sesión</h3>
                        <p class="text-secondary" style="font-size: 0.9rem;">
                            Sistema de Gestión de Personal Operativo y Activos Tecnológicos - INEA
                        </p>
                    </div>

                    <form id="login-form">
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-semibold" style="font-size: 0.9rem; color: #333;">Usuario</label>
                            <input type="text" class="form-control custom-input" id="usr" name="usr" placeholder="Ingrese su usuario" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold" style="font-size: 0.9rem; color: #333;">Contraseña</label>
                            <input type="password" class="form-control custom-input" id="pass" name="pass" placeholder="Ingrese su contraseña" required>
                        </div>

                        <div class="d-grid gap-2 mb-2">
                            <button type="submit" class="btn btn-guinda">
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </main>

        <footer class="footer-gob pt-5">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <img src="https://framework-gb.cdn.gob.mx/landing/img/logoheader.svg" alt="Logo México" height="35" class="mb-3" style="filter: brightness(0) invert(1);">
                        <p class="small font-weight-light opacity-75" style="color: #f8f9fa;">
                            Sistema de Gestión de Personal Operativo y Activos Tecnológicos.<br>
                            Gobierno de México.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="fw-bold mb-3 text-white">Enlaces</h6>
                        <ul class="list-unstyled small">
                            <li class="mb-2"><a href="#" class="footer-link">Participa</a></li>
                            <li class="mb-2"><a href="#" class="footer-link">Datos</a></li>
                            <li><a href="https://www.gob.mx" class="footer-link">Gob.mx</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bar"></div>
        </footer>
    </div>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>utils/sweetalert2_v11.11.1/sweetalert2.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/site.js"></script>
    <script src="<?php echo SITE_URL; ?>js/login.js"></script>
</body>
</html>