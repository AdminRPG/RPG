<?php
/**
 * One Piece: Eternal · Guías
 * Página vaciada — se rellenará con contenido OPE en F7.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'guias.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Guías</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-guias">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Guías</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Guías</h1>
      <span class="code">// reglamento &amp; sistema</span>
      <span class="rule"></span>
    </div>
    <p class="guia-intro">Las guías oficiales del sistema se publicarán aquí cuando el contenido OPE esté listo.</p>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Contenido</span>
        <span class="c">// en preparación</span>
      </div>
      <div class="plate-b">
        <p class="pj-empty">No hay guías publicadas por ahora.</p>
      </div>
    </div>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
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
