document.addEventListener("DOMContentLoaded", function () {
    
    // ========================================================
    // MODAL Y LÓGICA DE USUARIOS
    // ========================================================
    const modalUsuario = document.getElementById('modalUsuario');
    const formUsuario = document.getElementById('formUsuario');
    
    if (modalUsuario) {
        modalUsuario.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar');
            
            formUsuario.reset();

            if (esEdicion) {
                document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuario';
                document.getElementById('peticion_input').value = 'UPDATE_USUARIO';
                document.getElementById('id_usuar_input').value = button.getAttribute('data-id');
                document.getElementById('nom_usuar_input').value = button.getAttribute('data-nom_usuar');
                document.getElementById('nombres_input').value = button.getAttribute('data-nombres');
                document.getElementById('ape_pa_input').value = button.getAttribute('data-ape_pa');
                document.getElementById('ape_ma_input').value = button.getAttribute('data-ape_ma');
                document.getElementById('id_rol_input').value = button.getAttribute('data-id_rol');
                
                // Al editar, la contraseña no es obligatoria
                document.getElementById('password_input').required = false;
            } else {
                document.getElementById('modalUsuarioLabel').textContent = 'Nuevo Usuario';
                document.getElementById('peticion_input').value = 'INSERT_USUARIO';
                document.getElementById('id_usuar_input').value = '';
                
                // Al crear, la contraseña SÍ es obligatoria
                document.getElementById('password_input').required = true;
            }
        });
    }

    // Guardar / Editar Usuario usando asyncProcessForm
    if (formUsuario) {
        formUsuario.addEventListener('submit', function (e) {
            e.preventDefault();
            asyncProcessForm(this, '_controller/AdminUsuariosAsync.php');
        });
    }

    // Botón Dar de Baja Usuario usando Formulario Virtual
    document.querySelectorAll('.btn-baja').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            
            Swal.fire({
                title: '¿Dar de baja al usuario?',
                text: 'Perderá inmediatamente el acceso al sistema.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, dar de baja'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formVirtual = document.createElement('form');
                    formVirtual.innerHTML = `
                        <input type="hidden" name="peticion" value="BAJA_USUARIO">
                        <input type="hidden" name="id_usuar" value="${id}">
                    `;
                    asyncProcessForm(formVirtual, '_controller/AdminUsuariosAsync.php');
                }
            });
        });
    });

    // ========================================================
    // MODAL Y LÓGICA DE ROLES
    // ========================================================
    const modalRol = document.getElementById('modalRol');
    const formRol = document.getElementById('formRol');
    
    if (modalRol) {
        modalRol.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar-rol');
            
            formRol.reset();
            // Apagar todos los checkboxes antes de abrir
            document.querySelectorAll('.check-permiso-rol').forEach(chk => chk.checked = false);

            if (esEdicion) {
                document.getElementById('modalRolLabel').textContent = 'Editar Rol';
                document.getElementById('peticion_rol_input').value = 'UPDATE_ROL';
                document.getElementById('id_rol_hidden').value = button.getAttribute('data-id');
                document.getElementById('nom_rol_input').value = button.getAttribute('data-nom_rol');

                // Magia: Extraer el JSON y encender los checkboxes de los submódulos correspondientes
                const permisosJson = button.getAttribute('data-permisos');
                if (permisosJson) {
                    const permisosDict = JSON.parse(permisosJson);
                    // Itera sobre los IDs de los submódulos
                    for (const id_sub in permisosDict) {
                        const arrayPermisos = permisosDict[id_sub]; // Ej: ["1", "2"]
                        arrayPermisos.forEach(id_perm => {
                            const checkbox = document.getElementById(`sub${id_sub}_perm${id_perm}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                }
            } else {
                document.getElementById('modalRolLabel').textContent = 'Nuevo Rol';
                document.getElementById('peticion_rol_input').value = 'INSERT_ROL';
                document.getElementById('id_rol_hidden').value = '';
            }
        });
    }

    // Guardar / Editar Rol usando asyncProcessForm
    if (formRol) {
        formRol.addEventListener('submit', function (e) {
            e.preventDefault();
            // Cambiamos el destino al nuevo Async de Roles
            asyncProcessForm(this, '_controller/AdminRolesAsync.php'); 
        });
    }
    // Botón Eliminar Rol usando Formulario Virtual
    document.querySelectorAll('.btn-eliminar-rol').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nom = this.getAttribute('data-nom');
            
            Swal.fire({
                title: '¿Eliminar Rol?',
                text: `Estás a punto de eliminar el rol '${nom}'. Si existen usuarios asignados a este rol, el sistema rechazará la acción.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formVirtual = document.createElement('form');
                    formVirtual.innerHTML = `
                        <input type="hidden" name="peticion" value="DELETE_ROL">
                        <input type="hidden" name="id_rol" value="${id}">
                    `;
                    asyncProcessForm(formVirtual, '_controller/AdminRolesAsync.php');
                }
            });
        });
    });

});