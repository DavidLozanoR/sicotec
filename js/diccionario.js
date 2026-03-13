/**
 * Dictionary Module Handler
 * Manages the Offcanvas lifecycle, inline editing via ContentEditable, 
 * and asynchronous data synchronization with the backend.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ... initial variables ...

    /**
     * Rebuilds the dictionary table by fetching data from the API.
     * @param {boolean} focusNew If true, automatically focuses the first cell of the first row.
     */
    function cargarDiccionario(focusNew = false) {
        fetch('_controller/DiccionarioAsync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'leer' })
        })
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.status === 'success' && data.data.length > 0) {
                data.data.forEach((fila, index) => {
                    /** * Dynamic row generation. 
                     * ContentEditable is used for inline editing without input elements. 
                     */
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="nom" class="p-2">${fila.nom}</td>
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="signif" class="p-2">${fila.signif}</td>
                        <td contenteditable="true" data-id="${fila.Id_nom}" data-campo="notas" class="p-2">${fila.notas}</td>
                    `;
                    tbody.appendChild(tr);

                    /** Auto-focus logic for UX improvement when adding new rows */
                    if (focusNew && index === 0) {
                        const cell = tr.querySelector('td');
                        setTimeout(() => {
                            cell.focus();
                            document.execCommand('selectAll', false, null);
                        }, 100);
                    }
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center">No hay registros.</td></tr>';
            }
        });
    }

    /** * Keydown Event Delegation 
     * Intercepts the 'Enter' key on editable cells to trigger the update API call.
     */
    tbody.addEventListener('keydown', (e) => {
        if (e.target.tagName === 'TD' && e.target.isContentEditable && e.key === 'Enter') {
            e.preventDefault(); // Stop line break in cell
            
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
            // ... toast feedback logic ...
        }
    });
});