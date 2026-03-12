$(document).ready(function() {

    // ==========================================
    // FUNCIÓN AUXILIAR: QUITAR ACENTOS
    // ==========================================
    function quitarAcentos(texto) {
        if (!texto) return '';
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
    }

    // ==========================================
    // 1. LÓGICA DE BÚSQUEDA EN VIVO (Búsqueda Global)
    // ==========================================
    
    // Asumimos que agregarás un input de búsqueda con ID "buscarDispositivo" 
    // en tu HTML de dispositivos.html.php
    const $inputBusqueda = $('#buscarDispositivo');
    const $filasDispositivos = $('table tbody tr'); 

    function filtrarTabla() {
        if (!$inputBusqueda.length) return; // Si no existe el input, no hace nada

        const textoBusqueda = quitarAcentos($inputBusqueda.val().toLowerCase().trim());
        const palabrasBusqueda = textoBusqueda.split(/\s+/); 

        let filasVisibles = 0;

        $filasDispositivos.each(function() {
            const $fila = $(this);
            
            // Si es la fila de "No se encontraron dispositivos", la omitimos de la lógica
            if ($fila.find('td').length === 1) return;

            // Obtenemos todo el texto de la fila (Hostname, Marca, Serie, IP, Servicio)
            const textoFila = quitarAcentos($fila.text().replace(/\s+/g, ' ').toLowerCase());

            let coincide = true;
            for (let i = 0; i < palabrasBusqueda.length; i++) {
                if (!textoFila.includes(palabrasBusqueda[i])) {
                    coincide = false;
                    break;
                }
            }

            if (coincide) {
                $fila.show();
                filasVisibles++;
            } else {
                $fila.hide();
            }
        });

        // Mostrar mensaje si no hay resultados en la búsqueda
        if (filasVisibles === 0 && textoBusqueda !== '') {
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
        } else {
            $('#sin-resultados').remove();
        }
    }

    // Escuchamos el evento para ejecutar el filtro instantáneamente
    $inputBusqueda.on('keyup', filtrarTabla);

    // ==========================================
    // 2. ELIMINAR DISPOSITIVO (Ejemplo con SweetAlert2)
    // ==========================================
    $('.btn-eliminar-disp').on('click', function(e) {
        e.preventDefault();
        const idDisp = $(this).data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: "El dispositivo será dado de baja del sistema.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Aquí iría tu petición Fetch/Ajax hacia el controlador asíncrono
                console.log("Eliminar ID:", idDisp);
                // fetch('...', { ... })
            }
        });
    });

});