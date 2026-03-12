document.addEventListener('DOMContentLoaded', () => {

    const formDispositivo = document.getElementById('formDispositivo');
    
    // Configuración de alerta Toast no intrusiva
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    if (formDispositivo) {
        formDispositivo.addEventListener('submit', function (e) {
            e.preventDefault();

            // 1. VALIDACIÓN HTML5 CON SOPORTE PARA PESTAÑAS (BOOTSTRAP TABS)
            if (!this.checkValidity()) {
                e.stopPropagation();
                
                // Encontrar el primer campo inválido
                const firstInvalid = this.querySelector(':invalid');
                if (firstInvalid) {
                    // Encontrar a qué panel (tab-pane) pertenece este campo
                    const tabPane = firstInvalid.closest('.tab-pane');
                    if (tabPane) {
                        const tabId = tabPane.getAttribute('id');
                        // Encontrar el botón de la pestaña que controla este panel
                        const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                        
                        if (tabButton) {
                            // Activar la pestaña usando la API de Bootstrap
                            const tab = new bootstrap.Tab(tabButton);
                            tab.show();
                            
                            // Dar foco al elemento inválido después de cambiar de pestaña
                            setTimeout(() => {
                                firstInvalid.focus();
                            }, 200);
                        }
                    }
                }
                
                this.classList.add('was-validated');
                Toast.fire({
                    icon: 'warning',
                    title: 'Por favor, completa los campos obligatorios.'
                });
                return;
            }

            // 2. RECOPILAR DATOS Y ENVIAR AL BACKEND
            const formData = new FormData(this);
            // Convertimos FormData a un objeto plano para enviarlo como JSON si es necesario
            const dataObj = Object.fromEntries(formData.entries());

            // Bloquear el botón para evitar doble envío
            const btnSubmit = document.getElementById('btnGuardarDispositivo');
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
            btnSubmit.disabled = true;

            // NOTA: Ajusta esta URL al controlador asíncrono real que procese el CRUD
            fetch('_controller/DispositivosAsync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataObj)
            })
            .then(response => response.json())
            .then(data => {
                if (data.result === 1 || data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: data.msg || 'El dispositivo se guardó correctamente.',
                        confirmButtonColor: '#9D2449'
                    }).then(() => {
                        // Redirigir al catálogo después de guardar
                        window.location.href = 'configuracion/dispositivos/'; 
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.msg || 'Ocurrió un problema al guardar.',
                        confirmButtonColor: '#9D2449'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.fire({
                    icon: 'error',
                    title: 'Error de comunicación con el servidor.'
                });
            })
            .finally(() => {
                // Restaurar botón
                btnSubmit.innerHTML = originalBtnText;
                btnSubmit.disabled = false;
            });
        });
    }

});