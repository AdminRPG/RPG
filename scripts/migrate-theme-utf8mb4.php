<?php
/**
 * Convierte mybb_themestylesheets a utf8mb4 para poder importar ope.css
 * con prepared statements (conexión utf8mb4_0900_ai_ci).
 *
 *   php scripts/migrate-theme-utf8mb4.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/_db-config.php';

echo "=== migrate-theme-utf8mb4 ===\n";

$tables = array('mybb_themestylesheets', 'mybb_templates', 'mybb_themes');
foreach ($tables as $table) {
    $r = $db->query("SHOW TABLE STATUS LIKE '{$table}'");
    if (!$r || !$r->num_rows) {
        echo "  [skip] {$table} no existe\n";
        continue;
    }
    $row = $r->fetch_assoc();
    $cs = (string) ($row['Collation'] ?? '');
    echo "  {$table} collation={$cs}\n";
    $sql = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($db->query($sql) === false) {
        echo "  [FAIL] {$table}: {$db->error}\n";
    } else {
        echo "  [OK] {$table} -> utf8mb4_unicode_ci\n";
    }
}

echo "=== listo ===\n";
$db->close();
