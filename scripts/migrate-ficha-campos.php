<?php
/**
 * Granblue Fantasy: Eternal · Migración "campos de ficha" (rediseño de ficha.php)
 * ---------------------------------------------------------------------
 * Añade a rol_personajes las columnas que necesita la ficha rediseñada:
 *   - rango_faccion : rango del personaje DENTRO de su facción (grumete, capitán…)
 *   - from_fisico   : referencia/faceclaim de dónde viene el físico del personaje
 *   - desc_fisica   : descripción física (crónica)
 *   - personalidad  : personalidad (crónica)
 *
 * Idempotente: comprueba SHOW COLUMNS antes de tocar nada.
 *
 * Ejecutar:
 *   php scripts/migrate-ficha-campos.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "=== Migración campos de ficha ===\n";

add_col($db, "{$PREFIX}rol_personajes", 'rango_faccion', "VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'rango dentro de la facción (grumete, capitán…)'");
add_col($db, "{$PREFIX}rol_personajes", 'from_fisico',   "VARCHAR(160) NOT NULL DEFAULT '' COMMENT 'referencia/faceclaim del físico'");
add_col($db, "{$PREFIX}rol_personajes", 'desc_fisica',   "TEXT NULL COMMENT 'descripción física (crónica)'");
add_col($db, "{$PREFIX}rol_personajes", 'personalidad',  "TEXT NULL COMMENT 'personalidad (crónica)'");

echo "\n=== DONE ===\n";
$db->close();
