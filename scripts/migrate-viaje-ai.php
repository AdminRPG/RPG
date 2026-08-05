<?php
/**
 * Migración: Introducción narrativa de viaje generada por IA.
 * ------------------------------------------------------------
 * Añade a `rol_viajes`:
 *   - introduccion_api       TEXT NULL   (texto generado por la IA)
 *   - introduccion_ai_modelo VARCHAR(60) (modelo que lo generó)
 *
 * Idempotente. Ejecutar: php scripts/migrate-viaje-ai.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
    } else {
        echo "  [OK] $label\n";
    }
}

function col_exists(mysqli $db, string $prefix, string $table, string $col): bool
{
    $r = $db->query("SHOW COLUMNS FROM {$prefix}{$table} LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}

echo "=== Columnas IA en rol_viajes ===\n";
$cols = array(
    'introduccion_api'       => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN introduccion_api TEXT NULL AFTER resultado_json",
    'introduccion_ai_modelo' => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN introduccion_ai_modelo VARCHAR(60) NOT NULL DEFAULT '' AFTER introduccion_api",
);
foreach ($cols as $col => $sql) {
    if (!col_exists($db, $PREFIX, 'rol_viajes', $col)) {
        run($db, "ADD rol_viajes.$col", $sql);
    } else {
        echo "  [=] rol_viajes.$col ya existe\n";
    }
}

echo "\n=== DONE ===\n";
echo "Columnas añadidas: introduccion_api, introduccion_ai_modelo\n";
