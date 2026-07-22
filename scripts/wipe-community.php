<?php
/**
 * Granblue Eternal — Limpieza total de contenido comunitario
 * ------------------------------------------------------------
 * Borra posts, hilos, NPCs, lore, datos rol y cuentas excepto el admin.
 * La estructura de foros (Skydoms/islas) se conserva; solo se vacían contadores.
 *
 * Uso:
 *   php scripts/wipe-community.php              # simulación (dry-run)
 *   php scripts/wipe-community.php --apply      # ejecutar
 *   php scripts/wipe-community.php --apply --admin=1
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';
$apply = in_array('--apply', $argv, true);
$adminUid = 1;
foreach ($argv as $arg) {
    if (preg_match('/^--admin=(\d+)$/', $arg, $m)) {
        $adminUid = (int)$m[1];
    }
}

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

function wipe_table(mysqli $db, string $table, bool $apply, string $where = '1=1'): void
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
        echo "  [=] {$full}: vacío\n";
        return;
    }
    if ($apply) {
        q($db, "DELETE FROM `{$full}` WHERE {$where}", true);
        echo "  [-] {$full}: {$before} fila(s) borradas\n";
    } else {
        echo "  [~] {$full}: borraría {$before} fila(s)\n";
    }
}

function truncate_table(mysqli $db, string $table, bool $apply): void
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

echo "=== Wipe comunidad Granblue Eternal ===\n";
echo $apply ? "MODO: APPLY (destructivo)\n" : "MODO: dry-run (añade --apply para ejecutar)\n";
echo "Admin conservado: uid={$adminUid}\n\n";

$r = $db->query("SELECT uid, username FROM {$PREFIX}users WHERE uid = {$adminUid} LIMIT 1");
$admin = $r ? $r->fetch_assoc() : null;
if (!$admin) {
    fwrite(STDERR, "ERROR: no existe usuario uid={$adminUid}\n");
    exit(1);
}
echo "Admin: {$admin['username']} (uid={$adminUid})\n";

$otherUsers = count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}users WHERE uid != {$adminUid}");
echo "Usuarios a borrar: {$otherUsers}\n";
echo "Hilos: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}threads") . "\n";
echo "Posts: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}posts") . "\n";
echo "Personajes/NPCs: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_personajes") . "\n";
echo "Lore: " . count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}rol_lore") . "\n\n";

if (!$apply) {
    echo "--- Simulación ---\n";
}

// ── Foro MyBB: contenido ──
echo ">> Contenido del foro\n";
$forumContent = [
    'postlog',
    'posts',
    'threads',
    'threadratings',
    'threadviews',
    'pollvotes',
    'polls',
    'attachments',
    'announcements',
    'delayedmoderation',
    'moderatorlog',
    'reportedposts',
    'searchlog',
    'threadsubscriptions',
    'forumsubscriptions',
    'favorites',
    'warninglevels',
];
foreach ($forumContent as $t) {
    truncate_table($db, $t, $apply);
}

// Post snapshots / meta rol
echo "\n>> Meta de hilos y posts (rol)\n";
foreach (['rol_post_snapshot', 'rol_thread_meta'] as $t) {
    truncate_table($db, $t, $apply);
}

// ── Rol: personajes, lore, social ──
echo "\n>> Sistema de rol\n";
$rolTruncate = [
    'rol_personajes',
    'rol_lore',
    'rol_relaciones',
    'rol_tramites',
    'rol_transacciones',
    'rol_tiradas',
    'rol_mensajes',
    'rol_alertas',
    'rol_cartas',
    'rol_tecnicas',
    'rol_npcs_secundarios',
    'rol_viajes',
    'rol_tripulacion_miembros',
    'rol_tripulaciones',
    'rol_acompanantes',
    'rol_acompanante_solicitudes',
    'rol_cronologia',
    'rol_calendario',
    'rol_calendario_onrol',
    'rol_estados',
    'rol_estilos',
    'rol_haki',
    'rol_pj_fruta',
    'rol_pj_eternal',
    'rol_akuma',
    'rol_bestiario',
    'rol_wanted',
    'rol_pl',
    'rol_pl_log',
    'rol_pp_log',
    'rol_pp_saldo',
    'rol_rachas',
    'rol_post_templates',
    'rol_tienda_items',
    'rol_mv_arcos',
    'rol_mv_audit',
    'rol_mv_ciclos',
    'rol_mv_eventos',
    'rol_mv_facciones',
    'rol_mv_mision_asignaciones',
    'rol_mv_misiones',
    'rol_mv_noticias',
    'rol_mv_npc_menores',
    'rol_mv_tension',
    'rol_mv_zonas',
];
foreach ($rolTruncate as $t) {
    truncate_table($db, $t, $apply);
}

// Cuentas rol: conservar solo admin
echo "\n>> Cuentas rol\n";
wipe_table($db, 'rol_cuentas', $apply, "uid != {$adminUid}");
if ($apply) {
    $now = time();
    q($db, "INSERT INTO {$PREFIX}rol_cuentas (uid, staff_level, slots, personaje_activo, dateline)
        VALUES ({$adminUid}, 3, 3, 0, {$now})
        ON DUPLICATE KEY UPDATE staff_level = 3, personaje_activo = 0", true);
    echo "  [+] rol_cuentas: admin uid={$adminUid} reseteado\n";
} else {
    echo "  [~] rol_cuentas: conservaría uid={$adminUid}, borraría el resto\n";
}

// ── Usuarios MyBB (excepto admin) ──
echo "\n>> Usuarios MyBB\n";
if ($apply) {
    q($db, "DELETE FROM {$PREFIX}sessions", true);
    echo "  [-] sessions: todas purgadas\n";
} else {
    $sess = count_rows($db, "SELECT COUNT(*) c FROM {$PREFIX}sessions");
    echo "  [~] sessions: purgaría {$sess} fila(s)\n";
}
foreach (['privatemessages', 'reputation', 'buddylist', 'buddyrequests', 'warnings'] as $t) {
    $r = $db->query("SHOW TABLES LIKE '{$PREFIX}{$t}'");
    if (!$r || $r->num_rows === 0) {
        continue;
    }
    // Tablas con uid directo
    wipe_table($db, $t, $apply, "uid != {$adminUid}");
}
wipe_table($db, 'userfields', $apply, "ufid != {$adminUid}");
wipe_table($db, 'users', $apply, "uid != {$adminUid}");

// Reset admin stats
if ($apply) {
    q($db, "UPDATE {$PREFIX}users SET
        postnum = 0, threadnum = 0, lastpost = 0, lastvisit = 0,
        lastactive = 0, timeonline = 0, reputation = 0
        WHERE uid = {$adminUid}", true);
    echo "  [+] users uid={$adminUid}: contadores reseteados\n";
} else {
    echo "  [~] users uid={$adminUid}: resetearía contadores\n";
}

// ── Contadores de foros ──
echo "\n>> Contadores de foros\n";
if ($apply) {
    q($db, "UPDATE {$PREFIX}forums SET
        threads = 0, posts = 0, unapprovedthreads = 0, unapprovedposts = 0,
        lastpost = 0, lastposter = '', lastposttid = 0, lastpostsubject = ''", true);
    echo "  [+] forums: contadores a cero\n";
} else {
    echo "  [~] forums: pondría contadores a cero\n";
}

// Stats globales (datacache; MyBB 1.8 no usa tabla stats fija)
if ($apply) {
    $stats = serialize([
        'numusers' => 1,
        'numthreads' => 0,
        'numposts' => 0,
        'lastuid' => $adminUid,
        'lastusername' => $admin['username'],
        'lastpost' => 0,
    ]);
    $statsEsc = $db->real_escape_string($stats);
    q($db, "INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('stats', '{$statsEsc}')
        ON DUPLICATE KEY UPDATE cache = '{$statsEsc}'", true);
    echo "  [+] datacache stats: reseteadas\n";
} else {
    echo "  [~] datacache stats: resetearía a 1 usuario, 0 hilos/posts\n";
}

// Datacache
echo "\n>> Datacache\n";
$cacheKeys = [
    'forums', 'forumsdisplay', 'moderators', 'stats', 'mostonline',
    'birthdays', 'birthdaysweek', 'spiders', 'internal_settings',
];
if ($apply) {
    $in = "'" . implode("','", array_map([$db, 'real_escape_string'], $cacheKeys)) . "'";
    q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ({$in})", true);
    echo "  [-] datacache: claves foro/stats purgadas\n";
} else {
    echo "  [~] datacache: purgaría claves foro/stats\n";
}

echo "\n=== " . ($apply ? "Hecho" : "Dry-run completado") . " ===\n";
if (!$apply) {
    echo "Ejecuta: php scripts/wipe-community.php --apply\n";
} else {
    echo "Recomendado: ACP → Tools → Recount & Rebuild (o visitar el foro).\n";
    echo "Hard refresh en navegador para ver foros vacíos.\n";
}
