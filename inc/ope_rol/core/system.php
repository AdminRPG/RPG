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

/**
 * Obtiene el saldo de PP de un personaje (crea la fila si no existe).
 * Devuelve array con pp_total, pp_gastado, pp_disponible.
 */
function ope_pp_saldo($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);

    if (!$db->table_exists('ope_pp_saldo')) {
        return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);
    }

    $q = $db->simple_select('ope_pp_saldo', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $row = $db->fetch_array($q);
        return array(
            'pp_total'      => (int) $row['pp_total'],
            'pp_gastado'    => (int) $row['pp_gastado'],
            'pp_disponible' => (int) $row['pp_disponible'],
        );
    }

    // Crear fila
    $db->insert_query('ope_pp_saldo', array(
        'pid'           => $pid,
        'pp_total'      => 0,
        'pp_gastado'    => 0,
        'pp_disponible' => 0,
        'last_update'   => TIME_NOW,
    ));
    return array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);
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
    if (!$db->table_exists('ope_pp_log') || !$db->table_exists('ope_pp_saldo')) return false;

    $saldo = ope_pp_saldo($pid);
    if ($saldo['pp_disponible'] < $cost) return false;

    // Insertar log (negativo)
    $db->insert_query('ope_pp_log', array(
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
    $db->update_query('ope_pp_saldo', array(
        'pp_gastado'    => $nuevo_gastado,
        'pp_disponible' => $nuevo_disp,
        'last_update'   => TIME_NOW,
    ), "pid = {$pid}");

    return true;
}


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


/**
 * Catálogo completo de estados (de BD o fallback estático).
 * D6.3: fuente canónica mybb_ope_estados (34 estados del Anexo A.1). La clave
 * se normaliza como en el esquema viejo (estado_key): 'Quemadura I' →
 * 'quemadura', y se indexa también por id y por nombre literal.
 */
function ope_combat_estados()
{
    global $db;
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = array();
    if (!$db->table_exists('ope_estados')) {
        return $cache;
    }
    $q = $db->simple_select('ope_estados', '*', 'activo = 1', array('order_by' => 'nombre'));
    while ($r = $db->fetch_array($q)) {
        $r['tipo'] = (string) ($r['categoria'] ?? 'fisico');
        $key = mb_strtolower((string) $r['nombre'], 'UTF-8');
        $key = strtr($key, array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'));
        $key = trim(preg_replace('/\s*(i{1,3}|iv|v)\s*$/', '', $key));
        if ($key !== '') {
            $cache[$key] = $r;
        }
        $cache[(string) $r['id']] = $r;
        $cache[(string) $r['nombre']] = $r;
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


