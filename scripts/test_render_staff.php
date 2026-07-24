<?php
define('IN_MYBB', 1);
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once __DIR__ . '/../global.php';

// Force staff user
$mybb->user['uid'] = 1;

ob_start();
try {
    include __DIR__ . '/../zona-staff-personajes.php';
    $out = ob_get_clean();
    echo "SUCCESS! Rendered " . strlen($out) . " bytes.\n";
    echo "First 500 chars:\n" . substr($out, 0, 500) . "\n";
    echo "Last 500 chars:\n" . substr($out, -500) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
