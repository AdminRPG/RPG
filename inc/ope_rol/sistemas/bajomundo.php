<?php
/**
 * One Piece: 7 Seas · Bajo mundo e información (F6) — 5.13/cap. 14
 * ------------------------------------------------------------------
 * Trámites 25–33 (el último bloque de trámites del catálogo):
 *   · 25 — Solicitar rumor a la red (ia + firma): la IA propone la ficha del
 *          rumor según la capacidad del espía y el tiempo; el staff fija la
 *          veracidad interna y firma. Cobra el mantenimiento de la ronda.
 *   · 26 — Comprar rumor (ligero): pago de cartera según multiplicadores de
 *          fiabilidad × frescura (techo global 0,5×–2×); ficha transferida.
 *   · 27 — Contrastar rumor (ia + firma): coste por alcance × sensibilidad,
 *          afina la fiabilidad un grado y en Sólido revela la veracidad.
 *   · 28 — Vender rumor (ligero): transferencia entre jugadores con copia del
 *          vendedor; se vende con la fiabilidad publicada.
 *   · 29 — Montar/ampliar la red (ligero): contrato + mantenimiento, límite 4
 *          espías en combos equivalentes; sin pago la red se desactiva.
 *   · 30 — Publicar cartel (staff + firma): cifra, paradero con fiabilidad,
 *          caducidad de paradero a 3 rondas (14.6).
 *   · 31 — Cobrar recompensa (ia + firma): entrega verificada (5.10), registro
 *          en `recompensas_historico` + actualización del Wanted.
 *   · 32 — Crear rumor falso (propaganda, ia + firma): veracidad interna falsa,
 *          fiabilidad publicada decidida por el staff.
 *   · 33 — Ataque a una red (ia + firma): veredicto sin dados (14.5).
 *
 * Números cerrados del manual (14.2–14.6) — no recalibrar.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Tipos de espía: qué investiga, alcance, contrato y mantenimiento (14.2.3). */
function ope7_espia_tipos()
{
    return array(
        'novato'        => array('categorias' => array('suspiro', 'murmullo'), 'alcance' => 'local',     'coste' => 5000,   'mantenimiento' => 500),
        'avanzado'      => array('categorias' => array('suspiro', 'murmullo', 'susurro'), 'alcance' => 'regional',  'coste' => 25000,  'mantenimiento' => 2500),
        'experimentado' => array('categorias' => array('suspiro', 'murmullo', 'susurro', 'alto_susurro'), 'alcance' => 'regional', 'coste' => 100000, 'mantenimiento' => 10000),
        'supremo'       => array('categorias' => array('suspiro', 'murmullo', 'susurro', 'alto_susurro'), 'alcance' => 'mundial',   'coste' => 500000, 'mantenimiento' => 50000),
    );
}

/** Ficha de rumor o null. */
function ope7_rumor_info($rumor_id)
{
    global $db;
    $rumor_id = (int) $rumor_id;
    if ($rumor_id < 1 || !ope7_tabla_existe('rumores')) {
        return null;
    }
    $q = $db->simple_select('ope_rumores', '*', "id = {$rumor_id}", array('limit' => 1));
    return $db->fetch_array($q) ?: null;
}

/** Multiplicadores de precio de un rumor (14.2.2): fiabilidad × frescura. */
function ope7_rumor_multiplier($rumor)
{
    $mult_fiab = array('rumoroso' => 0.6, 'plausible' => 1.0, 'solido' => 1.5);
    $mult_fresc = array('fresco' => 1.2, 'familiar' => 1.0, 'frio' => 0.5);
    $m = (float) ($mult_fiab[(string) ($rumor['fiabilidad'] ?? '')] ?? 1.0);
    $f = (float) ($mult_fresc[(string) ($rumor['frescura'] ?? '')] ?? 1.0);
    $precio = (int) round((int) ($rumor['precio_base'] ?? 0) * $m * $f);
    // Techo global de la economía (10.2/14.2.2): nadie paga el cuádruple.
    $base = max(1, (int) ($rumor['precio_base'] ?? 0));
    $precio = max((int) round($base * 0.5), min((int) round($base * 2), $precio));
    return array('fiabilidad' => $m, 'frescura' => $f, 'precio' => $precio);
}

/** Coste del contraste (14.4): base por alcance × sensibilidad del objetivo. */
function ope7_contraste_coste($alcance, $sensibilidad)
{
    $base = array('local' => 1000, 'regional' => 5000, 'mundial' => 50000);
    $sens = array('comun' => 1, 'figura' => 2, 'criminal' => 3, 'oculta' => 5, 'entidad' => 10);
    $b = (int) ($base[(string) $alcance] ?? 1000);
    $s = (int) ($sens[(string) $sensibilidad] ?? 1);
    return $b * $s;
}

/** Red activa del personaje (o null). */
function ope7_red_del_pj($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('red_espionaje')) {
        return null;
    }
    $q = $db->simple_select('ope_red_espionaje', '*', "dueno_id = {$pid} AND estado = 'activa'", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
    return $db->fetch_array($q) ?: null;
}

