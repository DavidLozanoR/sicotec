document.addEventListener('DOMContentLoaded', () => {

    const formDispositivo = document.getElementById('formDispositivo');
    const contenedorServicios = document.getElementById('contenedor-servicios');
    const btnAddServicio = document.getElementById('btn-add-servicio');
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });

    const btnGestionarCatalogo = document.getElementById('btn-gestionar-catalogo');
    const modalCatalogoElement = document.getElementById('modalCatalogoServicios');
    const tbodyCatalogo = document.getElementById('tbody-catalogo-servicios');

    // ==========================================
    // 1. LÓGICA DINÁMICA DE REDES (DEPARTAMENTO -> IP)
    // ==========================================
    const selectRedDep = document.getElementById('select-red-dep');
    const selectRedIp = document.getElementById('select-red-ip');

    if (selectRedDep && selectRedIp && typeof redesDisponibles !== 'undefined') {
        // 1A. Extraer Departamentos Únicos (Ignorando los que no tienen nombre)
        const departamentosRed = [];
        const mapDeps = new Map();
        
        redesDisponibles.forEach(red => {
            const idDep = red.id_dep || 'general';
            const nomDep = red.nom_dep || 'General / Sin Departamento';
            if (!mapDeps.has(idDep)) {
                mapDeps.set(idDep, nomDep);
                departamentosRed.push({ id_dep: idDep, nom_dep: nomDep });
            }
        });

        // Ordenar alfabéticamente
        departamentosRed.sort((a, b) => a.nom_dep.localeCompare(b.nom_dep));

        // Llenar el Select de Departamentos
        departamentosRed.forEach(dep => {
            const option = document.createElement('option');
            option.value = dep.id_dep;
            option.textContent = dep.nom_dep;
            selectRedDep.appendChild(option);
        });

        // 1B. Función para filtrar las IPs según el departamento
        const cargarIPs = (idDep, ipSeleccionada = null) => {
            selectRedIp.innerHTML = '';
            
            // Si elige "En resguardo", apagamos el selector de IP
            if (idDep === 'resguardo') {
                selectRedIp.innerHTML = '<option value="">-- Sin IP (En resguardo) --</option>';
                selectRedIp.disabled = true;
                selectRedIp.classList.add('bg-light');
                return;
            }

            // Si elige un departamento, activamos el selector
            selectRedIp.disabled = false;
            selectRedIp.classList.remove('bg-light');
            
            const redesFiltradas = redesDisponibles.filter(r => (r.id_dep || 'general') == idDep);
            
            if (redesFiltradas.length === 0) {
                selectRedIp.innerHTML = '<option value="">-- No hay IPs disponibles --</option>';
                selectRedIp.disabled = true;
                selectRedIp.classList.add('bg-light');
                return;
            }

            // Llenar las IPs disponibles
            redesFiltradas.forEach(red => {
                const opt = document.createElement('option');
                opt.value = red.id_red;
                opt.textContent = `IP: ${red.ip}`;
                selectRedIp.appendChild(opt);
            });

            // Preseleccionar IP si estamos editando, o la primera de la lista si es nuevo
            if (ipSeleccionada && redesFiltradas.some(r => r.id_red == ipSeleccionada)) {
                selectRedIp.value = ipSeleccionada;
            } else {
                selectRedIp.selectedIndex = 0; // Preselección automática de la primera IP
            }
        };

        // 1C. Evento cuando el usuario cambia el departamento
        selectRedDep.addEventListener('change', function() {
            cargarIPs(this.value);
        });

        // 1D. Carga inicial (Para cuando el form abre en modo Edición)
        let idDepInicial = 'resguardo';
        let idRedInicial = null;

        if (typeof dispositivoActual !== 'undefined' && dispositivoActual && dispositivoActual.id_red) {
            idRedInicial = dispositivoActual.id_red;
            const redEncontrada = redesDisponibles.find(r => r.id_red == idRedInicial);
            if (redEncontrada) {
                idDepInicial = redEncontrada.id_dep || 'general';
            }
        }

        selectRedDep.value = idDepInicial;
        cargarIPs(idDepInicial, idRedInicial);
    }

    // ==========================================
    // AUTO-COMPLETADO AMBICIOSO DESDE JS STORAGE (UX)
    // ==========================================
    const selectAsignado = document.querySelector('select[name="asig_po_id"]');
    const selectResponsable = document.querySelector('select[name="resp_po_id"]');
    const idPoEnMemoria = sessionStorage.getItem('po_preselect_asignado');

    if (idPoEnMemoria && selectAsignado) {
        if (selectAsignado.value === "") {
            selectAsignado.value = idPoEnMemoria;
            selectAsignado.classList.remove('border-secondary-subtle');
            selectAsignado.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'text-success', 'fw-bold');
            
            if (selectResponsable && selectResponsable.value === "") {
                selectResponsable.value = idPoEnMemoria;
                selectResponsable.classList.remove('border-secondary-subtle');
                selectResponsable.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'text-success', 'fw-bold');
            }
        }
        sessionStorage.removeItem('po_preselect_asignado');
    }

    // ==========================================
    // LÓGICA DE CONSULTA DE CATÁLOGO (MODAL)
    // ==========================================
    if (btnGestionarCatalogo && modalCatalogoElement) {
        btnGestionarCatalogo.addEventListener('click', () => {
            tbodyCatalogo.innerHTML = '';
            
            if (catalogoServicios.length === 0) {
                tbodyCatalogo.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay servicios registrados en el catálogo.</td></tr>';
            } else {
                catalogoServicios.forEach(servicio => {
                    const versionOficial = catalogoVersiones.find(v => v.id_serv == servicio.id_serv && String(v.obs) === "0");
                    const nombreVersionOficial = versionOficial ? versionOficial.ver : 'Sin versión oficial';
                    
                    const otrasVersiones = catalogoVersiones
                        .filter(v => v.id_serv == servicio.id_serv && String(v.obs) === "1")
                        .map(v => `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle mb-1 me-1">${v.ver}</span>`)
                        .join('');

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="fw-bold text-dark ps-4">${servicio.nom}</td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle shadow-sm px-2 py-1">
                                <i class="fas fa-check-circle me-1"></i> ${nombreVersionOficial}
                            </span>
                        </td>
                        <td>${otrasVersiones || '<span class="text-muted small">Ninguna</span>'}</td>
                    `;
                    tbodyCatalogo.appendChild(tr);
                });
            }

            const modalCatalogo = new bootstrap.Modal(modalCatalogoElement);
            modalCatalogo.show();
        });
    }

    // ==========================================
    // LÓGICA DE MÚLTIPLES SERVICIOS Y VERSIONES
    // ==========================================
    function crearFilaServicio(idServicioSelec = '', idVersionSelec = '') {
        const fila = document.createElement('label');
        fila.className = 'list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action bg-white';
        
        fila.innerHTML = `
            <div class="d-flex align-items-start gap-3 w-100 me-3">
                <div class="row g-2 w-100 align-items-center">
                    <div class="col-md-6">
                        <select class="form-select select-servicio border-secondary-subtle focus-guinda" name="servicios_instalados[]" required>
                            <option value="" disabled selected>Seleccione software...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select select-version border-secondary-subtle focus-guinda" required>
                            <option value="" disabled selected>Seleccione versión...</option>
                        </select>
                        <div class="status-version mt-1 small"></div>
                    </div>
                </div>
            </div>
            <div class="text-end text-nowrap mt-1">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Quitar servicio">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;

        const selectServ = fila.querySelector('.select-servicio');
        const selectVer = fila.querySelector('.select-version');
        const statusVer = fila.querySelector('.status-version');
        const btnRemove = fila.querySelector('.btn-remove-row');

        selectServ.addEventListener('change', function() {
            selectVer.name = `versiones_instaladas[${this.value}]`;
        });

        catalogoServicios.forEach(s => {
            const option = document.createElement('option');
            option.value = s.id_serv;
            option.textContent = s.nom;
            
            const versionOficial = catalogoVersiones.find(v => v.id_serv == s.id_serv && String(v.obs) === "0");
            option.dataset.oficial = versionOficial ? versionOficial.id_ver : '';
            
            selectServ.appendChild(option);
        });

        const cargarVersiones = (idServicioActual, versionSeleccionada = '') => {
            selectVer.innerHTML = '<option value="" disabled selected>Seleccione versión...</option>';
            const versionesFiltradas = catalogoVersiones.filter(v => v.id_serv == idServicioActual);
            
            versionesFiltradas.forEach(v => {
                const option = document.createElement('option');
                option.value = v.id_ver;
                option.textContent = v.ver;
                selectVer.appendChild(option);
            });

            if (versionSeleccionada) {
                selectVer.value = versionSeleccionada;
            }
            
            validarVersion(selectServ, selectVer, statusVer, fila);
        };

        selectServ.addEventListener('change', () => cargarVersiones(selectServ.value));
        selectVer.addEventListener('change', () => validarVersion(selectServ, selectVer, statusVer, fila));
        btnRemove.addEventListener('click', () => fila.remove());

        if (idServicioSelec) {
            selectServ.value = idServicioSelec;
            selectVer.name = `versiones_instaladas[${idServicioSelec}]`;
            cargarVersiones(idServicioSelec, idVersionSelec);
        }

        const msjVacio = document.getElementById('mensaje-sin-servicios');
        if(msjVacio) msjVacio.remove();

        contenedorServicios.appendChild(fila);
    }

    function validarVersion(selectServ, selectVer, containerStatus, fila) {
        containerStatus.innerHTML = '';
        selectVer.classList.remove('is-valid', 'is-invalid', 'border-danger');
        fila.classList.remove('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');

        if (!selectServ.value || !selectVer.value) return;

        const optionServ = selectServ.options[selectServ.selectedIndex];
        const idVerOficial = optionServ.dataset.oficial;

        if (selectVer.value === idVerOficial) {
            selectVer.classList.add('is-valid');
            containerStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Oficial (Al día)</span>';
        } else {
            selectVer.classList.add('is-invalid', 'border-danger');
            fila.classList.add('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');
            containerStatus.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Obsoleta/Distinta</span>';
        }
    }

    if(btnAddServicio) {
        btnAddServicio.addEventListener('click', () => crearFilaServicio());
    }

    // ==========================================
    // ACTUALIZAR SERVICIO CARGADO (BOTÓN RÁPIDO)
    // ==========================================
    document.querySelectorAll('.btn-quitar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.list-group-item').remove();
        });
    });

    document.querySelectorAll('.btn-actualizar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('.list-group-item');
            const idServ = this.dataset.idServ;
            const inputVersion = row.querySelector(`input[name="versiones_instaladas[${idServ}]"]`);
            
            const versionOficial = catalogoVersiones.find(v => v.id_serv == idServ && String(v.obs) === "0");
            
            if(versionOficial && inputVersion) {
                inputVersion.value = versionOficial.id_ver;
                
                row.classList.remove('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');
                row.classList.add('bg-white');
                
                const titleH6 = row.querySelector('h6');
                titleH6.classList.remove('text-danger');
                titleH6.classList.add('text-dark');
                
                const iconDanger = titleH6.querySelector('.fa-exclamation-triangle');
                if(iconDanger) iconDanger.remove();

                const textSmall = row.querySelector('small');
                textSmall.classList.remove('text-danger');
                textSmall.classList.add('text-muted');
                textSmall.innerHTML = `Instalado: <strong>${versionOficial.ver}</strong>`;

                const textEndDiv = this.parentElement;
                textEndDiv.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 shadow-sm me-2">
                        <i class="fas fa-check-circle me-1"></i> Al día
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quitar-serv" data-id-serv="${idServ}" title="Desinstalar servicio">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;

                textEndDiv.querySelector('.btn-quitar-serv').addEventListener('click', function() {
                    this.closest('.list-group-item').remove();
                });

                Toast.fire({ icon: 'success', title: 'Versión actualizada. Guarde los cambios.' });
            } else {
                Toast.fire({ icon: 'warning', title: 'Este servicio no tiene una versión oficial.' });
            }
        });
    });

    // ==========================================
    // ENVÍO DEL FORMULARIO
    // ==========================================
    if (formDispositivo) {
        formDispositivo.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
                const firstInvalid = this.querySelector(':invalid');
                if (firstInvalid) {
                    const tabPane = firstInvalid.closest('.tab-pane');
                    if (tabPane) {
                        const tabId = tabPane.getAttribute('id');
                        const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                        if (tabButton) {
                            const tab = new bootstrap.Tab(tabButton);
                            tab.show();
                            setTimeout(() => firstInvalid.focus(), 200);
                        }
                    }
                }
                this.classList.add('was-validated');
                Toast.fire({ icon: 'warning', title: 'Complete los campos obligatorios.' });
                return;
            }

            const formData = new FormData(this);
            const dataObj = Object.fromEntries(formData.entries());
            
            const serviciosInputArray = formData.getAll('servicios_instalados[]');
            
            // ESCUDO: PREVENIR SERVICIOS DUPLICADOS
            const uniqueServs = new Set(serviciosInputArray);
            if (uniqueServs.size !== serviciosInputArray.length) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Servicios Duplicados', 
                    text: 'Ha seleccionado el mismo software más de una vez. Quite los duplicados de la lista antes de guardar.', 
                    confirmButtonColor: '#9D2449' 
                });
                return; 
            }

            const asignacionesSoftware = [];

            serviciosInputArray.forEach(id_serv => {
                const id_ver_instalada = formData.get(`versiones_instaladas[${id_serv}]`);
                if(id_serv && id_ver_instalada) {
                    asignacionesSoftware.push({
                        id_serv: id_serv,
                        id_ver_instalada: id_ver_instalada
                    });
                }
            });

            dataObj.asignaciones_software = asignacionesSoftware;
            for(let key in dataObj) {
                if(key.includes('versiones_instaladas') || key.includes('servicios_instalados')) {
                    delete dataObj[key];
                }
            }

            const btnSubmit = document.getElementById('btnGuardarDispositivo');
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
            btnSubmit.disabled = true;

            fetch('_controller/DispositivosAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataObj)
            })
            .then(response => response.json())
            .then(data => {
                if (data.result === 1 || data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'El dispositivo se guardó correctamente.', confirmButtonColor: '#9D2449' })
                    .then(() => window.location.href = 'dispositivos/');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'Ocurrió un problema.', confirmButtonColor: '#9D2449' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({ icon: 'error', title: 'Error de comunicación con el servidor.' });
            })
            .finally(() => {
                btnSubmit.innerHTML = originalBtnText;
                btnSubmit.disabled = false;
            });
        });
    }
});