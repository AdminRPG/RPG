<?php
/**
 * Migracion: eliminar columnas huerfanas de Mundo Vivo v2 → v3
 *
 * - mybb_rol_mv_zonas.eco       → reemplazada por riq
 * - mybb_rol_mv_facciones.inf   → reemplazada por pol
 *
 * Idempotente: solo elimina si existe.
 * Ejecutar: php scripts/migrate-drop-orphan-columns.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

echo "== Eliminar columnas huerfanas v2 ==\n";

$tablas = array(
    'rol_mv_zonas'     => array('eco'),
    'rol_mv_facciones' => array('inf'),
);

foreach ($tablas as $tabla => $cols) {
    $full = TABLE_PREFIX . $tabla;
    foreach ($cols as $col) {
        if ($db->field_exists($col, $tabla)) {
            $db->write_query("ALTER TABLE {$full} DROP COLUMN {$col}");
            echo "[+] {$tabla}.{$col} eliminada.\n";
        } else {
            echo "[=] {$tabla}.{$col} ya no existe.\n";
        }
    }
}

echo "\nMigracion completada.\n";
