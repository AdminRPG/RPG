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

// Las alertas son POR PERSONAJE: se muestran las del personaje activo.
$activePid = (int)($mybb->user['ope_active_pid'] ?? 0);
if ($activePid <= 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $aq0 = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($aq0)) $activePid = (int)$db->fetch_field($aq0, 'pid');
}

// POST: marcar leídas / borrar (siempre acotado al personaje activo)
if ($loggedin && $activePid > 0 && $mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'), true) && $db->table_exists('rol_alertas')) {
    // Marcar como leídas
    if ($mybb->get_input('mark_all')) {
        $db->update_query('rol_alertas', array('leido' => 1), "pid = {$activePid}");
    }
    if (($aid = (int)$mybb->get_input('mark_one', MyBB::INPUT_INT)) > 0) {
        $db->update_query('rol_alertas', array('leido' => 1), "aid = {$aid} AND pid = {$activePid}");
    }
    // Borrar
    if ($mybb->get_input('delete_all')) {
        $db->delete_query('rol_alertas', "pid = {$activePid}");
    }
    if (($del = (int)$mybb->get_input('delete_one', MyBB::INPUT_INT)) > 0) {
        $db->delete_query('rol_alertas', "aid = {$del} AND pid = {$activePid}");
    }
    if ($mybb->get_input('delete_sel')) {
        $sel = (array)$mybb->get_input('sel', MyBB::INPUT_ARRAY);
        $ids = array();
        foreach ($sel as $s) { $s = (int)$s; if ($s > 0) $ids[] = $s; }
        if (!empty($ids)) {
            $db->delete_query('rol_alertas', "pid = {$activePid} AND aid IN (".implode(',', $ids).")");
        }
    }
    // PRG: evita reenvíos al recargar
    header('Location: ' . $mybb->settings['bburl'] . '/alertas.php');
    exit;
}

// Cargar alertas del personaje activo
$alertas = array();
if ($loggedin && $activePid > 0 && $db->table_exists('rol_alertas')) {
    $aq = $db->simple_select('rol_alertas', '*', "pid = {$activePid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 50));
    while ($row = $db->fetch_array($aq)) $alertas[] = $row;
}

require_once MYBB_ROOT . 'inc/ope_user_init.php';

$initials   = ope_get_initials($mybb->user['username'] ?? '');
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
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-alertas) -->
</head>
<body class="ope-pg-alertas">

<?php echo ope_rol_navbar_html(); ?>

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

  <?php if ($loggedin && $activePid <= 0): ?>
    <div class="empty-state">
      <div class="big">Sin personaje activo</div>
      <p>Las alertas son por personaje. Activa un personaje en <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a> para ver sus notificaciones.</p>
    </div>
  <?php elseif (empty($alertas)): ?>
    <div class="empty-state">
      <div class="big">No tienes alertas</div>
      <p>Aquí aparecerán mensajes nuevos, aprobaciones y otras notificaciones de este personaje.</p>
    </div>
  <?php else: ?>
    <form method="post" id="alert-form">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
    <div class="bar">
      <label class="al-selall"><input type="checkbox" id="al-checkall"> <span>Seleccionar todo</span></label>
      <span class="count"><?php echo count($alertas); ?> alerta(s)</span>
      <span class="al-bar-sp"></span>
      <button type="submit" name="mark_all" value="1" class="btn btn-ghost btn-sm">Marcar leídas</button>
      <button type="submit" name="delete_sel" value="1" class="btn btn-ghost btn-sm" onclick="return alConfirmSel(this)">Borrar seleccionadas</button>
      <button type="submit" name="delete_all" value="1" class="btn btn-danger btn-sm" onclick="return confirm('¿Borrar TODAS las alertas de este personaje? Esta acción no se puede deshacer.')">Borrar todas</button>
    </div>

    <div class="alert-list">
      <?php foreach ($alertas as $al): 
        $icon = $tipo_iconos[$al['tipo']] ?? '●';
        $color = $tipo_colores[$al['tipo']] ?? 'var(--rivet)';
        $unread = !(int)$al['leido'];
      ?>
        <div class="alert-item<?php echo $unread ? ' unread' : ''; ?>">
          <label class="al-check"><input type="checkbox" class="al-cb" name="sel[]" value="<?php echo (int)$al['aid']; ?>"></label>
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
              <button type="submit" name="mark_one" value="<?php echo (int)$al['aid']; ?>" class="btn btn-ghost btn-sm al-icon-btn" title="Marcar como leída">✓</button>
            <?php endif; ?>
            <button type="submit" name="delete_one" value="<?php echo (int)$al['aid']; ?>" class="btn btn-danger btn-sm al-icon-btn" title="Borrar" onclick="return confirm('¿Borrar esta alerta?')">✕</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    </form>
    <script>
    (function(){
      var all=document.getElementById('al-checkall');
      var boxes=[].slice.call(document.querySelectorAll('.al-cb'));
      if(all){all.addEventListener('change',function(){boxes.forEach(function(b){b.checked=all.checked;});});}
      window.alConfirmSel=function(){
        var n=boxes.filter(function(b){return b.checked;}).length;
        if(n===0){alert('Selecciona al menos una alerta.');return false;}
        return confirm('¿Borrar '+n+' alerta(s) seleccionada(s)?');
      };
    })();
    </script>
  <?php endif; ?>
</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
