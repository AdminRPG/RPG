<?php
define('IN_MYBB', 1);
$_GET['pid'] = 15;
require_once __DIR__ . '/../global.php';

ob_start();
include __DIR__ . '/../ficha.php';
$html = ob_get_clean();

file_put_contents(__DIR__ . '/ficha_15_dump.html', $html);
echo "HTML dumped to scripts/ficha_15_dump.html (" . strlen($html) . " bytes)\n";

// Parse sections
preg_match('/<div class="ope-hero-main">(.*?)<\/div>/s', $html, $m1);
echo "HERO SECTION:\n" . strip_tags($m1[0] ?? 'NOT FOUND') . "\n\n";

preg_match('/<section class="ope-tabs-sec">(.*?)<\/section>/s', $html, $m2);
echo "TABS HEADERS:\n" . strip_tags($m2[0] ?? 'NOT FOUND') . "\n\n";

// Match all tab panes
preg_match_all('/<div class="tab-pane[^"]*" id="tab-([^"]+)"[^>]*>(.*?)<\/div>\s*<!-- \/tab -->/s', $html, $panes);
for ($i = 0; $i < count($panes[1]); $i++) {
    echo "=== TAB: {$panes[1][$i]} ===\n";
    echo substr(strip_tags($panes[2][$i]), 0, 300) . "...\n\n";
}
