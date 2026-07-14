<?php
/**
 * One Piece Eternal · Migración "cronología por personaje"
 * --------------------------------------------------------
 * Crea la tabla que guarda la DESCRIPCIÓN que un personaje le pone a cada tema
 * de su línea de tiempo. La descripción es por (personaje, tema), de ahí la PK
 * compuesta. Idempotente: CREATE TABLE IF NOT EXISTS.
 *
 *   mybb_rol_cronologia(pid, tid, descripcion, dateline)  PK(pid, tid)
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" \
 *     "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-cronologia.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración cronología por personaje ===\n";

$sql = "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_cronologia` (
        pid         INT UNSIGNED NOT NULL,
        tid         INT UNSIGNED NOT NULL,
        descripcion TEXT NULL,
        dateline    INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (pid, tid),
        KEY idx_tid (tid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] rol_cronologia: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] rol_cronologia\n";

echo "\n=== DONE ===\n";
$db->close();
