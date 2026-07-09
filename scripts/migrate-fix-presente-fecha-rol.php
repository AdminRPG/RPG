<?php
/**
 * Corrige mybb_rol_thread_meta.fecha_rol para hilos de era "presente" que
 * quedaron con el año gregoriano (p.ej. 2026) en vez del año on-rol (I, II, III...),
 * bug introducido antes de que ope_rol_present_year() usara el calendario on-rol.
 *
 * Recalcula fecha_rol (y fecha_dia/estacion si faltan) a partir de la fecha real
 * del post, usando la misma lógica de calendario on-rol que index.php / ficha.php.
 *
 * Uso: php scripts/migrate-fix-presente-fecha-rol.php
 */

define('IN_MYBB', 1);
chdir(__DIR__ . '/..');
require_once './global.php';

if (!$db->table_exists('rol_thread_meta')) {
    echo "No existe rol_thread_meta, nada que hacer.\n";
    exit;
}

$q = $db->query("
    SELECT m.tid, m.fecha_rol, m.fecha_dia, m.estacion, t.dateline
    FROM " . TABLE_PREFIX . "rol_thread_meta m
    INNER JOIN " . TABLE_PREFIX . "threads t ON t.tid = m.tid
    WHERE m.era = 'presente' AND m.fecha_rol > 100
");

$fixed = 0;
while ($row = $db->fetch_array($q)) {
    $cal = ope_rol_onrol_calendar((int) $row['dateline']);
    $db->update_query('rol_thread_meta', array(
        'fecha_rol' => (int) $cal['year'],
        'fecha_dia' => (int) $cal['day'],
        'estacion'  => $db->escape_string((string) $cal['season']),
    ), "tid = " . (int) $row['tid']);
    echo "tid={$row['tid']}: fecha_rol {$row['fecha_rol']} -> {$cal['year']} (dia {$cal['day']}, {$cal['season']})\n";
    $fixed++;
}

echo "Corregidos: $fixed\n";
