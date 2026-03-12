document.addEventListener('DOMContentLoaded', () => {

    const formDispositivo = document.getElementById('formDispositivo');
    const contenedorServicios = document.getElementById('contenedor-servicios');
    const btnAddServicio = document.getElementById('btn-add-servicio');
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });

    // ==========================================
    // LÓGICA DE MÚLTIPLES SERVICIOS Y VERSIONES
    // ==========================================

    function crearFilaServicio(idServicioSelec = '', idVersionSelec = '') {
        const fila = document.createElement('div');
        fila.className = 'row g-2 align-items-center p-3 border border-secondary-subtle rounded-3 bg-light shadow-sm';
        
        fila.innerHTML = `
            <div class="col-md-5">
                <label class="form-label fw-bold small mb-1">Software / Servicio</label>
                <select class="form-select select-servicio" name="servicios[]" required>
                    <option value="" disabled selected>Seleccione servicio...</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small mb-1">Versión Instalada</label>
                <select class="form-select select-version" name="versiones[]" required>
                    <option value="" disabled selected>Seleccione versión...</option>
                </select>
                <div class="status-version mt-1 small"></div>
            </div>
            <div class="col-md-2 text-end mt-4">
                <button type="button" class="btn btn-outline-danger btn-remove-row" title="Quitar servicio">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;

        const selectServ = fila.querySelector('.select-servicio');
        const selectVer = fila.querySelector('.select-version');
        const statusVer = fila.querySelector('.status-version');
        const btnRemove = fila.querySelector('.btn-remove-row');

        // Poblar select de servicios
        catalogoServicios.forEach(s => {
            const option = document.createElement('option');
            option.value = s.id_serv;
            option.textContent = s.nom;
            option.dataset.oficial = s.id_ver;
            selectServ.appendChild(option);
        });

        // Función para cargar las versiones filtradas
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
            
            validarVersion(selectServ, selectVer, statusVer);
        };

        // Evento cambio de servicio (recarga versiones)
        selectServ.addEventListener('change', () => {
            cargarVersiones(selectServ.value);
        });
        
        // Evento cambio de versión (valida estatus)
        selectVer.addEventListener('change', () => validarVersion(selectServ, selectVer, statusVer));

        // Evento eliminar fila
        btnRemove.addEventListener('click', () => fila.remove());

        // Carga inicial si viene de BD
        if (idServicioSelec) {
            selectServ.value = idServicioSelec;
            cargarVersiones(idServicioSelec, idVersionSelec);
        }

        contenedorServicios.appendChild(fila);
    }

    function validarVersion(selectServ, selectVer, containerStatus) {
        containerStatus.innerHTML = '';
        selectVer.classList.remove('is-valid', 'is-invalid');

        if (!selectServ.value || !selectVer.value) return;

        const optionServ = selectServ.options[selectServ.selectedIndex];
        const idVerOficial = optionServ.dataset.oficial;

        if (selectVer.value === idVerOficial) {
            selectVer.classList.add('is-valid');
            containerStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Versión Oficial (Al día)</span>';
        } else {
            selectVer.classList.add('is-invalid');
            containerStatus.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Versión Obsoleta/Distinta</span>';
        }
    }

    // Inicializar filas existentes
    if (serviciosAsignadosIniciales.length > 0) {
        serviciosAsignadosIniciales.forEach(asig => {
            crearFilaServicio(asig.id_serv, asig.id_ver_instalada);
        });
    }

    btnAddServicio.addEventListener('click', () => crearFilaServicio());

    // ==========================================
    // VALIDACIÓN Y ENVÍO DEL FORMULARIO
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
            // Reestructurar los arrays de servicios y versiones para enviarlos limpios al backend
            const serviciosArray = formData.getAll('servicios[]');
            const versionesArray = formData.getAll('versiones[]');
            
            // Eliminar los arrays raw del FormData para no duplicar datos
            formData.delete('servicios[]');
            formData.delete('versiones[]');

            const dataObj = Object.fromEntries(formData.entries());
            
            // Combinar los arreglos en un objeto para el controlador
            dataObj.asignaciones_software = serviciosArray.map((id_serv, index) => ({
                id_serv: id_serv,
                id_ver_instalada: versionesArray[index]
            }));

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