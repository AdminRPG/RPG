<?php
/**
 * I-Forge · Sistema de Wanted (bounty/recompensa).
 *
 * Valor cosmético que refleja la notoriedad del personaje en el mundo.
 * Se muestra formateado (B/M/K) en el perfil y se inicializa según la raza.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_wanted_get($pid) {
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_wanted')) return 0;
    $q = $db->simple_select('rol_wanted', 'bounty', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) return (int)$db->fetch_field($q, 'bounty');
    return 0;
}

function ope_wanted_set($pid, $amount) {
    global $db;
    $pid = (int)$pid; $amount = (int)$amount;
    if ($pid < 1 || !$db->table_exists('rol_wanted')) return false;
    $exist = $db->simple_select('rol_wanted', 'pid', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($exist)) {
        $db->update_query('rol_wanted', array('bounty' => $amount, 'last_update' => TIME_NOW), "pid = {$pid}");
    } else {
        $db->insert_query('rol_wanted', array('pid' => $pid, 'bounty' => $amount, 'last_update' => TIME_NOW));
    }
    return true;
}

function ope_wanted_formatear($bounty) {
    $b = (int)$bounty;
    if ($b >= 1000000000) return number_format($b / 1000000000, 1) . 'B';
    if ($b >= 1000000) return number_format($b / 1000000, 0) . 'M';
    if ($b >= 1000) return number_format($b / 1000, 0) . 'K';
    return (string)$b;
}

/**
 * Inicializa el Wanted según la raza al crear personaje.
 */
function ope_wanted_init_raza($pid, $raza) {
    $iniciales = array(
        'lunarian' => 100000000,
        'bucaneer' => 50000000,
    );
    $amount = isset($iniciales[$raza]) ? $iniciales[$raza] : 0;
    if ($amount > 0) ope_wanted_set($pid, $amount);
    return $amount;
}
