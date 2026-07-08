### Task 5: Crear `mensajes.php` — sistema de mensajes directos

**Files:**
- Create: `mensajes.php`

**Interfaces:**
- Consumes: `rol_mensajes` (Task 1), `rol_personajes` (personaje activo), `rol_alertas` (Task 1)
- Consumes: `iforge_rol_navbar_html()` (Task 2 ya tiene enlace a mensajes)
- Consumes: `$mybb->user['iforge_active_pid']` (plugin iforge_rol)
- Produces: Interfaz de bandeja MD con hilos, lectura y respuesta

**IMPORTANTE**: El código completo está en el plan: `docs/superpowers/plans/2026-07-08-aprobacion-md-alertas.md`, sección "### Task 5: Crear `mensajes.php`". Lee esa sección para el código PHP exacto.

**Funcionalidad:**
- Lista de hilos de conversación a la izquierda (sidebar)
- Vista de mensajes estilo chat con burbujas a la derecha
- Formulario de nuevo mensaje (selector de destinatario + asunto + cuerpo)
- Respuesta en hilo existente
- Marcar hilos como leídos automáticamente al abrirlos
- Al crear mensaje, se genera alerta para el destinatario
- Badge de mensajes no leídos en cada hilo
- Personajes destino: solo aprobados

**Verificación**: Navegar a `http://localhost/iforge/mensajes.php` como usuario con personaje activo
