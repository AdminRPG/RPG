<?php
/**
 * Granblue Fantasy: Eternal · Rename masivo ope → gbe (contenido de archivos)
 * ---------------------------------------------------------------------------
 * NO toca la ruta web /iforge/ ni localhost/iforge.
 *
 *   php scripts/rename-gbe.php          (dry-run)
 *   php scripts/rename-gbe.php --apply
 */

$apply = in_array('--apply', $argv, true);
$root  = dirname(__DIR__);

$rules = [
    // ['images/ope/',           'images/gbe/'], // ya migrado (jul 2026)
    ['One Piece Eternal',     'Granblue Fantasy: Eternal'],
    ['ONE PIECE ETERNAL',     'GRANBLUE FANTASY: ETERNAL'],
    ['One piece eternal',     'Granblue Fantasy: Eternal'],
    ['OP-Eternal',            'GBEternal'],
    ['OP Eternal',            'Granblue Eternal'],
    ['ope-pg-',               'gbe-pg-'],
    ['ope-',                  'gbe-'],
    ['OPE_',                  'GBE_'],
    ['ope_',                  'gbe_'],
    ['ope.css',               'gbe.css'],
    ['ope-index.xml',         'gbe-index.xml'],
    ['ope-forumdisplay.xml',  'gbe-forumdisplay.xml'],
    ['ope_rol.php',           'gbe_rol.php'],
    ['ope_rol_',              'gbe_rol_'],
    ['ope_functions.php',     'gbe_functions.php'],
    ['ope_home',              'gbe_home'],
    ['ope_lastpid',           'gbe_lastpid'],
    ['ope_pid',               'gbe_pid'],
];

$excludeDirs = [
    '.git', 'backups', 'cache', 'node_modules', 'vendor', 'uploads',
    $root . '/docs/references',
    $root . '/docs/Prototypes',
    $root . '/.impeccable',
    $root . '/graphify-out',
];
$exts = ['php', 'css', 'xml', 'md', 'js', 'json'];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$touched = 0;
$totalReplacements = 0;
$self = basename(__FILE__);

foreach ($rii as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());

    $skip = false;
    foreach ($excludeDirs as $ex) {
        $ex = str_replace('\\', '/', $ex);
        if (strpos($path, '/' . trim($ex, '/') . '/') !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $exts, true)) {
        continue;
    }
    if (basename($path) === $self || basename($path) === 'rename-ope.php') {
        continue;
    }

    $orig = file_get_contents($file->getPathname());
    if ($orig === false) {
        continue;
    }
    if (stripos($orig, 'ope') === false && stripos($orig, 'ONE PIECE') === false && stripos($orig, 'OP-Eternal') === false) {
        continue;
    }

    $new = $orig;
    $count = 0;
    foreach ($rules as [$from, $to]) {
        $c = 0;
        $new = str_replace($from, $to, $new, $c);
        $count += $c;
    }

    if ($new !== $orig) {
        $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', $path), '/');
        echo sprintf("%-60s %d\n", $rel, $count);
        $touched++;
        $totalReplacements += $count;
        if ($apply) {
            file_put_contents($file->getPathname(), $new);
        }
    }
}

echo "\n" . ($apply ? 'APLICADO' : 'DRY-RUN') . ": {$touched} archivos, {$totalReplacements} reemplazos.\n";
if (!$apply) {
    echo "Ejecuta con --apply para escribir.\n";
}
