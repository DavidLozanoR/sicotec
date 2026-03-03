# Documentacion del Sistema de control de Soporte tecnico


---

## 1. Información General del Proyecto
### 1.1 Resumen Ejecutivo
**Sistema Control** es una solución integral basada en arquitectura web diseñada específicamente para el Departamento de Servicio Técnico del INEA. Su función primordial es centralizar, gestionar y auditar las intervenciones técnicas en las áreas de software, hardware y redes, garantizando la trazabilidad de cada solicitud.

### 1.2 Justificación
La implementación de este sistema responde a la necesidad de modernizar la gestión de incidencias. Al ser una plataforma 100% web, elimina la dependencia de software local y permite una administración técnica ubicua, mejorando los tiempos de respuesta y la integridad de la base de datos centralizada.

### 1.3 Objetivos del Sistema
- **Centralización:** Unificar el registro de servicios de soporte en una sola fuente de datos.
- **Accesibilidad:** Permitir el acceso multiplataforma (escritorio y móvil).
- **Optimización:** Mejorar la organización del flujo de trabajo del personal técnico.
- **Trazabilidad:** Mantener un historial detallado de servicios por cada expediente.
---

## 2. Especificaciones Técnicas
### 2.1 Stack Tecnológico
Se ha seleccionado un stack basado en tecnologías de código abierto con alta compatibilidad en entornos Windows.

| Componente | Tecnología | Versión / Detalle |
| ----- | ----- | ----- |
| **Lenguaje de Programación** | PHP | 8.x o superior |
| **Motor de Base de Datos** | MariaDB | 10.x o superior |
| **Frontend Framework** | Bootstrap | Responsivo / Mobile-first |
| **Lógica de Cliente** | JavaScript | Manipulación de DOM y validaciones |
| **Maquetado y Estilos** | HTML5 / CSS3 | Estándares web modernos |
### 2.2 Requisitos de Infraestructura
- **Servidor:** Entorno Windows con servidor web compatible (Apache/IIS).
- **Cliente:** Navegador web moderno con soporte para estándares HTML5.
---

## 3. Arquitectura Funcional (Módulos)
Basado en la estructura definida para el sistema, el software se divide en los siguientes bloques operativos esenciales:

- **Módulo de Expediente:** Gestión de la información técnica histórica.
- **Módulo de Agenda:** Programación de citas e inicio de servicios.
- **Módulo de Asistencia:** Control de presencia y atención técnica.
- **Módulo de Interesados y Necesidades:** Captura de requerimientos y perfiles de usuario.
- **Servicios Transversales:** Sistema de notificaciones vía correo electrónico y generación de reportes operativos.
---

### 4. Diagramas de caso de uso
Todos los diagramas fueros creados usando la herramienta PlantUML 
#### Login
![login.png](https://eraser.imgix.net/workspaces/XisKYPqPWIxA1PjjwWLq/ocaMKt4BqwZLXFPzQV6QTETS2C62/login_wMUmEGPN9kRwxDo13SDC0.png?ixlib=js-3.8.0 "login.png")

#### Consola del admin
![admin_console.png](https://eraser.imgix.net/workspaces/XisKYPqPWIxA1PjjwWLq/ocaMKt4BqwZLXFPzQV6QTETS2C62/admin_console_vQuQFyvwzTQWztrPIJ-Mn.png?ixlib=js-3.8.0 "admin_console.png")

#### Consola de los usuarios
![user_dashboard.png](https://eraser.imgix.net/workspaces/XisKYPqPWIxA1PjjwWLq/ocaMKt4BqwZLXFPzQV6QTETS2C62/user_dashboard_i5hbw2fTRx6CgctEVNyNr.png?ixlib=js-3.8.0 "user_dashboard.png")


