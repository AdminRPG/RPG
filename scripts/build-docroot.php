<?php
/**
 * Ensambla el docroot desplegable en back/forum/ desde la raíz del repo.
 * Uso: php scripts/build-docroot.php
 *
 * La raíz del repo ES el docroot MyBB (inc/, admin/, index.php...). Este
 * script copia el árbol completo preservando la estructura, excluyendo:
 *   - directorios de desarrollo (docs, scripts, cache, install, back...)
 *   - basura de root (capturas *.png, *.html, *.txt, *.md de docs)
 *   - inc/ope_rol/config/viaje_ai.php (claves locales NVIDIA)
 *
 * El CI reproduce esto con rsync (ver .github/workflows/deploy.yml).
 */
define('IN_MYBB', 1);

$root = dirname(__DIR__);
$dst  = $root . '/back/forum';

$excludeDirs = array(
    '.git', '.github',
    'docs', 'scripts', 'cache', 'install', 'node_modules', 'test-results',
    'graphify-out', 'back',
);

$rootFilesWhitelist = array('.htaccess', 'favicon.ico', 'robots.txt');

function ope_build_rmrf($path)
{
    if (!is_dir($path)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($path);
}

function ope_build_is_root_deploy_file($base, array $whitelist)
{
    if (in_array($base, $whitelist, true)) {
        return true;
    }
    return substr($base, -4) === '.php';
}

ope_build_rmrf($dst);
mkdir($dst, 0777, true);

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    $parts = explode('/', $rel);
    $top  = $parts[0];
    $base = basename($rel);

    if (in_array($top, $excludeDirs, true)) {
        continue;
    }
    if (count($parts) === 1 && !$f->isDir()) {
        if (!ope_build_is_root_deploy_file($base, $rootFilesWhitelist)) {
            continue;
        }
    }
    if ($rel === 'inc/ope_rol/config/viaje_ai.php') {
        continue;
    }

    $target = $dst . '/' . $rel;
    if ($f->isDir()) {
        if (!is_dir($target)) {
            mkdir($target, 0777, true);
        }
    } else {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($f->getPathname(), $target);
    }
}

function ope_build_dirsize($dir)
{
    $sz = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $sz += $f->getSize();
    }
    return number_format($sz);
}

echo "docroot -> {$dst}\n";
echo "bytes  -> " . ope_build_dirsize($dst) . "\n";
