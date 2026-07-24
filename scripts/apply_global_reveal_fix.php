<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

echo "=== APLICANDO FIX GLOBAL DE VISIBILIDAD .REVEAL ===\n\n";

// 1. Modificar inc/plugins/ope_rol.php
$plugin_file = MYBB_ROOT . 'inc/plugins/ope_rol.php';
$content = file_get_contents($plugin_file);
$target = "\$html .= '<link rel=\"stylesheet\" href=\"' . htmlspecialchars_uni(\$href) . '\">' . \"\\n\";";
$replacement = "\$html .= '<link rel=\"stylesheet\" href=\"' . htmlspecialchars_uni(\$href) . '\">' . \"\\n\";\n    \$html .= '<style>.reveal{opacity:1!important;transform:none!important;visibility:visible!important}</style>' . \"\\n\";";

if (strpos($content, '.reveal{opacity:1!important') === false) {
    $new_content = str_replace($target, $replacement, $content);
    file_put_contents($plugin_file, $new_content);
    echo "✔ Fix inyectado en ope_rol_head_base() (inc/plugins/ope_rol.php).\n";
} else {
    echo "ℹ Fix ya estaba presente en inc/plugins/ope_rol.php.\n";
}

// 2. Modificar docs/themes/ope.css y cache/themes/theme*/ope.css
$css_files = array_merge(
    array(MYBB_ROOT . 'docs/themes/ope.css'),
    glob(MYBB_ROOT . 'cache/themes/theme*/ope.css') ?: array()
);

$rule = "\n/* FIX GLOBAL DE VISIBILIDAD DE CONTENIDO */\n.reveal, .reveal.vis { opacity: 1 !important; transform: none !important; visibility: visible !important; }\n";

foreach ($css_files as $f) {
    if (!is_file($f)) continue;
    $c = file_get_contents($f);
    if (strpos($c, 'FIX GLOBAL DE VISIBILIDAD DE CONTENIDO') === false) {
        file_put_contents($f, $rule . $c);
        echo "✔ Fix inyectado en " . basename(dirname($f)) . '/' . basename($f) . "\n";
    } else {
        echo "ℹ Fix ya presente en " . basename(dirname($f)) . '/' . basename($f) . "\n";
    }
}

echo "\n=== FIX GLOBAL FINALIZADO CON ÉXITO ===\n";
