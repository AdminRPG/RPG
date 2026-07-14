<?php
/**
 * I-Forge · Seed: Los Cuatro Yonko de la Era 4
 *
 * NPCs Mayores completos. Idempotente (INSERT o UPDATE segun slug).
 * Uso: php scripts/seed-yonko.php
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

echo "=== SEED YONKO: Los Cuatro Emperadores ===\n";

// ================================================================
// SHURA "DIOS DE LA IRA" -- Yonko Belicista
// ================================================================

$s = '{"FUE":101,"DES":85,"VIG":100,"AGI":80,"INT":60,"ING":58,"CON":72,"PER":80,"CAR":84,"CTR":38,"VOL":100,"SEN":78}';

seed_npc($db, [
    'slug' => 'shura-dios-de-la-ira',
    'nombre' => "Shura 'Dios de la Ira'",
    'nivel' => 95, 'ps_gastados' => 950,
    'stats_json' => $s,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":true,"raza_secundaria":"oni","apodo":"Dios de la Ira","edad":"47","genero":"Femenino","stats_efectivas":{"FUE":101,"DES":85,"VIG":100,"AGI":80,"INT":60,"ING":58,"CON":72,"PER":80,"CAR":84,"CTR":38,"VOL":100,"SEN":78},"virtudes":[{"nombre":"Hito Hito no Mi, Modelo: Buda","coste":0,"spec":"Zoan Mitica. Transformacion en deidad dorada. Ondas de choque con las palmas."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":12,"faccion":"pirata","concepto":"Oni belica con Zoan Mitica del Buda. Alterna furia demoniaca y calma divina."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/shura_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/shura_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/shura_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/shura_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/shura_personalidad.txt'),
    'mundo_zona' => 'new-world',
    'mundo_ubic' => 'Isla Onigashima -- Templo del Buda Demoníaco',
    'mundo_accion' => 'Supervisando preparativos de guerra. Reuniendo a sus generales. Observando los movimientos de los otros Yonko a traves de su red de espias infiltrados en Marineford, Zou y la Fortaleza de Chatarra. Planificando el asalto final al trono.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// SEKHMET "REINA LEONA" -- Yonko Carismatica
// ================================================================

$s2 = '{"FUE":75,"DES":82,"VIG":78,"AGI":95,"INT":68,"ING":60,"CON":72,"PER":78,"CAR":100,"CTR":58,"VOL":98,"SEN":75}';

seed_npc($db, [
    'slug' => 'sekhmet-reina-leona',
    'nombre' => "Sekhmet 'Reina Leona'",
    'nivel' => 94, 'ps_gastados' => 940,
    'stats_json' => $s2,
    'datos_legacy' => '{"raza_principal":"mink","hibrido":false,"apodo":"Reina Leona","edad":"44","genero":"Femenino","stats_efectivas":{"FUE":75,"DES":82,"VIG":78,"AGI":95,"INT":68,"ING":60,"CON":72,"PER":78,"CAR":100,"CTR":58,"VOL":98,"SEN":75},"virtudes":[{"nombre":"Electro","coste":0,"spec":"Electricidad innata Mink. Descargas ofensivas y defensivas."},{"nombre":"Transformacion Sulong","coste":0,"spec":"Bajo luna llena. Stats x2. Riesgo de perdida de control."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"pirata","concepto":"Mink Leona majestuosa. Yonko carismatica. Electro y Sulong devastadores."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/sekhmet_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/sekhmet_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/sekhmet_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/sekhmet_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/sekhmet_personalidad.txt'),
    'mundo_zona' => 'new-world',
    'mundo_ubic' => 'Zou -- Trono de la Reina Leona',
    'mundo_accion' => 'Presidiendo el consejo de ancianos Mink. Deliberando sobre la ejecucion de Isabella. Cada noche contempla la luna desde la cima del arbol Zunesha, buscando en su luz la decision correcta: intervenir o permanecer neutral.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// EZEKIEL "EL ARCANGEL" -- Yonko Enigmatico
// ================================================================

$s3 = '{"FUE":52,"DES":88,"VIG":62,"AGI":92,"INT":98,"ING":88,"CON":72,"PER":100,"CAR":65,"CTR":55,"VOL":80,"SEN":90}';

seed_npc($db, [
    'slug' => 'ezekiel-el-arcangel',
    'nombre' => "Ezekiel 'El Arcangel'",
    'nivel' => 93, 'ps_gastados' => 930,
    'stats_json' => $s3,
    'datos_legacy' => '{"raza_principal":"lunarian","hibrido":true,"raza_secundaria":"skypiean","apodo":"El Arcangel","edad":"39","genero":"Masculino","stats_efectivas":{"FUE":52,"DES":88,"VIG":62,"AGI":92,"INT":98,"ING":88,"CON":72,"PER":100,"CAR":65,"CTR":55,"VOL":80,"SEN":90},"virtudes":[{"nombre":"Llamas Eternas Lunarian","coste":0,"spec":"Fuego perpetuo en hombros y espalda. Resistencia sobrehumana."},{"nombre":"Alas Skypiean","coste":0,"spec":"Vuelo completo. Maniobrabilidad aerea superior."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"pirata","concepto":"Hibrido Skypiean/Lunarian. Angelico francotirador que caza desde los cielos con rifle de Dials."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/ezekiel_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/ezekiel_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/ezekiel_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/ezekiel_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/ezekiel_personalidad.txt'),
    'mundo_zona' => 'new-world',
    'mundo_ubic' => 'Red Line -- Santuario Flotante de Ark',
    'mundo_accion' => 'Monitoreando el mundo desde la plataforma de observacion del Santuario de Ark. Su red de Dials de vigilancia rastrea los movimientos de los otros Yonko, la Marina y los aliados de Isabella. Vigilando a Balgor con especial atencion. Esperando una senal.',
    'mundo_estado_np' => 'Oculto',
]);


// ================================================================
// BALGOR "TITAN DE CHATARRA" -- Yonko Traicionero
// ================================================================

$s4 = '{"FUE":100,"DES":78,"VIG":100,"AGI":70,"INT":85,"ING":95,"CON":88,"PER":75,"CAR":35,"CTR":52,"VOL":88,"SEN":65}';

seed_npc($db, [
    'slug' => 'balgor-titan-de-chatarra',
    'nombre' => "Balgor 'Titan de Chatarra'",
    'nivel' => 92, 'ps_gastados' => 920,
    'stats_json' => $s4,
    'datos_legacy' => '{"raza_principal":"gigante","hibrido":false,"sub_opcion_racial":"ancestral","apodo":"Titan de Chatarra","edad":"72","genero":"Masculino","stats_efectivas":{"FUE":100,"DES":78,"VIG":100,"AGI":70,"INT":85,"ING":95,"CON":88,"PER":75,"CAR":35,"CTR":52,"VOL":88,"SEN":65},"virtudes":[{"nombre":"Gasha Gasha no Mi","coste":0,"spec":"Paramecia. Ensambla objetos mecanicos con su cuerpo o entre si. Asimilacion ilimitada."},{"nombre":"Fuerza Gigante Ancestral","coste":0,"spec":"Potencia colosal propia de los gigantes de Elbaf."}],"defectos":[{"nombre":"Marca del Traidor","coste":0,"spec":"Personajes con honor >50 desconfian o lo consideran enemigo. -30 en tiradas de Carisma con honorables."}],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"pirata","concepto":"Gigante cyborg de Elbaf. Usa Gasha Gasha no Mi para asimilar flotas. Se convierte en Mecha colosal."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/balgor_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/balgor_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/balgor_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/balgor_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de I-Forge',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/balgor_personalidad.txt'),
    'mundo_zona' => 'new-world',
    'mundo_ubic' => 'Nuevo Mundo -- La Fortaleza de Chatarra',
    'mundo_accion' => 'Supervisando la construccion del Mecha-Coloso (78%). Las fabricas producen armamento sin descanso. Mecha-constructos vigilan Marineford.',
    'mundo_estado_np' => 'Activo',
]);

echo "\n=== FIN: Los 4 Yonko procesados ===\n";

