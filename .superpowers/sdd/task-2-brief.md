# Tarea 2: Core gbe_rol_mundo.php — Mundo Vivo v3

## Contexto
Archivo: `inc/gbe_rol_mundo.php` (1149 líneas actualmente)
Este archivo contiene toda la lógica del sistema Mundo Vivo.
La migración BD v3 ya se ejecutó (las columnas cli, riq, inf, ten, pol, alc, threads_json, nav_resumen, tipo_suceso, pe_estimado, datos_publicos, datos_internos existen).
Tienes que leer el archivo actual y aplicar TODOS los cambios abajo.

## Cambios a hacer

### 1. `gbe_rol_mv_zona_metrics()` — 10 métricas
Reemplazar el array actual (7 keys: est,mar,pir,rev,eco,civ,pel) por 10 keys en este orden:

```php
'cli' => ['label'=>'Clima',              'bands'=>['Tormentoso','Inestable','Variable','Bonancible','Calma'],              'col'=>'var(--h4)'],
'pel' => ['label'=>'Nivel de peligro',   'bands'=>['Seguro','Bajo','Moderado','Alto','Mortal'],                          'col'=>'var(--crack)'],
'riq' => ['label'=>'Riqueza',            'bands'=>['Miseria','Precaria','Modesta','Próspera','Opulenta'],                'col'=>'var(--ember)'],
'civ' => ['label'=>'Orden civil',        'bands'=>['Anarquía','Caótico','Frágil','Ordenado','Férreo'],                  'col'=>'var(--fac-cazarrecompensas)'],
'mar' => ['label'=>'Presencia Marine',   'bands'=>['Nula','Escasa','Moderada','Fuerte','Dominante'],                     'col'=>'var(--fac-marine)'],
'pir' => ['label'=>'Actividad pirata',   'bands'=>['Insignificante','Baja','Notable','Alta','Dominante'],               'col'=>'var(--fac-pirata)'],
'rev' => ['label'=>'Influencia revolucionaria','bands'=>['Nula','Escasa','Moderada','Fuerte','Dominante'],              'col'=>'var(--fac-revolucionario)'],
'inf' => ['label'=>'Inframundo',         'bands'=>['Inexistente','Bajo','Notable','Extendido','Dominante'],              'col'=>'var(--crack)'],
'est' => ['label'=>'Estabilidad',        'bands'=>['Colapso','Inestable','Tensa','Estable','Próspera'],                 'col'=>'var(--patina)'],
'ten' => ['label'=>'Tensión General',    'bands'=>['Paz','Leve','Notable','Alta','Crítica'],                            'col'=>'var(--danger)'],
```

NOTA: `eco` desaparece del array (era prosperidad económica de zona, ahora reemplazada por `riq`).

### 2. `gbe_rol_mv_faccion_metrics()` — 7 métricas
Reemplazar el array actual (6 keys) por 7 keys:

```php
'rep' => ['label'=>'Reputación pública', 'special'=>'rep',                                                                           'col'=>'var(--patina)'],
'coh' => ['label'=>'Cohesión interna',   'bands'=>['Fracturada','Débil','Sólida','Firme','Monolítica'],                              'col'=>'var(--h4)'],
'mil' => ['label'=>'Poder militar',      'bands'=>['Ínfimo','Débil','Medio','Fuerte','Supremo'],                                    'col'=>'var(--crack)'],
'pol' => ['label'=>'Influencia política','bands'=>['Nula','Escasa','Moderada','Fuerte','Dominante'],                                'col'=>'var(--fac-civil)'],
'eco' => ['label'=>'Recursos económicos','bands'=>['Miseria','Precaria','Modesta','Próspera','Opulenta'],                            'col'=>'var(--ember)'],
'mor' => ['label'=>'Moral',              'bands'=>['Rota','Baja','Firme','Alta','Fervorosa'],                                       'col'=>'var(--fac-revolucionario)'],
'alc' => ['label'=>'Alcance',            'bands'=>['Local','Regional','Multimar','Global','Mundial'],                                'col'=>'var(--h4)'],
```

NOTA: `inf` (vieja influencia política) desaparece, ahora es `pol`.

### 3. `gbe_rol_mv_npc_mayores()`
Añadir `datos_publicos` y `datos_internos` al SELECT. Actualizar el fetch para decodificar ambos JSON y añadirlos al array de retorno:
```php
$q = $db->simple_select('rol_personajes', 'pid, nombre, rango, datos, mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np, datos_publicos, datos_internos', "es_npc = 1 AND estado <> 'eliminado'", ...);
```
Al construir el $out, decodificar datos_publicos y datos_internos con json_decode y añadirlos como sub-arrays.

### 4. Nueva función: `gbe_rol_mv_threads_activos()`
Lee el `estado_json` del último ciclo publicado, extrae el array `threads` y lo devuelve.

