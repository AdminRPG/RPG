<?php
define('IN_MYBB', 1);
$_GET['pid'] = 15;
require_once __DIR__ . '/../global.php';

ob_start();
try {
    include __DIR__ . '/../ficha.php';
    $out = ob_get_clean();
    echo "SUCCESS! Rendered " . strlen($out) . " bytes for PID 15.\n";
    echo "First 400 chars:\n" . substr($out, 0, 400) . "\n";
    echo "Last 400 chars:\n" . substr($out, -400) . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
