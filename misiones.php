<?php
/**
 * One Piece: Eternal · Tablon de Misiones
 * --------------------------------------------------------
 * Muestra las misiones escritas por el staff. El jugador puede
 * solicitar tomar una mision con su personaje activo (exclusiva).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'misiones.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

if (!$loggedin) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

$pj = null;
$pj_nombre = '';
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', 'pid, nombre, estado', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
        $pj_nombre = (string) ($pj['nombre'] ?? '');
    }
}

$flash = '';
$flash_ok = false;

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesion del formulario caduco.';
    } elseif ($pid < 1) {
        $flash = 'Necesitas un personaje activo para tomar misiones.';
    } else {
        $action = $mybb->get_input('action');
        if ($action === 'tomar') {
            $mision_id = (int) $mybb->get_input('mision_id', MyBB::INPUT_INT);
            $companeros = (array) $mybb->get_input('companeros', MyBB::INPUT_ARRAY);
            $res = ope_mision_solicitar_toma($mision_id, $pid, $uid, $companeros);
            $flash = $res['msg'];
            $flash_ok = !empty($res['ok']);
        }
    }
}

$misiones = ope_misiones_catalogo();

// Métricas para el tablón
$n_disponibles = 0;
$n_curso = 0;
foreach ($misiones as $m) {
    if (!empty($m['toma_estado'])) {
        $n_curso++;
    } else {
        $n_disponibles++;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tablon de Misiones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tramites">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Tablon de Misiones</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tablon de Misiones</h1>
      <span class="code">// <?php echo $n_disponibles; ?> disponibles &middot; <?php echo $n_curso; ?> en curso</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Misiones escritas por el staff. Tomar una es <b>exclusiva</b>: un solo grupo la juega a la vez. Cada toma pasa por aprobacion del staff antes de que tu personaje se comprometa.
<?php if ($pj_nombre !== ''): ?> Personaje activo: <b><?php echo htmlspecialchars_uni($pj_nombre); ?></b>.
<?php else: ?> <span class="c-ember">Activa un personaje</span> para tomar misiones.<?php endif; ?></p>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones en el mar</span><span class="c">// rango, peligrosidad, modalidad</span></div>
      <div class="plate-b">
<?php if (empty($misiones)): ?>
        <p class="tram-empty">Sin misiones publicadas todavia. El staff abrira encargos pronto.</p>
<?php else: ?>
        <div class="cards mision-grid">
<?php foreach ($misiones as $m):
        $mid = (int) $m['mision_id'];
        $es_curso = !empty($m['toma_estado']);
        $zona_nombre = function_exists('ope_isla_nombre') ? ope_isla_nombre((string) $m['zona_slug']) : (string) $m['zona_slug'];
?>
          <article class="card <?php echo $es_curso ? 'card--featured' : ''; ?><?php echo $pid < 1 ? ' disabled' : ''; ?>">
            <div class="card-top">
              <div class="card-head">
                <div class="card-title"><?php echo htmlspecialchars_uni($m['titulo']); ?></div>
                <div class="card-code">Rango <?php echo ope_mision_rango_label((string) $m['rango']); ?> &middot; Peligrosidad <?php echo (int) $m['peligrosidad']; ?>/5 &middot; <?php echo ope_mision_modalidad_label((string) $m['modalidad']); ?></div>
              </div>
<?php if ($es_curso): ?>
              <span class="card-count" title="En curso por otro PJ">EN CURSO</span>
<?php endif; ?>
            </div>
            <div class="card-body">
              <p><?php echo htmlspecialchars_uni($m['resumen'] ?? ''); ?></p>
              <div class="mision-meta">
                <span class="chip"><?php echo htmlspecialchars_uni($zona_nombre); ?></span>
<?php if (!empty($m['facciones'])): ?>
                <span class="chip"><?php echo htmlspecialchars_uni($m['facciones']); ?></span>
<?php endif; ?>
              </div>
<?php if ($es_curso): ?>
              <p class="c-dim mono fs-76">Tomada por <b><?php echo htmlspecialchars_uni($m['titular_nombre']); ?></b><?php if (!empty($m['companeros_arr'])): ?> + <?php echo count($m['companeros_arr']); ?> companeros<?php endif; ?></p>
<?php endif; ?>
            </div>
            <div class="card-foot">
              <span class="card-meta"><?php echo htmlspecialchars_uni($m['recompensa'] ?? ''); ?></span>
<?php if (!$es_curso && $pid > 0): ?>
              <form method="post" action="<?php echo $bburl; ?>/misiones.php">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="tomar">
                <input type="hidden" name="mision_id" value="<?php echo $mid; ?>">
                <button type="submit" class="btn btn-hot btn-sm" onclick="return confirm('Solicitar tomar esta mision con tu personaje activo?');">Solicitar toma</button>
              </form>
<?php elseif ($es_curso): ?>
              <span class="chip">Ocupada</span>
<?php else: ?>
              <span class="chip">Sin PJ activo</span>
<?php endif; ?>
            </div>
          </article>
<?php endforeach; ?>
        </div>
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