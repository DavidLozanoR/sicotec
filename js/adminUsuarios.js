$(document).ready(function() {

    // =========================================================
    // 1. EVENTO EDITAR USUARIO
    // =========================================================
    $(document).on('click', '.btn-editar', function() {
        var button = $(this); 
        var idUsuar = button.attr('data-id'); 

        $('#modalUsuarioLabel').text("Editar Usuario");
        $('#peticion_input').val('UPDATE');
        $('#id_usuar_input').val(idUsuar);

        $('#nom_usuar_input').val(button.attr('data-nom_usuar'));
        $('#nombres_input').val(button.attr('data-nombres'));
        $('#ape_pa_input').val(button.attr('data-ape_pa'));
        $('#ape_ma_input').val(button.attr('data-ape_ma'));
        $('#id_rol_input').val(button.attr('data-id_rol'));

        $('.check-permiso').prop('checked', false);

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
    $('[data-bs-target="#modalUsuario"]').not('.btn-editar').on('click', function() {
        $('#formUsuario')[0].reset();
        $('.check-permiso').prop('checked', false);
        
        $('#modalUsuarioLabel').text("Nuevo Usuario");
        $('#peticion_input').val('INSERT');
        $('#id_usuar_input').val('');
    });

    // =========================================================
    // 3. EVENTO GUARDAR USUARIO
    // =========================================================
    $('#formUsuario').on('submit', function(e) {
        e.preventDefault();
        
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
    // 4. EVENTO DAR DE BAJA USUARIO
    // =========================================================
    $(document).on('click', '.btn-baja', function() {
        var idUsuar = $(this).attr('data-id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El usuario será desactivado del sistema INEA.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
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

    // =========================================================
    // 5. REGLAS DE NEGOCIO — ROLES Y PERMISOS EN MODAL USUARIO
    // =========================================================
    
    function evaluarProteccionRol() {
        var rolSeleccionado = $('#id_rol_input').val();
        if (rolSeleccionado == 1 || rolSeleccionado == 2) {
            $('.check-permiso').prop('disabled', true);
        } else {
            $('.check-permiso').prop('disabled', false);
        }
    }

    $('#id_rol_input').on('change', function() {
        evaluarProteccionRol();
        var idRol = $(this).val();
        
        if (idRol) {
            var formData = new FormData();
            formData.append('peticion', 'GET_PERMISOS_ROL');
            formData.append('id_rol', idRol);

            fetch('_controller/AdminUsuariosAsync.php', {
                method: 'POST',
                body: formData
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.result === 1) {
                    $('.check-permiso').prop('checked', false);
                    if (data.permisos && data.permisos.length > 0) {
                        data.permisos.forEach(function(permId) {
                            $('#perm_' + permId).prop('checked', true);
                        });
                    }
                }
            })
            .catch(error => {
                console.error("Error al obtener los permisos del rol:", error);
            });
        }
    });

    $('#modalUsuario').on('shown.bs.modal', function () {
        evaluarProteccionRol();
    });

    let alertaMostrada = false; 

    $('.check-permiso').on('change', function() {
        var rolSeleccionado = $('#id_rol_input').val();
        
        if (rolSeleccionado && rolSeleccionado != 1 && rolSeleccionado != 2) {
            if (!alertaMostrada) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Aviso Importante',
                    text: 'Este rol ya existe. Si modificas sus permisos, se cambiarán para TODOS los usuarios que lo tengan.',
                    showConfirmButton: false,
                    timer: 6000,
                    timerProgressBar: true
                });
                alertaMostrada = true;
                
                $('#modalUsuario').on('hidden.bs.modal', function () {
                    alertaMostrada = false;
                });
            }
        }
    });


    // =========================================================
    // 6. ROLES — NUEVO ROL (Limpiar Modal)
    // =========================================================
    $('[data-bs-target="#modalRol"]').not('.btn-editar-rol').on('click', function() {
        $('#formRol')[0].reset();
        $('.check-permiso-rol').prop('checked', false);

        $('#modalRolLabel').text("Nuevo Rol");
        $('#peticion_rol_input').val('INSERT');
        $('#id_rol_hidden').val('');
        $('#btnGuardarRol').text('Guardar Rol');
    });

    // =========================================================
    // 7. ROLES — EDITAR ROL
    // =========================================================
    $(document).on('click', '.btn-editar-rol', function() {
        var button   = $(this);
        var idRol    = button.attr('data-id');
        var nomRol   = button.attr('data-nom_rol');
        var permisos = [];

        try {
            permisos = JSON.parse(button.attr('data-permisos') || '[]');
        } catch(e) {
            console.error("Error al leer permisos del rol:", e);
        }

        $('#modalRolLabel').text("Editar Rol");
        $('#peticion_rol_input').val('UPDATE');
        $('#id_rol_hidden').val(idRol);
        $('#nom_rol_input').val(nomRol);
        $('#btnGuardarRol').text('Actualizar Rol');

        // Desmarcar todos y marcar los asignados
        $('.check-permiso-rol').prop('checked', false);
        permisos.forEach(function(permId) {
            $('#rolperm_' + permId).prop('checked', true);
        });
    });

    // =========================================================
    // 8. ROLES — GUARDAR (INSERT / UPDATE)
    // =========================================================
    $('#formRol').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#btnGuardarRol');
        var textoOriginal = $btn.text();
        $btn.prop('disabled', true).text('Guardando...');

        var formData = new FormData(this);

        fetch('_controller/AdminRolesAsync.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.result == 1) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: data.msg,
                    confirmButtonColor: '#9D2449'
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.msg,
                    confirmButtonColor: '#9D2449'
                });
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
        })
        .finally(() => {
            $btn.prop('disabled', false).text(textoOriginal);
        });
    });

    // =========================================================
    // 9. ROLES — ELIMINAR ROL
    // =========================================================
    $(document).on('click', '.btn-eliminar-rol', function() {
        var idRol    = $(this).attr('data-id');
        var nomRol   = $(this).attr('data-nom');
        var usuarios = parseInt($(this).attr('data-usuarios'));

        // Bloqueo preventivo en el cliente
        if (usuarios > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Acción no permitida',
                text: 'No se puede eliminar el rol "' + nomRol + '" porque tiene ' + usuarios + ' usuario(s) activo(s) asignado(s).',
                confirmButtonColor: '#9D2449'
            });
            return;
        }

        Swal.fire({
            title: '¿Eliminar rol "' + nomRol + '"?',
            text: 'Esta acción eliminará el rol y todos sus permisos asignados. No se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#9D2449',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('peticion', 'DELETE');
                formData.append('id_rol', idRol);

                fetch('_controller/AdminRolesAsync.php', {
                    method: 'POST',
                    body: formData
                })
                .then(resp => resp.json())
                .then(data => {
                    if (data.result == 1) {
                        Swal.fire('¡Eliminado!', data.msg, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
                });
            }
        });
    });

});