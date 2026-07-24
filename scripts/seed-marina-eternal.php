<?php
/**
 * One Piece: Eternal · Seed: Núcleo del roster (Universo A)
 *
 * NPCs Mayores en el sistema CANÓNICO (8 stats, Nivel 50, techo 99 base+comprado,
 * linaje suma-0). Idempotente: INSERT o UPDATE según slug.
 *
 * Premisa: el Rey Pirata (Rolf D. Basterra) ha sido capturado; su madre, la
 * Almirante de Flota "El Puño de la Marina" (Sigrun D. Basterra), debe presidir
 * su ejecución. Almirantes: Escarcha, La Cazadora, El Martillo del Abismo.
 *
 * Uso: php scripts/seed-marina-eternal.php
 */

if (php_sapi_name() !== 'cli') { die("CLI only.\n"); }
require_once __DIR__ . '/_db-config.php';

function seed_npc(mysqli $db, array $n) {
    $slug = $n['slug'];
    echo "\n-- {$n['nombre']} --\n";
    $res = $db->query("SELECT pid, es_npc FROM mybb_rol_personajes WHERE slug='" . $db->real_escape_string($slug) . "' LIMIT 1");
    $is_new = true; $pid = 0;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ((int)$row['es_npc'] !== 1) { echo "  SKIP: existe un PERSONAJE (no NPC) con slug '{$slug}'.\n"; return; }
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
        if ($st->execute()) { echo "  INSERT OK. pid=" . $db->insert_id . "\n"; }
        else { echo "  ERROR execute: {$st->error}\n"; }
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
        else { echo "  ERROR execute: {$st->error}\n"; }
        $st->close();
    }
}

$NPCS = array();

