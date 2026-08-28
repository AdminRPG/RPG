<?php
/**
 * One Piece: 7 Seas · Reliquias (zona staff — Anexo A.3, F6)
 * ----------------------------------------------------------
 * Panel «Reliquias» (Staff 20.1–20.5, 5.21-bis): fichas muertas con su
 * leyenda (visibles para el mundo) e histórico de muertes con calidad y
 * herencia. Solo staff. Scope CSS: body.ope-pg-reliquias-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'reliquias-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de Reliquias.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Reliquias</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-reliquias-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Reliquias</b>
</div></div>
<div class="wrap">
<?php echo ope7_reliquias_panel_html(); ?>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.plate').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.plate').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
