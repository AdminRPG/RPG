<?php
/**
 * One Piece: 7 Seas · Renderer de la ficha (F1.2)
 * -------------------------------------------------
 * Pinta la ficha completa de un personaje `mybb_ope_*` (Anexo A.1): identidad,
 * desglose de atributos base + racial, secundarios calculados (fórmulas 5.2),
 * PP, dotes/defectos (con origen), rasgos (con karma y estado), dominios
 * (nivel/rama), técnicas (librería) y sección solo-staff.
 * Reutiliza el chrome de body.ope-pg-ficha; clases nuevas con prefijo f7-.
 * Devuelve HTML; cero <style> y cero estilos inline estáticos.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Escapa y devuelve el HTML de la ficha 7 Seas. */
function ope7_ficha_html($f, $ctx = array())
{
    global $db, $mybb;
    $ctx = array_merge(array('uid' => 0, 'es_activo' => false, 'puede_gestionar' => false, 'es_staff' => false, 'bburl' => '', 'reserva_flash' => ''), $ctx);
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };

    $estado = (string) ($f['estado'] ?? 'borrador');
    $estado_label = array('borrador' => 'Borrador', 'revision' => 'En revisión', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado');
    $estado_txt = $estado_label[$estado] ?? $estado;
    $estado_cls = $estado === 'aprobado' ? 'ok' : 'pend';

    // Raza(s).
    $raza_txt = array();
    $mods = ope7_pj_modificadores_efectivos($f);
    if (isset($f['razas']['raza_id'])) {
        $raza_txt[] = $f['razas']['raza_id']['nombre'];
    }
    if (isset($f['razas']['raza_hibrida_id'])) {
        $raza_txt[] = $f['razas']['raza_hibrida_id']['nombre'];
    }
    $tribu_txt = $f['tribu'] ? $f['tribu']['nombre'] : '';

    $ATR = array('fue' => 'FUE', 'des' => 'DES', 'agi' => 'AGI', 'res' => 'RES', 'per' => 'PER', 'inte' => 'INT', 'car' => 'CAR', 'vol' => 'VOL');
    $s = $f['secundarios'];

    $html = '';

    // ── Identidad ──
    $html .= '<div class="idbanner">'
          . '<div class="eyebrow">' . $e(implode(' · ', $raza_txt)) . ($tribu_txt !== '' ? '<span class="sep">›</span>' . $e($tribu_txt) : '') . '<span class="sep">›</span>Nivel ' . (int) $f['nivel'] . '</div>'
          . '<h1>' . $e($f['nombre']) . '</h1>'
          . ($f['bio'] ? '<div class="desig">' . $e($f['bio']) . '</div>' : '')
          . '<div class="idtags">'
          . '<span class="tag estado ' . $estado_cls . '">' . $e($estado_txt) . '</span>'
          . '<span class="tag">' . $e(implode(' / ', $raza_txt)) . '</span>'
          . ($tribu_txt !== '' ? '<span class="tag line">' . $e($tribu_txt) . '</span>' : '')
          . '<span class="tag">Nv ' . (int) $f['nivel'] . '</span>'
          . '</div></div>';

    // ── Cuerpo: columna izquierda + principal ──
    $html .= '<div class="forge">';

    // Columna izquierda: retrato + atributos + secundarios.
    $html .= '<aside class="pcol">';
    $inicial = mb_strtoupper(mb_substr(trim((string) $f['nombre']), 0, 1));
    $html .= '<div class="forge-portrait"><div class="fp-frame">'
          . ($f['retrato'] ? '<img class="fp-img" src="' . $e($f['retrato']) . '" alt="">' : '')
          . '<div class="fp-grid"></div><div class="fp-shade"></div>'
          . '<div class="fp-initial">' . $e($inicial) . '</div>'
          . '<div class="fp-lv">NV ' . (int) $f['nivel'] . '</div>'
          . '</div>'
          . '<div class="fp-nameplate"><b>' . $e($f['nombre']) . '</b><span>' . $e(implode(' · ', $raza_txt)) . ($tribu_txt !== '' ? ' · ' . $e($tribu_txt) : '') . '</span></div>'
          . '</div>';

    $html .= '<div class="under">';
    $html .= '<div class="estado-chip">' . $e($estado_txt) . '</div>';
    $html .= '<div class="f7-pp"><span class="f7-pp-v">' . (int) $f['pp_saldo'] . '</span> <span class="f7-pp-l">PP disponibles</span></div>';
    $html .= '</div>';

    // Atributos (base + racial).
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Atributos</span><span class="c">base + racial</span></div><div class="plate-b">';
    $html .= '<div class="f7-atr">';
    foreach ($ATR as $k => $lab) {
        $base = (int) ($f[$k] ?? 0);
        $bonus = (int) ($mods[$k] ?? 0);
        $total = $base + $bonus;
        $html .= '<div class="f7-atr-row">'
              . '<span class="f7-atr-k">' . $lab . '</span>'
              . '<span class="f7-atr-v"><b>' . $total . '</b>'
              . ($bonus !== 0 ? ' <span class="f7-atr-b">' . ($bonus > 0 ? '+' : '') . $bonus . '</span>' : '')
              . '</span>'
              . '<span class="f7-atr-base">' . $base . ' base</span>'
              . '</div>';
    }
    $html .= '</div></div></div>';

    // Secundarios.
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Secundarios</span><span class="c">calculados</span></div><div class="plate-b"><div class="f7-sec">';
    $sec_items = array(
        'Vida (PV)' => $s['pv'], 'Energía (PE)' => $s['pe'], 'PA / turno' => $s['pa'],
        'Velocidad' => $s['velocidad'] . ' m/s', 'Sprint' => $s['sprint'] . ' m/s',
        'Salto' => $s['salto_v'] . ' / ' . $s['salto_h'] . ' m',
        'Carga' => $s['carga'] . ' kg', 'Levantar' => $s['carga_levantar'] . ' kg',
        'Res. pasiva' => $s['resistencia_pasiva'], 'Lanzamiento' => $s['lanzamiento'] . ' m',
        'Recuperación' => $s['recuperacion'] . '%/h',
    );
    $i = 0;
    foreach ($sec_items as $k => $v) {
        $hl = $i < 3 ? ' f7-sec-hl' : '';
        $html .= '<div class="f7-sec-item' . $hl . '"><span>' . $e($k) . '</span><b>' . $e($v) . '</b></div>';
        $i++;
    }
    $html .= '</div></div></div>';
    $html .= '</aside>';

    // Columna principal.
    $html .= '<div class="f7-main">';

    // Dotes y defectos.
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Dotes y defectos</span><span class="c">balanza de creación a 0</span></div><div class="plate-b">';
    $items = array();
    foreach ($f['dotes'] as $dt) {
        $items[] = array('+' . (int) $dt['puntuacion'], $dt['nombre'], 'Dote' . ($dt['tipo'] === 'racial' ? ' racial' : '') . ($dt['requiere_raza_pura'] ? ' · solo puro' : ''), $dt['efecto'], (string) $dt['origen']);
    }
    foreach ($f['defectos'] as $df) {
        $items[] = array((int) $df['puntuacion'] . '', $df['nombre'], 'Defecto', $df['efecto'], (string) $df['origen']);
    }
    if (!$items) {
        $html .= '<div class="f7-empty">Sin dotes ni defectos en la balanza.</div>';
    }
    foreach ($items as $it) {
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($it[1]) . '</span><span class="f7-row-pts' . (strpos($it[0], '-') === 0 ? ' neg' : '') . '">' . $e($it[0]) . '</span></div>'
              . '<div class="f7-row-meta">' . $e($it[2]) . ' · origen: ' . $e($it[4]) . '</div>'
              . '<div class="f7-row-desc">' . $e(trim((string) $it[3], '"')) . '</div></div>';
    }
    $html .= '</div></div>';

    // Rasgos con karma.
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Rasgos</span><span class="c">karma público</span></div><div class="plate-b">';
    if (!$f['rasgos']) {
        $html .= '<div class="f7-empty">Sin rasgos declarados.</div>';
    }
    foreach ($f['rasgos'] as $rg) {
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($rg['nombre']) . '</span>'
              . '<span class="f7-row-pts' . ((int) $rg['puntuacion'] < 0 ? ' neg' : '') . '">' . ((int) $rg['puntuacion'] > 0 ? '+' : '') . (int) $rg['puntuacion'] . '</span></div>'
              . '<div class="f7-row-meta">' . $e($rg['estado']) . ' · karma ' . (int) $rg['karma_acumulado'] . ' · contradicciones ' . (int) $rg['contador_contradicciones'] . '</div>'
              . '<div class="f7-row-desc">' . $e($rg['descripcion']) . '</div></div>';
    }
    $html .= '</div></div>';

    // Dominios (5.3/4.4): nivel, cronómetro de 15 días en curso y compra/subida.
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Dominios</span><span class="c">2 puntos de creación · cronómetro 15 días (5.3)</span></div><div class="plate-b">';
    if (!$f['dominios']) {
        $html .= '<div class="f7-empty">Sin dominios.</div>';
    }
    foreach ($f['dominios'] as $dm) {
        $mult = isset($dm['coste_mult']) ? (float) $dm['coste_mult'] : 1.0;
        $entrenando = (int) ($dm['entrenamiento_fin'] ?? 0) > TIME_NOW;
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($dm['nombre']) . '</span><span class="f7-row-pts">Nv ' . (int) $dm['nivel'] . ($mult > 1.0 ? ' · ×' . number_format($mult, 2, ',', '') : '') . '</span></div>'
              . '<div class="f7-row-meta">' . ($dm['tipo'] === 'belico' ? 'Bélico' : 'Oficio') . ' · atributo rey ' . strtoupper((string) $dm['atributo_rey']) . ($dm['rama'] ? ' · rama: ' . $e($dm['rama']) : '')
              . ($entrenando ? ' · <b>entrenando → nv' . (int) $dm['entrenamiento_nivel'] . '</b> (termina ' . date('d/m/Y', (int) $dm['entrenamiento_fin']) . ')' : '') . '</div></div>';
    }
    // Compra/subida de dominio (5.3): solo para el dueño; ligero automático.
    if (!empty($ctx['puede_gestionar']) && function_exists('ope7_tramite_crear')) {
        $dom_flash = (string) ($ctx['dom_flash'] ?? '');
        if ($dom_flash !== '') {
            $html .= '<div class="flash ' . (strpos($dom_flash, 'aceptada') !== false ? 'ok' : 'warn') . '">' . $e($dom_flash) . '</div>';
        }
        $html .= '<div class="f7-dom-form"><form method="post" action="' . $e($ctx['bburl']) . '/ficha.php?pid=' . (int) $f['id'] . '" class="f7-dom-inline">'
               . '<input type="hidden" name="my_post_key" value="' . $e($mybb->post_code ?? '') . '">'
               . '<input type="hidden" name="gaccion" value="dominio">'
               . '<div class="f7-dom-row">'
               . '<span class="f7-dom-k">Dominio</span>'
               . '<select name="dom_dominio_id" class="f7-dom-select">';
        $tengo = array();
        foreach ($f['dominios'] as $dm) {
            $tengo[(int) $dm['dominio_id']] = (int) $dm['nivel'];
        }
        $dq = $db->simple_select('ope_dominios', '*', 'activo = 1', array('order_by' => 'tipo, nombre'));
        while ($domc = $db->fetch_array($dq)) {
            $did = (int) $domc['id'];
            if (isset($tengo[$did])) {
                $nuevo = $tengo[$did] + 1;
                if ($nuevo <= 5) {
                    $mult_lbl = '';
                    foreach ($f['dominios'] as $dmt) {
                        if ((int) $dmt['dominio_id'] === $did && (float) ($dmt['coste_mult'] ?? 1.0) > 1.0) {
                            $mult_lbl = ' ×' . number_format((float) $dmt['coste_mult'], 2, ',', '');
                        }
                    }
                    $html .= '<option value="' . $did . ':' . $nuevo . '">Subir ' . $e($domc['nombre']) . ' → nv' . $nuevo . ' (' . ope7_dominio_coste_base($nuevo) . ' PP' . $mult_lbl . ')</option>';
                }
            } else {
                $html .= '<option value="' . $did . ':2">Adquirir ' . $e($domc['nombre']) . ' (nv1 → nv2, 60 PP ×1,5 el 1.º adicional · ×2 el 2.º+)</option>';
            }
        }
        $html .= '</select>'
               . '<button type="submit" class="btn btn-ghost">Entrenar (15 días)</button>'
               . '</div>'
               . '<div class="f7-dom-aviso">El coste sale de tu PP; el cronómetro de dominios es independiente del de atributos (4.4): puedes entrenar los dos a la vez, pero no dos dominios.</div>'
               . '</form></div>';
    }
    $html .= '</div></div>';

    // Técnicas (librería).
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Técnicas</span><span class="c">librería personal</span></div><div class="plate-b">';
    if (!$f['tecnicas']) {
        $html .= '<div class="f7-empty">Sin técnicas todavía — se crean por el trámite 13 (PP según tier).</div>';
    }
    foreach ($f['tecnicas'] as $tc) {
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($tc['nombre']) . '</span>'
              . '<span class="f7-row-pts">T' . (int) $tc['tier'] . ' · ' . $e($tc['tipo']) . '</span></div>'
              . '<div class="f7-row-meta">' . $e($tc['dominio_nombre']) . ' · PA ' . (int) $tc['pa'] . ' · PE ' . (int) $tc['pe_pct'] . '% · reposo ' . (int) $tc['reposo'] . ' · coste ' . (int) $tc['coste_pp'] . ' PP</div>'
              . ($tc['nota_moderacion'] ? '<div class="f7-row-desc">' . $e($tc['nota_moderacion']) . '</div>' : '')
              . '</div>';
    }
    $html .= '</div></div>';

    // Equipo y cartera (F3.2/F3.4): ranuras, objetos y saldos (cap. 9/10).
    if (function_exists('ope7_inventario_resumen')) {
        $inv = ope7_inventario_resumen((int) $f['id']);
        $html .= '<div class="plate"><div class="plate-h"><span class="t">Equipo y cartera</span><span class="c">ranuras · objetos · ฿</span></div><div class="plate-b">';
        $html .= '<div class="f7-sec">'
              . '<div class="f7-sec-v">' . (int) $inv['cartera']['cartera'] . ' <span>฿ cartera</span></div>'
              . '<div class="f7-sec-v">' . (int) $inv['cartera']['boveda'] . ' <span>฿ bóveda</span></div>'
              . '<div class="f7-sec-v">' . (int) $inv['usado']['equipado'] . '/' . (int) $inv['capacidad']['equipado'] . ' <span>equipado (3+FUE/10)</span></div>'
              . '<div class="f7-sec-v">' . (int) $inv['usado']['mochila'] . '/' . (int) $inv['capacidad']['mochila'] . ' <span>mochila (8+FUE/4' . (!empty($inv['capacidad']['tontatta']) ? ' · Tontatta ×2' : '') . ')</span></div>'
              . '</div>';
        if (!$inv['equipado'] && !$inv['mochila'] && !$inv['almacen']) {
            $html .= '<div class="f7-empty">Sin equipo todavía — compra o produce por los trámites 6 y 14.</div>';
        }
        $listar = function ($items, $etiqueta) use ($e) {
            $out = '';
            if (!$items) {
                return $out;
            }
            $out .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $etiqueta . '</span></div></div>';
            foreach ($items as $it) {
                $out .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($it['nombre']) . '</span>'
                      . '<span class="f7-row-pts">' . $e($it['calidad'] !== '' ? ucfirst($it['calidad']) : ucfirst($it['categoria'])) . ($it['ranuras'] > 1 ? ' · ' . (int) $it['ranuras'] . ' ran.' : '') . (isset($it['cantidad']) ? ' · ×' . (int) $it['cantidad'] : '') . '</span></div>'
                      . (isset($it['zona']) ? '<div class="f7-row-meta">' . $e($it['zona']) . '</div>' : '') . '</div>';
            }
            return $out;
        };
        $html .= $listar($inv['equipado'], 'Equipado');
        $html .= $listar($inv['mochila'], 'Mochila');
        $html .= $listar($inv['almacen'], 'Almacén (seguro — nunca se roba)');
        $html .= '</div></div>';
    }

    // Fruta y Haki (F5, 5.18/5.19): el poder del personaje en un vistazo.
    if (ope7_tabla_existe('akumas') && function_exists('ope7_akuma_info')) {
        $akuma_id = (int) ($f['akuma_id'] ?? 0);
        $sin_comer = false;
        if ($akuma_id < 1) {
            $aq = $db->simple_select('ope_akumas', 'id', "portador_id = " . (int) $f['id'] . " AND estado = 'con_portador'", array('limit' => 1));
            $akuma_id = (int) $db->fetch_field($aq, 'id');
            $sin_comer = $akuma_id > 0;
        }
        if ($akuma_id > 0) {
            $akuma = ope7_akuma_info($akuma_id);
            if ($akuma) {
                $rareza = (string) ($akuma['rareza'] ?? '');
                $html .= '<div class="plate f7-akuma"><div class="plate-h"><span class="t">Fruta del diablo</span><span class="c">' . ($sin_comer ? 'sin comer — trámite 47' : 'T' . (int) $akuma['tier'] . ' · ' . $e($akuma['familia']) . ($rareza !== '' ? ' · ' . $e($rareza) : '') . ($akuma_id > 0 && !$sin_comer && (int) ($f['akuma_afinidad'] ?? 0) ? ' · afinidad −10 % PE' : '')) . '</span></div><div class="plate-b">';
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name"><b>' . $e($akuma['nombre_propio']) . '</b></span><span class="f7-row-pts">' . $e($akuma['aspecto']) . '</span></div></div>';
                if (!$sin_comer) {
                    $mec = is_array($akuma['mecanica_base'] ?? null) ? $akuma['mecanica_base'] : array();
                    $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Mecánica base</span></div><div class="f7-row-meta">' . $e((string) ($mec['resumen'] ?? '')) . '</div></div>';
                    $puertas = (array) ($akuma['puertas'] ?? array());
                    if ($puertas) {
                        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Puertas de poder</span></div><div class="f7-row-meta">' . $e(implode(' · ', $puertas)) . '</div></div>';
                    }
                    $deb = is_array($akuma['debilidades'] ?? null) ? $akuma['debilidades'] : array();
                    if (!empty($deb['enemigo_natural'])) {
                        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Enemigo natural</span></div><div class="f7-row-meta">' . $e((string) $deb['enemigo_natural']) . '</div></div>';
                    }
                    $desp = is_array($akuma['despertar'] ?? null) ? $akuma['despertar'] : array();
                    if (!empty($desp['resumen'])) {
                        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Despertar</span></div><div class="f7-row-meta">' . $e((string) $desp['resumen']) . '</div></div>';
                    }
                    $inf = function_exists('ope7_akuma_influencia') ? ope7_akuma_influencia($akuma) : null;
                    if ($inf && ($inf['defectos'] || $inf['dotes'])) {
                        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Influencia en la ficha</span></div><div class="f7-row-meta">' . $e(implode(' · ', array_merge($inf['defectos'], $inf['dotes']))) . '</div></div>';
                    }
                }
                $html .= '</div></div>';
            }
        }
    }
    if (ope7_tabla_existe('haki')) {
        $html .= '<div class="plate f7-haki"><div class="plate-h"><span class="t">Haki</span><span class="c">20.x — niveles, usos y PP</span></div><div class="plate-b">';
        $hq = $db->simple_select('ope_haki', '*', "personaje_id = " . (int) $f['id'] . " AND activo = 1", array('order_by' => 'tipo'));
        if (!$db->num_rows($hq)) {
            $html .= '<div class="f7-empty">Sin Haki despertado. Armadura y Mantra se despiertan solos a nv10 (20.1); el Conquistador solo por tirada (trámite 50, nv5+).</div>';
        } else {
            while ($h = $db->fetch_array($hq)) {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e(ucfirst((string) $h['tipo'])) . '</span><span class="f7-row-pts">N' . (int) $h['nivel'] . ' · ' . (int) $h['usos_acumulados'] . ' usos · ' . (int) $h['pp_invertidos'] . ' PP</span></div></div>';
            }
        }
        $html .= '</div></div>';
    }

    // Tripulación (F5.3, 5.21-ter): banda activa con cofre común (5.9) y
    // espacio del barco (5.17). Valor operativo, sin bonos numéricos.
    if (function_exists('ope7_pj_tripulacion_activa') && function_exists('ope7_trip_get')) {
        $trip_id = ope7_pj_tripulacion_activa((int) $f['id']);
        if ($trip_id > 0) {
            $trip = ope7_trip_get($trip_id);
            if ($trip) {
                $miembros = function_exists('ope7_trip_miembros') ? ope7_trip_miembros($trip_id, true) : array();
                $cofre = function_exists('ope7_trip_cofre_get') ? ope7_trip_cofre_get($trip_id) : array('berries' => 0);
                $nombres = array();
                foreach ($miembros as $m) {
                    $nombres[] = ($m['rol'] === 'capitan' ? '👑 ' : '') . $e((string) $m['pj_nombre']);
                }
                $html .= '<div class="plate f7-trip"><div class="plate-h"><span class="t">Tripulación</span><span class="c">' . $e((string) $trip['nombre']) . ' · ' . count($miembros) . ' miembros · cofre ' . number_format((int) ($cofre['berries'] ?? 0)) . ' ฿</span></div><div class="plate-b">';
                if ($nombres) {
                    $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Miembros</span></div><div class="f7-row-meta">' . implode(' · ', $nombres) . '</div></div>';
                }
                if (trim((string) ($trip['proposito'] ?? '')) !== '') {
                    $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Propósito</span></div><div class="f7-row-desc">' . $e(trim((string) $trip['proposito'])) . '</div></div>';
                }
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Cofre común (5.9)</span></div><div class="f7-row-meta">' . number_format((int) ($cofre['berries'] ?? 0)) . ' ฿ — lo gestiona el capitán (trámites 63–67).</div></div>';
                $html .= '</div></div>';
            }
        }
    }

    // Misión en curso (F5.2, 5.20): auto-narrada activa con tramo/acto.
    if (ope7_tabla_existe('misiones') && function_exists('ope7_mision_get') && function_exists('ope7_mision_ultimo_tramo')) {
        $mq = $db->simple_select('ope_misiones', '*', "estado = 'en_curso' AND solicitante_id = " . (int) $f['id'], array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
        $mrow = $db->fetch_array($mq);
        if ($mrow) {
            $m = ope7_mision_get((int) $mrow['id']);
            if ($m) {
                $ult = ope7_mision_ultimo_tramo((int) $m['id']);
                $nombre = (string) ($m['identidad']['nombre'] ?? ('Misión #' . $m['id']));
                $html .= '<div class="plate f7-mision"><div class="plate-h"><span class="t">Misión en curso</span><span class="c">auto-narrada · tramo ' . (int) $ult['tramo'] . '/' . (int) $m['duracion_rondas'] . '</span></div><div class="plate-b">';
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($nombre) . '</span><span class="f7-row-pts">Acto ' . (int) $ult['acto'] . ' de 3</span></div></div>';
                $cond = (array) ($m['condiciones'] ?? array());
                if (trim((string) ($cond['victoria'] ?? '')) !== '') {
                    $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Victoria</span></div><div class="f7-row-meta">' . $e(trim((string) $cond['victoria'])) . '</div></div>';
                }
                if (trim((string) ($cond['fracaso'] ?? '')) !== '') {
                    $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Fracaso</span></div><div class="f7-row-meta">' . $e(trim((string) $cond['fracaso'])) . '</div></div>';
                }
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Posteo de tramo</span></div><div class="f7-row-meta">Trámite 53 — sin posts de la ronda no hay tramo (21.3).</div></div>';
                $html .= '</div></div>';
            }
        }
    }

    // Cibernética (F5.4, 5.22/23): implantes activos con zona/nivel/estado,
    // defectos aplicados y bonos de atributo — SEPARADOS del desglose
    // base+racial (sus bonos viven en su propio sistema, 23.2/5.22 §A.4).
    if (function_exists('ope7_implantes_pj')) {
        $implantes = ope7_implantes_pj((int) $f['id']);
        if ($implantes) {
            $html .= '<div class="plate f7-ciber"><div class="plate-h"><span class="t">Cibernética</span><span class="c">implantes · zona/nivel · estado · bonos aparte</span></div><div class="plate-b">';
            // Bonos de atributo de TODOS los implantes (tope +5 por atributo, 5.22 §A.4).
            $bonos = array('fue' => 0, 'des' => 0, 'agi' => 0, 'res' => 0, 'per' => 0, 'inte' => 0, 'car' => 0, 'vol' => 0);
            $bonos_txt = array();
            foreach ($implantes as $m) {
                $ranuras = json_decode((string) ($m['ranuras'] ?? '[]'), true);
                foreach ((array) $ranuras as $r) {
                    if ((string) ($r['tipo'] ?? '') === 'bonificador') {
                        $det = (string) ($r['detalle'] ?? '');
                        if (preg_match('/(FUE|DES|AGI|RES|PER|INT|CAR|VOL)\s*\+?(\d+)/', $det, $mm)) {
                            $k = strtolower($mm[1]);
                            $bonos[$k] += (int) $mm[2];
                        }
                    }
                }
            }
            foreach ($bonos as $k => $v) {
                if ($v > 0) {
                    $bonos_txt[] = strtoupper($k) . ' +' . $v;
                }
            }
            if ($bonos_txt) {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Bonos de implantes</span><span class="f7-row-pts">' . $e(implode(' · ', $bonos_txt)) . '</span></div>'
                      . '<div class="f7-row-meta">Se aplican aparte del desglose base + racial (tope +5 por atributo, 5.22 §A.4).</div></div>';
            }
            foreach ($implantes as $m) {
                $t = (array) ($m['tabla'] ?? array());
                $estado = (string) $m['estado'];
                $estado_txt = $estado === 'averiado' ? ' ⚠ averiado (sin mantenimiento)' : ucfirst($estado);
                $defectos = array();
                $def_raw = json_decode((string) ($m['defectos'] ?? '[]'), true);
                foreach ((array) $def_raw as $d) {
                    $defectos[] = (string) ($d['nombre'] ?? '');
                }
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e((string) $m['nombre']) . '</span>'
                      . '<span class="f7-row-pts">' . $e((string) $m['zona']) . ' ' . $e((string) $m['nivel']) . ' · ' . $e($estado_txt) . '</span></div>'
                      . '<div class="f7-row-meta">Puerta nv' . (int) ($t['puerta'] ?? 0) . ' · mantenimiento ' . number_format((int) ($t['mant'] ?? 0)) . ' ฿/ronda'
                      . ($defectos ? ' · defectos: ' . $e(implode(', ', array_filter($defectos))) : '') . '</div>'
                      . '</div>';
            }
            $html .= '</div></div>';
        }
    }

    // Linaje (F5.4, 5.22 §B/23.7): familia legendaria activa con su dote y el
    // defecto «La sangre llama» — origen narrativo visible, no toca la balanza
    // de creación (la herencia se juega, no se compra).
    if (ope7_tabla_existe('linaje_personaje') && ope7_tabla_existe('familias_legendarias')) {
        $lq = $db->query('SELECT l.*, f.nombre AS fam_nombre, f.dote AS fam_dote, f.defecto AS fam_defecto, f.lore AS fam_lore '
            . 'FROM ' . ope7_tabla_full('linaje_personaje') . ' l '
            . 'JOIN ' . ope7_tabla_full('familias_legendarias') . ' f ON f.id = l.familia_id '
            . 'WHERE l.personaje_id = ' . (int) $f['id'] . " AND l.estado = 'activo' ORDER BY l.fecha DESC LIMIT 1");
        $lin = $db->fetch_array($lq);
        if ($lin) {
            $html .= '<div class="plate f7-linaje"><div class="plate-h"><span class="t">Linaje</span><span class="c">' . $e((string) $lin['fam_nombre']) . ' · concedido por el staff (23.7)</span></div><div class="plate-b">';
            if (trim((string) ($lin['fam_lore'] ?? '')) !== '') {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">La sangre</span></div><div class="f7-row-desc">' . $e(trim((string) $lin['fam_lore'])) . '</div></div>';
            }
            if (trim((string) ($lin['fam_dote'] ?? '')) !== '') {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Dote de linaje</span><span class="f7-row-pts">+1</span></div>'
                      . '<div class="f7-row-meta">' . $e(trim((string) $lin['fam_dote'])) . ' · origen: narrativo</div></div>';
            }
            if (trim((string) ($lin['fam_defecto'] ?? '')) !== '') {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e(trim((string) $lin['fam_defecto'])) . '</span><span class="f7-row-pts neg">−1</span></div>'
                      . '<div class="f7-row-meta">El legado pesa: la sangre llama (23.7) · origen: narrativo</div></div>';
            }
            if (trim((string) ($lin['motivo'] ?? '')) !== '') {
                $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Motivo de la concesión</span></div><div class="f7-row-meta">' . $e(trim((string) $lin['motivo'])) . '</div></div>';
            }
            $html .= '</div></div>';
        }
    }

    // Reserva de puntos (F4.2, 7.3): stepper por atributo con el techo del
    // nivel y botón que aplica la distribución (ope7_pj_colocar_reserva).
    if (!empty($ctx['puede_gestionar']) && function_exists('ope7_pj_techo_atributo')) {
        $reserva = (int) ($f['reserva'] ?? 0);
        $techo_res = ope7_pj_techo_atributo((int) $f['nivel']);
        $html .= '<div class="plate f7-reserva"><div class="plate-h"><span class="t">Reserva de puntos</span><span class="c">' . $reserva . ' pendientes · techo del nivel ' . $techo_res . '</span></div><div class="plate-b">';
        $flash_res = (string) ($ctx['reserva_flash'] ?? '');
        if ($flash_res !== '') {
            $html .= '<div class="flash ' . (strpos($flash_res, 'Reserva colocada') === 0 ? 'ok' : 'warn') . '">' . $e($flash_res) . '</div>';
        }
        if ($reserva < 1) {
            $html .= '<div class="f7-empty">No tienes puntos en reserva. Al terminar un entrenamiento (compra de PP, trámite 4) entran aquí para colocarlos donde quieras (7.3).</div>';
        } else {
            $html .= '<form method="post" action="' . $e($ctx['bburl']) . '/ficha.php?pid=' . (int) $f['id'] . '" class="f7-reserva-form">'
                   . '<input type="hidden" name="my_post_key" value="' . $e($mybb->post_code ?? '') . '">'
                   . '<input type="hidden" name="gaccion" value="reserva">'
                   . '<div class="f7-reserva-total">Por colocar: <b id="f7-reserva-suma">0</b> de ' . $reserva . '</div>'
                   . '<div class="f7-reserva-steppers">';
            foreach ($ATR as $k => $lab) {
                $base = (int) ($f[$k] ?? 0);
                $bonus = (int) ($mods[$k] ?? 0);
                $actual = $base + $bonus;
                $max_pts = max(0, $techo_res - $actual);
                $html .= '<div class="f7-reserva-row" data-atr="' . $k . '" data-actual="' . $actual . '" data-techo="' . $techo_res . '">'
                       . '<span class="f7-reserva-k">' . $lab . '</span>'
                       . '<span class="f7-reserva-v">' . $actual . '/' . $techo_res . '</span>'
                       . '<div class="f7-stepper">'
                       . '<button type="button" class="f7-step-btn f7-step-menos" aria-label="menos">−</button>'
                       . '<input type="number" name="res_' . $k . '" class="f7-step-input" value="0" min="0" max="' . $max_pts . '" data-max="' . $max_pts . '">'
                       . '<button type="button" class="f7-step-btn f7-step-mas" aria-label="más">+</button>'
                       . '</div>'
                       . '<span class="f7-reserva-info">máx ' . $max_pts . '</span>'
                       . '</div>';
            }
            $html .= '</div>'
                   . '<div class="f7-reserva-actions">'
                   . '<button type="submit" class="btn btn-hot">Colocar reserva</button>'
                   . '</div>'
                   . '</form>';
        }
        $html .= '</div></div>';
    }

    // Narrativa (descripción física, personalidad e historia públicas; las notas
    // solo las ve el dueño y el staff). Las escribe el wizard de creación (paso 1).
    $narr = array(
        'desc_fisica'  => array('Descripción física', false),
        'personalidad' => array('Personalidad', false),
        'historia'     => array('Historia', false),
        'notas'        => array('Notas', true),
    );
    $narr_html = '';
    foreach ($narr as $nk => $nn) {
        $txt = trim((string) ($f[$nk] ?? ''));
        $privada = $nn[1];
        if ($privada && empty($ctx['puede_gestionar']) && empty($ctx['es_staff'])) {
            continue;
        }
        if ($txt === '' && $privada) {
            continue;
        }
        $narr_html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">' . $e($nn[0]) . ($privada ? ' <span class="f7-row-meta">(solo tú y el staff)</span>' : '') . '</span></div>'
                    . '<div class="f7-row-desc">' . ($txt !== '' ? nl2br($e($txt)) : '<span class="f7-empty">Todavía sin redactar.</span>') . '</div></div>';
    }
    if ($narr_html !== '') {
        $html .= '<div class="plate f7-narrativa"><div class="plate-h"><span class="t">Narrativa</span><span class="c">quiénes eres antes de jugar</span></div><div class="plate-b">' . $narr_html . '</div></div>';
    }

    // Sección solo-staff.
    if (!empty($ctx['es_staff'])) {
        $html .= '<div class="plate f7-staff"><div class="plate-h"><span class="t">Solo staff</span><span class="c">no visible para jugadores</span></div><div class="plate-b">';
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Estado del personaje</span><span class="f7-row-pts">' . $e($estado_txt) . '</span></div>'
              . '<div class="f7-row-meta">id ' . (int) $f['id'] . ' · uid ' . (int) $f['uid'] . ' · es_NPC ' . ((int) $f['es_NPC'] ? 'sí' : 'no') . ' · vida: ' . $e($f['estado_vida']) . '</div></div>';
        $html .= '</div></div>';
    }

    // Acciones.
    $html .= '<div class="wiz-nav f7-acts">';
    if (!empty($ctx['puede_gestionar'])) {
        $html .= '<a class="btn btn-ghost" href="' . $e($ctx['bburl']) . '/tramites.php">Mis trámites</a>';
    }
    if (!empty($ctx['es_staff'])) {
        $html .= '<a class="btn btn-ghost" href="' . $e($ctx['bburl']) . '/bandeja.php">Bandeja del staff</a>';
    }
    $html .= '<a class="btn" href="' . $e($ctx['bburl']) . '/personajes.php">Personajes</a>';
    $html .= '</div>';

    $html .= '</div></div>'; // cierra forge
    return $html;
}
