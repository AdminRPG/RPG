# Tarea 3: Panel Staff mundo-vivo.php — Mundo Vivo v3

## Contexto
Archivo: `mundo-vivo.php` (670 líneas)
Panel de staff/WebMaster. Ya se actualizó `inc/ope_rol_mundo.php` con las nuevas funciones v3.

## Cambios a hacer

### 1. Tab "Tablero" — Inputs para 10 métricas de zona y 7 de facción
Actualmente el bucle renderiza usando `$zMetricsDef` y `$fMetricsDef`. Como esas funciones ahora devuelven 10 y 7 keys respectivamente, los inputs se actualizan automáticamente. **Pero verifica que así sea** — si hay código hardcodeado, actualízalo.

Busca secciones como:
```php
foreach ($zMetricsDef as $k => $m):
  <input type="number" ... name="zona[...][$k]" value="...">
endforeach;
```
Esto funciona automáticamente porque `$zMetricsDef = ope_rol_mv_zona_metrics()` ahora devuelve 10 keys.

Lo mismo para facciones con `$fMetricsDef`.

### 2. Tab "NPCs" — Añadir editor JSON para datos_publicos y datos_internos
Después del formulario existente de NPC (mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np), añadir para cada NPC:

```html
<details class="mv-npc-json">
  <summary>Datos públicos (JSON) — se muestra en Estado del Mundo</summary>
  <textarea name="datos_publicos[PID]" class="mv-input mv-mono" rows="4"><?php echo htmlspecialchars_uni(json_encode($n['datos_publicos'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></textarea>
</details>
<details class="mv-npc-json">
  <summary>Datos internos (JSON) — solo staff/IA</summary>
  <textarea name="datos_internos[PID]" class="mv-input mv-mono" rows="6"><?php echo htmlspecialchars_uni(json_encode($n['datos_internos'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></textarea>
</details>
```

Añadir handlers POST para `npc_json_save`:
```php
} elseif ($action === 'npc_json_save') {
    $dpi = $mybb->get_input('datos_publicos', MyBB::INPUT_ARRAY);
    $dii = $mybb->get_input('datos_internos', MyBB::INPUT_ARRAY);
    if (is_array($dpi)) {
        foreach ($dpi as $pid => $json_str) {
            $pid = (int)$pid; if ($pid < 1) continue;
            $dp_clean = json_decode($json_str, true);
            if (!is_array($dp_clean)) $dp_clean = new stdClass();
            $db->update_query('rol_personajes', array('datos_publicos' => $db->escape_string(json_encode($dp_clean, JSON_UNESCAPED_UNICODE))), 'pid = ' . $pid);
        }
    }
    if (is_array($dii)) {
        foreach ($dii as $pid => $json_str) {
            $pid = (int)$pid; if ($pid < 1) continue;
            $di_clean = json_decode($json_str, true);
            if (!is_array($di_clean)) $di_clean = new stdClass();
            $db->update_query('rol_personajes', array('datos_internos' => $db->escape_string(json_encode($di_clean, JSON_UNESCAPED_UNICODE))), 'pid = ' . $pid);
        }
    }
    $flash = 'Datos de NPCs actualizados.';
}
```

### 3. Tab "Generar" — Hilos narrativos en vista previa
Después de la sección "Tramas abiertas" (arcos) en la vista previa, añadir sección de hilos narrativos:

```php
<h3 class="mv-prev-h">Hilos narrativos activos</h3>
<?php 
$threadsPrev = (is_array($preview['estado']) && !empty($preview['estado']['threads'])) ? $preview['estado']['threads'] : array();
if (empty($threadsPrev)): ?>
<p class="mv-empty">La IA no devolvió hilos narrativos.</p>
<?php else: ?>
<div class="mv-thrlist">
<?php foreach ($threadsPrev as $th): if (!is_array($th)) continue; ?>
  <div class="mv-thrcard">
    <div class="mv-thrcard-h">
      <span class="mv-thrcard-t"><?php echo htmlspecialchars_uni($th['titulo'] ?? '(sin título)'); ?></span>
      <span class="mv-thrcard-est"><?php echo htmlspecialchars_uni($th['estado'] ?? ''); ?></span>
    </div>
    <?php if (!empty($th['descripcion'])): ?><p><?php echo htmlspecialchars_uni($th['descripcion']); ?></p><?php endif; ?>
    <?php if (!empty($th['proxima_evolucion'])): ?><p class="mv-note">Próxima: <?php echo htmlspecialchars_uni($th['proxima_evolucion']); ?></p><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
```

