<?php
/**
 * I-Forge · Estado del Mundo (público)
 * La Balanza en detalle: cada mar es clicable y abre su ficha completa (todas las
 * métricas, notas y tensiones entre facciones EN ESE MAR con su porqué). Las
 * facciones muestran su perfil completo. Visible por todos.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'estado-mundo.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$zonas     = ope_rol_mv_zonas();
$facciones = ope_rol_mv_facciones();
$tension   = ope_rol_mv_tension();          // anidado por zona
$arcos     = ope_rol_mv_arcos();
$npcs      = ope_rol_mv_npc_mayores();
$ultimo    = ope_rol_mv_ultimo_publicado();

$zMetrics = ope_rol_mv_zona_metrics();
$fMetrics = ope_rol_mv_faccion_metrics();

$hero = $bburl . '/images/mundo-vivo/estado-hero.jpg';

/** Barra de una métrica. $signed=true para REP (-100..100). */
function mv_metric_bar($label, $val, $bandLabel, $color, $signed = false)
{
    $val = (int) $val;
    if ($signed) {
        $w = (int) round((($val + 100) / 200) * 100);
        $shown = ($val > 0 ? '+' : '') . $val;
    } else {
        $w = max(0, min(100, $val));
        $shown = $val;
    }
    $h  = '<div class="em-bar">';
    $h .= '<span class="em-bar-l">' . htmlspecialchars_uni($label) . '</span>';
    $h .= '<span class="em-bar-track"><i style="width:' . $w . '%;background:' . $color . '"></i></span>';
    $h .= '<span class="em-bar-v">' . htmlspecialchars_uni($bandLabel) . ' <em>' . $shown . '</em></span>';
    $h .= '</div>';
    return $h;
}

function mv_tension_class($v)
{
    $v = (int) $v;
    if ($v >= 81) return 'em-t-war';
    if ($v >= 61) return 'em-t-high';
    if ($v >= 41) return 'em-t-mid';
    return 'em-t-low';
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Estado del mundo</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-estado-mundo) -->
</head>
<body class="ope-pg-estado-mundo">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Estado del mundo</b>
  </div>
</div>

<div class="em-hero" style="background-image:linear-gradient(rgba(20,16,12,.5),rgba(20,16,12,.82)),url('<?php echo $hero; ?>')">
  <div class="em-hero-in">
    <span class="em-hero-k">// la balanza del mundo</span>
    <h1>Estado del mundo</h1>
    <p><?php echo $ultimo ? 'Última actualización: <a href="' . $bburl . '/periodicos.php?c=' . (int)$ultimo['ciclo_id'] . '">' . htmlspecialchars_uni($ultimo['periodo']) . '</a>' : 'El mundo aún no ha vivido su primer ciclo.'; ?></p>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead"><h2>Los mares</h2><span class="code">// pulsa un mar para ver su ficha completa</span><span class="rule"></span></div>
    <div class="em-grid">
<?php foreach ($zonas as $z): ?>
      <button type="button" class="em-zona" data-zona="<?php echo htmlspecialchars_uni($z['slug']); ?>" aria-haspopup="dialog">
        <div class="em-zona-top"><h3><?php echo htmlspecialchars_uni($z['nombre']); ?></h3><span class="em-zona-more">Ver notas &rarr;</span></div>
        <div class="em-bars">
<?php foreach ($zMetrics as $k => $m):
          echo mv_metric_bar($m['label'], $z[$k], ope_rol_mv_band5($z[$k], $m['bands']), $m['col']);
        endforeach; ?>
        </div>
      </button>
<?php endforeach; ?>
    </div>
  </section>

  <section class="reveal">
    <div class="shead"><h2>Facciones</h2><span class="code">// perfil de poder</span><span class="rule"></span></div>
    <div class="em-grid em-grid-fac">
<?php foreach ($facciones as $f): ?>
      <article class="em-fac em-fac-<?php echo htmlspecialchars_uni($f['slug']); ?>">
        <h3><?php echo htmlspecialchars_uni($f['nombre']); ?></h3>
        <div class="em-bars">
