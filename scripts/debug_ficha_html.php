<?php
define('IN_MYBB', 1);
$_GET['pid'] = 15;
require_once __DIR__ . '/../global.php';

ob_start();
include __DIR__ . '/../ficha.php';
$html = ob_get_clean();

// Check key sections in HTML
echo "HTML Length: " . strlen($html) . "\n";
echo "Contains 'Vaelen': " . (strpos($html, 'Vaelen') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Ito Ito no Mi': " . (strpos($html, 'Ito Ito no Mi') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Gobierno Mundial': " . (strpos($html, 'Gobierno Mundial') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Centinela': " . (strpos($html, 'Centinela') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'Alcance': " . (strpos($html, 'Alcance') !== false ? 'YES' : 'NO') . "\n";

// Check if any tab content is empty
if (preg_match('/<div class="ope-pj-sheet">(.*?)<\/div>/s', $html, $m)) {
    echo "Sheet container found!\n";
} else {
    echo "Sheet container NOT found!\n";
}

// Extract main header info
preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $html, $h1s);
echo "H1 Tags: " . implode(' | ', $h1s[1] ?? array()) . "\n";

preg_match_all('/<div class="ope-tab-pane[^"]*" id="([^"]*)">(.*?)<\/div>/s', $html, $tabs);
for ($i = 0; $i < count($tabs[1]); $i++) {
    echo "Tab '{$tabs[1][$i]}': length " . strlen($tabs[2][$i]) . "\n";
}
