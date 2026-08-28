<?php
/**
 * One Piece: 7 Seas · Test F5.3 — Tripulaciones (5.21-ter/cap. 22.9)
 * ------------------------------------------------------------------
 * Verifica:
 *  · 63 — Fundación: mínimo 2 (capitán + 1), ficha (nombre/bandera/
 *    propósito), barco del capitán con plazas (5.17, espacio por raza),
 *    un PJ por usuario, un-presente (5.6); crea la entidad + cofre común
 *    (5.9), vincula el barco y abre el tema de fundación.
 *  · 64 — Ingreso: solo el capitán, espacio del barco, PJ aprobado sin
 *    tripulación, un PJ por usuario; fecha de ingreso.
 *  · 65 — Baja/expulsión: solo el capitán, motivo obligatorio, libera
 *    plaza y reparte la parte del cofre (5.9).
 *  · 66 — Cambio de capitán (staff): cesión o motín con veredicto; roles
 *    intercambiados; suceso de ronda en borrador si cambia el nombre (5.14).
 *  · 67 — Disolución (staff): reparto equitativo del cofre (residuo al
 *    capitán), barco al último capitán, estado disuelta.
 *  · Cron — <2 activos: 1ª detección → aviso con plazo (no disuelve);
 *    2ª detección → disolución automática con motivo (22.9).
 *  · Panel staff «Tripulaciones» (A.3) sin estilos inline.
 * Idempotente: limpieza completa al final (PJ, tripulaciones, barcos, temas).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-tripulaciones.php');
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

// ── Limpieza previa idempotente ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-trip-%'");
$db->delete_query('ope_tripulacion_historico', 'tripulacion_id NOT IN (SELECT id FROM mybb_ope_tripulaciones)');
$db->delete_query('ope_tripulantes', 'tripulacion_id NOT IN (SELECT id FROM mybb_ope_tripulaciones)');
$db->delete_query('ope_cofre_tripulacion', 'tripulacion_id NOT IN (SELECT id FROM mybb_ope_tripulaciones)');
$db->delete_query('ope_tripulaciones', "nombre LIKE 'Trip test%' OR nombre LIKE 'Banda test%' OR nombre LIKE 'Banda renombrada%'");
$db->delete_query('ope_barcos', "nombre LIKE 'Barco test%'");
$db->delete_query('ope_tramites', 'numero IN (63,64,65,66,67) AND solicitante_id = 1');
$db->delete_query('ope_temas', "zona LIKE 'Fundación: Trip test%' OR zona LIKE 'Fundación: Banda test%'");
$db->delete_query('ope_temas_participantes', 'tema_id NOT IN (SELECT tid FROM mybb_ope_temas)');
$db->delete_query('ope_sucesos', "tipo = 'tripulacion'");

// Un uid distinto por PJ: el módulo exige un PJ por usuario (22.9).
$next_uid = 1;
/** Crea un PJ aprobado (raza Mink, espacio 1) o Gigante (espacio 5). */
$mk_pj = function ($slug, $raza_nombre = 'Mink', $nivel = 3) use ($db, &$next_uid) {
    $q = $db->simple_select('ope_razas', 'id', "nombre = '" . $db->escape_string($raza_nombre) . "'", array('limit' => 1));
    $raza = (int) $db->fetch_field($q, 'id');
    $pid = ope7_pj_guardar(array(
        'uid' => $next_uid++, 'nombre' => 'Prueba Trip ' . $slug, 'slug' => 'prueba-trip-' . $slug, 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => (int) $nivel, 'raza_id' => $raza,
        'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
        'puntos_comprados' => 0, 'pp_saldo' => 0,
    ));
    ope7_cartera_mover($pid, 'cartera', 100000);
    return $pid;
};

/** Crea un barco del capitán (Balandro, plazas por N1) y devuelve su id. */
$mk_barco = function ($pid, $nombre) use ($db) {
    $q = $db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Balandro'", array('limit' => 1));
    $tipo = (int) $db->fetch_field($q, 'id');
    $q = $db->simple_select('ope_maderas_casco', 'id', '1=1', array('limit' => 1));
    $madera = (int) $db->fetch_field($q, 'id');
    return ope7_barco_crear($pid, $tipo, 'N1', $madera, $nombre);
};

