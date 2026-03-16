$(document).ready(function() {
    const idSub = $('#id_sub_serv').val();
    const urlAsync = '_controller/ServiciosAsync.php';
    
    const modalForm = new bootstrap.Modal(document.getElementById('modalFormServicio'));
    const modalVer = new bootstrap.Modal(document.getElementById('modalVersiones'));
    const $form = $('#formServicio');

    // ==========================================
    // 1. GESTIÓN DEL CATÁLOGO DE SERVICIOS
    // ==========================================
    $('#btnNuevoServicio').on('click', function() {
        $form[0].reset();
        $('#serv_id').val('');
        $('#tituloModalServ').html('<i class="fas fa-plus-circle me-2 text-guinda"></i>Nuevo Servicio');
        $('#bloque_version_inicial').show();
        $('#serv_ver_ini').prop('required', true);
        $form.removeClass('was-validated');
        modalForm.show();
    });

    $('.btn-editar-serv').on('click', function(e) {
        e.preventDefault();
        $('#serv_id').val($(this).data('id'));
        $('#serv_tipo').val($(this).data('tipo'));
        $('#serv_nom').val($(this).data('nom'));
        $('#serv_especs').val($(this).data('especs'));
        
        $('#tituloModalServ').html('<i class="fas fa-edit me-2 text-secondary"></i>Editar Servicio');
        $('#bloque_version_inicial').hide(); // Ocultamos porque las versiones se editan en su propio modal
        $('#serv_ver_ini').prop('required', false);
        $form.removeClass('was-validated');
        modalForm.show();
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        if (!this.checkValidity()) {
            e.stopPropagation();
            $form.addClass('was-validated');
            return;
        }

        let formData = new FormData(this);
        formData.append('peticion', ($('#serv_id').val() === '') ? 'INSERT_SERV' : 'UPDATE_SERV');
        formData.append('id_sub', idSub);

        $.ajax({
            url: urlAsync, type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(r) {
                if (r.result === 1) {
                    Swal.fire({icon: 'success', title: 'Éxito', text: r.msg, confirmButtonColor: '#9D2449'}).then(() => location.reload());
                } else {
                    Swal.fire({icon: 'error', title: 'Error', text: r.msg, confirmButtonColor: '#9D2449'});
                }
            }
        });
    });

    $('.btn-eliminar-serv').on('click', function(e) {
        e.preventDefault();
        let id_serv = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar este servicio?',
            text: "Se borrará junto con todo su historial de versiones.",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#9D2449', confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(urlAsync, {peticion: 'DELETE_SERV', id_serv: id_serv, id_sub: idSub}, function(r) {
                    if (r.result === 1) Swal.fire({icon: 'success', title: 'Eliminado', text: r.msg, confirmButtonColor: '#9D2449'}).then(() => location.reload());
                    else Swal.fire({icon: 'error', title: 'Atención', text: r.msg, confirmButtonColor: '#9D2449'});
                }, 'json');
            }
        });
    });

    // ==========================================
    // 2. GESTIÓN DEL HISTORIAL DE VERSIONES
    // ==========================================
    $('.btn-versiones').on('click', function(e) {
        e.preventDefault();
        let id_serv = $(this).data('id');
        let nom_serv = $(this).data('nom');
        
        $('#ver_id_serv').val(id_serv);
        $('#tituloModalVer').html(`<i class="fas fa-code-branch me-2"></i> Versiones de: ${nom_serv}`);
        $('#formNuevaVersion')[0].reset();
        
        cargarVersiones(id_serv);
        modalVer.show();
    });

    function cargarVersiones(id_serv) {
        let $tbody = $('#tbodyVersiones');
        $tbody.html('<tr><td colspan="3" class="text-center py-3"><i class="fas fa-spinner fa-spin text-guinda"></i> Cargando...</td></tr>');
        
        $.post(urlAsync, {peticion: 'GET_VERSIONS', id_serv: id_serv, id_sub: idSub}, function(r) {
            if (r.result === 1) {
                let html = '';
                r.data.forEach(function(v) {
                    let esActual = (v.es_actual == 1);
                    let badge = esActual ? '<span class="badge bg-success shadow-sm"><i class="fas fa-check-circle me-1"></i>Versión Actual</span>' : '<span class="badge bg-secondary opacity-50">Histórica</span>';
                    
                    let btnAction = esActual ? 
                        '<button class="btn btn-sm btn-light text-muted" disabled>Activa</button>' : 
                        `<button class="btn btn-sm btn-outline-primary btn-set-actual" data-id="${v.id_ver}">Hacer Actual</button>`;

                    html += `<tr>
                        <td class="ps-4 fw-bold font-monospace">${v.ver}</td>
                        <td>${badge}</td>
                        <td class="text-end pe-4">${btnAction}</td>
                    </tr>`;
                });
                $tbody.html(html || '<tr><td colspan="3" class="text-center text-muted">No hay versiones registradas.</td></tr>');
            }
        }, 'json');
    }

    // Agregar nueva versión desde el modal
    $('#formNuevaVersion').on('submit', function(e) {
        e.preventDefault();
        let id_serv = $('#ver_id_serv').val();
        let formData = new FormData(this);
        formData.append('peticion', 'ADD_VERSION');
        formData.append('id_sub', idSub);

        $.ajax({
            url: urlAsync, type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            success: function(r) {
                if (r.result === 1) {
                    $('#nueva_version').val('');
                    cargarVersiones(id_serv); // Recargamos la tabla interna
                } else {
                    Swal.fire({icon: 'error', text: r.msg, confirmButtonColor: '#9D2449'});
                }
            }
        });
    });

    // Marcar una versión vieja como la "Actual"
    $(document).on('click', '.btn-set-actual', function() {
        let id_ver = $(this).data('id');
        let id_serv = $('#ver_id_serv').val();

        $.post(urlAsync, {peticion: 'SET_CURRENT_VERSION', id_serv: id_serv, id_ver: id_ver, id_sub: idSub}, function(r) {
            if (r.result === 1) {
                cargarVersiones(id_serv); // Refrescamos para mover el badge verde
            } else {
                Swal.fire({icon: 'error', text: r.msg, confirmButtonColor: '#9D2449'});
            }
        }, 'json');
    });

    // Recargar la página al cerrar el modal de versiones (para que la tarjeta actualice su etiqueta "Versión Desplegada")
    document.getElementById('modalVersiones').addEventListener('hidden.bs.modal', function () {
        location.reload();
    });
});