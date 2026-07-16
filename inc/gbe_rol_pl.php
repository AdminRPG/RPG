<?php
/**
 * I-Forge · Sistema de Puntos de Leyenda (PL).
 *
 * Los PL se ganan por eventos épicos y se gastan en la tienda de leyenda
 * para obtener ventajas narrativas significativas.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function gbe_pl_saldo($pid) {
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_pl')) {
        return array('pl_total' => 0, 'pl_gastado' => 0, 'pl_disponible' => 0);
    }
    $q = $db->simple_select('rol_pl', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $row = $db->fetch_array($q);
        return array(
            'pl_total'      => (int)$row['pl_total'],
            'pl_gastado'    => (int)$row['pl_gastado'],
            'pl_disponible' => (int)$row['pl_disponible'],
        );
    }
    $db->insert_query('rol_pl', array('pid' => $pid, 'pl_total' => 0, 'pl_gastado' => 0, 'pl_disponible' => 0, 'last_update' => TIME_NOW));
    return array('pl_total' => 0, 'pl_gastado' => 0, 'pl_disponible' => 0);
}

function gbe_pl_add($pid, $pl, $tipo = '', $notas = '') {
    global $db;
    $pid = (int)$pid; $pl = (int)$pl;
    if ($pid < 1 || $pl < 1) return false;
    if (!$db->table_exists('rol_pl_log') || !$db->table_exists('rol_pl')) return false;

    $db->insert_query('rol_pl_log', array(
        'pid' => $pid, 'pl_cambio' => $pl, 'tipo' => $db->escape_string($tipo),
        'notas' => $db->escape_string($notas), 'dateline' => TIME_NOW,
    ));
    $saldo = gbe_pl_saldo($pid);
    $db->update_query('rol_pl', array(
        'pl_total' => $saldo['pl_total'] + $pl,
        'pl_disponible' => $saldo['pl_disponible'] + $pl,
        'last_update' => TIME_NOW,
    ), "pid = {$pid}");
    return true;
}

function gbe_pl_spend($pid, $cost, $tipo = '', $notas = '') {
    global $db;
    $pid = (int)$pid; $cost = (int)$cost;
    if ($pid < 1 || $cost < 1) return false;
    $saldo = gbe_pl_saldo($pid);
    if ($saldo['pl_disponible'] < $cost) return false;

    $db->insert_query('rol_pl_log', array(
        'pid' => $pid, 'pl_cambio' => -$cost, 'tipo' => $db->escape_string($tipo),
        'notas' => $db->escape_string($notas), 'dateline' => TIME_NOW,
    ));
    $db->update_query('rol_pl', array(
        'pl_gastado' => $saldo['pl_gastado'] + $cost,
        'pl_disponible' => $saldo['pl_disponible'] - $cost,
        'last_update' => TIME_NOW,
    ), "pid = {$pid}");
    return true;
}

/**
 * Tienda de PL: qué se puede comprar y cuánto cuesta.
 */
function gbe_pl_tienda() {
    return array(
        'voluntad_d' => array('nombre' => 'Voluntad de D.', 'coste' => 5, 'desc' => 'Añade "D." a tu nombre. +10% de que tus eventos afecten a los Dragones Celestiales.'),
        'fruta_especifica' => array('nombre' => 'Fruta del Diablo específica', 'coste' => 3, 'desc' => 'Elige qué fruta quieres (sujeto a disponibilidad y aprobación).'),
        'linaje_especial' => array('nombre' => 'Linaje Especial', 'coste' => 5, 'desc' => 'Sangre de un linaje único (Dragón Celestial, Tribu Real antigua, etc.).'),
        'arma_suprema' => array('nombre' => 'Arma Suprema (Saijo O Wazamono)', 'coste' => 10, 'desc' => 'Una de las 12 armas de grado supremo.'),
        'haki_haoshoku' => array('nombre' => 'Subir Haoshoku +1 nivel', 'coste' => 3, 'desc' => 'Mejora tu Haoshoku un nivel (requiere tenerlo desbloqueado).'),
    );
}
