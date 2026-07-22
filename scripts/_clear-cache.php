<?php
require __DIR__ . '/_db-config.php';
$db->query("DELETE FROM mybb_datacache WHERE title IN ('templates','forums','forum_permissions','moderators')");
echo "Datacache cleared: {$db->affected_rows} rows\n";

// Also delete the cache file if it exists
$cacheDir = __DIR__ . '/../cache/';
foreach (glob($cacheDir . '*.php') as $f) {
    if (strpos(basename($f), 'templates') !== false) {
        unlink($f);
        echo "Deleted: " . basename($f) . "\n";
    }
}

echo "Done.\n";
$db->close();
