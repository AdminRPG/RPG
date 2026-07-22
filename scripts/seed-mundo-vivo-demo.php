<?php
/**
 * DEMO Mundo Vivo — simula la respuesta de la IA (4 bloques) para un mes de PAZ
 * MEDIDA (lo normal): el mundo está mayormente tranquilo, con un único foco de
 * conflicto LOCALIZADO (no guerra) en el Nuevo Mundo. Muestra además la VARIEDAD
 * de maquetación del periódico (imagen ancha centrada, reportaje a una columna,
 * cita destacada, anuncios/clasificados y cartel de SE BUSCA).
 *
 * Se publica por el MISMO pipeline que el panel de staff
 * (parse -> aplicar estado -> guardar periódico/noticia -> noticia de portada).
 *
 * Uso:  php scripts/seed-mundo-vivo-demo.php
 * Idempotente para demo: limpia noticias previas de origen mundo_vivo antes de publicar.
 */

define('IN_MYBB', 1);
require dirname(__DIR__) . '/inc/init.php';
require_once dirname(__DIR__) . '/inc/ope_rol_mundo.php';

$ciclo = ope_rol_mv_ciclo_actual();
if (!$ciclo) { fwrite(STDERR, "No hay ciclo abierto.\n"); exit(1); }
$ciclo_id = (int) $ciclo['ciclo_id'];

