<?php
/**
 * One Piece: 7 Seas · Test F1.3 — ciclo con usuario + efectos de trámites 1–12
 * ---------------------------------------------------------------------------
 * Verifica:
 *   · Ciclo con usuario (3 y 13): resultado → revision_usuario → aceptar →
 *     aceptado_usuario → firma publica (y pedir_cambios vuelve a en_revision).
 *   · Efectos al publicar: 1 apertura de tema (ancla + instantánea + un-presente),
 *     2 cierre (PP + karma), 4 compra de PP (automático), 7 hito de dote,
 *     12 justificación de contradicción.
 * Limpia todo al final.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f1-tramites.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$uid = 1;
$pid = 0;
$tids = array();
$temas = array();

function ope7_tf1_id($tabla, $nombre)
{
    global $db;
    $q = $db->simple_select($tabla, 'id', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
    return (int) $db->fetch_field($q, 'id');
}

function ope7_tf1_cleanup($pid, $tids, $temas)
{
    global $db;
    // Trámites huérfanos del personaje (p. ej. restos de ejecuciones fallidas).
    $q = $db->simple_select('ope_tramites', 'id', "personaje_id = {$pid}");
    while ($row = $db->fetch_array($q)) {
        $tids[] = (int) $row['id'];
    }
    $tids = array_unique(array_filter($tids));
    foreach ($tids as $tid) {
        $db->delete_query('ope_tramites_historico', "tramite_id = {$tid}");
        $db->delete_query('ope_tramites', "id = {$tid}");
    }
    foreach ($temas as $tid_tema) {
        $db->delete_query('ope_temas_participantes', "tema_id = {$tid_tema}");
        $db->delete_query('ope_temas', "tid = {$tid_tema}");
    }
    if ($pid > 0) {
        $db->delete_query('ope_historico_pp', "personaje_id = {$pid}");
        $db->delete_query('ope_atributos_secundarios', "personaje_id = {$pid}");
        $db->delete_query('ope_personaje_dotes', "personaje_id = {$pid}");
        $db->delete_query('ope_personaje_rasgos', "personaje_id = {$pid}");
        $db->delete_query('ope_dominios_personaje', "personaje_id = {$pid}");
        $db->delete_query('ope_inventario_personaje', "personaje_id = {$pid}");
        $db->delete_query('ope_personajes', "id = {$pid}");
    }
}

echo "=== Test F1.3 — ciclo y efectos 1–12 ===\n";

// ── Limpieza previa (idempotencia tras ejecuciones fallidas) ──
// 1) Trámites de prueba aunque queden huérfanos (restos de corridas previas).
$nums_test = '1,2,3,4,7,12,13';
$q = $db->simple_select('ope_tramites', 'id', "solicitante_id = 1 AND numero IN ({$nums_test})");
$borrados = 0;
while ($row = $db->fetch_array($q)) {
    $db->delete_query('ope_tramites_historico', 'tramite_id = ' . (int) $row['id']);
    $db->delete_query('ope_tramites', 'id = ' . (int) $row['id']);
    $borrados++;
}
// 2) Personajes de prueba (puede haber varios) con sus dependencias.
$q = $db->simple_select('ope_personajes', 'id', "slug = 'prueba-f13-mink'");
while ($row = $db->fetch_array($q)) {
    ope7_tf1_cleanup((int) $row['id'], array(), array());
    $borrados++;
}
// 3) Temas huérfanos: participantes cuyo personaje ya no existe.
if (ope7_tabla_existe('temas_participantes') && ope7_tabla_existe('temas') && ope7_tabla_existe('personajes')) {
    $q = $db->query("SELECT DISTINCT tp.tema_id FROM " . ope7_tabla_full('temas_participantes') . " tp "
        . "LEFT JOIN " . ope7_tabla_full('personajes') . " p ON p.id = tp.personaje_id "
        . "WHERE p.id IS NULL");
    while ($row = $db->fetch_array($q)) {
        $db->delete_query('ope_temas_participantes', 'tema_id = ' . (int) $row['tema_id']);
        $db->delete_query('ope_temas', 'tid = ' . (int) $row['tema_id']);
    }
}
if ($borrados > 0) {
    echo "  Limpieza previa: {$borrados} restos eliminados\n";
}

// ── Setup: personaje de prueba (Mink puro, 200 PP) ──
$raza_id = ope7_tf1_id('ope_razas', 'Mink');
$dote_id = ope7_tf1_id('ope_dotes', 'Adaptación Rápida');
$defecto_id = ope7_tf1_id('ope_defectos', 'Reserva Menguada');
$rasgo_amb = ope7_tf1_id('ope_rasgos', 'Ambicioso');
$pid = ope7_pj_guardar(array(
    'uid' => $uid, 'nombre' => 'Prueba F1.3 Mink', 'slug' => 'prueba-f13-mink', 'estado' => 'borrador',
    'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza_id,
    'fue' => 14, 'des' => 12, 'agi' => 20, 'res' => 10, 'per' => 18, 'inte' => 14, 'car' => 12, 'vol' => 20,
    'puntos_comprados' => 120, 'pp_saldo' => 200,
));
$db->insert_query('ope_personaje_rasgos', array('personaje_id' => $pid, 'rasgo_id' => $rasgo_amb, 'origen' => 'creacion', 'karma_acumulado' => 4, 'estado' => 'activo', 'contador_contradicciones' => 3));
echo "  Setup: pid={$pid} · PP 200\n";

// ── [1] Ciclo con usuario (trámite 3) ──
$r = ope7_tramite_crear($uid, $pid, 3, 'Validación de ficha de prueba');
$t3 = (int) ($r['tid'] ?? 0);
$tids[] = $t3;
echo "[1] Ciclo 3: " . ($r['ok'] ? 'creado' : 'FALLO ' . $r['msg']) . " (estado " . ope7_tramite_get($t3)['estado'] . ")\n";
$ok1 = false;
if ($t3 > 0) {
    ope7_tramite_guardar_resultado($t3, array('informe' => 'Todo verde: balanza 0, híbridos OK, techos OK.'));
    $st = ope7_tramite_get($t3);
    echo "    tras resultado: " . $st['estado'] . " (esperado revision_usuario)\n";
    $fir_antes = ope7_tramite_firmar($t3, $uid, 'publicar', 'Ficha válida.');
    echo "    firmar sin aceptar: " . ($fir_antes['ok'] ? 'FALLO (debería bloquear)' : 'OK — bloqueado: ' . $fir_antes['msg']) . "\n";
    $acc = ope7_tramite_usuario_aceptar($t3, $uid);
    echo "    aceptar: " . ($acc['ok'] ? 'OK' : 'FALLO ' . $acc['msg']) . " → " . ope7_tramite_get($t3)['estado'] . "\n";
    $fir = ope7_tramite_firmar($t3, $uid, 'publicar', 'Ficha validada y aceptada por el jugador.');
    $pj = ope7_pj_get($pid);
    echo "    firmar: " . ($fir['ok'] ? 'OK' : 'FALLO ' . $fir['msg']) . " · personaje.estado=" . $pj['estado'] . "\n";
    $ok1 = $fir['ok'] && $pj['estado'] === 'aprobado';
}
echo "    RESULTADO [1]: " . ($ok1 ? 'OK' : 'FALLO') . "\n";

// ── [2] Pedir cambios (trámite 13) ──
$r = ope7_tramite_crear($uid, $pid, 13, 'Quiero una técnica de agua', array('personaje_id' => $pid), array('idea' => 'Tajo que congela el agua', 'tier' => 1));
$t13 = (int) ($r['tid'] ?? 0);
$tids[] = $t13;
$ok2 = false;
if ($t13 > 0) {
    ope7_tramite_guardar_resultado($t13, array('nombre' => 'Tajo de Agua', 'tier' => 1, 'coste_pp' => 60));
    $st = ope7_tramite_get($t13);
    $pc = ope7_tramite_usuario_pedir_cambios($t13, $uid, 'Prefiero fuego, no agua.');
    echo "[2] Ciclo 13 pedir cambios: estado " . $st['estado'] . " → " . ($pc['ok'] ? 'OK — en_revision' : 'FALLO ' . $pc['msg']) . "\n";
    $fir2 = ope7_tramite_firmar($t13, $uid, 'publicar', 'Publicar sin aceptación final.');
    echo "    firmar sin re-aceptación: " . ($fir2['ok'] ? 'FALLO (debería bloquear)' : 'OK — bloqueado') . "\n";
    $ok2 = $pc['ok'] && !$fir2['ok'];
}
echo "    RESULTADO [2]: " . ($ok2 ? 'OK' : 'FALLO') . "\n";

// ── [3] Efecto 4 · compra de PP (automático) ──
// 7.3 (D3.5): la compra paga PP y arranca el cronómetro; los puntos entran en
// la reserva al terminar. Coste tramo I = 10 PP × 5 = 50. Saldo 200 → 150.
$r = ope7_tramite_crear($uid, $pid, 4, '', array('atributo' => 'fue', 'bloque' => 5));
$t4 = (int) ($r['tid'] ?? 0);
$tids[] = $t4;
$pj = ope7_pj_get($pid);
$ok3 = false;
if ($t4 > 0 && ope7_tramite_get($t4)['estado'] === 'publicado') {
    $ok3 = (int) $pj['pp_saldo'] === 150 && (int) $pj['reserva'] === 0 && (int) $pj['entrenamiento_fin'] > time();
    echo "[3] Compra PP (auto): saldo={$pj['pp_saldo']} (esperado 150) reserva={$pj['reserva']} (0 al comprar; entra al vencer) entrenamiento=" . ($pj['entrenamiento_fin'] > time() ? 'OK' : 'FALLO') . "\n";
    // Comprar de nuevo mientras entrena → bloqueado (cronómetro 5.6).
    $r2 = ope7_tramite_crear($uid, $pid, 4, '', array('atributo' => 'fue', 'bloque' => 10));
    $ok3 = $ok3 && !$r2['ok'] && ope7_tramite_get((int) ($r2['tid'] ?? 0))['estado'] === 'rechazado';
    echo "    bloqueo por cronómetro: " . (!$r2['ok'] ? 'OK' : 'FALLO') . " (queda rechazado: " . ope7_tramite_get((int) ($r2['tid'] ?? 0))['estado'] . ")\n";
}
echo "    RESULTADO [3]: " . ($ok3 ? 'OK' : 'FALLO') . "\n";

// ── [4] Efecto 1 · apertura de tema (presente + un-presente) ──
// D1.8: el hilo real se vincula (bien por formulario con mybb_tid, bien por el
// hook de posteo ope7_tema_vincular_mybb_por_pj). Probamos el campo explícito.
$r = ope7_tramite_crear($uid, $pid, 1, '', array('zona' => 'Paraíso', 'tipo' => 'presente', 'mybb_tid' => 777));
$t1a = (int) ($r['tid'] ?? 0);
$tids[] = $t1a;
$q = $db->simple_select('ope_temas_participantes', 'tema_id', "personaje_id = {$pid}", array('limit' => 1));
$tema1 = (int) $db->fetch_field($q, 'tema_id');
$temas[] = $tema1;
$ok4 = false;
if ($t1a > 0 && $tema1 > 0) {
    $tq = $db->simple_select('ope_temas', 'tipo, estado, fecha_foro, mybb_tid', "tid = {$tema1}", array('limit' => 1));
    $trow = $db->fetch_array($tq);
    $pq = $db->simple_select('ope_temas_participantes', 'ficha_instantanea', "personaje_id = {$pid} AND tema_id = {$tema1}", array('limit' => 1));
    $snap = $db->fetch_field($pq, 'ficha_instantanea');
    echo "[4] Apertura tema: tipo={$trow['tipo']} estado={$trow['estado']} ancla={$trow['fecha_foro']} instantánea=" . ($snap ? 'OK' : 'FALLO') . " mybb_tid={$trow['mybb_tid']}\n";
    // Segundo presente → bloqueado por un-presente.
    $r2 = ope7_tramite_crear($uid, $pid, 1, '', array('zona' => 'East Blue', 'tipo' => 'presente'));
    $ok4 = $trow['tipo'] === 'presente' && $trow['estado'] === 'abierto' && $snap !== '' && !$r2['ok'] && (int) $trow['mybb_tid'] === 777;
    echo "    bloqueo un-presente: " . (!$r2['ok'] ? 'OK' : 'FALLO') . " · vinculación hilo (777): " . ((int) $trow['mybb_tid'] === 777 ? 'OK' : 'FALLO') . "\n";
    // D1.8: vincular por hook (por_pj) no pisa un hilo ya vinculado.
    $vin = ope7_tema_vincular_mybb_por_pj($pid, 999);
    $mybb_tras = (int) $db->fetch_field($db->simple_select('ope_temas', 'mybb_tid', "tid = {$tema1}", array('limit' => 1)), 'mybb_tid');
    echo "    hook por_pj no pisa: " . ($vin === 0 && $mybb_tras === 777 ? 'OK' : 'FALLO') . " (quedó {$mybb_tras})\n";
    $ok4 = $ok4 && $vin === 0 && $mybb_tras === 777;
    $tids[] = 0; // el segundo ni se creó
}
echo "    RESULTADO [4]: " . ($ok4 ? 'OK' : 'FALLO') . "\n";

// ── [5] Efecto 2 · cierre de tema (PP + karma) ──
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
$saldo_antes = (int) $db->fetch_field($q, 'pp_saldo');
// D1.8: el thread real vinculado (777) se crea en MyBB y el cierre lo cierra.
if ($db->table_exists('threads')) {
    $db->delete_query('threads', 'tid = 777');
    $db->insert_query('threads', array(
        'tid' => 777, 'fid' => 1, 'subject' => 'Test D1.8', 'uid' => $uid,
        'username' => 'admin', 'dateline' => TIME_NOW, 'firstpost' => 0, 'lastpost' => TIME_NOW,
        'lastposter' => 'admin', 'lastposteruid' => $uid, 'views' => 0, 'replies' => 0,
        'closed' => '', 'sticky' => 0, 'notes' => '', 'visible' => 1,
    ));
}
$r = ope7_tramite_crear($uid, $pid, 2, 'Cierre del tema de la taberna', array('tema_id' => $tema1), array('participantes' => array($pid)));
$t2 = (int) ($r['tid'] ?? 0);
$tids[] = $t2;
$ok5 = false;
if ($t2 > 0) {
    ope7_tramite_guardar_resultado($t2, array(
        'pp_total' => 50, 'base_pp' => 50, 'tramo' => 1,
        'factores' => array('fidelidad' => 1.0, 'peso' => 1.0, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0),
        'rasgos' => array(array('rasgo_id' => $rasgo_amb, 'estado' => 'jugado')),
        'motivo' => 'Cierre estándar.',
    ));
    $fir = ope7_tramite_firmar($t2, $uid, 'publicar', 'Desglose correcto.');
    $q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
    $saldo_despues = (int) $db->fetch_field($q, 'pp_saldo');
    $q = $db->simple_select('ope_personaje_rasgos', 'karma_acumulado, estado', "personaje_id = {$pid} AND rasgo_id = {$rasgo_amb}", array('limit' => 1));
    $rk = $db->fetch_array($q);
    $q = $db->simple_select('ope_temas', 'estado', "tid = {$tema1}", array('limit' => 1));
    $t_estado = $db->fetch_field($q, 'estado');
    // D1.8: el hilo real vinculado (777) debe quedar cerrado en MyBB.
    $th = $db->table_exists('threads') ? (string) $db->fetch_field($db->simple_select('threads', 'closed', 'tid = 777', array('limit' => 1)), 'closed') : '';
    $hilo_cerrado = $th !== '';
    echo "[5] Cierre: PP {$saldo_antes}→{$saldo_despues} (esperado +50) · karma={$rk['karma_acumulado']} (4→5) estado_rasgo={$rk['estado']} (arraigado) · tema={$t_estado} (cerrado) · hilo 777 closed='{$th}'\n";
    $ok5 = $saldo_despues === $saldo_antes + 50 && (int) $rk['karma_acumulado'] === 5 && $rk['estado'] === 'arraigado' && $t_estado === 'cerrado' && $fir['ok'] && $hilo_cerrado;
    if ($db->table_exists('threads')) {
        $db->delete_query('threads', 'tid = 777');
    }
}
echo "    RESULTADO [5]: " . ($ok5 ? 'OK' : 'FALLO') . "\n";

// ── [6] Efecto 7 · hito de dote ──
$r = ope7_tramite_crear($uid, $pid, 7, 'El maestro del dojo me enseña su guardia', array(), array('dote_id' => $dote_id));
$t7 = (int) ($r['tid'] ?? 0);
$tids[] = $t7;
$ok6 = false;
if ($t7 > 0) {
    ope7_tramite_guardar_resultado($t7, array('dote_id' => $dote_id));
    $fir = ope7_tramite_firmar($t7, $uid, 'publicar', 'Hito narrativo validado.');
    $q = $db->simple_select('ope_personaje_dotes', 'id, origen', "personaje_id = {$pid} AND dote_id = {$dote_id} AND origen = 'hito'", array('limit' => 1));
    $dk = $db->fetch_array($q);
    $ok6 = $fir['ok'] && (bool) $dk;
    echo "[6] Hito dote: " . ($ok6 ? 'OK — dote ' . $dote_id . ' origen hito' : 'FALLO') . "\n";
}
echo "    RESULTADO [6]: " . ($ok6 ? 'OK' : 'FALLO') . "\n";

// ── [7] Efecto 12 · justificación de contradicción ──
$r = ope7_tramite_crear($uid, $pid, 12, 'El personaje traiciona a su tripulación porque le chantajean con su hermano', array(), array('rasgo_id' => $rasgo_amb));
$t12 = (int) ($r['tid'] ?? 0);
$tids[] = $t12;
$ok7 = false;
if ($t12 > 0) {
    ope7_tramite_guardar_resultado($t12, array('rasgo_id' => $rasgo_amb));
    $fir = ope7_tramite_firmar($t12, $uid, 'publicar', 'Justificación validada: chantaje.');
    $q = $db->simple_select('ope_personaje_rasgos', 'contador_contradicciones', "personaje_id = {$pid} AND rasgo_id = {$rasgo_amb}", array('limit' => 1));
    $cc = (int) $db->fetch_field($q, 'contador_contradicciones');
    $ok7 = $fir['ok'] && $cc === 0;
    echo "[7] Justificación: contador=" . $cc . " (esperado 0) " . ($ok7 ? 'OK' : 'FALLO') . "\n";
}
echo "    RESULTADO [7]: " . ($ok7 ? 'OK' : 'FALLO') . "\n";

// ── Limpieza ──
ope7_tf1_cleanup($pid, $tids, $temas);
echo "[8] Limpieza: OK\n";

$total = $ok1 + $ok2 + $ok3 + $ok4 + $ok5 + $ok6 + $ok7;
echo "\n=== DONE — " . $total . "/7 bloques OK ===\n";
exit($total === 7 ? 0 : 1);
