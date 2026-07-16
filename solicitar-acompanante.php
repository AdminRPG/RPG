<?php
/**
 * I-Forge · Trámite "Solicitar acompañante"
 * El jugador SOLICITA un NPC secundario para su personaje activo (máx. 2). La
 * solicitud queda pendiente hasta que el staff la aprueba o rechaza en
 * gestionar-acompanantes.php (Zona Staff).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'solicitar-acompanante.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

$active_pid = 0;
$char_name  = '';
if ($loggedin) {
    $active_pid = function_exists('gbe_rol_active_pid_for') ? gbe_rol_active_pid_for($uid) : (int) ($mybb->user['gbe_active_pid'] ?? 0);
    if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre', "pid = {$active_pid} AND uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($pq)) {
            $char_name = (string) $db->fetch_field($pq, 'nombre');
        } else {
            $active_pid = 0;
        }
    }
}

$flash = '';
$flash_kind = 'ok';
$table_ok = $db->table_exists('rol_npcs_secundarios') && $db->table_exists('rol_acompanantes') && $db->table_exists('rol_acompanante_solicitudes');
$max_slots = function_exists('gbe_rol_acompanantes_max') ? gbe_rol_acompanantes_max() : 2;

// ── POST ──
if ($loggedin && $active_pid > 0 && $mybb->request_method === 'post' && $table_ok) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga e inténtalo de nuevo.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        if ($action === 'solicitar') {
            $npc_id = (int) $mybb->get_input('npc_id', MyBB::INPUT_INT);
            $motivo = (string) $mybb->get_input('motivo');
            $res = gbe_rol_acompanante_solicitar($active_pid, $uid, $npc_id, $motivo);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
            if ($res['ok']) {
                header('Location: ' . $bburl . '/solicitar-acompanante.php?ok=req');
                exit;
            }
        } elseif ($action === 'cancelar') {
            $sid = (int) $mybb->get_input('sid', MyBB::INPUT_INT);
            $res = gbe_rol_acompanante_solicitud_cancelar($sid, $active_pid);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
            if ($res['ok']) {
                header('Location: ' . $bburl . '/solicitar-acompanante.php?ok=cancel');
                exit;
            }
        } elseif ($action === 'quitar') {
            $slot = (int) $mybb->get_input('slot', MyBB::INPUT_INT);
            $res = gbe_rol_acompanante_quitar($active_pid, $slot);
            $flash = $res['msg'];
            $flash_kind = $res['ok'] ? 'ok' : 'warn';
            if ($res['ok']) {
                header('Location: ' . $bburl . '/solicitar-acompanante.php?ok=del');
                exit;
            }
        }
    }
}

$okp = $mybb->get_input('ok');
if ($okp === 'req')         { $flash = 'Solicitud enviada. El staff la revisará pronto.'; $flash_kind = 'ok'; }
elseif ($okp === 'cancel')  { $flash = 'Solicitud cancelada.'; $flash_kind = 'ok'; }
elseif ($okp === 'del')     { $flash = 'Acompañante retirado.'; $flash_kind = 'ok'; }

$mis_acomps   = ($active_pid > 0 && $table_ok) ? gbe_rol_char_acompanantes($active_pid) : array();
$mis_solicit  = ($active_pid > 0 && $table_ok) ? gbe_rol_char_solicitudes_acompanante($active_pid) : array();
$lib          = $table_ok ? gbe_rol_npc_sec_lib('', '') : array();

// Estados usados por el jugador (para bloquear duplicados en el selector).
$bloqueados = array();
foreach ($mis_acomps as $ma)  { $bloqueados[(int) ($ma['npc_id'] ?? 0)] = 'asignado'; }
foreach ($mis_solicit as $ms) {
    if ((string) $ms['estado'] === 'pendiente') {
        $bloqueados[(int) $ms['npc_id']] = 'pendiente';
    }
}
$pend_count = 0;
foreach ($mis_solicit as $ms) { if ((string) $ms['estado'] === 'pendiente') $pend_count++; }
$slots_libres = max(0, $max_slots - count($mis_acomps) - $pend_count);

// Datos de NPC para la vista previa en vivo (JS).
$npc_js = array();
foreach ($lib as $npc) {
    $npc_js[(int) $npc['id']] = array(
        'nombre' => (string) ($npc['nombre'] ?? ''),
        'imagen' => (string) ($npc['imagen'] ?? ''),
        'desc'   => (string) ($npc['descripcion'] ?? ''),
        'tec'    => array_values(array_map(static function ($t) {
            return array(
                'n' => (string) ($t['n'] ?? ''),
                'd' => (string) ($t['d'] ?? ''),
                'e' => (string) ($t['e'] ?? ''),
            );
        }, is_array($npc['tecnicas'] ?? null) ? $npc['tecnicas'] : array())),
    );
}

$estado_lbl = array('pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada');

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Solicitar acompa&ntilde;ante</title>
<?php echo gbe_rol_head_base(); ?>
<?php echo gbe_rol_npc_sec_card_css(); ?>
</head>
<body class="gbe-pg-tramites gbe-pg-solicitar-acomp">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Tr&aacute;mites</a>
    <span class="sep">&#8250;</span>
    <b>Solicitar acompa&ntilde;ante</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Solicitar acompa&ntilde;ante</h1>
      <span class="code">// NPC secundario &middot; m&aacute;x. <?php echo (int) $max_slots; ?></span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Pide un <b>NPC secundario</b> de la biblioteca para tu personaje activo. La solicitud pasa por el <b>staff</b>: cuando la apruebe, el acompa&ntilde;ante se asignar&aacute; a un slot libre y podr&aacute;s usar sus t&eacute;cnicas al postear con el RPG System.</p>
  </section>

<?php if (!$loggedin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-intro m-0">Debes <a href="<?php echo $bburl; ?>/member.php?action=login">iniciar sesi&oacute;n</a> para solicitar acompa&ntilde;antes.</p></div></div></section>
<?php elseif ($active_pid <= 0): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-intro m-0">Necesitas un <b>personaje activo</b> propio para solicitar acompa&ntilde;antes.</p></div></div></section>
<?php elseif (!$table_ok): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-intro m-0">Falta la infraestructura de acompa&ntilde;antes. Ejecuta <code>php scripts/migrate-acompanantes.php</code>.</p></div></div></section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="sa-flash sa-<?php echo htmlspecialchars_uni($flash_kind); ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <div class="sa-grid">

    <!-- Formulario de solicitud -->
    <section class="reveal">
      <div class="plate">
        <div class="plate-h">
          <span class="t">Nueva solicitud</span>
          <span class="c">// <?php echo htmlspecialchars_uni($char_name); ?></span>
        </div>
        <div class="plate-b">
<?php if (empty($lib)): ?>
          <p class="tram-intro m-0">A&uacute;n no hay NPCs secundarios en la biblioteca. Vuelve cuando el staff a&ntilde;ada alguno.</p>
<?php elseif ($slots_libres < 1): ?>
          <p class="tram-intro m-0">No te quedan cupos libres (<?php echo count($mis_acomps); ?> asignado(s) + <?php echo $pend_count; ?> pendiente(s) de <?php echo (int) $max_slots; ?>). Retira un acompa&ntilde;ante o espera la resoluci&oacute;n de una solicitud.</p>
<?php else: ?>
          <form method="post" action="<?php echo $bburl; ?>/solicitar-acompanante.php" class="sa-form" id="saForm">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="solicitar">

            <label class="sa-lbl" for="saNpc">Elige un NPC</label>
            <select name="npc_id" id="saNpc" class="sa-select" required>
              <option value="">&mdash; Selecciona un NPC de la biblioteca &mdash;</option>
<?php foreach ($lib as $npc):
        $nid = (int) $npc['id'];
        $bloq = $bloqueados[$nid] ?? '';
?>
              <option value="<?php echo $nid; ?>"<?php echo $bloq !== '' ? ' disabled' : ''; ?>>
                <?php echo htmlspecialchars_uni((string) $npc['nombre']); ?><?php echo $bloq === 'asignado' ? ' (ya asignado)' : ($bloq === 'pendiente' ? ' (solicitud pendiente)' : ''); ?>
              </option>
<?php endforeach; ?>
            </select>
            <p class="sa-hint">Cupos libres: <b><?php echo $slots_libres; ?></b> de <?php echo (int) $max_slots; ?>.</p>

            <div class="sa-preview" id="saPreview" hidden>
              <p class="sa-preview-lbl">// vista previa</p>
              <div class="ons-deck ons-deck-single"><div id="saPreviewCard"></div></div>
            </div>

            <label class="sa-lbl" for="saMotivo">Motivo <span class="sa-hint-inline">(opcional, ayuda al staff)</span></label>
            <textarea name="motivo" id="saMotivo" class="sa-textarea" rows="4" maxlength="1000" placeholder="¿Por qué encaja este acompañante con tu personaje o tu historia?"></textarea>

            <div class="sa-actions">
              <button type="submit" class="btn btn-hot">Enviar solicitud</button>
            </div>
          </form>
<?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Estado actual -->
    <section class="reveal">
      <div class="plate">
        <div class="plate-h">
          <span class="t">Tus acompa&ntilde;antes</span>
          <span class="c">// <?php echo count($mis_acomps); ?>/<?php echo (int) $max_slots; ?></span>
        </div>
        <div class="plate-b">
<?php if (empty($mis_acomps)): ?>
          <p class="tram-intro m-0">A&uacute;n no tienes acompa&ntilde;antes asignados.</p>
<?php else: foreach ($mis_acomps as $ma):
        $npc = $ma['npc'] ?? null;
        if (!$npc) continue;
        $slot = (int) ($ma['slot'] ?? 0);
?>
          <div class="sa-mine-row">
            <div class="sa-mine-info">
              <span class="sa-mine-slot">Slot <?php echo $slot; ?></span>
              <span class="sa-mine-name"><?php echo htmlspecialchars_uni((string) $npc['nombre']); ?></span>
            </div>
            <div class="sa-mine-acts">
              <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $active_pid; ?>" class="btn btn-ghost btn-sm">Ver ficha</a>
              <form method="post" action="<?php echo $bburl; ?>/solicitar-acompanante.php" onsubmit="return confirm('¿Retirar este acompañante del slot <?php echo $slot; ?>?');">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="action" value="quitar">
                <input type="hidden" name="slot" value="<?php echo $slot; ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Retirar</button>
              </form>
            </div>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>

      <div class="plate">
        <div class="plate-h">
          <span class="t">Tus solicitudes</span>
          <span class="c">// <?php echo count($mis_solicit); ?> en total</span>
        </div>
        <div class="plate-b">
<?php if (empty($mis_solicit)): ?>
          <p class="tram-intro m-0">No has enviado ninguna solicitud todav&iacute;a.</p>
<?php else: foreach ($mis_solicit as $ms):
        $est = (string) $ms['estado'];
?>
          <div class="sa-sol-row">
            <div class="sa-sol-info">
              <span class="sa-sol-name"><?php echo htmlspecialchars_uni((string) $ms['npc_nombre']); ?></span>
              <span class="sa-badge sa-badge-<?php echo htmlspecialchars_uni($est); ?>"><?php echo htmlspecialchars_uni($estado_lbl[$est] ?? $est); ?></span>
              <span class="sa-sol-date"><?php echo date('d/m/Y H:i', (int) $ms['dateline']); ?></span>
            </div>
<?php if ($est === 'rechazada' && trim((string) $ms['staff_nota']) !== ''): ?>
            <p class="sa-sol-nota">Nota del staff: <?php echo htmlspecialchars_uni((string) $ms['staff_nota']); ?></p>
<?php endif; ?>
<?php if ($est === 'pendiente'): ?>
            <form method="post" action="<?php echo $bburl; ?>/solicitar-acompanante.php" class="sa-sol-cancel" onsubmit="return confirm('¿Cancelar esta solicitud?');">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="action" value="cancelar">
              <input type="hidden" name="sid" value="<?php echo (int) $ms['id']; ?>">
              <button type="submit" class="btn btn-ghost btn-sm">Cancelar</button>
            </form>
<?php endif; ?>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>
    </section>

  </div>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var NPCS = <?php echo json_encode($npc_js, JSON_UNESCAPED_UNICODE); ?>;
  var sel = document.getElementById('saNpc');
  var box = document.getElementById('saPreview');
  var card = document.getElementById('saPreviewCard');

  function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }

  function render(id){
    var n = NPCS[id];
    if (!n){ box.hidden = true; card.innerHTML = ''; return; }
    var html = '<article class="ons-card">';
    html += '<div class="ons-img-box">';
    if (n.imagen) {
      html += '<div class="ons-img"><img src="'+esc(n.imagen)+'" alt="'+esc(n.nombre)+'" onerror="this.style.display=\'none\';this.parentElement.classList.add(\'on-empty\');"></div>';
    } else {
      html += '<div class="ons-img on-empty"></div>';
    }
    html += '</div><div class="ons-body">';
    html += '<h4 class="ons-name">'+esc(n.nombre||'Sin nombre')+'</h4>';
    if (n.desc) html += '<p class="ons-desc">'+esc(n.desc).replace(/\n/g,'<br>')+'</p>';
    if (n.tec && n.tec.length){
      html += '<div class="ons-tec"><span class="ons-tec-lbl">Tecnicas</span><div class="ons-tec-list">';
      n.tec.forEach(function(t){
        html += '<div class="ons-tec-item"><span class="ons-tec-iname">'+esc(t.n)+'</span>';
        if (t.d) html += '<span class="ons-tec-idice">'+esc(t.d)+'</span>';
        if (t.e) html += '<span class="ons-tec-ief">'+esc(t.e)+'</span>';
        html += '</div>';
      });
      html += '</div></div>';
    }
    html += '</div></article>';
    card.innerHTML = html;
    box.hidden = false;
  }

  if (sel){
    sel.addEventListener('change', function(){ render(parseInt(this.value,10)); });
  }

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
  }
})();
</script>
</body>
</html>
