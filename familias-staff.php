<?php
/**
 * One Piece: 7 Seas · Familias Legendarias (zona staff — Anexo A.3, F5.4)
 * -----------------------------------------------------------------------
 * Panel «Familias Legendarias» (5.22 §B/cap. 23.7): catálogo con cupos
 * (3–5), portadores activos/revocados, expediente de fidelidad y bandeja de
 * concesión/revocación (trámites 60–61, staff-only). La IA propone, el
 * staff firma con motivo; suceso de ronda en borrador (5.14).
 * Scope CSS: body.ope-pg-familias-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'familias-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de familias legendarias.');
}

$flash = '';
if ($mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'))) {
    $gaccion = (string) $mybb->get_input('gaccion');
    if ($gaccion === 'conceder') {
        // Trámite 60 (staff): expediente × cupo; el motor valida y aplica al firmar.
        $familia_id = (int) $mybb->get_input('familia_id', 1);
        $personaje_id = (int) $mybb->get_input('personaje_id', 1);
        if ($familia_id < 1 || $personaje_id < 1) {
            $flash = 'Faltan datos (familia o personaje).';
        } else {
            $r = ope7_tramite_crear($uid, $personaje_id, 60,
                trim((string) $mybb->get_input('motivo')),
                array('familia_id' => $familia_id, 'personaje_id' => $personaje_id));
            $flash = $r['ok'] ? 'Trámite 60 creado: la IA cruza el expediente con el cupo y firmas la concesión.' : $r['msg'];
        }
    } elseif ($gaccion === 'revocar') {
        // Trámite 61 (staff): retira dote/defecto, libera cupo.
        $linaje_id = (int) $mybb->get_input('linaje_id', 1);
        $q = $db->simple_select('ope_linaje_personaje', 'personaje_id', "id = {$linaje_id}", array('limit' => 1));
        $pid = (int) $db->fetch_field($q, 'personaje_id');
        if ($pid < 1) {
            $flash = 'Linaje no encontrado.';
        } else {
            $r = ope7_tramite_crear($uid, $pid, 61,
                trim((string) $mybb->get_input('motivo')),
                array('linaje_id' => $linaje_id));
            $flash = $r['ok'] ? 'Trámite 61 creado: firma la revocación en la bandeja (liberas el cupo).' : $r['msg'];
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
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Familias Legendarias</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-familias-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Familias Legendarias</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_familias_panel_html(); ?>
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