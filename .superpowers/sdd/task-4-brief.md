# Tarea 4: Página Pública estado-mundo.php — Mundo Vivo v3

## Contexto
Archivo: `estado-mundo.php` (250 líneas)
Página visible por todos los usuarios. Muestra el estado del mundo: zonas, facciones, arcos, NPCs.

## Cambios a hacer

### 1. Render de zonas — 10 métricas en modal
Actualmente los modales de cada zona (`#zfull-{slug}`) iteran con `$zMetrics`. Como `ope_rol_mv_zona_metrics()` ahora devuelve 10 keys, el bucle ya renderiza todas automáticamente. **Verifica** que no haya hardcode de 7 métricas.

```php
// Buscar en el HTML dentro del div.store:
foreach ($zMetrics as $k => $m):
    echo mv_metric_bar($m['label'], $z[$k], ope_rol_mv_band5($z[$k], $m['bands']), $m['col']);
endforeach;
```
Si existe exactamente así, funciona automáticamente. Si hay algo hardcodeado (como una lista fija de keys), actualízalo para usar el array dinámico.

### 2. Render de facciones — 7 métricas en tarjetas
Lo mismo que arriba pero con `$fMetrics`. El bucle actual debería funcionar automáticamente al devolver 7 keys ahora.

### 3. NPCs mayores — usar datos_publicos
Reemplazar la sección "Figuras del mundo" (líneas ~146-167).

Actualmente:
```php
$npcs_ubicados = array_filter($npcs, function ($n) { return trim((string)$n['mundo_zona']) !== '' || trim((string)$n['mundo_ubic']) !== ''; });
```
Cambiar para que también incluya NPCs que tengan datos_publicos (aunque no tengan mundo_zona):
```php
$npcs_ubicados = array_filter($npcs, function ($n) { 
    return trim((string)$n['mundo_zona']) !== '' || trim((string)$n['mundo_ubic']) !== '' || !empty($n['datos_publicos']); 
});
```

Y dentro del foreach de NPCs, actualizar el render para mostrar datos enriquecidos si existen:
```php
<?php foreach ($npcs_ubicados as $n):
    $pub = $n['datos_publicos'] ?? array();
    $zname = isset($zonas[$n['mundo_zona']]) ? $zonas[$n['mundo_zona']]['nombre'] : $n['mundo_zona'];
    // Si datos_publicos tiene ubicación, úsala con preferencia
    $ubicPublica = !empty($pub['ubicacion_publica']) ? $pub['ubicacion_publica'] : ($zname !== '' ? $zname : '');
    if (trim((string)$n['mundo_ubic']) !== '' && empty($pub['ubicacion_publica'])) {
        $ubicPublica .= ($ubicPublica !== '' ? ' · ' : '') . trim((string)$n['mundo_ubic']);
    }
?>
  <article class="em-npc">
    <h3><?php echo htmlspecialchars_uni($n['nombre']); ?></h3>
    <div class="em-fac-tags">
      <?php if ($n['faccion'] !== ''): ?><span class="em-tag"><?php echo htmlspecialchars_uni($n['faccion']); ?></span><?php endif; ?>
      <?php if (!empty($pub['titulos'][0])): ?><span class="em-tag"><?php echo htmlspecialchars_uni($pub['titulos'][0]); ?></span><?php endif; ?>
      <?php if ($ubicPublica !== ''): ?><span class="em-tag"><?php echo htmlspecialchars_uni($ubicPublica); ?></span><?php endif; ?>
    </div>
    <?php if (!empty($pub['descripcion'])): ?><p class="em-notas"><?php echo htmlspecialchars_uni(mb_substr($pub['descripcion'], 0, 200)); ?><?php echo mb_strlen($pub['descripcion']) > 200 ? '…' : ''; ?></p><?php endif; ?>
    <?php if (trim((string)$n['mundo_accion']) !== ''): ?><p class="em-notas"><?php echo htmlspecialchars_uni((string)$n['mundo_accion']); ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>
```

### 4. Tensión — solo top 3 pares
Actualmente en los modales de zona se muestran TODOS los 15 pares de tensión. Cambiar para mostrar solo el top 3 (los 3 con mayor valor), con un enlace "Ver todas las tensiones" que despliegue el resto.

En el bucle de tensiones dentro de `#zfull-{slug}`:
```php
<?php 
$zt_all = $zt; // Copia
uasort($zt, function ($a, $b) { return $b['valor'] <=> $a['valor']; });
$zt_top3 = array_slice($zt, 0, 3, true);
$zt_rest = array_slice($zt, 3, null, true);
?>
<?php foreach ($zt_top3 as $par => $info): ?>
  <!-- render igual que antes -->
<?php endforeach; ?>
<?php if (!empty($zt_rest)): ?>
  <details class="em-ten-more">
    <summary>Ver todas las tensiones (<?php echo count($zt_rest); ?> más)</summary>
    <?php foreach ($zt_rest as $par => $info): ?>
      <!-- render igual -->
    <?php endforeach; ?>
  </details>
<?php endif; ?>
```

### 5. Nueva sección: Hilos del mundo
Añadir una sección después de "Arcos en marcha" que muestre los hilos narrativos activos. Usa `ope_rol_mv_threads_activos()`.

```php
<?php
$threadsPub = ope_rol_mv_threads_activos();
if (!empty($threadsPub)):
  $threadsActivos = array_filter($threadsPub, function($t) { return in_array($t['estado'] ?? '', ['activo', 'reabierto']); });
  if (!empty($threadsActivos)):
?>
  <section class="reveal">
    <div class="shead"><h2>Hilos del mundo</h2><span class="code">// tramas en curso</span><span class="rule"></span></div>
    <div class="em-arcos">
<?php foreach ($threadsActivos as $th): ?>
      <article class="em-arco">
        <h3><?php echo htmlspecialchars_uni($th['titulo'] ?? '(sin título)'); ?> <span class="em-arco-st"><?php echo htmlspecialchars_uni($th['tipo'] ?? ''); ?></span></h3>
        <?php if (!empty($th['descripcion'])): ?><p><?php echo nl2br(htmlspecialchars_uni($th['descripcion'])); ?></p><?php endif; ?>
        <?php if (!empty($th['facciones_implicadas'])): ?><p class="em-notas">Facciones: <?php echo htmlspecialchars_uni(implode(', ', $th['facciones_implicadas'])); ?></p><?php endif; ?>
      </article>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; endif; ?>
```

### 6. Último periódico — enlace
Ya existe `$ultimo` y se muestra en el hero "Última actualización". Verificar que el enlace al periódico funciona (debe apuntar a `periodicos.php?c=ID`).

## Reglas
- Mantener el estilo existente: clases em-*, estructura reveal, hero
- NO romper funcionalidad actual
- NO modificar CSS (clases ya existen en ope.css)
- Asegurar que los nuevos elementos usen clases existentes

## Archivo a modificar
`C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\estado-mundo.php`
