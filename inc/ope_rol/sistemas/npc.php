<?php
/**
 * One Piece: 7 Seas · NPCs (F6) — 5.11/cap. 12
 * --------------------------------------------
 * Trámite 19 — Reclutamiento de NPC (12.5, ligero/firma):
 *   reclutar = asignar el uso de una ficha existente del catálogo (bestiario o
 *   primario). El reclutado deja de jugarse como bestiario en combates (5.10) y
 *   aporta con oficios/navegación/entrenamiento reales (12.5). Su ficha queda
 *   marcada «reclutado» mientras dura el vínculo; no se crea ficha de combate.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Efecto 19 · Reclutamiento de NPC (12.5, ligero + firma).
 * El staff firma el uso de una ficha existente: se busca por nombre en el
 * bestiario (y primarios); si existe, se marca «reclutado» en npc_apariciones
 * (manejado_por = reclutador). Sin tiradas y sin ficha de combate nueva.
 */
function ope7_efecto_reclutar_npc($tr, $pid, $res, $ids)
{
    global $db;
    if (!ope7_tabla_existe('bestiario') || !ope7_tabla_existe('npc_apariciones')) {
        return 'Reclutamiento de NPC: tablas no migradas (pendiente).';
    }
    $nombre = trim((string) ($res['nombre_npc'] ?? ($ids['nombre_npc'] ?? '')));
    if ($nombre === '') {
        return 'Reclutamiento BLOQUEADO: indica el NPC del catálogo a reclutar (12.5 — usas una ficha existente, no creas una).';
    }
    // Busca la ficha existente: bestiario por nombre exacto o LIKE.
    $q = $db->simple_select('ope_bestiario', '*', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
    $npc = $db->fetch_array($q);
    if (!$npc) {
        $q = $db->simple_select('ope_bestiario', '*', "nombre LIKE '%" . $db->escape_string($nombre) . "%'", array('limit' => 1));
        $npc = $db->fetch_array($q);
    }
    if (!$npc) {
        return 'Reclutamiento BLOQUEADO: «' . $nombre . '» no está en el catálogo de bestiario. '
            . 'Reclutar usa una ficha existente (12.5): crea o publica el NPC primero o indica su nombre exacto.';
    }
    // Marca la ficha como reclutada: fuera del catálogo activo mientras dure el
    // vínculo (12.5). Registro en npc_apariciones con estado reclutado.
    $npc_id = (int) $npc['id'];
    $q = $db->simple_select('ope_npc_apariciones', 'id', "bestiario_id = {$npc_id} AND estado = 'reclutado'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        $tema_id = (int) ($ids['tema_id'] ?? 0);
        // MyBB convierte null → '' en JSON NOT NULL (bug conocido): JSON válido.
        $db->insert_query('ope_npc_apariciones', array(
            'bestiario_id'  => $npc_id,
            'tema_id'       => $tema_id > 0 ? $tema_id : 0,
            'pv_actual'     => (int) ($npc['pv_max'] ?? 0),
            'pe_actual'     => (int) ($npc['pe_max'] ?? 0),
            'estados'       => '{}',
            'manejado_por'  => (int) $pid,
            'estado'        => 'reclutado',
        ));
    }
    // Actualiza el manejado_por por si el vínculo cambia de manos.
    $db->update_query('ope_npc_apariciones', array('manejado_por' => (int) $pid), "bestiario_id = {$npc_id} AND estado = 'reclutado'");

    $tipo = (string) ($npc['tipo'] ?? 'terciario');
    return 'NPC reclutado: «' . $npc['nombre'] . '» (' . $tipo . ', nv ' . (int) $npc['nivel']
        . ') marcado como reclutado por #' . (int) $pid . '. Deja de jugarse como bestiario en combates y '
        . 'aporta con oficios/navegación/entrenamiento reales (12.5). El staff mantiene la ficha.';
}

// ─────────────────────────────────────────────────────────────
// Panel staff «NPCs» (A.3, 5.11): primarios (capa visible + oculta),
// bestiario, apariciones por tema (incluido «reclutado»).
// ─────────────────────────────────────────────────────────────

/** HTML del panel: primarios, bestiario, apariciones. */
function ope7_npc_panel_html()
{
    global $db;
    $out = '';

    // ── Primarios: capa visible + oculta ──
    $out .= '<div class="plate"><div class="plate-h">Primarios (5.11: capa visible + oculta solo-staff)</div><div class="plate-b">';
    if (ope7_tabla_existe('npc_primario') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT n.*, p.nombre AS pj_nombre, r.nombre AS raza_nombre, p.estado FROM ' . ope7_tabla_full('npc_primario') . ' n '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = n.personaje_id '
            . 'LEFT JOIN ' . ope7_tabla_full('razas') . ' r ON r.id = p.raza_id ORDER BY p.nombre');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin primarios registrados todavía.</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $out .= '<div class="zs-row"><div class="zs-row-h"><b>' . htmlspecialchars((string) $r['pj_nombre']) . '</b>'
                    . ' <span class="zs-mut">' . htmlspecialchars((string) ($r['raza_nombre'] ?? '—')) . ' · ' . htmlspecialchars((string) $r['estado']) . '</span></div>';
                if ((string) ($r['intenciones_ocultas'] ?? '') !== '') {
                    $out .= '<div class="zs-sub"><b>Solo-staff:</b> ' . htmlspecialchars((string) $r['intenciones_ocultas']) . '</div>';
                }
                if ((string) ($r['historia_completa'] ?? '') !== '') {
                    $out .= '<div class="zs-sub">' . htmlspecialchars(mb_substr((string) $r['historia_completa'], 0, 240)) . (mb_strlen((string) $r['historia_completa']) > 240 ? '…' : '') . '</div>';
                }
                $out .= '</div>';
            }
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (npc_primario).</p>';
    }
    $out .= '</div></div>';

    // ── Bestiario ──
    $out .= '<div class="plate"><div class="plate-h">Bestiario (editor de fichas de combate)</div><div class="plate-b">';
    if (ope7_tabla_existe('bestiario')) {
        $q = $db->simple_select('ope_bestiario', '*', '1=1', array('order_by' => 'nombre'));
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Bestiario vacío (los NPC se dan de alta en la ficha del mundo; el trámite 19 usa fichas existentes).</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>Nombre</th><th>Tipo</th><th>Nv</th><th>PV/PE</th><th>PA</th><th>Zona</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $out .= '<tr><td><b>' . htmlspecialchars((string) $r['nombre']) . '</b></td>'
                    . '<td>' . htmlspecialchars((string) $r['tipo']) . '</td>'
                    . '<td>' . (int) $r['nivel'] . '</td>'
                    . '<td>' . (int) $r['pv_max'] . '/' . (int) $r['pe_max'] . '</td>'
                    . '<td>' . (int) $r['pa'] . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['zona']) . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tabla no migrada (bestiario).</p>';
    }
    $out .= '</div></div>';

    // ── Apariciones por tema (incluido «reclutado», 12.5) ──
    $out .= '<div class="plate"><div class="plate-h">Apariciones por tema (incluido «reclutado»)</div><div class="plate-b">';
    if (ope7_tabla_existe('npc_apariciones') && ope7_tabla_existe('bestiario')) {
        $q = $db->query('SELECT a.*, b.nombre AS npc_nombre, b.nivel FROM ' . ope7_tabla_full('npc_apariciones') . ' a '
            . 'JOIN ' . ope7_tabla_full('bestiario') . ' b ON b.id = a.bestiario_id '
            . 'ORDER BY a.id DESC LIMIT 30');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin apariciones registradas (el combate 5.10 y el reclutamiento 12.5 las archivan aquí).</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>NPC</th><th>Tema</th><th>Estado</th><th>Manejado por</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $out .= '<tr><td>' . htmlspecialchars((string) $r['npc_nombre']) . ' (nv' . (int) $r['nivel'] . ')</td>'
                    . '<td>#' . (int) $r['tema_id'] . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['estado']) . '</td>'
                    . '<td>' . ((int) $r['manejado_por'] > 0 ? 'PJ #' . (int) $r['manejado_por'] : '—') . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (npc_apariciones).</p>';
    }
    $out .= '</div></div>';

    $out .= '</div>';
    return $out;
}
