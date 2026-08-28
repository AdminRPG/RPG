<?php
/**
 * One Piece: 7 Seas · Progresión y calendario on-roll (F3.0)
 * -----------------------------------------------------------
 * El reloj del foro (5.6/7.7): 1 día real = 2 días on-roll. El avance es
 * perezoso e idempotente (D3.1): con el primer visitante del día, o al pedir
 * la fecha del foro, `calendario_foro` avanza +2 días por día real transcurrido
 * y registra el avance. También cierra el ciclo de la compra de bloques (7.3):
 * al vencer el cronómetro los puntos entran en la reserva, se cuentan para el
 * nivel (10 comprados = nivel+1 con arrastre) y el jugador los coloca donde
 * quiera con `ope7_pj_colocar_reserva`.
 *
 * Decisiones: D3.1 (avance perezoso en hook), D3.3 (semilla = hoy, reiniciable
 * borrando la fila), D3.5 (corrección del flujo de reserva al manual 7.3).
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Semilla idempotente del calendario (D3.3): si no hay fila, el mundo arranca
 * hoy (fecha real = fecha on-roll inicial, ratio 2). Para reiniciar el reloj:
 * borrar la fila de `ope_calendario_foro` y volver a llamar.
 */
function ope7_calendario_semilla()
{
    global $db;
    if (!ope7_tabla_existe('calendario_foro')) {
        return null;
    }
    $q = $db->simple_select('ope_calendario_foro', 'fecha_foro_actual', '1=1', array('limit' => 1));
    if ($db->num_rows($q)) {
        return (string) $db->fetch_field($q, 'fecha_foro_actual');
    }
    $hoy = date('Y-m-d');
    $db->insert_query('ope_calendario_foro', array(
        'fecha_foro_actual'        => $hoy,
        'ratio'                    => 2.00,
        'ultima_actualizacion_real' => TIME_NOW,
        'avances'                  => json_encode(array(), JSON_UNESCAPED_UNICODE),
    ));
    return $hoy;
}

/**
 * Avance perezoso del calendario (D3.1): +2 días on-roll por día real
 * transcurrido desde la última actualización. Idempotente: no hace nada si ya
 * se avanzó hoy. Devuelve la fecha on-roll actual tras el avance.
 */
function ope7_calendario_avanzar()
{
    global $db;
    if (!ope7_tabla_existe('calendario_foro')) {
        return date('Y-m-d');
    }
    $fecha = ope7_calendario_semilla();
    if ($fecha === null) {
        return date('Y-m-d');
    }
    $q = $db->simple_select('ope_calendario_foro', '*', '1=1', array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'DESC'));
    $row = $db->fetch_array($q);
    if (!$row) {
        return date('Y-m-d');
    }
    $ultima = (int) ($row['ultima_actualizacion_real'] ?? 0);
    $dias = $ultima > 0 ? (int) floor((TIME_NOW - $ultima) / 86400) : 0;
    if ($dias < 1) {
        return (string) $row['fecha_foro_actual'];
    }
    $desde = (string) $row['fecha_foro_actual'];
    $ratio = (float) ($row['ratio'] ?? 2.00);
    $pasos = (int) round($dias * $ratio);
    $hasta = date('Y-m-d', strtotime($desde . ' +' . $pasos . ' days'));
    $avances = json_decode((string) ($row['avances'] ?? '[]'), true);
    if (!is_array($avances)) {
        $avances = array();
    }
    $avances[] = array(
        'desde'      => $desde,
        'hasta'      => $hasta,
        'dias_real'  => $dias,
        'pasos_onroll' => $pasos,
        'fecha_real' => date('Y-m-d H:i'),
    );
    $db->update_query('ope_calendario_foro', array(
        'fecha_foro_actual'         => $hasta,
        'ultima_actualizacion_real' => TIME_NOW,
        'avances'                   => json_encode($avances, JSON_UNESCAPED_UNICODE),
    ), "id = " . (int) $row['id']);
    return $hasta;
}

/**
 * Finaliza los entrenamientos de atributos vencidos (7.3): al terminar el
 * cronómetro los puntos del bloque entran en la reserva, se suman a
 * `puntos_comprados` (10 → nivel+1 con arrastre) y se recalcula la ficha.
 * Llamado por el hook global_start. Devuelve cuántos personajes avanzaron.
 */
