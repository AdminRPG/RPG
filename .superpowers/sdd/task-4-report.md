# Tarea 4 — Reporte de implementación

**Archivo:** `estado-mundo.php` (295 líneas, +45 netas)

## Cambios realizados

### 1. Render de zonas — 10 métricas en modal ✅
No requiere cambios. El bucle `foreach ($zMetrics as $k => $m)` en el modal (línea 208) ya itera dinámicamente sobre las 10 keys que devuelve `ope_rol_mv_zona_metrics()`. El teaser de tarjetas sigue usando 3 hardcodeadas (`est`, `mar`, `pir`) — es intencional.

### 2. Render de facciones — 7 métricas en tarjetas ✅
No requiere cambios. El bucle `foreach ($fMetrics as $k => $m)` (línea 118) ya itera dinámicamente sobre las 7 keys.

### 3. NPCs mayores — datos_publicos ✅
- **Filtro** (línea 167): añadido `|| !empty($n['datos_publicos'])` al `array_filter`.
- **Render** (líneas 173-191): extrae `datos_publicos` como `$pub`, calcula `$ubicPublica` con preferencia a `ubicacion_publica`, muestra título, descripción (truncada a 200 chars) y mantiene `mundo_accion`.

### 4. Tensión — top 3 + details ✅
- División en `$zt_top3` (array_slice, 0-3) y `$zt_rest` (desde índice 3).
- Top 3 se renderizan directamente.
- Resto dentro de `<details class="em-ten-more"><summary>Ver todas las tensiones (N más)</summary>...</details>`.

### 5. Nueva sección: Hilos del mundo ✅
- Líneas 146-164, después de arcos.
- Usa `ope_rol_mv_threads_activos()`, filtra por estado `activo`/`reabierto`.
- Render con clases `.em-arcos`/`.em-arco`/`.em-arco-st` existentes.
- Muestra título, tipo, descripción y facciones implicadas.

### 6. Enlace al último periódico ✅
- Línea 82: el nombre del periodo en el hero ahora es un `<a href="periodicos.php?c=CICLO_ID">`.

## Reglas
- Estilo existente mantenido (clases em-*, reveal, hero).
- No se modificó CSS.
- No se rompió funcionalidad existente.
