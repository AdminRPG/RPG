<?php
/**
 * Migración Oleada 1 — Sistema OPE Eternal + Motor de PP (Puntos de Progreso).
 *
 * Idempotente: comprueba existencia antes de insertar/crear.
 *
 * Qué hace:
 *   1. Crea el usuario MyBB `OPE Eternal` (uid reservado).
 *   2. Crea la cuenta rol `rol_cuentas` y el personaje `rol_personajes` del sistema.
 *   3. Crea tablas `rol_pp_log` y `rol_pp_saldo`.
 *   4. Añade columna `pp_gastado` a `rol_pp_saldo` si no existe.
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" ^
 *     "C:\Users\Fgonz\Documents\Proyectos\One Piece: Eternal\scripts\migrate-oleada1.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

// ── Helpers ────────────────────────────────────────────────────────────────

function oleada1_run(mysqli $db, string $sql, string $label): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$label}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$label}\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. USUARIO SISTEMA OPE Eternal
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 1. USUARIO SISTEMA OPE Eternal ===\n";

// 1a. Buscar un uid pequeño libre (intentamos uid=2 primero, si no, el siguiente).
$sys_uid = 0;
$res = $db->query("SELECT uid FROM `{$PREFIX}users` WHERE uid = 2 LIMIT 1");
if ($res && $res->num_rows > 0) {
    // uid=2 ya existe. Ver si es OPE Eternal.
    $row = $res->fetch_assoc();
    $check = $db->query("SELECT uid FROM `{$PREFIX}users` WHERE uid = 2 AND username = 'OPE Eternal' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $sys_uid = 2;
        echo "  [skip] OPE Eternal ya existe como uid=2\n";
    } else {
        // uid=2 ocupado por otro. Buscar siguiente libre.
        $res3 = $db->query("SELECT MAX(uid)+1 AS nxt FROM `{$PREFIX}users`");
        $nxt = $res3 ? (int) $res3->fetch_assoc()['nxt'] : 3;
        $sys_uid = max($nxt, 3);
    }
} else {
    $sys_uid = 2;
}

// 1b. Crear usuario MyBB si no existe.
if ($sys_uid > 0) {
    $exists = $db->query("SELECT uid FROM `{$PREFIX}users` WHERE username = 'OPE Eternal' LIMIT 1");
    if ($exists && $exists->num_rows > 0) {
        $row = $exists->fetch_assoc();
        $sys_uid = (int) $row['uid'];
        echo "  [skip] usuario OPE Eternal ya existe (uid={$sys_uid})\n";
    } else {
        $now = time();
        $salt = substr(bin2hex(random_bytes(4)), 0, 8);
        $pass = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $loginkey = bin2hex(random_bytes(25));
        $stmt = $db->prepare("
            INSERT INTO `{$PREFIX}users`
                (uid, username, password, salt, loginkey, email,
                 usergroup, displaygroup, regdate, lastactive, lastvisit,
                 signature, buddylist, ignorelist, pmfolders, notepad, usernotes)
            VALUES (?, 'OPE Eternal', ?, ?, ?, 'system@ope.local',
                    4, 4, ?, ?, ?,
                    '', '', '', '', '', '')
        ");
        $stmt->bind_param('isssiii', $sys_uid, $pass, $salt, $loginkey, $now, $now, $now);
        if ($stmt->execute()) {
            echo "  [OK] usuario OPE Eternal creado (uid={$sys_uid})\n";
        } else {
            fwrite(STDERR, "  [ERROR] crear usuario: " . $stmt->error . "\n");
            exit(1);
        }
        $stmt->close();
    }
}

// 1c. Crear cuenta rol (rol_cuentas).
if ($sys_uid > 0 && table_exists($db, "{$PREFIX}rol_cuentas")) {
    $r = $db->query("SELECT uid FROM `{$PREFIX}rol_cuentas` WHERE uid = {$sys_uid} LIMIT 1");
    if ($r && $r->num_rows > 0) {
        echo "  [skip] cuenta rol OPE Eternal ya existe\n";
    } else {
        $now = time();
        $stmt = $db->prepare("INSERT INTO `{$PREFIX}rol_cuentas` (uid, staff_level, slots, personaje_activo, dateline) VALUES (?, 3, 3, 0, ?)");
        $stmt->bind_param('ii', $sys_uid, $now);
        $stmt->execute();
        $stmt->close();
        echo "  [OK] cuenta rol OPE Eternal creada\n";
    }
}

// 1d. Crear personaje sistema (rol_personajes).
$sys_pid = 0;
if ($sys_uid > 0 && table_exists($db, "{$PREFIX}rol_personajes")) {
    $r = $db->query("SELECT pid FROM `{$PREFIX}rol_personajes` WHERE uid = {$sys_uid} AND nombre = 'OPE Eternal' LIMIT 1");
    if ($r && $r->num_rows > 0) {
        $sys_pid = (int) $r->fetch_assoc()['pid'];
        echo "  [skip] personaje OPE Eternal ya existe (pid={$sys_pid})\n";
    } else {
        $now = time();
        $datos = json_encode(array(
            'concepto' => 'Sistema automático de One Piece: Eternal',
            'faccion' => '',
            'raza' => array('principal' => 'Humano'),
            'puro' => true,
            'stats_base' => array_fill_keys(array('FUE','DES','VIG','AGI','INT','ING','CON','PER','CAR','CTR','VOL','SEN'), 1),
            'stats_efectivas' => array_fill_keys(array('FUE','DES','VIG','AGI','INT','ING','CON','PER','CAR','CTR','VOL','SEN'), 1),
            'virtudes' => array(),
            'defectos' => array(),
            'pc_gastados' => 0,
        ), JSON_UNESCAPED_UNICODE);
        $bio = json_encode(array(
            'historia' => 'OPE Eternal es el sistema automático que gestiona viajes, misiones y eventos del mundo de One Piece: Eternal.',
            'apariencia' => '',
            'personalidad' => '',
        ), JSON_UNESCAPED_UNICODE);
        $stmt = $db->prepare("
            INSERT INTO `{$PREFIX}rol_personajes`
                (uid, nombre, slug, estado, activo, rango, nivel,
                 avatar, datos, inventario, economia, bio,
                 staff_rol, staff_narrador, es_npc,
                 dateline, lastedit)
            VALUES (?, 'OPE Eternal', 'op-eternal', 'aprobado', 1, 'SS', 1,
                    '', ?, '[]', '[]', ?,
                    'webmaster', 1, 1,
                    ?, ?)
        ");
        $stmt->bind_param('issii', $sys_uid, $datos, $bio, $now, $now);
        if ($stmt->execute()) {
            $sys_pid = (int) $db->insert_id;
            echo "  [OK] personaje OPE Eternal creado (pid={$sys_pid})\n";
        } else {
            fwrite(STDERR, "  [ERROR] crear personaje: " . $stmt->error . "\n");
            exit(1);
        }
        $stmt->close();
    }

    // Actualizar personaje_activo en la cuenta.
    if ($sys_pid > 0) {
        $db->query("UPDATE `{$PREFIX}rol_cuentas` SET personaje_activo = {$sys_pid} WHERE uid = {$sys_uid}");
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 2. TABLAS DE PP (PUNTOS DE PROGRESO)
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 2. TABLAS DE PP ===\n";

// 2a. rol_pp_saldo — saldo de PP por personaje (cache vivo).
oleada1_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pp_saldo` (
        `pid`         INT UNSIGNED NOT NULL,
        `pp_total`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'PP ganados totales (suma de todos los logs)',
        `pp_gastado`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'PP gastados en stats, cartas, haki...',
        `pp_disponible` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'pp_total - pp_gastado (cache)',
        `last_update` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'rol_pp_saldo');

// 2b. rol_pp_log — registro de cada ganancia/gasto de PP.
oleada1_run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_pp_log` (
        `log_id`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `pid`       INT UNSIGNED NOT NULL COMMENT 'personaje que gana/gasta PP',
        `tid`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'tema donde ocurrió',
        `post_pid`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'pid del post que generó el PP',
        `palabras`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'palabras contadas (si aplica)',
        `pp_cambio` INT NOT NULL DEFAULT 0 COMMENT 'PP ganados (+) o gastados (-)',
        `tipo`      VARCHAR(20) NOT NULL DEFAULT 'post' COMMENT 'post|mision|arco|evento|staff|gasto_stat|gasto_carta|gasto_haki',
        `notas`     VARCHAR(500) NOT NULL DEFAULT '',
        `uid_staff` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'uid del staff que lo asignó (0 = automático)',
        `dateline`  INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`log_id`),
        KEY `idx_pid` (`pid`),
        KEY `idx_tipo` (`tipo`),
        KEY `idx_dateline` (`dateline`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'rol_pp_log');

echo "\n=== DONE ===\n";
echo "OPE Eternal: uid={$sys_uid}, pid={$sys_pid}\n";

$db->close();
