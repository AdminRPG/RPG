<?php
/**
 * Migración: Cola de viajes (publicación asíncrona) + alerta 'viaje_publicado' + tarea MyBB.
 * ------------------------------------------------------------------------------------
 * Crea:
 *   - Tabla `rol_viajes_cola`   (viajes pendientes de publicar en segundo plano)
 *   - Tabla `rol_flash`         (mensajes flash one-time para el navbar, p.ej. "¡Te avisaremos!")
 *   - Extiende ENUM `rol_alertas.tipo` con 'viaje_publicado'
 *   - Registra la tarea programada MyBB `ope_viajes` (procesa la cola)
 *
 * Idempotente. Ejecutar: php scripts/migrate-viajes-cola.php
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

function table_exists(mysqli $db, string $name): bool
{
    $r = $db->query("SHOW TABLES LIKE '{$name}'");
    return $r && $r->num_rows > 0;
}

// ═══════════════════════════════════════════════════════════════
echo "=== Tabla rol_viajes_cola ===\n";
run($db, 'CREATE rol_viajes_cola', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_viajes_cola (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid_capitan INT UNSIGNED NOT NULL,
        uid INT UNSIGNED NOT NULL DEFAULT 0,
        payload_json TEXT NOT NULL,
        estado ENUM('pendiente','procesando','ok','fallo') NOT NULL DEFAULT 'pendiente',
        viaje_id INT UNSIGNED NOT NULL DEFAULT 0,
        tid INT UNSIGNED NOT NULL DEFAULT 0,
        error VARCHAR(255) NOT NULL DEFAULT '',
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        procesado_dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_estado (estado),
        KEY idx_capitan_estado (pid_capitan, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ═══════════════════════════════════════════════════════════════
echo "\n=== Tabla rol_flash ===\n";
run($db, 'CREATE rol_flash', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_flash (
        fid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uid INT UNSIGNED NOT NULL,
        tipo VARCHAR(20) NOT NULL DEFAULT 'ok',
        mensaje TEXT NOT NULL,
        leido TINYINT(1) NOT NULL DEFAULT 0,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (fid),
        KEY idx_uid_leido (uid, leido),
        KEY idx_dateline (dateline)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ═══════════════════════════════════════════════════════════════
echo "\n=== ENUM rol_alertas.tipo += viaje_publicado ===\n";
if (table_exists($db, $PREFIX . 'rol_alertas')) {
    run($db, 'ALTER rol_alertas.tipo', "
        ALTER TABLE {$PREFIX}rol_alertas
        MODIFY COLUMN tipo ENUM('mensaje_nuevo','personaje_aprobado','personaje_rechazado',
        'personaje_moderado','staff_asignado','viaje_publicado') NOT NULL DEFAULT 'mensaje_nuevo'
    ");
} else {
    echo "  [skip] rol_alertas no existe\n";
}

// ═══════════════════════════════════════════════════════════════
echo "\n=== Tarea programada MyBB: ope_viajes ===\n";
if (table_exists($db, $PREFIX . 'tasks')) {
    $tq = $db->query("SELECT tid FROM {$PREFIX}tasks WHERE file = 'ope_viajes' LIMIT 1");
    if ($tq && $tq->num_rows > 0) {
        echo "  [=] tarea ope_viajes ya existe\n";
    } else {
        run($db, 'INSERT tarea ope_viajes', "
            INSERT INTO {$PREFIX}tasks
            (title, description, file, minute, hour, day, month, weekday, nextrun, lastrun, enabled, logging, locked)
            VALUES
            ('Publicación de viajes (cola)', 'Procesa la cola de viajes pendientes y notifica a capitán y tripulantes.', 'ope_viajes', '*', '*', '*', '*', '*', " . time() . ", 0, 1, 0, 0)
        ");
    }
} else {
    echo "  [skip] tabla tasks no existe\n";
}

echo "\n=== DONE ===\n";
echo "Creadas: rol_viajes_cola, rol_flash · ENUM viaje_publicado · tarea ope_viajes\n";
