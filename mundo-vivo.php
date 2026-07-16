<?php
/**
 * I-Forge · Panel de Mundo Vivo (Zona Staff · Web Master)
 * Recopila eventos/misiones/tablero/NPCs, genera el super-prompt para la IA,
 * ingiere el resultado estructurado, muestra vista previa y publica.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mundo-vivo.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);

$staff = $loggedin ? gbe_rol_active_staff($uid) : array('rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank  = (int)$staff['rank'];
$is_webmaster = ($rank >= 4);

$flash = ''; $flash_kind = 'ok';
$preview = null;   // resultado parseado a previsualizar

$ciclo = gbe_rol_mv_ciclo_actual();
$ciclo_id = is_array($ciclo) ? (int)$ciclo['ciclo_id'] : 0;

// ── POST ──
if ($is_webmaster && $mybb->request_method === 'post' && $ciclo_id > 0) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga e inténtalo de nuevo.'; $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('mv_action');

        // v4 (Fase 1): el staff SOLO guarda indicaciones, genera el prompt, pega el
        // resultado y publica. Todo lo demás (clasificar eventos, editar el tablero,
        // gestionar arcos/hilos/misiones, mover NPCs) lo hace el propio sistema o la
        // IA — el panel de abajo es de solo lectura para esas cosas.
        if ($action === 'save_indicaciones') {
            $db->update_query('rol_mv_ciclos', array('indicaciones' => $db->escape_string(trim($mybb->get_input('indicaciones')))), 'ciclo_id = ' . $ciclo_id);
            $flash = 'Indicaciones guardadas.';

        } elseif ($action === 'generar_prompt') {
            $ciclo = gbe_rol_mv_ciclo_by_id($ciclo_id);
            $prompt = gbe_rol_mv_build_prompt($ciclo);
            $db->update_query('rol_mv_ciclos', array('prompt' => $db->escape_string($prompt), 'estado' => 'prompt'), 'ciclo_id = ' . $ciclo_id);
            $flash = 'Prompt generado. Cópialo y pégalo en tu IA.';

        } elseif ($action === 'ingerir') {
            $raw = (string)$mybb->get_input('resultado');
            $preview = gbe_rol_mv_parse_resultado($raw);
            $db->update_query('rol_mv_ciclos', array('resultado_raw' => $db->escape_string($raw), 'estado' => 'preview'), 'ciclo_id = ' . $ciclo_id);
            if (!empty($preview['errores'])) {
                $flash = 'El resultado tiene problemas: ' . implode(' ', $preview['errores']); $flash_kind = 'warn';
            } else {
                $flash = 'Resultado interpretado. Revisa la vista previa y pulsa Publicar.';
            }

        } elseif ($action === 'publicar') {
            $ciclo = gbe_rol_mv_ciclo_by_id($ciclo_id);
            $raw = (string)$ciclo['resultado_raw'];
            $parsed = gbe_rol_mv_parse_resultado($raw);
            // Links de imagen pegados por el staff: map id => url. Se inyectan en el
            // periódico/noticia dentro de gbe_rol_mv_publicar (evita subir al backend).
            $imgUrls = $mybb->get_input('img_url', MyBB::INPUT_ARRAY);
            if (!is_array($imgUrls)) { $imgUrls = array(); }
            $r = gbe_rol_mv_publicar($ciclo_id, $parsed, $raw, $imgUrls);
            if (!empty($r['ok'])) {
                $flash = 'Publicado: el nuevo estado del mundo, el periódico Eternal News y la noticia ya están en línea.';
                if (!empty($r['caps'])) {
                    $flash .= ' ⚠ Se aplicaron ' . count($r['caps']) . ' tope(s) anti-escalada porque la IA propuso cambios mayores de lo permitido en un ciclo (ver pestaña Auditoría).';
                    $flash_kind = 'warn';
                }
                if (!empty($r['misiones_creadas'])) {
                    $flash .= ' Se crearon ' . (int)$r['misiones_creadas'] . ' misión(es) nueva(s) para el próximo ciclo.';
                }
            } else {
                $flash = 'No se pudo publicar: ' . ($r['error'] ?? 'error desconocido'); $flash_kind = 'warn';
            }
        }
    }
    // Refrescar ciclo tras cambios
    $ciclo = gbe_rol_mv_ciclo_by_id($ciclo_id);
}

// ── Datos para render ──
$zonas       = gbe_rol_mv_zonas();
$facciones   = gbe_rol_mv_facciones();
$tension     = gbe_rol_mv_tension();          // anidado por zona
$zMetricsDef = gbe_rol_mv_zona_metrics();
$fMetricsDef = gbe_rol_mv_faccion_metrics();
$arcos       = gbe_rol_mv_arcos();
$eventos   = $ciclo_id ? gbe_rol_mv_eventos($ciclo_id) : array();
$misiones  = $ciclo_id ? gbe_rol_mv_misiones($ciclo_id) : array();
$npcs      = gbe_rol_mv_npc_mayores();
$menores   = $ciclo_id ? gbe_rol_mv_npc_menores($ciclo_id) : array();
$periodicos = gbe_rol_mv_periodicos(60);
$auditLog  = gbe_rol_mv_audit_list(15);
$pk = htmlspecialchars_uni($mybb->post_code);
$mes_label = is_array($ciclo) ? htmlspecialchars_uni($ciclo['periodo']) : '';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Mundo Vivo</title>
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-mundo-vivo) -->
</head>
<body class="gbe-pg-mundo-vivo">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Mundo Vivo</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Mundo Vivo</h1>
      <span class="code">// la balanza &middot; mes <?php echo $mes_label; ?></span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$is_webmaster): ?>
  <section class="reveal">
    <div class="plate"><div class="plate-b">
      <div class="noperm"><div class="big">Zona reservada a Web Master</div>
      <p>El panel de Mundo Vivo solo está disponible para personajes con rol <b>Web Master</b>.</p>
      <a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver a Zona Staff</a></div>
    </div></div>
  </section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="mv-tabs" id="mvTabs">
      <button class="mv-tab on" data-tab="eventos">Eventos <span class="mv-pill"><?php echo count($eventos); ?></span></button>
      <button class="mv-tab" data-tab="misiones">Misiones <span class="mv-pill"><?php echo count($misiones); ?></span></button>
      <button class="mv-tab" data-tab="tablero">Tablero</button>
      <button class="mv-tab" data-tab="npcs">NPCs</button>
      <button class="mv-tab" data-tab="hilos">Hilos</button>
      <button class="mv-tab" data-tab="historico">Histórico</button>
      <button class="mv-tab" data-tab="auditoria">Auditoría</button>
      <button class="mv-tab" data-tab="generar">Generar / Publicar</button>
    </div>
  </section>

  <!-- ===== EVENTOS (solo lectura: se incluyen TODOS automáticamente) ===== -->
<?php
// Auto-clasificar eventos sin clasificar (se persiste en DB para la próxima)
if (!empty($eventos) && $ciclo['ciclo_id'] > 0) {
    gbe_rol_mv_auto_classify_pendientes((int)$ciclo['ciclo_id']);
    $eventos = gbe_rol_mv_eventos((int)$ciclo['ciclo_id']);
}
?>
  <section class="mv-panel reveal" id="tab-eventos">
    <div class="plate">
      <div class="plate-h"><span class="t">Eventos notificados</span><span class="c">// todos se incluyen en el prompt · la IA los pondera, no este sistema</span></div>
      <div class="plate-b">
        <p class="mv-note mb-12">La etiqueta [S-??/PE=?] es solo una estimación mecánica por palabras clave para tu referencia visual — <b>no</b> es lo que decide el impacto real. La IA que uses lee el resumen (y el hilo original si lo necesita) y clasifica cada evento por su cuenta.</p>
<?php if (empty($eventos)): ?>
        <p class="mv-empty">No hay eventos notificados este mes.</p>
<?php else: foreach ($eventos as $e): ?>
        <div class="mv-row mv-ev">
          <div class="mv-ev-main">
            <a class="mv-ev-t" href="<?php echo htmlspecialchars_uni($e['enlace']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars_uni($e['titulo']); ?></a>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($e['zona_slug']); ?> &middot; estimación orientativa: <b><?php echo htmlspecialchars_uni($e['tipo_suceso'] ?: 'S-??'); ?></b> PE=<?php echo (int)($e['pe_estimado'] ?: 4); ?></span>
            <p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$e['resumen'])); ?></p>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== MISIONES (solo lectura: la IA las resuelve/propone sola) ===== -->
  <section class="mv-panel reveal" id="tab-misiones" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones del mes</span><span class="c">// la IA decide su resolución al publicar</span></div>
      <div class="plate-b">
<?php if (empty($misiones)): ?>
        <p class="mv-empty">No hay misiones en curso este mes.</p>
<?php else: foreach ($misiones as $m): ?>
        <div class="mv-row mv-mis mv-mis-<?php echo htmlspecialchars_uni($m['estado']); ?>">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($m['titulo']); ?></span>
            <span class="mv-ev-meta">#<?php echo (int)$m['mision_id']; ?> &middot; <?php echo htmlspecialchars_uni($m['zona_slug']); ?> &middot; <b><?php echo htmlspecialchars_uni(str_replace('_', ' ', $m['estado'])); ?></b></span>
            <?php if (trim((string)$m['resumen']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$m['resumen'])); ?></p><?php endif; ?>
            <?php if (trim((string)($m['notas_resolucion'] ?? '')) !== ''): ?><p class="mv-note">Resolución de la IA: <?php echo nl2br(htmlspecialchars_uni((string)$m['notas_resolucion'])); ?></p><?php endif; ?>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== TABLERO (solo lectura: lo actualiza la IA al publicar, con topes) ===== -->
  <section class="mv-panel reveal" id="tab-tablero" hidden>
      <div class="plate">
        <div class="plate-h"><span class="t">Zonas (los mares)</span><span class="c">// métricas 0-100 · las actualiza la IA al publicar</span></div>
        <div class="plate-b">
<?php foreach ($zonas as $z): $zt = isset($tension[$z['slug']]) ? $tension[$z['slug']] : array(); ?>
          <div class="mv-zona">
            <div class="mv-zona-h"><b><?php echo htmlspecialchars_uni($z['nombre']); ?></b> <code><?php echo htmlspecialchars_uni($z['slug']); ?></code></div>
            <div class="mv-zona-vals mv-zona-vals-ro">
<?php foreach ($zMetricsDef as $k => $m): ?>
              <span class="mv-valchip" title="<?php echo htmlspecialchars_uni($m['label']); ?>"><?php echo strtoupper($k); ?> <b><?php echo (int)($z[$k] ?? 0); ?></b></span>
<?php endforeach; ?>
            </div>
<?php if (trim((string)$z['notas']) !== ''): ?><p class="mv-note"><?php echo htmlspecialchars_uni((string)$z['notas']); ?></p><?php endif; ?>
<?php if (!empty($zt)): ?>
            <details class="mv-tenblock">
              <summary>Tensiones entre facciones en <?php echo htmlspecialchars_uni($z['nombre']); ?></summary>
<?php foreach ($zt as $par => $info):
                $na = isset($facciones[$info['a']]) ? $facciones[$info['a']]['nombre'] : $info['a'];
                $nb = isset($facciones[$info['b']]) ? $facciones[$info['b']]['nombre'] : $info['b'];
?>
              <div class="mv-tenrow">
                <span class="mv-tenrow-v"><span><?php echo htmlspecialchars_uni($na . ' vs ' . $nb); ?></span> <b><?php echo (int)$info['valor']; ?></b></span>
<?php if (trim((string)$info['notas']) !== ''): ?><span class="mv-tenrow-n"><?php echo htmlspecialchars_uni((string)$info['notas']); ?></span><?php endif; ?>
              </div>
<?php endforeach; ?>
            </details>
<?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="plate">
        <div class="plate-h"><span class="t">Facciones</span><span class="c">// REP -100..100 · resto 0-100</span></div>
        <div class="plate-b">
<?php foreach ($facciones as $f): ?>
          <div class="mv-zona">
            <div class="mv-zona-h"><b><?php echo htmlspecialchars_uni($f['nombre']); ?></b> <code><?php echo htmlspecialchars_uni($f['slug']); ?></code></div>
            <div class="mv-zona-vals mv-zona-vals-ro">
<?php foreach ($fMetricsDef as $k => $m): ?>
              <span class="mv-valchip" title="<?php echo htmlspecialchars_uni($m['label']); ?>"><?php echo strtoupper($k); ?> <b><?php echo (int)($f[$k] ?? 0); ?></b></span>
<?php endforeach; ?>
            </div>
<?php if (trim((string)$f['notas']) !== ''): ?><p class="mv-note"><?php echo htmlspecialchars_uni((string)$f['notas']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>

    <div class="plate">
      <div class="plate-h"><span class="t">Arcos abiertos</span><span class="c">// tramas mayores · las gestiona la IA (bloque ESTADO_JSON)</span></div>
      <div class="plate-b">
<?php if (empty($arcos)): ?>
        <p class="mv-empty">No hay arcos abiertos.</p>
<?php else: foreach ($arcos as $a): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($a['nombre']); ?> <small>[<?php echo htmlspecialchars_uni($a['estado']); ?>]</small></span>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($a['zonas']); ?> &middot; <?php echo htmlspecialchars_uni($a['facciones']); ?></span>
            <?php if (trim((string)$a['descripcion']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$a['descripcion'])); ?></p><?php endif; ?>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== NPCs ===== -->
  <section class="mv-panel reveal" id="tab-npcs" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">NPCs mayores</span><span class="c">// datos automáticos desde ficha</span></div>
      <div class="plate-b">
<?php if (empty($npcs)): ?>
        <p class="mv-empty">No hay NPCs con ficha.</p>
<?php else: foreach ($npcs as $n): ?>
<?php
  $npcTrack = $n['datos_internos']['tracking'] ?? array();
?>
        <div class="mv-npc mv-npc-ro">
          <div class="mv-npc-name"><b><?php echo htmlspecialchars_uni($n['nombre']); ?></b> <small><?php echo htmlspecialchars_uni($n['faccion']); ?> · <?php echo htmlspecialchars_uni($n['rango']); ?></small></div>
          <div class="mv-npc-row">
            <span class="mv-npc-info">Zona: <?php echo htmlspecialchars_uni($n['mundo_zona'] ?: '?'); ?></span>
            <span class="mv-npc-info">Ubicación: <?php echo htmlspecialchars_uni($n['mundo_ubic'] ?: '?'); ?></span>
            <span class="mv-npc-info">Estado: <?php echo htmlspecialchars_uni($n['mundo_estado_np'] ?: 'normal'); ?></span>
            <span class="mv-npc-info">Acción: <?php echo htmlspecialchars_uni($n['mundo_accion'] ?: '?'); ?></span>
          </div>
          <div class="mv-npc-row">
            <span class="mv-npc-info">Salud: <?php echo (int)($npcTrack['salud'] ?? 100); ?>/100</span>
            <span class="mv-npc-info">Moral: <?php echo (int)($npcTrack['moral'] ?? 100); ?>/100</span>
            <?php if (!empty($npcTrack['plan_activo'])): ?><span class="mv-npc-info">Plan: <?php echo htmlspecialchars_uni($npcTrack['plan_activo']); ?></span><?php endif; ?>
            <?php if (!empty($npcTrack['meta_actual'])): ?><span class="mv-npc-info">Meta: <?php echo htmlspecialchars_uni($npcTrack['meta_actual']); ?></span><?php endif; ?>
          </div>
          <details class="mv-npc-json mt-8">
            <summary>Ver datos completos (públicos + internos)</summary>
            <pre class="mv-mono mv-json-dump fs-75"><?php
              echo htmlspecialchars_uni(json_encode($n['datos_publicos'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
              echo "\n\n// ---- Interno ----\n\n";
              echo htmlspecialchars_uni(json_encode($n['datos_internos'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            ?></pre>
          </details>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
    <div class="plate">
      <div class="plate-h"><span class="t">NPCs menores (historial)</span><span class="c">// relleno de este mes</span></div>
      <div class="plate-b">
<?php if (empty($menores)): ?>
        <p class="mv-empty">Sin NPCs menores registrados este mes.</p>
<?php else: foreach ($menores as $mn): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($mn['nombre']); ?></span>
            <span class="mv-ev-meta"><?php echo htmlspecialchars_uni($mn['zona_slug']); ?> &middot; <?php echo htmlspecialchars_uni($mn['estado']); ?></span>
            <?php if (trim((string)$mn['descripcion']) !== ''): ?><p class="mv-ev-res"><?php echo nl2br(htmlspecialchars_uni((string)$mn['descripcion'])); ?></p><?php endif; ?>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== HILOS NARRATIVOS ===== -->
  <section class="mv-panel reveal" id="tab-hilos" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Hilos narrativos</span><span class="c">// tramas persistentes entre periódicos</span></div>
      <div class="plate-b">
<?php
$threadsList = gbe_rol_mv_threads_activos();
if (empty($threadsList)): ?>
        <p class="mv-empty">No hay hilos narrativos activos.</p>
<?php else: foreach ($threadsList as $th): if (!is_array($th)) continue; ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($th['titulo'] ?? '(sin título)'); ?> <small>[<?php echo htmlspecialchars_uni($th['estado'] ?? ''); ?>]</small></span>
            <span class="mv-ev-meta">
              Tipo: <?php echo htmlspecialchars_uni($th['tipo'] ?? ''); ?>
              <?php if (!empty($th['zonas'])): ?> &middot; Zonas: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['zonas'])); endif; ?>
              <?php if (!empty($th['facciones_implicadas'])): ?> &middot; Facciones: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['facciones_implicadas'])); endif; ?>
            </span>
            <?php if (!empty($th['descripcion'])): ?><p class="mv-ev-res"><?php echo htmlspecialchars_uni($th['descripcion']); ?></p><?php endif; ?>
            <?php if (!empty($th['npc_implicados'])): ?><p class="mv-note">NPCs: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['npc_implicados'])); ?></p><?php endif; ?>
            <?php if (!empty($th['pj_implicados'])): ?><p class="mv-note">PJs: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['pj_implicados'])); ?></p><?php endif; ?>
            <?php if (!empty($th['proxima_evolucion'])): ?><p class="mv-note">Próxima: <?php echo htmlspecialchars_uni($th['proxima_evolucion']); ?></p><?php endif; ?>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== HISTÓRICO ===== -->
  <section class="mv-panel reveal" id="tab-historico" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Histórico</span><span class="c">// periódicos publicados</span></div>
      <div class="plate-b">
<?php if (empty($periodicos)): ?>
        <p class="mv-empty">Aún no se ha publicado ningún periódico.</p>
<?php else: foreach ($periodicos as $p): ?>
        <a class="mv-hist-item" href="<?php echo $bburl; ?>/periodicos.php?c=<?php echo (int)$p['ciclo_id']; ?>">
          <b>Eternal News</b> — <?php echo htmlspecialchars_uni($p['periodo']); ?>
          <span><?php echo htmlspecialchars_uni($p['noticia_titulo']); ?></span>
        </a>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== AUDITORÍA (Fase 3: rastro de topes aplicados / misiones resueltas) ===== -->
  <section class="mv-panel reveal" id="tab-auditoria" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Auditoría de publicaciones</span><span class="c">// red de seguridad para publicación desatendida</span></div>
      <div class="plate-b">
        <p class="mv-note">Cada vez que se publica un ciclo, el sistema guarda aquí si tuvo que recortar algún cambio de la IA por superar los topes anti-escalada (±<?php echo (int)(defined('GBE_MV_METRIC_MAX_DELTA') ? GBE_MV_METRIC_MAX_DELTA : 15); ?> por métrica y ciclo), y qué pasó con las misiones. Sirve para revisar cualquier publicación sin depender de la memoria de quien la hizo.</p>
<?php if (empty($auditLog)): ?>
        <p class="mv-empty">Aún no hay publicaciones registradas (o la migración v4 no se ha ejecutado todavía: <code>php scripts/migrate-mundo-vivo-v4.php</code>).</p>
<?php else: foreach ($auditLog as $al): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t">Ciclo #<?php echo (int)$al['ciclo_id']; ?> <small><?php echo date('d/m/Y H:i', (int)$al['dateline']); ?></small></span>
            <span class="mv-ev-meta">
              Misiones resueltas: <?php echo (int)$al['misiones_resueltas']; ?> &middot; Misiones creadas: <?php echo (int)$al['misiones_creadas']; ?>
<?php if ((int)$al['caps_aplicados_n'] > 0): ?> &middot; <b class="mv-audit-warn">⚠ <?php echo (int)$al['caps_aplicados_n']; ?> tope(s) aplicado(s)</b><?php else: ?> &middot; sin topes aplicados<?php endif; ?>
            </span>
<?php if (!empty($al['caps_aplicados'])): ?>
            <ul class="mv-audit-caps">
<?php foreach ($al['caps_aplicados'] as $c): ?>
              <li>[<?php echo htmlspecialchars_uni($c['ambito'] ?? ''); ?>] <?php echo htmlspecialchars_uni($c['slug'] ?? ''); ?> · <?php echo htmlspecialchars_uni($c['metrica'] ?? ''); ?>: la IA propuso <?php echo ($c['propuesto_delta'] >= 0 ? '+' : '') . (int)$c['propuesto_delta']; ?>, se aplicó <?php echo ($c['aplicado_delta'] >= 0 ? '+' : '') . (int)$c['aplicado_delta']; ?></li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

  <!-- ===== GENERAR / PUBLICAR ===== -->
  <section class="mv-panel reveal" id="tab-generar" hidden>
    <div class="plate">
      <div class="plate-h"><span class="t">Indicaciones del staff</span><span class="c">// se incluyen en el prompt de este mes</span></div>
      <div class="plate-b">
        <form method="post">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="save_indicaciones">
          <textarea name="indicaciones" class="mv-input" rows="5" placeholder="Qué quieres que la IA tenga en cuenta este mes..."><?php echo htmlspecialchars_uni((string)($ciclo['indicaciones'] ?? '')); ?></textarea>
          <div class="mv-save-bar"><button class="btn btn-primary btn-sm">Guardar indicaciones</button></div>
        </form>
        <p class="mv-note mt-075">Este es el ÚNICO input además de las URLs de imagen (más abajo, tras interpretar el resultado). Las indicaciones no se heredan automáticamente. Cópialas manualmente del mes anterior si es necesario.</p>
      </div>
    </div>

    <div class="plate">
      <div class="plate-h"><span class="t">1 · Generar prompt</span><span class="c">// autocontenido para IA externa</span></div>
      <div class="plate-b">
        <form method="post"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="generar_prompt"><button class="btn btn-primary">Generar prompt</button></form>
<?php if (!empty($ciclo['prompt'])): ?>
        <div class="mv-prompt-wrap">
          <textarea id="mvPrompt" class="mv-input mv-mono" rows="12" readonly><?php echo htmlspecialchars_uni((string)$ciclo['prompt']); ?></textarea>
          <button type="button" class="btn btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('mvPrompt').value);this.textContent='Copiado'">Copiar prompt</button>
        </div>
<?php endif; ?>
      </div>
    </div>

    <div class="plate">
      <div class="plate-h"><span class="t">2 · Pegar resultado</span><span class="c">// bloques estructurados de la IA</span></div>
      <div class="plate-b">
        <form method="post">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="ingerir">
          <textarea name="resultado" class="mv-input mv-mono" rows="12" placeholder="Pega aquí el resultado completo de la IA (bloques ===ESTADO_JSON===, ===PERIODICO_HTML===, ===NOTICIA===, ===MISIONES_RESUELTAS===, ===MISIONES===, ===IMAGENES===)"><?php echo htmlspecialchars_uni((string)($ciclo['resultado_raw'] ?? '')); ?></textarea>
          <div class="mv-save-bar"><button class="btn btn-primary">Interpretar resultado</button></div>
        </form>
      </div>
    </div>

<?php if ($preview !== null && empty($preview['errores'])):
      // Diff entre el tablero ACTUAL (aún no publicado) y el estado nuevo de la IA.
      $tablero = gbe_rol_mv_tablero();
      $diff = gbe_rol_mv_diff_estado($tablero, $preview['estado']);
      $diffVacio = empty($diff['zonas']) && empty($diff['facciones']) && empty($diff['tension']);
      $arcosNuevos = (is_array($preview['estado']) && !empty($preview['estado']['arcos']) && is_array($preview['estado']['arcos'])) ? $preview['estado']['arcos'] : array();
      $capsPrevistos = gbe_rol_mv_calcular_caps_previstos($preview['estado']);
?>
    <div class="plate">
      <div class="plate-h"><span class="t">3 · Vista previa</span><span class="c">// revisa antes de publicar</span></div>
      <div class="plate-b">
<?php if (!empty($capsPrevistos)): ?>
        <div class="mv-flash mv-warn">
          ⚠ La IA propuso <?php echo count($capsPrevistos); ?> cambio(s) mayores que el tope anti-escalada de este ciclo. Al publicar se recortarán automáticamente:
          <ul class="mv-audit-caps">
<?php foreach ($capsPrevistos as $c): ?>
            <li>[<?php echo htmlspecialchars_uni($c['ambito']); ?>] <?php echo htmlspecialchars_uni($c['slug']); ?> · <?php echo htmlspecialchars_uni($c['metrica']); ?>: propuesto <?php echo ($c['propuesto_delta'] >= 0 ? '+' : '') . (int)$c['propuesto_delta']; ?>, se aplicará <?php echo ($c['aplicado_delta'] >= 0 ? '+' : '') . (int)$c['aplicado_delta']; ?></li>
<?php endforeach; ?>
          </ul>
        </div>
<?php endif; ?>
        <h3 class="mv-prev-h">Noticia de portada</h3>
        <div class="mv-prev-box"><b><?php echo htmlspecialchars_uni($preview['noticia']['titulo']); ?></b><p><?php echo htmlspecialchars_uni($preview['noticia']['resumen']); ?></p><div class="mv-prev-html"><?php echo $preview['noticia']['cuerpo']; ?></div></div>

        <h3 class="mv-prev-h">Cómo se vería el periódico</h3>
        <div class="gbe-periodico mv-prev-per" style="background-image:url('<?php echo $bburl; ?>/images/mundo-vivo/paper.jpg')"><?php echo $preview['periodico']; ?></div>

        <h3 class="mv-prev-h">Cambios en el mundo</h3>
<?php if ($diffVacio): ?>
        <p class="mv-empty">El nuevo estado no modifica ninguna métrica del tablero actual.</p>
<?php else: ?>
        <div class="mv-diff">
<?php if (!empty($diff['zonas'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Zonas</h4>
<?php foreach ($diff['zonas'] as $zc): ?>
            <div class="mv-diff-block">
              <div class="mv-diff-name"><?php echo htmlspecialchars_uni($zc['nombre']); ?> <code><?php echo htmlspecialchars_uni($zc['slug']); ?></code></div>
<?php foreach ($zc['cambios'] as $c): ?>
              <div class="mv-diff-line mv-diff-<?php echo $c['dir'] > 0 ? 'up' : 'down'; ?>">
                <span class="mv-diff-m"><?php echo htmlspecialchars_uni($c['label']); ?></span>
                <span class="mv-diff-v"><?php echo htmlspecialchars_uni($c['antes_lbl']); ?> (<?php echo (int)$c['antes']; ?>) <span class="mv-diff-arrow"><?php echo $c['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($c['despues_lbl']); ?> (<?php echo (int)$c['despues']; ?>)</span>
              </div>
<?php endforeach; ?>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
<?php if (!empty($diff['facciones'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Facciones</h4>
<?php foreach ($diff['facciones'] as $fc): ?>
            <div class="mv-diff-block">
              <div class="mv-diff-name"><?php echo htmlspecialchars_uni($fc['nombre']); ?> <code><?php echo htmlspecialchars_uni($fc['slug']); ?></code></div>
<?php foreach ($fc['cambios'] as $c): ?>
              <div class="mv-diff-line mv-diff-<?php echo $c['dir'] > 0 ? 'up' : 'down'; ?>">
                <span class="mv-diff-m"><?php echo htmlspecialchars_uni($c['label']); ?></span>
                <span class="mv-diff-v"><?php echo htmlspecialchars_uni($c['antes_lbl']); ?> (<?php echo (int)$c['antes']; ?>) <span class="mv-diff-arrow"><?php echo $c['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($c['despues_lbl']); ?> (<?php echo (int)$c['despues']; ?>)</span>
              </div>
<?php endforeach; ?>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
<?php if (!empty($diff['tension'])): ?>
          <div class="mv-diff-group">
            <h4 class="mv-diff-gt">Tensiones por mar</h4>
<?php foreach ($diff['tension'] as $tc): ?>
            <div class="mv-diff-line mv-diff-<?php echo $tc['dir'] > 0 ? 'up' : 'down'; ?>">
              <span class="mv-diff-m"><?php echo htmlspecialchars_uni($tc['zona_nombre']); ?> · <?php echo htmlspecialchars_uni(str_replace('|', ' vs ', $tc['par'])); ?></span>
              <span class="mv-diff-v"><?php echo htmlspecialchars_uni($tc['antes_lbl']); ?> (<?php echo (int)$tc['antes']; ?>) <span class="mv-diff-arrow"><?php echo $tc['dir'] > 0 ? '↑' : '↓'; ?></span> <?php echo htmlspecialchars_uni($tc['despues_lbl']); ?> (<?php echo (int)$tc['despues']; ?>)</span>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
<?php endif; ?>

        <h3 class="mv-prev-h">Misiones que surgirán</h3>
<?php if (empty($preview['misiones'])): ?>
        <p class="mv-empty">La IA no propuso misiones en el bloque ===MISIONES===.</p>
<?php else: ?>
        <p class="mv-note">Se crearán automáticamente como misiones "en curso" del próximo ciclo al pulsar Publicar — no hace falta redactarlas ni filtrarlas a mano.</p>
        <div class="mv-mislist">
<?php foreach ($preview['misiones'] as $ms): ?>
          <div class="mv-miscard mv-mis-dif-<?php echo htmlspecialchars_uni($ms['dificultad'] !== '' ? $ms['dificultad'] : 'na'); ?>">
            <div class="mv-miscard-h">
              <span class="mv-miscard-t"><?php echo htmlspecialchars_uni($ms['titulo'] !== '' ? $ms['titulo'] : '(sin título)'); ?></span>
<?php if ($ms['dificultad'] !== ''): ?><span class="mv-miscard-dif"><?php echo htmlspecialchars_uni(ucfirst($ms['dificultad'])); ?></span><?php endif; ?>
            </div>
            <div class="mv-miscard-meta">
<?php if ($ms['zona'] !== ''): ?><span>Zona: <?php echo htmlspecialchars_uni($ms['zona']); ?></span><?php endif; ?>
<?php if ($ms['facciones'] !== ''): ?><span>Facciones: <?php echo htmlspecialchars_uni($ms['facciones']); ?></span><?php endif; ?>
            </div>
<?php if ($ms['resumen'] !== ''): ?><p class="mv-miscard-res"><?php echo htmlspecialchars_uni($ms['resumen']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <h3 class="mv-prev-h">Misiones resueltas por la IA</h3>
<?php if (empty($preview['misiones_resueltas'])): ?>
        <p class="mv-empty">La IA no resolvió ninguna misión en el bloque ===MISIONES_RESUELTAS=== (o no había ninguna EN CURSO).</p>
<?php else: ?>
        <div class="mv-mislist">
<?php foreach ($preview['misiones_resueltas'] as $mr): ?>
          <div class="mv-miscard">
            <div class="mv-miscard-h"><span class="mv-miscard-t">#<?php echo (int)$mr['id']; ?> — <?php echo htmlspecialchars_uni(str_replace('_', ' ', $mr['estado'])); ?></span></div>
<?php if ($mr['resumen'] !== ''): ?><p class="mv-miscard-res"><?php echo htmlspecialchars_uni($mr['resumen']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>

        <h3 class="mv-prev-h">Tramas abiertas</h3>
<?php if (empty($arcosNuevos)): ?>
        <p class="mv-empty">El nuevo estado no deja arcos abiertos.</p>
<?php else: ?>
        <div class="mv-arclist">
<?php foreach ($arcosNuevos as $ar): if (!is_array($ar)) continue; ?>
          <div class="mv-arccard">
            <div class="mv-arccard-h"><span class="mv-arccard-t"><?php echo htmlspecialchars_uni((string)($ar['nombre'] ?? '(sin nombre)')); ?></span><span class="mv-arccard-est"><?php echo htmlspecialchars_uni((string)($ar['estado'] ?? '')); ?></span></div>
            <div class="mv-arccard-meta">
<?php if (!empty($ar['zonas'])): ?><span>Zonas: <?php echo htmlspecialchars_uni((string)$ar['zonas']); ?></span><?php endif; ?>
<?php if (!empty($ar['facciones'])): ?><span>Facciones: <?php echo htmlspecialchars_uni((string)$ar['facciones']); ?></span><?php endif; ?>
            </div>
<?php if (!empty($ar['descripcion'])): ?><p class="mv-arccard-desc"><?php echo htmlspecialchars_uni((string)$ar['descripcion']); ?></p><?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>

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

        <h3 class="mv-prev-h">Tracking de NPCs mayores</h3>
<?php
$npcTrackPrev = (is_array($preview['estado']) && !empty($preview['estado']['npc_tracking'])) ? $preview['estado']['npc_tracking'] : array();
if (empty($npcTrackPrev)): ?>
        <p class="mv-empty">La IA no devolvió tracking de NPCs.</p>
<?php else:
  $currentTracking = gbe_rol_mv_npc_tracking_from_db();
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

        <form method="post" onsubmit="return confirm('¿Publicar el nuevo estado del mundo, el periódico y la noticia?');">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="mv_action" value="publicar">

          <h3 class="mv-prev-h">Imágenes a generar</h3>
<?php if (empty($preview['imagenes_list'])): ?>
          <p class="mv-empty">La IA no devolvió imágenes en el bloque ===IMAGENES===.</p>
<?php else: ?>
          <p class="mv-note">Pega el link directo de cada imagen ya generada. Al publicar se insertarán en el periódico en lugar de los recuadros de marcador; las que dejes en blanco se quedan como marcador.</p>
          <div class="mv-imglist">
<?php foreach ($preview['imagenes_list'] as $im): ?>
            <div class="mv-imgcard">
              <div class="mv-imgcard-h"><code><?php echo htmlspecialchars_uni($im['id']); ?></code><?php if ($im['tamano'] !== ''): ?> <span class="mv-imgcard-sz"><?php echo htmlspecialchars_uni($im['tamano']); ?></span><?php endif; ?></div>
<?php if ($im['prompt'] !== ''): ?><p class="mv-imgcard-prompt"><?php echo htmlspecialchars_uni($im['prompt']); ?></p><?php endif; ?>
              <input type="url" class="mv-input mv-imgcard-url" name="img_url[<?php echo htmlspecialchars_uni($im['id']); ?>]" placeholder="https://... link directo a la imagen">
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>

          <div class="mv-save-bar"><button class="btn btn-primary">Publicar</button></div>
        </form>
      </div>
    </div>
<?php endif; ?>
  </section>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var tabs = document.querySelectorAll('#mvTabs .mv-tab');
  var panels = document.querySelectorAll('.mv-panel');
  tabs.forEach(function(t){
    t.addEventListener('click', function(){
      tabs.forEach(function(x){x.classList.remove('on');});
      t.classList.add('on');
      var id = t.getAttribute('data-tab');
      panels.forEach(function(p){ p.hidden = (p.id !== 'tab-'+id); if(!p.hidden){ p.classList.add('vis'); } });
    });
  });
})();
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .05 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
