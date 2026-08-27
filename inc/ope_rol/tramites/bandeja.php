<?php
/**
 * One Piece: 7 Seas · Bandeja de trámites (render)
 * -----------------------------------------------------------------------------
 * La bandeja transversal del staff (A.3 «Trámites») y el hub del jugador.
 * Todos los paneles por sistema serán vistas filtradas de este mismo motor.
 * Sin etiquetas de estilo estáticas: todo vive en ope.css scoped por página.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Escapa texto para HTML. */
function ope7_e($v)
{
    return htmlspecialchars_uni((string) $v);
}

/** Fecha corta legible. */
function ope7_fecha($ts)
{
    $ts = (int) $ts;
    if ($ts < 1) {
        return '—';
    }
    return function_exists('my_date') ? my_date('d/m/Y H:i', $ts) : date('d/m/Y H:i', $ts);
}

/** Nombre del usuario por uid (caché estática). */
function ope7_nombre_usuario($uid)
{
    global $db;
    static $cache = array();
    $uid = (int) $uid;
    if ($uid < 1) {
        return 'Sistema';
    }
    if (!isset($cache[$uid])) {
        $cache[$uid] = '—';
        $q = $db->simple_select('users', 'username', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $cache[$uid] = $db->fetch_field($q, 'username');
        }
    }
    return $cache[$uid];
}

/** Cabecera de página reutilizable (shead). */
function ope7_shead($titulo, $kicker, $sub = '')
{
    $html  = '<section class="reveal">' . "\n";
    $html .= '  <div class="shead">' . "\n";
    $html .= '    <h1>' . ope7_e($titulo) . '</h1>' . "\n";
    $html .= '    <span class="code">// ' . ope7_e($kicker) . '</span>' . "\n";
    $html .= '    <span class="rule"></span>' . "\n";
    $html .= '  </div>' . "\n";
    if ($sub !== '') {
        $html .= '  <p class="tram-intro">' . $sub . '</p>' . "\n";
    }
    $html .= '</section>' . "\n";
    return $html;
}

/** Paginación simple (lista larga → 20 por página). */
function ope7_pager_html($base_url, $pagina, $total, $por_pagina = 20)
{
    $paginas = max(1, (int) ceil(max(0, (int) $total) / max(1, $por_pagina)));
    $pagina = min(max(1, (int) $pagina), $paginas);
    if ($paginas < 2) {
        return '';
    }
    $sep = strpos((string) $base_url, '?') === false ? '?' : '&';
    $h = '<nav class="tram-pager" aria-label="Paginación">';
    if ($pagina > 1) {
        $h .= '<a class="ope-btn ope-btn-sm ope-btn-ghost" href="' . ope7_e($base_url) . $sep . 'p=' . ($pagina - 1) . '">← Anterior</a>';
    }
    for ($i = 1; $i <= $paginas; $i++) {
        if ($i === $pagina) {
            $h .= '<span class="tram-pager-cur">' . $i . '</span>';
        } else {
            $h .= '<a class="tram-pager-n" href="' . ope7_e($base_url) . $sep . 'p=' . $i . '">' . $i . '</a>';
        }
    }
    if ($pagina < $paginas) {
        $h .= '<a class="ope-btn ope-btn-sm ope-btn-ghost" href="' . ope7_e($base_url) . $sep . 'p=' . ($pagina + 1) . '">Siguiente →</a>';
    }
    $h .= '</nav>';
    return $h;
}

/** Etiqueta humana de quién inicia el trámite. */
function ope7_quien_label($quien)
{
    $labels = array(
        'jugador'       => 'Lo pides tú',
        'jugador-staff' => 'Tú o el staff',
        'staff'         => 'Solo el staff',
        'staff-jugador' => 'Staff o jugador',
        'capitan'       => 'Capitán de tripulación',
        'capitan-staff' => 'Capitán o staff',
    );
    return isset($labels[$quien]) ? $labels[$quien] : $quien;
}


/**
 * Bandeja transversal del staff.
 * @param int $uid uid del staff.
 * @param int $detalle_tid si > 0, muestra el detalle/firma de ese trámite.
 * @param int $pagina página de la cola (20 por página).
 */
