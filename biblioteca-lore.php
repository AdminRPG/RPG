<?php
/**
 * One Piece: Eternal · Biblioteca de Lore y Cronología Histórica
 * Crónica oficial del mundo de One Piece: Eternal: historia, cronología y eras.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-lore.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

// Carga de NPCs públicos para autoreemplazo {npc:slug}
$npcs = function_exists('ope_rol_cat_npcs_publicos') ? ope_rol_cat_npcs_publicos() : array();
$npc_map = array();
foreach ($npcs as $n) {
    $s = $n['slug'] ?? '';
    if ($s !== '') $npc_map[$s] = $n;
}

function tomo_npc_link($slug, $map, $bburl) {
    if (!isset($map[$slug])) {
        $legible = ucwords(str_replace('-', ' ', $slug));
        return '<span class="tomo-npc-ref is-plain">' . htmlspecialchars($legible) . '</span>';
    }
    $n = $map[$slug];
    $fc = $n['faccion_slug'] ?? '';
    $display = !empty($n['apodo']) ? $n['nombre'] . ' «' . $n['apodo'] . '»' : $n['nombre'];
    $url = $bburl . '/ficha.php?pid=' . ((int)$n['pid']);
    return '<a href="' . htmlspecialchars($url) . '" class="tomo-npc-ref fac-' . htmlspecialchars($fc) . '" title="Ver ficha de ' . htmlspecialchars($n['nombre']) . '">' . htmlspecialchars($display) . '</a>';
}

function tomo_npcify($text, $map, $bburl) {
    return preg_replace_callback('/\{npc:([a-z0-9-]+)\}/', function($m) use ($map, $bburl) {
        return tomo_npc_link($m[1], $map, $bburl);
    }, $text);
}

$total_capitulos = 3;

header('Content-Type: text/html; charset=utf-8');
ob_start();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Lore y Cronología</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-guias + .bib-lore) -->
</head>
<body class="ope-pg-guias bib-lore">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Lore y Cronología</b></div></div>
<div class="wrap">

<!-- INTRO -->
<section class="reveal">
  <div class="shead">
    <h1>Biblioteca de Lore & Cronología Histórica</h1>
    <span class="code">// crónica oficial del mundo · <?php echo $total_capitulos; ?> capítulos</span>
    <span class="rule"></span>
  </div>
  <p class="guia-intro">El <b>archivo oficial</b> de One Piece: Eternal: la historia del mundo, la cronología oficial de las cuatro eras y el origen de las facciones. NAVEGA por los capítulos a la izquierda para explorar la crónica del mar.</p>
</section>

<!-- SELECTORES (izquierda) + CONTENIDO (derecha) -->
<section class="reveal">
  <div class="guide-shell">

    <!-- NAVEGACIÓN IZQUIERDA -->
    <nav class="guide-nav">
      <div class="guide-nav-inner" id="loreNav">
        <div class="nav-section">Capítulos</div>
        <button class="guide-nav-item active" data-guide="historia"><span class="n">I</span> Historia del Mundo</button>
        <button class="guide-nav-item" data-guide="cronologia"><span class="n">II</span> Cronología Histórica</button>
        <button class="guide-nav-item" data-guide="eras"><span class="n">III</span> Las Cuatro Eras</button>
      </div>
    </nav>

    <!-- CONTENIDO DERECHA -->
    <div class="guide-main">

      <!-- I · Historia del Mundo -->
      <div class="guide-content active" id="g-historia">
        <div class="g-title">Historia del Mundo</div>
        <div class="g-sub">// la sinopsis completa</div>
        <p><span class="tomo-drop">E</span>n un mundo cubierto casi por completo por un océano infinito e indomable, donde las islas se alzan como dientes de dragón contra un cielo perpetuamente cambiante, existe una verdad que ha sido perseguida durante siglos. Una verdad tan peligrosa que el mismísimo <strong>Gobierno Mundial</strong> —heredero de las dieciséis familias que un día borraron un siglo entero de los libros de historia— ha consagrado su existencia a mantenerla enterrada bajo capas de mentiras, censura y sangre.</p>

        <p>Pero la verdad, como el mar, siempre encuentra la manera de filtrarse.</p>

        <p>Hace <strong>900 años</strong>, una civilización que hoy solo conocemos como el <strong>Gran Reino</strong> fue aniquilada en una guerra de proporciones apocalípticas. Sus enemigos —una coalición de veinte reinos que luego se coronarían a sí mismos como los <em>creadores del mundo</em>— no se conformaron con la victoria militar. Borraron cada rastro, cada nombre, cada susurro de aquella civilización durante lo que los eruditos clandestinos denominan el <strong>Siglo Vacío</strong>. Cien años de historia arrancados del tejido mismo del tiempo.</p>

        <p>Pero los sabios del Gran Reino, previendo su propia destrucción, grabaron la verdad en piedra indestructible. Los <strong>Poneglyphs</strong> —monolitos de un material desconocido que ni el fuego ni el acero ni el paso de los milenios puede dañar— fueron dispersados por todo el mundo. En ellos, en una lengua que solo unos pocos elegidos pueden leer, está escrita la <em>Verdadera Historia</em>.</p>

        <p>Novecientos años después, un hombre con la inicial <strong>D.</strong> en su nombre —heredero involuntario de la voluntad que desafía a los dioses— se alzó desde la nada hasta conquistar el <strong>Grand Line</strong> y ser coronado <strong>Rey de los Piratas</strong>. Durante años, su bandera fue sinónimo de libertad para unos y de caos para otros.</p>

        <p>Pero hace poco, lo imposible ocurrió: el Rey Pirata <strong>fue capturado</strong>. Hoy aguarda, encadenado, su ejecución pública en el corazón mismo de la Marina.</p>

        <p>Y aquí reside la ironía que estremece al mundo: quien debe garantizar esa ejecución es la <strong>Almirante de Flota</strong> conocida como <strong>«El Puño de la Marina»</strong>… su propia madre. Deber contra sangre, sobre el filo de una decisión imposible.</p>

        <p>El mundo contiene la respiración. Porque todos saben —los Cuatro Emperadores, la Marina, el Ejército Revolucionario, el Gobierno Mundial— que la ejecución del Rey de los Piratas no será el final de nada. Será el prólogo. El disparo de salida. La chispa que encenderá la mayor guerra que estos mares hayan visto jamás.</p>
      </div><!-- /#g-historia -->

      <!-- II · Cronología -->
      <div class="guide-content" id="g-cronologia">
        <div class="g-title">Cronología Histórica</div>
        <div class="g-sub">// la línea temporal oficial del mar</div>
        <p><span class="tomo-drop">E</span>l tiempo en este mundo se mide por las cicatrices que dejan los grandes eventos en el océano. A continuación se presenta la <strong>Línea Temporal Oficial</strong> reconstruida a partir de los archivos de la Marina, las tablillas antiguas y los registros históricos de Mary Geoise.</p>

        <div class="tomo-divider"></div>
        <ul class="tomo-timeline">
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 900 años</span><span class="tomo-tl-text">La <strong>Gran Guerra del Reino Antiguo</strong>. Se inicia el <strong>Siglo Vacío</strong>. Los 30 Poneglyphs y los 4 Poneglyphs Rojos son dispersados por el mundo como último legado de resistencia.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 800 años</span><span class="tomo-tl-text">Las <strong>16 Familias Fundadoras</strong> ascienden a la Red Line y fundan <strong>Mary Geoise</strong> y el Gobierno Mundial. Se instaura el Tributo Celestial y la pena de muerte por investigar el Siglo Vacío.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 50 años</span><span class="tomo-tl-text"><strong>La Era del Gran Trébol y las Conquistas</strong>. Nacimiento del Gremio de Cazarrecompensas y los primeros conflictos de los gigantes en Elbaf.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 20 años</span><span class="tomo-tl-text">Zarpa <strong>Rolf D. Basterra</strong> desde los Blues. En 5 años supera los peligros de Grand Line y alcanza La Última Isla, siendo coronado Rey de los Piratas.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 1 año</span><span class="tomo-tl-text">Los <strong>Cuatro Emperadores (Yonkou)</strong> —Kaiser Vaelgor, Jarl Brogaz, Princesa Rosette y Sylphira— fijan sus fronteras en el Nuevo Mundo e inician una guerra fría territorial por los Poneglyphs Rojos.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 1 mes</span><span class="tomo-tl-text">Rolf D. Basterra se entrega en secreto a su madre, la Almirante de Flota Sigrun, para evitar un Buster Call sobre su tierra natal. El Gobierno Mundial anuncia la ejecución en Marineford.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Año 25 E.P. (Presente)</span><span class="tomo-tl-text"><strong>La Gran Ejecución en Marineford</strong>. Las últimas palabras de Rolf desatan la Gran Era Pirata. El Alto Inquisidor Vaelen ordena el Cierre Militar de Reverse Mountain y Calm Belt.</span></li>
        </ul>
      </div><!-- /#g-cronologia -->

      <!-- III · Las Cuatro Eras -->
      <div class="guide-content" id="g-eras">
        <div class="g-title">Las Cuatro Eras del Mundo</div>
        <div class="g-sub">// de la fundación ancestral a la ejecución</div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era I — El Siglo Vacío (Hace 800 - 900 años)</h3>
          <span class="tomo-era-sub">La caída del Gran Reino y el nacimiento del Gobierno Mundial</span>
          <p>Hace 900 años estalla la guerra apocalíptica entre la civilización del Gran Reino y la alianza de reinos fundadores. Tras su caída, las 16 familias aristocráticas se instalan en Mary Geoise y borran 100 años de historia. Los sabios antiguos esculpen los Poneglyphs indestructibles para preservar la verdad para las generaciones futuras.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era II — La Era del Gran Trébol (Hace 50 años)</h3>
          <span class="tomo-era-sub">La cartografía del Grand Line y la llegada del Gremio</span>
          <p>Se establecen las 7 rutas de navegación del Log Pose a través del Paraíso y el Nuevo Mundo. Surgimiento de las potencias mercantiles y creación del Gremio de Cazarrecompensas para contener a los piratas emergentes.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era III — La Corona Pirata (Hace 5 años)</h3>
          <span class="tomo-era-sub">El hito de Rolf D. Basterra y la fractura del equilibrio</span>
          <p>Rolf D. Basterra completa la travesía del Grand Line, descubre la verdad de La Última Isla y se convierte en el Rey de los Piratas. Los 4 Yonkou fortifican sus reinos en el Nuevo Mundo, cada uno reteniendo un Poneglyph Rojo del Camino.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era IV — La Nueva Era Pirata (Año 25 E.P. - Presente)</h3>
          <span class="tomo-era-sub">El discurso del patíbulo y la carrera por el Nuevo Mundo</span>
          <p>La ejecución de Rolf D. Basterra en Marineford se convierte en la chispa inicial. Sus últimas palabras incitan a la nueva generación a romper el Tributo Celestial y conquistar el Nuevo Mundo. Todos los novatos inician en los Blues debiendo forzar el Bloqueo Militar de Reverse Mountain.</p>
        </div>
      </div><!-- /#g-eras -->

    </div><!-- /.guide-main -->
  </div><!-- /.guide-shell -->
</section>

</div><!-- .wrap -->

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }

  var nav = document.getElementById('loreNav');
  if (!nav) return;
  var items = nav.querySelectorAll('.guide-nav-item');
  var panels = document.querySelectorAll('.guide-main .guide-content');
  var main = document.querySelector('.guide-main');
  items.forEach(function(btn){
    btn.addEventListener('click', function(){
      var id = 'g-' + btn.getAttribute('data-guide');
      items.forEach(function(i){ i.classList.toggle('active', i === btn); });
      panels.forEach(function(p){ p.classList.toggle('active', p.id === id); });
      if (main) main.scrollTop = 0;
    });
  });
})();
</script>
</body>
</html>
<?php
echo tomo_npcify(ob_get_clean(), $npc_map, $bburl);
