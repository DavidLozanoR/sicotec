document.addEventListener("DOMContentLoaded", function () {
    
    const mainContainer = document.getElementById('main-roles-container');
    const idSubActual = mainContainer ? mainContainer.getAttribute('data-id-sub') : '';

    const inputNomRol = document.getElementById('nom_rol_input');
    if (inputNomRol) {
        inputNomRol.addEventListener('input', function () {
            // 1. Quitamos espacios en blanco inmediatamente
            let val = this.value.replace(/\s/g, '');
            
            // 2. Quitamos caracteres especiales no permitidos (dejamos letras, números)
            val = val.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ]/g, '');

            // 3. Validar que el primer carácter sea estrictamente una letra
            // Si hay texto, pero el primer carácter no es una letra, lo borramos
            if (val.length > 0 && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]/.test(val)) {
                val = val.substring(1); 
            }

            // 4. Limitar a un máximo de 25 caracteres
            if (val.length > 25) {
                val = val.substring(0, 25);
            }

            // Regresamos el valor limpio y formateado al input
            this.value = val;
        });
    }

    function recargarTablaRoles(res) {
        const modalR = bootstrap.Modal.getInstance(document.getElementById('modalRol'));
        if (modalR) modalR.hide();

        const urlObj = new URL(window.location.href);
        urlObj.searchParams.set('_t', new Date().getTime());

        $('#contenedor-tabla-roles').load(urlObj.toString() + ' #contenedor-tabla-roles > *');
    }

    const modalRol = document.getElementById('modalRol');
    const formRol = document.getElementById('formRol');
    
    if (modalRol) {
        modalRol.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar-rol');
            
            formRol.classList.remove('was-validated');
            formRol.reset();
            document.querySelectorAll('.check-permiso-rol').forEach(chk => chk.checked = false);

            if (esEdicion) {
                document.getElementById('modalRolLabel').textContent = 'Editar Rol';
                document.getElementById('peticion_rol_input').value = 'UPDATE_ROL';
                document.getElementById('id_rol_hidden').value = button.getAttribute('data-id');
                document.getElementById('nom_rol_input').value = button.getAttribute('data-nom_rol');

                const permisosJson = button.getAttribute('data-permisos');
                if (permisosJson) {
                    const permisosDict = JSON.parse(permisosJson);
                    for (const id_sub in permisosDict) {
                        const arrayPermisos = permisosDict[id_sub]; 
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

    if (formRol) {
        formRol.addEventListener('submit', function (e) {
            e.preventDefault();

            // 1. Validación Nativa (Nombre del Rol vacío)
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // 2. VALIDACIÓN DE NEGOCIO: Al menos un permiso de Consultar (ID 4)
            const permisosSeleccionados = document.querySelectorAll('.check-permiso-rol:checked');
            let tieneConsultar = false;

            if (permisosSeleccionados.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Permisos',
                    text: 'Debe seleccionar al menos el permiso de "Consultar" en algún submódulo para que el usuario pueda ver el sistema.',
                    confirmButtonColor: '#9D2449'
                });
                return; // Detiene el envío
            }

            permisosSeleccionados.forEach(chk => {
                if (chk.value === "4") {
                    tieneConsultar = true;
                }
            });

            if (!tieneConsultar) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta Permiso Básico',
                    text: 'Debe seleccionar al menos el permiso de "Consultar" en algún submódulo para que el usuario pueda ver el sistema.',
                    confirmButtonColor: '#9D2449'
                });
                return; // Detiene el envío
            }

            this.classList.remove('was-validated');
            this.querySelector('.dynamic-id-sub').value = idSubActual;

            asyncProcessForm(this, '_controller/AdminRolesAsync.php', function(res) {
                if (res.result === 1) {
                    recargarTablaRoles(res);
                } else if (res.result === 2) {
                    Swal.fire({
                        title: 'Rol Inactivo Detectado',
                        text: res.msg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#6C1D45',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, reactivarlo',
                        cancelButtonText: 'No, cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('peticion_rol_input').value = 'REACTIVATE_ROL';
                            document.getElementById('id_rol_hidden').value = res.id_rol_reactivar;
                            asyncProcessForm(formRol, '_controller/AdminRolesAsync.php', recargarTablaRoles);
                        }
                    });
                }
            }); 
        });
    }

    document.addEventListener('click', function (e) {
        const btnEliminarRol = e.target.closest('.btn-eliminar-rol');
        if (btnEliminarRol) {
            const id = btnEliminarRol.getAttribute('data-id');
            const nom = btnEliminarRol.getAttribute('data-nom');
            Swal.fire({
                title: '¿Dar de baja Rol?',
                text: `Estás a punto de dar de baja el rol '${nom}'. Si existen usuarios asignados a este rol, el sistema rechazará la acción.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, dar de baja'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formVirtual = document.createElement('form');
                    formVirtual.innerHTML = `
                        <input type="hidden" name="peticion" value="DELETE_ROL">
                        <input type="hidden" name="id_rol" value="${id}">
                        <input type="hidden" name="id_sub" value="${idSubActual}">
                    `;
                    asyncProcessForm(formVirtual, '_controller/AdminRolesAsync.php', recargarTablaRoles);
                }
            });
        }
    });
});