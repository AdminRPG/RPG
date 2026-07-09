<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-personajes.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
$facs = ['pirata','marine','revolucionario','gobierno','cazarrecompensas','civil'];
$data = [
  ['n'=>'Monkey D. Luffy','f'=>'pirata','l'=>980,'c'=>'Sombrero de Paja','e'=>'Activo','d'=>'Capitán de los Sombrero de Paja. Portador de la Gomu Gomu no Mi. Aspira a ser el Rey de los Piratas.'],
  ['n'=>'Roronoa Zoro','f'=>'pirata','l'=>950,'c'=>'Sombrero de Paja','e'=>'Activo','d'=>'Espadachín de los Sombrero de Paja. Tres espadas. Aspira a ser el mejor espadachín del mundo.'],
  ['n'=>'Nami','f'=>'pirata','l'=>820,'c'=>'Sombrero de Paja','e'=>'Activo','d'=>'Navegante de los Sombrero de Paja. Su sueño es dibujar el mapa del mundo entero.'],
  ['n'=>'Trafalgar Law','f'=>'pirata','l'=>920,'c'=>'Heart','e'=>'Activo','d'=>'Capitán de los Heart. Portador de la Ope Ope no Mi. Antiguo Shichibukai.'],
  ['n'=>'Koby','f'=>'marine','l'=>450,'c'=>'Marine HQ','e'=>'Activo','d'=>'Marine del cuartel general. Entrenado por Garp. Aspira a ser Almirante.'],
  ['n'=>'Sabo','f'=>'revolucionario','l'=>900,'c'=>'Ejército Revolucionario','e'=>'Activo','d'=>'Jefe de Estado Mayor Revolucionario. Portador de la Mera Mera no Mi. Hermano de Luffy.'],
  ['n'=>'Rob Lucci','f'=>'gobierno','l'=>860,'c'=>'Cipher Pol 0','e'=>'Activo','d'=>'Agente del CP0. Portador de la Neko Neko no Mi (Modelo Leopardo).'],
  ['n'=>'Jewelry Bonney','f'=>'pirata','l'=>750,'c'=>'Bonney','e'=>'Activo','d'=>'Capitana de los Bonney. Portadora de la Toshi Toshi no Mi.'],
  ['n'=>'Smoker','f'=>'marine','l'=>700,'c'=>'Marine G-5','e'=>'Activo','d'=>'Vicealmirante Marine. Portador de la Moku Moku no Mi. Persigue a Luffy.'],
  ['n'=>'Dracule Mihawk','f'=>'pirata','l'=>1050,'c'=>'','e'=>'Activo','d'=>'El mejor espadachín del mundo. Antiguo Shichibukai. Portador de la espada negra Yoru.'],
];
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
$facs_json = json_encode($facs, JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca personajes</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca personajes</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca personajes</h1><span class="code">// galería de personajes jugadores</span><span class="rule"></span></div></section>
<section class="reveal" id="bibApp">
<div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar personaje..."></div>
<div class="bib-filters" id="bibFilters">
  <button class="bib-filter on" data-filt="todas">Todas</button>
  <button class="bib-filter" data-filt="pirata">🏴‍☠️ Pirata</button>
  <button class="bib-filter" data-filt="marine">⚓ Marine</button>
  <button class="bib-filter" data-filt="revolucionario">🔥 Revolucionario</button>
  <button class="bib-filter" data-filt="gobierno">🏛️ Gobierno</button>
  <button class="bib-filter" data-filt="cazarrecompensas">🎯 Cazarecompensas</button>
  <button class="bib-filter" data-filt="civil">👤 Civil</button>
</div>
<div class="bib-grid" id="bibGrid"></div>
</section>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
(function(){
  var data = <?php echo $data_json; ?>;
  var facs = <?php echo $facs_json; ?>;
  var filtro = 'todas';

  function render() {
    var q = document.getElementById('bibSearch').value.toLowerCase();
    var items = data.filter(function(p){
      if (filtro !== 'todas' && p.f !== filtro) return false;
      if (q && p.n.toLowerCase().indexOf(q) === -1 && (p.c||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    var html = '';
    items.forEach(function(p){
      var facLabel = {pirata:'🏴‍☠️ Pirata',marine:'⚓ Marine',revolucionario:'🔥 Revolucionario',gobierno:'🏛️ Gobierno',cazarrecompensas:'🎯 Cazarecompensas',civil:'👤 Civil'}[p.f]||p.f;
      html += '<div class="bib-card">'
        + '<div class="bib-card-avatar">' + p.n.charAt(0) + '</div>'
        + '<div class="bib-card-head">'
        + '<span class="bib-card-nom">' + p.n + '</span>'
        + '<span class="bib-card-tag fac-' + p.f + '">' + facLabel + '</span>'
        + '</div>'
        + (p.c ? '<div class="bib-card-extra"><span>⛵ <b>' + p.c + '</b></span></div>' : '')
        + '<div class="bib-card-extra"><span>⭐ Nivel <b>' + p.l + '</b></span><span>● ' + p.e + '</span></div>'
        + '<div class="bib-card-desc">' + p.d + '</div>'
        + '</div>';
    });
    document.getElementById('bibGrid').innerHTML = html || '<div class="bib-empty">No se encontraron personajes.</div>';
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
