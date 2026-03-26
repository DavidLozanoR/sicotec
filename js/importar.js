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
                // Si la subida a "Aduana" fue exitosa, buscamos los conflictos
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
            // Transición visual de secciones
            sectionUpload.classList.add('d-none');
            sectionResolucion.classList.remove('d-none');
        });
    }

    function renderizarTablas(data) {
        // 1. Departamentos
        tbodyDepts.innerHTML = '';
        badgeDepts.innerText = data.depts.length;
        data.depts.forEach(d => {
            const tr = document.createElement('tr');
            tr.className = 'conflict-row';
            tr.innerHTML = `
                <td><b class="text-primary">${d.clave_dep_raw}</b></td>
                <td>${d.nom_dep_raw}</td>
                <td>
                    <select class="form-select form-select-sm res-dept" data-clave="${d.clave_dep_raw}" data-nombre="${d.nom_dep_raw}">
                        <option value="CREAR">Crear como nuevo departamento</option>
                        ${d.sugerencias.map(s => `<option value="VINCULAR_${s.id_dep}">Vincular a: ${s.nom}</option>`).join('')}
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
                        ${tieneEquipos ? `<i class="fas fa-laptop me-1"></i> ${b.total_dispositivos} Equipos` : 'Sin equipos'}
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