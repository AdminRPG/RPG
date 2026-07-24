<?php
$f = __DIR__ . '/../docs/themes/ope.css';
$content = file_get_contents($f);

// Remove BOM if present
$content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

// Remove the injected fix comment from top if present
$content = preg_replace('/\/\* FIX GLOBAL DE VISIBILIDAD DE CONTENIDO \*\/.*?\n\.reveal.*?\n+/s', '', $content);

// Clean top of file
$content = ltrim($content);

file_put_contents($f, $content);
echo "Cleaned docs/themes/ope.css. First 100 chars:\n" . substr($content, 0, 100) . "\n";
