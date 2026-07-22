<?php
/**
 * One Piece: Eternal · Ventanilla de trámite
 * Formulario de un tipo concreto (?tipo=akuma_pd …).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramite.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_tramites.php';
require_once MYBB_ROOT . 'inc/ope_rol_frutas.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}

$catalogo = ope_tramites_catalogo();
$tipo = $mybb->get_input('tipo');
if ($tipo === '' || !isset($catalogo[$tipo])) {
    header('Location: ' . $mybb->settings['bburl'] . '/tramites.php');
    exit;
}
$info = $catalogo[$tipo];

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

$flash = '';
$flash_ok = false;

if ($mybb->request_method === 'post' && $pid > 0) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó.';
    } else {
        $payload = array(
            'hito' => trim($mybb->get_input('hito')),
            'ruta' => $mybb->get_input('ruta'),
            'modo' => $mybb->get_input('modo'),
            'tier' => (int) $mybb->get_input('tier'),
            'fruta_id' => (int) $mybb->get_input('fruta_id'),
            'nombre' => trim($mybb->get_input('nombre')),
            'descripcion' => trim($mybb->get_input('descripcion')),
            'pa' => (int) $mybb->get_input('pa'),
            'en' => (int) $mybb->get_input('en'),
            'dote_id' => trim($mybb->get_input('dote_id')),
            'slot' => trim($mybb->get_input('slot')),
            'motivo' => trim($mybb->get_input('motivo')),
        );
        $res = ope_tramite_crear($uid, $pid, $tipo, $payload);
        $flash = (string) ($res['msg'] ?? '');
        $flash_ok = !empty($res['ok']);
    }
}

$pj_nombre = '';
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'nombre', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj_nombre = (string) $db->fetch_field($pq, 'nombre');
    }
}

$frutas_libres = function_exists('ope_fruta_libres') ? ope_fruta_libres(0) : array();
$tit = (string) $info['titulo'];

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · <?php echo htmlspecialchars_uni($tit); ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tramites">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a><span class="sep">›</span>
  <b><?php echo htmlspecialchars_uni($tit); ?></b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1><?php echo htmlspecialchars_uni($tit); ?></h1>
      <span class="code">// <?php echo htmlspecialchars_uni($tipo); ?></span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro"><?php echo htmlspecialchars_uni($info['desc']); ?>
<?php if ($pj_nombre !== ''): ?> · PJ: <b><?php echo htmlspecialchars_uni($pj_nombre); ?></b><?php endif; ?></p>
<?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_ok ? 'ok' : 'warn'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
<?php endif; ?>
  </section>

  <section class="reveal">
    <div class="plate tram-detail">
      <div class="plate-h"><span class="t">Solicitud</span><span class="c">// formulario</span></div>
      <div class="plate-b">
<?php if ($pid < 1): ?>
        <p class="pj-empty">Activa un personaje en la ficha para enviar este trámite.</p>
        <p><a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/tramites.php">&larr; Volver a ventanillas</a></p>
<?php else: ?>
        <form method="post" action="<?php echo $bburl; ?>/tramite.php?tipo=<?php echo urlencode($tipo); ?>" class="tram-form tram-form--detail">
          <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
          <input type="hidden" name="tram_tipo" value="<?php echo htmlspecialchars_uni($tipo); ?>">
<?php if ($tipo === 'hao_despertar'): ?>
          <label class="ope-field"><span>Ruta</span>
            <select name="ruta">
              <option value="tirada">Tirada 1d100 (staff)</option>
              <option value="pd">Forzar con 4 PD</option>
            </select>
          </label>
<?php endif; ?>
<?php if ($tipo === 'akuma_pd'): ?>
          <label class="ope-field"><span>Modo</span>
            <select name="modo" class="tram-akuma-modo">
              <option value="tier">Aleatoria de Tier</option>
              <option value="concreta">Fruta concreta</option>
            </select>
          </label>
          <label class="ope-field"><span>Tier (I–V)</span>
            <select name="tier">
<?php for ($t = 1; $t <= 5; $t++): ?>
              <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
<?php endfor; ?>
            </select>
          </label>
          <label class="ope-field"><span>Fruta concreta</span>
            <select name="fruta_id">
              <option value="0">—</option>
<?php foreach ($frutas_libres as $f): ?>
              <option value="<?php echo (int) $f['id']; ?>"><?php echo htmlspecialchars_uni($f['nombre']); ?> (T<?php echo (int) ($f['tier'] ?? 1); ?>)</option>
<?php endforeach; ?>
            </select>
          </label>
<?php endif; ?>
<?php if ($tipo === 'tecnica_custom'): ?>
          <label class="ope-field"><span>Nombre</span><input type="text" name="nombre" maxlength="120" required></label>
          <label class="ope-field"><span>Descripción</span><textarea name="descripcion" rows="4" required></textarea></label>
          <label class="ope-field"><span>PA</span><input type="number" name="pa" min="0" max="10" value="1"></label>
          <label class="ope-field"><span>EN</span><input type="number" name="en" min="0" max="200" value="10"></label>
<?php endif; ?>
<?php if ($tipo === 'dote_poder'): ?>
          <label class="ope-field"><span>ID / nombre de dote</span><input type="text" name="dote_id" maxlength="80" required></label>
<?php endif; ?>
<?php if ($tipo === 'cyborg'): ?>
          <label class="ope-field"><span>Slot</span>
            <select name="slot"><option>brazo</option><option>pierna</option><option>ojo</option><option>torso</option></select>
          </label>
<?php endif; ?>
<?php if ($tipo === 'herencia'): ?>
          <label class="ope-field"><span>Motivo / escena</span><textarea name="motivo" rows="4" required></textarea></label>
<?php else: ?>
          <label class="ope-field"><span>Hito / justificación</span><textarea name="hito" rows="4" placeholder="Describe la escena o motivo…" required></textarea></label>
<?php endif; ?>
          <div class="tram-form-actions">
            <a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/tramites.php">&larr; Ventanillas</a>
            <button type="submit" class="btn btn-hot btn-sm">Enviar solicitud</button>
          </div>
        </form>
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
