<?php
/**
 * One Piece: 7 Seas · Mundo Vivo (F4.1) — 5.14/5.15
 * -------------------------------------------------
 * El pilar central del foro (principio 2): mares, islas con ficha viva,
 * zonas clave, la ronda mensual y el panel staff «Mundo Vivo».
 *
 * Reglas operativas (Manual del Staff 15.1–15.8):
 *  · La ronda es mensual y única: al cerrarla se aplican los cambios de
 *    isla, recompensas y precios firmados por el staff.
 *  · La IA propone (skill-mundo-vivo), el staff firma: el motor aplica lo
 *    firmado, nunca inventa ni publica solo.
 *  · Todo cambio de isla queda en `isla_estado_historico` con motivo y
 *    fuente (mision/tramite/conquista/suceso/arranque/ronda).
 *  · La peligrosidad es nivel 1–50 comparable al de un personaje (5.16 lo usa).
 *  · Nada se marca publicado sin la acción de publicación del staff.
 *
 * Números cerrados del manual — no recalibrar.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ─────────────────────────────────────────────────────────────
// Datos del mundo: mares, islas, ficha viva, zonas
// ─────────────────────────────────────────────────────────────

/** Lista de mares ordenada por región. */
function ope7_mares_lista()
{
    global $db;
    if (!ope7_tabla_existe('mares')) {
        return array();
    }
    $q = $db->simple_select('ope_mares', '*', '1=1', array('order_by' => 'orden'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Mar por id. */
function ope7_mar_por_id($mar_id)
{
    global $db;
    $mar_id = (int) $mar_id;
    if ($mar_id < 1 || !ope7_tabla_existe('mares')) {
        return null;
    }
    $q = $db->simple_select('ope_mares', '*', "id = {$mar_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Isla por slug. */
function ope7_isla_por_slug($slug)
{
    global $db;
    if ($slug === '' || !ope7_tabla_existe('islas')) {
        return null;
    }
    $q = $db->simple_select('ope_islas', '*', "slug = '" . $db->escape_string((string) $slug) . "'", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Isla por id. */
function ope7_isla_por_id($isla_id)
{
    global $db;
    $isla_id = (int) $isla_id;
    if ($isla_id < 1 || !ope7_tabla_existe('islas')) {
        return null;
    }
    $q = $db->simple_select('ope_islas', '*', "id = {$isla_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Todas las islas (con mar). */
function ope7_islas_lista()
{
    global $db;
    if (!ope7_tabla_existe('islas')) {
        return array();
    }
    $q = $db->query('SELECT i.*, m.nombre AS mar_nombre, m.orden AS mar_orden '
        . 'FROM ' . ope7_tabla_full('islas') . ' i '
        . 'JOIN ' . ope7_tabla_full('mares') . ' m ON m.id = i.mar_id '
        . 'ORDER BY m.orden, i.nombre');
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Ficha viva de la isla (estado + isla + mar), con JSON decodificados. */
function ope7_isla_ficha($isla_id)
{
    global $db;
    $isla_id = (int) $isla_id;
    if ($isla_id < 1 || !ope7_tabla_existe('islas') || !ope7_tabla_existe('isla_estado')) {
        return null;
    }
    $q = $db->query('SELECT i.*, e.*, m.nombre AS mar_nombre, m.orden AS mar_orden '
        . 'FROM ' . ope7_tabla_full('islas') . ' i '
        . 'JOIN ' . ope7_tabla_full('isla_estado') . ' e ON e.isla_id = i.id '
        . 'JOIN ' . ope7_tabla_full('mares') . ' m ON m.id = i.mar_id '
        . "WHERE i.id = {$isla_id} LIMIT 1");
    $r = $db->fetch_array($q);
    if (!$r) {
        return null;
    }
    foreach (array('guarnicion', 'fortificaciones', 'poblacion_orden', 'recursos', 'oferta_demanda',
                   'lugares_clave', 'sucesos', 'hitos', 'recompensas_tesoros', 'presencia_facciones') as $j) {
        $r[$j] = json_decode((string) ($r[$j] ?? ''), true);
    }
    return $r;
}

/** Zonas clave de una isla. */
function ope7_isla_zonas($isla_id)
{
    global $db;
    $isla_id = (int) $isla_id;
    if ($isla_id < 1 || !ope7_tabla_existe('zonas')) {
        return array();
    }
    $q = $db->simple_select('ope_zonas', '*', "isla_id = {$isla_id}", array('order_by' => 'nombre'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/**
 * Cambio de estado de isla con motivo (5.15): actualiza `isla_estado` y
 * registra cada campo cambiado en `isla_estado_historico` (fuente dada).
 * Devuelve array(campos_cambiados). No valida el motivo: quien llama firma.
 */
function ope7_isla_actualizar($isla_id, array $cambios, $fuente, $motivo, $ronda = 0)
{
    global $db;
    $isla_id = (int) $isla_id;
    if ($isla_id < 1 || !ope7_tabla_existe('isla_estado') || !ope7_tabla_existe('isla_estado_historico')) {
        return array();
    }
    $motivo = trim((string) $motivo);
    $fuente = in_array((string) $fuente, array('mision', 'tramite', 'conquista', 'suceso', 'arranque', 'ronda'), true)
        ? (string) $fuente : 'ronda';
    $permitidos = array('peligrosidad', 'afiliacion', 'fuerza_defensiva_nivel', 'quien_manda',
        'guarnicion', 'fortificaciones', 'desarrollo', 'poblacion_orden', 'recursos', 'oferta_demanda',
        'clima_logpose', 'lugares_clave', 'sucesos', 'hitos', 'recompensas_tesoros', 'presencia_facciones');

    $q = $db->simple_select('ope_isla_estado', '*', "isla_id = {$isla_id}", array('limit' => 1));
    $actual = $db->fetch_array($q);
    if (!$actual) {
        return array();
    }

    $sets = array();
    $hist = array();
    foreach ($cambios as $campo => $valor) {
        if (!in_array((string) $campo, $permitidos, true)) {
            continue;
        }
        $v = $valor;
        if (is_array($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        $de = (string) ($actual[$campo] ?? '');
        $a = (string) $v;
        if ($de === $a) {
            continue;
        }
        $sets[$campo] = $v;
        $hist[] = array(
            'isla_id' => $isla_id,
            'ronda'   => (int) $ronda,
            'campo'   => (string) $campo,
            'de_valor'=> $de !== '' ? $de : null,
            'a_valor' => $a !== '' ? $a : null,
            'motivo'  => $motivo,
            'fuente'  => $fuente,
            'fecha'   => TIME_NOW,
        );
    }
    if (!$sets) {
        return array();
    }
    $sets['updated'] = TIME_NOW;
    $db->update_query('ope_isla_estado', $sets, "isla_id = {$isla_id}");
    foreach ($hist as $h) {
        $db->insert_query('ope_isla_estado_historico', $h);
    }
    return array_keys($sets);
}

// ─────────────────────────────────────────────────────────────
// Ronda mensual (5.14): el motor
// ─────────────────────────────────────────────────────────────

/** Ronda activa (la más reciente abierta). */
function ope7_ronda_activa()
{
    global $db;
    if (!ope7_tabla_existe('rondas')) {
        return null;
    }
    $q = $db->query('SELECT * FROM ' . ope7_tabla_full('rondas') . " WHERE estado IN ('abierta','analisis') ORDER BY numero DESC LIMIT 1");
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Ronda por número o null. */
function ope7_ronda_por_numero($numero)
{
    global $db;
    $numero = (int) $numero;
    if ($numero < 1 || !ope7_tabla_existe('rondas')) {
        return null;
    }
    $q = $db->simple_select('ope_rondas', '*', "numero = {$numero}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/**
 * Abre la ronda siguiente: si no hay ronda abierta/analisis, crea la N+1
 * (fecha de inicio = hoy, dashboard vacío). Devuelve la ronda activa.
 */
function ope7_ronda_abrir_siguiente()
{
    global $db;
    if (!ope7_tabla_existe('rondas')) {
        return null;
    }
    $activa = ope7_ronda_activa();
    if ($activa) {
        return $activa;
    }
    $q = $db->simple_select('ope_rondas', 'MAX(numero) AS n', '1=1');
    $max = (int) $db->fetch_field($q, 'n');
    // MyBB convierte null → '' en columnas INT (bug conocido): SQL crudo para
    // conservar el NULL real de `fin`/`dashboard`/`publicado_por`.
    $db->query('INSERT INTO ' . ope7_tabla_full('rondas') . " (numero, inicio, fin, estado, dashboard, publicado_por) "
        . 'VALUES (' . ($max + 1) . ', ' . TIME_NOW . ', NULL, \'abierta\', NULL, NULL)');
    return ope7_ronda_activa();
}

/**
 * Temas presentes abiertos (para la cola de análisis de la ronda).
 * No toca nada: solo lista los candidatos a analizar.
 */
function ope7_ronda_temas_pendientes()
{
    global $db;
    if (!ope7_tabla_existe('temas') || !ope7_tabla_existe('temas_participantes')) {
        return array();
    }
    $q = $db->query('SELECT t.tid, t.tipo, t.fecha_foro, t.zona, t.tema_tipo, '
        . 'GROUP_CONCAT(CONCAT(tp.personaje_id, \':\', tp.tramo) SEPARATOR \',\') AS participantes '
        . 'FROM ' . ope7_tabla_full('temas') . ' t '
        . 'JOIN ' . ope7_tabla_full('temas_participantes') . ' tp ON tp.tema_id = t.tid '
        . "WHERE t.tipo = 'presente' AND t.estado = 'abierto' "
        . 'GROUP BY t.tid ORDER BY t.fecha_real_apertura DESC');
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/**
 * Cambia el estado de la ronda (abierta → analisis → cerrada).
 * A cerrar: fija `fin` y guarda quién la publicó. Devuelve mensaje.
 */
function ope7_ronda_cambiar_estado($ronda_id, $estado, $staff_uid = 0)
{
    global $db;
    $ronda_id = (int) $ronda_id;
    $estado = in_array((string) $estado, array('abierta', 'analisis', 'cerrada'), true) ? (string) $estado : 'abierta';
    if ($ronda_id < 1 || !ope7_tabla_existe('rondas')) {
        return 'Ronda no disponible.';
    }
    $q = $db->simple_select('ope_rondas', '*', "id = {$ronda_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r) {
        return 'Ronda no encontrada.';
    }
    $sets = array('estado' => $estado);
    if ($estado === 'cerrada') {
        $sets['fin'] = TIME_NOW;
        $sets['publicado_por'] = (int) $staff_uid;
    }
    $db->update_query('ope_rondas', $sets, "id = {$ronda_id}");
    return 'Ronda ' . (int) $r['numero'] . " → {$estado}.";
}

/**
 * Aplica el cierre firmado de la ronda: cambios de isla (5.14/15.5),
 * recompensas con motivo (5.14/15.6), fluctuación de precios (5.9/10.2)
 * y archiva el periódico en `historico_periodicos`.
 *
 * $cierre = array(
 *   'islas'   => [ [isla_id, cambios[], motivo], ... ],
 *   'recompensas' => [ [personaje_id, tipo, cantidad, motivo], ... ],
 *   'precios' => [ [objeto_id, zona_id, precio_actual, motivo], ... ],
 *   'periodico' => 'texto del periódico' (si se publica),
 *   'ronda'   => número de ronda,
 * )
 * Devuelve resumen con contadores.
 */
function ope7_ronda_aplicar_cierre(array $cierre)
{
    global $db;
    $ronda = (int) ($cierre['ronda'] ?? 0);
    $resumen = array('islas' => 0, 'recompensas' => 0, 'precios' => 0, 'periodico' => 0);

    foreach ((array) ($cierre['islas'] ?? array()) as $c) {
        $isla_id = (int) ($c['isla_id'] ?? 0);
        $cambios = (array) ($c['cambios'] ?? array());
        $motivo = (string) ($c['motivo'] ?? '');
        if ($isla_id > 0 && $cambios && $motivo !== '') {
            $ok = ope7_isla_actualizar($isla_id, $cambios, 'ronda', $motivo, $ronda);
            if ($ok) {
                $resumen['islas']++;
            }
        }
    }

    foreach ((array) ($cierre['recompensas'] ?? array()) as $rc) {
        $pid = (int) ($rc['personaje_id'] ?? 0);
        $tipo = in_array((string) ($rc['tipo'] ?? ''), array('subida', 'bajada', 'cartel', 'mision', 'suceso'), true)
            ? (string) $rc['tipo'] : 'suceso';
        $cant = (int) ($rc['cantidad'] ?? 0);
        $motivo = (string) ($rc['motivo'] ?? '');
        if ($pid > 0 && $motivo !== '' && ope7_tabla_existe('recompensas_historico')) {
            $db->insert_query('ope_recompensas_historico', array(
                'personaje_id' => $pid,
                'ronda'        => $ronda,
                'tipo'         => $tipo,
                'cantidad'     => $cant,
                'motivo'       => $motivo,
                'firmado_por'  => (int) ($cierre['staff_uid'] ?? 0),
                'fecha'        => TIME_NOW,
            ));
            $resumen['recompensas']++;
        }
    }

    foreach ((array) ($cierre['precios'] ?? array()) as $pc) {
        $oid = (int) ($pc['objeto_id'] ?? 0);
        $zona = (int) ($pc['zona_id'] ?? 0);
        $precio = (int) ($pc['precio_actual'] ?? 0);
        $motivo = (string) ($pc['motivo'] ?? '');
        if ($oid > 0 && $precio > 0 && $motivo !== '' && ope7_tabla_existe('precios_mercado') && ope7_tabla_existe('economia_config')) {
            $q = $db->query('SELECT banda_max, banda_min FROM ' . ope7_tabla_full('economia_config') . ' LIMIT 1');
            $cfg = $db->fetch_array($q);
            $base = (float) ($cfg['banda_max'] ?? 2.0);
            $suelo = (float) ($cfg['banda_min'] ?? 0.5);
            // Banda cerrada 0,5×–2× (5.9): nunca fuera del techo del manual.
            $q2 = $db->simple_select('ope_objetos', 'precio_base', "id = {$oid}", array('limit' => 1));
            $obj_base = (int) $db->fetch_field($q2, 'precio');
            if ($obj_base > 0) {
                $min = (int) round($obj_base * $suelo);
                $max = (int) round($obj_base * $base);
                $precio = max($min, min($max, $precio));
            }
            $existe = $db->simple_select('ope_precios_mercado', 'id', "objeto_id = {$oid} AND zona_id = {$zona}", array('limit' => 1));
            $fila_precio = array(
                'objeto_id' => $oid, 'zona_id' => $zona, 'precio_actual' => $precio,
                'motivo' => $motivo, 'ronda' => $ronda, 'fecha_foro' => ope7_fecha_foro_actual(),
            );
            if ($db->fetch_field($existe, 'id')) {
                $db->update_query('ope_precios_mercado', $fila_precio, "objeto_id = {$oid} AND zona_id = {$zona}");
            } else {
                $db->insert_query('ope_precios_mercado', $fila_precio);
            }
            $resumen['precios']++;
        }
    }

    $periodico = trim((string) ($cierre['periodico'] ?? ''));
    if ($periodico !== '' && ope7_tabla_existe('historico_periodicos')) {
        $q = $db->simple_select('ope_historico_periodicos', 'MAX(numero_edicion) AS n', '1=1');
        $edicion = (int) $db->fetch_field($q, 'n') + 1;
        $db->insert_query('ope_historico_periodicos', array(
            'ronda' => $ronda,
            'numero_edicion' => $edicion,
            'titulo' => (string) ($cierre['periodico_titulo'] ?? 'News Coo — Edición de la ronda'),
            'html' => $periodico,
            'estado' => 'borrador', // visibilidad manual (15.2): nada se publica solo.
            'publicado_por' => (int) ($cierre['staff_uid'] ?? 0),
            'fecha' => TIME_NOW,
        ));
        $resumen['periodico']++;
    }

    return $resumen;
}

// ─────────────────────────────────────────────────────────────
// Panel staff «Mundo Vivo» (A.3)
// ─────────────────────────────────────────────────────────────

/** HTML del panel: ronda actual, cola de temas, matriz de islas. */
function ope7_mundo_vivo_panel_html()
{
    global $db, $mybb;
    $ronda = ope7_ronda_abrir_siguiente();
    $out = '<div class="mv-panel">';

    // ── Cabecera de la ronda ──
    $num = $ronda ? (int) $ronda['numero'] : 0;
    $estado = $ronda ? (string) $ronda['estado'] : 'abierta';
    $estado_label = array('abierta' => 'Abierta', 'analisis' => 'En análisis', 'cerrada' => 'Cerrada');
    $out .= '<div class="plate mv-ronda"><div class="plate-h">Ronda ' . $num . ' · ' . (isset($estado_label[$estado]) ? $estado_label[$estado] : $estado) . '</div><div class="plate-b">';
    if ($ronda && (int) $ronda['inicio'] > 0) {
        $out .= '<p class="mv-dim">Inicio: ' . date('d/m/Y', (int) $ronda['inicio']) . '</p>';
    }
    $pendientes = ope7_ronda_temas_pendientes();
    $out .= '<p class="mv-count">' . count($pendientes) . ' tema(s) presente(s) abierto(s) en la cola de análisis.</p>';

    if ($estado !== 'cerrada') {
        $out .= '<p class="mv-hint">Flujo (15.2): abre el análisis → pega el prompt con los IDs en la skill-mundo-vivo → '
              . 'revisa y edita las salidas (dashboard, islas, recompensas, periódico) → firma el cierre. '
              . 'El motor solo aplica lo firmado; la visibilidad es manual.</p>';
    }
    $out .= '</div></div>';

    // ── Cola de análisis: temas presentes ──
    if ($pendientes) {
        $out .= '<div class="plate mv-cola"><div class="plate-h">Cola de análisis (temas presentes)</div><div class="plate-b"><table class="mv-table"><thead><tr>'
              . '<th>Tema</th><th>Ancla</th><th>Zona</th><th>Tipo</th><th>Participantes</th></tr></thead><tbody>';
        foreach ($pendientes as $t) {
            $out .= '<tr><td>#' . (int) $t['tid'] . '</td><td>' . htmlspecialchars((string) $t['fecha_foro']) . '</td>'
                  . '<td>' . htmlspecialchars((string) $t['zona']) . '</td><td>' . htmlspecialchars((string) $t['tema_tipo']) . '</td>'
                  . '<td>' . htmlspecialchars((string) $t['participantes']) . '</td></tr>';
        }
        $out .= '</tbody></table></div></div>';
    }

    // ── Recompensas con motivo (A.3: «recompensas con motivo») ──
    $out .= '<div class="plate mv-recompensas"><div class="plate-h">Recompensas con motivo</div><div class="plate-b">';
    if (ope7_tabla_existe('recompensas_historico') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT r.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('recompensas_historico') . ' r '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = r.personaje_id '
            . 'ORDER BY r.id DESC LIMIT 15');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="mv-dim">Sin recompensas registradas todavía (el cierre de ronda 15.2 las archiva aquí con su motivo).</p>';
        } else {
            $out .= '<table class="mv-table"><thead><tr><th>Personaje</th><th>Tipo</th><th>Cantidad</th><th>Ronda</th><th>Motivo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $out .= '<tr><td>' . htmlspecialchars((string) ($r['pj_nombre'] ?? '#' . (int) $r['personaje_id'])) . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['tipo']) . '</td>'
                    . '<td>' . number_format((int) $r['cantidad'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . (int) $r['ronda'] . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['motivo']) . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="mv-dim">Tablas no migradas (recompensas_historico).</p>';
    }
    $out .= '</div></div>';

    // ── Periódico «News Coo» (A.3): borrador → publicado, visibilidad manual (15.2) ──
    $out .= '<div class="plate mv-periodico"><div class="plate-h">Periódico «News Coo»</div><div class="plate-b">';
    if (ope7_tabla_existe('historico_periodicos')) {
        $q = $db->query('SELECT * FROM ' . ope7_tabla_full('historico_periodicos') . ' ORDER BY id DESC LIMIT 5');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="mv-dim">Sin ediciones todavía (el cierre de ronda archiva el borrador aquí).</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $estado = (string) $r['estado'];
                $out .= '<div class="mv-row"><div class="mv-arccard-h"><b>' . htmlspecialchars((string) $r['titulo']) . '</b>'
                    . ' <span class="mv-pill">Nº ' . (int) $r['numero_edicion'] . '</span>'
                    . ' <span class="mv-pill">' . ($estado === 'publicado' ? 'publicado' : 'borrador') . '</span>'
                    . ' <span class="mv-dim">ronda ' . (int) $r['ronda'] . '</span></div>'
                    . '<div class="mv-dim">' . date('d/m/Y', (int) $r['fecha']) . '</div>';
                if ($estado === 'borrador') {
                    $out .= '<form method="post" action="mundo-vivo.php"><input type="hidden" name="my_post_key" value="' . htmlspecialchars_uni((string) $mybb->get_input('my_post_key')) . '">'
                        . '<input type="hidden" name="gaccion" value="publicar_periodico"><input type="hidden" name="periodico_id" value="' . (int) $r['id'] . '">'
                        . '<button class="ope-btn" type="submit">Publicar edición</button></form>';
                }
                $out .= '</div>';
            }
        }
    } else {
        $out .= '<p class="mv-dim">Tabla no migrada (historico_periodicos).</p>';
    }
    $out .= '</div></div>';

    // ── Matriz de islas ──
    $islas = ope7_islas_lista();
    $out .= '<div class="plate mv-islas"><div class="plate-h">Matriz de islas (' . count($islas) . ')</div><div class="plate-b">';
    if (!$islas) {
        $out .= '<p class="pj-empty">Sin islas sembradas (ejecuta el seed del mundo).</p>';
    } else {
        $out .= '<table class="mv-table"><thead><tr><th>Isla</th><th>Mar</th><th>Peligr.</th><th>Control</th><th>Defensa</th><th>Desarrollo</th><th>Clima / Log Pose</th></tr></thead><tbody>';
        foreach ($islas as $isla) {
            $ficha = ope7_isla_ficha((int) $isla['id']);
            if (!$ficha) {
                continue;
            }
            $afiliacion = (string) $ficha['afiliacion'];
            $af_label = array('local' => 'Local', 'gobierno' => 'Gobierno', 'salvaje' => 'Salvaje', 'mixta' => 'Mixta');
            $modo = (string) $ficha['modo_viaje'];
            if ($modo !== 'normal') {
                $modo = '<em>' . htmlspecialchars($modo) . '</em>';
            }
            $out .= '<tr><td><strong>' . htmlspecialchars((string) $ficha['nombre']) . '</strong>'
                  . ($ficha['es_canon'] ? ' <span class="mv-canon">canon</span>' : '') . '</td>'
                  . '<td>' . htmlspecialchars((string) $ficha['mar_nombre']) . '</td>'
                  . '<td>' . (int) $ficha['peligrosidad'] . '</td>'
                  . '<td>' . (isset($af_label[$afiliacion]) ? $af_label[$afiliacion] : $afiliacion) . '</td>'
                  . '<td>nv' . (int) $ficha['fuerza_defensiva_nivel'] . '</td>'
                  . '<td>' . htmlspecialchars((string) $ficha['desarrollo']) . '</td>'
                  . '<td>' . htmlspecialchars((string) $ficha['clima_logpose']) . ' ' . $modo . '</td></tr>';
        }
        $out .= '</tbody></table>';
    }
    $out .= '</div></div>';

    $out .= '</div>';
    return $out;
}