// ── BLOQUE 1: nuevo estado del mundo (PAZ medida; base ~calma) ──
$estado = array(
    'zonas' => array(
        'east-blue'  => array('est'=>66,'mar'=>55,'pir'=>28,'rev'=>12,'eco'=>60,'civ'=>66,'pel'=>22,'notas'=>'Mes tranquilo en el mar del amanecer: buen comercio en los puertos y patrullas rutinarias de la Marina.'),
        'west-blue'  => array('est'=>58,'mar'=>48,'pir'=>34,'rev'=>30,'eco'=>52,'civ'=>55,'pel'=>30,'notas'=>'Circulan panfletos revolucionarios en las tabernas, pero sin incidentes serios. La vida sigue su curso.'),
        'north-blue' => array('est'=>62,'mar'=>58,'pir'=>30,'rev'=>15,'eco'=>57,'civ'=>60,'pel'=>33,'notas'=>'Inviernos duros y pesca abundante; alguna reyerta portuaria sin mayor recorrido.'),
        'south-blue' => array('est'=>54,'mar'=>44,'pir'=>40,'rev'=>26,'eco'=>48,'civ'=>50,'pel'=>38,'notas'=>'Reconstrucción tras la temporada de tormentas; los astilleros no dan abasto con los encargos.'),
        'calm-belt'  => array('est'=>50,'mar'=>20,'pir'=>18,'rev'=>5,'eco'=>22,'civ'=>30,'pel'=>88,'notas'=>'Como siempre, aguas mortales: los Reyes del Mar mantienen alejado a cualquiera con dos dedos de frente.'),
        'red-line'   => array('est'=>70,'mar'=>82,'pir'=>12,'rev'=>10,'eco'=>68,'civ'=>80,'pel'=>40,'notas'=>'Orden férreo bajo la sombra del Gobierno; peregrinos y mercancías cruzan sin sobresaltos.'),
        'paraiso'    => array('est'=>55,'mar'=>50,'pir'=>45,'rev'=>22,'eco'=>58,'civ'=>48,'pel'=>55,'notas'=>'El primer tramo de la Grand Line bulle de aventureros novatos; competencia sana entre tripulaciones.'),
        'new-world'  => array('est'=>45,'mar'=>46,'pir'=>55,'rev'=>30,'eco'=>52,'civ'=>40,'pel'=>70,'notas'=>'Frontera indómita: crece el pulso entre una flota pirata y la Marina por el control de una ruta clave.'),
    ),
    'facciones' => array(
        'marine'           => array('rep'=>45,'coh'=>74,'mil'=>86,'inf'=>80,'eco'=>74,'mor'=>68,'notas'=>'Mantiene el orden con presencia disuasoria; moral estable.'),
        'pirata'           => array('rep'=>-8,'coh'=>50,'mil'=>66,'inf'=>28,'eco'=>52,'mor'=>66,'notas'=>'Actividad habitual de contrabando y pequeñas fechorías; una flota ambiciosa asoma en el Nuevo Mundo.'),
        'revolucionario'   => array('rep'=>18,'coh'=>68,'mil'=>58,'inf'=>44,'eco'=>44,'mor'=>72,'notas'=>'Trabajo de base y propaganda; siembran ideas más que batallas.'),
        'gobierno'         => array('rep'=>12,'coh'=>72,'mil'=>82,'inf'=>90,'eco'=>84,'mor'=>66,'notas'=>'Gestiona el mundo con mano firme y burocracia; sin sobresaltos este mes.'),
        'cazarrecompensas' => array('rep'=>30,'coh'=>52,'mil'=>52,'inf'=>26,'eco'=>56,'mor'=>60,'notas'=>'Negocio estable: capturas menores y alguna pieza jugosa en el horizonte.'),
        'civil'            => array('rep'=>40,'coh'=>54,'mil'=>12,'inf'=>30,'eco'=>52,'mor'=>62,'notas'=>'La gente de a pie respira tranquila; ferias, mercados y botaduras animan los puertos.'),
    ),
    'tension' => array(
        'east-blue'  => array(
            'marine|pirata'           => array('valor'=>24,'notas'=>'Roces menores entre patrullas y contrabandistas; nada serio.'),
            'pirata|cazarrecompensas' => array('valor'=>20,'notas'=>'Algún cazador persigue recompensas modestas por la zona.'),
        ),
        'west-blue'  => array(
            'revolucionario|gobierno' => array('valor'=>34,'notas'=>'Fricción fría: panfletos y arrestos puntuales, sin choques abiertos.'),
            'marine|revolucionario'   => array('valor'=>28,'notas'=>'Vigilancia sobre reuniones sospechosas.'),
        ),
        'north-blue' => array(
            'marine|pirata'           => array('valor'=>22,'notas'=>'Contrabando de temporada; la Marina hace la vista gorda salvo excesos.'),
        ),
        'south-blue' => array(
            'marine|pirata'           => array('valor'=>30,'notas'=>'Rivalidad habitual por las rutas comerciales en reconstrucción.'),
            'pirata|civil'            => array('valor'=>22,'notas'=>'Quejas por pequeños hurtos en los muelles.'),
        ),
        'calm-belt'  => array(
            'marine|pirata'           => array('valor'=>12,'notas'=>'Nadie pelea aquí: los Reyes del Mar imponen su propia paz.'),
        ),
        'red-line'   => array(
            'revolucionario|gobierno' => array('valor'=>30,'notas'=>'Vigilancia estrecha; la revolución no se atreve a asomar por aquí.'),
        ),
        'paraiso'    => array(
            'marine|pirata'           => array('valor'=>40,'notas'=>'Competencia intensa pero deportiva entre tripulaciones novatas y patrullas.'),
            'pirata|gobierno'         => array('valor'=>32,'notas'=>'Algún capitán ambicioso desafía multas y aduanas.'),
        ),
        'new-world'  => array(
            'marine|pirata'           => array('valor'=>58,'notas'=>'CONFLICTO LOCALIZADO: una flota pirata y una base marine se disputan una ruta; escaramuzas, aún no guerra.'),
            'pirata|gobierno'         => array('valor'=>44,'notas'=>'El Gobierno sube las recompensas de la flota implicada.'),
            'revolucionario|gobierno' => array('valor'=>30,'notas'=>'Los revolucionarios observan el pulso desde la distancia.'),
        ),
    ),
    'arcos' => array(
        array('nombre'=>'El Pulso del Nuevo Mundo','estado'=>'Activo','zonas'=>'new-world','facciones'=>'marine,pirata','descripcion'=>'Una flota pirata ambiciosa y una base de la Marina se disputan el control de una ruta clave. De momento son escaramuzas; si nadie cede, podría escalar en los próximos meses.'),
        array('nombre'=>'Reconstrucción del South Blue','estado'=>'Latente','zonas'=>'south-blue','facciones'=>'civil,marine','descripcion'=>'Tras una dura temporada de tormentas, los puertos del sur se reconstruyen con ayuda de gremios y patrullas.'),
    ),
);
$estadoJson = json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// ── BLOQUE 2: periódico Eternal News (tono de paz + VARIEDAD de maquetación) ──
$periodico = <<<'HTML'
<section class="ope-per-lead">
  <h2>Un mes en calma: el mundo respira entre feria y marea</h2>
  <figure class="ope-per-figwide" data-img="portada"><figcaption>Puerto bullicioso al atardecer: mercantes descargando, gaviotas y el faro encendido.</figcaption></figure>
  <p>Corren tiempos apacibles, y en esta redacción sabemos que la calma también es noticia. De East Blue a Paraíso, los puertos rebosan de comercio, las tabernas de historias y los muelles de recién llegados con sueños demasiado grandes para sus barcas. Que dure.</p>
  <p>La Marina mantiene sus patrullas de rutina y el Gobierno, su eterna burocracia. Solo en la frontera del Nuevo Mundo se oye, lejano, el tamborileo de un pulso que conviene no perder de vista.</p>
