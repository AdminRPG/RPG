<?php
/**
 * I-Forge · Tripulaciones
 * Catálogo de tripulaciones poblado desde BD (rol_tripulaciones). Sin mockup.
 * Estilos en docs/themes/ope.css (scope: ope-pg-tripulacion).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tripulacion.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);
$loggedin = $uid > 0;

$FACCIONES     = ope_rol_facciones();
$tripulaciones = ope_rol_cat_tripulaciones();
$active_pid    = $loggedin ? ope_rol_pid_activo($uid) : 0;
$mi_tripulacion = ($active_pid > 0) ? ope_rol_cat_tripulacion_de_personaje($active_pid) : null;
$tramite_pendiente = ($active_pid > 0 && !$mi_tripulacion) ? ope_rol_cat_tripulacion_tramite_pendiente($active_pid) : null;

$fac_labels = array();
foreach ($FACCIONES as $slug => $f) { $fac_labels[$slug] = $f['nombre']; }

$data_json = json_encode($tripulaciones, JSON_UNESCAPED_UNICODE);
$fac_labels_json = json_encode($fac_labels, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tripulaciones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tripulacion">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Tripulaciones</b></div></div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Tripulaciones</h1>
      <span class="code">// navega con los tuyos</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($mi_tripulacion):
  $mt = $mi_tripulacion['tripulacion'];
  $rol_lbl = $mi_tripulacion['rol'] === 'capitan' ? 'Capitán' : 'Tripulante';
?>
  <section class="reveal">
    <div class="trip-mine fac-<?php echo htmlspecialchars_uni($mt['faccion']); ?>">
      <div class="trip-mine-body">
        <span class="trip-mine-kicker">Tu tripulación · <?php echo htmlspecialchars_uni($rol_lbl); ?></span>
        <h2 class="trip-mine-nom"><?php echo htmlspecialchars_uni($mt['nombre']); ?></h2>
        <?php if (!empty($mt['lema'])): ?><p class="trip-mine-lema">«<?php echo htmlspecialchars_uni($mt['lema']); ?>»</p><?php endif; ?>
        <div class="trip-mine-stats">
          <span><b><?php echo (int) $mt['nivel']; ?></b> Nivel</span>
          <span><b><?php echo (int) $mt['miembros']; ?></b> Miembros</span>
        </div>
      </div>
    </div>
  </section>
<?php elseif ($tramite_pendiente): ?>
  <section class="reveal">
    <div class="trip-join trip-join-pending">
      <div class="trip-join-text">
        <h2>Trámite en revisión</h2>
        <p>Tu solicitud de tripulación está pendiente. El staff la revisará pronto.</p>
      </div>
    </div>
  </section>
<?php elseif ($loggedin): ?>
  <section class="reveal">
    <div class="trip-join">
      <div class="trip-join-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 18l9-14 9 14"/><path d="M3 18h18"/><path d="M12 4v14"/></svg></div>
      <div class="trip-join-text">
        <h2>Aún no tienes tripulación</h2>
        <p>Navegar solo está bien, pero una buena tripulación lo cambia todo. Funda la tuya o solicita unirte a una existente mediante trámite.</p>
      </div>
      <div class="trip-join-actions">
        <a href="<?php echo $bburl; ?>/tramite-tripulacion.php?modo=fundar" class="btn btn-hot">Fundar tripulación</a>
        <a href="<?php echo $bburl; ?>/tramite-tripulacion.php?modo=unirse" class="btn btn-ghost">Unirse a una</a>
      </div>
    </div>
  </section>
<?php endif; ?>

  <section class="reveal">
    <div class="shead shead-sec">
      <h2>Tripulaciones activas</h2>
      <span class="code">// <?php echo count($tripulaciones); ?> registradas</span>
      <span class="rule"></span>
    </div>
    <div class="trip-grid" id="tripGrid"></div>
  </section>

</div>

<div class="trip-overlay" id="tripOverlay" hidden><div class="trip-detail" id="tripDetail"></div></div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var data = <?php echo $data_json; ?>;
  var facLabels = <?php echo $fac_labels_json; ?>;
  var grid = document.getElementById('tripGrid');
  var overlay = document.getElementById('tripOverlay');
  var detail = document.getElementById('tripDetail');

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function facLbl(s){ return facLabels[s] || (s? s.charAt(0).toUpperCase()+s.slice(1) : ''); }
  function media(t){
    if (t.imagen) return '<img src="'+esc(t.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="trip-media-flag" aria-hidden="true"><svg viewBox="0 0 48 48"><circle cx="24" cy="20" r="11" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="M18 16l12 8M30 16l-12 8" stroke="currentColor" stroke-width="2.4"/><path d="M13 30l-3 12M35 30l3 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></span>';
  }

  function render(){
    if (!data.length){ grid.innerHTML = '<div class="trip-empty">Todavía no hay tripulaciones registradas.</div>'; return; }
    grid.innerHTML = data.map(function(t){
      var i = data.indexOf(t);
      return '<article class="trip-card fac-'+esc(t.faccion)+'" data-i="'+i+'">'
        + '<div class="trip-card-media">'+media(t)+'<span class="ope-tag ope-tag-'+esc(t.faccion)+' trip-card-fac">'+esc(facLbl(t.faccion))+'</span></div>'
        + '<div class="trip-card-body">'
          + '<h3 class="trip-card-nom">'+esc(t.nombre)+'</h3>'
          + '<div class="trip-card-cap"><span class="trip-card-cap-l">Capitán</span><span class="trip-card-cap-n">'+esc(t.capitan||'—')+'</span></div>'
          + (t.lema?'<p class="trip-card-lema">«'+esc(t.lema)+'»</p>':'')
          + '<div class="trip-card-stats">'
            + '<div class="trip-card-stat"><b>'+esc(t.nivel||0)+'</b><span>Nivel</span></div>'
            + '<div class="trip-card-stat"><b>'+esc(t.miembros||0)+'</b><span>Miembros</span></div>'
          + '</div>'
          + '<button type="button" class="btn btn-ghost btn-sm trip-card-btn">Ver tripulación</button>'
        + '</div></article>';
    }).join('');
  }

  function openDetail(t){
    detail.innerHTML =
      '<button type="button" class="trip-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="trip-d-head fac-'+esc(t.faccion)+'">'
        + '<div class="trip-d-media">'+media(t)+'</div>'
        + '<div class="trip-d-title"><h2>'+esc(t.nombre)+'</h2>'
        + '<span class="ope-tag ope-tag-'+esc(t.faccion)+'">'+esc(facLbl(t.faccion))+'</span></div>'
      + '</div>'
      + (t.lema?'<p class="trip-d-lema">«'+esc(t.lema)+'»</p>':'')
      + '<div class="trip-d-grid">'
        + '<div class="trip-d-stat"><b>'+esc(t.nivel||0)+'</b><span>Nivel de flota</span></div>'
        + '<div class="trip-d-stat"><b>'+esc(t.miembros||0)+'</b><span>Miembros</span></div>'
        + '<div class="trip-d-stat"><b>'+esc(t.capitan||'—')+'</b><span>Capitán</span></div>'
      + '</div>'
      + (t.descripcion?'<div class="trip-d-block"><span class="trip-d-h">Sobre la tripulación</span><p>'+esc(t.descripcion)+'</p></div>':'');
    overlay.hidden = false; document.body.classList.add('trip-no-scroll');
    requestAnimationFrame(function(){ detail.classList.add('in'); });
  }
  function closeDetail(){ detail.classList.remove('in'); overlay.hidden = true; document.body.classList.remove('trip-no-scroll'); }

  grid.addEventListener('click', function(e){ var c = e.target.closest('.trip-card'); if(!c) return; openDetail(data[+c.getAttribute('data-i')]); });
  overlay.addEventListener('click', function(e){ if(e.target===overlay || e.target.closest('.trip-d-close')) closeDetail(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !overlay.hidden) closeDetail(); });
  render();

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
})();
</script>
</body>
</html>
