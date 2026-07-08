<?php
/**
 * One Piece Eternal · Migración de BD para el rename iforge -> ope.
 * ----------------------------------------------------------------
 * Se ejecuta con mysqli directo (SIN bootstrap de MyBB), porque el plugin
 * ya se renombró en disco y la caché de plugins aún apunta al codename viejo.
 *
 *   php scripts/migrate-ope-db.php
 *
 * Idempotente: se puede re-ejecutar sin romper nada.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) { fwrite(STDERR, "DB error: {$db->connect_error}\n"); exit(1); }
$db->set_charset('utf8mb4');
$P = 'mybb_';

function deep_replace($data, $from, $to) {
    if (is_array($data)) {
        $out = [];
        foreach ($data as $k => $v) {
            $nk = is_string($k) ? str_replace($from, $to, $k) : $k;
            $out[$nk] = deep_replace($v, $from, $to);
        }
        return $out;
    }
    return is_string($data) ? str_replace($from, $to, $data) : $data;
}

/* ── 1) Renombrar columnas (idempotente) ── */
$cols = [
    ['posts',   'iforge_pid',     'ope_pid'],
    ['threads', 'iforge_pid',     'ope_pid'],
    ['threads', 'iforge_lastpid', 'ope_lastpid'],
    ['forums',  'iforge_lastpid', 'ope_lastpid'],
];
foreach ($cols as [$t, $old, $new]) {
    $tbl = $P . $t;
    $chk = $db->query("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema='mybb_foro' AND table_name='{$tbl}' AND column_name='{$old}'");
    $has = (int)($chk->fetch_assoc()['c'] ?? 0);
    if ($has) {
        $db->query("ALTER TABLE {$tbl} CHANGE {$old} {$new} INT UNSIGNED NOT NULL DEFAULT 0");
        echo "COL: {$tbl}.{$old} -> {$new}\n";
    } else {
        echo "COL: {$tbl}.{$old} ya no existe (ok)\n";
    }
}

/* ── 2) datacache: iforge_home -> ope_home ── */
$db->query("UPDATE {$P}datacache SET title='ope_home' WHERE title='iforge_home'");
echo "CACHE: iforge_home -> ope_home (filas: {$db->affected_rows})\n";

/* ── 3) plugins cache: codename iforge_rol -> ope_rol (serializado) ── */
$res = $db->query("SELECT cache FROM {$P}datacache WHERE title='plugins' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $data = @unserialize($row['cache']);
    if (is_array($data)) {
        $data = deep_replace($data, 'iforge_rol', 'ope_rol');
        $ser = $db->real_escape_string(serialize($data));
        $db->query("UPDATE {$P}datacache SET cache='{$ser}' WHERE title='plugins'");
        echo "PLUGINS: codename iforge_rol -> ope_rol\n";
    }
}

/* ── 4) themestylesheets: iforge.css -> ope.css ── */
$db->query("UPDATE {$P}themestylesheets SET name='ope.css' WHERE name='iforge.css'");
echo "STYLESHEET: name iforge.css -> ope.css (filas: {$db->affected_rows})\n";

/* ── 5) theme properties (disporder) ── */
$res = $db->query("SELECT tid, properties FROM {$P}themes WHERE properties LIKE '%iforge.css%'");
while ($row = $res->fetch_assoc()) {
    $props = @unserialize($row['properties']);
    if (is_array($props)) {
        $props = deep_replace($props, 'iforge.css', 'ope.css');
        $ser = $db->real_escape_string(serialize($props));
        $tid = (int)$row['tid'];
        $db->query("UPDATE {$P}themes SET properties='{$ser}' WHERE tid={$tid}");
        echo "THEME {$tid}: disporder iforge.css -> ope.css\n";
    }
}

/* ── 6) default_theme datacache: disporder + rutas de stylesheets ── */
$res = $db->query("SELECT cache FROM {$P}datacache WHERE title='default_theme' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $data = @unserialize($row['cache']);
    if (is_array($data)) {
        // properties viene como string serializado anidado: renormalizamos.
        if (isset($data['properties']) && is_string($data['properties'])) {
            $inner = @unserialize($data['properties']);
            if (is_array($inner)) {
                $inner = deep_replace($inner, 'iforge.css', 'ope.css');
                $data['properties'] = serialize($inner);
            }
        }
        if (isset($data['stylesheets']) && is_string($data['stylesheets'])) {
            $inner = @unserialize($data['stylesheets']);
            if (is_array($inner)) {
                $inner = deep_replace($inner, 'iforge.css', 'ope.css');
                $data['stylesheets'] = serialize($inner);
            }
        }
        $ser = $db->real_escape_string(serialize($data));
        $db->query("UPDATE {$P}datacache SET cache='{$ser}' WHERE title='default_theme'");
        echo "DEFAULT_THEME: rutas iforge.css -> ope.css\n";
    }
}

$db->close();
echo "Migración BD completada.\n";