function ope7_bandeja_staff_html($uid, $detalle_tid = 0, $pagina = 1)
{
    global $mybb;

    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    $resumen = ope7_tramite_resumen_catalogo();
    $conteos = ope7_tramite_conteos();

    // ── Detalle de un trámite ──
    if ($detalle_tid > 0) {
        $tr = ope7_tramite_get($detalle_tid);
        if (!$tr) {
            return '<div class="flash warn">Trámite no encontrado.</div>';
        }
        $info = ope7_tramite_info((int) $tr['numero']);
        $hist = ope7_tramite_historico($detalle_tid);
        $estado_lbl = ope7_tramite_estado_label((string) $tr['estado']);

        $html  = ope7_shead('Trámite #' . (int) $tr['id'], 'detalle · firma', 'Nº ' . (int) $tr['numero'] . ' · ' . ope7_e($info['nombre']));
        $html .= '<div class="plate"><div class="plate-b">' . "\n";
        $html .= '  <p><b>Estado:</b> <span class="ope-badge ope-badge-' . ope7_estado_badge_class((string) $tr['estado']) . '">' . ope7_e($estado_lbl) . '</span>';
        $html .= '  <b>Solicitante:</b> ' . ope7_e(ope7_nombre_usuario((int) $tr['solicitante_id'])) . ' · ';
        $html .= '  <b>Skill:</b> ' . ope7_e($info['skill'] !== '' ? $info['skill'] : '—') . ' · ';
        $html .= '  <b>Naturaleza:</b> ' . ope7_e(ope7_naturaleza_label($info['naturaleza'])) . ' · ';
        $html .= '  <b>Firma:</b> ' . ($info['firma'] ? 'sí' : 'no') . '</p>' . "\n";
        if (trim((string) $tr['motivo']) !== '') {
            $html .= '  <p><b>Motivo del solicitante:</b> ' . ope7_e($tr['motivo']) . '</p>' . "\n";
        }
        $html .= '  <p><b>IDs de contexto:</b> <code>' . ope7_e(json_encode($tr['ids'], JSON_UNESCAPED_UNICODE)) . '</code></p>' . "\n";

        // Prompt (copiar).
        $html .= '  <div class="tram-block"><h3 class="tram-h">Prompt generado</h3>' . "\n";
        $html .= '  <textarea class="tram-ta" rows="10" readonly>' . ope7_e((string) $tr['prompt']) . '</textarea>' . "\n";
        $html .= '  <button type="button" class="ope-btn ope-btn-sm ope-btn-ghost" data-copiar="' . (int) $tr['id'] . '">Copiar prompt</button></div>' . "\n";

        // Resultado editable (solo si hay prompt listo / analizado / en revisión).
        if (in_array((string) $tr['estado'], array('prompt_listo', 'analizado', 'en_revision'), true)) {
            $html .= '<form method="post" action="' . $bburl . '/bandeja.php?tramite=' . (int) $tr['id'] . '">' . "\n";
            $html .= '  <input type="hidden" name="tid" value="' . (int) $tr['id'] . '">' . "\n";
            $html .= '  <div class="tram-block"><h3 class="tram-h">Resultado de la IA (editable)</h3>' . "\n";
            $res_texto = $tr['resultado'] !== null ? json_encode($tr['resultado'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
            $html .= '  <textarea class="tram-ta" name="resultado" rows="12" placeholder="Pega aquí el resultado de tu sesión de IA (o edítalo).">' . ope7_e($res_texto) . '</textarea></div>' . "\n";
            $html .= '  <div class="tram-block"><h3 class="tram-h">Firma con motivo (obligatorio)</h3>' . "\n";
            $html .= '  <textarea class="tram-ta" name="motivo" rows="3" placeholder="Motivo de la firma (queda en el histórico auditable)."></textarea>' . "\n";
            $html .= '  <p class="tram-actions">' . "\n";
            $html .= '    <button type="submit" name="accion" value="publicar" class="ope-btn ope-btn-hot">Publicar</button>' . "\n";
            $html .= '    <button type="submit" name="accion" value="rechazar" class="ope-btn ope-btn-ghost">Rechazar</button>' . "\n";
            $html .= '    <button type="submit" name="accion" value="archivar" class="ope-btn ope-btn-ghost">Archivar</button>' . "\n";
            $html .= '  </p></div>' . "\n";
            $html .= '</form>' . "\n";
        }

        // Histórico auditable.
        $html .= '  <div class="tram-block"><h3 class="tram-h">Histórico</h3><ul class="tram-hist">' . "\n";
        foreach ($hist as $h) {
            $html .= '    <li><span class="ope-badge">' . ope7_e((string) $h['estado']) . '</span> '
                   . ope7_e(ope7_nombre_usuario((int) $h['actor_id'])) . ' · ' . ope7_fecha((int) $h['fecha'])
                   . ' — ' . ope7_e((string) $h['motivo']) . '</li>' . "\n";
        }
        $html .= '  </ul></div>' . "\n";
        $html .= '  <p><a class="ope-btn ope-btn-sm ope-btn-ghost" href="' . $bburl . '/bandeja.php">← Volver a la bandeja</a></p>' . "\n";
        $html .= '</div></div>' . "\n";
        return $html;
    }

    // ── Vista lista ──
    $pagina = max(1, (int) $pagina);
    $por_pagina = 20;
    $total_cola = ope7_tramite_contar(array('estado' => array('pendiente', 'prompt_listo', 'analizado', 'en_revision')));
    $pendientes = ope7_tramite_listar(array('estado' => array('pendiente', 'prompt_listo', 'analizado', 'en_revision'), 'pagina' => $pagina), $por_pagina);

    $html  = ope7_shead('Bandeja de trámites', 'motor 5.21 · la IA propone, el staff decide',
        'Catálogo cerrado de <b>' . (int) $resumen['total'] . ' trámites</b> (cap. 22.3). '
        . 'IA + firma: <b>' . (int) $resumen['ia'] . '</b> · Staff: <b>' . (int) $resumen['staff'] . '</b> · '
        . 'Hitos: <b>' . (int) $resumen['hito'] . '</b> · Ligeros (sin IA): <b>' . (int) $resumen['ligero'] . '</b> '
        . '(100 % automáticos: ' . implode(', ', $resumen['automaticos']) . '). '
        . 'Regla de oro: la automatización nunca decide sola — la IA propone, tú firmas.');

    // Pendientes
    $html .= '<div class="plate"><div class="plate-h"><span>En cola (' . (int) $total_cola . ')</span></div><div class="plate-b">' . "\n";
    if (empty($pendientes)) {
        $html .= '<p class="tram-empty">No hay trámites en cola. El catálogo de 67 está listo abajo.</p>' . "\n";
    } else {
        $html .= '<ul class="tram-list">' . "\n";
        foreach ($pendientes as $tr) {
            $info = ope7_tramite_info((int) $tr['numero']);
            $nombre = $info ? $info['nombre'] : $tr['tipo'];
            $html .= '  <li class="tram-row">' . "\n";
            $html .= '    <span class="ope-tag ope-tag-rank">#' . (int) $tr['numero'] . '</span> '
                   . '<b>' . ope7_e($nombre) . '</b>' . "\n";
            $html .= '    <span class="tram-meta">' . ope7_e(ope7_nombre_usuario((int) $tr['solicitante_id'])) . ' · ' . ope7_fecha((int) $tr['fecha_creacion']) . '</span>' . "\n";
            $html .= '    <span class="ope-badge ope-badge-' . ope7_estado_badge_class((string) $tr['estado']) . '">' . ope7_e(ope7_tramite_estado_label((string) $tr['estado'])) . '</span>' . "\n";
            $html .= '    <a class="ope-btn ope-btn-sm ope-btn-ghost" href="' . $bburl . '/bandeja.php?tramite=' . (int) $tr['id'] . '">Revisar / firmar</a>' . "\n";
            $html .= '  </li>' . "\n";
        }
        $html .= '</ul>' . "\n";
        $html .= ope7_pager_html($bburl . '/bandeja.php', $pagina, $total_cola, $por_pagina);
    }
    $html .= '</div></div>' . "\n";

    // Catálogo completo de los 67
    $html .= '<div class="plate"><div class="plate-h"><span>Catálogo completo (67)</span></div><div class="plate-b">' . "\n";
    $html .= ope7_catalogo_tabla_html(true);
    $html .= '</div></div>' . "\n";

    return $html;
}

/** Hub del jugador: sus solicitudes (paginadas) + catálogo de ventanillas por grupo. */
function ope7_tramites_jugador_html($uid, $pagina = 1)
{
    global $mybb;
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    $pagina = max(1, (int) $pagina);
    $por_pagina = 20;
    $total = ope7_tramite_contar(array('solicitante_id' => $uid));
    $mis = ope7_tramite_listar(array('solicitante_id' => $uid, 'pagina' => $pagina), $por_pagina);

    $html  = ope7_shead('Tus trámites', 'ventanillas · motor 5.21',
        'Sigue el estado de tus solicitudes: pendiente → prompt → analizado → revisión → <b>publicado/rechazado</b>. '
        . 'En la validación de ficha y la creación de técnica (ciclo) el resultado vuelve a ti: lo aceptas o pides cambios. '
        . 'El histórico con los motivos del staff es público y auditable.');

    $html .= '<div class="plate"><div class="plate-h"><span>Tus solicitudes (' . (int) $total . ')</span></div><div class="plate-b">' . "\n";
    if (empty($mis)) {
        $html .= '<p class="tram-empty">Todavía no has enviado ningún trámite. Abajo tienes el catálogo de ventanillas.</p>' . "\n";
    } else {
        $html .= '<ul class="tram-list">' . "\n";
        foreach ($mis as $tr) {
            $info = ope7_tramite_info((int) $tr['numero']);
            $nombre = $info ? $info['nombre'] : $tr['tipo'];
            $html .= '  <li class="tram-row">'
                   . '<span class="ope-tag ope-tag-rank">#' . (int) $tr['numero'] . '</span> '
                   . '<b>' . ope7_e($nombre) . '</b> '
                   . '<span class="tram-meta">' . ope7_fecha((int) $tr['fecha_creacion']) . '</span> '
                   . '<span class="ope-badge ope-badge-' . ope7_estado_badge_class((string) $tr['estado']) . '">' . ope7_e(ope7_tramite_estado_label((string) $tr['estado'])) . '</span>'
                   . '</li>' . "\n";
            // Ciclo con usuario (F1.3): el resultado espera tu decisión.
            if ((string) $tr['estado'] === 'revision_usuario' && !empty($tr['resultado'])) {
                $html .= '<li class="tram-ciclo"><div class="tram-ciclo-r">'
                       . '<div class="tram-ciclo-t">Resultado del staff (editable):</div>'
                       . '<pre class="tram-ciclo-pre">' . ope7_e(ope7_tramite_resultado_texto($tr['resultado'])) . '</pre>'
                       . '</div><div class="tram-ciclo-a">'
                       . '<form method="post" action="' . ope7_e($bburl) . '/tramites.php">'
                       . '<input type="hidden" name="tid" value="' . (int) $tr['id'] . '">'
                       . '<input type="hidden" name="accion" value="aceptar">'
                       . '<button type="submit" class="btn btn-hot btn-sm">Aceptar el resultado</button>'
                       . '</form>'
                       . '<form method="post" action="' . ope7_e($bburl) . '/tramites.php" class="tram-ciclo-c">'
                       . '<input type="hidden" name="tid" value="' . (int) $tr['id'] . '">'
                       . '<input type="hidden" name="accion" value="cambios">'
                       . '<input type="text" name="motivo" class="tram-ciclo-in" placeholder="Qué quieres cambiar…" required>'
                       . '<button type="submit" class="btn btn-ghost btn-sm">Pedir cambios</button>'
                       . '</form>'
                       . '</div></li>' . "\n";
            }
        }
        $html .= '</ul>' . "\n";
        $html .= ope7_pager_html($bburl . '/tramites.php', $pagina, $total, $por_pagina);
    }
    $html .= '</div></div>' . "\n";

    $html .= ope7_tramites_hub_html();
    return $html;
}

/** Catálogo de ventanillas del jugador: 6 áreas, tarjetas enlazadas a su página. */
function ope7_tramites_hub_html()
{
    global $mybb;
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    $lista = ope7_tramites_lista();
    $areas = ope7_tramites_areas();
    $solo = ope7_tramites_solo_staff();

    $html  = '<div class="plate"><div class="plate-h"><span>Catálogo de ventanillas (67)</span><span class="c">6 áreas · cada una con su página</span></div><div class="plate-b">' . "\n";
    $html .= '<div class="tram-filtros" role="group" aria-label="Filtrar ventanillas">' . "\n";
    $html .= '  <button type="button" class="tram-chip" data-filtro="yo" aria-pressed="true">Puedo iniciar</button>' . "\n";
    $html .= '  <button type="button" class="tram-chip" data-filtro="auto" aria-pressed="false">Automáticos</button>' . "\n";
    $html .= '  <button type="button" class="tram-chip" data-filtro="ia" aria-pressed="false">IA + firma</button>' . "\n";
    $html .= '  <button type="button" class="tram-chip" data-filtro="todo" aria-pressed="false">Ver todo</button>' . "\n";
    $html .= '</div>' . "\n";

    foreach ($areas as $a) {
        list($titulo, $desc, $numeros) = $a;
        $html .= '<div class="tram-grupo">' . "\n";
        $html .= '  <div class="tram-grupo-h"><span class="t">' . ope7_e($titulo) . '</span><span class="c">' . count($numeros) . ' ventanillas</span></div>' . "\n";
        $html .= '  <p class="tram-grupo-desc">' . ope7_e($desc) . '</p>' . "\n";
        $html .= '  <div class="tram-hub-grid">' . "\n";
        foreach ($lista as $e) {
            $n = (int) $e['numero'];
            if (!in_array($n, $numeros, true)) {
                continue;
            }
            $yo = ope7_tramite_tiene_pagina($n);
            $auto = $e['naturaleza'] === 'ligero';
            $f = array('yo');
            $f[] = $auto ? 'auto' : 'ia';
            if (!$yo) {
                $f[] = 'staff';
            }
            $badge = $auto ? 'g' : ($yo ? 's' : 'r');
            $etiqueta = ope7_naturaleza_label($e['naturaleza']);
            if (!$yo) {
                $etiqueta = 'Solo staff';
            }
            $card = '<div class="tram-card-h"><span class="ope-tag ope-tag-rank">#' . $n . '</span>'
                  . '<span class="ope-badge ope-badge-' . $badge . '">' . ope7_e($etiqueta) . '</span></div>' . "\n"
                  . '<div class="tram-card-n">' . ope7_e($e['nombre']) . '</div>' . "\n"
                  . '<div class="tram-card-d">' . ope7_e($e['efecto']) . '</div>' . "\n"
                  . '<div class="tram-card-meta">' . ope7_e(ope7_quien_label($e['quien'])) . '</div>' . "\n";
            if ($yo) {
                $html .= '  <a class="tram-card" data-f="' . implode(' ', $f) . '" href="' . $bburl . '/tramite-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT) . '.php">' . "\n" . $card . '  </a>' . "\n";
            } else {
                $html .= '  <div class="tram-card tram-card-staff" data-f="' . implode(' ', $f) . '" title="Solo el staff puede iniciarlo (bandeja).">' . "\n" . $card . '  </div>' . "\n";
            }
        }
        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";
    }
    $html .= '</div></div>' . "\n";

    // Filtros por chips (solo un activo a la vez).
    $html .= '<script>(function () {' . "\n";
    $html .= '  var chips = Array.prototype.slice.call(document.querySelectorAll(".tram-filtros .tram-chip"));' . "\n";
    $html .= '  var cards = Array.prototype.slice.call(document.querySelectorAll(".tram-hub-grid .tram-card"));' . "\n";
    $html .= '  var grupos = Array.prototype.slice.call(document.querySelectorAll(".tram-grupo"));' . "\n";
    $html .= '  function aplicar(f) {' . "\n";
    $html .= '    chips.forEach(function (c) { c.setAttribute("aria-pressed", c.getAttribute("data-filtro") === f ? "true" : "false"); });' . "\n";
    $html .= '    cards.forEach(function (card) {' . "\n";
    $html .= '      var ok = f === "todo" || (card.getAttribute("data-f") || "").split(" ").indexOf(f) !== -1;' . "\n";
    $html .= '      card.style.display = ok ? "" : "none";' . "\n";
    $html .= '    });' . "\n";
    $html .= '    grupos.forEach(function (g) {' . "\n";
    $html .= '      var alguno = g.querySelectorAll(".tram-card").length > 0 && Array.prototype.some.call(g.querySelectorAll(".tram-card"), function (c) { return c.style.display !== "none"; });' . "\n";
    $html .= '      g.style.display = alguno ? "" : "none";' . "\n";
    $html .= '    });' . "\n";
    $html .= '  }' . "\n";
    $html .= '  chips.forEach(function (c) { c.addEventListener("click", function () { aplicar(c.getAttribute("data-filtro")); }); });' . "\n";
    $html .= '  aplicar("yo");' . "\n";
    $html .= '})();</script>' . "\n";
    return $html;
}

/** Tabla del catálogo de 67 (solo-staff o público según $staff). */
function ope7_catalogo_tabla_html($staff)
{
    $lista = ope7_tramites_lista();
    $html = '<table class="tram-table"><thead><tr>'
          . '<th>#</th><th>Sistema</th><th>Trámite</th><th>Skill</th><th>Quién</th><th>Naturaleza</th><th>Firma</th>'
          . ($staff ? '<th>Efecto al publicar</th>' : '')
          . '</tr></thead><tbody>' . "\n";
    foreach ($lista as $e) {
        $html .= '<tr>'
               . '<td>' . (int) $e['numero'] . '</td>'
               . '<td class="tram-mono">' . ope7_e($e['sistema']) . '</td>'
               . '<td><b>' . ope7_e($e['nombre']) . '</b></td>'
               . '<td class="tram-mono">' . ope7_e($e['skill'] !== '' ? $e['skill'] : '—') . '</td>'
               . '<td>' . ope7_e($e['quien']) . '</td>'
               . '<td>' . ope7_e(ope7_naturaleza_label($e['naturaleza'])) . '</td>'
               . '<td>' . ($e['firma'] ? 'sí' : '—') . '</td>'
               . ($staff ? '<td class="tram-efecto">' . ope7_e($e['efecto']) . '</td>' : '')
               . '</tr>' . "\n";
    }
    $html .= '</tbody></table>' . "\n";
    return $html;
}

/** Etiqueta humana de un estado. */
function ope7_tramite_estado_label($estado)
{
    $labels = array(
        'borrador'         => 'Borrador',
        'pendiente'        => 'Pendiente',
        'prompt_listo'     => 'Prompt listo',
        'analizado'        => 'Analizado',
        'en_revision'      => 'En revisión',
        'revision_usuario' => 'Espera tu decisión',
        'aceptado_usuario' => 'Aceptado · espera firma',
        'publicado'        => 'Publicado',
        'rechazado'        => 'Rechazado',
        'archivado'        => 'Archivado',
    );
    return isset($labels[$estado]) ? $labels[$estado] : $estado;
}

/** Texto legible de un resultado estructurado (para el ciclo con usuario). */
function ope7_tramite_resultado_texto($resultado)
{
    $lineas = array();
    foreach ((array) $resultado as $k => $v) {
        if (is_array($v) || is_object($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        $lineas[] = $k . ': ' . $v;
    }
    return implode("\n", $lineas);
}

/** Clase de badge según estado. */
function ope7_estado_badge_class($estado)
{
    switch ($estado) {
        case 'publicado': return 'g';
        case 'rechazado': return 'r';
        case 'archivado': return 's';
        default: return 's';
    }
}
