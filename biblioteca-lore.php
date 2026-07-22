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

        <p>Novecientos años después, una mujer con la inicial <strong>D.</strong> en su nombre —heredera involuntaria de la voluntad que desafía a los dioses— se alzó desde la nada hasta convertirse en la dueña indiscutible de los mares.</p>

        <p>Esa mujer es {npc:isabella-d-vega}.</p>

        <p>Nacida en la miseria de una isla olvidada por los mapas y explotada por nobles que se creían intocables, Isabella escapó de las cadenas de la tiranía a una edad en la que otros niños aún aprenden a leer. Durante <strong>treinta años</strong>, forjó su leyenda ola a ola, combate a combate, aliado a aliado y traición a traición. Fundó los legendarios <strong>Piratas Carmesí</strong> —así llamados por el rastro carmesí que dejaban en el horizonte al zarpar al amanecer— y reunió a su alrededor a una tripulación de monstruos, soñadores y almas perdidas que encontraron en su bandera un hogar.</p>

        <p>A su lado, como su sombra y su escudo, navega {npc:jack-el-inmortal}, el vice-capitán que ha sobrevivido a heridas que habrían matado a cualquier otro mortal diez veces. Y en la enfermería del barco, {npc:aurelian-lira} —una mujer que un día caminó por los pasillos de Mary Geoise como <strong>Dragón Celestial</strong> y que renunció a todo para curar a aquellos a los que su sangre debería despreciar— venda las heridas de la tripulación y con ellas, quizás, las suyas propias.</p>

        <p>Pero el destino de Isabella —como el de todos los que portan la <strong>D.</strong>— estaba sellado desde el momento en que nació.</p>

        <p>Hace cinco años, los Piratas Carmesí lograron lo imposible: alcanzaron <strong>La Última Isla</strong>. Allí, en el confín del mundo, Isabella contempló la verdad que el Gobierno Mundial lleva siglos tratando de ocultar. Lo que vio en aquella isla —lo que comprendió en aquel instante— jamás ha sido revelado a nadie. Pero quienes la conocen aseguran que, al regresar, sus ojos —esos ojos que le habían ganado el epíteto de <strong>Ojos Carmesí</strong>— ya no miraban al presente. Miraban al futuro. O quizás al pasado.</p>

        <p>No atacó. No reveló lo que sabía. Simplemente... esperó.</p>

        <p>Y entonces llegó la traición.</p>

        <p>{npc:balgor-titan-de-chatarra}, el colosal gigante que una vez fue su aliado más formidable —un titán de hierro y carne que asimiló flotas enteras para convertirse en una máquina de guerra viviente—, vendió las coordenadas de su antigua capitana a cambio de armamento. La que fue confianza se convirtió en humo. Y mientras Isabella descansaba en una isla remota, creyéndose a salvo, la <strong>Almirante de Flota {npc:valyria-almirante-de-flota}</strong> —«El Filo de la Marina», la mujer cuya espada partió una isla en dos y cuyo sentido de la justicia es tan absoluto como aterrador— cayó sobre ella como un halcón sobre su presa.</p>

        <p>El duelo fue legendario. Dos mujeres. Dos voluntades. Dos filos. El cielo se partió. El mar rugió. Y al final, la Reina de los Piratas cayó.</p>

        <p>Ahora, Isabella D. Vega espera en una celda de <strong>Impel Down</strong>, custodiada por los tres Almirantes: {npc:ken-dragon-azul}, cuyo aliento de dragón congela el mismo tiempo; {npc:flint-balas-de-plata}, que prefiere la comodidad de su hamaca pero cuyo gatillo es más rápido que el pensamiento; y {npc:nereida-el-abismo}, la sirena que gobierna las profundidades y cuyo concepto de la justicia no admite matices.</p>

        <p>En <strong>30 días</strong>, a plena vista del mundo entero, en la plaza central de Marineford, Isabella D. Vega será ejecutada.</p>

        <p>El mundo contiene la respiración. Porque todos saben —los Cuatro Emperadores, la Marina, el Ejército Revolucionario, los Señores de la Guerra— que la ejecución de la Reina de los Piratas no es el final de la historia. Es el prólogo. El disparo de salida. La chispa que encenderá la mayor guerra que este mundo haya visto jamás.</p>

        <p>Porque cuando muera Isabella D. Vega... <strong>¿quién reclamará su trono?</strong></p>
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
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 30 años</span><span class="tomo-tl-text">{npc:isabella-d-vega} escapa de la tiranía de su isla natal. Nace un odio eterno hacia la nobleza que marcará el resto de su vida.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 25 años</span><span class="tomo-tl-text">Isabella funda los <strong>Piratas Carmesí</strong>. Se asocia temporalmente con {npc:balgor-titan-de-chatarra}, un colosal gigante con ambiciones mecánicas. {npc:jack-el-inmortal} se une como primer oficial.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 20 años</span><span class="tomo-tl-text">{npc:sekhmet-reina-leona} —majestuosa Mink leona del reino de Zou— y {npc:shura-dios-de-la-ira} —terrorífica Oni portadora de una Zoan Mitica— emergen como fuerzas imparables en el Nuevo Mundo.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 15 años</span><span class="tomo-tl-text">{npc:valyria-almirante-de-flota} asciende meteóricamente a Almirante de Flota tras partir una isla entera por la mitad con un solo golpe de su espada. La Marina entra en una nueva era de poder absoluto.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 12 años</span><span class="tomo-tl-text">{npc:aurelian-lira}, nacida como Dragón Celestial, renuncia a su título y a su familia para unirse a los Piratas Carmesí como médica. Mary Geoise declara su nombre <em>maldito</em>.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 10 años</span><span class="tomo-tl-text">{npc:balgor-titan-de-chatarra} completa su transformación en un Mecha gigante tras asimilar los restos de varias flotas derrotadas. Se autoproclama <strong>Yonko</strong>. {npc:ezekiel-el-arcangel}, híbrido Skypiean/Lunarian, comienza a cazar piratas desde los cielos con su rifle de Diales y es catalogado como el cuarto Emperador.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 8 años</span><span class="tomo-tl-text">{npc:ignis-llama-del-sur} es nombrado Comandante del <strong>Ejército Revolucionario</strong>, liderando operaciones de liberación en islas oprimidas por el Gobierno Mundial.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 5 años</span><span class="tomo-tl-text">Isabella y los Piratas Carmesí alcanzan <strong>La Última Isla</strong>. Descubre la verdad del mundo. Es coronada <strong>Reina de los Piratas</strong>. Decide no atacar.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Hace 1 mes</span><span class="tomo-tl-text">{npc:balgor-titan-de-chatarra} traiciona a Isabella, vendiendo sus coordenadas. {npc:valyria-almirante-de-flota} intercepta a la Reina Pirata en una isla remota. <strong>Duelo de espadas legendario</strong>. Isabella es capturada y encarcelada en Impel Down, Nivel 6.</span></li>
          <li class="tomo-tl-item"><span class="tomo-tl-year">Día 0 — AHORA</span><span class="tomo-tl-text">Faltan <strong>30 días</strong> para la ejecución pública de Isabella D. Vega en Marineford. Los Yonko —{npc:shura-dios-de-la-ira}, {npc:sekhmet-reina-leona}, {npc:ezekiel-el-arcangel} y {npc:balgor-titan-de-chatarra}— mueven ficha. La Marina se prepara para la guerra total. El mundo se asoma al abismo de una nueva era.</span></li>
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
          <p>Entre esas familias, los <strong>Oakhaven</strong> se consolidan como una de las más influyentes. Siglos después, un heredero de ese linaje —{npc:principe-oakhaven}— recorrerá los pasillos de Mary Geoise con la arrogancia de quien cree que la historia la escriben los vencedores. Pero como demuestra el tiempo una y otra vez... <em>los vencedores nunca tienen la última palabra</em>.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era II — La Edad de las Bestias</h3>
          <span class="tomo-era-sub">Cuando los colosos cartografiaron el mundo y los gigantes despertaron</span>
          <p>Hace 400 años, la <strong>Gran Exploración</strong> cartografía el Grand Line, estableciendo las rutas y la geografía actual del mundo que todos conocen. Los Log Pose se convierten en la herramienta indispensable de navegación.</p>
          <p>Hace 50 años, <strong>Grog «Rompe-Cielos»</strong> despierta como el guerrero más formidable y aterrador de la nueva generación de Elbaf, llevando la gloria a la raza de los gigantes y demostrando que el poder bruto puede rivalizar con cualquier Fruta del Diablo. Es en esta era cuando se forjan las alianzas tribales que siglos después permitirán a criaturas como {npc:balgor-titan-de-chatarra} alcanzar un poder que roza lo divino.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era III — El Ascenso de los Nuevos Emperadores</h3>
          <span class="tomo-era-sub">Cuando una mujer escapó de sus cadenas y cuatro monstruos reclamaron el mar</span>
          <p>Hace 30 años, {npc:isabella-d-vega} escapa de la tiranía de su isla natal, forjando un odio eterno hacia el mundo noble. Cinco años después funda los <strong>Piratas Carmesí</strong> y se asocia temporalmente con el colosal gigante {npc:balgor-titan-de-chatarra}. A su lado, {npc:jack-el-inmortal} —un hombre cuya lealtad es tan inquebrantable como su capacidad para sobrevivir a lo imposible— jura proteger la bandera carmesí con su vida.</p>
          <p>Hace 20 años se consolida como amenaza mundial al chocar contra otros monstruos, mientras {npc:sekhmet-reina-leona} —una majestuosa Mink leona cuyo rugido hace temblar las islas— y {npc:shura-dios-de-la-ira} —una terrorífica Oni cuyo poder trasciende la comprensión humana— emergen en el Nuevo Mundo.</p>
          <p>Hace 15 años, {npc:valyria-almirante-de-flota} asciende meteóricamente a Almirante de Flota tras cortar una isla por la mitad, imponiendo el respeto absoluto. Bajo su mando, tres oficiales de talento excepcional —{npc:ken-dragon-azul}, {npc:flint-balas-de-plata} y {npc:nereida-el-abismo}— son elevados al rango de Almirantes, formando el cuarteto más temible que la Marina haya conocido jamás.</p>
          <p>Hace 10 años, {npc:balgor-titan-de-chatarra} asimila varias flotas enteras para convertirse en un Mecha gigante, deserta y se corona Yonko como <strong>«Titán de Chatarra»</strong>. Ese mismo año, {npc:ezekiel-el-arcangel} —un híbrido Skypiean/Lunarian— comienza a cazar piratas desde los cielos con su rifle de Diales, siendo catalogado como el cuarto Emperador. En las sombras del mundo, {npc:ignis-llama-del-sur} organiza células revolucionarias en docenas de islas.</p>
          <p>En los bajos fondos, figuras como {npc:cara-de-moneda-gils} —corredor del mercado negro con contactos en todas las facciones— y {npc:perro-rabioso-varg} —cyborg cazarrecompensas de instintos implacables— prosperan en el caos de una era definida por la ambición.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era IV — La Caída de la Reina</h3>
          <span class="tomo-era-sub">Cuando la Reina cayó y el mundo contuvo la respiración</span>
          <p>Hace 5 años, Isabella y sus leales —{npc:jack-el-inmortal} y {npc:aurelian-lira} entre ellos— llegan a <strong>La Última Isla</strong>. Descubre la verdad del mundo pero opta por no atacar aún, siendo coronada <strong>«Reina de los Piratas»</strong>.</p>
          <p>Hace un mes, {npc:balgor-titan-de-chatarra} traiciona su antiguo pacto vendiendo las coordenadas de Isabella a cambio de armamento. La Almirante de Flota {npc:valyria-almirante-de-flota} intercepta a Isabella y, tras un duelo de espadas legendario, la captura.</p>
          <p><strong>En la actualidad (Día 0):</strong> Faltan exactamente <strong>30 días</strong> para la ejecución pública de Isabella D. Vega en Marineford. Las fuerzas mundiales se preparan para la Guerra Total. Los Cuatro Emperadores mueven ficha. El mundo entra en su era más caótica y decisiva.</p>
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

