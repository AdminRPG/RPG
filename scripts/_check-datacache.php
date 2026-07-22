<?php
require __DIR__ . '/_db-config.php';
$r = $db->query("SELECT title FROM mybb_datacache");
while($row = $r->fetch_assoc()) {
    echo $row['title'] . "\n";
}
$db->close();
