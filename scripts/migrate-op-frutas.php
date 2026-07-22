<?php
/**
 * One Piece: Eternal — fruta por PJ + unicidad en catálogo.
 *   php scripts/migrate-op-frutas.php
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

echo "=== migrate-op-frutas ===\n";

$sql = "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pj_fruta` (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  fruta_id INT UNSIGNED NOT NULL,
  nivel TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 Manifestación .. 3 Despertar',
  cu INT UNSIGNED NOT NULL DEFAULT 0,
  pp_gastado INT UNSIGNED NOT NULL DEFAULT 0,
  origen VARCHAR(20) NOT NULL DEFAULT 'roll' COMMENT 'roll|pd|trama|staff',
  potencia_sec VARCHAR(8) NOT NULL DEFAULT '' COMMENT 'stat secundario (INT/FUE/VOL…)',
  fecha_despertar INT UNSIGNED NOT NULL DEFAULT 0,
  dateline INT UNSIGNED NOT NULL DEFAULT 0,
  lastedit INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pid (pid),
  KEY idx_fruta (fruta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($db->query($sql) === false) {
    echo "  [FAIL] rol_pj_fruta: {$db->error}\n";
} else {
    echo "  [OK] rol_pj_fruta\n";
}

// Unicidad mundial: qué PJ tiene la fruta activa (0 = libre).
add_col($db, "{$PREFIX}rol_akuma", 'ocupada_pid', "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'pid dueño activo; 0=libre'");
add_col($db, "{$PREFIX}rol_akuma", 'tier', "TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'I-V rareza mecánica'");

echo "=== listo ===\n";
$db->close();
