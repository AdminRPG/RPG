<?php
define('IN_MYBB', 1);
define('MYBB_ROOT', dirname(__DIR__) . '/');
require MYBB_ROOT . 'inc/ope_rol_eternal.php';
echo count(ope_eternal_tree_ids()) . " trees\n";
$t = ope_eternal_load('identidad-centinela');
echo ($t['nombre'] ?? 'fail') . ' nodos=' . count($t['nodos'] ?? []) . "\n";
echo substr(ope_eternal_render_tree($t, 'preview'), 0, 120) . "...\n";
