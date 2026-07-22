<?php
/**
 * One Piece: Eternal — Migración e Importación Completa de Akuma no Mi a la BD
 *   php scripts/migrate-akuma-db-full.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';

function col_exists(mysqli $db, string $table, string $col): bool
{
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $r = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $r && $r->num_rows > 0;
}

function add_col(mysqli $db, string $table, string $col, string $def): void
{
    if (col_exists($db, $table, $col)) {
        return;
    }
    $db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
}

echo "=== Migración de Akuma no Mi a la Base de Datos ===\n";

$sql_create = "CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_akuma` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL DEFAULT '',
  `nombre` VARCHAR(160) NOT NULL,
  `tipo` VARCHAR(60) NOT NULL DEFAULT 'paramecia',
  `tier` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `secundario` VARCHAR(10) NOT NULL DEFAULT 'FUE',
  `estado` VARCHAR(20) NOT NULL DEFAULT 'libre',
  `ocupada_pid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID del personaje poseedor (0 = libre)',
  `usuario` VARCHAR(160) NOT NULL DEFAULT 'Nadie' COMMENT 'Nombre del PJ poseedor',
  `descripcion_breve` TEXT NULL,
  `efecto_general` TEXT NULL,
  `debilidad` TEXT NULL,
  `potencia_formula` VARCHAR(120) NOT NULL DEFAULT '',
  `caps_json` LONGTEXT NULL COMMENT 'Capacidades 01-08 y Pasivas en JSON',
  `notas_jugadores` TEXT NULL,
  `notas_staff` TEXT NULL,
  `imagen` VARCHAR(255) NOT NULL DEFAULT '',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_tier` (`tier`),
  KEY `idx_ocupada_pid` (`ocupada_pid`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($sql_create) === false) {
    echo "[FAIL] Error al crear la tabla rol_akuma: " . $db->error . "\n";
} else {
    echo "[OK] Tabla rol_akuma garantizada.\n";
}

// Alter column length if needed
$db->query("ALTER TABLE `{$PREFIX}rol_akuma` MODIFY COLUMN `tipo` VARCHAR(60) NOT NULL DEFAULT 'paramecia'");
add_col($db, "{$PREFIX}rol_akuma", 'slug', "VARCHAR(80) NOT NULL DEFAULT ''");
add_col($db, "{$PREFIX}rol_akuma", 'secundario', "VARCHAR(10) NOT NULL DEFAULT 'FUE'");
add_col($db, "{$PREFIX}rol_akuma", 'estado', "VARCHAR(20) NOT NULL DEFAULT 'libre'");
add_col($db, "{$PREFIX}rol_akuma", 'ocupada_pid', "INT UNSIGNED NOT NULL DEFAULT 0");
add_col($db, "{$PREFIX}rol_akuma", 'descripcion_breve', "TEXT NULL");
add_col($db, "{$PREFIX}rol_akuma", 'efecto_general', "TEXT NULL");
add_col($db, "{$PREFIX}rol_akuma", 'potencia_formula', "VARCHAR(120) NOT NULL DEFAULT ''");
add_col($db, "{$PREFIX}rol_akuma", 'caps_json', "LONGTEXT NULL");
add_col($db, "{$PREFIX}rol_akuma", 'notas_jugadores', "TEXT NULL");
add_col($db, "{$PREFIX}rol_akuma", 'notas_staff', "TEXT NULL");

// Leer el dataset JSON parsed de Akuma no Mi
$json_path = __DIR__ . '/../inc/ope_rol/catalogos/akuma_no_mi_db.json';
if (!file_exists($json_path)) {
    echo "[ERROR] No se encontró el archivo de datos JSON: {$json_path}\n";
    exit(1);
}

$fruits = json_decode(file_get_contents($json_path), true);
if (!is_array($fruits)) {
    echo "[ERROR] JSON inválido.\n";
    exit(1);
}

echo "Insertando/Actualizando " . count($fruits) . " Akuma no Mi en la BD...\n";

$inserted = 0;
$updated = 0;

$stmt_check = $db->prepare("SELECT id, ocupada_pid, usuario FROM `{$PREFIX}rol_akuma` WHERE slug = ? OR nombre = ? LIMIT 1");
$stmt_insert = $db->prepare("INSERT INTO `{$PREFIX}rol_akuma` (slug, nombre, tipo, tier, secundario, estado, ocupada_pid, usuario, descripcion_breve, efecto_general, debilidad, potencia_formula, caps_json, notas_jugadores, notas_staff, imagen, activo, orden, dateline) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
$stmt_update = $db->prepare("UPDATE `{$PREFIX}rol_akuma` SET slug=?, nombre=?, tipo=?, tier=?, secundario=?, descripcion_breve=?, efecto_general=?, debilidad=?, potencia_formula=?, caps_json=?, notas_jugadores=?, notas_staff=?, imagen=?, orden=? WHERE id=?");

$now = time();

foreach ($fruits as $f) {
    $slug = $f['slug'] ?? '';
    $nombre = $f['nombre'] ?? '';
    $tipo = mb_substr($f['tipo'] ?? 'paramecia', 0, 60, 'UTF-8');
    
    // Tier romano a entero
    $tier_str = $f['tier'] ?? 'I';
    $tier = 1;
    if ($tier_str === 'V') $tier = 5;
    elseif ($tier_str === 'IV') $tier = 4;
    elseif ($tier_str === 'III') $tier = 3;
    elseif ($tier_str === 'II') $tier = 2;
    
    $secundario = mb_substr($f['secundario'] ?? 'FUE', 0, 10, 'UTF-8');
    $desc = $f['descripcion_breve'] ?? '';
    $efecto = $f['efecto_general'] ?? '';
    $debilidad = $f['debilidad'] ?? '';
    $potencia = $f['potencia_formula'] ?? '';
    $caps_json = json_encode(array(
        'raw' => $f['capacidades_raw'] ?? '',
        'origen' => $f['origen'] ?? 'Canon'
    ), JSON_UNESCAPED_UNICODE);
    $notas_jug = $f['notas_jugadores'] ?? '';
    $notas_staff = $f['notas_staff'] ?? '';
    $imagen = $f['imagen'] ?? '';
    $orden = (int) ($f['orden'] ?? 0);
    
    // Check if exists
    $stmt_check->bind_param('ss', $slug, $nombre);
    $stmt_check->execute();
    $res = $stmt_check->get_result();
    
    if ($res && $res->num_rows > 0) {
        $existing = $res->fetch_assoc();
        $id = (int)$existing['id'];
        $stmt_update->bind_param('sssisssssssssii', $slug, $nombre, $tipo, $tier, $secundario, $desc, $efecto, $debilidad, $potencia, $caps_json, $notas_jug, $notas_staff, $imagen, $orden, $id);
        $stmt_update->execute();
        $updated++;
    } else {
        $estado = 'libre';
        $ocupada_pid = 0;
        $usuario = 'Nadie';
        $stmt_insert->bind_param('sssisissssssssssii', $slug, $nombre, $tipo, $tier, $secundario, $estado, $ocupada_pid, $usuario, $desc, $efecto, $debilidad, $potencia, $caps_json, $notas_jug, $notas_staff, $imagen, $orden, $now);
        $stmt_insert->execute();
        $inserted++;
    }
}

echo "=== MIGRACIÓN DE AKUMA NO MI COMPLETA ===\n";
echo "  [OK] Frutas insertadas: {$inserted}\n";
echo "  [OK] Frutas actualizadas: {$updated}\n";
echo "  [TOTAL]: " . ($inserted + $updated) . "\n";
$db->close();
