<?php
/**
 * I-Forge · Trámites del taller
 * Página de front-end MyBB (dirección "Granblue Fantasy: Eternal").
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

require_once MYBB_ROOT . 'inc/gbe_user_init.php';

// Nivel de staff (plugin gbe_rol, con respaldo directo)
$staff_level = gbe_get_staff_level($uid);

$initials   = gbe_get_initials($mybb->user['username'] ?? '');
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
    array(
        'title'    => 'Tablón de Misiones',
        'code'     => 'TRA-02',
        'cat'      => 'mundo',
        'cat_lbl'  => 'Mundo Vivo',
        'icon'     => '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        'body'     => 'Consulta las misiones disponibles este ciclo y acepta las que quieras completar. Cada misión tiene una zona, facciones implicadas y un resumen de objetivos.',
        'meta'     => '// tablón de misiones',
        'link'     => 'tablon-misiones.php',
        'link_lbl' => 'Ver misiones',
    ),
    array(
        'title'    => 'Tienda',
        'code'     => 'TRA-03',
        'cat'      => 'mundo',
        'cat_lbl'  => 'Mundo Vivo',
        'icon'     => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'body'     => 'Compra objetos, consumibles, mejoras y artículos especiales para tu personaje. Usa tus berries para equiparte y prepararte para la aventura.',
        'meta'     => '// tienda del foro',
        'link'     => 'tienda.php',
        'link_lbl' => 'Ir a la tienda',
    ),
    array(
        'title'    => 'Solicitar acompa&ntilde;ante',
        'code'     => 'TRA-05',
        'cat'      => 'personaje',
        'cat_lbl'  => 'Personaje',
        'icon'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'body'     => 'Asocia un <b>NPC secundario</b> de la biblioteca del staff a tu personaje activo. Puedes llevar hasta <b>dos acompa&ntilde;antes</b> y usar sus t&eacute;cnicas al postear con el RPG System.',
        'meta'     => '// NPCs secundarios · m&aacute;x. 2',
        'link'     => 'solicitar-acompanante.php',
        'link_lbl' => 'Solicitar',
    ),
    array(
        'title'    => 'Oráculo de Viaje',
        'code'     => 'TRA-04',
        'cat'      => 'navegacion',
        'cat_lbl'  => 'Navegación',
        'icon'     => '<path d="M3 18h18"/><path d="M5 16V8l7-4 7 4v8"/><path d="M9 16v-5h6v5"/><path d="M12 4v3"/>',
        'body'     => 'Solicita una travesía entre islas. <b>Lyria</b> tira el oráculo (clima, encuentros, hallazgos, peligros) y abre un hilo en Alta Mar. Cierras la llegada cuando la tripulación lo decida.',
        'meta'     => '// oráculo · 4 mesas D100',
        'link'     => 'viajes.php',
        'link_lbl' => 'Solicitar viaje',
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
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-tramites) -->
</head>
<body class="gbe-pg-tramites">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Trámites</b>
  </div>
</div>

<div class="wrap">

  <?php echo gbe_rol_deco_banner('ope/deco/tramites', 'Ventanillas de trámites oficiales del foro', 'Trámites oficiales'); ?>


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
