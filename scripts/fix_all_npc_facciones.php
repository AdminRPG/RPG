<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$map_alias = array(
    'marine' => 'marines',
    'marina' => 'marines',
    'pirata' => 'piratas',
    'piratas' => 'piratas',
    'gobierno' => 'gobierno-mundial',
    'gobierno-mundial' => 'gobierno-mundial',
    'cazarrecompensas' => 'cazarrecompensas',
    'civil' => 'civiles',
    'civiles' => 'civiles',
);

$q = $db->simple_select('rol_personajes', '*', "es_npc = 1");
while ($pj = $db->fetch_array($q)) {
    $pid = (int) $pj['pid'];
    $datos = json_decode((string) $pj['datos'], true) ?: array();
    $curr_fac = strtolower(trim((string) ($datos['faccion'] ?? '')));
    $norm_fac = isset($map_alias[$curr_fac]) ? $map_alias[$curr_fac] : $curr_fac;
    
    $datos['faccion'] = $norm_fac;
    $new_json = json_encode($datos, JSON_UNESCAPED_UNICODE);
    
    $db->update_query('rol_personajes', array('datos' => $db->escape_string($new_json)), "pid = {$pid}");
    echo "Updated PID #{$pid} ({$pj['nombre']}): faccion '{$curr_fac}' -> '{$norm_fac}'\n";
}
