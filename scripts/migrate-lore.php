<?php
/**
 * I-Forge · Migración de Biblioteca de Lore (mybb_rol_lore)
 * ---------------------------------------------------------------
 * Crea la tabla del lore del mundo de One Piece Eternal.
 * No siembra datos; el seed se ejecuta con scripts/seed-lore.php.
 *
 * Ejecutar:  php scripts/migrate-lore.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "=== Migración Biblioteca de Lore (mybb_rol_lore) ===\n";

// ─────────────────────────────────────────────────────────────
// rol_lore — Biblioteca de Lore
// ─────────────────────────────────────────────────────────────
$sql = "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_lore (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL DEFAULT '',
    categoria VARCHAR(100) NOT NULL DEFAULT 'historia' COMMENT 'historia|eras|personajes|facciones|ubicaciones|sistemas|cronologia',
    subcategoria VARCHAR(100) DEFAULT NULL COMMENT 'facción específica, región, etc.',
    resumen TEXT NULL COMMENT 'resumen para tarjetas',
    contenido LONGTEXT NOT NULL COMMENT 'contenido completo en HTML',
    imagen VARCHAR(500) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_categoria (categoria),
    KEY idx_activo (activo),
    KEY idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] rol_lore: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] rol_lore\n";

echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_%'");
while ($t = $check->fetch_array()) {
    echo "  tabla: {$t[0]}\n";
}

echo "\n=== DONE ===\n";
$db->close();
