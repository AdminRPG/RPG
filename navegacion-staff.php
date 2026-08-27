<?php
/**
 * One Piece: 7 Seas · Navegación (zona staff — Anexo A.3, F4.3)
 * ------------------------------------------------------------
 * Panel «Navegación» (17.8): travesías activas por isla y jugador con
 * oráculos, tiempos y vencimientos, víveres pendientes al cierre, avisos
 * de plazo y el histórico de travesías resueltas/vencidas. La ficha se
 * edita y firma en la bandeja (trámite 38, skill-navegacion). Solo staff.
 * Scope CSS: body.ope-pg-navegacion-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'navegacion-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de navegación.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Navegación</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-navegacion-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Navegación</b>
</div></div>
<div class="wrap">
<?php echo ope7_navegacion_panel_html(); ?>
</div>
<?php include 'inc/footer_custom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ob = new IntersectionObserver(function (en) {
        en.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('revealed'); ob.unobserve(e.target); } });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { ob.observe(el); });
});
</script>
</body>
</html>
