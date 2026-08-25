<?php
/**
 * One Piece: Eternal · Biblioteca de Lore y Cronología Histórica
 * Crónica oficial del mundo de One Piece: Eternal: historia, cronología, eras,
 * el Pacto de las Quince Coronas, las facciones, los Yonkou y el equilibrio del mundo.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-lore.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$npcs = function_exists('ope_rol_cat_npcs_publicos') ? ope_rol_cat_npcs_publicos() : array();
$npc_map = array();
foreach ($npcs as $n) {
    $s = $n['slug'] ?? '';
    if ($s !== '') $npc_map[$s] = $n;
}

function tomo_npc_link($slug, $map, $bburl) {
    if (!isset($map[$slug])) {
        $legible = ucwords(str_replace('-', ' ', $slug));
        return '<span class="tomo-npc-ref is-plain">' . htmlspecialchars($legible) . '</span>';
    }
    $n = $map[$slug];
    $fc = $n['faccion_slug'] ?? '';
    $display = !empty($n['apodo']) ? $n['nombre'] . ' «' . $n['apodo'] . '»' : $n['nombre'];
    $url = $bburl . '/ficha.php?pid=' . ((int)$n['pid']);
    return '<a href="' . htmlspecialchars($url) . '" class="tomo-npc-ref fac-' . htmlspecialchars($fc) . '" title="Ver ficha de ' . htmlspecialchars($n['nombre']) . '">' . htmlspecialchars($display) . '</a>';
}

function tomo_npcify($text, $map, $bburl) {
    return preg_replace_callback('/\{npc:([a-z0-9-]+)\}/', function($m) use ($map, $bburl) {
        return tomo_npc_link($m[1], $map, $bburl);
    }, $text);
}

$total_capitulos = 7;

header('Content-Type: text/html; charset=utf-8');
ob_start();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Lore y Cronología</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-guias bib-lore">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Lore y Cronología</b></div></div>
<div class="wrap">

<section class="reveal">
  <div class="shead">
    <h1>Biblioteca de Lore & Cronología Histórica</h1>
    <span class="code">// archivo en construcción · la crónica aún no se ha escrito</span>
    <span class="rule"></span>
  </div>
  <p class="guia-intro">El <b>archivo oficial</b> de One Piece: Eternal. Aquí se publicará la crónica del mundo cuando el equipo la redacte.</p>
</section>

<section class="reveal">
  <div class="guide-shell">

    <div class="guide-main">
      <div class="guide-content active" id="g-historia">
        <div class="g-title">Biblioteca de Lore</div>
        <div class="g-sub">// el archivo está en blanco</div>
        <p>La crónica de este mundo aún no se ha escrito. Cuando el equipo publique los capítulos de la Biblioteca de Lore, aparecerán aquí.</p>
      </div>
    </div>
  </div></section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  const nav = document.getElementById('loreNav');
  if (!nav) return;
  const buttons = nav.querySelectorAll('.guide-nav-item');
  const contents = document.querySelectorAll('.guide-content');
  function activate(id) {
    buttons.forEach(b => b.classList.toggle('active', b.dataset.guide === id));
    contents.forEach(c => c.classList.toggle('active', c.id === 'g-' + id));
    window.scrollTo({ top: nav.closest('.reveal').offsetTop - 20, behavior: 'smooth' });
  }
  buttons.forEach(b => b.addEventListener('click', () => activate(b.dataset.guide)));
  window.addEventListener('hashchange', function() {
    const hash = location.hash.replace('#','');
    if (hash && document.getElementById('g-' + hash)) activate(hash);
  });
  const initial = location.hash.replace('#','');
  if (initial && document.getElementById('g-' + initial)) activate(initial);
})();

if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
}
</script>

</body>
</html>
<?php
$output = ob_get_clean();
$output = tomo_npcify($output, $npc_map, $bburl);
echo $output;
