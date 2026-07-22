<?php
/**
 * One Piece: Eternal · Reestructuración foros → Mares / Islas OP
 * ----------------------------------------------------------------
 * - Desactiva restos Skydom GBF
 * - Normaliza Blues / Paraíso / Nuevo Mundo como categorías
 * - Asegura islas clave (ISLAS.md) con parentlist correcto
 *
 *   php scripts/restructure-mares-op.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

function q(mysqli $db, string $sql): mysqli_result|bool
{
    $r = $db->query($sql);
    if ($r === false) {
        fwrite(STDERR, "SQL FAIL: {$sql}\n{$db->error}\n");
    }
    return $r;
}

function find_by_name(mysqli $db, string $PREFIX, string $name): ?array
{
    $n = $db->real_escape_string($name);
    $r = q($db, "SELECT fid, name, pid, type, parentlist, active FROM {$PREFIX}forums WHERE name='{$n}' LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        return $row;
    }
    return null;
}

function parentlist_for(mysqli $db, string $PREFIX, int $pid, int $fid): string
{
    if ($pid <= 0) {
        return (string) $fid;
    }
    $r = q($db, "SELECT parentlist FROM {$PREFIX}forums WHERE fid=" . (int) $pid . " LIMIT 1");
    $pl = '';
    if ($r && ($row = $r->fetch_assoc())) {
        $pl = trim((string) $row['parentlist']);
    }
    if ($pl === '') {
        $pl = (string) $pid;
    }
    return $pl . ',' . $fid;
}

function set_forum(mysqli $db, string $PREFIX, int $fid, array $fields): void
{
    $parts = array();
    foreach ($fields as $k => $v) {
        if (is_int($v)) {
            $parts[] = "`{$k}`=" . $v;
        } else {
            $parts[] = "`{$k}`='" . $db->real_escape_string((string) $v) . "'";
        }
    }
    q($db, "UPDATE {$PREFIX}forums SET " . implode(',', $parts) . " WHERE fid={$fid}");
}

function upsert(mysqli $db, string $PREFIX, string $name, string $desc, int $pid, string $type, int $disporder, array $aliases = array()): int
{
    $existing = find_by_name($db, $PREFIX, $name);
    if ($existing === null) {
        foreach ($aliases as $alias) {
            $existing = find_by_name($db, $PREFIX, $alias);
            if ($existing) {
                break;
            }
        }
    }
    if ($existing) {
        $fid = (int) $existing['fid'];
        $pl = parentlist_for($db, $PREFIX, $pid, $fid);
        set_forum($db, $PREFIX, $fid, array(
            'name' => $name,
            'description' => $desc,
            'type' => $type,
            'pid' => $pid,
            'disporder' => $disporder,
            'parentlist' => $pl,
            'active' => 1,
            'open' => 1,
        ));
        echo "  [upd] {$name} (fid={$fid})\n";
        return $fid;
    }
    $n = $db->real_escape_string($name);
    $d = $db->real_escape_string($desc);
    q($db, "INSERT INTO {$PREFIX}forums (name, description, type, pid, parentlist, rules, disporder, active, open, allowhtml, allowmycode, allowsmilies, allowimgcode, allowvideocode, allowpicons, allowtratings, usepostcounts, usethreadcounts) VALUES ('{$n}','{$d}','{$type}',{$pid},'','',{$disporder},1,1,0,1,1,1,1,1,1,1,1)");
    $fid = (int) $db->insert_id;
    set_forum($db, $PREFIX, $fid, array('parentlist' => parentlist_for($db, $PREFIX, $pid, $fid)));
    echo "  [new] {$name} (fid={$fid})\n";
    return $fid;
}

echo "=== Reestructurar mares OP ===\n";

// Raíz
$mares = upsert($db, $PREFIX, 'Los Mares', 'Geografía de progresión (Blues → Grand Line → Nuevo Mundo).', 0, 'c', 1, array('El Cielo', 'Skydoms', 'El Mundo'));
$ot = upsert($db, $PREFIX, 'Off Topic', 'Fuera de rol: presentaciones, dudas, off-topic.', 0, 'c', 90);

// Desactivar restos Skydom GBF
$skydom_names = array(
    'Phantagrande Skydom', 'Puerto Ancla', 'Villa Farolar', 'Los Bajíos',
    'Nalhegrande Skydom', 'Feria de Latón', 'Corona de Tormenta',
    'Zeephone Skydom', 'Cresta de Hielo', 'Santuario de Éter',
    'Auguste Skydom', 'Caldera Sellada',
    'Estalucia', 'Umbral de Estalucia',
);
foreach ($skydom_names as $sn) {
    $f = find_by_name($db, $PREFIX, $sn);
    if ($f && (int) $f['active'] === 1) {
        set_forum($db, $PREFIX, (int) $f['fid'], array('active' => 0));
        echo "  [off] {$sn}\n";
    }
}

$east = upsert($db, $PREFIX, 'East Blue', 'Tramo I. Villa Foosha, Loguetown y mares novatos.', $mares, 'c', 1);
$west = upsert($db, $PREFIX, 'West Blue', 'Tramo I. Mares occidentales.', $mares, 'c', 2);
$north = upsert($db, $PREFIX, 'North Blue', 'Tramo I. Mares septentrionales.', $mares, 'c', 3);
$south = upsert($db, $PREFIX, 'South Blue', 'Tramo I. Mares meridionales.', $mares, 'c', 4);
$paraiso = upsert($db, $PREFIX, 'Grand Line — Paraíso', 'Tramo II–III. Reverse Mountain → Sabaody / Skypeia.', $mares, 'c', 5, array('Paraíso', 'Paradise'));
$nw = upsert($db, $PREFIX, 'Nuevo Mundo', 'Tramo IV. Isla Gyojin, Wano, Zou, Onigashima.', $mares, 'c', 6, array('New World', 'Nuevo Mundo'));
$cuspide = upsert($db, $PREFIX, 'Cúspide', 'Tramo V / Prestigio. Laugh Tale (trama staff).', $mares, 'c', 7);

// East Blue islas
upsert($db, $PREFIX, 'Villa Foosha', 'Puerto de inicio. Recluta nakamas y zarpa.', $east, 'f', 1, array('Fuchsia Village', 'Foosha'));
upsert($db, $PREFIX, 'Loguetown', 'Ciudad del inicio y del fin. Comercio y Marina.', $east, 'f', 2);
upsert($db, $PREFIX, 'Shells Town', 'Base de la Marina en East Blue.', $east, 'f', 3);
upsert($db, $PREFIX, 'Baratie', 'Restaurante flotante.', $east, 'f', 4);

// Otros Blues (foro genérico si no hay islas específicas)
upsert($db, $PREFIX, 'Islas del West Blue', 'Aventuras de Tramo I en West Blue.', $west, 'f', 1, array('Isla Poniente'));
upsert($db, $PREFIX, 'Islas del North Blue', 'Aventuras de Tramo I en North Blue.', $north, 'f', 1, array('Isla Escarcha'));
upsert($db, $PREFIX, 'Islas del South Blue', 'Aventuras de Tramo I en South Blue.', $south, 'f', 1, array('Isla del Sur'));

// Paraíso
upsert($db, $PREFIX, 'Reverse Mountain', 'Entrada a Grand Line.', $paraiso, 'f', 1);
upsert($db, $PREFIX, 'Whisky Peak', 'Bienvenida traicionera de cazarrecompensas.', $paraiso, 'f', 2);
upsert($db, $PREFIX, 'Alabasta', 'Reino desértico en conflicto.', $paraiso, 'f', 3);
upsert($db, $PREFIX, 'Water 7', 'Ciudad de canales y astilleros.', $paraiso, 'f', 4, array('Water Seven'));
upsert($db, $PREFIX, 'Sabaody', 'Archipiélago de burbujas y brokers.', $paraiso, 'f', 5, array('Sabaody Archipelago'));
upsert($db, $PREFIX, 'Skypeia', 'Isla del cielo (Knock Up Stream).', $paraiso, 'f', 6, array('Skypiea'));

// Nuevo Mundo
upsert($db, $PREFIX, 'Isla Gyojin', 'Paso submarino bajo la Red Line.', $nw, 'f', 1, array('Fishman Island', 'Isla Gyojin'));
upsert($db, $PREFIX, 'Zou', 'Hogar de los Minks.', $nw, 'f', 2);
upsert($db, $PREFIX, 'Wano Kuni', 'Reino cerrado de samuráis.', $nw, 'f', 3, array('Wano Country', 'Wano'));
upsert($db, $PREFIX, 'Onigashima', 'Fortaleza de alto rango.', $nw, 'f', 4);

// Cúspide
upsert($db, $PREFIX, 'Laugh Tale', 'La isla final. Acceso por Road Poneglyphs / staff.', $cuspide, 'f', 1);

// OT
upsert($db, $PREFIX, 'Presentaciones', 'Preséntate a la comunidad.', $ot, 'f', 1);
upsert($db, $PREFIX, 'Dudas de sistema', 'Preguntas sobre reglas y mecánicas.', $ot, 'f', 2);

// Recalcular parentlist de hijos cuyo padre cambió de tipo (Blues)
foreach (array($east, $west, $north, $south, $paraiso, $nw, $cuspide, $ot) as $parent_fid) {
    $r = q($db, "SELECT fid, pid FROM {$PREFIX}forums WHERE pid={$parent_fid}");
    if (!$r) {
        continue;
    }
    while ($row = $r->fetch_assoc()) {
        $fid = (int) $row['fid'];
        set_forum($db, $PREFIX, $fid, array('parentlist' => parentlist_for($db, $PREFIX, $parent_fid, $fid)));
    }
}

q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forum_permissions','moderators')");
echo "=== listo (cache foros limpiada) ===\n";
$db->close();
