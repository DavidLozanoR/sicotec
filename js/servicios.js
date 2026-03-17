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

    // ==========================================
    // 1. GESTIÓN DEL CATÁLOGO DE SERVICIOS
    // ==========================================

    // Diccionario extendido de iconos FontAwesome 6+
    const iconosDisponibles = [
        // --- GENERAL & SISTEMA ---
        { val: 'fas fa-cube text-dark', text: 'Cubo (Software General)' },
        { val: 'fas fa-cogs text-secondary', text: 'Configuración / Sistema' },
        { val: 'fas fa-terminal text-dark', text: 'Consola / Scripts' },
        { val: 'fas fa-microchip text-danger', text: 'Procesador / Driver' },
        { val: 'fas fa-memory text-warning', text: 'Memoria / Optimización' },
        { val: 'fas fa-hard-drive text-secondary', text: 'Almacenamiento / Disco' },

        // --- SISTEMAS OPERATIVOS ---
        { val: 'fab fa-windows text-primary', text: 'Windows' },
        { val: 'fab fa-apple text-secondary', text: 'macOS / iOS' },
        { val: 'fab fa-linux text-dark', text: 'Linux (General)' },
        { val: 'fab fa-ubuntu text-orange', text: 'Ubuntu' },
        { val: 'fab fa-redhat text-danger', text: 'RedHat / Fedora' },
        { val: 'fab fa-android text-success', text: 'Android' },

        // --- OFIMÁTICA Y DOCUMENTOS ---
        { val: 'fas fa-file-word text-primary', text: 'Procesador de Textos (Word)' },
        { val: 'fas fa-file-excel text-success', text: 'Hojas de Cálculo (Excel)' },
        { val: 'fas fa-file-powerpoint text-danger', text: 'Presentaciones (PPT)' },
        { val: 'fas fa-file-pdf text-danger', text: 'Lector PDF' },
        { val: 'fas fa-file-archive text-warning', text: 'Compresor (ZIP/RAR)' },
        { val: 'fas fa-envelope text-primary', text: 'Correo / Outlook' },

        // --- SEGURIDAD Y REDES ---
        { val: 'fas fa-shield-alt text-success', text: 'Antivirus / Firewall' },
        { val: 'fas fa-lock text-warning', text: 'Cifrado / Seguridad' },
        { val: 'fas fa-key text-secondary', text: 'Licencias / Llaves' },
        { val: 'fas fa-network-wired text-primary', text: 'Red / Conectividad' },
        { val: 'fas fa-wifi text-primary', text: 'Wi-Fi / Inalámbrico' },
        { val: 'fas fa-cloud text-info', text: 'Nube / Almacenamiento' },
        { val: 'fas fa-vpn-lock text-info', text: 'VPN / Acceso Remoto' },

        // --- DESARROLLO Y BASES DE DATOS ---
        { val: 'fas fa-code text-primary', text: 'Programación / IDE' },
        { val: 'fas fa-database text-secondary', text: 'Base de Datos (SQL)' },
        { val: 'fas fa-laptop-code text-dark', text: 'Desarrollo Web' },
        { val: 'fab fa-git-alt text-danger', text: 'Control de Versiones (Git)' },
        { val: 'fab fa-docker text-primary', text: 'Contenedores (Docker)' },
        { val: 'fab fa-php text-secondary', text: 'Entorno PHP' },

        // --- COMUNICACIÓN Y WEB ---
        { val: 'fas fa-globe text-info', text: 'Navegador / Web' },
        { val: 'fab fa-chrome text-success', text: 'Google Chrome' },
        { val: 'fab fa-firefox-browser text-warning', text: 'Firefox' },
        { val: 'fab fa-edge text-primary', text: 'MS Edge' },
        { val: 'fas fa-video text-primary', text: 'Videoconferencia' },
        { val: 'fas fa-comments text-info', text: 'Mensajería / Chat' },

        // --- DISEÑO Y MULTIMEDIA ---
        { val: 'fas fa-paint-brush text-danger', text: 'Diseño Gráfico' },
        { val: 'fas fa-camera text-dark', text: 'Fotografía / Imagen' },
        { val: 'fas fa-play-circle text-primary', text: 'Reproductor Multimedia' },
        { val: 'fas fa-volume-up text-secondary', text: 'Audio / Sonido' },

        // --- HERRAMIENTAS ESPECÍFICAS ---
        { val: 'fas fa-calculator text-dark', text: 'Calculadora / Contabilidad' },
        { val: 'fas fa-print text-secondary', text: 'Impresión / Escáner' },
        { val: 'fas fa-tools text-secondary', text: 'Mantenimiento / Herramientas' },
        { val: 'fas fa-chart-pie text-success', text: 'Estadística / Reportes' },

                // --- INFRAESTRUCTURA Y SERVIDORES ---
        { val: 'fas fa-server text-secondary', text: 'Servidor Físico / VPS' },
        { val: 'fas fa-ethernet text-dark', text: 'Puerto LAN / Ethernet' },
        { val: 'fas fa-broadcast-tower text-danger', text: 'Antena / Emisión' },
        { val: 'fas fa-satellite-dish text-info', text: 'Enlace Satelital' },

        // --- HARDWARE Y PERIFÉRICOS ---
        { val: 'fas fa-keyboard text-secondary', text: 'Teclado / Entrada' },
        { val: 'fas fa-mouse text-dark', text: 'Mouse / Puntero' },
        { val: 'fas fa-desktop text-primary', text: 'Monitor / Pantalla' },
        { val: 'fas fa-headset text-info', text: 'Soporte / Diadema' },
        { val: 'fas fa-battery-three-quarters text-success', text: 'Energía / Batería' },
        { val: 'fas fa-plug text-warning', text: 'Conector / Corriente' },

        // --- INTELIGENCIA ARTIFICIAL Y CIENCIA ---
        { val: 'fas fa-brain text-info', text: 'IA / Machine Learning' },
        { val: 'fas fa-robot text-secondary', text: 'Bot / Automatización' },
        { val: 'fas fa-flask text-success', text: 'I+D / Laboratorio' },
        { val: 'fas fa-dna text-danger', text: 'Bioinformática / Genética' },

        // --- FINANZAS Y COMERCIO ---
        { val: 'fas fa-credit-card text-primary', text: 'Pasarela de Pago' },
        { val: 'fas fa-shopping-cart text-success', text: 'E-commerce / Tienda' },
        { val: 'fas fa-wallet text-warning', text: 'Billetera Digital' },
        { val: 'fas fa-money-bill-wave text-success', text: 'Facturación / Cobros' },

        // --- EDUCACIÓN Y COLABORACIÓN ---
        { val: 'fas fa-graduation-cap text-dark', text: 'Capacitación / LMS' },
        { val: 'fas fa-book text-info', text: 'Documentación / Manuales' },
        { val: 'fas fa-users text-primary', text: 'Recursos Humanos / Equipo' },
        { val: 'fas fa-calendar-alt text-danger', text: 'Agenda / Calendario' },

        // --- LOGOTIPOS TECNOLÓGICOS ADICIONALES ---
        { val: 'fab fa-python text-primary', text: 'Python' },
        { val: 'fab fa-js text-warning', text: 'JavaScript' },
        { val: 'fab fa-aws text-orange', text: 'Amazon Web Services' },
        { val: 'fab fa-google text-success', text: 'Google Services' },

                // --- SEGURIDAD AVANZADA ---
        { val: 'fas fa-fingerprint text-primary', text: 'Biometría / Huella' },
        { val: 'fas fa-user-shield text-success', text: 'Gestión de Identidades' },
        { val: 'fas fa-eye text-secondary', text: 'Monitoreo / Auditoría' },
        { val: 'fas fa-file-contract text-dark', text: 'Políticas / Cumplimiento' },
        { val: 'fas fa-user-secret text-dark', text: 'Modo Incógnito / Privacidad' },
        { val: 'fas fa-skull-crossbones text-danger', text: 'Malware / Amenazas' },

        // --- DESARROLLO Y DEVOPS ---
        { val: 'fab fa-js-square text-warning', text: 'JavaScript / Frontend' },
        { val: 'fab fa-node-js text-success', text: 'Node.js / Backend' },
        { val: 'fab fa-react text-info', text: 'React Framework' },
        { val: 'fab fa-angular text-danger', text: 'Angular Framework' },
        { val: 'fab fa-vuejs text-success', text: 'Vue.js Framework' },
        { val: 'fas fa-vial text-warning', text: 'Testing / QA' },
        { val: 'fas fa-box-open text-secondary', text: 'Paquetes / Librerías' },
        { val: 'fas fa-infinity text-primary', text: 'CI/CD / DevOps' },
        { val: 'fas fa-layer-group text-info', text: 'Stack / Arquitectura' },

        // --- COMUNICACIÓN Y COLABORACIÓN ---
        { val: 'fab fa-slack text-danger', text: 'Slack / Canales' },
        { val: 'fab fa-discord text-primary', text: 'Discord / Comunidad' },
        { val: 'fab fa-skype text-info', text: 'Skype / Llamadas' },
        { val: 'fab fa-whatsapp text-success', text: 'WhatsApp Business' },
        { val: 'fab fa-telegram text-info', text: 'Telegram' },
        { val: 'fas fa-at text-secondary', text: 'Menciones / Etiquetas' },
        { val: 'fas fa-bullhorn text-warning', text: 'Anuncios / Difusión' },

        // --- GESTIÓN DE PROYECTOS Y TAREAS ---
        { val: 'fas fa-tasks text-primary', text: 'Gestión de Tareas' },
        { val: 'fas fa-clipboard-list text-success', text: 'Checklist / Inventario' },
        { val: 'fas fa-project-diagram text-info', text: 'Flujo de Trabajo' },
        { val: 'fas fa-thumbtack text-danger', text: 'Notas Fijadas' },
        { val: 'fas fa-hourglass-half text-warning', text: 'Control de Tiempos' },
        { val: 'fas fa-history text-secondary', text: 'Logs / Historial' },

        // --- MULTIMEDIA Y CREATIVIDAD ---
        { val: 'fas fa-clapperboard text-dark', text: 'Edición de Video' },
        { val: 'fas fa-music text-primary', text: 'Composición Musical' },
        { val: 'fas fa-microphone text-danger', text: 'Podcast / Grabación' },
        { val: 'fas fa-pen-nib text-info', text: 'Ilustración Vectorial' },
        { val: 'fas fa-palette text-warning', text: 'Temas / Colores' },
        { val: 'fas fa-crop-alt text-secondary', text: 'Recorte / Edición' },
        { val: 'fas fa-vector-square text-primary', text: 'Modelado 3D' },

        // --- E-COMMERCE Y LOGÍSTICA ---
        { val: 'fas fa-box text-warning', text: 'Paquetería / Envíos' },
        { val: 'fas fa-truck text-primary', text: 'Logística / Flota' },
        { val: 'fas fa-barcode text-dark', text: 'Escáner de Barras' },
        { val: 'fas fa-qrcode text-dark', text: 'Código QR' },
        { val: 'fas fa-tag text-success', text: 'Ofertas / Descuentos' },
        { val: 'fas fa-store text-info', text: 'Punto de Venta (POS)' },

        // --- NUBE Y VIRTUALIZACIÓN ---
        { val: 'fab fa-google-drive text-success', text: 'Google Drive' },
        { val: 'fab fa-dropbox text-primary', text: 'Dropbox' },
        { val: 'fas fa-cloud-upload-alt text-info', text: 'Subida a la Nube' },
        { val: 'fas fa-cloud-download-alt text-primary', text: 'Descarga de Nube' },
        { val: 'fas fa-clone text-secondary', text: 'Instancias / Réplicas' },

        // --- ANALÍTICA Y DATOS ---
        { val: 'fas fa-chart-line text-success', text: 'Tendencias / Análisis' },
        { val: 'fas fa-chart-bar text-primary', text: 'Métricas de Rendimiento' },
        { val: 'fas fa-search-dollar text-success', text: 'Auditoría Financiera' },
        { val: 'fas fa-funnel-dollar text-warning', text: 'Embudo de Ventas' },
        { val: 'fas fa-table text-secondary', text: 'Tablas de Datos' },

        // --- SOPORTE Y USUARIO ---
        { val: 'fas fa-headset text-danger', text: 'Soporte Técnico' },
        { val: 'fas fa-question-circle text-info', text: 'Centro de Ayuda' },
        { val: 'fas fa-user-plus text-success', text: 'Registro de Usuario' },
        { val: 'fas fa-user-cog text-secondary', text: 'Perfil de Usuario' },
        { val: 'fas fa-ticket-alt text-warning', text: 'Tickets de Soporte' },
    ];


