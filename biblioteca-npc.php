<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-npc.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
$data = [
  ['n'=>'Shanks','f'=>'pirata','r'=>'Emperador','l'=>1050,'e'=>'Activo','d'=>'Capitán de los Red Hair. Uno de los Cuatro Emperadores. Ex-miembro de la tripulación de Roger.'],
  ['n'=>'Marshall D. Teach','f'=>'pirata','r'=>'Emperador','l'=>1040,'e'=>'Activo','d'=>'Capitán de los Blackbeard. Emperador. Portador de Yami Yami + Gura Gura.'],
  ['n'=>'Akainu (Sakazuki)','f'=>'marine','r'=>'Almirante Flota','l'=>1100,'e'=>'Activo','d'=>'Almirante de Flota Marine. Justicia absoluta. Portador del Magma.'],
  ['n'=>'Kizaru (Borsalino)','f'=>'marine','r'=>'Almirante','l'=>1050,'e'=>'Activo','d'=>'Almirante Marine. Portador de la Pika Pika no Mi (Luz).'],
  ['n'=>'Aokiji (Kuzan)','f'=>'marine','r'=>'Almirante','l'=>1040,'e'=>'Activo','d'=>'Almirante Marine. Portador del Hielo. Antiguo Marine, ahora con Teach.'],
  ['n'=>'Monkey D. Garp','f'=>'marine','r'=>'Vicealmirante','l'=>1100,'e'=>'Activo','d'=>'Héroe de la Marine. Puño de amor. Abuelo de Luffy. Padre de Dragon.'],
  ['n'=>'Silvers Rayleigh','f'=>'pirata','r'=>'Leyenda','l'=>1080,'e'=>'Retirado','d'=>'Primer oficial de Gol D. Roger. El Rey Oscuro. Vivió en Sabaody.'],
  ['n'=>'Boa Hancock','f'=>'pirata','r'=>'Emperatriz','l'=>920,'e'=>'Activo','d'=>'Emperatriz de Amazon Lily. Shichibukai. Portadora de la Mero Mero no Mi.'],
  ['n'=>'Dracule Mihawk','f'=>'pirata','r'=>'Shichibukai','l'=>1050,'e'=>'Activo','d'=>'El mejor espadachín. Antiguo Shichibukai. Portador de Yoru.'],
  ['n'=>'Bartholomew Kuma','f'=>'revolucionario','r'=>'Shichibukai','l'=>950,'e'=>'Desconocido','d'=>'Antiguo Shichibukai. Revolucionario. Portador de la Nikyu Nikyu no Mi.'],
  ['n'=>'Vegapunk','f'=>'gobierno','r'=>'Científico','l'=>'??','e'=>'Activo','d'=>'Genio científico del Gobierno Mundial. Creó los Pacifista y las armas más avanzadas.'],
  ['n'=>'Kong','f'=>'gobierno','r'=>'Comandante Supremo','l'=>1200,'e'=>'Activo','d'=>'Comandante Supremo de las fuerzas del Gobierno Mundial. Superior a los Almirantes.'],
];
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca NPC</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca NPC</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca NPC</h1><span class="code">// personajes no jugadores</span><span class="rule"></span></div></section>
<section class="reveal" id="bibApp">
<div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar NPC..."></div>
<div class="bib-filters" id="bibFilters">
  <button class="bib-filter on" data-filt="todas">Todos</button>
  <button class="bib-filter" data-filt="pirata">🏴‍☠️ Pirata</button>
  <button class="bib-filter" data-filt="marine">⚓ Marine</button>
  <button class="bib-filter" data-filt="revolucionario">🔥 Revolucionario</button>
  <button class="bib-filter" data-filt="gobierno">🏛️ Gobierno</button>
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
      if (filtro !== 'todas' && p.f !== filtro) return false;
      if (q && p.n.toLowerCase().indexOf(q) === -1 && (p.r||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    var html = '';
    items.forEach(function(p){
      var facLabel = {pirata:'🏴‍☠️ Pirata',marine:'⚓ Marine',revolucionario:'🔥 Revolucionario',gobierno:'🏛️ Gobierno'}[p.f]||p.f;
      html += '<div class="bib-card">'
        + '<div class="bib-card-avatar">' + p.n.charAt(0) + '</div>'
        + '<div class="bib-card-head">'
        + '<span class="bib-card-nom">' + p.n + '</span>'
        + '<span class="bib-card-tag fac-' + p.f + '">' + facLabel + '</span>'
        + '</div>'
        + '<div class="bib-card-extra"><span>🎭 <b>' + p.r + '</b></span></div>'
        + '<div class="bib-card-extra"><span>⭐ Nivel <b>' + p.l + '</b></span><span>● ' + p.e + '</span></div>'
        + '<div class="bib-card-desc">' + p.d + '</div>'
        + '</div>';
    });
    document.getElementById('bibGrid').innerHTML = html || '<div class="bib-empty">No se encontraron NPCs.</div>';
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
