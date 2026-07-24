<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$res = $db->query("SHOW COLUMNS FROM " . TABLE_PREFIX . "rol_personajes");
while ($col = $db->fetch_array($res)) {
    echo "{$col['Field']} - {$col['Type']}\n";
}
