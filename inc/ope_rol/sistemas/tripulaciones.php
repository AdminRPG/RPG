<?php
/**
 * One Piece: 7 Seas · Tripulaciones (F5.3, 5.21-ter/cap. 22.9)
 * ------------------------------------------------------------
 * Trámites 63–67 (la entidad que formaliza el cofre común de 5.9, el límite
 * por plazas del barco de 5.17 y los presentes compartidos; sin bonos
 * numéricos — valor solo operativo):
 *  · 63 — Fundación (ia, capitán): mínimo 2 (capitán + 1), ficha
 *    (nombre/bandera/propósito), plazas del barco (solo PJs, 5.17), un PJ por
 *    usuario; abre el tema de fundación (presente, 5.6) y crea el cofre.
 *  · 64 — Ingreso (ligero/firma, capitán): verifica espacio del barco y un
 *    PJ por usuario; fecha de ingreso.
 *  · 65 — Baja/expulsión (ligero/firma, capitán): libera la plaza y reparte
 *    la parte del cofre con registro.
 *  · 66 — Cambio de capitán (staff + firma): cesión o motín con veredicto
 *    (5.10/5.14); mueve el cofre; suceso de ronda si cambia el nombre.
 *  · 67 — Disolución (staff + firma): reparte el cofre, devuelve objetos,
 *    barco al último capitán; cierra la entidad (automática <2 activos con
 *    aviso y plazo para reclutar, hook de ronda 5.14).
 * Sin dados: el motín se resuelve con veredicto justificado; la IA propone,
 * el staff firma, nada se decide solo.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Ficha de una tripulación (fila + cofre decodificado). */
function ope7_trip_get($trip_id)
{
    global $db;
    $trip_id = (int) $trip_id;
    if ($trip_id < 1 || !ope7_tabla_existe('tripulaciones')) {
        return null;
    }
    $q = $db->simple_select('ope_tripulaciones', '*', "id = {$trip_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r) {
        return null;
    }
    return $r;
}

/** Miembros de la tripulación (activos o todos) con nombre y uid. */
function ope7_trip_miembros($trip_id, $solo_activos = true)
{
    global $db;
    $trip_id = (int) $trip_id;
    if ($trip_id < 1 || !ope7_tabla_existe('tripulantes') || !ope7_tabla_existe('personajes')) {
        return array();
    }
    $where = "t.tripulacion_id = {$trip_id}";
    if ($solo_activos) {
        $where .= " AND t.estado = 'activo'";
    }
    $q = $db->query('SELECT t.*, p.nombre AS pj_nombre, p.uid AS pj_uid FROM ' . ope7_tabla_full('tripulantes') . ' t '
        . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = t.personaje_id WHERE {$where} ORDER BY t.rol, t.fecha_ingreso");
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Espacio del barco ocupado por los miembros activos (5.17). */
function ope7_trip_espacio_usado($trip_id)
{
    $n = 0;
    foreach (ope7_trip_miembros($trip_id, true) as $m) {
        $n += (int) ($m['espacio_ocupado'] ?? 1);
    }
    return $n;
}

/** Espacio máximo del barco de la tripulación (5.17: `barcos.espacio_max`). */
function ope7_trip_espacio_max($trip)
{
    global $db;
    $barco_id = (int) ($trip['barco_id'] ?? 0);
    if ($barco_id < 1 || !ope7_tabla_existe('barcos')) {
        return 0;
    }
    $q = $db->simple_select('ope_barcos', 'espacio_max', "id = {$barco_id}", array('limit' => 1));
    return (int) $db->fetch_field($q, 'espacio_max');
}

/** Espacio que ocupa un personaje a bordo según su raza (18.3). */
function ope7_trip_espacio_raza($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('personajes') || !function_exists('ope7_pj_get')) {
        return 1;
    }
    $f = ope7_pj_get($pid);
    if (!$f) {
        return 1;
    }
    if (function_exists('ope7_barco_espacio_raza')) {
        return (int) ope7_barco_espacio_raza($f);
    }
    return 1;
}

/** Cofre común (5.9): berries + log de movimientos (JSON). */
function ope7_trip_cofre_get($trip_id)
{
    global $db;
    $trip_id = (int) $trip_id;
    if ($trip_id < 1 || !ope7_tabla_existe('cofre_tripulacion')) {
        return array('berries' => 0, 'log' => array());
    }
    $q = $db->simple_select('ope_cofre_tripulacion', 'berries, log', "tripulacion_id = {$trip_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r) {
        return array('berries' => 0, 'log' => array());
    }
    $log = json_decode((string) ($r['log'] ?? '[]'), true);
    return array('berries' => (int) ($r['berries'] ?? 0), 'log' => is_array($log) ? $log : array());
}

/** Mueve berries del cofre (5.9: aportar/retirar con registro). $cantidad < 0 = retirada. */
function ope7_trip_cofre_mover($trip_id, $pid, $cantidad, $concepto)
{
    global $db;
    $trip_id = (int) $trip_id;
    $pid = (int) $pid;
    $cantidad = (int) $cantidad;
    if ($trip_id < 1 || $cantidad === 0 || !ope7_tabla_existe('cofre_tripulacion')) {
        return array('ok' => false, 'msg' => 'Movimiento de cofre inválido.');
    }
    $c = ope7_trip_cofre_get($trip_id);
    $nuevo = (int) $c['berries'] + $cantidad;
    if ($nuevo < 0) {
        return array('ok' => false, 'msg' => 'El cofre no tiene tantas berries (' . (int) $c['berries'] . ' ฿).');
    }
    $log = $c['log'];
    $log[] = array(
        'fecha'    => TIME_NOW,
        'personaje_id' => $pid,
        'cantidad' => $cantidad,
        'concepto' => (string) $concepto,
    );
    $q = $db->simple_select('ope_cofre_tripulacion', 'id', "tripulacion_id = {$trip_id}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $db->update_query('ope_cofre_tripulacion', array(
            'berries' => $nuevo,
            'log'     => json_encode($log, JSON_UNESCAPED_UNICODE),
        ), "tripulacion_id = {$trip_id}");
    } else {
        $db->insert_query('ope_cofre_tripulacion', array(
            'tripulacion_id' => $trip_id,
            'berries'        => $nuevo,
            'log'            => json_encode($log, JSON_UNESCAPED_UNICODE),
        ));
    }
    return array('ok' => true, 'msg' => ($cantidad > 0 ? '+' : '') . $cantidad . ' ฿ al cofre (saldo ' . $nuevo . ').', 'saldo' => $nuevo);
}

