<?php
/**
 * Migracion: Tablon de Misiones en Tramite.
 * ---------------------------------------------------------
 * Crea:
 *   - rol_misiones        : catalogo de misiones escritas por el staff.
 *   - rol_mision_tomas    : solicitudes/asignaciones de mision por PJ (exclusivas).
 *
 * Idempotente. Ejecutar: php scripts/migrate-misiones.php
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

function table_exists(mysqli $db, string $prefix, string $table): bool
{
    $r = $db->query("SHOW TABLES LIKE '{$prefix}{$table}'");
    return $r && $r->num_rows > 0;
}

echo "=== Tabla rol_misiones (catalogo staff) ===\n";
if (!table_exists($db, $PREFIX, 'rol_misiones')) {
    run($db, 'CREATE rol_misiones', "
        CREATE TABLE {$PREFIX}rol_misiones (
            mision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            titulo VARCHAR(300) NOT NULL DEFAULT '',
            resumen MEDIUMTEXT NULL,
            descripcion_larga MEDIUMTEXT NULL,
            zona_slug VARCHAR(40) NOT NULL DEFAULT '',
            facciones VARCHAR(240) NOT NULL DEFAULT '',
            recompensa VARCHAR(500) NOT NULL DEFAULT '',
            rango VARCHAR(8) NOT NULL DEFAULT 'D',
            peligrosidad TINYINT UNSIGNED NOT NULL DEFAULT 1,
            modalidad ENUM('solo','grupo','cualquiera') NOT NULL DEFAULT 'cualquiera',
            estado ENUM('publicada','inactiva') NOT NULL DEFAULT 'publicada',
            uid_autor INT UNSIGNED NOT NULL DEFAULT 0,
            dateline INT UNSIGNED NOT NULL DEFAULT 0,
            lastedit INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (mision_id),
            KEY idx_estado (estado),
            KEY idx_zona (zona_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} else {
    echo "  [=] rol_misiones ya existe\n";
}

echo "\n=== Tabla rol_mision_tomas (solicitudes/asignaciones) ===\n";
if (!table_exists($db, $PREFIX, 'rol_mision_tomas')) {
    run($db, 'CREATE rol_mision_tomas', "
        CREATE TABLE {$PREFIX}rol_mision_tomas (
            toma_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            mision_id BIGINT UNSIGNED NOT NULL,
            pid INT UNSIGNED NOT NULL DEFAULT 0,
            uid INT UNSIGNED NOT NULL DEFAULT 0,
            companeros JSON NULL,
            estado ENUM('pendiente','en_proceso','completada','fallida','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
            motivo VARCHAR(400) NOT NULL DEFAULT '',
            uid_staff INT UNSIGNED NOT NULL DEFAULT 0,
            dateline INT UNSIGNED NOT NULL DEFAULT 0,
            lastedit INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (toma_id),
            KEY idx_mision (mision_id),
            KEY idx_pid (pid),
            KEY idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} else {
    echo "  [=] rol_mision_tomas ya existe\n";
}

echo "\n=== DONE ===\n";
echo "Ejecuta este script una vez para activar el Tablon de Misiones.\n";