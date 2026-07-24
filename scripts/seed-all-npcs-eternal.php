<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== INICIANDO SEMBRADO MASTER DE NPCS ===\n\n";

$ALL_NPCS = array();

// ── 1. Almirante de Flota Sigrun D. Basterra ──
$ALL_NPCS[] = array(
    'slug' => 'almirante-flota-sigrun-basterra', 'nombre' => 'Sigrun D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Almirante de Flota', 'faccion_slug' => 'marine', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford — Cuartel General de la Marina',
    'mundo_accion' => 'Prepara el dispositivo de seguridad para la ejecución pública de su hijo, el Rey Pirata.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 2275, 'en_max' => 1460, 'pa' => 14, 'ps' => 536, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>99,'RES'=>98,'AGI'=>45,'INT'=>30,'PER'=>70,'TEM'=>78,'VOL'=>88,'CAR'=>60),
    'desc_fisica' => "Buccaneer de 2,40 metros de estatura, con hombros anchos como laderas de montaña y puños del tamaño de yunques. Su cabellera cana y plateada está recogida en trenzas de combate que caen sobre los hombros, enmarcando un rostro surcado por cicatrices de mil duelos con Haki endurecido. Viste el imponente uniforme blanco de Almirante de Flota con botones dorados y la gran capa blanca con el kanji de «Justicia» bañado en oro a la espalda.\n\nEn combate, la piel de sus nudillos y antebrazos se torna de un negro azabache brillante por el Haki de Armadura avanzado, mientras el aura gravitatoria de la Zushi Zushi no Mi distorsiona el aire a su alrededor, agrietando el suelo bajo sus pies pesados.",
    'personalidad' => "Inamovible como un bastión de granito. Sigrun representa el vértice supremo del deber y la Justicia Heroica de la Marina. Es una mujer justa hasta el dolor, incapaz de doblegar la ley por favoritismos, pero dotada de un respeto humano profundo hacia sus subordinados y rivales de honor.\n\nEs la madre biológica de Rolf D. Basterra, el Rey de los Piratas capturado. Jamás ha renegado de él ni de su sangre, pero entiende la ejecución pública como su prueba de fe definitiva ante el mundo. No lidera con gritos ni tiranía, sino con el ejemplo silencioso y la aplastante certeza de que su puño protege la estabilidad del océano.",
    'datos' => array(
        'raza_principal'=>'buccaneers','hibrido'=>false,'apodo'=>'El Puño de la Marina','edad'=>'58','genero'=>'Femenino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Heroica',
        'identidad'=>'coloso','arbol_identidad'=>'identidad-coloso','arbol_arma'=>'arma-cuerpo','arma'=>'punio_hierro',
        'arbol_identidad_nodos_ids'=>array('coloso-peso-t1', 'coloso-peso-t2', 'coloso-peso-t3', 'coloso-peso-t4', 'coloso-pinaculo-peso'),
        'arbol_arma_nodos_ids'=>array('cuerpo-impacto-marcial-t1', 'cuerpo-impacto-marcial-t2', 'cuerpo-impacto-marcial-t3', 'cuerpo-impacto-marcial-t4', 'cuerpo-pinaculo-impacto-marcial'),
        'haki'=>array('armadura'=>'avanzado (Pot 23)','observacion'=>'alto (Pot 19)','conquistador'=>'rey (Pot 18)'),
        'fruta_id'=>34,'fruta_slug'=>'fruta.zushi_zushi','fruta_nombre'=>'Zushi Zushi no Mi','fruta_sec'=>'TEM',
        'factor_linaje'=>array(
            'buccaneers' => array('nombre' => 'Voluntad Buccaneer', 'spec' => 'Fuerza y resistencia colosal (+6 RES, -2 CAR)', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Zushi Zushi no Mi (Gravedad)', 'spec' => 'Paramecia Tier V. Aplasta con gravedad, invierte superficies, atrae meteoros. Potencia TEM+VOL.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'coloso'     => array('nombre' => 'Coloso — Peso Absoluto', 'spec' => 'Acumula Mole y remata con daño multiplicado sin tope.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cuerpo'     => array('nombre' => 'Puño de Hierro — Puño de Dios', 'spec' => 'Golpe concentrado que penetra toda defensa como acción normal.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'hao'        => array('nombre' => 'Haki del Conquistador (Rey)', 'spec' => 'Dobla la voluntad de ejércitos enteros.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>99,'RES'=>98,'AGI'=>45,'INT'=>30,'PER'=>70,'TEM'=>78,'VOL'=>88,'CAR'=>60),
        'virtudes'=>array(
            array('nombre'=>'Zushi Zushi no Mi (gravedad)','coste'=>0,'spec'=>'Paramecia Tier V. Aplasta con gravedad, invierte superficies, atrae meteoros. Potencia 20 (TEM+VOL).'),
            array('nombre'=>'Coloso — Peso Absoluto','coste'=>0,'spec'=>'Acumula Mole y remata con daño multiplicado sin tope.'),
            array('nombre'=>'Puño de Hierro — Puño de Dios','coste'=>0,'spec'=>'Golpe concentrado que penetra toda defensa como acción normal.'),
            array('nombre'=>'Haki del Conquistador (rey)','coste'=>0,'spec'=>'Dobla la voluntad de ejércitos enteros.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Almirante de Flota Buccaneer, el puño inamovible del deber. Madre del Rey Pirata capturado.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Almirante de Flota Sigrun D. Basterra — «El Puño de la Marina»',
        'descripcion'=>'La máxima autoridad militar del mundo. Su puño, imbuido en Haki y multiplicado por la gravedad de la Zushi Zushi, rompe islas y aplasta flotas. Madre del Rey Pirata al que debe ejecutar.',
        'personalidad_publica'=>'Inamovible, justa, temida y respetada por igual.',
        'relaciones_publicas'=>array(array('nombre'=>'Rolf D. Basterra','vinculo'=>'Su hijo, el Rey Pirata capturado. Debe presidir su ejecución.','tipo'=>'compleja')),
        'recompensa'=>'No aplica (Marina)','fruta'=>'Zushi Zushi no Mi',
        'ubicacion_publica'=>'Marineford','ocupacion'=>'Almirante de Flota','lema'=>'La justicia se sostiene con el puño, no con la excusa.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'Busca en secreto una tercera vía que no pase por matar a su hijo.','objetivos_ocultos'=>array(),'conexiones_clave'=>array('Rolf D. Basterra')),
    'bio' => array('concepto'=>'El puño de la Marina','pasado'=>'Nacida en una hermandad Buccaneer oculta, ascendió a Almirante de Flota a fuerza de puños y voluntad. Su hijo tomó el mar y se convirtió en Rey Pirata.','motivacion'=>'Sostener el orden del mundo sin traicionar a su sangre.'),
);

// ── 2. Rolf D. Basterra (Rey de los Piratas) ──
$ALL_NPCS[] = array(
    'slug' => 'rey-pirata-rolf-basterra', 'nombre' => 'Rolf D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Rey de los Piratas', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Impel Down — Nivel 6 (traslado a Marineford)',
    'mundo_accion' => 'Encadenado en kairoseki, aguarda su ejecución pública con una sonrisa.',
    'mundo_estado_np' => 'Capturado', 'isla_actual' => 'impel_down',
    'pv_max' => 1910, 'en_max' => 1405, 'pa' => 22, 'ps' => 608, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>82,'RES'=>70,'AGI'=>95,'INT'=>55,'PER'=>90,'TEM'=>60,'VOL'=>96,'CAR'=>88),
    'desc_fisica' => "Humano de porte regio de 1,92 metros, con una presencia electromagnética que impone respeto incluso vistiendo harapos de prisionero del Nivel 6. Posee una cabellera negra alborotada, barba corta de tres días y unos ojos afilados llenos de chispa y burla. Su torso está cubierto de cicatrices limpias trazadas por las espadas de los mejores duelistas de la historia.\n\nIncluso encadenado con gruesos grilletes de Kairoseki pesado en pies y manos, camina erguido sin inclinar la cabeza. Carece de Akuma no Mi; su cuerpo es el receptáculo del Haki de Conquistador de Rey más denso de la era moderna.",
    'personalidad' => "Indomable, temerario y libre hasta el tuétano. Rolf es la encarnación viva del espíritu libertario del mar y la Voluntad de la D. Sabe que su muerte detonará la mayor era de piratería que el mundo haya visto jamás, y esa certeza le causa una divertida satisfacción festiva.\n\nSiente un afecto inquebrantable y sincero por su madre, la Almirante de Flota Sigrun D. Basterra, respetando la posición antagónica que el destino les obligó a ocupar. Jamás ha suplicado clemencia ni ha mostrado arrepentimiento por haber conquistado La Última Isla.",
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Rey Libre','edad'=>'28','genero'=>'Masculino',
        'faccion'=>'pirata','arquetipo'=>'La Libertad Absoluta',
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
        'virtudes'=>array(
            array('nombre'=>'Haki puro (sin fruta)','coste'=>0,'spec'=>'Conquistó el Grand Line sin comer Akuma no Mi. Hao de rey, Ken de presciencia.'),
            array('nombre'=>'Duelista — Punto Mortal','coste'=>0,'spec'=>'Sus cortes ignoran toda mitigación física; no se esquivan ni bloquean.'),
            array('nombre'=>'Filo — Mil Cortes','coste'=>0,'spec'=>'Sangrado imparable que se transfiere al ejecutar.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Rey de los Piratas capturado. Espadachín de Haki puro. Hijo de la Almirante de Flota.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Rolf D. Basterra — «El Rey Libre», Rey de los Piratas',
        'descripcion'=>'El hombre que conquistó el Grand Line con voluntad y filo, sin comer jamás una fruta. Capturado, aguarda su ejecución pública. Hijo de la Almirante de Flota.',
        'personalidad_publica'=>'Libre, carismático, imposible de doblegar.',
        'relaciones_publicas'=>array(array('nombre'=>'Sigrun D. Basterra','vinculo'=>'Su madre, la Almirante de Flota que debe ejecutarlo.','tipo'=>'compleja')),
        'recompensa'=>'La más alta de la historia','fruta'=>null,
        'ubicacion_publica'=>'Impel Down / Marineford','ocupacion'=>'Rey de los Piratas (capturado)','lema'=>'Un rey no pide permiso para ser libre.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'Sabe algo de La Última Isla que no ha revelado a nadie.','objetivos_ocultos'=>array(),'conexiones_clave'=>array('Sigrun D. Basterra')),
    'bio' => array('concepto'=>'El rey que eligió la libertad','pasado'=>'Hijo de una Marina legendaria, tomó el mar contra el deber de su madre y se coronó Rey de los Piratas tras alcanzar La Última Isla.','motivacion'=>'La libertad absoluta, aun al precio de su vida.'),
);

// ── 3. Almirante Halvar ("Escarcha") ──
$ALL_NPCS[] = array(
    'slug' => 'almirante-halvar-escarcha', 'nombre' => 'Halvar',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'faccion_slug' => 'marine', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Blinda el perímetro helado de Marineford para la ejecución.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 1920, 'en_max' => 1630, 'pa' => 16, 'ps' => 525, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>60,'RES'=>82,'AGI'=>55,'INT'=>50,'PER'=>78,'TEM'=>85,'VOL'=>88,'CAR'=>55),
    'desc_fisica' => "Humano de 2,05 metros de estatura, piel pálida como el hielo glaciar y ojos azabache sin calidez. Exhala escarcha permanente con cada respiración. Viste la túnica blanca reglamentaria de Almirante reforzada con cuellos de piel de oso ártico. Empuña una larga lanza tridente de acero forjado en frío.\n\nGracias al poder de la Hie Hie no Mi, puede congelar kilómetros de mar al contacto con su palma y reconstruir su cuerpo de hielo puro ante cualquier impacto físico.",
    'personalidad' => "Frío, riguroso e inclemente. Halvar encarna la Justicia Absoluta en su variante más inflexible: la ley es una estructura de hielo que no admite grietas ni concesiones emocionales. Para él, la compasión es la debilidad por donde se filtra el caos.",
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'Escarcha','edad'=>'49','genero'=>'Masculino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Absoluta',
        'identidad'=>'centinela','arbol_identidad'=>'identidad-centinela','arbol_arma'=>'arma-alcance','arma'=>'lanza',
        'arbol_identidad_nodos_ids'=>array('centinela-bastion-t1', 'centinela-bastion-t2', 'centinela-bastion-t3', 'centinela-bastion-t4', 'centinela-pinaculo-bastion'),
        'arbol_arma_nodos_ids'=>array('alcance-control-t1', 'alcance-control-t2', 'alcance-control-t3', 'alcance-control-t4', 'alcance-pinaculo-control'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'no'),
        'fruta_id'=>16,'fruta_slug'=>'fruta.hie_hie','fruta_nombre'=>'Hie Hie no Mi','fruta_sec'=>'VOL',
        'factor_linaje'=>array(
            'humanos'    => array('nombre' => 'Adaptabilidad Humana', 'spec' => 'Improvisar y resistir ante la adversidad.', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Hie Hie no Mi (Hielo)', 'spec' => 'Logia Tier IV. Congela mares (Era de Hielo), lanzas de escarcha, congelación biológica.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'centinela'  => array('nombre' => 'Centinela — Bastión', 'spec' => 'Muro inamovible; ancla y protege la zona.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'alcance'    => array('nombre' => 'Alcance — Control', 'spec' => 'Engancha, ata y enraíza al enemigo a distancia.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>60,'RES'=>82,'AGI'=>55,'INT'=>50,'PER'=>78,'TEM'=>85,'VOL'=>88,'CAR'=>55),
        'virtudes'=>array(
            array('nombre'=>'Hie Hie no Mi (hielo)','coste'=>0,'spec'=>'Logia Tier IV. Congela mares (Era de Hielo), lanzas de escarcha, congelación biológica. Potencia 21 (TEM+VOL).'),
            array('nombre'=>'Centinela — Bastión','coste'=>0,'spec'=>'Muro inamovible; ancla y protege la zona.'),
            array('nombre'=>'Alcance — Control','coste'=>0,'spec'=>'Engancha, ata y enraíza al enemigo a distancia.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Almirante de hielo, la muralla blanca de la Justicia Absoluta.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Almirante Halvar — «Escarcha»',
        'descripcion'=>'La muralla de hielo de la Marina. Congela mares enteros y ancla el campo de batalla; nadie cruza su línea.',
        'personalidad_publica'=>'Frío e implacable; la ley por encima de todo.',
        'relaciones_publicas'=>array(array('nombre'=>'Sigrun D. Basterra','vinculo'=>'Su Almirante de Flota. Le obedece sin fisuras.','tipo'=>'leal')),
        'recompensa'=>'No aplica (Marina)','fruta'=>'Hie Hie no Mi',
        'ubicacion_publica'=>'Marineford','ocupacion'=>'Almirante','lema'=>'La ley no se negocia: se congela.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'La muralla blanca','pasado'=>'Ascendió por su capacidad de contener él solo frentes enteros con su hielo.','motivacion'=>'Un mundo sin excepciones a la ley.'),
);

// ── 4. Almirante Ysolde ("La Cazadora") ──
$ALL_NPCS[] = array(
    'slug' => 'almirante-ysolde-cazadora', 'nombre' => 'Ysolde',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'faccion_slug' => 'marine', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Rastrea infiltrados piratas antes de la ejecución.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 1820, 'en_max' => 1355, 'pa' => 22, 'ps' => 529, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>74,'RES'=>65,'AGI'=>92,'INT'=>55,'PER'=>92,'TEM'=>55,'VOL'=>78,'CAR'=>50),
    'desc_fisica' => "Mink loba de 1,90 metros de estatura, pelaje gris plata tupido y deslumbrantes ojos ámbar de depredador. Viste un traje táctico de cuero negro entallado bajo una chaqueta blanca de Almirante sin mangas. A la espalda lleva un rifle francotirador de largo alcance con cañón reforzado.\n\nCarece de Akuma no Mi. Canaliza descargas de Electro a través de la mira telescópica y sus garras. Bajo la luna llena, su transformación en Sulong triplica su envergadura, cubriéndola de un pelaje blanco puro con ojos rojos fulgurantes.",
    'personalidad' => "Pragmática, reservada y con una paciencia letal. Ysolde entiende el combate como una cacería de precisión: no cree en discursos sobre la justicia ni en exhibiciones inútiles de poder. Su único objetivo es neutralizar al objetivo asignado con el menor número de disparos.",
    'datos' => array(
        'raza_principal'=>'minks','hibrido'=>false,'apodo'=>'La Cazadora','edad'=>'37','genero'=>'Femenino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Pragmática',
        'identidad'=>'cazador','arbol_identidad'=>'identidad-cazador','arbol_arma'=>'arma-distancia','arma'=>'arma_fuego',
        'arbol_identidad_nodos_ids'=>array('cazador-marcaje-t1', 'cazador-marcaje-t2', 'cazador-marcaje-t3', 'cazador-marcaje-t4', 'cazador-pinaculo-marcaje'),
        'arbol_arma_nodos_ids'=>array('distancia-precision-t1', 'distancia-precision-t2', 'distancia-precision-t3', 'distancia-precision-t4', 'distancia-pinaculo-precision'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'no'),
        'fruta_id'=>null,'fruta_slug'=>null,'fruta_nombre'=>null,
        'factor_linaje'=>array(
            'minks'      => array('nombre' => 'Latido Salvaje + Electro', 'spec' => 'Descarga eléctrica en sus ataques (+4 AGI, +4 FUE, -4 VOL).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'sulong'     => array('nombre' => 'Sulong (Luna Llena)', 'spec' => 'Transformación letal que dispara sus capacidades bajo luna llena.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cazador'    => array('nombre' => 'Cazador — Marcaje', 'spec' => 'Acumula Rastro sobre la presa y remata más fuerte cuanto más la persigue.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'distancia'  => array('nombre' => 'Distancia — Precisión', 'spec' => 'Un tiro, una bala: marca y explota debilidades a kilómetros.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>74,'RES'=>65,'AGI'=>92,'INT'=>55,'PER'=>92,'TEM'=>55,'VOL'=>78,'CAR'=>50),
        'virtudes'=>array(
            array('nombre'=>'Electro (Mink)','coste'=>0,'spec'=>'Descarga eléctrica en sus ataques.'),
            array('nombre'=>'Sulong (luna llena)','coste'=>0,'spec'=>'Transformación que dispara sus capacidades bajo luna llena real.'),
            array('nombre'=>'Cazador — Marcaje','coste'=>0,'spec'=>'Acumula Rastro sobre la presa y remata más fuerte cuanto más la persigue.'),
            array('nombre'=>'Distancia — Precisión','coste'=>0,'spec'=>'Un tiro, una bala: marca y explota debilidades a kilómetros.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Almirante Mink francotiradora; rastrea y abate. Sin fruta.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Almirante Ysolde — «La Cazadora», el Ojo de la Luna',
        'descripcion'=>'Rastrea a su presa a kilómetros y la abate de un solo tiro. Bajo la luna llena, Sulong.',
        'personalidad_publica'=>'Pragmática, paciente, letal.',
        'relaciones_publicas'=>array(array('nombre'=>'Sigrun D. Basterra','vinculo'=>'Su Almirante de Flota.','tipo'=>'leal')),
        'recompensa'=>'No aplica (Marina)','fruta'=>null,
        'ubicacion_publica'=>'Marineford','ocupacion'=>'Almirante','lema'=>'No fallo. Solo espero el momento.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'El ojo de la luna','pasado'=>'Cazadora de Zou reclutada por la Marina por su puntería sobrehumana.','motivacion'=>'Resultados, no discursos.'),
);

// ── 5. Almirante Draven ("El Martillo del Abismo") ──
$ALL_NPCS[] = array(
    'slug' => 'almirante-draven-martillo', 'nombre' => 'Draven',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'faccion_slug' => 'marine', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Refuerza las murallas y la bahía de Marineford.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 2160, 'en_max' => 1410, 'pa' => 14, 'ps' => 502, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>96,'RES'=>88,'AGI'=>45,'INT'=>40,'PER'=>60,'TEM'=>68,'VOL'=>82,'CAR'=>55),
    'desc_fisica' => "Gyojin Tiburón de 3,10 metros de altura, con piel gris acorazada repleta de cicatrices de combates submarinos y una mandíbula con tres hileras de dientes afilados. Lleva el pecho descubierto luciendo el tatuaje del Sol de los Gyojin reescrito con el emblema de la Marina, y cubre sus hombros con la capa blanca militar. Empuña un Kanabō colosal de hierro negro.\n\nNo posee Akuma no Mi, lo que le permite mantener su supremacía marina absoluta. Su Karate Gyojin genera ondas de choque acuáticas capaces de partir cascos de acorazados de un solo golpe.",
    'personalidad' => "Brutal pero guiado por un código de honor estricto. Draven representa la Justicia Guerrera: cree que la fuerza bruta debe estar al servicio de proteger a los débiles. Exige disciplina absoluta y no tolera la cobardía.",
    'datos' => array(
        'raza_principal'=>'gyojins','hibrido'=>false,'apodo'=>'El Martillo del Abismo','edad'=>'44','genero'=>'Masculino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Guerrera',
        'identidad'=>'verdugo','arbol_identidad'=>'identidad-verdugo','arbol_arma'=>'arma-contundente','arma'=>'maza',
        'arbol_identidad_nodos_ids'=>array('verdugo-sentencia-t1', 'verdugo-sentencia-t2', 'verdugo-sentencia-t3', 'verdugo-sentencia-t4', 'verdugo-pinaculo-sentencia'),
        'arbol_arma_nodos_ids'=>array('contundente-impacto-t1', 'contundente-impacto-t2', 'contundente-impacto-t3', 'contundente-impacto-t4', 'contundente-pinaculo-impacto'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'no'),
        'fruta_id'=>null,'fruta_slug'=>null,'fruta_nombre'=>null,
        'factor_linaje'=>array(
            'gyojins'    => array('nombre' => 'Piel de Abismo + Hijo del Mar', 'spec' => 'Piel acorazada e inmunidad acuática (+6 FUE, -2 PER).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'karate'     => array('nombre' => 'Karate Gyojin', 'spec' => 'Bajo el agua sus golpes ganan alcance y potencia (chorros de agua a presión).', 'valor' => 0, 'tipo' => 'dote_innata'),
            'verdugo'    => array('nombre' => 'Verdugo — Sentencia', 'spec' => 'Acumula Dominio sobre el controlado y lo remata sin vuelta atrás.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'contundente'=> array('nombre' => 'Contundente — Impacto', 'spec' => 'Rotura de guardia y aturdimiento con maza pesada.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>96,'RES'=>88,'AGI'=>45,'INT'=>40,'PER'=>60,'TEM'=>68,'VOL'=>82,'CAR'=>55),
        'virtudes'=>array(
            array('nombre'=>'Karate Gyojin','coste'=>0,'spec'=>'Bajo el agua sus golpes ganan alcance y potencia (chorros de agua a presión).'),
            array('nombre'=>'Verdugo — Sentencia','coste'=>0,'spec'=>'Acumula Dominio sobre el controlado y lo remata sin vuelta atrás.'),
            array('nombre'=>'Contundente — Impacto','coste'=>0,'spec'=>'Rotura de guardia y Aturdimiento.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Almirante Gyojin, martillo bruto de la Justicia Guerrera. Sin fruta.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Almirante Draven — «El Martillo del Abismo»',
        'descripcion'=>'Muele guardias y remata al controlado. Bajo el agua no tiene rival.',
        'personalidad_publica'=>'Guerrero de honor brutal y directo.',
        'relaciones_publicas'=>array(array('nombre'=>'Sigrun D. Basterra','vinculo'=>'Su Almirante de Flota.','tipo'=>'leal')),
        'recompensa'=>'No aplica (Marina)','fruta'=>null,
        'ubicacion_publica'=>'Marineford','ocupacion'=>'Almirante','lema'=>'El fuerte protege. El débil, que calle.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'El martillo del abismo','pasado'=>'Defensor del Reino de Ryugu que ascendió en la Marina por su fuerza descomunal.','motivacion'=>'Proteger a los débiles con sus propias manos.'),
);

// ── 6. YONKO Kaiser Vaelgor ("El Rey de la Ruina") ──
$ALL_NPCS[] = array(
    'slug' => 'yonko-kaiser-vaelgor', 'nombre' => 'Kaiser Vaelgor',
    'rango' => 'M+', 'rango_faccion' => 'Emperador del Mar / Yonko', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 4850000000, 'from_fisico' => 'Oni del Norte',
    'mundo_zona' => 'nuevo_mundo', 'mundo_ubic' => 'Isla Skar — Fortaleza de la Ruina',
    'mundo_accion' => 'Moviliza la flota del Imperio Asolador aprovechando la distracción de Marineford.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'skar',
    'pv_max' => 2450, 'en_max' => 1510, 'pa' => 16, 'ps' => 580, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>99,'RES'=>99,'AGI'=>40,'INT'=>45,'PER'=>65,'TEM'=>85,'VOL'=>98,'CAR'=>75),
    'desc_fisica' => "Gigantesco Oni del Norte de 4,20 metros de estatura, piel violácea curtida y dos cuernos masivos curvados hacia el cielo. Luce una barba negra trenzada con argollas de oro confiscadas a reyes caídos. Viste una coraza pesada de hierro negro y una capa de piel de bestia marina.\n\nEmpuña un Kanabō gigante imbuido en Haki de Conquistador de Rey. Al activar la Gura Gura no Mi, crea grietas luminosas en la estructura misma del aire, desencadenando tsunamis y terremotos capaces de fragmentar islas enteras.",
    'personalidad' => "Un tirano despiadado que cree únicamente en el dominio del más fuerte. Vaelgor contempla el mar como una arena de supervivencia donde los débiles están destinados a ser aplastados. Es orgulloso, indomable y ambiciona someter tanto al Gobierno Mundial como a los demás Yonkou bajo su trono de ruina.",
    'datos' => array(
        'raza_principal'=>'onis','hibrido'=>false,'apodo'=>'El Rey de la Ruina','edad'=>'62','genero'=>'Masculino',
        'faccion'=>'pirata','arquetipo'=>'El Conquistador de Ruina',
        'identidad'=>'coloso','arbol_identidad'=>'identidad-coloso','arbol_arma'=>'arma-contundente','arma'=>'maza',
        'arbol_identidad_nodos_ids'=>array('coloso-peso-t1', 'coloso-peso-t2', 'coloso-peso-t3', 'coloso-peso-t4', 'coloso-pinaculo-peso'),
        'arbol_arma_nodos_ids'=>array('contundente-impacto-t1', 'contundente-impacto-t2', 'contundente-impacto-t3', 'contundente-impacto-t4', 'contundente-pinaculo-impacto'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'rey'),
        'fruta_id'=>15,'fruta_slug'=>'fruta.gura_gura','fruta_nombre'=>'Gura Gura no Mi','fruta_sec'=>'FUE',
        'factor_linaje'=>array(
            'onis'       => array('nombre' => 'Fuerza Ancestral Oni', 'spec' => 'Anatomía de gigante oscuro (+6 FUE, +4 RES, -4 INT).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Gura Gura no Mi (Terremoto)', 'spec' => 'Paramecia Tier V. Genera sismos, rompe el aire y desata tsunamis colosales.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'coloso'     => array('nombre' => 'Coloso — Peso Absoluto', 'spec' => 'Acumula Mole y destruye estructuras con multiplicador de daño.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'contundente'=> array('nombre' => 'Contundente — Impacto', 'spec' => 'Rotura de guardia y aturdimiento masivo con Kanabō.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'hao'        => array('nombre' => 'Haki del Conquistador (Rey)', 'spec' => 'Empapa sus golpes en Haki del Rey agrietando la realidad.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>99,'RES'=>99,'AGI'=>40,'INT'=>45,'PER'=>65,'TEM'=>85,'VOL'=>98,'CAR'=>75),
        'virtudes'=>array(
            array('nombre'=>'Gura Gura no Mi (Terremoto)','coste'=>0,'spec'=>'Destrucción sísmica pura.'),
            array('nombre'=>'Haki de Conquistador de Rey','coste'=>0,'spec'=>'Aura de dominación absoluta.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Yonko Oni definitivo, señor de la guerra y portador de la Gura Gura no Mi.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Yonko Kaiser Vaelgor — «El Rey de la Ruina»',
        'descripcion'=>'Emperador del mar y soberano del Imperio Asolador. Su Gura Gura no Mi y su Kanabō destruyen flotas e islas enteras.',
        'personalidad_publica'=>'Tirano despiadado, indomable y absoluto.',
        'relaciones_publicas'=>array(),'recompensa'=>'4.850.000.000 Berries','fruta'=>'Gura Gura no Mi',
        'ubicacion_publica'=>'Nuevo Mundo — Isla Skar','ocupacion'=>'Yonko / Capitán Pirata','lema'=>'Solo lo que permanece en pie tiene derecho a existir.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'Planea asaltar Impel Down si la ejecución de Rolf fracasa.','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'El rey de la ruina','pasado'=>'Conquistó docenas de reinos del Nuevo Mundo a la fuerza hasta erigirse Yonko.','motivacion'=>'Someter el mar entero bajo su dominio.'),
);

// ── 7. YONKO Jarl Brogaz ("El Tragahierro") ──
$ALL_NPCS[] = array(
    'slug' => 'yonko-jarl-brogaz', 'nombre' => 'Jarl Brogaz',
    'rango' => 'M+', 'rango_faccion' => 'Emperador del Mar / Yonko', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 4300000000, 'from_fisico' => 'Gigante de Elbaf',
    'mundo_zona' => 'nuevo_mundo', 'mundo_ubic' => 'Isla Ironforge — Gran Cañón de Elbaf',
    'mundo_accion' => 'Forja artillería pesada combinando minerales raros con su fruta.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'ironforge',
    'pv_max' => 2600, 'en_max' => 1400, 'pa' => 14, 'ps' => 565, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>99,'RES'=>99,'AGI'=>30,'INT'=>50,'PER'=>60,'TEM'=>80,'VOL'=>90,'CAR'=>70),
    'desc_fisica' => 'Gigante titánico de 18,5 metros de estatura, con músculos como bloques de granito y barba pelirroja al estilo vikingo. Lleva una armadura artesanal de placas de acero soldadas directamente a su piel mediante el poder de su fruta Baku Baku no Mi.\n\nEn combate, devora cañones, barcos y minerales preciados para transformar su propio cuerpo en una batería caminante de supercañones de gran calibre embuidos en Haki de Armadura.',
    'personalidad' => 'Un guerrero honorable de Elbaf con una pasión desmedida por la herrería, el alcohol y las batallas titánicas. Aunque es un pirata feroz, protege los territorios bajo su bandera como una familia extendida y valora la lealtad por encima del oro.',
    'datos' => array(
        'raza_principal'=>'gigantes','hibrido'=>false,'apodo'=>'El Tragahierro','edad'=>'145','genero'=>'Masculino',
        'faccion'=>'pirata','arquetipo'=>'El Gigante de Acero',
        'identidad'=>'detonador','arbol_identidad'=>'identidad-detonador','arbol_arma'=>'arma-cuerpo','arma'=>'punio_hierro',
        'arbol_identidad_nodos_ids'=>array('detonador-acumulacion-t1', 'detonador-acumulacion-t2', 'detonador-acumulacion-t3', 'detonador-acumulacion-t4', 'detonador-pinaculo-acumulacion'),
        'arbol_arma_nodos_ids'=>array('cuerpo-rafaga-t1', 'cuerpo-rafaga-t2', 'cuerpo-rafaga-t3', 'cuerpo-rafaga-t4', 'cuerpo-pinaculo-rafaga'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'no'),
        'fruta_id'=>40,'fruta_slug'=>'fruta.baku_baku','fruta_nombre'=>'Baku Baku no Mi','fruta_sec'=>'FUE',
        'factor_linaje'=>array(
            'gigantes'   => array('nombre' => 'Cuerpo de Gigante de Elbaf', 'spec' => 'Estatura colosal y fuerza física titánica (+8 RES, +4 FUE, -6 AGI).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Baku Baku no Mi (Fusión Metal)', 'spec' => 'Paramecia Tier IV. Devora metal y objetos para integrarlos en su anatomía.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'detonador'  => array('nombre' => 'Detonador — Reserva Sin Fondo', 'spec' => 'Acumula cargas de cañón y desata salvas devastadoras.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cuerpo'     => array('nombre' => 'Cuerpo — Puño del Rey', 'spec' => 'Impacto masivo de puño de acero.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>99,'RES'=>99,'AGI'=>30,'INT'=>50,'PER'=>60,'TEM'=>80,'VOL'=>90,'CAR'=>70),
        'virtudes'=>array(
            array('nombre'=>'Baku Baku no Mi (Fusión Metal)','coste'=>0,'spec'=>'Devora y transmuta su cuerpo en artillería.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Yonko Gigante de Elbaf que integró la Baku Baku no Mi en su anatomía militar.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Yonko Jarl Brogaz — «El Tragahierro»',
        'descripcion'=>'Emperador del mar y señor de Elbaf. Devora metal para convertir su cuerpo gigante en fortaleza militar.',
        'personalidad_publica'=>'Guerrero honorable, festivo y feroz.',
        'relaciones_publicas'=>array(),'recompensa'=>'4.300.000.000 Berries','fruta'=>'Baku Baku no Mi',
        'ubicacion_publica'=>'Nuevo Mundo — Isla Ironforge','ocupacion'=>'Yonko / Capitán Pirata','lema'=>'El acero se digiere bien cuando hay buena batalla.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'El tragahierro de Elbaf','pasado'=>'Zarpó de Elbaf hace un siglo y forjó la mayor armada titánica del mar.','motivacion'=>'Protección de los suyos y la batalla definitiva.'),
);

// ── 8. YONKO Princesa Rosette ("La Masacre Diminuta") ──
$ALL_NPCS[] = array(
    'slug' => 'yonko-princesa-rosette', 'nombre' => 'Princesa Rosette',
    'rango' => 'M+', 'rango_faccion' => 'Emperadora del Mar / Yonko', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 4150000000, 'from_fisico' => 'Tontatta Sanguinaria',
    'mundo_zona' => 'nuevo_mundo', 'mundo_ubic' => 'Isla Rosewood — El Jardín Sangriento',
    'mundo_accion' => 'Rastrea rutas comerciales para interceptar valiosos tesoros.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'rosewood',
    'pv_max' => 1750, 'en_max' => 1450, 'pa' => 24, 'ps' => 540, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>75,'RES'=>60,'AGI'=>99,'INT'=>65,'PER'=>95,'TEM'=>60,'VOL'=>85,'CAR'=>75),
    'desc_fisica' => "Tontatta diminuta de solo 18 centímetros de estatura, con vestiduras de seda roja de reina y una pequeña corona de espinas de rubí. A pesar de su reducido tamaño, posee una fuerza muscular proporcionalmente monstruosa y una agilidad invisible al ojo humano.\n\nEs portadora de la Zoan Mitológica de la Bat Bat no Mi, Modelo: Vampiro, permitiéndole desplegar grandes alas de murciélago carmesí, drenar la vitalidad de sus víctimas para rejuvenecer y controlar murciélagos de sombra.",
    'personalidad' => "Sanguinaria, caprichosa y despiadada. Rosette aprovecha su apariencia diminuta e inocente para infundir un terror absoluto: descuartiza flotas enteras desde las sombras sin despeinarse. Odia que la traten como a una criatura frágil.",
    'datos' => array(
        'raza_principal'=>'tontattas','hibrido'=>false,'apodo'=>'La Masacre Diminuta','edad'=>'32','genero'=>'Femenino',
        'faccion'=>'pirata','arquetipo'=>'La Reina Vampiro',
        'identidad'=>'verdugo','arbol_identidad'=>'identidad-verdugo','arbol_arma'=>'arma-filo','arma'=>'daga',
        'arbol_identidad_nodos_ids'=>array('verdugo-sentencia-t1', 'verdugo-sentencia-t2', 'verdugo-sentencia-t3', 'verdugo-sentencia-t4', 'verdugo-pinaculo-sentencia'),
        'arbol_arma_nodos_ids'=>array('filo-apertura-t1', 'filo-apertura-t2', 'filo-apertura-t3', 'filo-apertura-t4', 'filo-pinaculo-apertura'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'no'),
        'fruta_id'=>64,'fruta_slug'=>'fruta.inu_kyubi','fruta_nombre'=>'Bat Bat no Mi, Modelo: Vampiro','fruta_sec'=>'AGI',
        'factor_linaje'=>array(
            'tontattas'  => array('nombre' => 'Fuerza Diminuta Tontatta', 'spec' => 'Tamaño diminuto con agilidad y fuerza extrema (+8 AGI, +4 PER, -6 RES).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Bat Bat: Vampiro (Zoan Mitológica)', 'spec' => 'Absorbe vitalidad, vuelo con alas de murciélago y regeneración.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'verdugo'    => array('nombre' => 'Verdugo — Ejecutor Absoluto', 'spec' => 'Sometimiento de objetivos y remate de sangre.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'filo'       => array('nombre' => 'Filo — Mil Cortes', 'spec' => 'Dagas sangrientas invisibles.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>75,'RES'=>60,'AGI'=>99,'INT'=>65,'PER'=>95,'TEM'=>60,'VOL'=>85,'CAR'=>75),
        'virtudes'=>array(
            array('nombre'=>'Zoan Mitológica Vampiro','coste'=>0,'spec'=>'Drenaje de vitalidad y regeneración.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Yonko Tontatta sanguinaria con la Zoan Mitológica del Vampiro.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Yonko Princesa Rosette — «La Masacre Diminuta»',
        'descripcion'=>'La Yonko Tontatta más temida del Nuevo Mundo. Su tamaño e inocencia ocultan una vampira sanguinaria letal.',
        'personalidad_publica'=>'Caprichosa, sanguinaria y despiadada.',
        'relaciones_publicas'=>array(),'recompensa'=>'4.150.000.000 Berries','fruta'=>'Bat Bat no Mi: Vampiro',
        'ubicacion_publica'=>'Nuevo Mundo — Isla Rosewood','ocupacion'=>'Yonko / Capitana Pirata','lema'=>'Las rosas crecen más rojas con sangre fresca.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'La masacre diminuta','pasado'=>'Fundó la Corte de Espinas tras masacrar a los piratas que intentaron esclavizar su aldea.','motivacion'=>'Someter a los gigantes del mar.'),
);

// ── 9. YONKO Sylphira ("La Hoja Inmaculada") ──
$ALL_NPCS[] = array(
    'slug' => 'yonko-sylphira', 'nombre' => 'Sylphira',
    'rango' => 'M+', 'rango_faccion' => 'Emperadora del Mar / Yonko', 'faccion_slug' => 'piratas', 'nivel' => 50,
    'berries' => 4500000000, 'from_fisico' => 'Skypean Elegante',
    'mundo_zona' => 'nuevo_mundo', 'mundo_ubic' => 'Isla Aethelgard — El Santuario Celeste',
    'mundo_accion' => 'Entrena en el manejo de presciencia de Haki en el santuario celestial.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'aethelgard',
    'pv_max' => 1890, 'en_max' => 1520, 'pa' => 22, 'ps' => 550, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>85,'RES'=>68,'AGI'=>98,'INT'=>60,'PER'=>95,'TEM'=>65,'VOL'=>92,'CAR'=>80),
    'desc_fisica' => "Skypean de belleza seráfica y 1,82 metros de estatura, con un par de alas blancas inmaculadas en su espalda. Viste una túnica de combate azul rey con detalles dorados y armadura ligera de diales celestes. Empuña una fina meito (espada de renombre) de hoja plateada impecable.\n\nNo posee Akuma no Mi. Su combate se basa exclusivamente en la maestría suprema del filo, el uso táctico de Diales de Viento y Rechazo, y un Haki de Observación tan desarrollado que prevé los movimientos rivales segundos antes de que ocurran.",
    'personalidad' => "Elegante, stoica y de una compostura inquebrantable. Sylphira busca la perfección marcial y la armonía del cielo. Desprecia la brutalidad inútil pero es implacable contra quienes amenazan el equilibrio del océano.",
    'datos' => array(
        'raza_principal'=>'skypeans','hibrido'=>false,'apodo'=>'La Hoja Inmaculada','edad'=>'35','genero'=>'Femenino',
        'faccion'=>'pirata','arquetipo'=>'La Espadachina Celeste',
        'identidad'=>'duelista','arbol_identidad'=>'identidad-duelista','arbol_arma'=>'arma-filo','arma'=>'espada',
        'arbol_identidad_nodos_ids'=>array('duelista-precision-t1', 'duelista-precision-t2', 'duelista-precision-t3', 'duelista-precision-t4', 'duelista-pinaculo-precision'),
        'arbol_arma_nodos_ids'=>array('filo-apertura-t1', 'filo-apertura-t2', 'filo-apertura-t3', 'filo-apertura-t4', 'filo-pinaculo-apertura'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'rey'),
        'fruta_id'=>null,'fruta_slug'=>null,'fruta_nombre'=>null,
        'factor_linaje'=>array(
            'skypeans'   => array('nombre' => 'Alas del Cielo Skypean', 'spec' => 'Movilidad aérea y dominio de Diales celestes (+4 AGI, +4 PER).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'haki_puro'  => array('nombre' => 'Presciencia del Mantis', 'spec' => 'Haki de Observación avanzado que anticipa ataques futuros.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'duelista'   => array('nombre' => 'Duelista — Punto Mortal', 'spec' => 'Estocadas quirúrgicas de precisión absoluta.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'filo'       => array('nombre' => 'Filo — Mil Cortes', 'spec' => 'Cortes aéreos impulsados por Diales de Viento.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>85,'RES'=>68,'AGI'=>98,'INT'=>60,'PER'=>95,'TEM'=>65,'VOL'=>92,'CAR'=>80),
        'virtudes'=>array(
            array('nombre'=>'Maestría de Diales y Espada','coste'=>0,'spec'=>'Sin fruta; combate aéreo fluido.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Yonko Skypean espadachina suprema sin fruta con Haki de presciencia.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Yonko Sylphira — «La Hoja Inmaculada»',
        'descripcion'=>'Emperadora del mar y capitana de las Espadas Celestes. Espadachina suprema sin fruta.',
        'personalidad_publica'=>'Elegante, estoica y de maestría perfecta.',
        'relaciones_publicas'=>array(),'recompensa'=>'4.500.000.000 Berries','fruta'=>null,
        'ubicacion_publica'=>'Nuevo Mundo — Isla Aethelgard','ocupacion'=>'Yonko / Capitana Pirata','lema'=>'Un filo puro no necesita fruta para cortar el cielo.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'La hoja inmaculada','pasado'=>'Descendió de las islas del cielo para medir su filo contra los espadachines del mar.', 'motivacion'=>'Alcanzar la cima marcial absoluta.'),
);

// ── 10. Alto Inquisidor Vaelen (Gobierno Mundial) ──
$ALL_NPCS[] = array(
    'slug' => 'alto-inquisidor-vaelen', 'nombre' => 'Vaelen',
    'rango' => 'M+', 'rango_faccion' => 'Comisionado Supremo', 'faccion_slug' => 'gobierno', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Aristócrata de Mary Geoise',
    'mundo_zona' => 'red_line', 'mundo_ubic' => 'Mary Geoise — Tribunal del Dragón',
    'mundo_accion' => 'Supervisa la respuesta del Gobierno ante la amenaza de los Yonkou.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'mary_geoise',
    'pv_max' => 2050, 'en_max' => 1600, 'pa' => 18, 'ps' => 560, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>70,'RES'=>80,'AGI'=>85,'INT'=>98,'PER'=>90,'TEM'=>80,'VOL'=>90,'CAR'=>85),
    'desc_fisica' => "Humano de 1,95 metros de estatura, porte aristocrático impecable y vestiduras ceremoniales blancas con bordados de hilo de oro del Gobierno Mundial. Luce una máscara inquisitorial de porcelana dorada que cubre la mitad superior de su rostro.\n\nEs el portador de la Ito Ito no Mi, permitiéndole crear hilos invisibles de tenacidad de titanio para marionetizar enemigos, cortar extremidades a distancia y desplazarse sujetándose de las nubes del cielo.",
    'personalidad' => "Frío, calculadora e inquisitorial. Vaelen representa la Autoridad Suprema del Gobierno Mundial: para él, las vidas humanas son piezas de ajedrez en el gran tablero de la estabilidad global. No vacila en ordenar Buster Calls o purgas totales si la ley de Mary Geoise lo requiere.",
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Inquisidor Dorado','edad'=>'52','genero'=>'Masculino',
        'faccion'=>'gobierno','arquetipo'=>'La Autoridad Suprema',
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
        'virtudes'=>array(
            array('nombre'=>'Ito Ito no Mi (Hilos)','coste'=>0,'spec'=>'Marionetización y corte por hilos.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
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
    'bio' => array('concepto'=>'El inquisidor de Mary Geoise','pasado'=>'Ascendió a través del cuerpo inquisitorial eliminando amenazas al Gobierno.', 'motivacion'=>'Preservar la autoridad incontestada del Gobierno Mundial.'),
);

// ── 11. Valerius (Gran Maestro Cazarrecompensas) ──
$ALL_NPCS[] = array(
    'slug' => 'valerius-cazador-supremo', 'nombre' => 'Valerius',
    'rango' => 'M', 'rango_faccion' => 'Líder del Gremio de Cazadores', 'faccion_slug' => 'cazarrecompensas', 'nivel' => 50,
    'berries' => 1200000000, 'from_fisico' => 'Tirador Táctico Veterano',
    'mundo_zona' => 'gran_line', 'mundo_ubic' => 'Isla Bounty — Cuartel del Gremio',
    'mundo_accion' => 'Acepta contratos especiales para cazar piratas del Nuevo Mundo.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'bounty',
    'pv_max' => 1850, 'en_max' => 1400, 'pa' => 20, 'ps' => 520, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>75,'RES'=>70,'AGI'=>94,'INT'=>70,'PER'=>98,'TEM'=>60,'VOL'=>80,'CAR'=>65),
    'desc_fisica' => "Humano de 1,88 metros de estatura, con rostro curtido por el sol y la sal marina, parche en el ojo izquierdo y un poncho táctico marrón sobre armadura ligera reforzada con fundas de munidores. Porta dos trabucos pesados de precisión y cinturones con proyectiles especiales de Kairoseki puro.\n\nSin Akuma no Mi. Su eficacia proviene de su Haki de Presciencia sobrehumano y su arsenal táctico antirrol de usuarios de fruta.",
    'personalidad' => "Pragmático, astuto y enfocado en los negocios. Valerius no caza por ideología ni por patria: caza por berries y por la reputación inigualable de ser el depredador más peligroso del mar.",
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Ojo del Gremio','edad'=>'48','genero'=>'Masculino',
        'faccion'=>'cazarrecompensas','arquetipo'=>'El Cazador Supremo',
        'identidad'=>'cazador','arbol_identidad'=>'identidad-cazador','arbol_arma'=>'arma-distancia','arma'=>'arma_fuego',
        'arbol_identidad_nodos_ids'=>array('cazador-marcaje-t1', 'cazador-marcaje-t2', 'cazador-marcaje-t3', 'cazador-marcaje-t4', 'cazador-pinaculo-marcaje'),
        'arbol_arma_nodos_ids'=>array('distancia-precision-t1', 'distancia-precision-t2', 'distancia-precision-t3', 'distancia-precision-t4', 'distancia-pinaculo-precision'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'no'),
        'fruta_id'=>null,'fruta_slug'=>null,'fruta_nombre'=>null,
        'factor_linaje'=>array(
            'humanos'    => array('nombre' => 'Instinto Cazador Humano', 'spec' => 'Rastreo y puntería sobrehumana (+4 AGI, +6 PER).', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'kairoseki'  => array('nombre' => 'Munición de Kairoseki', 'spec' => 'Disparos que anulan poderes de Akuma no Mi al impacto.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'cazador'    => array('nombre' => 'Cazador — Cacería Perfecta', 'spec' => 'Marcaje de presas a larga distancia.', 'valor' => 0, 'tipo' => 'dote_innata'),
            'distancia'  => array('nombre' => 'Distancia — Bala de Oro', 'spec' => 'Disparo penetrante definitivo.', 'valor' => 0, 'tipo' => 'dote_innata')
        ),
        'stats_efectivas'=>array('FUE'=>75,'RES'=>70,'AGI'=>94,'INT'=>70,'PER'=>98,'TEM'=>60,'VOL'=>80,'CAR'=>65),
        'virtudes'=>array(
            array('nombre'=>'Munición Táctica Kairoseki','coste'=>0,'spec'=>'Anulación de Akumas a distancia.'),
        ),
        'defectos'=>array(),'pl_balance'=>0,
        'concepto'=>'Gran Maestro del Gremio de Cazarrecompensas, tirador de Kairoseki.',
    ),
    'datos_publicos' => array(
        'titulo'=>'Valerius — «El Ojo del Gremio»',
        'descripcion'=>'Líder del Gremio de Cazadores. Caza piratas de recompensas millonarias con proyectiles de Kairoseki.',
        'personalidad_publica'=>'Pragmático, astuto y mercenario.',
        'relaciones_publicas'=>array(),'recompensa'=>'No aplica (Gremio)','fruta'=>null,
        'ubicacion_publica'=>'Grand Line — Isla Bounty','ocupacion'=>'Líder de Cazarrecompensas','lema'=>'Todo pirata tiene un precio. Yo cobro el tuyo.',
    ),
    'datos_internos' => array('secreto_narrativo'=>'','objetivos_ocultos'=>array(),'conexiones_clave'=>array()),
    'bio' => array('concepto'=>'El cazador supremo','pasado'=>'Fundó el Gremio de Cazadores tras dos décadas retirando piratas del mar.', 'motivacion'=>'Cobrar las mayores recompensas del mundo.'),
);

// ── EJECUCIÓN DEL SEMBRADO E INSERCIÓN BD ──

function seed_master_npc(mysqli $db, array $n) {
    $slug = $n['slug'];
    echo "Processing NPC: {$n['nombre']} ({$slug})...\n";

    $res = $db->query("SELECT pid, es_npc FROM mybb_rol_personajes WHERE slug='" . $db->real_escape_string($slug) . "' LIMIT 1");
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
        'avatar'          => '',
        'icono'           => '',
        'datos'           => json_encode($n['datos'], JSON_UNESCAPED_UNICODE),
        'inventario'      => '{}',
        'economia'        => json_encode(array('berries' => $n['berries']), JSON_UNESCAPED_UNICODE),
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
        'ps_gastados'     => (int)$n['ps'],
        'stats_ganados'   => (int)$n['ps'],
        'pt_disponibles'  => (int)$n['pt_disp'],
        'pt_gastados'     => (int)$n['pt_gas'],
        'isla_actual'     => $n['isla_actual'],
        'lastedit'        => time(),
    );

    if ($is_new) {
        $cols['dateline'] = time();
        $fields = array(); $place = array(); $vals = array(); $types = '';
        foreach ($cols as $k => $v) {
            $fields[] = "`{$k}`"; $place[] = '?'; $vals[] = $v;
            $types .= is_int($v) ? 'i' : 's';
        }
        $sql = "INSERT INTO mybb_rol_personajes (" . implode(',', $fields) . ") VALUES (" . implode(',', $place) . ")";
        $st = $db->prepare($sql);
        if (!$st) { echo "  ERROR prepare: {$db->error}\n"; return; }
        $st->bind_param($types, ...$vals);
        if ($st->execute()) { $pid = $db->insert_id; echo "  INSERT OK. pid={$pid}\n"; }
        else { echo "  ERROR execute: {$st->error}\n"; return; }
        $st->close();
    } else {
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
        if ($st->execute()) { echo "  UPDATE OK. pid={$pid}\n"; }
        else { echo "  ERROR execute: {$st->error}\n"; return; }
        $st->close();
    }

    // 1. Nodos Eternal en rol_pj_eternal
    if (isset($n['datos']['arbol_identidad_nodos_ids'])) {
        $db->query("DELETE FROM mybb_rol_pj_eternal WHERE pid = {$pid}");
        foreach ($n['datos']['arbol_identidad_nodos_ids'] as $nid) {
            $db->query("INSERT INTO mybb_rol_pj_eternal (pid, arbol, nodo_id, dateline) VALUES ({$pid}, '" . $db->real_escape_string($n['datos']['arbol_identidad']) . "', '" . $db->real_escape_string($nid) . "', " . time() . ")");
        }
        foreach ($n['datos']['arbol_arma_nodos_ids'] as $nid) {
            $db->query("INSERT INTO mybb_rol_pj_eternal (pid, arbol, nodo_id, dateline) VALUES ({$pid}, '" . $db->real_escape_string($n['datos']['arbol_arma']) . "', '" . $db->real_escape_string($nid) . "', " . time() . ")");
        }
        echo "  -> 10 Nodos Eternal insertados en rol_pj_eternal.\n";
    }

    // 2. Akuma no Mi en rol_pj_fruta (solo poseedores)
    $db->query("DELETE FROM mybb_rol_pj_fruta WHERE pid = {$pid}");
    if (!empty($n['datos']['fruta_id'])) {
        $fid = (int) $n['datos']['fruta_id'];
        $sec = $db->real_escape_string($n['datos']['fruta_sec'] ?? 'INT');
        $db->query("INSERT INTO mybb_rol_pj_fruta (pid, fruta_id, nivel, cu, pp_gastado, origen, potencia_sec, fecha_despertar, dateline, lastedit) VALUES ({$pid}, {$fid}, 3, 120, 0, 'inicial', '{$sec}', " . time() . ", " . time() . ", " . time() . ")");
        $db->query("UPDATE mybb_rol_akuma SET ocupada_pid = {$pid} WHERE id = {$fid}");
        echo "  -> Akuma no Mi (ID {$fid}) asignada en rol_pj_fruta.\n";
    } else {
        echo "  -> Sin Akuma no Mi (Haki / combate físico / estilo racial).\n";
    }
    echo "\n";
}

require_once __DIR__ . '/_db-config.php';
foreach ($ALL_NPCS as $npc) {
    seed_master_npc($db, $npc);
}

echo "=== SEMBRADO MASTER FINALIZADO EXITOSAMENTE ===\n";
