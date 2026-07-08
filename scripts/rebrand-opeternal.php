<?php
/**
 * One Piece Eternal · Rebranding
 * ------------------------------
 * - Persiste bbname = "One Piece Eternal" en mybb_settings (además del cache file).
 * - Refresca curiosidades + lore del datacache 'ope_home' a la temática nueva,
 *   preservando discord_url y rol_epoch si ya existían.
 *
 *   php scripts/rebrand-opeternal.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    fwrite(STDERR, "DB connection error: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');
$PREFIX = 'mybb_';

/* 1) bbname en la tabla de settings (fuente para reconstrucciones de caché) */
$name = 'One Piece Eternal';
$stmt = $db->prepare("UPDATE {$PREFIX}settings SET value = ? WHERE name = 'bbname'");
$stmt->bind_param('s', $name);
$stmt->execute();
echo "bbname actualizado en BD (filas afectadas: {$stmt->affected_rows}).\n";
$stmt->close();

/* 2) datacache 'ope_home': refrescar curiosidades + lore, preservar el resto */
$curiosidades = array(
    'En el Grand Line las brújulas normales no sirven: solo un Log Pose marca el rumbo entre islas.',
    'La escala de poder mide fuerza, no tamaño. Un rango alto impone aunque el personaje parezca inofensivo.',
    'Un personaje sin rumbo se estanca; el mundo premia a quien sigue navegando y arriesgando.',
    'Cada historia deja huella en el mundo. Ninguna aventura pasa sin dejar marca.',
);
$lore = array(
    'titulo' => 'La aventura continúa',
    'texto'  => 'One Piece Eternal abre sus puertas a una nueva generación de aventureros. El mundo, sus reglas y su historia se escriben aquí, historia a historia. Crea tu personaje y zárpate a descubrir el mundo.',
);

$res = $db->query("SELECT cache FROM {$PREFIX}datacache WHERE title = 'ope_home' LIMIT 1");
$home = array();
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
if (!isset($home['rol_epoch'])) {
    $home['rol_epoch'] = mktime(0, 0, 0, 1, 1, 2026);
}
if (!isset($home['discord_url'])) {
    $home['discord_url'] = 'https://discord.gg/';
}

$serialized = serialize($home);
if ($exists) {
    $stmt = $db->prepare("UPDATE {$PREFIX}datacache SET cache = ? WHERE title = 'ope_home'");
} else {
    $stmt = $db->prepare("INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('ope_home', ?)");
}
$stmt->bind_param('s', $serialized);
$stmt->execute();
$stmt->close();
echo "datacache 'ope_home' refrescado (curiosidades+lore). discord_url y rol_epoch preservados.\n";

$db->close();
echo "Rebranding completado.\n";
