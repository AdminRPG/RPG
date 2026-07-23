<?php
/**
 * One Piece: Eternal · Funciones del Sistema (OPE Eternal + PP).
 *
 * Incluido desde inc/plugins/ope_rol.php. Usa $db (MyBB) contra tablas mybb_rol_*.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ─────────────────────────────────────────────────────────────────────────
// OPE Eternal: usuario y personaje del sistema
// ─────────────────────────────────────────────────────────────────────────

/** Devuelve el uid de MyBB del bot NARRADOR del sistema. */
function ope_system_uid()
{
    global $db;
    static $uid = null;
    if ($uid !== null) return $uid;

    if ($db->table_exists('users')) {
        $q = $db->simple_select('users', 'uid', "username = 'Narrador' OR username = 'OPE Eternal'", array('order_by' => 'uid', 'order_dir' => 'ASC', 'limit' => 1));
        if ($db->num_rows($q)) {
            $uid = (int) $db->fetch_field($q, 'uid');
            return $uid;
        }
    }
    $uid = 0;
    return $uid;
}

/** Devuelve el pid del personaje NARRADOR del sistema. */
function ope_system_pid()
{
    global $db;
    static $pid = null;
    if ($pid !== null) return $pid;

    $suid = ope_system_uid();
    if ($suid > 0 && $db->table_exists('rol_personajes')) {
        $q = $db->simple_select('rol_personajes', 'pid', "nombre = 'Narrador' OR uid = {$suid}", array('order_by' => 'pid', 'order_dir' => 'ASC', 'limit' => 1));
        if ($db->num_rows($q)) {
            $pid = (int) $db->fetch_field($q, 'pid');
            return $pid;
        }
    }
    $pid = 0;
    return $pid;
}

/**
 * Publica un tema/hilo como Narrador en el foro indicado.
 * Devuelve el tid creado o 0 en caso de error.
 */
function ope_system_create_thread($fid, $subject, $message, $tag = '')
{
    global $mybb, $db;

    $sys_uid = ope_system_uid();
    $sys_pid = ope_system_pid();
    if ($sys_uid < 1 || $sys_pid < 1) return 0;
    if ((int) $fid < 1) return 0;

    require_once MYBB_ROOT . 'inc/datahandlers/post.php';
    $dh = new PostDataHandler('insert');
    $dh->set_data(array(
        'fid'         => (int) $fid,
        'uid'         => $sys_uid,
        'username'    => 'Narrador',
        'subject'     => $subject,
        'message'     => $message,
        'visible'     => 1,
        'options'     => array(),
        'posthash'    => md5($sys_uid . time() . rand(0, 99999)),
    ));

    $valid = $dh->validate_thread();
    if (!$valid) {
        $errors = $dh->get_errors();
        if (!empty($errors)) {
            error_log('OPE Eternal create_thread error: ' . implode(', ', $errors));
        }
        return 0;
    }

    // Forzar el pid del personaje sistema en el post estampado
    $dh->thread_insert_data['ope_pid'] = $sys_pid;
    $dh->post_insert_data['ope_pid']   = $sys_pid;

    $thread_info = $dh->insert_thread();
    $tid = isset($thread_info['tid']) ? (int) $thread_info['tid'] : 0;
    if ($tid > 0) {
        if ($db->table_exists('rol_thread_meta')) {
            ope_rol_store_thread_meta($tid, (int) $fid, 'presente', 0, $tag);
        }
    }
    return $tid;
}

/**
 * Publica una respuesta como OPE Eternal en un tema existente.
 * Devuelve el pid del post creado o 0 en caso de error.
 */
