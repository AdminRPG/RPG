<?php
/**
 * One Piece: 7 Seas · Narradores y auto-narradas (F5.2, 5.20/cap. 21)
 * ------------------------------------------------------------------
 * Trámites 52–55 (naturaleza ia/staff, skill-narracion-automatica):
 *  · 52 — Solicitud de auto-narrada: valida ficha completa (6 bloques con
 *    condiciones de victoria/fracaso explícitas), requisitos duros, tasa del
 *    tablón (5.9) y un-presente (5.6); lanza los oráculos del acto 1 (motor
 *    de 5.16), abre el tema presente (tema_tipo aventura, invadible) y crea
 *    el primer tramo con el texto de la IA firmado por el staff.
 *  · 53 — Posteo de tramo: recoge los posts de la ronda (nunca adelanta un
 *    tramo sin ellos), lanza el oráculo siguiente si el acto lo pide y crea
 *    el tramo pendiente de firma.
 *  · 54 — Apertura de misión (staff): publica la ficha de 6 bloques en el
 *    tablón solo-staff (condiciones explícitas + secretos solo-staff).
 *  · 55 — Cierre de misión (staff): verifica las condiciones del acto final,
 *    aplica recompensas (berries 5.9 · PP 5.6 · fama 5.12 · objetos 5.8) con
 *    motivo e histórico, cierra el tema y deja el suceso de Mundo Vivo en
 *    borrador (5.14 — el staff lo publica desde el panel).
 *  · Cron: misión en curso cuyo tema se cerró sin cierre de misión →
 *    abandonada con motivo (5.20, anti-abuso).
 * Sin dados: los oráculos son escenarios deterministas por la banda del IRT
 * de la isla (5.16); la IA propone, el staff firma, nada se publica solo.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Categorías del catálogo de misiones (5.20/21.3). */
function ope7_mision_categorias()
{
    return array('faccion', 'reino_isla', 'profesional', 'bajo_mundo', 'especial');
}

/**
 * Ficha completa de una misión (fila + JSONs decodificados).
 * `secretos_json` solo debe servirse a staff/narradores (21.2/21.5).
 */
function ope7_mision_get($mision_id)
{
    global $db;
    $mision_id = (int) $mision_id;
    if ($mision_id < 1 || !ope7_tabla_existe('misiones')) {
        return null;
    }
    $q = $db->simple_select('ope_misiones', '*', "id = {$mision_id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    if (!$r) {
        return null;
    }
    foreach (array('identidad', 'condiciones', 'escenas', 'recompensas', 'requisitos', 'secretos_json', 'oraculos') as $k) {
        $r[$k] = json_decode((string) ($r[$k] ?? 'null'), true);
        if (!is_array($r[$k])) {
            $r[$k] = array();
        }
    }
    return $r;
}

/**
 * Valida la ficha de 6 bloques (21.3). Requisito duro de la auto-narrada:
 * condiciones de victoria Y fracaso explícitas (21.2.2/21.4). Devuelve
 * array('ok', 'msg').
 */
function ope7_mision_ficha_valida(array $m)
{
    $ident = (array) ($m['identidad'] ?? array());
    $cond = (array) ($m['condiciones'] ?? array());
    $esc = (array) ($m['escenas'] ?? array());
    if (trim((string) ($ident['nombre'] ?? '')) === '') {
        return array('ok' => false, 'msg' => 'La misión no tiene nombre (bloque Identidad).');
    }
    if (!in_array((string) ($ident['categoria'] ?? ''), ope7_mision_categorias(), true)) {
        return array('ok' => false, 'msg' => 'Categoría fuera del catálogo (Facción/Reino-Isla/Profesional/Bajo mundo/Especial).');
    }
    $v = trim((string) ($cond['victoria'] ?? ''));
    $f = trim((string) ($cond['fracaso'] ?? ''));
    if ($v === '' || $f === '') {
        return array('ok' => false, 'msg' => 'Sin condiciones de victoria/fracaso explícitas: esta misión NO puede ser auto-narrada (21.2).');
    }
    if (trim((string) ($esc['acto1'] ?? '')) === '' || trim((string) ($esc['acto2'] ?? '')) === '' || trim((string) ($esc['acto3'] ?? '')) === '') {
        return array('ok' => false, 'msg' => 'Faltan escenas: la misión necesita los 3 actos (comienzo/medio/final).');
    }
    return array('ok' => true, 'msg' => 'Ficha completa de 6 bloques (condiciones explícitas).');
}

/**
 * Requisitos duros del solicitante (21.2.5): nivel mínimo, facción y oficios.
 * El tamaño de grupo lo valida el staff al revisar (el prompt lo menciona).
 */
function ope7_mision_cumple_requisitos($pid, array $m)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return array('ok' => false, 'msg' => 'Sin personaje asociado.');
    }
    $req = (array) ($m['requisitos'] ?? array());
    $q = $db->simple_select('ope_personajes', 'nivel', "id = {$pid}", array('limit' => 1));
    $nivel = (int) $db->fetch_field($q, 'nivel');
    $nivel_min = (int) ($req['nivel_min'] ?? 0);
    if ($nivel_min > 0 && $nivel < $nivel_min) {
        return array('ok' => false, 'msg' => "Requisito no cumplido: la misión pide nivel {$nivel_min} y tienes {$nivel}.");
    }
    $faccion = (string) ($req['faccion'] ?? '');
    if ($faccion !== '' && function_exists('ope7_pj_faccion_nombre') && ope7_pj_faccion_nombre($pid) !== $faccion) {
        return array('ok' => false, 'msg' => "Requisito no cumplido: la misión es de «{$faccion}».");
    }
    $oficios = (array) ($req['oficios'] ?? array());
    foreach ($oficios as $of) {
        $of = trim((string) $of);
        if ($of === '') {
            continue;
        }
        if (!ope7_tabla_existe('dominios_personaje') || !ope7_tabla_existe('dominios')) {
            return array('ok' => false, 'msg' => "Requisito no verificable (dominios sin migrar): «{$of}».");
        }
        $q = $db->query('SELECT COUNT(*) AS c FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
            . 'JOIN ' . ope7_tabla_full('dominios') . ' d ON d.id = dp.dominio_id '
            . "WHERE dp.personaje_id = {$pid} AND d.nombre = '" . $db->escape_string($of) . "'");
        if ((int) $db->fetch_field($q, 'c') < 1) {
            return array('ok' => false, 'msg' => "Requisito no cumplido: la misión pide el oficio «{$of}».");
        }
    }
    return array('ok' => true, 'msg' => 'Requisitos cumplidos.');
}

