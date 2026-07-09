<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-estilos.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
$data = [
  ['n'=>'Rokushiki','c'=>'Combate','d'=>'Alta','des'=>'Las seis técnicas del cuartel Marine. Shave, Moonwalk, Geppo, Rankyaku, Kami-e y Tekkai. Dominio corporal absoluto.'],
  ['n'=>'Haki del Armamento','c'=>'Defensa','d'=>'Alta','des'=>'Escudo invisible. Recubre el cuerpo de armadura espiritual. Puede tocar cuerpos de Logia.'],
  ['n'=>'Haki de la Observación','c'=>'Percepción','d'=>'Alta','des'=>'Siente la presencia y el poder de otros. Permite esquivar ataques. En su cima: ver el futuro.'],
  ['n'=>'Haki del Rey','c'=>'Combate','d'=>'Legendaria','des'=>'Imposición de voluntad. Doblega a los débiles. Portado solo por uno de cada millón.'],
  ['n'=>'Estilo de Tres Espadas','c'=>'Combate','d'=>'Alta','des'=>'Técnica de Zoro. Tres katanas simultáneas. Posturas: Oni Giri, Tora Gari, Santoryu Ougi.'],
  ['n'=>'Black Leg','c'=>'Combate','d'=>'Media','des'=>'Estilo de patadas de Sanji. Uso exclusivo de piernas para no manchar las manos. Fire Kick.'],
  ['n'=>'Fisicoculturismo','c'=>'Combate','d'=>'Media','des'=>'Musculatura densa que absorbe impactos. Patada Motha, Finger Pistol.'],
  ['n'=>'Pez Espada','c'=>'Combate','d'=>'Baja','des'=>'Estilo acuático con puñal entre dientes. Imita ataques de pez espada en tierra.'],
  ['n'=>'Kung Fu Duro','c'=>'Defensa','d'=>'Media','des'=>'Posturas firmes. Puños de piedra. Técnica de islas del sur.'],
  ['n'=>'Medicina de Campo','c'=>'Apoyo','d'=>'Media','des'=>'Vendas con alcohol, sutura rápida y puntos de presión. Sin fruta, sin Haki, pura ciencia.'],
  ['n'=>'Franky Shogun','c'=>'Combate','d'=>'Alta','des'=>'Armadura cyborg. Cañones, misiles, pinchos y un puño de hierro gigante.'],
  ['n'=>'Navigación Estelar','c'=>'Apoyo','d'=>'Baja','des'=>'Lectura de estrellas, corrientes y nubes. Sin log pose, el cielo es tu brújula.'],
];
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca estilos</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca estilos</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca estilos</h1><span class="code">// estilos de lucha y habilidades</span><span class="rule"></span></div></section>
<section class="reveal" id="bibApp">
<div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar estilo..."></div>
<div class="bib-filters" id="bibFilters">
  <button class="bib-filter on" data-filt="todas">Todos</button>
  <button class="bib-filter" data-filt="Combate">⚔️ Combate</button>
  <button class="bib-filter" data-filt="Defensa">🛡️ Defensa</button>
  <button class="bib-filter" data-filt="Percepción">👁️ Percepción</button>
  <button class="bib-filter" data-filt="Apoyo">💊 Apoyo</button>
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
      if (filtro !== 'todas' && p.c !== filtro) return false;
      if (q && p.n.toLowerCase().indexOf(q) === -1 && p.des.indexOf(q) === -1) return false;
      return true;
    });
    var html = '';
    items.forEach(function(p){
      var diffClass = {Baja:'t2',Media:'t3',Alta:'t4',Legendaria:'t5'}[p.d]||'t3';
      html += '<div class="bib-card">'
        + '<div class="bib-card-head">'
        + '<span class="bib-card-nom">' + p.n + '</span>'
        + '<span class="bib-card-tier ' + diffClass + '" style="font-size:.6rem;padding:1px 7px">' + p.d + '</span>'
        + '</div>'
        + '<div class="bib-card-type">' + ({Combate:'⚔️ Combate',Defensa:'🛡️ Defensa',Percepción:'👁️ Percepción',Apoyo:'💊 Apoyo'}[p.c]||p.c) + '</div>'
        + '<div class="bib-card-desc">' + p.des + '</div>'
        + '</div>';
    });
    document.getElementById('bibGrid').innerHTML = html || '<div class="bib-empty">No se encontraron estilos.</div>';
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
