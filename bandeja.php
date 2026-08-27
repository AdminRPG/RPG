<?php
/**
 * One Piece: 7 Seas · Bandeja de trámites (zona staff — motor 5.21)
 * -----------------------------------------------------------------
 * Vista transversal del motor de trámites (A.3 «Trámites»): pendientes,
 * prompt generado (copiar), resultado editable, firma con motivo e histórico.
 * Todos los paneles por sistema serán vistas filtradas de este mismo motor.
 * Solo staff. Scope CSS: body.ope-pg-bandeja.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'bandeja.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder a la bandeja de trámites.');
}

// ── Acciones POST ──
$flash = '';
if ($mybb->request_method === 'post') {
    $tid  = (int) $mybb->get_input('tid', 1);
    $accion = (string) $mybb->get_input('accion');
    $motivo = trim((string) $mybb->get_input('motivo'));
    $resultado = (string) $mybb->get_input('resultado');

    if ($accion !== '' && $tid > 0) {
        if ($resultado !== '') {
            $dec = json_decode($resultado, true);
            if ($dec === null) {
                $dec = array('texto' => $resultado);
            }
            ope7_tramite_guardar_resultado($tid, $dec);
        }
                $res = ope7_tramite_firmar($tid, $uid, $accion, $motivo);
        $flash = $res['ok']
            ? '<div class="flash ok">' . htmlspecialchars_uni($res['msg']) . '</div>'
            : '<div class="flash warn">' . htmlspecialchars_uni($res['msg']) . '</div>';
    }
}

$detalle_tid = (int) $mybb->get_input('tramite', 1);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Bandeja de trámites</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-bandeja">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span><b>Bandeja de trámites</b>
</div></div>
<div class="wrap">
  <?php echo ope7_bandeja_staff_html($uid, $detalle_tid, (int) $mybb->get_input('p', 1)); ?>
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
document.querySelectorAll('[data-copiar]').forEach(btn => {
  btn.addEventListener('click', () => {
    const ta = btn.closest('.tram-block').querySelector('textarea');
    if (!ta) return;
    ta.select();
    navigator.clipboard && navigator.clipboard.writeText(ta.value);
    const old = btn.textContent;
    btn.textContent = '¡Copiado!';
    setTimeout(() => btn.textContent = old, 1200);
  });
});
</script>
</body>
</html>
