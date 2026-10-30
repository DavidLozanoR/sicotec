$(document).ready(function() {

    const idSub = $('#id_sub_red').val(); 
    const urlAsync = '_controller/RedAsync.php';
    
    const modalForm = new bootstrap.Modal(document.getElementById('modalFormSegmento'));
    const modalIps = new bootstrap.Modal(document.getElementById('modalIps'));
    
    const $form = $('#formSegmento');
    const $tituloModal = $('#tituloModalSegmento');
    // ====================================================================
    // VALIDACIÓN REACTIVA PARA DIRECCIONES IP (Rango de Segmentos)
    // ====================================================================
    const ipInputs = document.querySelectorAll('#seg_ini, #seg_lim');
    // Expresión Regular estricta para formato IPv4 (Ej. 0-255.0-255.0-255.0-255)
    const ipv4Regex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    ipInputs.forEach(input => {
        
        // 1. Mientras escribe (input): Filtro en tiempo real y auto-formato
        input.addEventListener('input', function(e) {
            // Eliminar cualquier cosa que no sea número o punto
            let val = this.value.replace(/[^0-9\.]/g, ''); 
            
            // Separar la IP por los puntos
            let octetos = val.split('.');
            
            // Bloquear que existan más de 4 bloques
            if (octetos.length > 4) {
                octetos = octetos.slice(0, 4);
            }

            // Reglas de Negocio para cada bloque
            octetos = octetos.map(octeto => {
                // REGLA 1: Nunca permitir más de 3 dígitos (evita el desborde de ceros: 0000)
                if (octeto.length > 3) {
                    octeto = octeto.substring(0, 3);
                }
                
                // REGLA 2: Ningún bloque puede ser matemáticamente mayor a 255
                if (parseInt(octeto) > 255) {
                    // Si intenta escribir 256 o 300, lo recorta (se queda en 25 o 30)
                    return octeto.substring(0, 2); 
                }
                
                return octeto;
            });

            // Auto-Completado: Poner el punto si llegó a 3 dígitos 
            // Evitamos poner el punto si el usuario está borrando o si ya está en el 4to bloque
            if (e.inputType !== 'deleteContentBackward' && octetos.length < 4) {
                let ultimoOcteto = octetos[octetos.length - 1];
                if (ultimoOcteto.length === 3) {
                    octetos.push(''); // Forzamos un bloque vacío para imprimir el punto
                }
            }

            // Unir el arreglo de nuevo a texto con puntos
            this.value = octetos.join('.');
        });

        // 2. Al perder el foco (blur): Valida el formato lógico de extremo a extremo
        input.addEventListener('blur', function() {
            if (this.value === '') {
                this.setCustomValidity('La dirección IP es obligatoria.');
            } else if (!ipv4Regex.test(this.value)) {
                this.setCustomValidity('Formato de IP inválido. Ejemplo: 192.168.1.1');
                
                if(this.parentElement.querySelector('.invalid-feedback')) {
                    this.parentElement.querySelector('.invalid-feedback').innerText = 'Debe ser una IP completa (ej. 192.168.1.100)';
                }
            } else {
                this.setCustomValidity(''); // Todo perfecto
            }
        });
    });
    // ====================================================================
    // VALIDACIÓN REACTIVA PARA LA DESCRIPCIÓN
    // ====================================================================
    const $vlanDesc = $('textarea[name="descripcion"], input[name="descripcion"]');
    const MAX_LONGITUD_DESC = 150; //longitud maxima

    $vlanDesc.on('input', function() {
        let valor = $(this).val();

        // 1. TOPE MÁXIMO: Cortar el texto si supera el límite permitido
        if (valor.length > MAX_LONGITUD_DESC) {
            valor = valor.substring(0, MAX_LONGITUD_DESC);
            $(this).val(valor); // Actualiza el input truncando el excedente
        }

        // 2. VALIDACIÓN LÓGICA DE BOOTSTRAP (Opcional)
        // Si la descripción NO es obligatoria, simplemente asegúrate de limpiar errores:
        this.setCustomValidity(''); 
        
        // Pero si la descripción SÍ es obligatoria, descomenta estas líneas:
        /*
        if (valor.trim().length === 0) {
            this.setCustomValidity('Vacío');
        } else if (valor.trim().length < 10) {
            this.setCustomValidity('Muy corto'); // Exige al menos 10 caracteres
        } else {
            this.setCustomValidity(''); 
        }
        */
    });

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
        // Solo aplicar buscador si el select NO está deshabilitado
        if (!$('#seg_dep').prop('disabled')) {
            aplicarFuzzySearch('#seg_dep');
        }
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

        let peticion = $('#seg_id').val() === '' ? 'INSERT_SEG' : 'UPDATE_SEG';
        let bodyData = new FormData(this);
        bodyData.append('peticion', peticion);
        bodyData.append('id_sub', idSub);

        asyncProcessForm(bodyData, urlAsync, "Procesando...", "Guardando segmento...", "¡Operación Exitosa!");
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
                const payload = { peticion: 'DELETE_SEG', id_seg: id_seg, id_sub: idSub };
                asyncProcessForm(payload, urlAsync, "Dando de baja...", "Eliminando segmento...", "¡Eliminado!");
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

    // ====================================================================
    // 10. SECCIÓN DE CONFIGURACIÓN DE VLANS (PANEL DUAL)
    // ====================================================================
    
    // Inicialización del Modal y Elementos (
    const modalVlans = new bootstrap.Modal(document.getElementById('modalConfigVlans'));
    const $formVlan = $('#formVlan');
    const $vlanListSection = $('#vlanListSection');
    const $vlanFormSection = $('#vlanFormSection');
    const $vlanFormTitle = $('#vlanFormTitle');

    // ====================================================================
    // VALIDACIÓN REACTIVA PARA EL NOMBRE DE LA VLAN
    // ====================================================================
    const $vlanNombre = $('#vlan_nombre');
    const $vlanFeedback = $('#vlan_nombre_feedback');
    const MAX_LONGITUD_VLAN = 50;

    $vlanNombre.on('input', function() {
        // 1. Permitir letras (mayúsculas/minúsculas), números y guiones bajos (_). Eliminar espacios u otros símbolos.
        let valorLimpio = $(this).val().replace(/[^a-zA-ZÁÉÍÓÚÑáéíóúñ]_/g, '');
        
        //  Convertir a mayúsculas automáticamente
        valorLimpio = valorLimpio;
        if (valorLimpio.length > MAX_LONGITUD_VLAN) {
            valorLimpio = valorLimpio.substring(0, MAX_LONGITUD_VLAN);
        }

        // Reasignamos el valor limpio al input
        if ($(this).val() !== valorLimpio) {
            $(this).val(valorLimpio);
        }
        

        // 2. Validación de longitud
        if (valorLimpio.length === 0) {
            this.setCustomValidity('Vacío');
            $vlanFeedback.text('El nombre es obligatorio.');
        } else if (valorLimpio.length < 4) {
            this.setCustomValidity('Corto');
            $vlanFeedback.text(`Demasiado corto. Faltan ${4 - valorLimpio.length} caracteres.`);
        } else {
            this.setCustomValidity(''); // Es válido
            $vlanFeedback.text('Mínimo 4 caracteres (sin espacios).'); // Resetea el texto por defecto
        }
    });


    // Botón Principal: Abre el modal siempre en la vista de Lista
    $('#btnConfigVlans').on('click', function() {
        $vlanFormSection.addClass('d-none');
        $vlanListSection.removeClass('d-none');
        modalVlans.show();
    });

    // Botón "Nueva VLAN" (Oculta lista, muestra formulario limpio)
    $('#btnShowAddVlan').on('click', function() {
        $formVlan[0].reset();
        $('#vlan_id').val(''); 
        $formVlan.removeClass('was-validated');
        $vlanFormTitle.text('Registrar Nueva VLAN');
        
        $vlanListSection.addClass('d-none');
        $vlanFormSection.removeClass('d-none');
    });

    // Botón "Editar" en la tabla (Oculta lista, muestra formulario con datos)
    $('.btn-editar-vlan').on('click', function(e) {
        e.preventDefault();
        $('#vlan_id').val($(this).data('id'));
        $('#vlan_nombre').val($(this).data('nom'));
        $('#vlan_desc').val($(this).data('desc'));
        
        $formVlan.removeClass('was-validated');
        $vlanFormTitle.text('Editar VLAN');
        
        $vlanListSection.addClass('d-none');
        $vlanFormSection.removeClass('d-none');
    });

    // Botón "Regresar" (Cancela formulario, vuelve a mostrar lista)
    $('#btnCancelVlanForm').on('click', function() {
        $vlanFormSection.addClass('d-none');
        $vlanListSection.removeClass('d-none');
    });

    // Guardar (Insertar / Actualizar) VLAN vía AJAX
    $formVlan.on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $formVlan.addClass('was-validated');
            return;
        }

        let peticion = $('#vlan_id').val() === '' ? 'INSERT_VLAN' : 'UPDATE_VLAN';
        let bodyData = new FormData(this);
        bodyData.append('peticion', peticion);
        bodyData.append('id_sub', idSub);
        
        asyncProcessForm(bodyData, urlAsync, "Procesando...", "Guardando VLAN...", "¡Exitoso!");
    });

    // Eliminar VLAN (Borrado Físico)
    $('.btn-eliminar-vlan').on('click', function(e) {
        e.preventDefault();
        let id_vlan = $(this).data('id');
        let nom_vlan = $(this).data('nom');

        Swal.fire({
            title: '¿Eliminar VLAN?',
            html: `Está a punto de eliminar físicamente la <b>${nom_vlan}</b>.<br><small class="text-danger">Esta acción no se puede deshacer.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const payload = { peticion: 'DELETE_VLAN', id_vlan: id_vlan, id_sub: idSub };
                asyncProcessForm(payload, urlAsync, "Eliminando...", "Borrando VLAN...", "¡Eliminado!");
            }
        });
    });
});