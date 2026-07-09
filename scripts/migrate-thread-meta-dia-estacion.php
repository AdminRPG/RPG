<?php
/**
 * One Piece Eternal · Migración "día y estación para rol_thread_meta"
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

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    fwrite(STDERR, "DB connection error: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$PREFIX = 'mybb_';

function col_exists(mysqli $db, string $table, string $col): bool
{
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $res = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $res && $res->num_rows > 0;
}

function add_col(mysqli $db, string $table, string $col, string $definition): void
{
    if (col_exists($db, $table, $col)) {
        echo "  [skip] {$table}.{$col} ya existe\n";
        return;
    }
    if ($db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}") === false) {
        fwrite(STDERR, "  [ERROR] {$table}.{$col}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$table}.{$col} añadida\n";
}

echo "=== Migración día/estación para rol_thread_meta ===\n";

$table = $PREFIX . 'rol_thread_meta';
add_col($db, $table, 'fecha_dia', "SMALLINT NULL COMMENT 'día in-rol en estación (1-65)'");
add_col($db, $table, 'estacion',  "VARCHAR(15) NOT NULL DEFAULT '' COMMENT 'Primavera|Verano|Otoño|Invierno'");

echo "=== Listo ===\n";
$db->close();
