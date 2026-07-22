<?php
/**
 * One Piece: Eternal · Migración de Catálogos (mybb_rol_*)
 * ---------------------------------------------------------------
 * Crea las tablas de los catálogos gestionables por staff que antes vivían
 * como datos mockup dentro de los .php públicos:
 *   - rol_tienda_items       (Bazar Pirata)
 *   - rol_tripulaciones      (Tripulaciones)
 *   - rol_akuma              (Biblioteca · Akuma no Mi)
 *   - rol_bestiario          (Biblioteca · Bestiario)
 *   - rol_estilos            (Biblioteca · Estilos de lucha)
 * Y la tabla que registra qué personaje ha "cogido" una misión:
 *   - rol_mv_mision_asignaciones
 *
 * Idempotente (CREATE TABLE IF NOT EXISTS). Cada catálogo se siembra con UN
 * único ejemplo bien estructurado (solo si la tabla está vacía) para que el
 * staff vea el formato; el resto se puebla desde gestionar-catalogos.php.
 *
 * Ejecutar:  php scripts/migrate-catalogos.php
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

/** Siembra una fila solo si la tabla está vacía. */
function seed_if_empty(mysqli $db, string $table, string $insertSql): void
{
    $r = $db->query("SELECT COUNT(*) AS c FROM `{$table}`");
    $row = $r ? $r->fetch_assoc() : array('c' => 1);
    if ((int)$row['c'] > 0) {
        echo "  [skip] seed {$table} (ya tiene datos)\n";
        return;
    }
    if ($db->query($insertSql) === false) {
        fwrite(STDERR, "  [ERROR] seed {$table}: " . $db->error . "\n");
        exit(1);
    }
    echo "  [OK] seed {$table} (1 ejemplo)\n";
}

$now = time();

echo "=== Migración catálogos (mybb_rol_*) ===\n";