function ope_system_create_post($tid, $message)
{
    global $mybb, $db;

    $sys_uid = ope_system_uid();
    $sys_pid = ope_system_pid();
    if ($sys_uid < 1 || $sys_pid < 1) return 0;
    if ((int) $tid < 1) return 0;

    require_once MYBB_ROOT . 'inc/datahandlers/post.php';
    $dh = new PostDataHandler('insert');
    $dh->set_data(array(
        'tid'      => (int) $tid,
        'uid'      => $sys_uid,
        'username' => 'Narrador',
        'message'  => $message,
        'visible'  => 1,
        'options'  => array(),
        'posthash' => md5($sys_uid . time() . rand(0, 99999)),
    ));

    $valid = $dh->validate_post();
    if (!$valid) {
        $errors = $dh->get_errors();
        if (!empty($errors)) {
            error_log('OPE Eternal create_post error: ' . implode(', ', $errors));
        }
        return 0;
    }

    $dh->post_insert_data['ope_pid'] = $sys_pid;
    $post_info = $dh->insert_post();
    return isset($post_info['pid']) ? (int) $post_info['pid'] : 0;
}


// ─────────────────────────────────────────────────────────────────────────
// Motor de PP (Puntos de Progreso)
// ─────────────────────────────────────────────────────────────────────────

/**
 * Tabla legacy (ya no usada para cálculo). Conservada por compatibilidad.
 * Canon OPE: 1 PP por cada 100 palabras (STATS.md / HOJA-DE-RUTA).
 */
function ope_pp_word_table()
{
    return array(
        array(0, 99, 0),
        array(100, 999999, 1), // placeholder; ver ope_pp_for_words
    );
}

/**
 * Calcula PP según el número de palabras.
 * Canon: floor(palabras / 100). Un post de 1000 palabras = 10 PP.
 */
function ope_pp_for_words($word_count)
{
    $w = max(0, (int) $word_count);
    return (int) floor($w / 100);
}

/**
 * Cuenta palabras reales en texto (ignora BBCode, espacios extra y saltos de línea).
 */
function ope_count_words($text)
{
    // Quitar BBCode básico
    $clean = preg_replace('/\[[^\]]*\]/', ' ', $text);
    // Quitar espacios múltiples y trim
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    if ($clean === '') return 0;
    return count(explode(' ', $clean));
}

/**
 * Obtiene el saldo de PP de un personaje (crea la fila si no existe).
 * Devuelve array con pp_total, pp_gastado, pp_disponible.
 */
function ope_pp_saldo($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);

    if (!$db->table_exists('rol_pp_saldo')) {
        return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);
    }

    $q = $db->simple_select('rol_pp_saldo', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $row = $db->fetch_array($q);
        return array(
            'pp_total'      => (int) $row['pp_total'],
            'pp_gastado'    => (int) $row['pp_gastado'],
            'pp_disponible' => (int) $row['pp_disponible'],
        );
    }

    // Crear fila
    $db->insert_query('rol_pp_saldo', array(
        'pid'           => $pid,
        'pp_total'      => 0,
        'pp_gastado'    => 0,
        'pp_disponible' => 0,
        'last_update'   => TIME_NOW,
    ));
    return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);
}

/**
 * Añade PP a un personaje (por post, misión, staff, etc.).
 * @param int    $pid       Personaje
 * @param int    $pp        Cantidad (positiva)
 * @param string $tipo      'post'|'mision'|'arco'|'evento'|'staff'
 * @param int    $tid       Tema (opcional)
 * @param int    $post_pid  Post que generó el PP (opcional)
 * @param int    $palabras  Palabras contadas (opcional)
 * @param string $notas     Notas (opcional)
 * @param int    $uid_staff UID del staff (0 = automático)
 */
function ope_pp_add($pid, $pp, $tipo = 'post', $tid = 0, $post_pid = 0, $palabras = 0, $notas = '', $uid_staff = 0)
{
    global $db;
    $pid  = (int) $pid;
    $pp   = (int) $pp;
    if ($pid < 1 || $pp < 1) return false;
    if (!$db->table_exists('rol_pp_log') || !$db->table_exists('rol_pp_saldo')) return false;

    // Insertar log
    $db->insert_query('rol_pp_log', array(
        'pid'       => $pid,
        'tid'       => (int) $tid,
        'post_pid'  => (int) $post_pid,
        'palabras'  => (int) $palabras,
        'pp_cambio' => $pp,
        'tipo'      => $db->escape_string($tipo),
        'notas'     => $db->escape_string($notas),
        'uid_staff' => (int) $uid_staff,
        'dateline'  => TIME_NOW,
    ));

    // Actualizar saldo
    $saldo = ope_pp_saldo($pid);
    $nuevo_total = $saldo['pp_total'] + $pp;
    $nuevo_disp  = $saldo['pp_disponible'] + $pp;
    $db->update_query('rol_pp_saldo', array(
        'pp_total'      => $nuevo_total,
        'pp_disponible' => $nuevo_disp,
        'last_update'   => TIME_NOW,
    ), "pid = {$pid}");

    return true;
}

