<?php
/**
 * Chequeo estructural de parseo PHP (sin BD).
 * -------------------------------------------------
 * Detecta la clase de bugs que rompieron el plugin el 2026-08-29 (F6.3):
 *
 *   1. El cierre de etiqueta PHP (los caracteres `?` `>`) dentro de un comentario
 *      `/* *\/` o suelto Cierra el modo PHP: todo lo posterior del archivo se
 *      vuelve HTML inline y las funciones dejan de definirse (fatal en runtime,
 *      aunque `php -l` pase).
 *   2. Una función nombrada ANIDADA dentro de otra (o de un bloque que no es un
 *      guard `if (!function_exists(...))`): solo se define si la exterior corre.
 *   3. Close tags / HTML inline / BOM UTF-8 en PHP puro (bibliotecas).
 *
 * Qué barre: PHP puro del motor — inc/ope_rol/**, inc/plugins/*.php,
 * inc/tasks/*.php, inc/ope_rol_*.php y scripts/*.php. Las páginas públicas
 * (raíz) son plantillas híbridas HTML+PHP y quedan fuera; la única excepción
 * dentro del barrido es `inc/ope_rol/tramites/paginas.php` (genera las páginas
 * de ventanilla con HTML real intercalado, es legítimo).
 *
 * Uso:
 *   php scripts/check-parseo.php            # barrido estándar
 *   php scripts/check-parseo.php ARCHIVO... # añade archivos sueltos (smoke)
 *
 * Exit code 0 = todo limpio; 1 = se encontraron problemas.
 */

$ROOT = str_replace('\\', '/', dirname(__DIR__));

// Híbridos conocidos DENTRO del barrido (HTML real intercalado, legítimo).
$HYBRID = array(
    'inc/ope_rol/tramites/paginas.php',
);

// ── Recoger archivos ────────────────────────────────────────────────────────
$files = array();
foreach (array('inc/ope_rol', 'inc/plugins', 'inc/tasks', 'scripts') as $dir) {
    $path = $ROOT . '/' . $dir;
    if (!is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') {
            $files[] = str_replace('\\', '/', $f->getPathname());
        }
    }
}
foreach (glob($ROOT . '/inc/ope_rol_*.php') as $f) {
    $files[] = str_replace('\\', '/', $f);
}
// Archivos extra pasados por argv (smoke tests).
for ($i = 1; $i < $argc; $i++) {
    $files[] = str_replace('\\', '/', $argv[$i]);
}
$files = array_values(array_unique($files));
sort($files);

// ── Escáner de un archivo ───────────────────────────────────────────────────
/**
 * @return array{issues:string[], funcs:int}
 */
