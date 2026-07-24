<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_system.php';
require_once MYBB_ROOT . 'inc/ope_rol_eternal.php';
require_once MYBB_ROOT . 'inc/ope_rol_frutas.php';

$pids = array(6, 7, 8, 9, 10);
foreach ($pids as $pid) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}");
    $pj = $db->fetch_array($q);
    $datos = json_decode($pj['datos'], true) ?: array();
    $stats_ef = json_decode($pj['stats_json'], true) ?: array();
    
    echo "=========================================\n";
    echo "FICHA VERIFICATION FOR PID {$pid}: {$pj['nombre']}\n";
    echo "=========================================\n";

    // 1. Check Akuma no Mi block (as loaded by ficha.php)
    $fruta_block = function_exists('ope_fruta_ficha_block') ? ope_fruta_ficha_block((int) $pj['pid'], $stats_ef) : array('tiene' => false);
    echo "AKUMA NO MI: " . ($fruta_block['tiene'] ? "SÍ -> {$fruta_block['fruta']['nombre']} (Nv.{$fruta_block['nivel']}, Pot.{$fruta_block['potencia']})" : "NO (Sin fruta / Haki puro)") . "\n";

    // 2. Check Eternal Picks (as loaded by ficha.php)
    $picks = function_exists('ope_eternal_picks') ? ope_eternal_picks((int) $pj['pid']) : array();
    echo "NODOS ETERNAL POR ÁRBOL:\n";
    foreach ($picks as $arbol => $nlist) {
        echo "  - Árbol '{$arbol}': " . implode(', ', $nlist) . "\n";
    }

    // 3. Check Factor Linaje / Rasgos (as loaded by ficha.php)
    $fl = is_array($datos['factor_linaje'] ?? null) ? $datos['factor_linaje'] : array();
    echo "RASGOS / FACTOR LINAJE (" . count($fl) . " items):\n";
    foreach ($fl as $k => $r) {
        echo "  - {$r['nombre']} [{$r['tipo']}]: {$r['spec']}\n";
    }
    echo "\n";
}
