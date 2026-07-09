<?php
/**
 * I-Forge · Trámites del taller
 * Página de front-end MyBB (dirección "One Piece Eternal").
 * Estructura de servicios del taller. Sin datos de ejemplo ni saldos inventados.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramites.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Nivel de staff (plugin ope_rol, con respaldo directo)
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['ope_staff_level'])) {
        $staff_level = (int)$mybb->user['ope_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int)$db->fetch_field($cq, 'staff_level');
        }
    }
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

// ── Catálogo de trámites (data-driven; los filtros se generan a partir de esto) ──
$tramites = array(
    array(
        'title'    => 'Notificar tema',
        'code'     => 'TRA-01',
        'cat'      => 'mundo',
        'cat_lbl'  => 'Mundo Vivo',
        'icon'     => '<path d="M4 4h16v12H5.2L4 17.2Z"/><path d="M8 9h8"/><path d="M8 12h5"/>',
        'body'     => 'Env&iacute;a el enlace de un tema <b>en presente</b> del mes en vigor y un resumen de lo que ocurre. Alimenta el estado del mundo y el peri&oacute;dico <b>Eternal News</b>.',
        'meta'     => '// eventos del mundo',
        'link'     => 'notificar-tema.php',
        'link_lbl' => 'Notificar',
    ),
);

// Categorías presentes (para la barra de filtros), en orden de aparición.
$cats = array();
foreach ($tramites as $t) {
    if (!isset($cats[$t['cat']])) $cats[$t['cat']] = $t['cat_lbl'];
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámites</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-tramites) -->
</head>
<body class="ope-pg-tramites">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Trámites</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Trámites</h1>
      <span class="code">// ventanillas de servicio</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Ventanillas oficiales del foro. Usa los filtros para encontrar el trámite que necesitas; se irán sumando más conforme se habiliten nuevos sistemas.</p>
  </section>

  <section class="reveal">
    <div class="tram-bar">
      <span class="bar-l">Filtrar:</span>
      <button type="button" class="tram-chip on" data-filter="all">Todos</button>
<?php foreach ($cats as $slug => $lbl): ?>
      <button type="button" class="tram-chip" data-filter="<?php echo htmlspecialchars_uni($slug); ?>"><?php echo htmlspecialchars_uni($lbl); ?></button>
<?php endforeach; ?>
    </div>
    <div class="cards">
<?php foreach ($tramites as $t): ?>
      <article class="card" data-cat="<?php echo htmlspecialchars_uni($t['cat']); ?>">
        <div class="card-top">
          <span class="card-ic"><svg viewBox="0 0 24 24"><?php echo $t['icon']; ?></svg></span>
          <div class="card-head">
            <div class="card-title"><?php echo htmlspecialchars_uni($t['title']); ?></div>
            <div class="card-code"><?php echo htmlspecialchars_uni($t['code']); ?></div>
          </div>
          <span class="card-tag"><?php echo htmlspecialchars_uni($t['cat_lbl']); ?></span>
        </div>
        <div class="card-body"><?php echo $t['body']; ?></div>
        <div class="card-foot">
          <span class="card-meta"><?php echo htmlspecialchars_uni($t['meta']); ?></span>
          <a href="<?php echo $bburl . '/' . htmlspecialchars_uni($t['link']); ?>" class="btn btn-ghost btn-sm"><?php echo htmlspecialchars_uni($t['link_lbl']); ?></a>
        </div>
      </article>
<?php endforeach; ?>
      <p class="tram-empty" hidden>No hay trámites en esta categoría por ahora.</p>
    </div>
  </section>

  <section class="horario reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Horario de atención</span>
        <span class="c">// atención de staff</span>
      </div>
      <div class="plate-b mono">
        <div class="hbit"><div class="hl">Atención del staff</div><div class="hv">L–V · 09:00–22:00 <small>CET</small></div></div>
        <div class="hbit"><div class="hl">Cola media · peticiones</div><div class="hv">~24 h <small>días laborables</small></div></div>
        <div class="hbit"><div class="hl">Cola media · rangos</div><div class="hv">~72 h <small>evaluación</small></div></div>
        <div class="hbit"><div class="hl">Incidencias urgentes</div><div class="hv">~48 h <small>respuesta</small></div></div>
      </div>
    </div>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
// --- Filtro de trámites ---
(function(){
  var chips = document.querySelectorAll('.tram-chip');
  var cards = document.querySelectorAll('.card[data-cat]');
  var empty = document.querySelector('.tram-empty');
  chips.forEach(function(chip){
    chip.addEventListener('click', function(){
      var f = chip.getAttribute('data-filter');
      chips.forEach(function(c){ c.classList.toggle('on', c === chip); });
      var shown = 0;
      cards.forEach(function(card){
        var vis = (f === 'all' || card.getAttribute('data-cat') === f);
        card.classList.toggle('hidden', !vis);
        if (vis) shown++;
      });
      if (empty) empty.hidden = (shown !== 0);
    });
  });
})();

// --- Reveal on scroll ---
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
