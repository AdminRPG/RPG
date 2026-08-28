<?php
/**
 * One Piece: 7 Seas · Muertes / Reliquias (F6) — 5.21-bis/cap. 20
 * --------------------------------------------------------------
 * Trámite 62 (muerte): veredicto con umbral, calidad (skill-cierre-temas),
 * reliquia (ficha muerta con leyenda), herencia (PP + berries × calidad) y
 * efectos de mundo (fruta renacida, cartel, facción, suceso de ronda).
 *
 * Panel staff «Reliquias» (Anexo A.3): fichas muertas con su leyenda
 * (visibles para el mundo) e histórico de muertes con calidad y herencia.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** HTML del panel: reliquias (fichas muertas) e histórico de muertes. */
function ope7_reliquias_panel_html()
{
    global $db;
    $out = '';

    // ── Reliquias: fichas muertas con su leyenda (visibles para el mundo) ──
    $out .= '<div class="plate"><div class="plate-h">Reliquias (fichas muertas con leyenda, 5.21-bis)</div><div class="plate-b">';
    if (ope7_tabla_existe('muertes') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT m.*, p.nombre AS pj_nombre, r.nombre AS raza_nombre, p.nivel, p.akuma_id '
            . 'FROM ' . ope7_tabla_full('muertes') . ' m '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = m.personaje_id '
            . 'LEFT JOIN ' . ope7_tabla_full('razas') . ' r ON r.id = p.raza_id '
            . 'ORDER BY m.id DESC LIMIT 30');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin muertes registradas (el trámite 62 con firma archiva aquí la reliquia y la herencia).</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>Personaje</th><th>Raza · Nv</th><th>Calidad</th><th>Herencia</th><th>Causa</th><th>Fecha</th><th>Heredero</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $herencia = json_decode((string) ($r['herencia'] ?? '{}'), true);
                $pp = (int) ($herencia['pp'] ?? 0);
                $ber = (int) ($herencia['berries'] ?? 0);
                $heredero = '';
                if ((int) $r['heredero_id'] > 0) {
                    $hq = $db->simple_select('ope_personajes', 'nombre', "id = " . (int) $r['heredero_id'], array('limit' => 1));
                    $heredero = (string) $db->fetch_field($hq, 'nombre');
                }
                $out .= '<tr><td><b>' . htmlspecialchars((string) $r['pj_nombre']) . '</b></td>'
                    . '<td>' . htmlspecialchars((string) ($r['raza_nombre'] ?? '—')) . ' · nv' . (int) $r['nivel'] . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['calidad']) . '</td>'
                    . '<td>' . number_format($pp, 0, ',', '.') . ' PP · ' . number_format($ber, 0, ',', '.') . ' ฿</td>'
                    . '<td>' . htmlspecialchars(mb_substr((string) $r['causa'], 0, 120)) . (mb_strlen((string) $r['causa']) > 120 ? '…' : '') . '</td>'
                    . '<td>' . date('d/m/y', (int) $r['fecha']) . '</td>'
                    . '<td>' . ($heredero !== '' ? htmlspecialchars($heredero) : '—') . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (muertes).</p>';
    }
    $out .= '</div></div>';

    // ── Histórico de muertes: calidad y efectos de mundo ──
    $out .= '<div class="plate"><div class="plate-h">Histórico de muertes (calidad + efectos de mundo)</div><div class="plate-b">';
    if (ope7_tabla_existe('muertes')) {
        $q = $db->query('SELECT m.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('muertes') . ' m '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = m.personaje_id '
            . 'ORDER BY m.id DESC LIMIT 15');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin histórico todavía.</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $fx = json_decode((string) ($r['efectos_mundo'] ?? '{}'), true);
                $fx_txt = array();
                foreach ((array) $fx as $k => $v) {
                    $ok = is_array($v) ? (bool) ($v['aplicado'] ?? false) : (bool) $v;
                    $fx_txt[] = $k . ($ok ? ' ✓' : ' ✗');
                }
                $out .= '<div class="zs-row"><div class="zs-row-h"><b>' . htmlspecialchars((string) $r['pj_nombre']) . '</b>'
                    . ' <span class="zs-mut">' . htmlspecialchars((string) $r['calidad']) . ' · umbral: ' . htmlspecialchars((string) $r['umbral_confirmado']) . '</span></div>'
                    . '<div class="zs-sub">Efectos de mundo: ' . htmlspecialchars(implode(' · ', $fx_txt)) . '</div></div>';
            }
        }
    } else {
        $out .= '<p class="zs-mut">Tabla no migrada (muertes).</p>';
    }
    $out .= '</div></div>';

    $out .= '</div>';
    return $out;
}
