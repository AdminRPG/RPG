<?php
/**
 * Migración Oleada 2 — Columnas de combate + Estados.
 *
 * Idempotente: comprueba existencia antes de alterar/crear.
 *
 * Qué hace:
 *   1. Añade pv_max, en_max, pa_por_turno a rol_personajes.
 *   2. Crea tabla rol_estados (catálogo de condiciones).
 *
 * Ejecutar:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" ^
 *     "C:\Users\Fgonz\Documents\Proyectos\I-Forge-RPG\scripts\migrate-oleada2.php"
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    fwrite(STDERR, "DB connection error: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$PREFIX = 'mybb_';

function col_exists(mysqli $db, string $table, string $col): bool
{
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $res = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $res && $res->num_rows > 0;
}

function add_col(mysqli $db, string $table, string $col, string $definition): void
{
    if (col_exists($db, $table, $col)) {
        echo "  [skip] {$table}.{$col} ya existe\n";
        return;
    }
    if ($db->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}") === false) {
        fwrite(STDERR, "  [ERROR] {$table}.{$col}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$table}.{$col} añadida\n";
}

function run(mysqli $db, string $sql, string $label): void
{
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$label}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] {$label}\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. Columnas de combate en rol_personajes
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 1. COLUMNAS DE COMBATE ===\n";

add_col($db, "{$PREFIX}rol_personajes", 'pv_max',
    "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntos de Vida máximos: (FUE+VIG)*5 + (VOL+CON)*2'");
add_col($db, "{$PREFIX}rol_personajes", 'en_max',
    "INT UNSIGNED NOT NULL DEFAULT 20 COMMENT 'Energía máxima según rango del personaje'");
add_col($db, "{$PREFIX}rol_personajes", 'pa_por_turno',
    "TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'PA por turno: AGI + max(INT,ING,CAR) + bono_rango'");

// ═══════════════════════════════════════════════════════════════════════════
// 2. Tabla de Estados (condiciones de combate)
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 2. TABLA DE ESTADOS ===\n";

run($db, "
    CREATE TABLE IF NOT EXISTS `{$PREFIX}rol_estados` (
        `estado_key`   VARCHAR(30) NOT NULL,
        `nombre`       VARCHAR(60) NOT NULL DEFAULT '',
        `efecto`       VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Descripción del efecto mecánico',
        `duracion_default` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Duración en rondas (0=instantáneo/permanente)',
        `tipo`         VARCHAR(20) NOT NULL DEFAULT 'negativo' COMMENT 'positivo|negativo|neutral',
        `disipable`    TINYINT(1) NOT NULL DEFAULT 1 COMMENT '¿Se puede quitar con medios normales?',
        PRIMARY KEY (`estado_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'rol_estados');

// ═══════════════════════════════════════════════════════════════════════════
// 3. Seed de estados (catálogo AV-01)
// ═══════════════════════════════════════════════════════════════════════════

echo "\n=== 3. SEED ESTADOS ===\n";

$estados = array(
    array('Aturdido',        '-1 PA en tu próximo post', 1, 'negativo', 1),
    array('Quemado',         '3 de daño al inicio de tu post', 3, 'negativo', 1),
    array('Envenenado',      '5 de daño al inicio de tu post. Hasta curación.', 0, 'negativo', 1),
    array('Paralizado',      'No puedes moverte (0 PA para movimiento)', 2, 'negativo', 1),
    array('Confuso',         'Tus cartas cuestan +1 PA', 2, 'negativo', 1),
    array('Fortalecido',     '+2 al daño de tus cartas', 3, 'positivo', 1),
    array('Protegido',       '-5 al daño que recibes', 3, 'positivo', 1),
    array('Empapado',        '-25% stats efectivos por nivel de inmersión. Hasta secarse.', 0, 'negativo', 1),
    array('Anulado',         'Fruta desactivada. Stats efectivos al 50%. Mientras dure el contacto.', 0, 'negativo', 1),
    array('Cegado',          '-3 a PER efectiva. Ataques con -4.', 2, 'negativo', 1),
    array('Ensordecido',     '-2 a REF. No puedes oír órdenes.', 2, 'negativo', 1),
    array('Sangrado',        '2 de daño al inicio de tu post', 4, 'negativo', 1),
    array('Congelado',       '-2 PA, -50% AGI', 2, 'negativo', 1),
    array('Electrocutado',   '-1 PA, no puedes usar armas metálicas', 1, 'negativo', 1),
    array('Derribado',       'En el suelo. -2 REF. Necesitas 1 PA para levantarte.', 0, 'negativo', 1),
    array('Inmovilizado',    'No puedes moverte del sitio.', 2, 'negativo', 1),
    array('Silenciado',      'No puedes hablar ni activar cartas con componente vocal.', 2, 'negativo', 1),
    array('Marcado',         'El enemigo que te marcó tiene +2 a atacarte.', 5, 'negativo', 1),
    array('Inspirado',       '+1 a todas las tiradas.', 1, 'positivo', 1),
);

$stmt = $db->prepare("
    INSERT IGNORE INTO `{$PREFIX}rol_estados` (estado_key, nombre, efecto, duracion_default, tipo, disipable)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($estados as $e) {
    $key = strtolower(str_replace(array(' ', 'á','é','í','ó','ú'), array('_','a','e','i','o','u'), $e[0]));
    $stmt->bind_param('sssisi', $key, $e[0], $e[1], $e[2], $e[3], $e[4]);
    $stmt->execute();
    echo "  [seed] {$e[0]} ({$key})\n";
}
$stmt->close();

echo "\n=== DONE ===\n";
$db->close();
