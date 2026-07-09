<?php
/**
 * check-inline-styles.php
 *
 * Escanea archivos PHP del proyecto y reporta ocurrencias de style= inline
 * en HTML. Solo marca los inlines ESTÁTICOS (sin variables PHP/JS y sin
 * asignación de CSS custom properties --var).
 *
 * Uso:
 *   php scripts/check-inline-styles.php              # reporte completo
 *   php scripts/check-inline-styles.php --summary    # solo conteo
 *   php scripts/check-inline-styles.php --ci         # exit 0/1 para CI
 *   php scripts/check-inline-styles.php --fix-report # lista plana
 *   php scripts/check-inline-styles.php --all        # incluye dinámicos
 */

$base = __DIR__ . '/..';

// Directorios a excluir completamente
$excludeDirs = [
    'admin', 'cache', 'uploads', 'jscripts', 'install', 'archive',
    'vendor', 'node_modules', 'images',
];

// Archivos MyBB core / third-party que NO se deben tocar
$coreFiles = [
    'global.php', 'xmlhttp.php', 'forumdisplay.php',
    'inc/class_parser.php',
    'inc/db_mysql_pdo.php', 'inc/db_pgsql_pdo.php',
    'inc/languages/english/admin/config_settings.lang.php',
    'inc/plugins/rolbridge.php',
    'mybb-plugin-rol/inc/plugins/rolbridge.php',
];

$excludeFiles = [
    'check-inline-styles.php',
    ...$coreFiles,
];

function shouldExclude(string $rel, array $excludeDirs): bool {
    foreach ($excludeDirs as $d) {
        if (str_starts_with($rel, $d . '/') || $rel === $d) return true;
    }
    return false;
}

/**
 * Detecta si un valor style contiene variables dinámicas
 * (PHP echo/var, JS concat, CSS custom props)
 */
function isDynamic(string $styleValue): bool {
    // Contiene PHP
    if (preg_match('/<\?php|\$[a-zA-Z_]/', $styleValue)) return true;
    // Contiene JS concatenación
    if (str_contains($styleValue, "+'") || str_contains($styleValue, "'+") || str_contains($styleValue, '"+') || str_contains($styleValue, '+"')) return true;
    if (str_contains($styleValue, '${') || str_contains($styleValue, 'd.') || str_contains($styleValue, 't.')) return true;
    // Solo CSS custom properties (--var: value)
    if (preg_match('/^--[a-zA-Z]/', trim($styleValue))) return true;
    // Usa var(--...) dentro del valor (variable de tema dinámica)
    if (preg_match('/var\(--[a-zA-Z]/', $styleValue)) return true;
    return false;
}

function findInlineStyles(string $content, bool $all): array {
    $hits = [];
    $re = '/(?<![\$>?])\bstyle\s*=\s*(["\'])(?:(?!\1).)*\1/i';
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (preg_match($re, $line, $m, PREG_OFFSET_CAPTURE)) {
            $styleAttr = $m[0][0];
            // Extraer el valor entre las comillas
            $quote = $m[1][0];
            $valStart = $m[0][1] + strlen('style=') + 1; // after style="
            $valLen = strpos($line, $quote, $valStart) - $valStart;
            if ($valLen === false) continue;
            $styleValue = substr($line, $valStart, $valLen);

            if (!$all && isDynamic($styleValue)) continue;

            $truncated = substr(trim($line), 0, 150);
            $hits[] = [
                'line' => $i + 1,
                'snippet' => $truncated,
                'dynamic' => isDynamic($styleValue),
            ];
        }
    }
    return $hits;
}

// ── collect ──

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
$results = [];
$all = in_array('--all', $_SERVER['argv'] ?? [], true);

foreach ($rii as $file) {
    if (!$file->isFile()) continue;
    if ($file->getExtension() !== 'php') continue;

    $rel = str_replace($base . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $rel = str_replace('\\', '/', $rel);
    if (shouldExclude($rel, $excludeDirs)) continue;
    if (in_array($rel, $excludeFiles, true)) continue;
    if (in_array(basename($rel), $excludeFiles, true)) continue;

    $content = file_get_contents($file->getPathname());
    if ($content === false || !str_contains($content, 'style')) continue;

    $hits = findInlineStyles($content, $all);
    if (!empty($hits)) {
        $results[$rel] = $hits;
    }
}

// ── output ──

$total = array_sum(array_map('count', $results));
$opts = $_SERVER['argv'] ?? [];

$tag = $all ? '(incluye dinámicos)' : '(solo estáticos; --all para incluir dinámicos)';

if (in_array('--ci', $opts, true)) {
    exit($total > 0 ? 1 : 0);
}

if (in_array('--summary', $opts, true)) {
    echo "Archivos con inline styles: " . count($results) . " $tag\n";
    echo "Total inline styles: $total\n";
    if ($total > 0) {
        echo "\nPor archivo:\n";
        foreach ($results as $rel => $hits) {
            printf("  %-50s %d\n", $rel, count($hits));
        }
    }
    exit(0);
}

if (in_array('--fix-report', $opts, true)) {
    foreach ($results as $rel => $hits) {
        foreach ($hits as $h) {
            $prefix = $h['dynamic'] ? '[DIN]' : '[EST]';
            printf("%s %s:%d  %s\n", $prefix, $rel, $h['line'], $h['snippet']);
        }
    }
    exit(0);
}

if ($total === 0) {
    echo "\n✓ No se encontraron inline styles estáticos.\n";
    exit(0);
}

echo "\n✗ $total inline styles estáticos en " . count($results) . " archivos $tag:\n\n";
foreach ($results as $rel => $hits) {
    $staticCount = count(array_filter($hits, fn($h) => !$h['dynamic']));
    echo "  \e[37;1m$rel\e[0m ($staticCount estáticos)\n";
    foreach ($hits as $h) {
        $label = $h['dynamic'] ? '  [DIN]' : '  \e[33;1m[EST]\e[0m';
        echo "    {$label} L{$h['line']}  {$h['snippet']}\n";
    }
    echo "\n";
}

echo "\nResumen: $total inline styles en " . count($results) . " archivos.\n";
exit(1);
