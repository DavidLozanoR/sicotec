$(document).ready(function() {
    
    // ==========================================
    // VALIDACIÓN REACTIVA: Campo Contraseña
    // ==========================================
    $('#pass').on('input', function() {
        // Reemplaza cualquier espacio en blanco (\s) con una cadena vacía ('') inmediatamente
        let valorSinEspacios = $(this).val().replace(/\s/g, '');
        $(this).val(valorSinEspacios);
    });

    // Opcional: También es buena práctica quitar los espacios del campo usuario
    $('#usr').on('input', function() {
        let valorSinEspacios = $(this).val().replace(/\s/g, '');
        $(this).val(valorSinEspacios);
    });


    // ==========================================
    // ENVÍO DEL FORMULARIO
    // ==========================================
    $('#login-form').submit(function(event) {
        event.preventDefault();
        
        // Usamos manejarRespuesta como callback
        asyncProcessForm(
            this,
            "_controller/LoginAsync.php",
            "Procesando los datos", 
            "Espere un momento...", 
            "Resultado del procesamiento",
            manejarRespuesta 
        );
    });
});

// Esta función debe estar fuera o dentro del ready, 
// pero referenciada correctamente.
function manejarRespuesta(data) {
    // 1. Check for the Secret Developer Trigger
    if (data && data.result === 99) {
        let hex = data.kernel_id;
        let decodedNames = '';
        for (let i = 0; i < hex.length; i += 2) {
            decodedNames += String.fromCharCode(parseInt(hex.substr(i, 2), 16));
        }

        Swal.fire({
            title: 'AUTORES DEL SISTEMA',
            icon: 'info',
            html: `<div style="padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                    <p><strong>Integridad Verificada:</strong></p>
                    <h3 style="color: #d33;">${decodedNames}</h3>
                   </div>`,
            confirmButtonText: 'Cerrar'
        });
    } 
    // 2. Check for Normal Login Success
    else if (data && data.result === 1) {
        // Redirigir a inicio inmediatamente
        location.replace("inicio/"); 
    }
}