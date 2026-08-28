<?php
/**
 * One Piece: 7 Seas · Cibernética (zona staff — Anexo A.3, F5.4)
 * --------------------------------------------------------------
 * Panel «Cibernética» (5.22/cap. 23): implantes por personaje con
 * zona/nivel/estado, requisitos acumulados (5.22 §A.2), mantenimientos
 * pendientes e histórico con firma. El staff puede iniciar desde aquí el
 * trámite 56 (instalación) y el 57 (retirada) — el motor valida y aplica
 * al firmar.
 * Scope CSS: body.ope-pg-cibernetica-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'cibernetica-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de cibernética.');
}

$flash = '';
if ($mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'))) {
    $gaccion = (string) $mybb->get_input('gaccion');
    if ($gaccion === 'instalar') {
        // Trámite 56 (ia): el motor valida y aplica al firmar.
        $pid = (int) $mybb->get_input('personaje_id', 1);
        $implante_id = (int) $mybb->get_input('implante_id', 1);
        if ($pid < 1 || $implante_id < 1) {
            $flash = 'Faltan datos (personaje o implante).';
        } else {
            $r = ope7_tramite_crear($uid, $pid, 56,
                trim((string) $mybb->get_input('motivo')),
                array('implante_id' => $implante_id, 'autocirugia' => (int) $mybb->get_input('autocirugia', 1) === 1 ? 1 : 0));
            $flash = $r['ok'] ? 'Trámite 56 creado: la IA calibra la ficha y firmas la instalación en la bandeja.' : $r['msg'];
        }
    } elseif ($gaccion === 'retirar') {
        // Trámite 57 (ligero/firma): el motor valida y aplica al firmar.
        $mod_id = (int) $mybb->get_input('modificacion_id', 1);
        $q = $db->simple_select('ope_modificaciones_personaje', 'personaje_id', "id = {$mod_id}", array('limit' => 1));
        $pid = (int) $db->fetch_field($q, 'personaje_id');
        if ($pid < 1) {
            $flash = 'Implante no encontrado.';
        } else {
            $r = ope7_tramite_crear($uid, $pid, 57,
                trim((string) $mybb->get_input('motivo')),
                array('modificacion_id' => $mod_id));
            $flash = $r['ok'] ? 'Trámite 57 creado: firma la retirada en la bandeja (libera cupo y balanza).' : $r['msg'];
        }
    } elseif ($gaccion === 'disenar') {
        // Trámite 59 (staff, 23.6): mejora a medida — la skill calibra la ficha.
        $implante_id = (int) $mybb->get_input('implante_id', 1);
        $q = $db->simple_select('ope_implantes', 'id', "id = {$implante_id}", array('limit' => 1));
        if ((int) $db->fetch_field($q, 'id') < 1) {
            $flash = 'Implante no encontrado.';
        } else {
            $r = ope7_tramite_crear($uid, 0, 59,
                trim((string) $mybb->get_input('concepto')),
                array('implante_id' => $implante_id, 'concepto' => trim((string) $mybb->get_input('concepto'))));
            $flash = $r['ok'] ? 'Trámite 59 creado: la skill calibra la ranura y firmas la ficha en la bandeja.' : $r['msg'];
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
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Cibernética</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-cibernetica-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Cibernética</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_cibernetica_panel_html(); ?>
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