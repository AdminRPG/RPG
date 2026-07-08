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

// ── Nivel de staff (plugin iforge_rol, con respaldo directo) ──
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['iforge_staff_level'])) {
        $staff_level = (int) $mybb->user['iforge_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int) $db->fetch_field($cq, 'staff_level');
        }
    }
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string) $mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

$nivel_labels = array(0 => 'Sin rango', 1 => 'Narrador', 2 => 'Moderador', 3 => 'Administrador');
$mi_nivel_lbl = $nivel_labels[min($staff_level, 3)] ?? 'Sin rango';

// ── Contador de fichas pendientes de revisión ──
$pendientes_count = 0;
if ($db->table_exists('rol_personajes')) {
    $pc = $db->simple_select('rol_personajes', 'COUNT(*) as cnt', "estado = 'revision'");
    $pendientes_count = (int)$db->fetch_field($pc, 'cnt');
}

// ── Definición de zonas (min_level, cumulativas) ──
$zonas = array(
    // Nivel 1 · Narrador
    array('lvl' => 1, 'grupo' => 'Narrador', 'code' => 'STF-01', 'color' => 'var(--h6)',
        'title' => 'Aprobaci&oacute;n de expedientes',
        'body'  => 'Revisa las fichas enviadas a revisi&oacute;n, aprueba o rechaza personajes y deja notas al jugador.',
        'meta'  => $pendientes_count . ' pendiente(s)', 'cta' => 'Revisar', 'href' => $bburl . '/revisar-personaje.php'),
    array('lvl' => 1, 'grupo' => 'Narrador', 'code' => 'STF-02', 'color' => 'var(--h6)',
        'title' => 'Gesti&oacute;n de tramas y eventos',
        'body'  => 'Organiza tramas, eventos y misiones del foro. Coordina las tramas narrativas en curso.',
        'meta'  => 'Narrativa', 'cta' => 'Gestionar', 'href' => $bburl . '/calendar.php'),
    array('lvl' => 1, 'grupo' => 'Narrador', 'code' => 'STF-03', 'color' => 'var(--h6)',
        'title' => 'Calendario del rol',
        'body'  => 'Planifica fechas de eventos, cierres de trama y aperturas de temporada en el calendario.',
        'meta'  => 'Agenda', 'cta' => 'Ver calendario', 'href' => $bburl . '/calendar.php'),

    // Nivel 2 · Moderador
    array('lvl' => 2, 'grupo' => 'Moderador', 'code' => 'STF-04', 'color' => 'var(--ember-hi)',
        'title' => 'Cola de moderaci&oacute;n',
        'body'  => 'Aprueba o rechaza mensajes y temas en espera. Gestiona el contenido moderado del foro.',
        'meta'  => 'ModCP', 'cta' => 'Abrir cola', 'href' => $bburl . '/modcp.php'),
    array('lvl' => 2, 'grupo' => 'Moderador', 'code' => 'STF-05', 'color' => 'var(--ember-hi)',
        'title' => 'Reportes',
        'body'  => 'Atiende los reportes de usuarios: mensajes denunciados, conductas y errores de ficha.',
        'meta'  => 'Incidencias', 'cta' => 'Ver reportes', 'href' => $bburl . '/modcp.php?action=reports'),
    array('lvl' => 2, 'grupo' => 'Moderador', 'code' => 'STF-06', 'color' => 'var(--ember-hi)',
        'title' => 'Gesti&oacute;n de temas y foros',
        'body'  => 'Mueve, fusiona, cierra o destaca temas. Mantiene el orden de los foros.',
        'meta'  => 'Herramientas', 'cta' => 'Gestionar', 'href' => $bburl . '/modcp.php?action=modqueue'),

    // Nivel 3 · Administrador
    array('lvl' => 3, 'grupo' => 'Administrador', 'code' => 'STF-07', 'color' => 'var(--crack)',
        'title' => 'Configuraci&oacute;n del foro',
        'body'  => 'Ajustes globales del foro: nombre, opciones, temas y plantillas del foro.',
        'meta'  => 'Admin CP', 'cta' => 'Configurar', 'href' => $bburl . '/admin/'),
    array('lvl' => 3, 'grupo' => 'Administrador', 'code' => 'STF-08', 'color' => 'var(--crack)',
        'title' => 'Usuarios y rangos de staff',
        'body'  => 'Gestiona cuentas, grupos y niveles de staff (narrador, moderador, administrador).',
        'meta'  => 'Usuarios', 'cta' => 'Administrar', 'href' => $bburl . '/admin/index.php?module=user-users'),
    array('lvl' => 3, 'grupo' => 'Administrador', 'code' => 'STF-09', 'color' => 'var(--crack)',
        'title' => 'Econom&iacute;a global',
        'body'  => 'Supervisa la econom&iacute;a del foro: divisas, ajustes de saldo y transacciones globales.',
        'meta'  => 'Tesorer&iacute;a', 'cta' => 'Supervisar', 'href' => $bburl . '/tramites.php'),
);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Zona Staff</title>
<?php echo iforge_rol_head_base(); ?>
<style>
/* Estilos de esta página — base global (:root, body, fondo) en docs/themes/iforge.css */

/* ---------------- BREADCRUMB ---------------- */
.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1300px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

/* ---------------- BOTONES ---------------- */
.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:12px 20px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{background:var(--ember-hi);color:var(--iron);transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:7px 13px;font-size:.7rem}

