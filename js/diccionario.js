document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('tbodyDiccionario');
    const btnAgregar = document.getElementById('btnAgregarNomenclatura');

    /**
     * Rebuilds the table. No 'Acción' column.
     */
    function cargarDiccionario(focusNew = false) {
        fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leer' })
        })
        .then(response => response.text()) // Get text first to debug empty responses
        .then(text => {
            try {
                const data = JSON.parse(text);
                tbody.innerHTML = '';
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach((fila, index) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="nom" class="p-2">${fila.nom}</td>
                            <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="signif" class="p-2">${fila.signif}</td>
                            <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="notas" class="p-2">${fila.notas}</td>
                        `;
                        tbody.appendChild(tr);

                        if (focusNew && index === 0) {
                            const cell = tr.querySelector('td');
                            setTimeout(() => {
                                cell.focus();
                                document.execCommand('selectAll', false, null);
                            }, 50);
                        }
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center">No hay registros.</td></tr>';
                }
            } catch (e) {
                console.error("Server sent invalid JSON:", text);
            }
        });
    }

    if (btnAgregar) {
        btnAgregar.addEventListener('click', () => {
            fetch('_controller/DiccionarioAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'insertar' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    cargarDiccionario(true);
                }
            })
            .catch(err => console.error("Error inserting:", err));
        });
    }

    // Keydown delegation for Enter key...
    tbody.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'TD' && e.target.isContentEditable && e.key === 'Enter') {
            e.preventDefault();
            const celda = e.target;
            const id = celda.getAttribute('data-id');
            const campo = celda.getAttribute('data-campo');
            const valor = celda.innerText.trim();
            celda.blur();

            fetch('_controller/DiccionarioAsync.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'actualizar', Id_nom: id, campo: campo, valor: valor })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    celda.style.backgroundColor = '#d4edda';
                    setTimeout(() => celda.style.backgroundColor = '', 500);
                }
            });
        }
    });
});