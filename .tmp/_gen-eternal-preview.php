<?php
// Genera HTML de preview de árboles Eternal v3 para revisión visual.
define('OPE_ETERNAL_STANDALONE', 1);
require dirname(__DIR__) . '/inc/ope_rol_eternal.php';

$dst = __DIR__;
// Refresca copia local del CSS canónico
@copy(dirname(__DIR__) . '/docs/themes/ope.css', $dst . '/ope.css');

$ids = ope_eternal_tree_ids();
foreach ($ids as $id) {
    $tree = ope_eternal_load($id);
    if (!$tree) { echo "skip $id\n"; continue; }
    $html = ope_eternal_render_tree($tree, 'preview');
    file_put_contents($dst . '/eternal-' . $id . '-preview.html', $html);
    echo "gen $id (" . count($tree['nodos']) . " nodos)\n";
}

// Vista combinada: 1 arma (filo) + 1 identidad (coloso), diálogos abiertos.
$view = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Eternal v3 preview</title>'
    . '<link rel="stylesheet" href="ope.css">'
    . '<style>body{margin:0;padding:20px;background:#f0eee8;font-family:serif}'
    . 'h1{font-size:1rem;color:#555;margin:24px 0 8px}'
    . 'dialog.eternal-tree-modal{display:block;position:static;margin:0 0 24px;border:1px solid #ccc;width:min(1400px,98vw)}'
    . '.eternal-tree-modal__panel{height:auto;max-height:none}'
    . '.eternal-tree__viewport{max-height:640px}</style></head><body>';
foreach (array('arma-filo', 'identidad-coloso', 'identidad-detonador') as $id) {
    $view .= '<h1>' . htmlspecialchars($id) . '</h1>';
    $view .= file_get_contents($dst . '/eternal-' . $id . '-preview.html');
}
$view .= '<script>document.querySelectorAll("dialog.eternal-tree-modal").forEach(function(d){d.setAttribute("open","open");});</script>';
$view .= '</body></html>';
file_put_contents($dst . '/eternal-view-v3.html', $view);
echo "view -> .tmp/eternal-view-v3.html\n";
