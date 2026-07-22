<?php
/**
 * One Piece: Eternal · Trámites (helpers)
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Catálogo de ventanillas jugador. */
function ope_tramites_catalogo()
{
    return array();
}

function ope_tramite_rank_min($tipo)
{
    $cat = ope_tramites_catalogo();
    return (int) ($cat[$tipo]['rank_min'] ?? 2);
}

function ope_tramite_crear($uid, $pid, $tipo, array $payload)
{
    global $db;
    $uid = (int) $uid;
    $pid = (int) $pid;
    $tipo = (string) $tipo;
    $cat = ope_tramites_catalogo();
    if (!isset($cat[$tipo]) || !$db->table_exists('rol_tramites')) {
        return array('ok' => false, 'msg' => 'Tipo de trámite no válido.');
    }
    if ($uid < 1 || $pid < 1) {
        return array('ok' => false, 'msg' => 'Necesitas un personaje activo.');
    }
    // Evitar duplicados pendientes del mismo tipo
    $tq = $db->simple_select(
        'rol_tramites',
        'tid',
        "pid = {$pid} AND tipo = '" . $db->escape_string($tipo) . "' AND estado IN ('pendiente','en_proceso')",
        array('limit' => 1)
    );
    if ($db->num_rows($tq)) {
        return array('ok' => false, 'msg' => 'Ya tienes un trámite de este tipo en cola.');
    }

    $tid = $db->insert_query('rol_tramites', array(
        'uid' => $uid,
        'pid' => $pid,
        'tipo' => $db->escape_string($tipo),
        'estado' => 'pendiente',
        'datos' => $db->escape_string(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        'dateline' => TIME_NOW,
        'lastedit' => TIME_NOW,
    ));
    return array('ok' => true, 'msg' => 'Trámite enviado. El staff lo revisará.', 'tid' => (int) $tid);
}

/**
 * Resuelve un trámite: aplica efectos mecánicos según tipo.
 */
function ope_tramite_resolver($tid, $staff_uid, $accion, $nota = '', array $extra = array())
{
    global $db;
    $tid = (int) $tid;
    $staff_uid = (int) $staff_uid;
    $accion = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';

    $q = $db->simple_select('rol_tramites', '*', "tid = {$tid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Trámite no encontrado.');
    }
    $tr = $db->fetch_array($q);
    if (!in_array((string) $tr['estado'], array('pendiente', 'en_proceso'), true)) {
        return array('ok' => false, 'msg' => 'Este trámite ya está cerrado.');
    }

    $tipo = (string) $tr['tipo'];
    $pid = (int) $tr['pid'];
    $datos = json_decode((string) ($tr['datos'] ?? ''), true);
    if (!is_array($datos)) {
        $datos = array();
    }

    $msg_efecto = '';
    if ($accion === 'aprobado') {
        switch ($tipo) {
            case 'hao_despertar':
                $ruta = (string) ($datos['ruta'] ?? 'tirada');
                if ($ruta === 'tirada') {
                    $roll = isset($extra['roll']) ? (int) $extra['roll'] : mt_rand(1, 100);
                    $chance = isset($extra['chance']) ? (int) $extra['chance'] : 8;
                    $datos['roll'] = $roll;
                    $datos['chance'] = $chance;
                    if ($roll > $chance) {
                        $accion = 'rechazado';
                        $msg_efecto = "Tirada {$roll}/100 (necesitaba ≤{$chance}). Fallo de despertar.";
                        break;
                    }
                    $msg_efecto = "Tirada {$roll}/100 ≤{$chance}. ";
                }
                if ($accion === 'aprobado' && function_exists('ope_haki_marcar_despertado')) {
                    $r = ope_haki_marcar_despertado($pid, 'hao', $ruta === 'pd' ? 'pd' : 'tirada');
                    $msg_efecto .= (string) ($r['msg'] ?? '');
                }
                break;

            case 'ken_t1':
                if (function_exists('ope_haki_buy_ken_t1')) {
                    $r = ope_haki_buy_ken_t1($pid);
                    if (empty($r['ok'])) {
                        return array('ok' => false, 'msg' => (string) ($r['msg'] ?? 'No se pudo aplicar Ken T1.'));
                    }
                    $msg_efecto = (string) $r['msg'];
                }
                break;

            case 'fruta_despertar':
                if (function_exists('ope_fruta_despertar')) {
                    $r = ope_fruta_despertar($pid);
                    if (empty($r['ok'])) {
                        return array('ok' => false, 'msg' => (string) ($r['msg'] ?? 'No se pudo despertar.'));
                    }
                    $msg_efecto = (string) $r['msg'];
                }
                break;

            case 'akuma_pd':
                $modo = (string) ($datos['modo'] ?? 'tier');
                $fruta_id = (int) ($datos['fruta_id'] ?? 0);
                if ($modo === 'concreta' && $fruta_id > 0) {
                    $r = ope_fruta_asignar($pid, $fruta_id, 'pd');
                } else {
                    $tier = (int) ($datos['tier'] ?? 1);
                    $libres = ope_fruta_libres($tier);
                    if (empty($libres)) {
                        return array('ok' => false, 'msg' => 'No hay frutas libres de ese Tier.');
                    }
                    $pick = $libres[array_rand($libres)];
                    $r = ope_fruta_asignar($pid, (int) $pick['id'], 'pd');
                }
                if (empty($r['ok'])) {
                    return array('ok' => false, 'msg' => (string) ($r['msg'] ?? 'No se pudo asignar fruta.'));
                }
                $msg_efecto = (string) $r['msg'];
                break;

            default:
                // tecnica_custom, dote_poder, cyborg, herencia: registro staff (efecto manual / futuro)
                $msg_efecto = 'Aprobado. Aplica el efecto en ficha si procede.';
                break;
        }
    }

    $upd = array(
        'estado' => $accion,
        'lastedit' => TIME_NOW,
        'datos' => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
    );
    if ($db->field_exists('staff_uid', 'rol_tramites')) {
        $upd['staff_uid'] = $staff_uid;
    }
    if ($db->field_exists('nota_staff', 'rol_tramites')) {
        $nota_full = trim($nota . ($msg_efecto !== '' ? (' · ' . $msg_efecto) : ''));
        $upd['nota_staff'] = $db->escape_string($nota_full);
    }
    $db->update_query('rol_tramites', $upd, "tid = {$tid}");

    $label = $accion === 'aprobado' ? 'aprobado' : 'rechazado';
    return array(
        'ok' => true,
        'msg' => 'Trámite ' . $label . '.' . ($msg_efecto !== '' ? ' ' . $msg_efecto : ''),
    );
}
