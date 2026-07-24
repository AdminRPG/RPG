<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$dir = MYBB_ROOT . 'inc/ope_eternal/';
$files = glob($dir . '*.json');

foreach ($files as $f) {
    $tree_id = basename($f, '.json');
    $raw = file_get_contents($f);
    $data = json_decode($raw, true);
    echo "=== ÁRBOL: {$tree_id} ({$data['nombre']}) ===\n";
    foreach ($data['nodos'] as $n) {
        echo "   Node ID: {$n['id']} | Code: {$n['codigo']} | Name: {$n['nombre']} | Tier: {$n['tier']}\n";
    }
    echo "\n";
}
