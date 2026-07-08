<?php
/**
 * Shared helpers for I-Forge theme sync (repo <-> MyBB DB).
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
    $db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
    if ($db->connect_error) {
        fwrite(STDERR, "DB connection error: {$db->connect_error}\n");
        exit(1);
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function ope_resolve_theme(mysqli $db): array
{
    $result = $db->query("SELECT tid, name, properties FROM mybb_themes WHERE name = 'I-Forge RPG' OR name = 'RPG' ORDER BY tid DESC LIMIT 1");
    $theme = $result ? $result->fetch_assoc() : null;
    if (!$theme) {
        fwrite(STDERR, "Theme 'I-Forge RPG' not found. Create it first via Admin CP or scripts/import-theme.php install.\n");
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

    $stmt = $db->prepare('SELECT sid FROM mybb_themestylesheets WHERE tid = ? AND name = ?');
    $stmt->bind_param('is', $tid, $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $up = $db->prepare('UPDATE mybb_themestylesheets SET stylesheet = ?, lastmodified = ? WHERE tid = ? AND name = ?');
        $up->bind_param('siis', $css, $now, $tid, $name);
        $up->execute();
        $up->close();
        if (!$quiet) {
            echo "  CSS: $name updated (" . strlen($css) . " bytes)\n";
        }
    } else {
        $stmt->close();
        $in = $db->prepare('INSERT INTO mybb_themestylesheets (name, tid, stylesheet, attachedto, lastmodified) VALUES (?, ?, ?, \'\', ?)');
        $in->bind_param('sisi', $name, $tid, $css, $now);
        $in->execute();
        $in->close();
        if (!$quiet) {
            echo "  CSS: $name inserted (" . strlen($css) . " bytes)\n";
        }
    }

    $theme_dir = dirname(__DIR__) . "/cache/themes/theme{$tid}";
    if (!is_dir($theme_dir)) {
        @mkdir($theme_dir, 0777, true);
    }
    $cache_path = "{$theme_dir}/ope.css";
    if (@file_put_contents($cache_path, $css) === false) {
        fwrite(STDERR, "  WARNING: could not write $cache_path\n");
    } elseif (!$quiet) {
        echo "  CSS: cache written -> cache/themes/theme{$tid}/ope.css\n";
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
    $nodes = $xpath->query("/theme/templates/template[@name='" . str_replace("'", '', $title) . "']");
    if ($nodes->length === 0) {
        return false;
    }

    /** @var DOMElement $node */
    $node = $nodes->item(0);
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
    $themeEl->setAttribute('name', 'I-Forge RPG');
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
