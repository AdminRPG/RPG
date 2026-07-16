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
    ? gbe_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank      = (int) $staff['rank'];
$narrador  = (int) $staff['narrador'];
$is_staff  = !empty($staff['is_staff']);
$rol_lbl   = gbe_rol_staff_label($staff['rol']);
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
// Contadores adicionales
$npc_sin_asignar = 0;
if ($db->table_exists('rol_personajes') && $db->field_exists('es_npc', 'rol_personajes')) {
    $nq = $db->simple_select('rol_personajes', 'COUNT(*) AS c', "es_npc = 1 AND uid = 0");
    $npc_sin_asignar = (int) $db->fetch_field($nq, 'c');
}

$total_personajes = 0;
if ($db->table_exists('rol_personajes')) {
    $tq = $db->simple_select('rol_personajes', 'COUNT(*) AS c');
    $total_personajes = (int) $db->fetch_field($tq, 'c');
}

$cartas_biblioteca = 0;
if ($db->table_exists('rol_cartas')) {
    $cq = $db->simple_select('rol_cartas', 'COUNT(*) AS c');
    $cartas_biblioteca = (int) $db->fetch_field($cq, 'c');
}
$cartas_asignadas = 0;
if ($db->table_exists('rol_tecnicas')) {
    $cq = $db->simple_select('rol_tecnicas', 'COUNT(*) AS c');
    $cartas_asignadas = (int) $db->fetch_field($cq, 'c');
}

// Contadores Mundo Vivo (para badges de las cards).
$mv_eventos_pend = 0;
$mv_noticias_activas = 0;
if ($db->table_exists('rol_mv_eventos')) {
    $mv_ciclo = gbe_rol_mv_ciclo_actual();
    if (is_array($mv_ciclo)) {
        $mv_eventos_pend = (int) $db->fetch_field($db->simple_select('rol_mv_eventos', 'COUNT(*) c', "ciclo_id = " . (int)$mv_ciclo['ciclo_id'] . " AND estado = 'pendiente'"), 'c');
    }
}
if ($db->table_exists('rol_mv_noticias')) {
    $mv_noticias_activas = (int) $db->fetch_field($db->simple_select('rol_mv_noticias', 'COUNT(*) c', 'activa = 1'), 'c');
}

$npc_sec_count = 0;
if ($db->table_exists('rol_npcs_secundarios')) {
    $npc_sec_count = (int) $db->fetch_field($db->simple_select('rol_npcs_secundarios', 'COUNT(*) c'), 'c');
}

$acomp_sol_pend = function_exists('gbe_rol_acompanante_solicitudes_pend_count')
    ? gbe_rol_acompanante_solicitudes_pend_count()
    : 0;

$trip_tramites_pend = 0;
if ($db->table_exists('rol_tramites')) {
    $trip_tramites_pend = (int) $db->fetch_field(
        $db->simple_select('rol_tramites', 'COUNT(*) c', "estado = 'pendiente' AND tipo IN ('fundar_tripulacion','unirse_tripulacion')"),
        'c'
    );
}

// Contador de anotaciones del calendario on-rol
$cal_notas = 0;
if ($db->table_exists('rol_calendario')) {
    $cal_notas = (int) $db->fetch_field($db->simple_select('rol_calendario', 'COUNT(*) c'), 'c');
}

