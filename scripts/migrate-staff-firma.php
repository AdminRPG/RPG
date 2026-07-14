<?php
/**
 * One Piece Eternal · Migración "staff por personaje + firma/icono + tablas de rol"
 * --------------------------------------------------------------------------------
 * 1) Añade a rol_personajes las columnas que convierten al PERSONAJE (no la cuenta)
 *    en la unidad de staff, más su icono y firma propios:
 *      - staff_rol      : ''|colaborador|moderador|administrador|webmaster (jerárquico)
 *      - staff_narrador : 0/1 (rol OPCIONAL e independiente; puede combinarse)
 *      - icono          : URL del icono de post (distinto del avatar/retrato)
 *      - firma          : firma BBCode que se muestra en cada post de ese personaje
 * 2) Crea las tablas de apoyo para las siguientes fases (idempotente):
 *      - rol_post_templates : plantillas de post reutilizables por personaje
 *      - rol_relaciones     : mapa de relaciones entre personajes
 *      - rol_thread_meta     : metadatos in-rol del tema (pasado/presente, fecha, tag)
 * 3) Migra el staff antiguo (rol_cuentas.staff_level) al personaje ACTIVO de cada
 *    cuenta y garantiza que el personaje activo de uid=1 sea Web Master.
 *
 * Idempotente: comprueba SHOW COLUMNS / tablas antes de tocar nada.
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" \
 *     "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-staff-firma.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

function staff_firma_run(mysqli $db, string $sql, string $label): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$label}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$label}\n";
}

echo "=== Migración staff por personaje + firma/icono ===\n";

echo "\n--- rol_personajes: columnas nuevas ---\n";
add_col($db, "{$PREFIX}rol_personajes", 'staff_rol',      "VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'colaborador|moderador|administrador|webmaster'");
add_col($db, "{$PREFIX}rol_personajes", 'staff_narrador', "TINYINT NOT NULL DEFAULT 0 COMMENT 'rol opcional narrador (independiente)'");
add_col($db, "{$PREFIX}rol_personajes", 'icono',          "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'icono de post (distinto del avatar)'");
add_col($db, "{$PREFIX}rol_personajes", 'firma',          "TEXT NULL COMMENT 'firma BBCode por personaje'");

echo "\n--- Tablas de apoyo (fases siguientes) ---\n";
staff_firma_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_post_templates` (
        tpl_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid       INT UNSIGNED NOT NULL,
        nombre    VARCHAR(120) NOT NULL DEFAULT '',
        cuerpo    MEDIUMTEXT NULL,
        disporder INT NOT NULL DEFAULT 0,
        dateline  INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (tpl_id),
        KEY idx_pid (pid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "rol_post_templates");

staff_firma_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_relaciones` (
        rid         INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pid         INT UNSIGNED NOT NULL COMMENT 'personaje dueño del mapa',
        destino_pid INT UNSIGNED NOT NULL COMMENT 'personaje relacionado',
        etiqueta    VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'nombre de la relación',
        tipo        VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'aliado|rival|familia|... ',
        descripcion TEXT NULL,
        px          INT NOT NULL DEFAULT 0 COMMENT 'posición X en el mapa svg',
        py          INT NOT NULL DEFAULT 0 COMMENT 'posición Y en el mapa svg',
        dateline    INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (rid),
        KEY idx_pid (pid),
        KEY idx_destino (destino_pid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "rol_relaciones");

staff_firma_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_thread_meta` (
        tid       INT UNSIGNED NOT NULL,
        era       ENUM('pasado','presente') NOT NULL DEFAULT 'presente',
        fecha_rol INT NULL COMMENT 'año/fecha in-rol si es pasado (timestamp o año)',
        tag       VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Mision|Trama|Viaje|Fic',
        dateline  INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (tid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
", "rol_thread_meta");

// ─────────────────────────────────────────────────────────────
// Migración del staff antiguo (rol_cuentas.staff_level) al personaje ACTIVO.
// ─────────────────────────────────────────────────────────────
echo "\n--- Migrar staff de cuenta -> personaje activo ---\n";
$map = array(3 => 'administrador', 2 => 'moderador', 1 => 'colaborador');
$res = $db->query("SELECT uid, staff_level, personaje_activo FROM `{$PREFIX}rol_cuentas` WHERE staff_level > 0");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $uid = (int) $row['uid'];
        $lvl = (int) $row['staff_level'];
        $pid = (int) $row['personaje_activo'];
        if ($pid <= 0) {
            // Sin activo: usa el personaje de menor pid de esa cuenta.
            $r2 = $db->query("SELECT pid FROM `{$PREFIX}rol_personajes` WHERE uid = {$uid} ORDER BY pid ASC LIMIT 1");
            if ($r2 && $r2->num_rows) { $pid = (int) $r2->fetch_assoc()['pid']; }
        }
        if ($pid > 0 && isset($map[$lvl])) {
            $rol = $map[$lvl];
            $db->query("UPDATE `{$PREFIX}rol_personajes` SET staff_rol = '{$rol}' WHERE pid = {$pid}");
            echo "  [OK] uid {$uid}: personaje {$pid} -> {$rol}\n";
        }
    }
}

// uid=1 = Web Master en su personaje activo (o primero) sí o sí.
$r = $db->query("SELECT personaje_activo FROM `{$PREFIX}rol_cuentas` WHERE uid = 1 LIMIT 1");
$pid1 = ($r && $r->num_rows) ? (int) $r->fetch_assoc()['personaje_activo'] : 0;
if ($pid1 <= 0) {
    $r = $db->query("SELECT pid FROM `{$PREFIX}rol_personajes` WHERE uid = 1 ORDER BY pid ASC LIMIT 1");
    $pid1 = ($r && $r->num_rows) ? (int) $r->fetch_assoc()['pid'] : 0;
}
if ($pid1 > 0) {
    $db->query("UPDATE `{$PREFIX}rol_personajes` SET staff_rol = 'webmaster' WHERE pid = {$pid1}");
    echo "  [OK] uid 1: personaje {$pid1} -> webmaster\n";
} else {
    echo "  [warn] uid 1 no tiene personajes; asigna Web Master manualmente\n";
}

echo "\n=== DONE ===\n";
$db->close();
