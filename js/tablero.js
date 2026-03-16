document.addEventListener('DOMContentLoaded', () => {

    const panelModulos = document.getElementById('panelModulos');
    const panelSubmodulos = document.getElementById('panelSubmodulos');
    const contenedorSubmodulos = document.getElementById('contenedorSubmodulos');
    const tituloSubmodulos = document.getElementById('tituloSubmodulos');
    const btnVolver = document.getElementById('btnVolverModulos');

    // 1. Escuchar los clics en los módulos que tienen varias opciones
    document.querySelectorAll('.btn-drilldown').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Evitamos que el '#' mueva la pantalla hacia arriba

            const nombreModulo = this.getAttribute('data-nombre');
            const hijosJSON = this.getAttribute('data-hijos');
            
            if (!hijosJSON) return;

            const hijos = JSON.parse(hijosJSON);

            // Cambiamos el título del nuevo panel
            tituloSubmodulos.innerHTML = `<i class="fas fa-layer-group me-2"></i> Opciones de ${nombreModulo}`;

            // Limpiamos el contenedor por si había tarjetas de clics anteriores
            contenedorSubmodulos.innerHTML = '';

            // Dibujamos una tarjeta por cada submódulo CON EL ESTILO ORIGINAL
            for (const slug in hijos) {
                const sub = hijos[slug];
                const nombreSub = sub.nombre;
                
                // CORRECCIÓN: Usamos un icono de bloque/cubo en lugar de la flecha
                const iconoSub = sub.icono || 'fas fa-cube'; 
                const descSub = sub.descripcion || 'Acceder a esta herramienta.'; // Descripción por defecto

                const col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4'; // Mismo ancho que los módulos principales
                
                col.innerHTML = `
                    <div class="card h-100 shadow-sm card-module">
                        <div class="card-body text-center p-4 d-flex flex-column">
                            <div class="mb-3">
                                <i class="${iconoSub} icon-module"></i>
                            </div>
                            <h5 class="card-title fw-bold">${nombreSub}</h5>
                            <p class="card-text text-muted small mb-4">
                                ${descSub}
                            </p>
                            <a href="${slug}/" class="btn btn-inea w-100 mt-auto">
                                <i class="fas fa-sign-in-alt me-1"></i> Entrar
                            </a>
                        </div>
                    </div>
                `;
                contenedorSubmodulos.appendChild(col);
            }

            // Efecto de transición visual: Ocultamos lo principal y mostramos lo secundario
            panelModulos.classList.add('d-none');
            panelSubmodulos.classList.remove('d-none');
            btnVolver.classList.remove('d-none');
        });
    });

    // 2. Escuchar el clic en el botón de "Volver"
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            // Revertimos la transición
            panelSubmodulos.classList.add('d-none');
            btnVolver.classList.add('d-none');
            panelModulos.classList.remove('d-none');
        });
    }
});