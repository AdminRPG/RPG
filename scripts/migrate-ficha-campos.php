<?php
/**
 * One Piece Eternal · Migración "campos de ficha" (rediseño de ficha.php)
 * ---------------------------------------------------------------------
 * Añade a rol_personajes las columnas que necesita la ficha rediseñada:
 *   - rango_faccion : rango del personaje DENTRO de su facción (grumete, capitán…)
 *   - from_fisico   : referencia/faceclaim de dónde viene el físico del personaje
 *   - desc_fisica   : descripción física (crónica)
 *   - personalidad  : personalidad (crónica)
 *
 * Idempotente: comprueba SHOW COLUMNS antes de tocar nada.
 *
 * Ejecutar:
 *   php scripts/migrate-ficha-campos.php
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

function col_exists(mysqli $db, string $table, string $col): bool
{
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $res = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $res && $res->num_rows > 0;
}

function add_col(mysqli $db, string $table, string $col, string $definition): void
{
    if (col_exists($db, $table, $col)) {
        echo "  [skip] {$table}.{$col} ya existe\n";
        return;
    }
    if ($db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}") === false) {
        fwrite(STDERR, "  [ERROR] {$table}.{$col}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$table}.{$col} añadida\n";
}

echo "=== Migración campos de ficha ===\n";

add_col($db, "{$PREFIX}rol_personajes", 'rango_faccion', "VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'rango dentro de la facción (grumete, capitán…)'");
add_col($db, "{$PREFIX}rol_personajes", 'from_fisico',   "VARCHAR(160) NOT NULL DEFAULT '' COMMENT 'referencia/faceclaim del físico'");
add_col($db, "{$PREFIX}rol_personajes", 'desc_fisica',   "TEXT NULL COMMENT 'descripción física (crónica)'");
add_col($db, "{$PREFIX}rol_personajes", 'personalidad',  "TEXT NULL COMMENT 'personalidad (crónica)'");

echo "\n=== DONE ===\n";
$db->close();