/* ---------------- PLACAS / HEADER ---------------- */
.plate{border:2px solid #000;background:var(--iron-plate);margin-bottom:12px}
.plate-h{background:var(--iron-edge);padding:9px 13px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:2px solid #000}
.plate-h .t{font-family:var(--disp);font-weight:800;font-size:1.1rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.plate-h .c{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.plate-b{padding:20px 22px;text-align:center}
.shead{display:flex;align-items:baseline;gap:14px;margin:8px 0 14px}
.shead h1,.shead h2{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* ---------------- INTRO / NIVEL ---------------- */
.zs-intro{font-size:.92rem;color:var(--paper-dim);max-width:72ch;margin:-6px 0 16px;line-height:1.55}
.zs-intro b{color:var(--paper);font-weight:600}
.zs-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.zs-level{font-family:var(--mono);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--paper-dim)}
.zs-level b{color:var(--iron);background:var(--ember);padding:3px 10px;border:2px solid #000;margin-left:6px}

/* ---------------- GRUPOS DE ZONAS ---------------- */
.zs-group{margin-bottom:26px}
.zs-group-h{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.zs-group-h .lbl{font-family:var(--disp);font-weight:800;font-size:1.3rem;text-transform:uppercase;color:var(--paper);letter-spacing:.5px}
.zs-group-h .need{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--iron);padding:2px 8px;border:2px solid #000}
.zs-group-h .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

/* ---------------- TARJETAS ---------------- */
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.card{border:2px solid #000;background:var(--iron-plate);display:flex;flex-direction:column;transition:transform .16s,box-shadow .16s;position:relative}
.card:hover{transform:translate(-3px,-3px);box-shadow:6px 6px 0 #000}
.card-top{position:relative;padding:16px;border-bottom:2px solid #000;display:flex;align-items:center;gap:13px;background:linear-gradient(150deg,var(--iron-hi),var(--iron-edge))}
.card-ic{width:52px;height:52px;flex:0 0 auto;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.card-ic svg{width:26px;height:26px;stroke:var(--h6);fill:none;stroke-width:2}
.card-title{font-family:var(--disp);font-weight:800;font-size:1.35rem;text-transform:uppercase;line-height:.98;color:var(--paper)}
.card-code{font-family:var(--mono);font-size:.6rem;font-weight:700;color:var(--ember-hi);text-transform:uppercase;letter-spacing:1px;margin-top:3px}
.card-body{padding:14px 16px;flex:1;font-size:.86rem;color:var(--paper-dim);line-height:1.5}
.card-foot{padding:12px 16px;border-top:2px solid #000;display:flex;align-items:center;justify-content:space-between;gap:10px}
.card-meta{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase}
.card-tag{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:2px 8px;border:2px solid #000;color:var(--iron);position:absolute;top:0;right:0;border-left:2px solid #000;border-bottom:2px solid #000}

/* ---------------- SIN PERMISO ---------------- */
.noperm{border:2px dashed var(--crack);background:var(--iron-plate);padding:40px 22px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:14px}
.noperm .lock{width:72px;height:72px;background:var(--iron);border:2px solid #000;display:flex;align-items:center;justify-content:center}
.noperm .lock svg{width:36px;height:36px;stroke:var(--crack);fill:none;stroke-width:2}
.noperm .big{font-family:var(--disp);font-weight:800;font-size:1.9rem;text-transform:uppercase;color:var(--paper);line-height:1}
.noperm p{font-family:var(--mono);font-size:.76rem;color:var(--paper-dim);line-height:1.6;max-width:54ch}

/* ---------------- FOOTER ---------------- */
.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
.foot-links{display:flex;gap:16px;flex-wrap:wrap}
.foot-links a{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--paper-dim)}
.foot-links a:hover{color:var(--ember-hi)}
.foot-c{font-family:var(--mono);font-size:.62rem;color:var(--ash)}

.reveal{opacity:0;transform:translateY(14px);transition:opacity .5s,transform .5s}
.reveal.vis{opacity:1;transform:none}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important}.reveal{opacity:1;transform:none}}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

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

<?php if ($staff_level < 1): ?>
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
    <p class="zs-intro">Panel de <b>administraci&oacute;n del foro</b>. Solo ves las zonas que desbloquea tu rango; los permisos son <b>acumulativos</b> (un administrador ve todo lo de narrador y moderador).</p>
<?php if ($pendientes_count > 0): ?>
    <div style="margin-bottom:14px;padding:12px 16px;border:2px solid var(--ember);background:var(--iron-plate);display:flex;align-items:center;justify-content:space-between;gap:12px">
      <span style="font-family:var(--mono);font-size:.68rem;color:var(--ember-hi)"><b style="color:var(--ember)"><?php echo $pendientes_count; ?></b> expediente(s) pendiente(s) de revisi&oacute;n</span>
      <a href="<?php echo $bburl; ?>/revisar-personaje.php" class="btn btn-hot btn-sm">Revisar ahora</a>
    </div>
<?php endif; ?>
    <div class="zs-bar">
      <span class="zs-level">Tu rango de staff: <b><?php echo $mi_nivel_lbl; ?></b></span>
    </div>
  </section>

<?php
  $grupos = array(
      1 => array('lbl' => 'Narrador',      'need' => 'Nivel &ge; 1', 'col' => 'var(--h6)'),
      2 => array('lbl' => 'Moderador',     'need' => 'Nivel &ge; 2', 'col' => 'var(--ember-hi)'),
      3 => array('lbl' => 'Administrador', 'need' => 'Nivel &ge; 3', 'col' => 'var(--crack)'),
  );
  foreach ($grupos as $glvl => $g):
      if ($staff_level < $glvl) continue;
      $zonas_grupo = array_filter($zonas, function ($z) use ($glvl) { return $z['lvl'] === $glvl; });
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
          <span class="card-tag" style="background:<?php echo $z['color']; ?>"><?php echo $g['lbl']; ?></span>
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
