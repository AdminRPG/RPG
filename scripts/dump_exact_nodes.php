<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol_eternal.php';

$trees = array(
    'identidad-coloso', 'arma-cuerpo',
    'identidad-duelista', 'arma-filo',
    'identidad-centinela', 'arma-alcance',
    'identidad-cazador', 'arma-distancia',
    'identidad-verdugo', 'arma-contundente'
);

foreach ($trees as $tid) {
    $t = ope_eternal_load($tid);
    echo "=== {$tid} ({$t['nombre']}) ===\n";
    foreach ($t['nodos'] as $nid => $nodo) {
        echo "   {$nid} => {$nodo['label']}\n";
    }
    echo "\n";
}
