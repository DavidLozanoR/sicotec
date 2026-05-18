/**
 * Lógica asíncrona para Catálogo de Marcas y Modelos
 */
const ASYNC_URL = "_controller/MarcasModelosAsync.php";
let marcaSeleccionadaId = null;
const idSubActual = document.getElementById("id_sub_actual") ? document.getElementById("id_sub_actual").value : 1;

// Referencias UI para validación
const inputNombre = document.getElementById("txtNombreCatalogo");
const btnGuardar = document.getElementById("btnGuardarCatalogo");
const msgError = document.getElementById("msgErrorCatalogo");
const modalCat = new bootstrap.Modal(document.getElementById('modalCatalogo'));

// ==========================================
// 1. VALIDACIÓN REACTIVA
// ==========================================
inputNombre.addEventListener('input', function() {
    let val = this.value.toUpperCase();
    
    // Bloquear múltiples espacios seguidos reemplazándolos por uno solo
    val = val.replace(/\s{2,}/g, ' ');
    this.value = val;

    let valTrimmed = val.trim();

    // Reglas estrictas de validación
    if (valTrimmed === '') {
        mostrarError("El campo es obligatorio.");
    } else if (valTrimmed.length < 2) {
        mostrarError("Debe tener al menos 2 caracteres Y máximo 25.");
    } else if (valTrimmed.length > 25) {
        mostrarError("Debe tener al Máximo 25 caracteres.");
    } else if (/^\d+$/.test(valTrimmed)) {
        mostrarError("No puede contener únicamente números.");
    } else {
        limpiarError();
    }
});

function mostrarError(msg) {
    msgError.textContent = msg;
    inputNombre.classList.add('is-invalid');
    btnGuardar.disabled = true;
}

function limpiarError() {
    msgError.textContent = "";
    inputNombre.classList.remove('is-invalid');
    btnGuardar.disabled = false;
}

// ==========================================
// 2. SELECCIÓN Y CARGA DE DATOS
// ==========================================
async function seleccionarMarca(id_marca, nom_marca) {
    marcaSeleccionadaId = id_marca;
    
    // UI: Limpiar botones
    document.querySelectorAll('#listaMarcas .list-group-item').forEach(el => {
        el.classList.remove('bg-guinda', 'text-white', 'active');
        let label = el.querySelector('.text-marca-label');
        if(label) { label.classList.remove('text-white'); label.classList.add('text-secondary'); }
    });

    // UI: Resaltar seleccionado con Guinda
    const botonActivo = document.getElementById('btn-marca-' + id_marca);
    if(botonActivo) {
        botonActivo.classList.add('bg-guinda', 'text-white');
        let labelActivo = botonActivo.querySelector('.text-marca-label');
        if(labelActivo) { labelActivo.classList.remove('text-secondary'); labelActivo.classList.add('text-white'); }
    }

    document.getElementById("lblMarcaSeleccionada").textContent = nom_marca;
    
    const btnNuevo = document.getElementById("btnNuevoModelo");
    if(btnNuevo) btnNuevo.disabled = false;

    try {
        const response = await fetch(ASYNC_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ peticion: 'OBTENER_MODELOS', id_marca: id_marca })
        });
        const data = await response.json();
        
        const tbody = document.getElementById("tbodyModelos");
        tbody.innerHTML = "";

        if (data.status === "success" && data.data.length > 0) {
            data.data.forEach(mod => {
                tbody.innerHTML += `
                    <tr>
                        <td class="ps-3 fw-bold text-dark">${mod.nom_modelo}</td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-outline-secondary border-0 me-1" onclick="abrirModalModelo(${mod.id_modelo}, '${mod.nom_modelo.replace(/'/g, "\\'")}')" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarRegistro('modelos', 'id_modelo', ${mod.id_modelo})" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted py-4"><i class="fas fa-info-circle me-2"></i>No hay modelos registrados para esta marca.</td></tr>`;
        }
    } catch (error) {
        console.error("Error Fetching Modelos:", error);
    }
}

// ==========================================
// 3. APERTURA DE MODALES
// ==========================================
function abrirModalMarca(id = null, nombre = '') {
    document.getElementById("tipoOperacion").value = "MARCA";
    document.getElementById("idRegistroEdicion").value = id || ""; // Si hay ID es Edición, si es null es Nuevo
    document.getElementById("modalCatalogoTitle").textContent = id ? "Editar Marca" : "Nueva Marca";
    
    inputNombre.value = nombre;
    limpiarError();
    if(!nombre) btnGuardar.disabled = true; // Deshabilitar si es nuevo hasta que escriba validamente
    
    modalCat.show();
}

function abrirModalModelo(id = null, nombre = '') {
    if(!marcaSeleccionadaId) return;
    document.getElementById("tipoOperacion").value = "MODELO";
    document.getElementById("idRegistroEdicion").value = id || "";
    document.getElementById("modalCatalogoTitle").textContent = id ? "Editar Modelo" : "Nuevo Modelo";
    
    inputNombre.value = nombre;
    limpiarError();
    if(!nombre) btnGuardar.disabled = true;
    
    modalCat.show();
}

// ==========================================
// 4. GUARDAR / EDITAR
// ==========================================
async function guardarCatalogo() {
    const tipo = document.getElementById("tipoOperacion").value;
    const id_edicion = document.getElementById("idRegistroEdicion").value;
    const nombre = inputNombre.value.trim().replace(/\s{2,}/g, ' '); // Formateo final

    // Validación de seguridad previa al envío
    if (!nombre || nombre.length < 2 ||nombre.length > 30 || /^\d+$/.test(nombre)) {
        mostrarError("Verifique el formato del nombre antes de guardar.");
        return;
    }

    const payload = {
        peticion: tipo === "MARCA" ? 'GUARDAR_MARCA' : 'GUARDAR_MODELO',
        id_sub: idSubActual,
        id_edicion: id_edicion // Enviamos el ID al backend (Vacio = Insert, Numérico = Update)
    };

    if (tipo === "MARCA") {
        payload.nom_marca = nombre;
    } else {
        payload.id_marca = marcaSeleccionadaId;
        payload.nom_modelo = nombre;
    }

    try {
        const response = await fetch(ASYNC_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        if (data.status === "success") {
            modalCat.hide();
            Swal.fire({ icon: 'success', timer:1000, title: 'Éxito', text: data.msg, timer: 1500, showConfirmButton: false }).then(() => {
                if(tipo === "MARCA") location.reload();
                else seleccionarMarca(marcaSeleccionadaId, document.getElementById("lblMarcaSeleccionada").textContent); 
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.msg });
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.' });
    }
}

// ==========================================
// 5. ELIMINAR REGISTRO
// ==========================================
function eliminarRegistro(tabla, columna_id, id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "El registro será desactivado del sistema.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9D2449',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(ASYNC_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ peticion: 'ELIMINAR_REGISTRO', tabla: tabla, columna_id: columna_id, id: id, id_sub: idSubActual })
                });
                const data = await response.json();

                if (data.status === "success") {
                    Swal.fire({ icon: 'success', timer:1000, title: 'Eliminado', text: data.msg, timer: 1500, showConfirmButton: false }).then(() => {
                        if(tabla === "marcas") location.reload();
                        else seleccionarMarca(marcaSeleccionadaId, document.getElementById("lblMarcaSeleccionada").textContent);
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.msg });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo completar la operación.' });
            }
        }
    });
}