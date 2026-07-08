<?php
/**
 * One Piece Eternal · Rename masivo iforge -> ope (SOLO contenido de archivos)
 * ---------------------------------------------------------------------------
 * Reemplazos ORDENADOS y quirúrgicos. NO toca la ruta de instalación web
 * `/iforge/` (bburl http://localhost/iforge) ni `localhost/iforge`; solo
 * renombra nuestros propios tokens.
 *
 *   php scripts/rename-ope.php          (dry-run: solo informa)
 *   php scripts/rename-ope.php --apply  (escribe cambios)
 *
 * Los renombrados de ARCHIVOS y la migración de BD se hacen aparte.
 */

$apply = in_array('--apply', $argv, true);
$root  = dirname(__DIR__);

// Reglas en orden. La primera que casa un fragmento lo transforma; el resto
// opera sobre el texto ya transformado (por eso 'iforge-foundry-' va antes que
// 'iforge-', y 'images/iforge/' primero para no chocar con nada).
$rules = [
    ['images/iforge/',     'images/ope/'],
    ['iforge-foundry-',    'ope-'],          // además elimina el token "foundry" de los XML
    ['iforge-',            'ope-'],           // clases CSS y prefijos de plantilla
    ['IFORGE_',            'OPE_'],           // constantes PHP
    ['iforge_',            'ope_'],           // funciones, globales, claves de caché, columnas, codename
    ['iforge.css',         'ope.css'],
    ['FOUNDRY BRUTALISM',  'ONE PIECE ETERNAL'],
    ['Foundry Brutalism',  'One Piece Eternal'],
    ['Foundry brutalism',  'One Piece Eternal'],
];

// Directorios excluidos (core MyBB no tiene tokens; referencias/backups no se tocan).
$excludeDirs = [
    '.git', 'backups', 'cache', 'node_modules', 'vendor', 'uploads',
    $root . '/docs/references',
    $root . '/.impeccable',
];
$exts = ['php', 'css', 'xml'];

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$touched = 0;
$totalReplacements = 0;

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    $path = str_replace('\\', '/', $file->getPathname());

    // Excluir dirs
    $skip = false;
    foreach ($excludeDirs as $ex) {
        $ex = str_replace('\\', '/', $ex);
        if (strpos($path, '/' . trim($ex, '/') . '/') !== false || strpos($path, rtrim($ex, '/') . '/') === 0) {
            $skip = true; break;
        }
    }
    if ($skip) continue;

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $exts, true)) continue;

    // No reescribir este propio script
    if (basename($path) === 'rename-ope.php') continue;

    $orig = file_get_contents($file->getPathname());
    if ($orig === false || stripos($orig, 'iforge') === false && stripos($orig, 'IFORGE') === false && stripos($orig, 'foundry') === false) {
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
        echo sprintf("%-55s %d reemplazos\n", $rel, $count);
        $touched++;
        $totalReplacements += $count;
        if ($apply) {
            file_put_contents($file->getPathname(), $new);
        }
    }
}

echo "\n" . ($apply ? 'APLICADO' : 'DRY-RUN') . ": {$touched} archivos, {$totalReplacements} reemplazos.\n";
if (!$apply) {
    echo "Ejecuta con --apply para escribir los cambios.\n";
}
