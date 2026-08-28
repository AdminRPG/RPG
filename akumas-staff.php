<?php
/**
 * One Piece: 7 Seas · Akumas y Haki (zona staff — Anexo A.3, F5)
 * --------------------------------------------------------------
 * Panel «Akumas» (19.7): catálogo de frutas con la plantilla de 8 bloques,
 * control de cupos mundiales, pool de la tirada e histórico de renacimientos,
 * más la vista «Haki» (20.5): niveles/usos/PP, intentos del Conquistador y
 * publicación de los sucesos en borrador. Solo staff.
 * Scope CSS: body.ope-pg-akumas-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'akumas-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel de akumas y Haki.');
}

// Acciones del panel (gaccion): publicar suceso en borrador · adaptar fruta (49).
$flash = '';
if (($mybb->get_input('gaccion') === 'publicar_suceso') && verify_post_check($mybb->get_input('my_post_key'))) {
    if (function_exists('ope7_akumas_publicar_suceso') && ope7_akumas_publicar_suceso((int) $mybb->get_input('suceso_id', 1))) {
        $flash = 'Suceso publicado: ya forma parte del Mundo Vivo.';
    } else {
        $flash = 'No se pudo publicar el suceso.';
    }
} elseif (($mybb->get_input('gaccion') === 'adaptar_fruta') && verify_post_check($mybb->get_input('my_post_key'))) {
    // Trámite 49 (staff, skill-adaptacion-akumas): la IA propone la ficha de 8
    // bloques desde nombre+concepto canon; el staff la revisa y firma en la bandeja.
    $concepto = trim((string) $mybb->get_input('concepto'));
    if ($concepto === '') {
        $flash = 'Indica la fruta (nombre+concepto canon) a adaptar.';
    } else {
        $r = ope7_tramite_crear($uid, 0, 49, 'Adaptación de fruta bajo demanda', array('concepto' => $concepto));
        $flash = $r['ok'] ? 'Trámite 49 creado: la skill construye la ficha de 8 bloques y la firmas en la bandeja.' : $r['msg'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Akumas y Haki</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-akumas-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Akumas y Haki</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_akumas_panel_html(); ?>
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
