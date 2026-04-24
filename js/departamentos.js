document.addEventListener('DOMContentLoaded', () => {
    
    const modalDeptoEl = document.getElementById('modalFormDepto');
    if (!modalDeptoEl) return;

    const modalDepto = new bootstrap.Modal(modalDeptoEl);
    const formDepto = document.getElementById('formDepto');
    const idSub = document.getElementById('id_sub_dep') ? document.getElementById('id_sub_dep').value : 3;

    // --- CAMPOS ---
    const inputClave = document.getElementById('dep_clave');
    const inputNom = document.getElementById('dep_nom');

    // --- RESTRICCIONES Y LIMPIEZA DE INPUTS EN TIEMPO REAL ---

    // Clave: Solo números, máximo 20 (el max length ya está en HTML, pero aseguramos).
    inputClave.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Función global para limpiar espacios (al final, antes de validar/enviar)
    const limpiarEspacios = (str) => {
        if (!str) return '';
        // trim() quita inicio y fin. replace(/\s+/g, ' ') deja solo un espacio entre palabras.
        return str.trim().replace(/\s+/g, ' ');
    };

    // --- NUEVO DEPARTAMENTO ---
    const btnNuevoDepto = document.getElementById('btnNuevoDepto');
    if (btnNuevoDepto) {
        btnNuevoDepto.addEventListener('click', () => {
            formDepto.reset();
            formDepto.classList.remove('was-validated');
            document.getElementById('dep_id').value = '';
            document.getElementById('tituloModalDepto').innerHTML = '<i class="fas fa-building me-2 text-guinda"></i>Nuevo Departamento';
            modalDepto.show();
        });
    }

    // --- EDITAR DEPARTAMENTO ---
    document.body.addEventListener('click', (e) => {
        const btnEditDep = e.target.closest('.btn-editar-dep');
        if (btnEditDep) {
            e.preventDefault();
            formDepto.classList.remove('was-validated');
            document.getElementById('dep_id').value = btnEditDep.dataset.id;
            document.getElementById('dep_clave').value = btnEditDep.dataset.clave;
            document.getElementById('dep_nom').value = btnEditDep.dataset.nom;
            document.getElementById('tituloModalDepto').innerHTML = '<i class="fas fa-edit me-2 text-guinda"></i>Editar Departamento';
            modalDepto.show();
        }
    });

    // --- ENVIAR FORMULARIO (AJAX CON SWEETALERT2 Y VALIDACIONES) ---
    formDepto.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 1. Limpieza final de valores antes de procesar
        inputClave.value = limpiarEspacios(inputClave.value);
        inputNom.value = limpiarEspacios(inputNom.value);

        // 2. Validación Nativa de Bootstrap/HTML5
        if (!formDepto.checkValidity()) {
            formDepto.classList.add('was-validated');
            return;
        }

        // 3. Validaciones Custom (Largo de campos)
        if (inputNom.value.length < 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Nombre muy corto',
                text: 'El nombre del departamento debe tener al menos 5 caracteres.',
                confirmButtonColor: '#9D2449'
            });
            return;
        }

        if (inputClave.value !== '' && inputClave.value.length < 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Clave muy corta',
                text: 'Si decide ingresar una clave, esta debe tener al menos 2 números.',
                confirmButtonColor: '#9D2449'
            });
            return;
        }

        // 4. Preparar Petición
        const idValue = document.getElementById('dep_id').value;
        const peticionAccion = (idValue !== '') ? 'UPDATE_DEP' : 'INSERT_DEP';

        const formData = new FormData(formDepto);
        formData.append('peticion', peticionAccion);
        formData.append('id_sub', idSub);

        const btnSubmit = formDepto.querySelector('button[type="submit"]');
        const textoOriginal = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

        try {
            const response = await fetch('_controller/DepartamentosAsync.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.result === 1 || data.status === 'success') {
                modalDepto.hide();
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.msg || 'Guardado correctamente.',
                    confirmButtonColor: '#285C4D',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload(); 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.msg || 'No se pudo procesar la solicitud.',
                    confirmButtonColor: '#9D2449'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Comunicación',
                text: 'Hubo un problema al contactar con el servidor.',
                confirmButtonColor: '#9D2449'
            });
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = textoOriginal;
        }
    });
});