/** Histórico auditable de la tripulación. */
function ope7_trip_hist($trip_id, $evento, $motivo, $firmado_por = 0)
{
    global $db;
    $trip_id = (int) $trip_id;
    if ($trip_id < 1 || !ope7_tabla_existe('tripulacion_historico')) {
        return;
    }
    $db->insert_query('ope_tripulacion_historico', array(
        'tripulacion_id' => $trip_id,
        'evento'         => (string) $evento,
        'motivo'         => (string) $motivo,
        'firmado_por'    => (int) $firmado_por,
        'fecha'          => TIME_NOW,
    ));
}

/** Tripulación activa del personaje (0 si ninguna). */
function ope7_pj_tripulacion_activa($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('tripulantes') || !ope7_tabla_existe('tripulaciones')) {
        return 0;
    }
    $q = $db->query('SELECT t.tripulacion_id FROM ' . ope7_tabla_full('tripulantes') . ' t '
        . 'JOIN ' . ope7_tabla_full('tripulaciones') . " tr ON tr.id = t.tripulacion_id "
        . "WHERE t.personaje_id = {$pid} AND t.estado = 'activo' AND tr.estado = 'activa' LIMIT 1");
    return (int) $db->fetch_field($q, 'tripulacion_id');
}

/** ¿Algún personaje del mismo usuario ya está en la tripulación? (un PJ por usuario). */
function ope7_trip_uid_ya_esta($trip_id, $uid)
{
    global $db;
    $trip_id = (int) $trip_id;
    $uid = (int) $uid;
    if ($trip_id < 1 || $uid < 1 || !ope7_tabla_existe('tripulantes') || !ope7_tabla_existe('personajes')) {
        return false;
    }
    $q = $db->query('SELECT COUNT(*) AS c FROM ' . ope7_tabla_full('tripulantes') . ' t '
        . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = t.personaje_id "
        . "WHERE t.tripulacion_id = {$trip_id} AND t.estado = 'activo' AND p.uid = {$uid}");
    return (int) $db->fetch_field($q, 'c') > 0;
}

/**
 * Efecto 63 · Fundación de tripulación (5.21-ter/22.9): valida mínimo 2,
 * ficha (nombre/bandera/propósito), plazas del barco (5.17, solo PJs) y un
 * PJ por usuario; crea la entidad + el cofre común, vincula el barco y abre
 * el tema de fundación (presente, 5.6).
 */
