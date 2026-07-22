<?php
/**
 * One Piece: Eternal — renombra columnas gbe_* → ope_* en MyBB.
 *
 *   php scripts/migrate-gbe-columns-to-ope.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
$PREFIX = 'mybb_';

function col_exists(mysqli $db, string $table, string $col): bool
{
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $r = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $r && $r->num_rows > 0;
}

function rename_col(mysqli $db, string $table, string $from, string $to, string $definition): void
{
    if (!col_exists($db, $table, $from)) {
        if (col_exists($db, $table, $to)) {
            echo "  [skip] {$table}.{$to} ya existe\n";
        } else {
            echo "  [skip] {$table}.{$from} no existe\n";
        }
        return;
    }
    if (col_exists($db, $table, $to)) {
        echo "  [warn] {$table}.{$to} ya existe y {$from} también — revisar a mano\n";
        return;
    }
    $sql = "ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$definition}";
    if ($db->query($sql) === false) {
        echo "  [FAIL] {$sql}\n  {$db->error}\n";
        return;
    }
    echo "  [OK] {$table}.{$from} → {$to}\n";
}

echo "=== Renombre de columnas gbe_* → ope_* ===\n";

rename_col(
    $db,
    "{$PREFIX}posts",
    'gbe_pid',
    'ope_pid',
    "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje de rol autor del mensaje'"
);
rename_col(
    $db,
    "{$PREFIX}threads",
    'gbe_pid',
    'ope_pid',
    "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje autor del hilo'"
);
rename_col(
    $db,
    "{$PREFIX}threads",
    'gbe_lastpid',
    'ope_lastpid',
    "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje del último post'"
);
rename_col(
    $db,
    "{$PREFIX}forums",
    'gbe_lastpid',
    'ope_lastpid',
    "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje del último post del foro'"
);

// Índices posts
$r = $db->query("SHOW INDEX FROM `{$PREFIX}posts` WHERE Key_name = 'idx_gbe_pid'");
if ($r && $r->num_rows > 0) {
    if ($db->query("ALTER TABLE `{$PREFIX}posts` DROP INDEX `idx_gbe_pid`") !== false) {
        echo "  [OK] drop idx_gbe_pid\n";
    }
}
$r = $db->query("SHOW INDEX FROM `{$PREFIX}posts` WHERE Key_name = 'idx_ope_pid'");
if (!$r || $r->num_rows === 0) {
    if (col_exists($db, "{$PREFIX}posts", 'ope_pid')) {
        if ($db->query("ALTER TABLE `{$PREFIX}posts` ADD INDEX `idx_ope_pid` (`ope_pid`)") !== false) {
            echo "  [OK] índice posts.idx_ope_pid\n";
        }
    }
}

echo "=== Listo ===\n";
$db->close();
