<?php
/**
 * Initial theme install only (creates theme + templateset if missing).
 * For day-to-day deploy use: php scripts/sync-theme.php import
 *
 * Run: php scripts/import-theme.php
 */
require __DIR__ . '/_theme-sync-lib.php';

$db = ope_db_connect();

$result = $db->query("SELECT tid, properties FROM mybb_themes WHERE name = 'One Piece: Eternal' OR name = 'RPG' ORDER BY tid DESC LIMIT 1");
$theme = $result ? $result->fetch_assoc() : null;

if ($theme) {
    echo "Theme already exists (tid={$theme['tid']}).\n";
    echo "Use: php scripts/sync-theme.php import\n";
    $db->close();
    exit(0);
}

echo "Creating new One Piece: Eternal theme...\n";

$r = $db->query('SELECT tid FROM mybb_themes WHERE def = 1 AND pid = 0');
$parent = $r ? $r->fetch_assoc() : null;
$pid = $parent ? (int)$parent['tid'] : 1;

$db->query("INSERT INTO mybb_templatesets (title) VALUES ('One Piece: Eternal')");
$sid = $db->insert_id;

$props = serialize([
    'templateset' => $sid,
    'imgdir' => 'images',
    'logo' => 'images/logo.png',
    'tablespace' => 5,
    'borderwidth' => 0,
    'editortheme' => 'mybb.css',
]);
$stmt = $db->prepare("INSERT INTO mybb_themes (name, pid, def, properties, stylesheets, allowedgroups) VALUES ('One Piece: Eternal', ?, 1, ?, '', 'all')");
$stmt->bind_param('is', $pid, $props);
$stmt->execute();
$tid = $stmt->insert_id;
$stmt->close();

echo "Created theme tid=$tid, templateset sid=$sid\n";
echo "Now run: php scripts/sync-theme.php import\n";
$db->close();
