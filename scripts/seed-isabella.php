<?php
/**
 * One Piece: Eternal · Seed: Isabella D. Vega — La Reina Pirata
 * 
 * NPC Mayor completo siguiendo docs/guia-npcs-mayores-completa.md.
 * Si ya existe, actualiza; si no, inserta.
 * 
 * Uso: php scripts/seed-isabella.php
 */

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

require_once __DIR__ . '/_db-config.php';

// ── Verificar si ya existe ──
$check = $db->query("SELECT pid, es_npc FROM mybb_rol_personajes WHERE nombre = 'Isabella D. Vega' LIMIT 1");

if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    if ((int)$row['es_npc'] !== 1) {
        die("ERROR: Ya existe un PERSONAJE (no NPC) con ese nombre. Aborta.\n");
    }
    $pid = (int)$row['pid'];
    $is_new = false;
    echo "Isabella D. Vega ya existe (pid={$pid}, es_npc=1). Actualizando...\n";
} else {
    $is_new = true;
    echo "Creando Isabella D. Vega como NPC...\n";
}

// ── Stats ──
$stats_json = '{"FUE":83,"DES":62,"VIG":83,"AGI":73,"INT":72,"ING":42,"CON":52,"PER":73,"CAR":93,"CTR":32,"VOL":100,"SEN":85}';

