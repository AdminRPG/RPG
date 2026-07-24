<?php
$html = file_get_contents(__DIR__ . '/ficha_15_dump.html');

$pos = strpos($html, 'Vaelen');
if ($pos !== false) {
    echo "Found 'Vaelen' at pos {$pos}:\n";
    echo substr($html, max(0, $pos - 300), 800) . "\n";
} else {
    echo "'Vaelen' not found in HTML dump!\n";
}
