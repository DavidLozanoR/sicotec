$(document).ready(function() {

    // ==========================================
    // FUNCIÓN AUXILIAR: QUITAR ACENTOS
    // ==========================================
    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    // ==========================================
    // 1. LÓGICA DE BÚSQUEDA Y FILTRADO EN VIVO
    // ==========================================
    const $inputCobroNombre = $('#buscarCobroNombre');
    const $selectDepartamento = $('#filtroDepartamento');
    const $inputPuesto = $('#buscarPuesto');
    const $btnLimpiar = $('#btnLimpiarFiltros');
    const $filasPersonal = $('.fila-personal');
    const $contadorRegistros = $('#contador-registros');

    function filtrarTabla() {
        const textoCobroNombre = quitarAcentos($inputCobroNombre.val().toLowerCase().trim());
        const textoDepartamento = quitarAcentos($selectDepartamento.val().toLowerCase());
        const textoPuesto = quitarAcentos($inputPuesto.val().toLowerCase().trim());
        const palabrasBusqueda = textoCobroNombre.split(/\s+/); 

        let filasVisibles = 0;

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
                $fila.show();
                filasVisibles++;
            } else {
                $fila.hide();
            }
        });

        $contadorRegistros.text(`Mostrando ${filasVisibles} registros`);

        if (filasVisibles === 0 && $filasPersonal.length > 0) {
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
        } else {
            $('#sin-resultados').remove();
        }
    }

    $inputCobroNombre.on('keyup', filtrarTabla);
    $inputPuesto.on('keyup', filtrarTabla);
    $selectDepartamento.on('change', filtrarTabla);

    $btnLimpiar.on('click', function() {
        $inputCobroNombre.val('');
        $inputPuesto.val('');
        $selectDepartamento.val('all');
        filtrarTabla(); 
    });


    // ==========================================
    // 2. LÓGICA PARA BAJA LÓGICA DE EMPLEADO
    // ==========================================
    // Escuchamos el clic en el botón de eliminar (Asegúrate de que tus botones de eliminar en el HTML tengan la clase .btn-baja-po)
    $(document).on('click', '.btn-baja-po', function(e) {
        e.preventDefault();
        
        let id_po = $(this).data('id');
        let id_sub = $(this).data('sub'); // Requerimos el ID del submódulo para la bitácora/permisos
        let nombre_po = $(this).data('nombre'); // Opcional: para mostrar en el mensaje

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
                
                // Realizamos la petición AJAX
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
                                // Recargar la página para reflejar los cambios
                                location.reload();
                            });
                        } else {
                            // Muestra error de permisos (Ej: "No cuenta con los permisos...") o reglas de negocio
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