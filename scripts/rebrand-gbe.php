<?php
/**
 * One Piece: Eternal · Rebranding
 * --------------------------------------
 * - bbname en mybb_settings
 * - Refresca curiosidades + lore en datacache 'ope_home' (hasta purga → ope_home)
 *
 *   php scripts/rebrand-gbe.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

function q(mysqli $db, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "SQL ERROR: {$db->error}\n");
        exit(1);
    }
}

$name = 'One Piece: Eternal';
$stmt = $db->prepare("UPDATE {$PREFIX}settings SET value = ? WHERE name = 'bbname'");
$stmt->bind_param('s', $name);
$stmt->execute();
echo "bbname actualizado (filas: {$stmt->affected_rows}).\n";
$stmt->close();

$curiosidades = [
    'El cielo no tiene fin: cada isla es un mundo y cada ruta una apuesta.',
    'No todos los skyfarers sellan un Pacto con una Bestia Primaria; elemento y arma definen a la mayoría.',
    'Las Órdenes del Gremio miden el renombre mejor que cualquier mapa.',
    'Una crew sin aeronave no llega lejos; el barco es la segunda casa.',
];
$lore = [
    'titulo' => 'La Grieta de Éter se ensancha',
    'texto'  => 'Los faros de Villa Farolar parpadean y el horizonte vuelve a agitarse. One Piece: Eternal abre el registro de skyfarers: crea tu personaje, elige elemento y arma, y zarpa hacia Estalucia.',
];

$res = $db->query("SELECT cache FROM {$PREFIX}datacache WHERE title = 'ope_home' LIMIT 1");
$home = [];
$exists = false;
if ($res && ($row = $res->fetch_assoc())) {
    $exists = true;
    $decoded = @unserialize($row['cache']);
    if (is_array($decoded)) {
        $home = $decoded;
    }
}

$home['curiosidades'] = $curiosidades;
$home['lore'] = $lore;
if (!isset($home['discord_url'])) {
    $home['discord_url'] = 'https://discord.gg/';
}
if (!isset($home['rol_epoch'])) {
    $home['rol_epoch'] = mktime(0, 0, 0, 1, 1, 2026);
}

$serialized = serialize($home);
$stmt = $exists
    ? $db->prepare("UPDATE {$PREFIX}datacache SET cache = ? WHERE title = 'ope_home'")
    : $db->prepare("INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('ope_home', ?)");
$stmt->bind_param('s', $serialized);
$stmt->execute();
echo "datacache 'ope_home' refrescado (lore OPE).\n";
$stmt->close();

q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ('settings','default_theme','theme13')");
echo "Caché de settings/tema invalidada.\n";

echo "DONE rebrand-gbe.\n";
