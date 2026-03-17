document.addEventListener('DOMContentLoaded', () => {
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
    
    const idSub = document.getElementById('id_sub_responsiva').value;
    const itemsPO = document.querySelectorAll('.po-item');
    const inputBuscar = document.getElementById('buscarPO');
    const checkFiltroEquipos = document.getElementById('filtroConEquipos');
    const msgNoResults = document.getElementById('msg-no-results');
    
    const contenedorEquipos = document.getElementById('contenedor-equipos');
    const estadoInicial = document.getElementById('estado-inicial');
    const loaderEquipos = document.getElementById('loader-equipos');
    const lblPoActivo = document.getElementById('lbl-po-activo');
    const badgeContadorRight = document.getElementById('badge-contador-right');

    const formTransferir = document.getElementById('formTransferir');
    const selectDestino = document.getElementById('select-po-destino');
    const modalTransferir = new bootstrap.Modal(document.getElementById('modalTransferir'));

    let poActivoId = null;
    let poActivoNombre = null;

    // ==========================================
    // FUNCIÓN AUXILIAR: QUITAR ACENTOS
    // ==========================================
    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    // ==========================================
    // 1. LÓGICA DE FILTRADO (DIRECTORIO)
    // ==========================================
    function aplicarFiltros() {
        if (!inputBuscar) return;

        const textoBusqueda = quitarAcentos(inputBuscar.value.toLowerCase().trim());
        const palabrasBusqueda = textoBusqueda.split(/\s+/);
        const soloConEquipos = checkFiltroEquipos.checked;
        
        let visibles = 0;

        itemsPO.forEach(item => {
            const nombre = quitarAcentos(item.dataset.nombre || "").toLowerCase();
            const numCobro = quitarAcentos(item.dataset.cobro || "").toLowerCase(); 
            const numEquipos = parseInt(item.dataset.equipos) || 0;

            const textoFila = `${nombre} ${numCobro}`;

            let coincideTexto = true;
            if (textoBusqueda !== '') {
                for (let i = 0; i < palabrasBusqueda.length; i++) {
                    if (!textoFila.includes(palabrasBusqueda[i])) {
                        coincideTexto = false;
                        break;
                    }
                }
            }

            const coincideEquipos = soloConEquipos ? (numEquipos > 0) : true;

            if (coincideTexto && coincideEquipos) {
                item.style.display = 'flex';
                item.classList.remove('d-none');
                visibles++;
            } else {
                item.style.display = 'none';
                item.classList.add('d-none');
            }
        });

        if (visibles === 0) {
            msgNoResults.classList.remove('d-none');
        } else {
            msgNoResults.classList.add('d-none');
        }
    }

    inputBuscar.addEventListener('input', aplicarFiltros);
    checkFiltroEquipos.addEventListener('change', aplicarFiltros);
    
    // Ejecutamos el filtro inicial
    aplicarFiltros();

    // ==========================================
    // 2. CARGAR EQUIPOS DEL USUARIO
    // ==========================================
    itemsPO.forEach(item => {
        item.addEventListener('click', function() {
            itemsPO.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            poActivoId = this.dataset.id;
            poActivoNombre = this.querySelector('.po-nombre').textContent;

            lblPoActivo.innerHTML = `<i class="fas fa-user-check me-1 text-success"></i> Mostrando inventario de: <strong>${poActivoNombre}</strong>`;
            
            cargarInventario();
        });
    });

    function cargarInventario() {
        if (!poActivoId) return;

        contenedorEquipos.innerHTML = '';
        if(estadoInicial) estadoInicial.classList.add('d-none');
        badgeContadorRight.classList.add('d-none');
        loaderEquipos.classList.remove('d-none');

        const formData = new FormData();
        formData.append('peticion', 'GET_EQUIPOS');
        formData.append('id_sub', idSub);
        formData.append('id_po', poActivoId);

        fetch('_controller/ResponsivaAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            loaderEquipos.classList.add('d-none');
            
            if (data.result === 1) {
                const equipos = data.data;
                
                badgeContadorRight.classList.remove('d-none');
                badgeContadorRight.textContent = `${equipos.length} equipo(s)`;

                if (equipos.length === 0) {
                    contenedorEquipos.innerHTML = `
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 opacity-25"></i>
                            <h6 class="fw-bold">Sin Equipos</h6>
                            <p class="mb-0 small">Este usuario no tiene activos en resguardo actualmente.</p>
                        </div>`;
                    return;
                }

                equipos.forEach(eq => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6 col-xl-4';
                    card.innerHTML = `
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded d-flex justify-content-center align-items-center me-3" style="width: 45px; height: 45px;">
                                        <i class="fas fa-laptop text-guinda fa-lg"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <h6 class="fw-bold mb-0 text-dark text-truncate" title="${eq.nom_host}">${eq.nom_host}</h6>
                                        <small class="text-muted d-block text-truncate">${eq.marca} / ${eq.modelo}</small>
                                    </div>
                                </div>
                                <div class="mb-3 ps-2 border-start border-3 border-secondary-subtle small">
                                    <div class="text-muted mb-1"><strong class="text-dark">N. Serie:</strong> <span class="font-monospace">${eq.num_ser || 'N/A'}</span></div>
                                    <div class="text-muted"><strong class="text-dark">Inv:</strong> <span class="font-monospace">${eq.num_inv || 'N/A'}</span></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-guinda w-100 fw-bold btn-abrir-transfer" 
                                    data-id="${eq.id_disp}" 
                                    data-host="${eq.nom_host}" 
                                    data-sn="${eq.num_ser || 'Sin Serie'}">
                                    <i class="fas fa-exchange-alt me-1"></i> Transferir...
                                </button>
                            </div>
                        </div>
                    `;
                    contenedorEquipos.appendChild(card);
                });

                document.querySelectorAll('.btn-abrir-transfer').forEach(btn => {
                    btn.addEventListener('click', function() {
                        prepararModalTransferencia(this.dataset);
                    });
                });

            } else {
                Toast.fire({ icon: 'error', title: data.msg });
            }
        })
        .catch(error => {
            loaderEquipos.classList.add('d-none');
            Toast.fire({ icon: 'error', title: 'Error de red al consultar equipos.' });
        });
    }

    // ==========================================
    // 3. LÓGICA DE TRANSFERENCIA (MODAL)
    // ==========================================
    function prepararModalTransferencia(datosEquipo) {
        document.getElementById('transf_id_disp').value = datosEquipo.id;
        document.getElementById('transf_po_origen').value = poActivoId;
        
        document.getElementById('lbl-equipo-trasferir').textContent = datosEquipo.host;
        document.getElementById('lbl-serie-transferir').textContent = `SN: ${datosEquipo.sn}`;
        document.getElementById('lbl-origen-nombre').textContent = poActivoNombre;

        Array.from(selectDestino.options).forEach(opt => {
            opt.disabled = (opt.value === poActivoId);
        });
        selectDestino.value = ""; 

        modalTransferir.show();
    }

    formTransferir.addEventListener('submit', function(e) {
        e.preventDefault();

        const btnSubmit = document.getElementById('btnConfirmarTransferencia');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
        btnSubmit.disabled = true;

        const formData = new FormData(this);
        formData.append('peticion', 'TRANSFERIR_EQUIPO');
        formData.append('id_sub', idSub);

        const idDestino = selectDestino.value;

        fetch('_controller/ResponsivaAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.result === 1) {
                modalTransferir.hide();
                Swal.fire({ icon: 'success', title: '¡Transferencia Exitosa!', text: data.msg, confirmButtonColor: '#9D2449' });
                
                // Actualizar contadores del DOM
                const itemOrigen = document.querySelector(`.po-item[data-id="${poActivoId}"]`);
                const itemDestino = document.querySelector(`.po-item[data-id="${idDestino}"]`);
                
                if (itemOrigen) {
                    let eqOrigen = parseInt(itemOrigen.dataset.equipos) - 1;
                    itemOrigen.dataset.equipos = eqOrigen;
                    const badgeOrigen = itemOrigen.querySelector('.badge-equipos');
                    badgeOrigen.textContent = eqOrigen;
                    if (eqOrigen === 0) {
                        badgeOrigen.classList.replace('bg-guinda', 'bg-secondary');
                        badgeOrigen.classList.add('bg-opacity-50', 'text-dark');
                    }
                }
                
                if (itemDestino) {
                    let eqDestino = parseInt(itemDestino.dataset.equipos) + 1;
                    itemDestino.dataset.equipos = eqDestino;
                    const badgeDestino = itemDestino.querySelector('.badge-equipos');
                    badgeDestino.textContent = eqDestino;
                    if (badgeDestino.classList.contains('bg-secondary')) {
                        badgeDestino.classList.replace('bg-secondary', 'bg-guinda');
                        badgeDestino.classList.remove('bg-opacity-50', 'text-dark');
                    }
                }

                aplicarFiltros(); 
                cargarInventario();
                
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.msg, confirmButtonColor: '#9D2449' });
            }
        })
        .catch(error => {
            Toast.fire({ icon: 'error', title: 'Error al procesar la transferencia.' });
        })
        .finally(() => {
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        });
    });

});