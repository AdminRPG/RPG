<?php
/**
 * One Piece: Eternal · Sistema de Haki
 * Canon: Eternal-Sistema/docs/02-HAKI-Y-FRUTAS/SISTEMA-DE-HAKI.md
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_haki_catalogo()
{
    static $cat = null;
    if ($cat !== null) {
        return $cat;
    }
    $path = dirname(__FILE__) . '/../../ope_haki/catalogo.json';
    $raw = is_file($path) ? file_get_contents($path) : false;
    $cat = ($raw !== false) ? json_decode($raw, true) : array();
    if (!is_array($cat)) {
        $cat = array();
    }
    return $cat;
}

function ope_haki_carta($id)
{
    $cat = ope_haki_catalogo();
    $id = (string) $id;
    return isset($cat['cartas'][$id]) ? $cat['cartas'][$id] : null;
}

function ope_haki_cartas_desbloqueadas($tipo, $nivel)
{
    $cat = ope_haki_catalogo();
    $out = array();
    $nivel = (int) $nivel;
    $tipo = (string) $tipo;
    foreach (($cat['cartas'] ?? array()) as $id => $c) {
        if (($c['tipo'] ?? '') === $tipo && (int) ($c['nivel_min'] ?? 99) <= $nivel) {
            $out[$id] = $c;
        }
    }
    return $out;
}

function ope_haki_tipos()
{
    return array('ken', 'buso', 'hao');
}

function ope_haki_label($tipo)
{
    $cat = ope_haki_catalogo();
    return (string) ($cat['labels'][$tipo] ?? $tipo);
}

function ope_haki_secundario($tipo)
{
    $cat = ope_haki_catalogo();
    return (string) ($cat['secundario'][$tipo] ?? 'VOL');
}

/**
 * Potencia = floor((VOL + secundario) / 8), mínimo 1 si despertado/nivel≥1.
 */
function ope_haki_potencia($stats, $tipo, $activo = true)
{
    if (!$activo) {
        return 0;
    }
    $vol = function_exists('ope_rol_stat_num') ? ope_rol_stat_num($stats, 'VOL', 1) : 1;
    $sec = ope_haki_secundario($tipo);
    $sec_v = function_exists('ope_rol_stat_num') ? ope_rol_stat_num($stats, $sec, 1) : 1;
    return max(1, (int) floor(($vol + $sec_v) / 8));
}

