document.addEventListener('DOMContentLoaded', () => {
    
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('input-archivo-dhcp');
    const btnIniciar = document.getElementById('btn-iniciar-conciliacion');
    const seccionCarga = document.getElementById('seccion-carga');
    const seccionResultados = document.getElementById('seccion-resultados');
    const idSubActual = document.getElementById("id_sub_actual") ? document.getElementById("id_sub_actual").value : 11;

    let archivoSeleccionado = null;

    // ==========================================
    // 1. LÓGICA DE DRAG & DROP
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
    // 2. ENVÍO AL BACKEND VÍA FORMDATA
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
                body: formData // Fetch gestiona el Content-Type multipart
            });

            const data = await response.json();

            if (data.status === 'success') {
                mostrarResultados(data.data);
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
    // 3. RENDERIZADO DE RESULTADOS
    // ==========================================
    function mostrarResultados(data) {
        seccionCarga.classList.add('d-none');
        seccionResultados.classList.remove('d-none');

        document.getElementById('stat-total-dhcp').textContent = data.resumen.total_dhcp;
        document.getElementById('stat-conflictos').textContent = data.conflictos.length;
        document.getElementById('stat-fantasmas').textContent = data.fantasmas.length;
        document.getElementById('stat-huerfanas').textContent = data.huerfanas.length;

        llenarTabla('tbody-fantasmas', data.fantasmas, row => `
            <td class="ps-4"><span class="badge ${row.tipo.includes('Fantasma') ? 'bg-dark' : 'bg-secondary'}">${row.tipo}</span></td>
            <td class="fw-bold">${row.ip_dhcp}</td>
            <td class="font-monospace">${row.mac}</td>
            <td>${row.hostname_dhcp || '<span class="text-muted">Desconocido</span>'}</td>
            <td>${row.usuario_dhcp || '<span class="text-muted">N/A</span>'}</td>
            <td class="pe-4 text-muted small">${row.detalle}</td>
        `);

        llenarTabla('tbody-conflictos', data.conflictos, row => `
            <td class="ps-4 fw-bold text-danger">${row.ip}</td>
            <td>
                <div class="small text-muted">DHCP: <strong class="text-dark">${row.hostname_dhcp}</strong></div>
                <div class="small text-muted">SICOSOTE: <strong class="text-dark">${row.hostname_sicosote}</strong></div>
            </td>
            <td class="font-monospace">
                <div class="small text-danger">DHCP: ${row.mac_dhcp}</div>
                <div class="small text-success">SICOSOTE: ${row.mac_sicosote}</div>
            </td>
            <td class="pe-4 text-danger small fw-bold"><i class="fas fa-exclamation-circle me-1"></i>${row.detalle}</td>
        `);

        llenarTabla('tbody-huerfanas', data.huerfanas, row => `
            <td class="ps-4 fw-bold text-warning">${row.ip}</td>
            <td>${row.hostname_sicosote}</td>
            <td class="font-monospace">${row.mac_sicosote}</td>
            <td><span class="badge bg-light text-dark border">${row.inventario || 'S/N'}</span></td>
            <td class="pe-4 text-muted small">${row.detalle}</td>
        `);

        Swal.fire({ icon: 'success', title: 'Auditoría Completada', confirmButtonColor: '#13322B' });
    }

    function llenarTabla(tbodyId, arrayData, rowHtmlFn) {
        const tbody = document.getElementById(tbodyId);
        tbody.innerHTML = '';
        if (arrayData.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-5"><i class="fas fa-check-circle text-success fa-3x mb-3 d-block opacity-75"></i> Todo en orden.</td></tr>`;
            return;
        }
        arrayData.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = rowHtmlFn(item);
            tbody.appendChild(tr);
        });
    }
});