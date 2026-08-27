<?php
/**
 * One Piece: 7 Seas · Resolución de combates (zona staff — A.3 «Combate», F2.3)
 * ----------------------------------------------------------------------------
 * Panel de resolución al cierre: salas con sus turnos (pa_total vs pa_gastado),
 * excesos y avisos, generación/regeneración de veredictos con las tablas de
 * delta (matices ajustables) y firma del veredicto que el trámite 2 (cierre)
 * referencia. Solo staff. Scope CSS: body.ope-pg-resolucion.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'resolucion-combate.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder a la resolución de combates.');
}

$flash = '';
if ($mybb->request_method === 'post') {
    $accion  = (string) $mybb->get_input('accion');
    $sala_id = (int) $mybb->get_input('sala_id', 1);

    if ($accion === 'resolver' && $sala_id > 0) {
        $matices = array_map('intval', (array) $mybb->get_input('matices', MyBB::INPUT_ARRAY));
        $res = ope7_resolucion_generar($sala_id, $matices);
        $flash = '<div class="flash ' . ($res['ok'] ? 'ok' : 'warn') . '">' . htmlspecialchars_uni($res['msg']) . '</div>';
    } elseif ($accion === 'firmar' && $sala_id > 0) {
        $motivo = trim((string) $mybb->get_input('motivo'));
        if ($motivo === '') {
            $flash = '<div class="flash warn">La firma requiere un motivo.</div>';
        } else {
            $res = ope7_resolucion_firmar($sala_id, $uid, $motivo);
            $flash = '<div class="flash ' . ($res['ok'] ? 'ok' : 'warn') . '">' . htmlspecialchars_uni($res['msg']) . '</div>';
        }
    }
}

$detalle_id = (int) $mybb->get_input('sala', 1);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Resolución de combates</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-resolucion">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Resolución de combates</b>
</div></div>
<div class="wrap">
<?php echo ope7_resolucion_html($detalle_id, $flash); ?>
</div>
<?php include 'inc/footer_custom.php'; ?>
<script>
(function () {
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    });
    document.querySelectorAll('.plate').forEach(function (el) { io.observe(el); });
})();
</script>
</body>
</html>
