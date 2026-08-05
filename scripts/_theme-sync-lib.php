<?php
/**
 * Shared helpers for One Piece: Eternal theme sync (repo <-> MyBB DB).
 *
 * Canonical sources in docs/themes/:
 *   - ope.css                      (stylesheet)
 *   - ope-shared.xml       (auxiliary MyBB templates)
 *   - ope-index.xml
 *   - ope-forumdisplay.xml
 *   - ope-showthread.xml
 *   - ope-forms.xml
 *
 * ope-child-theme.xml is GENERATED (build-xml) for Admin CP import only.
 */

define('OPE_THEME_ROOT', dirname(__DIR__) . '/docs/themes');
define('OPE_CSS_FILE', OPE_THEME_ROOT . '/ope.css');
define('OPE_CHILD_XML', OPE_THEME_ROOT . '/ope-child-theme.xml');
define('OPE_CHILD_BUNDLE_XML', OPE_THEME_ROOT . '/ope-child-theme.bundle.xml');

/** Import order: earlier files are overridden by later ones on name collision. */
define('OPE_TEMPLATE_XML_FILES', [
    OPE_THEME_ROOT . '/ope-shared.xml',
    OPE_THEME_ROOT . '/ope-forms.xml',
    OPE_THEME_ROOT . '/ope-showthread.xml',
    OPE_THEME_ROOT . '/ope-forumdisplay.xml',
    OPE_THEME_ROOT . '/ope-index.xml',
]);

function ope_db_connect(): mysqli
{
    require __DIR__ . '/_db-config.php';
    return $db;
}

function ope_resolve_theme(mysqli $db): array
{
    $result = $db->query("SELECT tid, name, properties FROM mybb_themes WHERE name IN ('One Piece: Eternal','RPG') ORDER BY tid DESC LIMIT 1");
    $theme = $result ? $result->fetch_assoc() : null;
    if (!$theme) {
        fwrite(STDERR, "Theme 'One Piece: Eternal' (o legado RPG) not found. Create it first via Admin CP or scripts/import-theme.php install.\n");
        exit(1);
    }
    $props = @unserialize($theme['properties']);
    $templateset = (int)($props['templateset'] ?? 0);
    if ($templateset === 0) {
        fwrite(STDERR, "Theme tid={$theme['tid']} has no templateset in properties.\n");
        exit(1);
    }
    return [
        'tid' => (int)$theme['tid'],
        'name' => $theme['name'],
        'templateset' => $templateset,
        'properties' => is_array($props) ? $props : [],
    ];
}

function ope_read_css(): string
{
    if (!is_file(OPE_CSS_FILE)) {
        fwrite(STDERR, "Missing canonical CSS: " . OPE_CSS_FILE . "\n");
        exit(1);
    }
    $css = file_get_contents(OPE_CSS_FILE);
    if ($css === false || $css === '') {
        fwrite(STDERR, "Could not read CSS file or it is empty.\n");
        exit(1);
    }
    // Normalizar a UTF-8 (PowerShell Add-Content en Windows a veces deja Windows-1252).
    if (!mb_check_encoding($css, 'UTF-8')) {
        $converted = @mb_convert_encoding($css, 'UTF-8', 'Windows-1252');
        if ($converted !== false && $converted !== '') {
            $css = $converted;
        }
    }
    // BOM
    if (strncmp($css, "\xEF\xBB\xBF", 3) === 0) {
        $css = substr($css, 3);
    }
    return $css;
}

/** @return array<string, array{file:string, version:string, content:string}> */
function ope_load_repo_templates(): array
{
    $templates = [];
    foreach (OPE_TEMPLATE_XML_FILES as $path) {
        if (!is_file($path)) {
            fwrite(STDERR, "Missing template XML: $path\n");
            exit(1);
        }
        $xml = simplexml_load_file($path);
        if (!$xml || !$xml->templates || !$xml->templates->template) {
            continue;
        }
        foreach ($xml->templates->template as $tpl) {
            $name = (string)$tpl['name'];
            $templates[$name] = [
                'file' => basename($path),
                'version' => (string)($tpl['version'] ?? '1839'),
                'content' => (string)$tpl,
            ];
        }
    }
    return $templates;
}