<?php foreach ($fMetrics as $k => $m):
          $signed = !empty($m['special']) && $m['special'] === 'rep';
          $band = ope_rol_mv_faccion_metric_label($k, $f[$k]);
          echo mv_metric_bar($m['label'], $f[$k], $band, $m['col'], $signed);
        endforeach; ?>
        </div>
<?php if (trim((string)$f['notas']) !== ''): ?>
        <p class="em-notas"><?php echo nl2br(htmlspecialchars_uni((string)$f['notas'])); ?></p>
<?php endif; ?>
      </article>
<?php endforeach; ?>
    </div>
  </section>

<?php if (!empty($arcos)): ?>
  <section class="reveal">
    <div class="shead"><h2>Arcos en marcha</h2><span class="code">// grandes tramas</span><span class="rule"></span></div>
    <div class="em-arcos">
<?php foreach ($arcos as $a): ?>
      <article class="em-arco">
        <h3><?php echo htmlspecialchars_uni($a['nombre']); ?> <span class="em-arco-st"><?php echo htmlspecialchars_uni($a['estado']); ?></span></h3>
<?php if (trim((string)$a['descripcion']) !== ''): ?><p><?php echo nl2br(htmlspecialchars_uni((string)$a['descripcion'])); ?></p><?php endif; ?>
      </article>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

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
        <?php if (!empty($th['facciones_implicadas'])): ?><p class="em-notas">Facciones: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['facciones_implicadas'])); ?></p><?php endif; ?>
        <?php if (!empty($th['npc_implicados'])): ?><p class="em-notas">NPCs: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['npc_implicados'])); ?></p><?php endif; ?>
        <?php if (!empty($th['pj_implicados'])): ?><p class="em-notas">PJs: <?php echo htmlspecialchars_uni(implode(', ', (array)$th['pj_implicados'])); ?></p><?php endif; ?>
        <?php if (!empty($th['proxima_evolucion'])): ?><p class="em-notas">Próxima evolución: <?php echo htmlspecialchars_uni($th['proxima_evolucion']); ?></p><?php endif; ?>
      </article>
<?php endforeach; ?>
    </div>
  </section>
<?php endif; endif; ?>

<?php
  $npcs_ubicados = array_filter($npcs, function ($n) { return trim((string)$n['mundo_zona']) !== '' || trim((string)$n['mundo_ubic']) !== '' || !empty($n['datos_publicos']); });
  if (!empty($npcs_ubicados)):
?>
  <section class="reveal">
    <div class="shead"><h2>Figuras del mundo</h2><span class="code">// NPCs y su paradero</span><span class="rule"></span></div>
    <div class="em-grid em-grid-fac">
<?php foreach ($npcs_ubicados as $n):
        $pub = $n['datos_publicos'] ?? array();
        $zname = isset($zonas[$n['mundo_zona']]) ? $zonas[$n['mundo_zona']]['nombre'] : $n['mundo_zona'];
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
    </div>
  </section>
<?php endif; ?>

</div>

<!-- Fichas completas de cada mar (ocultas; se cargan en el modal al pulsar) -->
<div class="em-zonafull-store" hidden>
<?php foreach ($zonas as $z):
        $zt = isset($tension[$z['slug']]) ? $tension[$z['slug']] : array();
        uasort($zt, function ($a, $b) { return $b['valor'] <=> $a['valor']; });
        $zt_top3 = array_slice($zt, 0, 3, true);
        $zt_rest = array_slice($zt, 3, null, true);
?>
  <div id="zfull-<?php echo htmlspecialchars_uni($z['slug']); ?>" data-title="<?php echo htmlspecialchars_uni($z['nombre']); ?>">
    <div class="em-full-metrics">
<?php foreach ($zMetrics as $k => $m):
        echo mv_metric_bar($m['label'], $z[$k], ope_rol_mv_band5($z[$k], $m['bands']), $m['col']);
      endforeach; ?>
    </div>
