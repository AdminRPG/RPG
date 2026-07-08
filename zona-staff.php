<?php
/**
 * I-Forge · Zona Staff
 * Página de administración del rol, GATED por staff_level (mybb_rol_cuentas).
 *
 * Jerarquía acumulativa:
 *   1 = Narrador       → ve zonas nivel >= 1
 *   2 = Moderador      → ve zonas nivel >= 1 y >= 2
 *   3 = Administrador  → ve todas las zonas
 * staff_level 0 no tiene acceso: se muestra un mensaje de sin permiso.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid       = (int) ($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// ── Staff del PERSONAJE ACTIVO (no de la cuenta) ──
// El rol de staff vive en el personaje: si tienes activo un personaje sin rol,
// no eres staff aquí aunque otro de tus personajes lo sea.
$staff = $loggedin
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank      = (int) $staff['rank'];
$narrador  = (int) $staff['narrador'];
$is_staff  = !empty($staff['is_staff']);
$rol_lbl   = ope_rol_staff_label($staff['rol']);
$char_name = htmlspecialchars_uni((string) $staff['nombre']);

// Etiqueta de rango a mostrar ("Administrador + Narrador", "Narrador", ...).
$mi_rango_lbl = $rol_lbl !== '' ? $rol_lbl : 'Sin rango';
if ($narrador) {
    $mi_rango_lbl = $rol_lbl !== '' ? ($rol_lbl . ' + Narrador') : 'Narrador';
}

// ── Fichas pendientes de revisión (lista + contador) ──
$pendientes = array();
if ($db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'pid, nombre, uid', "estado = 'revision'", array('order_by' => 'pid', 'order_dir' => 'ASC', 'limit' => 30));
    while ($prow = $db->fetch_array($pq)) {
        $prow['owner'] = '?';
        if ((int)$prow['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int)$prow['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) $prow['owner'] = $db->fetch_field($uq, 'username');
        }
        $pendientes[] = $prow;
    }
}
$pendientes_count   = count($pendientes);
$primer_pendiente   = $pendientes_count > 0 ? (int)$pendientes[0]['pid'] : 0;

// ── Contador de personajes con rol de staff (para la tarjeta de gestión) ──
$staff_count = 0;
if ($db->table_exists('rol_personajes')) {
    $scq = $db->simple_select('rol_personajes', 'COUNT(*) AS c', "staff_rol <> '' OR staff_narrador = 1");
    $staff_count = (int) $db->fetch_field($scq, 'c');
}

// ── Definición de zonas ──
// Cada tarjeta pertenece a un grupo de rol. "Aprobación de expedientes" ahora
// requiere Colaborador (rol >= colaborador). El resto de utilidades se añadirán
// cuando tengan backend real.
$zonas = array(
    array('grp' => 'colaborador', 'code' => 'STF-01',
        'title' => 'Aprobaci&oacute;n de expedientes',
        'body'  => 'Revisa las fichas enviadas a revisi&oacute;n, aprueba o rechaza personajes y deja notas al jugador.',
        'meta'  => $pendientes_count . ' pendiente(s)', 'cta' => 'Revisar', 'badge' => $pendientes_count, 'href' => $bburl . '/revisar-personaje.php'),
    array('grp' => 'webmaster', 'code' => 'STF-02',
        'title' => 'Gesti&oacute;n de staff',
        'body'  => 'Asigna el rol (Colaborador, Moderador, Administrador, Web Master) y el a&ntilde;adido de Narrador a cada personaje, y consulta los permisos de cada rol.',
        'meta'  => $staff_count . ' con rol', 'cta' => 'Gestionar', 'badge' => $staff_count, 'href' => $bburl . '/gestionar-staff.php'),
);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Zona Staff</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-zona-staff) -->
</head>
<body class="ope-pg-zona-staff">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Zona Staff</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Zona Staff</h1>
      <span class="code">// administraci&oacute;n del foro</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$is_staff): ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Acceso restringido</span>
        <span class="c">// solo staff</span>
      </div>
      <div class="plate-b">
        <div class="noperm">
          <span class="lock" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="9" rx="1"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
          <div class="big">No tienes acceso a la Zona Staff</div>
          <p>Esta secci&oacute;n est&aacute; reservada al equipo del foro (narradores, moderadores y administradores). Si crees que deber&iacute;as tener acceso, contacta con un administrador.</p>
          <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="reveal">
    <p class="zs-intro">Panel de <b>administraci&oacute;n del foro</b>. El rol de staff va por <b>personaje</b>: solo ves las zonas que desbloquea el rol del personaje que tienes activo. Los roles son <b>acumulativos</b> (un administrador ve lo de colaborador y moderador); <b>Narrador</b> es un rol independiente que puede combinarse con cualquiera.</p>
    <div class="zs-bar">
      <span class="zs-level">Personaje activo: <b><?php echo $char_name !== '' ? $char_name : '&mdash;'; ?></b> &middot; rol: <b><?php echo $mi_rango_lbl; ?></b></span>
    </div>
  </section>

<?php
  // Grupos por rol. 'rank' = rango mínimo jerárquico; 'narr' = grupo del rol narrador.
  $grupos = array(
      'colaborador'   => array('lbl' => 'Colaborador',   'need' => 'Rol &ge; Colaborador',   'col' => 'var(--h6)',        'rank' => 1),
      'moderador'     => array('lbl' => 'Moderador',     'need' => 'Rol &ge; Moderador',     'col' => 'var(--ember-hi)',  'rank' => 2),
      'administrador' => array('lbl' => 'Administrador', 'need' => 'Rol &ge; Administrador', 'col' => 'var(--crack)',     'rank' => 3),
      'webmaster'     => array('lbl' => 'Web Master',    'need' => 'Rol Web Master',         'col' => 'var(--patina)',    'rank' => 4),
      'narrador'      => array('lbl' => 'Narrador',      'need' => 'Rol Narrador',           'col' => 'var(--patina-hi)', 'narr' => true),
  );
  foreach ($grupos as $gkey => $g):
      $puede = !empty($g['narr']) ? ($narrador === 1) : ($rank >= $g['rank']);
      if (!$puede) continue;
      $zonas_grupo = array_filter($zonas, function ($z) use ($gkey) { return $z['grp'] === $gkey; });
      if (empty($zonas_grupo)) continue; // no renderizar grupos sin utilidades
?>
  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl"><?php echo $g['lbl']; ?></span>
      <span class="need" style="background:<?php echo $g['col']; ?>;color:var(--iron)"><?php echo $g['need']; ?></span>
      <span class="rule"></span>
    </div>
    <div class="cards">
<?php foreach ($zonas_grupo as $z): ?>
      <article class="card">
        <div class="card-top">
          <span class="card-ic"><svg viewBox="0 0 24 24"><path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"/></svg></span>
          <div>
            <div class="card-title"><?php echo $z['title']; ?></div>
            <div class="card-code"><?php echo $z['code']; ?></div>
          </div>
          <span class="card-tag" style="background:<?php echo $g['col']; ?>"><?php echo $g['lbl']; ?></span>
<?php if (!empty($z['badge'])): ?>
          <span class="card-count" title="<?php echo (int)$z['badge']; ?> en revisi&oacute;n"><?php echo (int)$z['badge']; ?></span>
<?php endif; ?>
        </div>
        <div class="card-body"><?php echo $z['body']; ?></div>
        <div class="card-foot">
          <span class="card-meta"><?php echo $z['meta']; ?></span>
          <a href="<?php echo $z['href']; ?>" class="btn btn-ghost btn-sm"><?php echo $z['cta']; ?></a>
        </div>
      </article>
<?php endforeach; ?>
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
