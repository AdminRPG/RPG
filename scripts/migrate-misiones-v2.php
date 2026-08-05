<?php
/**
 * Migracion v2: añade columnas de hilo + IA a rol_mision_tomas.
 * ---------------------------------------------------------
 * Idempotente. Ejecutar: php scripts/migrate-misiones-v2.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

function table_exists(mysqli $db, string $prefix, string $table): bool
{
    $r = $db->query("SHOW TABLES LIKE '{$prefix}{$table}'");
    return $r && $r->num_rows > 0;
}

function col_exists(mysqli $db, string $prefix, string $table, string $col): bool
{
    $r = $db->query("SHOW COLUMNS FROM {$prefix}{$table} LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
    } else {
        echo "  [OK] $label\n";
    }
}

echo "=== Migracion Misiones v2: columnas de hilo + IA ===\n";

if (!table_exists($db, $PREFIX, 'rol_mision_tomas')) {
    echo "  [!] rol_mision_tomas no existe. Ejecuta migrate-misiones.php primero.\n";
    exit(1);
}

$cols = array(
    'tid'                  => "ALTER TABLE {$PREFIX}rol_mision_tomas ADD COLUMN tid BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER estado",
    'oraculo_json'         => "ALTER TABLE {$PREFIX}rol_mision_tomas ADD COLUMN oraculo_json TEXT NULL AFTER tid",
    'introduccion_api'     => "ALTER TABLE {$PREFIX}rol_mision_tomas ADD COLUMN introduccion_api TEXT NULL AFTER oraculo_json",
    'introduccion_ai_modelo'=> "ALTER TABLE {$PREFIX}rol_mision_tomas ADD COLUMN introduccion_ai_modelo VARCHAR(80) NOT NULL DEFAULT '' AFTER introduccion_api",
);

foreach ($cols as $col => $sql) {
    echo "  columna '$col': ";
    if (col_exists($db, $PREFIX, 'rol_mision_tomas', $col)) {
        echo "ya existe\n";
    } else {
        run($db, "ADD $col", $sql);
    }
}

echo "\n=== DONE ===\n";
echo "Columnas tid, oraculo_json, introduccion_api, introduccion_ai_modelo listas.\n";
