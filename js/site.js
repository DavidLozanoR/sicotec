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

// const SITE_URL = "<?php echo SITE_URL; ?>";

function asyncProcessForm(form, actionUrl, titleProcess, textProcess, titleResult, funcSuccess=null, argsFuncSuccess=null) {
	const formData = new FormData(form); // Crear objeto FormData con el formulario

	Swal.fire({
		title: titleProcess,
		text: textProcess,
		icon: "info",
		allowOutsideClick: () => !Swal.isLoading(),
		showConfirmButton: false,
		didOpen: async () => {
			Swal.showLoading(); // Mostrar estado de carga
			try {
				// Enviar la petición asíncrona
				const response = await fetch(actionUrl, {
					method: "POST",
					body: formData,
				});

				// Verificar si la respuesta es válida
				if (!response.ok) {
					throw new Error(`Error: ${response.statusText}`);
				}

				const res = await response.json(); // Parsear respuesta JSON

				Swal.hideLoading(); // Ocultar el estado de carga

				if (res.result === 99) {
					if (funcSuccess != null) {
						funcSuccess(res); // Pass the whole response (including kernel_id) to your callback
					}
					return; // Stop execution here
				}
				// Verificar el resultado del procesamiento
				if (res.result === 1) {
					Swal.fire({
						icon: "success",
						title: titleResult,
						text: res.msg,
						timer: 3000,
						timerProgressBar: true,
						showConfirmButton: true,
					}).then(() => {
						// Ejecutar la función de éxito si fue proporcionada
						if (typeof funcSuccess === "function") {
            				funcSuccess(res);
						}
					});
				} else {
					// Mostrar error en caso de fallo
					Swal.fire({
						icon: "error",
						title: "Error",
						text: res.msg || "Hubo un problema con el procesamiento.",
					});
				}
			} catch (error) {
				// Manejo de errores de la petición
				Swal.hideLoading();
				Swal.fire({
					icon: "error",
					title: "Error",
					text: error.message || "Error inesperado durante el procesamiento.",
				});
			}
		},
	});
}
