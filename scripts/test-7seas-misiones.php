<?php
/**
 * One Piece: 7 Seas · Test F5.2 — Narradores y auto-narradas (5.20/cap. 21)
 * ------------------------------------------------------------------------
 * Verifica:
 *  · 54 — Apertura de misión (staff): ficha de 6 bloques con condiciones
 *    de victoria/fracaso explícitas; sin ellas → rechazada (21.2).
 *  · 52 — Solicitud de auto-narrada: valida ficha + requisitos + un-presente,
 *    abre el tema presente (aventura invadible, 5.6), crea el primer tramo,
 *    registra al participante y descuenta la tasa del tablón (5.9).
 *  · 53 — Posteo de tramo: sin posts de la ronda → bloqueado (anti-abuso
 *    21.3); con posts → tramo siguiente con oráculos del acto (5.16).
 *  · 55 — Cierre de misión: acto final obligatorio, recompensas con motivo
 *    (berries/PP/fama/objetos), tema cerrado, suceso de Mundo Vivo en
 *    borrador (5.14) y participante con salida.
 *  · Cron: misión en curso cuyo tema se cerró → abandonada con motivo.
 *  · Panel staff «Narradores y Misiones» (A.3) sin estilos inline.
 * Idempotente: limpieza completa al final (PJ, misiones, tramos, temas).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-misiones.php');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/ope_rol/bootstrap.php';

global $db;
$G = array('ok' => 0, 'fail' => 0);
$G['chk'] = function ($label, $cond) use (&$G) {
    if ($cond) {
        $G['ok']++;
        echo "  OK — {$label}\n";
    } else {
        $G['fail']++;
        echo "  FALLO — {$label}\n";
    }
};

$mk_pj = function ($slug, $nivel = 3, $pp = 0) use ($db) {
    static $raza = null;
    if ($raza === null) {
        $raza = (int) $db->fetch_field($db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1)), 'id');
    }
    $pid = ope7_pj_guardar(array(
        'uid' => 1, 'nombre' => 'Prueba Mis ' . $slug, 'slug' => 'prueba-mis-' . $slug, 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => (int) $nivel, 'raza_id' => $raza,
        'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
        'puntos_comprados' => 0, 'pp_saldo' => (int) $pp,
    ));
    ope7_cartera_mover($pid, 'cartera', 10000);
    return $pid;
};

// ── Limpieza previa idempotente ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-mis-%'");
$db->delete_query('ope_misiones', "origen = 'test F5.2'");
$db->delete_query('ope_mision_tramos', 'mision_id NOT IN (SELECT id FROM mybb_ope_misiones)');
$db->delete_query('ope_mision_participantes', 'mision_id NOT IN (SELECT id FROM mybb_ope_misiones)');
$db->delete_query('ope_tramites', 'numero IN (52,53,54,55) AND solicitante_id = 1');
$db->delete_query('ope_sucesos', "tipo = 'mision'");
$db->delete_query('ope_temas', "zona LIKE 'Misión de prueba%' OR zona LIKE 'Misión exigente%' OR zona LIKE 'Misión segunda%' OR zona LIKE 'Misión larga%' OR zona LIKE 'Misión abandonada%'");
$db->delete_query('ope_temas_participantes', 'tema_id NOT IN (SELECT tid FROM mybb_ope_temas)');

$oid = (int) $db->fetch_field($db->simple_select('ope_objetos', 'id', '1=1', array('limit' => 1)), 'id');

/** Crea y publica una misión vía el trámite 54 (staff). Devuelve mid. */
$crear_mision = function ($nombre, array $extra = array()) use ($db) {
    $ficha = array_merge(array(
        'identidad' => array('nombre' => $nombre, 'categoria' => 'reino_isla', 'origen' => 'test F5.2', 'dificultad' => 'nv1-10', 'duracion' => 2),
        'condiciones' => array('victoria' => 'El tesoro llega antes del amanecer de la 2ª ronda.', 'fracaso' => 'Los bandidos ejecutan al rehén o el grupo abandona la isla.'),
        'escenas' => array('acto1' => 'El grupo llega a la aldea.', 'acto2' => 'El encuentro con los bandidos.', 'acto3' => 'El rescate del rehén.'),
        'recompensas' => array('berries' => 50000, 'pp' => 120, 'fama' => 5, 'tasa' => 0, 'objetos' => array()),
        'requisitos' => array('nivel_min' => 1, 'faccion' => '', 'oficios' => array(), 'grupo_min' => 1),
        'secretos_json' => array('texto' => 'El rehén es el villano disfrazado.'),
        'isla_id' => 0,
    ), $extra);
    $r = ope7_tramite_crear(1, 0, 54, 'test F5.2', array('concepto' => $nombre), $ficha);
    if (!$r['ok']) {
        return array('ok' => false, 'msg' => $r['msg']);
    }
    $f = ope7_tramite_firmar((int) $r['tid'], 1, 'publicar', 'Publicar misión de prueba');
    if (!$f['ok']) {
        return array('ok' => false, 'msg' => $f['msg']);
    }
    $q = $db->simple_select('ope_misiones', 'id', "origen = 'test F5.2' AND estado = 'publicada' ORDER BY id DESC", array('limit' => 1));
    return array('ok' => true, 'mid' => (int) $db->fetch_field($q, 'id'));
};

