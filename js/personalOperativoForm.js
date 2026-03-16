$(document).ready(function() {

    const $formPO = $('#formPersonalOperativo');
    const $btnGuardar = $('#btnGuardarPersonal');

    // 1. VALIDACIONES REACTIVAS (EN TIEMPO REAL)
    // ====================================================================

    // A) Nombres y Apellidos: Solo letras, sin espacios al inicio, sin dobles espacios
    $('input[name="nombres"], input[name="ape_pa"], input[name="ape_ma"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, ''); // Bloquea espacios al inicio
        valor = valor.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, ''); // Solo letras
        valor = valor.replace(/\s{2,}/g, ' '); // Evita dos espacios seguidos
        $(this).val(valor);
    });

    // B) Número de Cobro y Extensión: Solo números enteros
    $('input[name="num_cobro"], input[name="ext"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/[^0-9]/g, ''); // Bloquea todo lo que no sea número (incluye espacios)
        $(this).val(valor);
    });

    // C) Correo Institucional y Cuenta de Dominio: Sin espacios, alfanumérico y . _ -
    $('input[name="correo_prefijo"], input[name="cta_dom"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, ''); // Bloquea espacios al inicio
        valor = valor.replace(/[^a-zA-Z0-9._-]/g, ''); // Sin espacios en el medio, solo alfanumérico
        $(this).val(valor);
    });

    // D) Puesto Operativo: Letras, números, sin espacios al inicio, sin dobles espacios
    $('input[name="puesto"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, ''); // Bloquea espacios al inicio
        valor = valor.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, ''); // Letras y números (Ej. "Analista 1")
        valor = valor.replace(/\s{2,}/g, ' '); // Evita dos espacios seguidos
        $(this).val(valor);
    });

    // E) Dirección Completa: Letras, números, espacios y signos permitidos (, . # -)
    $('textarea[name="dir"]').on('input', function() {
        let valor = $(this).val();
        valor = valor.replace(/^\s+/g, ''); // Bloquea espacios al inicio
        valor = valor.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s,.#-]/g, ''); // Permite signos de direcciones
        valor = valor.replace(/\s{2,}/g, ' '); // Evita dos espacios seguidos
        $(this).val(valor);
    });

    // F) Feedback visual al salir del campo (Blur) o al cambiar un Select (Change)
    $formPO.find('input[required], select[required], textarea[required]').on('blur change', function() {
        validarCampoUI($(this));
    });

    // Función auxiliar para pintar los campos de verde o rojo individualmente
    function validarCampoUI($campo) {
        // Usamos .trim() para asegurar que no nos engañen con un espacio final
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
    // 2. LÓGICA DE ENVÍO DEL FORMULARIO (SUBMIT)
    // ====================================================================
    $formPO.on('submit', function(e) {
        e.preventDefault();

        // Forzamos la validación visual de todos los campos antes de enviar
        let formularioValido = true;
        $formPO.find('input[required], select[required], textarea[required]').each(function() {
            validarCampoUI($(this));
            if ($(this).val().trim() === '') {
                formularioValido = false;
            }
        });

        // Validación nativa de HTML5/Bootstrap
        if (!this.checkValidity() || !formularioValido) {
            e.stopPropagation();
            $formPO.addClass('was-validated'); 
            
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor, revise los campos marcados en rojo.',
                confirmButtonColor: '#9D2449'
            });
            return; 
        }

        // Si pasó la validación, quitamos la clase was-validated para evitar conflictos visuales
        $formPO.removeClass('was-validated');

        // ====================================================================
        // 3. PREPARACIÓN DE DATOS PARA AJAX
        // ====================================================================
        let formData = new FormData(this);
        
        let id_po = $('#id_po').val();
        let peticion = (id_po === '' || id_po == 0) ? 'INSERT_PO' : 'UPDATE_PO';
        formData.append('peticion', peticion);

        $btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Procesando...');

        // ====================================================================
        // 4. PETICIÓN AJAX AL BACKEND
        // ====================================================================
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
                            
                            // LÓGICA DE MANTENIMIENTO EN PANTALLA:
                            // Si acabamos de crear un nuevo empleado (INSERT_PO)
                            if (peticion === 'INSERT_PO' && response.id_nuevo) {
                                
                                // 1. Inyectamos el nuevo ID en el input oculto
                                $('#id_po').val(response.id_nuevo);
                                
                                // 2. Cambiamos el título de la página
                                $('h3.fw-bold').text('Editar Empleado');
                                
                                // 3. Modificamos la URL en el navegador (sin recargar)
                                let url_actual = window.location.href;
                                let base_url = url_actual.substring(0, url_actual.lastIndexOf('/') + 1);
                                window.history.replaceState({}, '', base_url + response.id_nuevo);

                                // 4. Habilitamos la pestaña de equipos y CORREGIMOS EL BOTÓN.
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
                            
                            // Restauramos el botón
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
    // 5. DESVINCULAR EQUIPOS (QUITAR USO DIARIO O RESGUARDO)
    // ====================================================================
    $('.dropdown-item[data-action="quitar_uso"], .dropdown-item[data-action="quitar_resguardo"]').on('click', function(e) {
        e.preventDefault();
        
        let action = $(this).data('action'); // 'quitar_uso' o 'quitar_resguardo'
        let id_disp = $(this).data('id');
        let id_po = $('#id_po').val();
        let id_sub = $('#id_sub').val();

        // Leemos la etiqueta actual de la tarjeta (Ej. "Uso Diario", "En Resguardo" o "Uso y Resguardo")
        let etiquetaActual = $(this).closest('.card-body').find('.badge').text().trim();

        // Textos dinámicos dependiendo de la acción y el estado actual
        let titulo = '';
        let texto = '';

        if (action === 'quitar_uso') {
            titulo = '¿Quitar de Uso Diario?';
            texto = 'El equipo ya no estará asignado para el uso diario de este empleado.';
            // Si tiene dualidad, le advertimos que el otro vínculo se mantiene
            if (etiquetaActual === 'Uso y Resguardo') {
                texto += '<br><br><div class="alert alert-warning py-2 mb-0 small text-start"><i class="fas fa-info-circle me-1"></i> <b>Nota:</b> El empleado seguirá siendo el <b class="text-dark">Responsable Legal (Resguardo)</b> del equipo.</div>';
            }
        } else if (action === 'quitar_resguardo') {
            titulo = '¿Liberar Resguardo Legal?';
            texto = 'El empleado ya no será el responsable legal de este equipo frente a la institución.';
            // Si tiene dualidad, le advertimos que el otro vínculo se mantiene
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
    // 6. ALMACENAMIENTO JS (UX): GUARDAR OP EN MEMORIA PARA VINCULACIÓN
    // ====================================================================
    // Usamos delegación de eventos por si el botón se creó dinámicamente con AJAX
    $(document).on('click', '#btnVincularDispositivo', function(e) {
        let id_po_actual = $('#id_po').val();
        
        // Si hay un empleado cargado en el formulario, lo guardamos en memoria
        if (id_po_actual && id_po_actual !== "" && id_po_actual != 0) {
            sessionStorage.setItem('po_preselect_asignado', id_po_actual);
        }
    });

});