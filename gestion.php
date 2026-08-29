<?php
/**
 * One Piece: Eternal · Gestión (hub inteligente)
 * Detecta personaje activo, tripulación, barco e inventario.
 * Muestra shortcuts contextuales (Mi Personaje, Mi Tienda, Mi Barco…).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestion.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

// ── Cargar datos del personaje activo (fuente canónica mybb_ope_personajes) ──
$pj = null;
$isla_actual = null;
if ($pid > 0 && $db->table_exists('ope_personajes')) {
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

// Nombre a mostrar
$display_name = function_exists('ope_get_display_name') ? ope_get_display_name() : htmlspecialchars_uni($mybb->user['username'] ?? '');

// ── Detectar tripulación (canónica: ope_tripulantes → ope_tripulaciones) ──
$tiene_tripulacion = false;
$mi_tripulacion = null;
if ($pid > 0 && $db->table_exists('ope_tripulantes') && $db->table_exists('ope_tripulaciones')) {
    $tq = $db->query("SELECT t.id, t.nombre, tm.rol, tm.estado
        FROM " . TABLE_PREFIX . "ope_tripulantes tm
        JOIN " . TABLE_PREFIX . "ope_tripulaciones t ON t.id = tm.tripulacion_id
        WHERE tm.personaje_id = {$pid} AND tm.estado = 'activo' AND t.estado = 'activa'
        LIMIT 1");
    if ($db->num_rows($tq)) {
        $mi_tripulacion = $db->fetch_array($tq);
        $tiene_tripulacion = true;
    }
}

// ── Detectar barco activo (canónico: mybb_ope_barcos) ──
$barco = null;
if ($pid > 0 && function_exists('ope7_barco_flota') && ope7_tabla_existe('barcos')) {
    $barcos = ope7_barco_flota($pid);
    if (!empty($barcos)) {
        $barco = $barcos[0];
    }
}

// ── Detectar inventario (canónico: ope_inventario_personaje) ──
$tiene_inventario = false;
$inv_count = 0;
if ($pid > 0 && $db->table_exists('ope_inventario_personaje')) {
    $iq = $db->simple_select('ope_inventario_personaje', 'COUNT(*) as cnt', "personaje_id = {$pid}");
    if ($db->num_rows($iq)) {
        $inv_count = (int) $db->fetch_field($iq, 'cnt');
        $tiene_inventario = $inv_count > 0;
    }
}

// ── Trámites pendientes (canónico: ope_tramites del motor 5.21) ──
$tramites_pendientes = 0;
if ($pid > 0 && $db->table_exists('ope_tramites')) {
    $tq2 = $db->simple_select('ope_tramites', 'COUNT(*) as cnt', "personaje_id = {$pid} AND estado = 'pendiente'");
    if ($db->num_rows($tq2)) {
        $tramites_pendientes = (int) $db->fetch_field($tq2, 'cnt');
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Gestión</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-gestion">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Gestión</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Gestión</h1>
      <span class="code">// centro de mando</span>
      <span class="rule"></span>
    </div>
    <p class="gestion-intro">Gestiona tu personaje, tripulación, inventario y embarcación desde un solo lugar.</p>
  </section>

<?php if ($pj): ?>
  <!-- Banner resumen del personaje activo -->
  <div class="gestion-pj-banner reveal">
    <div class="gestion-pj-avatar">
      <?php
      $avatar_src = '';
      $datos_pj = json_decode((string) ($pj['datos'] ?? ''), true);
      if (!is_array($datos_pj)) $datos_pj = array();
      $avatar_src = trim((string) ($datos_pj['retrato'] ?? ''));
      if ($avatar_src === '') $avatar_src = trim((string) ($pj['avatar'] ?? ''));
      if ($avatar_src === '') $avatar_src = trim((string) ($pj['icono'] ?? ''));
      $initials = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));
      ?>
      <?php if ($avatar_src !== ''): ?>
        <img src="<?php echo htmlspecialchars_uni($avatar_src); ?>" alt="<?php echo htmlspecialchars_uni($pj['nombre']); ?>" onerror="this.parentElement.innerHTML='<span><?php echo htmlspecialchars_uni($initials); ?></span>'">
      <?php else: ?>
        <span><?php echo htmlspecialchars_uni($initials); ?></span>
      <?php endif; ?>
    </div>
    <div class="gestion-pj-info">
      <span class="gestion-pj-label">Personaje activo</span>
      <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $pid; ?>" class="gestion-pj-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></a>
      <div class="gestion-pj-chips">
        <span class="gestion-chip">Lv. <?php echo (int) ($pj['nivel'] ?? 1); ?></span>
        <?php if ($tiene_tripulacion): ?>
          <span class="gestion-chip gestion-chip--trip">=<?php echo htmlspecialchars_uni($mi_tripulacion['nombre']); ?></span>
        <?php endif; ?>
        <?php if ($barco): ?>
          <span class="gestion-chip gestion-chip--barco">=<?php echo htmlspecialchars_uni($barco['nombre']); ?></span>
        <?php endif; ?>
        <?php if ($tramites_pendientes > 0): ?>
          <span class="gestion-chip gestion-chip--pend"><?php echo $tramites_pendientes; ?> trámite<?php echo $tramites_pendientes > 1 ? 's' : ''; ?> pendiente<?php echo $tramites_pendientes > 1 ? 's' : ''; ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

  <div class="gestion-grid">
    <!-- Personaje -->
    <a href="<?php echo $bburl; ?>/personajes.php" class="gestion-card reveal">
      <div class="gestion-card-icon">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3><?php echo $pj ? 'Mi Personaje' : 'Crear Personaje'; ?></h3>
        <p><?php echo $pj
            ? htmlspecialchars_uni($pj['nombre']) . ' · Lv.' . (int) ($pj['nivel'] ?? 1) . ' · ' . htmlspecialchars_uni($pj['rango'] ?? '')
            : 'Crea tu primera ficha para empezar a postear en el Grand Line.'; ?></p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Tripulación -->
    <a href="<?php echo $bburl; ?>/tripulacion.php" class="gestion-card reveal">
      <div class="gestion-card-icon">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 3.13a4 4 0 0 1 0 7.75" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3><?php echo $tiene_tripulacion ? 'Mi Tripulación' : 'Tripulación'; ?></h3>
        <p><?php echo $tiene_tripulacion
            ? htmlspecialchars_uni($mi_tripulacion['nombre']) . ' · ' . htmlspecialchars_uni($mi_tripulacion['lema'] ?? '')
            : 'Busca una tripulación o crea la tuya propia.'; ?></p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Tienda personal -->
    <a href="<?php echo $bburl; ?>/tienda-personal.php" class="gestion-card reveal">
      <div class="gestion-card-icon">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" fill="none" stroke="currentColor" stroke-width="1.8"/><line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="1.8"/><path d="M16 10a4 4 0 0 1-8 0" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3>Mi Tienda</h3>
        <p><?php echo $tiene_inventario
            ? $inv_count . ' objeto' . ($inv_count > 1 ? 's' : '') . ' en inventario'
            : 'Gestiona los objetos y equipamiento de tu personaje.'; ?></p>
      </div>
      <span class="gestion-card-arrow">›</span>
    </a>

    <!-- Barco -->
    <a href="<?php echo $bburl; ?>/barco.php" class="gestion-card reveal">
      <div class="gestion-card-icon">
        <svg viewBox="0 0 24 24" width="32" height="32"><path d="M2 20l2-4h16l2 4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4 16l2-8h12l2 8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 4v4" stroke="currentColor" stroke-width="1.8"/><path d="M9 4h6" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="gestion-card-body">
        <h3><?php echo $barco ? 'Mi Barco' : 'Barco'; ?></h3>
        <p><?php echo $barco
            ? htmlspecialchars_uni($barco['nombre']) . ' · ' . htmlspecialchars_uni($barco['tipo']['nombre'] ?? '')
            : 'Solicita o compra una embarcación para navegar el Grand Line.'; ?></p>
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
