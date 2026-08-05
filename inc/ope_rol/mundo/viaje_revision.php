<?php
/**
 * One Piece: Eternal · Logica de revision staff de cierres de viaje.
 * -----------------------------------------------------------------
 * Funciones para listar viajes pendientes, aprobar y rechazar cierres.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Lista los viajes con estado pendiente_cierre. */
function ope_viaje_revision_pendientes()
{
    global $db;
    if (!$db->table_exists('rol_viajes')) return array();

    $q = $db->query("
        SELECT v.*
        FROM {$db->table_prefix}rol_viajes v
        WHERE v.estado = 'pendiente_cierre'
        ORDER BY v.dateline ASC
    ");
    $out = array();
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    return $out;
}

/** Cuenta viajes pendientes de revision. */
function ope_viaje_revision_count()
{
    global $db;
    if (!$db->table_exists('rol_viajes')) return 0;
    $q = $db->simple_select('rol_viajes', 'COUNT(*) AS c', "estado = 'pendiente_cierre'");
    return (int) $db->fetch_field($q, 'c');
}

/** Aprueba el cierre de un viaje: ejecuta cierre real, marca como completado. */
function ope_viaje_revision_aprobar($viaje_id, $staff_uid)
{
    global $db;

    $viaje = ope_viaje_por_id($viaje_id);
    if (!$viaje) return array('ok' => false, 'msg' => 'Viaje no encontrado.');
    if (($viaje['estado'] ?? '') !== 'pendiente_cierre') {
        return array('ok' => false, 'msg' => 'Este viaje no esta pendiente de revision.');
    }

    $res = ope_viaje_cerrar_ejecutar($viaje);
    if (!$res['ok']) return $res;

    $db->update_query('rol_viajes', array(
        'revision_staff_uid' => (int) $staff_uid,
        'revision_dateline'  => TIME_NOW,
    ), 'viaje_id = ' . (int) $viaje_id);

    return array('ok' => true, 'msg' => 'Cierre aprobado. La tripulacion ha llegado a destino.');
}

/** Rechaza el cierre. 1er intento: vuelve a activo. 2do intento: cancela el viaje. */
function ope_viaje_revision_rechazar($viaje_id, $staff_uid, $motivo)
{
    global $db;

    $viaje = ope_viaje_por_id($viaje_id);
    if (!$viaje) return array('ok' => false, 'msg' => 'Viaje no encontrado.');
    if (($viaje['estado'] ?? '') !== 'pendiente_cierre') {
        return array('ok' => false, 'msg' => 'Este viaje no esta pendiente de revision.');
    }

    $intentos = (int) ($viaje['revision_intentos'] ?? 1);
    $bburl = rtrim((string) $GLOBALS['mybb']->settings['bburl'], '/');

    if ($intentos >= 2) {
        // Segundo rechazo: cancelar viaje
        $db->update_query('rol_viajes', array(
            'estado'             => 'cancelado',
            'revision_staff_uid'  => (int) $staff_uid,
            'revision_motivo'     => $db->escape_string($motivo),
            'revision_dateline'   => TIME_NOW,
        ), 'viaje_id = ' . (int) $viaje_id);

        $post_msg = '[viaje-rechazo-cancelado=' . (int) $viaje_id . ']';
        ope_system_create_post((int) $viaje['tid'], $post_msg);

        return array(
            'ok' => true,
            'msg' => 'Viaje cancelado definitivamente (2 rechazos).',
            'url' => $bburl . '/showthread.php?tid=' . (int) $viaje['tid'],
        );
    }

    // Primer rechazo: volver a activo
    $db->update_query('rol_viajes', array(
        'estado'             => 'activo',
        'revision_staff_uid'  => (int) $staff_uid,
        'revision_motivo'     => $db->escape_string($motivo),
        'revision_dateline'   => TIME_NOW,
    ), 'viaje_id = ' . (int) $viaje_id);

    $post_msg = '[viaje-rechazo=' . (int) $viaje_id . ']';
    ope_system_create_post((int) $viaje['tid'], $post_msg);

    return array(
        'ok' => true,
        'msg' => 'Cierre rechazado. El viaje vuelve a estar activo para que la tripulacion pueda corregirlo.',
        'url' => $bburl . '/showthread.php?tid=' . (int) $viaje['tid'],
    );
}

/** Guarda el resultado del analisis IA en el viaje. */
function ope_viaje_revision_guardar_ai($viaje_id, array $analisis)
{
    global $db;
    $db->update_query('rol_viajes', array(
        'revision_ai_json' => $db->escape_string(json_encode($analisis, JSON_UNESCAPED_UNICODE)),
    ), 'viaje_id = ' . (int) $viaje_id);
}

/** Obtiene los posts de un hilo para el analisis IA. */
function ope_viaje_revision_obtener_posts($tid)
{
    global $db;
    $tid = (int) $tid;
    if ($tid < 1) return array();

    $q = $db->query("
        SELECT p.pid, p.uid, p.username, p.message, p.dateline
        FROM {$db->table_prefix}posts p
        WHERE p.tid = {$tid} AND p.visible = 1
        ORDER BY p.dateline ASC
    ");
    $posts = array();
    while ($row = $db->fetch_array($q)) {
        $msg = strip_tags((string) $row['message']);
        $msg = preg_replace('/\s+/', ' ', $msg);
        $msg = trim($msg);
        if (mb_strlen($msg, 'UTF-8') > 800) {
            $msg = mb_substr($msg, 0, 800, 'UTF-8') . ' [truncado]';
        }
        $posts[] = array(
            'autor'    => (string) ($row['username'] ?? 'Desconocido'),
            'contenido' => $msg,
            'fecha'    => (int) $row['dateline'],
        );
    }
    return $posts;
}
