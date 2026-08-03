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

    // Seeding de Vocaciones en rol_pj_vocaciones
    if ($db->table_exists('rol_pj_vocaciones')) {
        $db->query("DELETE FROM mybb_rol_pj_vocaciones WHERE pid = {$pid}");
        $clase_seed = $n['datos']['clase'] ?? ($n['datos']['identidad'] ?? 'luchador');
        $arma_seed = $n['datos']['arma'] ?? 'guantelete';
        $oficios_seed = json_encode($n['datos']['oficios'] ?? array(), JSON_UNESCAPED_UNICODE);
        $elecciones_seed = json_encode($n['datos']['elecciones'] ?? new stdClass(), JSON_UNESCAPED_UNICODE);
        $arquetipo_seed = $n['datos']['arquetipo_clase'] ?? '';

        $db->query("INSERT INTO mybb_rol_pj_vocaciones (pid, clase, oficios, arma, elecciones, arquetipo_clase, dateline) VALUES ({$pid}, '" . $db->real_escape_string($clase_seed) . "', '" . $db->real_escape_string($oficios_seed) . "', '" . $db->real_escape_string($arma_seed) . "', '" . $db->real_escape_string($elecciones_seed) . "', '" . $db->real_escape_string($arquetipo_seed) . "', " . time() . ")");
    }

    // Seeding de Akuma no Mi en rol_pj_fruta
    if (!empty($n['datos']['fruta_id'])) {
        $db->query("DELETE FROM mybb_rol_pj_fruta WHERE pid = {$pid}");
        $fid = (int) $n['datos']['fruta_id'];
        $sec = $db->real_escape_string($n['datos']['fruta_sec'] ?? 'INT');
        $db->query("INSERT INTO mybb_rol_pj_fruta (pid, fruta_id, nivel, cu, pp_gastado, origen, potencia_sec, fecha_despertar, dateline, lastedit) VALUES ({$pid}, {$fid}, 3, 120, 0, 'inicial', '{$sec}', " . time() . ", " . time() . ", " . time() . ")");
        $db->query("UPDATE mybb_rol_akuma SET ocupada_pid = {$pid} WHERE id = {$fid}");
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
        'arbol_identidad_nodos_ids'=>array('coloso-peso-t1', 'coloso-peso-t2', 'coloso-peso-t3', 'coloso-peso-t4', 'coloso-pinaculo-peso'),
        'arbol_arma_nodos_ids'=>array('cuerpo-impacto-marcial-t1', 'cuerpo-impacto-marcial-t2', 'cuerpo-impacto-marcial-t3', 'cuerpo-impacto-marcial-t4', 'cuerpo-pinaculo-impacto-marcial'),
        'haki'=>array('armadura'=>'avanzado (Pot 23)','observacion'=>'alto (Pot 19)','conquistador'=>'rey (Pot 18)'),
        'fruta_id'=>34,'fruta_slug'=>'fruta.zushi_zushi','fruta_nombre'=>'Zushi Zushi no Mi','fruta_sec'=>'TEM',
        'factor_linaje'=>array(
            'buccaneers' => array('nombre' => 'Voluntad Buccaneer', 'spec' => 'Fuerza y resistencia colosal (+6 RES, -2 CAR)', 'valor' => 0, 'tipo' => 'rasgo_racial'),
            'fruta'      => array('nombre' => 'Zushi Zushi no Mi (Gravedad)', 'spec' => 'Paramecia Tier V. Aplasta con gravedad, invierte superficies, atrae meteoros.', 'valor' => 0, 'tipo' => 'dote_innata'),
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

echo "=== Seed Marina/Rey (Universo A) ===\n";
foreach ($NPCS as $npc) { seed_npc($db, $npc); }
echo "\nHecho.\n";
