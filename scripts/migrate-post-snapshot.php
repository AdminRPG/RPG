<?php
/**
 * I-Forge · Migración "snapshot histórico de personaje por post"
 * ----------------------------------------------------------------
 * Crea la tabla `mybb_rol_post_snapshot`, que guarda el estado INMUTABLE del
 * personaje (stats efectivas + objetos que llevaba "encima") en el momento
 * exacto en que se publicó cada post. Los modales "Mochila"/"Atributos" del
 * postbit leen de aquí para no mostrar nunca el estado actual/en vivo del
 * personaje sobre un post antiguo (p.ej. si luego se le añade un objeto al
 * inventario, los posts ya publicados no deben reflejar ese cambio).
 *
 * A partir de que este plugin esté activo, cada post nuevo se captura solo
 * (hooks datahandler_post_insert_thread_end / datahandler_post_insert_post_end
 * en inc/plugins/ope_rol.php, función ope_rol_snapshot_post). Este script
 * hace además un BACKFILL best-effort para los posts que ya existían antes de
 * desplegar esta funcionalidad: como su histórico real no es recuperable, se
 * usa el estado ACTUAL del personaje como aproximación (queda anotado en el
 * propio código; no hay forma de saber qué llevaba encima en aquel momento).
 *
 * Idempotente: se puede re-ejecutar sin duplicar filas ni perder snapshots
 * ya capturados en tiempo real (solo rellena los pid de post que faltan).
 *
 * Ejecutar (desde la raíz del repo, sin truncar la salida):
 *   php scripts/migrate-post-snapshot.php
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

function table_exists(mysqli $db, string $table): bool
{
    $t   = $db->real_escape_string($table);
    $res = $db->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

echo "=== Migración snapshot histórico de personaje por post ===\n";

$table = $PREFIX . 'rol_post_snapshot';

if (table_exists($db, $table)) {
    echo "  [skip] tabla {$table} ya existe\n";
} else {
    $sql = "CREATE TABLE `{$table}` (
        `pid` INT UNSIGNED NOT NULL COMMENT 'pid del post (mybb_posts.pid)',
        `personaje_pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje autor del post en ese momento',
        `atributos` JSON NULL COMMENT 'stats_efectivas del personaje en el momento del post',
        `objetos` JSON NULL COMMENT 'objetos que llevaba \"encima\" (mochila) en el momento del post',
        `dateline` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'momento en que se capturó el snapshot',
        PRIMARY KEY (`pid`),
        KEY `idx_personaje_pid` (`personaje_pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] creando {$table}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] tabla {$table} creada\n";
}

// ─────────────────────────────────────────────────────────────
// Backfill best-effort: un snapshot por cada post con personaje (ope_pid>0)
// que todavía no tenga fila. APROXIMACIÓN: usa el estado ACTUAL del
// personaje (datos.stats_efectivas + inventario.encima), porque el histórico
// real de posts anteriores a esta funcionalidad no se puede reconstruir.
// Los posts nuevos, a partir de ahora, sí capturan el estado exacto de ese
// instante vía el hook ope_rol_snapshot_post().
// ─────────────────────────────────────────────────────────────
echo "\n--- Backfill (aproximado, estado actual del personaje) ---\n";

$sql = "
    SELECT p.pid, p.ope_pid, p.dateline
    FROM `{$PREFIX}posts` p
    LEFT JOIN `{$table}` s ON s.pid = p.pid
    WHERE p.ope_pid > 0 AND s.pid IS NULL
";
$res = $db->query($sql);
if ($res === false) {
    fwrite(STDERR, "  [ERROR] seleccionando posts pendientes: " . $db->error . "\n");
    exit(1);
}

$pendientes = array();
while ($row = $res->fetch_assoc()) {
    $pendientes[] = $row;
}
echo '  posts pendientes de snapshot: ' . count($pendientes) . "\n";

$charCache = array();
$insertados = 0;
$omitidos   = 0;

$stmt = $db->prepare("INSERT INTO `{$table}` (pid, personaje_pid, atributos, objetos, dateline) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    fwrite(STDERR, "  [ERROR] preparando INSERT: " . $db->error . "\n");
    exit(1);
}

foreach ($pendientes as $row) {
    $postPid  = (int) $row['pid'];
    $charPid  = (int) $row['ope_pid'];
    $dateline = (int) $row['dateline'];

    if (!array_key_exists($charPid, $charCache)) {
        $charCache[$charPid] = null;
        $cq = $db->query("SELECT datos, inventario FROM `{$PREFIX}rol_personajes` WHERE pid = {$charPid} LIMIT 1");
        if ($cq && $cq->num_rows > 0) {
            $charCache[$charPid] = $cq->fetch_assoc();
        }
    }
    $charRow = $charCache[$charPid];
    if ($charRow === null) {
        // Personaje borrado/inexistente: no se puede aproximar nada, se omite.
        $omitidos++;
        continue;
    }

    $datos = json_decode((string) $charRow['datos'], true);
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();

    $inv    = json_decode((string) $charRow['inventario'], true);
    $encima = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();

    $atributosJson = json_encode($stats, JSON_UNESCAPED_UNICODE);
    $objetosJson   = json_encode($encima, JSON_UNESCAPED_UNICODE);

    $stmt->bind_param('iissi', $postPid, $charPid, $atributosJson, $objetosJson, $dateline);
    if (!$stmt->execute()) {
        fwrite(STDERR, "  [warn] pid={$postPid}: " . $stmt->error . "\n");
        continue;
    }
    $insertados++;
}
$stmt->close();

echo "  [OK] snapshots insertados: {$insertados}\n";
if ($omitidos > 0) {
    echo "  [warn] omitidos (personaje ya no existe): {$omitidos}\n";
}

echo "\n=== DONE ===\n";
$db->close();
