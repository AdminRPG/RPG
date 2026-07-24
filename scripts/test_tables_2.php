<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== TABLA rol_pj_eternal ===\n";
if ($db->table_exists('rol_pj_eternal')) {
    $res = $db->query("SHOW COLUMNS FROM " . TABLE_PREFIX . "rol_pj_eternal");
    while ($col = $db->fetch_array($res)) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
    $q = $db->simple_select('rol_pj_eternal', '*');
    echo "Registros en rol_pj_eternal: " . $db->num_rows($q) . "\n";
    while ($r = $db->fetch_array($q)) {
        echo "PID: {$r['pid']} | Arbol: {$r['arbol']} | Nodo ID: {$r['nodo_id']}\n";
    }
} else {
    echo "NO EXISTE rol_pj_eternal\n";
}

echo "\n=== TABLA rol_akuma (FRUTAS) ===\n";
if ($db->table_exists('rol_akuma')) {
    $res = $db->query("SHOW COLUMNS FROM " . TABLE_PREFIX . "rol_akuma");
    while ($col = $db->fetch_array($res)) {
        echo "{$col['Field']} - {$col['Type']}\n";
    }
    $q = $db->simple_select('rol_akuma', '*');
    echo "Registros en rol_akuma: " . $db->num_rows($q) . "\n";
    while ($r = $db->fetch_array($q)) {
        echo "ID: {$r['id']} | Nombre: {$r['nombre']} | Slug: {$r['slug']} | Rareza: {$r['rareza']} | Tipo: {$r['tipo']}\n";
    }
} else {
    echo "NO EXISTE rol_akuma\n";
}
