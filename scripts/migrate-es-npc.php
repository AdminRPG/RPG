<?php
/**
 * I-Forge · Migracion NPCs + soft-delete
 * ---------------------------------------
 * Añade la columna es_npc a rol_personajes y el estado 'eliminado'
 * al ENUM de estado para el soft-delete de expedientes.
 *
 * Idempotente: comprueba SHOW COLUMNS antes de tocar nada.
 *
 * Ejecutar:
 *   php scripts/migrate-es-npc.php
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

echo "=== Migracion NPCs + soft-delete ===\n";

add_col($db, "{$PREFIX}rol_personajes", 'es_npc', "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = personaje NPC (no jugador)'");

echo "\n--- Añadiendo 'eliminado' al ENUM estado ---\n";
$table = $PREFIX . 'rol_personajes';
$res = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'estado'");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $type = $row['Type'];
    if (strpos($type, 'eliminado') === false) {
        $newType = str_replace("'rechazado')", "'rechazado','eliminado')", $type);
        if ($db->query("ALTER TABLE `{$table}` MODIFY COLUMN `estado` {$newType} NOT NULL DEFAULT 'borrador'") === false) {
            fwrite(STDERR, "  [ERROR] modificando ENUM estado: " . $db->error . "\n");
            exit(1);
        }
        echo "  [OK] 'eliminado' añadido al ENUM estado\n";
    } else {
        echo "  [skip] 'eliminado' ya existe en el ENUM estado\n";
    }
}

echo "\n=== DONE ===\n";
$db->close();