function ope7_efecto_fundar_tripulacion($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('tripulaciones')) {
        return 'Fundación BLOQUEADA: sin personaje (necesitas un PJ aprobado).';
    }
    $nombre = trim((string) ($ids['nombre'] ?? $res['nombre'] ?? ''));
    if ($nombre === '') {
        return 'Fundación BLOQUEADA: la tripulación necesita nombre (ficha, 22.9).';
    }
    // Nombre único (uq_nombre).
    $q = $db->simple_select('ope_tripulaciones', 'id', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Fundación BLOQUEADA: ya existe una tripulación llamada «' . $nombre . '».';
    }
    // Mínimo 2 (capitán + 1 fundador, 22.9).
    $fundadores = array_values(array_filter(array_map('intval', (array) ($ids['fundadores'] ?? $res['fundadores'] ?? array()))));
    $fundadores = array_diff($fundadores, array($pid));
    if (count($fundadores) < 1) {
        return 'Fundación BLOQUEADA: se necesita mínimo 2 personas (capitán + 1) escenificando la fundación (22.9).';
    }
    $grupito = array_merge(array($pid), $fundadores);
    // Un PJ por usuario + nadie en otra tripulación activa.
    foreach ($grupito as $pj) {
        if (ope7_pj_tripulacion_activa($pj) > 0) {
            return 'Fundación BLOQUEADA: el personaje #' . $pj . ' ya está en una tripulación activa.';
        }
        $q = $db->simple_select('ope_personajes', 'uid', "id = {$pj}", array('limit' => 1));
        $uid_pj = (int) $db->fetch_field($q, 'uid');
        foreach ($grupito as $otro) {
            if ($otro === $pj) {
                continue;
            }
            $q2 = $db->simple_select('ope_personajes', 'uid', "id = {$otro}", array('limit' => 1));
            if ((int) $db->fetch_field($q2, 'uid') === $uid_pj && $uid_pj > 0) {
                return 'Fundación BLOQUEADA: un mismo usuario no aporta dos personajes a la tripulación (un PJ por usuario, 22.9).';
            }
        }
    }
    // Barco del capitán con plazas (5.17): solo PJs, espacio por raza.
    $barco_id = (int) ($ids['barco_id'] ?? $res['barco_id'] ?? 0);
    if ($barco_id < 1 || !ope7_tabla_existe('barcos')) {
        return 'Fundación BLOQUEADA: la tripulación necesita un barco con plazas (5.17).';
    }
    $q = $db->simple_select('ope_barcos', '*', "id = {$barco_id}", array('limit' => 1));
    $barco = $db->fetch_array($q);
    if (!$barco) {
        return 'Fundación BLOQUEADA: el barco no existe.';
    }
    if ((int) ($barco['dueno_id'] ?? 0) !== $pid) {
        return 'Fundación BLOQUEADA: el barco debe ser del capitán (5.17).';
    }
    if ((int) ($barco['tripulacion_id'] ?? 0) > 0) {
        return 'Fundación BLOQUEADA: el barco ya pertenece a otra tripulación.';
    }
    $espacio_max = (int) ($barco['espacio_max'] ?? 0);
    $espacio_necesario = 0;
    foreach ($grupito as $pj) {
        $espacio_necesario += ope7_trip_espacio_raza($pj);
    }
    if ($espacio_necesario > $espacio_max) {
        return 'Fundación BLOQUEADA: el barco tiene ' . $espacio_max . ' plaza(s) y la tripulación ocupa ' . $espacio_necesario . ' (espacio por raza, 5.17).';
    }
    // Un-presente (5.6): la fundación es el presente del capitán.
    if (ope7_pj_tiene_presente_abierto($pid)) {
        return 'Fundación BLOQUEADA: el capitán ya tiene un tema presente abierto (un-presente, 5.6) — la fundación es su presente.';
    }

    // ── Crea la entidad + cofre común (5.9) ──
    $trip_id = (int) $db->insert_query('ope_tripulaciones', array(
        'nombre'     => $db->escape_string($nombre),
        'bandera'    => $db->escape_string(trim((string) ($ids['bandera'] ?? $res['bandera'] ?? ''))),
        'proposito'  => $db->escape_string(trim((string) ($ids['proposito'] ?? $res['proposito'] ?? ''))),
        'capitan_id' => $pid,
        'barco_id'   => $barco_id,
        'cofre_id'   => 0,
        'estado'     => 'activa',
        'fundada_por'=> $pid,
        'fecha'      => TIME_NOW,
    ));
    $cofre_id = (int) $db->insert_query('ope_cofre_tripulacion', array(
        'tripulacion_id' => $trip_id,
        'berries'        => 0,
        'log'            => json_encode(array(), JSON_UNESCAPED_UNICODE),
    ));
    $db->update_query('ope_tripulaciones', array('cofre_id' => $cofre_id), "id = {$trip_id}");
    // Miembros (capitán + fundadores) con espacio por raza (5.17).
    $db->insert_query('ope_tripulantes', array(
        'tripulacion_id' => $trip_id,
        'personaje_id'   => $pid,
        'rol'            => 'capitan',
        'espacio_ocupado'=> ope7_trip_espacio_raza($pid),
        'fecha_ingreso'  => TIME_NOW,
        'fecha_salida'   => 0,
        'estado'         => 'activo',
    ));
    foreach ($fundadores as $pj) {
        $db->insert_query('ope_tripulantes', array(
            'tripulacion_id' => $trip_id,
            'personaje_id'   => $pj,
            'rol'            => 'miembro',
            'espacio_ocupado'=> ope7_trip_espacio_raza($pj),
            'fecha_ingreso'  => TIME_NOW,
            'fecha_salida'   => 0,
            'estado'         => 'activo',
        ));
    }
    // El barco es de la banda, no de uno (22.9).
    $db->update_query('ope_barcos', array('tripulacion_id' => $trip_id), "id = {$barco_id}");
    // Tema de fundación (presente, 5.6).
    ope7_efecto_apertura_tema($tr, $pid, array('tipo' => 'presente', 'tema_tipo' => 'trama', 'zona' => 'Fundación: ' . $nombre), array('tipo' => 'presente', 'tema_tipo' => 'trama', 'zona' => 'Fundación: ' . $nombre));
    $q = $db->query('SELECT tp.tema_id FROM ' . ope7_tabla_full('temas_participantes') . ' tp '
        . 'WHERE tp.personaje_id = ' . $pid . ' ORDER BY tp.id DESC LIMIT 1');
    $tema_id = (int) $db->fetch_field($q, 'tema_id');
    $db->update_query('ope_tripulaciones', array('fundacion_tema_id' => $tema_id > 0 ? $tema_id : 0), "id = {$trip_id}");
    ope7_trip_hist($trip_id, 'fundacion', 'Fundación por ' . count($grupito) . ' personaje(s) con el barco «' . (string) $barco['nombre'] . '» (tema #' . $tema_id . ').', (int) ($tr['_staff_uid'] ?? 0));

    return 'Tripulación «' . $nombre . '» fundada (#' . $trip_id . ') con ' . count($grupito) . ' miembros y cofre común (5.9). '
        . 'Barco «' . (string) $barco['nombre'] . '» vinculado (' . $espacio_necesario . '/' . $espacio_max . ' plazas) · tema de fundación #' . $tema_id . ' abierto (presente, 5.6). '
        . 'El grupo postea la fundación y pide el ingreso de más miembros con el trámite 64.';
}

/**
 * Efecto 64 · Ingreso en tripulación (5.21-ter, ligero/firma): el capitán
 * inicia; valida espacio del barco (solo PJs, 5.17) y un PJ por usuario;
 * registra la fecha de ingreso.
 */
