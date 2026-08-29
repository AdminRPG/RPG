<?php
/**
 * Limpieza de contenido de rpg_forum (solo desarrollo).
 * ----------------------------------------------------
 * Borra TODO el CONTENIDO de juego y del foro:
 *   · Personajes de prueba, atributos, dotes, dominios, haki, frutas,
 *     carteras, inventario, almacén, PP, historiales
 *   · Temas (presentes), trámites, turnos de combate, misiones, sucesos,
 *     rumores/carteles, conquistas, travesías, tiendas, tripulaciones, barcos
 *   · NPCs, bestiario, lore, alertas, cron_log, calendario on-roll, noticias
 *   · MyBB: hilos, posts, adjuntos, polls, PMs, eventos, ratings, logs,
 *     suscripciones y contadores de foro/usuario
 *
 * CONSERVA:
 *   · Catálogos/semillas (razas, raciales, tribus, dominios, dotes, defectos,
 *     rasgos, estados, acciones, matices, islas, mares, zonas, facciones,
 *     akumas, implantes, objetos, economía, estilos, cuentas…)
 *   · Usuarios MyBB admin + «OPE Eternal» y su personaje (#2024)
 *   · Esquema completo F0–F6 y las 54 tablas mybb_rol_retirada_* (respaldo D6.3)
 *
 * Idempotente: se puede re-ejecutar. No toca el esquema.
 */
require __DIR__ . '/_db-config.php';

// ── Catálogos / sistema que se CONSERVAN (nombre sin prefijo mybb_ope_) ─────
$keep = array(
    'acciones_pa', 'akuma_pool_tirada', 'akumas', 'catalogo_efectos', 'cuentas',
    'defectos', 'dominios', 'dotes', 'economia_config', 'especializaciones',
    'estados', 'estilos', 'facciones', 'familias_legendarias', 'implantes',
    'isla_estado', 'islas', 'maderas_casco', 'mares', 'matices_combate',
    'modulos_barcos', 'objetos', 'oraculos_catalogo', 'raciales',
    'rangos_faccion', 'rasgos', 'razas', 'tipos_barcos', 'transportes',
    'tribus', 'zonas',
);
$bot_uid = 2;   // usuario MyBB del bot «OPE Eternal»
$bot_pid = 2024; // pid canónico previo del bot (se recrea con otro id si falta)

echo "== Limpieza de contenido de rpg_forum ==\n\n";

// ── 1. Tablas mybb_ope_* de contenido ───────────────────────────────────────
$tables = array();
$q = $db->query("SHOW TABLES LIKE 'mybb_ope_%'");
while ($r = $q->fetch_row()) {
    $tables[] = $r[0];
}
$total = 0;
foreach ($tables as $t) {
    $name = substr($t, strlen('mybb_ope_'));
    if (in_array($name, $keep, true) || $name === 'personajes') {
        continue; // personajes se gestiona en el paso 2 (se conserva el del bot)
    }
    $db->query("DELETE FROM `{$t}`");
    $n = $db->affected_rows;
    if ($n > 0) {
        echo "  [ope] {$name}: borradas {$n} filas\n";
    }
    $total += $n;
}
echo "  [ope] {$total} filas de contenido borradas (" . count($tables) . " tablas ope revisadas)\n\n";

// ── 2. Personajes: se conserva solo el del bot; si no existe se recrea ──────
$db->query("DELETE FROM mybb_ope_personajes WHERE uid <> {$bot_uid}");
echo "  [ope] personajes: borrados " . $db->affected_rows . " de prueba\n";
$chk = $db->query("SELECT id FROM mybb_ope_personajes WHERE uid = {$bot_uid} AND es_NPC = 1 ORDER BY id LIMIT 1");
if ($chk && $chk->num_rows) {
    $bot_id = (int) $chk->fetch_row()[0];
    echo "  [ope] bot «OPE Eternal» presente: #{$bot_id}\n";
} else {
    $now = time();
    $db->query("INSERT INTO mybb_ope_personajes (uid, nombre, slug, estado, es_NPC, nivel, avatar, bio, dateline, lastedit) VALUES ({$bot_uid}, 'OPE Eternal', 'ope-eternal', 'aprobado', 1, 1, '', 'La voz del mundo: el periódico News Coo y los avisos del sistema.', {$now}, {$now})");
    echo "  [ope] bot «OPE Eternal» RECREADO (#" . $db->insert_id . ")\n";
}

// ── 3. Contenido MyBB (núcleo) ──────────────────────────────────────────────
$core = array(
    'attachments', 'polls', 'pollvotes', 'threadviews', 'threadread',
    'events', 'ratings', 'reportedposts', 'moderatorlog', 'searchlog',
    'privatemessages', 'forumsubscriptions', 'threadsubscriptions',
    'notifications', 'posts', 'threads',
);
$core_total = 0;
foreach ($core as $t) {
    $chk = $db->query("SHOW TABLES LIKE 'mybb_{$t}'");
    if (!$chk || $chk->num_rows === 0) {
        continue;
    }
    $db->query("DELETE FROM mybb_{$t}");
    $n = $db->affected_rows;
    if ($n > 0) {
        echo "  [mybb] {$t}: borradas {$n} filas\n";
    }
    $core_total += $n;
}
echo "  [mybb] {$core_total} filas de contenido de foro borradas\n\n";

// ── 4. Contadores, punteros y cachés ────────────────────────────────────────
$db->query("UPDATE mybb_forums SET threads=0, posts=0, lastpost=0, lastposter='', lastposteruid=0, lastposttid=0, lastpostsubject='', unapprovedthreads=0, unapprovedposts=0, deletedthreads=0, deletedposts=0, ope_lastpid=0");
echo "  [mybb] foros: contadores reiniciados (" . $db->affected_rows . " foros)\n";
$db->query("UPDATE mybb_users SET postnum=0");
echo "  [mybb] usuarios: postnum reiniciado (" . $db->affected_rows . ")\n";
$db->query("UPDATE mybb_ope_cuentas SET personaje_activo=0");
echo "  [ope] cuentas: personaje_activo reiniciado (" . $db->affected_rows . ")\n";
$db->query("DELETE FROM mybb_datacache WHERE title IN ('stats','forums','latest_activity','ope_home')");
echo "  [mybb] datacache: stats/forums/latest_activity/ope_home purgados\n";

echo "\n== Limpieza completada. Catálogos, usuarios, bot (#{$bot_pid}) y esquema intactos. ==\n";
$db->close();
