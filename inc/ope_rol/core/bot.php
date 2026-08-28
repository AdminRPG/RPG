<?php
/**
 * One Piece: 7 Seas · Bot «OPE Eternal» (módulo del sistema)
 * -----------------------------------------------------------------------------
 * El bot es el usuario del sistema para posteos automáticos: periódico
 * «News Coo», sucesos de ronda, rumores, renacimiento de frutas, avisos.
 * Necesita un personaje-sistema (es_NPC) para firmar los posts como personaje
 * (el postbit muestra el personaje, no la cuenta). F6.3: vive en
 * mybb_ope_personajes (canónico); si aún existe el legado rol_personajes,
 * también se mantiene en espejo para los hooks viejos del plugin.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** uid del bot (por nombre, caché estática). */
function ope7_bot_uid()
{
    global $db;
    static $uid = null;
    if ($uid !== null) {
        return $uid;
    }
    $uid = 0;
    if ($db->table_exists('users')) {
        $q = $db->simple_select('users', 'uid', "username = 'OPE Eternal'", array('limit' => 1));
        if ($db->num_rows($q)) {
            $uid = (int) $db->fetch_field($q, 'uid');
        }
    }
    return $uid;
}

/**
 * Asegura (idempotente) el personaje-sistema del bot en mybb_ope_personajes
 * (es_NPC=1). F6.3: fuente canónica; el legado rol_personajes solo se usa si
 * el esquema nuevo no existe todavía (transición) o como espejo.
 * @return int id del personaje del bot (0 si falla).
 */
function ope7_bot_personaje()
{
    global $db;
    $bot_uid = ope7_bot_uid();
    if ($bot_uid < 1) {
        return 0;
    }
    if (ope7_tabla_existe('personajes')) {
        $q = $db->simple_select(ope7_tabla('personajes'), 'id', "uid = {$bot_uid} AND es_NPC = 1", array('order_by' => 'id', 'order_dir' => 'ASC', 'limit' => 1));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'id');
        }
        // Crear la persona del bot en el esquema canónico (foto fija, sin progreso).
        $now = TIME_NOW;
        $pid = $db->insert_query(ope7_tabla('personajes'), array(
            'uid'       => $bot_uid,
            'nombre'    => 'OPE Eternal',
            'slug'      => 'ope-eternal',
            'estado'    => 'aprobado',
            'es_NPC'    => 1,
            'nivel'     => 1,
            'avatar'    => '',
            'bio'       => 'La voz del mundo: el periódico News Coo y los avisos del sistema.',
            'dateline'  => $now,
            'lastedit'  => $now,
        ));
        return (int) $pid;
    }
    // Transición: el esquema canónico aún no existe → legado rol_personajes.
    if ($db->table_exists('rol_personajes')) {
        $q = $db->simple_select('rol_personajes', 'pid', "uid = {$bot_uid} AND es_npc = 1", array('order_by' => 'pid', 'order_dir' => 'ASC', 'limit' => 1));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'pid');
        }
        $now = TIME_NOW;
        $pid = $db->insert_query('rol_personajes', array(
            'uid' => $bot_uid, 'nombre' => 'OPE Eternal', 'slug' => 'ope-eternal',
            'estado' => 'aprobado', 'activo' => 0, 'rango' => 'P', 'nivel' => 1,
            'datos' => $db->escape_string(json_encode(array('retrato' => ''), JSON_UNESCAPED_UNICODE)),
            'inventario' => '{}', 'economia' => '{}',
            'bio' => $db->escape_string(json_encode('La voz del mundo: el periódico News Coo y los avisos del sistema.', JSON_UNESCAPED_UNICODE)),
            'dateline' => $now, 'lastedit' => $now, 'es_npc' => 1, 'staff_rol' => 'webmaster',
        ));
        return (int) $pid;
    }
    return 0;
}

/** Publica un hilo como «OPE Eternal». Devuelve el tid o 0. */
function ope7_bot_post_thread($fid, $subject, $message, $tag = '')
{
    global $mybb, $db;

    $bot_uid = ope7_bot_uid();
    $bot_pid = ope7_bot_personaje();
    if ($bot_uid < 1 || $bot_pid < 1 || (int) $fid < 1) {
        return 0;
    }

    require_once MYBB_ROOT . 'inc/datahandlers/post.php';
    $dh = new PostDataHandler('insert');
    $dh->set_data(array(
        'fid'      => (int) $fid,
        'uid'      => $bot_uid,
        'username' => 'OPE Eternal',
        'subject'  => (string) $subject,
        'message'  => (string) $message,
        'visible'  => 1,
        'options'  => array(),
        'posthash' => md5($bot_uid . time() . rand(0, 99999)),
    ));
    if (!$dh->validate_thread()) {
        $err = $dh->get_errors();
        error_log('OPE Eternal (7 Seas) create_thread error: ' . implode(', ', $err));
        return 0;
    }
    $dh->thread_insert_data['ope_pid'] = $bot_pid;
    $dh->post_insert_data['ope_pid']   = $bot_pid;

    $thread_info = $dh->insert_thread();
    $tid = isset($thread_info['tid']) ? (int) $thread_info['tid'] : 0;
    if ($tid > 0 && function_exists('ope_rol_store_thread_meta') && $db->table_exists('ope_thread_meta')) {
        ope_rol_store_thread_meta($tid, (int) $fid, 'presente', 0, $tag);
    }
    return $tid;
}

/** Publica una respuesta como «OPE Eternal» en un tema. Devuelve el pid del post o 0. */
function ope7_bot_post_reply($tid, $message)
{
    $bot_uid = ope7_bot_uid();
    $bot_pid = ope7_bot_personaje();
    if ($bot_uid < 1 || $bot_pid < 1 || (int) $tid < 1) {
        return 0;
    }

    require_once MYBB_ROOT . 'inc/datahandlers/post.php';
    $dh = new PostDataHandler('insert');
    $dh->set_data(array(
        'tid'      => (int) $tid,
        'uid'      => $bot_uid,
        'username' => 'OPE Eternal',
        'message'  => (string) $message,
        'visible'  => 1,
        'options'  => array(),
        'posthash' => md5($bot_uid . time() . rand(0, 99999)),
    ));
    if (!$dh->validate_post()) {
        $err = $dh->get_errors();
        error_log('OPE Eternal (7 Seas) create_post error: ' . implode(', ', $err));
        return 0;
    }
    $dh->post_insert_data['ope_pid'] = $bot_pid;
    $post_info = $dh->insert_post();
    return isset($post_info['pid']) ? (int) $post_info['pid'] : 0;
}
