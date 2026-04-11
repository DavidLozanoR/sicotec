document.addEventListener('DOMContentLoaded', () => {
    // --- Elementos de la Interfaz ---
    const sectionUpload = document.getElementById('section_upload');
    const sectionResolucion = document.getElementById('section_resolucion');
    const btnIniciar = document.getElementById('btnIniciarAnalisis');
    const btnFinalizar = document.getElementById('btnFinalizarImportacion');
    const inputCSV = document.getElementById('inputArchivoCSV');
    const progress = document.getElementById('upload_progress');

    // --- Tablas y Badges ---
    const tbodyDepts = document.getElementById('tbodyConflictosDep');
    const tbodyBajas = document.getElementById('tbodyBajasDetectadas');
    const badgeDepts = document.getElementById('badge-conflictos-dep');
    const badgeBajas = document.getElementById('badge-bajas');

    // Variable global para almacenar el catálogo y usarlo en el Modal
    let catalogoDepartamentosGlobal = [];

    // --- LÓGICA PASO 1: SUBIR Y ANALIZAR ---
    btnIniciar.addEventListener('click', () => {
        const archivo = inputCSV.files[0];
        if (!archivo) {
            return Swal.fire('Error', 'Por favor seleccione un archivo .csv', 'error');
        }

        const formData = new FormData();
        formData.append('archivo_csv', archivo);
        formData.append('id_sub', ID_SUB_PERSONAL);
        formData.append('peticion', 'SUBIR_CSV');

        btnIniciar.disabled = true;
        progress.classList.remove('d-none');

        fetch('_controller/ImportacionAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                obtenerResultadosAnalisis();
            } else {
                Swal.fire('Error de Análisis', data.msg, 'error');
                btnIniciar.disabled = false;
                progress.classList.add('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'No se pudo comunicar con el servidor.', 'error');
            btnIniciar.disabled = false;
        });
    });

    // --- LÓGICA PASO 2: OBTENER CONFLICTOS DEL SERVIDOR ---
    function obtenerResultadosAnalisis() {
        fetch('_controller/ImportacionAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                peticion: 'GET_CONFLICTOS', 
                id_sub: ID_SUB_PERSONAL 
            })
        })
        .then(res => res.json())
        .then(data => {
            renderizarTablas(data);
            sectionUpload.classList.add('d-none');
            sectionResolucion.classList.remove('d-none');
        });
    }

    function renderizarTablas(data) {
        // Guardamos el catálogo para la lista responsiva
        catalogoDepartamentosGlobal = data.catalogo_depts || [];

        // 1. Departamentos
        tbodyDepts.innerHTML = '';
        badgeDepts.innerText = data.depts.length;
        
        data.depts.forEach(d => {
            // Opción base acortada para móviles
            let opcionesHTML = '<option value="CREAR" class="fw-bold text-success">✨ Crear nuevo</option>';
            
            // Sugerencias automáticas truncadas para que no rompan el <select>
            if (d.sugerencias && d.sugerencias.length > 0) {
                opcionesHTML += '<optgroup label="Sugerido por el sistema">';
                d.sugerencias.forEach(s => {
                    // Si el nombre es larguísimo, lo cortamos visualmente a 28 caracteres
                    let nomCorto = s.nom.length > 28 ? s.nom.substring(0, 28) + '...' : s.nom;
                    opcionesHTML += '<option value="VINCULAR_' + s.id_dep + '" selected>🔗 ' + nomCorto + '</option>';
                });
                opcionesHTML += '</optgroup>';
            }

            // Agregamos la opción manual acortada
            opcionesHTML += '<option value="VINCULAR_EXISTENTE" class="text-primary fw-bold">🔍 Buscar otro...</option>';

            const tr = document.createElement('tr');
            tr.className = 'conflict-row';
            // Controlamos los anchos de las celdas (25%, 35%, 40%) para evitar desbordes
            tr.innerHTML = `
                <td style="width: 25%;"><b class="text-primary">${d.clave_dep_raw || 'Sin Clave'}</b></td>
                <td style="width: 35%; max-width: 100px;" class="text-truncate">${d.nom_dep_raw}</td>
                <td style="width: 40%;">
                    <select class="form-select form-select-sm res-dept border-primary" style="text-overflow: ellipsis; white-space: nowrap;" data-clave="${d.clave_dep_raw}" data-nombre="${d.nom_dep_raw}">
                        ${opcionesHTML}
                    </select>
                </td>
            `;
            tbodyDepts.appendChild(tr);
        });

        // 2. Bajas
        tbodyBajas.innerHTML = '';
        badgeBajas.innerText = data.bajas.length;
        data.bajas.forEach(b => {
            const tieneEquipos = parseInt(b.total_dispositivos) > 0;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${b.num_cobro}</td>
                <td>${b.nombres} ${b.ape_pa} ${b.ape_ma}</td>
                <td>
                    <span class="badge ${tieneEquipos ? 'bg-danger' : 'bg-success'} badge-responsiva">
                        ${tieneEquipos ? '<i class="fas fa-laptop me-1"></i> ' + b.total_dispositivos + ' Equipos' : 'Sin equipos'}
                    </span>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input check-baja" type="checkbox" 
                               value="${b.num_cobro}" ${tieneEquipos ? 'disabled' : ''}>
                    </div>
                </td>
            `;
            tbodyBajas.appendChild(tr);
        });
    }

    // --- EVENTO PARA DETECTAR CUANDO EL USUARIO ELIGE "BUSCAR OTRO" ---
    tbodyDepts.addEventListener('change', (e) => {
        if (e.target.classList.contains('res-dept')) {
            if (e.target.value === 'VINCULAR_EXISTENTE') {
                abrirModalBuscadorDepartamentos(e.target);
            }
        }
    });

    // --- LÓGICA DEL MODAL RESPONSIVO (DISEÑO MÓVIL FLEXBOX & CLAMP) ---
    function abrirModalBuscadorDepartamentos(selectElement) {
        let htmlContenido = `
        <div class="mb-3">
            <input type="text" id="swal-search-dept" class="form-control form-control-lg border-primary shadow-sm" placeholder="🔍 Buscar nombre o clave..." autocomplete="off">
        </div>
        <div id="lista-swal-depts" class="text-start" style="max-height: 60vh; overflow-y: auto; overflow-x: hidden; padding-right: 5px;">
        `;

        catalogoDepartamentosGlobal.forEach(dep => {
            htmlContenido += `
                <div class="d-flex justify-content-between align-items-center border-bottom py-3 item-dept">
                    <div class="pe-2 flex-grow-1" style="min-width: 0;">
                        <span class="badge bg-secondary mb-1">${dep.clave_dep || 'Sin Clave'}</span><br>
                        <span class="fw-bold text-dark d-block text-wrap lh-sm" style="font-size: clamp(0.8rem, 3.5vw, 0.95rem); word-break: break-word;">
                            ${dep.nom_dep}
                        </span>
                    </div>
                    <div class="flex-shrink-0 ms-2">
                        <button class="btn btn-primary btn-sm btn-seleccionar-dept" 
                                data-id="${dep.id_dep}" 
                                data-nombre="${dep.nom_dep}">
                            <i class="fas fa-check"></i> Elegir
                        </button>
                    </div>
                </div>
            `;
        });

        htmlContenido += `</div>`;

        Swal.fire({
            title: '<h5 class="fw-bold mb-0">Seleccionar Departamento</h5>',
            html: htmlContenido,
            width: '100%',
            padding: '1em',
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                const inputSearch = document.getElementById('swal-search-dept');
                const contenedorLista = document.getElementById('lista-swal-depts');
                const items = contenedorLista.querySelectorAll('.item-dept');

                // --- SOLUCIÓN DE BÚSQUEDA ---
                inputSearch.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    items.forEach(item => {
                        const text = item.innerText.toLowerCase();
                        if (text.includes(term)) {
                            // Usamos las clases de Bootstrap para sobreescribir el display
                            item.classList.remove('d-none');
                            item.classList.add('d-flex');
                        } else {
                            // Ocultamos el elemento eliminando el d-flex
                            item.classList.remove('d-flex');
                            item.classList.add('d-none');
                        }
                    });
                });

                // Clic en elegir
                document.querySelectorAll('.btn-seleccionar-dept').forEach(btn => {
                    btn.addEventListener('click', (ev) => {
                        const btnTarget = ev.currentTarget;
                        const selectedId = btnTarget.getAttribute('data-id');
                        const selectedNombre = btnTarget.getAttribute('data-nombre');

                        // Creamos la opción en el select si no existía
                        let selectorString = 'option[value="VINCULAR_' + selectedId + '"]';
                        let existingOpt = selectElement.querySelector(selectorString);
                        
                        if (!existingOpt) {
                            existingOpt = document.createElement('option');
                            existingOpt.value = 'VINCULAR_' + selectedId;
                            // Acortamos el texto para que el select no se expanda al regresar
                            let nomCortoSelect = selectedNombre.length > 25 ? selectedNombre.substring(0, 25) + '...' : selectedNombre;
                            existingOpt.text = '🔗 Vinculado a: ' + nomCortoSelect;
                            selectElement.appendChild(existingOpt);
                        }
                        
                        selectElement.value = 'VINCULAR_' + selectedId;
                        Swal.close(); 
                    });
                });
            }
        }).then(() => {
            // Manejo de cancelación si el usuario toca fuera del modal
            if (!selectElement.value.startsWith('VINCULAR_') || selectElement.value === 'VINCULAR_EXISTENTE') {
                selectElement.value = 'CREAR';
            }
        });
    }

    // --- LÓGICA FINAL: SINCRONIZACIÓN ---
    btnFinalizar.addEventListener('click', () => {
        const resolucionesDepts = [];
        document.querySelectorAll('.res-dept').forEach(sel => {
            resolucionesDepts.push({
                clave: sel.dataset.clave,
                nombre: sel.dataset.nombre,
                accion: sel.value 
            });
        });

        const numCobrosBaja = [];
        document.querySelectorAll('.check-baja:checked').forEach(chk => {
            numCobrosBaja.push(chk.value);
        });

        Swal.fire({
            title: '¿Confirmar Sincronización?',
            text: "Se actualizará la base de datos de personal y se procesarán las bajas seleccionadas.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Sí, ejecutar'
        }).then((result) => {
            if (result.isConfirmed) {
                btnFinalizar.disabled = true;
                btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                fetch('_controller/ImportacionAsync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        peticion: 'EJECUTAR_FINAL',
                        id_sub: ID_SUB_PERSONAL,
                        resolucionesDepts,
                        numCobrosBaja
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('¡Éxito!', data.msg, 'success').then(() => {
                            window.location.href = 'personal';
                        });
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                        btnFinalizar.disabled = false;
                        btnFinalizar.innerText = 'Ejecutar Sincronización Final';
                    }
                });
            }
        });
    });
});