// ── datos_publicos ──
$datos_publicos = json_encode([
    'titulo' => 'Reina de los Piratas · Ojos Carmesí · Portadora de la D.',
    'descripcion' => "Isabella D. Vega es la indiscutible Reina de los Piratas: la mujer que conquistó Grand Line entero, alcanzó la Última Isla y descubrió la verdad del Siglo Vacío, todo sin poseer una sola Fruta del Diablo. De estatura imponente (2.10 m) y figura atlética marcada por cicatrices de incontables batallas, su rasgo más distintivo son sus ojos carmesí — un rojo tan intenso que parecen brillar con luz propia cuando desata su Haki del Conquistador, capaz de doblegar la voluntad de ejércitos enteros. Viste un abrigo de capitán rasgado por décadas de tormentas y combates, pantalones oscuros de cuero reforzado y pesadas botas de hierro con las que ha caminado por cada isla del mundo. En su brazo derecho lleva enrollada su arma insignia: una cadena de Kairoseki que usa tanto como látigo devastador como para anular los poderes de usuarios de Frutas del Diablo.\n\nNacida en la pobreza más extrema bajo la tiranía de un reino afiliado al Gobierno Mundial, Isabella escapó al mar siendo apenas una niña, movida por un odio visceral hacia los nobles que habían destruido todo lo que amaba. Con nada más que su voluntad, formó los Piratas Carmesí y los llevó desde los Blues más humildes hasta el trono del Nuevo Mundo, ganándose aliados, rivales y enemigos en cada puerto. Su risa estridente — «¡Gahahahaha!» — se convirtió en sinónimo de libertad para millones de personas oprimidas y en una amenaza existencial para el Gobierno Mundial.\n\nTras alcanzar la Última Isla y descubrir la verdad del mundo, Isabella optó por no actuar de inmediato, buscando primero reunir fuerzas suficientes para derrocar a los Gorosei. Fue entonces cuando Balgor 'Titán de Chatarra' — su antiguo aliado y confidente — la traicionó vilmente, vendiendo sus coordenadas exactas a la Marina a cambio de cañones y armamento. Debilitada y emboscada, se enfrentó en un duelo legendario de espadas a la Almirante de Flota Valyria, la mejor espadachina del mundo. Tras un combate que duró tres días y devastó una isla entera, Valyria logró someterla.\n\nAhora espera su ejecución pública en Marineford, encadenada con Kairoseki en una celda de máxima seguridad. Pero incluso prisionera, Isabella no ha dejado de sonreír. Su captura ha desequilibrado la balanza del mundo: los cuatro Yonko mueven ficha, la Marina reúne todas sus fuerzas, y el Ejército Revolucionario ve una oportunidad sin precedentes. Faltan 30 días para la ejecución. El mundo contiene la respiración.",
    'personalidad_publica' => "Temeraria hasta la imprudencia, libre como el viento y con una risa estridente que resuena como un cañonazo — esa es la imagen que el mundo tiene de Isabella D. Vega. Desprecia abiertamente toda forma de autoridad, y reserva un odio especial para los Tenryubitos y el sistema que perpetúa la esclavitud. En combate es despiadada y no muestra piedad a quienes oprimen a los débiles, pero es conocida por perdonar la vida a enemigos que luchan con honor.\n\nPara su tripulación y para quienes la han conocido de cerca, Isabella es intensamente leal — una capitana que daría su vida sin dudarlo por cualquiera de los suyos. Esta lealtad ciega es también su mayor debilidad conocida: confió demasiado en Balgor, y esa confianza la llevó a la celda donde se encuentra ahora. A pesar de la traición, no se arrepiente de haber confiado — lo que le duele es no haber podido proteger a su tripulación de las consecuencias.\n\nHabla fuerte, con un tono autoritario pero cálido. Se burla constantemente de la seriedad de los marines y de la pomposidad de los nobles. Tiene la costumbre de reír a carcajadas en los momentos más inapropiados, lo que desconcierta tanto a aliados como a enemigos. Su frase más citada: «¡Gahahahaha! El mar no tiene dueño… ¡pero si lo tuviera, sería yo!»",
    'relaciones_publicas' => [
        ['nombre' => 'Almirante de Flota Valyria', 'vinculo' => 'Captora y némesis. La derrotó en un duelo de espadas de 3 días.', 'tipo' => 'enemiga'],
        ['nombre' => "Balgor 'Titán de Chatarra'", 'vinculo' => 'Ex-aliado que la traicionó vendiendo sus coordenadas a cambio de armamento. Antiguo nakama de los Piratas Carmesí, ahora Yonko independiente.', 'tipo' => 'enemiga'],
        ['nombre' => "Jack 'El Inmortal'", 'vinculo' => 'Vice-capitán de los Piratas Carmesí. El hombre más leal que ha conocido. Está movilizando lo que queda de la tripulación para rescatarla.', 'tipo' => 'leal'],
        ['nombre' => 'Dra. Aurelian Lira', 'vinculo' => 'Médica de los Piratas Carmesí. Exiliada de Mary Geoise (ex-Tenryubito). Una de las pocas personas que puede hacer callar a Isabella.', 'tipo' => 'leal'],
        ['nombre' => "Sekhmet 'Reina Leona'", 'vinculo' => 'Rival Yonko. Se respetan profundamente como guerreras. Su posición ante la ejecución es incierta — podría salvarla o quedarse mirando.', 'tipo' => 'compleja'],
        ['nombre' => "Shura 'Dios de la Ira'", 'vinculo' => 'Rival Yonko. La considera una amenaza a su dominio. Si Isabella muere, Shura gana territorio. Si vive, será la primera en intentar matarla de nuevo.', 'tipo' => 'hostil'],
        ['nombre' => "Ezekiel 'El Arcángel'", 'vinculo' => 'Rival Yonko. Enigmático. Nadie sabe si planea asistir a la ejecución como espectador, como salvador o como verdugo.', 'tipo' => 'compleja'],
    ],
    'recompensa' => '5.500.000.000 berries (congelada desde su captura)',
    'fruta' => null,
    'ubicacion_publica' => 'Marineford — Prisión de máxima seguridad',
    'ocupacion' => 'Prisionera condenada a ejecución pública (ex-Capitana de los Piratas Carmesí, ex-Reina de los Piratas)',
    'lema' => '¡Gahahahaha! El mar no tiene dueño… ¡pero si lo tuviera, sería yo!',
], JSON_UNESCAPED_UNICODE);

