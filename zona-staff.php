<?php
/**
 * One Piece: Eternal · Zona Staff (hub)
 * Cards agrupadas por rank mínimo → Acceder a panel PHP.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_tramites.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

// Compat: enlaces antiguos ?pid= → panel de aprobación
$view_pid = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
if ($view_pid > 0) {
    header('Location: ' . $mybb->settings['bburl'] . '/zona-staff-aprobacion.php?pid=' . $view_pid);
    exit;
}

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);
$rol_lbl    = ope_rol_staff_label($staff['rol']);
$char_name  = htmlspecialchars_uni((string) $staff['nombre']);
$mi_rango   = $rol_lbl !== '' ? $rol_lbl : 'Sin rango';

$rank_labels = array(
    1 => 'Colaborador',
    2 => 'Moderador',
    3 => 'Administrador',
    4 => 'Web Master',
);

$paneles = array(
    array(
        'id' => 'aprobacion',
        'titulo' => 'Aprobación de personajes',
        'code' => 'STF-01',
        'desc' => 'Cola de fichas en revisión. Aprobar otorga 1 PT y activa Eternal interactivo.',
        'rank_min' => 1,
        'href' => 'zona-staff-aprobacion.php',
        'count_key' => 'cola_pj',
        'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>',
    ),
    array(
        'id' => 'tramites',
        'titulo' => 'Cola de trámites',
        'code' => 'STF-02',
        'desc' => 'Solicitudes de ventanillas. Ver desde Colaborador; resolver según rank del tipo.',
        'rank_min' => 1,
        'href' => 'zona-staff-tramites.php',
        'count_key' => 'cola_tram',
        'icon_svg' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>',
    ),
);

$counts = array('cola_pj' => 0, 'cola_tram' => 0);
if ($is_staff && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', 'COUNT(*) AS c', "estado = 'revision'");
    $counts['cola_pj'] = (int) $db->fetch_field($q, 'c');
}
if ($is_staff && $staff_rank >= 1 && $db->table_exists('rol_tramites')) {
    $q = $db->simple_select(
        'rol_tramites',
        'COUNT(*) AS c',
        "estado IN ('pendiente','en_proceso') AND tipo != 'crear_personaje'"
    );
    $counts['cola_tram'] = (int) $db->fetch_field($q, 'c');
}

$by_rank = array();
foreach ($paneles as $p) {
    if ($staff_rank < (int) $p['rank_min'] && $staff_rank < 4) {
        continue;
    }
    $by_rank[(int) $p['rank_min']][] = $p;
}
ksort($by_rank);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Zona Staff</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Zona Staff</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Zona Staff</h1>
      <span class="code">// paneles</span>
      <span class="rule"></span>
    </div>
  </section>
<?php if (!$is_staff): ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Acceso restringido</span><span class="c">// solo staff</span></div>
      <div class="plate-b">
        <div class="noperm">
          <div class="lock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div class="big">No tienes acceso a la Zona Staff</div>
          <p>Reservado al equipo del foro. La cuenta Admin MyBB tiene acceso aunque no tenga personaje activo.</p>
          <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="reveal">
    <p class="zs-intro">Herramientas del equipo. Cada panel exige un <b>rank mínimo</b>; solo ves las cards a las que puedes acceder.</p>
    <div class="zs-bar">
      <span class="zs-level">Activo: <b><?php echo $char_name !== '' ? $char_name : 'Admin (sin PJ)'; ?></b> · rol: <b><?php echo htmlspecialchars_uni($mi_rango); ?></b> · rank <?php echo (int) $staff_rank; ?></span>
    </div>
  </section>

<?php if (empty($by_rank)): ?>
  <section class="reveal">
    <div class="empty-state">
      <div class="big">Sin paneles disponibles</div>
      <p>Tu rank actual no abre ninguna herramienta.</p>
    </div>
  </section>
<?php else: ?>
<?php foreach ($by_rank as $need => $list): ?>
  <section class="reveal zs-group">
    <div class="zs-group-h">
      <span class="lbl">Rank ≥ <?php echo (int) $need; ?></span>
      <span class="need"><?php echo htmlspecialchars_uni($rank_labels[$need] ?? ('Rank ' . $need)); ?>+</span>
      <span class="rule"></span>
    </div>
    <div class="cards">
<?php foreach ($list as $p):
    $ck = (string) ($p['count_key'] ?? '');
    $cnt = ($ck !== '' && isset($counts[$ck])) ? (int) $counts[$ck] : null;
    $svg = $p['icon_svg'] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
?>
      <article class="card">
        <div class="card-top">
          <div class="card-ic">
            <?php echo $svg; ?>
          </div>
          <div class="card-head">
            <div class="card-title"><?php echo htmlspecialchars_uni($p['titulo']); ?></div>
            <div class="card-code"><?php echo htmlspecialchars_uni($p['code']); ?></div>
          </div>
<?php if ($cnt !== null): ?>
          <span class="card-count" title="En cola"><?php echo $cnt; ?></span>
<?php endif; ?>
        </div>
        <div class="card-body"><?php echo htmlspecialchars_uni($p['desc']); ?></div>
        <div class="card-foot">
          <span class="card-meta">Mín. <?php echo htmlspecialchars_uni($rank_labels[$need] ?? ('rank ' . $need)); ?></span>
          <a class="btn btn-hot btn-sm" href="<?php echo $bburl; ?>/<?php echo htmlspecialchars_uni($p['href']); ?>">Acceder</a>
        </div>
      </article>
<?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
<?php endif; ?>
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
