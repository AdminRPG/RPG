<?php
/**
 * One Piece: Eternal · Biblioteca de Akuma no Mi (Frutas del Diablo)
 * Catálogo poblado desde BD (rol_akuma). Sin datos mockup.
 * Estilos en docs/themes/ope.css (scope: ope-pg-biblioteca).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-akuma.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$is_staff = !empty($mybb->user['ope_is_staff']) || (int) ($mybb->user['ope_staff_level'] ?? 0) >= 1;
$data = array();
foreach (ope_rol_cat_akuma() as $row) {
    $data[] = function_exists('ope_fruta_norm')
        ? ope_fruta_norm((array) $row, $is_staff)
        : (array) $row;
}
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Akuma no Mi</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca bib-akuma">
<?php echo ope_rol_navbar_html(); ?>
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
      <button class="bib-filter" data-filt="estado:libre">Libres</button>
      <button class="bib-filter" data-filt="estado:ocupada">En uso</button>
    </div>
  </div>
  <div class="bib-grid" id="bibGrid"></div>
</section>
</div>

<?php echo ope_fruta_modal_assets($bburl); ?>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var BB = <?php echo json_encode($bburl, JSON_UNESCAPED_SLASHES); ?>;
  var data = <?php echo $data_json; ?>;
  var filtro = 'todas';
  var grid = document.getElementById('bibGrid');
  var tipoLbl = { paramecia:'Paramecia', zoa:'Zoan', logia:'Logia' };

  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function media(p){
    if (p.imagen) return '<img src="'+esc(p.imagen)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.remove()">';
    return '<span class="bib-media-fruit" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 12c4-6 14-6 16 2 2 8-6 20-16 22C14 34 6 22 8 14c2-8 12-8 16-2z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M24 12c-1-3 1-6 4-7" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M14 20c3 1 5 4 5 8M30 18c-2 2-3 5-2 9" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>';
  }

  function match(p){
    if (filtro === 'todas') return true;
    if (filtro.indexOf('tipo:') === 0) return p.tipo_base === filtro.slice(5);
    if (filtro.indexOf('tier:') === 0) return String(p.tier) === filtro.slice(5);
    if (filtro === 'estado:libre') return !(p.ocupada_pid > 0);
    if (filtro === 'estado:ocupada') return p.ocupada_pid > 0;
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
      var ownerText = p.ocupada_pid > 0
        ? '<a href="' + BB + '/ficha.php?pid=' + p.ocupada_pid + '" onclick="event.stopPropagation();" style="color:inherit;text-decoration:underline;"><b>' + esc(p.usuario || ('Personaje #' + p.ocupada_pid)) + '</b></a>'
        : '<b>' + esc(p.usuario) + '</b>';
      var usuHtml = (p.ocupada_pid > 0 || p.usuario)
        ? '<div class="bib-card-meta"><span class="ope-tag-usada">En uso · ' + ownerText + '</span></div>'
        : '<div class="bib-card-meta"><span class="ope-tag-libre">Libre</span></div>';
      return '<article class="bib-card tipo-'+esc(p.tipo_base)+'" data-i="'+i+'" tabindex="0" role="button" aria-label="'+esc(p.nombre)+'">'
        + '<div class="bib-card-media">'+media(p)
          + '<span class="bib-card-badge t'+esc(p.tier)+'">Tier '+esc(p.tier_roman)+'</span>'
        + '</div>'
        + '<div class="bib-card-body">'
          + '<span class="bib-card-kicker">'+esc(tipoLbl[p.tipo_base]||p.tipo)+' · TEM+<b>'+esc(p.secundario||'—')+'</b></span>'
          + '<h3 class="bib-card-nom">'+esc(p.nombre)+'</h3>'
          + '<p class="bib-card-desc">'+esc(p.desc||'')+'</p>'
          + usuHtml
        + '</div></article>';
    }).join('');
  }

  function openCard(c){ if(window.OPEFruta){ OPEFruta.open(data[+c.getAttribute('data-i')]); } }
  grid.addEventListener('click', function(e){ var c = e.target.closest('.bib-card'); if(c) openCard(c); });
  grid.addEventListener('keydown', function(e){ if(e.key!=='Enter'&&e.key!==' ')return; var c=e.target.closest('.bib-card'); if(c){ e.preventDefault(); openCard(c);} });
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