function ope7_pj_finalizar_entrenamientos()
{
    global $db;
    if (!ope7_tabla_existe('personajes')) {
        return 0;
    }
    $q = $db->simple_select('ope_personajes', 'id, reserva, puntos_comprados, nivel, entrenamiento_fin, entrenamiento_bloque',
        "entrenamiento_fin > 0 AND entrenamiento_fin <= " . TIME_NOW, array('limit' => 200));
    $n = 0;
    while ($f = $db->fetch_array($q)) {
        $id = (int) $f['id'];
        $bloque = (int) ($f['entrenamiento_bloque'] ?? 0);
        if ($bloque < 1) {
            $bloque = 5;
        }
        $reserva = (int) $f['reserva'] + $bloque;
        $comprados = (int) $f['puntos_comprados'] + $bloque;
        $nivel = (int) $f['nivel'];
        $subio = false;
        if ($comprados >= 10) {
            $nivel = min(50, $nivel + (int) floor($comprados / 10));
            $comprados = $comprados % 10;
            $subio = true;
        }
        $db->update_query('ope_personajes', array(
            'reserva'           => $reserva,
            'puntos_comprados'  => $comprados,
            'nivel'             => $nivel,
            'entrenamiento_fin' => 0,
            'entrenamiento_bloque' => 0,
        ), "id = {$id}");
        ope7_pj_recalcular_secundarios($id);
        if (function_exists('ope7_notificar')) {
            ope7_notificar($id, 'Entrenamiento completado: +' . $bloque . ' puntos en tu reserva' . ($subio ? ' · ¡NIVEL ' . $nivel . '!' : '') . '. Colócalos desde tu ficha.');
        }
        $n++;
    }
    return $n;
}

/**
 * Coloca puntos de la reserva en atributos (7.3: «colocarlos donde quieras»).
 * Valida reserva suficiente, techos por nivel y que el total no exceda la
 * reserva. Devuelve array('ok' => bool, 'msg' => ...).
 */
function ope7_pj_colocar_reserva($pid, array $distribucion)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return array('ok' => false, 'msg' => 'Personaje no válido.');
    }
    $ATR = array('fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol');
    $total = 0;
    $dest = array();
    foreach ($distribucion as $atr => $pts) {
        $atr = (string) $atr;
        $pts = (int) $pts;
        if (!in_array($atr, $ATR, true) || $pts < 0) {
            return array('ok' => false, 'msg' => "Atributo inválido: {$atr}.");
        }
        if ($pts > 0) {
            $dest[$atr] = $pts;
            $total += $pts;
        }
    }
    if ($total < 1) {
        return array('ok' => false, 'msg' => 'No has indicado qué puntos colocar.');
    }
    $q = $db->simple_select('ope_personajes', 'nivel, reserva, ' . implode(',', $ATR), "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return array('ok' => false, 'msg' => 'Personaje no encontrado.');
    }
    if ($total > (int) $f['reserva']) {
        return array('ok' => false, 'msg' => 'Reserva insuficiente: quieres ' . $total . ' y tienes ' . (int) $f['reserva'] . '.');
    }
    $techo = ope7_pj_techo_atributo((int) $f['nivel']);
    $set = array();
    foreach ($dest as $atr => $pts) {
        $nuevo = (int) $f[$atr] + $pts;
        if ($nuevo > $techo) {
            return array('ok' => false, 'msg' => strtoupper($atr) . ' superaría el techo del nivel (' . $techo . ').');
        }
        $set[$atr] = $nuevo;
    }
    $set['reserva'] = (int) $f['reserva'] - $total;
    $db->update_query('ope_personajes', $set, "id = {$pid}");
    ope7_pj_recalcular_secundarios($pid);
    $detalle = array();
    foreach ($dest as $atr => $pts) {
        $detalle[] = '+' . $pts . ' ' . strtoupper($atr);
    }
    return array('ok' => true, 'msg' => 'Reserva colocada: ' . implode(', ', $detalle) . ' (restan ' . (int) $set['reserva'] . ').');
}

/**
 * Registra la ejecución de un cron en `cron_log` (F6.5, panel «Progresión»).
 * Ejecuta $fn, captura su retorno (un int de acciones o un array) y guarda
 * cuándo corrió y cuánto automatizó. Idempotente: solo inserta/actualiza la
 * fila del cron. Devuelve el valor que devolvió $fn.
 */
