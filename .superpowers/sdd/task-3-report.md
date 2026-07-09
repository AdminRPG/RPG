# Task 3 Report — Panel Staff mundo-vivo.php (v3)

## Summary
All 6 changes from the brief implemented in `mundo-vivo.php`.

## Changes applied

1. **Tablero** — No code changes needed. `$zMetricsDef = ope_rol_mv_zona_metrics()` returns 10 keys, `$fMetricsDef = ope_rol_mv_faccion_metrics()` returns 7 keys. Both foreach loops on lines ~336 and ~364 already iterate dynamically over the metric definitions.

2. **NPCs JSON editor** — After each NPC's existing `npc_ubic` form, added a new independent `<form method="post" class="mv-npc">` with `mv_action = npc_json_save`. Contains two `<details>` blocks with textareas for `datos_publicos[PID]` and `datos_internos[PID]`. Includes a "Guardar JSON" button.

3. **Threads en vista previa** — Added `<h3 class="mv-prev-h">Hilos narrativos activos</h3>` section after "Tramas abiertas". Renders `$preview['estado']['threads']` as `mv-thrcard` cards showing título, estado, descripción, proxima_evolucion.

4. **NPC tracking diff en vista previa** — Added `<h3 class="mv-prev-h">Tracking de NPCs mayores</h3>` section after threads. Reads `$preview['estado']['npc_tracking']`, gets current tracking via `ope_rol_mv_npc_tracking_from_db()`, shows salud/moral/ubicación diff with `→` arrows using `.mv-diff-block` / `.mv-diff-line` classes.

5. **Nav resumen** — Added POST handler `save_nav_resumen` that updates `rol_mv_ciclos.nav_resumen`. Added a new `<div class="plate">` in the Generar tab with a textarea and guardar button.

6. **Indicaciones del staff** — Added note: "Las indicaciones no se heredan automáticamente. Cópialas manualmente del mes anterior si es necesario." right after the indicaciones form.

## Verification
- PHP lint: no syntax errors
- Existing functionality preserved (all original forms, actions, and render logic unchanged)
- All new CSS classes (`mv-npc-json`, `mv-thrcard`, `mv-diff-block`, etc.) follow existing naming conventions