</section>

<section class="ope-per-sec">
  <h3>Nuevo Mundo · Un pulso por la ruta del Fanal</h3>
  <div class="ope-per-cols">
    <p>En la frontera del mundo, una flota pirata de creciente reputación y una base de la Marina se disputan el control de una ruta comercial clave. Por ahora hablamos de <b>escaramuzas</b>: cañonazos de advertencia, abordajes fallidos y mucho pulso de miradas. Nadie ha declarado nada, pero nadie retrocede.</p>
    <p>Los observadores más veteranos recuerdan que así empiezan las grandes historias… y también las grandes tragedias. El Gobierno ha respondido, de momento, subiendo las recompensas de los capitanes implicados.</p>
  </div>
  <aside class="ope-per-aside"><h4>Se dice en los muelles</h4><p>Que la flota pirata busca menos saquear que asentarse. Que el oficial al mando de la base no piensa ceder ni un palmo de agua. Rumores, por ahora.</p></aside>
</section>

<section class="ope-per-sec">
  <h3>Reportaje · La vida vuelve al South Blue</h3>
  <div class="ope-per-longread">
    <p>Donde el mar rompió tejados y hundió pantalanes, hoy suenan martillos. La temporada de tormentas dejó al sur maltrecho, pero la reconstrucción avanza más rápido de lo que nadie esperaba, con gremios de carpinteros de ribera trabajando de sol a sol y patrullas prestando manos que no siempre empuñan fusiles.</p>
    <blockquote class="ope-per-pull">«El mar nos quitó el puerto; nosotros se lo devolvemos, tabla a tabla.»</blockquote>
    <p>No todo es idílico: los precios de la madera se han disparado y algún que otro listillo intenta aprovechar el desconcierto. Pero el ánimo, dicen los vecinos, hacía tiempo que no estaba tan alto.</p>
  </div>
</section>

<section class="ope-per-sec">
  <h3>Ecos del mar · Breves de un mundo tranquilo</h3>
  <div class="ope-per-cols">
    <p><b>Paraíso.</b> Récord de botaduras de tripulaciones novatas; las apuestas sobre quién llegará más lejos animan cada taberna.</p>
    <p><b>West Blue.</b> Circulan panfletos revolucionarios, pero la sangre no ha llegado al río: de momento, es guerra de palabras.</p>
    <p><b>North Blue.</b> Pesca abundante y algún contrabando de temporada que la Marina tolera mientras no haya excesos.</p>
    <p><b>Calm Belt.</b> Un pesquero temerario juró haber visto la cresta de un Rey del Mar. Volvió con la red vacía y el pelo blanco.</p>
  </div>
</section>

<section class="ope-per-sec">
  <h3>Clasificados de Eternal News</h3>
  <div class="ope-per-ads">
    <div class="ope-per-ad">
      <span class="ope-per-ad-tag">Negocio</span>
      <h4>Taberna El Ancla Torcida</h4>
      <p>Reabre en Loguetown tras las reformas. Ron del bueno, música en vivo y peleas gratis los viernes.</p>
    </div>
    <div class="ope-per-ad">
      <span class="ope-per-ad-tag">Se vende</span>
      <h4>Bergantín «Gaviota»</h4>
      <p>Casco sólido, velas nuevas, pocas balas incrustadas. Razón: capitán jubilado. Precio a negociar en el muelle 7.</p>
    </div>
    <div class="ope-per-ad">
      <span class="ope-per-ad-tag">Se busca tripulación</span>
      <h4>¡Zarpamos rumbo a Paraíso!</h4>
      <p>Cocinero, médico y timonel con agallas. Reparto justo del botín. Preguntad por el capitán del sombrero rojo.</p>
    </div>
    <div class="ope-per-ad ope-per-ad--wanted">
      <span class="ope-per-ad-tag">Se busca</span>
      <h4>«Dedos Rápidos» Molt</h4>
      <figure class="ope-per-fig" data-img="wanted-molt"><figcaption>Vivo o muerto</figcaption></figure>
      <p class="bounty">Recompensa: 3.000.000 de Berries</p>
    </div>
  </div>