/**
 * Gasta PP de un personaje.
 * @return bool false si saldo insuficiente
 */
function ope_pp_spend($pid, $cost, $tipo = 'gasto_stat', $notas = '')
{
    global $db;
    $pid  = (int) $pid;
    $cost = (int) $cost;
    if ($pid < 1 || $cost < 1) return false;
    if (!$db->table_exists('rol_pp_log') || !$db->table_exists('rol_pp_saldo')) return false;

    $saldo = ope_pp_saldo($pid);
    if ($saldo['pp_disponible'] < $cost) return false;

    // Insertar log (negativo)
    $db->insert_query('rol_pp_log', array(
        'pid'       => $pid,
        'tid'       => 0,
        'post_pid'  => 0,
        'palabras'  => 0,
        'pp_cambio' => -$cost,
        'tipo'      => $db->escape_string($tipo),
        'notas'     => $db->escape_string($notas),
        'uid_staff' => 0,
        'dateline'  => TIME_NOW,
    ));

    // Actualizar saldo
    $nuevo_gastado = $saldo['pp_gastado'] + $cost;
    $nuevo_disp    = $saldo['pp_disponible'] - $cost;
    $db->update_query('rol_pp_saldo', array(
        'pp_gastado'    => $nuevo_gastado,
        'pp_disponible' => $nuevo_disp,
        'last_update'   => TIME_NOW,
    ), "pid = {$pid}");

    return true;
}

/**
 * Hook: cuenta palabras de cada post y asigna PP automáticamente.
 * Se ejecuta tras snapshot_post para no interferir.
 */
function ope_pp_on_post(&$dh)
{
    global $db;

    if (!$db->table_exists('rol_pp_log') || !$db->table_exists('rol_pp_saldo')) {
        return $dh;
    }

    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);
    if ($visible !== 1) return $dh;

    $post_pid = (int) ($dh->pid ?? 0);
    if ($post_pid < 1) return $dh;

    $uid  = (int) ($dh->data['uid'] ?? 0);
    $tid  = (int) ($dh->data['tid'] ?? ($dh->post_insert_data['tid'] ?? 0));

    // No contar PP para OPE Eternal ni posts del sistema
    if ($uid === ope_system_uid()) return $dh;

    // Personaje que firma el post (estampado en insert_post)
    $char_pid = (int) ($dh->post_insert_data['ope_pid'] ?? 0);
    if ($char_pid < 1) {
        $char_pid = ope_rol_active_pid_for($uid);
    }
    if ($char_pid < 1) return $dh;

    // NPCs y personajes sistema no ganan PP
    if ($db->table_exists('rol_personajes')) {
        $cq = $db->simple_select('rol_personajes', 'es_npc, uid', "pid = {$char_pid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $crow = $db->fetch_array($cq);
            if ((int) ($crow['es_npc'] ?? 0) === 1) return $dh;
            if ((int) ($crow['uid'] ?? 0) === ope_system_uid()) return $dh;
        }
    }

    // Idempotente: no duplicar PP para el mismo post
    $exists = $db->simple_select('rol_pp_log', 'log_id', "post_pid = {$post_pid} AND tipo = 'post'", array('limit' => 1));
    if ($db->num_rows($exists)) return $dh;

    // Contar palabras del mensaje
    $message = (string) ($dh->post_insert_data['message'] ?? '');
    $words = ope_count_words($message);
    $pp = ope_pp_for_words($words);

    if ($pp > 0) {
        ope_pp_add($char_pid, $pp, 'post', $tid, $post_pid, $words);
    }

    // Procesar racha diaria
    if (function_exists('ope_racha_procesar_post')) {
        ope_racha_procesar_post($char_pid);
    }

    return $dh;
}

