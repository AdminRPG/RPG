<?php
/**
 * I-Forge · Migración acompañantes (NPCs secundarios ↔ personaje)
 * ----------------------------------------------------------------
 * Vincula NPCs de mybb_rol_npcs_secundarios a personajes (máx. 2 por pid).
 *
 * Ejecutar (desde la raíz del repo):
 *   php scripts/migrate-acompanantes.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

echo "=== Migración acompañantes (NPC secundario) ===\n";

$table = $PREFIX . 'rol_acompanantes';
$sql = "
CREATE TABLE IF NOT EXISTS {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL COMMENT 'personaje dueño',
    npc_id BIGINT UNSIGNED NOT NULL COMMENT 'rol_npcs_secundarios.id',
    slot TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 o 2',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pid_slot (pid, slot),
    UNIQUE KEY uq_pid_npc (pid, npc_id),
    KEY idx_npc (npc_id),
    KEY idx_pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if ($db->query($sql) === false) {
    fwrite(STDERR, "  [ERROR] creando {$table}: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] tabla {$table} lista\n";

$table2 = $PREFIX . 'rol_acompanante_solicitudes';
$sql2 = "
CREATE TABLE IF NOT EXISTS {$table2} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL COMMENT 'personaje solicitante',
    uid INT UNSIGNED NOT NULL COMMENT 'cuenta solicitante',
    npc_id BIGINT UNSIGNED NOT NULL COMMENT 'rol_npcs_secundarios.id',
    motivo TEXT NULL COMMENT 'justificación del jugador',
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente' COMMENT 'pendiente|aprobada|rechazada',
    slot TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'slot asignado al aprobar',
    staff_uid INT UNSIGNED NOT NULL DEFAULT 0,
    staff_nota VARCHAR(500) NOT NULL DEFAULT '',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    resolved INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_estado (estado),
    KEY idx_pid (pid),
    KEY idx_npc (npc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
if ($db->query($sql2) === false) {
    fwrite(STDERR, "  [ERROR] creando {$table2}: " . $db->error . "\n");
    exit(1);
}
echo "  [OK] tabla {$table2} lista\n";

echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_acompan%'");
while ($t = $check->fetch_array()) {
    echo "  tabla: {$t[0]}\n";
}

echo "\n=== DONE ===\n";
$db->close();
