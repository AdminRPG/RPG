<?php
/**
 * One Piece: Eternal · Biblioteca de Lore
 * Crónica del mundo de One Piece: Eternal: historia, cronología y eras.
 * Reutiliza el shell de Guías (selectores a la izquierda, contenido a la
 * derecha) con extras de lore. NPCs referenciados vía {npc:slug} que
 * enlazan a ficha.php (coloreados por facción).
 * Estilos en docs/themes/ope.css (scope: ope-pg-guias + .bib-lore).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-lore.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$npcs = ope_rol_cat_npcs_publicos();
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
<title><?php echo $bbname; ?> · Biblioteca de Lore</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-guias + .bib-lore) -->
</head>
<body class="ope-pg-guias bib-lore">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Lore</b></div></div>
<div class="wrap">

<!-- INTRO -->
<section class="reveal">
  <div class="shead">
    <h1>Biblioteca de Lore</h1>
    <span class="code">// crónica del mundo · <?php echo $total_capitulos; ?> capítulos</span>
    <span class="rule"></span>
  </div>
  <p class="guia-intro">El <b>archivo oficial</b> de One Piece: Eternal: la historia del mundo, su cronología y las cuatro grandes eras. <b>Elige un capítulo</b> en la izquierda para leer su crónica; los <b>personajes</b> enlazan a su ficha.</p>
</section>

<!-- SELECTORES (izquierda) + CONTENIDO (derecha) -->
<section class="reveal">
  <div class="guide-shell">

    <!-- NAVEGACIÓN IZQUIERDA -->
    <nav class="guide-nav">
      <div class="guide-nav-inner" id="loreNav">
        <div class="nav-section">Capítulos</div>
        <button class="guide-nav-item active" data-guide="historia"><span class="n">I</span> Historia del Mundo</button>
        <button class="guide-nav-item" data-guide="cronologia"><span class="n">II</span> Cronología</button>
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

        <p><em>La crónica de esta era se está escribiendo. Sus protagonistas se revelarán pronto.</em></p>
      </div><!-- /#g-historia -->

      <!-- II · Cronología -->
      <div class="guide-content" id="g-cronologia">
        <div class="g-title">Cronología</div>
        <div class="g-sub">// línea temporal oficial</div>
        <p><span class="tomo-drop">E</span>l tiempo en este mundo no se mide como en otros. Aquí, los años se cuentan por las cicatrices que dejan en el mar. Cada isla tiene su propio calendario, cada cultura su propio punto de partida. Pero existe una cronología que todos —marinos, piratas, revolucionarios y civiles— reconocen como la <strong>Línea Temporal Oficial</strong>, registrada por los eruditos de la Biblioteca de Ohara antes de que el <em>Buster Call</em> redujera aquella isla del conocimiento a cenizas.</p>

        <p>Lo que sigue es un intento de reconstrucción. Los fragmentos que sobrevivieron al fuego. Las fechas que el Gobierno Mundial no ha podido —o no ha querido— borrar. Las piezas del rompecabezas que los <strong>Poneglyphs</strong> han ido revelando a lo largo de los siglos, descifradas en susurros por aquellos que aún se atreven a leer la lengua prohibida.</p>

        <p>Porque, como bien saben los que navegan el <strong>Grand Line</strong>: <em>nada se pierde para siempre en el mar. Todo regresa. Toda verdad encuentra su playa.</em></p>

        <div class="tomo-divider"></div>
        <ul class="tomo-timeline">
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 900 años</span><span class="tomo-tl-text">La <strong>Gran Guerra</strong> destruye el Gran Reino. El <strong>Siglo Vacío</strong> comienza. Los Poneglyphs son dispersados por el mundo como último acto de resistencia de una civilización condenada.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 800 años</span><span class="tomo-tl-text">Las <strong>16 Familias Fundadoras</strong> ascienden al Red Line y establecen <strong>Mary Geoise</strong>. Se funda el Gobierno Mundial. Nace el sistema de los <strong>Dragones Celestiales</strong>. La censura histórica se convierte en política de estado.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 400 años</span><span class="tomo-tl-text">La <strong>Gran Exploración</strong> cartografía el Grand Line. Se establecen las siete rutas principales. Los <strong>Log Pose</strong> se convierten en herramienta indispensable de navegación.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 50 años</span><span class="tomo-tl-text">En Elbaf, el joven guerrero que se hará llamar <strong>Grog «Rompe-Cielos»</strong> despierta su poder latente. La era de los gigantes como fuerza militar indiscutible alcanza su cénit.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace ~40 años</span><span class="tomo-tl-text">Nace, en una hermandad <strong>Buccaneer</strong> apartada del mundo, quien un día será <strong>«El Puño de la Marina»</strong>.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace ~28 años</span><span class="tomo-tl-text">Nace su hijo, portador de la voluntad de la <strong>D.</strong> Madre e hijo tomarán caminos opuestos: ella, el deber; él, la libertad.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace ~10 años</span><span class="tomo-tl-text">Cuatro <strong>Emperadores</strong> consolidan su dominio sobre el Nuevo Mundo. El equilibrio de poder de la era queda fijado.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace ~5 años</span><span class="tomo-tl-text">El futuro Rey Pirata alcanza <strong>La Última Isla</strong> y es reconocido como <strong>Rey de los Piratas</strong>.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace ~1 mes</span><span class="tomo-tl-text">Lo imposible: el <strong>Rey Pirata es capturado</strong> y encerrado en la prisión de máxima seguridad de la Marina.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Día 0 — AHORA</span><span class="tomo-tl-text">Cuenta atrás para la <strong>ejecución pública del Rey Pirata</strong>. Su madre, la Almirante de Flota, debe presidirla. Los Cuatro Emperadores mueven ficha. El mundo se asoma al abismo de una nueva guerra.</span></li>
        </ul>
      </div><!-- /#g-cronologia -->

      <!-- III · Las Cuatro Eras -->
      <div class="guide-content" id="g-eras">
        <div class="g-title">Las Cuatro Eras</div>
        <div class="g-sub">// de la fundación a la ejecución</div>
        <p><span class="tomo-drop">L</span>os eruditos de la desaparecida <strong>Ohara</strong> dividieron la historia conocida en cuatro grandes eras, cada una definida por un cataclismo que transformó irreversiblemente el equilibrio del mundo. Cuatro edades que, como las estaciones de un año imposiblemente largo, marcan el pulso de la civilización en este planeta de agua y misterio.</p>

        <p>Cuatro eras. Cuatro puntos de inflexión. Cuatro historias que, juntas, forman el tapiz sobre el que se borda el destino de cada hombre, cada mujer, cada niño que alza una vela y se hace al mar.</p>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era I — El Gran Olvido</h3>
          <span class="tomo-era-sub">Cuando los dioses borraron un siglo y los sabios grabaron la verdad en piedra</span>
          <p>Hace 900 años, estalla la Gran Guerra entre el Gran Reino y una alianza de reinos menores por razones perdidas en el tiempo. Un siglo después, el Gran Reino es completamente borrado de la historia en lo que se conoce como el <strong>Siglo Vacío</strong>.</p>
          <p>Las <strong>16 Familias Fundadoras</strong> suben al Red Line y establecen <strong>Mary Geoise</strong>, coronándose como Dioses Mundiales y censurando todo el conocimiento antiguo. Los <strong>Poneglyphs</strong> son creados como única fuente de verdad histórica indestructible, dispersados por todo el mundo para preservar lo que el Gobierno Mundial intentó destruir.</p>
          <p>Desde entonces, las familias fundadoras y sus herederos recorren los pasillos de Mary Geoise con la arrogancia de quien cree que la historia la escriben los vencedores. Pero como demuestra el tiempo una y otra vez... <em>los vencedores nunca tienen la última palabra</em>.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era II — La Edad de las Bestias</h3>
          <span class="tomo-era-sub">Cuando los colosos cartografiaron el mundo y los gigantes despertaron</span>
          <p>Hace 400 años, la <strong>Gran Exploración</strong> cartografía el Grand Line, estableciendo las rutas y la geografía actual del mundo que todos conocen. Los Log Pose se convierten en la herramienta indispensable de navegación.</p>
          <p>Hace 50 años, <strong>Grog «Rompe-Cielos»</strong> despierta como el guerrero más formidable y aterrador de la nueva generación de Elbaf, llevando la gloria a la raza de los gigantes y demostrando que el poder bruto puede rivalizar con cualquier Fruta del Diablo. Es en esta era cuando se forjan las alianzas tribales que siglos después alzarán a colosos capaces de rozar lo divino.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era III — El Ascenso de los Emperadores</h3>
          <span class="tomo-era-sub">Cuando cuatro monstruos reclamaron el mar y un rey se alzó sobre todos</span>
          <p>En las últimas décadas, cuatro fuerzas colosales —los <strong>Cuatro Emperadores (Yonko)</strong>— se repartieron el dominio del <strong>Nuevo Mundo</strong>, imponiendo un frágil equilibrio de terror y ambición que ninguna nación se atrevía a desafiar.</p>
          <p>Fue en ese tablero donde un hombre con la voluntad de la <strong>D.</strong> reunió una tripulación de soñadores y monstruos, cruzó el Grand Line de punta a punta y, cinco años atrás, fue reconocido como <strong>Rey de los Piratas</strong> tras alcanzar La Última Isla. Su bandera se convirtió en el símbolo de una libertad que el Gobierno Mundial no podía tolerar.</p>
          <p><em>La crónica detallada de esta era —sus Emperadores, sus Almirantes y sus revolucionarios— se está escribiendo.</em></p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era IV — La Cuenta Atrás</h3>
          <span class="tomo-era-sub">Cuando el Rey cayó y el mundo contuvo la respiración</span>
          <p>Hace apenas un mes, lo imposible se hizo realidad: el <strong>Rey Pirata fue capturado</strong> y encerrado en la prisión de máxima seguridad de la Marina.</p>
          <p>Quien debe garantizar su ejecución es la <strong>Almirante de Flota «El Puño de la Marina»</strong>… su propia madre. Un deber que le exige matar a su sangre ante los ojos del mundo entero.</p>
          <p><strong>En la actualidad (Día 0):</strong> corre la cuenta atrás para la ejecución pública del Rey de los Piratas. Las fuerzas mundiales se preparan para la Guerra Total. Los Cuatro Emperadores mueven ficha. El mundo entra en su era más caótica y decisiva.</p>
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