function ope7_cron_tick($cron, $etiqueta = '')
{
    global $db;
    $acciones = 0;
    $detalle = '';
    if (is_callable($cron)) {
        $res = $cron();
        if (is_array($res)) {
            $acciones = (int) ($res['acciones'] ?? 0);
            if (empty($acciones) && isset($res['propuestas'], $res['aplicadas'])) {
                $acciones = (int) $res['propuestas'] + (int) $res['aplicadas'];
            }
            $detalle = trim(implode(' · ', array_map(function ($v, $k) {
                return (string) $k . '=' . (is_scalar($v) ? $v : 'n');
            }, $res, array_keys($res))));
        } elseif (is_scalar($res)) {
            $acciones = (int) $res;
            $detalle = $acciones > 0 ? ($etiqueta !== '' ? $etiqueta . ': ' : '') . $acciones . ' acción/es' : '';
        }
    }
    if (ope7_tabla_existe('cron_log')) {
        $existe = $db->simple_select(ope7_tabla('cron_log'), 'cron', "cron = '" . $db->escape_string((string) $etiqueta) . "'", array('limit' => 1));
        $nombre = $etiqueta !== '' ? $etiqueta : (is_string($cron) ? $cron : 'cron');
        if ($db->num_rows($existe)) {
            $db->update_query(ope7_tabla('cron_log'), array(
                'ultima_run' => TIME_NOW,
                'acciones'   => $acciones,
                'detalle'    => $db->escape_string((string) $detalle),
            ), "cron = '" . $db->escape_string((string) $nombre) . "'");
        } else {
            $db->insert_query(ope7_tabla('cron_log'), array(
                'cron'       => $nombre,
                'ultima_run' => TIME_NOW,
                'acciones'   => $acciones,
                'detalle'    => $db->escape_string((string) $detalle),
            ));
        }
    }
    return $acciones;
}

/**
 * Cron perezoso de progresión (hook global_start): avanza el calendario y
 * finaliza entrenamientos vencidos. Idempotente y barato (una lectura + 0-1
 * escrituras por día real). F6.5: cada subcron queda registrado en cron_log
 * para que el panel «Progresión» muestre qué automatizó (última ejecución,
 * acciones y pendientes detectados).
 */
function ope7_progresion_cron()
{
    ope7_cron_tick(function () { ope7_calendario_avanzar(); return 0; }, 'Calendario');
    ope7_cron_tick('ope7_pj_finalizar_entrenamientos', 'Entrenamientos');
    ope7_cron_tick('ope7_pj_finalizar_dominios', 'Dominios');
    // F4.3: travesías cuyo plazo (17.5) se agotó sin cierre → vencidas.
    ope7_cron_tick('ope7_travesias_vencidas', 'Travesías vencidas');
    // F4.3: conquista — mantenimientos de unidades y abandono de asedios.
    if (function_exists('ope7_conquista_mantenimientos')) {
        ope7_cron_tick('ope7_conquista_mantenimientos', 'Conquista · mantenimientos');
    }
    if (function_exists('ope7_conquista_abandonos')) {
        ope7_cron_tick('ope7_conquista_abandonos', 'Conquista · abandonos');
    }
    // F5.1 (20.1): despertar automático de Armadura/Mantra a nv10.
    ope7_cron_tick('ope7_haki_auto_despertar_cron', 'Haki · auto-despertar');
    ope7_cron_tick('ope7_misiones_ronda_cerrar', 'Misiones vencidas');
    // F5.3 (22.9): tripulaciones con <2 activos → aviso/disolución.
    ope7_cron_tick('ope7_tripulaciones_ronda_cerrar', 'Tripulaciones');
    // F5.4 (23.3): mantenimiento de implantes → averiados sin saldo.
    ope7_cron_tick('ope7_implantes_ronda_mantenimiento', 'Implantes · mantenimiento');
    // F6 (14.6): caducidad de paraderos de carteles.
    ope7_cron_tick('ope7_bajomundo_cron', 'Bajo mundo · paraderos');
}

/**
 * Finaliza los cronómetros de dominios vencidos (5.3/4.4): al terminar los
 * 15 días, el dominio sube al nivel objetivo (entrenamiento_nivel) y el
 * cronómetro se limpia. Independiente del de atributos (4.4). Idempotente.
 * Devuelve cuántos dominios terminaron.
 */
