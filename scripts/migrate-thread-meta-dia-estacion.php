<?php
/**
 * One Piece: Eternal · Migración "día y estación para rol_thread_meta"
 * ---------------------------------------------------------------------
 * Añade a rol_thread_meta las columnas:
 *   - fecha_dia : día in-rol dentro de la estación (1-65)
 *   - estacion  : estación (Primavera|Verano|Otoño|Invierno o '')
 *
 * Idempotente: comprueba SHOW COLUMNS antes de tocar nada.
 *
 * Ejecutar:
 *   php scripts/migrate-thread-meta-dia-estacion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "=== Migración día/estación para rol_thread_meta ===\n";

$table = $PREFIX . 'rol_thread_meta';
add_col($db, $table, 'fecha_dia', "SMALLINT NULL COMMENT 'día in-rol en estación (1-65)'");
add_col($db, $table, 'estacion',  "VARCHAR(15) NOT NULL DEFAULT '' COMMENT 'Primavera|Verano|Otoño|Invierno'");

echo "=== Listo ===\n";
$db->close();
