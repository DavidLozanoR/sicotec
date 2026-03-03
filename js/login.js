// Enviar formulario de inicio de sesión
    $('#login-form').submit(function(event) {
        event.preventDefault();
        // Llamada a tu función asyncProcessForm
        asyncProcessForm(this,"_controller/LoginAsync.php", //this->el formulario que desencadeno el evento
            "Procesando los datos", "Espere un momento...", "Resultado del procesamiento",regresaPrincipal);
        
    });

     function regresaPrincipal() {
        location.replace("");
    }