function ope7_efecto_ingreso_tripulacion($tr, $pid, $res, $ids)
{
    global $db;
    $capitan = (int) $pid;
    $trip_id = (int) ($ids['tripulacion_id'] ?? $res['tripulacion_id'] ?? 0);
    $ingresado = (int) ($ids['ingresado_id'] ?? $res['ingresado_id'] ?? 0);
    if ($capitan < 1 || $trip_id < 1 || $ingresado < 1 || !ope7_tabla_existe('tripulaciones')) {
        return 'Ingreso BLOQUEADO: faltan datos (tripulación o personaje).';
    }
    $trip = ope7_trip_get($trip_id);
    if (!$trip || (string) $trip['estado'] !== 'activa') {
        return 'Ingreso BLOQUEADO: la tripulación no está activa.';
    }
    // Solo el capitán ingresa (22.3: quien = capitán).
    if ((int) $trip['capitan_id'] !== $capitan) {
        return 'Ingreso BLOQUEADO: solo el capitán ingresa miembros (22.3).';
    }
    if (ope7_pj_tripulacion_activa($ingresado) > 0) {
        return 'Ingreso BLOQUEADO: ese personaje ya está en una tripulación activa.';
    }
    $q = $db->simple_select('ope_personajes', 'uid, estado', "id = {$ingresado}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f || (string) ($f['estado'] ?? '') !== 'aprobado') {
        return 'Ingreso BLOQUEADO: el personaje debe estar aprobado (ficha válida).';
    }
    if (ope7_trip_uid_ya_esta($trip_id, (int) $f['uid'])) {
        return 'Ingreso BLOQUEADO: ese usuario ya aporta un personaje a la tripulación (un PJ por usuario, 22.9).';
    }
    // Espacio del barco (5.17): solo PJs, espacio por raza.
    $usado = ope7_trip_espacio_usado($trip_id);
    $max = ope7_trip_espacio_max($trip);
    $ocupa = ope7_trip_espacio_raza($ingresado);
    if ($max < 1 || $usado + $ocupa > $max) {
        return 'Ingreso BLOQUEADO: el barco tiene ' . $max . ' plaza(s), ocupadas ' . $usado . ' y este personaje ocupa ' . $ocupa . ' (5.17).';
    }

    $db->insert_query('ope_tripulantes', array(
        'tripulacion_id' => $trip_id,
        'personaje_id'   => $ingresado,
        'rol'            => 'miembro',
        'espacio_ocupado'=> $ocupa,
        'fecha_ingreso'  => TIME_NOW,
        'fecha_salida'   => 0,
        'estado'         => 'activo',
    ));
    $q = $db->simple_select('ope_personajes', 'nombre', "id = {$ingresado}", array('limit' => 1));
    $nombre = (string) $db->fetch_field($q, 'nombre');
    ope7_trip_hist($trip_id, 'ingreso', 'Ingreso de «' . $nombre . '» (#' . $ingresado . ') — ocupa ' . $ocupa . ' plaza(s).', (int) ($tr['_staff_uid'] ?? 0));

    return '«' . $nombre . '» (# ' . $ingresado . ') ingresó a «' . (string) $trip['nombre'] . '» (' . ($usado + $ocupa) . '/' . $max . ' plazas ocupadas, 5.17). Fecha de ingreso registrada.';
}

/**
 * Efecto 65 · Baja/expulsión (5.21-ter, ligero/firma): libera la plaza y
 * reparte la parte del cofre del que sale con registro (5.9).
 */
function ope7_efecto_baja_tripulacion($tr, $pid, $res, $ids)
{
    global $db;
    $capitan = (int) $pid;
    $trip_id = (int) ($ids['tripulacion_id'] ?? $res['tripulacion_id'] ?? 0);
    $expulsado = (int) ($ids['expulsado_id'] ?? $res['expulsado_id'] ?? 0);
    if ($capitan < 1 || $trip_id < 1 || $expulsado < 1 || !ope7_tabla_existe('tripulaciones')) {
        return 'Baja BLOQUEADA: faltan datos (tripulación o personaje).';
    }
    $trip = ope7_trip_get($trip_id);
    if (!$trip || (string) $trip['estado'] !== 'activa') {
        return 'Baja BLOQUEADA: la tripulación no está activa.';
    }
    if ((int) $trip['capitan_id'] !== $capitan) {
        return 'Baja BLOQUEADA: solo el capitán da de baja o expulsa (22.3).';
    }
    if ($expulsado === $capitan) {
        return 'Baja BLOQUEADA: el capitán no se da de baja a sí mismo — usa el trámite 66 (cambio de capitán) o 67 (disolución).';
    }
    $q = $db->simple_select('ope_tripulantes', '*', "tripulacion_id = {$trip_id} AND personaje_id = {$expulsado} AND estado = 'activo'", array('limit' => 1));
    $miembro = $db->fetch_array($q);
    if (!$miembro) {
        return 'Baja BLOQUEADA: ese personaje no es miembro activo de la tripulación.';
    }
    $q = $db->simple_select('ope_personajes', 'nombre', "id = {$expulsado}", array('limit' => 1));
    $nombre = (string) $db->fetch_field($q, 'nombre');
    $motivo = trim((string) ($res['motivo'] ?? $tr['motivo'] ?? $tr['_firma_motivo'] ?? ''));
    if ($motivo === '') {
        return 'Baja BLOQUEADA: se requiere un motivo escrito (queda en el histórico).';
    }

    // Reparto de la parte del cofre (5.9): parte equitativa del saldo actual.
    $notas = array();
    $miembros_antes = count(ope7_trip_miembros($trip_id, true));
    $cofre = ope7_trip_cofre_get($trip_id);
    if ((int) $cofre['berries'] > 0 && $miembros_antes > 0) {
        $parte = (int) floor((int) $cofre['berries'] / $miembros_antes);
        if ($parte > 0) {
            ope7_trip_cofre_mover($trip_id, $expulsado, -$parte, 'Reparto por baja/expulsión');
            ope7_cartera_mover($expulsado, 'cartera', $parte);
            $notas[] = 'reparto del cofre +' . number_format($parte) . ' ฿ a su cartera';
        }
    }

    // Libera la plaza (5.17).
    $db->update_query('ope_tripulantes', array(
        'estado'       => 'salio',
        'fecha_salida' => TIME_NOW,
    ), "id = " . (int) $miembro['id']);
    ope7_trip_hist($trip_id, 'baja', 'Baja/expulsión de «' . $nombre . '» (#' . $expulsado . '): ' . $motivo . ($notas ? ' · ' . implode(' · ', $notas) : ''), (int) ($tr['_staff_uid'] ?? 0));

    return '«' . $nombre . '» salió de «' . (string) $trip['nombre'] . '» — plaza liberada (' . ope7_trip_espacio_usado($trip_id) . '/' . ope7_trip_espacio_max($trip) . ').'
        . ($notas ? ' ' . implode(' · ', $notas) . '.' : '');
}

