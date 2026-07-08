<?php
/**
 * I-Forge · Trámites del taller
 * Página de front-end MyBB (dirección "One Piece Eternal").
 * Estructura de servicios del taller. Sin datos de ejemplo ni saldos inventados.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tramites.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Nivel de staff (plugin ope_rol, con respaldo directo)
$staff_level = 0;
if ($loggedin) {
    if (isset($mybb->user['ope_staff_level'])) {
        $staff_level = (int)$mybb->user['ope_staff_level'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $staff_level = (int)$db->fetch_field($cq, 'staff_level');
        }
    }
}

$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string)$mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámites</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-tramites) -->
</head>
<body class="ope-pg-tramites">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Trámites</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Trámites</h1>
      <span class="code">// ventanillas de servicio</span>
      <span class="rule"></span>
    </div>
    <p class="tram-intro">Aquí estarán las <b>ventanillas oficiales</b> del foro para gestionar tu personaje, tu economía y las peticiones al staff. Se irán habilitando conforme tengan su sistema listo.</p>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Ventanillas en preparaci&oacute;n</span>
        <span class="c">// pr&oacute;ximamente</span>
      </div>
      <div class="plate-b">
        <p class="tram-intro" style="margin:0">Los tr&aacute;mites del foro (econom&iacute;a, solicitudes de personaje y peticiones al staff) se ir&aacute;n habilitando aqu&iacute; conforme tengan su sistema listo. De momento, para cualquier gesti&oacute;n contacta directamente con <b>el staff</b>.</p>
      </div>
    </div>
  </section>

  <section class="horario reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Horario de atención</span>
        <span class="c">// atención de staff</span>
      </div>
      <div class="plate-b mono">
        <div class="hbit"><div class="hl">Atención del staff</div><div class="hv">L–V · 09:00–22:00 <small>CET</small></div></div>
        <div class="hbit"><div class="hl">Cola media · peticiones</div><div class="hv">~24 h <small>días laborables</small></div></div>
        <div class="hbit"><div class="hl">Cola media · rangos</div><div class="hv">~72 h <small>evaluación</small></div></div>
        <div class="hbit"><div class="hl">Incidencias urgentes</div><div class="hv">~48 h <small>respuesta</small></div></div>
      </div>
    </div>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
// --- Reveal on scroll ---
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
