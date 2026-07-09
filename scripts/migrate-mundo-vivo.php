<?php
/**
 * Migracion: Sistema "Mundo Vivo" (AV-13).
 * Crea las tablas rol_mv_*, anade columnas de ubicacion a rol_personajes (NPCs),
 * siembra el tablero (8 zonas, 6 facciones, matriz de tension) y abre el ciclo del mes.
 *
 * Idempotente: se puede re-ejecutar sin duplicar nada.
 * Ejecutar: php scripts/migrate-mundo-vivo.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

echo "== Mundo Vivo: creando tablas ==\n";

// -- Ciclos (mes natural real) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_ciclos (
        ciclo_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        periodo CHAR(7) NOT NULL COMMENT 'YYYY-MM del mes natural',
        estado ENUM('abierto','prompt','preview','publicado','archivado') NOT NULL DEFAULT 'abierto',
        indicaciones MEDIUMTEXT NULL COMMENT 'texto libre del staff para el prompt de este mes',
        prompt LONGTEXT NULL COMMENT 'ultimo super-prompt generado',
        resultado_raw LONGTEXT NULL COMMENT 'resultado pegado de la IA',
        periodico_html LONGTEXT NULL,
        estado_json LONGTEXT NULL COMMENT 'snapshot del tablero al publicar',
        noticia_titulo VARCHAR(240) NOT NULL DEFAULT '',
        noticia_html LONGTEXT NULL,
        imagenes_json LONGTEXT NULL,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        published_at INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (ciclo_id),
        UNIQUE KEY uq_periodo (periodo),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Zonas (8 grandes regiones) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_zonas (
        slug VARCHAR(40) NOT NULL,
        nombre VARCHAR(80) NOT NULL,
        est TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Estabilidad 0-100',
        mar TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Presencia Marine 0-100',
        pir TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Actividad pirata 0-100',
        notas MEDIUMTEXT NULL,
        orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Facciones (6) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_facciones (
        slug VARCHAR(40) NOT NULL,
        nombre VARCHAR(80) NOT NULL,
        rep SMALLINT NOT NULL DEFAULT 0 COMMENT 'Reputacion -100..100',
        coh TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Cohesion 0-100',
        notas MEDIUMTEXT NULL,
        orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Matriz de tension (pares canonicos a|b) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_tension (
        par VARCHAR(90) NOT NULL COMMENT 'slugA|slugB en orden canonico',
        a_slug VARCHAR(40) NOT NULL,
        b_slug VARCHAR(40) NOT NULL,
        valor TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
        PRIMARY KEY (par)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Arcos abiertos --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_arcos (
        arco_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(200) NOT NULL,
        estado VARCHAR(40) NOT NULL DEFAULT 'Activo',
        zonas VARCHAR(240) NOT NULL DEFAULT '',
        facciones VARCHAR(240) NOT NULL DEFAULT '',
        descripcion MEDIUMTEXT NULL,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (arco_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Eventos notificados (trámite Notificar tema) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_eventos (
        evento_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ciclo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        pid INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'personaje que notifica',
        uid INT UNSIGNED NOT NULL DEFAULT 0,
        tid BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'thread id',
        enlace VARCHAR(400) NOT NULL DEFAULT '',
        titulo VARCHAR(300) NOT NULL DEFAULT '',
        resumen MEDIUMTEXT NULL,
        fid INT UNSIGNED NOT NULL DEFAULT 0,
        zona_slug VARCHAR(40) NOT NULL DEFAULT '',
        estado ENUM('pendiente','incluido','descartado') NOT NULL DEFAULT 'pendiente',
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (evento_id),
        KEY idx_ciclo (ciclo_id),
        KEY idx_estado (estado),
        KEY idx_tid (tid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Misiones (v1: alta manual) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_misiones (
        mision_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ciclo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        titulo VARCHAR(300) NOT NULL DEFAULT '',
        resumen MEDIUMTEXT NULL,
        zona_slug VARCHAR(40) NOT NULL DEFAULT '',
        facciones VARCHAR(240) NOT NULL DEFAULT '',
        enlace VARCHAR(400) NOT NULL DEFAULT '',
        estado ENUM('en_curso','completada','fallida') NOT NULL DEFAULT 'en_curso',
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (mision_id),
        KEY idx_ciclo (ciclo_id),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Historial de NPCs menores (sin ficha) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_npc_menores (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ciclo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        nombre VARCHAR(200) NOT NULL DEFAULT '',
        descripcion MEDIUMTEXT NULL,
        zona_slug VARCHAR(40) NOT NULL DEFAULT '',
        estado VARCHAR(60) NOT NULL DEFAULT '',
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_ciclo (ciclo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// -- Noticias del index (feed rotatorio) --
$db->write_query("
    CREATE TABLE IF NOT EXISTS {$PREFIX}rol_mv_noticias (
        noticia_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        titulo VARCHAR(240) NOT NULL DEFAULT '',
        resumen VARCHAR(400) NOT NULL DEFAULT '',
        cuerpo_html LONGTEXT NULL,
        origen ENUM('mundo_vivo','manual') NOT NULL DEFAULT 'manual',
        ciclo_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        activa TINYINT(1) NOT NULL DEFAULT 1,
        orden INT NOT NULL DEFAULT 0,
        uid_autor INT UNSIGNED NOT NULL DEFAULT 0,
        dateline INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (noticia_id),
        KEY idx_activa (activa, orden),
        KEY idx_dateline (dateline)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "== Columnas de ubicacion en rol_personajes (NPCs) ==\n";
$npc_cols = array(
    'mundo_zona'      => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN mundo_zona VARCHAR(40) NOT NULL DEFAULT ''",
    'mundo_ubic'      => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN mundo_ubic VARCHAR(160) NOT NULL DEFAULT ''",
    'mundo_accion'    => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN mundo_accion VARCHAR(255) NOT NULL DEFAULT ''",
    'mundo_estado_np' => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN mundo_estado_np VARCHAR(40) NOT NULL DEFAULT ''",
);
foreach ($npc_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_personajes')) {
        $db->write_query($sql);
        echo "  [+] rol_personajes.$col\n";
    } else {
        echo "  [=] rol_personajes.$col ya existe\n";
    }
}

echo "== Sembrando tablero ==\n";

// Zonas: [slug, nombre, EST, MAR, PIR, orden]
$zonas = array(
    array('east-blue',  'East Blue',  60, 50, 35, 1),
    array('west-blue',  'West Blue',  50, 45, 30, 2),
    array('north-blue', 'North Blue', 45, 55, 40, 3),
    array('south-blue', 'South Blue', 40, 35, 55, 4),
    array('calm-belt',  'Calm Belt',  30, 10, 20, 5),
    array('red-line',   'Red Line',   70, 80, 10, 6),
    array('paraiso',    'Paraíso',    40, 55, 50, 7),
    array('new-world',  'New World',  30, 25, 80, 8),
);
foreach ($zonas as $z) {
    $exists = $db->fetch_field($db->simple_select('rol_mv_zonas', 'COUNT(*) c', "slug='" . $db->escape_string($z[0]) . "'"), 'c');
    if ((int)$exists === 0) {
        $db->insert_query('rol_mv_zonas', array(
            'slug' => $db->escape_string($z[0]), 'nombre' => $db->escape_string($z[1]),
            'est' => (int)$z[2], 'mar' => (int)$z[3], 'pir' => (int)$z[4],
            'notas' => '', 'orden' => (int)$z[5],
        ));
        echo "  [+] zona {$z[1]}\n";
    }
}

// Facciones: [slug, nombre, REP, COH, orden]
$facciones = array(
    array('marine',           'Marines',          40, 80, 1),
    array('pirata',           'Piratas',         -10, 40, 2),
    array('revolucionario',   'Revolucionarios',  20, 85, 3),
    array('gobierno',         'Gobierno Mundial',-10, 70, 4),
    array('cazarrecompensas', 'Cazarrecompensas', 10, 30, 5),
    array('civil',            'Civiles',          50, 50, 6),
);
foreach ($facciones as $f) {
    $exists = $db->fetch_field($db->simple_select('rol_mv_facciones', 'COUNT(*) c', "slug='" . $db->escape_string($f[0]) . "'"), 'c');
    if ((int)$exists === 0) {
        $db->insert_query('rol_mv_facciones', array(
            'slug' => $db->escape_string($f[0]), 'nombre' => $db->escape_string($f[1]),
            'rep' => (int)$f[2], 'coh' => (int)$f[3], 'notas' => '', 'orden' => (int)$f[4],
        ));
        echo "  [+] faccion {$f[1]}\n";
    }
}

// Matriz de tension (orden canonico segun array de facciones)
$orderFac = array('marine', 'pirata', 'revolucionario', 'gobierno', 'cazarrecompensas', 'civil');
$tension = array(
    'marine|pirata' => 75, 'marine|revolucionario' => 60, 'marine|gobierno' => 15,
    'marine|cazarrecompensas' => 25, 'marine|civil' => 20,
    'pirata|revolucionario' => 40, 'pirata|gobierno' => 70, 'pirata|cazarrecompensas' => 65, 'pirata|civil' => 45,
    'revolucionario|gobierno' => 85, 'revolucionario|cazarrecompensas' => 30, 'revolucionario|civil' => 25,
    'gobierno|cazarrecompensas' => 30, 'gobierno|civil' => 35,
    'cazarrecompensas|civil' => 30,
);
for ($i = 0; $i < count($orderFac); $i++) {
    for ($j = $i + 1; $j < count($orderFac); $j++) {
        $a = $orderFac[$i]; $b = $orderFac[$j];
        $par = $a . '|' . $b;
        $val = isset($tension[$par]) ? (int)$tension[$par] : 0;
        $exists = $db->fetch_field($db->simple_select('rol_mv_tension', 'COUNT(*) c', "par='" . $db->escape_string($par) . "'"), 'c');
        if ((int)$exists === 0) {
            $db->insert_query('rol_mv_tension', array(
                'par' => $db->escape_string($par), 'a_slug' => $db->escape_string($a),
                'b_slug' => $db->escape_string($b), 'valor' => $val,
            ));
        }
    }
}
echo "  [+] matriz de tension sembrada\n";

echo "== Abriendo ciclo del mes ==\n";
$periodo = date('Y-m');
$exists = $db->fetch_field($db->simple_select('rol_mv_ciclos', 'COUNT(*) c', "periodo='" . $db->escape_string($periodo) . "'"), 'c');
if ((int)$exists === 0) {
    $db->insert_query('rol_mv_ciclos', array(
        'periodo' => $db->escape_string($periodo),
        'estado' => 'abierto',
        'indicaciones' => '',
        'dateline' => (int)TIME_NOW,
    ));
    echo "  [+] ciclo $periodo abierto\n";
} else {
    echo "  [=] ciclo $periodo ya existe\n";
}

echo "\nMigracion Mundo Vivo completada.\n";
