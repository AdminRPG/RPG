<?php
/**
 * I-Forge · Mensajes Directos
 * Bandeja de mensajes por personaje: lista de hilos, lectura, envío y respuesta.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mensajes.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');
$activePid = (int)($mybb->user['iforge_active_pid'] ?? 0);

// Fallback: buscar personaje activo
if ($activePid <= 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $aq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($aq)) $activePid = (int)$db->fetch_field($aq, 'pid');
}

$staff_level = 0;
if ($loggedin && isset($mybb->user['iforge_staff_level'])) {
    $staff_level = (int)$mybb->user['iforge_staff_level'];
}

// POST: enviar mensaje
$flash = ''; $flash_kind = 'ok';
if ($loggedin && $activePid > 0 && $mybb->request_method === 'post' && $db->table_exists('rol_mensajes')) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.'; $flash_kind = 'warn';
    } else {
        $thread_id = (int)($mybb->get_input('thread_id', MyBB::INPUT_INT));
        $destino_pid = (int)($mybb->get_input('destino_pid', MyBB::INPUT_INT));
        $asunto = trim($mybb->get_input('asunto'));
        $cuerpo = trim($mybb->get_input('cuerpo'));

        if ($destino_pid <= 0 || $cuerpo === '') {
            $flash = 'Faltan campos obligatorios.'; $flash_kind = 'warn';
        } else {
            if ($thread_id <= 0) $thread_id = TIME_NOW;
            $db->insert_query('rol_mensajes', array(
                'thread_id' => $thread_id,
                'origen_pid' => $activePid,
                'destino_pid' => $destino_pid,
                'asunto' => $db->escape_string($asunto),
                'cuerpo' => $db->escape_string($cuerpo),
                'leido' => 0,
                'dateline' => TIME_NOW
            ));
            // Alerta para el destinatario
            if ($db->table_exists('rol_alertas')) {
                $du = $db->simple_select('rol_personajes', 'uid', "pid = {$destino_pid}", array('limit' => 1));
                $dest_uid = (int)$db->fetch_field($du, 'uid');
                $db->insert_query('rol_alertas', array(
                    'pid' => $destino_pid, 'uid' => $dest_uid,
                    'tipo' => 'mensaje_nuevo',
                    'titulo' => 'Nuevo mensaje',
                    'cuerpo' => 'Has recibido un mensaje nuevo.',
                    'link' => $bburl . '/mensajes.php?t=' . $thread_id,
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Mensaje enviado.';
        }
    }
}

// Marcar hilo como leído
$thread_open = (int)($mybb->get_input('t', MyBB::INPUT_INT));
if ($thread_open > 0 && $activePid > 0 && $db->table_exists('rol_mensajes')) {
    $db->update_query('rol_mensajes', array('leido' => 1), "thread_id = {$thread_open} AND destino_pid = {$activePid}");
}

// Cargar hilos (conversaciones)
$hilos = array();
if ($activePid > 0 && $db->table_exists('rol_mensajes')) {
    $hq = $db->query("
        SELECT m.*, 
               CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END as otro_pid,
               (SELECT nombre FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END LIMIT 1) as otro_nombre,
               (SELECT COUNT(*) FROM " . TABLE_PREFIX . "rol_mensajes WHERE thread_id = m.thread_id AND destino_pid = {$activePid} AND leido = 0) as no_leidos
        FROM " . TABLE_PREFIX . "rol_mensajes m
        WHERE m.origen_pid = {$activePid} OR m.destino_pid = {$activePid}
        GROUP BY m.thread_id
        ORDER BY MAX(m.dateline) DESC
    ");
    while ($row = $db->fetch_array($hq)) $hilos[] = $row;
}

// Cargar mensajes del hilo abierto
$mensajes_hilo = array();
if ($thread_open > 0 && $db->table_exists('rol_mensajes')) {
    $mq = $db->query("
        SELECT m.*, 
               (SELECT nombre FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = m.origen_pid LIMIT 1) as origen_nombre
        FROM " . TABLE_PREFIX . "rol_mensajes m
        WHERE m.thread_id = {$thread_open} AND (m.origen_pid = {$activePid} OR m.destino_pid = {$activePid})
        ORDER BY m.dateline ASC
    ");
    while ($row = $db->fetch_array($mq)) $mensajes_hilo[] = $row;
}

// Lista de personajes para enviar nuevo mensaje (solo aprobados)
$personajes_destino = array();
if ($activePid > 0 && $db->table_exists('rol_personajes')) {
    $dq = $db->simple_select('rol_personajes', 'pid, nombre', "pid != {$activePid} AND estado = 'aprobado'", array('order_by' => 'nombre'));
    while ($row = $db->fetch_array($dq)) $personajes_destino[] = $row;
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) { if ($p !== '') $initials .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'); }
    $initials = mb_substr($initials, 0, 2, 'UTF-8');
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Mensajes</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=Space+Mono:wght@400;700&family=Archivo:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --iron:#0b3157; --iron-plate:#10477B; --iron-hi:#175a95; --iron-edge:#082742;
  --rivet:#3d6f9e; --paper:#eaf4fb; --paper-dim:#a9c6e0; --ash:#5c83a7;
  --ember:#FFCB93; --ember-hi:#FFE9A3; --patina:#41A4E0; --crack:#e63b2e;
  --disp:'Big Shoulders Display',Impact,sans-serif;
  --mono:'Space Mono',Menlo,Consolas,monospace;
  --body:'Archivo',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--iron);color:var(--paper);font-family:var(--body);font-size:15px;line-height:1.55;padding-top:52px;
  background-image:linear-gradient(rgba(255,255,255,.015) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.015) 1px,transparent 1px);background-size:26px 26px}
a{color:var(--ember-hi);text-decoration:none}
.wrap{max-width:1300px;margin:0 auto;padding:0 18px}

.breadcrumb{background:var(--iron-plate);border-bottom:2px solid #000}
.breadcrumb-in{max-width:1300px;margin:0 auto;padding:9px 18px;display:flex;align-items:center;gap:8px;font-family:var(--mono);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.breadcrumb-in a{color:var(--paper-dim)}
.breadcrumb-in a:hover{color:var(--ember-hi)}
.breadcrumb-in .sep{color:var(--rivet)}
.breadcrumb-in b{color:var(--paper)}

.shead{display:flex;align-items:baseline;gap:14px;margin:20px 0 14px}
.shead h1{font-family:var(--disp);font-weight:800;font-size:2rem;text-transform:uppercase;color:var(--paper);line-height:1}
.shead .code{font-family:var(--mono);font-size:.7rem;font-weight:700;color:var(--ember-hi);letter-spacing:1px}
.shead .rule{flex:1;height:2px;background:repeating-linear-gradient(90deg,var(--rivet) 0 6px,transparent 6px 12px)}

.btn{font-family:var(--mono);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:10px 18px;border:2px solid #000;cursor:pointer;transition:transform .12s,box-shadow .12s;display:inline-block}
.btn-hot{background:var(--ember);color:var(--iron)}
.btn-hot:hover{transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-ghost{background:transparent;color:var(--paper);border-color:var(--rivet)}
.btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}
.btn-sm{padding:6px 12px;font-size:.7rem}

.flash{font-family:var(--mono);font-size:.72rem;padding:10px 14px;border:2px solid #000;margin-bottom:14px}
.flash.ok{background:var(--iron-plate);color:var(--h6);border-color:var(--patina)}
.flash.warn{background:var(--iron-plate);color:var(--ember);border-color:var(--ember)}

.msg-shell{display:grid;grid-template-columns:300px 1fr;gap:0;border:2px solid #000;min-height:600px}
.msg-sidebar{background:var(--iron-edge);border-right:2px solid #000;overflow-y:auto}
.msg-sidebar-in{padding:0}
.msg-thread{display:block;width:100%;padding:12px 14px;background:transparent;border:none;border-bottom:1px solid var(--iron);cursor:pointer;text-align:left;color:var(--paper-dim);font-family:var(--body);font-size:.82rem;transition:background .12s}
.msg-thread:hover{background:var(--iron-plate)}
.msg-thread.active{background:var(--iron-plate);border-left:3px solid var(--ember)}
.msg-thread .th-name{font-weight:600;color:var(--paper);margin-bottom:3px}
.msg-thread .th-subject{font-size:.74rem;color:var(--paper-dim)}
.msg-thread .th-meta{font-family:var(--mono);font-size:.58rem;color:var(--ash);margin-top:4px}
.msg-thread .th-badge{display:inline-block;min-width:20px;height:18px;background:var(--crack);color:#fff;font-family:var(--mono);font-size:.56rem;font-weight:700;border-radius:9px;text-align:center;line-height:18px;padding:0 5px;margin-left:6px}

.msg-main{padding:0;display:flex;flex-direction:column;background:var(--iron)}
.msg-list{flex:1;overflow-y:auto;padding:16px 20px;max-height:460px}
.msg-bubble{margin-bottom:14px;max-width:80%}
.msg-bubble.mine{margin-left:auto}
.msg-bubble .b-head{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--ash);margin-bottom:4px}
.msg-bubble .b-body{background:var(--iron-plate);border:2px solid #000;padding:10px 14px;font-size:.84rem;color:var(--paper-dim);line-height:1.55}
.msg-bubble.mine .b-body{background:var(--iron-edge);color:var(--paper)}
.msg-bubble .b-time{font-family:var(--mono);font-size:.54rem;color:var(--ash);margin-top:4px;text-align:right}

.msg-form{border-top:2px solid #000;padding:14px 20px;background:var(--iron-plate)}
.msg-form textarea{width:100%;min-height:80px;background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--body);font-size:.82rem;padding:10px 12px;resize:vertical;margin-bottom:8px}
.msg-form textarea:focus{outline:none;border-color:var(--ember)}
.msg-form .form-row{display:flex;gap:10px;align-items:flex-end}
.msg-form select{background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--mono);font-size:.7rem;padding:8px 10px}
.msg-form input[type="text"]{background:var(--iron);border:2px solid #000;color:var(--paper);font-family:var(--body);font-size:.82rem;padding:8px 10px;flex:1}

.empty-state{text-align:center;padding:48px 20px;color:var(--paper-dim)}
.empty-state .big{font-family:var(--disp);font-weight:800;font-size:1.6rem;text-transform:uppercase;color:var(--paper);margin-bottom:8px}
.empty-state p{font-family:var(--mono);font-size:.72rem;max-width:48ch;margin:0 auto;line-height:1.6}

.foot{background:var(--iron-edge);border-top:2px solid #000;padding:24px 18px;margin-top:36px}
.foot-in{max-width:1300px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.foot-b{font-family:var(--disp);font-weight:900;font-size:1.3rem;text-transform:uppercase;color:var(--paper)}

@media(max-width:768px){
  .msg-shell{grid-template-columns:1fr}
  .msg-sidebar{border-right:none;border-bottom:2px solid #000;max-height:200px}
  .msg-list{max-height:300px}
}
</style>
</head>
<body>

<?php echo iforge_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <b>Mensajes</b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1>Mensajes</h1>
    <span class="code">// mensajería directa</span>
    <span class="rule"></span>
  </div>

  <?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <?php if ($activePid <= 0): ?>
    <div class="empty-state">
      <div class="big">Sin personaje activo</div>
      <p>Activa un personaje desde la página de Personaje para usar la mensajería.</p>
    </div>
  <?php else: ?>
    <div class="msg-shell">
      <!-- SIDEBAR: lista de hilos -->
      <div class="msg-sidebar">
        <div class="msg-sidebar-in">
          <div style="padding:10px 14px;border-bottom:2px solid #000;display:flex;justify-content:space-between;align-items:center">
            <span style="font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;color:var(--ash)">Conversaciones</span>
            <button onclick="document.getElementById('newMsgForm').style.display='block';document.getElementById('threadView').style.display='none'" class="btn btn-ghost btn-sm" style="font-size:.6rem;padding:4px 8px">+ Nuevo</button>
          </div>
          <?php if (empty($hilos)): ?>
            <div style="padding:20px;text-align:center;font-family:var(--mono);font-size:.64rem;color:var(--ash)">Sin mensajes aún.</div>
          <?php else: ?>
            <?php foreach ($hilos as $h): ?>
              <a href="?t=<?php echo (int)$h['thread_id']; ?>" class="msg-thread<?php echo $thread_open === (int)$h['thread_id'] ? ' active' : ''; ?>">
                <div class="th-name"><?php echo htmlspecialchars_uni($h['otro_nombre'] ?? '?'); ?></div>
                <div class="th-subject"><?php echo htmlspecialchars_uni($h['asunto']); ?></div>
                <div class="th-meta"><?php echo date('d/m H:i', (int)$h['dateline']); ?><?php if ((int)$h['no_leidos'] > 0): ?><span class="th-badge"><?php echo (int)$h['no_leidos']; ?></span><?php endif; ?></div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- MAIN: mensajes del hilo + formulario -->
      <div class="msg-main">
        <?php if ($thread_open > 0 && !empty($mensajes_hilo)): ?>
          <div class="msg-list" id="threadView">
            <?php foreach ($mensajes_hilo as $msg): 
              $isMine = (int)$msg['origen_pid'] === $activePid;
            ?>
              <div class="msg-bubble<?php echo $isMine ? ' mine' : ''; ?>">
                <div class="b-head"><?php echo htmlspecialchars_uni($msg['origen_nombre'] ?? '?'); ?></div>
                <div class="b-body"><?php echo nl2br(htmlspecialchars_uni($msg['cuerpo'])); ?></div>
                <div class="b-time"><?php echo date('d/m/Y H:i', (int)$msg['dateline']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Responder en hilo -->
          <form method="post" action="<?php echo $bburl; ?>/mensajes.php?t=<?php echo $thread_open; ?>" class="msg-form">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="thread_id" value="<?php echo $thread_open; ?>">
            <?php 
              // Encontrar el otro pid en el hilo
              $otro_pid = 0;
              foreach ($hilos as $h) {
                if ((int)$h['thread_id'] === $thread_open) { $otro_pid = (int)$h['otro_pid']; break; }
              }
            ?>
            <input type="hidden" name="destino_pid" value="<?php echo $otro_pid; ?>">
            <input type="hidden" name="asunto" value="Re: <?php echo htmlspecialchars_uni($mensajes_hilo[0]['asunto'] ?? ''); ?>">
            <textarea name="cuerpo" placeholder="Escribe tu respuesta..."></textarea>
            <button type="submit" class="btn btn-hot btn-sm">Responder</button>
          </form>

        <?php else: ?>
          <div id="threadView">
            <div class="empty-state">
              <div class="big">Selecciona una conversación</div>
              <p>Elige un hilo de la izquierda o crea uno nuevo.</p>
            </div>
          </div>

          <!-- Nuevo mensaje -->
          <form method="post" action="<?php echo $bburl; ?>/mensajes.php" class="msg-form" id="newMsgForm" style="display:block">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <div class="form-row" style="margin-bottom:8px">
              <select name="destino_pid" required>
                <option value="">Destinatario...</option>
                <?php foreach ($personajes_destino as $dp): ?>
                  <option value="<?php echo (int)$dp['pid']; ?>"><?php echo htmlspecialchars_uni($dp['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="asunto" placeholder="Asunto..." required>
            </div>
            <textarea name="cuerpo" placeholder="Escribe tu mensaje..." required></textarea>
            <button type="submit" class="btn btn-hot btn-sm">Enviar mensaje</button>
          </form>
        <?php endif; ?>
      </div>
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
