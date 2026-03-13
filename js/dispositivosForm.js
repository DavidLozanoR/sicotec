document.addEventListener('DOMContentLoaded', () => {

    const formDispositivo = document.getElementById('formDispositivo');
    const contenedorServicios = document.getElementById('contenedor-servicios');
    const btnAddServicio = document.getElementById('btn-add-servicio');
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });

    const btnGestionarCatalogo = document.getElementById('btn-gestionar-catalogo');
    const modalCatalogoElement = document.getElementById('modalCatalogoServicios');
    const tbodyCatalogo = document.getElementById('tbody-catalogo-servicios');

    // ==========================================
    // LÓGICA DE CONSULTA DE CATÁLOGO (MODAL)
    // ==========================================
    if (btnGestionarCatalogo && modalCatalogoElement) {
        btnGestionarCatalogo.addEventListener('click', () => {
            tbodyCatalogo.innerHTML = '';
            
            if (catalogoServicios.length === 0) {
                tbodyCatalogo.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay servicios registrados en el catálogo del sistema.</td></tr>';
            } else {
                catalogoServicios.forEach(servicio => {
                    // Identificar la versión oficial (la que coincide con id_ver del servicio)
                    const versionOficial = catalogoVersiones.find(v => v.id_ver == servicio.id_ver);
                    const nombreVersionOficial = versionOficial ? versionOficial.ver : 'Indefinida';
                    
                    // Filtrar las demás versiones asociadas a este servicio que no son la oficial
                    const otrasVersiones = catalogoVersiones
                        .filter(v => v.id_serv == servicio.id_serv && v.id_ver != servicio.id_ver)
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

            // Instanciar y mostrar el modal de Bootstrap
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

        // Configurar el nombre del input de versión para que el array sea asociativo si es necesario en PHP
        selectServ.addEventListener('change', function() {
            selectVer.name = `versiones_instaladas[${this.value}]`;
        });

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
            
            validarVersion(selectServ, selectVer, statusVer, fila);
        };

        // Eventos
        selectServ.addEventListener('change', () => cargarVersiones(selectServ.value));
        selectVer.addEventListener('change', () => validarVersion(selectServ, selectVer, statusVer, fila));
        btnRemove.addEventListener('click', () => fila.remove());

        // Carga inicial
        if (idServicioSelec) {
            selectServ.value = idServicioSelec;
            selectVer.name = `versiones_instaladas[${idServicioSelec}]`;
            cargarVersiones(idServicioSelec, idVersionSelec);
        }

        // Eliminar el mensaje de "No hay servicios" si existe
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

    // Inicializar añadir nuevo
    if(btnAddServicio) {
        btnAddServicio.addEventListener('click', () => crearFilaServicio());
    }

    // Manejar eliminación de servicios cargados desde la base de datos
    document.querySelectorAll('.btn-quitar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.list-group-item').remove();
        });
    });

    // Manejar actualización rápida de servicios cargados desde la base de datos
    document.querySelectorAll('.btn-actualizar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('.list-group-item');
            const idServ = this.dataset.idServ;
            const inputVersion = row.querySelector(`input[name="versiones_instaladas[${idServ}]"]`);
            
            // Buscar la versión oficial en el catálogo de servicios
            const servicioCat = catalogoServicios.find(s => s.id_serv == idServ);
            if(servicioCat && inputVersion) {
                // Actualizar el input hidden con la ID de la versión oficial
                inputVersion.value = servicioCat.id_ver;
                
                // Buscar el texto de la versión en el catálogo de versiones
                const versionOficialText = catalogoVersiones.find(v => v.id_ver == servicioCat.id_ver)?.ver || 'Desconocida';
                
                // Actualizar UI
                row.classList.remove('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');
                row.classList.add('bg-white');
                
                const titleH6 = row.querySelector('h6');
                titleH6.classList.remove('text-danger');
                titleH6.classList.add('text-dark');
                // Quitar icono de peligro
                const iconDanger = titleH6.querySelector('.fa-exclamation-triangle');
                if(iconDanger) iconDanger.remove();

                const textSmall = row.querySelector('small');
                textSmall.classList.remove('text-danger');
                textSmall.classList.add('text-muted');
                textSmall.innerHTML = `Instalado: <strong>${versionOficialText}</strong>`;

                // Reemplazar botones de la derecha por el badge de "Al día"
                const textEndDiv = this.parentElement;
                textEndDiv.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 shadow-sm me-2">
                        <i class="fas fa-check-circle me-1"></i> Al día
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quitar-serv" data-id-serv="${idServ}" title="Desinstalar servicio">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;

                // Re-asociar evento de eliminar al nuevo botón
                textEndDiv.querySelector('.btn-quitar-serv').addEventListener('click', function() {
                    this.closest('.list-group-item').remove();
                });

                Toast.fire({ icon: 'success', title: 'Versión actualizada en el formulario. Guarde para aplicar.' });
            }
        });
    });

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

            // Preparar el envío
            const formData = new FormData(this);
            const dataObj = Object.fromEntries(formData.entries());
            
            // Reconstruir los arreglos de software desde los inputs (tanto selects nuevos como hiddens viejos)
            const serviciosInputArray = formData.getAll('servicios_instalados[]');
            const asignacionesSoftware = [];

            serviciosInputArray.forEach(id_serv => {
                // Buscar en el form data el valor asociado a esa llave
                const id_ver_instalada = formData.get(`versiones_instaladas[${id_serv}]`);
                if(id_serv && id_ver_instalada) {
                    asignacionesSoftware.push({
                        id_serv: id_serv,
                        id_ver_instalada: id_ver_instalada
                    });
                }
            });

            // Limpiar FormData suelto y asignar el array de objetos
            dataObj.asignaciones_software = asignacionesSoftware;
            // Eliminar propiedades basura que Object.fromEntries agarró de los arrays
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