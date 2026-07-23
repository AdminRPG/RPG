<?php
/**
 * One Piece: Eternal · Siembra de las 44 islas y foros de Mar Abierto por Región
 * -----------------------------------------------------------------------------
 * Crea/actualiza en MyBB:
 *   - La categoría principal 'El Mundo'
 *   - Las 8 regiones (East Blue, West Blue, North Blue, South Blue, Calm Belt, Red Line, Paraíso, New World)
 *   - Las 44 islas del catálogo oficial bajo su región correspondiente
 *   - Un foro 'Mar Abierto (Región)' en CADA una de las 8 regiones para la apertura automática de viajes
 *
 * Idempotente. Ejecutar: php scripts/seed-islas-mar-abierto.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
require __DIR__ . '/_db-config.php';
require_once __DIR__ . '/../inc/ope_rol/mundo/islas_cat.php';

$PREFIX = 'mybb_';

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
    } else {
        echo "  [OK] $label\n";
    }
}

function upsert_forum(mysqli $db, string $prefix, string $name, string $desc, int $pid, string $type, int $disporder): int
{
    $nameE = $db->real_escape_string($name);
    $r = $db->query("SELECT fid FROM {$prefix}forums WHERE name='{$nameE}' AND pid={$pid} AND type='{$type}' LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        $fid = (int) $row['fid'];
        $descE = $db->real_escape_string($desc);
        $db->query("UPDATE {$prefix}forums SET description='{$descE}', disporder={$disporder}, active=1 WHERE fid={$fid}");
        echo "  [=] Foro {$name} (fid={$fid})\n";
        return $fid;
    }

    $descE = $db->real_escape_string($desc);
    $db->query("INSERT INTO {$prefix}forums
        (name, description, active, type, pid, disporder, open, allowhtml, allowmycode, allowsmilies,
         allowimgcode, allowvideocode, allowtratings, usepostcounts, usethreadcounts, requireprefix,
         showinjump, parentlist, threads, posts, unapprovedthreads, unapprovedposts, defaultsortby, rules, rulestitle)
        VALUES ('{$nameE}', '{$descE}', 1, '{$type}', {$pid}, {$disporder}, 1, 0, 1, 1,
         1, 1, 1, 1, 1, 0, 1, '', 0, 0, 0, 0, 'lastpost', '', '')");
    $fid = (int) $db->insert_id;
    $parentlist = (string) $fid;
    if ($pid > 0) {
        $pr = $db->query("SELECT parentlist FROM {$prefix}forums WHERE fid={$pid}");
        $prow = $pr ? $pr->fetch_assoc() : null;
        $pp = $prow ? (string) $prow['parentlist'] : '';
        $parentlist = ($pp !== '' ? $pp . ',' : '') . $fid;
    }
    $db->query("UPDATE {$prefix}forums SET parentlist='{$parentlist}' WHERE fid={$fid}");
    echo "  [+] Foro {$name} (fid={$fid})\n";
    return $fid;
}

echo "=== Categoría El Mundo ===\n";
$elMundo = upsert_forum($db, $PREFIX, 'El Mundo', 'Las ocho regiones del mundo conocido. Elige una región para explorar sus islas y navegar en mar abierto.', 0, 'c', 1);

// Definir las 8 regiones
$regiones_info = array(
    'East Blue'  => array('desc' => 'El mar de los novatos. Aguas tranquilas y el punto de partida de la mayoría de tripulaciones.', 'macro' => 'east_blue',  'order' => 1),
    'West Blue'  => array('desc' => 'Costas fragmentadas entre reinos independientes, puertos comerciales y gremios.',       'macro' => 'west_blue',  'order' => 2),
    'North Blue' => array('desc' => 'El mar del norte. Fiordos helados, fortalezas y patrullas constantes de la Marine.',        'macro' => 'north_blue', 'order' => 3),
    'South Blue' => array('desc' => 'Territorios insulares dispersos y aguas de clima variable con abundante actividad pirata.', 'macro' => 'south_blue', 'order' => 4),
    'Calm Belt'  => array('desc' => 'Mares en calma absoluta sin viento ni corriente, dominados por Reyes del Mar.',            'macro' => 'calm_belt',  'order' => 5),
    'Red Line'   => array('desc' => 'El gran continente de roca que divide el mundo. Asiento del Gobierno Mundial y Marineford.', 'macro' => 'red_line',   'order' => 6),
    'Paraíso'    => array('desc' => 'La primera mitad de la Grand Line. Clima magnético e islas donde se forjan las leyendas.',   'macro' => 'paradise',   'order' => 7),
    'New World'  => array('desc' => 'La segunda mitad de la Grand Line. El mar más peligroso del mundo, bajo el dominio de los Yonko.','macro'=>'new_world',  'order' => 8),
);

$region_fids = array();
$mar_abierto_fids = array();

echo "\n=== Creando las 8 Regiones ===\n";
foreach ($regiones_info as $r_name => $r_data) {
    $r_fid = upsert_forum($db, $PREFIX, $r_name, $r_data['desc'], $elMundo, 'f', $r_data['order']);
    $region_fids[$r_data['macro']] = $r_fid;

    // Crear foro "Mar Abierto (Región)" en CADA región
    $mar_abierto_name = 'Mar Abierto (' . $r_name . ')';
    $mar_abierto_desc = 'Bitácoras de travesía en alta mar por el ' . $r_name . '. Los hilos se abren automáticamente al navegar hacia este mar.';
    $ma_fid = upsert_forum($db, $PREFIX, $mar_abierto_name, $mar_abierto_desc, $r_fid, 'f', 1);
    $mar_abierto_fids[$r_data['macro']] = $ma_fid;
}

echo "\n=== Creando las 44 Islas del Catálogo ===\n";
$islas_cat = ope_islas_catalogo();
$isla_order = array();

foreach ($islas_cat as $slug => $isla) {
    $macro = $isla['macro'];
    if (!isset($region_fids[$macro])) {
        continue;
    }
    $r_fid = $region_fids[$macro];
    if (!isset($isla_order[$macro])) {
        $isla_order[$macro] = 2; // 1 reservado para Mar Abierto
    }
    $ord = $isla_order[$macro]++;

    $isla_name = $isla['nombre'];
    $isla_desc = 'Isla de ' . $isla['region'] . ' (Tier ' . $isla['tier'] . '). Peligro Base: ' . $isla['peligro_base'] . '/10.';
    $isla_fid = upsert_forum($db, $PREFIX, $isla_name, $isla_desc, $r_fid, 'f', $ord);
}

echo "\n=== Limpiando caché de foros ===\n";
$db->query("DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forumsdisplay','moderators')");
echo "  [OK] Caché de foros invalidada.\n";

echo "\n=== DONE ===\n";
echo "Se han sembrado las 8 regiones, las 44 islas oficiales y los 8 foros de Mar Abierto por mar.\n";
