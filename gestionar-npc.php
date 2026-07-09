<?php
/**
 * I-Forge · Gestionar NPC
 * Asigna y desasigna personajes no jugadores (NPC) a cuentas de Narrador.
 *
 * Acceso: Administrador+ (rank >= 3) con el personaje activo.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-npc.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin
    ? ope_rol_active_staff($uid)
    : array('rank' => 0, 'narrador' => 0, 'is_staff' => false, 'nombre' => '');
$rank = (int) $staff['rank'];

if (!$loggedin || $rank < 3) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

// ── POST: asignar NPC ──
if ($mybb->request_method === 'post'
    && $mybb->get_input('action') === 'assign_npc'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $db->table_exists('rol_personajes')) {

    $target_pid = (int) $mybb->get_input('target_pid', MyBB::INPUT_INT);
    $target_uid = (int) $mybb->get_input('narrator_uid', MyBB::INPUT_INT);

    if ($target_pid > 0 && $target_uid > 0) {
        $nq = $db->simple_select('rol_personajes', 'pid', "pid = {$target_pid} AND es_npc = 1 AND uid = 0", array('limit' => 1));
        if ($db->num_rows($nq)) {
            $db->update_query('rol_personajes', array(
                'uid'    => $target_uid,
                'estado' => 'aprobado',
            ), "pid = {$target_pid}");
        }
    }
    header('Location: ' . $bburl . '/gestionar-npc.php?ok=assign');
    exit;
}

// ── POST: desasignar NPC ──
if ($mybb->request_method === 'post'
    && $mybb->get_input('action') === 'unassign_npc'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $db->table_exists('rol_personajes')) {

    $target_pid = (int) $mybb->get_input('target_pid', MyBB::INPUT_INT);
    if ($target_pid > 0) {
        $nq = $db->simple_select('rol_personajes', 'pid', "pid = {$target_pid} AND es_npc = 1 AND uid > 0", array('limit' => 1));
        if ($db->num_rows($nq)) {
            $db->update_query('rol_personajes', array('uid' => 0), "pid = {$target_pid}");
        }
    }
    header('Location: ' . $bburl . '/gestionar-npc.php?ok=unassign');
    exit;
}

$flash = '';
$flash_type = '';
$ok = $mybb->get_input('ok');
if ($ok === 'assign') {
    $flash = 'NPC asignado correctamente.';
    $flash_type = 'ok';
} elseif ($ok === 'unassign') {
    $flash = 'NPC desasignado correctamente.';
    $flash_type = 'ok';
}

// ── Narradores: usuarios con al menos un personaje con staff_narrador=1 ──
$narradores = array();
if ($db->table_exists('rol_personajes')) {
    $nrq = $db->query("
        SELECT DISTINCT p.uid, u.username
        FROM mybb_rol_personajes p
        JOIN mybb_users u ON u.uid = p.uid
        WHERE p.staff_narrador = 1 AND p.uid > 0
        ORDER BY u.username ASC
    ");
    while ($urow = $db->fetch_array($nrq)) {
        $narradores[] = $urow;
    }
}

// ── Sección A: NPCs sin asignar (es_npc=1, uid=0) ──
$sin_asignar = array();
if ($db->table_exists('rol_personajes')) {
    $sq = $db->simple_select('rol_personajes', 'pid, nombre, rango, nivel',
        "es_npc = 1 AND uid = 0",
        array('order_by' => 'nombre', 'order_dir' => 'ASC', 'limit' => 200)
    );
    while ($srow = $db->fetch_array($sq)) {
        $sin_asignar[] = $srow;
    }
}

// ── Sección B: NPCs asignados (es_npc=1, uid>0) ──
$asignados = array();
if ($db->table_exists('rol_personajes')) {
    $aq = $db->query("
        SELECT p.pid, p.nombre, p.rango, p.nivel, p.uid, u.username
        FROM mybb_rol_personajes p
        JOIN mybb_users u ON u.uid = p.uid
        WHERE p.es_npc = 1 AND p.uid > 0
        ORDER BY p.nombre ASC
        LIMIT 200
    ");
    while ($arow = $db->fetch_array($aq)) {
        $asignados[] = $arow;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Gestionar NPC</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff ope-pg-gestionar-npc">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Gestionar NPC</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Gestionar NPC</h1>
      <span class="code">// asignaci&oacute;n de personajes no jugadores</span>
      <span class="rule"></span>
    </div>
  </section>

  <section class="reveal">
    <p class="zs-intro">Asigna NPCs a cuentas con personaje <b>Narrador</b> para que puedan postear como ellos. Los NPCs sin asignar est&aacute;n disponibles para cualquier Narrador; los asignados aparecer&aacute;n en el selector de personaje de esa cuenta.</p>
  </section>

<?php if ($flash !== ''): ?>
  <section class="reveal">
    <div class="zs-flash"><?php echo $flash; ?></div>
  </section>
<?php endif; ?>

  <!-- ── Sección A: NPCs sin asignar ── -->
  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">NPCs sin asignar</span>
      <span class="need bg-h6"><?php echo count($sin_asignar); ?> disponible(s)</span>
      <span class="rule"></span>
    </div>

<?php if (empty($sin_asignar)): ?>
    <div class="empty-state"><div class="big">Sin NPCs disponibles</div><p>Todos los NPCs han sido asignados. Puedes crear nuevos NPCs desde la Zona Staff.</p></div>
<?php else: ?>
    <div class="zs-stafftbl">
<?php foreach ($sin_asignar as $npc): ?>
      <form method="post" action="<?php echo $bburl; ?>/gestionar-npc.php" class="zs-staffrow">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="assign_npc">
        <input type="hidden" name="target_pid" value="<?php echo (int) $npc['pid']; ?>">
        <div class="zs-staffwho">
          <span class="zs-staffname"><?php echo htmlspecialchars_uni($npc['nombre']); ?></span>
          <span class="zs-staffowner">// <?php echo htmlspecialchars_uni($npc['rango']); ?> &middot; Nivel <?php echo (int) $npc['nivel']; ?></span>
        </div>
        <select name="narrator_uid" class="zs-staffsel" required>
          <option value="">Seleccionar narrador&hellip;</option>
<?php foreach ($narradores as $nr): ?>
          <option value="<?php echo (int) $nr['uid']; ?>"><?php echo htmlspecialchars_uni($nr['username']); ?></option>
<?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-hot btn-sm">Asignar</button>
      </form>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

  <!-- ── Sección B: NPCs asignados ── -->
  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">NPCs asignados</span>
      <span class="need bg-patina"><?php echo count($asignados); ?> asignado(s)</span>
      <span class="rule"></span>
    </div>

<?php if (empty($asignados)): ?>
    <div class="empty-state"><div class="big">Sin NPCs asignados</div><p>No hay NPCs asignados a ninguna cuenta de Narrador todav&iacute;a.</p></div>
<?php else: ?>
    <div class="zs-stafftbl">
<?php foreach ($asignados as $npc): ?>
      <form method="post" action="<?php echo $bburl; ?>/gestionar-npc.php" class="zs-staffrow">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="unassign_npc">
        <input type="hidden" name="target_pid" value="<?php echo (int) $npc['pid']; ?>">
        <div class="zs-staffwho">
          <span class="zs-staffname"><?php echo htmlspecialchars_uni($npc['nombre']); ?></span>
          <span class="zs-staffowner">// <?php echo htmlspecialchars_uni($npc['rango']); ?> &middot; Nivel <?php echo (int) $npc['nivel']; ?></span>
        </div>
        <span class="zs-staffnarr c-patina">&#8618; <?php echo htmlspecialchars_uni($npc['username']); ?></span>
        <button type="submit" class="btn btn-ghost btn-sm">Desasignar</button>
      </form>
<?php endforeach; ?>
    </div>
<?php endif; ?>
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