// ── datos_internos ──
$datos_internos = json_encode([
    'personalidad' => [
        'agr' => 55, 'val' => 100, 'hon' => 75,
        'lea' => 60, 'amb' => 70, 'int' => 65,
    ],
    'personalidad_detallada' => "Isabella tiene una agresividad moderada (55): es feroz en combate pero no busca conflicto gratuitamente. Encadenada, su agresividad se manifiesta como desafío verbal y provocación constante a los guardias, no como violencia física (que le es imposible en su estado actual). Su valentía es absoluta (100): nunca ha retrocedido ante nada en toda su vida. Enfrentó a Valyria sabiendo que perdería porque la alternativa — huir — era peor que la muerte para ella. Incluso ahora, condenada a la ejecución, no muestra ni rastro de miedo. Su honor es alto (75): tiene un código moral estricto que le prohíbe atacar a inocentes, que la empuja a proteger a los esclavos y oprimidos, y que le exige cumplir su palabra. Pero no es un caballero — es una pirata, y no dudaría en mentir o robar si es por una causa que considera justa. Su lealtad ha sido dañada (60): la traición de Balgor la ha herido profundamente. Antes de la traición, su lealtad habría sido 90. Ahora desconfía de alianzas nuevas y se cuestiona si fue ingenua al confiar tanto. Sin embargo, su lealtad hacia Jack, Aurelian y el resto de su tripulación original permanece inquebrantable. Su ambición es alta (70): no busca poder personal, sino cambiar el mundo. Quiere derrocar el sistema de los Gorosei y liberar a los oprimidos. Es una ambición idealista, no egoísta, pero no por ello menos intensa. Su inteligencia táctica es moderada-alta (65): es una estratega instintiva que lee personas y situaciones con rapidez, pero no es una planificadora metódica. Confía más en su instinto y en la fuerza bruta que en planes elaborados.",
    'metas' => [
        'Sobrevivir a la ejecución en Marineford: su prioridad absoluta es no morir. No tiene un plan de escape propio, pero confía en que alguien vendrá por ella — Jack, los restos de su tripulación, o incluso algún rival Yonko que la prefiera viva a muerta. Mientras tanto, observa, escucha y busca cualquier debilidad en el sistema de seguridad de Marineford que pueda explotar si surge la oportunidad.',
        'Derrocar a Imu y los Gorosei para liberar al mundo: este es su objetivo a largo plazo, el sueño por el que ha luchado toda su vida. Descubrió la verdad del Siglo Vacío en la Última Isla y sabe que mientras los Gorosei gobiernen, la humanidad vivirá encadenada. Este objetivo solo podrá perseguirse si sobrevive a la ejecución.',
        'Perdonarse a sí misma por haber permitido la caída de su tripulación: esta es una meta emocional, un arco interno. Isabella carga con la culpa de haber confiado ciegamente en Balgor, lo que llevó a la emboscada, a la captura, y a la dispersión de los Piratas Carmesí. Necesita reconciliarse con su pasado para poder mirar hacia adelante. Este proceso se desarrollará a través de conversaciones con prisioneros, recuerdos en su celda, y eventualmente el reencuentro con sus nakama.',
    ],
    'meta_actual' => 'Sobrevivir a la ejecución en Marineford',
    'tracking' => [
        'ubicacion_zona' => 'paraiso',
        'salud' => 35,
        'moral' => 60,
        'plan_activo' => 'Isabella está encadenada con Kairoseki en una celda de máxima seguridad de Marineford, custodiada por un escuadrón rotativo de marines de élite. No puede usar Haki ni fuerza sobrehumana mientras lleve las cadenas. Su actividad se limita a observar los patrones de guardia, memorizar rutinas de los carceleros, y comunicarse con los prisioneros de las celdas adyacentes a través de las paredes. Busca cualquier fragmento de información sobre lo que ocurre en el exterior: ¿los Yonko se mueven? ¿su tripulación sigue viva? ¿hay rumores de un rescate? Cada día que pasa la acerca a la ejecución, pero también le da más tiempo para que alguien actúe.',
        'thread_id' => null,
        'ultimo_ciclo' => 0,
    ],
    'notas_staff' => "Isabella D. Vega es el ancla narrativa de toda la Era 4 del foro — La Caída de la Reina. Su ejecución programada (o su rescate) es el evento central que define el arco inaugural. REGLAS ABSOLUTAS: (1) No matar a Isabella sin consenso UNÁNIME del equipo de staff completo. (2) No liberarla antes de que al menos 3 ciclos de juego hayan pasado — los jugadores necesitan tiempo para preparar sus personajes y vivir la tensión. (3) Si un PJ intenta infiltrarse en Marineford, Isabella puede interactuar con él desde su celda pero NO puede escapar sola — necesita ayuda exterior. (4) La moral de Isabella debe fluctuar de forma creíble: si el mundo se olvida de ella, baja; si hay rumores de rescate, sube. Si llega a 0, Isabella acepta su destino y pronuncia sus últimas palabras — esto debería ser un evento masivo.",
    'triggers_especiales' => [
        'Si un PJ pirata se infiltra en Marineford: Isabella puede comunicarse con él a través de las paredes de la celda, darle información sobre los turnos de guardia, o inspirarlo con su presencia. NO puede escapar sola.',
        'Si la moral cae a 0: Isabella acepta la muerte con dignidad. Pronuncia un discurso final que la IA debe redactar como un momento épico al nivel de las últimas palabras de Roger. Este es un evento de máxima prioridad que debe mencionarse en portada del periódico.',
        'Si un Yonko ataca Marineford: el caos podría debilitar las defensas de la prisión. Las cadenas de Kairoseki no se rompen solas, pero los guardias podrían descuidarse. Isabella aprovecha cualquier distracción.',
        "Si Jack 'El Inmortal' llega a Marineford: el reencuentro entre Isabella y su vice-capitán es un momento narrativo que debe tratarse con el peso emocional que merece. Ella no llorará — se reirá. «¡Gahahahaha! ¡Sabía que vendrías, idiota!»",
        'Si Balgor aparece en Marineford: la reacción de Isabella será de ira pura. Es el único escenario donde su moral sube Y su agresividad se dispara al 100%. Quiere matarlo con sus propias manos.',
    ],
], JSON_UNESCAPED_UNICODE);

