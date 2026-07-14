<?php
/**
 * I-Forge · Reestructuración de "El Mundo"
 * -------------------------------------------------------------------
 * Cambios pedidos:
 *   - Los 4 Blues (East/West/North/South) dejan de ser islas de un
 *     "Blues" contenedor y pasan a ser regiones propias bajo El Mundo,
 *     cada una con sus propias islas.
 *   - "Grand Line" se renombra a "Paraíso" (conserva sus islas: Alabasta,
 *     Skypiea, Water Seven, Thriller Bark — la primera mitad de la ruta).
 *   - "Paraíso & New World" se renombra a "New World" (conserva sus
 *     islas: Dressrosa, Zou, Whole Cake Island, Wano — la segunda mitad).
 *   - Se puebla mybb_rol_forum_meta (dueño actual, clima, zonas,
 *     anotaciones) para El Mundo, sus 8 regiones y todas sus islas.
 *
 * Idempotente: se puede re-ejecutar sin duplicar nada.
 *
 * Requiere haber corrido antes: scripts/seed-forums.php y
 * scripts/migrate-forum-meta.php.
 *
 * Ejecutar:
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
        $parentParentlist = $prow ? $prow['parentlist'] : '';
        $parentlist = ($parentParentlist !== '' ? $parentParentlist . ',' : '') . $fid;
    }
    q($db, "UPDATE {$PREFIX}forums SET parentlist='{$parentlist}' WHERE fid={$fid}");

    echo "  [+] $name (fid={$fid})\n";
    return $fid;
}

function rename_forum(mysqli $db, string $PREFIX, int $fid, string $newName, string $newDesc): void
{
    q($db, "UPDATE {$PREFIX}forums SET name='" . esc($db, $newName) . "', description='" . esc($db, $newDesc) . "' WHERE fid={$fid}");
    echo "  [~] fid={$fid} -> $newName\n";
}

function reparent_forum(mysqli $db, string $PREFIX, int $fid, int $newPid, string $newDesc): void
{
    $pr = q($db, "SELECT parentlist FROM {$PREFIX}forums WHERE fid={$newPid}");
    $prow = $pr->fetch_assoc();
    $parentParentlist = $prow ? $prow['parentlist'] : '';
    $parentlist = ($parentParentlist !== '' ? $parentParentlist . ',' : '') . $fid;
    q($db, "UPDATE {$PREFIX}forums SET pid={$newPid}, parentlist='" . esc($db, $parentlist) . "', description='" . esc($db, $newDesc) . "' WHERE fid={$fid}");
    echo "  [>] fid={$fid} reparentado bajo fid={$newPid}\n";
}

function upsert_meta(mysqli $db, string $PREFIX, int $fid, string $dueno, string $clima, array $zonas, string $anotaciones = ''): void
{
    $zonasJson = esc($db, json_encode(array_values($zonas), JSON_UNESCAPED_UNICODE));
    $now = time();
    q($db, "INSERT INTO {$PREFIX}rol_forum_meta (fid, dueno, clima, zonas, anotaciones, dateline, lastedit)
        VALUES ({$fid}, '" . esc($db, $dueno) . "', '" . esc($db, $clima) . "', '{$zonasJson}', '" . esc($db, $anotaciones) . "', {$now}, {$now})
        ON DUPLICATE KEY UPDATE dueno=VALUES(dueno), clima=VALUES(clima), zonas=VALUES(zonas), anotaciones=VALUES(anotaciones), lastedit=VALUES(lastedit)");
}

// ---------------------------------------------------------------------
// 1) Localizar nodos existentes
// ---------------------------------------------------------------------
$elMundo = find_fid($db, $PREFIX, 'El Mundo', 0, 'c');
$offTopic = find_fid($db, $PREFIX, 'Off Topic', 0, 'c');
$blues = find_fid($db, $PREFIX, 'Blues', $elMundo, 'f');
$grandLine = find_fid($db, $PREFIX, 'Grand Line', $elMundo, 'f');
$paraisoNW = find_fid($db, $PREFIX, 'Paraíso & New World', $elMundo, 'f');

if ($elMundo === null) {
    fwrite(STDERR, "No existe la categoría 'El Mundo'. Ejecuta primero scripts/seed-forums.php\n");
    exit(1);
}

echo "=== Renombrando Grand Line -> Paraíso / Paraíso&NewWorld -> New World ===\n";
if ($grandLine !== null) {
    rename_forum($db, $PREFIX, $grandLine, 'Paraíso', 'La primera mitad de la Grand Line: de Reverse Mountain a Sabaody. El tramo donde se forjan las leyendas nuevas.');
    $paraiso = $grandLine;
} else {
    $paraiso = upsert_forum($db, $PREFIX, 'Paraíso', 'La primera mitad de la Grand Line: de Reverse Mountain a Sabaody. El tramo donde se forjan las leyendas nuevas.', $elMundo, 'f', 7);
}
if ($paraisoNW !== null) {
    rename_forum($db, $PREFIX, $paraisoNW, 'New World', 'La segunda mitad de la Grand Line, tras Sabaody. El mar más violento e impredecible, repartido entre los Cuatro Emperadores.');
    $newWorld = $paraisoNW;
} else {
    $newWorld = upsert_forum($db, $PREFIX, 'New World', 'La segunda mitad de la Grand Line, tras Sabaody. El mar más violento e impredecible, repartido entre los Cuatro Emperadores.', $elMundo, 'f', 8);
}

echo "\n=== Los 4 Blues pasan a ser regiones propias (antes islas de 'Blues') ===\n";
$blueDescs = [
    'East Blue' => 'El mar de los novatos: aguas tranquilas y el punto de partida de la mayoría de las tripulaciones.',
    'West Blue' => 'Costas fragmentadas entre reinos menores, mercantes y disputas de poca monta lejos de la vigilancia del Gobierno.',
    'North Blue' => 'El mar más frío: fiordos helados, reinos hereditarios y una vigilancia férrea de la Marine.',
    'South Blue' => 'Territorios dispersos y alta presencia pirata; el mar donde más fácil es desaparecer.',
];
$blueFids = [];
foreach ($blueDescs as $name => $desc) {
    $fid = ($blues !== null) ? find_fid($db, $PREFIX, $name, $blues, 'f') : null;
    if ($fid === null) {
        // Ya reparentado en una ejecución anterior: buscar bajo El Mundo directamente.
        $fid = find_fid($db, $PREFIX, $name, $elMundo, 'f');
    }
    if ($fid === null) {
        fwrite(STDERR, "  No se encontró el foro '$name', se creará como región nueva.\n");
        $fid = upsert_forum($db, $PREFIX, $name, $desc, $elMundo, 'f', 1);
    } else {
        reparent_forum($db, $PREFIX, $fid, $elMundo, $desc);
    }
    $blueFids[$name] = $fid;
}

if ($blues !== null) {
    $r = q($db, "SELECT COUNT(*) c FROM {$PREFIX}forums WHERE pid={$blues}");
    $remaining = (int)$r->fetch_assoc()['c'];
    if ($remaining === 0) {
        q($db, "DELETE FROM {$PREFIX}forums WHERE fid={$blues}");
        q($db, "DELETE FROM {$PREFIX}rol_forum_meta WHERE fid={$blues}");
        echo "  [-] Contenedor 'Blues' (fid={$blues}) eliminado\n";
    } else {
        echo "  [!] 'Blues' (fid={$blues}) aún tiene {$remaining} hijo(s), no se elimina\n";
    }
}

echo "\n=== Islas de cada Blue ===\n";
$blueIslas = [
    'East Blue' => ['Fuchsia Village', 'Shells Town', 'Baratie', 'Syrup Village'],
    'West Blue' => ['Isla Poniente', 'Bahía Gris', 'Puerto Salado', 'Cabo Tormenta'],
    'North Blue' => ['Isla Escarcha', 'Puerto Boreal', 'Fiordo Negro', 'Bahía Helada'],
    'South Blue' => ['Isla del Sur', 'Cayo Ámbar', 'Puerto Meridional', 'Arrecife Austral'],
];
$islandFids = [];
foreach ($blueIslas as $region => $islas) {
    $order = 1;
    foreach ($islas as $isla) {
        $islandFids[$isla] = upsert_forum($db, $PREFIX, $isla, "Isla de {$region}. Sus calles, gentes y tramas están por escribir.", $blueFids[$region], 'f', $order++);
    }
}

echo "\n=== Orden final de las 8 regiones bajo El Mundo ===\n";
$order = [
    'East Blue' => 1, 'West Blue' => 2, 'North Blue' => 3, 'South Blue' => 4,
    'Calm Belt' => 5, 'Red Line' => 6, 'Paraíso' => 7, 'New World' => 8,
];
foreach ($order as $name => $disp) {
    $fid = find_fid($db, $PREFIX, $name, $elMundo, 'f');
    if ($fid !== null) {
        q($db, "UPDATE {$PREFIX}forums SET disporder={$disp} WHERE fid={$fid}");
    }
}
q($db, "UPDATE {$PREFIX}forums SET disporder=1 WHERE fid={$elMundo}");
if ($offTopic !== null) {
    q($db, "UPDATE {$PREFIX}forums SET disporder=2 WHERE fid={$offTopic}");
}
echo "  Orden aplicado.\n";

// ---------------------------------------------------------------------
// Metadatos (dueño actual / clima / zonas / anotaciones)
// ---------------------------------------------------------------------
echo "\n=== Metadatos: El Mundo + regiones + islas ===\n";

q($db, "UPDATE {$PREFIX}forums SET description='" . esc($db, 'Las ocho grandes regiones del mundo conocido. Elige una región para entrar en sus islas.') . "' WHERE fid={$elMundo}");
upsert_meta($db, $PREFIX, $elMundo, 'Repartido entre el Gobierno Mundial, los Cuatro Emperadores y cientos de reinos menores', 'De extremo a extremo: desde el hielo eterno hasta el desierto ardiente', ['East Blue', 'West Blue', 'North Blue', 'South Blue', 'Calm Belt', 'Red Line', 'Paraíso', 'New World']);

$regionMeta = [
    'East Blue' => ['Sin gobierno unificado; alcaldías y consejos locales', 'Templado, mares tranquilos casi todo el año', ['Rutas comerciales costeras', 'Arrecifes pesqueros', 'Islas dispersas de baja vigilancia']],
    'West Blue' => ['Fragmentado entre reinos menores y gremios portuarios', 'Variable, con tormentas frecuentes en invierno', ['Costas rocosas', 'Rutas mercantes al oeste', 'Puertos de paso']],
    'North Blue' => ['Varios reinos hereditarios bajo vigilancia de la Marine', 'Frío, con inviernos duros y fiordos helados', ['Fiordos del norte', 'Rutas heladas', 'Puestos avanzados de la Marine']],
    'South Blue' => ['Territorios dispersos con alta presencia pirata', 'Cálido y húmedo, con temporada de lluvias marcada', ['Islas volcánicas', 'Rutas al sur', 'Zonas de naufragios frecuentes']],
    'Calm Belt' => ['Ningún gobierno se aventura aquí; territorio de los Reyes del Mar', 'Sin viento, calma absoluta y calor sofocante', ['Aguas muertas', 'Territorio de Reyes del Mar', 'Paso de remolque vigilado']],
    'Red Line' => ['Gobierno Mundial', 'Extremos según la altitud: de tropical a gélido', ['La columna que divide todos los mares', 'Puntos de cruce vigilados']],
    'Paraíso' => ['Sin autoridad única; disputada por piratas y Marine', 'Impredecible, cambia de una isla a otra sin aviso', ['Primera mitad de la Grand Line', 'Del Reverse Mountain a Sabaody']],
    'New World' => ['Repartida entre los Cuatro Emperadores', 'El más violento e impredecible de todos los mares', ['Segunda mitad de la Grand Line', 'Territorio Yonko']],
];
foreach ($regionMeta as $name => [$dueno, $clima, $zonas]) {
    $fid = find_fid($db, $PREFIX, $name, $elMundo, 'f');
    if ($fid !== null) { upsert_meta($db, $PREFIX, $fid, $dueno, $clima, $zonas); }
}

$islandMeta = [
    'Fuchsia Village' => ['Alcaldía local', 'Costero suave, brisa constante', ['Puerto', 'Colina del Alcalde', 'Muelle Viejo']],
    'Shells Town' => ['Antigua guarnición de la Marine, hoy medio abandonada', 'Seco, vientos de tierra', ['Cuartel en ruinas', 'Plaza del Pilar']],
    'Baratie' => ['Zeff "Pata de Cabra" (el restaurante-barco)', 'Mar abierto, sin estación fija', ['Cocina', 'Cubierta de clientes', 'Bodega']],
    'Syrup Village' => ['Consejo de vecinos', 'Templado, con niebla matinal', ['Bosque cercano', 'Mansión de la colina', 'Puerto pesquero']],

    'Isla Poniente' => ['Consejo portuario', 'Ventoso todo el año', ['Puerto Viejo', 'Faro Roto']],
    'Bahía Gris' => ['Disputada entre dos clanes pesqueros', 'Nublado casi permanente', ['Muelle Sur', 'Barrio de Redes']],
    'Puerto Salado' => ['Gremio de comerciantes de sal', 'Seco y salino', ['Salinas', 'Almacenes portuarios']],
    'Cabo Tormenta' => ['Nadie; zona salvaje sin ley', 'Tormentoso, oleaje fuerte', ['Acantilados', 'Cueva del Naufragio']],

    'Isla Escarcha' => ['Monarquía local menor', 'Nieve casi todo el año', ['Castillo helado', 'Mercado cubierto']],
    'Puerto Boreal' => ['Base naval regional', 'Gélido, con vientos cortantes', ['Muelle militar', 'Faro del Norte']],
    'Fiordo Negro' => ['Clan de cazadores', 'Oscuro y frío, noches muy largas', ['Bosque negro', 'Cuevas de hielo']],
    'Bahía Helada' => ['Sin gobierno formal', 'Bajo cero casi constante', ['Muelle congelado', 'Refugio de pescadores']],

    'Isla del Sur' => ['Alcaldía costera', 'Tropical, húmedo', ['Playa Este', 'Mercado central']],
    'Cayo Ámbar' => ['Disputado por bandas locales', 'Cálido, con lluvias intensas en verano', ['Cala escondida', 'Ruinas antiguas']],
    'Puerto Meridional' => ['Gremio de pescadores', 'Húmedo, brisa marina constante', ['Lonja', 'Astillero']],
    'Arrecife Austral' => ['Nadie; zona de naufragios', 'Cálido, con tormentas súbitas', ['Arrecife exterior', 'Cementerio de barcos']],

    'Calm Belt Norte' => ['Reyes del Mar', 'Calma total, calor sofocante', ['Aguas superficiales', 'Fondo abisal']],
    'Calm Belt Sur' => ['Reyes del Mar', 'Calma total, humedad extrema', ['Paso de remolque', 'Zona de avistamiento']],

    'Mariejois' => ['Gobierno Mundial / Nobles Mundiales', 'Templado y controlado', ['Ciudad Sagrada', 'Salón de los Cinco Ancianos']],
    'Marineford' => ['Cuartel General de la Marine', 'Marítimo templado', ['Plaza de ejecuciones', 'Puerto de guerra']],
    'Reverse Mountain' => ['Crocus, el farero (neutral)', 'Corrientes cruzadas, clima impredecible', ['Los cuatro canales', 'El Faro']],

    'Alabasta' => ['Casa real Nefertari', 'Desértico', ['Alubarna (capital)', 'Yuba', 'Rainbase']],
    'Skypiea' => ['Consejo de sacerdotes', 'Cielo despejado con tormentas eléctricas súbitas', ['Upper Yard', 'Templo de Oro']],
    'Water Seven' => ['Gremio de astilleros (Galley-La)', 'Húmedo, ciudad de canales navegables', ['Dock 1', 'Barrio de astilleros']],
    'Thriller Bark' => ['Sin dueño fijo; isla errante', 'Nublado y tenebroso de forma permanente', ['Bosque encantado', 'Castillo gótico']],

    'Dressrosa' => ['Corte real, bajo vigilancia de la Marine', 'Mediterráneo, cálido', ['Coliseo', 'Barrio de juguetes']],
    'Zou' => ['Clan Mink', 'Templado; la isla es un elefante en movimiento', ['Lomo del elefante', 'Capital Mink']],
    'Whole Cake Island' => ['Una gran familia numerosa', 'Dulce y cálido, "eterno postre"', ['Castillo de bizcocho', 'Jardín de azúcar']],
    'Wano Country' => ['Shogunato local', 'Templado, con estaciones marcadas', ['Capital de las Flores', 'Puerto cerrado']],
];
foreach ($islandMeta as $name => [$dueno, $clima, $zonas]) {
    if (isset($islandFids[$name])) {
        $fid = $islandFids[$name];
    } else {
        // Foro ya existente de una ejecución anterior (nombre único en la práctica)
        $r = q($db, "SELECT fid FROM {$PREFIX}forums WHERE name='" . esc($db, $name) . "' AND type='f' LIMIT 1");
        $row = $r->fetch_assoc();
        $fid = $row ? (int)$row['fid'] : null;
    }
    if ($fid !== null) { upsert_meta($db, $PREFIX, $fid, $dueno, $clima, $zonas); }
}

echo "\n=== Limpiando caché de foros ===\n";
q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forumsdisplay','moderators')");
echo "  hecho\n";

echo "\n=== DONE ===\n";
