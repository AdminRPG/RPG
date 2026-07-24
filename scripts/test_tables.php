<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== AKUMA EN BD (rol_akuma) ===\n";
if ($db->table_exists('rol_akuma')) {
    $q = $db->simple_select('rol_akuma', '*');
    echo "Total Akuma en rol_akuma: " . $db->num_rows($q) . "\n";
    while ($r = $db->fetch_array($q)) {
        echo "ID: {$r['id']} | Nombre: {$r['nombre']} | Slug: {$r['slug']} | Tipo: {$r['tipo']} | Ocupada: " . ($r['ocupada_pid'] ?? 0) . "\n";
    }
} else {
    echo "Tabla rol_akuma NO existe.\n";
}

echo "\n=== FRUTAS ASIGNADAS EN BD (rol_pj_fruta) ===\n";
if ($db->table_exists('rol_pj_fruta')) {
    $q = $db->simple_select('rol_pj_fruta', '*');
    echo "Total Asignaciones en rol_pj_fruta: " . $db->num_rows($q) . "\n";
    while ($r = $db->fetch_array($q)) {
        echo "PID: {$r['pid']} | Fruta ID: {$r['fruta_id']} | Nivel: {$r['nivel']} | CU: {$r['cu']}\n";
    }
}

echo "\n=== ETERNAL TREES ARCHIVOS / SISTEMA ===\n";
require_once MYBB_ROOT . 'inc/ope_rol_eternal.php';
$trees = ope_eternal_tree_ids();
foreach ($trees as $tid) {
    $t = ope_eternal_load($tid);
    echo "\nÁrbol '{$tid}' ({$t['nombre']}):\n";
    foreach ($t['nodos'] as $n) {
        echo "   - [{$n['id']}] {$n['label']} (Nivel: {$n['nivel']}, Pos: {$n['pos']})\n";
    }
}
