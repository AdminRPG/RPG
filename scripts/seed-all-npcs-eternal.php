<?php
/**
 * One Piece: Eternal · Master Seed de NPCs Mayores
 * Puebla y equipa a todos los NPCs con descripciones físicas, psicológicas e históricas
 * profundamente detalladas y ambientadas (3-5 párrafos por sección).
 */

define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== INICIANDO SEMBRADO MASTER DE NPCS CON LORE EXPANDIDO ===\n\n";

$ALL_NPCS = array();

// ── 1. Sigrun D. Basterra (Almirante de Flota) ──
$ALL_NPCS[] = array(
    'slug' => 'almirante-flota-sigrun-basterra', 'nombre' => 'Sigrun D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Almirante de Flota', 'faccion_slug' => 'marines', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Veterana Buccaneer del Cuartel General',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford — Cuartel General de la Marina',
    'mundo_accion' => 'Prepara el dispositivo de seguridad para la ejecución pública de su hijo, el Rey Pirata.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 2275, 'en_max' => 1460, 'pa' => 14, 'ps' => 536, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>99,'RES'=>98,'AGI'=>45,'INT'=>30,'PER'=>70,'TEM'=>78,'VOL'=>88,'CAR'=>60),
    'desc_fisica' => "Buccaneer de 2,40 metros de estatura, con hombros anchos como laderas de montaña y puños del tamaño de yunques de herrería. Su cabellera cana y plateada está recogida en trenzas de combate que caen sobre sus hombros, enmarcando un rostro sobrio surcado por cicatrices profundas acumuladas tras cuatro décadas en la vanguardia del mar. Viste el imponente uniforme blanco de Almirante de Flota con charreteras doradas y una gran capa militar que lleva grabado a la espalda el kanji de «Justicia» con letras bañadas en pan de oro.\n\nEn combate, la piel de sus nudillos y antebrazos se torna de un negro azabache espejado por la concentración de Haki de Armadura avanzado, mientras el aura gravitatoria de la Zushi Zushi no Mi distorsiona el aire a su alrededor, agrietando el suelo de granito y aumentando el peso de la atmósfera hasta aplastar a los enemigos antes de que puedan desenfundar.",
    'personalidad' => "Inamovible como un bastión de granito en medio del océano. Sigrun representa el vértice supremo de la Justicia Heroica y el deber militar incondicional. Es una mujer justa y severa, incapaz de doblegar la ley por favoritismos o intereses políticos, pero dotada de un respeto humano profundo hacia sus subordinados y rivales de honor.\n\nEs la madre biológica de Rolf D. Basterra, el Rey de los Piratas capturado. Jamás ha renegado de su hijo ni de la sangre de la D. que corre por sus venas, pero entiende la ejecución pública en Marineford como su prueba de fe definitiva ante el mundo. No gobierna a la Marina con gritos ni tiranía, sino con la autoridad serena y la aplastante certeza de que su puño protege la frágil estabilidad del mar.",
    'bio' => array(
        'pb' => 'Veterana Buccaneer del Cuartel General',
        'edad' => '58 años',
        'genero' => 'Femenino',
        'apodo' => 'El Puño de la Marina',
        'desc_fisica' => "Buccaneer de 2,40 metros de estatura, con hombros anchos como laderas de montaña y puños del tamaño de yunques de herrería. Su cabellera cana y plateada está recogida en trenzas de combate que caen sobre sus hombros, enmarcando un rostro sobrio surcado por cicatrices profundas acumuladas tras cuatro décadas en la vanguardia del mar. Viste el imponente uniforme blanco de Almirante de Flota con charreteras doradas y una gran capa militar que lleva grabado a la espalda el kanji de «Justicia» con letras bañadas en pan de oro.\n\nEn combate, la piel de sus nudillos y antebrazos se torna de un negro azabache espejado por la concentración de Haki de Armadura avanzado, mientras el aura gravitatoria de la Zushi Zushi no Mi distorsiona el aire a su alrededor, agrietando el suelo de granito y aumentando el peso de la atmósfera hasta aplastar a los enemigos antes de que puedan desenfundar.",
        'desc_psicologica' => "Inamovible como un bastión de granito en medio del océano. Sigrun representa el vértice supremo de la Justicia Heroica y el deber militar incondicional. Es una mujer justa y severa, incapaz de doblegar la ley por favoritismos o intereses políticos, pero dotada de un respeto humano profundo hacia sus subordinados y rivales de honor.\n\nEs la madre biológica de Rolf D. Basterra, el Rey de los Piratas capturado. Jamás ha renegado de su hijo ni de la sangre de la D. que corre por sus venas, pero entiende la ejecución pública en Marineford como su prueba de fe definitiva ante el mundo. No gobierna a la Marina con gritos ni tiranía, sino con la autoridad serena y la aplastante certeza de que su puño protege la frágil estabilidad del mar.",
        'pasado' => "Nacida en un asentamiento Buccaneer oculto en los mares del Sur, Sigrun ingresó en la Marina como recluta tras demostrar una fuerza física sobrehumana y un código moral inquebrantable. A lo largo de tres décadas, ascendió desde marinero de tercera hasta Almirante, liderando batallas campales contra los mayores piratas del Nuevo Mundo.\n\nDurante su juventud concibió a Rolf D. Basterra, quien contra todos sus deseos eligió el camino de la piratería hasta alcanzar La Última Isla y ser proclamado Rey de los Piratas. Tras ser capturado en circunstancias misteriosas, Sigrun fue nombrada Almirante de Flota por el Gobierno Mundial con la misión imperativa de presidir la ejecución de su propio hijo.",
        'historia' => "Nacida en un asentamiento Buccaneer oculto en los mares del Sur, Sigrun ingresó en la Marina como recluta tras demostrar una fuerza física sobrehumana y un código moral inquebrantable. A lo largo de tres décadas, ascendió desde marinero de tercera hasta Almirante, liderando batallas campales contra los mayores piratas del Nuevo Mundo.\n\nDurante su juventud concibió a Rolf D. Basterra, quien contra todos sus deseos eligió el camino de la piratería hasta alcanzar La Última Isla y ser proclamado Rey de los Piratas. Tras ser capturado en circunstancias misteriosas, Sigrun fue nombrada Almirante de Flota por el Gobierno Mundial con la misión imperativa de presidir la ejecución de su propio hijo."
    ),
    'datos' => array(
        'raza_principal'=>'buccaneers','hibrido'=>false,'apodo'=>'El Puño de la Marina','edad'=>'58','genero'=>'Femenino',
        'faccion'=>'marines','arquetipo'=>'La Justicia Heroica',
        'identidad'=>'coloso','arbol_identidad'=>'identidad-coloso','arbol_arma'=>'arma-cuerpo','arma'=>'punio_hierro',
        'arbol_identidad_nodos_ids'=>array('coloso-peso-t1', 'coloso-peso-t2', 'coloso-peso-t3', 'coloso-peso-t4', 'coloso-pinaculo-peso'),
        'arbol_arma_nodos_ids'=>array('cuerpo-impacto-marcial-t1', 'cuerpo-impacto-marcial-t2', 'cuerpo-impacto-marcial-t3', 'cuerpo-impacto-marcial-t4', 'cuerpo-pinaculo-impacto-marcial'),
        'haki'=>array('armadura'=>'avanzado (Pot 23)','observacion'=>'alto (Pot 19)','conquistador'=>'rey (Pot 18)'),
        'fruta_id'=>34,'fruta_slug'=>'fruta.zushi_zushi','fruta_nombre'=>'Zushi Zushi no Mi','fruta_sec'=>'TEM',
        'factor_linaje'=>array(
            'buccaneers' => array('nombre' => 'Voluntad Buccaneer', 'spec' => 'Fuerza y resistencia colosal (+6 RES, -2 CAR)', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Zushi Zushi no Mi (Gravedad)', 'spec' => 'Paramecia Tier V. Aplasta con gravedad, invierte superficies, atrae meteoros.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'coloso'     => array('nombre' => 'Coloso — Peso Absoluto', 'spec' => 'Acumula Mole y remata con daño multiplicado sin tope.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cuerpo'     => array('nombre' => 'Puño de Hierro — Puño de Dios', 'spec' => 'Golpe concentrado que penetra toda defensa.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'hao'        => array('nombre' => 'Haki del Conquistador (Rey)', 'spec' => 'Dobla la voluntad de ejércitos enteros.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>99,'RES'=>98,'AGI'=>45,'INT'=>30,'PER'=>70,'TEM'=>78,'VOL'=>88,'CAR'=>60),
        'concepto'=>'Almirante de Flota Buccaneer, el puño inamovible del deber. Madre del Rey Pirata capturado.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Almirante de Flota Sigrun D. Basterra — «El Puño de la Marina»',
        'descripcion'=>'La máxima autoridad militar del mundo. Su puño imbuido en Haki y multiplicado por la Zushi Zushi rompe islas y aplasta flotas. Madre del Rey Pirata al que debe ejecutar.',
        'personalidad_publica'=>'Inamovible, justa, temida y respetada por igual.',
        'relaciones_publicas'=>array(array('nombre'=>'Rolf D. Basterra','vinculo'=>'Su hijo, el Rey Pirata capturado. Debe presidir su ejecución.','tipo'=>'compleja')),
        'recompensa'=>'No aplica (Marina)','fruta'=>'Zushi Zushi no Mi',
        'ubicacion_publica'=>'Marineford','ocupacion'=>'Almirante de Flota','lema'=>'La justicia se sostiene con el puño, no con la excusa.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'Busca en secreto una tercera vía que no pase por matar a su hijo.','objetivos_ocultos'=>array(),'conexiones_clave'=>array('Rolf D. Basterra')),
);

// ── 2. Rolf D. Basterra (Rey de los Piratas) ──
$ALL_NPCS[] = array(
    'slug' => 'rey-pirata-rolf-basterra', 'nombre' => 'Rolf D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Rey de los Piratas', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Espadachín de Leyenda',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Impel Down — Nivel 6 (traslado a Marineford)',
    'mundo_accion' => 'Encadenado en kairoseki, aguarda su ejecución pública con una sonrisa.',
    'mundo_estado_np' => 'Capturado', 'isla_actual' => 'impel_down',
    'pv_max' => 1910, 'en_max' => 1405, 'pa' => 22, 'ps' => 608, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>82,'RES'=>70,'AGI'=>95,'INT'=>55,'PER'=>90,'TEM'=>60,'VOL'=>96,'CAR'=>88),
    'desc_fisica' => "Humano de porte regio de 1,92 metros de estatura, con una presencia electromagnética que impone respeto incluso vistiendo harapos de prisionero del Nivel 6 de Impel Down. Posee una cabellera negra alborotada, barba corta y unos ojos afilados llenos de burla y vitalidad indomable. Su torso está repleto de cicatrices trazadas por las espadas y proyectiles de los mejores luchadores del mar.\n\nIncluso encadenado con gruesos grilletes de Kairoseki pesado en pies y manos, camina erguido sin inclinar jamás la cabeza. Carece por completo de Akuma no Mi; su cuerpo es el receptáculo del Haki de Conquistador de Rey más potente y puro de la era moderna.",
    'personalidad' => "Indomable, temerario y libre hasta el tuétano. Rolf es la encarnación viva del espíritu libertario del océano y la Voluntad de la D. Sabe que su ejecución detonará la mayor era de piratería que el mundo haya visto jamás, y esa certeza le causa una divertida satisfacción festiva.\n\nSiente un afecto inquebrantable y sincero por su madre, la Almirante de Flota Sigrun D. Basterra, respetando el choque trágico al que el destino los ha arrastrado. Jamás ha suplicado clemencia ni ha mostrado arrepentimiento por haber conquistado La Última Isla.",
    'bio' => array(
        'pb' => 'Espadachín de Leyenda',
        'edad' => '28 años',
        'genero' => 'Masculino',
        'apodo' => 'El Rey Libre',
        'desc_fisica' => "Humano de porte regio de 1,92 metros de estatura, con una presencia electromagnética que impone respeto incluso vistiendo harapos de prisionero del Nivel 6 de Impel Down. Posee una cabellera negra alborotada, barba corta y unos ojos afilados llenos de burla y vitalidad indomable. Su torso está repleto de cicatrices trazadas por las espadas y proyectiles de los mejores luchadores del mar.\n\nIncluso encadenado con gruesos grilletes de Kairoseki pesado en pies y manos, camina erguido sin inclinar jamás la cabeza. Carece por completo de Akuma no Mi; su cuerpo es el receptáculo del Haki de Conquistador de Rey más potente y puro de la era moderna.",
        'desc_psicologica' => "Indomable, temerario y libre hasta el tuétano. Rolf es la encarnación viva del espíritu libertario del océano y la Voluntad de la D. Sabe que su ejecución detonará la mayor era de piratería que el mundo haya visto jamás, y esa certeza le causa una divertida satisfacción festiva.\n\nSiente un afecto inquebrantable y sincero por su madre, la Almirante de Flota Sigrun D. Basterra, respetando el choque trágico al que el destino los ha arrastrado. Jamás ha suplicado clemencia ni ha mostrado arrepentimiento por haber conquistado La Última Isla.",
        'pasado' => "Hijo de la Marine Sigrun D. Basterra, Rolf desafió el destino trazado para él y zarpó al mar a los 16 años. Reunió una tripulación legendaria, cruzó el Calm Belt, superó los peligros del Nuevo Mundo y alcanzó La Última Isla cinco años atrás, descubriendo los secretos del Siglo Vacío.\n\nTras ser coronado Rey de los Piratas por el mar entero, se entregó voluntariamente bajo condiciones que nadie conoce, detonando la crisis política definitiva que enfrenta a la Marina, los Yonkou y el Gobierno Mundial.",
        'historia' => "Hijo de la Marine Sigrun D. Basterra, Rolf desafió el destino trazado para él y zarpó al mar a los 16 años. Reunió una tripulación legendaria, cruzó el Calm Belt, superó los peligros del Nuevo Mundo y alcanzó La Última Isla cinco años atrás, descubriendo los secretos del Siglo Vacío.\n\nTras ser coronado Rey de los Piratas por el mar entero, se entregó voluntariamente bajo condiciones que nadie conoce, detonando la crisis política definitiva que enfrenta a la Marina, los Yonkou y el Gobierno Mundial."
    ),
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Rey Libre','edad'=>'28','genero'=>'Masculino',
        'faccion'=>'piratas','arquetipo'=>'La Libertad Absoluta',
        'identidad'=>'duelista','arbol_identidad'=>'identidad-duelista','arbol_arma'=>'arma-filo','arma'=>'espada',
        'arbol_identidad_nodos_ids'=>array('duelista-precision-t1', 'duelista-precision-t2', 'duelista-precision-t3', 'duelista-precision-t4', 'duelista-pinaculo-precision'),
        'arbol_arma_nodos_ids'=>array('filo-apertura-t1', 'filo-apertura-t2', 'filo-apertura-t3', 'filo-apertura-t4', 'filo-pinaculo-apertura'),
        'haki'=>array('armadura'=>'avanzado (Pot 20)','observacion'=>'presciencia (Pot 23)','conquistador'=>'rey (Pot 23)'),
        'fruta_id'=>null,'fruta_slug'=>null,'fruta_nombre'=>null,
        'factor_linaje'=>array(
            'humanos'    => array('nombre' => 'Adaptabilidad Humana', 'spec' => 'Improvisar y resistir ante la adversidad.', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'haki_puro'  => array('nombre' => 'Haki Puro (Sin Fruta)', 'spec' => 'Conquistó el Grand Line sin Akuma no Mi. Presciencia y Hao de rey.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'duelista'   => array('nombre' => 'Duelista — Punto Mortal', 'spec' => 'Cortes que ignoran la mitigación física; no se esquivan ni bloquean.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'filo'       => array('nombre' => 'Filo — Mil Cortes', 'spec' => 'Sangrado imparable que se transfiere al ejecutar.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>82,'RES'=>70,'AGI'=>95,'INT'=>55,'PER'=>90,'TEM'=>60,'VOL'=>96,'CAR'=>88),
        'concepto'=>'Rey de los Piratas capturado. Espadachín de Haki puro. Hijo de la Almirante de Flota.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Rolf D. Basterra — «El Rey Libre», Rey de los Piratas',
        'descripcion'=>'El hombre que conquistó el Grand Line con voluntad y filo sin comer jamás una fruta. Capturado, aguarda su ejecución pública.',
        'personalidad_publica'=>'Libre, carismático, imposible de doblegar.',
        'relaciones_publicas'=>array(array('nombre'=>'Sigrun D. Basterra','vinculo'=>'Su madre, la Almirante de Flota que debe ejecutarlo.','tipo'=>'compleja')),
        'recompensa'=>'La más alta de la historia','fruta'=>null,
        'ubicacion_publica'=>'Impel Down / Marineford','ocupacion'=>'Rey de los Piratas (capturado)','lema'=>'Un rey no pide permiso para ser libre.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'Sabe algo de La Última Isla que no ha revelado a nadie.','objetivos_ocultos'=>array(),'conexiones_clave'=>array('Sigrun D. Basterra')),
);

// ── 15. Alto Inquisidor Vaelen (Gobierno Mundial) ──
$ALL_NPCS[] = array(
    'slug' => 'alto-inquisidor-vaelen', 'nombre' => 'Vaelen',
    'rango' => 'M+', 'rango_faccion' => 'Comisionado Supremo', 'faccion_slug' => 'gobierno-mundial', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Aristócrata de Mary Geoise',
    'mundo_zona' => 'red_line', 'mundo_ubic' => 'Mary Geoise — Tribunal del Dragón',
    'mundo_accion' => 'Supervisa la respuesta del Gobierno ante la amenaza de los Yonkou.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'mary_geoise',
    'pv_max' => 2050, 'en_max' => 1600, 'pa' => 18, 'ps' => 560, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>70,'RES'=>80,'AGI'=>85,'INT'=>98,'PER'=>90,'TEM'=>80,'VOL'=>90,'CAR'=>85),
    'desc_fisica' => "Humano de 1,95 metros de estatura con un porte aristocrático impecable y regio. Sus vestiduras ceremoniales están confeccionadas en seda blanca ártica bordada con hilo de oro puro que forma los emblemas sagrados del Gobierno Mundial y Mary Geoise. Cubre la mitad superior de su rostro con una máscara inquisitorial tallada en porcelana dorada que oculta sus ojos por completo, dejando al descubierto tan solo su barbilla pulcra y unos labios delgados que jamás esbozan una sonrisa.\n\nEn combate, la serenidad de su postura contrasta con la brutalidad estética de su Akuma no Mi. Al canalizar la Ito Ito no Mi, de sus yemas brotan hebras de hilo invisibles pero reflectantes al sol como filamentos de titanio incandescente. Estos hilos se extienden a cientos de metros, enredándose en el aire y en las estructuras de los barcos hasta crear una tela de araña mortal donde cada movimiento de sus dedos controla la postura o cercena las extremidades de sus oponentes.",
    'personalidad' => "Frío, calculador y desprovisto de toda vacilación emocional. Vaelen representa la personificación de la Autoridad Inquisitorial de la Santa Tierra de Mary Geoise: para él, la humanidad y los reinos del mar no son más que piezas de ajedrez en un gran tablero de estabilidad global que debe ser protegido a cualquier precio.\n\nNo siente odio personal hacia los piratas ni compasión hacia los civiles sacrificados; su mente funciona con la lógica matemática y fría de los decretos supremos. Entiende la justicia no como una virtud moral, sino como una estructura de sumisión absoluta donde el caos debe ser erradicado de raíz antes de que germine. Cuando pronuncia una sentencia, lo hace con la voz pausada y solemne de quien se sabe respaldado por el peso incontestado de ocho siglos de hegemonía mundial.",
    'bio' => array(
        'pb' => 'Aristócrata de Mary Geoise',
        'edad' => '52 años',
        'genero' => 'Masculino',
        'apodo' => 'El Inquisidor Dorado',
        'desc_fisica' => "Humano de 1,95 metros de estatura con un porte aristocrático impecable y regio. Sus vestiduras ceremoniales están confeccionadas en seda blanca ártica bordada con hilo de oro puro que forma los emblemas sagrados del Gobierno Mundial y Mary Geoise. Cubre la mitad superior de su rostro con una máscara inquisitorial tallada en porcelana dorada que oculta sus ojos por completo, dejando al descubierto tan solo su barbilla pulcra y unos labios delgados que jamás esbozan una sonrisa.\n\nEn combate, la serenidad de su postura contrasta con la brutalidad estética de su Akuma no Mi. Al canalizar la Ito Ito no Mi, de sus yemas brotan hebras de hilo invisibles pero reflectantes al sol como filamentos de titanio incandescente. Estos hilos se extienden a cientos de metros, enredándose en el aire y en las estructuras de los barcos hasta crear una tela de araña mortal donde cada movimiento de sus dedos controla la postura o cercena las extremidades de sus oponentes.",
        'desc_psicologica' => "Frío, calculador y desprovisto de toda vacilación emocional. Vaelen representa la personificación de la Autoridad Inquisitorial de la Santa Tierra de Mary Geoise: para él, la humanidad y los reinos del mar no son más que piezas de ajedrez en un gran tablero de estabilidad global que debe ser protegido a cualquier precio.\n\nNo siente odio personal hacia los piratas ni compasión hacia los civiles sacrificados; su mente funciona con la lógica matemática y fría de los decretos supremos. Entiende la justicia no como una virtud moral, sino como una estructura de sumisión absoluta donde el caos debe ser erradicado de raíz antes de que germine. Cuando pronuncia una sentencia, lo hace con la voz pausada y solemne de quien se sabe respaldado por el peso incontestado de ocho siglos de hegemonía mundial.",
        'pasado' => "Nacido en el seno de una de las casas aristocráticas más influyentes vinculadas a la administración central de Mary Geoise, Vaelen fue educado desde su juventud en la doctrina del Gobierno Mundial y el dominio táctico de la ley inquisitorial. Tras consumir la Ito Ito no Mi y destacar por su frialdad táctica, ascendió rápidamente a través de las ramas más oscuras de la inquisición judicial y los servicios de inteligencia del Tribunal del Dragón.\n\nA lo largo de tres décadas, dirigió purgas silenciosas en reinos conspiradores del Paraíso, desarticuló células revolucionarias embrionarias y coordinó las órdenes ejecutivas de los Cipher Pol contra piratas del Nuevo Mundo. Su nombramiento como Comisionado Supremo se produjo tras sofocar con éxito el levantamiento de la Isla de Ostraka, donde tendió una red de hilos que bloqueó la bahía entera y sometió a la flota rebelde sin permitir una sola huida. En la actualidad, preside el comité extraordinario de seguridad tras el arresto de Rolf D. Basterra y los movimientos amenazantes de los cuatro Yonkou.",
        'historia' => "Nacido en el seno de una de las casas aristocráticas más influyentes vinculadas a la administración central de Mary Geoise, Vaelen fue educado desde su juventud en la doctrina del Gobierno Mundial y el dominio táctico de la ley inquisitorial. Tras consumir la Ito Ito no Mi y destacar por su frialdad táctica, ascendió rápidamente a través de las ramas más oscuras de la inquisición judicial y los servicios de inteligencia del Tribunal del Dragón.\n\nA lo largo de tres décadas, dirigió purgas silenciosas en reinos conspiradores del Paraíso, desarticuló células revolucionarias embrionarias y coordinó las órdenes ejecutivas de los Cipher Pol contra piratas del Nuevo Mundo. Su nombramiento como Comisionado Supremo se produjo tras sofocar con éxito el levantamiento de la Isla de Ostraka, donde tendió una red de hilos que bloqueó la bahía entera y sometió a la flota rebelde sin permitir una sola huida. En la actualidad, preside el comité extraordinario de seguridad tras el arresto de Rolf D. Basterra y los movimientos amenazantes de los cuatro Yonkou."
    ),
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Inquisidor Dorado','edad'=>'52','genero'=>'Masculino',
        'faccion'=>'gobierno-mundial','arquetipo'=>'La Autoridad Suprema',
        'identidad'=>'centinela','arbol_identidad'=>'identidad-centinela','arbol_arma'=>'arma-alcance','arma'=>'lanza',
        'arbol_identidad_nodos_ids'=>array('centinela-bastion-t1', 'centinela-bastion-t2', 'centinela-bastion-t3', 'centinela-bastion-t4', 'centinela-pinaculo-bastion'),
        'arbol_arma_nodos_ids'=>array('alcance-control-t1', 'alcance-control-t2', 'alcance-control-t3', 'alcance-control-t4', 'alcance-pinaculo-control'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'rey'),
        'fruta_id'=>66,'fruta_slug'=>'fruta.ito_ito','fruta_nombre'=>'Ito Ito no Mi','fruta_sec'=>'INT',
        'factor_linaje'=>array(
            'humanos'    => array('nombre' => 'Intelecto Aristocrático', 'spec' => 'Estrategia y calculo político perfecto (+6 INT, +4 CAR).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Ito Ito no Mi (Hilos)', 'spec' => 'Paramecia Tier IV. Control de hilos cortantes, marionetización y jaula de hilos.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'centinela'  => array('nombre' => 'Centinela — Bastión', 'spec' => 'Red de hilos defensiva inexpugnable.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'alcance'    => array('nombre' => 'Alcance — Control', 'spec' => 'Control de marioneta a distancia.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>70,'RES'=>80,'AGI'=>85,'INT'=>98,'PER'=>90,'TEM'=>80,'VOL'=>90,'CAR'=>85),
        'concepto'=>'Alto Inquisidor del Gobierno Mundial, estratega supremo con la Ito Ito no Mi.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Alto Inquisidor Vaelen — «El Inquisidor Dorado»',
        'descripcion'=>'Portavoz y comisionado supremo del Gobierno Mundial. Dirige la inquisición central con la Ito Ito no Mi.',
        'personalidad_publica'=>'Frío, calculador y absoluto.',
        'relaciones_publicas'=>array(),'recompensa'=>'No aplica (Gobierno)','fruta'=>'Ito Ito no Mi',
        'ubicacion_publica'=>'Red Line — Mary Geoise','ocupacion'=>'Comisionado Supremo','lema'=>'El orden se teje con hilos de sangre e hierro.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
);

// ── EJECUCIÓN DE ACTUALIZACIÓN EN BASE DE DATOS ──

function seed_master_npc(mysqli $db, array $n) {
    $slug = $n['slug'];
    echo "Processing NPC: {$n['nombre']} ({$slug})...\n";

    $res = $db->query("SELECT pid FROM mybb_rol_personajes WHERE slug='" . $db->real_escape_string($slug) . "' LIMIT 1");
    $is_new = true; $pid = 0;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $is_new = false; $pid = (int)$row['pid'];
    }

    $cols = array(
        'uid'             => 0,
        'nombre'          => $n['nombre'],
        'slug'            => $slug,
        'estado'          => 'aprobado',
        'activo'          => 0,
        'rango'           => $n['rango'],
        'nivel'           => (int)$n['nivel'],
        'datos'           => json_encode($n['datos'], JSON_UNESCAPED_UNICODE),
        'bio'             => json_encode($n['bio'], JSON_UNESCAPED_UNICODE),
        'rango_faccion'   => $n['rango_faccion'],
        'from_fisico'     => $n['from_fisico'],
        'desc_fisica'     => $n['desc_fisica'],
        'personalidad'    => $n['personalidad'],
        'es_npc'          => 1,
        'mundo_zona'      => $n['mundo_zona'],
        'mundo_ubic'      => $n['mundo_ubic'],
        'mundo_accion'    => $n['mundo_accion'],
        'mundo_estado_np' => $n['mundo_estado_np'],
        'datos_publicos'  => json_encode($n['datos_publicos'], JSON_UNESCAPED_UNICODE),
        'datos_internos'  => json_encode($n['datos_internos'], JSON_UNESCAPED_UNICODE),
        'pv_max'          => (int)$n['pv_max'],
        'en_max'          => (int)$n['en_max'],
        'pa_por_turno'    => (int)$n['pa'],
        'stats_json'      => json_encode($n['stats'], JSON_UNESCAPED_UNICODE),
        'isla_actual'     => $n['isla_actual'],
        'lastedit'        => time(),
    );

    $set = array(); $vals = array(); $types = '';
    foreach ($cols as $k => $v) {
        if ($k === 'slug') continue;
        $set[] = "`{$k}`=?"; $vals[] = $v; $types .= is_int($v) ? 'i' : 's';
    }
    $vals[] = $pid; $types .= 'i';
    $sql = "UPDATE mybb_rol_personajes SET " . implode(',', $set) . " WHERE pid=?";
    $st = $db->prepare($sql);
    if (!$st) { echo "  ERROR prepare: {$db->error}\n"; return; }
    $st->bind_param($types, ...$vals);
    if ($st->execute()) { echo "  UPDATE LORE OK. pid={$pid}\n"; }
    else { echo "  ERROR execute: {$st->error}\n"; return; }
    $st->close();
}

require_once __DIR__ . '/_db-config.php';
foreach ($ALL_NPCS as $npc) {
    seed_master_npc($db, $npc);
}

echo "=== LORE EXPANDIDO ACTUALIZADO EXITOSAMENTE ===\n";
