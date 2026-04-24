document.addEventListener('DOMContentLoaded', () => {
    
    // --- Referencias Modales ---
    const modalDeptoEl = document.getElementById('modalFormDepto');
    const modalPuestoEl = document.getElementById('modalFormPuesto');
    if (!modalDeptoEl || !modalPuestoEl) return;

    const modalDepto = new bootstrap.Modal(modalDeptoEl);
    const modalPuesto = new bootstrap.Modal(modalPuestoEl);
    const idSub = document.getElementById('id_sub_dep') ? document.getElementById('id_sub_dep').value : 3;

    // ==========================================
    // LÓGICA: DEPARTAMENTOS
    // ==========================================
    const formDepto = document.getElementById('formDepto');
    const btnNuevoDepto = document.getElementById('btnNuevoDepto');

    if (btnNuevoDepto) {
        btnNuevoDepto.addEventListener('click', () => {
            formDepto.reset();
            document.getElementById('dep_id').value = '';
            document.getElementById('tituloModalDepto').innerHTML = '<i class="fas fa-building me-2 text-guinda"></i>Nuevo Departamento';
            modalDepto.show();
        });
    }

    // ==========================================
    // LÓGICA: PUESTOS
    // ==========================================
    const formPuesto = document.getElementById('formPuesto');
    const btnNuevoPuesto = document.getElementById('btnNuevoPuesto');

    if (btnNuevoPuesto) {
        btnNuevoPuesto.addEventListener('click', () => {
            formPuesto.reset();
            document.getElementById('puesto_id').value = '';
            document.getElementById('tituloModalPuesto').innerHTML = '<i class="fas fa-briefcase me-2 text-guinda"></i>Nuevo Puesto';
            modalPuesto.show();
        });
    }

    // ==========================================
    // DELEGACIÓN DE EVENTOS (Botones Editar)
    // ==========================================
    document.body.addEventListener('click', (e) => {
        // Editar Departamento
        const btnEditDep = e.target.closest('.btn-editar-dep');
        if (btnEditDep) {
            e.preventDefault();
            document.getElementById('dep_id').value = btnEditDep.dataset.id;
            document.getElementById('dep_clave').value = btnEditDep.dataset.clave;
            document.getElementById('dep_nom').value = btnEditDep.dataset.nom;
            document.getElementById('tituloModalDepto').innerHTML = '<i class="fas fa-edit me-2 text-guinda"></i>Editar Departamento';
            modalDepto.show();
        }

        // Editar Puesto
        const btnEditPuesto = e.target.closest('.btn-editar-puesto');
        if (btnEditPuesto) {
            e.preventDefault();
            document.getElementById('puesto_id').value = btnEditPuesto.dataset.id;
            document.getElementById('puesto_nom').value = btnEditPuesto.dataset.nom;
            document.getElementById('tituloModalPuesto').innerHTML = '<i class="fas fa-edit me-2 text-guinda"></i>Editar Puesto';
            modalPuesto.show();
        }
    });

    // ==========================================
    // ENVÍO DE FORMULARIOS (AJAX Reutilizable)
    // ==========================================
    const procesarFormulario = async (formElement, accionInsertar, accionActualizar, modalInstance) => {
        formElement.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!formElement.checkValidity()) {
                formElement.classList.add('was-validated');
                return;
            }

            // Detectar si el primer input hidden (el ID) tiene valor
            const idValue = formElement.querySelector('input[type="hidden"]').value;
            const peticionAccion = (idValue !== '') ? accionActualizar : accionInsertar;

            const formData = new FormData(formElement);
            formData.append('peticion', peticionAccion);
            formData.append('id_sub', idSub);

            const btnSubmit = formElement.querySelector('button[type="submit"]');
            const textoOriginal = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

            try {
                // TODO se maneja en el mismo archivo Asíncrono
                const response = await fetch('DepartamentosAsync.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.result === 1 || data.status === 'success') {
                    alert(data.msg || 'Guardado correctamente.');
                    modalInstance.hide();
                    window.location.reload(); 
                } else {
                    alert('Error: ' + (data.msg || 'No se pudo procesar.'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión con el servidor.');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = textoOriginal;
            }
        });
    };

    // Vincular ambos formularios a la misma función pero con diferentes acciones
    procesarFormulario(formDepto, 'INSERT_DEP', 'UPDATE_DEP', modalDepto);
    procesarFormulario(formPuesto, 'INSERT_PUESTO', 'UPDATE_PUESTO', modalPuesto);
});