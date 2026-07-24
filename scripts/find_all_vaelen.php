<?php
$html = file_get_contents(__DIR__ . '/ficha_15_dump.html');

$offset = 0;
$i = 1;
while (($pos = strpos($html, 'Vaelen', $offset)) !== false) {
    echo "--- MATCH #{$i} AT POS {$pos} ---\n";
    echo substr($html, max(0, $pos - 150), 350) . "\n\n";
    $offset = $pos + 6;
    $i++;
}
