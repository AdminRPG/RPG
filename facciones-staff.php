<?php
/**
 * One Piece: 7 Seas · Facciones (zona staff — Anexo A.3, F4.3)
 * ------------------------------------------------------------
 * Panel «Facciones» (13.8): tablero de rangos y cupos por facción, ascensos
 * en cola (trámite 20 — la skill propone, el staff firma), subfacciones de
 * élite (Shichibukai cupo 7) e histórico inmutable de cambios. Solo staff.
 * Scope CSS: body.ope-pg-facciones-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'facciones-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de facciones.');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Facciones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-facciones-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Facciones</b>
</div></div>
<div class="wrap">
<?php echo ope7_facciones_panel_html(); ?>
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