// ── datos (legacy, para compatibilidad con ficha.php) ──
$datos_legacy = json_encode([
    'raza_principal' => 'humano',
    'hibrido' => false,
    'apodo' => 'Ojos Carmesí',
    'edad' => '38',
    'genero' => 'Femenino',
    'stats_efectivas' => json_decode($stats_json, true),
    'virtudes' => [
        'V-VOL-D' => ['nombre' => 'Voluntad de D.', 'coste' => 0, 'spec' => ''],
    ],
    'defectos' => [],
    'pc_gastado' => 0,
    'pc_devuelto' => 0,
    'pc_balance' => 6,
    'faccion' => 'pirata',
], JSON_UNESCAPED_UNICODE);

// ── bio (historia) ──
$bio = json_encode([
    'concepto' => 'La Reina de los Piratas. Una mujer que nació en la miseria mas absoluta bajo un reino títere del Gobierno Mundial, escapó al mar con nada mas que su voluntad, conquistó todos los mares sin poseer una sola Fruta del Diablo, formó la tripulación mas temida del mundo -- los Piratas Carmesí --, descubrió la verdad del Siglo Vacío en la Última Isla, y fue traicionada por Balgor, el hombre en quien mas confiaba. Ahora espera su ejecución pública en Marineford, encadenada con Kairoseki en una celda de maxima seguridad. Pero incluso prisionera, no ha dejado de sonreír. Su captura ha desequilibrado la balanza del mundo: los cuatro Yonko mueven ficha, la Marina reúne todas sus fuerzas, y el Ejército Revolucionario ve una oportunidad sin precedentes. Faltan 30 días para la ejecución. El mundo contiene la respiración.',
    'pasado' => "Isabella nació en la isla de Vega, un reino empobrecido del South Blue aplastado bajo el tributo a los Tenryubitos. Huérfana desde los 4 años -- sus padres fueron ejecutados por no pagar el Tributo Celestial -- creció en los muelles robando pescado, durmiendo entre barriles y soñando con un horizonte donde nadie tuviera que arrodillarse. A los 8 años, un viejo pirata retirado le enseñó a leer y a pelear. A los 12, se coló como polizón en un barco mercante. A los 14, ya era grumete en un navío pirata del South Blue. A los 16, su capitán murió en combate y ella tomó el mando. Nadie se lo discutió: a los 16 años ya partía cráneos con los puños y miraba a los hombres a los ojos sin pestañear.\n\nRebautizó el barco como 'Carmesí' por el color de sus ojos, y así nacieron los Piratas Carmesí. Durante 9 años barrieron el South Blue, liberando islas oprimidas y ganándose el odio eterno del Gobierno Mundial. A los 25, con una flota de 12 barcos y una tripulación de miles, entró en Grand Line. No fue bien recibida: la Marina envió tres Vicealmirantes tras ella. Los derrotó a los tres en una semana.\n\nDurante los siguientes 10 años, Isabella conquistó isla tras isla, forjó alianzas con reinos rebeldes, se ganó el respeto de los Yonko existentes y se convirtió en la mujer mas buscada del mundo. Descubrió que poseía Haoshoku Haki cuando, en medio de una emboscada de 5.000 marines, su voluntad se desbordó y dejó inconscientes a 4.500 de ellos. Los 500 restantes huyeron. La leyenda de sus ojos carmesí creció: se decía que quien los miraba directamente cuando brillaban, olvidaba quién era.\n\nA los 35 años, Isabella alcanzó la Última Isla. Lo que encontró allí -- la verdad del Siglo Vacío, el nombre del primer Rey del Mundo, el arma ancestral -- lo mantuvo en secreto. El mundo no estaba listo. Decidió regresar al Paraíso, reagrupar a sus aliados, y prepararse para derrocar el sistema desde sus cimientos. Pero antes de poder actuar, Balgor 'Titán de Chatarra', un aliado en quien confiaba ciegamente -- un hombre al que había considerado su hermano -- la traicionó. Vendió sus coordenadas exactas a la Marina a cambio de cañones, acero y el título de Yonko.\n\nLa Marina lanzó una Buster Call modificada con los tres Almirantes liderados por Valyria en persona. La emboscada fue absoluta. La tripulación de Isabella se dispersó, su flota fue destruida. Ella, sola, luchó durante tres días contra Valyria en un duelo de espadas que devastó una isla entera del Paraíso. Perdió. Pero incluso con los brazos encadenados en Kairoseki, incluso arrodillada ante toda la fuerza naval del mundo, Isabella no dejó de sonreír. 'El dia que dejemos de reír -- dijo -- ese dia habremos perdido de verdad.'",
    'motivacion' => "Isabella no busca poder, ni gloria, ni riquezas. Su sueño es simple y absoluto: un mundo libre. Libre de los Tenryubitos, libre de los Gorosei, libre de un sistema que permite que una niña de 4 años se quede huérfana porque sus padres no pudieron pagar un tributo a hombres que se creen dioses.\n\nDescubrió la verdad -- la encontró al final de todo, en la Última Isla, y supo que el mundo que conocían era una mentira construida durante 800 años. No puede des-aprender lo que vio. No puede ignorar lo que sabe. Y aunque esté encadenada, aunque queden 30 días para su ejecución, aunque la traición de Balgor le haya roto el corazón... su voluntad no se ha apagado. Porque el sueño de Isabella D. Vega no es suyo: es de todos los que alguna vez miraron al mar y desearon ser libres.\n\nSu captura no ha matado ese sueño. Lo ha convertido en una mecha encendida. Y cuando esa mecha llegue al barril de pólvora... el mundo entero va a arder.",
], JSON_UNESCAPED_UNICODE);

