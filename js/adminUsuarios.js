document.addEventListener("DOMContentLoaded", function () {
    
    // 1. OBTENEMOS EL ID DEL SUBMÓDULO DINÁMICAMENTE DESDE LA VISTA
    const mainContainer = document.getElementById('main-admin-container');
    const idSubActual = mainContainer ? mainContainer.getAttribute('data-id-sub') : '';


    // VALIDACIONES EN TIEMPO REAL (FRONTEND)
  
    // 1. Nickname: Una sola palabra, sin espacios, permite letras, números, guiones y guiones bajos
    const inputNomUsuar = document.getElementById('nom_usuar_input');
    if (inputNomUsuar) {
        inputNomUsuar.addEventListener('input', function () {
            // Eliminamos cualquier espacio en blanco al instante
            let texto = this.value.replace(/\s/g, '');
            
            // Eliminamos cualquier carácter que no sea letra, número, CARACTERES
            texto = texto.replace(/^[^a-zA-ZáéíóúÁÉÍÓÚñÑ]+/, '');
            
            // Regla estricta: obligamos a que el primer carácter sea siempre una letra
            if (texto.length > 0 && !/^[a-zA-Z]/.test(texto)) {
                texto = texto.substring(1);
            }

            this.value = texto;
        });
    }

    // 2. Nombres y Apellidos: Solo letras y espacios (sin espacios al inicio)
    const nameInputs = ['nombres_input', 'ape_pa_input', 'ape_ma_input'];
    nameInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function () {
                // Paso 1: Permitir solo letras y espacios
                let texto = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                
                // Paso 2: Eliminar espacios al inicio de la cadena
                texto = texto.replace(/^\s+/, '');

                // Paso 3: Evitar múltiples espacios consecutivos entre palabras
                texto = texto.replace(/\s{2,}/g, ' ');

                this.value = texto;
            });
        }
    });

    // 3. Contraseña: Sin espacios en blanco
    const inputPassword = document.getElementById('password_input');
    if (inputPassword) {
        inputPassword.addEventListener('input', function () {
            // Borra cualquier espacio en blanco que intente escribir
            this.value = this.value.replace(/\s/g, ''); 
        });
    }

    // 4. Nombre del Rol: Letras, números y espacios
    const inputNomRol = document.getElementById('nom_rol_input');
    if (inputNomRol) {
        inputNomRol.addEventListener('input', function () {
            // Letras, acentos, ñ, números y espacios permitidos
            this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]/g, '');
        });
    }

    // FUNCIÓN DE RECARGA PARCIAL (DOM DINÁMICO)
    function recargarTablasYModales(res) {
        const modalU = bootstrap.Modal.getInstance(document.getElementById('modalUsuario'));
        if (modalU) modalU.hide();
        
        const modalR = bootstrap.Modal.getInstance(document.getElementById('modalRol'));
        if (modalR) modalR.hide();

        $('#contenedor-tabla-usuarios').load(window.location.href + ' #contenedor-tabla-usuarios > *');
        $('#contenedor-tabla-roles').load(window.location.href + ' #contenedor-tabla-roles > *');

        if (res && res.update_current_user) {
            document.querySelectorAll('.name-display-global').forEach(elemento => {
                elemento.textContent = res.nuevo_nombre;
            });
        }
    }

    // MODAL Y FORMULARIO DE USUARIOS
    const modalUsuario = document.getElementById('modalUsuario');
    const formUsuario = document.getElementById('formUsuario');
    
    if (modalUsuario) {
        modalUsuario.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar');
            
            // Limpiamos los colores rojos de validaciones previas al abrir
            formUsuario.classList.remove('was-validated');
            formUsuario.reset();

            const selectRol = document.getElementById('id_rol_input');
            const optAdmin = document.getElementById('opt-admin');

            let hiddenRol = document.getElementById('hidden_id_rol');
            if (!hiddenRol) {
                hiddenRol = document.createElement('input');
                hiddenRol.type = 'hidden';
                hiddenRol.name = 'id_rol';
                hiddenRol.id = 'hidden_id_rol';
                formUsuario.appendChild(hiddenRol);
            }

            if (esEdicion) {
                document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuario';
                document.getElementById('peticion_input').value = 'UPDATE_USUARIO';
                document.getElementById('id_usuar_input').value = button.getAttribute('data-id');
                document.getElementById('nom_usuar_input').value = button.getAttribute('data-nom_usuar');
                document.getElementById('nombres_input').value = button.getAttribute('data-nombres');
                document.getElementById('ape_pa_input').value = button.getAttribute('data-ape_pa');
                document.getElementById('ape_ma_input').value = button.getAttribute('data-ape_ma');
                document.getElementById('password_input').required = false;

                const id_rol_editado = button.getAttribute('data-id_rol');
                
                if (id_rol_editado == '1') {
                    optAdmin.disabled = false;
                    optAdmin.style.display = 'block';
                    selectRol.value = '1';
                    selectRol.disabled = true; 
                    hiddenRol.value = '1';
                    hiddenRol.disabled = false; 
                } else {
                    optAdmin.disabled = true;
                    optAdmin.style.display = 'none';
                    selectRol.disabled = false;
                    selectRol.value = id_rol_editado;
                    hiddenRol.disabled = true;
                }
            } else {
                document.getElementById('modalUsuarioLabel').textContent = 'Nuevo Usuario';
                document.getElementById('peticion_input').value = 'INSERT_USUARIO';
                document.getElementById('id_usuar_input').value = '';
                document.getElementById('password_input').required = true;

                optAdmin.disabled = true;
                optAdmin.style.display = 'none';
                selectRol.disabled = false;
                selectRol.value = '';
                hiddenRol.disabled = true;
            }
        });
    }

    if (formUsuario) {
        formUsuario.addEventListener('submit', function (e) {
            e.preventDefault();
            
            // VERIFICACIÓN DE BOOTSTRAP
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated'); // Muestra los mensajes rojos
                return; // Detiene la ejecución aquí
            }

            // Si pasa la validación, procesamos normal
            this.classList.remove('was-validated'); 
            this.querySelector('.dynamic-id-sub').value = idSubActual;
            asyncProcessForm(this, '_controller/AdminUsuariosAsync.php', recargarTablasYModales);
        });
    }

    // MODAL Y FORMULARIO DE ROLES
    const modalRol = document.getElementById('modalRol');
    const formRol = document.getElementById('formRol');
    
    if (modalRol) {
        modalRol.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar-rol');
            
            // Limpiamos validaciones pasadas
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

            // VERIFICACIÓN DE BOOTSTRAP
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated'); // Muestra los mensajes rojos
                return; // Detiene la ejecución aquí
            }

            // Si es válido, avanza
            this.classList.remove('was-validated');
            this.querySelector('.dynamic-id-sub').value = idSubActual;

            asyncProcessForm(this, '_controller/AdminRolesAsync.php', function(res) {
                if (res.result === 1) {
                    recargarTablasYModales(res);
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
                            asyncProcessForm(formRol, '_controller/AdminRolesAsync.php', recargarTablasYModales);
                        }
                    });
                }
            }); 
        });
    }

    // EVENTOS DE BOTONES DINÁMICOS (Dar de Baja y Eliminar)
    document.addEventListener('click', function (e) {
        
        // 1. Botón Dar de Baja (Usuarios)
        const btnBaja = e.target.closest('.btn-baja');
        if (btnBaja) {
            const id = btnBaja.getAttribute('data-id');
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
                        <input type="hidden" name="id_sub" value="${idSubActual}">
                    `;
                    asyncProcessForm(formVirtual, '_controller/AdminUsuariosAsync.php', recargarTablasYModales);
                }
            });
        }

        // 2. Botón Eliminar / Dar de Baja (Roles)
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
                    asyncProcessForm(formVirtual, '_controller/AdminRolesAsync.php', recargarTablasYModales);
                }
            });
        }
    });
});