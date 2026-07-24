<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$q = $db->query("SELECT pid, nombre, es_npc, estado, faccion_slug, datos, rango_faccion FROM " . TABLE_PREFIX . "rol_personajes ORDER BY pid ASC");
echo "TOTAL ROWS IN rol_personajes: " . $db->num_rows($q) . "\n\n";

while ($r = $db->fetch_array($q)) {
    $datos = json_decode($r['datos'], true) ?: array();
    echo "PID #{$r['pid']} | Nombre: {$r['nombre']} | es_npc: {$r['es_npc']} | Estado: {$r['estado']}\n";
    echo "   faccion_slug DB col: '" . ($r['faccion_slug'] ?? 'N/A') . "'\n";
    echo "   datos['faccion']: '" . ($datos['faccion'] ?? 'N/A') . "'\n";
    echo "   rango_faccion: '" . ($r['rango_faccion'] ?? 'N/A') . "'\n";
    echo "---------------------------------------------------------\n";
}
