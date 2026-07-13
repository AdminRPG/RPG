<?php
/**
 * I-Forge · Migración del Calendario On-Rol (mybb_rol_calendario)
 * ---------------------------------------------------------------
 * Crea la tabla que almacena las anotaciones del staff sobre los días
 * del calendario on-rol (4 estaciones × 65 días = 260 días/año).
 *
 * Idempotente (CREATE TABLE IF NOT EXISTS).
 *
 * Ejecutar:  php scripts/migrate-calendario-onrol.php
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

$sqls = [
    "rol_calendario" => "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_calendario` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `anio`        INT UNSIGNED NOT NULL DEFAULT 1,
        `dia`         TINYINT UNSIGNED NOT NULL COMMENT '1-65 (día dentro de la estación)',
        `estacion`    ENUM('Primavera','Verano','Otoño','Invierno') NOT NULL,
        `dato`        TEXT NULL COMMENT 'Anotación del staff sobre este día',
        `autor_pid`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'pid del personaje staff que escribió',
        `dateline`    INT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY `uq_dia_estacion_anio` (`anio`,`dia`,`estacion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($sqls as $name => $sql) {
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$name}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$name}\n";
}

echo "\nMigración del calendario on-rol completada.\n";
$db->close();
