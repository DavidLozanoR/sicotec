$(document).ready(function() {

    const $formPO = $('#formPersonalOperativo');
    const $btnGuardar = $('#btnGuardarPersonal');

    // 1. VALIDACIONES REACTIVAS (EN TIEMPO REAL)
    // ====================================================================

    // A) Nombres y Apellidos
    $('input[name="nombres"], input[name="ape_pa"], input[name="ape_ma"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, '');
        valor = valor.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
        valor = valor.replace(/\s{2,}/g, ' ');
        $(this).val(valor);
    });

    // B) Número de Cobro y Extensión
    $('input[name="num_cobro"], input[name="ext"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/[^0-9]/g, '');
        $(this).val(valor);
    });

    // C) Correo Institucional y Cuenta de Dominio
    $('input[name="correo_prefijo"], input[name="cta_dom"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, '');
        valor = valor.replace(/[^a-zA-Z0-9._-]/g, '');
        $(this).val(valor);
    });

    // D) Puesto Operativo
    $('input[name="puesto"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, '');
        valor = valor.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        valor = valor.replace(/\s{2,}/g, ' ');
        $(this).val(valor);
    });

    // E) Dirección Completa
    $('textarea[name="dir"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, '');
        valor = valor.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s,.#-]/g, '');
        valor = valor.replace(/\s{2,}/g, ' ');
        $(this).val(valor);
    });

    // F) Feedback visual 
    $formPO.find('input[required], select[required], textarea[required]').on('blur change input', function() {
        validarCampoUI($(this));
    });

    function validarCampoUI($campo) {
        if ($campo.prop('required') && $campo.val().trim() === '') {
            $campo.removeClass('is-valid').addClass('is-invalid');
        } else {
            if ($campo.val().trim() !== '') {
                $campo.removeClass('is-invalid').addClass('is-valid');
            } else {
                $campo.removeClass('is-invalid is-valid');
            }
        }
    }

    // ====================================================================
    // 2. LÓGICA DE NAVEGACIÓN POR PESTAÑAS (WIZARD)
    // ====================================================================
    $('.btn-siguiente').on('click', function() {
        let $pestanaActual = $(this).closest('.tab-pane');
        let nextTabId = $(this).data('next');
        let isValid = true;

        // Forzar validación solo en la pestaña actual
        $pestanaActual.find('input[required], select[required], textarea[required]').each(function() {
            validarCampoUI($(this));
            if ($(this).hasClass('is-invalid') || $(this).val().trim() === '') {
                isValid = false;
            }
        });

        if (isValid) {
            let nextTab = new bootstrap.Tab(document.getElementById(nextTabId));
            nextTab.show();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Información Incompleta',
                text: 'Por favor, llene correctamente todos los campos obligatorios de esta sección antes de avanzar.',
                confirmButtonColor: '#9D2449'
            });
        }
    });

    // ====================================================================
    // 3. LÓGICA DE ENVÍO DEL FORMULARIO (SUBMIT)
    // ====================================================================
    $formPO.on('submit', function(e) {
        e.preventDefault();

        let formularioValido = true;
        
        $formPO.find('input[required], select[required], textarea[required]').each(function() {
            validarCampoUI($(this));
            if ($(this).val().trim() === '') {
                formularioValido = false;
            }
        });

        if (!this.checkValidity() || !formularioValido) {
            e.stopPropagation();
            $formPO.addClass('was-validated'); 
            
            // Buscar la primera pestaña que contenga un error y redirigir al usuario ahí
            let primerError = $formPO.find('.is-invalid').first();
            if (primerError.length) {
                let tabPane = primerError.closest('.tab-pane');
                let tabId = tabPane.attr('id');
                let tabButton = document.getElementById(tabId + '-tab');
                if (tabButton) {
                    new bootstrap.Tab(tabButton).show();
                }
            }

            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor, revise los campos marcados en rojo.',
                confirmButtonColor: '#9D2449'
            });
            return; 
        }

        $formPO.removeClass('was-validated');

        // ====================================================================
        // 4. PREPARACIÓN DE DATOS PARA AJAX
        // ====================================================================
        let formData = new FormData(this);
        let id_po = $('#id_po').val();
        let peticion = (id_po === '' || id_po == 0) ? 'INSERT_PO' : 'UPDATE_PO';
        formData.append('peticion', peticion);

        $btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando...');

        $.ajax({
            url: '_controller/PersonalOPAsync.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.result === 1) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.msg,
                        confirmButtonColor: '#285C4D',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (peticion === 'INSERT_PO' && response.id_nuevo) {
                                $('#id_po').val(response.id_nuevo);
                                $('h3.fw-bold').text('Editar Empleado');
                                
                                let url_actual = window.location.href;
                                let base_url = url_actual.substring(0, url_actual.lastIndexOf('/') + 1);
                                window.history.replaceState({}, '', base_url + response.id_nuevo);

                                let htmlEquipos = `
                                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary-subtle pb-3">
                                        <div>
                                            <h5 class="fw-bold text-guinda mb-0">Dispositivos Vinculados</h5>
                                            <small class="text-muted">Equipos asignados para uso diario o bajo resguardo legal.</small>
                                        </div>
                                        <a href="dispositivos/" class="btn btn-sm btn-guinda shadow-sm" id="btnVincularDispositivo">
                                            <i class="fas fa-plus me-1"></i> Añadir Dispositivo
                                        </a>
                                    </div>
                                    <div class="text-center py-5 text-muted border border-dashed rounded-3 bg-light">
                                        <i class="fas fa-laptop fa-3x mb-3 opacity-25"></i>
                                        <p class="mb-0">Este empleado no tiene dispositivos vinculados en el sistema.</p>
                                    </div>
                                `;
                                $('#v-pills-equipos').html(htmlEquipos);
                            }
                            $btnGuardar.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Guardar Empleado');
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Atención',
                        text: response.msg,
                        confirmButtonColor: '#9D2449'
                    });
                    $btnGuardar.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Guardar Empleado');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Servidor',
                    text: 'Ocurrió un problema de comunicación. Contacte a soporte técnico.',
                    confirmButtonColor: '#9D2449'
                });
                $btnGuardar.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Guardar Empleado');
            }
        });
    });

    // ====================================================================
    // 5. DESVINCULAR EQUIPOS 
    // ====================================================================
    $('.dropdown-item[data-action="quitar_uso"], .dropdown-item[data-action="quitar_resguardo"]').on('click', function(e) {
        e.preventDefault();
        
        let action = $(this).data('action'); 
        let id_disp = $(this).data('id');
        let id_po = $('#id_po').val();
        let id_sub = $('#id_sub').val();
        let etiquetaActual = $(this).closest('.card-body').find('.badge').text().trim();

        let titulo = '';
        let texto = '';

        if (action === 'quitar_uso') {
            titulo = '¿Quitar de Uso Diario?';
            texto = 'El equipo ya no estará asignado para el uso diario de este empleado.';
            if (etiquetaActual === 'Uso y Resguardo') {
                texto += '<br><br><div class="alert alert-warning py-2 mb-0 small text-start"><i class="fas fa-info-circle me-1"></i> <b>Nota:</b> El empleado seguirá siendo el <b class="text-dark">Responsable Legal (Resguardo)</b> del equipo.</div>';
            }
        } else if (action === 'quitar_resguardo') {
            titulo = '¿Liberar Resguardo Legal?';
            texto = 'El empleado ya no será el responsable legal de este equipo frente a la institución.';
            if (etiquetaActual === 'Uso y Resguardo') {
                texto += '<br><br><div class="alert alert-primary py-2 mb-0 small text-start"><i class="fas fa-info-circle me-1"></i> <b>Nota:</b> El equipo seguirá asignado para el <b class="text-dark">Uso Diario</b> del empleado.</div>';
            }
        }

        Swal.fire({
            title: titulo,
            html: texto, 
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-unlink me-1"></i> Sí, desvincular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                $.ajax({
                    url: '_controller/PersonalOPAsync.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        peticion: 'DESVINCULAR_EQUIPO',
                        id_po: id_po,
                        id_disp: id_disp,
                        tipo_vinculo: action,
                        id_sub: id_sub
                    },
                    success: function(response) {
                        if (response.result === 1) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Desvinculado!',
                                text: response.msg,
                                confirmButtonColor: '#285C4D'
                            }).then(() => {
                                location.reload(); 
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Atención', text: response.msg, confirmButtonColor: '#9D2449' });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Problema de comunicación con el servidor.', confirmButtonColor: '#9D2449' });
                    }
                });
            }
        });
    });

    // ====================================================================
    // 6. ALMACENAMIENTO JS (UX)
    // ====================================================================
    $(document).on('click', '#btnVincularDispositivo', function(e) {
        let id_po_actual = $('#id_po').val();
        if (id_po_actual && id_po_actual !== "" && id_po_actual != 0) {
            sessionStorage.setItem('po_preselect_asignado', id_po_actual);
        }
    });

});