```php
function gbe_rol_mv_threads_activos() {
    global $db;
    $ultimo = gbe_rol_mv_ultimo_publicado();
    if (!$ultimo || empty($ultimo['estado_json'])) return array();
    $ej = json_decode($ultimo['estado_json'], true);
    if (!is_array($ej) || !isset($ej['threads']) || !is_array($ej['threads'])) return array();
    return $ej['threads'];
}
```

### 5. Nueva función: `gbe_rol_mv_ultimos_periodicos($n = 3)`
Devuelve un array con los últimos N periódicos publicados (ciclo_id, periodo, noticia_titulo, periodico_html truncado a 500 chars).

```php
function gbe_rol_mv_ultimos_periodicos($n = 3) {
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_ciclos')) return $out;
    $q = $db->simple_select('rol_mv_ciclos', 'ciclo_id, periodo, noticia_titulo, periodico_html, published_at', "published_at > 0", array('order_by' => 'published_at', 'order_dir' => 'DESC', 'limit' => (int)$n));
    while ($r = $db->fetch_array($q)) {
        $r['periodico_resumen'] = mb_substr(strip_tags((string)$r['periodico_html']), 0, 500);
        unset($r['periodico_html']);
        $out[] = $r;
    }
    return $out;
}
```

### 6. Nueva función: `gbe_rol_mv_npc_tracking_from_db()`
Construye array clave-valor: `{pid => {salud, moral, plan_activo, ubicacion_zona, meta_actual}}` extrayendo de `datos_internos` de cada NPC mayor.

```php
function gbe_rol_mv_npc_tracking_from_db() {
    global $db;
    $out = array();
    $q = $db->simple_select('rol_personajes', 'pid, datos_internos', "es_npc = 1 AND estado <> 'eliminado'");
    while ($r = $db->fetch_array($q)) {
        $di = json_decode((string)$r['datos_internos'], true);
        if (!is_array($di) || !isset($di['tracking'])) continue;
        $out[(int)$r['pid']] = $di['tracking'];
    }
    return $out;
}
```

### 7. `gbe_rol_mv_build_prompt()` — REEMPLAZAR COMPLETAMENTE
Es la función más grande. Reemplazar TODO su contenido con la nueva versión v3.

Lo que debe cambiar:
- **Encabezado**: "MUNDO VIVO · \"LA BALANZA\" v3"
- **Sección de principios rectores**: los 6 principios de AV-13 §0.4
- **Tabla de regresión**: completa de AV-13 §3.1 (tanto zona como facción)
- **Sistema de impacto**: fórmula PE×MR×FR×FA/10, clasificación S-01 a S-12, huella por tipo, topes anti-escalada de §2.4
- **Métricas**: describir las 10 de zona y 7 de facción
- **Tensión**: 15 pares por mar, 0-100
- **Estado actual**: igual que antes pero incluyendo las nuevas columnas
- **Hilos narrativos**: añadir sección "HILOS NARRATIVOS ABIERTOS" usando `gbe_rol_mv_threads_activos()`
- **Últimos periódicos**: añadir sección "ÚLTIMOS PERIÓDICOS" usando `gbe_rol_mv_ultimos_periodicos(3)`
- **NPCs mayores**: mostrar con formato de datos_internos (personalidad 6 ejes, metas, tracking actual) más datos_publicos (título, descripción)
- **Contrato de salida**: 5 BLOQUES:
  1. ESTADO_JSON (incluye `threads` y `npc_tracking`)
  2. PERIODICO_HTML
  3. NOTICIA (titulo, resumen, cuerpo)
  4. MISIONES (formato: `- titulo: ... | zona: ... | facciones: ... | dificultad: ... | resumen: ...`)
  5. IMAGENES (formato: `- id: ... | tamaño: ... | prompt: ...`)

Para el ESTADO_JSON, el formato ahora es:
```json
{
  "zonas": { "east-blue": {"cli":65,"pel":20,"riq":55,"civ":60,"mar":50,"pir":35,"rev":15,"inf":15,"est":60,"ten":25,"notas":"..."} },
  "facciones": { "marine": {"rep":40,"coh":80,"mil":85,"pol":80,"eco":75,"mor":70,"alc":80,"notas":"..."} },
  "tension": { "east-blue": { "marine|pirata": {"valor":76,"notas":"..."} } },
  "arcos": [ {"nombre":"...","estado":"Activo","zonas":"...","facciones":"...","descripcion":"..."} ],
  "threads": [ {"id":"th-001","titulo":"...","estado":"activo","tipo":"...","zonas":[],"npc_implicados":[],"facciones_implicadas":[],"descripcion":"...","proxima_evolucion":"...","posible_cierre":false} ],
  "npc_tracking": { "42": {"salud":95,"moral":80,"plan_activo":"...","ubicacion_zona":"East Blue","meta_actual":"..."} }
}
```

