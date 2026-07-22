<?php
/**
 * One Piece: Eternal · Biblioteca del Bestiario
 * Catálogo poblado desde BD (rol_bestiario). Sin datos mockup.
 * Estilos en docs/themes/ope.css (scope: ope-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-bestiario.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$data = ope_rol_cat_bestiario();
foreach ($data as &$d) { $d['tier'] = ope_rol_cat_rareza_tier($d['rareza']); }
unset($d);
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Bestiario</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca bib-bestiario">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Bestiario</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Bestiario</h1><span class="code">// criaturas y monstruos · <?php echo count($data); ?> catalogadas</span><span class="rule"></span></div></section>

<section class="reveal" id="bibApp">
  <div class="bib-toolbar">
    <div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar criatura o hábitat…" autocomplete="off"></div>
    <div class="bib-filters" id="bibFilters">
      <button class="bib-filter on" data-filt="todas">Todas</button>
      <button class="bib-filter" data-filt="Legendario">Legendario</button>
      <button class="bib-filter" data-filt="Épico">Épico</button>
      <button class="bib-filter" data-filt="Raro">Raro</button>
      <button class="bib-filter" data-filt="Común">Común</button>
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
  var pelClass = { 'Bajo':'p1','Moderado':'p2','Alto':'p3','Extremo':'p4' };

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-beast" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M8 28c0-8 6-14 14-14 4 0 7 2 9 5l7-3-3 7c1 2 1 4 1 6 0 6-5 11-12 11-3 0-6-1-8-3l-6 2 2-6c-2-2-4-5-4-8z" fill="none" stroke="currentColor" stroke-width="2.2"/><circle cx="20" cy="24" r="1.8" fill="currentColor"/><path d="M28 30c-2 2-6 2-8 0" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>';
  }

  function render(){
    var q = document.getElementById('bibSearch').value.toLowerCase().trim();
    var items = data.filter(function(p){
      if (filtro !== 'todas' && p.rareza !== filtro) return false;
      if (q && p.nombre.toLowerCase().indexOf(q) === -1 && (p.habitat||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!items.length){ grid.innerHTML = '<div class="bib-empty">No se encontraron criaturas.</div>'; return; }
    grid.innerHTML = items.map(function(p){
      var i = data.indexOf(p);
      return '<article class="bib-card" data-i="'+i+'">'
        + '<div class="bib-card-media">'+media(p)
          + '<span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.rareza)+'</span>'
          + (p.peligro?'<span class="bib-card-peligro '+(pelClass[p.peligro]||'p1')+'">'+esc(p.peligro)+'</span>':'')
        + '</div>'
        + '<div class="bib-card-body">'
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + (p.habitat?'<div class="bib-card-meta"><span>'+esc(p.habitat)+'</span></div>':'')
          + '<p class="bib-card-desc">'+esc(p.descripcion||'')+'</p>'
        + '</div></article>';
    }).join('');
  }

  function openDetail(p){
    var rows = '';
    function row(l,v){ if(v) rows += '<div class="bib-d-row"><span class="bib-d-l">'+esc(l)+'</span><span class="bib-d-v">'+esc(v)+'</span></div>'; }
    row('Rareza', p.rareza);
    row('Hábitat', p.habitat);
    row('Peligrosidad', p.peligro);
    row('Tamaño', p.tamano);
    row('Dieta', p.dieta);
    var blocks = '';
    if (p.descripcion && p.descripcion.trim()) blocks += '<div class="bib-d-block"><span class="bib-d-h">Descripción</span><p>'+esc(p.descripcion)+'</p></div>';
    detail.innerHTML =
      '<button type="button" class="bib-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="bib-d-head">'
        + '<div class="bib-d-media">'+media(p)+'</div>'
        + '<div class="bib-d-title"><h2>'+esc(p.nombre)+'</h2>'
        + '<span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.rareza)+'</span>'
        + (p.peligro?' <span class="bib-card-peligro '+(pelClass[p.peligro]||'p1')+'">Peligro: '+esc(p.peligro)+'</span>':'')+'</div>'
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