/** Solicita una auto-narrada (52) y firma el tramo inicial. */
$solicitar = function ($pid, $mid) use ($db) {
    $r = ope7_tramite_crear(1, $pid, 52, 'Aceptamos la misión', array('mision_id' => $mid, 'mision' => 'Misión de prueba'));
    if (!$r['ok']) {
        return $r;
    }
    $g = ope7_tramite_guardar_resultado((int) $r['tid'], array('tramo_texto' => 'Tramo inicial narrado por la IA.'));
    if (!$g['ok']) {
        return $g;
    }
    return ope7_tramite_firmar((int) $r['tid'], 1, 'publicar', 'Firmo el tramo inicial');
};

// ── [1] 54 · Apertura de misión (tablón solo-staff) ──
$creada = $crear_mision('Misión de prueba');
$G['chk']('[54] Publicar misión con ficha completa', $creada['ok']);
$mid = (int) ($creada['mid'] ?? 0);
$m = ope7_mision_get($mid);
$G['chk']('[54] Ficha de 6 bloques con condiciones explícitas', $m && trim((string) $m['condiciones']['victoria']) !== '' && trim((string) $m['condiciones']['fracaso']) !== '');
$G['chk']('[54] En el tablón (publicada, en_tablon=1)', $m && (string) $m['estado'] === 'publicada' && (int) $m['en_tablon'] === 1);
$G['chk']('[54] Secretos solo-staff guardados', $m && trim((string) $m['secretos_json']['texto']) !== '');

// Sin condiciones explícitas → la validación dura la rechaza (21.2).
$r = ope7_tramite_crear(1, 0, 54, 'test', array('concepto' => 'Mala'), array(
    'identidad' => array('nombre' => 'Mala', 'categoria' => 'faccion', 'origen' => 'test F5.2'),
    'condiciones' => array('victoria' => '', 'fracaso' => ''),
    'escenas' => array('acto1' => 'a', 'acto2' => 'b', 'acto3' => 'c'),
));
$f = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'x');
$G['chk']('[54] Sin condiciones → rechazada (BLOQUEADO)', !$f['ok'] && stripos((string) ($f['msg'] ?? ''), 'BLOQUEADA') !== false);

// ── [2] 52 · Solicitud de auto-narrada ──
$pid = $mk_pj('solicitante', 3, 100);
$s = $solicitar($pid, $mid);
$G['chk']('[52] Solicitud firmada → misión en curso', $s['ok']);
$m = ope7_mision_get($mid);
$G['chk']('[52] Estado en_curso + tema + solicitante', $m && (string) $m['estado'] === 'en_curso' && (int) $m['tema_id'] > 0 && (int) $m['solicitante_id'] === (int) $pid);
$q = $db->simple_select('ope_temas', 'tema_tipo, invadible', 'tid = ' . (int) ($m['tema_id'] ?? 0), array('limit' => 1));
$tema = $db->fetch_array($q);
$G['chk']('[52] Tema presente aventura invadible (5.6)', $tema && (string) $tema['tema_tipo'] === 'aventura' && (int) $tema['invadible'] === 1);
$q = $db->simple_select('ope_mision_tramos', 'COUNT(*) AS c', "mision_id = {$mid} AND tramo = 1");
$G['chk']('[52] Primer tramo creado', (int) $db->fetch_field($q, 'c') === 1);
$q = $db->simple_select('ope_mision_participantes', 'COUNT(*) AS c', "mision_id = {$mid} AND personaje_id = {$pid}");
$G['chk']('[52] Participante registrado', (int) $db->fetch_field($q, 'c') === 1);
$G['chk']('[52] Un-presente activo tras abrir', ope7_pj_tiene_presente_abierto($pid) === true);

