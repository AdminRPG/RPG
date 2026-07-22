<?php
/**
 * One Piece: Eternal — columnas PT + progreso Eternal por PJ.
 *   php scripts/migrate-op-eternal.php
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

echo "=== migrate-op-eternal ===\n";
add_col($db, "{$PREFIX}rol_personajes", 'pt_disponibles', "INT NOT NULL DEFAULT 0 COMMENT 'Puntos de Talento disponibles'");
add_col($db, "{$PREFIX}rol_personajes", 'pt_gastados', "INT NOT NULL DEFAULT 0 COMMENT 'PT gastados en nodos Eternal'");
add_col($db, "{$PREFIX}rol_tramites", 'staff_uid', 'INT UNSIGNED NOT NULL DEFAULT 0');
add_col($db, "{$PREFIX}rol_tramites", 'nota_staff', 'TEXT NULL');

$sql = "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pj_eternal` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  arbol VARCHAR(64) NOT NULL,
  nodo_id VARCHAR(96) NOT NULL,
  dateline INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pid_arbol_nodo (pid, arbol, nodo_id),
  KEY idx_pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($db->query($sql) === false) {
    echo "  [FAIL] rol_pj_eternal: {$db->error}\n";
} else {
    echo "  [OK] rol_pj_eternal\n";
}

// Nodos con rango (I, II, III…) ahora son UN solo nodo en los datos (RAZAS/T4.x
// rework de jul-2026): rango_actual guarda cuántos rangos ya compró el PJ de
// ese nodo (1..max), en vez de una fila por rango.
add_col($db, "{$PREFIX}rol_pj_eternal", 'rango_actual', "TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Rango comprado (1..max) para nodos con rango.max > 1'");

echo "=== listo ===\n";
$db->close();
