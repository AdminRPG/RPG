<?php
/**
 * Corrige la columna mybb_themes.stylesheets (mapa compilado) que MyBB usa para
 * reconstruir la caché default_theme. Renombra iforge.css -> ope.css de forma
 * segura para la serialización y limpia la caché para que se regenere.
 *   php scripts/fix-theme-stylesheets.php
 */
error_reporting(E_ALL); ini_set('display_errors', '1');
require __DIR__ . '/_db-config.php';

function deep_replace($d, $f, $t) {
    if (is_array($d)) { $o = []; foreach ($d as $k => $v) { $nk = is_string($k) ? str_replace($f, $t, $k) : $k; $o[$nk] = deep_replace($v, $f, $t); } return $o; }
    return is_string($d) ? str_replace($f, $t, $d) : $d;
}

$res = $db->query("SELECT tid, stylesheets FROM mybb_themes WHERE stylesheets LIKE '%iforge.css%'");
while ($row = $res->fetch_assoc()) {
    $map = @unserialize($row['stylesheets']);
    if (is_array($map)) {
        $map = deep_replace($map, 'iforge.css', 'ope.css');
        $ser = $db->real_escape_string(serialize($map));
        $tid = (int)$row['tid'];
        $db->query("UPDATE mybb_themes SET stylesheets='{$ser}' WHERE tid={$tid}");
        echo "THEME {$tid}: columna stylesheets iforge.css -> ope.css\n";
    }
}

// Forzar regeneración de la caché del tema por defecto.
$db->query("DELETE FROM mybb_datacache WHERE title='default_theme'");
echo "default_theme cache limpiada (se regenerará en la próxima carga).\n";
$db->close();
