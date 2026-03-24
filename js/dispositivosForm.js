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
    function validarCampoUI(campo) {
        if (campo.required && campo.value.trim() === '') {
            campo.classList.remove('is-valid');
            campo.classList.add('is-invalid');
        } else {
            if (campo.value.trim() !== '') {
                campo.classList.remove('is-invalid');
                campo.classList.add('is-valid');
            } else {
                campo.classList.remove('is-invalid', 'is-valid');
            }
        }
    }

    if (formDispositivo) {
        const formInputs = formDispositivo.querySelectorAll('input[required], select[required], textarea[required]');
        formInputs.forEach(input => {
            input.addEventListener('blur', () => validarCampoUI(input));
            input.addEventListener('change', () => validarCampoUI(input));
            input.addEventListener('input', () => validarCampoUI(input));
        });

        document.querySelectorAll('.btn-siguiente').forEach(btn => {
            btn.addEventListener('click', function() {
                const pestanaActual = this.closest('.tab-pane');
                const nextTabId = this.getAttribute('data-next');
                let isValid = true;

                const inputsActuales = pestanaActual.querySelectorAll('input[required], select[required], textarea[required]');
                inputsActuales.forEach(input => {
                    validarCampoUI(input);
                    if (input.classList.contains('is-invalid') || input.value.trim() === '') {
                        isValid = false;
                    }
                });

                if (isValid) {
                    const tabTarget = document.getElementById(nextTabId);
                    if(tabTarget) {
                        const nextTab = new bootstrap.Tab(tabTarget);
                        nextTab.show();
                    }
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Información Incompleta',
                        text: 'Por favor, llene correctamente todos los campos obligatorios de esta sección antes de avanzar.',
                        confirmButtonColor: '#9D2449'
                    });
                }
            });
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
        
        if (typeof redesDisponibles === 'undefined') return;

        const grupos = {};
        redesDisponibles.forEach(red => {
            const nombreGrupo = red.nom_vlan || red.nom_dep || 'General / Sin VLAN';
            if (!grupos[nombreGrupo]) grupos[nombreGrupo] = [];
            grupos[nombreGrupo].push(red);
        });

        for (const [grupo, redes] of Object.entries(grupos)) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = grupo;
            
            const vlansUnicas = [...new Set(redes.map(r => r.id_seg))];
            vlansUnicas.forEach(id_seg => {
                if (!id_seg) return;
                const opt = document.createElement('option');
                opt.value = id_seg;
                opt.textContent = `Segmento de Red (ID: ${id_seg})`;
                optgroup.appendChild(opt);
            });
            selectVlan.appendChild(optgroup);
        }
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

    function agregarInterfaz() {
        if (!contenedorInterfaces || !templateInterfaz) return;
        
        const numInterfaces = contenedorInterfaces.querySelectorAll('.interfaz-item').length;
        if (numInterfaces >= 2) {
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

        cargarVLANs(selectVlan);

        // Validación de tipo duplicado
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
            validarCampoUI(this);
        });

        // Formateo de MAC y habilitación de VLAN
        inputMac.addEventListener('input', function() {
            formatearMAC(this);
            validarCampoUI(this);
            
            if (this.value.length === 17) {
                selectVlan.disabled = false;
            } else {
                selectVlan.disabled = true;
                selectVlan.value = '';
                selectIp.innerHTML = '<option value="">-- Sin IP --</option>';
                selectIp.disabled = true;
                selectIp.classList.add('bg-light');
            }
        });

        // Cambio de VLAN carga IPs
        selectVlan.addEventListener('change', function() {
            cargarIPs(this.value, selectIp);
            validarCampoUI(this);
        });

        // Eliminar interfaz
        btnEliminar.addEventListener('click', function() {
            nuevoItem.remove();
            btnAddInterfaz.disabled = false;
        });

        contenedorInterfaces.appendChild(nuevoItem);

        if (contenedorInterfaces.querySelectorAll('.interfaz-item').length >= 2) {
            btnAddInterfaz.disabled = true;
        }
    }

    if (btnAddInterfaz) {
        btnAddInterfaz.addEventListener('click', agregarInterfaz);
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
                    const versionOficial = catalogoVersiones.find(v => v.id_serv == servicio.id_serv && String(v.obs) === "0");
                    const nombreVersionOficial = versionOficial ? versionOficial.ver : 'Sin versión oficial';
                    
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
            const isFuzzyActive = selectVer.style.display === 'none';
            const $fuzzyContainer = selectVer.nextElementSibling;
            
            if (isFuzzyActive && $fuzzyContainer && $fuzzyContainer.classList.contains('fuzzy-container')) {
                $fuzzyContainer.remove();
                selectVer.style.display = ''; 
            }

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

        const optionServ = selectServ.options[selectServ.selectedIndex];
        const idVerOficial = optionServ.dataset.oficial;

        if (selectVer.value === idVerOficial) {
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
            
            const versionOficial = catalogoVersiones.find(v => v.id_serv == idServ && String(v.obs) === "0");
            
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
    // 6. BUSCADOR FUZZY PARA SELECTS
    // ==========================================
    function aplicarFuzzySearch(selector) {
        const selectOriginal = document.querySelector(selector);
        if (!selectOriginal) return;

        if (selectOriginal.nextElementSibling && selectOriginal.nextElementSibling.classList.contains('fuzzy-container')) return;

        selectOriginal.style.display = 'none';

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

        Array.from(selectOriginal.options).forEach(opt => {
            if (opt.value === '') return;

            const li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            li.textContent = opt.text;
            li.dataset.value = opt.value;
            listaDesplegable.appendChild(li);

            if (opt.selected) {
                inputBuscador.value = opt.text;
                iconClear.style.display = 'block';
            }
        });

        container.appendChild(inputBuscador);
        container.appendChild(iconClear);
        container.appendChild(listaDesplegable);
        selectOriginal.parentNode.insertBefore(container, selectOriginal.nextSibling);

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

        inputBuscador.addEventListener('focus', () => {
            listaDesplegable.classList.remove('d-none');
            filtrarResultados(inputBuscador.value);
        });

        inputBuscador.addEventListener('input', (e) => {
            const valor = e.target.value;
            filtrarResultados(valor);
            iconClear.style.display = valor ? 'block' : 'none';
            selectOriginal.value = ''; 
        });

        listaDesplegable.addEventListener('click', (e) => {
            const li = e.target.closest('li');
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

    aplicarFuzzySearch('select[name="resp_po_id"]');
    aplicarFuzzySearch('select[name="asig_po_id"]');

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
            
            // Limpiar los arreglos basura que genera Object.fromEntries con selects dinámicos
            delete dataObj['tipo_conexion[]'];
            delete dataObj['mac[]'];
            delete dataObj['id_red[]'];
            
            // Inyectamos el arreglo de objetos estructurado de nuestras interfaces
            dataObj.interfaces_red = interfacesData;

            // Escudo Servicios Duplicados
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

            const btnSubmit = document.getElementById('btnGuardarDispositivo');
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
            btnSubmit.disabled = true;

            // === ENVÍO FINAL ===
            fetch('_controller/DispositivosAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataObj)
            })
            .then(response => response.json())
            .then(data => {
                if (data.result === 1 || data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'El dispositivo se guardó correctamente.', confirmButtonColor: '#9D2449' })
                    .then(() => window.location.href = 'dispositivos/');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'Ocurrió un problema.', confirmButtonColor: '#9D2449' });
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
    // 1. LÓGICA DINÁMICA DE INTERFACES DE RED (VLAN -> IP)
    // ==========================================

    function formatearMAC(input) {
        let value = input.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
        let formatted = value.match(/.{1,2}/g);
        input.value = formatted ? formatted.join(':').substring(0, 17) : '';
    }

    function cargarVLANs(selectVlan) {
        selectVlan.innerHTML = '<option value="" selected disabled>-- Seleccione una red --</option>';
        if (typeof redesDisponibles === 'undefined') return;

        const grupos = {};
        redesDisponibles.forEach(red => {
            const nombreGrupo = red.nom_vlan || 'General / Sin VLAN';
            if (!grupos[nombreGrupo]) grupos[nombreGrupo] = [];
            grupos[nombreGrupo].push(red);
        });

        for (const [grupo, redes] of Object.entries(grupos)) {
            const optgroup = document.createElement('optgroup');
            optgroup.label = grupo;
            
            const vlansUnicas = [...new Set(redes.map(r => r.id_seg))];
            vlansUnicas.forEach(id_seg => {
                if (!id_seg) return;
                const opt = document.createElement('option');
                opt.value = id_seg;
                opt.textContent = `Segmento de Red (ID: ${id_seg})`;
                optgroup.appendChild(opt);
            });
            selectVlan.appendChild(optgroup);
        }
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

    // MODIFICADO: Ahora puede recibir un objeto con datos precargados para el modo EDICIÓN
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

        cargarVLANs(selectVlan);

        // Si es edición y hay datos guardados, pre-llenamos el bloque
        if (datosPreCargados) {
            selectTipo.value = datosPreCargados.tipo;
            inputMac.value = datosPreCargados.mac;
            selectVlan.disabled = false;

            if (datosPreCargados.id_seg && datosPreCargados.id_red) {
                selectVlan.value = datosPreCargados.id_seg;
                // Al cargar las IPs precargadas, nos aseguramos de incluir también la IP actual 
                // por si la red ya está "llena" y no viene en las disponibles
                cargarIPs(datosPreCargados.id_seg, selectIp);
                
                // Si la IP no está en las disponibles (porque ya es suya), la forzamos visualmente
                if (!Array.from(selectIp.options).some(opt => opt.value == datosPreCargados.id_red)) {
                    const opt = document.createElement('option');
                    opt.value = datosPreCargados.id_red;
                    opt.textContent = datosPreCargados.ip_str || "IP Asignada";
                    selectIp.appendChild(opt);
                }
                
                selectIp.value = datosPreCargados.id_red;
            }
        }

        // Validación de tipo duplicado
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
        });

        // Formateo de MAC y habilitación de VLAN
        inputMac.addEventListener('input', function() {
            formatearMAC(this);
            if (typeof validarCampoUI === 'function') validarCampoUI(this);
            
            if (this.value.length === 17) {
                selectVlan.disabled = false;
            } else {
                selectVlan.disabled = true;
                selectVlan.value = '';
                selectIp.innerHTML = '<option value="">-- Sin IP --</option>';
                selectIp.disabled = true;
                selectIp.classList.add('bg-light');
            }
        });

        // Cambio de VLAN carga IPs
        selectVlan.addEventListener('change', function() {
            cargarIPs(this.value, selectIp);
            if (typeof validarCampoUI === 'function') validarCampoUI(this);
        });

        // Eliminar interfaz
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

    // ==========================================
    // NUEVO: PRE-CARGAR INTERFACES EN MODO EDICIÓN
    // ==========================================
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
    
});