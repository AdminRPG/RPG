<?php
require __DIR__ . '/_db-config.php';
foreach (['mybb_rol_personajes', 'mybb_posts', 'mybb_threads'] as $t) {
    echo "== $t ==\n";
    $res = $db->query("SHOW COLUMNS FROM $t");
    while ($row = $res->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
