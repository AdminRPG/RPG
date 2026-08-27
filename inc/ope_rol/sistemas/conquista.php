<?php
/**
 * One Piece: 7 Seas · Conquista y control territorial (F4.3, 5.15 / cap. 16)
 * -------------------------------------------------------------------------
 * Trámites 34–37:
 *   34 · Anuncio de conquista (ia-general, jugador → staff): control previo
 *       (salvaje/guarnición/territorio de jugador, 16.2), fases, duración
 *       mínima, suceso público e invitación al defensor.
 *   35 · Responder al asedio (ligero, defensor): defensa activa registrada.
 *   36 · Resolver/registrar conquista (staff, skill-mundo-vivo): veredicto,
 *       registro de afiliación/fuerza defensiva con motivo, suspensión de
 *       tiendas del anterior dueño (16.6) y periódico/rumores.
 *   37 · Declarar reconquista (ia-general, jugador → staff): nueva disputa
 *       con las mismas cinco fases (16.5).
 * Ejércitos y hordas (16.7): unidades (Infantería/Élite/Especialistas) con
 * contrato + mantenimiento por ronda y máx 4 por bando; hordas (Mara/Masa/
 * Marea) contratables una vez por asedio o generadas por el Mundo Vivo.
 * Abandono (16.5): 2 rondas sin actividad → revuelta propuesta; 3.ª se aplica.
 * Sin azar: todo veredicto con motivo; la IA propone, el staff firma.
 */

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