/** @return array<string, string> template name => owning filename */
function ope_template_ownership_map(): array
{
    $map = [];
    foreach (ope_load_repo_templates() as $name => $meta) {
        $map[$name] = $meta['file'];
    }
    return $map;
}

function ope_import_css(mysqli $db, int $tid, bool $quiet = false): void
{
    $css = ope_read_css();
    $name = 'ope.css';
    $now = time();
    $rel_cache = "cache/themes/theme{$tid}/{$name}";

    // Evitar mismatch utf8mb4 connection ↔ columnas utf8mb3 legacy.
    @$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

    $stmt = $db->prepare('SELECT sid FROM mybb_themestylesheets WHERE tid = ? AND name = ?');
    if (!$stmt) {
        fwrite(STDERR, "  CSS prepare SELECT failed: {$db->error}\n");
        exit(1);
    }
    $stmt->bind_param('is', $tid, $name);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $up = $db->prepare('UPDATE mybb_themestylesheets SET stylesheet = ?, cachefile = ?, lastmodified = ? WHERE tid = ? AND name = ?');
        if (!$up) {
            fwrite(STDERR, "  CSS prepare UPDATE failed: {$db->error}\n");
            exit(1);
        }
        $up->bind_param('ssiis', $css, $name, $now, $tid, $name);
        $okExec = false;
        try {
            $okExec = $up->execute();
        } catch (Throwable $e) {
            $okExec = false;
        }
        if (!$okExec) {
            // Fallback sin prepared (por si la colación de la tabla sigue mal).
            $esc = $db->real_escape_string($css);
            $ename = $db->real_escape_string($name);
            $ok = $db->query("UPDATE mybb_themestylesheets SET stylesheet = '{$esc}', cachefile = '{$ename}', lastmodified = {$now} WHERE tid = {$tid} AND name = '{$ename}'");
            if (!$ok) {
                fwrite(STDERR, "  CSS UPDATE failed: {$up->error} / {$db->error}\n");
                exit(1);
            }
            if (!$quiet) {
                echo "  CSS: $name updated via fallback (" . strlen($css) . " bytes)\n";
            }
        } elseif (!$quiet) {
            echo "  CSS: $name updated (" . strlen($css) . " bytes)\n";
        }
        $up->close();
    } else {
        $in = $db->prepare('INSERT INTO mybb_themestylesheets (name, tid, stylesheet, cachefile, attachedto, lastmodified) VALUES (?, ?, ?, ?, \'\', ?)');
        if (!$in) {
            fwrite(STDERR, "  CSS prepare INSERT failed: {$db->error}\n");
            exit(1);
        }
        $in->bind_param('sissi', $name, $tid, $css, $name, $now);
        $okExec = false;
        try {
            $okExec = $in->execute();
        } catch (Throwable $e) {
            $okExec = false;
        }
        if (!$okExec) {
            $esc = $db->real_escape_string($css);
            $ename = $db->real_escape_string($name);
            $ok = $db->query("INSERT INTO mybb_themestylesheets (name, tid, stylesheet, cachefile, attachedto, lastmodified) VALUES ('{$ename}', {$tid}, '{$esc}', '{$ename}', '', {$now})");
            if (!$ok) {
                fwrite(STDERR, "  CSS INSERT failed: {$in->error} / {$db->error}\n");
                exit(1);
            }
            if (!$quiet) {
                echo "  CSS: $name inserted via fallback (" . strlen($css) . " bytes)\n";
            }
        } elseif (!$quiet) {
            echo "  CSS: $name inserted (" . strlen($css) . " bytes)\n";
        }
        $in->close();
    }

    $theme_dir = dirname(__DIR__) . "/cache/themes/theme{$tid}";
    if (!is_dir($theme_dir)) {
        @mkdir($theme_dir, 0755, true);
    }
    $cache_path = "{$theme_dir}/ope.css";
    if (@file_put_contents($cache_path, $css) === false) {
        fwrite(STDERR, "  WARNING: could not write $cache_path\n");
    } elseif (!$quiet) {
        echo "  CSS: cache written -> cache/themes/theme{$tid}/ope.css\n";
    }

    // Keep themes.stylesheets + properties.disporder pointing at ope.css.
    // Critical: never leave a nested properties['stylesheets'] — global.php
    // array_merge() would overwrite the stylesheets *column* and my_unserialize()
    // then returns false → {$stylesheets} empty (unstyled forum).
    ope_ensure_theme_stylesheet_wiring($db, $tid, $rel_cache, $quiet);
}

