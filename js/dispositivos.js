$(document).ready(function() {

    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    const $inputBusqueda = $('#buscarDispositivo');
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
        const limite = $selectRegistros.val();

        let filasFiltradas = [];

        $filasDispositivos.each(function() {
            const $fila = $(this);
            // Extraemos la información de software oculta en el tr
            const softwareOculto = $fila.attr('data-software') || '';
            
            // Concatenamos el texto visible de la fila con el software oculto
            const textoFila = quitarAcentos($fila.text().replace(/\s+/g, ' ') + ' ' + softwareOculto).toLowerCase();

            let coincide = true;
            for (let i = 0; i < palabrasBusqueda.length; i++) {
                if (!textoFila.includes(palabrasBusqueda[i])) {
                    coincide = false;
                    break;
                }
            }

            if (coincide) {
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
                            <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No se encontraron dispositivos que coincidan con "${$inputBusqueda.val()}".</p>
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

    $inputBusqueda.on('keyup', () => { paginaActual = 1; actualizarTabla(); });
    $selectRegistros.on('change', () => { paginaActual = 1; actualizarTabla(); });

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
    // 2. ELIMINAR DISPOSITIVO (Baja Definitiva)
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
            Swal.fire({
                title: 'Procesando Baja...',
                text: 'Liberando recursos y registrando bitácora.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('_controller/DispositivosAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    peticion: 'BAJA_DISP',
                    id_disp: idDisp,
                    id_sub: idSub
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Baja Exitosa', text: data.msg, confirmButtonColor: '#285C4D' })
                    .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.msg, confirmButtonColor: '#9D2449' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error de servidor', text: 'Ocurrió un problema de comunicación.', confirmButtonColor: '#9D2449' });
            });
        }
    });
});