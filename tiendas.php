<?php
/**
 * One Piece: Eternal · Tiendas (hub)
 * Accesos rápidos a: Tienda General, Astillero, Mercado Negro, Tiendas Personales.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tiendas.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tiendas</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tiendas">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Tiendas</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tiendas</h1>
      <span class="code">// mercado libre</span>
      <span class="rule"></span>
    </div>
    <p class="tiendas-intro">Compra y vende en los mercados del Grand Line. Tienda general, astillero, mercado negro o las tiendas privadas de otros piratas.</p>
  </section>

  <div class="gestion-grid">
    <!-- Tienda General -->
    <a href="<?php echo $bburl; ?>/tienda-general.php" class="gestion-card reveal">
      <div class="gestion-card-icon tiendas-icon--general">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" fill="none" stroke="currentColor" stroke-width="1.8"/><polyline points="9 22 9 12 15 12 15 22" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3>Tienda General</h3>
        <p>Objetos básicos, provisiones y equipamiento estándar al alcance de todos.</p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Astillero -->
    <a href="<?php echo $bburl; ?>/astillero.php" class="gestion-card reveal">
      <div class="gestion-card-icon tiendas-icon--astillero">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3>Astillero</h3>
        <p>Compra, repara y mejora embarcaciones. El.astillero más completo del Grand Line.</p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Mercado Negro -->
    <a href="<?php echo $bburl; ?>/mercado-negro.php" class="gestion-card reveal">
      <div class="gestion-card-icon tiendas-icon--mercado">
        <svg viewBox="0 0 24 24" width="32" height="32"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 14s1.5 2 4 2 4-2 4-2" fill="none" stroke="currentColor" stroke-width="1.8"/><line x1="9" y1="9" x2="9.01" y2="9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><line x1="15" y1="9" x2="15.01" y2="9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3>Mercado Negro</h3>
        <p>Objetos raros, Akuma no Mi prohibidas y mercancía del bajo mundo. Precios elevados.</p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Tiendas Personales -->
    <a href="<?php echo $bburl; ?>/tiendas-personales.php" class="gestion-card reveal">
      <div class="gestion-card-icon tiendas-icon--personales">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3>Tiendas Personales</h3>
        <p>Las tiendas privadas de otros personajes. Compra directamente a tus aliados o rivales.</p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>
  </div>
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
