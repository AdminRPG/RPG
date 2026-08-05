<?php
/**
 * One Piece: Eternal · Panel Staff: Revision de Cierres de Viaje (STF-06)
 * Listado de viajes pendientes de revision de cierre.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-viajes.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);

// Solo Administradores (rank >= 3)
if (!$is_staff || $staff_rank < 3) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$flash = '';
$flash_ok = false;

if ($mybb->get_input('ok', MyBB::INPUT_INT) === 1) {
    $flash = 'Accion completada correctamente.';
    $flash_ok = true;
}

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesion del formulario caduco.';
    } else {
        $action = $mybb->get_input('action');
        if ($action === 'aprobar' || $action === 'rechazar') {
            $viaje_id = (int) $mybb->get_input('viaje_id', MyBB::INPUT_INT);
            if ($viaje_id < 1) {
                $flash = 'ID de viaje invalido.';
            } else {
                if ($action === 'aprobar') {
                    require_once MYBB_ROOT . 'inc/ope_rol/mundo/viaje_revision.php';
                    $res = ope_viaje_revision_aprobar($viaje_id, $uid);
                } else {
                    $motivo = trim((string) $mybb->get_input('motivo'));
                    if ($motivo === '') {
                        $flash = 'Debes especificar un motivo para rechazar el cierre.';
                    } else {
                        require_once MYBB_ROOT . 'inc/ope_rol/mundo/viaje_revision.php';
                        $res = ope_viaje_revision_rechazar($viaje_id, $uid, $motivo);
                    }
                }
                if (isset($res)) {
                    $flash = $res['msg'];
                    $flash_ok = $res['ok'];
                }
            }
        }
    }
}

$pendientes = function_exists('ope_viaje_revision_pendientes') ? ope_viaje_revision_pendientes() : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Revision de Viajes</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Revision de Viajes</b>
</div></div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Revision de Cierres de Viaje</h1>
      <span class="code">// panel STF-06 &middot; Admin only</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'ok' : 'error'; ?>">
    <?php echo htmlspecialchars_uni($flash); ?>
  </div>
<?php endif; ?>

  <section class="reveal">
<?php if (empty($pendientes)): ?>
    <div class="empty-state">
      <div class="big">No hay viajes pendientes de revision</div>
      <p>Todos los cierres estan al dia. Vuelve cuando un jugador solicite el cierre de una travesia.</p>
    </div>
<?php else: ?>
    <div class="zs-stafftbl">
<?php foreach ($pendientes as $v):
    $intentos = (int) ($v['revision_intentos'] ?? 1);
    $intentos_lbl = $intentos >= 2 ? '⚠️ 2º intento (se cancelara si se rechaza)' : '1er intento';
?>
      <div class="zs-staffrow">
        <div class="zs-staffwho col-grow">
          <span class="zs-staffname"><?php echo htmlspecialchars_uni($v['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($v['destino_nombre']); ?></span>
          <span class="zs-staffowner">Barco: <?php echo htmlspecialchars_uni($v['barco_nombre']); ?> &middot; Viaje #<?php echo (int) $v['viaje_id']; ?></span>
          <span class="col-grow zs-lbl-subtle">
            Peligro: <?php echo htmlspecialchars_uni(ucfirst($v['nivel_peligro'] ?? 'bajo')); ?>
            &middot; <?php echo $intentos_lbl; ?>
            &middot; <?php echo my_date('relative', (int) $v['dateline']); ?>
          </span>
        </div>
        <a href="<?php echo $bburl; ?>/revision-viaje.php?viaje_id=<?php echo (int) $v['viaje_id']; ?>" class="btn btn-hot btn-sm">Revisar</a>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es) {
        es.forEach(function(e) { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } });
    }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });
} else {
    document.querySelectorAll('.reveal').forEach(function(el) { el.classList.add('vis'); });
}
</script>
</body>
</html>
