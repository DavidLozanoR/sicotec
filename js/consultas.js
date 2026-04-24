document.addEventListener('DOMContentLoaded', () => {
    
    // --- Elementos del DOM ---
    const formConsultas = document.getElementById('form-consultas');
    const btnGenerar = document.getElementById('btn-generar-consulta');
    const tbodyResultados = document.getElementById('tbody-resultados');
    
    const filtroEstadoAsig = document.getElementById('filtro-estado-asig');
    const filtroDepartamento = document.getElementById('filtro-departamento');
    const helpDepartamento = document.getElementById('help-departamento');
    const selectServicio = document.getElementById('filtro-servicio');
    const selectEstadoSoft = document.getElementById('filtro-estado-soft');
    const thSoftware = document.querySelector('.th-software');
    const selectVlanRed = document.getElementById('vlan-select')

    const formBitacora = document.getElementById('form-bitacora');
    const btnGenerarBitacora = document.getElementById('btn-generar-bitacora');
    const tbodyBitacora = document.getElementById('tbody-bitacora');
    const bitacoraTab = document.getElementById('bitacora-tab');

    const btnExportarExcel = document.getElementById('btn-exportar-excel');

    // ==========================================
    // BUSCADOR FUZZY FINDER
    // ==========================================
    function aplicarFuzzySearch(selector) {
        const selectOriginal = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!selectOriginal) return;

        // REACTIVIDAD: Si ya existe una lista, la borramos para reconstruirla con los datos nuevos (ej. IPs)
        if (selectOriginal.nextElementSibling && selectOriginal.nextElementSibling.classList.contains('fuzzy-container')) {
            selectOriginal.nextElementSibling.remove();
        }

        selectOriginal.style.display = 'none';

        const container = document.createElement('div');
        container.className = 'position-relative w-100 fuzzy-container';
        container.style.fontFamily = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

        const inputBuscador = document.createElement('input');
        inputBuscador.type = 'text';
        inputBuscador.className = 'form-control focus-guinda pe-4 shadow-sm';
        inputBuscador.style.cssText = 'border-radius: 0.5rem; border: 1px solid #dee2e6; font-size: 0.95rem; padding: 0.6rem 2.5rem 0.6rem 1rem; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;';
        
        if (selectOriginal.classList.contains('font-monospace')) inputBuscador.classList.add('font-monospace');
        if (selectOriginal.classList.contains('form-select-sm')) inputBuscador.classList.add('form-control-sm');
        
        // Sincronizar bloqueo (disabled) y estilos de alerta
        if (selectOriginal.disabled) {
            inputBuscador.disabled = true;
            inputBuscador.classList.add('bg-light');
            
            // FIX: Si el select original fue marcado en rojo por falta de VLAN, heredamos el diseño
            if (selectOriginal.classList.contains('border-danger')) {
                inputBuscador.classList.remove('border-secondary-subtle');
                inputBuscador.classList.add('border-danger', 'text-danger', 'fw-bold');
            }
        }

        inputBuscador.placeholder = selectOriginal.options.length > 0 && selectOriginal.options[0].text ? selectOriginal.options[0].text : 'Buscar...';
        inputBuscador.autocomplete = 'off';

        const iconClear = document.createElement('span');
        iconClear.innerHTML = '&#x2715;'; // Aspa más estética
        iconClear.className = 'position-absolute text-secondary';
        iconClear.style.cssText = 'right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; display: none; font-size: 0.85rem; z-index: 4; opacity: 0.6; transition: opacity 0.2s ease;';
        iconClear.onmouseover = () => iconClear.style.opacity = '1';
        iconClear.onmouseout = () => iconClear.style.opacity = '0.6';

        const listaDesplegable = document.createElement('ul');
        listaDesplegable.className = 'list-group position-absolute w-100 z-3 d-none overflow-x-hidden shadow-sm';
        listaDesplegable.style.cssText = 'max-height: 220px; overflow-y: auto; top: calc(100% + 4px); left: 0; border-radius: 0.5rem; border: 1px solid #e5e7eb; background-color: #fff; scrollbar-width: thin;';

        Array.from(selectOriginal.options).forEach(opt => {
            if (opt.value === '') return;

            // Conservamos soporte para OPTGROUP si lo usaran los demás selects
            if (opt.parentElement.tagName === 'OPTGROUP') {
                if (opt.parentElement.firstElementChild === opt) {
                    const headerLi = document.createElement('li');
                    headerLi.className = 'list-group-item pe-none';
                    headerLi.style.cssText = 'background-color: #f8f9fa; color: #6c757d; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border: none; padding: 0.75rem 1rem 0.25rem 1rem;';
                    headerLi.textContent = opt.parentElement.label;
                    listaDesplegable.appendChild(headerLi);
                }
            }

            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cssText = 'cursor: pointer; border: none; border-bottom: 1px solid #f3f4f6; padding: 0.6rem 1rem; font-size: 0.9rem; color: #374151; transition: background-color 0.2s ease;';
            li.title = opt.text;
            if (opt.parentElement.tagName === 'OPTGROUP') li.style.paddingLeft = '1.5rem';
            
            // Si el option tiene un HTML inyectado (como las VLANs), lo renderizamos. Sino, texto normal.
            if (opt.dataset.htmlText) {
                li.innerHTML = opt.dataset.htmlText;
            } else {
                li.textContent = opt.text;
            }

            li.dataset.value = opt.value;
            listaDesplegable.appendChild(li);

            if (opt.selected && opt.value !== "") {
                inputBuscador.value = opt.text;
                if (!inputBuscador.disabled) iconClear.style.display = 'block';
            }
        });

        // Eliminar el último borde inferior de la lista
        if (listaDesplegable.lastChild) {
            listaDesplegable.lastChild.style.borderBottom = 'none';
        }

        container.appendChild(inputBuscador);
        container.appendChild(iconClear);
        container.appendChild(listaDesplegable);
        selectOriginal.parentNode.insertBefore(container, selectOriginal.nextSibling);

        function filtrarResultados(texto) {
            const query = texto.toLowerCase();
            let coincidencias = 0;
            const items = listaDesplegable.querySelectorAll('li:not(.pe-none)');

            items.forEach(li => {
                const textoItem = li.textContent.toLowerCase();
                if (textoItem.includes(query)) {
                    li.style.display = 'block';
                    coincidencias++;
                } else {
                    li.style.display = 'none';
                }
            });

            if (coincidencias > 0) {
                listaDesplegable.classList.remove('d-none');
            } else {
                listaDesplegable.classList.add('d-none');
            }
        }

        inputBuscador.addEventListener('focus', () => {
            if (!inputBuscador.disabled) {
                listaDesplegable.classList.remove('d-none');
                filtrarResultados(inputBuscador.value);
            }
        });

        inputBuscador.addEventListener('input', (e) => {
            const valor = e.target.value;
            filtrarResultados(valor);
            iconClear.style.display = valor ? 'block' : 'none';
            selectOriginal.value = ''; 
        });

        listaDesplegable.addEventListener('click', (e) => {
            const li = e.target.closest('li:not(.pe-none)');
            if (!li) return;

            inputBuscador.value = li.textContent;
            listaDesplegable.classList.add('d-none');
            iconClear.style.display = 'block';
            selectOriginal.value = li.dataset.value;
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        iconClear.addEventListener('click', () => {
            inputBuscador.value = '';
            selectOriginal.value = '';
            iconClear.style.display = 'none';
            filtrarResultados('');
            inputBuscador.focus();
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                listaDesplegable.classList.add('d-none');
                if (selectOriginal.value === '') {
                    inputBuscador.value = '';
                    iconClear.style.display = 'none';
                } else {
                    const opcionSeleccionada = selectOriginal.options[selectOriginal.selectedIndex];
                    if (opcionSeleccionada) {
                        inputBuscador.value = opcionSeleccionada.text;
                    }
                }
            }
        });
    }

    // =======================================================
    // REGLAS DE NEGOCIO Y COMPORTAMIENTO DES
    // =======================================================
    if (selectServicio) {
        selectServicio.addEventListener('change', function() {
            if (this.value !== "") {
                selectEstadoSoft.disabled = false;
            } else {
                selectEstadoSoft.disabled = true;
                selectEstadoSoft.value = "todos";
            }
        });
    }

    if (filtroEstadoAsig) {
        filtroEstadoAsig.addEventListener('change', function() {
            if (this.value === 'almacen') {
                filtroDepartamento.value = "";
                filtroDepartamento.disabled = true;
                if(helpDepartamento) helpDepartamento.innerHTML = '<span class="text-danger"><i class="fas fa-info-circle"></i> En almacén no aplican los departamentos.</span>';
            } else {
                filtroDepartamento.disabled = false;
                if(helpDepartamento) helpDepartamento.innerHTML = 'Filtra por el área del responsable.';
            }
        });
    }

    //Llamar a la funcion de fuzzy search para los selects que se desvordan
    aplicarFuzzySearch(filtroDepartamento);
    aplicarFuzzySearch(selectVlanRed);
    aplicarFuzzySearch(selectServicio);


    // =======================================================
    // PETICIÓN AJAX: AUDITORÍA DE EQUIPOS
    // =======================================================
    const ejecutarConsultaAuditoria = () => {
        const originalText = btnGenerar.innerHTML;
        btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Buscando...';
        btnGenerar.disabled = true;

        const formData = new FormData(formConsultas);
        formData.append('peticion', 'EJECUTAR_CONSULTA');

        const auditaSoftware = selectServicio.value !== "";
        if (auditaSoftware && thSoftware) thSoftware.classList.remove('d-none');
        else if (thSoftware) thSoftware.classList.add('d-none');

        fetch('_controller/ConsultasAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.result === 1) {
                tbodyResultados.innerHTML = '';
                
                // Si la consulta viene vacía
                if (data.data.length === 0) {
                    tbodyResultados.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <h5>Búsqueda sin resultados</h5>
                        <p>Ningún equipo cumple con la combinación exacta de estos filtros.</p>
                    </td></tr>`;
                    
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin Resultados',
                        text: 'Modifique los filtros e intente de nuevo.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                // ALERTA DE ÉXITO CUANDO ENCUENTRA DATOS
                Swal.fire({
                    icon: 'success',
                    title: 'Auditoría Exitosa',
                    text: `Se encontraron ${data.data.length} equipos con estos criterios.`,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Renderizar las filas
                data.data.forEach(row => {
                    let colSoftware = '';
                    if (auditaSoftware) {
                        const badgeClass = row.es_obsoleto == 1 ? 'bg-danger' : 'bg-success';
                        const iconClass = row.es_obsoleto == 1 ? 'fa-exclamation-triangle' : 'fa-check-circle';
                        colSoftware = `
                            <td class="py-3">
                                <strong class="text-dark">${row.nombre_software}</strong><br>
                                <span class="badge ${badgeClass}"><i class="fas ${iconClass} me-1"></i> v.${row.version_instalada}</span>
                            </td>
                        `;
                    }

                    const vlanBadge = row.nom_vlan ? `<span class="badge bg-secondary ms-1" style="font-size:0.7rem;">VLAN: ${row.nom_vlan}</span>` : '';
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="py-3 ps-4">
                            <strong class="text-guinda"><i class="fas fa-desktop me-2"></i>${row.nom_host || 'Sin Hostname'}</strong><br>
                            <small class="text-muted"><strong class="text-dark">Mod:</strong> ${row.marca || ''} ${row.modelo || ''}</small><br>
                            <small class="text-muted"><strong class="text-dark">Inv:</strong> ${row.num_inv || 'N/A'}</small>
                        </td>
                        <td class="py-3">
                            <span class="font-monospace small text-primary d-block mb-1"><i class="fas fa-network-wired me-1"></i> ${row.ip || 'IP No Asignada'}</span>
                            ${vlanBadge}
                        </td>
                        <td class="py-3">
                            ${row.responsable ? 
                                `<span class="d-block mb-1"><i class="fas fa-user-tie text-success me-1"></i> ${row.responsable}</span>
                                 <span class="badge bg-light text-dark border border-secondary-subtle"><i class="fas fa-building text-guinda me-1"></i> ${row.departamento || 'N/A'}</span>` 
                                : 
                                `<span class="text-danger small fw-bold"><i class="fas fa-box me-1"></i> Equipo en Almacén</span>`
                            }
                        </td>
                        ${colSoftware}
                    `;
                    tbodyResultados.appendChild(tr);
                });

            } else {
                Swal.fire('Error en la Consulta', data.msg, 'error');
            }
        })
        .catch(error => {
            console.error('Error AJAX:', error);
            Swal.fire('Error de Conexión', 'Hubo un fallo de red al contactar el servidor.', 'error');
        })
        .finally(() => {
            btnGenerar.innerHTML = originalText;
            btnGenerar.disabled = false;
        });
    };

    // AL HACER CLIC EN EL BOTON AUDITORIA
    if (btnGenerar) {
        btnGenerar.addEventListener('click', (e) => {
            e.preventDefault();
            ejecutarConsultaAuditoria();
        });
    }


    // =======================================================
    // PETICIÓN AJAX: BITÁCORA DEL SISTEMA
    // =======================================================
    const extraerDatosBitacora = () => {
        const originalText = btnGenerarBitacora.innerHTML;
        btnGenerarBitacora.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        btnGenerarBitacora.disabled = true;

        const formData = new FormData(formBitacora);
        formData.append('peticion', 'OBTENER_BITACORA');

        fetch('_controller/ConsultasAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.result === 1) {
                tbodyBitacora.innerHTML = '';
                
                if (data.data.length === 0) {
                    tbodyBitacora.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                        <h5>Bitácora Limpia</h5>
                        <p>No se encontraron movimientos registrados con estos parámetros.</p>
                    </td></tr>`;
                    
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin registros',
                        text: 'No se encontraron acciones en estas fechas.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                // ALERTA DE ÉXITO CUANDO ENCUENTRA DATOS
                Swal.fire({
                    icon: 'success',
                    title: 'Bitácora Extraída',
                    text: `Se muestran ${data.data.length} movimientos.`,
                    timer: 2000,
                    showConfirmButton: false
                });

                data.data.forEach(row => {
                    let colorAccion = 'text-secondary';
                    if(row.accion === 'INSERT') colorAccion = 'text-success';
                    if(row.accion === 'UPDATE') colorAccion = 'text-warning text-dark';
                    if(row.accion === 'DELETE' || row.accion === 'BACKUP_BAJA') colorAccion = 'text-danger';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-4">
                            <span class="d-block fw-bold">${row.fecha_fmt}</span>
                            <small class="text-muted"><i class="far fa-clock me-1"></i>${row.hora}</small>
                        </td>
                        <td><i class="fas fa-user-circle me-1 text-primary"></i> <span class="fw-bold">${row.nom_usuar}</span></td>
                        <td><span class="badge bg-light text-dark border">${row.nom_sub}</span></td>
                        <td class="fw-bold ${colorAccion}">${row.accion}</td>
                        <td class="small">${row.detalle}</td>
                    `;
                    tbodyBitacora.appendChild(tr);
                });

            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        })
        .catch(error => {
            console.error('Error AJAX:', error);
            Swal.fire('Error de Conexión', 'No se pudo cargar la información de la bitácora.', 'error');
        })
        .finally(() => {
            btnGenerarBitacora.innerHTML = originalText;
            btnGenerarBitacora.disabled = false;
        });
    };

    // AL HACER CLIC EN EL BOTON BITACORA
    if (btnGenerarBitacora) {
        btnGenerarBitacora.addEventListener('click', (e) => {
            e.preventDefault();
            extraerDatosBitacora();
        });
    }

    // Disparador automático al cambiar a la pestaña de Bitácora por primera vez
    if (bitacoraTab) {
        bitacoraTab.addEventListener('shown.bs.tab', function () {
            if (tbodyBitacora.innerHTML.includes('fa-history fa-3x')) {
                extraerDatosBitacora();
            }
        });
    }

    // =======================================================
    // EXPORTAR EXCEL
    // =======================================================
    if (btnExportarExcel) {
        btnExportarExcel.addEventListener('click', () => {
            Swal.fire({
                icon: 'info',
                title: 'Módulo en Desarrollo',
                text: 'La función para exportar a Excel estará disponible en la próxima actualización.',
                confirmButtonColor: '#9D2449'
            });
        });
    }
});