<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../inc/init.php';
$PREFIX = TABLE_PREFIX;
if (!$db->field_exists('descripcion_larga', 'rol_mv_misiones')) {
    $db->write_query("ALTER TABLE {$PREFIX}rol_mv_misiones ADD COLUMN descripcion_larga MEDIUMTEXT NULL COMMENT 'descripcion completa que se ve al expandir' AFTER resumen");
    echo "[+] descripcion_larga\n";
} else {
    echo "[=] descripcion_larga ya existe\n";
}
echo "Listo.\n";