<?php
/**
 * I-Forge · Migración v5 — Tablón de Misiones
 * Añade campos de detalle a rol_mv_misiones:
 *  - rango        (S/A/B/C/D)
 *  - peligrosidad (1-5)
 *  - recompensa
 *  - modalidad    (solo/grupo/cualquiera)
 */
define('IN_MYBB', 1);
require_once __DIR__ . '/../inc/init.php';

$PREFIX = TABLE_PREFIX;

$cols = array(
    'rango'        => "ADD COLUMN rango VARCHAR(8) NOT NULL DEFAULT 'D' COMMENT 'Rango S/A/B/C/D' AFTER resumen",
    'peligrosidad' => "ADD COLUMN peligrosidad TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1-5' AFTER rango",
    'recompensa'   => "ADD COLUMN recompensa VARCHAR(500) NOT NULL DEFAULT '' AFTER facciones",
    'modalidad'    => "ADD COLUMN modalidad ENUM('solo','grupo','cualquiera') NOT NULL DEFAULT 'cualquiera' AFTER recompensa",
);

echo "Migrando rol_mv_misiones (v5 — tablón de misiones)...\n";
foreach ($cols as $name => $def) {
    if (!$db->field_exists($name, 'rol_mv_misiones')) {
        $db->write_query("ALTER TABLE {$PREFIX}rol_mv_misiones {$def}");
        echo "  [+] {$name}\n";
    } else {
        echo "  [=] {$name} ya existe\n";
    }
}

echo "\nListo.\n";