<?php
/**
 * One Piece Eternal · Consolidación de CSS inline -> ope.css
 * ---------------------------------------------------------
 * Mueve el bloque <style> de cada página PHP autónoma a docs/themes/ope.css,
 * SCOPEADO bajo una clase única de <body> (body.ope-pg-<pagina>), de modo que:
 *   - No queda CSS inline (una sola fuente de verdad, cacheada).
 *   - El render es idéntico (mismas reglas, solo relocalizadas + scopeadas).
 *   - No hay colisiones entre páginas ni con las plantillas MyBB.
 *
 *   php scripts/consolidate-css.php          (dry-run)
 *   php scripts/consolidate-css.php --apply
 */
$apply = in_array('--apply', $argv, true);
$root  = dirname(__DIR__);
$cssFile = $root . '/docs/themes/ope.css';

$pages = [
    'personajes.php', 'ficha.php', 'tramites.php', 'guias.php',
    'zona-staff.php', 'crear-personaje.php', 'alertas.php',
    'mensajes.php', 'revisar-personaje.php',
];

/** Prefija cada selector de una lista separada por comas con el scope. */
function scope_selectors(string $sel, string $scope): string {
    $parts = preg_split('/,(?![^(]*\))/', $sel); // no partir comas dentro de ()
    $res = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if ($p === ':root' || strpos($p, 'html') === 0) { $res[] = $p; continue; }
        if ($p === 'body' || strpos($p, 'body.') === 0 || strpos($p, 'body:') === 0 || strpos($p, 'body ') === 0 || strpos($p, 'body>') === 0) {
            $res[] = $scope . substr($p, 4); // 'body' -> scope, preserva sufijo (.x, :hover, ' ...', '>')
        } else {
            $res[] = $scope . ' ' . $p;
        }
    }
    return implode(',', $res);
}

/** Scopea un bloque CSS completo bajo $scope (maneja comentarios, @media, @keyframes). */
function scope_css(string $css, string $scope): string {
    $out = ''; $i = 0; $n = strlen($css);
    while ($i < $n) {
        $ch = $css[$i];
        if (ctype_space($ch)) { $out .= $ch; $i++; continue; }
        if (substr($css, $i, 2) === '/*') {
            $e = strpos($css, '*/', $i); $e = ($e === false) ? $n - 2 : $e;
            $out .= substr($css, $i, $e + 2 - $i); $i = $e + 2; continue;
        }
        // Leer prelude hasta '{'
        $j = $i;
        while ($j < $n && $css[$j] !== '{') $j++;
        if ($j >= $n) { $out .= substr($css, $i); break; }
        $prelude = trim(substr($css, $i, $j - $i));
        // Encontrar '}' que cierra este bloque
        $k = $j + 1; $d = 1;
        while ($k < $n && $d > 0) {
            if ($css[$k] === '{') $d++;
            elseif ($css[$k] === '}') { $d--; if ($d === 0) break; }
            $k++;
        }
        $body = substr($css, $j + 1, $k - $j - 1);
        if (preg_match('/^@(media|supports|-moz-document)/i', $prelude)) {
            $out .= $prelude . '{' . scope_css($body, $scope) . '}';
        } elseif (preg_match('/^@/', $prelude)) { // keyframes, font-face, page...
            $out .= $prelude . '{' . $body . '}';
        } else {
            $out .= scope_selectors($prelude, $scope) . '{' . $body . '}';
        }
        $i = $k + 1;
    }
    return $out;
}

$collected = "\n\n/* ============================================================\n";
$collected .= "   PÁGINAS AUTÓNOMAS (CSS consolidado, antes inline) — scopeado por\n";
$collected .= "   body.ope-pg-<pagina>. Fuente ÚNICA: NO reintroducir <style> en PHP.\n";
$collected .= "   ============================================================ */\n";

$report = [];
foreach ($pages as $page) {
    $path = $root . '/' . $page;
    if (!is_file($path)) { $report[] = "$page: no existe"; continue; }
    $src = file_get_contents($path);
    if (!preg_match('/<style>(.*?)<\/style>/s', $src, $m)) { $report[] = "$page: sin <style>"; continue; }

    $slug  = 'ope-pg-' . preg_replace('/\.php$/', '', $page);
    $scope = 'body.' . $slug;
    $inner = $m[1];
    $scoped = scope_css($inner, $scope);

    $collected .= "\n/* ---- {$page} ---- */\n" . trim($scoped) . "\n";

    // Quitar el bloque <style> del PHP (deja un comentario de rastro).
    $newSrc = preg_replace('/<style>.*?<\/style>/s',
        '<!-- estilos en docs/themes/ope.css (scope: ' . $slug . ') -->', $src, 1);

    // Añadir la clase de scope al <body> (soporta <body> y <body ...>).
    if (preg_match('/<body\s+class\s*=\s*"([^"]*)"/i', $newSrc)) {
        $newSrc = preg_replace('/(<body\s+class\s*=\s*")([^"]*)(")/i', '$1' . $slug . ' $2$3', $newSrc, 1);
    } elseif (preg_match('/<body(\s[^>]*)?>/i', $newSrc)) {
        $newSrc = preg_replace('/<body(\s[^>]*)?>/i', '<body class="' . $slug . '"$1>', $newSrc, 1);
    }

    $report[] = sprintf("%-24s -> scope %s (%d bytes CSS)", $page, $slug, strlen($scoped));
    if ($apply) file_put_contents($path, $newSrc);
}

if ($apply) {
    file_put_contents($cssFile, file_get_contents($cssFile) . $collected);
}

echo implode("\n", $report) . "\n\n";
echo ($apply ? 'APLICADO' : 'DRY-RUN') . ": " . strlen($collected) . " bytes añadidos a ope.css\n";
if (!$apply) echo "Ejecuta con --apply para escribir.\n";
