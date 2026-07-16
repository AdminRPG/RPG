<?php
/**
 * I-Forge · Gestionar trámites de tripulación (Zona Staff · Administrador)
 * Aprueba o rechaza solicitudes fundar_tripulacion / unirse_tripulacion.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-tramites-tripulacion.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin ? gbe_rol_active_staff($uid) : array('rank' => 0);
$is_admin = ((int) $staff['rank'] >= 3);

$flash = '';
$flash_kind = 'ok';
$pk = htmlspecialchars_uni($mybb->post_code);

if ($is_admin && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $tid = (int) $mybb->get_input('tid', MyBB::INPUT_INT);
        if ($tid > 0 && $action === 'aprobar') {
            $res = gbe_rol_cat_tripulacion_aprobar_tramite($tid);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
        } elseif ($tid > 0 && $action === 'rechazar') {
            $res = gbe_rol_cat_tripulacion_rechazar_tramite($tid);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
        }
    }
}

$pendientes = array();
if ($is_admin && $db->table_exists('rol_tramites')) {
    $q = $db->simple_select(
        'rol_tramites',
        '*',
        "estado = 'pendiente' AND tipo IN ('fundar_tripulacion','unirse_tripulacion')",
        array('order_by' => 'dateline', 'order_dir' => 'ASC')
    );
    while ($r = $db->fetch_array($q)) {
        $r['datos_arr'] = json_decode((string) ($r['datos'] ?? ''), true);
        if (!is_array($r['datos_arr'])) {
            $r['datos_arr'] = array();
        }
        $r['pj_nombre'] = gbe_rol_cat_nombre_pid((int) $r['pid']);
        $pendientes[] = $r;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámites de tripulación</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-mundo-vivo">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">›</span>
    <b>Trámites tripulación</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Trámites de tripulación</h1><span class="code">// fundar · unirse</span><span class="rule"></span></div>
  </section>

<?php if (!$is_admin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><div class="noperm"><div class="big">Zona reservada a Administradores</div><a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver</a></div></div></div></section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Pendientes</span><span class="c">// <?php echo count($pendientes); ?> solicitud(es)</span></div>
      <div class="plate-b">
<?php if (empty($pendientes)): ?>
        <p class="mv-empty">No hay trámites de tripulación pendientes.</p>
<?php else: foreach ($pendientes as $t):
        $d = $t['datos_arr'];
        $tipo_lbl = $t['tipo'] === 'fundar_tripulacion' ? 'Fundar' : 'Unirse';
?>
        <div class="mv-row">
          <div class="mv-ev-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($tipo_lbl); ?> · <?php echo htmlspecialchars_uni($t['pj_nombre'] ?: 'pid #' . (int) $t['pid']); ?></span>
            <span class="mv-ev-meta">
<?php if ($t['tipo'] === 'fundar_tripulacion'): ?>
              «<?php echo htmlspecialchars_uni((string) ($d['nombre'] ?? '')); ?>» · <?php echo htmlspecialchars_uni((string) ($d['faccion'] ?? '')); ?>
<?php else:
        $trip_nom = '';
        $tid_trip = (int) ($d['tripulacion_id'] ?? 0);
        if ($tid_trip > 0 && $db->table_exists('rol_tripulaciones')) {
            $tq = $db->simple_select('rol_tripulaciones', 'nombre', "id = {$tid_trip}", array('limit' => 1));
            if ($db->num_rows($tq)) {
                $trip_nom = (string) $db->fetch_field($tq, 'nombre');
            }
        }
?>
              Tripulación: <?php echo $trip_nom !== '' ? htmlspecialchars_uni($trip_nom) : '#' . $tid_trip; ?>
<?php endif; ?>
              · <?php echo date('d/m/Y H:i', (int) $t['dateline']); ?>
            </span>
<?php if ($t['tipo'] === 'fundar_tripulacion' && !empty($d['descripcion'])): ?>
            <p class="mv-ev-res"><?php echo htmlspecialchars_uni((string) $d['descripcion']); ?></p>
<?php elseif ($t['tipo'] === 'unirse_tripulacion' && !empty($d['mensaje'])): ?>
            <p class="mv-ev-res"><?php echo htmlspecialchars_uni((string) $d['mensaje']); ?></p>
<?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <form method="post" action="<?php echo $bburl; ?>/gestionar-tramites-tripulacion.php">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="action" value="aprobar">
              <input type="hidden" name="tid" value="<?php echo (int) $t['tid']; ?>">
              <button class="btn btn-sm btn-hot">Aprobar</button>
            </form>
            <form method="post" action="<?php echo $bburl; ?>/gestionar-tramites-tripulacion.php" onsubmit="return confirm('¿Rechazar esta solicitud?');">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="action" value="rechazar">
              <input type="hidden" name="tid" value="<?php echo (int) $t['tid']; ?>">
              <button class="btn btn-sm btn-ghost">Rechazar</button>
            </form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

<?php endif; ?>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