function ope_haki_row($pid, $tipo)
{
    global $db;
    $pid = (int) $pid;
    $tipo = (string) $tipo;
    if ($pid < 1 || !function_exists('ope7_tabla_existe') || !ope7_tabla_existe('haki')) {
        return null;
    }
    // D6.3: tipos canónicos mybb_ope_haki (armadura/mantra/conquistador).
    $tipo_canon = array('ken' => 'mantra', 'buso' => 'armadura', 'hao' => 'conquistador');
    $tc = isset($tipo_canon[$tipo]) ? $tipo_canon[$tipo] : $tipo;
    $q = $db->simple_select('ope_haki', '*', "personaje_id = {$pid} AND tipo = '" . $db->escape_string($tc) . "' AND activo = 1", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $r = $db->fetch_array($q);
    $nivel = (int) ($r['nivel'] ?? 0);
    return array(
        'pid'        => $pid,
        'tipo'       => $tipo,
        'nivel'      => $nivel,
        'cu'         => 0,
        'pp_gastado' => (int) ($r['pp_invertidos'] ?? 0),
        'despertado' => $nivel >= 1 ? 1 : 0,
        'origen'     => '',
        'dateline'   => TIME_NOW,
        'lastedit'   => TIME_NOW,
    );
}

function ope_haki_all($pid)
{
    $out = array();
    foreach (ope_haki_tipos() as $t) {
        $row = ope_haki_row($pid, $t);
        $out[$t] = $row ? $row : array(
            'pid' => (int) $pid,
            'tipo' => $t,
            'nivel' => 0,
            'cu' => 0,
            'pp_gastado' => 0,
            'despertado' => ($t === 'hao') ? 0 : 0,
            'origen' => '',
        );
    }
    return $out;
}

function ope_haki_nivel_def($tipo, $nivel)
{
    $cat = ope_haki_catalogo();
    $nivel = (string) (int) $nivel;
    return isset($cat['niveles'][$tipo][$nivel]) ? $cat['niveles'][$tipo][$nivel] : null;
}

/**
 * ¿Puede comprar el siguiente nivel? (autoservicio Ken/Buso; Hao solo si despertado).
 * Ken Nv.1 en T1 (nivel 8–10) requiere trámite ken_t1 — no autoservicio.
 */
function ope_haki_can_level($pid, $tipo, $stats = null, $nivel_pj = null)
{
    global $db;
    $pid = (int) $pid;
    $tipo = (string) $tipo;
    if (!in_array($tipo, ope_haki_tipos(), true)) {
        return array('ok' => false, 'msg' => 'Tipo de Haki no válido.');
    }
    // D6.3: fuente canónica mybb_ope_personajes.
    if (!ope7_tabla_existe('personajes')) {
        return array('ok' => false, 'msg' => 'Sistema Haki no disponible.');
    }

    $pq = $db->simple_select(ope7_tabla('personajes'), 'id, estado, nivel, datos', "id = {$pid}", array('limit' => 1));
    if (!$db->num_rows($pq)) {
        return array('ok' => false, 'msg' => 'Personaje no encontrado.');
    }
    $pj = $db->fetch_array($pq);
    if ((string) ($pj['estado'] ?? '') !== 'aprobado') {
        return array('ok' => false, 'msg' => 'Solo personajes aprobados.');
    }

    if ($stats === null) {
        $datos = json_decode((string) ($pj['datos'] ?? ''), true);
        $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();
    }
    if ($nivel_pj === null) {
        $nivel_pj = max(1, (int) ($pj['nivel'] ?? 1));
    }
    $tramo = function_exists('ope_rol_tramo') ? ope_rol_tramo($nivel_pj) : 1;

    $row = ope_haki_row($pid, $tipo);
    $nivel_actual = $row ? (int) $row['nivel'] : 0;
    $cu = $row ? (int) $row['cu'] : 0;
    $despertado = $row ? (int) $row['despertado'] : 0;

    $siguiente = $nivel_actual + 1;
    $def = ope_haki_nivel_def($tipo, $siguiente);
    if (!$def) {
        return array('ok' => false, 'msg' => 'Nivel máximo alcanzado.');
    }

    if ($tipo === 'hao' && $despertado < 1 && $siguiente === 1) {
        return array('ok' => false, 'msg' => 'Haoshoku aún no despertado. Abre el trámite de despertar.', 'tramite' => 'hao_despertar');
    }
    if ($tipo === 'hao' && $despertado < 1) {
        return array('ok' => false, 'msg' => 'Haoshoku no despertado.');
    }

    // Ken T1 excepcional: Nv.1 con nivel 8–10 → trámite
    if ($tipo === 'ken' && $siguiente === 1 && $nivel_pj >= 8 && $nivel_pj <= 10 && $tramo === 1) {
        return array('ok' => false, 'msg' => 'Ken en Tramo I requiere trámite (trauma validado).', 'tramite' => 'ken_t1');
    }
    // Ken Nv.1 normal: T2+
    if ($tipo === 'ken' && $siguiente === 1 && $tramo < 2 && $nivel_pj < 11) {
        return array('ok' => false, 'msg' => 'Ken Nv.1 requiere Tramo II (Nv.11+) o trámite T1.');
    }

    if ($tipo === 'buso' && $siguiente === 1 && ($tramo < 2 || $nivel_pj < 11)) {
        return array('ok' => false, 'msg' => 'Buso Nv.1 requiere Tramo II y Nv.11+.');
    }

    if (!empty($def['requiere_d'])) {
        // Voluntad D: buscar en datos.dotes o similar; si no hay, bloquear
        $datos = json_decode((string) ($pj['datos'] ?? ''), true);
        $d_nivel = (int) ($datos['voluntad_d'] ?? ($datos['dotes']['voluntad_d'] ?? 0));
        if ($d_nivel < 1) {
            return array('ok' => false, 'msg' => 'Hao Nv.6 requiere Voluntad D ≥ Nv.1.');
        }
    }

    if ($tramo < (int) ($def['tramo'] ?? 1) || $nivel_pj < (int) ($def['nivel_pj'] ?? 1)) {
        return array('ok' => false, 'msg' => 'Tramo o nivel de PJ insuficiente.');
    }
    if ($cu < (int) ($def['cu'] ?? 0)) {
        return array('ok' => false, 'msg' => 'CU insuficiente (' . $cu . '/' . (int) $def['cu'] . ').');
    }

    $pp = (int) ($def['pp'] ?? 0);
    $saldo = function_exists('ope_pp_saldo') ? ope_pp_saldo($pid) : array('pp_disponible' => 0);
    if ((int) $saldo['pp_disponible'] < $pp) {
        return array('ok' => false, 'msg' => "Necesitas {$pp} PP.");
    }

    return array(
        'ok' => true,
        'msg' => 'Dominio disponible.',
        'siguiente' => $siguiente,
        'nombre' => (string) ($def['nombre'] ?? ''),
        'pp' => $pp,
        'cu' => $cu,
        'cu_req' => (int) ($def['cu'] ?? 0),
    );
}

/**
 * Resumen para ficha UI.
 */
function ope_haki_ficha_block($pid, $stats, $nivel_pj)
{
    $all = ope_haki_all($pid);
    $out = array();
    foreach (ope_haki_tipos() as $t) {
        $row = $all[$t];
        $nivel = (int) ($row['nivel'] ?? 0);
        $activo = $nivel >= 1 || ($t === 'hao' && (int) ($row['despertado'] ?? 0) === 1);
        $pot = ope_haki_potencia($stats, $t, $activo && $nivel >= 1);
        $def = $nivel > 0 ? ope_haki_nivel_def($t, $nivel) : null;
        $next = ope_haki_can_level($pid, $t, $stats, $nivel_pj);
        $prox_def = ope_haki_nivel_def($t, $nivel + 1);
        $out[$t] = array(
            'label' => ope_haki_label($t),
            'nivel' => $nivel,
            'nombre_nivel' => $def ? (string) $def['nombre'] : '—',
            'cu' => (int) ($row['cu'] ?? 0),
            'cu_prox' => $prox_def ? (int) ($prox_def['cu'] ?? 0) : null,
            'pp_gastado' => (int) ($row['pp_gastado'] ?? 0),
            'potencia' => $pot,
            'secundario' => ope_haki_secundario($t),
            'despertado' => (int) ($row['despertado'] ?? 0),
            'origen' => (string) ($row['origen'] ?? ''),
            'cartas' => ope_haki_cartas_desbloqueadas($t, $nivel),
            'dominio' => $next,
        );
    }
    return $out;
}

/**
 * HTML compacto de carta Haki para el pie de post.
 */
function ope_haki_carta_html(array $carta, $potencia = 1)
{
    $nombre = htmlspecialchars_uni((string) ($carta['nombre'] ?? $carta['id']));
    $tipo = htmlspecialchars_uni((string) ($carta['tipo'] ?? 'ken'));
    $pa = (int) ($carta['pa'] ?? 0);
    $en = (int) ($carta['en_base'] ?? 0) + ((int) ($carta['en_pot'] ?? 0) * max(1, (int) $potencia));
    $id = htmlspecialchars_uni((string) ($carta['id'] ?? ''));
    return '<div class="ope-tk-card ope-tk-card--haki ope-tk-card--' . $tipo . '" data-carta="' . $id . '">'
        . '<div class="ope-tk-card-h"><span class="ope-tk-tier">Haki</span><b>' . $nombre . '</b></div>'
        . '<div class="ope-tk-card-b"><span class="ope-tk-cost">EN ' . $en . ($pa ? ' · PA ' . $pa : '') . '</span></div>'
        . '</div>';
}
