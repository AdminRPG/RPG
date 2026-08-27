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

    // Sección solo-staff.
    if (!empty($ctx['es_staff'])) {
        $html .= '<div class="plate f7-staff"><div class="plate-h"><span class="t">Solo staff</span><span class="c">no visible para jugadores</span></div><div class="plate-b">';
        $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Estado del personaje</span><span class="f7-row-pts">' . $e($estado_txt) . '</span></div>'
              . '<div class="f7-row-meta">id ' . (int) $f['id'] . ' · uid ' . (int) $f['uid'] . ' · es_NPC ' . ((int) $f['es_NPC'] ? 'sí' : 'no') . ' · vida: ' . $e($f['estado_vida']) . '</div></div>';
        if ($f['historia']) {
            $html .= '<div class="f7-row"><div class="f7-row-h"><span class="f7-row-name">Historia</span></div><div class="f7-row-desc">' . $e($f['historia']) . '</div></div>';
        }
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
