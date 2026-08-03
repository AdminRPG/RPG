<?php
/**
 * One Piece: Eternal — Migración a Clases/Oficios v4 (elimina Sistema Eternal).
 *   php scripts/migrate-op-vocaciones.php
 *
 * 1. Crea tabla mybb_rol_pj_vocaciones (clase, oficios, arma, elecciones).
 * 2. DROP tabla mybb_rol_pj_eternal (árboles de nodos).
 * 3. DROP columnas legacy pt_disponibles / pt_gastados de rol_personajes.
 * 4. Wipe datos existentes (rol_personajes, rol_pj_fruta, trámites de creación).
 *
 * Idempotente: se puede re-ejecutar sin efectos secundarios.
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

function table_exists(mysqli $db, string $table): bool
{
    $t = $db->real_escape_string($table);
    $r = $db->query("SHOW TABLES LIKE '{$t}'");
    return $r && $r->num_rows > 0;
}

function drop_col(mysqli $db, string $table, string $col): void
{
    if (!col_exists($db, $table, $col)) {
        echo "  [skip] {$table}.{$col} (no existe)\n";
        return;
    }
    if ($db->query("ALTER TABLE `{$table}` DROP COLUMN `{$col}`") === false) {
        echo "  [FAIL] DROP {$table}.{$col}: {$db->error}\n";
        return;
    }
    echo "  [OK] DROP {$table}.{$col}\n";
}

echo "=== migrate-op-vocaciones (Clases/Oficios v4) ===\n\n";

// ─────────────────────────────────────────────────────────────
// 1. Crear tabla mybb_rol_pj_vocaciones
// ─────────────────────────────────────────────────────────────
echo "── 1. Tabla rol_pj_vocaciones ──\n";
$sql = "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pj_vocaciones` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pid INT UNSIGNED NOT NULL,
  clase VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'key de ope_rol_clases()',
  oficios JSON NULL COMMENT '[\"cocinero\",\"medico\"] — 1 a 2 oficios',
  arma VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'key de ope_rol_armas_vocacionales()',
  elecciones JSON NULL COMMENT '{\"10\":\"Demolición\",\"20\":\"Impetuoso\",...}',
  arquetipo_clase VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'segunda clase (Nv.30+)',
  dateline INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pid (pid),
  KEY idx_clase (clase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($db->query($sql) === false) {
    echo "  [FAIL] rol_pj_vocaciones: {$db->error}\n";
    exit(1);
} else {
    echo "  [OK] rol_pj_vocaciones\n";
}

// ─────────────────────────────────────────────────────────────
// 2. DROP tabla legacy mybb_rol_pj_eternal
// ─────────────────────────────────────────────────────────────
echo "\n── 2. DROP tabla rol_pj_eternal ──\n";
if (table_exists($db, "{$PREFIX}rol_pj_eternal")) {
    if ($db->query("DROP TABLE `{$PREFIX}rol_pj_eternal`") === false) {
        echo "  [FAIL] DROP rol_pj_eternal: {$db->error}\n";
    } else {
        echo "  [OK] DROP rol_pj_eternal\n";
    }
} else {
    echo "  [skip] rol_pj_eternal (no existe)\n";
}

// ─────────────────────────────────────────────────────────────
// 3. DROP columnas legacy de rol_personajes
// ─────────────────────────────────────────────────────────────
echo "\n── 3. DROP columnas legacy ──\n";
$pj_table = "{$PREFIX}rol_personajes";
if (table_exists($db, $pj_table)) {
    drop_col($db, $pj_table, 'pt_disponibles');
    drop_col($db, $pj_table, 'pt_gastados');
} else {
    echo "  [skip] tabla {$pj_table} no existe\n";
}

// ─────────────────────────────────────────────────────────────
// 4. Wipe datos existentes
// ─────────────────────────────────────────────────────────────
echo "\n── 4. Wipe datos existentes ──\n";
$wipe_tables = array(
    "{$PREFIX}rol_personajes"     => null,
    "{$PREFIX}rol_pj_fruta"       => null,
    "{$PREFIX}rol_pj_vocaciones"  => null,
);
foreach ($wipe_tables as $tbl => $where) {
    if (!table_exists($db, $tbl)) {
        echo "  [skip] {$tbl} (no existe)\n";
        continue;
    }
    if ($where === null) {
        if ($db->query("TRUNCATE TABLE `{$tbl}`") === false) {
            echo "  [FAIL] TRUNCATE {$tbl}: {$db->error}\n";
        } else {
            echo "  [OK] TRUNCATE {$tbl}\n";
        }
    } else {
        if ($db->query("DELETE FROM `{$tbl}` WHERE {$where}") === false) {
            echo "  [FAIL] DELETE {$tbl}: {$db->error}\n";
        } else {
            echo "  [OK] DELETE {$tbl} ({$db->affected_rows} rows)\n";
        }
    }
}
// Trámites de creación
$tram_tbl = "{$PREFIX}rol_tramites";
if (table_exists($db, $tram_tbl)) {
    if ($db->query("DELETE FROM `{$tram_tbl}` WHERE tipo = 'crear_personaje'") === false) {
        echo "  [FAIL] DELETE trámites crear_personaje: {$db->error}\n";
    } else {
        echo "  [OK] DELETE trámites crear_personaje ({$db->affected_rows} rows)\n";
    }
} else {
    echo "  [skip] {$tram_tbl} (no existe)\n";
}

echo "\n=== migrate-op-vocaciones DONE ===\n";
$db->close();
