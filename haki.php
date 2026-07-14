<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'haki.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_haki.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);

require_once MYBB_ROOT . 'inc/ope_user_init.php';

$initials       = ope_get_initials($mybb->user['username'] ?? '');
$initials_e     = htmlspecialchars_uni($initials);
$display_name   = ope_get_display_name();
$display_name_e = htmlspecialchars_uni($display_name);

$active_pid = 0;
if ($loggedin && isset($mybb->user['ope_active_pid']) && (int)$mybb->user['ope_active_pid'] > 0) {
    $active_pid = (int)$mybb->user['ope_active_pid'];
} elseif ($loggedin && $db->table_exists('rol_cuentas')) {
    $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($cq)) $active_pid = (int)$db->fetch_field($cq, 'personaje_activo');
}

$staff_level = ope_get_staff_level($uid, $active_pid);

$flash = '';
$flash_kind = 'ok';

// Procesar subida de Haki
if ($loggedin && $active_pid > 0 && $mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'), true)) {
    $tipo = $mybb->get_input('haki_tipo');
    $resultado = ope_haki_subir($active_pid, $tipo);
    if ($resultado === '') {
        $flash = '¡Haki mejorado!';
    } else {
        $flash = $resultado;
        $flash_kind = 'error';
    }
}

// Cargar datos
$pj = null;
$pj_nivel = 0;
if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'nombre, nivel, stats_json', "pid = {$active_pid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj = $db->fetch_array($pq);
        $pj_nivel = (int)$pj['nivel'];
    }
}
$haki = function_exists('ope_haki_get') ? ope_haki_get($active_pid) : array();
$pp_data = function_exists('ope_pp_saldo') ? ope_pp_saldo($active_pid) : array('pp_disponible' => 0);
$haki_tipos = function_exists('ope_haki_tipos') ? ope_haki_tipos() : array();
$haki_niveles = function_exists('ope_haki_niveles') ? ope_haki_niveles() : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Haki</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-haki) -->
</head>
<body class="ope-pg-haki">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/progresion.php">Progresión</a>
    <span class="sep">&#8250;</span>
    <b>Haki</b>
  </div>
</div>

<div class="wrap">

<?php if (!$loggedin || $active_pid < 1): ?>
  <section class="reveal">
    <div class="shead"><h1>Haki</h1><span class="code">// voluntad</span><span class="rule"></span></div>
    <div class="plate"><div class="plate-b">
      <p class="pj-empty"><?php echo $loggedin ? 'Selecciona un personaje activo.' : 'Necesitas acceder.'; ?></p>
    </div></div>
  </section>
<?php else: ?>

  <?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
  <?php endif; ?>

  <section class="reveal">
    <div class="shead">
      <h1>Haki · <?php echo htmlspecialchars_uni($pj['nombre']); ?></h1>
      <span class="code">// Nivel <?php echo $pj_nivel; ?></span>
      <span class="rule"></span>
    </div>
  </section>

  <!-- ── Barra de PP ── -->
  <section class="reveal">
    <div class="ope-prog-ppbar">
      <div class="ope-prog-ppbar-total">
        <span class="ope-prog-ppbar-val"><?php echo (int)$pp_data['pp_disponible']; ?></span>
        <span class="ope-prog-ppbar-label">PP disponibles</span>
      </div>
      <div class="ope-prog-ppbar-detail">
        <div class="ope-prog-ppbar-stat">
          <span class="ope-prog-ppbar-num"><?php echo (int)$pp_data['pp_total']; ?></span>
          <span class="ope-prog-ppbar-lbl">Total ganados</span>
        </div>
        <div class="ope-prog-ppbar-stat">
          <span class="ope-prog-ppbar-num"><?php echo (int)$pp_data['pp_gastado']; ?></span>
          <span class="ope-prog-ppbar-lbl">Gastados</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Los 3 tipos de Haki -->
  <?php foreach ($haki_tipos as $tipo_key => $tipo_info):
    $nivel = $haki[$tipo_key]['nivel'];
    $siguiente = $nivel + 1;
    $puede_subir = ($nivel < 4) && ($tipo_key !== 'haoshoku' || $nivel === 0);
    $info_nivel = isset($haki_niveles[$siguiente]) ? $haki_niveles[$siguiente] : null;
    $coste = $info_nivel ? $info_nivel['coste_pp'] : 0;
    $requiere = $info_nivel ? $info_nivel['requiere_nivel'] : 0;
    $bloqueado = $info_nivel && $pj_nivel < $requiere;
    $sin_pp = $pp_data['pp_disponible'] < $coste;
  ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t"><?php echo htmlspecialchars_uni($tipo_info['nombre']); ?></span>
        <span class="c">// Nivel <?php echo $nivel; ?>/4</span>
      </div>
      <div class="plate-b">
        <p><?php echo htmlspecialchars_uni($tipo_info['desc']); ?></p>
        <!-- Barra de progreso niveles -->
        <div class="ope-haki-bar">
          <?php for ($i = 1; $i <= 4; $i++): ?>
          <span class="ope-haki-dot<?php echo $i <= $nivel ? ' ope-haki-dot--on' : ''; ?>">Nv.<?php echo $i; ?></span>
          <?php endfor; ?>
        </div>
        <?php if ($nivel >= 4): ?>
          <p class="ope-haki-max">&#9733; Nivel Supremo alcanzado</p>
        <?php elseif ($tipo_key === 'haoshoku' && $nivel >= 1): ?>
          <p class="ope-haki-pl">Haoshoku se mejora con Puntos de Leyenda (PL), no con PP.</p>
        <?php elseif ($bloqueado): ?>
          <p class="ope-haki-locked">Requiere nivel <?php echo $requiere; ?> (tienes <?php echo $pj_nivel; ?>)</p>
        <?php elseif ($sin_pp): ?>
          <p class="ope-haki-locked">Necesitas <?php echo $coste; ?> PP (tienes <?php echo (int)$pp_data['pp_disponible']; ?>)</p>
        <?php else: ?>
          <form method="post" action="haki.php">
            <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
            <input type="hidden" name="haki_tipo" value="<?php echo $tipo_key; ?>">
            <p>Subir a nivel <?php echo $siguiente; ?> (<?php echo $info_nivel['nombre']; ?>): <b><?php echo $coste; ?> PP</b></p>
            <button type="submit" class="btn btn-hot">Subir Haki</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endforeach; ?>

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