</section>

<section class="ope-per-sec">
  <h3>Columna · «Elogio de la calma»</h3>
  <div class="ope-per-longread">
    <p>Hay quien confunde la paz con el aburrimiento. Craso error. La paz es el momento en que se reparan los barcos, se crían los hijos, se afilan los sueños. Es el respiro que el mar concede antes de la siguiente ola. Aprovechémoslo, porque en este mundo nuestro nunca dura tanto como quisiéramos.</p>
    <aside class="ope-per-aside"><h4>Firma</h4><p>— El Viejo Faro, cronista errante de <i>Eternal News</i>.</p></aside>
  </div>
</section>
HTML;

// ── BLOQUE 3: noticia de portada (tono tranquilo) ──
$noticia = "titulo: Un mes en calma: el mundo respira entre feria y marea\n"
    . "resumen: Comercio próspero y puertos animados; solo un pulso localizado en el Nuevo Mundo enciende alguna chispa.\n"
    . "cuerpo: <p>Corren tiempos apacibles. De East Blue a Paraíso, los puertos rebosan comercio y los muelles reciben a recién llegados con más sueños que berries. La Marina mantiene sus patrullas de rutina y la vida civil florece.</p><p>La única sombra está en la frontera del <b>Nuevo Mundo</b>, donde una flota pirata y una base de la Marina se disputan una ruta clave. De momento son solo escaramuzas, pero conviene no perderlas de vista.</p><p>Consulta la edición completa de <b>Eternal News</b> para el parte de cada mar, los clasificados y la columna del mes.</p>";

// ── BLOQUE 4: prompts de imagen ──
$imagenes = "- id: portada | tamaño: 1200x675 | prompt: One Piece style illustration, a lively peaceful harbor at sunset, merchant ships unloading crates, seagulls, a lit lighthouse, warm golden light, no battle, wide cinematic shot\n"
    . "- id: wanted-molt | tamaño: 600x600 | prompt: a One Piece style wanted poster portrait of a sneaky thief pirate with a sly grin, aged paper look, bounty poster framing";

// ── Ensamblar el resultado tal como lo pegaría el staff ──
$raw = "===ESTADO_JSON===\n{$estadoJson}\n===FIN===\n"
    . "===PERIODICO_HTML===\n{$periodico}\n===FIN===\n"
    . "===NOTICIA===\n{$noticia}\n===FIN===\n"
    . "===IMAGENES===\n{$imagenes}\n===FIN===\n";

// Guardar el "input" (super-prompt) y el "output" (raw) a disco para inspección.
$outDir = dirname(__DIR__) . '/scripts';
$prompt = ope_rol_mv_build_prompt($ciclo);
@file_put_contents($outDir . '/_demo_mundo_vivo_INPUT.txt', $prompt);
@file_put_contents($outDir . '/_demo_mundo_vivo_OUTPUT.txt', $raw);

echo "Ciclo: {$ciclo['periodo']} (id {$ciclo_id})\n";
echo "Prompt (input) len=" . strlen($prompt) . " -> scripts/_demo_mundo_vivo_INPUT.txt\n";
echo "Resultado (output) len=" . strlen($raw) . " -> scripts/_demo_mundo_vivo_OUTPUT.txt\n";

// Limpieza de demo: quitar noticias previas de origen mundo_vivo para no duplicar en portada.
$db->delete_query('rol_mv_noticias', "origen = 'mundo_vivo'");

// ── Parsear y PUBLICAR por el pipeline real ──
$parsed = ope_rol_mv_parse_resultado($raw);
if (!empty($parsed['errores'])) {
    fwrite(STDERR, "Errores de parseo: " . implode(' | ', $parsed['errores']) . "\n");
    exit(1);
}
$res = ope_rol_mv_publicar($ciclo_id, $parsed, $raw);
if (empty($res['ok'])) {
    fwrite(STDERR, "Publicación falló: " . ($res['error'] ?? '?') . "\n");
    exit(1);
}
echo "PUBLICADO OK. estado-mundo.php, periodicos.php e index.php reflejan un mundo EN PAZ (con un foco localizado).\n";