/**
 * Wire theme13 stylesheets path + disporder; strip properties.stylesheets trap.
 */
function ope_ensure_theme_stylesheet_wiring(mysqli $db, int $tid, string $rel_cache, bool $quiet = false): void
{
    $r = $db->query("SELECT properties, stylesheets FROM mybb_themes WHERE tid={$tid} LIMIT 1");
    if (!$r || !($row = $r->fetch_assoc())) {
        return;
    }

    $ss = @unserialize($row['stylesheets']);
    if (!is_array($ss)) {
        $ss = array();
    }
    if (!isset($ss['global']['global']) || !is_array($ss['global']['global'])) {
        $ss['global']['global'] = array();
    }

    $global = array();
    $seen = array();
    foreach ($ss['global']['global'] as $path) {
        $path = str_replace(
            array("cache/themes/theme{$tid}/gbe.css", 'cache/themes/theme13/gbe.css'),
            $rel_cache,
            (string) $path
        );
        if (isset($seen[$path])) {
            continue;
        }
        $seen[$path] = true;
        $global[] = $path;
    }
    if (!in_array($rel_cache, $global, true)) {
        array_unshift($global, $rel_cache);
    }
    $ss['global']['global'] = $global;

    $props = @unserialize($row['properties']);
    if (!is_array($props)) {
        $props = array();
    }
    $had_nested = isset($props['stylesheets']);
    unset($props['stylesheets']);
    if (!isset($props['disporder']) || !is_array($props['disporder'])) {
        $props['disporder'] = array();
    }
    unset($props['disporder']['gbe.css']);
    $props['disporder']['ope.css'] = 1;

    $ss_esc = $db->real_escape_string(serialize($ss));
    $props_esc = $db->real_escape_string(serialize($props));
    $db->query("UPDATE mybb_themes SET stylesheets='{$ss_esc}', properties='{$props_esc}' WHERE tid={$tid}");

    if (!$quiet) {
        echo "  CSS: theme wiring ok (ope.css in stylesheets" . ($had_nested ? '; stripped properties.stylesheets' : '') . ")\n";
    }
}

function ope_import_templates(mysqli $db, int $templateset, bool $quiet = false): int
{
    $repo = ope_load_repo_templates();
    $count = 0;

    foreach ($repo as $title => $meta) {
        $content = $meta['content'];
        $version = $meta['version'];

        $stmt = $db->prepare('SELECT tid FROM mybb_templates WHERE title = ? AND sid = ?');
        $stmt->bind_param('si', $title, $templateset);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            $up = $db->prepare('UPDATE mybb_templates SET template = ?, version = ? WHERE title = ? AND sid = ?');
            $up->bind_param('sssi', $content, $version, $title, $templateset);
            $up->execute();
            $up->close();
            if (!$quiet) {
                echo "  [+] {$title} ({$meta['file']})\n";
            }
        } else {
            $stmt->close();
            $now = time();
            $in = $db->prepare('INSERT INTO mybb_templates (title, template, sid, version, status, dateline) VALUES (?, ?, ?, ?, 1, ?)');
            $in->bind_param('ssisi', $title, $content, $templateset, $version, $now);
            $in->execute();
            $in->close();
            if (!$quiet) {
                echo "  [N] {$title} ({$meta['file']})\n";
            }
        }
        $count++;
    }
    return $count;
}

