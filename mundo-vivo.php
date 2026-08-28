<?php
/**
 * One Piece: 7 Seas · Mundo Vivo (zona staff — Anexo A.3, F4.1)
 * ---------------------------------------------------------------
 * Panel «Mundo Vivo» (Staff 15.1–15.8): ronda mensual actual con su estado,
 * la cola de análisis (temas presentes abiertos), la matriz de islas con su
 * ficha viva (peligrosidad, control, defensa, desarrollo, clima) y el aviso
 * del flujo: la skill-mundo-vivo propone, el staff firma, el motor aplica.
 * Solo staff. Scope CSS: body.ope-pg-mundo-vivo.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mundo-vivo.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel del Mundo Vivo.');
}

// Publicar una edición del periódico en borrador (visibilidad manual 15.2).
$flash = '';
if (($mybb->get_input('gaccion') === 'publicar_periodico') && verify_post_check($mybb->get_input('my_post_key'))) {
    $pid = (int) $mybb->get_input('periodico_id', 1);
    if ($pid > 0 && ope7_tabla_existe('historico_periodicos')) {
        $db->update_query('ope_historico_periodicos', array('estado' => 'publicado', 'publicado_por' => $uid), "id = {$pid}");
        $flash = 'Edición publicada: el News Coo ya forma parte del mundo.';
    } else {
        $flash = 'No se pudo publicar la edición.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Mundo Vivo</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-mundo-vivo">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Mundo Vivo</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_mundo_vivo_panel_html(); ?>
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
