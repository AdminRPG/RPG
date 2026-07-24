<?php
$html = file_get_contents(__DIR__ . '/ficha_15_dump.html');

preg_match_all('/class="([^"]+)"/', $html, $m);
$classes = array_unique($m[1]);
echo "CLASSES USED IN FICHA.PHP:\n";
foreach (array_slice($classes, 0, 40) as $c) {
    echo "  - {$c}\n";
}
