$(document).ready(function() {

    const idSub = $('#id_sub_red').val(); 
    const urlAsync = '_controller/RedAsync.php';
    
    const modalForm = new bootstrap.Modal(document.getElementById('modalFormSegmento'));
    const modalIps = new bootstrap.Modal(document.getElementById('modalIps'));
    
    const $form = $('#formSegmento');
    const $tituloModal = $('#tituloModalSegmento');

    // ==========================================
    // FUNCIÓN GENÉRICA: BUSCADOR FUZZY PARA SELECTS
    // EX: aplicarFuzzySearch('select[name="icono"]'); 
    // EX: aplicarFuzzySearch('#id_name_seegment') 
    // ==========================================
    function aplicarFuzzySearch(selector) {
        const selectOriginal = document.querySelector(selector);
        if (!selectOriginal) return;

        // Evitar doble inicialización
        if (selectOriginal.nextElementSibling && selectOriginal.nextElementSibling.classList.contains('fuzzy-container')) return;

        // 1. Ocultar el select original (seguirá almacenando el valor para el FormData)
        selectOriginal.style.display = 'none';

        // 2. Crear la estructura del nuevo componente
        const container = document.createElement('div');
        container.className = 'position-relative w-100 fuzzy-container';

        const inputBuscador = document.createElement('input');
        inputBuscador.type = 'text';
        inputBuscador.className = 'form-control focus-guinda border-secondary-subtle pe-4';
        inputBuscador.placeholder = selectOriginal.options[0] ? selectOriginal.options[0].text : 'Buscar...';
        inputBuscador.autocomplete = 'off';

        const iconClear = document.createElement('span');
        iconClear.innerHTML = '&times;';
        iconClear.className = 'position-absolute text-muted';
        iconClear.style.cssText = 'right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: none; font-size: 1.2rem;';

        const listaDesplegable = document.createElement('ul');
        listaDesplegable.className = 'list-group position-absolute w-100 shadow-lg z-3 d-none';
        listaDesplegable.style.cssText = 'max-height: 250px; overflow-y: auto; top: 100%; left: 0;';

        // 3. Poblar la lista con las opciones del select original
        Array.from(selectOriginal.options).forEach(opt => {
            if (opt.value === '') return; // Omitir el placeholder original

            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            li.textContent = opt.text;
            li.dataset.value = opt.value;
            listaDesplegable.appendChild(li);

            // Sincronizar si ya existe una opción seleccionada (ej. al editar)
            if (opt.selected) {
                inputBuscador.value = opt.text;
                iconClear.style.display = 'block';
            }
        });

        // Ensamblar e inyectar en el DOM
        container.appendChild(inputBuscador);
        container.appendChild(iconClear);
        container.appendChild(listaDesplegable);
        selectOriginal.parentNode.insertBefore(container, selectOriginal.nextSibling);

        // 4. Lógica de filtrado (Fuzzy Search)
        function filtrarResultados(texto) {
            const query = texto.toLowerCase();
            let coincidencias = 0;
            const items = listaDesplegable.querySelectorAll('li');

            items.forEach(li => {
                const textoItem = li.textContent.toLowerCase();
                if (textoItem.includes(query)) {
                    li.style.display = 'block';
                    coincidencias++;
                } else {
                    li.style.display = 'none';
                }
            });

            if (coincidencias > 0) {
                listaDesplegable.classList.remove('d-none');
            } else {
                listaDesplegable.classList.add('d-none');
            }
        }

        // 5. Eventos del Input
        inputBuscador.addEventListener('focus', () => {
            listaDesplegable.classList.remove('d-none');
            filtrarResultados(inputBuscador.value);
        });

        inputBuscador.addEventListener('input', (e) => {
            const valor = e.target.value;
            filtrarResultados(valor);
            iconClear.style.display = valor ? 'block' : 'none';
            // Al escribir, se borra la selección real hasta que el usuario elija una opción válida
            selectOriginal.value = ''; 
        });

        // 6. Eventos de la Lista (Selección)
        listaDesplegable.addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (!li) return;

            // Actualizar interfaz visual
            inputBuscador.value = li.textContent;
            listaDesplegable.classList.add('d-none');
            iconClear.style.display = 'block';

            // Actualizar el valor del <select> oculto para el FormData
            selectOriginal.value = li.dataset.value;

            // Disparar evento change por si hay validaciones atadas al select original
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // 7. Evento Limpiar (X)
        iconClear.addEventListener('click', () => {
            inputBuscador.value = '';
            selectOriginal.value = '';
            iconClear.style.display = 'none';
            filtrarResultados('');
            inputBuscador.focus();
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // 8. Cerrar dropdown al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                listaDesplegable.classList.add('d-none');
                
                // Si el usuario escribió algo pero no seleccionó nada de la lista, restaurar o limpiar
                if (selectOriginal.value === '') {
                    inputBuscador.value = '';
                    iconClear.style.display = 'none';
                } else {
                    const opcionSeleccionada = selectOriginal.options[selectOriginal.selectedIndex];
                    if (opcionSeleccionada) {
                        inputBuscador.value = opcionSeleccionada.text;
                    }
                }
            }
        });
    }

    // 1. ABRIR MODAL PARA "NUEVO SEGMENTO"
    $('#btnNuevoSegmento').on('click', function() {
        $form[0].reset(); 
        $('#seg_id').val(''); 
        
        // Desbloquear campos
        $('#seg_ini, #seg_lim').prop('readonly', false);
        $('#seg_reservas').val(''); // Limpiamos reservas previas

        $tituloModal.html('<i class="fas fa-plus-circle me-2 text-guinda"></i>Nuevo Segmento');
        $form.removeClass('was-validated'); 
        
        
        modalForm.show();
        aplicarFuzzySearch('#seg_dep')
    });

    // 2. ABRIR MODAL PARA "EDITAR SEGMENTO Y RESERVAS"
    $('.btn-editar-seg').on('click', function(e) {
        e.preventDefault();
        
        $('#seg_id').val($(this).data('id'));
        $('#seg_dep').val($(this).data('dep'));
        $('#seg_ini').val($(this).data('ini'));
        $('#seg_lim').val($(this).data('lim'));

        $('#seg_reservas').val($(this).data('reservas'));
        
        // FIX: Desbloqueamos los campos para poder cambiar la IP
        $('#seg_ini, #seg_lim').prop('readonly', false);
        $('#bloqueReservas').show();

        $tituloModal.html('<i class="fas fa-edit me-2 text-secondary"></i>Editar Segmento / Reservas');
        $form.removeClass('was-validated'); 
        modalForm.show();
    });
    // 3. ENVÍO DE FORMULARIO (INSERT / UPDATE)
    $form.on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $form.addClass('was-validated');
            return;
        }

        let formData = $(this).serialize() + '&peticion=' + ($('#seg_id').val() === '' ? 'INSERT_SEG' : 'UPDATE_SEG') + '&id_sub=' + idSub;

        const originalText = $('#btnGuardarSeg').html();
        $('#btnGuardarSeg').html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...').prop('disabled', true);

        $.ajax({
            url: urlAsync,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.result === 1) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: response.msg,
                        confirmButtonColor: '#285C4D'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Alerta de Validación',
                        text: response.msg,
                        confirmButtonColor: '#9D2449'
                    });
                }
            },
            error: function() {
                Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
            },
            complete: function() {
                $('#btnGuardarSeg').html(originalText).prop('disabled', false);
            }
        });
    });

    // 4. ELIMINAR (BAJA LÓGICA)
    $('.btn-eliminar-seg').on('click', function(e) {
        e.preventDefault();
        let id_seg = $(this).data('id');

        Swal.fire({
            title: '¿Dar de baja este segmento?',
            text: "Se ocultará del sistema. La operación fallará si hay dispositivos conectados.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, dar de baja'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(urlAsync, { peticion: 'DELETE_SEG', id_seg: id_seg, id_sub: idSub }, function(response) {
                    if (response.result === 1) {
                        Swal.fire({icon: 'success', title: 'Eliminado', text: response.msg, confirmButtonColor: '#285C4D'})
                        .then(() => location.reload());
                    } else {
                        Swal.fire({icon: 'error', title: 'Bloqueo', text: response.msg, confirmButtonColor: '#9D2449'});
                    }
                }, 'json');
            }
        });
    });

    // 5. ABRIR MODAL "EXPLORADOR DE IPs"
    $('.btn-ver-ips').on('click', function(e) {
        e.preventDefault();
        let id_seg = $(this).data('id');
        let $tbody = $('#tbodyIps');
        
        $tbody.html('<tr><td colspan="3" class="text-center py-4"><i class="fas fa-spinner fa-spin text-guinda fa-2x"></i><br>Cargando direcciones IP...</td></tr>');
        $('#buscadorIps').val(''); // Limpiamos buscador anterior
        modalIps.show();

        $.ajax({
            url: urlAsync,
            type: 'POST',
            data: { peticion: 'GET_IPS', id_seg: id_seg },
            dataType: 'json',
            success: function(response) {
                if (response.result === 1) {
                    let html = '';
                    if (response.data.length === 0) {
                        html = '<tr><td colspan="3" class="text-center text-muted py-3">No hay IPs generadas para este segmento.</td></tr>';
                    } else {
                        response.data.forEach(function(ip) {
                            let badgeClass = 'bg-secondary';
                            let estatusLow = ip.est.toLowerCase();
                            
                            if (estatusLow === 'libre') badgeClass = 'bg-success';
                            else if (estatusLow === 'ocupado') badgeClass = 'bg-primary';
                            else if (estatusLow === 'reservado') badgeClass = 'bg-warning text-dark';
                            
                            let hostInfo = (ip.nom_host) ? 
                                `<span class="fw-bold text-dark">${ip.nom_host}</span><br><small class="text-muted">Inv: ${ip.num_inv}</small>` : 
                                '<span class="text-muted fst-italic">-- Ninguno --</span>';

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

    // 6. BUSCADOR EN TIEMPO REAL (FILTRO DE TABLA EN EL MODAL)
    $('#buscadorIps').on('keyup', function() {
        let valor = $(this).val().toLowerCase();
        $('#tbodyIps tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(valor) > -1);
        });
    });
    // ====================================================================
    // 8. MOTOR DE FILTRADO PARA LOS SEGMENTOS (Tarjetas y Tabla)
    // ====================================================================
    
    // Capturamos los controles del filtro
    const $filtroTexto = $('#filtroTexto');
    const $filtroDep = $('#filtroDep');
    const $filtroEstado = $('#filtroEstado');
    
    function aplicarFiltrosSegmentos() {
        let texto = $filtroTexto.val().toLowerCase().trim();
        let valDep = $filtroDep.val();
        let valEstado = $filtroEstado.val();
        
        let elementosVisibles = 0;

        // Iteramos sobre todos los elementos con clase .item-segmento (Cards y Filas TR)
        $('.item-segmento').each(function() {
            let $el = $(this);
            
            // Leemos los datos inyectados en HTML
            let dataDep = $el.attr('data-dep'); // Puede ser vacío si no tiene dep
            let dataTexto = $el.attr('data-texto');
            let libres = parseInt($el.attr('data-libres'));
            let reservas = parseInt($el.attr('data-reservas'));

            // 1. Condición de Texto
            let cumpleTexto = true;
            if (texto !== '') {
                cumpleTexto = dataTexto.includes(texto);
            }

            // 2. Condición de Departamento
            let cumpleDep = true;
            if (valDep !== 'todos') {
                if (valDep === 'sin_asignar') {
                    cumpleDep = (dataDep === '');
                } else {
                    cumpleDep = (dataDep === valDep);
                }
            }

            // 3. Condición de Estado de Ocupación
            let cumpleEstado = true;
            if (valEstado !== 'todos') {
                if (valEstado === 'con_libres') {
                    cumpleEstado = (libres > 0);
                } else if (valEstado === 'saturados') {
                    cumpleEstado = (libres === 0);
                } else if (valEstado === 'con_reservas') {
                    cumpleEstado = (reservas > 0);
                }
            }

            // Si cumple las 3 condiciones, mostramos la tarjeta/fila
            if (cumpleTexto && cumpleDep && cumpleEstado) {
                $el.show();
                elementosVisibles++;
            } else {
                $el.hide();
            }
        });

        // Mostrar u ocultar el mensaje de "Sin Resultados"
        if (elementosVisibles === 0 && $('.item-segmento').length > 0) {
            $('#msg-sin-resultados').removeClass('d-none');
            // Ocultamos los encabezados de la tabla móvil si no hay resultados
            $('#contenedor-tabla').hide();
        } else {
            $('#msg-sin-resultados').addClass('d-none');
            $('#contenedor-tabla').show();
        }
    }

    // llamar el fuzzysearch para las vlans
    aplicarFuzzySearch('#filtroDep');
    // Escuchamos los eventos para que el filtro sea en vivo
    $filtroTexto.on('keyup', aplicarFiltrosSegmentos);
    $filtroDep.on('change', aplicarFiltrosSegmentos);
    $filtroEstado.on('change', aplicarFiltrosSegmentos);

// ====================================================================
    // 9. SINCRONIZADOR DE DROPDOWNS NATIVOS (Para Filtros visuales de 30vh)
    // ====================================================================
    $('.dropdown-filtro .dropdown-item').on('click', function(e) {
        e.preventDefault();
        
        let $item = $(this);
        let $container = $item.closest('.dropdown-filtro');
        let $inputOculto = $container.find('input[type="hidden"]');
        let $textoBoton = $container.find('button span');

        // 1. Actualiza el texto visual del botón
        $textoBoton.text($item.text().trim());

        // 2. Mueve las clases para resaltar la opción seleccionada
        $container.find('.dropdown-item').removeClass('active bg-light text-dark fw-bold');
        $item.addClass('active bg-light text-dark fw-bold');

        // 3. Asigna el valor al input y avisa a tu función aplicarFiltrosSegmentos()
        $inputOculto.val($item.data('value')).trigger('change');
    });



});