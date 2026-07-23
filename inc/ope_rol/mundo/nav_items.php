<?php
/**
 * One Piece: Eternal · Items de Navegacion (CRUD sobre rol_nav_items)
 * -------------------------------------------------------------------
 * Gestiona items de navegacion vinculados a un personaje:
 * Brujula, Log Pose, Eternal Pose, Kairoseki, Resina, Mapa, Vivre Card.
 *
 * Uso:
 *   $items = ope_nav_item_lista($pid);
 *   $r     = ope_nav_item_dar($pid, 'log_pose');
 *   $tiene = ope_nav_item_tiene($pid, 'brujula');
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Catalogo de items de navegacion.
 */
function ope_nav_items_catalogo()
{
    return array(
        'brujula'         => array('nombre' => 'Brujula',                'desc' => 'Necesaria para navegar entre islas de los Blues.',                          'requerido_en' => 'Blues'),
        'log_pose'        => array('nombre' => 'Log Pose',              'desc' => 'Registra el magnetismo de las islas. Obligatorio en Grand Line (Paradise).', 'requerido_en' => 'Paradise'),
        'log_pose_nw'     => array('nombre' => 'Log Pose del Nuevo Mundo','desc'=> 'Version de 3 agujas. Obligatorio en el Nuevo Mundo.',                     'requerido_en' => 'New World'),
        'eternal_pose'    => array('nombre' => 'Eternal Pose',          'desc' => 'Apunta permanentemente a una isla concreta. Reduce tramos y peligro.',       'requerido_en' => null),
        'kairoseki'       => array('nombre' => 'Recubrimiento Kairoseki','desc'=> 'Permite cruzar el Calm Belt sin ser detectado por Reyes Marinos.',          'requerido_en' => 'Calm Belt'),
        'resina_sabaody'  => array('nombre' => 'Resina de Sabaody',     'desc' => 'Burbuja protectora para descender a Isla Gyojin. Se consume al usarla.',    'requerido_en' => 'Sabaody-Gyojin'),
        'mapa_nautico'    => array('nombre' => 'Mapa Nautico',          'desc' => 'Reduce tramos en rutas ya conocidas.',                                      'requerido_en' => null),
        'vivre_card'      => array('nombre' => 'Vivre Card',            'desc' => 'Apunta a un PJ concreto. Reduce peligro ligeramente.',                       'requerido_en' => null),
    );
}

/**
 * Lista todos los items de navegacion de un personaje.
 */
function ope_nav_item_lista($pid)
{
    global $db;
    $pid = (int)$pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('rol_nav_items')) {
        return $out;
    }
    $q = $db->simple_select('rol_nav_items', '*', "pid = {$pid}", array('order_by' => 'slug'));
    $cat = ope_nav_items_catalogo();
    while ($row = $db->fetch_array($q)) {
        $slug = (string)$row['slug'];
        $row['nombre'] = isset($cat[$slug]) ? $cat[$slug]['nombre'] : $slug;
        $row['desc']   = isset($cat[$slug]) ? $cat[$slug]['desc'] : '';
        $row['datos']  = json_decode((string)($row['datos_json'] ?? '{}'), true);
        if (!is_array($row['datos'])) $row['datos'] = array();
        $out[] = $row;
    }
    return $out;
}

/**
 * Devuelve los slugs de items que tiene un personaje.
 */
function ope_nav_item_slugs($pid)
{
    $items = ope_nav_item_lista($pid);
    $slugs = array();
    foreach ($items as $item) {
        $slugs[] = (string)$item['slug'];
    }
    return $slugs;
}

/**
 * Comprueba si un personaje tiene un item concreto.
 */
function ope_nav_item_tiene($pid, $slug)
{
    global $db;
    $pid  = (int)$pid;
    $slug = (string)$slug;
    if ($pid < 1 || !$db->table_exists('rol_nav_items')) return false;
    $q = $db->simple_select('rol_nav_items', 'item_id', "pid = {$pid} AND slug = '" . $db->escape_string($slug) . "'", array('limit' => 1));
    return $db->num_rows($q) > 0;
}

/**
 * Otorga un item de navegacion a un personaje.
 */
function ope_nav_item_dar($pid, $slug, array $datos = array())
{
    global $db;
    $pid  = (int)$pid;
    $slug = (string)$slug;
    $cat  = ope_nav_items_catalogo();

    if ($pid < 1) {
        return array('ok' => false, 'msg' => 'Personaje no valido.');
    }
    if (!isset($cat[$slug])) {
        return array('ok' => false, 'msg' => 'Item de navegacion no valido.');
    }
    if (!$db->table_exists('rol_nav_items')) {
        return array('ok' => false, 'msg' => 'Sistema de navegacion no instalado.');
    }

    // Items unicos (no stackeables): brujula, log_pose, log_pose_nw
    $unicos = array('brujula', 'log_pose', 'log_pose_nw');
    if (in_array($slug, $unicos, true) && ope_nav_item_tiene($pid, $slug)) {
        return array('ok' => false, 'msg' => 'Ya tienes este item.');
    }

    $db->insert_query('rol_nav_items', array(
        'pid'        => $pid,
        'slug'       => $db->escape_string($slug),
        'datos_json' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
        'cantidad'   => 1,
        'dateline'   => defined('TIME_NOW') ? TIME_NOW : time(),
    ));
    $id = (int)$db->insert_id();
    return array('ok' => true, 'msg' => $cat[$slug]['nombre'] . ' obtenido.', 'item_id' => $id);
}

/**
 * Quita un item de navegacion de un personaje (consume).
 */
function ope_nav_item_consumir($pid, $slug)
{
    global $db;
    $pid  = (int)$pid;
    $slug = (string)$slug;
    if ($pid < 1 || !$db->table_exists('rol_nav_items')) {
        return array('ok' => false, 'msg' => 'Error.');
    }
    $q = $db->simple_select('rol_nav_items', 'item_id, cantidad', "pid = {$pid} AND slug = '" . $db->escape_string($slug) . "'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'No tienes ese item.');
    }
    $row = $db->fetch_array($q);
    $db->delete_query('rol_nav_items', "item_id = " . (int)$row['item_id']);
    return array('ok' => true, 'msg' => 'Item consumido.');
}

/**
 * Otorga items por defecto a un personaje recien creado.
 * PJs que empiezan en Blues reciben Brujula.
 */
function ope_nav_item_defecto($pid, $isla_actual_slug)
{
    $isla = ope_isla_por_slug($isla_actual_slug);
    if (!$isla) return;

    // Todos reciben brujula al empezar
    if (ope_isla_es_blue($isla['macro'])) {
        ope_nav_item_dar($pid, 'brujula');
    }
}
