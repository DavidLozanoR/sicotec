<?php
// Asegurarnos de tener las variables de sesión listas (Respaldos por si acaso)
$nombre_usuario = $_SESSION['usuario']['nombres'] ?? $_SESSION['usuario']['nom_usuar'] ?? 'Usuario';
$permisos_usuario = $_SESSION['permisos'] ?? [];

if (!function_exists('tiene_permiso')) {
    function tiene_permiso($permiso_requerido, $mis_permisos) {
        return in_array($permiso_requerido, $mis_permisos);
    }
}
?>


<div class="mb-4">
    <h2 class="fw-bold text-dark mb-1">Dashboard</h2>
    <p class="text-muted" style="text-transform: capitalize;">Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></p>
</div>

<div class="row g-4">
    
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm card-module">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-users icon-module"></i>
                </div>
                <h5 class="card-title fw-bold">Gestión de Personal Operativo</h5>
                <p class="card-text text-muted small mb-4">
                    Administrar el directorio de trabajadores, actualizar información y asignar equipos de cómputo.
                </p>
                <a href="personal/" class="btn btn-inea w-100 mt-auto">Acceder al Módulo</a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm card-module">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-cog icon-module"></i>
                </div>
                <h5 class="card-title fw-bold">Configuración Global</h5>
                <p class="card-text text-muted small mb-4">
                    Gestionar catálogos de servicios, infraestructura de red y formatos de responsiva de equipos.
                </p>
                <a href="configuracion/" class="btn btn-inea w-100 mt-auto">Acceder al Módulo</a>
            </div>
        </div>
    </div>

    <?php if (tiene_permiso('Consultar', $permisos_usuario)): ?>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm card-module">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-search icon-module"></i>
                </div>
                <h5 class="card-title fw-bold">Consultas y Auditoría</h5>
                <p class="card-text text-muted small mb-4">
                    Realizar búsquedas avanzadas, auditorías de movimientos y generar reportes del sistema.
                </p>
                <a href="consultas/" class="btn btn-inea w-100 mt-auto">Acceder al Módulo</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm card-module">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-shield-alt icon-module"></i>
                </div>
                <h5 class="card-title fw-bold">Administración de Usuarios</h5>
                <p class="card-text text-muted small mb-4">
                    Gestionar cuentas de acceso al sistema, roles y establecer permisos de seguridad.
                </p>
                <a href="usuarios/" class="btn btn-inea w-100 mt-auto">Acceder al Módulo</a>
            </div>
        </div>
    </div>

</div>