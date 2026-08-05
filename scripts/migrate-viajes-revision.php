<?php
/**
 * Migracion: Sistema de revision staff de cierres de viaje.
 * ---------------------------------------------------------
 * Amplia el ENUM estado en rol_viajes y anhade columnas de revision.
 *
 * Idempotente. Ejecutar: php scripts/migrate-viajes-revision.php
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

echo "=== Ampliar ENUM estado en rol_viajes ===\n";
run($db, 'MODIFY rol_viajes.estado', "
    ALTER TABLE {$PREFIX}rol_viajes
    MODIFY COLUMN estado ENUM('activo','cerrado','cancelado','pendiente_cierre') NOT NULL DEFAULT 'activo'
");

echo "\n=== Columnas de revision en rol_viajes ===\n";
$cols = array(
    'revision_intentos'   => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN revision_intentos TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER plazo_dias",
    'revision_ai_json'    => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN revision_ai_json TEXT NULL AFTER revision_intentos",
    'revision_staff_uid'  => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN revision_staff_uid INT UNSIGNED NOT NULL DEFAULT 0 AFTER revision_ai_json",
    'revision_motivo'      => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN revision_motivo TEXT NULL AFTER revision_staff_uid",
    'revision_dateline'   => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN revision_dateline INT UNSIGNED NOT NULL DEFAULT 0 AFTER revision_motivo",
);
foreach ($cols as $col => $sql) {
    if (!col_exists($db, $PREFIX, 'rol_viajes', $col)) {
        run($db, "ADD rol_viajes.$col", $sql);
    } else {
        echo "  [=] rol_viajes.$col ya existe\n";
    }
}

echo "\n=== DONE ===\n";
echo "Ejecuta este script una vez para activar el sistema de revision de cierres.\n";