/**
 * Compra +1 a un stat con PP (STATS.md).
 * @return array{ok:bool,msg:string,nivel?:int,cost?:int,stat?:string,value?:int}
 */
function ope_pp_buy_stat($pid, $stat_key)
{
    global $db;
    $pid = (int) $pid;
    $stat_key = strtoupper(trim((string) $stat_key));
    $keys = function_exists('ope_rol_stat_keys') ? ope_rol_stat_keys() : array();
    if ($pid < 1 || !in_array($stat_key, $keys, true)) {
        return array('ok' => false, 'msg' => 'Stat no válido.');
    }
    if (!$db->table_exists('rol_personajes')) {
        return array('ok' => false, 'msg' => 'Sistema no disponible.');
    }

    $fields = 'pid, uid, estado, nivel, stats_json, datos';
    if ($db->field_exists('stats_ganados', 'rol_personajes')) $fields .= ', stats_ganados';
    $q = $db->simple_select('rol_personajes', $fields, "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Personaje no encontrado.');
    }
    $pj = $db->fetch_array($q);
    if ((string) ($pj['estado'] ?? '') !== 'aprobado') {
        return array('ok' => false, 'msg' => 'Solo personajes aprobados pueden progresar.');
    }

    $stats = json_decode((string) ($pj['stats_json'] ?? ''), true);
    $datos = json_decode((string) ($pj['datos'] ?? ''), true);
    if (!is_array($datos)) $datos = array();
    if (!is_array($stats) || empty($stats)) {
        $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    }
    if (empty($stats)) {
        return array('ok' => false, 'msg' => 'La ficha no tiene stats.');
    }

    $stats_ganados = (int) ($pj['stats_ganados'] ?? ($datos['stats_ganados'] ?? 0));
    $nivel = ope_rol_nivel_from_stats_comprados($stats_ganados);
    if ($nivel >= 50) {
        return array('ok' => false, 'msg' => 'Nivel 50 · Prestigio: los PP ya no suben stats.');
    }

    $current = ope_rol_stat_num($stats, $stat_key, 1);
    $cap = ope_rol_stat_cap_tramo($nivel);
    if ($current >= $cap) {
        return array('ok' => false, 'msg' => "Tope de {$stat_key} en Tramo " . ope_rol_tramo_romano(ope_rol_tramo($nivel)) . " ({$cap}).");
    }

    $cost = ope_rol_pp_cost_tramo($nivel);
    $saldo = ope_pp_saldo($pid);
    if ($saldo['pp_disponible'] < $cost) {
        return array('ok' => false, 'msg' => "Necesitas {$cost} PP (tienes {$saldo['pp_disponible']}).");
    }

    if (!ope_pp_spend($pid, $cost, 'gasto_stat', "+1 {$stat_key}")) {
        return array('ok' => false, 'msg' => 'No se pudo gastar el PP.');
    }

    $stats[$stat_key] = $current + 1;
    $stats_ganados++;
    $nivel_nuevo = ope_rol_nivel_from_stats_comprados($stats_ganados);

    $datos['stats_efectivas'] = $stats;
    $datos['stats_ganados'] = $stats_ganados;

    $upd = array(
        'stats_json' => $db->escape_string(json_encode($stats, JSON_UNESCAPED_UNICODE)),
        'datos'      => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
        'nivel'      => $nivel_nuevo,
        'lastedit'   => TIME_NOW,
    );
    if ($db->field_exists('stats_ganados', 'rol_personajes')) {
        $upd['stats_ganados'] = $stats_ganados;
    }
    if ($db->field_exists('ps_gastados', 'rol_personajes')) {
        // ps_gastados = puntos de creación; no tocar. stats_ganados es la métrica PP.
    }
    $db->update_query('rol_personajes', $upd, "pid = {$pid}");

    ope_combat_recalc($pid);

    return array(
        'ok'    => true,
        'msg'   => "+1 {$stat_key} (−{$cost} PP).",
        'nivel' => $nivel_nuevo,
        'cost'  => $cost,
        'stat'  => $stat_key,
        'value' => $stats[$stat_key],
    );
}


