<?php
/**
 * One Piece: Eternal · Tarea programada MyBB: publica los viajes encolados.
 *
 * Se dispara con la cadencia configurada en la tabla `tasks` (file = ope_viajes).
 * Procesa UN viaje pendiente por pasada para no alargar la carga de la página
 * que dispara la tarea. Los hilos se publican en Alta Mar y se notifica por
 * la campana a capitán y tripulantes.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function task_ope_viajes($task)
{
    global $db;

    if (!$db->table_exists('rol_viajes_cola')) {
        return true;
    }

    // Asegurar que el módulo de viajes está cargado (bootstrap del plugin).
    if (!function_exists('ope_viaje_procesar_cola')) {
        $boot = MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
        if (is_file($boot)) {
            require_once $boot;
        }
    }

    if (function_exists('ope_viaje_procesar_cola')) {
        ope_viaje_procesar_cola();
    }

    // Limpieza: flashes ya leídos con más de 7 días.
    if ($db->table_exists('rol_flash')) {
        $db->delete_query('rol_flash', "leido = 1 AND dateline < " . (TIME_NOW - 7 * 86400));
    }

    return true;
}
