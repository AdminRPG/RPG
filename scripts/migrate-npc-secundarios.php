<?php
/**
 * One Piece: Eternal · Migración "NPCs Secundarios" (STF-14)
 * -------------------------------------------------
 * Tabla para almacenar fichas simplificadas de NPCs secundarios (no-jugadores
 * de apoyo, sin ficha completa de personaje). Cada entrada tiene nombre,
 * descripción, imagen y una lista de técnicas representativas en JSON.
 *
 * Idempotente: CREATE TABLE IF NOT EXISTS.
 *
 * Ejecutar (desde la raíz del repo):
 *   php scripts/migrate-npc-secundarios.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración NPCs Secundarios ===\n";

$table = $PREFIX . 'rol_npcs_secundarios';
$sql = "
CREATE TABLE IF NOT EXISTS {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    imagen VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'URL de la imagen del NPC',
    descripcion TEXT NULL COMMENT 'Descripción narrativa breve',
    tecnicas JSON NULL COMMENT 'Array de strings con nombres de técnicas',
    creador_uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'staff que lo creó',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] creando {$table}: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] tabla {$table} lista\n";

echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_npcs_secundarios%'");
while ($t = $check->fetch_array()) {
    echo "  tabla: {$t[0]}\n";
}

echo "\n=== DONE ===\n";
$db->close();