$zonas = array(
    array('grp' => 'colaborador', 'code' => 'STF-01',
        'title' => 'Gesti&oacute;n de expedientes',
        'body'  => 'Revisa, aprueba, rechaza, devuelve a revisi&oacute;n o elimina fichas. Gesti&oacute;n completa del ciclo de vida de cada expediente.',
        'meta'  => $pendientes_count . ' pendiente(s)', 'cta' => 'Revisar', 'badge' => $pendientes_count, 'href' => $bburl . '/revisar-personaje.php',
        'icon'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M9 15h6"/><path d="M9 11h6"/><path d="M9 19h3"/>'),
    array('grp' => 'colaborador', 'code' => 'STF-13',
        'title' => 'Calendario on-rol',
        'body'  => 'A&ntilde;ade y edita anotaciones en los d&iacute;as del calendario del mundo (4 estaciones &times; 65 d&iacute;as). Las anotaciones son visibles para todos los jugadores en el calendario p&uacute;blico.',
        'meta'  => $cal_notas . ' anotaci&oacute;n(es)', 'cta' => 'Editar calendario', 'badge' => 0, 'href' => $bburl . '/gestionar-calendario.php',
        'icon'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/>'),
    array('grp' => 'webmaster', 'code' => 'STF-02',
        'title' => 'Gesti&oacute;n de staff',
        'body'  => 'Asigna el rol (Colaborador, Moderador, Administrador, Web Master) y el a&ntilde;adido de Narrador a cada personaje, y consulta los permisos de cada rol.',
        'meta'  => $staff_count . ' con rol', 'cta' => 'Gestionar', 'badge' => $staff_count, 'href' => $bburl . '/gestionar-staff.php',
        'icon'  => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"/>'),
    array('grp' => 'administrador', 'code' => 'STF-03',
        'title' => 'Crear NPC',
        'body'  => 'Crea personajes no jugadores (NPC) con el mismo wizard de creaci&oacute;n. Los NPC no pertenecen a ninguna cuenta hasta que se asignan a un Narrador.',
        'meta'  => $npc_sin_asignar . ' sin asignar', 'cta' => 'Crear NPC', 'badge' => 0, 'href' => $bburl . '/crear-npc.php',
        'icon'  => '<circle cx="12" cy="8" r="4"/><path d="M4 20v-1a6 6 0 0 1 6-6h4"/><path d="M22 14l-4 4-2-2"/>'),
    array('grp' => 'administrador', 'code' => 'STF-04',
        'title' => 'Gestionar personaje',
        'body'  => 'Edita toda la informaci&oacute;n on-rol de cualquier personaje: stats, virtudes, defectos, inventario, econom&iacute;a, bio, facci&oacute;n y m&aacute;s.',
        'meta'  => $total_personajes . ' personajes totales', 'cta' => 'Gestionar', 'badge' => 0, 'href' => $bburl . '/gestionar-personaje.php',
        'icon'  => '<path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>'),
    array('grp' => 'administrador', 'code' => 'STF-05',
        'title' => 'Gestionar NPC',
        'body'  => 'Asigna NPCs a cuentas con personaje Narrador para que puedan postear como ellos. Los NPCs asignados aparecer&aacute;n en Personaje con un toggle.',
        'meta'  => '', 'cta' => 'Asignar', 'badge' => 0, 'href' => $bburl . '/gestionar-npc.php',
        'icon'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
    array('grp' => 'administrador', 'code' => 'STF-06',
        'title' => 'Crear cartas',
        'body'  => 'Forja cartas de t&eacute;cnica (INI-03) para la biblioteca com&uacute;n, sin asociarlas a nadie: 6 categor&iacute;as de tags, tier, presupuesto y vista previa en vivo. Incluye ayuda IA con prompt y autorrelleno por YAML.',
        'meta'  => $cartas_biblioteca . ' en biblioteca', 'cta' => 'Crear cartas', 'badge' => 0, 'href' => $bburl . '/crear-cartas.php',
        'icon'  => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8"/><path d="M8 12h8"/>'),
    array('grp' => 'administrador', 'code' => 'STF-07',
        'title' => 'Asignar cartas',
        'body'  => 'Asocia cartas ya creadas al deck de cualquier personaje. Al asignar se copia la carta al deck (con su insignia propia). Gestiona el deck: retirar cartas o marcar la T&eacute;cnica Insignia.',
        'meta'  => $cartas_asignadas . ' asignada(s)', 'cta' => 'Asignar', 'badge' => 0, 'href' => $bburl . '/asignar-cartas.php',
        'icon'  => '<path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><rect x="9" y="2" width="10" height="20" rx="2"/><path d="M13 7h2"/><path d="M13 11h2"/>'),
    array('grp' => 'administrador', 'code' => 'STF-14',
        'title' => 'NPCs Secundarios',
        'body'  => 'Crea fichas simplificadas de NPCs secundarios con nombre, imagen, descripci&oacute;n y t&eacute;cnicas representativas. Cada carta muestra el dise&ntilde;o en vivo mientras la editas, con imagen a la izquierda y datos a la derecha.',
        'meta'  => $npc_sec_count . ' en biblioteca', 'cta' => 'Crear NPC', 'badge' => 0, 'href' => $bburl . '/crear-npc-secundario.php',
        'icon'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
    array('grp' => 'colaborador', 'code' => 'STF-15',
        'title' => 'Solicitudes de acompa&ntilde;ante',
        'body'  => 'Aprueba o rechaza las solicitudes de los jugadores para asociar un <b>NPC secundario</b> como acompa&ntilde;ante. Al aprobar, el NPC se asigna a un slot libre del personaje (m&aacute;x. 2).',
        'meta'  => $acomp_sol_pend . ' pendiente(s)', 'cta' => 'Revisar', 'badge' => $acomp_sol_pend, 'href' => $bburl . '/gestionar-acompanantes.php',
        'icon'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'),
    array('grp' => 'webmaster', 'code' => 'STF-08',
        'title' => 'Mundo Vivo',
        'body'  => 'Centro de la Balanza: eventos notificados, misiones del mes, tablero de zonas/facciones/tensi&oacute;n, NPCs y sus ubicaciones. Genera el super-prompt para la IA, ingiere el resultado y publica el nuevo estado del mundo y el peri&oacute;dico <b>Eternal News</b>.',
        'meta'  => $mv_eventos_pend . ' evento(s) por revisar', 'cta' => 'Abrir panel', 'badge' => $mv_eventos_pend, 'href' => $bburl . '/mundo-vivo.php',
        'icon'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18"/>'),
    array('grp' => 'administrador', 'code' => 'STF-09',
        'title' => 'Noticias',
        'body'  => 'Gestiona el feed de <b>&Uacute;ltimas noticias</b> de la portada: crea entradas manuales, edita las existentes, act&iacute;valas o desact&iacute;valas de la rotaci&oacute;n y ordena su prioridad. Las noticias de Mundo Vivo se a&ntilde;aden autom&aacute;ticamente.',
        'meta'  => $mv_noticias_activas . ' en rotaci&oacute;n', 'cta' => 'Gestionar', 'badge' => 0, 'href' => $bburl . '/gestionar-noticias.php',
        'icon'  => '<path d="M4 4h16v14a2 2 0 0 1-2 2H4Z"/><path d="M4 20a2 2 0 0 1-2-2V8"/><path d="M8 8h8"/><path d="M8 12h8"/><path d="M8 16h5"/>'),
    array('grp' => 'administrador', 'code' => 'STF-10',
        'title' => 'Gestionar misiones',
        'body'  => 'Administra las misiones del <b>Tabl&oacute;n</b>: crea, edita, activa, desactiva o elimina misiones del ciclo actual. Controla estado, rango, peligrosidad, recompensa y facciones implicadas.',
        'meta'  => '', 'cta' => 'Ir al panel', 'badge' => 0, 'href' => $bburl . '/gestionar-misiones.php',
        'icon'  => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"/><path d="M12 8v4l2 2"/>'),
    array('grp' => 'administrador', 'code' => 'STF-11',
        'title' => 'Gestionar cat&aacute;logos',
        'body'  => 'Administra en un solo sitio todos los cat&aacute;logos del foro: la <b>Tienda</b> (Bazar), las <b>Tripulaciones</b> y las bibliotecas de <b>Pactos Primarios</b>, <b>Bestiario</b> y <b>Estilos</b>. Crea, edita, muestra u oculta cada entrada.',
        'meta'  => '', 'cta' => 'Gestionar', 'badge' => 0, 'href' => $bburl . '/gestionar-catalogos.php',
        'icon'  => '<path d="M4 4h7v7H4Z"/><path d="M13 4h7v7h-7Z"/><path d="M4 13h7v7H4Z"/><path d="M13 13h7v7h-7Z"/>'),
    array('grp' => 'administrador', 'code' => 'STF-12',
        'title' => 'Tr&aacute;mites de tripulaci&oacute;n',
        'body'  => 'Aprueba o rechaza solicitudes de jugadores para <b>fundar</b> una nueva tripulaci&oacute;n o <b>unirse</b> a una existente. Al aprobar se actualiza el cat&aacute;logo y la membres&iacute;a del personaje.',
        'meta'  => $trip_tramites_pend . ' pendiente(s)', 'cta' => 'Revisar', 'badge' => $trip_tramites_pend, 'href' => $bburl . '/gestionar-tramites-tripulacion.php',
        'icon'  => '<path d="M3 18l9-14 9 14"/><path d="M3 18h18"/><circle cx="18" cy="6" r="3" fill="currentColor"/>'),
);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Zona Staff</title>
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-zona-staff) -->
</head>
<body class="gbe-pg-zona-staff">

<?php echo gbe_rol_navbar_html(); ?>

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
          <span class="card-ic"><svg viewBox="0 0 24 24"><?php echo isset($z['icon']) ? $z['icon'] : '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6Z"/>'; ?></svg></span>
          <div>
            <div class="card-title"><?php echo $z['title']; ?></div>
            <div class="card-code"><?php echo $z['code']; ?></div>
          </div>
          <span class="card-tag" style="background:<?php echo $g['col']; ?>"><?php echo $g['lbl']; ?></span>
<?php if (!empty($z['badge']) && (int)$z['badge'] > 0): ?>
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