function ope_clear_theme_caches(mysqli $db): void
{
    $db->query("DELETE FROM mybb_datacache WHERE title IN ('themes', 'themestylesheets', 'templates', 'default_theme')");
    $db->query("UPDATE mybb_datacache SET cache = '' WHERE title LIKE '%stylesheet%' OR title LIKE '%theme%'");
}

function ope_export_css(mysqli $db, int $tid): void
{
    $stmt = $db->prepare('SELECT stylesheet FROM mybb_themestylesheets WHERE tid = ? AND name = ?');
    $name = 'ope.css';
    $stmt->bind_param('is', $tid, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || $row['stylesheet'] === '') {
        fwrite(STDERR, "No ope.css in database for tid=$tid\n");
        exit(1);
    }

    $css = $row['stylesheet'];
    if (substr($css, -1) !== "\n") {
        $css .= "\n";
    }
    if (file_put_contents(OPE_CSS_FILE, $css) === false) {
        fwrite(STDERR, "Could not write " . OPE_CSS_FILE . "\n");
        exit(1);
    }
    echo "Exported CSS -> docs/themes/ope.css (" . strlen($css) . " bytes)\n";
}

function ope_update_template_in_xml_file(string $path, string $title, string $content, string $version = '1839'): bool
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    if (!$dom->load($path)) {
        return false;
    }

    $xpath = new DOMXPath($dom);
    $allTemplates = $xpath->query("/theme/templates/template");
    $nodes = [];
    foreach ($allTemplates as $template) {
        if ((string)$template->getAttribute('name') === $title) {
            $nodes[] = $template;
            break;
        }
    }
    if (count($nodes) === 0) {
        return false;
    }

    /** @var DOMElement $node */
    $node = $nodes[0];
    while ($node->firstChild) {
        $node->removeChild($node->firstChild);
    }
    $node->appendChild($dom->createCDATASection($content));
    $node->setAttribute('version', $version);
    return $dom->save($path) !== false;
}

function ope_export_templates(mysqli $db, int $templateset): void
{
    $ownership = ope_template_ownership_map();
    $by_file = [];
    foreach ($ownership as $name => $file) {
        $by_file[$file][] = $name;
    }

    $updated = 0;
    $missing = 0;

    foreach ($by_file as $file => $names) {
        $path = OPE_THEME_ROOT . '/' . $file;
        sort($names);
        foreach ($names as $title) {
            $stmt = $db->prepare('SELECT template, version FROM mybb_templates WHERE title = ? AND sid = ?');
            $stmt->bind_param('si', $title, $templateset);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                echo "  SKIP (not in DB): $title\n";
                $missing++;
                continue;
            }

            if (!ope_update_template_in_xml_file($path, $title, $row['template'], $row['version'] ?? '1839')) {
                echo "  FAIL (not in XML): $title -> $file\n";
                $missing++;
                continue;
            }
            $updated++;
        }
        echo "  $file: " . count($names) . " templates checked\n";
    }

    echo "Exported $updated templates to docs/themes/ope-*.xml";
    if ($missing > 0) {
        echo " ($missing skipped/failed)";
    }
    echo "\n";
}

