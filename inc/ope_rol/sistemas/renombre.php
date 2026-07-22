<?php
/**
 * One Piece: Eternal · Sistema de Renombre (One Piece: Eternal)
 * --------------------------------------------------------
 * Refleja la reputación y fama del Skyfarer en los cielos.
 * Reemplaza al antiguo sistema de Wanted (Bounty).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Obtiene los puntos de renombre de un personaje.
 */
function ope_renombre_get($pid)
{
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_renombre')) {
        return 0;
    }
    $q = $db->simple_select('rol_renombre', 'puntos', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        return (int)$db->fetch_field($q, 'puntos');
    }
    return 0;
}

/**
 * Actualiza los puntos de renombre de un personaje.
 */
function ope_renombre_set($pid, $amount)
{
    global $db;
    $pid = (int)$pid;
    $amount = max(0, (int)$amount);
    if ($pid < 1 || !$db->table_exists('rol_renombre')) {
        return false;
    }

    $exist = $db->simple_select('rol_renombre', 'pid', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($exist)) {
        $db->update_query('rol_renombre', array('puntos' => $amount, 'last_update' => TIME_NOW), "pid = {$pid}");
    } else {
        $db->insert_query('rol_renombre', array('pid' => $pid, 'puntos' => $amount, 'last_update' => TIME_NOW));
    }
    return true;
}

/**
 * Obtiene el rango o bracket de renombre según los puntos acumulados.
 * Basado en docs/05-SOCIAL/CREWS-Y-RENOMBRE.md.
 */
function ope_renombre_rango($puntos)
{
    $p = (int)$puntos;
    if ($p >= 2500) {
        return 'Símbolo del Cielo';
    }
    if ($p >= 1500) {
        return 'Leyenda del Skydom';
    }
    if ($p >= 1000) {
        return 'Héroe local';
    }
    if ($p >= 600) {
        return 'Famoso';
    }
    if ($p >= 300) {
        return 'Conocido';
    }
    if ($p >= 100) {
        return 'Novato';
    }
    return 'Desconocido';
}

/**
 * Formatea visualmente los puntos de renombre.
 */
function ope_renombre_formatear($puntos)
{
    return number_format((int)$puntos, 0, ',', '.');
}
