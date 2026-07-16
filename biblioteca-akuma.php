<?php
/**
 * I-Forge · Biblioteca de Akuma no Mi (Frutas del Diablo)
 * Catálogo poblado desde BD (rol_akuma). Sin datos mockup.
 * Estilos en docs/themes/gbe.css (scope: gbe-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-akuma.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$data = gbe_rol_cat_akuma();
foreach ($data as &$d) { $d['tier'] = gbe_rol_cat_rareza_tier($d['rareza']); }
unset($d);
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Akuma no Mi</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-biblioteca bib-akuma">
<?php echo gbe_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Akuma no Mi</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Akuma no Mi</h1><span class="code">// catálogo de frutas del diablo · <?php echo count($data); ?> registradas</span><span class="rule"></span></div></section>

<section class="reveal" id="bibApp">
  <div class="bib-toolbar">
    <div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar fruta o usuario…" autocomplete="off"></div>
    <div class="bib-filters" id="bibFilters">
      <button class="bib-filter on" data-filt="todas">Todas</button>
      <button class="bib-filter" data-filt="tipo:paramecia">Paramecia</button>
      <button class="bib-filter" data-filt="tipo:zoa">Zoan</button>
      <button class="bib-filter" data-filt="tipo:logia">Logia</button>
      <button class="bib-filter" data-filt="rar:Legendario">Legendario</button>
      <button class="bib-filter" data-filt="rar:Épico">Épico</button>
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
  var tipoLbl = { paramecia:'Paramecia', zoa:'Zoan', logia:'Logia' };

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-fruit" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 12c4-6 14-6 16 2 2 8-6 20-16 22C14 34 6 22 8 14c2-8 12-8 16-2z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M24 12c-1-3 1-6 4-7" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M14 20c3 1 5 4 5 8M30 18c-2 2-3 5-2 9" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>';
  }

  function match(p){
    if (filtro === 'todas') return true;
    if (filtro.indexOf('tipo:') === 0) return p.tipo === filtro.slice(5);
    if (filtro.indexOf('rar:') === 0) return p.rareza === filtro.slice(4);
    return true;
  }
  function render(){
    var q = document.getElementById('bibSearch').value.toLowerCase().trim();
    var items = data.filter(function(p){
      if (!match(p)) return false;
      if (q && p.nombre.toLowerCase().indexOf(q) === -1 && (p.usuario||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!items.length){ grid.innerHTML = '<div class="bib-empty">No se encontraron frutas.</div>'; return; }
    grid.innerHTML = items.map(function(p){
      var i = data.indexOf(p);
      return '<article class="bib-card tipo-'+esc(p.tipo)+'" data-i="'+i+'">'
        + '<div class="bib-card-media">'+media(p)
          + '<span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.rareza)+'</span>'
        + '</div>'
        + '<div class="bib-card-body">'
          + '<span class="bib-card-kicker">'+esc(tipoLbl[p.tipo]||p.tipo)+'</span>'
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + '<p class="bib-card-desc">'+esc(p.descripcion||'')+'</p>'
          + (p.usuario?'<div class="bib-card-meta"><span>Usuario: <b>'+esc(p.usuario)+'</b></span></div>':'')
        + '</div></article>';
    }).join('');
  }

  function openDetail(p){
    var rows = '';
    function row(l,v){ if(v) rows += '<div class="bib-d-row"><span class="bib-d-l">'+esc(l)+'</span><span class="bib-d-v">'+esc(v)+'</span></div>'; }
    row('Tipo', tipoLbl[p.tipo]||p.tipo);
    row('Rareza', p.rareza);
    row('Usuario actual', p.usuario);
    var blocks = '';
    function block(l,v){ if(v && v.trim()) blocks += '<div class="bib-d-block"><span class="bib-d-h">'+esc(l)+'</span><p>'+esc(v)+'</p></div>'; }
    block('Poder', p.descripcion);
    block('Despertar', p.despertar);
    block('Debilidad', p.debilidad);
    detail.innerHTML =
      '<button type="button" class="bib-d-close" aria-label="Cerrar">✕</button>'
      + '<div class="bib-d-head tipo-'+esc(p.tipo)+'">'
        + '<div class="bib-d-media">'+media(p)+'</div>'
        + '<div class="bib-d-title"><h2>'+esc(p.nombre)+'</h2>'
        + '<span class="gbe-tag">'+esc(tipoLbl[p.tipo]||p.tipo)+'</span> <span class="bib-card-badge '+esc(p.tier)+'">'+esc(p.rareza)+'</span></div>'
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
