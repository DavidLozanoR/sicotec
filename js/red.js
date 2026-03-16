$(document).ready(function() {

    // 1. VARIABLES GLOBALES Y REFERENCIAS UI
    const idSub = $('#id_sub_red').val(); // Leemos el ID del submódulo por seguridad
    const urlAsync = '_controller/RedAsync.php';
    
    // Modales de Bootstrap
    const modalForm = new bootstrap.Modal(document.getElementById('modalFormSegmento'));
    const modalIps = new bootstrap.Modal(document.getElementById('modalIps'));
    
    const $form = $('#formSegmento');
    const $tituloModal = $('#tituloModalSegmento');

    // ====================================================================
    // 2. ABRIR MODAL PARA "NUEVO SEGMENTO"
    // ====================================================================
    $('#btnNuevoSegmento').on('click', function() {
        $form[0].reset(); // Limpiamos todos los campos
        $('#seg_id').val(''); // Limpiamos el ID oculto
        $tituloModal.html('<i class="fas fa-plus-circle me-2"></i>Nuevo Segmento');
        $form.removeClass('was-validated'); // Quitamos alertas rojas previas
        
        modalForm.show();
    });

    // ====================================================================
    // 3. ABRIR MODAL PARA "EDITAR SEGMENTO"
    // ====================================================================
    $('.btn-editar-seg').on('click', function(e) {
        e.preventDefault();
        
        // Recolectamos los datos incrustados en el botón (data-id, data-dep, etc.)
        let id = $(this).data('id');
        let dep = $(this).data('dep');
        let ini = $(this).data('ini');
        let lim = $(this).data('lim');

        // Llenamos el formulario
        $('#seg_id').val(id);
        $('#seg_dep').val(dep);
        $('#seg_ini').val(ini);
        $('#seg_lim').val(lim);

        $tituloModal.html('<i class="fas fa-edit me-2"></i>Editar Rango de Segmento');
        $form.removeClass('was-validated');
        
        modalForm.show();
    });

    // ====================================================================
    // 4. GUARDAR SEGMENTO (NUEVO O ACTUALIZADO)
    // ====================================================================
    $form.on('submit', function(e) {
        e.preventDefault();

        // Validación visual de Bootstrap
        if (!this.checkValidity()) {
            e.stopPropagation();
            $form.addClass('was-validated');
            return;
        }

        let formData = new FormData(this);
        let id_seg = $('#seg_id').val();
        
        // Decidimos la petición basándonos en si hay o no un ID cargado
        let peticion = (id_seg === '') ? 'INSERT_SEG' : 'UPDATE_SEG';
        formData.append('peticion', peticion);
        formData.append('id_sub', idSub);

        // Bloqueamos el botón para evitar dobles clics
        let $btnGuardar = $('#btnGuardarSeg');
        let btnTextoOriginal = $btnGuardar.html();
        $btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');

        $.ajax({
            url: urlAsync,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.result === 1) {
                    modalForm.hide();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.msg,
                        confirmButtonColor: '#9D2449'
                    }).then(() => {
                        location.reload(); // Recargamos para actualizar las tarjetas
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Atención', text: response.msg, confirmButtonColor: '#9D2449' });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de comunicación con el servidor.', confirmButtonColor: '#9D2449' });
            },
            complete: function() {
                $btnGuardar.prop('disabled', false).html(btnTextoOriginal);
            }
        });
    });

    // ====================================================================
    // 5. DESACTIVAR (BORRADO LÓGICO) SEGMENTO
    // ====================================================================
    $('.btn-eliminar-seg').on('click', function(e) {
        e.preventDefault();
        let id_seg = $(this).data('id');

        Swal.fire({
            title: '¿Desactivar este Segmento?',
            text: "Las IPs se ocultarán del sistema, pero no afectará a los equipos que ya las tengan asignadas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                
                $.ajax({
                    url: urlAsync,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        peticion: 'DELETE_SEG',
                        id_seg: id_seg,
                        id_sub: idSub
                    },
                    success: function(response) {
                        if (response.result === 1) {
                            Swal.fire({ icon: 'success', title: '¡Desactivado!', text: response.msg, confirmButtonColor: '#9D2449' })
                            .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Operación denegada', text: response.msg, confirmButtonColor: '#9D2449' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema con el servidor al intentar eliminar.', confirmButtonColor: '#9D2449' });
                    }
                });
            }
        });
    });

    // ====================================================================
    // 6. EXPLORADOR DE IPs (CARGAR TABLA Y MOSTRAR MODAL)
    // ====================================================================
    $('.btn-ver-ips').on('click', function(e) {
        e.preventDefault();
        let id_seg = $(this).data('id');
        let $tbody = $('#tbodyIps');

        // Limpiamos la tabla y ponemos un mensaje de carga
        $tbody.html('<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin text-guinda fa-2x"></i><p class="mt-2 text-muted">Cargando direcciones IP...</p></td></tr>');
        
        // Limpiamos el buscador
        $('#buscadorIps').val('');
        
        modalIps.show();

        $.ajax({
            url: urlAsync,
            type: 'POST',
            dataType: 'json',
            data: {
                peticion: 'GET_IPS',
                id_seg: id_seg,
                id_sub: idSub
            },
            success: function(response) {
                if (response.result === 1) {
                    let html = '';
                    let datos = response.data;

                    if (datos.length === 0) {
                        html = '<tr><td colspan="3" class="text-center text-muted py-3">No hay IPs disponibles para mostrar.</td></tr>';
                    } else {
                        // Dibujamos las filas dinámicamente
                        datos.forEach(function(ip) {
                            
                            // Badges dinámicos según el estatus
                            let badgeClass = 'bg-secondary';
                            let estado = ip.est.toLowerCase();
                            
                            if (estado === 'libre') badgeClass = 'bg-success';
                            if (estado === 'ocupado') badgeClass = 'bg-primary';
                            if (estado === 'reservado') badgeClass = 'bg-warning text-dark';
                            
                            // Info del dispositivo (si está ocupada)
                            let hostInfo = (ip.nom_host) 
                                ? `<span class="fw-bold text-dark"><i class="fas fa-desktop me-1 text-secondary"></i> ${ip.nom_host}</span><br><small class="text-muted">${ip.num_inv}</small>` 
                                : '<span class="text-muted fst-italic">Sin asignar</span>';

                            html += `
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold text-dark">${ip.ip}</td>
                                    <td><span class="badge ${badgeClass} shadow-sm">${ip.est}</span></td>
                                    <td>${hostInfo}</td>
                                </tr>
                            `;
                        });
                    }
                    $tbody.html(html);
                } else {
                    $tbody.html(`<tr><td colspan="3" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>${response.msg}</td></tr>`);
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="3" class="text-center text-danger py-3">Ocurrió un error al cargar las IPs.</td></tr>');
            }
        });
    });

    // ====================================================================
    // 7. BUSCADOR EN TIEMPO REAL (FILTRO DE TABLA EN EL MODAL)
    // ====================================================================
    $('#buscadorIps').on('keyup', function() {
        let valor = $(this).val().toLowerCase();
        
        // Filtramos cada fila de la tabla de IPs
        $('#tbodyIps tr').filter(function() {
            // toggle() muestra la fila si la función devuelve true, y la oculta si devuelve false
            $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1);
        });
    });

});