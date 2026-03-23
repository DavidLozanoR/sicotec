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
                        <div class="mb-3">
                            <img src="data:image/png;base64,UklGRgYGAABXRUJQVlA4IPoFAACwJgCdASq0ALQAPp1MoUslpKOhpnI6WLATiWlu3WBpBgOwz+/8txL2OM/k/2bi19SnqBex92fAB1DOqUqxzK/8V6MGldSVf2l3vrQgvaDEHaeGioZeseToxXQ9QLwNAecnPoE8EvlJV6psZjcL9e8HNtj6m5l1oFixiFxgfpdcqe+gAoxjy2oT/KNQjrLFgpfF1XAa+uOquBFqsWJC2jqo8HfzAjGxIRi1I8nsjn5nySac6uNLtpRJy8wriQpImr0oIWnQ0YHbNLi7oFf1Iof2bbH5cJez/k8MU2pFriKI9i4NedOLj6r3vhHHg00x/wV0HxRwN9cwTJNN/m7E0PZOpR/uq5zreJ8DJiK1O4Fgkbry5qpGO7vSWn7XPJya9KcS8JUGkCwLUS2FL6TsM3olGjO1buhjOqj1R6xvtAAA/vlYkcFXjZcecXda+TvN3XURedCNs/VdoohDyA8RnrvSXJ1yIv6PL+iBbWBF7RoCoYCQCSXc/kzeZe3cwBXPbZ5KI7cGCVfGj9UEn9w9VSPeTZqQIC3bXcSxSv1tc7SGGAl503VPUXL77XDr+InSn9G3/ZEPtLNi3bkxMmxUHIO/x2oqhBf1LP7d44shpU3S+cLmZKcRm2+wRnRVIQKLK67iTgVdUqHR1+f3utxVwkL5muJrOglrjivgKF9phF58K871eD5kriuWCAdmMl3+3ODDrciBRHXTQgA9rnk4Lu0tNtDkl1X0xb8gVlWu0XAz7ZXyLb1CPYuAu23W/KR1NK2icCS+s+VRqgWRfky22UqvrdxQ82OvHxi+qADzOfW4dTADJQmVwPhNNYM3Yc+ussd6W2DOLQwwIkD3ySu5fHq8CKC8QYhmzkpQMAHwBTrLACKKV8e1GiJBHgtrWPNQ9X3IKerUVuegdlZogE4tedcgeTHw6yNbElsQCUtmMyLMgrsOBnluVzjqCjPQhE4xtTaUM619F0u+RLIsEkgtfc+jIv1TpuAnivucNU8akpPBd7CsAeYE7IiLGT5FunAMjv1+5M5j3eYP1pmZ9lx6GU+myHjG/CD6lGEPgsNwa6eIlvU7Q556ZYMuJyYyF321zi/QLav2jtmoDqc4sRohrNNcQ+zeJrg0Krr0w8vkQeZEHx/BhWauobEyhvoamsPD5r54HjAK0LHskme84DkTEhU9OGii+pvQLCvEHP4+hryf9tC2FQs+3ToCSITzxQ9paWpbnXNH60ngbVQMQVSGkJequmySwMGW2tHvjcMnhYGEx4Zn4NBW2YnCEIwYpMwZKxtX2X+PFlSgKvcLI+3KeDImaXqlYLyMBkBpAEP+mzS8/u6JifiYPRHLPeaUJfn8UsOVPvxSRW63kwgTSigvEPpk/fkHtVj0I6wu4YoqlBSxI0tSer+wJyorBHEeBYlem7MxJGwk6GJj8XvAsUVFLaXV2fra26omAl6YOMqZzj83pCkddJVniHKJbDLrwJXpzjqJCiNumk+uEkv8XIz8Vp4+oNt+ndOjlPZBXmWloPzC1hKxd6B9JeRBMCBzQiJeQfB6Bp856Nsv+p+m7z7wTRfYCR4QXfHd2Af19WbOBTsUOGG4FS+prsmxePjXzx91rJeaFIK5S2konlEzdZywBqdM8h5LW4JuBzUl9j7QMEIwlsC/HxftuMOh9KAomDNp8X60DoG1+b5+40nGLNIIl40RAkqeq7BEM+gYSvmu3YJHJBQ6sH95M/yYWIacZjH+6ltpmxnKdoV4oCt0Iycy93VKb7ovXMcYe5sPkyPIZeK8swviaqw/5l8w8k/MGmvBBNJbXGcpJbVqJP/BXqcyPSyQIbSAGNd0MCK4E6Yzks+s+pV4i5qb8erPGGz3M/MRUurCC8Rhenj+pRlU7MPxY4qDAkgOBQgpdcB7YWamyg8+/8QmwrGl8IaGVWOSjvou50lpvTt039mX5LnZNo1bg2ZaUX8beMcTkqmWSdRkDvSg0XfXaf9L0eRgnZdtu3UQSe98XNm4mbQa1dtFc/682KVc6iie6p24X68n+83iJSV6FeYU3u2KcAAAAAA=" alt="Logo INEA" style="height: 40px; width: auto;" class="me-2">
                        </div>
                        
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