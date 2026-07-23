<?php
/**
 * One Piece: Eternal · Migración de Etiquetas de Hilo, Snapshots y Card Flip
 * -------------------------------------------------------------------------
 * Añade columnas a mybb_threads (temporal_tipo, temporal_fecha, tema_tipo)
 * y crea/actualiza la tabla mybb_rol_thread_snapshots para la congelación
 * de la ficha del personaje al iniciar o participar en un hilo.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
require_once __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración de Etiquetas de Hilo y Snapshots de Ficha ===\n";

// Helper para verificar existencia de tabla
function db_table_exists_raw($table_name) {
    global $db;
    $res = $db->query("SHOW TABLES LIKE '{$table_name}'");
    return ($res && $res->num_rows > 0);
}

// 1. Columnas en mybb_threads
$thread_cols = array(
    'temporal_tipo'  => "VARCHAR(20) NOT NULL DEFAULT 'presente' AFTER subject",
    'temporal_fecha' => "VARCHAR(100) NOT NULL DEFAULT '' AFTER temporal_tipo",
    'tema_tipo'      => "VARCHAR(30) NOT NULL DEFAULT 'social' AFTER temporal_fecha",
);

foreach ($thread_cols as $col => $def) {
    $cq = $db->query("SHOW COLUMNS FROM {$PREFIX}threads LIKE '{$col}'");
    if ($cq && $cq->num_rows > 0) {
        echo "  [=] Columna {$col} ya existe en mybb_threads\n";
    } else {
        if ($db->query("ALTER TABLE {$PREFIX}threads ADD COLUMN {$col} {$def}")) {
            echo "  [+] Columna {$col} añadida a mybb_threads\n";
        } else {
            echo "  [ERROR] Fallo al añadir {$col} a mybb_threads: " . $db->error . "\n";
        }
    }
}

// 2. Columnas en mybb_rol_post_snapshot para la ficha completa por post (Stats, Mochila, Fruta, NPCs, Modificadores)
$snapshot_cols = array(
    'stats_json'     => "TEXT NULL AFTER en_actual",
    'mochila_json'   => "TEXT NULL AFTER stats_json",
    'fruta_json'     => "TEXT NULL AFTER mochila_json",
    'npcs_json'      => "TEXT NULL AFTER fruta_json",
    'estados_json'   => "TEXT NULL AFTER npcs_json",
    'mods_json'      => "TEXT NULL AFTER estados_json",
);

if (db_table_exists_raw($PREFIX . 'rol_post_snapshot')) {
    foreach ($snapshot_cols as $scol => $sdef) {
        $cq = $db->query("SHOW COLUMNS FROM {$PREFIX}rol_post_snapshot LIKE '{$scol}'");
        if ($cq && $cq->num_rows > 0) {
            echo "  [=] Columna {$scol} ya existe en rol_post_snapshot\n";
        } else {
            if ($db->query("ALTER TABLE {$PREFIX}rol_post_snapshot ADD COLUMN {$scol} {$sdef}")) {
                echo "  [+] Columna {$scol} añadida a rol_post_snapshot\n";
            } else {
                echo "  [ERROR] Fallo al añadir {$scol} a rol_post_snapshot: " . $db->error . "\n";
            }
        }
    }
}

// 3. Tabla de snapshots de hilo (congelación de personaje al entrar al hilo)
$create_thread_snapshots = "CREATE TABLE IF NOT EXISTS {$PREFIX}rol_thread_snapshots (
    snapshot_id INT(11) NOT NULL AUTO_INCREMENT,
    tid INT(11) NOT NULL,
    pid INT(11) NOT NULL,
    nivel INT(11) NOT NULL DEFAULT 1,
    rango VARCHAR(50) NOT NULL DEFAULT 'Rang E',
    faccion VARCHAR(50) NOT NULL DEFAULT 'Pirata',
    stats_base_json TEXT NULL,
    mochila_json TEXT NULL,
    fruta_json TEXT NULL,
    npcs_json TEXT NULL,
    dateline INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (snapshot_id),
    UNIQUE KEY tid_pid (tid, pid),
    KEY pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($create_thread_snapshots)) {
    echo "  [+] Tabla rol_thread_snapshots creada/verificada.\n";
} else {
    echo "  [ERROR] Error creando rol_thread_snapshots: " . $db->error . "\n";
}

echo "=== MIGRACIÓN COMPLETADA CON ÉXITO ===\n";
