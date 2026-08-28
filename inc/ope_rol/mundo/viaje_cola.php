<?php
/**
 * One Piece: Eternal · Cola de viajes (publicación asíncrona) + alertas + flash.
 *
 * El usuario pulsa "Zarpar" → se ENCOLA el viaje y vuelve al índice al instante.
 * Una tarea programada de MyBB procesa la cola en segundo plano: calcula el
 * oráculo + intro IA, crea el hilo en Alta Mar y notifica por la campana
 * (ope_alertas) al capitán y a cada tripulante acompañante.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ─────────────────────────────────────────────────────────────
// Flash one-time (mensajes al volver al índice, p.ej. "¡Te avisaremos!")
// ─────────────────────────────────────────────────────────────
function ope_flash_set(int $uid, string $tipo, string $mensaje)
{
    global $db;
    if ($uid <= 0 || !$db->table_exists('rol_flash')) return;
    $db->insert_query('rol_flash', array(
        'uid'       => $uid,
        'tipo'      => $db->escape_string($tipo),
        'mensaje'   => $db->escape_string($mensaje),
        'leido'     => 0,
        'dateline'  => TIME_NOW,
    ));
}

/** Devuelve los flashes sin leer del usuario y los marca como leídos (one-time). */
function ope_flash_pull(int $uid)
{
    global $db;
    if ($uid <= 0 || !$db->table_exists('rol_flash')) return array();
    $q = $db->simple_select('rol_flash', '*', "uid = {$uid} AND leido = 0", array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => 5));
    $out = array();
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    $db->update_query('rol_flash', array('leido' => 1), "uid = {$uid} AND leido = 0");
    return $out;
}

// ─────────────────────────────────────────────────────────────
// Encolar
// ─────────────────────────────────────────────────────────────
/**
 * Encola una solicitud de viaje para publicarla en segundo plano.
 * Valida que el capitán no tenga un viaje activo ni otro pendiente.
 */
function ope_viaje_encolar(array $data)
{
    global $db;

    if (!$db->table_exists('rol_viajes_cola')) {
        return array('ok' => false, 'msg' => 'Sistema de viajes no instalado. Ejecuta migrate-viajes-cola.php.');
    }

    $pid_capitan = (int) ($data['pid_capitan'] ?? 0);
    $uid         = (int) ($data['uid'] ?? 0);
    if ($pid_capitan < 1) {
        return array('ok' => false, 'msg' => 'Debes tener un personaje activo para zarpar.');
    }

    // No encolar si ya hay un viaje activo o uno pendiente/en cola.
    if (function_exists('ope_viaje_por_capitan_activo') && ope_viaje_por_capitan_activo($pid_capitan)) {
        return array('ok' => false, 'msg' => 'Ya tienes un viaje en curso. Debes llegar a tu destino antes de zarpar de nuevo.');
    }
    $dup = $db->simple_select('rol_viajes_cola', 'COUNT(*) as cnt', "pid_capitan = {$pid_capitan} AND estado IN ('pendiente','procesando')");
    if ($db->num_rows($dup) && (int) $db->fetch_field($dup, 'cnt') > 0) {
        return array('ok' => false, 'msg' => 'Ya tienes un viaje en preparación. Pronto estará publicado.');
    }

    $db->insert_query('rol_viajes_cola', array(
        'pid_capitan'    => $pid_capitan,
        'uid'            => $uid,
        'payload_json'   => $db->escape_string(json_encode($data, JSON_UNESCAPED_UNICODE)),
        'estado'         => 'pendiente',
        'dateline'       => TIME_NOW,
    ));
    $cola_id = (int) $db->insert_id();

    return array('ok' => true, 'msg' => '¡Te avisaremos cuando zarpe!', 'cola_id' => $cola_id);
}

// ─────────────────────────────────────────────────────────────
// Procesar la cola (desde la tarea programada)
// ─────────────────────────────────────────────────────────────
/**
 * Procesa UN viaje pendiente de la cola y publica el hilo + notifica.
 * Devuelve el número de viajes procesados en esta pasada.
 */
