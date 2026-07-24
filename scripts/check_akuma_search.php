<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$fruits = array('zushi', 'hie', 'magu', 'mera', 'goro', 'pika', 'ope', 'ito');
foreach ($fruits as $f) {
    $q = $db->simple_select('rol_akuma', '*', "slug LIKE '%{$f}%' OR nombre LIKE '%{$f}%'");
    echo "Búsqueda '{$f}': " . $db->num_rows($q) . " resultados.\n";
    while ($r = $db->fetch_array($q)) {
        echo "   ID: {$r['id']} | Nombre: {$r['nombre']} | Slug: {$r['slug']}\n";
    }
}
