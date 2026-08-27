<?php
/**
 * One Piece: Eternal · Zona Staff (hub)
 * Skeleton — cards se agregan una a una.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);
$rol_lbl    = ope_rol_staff_label($staff['rol']);
$char_name  = htmlspecialchars_uni((string) $staff['nombre']);
$mi_rango   = $rol_lbl !== '' ? $rol_lbl : 'Sin rango';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Zona Staff</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Zona Staff</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Zona Staff</h1>
      <span class="code">// paneles</span>
      <span class="rule"></span>
    </div>
  </section>
<?php if (!$is_staff): ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Acceso restringido</span><span class="c">// solo staff</span></div>
      <div class="plate-b">
        <div class="noperm">
          <div class="lock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div class="big">No tienes acceso a la Zona Staff</div>
          <p>Reservado al equipo del foro. La cuenta Admin MyBB tiene acceso aunque no tenga personaje activo.</p>
          <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="reveal">
    <p class="zs-intro">Herramientas del equipo. Cada panel exige un <b>rank mínimo</b>; solo ves las cards a las que puedes acceder.</p>
    <div class="zs-bar">
      <span class="zs-level">Activo: <b><?php echo $char_name !== '' ? $char_name : 'Admin (sin PJ)'; ?></b> · rol: <b><?php echo htmlspecialchars_uni($mi_rango); ?></b> · rank <?php echo (int) $staff_rank; ?></span>
    </div>
  </section>
  <!-- Agregar cards aquí -->
<?php endif; ?>
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
