<?php
/**
 * I-Forge · Periódicos "Eternal News" (público)
 * Lista de números anteriores a la izquierda; el seleccionado (o el último) a la
 * derecha, renderizado con el componente ope-periodico.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'periodicos.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$periodicos = ope_rol_mv_periodicos(120);

$sel_id = (int)$mybb->get_input('c', MyBB::INPUT_INT);
$actual = null;
if ($sel_id > 0) {
    $actual = ope_rol_mv_ciclo_by_id($sel_id);
    if ($actual && (int)$actual['published_at'] <= 0) { $actual = null; }
}
if (!$actual && !empty($periodicos)) {
    $actual = ope_rol_mv_ciclo_by_id((int)$periodicos[0]['ciclo_id']);
}

$masthead = $bburl . '/images/mundo-vivo/masthead.png';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Eternal News</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-periodicos + ope-periodico) -->
</head>
<body class="ope-pg-periodicos">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Periódicos</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Eternal News</h1><span class="code">// hemeroteca del mundo</span><span class="rule"></span></div>
  </section>

<?php if (!$actual): ?>
  <section class="reveal"><div class="plate"><div class="plate-b">
    <p class="tram-intro" style="margin:0">Todavía no se ha publicado ningún número de <b>Eternal News</b>. Vuelve tras el primer cierre de mes.</p>
  </div></div></section>
<?php else: ?>
  <section class="reveal">
    <div class="pe-layout">
      <aside class="pe-archive">
        <div class="pe-archive-h">Números anteriores</div>
        <ul>
<?php foreach ($periodicos as $p):
        $on = ((int)$p['ciclo_id'] === (int)$actual['ciclo_id']) ? ' class="on"' : '';
?>
          <li<?php echo $on; ?>><a href="<?php echo $bburl; ?>/periodicos.php?c=<?php echo (int)$p['ciclo_id']; ?>">
            <b><?php echo htmlspecialchars_uni($p['periodo']); ?></b>
            <span><?php echo htmlspecialchars_uni($p['noticia_titulo'] !== '' ? $p['noticia_titulo'] : 'Edición mensual'); ?></span>
          </a></li>
<?php endforeach; ?>
        </ul>
      </aside>

      <main class="pe-current">
<?php
        $ts_onrol   = (int)($actual['published_at'] ?? 0) > 0 ? (int)$actual['published_at'] : TIME_NOW;
        $ooc_label   = ope_rol_mv_periodo_label($actual['periodo']);
        $onrol_label = ope_rol_mv_fecha_onrol($ts_onrol);
?>
        <div class="ope-periodico" style="background-image:url('<?php echo $bburl; ?>/images/mundo-vivo/paper.jpg')">
          <header class="ope-per-masthead">
            <div class="ope-per-date ope-per-date-ooc">
              <span class="k">Edición</span>
              <b><?php echo htmlspecialchars_uni($ooc_label); ?></b>
              <small>tiempo real</small>
            </div>
            <img class="ope-per-logo" src="<?php echo $masthead; ?>" alt="Eternal News">
            <div class="ope-per-date ope-per-date-onrol">
              <span class="k">Era del mundo</span>
              <b><?php echo htmlspecialchars_uni($onrol_label); ?></b>
              <small>tiempo in-rol</small>
            </div>
          </header>
          <div class="ope-per-body">
<?php echo (string)$actual['periodico_html']; ?>
          </div>
        </div>
      </main>
    </div>
  </section>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .04 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
