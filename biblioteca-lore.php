<?php
/**
 * One Piece: Eternal · Biblioteca de Lore y Cronología Histórica
 * Crónica oficial del mundo de One Piece: Eternal: historia, cronología, eras,
 * el Pacto de las Quince Coronas, las facciones, los Yonkou y el equilibrio del mundo.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'biblioteca-lore.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

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

$total_capitulos = 7;

header('Content-Type: text/html; charset=utf-8');
ob_start();
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Biblioteca de Lore y Cronología</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-guias bib-lore">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Biblioteca de Lore y Cronología</b></div></div>
<div class="wrap">

<section class="reveal">
  <div class="shead">
    <h1>Biblioteca de Lore & Cronología Histórica</h1>
    <span class="code">// crónica oficial del mundo · <?php echo $total_capitulos; ?> capítulos</span>
    <span class="rule"></span>
  </div>
  <p class="guia-intro">El <b>archivo oficial</b> de One Piece: Eternal: la historia del mundo desde el Pacto de las Quince Coronas hasta la <b>Ejecución del Ocaso</b>. Navega por los capítulos a la izquierda para explorar la crónica completa del mar.</p>
</section>

<section class="reveal">
  <div class="guide-shell">

    <nav class="guide-nav">
      <div class="guide-nav-inner" id="loreNav">
        <div class="nav-section">Capítulos</div>
        <button class="guide-nav-item active" data-guide="historia"><span class="n">I</span> Historia del Mundo</button>
        <button class="guide-nav-item" data-guide="cronologia"><span class="n">II</span> Cronología Histórica</button>
        <button class="guide-nav-item" data-guide="eras"><span class="n">III</span> Las Seis Eras</button>
        <button class="guide-nav-item" data-guide="poneglyphs"><span class="n">IV</span> Poneglyphs y Armas Ancestrales</button>
        <button class="guide-nav-item" data-guide="facciones"><span class="n">V</span> Las Facciones del Poder Mundial</button>
        <button class="guide-nav-item" data-guide="yonkou"><span class="n">VI</span> Los Señores del Nuevo Mundo</button>
        <button class="guide-nav-item" data-guide="equilibrio"><span class="n">VII</span> El Equilibrio y el Futuro</button>
      </div>
    </nav>

    <div class="guide-main">

      <!-- ============================================================ -->
      <!-- I · Historia del Mundo -->
      <!-- ============================================================ -->
      <div class="guide-content active" id="g-historia">
        <div class="g-title">Historia del Mundo</div>
        <div class="g-sub">// la crónica completa desde el origen hasta el presente</div>

        <p><span class="tomo-drop">L</span>a historia de este mundo no la escriben los vencedores. La escriben los que sobreviven lo suficiente para sostener la pluma. Mil doscientos veintidós años han pasado desde que quince monarcas de mares distintos firmaron un pacto que redefinió qué historia merecía ser recordada y cuál debía dejar de existir. Esto es lo que se sabe.</p>

        <div class="tomo-divider"></div>

        <h3 class="tomo-era-title">Antes del Año 0: El Silencio de las Quince Coronas</h3>
        <p>No existe una sola versión de lo que ocurrió antes del Pacto. Existen fragmentos, y los fragmentos no coinciden entre sí. En algún momento, durante un periodo que los pocos textos que lo mencionan sitúan en <strong>cuarenta días sin viento</strong>, el mar dejó de comportarse como el mar. Barcos que zarpaban no volvían. Barcos que volvían lo hacían con las tripulaciones envejecidas de golpe, o mudas, o ambas cosas. Ningún registro describe una guerra—solo describen que, durante esas seis semanas, algo dejó de estar donde debía y otra cosa ocupó su lugar. Los archivos lo llaman <strong>la Marea Muda</strong>.</p>

        <p>Cuando terminó, quince monarcas se reunieron en un punto del mapa que ningún documento posterior nombra igual dos veces. De esa reunión—el <strong>Concilio de las Quince Coronas</strong>—salieron dos cosas: el <strong>Pacto de las Quince Coronas</strong>, germen del actual Gobierno Mundial, y un silencio deliberado sobre un decimosexto nombre que debería haber estado en esa mesa y no lo estuvo.</p>

        <p><strong>Vethmar.</strong> Los pocos fragmentos que mencionan ese nombre—tallados en calcos, susurrados en canciones de cuna prohibidas—coinciden en muy poco, salvo en una cosa: antes de la Marea Muda, Vethmar existía. Después, no. Ni como reino, ni como ruina, ni como nombre en un mapa. Se sabe que, de las Quince Coronas originales, solo un puñado de nombres sobrevive en registros parciales: una reina recordada solo como <strong>«la de Cael»</strong>, un rey cuyo sello aparece en tres tratados distintos bajo tres grafías distintas de su propio nombre, y una silla—la decimoquinta—de la que ningún documento posterior admite quién la ocupó.</p>

        <h3 class="tomo-era-title">La Fundación del Gobierno Mundial</h3>
        <p>En el año 1 E.E. se fundó <strong>Mariejois</strong> como sede del nuevo poder, en un territorio que oficialmente «no pertenecía a nadie». Las Catorce Coronas—una menos que en la mesa original, aunque nadie explicó nunca qué había pasado con la decimoquinta—cedieron soberanía a una entidad supranacional cuyo propósito declarado era mantener el orden. Su propósito real, como los siglos se encargarían de demostrar, era garantizar que ciertos nombres jamás volvieran a pronunciarse.</p>

        <p>Nacieron entonces las instituciones que han regido el mundo hasta hoy: la <strong>Marina</strong> como brazo armado (fundada en el 87 E.E.), los <strong>Cipher Pol</strong> como agentes del silencio, el <strong>Tributo Celestial</strong> como mecanismo de sumisión económica, y un sistema de vigilancia y censura que ha sofocado cualquier intento de reconstruir la verdad.</p>

        <h3 class="tomo-era-title">El Nacimiento de la Piratería y la Era Dorada</h3>
        <p>Durante siglos, el equilibrio se sostuvo a base de administración del miedo. La Era del Silencio (0–40 E.E.) instauró las primeras leyes de censura. La Era de las Rutas Trazadas (40–210 E.E.) domesticó el Grand Line con el <strong>Log Pose</strong> de Anselm Roka y dio a luz a la Marina. Pero fue en la Era de los Reinos Errantes (210–440 E.E.) cuando la piratería—hasta entonces un problema menor—se convirtió en una fuerza geopolítica imparable.</p>

        <p>El Gobierno Mundial, incapaz de erradicarla, tomó una decisión que definiría el equilibrio mundial hasta hoy: <strong>formalizarla</strong>. Nacieron los <strong>Yonkou</strong> como «mal necesario», el <strong>Cartel Real de Recompensas</strong> como maquinaria de propaganda, y el título de <strong>Rey o Reina Pirata</strong>—un trono que el mundo entero sabía que existía aunque el Gobierno nunca lo reconociera oficialmente.</p>

        <h3 class="tomo-era-title">La Paz Fingida y el Despertar de la Rebelión</h3>
        <p>Ochocientos cincuenta años que el Gobierno Mundial presentó como «una larga paz» fueron, en realidad, los <strong>Siglos de Hambre</strong>: generaciones enteras de bloqueos comerciales selectivos, impuestos de protección naval que nadie pidió y cosechas confiscadas «por seguridad alimentaria regional». De esa acumulación de silencios administrativos nació <strong>la Masacre de Puerto Ceniza</strong> y, de sus cenizas, el <strong>Ejército Revolucionario</strong>, fundado por una testigo que la posteridad recuerda simplemente como <strong>Séraphine</strong>—y cuyo lema aún resuena: <em>«Ningún trono debería ser eterno.»</em></p>

        <h3 class="tomo-era-title">La Era de las Mareas Tensas y la Reina Pirata</h3>
        <p>Entre 1460 y 1472 E.E., un relevo generacional casi simultáneo en las grandes facciones dejó sillas vacías que una nueva generación ocuparía. Fue en ese contexto donde nació, en 1472 E.E., <strong>Selene Kestrel</strong>, hija de un joven Capitán de la Marina llamado <strong>Alaric Kestrel</strong>. De su madre, la historia oficial no dice una sola palabra.</p>

        <p>Selene robó un balandro de la Marina a los diecisiete años, desapareció seis años, y reapareció al mando de su propia tripulación. En 1505 se enfrentó a un Yonko de la generación saliente—sin testigos, sin registro oficial—y el Yonko desapareció. En 1511 reclamó el trono vacante de <strong>Reina Pirata</strong>, convirtiéndose en la persona más buscada del planeta. Comenzó la <strong>Década de la Reina sin Corona</strong>.</p>

        <p>En paralelo, su padre ascendía hasta convertirse en <strong>Almirante de Flota</strong> en 1508—el rango más alto de la Marina. Padre e hija, la ley y el crimen, enfrentados en una persecución que el mundo entero siguió con fascinación creciente. El Gobierno Mundial empezó a presionar a Alaric en 1520, cuestionando su lealtad. Alaric nunca respondió.</p>

        <h3 class="tomo-era-title">El Calco de Vethmar y la Captura</h3>
        <p>Semanas antes de su caída, la tripulación de Selene interceptó un cargamento de la Marina que transportaba un calco cubierto de coral. Al limpiarlo, apareció una palabra repetida en tres alfabetos muertos: <strong>Vethmar</strong>.</p>

        <p>Poco después, Selene Kestrel fue capturada. No en combate contra su padre, como el mundo esperaba, sino <strong>traicionada por alguien de su propia tripulación</strong>—una identidad que ningún registro ha confirmado. El calco fue confiscado en la misma redada.</p>

        <h3 class="tomo-era-title">La Ejecución del Ocaso</h3>
        <p>Hoy, 1522 E.E., Selene Kestrel aguarda su ejecución en Marineford. El mundo entero—Yonkou, revolucionarios, reinos neutrales, cazarrecompensas y un padre que aún no ha dicho una sola palabra—espera a ver qué ocurre cuando llegue la marea alta. La pregunta que nadie se atreve a responder en voz alta: ¿qué ocurrirá si la Reina Pirata, antes de morir, revela lo que sabe de Vethmar?</p>

        <p class="tomo-quote">«Ningún trono debería ser eterno.» — Séraphine, fundadora del Ejército Revolucionario</p>
      </div>

      <!-- ============================================================ -->
      <!-- II · Cronología -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-cronologia">
        <div class="g-title">Cronología Histórica</div>
        <div class="g-sub">// la línea temporal completa del mar</div>
        <p><span class="tomo-drop">E</span>l tiempo en este mundo no se mide solo por calendarios, sino por las cicatrices que los grandes eventos dejan en el océano. Esta es la <strong>Línea Temporal Oficial</strong> de One Piece: Eternal, desde antes del año 0 E.E. hasta el presente.</p>

        <div class="tomo-divider"></div>

        <ul class="tomo-timeline">

          <li class="tomo-tl-item"><span class="tomo-tl-year">Antes del Año 0</span><span class="tomo-tl-text"><strong>La Marea Muda.</strong> Durante cuarenta días sin viento, el mar deja de comportarse como el mar. Barcos que zarpan no vuelven. Barcos que vuelven lo hacen con tripulaciones envejecidas o mudas. Algo dejó de estar donde debía y otra cosa ocupó su lugar.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">Año 0 E.E.</span><span class="tomo-tl-text"><strong>El Concilio de las Quince Coronas.</strong> Quince monarcas de mares distintos firman el Pacto en un punto del mapa que ningún documento posterior nombra igual dos veces. Una decimosexta corona—Vethmar—no está presente. Nace el Gobierno Mundial.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1 E.E.</span><span class="tomo-tl-text"><strong>Fundación de Mariejois.</strong> La sede del Gobierno Mundial se alza sobre tierras que oficialmente «no pertenecían a nadie».</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">12 E.E.</span><span class="tomo-tl-text"><strong>Primera Ley del Silencio.</strong> Se prohíbe la mención pública de reinos, pueblos o linajes no reconocidos por el Concilio fundador. No menciona a Vethmar por su nombre: no hace falta.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">12–40 E.E.</span><span class="tomo-tl-text"><strong>La Quema de las Voces.</strong> Primera campaña de confiscación y destrucción de calcos y textos antiguos. Centenares de tablillas y pergaminos son destruidos. Los pocos que sobreviven lo hacen porque alguien los escondió.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~60 E.E.</span><span class="tomo-tl-text"><strong>El Log Pose.</strong> El cartógrafo Anselm Roka sistematiza el uso de imanes especiales sensibles al magnetismo del Grand Line. Por primera vez, cruzar el mar más peligroso del mundo deja de ser una apuesta ciega.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">87 E.E.</span><span class="tomo-tl-text"><strong>Fundación de la Marina.</strong> Las flotillas privadas de las Catorce Coronas se fusionan en un cuerpo militar único. Su primer Almirante de Flota es recordado simplemente como «el Fundador».</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~150 E.E.</span><span class="tomo-tl-text"><strong>Primer caso de Akuma no Mi.</strong> Un marinero come un fruto de aspecto repugnante rescatado de un naufragio. Descubre que ya no puede nadar—y que ha ganado un poder que ningún médico sabe explicar.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~150–210 E.E.</span><span class="tomo-tl-text"><strong>Avistamientos de Urano.</strong> Múltiples avistamientos no confirmados de una de las tres Armas Ancestrales. Ninguno vuelve a repetirse después del 210 E.E.—como si, trazado el mapa del mundo, alguien hubiera decidido que ya no hacía falta que el arma se dejara ver.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">210–440 E.E.</span><span class="tomo-tl-text"><strong>Era de los Reinos Errantes.</strong> Doscientos treinta años de guerra dinástica. El Dominio de Coriel, el Reino de Ashkar, la Batalla de los Mil Mástiles. Sobre las ruinas, asciende la Casa Vhoss en Alabasta—que ochocientos años después sigue gobernando el mismo desierto.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~450 E.E.</span><span class="tomo-tl-text"><strong>Nacimiento de los Yonkou.</strong> El Gobierno Mundial reconoce extraoficialmente a los Cuatro Emperadores como «mal necesario»—bastante más barato tolerarlos que combatirlos.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~480 E.E.</span><span class="tomo-tl-text"><strong>Cassian Draeger — Primer Rey Pirata.</strong> Unifica veinte tripulaciones bajo un solo estandarte. Desaparece sin dejar rastro. Su barco aparece vacío años después, sin señales de combate. Los registros mencionan una palabra tachada que solo se adivina por la forma del trazo.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~500 E.E.</span><span class="tomo-tl-text"><strong>Cartel Real de Recompensas.</strong> Nace el sistema de carteles que define cómo el mundo conoce—o cree conocer—a sus criminales más famosos.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">610–1460 E.E.</span><span class="tomo-tl-text"><strong>Los Siglos de Hambre.</strong> Ochocientos cincuenta años de «larga paz» que el Gobierno Mundial prefiere no detallar: bloqueos comerciales, impuestos de protección naval, cosechas confiscadas. Cientos de crisis locales que nadie se molestó en sumar.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~900 E.E.</span><span class="tomo-tl-text"><strong>Masacre de Puerto Ceniza.</strong> La represión de una protesta pesquera arrasa un puerto entero. Una testigo llamada Séraphine funda el Ejército Revolucionario bajo el lema: «Ningún trono debería ser eterno.»</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">~1000 E.E.</span><span class="tomo-tl-text"><strong>Guerra de los Cuatro Tronos.</strong> Última gran guerra entre potencias piratas. Más de una década de conflicto que reorganiza el Nuevo Mundo en cuatro asientos estables de Yonko.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1460–1472 E.E.</span><span class="tomo-tl-text"><strong>El Gran Relevo.</strong> Varias figuras clave del equilibrio mundial se retiran o mueren casi simultáneamente. Una nueva generación se prepara para ocupar las sillas vacías.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1472 E.E.</span><span class="tomo-tl-text"><strong>Nace Selene Kestrel.</strong> Hija de Alaric Kestrel, entonces joven Capitán de la Marina. De su madre no se dice una sola palabra.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1474 E.E.</span><span class="tomo-tl-text"><strong>Alaric asciende a Comodoro.</strong> Tras una campaña de pacificación en el Nuevo Mundo cuyos detalles permanecen clasificados.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1478 E.E.</span><span class="tomo-tl-text"><strong>Guerra interna de la Manada de la Luna Llena.</strong> Una guerra civil entre los Mink que prepara el terreno para el ascenso de una nueva Yonko años después.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1489 E.E.</span><span class="tomo-tl-text"><strong>Selene abandona la Marina.</strong> A los diecisiete años, roba un balandro y desaparece durante seis años tras una discusión con su padre que ninguno ha contado igual dos veces.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1491 E.E.</span><span class="tomo-tl-text"><strong>Alaric asciende a Vicealmirante.</strong> En su discurso evita mencionar a su hija de forma tan notoria que el silencio es la noticia del día.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1495 E.E.</span><span class="tomo-tl-text"><strong>Selene forma su tripulación.</strong> Reaparece en las Blues al mando de su propia tripulación. Sus primeras recompensas circulan en tabernas antes de llegar a los carteles oficiales.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1502 E.E.</span><span class="tomo-tl-text"><strong>Alaric asciende a Almirante.</strong></span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1505 E.E.</span><span class="tomo-tl-text"><strong>Selene se enfrenta a un Yonko.</strong> Sin testigos fiables. Sin registro oficial. El Yonko desaparece. Un asiento de Emperador queda vacante.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1508 E.E.</span><span class="tomo-tl-text"><strong>Alaric, Almirante de Flota.</strong> El rango más alto de la Marina. Apenas tres años antes de que su hija reclame el título de Reina Pirata.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1511 E.E.</span><span class="tomo-tl-text"><strong>Selene, Reina Pirata.</strong> Su hazaña es tan incómoda para el Gobierno Mundial que ningún cartel ni comunicado oficial la describe. Comienza la Década de la Reina sin Corona.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1516 E.E.</span><span class="tomo-tl-text"><strong>Crónicas de Corvina Ashworth.</strong> La periodista publica sus crónicas sobre la persecución entre padre e hija. El World Economic Journal duplica su tirada.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1519 E.E.</span><span class="tomo-tl-text"><strong>Nadira Vashti asciende.</strong> Nueva Comandante Suprema del Ejército Revolucionario tras la muerte de su predecesor, en circunstancias que ni sus propias filas conocen.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1520 E.E.</span><span class="tomo-tl-text"><strong>Presión del Gobierno.</strong> Cuestionan públicamente la lealtad del Almirante de Flota por su parentesco con la pirata más buscada. Alaric no responde.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1521 E.E.</span><span class="tomo-tl-text"><strong>El Calco de Vethmar.</strong> La tripulación de Selene intercepta un calco cubierto de coral. Al limpiarlo, aparece una palabra repetida en tres alfabetos muertos: Vethmar.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1521 E.E.</span><span class="tomo-tl-text"><strong>Captura de la Reina Pirata.</strong> Selene es traicionada por alguien de su propia tripulación—identidad no confirmada. El calco es confiscado.</span></li>

          <li class="tomo-tl-item"><span class="tomo-tl-year">1522 E.E. — Presente</span><span class="tomo-tl-text"><strong>La Ejecución del Ocaso.</strong> Sentenciada a muerte. La ejecución se fija en Marineford para la próxima marea alta. El mundo entero espera.</span></li>

        </ul>
      </div>

      <!-- ============================================================ -->
      <!-- III · Las Seis Eras -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-eras">
        <div class="g-title">Las Seis Eras del Mundo</div>
        <div class="g-sub">// del silencio fundacional a la ejecución de la Reina Pirata</div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era I — El Silencio (0–40 E.E.)</h3>
          <span class="tomo-era-sub">Administración del miedo y fundación del nuevo orden</span>
          <p><strong>Contexto:</strong> Los primeros cuarenta años tras el Pacto fueron años de consolidación del miedo. El Gobierno Mundial no tenía flota propia, ni territorio unificado, ni más autoridad que la que las Catorce Coronas quisieran cederle.</p>
          <p><strong>Eventos clave:</strong> Fundación de Mariejois (1 E.E.), Primera Ley del Silencio (12 E.E.), la Quema de las Voces, y la Revuelta de los Pescadores Grises—aplastada en un mes, pero que dejó una lección imborrable: gobernar el mar entero iba a requerir una flota.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era II — Las Rutas Trazadas (40–210 E.E.)</h3>
          <span class="tomo-era-sub">El mapa del mundo se completa por primera vez</span>
          <p><strong>Contexto:</strong> Si la Era del Silencio fue la de fundar el poder, esta fue la de aprender a ejercerlo sobre un mapa trazable. Anselm Roka sistematiza el Log Pose. La Marina nace como institución unificada (87 E.E.).</p>
          <p><strong>Eventos clave:</strong> Fundación de la Compañía de las Cien Velas, primer caso documentado de Akuma no Mi, y múltiples avistamientos no confirmados de Urano—que cesan abruptamente en el 210 E.E.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era III — Los Reinos Errantes (210–440 E.E.)</h3>
          <span class="tomo-era-sub">Dos siglos de guerra dinástica y el nacimiento de la piratería como potencia</span>
          <p><strong>Contexto:</strong> Doscientos treinta años de conflicto casi ininterrumpido. La Marina, joven e incapaz de controlarlo todo, ve emerger y desaparecer reinos enteros. Del caos nacen las primeras grandes familias piratas reconocidas como potencias navales.</p>
          <p><strong>Eventos clave:</strong> Auge y caída del Dominio de Coriel, Batalla de los Mil Mástiles que extingue el Reino de Ashkar, ascenso de la Casa Vhoss en Alabasta.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era IV — La Era Dorada de la Piratería (440–610 E.E.)</h3>
          <span class="tomo-era-sub">El Gobierno Mundial aprende a formalizar lo que no puede erradicar</span>
          <p><strong>Contexto:</strong> Fracasados dos siglos de intentos de erradicación, el Gobierno Mundial toma una decisión histórica: reconocer extraoficialmente a los Yonkou como mal necesario. Nace el sistema que define el equilibrio hasta hoy.</p>
          <p><strong>Eventos clave:</strong> Primeros Yonkou reconocidos, Cassian Draeger—primer Rey Pirata—desaparece misteriosamente, fundación del Cartel Real de Recompensas.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era V — La Paz Fingida (610–1460 E.E.)</h3>
          <span class="tomo-era-sub">Ochocientos cincuenta años que el Gobierno llama «paz»</span>
          <p><strong>Contexto:</strong> La era más larga y la más incómoda para los archivos oficiales. No hubo grandes guerras—hubo cientos de crisis locales, bloqueos selectivos y hambrunas sistémicas. La vigilancia del Gobierno se sofistica hasta convertirse en Cipher Pol.</p>
          <p><strong>Eventos clave:</strong> Los Siglos de Hambre, Masacre de Puerto Ceniza (~900 E.E.) que da origen al Ejército Revolucionario, Guerra de los Cuatro Tronos (~1000 E.E.) que reorganiza a los Yonko.</p>
        </div>

        <div class="tomo-era">
          <h3 class="tomo-era-title">Era VI — Las Mareas Tensas (1460 E.E. – Presente)</h3>
          <span class="tomo-era-sub">El Gran Relevo, la Reina Pirata y la Ejecución del Ocaso</span>
          <p><strong>Contexto:</strong> Un relevo generacional casi simultáneo en las grandes facciones (1460–1472) deja paso a una nueva generación. En ese contexto nace Selene Kestrel, cuya historia define el siglo.</p>
          <p><strong>Eventos clave:</strong> Ascenso de Alaric de Capitán a Almirante de Flota (1472–1508), Selene se convierte en Reina Pirata (1511), Década de la Reina sin Corona, descubrimiento del calco de Vethmar, captura de Selene por traición interna (1521), y la inminente Ejecución del Ocaso (1522).</p>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- IV · Poneglyphs y Armas Ancestrales -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-poneglyphs">
        <div class="g-title">Poneglyphs y Armas Ancestrales</div>
        <div class="g-sub">// los archivos de piedra del mundo perdido</div>

        <p><span class="tomo-drop">L</span>os Poneglyphs son monolitos de un material desconocido—una aleación de piedra y metal que ni el fuego, ni el acero, ni el paso de los milenios puede dañar. Fueron creados por una civilización anterior al Pacto de las Quince Coronas, cuyos sabios—previendo su destrucción—grabaron la verdad en estas piedras indestructibles.</p>

        <div class="tomo-divider"></div>

        <h3 class="tomo-era-title">El Sistema de Poneglyphs</h3>
        <p>Están grabados en una lengua antigua que solo aquellos con el oído entrenado o la percepción del <strong>Tribu del Tercer Ojo</strong> pueden descifrar. Se dividen en tres tipos:</p>
        <ul class="tomo-era-list">
          <li><strong>Poneglyphs Históricos:</strong> Contienen fragmentos de la historia anterior al Pacto. Dispersos por todo el Grand Line, cada uno revela una pieza del rompecabezas de lo que ocurrió antes del año 0 E.E.</li>
          <li><strong>Poneglyphs de Instrucción:</strong> Marcan ubicaciones de artefactos clave, incluyendo las Armas Ancestrales.</li>
          <li><strong>Road Poneglyphs (×4):</strong> Los más codiciados. Cada uno contiene una coordenada. Reunir los cuatro revela la ubicación de <strong>La Última Isla</strong>, donde se encuentra la verdad completa.</li>
        </ul>

        <h3 class="tomo-era-title">Las Armas Ancestrales</h3>
        <p>Tres artefactos de poder absoluto fueron creados antes del Pacto. Su mera existencia es un secreto que el Gobierno Mundial ha matado por proteger:</p>
        <ul class="tomo-era-list">
          <li><strong>Poseidón:</strong> El poder de comandar a los Reyes Marinos. No es un objeto, sino un don biológico que se manifiesta en la realeza de la Isla Gyojin. No hay registros de su portador actual.</li>
          <li><strong>Plutón:</strong> El acorazado de guerra definitivo, capaz de pulverizar islas. Su ubicación se perdió en los registros oficiales—o fue borrada deliberadamente.</li>
          <li><strong>Urano:</strong> El arma de los cielos. Múltiples avistamientos no confirmados entre los años 150 y 210 E.E. Ninguno desde entonces. Como si alguien hubiera decidido que ya no hacía falta que el arma se dejara ver.</li>
        </ul>

        <h3 class="tomo-era-title">El Calco Maldito</h3>
        <p>En 1521 E.E., la tripulación de la Reina Pirata Selene Kestrel interceptó un cargamento de la Marina que transportaba un calco cubierto de coral. Cuando los arqueólogos de la tripulación limpiaron la superficie, encontraron una palabra repetida en tres alfabetos muertos:</p>

        <p class="tomo-quote">«Vethmar.»</p>

        <p>El Gobierno Mundial confiscó el calco en la misma redada en que capturaron a Selene. Lo que contenía exactamente—y por qué su mera existencia justificaba el sigilo de un cargamento escoltado por la Marina—sigue siendo, a día de hoy, la pregunta más peligrosa del mundo.</p>
      </div>

      <!-- ============================================================ -->
      <!-- V · Las Facciones del Poder Mundial -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-facciones">
        <div class="g-title">Las Facciones del Poder Mundial</div>
        <div class="g-sub">// las seis fuerzas que mueven el mar</div>

        <p><span class="tomo-drop">M</span>il doscientos años después del Pacto, seis grandes facciones compiten, colaboran y chocan en un equilibrio frágil que la Ejecución del Ocaso amenaza con romper.</p>

        <div class="tomo-divider"></div>

        <h3 class="tomo-era-title">La Marina y los Almirantes</h3>
        <p>La fuerza militar marítima oficial del Gobierno Mundial. Fundada en el 87 E.E., su misión declarada es mantener el orden en los mares. Su misión real, heredada del Pacto fundacional, es garantizar que ciertas verdades nunca salgan a la luz.</p>
        <p><strong>Liderazgo:</strong> El Almirante de Flota {npc:alaric-kestrel}—ascendido de Capitán a la cúpula de la Marina en una carrera meteórica. Su campaña de pacificación de 1474 E.E. permanece clasificada. Desde 1520, el Gobierno Mundial cuestiona abiertamente su lealtad por su parentesco con la Reina Pirata. Hasta ahora, Alaric no ha respondido. Bajo su mando sirven tres Almirantes, cuyas identidades y doctrinas definen el carácter de la institución.</p>
        <p>La base principal de la Marina es <strong>Marineford</strong>, donde Selene Kestrel aguarda su ejecución. La moral de la institución está en tensión máxima: ejecutar a la hija del propio Almirante de Flota—o no hacerlo—es una decisión que dividirá a la Marina para siempre.</p>

        <h3 class="tomo-era-title">El Gobierno Mundial y Cipher Pol</h3>
        <p>La autoridad suprema del mundo reside en <strong>Mariejois</strong>, fundada sobre tierras que oficialmente «no pertenecían a nadie». Su símbolo más elocuente es el <strong>Trono Vacío</strong>—la decimoquinta silla del Concilio original, que nunca se ha ocupado oficialmente. Los historiadores más perspicaces sostienen que esa silla es la de Vethmar: su monarca firmó su propio pacto de desaparición a cambio de algo que nadie ha sabido nunca qué fue.</p>
        <p>Los <strong>Cipher Pol</strong> son el brazo de inteligencia, espionaje y eliminación que opera en las sombras. Su existencia se niega con el mismo entusiasmo con el que se ejerce.</p>

        <h3 class="tomo-era-title">Los Yonkou — Los Cuatro Emperadores</h3>
        <p>Las cuatro fuerzas piratas más poderosas del Nuevo Mundo. Su guerra fría define el equilibrio del mar más peligroso del planeta. Tras el relevo generacional de 1460–1472 E.E., los cuatro asientos de Emperador están ocupados por una nueva generación de titanes—cada uno con su propio Poneglyph Rojo, su propio territorio y sus propias ambiciones. (Ver capítulo VI para el detalle completo.)</p>

        <h3 class="tomo-era-title">El Ejército Revolucionario</h3>
        <p>Fundado tras la Masacre de Puerto Ceniza por una testigo llamada <strong>Séraphine</strong>, el Ejército Revolucionario es la organización clandestina más peligrosa para el Gobierno Mundial. Su lema—«Ningún trono debería ser eterno»—es también su programa político: la abolición del Tributo Celestial y el fin del régimen de Mariejois.</p>
        <p><strong>Liderazgo:</strong> La Comandante Suprema {npc:nadira-vashti}, ascendida en 1519 E.E. tras la muerte de su predecesor en circunstancias que ni las filas más leales del Ejército conocen por completo.</p>

        <h3 class="tomo-era-title">El Gremio de Cazarrecompensas</h3>
        <p>Una potencia mercenaria independiente que ha crecido hasta rivalizar con la Marina en capacidad de combate. Sin ideales políticos ni lealtades divididas: cazan piratas por dinero. Sus líderes están por definir, y su base de operaciones—la <strong>Isla de la Balanza</strong>—es un puerto franco neutral en el Grand Line.</p>

        <h3 class="tomo-era-title">Los Reinos y el Pueblo Llano</h3>
        <p>Más del ochenta por ciento de la población mundial no pertenece a ninguna facción armada. Son los comerciantes, artesanos, médicos y ciudadanos que mantienen el mundo funcionando mientras los poderosos deciden su destino. El <strong>Tributo Celestial</strong>—el impuesto que cada reino aliado paga a Mariejois—es el mecanismo que mantiene a millones de personas atadas a un sistema que no eligieron.</p>
      </div>

      <!-- ============================================================ -->
      <!-- VI · Los Señores del Nuevo Mundo -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-yonkou">
        <div class="g-title">Los Señores del Nuevo Mundo</div>
        <div class="g-sub">// los Cuatro Emperadores y sus imperios</div>

        <p><span class="tomo-drop">E</span>l Nuevo Mundo es la segunda mitad del Grand Line, el mar más peligroso del planeta. Allí, el clima obedece a la voluntad de los más fuertes, y cuatro figuras se han alzado como dueños absolutos de sus territorios: los <strong>Cuatro Emperadores (Yonkou)</strong>. Cada uno custodia uno de los Road Poneglyphs necesarios para encontrar La Última Isla.</p>

        <div class="tomo-divider"></div>

        <p>Los cuatro asientos de Emperador están ocupados por la nueva generación surgida del relevo silencioso de 1460–1472 E.E. Sin embargo, los nombres de los Yonkou actuales permanecen <strong>sin definir</strong> en los registros públicos—un silencio deliberado que el Gobierno Mundial mantiene como estrategia de información.</p>

        <p>Lo que se sabe es que cada uno controla un vasto territorio en el Nuevo Mundo, comanda una flota de guerra capaz de enfrentarse a la Marina, y protege uno de los cuatro Road Poneglyphs. Su guerra fría es el telón de fondo de la Era de las Mareas Tensas, y la Ejecución del Ocaso podría ser la chispa que la convierta en llama abierta.</p>

        <p class="tomo-quote">«Los Emperadores no esperan. Acechan.»</p>
      </div>

      <!-- ============================================================ -->
      <!-- VII · El Equilibrio y el Futuro -->
      <!-- ============================================================ -->
      <div class="guide-content" id="g-equilibrio">
        <div class="g-title">El Equilibrio y el Futuro</div>
        <div class="g-sub">// Vethmar, la profecía y la guerra que se avecina</div>

        <p><span class="tomo-drop">E</span>l mundo de One Piece: Eternal se asoma al abismo. La Ejecución del Ocaso será la chispa que encienda la pradera—o el silencio que demuestre que el Gobierno Mundial sigue teniendo el control. Pero incluso antes de que la marea alta llegue a Marineford, las fuerzas del destino ya están en movimiento.</p>

        <div class="tomo-divider"></div>

        <h3 class="tomo-era-title">El Equilibrio de los Tres Grandes Poderes</h3>
        <p>Durante siglos, el mundo se mantuvo estable bajo el esquema clásico: el Gobierno Mundial y la Marina, los Cuatro Emperadores, y el Ejército Revolucionario. Pero ese equilibrio nunca fue simétrico—el Gobierno Mundial siempre fue el polo dominante, con los recursos de 170 reinos aliados y el control absoluto de la información.</p>
        <p>La captura de Selene Kestrel ha roto ese equilibrio:</p>
        <ul class="tomo-era-list">
          <li><strong>El Gobierno Mundial</strong> cree que ejecutar a la Reina Pirata enviará un mensaje definitivo de autoridad. Pero el calco de Vethmar—confiscado pero no destruido—plantea una pregunta incómoda: ¿y si Selene habla antes de morir?</li>
          <li><strong>Los Yonkou</strong> ven en el vacío de poder una oportunidad. Si la Marina se debilita durante la ejecución, el Nuevo Mundo será un campo de batalla abierto.</li>
          <li><strong>El Ejército Revolucionario</strong>, bajo el mando de Nadira Vashti, ha estado esperando este momento. La atención mundial centrada en Marineford es la cobertura perfecta.</li>
          <li><strong>Las nuevas generaciones</strong> han escuchado las palabras de Selene desde su celda: «Mi legado no cabe en una horca.» ¿Quién no querría demostrar que tiene razón?</li>
        </ul>

        <h3 class="tomo-era-title">El Misterio de Vethmar</h3>
        <p>El calco que la tripulación de Selene interceptó en 1521 no es el primero que menciona ese nombre—pero sí el primero que la Reina Pirata tuvo en sus manos. Lo que contenía exactamente, y por qué la Marina lo transportaba con tal sigilo, es una pregunta que el Gobierno Mundial ha matado por mantener sin respuesta.</p>
        <p>El símbolo que algunos descendientes de linajes borrados siguen tallando en lugares donde nadie los busca—una corona partida por una ola—no aparece en ningún registro oficial. Aparece, eso sí, en el margen de al menos un calco confiscado en 1521. La corona partida podría ser la única pista física que queda de un reino que el mundo entero acordó olvidar.</p>

        <h3 class="tomo-era-title">El Rol de los Jugadores en la Nueva Era</h3>
        <p>El presente de One Piece: Eternal no es un escenario fijo. Cada decisión—cada batalla ganada, cada isla liberada, cada alianza forjada—cambia el equilibrio del mundo. Las posibilidades son infinitas:</p>
        <ul class="tomo-era-list">
          <li><strong>¿Puede alguien descubrir qué ocurrió realmente con Vethmar?</strong> Los calcos existen. Los fragmentos están dispersos. Quien reúna las piezas tendrá en sus manos la verdad que el Gobierno Mundial lleva mil doscientos años enterrando.</li>
          <li><strong>¿Puede un Marine mantener su lealtad mientras cuestiona la Justicia Absoluta?</strong> La Marina está dividida, y la ejecución de Selene—hija de su propio Almirante de Flota—forzará a cada oficial a elegir bando.</li>
          <li><strong>¿Puede alguien impedir la ejecución?</strong> El mundo entero se lo pregunta. Los Yonkou, los revolucionarios, los cazarrecompensas y un padre que aún no ha dicho una sola palabra.</li>
          <li><strong>¿Puede alguien ocupar el trono de Reina Pirata?</strong> Selene demostró que el título puede reclamarse. El mar espera a quien tenga el valor de intentarlo.</li>
        </ul>

        <h3 class="tomo-era-title">Las Preguntas que Quedan</h3>
        <p>Ninguna crónica está completa sin preguntas sin respuesta. Estas son las que definirán el futuro de One Piece: Eternal:</p>
        <ul class="tomo-era-list">
          <li>¿Qué descubrió Cassian Draeger, el primer Rey Pirata, antes de desaparecer sin dejar rastro?</li>
          <li>¿Qué contiene el calco de Vethmar que el Gobierno Mundial confiscó junto a Selene?</li>
          <li>¿Quién traicionó a la Reina Pirata? ¿Y por qué?</li>
          <li>¿Puede la ejecución de Selene ocurrir sin desatar una guerra global? ¿O acaso la guerra es exactamente lo que alguien quiere?</li>
          <li>¿Qué significa realmente la corona partida por una ola—el símbolo de los linajes borrados?</li>
          <li>Y la más importante: <strong>¿qué harás tú cuando la marea llegue a Marineford?</strong></li>
        </ul>

        <p class="tomo-quote">«La historia no la escriben los vencedores. La escriben los que sobreviven lo suficiente para sostener la pluma.» — Archivo Naval de Mariejois</p>
      </div>

    </div>
  </div>
</section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  const nav = document.getElementById('loreNav');
  if (!nav) return;
  const buttons = nav.querySelectorAll('.guide-nav-item');
  const contents = document.querySelectorAll('.guide-content');
  function activate(id) {
    buttons.forEach(b => b.classList.toggle('active', b.dataset.guide === id));
    contents.forEach(c => c.classList.toggle('active', c.id === 'g-' + id));
    window.scrollTo({ top: nav.closest('.reveal').offsetTop - 20, behavior: 'smooth' });
  }
  buttons.forEach(b => b.addEventListener('click', () => activate(b.dataset.guide)));
  window.addEventListener('hashchange', function() {
    const hash = location.hash.replace('#','');
    if (hash && document.getElementById('g-' + hash)) activate(hash);
  });
  const initial = location.hash.replace('#','');
  if (initial && document.getElementById('g-' + initial)) activate(initial);
})();

if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
}
</script>

</body>
</html>
<?php
$output = ob_get_clean();
$output = tomo_npcify($output, $npc_map, $bburl);
echo $output;
