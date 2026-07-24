<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$dir = MYBB_ROOT . 'inc/ope_eternal/';
$files = array('arma-cuerpo.json', 'arma-filo.json', 'arma-contundente.json', 'arma-alcance.json', 'arma-distancia.json');

foreach ($files as $fname) {
    $f = $dir . $fname;
    $tree_id = basename($f, '.json');
    $raw = file_get_contents($f);
    $data = json_decode($raw, true);
    echo "=== ÁRBOL: {$tree_id} ({$data['nombre']}) ===\n";
    foreach ($data['nodos'] as $n) {
        echo "   Node ID: {$n['id']} | Code: {$n['codigo']} | Name: {$n['nombre']} | Tier: {$n['tier']}\n";
    }
    echo "\n";
}
