<?php
/**
 * I-Forge · Alertas
 * Centro de notificaciones: mensajes, aprobaciones, rechazos, moderaciones.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'alertas.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// POST: marcar leídas
if ($loggedin && $mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'), true)) {
    if ($mybb->get_input('mark_all') && $db->table_exists('rol_alertas')) {
        $db->update_query('rol_alertas', array('leido' => 1), "uid = {$uid}");
    }
    if (($aid = (int)($mybb->get_input('mark_one', MyBB::INPUT_INT))) > 0 && $db->table_exists('rol_alertas')) {
        $db->update_query('rol_alertas', array('leido' => 1), "aid = {$aid} AND uid = {$uid}");
    }
}

// Cargar alertas
$alertas = array();
if ($loggedin && $db->table_exists('rol_alertas')) {
    $aq = $db->simple_select('rol_alertas', '*', "uid = {$uid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 50));
    while ($row = $db->fetch_array($aq)) $alertas[] = $row;
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) { if ($p !== '') $initials .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'); }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$initials_e = htmlspecialchars_uni($initials);

$tipo_iconos = [
    'mensaje_nuevo'        => '✉',
    'personaje_aprobado'   => '✓',
    'personaje_rechazado'  => '✕',
    'personaje_moderado'   => '↻',
    'staff_asignado'       => '⚑',
];
$tipo_colores = [
    'mensaje_nuevo'        => 'var(--patina)',
    'personaje_aprobado'   => 'var(--patina-hi)',
    'personaje_rechazado'  => 'var(--crack)',
    'personaje_moderado'   => 'var(--h6)',
    'staff_asignado'       => 'var(--ember)',
];

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Alertas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e; --paper:#eaf4fb; --paper-dim:#a9c6e0; --ash:#5c83a7;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --crack:#e63b2e;
  --h6:#FFCB93;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
a{color:var(--ember-hi);text-decoration:none}
.wrap{max-width:900px;margin:0 auto;padding:0 18px}

.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:900px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

.btn{font-family:var(--mono);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:8px 16px;border:2px solid #000;cursor:pointer;display:inline-block}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:5px 10px;font-size:.64rem}

.alert-list{border:2px solid #000}
.alert-item{display:flex;align-items:flex-start;gap:14px;padding:13px 16px;border-bottom:1px solid var(--iron-hi);background:var(--iron-plate);transition:background .12s}
.alert-item:last-child{border-bottom:none}
.alert-item:hover{background:var(--iron-hi)}
.alert-item.unread{border-left:4px solid var(--ember)}
.alert-item .al-icon{width:36px;height:36px;flex:0 0 auto;display:flex;align-items:center;justify-content:center;font-size:1.1rem;border:2px solid #000}
.alert-item .al-body{flex:1;min-width:0}
.alert-item .al-title{font-weight:600;font-size:.88rem;color:var(--paper);margin-bottom:3px}
.alert-item .al-text{font-size:.78rem;color:var(--paper-dim);line-height:1.45}
.alert-item .al-time{font-family:var(--mono);font-size:.58rem;color:var(--ash);margin-top:4px}
.alert-item .al-action{flex:0 0 auto}

.empty-state{text-align:center;padding:48px 20px;color:var(--paper-dim)}
.empty-state .big{font-family:var(--disp);font-weight:800;font-size:1.6rem;text-transform:uppercase;color:var(--paper);margin-bottom:8px}
.empty-state p{font-family:var(--mono);font-size:.72rem}

.bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.bar .count{font-family:var(--mono);font-size:.62rem;color:var(--ash);text-transform:uppercase}

.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <b>Alertas</b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Alertas</h1>
    <span class="code">// centro de notificaciones</span>
    <span class="rule"></span>
  </div>

  <?php if (empty($alertas)): ?>
    <div class="empty-state">
      <div class="big">No tienes alertas</div>
      <p>Aquí aparecerán mensajes nuevos, aprobaciones y otras notificaciones.</p>
    </div>
  <?php else: ?>
    <div class="bar">
      <span class="count"><?php echo count($alertas); ?> alerta(s)</span>
      <form method="post" style="display:inline">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <button type="submit" name="mark_all" value="1" class="btn btn-ghost btn-sm">Marcar todas como leídas</button>
      </form>
    </div>

    <div class="alert-list">
      <?php foreach ($alertas as $al): 
        $icon = $tipo_iconos[$al['tipo']] ?? '●';
        $color = $tipo_colores[$al['tipo']] ?? 'var(--rivet)';
        $unread = !(int)$al['leido'];
      ?>
        <div class="alert-item<?php echo $unread ? ' unread' : ''; ?>">
          <div class="al-icon" style="color:<?php echo $color; ?>"><?php echo $icon; ?></div>
          <div class="al-body">
            <div class="al-title"><?php echo htmlspecialchars_uni($al['titulo']); ?></div>
            <div class="al-text"><?php echo htmlspecialchars_uni($al['cuerpo']); ?></div>
            <div class="al-time"><?php echo date('d/m/Y H:i', (int)$al['dateline']); ?></div>
          </div>
          <div class="al-action">
            <?php if (!empty($al['link'])): ?>
              <a href="<?php echo htmlspecialchars_uni($al['link']); ?>" class="btn btn-ghost btn-sm">Ver</a>
            <?php endif; ?>
            <?php if ($unread): ?>
              <form method="post" style="display:inline;margin-left:4px">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <button type="submit" name="mark_one" value="<?php echo (int)$al['aid']; ?>" class="btn btn-ghost btn-sm" style="font-size:.56rem;padding:4px 7px">✓</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
