<?php
/**
 * I-Forge · Calendario On-Rol
 * Muestra la estación actual y el año on-rol en curso.
 * Cada 15 días se marca "Luna llena". Solo lectura.
 * La edición está en gestionar-calendario.php (Zona Staff).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'calendario-onrol.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);

$rol_seasons = array(
    array('Primavera', 'var(--patina-hi)'),
    array('Verano',    'var(--ember)'),
    array('Otoño',     'var(--h4)'),
    array('Invierno',  'var(--h1)'),
);

$cal = gbe_rol_onrol_calendar();
$rol_year       = (int) $cal['year'];
$rol_day        = (int) $cal['day'];
$rol_season     = (string) $cal['season'];
$rol_season_idx = (int) $cal['season_idx'];
$s_color        = $rol_seasons[$rol_season_idx][1];

$year_label = function_exists('gbe_rol_year_label') ? gbe_rol_year_label($rol_year) : (string)$rol_year;

$notas = array();
if ($db->table_exists('rol_calendario')) {
    $nq = $db->simple_select('rol_calendario', 'dia, dato', "anio = {$rol_year} AND estacion = '" . $db->escape_string($rol_season) . "'");
    while ($nr = $db->fetch_array($nq)) {
        $notas[(int)$nr['dia']] = (string) $nr['dato'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Calendario On-Rol</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-calendario-onrol">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <b>Calendario On-Rol</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Calendario On-Rol</h1>
      <span class="code">// <?php echo htmlspecialchars_uni($rol_season); ?> &middot; A&ntilde;o <?php echo $year_label; ?></span>
      <span class="rule"></span>
    </div>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h ocal-season-h" style="border-left:4px solid <?php echo $s_color; ?>">
        <span class="t ocal-season-t">
          <span class="ocal-dot" style="background:<?php echo $s_color; ?>;box-shadow:0 0 8px <?php echo $s_color; ?>" aria-hidden="true"></span>
          <?php echo htmlspecialchars_uni($rol_season); ?>
        </span>
        <span class="c">// 65 d&iacute;as &middot; A&ntilde;o <?php echo $year_label; ?></span>
        <span class="ocal-season-bar" aria-hidden="true" style="flex:1;height:3px;background:<?php echo $s_color; ?>;opacity:.35;border-radius:2px"></span>
      </div>
      <div class="plate-b ocal-grid">
        <?php for ($d = 1; $d <= 65; $d++):
            $is_today = ($d === $rol_day);
            $is_luna = ($d % 15 === 0);
            $tiene_nota = isset($notas[$d]);
            $nota_texto = $tiene_nota ? $notas[$d] : '';
            $day_title = $tiene_nota ? htmlspecialchars_uni($nota_texto) : ( $is_luna ? 'Luna llena' : '' );
        ?>
          <div class="ocal-day<?php echo $is_today ? ' ocal-day-today' : ''; ?><?php echo $is_luna ? ' ocal-day-luna' : ''; ?><?php echo $tiene_nota ? ' ocal-day-hasnote' : ''; ?>"
               title="<?php echo $day_title; ?>"
               style="<?php echo $is_today ? '--season:'.$s_color.';' : ''; ?>">
            <span class="ocal-day-num"><?php echo $d; ?></span>
            <?php if ($is_luna): ?>
              <span class="ocal-luna" aria-label="Luna llena">
                <svg viewBox="0 0 20 20" width="14" height="14" aria-hidden="true"><circle cx="10" cy="10" r="8" fill="currentColor"/></svg>
              </span>
            <?php endif; ?>
            <?php if ($tiene_nota): ?>
              <span class="ocal-note-dot" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if ($is_today): ?>
              <span class="ocal-today-ring" aria-hidden="true"></span>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </section>

  <section class="reveal ocal-legend">
    <span class="ocal-legend-i"><span class="ocal-dot-sm" style="background:var(--ember);box-shadow:0 0 6px var(--ember)"></span> D&iacute;a actual</span>
    <span class="ocal-legend-i"><svg viewBox="0 0 20 20" width="12" height="12" aria-hidden="true"><circle cx="10" cy="10" r="8" fill="currentColor"/></svg> Luna llena (cada 15 d&iacute;as)</span>
    <span class="ocal-legend-i"><span class="ocal-note-dot-sm"></span> D&iacute;a con anotaci&oacute;n</span>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  var io = new IntersectionObserver(function(es) { es.forEach(function(e) {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }); }, { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function(el) { el.classList.add('vis'); });
}
</script>
</body>
</html>