/** Conquista por id (fila completa). */
function ope7_conquista_por_id($id)
{
    global $db;
    $id = (int) $id;
    if ($id < 1 || !ope7_tabla_existe('conquistas')) {
        return null;
    }
    $q = $db->simple_select('ope_conquistas', '*', "id = {$id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Conquistas activas (sin resolver), por isla. */
function ope7_conquistas_activas()
{
    global $db;
    if (!ope7_tabla_existe('conquistas')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_conquistas', '*', "estado = 'activa'", array('order_by' => 'ronda_inicio', 'order_dir' => 'DESC'));
    while ($r = $db->fetch_array($q)) {
        $r['isla'] = ope7_isla_por_id((int) $r['isla_id']);
        $out[] = $r;
    }
    return $out;
}

/** Conquistas resueltas (histórico), por isla, más recientes primero. */
function ope7_conquistas_historico($limite = 30)
{
    global $db;
    if (!ope7_tabla_existe('conquistas')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_conquistas', '*', "estado != 'activa'",
        array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => (int) $limite));
    while ($r = $db->fetch_array($q)) {
        $r['isla'] = ope7_isla_por_id((int) $r['isla_id']);
        $out[] = $r;
    }
    return $out;
}

/**
 * Rondas de asedio requeridas según el control previo y la fuerza defensiva
 * (16.3): salvaje 0 · guarnición nv1–15: 1 · nv16–30: 2 · nv31–45: 3 ·
 * nv46–50: 4+. Las fortificaciones construidas suman 1 (16.4).
 */
function ope7_conquista_rondas_requeridas($afiliacion, $fd_nivel, $fortificaciones = false)
{
    $afiliacion = (string) $afiliacion;
    if ($afiliacion === 'salvaje') {
        return 0;
    }
    $n = (int) $fd_nivel;
    if ($n <= 15) {
        $rondas = 1;
    } elseif ($n <= 30) {
        $rondas = 2;
    } elseif ($n <= 45) {
        $rondas = 3;
    } else {
        $rondas = 4;
    }
    if ($fortificaciones) {
        $rondas++;
    }
    return $rondas;
}

/** Unidades activas de un bando en una conquista (16.7). */
function ope7_conquista_unidades_bando($conquista_id, $bando)
{
    global $db;
    $conquista_id = (int) $conquista_id;
    $bando = (string) $bando === 'defensor' ? 'defensor' : 'atacante';
    if ($conquista_id < 1 || !ope7_tabla_existe('unidades')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_unidades', '*', "conquista_id = {$conquista_id} AND bando = '{$bando}' AND estado = 'activa'");
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Hordas activas de una conquista (16.7). */
function ope7_conquista_hordas($conquista_id)
{
    global $db;
    $conquista_id = (int) $conquista_id;
    if ($conquista_id < 1 || !ope7_tabla_existe('hordas')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_hordas', '*', "conquista_id = {$conquista_id} AND estado = 'activa'");
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Log de asedio por ronda (16.8): acciones ponderadas + desgaste + veredictos. */
function ope7_conquista_log_asedio($conquista_id, $ronda, array $acciones = array(), array $desgaste = array(), array $veredictos = array())
{
    global $db;
    $conquista_id = (int) $conquista_id;
    if ($conquista_id < 1 || !ope7_tabla_existe('asedios')) {
        return 0;
    }
    return (int) $db->insert_query('ope_asedios', array(
        'conquista_id' => $conquista_id,
        'ronda'        => (int) $ronda,
        'acciones'     => json_encode($acciones, JSON_UNESCAPED_UNICODE),
        'desgaste'     => json_encode($desgaste, JSON_UNESCAPED_UNICODE),
        'veredictos'   => json_encode($veredictos, JSON_UNESCAPED_UNICODE),
    ));
}

/** Ronda actual (número) o 0. */
function ope7_conquista_ronda_actual()
{
    $r = ope7_ronda_activa();
    return $r ? (int) $r['numero'] : 0;
}

/** Publica un suceso de conquista (hook del anuncio, 16.8). */
function ope7_conquista_suceso($isla_id, $titulo, $descripcion, $ronda = 0)
{
    global $db;
    if (!ope7_tabla_existe('sucesos')) {
        return 0;
    }
    return (int) $db->insert_query('ope_sucesos', array(
        'isla_id'     => (int) $isla_id,
        'ronda'       => (int) $ronda,
        'tipo'        => 'conquista',
        'titulo'      => trim((string) $titulo),
        'descripcion' => trim((string) $descripcion),
        'impacto'     => json_encode(array('F_suceso' => 1), JSON_UNESCAPED_UNICODE),
        'activo'      => 1,
    ));
}

/**
 * Veredicto de rango alto para desplegar 3–4 unidades (16.7): «más de 2 exige
 * un rango alto o un imperio» — interpretación D4.8: cúspide de facción
 * (es_cuspide) o nivel de personaje ≥ 30 (imperio en ciernes).
 */
function ope7_conquista_puede_mandar_ejercito($pid)
{
    $pid = (int) $pid;
    $fp = function_exists('ope7_faccion_pj') ? ope7_faccion_pj($pid) : null;
    if ($fp && !empty($fp['rango']) && (int) ($fp['rango']['es_cuspide'] ?? 0) === 1) {
        return true;
    }
    if (ope7_tabla_existe('personajes')) {
        global $db;
        $q = $db->simple_select('ope_personajes', 'nivel', "id = {$pid}", array('limit' => 1));
        $nivel = (int) $db->fetch_field($q, 'nivel');
        if ($nivel >= 30) {
            return true;
        }
    }
    return false;
}

/**
 * Contratar una unidad (16.7): paga el contrato desde cartera, valida el cupo
 * (máx 4 por bando) y el rango alto para la 3.ª y 4.ª. Devuelve array con ok.
 */
function ope7_conquista_contratar_unidad($pid, $conquista_id, $tipo)
{
    global $db;
    $pid = (int) $pid;
    $conquista_id = (int) $conquista_id;
    $tipo = in_array((string) $tipo, array('infanteria', 'elite', 'especialista'), true) ? (string) $tipo : 'infanteria';
    $conq = ope7_conquista_por_id($conquista_id);
    if (!$conq) {
        return array('ok' => false, 'msg' => 'La conquista no existe.');
    }
    if ((int) $conq['atacante_id'] !== $pid) {
        return array('ok' => false, 'msg' => 'Solo el bando atacante contrata unidades para su asedio (16.7).');
    }
    $costes = array('infanteria' => 10000, 'elite' => 50000, 'especialista' => 25000);
    $mant   = array('infanteria' => 1000,  'elite' => 5000,  'especialista' => 2500);
    $capacidad = array('infanteria' => 'defiende y asedia zonas sin fortificar; no rompe fortificaciones',
                       'elite' => 'defiende fortificaciones y asalta con ventaja',
                       'especialista' => 'rompe fortificaciones y sostiene a las tropas');
    $actuales = count(ope7_conquista_unidades_bando($conquista_id, 'atacante'));
    if ($actuales >= 4) {
        return array('ok' => false, 'msg' => 'Máximo 4 unidades por bando en una conquista (16.7).');
    }
    if ($actuales >= 2 && !ope7_conquista_puede_mandar_ejercito($pid)) {
        return array('ok' => false, 'msg' => 'Desplegar más de 2 unidades exige un rango alto o un imperio (16.7 — D4.8).');
    }
    $coste = $costes[$tipo];
    $mov = ope7_cartera_mover($pid, 'cartera', -$coste);
    if (!$mov['ok']) {
        return array('ok' => false, 'msg' => 'Saldo insuficiente: el contrato de ' . $tipo . ' cuesta ' . number_format($coste, 0, ',', '.') . ' ฿.');
    }
    $id = (int) $db->insert_query('ope_unidades', array(
        'tipo'          => $tipo,
        'coste'         => $coste,
        'mantenimiento' => $mant[$tipo],
        'capacidad'     => json_encode(array('descripcion' => $capacidad[$tipo]), JSON_UNESCAPED_UNICODE),
        'dueno_id'      => $pid,
        'isla_id'       => (int) $conq['isla_id'],
        'conquista_id'  => $conquista_id,
        'bando'         => 'atacante',
        'cantidad'      => 1,
        'estado'        => 'activa',
    ));
    return array('ok' => true, 'id' => $id, 'msg' => 'Unidad ' . $tipo . ' contratada (' . number_format($coste, 0, ',', '.') . ' ฿, mantenimiento ' . number_format($mant[$tipo], 0, ',', '.') . ' ฿/ronda).');
}

/**
 * Contratar una horda (16.7): una sola vez por asedio y por bando; las hordas
 * las genera el Mundo Vivo o las contrata un bando. Factor del escenario.
 */
function ope7_conquista_contratar_horda($pid, $conquista_id, $tamaño)
{
    global $db;
    $pid = (int) $pid;
    $conquista_id = (int) $conquista_id;
    $tamaño = in_array((string) $tamaño, array('mara', 'masa', 'marea'), true) ? (string) $tamaño : 'mara';
    $conq = ope7_conquista_por_id($conquista_id);
    if (!$conq) {
        return array('ok' => false, 'msg' => 'La conquista no existe.');
    }
    if ((int) $conq['atacante_id'] !== $pid) {
        return array('ok' => false, 'msg' => 'Solo el bando atacante contrata hordas para su asedio (16.7).');
    }
    $tabla = array('mara' => array(10000, 'Decenas', 'nv1–10'),
                   'masa' => array(50000, 'Cientos', 'nv15–25'),
                   'marea' => array(200000, 'Miles', 'nv30–40'));
    if (!ope7_tabla_existe('hordas')) {
        return array('ok' => false, 'msg' => 'Hordas no migradas (pendiente).');
    }
    $q = $db->simple_select('ope_hordas', 'id', "conquista_id = {$conquista_id} AND contratada_por = {$pid} AND estado = 'activa'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Ya contrataste una horda para este asedio: una sola vez por asedio (16.7).');
    }
    $coste = $tabla[$tamaño][0];
    $mov = ope7_cartera_mover($pid, 'cartera', -$coste);
    if (!$mov['ok']) {
        return array('ok' => false, 'msg' => 'Saldo insuficiente: una ' . $tamaño . ' cuesta ' . number_format($coste, 0, ',', '.') . ' ฿.');
    }
    $id = (int) $db->insert_query('ope_hordas', array(
        'isla_id'       => (int) $conq['isla_id'],
        'conquista_id'  => $conquista_id,
        'contratada_por'=> $pid,
        'tamaño'        => $tamaño,
        'fuerza'        => $tamaño === 'marea' ? 35 : ($tamaño === 'masa' ? 20 : 5),
        'estado'        => 'activa',
        'veredicto_ronda'=> json_encode(array('nota' => 'Contratada por el bando atacante (' . $tabla[$tamaño][1] . ', ' . $tabla[$tamaño][2] . ').'), JSON_UNESCAPED_UNICODE),
    ));
    return array('ok' => true, 'id' => $id, 'msg' => 'Horda ' . $tamaño . ' contratada (' . number_format($coste, 0, ',', '.') . ' ฿, una sola vez por asedio).');
}

/**
 * Cron de ronda — mantenimientos de unidades (16.7): sin pago se van. Descuenta
 * el mantenimiento de la cartera del dueño; si no hay saldo → retirada.
 */
function ope7_conquista_mantenimientos()
{
    global $db;
    if (!ope7_tabla_existe('unidades')) {
        return 0;
    }
    $retiradas = 0;
    $q = $db->simple_select('ope_unidades', '*', "estado = 'activa'");
    while ($u = $db->fetch_array($q)) {
        $mant = (int) $u['mantenimiento'] * max(1, (int) $u['cantidad']);
        $mov = ope7_cartera_mover((int) $u['dueno_id'], 'cartera', -$mant);
        if (!$mov['ok']) {
            $db->update_query('ope_unidades', array('estado' => 'retirada'), 'id = ' . (int) $u['id']);
            $retiradas++;
        }
    }
    return $retiradas;
}

/**
 * Cron de ronda — abandono (16.5): una conquista ganada (ocupación) con 2
 * rondas sin actividad de ocupación propone la revuelta; en la 3.ª ronda se
 * aplica: afiliación → local/salvaje con motivo (fuente conquista).
 */
function ope7_conquista_abandonos()
{
    global $db;
    if (!ope7_tabla_existe('conquistas')) {
        return array('propuestas' => 0, 'aplicadas' => 0);
    }
    $ronda = ope7_conquista_ronda_actual();
    $propuestas = 0;
    $aplicadas = 0;
    $q = $db->simple_select('ope_conquistas', '*', "estado = 'ganada' AND fase = 'ocupacion'");
    while ($c = $db->fetch_array($q)) {
        $sin_actividad = $ronda - (int) $c['ultima_actividad_ronda'];
        if ($sin_actividad >= 3) {
            $isla = ope7_isla_por_id((int) $c['isla_id']);
            $nombre = $isla ? (string) ($isla['nombre'] ?? 'la isla') : 'la isla';
            ope7_isla_actualizar((int) $c['isla_id'], array(
                'afiliacion' => 'local',
                'quien_manda' => 'Autoridades locales (revuelta por abandono)',
            ), 'conquista', 'Abandono: 3 rondas sin actividad de ocupación (16.5) — ' . $nombre . ' se revuelve.', $ronda);
            $db->update_query('ope_conquistas', array('estado' => 'abandonada'), 'id = ' . (int) $c['id']);
            ope7_conquista_suceso((int) $c['isla_id'], 'Revuelta en ' . $nombre,
                'Tras 3 rondas sin actividad de ocupación, el territorio se revuelve (16.5).', $ronda);
            $aplicadas++;
        } elseif ($sin_actividad >= 2) {
            $propuestas++;
        }
    }
    return array('propuestas' => $propuestas, 'aplicadas' => $aplicadas);
}

// ─────────────────────────────────────────────────────────────
// Efectos de los trámites 34–37
// ─────────────────────────────────────────────────────────────

/**
 * Efecto 34 · Anuncio de conquista (16.2/16.3): valida el control previo, la
 * presencia justificada y el objetivo (isla o zona), crea la conquista en fase
 * 'anuncio' con las rondas de asedio requeridas y publica el suceso. La firma
 * del staff invita al defensor (participación garantizada, 16.4).
 */
function ope7_efecto_anuncio_conquista($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    if (!ope7_tabla_existe('conquistas')) {
        return 'Anuncio BLOQUEADO: tabla de conquistas no migrada (pendiente).';
    }
    $isla_id = (int) ($res['isla_id'] ?? 0);
    $zona_id = (int) ($res['zona_id'] ?? 0);
    $tipo = $zona_id > 0 ? 'zona' : 'isla';
    $isla = $isla_id > 0 ? ope7_isla_por_id($isla_id) : null;
    if (!$isla) {
        return 'Anuncio BLOQUEADO: elige una isla del catálogo (5.14) como objetivo.';
    }
    // Presencia justificada (16.2): el atacante está en la isla o su ubicación
    // declarada la menciona. Sin presencia no hay conquista.
    if (ope7_tabla_existe('personajes')) {
        $q = $db->simple_select('ope_personajes', 'ubicacion_isla_id, ubicacion_texto', "id = {$pid}", array('limit' => 1));
        $pj = $db->fetch_array($q);
        $en_isla = (int) ($pj['ubicacion_isla_id'] ?? 0) === $isla_id;
        $menciona = stripos((string) ($pj['ubicacion_texto'] ?? ''), (string) ($isla['nombre'] ?? '')) !== false;
        if (!$en_isla && !$menciona) {
            return 'Anuncio BLOQUEADO: necesitas justificar tu presencia en la isla (16.2) — llega por navegación o declara tu ubicación.';
        }
    }
    // Anti-abuso: sin fases no hay conquista → no puede existir otra activa
    // sobre la misma isla (o zona si el objetivo es una zona).
    $where = "isla_id = {$isla_id} AND estado = 'activa'";
    if ($tipo === 'zona') {
        $where .= " AND (zona_id = {$zona_id} OR zona_id IS NULL)";
    }
    $q = $db->simple_select('ope_conquistas', 'id', $where, array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Anuncio BLOQUEADO: ya hay una conquista activa sobre esta isla/zona (16.3).';
    }
    // Control previo (16.2): la fuerza defensiva de la isla define las rondas.
    $ficha = ope7_isla_ficha($isla_id);
    $afiliacion = (string) ($ficha['afiliacion'] ?? 'local');
    $fd_nivel = (int) ($ficha['fuerza_defensiva_nivel'] ?? 1);
    $fort = !empty($ficha['fortificaciones']) && json_decode((string) $ficha['fortificaciones'], true);
    $rondas = ope7_conquista_rondas_requeridas($afiliacion, $fd_nivel, (bool) $fort);
    $ronda = ope7_conquista_ronda_actual();
    $defensor_id = (int) ($res['defensor_id'] ?? 0);
    $bando = trim((string) ($res['bando'] ?? ''));
    $motivo = trim((string) ($res['motivo'] ?? ''));
    $justificacion = trim((string) ($res['justificacion'] ?? ''));
    if ($motivo === '' || $justificacion === '') {
        return 'Anuncio BLOQUEADO: indica el motivo y la justificación de la conquista (16.3).';
    }
    // Bug MyBB conocido (null → '' en INT): SQL crudo para conservar NULL.
    $zona_sql = $zona_id > 0 ? (int) $zona_id : 'NULL';
    $def_sql = $defensor_id > 0 ? (int) $defensor_id : 'NULL';
    $motivo_full = $db->escape_string($motivo . ($justificacion !== '' ? ' — ' . $justificacion : ''));
    $bando_sql = $db->escape_string($bando);
    $db->query('INSERT INTO ' . ope7_tabla_full('conquistas') . " (isla_id, zona_id, atacante_id, bando_atacante, defensor_id, tipo, fase, ronda_inicio, rondas_asedio, estado, ultima_actividad_ronda, motivo) "
        . "VALUES ({$isla_id}, {$zona_sql}, {$pid}, '{$bando_sql}', {$def_sql}, '{$tipo}', 'anuncio', {$ronda}, {$rondas}, 'activa', {$ronda}, '{$motivo_full}')");
    $id = (int) $db->insert_id();
    // Hook del anuncio: suceso público + invitación al defensor (16.8).
    $nombre = (string) ($isla['nombre'] ?? '');
    ope7_conquista_suceso($isla_id, 'Anuncio de conquista: ' . $nombre,
        'Un bando ha anunciado su intención de tomar ' . $nombre
        . ($defensor_id > 0 ? ' y el defensor ha sido invitado a responder.' : ' — la defensa queda convocada.'),
        $ronda);
    return 'Anuncio registrado: conquista de ' . $nombre . ' (' . $tipo . ') · control previo «' . $afiliacion
        . '» · ' . $rondas . ' ronda(s) de asedio requerida(s) (16.3) · suceso público publicado'
        . ($defensor_id > 0 ? ' · defensor invitado (participación garantizada, 16.4)' : '') . '.';
}

/**
 * Efecto 35 · Responder al asedio (defensor, ligero): el defensor declara su
 * defensa activa — pasa la conquista a fase 'asedio' y registra el log de la
 * ronda. Sin participación del defensor no hay veredicto (16.2).
 */
function ope7_efecto_responder_asedio($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $conquista_id = (int) ($res['conquista_id'] ?? 0);
    $conq = ope7_conquista_por_id($conquista_id);
    if (!$conq) {
        return 'Respuesta BLOQUEADA: la conquista no existe.';
    }
    if ((int) $conq['estado'] !== 0 && (string) $conq['estado'] !== 'activa') {
        return 'Respuesta BLOQUEADA: la conquista ya no está activa.';
    }
    $es_defensor = (int) ($conq['defensor_id'] ?? 0) === $pid;
    $guarnicion = (int) ($conq['defensor_id'] ?? 0) === 0;
    if (!$es_defensor && !$guarnicion) {
        return 'Respuesta BLOQUEADA: solo el defensor declarado (o la guarnición) responde al asedio (16.4).';
    }
    $estrategia = trim((string) ($res['estrategia'] ?? ''));
    if ($estrategia === '') {
        return 'Respuesta BLOQUEADA: describe tu defensa (guarnición, fortificaciones, personaje) — 16.4.';
    }
    $ronda = ope7_conquista_ronda_actual();
    $sets = array('fase' => 'asedio', 'ultima_actividad_ronda' => $ronda);
    if ($guarnicion && $es_defensor === false) {
        // El staff responde por la guarnición NPC; defensor_id sigue en 0.
    }
    $db->update_query('ope_conquistas', $sets, 'id = ' . $conquista_id);
    ope7_conquista_log_asedio($conquista_id, $ronda, array('defensa' => $estrategia), array(), array());
    return 'Defensa activa registrada: la conquista pasa a asedio (16.3) · log de la ronda ' . $ronda . ' creado.';
}

/**
 * Efecto 36 · Resolver/registrar conquista (staff, skill-mundo-vivo): el
 * veredicto decide el ganador; si gana el atacante se registra el cambio de
 * afiliación/fuerza defensiva con motivo (fuente conquista) y se suspenden las
 * tiendas del anterior dueño (16.6). Sin motivo no hay registro (16.2).
 */
function ope7_efecto_resolver_conquista($tr, $pid, $res)
{
    global $db;
    $conquista_id = (int) ($res['conquista_id'] ?? 0);
    $conq = ope7_conquista_por_id($conquista_id);
    if (!$conq) {
        return 'Resolución BLOQUEADA: la conquista no existe.';
    }
    if ((string) $conq['estado'] !== 'activa') {
        return 'Resolución BLOQUEADA: la conquista ya está resuelta.';
    }
    $ronda = ope7_conquista_ronda_actual();
    // Duración mínima (16.3): un asedio nunca se resuelve en la ronda del
    // anuncio — salvo isla salvaje (0 rondas: declarar + ocupar).
    $rondas_req = (int) $conq['rondas_asedio'];
    $rondas_transcurridas = $ronda - (int) $conq['ronda_inicio'];
    if ($rondas_req > 0 && $rondas_transcurridas < $rondas_req) {
        return 'Resolución BLOQUEADA: el asedio requiere ' . $rondas_req . ' ronda(s) (16.3); lleva ' . max(0, $rondas_transcurridas) . '.';
    }
    if ($rondas_req === 0 && $rondas_transcurridas === 0 && (string) $conq['fase'] !== 'asedio') {
        // Isla salvaje: declarar + ocupar en la misma ronda está permitido.
    }
    $ganador = (string) ($res['ganador'] ?? '') === 'defensor' ? 'defensor' : 'atacante';
    $motivo = trim((string) ($res['motivo'] ?? ''));
    if ($motivo === '') {
        return 'Resolución BLOQUEADA: sin motivo no hay registro (16.2).';
    }
    $veredicto = trim((string) ($res['veredicto'] ?? ''));
    $isla_id = (int) $conq['isla_id'];
    $isla = ope7_isla_por_id($isla_id);
    $nombre = $isla ? (string) ($isla['nombre'] ?? 'la isla') : 'la isla';
    if ($ganador === 'atacante') {
        // Registro (16.8): afiliación/control + quien manda con motivo.
        $bando = (string) $conq['bando_atacante'];
        $quien_manda = $bando !== '' ? $bando : 'Bando conquistador';
        ope7_isla_actualizar($isla_id, array(
            'afiliacion'           => 'mixta',
            'fuerza_defensiva_nivel' => max(1, (int) ($isla['fuerza_defensiva_nivel'] ?? 1)),
            'quien_manda'          => $quien_manda,
        ), 'conquista', 'Conquista registrada: ' . $motivo, $ronda);
        // Derrota del anterior dueño → cierre forzoso de tiendas (16.6).
        $suspendidas = function_exists('ope7_tiendas_suspender_en_isla') ? ope7_tiendas_suspender_en_isla($isla_id) : 0;
        $db->update_query('ope_conquistas', array(
            'estado'         => 'ganada',
            'fase'           => 'ocupacion',
            'ganador_id'     => (int) $conq['atacante_id'],
            'resuelta_ronda' => $ronda,
            'motivo'         => $motivo,
            'ultima_actividad_ronda' => $ronda,
        ), 'id = ' . $conquista_id);
        ope7_conquista_log_asedio($conquista_id, $ronda, array(), array(), array('veredicto' => $veredicto, 'ganador' => 'atacante'));
        ope7_conquista_suceso($isla_id, 'Conquista de ' . $nombre,
            'El bando atacante toma ' . $nombre . ' (' . $motivo . '). ' . $suspendidas . ' tienda(s) suspendida(s) por cambio de manos (16.6).', $ronda);
        return 'Conquista registrada: ' . $nombre . ' pasa a control del atacante · motivo en el histórico'
            . ' · ' . $suspendidas . ' tienda(s) suspendida(s) por cambio de manos (16.6) · periódico y rumores alimentados.';
    }
    // Defiende el defensor.
    $db->update_query('ope_conquistas', array(
        'estado'         => 'perdida',
        'fase'           => 'registro',
        'resuelta_ronda' => $ronda,
        'motivo'         => $motivo,
    ), 'id = ' . $conquista_id);
    ope7_conquista_log_asedio($conquista_id, $ronda, array(), array(), array('veredicto' => $veredicto, 'ganador' => 'defensor'));
    ope7_conquista_suceso($isla_id, 'Asedio repelido en ' . $nombre,
        'La defensa resiste el asedio (' . $motivo . ').', $ronda);
    return 'Asedio repelido: la defensa conserva ' . $nombre . ' · veredicto con motivo en el histórico.';
}

/**
 * Efecto 37 · Declarar reconquista (16.5): el bando desplazado (o cualquiera)
 * disputa el territorio de nuevo — es otra conquista con las mismas cinco
 * fases. La ventaja del defensor es su fuerza defensiva ya instalada.
 */
function ope7_efecto_reconquista($tr, $pid, $res)
{
    $isla_id = (int) ($res['isla_id'] ?? 0);
    $res['isla_id'] = $isla_id;
    $res['zona_id'] = (int) ($res['zona_id'] ?? 0);
    $prev = null;
    if ($isla_id > 0) {
        global $db;
        $q = $db->simple_select('ope_conquistas', '*', "isla_id = {$isla_id} AND estado != 'activa'", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
        $prev = $db->fetch_array($q);
    }
    if (!$prev) {
        return 'Reconquista BLOQUEADA: no hay conquista previa sobre esta isla — usa el trámite 34 (16.5).';
    }
    $msg = ope7_efecto_anuncio_conquista($tr, $pid, $res);
    if (strpos((string) $msg, 'Anuncio registrado') === 0) {
        return 'Reconquista declarada: ' . $msg . ' La defensa ya está instalada (16.5) — mismas cinco fases.';
    }
    return $msg;
}

// ─────────────────────────────────────────────────────────────
// Panel staff «Conquista» (Anexo A.3, 16.8)
// ─────────────────────────────────────────────────────────────

/** Panel «Conquista»: conquistas activas por isla, ejércitos y histórico. */
function ope7_conquista_panel_html()
{
    global $db;
    $h = array();
    $h[] = '<div class="shead"><h1>Conquista y control territorial</h1><span class="sub">A.3 · 16.x — fases, ejércitos y registro</span></div>';

    $ronda = ope7_conquista_ronda_actual();
    $h[] = '<p class="zs-intro">Ronda actual: <b>' . (int) $ronda . '</b> · regla de oro: la conquista nunca se resuelve en el anuncio; cada veredicto queda con motivo (16.1). Las tiendas del territorio conquistado se suspenden por cambio de manos (16.6).</p>';

    $activas = ope7_conquistas_activas();
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Conquistas activas</span><span class="c">' . count($activas) . '</span></div><div class="plate-b">';
    if (!$activas) {
        $h[] = '<p class="pj-empty">Sin conquistas activas. El anuncio llega por el trámite 34 (bandeja).</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Isla</th><th>Objetivo</th><th>Fase</th><th>Rondas</th><th>Atacante</th><th>Defensor</th></tr></thead><tbody>';
        foreach ($activas as $c) {
            $nombre = $c['isla'] ? (string) ($c['isla']['nombre'] ?? '—') : '—';
            $fase = (string) $c['fase'];
            $rondas = (string) (int) $c['rondas_asedio'] . ' req. · +' . max(0, $ronda - (int) $c['ronda_inicio']) . ' trans.';
            $pa = ope7_pj_get((int) $c['atacante_id']);
            $atacante = $pa ? (string) ($pa['nombre'] ?? 'PJ') : 'PJ';
            $pd = (int) ($c['defensor_id'] ?? 0) > 0 ? ope7_pj_get((int) $c['defensor_id']) : null;
            $defensor = $pd ? (string) ($pd['nombre'] ?? 'Defensor') : 'Guarnición NPC';
            $h[] = '<tr><td><b>' . htmlspecialchars_uni($nombre) . '</b></td><td>' . (string) $c['tipo'] . '</td>'
                . '<td>' . htmlspecialchars_uni($fase) . '</td><td>' . htmlspecialchars_uni($rondas) . '</td>'
                . '<td>' . htmlspecialchars_uni($atacante) . '</td><td>' . htmlspecialchars_uni($defensor) . '</td></tr>';
            // Ejércitos del bando atacante (16.7).
            $unidades = ope7_conquista_unidades_bando((int) $c['id'], 'atacante');
            $hordas = ope7_conquista_hordas((int) $c['id']);
            if ($unidades || $hordas) {
                $n_unidades = count($unidades);
                $n_hordas = count($hordas);
                $mant_total = 0;
                foreach ($unidades as $u) {
                    $mant_total += (int) $u['mantenimiento'];
                }
                $ejercitos = 'Ejércitos del asedio: ' . $n_unidades . ' unidad(es) · ' . $n_hordas . ' horda(s)';
                if ($mant_total > 0) {
                    $ejercitos .= ' · mantenimiento ' . number_format($mant_total, 0, ',', '.') . ' ฿/ronda';
                }
                $h[] = '<tr><td colspan="6" class="zs-sub">' . htmlspecialchars_uni($ejercitos) . '</td></tr>';
            }
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    // Histórico (16.8): registro con motivo.
    $hist = ope7_conquistas_historico(15);
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Histórico de conquistas</span><span class="c">registro con motivo</span></div><div class="plate-b">';
    if (!$hist) {
        $h[] = '<p class="pj-empty">Aún no hay conquistas resueltas.</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Isla</th><th>Resultado</th><th>Ronda</th><th>Motivo</th></tr></thead><tbody>';
        foreach ($hist as $c) {
            $nombre = $c['isla'] ? (string) ($c['isla']['nombre'] ?? '—') : '—';
            $estado = (string) $c['estado'];
            $h[] = '<tr><td><b>' . htmlspecialchars_uni($nombre) . '</b></td><td>' . htmlspecialchars_uni($estado) . '</td>'
                . '<td>' . (int) $c['resuelta_ronda'] . '</td>'
                . '<td>' . htmlspecialchars_uni((string) ($c['motivo'] ?? '')) . '</td></tr>';
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    $h[] = '<p class="zs-intro"><b>Flujo:</b> anuncio (34, público + invitación al defensor) → asedio (35, defensa activa) → resolución (36, veredicto con motivo) → registro (afiliación/fuerza defensiva, suspensión de tiendas) → ocupación (mantenimientos y abandono 16.5). Reconquista por el 37. Unidades/hordas: 16.7.</p>';
    return implode("\n", $h);
}