/**
 * Efecto 66 · Cambio de capitán (5.21-ter, staff + firma): cesión o motín con
 * veredicto (5.10/5.14); mueve el cofre (queda en la banda) y dispara el
 * suceso de ronda si cambia el nombre (5.14).
 */
function ope7_efecto_cambio_capitan($tr, $pid, $res, $ids)
{
    global $db;
    $trip_id = (int) ($ids['tripulacion_id'] ?? $res['tripulacion_id'] ?? 0);
    $sucesor = (int) ($res['sucesor_id'] ?? $ids['sucesor_id'] ?? 0);
    if ($trip_id < 1 || $sucesor < 1 || !ope7_tabla_existe('tripulaciones')) {
        return 'Cambio de capitán BLOQUEADO: faltan datos (tripulación o sucesor).';
    }
    $trip = ope7_trip_get($trip_id);
    if (!$trip || (string) $trip['estado'] !== 'activa') {
        return 'Cambio de capitán BLOQUEADO: la tripulación no está activa.';
    }
    $q = $db->simple_select('ope_tripulantes', '*', "tripulacion_id = {$trip_id} AND personaje_id = {$sucesor} AND estado = 'activo'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return 'Cambio de capitán BLOQUEADO: el sucesor debe ser miembro activo de la tripulación.';
    }
    $motivo = trim((string) ($res['motivo'] ?? $tr['_firma_motivo'] ?? $tr['motivo'] ?? ''));
    if ($motivo === '') {
        return 'Cambio de capitán BLOQUEADO: se requiere el motivo del veredicto (cesión o motín, 5.10/5.14).';
    }
    $tipo = (string) ($res['tipo'] ?? $ids['tipo'] ?? 'cesion');
    if (!in_array($tipo, array('cesion', 'motin'), true)) {
        return 'Cambio de capitán BLOQUEADO: indica cesión o motín.';
    }

    $capitan_viejo = (int) $trip['capitan_id'];
    // Roles: capitán → miembro, sucesor → capitán.
    $db->update_query('ope_tripulantes', array('rol' => 'miembro'), "tripulacion_id = {$trip_id} AND rol = 'capitan' AND estado = 'activo'");
    $db->update_query('ope_tripulantes', array('rol' => 'capitan'), "tripulacion_id = {$trip_id} AND personaje_id = {$sucesor} AND estado = 'activo'");
    $db->update_query('ope_tripulaciones', array('capitan_id' => $sucesor), "id = {$trip_id}");

    // Si cambia el nombre → suceso de ronda en borrador (5.14).
    $nuevo_nombre = trim((string) ($res['nuevo_nombre'] ?? $ids['nuevo_nombre'] ?? ''));
    $suceso = '';
    if ($nuevo_nombre !== '' && $nuevo_nombre !== (string) $trip['nombre']) {
        $db->update_query('ope_tripulaciones', array('nombre' => $db->escape_string($nuevo_nombre)), "id = {$trip_id}");
        if (ope7_tabla_existe('sucesos')) {
            $db->insert_query('ope_sucesos', array(
                'isla_id'     => 0,
                'ronda'       => 0,
                'tipo'        => 'tripulacion',
                'titulo'      => 'La banda cambia de nombre: «' . $nuevo_nombre . '»',
                'descripcion' => 'Tras el ' . $tipo . ', la tripulación «' . (string) $trip['nombre'] . '» ahora se llama «' . $nuevo_nombre . '» bajo el mando del nuevo capitán. Suceso en borrador: publícalo cuando toque la ronda.',
                'activo'      => 0,
            ));
            $suceso = ' · suceso de ronda en borrador (cambio de nombre)';
        }
    }
    ope7_trip_hist($trip_id, 'cambio_capitan', ucfirst($tipo) . ': capitán #' . $capitan_viejo . ' → #' . $sucesor . '. ' . $motivo, (int) ($tr['_staff_uid'] ?? 0));

    $q = $db->simple_select('ope_personajes', 'nombre', "id = {$sucesor}", array('limit' => 1));
    $nombre_s = (string) $db->fetch_field($q, 'nombre');
    return 'Nuevo capitán de «' . (string) $trip['nombre'] . '»: «' . $nombre_s . '» (#' . $sucesor . ') por ' . $tipo . ' con veredicto. '
        . 'El cofre común queda en la banda.' . ($suceso !== '' ? $suceso : '');
}

