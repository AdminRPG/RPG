<?php
/**
 * One Piece: Eternal · Trámites (hub)
 * Skeleton — cards se agregan una a una.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramites.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámites</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tramites">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Trámites</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Trámites</h1>
      <span class="code">// ventanillas</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Solicitudes que requieren revisión humana. Autoservicio (stats, Eternal, Ken/Buso normales, Fruta Nv.0–2) vive en la <a href="<?php echo $bburl; ?>/ficha.php">ficha</a>.</p>
  </section>
  <!-- Agregar cards aquí -->
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
