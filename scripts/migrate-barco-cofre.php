<?php
/**
 * One Piece: Eternal · Migración del Cofre de la Nave (berries_cofre, items_cofre_json, rol_barco_cofre_logs)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
require_once __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración de Cofre de la Nave ===\n";

// 1. Columnas en rol_barcos
$cols = array(
    'berries_cofre'    => "INT(11) NOT NULL DEFAULT 0 AFTER fotos_json",
    'items_cofre_json' => "TEXT NULL AFTER berries_cofre",
);

foreach ($cols as $col => $definition) {
    $cq = $db->query("SHOW COLUMNS FROM {$PREFIX}rol_barcos LIKE '{$col}'");
    if ($cq && $cq->num_rows > 0) {
        echo "  [=] Columna {$col} ya existe en rol_barcos\n";
    } else {
        if ($db->query("ALTER TABLE {$PREFIX}rol_barcos ADD COLUMN {$col} {$definition}")) {
            echo "  [+] Columna {$col} añadida a rol_barcos\n";
        } else {
            echo "  [ERROR] Fallo al añadir {$col}: " . $db->error . "\n";
        }
    }
}

// 2. Tabla mybb_rol_barco_cofre_logs
$create_table = "CREATE TABLE IF NOT EXISTS {$PREFIX}rol_barco_cofre_logs (
    log_id INT(11) NOT NULL AUTO_INCREMENT,
    barco_id INT(11) NOT NULL,
    pid INT(11) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    monto_berries INT(11) NOT NULL DEFAULT 0,
    item_nombre VARCHAR(255) NOT NULL DEFAULT '',
    target_pid INT(11) NOT NULL DEFAULT 0,
    dateline INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (log_id),
    KEY barco_id (barco_id),
    KEY pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($create_table)) {
    echo "  [+] Tabla rol_barco_cofre_logs creada/verificada.\n";
} else {
    echo "  [ERROR] Error creando rol_barco_cofre_logs: " . $db->error . "\n";
}

echo "=== MIGRACIÓN DEL COFRE COMPLETADA CON ÉXITO ===\n";
