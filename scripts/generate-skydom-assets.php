<?php
/**
 * Genera/copia paneles JPG 16:9 para Skydoms en images/gbe/skydom-*.jpg
 * Ejecutar: php scripts/generate-skydom-assets.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$dir = $root . '/images/ope';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

function copy_asset(string $src, string $dst): void
{
    if (!is_file($src)) {
        fwrite(STDERR, "  [skip] falta origen: $src\n");
        return;
    }
    if (!copy($src, $dst)) {
        fwrite(STDERR, "  [error] no se pudo copiar a $dst\n");
        exit(1);
    }
    echo '  [ok] ' . basename($dst) . ' (' . round(filesize($dst) / 1024) . " KB)\n";
}

function hex_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

function write_gradient_jpg(string $path, string $topHex, string $bottomHex, int $w = 1280, int $h = 720): void
{
    if (!function_exists('imagecreatetruecolor')) {
        fwrite(STDERR, "GD no disponible; no se puede generar $path\n");
        exit(1);
    }
    [$tr, $tg, $tb] = hex_rgb($topHex);
    [$br, $bg, $bb] = hex_rgb($bottomHex);
    $im = imagecreatetruecolor($w, $h);
    for ($y = 0; $y < $h; $y++) {
        $t = $h <= 1 ? 0 : $y / ($h - 1);
        $r = (int) round($tr + ($br - $tr) * $t);
        $g = (int) round($tg + ($bg - $tg) * $t);
        $b = (int) round($tb + ($bb - $tb) * $t);
        $col = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $w, $y, $col);
    }
    // Bruma suave tipo acuarela OPE
    $mist = imagecolorallocatealpha($im, 255, 255, 255, 110);
    imagefilledellipse($im, (int) ($w * 0.72), (int) ($h * 0.28), (int) ($w * 0.55), (int) ($h * 0.42), $mist);
    imagejpeg($im, $path, 88);
    imagedestroy($im);
    echo '  [ok] ' . basename($path) . ' (' . round(filesize($path) / 1024) . " KB, generado)\n";
}

echo "=== Skydom assets → images/gbe/ ===\n";

$copyMap = [
    'skydom-phantagrande-skydom.jpg' => 'hero-mundo.jpg',
    'skydom-nalhegrande-skydom.jpg' => 'hero-pueblos.jpg',
    'skydom-auguste-skydom.jpg' => 'hero-primal.jpg',
];
foreach ($copyMap as $dstName => $srcName) {
    copy_asset($dir . '/' . $srcName, $dir . '/' . $dstName);
}

write_gradient_jpg($dir . '/skydom-zeephone-skydom.jpg', '#7eb8d8', '#3a6f9a');
write_gradient_jpg($dir . '/skydom-estalucia.jpg', '#d4c4a8', '#4a6a8f');

echo "Hecho.\n";
