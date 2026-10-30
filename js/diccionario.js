document.addEventListener('DOMContentLoaded', () => {
    const offcanvasElement = document.getElementById('offcanvasDiccionario');
    const tbody = document.getElementById('tbodyDiccionario');
    const btnAgregar = document.getElementById('btnAgregarNomenclatura');
    const btnRecargar = document.getElementById('btnRecargarDiccionario');

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });

    cargarDiccionario();

    if (offcanvasElement) {
        offcanvasElement.addEventListener('show.bs.offcanvas', () => cargarDiccionario());
    }

    if (btnRecargar) {
        btnRecargar.addEventListener('click', () => {
            const icono = btnRecargar.querySelector('i');
            icono.classList.add('fa-spin');
            cargarDiccionario();
            setTimeout(() => icono.classList.remove('fa-spin'), 500);
        });
    }

    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            fetch('_controller/DiccionarioAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'insertar' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Toast.fire({ icon: 'success', title: 'Fila agregada' });
                    cargarDiccionario(true);
                } else {
                    // MODIFICADO: Mostrar mensaje real del servidor (ej. Acceso Denegado)
                    Swal.fire({ icon: 'error', title: 'Acción rechazada', text: data.message || 'Error al agregar', confirmButtonColor: '#9D2449' });
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    if (tbody) {
        // DETECTAR CAMBIOS PARA MOSTRAR EL DISQUETE
        tbody.addEventListener('input', (e) => {
            if (e.target.hasAttribute('contenteditable')) {
                const tr = e.target.closest('tr');
                const btnGuardar = tr.querySelector('.btn-guardar-nom');
                if (btnGuardar) {
                    btnGuardar.classList.remove('d-none');
                }
            }
        });

        tbody.addEventListener('click', (e) => {
            // BORRAR
            if (e.target.classList.contains('btn-borrar-nom')) {
                const id = e.target.getAttribute('data-id');
                
                Swal.fire({
                    title: '¿Eliminar nomenclatura?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#9D2449',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, borrar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const payload = { action: 'borrar', Id_nom: id };
                        asyncProcessForm(payload, '_controller/DiccionarioAsync.php', 'Borrando...', 'Por favor espere.', '¡Eliminado!', function(res) {
                            if (res.status === 'success' || res.result === 1) {
                                cargarDiccionario();
                            }
                        });
                    }
                });
            }

            // GUARDAR CON DISQUETE
            if (e.target.classList.contains('btn-guardar-nom')) {
                const celdaBoton = e.target;
                const tr = celdaBoton.closest('tr');
                const id = celdaBoton.getAttribute('data-id');
                ejecutarGuardadoMultiple(tr, id);
            }
        });

        // EVENTO ENTER (GUARDAR CON TECLADO)
        tbody.addEventListener('keydown', (e) => {
            if (e.target.hasAttribute('contenteditable') && e.key === 'Enter') {
                e.preventDefault(); 
                const celdaActual = e.target;
                const tr = celdaActual.closest('tr');
                const id = celdaActual.getAttribute('data-id');
                
                celdaActual.blur();
                ejecutarGuardadoMultiple(tr, id);
            }
        });
    }

    function ejecutarGuardadoMultiple(tr, id) {
        const valNom = tr.querySelector('div[data-campo="nom"]').innerText.trim();
        const valSignif = tr.querySelector('div[data-campo="signif"]').innerText.trim();
        const valNotas = tr.querySelector('div[data-campo="notas"]').innerText.trim();

        const divs = tr.querySelectorAll('div[contenteditable]');
        divs.forEach(d => d.setAttribute('contenteditable', 'false'));

        const req1 = fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar', Id_nom: id, campo: 'nom', valor: valNom })
        }).then(res => res.json());

        const req2 = fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar', Id_nom: id, campo: 'signif', valor: valSignif })
        }).then(res => res.json());

        const req3 = fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'actualizar', Id_nom: id, campo: 'notas', valor: valNotas })
        }).then(res => res.json());

        Promise.all([req1, req2, req3])
            .then(resultados => {
                const todoExito = resultados.every(r => r.status === 'success');
                if (todoExito) {
                    Toast.fire({ icon: 'success', title: 'Fila guardada' });
                    
                    // Ocultar el disquete tras guardar exitosamente
                    const btnGuardar = tr.querySelector('.btn-guardar-nom');
                    if (btnGuardar) btnGuardar.classList.add('d-none');

                    Array.from(tr.querySelectorAll('td')).forEach(td => {
                        td.style.backgroundColor = '#d4edda';
                        setTimeout(() => td.style.backgroundColor = '', 500);
                    });
                } else {
                    // MODIFICADO: Extraer el primer error real que devolvió el PHP (Permisos denegados)
                    const errorEncontrado = resultados.find(r => r.status === 'error' && r.message);
                    const msgError = errorEncontrado ? errorEncontrado.message : 'Error al actualizar uno o más campos';
                    
                    Swal.fire({ icon: 'error', title: 'Error al Guardar', text: msgError, confirmButtonColor: '#9D2449' });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({ icon: 'error', title: 'Error de comunicación' });
            })
            .finally(() => {
                divs.forEach(d => d.setAttribute('contenteditable', 'true'));
            });
    }

    function cargarDiccionario(focusNew = false) {
        fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leer' })
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                tbody.innerHTML = '';
                
                if (data.status === 'success') {
                    if (data.data.length > 0) {
                        data.data.forEach((fila, index) => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="p-2 align-middle">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-trash-alt text-danger btn-borrar-nom me-2 mt-1" 
                                           data-id="${fila.Id_nom}" 
                                           style="cursor: pointer;" 
                                           title="Eliminar nomenclatura"></i>
                                        <div contenteditable="true" data-id="${fila.Id_nom}" data-campo="nom" class="flex-grow-1" style="outline: none; min-height: 20px;">${fila.nom}</div>
                                    </div>
                                </td>
                                <td class="p-2 align-middle">
                                    <div contenteditable="true" data-id="${fila.Id_nom}" data-campo="signif" style="outline: none; min-height: 20px;">${fila.signif}</div>
                                </td>
                                <td class="p-2 align-middle">
                                    <div class="d-flex align-items-start">
                                        <div contenteditable="true" data-id="${fila.Id_nom}" data-campo="notas" class="flex-grow-1" style="outline: none; min-height: 20px;">${fila.notas}</div>
                                        <i class="fas fa-save text-success btn-guardar-nom ms-2 mt-1 d-none" 
                                           data-id="${fila.Id_nom}" 
                                           style="cursor: pointer; font-size: 1.1rem;" 
                                           title="Guardar fila"></i>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(tr);

                            if (focusNew && index === 0) {
                                const cell = tr.querySelector('div[contenteditable]');
                                setTimeout(() => {
                                    cell.focus();
                                    document.execCommand('selectAll', false, null);
                                }, 50);
                            }
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No hay nomenclaturas registradas.</td></tr>';
                    }
                } else {
                    // ==========================================
                    // SI NO TIENE PERMISO PARA LEER/MOSTRAR:
                    // ==========================================
                    tbody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-danger fw-bold"><i class="fas fa-lock fa-2x d-block mb-2 text-muted"></i>${data.message || 'Acceso denegado'}</td></tr>`;
                    
                    // Si no tiene permisos para ver, tampoco debería ver el botón de agregar
                    if (btnAgregar) btnAgregar.style.display = 'none';
                }
            } catch (e) {
                console.error("Server sent invalid JSON:", text);
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error de formato al procesar datos del servidor.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error fetch:', error);
        });
    }
});