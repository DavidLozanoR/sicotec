/**
 * Módulo de Importación Masiva (SICOSTE)
 * Arquitectura: Event-Driven & Modular DOM Manipulation
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
            bajas: document.getElementById('tbodyBajasDetectadas')
        }
    };

    let catalogoGlobal = []; // Estado local para búsqueda en modal

    // ==========================================
    // 2. CONTROLADORES DE EVENTOS PRINCIPALES
    // ==========================================

    UI.buttons.start.addEventListener('click', procesarArchivo);
    UI.buttons.finalize.addEventListener('click', ejecutarSincronizacion);
    
    // Delegación de eventos para el select dinámico de departamentos
    UI.tables.depts.addEventListener('change', (e) => {
        if (e.target.classList.contains('res-dept') && e.target.value === 'VINCULAR_EXISTENTE') {
            abrirModalBusqueda(e.target);
        }
    });

    // ==========================================
    // 3. LÓGICA DE NEGOCIO: PASO 1 (SUBIDA)
    // ==========================================

    async function procesarArchivo() {
        const archivo = UI.inputs.file.files[0];
        
        if (!archivo) {
            return Swal.fire('Archivo requerido', 'Seleccione un documento para analizar.', 'warning');
        }

        // Validación estricta de extensiones permitidas
        const ext = archivo.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'csv', 'xml'].includes(ext)) {
            return Swal.fire('Formato restringido', 'Asegure que su archivo sea del tipo .xlsx, .csv o .xml', 'error');
        }

        const formData = new FormData();
        formData.append('archivo_masivo', archivo);
        formData.append('id_sub', ID_SUB_PERSONAL); // Asume inyección global desde vista
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
            
            // Transición de Vistas y Renderizado
            UI.views.upload.classList.add('d-none');
            UI.views.resolution.classList.remove('d-none');
            
            renderizarDiagnostico(data.diagnostico);
            renderizarTablaDepartamentos(data.depts);
            renderizarTablaBajas(data.bajas);

            if (data.alerta_csv) {
                Swal.fire('Codificación Detectada', 'El sistema normalizó caracteres especiales. Revise los departamentos generados.', 'info');
            }

        } catch (error) {
            Swal.fire('Error al procesar conflictos', error.message, 'error');
        }
    }

    async function ejecutarSincronizacion() {
        // Recolección de resoluciones de DOM
        const resolucionesDepts = Array.from(document.querySelectorAll('.res-dept')).map(sel => ({
            clave: sel.dataset.clave,
            nombre: sel.dataset.nombre,
            accion: sel.value 
        }));

        const numCobrosBaja = Array.from(document.querySelectorAll('.check-baja:checked')).map(chk => chk.value);

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

        const payload = { peticion: 'EJECUTAR_FINAL', id_sub: ID_SUB_PERSONAL, resolucionesDepts, numCobrosBaja };
        
        asyncProcessForm(payload, '_controller/ImportacionAsync.php', "Ejecutando...", "Sincronizando información...", "¡Completado!", function(res) {
            if (res.status === 'success' || res.result === 1) {
                window.location.href = 'personal';
            } else {
                UI.buttons.finalize.disabled = false;
                UI.buttons.finalize.innerText = 'Ejecutar Sincronización Final';
            }
        });
    }

    // ==========================================
    // 5. MOTORES DE RENDERIZADO (UI)
    // ==========================================

    function renderizarDiagnostico(diag) {
        const oldPanel = document.getElementById('panel-diagnostico');
        if (oldPanel) oldPanel.remove();
        if (!diag) return;

        const htmlMuestra = (diag.muestra && diag.muestra.length > 0) 
            ? diag.muestra.map(d => `<li>[${d.clave_dep_raw || 'S/C'}] ${d.nom_dep_raw || 'Vacío'}</li>`).join('')
            : '<li><i class="text-muted">(Sin datos)</i></li>';

        const isWarning = diag.vacios > 0;
        const panelDiv = document.createElement('div');
        panelDiv.id = 'panel-diagnostico';
        panelDiv.className = `alert ${isWarning ? 'alert-warning' : 'alert-info'} mb-4 shadow-sm`;
        
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
        `;
        UI.views.resolution.prepend(panelDiv);
    }

    function renderizarTablaDepartamentos(depts) {
        UI.indicators.badgeDepts.innerText = depts.length;
        UI.tables.depts.innerHTML = depts.map(d => {
            
            // Construcción modular de <options>
            let opciones = '<option value="CREAR" class="text-success">✨ Crear como nuevo</option>';
            if (d.sugerencias?.length) {
                opciones += '<optgroup label="Sugerencias">';
                d.sugerencias.forEach(s => {
                    const texto = s.nom.length > 28 ? s.nom.substring(0,28)+'...' : s.nom;
                    opciones += `<option value="VINCULAR_${s.id_dep}" selected>🔗 ${texto}</option>`;
                });
                opciones += '</optgroup>';
            }
            opciones += '<option value="VINCULAR_EXISTENTE" class="text-primary fw-bold">🔍 Buscar catálogo...</option>';

            return `
                <tr>
                    <td width="25%"><b>${d.clave_dep_raw || 'N/A'}</b></td>
                    <td width="35%" class="text-truncate" style="max-width:150px;">${d.nom_dep_raw}</td>
                    <td width="40%">
                        <select class="form-select form-select-sm res-dept" data-clave="${d.clave_dep_raw}" data-nombre="${d.nom_dep_raw}">
                            ${opciones}
                        </select>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderizarTablaBajas(bajas) {
        UI.indicators.badgeBajas.innerText = bajas.length;
        UI.tables.bajas.innerHTML = bajas.map(b => {
            const equipos = parseInt(b.total_dispositivos);
            const bloqueado = equipos > 0 ? 'disabled' : '';
            const badge = equipos > 0 ? `<span class="badge bg-danger">${equipos} Equipos</span>` : `<span class="badge bg-success">Libre</span>`;
            
            return `
                <tr>
                    <td>${b.num_cobro}</td>
                    <td>${b.nombres} ${b.ape_pa} ${b.ape_ma}</td>
                    <td>${badge}</td>
                    <td class="text-center">
                        <input class="form-check-input check-baja" type="checkbox" value="${b.num_cobro}" ${bloqueado}>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // ==========================================
    // 6. CONTROLADOR DE MODAL (BÚSQUEDA)
    // ==========================================

    function abrirModalBusqueda(selectTrigger) {
        const listaHTML = catalogoGlobal.map(dep => `
            <div class="d-flex justify-content-between align-items-center border-bottom py-2 modal-item">
                <div class="pe-2 text-truncate">
                    <span class="badge bg-secondary">${dep.clave_dep || 'N/A'}</span> 
                    <span class="small fw-bold">${dep.nom_dep}</span>
                </div>
                <button class="btn btn-sm btn-outline-primary btn-select" data-id="${dep.id_dep}" data-nom="${dep.nom_dep}">
                    Elegir
                </button>
            </div>
        `).join('');

        Swal.fire({
            title: 'Catálogo de Departamentos',
            html: `
                <input type="text" id="buscador-modal" class="form-control mb-3" placeholder="Filtrar..." autocomplete="off">
                <div id="contenedor-lista" style="max-height: 50vh; overflow-y: auto; text-align: left;">${listaHTML}</div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                const input = document.getElementById('buscador-modal');
                const items = document.querySelectorAll('.modal-item');
                
                // Lógica de filtrado en tiempo real
                input.addEventListener('input', (e) => {
                    const term = e.target.value.toLowerCase();
                    items.forEach(el => el.style.display = el.innerText.toLowerCase().includes(term) ? 'flex' : 'none');
                });

                // Selección de elemento
                document.getElementById('contenedor-lista').addEventListener('click', (e) => {
                    if(e.target.closest('.btn-select')) {
                        const btn = e.target.closest('.btn-select');
                        const id = btn.dataset.id;
                        const nom = btn.dataset.nom.substring(0, 25) + '...';
                        
                        // Agregar opción al select si no existe y seleccionarla
                        if (!selectTrigger.querySelector(`option[value="VINCULAR_${id}"]`)) {
                            const newOption = new Option(`🔗 Vinculado: ${nom}`, `VINCULAR_${id}`);
                            selectTrigger.add(newOption);
                        }
                        selectTrigger.value = `VINCULAR_${id}`;
                        Swal.close();
                    }
                });
            }
        }).then(() => {
            // Revertir a "CREAR" si el usuario cerró el modal sin elegir nada
            if (selectTrigger.value === 'VINCULAR_EXISTENTE') selectTrigger.value = 'CREAR';
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