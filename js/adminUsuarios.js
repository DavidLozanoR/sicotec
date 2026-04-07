document.addEventListener("DOMContentLoaded", function () {
    
    const mainContainer = document.getElementById('main-admin-container');
    const idSubActual = mainContainer ? mainContainer.getAttribute('data-id-sub') : '';

    const inputNomUsuar = document.getElementById('nom_usuar_input');
    if (inputNomUsuar) {
        inputNomUsuar.addEventListener('input', function () {
            let texto = this.value.replace(/\s/g, '').replace(/^[^a-zA-ZáéíóúÁÉÍÓÚñÑ]+/, '');
            if (texto.length > 0 && !/^[a-zA-Z]/.test(texto)) texto = texto.substring(1);
            this.value = texto;
        });
    }

    const nameInputs = ['nombres_input', 'ape_pa_input', 'ape_ma_input'];
    nameInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function () {
                let texto = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '').replace(/^\s+/, '').replace(/\s{2,}/g, ' ');
                this.value = texto;
            });
        }
    });

    const inputPassword = document.getElementById('password_input');
    if (inputPassword) {
        inputPassword.addEventListener('input', function () {
            this.value = this.value.replace(/\s/g, ''); 
        });
    }

    function recargarTablaUsuarios(res) {
        const modalU = bootstrap.Modal.getInstance(document.getElementById('modalUsuario'));
        if (modalU) modalU.hide();
        
        $('#contenedor-tabla-usuarios').load(window.location.href + ' #contenedor-tabla-usuarios > *');

        if (res && res.update_current_user) {
            document.querySelectorAll('.name-display-global').forEach(elemento => {
                elemento.textContent = res.nuevo_nombre;
            });
        }
    }

    const modalUsuario = document.getElementById('modalUsuario');
    const formUsuario = document.getElementById('formUsuario');
    
    if (modalUsuario) {
        modalUsuario.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const esEdicion = button.classList.contains('btn-editar');
            
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
                
                const inputPassword = document.getElementById('password_input');
                inputPassword.required = false;

                const id_rol_editado = button.getAttribute('data-id_rol');
                
                if (id_rol_editado == '1') {
                    optAdmin.disabled = false;
                    optAdmin.style.display = 'block';
                    selectRol.value = '1';
                    selectRol.disabled = true; 
                    hiddenRol.value = '1';
                    hiddenRol.disabled = false; 

                    // --- NUEVO: Bloquear la contraseña para el Administrador ---
                    inputPassword.disabled = true;
                    inputPassword.value = '';
                    inputPassword.placeholder = 'Protegida (No modificable)';
                } else {
                    optAdmin.disabled = true;
                    optAdmin.style.display = 'none';
                    selectRol.disabled = false;
                    selectRol.value = id_rol_editado;
                    hiddenRol.disabled = true;

                    // --- Habilitar contraseña para otros roles ---
                    inputPassword.disabled = false;
                    inputPassword.placeholder = '';
                }
            } else {
                document.getElementById('modalUsuarioLabel').textContent = 'Nuevo Usuario';
                document.getElementById('peticion_input').value = 'INSERT_USUARIO';
                document.getElementById('id_usuar_input').value = '';
                
                // --- Resetear y requerir contraseña para registros nuevos ---
                const inputPassword = document.getElementById('password_input');
                inputPassword.required = true;
                inputPassword.disabled = false;
                inputPassword.placeholder = '';

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
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated'); 
                return; 
            }
            this.classList.remove('was-validated'); 
            this.querySelector('.dynamic-id-sub').value = idSubActual;
            
            // Interceptamos la respuesta para manejar la reactivación
            asyncProcessForm(this, '_controller/AdminUsuariosAsync.php', function(res) {
                if (res.result === 1) {
                    // Éxito normal
                    recargarTablaUsuarios(res);
                } else if (res.result === 2) {
                    // Usuario inactivo detectado, lanzamos SweetAlert de confirmación
                    Swal.fire({
                        title: 'Usuario Inactivo Detectado',
                        text: res.msg,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#6C1D45',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, reactivar cuenta',
                        cancelButtonText: 'No, cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Cambiamos la petición a REACTIVAR
                            document.getElementById('peticion_input').value = 'REACTIVATE_USER';
                            // Asignamos el ID del usuario inactivo que nos mandó PHP
                            document.getElementById('id_usuar_input').value = res.id_usuar_reactivar;
                            
                            // Volvemos a procesar el formulario automáticamente
                            asyncProcessForm(formUsuario, '_controller/AdminUsuariosAsync.php', recargarTablaUsuarios);
                        }
                    });
                }
            });
        });
    }

    document.addEventListener('click', function (e) {
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
                    asyncProcessForm(formVirtual, '_controller/AdminUsuariosAsync.php', recargarTablaUsuarios);
                }
            });
        }
    });
});