function ope7_pj_finalizar_dominios()
{
    global $db;
    if (!ope7_tabla_existe('dominios_personaje')) {
        return 0;
    }
    $q = $db->simple_select('ope_dominios_personaje', 'id, personaje_id, dominio_id, nivel, entrenamiento_nivel',
        "entrenamiento_fin > 0 AND entrenamiento_fin <= " . TIME_NOW, array('limit' => 200));
    $n = 0;
    while ($d = $db->fetch_array($q)) {
        $id = (int) $d['id'];
        $nivel = (int) ($d['entrenamiento_nivel'] ?? 0);
        if ($nivel < 1) {
            $nivel = (int) $d['nivel'] + 1;
        }
        $nivel = min(5, $nivel);
        $db->update_query('ope_dominios_personaje', array(
            'nivel'               => $nivel,
            'entrenamiento_fin'   => 0,
            'entrenamiento_nivel' => 0,
        ), "id = {$id}");
        if (function_exists('ope7_notificar')) {
            ope7_notificar((int) $d['personaje_id'], 'Dominio entrenado: sube a nivel ' . $nivel . ' (5.3).');
        }
        $n++;
    }
    return $n;
}

/**
 * Panel staff «Calendario» (Anexo A.3, Staff 7.4/7.5): fecha on-roll actual
 * con su histórico de avances, presentes activos con su ancla y congelados,
 * histórico de aperturas/cierres y avisos de coherencia de pasados.
 * Devuelve HTML; cero <style> y cero estilos inline estáticos.
 */