/**
 * Efecto 67 · Disolución (5.21-ter, staff + firma): reparte el cofre (5.9),
 * devuelve objetos, el barco queda en manos del último capitán y cierra la
 * entidad. Automática si <2 activos (hook de ronda, con aviso y plazo).
 */
function ope7_efecto_disolver_tripulacion($tr, $pid, $res, $ids)
{
    global $db;
    $trip_id = (int) ($ids['tripulacion_id'] ?? $res['tripulacion_id'] ?? 0);
    if ($trip_id < 1 || !ope7_tabla_existe('tripulaciones')) {
        return 'Disolución BLOQUEADA: falta la tripulación.';
    }
    $trip = ope7_trip_get($trip_id);
    if (!$trip || (string) $trip['estado'] !== 'activa') {
        return 'Disolución BLOQUEADA: la tripulación no está activa.';
    }
    $motivo = trim((string) ($res['motivo'] ?? $tr['_firma_motivo'] ?? $tr['motivo'] ?? ''));
    if ($motivo === '') {
        return 'Disolución BLOQUEADA: se requiere un motivo escrito (queda en el histórico).';
    }

    $notas = array();
    $miembros = ope7_trip_miembros($trip_id, true);
    $n = count($miembros);
    $cofre = ope7_trip_cofre_get($trip_id);
    if ((int) $cofre['berries'] > 0 && $n > 0) {
        $parte = (int) floor((int) $cofre['berries'] / $n);
        $residuo = (int) $cofre['berries'] - $parte * $n;
        foreach ($miembros as $m) {
            $monto = $parte + ($residuo > 0 && (int) $m['personaje_id'] === (int) $trip['capitan_id'] ? $residuo : 0);
            if ($monto > 0) {
                ope7_cartera_mover((int) $m['personaje_id'], 'cartera', $monto);
                $notas[] = '#' . (int) $m['personaje_id'] . ' +' . number_format($monto) . ' ฿';
            }
        }
        // El cofre se vacía con registro.
        ope7_trip_cofre_mover($trip_id, (int) $trip['capitan_id'], -(int) $cofre['berries'], 'Reparto por disolución');
    }
    // Todos los miembros salen (fecha_salida).
    $db->update_query('ope_tripulantes', array('estado' => 'salio', 'fecha_salida' => TIME_NOW), "tripulacion_id = {$trip_id} AND estado = 'activo'");
    // El barco queda en manos del último capitán (22.9).
    $barco_id = (int) ($trip['barco_id'] ?? 0);
    if ($barco_id > 0 && ope7_tabla_existe('barcos')) {
        $db->update_query('ope_barcos', array(
            'dueno_id'      => (int) $trip['capitan_id'],
            'tripulacion_id'=> 0,
        ), "id = {$barco_id}");
        $notas[] = 'barco al último capitán (#' . (int) $trip['capitan_id'] . ')';
    }
    $db->update_query('ope_tripulaciones', array('estado' => 'disuelta'), "id = {$trip_id}");
    ope7_trip_hist($trip_id, 'disolucion', 'Disolución: ' . $motivo . ($notas ? ' · ' . implode(' · ', $notas) : ''), (int) ($tr['_staff_uid'] ?? 0));

    return 'Tripulación «' . (string) $trip['nombre'] . '» disuelta con motivo (histórico).'
        . ($notas ? ' ' . implode(' · ', $notas) . '.' : '');
}

/**
 * Cron de ronda (5.14/22.9): tripulaciones con <2 PJs activos → aviso con
 * plazo para reclutar en la primera detección; disolución automática con
 * motivo en la siguiente (el manual: «con aviso y plazo para reclutar»).
 * Idempotente; integrado en ope7_progresion_cron.
 */
function ope7_tripulaciones_ronda_cerrar()
{
    global $db;
    if (!ope7_tabla_existe('tripulaciones') || !ope7_tabla_existe('tripulantes')) {
        return 0;
    }
    $q = $db->query('SELECT t.* FROM ' . ope7_tabla_full('tripulaciones') . ' t '
        . "WHERE t.estado = 'activa' AND (SELECT COUNT(*) FROM " . ope7_tabla_full('tripulantes') . " m "
        . "WHERE m.tripulacion_id = t.id AND m.estado = 'activo') < 2 LIMIT 50");
    $n = 0;
    while ($trip = $db->fetch_array($q)) {
        $trip_id = (int) $trip['id'];
        $ya_avisada = (int) ($trip['aviso_disolucion_en'] ?? 0) > 0;
        if (!$ya_avisada) {
            // 1ª detección: aviso con plazo para reclutar (no se disuelve aún).
            $db->update_query('ope_tripulaciones', array('aviso_disolucion_en' => TIME_NOW), "id = {$trip_id}");
            ope7_trip_hist($trip_id, 'aviso_disolucion', 'Menos de 2 miembros activos: la tripulación se disuelve si no recluta antes de la próxima ronda (22.9).', 0);
        } else {
            // 2ª detección: disolución automática con motivo (22.9).
            $res = array('motivo' => 'Disolución automática por menos de 2 miembros activos tras el aviso (22.9, hook de ronda 5.14).');
            ope7_efecto_disolver_tripulacion(array('_firma_motivo' => $res['motivo'], '_staff_uid' => 0), 0, $res, array('tripulacion_id' => $trip_id));
        }
        $n++;
    }
    return $n;
}

