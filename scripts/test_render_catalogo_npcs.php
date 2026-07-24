<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

ob_start();
try {
    include __DIR__ . '/../catalogo-npcs.php';
    $out = ob_get_clean();
    echo "SUCCESS! Rendered " . strlen($out) . " bytes for standalone catalogo-npcs.php.\n";
    echo "Contains 'Catálogo de Personajes Mayores': " . (strpos($out, 'Catálogo de Personajes Mayores') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'Sigrun D. Basterra': " . (strpos($out, 'Sigrun D. Basterra') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'Vaelen': " . (strpos($out, 'Vaelen') !== false ? 'YES' : 'NO') . "\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
