<?php
// Inspección READ-ONLY temporal para el inventario de F0. No modifica nada.
require __DIR__ . '/_db-config.php';

$r = $db->query('SHOW TABLES');
$tablas = [];
while ($row = $r->fetch_array()) {
    $tablas[] = $row[0];
}
echo count($tablas) . " tablas totales\n";
echo "--- tablas relevantes (rol_/ope_/sistema) ---\n";
foreach ($tablas as $x) {
    if (preg_match('/rol_|ope_|islas|mares|faccion|akuma|haki|mundo|viaje|traves|tramite|personaje|barco|tripulac|mision|estado|combate|tema|ruido/', $x)) {
        echo $x . "\n";
    }
}
echo "--- columnas de mybb_rol_personajes ---\n";
$res = $db->query('SHOW COLUMNS FROM mybb_rol_personajes');
if ($res) {
    while ($col = $res->fetch_assoc()) {
        echo $col['Field'] . " " . $col['Type'] . "\n";
    }
}
echo "--- columnas de mybb_rol_tramites ---\n";
$res = $db->query('SHOW COLUMNS FROM mybb_rol_tramites');
if ($res) {
    while ($col = $res->fetch_assoc()) {
        echo $col['Field'] . " " . $col['Type'] . "\n";
    }
}
$db->close();