function ope_viaje_procesar_cola()
{
    global $db;

    if (!$db->table_exists('rol_viajes_cola')) return 0;

    $q = $db->simple_select('rol_viajes_cola', '*', "estado = 'pendiente'", array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => 1));
    if (!$db->num_rows($q)) {
        return 0;
    }
    $item = $db->fetch_array($q);
    $cola_id = (int) $item['id'];

    // Bloquear para evitar doble proceso.
    $db->update_query('rol_viajes_cola', array('estado' => 'procesando'), "id = {$cola_id} AND estado = 'pendiente'");
    $check = $db->simple_select('rol_viajes_cola', 'estado', "id = {$cola_id}", array('limit' => 1));
    if ($db->num_rows($check) && $db->fetch_field($check, 'estado') !== 'procesando') {
        return 0;
    }

    $payload = json_decode((string) ($item['payload_json'] ?? '[]'), true);
    if (!is_array($payload)) {
        $db->update_query('rol_viajes_cola', array(
            'estado' => 'fallo',
            'error'  => 'Payload inválido',
            'procesado_dateline' => TIME_NOW,
        ), "id = {$cola_id}");
        return 1;
    }

    $uid = (int) ($item['uid'] ?? 0);
    $res = ope_viaje_solicitar($payload);

    if (!empty($res['ok'])) {
        $viaje_id = (int) ($res['viaje_id'] ?? 0);
        $tid      = (int) ($res['tid'] ?? 0);
        $db->update_query('rol_viajes_cola', array(
            'estado' => 'ok',
            'viaje_id' => $viaje_id,
            'tid' => $tid,
            'procesado_dateline' => TIME_NOW,
        ), "id = {$cola_id}");

        // Notificar a capitán y tripulantes
        if ($viaje_id > 0 && function_exists('ope_viaje_por_id')) {
            $viaje = ope_viaje_por_id($viaje_id);
            if ($viaje) {
                ope_viaje_alertar_publicar($viaje, $uid);
            }
        }
        return 1;
    }

    // Fallo: registrar y avisar al capitán para reintentar desde el planificador.
    $err = mb_substr((string) ($res['msg'] ?? 'Error al publicar el viaje.'), 0, 250);
    $db->update_query('rol_viajes_cola', array(
        'estado' => 'fallo',
        'error'  => $db->escape_string($err),
        'procesado_dateline' => TIME_NOW,
    ), "id = {$cola_id}");

    if ($uid > 0 && $db->table_exists('ope_alertas')) {
        $bburl = rtrim((string) $GLOBALS['mybb']->settings['bburl'] ?? '', '/');
        $db->insert_query('ope_alertas', array(
            'pid' => (int) $item['pid_capitan'],
            'uid' => $uid,
            'tipo' => 'viaje_publicado',
            'titulo' => 'El viaje no se pudo publicar',
            'cuerpo' => $err . ' Vuelve al planificador para intentarlo de nuevo.',
            'link' => $bburl . '/viajes.php',
            'leido' => 0,
            'dateline' => TIME_NOW,
        ));
    }

    return 1;
}

// ─────────────────────────────────────────────────────────────
// Alertas de publicación
// ─────────────────────────────────────────────────────────────
/**
 * Inserta una alerta 'viaje_publicado' para el capitán y cada tripulante del viaje.
 */
function ope_viaje_alertar_publicar(array $viaje, int $uid_capitan)
{
    global $db;

    if (!$db->table_exists('ope_alertas')) return;

    $viaje_id = (int) ($viaje['viaje_id'] ?? 0);
    $tid      = (int) ($viaje['tid'] ?? 0);
    $origen   = (string) ($viaje['origen_nombre'] ?? '');
    $destino  = (string) ($viaje['destino_nombre'] ?? '');
    $bburl    = rtrim((string) ($GLOBALS['mybb']->settings['bburl'] ?? ''), '/');
    $link     = $tid > 0 ? ($bburl . '/showthread.php?tid=' . $tid) : ($bburl . '/viajes.php');
    $titulo   = '¡Zarpe completado! ' . $origen . ' → ' . $destino;
    $cuerpo   = 'Tu travesía se ha publicado en Alta Mar. Narra cómo se desenvuelve la aventura.';

    // Receptores: pid + uid.
    $receptores = array();
    $cap_pid = (int) ($viaje['pid_capitan'] ?? 0);
    if ($cap_pid > 0) {
        $receptores[$cap_pid] = $uid_capitan > 0 ? $uid_capitan : 0;
    }

    $trip = json_decode((string) ($viaje['tripulantes_json'] ?? '[]'), true);
    if (is_array($trip)) {
        foreach ($trip as $t) {
            $tp = (int) ($t['pid'] ?? 0);
            if ($tp > 0 && $tp !== $cap_pid) {
                $receptores[$tp] = 0;
            }
        }
    }

    // Resolver uid de cada tripulante.
    if ($db->table_exists('rol_personajes')) {
        $pids = array_keys($receptores);
        if ($pids) {
            $inq = implode(',', array_map('intval', $pids));
            $rq = $db->simple_select('rol_personajes', 'pid, uid', "pid IN ({$inq})");
            while ($r = $db->fetch_array($rq)) {
                $rpid = (int) $r['pid'];
                if (isset($receptores[$rpid]) && $receptores[$rpid] === 0) {
                    $receptores[$rpid] = (int) $r['uid'];
                }
            }
        }
    }

    foreach ($receptores as $pid => $ruid) {
        if ($ruid < 1) continue;
        $db->insert_query('ope_alertas', array(
            'pid'       => $pid,
            'uid'       => $ruid,
            'tipo'      => 'viaje_publicado',
            'titulo'    => $db->escape_string($titulo),
            'cuerpo'    => $db->escape_string($cuerpo),
            'link'      => $db->escape_string($link),
            'leido'     => 0,
            'dateline'  => TIME_NOW,
        ));
    }
}