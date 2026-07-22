<?php
/**
 * One Piece: Eternal — tabla rol_haki (Ken / Buso / Hao).
 *   php scripts/migrate-op-haki.php
 *
 * Recrea el esquema canónico (SISTEMA-DE-HAKI.md). Si existe una tabla
 * legacy de oleada0, se altera de forma idempotente.
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

function add_col(mysqli $db, string $table, string $col, string $def): void
{
    if (col_exists($db, $table, $col)) {
        echo "  [skip] {$table}.{$col}\n";
        return;
    }
    if ($db->query("ALTER TABLE `{$table}` ADD COLUMN {$col} {$def}") === false) {
        echo "  [FAIL] {$table}.{$col}: {$db->error}\n";
        return;
    }
    echo "  [OK] {$table}.{$col}\n";
}

echo "=== migrate-op-haki ===\n";

$sql = "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_haki` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  tipo VARCHAR(16) NOT NULL COMMENT 'ken|buso|hao',
  nivel TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=sin dominio comprado; 1..6',
  cu INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'cartas jugadas del tipo',
  pp_gastado INT UNSIGNED NOT NULL DEFAULT 0,
  despertado TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Hao: 1 tras tirada/PD exitosa',
  origen VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'tirada|pd|staff|autoservicio',
  dateline INT UNSIGNED NOT NULL DEFAULT 0,
  lastedit INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pid_tipo (pid, tipo),
  KEY idx_pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($db->query($sql) === false) {
    echo "  [FAIL] rol_haki create: {$db->error}\n";
} else {
    echo "  [OK] rol_haki\n";
}

add_col($db, "{$PREFIX}rol_haki", 'cu', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'cartas jugadas del tipo'");
add_col($db, "{$PREFIX}rol_haki", 'despertado', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Hao despertado'");
add_col($db, "{$PREFIX}rol_haki", 'origen', "VARCHAR(20) NOT NULL DEFAULT ''");
add_col($db, "{$PREFIX}rol_haki", 'dateline', "INT UNSIGNED NOT NULL DEFAULT 0");
add_col($db, "{$PREFIX}rol_haki", 'lastedit', "INT UNSIGNED NOT NULL DEFAULT 0");

echo "=== listo ===\n";
$db->close();
