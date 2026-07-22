<?php
/**
 * Seed — Estado "Ebrio" (catálogo de estados de combate).
 *
 * Idempotente: INSERT IGNORE por clave primaria (estado_key).
 * Contexto: Factor Linaje (FACTOR-LINAJE.md). Ebrio es negativo para todas las
 * razas salvo Oni, que lo convierte en un bonus vía su rasgo "Sangre de Ogro"
 * (ver ope_rol_razas() en inc/ope_rol_data.php).
 *
 * Ejecutar:
 *   php scripts/seed-estado-ebrio.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

$tableCheck = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_estados'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    fwrite(STDERR, "La tabla {$PREFIX}rol_estados no existe. Ejecuta primero migrate-oleada2.php.\n");
    exit(1);
}

$stmt = $db->prepare("
    INSERT IGNORE INTO `{$PREFIX}rol_estados` (estado_key, nombre, efecto, duracion_default, tipo, disipable)
    VALUES ('ebrio', 'Ebrio', '-2 PER y -2 AGI efectivas. Requiere haber consumido alcohol de forma narrativa en la escena. Para los Oni (rasgo Sangre de Ogro), este estado se invierte: +4 FUE y +2 RES en su lugar, sin las penalizaciones estándar.', 5, 'negativo', 1)
");
$stmt->execute();
echo $stmt->affected_rows > 0 ? "  [seed] Ebrio (ebrio) insertado.\n" : "  [skip] Ebrio (ebrio) ya existía.\n";
$stmt->close();

echo "Listo.\n";
