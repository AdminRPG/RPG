<?php
/**
 * Migración: membresía de tripulaciones (jugador ↔ rol_tripulaciones).
 *
 *   php scripts/migrate-tripulacion-miembros.php
 */
define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

function run($db, $label, $sql)
{
    $db->write_query($sql);
    echo "  [OK] {$label}\n";
}

echo "=== Migración tripulación · miembros ===\n";

run($db, 'rol_tripulacion_miembros', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_tripulacion_miembros (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tripulacion_id INT UNSIGNED NOT NULL,
    pid INT UNSIGNED NOT NULL,
    uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'cuenta MyBB al unirse',
    rol VARCHAR(20) NOT NULL DEFAULT 'tripulante' COMMENT 'capitan|tripulante',
    estado VARCHAR(20) NOT NULL DEFAULT 'activo' COMMENT 'activo|baja',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_pid (pid),
    KEY idx_trip (tripulacion_id),
    KEY idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "\n=== DONE ===\n";