/** Espías activos de una red (o de un personaje). */
function ope7_red_espias($red_id)
{
    global $db;
    $red_id = (int) $red_id;
    if ($red_id < 1 || !ope7_tabla_existe('espias')) {
        return array();
    }
    $q = $db->simple_select('ope_espias', '*', "red_id = {$red_id} AND estado = 'activo'", array('order_by' => 'id'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $r['capacidad_json'] = !empty($r['capacidad']) ? json_decode((string) $r['capacidad'], true) : array();
        $out[] = $r;
    }
    return $out;
}

/** Registra una operación de rumor (histórico inmutable, 14.8). */
function ope7_rumor_operar($rumor_id, $tipo, $solicitante_id, $cobro, $motivo, $veredicto = array())
{
    global $db;
    $tipo = in_array((string) $tipo, array('compra', 'venta', 'contraste', 'propagacion'), true) ? (string) $tipo : 'compra';
    if (!ope7_tabla_existe('rumor_operaciones')) {
        return false;
    }
    $db->insert_query('ope_rumor_operaciones', array(
        'rumor_id'       => (int) $rumor_id,
        'tipo'           => $tipo,
        'solicitante_id' => (int) $solicitante_id,
        'cobro'          => (int) $cobro,
        'motivo'         => $db->escape_string((string) $motivo),
        'veredicto'      => $veredicto ? $db->escape_string(json_encode($veredicto, JSON_UNESCAPED_UNICODE)) : null,
        'fecha'          => TIME_NOW,
    ));
    return true;
}

/** ¿El personaje tiene el rumor (compra/venta a su favor)? (14.2.4/14.6) */
function ope7_rumor_en_poder($pid, $rumor_id)
{
    global $db;
    $pid = (int) $pid;
    $rumor_id = (int) $rumor_id;
    if ($pid < 1 || $rumor_id < 1 || !ope7_tabla_existe('rumor_operaciones')) {
        return false;
    }
    $q = $db->simple_select('ope_rumor_operaciones', 'id',
        "rumor_id = {$rumor_id} AND solicitante_id = {$pid} AND tipo IN ('compra','venta','contraste')",
        array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
    return $db->num_rows($q) > 0;
}

// ─────────────────────────────────────────────────────────────
// Efectos de los trámites 25–33
// ─────────────────────────────────────────────────────────────

/** Efecto 25 · Solicitar rumor a la red (14.2.3, ia + firma). */
function ope7_efecto_rumor_red($tr, $pid, $res, $ids)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('rumores') || !ope7_tabla_existe('red_espionaje') || !ope7_tabla_existe('espias')) {
        return 'Solicitar rumor: tablas no migradas (pendiente).';
    }
    $red = ope7_red_del_pj($pid);
    if (!$red) {
        return 'Solicitar rumor BLOQUEADO: no tienes una red activa (monta una con el trámite 29).';
    }
    $espia_id = (int) ($ids['espia_id'] ?? 0);
    $q = $db->simple_select('ope_espias', '*', "id = {$espia_id} AND red_id = " . (int) $red['id'] . " AND estado = 'activo'", array('limit' => 1));
    $espia = $db->fetch_array($q);
    if (!$espia) {
        return 'Solicitar rumor BLOQUEADO: elige un espía activo de tu red (29).';
    }
    $tipo = ope7_espia_tipos();
    $cfg = $tipo[(string) $espia['tipo']] ?? null;
    if (!$cfg) {
        return 'Solicitar rumor BLOQUEADO: tipo de espía no reconocido.';
    }

    // La IA propone la ficha del rumor; el staff la edita y firma.
    $contenido = trim((string) ($res['contenido'] ?? ''));
    if ($contenido === '') {
        return 'Solicitar rumor BLOQUEADO: la ficha del rumor (contenido) no llegó a la firma.';
    }
    $alcance = (string) ($res['alcance'] ?? 'local');
    if (!in_array($alcance, array('local', 'regional', 'mundial'), true)) {
        return 'Solicitar rumor BLOQUEADO: alcance no válido (local/regional/mundial).';
    }
    $categoria = (string) ($res['categoria'] ?? 'suspiro');
    if (!in_array($categoria, $cfg['categorias'], true)) {
        return 'Solicitar rumor BLOQUEADO: un espía ' . $espia['tipo'] . ' no investiga «' . $categoria . '» (capacidad 14.2.3: ' . implode('/', $cfg['categorias']) . ').';
    }
    if ($alcance === 'mundial' && $cfg['alcance'] !== 'mundial') {
        return 'Solicitar rumor BLOQUEADO: alcance mundial exige un espía Supremo (14.2.3).';
    }
    if ($alcance === 'regional' && !in_array($cfg['alcance'], array('regional', 'mundial'), true)) {
        return 'Solicitar rumor BLOQUEADO: alcance regional exige un espía Avanzado o superior.';
    }

    $fiabilidad = (string) ($res['fiabilidad'] ?? 'rumoroso');
    if (!in_array($fiabilidad, array('rumoroso', 'plausible', 'solido'), true)) {
        $fiabilidad = 'rumoroso';
    }
    $veracidad = (string) ($res['veracidad'] ?? 'dudoso');
    if (!in_array($veracidad, array('verdadero', 'dudoso', 'falso'), true)) {
        $veracidad = 'dudoso';
    }
    $ronda = 0;
    if (function_exists('ope7_ronda_activa')) {
        $ra = ope7_ronda_activa();
        $ronda = (int) ($ra['numero'] ?? 0);
    }

    $rumor_id = $db->insert_query('ope_rumores', array(
        'isla_id'     => (int) ($ids['isla_id'] ?? ($res['isla_id'] ?? 0)),
        'tipo'        => in_array((string) ($res['tipo'] ?? ''), array('suceso', 'tesoro', 'persona', 'faccion'), true) ? (string) $res['tipo'] : 'suceso',
        'contenido'   => $db->escape_string($contenido),
        'veracidad'   => $veracidad,   // solo-staff
        'fiabilidad'  => $fiabilidad,  // publicada
        'alcance'     => $alcance,
        'frescura'    => 'fresco',
        'ronda_origen'=> $ronda,
        'creador_id'  => $pid,
        'precio_base' => (int) ($res['precio_base'] ?? 0),
        'estado'      => 'activo',
    ));

    // Mantenimiento de la ronda (14.2.3: los espías cobran su mantenimiento).
    $mant = (int) $cfg['mantenimiento'];
    $mov = ope7_cartera_mover($pid, 'cartera', -$mant);
    if (!$mov['ok']) {
        $db->delete_query('ope_rumores', "id = {$rumor_id}");
        return 'Solicitar rumor BLOQUEADO: ' . $mov['msg'] . ' (mantenimiento del espía ' . $espia['tipo'] . ': ' . $mant . ' ฿/ronda).';
    }
    ope7_rumor_operar($rumor_id, 'compra', $pid, $mant,
        'Solicitud a la red (espía ' . $espia['tipo'] . ', ' . $categoria . '): mantenimiento de ronda.',
        array('red_id' => (int) $red['id'], 'espia_id' => (int) $espia['id'], 'veracidad' => $veracidad));

    return 'Rumor obtenido de tu red: «' . $contenido . '» (' . $alcance . ', fiabilidad ' . $fiabilidad
        . '). Cobrado ' . $mant . ' ฿ de mantenimiento. La veracidad interna (' . $veracidad . ') es solo-staff: solo aflora vía contraste a Sólido (14.4).';
}

