$(document).ready(function() {

    // =========================================================
    // 1. EVENTO EDITAR (Atrapar el clic directamente en el botón)
    // =========================================================
    $(document).on('click', '.btn-editar', function() {
        // 'this' garantiza que siempre leamos el botón, ignorando el ícono
        var button = $(this); 
        var idUsuar = button.attr('data-id'); 

        // 1.1 Configurar el Modal para modo UPDATE
        $('#modalUsuarioLabel').text("Editar Usuario");
        $('#peticion_input').val('UPDATE');
        $('#id_usuar_input').val(idUsuar);

        // 1.2 Rellenar los inputs de texto al instante
        $('#nom_usuar_input').val(button.attr('data-nom_usuar'));
        $('#nombres_input').val(button.attr('data-nombres'));
        $('#ape_pa_input').val(button.attr('data-ape_pa'));
        $('#ape_ma_input').val(button.attr('data-ape_ma'));
        $('#id_rol_input').val(button.attr('data-id_rol'));

        // 1.3 Limpiar todos los checkboxes primero
        $('.check-permiso').prop('checked', false);

        // 1.4 Marcar las palomitas que este usuario ya tiene
        try {
            var permisosJSON = button.attr('data-permisos');
            if (permisosJSON && permisosJSON !== "[]") {
                var permisosArr = JSON.parse(permisosJSON);
                permisosArr.forEach(function(permId) {
                    $('#perm_' + permId).prop('checked', true); 
                });
            }
        } catch (error) {
            console.error("Error al leer permisos en JSON:", error);
        }
    });

    // =========================================================
    // 2. EVENTO NUEVO USUARIO (Limpiar todo)
    // =========================================================
    // Atrapamos el botón principal de arriba que abre el modal
    $('[data-bs-target="#modalUsuario"]').not('.btn-editar').on('click', function() {
        $('#formUsuario')[0].reset();
        $('.check-permiso').prop('checked', false);
        
        $('#modalUsuarioLabel').text("Nuevo Usuario");
        $('#peticion_input').val('INSERT');
        $('#id_usuar_input').val('');
    });

    // =========================================================
    // 3. EVENTO GUARDAR (Manda el formulario al Async)
    // =========================================================
    $('#formUsuario').on('submit', function(e) {
        e.preventDefault();
        
        // Función del archivo site.js
        asyncProcessForm(
            this,
            '_controller/AdminUsuariosAsync.php',
            'Procesando',
            'Guardando la información en el sistema...',
            'Operación Exitosa',
            function() { location.reload(); }
        );
    });

    // =========================================================
    // 4. EVENTO DAR DE BAJA LÓGICA (SweetAlert)
    // =========================================================
    $(document).on('click', '.btn-baja', function() {
        var idUsuar = $(this).attr('data-id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El usuario será desactivado del sistema INEA.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449', // Guinda 
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('peticion', 'DELETE');
                formData.append('id_usuar', idUsuar);

                fetch('_controller/AdminUsuariosAsync.php', { 
                    method: 'POST', 
                    body: formData 
                })
                .then(resp => resp.json())
                .then(data => {
                    if (data.result === 1) {
                        Swal.fire('¡Baja Exitosa!', data.msg, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                }).catch(err => {
                    Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
                });
            }
        });
    });

});