const $selectIcono = $('#serv_icono');
    const $previewIcono = $('#preview-icono');

    // Inyectar buscador antes del select
    if (!$('#busquedaIcono').length) {
        $selectIcono.before('<input type="text" id="busquedaIcono" class="form-control form-control-sm mb-1" placeholder="Buscar icono por nombre..." autocomplete="off">');
    }

    // Bloquear el envío del formulario al dar Enter en la búsqueda
    $(document).on('keydown', '#busquedaIcono', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

    // Función de filtrado dinámico
    function filtrarIconos(busqueda) {
        const query = busqueda.toLowerCase();
        $selectIcono.find('option').each(function() {
            const label = $(this).text().toLowerCase();
            const coincide = label.indexOf(query) > -1;
            $(this).prop('disabled', !coincide).toggle(coincide);
        });

        // Si la opción seleccionada se oculta, saltar a la primera visible
        if ($selectIcono.find('option:selected').is(':hidden')) {
            $selectIcono.find('option:visible:first').prop('selected', true).trigger('change');
        }
    }

    $('#busquedaIcono').on('input', function() {
        filtrarIconos($(this).val());
    });

    function popularIconos() {
        $selectIcono.empty();
        iconosDisponibles.forEach(icono => {
            $selectIcono.append(`<option value="${icono.val}">${icono.text}</option>`);
        });
    }

    popularIconos();

    // Actualizar preview en tiempo real
    $selectIcono.on('change', function() {
        const clase = $(this).val();
        $previewIcono.html(`<i class="${clase} fs-5"></i>`);
    });

    // Acciones del botón NUEVO
    $('#btnNuevoServicio').on('click', function() {
        $form[0].reset();
        $('#busquedaIcono').val('');
        filtrarIconos('');
        $('#serv_id').val('');
        $('#tituloModalServ').html('<i class="fas fa-plus-circle me-2 text-guinda"></i>Nuevo Servicio');
        $('#bloque_version_inicial').show();
        $('#serv_ver_ini').prop('required', true);
        
        $selectIcono.val(iconosDisponibles[0].val).trigger('change');
        $form.removeClass('was-validated');
        modalForm.show();
    });

    // Acciones del botón EDITAR
    $('.btn-editar-serv').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        $('#busquedaIcono').val('');
        filtrarIconos('');
        
        $('#serv_id').val($btn.data('id'));
        $('#serv_tipo').val($btn.data('tipo'));
        $('#serv_nom').val($btn.data('nom'));
        $('#serv_especs').val($btn.data('especs'));
        
        let iconoBD = $btn.data('icono');
        if (iconoBD && $selectIcono.find(`option[value="${iconoBD}"]`).length === 0) {
            $selectIcono.prepend(`<option value="${iconoBD}">Icono Actual</option>`);
        }
        
        $selectIcono.val(iconoBD || iconosDisponibles[0].val).trigger('change');
        
        $('#tituloModalServ').html('<i class="fas fa-edit me-2 text-secondary"></i>Editar Servicio');
        $('#bloque_version_inicial').hide(); 
        $('#serv_ver_ini').prop('required', false);
        
        $form.removeClass('was-validated');
        modalForm.show();
    });
    });