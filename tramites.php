<?php
/**
 * One Piece: Eternal · Trámites (hub)
 * Cards con Acceder → tramite.php?tipo=…
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramites.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_tramites.php';

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

$catalogo = ope_tramites_catalogo();

$mios = array();
if ($db->table_exists('rol_tramites') && $pid > 0) {
    $q = $db->simple_select(
        'rol_tramites',
        '*',
        "pid = {$pid} AND tipo != 'crear_personaje'",
        array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 40)
    );
    while ($row = $db->fetch_array($q)) {
        $mios[] = $row;
    }
}

$pj_nombre = '';
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'nombre', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj_nombre = (string) $db->fetch_field($pq, 'nombre');
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
  <section class="reveal">
    <div class="shead">
      <h1>Trámites</h1>
      <span class="code">// ventanillas</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Solicitudes que requieren revisión humana. Autoservicio (stats, Eternal, Ken/Buso normales, Fruta Nv.0–2) vive en la <a href="<?php echo $bburl; ?>/ficha.php">ficha</a>.
<?php if ($pj_nombre !== ''): ?> Personaje activo: <b><?php echo htmlspecialchars_uni($pj_nombre); ?></b>.<?php else: ?> <span class="c-ember">Activa un personaje</span> para abrir trámites.<?php endif; ?></p>
  </section>

  <section class="reveal">
    <div class="shead"><h2>Ventanillas</h2><span class="rule"></span></div>
    <div class="cards tram-hub">
      <article class="card card--featured">
        <div class="card-top">
          <div>
            <div class="card-title">Navegación y Rutas</div>
            <div class="card-code">viaje_maritimo</div>
          </div>
        </div>
        <div class="card-body">Planifica tu travesía entre las 44 islas del mundo. Selecciona tu barco, equipamiento y tripulación para tirar el Oráculo de Viaje en Alta Mar.</div>
        <div class="card-foot">
          <span class="card-meta">Automático</span>
<?php if ($pid < 1): ?>
          <span class="chip">Sin PJ activo</span>
<?php else: ?>
          <a class="btn btn-hot btn-sm" href="<?php echo $bburl; ?>/viajes.php">Planificar Viaje</a>
<?php endif; ?>
        </div>
      </article>
<?php foreach ($catalogo as $tipo => $info): ?>
      <article class="card">
        <div class="card-top">
          <div>
            <div class="card-title"><?php echo htmlspecialchars_uni($info['titulo']); ?></div>
            <div class="card-code"><?php echo htmlspecialchars_uni($tipo); ?></div>
          </div>
        </div>
        <div class="card-body"><?php echo htmlspecialchars_uni($info['desc']); ?></div>
        <div class="card-foot">
          <span class="card-meta">Staff rank &ge; <?php echo (int) ($info['rank_min'] ?? 2); ?></span>
<?php if ($pid < 1): ?>
          <span class="chip">Sin PJ activo</span>
<?php else: ?>
          <a class="btn btn-hot btn-sm" href="<?php echo $bburl; ?>/tramite.php?tipo=<?php echo urlencode($tipo); ?>">Acceder</a>
<?php endif; ?>
        </div>
      </article>
<?php endforeach; ?>
    </div>
  </section>

  <section class="reveal">
    <div class="shead"><h2>Mis solicitudes</h2><span class="rule"></span></div>
    <div class="plate">
      <div class="plate-b">
<?php if (empty($mios)): ?>
        <p class="pj-empty">Sin trámites recientes.</p>
<?php else: ?>
        <ul class="tram-list">
<?php foreach ($mios as $t):
            $tit = $catalogo[$t['tipo']]['titulo'] ?? $t['tipo'];
?>
          <li>
            <b><?php echo htmlspecialchars_uni($tit); ?></b>
            <span class="tram-estado tram-estado--<?php echo htmlspecialchars_uni($t['estado']); ?>"><?php echo htmlspecialchars_uni($t['estado']); ?></span>
            <span class="c-dim mono fs-76"><?php echo my_date('relative', (int) $t['dateline']); ?></span>
<?php if (!empty($t['nota_staff'])): ?>
            <p class="mono fs-76"><?php echo htmlspecialchars_uni($t['nota_staff']); ?></p>
<?php endif; ?>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
    </div>
  </section>
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
