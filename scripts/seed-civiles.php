<?php
/**
 * One Piece: Eternal · Seed: NPCs Civiles y Neutros de la Era 4
 *
 * Tres NPCs secundarios con roles clave durante el arco de Marineford.
 * Idempotente (INSERT o UPDATE segun slug).
 * Uso: php scripts/seed-civiles.php
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

echo "=== SEED CIVILES: NPCs Secundarios de la Era 4 ===\n";

// ================================================================
// "CARA DE MONEDA" GILS -- Broker del Mercado Negro, Nivel 40
// ================================================================

$s_gils = '{"FUE":14,"DES":26,"VIG":16,"AGI":30,"INT":60,"ING":56,"CON":33,"PER":50,"CAR":38,"CTR":25,"VOL":34,"SEN":28}';

seed_npc($db, [
    'slug' => 'cara-de-moneda-gils',
    'nombre' => "\"Cara de Moneda\" Gils",
    'nivel' => 40, 'ps_gastados' => 400,
    'stats_json' => $s_gils,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"Cara de Moneda","edad":"52","genero":"Masculino","stats_efectivas":{"FUE":14,"DES":26,"VIG":16,"AGI":30,"INT":60,"ING":56,"CON":33,"PER":50,"CAR":38,"CTR":25,"VOL":34,"SEN":28},"virtudes":[],"defectos":[],"pc_gastado":0,"pc_devuelto":0,"pc_balance":0,"faccion":"civil","concepto":"Corredor del mercado negro bajo proteccion de Velvet. Vende armas a ambos bandos sin lealtades. Siempre con una moneda girando entre sus dedos."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/gils_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/gils_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/gils_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/gils_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/gils_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Archipielago Sabaody -- Mercado Negro, Grove 23',
    'mundo_accion' => 'Triangulando ventas de armas a todos los bandos. Marineria, piratas y revolucionarios compran sin saberlo del mismo proveedor. Maximo beneficio en visperas de la guerra.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// "PERRO RABIOSO" VARG -- Cazarrecompensas Cyborg, Nivel 60
// ================================================================

$s_varg = '{"FUE":42,"DES":70,"VIG":55,"AGI":75,"INT":38,"ING":46,"CON":40,"PER":72,"CAR":12,"CTR":28,"VOL":50,"SEN":62}';

seed_npc($db, [
    'slug' => 'perro-rabioso-varg',
    'nombre' => "\"Perro Rabioso\" Varg",
    'nivel' => 60, 'ps_gastados' => 600,
    'stats_json' => $s_varg,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":true,"raza_secundaria":"cyborg","apodo":"Perro Rabioso","edad":"41","genero":"Masculino","stats_efectivas":{"FUE":42,"DES":70,"VIG":55,"AGI":75,"INT":38,"ING":46,"CON":40,"PER":72,"CAR":12,"CTR":28,"VOL":50,"SEN":62},"virtudes":[{"nombre":"Implantes Ciberneticos","coste":0,"spec":"Brazo hidraulico, ojo optico con zoom/HUD, placas subdermicas. Respiracion asistida."}],"defectos":[{"nombre":"Susceptibilidad EMP","coste":0,"spec":"Ataques electromagneticos o Frutas que afecten metales causan daño critico a sistemas ciberneticos."}],"pc_gastado":0,"pc_devuelto":0,"pc_balance":0,"faccion":"cazarrecompensas","concepto":"Ex-marine convertido en cyborg contra su voluntad. Caza piratas debilitados cerca de Marineford por dinero. Sin moral, sin piedad, sin pasado."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/varg_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/varg_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/varg_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/varg_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/varg_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Sabaody -- Taberna del Cazador',
    'mundo_accion' => 'Patrullando rutas de escape desde Marineford. Acechando a piratas heridos que huyen del caos. Cada presa es una cifra sumandose a su cuenta bancaria.',
    'mundo_estado_np' => 'Activo',
]);


// ================================================================
// PRINCIPE OAKHAVEN -- Tenryubito Caprichoso, Nivel 5
// ================================================================

$s_oak = '{"FUE":4,"DES":4,"VIG":4,"AGI":4,"INT":8,"ING":4,"CON":4,"PER":4,"CAR":6,"CTR":4,"VOL":5,"SEN":5}';

seed_npc($db, [
    'slug' => 'principe-oakhaven',
    'nombre' => "Principe Oakhaven",
    'nivel' => 5, 'ps_gastados' => 60,
    'stats_json' => $s_oak,
    'datos_legacy' => '{"raza_principal":"humano","hibrido":false,"apodo":"El Niño Dorado","edad":"19","genero":"Masculino","stats_efectivas":{"FUE":4,"DES":4,"VIG":4,"AGI":4,"INT":8,"ING":4,"CON":4,"PER":4,"CAR":6,"CTR":4,"VOL":5,"SEN":5},"virtudes":[{"nombre":"Linaje Celestial","coste":0,"spec":"Como Tenryubito, su estatus garantiza proteccion de la Marina. Atacarle convoca a un Almirante."}],"defectos":[{"nombre":"Fisico Patetico","coste":0,"spec":"Nunca ha entrenado, luchado ni sudado. Cualquier PJ combatiente puede derrotarle sin esfuerzo."}],"pc_gastado":0,"pc_devuelto":0,"pc_balance":0,"faccion":"gobierno","concepto":"Joven y caprichoso Dragon Celestial obsesionado con comprar un gigante de Elbaf como esclavo. Viaja a Marineford a ver la ejecucion como entretenimiento."}',
    'datos_publicos' => file_get_contents(__DIR__ . '/_seed-data/oakhaven_publicos.json'),
    'datos_internos' => file_get_contents(__DIR__ . '/_seed-data/oakhaven_internos.json'),
    'bio' => file_get_contents(__DIR__ . '/_seed-data/oakhaven_bio.json'),
    'desc_fisica' => file_get_contents(__DIR__ . '/_seed-data/oakhaven_fisica.txt'),
    'from_fisico' => 'One Piece, Eiichiro Oda -- adaptado por el staff de One Piece: Eternal',
    'personalidad' => file_get_contents(__DIR__ . '/_seed-data/oakhaven_personalidad.txt'),
    'mundo_zona' => 'paraiso',
    'mundo_ubic' => 'Mary Geoise -- Palacio de la Familia Oakhaven',
    'mundo_accion' => 'Viajando hacia Marineford con su sequito para presenciar la ejecucion de Isabella D. Vega. Hara escala en Sabaody para negociar la compra de un gigante de Elbaf en el mercado negro.',
    'mundo_estado_np' => 'Activo',
]);

echo "\n=== FIN: 3 NPCs civiles procesados ===\n";
