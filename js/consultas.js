document.addEventListener('DOMContentLoaded', () => {
    
    const formConsultas = document.getElementById('form-consultas');
    const btnGenerar = document.getElementById('btn-generar-consulta');
    const tbodyResultados = document.getElementById('tbody-resultados');
    const btnExportarExcel = document.getElementById('btn-exportar-excel');
    
    const selectServicio = document.getElementById('filtro-servicio');
    const selectEstadoSoft = document.getElementById('filtro-estado-soft');
    const thSoftware = document.querySelector('.th-software');

    // Habilitar/Deshabilitar filtro de estado de software si selecciona un software
    selectServicio.addEventListener('change', function() {
        if (this.value !== "") {
            selectEstadoSoft.disabled = false;
        } else {
            selectEstadoSoft.disabled = true;
            selectEstadoSoft.value = "todos";
        }
    });

    // ==========================================
    // RESTRICCIÓN DE EXCEL (SWEETALERT)
    // ==========================================
    btnExportarExcel.addEventListener('click', () => {
        Swal.fire({
            icon: 'info',
            title: 'Reporte en Excel',
            text: 'La función para exportar reportes estratégicos a Excel estará disponible en la próxima actualización del sistema.',
            confirmButtonColor: '#9D2449',
            confirmButtonText: 'Entendido'
        });
    });

    // ==========================================
    // EJECUCIÓN DE LA AUDITORÍA
    // ==========================================
    formConsultas.addEventListener('submit', function(e) {
        e.preventDefault();

        const originalText = btnGenerar.innerHTML;
        btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        btnGenerar.disabled = true;

        const formData = new FormData(this);
        formData.append('peticion', 'EJECUTAR_CONSULTA');

        // Mostrar/Ocultar columna de software si se pidió auditar uno
        const auditarSoftware = formData.get('id_serv') !== "";
        if (auditarSoftware) {
            thSoftware.classList.remove('d-none');
        } else {
            thSoftware.classList.add('d-none');
        }

        fetch('_controller/ConsultasAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            tbodyResultados.innerHTML = '';

            if (data.result === 1) {
                const registros = data.data;

                if (registros.length === 0) {
                    tbodyResultados.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-search-minus fa-2x mb-3 opacity-50"></i>
                                <h6 class="fw-bold">Sin resultados</h6>
                                <p class="mb-0 small">Ningún equipo cumple con la combinación de filtros seleccionada.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                registros.forEach(row => {
                    let colSoftware = '';
                    if (auditarSoftware) {
                        const esObsoleto = row.es_obsoleta == 1;
                        const badge = esObsoleto ? 
                            '<span class="badge bg-danger rounded-pill shadow-sm"><i class="fas fa-exclamation-triangle me-1"></i>Obsoleto</span>' : 
                            '<span class="badge bg-success rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i>Seguro</span>';
                        
                        colSoftware = `
                            <td>
                                <strong class="text-dark d-block">${row.nombre_software}</strong>
                                <small class="text-muted d-block mb-1">Ver: ${row.version_instalada}</small>
                                ${badge}
                            </td>
                        `;
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-4 py-3">
                            <h6 class="fw-bold text-dark mb-0">${row.nom_host}</h6>
                            <small class="text-muted d-block"><i class="fas fa-desktop me-1"></i>${row.marca} / ${row.modelo}</small>
                            <small class="text-muted"><strong class="text-dark">Inv:</strong> ${row.num_inv || 'N/A'}</small>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-light text-dark border border-secondary-subtle d-inline-block mb-1">
                                <i class="fas fa-map-marker-alt text-guinda me-1"></i> ${row.departamento || 'Sin Asignar'}
                            </span><br>
                            <span class="font-monospace small text-primary"><i class="fas fa-network-wired me-1"></i> ${row.ip || 'Sin Conexión'}</span>
                        </td>
                        <td class="py-3">
                            ${row.responsable ? `<i class="fas fa-user-tie text-success me-1"></i> ${row.responsable}` : `<span class="text-danger small fw-bold"><i class="fas fa-box me-1"></i> En Almacén / Sin PO</span>`}
                        </td>
                        ${colSoftware}
                    `;
                    tbodyResultados.appendChild(tr);
                });

            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error de Red', 'No se pudo generar la consulta.', 'error');
        })
        .finally(() => {
            btnGenerar.innerHTML = originalText;
            btnGenerar.disabled = false;
        });
    });
});