function ope7_parseo_scan($s)
{
    $tokens = token_get_all($s);

    $issues = array();
    $funcs = 0;

    $depth = 0;          // profundidad de llaves reales (sin interpolación)
    $interp = false;     // cierre de interpolación {$var} en string doble
    $last = 0;           // última línea con tokens
    $close_tags = 0;     // cierres de etiqueta PHP (T_CLOSE_TAG)
    $inline_html = 0;    // T_INLINE_HTML no vacío (incluye BOM)
    $in_guard = array(); // profundidad => true si el bloque es `if (!function_exists(...))`

    $pending = false;    // tras T_FUNCTION: esperando nombre o ( = closure
    $fn_line = 0;

    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];

        if (is_array($t)) {
            $id = $t[0];
            $last = max($last, $t[2]);

            if ($id === T_FUNCTION) {
                $pending = true;
                $fn_line = $t[2];
                continue;
            }
            if ($pending) {
                if ($id === T_STRING) {
                    $pending = false;
                    $funcs++;
                    if ($depth > 0 && empty($in_guard[$depth])) {
                        $issues[] = "función anidada {$t[1]} en L{$fn_line} (depth {$depth}) — solo se define si el bloque exterior corre";
                    }
                    continue;
                }
                if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                    continue;
                }
                if ($t[1] === '&') { // función por referencia: function &nombre()
                    continue;
                }
                $pending = false; // closure (function ( ... )
                continue;
            }

            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $interp = true;
                continue;
            }
            if ($id === T_CLOSE_TAG) {
                $close_tags++;
                continue;
            }
            if ($id === T_INLINE_HTML && trim($t[1]) !== '') {
                $inline_html++;
                continue;
            }
            if ($id === T_IF) {
                // ¿Guard `if (!function_exists(...)) { function ... }`?
                // Escanea la condición hasta el `{` del cuerpo a profundidad de paréntesis 0.
                $guard = false;
                $paren = 0;
                for ($j = $i + 1; $j < $n; $j++) {
                    $u = $tokens[$j];
                    if (is_array($u)) {
                        if ($u[0] === T_STRING && $u[1] === 'function_exists') {
                            $guard = true;
                        }
                        continue;
                    }
                    if ($u === '(') {
                        $paren++;
                    } elseif ($u === ')') {
                        $paren--;
                    } elseif ($u === '{' && $paren === 0) {
                        break;
                    }
                }
                $guard_pending = $guard;
                continue;
            }
            continue;
        }

        // Tokens de un carácter.
        if ($t === '(' && $pending) {
            $pending = false; // closure
            continue;
        }
        if ($t === '{') {
            $depth++;
            $in_guard[$depth] = !empty($guard_pending);
            $guard_pending = false;
            continue;
        }
        if ($t === '}') {
            if ($interp) {
                $interp = false;
                continue;
            }
            unset($in_guard[$depth]);
            $depth--;
            continue;
        }
    }

    if ($close_tags > 0) {
        $issues[] = "{$close_tags} close tag(s) `?>` (cierra el modo PHP)";
    }
    if ($inline_html > 0) {
        $issues[] = "{$inline_html} bloque(s) de HTML inline (posible `?>` en comentario o BOM)";
    }
    if ($depth !== 0) {
        $issues[] = "llaves sin balancear al final (depth {$depth})";
    }

    return array($issues, $funcs, $last);
}

// ── Ejecutar ────────────────────────────────────────────────────────────────
$total_issues = 0;
$total_funcs = 0;
$fail = array();

foreach ($files as $f) {
    $rel = str_replace('\\', '/', $f);
    if (strpos($rel, $ROOT . '/') === 0) {
        $rel = substr($rel, strlen($ROOT) + 1);
    }
    if (!is_file($f)) {
        $fail[] = "$rel :: archivo no encontrado";
        $total_issues++;
        continue;
    }

    $s = @file_get_contents($f);
    if ($s === false) {
        $fail[] = "$rel :: no legible";
        $total_issues++;
        continue;
    }

    $is_hybrid = in_array($rel, $HYBRID, true);
    list($issues, $funcs, $last) = ope7_parseo_scan($s);
    $total_funcs += $funcs;

    // Los híbridos intercalan HTML legítimamente: solo se controlan las funciones anidadas.
    if ($is_hybrid) {
        $issues = array_values(array_filter($issues, function ($msg) {
            return strpos($msg, 'función anidada') === 0;
        }));
    }

    // Regla de cierre temprano (el tokenizador debe llegar al final del archivo).
    $lines = substr_count($s, "\n") + 1;
    if (!$is_hybrid && $last > 0 && $last < $lines - 10) {
        $issues[] = "el tokenizador termina en L{$last} (archivo de {$lines} líneas) — posible cierre temprano de PHP";
    }

    if (!empty($issues)) {
        $fail[] = $rel . " :: " . implode(' | ', $issues);
        $total_issues += count($issues);
    }
}

// ── Salida ──────────────────────────────────────────────────────────────────
if ($fail) {
    echo "FALLO — problemas de parseo detectados:\n\n";
    foreach ($fail as $line) {
        echo "  - " . $line . "\n";
    }
    echo "\n{$total_issues} problema(s) en " . count($files) . " archivo(s), {$total_funcs} funciones revisadas.\n";
    exit(1);
}

echo "OK — " . count($files) . " archivos PHP puros tokenizados hasta el final, " .
     $total_funcs . " funciones a nivel superior, sin close tags ni anidadas.\n";
exit(0);
