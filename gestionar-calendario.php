<?php
/**
 * I-Forge · Gestionar Calendario On-Rol (Zona Staff)
 * Permite a staff (rango ≥ Colaborador) añadir/editar/eliminar
 * anotaciones en los días de la estación actual del calendario on-rol.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-calendario.php');
require_once './global.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid       = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$rank      = (int) $staff['rank'];
$is_staff  = !empty($staff['is_staff']);
$puede_editar = ($rank >= 1);

$rol_seasons = array('Primavera', 'Verano', 'Otoño', 'Invierno');

$cal = ope_rol_onrol_calendar();
$rol_year       = (int) $cal['year'];
$rol_day        = (int) $cal['day'];
$rol_season     = (string) $cal['season'];
$rol_season_idx = (int) $cal['season_idx'];
$season_colors  = array('var(--patina-hi)', 'var(--ember)', 'var(--h4)', 'var(--h1)');
$s_color        = $season_colors[$rol_season_idx];
$year_label = function_exists('ope_rol_year_label') ? ope_rol_year_label($rol_year) : (string)$rol_year;

$flash_msg = '';
$flash_type = '';

if ($puede_editar && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ope_cal_save'])) {
    $post_dia      = (int) ($_POST['dia'] ?? 0);
    $post_estacion = trim((string) ($_POST['estacion'] ?? ''));
    $post_dato     = trim((string) ($_POST['dato'] ?? ''));

    if ($post_dia >= 1 && $post_dia <= 65 && in_array($post_estacion, $rol_seasons)) {
        $ex = $db->simple_select('rol_calendario', 'id, dato', "anio = {$rol_year} AND dia = {$post_dia} AND estacion = '" . $db->escape_string($post_estacion) . "'", array('limit' => 1));
        if ($db->num_rows($ex)) {
            $existing = $db->fetch_array($ex);
            if ($post_dato === '') {
                $db->delete_query('rol_calendario', "id = " . (int)$existing['id']);
                $flash_msg = 'Anotación eliminada del día ' . $post_dia . ' · ' . $post_estacion . '.';
            } else {
                $db->update_query('rol_calendario', array(
                    'dato'      => $db->escape_string($post_dato),
                    'autor_pid' => (int) $staff['pid'],
                    'dateline'  => TIME_NOW,
                ), "id = " . (int)$existing['id']);
                $flash_msg = 'Anotación actualizada (día ' . $post_dia . ' · ' . $post_estacion . ').';
            }
            $flash_type = 'ok';
        } else {
            if ($post_dato !== '') {
                $db->insert_query('rol_calendario', array(
                    'anio'      => $rol_year,
                    'dia'       => $post_dia,
                    'estacion'  => $db->escape_string($post_estacion),
                    'dato'      => $db->escape_string($post_dato),
                    'autor_pid' => (int) $staff['pid'],
                    'dateline'  => TIME_NOW,
                ));
                $flash_msg = 'Anotación guardada (día ' . $post_dia . ' · ' . $post_estacion . ').';
                $flash_type = 'ok';
            }
        }
    }
}

$notas = array();
$total_notas = 0;
if ($db->table_exists('rol_calendario')) {
    $nq = $db->simple_select('rol_calendario', 'dia, dato', "anio = {$rol_year} AND estacion = '" . $db->escape_string($rol_season) . "'");
    while ($nr = $db->fetch_array($nq)) {
        $notas[(int)$nr['dia']] = (string) $nr['dato'];
        $total_notas++;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Gestionar Calendario</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-calendario-onrol">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Gestionar Calendario</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Gestionar Calendario</h1>
      <span class="code">// editar d&iacute;as de la estaci&oacute;n actual</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$is_staff || !$puede_editar): ?>
  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Acceso restringido</span>
        <span class="c">// solo staff</span>
      </div>
      <div class="plate-b">
        <div class="noperm">
          <span class="lock" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="9" rx="1"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>
          <div class="big">No tienes acceso</div>
          <p>Necesitas rango <b>Colaborador</b> o superior en tu personaje activo para editar el calendario.</p>
          <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>

<?php if ($flash_msg !== ''): ?>
  <section class="reveal">
    <div class="flash <?php echo $flash_type; ?>"><?php echo htmlspecialchars_uni($flash_msg); ?></div>
  </section>
<?php endif; ?>

  <section class="reveal">
    <p class="ocal-intro">Haz clic en cualquier d&iacute;a para <b>a&ntilde;adir</b> o <b>editar</b> su anotaci&oacute;n. Deja el texto vac&iacute;o y guarda para <b>eliminar</b> la anotaci&oacute;n. Los cambios son visibles en <a href="<?php echo $bburl; ?>/calendario-onrol.php">el calendario p&uacute;blico</a>.</p>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h ocal-season-h" style="border-left:4px solid <?php echo $s_color; ?>">
        <span class="t ocal-season-t">
          <span class="ocal-dot" style="background:<?php echo $s_color; ?>;box-shadow:0 0 8px <?php echo $s_color; ?>" aria-hidden="true"></span>
          <?php echo htmlspecialchars_uni($rol_season); ?>
        </span>
        <span class="c">// 65 d&iacute;as &middot; A&ntilde;o <?php echo $year_label; ?> &middot; <?php echo $total_notas; ?> anotaci&oacute;n(es)</span>
        <span class="ocal-season-bar" aria-hidden="true" style="flex:1;height:3px;background:<?php echo $s_color; ?>;opacity:.35;border-radius:2px"></span>
      </div>
      <div class="plate-b ocal-grid">
        <?php for ($d = 1; $d <= 65; $d++):
            $is_today = ($d === $rol_day);
            $is_luna = ($d % 15 === 0);
            $tiene_nota = isset($notas[$d]);
            $nota_texto = $tiene_nota ? $notas[$d] : '';
        ?>
          <div class="ocal-day ocal-day-edit<?php echo $is_today ? ' ocal-day-today' : ''; ?><?php echo $is_luna ? ' ocal-day-luna' : ''; ?><?php echo $tiene_nota ? ' ocal-day-hasnote' : ''; ?>"
               title="Clic para editar"
               role="button" tabindex="0"
               data-dia="<?php echo $d; ?>"
               data-estacion="<?php echo htmlspecialchars_uni($rol_season); ?>"
               data-dato="<?php echo htmlspecialchars_uni($nota_texto); ?>"
               data-luna="<?php echo $is_luna ? '1' : '0'; ?>"
               onclick="openCalModal(this)"
               onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openCalModal(this)}"
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

<?php endif; ?>

</div>

<?php if ($puede_editar): ?>
<div id="ocal-modal" class="ocal-modal" hidden>
  <div class="ocal-modal-bg" onclick="closeCalModal()"></div>
  <div class="ocal-modal-box" role="dialog" aria-modal="true" aria-labelledby="ocal-modal-title">
    <button type="button" class="ocal-modal-x" onclick="closeCalModal()" aria-label="Cerrar">&times;</button>
    <div class="plate">
      <div class="plate-h">
        <span class="t" id="ocal-modal-title">Editar d&iacute;a</span>
        <span class="c">// anotaci&oacute;n staff</span>
      </div>
      <div class="plate-b">
        <form method="post" action="<?php echo $bburl; ?>/gestionar-calendario.php">
          <input type="hidden" name="dia" id="ocal-f-dia" value="">
          <input type="hidden" name="estacion" id="ocal-f-estacion" value="">
          <div class="ocal-meta" id="ocal-meta"></div>
          <div class="ocal-field">
            <label for="ocal-f-dato" class="mv-lbl">Anotaci&oacute;n del d&iacute;a</label>
            <textarea id="ocal-f-dato" name="dato" class="mv-textarea" rows="5" placeholder="Escribe aqu&iacute; la informaci&oacute;n de este d&iacute;a..."></textarea>
          </div>
          <div class="ocal-form-actions">
            <button type="submit" name="ope_cal_save" value="1" class="btn btn-hot btn-sm">Guardar anotaci&oacute;n</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeCalModal()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openCalModal(el) {
  var dia = el.getAttribute('data-dia');
  var estacion = el.getAttribute('data-estacion');
  var dato = el.getAttribute('data-dato') || '';
  var luna = el.getAttribute('data-luna') === '1';
  var modal = document.getElementById('ocal-modal');
  var lunaHtml = luna ? ' <span style="color:var(--ember-hi)">&middot; Luna llena</span>' : '';
  document.getElementById('ocal-f-dia').value = dia;
  document.getElementById('ocal-f-estacion').value = estacion;
  document.getElementById('ocal-f-dato').value = dato;
  document.getElementById('ocal-meta').innerHTML = 'D&iacute;a <b>' + dia + '</b> &middot; <b>' + estacion + '</b> &middot; A&ntilde;o <?php echo $rol_year; ?> (' + <?php echo json_encode($year_label); ?> + ')' + lunaHtml;
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  document.getElementById('ocal-f-dato').focus();
}
function closeCalModal() {
  var modal = document.getElementById('ocal-modal');
  if (modal) { modal.hidden = true; document.body.style.overflow = ''; }
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeCalModal();
});
</script>
<?php endif; ?>

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
