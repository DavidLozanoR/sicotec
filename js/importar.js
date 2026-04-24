/**
 * Módulo de Importación Masiva (SICOSTE)
 * Arquitectura: Event-Driven & Modular DOM Manipulation (Dropdowns Responsivos)
 */

document.addEventListener('DOMContentLoaded', () => {
    // ==========================================
    // 1. CACHÉ DE ELEMENTOS DOM
    // ==========================================
    const UI = {
        views: {
            upload: document.getElementById('section_upload'),
            resolution: document.getElementById('section_resolucion')
        },
        inputs: {
            file: document.getElementById('inputArchivoMasivo')
        },
        buttons: {
            start: document.getElementById('btnIniciarAnalisis'),
            finalize: document.getElementById('btnFinalizarImportacion')
        },
        indicators: {
            progress: document.getElementById('upload_progress'),
            badgeDepts: document.getElementById('badge-conflictos-dep'),
            badgeBajas: document.getElementById('badge-bajas')
        },
        tables: {
            depts: document.getElementById('tbodyConflictosDep'),
            bajas: document.getElementById('tbodyBajasDetectadas'),
            movimientos: document.getElementById('tbodyMovimientos')
        },
        alerts: {
            desaparecidos: document.getElementById('alert-desaparecidos'),
            divisiones: document.getElementById('alert-divisiones')
        }
    };

    let catalogoGlobal = [];

    // ==========================================
    // 2. CONTROLADORES DE EVENTOS PRINCIPALES
    // ==========================================

    UI.buttons.start.addEventListener('click', procesarArchivo);
    UI.buttons.finalize.addEventListener('click', ejecutarSincronizacion);
    
    // NUEVO: Delegación de eventos global para los Dropdowns Custom (Lista <ul>)
    document.addEventListener('click', (e) => {
        const item = e.target.closest('.item-custom-select');
        if (!item) return;

        e.preventDefault();
        
        const container = item.closest('.dropdown');
        const btnText = container.querySelector('.btn-text');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        const val = item.dataset.val;

        // Si es el disparador del Modal de Búsqueda
        if (val === 'VINCULAR_EXISTENTE') {
            abrirModalBusqueda(hiddenInput, container);
            return;
        }

        // Actualizamos visualmente el botón con el HTML del elemento clickeado
        btnText.innerHTML = item.innerHTML;
        hiddenInput.value = val;

        // Limpiamos bordes de advertencia
        const btn = container.querySelector('.btn');
        btn.classList.remove('border-warning', 'text-muted');
        btn.classList.add('border-secondary-subtle');

        // Si es un cambio en el área de Departamentos, sincronizamos los movimientos
        if (hiddenInput.classList.contains('res-dept')) {
            sincronizarDestinosMovimientos();
        }
    });

    // ==========================================
    // 3. LÓGICA DE NEGOCIO: PASO 1 (SUBIDA)
    // ==========================================

    async function procesarArchivo() {
        const archivo = UI.inputs.file.files[0];
        
        if (!archivo) return Swal.fire('Archivo requerido', 'Seleccione un documento para analizar.', 'warning');

        const ext = archivo.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'csv', 'xml'].includes(ext)) {
            return Swal.fire('Formato restringido', 'Asegure que su archivo sea del tipo .xlsx, .csv o .xml', 'error');
        }

        const formData = new FormData();
        formData.append('archivo_masivo', archivo);
        formData.append('id_sub', ID_SUB_PERSONAL); 
        formData.append('peticion', 'SUBIR_ARCHIVO'); 

        cambiarEstadoCarga(true);

        try {
            const res = await fetch('_controller/ImportacionAsync.php', { method: 'POST', body: formData });
            const data = await parseJSON(res);

            if (data.status === 'success') {
                obtenerConflictos();
            } else {
                Swal.fire('Fallo de Análisis', data.msg, 'error');
                cambiarEstadoCarga(false);
            }
        } catch (error) {
            Swal.fire('Error Crítico', error.message, 'error');
            cambiarEstadoCarga(false);
        }
    }

    // ==========================================
    // 4. LÓGICA DE NEGOCIO: PASO 2 (CONFLICTOS)
    // ==========================================

    async function obtenerConflictos() {
        try {
            const res = await fetch('_controller/ImportacionAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ peticion: 'GET_CONFLICTOS', id_sub: ID_SUB_PERSONAL })
            });
            const data = await parseJSON(res);
            
            catalogoGlobal = data.catalogo_depts || [];
            
            UI.views.upload.classList.add('d-none');
            UI.views.resolution.classList.remove('d-none');
            
            renderizarDiagnostico(data.diagnostico, data.cambios_cobro);
            renderizarTablaDepartamentos(data.depts);
            renderizarTablaBajas(data.bajas);
            renderizarTablaMovimientos(data.movimientos);
            renderizarAlertasAreas(data.desaparecidos, data.divisiones);

            sincronizarDestinosMovimientos();

            if (data.alerta_csv) {
                Swal.fire('Codificación Detectada', 'El sistema normalizó caracteres especiales. Revise los departamentos generados.', 'info');
            }

        } catch (error) {
            Swal.fire('Error al procesar conflictos', error.message, 'error');
        }
    }

    async function ejecutarSincronizacion() {
        // Al usar hidden inputs, esta lógica no cambia
        const inputsDepts = document.querySelectorAll('.res-dept');
        const inputsMovs = document.querySelectorAll('.res-mov'); 
        const inputsBajas = document.querySelectorAll('.res-baja'); 

        const faltanDepts = Array.from(inputsDepts).some(inp => !inp.value || inp.value === 'VINCULAR_EXISTENTE');
        const faltanMovs = Array.from(inputsMovs).some(inp => !inp.value);
        const faltanBajas = Array.from(inputsBajas).some(inp => !inp.value);

        if (faltanDepts || faltanMovs || faltanBajas) {
            return Swal.fire('Decisiones pendientes', 'Asegúrese de seleccionar una acción en todas las listas desplegables resaltadas en amarillo.', 'warning');
        }

        const resolucionesDepts = Array.from(inputsDepts).map(inp => ({
            clave: inp.dataset.clave,
            nombre: inp.dataset.nombre,
            accion: inp.value 
        }));

        const numCobrosBaja = Array.from(document.querySelectorAll('.check-baja:checked')).map(chk => chk.value);
        const bajasSelects = Array.from(inputsBajas).filter(inp => inp.value === 'BAJA_LIBERAR').map(inp => inp.dataset.cobro);
        numCobrosBaja.push(...bajasSelects);

        const ignorarCambioCobros = Array.from(inputsMovs).filter(inp => inp.value === 'IGNORAR').map(inp => inp.dataset.cobro);
        
        const liberarEquiposMovs = Array.from(inputsMovs).filter(inp => inp.value === 'LIBERAR').map(inp => inp.dataset.cobro);
        const liberarEquiposCobros = [...liberarEquiposMovs, ...bajasSelects]; 

        const confirmar = await Swal.fire({
            title: '¿Ejecutar Sincronización?',
            text: "Esta acción afectará la base de datos de producción.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, sincronizar'
        });

        if (!confirmar.isConfirmed) return;

        UI.buttons.finalize.disabled = true;
        UI.buttons.finalize.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ejecutando...';

        try {
            const res = await fetch('_controller/ImportacionAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    peticion: 'EJECUTAR_FINAL', 
                    id_sub: ID_SUB_PERSONAL, 
                    resolucionesDepts, 
                    numCobrosBaja,
                    ignorarCambioCobros,
                    liberarEquiposCobros
                })
            });
            const data = await parseJSON(res);

            if (data.status === 'success') {
                await Swal.fire('Completado', data.msg, 'success');
                window.location.href = 'personal';
            } else {
                throw new Error(data.msg);
            }
        } catch (error) {
            Swal.fire('Fallo en Sincronización', error.message, 'error');
            UI.buttons.finalize.disabled = false;
            UI.buttons.finalize.innerText = 'Ejecutar Sincronización Final';
        }
    }

    // ==========================================
    // 5. MOTORES DE RENDERIZADO (UI) - Dropdowns Custom
    // ==========================================

    function renderizarDiagnostico(diag, cambiosCobro = []) {
        const oldPanel = document.getElementById('panel-diagnostico');
        if (oldPanel) oldPanel.remove();
        if (!diag) return;

        const htmlMuestra = (diag.muestra && diag.muestra.length > 0) 
            ? diag.muestra.map(d => `<li>[${d.clave_dep_raw || 'S/C'}] ${d.nom_dep_raw || 'Vacío'}</li>`).join('')
            : '<li><i class="text-muted">(Sin datos)</i></li>';

        let htmlCambios = '';
        if (cambiosCobro.length > 0) {
            htmlCambios = `
                <div class="alert alert-info mt-3 mb-0 shadow-sm border-0 border-start border-4 border-info">
                    <i class="fas fa-id-card me-2"></i><b>Se detectaron ${cambiosCobro.length} empleados que cambiaron de Número de Cobro.</b><br>
                    <small>El sistema los identificó por su correo o nombre y actualizará su número automáticamente para conservar su historial de equipos.</small>
                </div>
            `;
        }

        const isWarning = diag.vacios > 0;
        const panelDiv = document.createElement('div');
        panelDiv.id = 'panel-diagnostico';
        panelDiv.className = `alert ${isWarning ? 'alert-warning' : 'alert-primary'} mb-4 shadow-sm`;
        
        panelDiv.innerHTML = `
            <h6 class="fw-bold"><i class="fas fa-microscope me-2"></i>Análisis de Memoria</h6>
            <div class="row small mt-2">
                <div class="col-md-6">
                    <p class="mb-1">Total procesados: <b>${diag.total}</b></p>
                    <p class="mb-0 text-${isWarning ? 'danger' : 'success'}">
                        Registros sin área asignada: <b>${diag.vacios}</b>
                    </p>
                </div>
                <div class="col-md-6 border-start">
                    Muestra de departamentos leídos:
                    <ul class="mb-0 ps-3 mt-1" style="max-height: 80px; overflow-y: auto;">${htmlMuestra}</ul>
                </div>
            </div>
            ${htmlCambios}
        `;
        UI.views.resolution.prepend(panelDiv);
    }

    function renderizarTablaDepartamentos(depts) {
        if (!UI.tables.depts) return;
        if (UI.indicators.badgeDepts) UI.indicators.badgeDepts.innerText = depts.length;
        
        UI.tables.depts.innerHTML = depts.map(d => {
            // Construcción del Dropdown Custom
            let listaOpciones = `<li><a class="dropdown-item item-custom-select text-wrap text-success py-2 border-bottom" href="#" data-val="CREAR">✨ Crear como nuevo</a></li>`;
            
            if (d.sugerencias?.length) {
                listaOpciones += `<li><h6 class="dropdown-header text-muted small bg-light">Sugerencias del sistema</h6></li>`;
                d.sugerencias.forEach(s => {
                    listaOpciones += `<li><a class="dropdown-item item-custom-select text-wrap py-2 border-bottom" href="#" data-val="VINCULAR_${s.id_dep}">🔗 ${s.nom}</a></li>`;
                });
            }
            listaOpciones += `<li><a class="dropdown-item item-custom-select text-wrap text-primary fw-bold py-2" href="#" data-val="VINCULAR_EXISTENTE"><i class="fas fa-search me-1"></i> Buscar en catálogo oficial...</a></li>`;

            return `
                <tr>
                    <td class="text-wrap" style="width: 20%; min-width: 80px;"><b>${d.clave_dep_raw || 'N/A'}</b></td>
                    <td class="text-wrap" style="width: 35%; min-width: 120px;">${d.nom_dep_raw}</td>
                    <td style="width: 45%; min-width: 220px;">
                        <div class="dropdown w-100">
                            <button class="btn btn-sm bg-white border border-secondary-subtle w-100 d-flex justify-content-between align-items-center shadow-sm text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 38px;">
                                <span class="btn-text text-wrap flex-grow-1 text-success">✨ Crear como nuevo</span>
                                <i class="fas fa-chevron-down text-muted ms-2" style="font-size: 0.8rem;"></i>
                            </button>
                            <ul class="dropdown-menu w-100 shadow" style="max-height: 250px; overflow-y: auto;">
                                ${listaOpciones}
                            </ul>
                            <input type="hidden" class="res-dept" data-clave="${d.clave_dep_raw}" data-nombre="${d.nom_dep_raw}" value="CREAR">
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderizarTablaBajas(bajas) {
        if (!UI.tables.bajas) return;
        if (UI.indicators.badgeBajas) UI.indicators.badgeBajas.innerText = bajas.length;
        
        UI.tables.bajas.innerHTML = bajas.map(b => {
            const equipos = parseInt(b.total_dispositivos);
            const tieneEquipos = equipos > 0;
            const badge = tieneEquipos ? `<span class="badge bg-danger">${equipos} Equipos</span>` : `<span class="badge bg-success">Libre</span>`;
            
            let accionHTML = '';
            if (tieneEquipos) {
                accionHTML = `
                    <div class="dropdown w-100">
                        <button class="btn btn-sm bg-white border border-warning w-100 d-flex justify-content-between align-items-center shadow-sm text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 38px;">
                            <span class="btn-text text-wrap flex-grow-1 text-muted">-- Seleccionar Acción --</span>
                            <i class="fas fa-chevron-down text-muted ms-2" style="font-size: 0.8rem;"></i>
                        </button>
                        <ul class="dropdown-menu w-100 shadow">
                            <li><a class="dropdown-item item-custom-select text-wrap py-2 border-bottom" href="#" data-val="IGNORAR">Ignorar (Dejar activo en el sistema)</a></li>
                            <li><a class="dropdown-item item-custom-select text-wrap text-danger fw-bold py-2" href="#" data-val="BAJA_LIBERAR">Liberar sus ${equipos} equipos y Dar de Baja</a></li>
                        </ul>
                        <input type="hidden" class="res-baja" data-cobro="${b.num_cobro}" value="">
                    </div>
                `;
            } else {
                accionHTML = `
                    <div class="text-center">
                        <input class="form-check-input check-baja cursor-pointer shadow-sm border-secondary-subtle" type="checkbox" value="${b.num_cobro}" style="width: 1.5rem; height: 1.5rem;">
                    </div>
                `;
            }
            
            return `
                <tr class="${tieneEquipos ? 'table-warning' : ''}">
                    <td class="text-nowrap" style="width: 15%;">${b.num_cobro}</td>
                    <td class="text-wrap" style="width: 35%; min-width: 150px;">${b.nombres} ${b.ape_pa} ${b.ape_ma}</td>
                    <td style="width: 20%;">${badge}</td>
                    <td style="width: 30%; min-width: 200px;">${accionHTML}</td>
                </tr>
            `;
        }).join('');
    }

    function renderizarTablaMovimientos(movimientos) {
        if (!UI.tables.movimientos) return;
        
        if (!movimientos || movimientos.length === 0) {
            UI.tables.movimientos.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No se detectaron movimientos de área del personal.</td></tr>';
            return;
        }

        UI.tables.movimientos.innerHTML = movimientos.map(m => {
            const equipos = parseInt(m.total_equipos);
            const tieneEquipos = equipos > 0;
            
            let accionHTML = '<span class="badge bg-success shadow-sm px-2 py-2"><i class="fas fa-check me-1"></i> Se moverá libremente</span>';
            
            if (tieneEquipos) {
                accionHTML = `
                    <div class="dropdown w-100">
                        <button class="btn btn-sm bg-white border border-warning w-100 d-flex justify-content-between align-items-center shadow-sm text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 38px;">
                            <span class="btn-text text-wrap flex-grow-1 text-muted">-- Seleccionar Acción --</span>
                            <i class="fas fa-chevron-down text-muted ms-2" style="font-size: 0.8rem;"></i>
                        </button>
                        <ul class="dropdown-menu w-100 shadow">
                            <li><a class="dropdown-item item-custom-select text-wrap py-2 border-bottom" href="#" data-val="IGNORAR">Ignorar cambio (Dejar en su área actual)</a></li>
                            <li><a class="dropdown-item item-custom-select text-wrap text-primary fw-bold py-2" href="#" data-val="LIBERAR">Liberar sus ${equipos} equipos y Mover de área</a></li>
                        </ul>
                        <input type="hidden" class="res-mov" data-cobro="${m.num_cobro}" value="">
                    </div>
                `;
            }

            return `
                <tr class="${tieneEquipos ? 'table-warning' : ''}">
                    <td class="text-nowrap" style="width: 10%;">${m.num_cobro}</td>
                    <td class="text-wrap" style="width: 25%; min-width: 130px;">${m.nombres} ${m.ape_pa} ${m.ape_ma}</td>
                    <td class="text-wrap text-muted" style="width: 20%; min-width: 120px;"><del>${m.dep_origen_nom}</del></td>
                    <td class="text-wrap fw-bold text-primary celda-destino" style="width: 20%; min-width: 120px;" data-raw-dest="${m.dep_destino_nom}">${m.dep_destino_nom}</td>
                    <td style="width: 25%; min-width: 220px;">${accionHTML}</td>
                </tr>
            `;
        }).join('');
    }

    function renderizarAlertasAreas(desaparecidos, divisiones) {
        if (UI.alerts.desaparecidos) {
            if (desaparecidos && desaparecidos.length > 0) {
                const lista = desaparecidos.map(d => `<li>${d.nom_dep}</li>`).join('');
                UI.alerts.desaparecidos.innerHTML = `<div class="alert alert-warning shadow-sm"><h6 class="fw-bold"><i class="fas fa-folder-minus me-2"></i>Áreas que desaparecen (Vaciadas en el archivo)</h6><ul class="mb-0">${lista}</ul></div>`;
                UI.alerts.desaparecidos.classList.remove('d-none');
            } else {
                UI.alerts.desaparecidos.classList.add('d-none');
            }
        }

        if (UI.alerts.divisiones) {
            if (divisiones && divisiones.length > 0) {
                const lista = divisiones.map(d => `<li><strong>${d.nom_dep}</strong> se dividió en: <span class="text-primary">${d.nombres_nuevos}</span></li>`).join('');
                UI.alerts.divisiones.innerHTML = `<div class="alert alert-info shadow-sm"><h6 class="fw-bold"><i class="fas fa-code-branch me-2"></i>Áreas que se dividieron</h6><ul class="mb-0">${lista}</ul></div>`;
                UI.alerts.divisiones.classList.remove('d-none');
            } else {
                UI.alerts.divisiones.classList.add('d-none');
            }
        }
    }


    // ==========================================
    // 6. CONTROLADOR DE MODAL Y SINCRONIZACIÓN
    // ==========================================

    function abrirModalBusqueda(hiddenInput, container) {
        const listaHTML = catalogoGlobal.map(dep => {
            const nomSeguro = dep.nom_dep ? dep.nom_dep.replace(/"/g, '&quot;') : '';
            return `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2 modal-item">
                    <div class="pe-2 text-truncate" title="${nomSeguro}">
                        <span class="badge bg-secondary">${dep.clave_dep || 'N/A'}</span> 
                        <span class="small fw-bold text-wrap">${nomSeguro}</span>
                    </div>
                    <button class="btn btn-sm btn-outline-primary btn-select" data-id="${dep.id_dep}" data-nom="${nomSeguro}">
                        Elegir
                    </button>
                </div>
            `;
        }).join('');

        Swal.fire({
            title: 'Catálogo de Departamentos',
            html: `
                <input type="text" id="buscador-modal" class="form-control mb-3 focus-guinda" placeholder="Filtrar por nombre o clave..." autocomplete="off">
                <div id="contenedor-lista" style="max-height: 50vh; overflow-y: auto; text-align: left;">${listaHTML}</div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                const input = document.getElementById('buscador-modal');
                const items = document.querySelectorAll('.modal-item');
                
                input.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    items.forEach(el => {
                        if (el.innerText.toLowerCase().includes(term)) {
                            el.classList.remove('d-none');
                            el.classList.add('d-flex');
                        } else {
                            el.classList.remove('d-flex');
                            el.classList.add('d-none');
                        }
                    });
                });

                document.getElementById('contenedor-lista').addEventListener('click', (e) => {
                    const btn = e.target.closest('.btn-select');
                    if(btn) {
                        const id = btn.dataset.id;
                        const nom = btn.dataset.nom;
                        
                        // Actualizar el estado visual del botón del dropdown
                        hiddenInput.value = `VINCULAR_${id}`;
                        const btnText = container.querySelector('.btn-text');
                        btnText.innerHTML = `<span class="text-primary fw-bold">🔗 ${nom}</span>`;
                        container.querySelector('.btn').classList.remove('border-warning');
                        container.querySelector('.btn').classList.add('border-secondary-subtle');
                        
                        sincronizarDestinosMovimientos();
                        Swal.close();
                    }
                });
            }
        });
    }

    function sincronizarDestinosMovimientos() {
        const inputsDepts = document.querySelectorAll('.res-dept');
        const celdasDestino = document.querySelectorAll('.celda-destino');

        if (!celdasDestino.length) return; 

        const mapaDestinos = {};

        inputsDepts.forEach(input => {
            const rawName = input.dataset.nombre;
            let finalName = rawName; 

            if (input.value && input.value.startsWith('VINCULAR_')) {
                const container = input.closest('.dropdown');
                const btnText = container.querySelector('.btn-text');
                if (btnText) {
                    finalName = btnText.innerText.replace(/🔗\s*/g, '').trim();
                }
            }
            mapaDestinos[rawName] = finalName;
        });

        celdasDestino.forEach(celda => {
            const rawDest = celda.dataset.rawDest;
            
            if (mapaDestinos[rawDest]) {
                celda.textContent = mapaDestinos[rawDest];
                
                if (mapaDestinos[rawDest] !== rawDest) {
                    celda.classList.add('text-success');
                    celda.classList.remove('text-primary');
                } else {
                    celda.classList.add('text-primary');
                    celda.classList.remove('text-success');
                }
            }
        });
    }

    // ==========================================
    // 7. UTILERÍAS
    // ==========================================

    function cambiarEstadoCarga(activo) {
        UI.buttons.start.disabled = activo;
        activo ? UI.indicators.progress.classList.remove('d-none') : UI.indicators.progress.classList.add('d-none');
    }

    async function parseJSON(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Respuesta cruda:", text);
            throw new Error("El servidor devolvió un error interno de sistema.");
        }
    }

});