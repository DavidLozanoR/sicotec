document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // INICIALIZADOR DE POPOVERS (AYUDA)
    // ==========================================
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('input-archivo-dhcp');
    const btnIniciar = document.getElementById('btn-iniciar-conciliacion');
    const seccionCarga = document.getElementById('seccion-carga');
    const seccionResultados = document.getElementById('seccion-resultados');
    const idSubActual = document.getElementById("id_sub_actual") ? document.getElementById("id_sub_actual").value : 11;

    let archivoSeleccionado = null;
    let resultadosAuditoria = { fantasmas: [], conflictos: [], huerfanas: [] };
    let tabActiva = 'fantasmas'; 
    let paginaActual = 1;
    let registrosPorPagina = 10;

    const inputBuscar = document.getElementById('inputBuscar');
    const selectorRegistros = document.getElementById('selectorRegistros');
    const infoPaginacion = document.getElementById('infoPaginacion');
    const ulPaginacion = document.getElementById('ulPaginacion');

    // ==========================================
    // 1. EVENTOS DE PAGINACIÓN Y BÚSQUEDA
    // ==========================================
    inputBuscar.addEventListener('input', () => {
        paginaActual = 1; 
        renderizarTabActiva();
    });

    selectorRegistros.addEventListener('change', (e) => {
        registrosPorPagina = e.target.value === 'todos' ? 'todos' : parseInt(e.target.value);
        paginaActual = 1;
        renderizarTabActiva();
    });

    document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', (e) => {
            const targetId = e.target.getAttribute('data-bs-target');
            if (targetId === '#tab-fantasmas') tabActiva = 'fantasmas';
            else if (targetId === '#tab-conflictos') tabActiva = 'conflictos';
            else if (targetId === '#tab-huerfanas') tabActiva = 'huerfanas';
            
            paginaActual = 1; 
            renderizarTabActiva();
        });
    });

    // ==========================================
    // 2. LÓGICA DE DRAG & DROP
    // ==========================================
    dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover') );
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault(); dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) manejarArchivo(e.dataTransfer.files[0]);
    });
    dropzone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', function() {
        if (this.files.length) manejarArchivo(this.files[0]);
    });

    function manejarArchivo(file) {
        const extensionesPermitidas = ['.csv', '.xlsx', '.xls', '.xml'];
        const ext = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
        
        if (!extensionesPermitidas.includes(ext)) {
            Swal.fire({ icon: 'warning', title: 'Archivo inválido', text: 'Sube un archivo de Excel, CSV o XML.', confirmButtonColor: '#9D2449' });
            return;
        }

        archivoSeleccionado = file;
        
        dropzone.innerHTML = `
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h5 class="text-success fw-bold">${file.name}</h5>
            <p class="text-muted small">Tamaño: ${(file.size / 1024).toFixed(2)} KB. Listo para cruzar.</p>
        `;
        dropzone.style.borderColor = '#198754';
        btnIniciar.disabled = false;
    }

    // ==========================================
    // 3. ENVÍO AL BACKEND VÍA FORMDATA
    // ==========================================
    btnIniciar.addEventListener('click', async () => {
        if (!archivoSeleccionado) return;

        const btnOriginalText = btnIniciar.innerHTML;
        btnIniciar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Cruzando Información...';
        btnIniciar.disabled = true;

        const formData = new FormData();
        formData.append('peticion', 'PROCESAR_DHCP');
        formData.append('id_sub', idSubActual);
        formData.append('archivo_dhcp', archivoSeleccionado);

        try {
            const response = await fetch('_controller/AuditoriaDhcpAsync.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                prepararResultados(data.data);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.msg, confirmButtonColor: '#9D2449' });
                btnIniciar.innerHTML = btnOriginalText;
                btnIniciar.disabled = false;
            }
        } catch (error) {
            console.error("Error:", error);
            Swal.fire({ icon: 'error', title: 'Error de Red', text: 'Fallo al comunicarse con el servidor.', confirmButtonColor: '#9D2449' });
            btnIniciar.innerHTML = btnOriginalText;
            btnIniciar.disabled = false;
        }
    });

    // ==========================================
    // 4. MOTOR DE PAGINACIÓN Y RENDERIZADO
    // ==========================================
    function prepararResultados(data) {
        seccionCarga.classList.add('d-none');
        seccionResultados.classList.remove('d-none');

        document.getElementById('stat-total-dhcp').textContent = data.resumen.total_dhcp;
        document.getElementById('stat-conflictos').textContent = data.conflictos.length;
        document.getElementById('stat-fantasmas').textContent = data.fantasmas.length;
        document.getElementById('stat-huerfanas').textContent = data.huerfanas.length;

        resultadosAuditoria.fantasmas = data.fantasmas;
        resultadosAuditoria.conflictos = data.conflictos;
        resultadosAuditoria.huerfanas = data.huerfanas;

        renderizarTabActiva();

        Swal.fire({ 
            icon: 'success', 
            title: 'Auditoría Completada', 
            confirmButtonColor: '#13322B',
            timer: 1500,
            timerProgressBar: true
        });    
    }

    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    function renderizarTabActiva() {
        const queryBusqueda = quitarAcentos(inputBuscar.value.toLowerCase().trim());
        let datosFiltrados = resultadosAuditoria[tabActiva];

        if (queryBusqueda !== '') {
            datosFiltrados = datosFiltrados.filter(item => {
                const textoObjeto = Object.values(item).join(' ').toLowerCase();
                return quitarAcentos(textoObjeto).includes(queryBusqueda);
            });
        }

        const totalItems = datosFiltrados.length;
        let totalPaginas = (registrosPorPagina === 'todos') ? 1 : Math.ceil(totalItems / registrosPorPagina);
        
        if (totalPaginas === 0) totalPaginas = 1;
        if (paginaActual > totalPaginas) paginaActual = totalPaginas;

        let start = 0;
        let end = totalItems;
        if (registrosPorPagina !== 'todos') {
            start = (paginaActual - 1) * registrosPorPagina;
            end = start + registrosPorPagina;
        }

        const datosPaginados = datosFiltrados.slice(start, end);

        const tbodyId = `tbody-${tabActiva}`;
        if (tabActiva === 'fantasmas') llenarTabla(tbodyId, datosPaginados, rowFantasmas);
        else if (tabActiva === 'conflictos') llenarTabla(tbodyId, datosPaginados, rowConflictos);
        else if (tabActiva === 'huerfanas') llenarTabla(tbodyId, datosPaginados, rowHuerfanas);

        dibujarControlesPaginacion(totalItems, start, end, totalPaginas);
    }

    const rowFantasmas = row => `
        <td class="ps-4"><span class="badge ${row.tipo.includes('Fantasma') ? 'bg-dark' : 'bg-secondary'}">${row.tipo}</span></td>
        <td class="fw-bold">${row.ip_dhcp}</td>
        <td class="font-monospace">${row.mac}</td>
        <td>${row.hostname_dhcp || '<span class="text-muted">Desconocido</span>'}</td>
        <td>${row.usuario_dhcp || '<span class="text-muted">N/A</span>'}</td>
        <td class="pe-4 text-muted small">${row.detalle}</td>
    `;

    const rowConflictos = row => `
        <td class="ps-4 fw-bold text-danger">${row.ip}</td>
        <td>
            <div class="small text-muted">DHCP: <strong class="text-dark">${row.hostname_dhcp}</strong></div>
            <div class="small text-muted">SIC: <strong class="text-dark">${row.hostname_sicosote}</strong></div>
        </td>
        <td class="font-monospace">
            <div class="small text-danger">DHCP: ${row.mac_dhcp}</div>
            <div class="small text-success">SIC: ${row.mac_sicosote}</div>
        </td>
        <td><span class="badge bg-light text-dark border">${row.departamento}</span></td>
        <td><span class="small fw-bold text-secondary">${row.responsable}</span></td>
        <td class="pe-4 text-danger small fw-bold"><i class="fas fa-exclamation-circle me-1"></i>${row.detalle}</td>
    `;

    const rowHuerfanas = row => `
        <td class="ps-4 fw-bold text-warning">${row.ip}</td>
        <td>${row.hostname_sicosote}</td>
        <td class="font-monospace">${row.mac_sicosote}</td>
        <td><span class="badge bg-light text-dark border">${row.inventario || 'S/N'}</span></td>
        <td><span class="badge bg-light text-dark border">${row.departamento}</span></td>
        <td><span class="small fw-bold text-secondary">${row.responsable}</span></td>
        <td class="pe-4 text-muted small">${row.detalle}</td>
    `;

    function llenarTabla(tbodyId, arrayData, rowHtmlFn) {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';
        
        if (arrayData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-5"><i class="fas fa-search text-secondary fa-3x mb-3 d-block opacity-50"></i> No se encontraron registros.</td></tr>`;
            return;
        }

        arrayData.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = rowHtmlFn(item);
            tbody.appendChild(tr);
        });
    }

    function dibujarControlesPaginacion(totalItems, start, end, totalPaginas) {
        ulPaginacion.innerHTML = '';

        if (totalItems === 0) {
            infoPaginacion.textContent = 'Mostrando 0 registros';
            return;
        }

        let currentEnd = end > totalItems ? totalItems : end;
        infoPaginacion.textContent = `Mostrando ${start + 1} a ${currentEnd} de ${totalItems} registros`;

        if (registrosPorPagina === 'todos' || totalPaginas <= 1) return;

        const liPrev = document.createElement('li');
        liPrev.className = `page-item ${paginaActual === 1 ? 'disabled' : ''}`;
        liPrev.innerHTML = `<a class="page-link text-guinda cursor-pointer" href="#">Anterior</a>`;
        liPrev.addEventListener('click', (e) => { e.preventDefault(); if (paginaActual > 1) { paginaActual--; renderizarTabActiva(); } });
        ulPaginacion.appendChild(liPrev);

        let inicioPag = Math.max(1, paginaActual - 2);
        let finPag = Math.min(totalPaginas, paginaActual + 2);

        for (let i = inicioPag; i <= finPag; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link ${i === paginaActual ? 'bg-guinda border-guinda text-white' : 'text-guinda cursor-pointer'}" href="#">${i}</a>`;
            li.addEventListener('click', (e) => { e.preventDefault(); paginaActual = i; renderizarTabActiva(); });
            ulPaginacion.appendChild(li);
        }

        const liNext = document.createElement('li');
        liNext.className = `page-item ${paginaActual === totalPaginas ? 'disabled' : ''}`;
        liNext.innerHTML = `<a class="page-link text-guinda cursor-pointer" href="#">Siguiente</a>`;
        liNext.addEventListener('click', (e) => { e.preventDefault(); if (paginaActual < totalPaginas) { paginaActual++; renderizarTabActiva(); } });
        ulPaginacion.appendChild(liNext);
    }
});