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

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

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
