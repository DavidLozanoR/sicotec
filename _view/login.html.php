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
    <base href="<?php echo SITE_URL; ?>">


</head>
<body>
    <div style="width: 100%; display: block; background-color: #0b231e;">
        <iframe src="_view/header_gob.html" id="iframeHeaderGob" style="width: 100%; height: 80px; border: none; overflow: hidden; display: block;" scrolling="no" title="Header Gobierno de México"></iframe>
    </div>

    <div class="wrapper">

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

        <div style="width: 100%; background-color: #13322B; margin-top: auto; display: flex;">
        <iframe src="_view/footer_gob.html" id="iframeFooterGob" style="width: 100%; height: 350px; border: none; overflow: hidden;" scrolling="no" title="Footer Gobierno de México"></iframe>
        </div>
    </div>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>utils/sweetalert2_v11.11.1/sweetalert2.min.js"></script>
     <script>
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'resizeFooter') {
                document.getElementById('iframeFooterGob').style.height = event.data.height + 'px';
            }
            
        });
    </script>
<script src="<?php echo SITE_URL; ?>js/site.js"></script>
<script src="<?php echo SITE_URL; ?>js/login.js"></script>
</body>
</html>