<?php if (trim((string)$z['notas']) !== ''): ?>
    <div class="em-full-block">
      <h4>Notas del mar</h4>
      <p class="em-notas"><?php echo nl2br(htmlspecialchars_uni((string)$z['notas'])); ?></p>
    </div>
<?php endif; ?>
    <div class="em-full-block">
      <h4>Tensiones entre facciones en este mar</h4>
<?php if (empty($zt)): ?>
      <p class="em-notas">Sin datos de tensión para este mar.</p>
<?php else: foreach ($zt_top3 as $par => $info):
          $na = isset($facciones[$info['a']]) ? $facciones[$info['a']]['nombre'] : $info['a'];
          $nb = isset($facciones[$info['b']]) ? $facciones[$info['b']]['nombre'] : $info['b'];
?>
      <div class="em-ten <?php echo mv_tension_class($info['valor']); ?>">
        <span class="em-ten-p"><?php echo htmlspecialchars_uni($na); ?> <em>vs</em> <?php echo htmlspecialchars_uni($nb); ?></span>
        <span class="em-ten-l"><?php echo ope_rol_mv_tension_label($info['valor']); ?> <em><?php echo (int)$info['valor']; ?></em></span>
      </div>
<?php if (trim((string)$info['notas']) !== ''): ?>
      <p class="em-ten-note"><?php echo nl2br(htmlspecialchars_uni((string)$info['notas'])); ?></p>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!empty($zt_rest)): ?>
      <details class="em-ten-more">
        <summary>Ver todas las tensiones (<?php echo count($zt_rest); ?> más)</summary>
<?php foreach ($zt_rest as $par => $info):
          $na = isset($facciones[$info['a']]) ? $facciones[$info['a']]['nombre'] : $info['a'];
          $nb = isset($facciones[$info['b']]) ? $facciones[$info['b']]['nombre'] : $info['b'];
?>
        <div class="em-ten <?php echo mv_tension_class($info['valor']); ?>">
          <span class="em-ten-p"><?php echo htmlspecialchars_uni($na); ?> <em>vs</em> <?php echo htmlspecialchars_uni($nb); ?></span>
          <span class="em-ten-l"><?php echo ope_rol_mv_tension_label($info['valor']); ?> <em><?php echo (int)$info['valor']; ?></em></span>
        </div>
<?php if (trim((string)$info['notas']) !== ''): ?>
        <p class="em-ten-note"><?php echo nl2br(htmlspecialchars_uni((string)$info['notas'])); ?></p>
<?php endif; ?>
<?php endforeach; ?>
      </details>
<?php endif; endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div id="em-modal" class="em-modal" hidden>
  <div class="em-modal-bg" data-close="1"></div>
  <div class="em-modal-box" role="dialog" aria-modal="true" aria-labelledby="em-modal-title">
    <button type="button" class="em-modal-x" data-close="1" aria-label="Cerrar">&times;</button>
    <div class="em-modal-kicker">// ficha del mar</div>
    <h2 id="em-modal-title"></h2>
    <div id="em-modal-body" class="em-modal-body"></div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var modal = document.getElementById('em-modal');
  var mTitle = document.getElementById('em-modal-title');
  var mBody = document.getElementById('em-modal-body');
  function open(slug){
    var src = document.getElementById('zfull-' + slug);
    if(!src || !modal) return;
    mTitle.textContent = src.getAttribute('data-title') || 'Mar';
    mBody.innerHTML = src.innerHTML;
    modal.hidden = false; document.body.style.overflow = 'hidden';
  }
  function close(){ if(modal){ modal.hidden = true; document.body.style.overflow=''; } }
  document.querySelectorAll('.em-zona').forEach(function(btn){
    btn.addEventListener('click', function(){ open(btn.getAttribute('data-zona')); });
  });
  if(modal){
    modal.addEventListener('click', function(e){ if(e.target.getAttribute('data-close')) close(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') close(); });
  }
})();
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
