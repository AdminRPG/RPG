<?php
/**
 * I-Forge · Migración "Cartas de Técnica" (INI-03)
 * -------------------------------------------------
 * Dos conceptos separados:
 *   - `mybb_rol_cartas`   → BIBLIOTECA de cartas creadas (sin personaje).
 *   - `mybb_rol_tecnicas` → DECK de cada personaje (copia asignada de una
 *                           carta de la biblioteca, con su insignia propia).
 * Al asignar, se copia la carta de la biblioteca al deck del personaje
 * (columna `origen_id` guarda de qué carta de biblioteca proviene).
 *
 * Los tags de las 6 categorías se guardan estructurados en la columna JSON
 * `tags` para poder pintarlos y filtrarlos sin migraciones.
 *
 * Idempotente: CREATE TABLE IF NOT EXISTS + ADD COLUMN condicional.
 *
 * Ejecutar (desde la raíz del repo, sin truncar la salida):
 *   php scripts/migrate-rol-tecnicas.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración cartas de técnica ===\n";

// ─────────────────────────────────────────────────────────────
// BIBLIOTECA de cartas (sin personaje): mybb_rol_cartas
// ─────────────────────────────────────────────────────────────
$lib = $PREFIX . 'rol_cartas';
$sql_lib = "
CREATE TABLE IF NOT EXISTS {$lib} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    tier TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1..5',
    tags JSON NULL COMMENT 'estilo[], tipo, alcance, elemento, estado[], ejecucion[]',
    coste_pa TINYINT UNSIGNED NOT NULL DEFAULT 1,
    coste_en SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    reposo TINYINT UNSIGNED NOT NULL DEFAULT 0,
    requisito_stats VARCHAR(255) NOT NULL DEFAULT '',
    dados VARCHAR(60) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    creador_uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'staff que la creó',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tier (tier),
    KEY idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($db->query($sql_lib) === false) {
    fwrite(STDERR, "  [ERROR] creando {$lib}: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] tabla {$lib} lista (biblioteca)\n";

// ─────────────────────────────────────────────────────────────
// DECK del personaje: mybb_rol_tecnicas
// ─────────────────────────────────────────────────────────────
$table = $PREFIX . 'rol_tecnicas';
$sql = "
CREATE TABLE IF NOT EXISTS {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL COMMENT 'personaje dueño de la carta',
    origen_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'carta de biblioteca de la que proviene (0 = manual)',
    nombre VARCHAR(160) NOT NULL,
    tier TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1..5',
    es_insignia TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'técnica insignia (evolución ilimitada)',
    tags JSON NULL COMMENT 'estilo[], tipo, alcance, elemento, estado[], ejecucion[]',
    coste_pa TINYINT UNSIGNED NOT NULL DEFAULT 1,
    coste_en SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    reposo TINYINT UNSIGNED NOT NULL DEFAULT 0,
    requisito_stats VARCHAR(255) NOT NULL DEFAULT '',
    dados VARCHAR(60) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    disporder INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pid (pid),
    KEY idx_pid_order (pid, disporder),
    KEY idx_tier (tier),
    KEY idx_origen (origen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] creando {$table}: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] tabla {$table} lista (deck)\n";

// origen_id: añadir la columna si la tabla existía sin ella (idempotente).
$colres = $db->query("SHOW COLUMNS FROM {$table} LIKE 'origen_id'");
if ($colres && $colres->num_rows === 0) {
    if ($db->query("ALTER TABLE {$table} ADD COLUMN origen_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER pid, ADD KEY idx_origen (origen_id)") === false) {
        fwrite(STDERR, "  [ERROR] añadiendo origen_id: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] columna origen_id añadida a {$table}\n";
}

echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_%'");
while ($t = $check->fetch_array()) {
    if (strpos($t[0], 'rol_cartas') !== false || strpos($t[0], 'rol_tecnicas') !== false) {
        echo "  tabla: {$t[0]}\n";
    }
}

echo "\n=== DONE ===\n";
$db->close();
