<?php
/**
 * One Piece: Eternal · Migración para Galería de Fotos de Barcos (fotos_json)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
require_once __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración de Galería de Fotos de Barcos ===\n";

$cq = $db->query("SHOW COLUMNS FROM {$PREFIX}rol_barcos LIKE 'fotos_json'");
if ($cq && $cq->num_rows > 0) {
    echo "  [=] Columna fotos_json ya existe en rol_barcos\n";
} else {
    if ($db->query("ALTER TABLE {$PREFIX}rol_barcos ADD COLUMN fotos_json TEXT NULL AFTER foto_url")) {
        echo "  [+] Columna fotos_json añadida a rol_barcos\n";
    } else {
        echo "  [ERROR] Fallo al añadir fotos_json: " . $db->error . "\n";
    }
}

echo "=== COMPLETADO ===\n";
