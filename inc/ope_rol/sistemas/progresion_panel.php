<?php
/**
 * One Piece: 7 Seas · Panel staff «Progresión» (F4.2 — Anexo A.3)
 * ----------------------------------------------------------------
 * Vista del motor de progresión (5.6): cronómetros de entrenamiento por
 * jugador, subidas de nivel con su histórico, gastos de PP por concepto
 * (atributos, dominios, técnicas), saldos y reservas.
 *
 * Todos los números vienen del libro `historico_pp` (gastos negativos,
 * ingresos positivos — F2.1/F3) y de `personajes` (nivel, pp_saldo, reserva,
 * puntos_comprados, entrenamiento_fin/bloque).
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** HTML del panel «Progresión». Solo lo pinta la página staff. */
function ope7_progresion_panel_html()
{
    global $db;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };

    ope7_progresion_cron(); // avance perezoso y finalización de vencidos

    $html = '<div class="shead"><h1>Progresión</h1><span class="code">A.3 · 5.6</span><span class="rule"></span></div>';

    // ── Cronómetros de entrenamiento en curso ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Cronómetros de entrenamiento</span><span class="c">en curso</span></div><div class="plate-b">';
    if (ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT id, nombre, nivel, pp_saldo, reserva, entrenamiento_fin, entrenamiento_bloque '
            . 'FROM ' . ope7_tabla_full('personajes') . ' '
            . "WHERE entrenamiento_fin > " . TIME_NOW . ' ORDER BY entrenamiento_fin ASC');
        $n = 0;
        while ($p = $db->fetch_array($q)) {
            $n++;
            $fin = (int) $p['entrenamiento_fin'];
            $dias = max(1, (int) ceil(($fin - TIME_NOW) / 86400));
            $bloque = (int) $p['entrenamiento_bloque'];
            $html .= '<div class="pr-crono"><div class="pr-crono-h"><b>' . $e($p['nombre']) . '</b> · nv' . (int) $p['nivel'] . '</div>'
                   . '<div class="pr-crono-meta">bloque de ' . $bloque . ' puntos · termina ' . date('d/m/Y', $fin) . ' (' . $dias . ' día' . ($dias > 1 ? 's' : '') . ' restante' . ($dias > 1 ? 's' : '') . ') · al vencer: +' . $bloque . ' a reserva (7.3)</div></div>';
        }
        if ($n === 0) {
            $html .= '<div class="pr-empty">Sin entrenamientos de atributos en curso (7.3).</div>';
        }
    } else {
        $html .= '<div class="pr-empty">Tabla personajes no migrada.</div>';
    }
    // Cronómetros de dominios (5.3): independientes del de atributos (4.4).
    if (ope7_tabla_existe('dominios_personaje') && ope7_tabla_existe('dominios') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT dp.id, dp.personaje_id, dp.nivel, dp.entrenamiento_fin, dp.entrenamiento_nivel, d.nombre, p.nombre AS pj_nombre '
            . 'FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
            . 'JOIN ' . ope7_tabla_full('dominios') . ' d ON d.id = dp.dominio_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = dp.personaje_id '
            . 'WHERE dp.entrenamiento_fin > ' . TIME_NOW . ' ORDER BY dp.entrenamiento_fin ASC');
        $nd = 0;
        while ($d = $db->fetch_array($q)) {
            $nd++;
            $fin = (int) $d['entrenamiento_fin'];
            $dias = max(1, (int) ceil(($fin - TIME_NOW) / 86400));
            $html .= '<div class="pr-crono"><div class="pr-crono-h"><b>' . $e($d['pj_nombre']) . '</b> · dominio <b>' . $e($d['nombre']) . '</b> → nv' . (int) ($d['entrenamiento_nivel'] ?? 0) . '</div>'
                   . '<div class="pr-crono-meta">termina ' . date('d/m/Y', $fin) . ' (' . $dias . ' día' . ($dias > 1 ? 's' : '') . ' restante' . ($dias > 1 ? 's' : '') . ') · 15 días (5.3, independiente de atributos)</div></div>';
        }
        if ($nd === 0 && $n === 0) {
            $html .= '<div class="pr-empty">Sin dominios en entrenamiento.</div>';
        }
    }
    $html .= '</div></div>';

    // ── Saldos, reservas y progreso de nivel ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Saldos y reservas</span><span class="c">pp · reserva · nivel</span></div><div class="plate-b"><table class="pr-table"><thead><tr>'
           . '<th>Personaje</th><th>Nivel</th><th>PP saldo</th><th>Reserva</th><th>Compr. desde el último nivel</th><th>Siguiente nivel</th></tr></thead><tbody>';
    if (ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT id, nombre, nivel, pp_saldo, reserva, puntos_comprados FROM ' . ope7_tabla_full('personajes')
            . " WHERE es_NPC = 0 ORDER BY nivel DESC, puntos_comprados DESC LIMIT 60");
        $n = 0;
        while ($p = $db->fetch_array($q)) {
            $n++;
            $comp = (int) $p['puntos_comprados'];
            $falta = max(0, 10 - $comp);
            $html .= '<tr><td><b>' . $e($p['nombre']) . '</b></td><td>' . (int) $p['nivel'] . '</td>'
                   . '<td>' . (int) $p['pp_saldo'] . ' PP</td><td>' . (int) $p['reserva'] . '</td>'
                   . '<td>' . $comp . ' / 10</td><td>' . $falta . ' puntos</td></tr>';
        }
        if ($n === 0) {
            $html .= '<tr><td colspan="6" class="pr-empty">Sin personajes aprobados todavía.</td></tr>';
        }
    }
    $html .= '</tbody></table></div></div>';

    // ── Gastos de PP por concepto (libro historico_pp) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Gastos de PP por concepto</span><span class="c">libro historico_pp</span></div><div class="plate-b"><table class="pr-table"><thead><tr>'
           . '<th>Concepto</th><th>Total (PP)</th><th>Movimientos</th></tr></thead><tbody>';
    if (ope7_tabla_existe('historico_pp')) {
        $q = $db->query('SELECT concepto, SUM(cantidad) AS total, COUNT(*) AS n FROM ' . ope7_tabla_full('historico_pp')
            . " WHERE concepto <> '' GROUP BY concepto ORDER BY total ASC");
        $n = 0;
        while ($c = $db->fetch_array($q)) {
            $n++;
            $total = (int) $c['total'];
            $clase = $total < 0 ? 'pr-neg' : 'pr-pos';
            $html .= '<tr><td>' . $e($c['concepto']) . '</td><td class="' . $clase . '">' . ($total > 0 ? '+' : '') . $total . '</td>'
                   . '<td>' . (int) $c['n'] . '</td></tr>';
        }
        if ($n === 0) {
            $html .= '<tr><td colspan="3" class="pr-empty">El libro de PP está vacío — todavía no hay gastos ni cierres registrados.</td></tr>';
        }
    }
    $html .= '</tbody></table></div></div>';

    // ── Histórico reciente de movimientos (gastos e ingresos) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Histórico reciente de PP</span><span class="c">últimos 25 movimientos</span></div><div class="plate-b">';
    if (ope7_tabla_existe('historico_pp') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT h.*, p.nombre FROM ' . ope7_tabla_full('historico_pp') . ' h '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = h.personaje_id '
            . 'ORDER BY h.id DESC LIMIT 25');
        $n = 0;
        while ($h = $db->fetch_array($q)) {
            $n++;
            $cant = (int) $h['cantidad'];
            $clase = $cant < 0 ? 'pr-neg' : 'pr-pos';
            $html .= '<div class="pr-mov"><div class="pr-mov-h"><span class="pr-mov-amt ' . $clase . '">' . ($cant > 0 ? '+' : '') . $cant . ' PP</span> '
                   . '<b>' . $e($h['nombre']) . '</b> · ' . $e($h['concepto'] !== '' ? $h['concepto'] : 'Movimiento') . '</div>'
                   . '<div class="pr-mov-meta">' . ($h['fecha'] > 0 ? date('d/m/Y H:i', (int) $h['fecha']) : '—')
                   . ($h['motivo'] ? ' · ' . $e($h['motivo']) : '') . '</div></div>';
        }
        if ($n === 0) {
            $html .= '<div class="pr-empty">Sin movimientos todavía.</div>';
        }
    }
    $html .= '</div></div>';

    return $html;
}
