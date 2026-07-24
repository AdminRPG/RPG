<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== TABLA rol_pj_fruta ===\n";
if ($db->table_exists('rol_pj_fruta')) {
    $res = $db->query("SHOW COLUMNS FROM " . TABLE_PREFIX . "rol_pj_fruta");
    while ($col = $db->fetch_array($res)) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
} else {
    echo "NO EXISTE rol_pj_fruta\n";
}

echo "\n=== TABLA rol_eternal_picks ===\n";
if ($db->table_exists('rol_eternal_picks')) {
    $res = $db->query("SHOW COLUMNS FROM " . TABLE_PREFIX . "rol_eternal_picks");
    while ($col = $db->fetch_array($res)) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
} else {
    echo "NO EXISTE rol_eternal_picks\n";
}

echo "\n=== CATALOGO ETERNAL TREES & NODES ===\n";
require_once MYBB_ROOT . 'inc/ope_rol_eternal.php';
$tree_ids = ope_eternal_tree_ids();
foreach ($tree_ids as $tid) {
    $t = ope_eternal_load($tid);
    echo "Árbol ID: {$tid} | Nombre: {$t['nombre']}\n";
    if (isset($t['nodos'])) {
        foreach ($t['nodos'] as $nid => $nodo) {
            echo "   - Node ID: {$nid} | Label: {$nodo['label']} | Coste: {$nodo['coste']}\n";
        }
    }
}

echo "\n=== CATALOGO AKUMA NO MI ===\n";
require_once MYBB_ROOT . 'inc/ope_rol_frutas.php';
if (function_exists('ope_fruta_catalogo')) {
    $frutas = ope_fruta_catalogo();
    echo "Total Frutas en catálogo: " . count($frutas) . "\n";
    foreach ($frutas as $fid => $fr) {
        echo "  ID: {$fid} | Nombre: {$fr['nombre']} | Slug: {$fr['slug']} | Tipo: {$fr['tipo']}\n";
    }
}
