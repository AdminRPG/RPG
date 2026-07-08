### Task 6: Crear `alertas.php` — página de alertas con campana

**Files:**
- Create: `alertas.php`

**Interfaces:**
- Consumes: `rol_alertas` (Task 1)
- Consumes: `iforge_rol_navbar_html()` (Task 2 ya tiene campana)
- Produces: Página de listado de alertas con marcar leídas

**IMPORTANTE**: Código completo en el plan: `docs/superpowers/plans/2026-07-08-aprobacion-md-alertas.md`, sección "### Task 6: Crear `alertas.php`".

**Funcionalidad:**
- Lista de alertas con iconos por tipo (✉ mensaje, ✓ aprobado, ✕ rechazado, ↻ moderado)
- Alertas no leídas con borde izquierdo dorado
- Botón "Marcar todas como leídas"
- Botón ✓ para marcar una alerta individual
- Links "Ver" que redirigen al recurso (mensajes.php, personajes.php)
- Tipos de alerta: mensaje_nuevo, personaje_aprobado, personaje_rechazado, personaje_moderado, staff_asignado

**Verificación**: Navegar a `http://localhost/iforge/alertas.php`