/** Tasa del tablón de la misión (5.9): berries que paga el solicitante. */
function ope7_mision_tasa(array $m)
{
    return (int) ($m['recompensas']['tasa'] ?? $m['requisitos']['tasa'] ?? 0);
}

/** Último tramo narrado de la misión (tramo, acto) o [0, 1]. */
function ope7_mision_ultimo_tramo($mision_id)
{
    global $db;
    $mision_id = (int) $mision_id;
    if ($mision_id < 1 || !ope7_tabla_existe('mision_tramos')) {
        return array('tramo' => 0, 'acto' => 1);
    }
    $q = $db->simple_select('ope_mision_tramos', 'tramo, acto', "mision_id = {$mision_id}", array('order_by' => 'tramo', 'order_dir' => 'DESC', 'limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? array('tramo' => (int) $r['tramo'], 'acto' => (int) $r['acto']) : array('tramo' => 0, 'acto' => 1);
}

/** Acto de un tramo según la duración en rondas (3 actos repartidos). */
function ope7_mision_acto_para($tramo, $duracion_rondas)
{
    $d = max(1, (int) $duracion_rondas);
    return min(3, 1 + (int) floor((max(1, (int) $tramo) - 1) * 3 / $d));
}

/**
 * Oráculos de la misión (5.16): el mismo motor de las travesías — banda del
 * IRT de la isla (nivel de mar + peligrosidad de la matriz 5.14) → incidentes
 * deterministas (sin dados). Si el staff propuso una lista, se valida contra
 * el catálogo y la banda. `$acto` 0 devuelve el plan completo con su acto.
 */
function ope7_mision_oraculos($isla_id, $acto, array $propuestos = array())
{
    $isla = ope7_isla_por_id((int) $isla_id);
    if (!$isla) {
        return array();
    }
    $mar = ope7_mar_por_id((int) ($isla['mar_id'] ?? 0));
    $base = $mar ? ope7_nav_nivel_mar($mar) : 1;
    $ficha = ope7_isla_ficha((int) $isla_id) ?: array();
    $pel = (int) ($ficha['peligrosidad'] ?? 0);
    $pel_add = $pel <= 10 ? 0 : ($pel <= 25 ? 1 : ($pel <= 40 ? 2 : 3));
    $oraculos = ope7_travesia_generar_oraculos($base + $pel_add, (array) $propuestos, '');
    $n = count($oraculos);
    foreach ($oraculos as $i => $o) {
        $o['acto'] = $n === 0 ? 1 : min(3, 1 + (int) floor($i * 3 / $n));
        $o['momento'] = 'Acto ' . (int) $o['acto'];
        $oraculos[$i] = $o;
    }
    if ((int) $acto < 1) {
        return $oraculos;
    }
    $out = array();
    foreach ($oraculos as $o) {
        if ((int) ($o['acto'] ?? 1) === (int) $acto) {
            $out[] = $o;
        }
    }
    return $out;
}

/** Render de la ficha de 6 bloques (para el panel y el prompt de la skill). */
function ope7_mision_ficha_html(array $m, $con_secretos = false)
{
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $ident = (array) ($m['identidad'] ?? array());
    $cond = (array) ($m['condiciones'] ?? array());
    $esc = (array) ($m['escenas'] ?? array());
    $rec = (array) ($m['recompensas'] ?? array());
    $req = (array) ($m['requisitos'] ?? array());
    $h = array();
    $h[] = '<div class="ms-ficha">';
    $h[] = '<div class="ms-f-h"><b>' . $e($ident['nombre'] ?? 'Sin nombre') . '</b>'
        . ' <span class="ms-chip">' . $e($ident['categoria'] ?? '?') . '</span>'
        . ($m['isla_id'] ? ' · isla #' . (int) $m['isla_id'] : '')
        . ' · ' . $e($ident['dificultad'] ?? 'dificultad ?')
        . ' · ' . (int) $m['duracion_rondas'] . ' ronda(s)</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Origen</span> ' . $e($ident['origen'] ?? '—') . '</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Victoria</span> ' . $e($cond['victoria'] ?? '—') . '</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Fracaso</span> ' . $e($cond['fracaso'] ?? '—') . '</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Acto 1</span> ' . $e($esc['acto1'] ?? '—') . '</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Acto 2</span> ' . $e($esc['acto2'] ?? '—') . '</div>';
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Acto 3</span> ' . $e($esc['acto3'] ?? '—') . '</div>';
    $rec_txt = array();
    foreach (array('berries' => 'berries', 'pp' => 'PP', 'fama' => 'fama', 'tasa' => 'tasa') as $k => $l) {
        if ((int) ($rec[$k] ?? 0) > 0) {
            $rec_txt[] = $l . ' ' . number_format((int) $rec[$k]);
        }
    }
    if (!empty($rec['objetos']) && is_array($rec['objetos'])) {
        $rec_txt[] = count($rec['objetos']) . ' objeto(s)';
    }
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Recompensas</span> ' . ($rec_txt ? $e(implode(' · ', $rec_txt)) : '—') . '</div>';
    $req_txt = array();
    if ((int) ($req['nivel_min'] ?? 0) > 0) {
        $req_txt[] = 'nv' . (int) $req['nivel_min'] . '+';
    }
    if (!empty($req['faccion'])) {
        $req_txt[] = (string) $req['faccion'];
    }
    if (!empty($req['oficios']) && is_array($req['oficios'])) {
        $req_txt[] = implode('/', $req['oficios']);
    }
    if ((int) ($req['grupo_min'] ?? 0) > 1) {
        $req_txt[] = 'grupo ' . (int) $req['grupo_min'];
    }
    $h[] = '<div class="ms-f-b"><span class="ms-lbl">Requisitos</span> ' . ($req_txt ? $e(implode(' · ', $req_txt)) : '—') . '</div>';
    if ($con_secretos) {
        $sec = (array) ($m['secretos_json'] ?? array());
        $sec_txt = (string) ($sec['texto'] ?? '');
        $h[] = '<div class="ms-f-b ms-secret"><span class="ms-lbl">Secretos (solo staff/narrador)</span> ' . ($sec_txt !== '' ? $e($sec_txt) : '—') . '</div>';
    }
    $h[] = '</div>';
    return implode("\n", $h);
}

/**
 * Efecto 52 · Solicitud de auto-narrada (5.20/21.3): valida ficha completa +
 * requisitos + tasa + un-presente, lanza oráculos del acto 1 (5.16), abre el
 * tema presente (aventura invadible) y crea el primer tramo con el texto de
 * la IA (editable) firmado por el staff.
 */
function ope7_efecto_apertura_mision($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    $mision_id = (int) ($ids['mision_id'] ?? $res['mision_id'] ?? 0);
    if ($pid < 1 || $mision_id < 1 || !ope7_tabla_existe('misiones')) {
        return 'Auto-narrada BLOQUEADA: faltan datos (personaje o misión).';
    }
    $m = ope7_mision_get($mision_id);
    if (!$m) {
        return 'Auto-narrada BLOQUEADA: la misión no existe.';
    }
    if ((string) $m['estado'] !== 'publicada' || !(int) $m['en_tablon']) {
        return 'Auto-narrada BLOQUEADA: la misión no está publicada en el tablón (5.20).';
    }
    $val = ope7_mision_ficha_valida($m);
    if (!$val['ok']) {
        return 'Auto-narrada BLOQUEADA: ' . $val['msg'];
    }
    $req = ope7_mision_cumple_requisitos($pid, $m);
    if (!$req['ok']) {
        return 'Auto-narrada BLOQUEADA: ' . $req['msg'];
    }
    if (ope7_pj_tiene_presente_abierto($pid)) {
        return 'Auto-narrada BLOQUEADA: el personaje ya tiene un tema presente abierto (un-presente, 5.6) — la misión es su presente.';
    }

    $notas = array();
    // Tasa del tablón (5.9): se paga de la cartera al solicitar.
    $tasa = ope7_mision_tasa($m);
    if ($tasa > 0) {
        $c = ope7_cartera_get($pid);
        if ((int) $c['cartera'] < $tasa) {
            return 'Auto-narrada BLOQUEADA: la tasa del tablón es ' . number_format($tasa) . ' ฿ y tienes ' . number_format((int) $c['cartera']) . ' en cartera.';
        }
        ope7_cartera_mover($pid, 'cartera', -$tasa);
        $notas[] = 'tasa −' . number_format($tasa) . ' ฿';
    }

    // Oráculos del acto 1 (5.16): plan completo guardado en la misión.
    $oraculos_plan = ope7_mision_oraculos((int) ($m['isla_id'] ?? 0), 0, (array) ($res['oraculos'] ?? array()));
    $oraculos_acto1 = array();
    foreach ($oraculos_plan as $o) {
        if ((int) ($o['acto'] ?? 1) === 1) {
            $oraculos_acto1[] = $o;
        }
    }

    // Abre el tema presente (5.6): aventura invadible.
    $nombre = (string) ($m['identidad']['nombre'] ?? 'Misión #' . $mision_id);
    $zona = $nombre . (isset($m['isla_id']) && $m['isla_id'] ? ' · isla #' . (int) $m['isla_id'] : '');
    ope7_efecto_apertura_tema($tr, $pid, array('tipo' => 'presente', 'tema_tipo' => 'aventura', 'zona' => $zona), array('tipo' => 'presente', 'tema_tipo' => 'aventura', 'zona' => $zona));
    $q = $db->query('SELECT tp.tema_id FROM ' . ope7_tabla_full('temas_participantes') . ' tp '
        . 'WHERE tp.personaje_id = ' . $pid . ' ORDER BY tp.id DESC LIMIT 1');
    $tema_id = (int) $db->fetch_field($q, 'tema_id');
    if ($tema_id < 1) {
        return 'Auto-narrada BLOQUEADA: no se pudo abrir el tema presente (5.6).';
    }

    // Misión en curso + participante + primer tramo (texto de la IA, firmado).
    $db->update_query('ope_misiones', array(
        'estado'        => 'en_curso',
        'tema_id'       => $tema_id,
        'solicitante_id'=> $pid,
        'abierta_en'    => TIME_NOW,
        'narrador_id'   => 0, // 0 = auto-narrada (21.5)
        'oraculos'      => json_encode($oraculos_plan, JSON_UNESCAPED_UNICODE),
    ), "id = {$mision_id}");
    $db->insert_query('ope_mision_participantes', array(
        'mision_id'    => $mision_id,
        'personaje_id' => $pid,
        'entrada'      => TIME_NOW,
        'salida'       => 0,
    ));
    $db->insert_query('ope_mision_tramos', array(
        'mision_id'         => $mision_id,
        'tramo'             => 1,
        'acto'              => 1,
        'oraculo_id'        => (int) ($oraculos_acto1[0]['oraculo_id'] ?? 0),
        'texto'             => (string) ($res['tramo_texto'] ?? ''),
        'posts_considerados'=> json_encode(array(), JSON_UNESCAPED_UNICODE),
        'firma_id'          => (int) ($tr['_staff_uid'] ?? 0),
        'fecha'             => TIME_NOW,
    ));

    $msg = 'Tema presente ' . $tema_id . ' abierto (aventura, 5.6) para «' . $nombre . '»'
        . ($notas ? ' · ' . implode(' · ', $notas) : '')
        . ' · oráculos del acto 1: ' . count($oraculos_acto1)
        . (count($oraculos_acto1) ? ' — ' . implode(', ', array_map(function ($o) {
            return (string) $o['tipo'] . ' [' . (string) $o['gravedad'] . ']';
        }, $oraculos_acto1)) : '')
        . ' · tramo 1 creado y firmado por el staff.'
        . ' El grupo postea en el tema y pide el siguiente tramo con el trámite 53.';
    return $msg;
}

/**
 * Efecto 53 · Posteo de tramo (5.20/21.3): recoge los posts de la ronda
 * (anti-abuso: la IA nunca adelanta un tramo sin ellos), lanza el oráculo
 * siguiente si el acto lo pide y crea el tramo con el texto firmado.
 */
function ope7_efecto_tramo_mision($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    $mision_id = (int) ($ids['mision_id'] ?? $res['mision_id'] ?? 0);
    if ($pid < 1 || $mision_id < 1 || !ope7_tabla_existe('misiones')) {
        return 'Tramo BLOQUEADO: faltan datos (personaje o misión).';
    }
    $m = ope7_mision_get($mision_id);
    if (!$m || (string) $m['estado'] !== 'en_curso') {
        return 'Tramo BLOQUEADO: la misión no está en curso.';
    }
    // Solo participa quien está en la misión (solicitante o participante).
    if ((int) ($m['solicitante_id'] ?? 0) !== $pid) {
        $q = $db->simple_select('ope_mision_participantes', 'COUNT(*) AS c', "mision_id = {$mision_id} AND personaje_id = {$pid}");
        if ((int) $db->fetch_field($q, 'c') < 1) {
            return 'Tramo BLOQUEADO: este personaje no participa en la misión.';
        }
    }
    // Tema vivo.
    if ((int) ($m['tema_id'] ?? 0) > 0 && ope7_tabla_existe('temas')) {
        $q = $db->simple_select('ope_temas', 'estado', 'tid = ' . (int) $m['tema_id'], array('limit' => 1));
        if ((string) $db->fetch_field($q, 'estado') === 'cerrado') {
            return 'Tramo BLOQUEADO: el tema de la misión está cerrado (la misión se cierra como abandonada, 5.20).';
        }
    }
    // Anti-abuso (21.3): sin los posts de la ronda no hay tramo.
    $posts = trim((string) ($res['posts'] ?? $ids['posts'] ?? ''));
    if ($posts === '') {
        return 'Tramo BLOQUEADO: sin los posts de la ronda no se adelanta un tramo (21.3, anti-abuso).';
    }

    $ult = ope7_mision_ultimo_tramo($mision_id);
    $tramo_nuevo = (int) $ult['tramo'] + 1;
    $acto_nuevo = ope7_mision_acto_para($tramo_nuevo, (int) $m['duracion_rondas']);

    // Oráculo siguiente si el acto lo pide (5.16): el primero del acto nuevo
    // que aún no se haya usado en tramos anteriores.
    $oraculo_id = 0;
    $usados = array();
    $q = $db->simple_select('ope_mision_tramos', 'oraculo_id', "mision_id = {$mision_id} AND oraculo_id > 0");
    while ($r = $db->fetch_array($q)) {
        $usados[(int) $r['oraculo_id']] = true;
    }
    foreach ((array) ($m['oraculos'] ?? array()) as $o) {
        if ((int) ($o['acto'] ?? 1) === $acto_nuevo && !isset($usados[(int) ($o['oraculo_id'] ?? 0)])) {
            $oraculo_id = (int) ($o['oraculo_id'] ?? 0);
            break;
        }
    }

    $db->insert_query('ope_mision_tramos', array(
        'mision_id'         => $mision_id,
        'tramo'             => $tramo_nuevo,
        'acto'              => $acto_nuevo,
        'oraculo_id'        => $oraculo_id,
        'texto'             => (string) ($res['tramo_texto'] ?? ''),
        'posts_considerados'=> json_encode(array('resumen' => $posts), JSON_UNESCAPED_UNICODE),
        'firma_id'          => (int) ($tr['_staff_uid'] ?? 0),
        'fecha'             => TIME_NOW,
    ));

    $msg = 'Tramo ' . $tramo_nuevo . ' (acto ' . $acto_nuevo . ') creado con el texto firmado'
        . ($oraculo_id > 0 ? ' · oráculo del acto aplicado (#' . $oraculo_id . ')' : '')
        . ' · posts de la ronda considerados.';
    if ($tramo_nuevo >= (int) $m['duracion_rondas']) {
        $msg .= ' Acto final alcanzado: el staff cierra con el trámite 55 (verifica condiciones y aplica recompensas).';
    }
    return $msg;
}

/**
 * Efecto 54 · Apertura de misión (staff, 5.20): publica la ficha de 6 bloques
 * en el tablón con condiciones explícitas y secretos solo-staff.
 */
function ope7_efecto_publicar_mision($tr, $pid, $res, $ids)
{
    global $db;
    if (!ope7_tabla_existe('misiones')) {
        return 'Apertura de misión: tabla no migrada (pendiente).';
    }
    $ident = (array) ($res['identidad'] ?? array());
    $cond = (array) ($res['condiciones'] ?? array());
    $esc = (array) ($res['escenas'] ?? array());
    $rec = (array) ($res['recompensas'] ?? array());
    $req = (array) ($res['requisitos'] ?? array());
    $sec = (array) ($res['secretos_json'] ?? array());
    $m = array(
        'identidad'     => $ident,
        'condiciones'   => $cond,
        'escenas'       => $esc,
        'recompensas'   => $rec,
        'requisitos'    => $req,
        'secretos_json' => $sec,
    );
    $val = ope7_mision_ficha_valida($m);
    if (!$val['ok']) {
        return 'Apertura de misión BLOQUEADA: ' . $val['msg'];
    }
    $categoria = (string) ($ident['categoria'] ?? 'reino_isla');
    if (!in_array($categoria, ope7_mision_categorias(), true)) {
        $categoria = 'reino_isla';
    }
    $isla_id = (int) ($res['isla_id'] ?? $ident['isla_id'] ?? 0);
    if ($isla_id > 0 && (!function_exists('ope7_isla_por_id') || !ope7_isla_por_id($isla_id))) {
        return 'Apertura de misión BLOQUEADA: la isla ' . $isla_id . ' no está en el catálogo (5.14).';
    }
    $duracion = max(1, min(12, (int) ($ident['duracion'] ?? $res['duracion_rondas'] ?? 3)));
    $mid = (int) $db->insert_query('ope_misiones', array(
        'categoria'      => $categoria,
        'origen'         => (string) ($ident['origen'] ?? ''),
        'isla_id'        => $isla_id > 0 ? $isla_id : 0,
        'dificultad'     => (string) ($ident['dificultad'] ?? ''),
        'duracion_rondas'=> $duracion,
        'identidad'      => json_encode($ident, JSON_UNESCAPED_UNICODE),
        'condiciones'    => json_encode($cond, JSON_UNESCAPED_UNICODE),
        'escenas'        => json_encode($esc, JSON_UNESCAPED_UNICODE),
        'recompensas'    => json_encode($rec, JSON_UNESCAPED_UNICODE),
        'requisitos'     => json_encode($req, JSON_UNESCAPED_UNICODE),
        'secretos_json'  => json_encode($sec, JSON_UNESCAPED_UNICODE),
        'estado'         => 'publicada',
        'en_tablon'      => 1,
    ));
    return 'Misión #' . $mid . ' publicada en el tablón: «' . (string) ($ident['nombre'] ?? '') . '»'
        . ' (' . $categoria . ' · ' . $duracion . ' ronda(s)) — ' . $val['msg']
        . ' Los jugadores la solicitan con el trámite 52 (auto-narrada).';
}

/**
 * Efecto 55 · Cierre de misión (staff, 5.20/21.3): verifica las condiciones
 * del acto final, aplica recompensas (berries/PP/fama/objetos) con motivo,
 * cierra el tema y deja el suceso de Mundo Vivo en borrador (5.14).
 */
function ope7_efecto_cierre_mision($tr, $pid, $res, $ids)
{
    global $db;
    $mision_id = (int) ($ids['mision_id'] ?? $res['mision_id'] ?? 0);
    if ($mision_id < 1 || !ope7_tabla_existe('misiones')) {
        return 'Cierre de misión BLOQUEADO: falta la misión.';
    }
    $m = ope7_mision_get($mision_id);
    if (!$m || (string) $m['estado'] !== 'en_curso') {
        return 'Cierre de misión BLOQUEADO: la misión no está en curso.';
    }
    $resultado = (string) ($res['resultado'] ?? '');
    if (!in_array($resultado, array('cumplida', 'fracasada', 'abandonada'), true)) {
        return 'Cierre de misión BLOQUEADO: indica el resultado (cumplida/fracasada/abandonada).';
    }
    // El acto final debe haberse alcanzado (salvo abandono anticipado).
    $ult = ope7_mision_ultimo_tramo($mision_id);
    $duracion = (int) $m['duracion_rondas'];
    if ($resultado !== 'abandonada' && (int) $ult['tramo'] < $duracion) {
        return 'Cierre de misión BLOQUEADO: el acto final no se ha alcanzado (tramo ' . (int) $ult['tramo'] . ' de ' . $duracion . ').';
    }
    // El motivo real es el de la FIRMA (el trámite 55 lo crea el staff en la
    // bandeja); el motivo de creación del trámite es solo el contexto inicial.
    $motivo = trim((string) ($res['motivo'] ?? $tr['_firma_motivo'] ?? $tr['motivo'] ?? ''));
    if ($motivo === '') {
        return 'Cierre de misión BLOQUEADO: se requiere un motivo escrito (queda en el histórico).';
    }

    $notas = array();
    $solicitante = (int) ($m['solicitante_id'] ?? 0);
    $rec = (array) ($res['recompensas'] ?? $m['recompensas'] ?? array());

    if ($resultado === 'cumplida' && $solicitante > 0) {
        // Berries (5.9) → cartera del solicitante (el grupo reparte como quiera).
        $berries = (int) ($rec['berries'] ?? 0);
        if ($berries > 0) {
            ope7_cartera_mover($solicitante, 'cartera', $berries);
            $notas[] = 'berries +' . number_format($berries) . ' ฿';
        }
        // PP (5.6) → saldo + histórico con motivo.
        $pp = (int) ($rec['pp'] ?? 0);
        if ($pp > 0 && ope7_tabla_existe('personajes')) {
            $q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$solicitante}", array('limit' => 1));
            $saldo = (int) $db->fetch_field($q, 'pp_saldo');
            $db->update_query('ope_personajes', array('pp_saldo' => $saldo + $pp), "id = {$solicitante}");
            if (ope7_tabla_existe('historico_pp')) {
                $db->insert_query('ope_historico_pp', array(
                    'personaje_id' => $solicitante,
                    'cantidad'     => $pp,
                    'concepto'     => 'Cierre de misión: ' . (string) ($m['identidad']['nombre'] ?? '#' . $mision_id),
                    'tramite_id'   => (int) ($tr['id'] ?? 0),
                    'fecha'        => TIME_NOW,
                ));
            }
            $notas[] = 'PP +' . $pp;
        }
        // Fama (5.12): expo de fama/infamia declarada en la ficha.
        $fama = (int) ($rec['fama'] ?? 0);
        if ($fama !== 0 && ope7_tabla_existe('personajes')) {
            $q = $db->simple_select('ope_personajes', 'fama_infamia_expo', "id = {$solicitante}", array('limit' => 1));
            $expo = (int) $db->fetch_field($q, 'fama_infamia_expo');
            $db->update_query('ope_personajes', array('fama_infamia_expo' => $expo + $fama), "id = {$solicitante}");
            $notas[] = ($fama > 0 ? 'fama +' : 'fama ') . $fama;
        }
        // Objetos (5.8) → mochila del solicitante.
        $objetos = (array) ($rec['objetos'] ?? array());
        foreach ($objetos as $obj) {
            $oid = (int) (is_array($obj) ? ($obj['objeto_id'] ?? $obj['id'] ?? 0) : $obj);
            $cant = max(1, (int) (is_array($obj) ? ($obj['cantidad'] ?? 1) : 1));
            if ($oid < 1 || !ope7_tabla_existe('inventario_personaje') || !ope7_tabla_existe('objetos')) {
                continue;
            }
            $q = $db->simple_select('ope_objetos', 'id', "id = {$oid}", array('limit' => 1));
            if (!$db->num_rows($q)) {
                $notas[] = 'objeto #' . $oid . ' no existe (no entregado)';
                continue;
            }
            $db->insert_query('ope_inventario_personaje', array(
                'personaje_id' => $solicitante,
                'objeto_id'    => $oid,
                'zona'         => 'mochila',
                'cantidad'     => $cant,
            ));
            $notas[] = 'objeto #' . $oid . ' ×' . $cant;
        }
    }

    // Condiciones verificadas (la IA propone, el staff firma con el motivo).
    $cond = (array) ($res['condiciones'] ?? array());
    if ($resultado === 'cumplida') {
        $notas[] = 'condiciones verificadas: ' . ((string) ($cond['victoria'] ?? '') !== '' ? 'victoria cumplida' : '—');
    } elseif ($resultado === 'fracasada') {
        $notas[] = 'condiciones verificadas: ' . ((string) ($cond['fracaso'] ?? '') !== '' ? 'fracaso consumado' : '—');
    } else {
        $notas[] = 'abandono: grupo fuera o plazo agotado (5.20, anti-abuso).';
    }

    // Cierra la misión con motivo e histórico.
    $db->update_query('ope_misiones', array(
        'estado'   => $resultado,
        'resultado'=> (string) ($res['resultado_txt'] ?? implode(' · ', $notas)),
        'motivo'   => $motivo,
    ), "id = {$mision_id}");
    // Participantes: salida (para reparto de fama/PP).
    if (ope7_tabla_existe('mision_participantes')) {
        $db->update_query('ope_mision_participantes', array('salida' => TIME_NOW), "mision_id = {$mision_id} AND salida = 0");
    }
    // Cierra el tema presente y libera el un-presente (5.6).
    $tema_id = (int) ($m['tema_id'] ?? 0);
    if ($tema_id > 0 && ope7_tabla_existe('temas')) {
        $db->update_query('ope_temas', array('estado' => 'cerrado'), "tid = {$tema_id}");
        if (ope7_tabla_existe('temas_participantes')) {
            $db->update_query('ope_temas_participantes', array('salio_en' => TIME_NOW), "tema_id = {$tema_id}");
        }
        // D1.8: cierra también el hilo real del foro si estaba vinculado.
        if (function_exists('ope7_tema_cerrar_mybb')) {
            ope7_tema_cerrar_mybb($tema_id);
        }
    }
    // Suceso de Mundo Vivo en borrador (5.14): el staff lo publica desde el panel.
    $suceso_id = 0;
    if (ope7_tabla_existe('sucesos')) {
        $suceso_id = (int) $db->insert_query('ope_sucesos', array(
            'isla_id'     => (int) ($m['isla_id'] ?? 0),
            'ronda'       => 0,
            'tipo'        => 'mision',
            'titulo'      => 'Misión ' . ($resultado === 'cumplida' ? 'cumplida' : ($resultado === 'fracasada' ? 'fracasada' : 'abandonada')) . ': ' . (string) ($m['identidad']['nombre'] ?? '#' . $mision_id),
            'descripcion' => $motivo . ($notas ? ' · ' . implode(' · ', $notas) : ''),
            'activo'      => 0,
        ));
    }

    return 'Misión #' . $mision_id . ' ' . $resultado . ' con motivo (histórico).'
        . ($notas ? ' ' . implode(' · ', $notas) : '')
        . ($suceso_id > 0 ? ' · suceso de Mundo Vivo en borrador (#' . $suceso_id . ', publícalo desde el panel).' : '');
}

/**
 * Cron de ronda (5.20): misión en curso cuyo tema se cerró sin cierre de
 * misión → abandonada con motivo (el grupo abandonó; el mundo lo nota).
 * Idempotente; integrado en ope7_progresion_cron.
 */
function ope7_misiones_ronda_cerrar()
{
    global $db;
    if (!ope7_tabla_existe('misiones') || !ope7_tabla_existe('temas')) {
        return 0;
    }
    $q = $db->query('SELECT m.id, m.tema_id, m.identidad FROM ' . ope7_tabla_full('misiones') . ' m '
        . 'JOIN ' . ope7_tabla_full('temas') . ' t ON t.tid = m.tema_id '
        . "WHERE m.estado = 'en_curso' AND m.tema_id > 0 AND t.estado = 'cerrado' LIMIT 50");
    $n = 0;
    while ($r = $db->fetch_array($q)) {
        $ident = json_decode((string) ($r['identidad'] ?? '{}'), true);
        $nombre = (string) ($ident['nombre'] ?? '#' . $r['id']);
        $db->update_query('ope_misiones', array(
            'estado'   => 'abandonada',
            'resultado'=> 'Tema cerrado sin cierre de misión: el grupo abandonó.',
            'motivo'   => 'Cierre automático de ronda (5.20): la misión era un tema presente y se cerró sin veredicto.',
        ), 'id = ' . (int) $r['id']);
        if (ope7_tabla_existe('mision_participantes')) {
            $db->update_query('ope_mision_participantes', array('salida' => TIME_NOW), 'mision_id = ' . (int) $r['id'] . ' AND salida = 0');
        }
        if (ope7_tabla_existe('sucesos')) {
            $db->insert_query('ope_sucesos', array(
                'isla_id'     => 0,
                'ronda'       => 0,
                'tipo'        => 'mision',
                'titulo'      => 'Misión abandonada: ' . $nombre,
                'descripcion' => 'Cierre automático de ronda: el grupo abandonó la misión sin veredicto (5.20).',
                'activo'      => 0,
            ));
        }
        $n++;
    }
    return $n;
}

/** Publica un suceso de misión en borrador (panel staff). */
function ope7_mision_suceso_publicar($suceso_id)
{
    global $db;
    $suceso_id = (int) $suceso_id;
    if ($suceso_id < 1 || !ope7_tabla_existe('sucesos')) {
        return false;
    }
    $db->update_query('ope_sucesos', array('activo' => 1), "id = {$suceso_id} AND tipo = 'mision'");
    return true;
}

/**
 * Panel staff «Narradores y Misiones» (Anexo A.3, 5.20): tablón CRUD de la
 * ficha de 6 bloques (secretos solo staff/narradores), narradores con cupo de
 * 2 simultáneas, auto-narradas en curso con tramos/oráculos, histórico de
 * cierres y sucesos en borrador. Devuelve HTML sin <style> ni estilos inline.
 */
function ope7_misiones_panel_html()
{
    global $db, $mybb;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $uid = (int) ($mybb->user['uid'] ?? 0);
    $es_staff = ope7_es_staff($uid);
    $con_secretos = ope7_es_staff_o_narrador($uid);
    $pk = $mybb->get_input('my_post_key');
    $h = array();

    $h[] = '<div class="shead"><h1>Narradores y Misiones</h1><span class="code">A.3 · 5.20/21</span><span class="rule"></span></div>';
    $h[] = '<p class="zs-intro">El tablón es <b>solo-staff</b>: las misiones nacen del análisis de ronda (5.14) y tú decides qué se publica. '
        . 'La auto-narrada (52–55) exige ficha completa con <b>condiciones de victoria/fracaso explícitas</b>; la IA narra por rondas (skill-narracion-automatica), '
        . 'tú firmas cada tramo y el cierre aplica recompensas con motivo. Los <b>secretos</b> solo los ven staff y narradores habilitados (21.2).</p>';

    // ── Tablón de misiones (CRUD solo-staff) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Tablón de misiones</span><span class="c">ficha de 6 bloques · solo-staff</span></div><div class="plate-b">';
    if (ope7_tabla_existe('misiones')) {
        $q = $db->query('SELECT m.*, i.nombre AS isla_nombre FROM ' . ope7_tabla_full('misiones') . ' m '
            . 'LEFT JOIN ' . ope7_tabla_full('islas') . ' i ON i.id = m.isla_id '
            . "WHERE m.estado IN ('publicada','borrador') ORDER BY m.id DESC LIMIT 30");
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Tablón vacío: crea la primera misión con el formulario de abajo o con el trámite 54 (la IA propone la ficha, tú la firmas).</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $m = ope7_mision_get((int) $r['id']);
                $h[] = '<div class="zs-row"><div class="ms-grow">'
                    . $e((string) ($m['identidad']['nombre'] ?? '#' . $r['id']))
                    . ' <span class="ms-chip">' . $e((string) $r['estado']) . '</span>'
                    . ($r['isla_nombre'] ? ' · ' . $e((string) $r['isla_nombre']) : '')
                    . ' · ' . (int) $r['duracion_rondas'] . ' ronda(s)'
                    . '<div class="zs-mut">' . $e((string) ($m['condiciones']['victoria'] ?? '—')) . '</div>'
                    . ($con_secretos ? '<div class="ms-secret">🔒 ' . $e((string) ($m['secretos_json']['texto'] ?? 'sin secretos')) . '</div>' : '')
                    . '</div>'
                    . '<div class="ms-acc">'
                    . ((string) $r['estado'] === 'borrador' && $es_staff
                        ? '<form method="post" action="misiones-staff.php"><input type="hidden" name="my_post_key" value="' . $e($pk) . '">'
                            . '<input type="hidden" name="gaccion" value="publicar_mision"><input type="hidden" name="mision_id" value="' . (int) $r['id'] . '">'
                            . '<button class="ope-btn" type="submit">Publicar</button></form>' : '')
                    . ((string) $r['estado'] === 'publicada' && $es_staff
                        ? '<form method="post" action="misiones-staff.php"><input type="hidden" name="my_post_key" value="' . $e($pk) . '">'
                            . '<input type="hidden" name="gaccion" value="archivar_mision"><input type="hidden" name="mision_id" value="' . (int) $r['id'] . '">'
                            . '<button class="ope-btn" type="submit">Archivar</button></form>' : '')
                    . '</div></div>';
            }
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de misiones no migrada.</p>';
    }

    // Formulario de creación (staff): ficha de 6 bloques.
    if ($es_staff) {
        $islas = array();
        if (ope7_tabla_existe('islas')) {
            $iq = $db->simple_select('ope_islas', 'id, nombre', '1=1', array('order_by' => 'mar_id, nombre'));
            while ($ir = $db->fetch_array($iq)) {
                $islas[] = $ir;
            }
        }
        $h[] = '<details class="ms-form"><summary>+ Crear misión (borrador) — ficha de 6 bloques</summary>'
            . '<form method="post" action="misiones-staff.php">'
            . '<input type="hidden" name="my_post_key" value="' . $e($pk) . '"><input type="hidden" name="gaccion" value="crear_mision">'
            . '<div class="ms-grid">'
            . '<label>Nombre <input type="text" name="nombre" required></label>'
            . '<label>Categoría <select name="categoria"><option value="faccion">Facción</option><option value="reino_isla" selected>Reino-Isla</option><option value="profesional">Profesional</option><option value="bajo_mundo">Bajo mundo</option><option value="especial">Especial</option></select></label>'
            . '<label>Origen (quién la publica) <input type="text" name="origen"></label>'
            . '<label>Isla <select name="isla_id"><option value="0">— sin isla —</option>'
            . implode('', array_map(function ($i) use ($e) { return '<option value="' . (int) $i['id'] . '">' . $e((string) $i['nombre']) . '</option>'; }, $islas))
            . '</select></label>'
            . '<label>Dificultad (banda de nivel) <input type="text" name="dificultad" placeholder="Ej. nv1–10"></label>'
            . '<label>Duración (rondas) <input type="number" name="duracion_rondas" value="3" min="1" max="12"></label>'
            . '</div>'
            . '<label>Condición de VICTORIA (explícita) <textarea name="cond_victoria" required></textarea></label>'
            . '<label>Condición de FRACASO (explícita) <textarea name="cond_fracaso" required></textarea></label>'
            . '<label>Escenas en 3 actos (comienzo · medio · final, con NPCs) <textarea name="escenas" required placeholder="Acto 1: …&#10;Acto 2: …&#10;Acto 3: …"></textarea></label>'
            . '<label>Recompensas (JSON: berries/pp/fama/objetos/tasa) <textarea name="recompensas" placeholder=\'{"berries":50000,"pp":120,"fama":5,"tasa":2000,"objetos":[{"objeto_id":1,"cantidad":1}]}\'></textarea></label>'
            . '<label>Requisitos (JSON: nivel_min/faccion/oficios/grupo_min) <textarea name="requisitos" placeholder=\'{"nivel_min":5,"faccion":"","oficios":[],"grupo_min":1}\'></textarea></label>'
            . '<label class="ms-secret">Secretos solo-staff <textarea name="secretos" placeholder="El giro que solo conocen staff y narradores…"></textarea></label>'
            . '<button class="ope-btn" type="submit">Guardar borrador</button></form></details>';
    }
    $h[] = '</div></div>';

    // ── Narradores (rol de foro, cupo de 2 simultáneas, historial) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Narradores</span><span class="c">rol de foro · cupo 2 simultáneas (21.2)</span></div><div class="plate-b">';
    $narr = array();
    // El rol de narrador es por PERSONAJE (staff_narrador, independiente del
    // staff_level — 21.2). F6.3: fuente canónica mybb_ope_cuentas; si aún
    // existe el legado rol_cuentas, se consulta como complemento.
    if (ope7_tabla_existe('misiones') && ope7_tabla_existe('cuentas') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT c.uid, c.personaje_activo, p.nombre AS pj_nombre, u.username '
            . 'FROM ' . ope7_tabla_full('cuentas') . ' c '
            . 'LEFT JOIN ' . TABLE_PREFIX . 'users u ON u.uid = c.uid '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = c.personaje_activo '
            . "WHERE c.staff_narrador = 1 OR c.staff_level >= 1 ORDER BY u.username");
        while ($r = $db->fetch_array($q)) {
            $narr[] = $r;
        }
    } elseif (ope7_tabla_existe('misiones') && $db->table_exists('rol_cuentas') && $db->table_exists('rol_personajes')) {
        $q = $db->query('SELECT rc.uid, rc.personaje_activo, COALESCE(p.nombre, rp.nombre) AS pj_nombre, u.username '
            . 'FROM ' . TABLE_PREFIX . 'rol_cuentas rc '
            . 'LEFT JOIN ' . TABLE_PREFIX . 'users u ON u.uid = rc.uid '
            . 'LEFT JOIN ' . TABLE_PREFIX . 'rol_personajes rp ON rp.uid = rc.uid AND rp.activo = 1 '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = rc.personaje_activo '
            . "WHERE rp.staff_narrador = 1 OR rc.staff_level >= 1 ORDER BY u.username");
        while ($r = $db->fetch_array($q)) {
            $narr[] = $r;
        }
    }
    if (!$narr) {
        $h[] = '<p class="pj-empty">Sin narradores habilitados (staff_narrador en rol_personajes, 21.2) ni staff con personaje activo.</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Narrador</th><th>Personaje</th><th>Aventuras activas</th><th>Cupo</th></tr></thead><tbody>';
        foreach ($narr as $r) {
            $activas = 0;
            if (ope7_tabla_existe('misiones')) {
                $aq = $db->simple_select('ope_misiones', 'COUNT(*) AS c', "estado = 'en_curso' AND narrador_id = " . (int) $r['uid']);
                $activas = (int) $db->fetch_field($aq, 'c');
            }
            $h[] = '<tr><td>' . $e((string) $r['username']) . '</td>'
                . '<td>' . $e((string) ($r['pj_nombre'] ?? '—')) . '</td>'
                . '<td>' . $activas . '</td>'
                . '<td>' . ($activas >= 2 ? '<b class="zs-ok">cupo lleno — redirige a auto-narrada (21.2)</b>' : 'libre (' . (2 - $activas) . ')') . '</td></tr>';
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '<p class="zs-mut">Las auto-narradas no ocupan cupo de narrador (narrador_id NULL): las cuenta el sistema por rondas.</p>';
    $h[] = '</div></div>';

    // ── Auto-narradas en curso ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Auto-narradas en curso</span><span class="c">tema presente · tramos por rondas</span></div><div class="plate-b">';
    $curso = array();
    if (ope7_tabla_existe('misiones') && ope7_tabla_existe('temas')) {
        $q = $db->query('SELECT m.*, p.nombre AS pj_nombre, t.estado AS tema_estado '
            . 'FROM ' . ope7_tabla_full('misiones') . ' m '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = m.solicitante_id '
            . 'LEFT JOIN ' . ope7_tabla_full('temas') . ' t ON t.tid = m.tema_id '
            . "WHERE m.estado = 'en_curso' ORDER BY m.id DESC LIMIT 30");
        while ($r = $db->fetch_array($q)) {
            $curso[] = $r;
        }
    }
    if (!$curso) {
        $h[] = '<p class="pj-empty">Sin auto-narradas en curso (los jugadores las piden con el trámite 52).</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Misión</th><th>Solicitante</th><th>Tema</th><th>Tramo/Acto</th><th>Oráculos</th><th>Acciones</th></tr></thead><tbody>';
        foreach ($curso as $r) {
            $m = ope7_mision_get((int) $r['id']);
            $ult = ope7_mision_ultimo_tramo((int) $r['id']);
            $orac = (array) ($m['oraculos'] ?? array());
            $orac_txt = array();
            foreach ($orac as $o) {
                $orac_txt[] = (string) $o['tipo'] . ' [A' . (int) ($o['acto'] ?? 1) . ']';
            }
            $h[] = '<tr><td><b>' . $e((string) ($m['identidad']['nombre'] ?? '#' . $r['id'])) . '</b></td>'
                . '<td>' . $e((string) ($r['pj_nombre'] ?? '—')) . '</td>'
                . '<td>#' . (int) $r['tema_id'] . ' <span class="zs-mut">(' . $e((string) $r['tema_estado']) . ')</span></td>'
                . '<td>' . (int) $ult['tramo'] . ' / ' . (int) $r['duracion_rondas'] . ' · acto ' . (int) $ult['acto'] . '</td>'
                . '<td>' . ($orac_txt ? $e(implode(', ', $orac_txt)) : '—') . '</td>'
                . '<td>' . ($es_staff
                    ? '<form method="post" action="misiones-staff.php"><input type="hidden" name="my_post_key" value="' . $e($pk) . '">'
                        . '<input type="hidden" name="gaccion" value="cerrar_mision"><input type="hidden" name="mision_id" value="' . (int) $r['id'] . '">'
                        . '<button class="ope-btn" type="submit">Cerrar (55)</button></form>'
                    : '<span class="zs-mut">el jugador pide el tramo con el 53</span>') . '</td></tr>';
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    // ── Histórico de misiones cerradas ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Histórico de misiones</span><span class="c">cumplida · fracasada · abandonada</span></div><div class="plate-b">';
    $hist = array();
    if (ope7_tabla_existe('misiones')) {
        $q = $db->query('SELECT m.* FROM ' . ope7_tabla_full('misiones') . ' m '
            . "WHERE m.estado IN ('cumplida','fracasada','abandonada') ORDER BY m.id DESC LIMIT 15");
        while ($r = $db->fetch_array($q)) {
            $hist[] = $r;
        }
    }
    if (!$hist) {
        $h[] = '<p class="pj-empty">Sin misiones cerradas todavía.</p>';
    } else {
        foreach ($hist as $r) {
            $m = ope7_mision_get((int) $r['id']);
            $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) ($m['identidad']['nombre'] ?? '#' . $r['id'])) . '</b>'
                . ' · <span class="' . ((string) $r['estado'] === 'cumplida' ? 'zs-ok' : 'zs-mut') . '">' . $e((string) $r['estado']) . '</span>'
                . '<div class="zs-mut">' . $e((string) $r['motivo']) . '</div>'
                . ((string) $r['resultado'] !== '' ? '<div class="zs-mut">' . $e((string) $r['resultado']) . '</div>' : '')
                . '</div></div>';
        }
    }
    $h[] = '</div></div>';

    // ── Sucesos de misión en borrador (5.14) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Sucesos de misión</span><span class="c">borrador · publícalos aquí (5.14)</span></div><div class="plate-b">';
    if (ope7_tabla_existe('sucesos')) {
        $q = $db->simple_select('ope_sucesos', '*', "tipo = 'mision' AND activo = 0", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 10));
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin sucesos de misión en borrador.</p>';
        } else {
            while ($r = $db->fetch_array($q)) {
                $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) $r['titulo']) . '</b>'
                    . '<div class="zs-mut">' . $e((string) $r['descripcion']) . '</div></div>'
                    . ($es_staff
                        ? '<form method="post" action="misiones-staff.php"><input type="hidden" name="my_post_key" value="' . $e($pk) . '">'
                            . '<input type="hidden" name="gaccion" value="publicar_suceso"><input type="hidden" name="suceso_id" value="' . (int) $r['id'] . '">'
                            . '<button class="ope-btn" type="submit">Publicar</button></form>'
                        : '') . '</div>';
            }
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de sucesos no migrada.</p>';
    }
    $h[] = '</div></div>';

    return implode("\n", $h);
}