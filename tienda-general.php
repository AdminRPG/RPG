<?php
/**
 * One Piece: Eternal · Tienda General
 * Objetos básicos, provisiones y equipamiento estándar.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tienda-general.php');
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
<title><?php echo $bbname; ?> · Tienda General</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tienda-general">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tiendas.php">Tiendas</a><span class="sep">›</span><b>Tienda General</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tienda General</h1>
      <span class="code">// provisiones y equipamiento</span>
      <span class="rule"></span>
    </div>
    <p class="tienda-intro">Objetos básicos al alcance de cualquier pirata o marinero. Provisiones, armas simples y equipamiento estándar.</p>
  </section>

  <div class="tienda-layout reveal">
    <div class="tienda-filters">
      <button class="tienda-filter-btn active" data-filter="all">Todo</button>
      <button class="tienda-filter-btn" data-filter="provision">Provisiones</button>
      <button class="tienda-filter-btn" data-filter="arma">Armas</button>
      <button class="tienda-filter-btn" data-filter="equipo">Equipo</button>
      <button class="tienda-filter-btn" data-filter="utensilio">Utensilios</button>
    </div>

    <div class="tienda-grid">
      <div class="tienda-item">
        <div class="tienda-item-img">
          <svg viewBox="0 0 40 40" width="40" height="40"><circle cx="20" cy="20" r="18" fill="none" stroke="var(--ope-gold)" stroke-width="1.5"/><text x="20" y="26" text-anchor="middle" font-size="18">🍖</text></svg>
        </div>
        <div class="tienda-item-info">
          <h4>Carne de caza</h4>
          <span class="tienda-item-price">50 berries</span>
        </div>
      </div>
      <div class="tienda-item">
        <div class="tienda-item-img">
          <svg viewBox="0 0 40 40" width="40" height="40"><circle cx="20" cy="20" r="18" fill="none" stroke="var(--ope-gold)" stroke-width="1.5"/><text x="20" y="26" text-anchor="middle" font-size="18">🍺</text></svg>
        </div>
        <div class="tienda-item-info">
          <h4>Sake</h4>
          <span class="tienda-item-price">30 berries</span>
        </div>
      </div>
      <div class="tienda-item">
        <div class="tienda-item-img">
          <svg viewBox="0 0 40 40" width="40" height="40"><circle cx="20" cy="20" r="18" fill="none" stroke="var(--ope-gold)" stroke-width="1.5"/><text x="20" y="26" text-anchor="middle" font-size="18">🗡️</text></svg>
        </div>
        <div class="tienda-item-info">
          <h4>Cuchillo de cocina</h4>
          <span class="tienda-item-price">200 berries</span>
        </div>
      </div>
      <div class="tienda-item">
        <div class="tienda-item-img">
          <svg viewBox="0 0 40 40" width="40" height="40"><circle cx="20" cy="20" r="18" fill="none" stroke="var(--ope-gold)" stroke-width="1.5"/><text x="20" y="26" text-anchor="middle" font-size="18">⚓</text></svg>
        </div>
        <div class="tienda-item-info">
          <h4>Cuerda de楠 (kaya)</h4>
          <span class="tienda-item-price">100 berries</span>
        </div>
      </div>
    </div>
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
