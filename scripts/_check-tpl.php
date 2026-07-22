<?php
require __DIR__ . '/_db-config.php';
// Show ALL versions of forumbit_depth2_forum across all template sets
$r = $db->query("SELECT sid, title, SUBSTRING(template,1,200) AS snippet FROM mybb_templates WHERE title='forumbit_depth2_forum' ORDER BY sid");
while ($row = $r->fetch_assoc()) {
    echo "SID={$row['sid']}: " . str_replace(["\n","\r"], " ", $row['snippet']) . "\n\n";
}
// Check active theme's template set
$r2 = $db->query("SELECT tid, name, properties FROM mybb_themes WHERE tid=13");
if ($r2 && ($t = $r2->fetch_assoc())) {
    echo "Theme: {$t['name']}\n";
    $props = unserialize($t['properties']);
    echo "Template set (templateset): " . ($props['templateset'] ?? 'N/A') . "\n";
}
// Also clear template cache
$db->query("DELETE FROM mybb_datacache WHERE title='templates'");
echo "\nTemplate cache cleared.\n";
$db->close();
