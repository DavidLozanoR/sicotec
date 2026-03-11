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
        // Obtenemos valores, limpiamos espacios, pasamos a minúsculas Y QUITAMOS ACENTOS
        const textoCobroNombre = quitarAcentos($inputCobroNombre.val().toLowerCase().trim());
        const textoDepartamento = quitarAcentos($selectDepartamento.val().toLowerCase());
        const textoPuesto = quitarAcentos($inputPuesto.val().toLowerCase().trim());
        
        // Separamos la búsqueda por palabras
        const palabrasBusqueda = textoCobroNombre.split(/\s+/); 

        let filasVisibles = 0;

        $filasPersonal.each(function() {
            const $fila = $(this);
            
            // Extraemos la información de la fila, forzamos a String, minúsculas Y QUITAMOS ACENTOS
            const dataNombre = quitarAcentos(String($fila.data('nombre')).replace(/\s+/g, ' ').toLowerCase());
            const dataCobro = quitarAcentos(String($fila.data('cobro')).toLowerCase());
            const dataDepto = quitarAcentos(String($fila.data('depto')).toLowerCase());
            const dataPuesto = quitarAcentos(String($fila.data('puesto')).toLowerCase());

            // 1. Evaluamos coincidencia de Nombre o Cobro
            let coincideNombre = true;
            
            for (let i = 0; i < palabrasBusqueda.length; i++) {
                if (!dataNombre.includes(palabrasBusqueda[i])) {
                    coincideNombre = false;
                    break;
                }
            }

            const coincideCobro = dataCobro.includes(textoCobroNombre);
            const coincideCobroNombre = (textoCobroNombre === '') || coincideNombre || coincideCobro;
            
            // 2. Coincide Departamento
            const coincideDepto = (textoDepartamento === 'all') || (dataDepto === textoDepartamento);
            
            // 3. Coincide Puesto
            const coincidePuesto = (textoPuesto === '') || dataPuesto.includes(textoPuesto);

            // Si cumple TODAS las condiciones, mostramos la fila
            if (coincideCobroNombre && coincideDepto && coincidePuesto) {
                $fila.show();
                filasVisibles++;
            } else {
                $fila.hide();
            }
        });

        // Actualizamos el texto del contador
        $contadorRegistros.text(`Mostrando ${filasVisibles} registros`);

        // Mostrar mensaje si no hay resultados en la búsqueda
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

    // Escuchamos los eventos para ejecutar el filtro instantáneamente
    $inputCobroNombre.on('keyup', filtrarTabla);
    $inputPuesto.on('keyup', filtrarTabla);
    $selectDepartamento.on('change', filtrarTabla);

    // ==========================================
    // 2. BOTÓN LIMPIAR FILTROS
    // ==========================================
    $btnLimpiar.on('click', function() {
        $inputCobroNombre.val('');
        $inputPuesto.val('');
        $selectDepartamento.val('all');
        filtrarTabla(); // Volvemos a ejecutar para mostrar todo
    });

});