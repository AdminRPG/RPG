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

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    fwrite(STDERR, "DB connection error: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$PREFIX = 'mybb_';

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
        exit(1);
    }
    echo "  [ok] $label\n";
}

echo "=== Migrando {$PREFIX}rol_forum_meta ===\n";
run($db, 'CREATE TABLE rol_forum_meta', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_forum_meta (
        fid SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
        dueno VARCHAR(180) NULL,
        clima VARCHAR(180) NULL,
        zonas JSON NULL,
        anotaciones TEXT NULL,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        lastedit INT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "\n=== DONE ===\n";
