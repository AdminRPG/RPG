<?php
/**
 * One Piece: Eternal · Migración de Gestión de Barcos, Invitaciones y Despensa
 * -----------------------------------------------------------------------------
 * Añade campos foto_url, descripcion, despensa y activo a mybb_rol_barcos
 * y crea la tabla mybb_rol_barco_invitaciones.
 *
 * Idempotente. Ejecutar: php scripts/migrate-barco-gestion.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
require_once __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración de Gestión de Barcos ===\n";

// 1. Nuevas columnas en mybb_rol_barcos
$cols = array(
    'foto_url'    => "VARCHAR(255) NOT NULL DEFAULT '' AFTER nombre",
    'descripcion' => "TEXT NULL AFTER foto_url",
    'despensa'    => "INT(11) NOT NULL DEFAULT 100 AFTER estado_casco",
    'activo'      => "TINYINT(1) NOT NULL DEFAULT 1 AFTER despensa",
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

// 2. Tabla mybb_rol_barco_invitaciones
$create_inv_table = "CREATE TABLE IF NOT EXISTS {$PREFIX}rol_barco_invitaciones (
    invitacion_id INT(11) NOT NULL AUTO_INCREMENT,
    barco_id INT(11) NOT NULL,
    pid_invitado INT(11) NOT NULL,
    pid_invitador INT(11) NOT NULL,
    puesto VARCHAR(50) NOT NULL DEFAULT 'tripulante',
    estado ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
    dateline INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (invitacion_id),
    KEY barco_id (barco_id),
    KEY pid_invitado (pid_invitado),
    KEY estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($create_inv_table)) {
    echo "  [+] Tabla rol_barco_invitaciones creada/verificada.\n";
} else {
    echo "  [ERROR] Error creando rol_barco_invitaciones: " . $db->error . "\n";
}

echo "\n=== MIGRACIÓN COMPLETADA CON ÉXITO ===\n";
