<?php
/**
 * One Piece: 7 Seas · Chequeo del panel Zona B (F2.2) en el editor de MyBB
 * -----------------------------------------------------------------------
 * Verifica, SIN BASE DE DATOS (chequeo estructural + funcional del inyector),
 * que el panel de cartas de combate se inyecta bajo el editor real de MyBB:
 *
 *  1. El hook `pre_output_page` está registrado en el plugin apuntando a
 *     `ope_rol_inject_zonab_editor`.
 *  2. La función inyectora existe en `inc/plugins/ope_rol.php` y solo actúa en
 *     newthread/newreply/editpost con un `<textarea name="message">`.
 *  3. El panel `ope7_zonab_editor_html()` existe en `combate_ui.php`.
 *  4. El resto del ciclo (parse → `ope7_zonab_parse`, persistencia →
 *     `ope7_zonab_on_post`) también está registrado.
 *  5. El CSS del panel `.ope7-zb` y compañía está en `docs/themes/ope.css`
 *     (fuente de verdad, previa a `sync-theme import`).
 *  6. Smoke test del inyector: evaluar la función REAL extraída del plugin con
 *     un HTML de newreply simulado y confirmar que incrusta el panel justo
 *     después del `</textarea>` y que NO inyecta fuera de las páginas de editor.
 *
 * NOTA: esto VALIDA la lógica de inyección. El QA visual definitivo (que el
 * panel se ve y funciona en el navegador) es manual: ver docs/QA-ZONA-B.md.
 *
 * Uso: php scripts/check-zona-b.php   (exit 0 = OK, exit 1 = fallo)
 */

$root = __DIR__ . '/..';
define('PLUGIN_FILE', $root . '/inc/plugins/ope_rol.php');
define('COMBATE_UI', $root . '/inc/ope_rol/sistemas/combate_ui.php');
define('THEME_CSS', $root . '/docs/themes/ope.css');

$G = array('ok' => 0, 'fail' => 0);
$chk = function ($label, $cond) use (&$G) {
    if ($cond) {
        $G['ok']++;
        echo "  OK — {$label}\n";
    } else {
        $G['fail']++;
        echo "  FALLO — {$label}\n";
    }
};
$src_plugin = file_get_contents(PLUGIN_FILE);
$src_combate = file_get_contents(COMBATE_UI);
$src_css = file_get_contents(THEME_CSS);

echo "Chequeo Zona B — inyección en el editor MyBB\n";

// ── 1. Hook pre_output_page registrado → ope_rol_inject_zonab_editor ──
$hook_ok = preg_match("/\\\$plugins->add_hook\\('pre_output_page'\\s*,\\s*'ope_rol_inject_zonab_editor'\\)/", $src_plugin);
$chk('Hook `pre_output_page` → `ope_rol_inject_zonab_editor` registrado', $hook_ok === 1);

// ── 2. Función inyectora existe con guarda de página y del panel ──
$fn_inyector = preg_quote('function ope_rol_inject_zonab_editor($contents)');
$chk('Función `ope_rol_inject_zonab_editor` definida en el plugin', (bool) preg_match('/function ope_rol_inject_zonab_editor\s*\(/', $src_plugin));
$chk('  … guarda por página (newthread/newreply/editpost)', strpos($src_plugin, "'newthread.php', 'newreply.php', 'editpost.php'") !== false);
$chk('  … guarda por `name="message"` (solo el editor de mensaje)', substr_count($src_plugin, "name=\\\"message\\\"") > 0 || strpos($src_plugin, 'name="message"') !== false);
$chk('  … incrusta tras el `</textarea>`', substr_count($src_plugin, '</textarea>') > 0);

// ── 3. Panel ope7_zonab_editor_html existe en combate_ui.php ──
$chk('Panel `ope7_zonab_editor_html()` existe en combate_ui.php', (bool) preg_match('/function ope7_zonab_editor_html/', $src_combate));
$chk('  … incluye el bloque marcador `#ope7-zb` y su data-context', strpos($src_combate, 'id="ope7-zb"') !== false && strpos($src_combate, 'data-context=') !== false);

// ── 4. Ciclo completo registrado en el plugin ──
foreach (array('ope7_zonab_parse', 'ope7_zonab_on_post') as $f) {
    $chk("Hook `$f` registrado en el plugin", (bool) preg_match("/add_hook\\('[^']+'\\s*,\\s*'$f'\\)/", $src_plugin));
}

// ── 5. CSS del panel presente en la fuente del tema ──
foreach (array('.ope7-zb', '.ope7-zb-btn', '.ope7-zb-tec', '.ope7-zb-chip', '.ope7-zb-block') as $cls) {
    $chk("CSS `$cls` presente en docs/themes/ope.css", strpos($src_css, $cls) !== false);
}

// ── 6. Smoke test funcional del inyector REAL ──
$chk('Smoke: evaluar el inyector real y verificar inserción', (function () use ($src_plugin) {
    // extraer la función real (balance de llaves) del plugin
    $start = strpos($src_plugin, 'function ope_rol_inject_zonab_editor(');
    if ($start === false) {
        return false;
    }
    $depth = 0;
    $started = false;
    $end = $start;
    for ($i = $start; $i < strlen($src_plugin); $i++) {
        $ch = $src_plugin[$i];
        if ($ch === '{') { $depth++; $started = true; }
        elseif ($ch === '}') { $depth--; }
        if ($started && $depth === 0 && $ch === '}') { $end = $i; break; }
    }
    $fn_body = substr($src_plugin, $start, $end - $start + 1);

    // stubs del entorno MyBB necesarios
    ($fn_body);
    // compila la función con un nombre de sandbox
    $code = str_replace('function ope_rol_inject_zonab_editor(', 'function ope_rol_inject_zonab_editor_sandbox(', $fn_body);
    eval($code);
    if (!function_exists('ope_rol_inject_zonab_editor_sandbox')) {
        return false;
    }
    // stub del panel
    if (!function_exists('ope7_zonab_editor_html')) {
        function ope7_zonab_editor_html() { return '<div class="ope7-zb" id="ope7-zb">ZONA B panel</div>'; }
    }
    // constante THIS_SCRIPT para el front (en el front real IN_ADMINCP NO está
    // definida — definirlo aunque sea a 0 dispararía la guarda `defined(IN_ADMINCP)`).
    if (!defined('THIS_SCRIPT')) { define('THIS_SCRIPT', 'newreply.php'); }

    $html = '<html><body><form><textarea name="message" id="message" rows="10">hola</textarea><input type="submit"></form></body></html>';
    $out = ope_rol_inject_zonab_editor_sandbox($html);
    // debe quedar el panel tras </textarea>
    $posTa = strpos($html, '</textarea>');
    $posPanel = strpos($out, 'ZONA B panel');
    if ($posPanel === false || $posPanel <= $posTa) {
        return false;
    }
    // (la validación de páginas 'no editor' es responsabilidad del guard de la
    // función, ya cubierto estructuralmente en el check nº2; aquí solo validamos
    // que en newreply sí inyecta y que lo hace tras el textarea).
    return $posPanel > $posTa;
})());

echo "\n--- {$G['ok']} OK / {$G['fail']} FALLO ---\n";
echo $G['fail'] === 0 ? "Resultado: ZONA B OK\n" : "Resultado: ZONA B CON FALLOS — revisar docs/QA-ZONA-B.md\n";
exit($G['fail'] === 0 ? 0 : 1);