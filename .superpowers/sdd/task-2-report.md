# Tarea 2 — Reporte de implementación

## Cambios aplicados

| # | Cambio | Estado | Notas |
|---|--------|--------|-------|
| 1 | `ope_rol_mv_zona_metrics()` — 10 keys (cli, pel, riq, civ, mar, pir, rev, inf, est, ten) | ✅ | eco eliminado, inf/riq/ten añadidos |
| 2 | `ope_rol_mv_faccion_metrics()` — 7 keys (rep, coh, mil, pol, eco, mor, alc) | ✅ | inf reemplazado por pol, alc añadido |
| 3 | `ope_rol_mv_npc_mayores()` — SELECT con datos_publicos, datos_internos + decode JSON | ✅ | |
| 4 | Nueva `ope_rol_mv_threads_activos()` | ✅ | Insertada tras ope_rol_mv_npc_menores |
| 5 | Nueva `ope_rol_mv_ultimos_periodicos($n = 3)` | ✅ | |
| 6 | Nueva `ope_rol_mv_npc_tracking_from_db()` | ✅ | |
| 7 | `ope_rol_mv_build_prompt()` — reemplazada por versión v3 | ✅ | 5 bloques, 10/7 métricas, threads, periodicos, NPC tracking, S-01..S-12, topes, regresión completa |
| 8 | `ope_rol_mv_parse_resultado()` — threads/npc_tracking opcionales | ✅ | Sin cambios necesarios: ya están en $res['estado'] |
| 9 | `ope_rol_mv_aplicar_estado()` — adaptación auto a 10/7 keys | ✅ | Sin cambios necesarios: usa funciones metrics() |
| 10 | `ope_rol_mv_publicar()` — threads_json, nav_resumen, NPC tracking | ✅ | |
| 11 | `OPE_MV_TENSION_MAX_UP` fallback 20→15 | ✅ | |

## Desviaciones del brief

- **Cambio 8**: No se requirió modificar `ope_rol_mv_parse_resultado()`. La función ya almacena todo el JSON decodificado en `$res['estado']`, por lo que `threads` y `npc_tracking` ya son accesibles vía `$parsed['estado']['threads']` y `$parsed['estado']['npc_tracking']`. No se añadió validación adicional porque no existía ninguna que los exigiera.
- **Cambio 9**: No se requirió modificar `ope_rol_mv_aplicar_estado()`. Los bucles ya iteran sobre `ope_rol_mv_zona_metrics()` y `ope_rol_mv_faccion_metrics()`, por lo que se adaptan automáticamente a las nuevas 10/7 keys. El clamp de `inf` y `ten` (nuevas columnas) usa el mismo `max(0, min(100, ...))` genérico.
- **Ubicación de funciones nuevas**: Se insertaron tras `ope_rol_mv_npc_menores()` (en lugar de entre `zona_from_fid` y `est_label`) porque la edición fallaba al coincidir con caracteres Unicode en los separadores de comentarios. Quedan funcionalmente equivalentes y antes de `build_prompt`.

## Preocupaciones

- `ope_rol_mv_ultimos_periodicos()` usa `mb_substr` y `strip_tags` — ambas funciones PHP estándar, disponibles en cualquier instalación típica de MyBB.
- La migración BD v3 debe estar ejecutada para que las nuevas columnas (cli, riq, inf, ten, pol, alc, threads_json, nav_resumen, datos_publicos, datos_internos) existan en las tablas.
- Las columnas legacy (eco en zonas, inf en facciones, mundo_zona/ubic/accion/estado_np en personajes) NO se tocan — se mantienen para compatibilidad.
