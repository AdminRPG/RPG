<?php
$html = file_get_contents(__DIR__ . '/ficha_15_dump.html');

// Check name, alias, level, faction, stats
preg_match('/<div class="ope-ficha-name">(.*?)<\/div>/s', $html, $name);
preg_match('/<div class="ope-ficha-alias">(.*?)<\/div>/s', $html, $alias);
preg_match('/<div class="ope-ficha-lv">(.*?)<\/div>/s', $html, $lv);
preg_match('/<div class="ope-ficha-facpill[^"]*">(.*?)<\/div>/s', $html, $fac);

echo "Nombre: " . trim(strip_tags($name[1] ?? 'N/A')) . "\n";
echo "Alias: " . trim(strip_tags($alias[1] ?? 'N/A')) . "\n";
echo "Nivel: " . trim(strip_tags($lv[1] ?? 'N/A')) . "\n";
echo "Facción Pill: " . trim(strip_tags($fac[1] ?? 'N/A')) . "\n\n";

// Find all rk-section titles and content
preg_match_all('/<div class="rk-section-h[^"]*">(.*?)<\/div>\s*<div class="rk-section-b[^"]*">(.*?)<\/div>/s', $html, $secs);
for ($i = 0; $i < count($secs[1]); $i++) {
    $title = trim(strip_tags($secs[1][$i]));
    $body = trim(preg_replace('/\s+/', ' ', strip_tags($secs[2][$i])));
    echo "=== SECTION: {$title} ===\n";
    echo substr($body, 0, 200) . (strlen($body) > 200 ? '...' : '') . "\n\n";
}
