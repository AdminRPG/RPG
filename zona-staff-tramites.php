<?php
/**
 * Zona Staff · Cola de trámites
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-tramites.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_tramites.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);

if (!$is_staff || $staff_rank < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/zona-staff.php');
    exit;
}

$flash = '';
$flash_ok = false;

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó.';
    } else {
        $action = $mybb->get_input('zs_action');
        if (in_array($action, array('tram_aprobar', 'tram_rechazar'), true)) {
            $tid = (int) $mybb->get_input('tid', MyBB::INPUT_INT);
            $tipo_tr = '';
            if ($db->table_exists('rol_tramites') && $tid > 0) {
                $tq = $db->simple_select('rol_tramites', 'tipo', "tid = {$tid}", array('limit' => 1));
                if ($db->num_rows($tq)) {
                    $tipo_tr = (string) $db->fetch_field($tq, 'tipo');
                }
            }
            $need = ope_tramite_rank_min($tipo_tr);
            if ($staff_rank < $need && $staff_rank < 4) {
                $flash = 'Rank insuficiente para resolver este trámite (requiere ' . $need . '+).';
            } else {
                $extra = array(
                    'roll' => (int) $mybb->get_input('hao_roll'),
                    'chance' => (int) $mybb->get_input('hao_chance'),
                );
                if ($extra['chance'] < 1) {
                    $extra['chance'] = 8;
                }
                if ($extra['roll'] < 1) {
                    unset($extra['roll']);
                }
                $res = ope_tramite_resolver(
                    $tid,
                    $uid,
                    $action === 'tram_aprobar' ? 'aprobar' : 'rechazar',
                    $mybb->get_input('nota_staff'),
                    $extra
                );
                $flash = $res['msg'];
                $flash_ok = !empty($res['ok']);
            }
        }
    }
}

$tram_cat = ope_tramites_catalogo();
$tram_cola = array();
if ($db->table_exists('rol_tramites')) {
    $q = $db->simple_select(
        'rol_tramites',
        '*',
        "estado IN ('pendiente','en_proceso') AND tipo != 'crear_personaje'",
        array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => 80)
    );
    while ($row = $db->fetch_array($q)) {
        $tram_cola[] = $row;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Cola de trámites</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Trámites</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Cola de trámites</h1>
      <span class="code">// STF-02</span>
      <span class="rule"></span>
    </div>
    <p class="zs-intro">Ver desde Colaborador+. Resolver según el <b>rank mínimo del tipo</b>.</p>
    <p><a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/zona-staff.php">&larr; Paneles</a></p>
  </section>
<?php if ($flash !== ''): ?>
  <div class="zs-flash<?php echo $flash_ok ? '' : ' warn'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Pendientes</span><span class="c">// <?php echo count($tram_cola); ?></span></div>
      <div class="plate-b zs-plate-left">
<?php if (empty($tram_cola)): ?>
        <p class="pj-empty">Cola vacía.</p>
<?php else: ?>
        <div class="zs-stafftbl">
<?php foreach ($tram_cola as $tr):
            $tt = (string) $tr['tipo'];
            $tit = $tram_cat[$tt]['titulo'] ?? $tt;
            $need = ope_tramite_rank_min($tt);
            $can_res = ($staff_rank >= $need || $staff_rank >= 4);
            $tdatos = json_decode((string) ($tr['datos'] ?? ''), true);
            if (!is_array($tdatos)) {
                $tdatos = array();
            }
            $tp_nombre = '';
            $tpid = (int) $tr['pid'];
            if ($tpid > 0) {
                $npq = $db->simple_select('rol_personajes', 'nombre', "pid = {$tpid}", array('limit' => 1));
                if ($db->num_rows($npq)) {
                    $tp_nombre = (string) $db->fetch_field($npq, 'nombre');
                }
            }
?>
          <div class="zs-staffrow zs-tram-row">
            <div class="zs-staffwho">
              <span class="zs-staffname"><?php echo htmlspecialchars_uni($tit); ?></span>
              <span class="zs-staffowner">#<?php echo $tpid; ?> <?php echo htmlspecialchars_uni($tp_nombre); ?>
                · <?php echo htmlspecialchars_uni($tr['estado']); ?>
                · rank≥<?php echo (int) $need; ?>
<?php if (!empty($tdatos['hito'])): ?> · <?php echo htmlspecialchars_uni(mb_substr((string) $tdatos['hito'], 0, 80, 'UTF-8')); ?><?php endif; ?>
              </span>
            </div>
<?php if ($can_res): ?>
            <div class="zs-tram-actions">
<?php if ($tt === 'hao_despertar' && ($tdatos['ruta'] ?? '') !== 'pd'): ?>
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="zs_action" value="tram_aprobar">
                <input type="hidden" name="tid" value="<?php echo (int) $tr['tid']; ?>">
                <input type="number" name="hao_chance" value="8" min="1" max="40" title="% éxito" class="zs-input-sm">
                <input type="number" name="hao_roll" value="" min="1" max="100" placeholder="d100" class="zs-input-sm">
                <button type="submit" class="btn btn-hot btn-sm">Tirar / aprobar</button>
              </form>
<?php else: ?>
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="zs_action" value="tram_aprobar">
                <input type="hidden" name="tid" value="<?php echo (int) $tr['tid']; ?>">
                <button type="submit" class="btn btn-hot btn-sm">Aprobar</button>
              </form>
<?php endif; ?>
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="zs_action" value="tram_rechazar">
                <input type="hidden" name="tid" value="<?php echo (int) $tr['tid']; ?>">
                <input type="text" name="nota_staff" placeholder="Motivo" maxlength="200">
                <button type="submit" class="btn btn-ghost btn-sm">Rechazar</button>
              </form>
            </div>
<?php else: ?>
            <span class="mono fs-76 c-dim">Solo lectura (rank ≥ <?php echo (int) $need; ?>)</span>
<?php endif; ?>
          </div>
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