// Requisitos duros (21.2.5): nivel mínimo no cumplido → rechazada.
$creada2 = $crear_mision('Misión exigente', array('requisitos' => array('nivel_min' => 10)));
$pid2 = $mk_pj('novato', 3);
$s2 = $solicitar($pid2, (int) ($creada2['mid'] ?? 0));
$G['chk']('[52] Requisito de nivel no cumplido → rechazada', !$s2['ok'] && stripos((string) ($s2['msg'] ?? ''), 'BLOQUEADA') !== false);

// Un-presente (5.6): el mismo PJ no abre una segunda misión.
$creada3 = $crear_mision('Misión segunda');
$s3 = $solicitar($pid, (int) ($creada3['mid'] ?? 0));
$G['chk']('[52] Un-presente: segunda misión del mismo PJ → rechazada', !$s3['ok'] && stripos((string) ($s3['msg'] ?? ''), 'BLOQUEADA') !== false);

// ── [3] 53 · Posteo de tramo ──
$r = ope7_tramite_crear(1, $pid, 53, 'posts', array('mision_id' => $mid, 'mision' => 'Misión de prueba', 'posts' => ''));
$g = ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('tramo_texto' => 'x', 'posts' => ''));
$f = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'x');
$G['chk']('[53] Sin posts de la ronda → bloqueado (anti-abuso 21.3)', !$f['ok'] && stripos((string) ($f['msg'] ?? ''), 'BLOQUEADO') !== false);

$r = ope7_tramite_crear(1, $pid, 53, 'posts', array('mision_id' => $mid, 'mision' => 'Misión de prueba', 'posts' => 'El grupo llegó a la aldea y negoció el paso.'));
$g = ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('tramo_texto' => 'Tramo 2 narrado.', 'posts' => 'El grupo llegó a la aldea y negoció el paso.'));
$f = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'x');
$G['chk']('[53] Con posts → tramo 2 creado', $f['ok']);
$q = $db->simple_select('ope_mision_tramos', 'COUNT(*) AS c', "mision_id = {$mid} AND tramo = 2");
$G['chk']('[53] Tramo 2 en el histórico', (int) $db->fetch_field($q, 'c') === 1);

// ── [4] 55 · Cierre de misión ──
// Cierre anticipado: misión de 3 rondas con solo el tramo 1 → bloqueado.
$creada4 = $crear_mision('Misión larga', array(
    'identidad' => array('nombre' => 'Misión larga', 'categoria' => 'especial', 'origen' => 'test F5.2', 'dificultad' => 'nv1-10', 'duracion' => 3),
));
$pid3 = $mk_pj('largo', 5);
$s4 = $solicitar($pid3, (int) ($creada4['mid'] ?? 0));
$G['chk']('[55] Misión de 3 rondas en curso', $s4['ok']);
$r = ope7_tramite_crear(1, $pid3, 55, 'cierre', array('mision_id' => (int) ($creada4['mid'] ?? 0), 'mision' => 'Misión larga'));
$g = ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('resultado' => 'cumplida', 'motivo' => 'x'));
$f = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'x');
$G['chk']('[55] Cierre anticipado (tramo 1 de 3) → bloqueado', !$f['ok'] && stripos((string) ($f['msg'] ?? ''), 'BLOQUEADO') !== false);

