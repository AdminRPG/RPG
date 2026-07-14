<?php
define('IN_MYBB', 1);
require_once './global.php';

$query = $db->simple_select('rol_personajes', 'pid, nombre, es_npc, rango, nivel, datos_publicos, datos_internos', 'es_npc = 1');
$npcs = [];
while ($row = $db->fetch_array($query)) {
    $npcs[] = [
        'pid' => $row['pid'],
        'nombre' => $row['nombre'],
        'rango' => $row['rango'],
        'nivel' => $row['nivel'],
        'pub_null' => is_null($row['datos_publicos']) || trim($row['datos_publicos']) === '' || $row['datos_publicos'] === 'null',
        'int_null' => is_null($row['datos_internos']) || trim($row['datos_internos']) === '' || $row['datos_internos'] === 'null'
    ];
}
echo json_encode($npcs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
