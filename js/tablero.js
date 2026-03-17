document.addEventListener('DOMContentLoaded', () => {

    const panelModulos = document.getElementById('panelModulos');
    const panelSubmodulos = document.getElementById('panelSubmodulos');
    const contenedorSubmodulos = document.getElementById('contenedorSubmodulos');
    const tituloSubmodulos = document.getElementById('tituloSubmodulos');
    const btnVolver = document.getElementById('btnVolverModulos');

    // 1. Escuchar los clics en los módulos que tienen varias opciones
    document.querySelectorAll('.btn-drilldown').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 

            const nombreModulo = this.getAttribute('data-nombre');
            const hijosJSON = this.getAttribute('data-hijos');
            
            if (!hijosJSON) return;

            const hijos = JSON.parse(hijosJSON);

            tituloSubmodulos.innerHTML = `<i class="fas fa-layer-group me-2"></i> Opciones de ${nombreModulo}`;
            contenedorSubmodulos.innerHTML = '';

            for (const slug in hijos) {
                const sub = hijos[slug];
                const nombreSub = sub.nombre;
                
                const iconoSub = sub.icono || 'fas fa-cube'; 
                const descSub = sub.descripcion || 'Acceder a esta herramienta.';

                // LÓGICA DE BOTÓN DINÁMICO
                // Si el slug es 'diccionario', creamos un botón que abre el Offcanvas.
                // Si es cualquier otro, creamos el enlace normal de navegación.
                let botonAccionHTML = '';
                
                if (slug === 'diccionario') {
                    botonAccionHTML = `
                        <button type="button" class="btn btn-inea w-100 mt-auto" data-bs-toggle="offcanvas" data-bs-target="#offcanvasDiccionario" aria-controls="offcanvasDiccionario">
                            <i class="fas fa-book-open me-1"></i> Abrir Diccionario
                        </button>
                    `;
                } else {
                    botonAccionHTML = `
                        <a href="${slug}/" class="btn btn-inea w-100 mt-auto">
                            <i class="fas fa-sign-in-alt me-1"></i> Entrar
                        </a>
                    `;
                }

                const col = document.createElement('div');
                col.className = 'col-12 col-md-6 col-lg-4'; 
                
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
                            ${botonAccionHTML}
                        </div>
                    </div>
                `;
                contenedorSubmodulos.appendChild(col);
            }

            panelModulos.classList.add('d-none');
            panelSubmodulos.classList.remove('d-none');
            btnVolver.classList.remove('d-none');
        });
    });

    // 2. Escuchar el clic en el botón de "Volver"
    if (btnVolver) {
        btnVolver.addEventListener('click', () => {
            panelSubmodulos.classList.add('d-none');
            btnVolver.classList.add('d-none');
            panelModulos.classList.remove('d-none');
        });
    }
});