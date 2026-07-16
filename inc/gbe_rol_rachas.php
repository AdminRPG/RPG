<?php
if (!defined('IN_MYBB')) { die('Direct initialization of this file is not allowed.'); }

function gbe_racha_recompensas() {
    return array(
        7  => array('pp' => 5,  'berries' => 50000,   'cofre' => false, 'nombre' => 'Racha 7 días'),
        14 => array('pp' => 10, 'berries' => 100000,  'cofre' => false, 'nombre' => 'Racha 14 días'),
        21 => array('pp' => 15, 'berries' => 250000,  'cofre' => true,  'nombre' => 'Racha 21 días'),
        30 => array('pp' => 25, 'berries' => 500000,  'cofre' => false, 'nombre' => 'Racha 30 días'),
    );
}

function gbe_racha_get($pid) {
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_rachas')) {
        return array('racha_dias' => 0, 'ultimo_post' => 0, 'recompensa_dia7' => 0, 'recompensa_dia14' => 0, 'recompensa_dia21' => 0, 'recompensa_dia30' => 0);
    }
    $q = $db->simple_select('rol_rachas', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) return $db->fetch_array($q);
    $db->insert_query('rol_rachas', array('pid' => $pid, 'created_at' => TIME_NOW, 'updated_at' => TIME_NOW));
    return array('racha_dias' => 0, 'ultimo_post' => 0, 'recompensa_dia7' => 0, 'recompensa_dia14' => 0, 'recompensa_dia21' => 0, 'recompensa_dia30' => 0);
}

function gbe_racha_procesar_post($pid) {
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_rachas')) return array('ok' => false);

    $racha = gbe_racha_get($pid);
    $ahora = TIME_NOW;
    $ultimo = (int)$racha['ultimo_post'];
    $dias_actual = (int)$racha['racha_dias'];

    if ($ultimo > 0 && ($ahora - $ultimo) > 172800) {
        $dias_actual = 0;
        $db->update_query('rol_rachas', array(
            'racha_dias' => 0,
            'recompensa_dia7' => 0, 'recompensa_dia14' => 0,
            'recompensa_dia21' => 0, 'recompensa_dia30' => 0,
            'updated_at' => $ahora,
        ), "pid = {$pid}");
        return array('ok' => true, 'racha_reset' => true, 'racha_dias' => 0);
    }

    $hoy = date('Y-m-d', $ahora);
    $ultimo_dia = $ultimo > 0 ? date('Y-m-d', $ultimo) : '';
    if ($hoy !== $ultimo_dia) {
        $dias_actual++;
        $update = array('racha_dias' => $dias_actual, 'ultimo_post' => $ahora, 'updated_at' => $ahora);

        $recompensas = gbe_racha_recompensas();
        $recompensa_otorgada = null;
        foreach (array(7, 14, 21, 30) as $hito) {
            $flag = "recompensa_dia{$hito}";
            if ($dias_actual >= $hito && isset($recompensas[$hito])) {
                $r = $recompensas[$hito];
                $updated = $db->write_query("UPDATE " . TABLE_PREFIX . "rol_rachas SET {$flag} = 1, updated_at = {$ahora} WHERE pid = {$pid} AND {$flag} = 0");
                if ($db->affected_rows() > 0) {
                    if (function_exists('gbe_pp_add')) {
                        gbe_pp_add($pid, $r['pp'], 'racha', 0, 0, 0, "Racha día {$hito}");
                    }
                    $pj_q = $db->simple_select('rol_personajes', 'economia', "pid = {$pid}", array('limit' => 1));
                    if ($db->num_rows($pj_q)) {
                        $eco = json_decode((string)$db->fetch_field($pj_q, 'economia'), true);
                        if (!is_array($eco)) $eco = array();
                        $eco['berries'] = ((int)($eco['berries'] ?? 0)) + $r['berries'];
                        $db->update_query('rol_personajes', array('economia' => $db->escape_string(json_encode($eco, JSON_UNESCAPED_UNICODE))), "pid = {$pid}");
                    }
                    $update[$flag] = 1;
                    $recompensa_otorgada = $r;
                    break;
                }
            }
        }

        $db->update_query('rol_rachas', $update, "pid = {$pid}");
        return array('ok' => true, 'racha_dias' => $dias_actual, 'recompensa' => $recompensa_otorgada);
    }

    $db->update_query('rol_rachas', array('ultimo_post' => $ahora, 'updated_at' => $ahora), "pid = {$pid}");
    return array('ok' => true, 'racha_dias' => $dias_actual, 'mismo_dia' => true);
}
