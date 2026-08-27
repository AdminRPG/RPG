<?php
/**
 * One Piece: Eternal · Astillero
 * Compra, repara y mejora embarcaciones.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'astillero.php');
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
<title><?php echo $bbname; ?> · Astillero</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-astillero">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tiendas.php">Tiendas</a><span class="sep">›</span><b>Astillero</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Astillero</h1>
      <span class="code">// construcción naval</span>
      <span class="rule"></span>
    </div>
    <p class="tienda-intro">Compra, repara y mejora embarcaciones. Desde barcos pequeños hasta navíos de guerra.</p>
  </section>

  <div class="astillero-layout reveal">
    <div class="tienda-filters">
      <button class="tienda-filter-btn active" data-filter="all">Todo</button>
      <button class="tienda-filter-btn" data-filter="compra">Comprar</button>
      <button class="tienda-filter-btn" data-filter="reparar">Reparar</button>
      <button class="tienda-filter-btn" data-filter="mejorar">Mejorar</button>
    </div>

    <div class="astillero-grid">
      <div class="astillero-ship">
        <div class="astillero-ship-preview">
          <svg viewBox="0 0 120 80" width="120" height="80">
            <path d="M20 60 Q60 70 100 60 L90 45 Q60 50 30 45 Z" fill="var(--ope-card-2)" stroke="var(--ope-gold)" stroke-width="1.5"/>
            <line x1="60" y1="45" x2="60" y2="15" stroke="var(--ope-ink-dim)" stroke-width="1.5"/>
            <polygon points="62,15 62,35 80,28" fill="var(--ope-sky)" stroke="var(--ope-gold)" stroke-width="1"/>
          </svg>
        </div>
        <div class="astillero-ship-info">
          <h4>Barcaza Básica</h4>
          <p class="astillero-ship-type">Embarcación pequeña</p>
          <span class="tienda-item-price">5,000 berries</span>
        </div>
      </div>

      <div class="astillero-ship">
        <div class="astillero-ship-preview">
          <svg viewBox="0 0 120 80" width="120" height="80">
            <path d="M15 60 Q60 72 105 60 L95 40 Q60 52 25 40 Z" fill="var(--ope-card-2)" stroke="var(--ope-gold)" stroke-width="1.5"/>
            <line x1="45" y1="40" x2="45" y2="10" stroke="var(--ope-ink-dim)" stroke-width="1.5"/>
            <polygon points="47,10 47,30 68,22" fill="var(--ope-sky)" stroke="var(--ope-gold)" stroke-width="1"/>
            <line x1="75" y1="40" x2="75" y2="15" stroke="var(--ope-ink-dim)" stroke-width="1.5"/>
            <polygon points="77,15 77,32 95,25" fill="var(--ope-sky)" stroke="var(--ope-gold)" stroke-width="1"/>
          </svg>
        </div>
        <div class="astillero-ship-info">
          <h4>Goleta</h4>
          <p class="astillero-ship-type">Embarcación media</p>
          <span class="tienda-item-price">25,000 berries</span>
        </div>
      </div>

      <div class="astillero-ship astillero-ship--locked">
        <div class="astillero-ship-preview">
          <svg viewBox="0 0 120 80" width="120" height="80">
            <path d="M10 60 Q60 75 110 60 L100 35 Q60 50 20 35 Z" fill="var(--ope-card-2)" stroke="var(--ope-ink-dim)" stroke-width="1.5" opacity=".5"/>
            <line x1="40" y1="35" x2="40" y2="5" stroke="var(--ope-ink-dim)" stroke-width="1.5" opacity=".5"/>
            <line x1="60" y1="35" x2="60" y2="5" stroke="var(--ope-ink-dim)" stroke-width="1.5" opacity=".5"/>
            <line x1="80" y1="35" x2="80" y2="8" stroke="var(--ope-ink-dim)" stroke-width="1.5" opacity=".5"/>
          </svg>
        </div>
        <div class="astillero-ship-info">
          <h4>Galeón</h4>
          <p class="astillero-ship-type">Embarcación grande — Próximamente</p>
          <span class="tienda-item-price tienda-item-price--locked">???</span>
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
