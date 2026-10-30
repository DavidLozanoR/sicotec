/**
 * Lógica asíncrona para el Catálogo de Dominios Institucionales
 * @author Angel David Lozano Rojas
 */

const ASYNC_URL = "_controller/DominiosAsync.php";
const idSubActual = document.getElementById("id_sub_actual") ? document.getElementById("id_sub_actual").value : 12;

// Referencias UI
const inputNombre = document.getElementById("txtNombreDominio");
const btnGuardar = document.getElementById("btnGuardarDominio");
const msgError = document.getElementById("msgErrorDominio");
const modalDom = new bootstrap.Modal(document.getElementById('modalDominio'));
const inputBuscar = document.getElementById("inputBuscarDominio");

// ==========================================
// 1. VALIDACIÓN REACTIVA EN TIEMPO REAL
// ==========================================
inputNombre.addEventListener('input', function() {
    // Limpieza automática para dominios: Forzar minúsculas y quitar espacios en blanco
    let val = this.value.toLowerCase().replace(/\s+/g, '');
    
    // Si el usuario pega una URL completa, le quitamos el http://, https:// y www.
    val = val.replace(/^(https?:\/\/)?(www\.)?/i, '');
    this.value = val;

    let valTrimmed = val.trim();

    // Reglas estrictas de validación
    if (valTrimmed === '') {
        mostrarError("El campo es obligatorio.");
    } else if (valTrimmed.length < 4) {
        mostrarError("El dominio debe tener al menos 4 caracteres.");
    } else if (valTrimmed.length > 100) {
        mostrarError("El dominio supera el máximo de 100 caracteres.");
    } else if (/^\d+$/.test(valTrimmed) || /^\d+\.\d+$/.test(valTrimmed)) {
        mostrarError("Un dominio no puede contener únicamente números.");
    } else if (!/^[a-z0-9.-]+\.[a-z]{2,}$/i.test(valTrimmed)) {
        mostrarError("Formato de dominio inválido (Ej: ineadom.gob.mx).");
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
// 2. FILTRADO / BÚSQUEDA DINÁMICA
// ==========================================
if (inputBuscar) {
    inputBuscar.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const filas = document.querySelectorAll('.fila-dominio');
        let contador = 0;

        filas.forEach(fila => {
            const texto = fila.querySelector('.nombre-dominio-txt').textContent.toLowerCase();
            if (texto.includes(term)) {
                fila.classList.remove('d-none');
                contador++;
            } else {
                fila.classList.add('d-none');
            }
        });

        const lblTotal = document.getElementById('lblTotalDominios');
        if (lblTotal) lblTotal.textContent = `Total: ${contador} registros filtrados`;
    });
}

// ==========================================
// 3. APERTURA DE MODALES
// ==========================================
function abrirModalDominio(id = null, nombre = '') {
    document.getElementById("idRegistroEdicion").value = id || ""; 
    document.getElementById("modalDominioTitle").innerHTML = id ? '<i class="fas fa-edit me-2"></i>Editar Dominio' : '<i class="fas fa-globe me-2"></i>Nuevo Dominio';
    
    inputNombre.value = nombre;
    limpiarError();
    
    // Si es nuevo registro, deshabilitar botón de guardado por defecto
    if(!nombre) btnGuardar.disabled = true; 
    
    modalDom.show();
}

// ==========================================
// 4. GUARDAR / EDITAR REGISTRO
// ==========================================
function guardarDominio() {
    const id_edicion = document.getElementById("idRegistroEdicion").value;
    const nombre = inputNombre.value.trim().toLowerCase();

    if (!nombre || btnGuardar.disabled) {
        mostrarError("Verifique el formato del dominio antes de guardar.");
        return;
    }

    const payload = {
        peticion: 'GUARDAR_DOMINIO',
        id_sub: idSubActual,
        id_edicion: id_edicion,
        nom_dom: nombre
    };

    asyncProcessForm(payload, ASYNC_URL, "Guardando...", "Procesando la información del dominio.", "¡Guardado!", function(res) {
        const isSuccess = (res.result === 1 || res.status === "success");
        const isWarning = (res.result === 2 || res.status === "exists_inactive");
        
        if (isSuccess) {
            location.reload();
        } else if (isWarning) {
            modalDom.hide();
            Swal.fire({
                title: 'Dominio Inactivo',
                text: res.msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#9D2449',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, reactivar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    reactivarDominio(res.id_reactivar || res.id_dom);
                } else {
                    modalDom.show(); 
                }
            });
        }
    });
}

// ==========================================
// 5. ELIMINAR REGISTRO (BAJA LÓGICA)
// ==========================================
function eliminarDominio(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "El dominio será dado de baja del catálogo activo.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9D2449',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const payload = { peticion: 'ELIMINAR_DOMINIO', id_dom: id, id_sub: idSubActual };
            asyncProcessForm(payload, ASYNC_URL, "Eliminando...", "Borrando registro...", "¡Eliminado!");
        }
    });
}

// ==========================================
// 6. REACTIVAR REGISTRO
// ==========================================
function reactivarDominio(id) {
    const payload = { peticion: 'REACTIVAR_DOMINIO', id_dom: id, id_sub: idSubActual };
    asyncProcessForm(payload, ASYNC_URL, "Reactivando...", "Por favor espere.", "¡Reactivado!");
}