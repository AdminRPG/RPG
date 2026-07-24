<?php
/**
 * Single entry point: keep repo theme sources and MyBB DB in sync.
 *
 *   php scripts/sync-theme.php import      Repo -> DB (default)
 *   php scripts/sync-theme.php export      DB -> repo files
 *   php scripts/sync-theme.php verify      Detect drift
 *   php scripts/sync-theme.php build-xml   Build ope-child-theme.xml for Admin CP
 *   php scripts/sync-theme.php bootstrap   One-time: extract CSS + shared templates from legacy child-theme.xml
 */
require __DIR__ . '/_theme-sync-lib.php';

$command = $argv[1] ?? 'import';

switch ($command) {
    case 'diff':
    case '--diff':
    case '--dry-run':
        $db = ope_db_connect();
        $theme = ope_resolve_theme($db);
        echo "Dry-run / Diff for tid={$theme['tid']} templateset={$theme['templateset']}\n\n";
        $diffs = ope_diff_sync($db, $theme['tid'], $theme['templateset']);
        $db->close();
        exit($diffs > 0 ? 1 : 0);

    case 'bootstrap':
        ope_bootstrap_from_legacy();
        break;

    case 'export':
        $db = ope_db_connect();
        $theme = ope_resolve_theme($db);
        echo "Exporting from tid={$theme['tid']} templateset={$theme['templateset']}\n";
        ope_export_css($db, $theme['tid']);
        ope_export_templates($db, $theme['templateset']);
        $db->close();
        break;

    case 'verify':
        $db = ope_db_connect();
        $theme = ope_resolve_theme($db);
        echo "Verifying tid={$theme['tid']} templateset={$theme['templateset']}\n";
        $errors = ope_verify_sync($db, $theme['tid'], $theme['templateset']);
        $db->close();
        exit($errors > 0 ? 1 : 0);

    case 'build-xml':
        ope_build_child_theme_xml();
        break;

    case 'import':
    default:
        if (!is_file(OPE_CSS_FILE)) {
            echo "ope.css not found — running bootstrap first...\n";
            ope_bootstrap_from_legacy();
        }
        if (!is_file(OPE_THEME_ROOT . '/ope-shared.xml')) {
            echo "ope-shared.xml not found — running bootstrap first...\n";
            ope_bootstrap_from_legacy();
        }

        $db = ope_db_connect();
        $theme = ope_resolve_theme($db);
        echo "Importing to tid={$theme['tid']} templateset={$theme['templateset']}\n\n";

        echo "--- CSS ---\n";
        ope_import_css($db, $theme['tid']);

        echo "\n--- Templates ---\n";
        $count = ope_import_templates($db, $theme['templateset']);

        ope_clear_theme_caches($db);
        $db->close();

        echo "\n=== DONE ===\n";
        echo "Templates synced: $count\n";
        echo "Sources: docs/themes/ope.css + docs/themes/ope-*.xml\n";
        echo "Hard-refresh the forum (Ctrl+Shift+R).\n";
        break;
}

/**
 * One-time migration: pull CSS and auxiliary templates out of the monolithic
 * ope-child-theme.xml so they are not overwritten on the next import.
 */
function ope_bootstrap_from_legacy(): void
{
    if (!is_file(OPE_CHILD_XML)) {
        fwrite(STDERR, "Legacy file not found: " . OPE_CHILD_XML . "\n");
        exit(1);
    }

    $xml = simplexml_load_file(OPE_CHILD_XML);
    if (!$xml) {
        fwrite(STDERR, "Invalid XML: " . OPE_CHILD_XML . "\n");
        exit(1);
    }

  // ── CSS ──
    if (!is_file(OPE_CSS_FILE) && $xml->stylesheets && $xml->stylesheets->stylesheet) {
        foreach ($xml->stylesheets->stylesheet as $sheet) {
            if ((string)$sheet['name'] === 'ope.css') {
                $css = (string)$sheet;
                if (substr($css, -1) !== "\n") {
                    $css .= "\n";
                }
                file_put_contents(OPE_CSS_FILE, $css);
                echo "Bootstrap: wrote docs/themes/ope.css (" . strlen($css) . " bytes)\n";
                break;
            }
        }
    }

  // ── Shared templates (everything in child-theme NOT in foundry-* files) ──
    $sharedPath = OPE_THEME_ROOT . '/ope-shared.xml';
    if (!is_file($sharedPath) && $xml->templates && $xml->templates->template) {
        $foundryNames = array_keys(ope_load_repo_templates_from([
            OPE_THEME_ROOT . '/ope-forms.xml',
            OPE_THEME_ROOT . '/ope-showthread.xml',
            OPE_THEME_ROOT . '/ope-forumdisplay.xml',
            OPE_THEME_ROOT . '/ope-index.xml',
        ]));

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $themeEl = $dom->createElement('theme');
        $themeEl->setAttribute('name', 'One Piece: Eternal — shared templates');
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
        echo "Bootstrap: wrote docs/themes/ope-shared.xml ($count templates)\n";
    }

    ope_write_child_theme_stub();
    echo "Bootstrap: replaced ope-child-theme.xml with properties stub\n";
}

/** @param list<string> $files */
function ope_load_repo_templates_from(array $files): array
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

function ope_write_child_theme_stub(): void
{
    $props = ope_child_theme_properties();
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;

    $comment = $dom->createComment(
        ' Properties stub — NOT the source of truth. '
        . 'Edit docs/themes/ope.css and docs/themes/ope-*.xml. '
        . 'Deploy: php scripts/sync-theme.php import '
        . 'Admin CP bundle: php scripts/sync-theme.php build-xml -> ope-child-theme.bundle.xml '
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
    $dom->appendChild($themeEl);
    $dom->save(OPE_CHILD_XML);
}
