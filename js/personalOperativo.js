$(document).ready(function() {

    // ==========================================
    // FUNCIÓN AUXILIAR: QUITAR ACENTOS
    // ==========================================
    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    // ==========================================
    // 1. LÓGICA DE BÚSQUEDA, FILTRADO Y PAGINACIÓN
    // ==========================================
    const $inputCobroNombre = $('#buscarCobroNombre');
    const $selectDepartamento = $('#filtroDepartamento');
    const $inputPuesto = $('#buscarPuesto');
    const $btnLimpiar = $('#btnLimpiarFiltros');
    const $selectRegistros = $('#selectorRegistros'); // El nuevo select del HTML
    const $paginacionContenedor = $('#paginacionTabla'); // El nuevo contenedor ul
    const $contadorRegistros = $('#contador-registros');
    const $filasPersonal = $('.fila-personal');
    
    let paginaActual = 1;

    function actualizarTabla() {
        const textoCobroNombre = quitarAcentos($inputCobroNombre.val().toLowerCase().trim());
        const textoDepartamento = quitarAcentos($selectDepartamento.val().toLowerCase());
        const textoPuesto = quitarAcentos($inputPuesto.val().toLowerCase().trim());
        const palabrasBusqueda = textoCobroNombre.split(/\s+/); 
        const limite = $selectRegistros.val();

        let filasFiltradas = [];

        // 1. Filtrar las filas según los inputs
        $filasPersonal.each(function() {
            const $fila = $(this);
            
            const dataNombre = quitarAcentos(String($fila.data('nombre')).replace(/\s+/g, ' ').toLowerCase());
            const dataCobro = quitarAcentos(String($fila.data('cobro')).toLowerCase());
            const dataDepto = quitarAcentos(String($fila.data('depto')).toLowerCase());
            const dataPuesto = quitarAcentos(String($fila.data('puesto')).toLowerCase());

            let coincideNombre = true;
            for (let i = 0; i < palabrasBusqueda.length; i++) {
                if (!dataNombre.includes(palabrasBusqueda[i])) {
                    coincideNombre = false;
                    break;
                }
            }

            const coincideCobro = dataCobro.includes(textoCobroNombre);
            const coincideCobroNombre = (textoCobroNombre === '') || coincideNombre || coincideCobro;
            const coincideDepto = (textoDepartamento === 'all') || (dataDepto === textoDepartamento);
            const coincidePuesto = (textoPuesto === '') || dataPuesto.includes(textoPuesto);

            if (coincideCobroNombre && coincideDepto && coincidePuesto) {
                filasFiltradas.push(this); // Lo guardamos como DOM element para paginarlo después
            } else {
                $fila.hide(); // Lo ocultamos si no coincide
            }
        });

        const totalFiltrados = filasFiltradas.length;

        // 2. Mostrar mensaje de "Sin resultados"
        if (totalFiltrados === 0 && $filasPersonal.length > 0) {
            if ($('#sin-resultados').length === 0) {
                $('#tbodyPersonal').append(`
                    <tr id="sin-resultados">
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">No se encontraron resultados para tu búsqueda.</p>
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

        // 3. Aplicar Paginación
        if (limite !== 'all') {
            const limitNum = parseInt(limite);
            const totalPaginas = Math.ceil(totalFiltrados / limitNum);
            
            // Si la búsqueda redujo los resultados y estamos en una página que ya no existe, regresamos a la 1
            if (paginaActual > totalPaginas) paginaActual = 1;

            const inicio = (paginaActual - 1) * limitNum;
            const fin = inicio + limitNum;

            // Mostrar solo los que corresponden a la página actual
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
            // Si eligen "Todos"
            $(filasFiltradas).show();
            $paginacionContenedor.empty();
            $contadorRegistros.text(`Mostrando todos los ${totalFiltrados} registros`);
        }
    }

    function dibujarPaginacion(totalPaginas, activa) {
    $paginacionContenedor.empty();
    if (totalPaginas <= 1) return;

    // Configuración del rango
    const rangoVisible = 2; // Cuántos números mostrar a los lados de la activa
    
    // Botón Anterior
    $paginacionContenedor.append(`
        <li class="page-item ${activa === 1 ? 'disabled' : ''}">
            <button class="page-link text-guinda btn-paginacion" data-page="${activa - 1}">Anterior</button>
        </li>
    `);

    // Lógica de números con elipsis (...)
    for (let i = 1; i <= totalPaginas; i++) {
        // Siempre mostrar la primera, la última, y las cercanas a la activa
        if (i === 1 || i === totalPaginas || (i >= activa - rangoVisible && i <= activa + rangoVisible)) {
            const esActiva = i === activa;
            $paginacionContenedor.append(`
                <li class="page-item ${esActiva ? 'active' : ''}">
                    <button class="page-link ${esActiva ? 'bg-guinda border-guinda text-white' : 'text-guinda'} btn-paginacion" data-page="${i}">${i}</button>
                </li>
            `);
        } 
        // Mostrar puntos suspensivos si hay huecos
        else if (i === activa - rangoVisible - 1 || i === activa + rangoVisible + 1) {
            $paginacionContenedor.append(`
                <li class="page-item disabled">
                    <span class="page-link border-0 text-muted">...</span>
                </li>
            `);
        }
    }

    // Botón Siguiente
    $paginacionContenedor.append(`
        <li class="page-item ${activa === totalPaginas ? 'disabled' : ''}">
            <button class="page-link text-guinda btn-paginacion" data-page="${activa + 1}">Siguiente</button>
        </li>
    `);
}

    // ==========================================
    // EVENTOS DE INTERACCIÓN
    // ==========================================
    // Al escribir o seleccionar, volvemos a la página 1 y actualizamos
    $inputCobroNombre.on('keyup', () => { paginaActual = 1; actualizarTabla(); });
    $inputPuesto.on('keyup', () => { paginaActual = 1; actualizarTabla(); });
    $selectDepartamento.on('change', () => { paginaActual = 1; actualizarTabla(); });
    $selectRegistros.on('change', () => { paginaActual = 1; actualizarTabla(); });

    // Clic en la paginación generada
    $(document).on('click', '.btn-paginacion', function(e) {
        e.preventDefault();
        const $li = $(this).parent();
        if (!$li.hasClass('disabled') && !$li.hasClass('active')) {
            paginaActual = parseInt($(this).data('page'));
            actualizarTabla();
        }
    });

    $btnLimpiar.on('click', function() {
        $inputCobroNombre.val('');
        $inputPuesto.val('');
        $selectDepartamento.val('all');
        if ($selectRegistros.length) $selectRegistros.val('10');
        paginaActual = 1;
        actualizarTabla(); 
    });

    // Inicializar tabla (por si hay más de 10 registros al cargar)
    if ($selectRegistros.length) {
        actualizarTabla();
    }


    // ==========================================
    // 2. LÓGICA PARA BAJA LÓGICA DE EMPLEADO
    // ==========================================
    $(document).on('click', '.btn-baja-po', function(e) {
        e.preventDefault();
        
        let id_po = $(this).data('id');
        let id_sub = $(this).data('sub'); 
        let nombre_po = $(this).data('nombre'); 

        Swal.fire({
            title: '¿Dar de baja al empleado?',
            html: `Está a punto de dar de baja a <b>${nombre_po}</b>.<br>El empleado no podrá ser asignado a nuevos equipos.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-user-minus"></i> Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '_controller/PersonalOPAsync.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        peticion: 'BAJA_PO',
                        id_po: id_po,
                        id_sub: id_sub
                    },
                    success: function(response) {
                        if (response.result === 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Baja Exitosa',
                                text: response.msg,
                                confirmButtonColor: '#285C4D'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo completar',
                                text: response.msg,
                                confirmButtonColor: '#9D2449'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de servidor',
                            text: 'Hubo un problema al procesar la solicitud.',
                            confirmButtonColor: '#9D2449'
                        });
                    }
                });
            }
        });
    });

});