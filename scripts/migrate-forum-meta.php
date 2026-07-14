<?php
/**
 * I-Forge · Migración de metadatos de foro (mybb_rol_forum_meta)
 * -----------------------------------------------------------------
 * Tabla auxiliar (1:1 con mybb_forums por fid) para la ficha enriquecida
 * de regiones/islas de "El Mundo": dueño actual, clima, zonas y
 * anotaciones del taller. Todo editable vía SQL/scripts hasta que exista
 * un módulo de Admin CP dedicado.
 *
 * Idempotente: CREATE TABLE IF NOT EXISTS, se puede re-ejecutar.
 *
 * Ejecutar:
 *   & "C:\Users\Fgonz\php\php.exe" scripts/migrate-forum-meta.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "=== Migrando {$PREFIX}rol_forum_meta ===\n";
$sql = "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_forum_meta (
        fid SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
        dueno VARCHAR(180) NULL,
        clima VARCHAR(180) NULL,
        zonas JSON NULL,
        anotaciones TEXT NULL,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        lastedit INT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] rol_forum_meta: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] rol_forum_meta\n";

echo "\n=== DONE ===\n";
