<?php
/**
 * One Piece: Eternal · Seed: Los Cuatro Almirantes de la Marina — Era 4
 *
 * NPCs Mayores completos siguiendo docs/guia-npcs-mayores-completa.md.
 * Idempotente (INSERT o UPDATE segun slug).
 * Uso: php scripts/seed-marines.php
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

echo "=== SEED MARINES: Los Cuatro Almirantes de la Marina ===\n";

// ================================================================
// ALMIRANTE DE FLOTA VALYRIA — La Mejor Espadachina del Mundo
// ================================================================

$vs = '{"FUE":70,"DES":100,"VIG":78,"AGI":88,"INT":98,"ING":72,"CON":82,"PER":85,"CAR":65,"CTR":58,"VOL":100,"SEN":85}';

seed_npc($db, [
    'slug' => 'valyria-almirante-de-flota',
    'nombre' => 'Valyria',
    'nivel' => 99, 'ps_gastados' => 990,
    'stats_json' => $vs,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"Ojo de Plata","edad":"43","genero":"Femenino","stats_efectivas":{"FUE":70,"DES":100,"VIG":78,"AGI":88,"INT":98,"ING":72,"CON":82,"PER":85,"CAR":65,"CTR":58,"VOL":100,"SEN":85},"virtudes":[{"nombre":"Getsumei (月明, Luz de Luna)","coste":0,"spec":"Odachi legendaria forjada con acero de meteorito. 1.80m de hoja. Corta lo material e inmaterial."},{"nombre":"Kenjutsu Trascendente","coste":0,"spec":"Estilo propio sin escuela. Velocidad de desenfunde sobrehumana. Cortes de energia con Haki Avanzado."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":12,"faccion":"marine","concepto":"Hermana mayor de Isabella D. Vega. Mejor espadachina del mundo. Almirante de Flota. Frialdad y precision letal."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/valyria_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/valyria_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/valyria_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/valyria_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/valyria_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Marineford -- Cuartel General de la Marina',
    'mundo_accion' => 'Coordinando el despliegue de fuerzas para la ejecucion. Supervisando defensas de Marineford. Descendiendo cada noche a la celda de Isabella. Contacto cifrado con espias infiltrados entre los Yonko.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// ALMIRANTE KEN "DRAGON AZUL" -- Justicia Heroica
// ================================================================

$ks = '{"FUE":75,"DES":95,"VIG":80,"AGI":100,"INT":65,"ING":60,"CON":68,"PER":75,"CAR":70,"CTR":48,"VOL":95,"SEN":74}';

seed_npc($db, [
    'slug' => 'ken-dragon-azul',
    'nombre' => "Ken 'Dragon Azul'",
    'nivel' => 90, 'ps_gastados' => 900,
    'stats_json' => $ks,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"Dragon Azul","edad":"51","genero":"Masculino","stats_efectivas":{"FUE":75,"DES":95,"VIG":80,"AGI":100,"INT":65,"ING":60,"CON":68,"PER":75,"CAR":70,"CTR":48,"VOL":95,"SEN":74},"virtudes":[{"nombre":"Karate Supersónico","coste":0,"spec":"Patadas que rompen la barrera del sonido. Ondas de choque cortantes. Alcance y poder devastadores."},{"nombre":"Haki de Armadura Avanzado","coste":0,"spec":"Imbuye piernas en negro obsidiana con reflejos azules. Emision de energia sin contacto."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"marine","concepto":"Karateka humano con coleta azul. Almirante de Justicia Heroica. Sus patadas supersónicas cortan el aire. Protege a los inocentes por encima de castigar a culpables."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/ken_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/ken_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/ken_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/ken_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/ken_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Marineford -- Torre del Dragon',
    'mundo_accion' => 'Supervisando preparativos de defensa civil y rutas de evacuacion. Entrenando tres escuadrones de elite en respuesta rapida. Coordinando con Flint la cobertura de francotiradores para proteccion de civiles.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// ALMIRANTE FLINT "BALAS DE PLATA" -- Justicia Perezosa
// ================================================================

$fs = '{"FUE":55,"DES":78,"VIG":62,"AGI":82,"INT":72,"ING":92,"CON":65,"PER":100,"CAR":68,"CTR":42,"VOL":95,"SEN":78}';

seed_npc($db, [
    'slug' => 'flint-balas-de-plata',
    'nombre' => "Flint 'Balas de Plata'",
    'nivel' => 89, 'ps_gastados' => 890,
    'stats_json' => $fs,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"Balas de Plata","edad":"47","genero":"Masculino","stats_efectivas":{"FUE":55,"DES":78,"VIG":62,"AGI":82,"INT":72,"ING":92,"CON":65,"PER":100,"CAR":68,"CTR":42,"VOL":95,"SEN":78},"virtudes":[{"nombre":"Plata I y Plata II","coste":0,"spec":"Dos revolveres de pedernal modificados. 8 balas cada uno. Cañones estriados. Precision milimetrica."},{"nombre":"Kenbunshoku Haki Supremo","coste":0,"spec":"Mejor Haki de Observacion del mundo. Rastrea presencias a kilometros. Anticipa movimientos. Lee estados emocionales."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"marine","concepto":"Bribon carismatico fumador empedernido. Genio tactico y francotirador definitivo. Justicia Perezosa: el minimo esfuerzo necesario es la maxima eficiencia."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/flint_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/flint_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/flint_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/flint_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/flint_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Marineford -- Barracon del Francotirador',
    'mundo_accion' => 'Supervisando defensas perimetrales desde su barracon. Posicionando francotiradores en 47 puntos estrategicos. Analisis balisticos entre siesta y siesta. Preparando cafe para 6 horas de guardia.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// ALMIRANTE NEREIDA "EL ABISMO" -- Justicia Absoluta
// ================================================================

$ns = '{"FUE":100,"DES":75,"VIG":100,"AGI":72,"INT":70,"ING":65,"CON":75,"PER":72,"CAR":55,"CTR":50,"VOL":100,"SEN":74}';

seed_npc($db, [
    'slug' => 'nereida-el-abismo',
    'nombre' => "Nereida 'El Abismo'",
    'nivel' => 91, 'ps_gastados' => 910,
    'stats_json' => $ns,
    'datos_legacy' => '{"raza_principal":"sirena","hibrido":false,"apodo":"El Abismo","edad":"48","genero":"Femenino","stats_efectivas":{"FUE":100,"DES":75,"VIG":100,"AGI":72,"INT":70,"ING":65,"CON":75,"PER":72,"CAR":55,"CTR":50,"VOL":100,"SEN":74},"virtudes":[{"nombre":"Gyojin Karate Catastrofico","coste":0,"spec":"Arte marcial acuatica. Manipula agua como extension corporal. Genera tsunamis y proyectiles de agua comprimida."},{"nombre":"Comunicacion Marina","coste":0,"spec":"Habla con toda la vida marina. Espionaje subacuatico perfecto. Control de corrientes y mareas."}],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":10,"faccion":"marine","concepto":"Sirena (Ningyo) de la Isla Gyojin. Almirante de Justicia Absoluta. Gyojin Karate a nivel catastrofico. Odia a los piratas con furia abisal. Protege a su pueblo por encima de todo."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/nereida_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/nereida_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/nereida_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/nereida_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/nereida_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Marineford -- Acuario de Contencion Maxima',
    'mundo_accion' => 'Patrullando el perimetro maritimo en ciclos de 8 horas. Detectando embarcaciones no autorizadas a 50 km. Alternando entre su acuario y aguas abiertas. Preparada para hundir cualquier flota pirata.',
    'mundo_estado_np' => 'Activo',
]);

echo "\n=== FIN: Los 4 Almirantes procesados ===\n";
