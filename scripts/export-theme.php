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

$db = gbe_db_connect();
$theme = gbe_resolve_theme($db);
echo "Exporting from tid={$theme['tid']} templateset={$theme['templateset']}\n";
gbe_export_css($db, $theme['tid']);
gbe_export_templates($db, $theme['templateset']);
$db->close();

gbe_build_child_theme_xml();
echo "\nDone. Commit docs/themes/gbe.css and docs/themes/gbe-*.xml\n";
