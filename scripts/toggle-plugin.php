<?php
/**
 * One Piece: Eternal · Activar/desactivar un plugin de MyBB desde CLI
 * --------------------------------------------------------
 * Edita la caché de plugins (mybb_datacache 'plugins') sin pasar por el ACP.
 *
 *   php scripts/toggle-plugin.php <codename> on|off
 *
 * Ejemplo (desactivar el puente legado a la API externa):
 *   php scripts/toggle-plugin.php rolbridge off
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$codename = $argv[1] ?? '';
$state     = strtolower($argv[2] ?? '');
if ($codename === '' || !in_array($state, array('on', 'off'), true)) {
    fwrite(STDERR, "Uso: php scripts/toggle-plugin.php <codename> on|off\n");
    exit(1);
}

require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

$res = $db->query("SELECT cache FROM {$PREFIX}datacache WHERE title = 'plugins' LIMIT 1");
$plugins = array('active' => array());
$exists_row = false;
if ($res && ($row = $res->fetch_assoc())) {
    $exists_row = true;
    $decoded = @unserialize($row['cache']);
    if (is_array($decoded)) {
        $plugins = $decoded;
    }
}
if (!isset($plugins['active']) || !is_array($plugins['active'])) {
    $plugins['active'] = array();
}

if ($state === 'on') {
    $plugins['active'][$codename] = $codename;
    echo "Plugin '{$codename}' ACTIVADO.\n";
} else {
    unset($plugins['active'][$codename]);
    echo "Plugin '{$codename}' DESACTIVADO.\n";
}

$serialized = serialize($plugins);
if ($exists_row) {
    $stmt = $db->prepare("UPDATE {$PREFIX}datacache SET cache = ? WHERE title = 'plugins'");
} else {
    $stmt = $db->prepare("INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('plugins', ?)");
}
$stmt->bind_param('s', $serialized);
$stmt->execute();
$stmt->close();

echo "Plugins activos ahora: " . implode(', ', array_keys($plugins['active'])) . "\n";
$db->close();
