<?php
/**
 * Migración Oleada 0 — Stats JSON numérico + Haki + PL + Wanted.
 *
 * Idempotente: comprueba existencia antes de alterar/crear.
 *
 * Qué hace:
 *   1. Añade stats_json, nivel, ps_gastados a rol_personajes.
 *   2. Crea tablas rol_haki, rol_pl, rol_pl_log, rol_wanted.
 *   3. Migra stats_efectivas de letra (F-M+) a numérico (5-30+) → stats_json.
 *   4. Actualiza columna nivel = FLOOR(suma_stats / 10).
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" ^
 *     "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-oleada0.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

function oleada0_run(mysqli $db, string $sql, string $label): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$label}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$label}\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. Nuevas columnas en rol_personajes
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 1. COLUMNAS ROL_PERSONAJES ===\n";

add_col($db, "{$PREFIX}rol_personajes", 'stats_json',
    "TEXT NULL DEFAULT NULL COMMENT 'Stats en formato {\"FUE\":8,\"DES\":7,...} valores 5-100+'");
add_col($db, "{$PREFIX}rol_personajes", 'nivel',
    "INT NOT NULL DEFAULT 0 COMMENT 'Nivel = floor(suma_stats / 10)'");
add_col($db, "{$PREFIX}rol_personajes", 'ps_gastados',
    "INT NOT NULL DEFAULT 0 COMMENT 'PS usados en creación'");

// ═══════════════════════════════════════════════════════════════════════════
// 2. Tablas nuevas
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 2. TABLAS NUEVAS ===\n";

oleada0_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_haki` (
        `id`        INT PRIMARY KEY AUTO_INCREMENT,
        `pid`       INT NOT NULL,
        `tipo`      VARCHAR(20) NOT NULL COMMENT 'busoshoku|kenbunshoku|haoshoku',
        `nivel`     TINYINT NOT NULL DEFAULT 0 COMMENT '0=no desbloqueado, 1-4',
        `pp_gastado` INT NOT NULL DEFAULT 0,
        `unlocked_at` DATETIME NULL,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_pid_tipo` (`pid`, `tipo`),
        INDEX `idx_pid` (`pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", 'rol_haki');

oleada0_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pl` (
        `pid`            INT PRIMARY KEY,
        `pl_total`       INT NOT NULL DEFAULT 0,
        `pl_gastado`     INT NOT NULL DEFAULT 0,
        `pl_disponible`  INT NOT NULL DEFAULT 0,
        `last_update`    INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", 'rol_pl');

oleada0_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pl_log` (
        `log_id`     INT PRIMARY KEY AUTO_INCREMENT,
        `pid`        INT NOT NULL,
        `pl_cambio`  INT NOT NULL,
        `tipo`       VARCHAR(30) NOT NULL DEFAULT '',
        `notas`      VARCHAR(255) NOT NULL DEFAULT '',
        `dateline`   INT NOT NULL DEFAULT 0,
        INDEX `idx_pid` (`pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", 'rol_pl_log');

oleada0_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_wanted` (
        `pid`         INT PRIMARY KEY,
        `bounty`      BIGINT NOT NULL DEFAULT 0 COMMENT 'Recompensa en berries (cosmético)',
        `last_update` INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", 'rol_wanted');

// ═══════════════════════════════════════════════════════════════════════════
// 3. Migración de stats (F-M+ → numérico)
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 3. MIGRACIÓN STATS ===\n";

$letter_to_num = array(
    'F' => 5, 'E' => 7, 'D' => 9, 'C' => 11, 'B' => 13,
    'A' => 15, 'S' => 18, 'SS' => 21, 'M' => 25, 'M+' => 30,
);

$stat_keys = array('FUE','DES','VIG','AGI','INT','ING','CON','PER','CAR','CTR','VOL','SEN');

function oleada0_stat_num(array $stats, string $key, array $letter_to_num): int
{
    if (!array_key_exists($key, $stats)) {
        return 5; // base = F
    }
    $val = $stats[$key];
    if (is_string($val) && isset($letter_to_num[$val])) {
        return $letter_to_num[$val];
    }
    return max(0, (int) $val);
}

$res = $db->query("SELECT pid, datos, stats_json FROM `{$PREFIX}rol_personajes`");
$updated = 0;
$skipped = 0;

while ($row = $res->fetch_assoc()) {
    $pid = (int) $row['pid'];

    // Idempotente: saltar personajes que ya tienen stats_json
    if (!empty($row['stats_json']) && $row['stats_json'] !== 'null') {
        $skipped++;
        continue;
    }

    $datos = json_decode((string) $row['datos'], true);
    if (!is_array($datos)) $datos = array();
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();

    // Sin stats_efectivas → saltar (personajes recién creados, sin datos)
    if (empty($stats)) {
        $skipped++;
        continue;
    }

    $converted = array();
    $sum = 0;
    foreach ($stat_keys as $sk) {
        $num = oleada0_stat_num($stats, $sk, $letter_to_num);
        $converted[$sk] = $num;
        $sum += $num;
    }

    $nivel = (int) floor($sum / 10);
    $ps_gastados = $sum - 60;

    $stats_json_esc = $db->real_escape_string(json_encode($converted, JSON_UNESCAPED_UNICODE));

    $db->query("UPDATE `{$PREFIX}rol_personajes`
        SET stats_json = '{$stats_json_esc}',
            nivel = {$nivel},
            ps_gastados = {$ps_gastados}
        WHERE pid = {$pid}");

    $updated++;
}
echo "  [OK] {$updated} personajes migrados, {$skipped} saltados\n";

// ═══════════════════════════════════════════════════════════════════════════
// 4. Actualizar nivel para personajes sin stats_json pero con stats_efectivas
//    (segunda pasada por si algún personaje no se migró en el paso 3
//     pero ya tiene nivel=0 por defecto)
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 4. BACKFILL NIVEL ===\n";

$res2 = $db->query("SELECT pid, datos FROM `{$PREFIX}rol_personajes`
    WHERE stats_json IS NOT NULL AND stats_json != 'null' AND nivel = 0");

$nivel_updated = 0;
while ($row2 = $res2->fetch_assoc()) {
    $pid = (int) $row2['pid'];
    $datos = json_decode((string) $row2['datos'], true);
    if (!is_array($datos)) $datos = array();
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();

    if (empty($stats)) continue;

    $sum = 0;
    foreach ($stat_keys as $sk) {
        $sum += oleada0_stat_num($stats, $sk, $letter_to_num);
    }
    $nivel = (int) floor($sum / 10);

    $db->query("UPDATE `{$PREFIX}rol_personajes` SET nivel = {$nivel} WHERE pid = {$pid}");
    $nivel_updated++;
}
echo "  [OK] {$nivel_updated} niveles recalculados\n";

echo "\n=== DONE ===\n";
$db->close();
