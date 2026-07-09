<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-akuma.php');
require_once './global.php';
$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
header('Content-Type: text/html; charset=utf-8');
$tipos = ['paramecia','zoa','logia'];
$data = [
  ['n'=>'Gomu Gomu no Mi','t'=>'paramecia','ti'=>'Legendario','d'=>'Convierte el cuerpo en goma. Permite estirar, comprimir y resistir impactos.','u'=>'Monkey D. Luffy'],
  ['n'=>'Mera Mera no Mi','t'=>'logia','ti'=>'Legendario','d'=>'Permite crear, controlar y convertirte en fuego.','u'=>'Sabo'],
  ['n'=>'Ope Ope no Mi','t'=>'paramecia','ti'=>'Legendario','d'=>'Crea un espacio quirúrgico donde el usuario puede manipular todo a su antojo.','u'=>'Trafalgar Law'],
  ['n'=>'Yami Yami no Mi','t'=>'logia','ti'=>'Legendario','d'=>'Oscuridad pura. Absorbe cualquier cosa, incluyendo otros poderes.','u'=>'Marshall D. Teach'],
  ['n'=>'Gura Gura no Mi','t'=>'paramecia','ti'=>'Legendario','d'=>'Crea vibraciones que pueden romper el aire, la tierra y el mar.','u'=>'Edward Newgate'],
  ['n'=>'Pika Pika no Mi','t'=>'logia','ti'=>'Legendario','d'=>'Luz pura. Movimiento a velocidad lumínica.','u'=>'Borsalino (Kizaru)'],
  ['n'=>'Hito Hito no Mi (Modelo Nikyu)','t'=>'paramecia','ti'=>'Épico','d'=>'Patas con almohadillas que repelen cualquier cosa, incluso el dolor.','u'=>'Bartholomew Kuma'],
  ['n'=>'Neko Neko no Mi (Modelo Leopardo)','t'=>'zoa','ti'=>'Épico','d'=>'Transformación en leopardo. Híbrido humano-leopardo.','u'=>'Rob Lucci'],
  ['n'=>'Sara Sara no Mi (Modelo Azul)','t'=>'zoa','ti'=>'Épico','d'=>'Transformación en ceratopsio. Forma híbrida masiva.','u'=>'X Drake'],
  ['n'=>'Moku Moku no Mi','t'=>'logia','ti'=>'Épico','d'=>'Cuerpo de humo. Permite atrapar y moverse como niebla.','u'=>'Smoker'],
  ['n'=>'Toshi Toshi no Mi','t'=>'paramecia','ti'=>'Épico','d'=>'Manipula la edad de las personas.','u'=>'Jewelry Bonney'],
  ['n'=>'Hana Hana no Mi','t'=>'paramecia','ti'=>'Épico','d'=>'Florecen partes del cuerpo en cualquier superficie.','u'=>'Nico Robin'],
  ['n'=>'Bara Bara no Mi','t'=>'paramecia','ti'=>'Raro','d'=>'El cuerpo se divide en partes flotantes independientes.','u'=>'Buggy'],
  ['n'=>'Sube Sube no Mi','t'=>'paramecia','ti'=>'Raro','d'=>'Piel ultrasuave que repele cualquier ataque.','u'=>'Alvida'],
  ['n'=>'Doa Doa no Mi','t'=>'paramecia','ti'=>'Raro','d'=>'Abre puertas en cualquier superficie.','u'=>'Blueno'],
];
$data_json = json_encode($data, JSON_UNESCAPED_UNICODE);
$tipos_json = json_encode($tipos, JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca akuma no mi</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-biblioteca">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca akuma no mi</b></div></div>
<div class="wrap">
<section class="reveal"><div class="shead"><h1>Biblioteca akuma no mi</h1><span class="code">// catálogo de frutas del diablo</span><span class="rule"></span></div></section>
<section class="reveal" id="bibApp">
<div class="bib-search-wrap"><input type="text" class="bib-search" id="bibSearch" placeholder="Buscar fruta..."></div>
<div class="bib-filters" id="bibFilters">
  <button class="bib-filter on" data-filt="todas">Todas</button>
  <button class="bib-filter" data-filt="paramecia">🌀 Paramecia</button>
  <button class="bib-filter" data-filt="zoa">🐾 Zoan</button>
  <button class="bib-filter" data-filt="logia">💨 Logia</button>
  <button class="bib-filter" data-filt="legendario">⭐ Legendario</button>
  <button class="bib-filter" data-filt="epico">✨ Épico</button>
</div>
<div class="bib-grid" id="bibGrid"></div>
</section>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
(function(){
  var data = <?php echo $data_json; ?>;
  var tipos = <?php echo $tipos_json; ?>;
  var filtro = 'todas';
  var tierOrder = { 'Legendario':5, 'Épico':4, 'Raro':3, 'Común':2 };

  function render() {
    var q = document.getElementById('bibSearch').value.toLowerCase();
    var items = data.filter(function(p){
      if (filtro !== 'todas') {
        if (filtro === 'legendario') { if (p.ti !== 'Legendario') return false; }
        else if (filtro === 'epico') { if (p.ti !== 'Épico') return false; }
        else if (p.t !== filtro) return false;
      }
      if (q && p.n.toLowerCase().indexOf(q) === -1 && (p.u||'').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    var html = '';
    items.forEach(function(p){
      var tCls = {Legendario:'t5',Épico:'t4',Raro:'t3',Común:'t2'}[p.ti]||'t2';
      var tipoClass = p.t;
      html += '<div class="bib-card">'
        + '<div class="bib-card-head"><span class="bib-card-tier ' + tCls + '">' + p.ti + '</span></div>'
        + '<div class="bib-card-nom">' + p.n + '</div>'
        + '<div class="bib-card-type">' + (p.t==='paramecia'?'🌀 Paramecia':p.t==='zoa'?'🐾 Zoan':'💨 Logia') + '</div>'
        + '<div class="bib-card-desc">' + p.d + '</div>'
        + (p.u ? '<div class="bib-card-extra"><span>👤 Usuario: <b>' + p.u + '</b></span></div>' : '')
        + '</div>';
    });
    document.getElementById('bibGrid').innerHTML = html || '<div class="bib-empty">No se encontraron frutas.</div>';
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