// ── 1. Almirante de Flota — "El Puño de la Marina" ──
$NPCS[] = array(
    'slug' => 'almirante-flota-sigrun-basterra', 'nombre' => 'Sigrun D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Almirante de Flota', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford — Cuartel General de la Marina',
    'mundo_accion' => 'Prepara el dispositivo de seguridad para la ejecución pública de su hijo, el Rey Pirata.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 2275, 'en_max' => 1460, 'pa' => 14, 'ps' => 536, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>99,'RES'=>98,'AGI'=>45,'INT'=>30,'PER'=>70,'TEM'=>78,'VOL'=>88,'CAR'=>60),
    'desc_fisica' => 'Buccaneer de 2,4 m, hombros de montaña y puños del tamaño de un yunque. Cabellera cana trenzada, uniforme blanco de Almirante de Flota con el kanji de Justicia a la espalda. Nudillos cubiertos de cicatrices de Haki endurecido.',
    'personalidad' => 'Inamovible como una montaña. Justa hasta el dolor. Adora a su hijo y jamás lo ha renegado; la ejecución es su prueba de fe definitiva. Lidera con el ejemplo, no con el grito.',
    'datos' => array(
        'raza_principal'=>'buccaneers','hibrido'=>false,'apodo'=>'El Puño de la Marina','edad'=>'58','genero'=>'Femenino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Heroica',
        'identidad'=>'coloso','arbol_identidad'=>'identidad-coloso','arbol_arma'=>'arma-cuerpo','arma'=>'punio_hierro',
        'arbol_identidad_nodos'=>array('Co-a1 Fuerza que Crece','Co-a2 Descarga de Peso','Co-a3 Mole Desatada','Co-a4 Golpe de Ejecución','★ Peso Absoluto'),
        'arbol_arma_nodos'=>array('Golpe Concentrado','Impacto Interior','Punto Vital','Golpe Prohibido','★ Puño de Dios'),
        'haki'=>array('armadura'=>'avanzado (Pot 23)','observacion'=>'alto (Pot 19)','conquistador'=>'rey (Pot 18)'),
        'fruta_slug'=>'fruta.zushi_zushi','fruta_nombre'=>'Zushi Zushi no Mi',
        'linaje'=>array('nombre'=>'buccaneers','rasgo'=>'Voluntad que no se Quiebra','mods'=>array('RES'=>6,'CAR'=>-2)),
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

// ── 2. Rey Pirata (capturado) — hijo de la Almirante ──
$NPCS[] = array(
    'slug' => 'rey-pirata-rolf-basterra', 'nombre' => 'Rolf D. Basterra',
    'rango' => 'M+', 'rango_faccion' => 'Rey de los Piratas', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Impel Down — Nivel 6 (traslado a Marineford)',
    'mundo_accion' => 'Encadenado en kairoseki, aguarda su ejecución pública con una sonrisa.',
    'mundo_estado_np' => 'Capturado', 'isla_actual' => 'impel_down',
    'pv_max' => 1910, 'en_max' => 1405, 'pa' => 22, 'ps' => 608, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>82,'RES'=>70,'AGI'=>95,'INT'=>55,'PER'=>90,'TEM'=>60,'VOL'=>96,'CAR'=>88),
    'desc_fisica' => 'Humano de porte regio, sonrisa perpetua y mirada libre. Cicatrices de mil duelos. Viste harapos de prisión sobre un cuerpo que aún impone. Una vieja espada legendaria confiscada le fue arrebatada.',
    'personalidad' => 'Libre hasta el tuétano. Carismático, temerario, incapaz de arrodillarse. Sabe que su muerte encenderá el mundo y le divierte.',
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'El Rey Libre','edad'=>'28','genero'=>'Masculino',
        'faccion'=>'pirata','arquetipo'=>'La Libertad Absoluta',
        'identidad'=>'duelista','arbol_identidad'=>'identidad-duelista','arbol_arma'=>'arma-filo','arma'=>'espada',
        'arbol_identidad_nodos'=>array('Du-b1 Ojo Quirúrgico','Du-b2 Estocada Precisa','Du-b3 Foco Absoluto','Du-b4 Brecha Perpetua','★ Punto Mortal'),
        'arbol_arma_nodos'=>array('F-a1 Corte Profundo','F-a2 Sentencia de Sangre','F-a3 Sangrado Implacable','F-a4 Remate de Ejecución','★ Mil Cortes'),
        'haki'=>array('armadura'=>'avanzado (Pot 20)','observacion'=>'presciencia (Pot 23)','conquistador'=>'rey (Pot 23)'),
        'fruta_slug'=>null,'fruta_nombre'=>null,
        'linaje'=>array('nombre'=>'humanos','rasgo'=>'Improvisar y Resistir','mods'=>array()),
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

// ── 3. Almirante "Escarcha" ──
$NPCS[] = array(
    'slug' => 'almirante-halvar-escarcha', 'nombre' => 'Halvar',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Blinda el perímetro helado de Marineford para la ejecución.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 1920, 'en_max' => 1630, 'pa' => 16, 'ps' => 525, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>60,'RES'=>82,'AGI'=>55,'INT'=>50,'PER'=>78,'TEM'=>85,'VOL'=>88,'CAR'=>55),
    'desc_fisica' => 'Humano alto y pálido, mirada glacial, escarcha permanente en el aliento. Uniforme blanco con capa de piel. Empuña un tridente de combate.',
    'personalidad' => 'Frío, metódico, implacable con el crimen. La ley por encima de todo.',
    'datos' => array(
        'raza_principal'=>'humanos','hibrido'=>false,'apodo'=>'Escarcha','edad'=>'49','genero'=>'Masculino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Absoluta',
        'identidad'=>'centinela','arbol_identidad'=>'identidad-centinela','arbol_arma'=>'arma-alcance','arma'=>'lanza',
        'arbol_identidad_nodos'=>array('Centinela T1 Bastión','T2 Bastión','T3 Bastión','T4 Bastión','★ Bastión'),
        'arbol_arma_nodos'=>array('Alcance T1 Control','T2 Control','T3 Control','T4 Control','★ Control'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'no'),
        'fruta_slug'=>'fruta.hie_hie','fruta_nombre'=>'Hie Hie no Mi',
        'linaje'=>array('nombre'=>'humanos','rasgo'=>'Improvisar y Resistir','mods'=>array()),
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

// ── 4. Almirante "La Cazadora" ──
$NPCS[] = array(
    'slug' => 'almirante-ysolde-cazadora', 'nombre' => 'Ysolde',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Rastrea infiltrados piratas antes de la ejecución.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 1820, 'en_max' => 1355, 'pa' => 22, 'ps' => 529, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>74,'RES'=>65,'AGI'=>92,'INT'=>55,'PER'=>92,'TEM'=>55,'VOL'=>78,'CAR'=>50),
    'desc_fisica' => 'Mink loba de pelaje gris plata, 1,9 m, ojos ámbar. Uniforme ligero de francotiradora, rifle de precisión a la espalda. Bajo la luna llena, Sulong.',
    'personalidad' => 'Pragmática y paciente. El fin justifica el disparo. Poco dada a la ceremonia.',
    'datos' => array(
        'raza_principal'=>'minks','hibrido'=>false,'apodo'=>'La Cazadora','edad'=>'37','genero'=>'Femenino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Pragmática',
        'identidad'=>'cazador','arbol_identidad'=>'identidad-cazador','arbol_arma'=>'arma-distancia','arma'=>'arma_fuego',
        'arbol_identidad_nodos'=>array('Cazador T1 Marcaje','T2 Marcaje','T3 Marcaje','T4 Marcaje','★ Marcaje'),
        'arbol_arma_nodos'=>array('D-a1 Ojo de Halcón','T2 Precisión','T3 Precisión','T4 Precisión','★ Precisión'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'presciencia','conquistador'=>'no'),
        'fruta_slug'=>null,'fruta_nombre'=>null,
        'linaje'=>array('nombre'=>'minks','rasgo'=>'Latido Salvaje + Sulong','mods'=>array('AGI'=>4,'FUE'=>4,'VOL'=>-4)),
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

// ── 5. Almirante "El Martillo del Abismo" ──
$NPCS[] = array(
    'slug' => 'almirante-draven-martillo', 'nombre' => 'Draven',
    'rango' => 'M', 'rango_faccion' => 'Almirante', 'nivel' => 50,
    'berries' => 0, 'from_fisico' => 'Adaptado por el staff',
    'mundo_zona' => 'paraiso', 'mundo_ubic' => 'Marineford',
    'mundo_accion' => 'Refuerza las murallas y la bahía de Marineford.',
    'mundo_estado_np' => 'Activo', 'isla_actual' => 'marineford',
    'pv_max' => 2160, 'en_max' => 1410, 'pa' => 14, 'ps' => 502, 'pt_disp' => 40, 'pt_gas' => 10,
    'stats' => array('FUE'=>96,'RES'=>88,'AGI'=>45,'INT'=>40,'PER'=>60,'TEM'=>68,'VOL'=>82,'CAR'=>55),
    'desc_fisica' => 'Gyojin tiburón de 3 m, piel gris acorazada, cicatrices de mordiscos. Empuña un kanabō de hierro. Bajo el agua es imparable.',
    'personalidad' => 'Guerrero de honor brutal. El fuerte protege; el débil calla. Directo y sin doblez.',
    'datos' => array(
        'raza_principal'=>'gyojins','hibrido'=>false,'apodo'=>'El Martillo del Abismo','edad'=>'44','genero'=>'Masculino',
        'faccion'=>'marine','arquetipo'=>'La Justicia Guerrera',
        'identidad'=>'verdugo','arbol_identidad'=>'identidad-verdugo','arbol_arma'=>'arma-contundente','arma'=>'maza',
        'arbol_identidad_nodos'=>array('Verdugo T1 Sentencia','T2 Sentencia','T3 Sentencia','T4 Sentencia','★ Sentencia'),
        'arbol_arma_nodos'=>array('C-a1 Golpe Demoledor','T2 Impacto','T3 Impacto','T4 Impacto','★ Impacto'),
        'haki'=>array('armadura'=>'avanzado','observacion'=>'alto','conquistador'=>'no'),
        'fruta_slug'=>null,'fruta_nombre'=>null,
        'linaje'=>array('nombre'=>'gyojins','rasgo'=>'Piel de Abismo + Hijo del Mar','mods'=>array('FUE'=>6,'PER'=>-2)),
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

echo "=== Seed Marina/Rey (Universo A) ===\n";
foreach ($NPCS as $npc) { seed_npc($db, $npc); }
echo "\nHecho.\n";
