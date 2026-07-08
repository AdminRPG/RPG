<?php
/**
 * One Piece Eternal · Gestión de staff (solo Web Master)
 * ------------------------------------------------------
 * Asignación de rol de staff (Colaborador < Moderador < Administrador <
 * Web Master) y del añadido opcional Narrador a cada PERSONAJE. El staff es por
 * personaje: el rol solo aplica cuando ese personaje está activo.
 *
 * Acceso: requiere que el PERSONAJE ACTIVO sea Web Master (rank 4).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-staff.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

// ── Staff del personaje activo ──
$staff = $loggedin
    ? ope_rol_active_staff($uid)
    : array('rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank      = (int) $staff['rank'];
$char_name = htmlspecialchars_uni((string) $staff['nombre']);

// Solo Web Master.
if (!$loggedin || $rank < 4) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

// ── POST: asignar rol / narrador a un personaje ──
$roles_validos = array('', 'colaborador', 'moderador', 'administrador', 'webmaster');
if ($mybb->request_method === 'post'
    && $mybb->get_input('action') === 'set_staff'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $db->table_exists('rol_personajes')) {

    $target = (int) $mybb->get_input('target_pid', MyBB::INPUT_INT);
    $nuevo  = (string) $mybb->get_input('staff_rol');
    $narr   = $mybb->get_input('staff_narrador') ? 1 : 0;
    if (!in_array($nuevo, $roles_validos, true)) $nuevo = '';

    if ($target > 0) {
        $tq = $db->simple_select('rol_personajes', 'pid', "pid = {$target}", array('limit' => 1));
        if ($db->num_rows($tq)) {
            $db->update_query('rol_personajes', array(
                'staff_rol'      => $db->escape_string($nuevo),
                'staff_narrador' => $narr,
            ), "pid = {$target}");
        }
    }
    // PRG: evita reenvíos al recargar.
    header('Location: ' . $bburl . '/gestionar-staff.php?ok=1');
    exit;
}
$staff_ok = $mybb->get_input('ok') ? true : false;

// Búsqueda opcional por nombre.
$buscar = trim((string) $mybb->get_input('q'));

// ── Listado de personajes ──
$staff_chars = array();
if ($db->table_exists('rol_personajes')) {
    $where = "(estado = 'aprobado' OR staff_rol <> '' OR staff_narrador = 1)";
    if ($buscar !== '') {
        $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    }
    $sq = $db->simple_select(
        'rol_personajes',
        'pid, nombre, uid, estado, staff_rol, staff_narrador',
        $where,
        array('order_by' => 'staff_rol DESC, nombre', 'order_dir' => 'ASC', 'limit' => 300)
    );
    while ($srow = $db->fetch_array($sq)) {
        $srow['owner'] = '?';
        if ((int) $srow['uid'] > 0) {
            $ownq = $db->simple_select('users', 'username', 'uid = ' . (int) $srow['uid'], array('limit' => 1));
            if ($db->num_rows($ownq)) $srow['owner'] = $db->fetch_field($ownq, 'username');
        }
        $staff_chars[] = $srow;
    }
}

// Referencia de permisos por rol.
$permisos = array(
    array('rol' => 'Colaborador',   'col' => 'var(--h6)',        'desc' => 'Aprobar / moderar / rechazar expedientes.'),
    array('rol' => 'Moderador',     'col' => 'var(--ember-hi)',  'desc' => 'Lo de Colaborador + moderación de temas y mensajes (próximamente).'),
    array('rol' => 'Administrador', 'col' => 'var(--crack)',     'desc' => 'Lo de Moderador + administración del foro (próximamente).'),
    array('rol' => 'Web Master',    'col' => 'var(--patina)',    'desc' => 'Control total, incluida esta gestión de staff.'),
    array('rol' => 'Narrador',      'col' => 'var(--patina-hi)', 'desc' => 'Añadido opcional combinable con cualquier rol (herramientas de narración, próximamente).'),
);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Gesti&oacute;n de staff</title>
<?php echo ope_rol_head_base(); ?>
<!-- reutiliza el scope ope-pg-zona-staff (fuente única de estilos) -->
</head>
<body class="ope-pg-zona-staff ope-pg-gestionar-staff">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Gesti&oacute;n de staff</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Gesti&oacute;n de staff</h1>
      <span class="code">// roles y permisos por personaje</span>
      <span class="rule"></span>
    </div>
  </section>

  <section class="reveal">
    <p class="zs-intro">El rol de staff vive en el <b>personaje</b>, no en la cuenta: solo tiene efecto cuando ese personaje est&aacute; activo. Jerarqu&iacute;a acumulativa <b>Colaborador &lt; Moderador &lt; Administrador &lt; Web Master</b>. <b>Narrador</b> es un a&ntilde;adido opcional combinable con cualquier rol (o en solitario).</p>
  </section>

  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">Permisos por rol</span>
      <span class="rule"></span>
    </div>
    <div class="zs-perms">
<?php foreach ($permisos as $p): ?>
      <div class="zs-perm">
        <span class="zs-perm-tag" style="background:<?php echo $p['col']; ?>"><?php echo $p['rol']; ?></span>
        <span class="zs-perm-d"><?php echo $p['desc']; ?></span>
      </div>
<?php endforeach; ?>
    </div>
  </section>

  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">Personajes</span>
      <span class="need" style="background:var(--patina);color:var(--iron)"><?php echo count($staff_chars); ?> personaje(s)</span>
      <span class="rule"></span>
    </div>
<?php if ($staff_ok): ?>
    <div class="zs-flash">Rol de staff actualizado correctamente.</div>
<?php endif; ?>
    <form method="get" action="<?php echo $bburl; ?>/gestionar-staff.php" class="zs-search">
      <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar personaje por nombre&hellip;">
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
<?php if ($buscar !== ''): ?>
      <a href="<?php echo $bburl; ?>/gestionar-staff.php" class="btn btn-ghost btn-sm">Limpiar</a>
<?php endif; ?>
    </form>
    <div class="zs-stafftbl">
<?php if (empty($staff_chars)): ?>
      <div class="empty-state"><div class="big">Sin resultados</div><p>No hay personajes que coincidan. Los personajes aprobados aparecen aqu&iacute; para asignarles rol.</p></div>
<?php else: foreach ($staff_chars as $sc): ?>
      <form method="post" action="<?php echo $bburl; ?>/gestionar-staff.php" class="zs-staffrow">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="set_staff">
        <input type="hidden" name="target_pid" value="<?php echo (int) $sc['pid']; ?>">
        <div class="zs-staffwho">
          <span class="zs-staffname"><a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $sc['pid']; ?>"><?php echo htmlspecialchars_uni($sc['nombre']); ?></a></span>
          <span class="zs-staffowner">// <?php echo htmlspecialchars_uni($sc['owner']); ?> &middot; <?php echo htmlspecialchars_uni($sc['estado']); ?></span>
        </div>
        <select name="staff_rol" class="zs-staffsel">
          <option value=""<?php echo $sc['staff_rol'] === '' ? ' selected' : ''; ?>>Sin rol</option>
          <option value="colaborador"<?php echo $sc['staff_rol'] === 'colaborador' ? ' selected' : ''; ?>>Colaborador</option>
          <option value="moderador"<?php echo $sc['staff_rol'] === 'moderador' ? ' selected' : ''; ?>>Moderador</option>
          <option value="administrador"<?php echo $sc['staff_rol'] === 'administrador' ? ' selected' : ''; ?>>Administrador</option>
          <option value="webmaster"<?php echo $sc['staff_rol'] === 'webmaster' ? ' selected' : ''; ?>>Web Master</option>
        </select>
        <label class="zs-staffnarr"><input type="checkbox" name="staff_narrador" value="1"<?php echo (int) $sc['staff_narrador'] === 1 ? ' checked' : ''; ?>> Narrador</label>
        <button type="submit" class="btn btn-hot btn-sm">Guardar</button>
      </form>
<?php endforeach; endif; ?>
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
