document.addEventListener('DOMContentLoaded', () => {
    const offcanvasElement = document.getElementById('offcanvasDiccionario');
    const tbody = document.getElementById('tbodyDiccionario');
    const btnAgregar = document.getElementById('btnAgregarNomenclatura');

    // Configuración de alerta no intrusiva (Toast)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });

    // Cargar datos cuando se abre el Offcanvas
    if (offcanvasElement) {
        offcanvasElement.addEventListener('show.bs.offcanvas', cargarDiccionario);
    }

    // Evento para agregar nueva nomenclatura (Inserción en blanco)
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
                    cargarDiccionario(); // Recargar la tabla para obtener el nuevo ID
                } else {
                    Toast.fire({ icon: 'error', title: data.message || 'Error al agregar' });
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    // Delegación de eventos para la edición en línea (Detectar Enter)
    if (tbody) {
        tbody.addEventListener('keydown', (e) => {
            // Verificar si el elemento objetivo es una celda editable y se presionó Enter
            if (e.target.tagName === 'TD' && e.target.isContentEditable && e.key === 'Enter') {
                e.preventDefault(); // Evitar el salto de línea en la celda
                
                const celda = e.target;
                const id = celda.getAttribute('data-id');
                const campo = celda.getAttribute('data-campo');
                const valor = celda.innerText.trim();

                // Quitar el foco de la celda
                celda.blur();

                // Enviar la actualización
                fetch('_controller/DiccionarioAsync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'actualizar', Id_nom: id, campo: campo, valor: valor })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Toast.fire({ icon: 'success', title: 'Guardado' });
                        // Efecto visual rápido de confirmación en la celda
                        celda.style.backgroundColor = '#d4edda';
                        setTimeout(() => celda.style.backgroundColor = '', 500);
                    } else {
                        Toast.fire({ icon: 'error', title: data.message || 'Error al guardar' });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    }

    // Función para obtener y pintar los registros
    function cargarDiccionario() {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Cargando...</td></tr>';

        fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leer' })
        })
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach(fila => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="nom" class="p-2">${fila.nom}</td>
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="signif" class="p-2">${fila.signif}</td>
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="notas" class="p-2">${fila.notas}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">No hay registros.</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error al cargar datos.</td></tr>';
        });
    }
});