/** Efecto 26 · Comprar rumor (14.2.2, ligero). */
function ope7_efecto_comprar_rumor($pid, $ids, $tr)
{
    global $db;
    $rumor_id = (int) ($ids['rumor_id'] ?? 0);
    if ($pid < 1 || $rumor_id < 1 || !ope7_tabla_existe('rumores')) {
        return 'Comprar rumor: faltan datos (personaje o rumor).';
    }
    $rumor = ope7_rumor_info($rumor_id);
    if (!$rumor || (string) $rumor['estado'] === 'retirado') {
        return 'Comprar rumor BLOQUEADO: el rumor no está disponible.';
    }
    if (ope7_rumor_en_poder($pid, $rumor_id)) {
        return 'Comprar rumor BLOQUEADO: ya tienes este rumor (14.2.4 — la copia registrada basta).';
    }
    $mult = ope7_rumor_multiplier($rumor);
    $mov = ope7_cartera_mover($pid, 'cartera', -$mult['precio']);
    if (!$mov['ok']) {
        return 'Comprar rumor BLOQUEADO: ' . $mov['msg'] . ' (precio ' . $mult['precio'] . ' ฿, 14.2.2).';
    }
    ope7_rumor_operar($rumor_id, 'compra', $pid, $mult['precio'],
        'Compra en el mercado de rumores (fiabilidad ×' . number_format($mult['fiabilidad'], 1, ',', '') . ' · frescura ×' . number_format($mult['frescura'], 1, ',', '') . ').',
        array('precio' => $mult['precio']));
    return 'Rumor comprado: «' . $rumor['contenido'] . '» (' . $rumor['alcance'] . ', fiabilidad ' . $rumor['fiabilidad']
        . ') por ' . $mult['precio'] . ' ฿. La ficha queda en tu poder (14.2.4).';
}

