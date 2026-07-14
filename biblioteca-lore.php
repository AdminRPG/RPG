<?php
/**
 * I-Forge · Biblioteca de Lore
 * Enciclopedia del mundo de One Piece Eternal: historia, eras, personajes,
 * facciones, ubicaciones, sistemas de poder y cronología.
 * Datos desde BD (rol_lore). Estilos en docs/themes/ope.css (scope: ope-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-lore.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$data = ope_rol_cat_lore();
$cat_labels = ope_rol_cat_lore_categoria_labels();
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
$cat_labels_json = json_encode($cat_labels, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Lore</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca bib-lore">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Lore</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca de Lore</h1><span class="code">// enciclopedia del mundo · <?php echo count($data); ?> artículos</span><span class="rule"></span></div></section>

<section class="reveal" id="bibApp">
  <div class="bib-toolbar">
    <div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar artículo de lore…" autocomplete="off"></div>
    <div class="bib-filters" id="bibFilters">
      <button class="bib-filter on" data-filt="todas">Todas</button>
      <button class="bib-filter" data-filt="cat:historia">Historia</button>
      <button class="bib-filter" data-filt="cat:eras">Eras</button>
      <button class="bib-filter" data-filt="cat:personajes">Personajes</button>
      <button class="bib-filter" data-filt="cat:facciones">Facciones</button>
      <button class="bib-filter" data-filt="cat:ubicaciones">Ubicaciones</button>
      <button class="bib-filter" data-filt="cat:sistemas">Sistemas</button>
      <button class="bib-filter" data-filt="cat:cronologia">Cronología</button>
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
  var catLabels = <?php echo $cat_labels_json; ?>;
  var filtro = 'todas';
  var grid = document.getElementById('bibGrid');
  var overlay = document.getElementById('bibOverlay');
  var detail = document.getElementById('bibDetail');

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  /** SVG decorativo por categoría */
  function loreIcon(cat){
    var icons = {
      historia:   '<svg viewBox="0 0 48 48"><rect x="8" y="6" width="32" height="38" rx="2" fill="none" stroke="currentColor" stroke-width="2.5"/><line x1="16" y1="14" x2="32" y2="14" stroke="currentColor" stroke-width="2"/><line x1="16" y1="20" x2="29" y2="20" stroke="currentColor" stroke-width="2"/><line x1="16" y1="26" x2="32" y2="26" stroke="currentColor" stroke-width="2"/><line x1="16" y1="32" x2="26" y2="32" stroke="currentColor" stroke-width="2"/></svg>',
      eras:        '<svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="17" fill="none" stroke="currentColor" stroke-width="2.5"/><polyline points="24,12 24,24 34,28" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>',
      personajes:  '<svg viewBox="0 0 48 48"><circle cx="24" cy="16" r="8" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M10 42c0-10 6-16 14-16s14 6 14 16" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>',
      facciones:   '<svg viewBox="0 0 48 48"><path d="M24 6L8 18v18l16 8 16-8V18z" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="24" cy="22" r="5" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
      ubicaciones: '<svg viewBox="0 0 48 48"><path d="M24 6C17 6 11 11 11 19c0 10 13 25 13 25s13-15 13-25c0-8-6-13-13-13z" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="24" cy="19" r="4" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
      sistemas:    '<svg viewBox="0 0 48 48"><polygon points="24,4 10,14 24,24 38,14" fill="none" stroke="currentColor" stroke-width="2.5"/><polygon points="10,14 10,34 24,44 24,24" fill="none" stroke="currentColor" stroke-width="2"/><polygon points="38,14 38,34 24,44 24,24" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
      cronologia:  '<svg viewBox="0 0 48 48"><line x1="8" y1="34" x2="40" y2="34" stroke="currentColor" stroke-width="2.5"/><circle cx="14" cy="34" r="5" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="26" cy="34" r="5" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="38" cy="34" r="5" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>'
    };
    return icons[cat] || icons['historia'];
  }

  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-lore" aria-hidden="true">'+loreIcon(p.categoria)+'</span>';
  }

  function match(p){
    if (filtro === 'todas') return true;
    if (filtro.indexOf('cat:') === 0) return p.categoria === filtro.slice(4);
    return true;
  }

  function render(){
    var q = document.getElementById('bibSearch').value.toLowerCase().trim();
    var items = data.filter(function(p){
      if (!match(p)) return false;
      if (q && p.nombre.toLowerCase().indexOf(q) === -1
          && (p.resumen||'').toLowerCase().indexOf(q) === -1
          && (p.subcategoria||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!items.length){ grid.innerHTML = '<div class="bib-empty">No se encontraron artículos de lore.</div>'; return; }
    grid.innerHTML = items.map(function(p){
      var i = data.indexOf(p);
      return '<article class="bib-card cat-'+esc(p.categoria)+'" data-i="'+i+'">'
        + '<div class="bib-card-media">'+media(p)
          + '<span class="bib-card-badge cat-'+esc(p.categoria)+'">'+esc(catLabels[p.categoria]||p.categoria)+'</span>'
        + '</div>'
        + '<div class="bib-card-body">'
          + (p.subcategoria ? '<span class="bib-card-kicker">'+esc(p.subcategoria)+'</span>' : '')
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + (p.resumen ? '<p class="bib-card-desc">'+esc(p.resumen)+'</p>' : '')
        + '</div></article>';
    }).join('');
  }

  function openDetail(p){
    var rows = '';
    function row(l,v){ if(v) rows += '<div class="bib-d-row"><span class="bib-d-l">'+esc(l)+'</span><span class="bib-d-v">'+esc(v)+'</span></div>'; }
    row('Categoría', catLabels[p.categoria]||p.categoria);
    row('Subcategoría', p.subcategoria);

    detail.innerHTML =
      '<button type="button" class="bib-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="bib-d-head cat-'+esc(p.categoria)+'">'
        + '<div class="bib-d-media">'+media(p)+'</div>'
        + '<div class="bib-d-title"><h2>'+esc(p.nombre)+'</h2>'
        + '<span class="ope-tag">'+esc(catLabels[p.categoria]||p.categoria)+'</span>'
        + (p.subcategoria ? '<span class="bib-d-apodo">'+esc(p.subcategoria)+'</span>' : '')
        + '</div>'
      + '</div>'
      + '<div class="bib-d-grid">'+rows+'</div>'
      + '<div class="bib-d-block bib-lore-content">'+ (p.contenido||'') +'</div>';
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