// ─────────────────────────────────────────────────────────────────────────
// Fórmulas de Combate (One Piece: Eternal — STATS.md)
// ─────────────────────────────────────────────────────────────────────────

/**
 * PV máximos (STATS.md): 50 + RES×10 + FUE×5 + Nivel×15
 */
function ope_combat_calc_pv($stats, $nivel = 1)
{
    $res = ope_rol_stat_num($stats, 'RES', 1);
    $fue = ope_rol_stat_num($stats, 'FUE', 1);
    $nivel = max(1, (int) $nivel);
    return 50 + ($res * 10) + ($fue * 5) + ($nivel * 15);
}

/**
 * Éter máximo (STATS.md): 30 + TEM×10 + INT×5 + Nivel×10
 */
function ope_combat_calc_en($stats, $nivel = 1)
{
    $tem = ope_rol_stat_num($stats, 'TEM', ope_rol_stat_num($stats, 'SIN', 1)); // SIN legado → TEM
    $int = ope_rol_stat_num($stats, 'INT', 1);
    $nivel = max(1, min(50, (int) $nivel));
    return 30 + ($tem * 10) + ($int * 5) + ($nivel * 10);
}

/**
 * Bonus de PA por tramo de nivel (STATS.md).
 */
function ope_combat_pa_bonus($nivel)
{
    if (function_exists('ope_rol_tramo_pa_bonus')) {
        return (int) ope_rol_tramo_pa_bonus($nivel);
    }
    $n = max(1, min(20, (int) $nivel));
    return (int) floor(($n - 1) / 4);
}

/**
 * PA por turno (STATS.md): 3 + floor(AGI/6) + bonus de tramo
 */
function ope_combat_calc_pa($stats, $nivel)
{
    $agi = ope_rol_stat_num($stats, 'AGI', 1);
    $agi_bonus = (int) floor(max(0, $agi) / 6);
    return 3 + $agi_bonus + ope_combat_pa_bonus($nivel);
}

/**
 * Capacidades físicas derivadas (NUMEROS-Y-BALANCE.md §1.2).
 * Movimiento canónico + salto / caída / carga para ficha y rol narrativo.
 *
 * @return array{
 *   movimiento:int, esprint:int, carrera_turno:int,
 *   salto_v:int, salto_h:int, caida_segura:int,
 *   carga_kg:int, levantamiento_kg:int, mitigacion_fis:int
 * }
 */
function ope_combat_calc_fisicas($stats)
{
    $agi = max(0, ope_rol_stat_num($stats, 'AGI', 1));
    $fue = max(0, ope_rol_stat_num($stats, 'FUE', 1));
    $res = max(0, ope_rol_stat_num($stats, 'RES', 1));

    $mov = 5 + (int) floor($agi / 3);

    return array(
        'movimiento'       => $mov,
        'esprint'          => $mov,
        'carrera_turno'    => $mov * 2,
        'salto_v'          => 1 + (int) floor($agi / 5) + (int) floor($fue / 15),
        'salto_h'          => 2 + (int) floor($agi / 4),
        'caida_segura'     => 3 + (int) floor($res / 4) + (int) floor($agi / 8),
        'carga_kg'         => 20 + ($fue * 10),
        'levantamiento_kg' => 40 + ($fue * 20),
        'mitigacion_fis'   => $res,
    );
}

