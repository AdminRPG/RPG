<?php
/**
 * I-Forge · Solicitud de tripulación (fundar o unirse)
 * Acceso solo desde tripulacion.php. Crea solicitud en rol_tramites;
 * el staff aprueba en gestionar-tramites-tripulacion.php.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramite-tripulacion.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);
$pid      = $loggedin ? ope_rol_pid_activo($uid) : 0;

$modo = $mybb->get_input('modo');
if (!in_array($modo, array('fundar', 'unirse'), true)) {
    $modo = 'fundar';
}

$FACCIONES = ope_rol_facciones();
$tripulaciones = ope_rol_cat_tripulaciones();
$mi_crew = ($pid > 0) ? ope_rol_cat_tripulacion_de_personaje($pid) : null;
$tramite_pendiente = ($pid > 0) ? ope_rol_cat_tripulacion_tramite_pendiente($pid) : null;

$char_name = '';
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $cq = $db->simple_select('rol_personajes', 'nombre, estado', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($cq)) {
        $char_name = (string) $db->fetch_field($cq, 'nombre');
        $estado_pj = (string) $db->fetch_field($cq, 'estado');
        if ($estado_pj !== 'aprobado') {
            $char_name = '';
            $pid = 0;
        }
    }
}

$flash = '';
$flash_kind = 'ok';
$titulo = $modo === 'unirse' ? 'Unirse a tripulación' : 'Fundar tripulación';
$pk = htmlspecialchars_uni($mybb->post_code);

if ($loggedin && $pid > 0 && $mybb->request_method === 'post' && !$mi_crew && !$tramite_pendiente) {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga e inténtalo de nuevo.';
        $flash_kind = 'warn';
    } elseif (!$db->table_exists('rol_tramites')) {
        $flash = 'El sistema de trámites no está disponible.';
        $flash_kind = 'warn';
    } else {
        $post_modo = $mybb->get_input('modo');
        if ($post_modo === 'fundar') {
            $nombre = trim($mybb->get_input('nombre'));
            $faccion = trim($mybb->get_input('faccion'));
            $lema = trim($mybb->get_input('lema'));
            $descripcion = trim($mybb->get_input('descripcion'));
            if ($nombre === '') {
                $flash = 'El nombre de la tripulación es obligatorio.';
                $flash_kind = 'warn';
            } elseif (!isset($FACCIONES[$faccion])) {
                $flash = 'Selecciona una facción válida.';
                $flash_kind = 'warn';
            } else {
                $datos = array(
                    'nombre'      => $nombre,
                    'faccion'     => $faccion,
                    'lema'        => $lema,
                    'descripcion' => $descripcion,
                    'personaje'   => $char_name,
                );
                $db->insert_query('rol_tramites', array(
                    'uid'      => $uid,
                    'pid'      => $pid,
                    'tipo'     => 'fundar_tripulacion',
                    'estado'   => 'pendiente',
                    'datos'    => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                    'dateline' => (int) TIME_NOW,
                    'lastedit' => (int) TIME_NOW,
                ));
                $flash = 'Solicitud enviada. El staff revisará tu petición para fundar «' . htmlspecialchars_uni($nombre) . '».';
                $tramite_pendiente = ope_rol_cat_tripulacion_tramite_pendiente($pid);
            }
        } elseif ($post_modo === 'unirse') {
            $trip_id = (int) $mybb->get_input('tripulacion_id', MyBB::INPUT_INT);
            $mensaje = trim($mybb->get_input('mensaje'));
            $valida = false;
            foreach ($tripulaciones as $tr) {
                if ((int) $tr['id'] === $trip_id) {
                    $valida = true;
                    break;
                }
            }
            if (!$valida) {
                $flash = 'Selecciona una tripulación válida.';
                $flash_kind = 'warn';
            } else {
                $datos = array(
                    'tripulacion_id' => $trip_id,
                    'mensaje'        => $mensaje,
                    'personaje'      => $char_name,
                );
                $db->insert_query('rol_tramites', array(
                    'uid'      => $uid,
                    'pid'      => $pid,
                    'tipo'     => 'unirse_tripulacion',
                    'estado'   => 'pendiente',
                    'datos'    => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                    'dateline' => (int) TIME_NOW,
                    'lastedit' => (int) TIME_NOW,
                ));
                $flash = 'Solicitud enviada. El staff revisará tu petición de ingreso.';
                $tramite_pendiente = ope_rol_cat_tripulacion_tramite_pendiente($pid);
            }
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · <?php echo htmlspecialchars_uni($titulo); ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tripulacion">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/tripulacion.php">Tripulaciones</a>
    <span class="sep">›</span>
    <b><?php echo htmlspecialchars_uni($titulo); ?></b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1><?php echo htmlspecialchars_uni($titulo); ?></h1>
      <span class="code">// tripulaciones · trámite oficial</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Las tripulaciones se gestionan mediante <b>trámite</b>: el staff revisa cada solicitud antes de darla de alta en el catálogo público.</p>
  </section>

<?php if (!$loggedin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-empty">Debes iniciar sesión para enviar un trámite.</p><a href="<?php echo $bburl; ?>/member.php?action=login" class="btn btn-hot">Iniciar sesión</a></div></div></section>
<?php elseif ($pid < 1): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-empty">Necesitas un personaje <b>aprobado</b> y activo. Créalo o actívalo desde Personajes.</p><a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a></div></div></section>
<?php elseif ($mi_crew): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-empty">Ya perteneces a la tripulación <b><?php echo htmlspecialchars_uni($mi_crew['tripulacion']['nombre']); ?></b>.</p><a href="<?php echo $bburl; ?>/tripulacion.php" class="btn btn-ghost">Ver tripulaciones</a></div></div></section>
<?php elseif ($tramite_pendiente): ?>
  <section class="reveal"><div class="plate"><div class="plate-b"><p class="tram-empty">Tienes una solicitud de tripulación <b>pendiente de revisión</b>. El staff te responderá pronto.</p><a href="<?php echo $bburl; ?>/tripulacion.php" class="btn btn-ghost">Volver</a></div></div></section>
<?php else: ?>

  <section class="reveal">
    <div class="tram-bar">
      <span class="bar-l">Tipo</span>
      <a href="<?php echo $bburl; ?>/tramite-tripulacion.php?modo=fundar" class="tram-chip<?php echo $modo === 'fundar' ? ' on' : ''; ?>">Fundar</a>
      <a href="<?php echo $bburl; ?>/tramite-tripulacion.php?modo=unirse" class="tram-chip<?php echo $modo === 'unirse' ? ' on' : ''; ?>">Unirse</a>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Solicitud</span><span class="c">// <?php echo htmlspecialchars_uni($char_name); ?></span></div>
      <div class="plate-b">
        <form method="post" action="<?php echo $bburl; ?>/tramite-tripulacion.php?modo=<?php echo $modo; ?>">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
          <input type="hidden" name="modo" value="<?php echo $modo; ?>">

<?php if ($modo === 'fundar'): ?>
          <label class="mv-lbl">Nombre de la tripulación *</label>
          <input type="text" name="nombre" class="mv-input" required maxlength="160">

          <label class="mv-lbl">Facción *</label>
          <select name="faccion" class="mv-input" required>
<?php foreach ($FACCIONES as $slug => $f): ?>
            <option value="<?php echo htmlspecialchars_uni($slug); ?>"><?php echo htmlspecialchars_uni($f['nombre']); ?></option>
<?php endforeach; ?>
          </select>

          <label class="mv-lbl">Lema</label>
          <input type="text" name="lema" class="mv-input" maxlength="255">

          <label class="mv-lbl">Descripción / presentación</label>
          <textarea name="descripcion" class="mv-input" rows="5" placeholder="Quiénes sois, qué buscáis, cómo navegáis…"></textarea>
<?php else: ?>
          <label class="mv-lbl">Tripulación *</label>
          <select name="tripulacion_id" class="mv-input" required>
            <option value="">— Elegir tripulación —</option>
<?php foreach ($tripulaciones as $tr): ?>
            <option value="<?php echo (int) $tr['id']; ?>"><?php echo htmlspecialchars_uni($tr['nombre']); ?> (<?php echo htmlspecialchars_uni($tr['capitan'] ?: 'sin capitán'); ?>)</option>
<?php endforeach; ?>
          </select>

          <label class="mv-lbl">Mensaje para el staff (opcional)</label>
          <textarea name="mensaje" class="mv-input" rows="4" placeholder="Por qué quieres unirte, qué rol desempeñarías…"></textarea>
<?php endif; ?>

          <div class="mv-save-bar">
            <button type="submit" class="btn btn-hot">Enviar trámite</button>
            <a href="<?php echo $bburl; ?>/tripulacion.php" class="btn btn-ghost btn-sm">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </section>

<?php endif; ?>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>
