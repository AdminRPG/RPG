<?php
/**
 * One Piece: Eternal · Siembra de estructura de foros
 * -----------------------------------------
 * Borra la categoría de pruebas y crea:
 *   - "El Mundo"  (macro categoría) -> regiones -> islas (subforos)
 *   - "Off Topic" (categoría simple) -> foros de charla fuera de rol
 *
 * Idempotente: si un foro con el mismo nombre y padre ya existe, lo reutiliza
 * (actualiza descripción/orden) en vez de duplicarlo.
 *
 * NOTA: la forma final del árbol de "El Mundo" (Blues como regiones propias,
 * Paraíso/New World separados) se aplica con scripts/restructure-world.php,
 * que se ejecuta DESPUÉS de este. Este script solo pone la primera piedra.
 *
 * Ejecutar:
 *   & "C:\Users\Fgonz\php\php.exe" scripts/seed-forums.php
 *   & "C:\Users\Fgonz\php\php.exe" scripts/migrate-forum-meta.php
 *   & "C:\Users\Fgonz\php\php.exe" scripts/restructure-world.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

function q(mysqli $db, string $sql)
{
    $r = $db->query($sql);
    if ($r === false) {
        fwrite(STDERR, "SQL ERROR: " . $db->error . "\nSQL: $sql\n");
        exit(1);
    }
    return $r;
}

function esc(mysqli $db, string $s): string
{
    return $db->real_escape_string($s);
}

/** Crea (o reutiliza) un foro/categoría y devuelve su fid. */
function upsert_forum(mysqli $db, string $PREFIX, string $name, string $desc, int $pid, string $type, int $disporder): int
{
    $nameE = esc($db, $name);
    $r = q($db, "SELECT fid FROM {$PREFIX}forums WHERE name='{$nameE}' AND pid={$pid} AND type='{$type}' LIMIT 1");
    if ($row = $r->fetch_assoc()) {
        $fid = (int)$row['fid'];
        $descE = esc($db, $desc);
        q($db, "UPDATE {$PREFIX}forums SET description='{$descE}', disporder={$disporder}, active=1 WHERE fid={$fid}");
        echo "  [=] $name (fid={$fid}) actualizado\n";
        return $fid;
    }

    $descE = esc($db, $desc);
    $defaultsortby = ($type === 'c') ? '' : 'lastpost';
    q($db, "INSERT INTO {$PREFIX}forums
        (name, description, active, type, pid, disporder, open, allowhtml, allowmycode, allowsmilies,
         allowimgcode, allowvideocode, allowtratings, usepostcounts, usethreadcounts, requireprefix,
         showinjump, parentlist, threads, posts, unapprovedthreads, unapprovedposts, defaultsortby, rules, rulestitle)
        VALUES
        ('{$db->real_escape_string($name)}', '{$descE}', 1, '{$type}', {$pid}, {$disporder}, 1, 0, 1, 1,
         1, 1, 1, 1, 1, 0,
         1, '', 0, 0, 0, 0, '{$defaultsortby}', '', '')");
    $fid = (int)$db->insert_id;

    $parentlist = (string)$fid;
    if ($pid > 0) {
        $pr = q($db, "SELECT parentlist FROM {$PREFIX}forums WHERE fid={$pid}");
        $prow = $pr->fetch_assoc();
        $parentParentlist = $prow ? $prow['parentlist'] : '';
        $parentlist = ($parentParentlist !== '' ? $parentParentlist . ',' : '') . $fid;
    }
    q($db, "UPDATE {$PREFIX}forums SET parentlist='{$parentlist}' WHERE fid={$fid}");

    echo "  [+] $name (fid={$fid}) creado\n";
    return $fid;
}

echo "=== Limpieza: categoría de pruebas ===\n";
$r = q($db, "SELECT fid FROM {$PREFIX}forums WHERE name='Prueba' AND type='c' AND pid=0");
if ($row = $r->fetch_assoc()) {
    $catFid = (int)$row['fid'];
    $r2 = q($db, "SELECT fid FROM {$PREFIX}forums WHERE pid={$catFid}");
    $childFids = [];
    while ($c = $r2->fetch_assoc()) { $childFids[] = (int)$c['fid']; }
    foreach ($childFids as $cfid) {
        $r3 = q($db, "SELECT tid FROM {$PREFIX}threads WHERE fid={$cfid}");
        $tids = [];
        while ($t = $r3->fetch_assoc()) { $tids[] = (int)$t['tid']; }
        if ($tids) {
            $in = implode(',', $tids);
            q($db, "DELETE FROM {$PREFIX}posts WHERE tid IN ($in)");
            q($db, "DELETE FROM {$PREFIX}threads WHERE tid IN ($in)");
            echo "  Borrados " . count($tids) . " hilo(s) de fid={$cfid}\n";
        }
        q($db, "DELETE FROM {$PREFIX}forums WHERE fid={$cfid}");
        echo "  Borrado foro de prueba fid={$cfid}\n";
    }
    q($db, "DELETE FROM {$PREFIX}forums WHERE fid={$catFid}");
    echo "  Borrada categoría de prueba fid={$catFid}\n";
} else {
    echo "  (no había categoría 'Prueba')\n";
}

echo "\n=== El Mundo ===\n";
$elMundo = upsert_forum($db, $PREFIX, 'El Mundo', 'Las ocho grandes regiones del mundo conocido. Elige una región para entrar en sus islas.', 0, 'c', 1);

$regiones = [
    ['Blues', 'Los cuatro mares que rodean la Red Line: East, West, North y South Blue. Cuna de la mayoría de forjadores novatos.', [
        'East Blue', 'West Blue', 'North Blue', 'South Blue',
    ]],
    ['Grand Line', 'La ruta que ningún mapa doma. Clima imposible, islas imposibles, historias imposibles.', [
        'Alabasta', 'Skypiea', 'Water Seven', 'Thriller Bark',
    ]],
    ['Calm Belt', 'Aguas sin viento ni corriente, dominadas por Reyes del Mar. Cruzarlo sin ayuda es una sentencia.', [
        'Calm Belt Norte', 'Calm Belt Sur',
    ]],
    ['Red Line', 'La columna vertebral del mundo. Divide los mares y sostiene el poder del Gobierno Mundial.', [
        'Mariejois', 'Marineford', 'Reverse Mountain',
    ]],
    ['Paraíso &amp; New World', 'La primera y la segunda mitad de la Grand Line tras Sabaody. Donde se escribe la leyenda o se acaba.', [
        'Dressrosa', 'Zou', 'Whole Cake Island', 'Wano Country',
    ]],
];

$order = 1;
foreach ($regiones as $regionData) {
    [$regionName, $regionDesc, $islas] = $regionData;
    $regionFid = upsert_forum($db, $PREFIX, $regionName, $regionDesc, $elMundo, 'f', $order++);
    $islaOrder = 1;
    foreach ($islas as $islaName) {
        upsert_forum($db, $PREFIX, $islaName, "Isla de {$regionName}. Sus calles, gentes y tramas están por escribir.", $regionFid, 'f', $islaOrder++);
    }
}

echo "\n=== Off Topic ===\n";
$offTopic = upsert_forum($db, $PREFIX, 'Off Topic', 'Charla libre fuera de personaje. Aquí no hay rol, solo la gente detrás de las fichas.', 0, 'c', 2);

$otForos = [
    ['Presentaciones', 'Preséntate a la comunidad: quién eres, qué te trae por aquí y qué esperas forjar.'],
    ['Curiosidades', 'Datos raros, teorías absurdas y descubrimientos que no encajan en ningún otro sitio.'],
    ['Ausencias y Despedidas', 'Avisa si te vas a ausentar una temporada, o despídete si dejas la fragua.'],
    ['Afiliaciones', 'Intercambio de afiliados y hermandad con otros proyectos y comunidades.'],
    ['Deseos y Sugerencias', 'Ideas, quejas constructivas y peticiones para mejorar el foro.'],
];
$otOrder = 1;
foreach ($otForos as [$name, $desc]) {
    upsert_forum($db, $PREFIX, $name, $desc, $offTopic, 'f', $otOrder++);
}

echo "\n=== Recalculando contadores y limpiando caché de foros ===\n";
q($db, "DELETE FROM {$PREFIX}datacache WHERE title='forums'");
q($db, "DELETE FROM {$PREFIX}datacache WHERE title='forumsdisplay'");
q($db, "DELETE FROM {$PREFIX}datacache WHERE title='moderators'");
echo "  Caché 'forums'/'forumsdisplay'/'moderators' invalidada (MyBB la reconstruye sola)\n";

echo "\n=== DONE ===\n";
