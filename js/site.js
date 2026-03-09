/*
 * Diálogo de respuesta SweetAlert2 que ejecuta una operación ajax enviando un formulario vía POST.
 * @param {form} form - La referencia al formulario.
 * @param {string} actionUrl - La dirección que procesará el formulario vía POST.
 * @param {string} titleProcess - El título del díalogo de procesamiento.
 * @param {string} textProcess - El texto del díalogo de procesamiento.
 * @param {string} titleResult - El título del díalogo de resultado.
 * @param {function} funcSuccess - Función a ejecutar luego de un procesamiento exitoso.
 * @param {function} argsFuncSuccess - Argumentos de la función a ejecutar.
 */
function asyncProcessForm(form, actionUrl, arg3 = "Procesando", textProcess = "Por favor espere...", titleResult = "¡Éxito!", funcSuccess = null, argsFuncSuccess = null) {
    
    let titleProcess = "Procesando";
    let callback = funcSuccess;

    // MAGIA: Detectar si el 3er parámetro es la función (como en usuarios) o es un texto (formato original)
    if (typeof arg3 === 'function') {
        callback = arg3;
    } else if (typeof arg3 === 'string') {
        titleProcess = arg3;
    }

    const formData = new FormData(form);
    
    // Aseguramos la ruta absoluta si la constante SITE_URL existe
    const urlAbsoluta = (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + actionUrl;

    Swal.fire({
        title: titleProcess,
        text: textProcess,
        icon: "info",
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: async () => {
            Swal.showLoading(); 
            try {
                // Enviar la petición asíncrona usando la URL absoluta
                const response = await fetch(urlAbsoluta, {
                    method: "POST",
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Error: ${response.statusText}`);
                }

                const res = await response.json(); 

                Swal.hideLoading(); 

                // 1. Caso Integridad (Error 99)
                if (res.result === 99) {
                    if (callback != null) {
                        callback(res); 
                    }
                    return; 
                }
                
                // 2. Caso Éxito (result = 1)
                if (res.result === 1) {
                    Swal.fire({
                        icon: "success", // Corregido: "success" en lugar de "Éxito" para que salga el icono verde
                        title: (typeof arg3 === 'string') ? titleResult : "Hecho",
                        text: res.msg,
                        confirmButtonColor: "#6C1D45" // Tu color institucional
                    }).then(() => {
                        // Si hay función personalizada (como la de recargarTablas), la ejecuta. Si no, recarga la página.
                        if (typeof callback === "function") {
                            callback(res);
                        } else {
                            location.reload();
                        }
                    });
                } else if (res.result === 2) {
                    if (typeof callback === "function") {
                        callback(res); // Le pasamos la respuesta al JS para que saque el modal de confirmación
                    }
                }
                // 3. Caso Error General (result = 0)
                else {
                    // Cambiamos el título a algo más genérico que "Acceso Denegado"
                    let tituloError = res.msg && res.msg.toLowerCase().includes("acceso") ? "Acceso Denegado" : "Atención";
                    
                    Swal.fire({
                        icon: "error",
                        title: tituloError,
                        text: res.msg || "Hubo un problema con el procesamiento.",
                        allowOutsideClick: false, 
                        allowEscapeKey: false,    
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#d33" 
                    });
                }
            } catch (error) {
                Swal.hideLoading();
                Swal.fire({
                    icon: "error",
                    title: "Error de Conexión",
                    text: error.message || "Error inesperado durante el procesamiento.",
                });
            }
        },
    });
}