<?php
/**
 * I-Forge · Biblioteca de Personajes (jugadores)
 * Catálogo poblado desde BD (rol_personajes, es_npc=0, estado=aprobado).
 * Sin datos mockup. Estilos en docs/themes/ope.css (scope: ope-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-personajes.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$FACCIONES = ope_rol_facciones();
$data = ope_rol_cat_personajes_publicos();

$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
$fac_labels = array();
foreach ($FACCIONES as $slug => $f) { $fac_labels[$slug] = $f['nombre']; }
$fac_labels_json = json_encode($fac_labels, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de personajes</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca bib-personajes">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de personajes</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca de personajes</h1><span class="code">// expedientes de jugadores · <?php echo count($data); ?> registrados</span><span class="rule"></span></div></section>

<section class="reveal" id="bibApp">
  <div class="bib-toolbar">
    <div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar por nombre o tripulación…" autocomplete="off"></div>
    <div class="bib-filters" id="bibFilters">
      <button class="bib-filter on" data-filt="todas">Todas</button>
<?php foreach ($FACCIONES as $slug => $f): ?>
      <button class="bib-filter" data-filt="<?php echo htmlspecialchars_uni($slug); ?>"><?php echo htmlspecialchars_uni($f['nombre']); ?></button>
<?php endforeach; ?>
    </div>
  </div>
  <div class="bib-grid" id="bibGrid"></div>
</section>
</div>

<div class="bib-overlay" id="bibOverlay" hidden><div class="bib-detail" id="bibDetail"></div></div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var data = <?php echo $data_json; ?>;
  var facLabels = <?php echo $fac_labels_json; ?>;
  var BB = '<?php echo $bburl; ?>';
  var filtro = 'todas';
  var grid = document.getElementById('bibGrid');
  var overlay = document.getElementById('bibOverlay');
  var detail = document.getElementById('bibDetail');

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function facLbl(s){ return facLabels[s] || (s? s.charAt(0).toUpperCase()+s.slice(1) : ''); }
  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-ini">'+esc((p.nombre||'?').charAt(0))+'</span>';
  }

  function render(){
    var q = document.getElementById('bibSearch').value.toLowerCase().trim();
    var items = data.filter(function(p){
      if (filtro !== 'todas' && p.faccion_slug !== filtro) return false;
      if (q && p.nombre.toLowerCase().indexOf(q) === -1 && (p.concepto||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!items.length){ grid.innerHTML = '<div class="bib-empty">No se encontraron personajes.</div>'; return; }
    grid.innerHTML = items.map(function(p){
      var i = data.indexOf(p);
      return '<article class="bib-card fac-'+esc(p.faccion_slug||'civil')+'" data-i="'+i+'">'
        + '<div class="bib-card-media">'+media(p)
          + (p.faccion_slug ? '<span class="bib-card-badge fac-'+esc(p.faccion_slug)+'">'+esc(facLbl(p.faccion_slug))+'</span>' : '')
          + '<span class="bib-card-rank">'+esc(p.rango||'—')+'</span>'
        + '</div>'
        + '<div class="bib-card-body">'
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + '<div class="bib-card-meta"><span>Nivel '+esc(p.nivel)+'</span>'+(p.rango_faccion?'<span>'+esc(p.rango_faccion)+'</span>':'')+'</div>'
          + '<p class="bib-card-desc">'+esc(p.concepto||'Sin concepto registrado.')+'</p>'
        + '</div></article>';
    }).join('');
  }

  function openDetail(p){
    var rows = '';
    function row(l,v){ if(v) rows += '<div class="bib-d-row"><span class="bib-d-l">'+esc(l)+'</span><span class="bib-d-v">'+esc(v)+'</span></div>'; }
    row('Facción', facLbl(p.faccion_slug));
    row('Rango de facción', p.rango_faccion);
    row('Raza', p.raza);
    row('Edad', p.edad); row('Género', p.genero);
    row('Nivel', p.nivel); row('Rango', p.rango);
    var blocks = '';
    function block(l,v){ if(v && v.trim()) blocks += '<div class="bib-d-block"><span class="bib-d-h">'+esc(l)+'</span><p>'+esc(v)+'</p></div>'; }
    block('Concepto', p.concepto);
    block('Personalidad', p.personalidad);
    block('Apariencia', p.apariencia);
    detail.innerHTML =
      '<button type="button" class="bib-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="bib-d-head fac-'+esc(p.faccion_slug||'civil')+'">'
        + '<div class="bib-d-media">'+media(p)+'</div>'
        + '<div class="bib-d-title"><h2>'+esc(p.nombre)+'</h2>'+(p.apodo?'<span class="bib-d-apodo">«'+esc(p.apodo)+'»</span>':'')
        + (p.faccion_slug?'<span class="ope-tag ope-tag-'+esc(p.faccion_slug)+'">'+esc(facLbl(p.faccion_slug))+'</span>':'')+'</div>'
      + '</div>'
      + '<div class="bib-d-grid">'+rows+'</div>'
      + blocks
      + '<div class="bib-d-actions"><a class="btn btn-hot" href="'+BB+'/ficha.php?pid='+p.pid+'">Ver ficha completa</a></div>';
    overlay.hidden = false; document.body.classList.add('bib-no-scroll');
    requestAnimationFrame(function(){ detail.classList.add('in'); });
  }
  function closeDetail(){ detail.classList.remove('in'); overlay.hidden = true; document.body.classList.remove('bib-no-scroll'); }

  grid.addEventListener('click', function(e){ var c = e.target.closest('.bib-card'); if(!c) return; openDetail(data[+c.getAttribute('data-i')]); });
  overlay.addEventListener('click', function(e){ if(e.target===overlay || e.target.closest('.bib-d-close')) closeDetail(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && !overlay.hidden) closeDetail(); });
  document.getElementById('bibSearch').addEventListener('input', render);
  document.getElementById('bibFilters').addEventListener('click', function(e){
    var b = e.target.closest('.bib-filter'); if(!b) return;
    this.querySelectorAll('.bib-filter').forEach(function(x){ x.classList.remove('on'); });
    b.classList.add('on'); filtro = b.getAttribute('data-filt'); render();
  });
  render();

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
})();
</script>
</body>
</html>
