<?php
/**
 * Migracion v3: ficha narrativa de personajes y clasificacion IA.
 *  - Zonas: nuevas metricas cli (clima), riq (riqueza), inf (inframundo), ten (tension).
 *  - Facciones: nuevas metricas pol (politica), alc (alcance).
 *  - Ciclos: threads_json, nav_resumen.
 *  - Eventos: tipo_suceso (S-01 a S-12), pe_estimado (1-10).
 *  - Personajes (NPCs): datos_publicos (JSON visible), datos_internos (JSON staff/IA).
 *
 * Idempotente: re-ejecutable.
 * Ejecutar: php scripts/migrate-mundo-vivo-v3.php
 */

define('IN_MYBB', 1);
require_once dirname(__DIR__) . '/inc/init.php';

$PREFIX = TABLE_PREFIX;

echo "== v3: nuevas columnas en zonas ==\n";
$zona_cols = array(
    'cli' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN cli TINYINT UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Clima 0-100' AFTER pel",
    'riq' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN riq TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Riqueza 0-100' AFTER cli",
    'inf' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN inf TINYINT UNSIGNED NOT NULL DEFAULT 20 COMMENT 'Influencia Inframundo 0-100' AFTER riq",
    'ten' => "ALTER TABLE {$PREFIX}rol_mv_zonas ADD COLUMN ten TINYINT UNSIGNED NOT NULL DEFAULT 20 COMMENT 'Tension General 0-100' AFTER inf",
);
foreach ($zona_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_zonas')) { $db->write_query($sql); echo "  [+] rol_mv_zonas.$col\n"; }
    else { echo "  [=] rol_mv_zonas.$col\n"; }
}

echo "== v3: nuevas columnas en facciones ==\n";
$fac_cols = array(
    'pol' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN pol TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Influencia Politica 0-100' AFTER mor",
    'alc' => "ALTER TABLE {$PREFIX}rol_mv_facciones ADD COLUMN alc TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Alcance 0-100' AFTER pol",
);
foreach ($fac_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_facciones')) { $db->write_query($sql); echo "  [+] rol_mv_facciones.$col\n"; }
    else { echo "  [=] rol_mv_facciones.$col\n"; }
}

echo "== v3: nuevas columnas en ciclos ==\n";
$ciclo_cols = array(
    'threads_json' => "ALTER TABLE {$PREFIX}rol_mv_ciclos ADD COLUMN threads_json LONGTEXT NULL DEFAULT NULL COMMENT 'Array de hilos narrativos (JSON)' AFTER imagenes_json",
    'nav_resumen'  => "ALTER TABLE {$PREFIX}rol_mv_ciclos ADD COLUMN nav_resumen TEXT NULL DEFAULT NULL COMMENT 'Resumen de viajes/navegacion del ciclo' AFTER threads_json",
);
foreach ($ciclo_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_ciclos')) { $db->write_query($sql); echo "  [+] rol_mv_ciclos.$col\n"; }
    else { echo "  [=] rol_mv_ciclos.$col\n"; }
}

echo "== v3: nuevas columnas en eventos ==\n";
$evento_cols = array(
    'tipo_suceso' => "ALTER TABLE {$PREFIX}rol_mv_eventos ADD COLUMN tipo_suceso VARCHAR(20) DEFAULT NULL COMMENT 'S-01 a S-12 (clasificacion de la IA)' AFTER dateline",
    'pe_estimado' => "ALTER TABLE {$PREFIX}rol_mv_eventos ADD COLUMN pe_estimado TINYINT UNSIGNED DEFAULT NULL COMMENT 'Peso del Evento 1-10 (estimado por IA)' AFTER tipo_suceso",
);
foreach ($evento_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_mv_eventos')) { $db->write_query($sql); echo "  [+] rol_mv_eventos.$col\n"; }
    else { echo "  [=] rol_mv_eventos.$col\n"; }
}

echo "== v3: nuevas columnas en personajes ==\n";
$pers_cols = array(
    'datos_publicos' => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN datos_publicos TEXT NULL DEFAULT NULL COMMENT 'JSON publico: titulos, descripcion, historia, relaciones, ubicacion visible' AFTER mundo_estado_np",
    'datos_internos' => "ALTER TABLE {$PREFIX}rol_personajes ADD COLUMN datos_internos TEXT NULL DEFAULT NULL COMMENT 'JSON interno (solo staff/IA): personalidad (6 ejes), metas, tracking' AFTER datos_publicos",
);
foreach ($pers_cols as $col => $sql) {
    if (!$db->field_exists($col, 'rol_personajes')) { $db->write_query($sql); echo "  [+] rol_personajes.$col\n"; }
    else { echo "  [=] rol_personajes.$col\n"; }
}

echo "== v3: seed de metricas nuevas en zonas (solo si estan a default) ==\n";
// Orden: slug, cli, pel, riq, civ, mar, pir, rev, inf, ten  (del brief)
// Pero solo nos interesan las nuevas columnas: cli, riq, inf, ten
// Actualizar solo cuando el valor actual es el DEFAULT (60 para cli, 50 para riq, 20 para inf, 20 para ten)
$zseed = array(
    array('east-blue',  65, 20, 55, 60, 50, 35, 15, 15, 25),
    array('west-blue',  60, 30, 50, 50, 45, 30, 30, 25, 30),
    array('north-blue', 55, 35, 55, 60, 55, 40, 20, 20, 30),
    array('south-blue', 60, 45, 40, 35, 35, 55, 35, 35, 40),
    array('calm-belt',  80, 95, 20, 25, 10, 20, 5,  10, 15),
    array('red-line',   50, 40, 70, 80, 80, 10, 10, 15, 20),
    array('paraiso',    55, 55, 60, 45, 55, 50, 25, 30, 45),
    array('new-world',  35, 85, 55, 25, 25, 80, 40, 45, 55),
);
foreach ($zseed as $z) {
    $slug = $db->escape_string($z[0]);
    $cli = (int)$z[1];
    $riq = (int)$z[3];
    $inf = (int)$z[8];
    $ten = (int)$z[9];
    $db->write_query("UPDATE {$PREFIX}rol_mv_zonas SET cli=$cli, riq=$riq, inf=$inf, ten=$ten WHERE slug='$slug' AND (cli=60 OR riq=50 OR inf=20 OR ten=20)");
}
echo "  [+] metricas de zona sembradas (solo donde estan a default)\n";

echo "== v3: seed de metricas nuevas en facciones (solo si estan a default) ==\n";
$fseed = array(
    array('marine',           80, 80),
    array('pirata',           25, 60),
    array('revolucionario',   45, 40),
    array('gobierno',         95, 85),
    array('cazarrecompensas', 20, 35),
    array('civil',            30, 50),
);
foreach ($fseed as $f) {
    $slug = $db->escape_string($f[0]);
    $pol = (int)$f[1];
    $alc = (int)$f[2];
    $db->write_query("UPDATE {$PREFIX}rol_mv_facciones SET pol=$pol, alc=$alc WHERE slug='$slug' AND (pol=50 OR alc=50)");
}
echo "  [+] seed de facciones sembrada (solo donde estan a default)\n";

echo "== v3: abriendo ciclo del mes ==\n";
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

echo "\nMigracion v3 completada.\n";