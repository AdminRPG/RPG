<?php
/**
 * One Piece: Eternal — Limpieza de Cronología y NPCs
 * ------------------------------------------------
 * Borra los registros de cronología, lore y NPCs (es_npc = 1, npcs secundarios y menores).
 * Conserva personajes jugables (es_npc = 0) y usuarios.
 *
 * Uso:
 *   php scripts/wipe-cronologia-npcs.php              # simulación (dry-run)
 *   php scripts/wipe-cronologia-npcs.php --apply      # ejecutar
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';
$apply = in_array('--apply', $argv, true);

function q(mysqli $db, string $sql, bool $apply): int
{
    if (!$apply) {
        return 0;
    }
    if ($db->query($sql) === false) {
        fwrite(STDERR, "SQL ERROR: {$db->error}\n  $sql\n");
        exit(1);
    }
    return (int)$db->affected_rows;
}

function count_rows(mysqli $db, string $sql): int
{
    $r = $db->query($sql);
    if (!$r) {
        return 0;
    }
    $row = $r->fetch_assoc();
    return (int)($row['c'] ?? 0);
}

function wipe_where(mysqli $db, string $table, string $where, bool $apply): void
{
    global $PREFIX;
    $full = $PREFIX . $table;
    $r = $db->query("SHOW TABLES LIKE '{$full}'");
    if (!$r || $r->num_rows === 0) {
        echo "  [skip] {$full} (no existe)\n";
        return;
    }
    $before = count_rows($db, "SELECT COUNT(*) c FROM `{$full}` WHERE {$where}");
    if ($before === 0) {
        echo "  [=] {$full} ({$where}): vacío\n";
        return;
    }
    if ($apply) {
        q($db, "DELETE FROM `{$full}` WHERE {$where}", true);
        echo "  [-] {$full}: {$before} fila(s) borradas ({$where})\n";
    } else {
        echo "  [~] {$full}: borraría {$before} fila(s) ({$where})\n";
    }
}

function truncate_tbl(mysqli $db, string $table, bool $apply): void
{
    global $PREFIX;
    $full = $PREFIX . $table;
    $r = $db->query("SHOW TABLES LIKE '{$full}'");
    if (!$r || $r->num_rows === 0) {
        echo "  [skip] {$full} (no existe)\n";
        return;
    }
    $before = count_rows($db, "SELECT COUNT(*) c FROM `{$full}`");
    if ($before === 0) {
        echo "  [=] {$full}: vacío\n";
        return;
    }
    if ($apply) {
        q($db, "TRUNCATE TABLE `{$full}`", true);
        echo "  [-] {$full}: truncado ({$before} filas)\n";
    } else {
        echo "  [~] {$full}: truncaría {$before} fila(s)\n";
    }
}

echo "=== Limpieza de Cronología y NPCs — One Piece: Eternal ===\n";
echo $apply ? "MODO: APPLY (ejecutando borrado)\n" : "MODO: dry-run (simulación)\n\n";

echo ">> Resumen pre-limpieza:\n";
echo "  Cronología: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_cronologia") . "\n";
echo "  NPCs mayores (rol_personajes es_npc=1): " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_personajes WHERE es_npc=1") . "\n";
echo "  NPCs secundarios: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_npcs_secundarios") . "\n";
echo "  NPCs menores (Mundo Vivo): " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_mv_npc_menores") . "\n";
echo "  Registros Lore: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_lore") . "\n\n";

echo ">> Limpiando Cronología y Lore...\n";
truncate_tbl($db, 'rol_cronologia', $apply);
truncate_tbl($db, 'rol_lore', $apply);

echo "\n>> Limpiando NPCs...\n";
wipe_where($db, 'rol_personajes', 'es_npc = 1', $apply);
truncate_tbl($db, 'rol_npcs_secundarios', $apply);
truncate_tbl($db, 'rol_mv_npc_menores', $apply);

echo "\n=== " . ($apply ? "Proceso completado exitosamente." : "Dry-run completado. Usa --apply para ejecutar.") . " ===\n";
