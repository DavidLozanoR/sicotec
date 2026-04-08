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

    const formBitacora = document.getElementById('form-bitacora');
    const btnGenerarBitacora = document.getElementById('btn-generar-bitacora');
    const tbodyBitacora = document.getElementById('tbody-bitacora');
    const bitacoraTab = document.getElementById('bitacora-tab');

    const btnExportarExcel = document.getElementById('btn-exportar-excel');

    // =======================================================
    // REGLAS DE NEGOCIO Y COMPORTAMIENTO DE FILTROS
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