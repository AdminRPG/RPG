<?php
/**
 * I-Forge · Gestionar solicitudes de acompañante (Zona Staff)
 * Aprueba o rechaza las solicitudes de acompañante NPC de los jugadores.
 * Al aprobar, el NPC se asigna al primer slot libre del personaje.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-acompanantes.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin ? gbe_rol_active_staff($uid) : array('rank' => 0, 'is_staff' => false);
$is_staff = !empty($staff['is_staff']) && (int) $staff['rank'] >= 1;

$flash = '';
$flash_kind = 'ok';
$pk = htmlspecialchars_uni($mybb->post_code);
$table_ok = $db->table_exists('rol_acompanante_solicitudes') && $db->table_exists('rol_acompanantes');

if ($is_staff && $table_ok && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $sid = (int) $mybb->get_input('sid', MyBB::INPUT_INT);
        $nota = (string) $mybb->get_input('nota');
        if ($sid > 0 && $action === 'aprobar') {
            $res = gbe_rol_acompanante_solicitud_aprobar($sid, $uid, $nota);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
        } elseif ($sid > 0 && $action === 'rechazar') {
            $res = gbe_rol_acompanante_solicitud_rechazar($sid, $uid, $nota);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
        }
    }
}

$pendientes = ($is_staff && $table_ok) ? gbe_rol_acompanante_solicitudes_pendientes() : array();
$max_slots  = function_exists('gbe_rol_acompanantes_max') ? gbe_rol_acompanantes_max() : 2;

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Solicitudes de acompa&ntilde;ante</title>
<?php echo gbe_rol_head_base(); ?>
<?php echo gbe_rol_npc_sec_card_css(); ?>
</head>
<body class="gbe-pg-mundo-vivo gbe-pg-gestionar-acomp">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Solicitudes de acompa&ntilde;ante</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Solicitudes de acompa&ntilde;ante</h1><span class="code">// aprobar &middot; rechazar</span><span class="rule"></span></div>
  </section>

<?php if (!$is_staff): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><div class="noperm"><div class="big">Zona reservada al staff</div><a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver</a></div></div></div></section>
<?php elseif (!$table_ok): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="mv-empty">Falta la infraestructura de acompa&ntilde;antes. Ejecuta <code>php scripts/migrate-acompanantes.php</code>.</p></div></div></section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Pendientes</span><span class="c">// <?php echo count($pendientes); ?> solicitud(es)</span></div>
      <div class="plate-b">
<?php if (empty($pendientes)): ?>
        <p class="mv-empty">No hay solicitudes de acompa&ntilde;ante pendientes.</p>
<?php else: foreach ($pendientes as $s):
        $npc = $s['npc'] ?? null;
        $slots_full = ((int) $s['asignados'] >= $max_slots);
?>
        <div class="ga-item">
          <div class="ga-card">
<?php if ($npc): ?>
            <?php echo gbe_rol_npc_sec_card_html($npc); ?>
<?php else: ?>
            <div class="ga-npc-missing">NPC eliminado de la biblioteca (id <?php echo (int) $s['npc_id']; ?>).</div>
<?php endif; ?>
          </div>
          <div class="ga-meta">
            <div class="ga-head">
              <span class="ga-pj"><?php echo htmlspecialchars_uni($s['pj_nombre'] !== '' ? $s['pj_nombre'] : ('pid #' . (int) $s['pid'])); ?></span>
              <span class="ga-owner">@<?php echo htmlspecialchars_uni($s['owner'] !== '' ? $s['owner'] : ('uid ' . (int) $s['uid'])); ?></span>
              <span class="ga-date"><?php echo date('d/m/Y H:i', (int) $s['dateline']); ?></span>
            </div>
            <div class="ga-slots">Acompa&ntilde;antes actuales: <b><?php echo (int) $s['asignados']; ?>/<?php echo (int) $max_slots; ?></b><?php echo $slots_full ? ' &middot; <span class="ga-warn">slots llenos</span>' : ''; ?></div>
<?php if (trim((string) $s['motivo']) !== ''): ?>
            <p class="ga-motivo"><?php echo nl2br(htmlspecialchars_uni((string) $s['motivo'])); ?></p>
<?php else: ?>
            <p class="ga-motivo ga-motivo-empty">Sin motivo indicado.</p>
<?php endif; ?>
            <form method="post" action="<?php echo $bburl; ?>/gestionar-acompanantes.php" class="ga-form">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="sid" value="<?php echo (int) $s['id']; ?>">
              <input type="text" name="nota" class="ga-nota" maxlength="500" placeholder="Nota para el jugador (opcional)">
              <div class="ga-acts">
                <button type="submit" name="action" value="aprobar" class="btn btn-hot btn-sm"<?php echo ($npc && !$slots_full) ? '' : ' disabled'; ?>>Aprobar</button>
                <button type="submit" name="action" value="rechazar" class="btn btn-ghost btn-sm" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</button>
              </div>
            </form>
<?php if ($slots_full): ?>
            <p class="ga-hint">No se puede aprobar: el personaje ya tiene los slots llenos.</p>
<?php endif; ?>
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
