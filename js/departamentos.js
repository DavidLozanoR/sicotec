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
    // Restricción reactiva: Nombre del Departamento
    if (inputNom) {
        // Mientras el usuario escribe (Reacciona en tiempo real)
        inputNom.addEventListener('input', function() {
            let valor = this.value;
            
            // 1. Convertir todo a mayúsculas automáticamente
            valor = valor.toUpperCase();
            
            // 2. Permitir SOLO letras (incluyendo acentos y Ñ) y espacios en blanco. Elimina números o símbolos.
            valor = valor.replace(/[^A-ZÁÉÍÓÚÑ\s]/g, '');
            
            // 3. Eliminar espacios al inicio (para que no pueda empezar con un espacio)
            valor = valor.replace(/^\s+/, '');
            
            // 4. Prevenir múltiples espacios (Si teclea dos espacios seguidos, los convierte en uno solo)
            valor = valor.replace(/\s{2,}/g, ' ');

            // 5. Validar longitud Máxima
            if(valor.length > 80){
                valor= valor.substring(0,80);
            }
            // Actualizar el valor en el input
            this.value = valor;
        });

        // Cuando el usuario sale del campo (pierde el foco)
        inputNom.addEventListener('blur', function() {
            // Elimina cualquier espacio que haya quedado colgando al final
            this.value = this.value.trim();
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
// ==========================================
// MANEJO DE TABLAS (BÚSQUEDA Y PAGINACIÓN)
// ==========================================
$(document).ready(function() {
    
    const registrosPorPagina = 10;
    let paginaActualDeptos = 1;
    let paginaActualPuestos = 1;

    // 1. BUSCADOR EN VIVO DEPARTAMENTOS
    $('#buscadorDeptos').on('keyup', function() {
        let valor = $(this).val().toLowerCase().trim();
        $('#tablaDeptos tbody tr.fila-registro').each(function() {
            let textoFila = $(this).text().toLowerCase();
            if (textoFila.includes(valor)) {
                $(this).removeClass('oculto-por-busqueda');
            } else {
                $(this).addClass('oculto-por-busqueda');
            }
        });
        actualizarDeptos(1); // Resetear a la página 1 y recalcular
    });

    // 2. BUSCADOR EN VIVO PUESTOS
    $('#buscadorPuestos').on('keyup', function() {
        let valor = $(this).val().toLowerCase().trim();
        $('#tablaPuestos tbody tr.fila-registro').each(function() {
            let textoFila = $(this).text().toLowerCase();
            if (textoFila.includes(valor)) {
                $(this).removeClass('oculto-por-busqueda');
            } else {
                $(this).addClass('oculto-por-busqueda');
            }
        });
        actualizarPuestos(1); // Resetear a la página 1 y recalcular
    });

    // 3. FUNCIÓN DE PAGINACIÓN ADAPTADA A BÚSQUEDA
    function renderizarPaginacion(idTabla, paginaActual, callbackPagina) {
        const $todasLasFilas = $(`${idTabla} tbody tr.fila-registro`);
        // Seleccionamos solo las filas que NO fueron filtradas por la búsqueda
        const $filasVisibles = $todasLasFilas.not('.oculto-por-busqueda');
        
        const totalRegistros = $filasVisibles.length;
        const totalPaginas = Math.ceil(totalRegistros / registrosPorPagina);

        // Ocultar absolutamente todas las filas primero
        $todasLasFilas.hide();
        
        // Calcular el bloque a mostrar y hacerlo visible
        const inicio = (paginaActual - 1) * registrosPorPagina;
        const fin = inicio + registrosPorPagina;
        $filasVisibles.slice(inicio, fin).show();

        // Control del mensaje de "No hay resultados"
        $(`${idTabla} tbody tr.fila-sin-resultados`).remove(); 
        if (totalRegistros === 0 && $todasLasFilas.length > 0) {
            $(`${idTabla} tbody`).append('<tr class="fila-sin-resultados"><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-search fa-2x mb-2 opacity-25"></i><br>No se encontraron resultados para la búsqueda.</td></tr>');
        }

        // Actualizar contador de texto
        const textoInicio = totalRegistros === 0 ? 0 : inicio + 1;
        const textoFin = Math.min(fin, totalRegistros);
        $('#contador-registros').text(`Mostrando ${textoInicio} - ${textoFin} de ${totalRegistros} registros`);

        // Renderizar botones HTML
        const $paginacion = $('#paginacionTabla');
        $paginacion.empty();

        if (totalPaginas <= 1) return;

        // Botón Anterior
        const claseAnterior = paginaActual === 1 ? 'disabled' : '';
        $paginacion.append(`<li class="page-item ${claseAnterior}"><a class="page-link text-dark" href="#" data-page="${paginaActual - 1}">Anterior</a></li>`);

        // Botones de números
        for (let i = 1; i <= totalPaginas; i++) {
            const claseActiva = i === paginaActual ? 'active' : '';
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

    actualizarDeptos(1);

    // Control de navegación en pestañas
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#deptos') {
            actualizarDeptos(paginaActualDeptos);
        } else if (target === '#puestos') {
            actualizarPuestos(paginaActualPuestos);
        }
    });
});