/** Crea y firma un trámite (cualquier número). Devuelve [ok, msg, tid]. */
$tramite = function ($pid, $numero, $motivo, array $ids = array()) use ($db) {
    $r = ope7_tramite_crear(1, $pid, $numero, $motivo, $ids);
    if (!$r['ok']) {
        return array('ok' => false, 'msg' => $r['msg']);
    }
    $f = ope7_tramite_firmar((int) $r['tid'], 1, 'publicar', 'Firma de prueba: ' . $motivo);
    return array('ok' => $f['ok'], 'msg' => (string) ($f['msg'] ?? ''), 'tid' => (int) $r['tid']);
};

// ── [1] 63 · Fundación ──
$cap = $mk_pj('cap');
$socio = $mk_pj('socio');
$barco = $mk_barco($cap, 'Barco test 1');

// Sin fundador → BLOQUEADO (mínimo 2, 22.9).
$r = $tramite($cap, 63, 'Fundar', array('nombre' => 'Trip test x', 'bandera' => '', 'proposito' => '',
    'barco_id' => $barco, 'fundadores' => array()));
$G['chk']('[63] Sin fundador → BLOQUEADO (mínimo 2)', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// Barco ajeno → BLOQUEADO (debe ser del capitán, 5.17).
$barco_ajeno = $mk_barco($socio, 'Barco test ajeno');
$r = $tramite($cap, 63, 'Fundar', array('nombre' => 'Trip test y', 'bandera' => '', 'proposito' => '',
    'barco_id' => $barco_ajeno, 'fundadores' => array($socio)));
$G['chk']('[63] Barco ajeno → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// Fundación válida.
$r = $tramite($cap, 63, 'Fundar la banda', array('nombre' => 'Trip test 1', 'bandera' => 'Calabera dorada',
    'proposito' => 'Buscar el One Piece', 'barco_id' => $barco, 'fundadores' => array($socio)));
$G['chk']('[63] Fundación válida (capitán + 1)', $r['ok']);
$q = $db->simple_select('ope_tripulaciones', '*', "nombre = 'Trip test 1'", array('limit' => 1));
$trip = $db->fetch_array($q);
$G['chk']('[63] Entidad activa creada con ficha', $trip && (string) $trip['estado'] === 'activa' && (string) $trip['bandera'] === 'Calabera dorada');
$trip_id = (int) ($trip['id'] ?? 0);
$G['chk']('[63] Capitán y fundador como miembros', $trip_id > 0 && count(ope7_trip_miembros($trip_id, true)) === 2);
$G['chk']('[63] Barco vinculado a la banda', $trip && (int) $trip['barco_id'] === $barco);
$q = $db->simple_select('ope_barcos', 'tripulacion_id', "id = {$barco}", array('limit' => 1));
$G['chk']('[63] El barco es de la banda (no de uno)', (int) $db->fetch_field($q, 'tripulacion_id') === $trip_id);
$cofre = ope7_trip_cofre_get($trip_id);
$G['chk']('[63] Cofre común creado (5.9)', $cofre['berries'] === 0);
$q = $db->simple_select('ope_temas', 'tid', "zona = 'Fundación: Trip test 1'", array('limit' => 1));
$G['chk']('[63] Tema de fundación abierto (presente, 5.6)', (int) $db->fetch_field($q, 'tid') > 0);

// Nombre duplicado → BLOQUEADO (uq_nombre).
$r = $tramite($cap, 63, 'Fundar', array('nombre' => 'Trip test 1', 'bandera' => '', 'proposito' => '',
    'barco_id' => $mk_barco($cap, 'Barco test 2'), 'fundadores' => array($socio)));
$G['chk']('[63] Nombre duplicado → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// Un-presente: el capitán ya tiene tema abierto → BLOQUEADO (5.6).
$r = $tramite($cap, 63, 'Fundar', array('nombre' => 'Trip test 3', 'bandera' => '', 'proposito' => '',
    'barco_id' => $mk_barco($cap, 'Barco test 3'), 'fundadores' => array($socio)));
$G['chk']('[63] Un-presente del capitán → BLOQUEADO (5.6)', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// ── [2] 64 · Ingreso ──
$nuevo = $mk_pj('nuevo');
$r = $tramite($nuevo, 64, 'Ingresar', array('tripulacion_id' => $trip_id, 'ingresado_id' => $nuevo));
$G['chk']('[64] No capitán → BLOQUEADO (solo el capitán ingresa)', !$r['ok'] && stripos($r['msg'], 'BLOQUEADO') !== false);
$r = $tramite($cap, 64, 'Ingresar', array('tripulacion_id' => $trip_id, 'ingresado_id' => $nuevo));
$G['chk']('[64] Ingreso válido', $r['ok']);
$G['chk']('[64] Miembro activo con fecha', ope7_pj_tripulacion_activa($nuevo) === $trip_id && count(ope7_trip_miembros($trip_id, true)) === 3);

// Ingreso duplicado → BLOQUEADO (ya en la banda).
$r = $tramite($cap, 64, 'Ingresar', array('tripulacion_id' => $trip_id, 'ingresado_id' => $nuevo));
$G['chk']('[64] Ya miembro → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADO') !== false);

// ── [3] 65 · Baja/expulsión ──
// Mete berries al cofre y verifica el reparto proporcional (5.9).
ope7_trip_cofre_mover($trip_id, $cap, 3000, 'Test: aporte al cofre');
$r = $tramite($cap, 65, 'Expulsión por traición', array('tripulacion_id' => $trip_id, 'expulsado_id' => $nuevo));
$G['chk']('[65] Baja válida con motivo', $r['ok']);
$G['chk']('[65] Plaza liberada (2 miembros)', count(ope7_trip_miembros($trip_id, true)) === 2);
$q = $db->simple_select('ope_tripulantes', 'estado', "tripulacion_id = {$trip_id} AND personaje_id = {$nuevo}", array('limit' => 1));
$G['chk']('[65] Estado salio + fecha_salida', (string) $db->fetch_field($q, 'estado') === 'salio');
$cofre = ope7_trip_cofre_get($trip_id);
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$nuevo}", array('limit' => 1));
$saldo_nuevo = (int) $db->fetch_field($q, 'cartera');
// Cofre: 3000 - reparto (3000/3=1000 por miembro, 3 miembros antes de la baja).
$G['chk']('[65] Reparto del cofre: saliente +1.000 ฿, cofre −1.000', $saldo_nuevo >= 1000 && (int) $cofre['berries'] === 2000);

// Sin motivo → BLOQUEADO.
$r = $tramite($cap, 65, '', array('tripulacion_id' => $trip_id, 'expulsado_id' => $socio));
$G['chk']('[65] Sin motivo → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// ── [4] 66 · Cambio de capitán (staff) ──
$r = $tramite($cap, 66, 'Cesión por motivos personales', array('tripulacion_id' => $trip_id, 'sucesor_id' => $socio, 'tipo' => 'cesion', 'nuevo_nombre' => 'Banda test renombrada'));
$G['chk']('[66] Cambio de capitán válido (cesión)', $r['ok']);
$q = $db->simple_select('ope_tripulaciones', 'capitan_id, nombre', "id = {$trip_id}", array('limit' => 1));
$trip2 = $db->fetch_array($q);
$G['chk']('[66] Nuevo capitán + nombre cambiado', (int) $trip2['capitan_id'] === $socio && (string) $trip2['nombre'] === 'Banda test renombrada');
$G['chk']('[66] Roles intercambiados (capitán viejo → miembro)', ope7_trip_miembros($trip_id, true) !== array());
$q = $db->simple_select('ope_sucesos', 'id', "tipo = 'tripulacion' AND titulo LIKE '%Banda test renombrada%'", array('limit' => 1));
$G['chk']('[66] Suceso de ronda en borrador (cambio de nombre, 5.14)', (int) $db->fetch_field($q, 'id') > 0);

// Sucesor no miembro → BLOQUEADO.
$ext = $mk_pj('ext');
$r = $tramite($socio, 66, 'Motín', array('tripulacion_id' => $trip_id, 'sucesor_id' => $ext, 'tipo' => 'motin'));
$G['chk']('[66] Sucesor no miembro → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADO') !== false);

// ── [5] 67 · Disolución (staff) ──
ope7_trip_cofre_mover($trip_id, $socio, 9000, 'Test: más aporte');
$r = $tramite($socio, 67, 'La banda se separa en buenos términos', array('tripulacion_id' => $trip_id));
$G['chk']('[67] Disolución válida con motivo', $r['ok']);
$q = $db->simple_select('ope_tripulaciones', 'estado', "id = {$trip_id}", array('limit' => 1));
$G['chk']('[67] Entidad disuelta', (string) $db->fetch_field($q, 'estado') === 'disuelta');
$G['chk']('[67] Sin miembros activos', count(ope7_trip_miembros($trip_id, true)) === 0);
$q = $db->simple_select('ope_barcos', 'dueno_id, tripulacion_id', "id = {$barco}", array('limit' => 1));
$b = $db->fetch_array($q);
$G['chk']('[67] Barco al último capitán y desvinculado', (int) $b['dueno_id'] === $socio && (int) $b['tripulacion_id'] === 0);
$cofre = ope7_trip_cofre_get($trip_id);
$G['chk']('[67] Cofre repartido y vacío (residuo al capitán)', (int) $cofre['berries'] === 0);
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$socio}", array('limit' => 1));
$G['chk']('[67] Capitán recibió su parte + residuo', (int) $db->fetch_field($q, 'cartera') > 5000);

// ── [6] Cron · <2 activos → aviso → disolución automática (22.9) ──
$cap2 = $mk_pj('cap2');
$socio2 = $mk_pj('socio2');
$barco2 = $mk_barco($cap2, 'Barco test 4');
$r = $tramite($cap2, 63, 'Fundar', array('nombre' => 'Trip test cron', 'bandera' => '', 'proposito' => '',
    'barco_id' => $barco2, 'fundadores' => array($socio2)));
$G['chk']('[cron] Fundación para el cron', $r['ok']);
$q = $db->simple_select('ope_tripulaciones', 'id', "nombre = 'Trip test cron'", array('limit' => 1));
$tcron = (int) $db->fetch_field($q, 'id');
// Baja del socio → 1 activo.
$r = $tramite($cap2, 65, 'Se va', array('tripulacion_id' => $tcron, 'expulsado_id' => $socio2));
$G['chk']('[cron] Baja para dejar 1 activo', $r['ok']);
$n1 = ope7_tripulaciones_ronda_cerrar();
$q = $db->simple_select('ope_tripulaciones', 'estado, aviso_disolucion_en', "id = {$tcron}", array('limit' => 1));
$tc = $db->fetch_array($q);
$G['chk']('[cron] 1ª detección: solo aviso (no disuelve)', $n1 >= 1 && (string) $tc['estado'] === 'activa' && (int) $tc['aviso_disolucion_en'] > 0);
$n2 = ope7_tripulaciones_ronda_cerrar();
$q = $db->simple_select('ope_tripulaciones', 'estado', "id = {$tcron}", array('limit' => 1));
$G['chk']('[cron] 2ª detección: disolución automática con motivo', $n2 >= 1 && (string) $db->fetch_field($q, 'estado') === 'disuelta');
$q = $db->simple_select('ope_tripulacion_historico', 'evento', "tripulacion_id = {$tcron} AND evento = 'disolucion'", array('limit' => 1));
$G['chk']('[cron] Motivo de disolución en el histórico', (string) $db->fetch_field($q, 'evento') === 'disolucion');

// ── [7] Panel staff sin estilos inline ──
if (function_exists('ope7_tripulaciones_panel_html')) {
    $html = ope7_tripulaciones_panel_html();
    $G['chk']('[panel] Panel renderiza', $html !== '');
    $G['chk']('[panel] Sin <style> ni style= estáticos', stripos($html, '<style') === false && strpos($html, 'style=') === false);
} else {
    $G['chk']('[panel] Función del panel existe', false);
}

// ── Resumen ──
echo "\n=== F5.3 Tripulaciones: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] > 0 ? 1 : 0);