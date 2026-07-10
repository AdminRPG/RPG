<?php
/**
 * I-Forge · Biblioteca de Estilos de lucha
 * Catálogo poblado desde BD (rol_estilos). Sin datos mockup.
 * Estilos en docs/themes/ope.css (scope: ope-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-estilos.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$data = ope_rol_cat_estilos();
foreach ($data as &$d) { $d['tier'] = ope_rol_cat_rareza_tier($d['dificultad']); }
unset($d);
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Estilos de lucha</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca bib-estilos">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Estilos de lucha</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Estilos de lucha</h1><span class="code">// técnicas y habilidades · <?php echo count($data); ?> documentados</span><span class="rule"></span></div></section>

<section class="reveal" id="bibApp">
  <div class="bib-toolbar">
    <div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar estilo…" autocomplete="off"></div>
    <div class="bib-filters" id="bibFilters">
      <button class="bib-filter on" data-filt="todas">Todos</button>
      <button class="bib-filter" data-filt="Combate">Combate</button>
      <button class="bib-filter" data-filt="Defensa">Defensa</button>
      <button class="bib-filter" data-filt="Percepción">Percepción</button>
      <button class="bib-filter" data-filt="Apoyo">Apoyo</button>
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
  var filtro = 'todas';
  var grid = document.getElementById('bibGrid');
  var overlay = document.getElementById('bibOverlay');
  var detail = document.getElementById('bibDetail');

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-style" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M10 38l6-6M32 16l6-6M14 10l24 24M34 10l-6 6M10 34l6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></span>';
  }

  function render(){
    var q = document.getElementById('bibSearch').value.toLowerCase().trim();
    var items = data.filter(function(p){
      if (filtro !== 'todas' && p.categoria !== filtro) return false;
      if (q && p.nombre.toLowerCase().indexOf(q) === -1 && (p.descripcion||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!items.length){ grid.innerHTML = '<div class="bib-empty">No se encontraron estilos.</div>'; return; }
    grid.innerHTML = items.map(function(p){
      var i = data.indexOf(p);
      return '<article class="bib-card" data-i="'+i+'">'
        + '<div class="bib-card-media">'+media(p)
          + '<span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.dificultad)+'</span>'
        + '</div>'
        + '<div class="bib-card-body">'
          + '<span class="bib-card-kicker">'+esc(p.categoria)+'</span>'
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + '<p class="bib-card-desc">'+esc(p.descripcion||'')+'</p>'
          + (p.usuarios?'<div class="bib-card-meta"><span>'+esc(p.usuarios)+'</span></div>':'')
        + '</div></article>';
    }).join('');
  }

  function openDetail(p){
    var rows = '';
    function row(l,v){ if(v) rows += '<div class="bib-d-row"><span class="bib-d-l">'+esc(l)+'</span><span class="bib-d-v">'+esc(v)+'</span></div>'; }
    row('Categoría', p.categoria);
    row('Dificultad', p.dificultad);
    row('Practicantes', p.usuarios);
    var blocks = '';
    function block(l,v){ if(v && v.trim()) blocks += '<div class="bib-d-block"><span class="bib-d-h">'+esc(l)+'</span><p>'+esc(v)+'</p></div>'; }
    block('Descripción', p.descripcion);
    block('Técnicas destacadas', p.tecnicas);
    detail.innerHTML =
      '<button type="button" class="bib-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="bib-d-head">'
        + '<div class="bib-d-media">'+media(p)+'</div>'
        + '<div class="bib-d-title"><h2>'+esc(p.nombre)+'</h2>'
        + '<span class="ope-tag">'+esc(p.categoria)+'</span> <span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.dificultad)+'</span></div>'
      + '</div>'
      + '<div class="bib-d-grid">'+rows+'</div>'
      + blocks;
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
