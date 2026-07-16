<?php
/**
 * I-Forge · Migración "posteo por personaje"
 * ------------------------------------------
 * Añade a las tablas núcleo de MyBB las columnas que asocian cada mensaje
 * (y el último posteo de hilos/foros) con el PERSONAJE de rol que lo escribió,
 * no sólo con la cuenta (uid). Es idempotente: comprueba SHOW COLUMNS antes de
 * ejecutar cada ALTER, así que se puede re-ejecutar sin efectos secundarios.
 *
 *   mybb_posts.gbe_pid       -> personaje autor de ese mensaje
 *   mybb_threads.gbe_pid     -> personaje autor del hilo (primer mensaje)
 *   mybb_threads.gbe_lastpid -> personaje del último mensaje del hilo
 *   mybb_forums.gbe_lastpid  -> personaje del último mensaje del foro
 *
 * Tras crear las columnas, hace un backfill best-effort de gbe_lastpid en
 * hilos y foros a partir de los gbe_pid de los mensajes existentes.
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" \
 *     "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-post-personaje.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "=== Migración posteo por personaje ===\n";

add_col($db, "{$PREFIX}posts",   'gbe_pid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje de rol autor del mensaje'");
add_col($db, "{$PREFIX}threads", 'gbe_pid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje autor del hilo'");
add_col($db, "{$PREFIX}threads", 'gbe_lastpid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje del último mensaje del hilo'");
add_col($db, "{$PREFIX}forums",  'gbe_lastpid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje del último mensaje del foro'");

// Índices (idempotentes vía IF NOT EXISTS emulado con SHOW INDEX).
function idx_exists(mysqli $db, string $table, string $index): bool
{
    $t = $db->real_escape_string($table);
    $i = $db->real_escape_string($index);
    $res = $db->query("SHOW INDEX FROM `{$t}` WHERE Key_name = '{$i}'");
    return $res && $res->num_rows > 0;
}
if (!idx_exists($db, "{$PREFIX}posts", 'idx_gbe_pid')) {
    if ($db->query("ALTER TABLE `{$PREFIX}posts` ADD INDEX `idx_gbe_pid` (`gbe_pid`)") !== false) {
        echo "  [OK] índice posts.idx_gbe_pid\n";
    }
}

// ─────────────────────────────────────────────────────────────
// Backfill best-effort de gbe_lastpid en hilos y foros.
// Usa el gbe_pid del mensaje visible más reciente de cada hilo/foro.
// ─────────────────────────────────────────────────────────────
echo "\n--- Backfill gbe_lastpid ---\n";

// Hilos: último mensaje visible por tid.
$sqlThreads = "
    UPDATE `{$PREFIX}threads` t
    JOIN (
        SELECT p.tid, p.gbe_pid
        FROM `{$PREFIX}posts` p
        JOIN (
            SELECT tid, MAX(dateline) AS md
            FROM `{$PREFIX}posts`
            WHERE visible = 1
            GROUP BY tid
        ) last ON last.tid = p.tid AND last.md = p.dateline
    ) src ON src.tid = t.tid
    SET t.gbe_lastpid = src.gbe_pid
    WHERE src.gbe_pid > 0
";
if ($db->query($sqlThreads) !== false) {
    echo "  [OK] threads.gbe_lastpid backfilled ({$db->affected_rows} filas)\n";
} else {
    echo "  [warn] backfill threads: " . $db->error . "\n";
}

// Foros: último mensaje visible por fid.
$sqlForums = "
    UPDATE `{$PREFIX}forums` f
    JOIN (
        SELECT p.fid, p.gbe_pid
        FROM `{$PREFIX}posts` p
        JOIN (
            SELECT fid, MAX(dateline) AS md
            FROM `{$PREFIX}posts`
            WHERE visible = 1
            GROUP BY fid
        ) last ON last.fid = p.fid AND last.md = p.dateline
    ) src ON src.fid = f.fid
    SET f.gbe_lastpid = src.gbe_pid
    WHERE src.gbe_pid > 0
";
if ($db->query($sqlForums) !== false) {
    echo "  [OK] forums.gbe_lastpid backfilled ({$db->affected_rows} filas)\n";
} else {
    echo "  [warn] backfill forums: " . $db->error . "\n";
}

echo "\n=== DONE ===\n";
$db->close();
