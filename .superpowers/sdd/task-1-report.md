# Task 1 Report — Migración Mundo Vivo v3

## Estado: DONE

## Archivos creados
- `scripts/migrate-mundo-vivo-v3.php`

## Resumen
Script de migración idempotente que:
1. Añade columnas `cli`, `riq`, `inf`, `ten` a `rol_mv_zonas`
2. Añade columnas `pol`, `alc` a `rol_mv_facciones`
3. Añade columnas `threads_json`, `nav_resumen` a `rol_mv_ciclos`
4. Añade columnas `tipo_suceso`, `pe_estimado` a `rol_mv_eventos`
5. Añade columnas `datos_publicos`, `datos_internos` a `rol_personajes`
6. Siembra valores base para zonas y facciones (solo columnas nuevas, con guarda de default)
7. Abre ciclo del mes si no existe

Sigue el mismo patrón que v2: `field_exists()`, `$PREFIX`, `IN_MYBB`, idempotente.