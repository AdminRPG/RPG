<?php
/**
 * Migración: Sistema de Navegación Automática (44 Islas)
 * -------------------------------------------------------
 * Crea:
 *   - Tabla `rol_barcos`       (barcos del personaje)
 *   - Tabla `rol_nav_items`    (items de navegación)
 *   - Columna `isla_actual`    en `rol_personajes`
 *   - Columnas nuevas          en `rol_viajes`
 *
 * Idempotente. Ejecutar: php scripts/migrate-navegacion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
    } else {
        echo "  [OK] $label\n";
    }
}

function col_exists(mysqli $db, string $prefix, string $table, string $col): bool
{
    $r = $db->query("SHOW COLUMNS FROM {$prefix}{$table} LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}

// ═══════════════════════════════════════════════════════════════
echo "=== Tabla rol_barcos ===\n";
run($db, 'CREATE rol_barcos', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_barcos (
        barco_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid         INT UNSIGNED NOT NULL,
        uid         INT UNSIGNED NOT NULL DEFAULT 0,
        nombre      VARCHAR(120) NOT NULL,
        tipo        ENUM('bote','balandra','goleta','cuter','bergantin','fragata','galeon','navio','especial')
                    NOT NULL DEFAULT 'bote',
        estadio     ENUM('basico','adaptado','reforzado','avanzado','legendario')
                    NOT NULL DEFAULT 'basico',
        es_banda    TINYINT(1) NOT NULL DEFAULT 0,
        mejoras_json TEXT NULL,
        estado_casco TINYINT UNSIGNED NOT NULL DEFAULT 100,
        notas       TEXT NULL,
        dateline    INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (barco_id),
        KEY idx_pid  (pid),
        KEY idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ═══════════════════════════════════════════════════════════════
echo "\n=== Tabla rol_nav_items ===\n";
run($db, 'CREATE rol_nav_items', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_nav_items (
        item_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid         INT UNSIGNED NOT NULL,
        slug        VARCHAR(40)  NOT NULL,
        datos_json  TEXT NULL,
        cantidad    TINYINT UNSIGNED NOT NULL DEFAULT 1,
        dateline    INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (item_id),
        KEY idx_pid  (pid),
        KEY idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ═══════════════════════════════════════════════════════════════
echo "\n=== Columna isla_actual en rol_personajes ===\n";
if (!col_exists($db, $PREFIX, 'rol_personajes', 'isla_actual')) {
    run($db, 'ADD isla_actual', "
        ALTER TABLE {$PREFIX}rol_personajes
        ADD COLUMN isla_actual VARCHAR(60) NOT NULL DEFAULT 'isla_dawn'
    ");
} else {
    echo "  [=] rol_personajes.isla_actual ya existe\n";
}

// ═══════════════════════════════════════════════════════════════
echo "\n=== Columnas nuevas en rol_viajes ===\n";
$viaje_cols = array(
    'barco_id'       => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN barco_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER barco_tipo",
    'items_json'     => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN items_json TEXT NULL AFTER tripulantes_json",
    'ruta_json'      => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN ruta_json TEXT NULL AFTER items_json",
    'peligro_total'  => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN peligro_total SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER tramos",
    'nivel_peligro'  => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN nivel_peligro VARCHAR(20) NOT NULL DEFAULT 'bajo' AFTER peligro_total",
    'dias_onrol'     => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN dias_onrol SMALLINT UNSIGNED NOT NULL DEFAULT 2 AFTER nivel_peligro",
    'es_temeraria'   => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN es_temeraria TINYINT(1) NOT NULL DEFAULT 0 AFTER dias_onrol",
    'origen_slug'    => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN origen_slug VARCHAR(60) NOT NULL DEFAULT '' AFTER fid_destino",
    'destino_slug'   => "ALTER TABLE {$PREFIX}rol_viajes ADD COLUMN destino_slug VARCHAR(60) NOT NULL DEFAULT '' AFTER origen_slug",
);
foreach ($viaje_cols as $col => $sql) {
    if (!col_exists($db, $PREFIX, 'rol_viajes', $col)) {
        run($db, "ADD rol_viajes.$col", $sql);
    } else {
        echo "  [=] rol_viajes.$col ya existe\n";
    }
}

// ═══════════════════════════════════════════════════════════════
echo "\n=== DONE ===\n";
echo "Tablas creadas: rol_barcos, rol_nav_items\n";
echo "Columnas añadidas: isla_actual, barco_id, items_json, ruta_json, peligro_total, nivel_peligro, dias_onrol, es_temeraria, origen_slug, destino_slug\n";
