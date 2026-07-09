<?php
/**
 * I-Forge · Trámite "Notificar tema" (Mundo Vivo)
 * Un personaje envía el enlace de un tema EN PRESENTE del mes natural en vigor
 * + un resumen. El evento entra en el ciclo del mes para el panel de Mundo Vivo.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'notificar-tema.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$activePid = (int)($mybb->user['ope_active_pid'] ?? 0);

if ($activePid <= 0 && $loggedin && $db->table_exists('rol_personajes')) {
    $aq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($aq)) $activePid = (int)$db->fetch_field($aq, 'pid');
}

$char_name = '';
if ($activePid > 0) {
    $cq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . $activePid, array('limit' => 1));
    if ($db->num_rows($cq)) $char_name = (string)$db->fetch_field($cq, 'nombre');
}

$flash = ''; $flash_kind = 'ok';
$ciclo = ope_rol_mv_ciclo_actual();

// ── POST: notificar ──
if ($loggedin && $activePid > 0 && $mybb->request_method === 'post' && is_array($ciclo)) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga e inténtalo de nuevo.'; $flash_kind = 'warn';
    } else {
        $enlace  = trim($mybb->get_input('enlace'));
        $resumen = trim($mybb->get_input('resumen'));

        $tid = 0;
        if (preg_match('/tid=(\d+)/', $enlace, $m)) {
            $tid = (int)$m[1];
        } elseif (preg_match('/^\d+$/', $enlace)) {
            $tid = (int)$enlace;
        }

        if ($enlace === '' || $resumen === '') {
            $flash = 'Debes indicar el enlace del tema y un resumen.'; $flash_kind = 'warn';
        } elseif ($tid <= 0) {
            $flash = 'No he podido leer el ID del tema en el enlace. Pega el enlace del hilo (showthread.php?tid=...).'; $flash_kind = 'warn';
        } elseif (mb_strlen($resumen) < 40) {
            $flash = 'El resumen es demasiado corto: detalla bien qué ocurre en el tema (mín. 40 caracteres).'; $flash_kind = 'warn';
        } else {
            // Validar el tema
            $tq = $db->simple_select('threads', 'tid, fid, subject, dateline, lastpost, visible', 'tid = ' . $tid, array('limit' => 1));
            if (!$db->num_rows($tq)) {
                $flash = 'No existe ningún tema con ese enlace.'; $flash_kind = 'warn';
            } else {
                $th = $db->fetch_array($tq);
                $zona = ope_rol_mv_zona_from_fid((int)$th['fid']);

                // Época del tema (rol_thread_meta)
                $era = '';
                if ($db->table_exists('rol_thread_meta')) {
                    $eq = $db->simple_select('rol_thread_meta', 'era', 'tid = ' . $tid, array('limit' => 1));
                    if ($db->num_rows($eq)) $era = (string)$db->fetch_field($eq, 'era');
                }

                $mesActual   = date('Y-m');
                $mesTema     = date('Y-m', (int)$th['lastpost']);
                $mesCreacion = date('Y-m', (int)$th['dateline']);

                // Duplicado
                $dup = $db->fetch_field($db->simple_select('rol_mv_eventos', 'COUNT(*) c', "tid = {$tid} AND pid = {$activePid} AND ciclo_id = " . (int)$ciclo['ciclo_id']), 'c');

                if ($zona === '') {
                    $flash = 'Ese tema no pertenece a ninguna región del mundo (¿es Off Topic?). Solo se notifican temas del mundo.'; $flash_kind = 'warn';
                } elseif ($era !== 'presente') {
                    $flash = 'Solo puedes notificar temas marcados como EN PRESENTE. Ese tema no lo está.'; $flash_kind = 'warn';
                } elseif ($mesTema !== $mesActual && $mesCreacion !== $mesActual) {
                    $flash = 'El tema no tiene actividad en el mes natural en vigor (' . htmlspecialchars_uni($mesActual) . '). Solo se notifican temas de este mes.'; $flash_kind = 'warn';
                } elseif ((int)$dup > 0) {
                    $flash = 'Ya has notificado este tema este mes.'; $flash_kind = 'warn';
                } else {
                    $db->insert_query('rol_mv_eventos', array(
                        'ciclo_id'  => (int)$ciclo['ciclo_id'],
                        'pid'       => $activePid,
                        'uid'       => $uid,
                        'tid'       => $tid,
                        'enlace'    => $db->escape_string($bburl . '/showthread.php?tid=' . $tid),
                        'titulo'    => $db->escape_string((string)$th['subject']),
                        'resumen'   => $db->escape_string($resumen),
                        'fid'       => (int)$th['fid'],
                        'zona_slug' => $db->escape_string($zona),
                        'estado'    => 'pendiente',
                        'dateline'  => (int)TIME_NOW,
                    ));
                    $flash = 'Tema notificado correctamente. El staff lo revisará en el cierre del mes.';
                    $flash_kind = 'ok';
                }
            }
        }
    }
}

// Notificaciones previas del personaje en este ciclo
$mis_eventos = array();
if ($activePid > 0 && is_array($ciclo)) {
    $eq = $db->simple_select('rol_mv_eventos', '*', "pid = {$activePid} AND ciclo_id = " . (int)$ciclo['ciclo_id'], array('order_by' => 'dateline', 'order_dir' => 'DESC'));
    while ($er = $db->fetch_array($eq)) { $mis_eventos[] = $er; }
}

$mes_label = is_array($ciclo) ? htmlspecialchars_uni($ciclo['periodo']) : '';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Notificar tema</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-notificar-tema) -->
</head>
<body class="ope-pg-notificar-tema">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Tr&aacute;mites</a>
    <span class="sep">&#8250;</span>
    <b>Notificar tema</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Notificar tema</h1>
      <span class="code">// mundo vivo &middot; mes <?php echo $mes_label; ?></span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Envía el enlace de un tema <b>en presente</b> del <b>mes natural en vigor</b> y un resumen de lo que ocurre. El staff lo tendrá en cuenta al recalcular el estado del mundo y redactar el periódico <b>Eternal News</b>.</p>
  </section>

<?php if (!$loggedin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-intro" style="margin:0">Debes <a href="<?php echo $bburl; ?>/member.php?action=login">iniciar sesión</a> con un personaje activo para notificar temas.</p></div></div></section>
<?php elseif ($activePid <= 0): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-intro" style="margin:0">Necesitas un <b>personaje activo</b> para notificar temas.</p></div></div></section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="nt-flash nt-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Nuevo aviso</span>
        <span class="c">// como <?php echo htmlspecialchars_uni($char_name); ?></span>
      </div>
      <div class="plate-b">
        <form method="post" action="<?php echo $bburl; ?>/notificar-tema.php" class="nt-form">
          <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
          <label class="nt-lbl" for="enlace">Enlace del tema</label>
          <input type="text" id="enlace" name="enlace" class="nt-input" placeholder="<?php echo $bburl; ?>/showthread.php?tid=123" required>
          <p class="nt-hint">Pega el enlace del hilo. Debe ser un tema del mundo, en presente y con actividad este mes.</p>

          <label class="nt-lbl" for="resumen">Resumen detallado</label>
          <textarea id="resumen" name="resumen" class="nt-textarea" rows="8" placeholder="Detalla bien qué ocurre: quiénes participan, dónde, qué acciones y consecuencias tiene para el mundo..." required></textarea>
          <p class="nt-hint">Cuanto más completo, mejor reflejará el impacto en el mundo.</p>

          <div class="nt-actions">
            <button type="submit" class="btn btn-primary">Notificar tema</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Tus avisos de este mes</span>
        <span class="c">// <?php echo count($mis_eventos); ?> enviado(s)</span>
      </div>
      <div class="plate-b">
<?php if (empty($mis_eventos)): ?>
        <p class="tram-intro" style="margin:0">Aún no has notificado ningún tema este mes.</p>
<?php else: ?>
        <ul class="nt-list">
<?php foreach ($mis_eventos as $e): ?>
          <li class="nt-item">
            <a class="nt-item-t" href="<?php echo htmlspecialchars_uni($e['enlace']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars_uni($e['titulo']); ?></a>
            <span class="nt-item-meta"><?php echo htmlspecialchars_uni($e['zona_slug']); ?> &middot; <?php echo htmlspecialchars_uni($e['estado']); ?></span>
            <p class="nt-item-res"><?php echo nl2br(htmlspecialchars_uni(mb_substr((string)$e['resumen'], 0, 240))); ?><?php echo mb_strlen((string)$e['resumen']) > 240 ? '&hellip;' : ''; ?></p>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
    </div>
  </section>

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