// ── Descripción física (columna independiente, visible en ficha) ──
$desc_fisica = "De estatura imponente (2.10 m) y figura atlética esculpida por décadas de combate naval, Isabella D. Vega impone respeto con solo entrar en una habitación -- incluso encadenada. Su cuerpo es un mapa de cicatrices: una larga linea diagonal cruza su espalda desde el hombro izquierdo hasta la cadera derecha (recuerdo de su primer abordaje a los 16 años), tres marcas paralelas de garras en el antebrazo derecho (un Rey del Mar en el Calm Belt), y una cicatriz de espada horizontal justo debajo de las costillas izquierdas (cortesía de Valyria en su ultimo duelo). Sus brazos, poderosos como vigas de hierro, están cubiertos de pequeñas quemaduras de pólvora y cortes superficiales que nunca se molestó en tratar.\n\nSu rostro es anguloso, de mandíbula fuerte y pómulos marcados, enmarcado por una melena negra azabache que cae en cascada hasta media espalda. Normalmente la lleva recogida en una coleta alta con un cordón de cuero gastado -- el unico recuerdo que conserva de su madre. Su piel, bronceada por el sol de cuatro mares, contrasta violentamente con el rojo carmesí de sus ojos: un rojo tan intenso, tan profundo, que quienes la han visto desatar su Haki del Conquistador juran que parecen brillar con luz propia, como dos brasas encendidas en la oscuridad.\n\nViste un abrigo largo de capitán color granate oscuro -- originalmente rojo brillante, desteñido por décadas de sol, salitre y sangre -- con el jolly roger de los Piratas Carmesí bordado en la espalda. Debajo, una camisa blanca de lino (antaño impecable, ahora permanentemente manchada) y pantalones negros de cuero reforzado. Calza botas de hierro que llegan hasta la rodilla, tan pesadas que un marine normal no podría caminar con ellas. Ella ha corrido, saltado y pateado con esas botas durante 20 años. En su brazo derecho lleva enrollada su arma insignia: una cadena de Kairoseki de 3 metros que usa tanto como látigo devastador como para anular Frutas del Diablo. Incluso ahora, en su celda de Marineford, los grilletes de Kairoseki que la aprisionan son idénticos a los eslabones de su propia cadena -- una ironía que la hace reír cada vez que la mira.";
$from_fisico = 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal';
$personalidad = "Temeraria hasta la inconsciencia, libre como el viento del Grand Line, y dueña de una risa estridente -- «¡Gahahahaha!» -- que resuena como un cañonazo incluso a través de los muros de Kairoseki de su celda. Esa es Isabella D. Vega en estado puro.\n\nDesprecia abiertamente toda forma de autoridad, pero reserva un odio especialmente visceral -- casi sagrado -- para los Tenryubitos y el sistema de esclavitud que perpetúan. Ha visto con sus propios ojos lo que los Nobles Mundiales hacen a las islas que no pueden pagar el Tributo Celestial. Su isla natal fue una de ellas. Nunca lo ha olvidado, y nunca lo perdonará.\n\nEn combate es despiadada. No concede cuartel a quienes oprimen a los débiles, y no muestra piedad a los enemigos que luchan sin honor. Sin embargo, es famosa por perdonar la vida a rivales dignos -- 'Hoy no es tu día', les dice, 'pero podria serlo mañana. Vuelve cuando seas mas fuerte.' Estas palabras han creado una red de respeto entre piratas que trasciende alianzas y facciones.\n\nPara su tripulación -- y para cualquiera que haya ganado su confianza -- Isabella es leal hasta la muerte. Daría su vida sin dudarlo por cualquiera de los suyos. Esta lealtad absoluta, que es su mayor fortaleza, es también su talón de Aquiles: confió ciegamente en Balgor durante 20 años, y esa confianza la llevó directamente a la celda donde se encuentra ahora. Y sin embargo... no se arrepiente. 'Prefiero que me traicionen por confiar -- dice -- a no confiar nunca por miedo.'\n\nEs ruidosa, desordenada y no tiene filtro. Se ríe en los momentos mas inapropiados, suelta verdades incómodas en medio de reuniones diplomáticas, y tiene la costumbre de llamar 'idiota' a la gente que le importa como muestra de afecto. Su tono es autoritario pero cálido, como un trueno lejano que anuncia tormenta pero también lluvia para los campos secos.\n\nTiene un profundo sentido de la responsabilidad hacia los oprimidos -- no por ideología, sino por instinto. Ha estado en el fondo. Sabe lo que es no tener nada. Y ahora que lo tiene todo (incluso perdiéndolo), siente que su vida pertenece a quienes aún no han podido escapar.";

