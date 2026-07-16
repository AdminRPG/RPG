<?php
/**
 * Single entry point: keep repo theme sources and MyBB DB in sync.
 *
 *   php scripts/sync-theme.php import      Repo -> DB (default)
 *   php scripts/sync-theme.php export      DB -> repo files
 *   php scripts/sync-theme.php verify      Detect drift
 *   php scripts/sync-theme.php build-xml   Build gbe-child-theme.xml for Admin CP
 *   php scripts/sync-theme.php bootstrap   One-time: extract CSS + shared templates from legacy child-theme.xml
 */
require __DIR__ . '/_theme-sync-lib.php';

$command = $argv[1] ?? 'import';

switch ($command) {
    case 'bootstrap':
        gbe_bootstrap_from_legacy();
        break;

    case 'export':
        $db = gbe_db_connect();
        $theme = gbe_resolve_theme($db);
        echo "Exporting from tid={$theme['tid']} templateset={$theme['templateset']}\n";
        gbe_export_css($db, $theme['tid']);
        gbe_export_templates($db, $theme['templateset']);
        $db->close();
        break;

    case 'verify':
        $db = gbe_db_connect();
        $theme = gbe_resolve_theme($db);
        echo "Verifying tid={$theme['tid']} templateset={$theme['templateset']}\n";
        $errors = gbe_verify_sync($db, $theme['tid'], $theme['templateset']);
        $db->close();
        exit($errors > 0 ? 1 : 0);

    case 'build-xml':
        gbe_build_child_theme_xml();
        break;

    case 'import':
    default:
        if (!is_file(GBE_CSS_FILE)) {
            echo "gbe.css not found — running bootstrap first...\n";
            gbe_bootstrap_from_legacy();
        }
        if (!is_file(GBE_THEME_ROOT . '/gbe-shared.xml')) {
            echo "gbe-shared.xml not found — running bootstrap first...\n";
            gbe_bootstrap_from_legacy();
        }

        $db = gbe_db_connect();
        $theme = gbe_resolve_theme($db);
        echo "Importing to tid={$theme['tid']} templateset={$theme['templateset']}\n\n";

        echo "--- CSS ---\n";
        gbe_import_css($db, $theme['tid']);

        echo "\n--- Templates ---\n";
        $count = gbe_import_templates($db, $theme['templateset']);

        gbe_clear_theme_caches($db);
        $db->close();

        echo "\n=== DONE ===\n";
        echo "Templates synced: $count\n";
        echo "Sources: docs/themes/gbe.css + docs/themes/gbe-*.xml\n";
        echo "Hard-refresh the forum (Ctrl+Shift+R).\n";
        break;
}

/**
 * One-time migration: pull CSS and auxiliary templates out of the monolithic
 * gbe-child-theme.xml so they are not overwritten on the next import.
 */
function gbe_bootstrap_from_legacy(): void
{
    if (!is_file(GBE_CHILD_XML)) {
        fwrite(STDERR, "Legacy file not found: " . GBE_CHILD_XML . "\n");
        exit(1);
    }

    $xml = simplexml_load_file(GBE_CHILD_XML);
    if (!$xml) {
        fwrite(STDERR, "Invalid XML: " . GBE_CHILD_XML . "\n");
        exit(1);
    }

  // ── CSS ──
    if (!is_file(GBE_CSS_FILE) && $xml->stylesheets && $xml->stylesheets->stylesheet) {
        foreach ($xml->stylesheets->stylesheet as $sheet) {
            if ((string)$sheet['name'] === 'gbe.css') {
                $css = (string)$sheet;
                if (substr($css, -1) !== "\n") {
                    $css .= "\n";
                }
                file_put_contents(GBE_CSS_FILE, $css);
                echo "Bootstrap: wrote docs/themes/gbe.css (" . strlen($css) . " bytes)\n";
                break;
            }
        }
    }

  // ── Shared templates (everything in child-theme NOT in foundry-* files) ──
    $sharedPath = GBE_THEME_ROOT . '/gbe-shared.xml';
    if (!is_file($sharedPath) && $xml->templates && $xml->templates->template) {
        $foundryNames = array_keys(gbe_load_repo_templates_from([
            GBE_THEME_ROOT . '/gbe-forms.xml',
            GBE_THEME_ROOT . '/gbe-showthread.xml',
            GBE_THEME_ROOT . '/gbe-forumdisplay.xml',
            GBE_THEME_ROOT . '/gbe-index.xml',
        ]));

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $themeEl = $dom->createElement('theme');
        $themeEl->setAttribute('name', 'I-Forge RPG — shared templates');
        $themeEl->setAttribute('version', '1839');
        $tempsEl = $dom->createElement('templates');

        $count = 0;
        foreach ($xml->templates->template as $tpl) {
            $name = (string)$tpl['name'];
            if (in_array($name, $foundryNames, true)) {
                continue;
            }
            $t = $dom->createElement('template');
            $t->setAttribute('name', $name);
            $t->setAttribute('version', (string)($tpl['version'] ?? '1839'));
            $t->appendChild($dom->createCDATASection((string)$tpl));
            $tempsEl->appendChild($t);
            $count++;
        }
        $themeEl->appendChild($tempsEl);
        $dom->appendChild($themeEl);
        $dom->save($sharedPath);
        echo "Bootstrap: wrote docs/themes/gbe-shared.xml ($count templates)\n";
    }

    gbe_write_child_theme_stub();
    echo "Bootstrap: replaced gbe-child-theme.xml with properties stub\n";
}

/** @param list<string> $files */
function gbe_load_repo_templates_from(array $files): array
{
    $templates = [];
    foreach ($files as $path) {
        if (!is_file($path)) {
            continue;
        }
        $xml = simplexml_load_file($path);
        if (!$xml || !$xml->templates) {
            continue;
        }
        foreach ($xml->templates->template as $tpl) {
            $templates[(string)$tpl['name']] = true;
        }
    }
    return $templates;
}

function gbe_write_child_theme_stub(): void
{
    $props = gbe_child_theme_properties();
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $comment = $dom->createComment(
        ' Properties stub — NOT the source of truth. '
        . 'Edit docs/themes/gbe.css and docs/themes/gbe-*.xml. '
        . 'Deploy: php scripts/sync-theme.php import '
        . 'Admin CP bundle: php scripts/sync-theme.php build-xml -> gbe-child-theme.bundle.xml '
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
    $dom->appendChild($themeEl);
    $dom->save(GBE_CHILD_XML);
}
