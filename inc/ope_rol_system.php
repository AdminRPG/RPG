<?php
/**
 * I-Forge · Funciones del Sistema (OP-Eternal + PP).
 *
 * Incluido desde inc/plugins/ope_rol.php. Usa $db (MyBB) contra tablas mybb_rol_*.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ─────────────────────────────────────────────────────────────────────────
// OP-Eternal: usuario y personaje del sistema
// ─────────────────────────────────────────────────────────────────────────

/** Devuelve el uid de MyBB del bot OP-Eternal. */
function ope_system_uid()
{
    global $db;
    static $uid = null;
    if ($uid !== null) return $uid;

    if ($db->table_exists('users')) {
        $q = $db->simple_select('users', 'uid', "username = 'OP-Eternal'", array('limit' => 1));
        if ($db->num_rows($q)) {
            $uid = (int) $db->fetch_field($q, 'uid');
            return $uid;
        }
    }
    $uid = 0;
    return $uid;
}

/** Devuelve el pid del personaje OP-Eternal. */
function ope_system_pid()
{
    global $db;
    static $pid = null;
    if ($pid !== null) return $pid;

    $suid = ope_system_uid();
    if ($suid > 0 && $db->table_exists('rol_personajes')) {
        $q = $db->simple_select('rol_personajes', 'pid', "uid = {$suid} AND nombre = 'OP-Eternal'", array('limit' => 1));
        if ($db->num_rows($q)) {
            $pid = (int) $db->fetch_field($q, 'pid');
            return $pid;
        }
    }
    $pid = 0;
    return $pid;
}

/**
 * Publica un tema/hilo como OP-Eternal en el foro indicado.
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
        'username'    => 'OP-Eternal',
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
            error_log('OP-Eternal create_thread error: ' . implode(', ', $errors));
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
 * Publica una respuesta como OP-Eternal en un tema existente.
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
        'username' => 'OP-Eternal',
        'message'  => $message,
        'visible'  => 1,
        'options'  => array(),
        'posthash' => md5($sys_uid . time() . rand(0, 99999)),
    ));

    $valid = $dh->validate_post();
    if (!$valid) {
        $errors = $dh->get_errors();
        if (!empty($errors)) {
            error_log('OP-Eternal create_post error: ' . implode(', ', $errors));
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
 * Tabla de PP por número de palabras (ver INI-04).
 * Devuelve [min, max, pp].
 */
function ope_pp_word_table()
{
    return array(
        array(0, 300, 1),
        array(301, 700, 2),
        array(701, 1200, 3),
        array(1201, 999999, 4),
    );
}

/**
 * Calcula PP según el número de palabras.
 */
function ope_pp_for_words($word_count)
{
    foreach (ope_pp_word_table() as $row) {
        if ($word_count >= $row[0] && $word_count <= $row[1]) {
            return (int) $row[2];
        }
    }
    return 1;
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
 * Tabla de costes de stats (INI-04).
 * Devuelve [de_rango, a_rango, coste_pp, coste_acumulado].
 */
function ope_pp_stat_cost_table()
{
    return array(
        array('F', 'E',   5,  5),
        array('E', 'D',  10,  15),
        array('D', 'C',  20,  35),
        array('C', 'B',  35,  70),
        array('B', 'A',  55,  125),
        array('A', 'S',  80,  205),
        array('S', 'SS', 110, 315),
        array('SS','M',  150, 465),
        array('M', 'M+', 200, 665),
    );
}

/**
 * Coste para subir una stat del valor actual al siguiente rango.
 * @deprecated Use ope_rol_stat_upgrade_cost()
 */
function ope_pp_stat_upgrade_cost($current_val)
{
    return ope_rol_stat_upgrade_cost($current_val);
}

/** @deprecated Use ope_rol_rank_from_sum() */
function ope_pp_rank_from_sum($sum)
{
    return ope_rol_rank_from_sum($sum);
}

/** @deprecated Use ope_rol_rank_from_val() */
function ope_pp_rank_from_val($val)
{
    return ope_rol_rank_from_val($val);
}

/** @deprecated Use ope_rol_val_from_rank() */
function ope_pp_val_from_rank($rank)
{
    return ope_rol_val_from_rank($rank);
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

    // No contar PP para OP-Eternal ni posts del sistema
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

    return $dh;
}


// ─────────────────────────────────────────────────────────────────────────
// Fórmulas de Combate (AV-01) — Oleada 2
// ─────────────────────────────────────────────────────────────────────────

/**
 * Tabla de EN máxima por rango del personaje (AV-01).
 */
function ope_combat_en_table()
{
    return array(
        'F' => 20, 'E' => 25, 'D' => 30, 'C' => 40, 'B' => 50,
        'A' => 65, 'S' => 80, 'SS' => 100, 'M' => 130, 'M+' => 170,
    );
}

/**
 * Calcula PV máximos: (FUE + VIG) × 5 + (VOL + CON) × 2
 */
function ope_combat_calc_pv($stats)
{
    $fue = ope_rol_stat_num($stats, 'FUE');
    $vig = ope_rol_stat_num($stats, 'VIG');
    $vol = ope_rol_stat_num($stats, 'VOL');
    $con = ope_rol_stat_num($stats, 'CON');
    return ($fue + $vig) * 5 + ($vol + $con) * 2;
}

/**
 * Calcula EN máxima según el rango del personaje.
 */
function ope_combat_calc_en($rango)
{
    $table = ope_combat_en_table();
    return isset($table[(string) $rango]) ? $table[(string) $rango] : 20;
}

/**
 * Bono de PA por rango (AV-01).
 */
function ope_combat_pa_bonus($rango)
{
    $map = array(
        'F' => 0, 'E' => 0, 'D' => 0,   // Novato
        'C' => 1, 'B' => 1,              // Oficial
        'A' => 2, 'S' => 2,              // Élite
        'SS' => 3, 'M' => 3, 'M+' => 3,  // Leyenda
    );
    return isset($map[(string) $rango]) ? $map[(string) $rango] : 0;
}

/**
 * Calcula PA por turno: AGI + max(INT, ING, CAR) + bono_rango
 */
function ope_combat_calc_pa($stats, $rango)
{
    $agi = ope_rol_stat_num($stats, 'AGI');
    $int = ope_rol_stat_num($stats, 'INT');
    $ing = ope_rol_stat_num($stats, 'ING');
    $car = ope_rol_stat_num($stats, 'CAR');
    $bonus = ope_combat_pa_bonus($rango);
    return $agi + max($int, $ing, $car) + $bonus;
}

/**
 * Recalcula y guarda PV, EN, PA del personaje en BD.
 * Llamar después de cualquier cambio de stats.
 */
function ope_combat_recalc($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !$db->table_exists('rol_personajes')) return false;

    $q = $db->simple_select('rol_personajes', 'datos, rango', "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) return false;
    $pj = $db->fetch_array($q);

    $datos = json_decode((string) $pj['datos'], true);
    if (!is_array($datos)) $datos = array();
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    $rango = ope_rol_rank_from_sum(ope_rol_stat_sum($stats));

    $pv = ope_combat_calc_pv($stats);
    $en = ope_combat_calc_en($rango);
    $pa = ope_combat_calc_pa($stats, $rango);

    $db->update_query('rol_personajes', array(
        'pv_max'       => $pv,
        'en_max'       => $en,
        'pa_por_turno' => $pa,
        'rango'        => $db->escape_string($rango),
    ), "pid = {$pid}");

    return array('pv_max' => $pv, 'en_max' => $en, 'pa_por_turno' => $pa, 'rango' => $rango);
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