function ope7_calendario_panel_html()
{
    global $db;
    $html = '';
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };

    $fecha_actual = ope7_calendario_avanzar();
    $cal = array('fecha_foro_actual' => $fecha_actual, 'ratio' => 2.00, 'ultima_actualizacion_real' => 0, 'avances' => array());
    if (ope7_tabla_existe('calendario_foro')) {
        $q = $db->simple_select('ope_calendario_foro', '*', '1=1', array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'DESC'));
        $r = $db->fetch_array($q);
        if ($r) {
            $cal['fecha_foro_actual'] = (string) $r['fecha_foro_actual'];
            $cal['ratio'] = (float) ($r['ratio'] ?? 2.00);
            $cal['ultima_actualizacion_real'] = (int) ($r['ultima_actualizacion_real'] ?? 0);
            $av = json_decode((string) ($r['avances'] ?? '[]'), true);
            $cal['avances'] = is_array($av) ? array_slice(array_reverse($av), 0, 10) : array();
        }
    }

    $html .= '<div class="shead"><h1>Calendario del foro</h1><span class="code">5.6 · on-roll</span><span class="rule"></span></div>';
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Fecha on-roll actual</span><span class="c">1 real = 2 on-roll</span></div><div class="plate-b"><div class="cal-now">'
          . '<div class="cal-now-v">' . $e($cal['fecha_foro_actual']) . '</div>'
          . '<div class="cal-now-meta">ratio ' . number_format((float) $cal['ratio'], 2, ',', '') . ' · última actualización real: ' . ($cal['ultima_actualizacion_real'] > 0 ? date('Y-m-d H:i', $cal['ultima_actualizacion_real']) : '—') . ' · avance automático perezoso (hook global_start)</div></div></div></div>';

    // ── Avisos de coherencia (7.5) ──
    $avisos = array();
    if (ope7_tabla_existe('temas')) {
        $futuros = $db->query('SELECT tid, tipo, fecha_foro FROM ' . ope7_tabla_full('temas') . " WHERE tipo = 'pasado' AND fecha_foro <> ''");
        while ($t = $db->fetch_array($futuros)) {
            if (strtotime((string) $t['fecha_foro']) > strtotime($fecha_actual)) {
                $avisos[] = 'Tema ' . (int) $t['tid'] . ' (pasado) anclado en el futuro: ' . $e($t['fecha_foro']) . ' > ' . $fecha_actual . ' — revisar (7.7).';
            }
        }
        // Presentes activos cuya duración on-roll excede la ventana real ×2 (abandono / incoherencia).
        $presentes = $db->query('SELECT tid, fecha_foro, fecha_real_apertura FROM ' . ope7_tabla_full('temas') . " WHERE tipo = 'presente' AND estado = 'abierto'");
        while ($t = $db->fetch_array($presentes)) {
            $onroll = strtotime($fecha_actual) - strtotime((string) $t['fecha_foro']);
            $real = TIME_NOW - (int) $t['fecha_real_apertura'];
            if ($onroll > 0 && $real > 0 && $onroll > $real * 2 + 15 * 86400) {
                $avisos[] = 'Presente ' . (int) $t['tid'] . ' anclado el ' . $e($t['fecha_foro']) . ' lleva más on-roll que la ventana real ×2 — revisar duración/abandono (7.5).';
            }
        }
    }
    if ($avisos) {
        $html .= '<div class="plate cal-warn"><div class="plate-h"><span class="t">Avisos de coherencia</span><span class="c">7.5 · 7.7</span></div><div class="plate-b">';
        foreach ($avisos as $a) {
            $html .= '<div class="cal-aviso">⚠ ' . $a . '</div>';
        }
        $html .= '</div></div>';
    } else {
        $html .= '<div class="plate"><div class="plate-h"><span class="t">Avisos de coherencia</span><span class="c">7.5 · 7.7</span></div><div class="plate-b"><div class="f7-empty">Sin avisos: todos los temas son coherentes con el calendario.</div></div></div>';
    }

    // ── Presentes activos ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Presentes activos</span><span class="c">ancla y congelados</span></div><div class="plate-b">';
    if (ope7_tabla_existe('temas') && ope7_tabla_existe('temas_participantes')) {
        $q = $db->query('SELECT t.tid, t.fecha_foro, t.zona, t.tema_tipo, t.fecha_real_apertura FROM ' . ope7_tabla_full('temas') . " t WHERE t.tipo = 'presente' AND t.estado = 'abierto' ORDER BY t.fecha_foro");
        $n = 0;
        while ($t = $db->fetch_array($q)) {
            $n++;
            $html .= '<div class="cal-tema"><div class="cal-tema-h">'
                  . '<b>TEMA ' . (int) $t['tid'] . '</b> · ancla ' . $e($t['fecha_foro']) . ' · ' . $e($t['tema_tipo']) . ($t['zona'] !== '' ? ' · ' . $e($t['zona']) : '') . '</div>';
            $pq = $db->query('SELECT tp.congelado_desde, tp.ficha_instantanea, p.nombre FROM ' . ope7_tabla_full('temas_participantes') . ' tp '
                . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tp.personaje_id WHERE tp.tema_id = ' . (int) $t['tid'] . ' ORDER BY tp.id');
            $html .= '<div class="cal-congelados">';
            while ($p = $db->fetch_array($pq)) {
                $html .= '<span class="cal-congelado">' . $e($p['nombre']) . ' · congelado desde ' . $e($p['congelado_desde']) . ($p['ficha_instantanea'] ? '' : ' · <b>sin instantánea</b>') . '</span>';
            }
            $html .= '</div></div>';
        }
        if ($n === 0) {
            $html .= '<div class="f7-empty">No hay presentes activos — el mundo espera su primer tema.</div>';
        }
    } else {
        $html .= '<div class="f7-empty">Tabla temas no migrada.</div>';
    }
    $html .= '</div></div>';

    // ── Histórico de aperturas/cierres ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Histórico de aperturas/cierres</span><span class="c">últimos temas</span></div><div class="plate-b">';
    if (ope7_tabla_existe('temas') && ope7_tabla_existe('temas_participantes')) {
        $q = $db->query('SELECT t.tid, t.tipo, t.estado, t.fecha_foro, t.fecha_real_apertura, tp.salio_en, tp.personaje_id, p.nombre FROM ' . ope7_tabla_full('temas') . " t "
            . 'LEFT JOIN ' . ope7_tabla_full('temas_participantes') . ' tp ON tp.tema_id = t.tid '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tp.personaje_id '
            . 'ORDER BY t.tid DESC LIMIT 20');
        $n = 0;
        while ($t = $db->fetch_array($q)) {
            $n++;
            $cerrado = (string) $t['estado'] === 'cerrado';
            $html .= '<div class="cal-histo">'
                  . '<span class="cal-histo-tag ' . ($cerrado ? 'cal-cerrado' : 'cal-abierto') . '">' . ($cerrado ? 'CERRADO' : 'ABIERTO') . '</span>'
                  . ' TEMA ' . (int) $t['tid'] . ' · ' . $e($t['tipo']) . ' · ancla ' . $e($t['fecha_foro']) . ($t['nombre'] ? ' · ' . $e($t['nombre']) : '')
                  . ($cerrado && $t['salio_en'] ? ' · salió ' . date('Y-m-d', (int) $t['salio_en']) : '')
                  . '</div>';
        }
        if ($n === 0) {
            $html .= '<div class="f7-empty">Sin temas todavía.</div>';
        }
    }
    $html .= '</div></div>';

    // ── Histórico de avances del calendario ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Histórico de avances</span><span class="c">calendario_foro.avances</span></div><div class="plate-b">';
    if ($cal['avances']) {
        foreach ($cal['avances'] as $av) {
            $html .= '<div class="cal-histo"><span class="cal-histo-tag">AVANCE</span> ' . $e($av['desde'] ?? '') . ' → ' . $e($av['hasta'] ?? '') . ' · +' . (int) ($av['pasos_onroll'] ?? 0) . ' días on-roll (' . (int) ($av['dias_real'] ?? 0) . ' días reales, ' . $e($av['fecha_real'] ?? '') . ')</div>';
        }
    } else {
        $html .= '<div class="f7-empty">El calendario aún no registra avances (la fecha se mueve +2 días por cada día real).</div>';
    }
    $html .= '</div></div>';
    return $html;
}

