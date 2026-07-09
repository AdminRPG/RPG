<?php
/**
 * Migracion v2: amplia el Tablero de Mundo Vivo.
 *  - Zonas: nuevas metricas rev (revolucion), eco (prosperidad), civ (orden civil), pel (peligro).
 *  - Facciones: nuevas metricas mil (militar), inf (influencia), eco (economia), mor (moral).
 *  - Tension: pasa a ser POR MAR (columna zona_slug) + notas. Se recrea y resiembra por zona.
 *
 * Idempotente: re-ejecutable. Solo recrea la tabla de tension si aun no tiene zona_slug.
 * Ejecutar: php scripts/migrate-mundo-vivo-v2.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

echo "== v2: metricas de zona ==\n";
$zona_cols = array(
    'rev' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN rev TINYINT UNSIGNED NOT NULL DEFAULT 20 COMMENT 'Influencia revolucionaria 0-100' AFTER pir",
    'eco' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN eco TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Prosperidad economica 0-100' AFTER rev",
    'civ' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN civ TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Orden civil 0-100' AFTER eco",
    'pel' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN pel TINYINT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Nivel de peligro 0-100' AFTER civ",
);
foreach ($zona_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_zonas')) { $db->write_query($sql); echo "  [+] rol_mv_zonas.$col\n"; }
    else { echo "  [=] rol_mv_zonas.$col\n"; }
}

echo "== v2: metricas de faccion ==\n";
$fac_cols = array(
    'mil' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN mil TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Poder militar 0-100' AFTER coh",
    'inf' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN inf TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Influencia politica 0-100' AFTER mil",
    'eco' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN eco TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Recursos economicos 0-100' AFTER inf",
    'mor' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN mor TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Moral 0-100' AFTER eco",
);
foreach ($fac_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_facciones')) { $db->write_query($sql); echo "  [+] rol_mv_facciones.$col\n"; }
    else { echo "  [=] rol_mv_facciones.$col\n"; }
}

echo "== v2: seed de metricas nuevas (solo si estan a default) ==\n";
// Zonas: [slug, rev, eco, civ, pel]
$zseed = array(
    array('east-blue', 15, 55, 60, 20),
    array('west-blue', 30, 50, 50, 30),
    array('north-blue', 20, 55, 60, 35),
    array('south-blue', 35, 40, 35, 45),
    array('calm-belt', 5, 20, 25, 95),
    array('red-line', 10, 70, 80, 40),
    array('paraiso', 25, 60, 45, 55),
    array('new-world', 40, 55, 25, 85),
);
foreach ($zseed as $z) {
    $db->update_query('rol_mv_zonas', array('rev' => (int)$z[1], 'eco' => (int)$z[2], 'civ' => (int)$z[3], 'pel' => (int)$z[4]), "slug='" . $db->escape_string($z[0]) . "'");
}
echo "  [+] metricas de zona sembradas\n";

// Facciones: [slug, mil, inf, eco, mor]
$fseed = array(
    array('marine', 85, 80, 75, 70),
    array('pirata', 55, 25, 45, 65),
    array('revolucionario', 60, 45, 40, 90),
    array('gobierno', 80, 95, 85, 60),
    array('cazarrecompensas', 45, 20, 40, 50),
    array('civil', 15, 30, 50, 55),
);
foreach ($fseed as $f) {
    $db->update_query('rol_mv_facciones', array('mil' => (int)$f[1], 'inf' => (int)$f[2], 'eco' => (int)$f[3], 'mor' => (int)$f[4]), "slug='" . $db->escape_string($f[0]) . "'");
}
echo "  [+] metricas de faccion sembradas\n";

echo "== v2: tension POR MAR ==\n";
if (!$db->field_exists('zona_slug', 'rol_mv_tension')) {
    $db->write_query("DROP TABLE IF EXISTS {$PREFIX}rol_mv_tension");
    $db->write_query("
        CREATE TABLE {$PREFIX}rol_mv_tension (
            zona_slug VARCHAR(40) NOT NULL,
            par VARCHAR(90) NOT NULL COMMENT 'slugA|slugB canonico',
            a_slug VARCHAR(40) NOT NULL,
            b_slug VARCHAR(40) NOT NULL,
            valor TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100',
            notas MEDIUMTEXT NULL COMMENT 'por que esa tension en este mar',
            PRIMARY KEY (zona_slug, par),
            KEY idx_zona (zona_slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $orderFac = array('marine', 'pirata', 'revolucionario', 'gobierno', 'cazarrecompensas', 'civil');
    $baseTension = array(
        'marine|pirata' => 75, 'marine|revolucionario' => 60, 'marine|gobierno' => 15,
        'marine|cazarrecompensas' => 25, 'marine|civil' => 20,
        'pirata|revolucionario' => 40, 'pirata|gobierno' => 70, 'pirata|cazarrecompensas' => 65, 'pirata|civil' => 45,
        'revolucionario|gobierno' => 85, 'revolucionario|cazarrecompensas' => 30, 'revolucionario|civil' => 25,
        'gobierno|cazarrecompensas' => 30, 'gobierno|civil' => 35,
        'cazarrecompensas|civil' => 30,
    );
    $zonas = array();
    $zq = $db->simple_select('rol_mv_zonas', 'slug');
    while ($zr = $db->fetch_array($zq)) { $zonas[] = $zr['slug']; }

    $n = 0;
    foreach ($zonas as $zslug) {
        for ($i = 0; $i < count($orderFac); $i++) {
            for ($j = $i + 1; $j < count($orderFac); $j++) {
                $a = $orderFac[$i]; $b = $orderFac[$j]; $par = $a . '|' . $b;
                $db->insert_query('rol_mv_tension', array(
                    'zona_slug' => $db->escape_string($zslug),
                    'par' => $db->escape_string($par),
                    'a_slug' => $db->escape_string($a),
                    'b_slug' => $db->escape_string($b),
                    'valor' => (int)($baseTension[$par] ?? 0),
                    'notas' => '',
                ));
                $n++;
            }
        }
    }
    echo "  [+] tension recreada por mar ($n filas)\n";
} else {
    echo "  [=] tension ya es por mar\n";
}

echo "\nMigracion v2 completada.\n";
