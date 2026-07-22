<?php
require __DIR__ . '/_db-config.php';
$r = $db->query('SELECT fid, name, pid, type, active, disporder FROM mybb_forums ORDER BY pid, disporder');
echo str_pad('FID',6).str_pad('PID',6).str_pad('T',4).str_pad('ACT',5).str_pad('ORD',5)."NAME\n";
echo str_repeat('-',70)."\n";
while($row = $r->fetch_assoc()) {
    echo str_pad($row['fid'],6)
        .str_pad($row['pid'],6)
        .str_pad($row['type'],4)
        .str_pad($row['active'],5)
        .str_pad($row['disporder'],5)
        .$row['name']."\n";
}
$db->close();
