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
$activePid = (int)($mybb->user['gbe_active_pid'] ?? 0);

// Fallback: buscar personaje activo
if ($activePid <= 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $aq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($aq)) $activePid = (int)$db->fetch_field($aq, 'pid');
}

$staff_level = 0;
if ($loggedin && isset($mybb->user['gbe_staff_level'])) {
    $staff_level = (int)$mybb->user['gbe_staff_level'];
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
            if ($thread_id <= 0) $thread_id = (int)(microtime(true) * 10000);
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
    // Último mensaje de cada hilo (self-join al MAX(dateline) por thread_id;
    // compatible con ONLY_FULL_GROUP_BY de MySQL 8 — no seleccionar columnas
    // sin agregar junto a un GROUP BY).
    $hq = $db->query("
        SELECT m.*, 
               CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END as otro_pid,
               (SELECT nombre FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END LIMIT 1) as otro_nombre,
               (SELECT icono FROM " . TABLE_PREFIX . "rol_personajes WHERE pid = CASE WHEN m.origen_pid = {$activePid} THEN m.destino_pid ELSE m.origen_pid END LIMIT 1) as otro_icono,
               (SELECT COUNT(*) FROM " . TABLE_PREFIX . "rol_mensajes WHERE thread_id = m.thread_id AND destino_pid = {$activePid} AND leido = 0) as no_leidos
        FROM " . TABLE_PREFIX . "rol_mensajes m
        INNER JOIN (
            SELECT thread_id, MAX(dateline) AS max_dl
            FROM " . TABLE_PREFIX . "rol_mensajes
            WHERE origen_pid = {$activePid} OR destino_pid = {$activePid}
            GROUP BY thread_id
        ) latest ON latest.thread_id = m.thread_id AND latest.max_dl = m.dateline
        WHERE m.origen_pid = {$activePid} OR m.destino_pid = {$activePid}
        ORDER BY m.dateline DESC
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
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-mensajes) -->
</head>
<body class="gbe-pg-mensajes">

<?php echo gbe_rol_navbar_html(); ?>

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
          <div class="msg-sidebar-h p-10-14 df jc-sb ai-center">
            <span class="mono fs-6 fw-700 ttu c-ash">Conversaciones</span>
            <button onclick="document.getElementById('newMsgForm').style.display='block';document.getElementById('threadView').style.display='none'" class="btn btn-ghost btn-sm btn-xtrasm fs-6">+ Nuevo</button>
          </div>
          <?php if (empty($hilos)): ?>
            <div class="p-20 tac mono fs-64 c-ash">Sin mensajes aún.</div>
          <?php else: ?>
            <?php foreach ($hilos as $h):
              // Contexto pequeño: ICONO del otro personaje (fallback a inicial).
              $th_ico = trim((string) ($h['otro_icono'] ?? ''));
              $th_nom = (string) ($h['otro_nombre'] ?? '?');
              $th_ini = function_exists('mb_substr') ? mb_strtoupper(mb_substr($th_nom, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($th_nom, 0, 1));
            ?>
              <a href="<?php echo $bburl; ?>/mensajes.php?t=<?php echo (int)$h['thread_id']; ?>" class="msg-thread<?php echo $thread_open === (int)$h['thread_id'] ? ' active' : ''; ?>">
                <span class="th-av"><?php if ($th_ico !== ''): ?><img src="<?php echo htmlspecialchars_uni($th_ico); ?>" alt="" onerror="this.remove()"><?php else: ?><?php echo htmlspecialchars_uni($th_ini); ?><?php endif; ?></span>
                <span class="th-body">
                  <span class="th-name"><?php echo htmlspecialchars_uni($th_nom); ?></span>
                  <span class="th-subject"><?php echo htmlspecialchars_uni($h['asunto']); ?></span>
                  <span class="th-meta"><?php echo date('d/m H:i', (int)$h['dateline']); ?><?php if ((int)$h['no_leidos'] > 0): ?><span class="th-badge"><?php echo (int)$h['no_leidos']; ?></span><?php endif; ?></span>
                </span>
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
          <form method="post" action="<?php echo $bburl; ?>/mensajes.php" class="msg-form" id="newMsgForm">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <div class="form-row mb-8">
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
    <div class="foot-b">Granblue Fantasy: Eternal</div>
  </div>
</footer>

</body>
</html>