function ope_verify_sync(mysqli $db, int $tid, int $templateset): int
{
    $errors = 0;

    $repoCss = ope_read_css();
    $stmt = $db->prepare('SELECT stylesheet FROM mybb_themestylesheets WHERE tid = ? AND name = ?');
    $name = 'ope.css';
    $stmt->bind_param('is', $tid, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo "DRIFT CSS: missing in database\n";
        $errors++;
    } elseif (md5($repoCss) !== md5($row['stylesheet'])) {
        echo "DRIFT CSS: docs/themes/ope.css != database\n";
        $errors++;
    } else {
        echo "OK   CSS: in sync\n";
    }

    $repo = ope_load_repo_templates();
    foreach ($repo as $title => $meta) {
        $stmt = $db->prepare('SELECT template, version FROM mybb_templates WHERE title = ? AND sid = ?');
        $stmt->bind_param('si', $title, $templateset);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo "DRIFT TPL: $title missing in DB (source: {$meta['file']})\n";
            $errors++;
            continue;
        }
        if ($row['template'] !== $meta['content']) {
            echo "DRIFT TPL: $title differs (source: {$meta['file']})\n";
            $errors++;
            continue;
        }
        echo "OK   TPL: $title ({$meta['file']})\n";
    }

    return $errors;
}

/**
 * Realiza una comparación previa (dry-run/diff) mostrando las diferencias
 * exactas línea por línea entre la DB y los XML del repo sin alterar la base de datos.
 */
function ope_diff_sync(mysqli $db, int $tid, int $templateset): int
{
    $diffs = 0;

    echo "=== COMPARING REPO VS DATABASE (DRY-RUN / DIFF) ===\n\n";

    // ── 1. CSS ──
    $repoCss = ope_read_css();
    $stmt = $db->prepare('SELECT stylesheet FROM mybb_themestylesheets WHERE tid = ? AND name = ?');
    $name = 'ope.css';
    $stmt->bind_param('is', $tid, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo "🔴 DRIFT CSS: ope.css existe en repo pero NO en la base de datos.\n\n";
        $diffs++;
    } elseif (md5($repoCss) !== md5($row['stylesheet'])) {
        echo "🔴 DRIFT CSS: docs/themes/ope.css difiere de la base de datos\n";
        ope_render_line_diff($row['stylesheet'], $repoCss, 'DB', 'REPO (ope.css)');
        $diffs++;
    } else {
        echo "🟢 OK CSS: ope.css está 100% sincronizado.\n\n";
    }

    // ── 2. Templates ──
    $repo = ope_load_repo_templates();
    foreach ($repo as $title => $meta) {
        $stmt = $db->prepare('SELECT template, version FROM mybb_templates WHERE title = ? AND sid = ?');
        $stmt->bind_param('si', $title, $templateset);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo "🔴 DRIFT TPL: Plantilla '$title' existe en {$meta['file']} pero NO en la DB.\n\n";
            $diffs++;
            continue;
        }
        if ($row['template'] !== $meta['content']) {
            echo "🔴 DRIFT TPL: Plantilla '$title' en DB difiere del XML {$meta['file']}\n";
            ope_render_line_diff($row['template'], $meta['content'], 'DB', "REPO ({$meta['file']})");
            $diffs++;
        }
    }

    if ($diffs === 0) {
        echo "🟢 Sin diferencias: Todo el tema está 100% en sincronía entre Repo y DB.\n";
    } else {
        echo "⚠️ Total de diferencias detectadas: $diffs. Usa 'php scripts/sync-theme.php import' para aplicar o 'export' para guardar la DB al repo.\n";
    }

    return $diffs;
}

function ope_render_line_diff(string $strA, string $strB, string $labelA, string $labelB): void
{
    $linesA = explode("\n", str_replace("\r\n", "\n", $strA));
    $linesB = explode("\n", str_replace("\r\n", "\n", $strB));
    $max = max(count($linesA), count($linesB));

    echo "--- $labelA\n";
    echo "+++ $labelB\n";
    for ($i = 0; $i < $max; $i++) {
        $lineA = $linesA[$i] ?? null;
        $lineB = $linesB[$i] ?? null;

        if ($lineA !== $lineB) {
            if ($lineA !== null) {
                echo "- L" . ($i + 1) . ": " . trim($lineA) . "\n";
            }
            if ($lineB !== null) {
                echo "+ L" . ($i + 1) . ": " . trim($lineB) . "\n";
            }
        }
    }
    echo "\n";
}

