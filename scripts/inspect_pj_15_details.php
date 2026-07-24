<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$pid = 15;
$q = $db->simple_select('rol_personajes', '*', "pid = {$pid}");
$pj = $db->fetch_array($q);

echo "=========================================\n";
echo "INSPECTING PID 15: {$pj['nombre']}\n";
echo "=========================================\n";
echo "Rango: {$pj['rango']}\n";
echo "Nivel: {$pj['nivel']}\n";
echo "Estado: {$pj['estado']}\n";
echo "Rango Facción: {$pj['rango_faccion']}\n";
echo "Físico: {$pj['from_fisico']}\n";

$datos = json_decode((string)$pj['datos'], true) ?: array();
echo "DATOS JSON:\n";
echo "  raza_principal: " . ($datos['raza_principal'] ?? 'N/A') . "\n";
echo "  faccion: " . ($datos['faccion'] ?? 'N/A') . "\n";
echo "  arbol_identidad: " . ($datos['arbol_identidad'] ?? 'N/A') . "\n";
echo "  arbol_arma: " . ($datos['arbol_arma'] ?? 'N/A') . "\n";
echo "  fruta_id: " . ($datos['fruta_id'] ?? 'N/A') . "\n";
echo "  fruta_name: " . ($datos['fruta_nombre'] ?? 'N/A') . "\n";

echo "FACTOR LINAJE (count: " . count($datos['factor_linaje'] ?? array()) . "):\n";
print_r($datos['factor_linaje'] ?? array());

// Akuma no Mi in rol_pj_fruta
$fq = $db->query("SELECT f.*, a.nombre, a.tipo, a.descripcion FROM mybb_rol_pj_fruta f LEFT JOIN mybb_rol_akuma a ON a.id = f.fruta_id WHERE f.pid = {$pid}");
if ($db->num_rows($fq) > 0) {
    $fr = $db->fetch_array($fq);
    echo "AKUMA IN rol_pj_fruta: ID {$fr['fruta_id']} -> {$fr['nombre']} (Tipo: {$fr['tipo']}, Nivel: {$fr['nivel']})\n";
} else {
    echo "AKUMA IN rol_pj_fruta: NINGUNA\n";
}

// Eternal picks in rol_pj_eternal
$eq = $db->simple_select('rol_pj_eternal', '*', "pid = {$pid}");
echo "ETERNAL PICKS IN rol_pj_eternal (count: " . $db->num_rows($eq) . "):\n";
while ($er = $db->fetch_array($eq)) {
    echo "  - Árbol '{$er['arbol']}': {$er['nodo_id']}\n";
}
