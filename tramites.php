<?php
/**
 * One Piece: 7 Seas · Trámites (hub del jugador — motor 5.21)
 * -----------------------------------------------------------------
 * Ventanillas del jugador: seguimiento de sus solicitudes (estado + histórico
 * auditable) y el catálogo de ventanillas. Scope CSS: body.ope-pg-tramites.
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

require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

// ── Ciclo con usuario (F1.3): aceptar / pedir cambios en trámites 3 y 13 ──
$flash = '';
if ($mybb->request_method === 'post') {
    $tid   = (int) $mybb->get_input('tid', 1);
    $acc   = (string) $mybb->get_input('accion');
    $motivo = trim((string) $mybb->get_input('motivo'));
    if ($tid > 0) {
        if ($acc === 'aceptar') {
            $r = ope7_tramite_usuario_aceptar($tid, $uid);
        } elseif ($acc === 'cambios') {
            $r = ope7_tramite_usuario_pedir_cambios($tid, $uid, $motivo);
        } else {
            $r = array('ok' => false, 'msg' => 'Acción no válida.');
        }
        $flash = $r['ok']
            ? '<div class="flash ok">' . htmlspecialchars_uni($r['msg']) . '</div>'
            : '<div class="flash warn">' . htmlspecialchars_uni($r['msg']) . '</div>';
    }
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
  <?php echo $flash; ?>
  <?php echo ope7_tramites_jugador_html($uid); ?>
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