function ope_combat_recalc($pid)
{
    global $db;
    $pid = (int)$pid;
    if ($pid < 1 || !$db->table_exists('rol_personajes')) return false;

    $fields = 'stats_json, nivel, datos';
    if ($db->field_exists('stats_ganados', 'rol_personajes')) {
        $fields .= ', stats_ganados';
    }
    $q = $db->simple_select('rol_personajes', $fields, "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) return false;
    $pj = $db->fetch_array($q);

    $stats_json = (string)($pj['stats_json'] ?? '');
    $stats = json_decode($stats_json, true);
    $datos = json_decode((string)($pj['datos'] ?? ''), true);
    if (!is_array($datos)) $datos = array();
    if (!is_array($stats) || empty($stats)) {
        $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    }
    if (empty($stats)) return false;

    $stats_ganados = (int) ($pj['stats_ganados'] ?? ($datos['stats_ganados'] ?? 0));
    $nivel = function_exists('ope_rol_nivel_from_stats_comprados')
        ? (int) ope_rol_nivel_from_stats_comprados($stats_ganados)
        : max(1, (int) ($pj['nivel'] ?? 1));
    // Columna rango es varchar(4): solo código corto (I–V / P).
    $rango_code = ($nivel >= 50) ? 'P' : ope_rol_tramo_romano(ope_rol_tramo($nivel));
    $rango_lbl = function_exists('ope_rol_nivel_label') ? ope_rol_nivel_label($nivel) : ('Nivel ' . $nivel);

    $pv = ope_combat_calc_pv($stats, $nivel);
    $en = ope_combat_calc_en($stats, $nivel);
    $pa = ope_combat_calc_pa($stats, $nivel);

    $upd = array(
        'pv_max'       => $pv,
        'en_max'       => $en,
        'pa_por_turno' => $pa,
        'nivel'        => $nivel,
    );
    if ($db->field_exists('rango', 'rol_personajes')) {
        $upd['rango'] = $db->escape_string($rango_code);
    }
    $db->update_query('rol_personajes', $upd, "pid = {$pid}");

    return array(
        'pv_max' => $pv,
        'en_max' => $en,
        'pa_por_turno' => $pa,
        'nivel' => $nivel,
        'rango' => $rango_lbl,
        'rango_code' => $rango_code,
    );
}

/**
 * Catálogo completo de estados (de BD o fallback estático).
 */
function ope_combat_estados()
{
    global $db;
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = array();
    if ($db->table_exists('rol_estados')) {
        $q = $db->simple_select('rol_estados', '*', '', array('order_by' => 'estado_key'));
        while ($r = $db->fetch_array($q)) {
            $cache[$r['estado_key']] = $r;
        }
    }
    return $cache;
}

/**
 * Sistema de Heridas localizadas (AV-01).
 * Tipos: leve, grave, critica.
 * Localización: 1=Cabeza, 2=Torso, 3=Brazo izq, 4=Brazo der, 5=Pierna izq, 6=Pierna der.
 */

function ope_combat_herida_partes()
{
    return array(1 => 'Cabeza', 2 => 'Torso', 3 => 'Brazo izquierdo', 4 => 'Brazo derecho', 5 => 'Pierna izquierda', 6 => 'Pierna derecha');
}

/**
 * Determina tipo de herida según % de PV max.
 */
function ope_combat_herida_tipo($dano, $pv_max)
{
    if ($pv_max < 1) return 'sin_herida';
    $pct = ($dano / $pv_max) * 100;
    if ($pct >= 35) return 'critica';
    if ($pct >= 20) return 'grave';
    if ($pct >= 10) return 'leve';
    return 'sin_herida';
}

/**
 * Efectos de herida por tipo.
 */
function ope_combat_herida_efecto($tipo)
{
    $efectos = array(
        'leve'    => array('penalizacion' => -1, 'desc' => '-1 a acciones con esa parte. Cura con descanso breve (30 min).'),
        'grave'   => array('penalizacion' => -2, 'desc' => '-2 a acciones con esa parte. No puedes activar cartas que requieran esa parte. Cura con descanso largo (4h).'),
        'critica' => array('penalizacion' => -3, 'desc' => 'Esa parte no funciona. -3 a todas las acciones. Requiere atención médica.'),
    );
    return isset($efectos[$tipo]) ? $efectos[$tipo] : array('penalizacion' => 0, 'desc' => '');
}

/**
 * Acumulación de heridas en la misma parte.
 * 2 leves → 1 grave, 2 graves → 1 crítica, 2 críticas → inutilizada permanente.
 */
function ope_combat_acumular_heridas($existing, $new_type)
{
    if ($existing === 'critica' && $new_type === 'critica') return 'inutilizada';
    if ($existing === 'grave'   && $new_type === 'grave')   return 'critica';
    if ($existing === 'leve'    && $new_type === 'leve')    return 'grave';
    // Si la nueva es peor, se queda la peor
    $order = array('sin_herida' => 0, 'leve' => 1, 'grave' => 2, 'critica' => 3, 'inutilizada' => 4);
    $ex_val = isset($order[$existing]) ? $order[$existing] : 0;
    $new_val = isset($order[$new_type]) ? $order[$new_type] : 0;
    return $new_val > $ex_val ? $new_type : $existing;
}

/**
 * Aprueba un PJ en revisión: estado→aprobado, otorga 1 PT inicial, cierra trámite.
 *
 * @param int $pid
 * @param int $staff_uid
 * @return array{ok:bool,msg:string}
 */
function ope_rol_pj_aprobar($pid, $staff_uid)
{
    global $db;
    $pid = (int) $pid;
    $staff_uid = (int) $staff_uid;
    if ($pid < 1 || !$db->table_exists('rol_personajes')) {
        return array('ok' => false, 'msg' => 'Personaje no válido.');
    }
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid} AND estado = 'revision'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'No hay personaje en revisión con ese id.');
    }
    $pj = $db->fetch_array($q);
    $upd = array(
        'estado' => 'aprobado',
        'activo' => 1,
        'lastedit' => TIME_NOW,
    );
    if ($db->field_exists('pt_disponibles', 'rol_personajes')) {
        $pt = (int) ($pj['pt_disponibles'] ?? 0);
        $upd['pt_disponibles'] = max(0, $pt) + 1;
    }
    $db->update_query('rol_personajes', $upd, "pid = {$pid}");

    if ($db->table_exists('rol_tramites')) {
        $tr = array(
            'estado' => 'aprobado',
            'lastedit' => TIME_NOW,
        );
        if ($db->field_exists('staff_uid', 'rol_tramites')) {
            $tr['staff_uid'] = $staff_uid;
        }
        if ($db->field_exists('nota_staff', 'rol_tramites')) {
            $tr['nota_staff'] = $db->escape_string('Aprobado · 1 PT inicial concedido');
        }
        $db->update_query('rol_tramites', $tr, "pid = {$pid} AND tipo = 'crear_personaje' AND estado = 'pendiente'");
    }
    return array('ok' => true, 'msg' => 'Personaje aprobado. Se otorgó 1 PT inicial.');
}

