/*
 * Diálogo de respuesta SweetAlert2 que ejecuta una operación ajax enviando datos al servidor.
 * @param {HTMLFormElement|FormData|Object} formOrData - El formulario, FormData o un objeto JSON con los datos.
 * @param {string} actionUrl - La dirección que procesará la petición.
 * @param {string|function} arg3 - El título del diálogo o la función callback.
 * @param {string} textProcess - El texto del diálogo de procesamiento.
 * @param {string} titleResult - El título del diálogo de resultado.
 * @param {function} funcSuccess - Función a ejecutar luego de un procesamiento exitoso.
 */
function asyncProcessForm(formOrData, actionUrl, arg3 = "Procesando", textProcess = "Por favor espere...", titleResult = "¡Éxito!", funcSuccess = null) {
    
    let titleProcess = "Procesando";
    let callback = funcSuccess;

    // MAGIA: Detectar si el 3er parámetro es la función (como en usuarios) o es un texto (formato original)
    if (typeof arg3 === 'function') {
        callback = arg3;
    } else if (typeof arg3 === 'string') {
        titleProcess = arg3;
    }

    let bodyData;
    let headers = {};

    if (formOrData instanceof HTMLFormElement) {
        bodyData = new FormData(formOrData);
    } else if (formOrData instanceof FormData) {
        bodyData = formOrData;
    } else if (typeof formOrData === 'object' && formOrData !== null) {
        bodyData = JSON.stringify(formOrData);
        headers['Content-Type'] = 'application/json';
    } else {
        console.error("El primer parámetro debe ser un HTMLFormElement, FormData o un Objeto JSON.");
        return;
    }
    
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
                // Enviar la petición asíncrona
                const fetchOptions = {
                    method: "POST",
                    body: bodyData,
                };
                
                if (Object.keys(headers).length > 0) {
                    fetchOptions.headers = headers;
                }
                
                const response = await fetch(urlAbsoluta, fetchOptions);

                if (!response.ok) {
                    throw new Error(`Error: ${response.statusText}`);
                }

                const res = await response.json(); 

                Swal.hideLoading(); 

                // Estandarizar lectura de respuestas (Soporta Backend nuevo con 'status' y antiguo con 'result')
                const isSuccess = (res.result === 1 || res.status === "success");
                const isWarning = (res.result === 2 || res.status === "exists_inactive");
                const isIntegrityError = (res.result === 99);

                // 1. Caso Integridad (Error 99)
                if (isIntegrityError) {
                    if (callback != null) {
                        callback(res); 
                    }
                    return; 
                }
                
                // 2. Caso Éxito
                if (isSuccess) {
                    Swal.fire({
                        icon: "success", 
                        title: (typeof arg3 === 'string') ? titleResult : "Hecho",
                        text: res.msg,
                        showConfirmButton: false, // Ocultamos el botón
                        timer: 2500, // Se cerrará automáticamente en 2.5 segundos
                        timerProgressBar: true
                    }).then(() => {
                        // El '.then()' se dispara automáticamente cuando el timer se acaba
                        if (typeof callback === "function") {
                            callback(res);
                        } else {
                            location.reload();
                        }
                    });
                } else if (isWarning) {
                    if (typeof callback === "function") {
                        callback(res); // Le pasamos la respuesta al JS para que saque el modal de confirmación manual
                    } else {
                        // Si no hay callback, mostramos la alerta por defecto (Fallback)
                        Swal.fire({ icon: "warning", title: "Atención", text: res.msg, confirmButtonColor: '#9D2449' });
                    }
                }
                // 3. Caso Error General
                else {
                    let tituloError = res.msg && res.msg.toLowerCase().includes("acceso") ? "Acceso Denegado" : "Atención";
                    
                    Swal.fire({
                        icon: "error",
                        title: tituloError,
                        text: res.msg || "Hubo un problema con el procesamiento.",
                        allowOutsideClick: false, 
                        allowEscapeKey: false,    
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#9D2449" 
                    });
                }
            } catch (error) {
                Swal.hideLoading();
                Swal.fire({
                    icon: "error",
                    title: "Error de Conexión",
                    text: error.message || "Error inesperado durante el procesamiento.",
                    confirmButtonColor: "#9D2449"
                });
            }
        },
    });
}