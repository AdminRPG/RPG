<?php
/**
 * Migración para el sistema de Enlace y Renombre (One Piece: Eternal)
 *
 * Elimina las tablas legacy de Haki y Wanted (One Piece)
 * y crea las nuevas estructuras para Enlace y Renombre.
 *
 * Ejecutar:
 *   py -c "import os; os.system('php scripts/migrate-ope-enlace-renombre.php')"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';
require __DIR__ . '/_migrate-lib.php';

$PREFIX = 'mybb_';

echo "\n=== MIGRACIÓN ENLACE Y RENOMBRE (OPE ETERNAL) ===\n";

// 1. Eliminar tablas antiguas
echo "1. Limpiando tablas heredadas de One Piece...\n";
$db->query("DROP TABLE IF EXISTS `{$PREFIX}rol_haki`");
echo "  [OK] Tabla `{$PREFIX}rol_haki` eliminada.\n";
$db->query("DROP TABLE IF EXISTS `{$PREFIX}rol_wanted`");
echo "  [OK] Tabla `{$PREFIX}rol_wanted` eliminada.\n";

// 2. Crear tablas nuevas
echo "2. Creando tablas nuevas...\n";

$q_enlace = "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_enlace` (
        `pid`        INT PRIMARY KEY,
        `criatura`   VARCHAR(50) NOT NULL COMMENT 'icarus|kurma|sirius|cait...',
        `nivel`      INT NOT NULL DEFAULT 1 COMMENT '1-6, Primal',
        `usos`       INT NOT NULL DEFAULT 0 COMMENT 'Contador de summon',
        `pp_gastado` INT NOT NULL DEFAULT 0,
        `updated_at` DATETIME NOT NULL,
        INDEX `idx_pid` (`pid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($db->query($q_enlace) === false) {
    die("  [ERROR] Al crear `rol_enlace`: " . $db->error . "\n");
}
echo "  [OK] Tabla `{$PREFIX}rol_enlace` creada.\n";

$q_renombre = "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_renombre` (
        `pid`         INT PRIMARY KEY,
        `puntos`      INT NOT NULL DEFAULT 0 COMMENT 'Puntos de renombre público',
        `last_update` INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
if ($db->query($q_renombre) === false) {
    die("  [ERROR] Al crear `rol_renombre`: " . $db->error . "\n");
}
echo "  [OK] Tabla `{$PREFIX}rol_renombre` creada.\n";

// 3. Backfill e inicialización de personajes existentes
echo "3. Inicializando datos de personajes existentes...\n";
$res = $db->query("SELECT pid, datos FROM `{$PREFIX}rol_personajes`");
$count = 0;

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pid = (int)$row['pid'];
        $datos = json_decode((string)$row['datos'], true);
        
        $criatura = 'icarus'; // Default si no se encuentra
        if (is_array($datos) && !empty($datos['enlace'])) {
            $criatura = $datos['enlace'];
        }
        
        $criatura_esc = $db->real_escape_string($criatura);
        
        // Registrar Enlace inicial (Nivel 1, 0 usos)
        $db->query("
            INSERT IGNORE INTO `{$PREFIX}rol_enlace` (pid, criatura, nivel, usos, pp_gastado, updated_at)
            VALUES ({$pid}, '{$criatura_esc}', 1, 0, 0, NOW())
        ");
        
        // Registrar Renombre inicial (0 puntos)
        $db->query("
            INSERT IGNORE INTO `{$PREFIX}rol_renombre` (pid, puntos, last_update)
            VALUES ({$pid}, 0, " . time() . ")
        ");
        
        $count++;
    }
}

echo "  [OK] Se han inicializado {$count} personajes.\n";
echo "=== MIGRACIÓN COMPLETADA ===\n";
$db->close();
