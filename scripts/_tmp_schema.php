<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
foreach (['mybb_rol_personajes', 'mybb_posts', 'mybb_threads'] as $t) {
    echo "== $t ==\n";
    $res = $mysqli->query("SHOW COLUMNS FROM $t");
    while ($row = $res->fetch_assoc()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
}
