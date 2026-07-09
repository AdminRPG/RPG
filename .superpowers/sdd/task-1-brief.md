# Tarea 1: Script de migración BD — Mundo Vivo v3

## Contexto
Este proyecto es un foro MyBB. Las tablas de Mundo Vivo tienen prefijo `mybb_rol_mv_*` y `mybb_rol_personajes`.
Ya existe `scripts/migrate-mundo-vivo.php` (v1) y `scripts/migrate-mundo-vivo-v2.php` (v2).
Hay que crear `scripts/migrate-mundo-vivo-v3.php` siguiendo el mismo patrón (idempotente, usa `$db->field_exists()` para no duplicar).

## Qué hace el script

### 1. Añadir columnas a `rol_mv_zonas`
Las columnas existentes son: slug, nombre, est, mar, pir, rev, eco, civ, pel, notas, orden
Añadir:
- `cli` TINYINT UNSIGNED DEFAULT 60 COMMENT 'Clima 0-100'
- `riq` TINYINT UNSIGNED DEFAULT 50 COMMENT 'Riqueza 0-100'
- `inf` TINYINT UNSIGNED DEFAULT 20 COMMENT 'Influencia Inframundo 0-100'
- `ten` TINYINT UNSIGNED DEFAULT 20 COMMENT 'Tensión General 0-100'

### 2. Añadir columnas a `rol_mv_facciones`
Las columnas existentes son: slug, nombre, rep, coh, mil, inf, eco, mor, notas, orden
Añadir:
- `pol` TINYINT UNSIGNED DEFAULT 50 COMMENT 'Influencia Política 0-100'
- `alc` TINYINT UNSIGNED DEFAULT 50 COMMENT 'Alcance 0-100'

### 3. Añadir columnas a `rol_mv_ciclos`
Las columnas existentes son: ciclo_id, periodo, estado, indicaciones, prompt, resultado_raw, periodico_html, estado_json, noticia_titulo, noticia_html, imagenes_json, dateline, published_at
Añadir:
- `threads_json` LONGTEXT DEFAULT NULL COMMENT 'Array de hilos narrativos (JSON)'
- `nav_resumen` TEXT DEFAULT NULL COMMENT 'Resumen de viajes/navegación del ciclo'

### 4. Añadir columnas a `rol_mv_eventos`
Las columnas existentes son: evento_id, ciclo_id, pid, uid, tid, enlace, titulo, resumen, fid, zona_slug, estado, dateline
Añadir:
- `tipo_suceso` VARCHAR(20) DEFAULT NULL COMMENT 'S-01 a S-12 (clasificación de la IA)'
- `pe_estimado` TINYINT UNSIGNED DEFAULT NULL COMMENT 'Peso del Evento 1-10 (estimado por IA)'

### 5. Añadir columnas a `rol_personajes`
Las columnas de NPC ya existentes son: mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np
Añadir:
- `datos_publicos` TEXT DEFAULT NULL COMMENT 'JSON público: títulos, descripción, historia, relaciones, ubicación visible'
- `datos_internos` TEXT DEFAULT NULL COMMENT 'JSON interno (solo staff/IA): personalidad (6 ejes), metas, tracking'

### 6. Sembrar valores base para las nuevas columnas

**Zonas** (solo las nuevas columnas): 
```php
$zseed = [
  ['east-blue',  65, 20, 55, 60, 50, 35, 15, 15, 25],
  ['west-blue',  60, 30, 50, 50, 45, 30, 30, 25, 30],
  ['north-blue', 55, 35, 55, 60, 55, 40, 20, 20, 30],
  ['south-blue', 60, 45, 40, 35, 35, 55, 35, 35, 40],
  ['calm-belt',  80, 95, 20, 25, 10, 20, 5,  10, 15],
  ['red-line',   50, 40, 70, 80, 80, 10, 10, 15, 20],
  ['paraiso',    55, 55, 60, 45, 55, 50, 25, 30, 45],
  ['new-world',  35, 85, 55, 25, 25, 80, 40, 45, 55],
];
// Orden: slug, cli, pel, riq, civ, mar, pir, rev, inf, ten
```

**Facciones** (solo las nuevas columnas):
```php
$fseed = [
  ['marine',           80, 80],
  ['pirata',           25, 60],
  ['revolucionario',   45, 40],
  ['gobierno',         95, 85],
  ['cazarrecompensas', 20, 35],
  ['civil',            30, 50],
];
// Orden: slug, pol, alc
```

Todas las seeds deben actualizar SOLO cuando el valor actual es el DEFAULT (50 para riq, 20 para inf/ten, 60 para cli, y 50 para pol/alc), igual que hace v2.

### 7. Abrir ciclo del mes si no existe (igual que v1/v2)

## Reglas
- Idempotente: usar `$db->field_exists()` para cada columna antes de ADD
- Usar `$PREFIX = TABLE_PREFIX` como en v2
- require_once `dirname(__DIR__) . '/inc/init.php'`
- El script se ejecuta con: `php scripts/migrate-mundo-vivo-v3.php`
- NO tocar columnas existentes ni tablas que no sean las listadas
- Seguir exactamente el estilo de `migrate-mundo-vivo-v2.php`

## Archivo a crear
`C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-mundo-vivo-v3.php`
