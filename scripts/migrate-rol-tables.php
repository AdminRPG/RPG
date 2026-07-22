<?php
/**
 * One Piece: Eternal · Migración del sistema de rol (tablas mybb_rol_*)
 * ----------------------------------------------------------
 * Crea el esquema del sistema de personajes como tablas con prefijo MyBB
 * (mybb_rol_*). Es idempotente: usa CREATE TABLE IF NOT EXISTS y se puede
 * re-ejecutar sin efectos secundarios.
 *
 * Diseño: datos flexibles/expandibles en columnas JSON (stats, inventario,
 * economía, bio, datos) para poder editarlos más adelante sin migraciones.
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" \
 *     "C:\Users\Fgonz\Documents\Proyectos\One Piece: Eternal\scripts\migrate-rol-tables.php"
 *
 * NOTA: MyBB antepone siempre el prefijo `mybb_`. Estas tablas conviven sin
 * colisión con las tablas normalizadas del backend Slim (rol_cuentas, etc.,
 * sin prefijo) descritas en rol-backend/database/migrations.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

/** Ejecuta una sentencia y aborta con mensaje claro si falla. */
function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] $label\n";
}

echo "=== Migración rol (mybb_rol_*) ===\n";

// ─────────────────────────────────────────────────────────────
// mybb_rol_cuentas — ajustes por cuenta MyBB (uid)
// ─────────────────────────────────────────────────────────────
run($db, 'mybb_rol_cuentas', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_cuentas (
    uid INT UNSIGNED NOT NULL,
    staff_level TINYINT NOT NULL DEFAULT 0 COMMENT '0=ninguno,1=narrador,2=moderador,3=administrador',
    slots INT NOT NULL DEFAULT 1,
    personaje_activo INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'pid del personaje activo',
    narrador TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Narrador oficial',
    datos JSON NULL,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (uid),
    KEY idx_staff (staff_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// mybb_rol_personajes — fichas de personaje (JSON expandible)
// ─────────────────────────────────────────────────────────────
run($db, 'mybb_rol_personajes', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_personajes (
    pid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    uid INT UNSIGNED NOT NULL COMMENT 'MyBB user propietario',
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL DEFAULT '',
    estado ENUM('borrador','revision','aprobado','rechazado') NOT NULL DEFAULT 'borrador',
    activo TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'personaje activo de la cuenta',
    rango VARCHAR(4) NOT NULL DEFAULT 'E' COMMENT 'escala de calor E..M+',
    nivel INT NOT NULL DEFAULT 1,
    avatar VARCHAR(255) NOT NULL DEFAULT '',
    datos JSON NULL COMMENT 'stats, atributos, derivadas, crisol',
    inventario JSON NULL,
    economia JSON NULL,
    bio JSON NULL COMMENT 'historia, apariencia, caracter',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (pid),
    KEY idx_uid (uid),
    KEY idx_uid_activo (uid, activo),
    KEY idx_estado (estado),
    KEY idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// mybb_rol_tramites — solicitudes al staff (JSON payload)
// ─────────────────────────────────────────────────────────────
run($db, 'mybb_rol_tramites', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_tramites (
    tid INT UNSIGNED NOT NULL AUTO_INCREMENT,
    uid INT UNSIGNED NOT NULL,
    pid INT UNSIGNED NOT NULL DEFAULT 0,
    tipo VARCHAR(50) NOT NULL DEFAULT 'general',
    estado ENUM('pendiente','en_proceso','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    datos JSON NULL,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    lastedit INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (tid),
    KEY idx_uid (uid),
    KEY idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// mybb_rol_transacciones — economía (JSON detalle)
// ─────────────────────────────────────────────────────────────
run($db, 'mybb_rol_transacciones', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_transacciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    origen_pid INT UNSIGNED NOT NULL DEFAULT 0,
    destino_pid INT UNSIGNED NOT NULL DEFAULT 0,
    tipo VARCHAR(40) NOT NULL DEFAULT 'ajuste',
    datos JSON NULL,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_origen (origen_pid),
    KEY idx_destino (destino_pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// mybb_rol_tiradas — dados (JSON detalle)
// ─────────────────────────────────────────────────────────────
run($db, 'mybb_rol_tiradas', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_tiradas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pid INT UNSIGNED NOT NULL DEFAULT 0,
    uid INT UNSIGNED NOT NULL DEFAULT 0,
    hilo INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'tid del hilo MyBB',
    formula VARCHAR(60) NOT NULL DEFAULT '',
    resultado INT NOT NULL DEFAULT 0,
    datos JSON NULL,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_pid (pid),
    KEY idx_hilo (hilo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// Seed: uid=1 (super admin) → staff_level=3 para probar Zona Staff.
// Idempotente vía ON DUPLICATE KEY UPDATE.
// ─────────────────────────────────────────────────────────────
$now = time();
$seed = $db->prepare("
    INSERT INTO {$PREFIX}rol_cuentas (uid, staff_level, slots, personaje_activo, dateline)
    VALUES (1, 3, 3, 0, ?)
    ON DUPLICATE KEY UPDATE staff_level = GREATEST(staff_level, 3)
");
$seed->bind_param('i', $now);
if ($seed->execute()) {
    echo "  [OK] seed uid=1 staff_level=3\n";
} else {
    fwrite(STDERR, "  [ERROR] seed uid=1: " . $seed->error . "\n");
    exit(1);
}
$seed->close();

// ─────────────────────────────────────────────────────────────
// Activar el plugin ope_rol en la caché de plugins de MyBB.
// ─────────────────────────────────────────────────────────────
$res = $db->query("SELECT cache FROM {$PREFIX}datacache WHERE title = 'plugins' LIMIT 1");
$plugins = array('active' => array());
$exists_row = false;
if ($res && ($row = $res->fetch_assoc())) {
    $exists_row = true;
    $decoded = @unserialize($row['cache']);
    if (is_array($decoded)) {
        $plugins = $decoded;
    }
}
if (!isset($plugins['active']) || !is_array($plugins['active'])) {
    $plugins['active'] = array();
}
$plugins['active']['ope_rol'] = 'ope_rol';
$serialized = serialize($plugins);

if ($exists_row) {
    $stmt = $db->prepare("UPDATE {$PREFIX}datacache SET cache = ? WHERE title = 'plugins'");
    $stmt->bind_param('s', $serialized);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $db->prepare("INSERT INTO {$PREFIX}datacache (title, cache) VALUES ('plugins', ?)");
    $stmt->bind_param('s', $serialized);
    $stmt->execute();
    $stmt->close();
}
echo "  [OK] plugin ope_rol activado (datacache 'plugins')\n";

// Verificación
echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_%'");
while ($t = $check->fetch_array()) {
    echo "  tabla: {$t[0]}\n";
}
$r = $db->query("SELECT uid, staff_level, slots FROM {$PREFIX}rol_cuentas WHERE uid = 1");
if ($r && ($row = $r->fetch_assoc())) {
    echo "  cuenta uid=1: staff_level={$row['staff_level']}, slots={$row['slots']}\n";
}

echo "\n=== DONE ===\n";
$db->close();
