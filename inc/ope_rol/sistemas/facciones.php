<?php
/**
 * One Piece: 7 Seas · Facciones (F4.3, 5.12/13)
 * ---------------------------------------------
 * Trámites 20–24: ascenso con termómetro y cupos (20), concesión de
 * subfacción élite (21, solo-staff), cambio de facción (22, hito),
 * deserción (23, hito) e infiltración (24, solo-staff).
 *
 * Reglas que aplica el motor (los DUROS, 13.4): rango inmediato siguiente
 * (sin saltos), cupo del rango (espera de cupo si está lleno), reputación
 * de facción mínima (`requisitos.rep_min`) cuando el rango la define, y
 * anti-abuso 13.7 (un personaje por facción por jugador). El termómetro
 * cualitativo (tipo de acciones de la facción) lo propone la skill en la
 * propuesta y lo firma el staff — el motor no decide solo (5.21).
 *
 * El histórico inmutable vive en `cambios_faccion` (alta/promoción/
 * deserción/infiltración/baja/concesión/revocación) con motivo y firma.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Facción por id. */
function ope7_faccion_por_id($faccion_id)
{
    global $db;
    $faccion_id = (int) $faccion_id;
    if ($faccion_id < 1 || !ope7_tabla_existe('facciones')) {
        return null;
    }
    $q = $db->simple_select('ope_facciones', '*', "id = {$faccion_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Facción por nombre. */
function ope7_faccion_por_nombre($nombre)
{
    global $db;
    if ($nombre === '' || !ope7_tabla_existe('facciones')) {
        return null;
    }
    $q = $db->simple_select('ope_facciones', '*', "nombre = '" . $db->escape_string((string) $nombre) . "'", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Rango de facción por id. */
function ope7_rango_por_id($rango_id)
{
    global $db;
    $rango_id = (int) $rango_id;
    if ($rango_id < 1 || !ope7_tabla_existe('rangos_faccion')) {
        return null;
    }
    $q = $db->simple_select('ope_rangos_faccion', '*', "id = {$rango_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Escalera de rangos de una facción, por orden. */
function ope7_faccion_rangos($faccion_id)
{
    global $db;
    $faccion_id = (int) $faccion_id;
    $out = array();
    if ($faccion_id < 1 || !ope7_tabla_existe('rangos_faccion')) {
        return $out;
    }
    $q = $db->simple_select('ope_rangos_faccion', '*', "faccion_id = {$faccion_id}", array('order_by' => 'orden', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Rango inicial de una facción (fila `rangos_faccion`). */
function ope7_faccion_rango_inicial($faccion_id)
{
    global $db;
    $faccion_id = (int) $faccion_id;
    $f = ope7_faccion_por_id($faccion_id);
    if (!$f || !ope7_tabla_existe('rangos_faccion')) {
        return null;
    }
    $q = $db->simple_select('ope_rangos_faccion', '*', "faccion_id = {$faccion_id}", array('order_by' => 'orden', 'order_dir' => 'ASC', 'limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Filiación actual del personaje (faccion_personaje + facción + rango). */
function ope7_faccion_pj($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('faccion_personaje')) {
        return null;
    }
    $q = $db->simple_select('ope_faccion_personaje', '*', "personaje_id = {$pid} AND activo = 1", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r) {
        return null;
    }
    $r['faccion'] = ope7_faccion_por_id((int) $r['faccion_id']);
    $r['rango'] = (int) ($r['rango_id'] ?? 0) > 0 ? ope7_rango_por_id((int) $r['rango_id']) : null;
    return $r;
}

/** Personajes activos en un rango (ocupación del cupo). */
function ope7_faccion_ocupados($rango_id)
{
    global $db;
    $rango_id = (int) $rango_id;
    if ($rango_id < 1 || !ope7_tabla_existe('faccion_personaje')) {
        return 0;
    }
    $q = $db->simple_select('ope_faccion_personaje', 'COUNT(*) AS n', "rango_id = {$rango_id} AND activo = 1");
    return (int) $db->fetch_field($q, 'n');
}

/**
 * Plazas libres de un rango: −1 = ilimitado · 0 = lleno (espera de cupo) ·
 * >0 = plazas. El cupo se lee de `rangos_faccion.cupo` (13.3/13.4).
 */
function ope7_faccion_cupo_libre($rango)
{
    if (!$rango) {
        return -1;
    }
    $cupo = (int) ($rango['cupo'] ?? 0);
    if ($cupo < 1) {
        return -1;
    }
    $libres = $cupo - ope7_faccion_ocupados((int) $rango['id']);
    // Nunca negativo: lleno o sobrecupo cuentan como 0 plazas (13.4).
    return $libres >= 0 ? $libres : 0;
}

/** ¿Otro personaje del mismo jugador ya está en la facción? (13.7). */
function ope7_faccion_pj_duplicado($pid, $faccion_id)
{
    global $db;
    $pid = (int) $pid;
    $faccion_id = (int) $faccion_id;
    if ($pid < 1 || $faccion_id < 1 || !ope7_tabla_existe('faccion_personaje') || !ope7_tabla_existe('personajes')) {
        return false;
    }
    $q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('faccion_personaje') . ' fp '
        . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = fp.personaje_id "
        . "WHERE fp.activo = 1 AND fp.faccion_id = {$faccion_id} AND p.uid > 0 AND p.id <> {$pid} "
        . 'AND p.uid = (SELECT uid FROM ' . ope7_tabla_full('personajes') . " WHERE id = {$pid})");
    return (int) $db->fetch_field($q, 'n') > 0;
}

/** Espeja la filiación en las columnas viejas de `personajes` (ficha heredada). */
function ope7_faccion_espejar($pid, $faccion_id, $rango_id)
{
    global $db;
    if (!ope7_tabla_existe('personajes')) {
        return;
    }
    $db->update_query('ope_personajes', array(
        'faccion_id' => (int) $faccion_id > 0 ? (int) $faccion_id : 0,
        'rango_id'   => (int) $rango_id > 0 ? (int) $rango_id : 0,
    ), 'id = ' . (int) $pid);
}

/** Registra un cambio en el histórico inmutable `cambios_faccion` (13.8). */
function ope7_faccion_registrar($pid, $tipo, $desde_faccion_id, $hasta_faccion_id, $motivo, $staff_uid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('cambios_faccion')) {
        return;
    }
    $tipos = array('alta', 'promocion', 'desercion', 'infiltracion', 'baja', 'concesion', 'revocacion');
    if (!in_array($tipo, $tipos, true)) {
        $tipo = 'baja';
    }
    $db->insert_query('ope_cambios_faccion', array(
        'personaje_id'     => $pid,
        'tipo'             => $tipo,
        'desde_faccion_id' => (int) $desde_faccion_id > 0 ? (int) $desde_faccion_id : 0,
        'hasta_faccion_id' => (int) $hasta_faccion_id > 0 ? (int) $hasta_faccion_id : 0,
        'motivo'           => $db->escape_string((string) $motivo),
        'firmado_por'      => (int) $staff_uid,
        'fecha'            => TIME_NOW,
    ));
}

/**
 * Asigna la filiación (alta/cambio/deserción): upsert en `faccion_personaje`
 * (UNIQUE personaje_id) + espejo en `personajes` + histórico. No toca la
 * infiltración (la gestiona ope7_efecto_infiltracion).
 */
function ope7_faccion_asignar($pid, $faccion_id, $rango_id, $tipo, $motivo, $staff_uid, $extra = array())
{
    global $db;
    $pid = (int) $pid;
    $faccion_id = (int) $faccion_id;
    $rango_id = (int) $rango_id;
    $fp = ope7_faccion_pj($pid);
    $desde = $fp ? (int) $fp['faccion_id'] : 0;
    if (!ope7_tabla_existe('faccion_personaje')) {
        return false;
    }
    $datos = array(
        'faccion_id'    => $faccion_id,
        'rango_id'      => $rango_id > 0 ? $rango_id : 0,
        'activo'        => 1,
    );
    // La infiltración en curso no se pisa con un alta normal (capa oculta).
    if (isset($extra['mantener_infiltracion']) && $extra['mantener_infiltracion']) {
        unset($datos['infiltracion_faccion_id'], $datos['infiltracion_rango_id'], $datos['infiltracion_activa']);
    }
    if ($fp) {
        $db->update_query('ope_faccion_personaje', $datos, "personaje_id = {$pid}");
    } else {
        $datos['personaje_id'] = $pid;
        $datos['rep_faccion'] = 0;
        $datos['wanted_base'] = 0;
        $db->insert_query('ope_faccion_personaje', $datos);
    }
    ope7_faccion_espejar($pid, $faccion_id, $rango_id);
    ope7_faccion_registrar($pid, $tipo, $desde, $faccion_id, $motivo, $staff_uid);
    return true;
}

/**
 * Efecto 20 · Ascenso de facción (5.12/13.4): la skill propone el termómetro,
 * el motor valida los DUROS (rango inmediato siguiente, cupo, rep_min) y el
 * staff firma. Aplica el rango y registra la promoción.
 */
function ope7_efecto_ascenso_faccion($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $fp = ope7_faccion_pj($pid);
    if (!$fp || !$fp['faccion']) {
        return 'Ascenso de facción: el personaje no tiene facción activa (13.1).';
    }
    $faccion = $fp['faccion'];
    $rangos = ope7_faccion_rangos((int) $faccion['id']);
    $rango_actual = (int) ($fp['rango_id'] ?? 0);
    $idx = null;
    foreach ($rangos as $i => $r) {
        if ((int) $r['id'] === $rango_actual) {
            $idx = $i;
            break;
        }
    }
    if ($idx === null) {
        return 'Ascenso BLOQUEADO: el personaje no tiene rango en la escalera de su facción.';
    }
    if (!isset($rangos[$idx + 1])) {
        return 'Ascenso BLOQUEADO: el personaje ya está en la cúspide de su facción.';
    }
    $rango_nuevo = $rangos[$idx + 1];
    // Sin saltos: el objetivo solo puede ser el inmediato siguiente (13.4).
    $objetivo = (int) ($res['rango_id'] ?? 0);
    if ($objetivo > 0 && $objetivo !== (int) $rango_nuevo['id']) {
        return 'Ascenso BLOQUEADO: solo se asciende al rango inmediato siguiente (sin saltos, 13.4).';
    }
    // Requisito duro: reputación de facción mínima si el rango la define (13.4).
    $req = json_decode((string) ($rango_nuevo['requisitos'] ?? '{}'), true);
    $rep_min = (int) ($req['rep_min'] ?? 0);
    if ($rep_min > 0 && (int) $fp['rep_faccion'] < $rep_min) {
        return 'Ascenso BLOQUEADO: reputación de facción insuficiente (requiere ' . $rep_min . ', tiene ' . (int) $fp['rep_faccion'] . ') — requisito duro (13.4).';
    }
    // Cupo (13.4): rango con cupo lleno → espera de cupo.
    $libres = ope7_faccion_cupo_libre($rango_nuevo);
    if ($libres === 0) {
        return 'Ascenso BLOQUEADO: el rango ' . (string) $rango_nuevo['nombre'] . ' está en espera de cupo (13.4) — resuelve por mérito, antigüedad o revocación.';
    }
    $staff_uid = (int) ($res['staff_uid'] ?? $tr['firma_staff'] ?? 0);
    $motivo = (string) ($res['motivo'] ?? 'Ascenso firmado por el staff.');
    $termometro = trim((string) ($res['termometro'] ?? ''));
    ope7_faccion_asignar($pid, (int) $faccion['id'], (int) $rango_nuevo['id'], 'promocion', $motivo, $staff_uid);
    return 'Ascenso aplicado: ' . (string) $faccion['nombre'] . ' → ' . (string) $rango_nuevo['nombre']
        . ' (rep_faccion ' . (int) $fp['rep_faccion'] . ($req['rep_min'] ? '/' . (int) $req['rep_min'] : '') . ' · cupo ' . ($libres > 0 ? $libres . ' libre(s)' : 'ilimitado') . ')'
        . ($termometro !== '' ? ' · termómetro: ' . $termometro : '')
        . ' · registrado en cambios_faccion.';
}

/**
 * Efecto 21 · Concesión/revocación de subfacción élite (13.2/13.8, staff).
 * Solo Shichibukai puede recaer en un jugador (cupo 7); Gorosei es NPC.
 * La revocación por romper las condiciones crece el Wanted (5.13).
 */
function ope7_efecto_concesion_elite($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $nombre = trim((string) ($res['nombre'] ?? 'Shichibukai'));
    if (!in_array($nombre, array('Shichibukai'), true)) {
        return 'Concesión BLOQUEADA: solo Shichibukai puede recaer en un jugador; Gorosei es contenido NPC del mundo (13.2).';
    }
    if (!ope7_tabla_existe('subfaccion_elite')) {
        return 'Concesión: tabla de subfacciones no migrada (pendiente).';
    }
    $staff_uid = (int) ($res['staff_uid'] ?? $tr['firma_staff'] ?? 0);
    $motivo = (string) ($res['motivo'] ?? '');
    // Revocación (13.2): romper las condiciones → se retira el título y crece el Wanted.
    if (!empty($res['revocar'])) {
        $db->update_query('ope_subfaccion_elite', array('activo' => 0), "nombre = 'Shichibukai' AND personaje_id = {$pid} AND activo = 1");
        $fp = ope7_faccion_pj($pid);
        if ($fp && ope7_tabla_existe('faccion_personaje')) {
            $wanted = (int) ($res['wanted_nuevo'] ?? 0);
            if ($wanted < 1) {
                $wanted = (int) $fp['wanted_base'] > 0 ? (int) round((int) $fp['wanted_base'] * 1.5) : 10000000;
            }
            $db->update_query('ope_faccion_personaje', array('wanted_base' => $wanted), "personaje_id = {$pid}");
        }
        ope7_faccion_registrar($pid, 'revocacion', 0, 0, $motivo !== '' ? $motivo : 'Revocación de Shichibukai.', $staff_uid);
        return 'Shichibukai revocado' . ($fp ? ' · el Wanted crece a ' . number_format((int) $wanted) . ' ฿ (5.13)' : '') . '.';
    }
    // Concesión: cupo 7 (13.2).
    $q = $db->simple_select('ope_subfaccion_elite', 'COUNT(*) AS n', "nombre = 'Shichibukai' AND activo = 1");
    if ((int) $db->fetch_field($q, 'n') >= 7) {
        return 'Concesión BLOQUEADA: cupo de Shichibukai lleno (7, 13.2).';
    }
    $q = $db->simple_select('ope_subfaccion_elite', 'id', "nombre = 'Shichibukai' AND personaje_id = {$pid} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Concesión BLOQUEADA: el personaje ya es Shichibukai.';
    }
    $db->insert_query('ope_subfaccion_elite', array(
        'nombre' => 'Shichibukai', 'personaje_id' => $pid,
        'concedida_por' => $staff_uid, 'fecha' => TIME_NOW, 'activo' => 1,
    ));
    ope7_faccion_registrar($pid, 'concesion', 0, 0, $motivo !== '' ? $motivo : 'Nombramiento de Shichibukai (13.2).', $staff_uid);
    return 'Shichibukai concedido al personaje ' . $pid . ' (cupo 7, 13.2): legitimidad y mandato — el mundo lo observa.';
}

/**
 * Efecto 22 · Cambio de facción (13.7, hito): transición narrada; el cambio
 * no arrastra rango — entra por el rango inicial (o el de equivalencia que
 * fije el staff). Anti-abuso: un personaje por facción por jugador.
 */
function ope7_efecto_cambio_faccion($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $fp = ope7_faccion_pj($pid);
    if (!$fp || !$fp['faccion']) {
        return 'Cambio de facción: el personaje no tiene facción activa.';
    }
    $nueva_id = (int) ($res['faccion_id'] ?? 0);
    $nueva = ope7_faccion_por_id($nueva_id);
    if (!$nueva) {
        return 'Cambio BLOQUEADO: la facción de destino no existe (catálogo 13.3).';
    }
    if ((int) $fp['faccion_id'] === $nueva_id) {
        return 'Cambio BLOQUEADO: el personaje ya pertenece a esa facción.';
    }
    if (ope7_faccion_pj_duplicado($pid, $nueva_id)) {
        return 'Cambio BLOQUEADO: otro personaje del mismo jugador ya está en ' . (string) $nueva['nombre'] . ' (límite 13.7).';
    }
    // Rango: el inicial, o el de equivalencia que fije el staff (13.7).
    $rango_id = (int) ($res['rango_id'] ?? 0);
    if ($rango_id < 1) {
        $ri = ope7_faccion_rango_inicial($nueva_id);
        $rango_id = (int) ($ri['id'] ?? 0);
    }
    if ($rango_id < 1) {
        return 'Cambio BLOQUEADO: la facción de destino no tiene rango inicial sembrado.';
    }
    $staff_uid = (int) ($res['staff_uid'] ?? $tr['firma_staff'] ?? 0);
    $motivo = (string) ($res['motivo'] ?? '');
    // Histórico: baja (de la anterior) + alta (en la nueva) — 13.8.
    ope7_faccion_registrar($pid, 'baja', (int) $fp['faccion_id'], $nueva_id, $motivo !== '' ? $motivo : 'Cambio de facción.', $staff_uid);
    ope7_faccion_asignar($pid, $nueva_id, $rango_id, 'alta', $motivo !== '' ? $motivo : 'Cambio de facción.', $staff_uid);
    $rango_nuevo = ope7_rango_por_id($rango_id);
    return 'Cambio aplicado: ' . (string) $fp['faccion']['nombre'] . ' → ' . (string) $nueva['nombre']
        . ' · entra como ' . (string) ($rango_nuevo['nombre'] ?? 'rango inicial') . ' (el cambio no arrastra rango, 13.7)'
        . (empty($res['rango_id']) ? '' : ' · equivalencia fijada por el staff') . '.';
}

/**
 * Efecto 23 · Deserción (13.7, hito): baja hostil → criminal (crece la
 * infamia y el Wanted, la institución persigue) · baja legal → retirada
 * sin criminalizar a una facción neutral (Aventurero libre).
 */
function ope7_efecto_desercion($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $fp = ope7_faccion_pj($pid);
    if (!$fp || !$fp['faccion']) {
        return 'Deserción: el personaje no tiene facción activa.';
    }
    $tipo = (string) ($res['tipo_baja'] ?? 'hostil');
    if (!in_array($tipo, array('hostil', 'legal'), true)) {
        return 'Deserción BLOQUEADA: tipo de baja no válido (hostil/legal).';
    }
    // Destino: Aventurero libre (puerta de entrada y facción neutral, 13.2).
    $av = ope7_faccion_por_nombre('Aventurero libre');
    if (!$av) {
        return 'Deserción BLOQUEADA: no existe la facción neutral «Aventurero libre» (13.2).';
    }
    $ri = ope7_faccion_rango_inicial((int) $av['id']);
    $rango_id = (int) ($ri['id'] ?? 0);
    $staff_uid = (int) ($res['staff_uid'] ?? $tr['firma_staff'] ?? 0);
    $motivo = (string) ($res['motivo'] ?? '');
    $nota = 'Baja ' . $tipo;
    if ($tipo === 'hostil' && ope7_tabla_existe('faccion_personaje')) {
        // Criminal: infamia +1 y Wanted crece (5.13) — config inicial ajustable.
        $wanted_nuevo = (int) ($res['wanted_nuevo'] ?? 0);
        if ($wanted_nuevo < 1) {
            $wanted_nuevo = (int) $fp['wanted_base'] > 0 ? (int) round((int) $fp['wanted_base'] * 1.5) : 5000000;
        }
        $db->update_query('ope_faccion_personaje', array(
            'wanted_base'      => $wanted_nuevo,
            'fama_infamia_expo'=> (int) ($fp['fama_infamia_expo'] ?? 0) + 1,
        ), "personaje_id = {$pid}");
        $nota .= ' → criminal: infamia +1 y Wanted ' . number_format($wanted_nuevo) . ' ฿ (5.13).';
    }
    ope7_faccion_asignar($pid, (int) $av['id'], $rango_id, 'desercion', $motivo !== '' ? $motivo : $nota, $staff_uid);
    return 'Deserción registrada: ' . (string) $fp['faccion']['nombre'] . ' → ' . (string) $av['nombre'] . ' · ' . $nota;
}

/**
 * Efecto 24 · Infiltración (13.7/13.8, staff): rango honorario en otra
 * facción. La lealtad VISIBLE pasa a la falsa; la REAL queda oculta
 * (infiltracion_* en `faccion_personaje`, solo-staff). Al terminar
 * (revocar), se restaura la lealtad real.
 */
function ope7_efecto_infiltracion($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $fp = ope7_faccion_pj($pid);
    if (!$fp) {
        return 'Infiltración: el personaje no tiene facción visible.';
    }
    if (!ope7_tabla_existe('faccion_personaje')) {
        return 'Infiltración: tabla no migrada (pendiente).';
    }
    $staff_uid = (int) ($res['staff_uid'] ?? $tr['firma_staff'] ?? 0);
    $motivo = (string) ($res['motivo'] ?? '');
    if (!empty($res['revocar'])) {
        // Fin de la infiltración: se restaura la lealtad real (13.7).
        $real = (int) ($fp['infiltracion_faccion_id'] ?? 0);
        $real_rango = (int) ($fp['infiltracion_rango_id'] ?? 0);
        $db->update_query('ope_faccion_personaje', array(
            'faccion_id'            => $real > 0 ? $real : (int) $fp['faccion_id'],
            'rango_id'              => $real_rango > 0 ? $real_rango : 0,
            'infiltracion_faccion_id' => 0,
            'infiltracion_rango_id'   => 0,
            'infiltracion_activa'     => 0,
        ), "personaje_id = {$pid}");
        ope7_faccion_espejar($pid, $real > 0 ? $real : (int) $fp['faccion_id'], $real_rango);
        ope7_faccion_registrar($pid, 'infiltracion', (int) $fp['faccion_id'], $real > 0 ? $real : (int) $fp['faccion_id'], $motivo !== '' ? $motivo : 'Fin de infiltración.', $staff_uid);
        $real_f = ope7_faccion_por_id($real);
        return 'Infiltración terminada: se restaura la lealtad real (' . ($real_f ? (string) $real_f['nombre'] : 'facción ' . $real) . ').';
    }
    $falsa_id = (int) ($res['faccion_id'] ?? 0);
    $falsa = ope7_faccion_por_id($falsa_id);
    if (!$falsa) {
        return 'Infiltración BLOQUEADA: la facción de infiltración no existe.';
    }
    if ((int) $fp['faccion_id'] === $falsa_id) {
        return 'Infiltración BLOQUEADA: no puedes infiltrarte en tu propia facción.';
    }
    $rango_hon = (int) ($res['rango_id'] ?? 0);
    if ($rango_hon < 1) {
        return 'Infiltración BLOQUEADA: falta el rango honorario en la facción de infiltración (24).';
    }
    $real = (int) $fp['faccion_id'];
    $real_rango = (int) ($fp['rango_id'] ?? 0);
    $db->update_query('ope_faccion_personaje', array(
        'faccion_id'              => $falsa_id,
        'rango_id'                => $rango_hon,
        'infiltracion_faccion_id' => $real,
        'infiltracion_rango_id'   => $real_rango,
        'infiltracion_activa'     => 1,
    ), "personaje_id = {$pid}");
    ope7_faccion_espejar($pid, $falsa_id, $rango_hon);
    ope7_faccion_registrar($pid, 'infiltracion', $real, $falsa_id, $motivo !== '' ? $motivo : 'Infiltración autorizada (24).', $staff_uid);
    $real_f = ope7_faccion_por_id($real);
    return 'Infiltración autorizada: visible = ' . (string) $falsa['nombre'] . ' (rango honorario) · real = ' . ($real_f ? (string) $real_f['nombre'] : 'facción ' . $real) . ' (capa oculta solo-staff, 13.7).';
}

/**
 * Panel staff «Facciones» (Anexo A.3, 13.8): tablero de rangos y cupos por
 * facción, ascensos en cola, subfacciones de élite e histórico de cambios.
 * Cero <style> y cero estilos inline estáticos.
 */
function ope7_facciones_panel_html()
{
    global $db;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };

    $html = '<div class="shead"><h1>Facciones</h1><span class="code">A.3 · 5.12</span><span class="rule"></span></div>';

    // ── Tablero de rangos y cupos por facción (13.8) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Rangos y cupos por facción</span><span class="c">cúspide con cupo (13.3)</span></div><div class="plate-b">';
    $facciones = array();
    if (ope7_tabla_existe('facciones')) {
        $q = $db->simple_select('ope_facciones', '*', '1=1', array('order_by' => 'id'));
        while ($r = $db->fetch_array($q)) {
            $facciones[] = $r;
        }
    }
    if (!$facciones) {
        $html .= '<p class="fc-empty">Catálogo de facciones vacío (seed F4.1).</p>';
    } else {
        foreach ($facciones as $f) {
            $rangos = ope7_faccion_rangos((int) $f['id']);
            $html .= '<div class="fc-fac"><div class="fc-fac-h"><b>' . $e((string) $f['nombre']) . '</b>'
                . ' <span class="fc-dim">' . $e((string) $f['familia']) . ($f['tiene_sueldo'] ? ' · sueldo' : '') . '</span></div><div class="fc-rangos">';
            foreach ($rangos as $r) {
                $cupo = (int) ($r['cupo'] ?? 0);
                $ocupados = $cupo > 0 ? ope7_faccion_ocupados((int) $r['id']) : null;
                $req = json_decode((string) ($r['requisitos'] ?? '{}'), true);
                $rep = (int) ($req['rep_min'] ?? 0);
                $html .= '<div class="fc-rango' . ($r['es_cuspide'] ? ' fc-cuspide' : '') . '">'
                    . '<span class="fc-rango-n">' . $e((string) $r['nombre']) . '</span>'
                    . ($cupo > 0 ? ' <span class="fc-dim">cupo ' . $ocupados . '/' . $cupo . ($ocupados >= $cupo ? ' <span class="fc-lleno">lleno</span>' : '') . '</span>' : ' <span class="fc-dim">sin cupo</span>')
                    . ($rep > 0 ? ' <span class="fc-dim">rep ' . $rep . '+</span>' : '')
                    . '</div>';
            }
            $html .= '</div></div>';
        }
    }
    $html .= '</div></div>';

    // ── Ascensos en cola (bandeja de ascensos, 13.4) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Ascensos en cola</span><span class="c">trámite 20 · la skill propone, tú firmas</span></div><div class="plate-b">';
    $cola = array();
    if (ope7_tabla_existe('tramites')) {
        $q = $db->query('SELECT tr.id, tr.personaje_id, tr.estado, tr.fecha_creacion, p.nombre AS pj_nombre '
            . 'FROM ' . ope7_tabla_full('tramites') . ' tr '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tr.personaje_id '
            . "WHERE tr.numero = 20 AND tr.estado IN ('pendiente','prompt_listo','analizado','en_revision') ORDER BY tr.id DESC LIMIT 20");
        while ($r = $db->fetch_array($q)) {
            $cola[] = $r;
        }
    }
    if (!$cola) {
        $html .= '<p class="fc-empty">Sin ascensos en cola.</p>';
    } else {
        foreach ($cola as $c) {
            $fp = ope7_faccion_pj((int) $c['personaje_id']);
            $html .= '<div class="fc-mov"><div class="fc-mov-h"><b>' . $e((string) $c['pj_nombre']) . '</b> · '
                . $e((string) ($fp['faccion']['nombre'] ?? 'sin facción')) . ' → rango siguiente'
                . ' · <span class="fc-dim">#' . (int) $c['id'] . ' · ' . $e((string) $c['estado']) . '</span></div></div>';
        }
    }
    $html .= '</div></div>';

    // ── Subfacciones de élite (13.2) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Subfacciones de élite</span><span class="c">Shichibukai cupo 7 · Gorosei solo NPC</span></div><div class="plate-b">';
    $elite = array();
    if (ope7_tabla_existe('subfaccion_elite') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT se.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('subfaccion_elite') . ' se '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = se.personaje_id '
            . "WHERE se.activo = 1 ORDER BY se.id DESC LIMIT 20");
        while ($r = $db->fetch_array($q)) {
            $elite[] = $r;
        }
    }
    $html .= '<p class="fc-empty">Shichibukai activos: ' . count($elite) . '/7.</p>';
    foreach ($elite as $en) {
        $html .= '<div class="fc-mov"><div class="fc-mov-h"><b>' . $e((string) $en['nombre']) . '</b> → ' . $e((string) $en['pj_nombre'])
            . ' <span class="fc-dim">concedido ' . date('d/m/Y', (int) $en['fecha']) . '</span></div></div>';
    }
    $html .= '</div></div>';

    // ── Sueldos y nóminas (A.3: «sueldos y nóminas») ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Sueldos y nóminas</span><span class="c">por ronda · pendiente/pagado</span></div><div class="plate-b">';
    $nom = array();
    if (ope7_tabla_existe('sueldos') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT s.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('sueldos') . ' s '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = s.personaje_id '
            . 'ORDER BY s.ronda DESC, p.nombre LIMIT 20');
        while ($r = $db->fetch_array($q)) {
            $nom[] = $r;
        }
    }
    if (!$nom) {
        $html .= '<p class="fc-empty">Sin nóminas todavía (el cron de sueldos por ronda genera las filas pendientes).</p>';
    } else {
        $html .= '<table class="zs-tab"><thead><tr><th>Ronda</th><th>Personaje</th><th>Posts</th><th>Monto</th><th>Estado</th></tr></thead><tbody>';
        foreach ($nom as $n) {
            $html .= '<tr><td>' . (int) $n['ronda'] . '</td>'
                . '<td>' . $e((string) $n['pj_nombre']) . '</td>'
                . '<td>' . (int) $n['posts_del_mes'] . '</td>'
                . '<td>' . number_format((int) $n['monto'], 0, ',', '.') . ' ฿</td>'
                . '<td>' . $e((string) $n['estado']) . '</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    $html .= '</div></div>';

    // ── Histórico de cambios (13.8, inmutable) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Histórico de cambios de facción</span><span class="c">cambios_faccion · inmutable</span></div><div class="plate-b">';
    $hist = array();
    if (ope7_tabla_existe('cambios_faccion') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT cf.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('cambios_faccion') . ' cf '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = cf.personaje_id '
            . 'ORDER BY cf.id DESC LIMIT 15');
        while ($r = $db->fetch_array($q)) {
            $hist[] = $r;
        }
    }
    if (!$hist) {
        $html .= '<p class="fc-empty">Sin cambios registrados todavía.</p>';
    } else {
        foreach ($hist as $h) {
            $desde = (int) ($h['desde_faccion_id'] ?? 0) > 0 ? (string) ($h['desde_faccion_id'] ?? '') : '—';
            $hasta = (int) ($h['hasta_faccion_id'] ?? 0) > 0 ? (string) ($h['hasta_faccion_id'] ?? '') : '—';
            $html .= '<div class="fc-mov"><div class="fc-mov-h"><b>' . $e((string) $h['pj_nombre']) . '</b> · '
                . '<span class="fc-tipo">' . $e((string) $h['tipo']) . '</span> ' . $desde . ' → ' . $hasta
                . ' <span class="fc-dim">' . date('d/m/Y', (int) $h['fecha']) . ' · staff #' . (int) $h['firmado_por'] . '</span></div>'
                . '<div class="fc-mov-meta">' . $e((string) $h['motivo']) . '</div></div>';
        }
    }
    $html .= '</div></div>';

    return $html;
}