/** Efecto 27 · Contrastar rumor (14.4, ia + firma). */
function ope7_efecto_contrastar_rumor($tr, $pid, $res, $ids)
{
    global $db;
    $rumor_id = (int) ($ids['rumor_id'] ?? 0);
    if ($pid < 1 || $rumor_id < 1 || !ope7_tabla_existe('rumores')) {
        return 'Contrastar rumor: faltan datos (personaje o rumor).';
    }
    $rumor = ope7_rumor_info($rumor_id);
    if (!$rumor) {
        return 'Contrastar rumor BLOQUEADO: el rumor no existe.';
    }
    if (!ope7_rumor_en_poder($pid, $rumor_id)) {
        return 'Contrastar rumor BLOQUEADO: no tienes este rumor en tu poder (cópmpalo primero, 26).';
    }
    $sensibilidad = (string) ($ids['sensibilidad'] ?? 'comun');
    $coste = ope7_contraste_coste((string) $rumor['alcance'], $sensibilidad);
    $mov = ope7_cartera_mover($pid, 'cartera', -$coste);
    if (!$mov['ok']) {
        return 'Contrastar rumor BLOQUEADO: ' . $mov['msg'] . ' (contraste ' . $coste . ' ฿, 14.4).';
    }

    // Afina la fiabilidad un grado (Rumoroso → Plausible → Sólido); en Sólido
    // se revela la veracidad interna al solicitante (14.4).
    $escala = array('rumoroso' => 0, 'plausible' => 1, 'solido' => 2);
    $grado = (int) ($escala[(string) $rumor['fiabilidad']] ?? 0);
    $nueva = array_search(min(2, $grado + 1), $escala, true);
    $revela = $nueva === 'solido';
    $db->update_query('ope_rumores', array('fiabilidad' => $nueva), "id = {$rumor_id}");
    ope7_rumor_operar($rumor_id, 'contraste', $pid, $coste,
        'Contraste (' . $rumor['alcance'] . ' × sensibilidad ' . $sensibilidad . '): ' . $rumor['fiabilidad'] . ' → ' . $nueva . '.',
        array('veracidad_revelada' => $revela ? (string) $rumor['veracidad'] : null));

    return 'Contraste resuelto: la fiabilidad de «' . $rumor['contenido'] . '» sube a ' . $nueva
        . ' (coste ' . $coste . ' ฿). '
        . ($revela ? 'Al llegar a Sólido se revela la veracidad interna: **' . $rumor['veracidad'] . '**.' : 'Contrastar a Sólido revela la veracidad interna (14.4).');
}

/** Efecto 28 · Vender rumor (14.2.4, ligero). */
function ope7_efecto_vender_rumor($pid, $ids, $tr)
{
    global $db;
    $rumor_id = (int) ($ids['rumor_id'] ?? 0);
    $comprador = (int) ($ids['comprador_id'] ?? 0);
    $precio = (int) ($ids['precio'] ?? 0);
    if ($pid < 1 || $rumor_id < 1 || $comprador < 1 || $comprador === $pid || $precio < 0) {
        return 'Vender rumor: indica rumor, comprador (otro personaje) y precio.';
    }
    if ($precio < 0 || $precio > 10000000000) {
        return 'Vender rumor BLOQUEADO: precio fuera de rango (14.2.4).';
    }
    $rumor = ope7_rumor_info($rumor_id);
    if (!$rumor) {
        return 'Vender rumor BLOQUEADO: el rumor no existe.';
    }
    if (!ope7_rumor_en_poder($pid, $rumor_id)) {
        return 'Vender rumor BLOQUEADO: no tienes este rumor para venderlo (14.2.4).';
    }
    $mov = ope7_cartera_mover($comprador, 'cartera', -$precio);
    if (!$mov['ok']) {
        return 'Vender rumor BLOQUEADO: ' . $mov['msg'] . ' (el comprador no cubre el precio).';
    }
    if ($precio > 0) {
        ope7_cartera_mover($pid, 'cartera', $precio);
    }
    // El vendedor conserva una copia (14.2.4); la venta queda registrada.
    ope7_rumor_operar($rumor_id, 'venta', $comprador, $precio,
        'Venta entre jugadores (vendedor #' . $pid . '): el vendedor conserva su copia.',
        array('vendedor_id' => $pid));
    return 'Rumor vendido a #' . $comprador . ' por ' . $precio . ' ฿. Te quedas con tu copia (14.2.4: la información no se destruye al venderse).';
}

/** Efecto 29 · Montar/ampliar la red (14.2.3, ligero). */
function ope7_efecto_montar_red($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('red_espionaje') || !ope7_tabla_existe('espias')) {
        return 'Montar/ampliar la red: tablas no migradas (pendiente).';
    }
    $tipo = (string) ($ids['tipo'] ?? '');
    $tipos = ope7_espia_tipos();
    if (!isset($tipos[$tipo])) {
        return 'Montar/ampliar la red BLOQUEADO: tipo de espía no válido (novato/avanzado/experimentado/supremo).';
    }
    $cfg = $tipos[$tipo];
    $red = ope7_red_del_pj($pid);
    $espias = $red ? ope7_red_espias((int) $red['id']) : array();
    if (count($espias) >= 4) {
        return 'Montar/ampliar la red BLOQUEADO: límite de 4 espías en combos equivalentes (14.2.3).';
    }
    if ($red && $tipo === 'supremo' && count($espias) >= 1) {
        $tiene_supremo = false;
        foreach ($espias as $e) {
            if ((string) $e['tipo'] === 'supremo') {
                $tiene_supremo = true;
            }
        }
        if ($tiene_supremo && count($espias) >= 2) {
            return 'Montar/ampliar la red BLOQUEADO: máximo un Supremo + otro espía en combos equivalentes (14.2.3).';
        }
    }
    // Contrato del espía (se paga al incorporarlo).
    $mov = ope7_cartera_mover($pid, 'cartera', -(int) $cfg['coste']);
    if (!$mov['ok']) {
        return 'Montar/ampliar la red BLOQUEADO: ' . $mov['msg'] . ' (contrato ' . $cfg['coste'] . ' ฿, 14.2.3).';
    }
    if (!$red) {
        $red_id = $db->insert_query('ope_red_espionaje', array(
            'dueno_id' => $pid,
            'nombre'   => $db->escape_string((string) ($ids['nombre'] ?? 'Red de ' . $pid)),
            'estado'   => 'activa',
            'fecha'    => TIME_NOW,
        ));
    } else {
        $red_id = (int) $red['id'];
    }
    $db->insert_query('ope_espias', array(
        'red_id'        => $red_id,
        'espia_id'      => 0,
        'tipo'          => $tipo,
        'capacidad'     => $db->escape_string(json_encode(array('categorias' => $cfg['categorias'], 'alcance' => $cfg['alcance']), JSON_UNESCAPED_UNICODE)),
        'coste'         => (int) $cfg['coste'],
        'mantenimiento' => (int) $cfg['mantenimiento'],
        'estado'        => 'activo',
    ));
    return 'Red ' . ($red ? 'ampliada' : 'montada') . ': espía ' . $tipo . ' incorporado (contrato ' . $cfg['coste']
        . ' ฿, mantenimiento ' . $cfg['mantenimiento'] . ' ฿/ronda, alcance ' . $cfg['alcance'] . '). '
        . 'Sin mantenimiento al cierre de ronda la red se desactiva (14.5).';
}

