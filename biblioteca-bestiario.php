<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-bestiario.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
$data = [
  ['n'=>'Rey de las Bestias','ti'=>'Legendario','h'=>'Islas de la Calma','p'=>'??','d'=>'Gigantesco felino envuelto en vapor. Se cree que es una de las criaturas más antiguas del Grand Line.'],
  ['n'=>'Kraken del Abismo','ti'=>'Épico','h'=>'Fosa de los Malditos','p'=>'Extremo','d'=>'Pulpo colosal de 200 m. Sus tentáculos pueden hundir un barco de guerra en segundos.'],
  ['n'=>'Dragón Marino (Madre)','ti'=>'Épico','h'=>'Calmas Belas','p'=>'Alto','d'=>'Dragón marino de 80 m. Protectora de sus crías. Su escama vale una fortuna.'],
  ['n'=>'Mantícora Coralina','ti'=>'Raro','h'=>'Arrecife Colmillos','p'=>'Moderado','d'=>'Cuerpo de león con cola de escorpión. Su caparazón de coral la camufla entre los arrecifes.'],
  ['n'=>'Lobo de las Nieves','ti'=>'Raro','h'=>'Pico Invernal','p'=>'Moderado','d'=>'Manada de lobos con pelaje blanco. Su aullido congela el agua. Atacan en grupo.'],
  ['n'=>'Cocodrilo de Arena','ti'=>'Común','h'=>'Desierto de los Suspiros','p'=>'Bajo','d'=>'Reptil de 5 m. Se desliza bajo la arena. Ataca por sorpresa a viajeros solitarios.'],
  ['n'=>'Ave Fénix Menor','ti'=>'Épico','h'=>'Volcán de la Luna','p'=>'Alto','d'=>'Ave de fuego pequeña. Sus lágrimas curan heridas. Extremadamente esquiva.'],
  ['n'=>'Serpiente Marina (Hijo)','ti'=>'Común','h'=>'Calmas Belas','p'=>'Bajo','d'=>'Cría de dragón marino. 10 m. Curiosa pero peligrosa si se siente amenazada.'],
  ['n'=>'Gorila de Hierro','ti'=>'Raro','h'=>'Selva de Hierro','p'=>'Alto','d'=>'Primate de 4 m con piel metálica. Golpea como un martillo. Su territorio es sagrado.'],
  ['n'=>'Medusa Titánica','ti'=>'Épico','h'=>'Fosa de los Malditos','p'=>'Extremo','d'=>'Medusa de 30 m. Sus tentáculos paralizan instantáneamente. Brilla en la oscuridad.'],
];
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca bestiario</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca bestiario</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca bestiario</h1><span class="code">// criaturas y monstruos</span><span class="rule"></span></div></section>
<section class="reveal" id="bibApp">
<div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar criatura..."></div>
<div class="bib-filters" id="bibFilters">
  <button class="bib-filter on" data-filt="todas">Todos</button>
  <button class="bib-filter" data-filt="Legendario">⭐ Legendario</button>
  <button class="bib-filter" data-filt="Épico">✨ Épico</button>
  <button class="bib-filter" data-filt="Raro">🔹 Raro</button>
  <button class="bib-filter" data-filt="Común">🔸 Común</button>
</div>
<div class="bib-grid" id="bibGrid"></div>
</section>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
(function(){
  var data = <?php echo $data_json; ?>;
  var filtro = 'todas';

  function render() {
    var q = document.getElementById('bibSearch').value.toLowerCase();
    var items = data.filter(function(p){
      if (filtro !== 'todas' && p.ti !== filtro) return false;
      if (q && p.n.toLowerCase().indexOf(q) === -1 && (p.h||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    var html = '';
    items.forEach(function(p){
      var tCls = {Legendario:'t5',Épico:'t4',Raro:'t3',Común:'t2'}[p.ti]||'t2';
      var peligro = {Extremo:'🔴',Alto:'🟠',Moderado:'🟡',Bajo:'🟢'}[p.p]||'⚪';
      html += '<div class="bib-card">'
        + '<div class="bib-card-head"><span class="bib-card-tier ' + tCls + '">' + p.ti + '</span></div>'
        + '<div class="bib-card-nom">' + p.n + '</div>'
        + '<div class="bib-card-extra"><span>🌍 <b>' + p.h + '</b></span></div>'
        + '<div class="bib-card-extra"><span>' + peligro + ' Peligro: <b>' + p.p + '</b></span></div>'
        + '<div class="bib-card-desc">' + p.d + '</div>'
        + '</div>';
    });
    document.getElementById('bibGrid').innerHTML = html || '<div class="bib-empty">No se encontraron criaturas.</div>';
  }

  document.getElementById('bibSearch').addEventListener('input', render);
  document.getElementById('bibFilters').addEventListener('click', function(e){
    var btn = e.target.closest('.bib-filter');
    if (!btn) return;
    document.querySelectorAll('.bib-filter').forEach(function(b){ b.classList.remove('on'); });
    btn.classList.add('on');
    filtro = btn.getAttribute('data-filt');
    render();
  });
  render();
})();
</script>
</body>
</html>