Y también mostrar NPC tracking changes si existen en el preview (similar a cómo se muestran los diffs de tensiones).

### 4. Vista previa — NPC tracking diff
Añadir sección que muestre cambios en NPC tracking (si `npc_tracking` viene en ESTADO_JSON):
```php
<h3 class="mv-prev-h">Tracking de NPCs mayores</h3>
<?php 
$npcTrackPrev = (is_array($preview['estado']) && !empty($preview['estado']['npc_tracking'])) ? $preview['estado']['npc_tracking'] : array();
if (empty($npcTrackPrev)): ?>
<p class="mv-empty">La IA no devolvió tracking de NPCs.</p>
<?php else: 
  $currentTracking = ope_rol_mv_npc_tracking_from_db(); 
  foreach ($npcTrackPrev as $pid => $track): 
    $old = $currentTracking[(int)$pid] ?? array();
?>
  <div class="mv-diff-block">
    <div class="mv-diff-name">NPC #<?php echo (int)$pid; ?></div>
    <div class="mv-diff-line">Salud: <?php echo (int)($old['salud'] ?? 100); ?> → <?php echo (int)$track['salud']; ?></div>
    <div class="mv-diff-line">Moral: <?php echo (int)($old['moral'] ?? 100); ?> → <?php echo (int)$track['moral']; ?></div>
    <div class="mv-diff-line">Ubicación: <?php echo htmlspecialchars_uni($old['ubicacion_zona'] ?? '?'); ?> → <?php echo htmlspecialchars_uni($track['ubicacion_zona']); ?></div>
    <div class="mv-diff-line">Plan: <?php echo htmlspecialchars_uni($track['plan_activo'] ?? ''); ?></div>
  </div>
<?php endforeach; endif; ?>
```

### 5. Nav resumen — campo de texto en Generar
Añadir un campo para `nav_resumen` en el tab Generar, que se guarde junto con el ciclo.

En el POST, añadir handler:
```php
} elseif ($action === 'save_nav_resumen') {
    $db->update_query('rol_mv_ciclos', array('nav_resumen' => $db->escape_string(trim($mybb->get_input('nav_resumen')))), 'ciclo_id = ' . $ciclo_id);
    $flash = 'Resumen de navegación guardado.';
}
```

Y en el HTML del tab Generar:
```html
<div class="plate">
  <div class="plate-h"><span class="t">Resumen de navegación</span><span class="c">// viajes del mes</span></div>
  <div class="plate-b">
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
      <input type="hidden" name="mv_action" value="save_nav_resumen">
      <textarea name="nav_resumen" class="mv-input" rows="4" placeholder="Resumen de viajes completados, naufragios, descubrimientos..."><?php echo htmlspecialchars_uni((string)($ciclo['nav_resumen'] ?? '')); ?></textarea>
      <div class="mv-save-bar"><button class="btn btn-primary btn-sm">Guardar</button></div>
    </form>
  </div>
</div>
```

### 6. Indicaciones del staff — persistencia entre ciclos
Actualmente las indicaciones se guardan en el ciclo actual. Al abrir un nuevo ciclo, las indicaciones están vacías. Añadir lógica para que al crear un ciclo nuevo, se copien las indicaciones del ciclo anterior (opcional pero útil).

Esto implica modificar `ope_rol_mv_ciclo_actual()` — pero esa función no acepta parámetros. En su lugar, simplemente mostrar un mensaje en el panel: "Las indicaciones no se heredan automáticamente. Cópialas manualmente del mes anterior si es necesario."

## Reglas generales
- Mantener el estilo HTML del archivo (clases CSS, estructura plate/plate-h/plate-b)
- NO eliminar funcionalidad existente
- Asegurar que los names de los formularios POST no colisionen
- Los nuevos inputs deben estar DENTRO del form method="post" existente si aplica
- Para los JSON editors, crear forms independientes dentro del loop de NPCs

## Archivo a modificar
`C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\mundo-vivo.php`
