<?php
/**
 * One Piece: Eternal · Reestructuración El Cielo (Skydoms)
 * ---------------------------------------------------------------
 * - Renombra categoría "El Mundo" → "El Cielo" (o la crea)
 * - Desactiva foros OP legacy bajo esa categoría
 * - Crea Skydoms canónicos con islas originales
 * - Actualiza Off Topic con foros OPE
 *
 * Idempotente. Ejecutar:
 *   php scripts/restructure-skydoms.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

function q(mysqli $db, string $sql)
{
    $r = $db->query($sql);
    if ($r === false) {
        fwrite(STDERR, "SQL ERROR: {$db->error}\nSQL: $sql\n");
        exit(1);
    }
    return $r;
}

function esc(mysqli $db, string $s): string
{
    return $db->real_escape_string($s);
}

function find_fid(mysqli $db, string $PREFIX, string $name, int $pid, string $type): ?int
{
    $r = q($db, "SELECT fid FROM {$PREFIX}forums WHERE name='" . esc($db, $name) . "' AND pid={$pid} AND type='{$type}' LIMIT 1");
    $row = $r->fetch_assoc();
    return $row ? (int)$row['fid'] : null;
}

function upsert_forum(mysqli $db, string $PREFIX, string $name, string $desc, int $pid, string $type, int $disporder): int
{
    $existing = find_fid($db, $PREFIX, $name, $pid, $type);
    $descE = esc($db, $desc);
    if ($existing !== null) {
        q($db, "UPDATE {$PREFIX}forums SET description='{$descE}', disporder={$disporder}, active=1 WHERE fid={$existing}");
        echo "  [=] $name (fid={$existing})\n";
        return $existing;
    }

    $defaultsortby = ($type === 'c') ? '' : 'lastpost';
    q($db, "INSERT INTO {$PREFIX}forums
        (name, description, active, type, pid, disporder, open, allowhtml, allowmycode, allowsmilies,
         allowimgcode, allowvideocode, allowtratings, usepostcounts, usethreadcounts, requireprefix,
         showinjump, parentlist, threads, posts, unapprovedthreads, unapprovedposts, defaultsortby, rules, rulestitle)
        VALUES
        ('" . esc($db, $name) . "', '{$descE}', 1, '{$type}', {$pid}, {$disporder}, 1, 0, 1, 1,
         1, 1, 1, 1, 1, 0,
         1, '', 0, 0, 0, 0, '{$defaultsortby}', '', '')");
    $fid = (int)$db->insert_id;

    $parentlist = (string)$fid;
    if ($pid > 0) {
        $pr = q($db, "SELECT parentlist FROM {$PREFIX}forums WHERE fid={$pid}");
        $prow = $pr->fetch_assoc();
        $pp = $prow ? $prow['parentlist'] : '';
        $parentlist = ($pp !== '' ? $pp . ',' : '') . $fid;
    }
    q($db, "UPDATE {$PREFIX}forums SET parentlist='{$parentlist}' WHERE fid={$fid}");
    echo "  [+] $name (fid={$fid})\n";
    return $fid;
}

function deactivate_children(mysqli $db, string $PREFIX, int $pid): void
{
    $r = q($db, "SELECT fid, name FROM {$PREFIX}forums WHERE pid={$pid} AND active=1");
    while ($row = $r->fetch_assoc()) {
        $fid = (int)$row['fid'];
        q($db, "UPDATE {$PREFIX}forums SET active=0 WHERE fid={$fid}");
        echo "  [-] desactivado: {$row['name']} (fid={$fid})\n";
        deactivate_children($db, $PREFIX, $fid);
    }
}

echo "=== El Cielo (categoría) ===\n";
$elCielo = find_fid($db, $PREFIX, 'El Cielo', 0, 'c');
$elMundo = find_fid($db, $PREFIX, 'El Mundo', 0, 'c');

if ($elCielo === null && $elMundo !== null) {
    q($db, "UPDATE {$PREFIX}forums SET name='El Cielo', description='Skydoms del cielo conocido. Elige una región para ver sus islas.' WHERE fid={$elMundo}");
    $elCielo = $elMundo;
    echo "  Renombrado El Mundo → El Cielo (fid={$elCielo})\n";
} elseif ($elCielo === null) {
    $elCielo = upsert_forum($db, $PREFIX, 'El Cielo', 'Skydoms del cielo conocido. Elige una región para ver sus islas.', 0, 'c', 1);
} else {
    upsert_forum($db, $PREFIX, 'El Cielo', 'Skydoms del cielo conocido. Elige una región para ver sus islas.', 0, 'c', 1);
}

if ($elMundo !== null && $elMundo !== $elCielo) {
    q($db, "UPDATE {$PREFIX}forums SET active=0 WHERE fid={$elMundo}");
    deactivate_children($db, $PREFIX, $elMundo);
}

echo "\n=== Desactivar hijos OP legacy bajo El Cielo ===\n";
$legacyNames = [
    'East Blue', 'West Blue', 'North Blue', 'South Blue',
    'Blues', 'Grand Line', 'Calm Belt', 'Red Line',
    'Paraíso', 'New World', 'Paraíso & New World', 'Paraíso &amp; New World',
];
foreach ($legacyNames as $ln) {
    $lf = find_fid($db, $PREFIX, $ln, $elCielo, 'f');
    if ($lf !== null) {
        q($db, "UPDATE {$PREFIX}forums SET active=0 WHERE fid={$lf}");
        echo "  [-] {$ln} (fid={$lf})\n";
        deactivate_children($db, $PREFIX, $lf);
    }
}

echo "\n=== Skydoms + islas ===\n";
$skydoms = [
    ['Phantagrande Skydom', 'Región de inicio. Puerto Ancla, Villa Farolar y los primeros contratos del Gremio.', [
        'Puerto Ancla' => 'Isla-puerto de todo novato skyfarer.',
        'Villa Farolar' => 'Faros de Éter y primeras órdenes del Gremio.',
        'Los Bajíos' => 'Islotes rotos, contrabando y rumores.',
    ]],
    ['Nalhegrande Skydom', 'Tier intermedio. Ferias Harvin, tormentas y Primales menores.', [
        'Feria de Latón' => 'Mercado flotante de los Harvin.',
        'Corona de Tormenta' => 'Tempestad perpetua; duerme un Primal.',
    ]],
    ['Zeephone Skydom', 'Archipiélago helado y cristales de éter. Órdenes T3–T4.', [
        'Cresta de Hielo' => 'Rutas aeronáuticas avanzadas.',
        'Santuario de Éter' => 'Cristales y sellos antiguos.',
    ]],
    ['Auguste Skydom', 'Dominio volcánico. Un Primal duerme bajo la caldera.', [
        'Caldera Sellada' => 'Zona de contención del Gremio.',
    ]],
    ['Estalucia', 'La isla legendaria al borde del cielo conocido. Endgame.', [
        'Umbral de Estalucia' => 'Solo skyfarers de renombre.',
    ]],
];

$order = 1;
foreach ($skydoms as [$skName, $skDesc, $islas]) {
    $skFid = upsert_forum($db, $PREFIX, $skName, $skDesc, $elCielo, 'f', $order++);
    $io = 1;
    foreach ($islas as $islaName => $islaDesc) {
        upsert_forum($db, $PREFIX, $islaName, $islaDesc, $skFid, 'f', $io++);
    }
}

echo "\n=== Off Topic ===\n";
$offTopic = find_fid($db, $PREFIX, 'Off Topic', 0, 'c');
if ($offTopic === null) {
    $offTopic = upsert_forum($db, $PREFIX, 'Off Topic', 'Charla libre fuera de rol.', 0, 'c', 2);
} else {
    upsert_forum($db, $PREFIX, 'Off Topic', 'Charla libre fuera de rol.', 0, 'c', 2);
}

$otForos = [
    ['Cafetería del Puerto', 'Presentaciones y charla libre fuera de rol.'],
    ['Arte y fanworks', 'Dibujos, música y fanart del universo One Piece: Eternal.'],
    ['Sugerencias', 'Ideas de mecánicas, ambientación y mejoras del foro.'],
];
$oto = 1;
foreach ($otForos as [$n, $d]) {
    upsert_forum($db, $PREFIX, $n, $d, $offTopic, 'f', $oto++);
}

echo "\n=== Limpiando caché de foros ===\n";
q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forumsdisplay','moderators')");
echo "DONE restructure-skydoms.\n";
