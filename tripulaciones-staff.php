<?php
/**
 * One Piece: 7 Seas · Tripulaciones (zona staff — Anexo A.3, F5.3)
 * -----------------------------------------------------------------
 * Panel «Tripulaciones» (5.21-ter/cap. 22.9): fichas activas con miembros y
 * su espacio (5.17), cofre común con log (5.9), avisos de disolución (<2
 * activos, 22.9) e histórico auditable. El staff puede iniciar desde aquí el
 * trámite 66 (cambio de capitán: cesión o motín con veredicto) y el 67
 * (disolución con reparto) — el motor valida y aplica al firmar.
 * Scope CSS: body.ope-pg-tripulaciones-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tripulaciones-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de tripulaciones.');
}

$flash = '';
if ($mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'))) {
    $gaccion = (string) $mybb->get_input('gaccion');
    if ($gaccion === 'cambio_capitan') {
        // Trámite 66 (staff): cesión o motín; el motor valida y aplica al firmar.
        $trip_id = (int) $mybb->get_input('tripulacion_id', 1);
        $trip = $trip_id > 0 && function_exists('ope7_trip_get') ? ope7_trip_get($trip_id) : null;
        if (!$trip || (string) $trip['estado'] !== 'activa') {
            $flash = 'Tripulación no encontrada o no activa.';
        } else {
            $r = ope7_tramite_crear($uid, (int) $trip['capitan_id'], 66,
                trim((string) $mybb->get_input('motivo')),
                array(
                    'tripulacion_id' => $trip_id,
                    'sucesor_id'     => (int) $mybb->get_input('sucesor_id', 1),
                    'tipo'           => in_array((string) $mybb->get_input('tipo'), array('cesion', 'motin'), true) ? (string) $mybb->get_input('tipo') : 'cesion',
                    'nuevo_nombre'   => trim((string) $mybb->get_input('nuevo_nombre')),
                ));
            $flash = $r['ok'] ? 'Trámite 66 creado: firma el cambio de capitán en la bandeja.' : $r['msg'];
        }
    } elseif ($gaccion === 'disolver') {
        // Trámite 67 (staff): disolución con reparto; el motor valida y aplica al firmar.
        $trip_id = (int) $mybb->get_input('tripulacion_id', 1);
        $trip = $trip_id > 0 && function_exists('ope7_trip_get') ? ope7_trip_get($trip_id) : null;
        if (!$trip || (string) $trip['estado'] !== 'activa') {
            $flash = 'Tripulación no encontrada o no activa.';
        } else {
            $r = ope7_tramite_crear($uid, (int) $trip['capitan_id'], 67,
                trim((string) $mybb->get_input('motivo')),
                array('tripulacion_id' => $trip_id));
            $flash = $r['ok'] ? 'Trámite 67 creado: firma la disolución en la bandeja (reparto del cofre + barco al último capitán).' : $r['msg'];
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Tripulaciones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tripulaciones-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Tripulaciones</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_tripulaciones_panel_html(); ?>
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