// ─────────────────────────────────────────────────────────────
// rol_tienda_items — Bazar Pirata
// ─────────────────────────────────────────────────────────────
run($db, 'rol_tienda_items', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_tienda_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tienda VARCHAR(40) NOT NULL DEFAULT 'general' COMMENT 'armeria|astilleros|general|mercado_negro',
    categoria VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'armas|armaduras|barcos|piezas|consumibles|mejoras|especiales',
    nombre VARCHAR(160) NOT NULL,
    resumen VARCHAR(255) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    precio BIGINT UNSIGNED NOT NULL DEFAULT 0,
    detalles JSON NULL COMMENT 'lista de \"Clave: Valor\"',
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tienda (tienda),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// rol_tripulaciones — Tripulaciones
// ─────────────────────────────────────────────────────────────
run($db, 'rol_tripulaciones', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_tripulaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    faccion VARCHAR(40) NOT NULL DEFAULT 'pirata',
    capitan VARCHAR(160) NOT NULL DEFAULT '',
    lema VARCHAR(255) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    nivel INT NOT NULL DEFAULT 0,
    miembros INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_faccion (faccion),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// rol_akuma — Biblioteca · Frutas del Diablo
// ─────────────────────────────────────────────────────────────
run($db, 'rol_akuma', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_akuma (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'paramecia' COMMENT 'paramecia|zoa|logia',
    rareza VARCHAR(20) NOT NULL DEFAULT 'Común' COMMENT 'Común|Raro|Épico|Legendario',
    descripcion TEXT NULL,
    usuario VARCHAR(160) NOT NULL DEFAULT '',
    debilidad VARCHAR(255) NOT NULL DEFAULT '',
    despertar TEXT NULL COMMENT 'despertar de la fruta',
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tipo (tipo),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// rol_bestiario — Biblioteca · Bestiario
// ─────────────────────────────────────────────────────────────
run($db, 'rol_bestiario', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_bestiario (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    rareza VARCHAR(20) NOT NULL DEFAULT 'Común' COMMENT 'Común|Raro|Épico|Legendario',
    habitat VARCHAR(160) NOT NULL DEFAULT '',
    peligro VARCHAR(40) NOT NULL DEFAULT '' COMMENT 'Bajo|Moderado|Alto|Extremo',
    tamano VARCHAR(80) NOT NULL DEFAULT '',
    dieta VARCHAR(80) NOT NULL DEFAULT '',
    descripcion TEXT NULL,
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_rareza (rareza),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// rol_estilos — Biblioteca · Estilos de lucha
// ─────────────────────────────────────────────────────────────
run($db, 'rol_estilos', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_estilos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(160) NOT NULL,
    categoria VARCHAR(40) NOT NULL DEFAULT 'Combate' COMMENT 'Combate|Defensa|Percepción|Apoyo',
    dificultad VARCHAR(40) NOT NULL DEFAULT 'Media' COMMENT 'Baja|Media|Alta|Legendaria',
    descripcion TEXT NULL,
    usuarios VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'practicantes conocidos',
    tecnicas TEXT NULL COMMENT 'técnicas destacadas',
    imagen VARCHAR(255) NOT NULL DEFAULT '',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_categoria (categoria),
    KEY idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// rol_mv_mision_asignaciones — quién ha cogido cada misión
// ─────────────────────────────────────────────────────────────
run($db, 'rol_mv_mision_asignaciones', "
CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_mision_asignaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mision_id INT UNSIGNED NOT NULL,
    pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje líder que acepta',
    uid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'cuenta MyBB del líder',
    modalidad VARCHAR(20) NOT NULL DEFAULT 'solo' COMMENT 'solo|grupo',
    companeros JSON NULL COMMENT 'array de pids acompañantes (grupo)',
    estado VARCHAR(20) NOT NULL DEFAULT 'en_proceso',
    dateline INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_mision (mision_id),
    KEY idx_pid (pid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─────────────────────────────────────────────────────────────
// Semillas (1 ejemplo por catálogo, solo si vacío)
// ─────────────────────────────────────────────────────────────
seed_if_empty($db, "{$PREFIX}rol_tienda_items", "
INSERT INTO {$PREFIX}rol_tienda_items
    (tienda, categoria, nombre, resumen, descripcion, precio, detalles, imagen, activo, orden, dateline)
VALUES
    ('armeria', 'armas', 'Espada de acero',
     'Espada recta de acero forjado.',
     'Forjada en los hornos de Sunacma por el herrero Dorn. Acero al carbono con filo tratado. No es legendaria, pero jamás te fallará.',
     45000,
     JSON_ARRAY('Daño base: 1d8', 'Peso: 2.5 kg', 'Tipo: Corte', 'Durabilidad: 60/60'),
     '', 1, 0, {$now});
");

seed_if_empty($db, "{$PREFIX}rol_tripulaciones", "
INSERT INTO {$PREFIX}rol_tripulaciones
    (nombre, faccion, capitan, lema, descripcion, nivel, miembros, imagen, activo, orden, dateline)
VALUES
    ('Sombrero de Paja', 'pirata', 'Monkey D. Luffy',
     'El rey de los piratas',
     'Una tripulación libre que navega hacia el sueño de su capitán. Pequeña en número, enorme en voluntad.',
     980, 10, '', 1, 0, {$now});
");

seed_if_empty($db, "{$PREFIX}rol_akuma", "
INSERT INTO {$PREFIX}rol_akuma
    (nombre, tipo, rareza, descripcion, usuario, debilidad, despertar, imagen, activo, orden, dateline)
VALUES
    ('Gomu Gomu no Mi', 'paramecia', 'Legendario',
     'Convierte el cuerpo del usuario en goma. Permite estirar, comprimir y resistir impactos contundentes y eléctricos.',
     'Monkey D. Luffy',
     'Agua de mar y kairoseki, como toda fruta del diablo.',
     'Al despertar, el usuario puede transmitir las propiedades de la goma a su entorno inmediato.',
     '', 1, 0, {$now});
");

seed_if_empty($db, "{$PREFIX}rol_bestiario", "
INSERT INTO {$PREFIX}rol_bestiario
    (nombre, rareza, habitat, peligro, tamano, dieta, descripcion, imagen, activo, orden, dateline)
VALUES
    ('Kraken del Abismo', 'Épico', 'Fosa de los Malditos', 'Extremo', '200 m', 'Carnívoro',
     'Pulpo colosal de las profundidades. Sus tentáculos pueden hundir un barco de guerra en segundos.',
     '', 1, 0, {$now});
");

seed_if_empty($db, "{$PREFIX}rol_estilos", "
INSERT INTO {$PREFIX}rol_estilos
    (nombre, categoria, dificultad, descripcion, usuarios, tecnicas, imagen, activo, orden, dateline)
VALUES
    ('Rokushiki', 'Combate', 'Alta',
     'Las seis técnicas del cuartel Marine. Dominio corporal absoluto llevado al límite físico humano.',
     'Rob Lucci, Kaku, Jabra',
     'Shave · Moonwalk · Geppo · Rankyaku · Kami-e · Tekkai',
     '', 1, 0, {$now});
");

echo "\n--- Verificación ---\n";
$check = $db->query("SHOW TABLES LIKE '{$PREFIX}rol_%'");
while ($t = $check->fetch_array()) {
    echo "  tabla: {$t[0]}\n";
}

echo "\n=== DONE ===\n";
$db->close();