IMPORTANTE: La función debe construir el prompt leyendo el estado actual con las nuevas 10 métricas. Asegúrate de que el bucle que itera sobre zonas use `gbe_rol_mv_zona_metrics()` para obtener las 10 keys, y lo mismo para facciones.

### 8. `gbe_rol_mv_parse_resultado()`
- Al validar ESTADO_JSON, permitir que `threads` y `npc_tracking` sean null/opcionales (no son obligatorios para versiones de transición)
- Si existen, almacenarlos en el resultado

### 9. `gbe_rol_mv_aplicar_estado()`
- Los bucles de zona y facción usan `gbe_rol_mv_zona_metrics()` y `gbe_rol_mv_faccion_metrics()` — se actualizan automáticamente porque esas funciones devuelven las nuevas 10/7 keys
- Asegurarse de que el clamp de `inf` y `ten` usa 0-100 (ya que son nuevas)
- Para facciones: `pol` y `alc` son 0-100

### 10. `gbe_rol_mv_publicar()`
Después de aplicar el estado (paso 1), añadir:

**a) Guardar threads_json en el ciclo:**
```php
$threads_json = '';
if (isset($parsed['estado']['threads'])) {
    $threads_json = json_encode($parsed['estado']['threads'], JSON_UNESCAPED_UNICODE);
}
```

**b) Guardar nav_resumen (campo preparado, vacío por ahora):**
```php
$nav_resumen = (string)($parsed['nav_resumen'] ?? '');
```

**c) Actualizar la update_query del ciclo para incluir threads_json y nav_resumen:**
Añadir al array de update_query:
```php
'threads_json' => $db->escape_string($threads_json),
'nav_resumen' => $db->escape_string($nav_resumen),
```

**d) Actualizar NPC tracking desde npc_tracking del ESTADO_JSON:**
```php
if (isset($parsed['estado']['npc_tracking']) && is_array($parsed['estado']['npc_tracking'])) {
    foreach ($parsed['estado']['npc_tracking'] as $pid => $tracking) {
        $pid = (int)$pid;
        if ($pid < 1) continue;
        // Leer datos_internos actual, merge con nuevo tracking
        $q = $db->simple_select('rol_personajes', 'datos_internos', "pid = $pid", array('limit' => 1));
        if (!$db->num_rows($q)) continue;
        $di = json_decode((string)$db->fetch_field($q, 'datos_internos'), true);
        if (!is_array($di)) $di = array('personalidad' => array(), 'metas' => array(), 'meta_actual' => '', 'tracking' => array());
        $di['tracking']['salud'] = isset($tracking['salud']) ? (int)$tracking['salud'] : ($di['tracking']['salud'] ?? 100);
        $di['tracking']['moral'] = isset($tracking['moral']) ? (int)$tracking['moral'] : ($di['tracking']['moral'] ?? 100);
        $di['tracking']['plan_activo'] = (string)($tracking['plan_activo'] ?? $di['tracking']['plan_activo'] ?? '');
        $di['tracking']['ubicacion_zona'] = (string)($tracking['ubicacion_zona'] ?? $di['tracking']['ubicacion_zona'] ?? '');
        $di['tracking']['meta_actual'] = (string)($tracking['meta_actual'] ?? $di['tracking']['meta_actual'] ?? '');
        $di['tracking']['ultimo_ciclo'] = $ciclo['periodo'];
        $db->update_query('rol_personajes', array('datos_internos' => $db->escape_string(json_encode($di, JSON_UNESCAPED_UNICODE))), "pid = $pid");
    }
}
```

**e) NO tocar las columnas viejas** (mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np) — se mantienen como legacy.

### 11. Constante GBE_MV_TENSION_MAX_UP
Cambiar de 20 a 15 (según topes v3 §2.4):
Buscar `defined('GBE_MV_TENSION_MAX_UP')` y cambiar el fallback:
```php
$capUp = defined('GBE_MV_TENSION_MAX_UP') ? (int) GBE_MV_TENSION_MAX_UP : 15;
```

## Reglas generales
- NO cambiar nada que no esté en esta lista
- NO eliminar funciones existentes (solo modificar su contenido)
- Respetar el estilo del archivo (comentarios, nomenclatura, formato)
- El orden de las funciones en el archivo debe mantenerse aproximadamente igual
- No añadir ni quitar funciones que no estén en esta lista
- Asegurarse de que el código es sintácticamente válido PHP
- Las funciones nuevas deben usar `global $db;` cuando accedan a BD
- Usar `JSON_UNESCAPED_UNICODE` en json_encode para caracteres UTF-8

## Archivo a modificar
`C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\inc\gbe_rol_mundo.php`