// ── INSERT o UPDATE ──
$db->begin_transaction();

try {
    if ($is_new) {
        $slug = 'isabella-d-vega';
        $query = "INSERT INTO mybb_rol_personajes 
            (uid, nombre, slug, estado, es_npc, activo, nivel, avatar,
             stats_json, ps_gastados, stats_ganados,
             datos, datos_publicos, datos_internos,
             desc_fisica, from_fisico, personalidad,
             inventario, economia, bio,
             mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np,
             dateline, lastedit)
            VALUES (0, ?, ?, 'aprobado', 1, 0, 85, '',
                    ?, 790, 790,
                    ?, ?, ?,
                    ?, ?, ?,
                    '{}', '{\"berries\":50000}', ?,
                    'paraiso', 'Marineford — Celda de maxima seguridad',
                    'Encadenada con Kairoseki, observando los patrones de guardia, comunicandose con prisioneros adyacentes. Esperando su ejecucion.',
                    'Capturada',
                    UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            'ssssssssssss',
            $nombre, $slug, $stats_json,
            $datos_legacy, $datos_publicos, $datos_internos,
            $desc_fisica, $from_fisico, $personalidad,
            $bio
        );
        $nombre = 'Isabella D. Vega';
        $stmt->execute();
        $pid = $db->insert_id;
        $stmt->close();

        echo "INSERT OK. pid={$pid}\n";
    } else {
        $query = "UPDATE mybb_rol_personajes SET
            stats_json = ?, ps_gastados = 790, stats_ganados = 790, nivel = 85,
            datos = ?, datos_publicos = ?, datos_internos = ?,
            desc_fisica = ?, from_fisico = ?, personalidad = ?,
            bio = ?,
            mundo_zona = 'paraiso',
            mundo_ubic = 'Marineford — Celda de maxima seguridad',
            mundo_accion = 'Encadenada con Kairoseki, observando los patrones de guardia, comunicandose con prisioneros adyacentes. Esperando su ejecucion.',
            mundo_estado_np = 'Capturada',
            lastedit = UNIX_TIMESTAMP()
            WHERE pid = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param('ssssssssi', $stats_json, $datos_legacy, $datos_publicos, $datos_internos, $desc_fisica, $from_fisico, $personalidad, $bio, $pid);
        $stmt->execute();
        $stmt->close();

        echo "UPDATE OK. pid={$pid}\n";
    }

    $db->commit();

    // ── Verificación ──
    $v = $db->query("SELECT 
        pid, nombre, nivel,
        IF(datos_publicos IS NOT NULL AND datos_publicos != '' AND datos_publicos != 'null', 'SI', 'NO') AS pub,
        IF(datos_internos IS NOT NULL AND datos_internos != '' AND datos_internos != 'null', 'SI', 'NO') AS inter,
        IF(mundo_zona != '', 'SI', 'NO') AS zona,
        IF(mundo_accion != '', 'SI', 'NO') AS accion,
        IF(mundo_estado_np != '', 'SI', 'NO') AS estado,
        IF(desc_fisica IS NOT NULL AND desc_fisica != '', 'SI', 'NO') AS fisica,
        IF(personalidad IS NOT NULL AND personalidad != '', 'SI', 'NO') AS perso,
        IF(bio IS NOT NULL AND bio != '' AND bio != 'null', 'SI', 'NO') AS bio_ok,
        CHAR_LENGTH(datos_publicos) AS pub_chars,
        CHAR_LENGTH(datos_internos) AS inter_chars,
        CHAR_LENGTH(desc_fisica) AS fisica_chars,
        CHAR_LENGTH(personalidad) AS perso_chars,
        CHAR_LENGTH(bio) AS bio_chars
        FROM mybb_rol_personajes WHERE pid = {$pid}");

    $row = $v->fetch_assoc();
    echo "\n=== VERIFICACION ===\n";
    echo "Nombre:      {$row['nombre']}\n";
    echo "Nivel:       {$row['nivel']}\n";
    echo "Pub:         {$row['pub']} ({$row['pub_chars']} chars)\n";
    echo "Interno:     {$row['inter']} ({$row['inter_chars']} chars)\n";
    echo "DescFisica:  {$row['fisica']} ({$row['fisica_chars']} chars)\n";
    echo "Personalidad:{$row['perso']} ({$row['perso_chars']} chars)\n";
    echo "Bio:         {$row['bio_ok']} ({$row['bio_chars']} chars)\n";
    echo "Zona:        {$row['zona']}\n";
    echo "Accion:      {$row['accion']}\n";
    echo "Estado:      {$row['estado']}\n";

    $warnings = [];
    if ($row['pub_chars'] < 500) $warnings[] = 'ADVERTENCIA: datos_publicos muy corto (<500 chars)';
    if ($row['inter_chars'] < 300) $warnings[] = 'ADVERTENCIA: datos_internos muy corto (<300 chars)';
    if ($row['fisica_chars'] < 200) $warnings[] = 'ADVERTENCIA: desc_fisica muy corta (<200 chars)';
    if ($row['perso_chars'] < 200) $warnings[] = 'ADVERTENCIA: personalidad muy corta (<200 chars)';
    if ($row['bio_chars'] < 500) $warnings[] = 'ADVERTENCIA: bio muy corto (<500 chars)';
    if ($row['pub'] === 'NO') $warnings[] = 'ERROR: datos_publicos vacio';
    if ($row['inter'] === 'NO') $warnings[] = 'ERROR: datos_internos vacio';
    if ($row['fisica'] === 'NO') $warnings[] = 'ERROR: desc_fisica vacia';
    if ($row['perso'] === 'NO') $warnings[] = 'ERROR: personalidad vacia';

    if ($warnings) {
        echo "\n" . implode("\n", $warnings) . "\n";
    } else {
        echo "\nTODO CORRECTO. NPC Mayor 100% funcional.\n";
    }

} catch (Exception $e) {
    $db->rollback();
    die("ERROR: " . $e->getMessage() . "\n");
}
