<?php
/**
 * Hilo de prueba para verificar postbit GBE (showthread).
 * Ejecutar: php scripts/seed-postbit-demo.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';
$SUBJECT = '[Demo] Verificación visual postbit GBE';
$FID = 66; // Cafetería del Puerto (Off Topic)
$UID = 1;
$now = time();

function q(mysqli $db, string $sql)
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "SQL ERROR: {$db->error}\n  $sql\n");
        exit(1);
    }
}

$r = $db->query("SELECT uid, username FROM {$PREFIX}users WHERE uid = {$UID} LIMIT 1");
$user = $r ? $r->fetch_assoc() : null;
if (!$user) {
    fwrite(STDERR, "No existe uid={$UID}\n");
    exit(1);
}

$r = $db->query("SELECT fid, name FROM {$PREFIX}forums WHERE fid = {$FID} AND type = 'f' LIMIT 1");
$forum = $r ? $r->fetch_assoc() : null;
if (!$forum) {
    fwrite(STDERR, "Foro fid={$FID} no encontrado\n");
    exit(1);
}

$r = $db->query("SELECT tid FROM {$PREFIX}threads WHERE subject = '" . $db->real_escape_string($SUBJECT) . "' LIMIT 1");
if ($r && ($row = $r->fetch_assoc())) {
    $tid = (int) $row['tid'];
    echo "Ya existe demo tid={$tid}\n";
    echo "URL: http://localhost/iforge/showthread.php?tid={$tid}\n";
    exit(0);
}

$username = $db->real_escape_string($user['username']);
$subjectEsc = $db->real_escape_string($SUBJECT);
$message = <<<'MSG'
Este hilo es una **prueba visual** del postbit Granblue Eternal.

Comprueba que:
- La cajetilla del autor no tiene borde negro OP
- El fondo usa tokens `--ope-*`
- Los botones del post son rectangulares OPE

Puedes borrar este hilo cuando termines la revisión.
MSG;
$messageEsc = $db->real_escape_string($message);
$ip = '127.0.0.1';

q($db, "INSERT INTO {$PREFIX}threads
    (fid, subject, prefix, icon, poll, uid, username, dateline, firstpost, lastpost, lastposter, lastposteruid,
     views, replies, closed, sticky, numratings, totalratings, notes, visible, unapprovedposts, deletedposts, attachmentcount)
    VALUES
    ({$FID}, '{$subjectEsc}', 0, 0, 0, {$UID}, '{$username}', {$now}, 0, {$now}, '{$username}', {$UID},
     0, 0, 0, 0, 0, 0, '', 1, 0, 0, 0)");
$tid = (int) $db->insert_id;

q($db, "INSERT INTO {$PREFIX}posts
    (tid, replyto, fid, subject, icon, uid, username, dateline, message, ipaddress, includesig, smilieoff, edituid, visible)
    VALUES
    ({$tid}, 0, {$FID}, '{$subjectEsc}', 0, {$UID}, '{$username}', {$now}, '{$messageEsc}', '{$ip}', 0, 0, 0, 1)");
$pid = (int) $db->insert_id;

q($db, "UPDATE {$PREFIX}threads SET firstpost = {$pid} WHERE tid = {$tid}");
q($db, "UPDATE {$PREFIX}forums SET
    threads = threads + 1, posts = posts + 1,
    lastpost = {$now}, lastposter = '{$username}', lastposttid = {$tid}, lastpostsubject = '{$subjectEsc}'
    WHERE fid = {$FID}");
q($db, "UPDATE {$PREFIX}users SET postnum = postnum + 1, threadnum = threadnum + 1, lastpost = {$now} WHERE uid = {$UID}");

$stats = serialize([
    'numusers' => 1,
    'numthreads' => 1,
    'numposts' => 1,
    'lastuid' => $UID,
    'lastusername' => $user['username'],
    'lastpost' => $now,
]);
$statsEsc = $db->real_escape_string($stats);
q($db, "INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('stats', '{$statsEsc}')
    ON DUPLICATE KEY UPDATE cache = '{$statsEsc}'");
q($db, "DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forumsdisplay')");

echo "Demo creado: tid={$tid} pid={$pid} en {$forum['name']} (fid={$FID})\n";
echo "URL: http://localhost/iforge/showthread.php?tid={$tid}\n";
echo "Newreply: http://localhost/iforge/newreply.php?tid={$tid}\n";
echo "Newthread: http://localhost/iforge/newthread.php?fid={$FID}\n";