/**
 * Bandas cerradas de skill-cierre-temas (Staff 7.2 — números sagrados, no
 * recalibrar). Devuelve el rango [min, max] de cada factor y la banda ampliada
 * de tiempo según el tipo de tema.
 */
function ope7_cierre_bandas()
{
    return array(
        'fidelidad' => array(0.90, 1.20),
        'peso'      => array(0.90, 1.25),
        'calidad'   => array(0.90, 1.20),
        'extension' => array(0.85, 1.10),
        'tiempo'    => array(0.70, 1.30), // banda ampliada completa (pasado/presente)
        'riesgo'    => array(0.90, 1.35),
        'perfil'    => array(1.00, 1.05),
    );
}

/** Banda de tiempo según tipo: pasado 0,70–0,90 · presente 1,00–1,30 (7.2). */
function ope7_cierre_tiempo_banda($tipo)
{
    return $tipo === 'pasado' ? array(0.70, 0.90) : array(1.00, 1.30);
}

/** Base del tramo: 5 × coste del punto (50/75/125/200/300). */
function ope7_cierre_base($nivel)
{
    return 5 * ope7_pj_coste_punto_pp((int) $nivel);
}

/**
 * Fórmula de PP del cierre (Staff 7.2): Base(T) × 7 factores, redondeo al
 * entero más cercano (mitades a favor del jugador), techo 2×, suelo 0,5×.
 * Valida cada factor dentro de su banda cerrada y el tiempo dentro de la banda
 * del tipo. Devuelve array('ok', 'pp', 'base', 'desglose', 'msg').
 */
function ope7_cierre_pp_calcular($nivel, $tipo, array $factores)
{
    $tipo = in_array((string) $tipo, array('presente', 'pasado'), true) ? (string) $tipo : 'presente';
    $bandas = ope7_cierre_bandas();
    $desglose = array();
    $prod = 1.0;
    foreach ($bandas as $k => $rango) {
        $v = isset($factores[$k]) ? (float) $factores[$k] : 1.00;
        $min = (float) $rango[0];
        $max = (float) $rango[1];
        if ($k === 'tiempo') {
            list($min, $max) = ope7_cierre_tiempo_banda($tipo);
        }
        if ($v < $min || $v > $max) {
            return array('ok' => false, 'pp' => 0, 'base' => 0, 'desglose' => array(), 'msg' => sprintf('%s %.2f fuera de banda (%s: %.2f–%.2f).', ucfirst($k), $v, $k, $min, $max));
        }
        $prod *= $v;
        $desglose[$k] = array('valor' => $v, 'banda' => array($min, $max));
    }
    $base = ope7_cierre_base((int) $nivel);
    $pp = (int) round($base * $prod, 0, PHP_ROUND_HALF_UP);
    // Techo 2× y suelo 0,5× (tema cerrado correctamente).
    $pp = min($pp, (int) round($base * 2));
    $pp = max($pp, (int) round($base * 0.5));
    return array('ok' => true, 'pp' => $pp, 'base' => $base, 'desglose' => $desglose, 'msg' => '');
}
