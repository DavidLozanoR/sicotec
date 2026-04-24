document.addEventListener('DOMContentLoaded', () => {
    
    const modalDeptoEl = document.getElementById('modalFormDepto');
    if (!modalDeptoEl) return;

    const modalDepto = new bootstrap.Modal(modalDeptoEl);
    const formDepto = document.getElementById('formDepto');
    const idSub = document.getElementById('id_sub_dep') ? document.getElementById('id_sub_dep').value : 3;

    // --- CAMPOS ---
    const inputClave = document.getElementById('dep_clave');
    const inputNom = document.getElementById('dep_nom');

    // Restricción: Clave solo números
    if (inputClave) {
        inputClave.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    const limpiarEspacios = (str) => {
        if (!str) return '';
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

    // --- EVENTOS DE LA TABLA (Editar y Eliminar) ---
    document.body.addEventListener('click', (e) => {
        
        // 1. Botón Editar
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

        // 2. Botón Eliminar
        const btnEliminarDep = e.target.closest('.btn-eliminar-dep');
        if (btnEliminarDep) {
            e.preventDefault();
            const idDep = btnEliminarDep.dataset.id;
            const nomDep = btnEliminarDep.dataset.nom;
            const total = parseInt(btnEliminarDep.dataset.total);

            if (total > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se puede eliminar',
                    text: `El departamento "${nomDep}" tiene ${total} empleado(s) vinculado(s). Reasígnalos primero en el panel de Personal.`,
                    confirmButtonColor: '#9D2449'
                });
                return;
            }

            Swal.fire({
                title: '¿Eliminar Departamento?',
                html: `Estás a punto de eliminar definitivamente <b>${nomDep}</b>.<br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    const formData = new FormData();
                    formData.append('peticion', 'DELETE_DEP');
                    formData.append('id_sub', idSub);
                    formData.append('id_dep', idDep);

                    try {
                        const response = await fetch('_controller/DepartamentosAsync.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();

                        if (data.result === 1 || data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: data.msg,
                                confirmButtonColor: '#285C4D',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = window.location.href;
                            });
                        } else {
                            Swal.fire('Error', data.msg, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Hubo un problema de red.', 'error');
                    }
                }
            });
        }
    });

    // --- ENVIAR FORMULARIO (INSERTAR/ACTUALIZAR) ---
    formDepto.addEventListener('submit', async (e) => {
        e.preventDefault();

        inputClave.value = limpiarEspacios(inputClave.value);
        inputNom.value = limpiarEspacios(inputNom.value);

        if (!formDepto.checkValidity()) {
            formDepto.classList.add('was-validated');
            return;
        }

        if (inputNom.value.length < 5) {
            Swal.fire({ icon: 'warning', title: 'Nombre muy corto', text: 'El nombre debe tener al menos 5 caracteres.', confirmButtonColor: '#9D2449' });
            return;
        }

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
                    window.location.href = window.location.href;
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'No se pudo procesar la solicitud.', confirmButtonColor: '#9D2449' });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({ icon: 'error', title: 'Error de red', text: 'Problema de conexión con el servidor.', confirmButtonColor: '#9D2449' });
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = textoOriginal;
        }
    });
});

// ==========================================
// MANEJO DE TABLAS (PAGINACIÓN CLIENT-SIDE)
// ==========================================
$(document).ready(function() {
    
    const registrosPorPagina = 10;
    let paginaActualDeptos = 1;
    let paginaActualPuestos = 1;

    // Función principal de renderizado de paginación
    function renderizarPaginacion(idTabla, paginaActual, callbackPagina) {
        const $filas = $(`${idTabla} tbody tr.fila-registro`);
        const totalRegistros = $filas.length;
        const totalPaginas = Math.ceil(totalRegistros / registrosPorPagina);

        // Ocultar todas las filas y mostrar solo el bloque correspondiente
        $filas.hide();
        const inicio = (paginaActual - 1) * registrosPorPagina;
        const fin = inicio + registrosPorPagina;
        $filas.slice(inicio, fin).show();

        // Actualizar contador de texto
        const textoInicio = totalRegistros === 0 ? 0 : inicio + 1;
        const textoFin = Math.min(fin, totalRegistros);
        $('#contador-registros').text(`Mostrando ${textoInicio} - ${textoFin} de ${totalRegistros} registros`);

        // Renderizar botones HTML
        const $paginacion = $('#paginacionTabla');
        $paginacion.empty();

        if (totalPaginas <= 1) return; // No generar botones si hay 1 página o menos

        // Botón Anterior
        const claseAnterior = paginaActual === 1 ? 'disabled' : '';
        $paginacion.append(`<li class="page-item ${claseAnterior}"><a class="page-link text-dark" href="#" data-page="${paginaActual - 1}">Anterior</a></li>`);

        // Botones de números
        for (let i = 1; i <= totalPaginas; i++) {
            const claseActiva = i === paginaActual ? 'active' : '';
            // El color "guinda" requiere clases específicas, aquí se usa un estilo inline o clases de Bootstrap estándar
            const estiloActivo = i === paginaActual ? 'background-color: #9D2449; border-color: #9D2449; color: white;' : 'color: #333;';
            $paginacion.append(`<li class="page-item ${claseActiva}"><a class="page-link" href="#" style="${estiloActivo}" data-page="${i}">${i}</a></li>`);
        }

        // Botón Siguiente
        const claseSiguiente = paginaActual === totalPaginas ? 'disabled' : '';
        $paginacion.append(`<li class="page-item ${claseSiguiente}"><a class="page-link text-dark" href="#" data-page="${paginaActual + 1}">Siguiente</a></li>`);

        // Asignar eventos de clic
        $paginacion.find('a.page-link').off('click').on('click', function(e) {
            e.preventDefault();
            const $parent = $(this).parent();
            if ($parent.hasClass('disabled') || $parent.hasClass('active')) return;
            
            const nuevaPagina = parseInt($(this).attr('data-page'));
            callbackPagina(nuevaPagina);
        });
    }

    // Funciones enlazadoras
    function actualizarDeptos(pagina) {
        paginaActualDeptos = pagina;
        renderizarPaginacion('#tablaDeptos', paginaActualDeptos, actualizarDeptos);
    }

    function actualizarPuestos(pagina) {
        paginaActualPuestos = pagina;
        renderizarPaginacion('#tablaPuestos', paginaActualPuestos, actualizarPuestos);
    }

    // Inicialización primaria (pestaña activa por defecto)
    actualizarDeptos(1);

    // Control de navegación en pestañas para recargar la paginación pertinente
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#deptos') {
            actualizarDeptos(paginaActualDeptos);
        } else if (target === '#puestos') {
            actualizarPuestos(paginaActualPuestos);
        }
    });
}); 