/** Efecto 30 · Publicar cartel (14.6, staff + firma). */
function ope7_efecto_publicar_cartel($tr, $pid, $res, $ids)
{
    global $db;
    if (!ope7_tabla_existe('carteles_recompensa') || !ope7_tabla_existe('personajes')) {
        return 'Publicar cartel: tablas no migradas (pendiente).';
    }
    $objetivo = (int) ($ids['personaje_id'] ?? $pid);
    if ($objetivo < 1) {
        return 'Publicar cartel BLOQUEADO: indica el personaje buscado.';
    }
    $cifra = (int) ($res['cifra'] ?? ($ids['cifra'] ?? 0));
    if ($cifra < 100000) {
        return 'Publicar cartel BLOQUEADO: la escala de carteles empieza en cientos de miles (5.9/14.6).';
    }
    $paradero = trim((string) ($res['paradero_publicado'] ?? ($ids['paradero'] ?? '')));
    if ($paradero === '') {
        return 'Publicar cartel BLOQUEADO: indica el paradero publicado (o «paradero desconocido»).';
    }
    $fiab = (string) ($res['fiabilidad_paradero'] ?? 'plausible');
    if (!in_array($fiab, array('rumoroso', 'plausible', 'solido'), true)) {
        $fiab = 'plausible';
    }
    $ronda = 0;
    if (function_exists('ope7_ronda_activa')) {
        $ra = ope7_ronda_activa();
        $ronda = (int) ($ra['numero'] ?? 0);
    }
    $cartel_id = $db->insert_query('ope_carteles_recompensa', array(
        'personaje_id'          => $objetivo,
        'cifra'                 => $cifra,
        'paradero_publicado'    => $db->escape_string($paradero),
        'fiabilidad_paradero'   => $fiab,
        'estado'                => 'vigente',
        'ronda_emision'         => $ronda,
        'ronda_caducidad_paradero' => $ronda + 3, // 3 rondas (14.6)
        'emitido_por'           => (int) ($tr['_staff_uid'] ?? 0),
    ));
    // Registro en el histórico de recompensas (misma disciplina que 5.14 §8).
    if (ope7_tabla_existe('recompensas_historico')) {
        $db->insert_query('ope_recompensas_historico', array(
            'personaje_id' => $objetivo,
            'ronda'        => $ronda,
            'tipo'         => 'cartel',
            'cantidad'     => $cifra,
            'motivo'       => $db->escape_string((string) ($tr['_firma_motivo'] ?? 'Emisión de cartel.')),
            'firmado_por'  => (int) ($tr['_staff_uid'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }
    return 'Cartel publicado: ' . $cifra . ' ฿ por #' . $objetivo . ' (paradero: ' . $paradero . ', fiabilidad ' . $fiab
        . '). El paradero caduca a las 3 rondas sin avistamiento actualizado (14.6).';
}

/** Efecto 31 · Cobrar recompensa (14.6, ia + firma). */
function ope7_efecto_cobrar_cartel($tr, $pid, $res, $ids)
{
    global $db;
    $cartel_id = (int) ($ids['cartel_id'] ?? 0);
    if ($pid < 1 || $cartel_id < 1 || !ope7_tabla_existe('carteles_recompensa')) {
        return 'Cobrar recompensa: faltan datos (personaje o cartel).';
    }
    $q = $db->simple_select('ope_carteles_recompensa', '*', "id = {$cartel_id}", array('limit' => 1));
    $cartel = $db->fetch_array($q);
    if (!$cartel) {
        return 'Cobrar recompensa BLOQUEADO: el cartel no existe.';
    }
    if ((string) $cartel['estado'] === 'frio') {
        return 'Cobrar recompensa BLOQUEADO: paradero frío (más de 3 rondas sin avistamiento, 14.6). Re-contrasta antes de cazar.';
    }
    if ((string) $cartel['estado'] !== 'vigente') {
        return 'Cobrar recompensa BLOQUEADO: el cartel no está vigente (estado ' . $cartel['estado'] . ').';
    }
    // Anti-abuso (14.6): sin entrega verificada no hay cobro; autocaza es abuso.
    if ((int) $cartel['personaje_id'] === (int) $pid) {
        return 'Cobrar recompensa BLOQUEADO (anti-abuso, 14.6): cobrar tu propio cartel es autocaza.';
    }
    // Paradero frío: 3 rondas sin avistamiento actualizado → no cazable (14.6).
    $ronda = 0;
    if (function_exists('ope7_ronda_activa')) {
        $ra = ope7_ronda_activa();
        $ronda = (int) ($ra['numero'] ?? 0);
    }
    if ((int) $cartel['ronda_caducidad_paradero'] > 0 && $ronda > (int) $cartel['ronda_caducidad_paradero']) {
        return 'Cobrar recompensa BLOQUEADO: paradero frío (más de 3 rondas sin avistamiento, 14.6). Re-contrasta antes de cazar.';
    }
    // Entrega verificada: el staff firma el veredicto de 5.10 (captura resuelta).
    $entrega = (string) ($res['entrega'] ?? '');
    if ($entrega === '') {
        return 'Cobrar recompensa BLOQUEADO: falta el veredicto de entrega (tema presente resuelto con veredicto de 5.10, 14.6).';
    }
    $cifra = (int) $cartel['cifra'];
    $mov = ope7_cartera_mover($pid, 'cartera', $cifra);
    if (!$mov['ok']) {
        return 'Cobrar recompensa: no se pudo abonar la cifra (' . $mov['msg'] . ').';
    }
    $db->update_query('ope_carteles_recompensa', array('estado' => 'cobrado'), "id = {$cartel_id}");
    if (ope7_tabla_existe('recompensas_historico')) {
        $db->insert_query('ope_recompensas_historico', array(
            'personaje_id' => $pid,
            'ronda'        => $ronda,
            'tipo'         => 'cartel',
            'cantidad'     => $cifra,
            'motivo'       => $db->escape_string('Cobro de cartel #' . $cartel_id . ' (' . $entrega . '). ' . (string) ($tr['_firma_motivo'] ?? '')),
            'firmado_por'  => (int) ($tr['_staff_uid'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }
    return 'Recompensa cobrada: ' . $cifra . ' ฿ abonados a tu cartera (cartel #' . $cartel_id . ', entrega verificada: ' . $entrega
        . '). El Wanted del buscado se actualiza y el periódico puede contarlo (14.6).';
}

/** Efecto 32 · Crear rumor falso (propaganda, 14.2.1/14.8, ia + firma). */
function ope7_efecto_rumor_falso($tr, $pid, $res, $ids)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('rumores')) {
        return 'Crear rumor falso: tablas no migradas (pendiente).';
    }
    $contenido = trim((string) ($res['contenido'] ?? ''));
    if ($contenido === '') {
        return 'Crear rumor falso BLOQUEADO: falta el contenido (el rumor que siembras).';
    }
    $fiabilidad = (string) ($res['fiabilidad'] ?? 'plausible');
    if (!in_array($fiabilidad, array('rumoroso', 'plausible', 'solido'), true)) {
        $fiabilidad = 'plausible';
    }
    $alcance = (string) ($res['alcance'] ?? 'local');
    if (!in_array($alcance, array('local', 'regional', 'mundial'), true)) {
        $alcance = 'local';
    }
    $ronda = 0;
    if (function_exists('ope7_ronda_activa')) {
        $ra = ope7_ronda_activa();
        $ronda = (int) ($ra['numero'] ?? 0);
    }
    $rumor_id = $db->insert_query('ope_rumores', array(
        'isla_id'      => (int) ($ids['isla_id'] ?? ($res['isla_id'] ?? 0)),
        'tipo'         => in_array((string) ($res['tipo'] ?? ''), array('suceso', 'tesoro', 'persona', 'faccion'), true) ? (string) $res['tipo'] : 'suceso',
        'contenido'    => $db->escape_string($contenido),
        'veracidad'    => 'falso', // propaganda: veracidad interna falsa (14.8)
        'fiabilidad'   => $fiabilidad,
        'alcance'      => $alcance,
        'frescura'     => 'fresco',
        'ronda_origen' => $ronda,
        'creador_id'   => $pid,
        'precio_base'  => (int) ($res['precio_base'] ?? 0),
        'estado'       => 'activo',
    ));
    ope7_rumor_operar($rumor_id, 'propagacion', $pid, 0,
        'Propaganda sembrada: rumor falso con fiabilidad publicada ' . $fiabilidad . '.',
        array('veracidad' => 'falso'));
    return 'Rumor falso sembrado: «' . $contenido . '» (' . $alcance . ', fiabilidad publicada ' . $fiabilidad
        . '). La veracidad interna es **falsa** (solo-staff): solo aflora vía contraste a Sólido (14.4). Un falso de gran alcance puede generar Wanted injusto — eso es trama (14.8).';
}

/** Efecto 33 · Ataque a una red (14.5, ia + firma). */
function ope7_efecto_ataque_red($tr, $pid, $res, $ids)
{
    global $db;
    $red_id = (int) ($ids['red_id'] ?? 0);
    if ($pid < 1 || $red_id < 1 || !ope7_tabla_existe('red_espionaje') || !ope7_tabla_existe('espias')) {
        return 'Ataque a una red: faltan datos (red objetivo).';
    }
    $q = $db->simple_select('ope_red_espionaje', '*', "id = {$red_id}", array('limit' => 1));
    $red = $db->fetch_array($q);
    if (!$red) {
        return 'Ataque a una red BLOQUEADO: la red no existe.';
    }
    if ((int) $red['dueno_id'] === (int) $pid) {
        return 'Ataque a una red BLOQUEADO: no puedes atacar tu propia red.';
    }
    $metodo = trim((string) ($res['metodo'] ?? ($ids['metodo'] ?? '')));
    if ($metodo === '') {
        return 'Ataque a una red BLOQUEADO: falta el veredicto (método declarado y qué descubre/estropea, 14.5 — sin dados).';
    }
    // Veredicto del staff: qué espías quedan descubiertos/retirados y si la red
    // se desactiva. Nunca azar.
    $descubiertos = (array) ($res['espias_descubiertos'] ?? array());
    $desactiva = !empty($res['desactiva_red']);
    $n = 0;
    foreach ($descubiertos as $eid) {
        $eid = (int) $eid;
        if ($eid < 1) {
            continue;
        }
        $eq = $db->simple_select('ope_espias', 'id', "id = {$eid} AND red_id = {$red_id}", array('limit' => 1));
        if ($db->num_rows($eq)) {
            $db->update_query('ope_espias', array('estado' => 'descubierto'), "id = {$eid}");
            $n++;
        }
    }
    if ($desactiva) {
        $db->update_query('ope_red_espionaje', array('estado' => 'inactiva'), "id = {$red_id}");
    }
    ope7_rumor_operar(0, 'propagacion', $pid, 0,
        'Ataque a la red #' . $red_id . ' de #' . (int) $red['dueno_id'] . ': ' . $metodo,
        array('espias_descubiertos' => count($descubiertos), 'desactiva_red' => $desactiva ? 1 : 0));
    return 'Ataque resuelto (veredicto, sin dados): ' . $n . ' espía(s) descubierto(s) de la red #' . $red_id
        . ($desactiva ? ' y la red queda inactiva.' : '.') . ' Un espía descubierto es trama: delación, contrainformación, chantaje (14.5).';
}

/** Cron de bajo mundo: caducidad de paraderos (3 rondas, 14.6) + redes sin
 * mantenimiento se desactivan (14.5). Idempotente por ronda. */
function ope7_bajomundo_cron()
{
    global $db;
    $n = 0;
    if (!ope7_tabla_existe('carteles_recompensa') || !function_exists('ope7_ronda_activa')) {
        return 0;
    }
    $ra = ope7_ronda_activa();
    $ronda = (int) ($ra['numero'] ?? 0);
    if ($ronda < 1) {
        return 0;
    }
    // Carteles vigentes con paradero caducado (ronda_emision + 3 < ronda actual).
    $q = $db->simple_select('ope_carteles_recompensa', 'id', "estado = 'vigente' AND ronda_caducidad_paradero > 0 AND ronda_caducidad_paradero < {$ronda}");
    while ($c = $db->fetch_array($q)) {
        $db->update_query('ope_carteles_recompensa', array('estado' => 'frio'), "id = " . (int) $c['id']);
        $n++;
    }
    return $n;
}

// ─────────────────────────────────────────────────────────────
// Panel «Bajo Mundo» (Anexo A.3, 14.8)
// ─────────────────────────────────────────────────────────────

/** Panel «Bajo Mundo» (A.3 · 5.13): rumores por isla, redes, carteles y operaciones. */
function ope7_bajomundo_panel_html()
{
    global $db, $mybb;
    $h = array();
    $h[] = '<div class="shead"><h1>Bajo Mundo</h1><span class="sub">A.3 · 5.13 — rumores, redes, carteles y caza</span></div>';
    $h[] = '<p class="zs-intro">La cara informativa del Mundo Vivo (14.1): la veracidad de cada rumor se fija al nacer y nunca se reescribe por presión (14.3). '
        . 'Tú fijas la fiabilidad publicada y la caducidad de los paraderos (3 rondas, 14.6); la IA propone, tú firmas — nunca hay tirada (14.5).</p>';

    // ── Rumores activos por isla ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Rumores por isla</span><span class="c">14.2/14.3 — ficha de 5 campos</span></div><div class="plate-b">';
    if (ope7_tabla_existe('rumores') && ope7_tabla_existe('islas')) {
        $q = $db->query('SELECT r.*, i.nombre AS isla_nombre FROM ' . ope7_tabla_full('rumores') . ' r '
            . 'LEFT JOIN ' . ope7_tabla_full('islas') . ' i ON i.id = r.isla_id '
            . "WHERE r.estado IN ('activo','contrastado') ORDER BY r.id DESC LIMIT 30");
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin rumores activos. `skill-mundo-vivo` los genera por ronda; los jugadores pueden sembrar falsos (32) y los brokers pedirlos a su red (25).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>#</th><th>Rumor</th><th>Isla</th><th>Fiabilidad</th><th>Veracidad</th><th>Alcance</th><th>Frescura</th><th>Precio</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . (int) $r['id'] . '</td>'
                    . '<td><b>' . htmlspecialchars_uni((string) $r['contenido']) . '</b></td>'
                    . '<td>' . htmlspecialchars_uni((string) ($r['isla_nombre'] ?? '—')) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['fiabilidad']) . '</td>'
                    . '<td><b class="zs-ok">' . htmlspecialchars_uni((string) $r['veracidad']) . '</b> <span class="zs-mut">(solo-staff)</span></td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['alcance']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['frescura']) . '</td>'
                    . '<td>' . number_format((int) $r['precio_base'], 0, ',', '.') . ' ฿</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tablas de rumores no migradas.</p>';
    }
    $h[] = '</div></div>';

    // ── Redes y espías ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Redes y espías</span><span class="c">14.2.3 — capacidad, no suerte</span></div><div class="plate-b">';
    if (ope7_tabla_existe('red_espionaje') && ope7_tabla_existe('espias') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT r.*, p.nombre AS dueno FROM ' . ope7_tabla_full('red_espionaje') . ' r '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = r.dueno_id ORDER BY r.id DESC');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin redes montadas todavía (trámite 29).</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $h[] = '<div class="zs-row"><div><b>' . htmlspecialchars_uni((string) $r['nombre']) . '</b> <span class="zs-mut">· ' . htmlspecialchars_uni((string) $r['dueno']) . ' · ' . htmlspecialchars_uni((string) $r['estado']) . '</span>';
                $espias = ope7_red_espias((int) $r['id']);
                if ($espias) {
                    $h[] = '<div class="zs-mut">';
                    foreach ($espias as $e) {
                        $h[] = htmlspecialchars_uni((string) $e['tipo']) . ' (mant. ' . number_format((int) $e['mantenimiento'], 0, ',', '.') . ' ฿/ronda) · ';
                    }
                    $h[] = '</div>';
                }
                $h[] = '</div><div><span class="zs-mut">' . count($espias) . '/4 espías</span></div></div>';
            }
        }
    } else {
        $h[] = '<p class="pj-empty">Tablas de redes no migradas.</p>';
    }
    $h[] = '</div></div>';

    // ── Carteles de recompensa ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Carteles</span><span class="c">14.6 — vigentes, cobros y caducidad</span></div><div class="plate-b">';
    if (ope7_tabla_existe('carteles_recompensa') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT c.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('carteles_recompensa') . ' c '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = c.personaje_id ORDER BY c.id DESC LIMIT 25');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin carteles. Se emiten desde aquí (trámite 30): cifra, paradero y caducidad a 3 rondas.</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>#</th><th>Buscado</th><th>Cifra</th><th>Paradero</th><th>Caduca paradero</th><th>Estado</th></tr></thead><tbody>';
            while ($c = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . (int) $c['id'] . '</td>'
                    . '<td><b>' . htmlspecialchars_uni((string) $c['pj_nombre']) . '</b></td>'
                    . '<td>' . number_format((int) $c['cifra'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . htmlspecialchars_uni((string) $c['paradero_publicado']) . ' (' . htmlspecialchars_uni((string) $c['fiabilidad_paradero']) . ')</td>'
                    . '<td>' . ((int) $c['ronda_caducidad_paradero'] > 0 ? 'ronda ' . (int) $c['ronda_caducidad_paradero'] : '—') . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $c['estado']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de carteles no migrada.</p>';
    }
    $h[] = '</div></div>';

    // ── Histórico de operaciones ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Operaciones</span><span class="c">rumor_operaciones — auditable</span></div><div class="plate-b">';
    if (ope7_tabla_existe('rumor_operaciones')) {
        $q = $db->query('SELECT o.*, r.contenido AS rumor FROM ' . ope7_tabla_full('rumor_operaciones') . ' o '
            . 'LEFT JOIN ' . ope7_tabla_full('rumores') . ' r ON r.id = o.rumor_id ORDER BY o.fecha DESC LIMIT 25');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin operaciones todavía (compra 26, venta 28, contraste 27, propaganda 32, ataques 33).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Tipo</th><th>Rumor</th><th>Quién</th><th>Cobro</th><th>Motivo</th></tr></thead><tbody>';
            while ($o = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $o['fecha']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $o['tipo']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) ($o['rumor'] ?? '#' . (int) $o['rumor_id'])) . '</td>'
                    . '<td>#' . (int) $o['solicitante_id'] . '</td>'
                    . '<td>' . number_format((int) $o['cobro'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . htmlspecialchars_uni((string) $o['motivo']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    }
    $h[] = '</div></div>';

    return implode("\n", $h);
}