/**
 * Panel staff «Tripulaciones» (Anexo A.3, 5.21-ter): fichas activas con
 * miembros y su espacio (5.17), cofre común con log (5.9), avisos de
 * disolución y histórico auditable. Devuelve HTML sin <style> ni estilos
 * inline estáticos.
 */
function ope7_tripulaciones_panel_html()
{
    global $db, $mybb;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $h = array();
    $h[] = '<div class="shead"><h1>Tripulaciones</h1><span class="code">A.3 · 5.21-ter/22.9</span><span class="rule"></span></div>';
    $h[] = '<p class="zs-intro">La tripulación es una <b>capa operativa</b> (sin bonos numéricos): formaliza el cofre común (5.9), el límite por plazas del barco (5.17, solo PJs) y los presentes compartidos. '
        . 'El capitán cambia por cesión o motín con veredicto (5.10/5.14) y la banda se disuelve si queda por debajo de <b>2 activos</b> (aviso + plazo para reclutar, 22.9).</p>';

    // ── Avisos de disolución ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Avisos de disolución</span><span class="c">&lt;2 activos · plazo para reclutar</span></div><div class="plate-b">';
    $avisos = array();
    if (ope7_tabla_existe('tripulaciones')) {
        $q = $db->query('SELECT t.* FROM ' . ope7_tabla_full('tripulaciones') . ' t '
            . "WHERE t.estado = 'activa' AND t.aviso_disolucion_en > 0 "
            . 'AND (SELECT COUNT(*) FROM ' . ope7_tabla_full('tripulantes') . " m WHERE m.tripulacion_id = t.id AND m.estado = 'activo') < 2");
        while ($r = $db->fetch_array($q)) {
            $avisos[] = $r;
        }
    }
    if (!$avisos) {
        $h[] = '<p class="pj-empty">Sin avisos: todas las tripulaciones activas tienen 2+ miembros.</p>';
    } else {
        foreach ($avisos as $r) {
            $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) $r['nombre']) . '</b>'
                . ' <span class="ms-chip">aviso desde ' . gmdate('d/m/Y', (int) $r['aviso_disolucion_en']) . '</span>'
                . '<div class="zs-mut">Se disuelve automáticamente en la próxima ronda si no recluta (22.9). El botón «Cerrar (67)» de abajo la disuelve ya con reparto.</div>'
                . '</div></div>';
        }
    }
    $h[] = '</div></div>';

    // ── Tripulaciones activas ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Tripulaciones activas</span><span class="c">ficha · miembros · cofre</span></div><div class="plate-b">';
    $tris = array();
    if (ope7_tabla_existe('tripulaciones')) {
        $q = $db->query('SELECT t.*, c.berries AS cofre_berries, p.nombre AS capitan_nombre, b.nombre AS barco_nombre '
            . 'FROM ' . ope7_tabla_full('tripulaciones') . ' t '
            . 'LEFT JOIN ' . ope7_tabla_full('cofre_tripulacion') . ' c ON c.tripulacion_id = t.id '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = t.capitan_id '
            . 'LEFT JOIN ' . ope7_tabla_full('barcos') . ' b ON b.id = t.barco_id '
            . "WHERE t.estado = 'activa' ORDER BY t.nombre");
        while ($r = $db->fetch_array($q)) {
            $tris[] = $r;
        }
    }
    if (!$tris) {
        $h[] = '<p class="pj-empty">Sin tripulaciones activas (se fundan con el trámite 63, mínimo 2 personas).</p>';
    } else {
        foreach ($tris as $r) {
            $trip_id = (int) $r['id'];
            $miembros = ope7_trip_miembros($trip_id, true);
            $usado = 0;
            $filas = array();
            foreach ($miembros as $m) {
                $usado += (int) $m['espacio_ocupado'];
                $filas[] = ($m['rol'] === 'capitan' ? '👑 ' : '') . $e((string) $m['pj_nombre']) . ' <span class="zs-mut">(' . (int) $m['espacio_ocupado'] . ' pl.)</span>';
            }
            $max = ope7_trip_espacio_max($r);
            $cofre = ope7_trip_cofre_get($trip_id);
            $log = $cofre['log'];
            $ultimo_log = $log ? end($log) : null;
            $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) $r['nombre']) . '</b>'
                . ' <span class="ms-chip">' . count($miembros) . ' miembros</span>'
                . '<div class="zs-mut">Capitán: ' . $e((string) ($r['capitan_nombre'] ?? '—')) . ' · Barco: ' . $e((string) ($r['barco_nombre'] ?? '—')) . ' · Espacio ' . $usado . '/' . $max . ' (5.17)</div>'
                . '<div class="zs-mut">' . implode(' · ', $filas) . '</div>'
                . '<div class="zs-mut">Cofre común (5.9): ' . number_format((int) ($cofre['berries'] ?? 0)) . ' ฿'
                . ($ultimo_log ? ' · último: ' . $e((string) ($ultimo_log['concepto'] ?? '')) . ' (' . (int) ($ultimo_log['cantidad'] ?? 0) . ' ฿)' : '') . '</div>'
                . ($r['aviso_disolucion_en'] > 0 ? '<div class="ms-secret">⚠ aviso de disolución activo (22.9)</div>' : '')
                . '</div></div>';
        }
    }
    $h[] = '</div></div>';

    // ── Acciones del staff: cambio de capitán (66) y disolución (67) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Acciones del staff</span><span class="c">66 · cambio de capitán · 67 · disolución</span></div><div class="plate-b">';
    $tris_act = array();
    if (ope7_tabla_existe('tripulaciones')) {
        $q = $db->query('SELECT t.*, b.nombre AS barco_nombre FROM ' . ope7_tabla_full('tripulaciones') . ' t '
            . 'LEFT JOIN ' . ope7_tabla_full('barcos') . ' b ON b.id = t.barco_id '
            . "WHERE t.estado = 'activa' ORDER BY t.nombre");
        while ($r = $db->fetch_array($q)) {
            $tris_act[] = $r;
        }
    }
    if (!$tris_act) {
        $h[] = '<p class="pj-empty">Sin tripulaciones activas: no hay nada que cambiar o disolver.</p>';
    } else {
        // Opciones de tripulación (id → [label, miembros]).
        $opts_trip = '';
        $miembros_por_trip = array();
        foreach ($tris_act as $r) {
            $miembros_por_trip[(int) $r['id']] = ope7_trip_miembros((int) $r['id'], true);
            $opts_trip .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['nombre']) . ' (' . count($miembros_por_trip[(int) $r['id']]) . ' miembros)</option>';
        }
        // 66 · Cambio de capitán
        $h[] = '<form method="post" action="tripulaciones-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="cambio_capitan">'
            . '<div class="zs-row"><div class="ms-grow"><b>Cambio de capitán (66)</b>'
            . '<div class="zs-mut">Cesión o motín con veredicto (5.10/5.14): elige la banda, el sucesor y el tipo. Si cambia el nombre, se genera el suceso de ronda (5.14).</div></div></div>'
            . '<div class="zs-grid2"><label class="flabel">Tripulación<select name="tripulacion_id" class="tp-dyn">' . $opts_trip . '</select></label>'
            . '<label class="flabel">Tipo<select name="tipo" class="tp-dyn"><option value="cesion">Cesión (voluntaria)</option><option value="motin">Motín (veredicto)</option></select></label></div>';
        // Selector de sucesor por tripulación (JS: se rellena al elegir banda).
        $h[] = '<label class="flabel">Nuevo capitán<select name="sucesor_id" class="tp-dyn" id="ts-sucesor"><option value="">— elige primero la tripulación —</option></select></label>'
            . '<label class="flabel">Nuevo nombre (opcional)<input type="text" name="nuevo_nombre" maxlength="120"></label>'
            . '<label class="flabel">Motivo / veredicto (obligatorio)<textarea name="motivo" maxlength="1500" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 66</button></form>';
        // Datos de sucesores por tripulación para el JS.
        $h[] = '<script>window.__ts_miembros = ' . json_encode(array_map(function ($m) { return array_map(function ($x) { return array((int) $x['personaje_id'], (string) $x['pj_nombre'], (string) $x['rol']); }, $m); }, $miembros_por_trip), JSON_UNESCAPED_UNICODE) . ';</script>';
        // 67 · Disolución
        $h[] = '<form method="post" action="tripulaciones-staff.php" class="zs-form">'
            . '<input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="disolver">'
            . '<div class="zs-row"><div class="ms-grow"><b>Disolución (67)</b>'
            . '<div class="zs-mut">Reparte el cofre común entre los miembros (5.9), el barco vuelve al último capitán y la entidad se cierra (22.9). También disponible automáticamente tras el aviso por &lt;2 activos.</div></div></div>'
            . '<label class="flabel">Tripulación<select name="tripulacion_id" class="tp-dyn">' . $opts_trip . '</select></label>'
            . '<label class="flabel">Motivo (obligatorio)<textarea name="motivo" maxlength="1500" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 67</button></form>';
    }
    $h[] = '</div></div>';

    // ── Histórico auditable ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Histórico de tripulaciones</span><span class="c">auditable · motivo y firma</span></div><div class="plate-b">';
    if (ope7_tabla_existe('tripulacion_historico')) {
        $q = $db->query('SELECT h.*, t.nombre AS trip_nombre FROM ' . ope7_tabla_full('tripulacion_historico') . ' h '
            . 'JOIN ' . ope7_tabla_full('tripulaciones') . ' t ON t.id = h.tripulacion_id '
            . 'ORDER BY h.fecha DESC LIMIT 20');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin eventos registrados todavía.</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Tripulación</th><th>Evento</th><th>Motivo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                    . '<td>' . $e((string) $r['trip_nombre']) . '</td>'
                    . '<td>' . $e((string) $r['evento']) . '</td>'
                    . '<td>' . $e((string) $r['motivo']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de histórico no migrada.</p>';
    }
    $h[] = '</div></div>';

    // JS: el selector de sucesor se rellena según la tripulación elegida (66).
    $h[] = '<script>
(function () {
    var datos = window.__ts_miembros || {};
    var selTrip = document.querySelector("form[action=tripulaciones-staff.php] select[name=tripulacion_id]");
    var selSuc = document.getElementById("ts-sucesor");
    if (!selTrip || !selSuc) { return; }
    var rellena = function () {
        var id = selTrip.value;
        selSuc.innerHTML = "";
        (datos[id] || []).forEach(function (m) {
            var o = document.createElement("option");
            o.value = m[0];
            o.textContent = (m[2] === "capitan" ? "👑 " : "") + m[1];
            selSuc.appendChild(o);
        });
        if ((datos[id] || []).length === 0) {
            var o = document.createElement("option");
            o.value = "";
            o.textContent = "— sin miembros activos —";
            selSuc.appendChild(o);
        }
    };
    selTrip.addEventListener("change", rellena);
    rellena();
})();
</script>';

    return implode("\n", $h);
}