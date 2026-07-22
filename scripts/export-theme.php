<?php
/**
 * Export live MyBB theme back to canonical repo files, then rebuild Admin CP XML.
 *
 * Run: php scripts/export-theme.php
 * Equivalent to:
 *   php scripts/sync-theme.php export
 *   php scripts/sync-theme.php build-xml
 */
require __DIR__ . '/_theme-sync-lib.php';

$db = ope_db_connect();
$theme = ope_resolve_theme($db);
echo "Exporting from tid={$theme['tid']} templateset={$theme['templateset']}\n";
ope_export_css($db, $theme['tid']);
ope_export_templates($db, $theme['templateset']);
$db->close();

ope_build_child_theme_xml();
echo "\nDone. Commit docs/themes/ope.css and docs/themes/ope-*.xml\n";