/**
 * Rechaza un PJ en revisión con motivo.
 *
 * @param int    $pid
 * @param int    $staff_uid
 * @param string $motivo
 * @return array{ok:bool,msg:string}
 */
function ope_rol_pj_rechazar($pid, $staff_uid, $motivo = '')
{
    global $db;
    $pid = (int) $pid;
    $staff_uid = (int) $staff_uid;
    $motivo = trim((string) $motivo);
    if ($pid < 1 || !$db->table_exists('rol_personajes')) {
        return array('ok' => false, 'msg' => 'Personaje no válido.');
    }
    if ($motivo === '') {
        return array('ok' => false, 'msg' => 'Indica un motivo de rechazo.');
    }
    $q = $db->simple_select('rol_personajes', 'pid', "pid = {$pid} AND estado = 'revision'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'No hay personaje en revisión con ese id.');
    }
    $db->update_query('rol_personajes', array(
        'estado' => 'rechazado',
        'activo' => 0,
        'lastedit' => TIME_NOW,
    ), "pid = {$pid}");

    if ($db->table_exists('rol_tramites')) {
        $tr = array(
            'estado' => 'rechazado',
            'lastedit' => TIME_NOW,
        );
        if ($db->field_exists('staff_uid', 'rol_tramites')) {
            $tr['staff_uid'] = $staff_uid;
        }
        if ($db->field_exists('nota_staff', 'rol_tramites')) {
            $tr['nota_staff'] = $db->escape_string($motivo);
        }
        $db->update_query('rol_tramites', $tr, "pid = {$pid} AND tipo = 'crear_personaje' AND estado = 'pendiente'");
    }
    return array('ok' => true, 'msg' => 'Personaje rechazado.');
}
