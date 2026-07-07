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

$db = iforge_db_connect();
$theme = iforge_resolve_theme($db);
echo "Exporting from tid={$theme['tid']} templateset={$theme['templateset']}\n";
iforge_export_css($db, $theme['tid']);
iforge_export_templates($db, $theme['templateset']);
$db->close();

iforge_build_child_theme_xml();
echo "\nDone. Commit docs/themes/iforge.css and docs/themes/iforge-foundry-*.xml\n";
