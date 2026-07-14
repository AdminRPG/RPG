<?php
/**
 * I-Forge · Seed: Tripulacion Carmesi y Aliados
 *
 * NPCs Mayores completos. Idempotente (INSERT o UPDATE segun slug).
 * Uso: php scripts/seed-crew.php
 */

if (php_sapi_name() !== 'cli') { die("CLI only.\n"); }

require_once __DIR__ . '/_db-config.php';

function seed_npc($db, array $npc) {
    $slug = $npc['slug']; $nombre = $npc['nombre'];
    echo "\n-- {$nombre} --\n";
    $check = $db->query("SELECT pid, es_npc FROM mybb_rol_personajes WHERE slug = '{$db->real_escape_string($slug)}' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ((int)$row['es_npc'] !== 1) { echo "  ERROR: Existe PERSONAJE (no NPC) con slug '{$slug}'. Saltando.\n"; return; }
        $pid = (int)$row['pid']; $is_new = false;
        echo "  Ya existe (pid={$pid}). Actualizando...\n";
    } else { $is_new = true; echo "  Creando nuevo NPC...\n"; }

    $db->begin_transaction();
    try {
        $st = $db->real_escape_string($npc['stats_json']);
        $dl = $db->real_escape_string($npc['datos_legacy']);
        $dp = $db->real_escape_string($npc['datos_publicos']);
        $di = $db->real_escape_string($npc['datos_internos']);
        $df = $db->real_escape_string($npc['desc_fisica']);
        $ff = $db->real_escape_string($npc['from_fisico']);
        $pe = $db->real_escape_string($npc['personalidad']);
        $bi = $db->real_escape_string($npc['bio']);
        $mz = $db->real_escape_string($npc['mundo_zona']);
        $mu = $db->real_escape_string($npc['mundo_ubic']);
        $ma = $db->real_escape_string($npc['mundo_accion']);
        $me = $db->real_escape_string($npc['mundo_estado_np']);
        $lv = $npc['nivel']; $ps = $npc['ps_gastados'];

        if ($is_new) {
            $q = "INSERT INTO mybb_rol_personajes
                (uid, nombre, slug, estado, es_npc, activo, nivel, avatar,
                 stats_json, ps_gastados, stats_ganados,
                 datos, datos_publicos, datos_internos,
                 desc_fisica, from_fisico, personalidad,
                 inventario, economia, bio,
                 mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np,
                 dateline, lastedit)
                VALUES (0, '{$db->real_escape_string($nombre)}', '{$db->real_escape_string($slug)}', 'aprobado', 1, 0, {$lv}, '',
                        '{$st}', {$ps}, {$ps},
                        '{$dl}', '{$dp}', '{$di}',
                        '{$df}', '{$ff}', '{$pe}',
                        '{}', '{\"berries\":50000}', '{$bi}',
                        '{$mz}', '{$mu}', '{$ma}', '{$me}',
                        UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
            $db->query($q); $pid = $db->insert_id;
            echo "  INSERT OK. pid={$pid}\n";
        } else {
            $q = "UPDATE mybb_rol_personajes SET
                stats_json = '{$st}', ps_gastados = {$ps}, stats_ganados = {$ps}, nivel = {$lv},
                datos = '{$dl}', datos_publicos = '{$dp}', datos_internos = '{$di}',
                desc_fisica = '{$df}', from_fisico = '{$ff}', personalidad = '{$pe}',
                bio = '{$bi}',
                mundo_zona = '{$mz}', mundo_ubic = '{$mu}',
                mundo_accion = '{$ma}', mundo_estado_np = '{$me}',
                lastedit = UNIX_TIMESTAMP()
                WHERE pid = {$pid}";
            $db->query($q);
            echo "  UPDATE OK. pid={$pid}\n";
        }
        $db->commit();

        $v = $db->query("SELECT pid, nombre, nivel,
            IF(datos_publicos IS NOT NULL AND datos_publicos != '' AND datos_publicos != 'null', 'SI', 'NO') AS pub,
            IF(datos_internos IS NOT NULL AND datos_internos != '' AND datos_internos != 'null', 'SI', 'NO') AS inter,
            IF(desc_fisica IS NOT NULL AND desc_fisica != '', 'SI', 'NO') AS fisica,
            IF(personalidad IS NOT NULL AND personalidad != '', 'SI', 'NO') AS perso,
            IF(bio IS NOT NULL AND bio != '' AND bio != 'null', 'SI', 'NO') AS bio_ok,
            CHAR_LENGTH(datos_publicos) AS pub_chars, CHAR_LENGTH(datos_internos) AS inter_chars,
            CHAR_LENGTH(desc_fisica) AS fisica_chars, CHAR_LENGTH(personalidad) AS perso_chars,
            CHAR_LENGTH(bio) AS bio_chars
            FROM mybb_rol_personajes WHERE pid = {$pid}");
        $row = $v->fetch_assoc();
        echo "  -- VERIFICACION --\n  Nombre:{$row['nombre']} Nivel:{$row['nivel']}\n";
        echo "  Pub:{$row['pub']}({$row['pub_chars']}c) Interno:{$row['inter']}({$row['inter_chars']}c)\n";
        echo "  Fisica:{$row['fisica']}({$row['fisica_chars']}c) Perso:{$row['perso']}({$row['perso_chars']}c) Bio:{$row['bio_ok']}({$row['bio_chars']}c)\n";
        $w = [];
        if ($row['pub_chars'] < 500) $w[] = 'ADVERTENCIA: datos_publicos corto';
        if ($row['inter_chars'] < 300) $w[] = 'ADVERTENCIA: datos_internos corto';
        if ($row['fisica_chars'] < 200) $w[] = 'ADVERTENCIA: desc_fisica corta';
        if ($row['perso_chars'] < 200) $w[] = 'ADVERTENCIA: personalidad corta';
        if ($row['bio_chars'] < 500) $w[] = 'ADVERTENCIA: bio corto';
        if ($row['pub'] === 'NO') $w[] = 'ERROR: datos_publicos vacio';
        if ($row['inter'] === 'NO') $w[] = 'ERROR: datos_internos vacio';
        if ($row['fisica'] === 'NO') $w[] = 'ERROR: desc_fisica vacia';
        if ($row['perso'] === 'NO') $w[] = 'ERROR: personalidad vacia';
        if ($w) { echo "  " . implode("\n  ", $w) . "\n"; } else { echo "  TODO CORRECTO.\n"; }
    } catch (Exception $e) {
        $db->rollback();
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

echo "=== SEED CREW: Tripulacion Carmesi y Aliados ===\n";

// ================================================================
// JACK "EL INMORTAL" -- Vice-Capitan de los Piratas Carmesi
// ================================================================

$sj = '{"FUE":92,"DES":68,"VIG":100,"AGI":65,"INT":45,"ING":42,"CON":68,"PER":58,"CAR":58,"CTR":22,"VOL":100,"SEN":80}';

seed_npc($db, [
    'slug' => 'jack-el-inmortal',
    'nombre' => "Jack 'El Inmortal'",
    'nivel' => 80, 'ps_gastados' => 800,
    'stats_json' => $sj,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"El Inmortal","edad":"40","genero":"Masculino","stats_efectivas":{"FUE":92,"DES":68,"VIG":100,"AGI":65,"INT":45,"ING":42,"CON":68,"PER":58,"CAR":58,"CTR":22,"VOL":100,"SEN":80},"virtudes":[],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":8,"faccion":"pirata","concepto":"Vice-capitan de los Piratas Carmesi. Cubierto de cicatrices. Se niega a morir hasta rescatar a Isabella. El hombre mas leal del mundo."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/jack_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/jack_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/jack_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/jack_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/jack_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Isla Sabaody -- Guarida Secreta de los Carmesi',
    'mundo_accion' => 'Movilizando lo que queda de la tripulacion para rescatar a Isabella. Enviando senales a aliados. Reparando barcos. Sin dormir. Sin descanso.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// DRA. AURELIAN LIRA -- Medica de los Piratas Carmesi
// ================================================================

$sa = '{"FUE":18,"DES":35,"VIG":42,"AGI":22,"INT":95,"ING":60,"CON":88,"PER":82,"CAR":42,"CTR":48,"VOL":68,"SEN":50}';

seed_npc($db, [
    'slug' => 'aurelian-lira',
    'nombre' => 'Dra. Aurelian Lira',
    'nivel' => 65, 'ps_gastados' => 650,
    'stats_json' => $sa,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"La Medica Cinica","edad":"34","genero":"Femenino","stats_efectivas":{"FUE":18,"DES":35,"VIG":42,"AGI":22,"INT":95,"ING":60,"CON":88,"PER":82,"CAR":42,"CTR":48,"VOL":68,"SEN":50},"virtudes":[],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":6,"faccion":"pirata","concepto":"Ex-Tenryubito de la Familia Aurelian. Huyo de Mary Geoise y se unio a los Carmesi como medica. Brillante, cinica y la unica que puede hacer callar a Isabella."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/aurelian_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/aurelian_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/aurelian_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/aurelian_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/aurelian_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Isla Sabaody -- Clinica Clandestina',
    'mundo_accion' => 'Atendiendo heridos de los Carmesi. Preparando suministros medicos para el rescate. Obligando a Jack a dormir. Manteniendo la clinica operativa bajo el radar de la Marina.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// COMANDANTE IGNIS "LLAMA DEL SUR" -- Ejercito Revolucionario
// ================================================================

$si = '{"FUE":86,"DES":65,"VIG":75,"AGI":68,"INT":52,"ING":38,"CON":60,"PER":55,"CAR":95,"CTR":35,"VOL":95,"SEN":56}';

seed_npc($db, [
    'slug' => 'ignis-llama-del-sur',
    'nombre' => "Ignis 'Llama del Sur'",
    'nivel' => 78, 'ps_gastados' => 780,
    'stats_json' => $si,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"Llama del Sur","edad":"36","genero":"Masculino","stats_efectivas":{"FUE":86,"DES":65,"VIG":75,"AGI":68,"INT":52,"ING":38,"CON":60,"PER":55,"CAR":95,"CTR":35,"VOL":95,"SEN":56},"virtudes":[{"nombre":"Netsu Netsu no Mi","coste":0,"spec":"Paramecia. Generacion y control de calor extremo. Puede derretir acero al contacto."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":8,"faccion":"revolucionario","concepto":"Comandante del Ejercito Revolucionario. Paramecia del calor. Planea infiltrarse en Marineford para asesinar Nobles Mundiales durante la ejecucion."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/ignis_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/ignis_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/ignis_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/ignis_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/ignis_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Isla Baltigo -- Base del Ejercito Revolucionario',
    'mundo_accion' => 'Planificando infiltracion en Marineford. Reuniendo inteligencia sobre protocolos VIP. Buscando voluntarios para mision suicida contra los Tenryubitos.',
    'mundo_estado_np' => 'Activo',
]);

echo "\n=== FIN: 3 NPCs de la Tripulacion Carmesi y Aliados procesados ===\n";
