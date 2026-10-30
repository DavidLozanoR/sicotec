$(document).ready(function() {

    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    const $inputBusqueda = $('#buscarDispositivo');
    const $filtroAsignacion = $('#filtroAsignacion');
    const $filtroDepartamento = $('#filtroDepartamento');
    const $selectRegistros = $('#selectorRegistros'); 
    
    const $paginacionContenedor = $('#paginacionTabla'); 
    const $contadorRegistros = $('#contador-registros');
    
    const $filasDispositivos = $('table tbody tr').filter(function() {
        return $(this).find('td').length > 1; 
    });

    let paginaActual = 1;

    function actualizarTabla() {
        if (!$inputBusqueda.length) return;

        const textoBusqueda = quitarAcentos($inputBusqueda.val().toLowerCase().trim());
        const palabrasBusqueda = textoBusqueda.split(/\s+/); 
        
        const valAsignacion = $filtroAsignacion.val();
        const valDepartamento = $filtroDepartamento.val();
        const limite = $selectRegistros.val();

        let filasFiltradas = [];

        $filasDispositivos.each(function() {
            const $fila = $(this);
            
            // Extraemos la información de los atributos
            const softwareOculto = $fila.attr('data-software') || '';
            const estadoAsig = $fila.attr('data-estado');
            const idDep = $fila.attr('data-dep');
            
            const textoFila = quitarAcentos($fila.text().replace(/\s+/g, ' ') + ' ' + softwareOculto).toLowerCase();

            // 1. Filtrado por texto libre
            let coincideBusqueda = true;
            for (let i = 0; i < palabrasBusqueda.length; i++) {
                if (!textoFila.includes(palabrasBusqueda[i])) {
                    coincideBusqueda = false;
                    break;
                }
            }

            // 2. Filtrado por Asignación
            let coincideAsignacion = (valAsignacion === 'todos' || estadoAsig === valAsignacion);

            // 3. Filtrado por Departamento
            let coincideDepartamento = (valDepartamento === 'todos' || idDep === valDepartamento);

            // Si pasa las 3 pruebas, se queda en la lista visible
            if (coincideBusqueda && coincideAsignacion && coincideDepartamento) {
                filasFiltradas.push(this);
            } else {
                $fila.hide();
            }
        });

        const totalFiltrados = filasFiltradas.length;

        if (totalFiltrados === 0 && $filasDispositivos.length > 0) {
            if ($('#sin-resultados').length === 0) {
                $('table tbody').append(`
                    <tr id="sin-resultados">
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-filter fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No se encontraron equipos con esa combinación de filtros.</p>
                        </td>
                    </tr>
                `);
            }
            $paginacionContenedor.empty();
            $contadorRegistros.text('Mostrando 0 registros');
            return;
        } else {
            $('#sin-resultados').remove();
        }

        if (limite !== 'all') {
            const limitNum = parseInt(limite);
            const totalPaginas = Math.ceil(totalFiltrados / limitNum);
            
            if (paginaActual > totalPaginas) paginaActual = 1;

            const inicio = (paginaActual - 1) * limitNum;
            const fin = inicio + limitNum;

            $(filasFiltradas).each(function(index) {
                if (index >= inicio && index < fin) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            dibujarPaginacion(totalPaginas, paginaActual);
            $contadorRegistros.text(`Mostrando ${inicio + 1} a ${Math.min(fin, totalFiltrados)} de ${totalFiltrados} registros`);
        } else {
            $(filasFiltradas).show();
            $paginacionContenedor.empty();
            $contadorRegistros.text(`Mostrando todos los ${totalFiltrados} registros`);
        }
    }

    function dibujarPaginacion(totalPaginas, activa) {
        $paginacionContenedor.empty();
        if (totalPaginas <= 1) return;

        $paginacionContenedor.append(`
            <li class="page-item ${activa === 1 ? 'disabled' : ''}">
                <button class="page-link text-guinda btn-paginacion" data-page="${activa - 1}">Anterior</button>
            </li>
        `);

        for (let i = 1; i <= totalPaginas; i++) {
            if (i === activa) {
                $paginacionContenedor.append(`
                    <li class="page-item active">
                        <button class="page-link bg-guinda border-guinda text-white btn-paginacion" data-page="${i}">${i}</button>
                    </li>
                `);
            } else {
                $paginacionContenedor.append(`
                    <li class="page-item">
                        <button class="page-link text-guinda btn-paginacion" data-page="${i}">${i}</button>
                    </li>
                `);
            }
        }

        $paginacionContenedor.append(`
            <li class="page-item ${activa === totalPaginas ? 'disabled' : ''}">
                <button class="page-link text-guinda btn-paginacion" data-page="${activa + 1}">Siguiente</button>
            </li>
        `);
    }

    // Eventos (Listeners) que disparan el recálculo de la tabla en vivo
    $inputBusqueda.on('keyup', () => { paginaActual = 1; actualizarTabla(); });
    $selectRegistros.on('change', () => { paginaActual = 1; actualizarTabla(); });
    $filtroAsignacion.on('change', () => { paginaActual = 1; actualizarTabla(); });
    $filtroDepartamento.on('change', () => { paginaActual = 1; actualizarTabla(); });

    $(document).on('click', '.btn-paginacion', function(e) {
        e.preventDefault();
        const $li = $(this).parent();
        if (!$li.hasClass('disabled') && !$li.hasClass('active')) {
            paginaActual = parseInt($(this).data('page'));
            actualizarTabla();
        }
    });

    if ($selectRegistros.length) {
        actualizarTabla();
    }

    // ==========================================
    // ELIMINAR DISPOSITIVO (Baja Definitiva)
    // ==========================================
    $(document).on('click', '.btn-eliminar-disp', async function(e) {
        e.preventDefault();
        const idDisp = $(this).data('id');
        const idSub = $('#main-dispositivos-container').data('id-sub');

        const paso1 = await Swal.fire({
            title: '¿Iniciar baja del dispositivo?',
            text: "Esta acción liberará la IP asignada, eliminará el registro de software instalado y desvinculará a los responsables.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Paso 1: Entendido, continuar',
            cancelButtonText: 'Cancelar'
        });

        if (!paso1.isConfirmed) return;

        const paso2 = await Swal.fire({
            title: 'Revisión de Impacto',
            text: "Se generará un respaldo en la bitácora con los datos actuales para posible recuperación. La eliminación del registro físico es irreversible.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Paso 2: Estoy seguro',
            cancelButtonText: 'Cancelar'
        });

        if (!paso2.isConfirmed) return;

        const paso3 = await Swal.fire({
            title: 'Confirmación Final',
            text: "Escriba 'ELIMINAR' para proceder con la baja definitiva:",
            input: 'text',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: '¡Dar de Baja!',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#000',
            inputValidator: (value) => {
                if (value !== 'ELIMINAR') {
                    return 'Debe escribir ELIMINAR con mayúsculas para confirmar';
                }
            }
        });

        if (paso3.isConfirmed) {
            const payload = { peticion: 'BAJA_DISP', id_disp: idDisp, id_sub: idSub };
            asyncProcessForm(payload, '_controller/DispositivosAsync.php', "Procesando Baja...", "Liberando recursos y registrando bitácora...", "¡Baja Exitosa!");
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Escuchar los clics en nuestras opciones de dropdown
    const dropdownItems = document.querySelectorAll('.dropdown-filtro .dropdown-item');
    
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            const container = this.closest('.dropdown');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const btnText = container.querySelector('button span');

            // 1. Actualizar el texto del botón
            btnText.textContent = this.textContent.trim();

            // 2. Mover la clase "activa" para resaltar la selección en gris/negritas
            container.querySelectorAll('.dropdown-item').forEach(el => el.classList.remove('active', 'bg-light', 'fw-bold', 'text-dark'));
            this.classList.add('active', 'bg-light', 'fw-bold', 'text-dark');

            // 3. Asignar el valor al input oculto y avisarle a jQuery que hubo un cambio
            hiddenInput.value = this.getAttribute('data-value');
            $(hiddenInput).trigger('change'); // <-- ESTO activa tu script de dispositivos.js al instante
        });
    });
});