function ope_child_theme_properties(): array
{
    if (is_file(OPE_CHILD_XML)) {
        $xml = simplexml_load_file(OPE_CHILD_XML);
        if ($xml && $xml->properties) {
            $props = [];
            foreach ($xml->properties->children() as $child) {
                $key = $child->getName();
                if ($child->count() > 0) {
                    $sub = [];
                    foreach ($child->children() as $subchild) {
                        $sub[$subchild->getName()] = (string)$subchild;
                    }
                    $props[$key] = $sub;
                } else {
                    $props[$key] = (string)$child;
                }
            }
            if (!empty($props)) {
                return $props;
            }
        }
    }

    return [
        'templateset' => '12',
        'imgdir' => 'images',
        'logo' => 'images/logo.png',
        'tablespace' => '5',
        'borderwidth' => '0',
        'editortheme' => 'mybb.css',
        'disporder' => [
            'ope.css' => '1',
            'global.css' => '2',
            'usercp.css' => '3',
            'modcp.css' => '4',
            'star_ratings.css' => '5',
            'showthread.css' => '6',
            'thread_status.css' => '7',
            'css3.css' => '8',
        ],
    ];
}

function ope_build_child_theme_xml(): void
{
    $css = ope_read_css();
    $repo = ope_load_repo_templates();
    $props = ope_child_theme_properties();

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $comment = $dom->createComment(
        ' GENERATED by scripts/sync-theme.php build-xml — do not edit manually. '
        . 'Edit docs/themes/ope.css and docs/themes/ope-*.xml, then run: php scripts/sync-theme.php import '
    );
    $dom->appendChild($comment);

    $themeEl = $dom->createElement('theme');
    $themeEl->setAttribute('name', 'One Piece: Eternal');
    $themeEl->setAttribute('version', '1839');

    $propsEl = $dom->createElement('properties');
    foreach ($props as $key => $value) {
        if (is_array($value)) {
            $sub = $dom->createElement($key);
            foreach ($value as $k => $v) {
                $sub->appendChild($dom->createElement($k, (string)$v));
            }
            $propsEl->appendChild($sub);
        } else {
            $propsEl->appendChild($dom->createElement($key, (string)$value));
        }
    }
    $themeEl->appendChild($propsEl);

    $sheetsEl = $dom->createElement('stylesheets');
    $sheet = $dom->createElement('stylesheet');
    $sheet->setAttribute('name', 'ope.css');
    $sheet->setAttribute('version', '1839');
    $sheet->appendChild($dom->createCDATASection($css));
    $sheetsEl->appendChild($sheet);
    $themeEl->appendChild($sheetsEl);

    $tempsEl = $dom->createElement('templates');
    ksort($repo);
    foreach ($repo as $title => $meta) {
        $t = $dom->createElement('template');
        $t->setAttribute('name', $title);
        $t->setAttribute('version', $meta['version']);
        $t->appendChild($dom->createCDATASection($meta['content']));
        $tempsEl->appendChild($t);
    }
    $themeEl->appendChild($tempsEl);

    $dom->appendChild($themeEl);
    if ($dom->save(OPE_CHILD_BUNDLE_XML) === false) {
        fwrite(STDERR, "Could not write " . OPE_CHILD_BUNDLE_XML . "\n");
        exit(1);
    }

    echo "Built " . OPE_CHILD_BUNDLE_XML . " (" . count($repo) . " templates, " . strlen($css) . " bytes CSS)\n";
    echo "Import that file in Admin CP if needed. Do not edit it — edit ope.css and ope-*.xml instead.\n";
}
