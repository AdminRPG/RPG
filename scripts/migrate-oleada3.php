<?php
/**
 * Migración Oleada 3 — Oráculo de Viaje + tablas rol_viajes + foro Alta Mar.
 *
 * Idempotente. Ejecutar:
 *   php scripts/migrate-oleada3.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

function run(mysqli $db, string $label, string $sql): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] $label: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] $label\n";
}

function setting_exists(mysqli $db, string $prefix, string $name): bool
{
    $name = $db->real_escape_string($name);
    $r = $db->query("SELECT sid FROM {$prefix}settings WHERE name='{$name}' LIMIT 1");
    return $r && $r->num_rows > 0;
}

function upsert_setting(mysqli $db, string $prefix, string $name, string $title, string $value): void
{
    $nameE  = $db->real_escape_string($name);
    $titleE = $db->real_escape_string($title);
    $valE   = $db->real_escape_string($value);
    if (setting_exists($db, $prefix, $name)) {
        run($db, "UPDATE setting {$name}", "UPDATE {$prefix}settings SET value='{$valE}' WHERE name='{$nameE}'");
        return;
    }
    run($db, "INSERT setting {$name}", "INSERT INTO {$prefix}settings (name, title, description, optionscode, value, disporder, gid)
        VALUES ('{$nameE}', '{$titleE}', 'FID del foro Alta Mar (travesías OPE Eternal)', 'numeric', '{$valE}', 1, 1)");
}

function upsert_forum(mysqli $db, string $prefix, string $name, string $desc, int $pid, string $type, int $disporder): int
{
    $nameE = $db->real_escape_string($name);
    $r = $db->query("SELECT fid FROM {$prefix}forums WHERE name='{$nameE}' AND pid={$pid} AND type='{$type}' LIMIT 1");
    if ($r && ($row = $r->fetch_assoc())) {
        $fid = (int) $row['fid'];
        $descE = $db->real_escape_string($desc);
        $db->query("UPDATE {$prefix}forums SET description='{$descE}', disporder={$disporder}, active=1 WHERE fid={$fid}");
        echo "  [=] Foro {$name} (fid={$fid})\n";
        return $fid;
    }
    $descE = $db->real_escape_string($desc);
    $db->query("INSERT INTO {$prefix}forums
        (name, description, active, type, pid, disporder, open, allowhtml, allowmycode, allowsmilies,
         allowimgcode, allowvideocode, allowtratings, usepostcounts, usethreadcounts, requireprefix,
         showinjump, parentlist, threads, posts, unapprovedthreads, unapprovedposts, defaultsortby, rules, rulestitle)
        VALUES ('{$nameE}', '{$descE}', 1, '{$type}', {$pid}, {$disporder}, 1, 0, 1, 1,
         1, 1, 1, 1, 1, 0, 1, '', 0, 0, 0, 0, 'lastpost', '', '')");
    $fid = (int) $db->insert_id;
    $parentlist = (string) $fid;
    if ($pid > 0) {
        $pr = $db->query("SELECT parentlist FROM {$prefix}forums WHERE fid={$pid}");
        $prow = $pr ? $pr->fetch_assoc() : null;
        $pp = $prow ? (string) $prow['parentlist'] : '';
        $parentlist = ($pp !== '' ? $pp . ',' : '') . $fid;
    }
    $db->query("UPDATE {$prefix}forums SET parentlist='{$parentlist}' WHERE fid={$fid}");
    echo "  [+] Foro {$name} (fid={$fid})\n";
    return $fid;
}

echo "=== Tabla rol_viajes ===\n";
run($db, 'CREATE rol_viajes', "
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_viajes (
        viaje_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        tid INT UNSIGNED NOT NULL DEFAULT 0,
        fid_alta_mar SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        pid_capitan INT UNSIGNED NOT NULL DEFAULT 0,
        uid_solicitante INT UNSIGNED NOT NULL DEFAULT 0,
        fid_origen SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        fid_destino SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        origen_nombre VARCHAR(120) NOT NULL DEFAULT '',
        destino_nombre VARCHAR(120) NOT NULL DEFAULT '',
        barco_nombre VARCHAR(120) NOT NULL DEFAULT '',
        barco_tipo VARCHAR(60) NOT NULL DEFAULT 'estandar',
        tripulantes_json MEDIUMTEXT NOT NULL,
        tramos TINYINT UNSIGNED NOT NULL DEFAULT 1,
        posts_min INT UNSIGNED NOT NULL DEFAULT 6,
        plazo_dias TINYINT UNSIGNED NOT NULL DEFAULT 5,
        estado ENUM('activo','cerrado','cancelado') NOT NULL DEFAULT 'activo',
        resultado_json MEDIUMTEXT NOT NULL,
        mods_json TEXT NULL,
        suministros VARCHAR(255) NOT NULL DEFAULT '',
        notas TEXT NULL,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        cierre_dateline INT UNSIGNED NOT NULL DEFAULT 0,
        cierre_pid INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (viaje_id),
        UNIQUE KEY uq_tid (tid),
        KEY idx_capitan (pid_capitan),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "\n=== Foro Alta Mar ===\n";
$navCat = upsert_forum($db, $PREFIX, 'Navegación', 'Travesías en alta mar. Los hilos los abre OPE Eternal con el Oráculo de Viaje.', 0, 'c', 3);
$altaMarFid = upsert_forum($db, $PREFIX, 'Alta Mar', 'Bitácoras de travesía generadas por el Oráculo. Rolear aquí hasta solicitar la llegada.', $navCat, 'f', 1);

echo "\n=== Setting ope_alta_mar_fid ===\n";
upsert_setting($db, $PREFIX, 'ope_alta_mar_fid', 'Foro Alta Mar (FID)', (string) $altaMarFid);

$db->query("DELETE FROM {$PREFIX}datacache WHERE title IN ('forums','forumsdisplay','moderators')");
echo "  [OK] Caché de foros invalidada\n";

echo "\n=== DONE (Alta Mar fid={$altaMarFid}) ===\n";
