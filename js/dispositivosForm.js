document.addEventListener('DOMContentLoaded', () => {

    const formDispositivo = document.getElementById('formDispositivo');
    const contenedorServicios = document.getElementById('contenedor-servicios');
    const btnAddServicio = document.getElementById('btn-add-servicio');
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });

    const btnGestionarCatalogo = document.getElementById('btn-gestionar-catalogo');
    const modalCatalogoElement = document.getElementById('modalCatalogoServicios');
    const tbodyCatalogo = document.getElementById('tbody-catalogo-servicios');

// ==========================================
    // 0. VALIDACIONES VISUALES Y WIZARD (PESTAÑAS)
    // ==========================================
    
    // Referencias del Número de Inventario Dinámico
    const invTipo = document.getElementById('inv_tipo');
    const invPrefixManual = document.getElementById('inv_prefix_manual');
    const invPart1 = document.getElementById('inv_part1');
    const invPart2 = document.getElementById('inv_part2');
    const invPart3 = document.getElementById('inv_part3');
    const invFinal = document.getElementById('num_inv_final');
    const invHelp = document.getElementById('inv_help');

    const inputHostname = document.querySelector('input[name="nom_host"]');
    if (inputHostname) {
        inputHostname.addEventListener('input', function() {
            // 1. Convierte a mayúsculas y quita espacios dinámicamente
            let valorLimpio = this.value.toUpperCase().replace(/\s+/g, '');
            
            // 2. Restricción estricta de longitud por estándar de red (Max 20)
            if (valorLimpio.length > 20) {
                valorLimpio = valorLimpio.substring(0, 20);
            }
            
            // 3. Devolvemos el valor limpio al input
            this.value = valorLimpio;
            
            // 4. Activamos la validación visual de Bootstrap (Verde/Rojo)
            if (typeof validarCampoUI === 'function') {
                validarCampoUI(this);
            }
        });
    }

   // Función principal de validación (Soporta prefijo manual + bloques y sincronización Fuzzy)
    function validarCampoUI(campo) {
        if (!campo) return true;
        const valor = campo.value.trim();
        const esInventario = campo.name === 'num_inv';

        // Corrección de precedencia lógica (Paréntesis añadidos)
        if (!campo.required && (valor === '' || valor === '--')) {
            if (esInventario && invTipo) {
                [invPrefixManual, invPart1, invPart2, invPart3].forEach(el => el.classList.remove('is-valid', 'is-invalid'));
                if(invHelp) invHelp.classList.add('d-none');
            } else {
                campo.classList.remove('is-valid', 'is-invalid');
                
                // Si es opcional y está vacío, limpiar también el buscador Fuzzy si existe
                if (campo.nextElementSibling && campo.nextElementSibling.classList.contains('fuzzy-container')) {
                    const fuzzyInput = campo.nextElementSibling.querySelector('input');
                    if (fuzzyInput) {
                        fuzzyInput.classList.remove('is-valid', 'is-invalid');
                        fuzzyInput.setCustomValidity(''); 
                    }
                }
            }
            return true; 
        }

        let isValid = valor !== '';
        
        // Reglas estrictas de Inventario
        if (esInventario) {
            const partsValid = invPart1.value.length === 3 && 
                               invPart2.value.length === 5 && 
                               invPart3.value.length === 2;

            if (invTipo.value === 'OTRO') {
                const prefixValid = invPrefixManual.value.trim().length >= 6 && invPrefixManual.value.trim().length <= 15;
                isValid = prefixValid && partsValid;
                
                if(!isValid && invHelp) {
                    if (!prefixValid) {
                        invHelp.textContent = "* El prefijo manual debe tener entre 6 y 15 NÚMEROS.";
                    } else {
                        invHelp.textContent = "* Complete los bloques: 3 dígitos - 5 dígitos - 2 dígitos.";
                    }
                    invHelp.classList.remove('d-none');
                }
            } else {
                isValid = partsValid;
                if(!isValid && invHelp) {
                    invHelp.textContent = "* Complete los bloques: 3 dígitos - 5 dígitos - 2 dígitos.";
                    invHelp.classList.remove('d-none');
                }
            }
            if (isValid && invHelp) invHelp.classList.add('d-none');
        }

        // Colorear en rojo o verde las cajas correspondientes
        if (esInventario) {
            const elementsToValidate = invTipo.value === 'OTRO' ? [invPrefixManual, invPart1, invPart2, invPart3] : [invPart1, invPart2, invPart3];
            
            if (invTipo.value !== 'OTRO') {
                invPrefixManual.classList.remove('is-valid', 'is-invalid');
            }

            elementsToValidate.forEach(el => {
                el.classList.toggle('is-valid', isValid);
                el.classList.toggle('is-invalid', !isValid);
            });
        } else {
            // Campos normales
            campo.classList.toggle('is-valid', isValid);
            campo.classList.toggle('is-invalid', !isValid);

            // FIX: Sincronizar el estado de validación con el Input del Buscador Fuzzy
            if (campo.nextElementSibling && campo.nextElementSibling.classList.contains('fuzzy-container')) {
                const fuzzyInput = campo.nextElementSibling.querySelector('input');
                if (fuzzyInput) {
                    if (!isValid) {
                        // Fuerza al input dinámico a volverse INVÁLIDO nativamente para Bootstrap
                        fuzzyInput.setCustomValidity('Inválido');
                    } else {
                        // Lo marca como válido
                        fuzzyInput.setCustomValidity('');
                    }
                    fuzzyInput.classList.toggle('is-valid', isValid);
                    fuzzyInput.classList.toggle('is-invalid', !isValid);
                }
            }
        }

        return isValid;
    }

    // Lógica para alternar las vistas del Número de Inventario
    function toggleInvUI() {
        if (!invTipo || !invPrefixManual) return;
        
        if (invTipo.value === 'OTRO') {
            // Se encoge el select al ancho de la flecha de Bootstrap para que no afecte visualmente
            invTipo.style.width = '42px'; 
            
            invPrefixManual.classList.remove('d-none');
            invPrefixManual.disabled = false;
            setTimeout(() => invPrefixManual.focus(), 100); 
        } else {
            // El select recupera su tamaño original para mostrar los números predefinidos
            invTipo.style.width = '110px'; 
            
            invPrefixManual.classList.add('d-none');
            invPrefixManual.disabled = true;
        }
        
        if (invHelp) invHelp.classList.add('d-none');
        buildInvFinal();
    }

    function buildInvFinal() {
        if (!invTipo) return;
        
        const prefix = invTipo.value === 'OTRO' ? invPrefixManual.value.trim() : invTipo.value;
        
        if (prefix || invPart1.value || invPart2.value || invPart3.value) {
            invFinal.value = `${prefix}${invPart1.value}-${invPart2.value}-${invPart3.value}`;
        } else {
            invFinal.value = '';
        }
        
        if (invFinal.value !== '' && invFinal.value !== '--') {
            validarCampoUI(invFinal);
        } else if (!invFinal.required) {
            [invPrefixManual, invPart1, invPart2, invPart3].forEach(el => el.classList.remove('is-valid', 'is-invalid'));
            if(invHelp) invHelp.classList.add('d-none');
        }
    }

    // Inicialización y Listeners del Inventario
    if (invTipo) {
        invTipo.addEventListener('change', toggleInvUI);
        
        // MODIFICACIÓN REACTIVA: Solo permite el ingreso de números (\D significa "Todo lo que no sea número")
        invPrefixManual.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, ''); 
            buildInvFinal();
        });
        
        invPart1.addEventListener('input', function() { 
            this.value = this.value.replace(/\D/g, ''); 
            buildInvFinal();
            if(this.value.length === 3) invPart2.focus(); // Salto automático
        });
        invPart2.addEventListener('input', function() { 
            this.value = this.value.replace(/\D/g, ''); 
            buildInvFinal();
            if(this.value.length === 5) invPart3.focus(); // Salto automático
        });
        invPart3.addEventListener('input', function() { 
            this.value = this.value.replace(/\D/g, ''); 
            buildInvFinal();
        });

        [invPrefixManual, invPart1, invPart2, invPart3].forEach(el => {
            if(el) el.addEventListener('blur', () => { if(invFinal.value !== '' && invFinal.value !== '--') validarCampoUI(invFinal); });
        });

        // Pre-carga si se está editando un equipo
        if (invFinal.value) {
            const val = invFinal.value;
            const regex18 = /^180000(\d{3})-(\d{5})-(\d{2})$/;
            const regex51 = /^51501000(\d{3})-(\d{5})-(\d{2})$/;
            
            // MODIFICACIÓN DE CARGA: Regex actualizada para aceptar de 6 a 15 dígitos únicamente numéricos
            const regexOtro = /^(\d{6,15})(\d{3})-(\d{5})-(\d{2})$/;
            
            const match18 = val.match(regex18);
            const match51 = val.match(regex51);
            const matchOtro = val.match(regexOtro);

            if (match18) {
                invTipo.value = '180000';
                invPart1.value = match18[1];
                invPart2.value = match18[2];
                invPart3.value = match18[3];
            } else if (match51) {
                invTipo.value = '51501000';
                invPart1.value = match51[1];
                invPart2.value = match51[2];
                invPart3.value = match51[3];
            } else if (matchOtro) {
                invTipo.value = 'OTRO';
                invPrefixManual.value = matchOtro[1];
                invPart1.value = matchOtro[2];
                invPart2.value = matchOtro[3];
                invPart3.value = matchOtro[4];
            }
        }
        toggleInvUI();
    }

    // Listener para avanzar entre pestañas WIZARD
    if (formDispositivo) {
        const inputsToValidate = formDispositivo.querySelectorAll('input[required], select[required], textarea[required]');
        
        ['blur', 'change', 'input'].forEach(evento => {
            inputsToValidate.forEach(input => input.addEventListener(evento, () => validarCampoUI(input)));
        });

        document.querySelectorAll('.btn-siguiente').forEach(btn => {
            btn.addEventListener('click', function() {
                const pestanaActual = this.closest('.tab-pane');
                const inputsActuales = pestanaActual.querySelectorAll('input[required], select[required], textarea[required]');
                
                let todoValido = Array.from(inputsActuales).every(input => validarCampoUI(input));
                
                if (pestanaActual.querySelector('#num_inv_final')) {
                    if (invFinal.value !== '' && invFinal.value !== '--' && !validarCampoUI(invFinal)) {
                        todoValido = false;
                        Swal.fire('Formato Incorrecto', 'Revise que el número de inventario esté completo (Los recuadros en rojo).', 'warning');
                    }
                }

                if (todoValido) {
                    const tabTarget = document.getElementById(this.dataset.next);
                    if (tabTarget) new bootstrap.Tab(tabTarget).show();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Información Incompleta',
                        text: 'Llene correctamente todos los campos obligatorios y sin errores de formato antes de avanzar.',
                        confirmButtonColor: '#9D2449'
                    });
                }
            });
        });
    }
    // ==========================================
    // 6. BUSCADOR FUZZY (MOVIDO ARRIBA PARA REACTIVIDAD)
    // ==========================================
    function aplicarFuzzySearch(selector) {
        const selectOriginal = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!selectOriginal) return;

        // REACTIVIDAD: Si ya existe una lista, la borramos para reconstruirla con los datos nuevos (ej. IPs)
        if (selectOriginal.nextElementSibling && selectOriginal.nextElementSibling.classList.contains('fuzzy-container')) {
            selectOriginal.nextElementSibling.remove();
        }

        selectOriginal.style.display = 'none';

        const container = document.createElement('div');
        container.className = 'position-relative w-100 fuzzy-container';

        const inputBuscador = document.createElement('input');
        inputBuscador.type = 'text';
        inputBuscador.className = 'form-control focus-guinda border-secondary-subtle pe-4';
        
        if (selectOriginal.classList.contains('font-monospace')) inputBuscador.classList.add('font-monospace');
        if (selectOriginal.classList.contains('form-select-sm')) inputBuscador.classList.add('form-control-sm');
        
        // Sincronizar bloqueo (disabled) y estilos de alerta
        if (selectOriginal.disabled) {
            inputBuscador.disabled = true;
            inputBuscador.classList.add('bg-light');
            
            // FIX: Si el select original fue marcado en rojo por falta de VLAN, heredamos el diseño
            if (selectOriginal.classList.contains('border-danger')) {
                inputBuscador.classList.remove('border-secondary-subtle');
                inputBuscador.classList.add('border-danger', 'text-danger', 'fw-bold');
            }
        }

        inputBuscador.placeholder = selectOriginal.options.length > 0 && selectOriginal.options[0].text ? selectOriginal.options[0].text : 'Buscar...';
        inputBuscador.autocomplete = 'off';

        const iconClear = document.createElement('span');
        iconClear.innerHTML = '&times;';
        iconClear.className = 'position-absolute text-muted';
        iconClear.style.cssText = 'right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: none; font-size: 1.2rem; z-index: 4;';

        const listaDesplegable = document.createElement('ul');
        listaDesplegable.className = 'list-group position-absolute w-100 shadow-lg z-3 d-none overflow-x-hidden';
        listaDesplegable.style.cssText = 'max-height: 200px; overflow-y: auto; top: 100%; left: 0; margin-top: 2px;';

        Array.from(selectOriginal.options).forEach(opt => {
            if (opt.value === '') return;

            // Conservamos soporte para OPTGROUP si lo usaran los demás selects
            if (opt.parentElement.tagName === 'OPTGROUP') {
                if (opt.parentElement.firstElementChild === opt) {
                    const headerLi = document.createElement('li');
                    headerLi.className = 'list-group-item bg-light text-muted small fw-bold pe-none';
                    headerLi.textContent = opt.parentElement.label;
                    listaDesplegable.appendChild(headerLi);
                }
            }

            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action py-2';
            li.style.cursor = 'pointer';
            li.title = opt.text;
            if (opt.parentElement.tagName === 'OPTGROUP') li.classList.add('ps-4');
            
            // Si el option tiene un HTML inyectado (como las VLANs), lo renderizamos. Sino, texto normal.
            if (opt.dataset.htmlText) {
                li.innerHTML = opt.dataset.htmlText;
            } else {
                li.textContent = opt.text;
            }

            li.dataset.value = opt.value;
            listaDesplegable.appendChild(li);

            if (opt.selected && opt.value !== "") {
                inputBuscador.value = opt.text;
                if (!inputBuscador.disabled) iconClear.style.display = 'block';
            }
        });

        container.appendChild(inputBuscador);
        container.appendChild(iconClear);
        container.appendChild(listaDesplegable);
        selectOriginal.parentNode.insertBefore(container, selectOriginal.nextSibling);

        function filtrarResultados(texto) {
            const query = texto.toLowerCase();
            let coincidencias = 0;
            const items = listaDesplegable.querySelectorAll('li:not(.pe-none)');

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

        inputBuscador.addEventListener('focus', () => {
            if (!inputBuscador.disabled) {
                listaDesplegable.classList.remove('d-none');
                filtrarResultados(inputBuscador.value);
            }
        });

        inputBuscador.addEventListener('input', (e) => {
            const valor = e.target.value;
            filtrarResultados(valor);
            iconClear.style.display = valor ? 'block' : 'none';
            selectOriginal.value = ''; 
        });

        listaDesplegable.addEventListener('click', (e) => {
            const li = e.target.closest('li:not(.pe-none)');
            if (!li) return;

            inputBuscador.value = li.textContent;
            listaDesplegable.classList.add('d-none');
            iconClear.style.display = 'block';
            selectOriginal.value = li.dataset.value;
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        iconClear.addEventListener('click', () => {
            inputBuscador.value = '';
            selectOriginal.value = '';
            iconClear.style.display = 'none';
            filtrarResultados('');
            inputBuscador.focus();
            selectOriginal.dispatchEvent(new Event('change', { bubbles: true }));
        });

        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                listaDesplegable.classList.add('d-none');
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

    // ==========================================
    // 1. LÓGICA DINÁMICA DE INTERFACES DE RED (VLAN -> IP)
    // ==========================================
    const btnAddInterfaz = document.getElementById('btn-add-interfaz');
    const contenedorInterfaces = document.getElementById('contenedor-interfaces');
    const templateInterfaz = document.getElementById('template-interfaz');

    function formatearMAC(input) {
        let value = input.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
        let formatted = value.match(/.{1,2}/g);
        input.value = formatted ? formatted.join(':').substring(0, 17) : '';
    }

    function cargarVLANs(selectVlan) {
        selectVlan.innerHTML = '<option value="" selected disabled>-- Seleccione una red --</option>';
        
        // 1. Verificamos que la variable exista y sea un array
        if (typeof redesDisponibles === 'undefined' || !redesDisponibles || !Array.isArray(redesDisponibles) || redesDisponibles.length === 0) {
            return false;
        }

        const segmentosUnicos = new Map();
        
        // 2. Mapeamos filtrando basura o nulos del backend
        redesDisponibles.forEach(red => {
            if (red && red.id_seg && !segmentosUnicos.has(red.id_seg)) {
                segmentosUnicos.set(red.id_seg, red);
            }
        });

        // FIX CRUCIAL: Si después de filtrar los nulos el mapa está vacío, ¡No hay redes válidas!
        if (segmentosUnicos.size === 0) {
            return false; 
        }

        // Construir opciones
        segmentosUnicos.forEach((red, id_seg) => {
            const nombreVlan = red.nom_vlan || 'General / Sin VLAN';
            
            let textoRango = `ID: ${id_seg}`;
            if (red.ip_ini && red.ip_lim) {
                textoRango = `${red.ip_ini} - ${red.ip_lim}`;
            }

            const opt = document.createElement('option');
            opt.value = id_seg;
            opt.textContent = `${nombreVlan} - ${textoRango}`;
            opt.dataset.htmlText = `<span class="fw-bold text-dark">${nombreVlan}</span> <span class="fw-normal text-secondary">- ${textoRango}</span>`;
            
            selectVlan.appendChild(opt);
        });
        
        return true; // Solo retorna true si realmente logró mapear datos válidos
    }

    function cargarIPs(id_seg, selectIp) {
        selectIp.innerHTML = '<option value="">-- Seleccione una IP --</option>';
        if (!id_seg) return;

        const redesFiltradas = redesDisponibles.filter(red => red.id_seg == id_seg);
        redesFiltradas.forEach(red => {
            const opt = document.createElement('option');
            opt.value = red.id_red;
            opt.textContent = red.ip;
            selectIp.appendChild(opt);
        });
        selectIp.disabled = false;
        selectIp.classList.remove('bg-light');
    }

    function agregarInterfaz(datosPreCargados = null) {
        if (!contenedorInterfaces || !templateInterfaz) return;
        
        const numInterfaces = contenedorInterfaces.querySelectorAll('.interfaz-item').length;
        if (!datosPreCargados && numInterfaces >= 2) {
            Swal.fire('Límite alcanzado', 'Solo puedes registrar un máximo de 2 interfaces (Ethernet y WiFi).', 'warning');
            return;
        }

        const clon = templateInterfaz.content.cloneNode(true);
        const nuevoItem = clon.querySelector('.interfaz-item');
        
       const selectTipo = nuevoItem.querySelector('.select-tipo-conexion');
        const inputMac = nuevoItem.querySelector('.input-mac');
        const selectVlan = nuevoItem.querySelector('.select-vlan');
        const selectIp = nuevoItem.querySelector('.select-ip');
        const btnEliminar = nuevoItem.querySelector('.btn-eliminar-interfaz');

        // Capturamos si existen redes
        const hayRedes = cargarVLANs(selectVlan);

        // BLOQUEO LÓGICO: Si no hay redes, bloqueamos el select e inyectamos la alerta
        if (!hayRedes) {
            selectVlan.innerHTML = '<option value="">Bloqueado - Catálogo vacío</option>';
            selectVlan.disabled = true;
            selectVlan.classList.add('border-danger', 'bg-light', 'text-muted');
            
            const alertaHTML = `
                <div class="alert alert-danger mt-2 mb-0 py-2 small fw-bold shadow-sm border-0 border-start border-4 border-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i> ¡Alto! Debe dirigirse a "Infraestructura de Red" y crear al menos un VLAN y un SEGMENTO antes de asignar IPs.
                </div>
            `;
            // Inyectamos la alerta justo debajo del select
            selectVlan.insertAdjacentHTML('afterend', alertaHTML);
        }

        if (datosPreCargados) {
            selectTipo.value = datosPreCargados.tipo;
            inputMac.value = datosPreCargados.mac;
            selectVlan.disabled = false;

            if (datosPreCargados.id_seg && datosPreCargados.id_red) {
                selectVlan.value = datosPreCargados.id_seg;
                cargarIPs(datosPreCargados.id_seg, selectIp);
                
                if (!Array.from(selectIp.options).some(opt => opt.value == datosPreCargados.id_red)) {
                    const opt = document.createElement('option');
                    opt.value = datosPreCargados.id_red;
                    opt.textContent = datosPreCargados.ip_str || "IP Asignada";
                    selectIp.appendChild(opt);
                }
                selectIp.value = datosPreCargados.id_red;
            }
        }

        // Activamos los selectores interactivos por primera vez en esta tarjeta
        aplicarFuzzySearch(selectVlan);
        aplicarFuzzySearch(selectIp);

        selectTipo.addEventListener('change', function() {
            const valorSeleccionado = this.value;
            const todosLosTipos = Array.from(contenedorInterfaces.querySelectorAll('.select-tipo-conexion'))
                                       .filter(select => select !== this)
                                       .map(select => select.value);

            if (todosLosTipos.includes(valorSeleccionado)) {
                Swal.fire('Tipo Duplicado', `Ya existe una interfaz registrada como ${valorSeleccionado}.`, 'error');
                this.value = ''; 
                return;
            }

            inputMac.value = '';
            selectVlan.value = '';
            selectVlan.disabled = true;
            selectIp.innerHTML = '<option value="">-- Sin IP --</option>';
            selectIp.disabled = true;
            selectIp.classList.add('bg-light');
            if (typeof validarCampoUI === 'function') validarCampoUI(this);
            
            // Sincronizar el bloqueo visual de los buscadores
            aplicarFuzzySearch(selectVlan);
            aplicarFuzzySearch(selectIp);
        });

        inputMac.addEventListener('input', function() {
            formatearMAC(this);
            if (typeof validarCampoUI === 'function') validarCampoUI(this);
            
            // Solo habilitar si llegó a 17 caracteres Y ADEMÁS hay redes en el catálogo
            if (this.value.length === 17 && hayRedes) {
                selectVlan.disabled = false;
            } else {
                selectVlan.disabled = true;
                if (hayRedes) {
                    selectVlan.value = ''; // Solo vaciamos si no está bloqueado permanentemente
                }
                selectIp.innerHTML = '<option value="">-- Sin IP --</option>';
                selectIp.disabled = true;
                selectIp.classList.add('bg-light');
            }
            
            // Sincronizar bloqueo visual de los buscadores
            aplicarFuzzySearch(selectVlan);
            aplicarFuzzySearch(selectIp);
        });

        selectVlan.addEventListener('change', function() {
            cargarIPs(this.value, selectIp);
            if (typeof validarCampoUI === 'function') validarCampoUI(this);
            
            // Reconstruye el buscador de IPs porque se le inyectaron IPs nuevas
            aplicarFuzzySearch(selectIp);
        });

        btnEliminar.addEventListener('click', function() {
            nuevoItem.remove();
            if (btnAddInterfaz) btnAddInterfaz.disabled = false;
        });

        contenedorInterfaces.appendChild(nuevoItem);

        if (btnAddInterfaz && contenedorInterfaces.querySelectorAll('.interfaz-item').length >= 2) {
            btnAddInterfaz.disabled = true;
        }
    }

    if (btnAddInterfaz) {
        btnAddInterfaz.addEventListener('click', () => agregarInterfaz(null));
    }

    if (typeof dispositivoActual !== 'undefined' && dispositivoActual) {
        const interfacesGuardadas = dispositivoActual.interfaces || [];
        
        if (dispositivoActual.mac_ethernet) {
            const redEth = interfacesGuardadas.find(i => i.tipo_conexion === 'Ethernet');
            agregarInterfaz({
                tipo: 'Ethernet',
                mac: dispositivoActual.mac_ethernet,
                id_seg: redEth ? redEth.id_seg : null,
                id_red: redEth ? redEth.id_red : null,
                ip_str: redEth ? redEth.ip : null
            });
        }
        
        if (dispositivoActual.mac_wifi) {
            const redWifi = interfacesGuardadas.find(i => i.tipo_conexion === 'WiFi');
            agregarInterfaz({
                tipo: 'WiFi',
                mac: dispositivoActual.mac_wifi,
                id_seg: redWifi ? redWifi.id_seg : null,
                id_red: redWifi ? redWifi.id_red : null,
                ip_str: redWifi ? redWifi.ip : null
            });
        }
    }

    // ==========================================
    // 2. AUTO-COMPLETADO AMBICIOSO DESDE JS STORAGE (UX)
    // ==========================================
    const selectAsignado = document.querySelector('select[name="asig_po_id"]');
    const selectResponsable = document.querySelector('select[name="resp_po_id"]');
    const idPoEnMemoria = sessionStorage.getItem('po_preselect_asignado');

    if (idPoEnMemoria && selectAsignado) {
        if (selectAsignado.value === "") {
            selectAsignado.value = idPoEnMemoria;
            selectAsignado.classList.remove('border-secondary-subtle');
            selectAsignado.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'text-success', 'fw-bold');
            
            if (selectResponsable && selectResponsable.value === "") {
                selectResponsable.value = idPoEnMemoria;
                selectResponsable.classList.remove('border-secondary-subtle');
                selectResponsable.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'text-success', 'fw-bold');
            }
        }
        sessionStorage.removeItem('po_preselect_asignado');
    }

    aplicarFuzzySearch('select[name="resp_po_id"]');
    aplicarFuzzySearch('select[name="asig_po_id"]');
    aplicarFuzzySearch('select[name="id_dep"]');
    aplicarFuzzySearch('select[name="id_dom"]');
// ==========================================
    // 2.5 CASCADA MARCAS Y MODELOS
    // ==========================================
    const selectMarca = document.getElementById('selectMarca');
    const selectModelo = document.getElementById('selectModelo');

    if (selectMarca && selectModelo) {
        // Aplicamos el buscador Fuzzy a ambos
        aplicarFuzzySearch(selectMarca);
        aplicarFuzzySearch(selectModelo);

        selectMarca.addEventListener('change', async function() {
            const id_marca = this.value;
            
            // Limpiar modelos actuales
            selectModelo.innerHTML = '<option value="">-- Seleccionar Modelo --</option>';
            selectModelo.disabled = true;
            validarCampoUI(selectModelo);
            
            if(!id_marca) {
                aplicarFuzzySearch(selectModelo);
                return;
            }

            try {
                const response = await fetch('_controller/DispositivosAsync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ peticion: 'GET_MODELOS_POR_MARCA', id_marca: id_marca })
                });
                const data = await response.json();

                if(data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(mod => {
                        const opt = document.createElement('option');
                        opt.value = mod.id_modelo;
                        opt.textContent = mod.nom_modelo;
                        selectModelo.appendChild(opt);
                    });
                    selectModelo.disabled = false;
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Marca sin modelos',
                        text: 'Esta marca aún no tiene modelos registrados.',
                        confirmButtonColor: '#13322B',
                        footer: '<a href="marcas/" class="fw-bold text-guinda text-decoration-none"><i class="fas fa-plus"></i> Ir a añadir modelos</a>'
                    });
                }
                
                // Actualizar el UI del buscador con las nuevas opciones
                aplicarFuzzySearch(selectModelo);

            } catch (e) {
                console.error("Error obteniendo modelos:", e);
                Toast.fire({ icon: 'error', title: 'Error al cargar modelos.' });
            }
        });
    }
    // ==========================================
    // 3. LÓGICA DE CONSULTA DE CATÁLOGO (MODAL)
    // ==========================================
    if (btnGestionarCatalogo && modalCatalogoElement) {
        btnGestionarCatalogo.addEventListener('click', () => {
            tbodyCatalogo.innerHTML = '';
            
            if (catalogoServicios.length === 0) {
                tbodyCatalogo.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay servicios registrados en el catálogo.</td></tr>';
            } else {
                catalogoServicios.forEach(servicio => {
                    const versionesOficiales = catalogoVersiones.filter(v => v.id_serv == servicio.id_serv && String(v.obs) === "0");
                    const nombreVersionOficial = versionesOficiales.length > 0 
                        ? versionesOficiales.map(v => `<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle shadow-sm px-2 py-1 mb-1 me-1"><i class="fas fa-check-circle me-1"></i> ${v.ver}</span>`).join('') 
                        : '<span class="text-muted">Sin versión oficial</span>';
                                        
                    const otrasVersiones = catalogoVersiones
                        .filter(v => v.id_serv == servicio.id_serv && String(v.obs) === "1")
                        .map(v => `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle mb-1 me-1">${v.ver}</span>`)
                        .join('');

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="fw-bold text-dark ps-4">${servicio.nom}</td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle shadow-sm px-2 py-1">
                                <i class="fas fa-check-circle me-1"></i> ${nombreVersionOficial}
                            </span>
                        </td>
                        <td>${otrasVersiones || '<span class="text-muted small">Ninguna</span>'}</td>
                    `;
                    tbodyCatalogo.appendChild(tr);
                });
            }

            const modalCatalogo = new bootstrap.Modal(modalCatalogoElement);
            modalCatalogo.show();
        });
    }

    // ==========================================
    // 4. LÓGICA DE MÚLTIPLES SERVICIOS Y VERSIONES
    // ==========================================
    let contadorServiciosDinamicos = 0;

    function crearFilaServicio(idServicioSelec = '', idVersionSelec = '') {
        contadorServiciosDinamicos++;
        const uniqueIdServ = `dinamic-serv-${contadorServiciosDinamicos}`;
        const uniqueIdVer = `dinamic-ver-${contadorServiciosDinamicos}`;

        const fila = document.createElement('label');
        fila.className = 'list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-action bg-white';
        
        fila.innerHTML = `
            <div class="d-flex align-items-start gap-3 w-100 me-3">
                <div class="row g-2 w-100 align-items-center">
                    <div class="col-md-6">
                        <select id="${uniqueIdServ}" class="form-select select-servicio border-secondary-subtle focus-guinda" name="servicios_instalados[]" required>
                            <option value="" disabled selected>Seleccione software...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select id="${uniqueIdVer}" class="form-select select-version border-secondary-subtle focus-guinda" required>
                            <option value="" disabled selected>Seleccione versión...</option>
                        </select>
                        <div class="status-version mt-1 small"></div>
                    </div>
                </div>
            </div>
            <div class="text-end text-nowrap mt-1">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Quitar servicio">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;

        const selectServ = fila.querySelector(`#${uniqueIdServ}`);
        const selectVer = fila.querySelector(`#${uniqueIdVer}`);
        const statusVer = fila.querySelector('.status-version');
        const btnRemove = fila.querySelector('.btn-remove-row');

        selectServ.addEventListener('change', () => validarCampoUI(selectServ));
        selectVer.addEventListener('change', () => validarCampoUI(selectVer));

        selectServ.addEventListener('change', function() {
            selectVer.name = `versiones_instaladas[${this.value}]`;
        });

        catalogoServicios.forEach(s => {
            const option = document.createElement('option');
            option.value = s.id_serv;
            option.textContent = s.nom;
            
            const versionOficial = catalogoVersiones.find(v => v.id_serv == s.id_serv && String(v.obs) === "0");
            option.dataset.oficial = versionOficial ? versionOficial.id_ver : '';
            
            selectServ.appendChild(option);
        });

        const cargarVersiones = (idServicioActual, versionSeleccionada = '') => {
            selectVer.innerHTML = '<option value="" disabled selected>Seleccione versión...</option>';
            const versionesFiltradas = catalogoVersiones.filter(v => v.id_serv == idServicioActual);
            
            versionesFiltradas.forEach(v => {
                const option = document.createElement('option');
                option.value = v.id_ver;
                option.textContent = v.ver;
                selectVer.appendChild(option);
            });

            if (versionSeleccionada) {
                selectVer.value = versionSeleccionada;
            }
            
            validarVersion(selectServ, selectVer, statusVer, fila);
            aplicarFuzzySearch(`#${uniqueIdVer}`);
        };

        selectServ.addEventListener('change', () => cargarVersiones(selectServ.value));
        selectVer.addEventListener('change', () => validarVersion(selectServ, selectVer, statusVer, fila));
        btnRemove.addEventListener('click', () => fila.remove());

        if (idServicioSelec) {
            selectServ.value = idServicioSelec;
            selectVer.name = `versiones_instaladas[${idServicioSelec}]`;
            cargarVersiones(idServicioSelec, idVersionSelec);
        }

        const msjVacio = document.getElementById('mensaje-sin-servicios');
        if(msjVacio) msjVacio.remove();

        contenedorServicios.appendChild(fila);
        aplicarFuzzySearch(`#${uniqueIdServ}`);
    }

    function validarVersion(selectServ, selectVer, containerStatus, fila) {
        containerStatus.innerHTML = '';
        selectVer.classList.remove('is-valid', 'is-invalid', 'border-danger');
        fila.classList.remove('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');

        if (!selectServ.value || !selectVer.value) return;
        const objVersion = catalogoVersiones.find(v => String(v.id_ver) === String(selectVer.value));

        // Si la encontramos y su estado 'obs' es 0 (no obsoleta), es oficial/aceptada
        if (objVersion && String(objVersion.obs) === "0") {
            selectVer.classList.add('is-valid');
            containerStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Oficial (Al día)</span>';
        } else {
            selectVer.classList.add('is-invalid', 'border-danger');
            fila.classList.add('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');
            containerStatus.innerHTML = '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> Obsoleta/Distinta</span>';
        }
    }

    if(btnAddServicio) {
        btnAddServicio.addEventListener('click', () => crearFilaServicio());
    }

    // ==========================================
    // 5. ACTUALIZAR SERVICIO CARGADO (BOTÓN RÁPIDO)
    // ==========================================
    document.querySelectorAll('.btn-quitar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.list-group-item').remove();
        });
    });

    document.querySelectorAll('.btn-actualizar-serv').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('.list-group-item');
            const idServ = this.dataset.idServ;
            const inputVersion = row.querySelector(`input[name="versiones_instaladas[${idServ}]"]`);
            
            // Obtiene todas las aceptadas y se queda con la de mayor ID (la más reciente registrada)
            const versionesOficiales = catalogoVersiones.filter(v => v.id_serv == idServ && String(v.obs) === "0");
            const versionOficial = versionesOficiales.length > 0 
                ? versionesOficiales.reduce((max, v) => parseInt(v.id_ver) > parseInt(max.id_ver) ? v : max) 
                : null;            
            if(versionOficial && inputVersion) {
                inputVersion.value = versionOficial.id_ver;
                
                row.classList.remove('bg-danger', 'bg-opacity-10', 'border-danger', 'border-opacity-50');
                row.classList.add('bg-white');
                
                const titleH6 = row.querySelector('h6');
                titleH6.classList.remove('text-danger');
                titleH6.classList.add('text-dark');
                
                const iconDanger = titleH6.querySelector('.fa-exclamation-triangle');
                if(iconDanger) iconDanger.remove();

                const textSmall = row.querySelector('small');
                textSmall.classList.remove('text-danger');
                textSmall.classList.add('text-muted');
                textSmall.innerHTML = `Instalado: <strong>${versionOficial.ver}</strong>`;

                const textEndDiv = this.parentElement;
                textEndDiv.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-2 shadow-sm me-2">
                        <i class="fas fa-check-circle me-1"></i> Al día
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-quitar-serv" data-id-serv="${idServ}" title="Desinstalar servicio">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;

                textEndDiv.querySelector('.btn-quitar-serv').addEventListener('click', function() {
                    this.closest('.list-group-item').remove();
                });

                Toast.fire({ icon: 'success', title: 'Versión actualizada. Guarde los cambios.' });
            } else {
                Toast.fire({ icon: 'warning', title: 'Este servicio no tiene una versión oficial.' });
            }
        });
    });

    // ==========================================
    // 7. ENVÍO DEL FORMULARIO Y NAVEGACIÓN A ERRORES
    // ==========================================
    if (formDispositivo) {
        formDispositivo.addEventListener('submit', function (e) {
            e.preventDefault();

            let formularioValido = true;
            
            const allRequired = this.querySelectorAll('input[required], select[required], textarea[required]');
            allRequired.forEach(input => {
                validarCampoUI(input);
                if (input.value.trim() === '') {
                    formularioValido = false;
                }
            });

            // Recolectar datos de interfaces para validación
            const interfaces = document.querySelectorAll('.interfaz-item');
            let macsIngresadas = new Set();
            let ipsSeleccionadas = new Set();
            let errorInterfaz = false;
            let interfacesData = [];

            interfaces.forEach(item => {
                const tipo = item.querySelector('.select-tipo-conexion').value;
                const mac = item.querySelector('.input-mac').value;
                const ip = item.querySelector('.select-ip').value;

                if (!tipo || !mac) {
                    Swal.fire('Interfáz Incompleta', 'Debe seleccionar el tipo de conexión y escribir la MAC.', 'warning');
                    errorInterfaz = true;
                    return;
                }

                if (macsIngresadas.has(mac)) {
                    Swal.fire('Error de MAC', 'No puedes ingresar la misma dirección MAC en dos interfaces distintas.', 'error');
                    errorInterfaz = true;
                }
                macsIngresadas.add(mac);

                if (ip && ipsSeleccionadas.has(ip)) {
                    Swal.fire('Error de IP', 'No puedes asignar exactamente la misma dirección IP a la tarjeta Ethernet y a la WiFi.', 'error');
                    errorInterfaz = true;
                }
                if(ip) ipsSeleccionadas.add(ip);

                interfacesData.push({ tipo_conexion: tipo, mac: mac, id_red: ip });
            });

            if (!this.checkValidity() || !formularioValido || errorInterfaz) {
                e.stopPropagation();
                this.classList.add('was-validated');
                
                const primerError = this.querySelector('.is-invalid, :invalid');
                if (primerError) {
                    const tabPane = primerError.closest('.tab-pane');
                    if (tabPane) {
                        const tabId = tabPane.getAttribute('id');
                        const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                        if (tabButton) {
                            const tab = new bootstrap.Tab(tabButton);
                            tab.show();
                            setTimeout(() => primerError.focus(), 200);
                        }
                    }
                }
                
                if (!errorInterfaz) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos incompletos',
                        text: 'Por favor, revise los campos marcados en rojo antes de guardar.',
                        confirmButtonColor: '#9D2449'
                    });
                }
                return;
            }

            const formData = new FormData(this);
            const dataObj = Object.fromEntries(formData.entries());
            
            delete dataObj['tipo_conexion[]'];
            delete dataObj['mac[]'];
            delete dataObj['id_red[]'];
            
            dataObj.interfaces_red = interfacesData;

            const serviciosInputArray = formData.getAll('servicios_instalados[]');
            const uniqueServs = new Set(serviciosInputArray);
            if (uniqueServs.size !== serviciosInputArray.length) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Servicios Duplicados', 
                    text: 'Ha seleccionado el mismo software más de una vez. Quite los duplicados de la lista antes de guardar.', 
                    confirmButtonColor: '#9D2449' 
                });
                return; 
            }

            const asignacionesSoftware = [];
            serviciosInputArray.forEach(id_serv => {
                const id_ver_instalada = formData.get(`versiones_instaladas[${id_serv}]`);
                if(id_serv && id_ver_instalada) {
                    asignacionesSoftware.push({
                        id_serv: id_serv,
                        id_ver_instalada: id_ver_instalada
                    });
                }
            });

            dataObj.asignaciones_software = asignacionesSoftware;
            for(let key in dataObj) {
                if(key.includes('versiones_instaladas') || key.includes('servicios_instalados')) {
                    delete dataObj[key];
                }
            }

            const inputSub = document.querySelector('input[name="id_sub"]') || document.getElementById('id_sub_actual');
            if (inputSub) {
                dataObj.id_sub = inputSub.value;
            } else {
                const pathArray = window.location.pathname.split('/');
                const indexForm = pathArray.indexOf('formulario');
                if (indexForm !== -1 && pathArray[indexForm + 1]) {
                    dataObj.id_sub = pathArray[indexForm + 1];
                } else {
                    dataObj.id_sub = 3; 
                }
            }

            const btnSubmit = document.getElementById('btnGuardarDispositivo');
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
            btnSubmit.disabled = true;

            fetch('_controller/DispositivosAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataObj) 
            })
            .then(response => response.json())
            .then(data => {
                if (data.result === 1 || data.status === 'success') {
                    Swal.fire({ icon: 'success', timer:1500, title: '¡Guardado!', text: 'El dispositivo se guardó correctamente.', confirmButtonColor: '#9D2449' })
                    .then(() => window.location.href = 'dispositivos/');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error de Permisos o Guardado', text: data.msg || 'Ocurrió un problema.', confirmButtonColor: '#9D2449' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({ icon: 'error', title: 'Error de comunicación con el servidor.' });
            })
            .finally(() => {
                btnSubmit.innerHTML = originalBtnText;
                btnSubmit.disabled = false;
            });
        });
    }
    // ==========================================
    // 8. AUTO-RECARGA SILENCIOSA DE CATÁLOGOS (AL VOLVER A LA PESTAÑA)
    // ==========================================
    document.addEventListener('visibilitychange', async () => {
        // Cuando el usuario regresa a esta pestaña después de ir al catálogo en otra...
        if (document.visibilityState === 'visible') {
            try {
                const res = await fetch('_controller/DispositivosAsync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ peticion: 'RECARGAR_CATALOGOS' })
                });
                const response = await res.json();
                
                if (response.status === 'success') {
                    const data = response.data;
                    
                // ESCENARIO A: El formulario tenía alertas de catálogos faltantes
                const alertaFaltantes = document.querySelector('.alert-danger');

                if (alertaFaltantes) {
                    // Verificamos si los catálogos que faltaban ya tienen datos
                    const todoLleno = data.marcas.length > 0 && data.departamentos.length > 0 && data.dominios.length > 0;

                    if (todoLleno) {
                        // 1. Eliminamos visualmente la alerta
                        alertaFaltantes.remove();
                        
                        // 2. Habilitamos el fieldset para que el usuario pueda interactuar
                        const fieldset = document.querySelector('fieldset');
                        if (fieldset) fieldset.disabled = false;
                        
                        // 3. Quitamos la clase 'disabled' de las pestañas (solución anterior)
                        document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('disabled', 'opacity-50'));
                        
                        Toast.fire({ icon: 'success', title: 'Catálogos actualizados. Ya puede registrar.' });
                    }
                }

                    // ESCENARIO B: El usuario ya estaba llenando datos y le faltaba una marca/dominio
                    // Función auxiliar para reconstruir los selects sin que el usuario lo note
                    const actualizarSelect = (selector, arrayDatos, valProp, textFn, defaultText) => {
                        const select = document.querySelector(selector);
                        if (!select) return;
                        
                        const valorAnterior = select.value;
                        select.innerHTML = `<option value="">${defaultText}</option>`;
                        
                        arrayDatos.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item[valProp];
                            opt.textContent = textFn(item);
                            select.appendChild(opt);
                        });
                        
                        // Si el usuario ya tenía algo seleccionado, se lo respetamos para que no pierda su progreso
                        if (arrayDatos.some(item => String(item[valProp]) === String(valorAnterior))) {
                            select.value = valorAnterior;
                        }
                        
                        // Reconstruimos la barra de búsqueda Fuzzy que hicimos anteriormente
                        if (typeof aplicarFuzzySearch === 'function') {
                            aplicarFuzzySearch(select);
                        }
                    };

                    // 1. Actualizamos Dominios
                    actualizarSelect('#id_dom', data.dominios, 'id_dom', item => item.nom_dom, '-- Seleccione un dominio --');
                    
                    // 2. Actualizamos Marcas
                    actualizarSelect('#selectMarca', data.marcas, 'id_marca', item => item.nom_marca, '-- Seleccionar Marca --');
                    
                    // 3. Actualizamos Departamentos (Soporta concatenar la clave y el nombre)
                    actualizarSelect('select[name="id_dep"]', data.departamentos, 'id_dep', item => item.clave_dep ? `${item.clave_dep} - ${item.nom_dep}` : item.nom_dep, '-- Seleccionar Departamento --');
                }
            } catch (error) {
                console.error("Error en auto-recarga de catálogos:", error);
            }
        }
    });

});