// Cierre cumplida de la misión de 2 rondas (acto final alcanzado).
$r = ope7_tramite_crear(1, $pid, 55, 'cierre', array('mision_id' => $mid, 'mision' => 'Misión de prueba'));
$g = ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array(
    'resultado' => 'cumplida',
    'condiciones' => array('victoria' => 'El tesoro llegó a tiempo.'),
    'recompensas' => array('berries' => 50000, 'pp' => 120, 'fama' => 5, 'objetos' => array(array('objeto_id' => $oid, 'cantidad' => 1))),
    'resultado_txt' => 'El tesoro llegó antes del amanecer.',
    'motivo' => 'Condiciones de victoria verificadas contra lo roleado.',
));
$f = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Cierre firmado');
$G['chk']('[55] Cierre cumplida firmado', $f['ok']);
$m = ope7_mision_get($mid);
$G['chk']('[55] Misión cumplida con motivo', $m && (string) $m['estado'] === 'cumplida' && trim((string) $m['motivo']) !== '');
$q = $db->simple_select('ope_temas', 'estado', 'tid = ' . (int) ($m['tema_id'] ?? 0), array('limit' => 1));
$G['chk']('[55] Tema cerrado (libera el presente)', (string) $db->fetch_field($q, 'estado') === 'cerrado');
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
$G['chk']('[55] PP aplicados (+120 al saldo)', (int) $db->fetch_field($q, 'pp_saldo') === 220);
$c = ope7_cartera_get($pid);
$G['chk']('[55] Berries a cartera (+50.000)', (int) $c['cartera'] === 60000);
$q = $db->query('SELECT COUNT(*) AS c FROM ' . ope7_tabla_full('inventario_personaje') . " WHERE personaje_id = {$pid} AND objeto_id = {$oid}");
$G['chk']('[55] Objeto entregado a la mochila', (int) $db->fetch_field($q, 'c') === 1);
$q = $db->simple_select('ope_sucesos', 'COUNT(*) AS c', "tipo = 'mision' AND activo = 0");
$G['chk']('[55] Suceso de Mundo Vivo en borrador (5.14)', (int) $db->fetch_field($q, 'c') >= 1);
$q = $db->simple_select('ope_mision_participantes', 'salida', "mision_id = {$mid} AND personaje_id = {$pid}", array('limit' => 1));
$G['chk']('[55] Participante con salida registrada', (int) $db->fetch_field($q, 'salida') > 0);

// ── [5] Cron: abandono por tema cerrado ──
$creada5 = $crear_mision('Misión abandonada');
$pid4 = $mk_pj('abandono', 4);
$s5 = $solicitar($pid4, (int) ($creada5['mid'] ?? 0));
$G['chk']('[Cron] Misión en curso lista para abandono', $s5['ok']);
$m5 = ope7_mision_get((int) ($creada5['mid'] ?? 0));
$db->update_query('ope_temas', array('estado' => 'cerrado'), 'tid = ' . (int) ($m5['tema_id'] ?? 0));
$n = ope7_misiones_ronda_cerrar();
$m5 = ope7_mision_get((int) ($creada5['mid'] ?? 0));
$G['chk']('[Cron] Tema cerrado sin cierre → abandonada con motivo', $n >= 1 && $m5 && (string) $m5['estado'] === 'abandonada' && trim((string) $m5['motivo']) !== '');

// ── [6] Panel staff ──
$panel = ope7_misiones_panel_html();
$G['chk']('[6] Panel: tablón de misiones', strpos($panel, 'Tablón de misiones') !== false);
$G['chk']('[6] Panel: narradores con cupo', strpos($panel, 'Narradores') !== false);
$G['chk']('[6] Panel: auto-narradas en curso', strpos($panel, 'Auto-narradas en curso') !== false);
$G['chk']('[6] Panel: histórico de misiones', strpos($panel, 'Histórico de misiones') !== false);
$G['chk']('[6] Panel: sucesos en borrador', strpos($panel, 'Sucesos de misión') !== false);
$G['chk']('[6] Panel sin estilos inline', strpos($panel, 'style=') === false);

// ── [7] Limpieza final ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-mis-%'");
$db->delete_query('ope_misiones', "origen = 'test F5.2'");
$db->delete_query('ope_mision_tramos', 'mision_id NOT IN (SELECT id FROM mybb_ope_misiones)');
$db->delete_query('ope_mision_participantes', 'mision_id NOT IN (SELECT id FROM mybb_ope_misiones)');
$db->delete_query('ope_tramites', 'numero IN (52,53,54,55) AND solicitante_id = 1');
$db->delete_query('ope_sucesos', "tipo = 'mision'");
$db->delete_query('ope_temas', "zona LIKE 'Misión de prueba%' OR zona LIKE 'Misión exigente%' OR zona LIKE 'Misión segunda%' OR zona LIKE 'Misión larga%' OR zona LIKE 'Misión abandonada%'");
$db->delete_query('ope_temas_participantes', 'tema_id NOT IN (SELECT tid FROM mybb_ope_temas)');

echo "\n=== F5.2: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] === 0 ? 0 : 1);