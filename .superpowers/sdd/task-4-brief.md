### Task 4: Crear `revisar-personaje.php` — página de revisión visual para staff

**Files:**
- Create: `revisar-personaje.php`

**Interfaces:**
- Consumes: `rol_personajes` con `pid` vía GET, `iforge_rol_navbar_html()`, `iforge_rol_data.php`
- Produces: Página completa con ficha visual del personaje + botones Aprobar/Moderar/Rechazar
- Produce: Alerta para el jugador al aprobar/rechazar/moderar (tablas `rol_alertas` y `rol_mensajes` de Task 1)
- Produce: Mensaje directo al moderar (tabla `rol_mensajes` de Task 1)

**IMPORTANTE**: El código completo está en el plan: `docs/superpowers/plans/2026-07-08-aprobacion-md-alertas.md`, sección "Task 4: Crear revisar-personaje.php". Lee esa sección para obtener el código PHP exacto a escribir. El archivo debe usar el patrón Foundry Brutalism con la paleta iron/sea, tipografía Big Shoulders Display/Space Mono/Archivo.

**Notas de implementación:**
- El archivo se accede como `http://localhost/iforge/revisar-personaje.php?pid=N`
- Solo staff (staff_level >= 1) puede ver la página
- Si no hay personaje con ese pid, redirige a zona-staff.php
- La página incluye su propio CSS inline (como todas las páginas del proyecto)
- Usa `iforge_rol_navbar_html()` para el navbar (ya modificado en Task 2 con campana y enlaces)
- Las tablas de mensajes y alertas ya existen (Task 1)
- Los helpers `iforge_rol_alertas_no_leidas()` y `iforge_rol_mensajes_no_leidos()` ya existen (Task 2)
- Usa `verify_post_check()` para todos los POST
- Usa `htmlspecialchars_uni()` para todo output
- Usa `(int)` para todos los IDs en queries

**Verificación**: 
1. Crear un personaje desde crear-personaje.php (queda en estado 'revision')
2. Navegar a `http://localhost/iforge/revisar-personaje.php?pid=N`
3. Verificar que la ficha se ve completa y los botones Aprobar/Rechazar/Moderar funcionan
