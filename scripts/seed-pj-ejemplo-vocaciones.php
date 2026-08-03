<?php
/**
 * One Piece: Eternal · Seed de Ejemplo con Clases/Oficios v4.
 *   php scripts/seed-pj-ejemplo-vocaciones.php
 *
 * Crea o actualiza 2 personajes de prueba para verificar el nuevo modelo:
 *   1. Admin Super (Luchador, Cocinero, Guantelete)
 *   2. Narrador Oficial (Estratega, Navegante + Arqueólogo, Mapa táctico)
 */
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';
require_once __DIR__ . '/../inc/ope_rol_data.php';

echo "=== Seed PJ de Ejemplo (Clases / Oficios v4) ===\n\n";

$pjs = array(
    array(
        'uid' => 1,
        'nombre' => 'Admin Super',
        'slug' => 'admin-super',
        'estado' => 'aprobado',
        'activo' => 1,
        'nivel' => 10,
        'clase' => 'luchador',
        'oficios' => array('cocinero'),
        'arma' => 'guantelete',
        'raza' => 'humanos',
        'faccion' => 'marines',
        'elecciones' => array('10' => 'Demolición'),
        'arquetipo_clase' => '',
        'stats' => array('FUE'=>5,'RES'=>4,'AGI'=>3,'INT'=>2,'PER'=>2,'TEM'=>2,'VOL'=>2,'CAR'=>2),
        'historia' => 'Un combatiente formidable dedicado a proteger los mares y la justicia.',
    ),
    array(
        'uid' => 1,
        'nombre' => 'Narrador Oficial',
        'slug' => 'narrador-oficial',
        'estado' => 'aprobado',
        'activo' => 0,
        'nivel' => 30,
        'clase' => 'estratega',
        'oficios' => array('navegante', 'arqueologo'),
        'arma' => 'mapa_tactico',
        'raza' => 'skypeans',
        'faccion' => 'gobierno-mundial',
        'elecciones' => array('10' => 'Comando', '20' => 'Doble Orden'),
        'arquetipo_clase' => 'duelista',
        'stats' => array('FUE'=>3,'RES'=>3,'AGI'=>5,'INT'=>8,'PER'=>6,'TEM'=>4,'VOL'=>4,'CAR'=>5),
        'historia' => 'Mente maestra y estratega que observa las corrientes de la gran era.',
    ),
);

foreach ($pjs as $p) {
    $nombre_esc = $db->escape_string($p['nombre']);
    $slug_esc = $db->escape_string($p['slug']);
    
    // Check if exists
    $q = $db->simple_select('rol_personajes', 'pid', "slug = '{$slug_esc}' OR nombre = '{$nombre_esc}'", array('limit' => 1));
    if ($db->num_rows($q) > 0) {
        $pid = (int) $db->fetch_field($q, 'pid');
        echo "  [UPDATE] PJ '{$p['nombre']}' (pid: {$pid})\n";
    } else {
        $pid = 0;
    }

    $datos = array(
        'raza' => $p['raza'],
        'raza_mods' => array(),
        'pureza' => 'pura',
        'linaje2' => '',
        'linajes' => array($p['raza']),
        'clase' => $p['clase'],
        'oficios' => $p['oficios'],
        'arma' => $p['arma'],
        'faccion' => $p['faccion'],
        'factor_linaje' => array(),
        'virtudes_defectos' => array(),
        'dotes' => array(),
        'suma_dotes' => 0,
        'pl_total' => 0,
        'pack_equipo' => 'combatiente',
        'ps_asignados' => $p['stats'],
        'ps_total_usado' => array_sum($p['stats']),
        'cyborg' => false,
        'cyborg_slot' => '',
    );
    $inventario = array('pack_equipo' => 'combatiente');
    $economia = array('berries' => 2000, 'rupies' => 2000);
    $bio = array(
        'historia' => $p['historia'],
        'apodo' => '',
        'edad' => '25',
        'genero' => 'Desconocido',
        'pb' => 'Original',
        'desc_fisica' => 'Apariencia imponente.',
        'desc_psicologica' => 'Determinado.',
        'notas' => '',
    );

    $ins_pj = array(
        'uid' => $p['uid'],
        'nombre' => $nombre_esc,
        'slug' => $slug_esc,
        'estado' => $p['estado'],
        'activo' => $p['activo'],
        'nivel' => $p['nivel'],
        'avatar' => '',
        'stats_json' => $db->escape_string(json_encode($p['stats'], JSON_UNESCAPED_UNICODE)),
        'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
        'inventario' => $db->escape_string(json_encode($inventario, JSON_UNESCAPED_UNICODE)),
        'economia' => $db->escape_string(json_encode($economia, JSON_UNESCAPED_UNICODE)),
        'bio' => $db->escape_string(json_encode($bio, JSON_UNESCAPED_UNICODE)),
        'dateline' => TIME_NOW,
        'lastedit' => TIME_NOW,
    );

    if ($pid > 0) {
        $db->update_query('rol_personajes', $ins_pj, "pid = {$pid}");
    } else {
        $pid = (int) $db->insert_query('rol_personajes', $ins_pj);
        echo "  [INSERT] PJ '{$p['nombre']}' (pid: {$pid})\n";
    }

    // Insert/update rol_pj_vocaciones
    if ($pid > 0 && $db->table_exists('rol_pj_vocaciones')) {
        $db->delete_query('rol_pj_vocaciones', "pid = {$pid}");
        $db->insert_query('rol_pj_vocaciones', array(
            'pid' => $pid,
            'clase' => $db->escape_string($p['clase']),
            'oficios' => $db->escape_string(json_encode($p['oficios'], JSON_UNESCAPED_UNICODE)),
            'arma' => $db->escape_string($p['arma']),
            'elecciones' => $db->escape_string(json_encode($p['elecciones'], JSON_UNESCAPED_UNICODE)),
            'arquetipo_clase' => $db->escape_string($p['arquetipo_clase']),
            'dateline' => TIME_NOW,
        ));
        echo "  [OK] rol_pj_vocaciones para pid {$pid}\n";
    }

    // Update active character in rol_cuentas if active
    if ($p['activo'] && $db->table_exists('rol_cuentas')) {
        $db->update_query('rol_cuentas', array('personaje_activo' => $pid), "uid = {$p['uid']}");
    }
}

echo "\n=== DONE ===\n";
$db->close();
