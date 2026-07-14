<?php
$db_host = getenv('MYBB_DB_HOST') ?: '127.0.0.1';
$db_user = getenv('MYBB_DB_USER') ?: 'root';
$db_pass = getenv('MYBB_DB_PASS') ?: '';
$db_name = getenv('MYBB_DB_NAME') ?: 'mybb_foro';

$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    die("DB connection failed: " . $db->connect_error);
}
$db->set_charset('utf8mb4');
