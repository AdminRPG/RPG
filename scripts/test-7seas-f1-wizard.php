<?php
/**
 * One Piece: 7 Seas · Test del flujo del wizard (F1.1)
 * ----------------------------------------------------
 * Dispara crear-personaje.php en un subproceso (el redirect hace exit):
 *   · ficha VÁLIDA (Mink puro, 120 pts, balanzas 0, 2 dominios) → redirect a
 *     tramites.php + borrador en mybb_ope_* + personaje activo ope + trámite 3.
 *   · ficha ROTA (balanza dotes ≠ 0) → render con errores, sin guardar.
 * Limpia todo al final y restaura el puntero del admin.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f1-wizard.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$uid = 1;
$tmp = sys_get_temp_dir();
$limpiar_pids = array();

function ope7_test_ids()
{
    global $db;
    $out = array();
    foreach (array('Mink', 'Adaptación Rápida', 'Reserva Menguada', 'Ambicioso', 'Vengativo', 'Armas de filo', 'Cuerpo a cuerpo') as $n) {
        $tabla = 'ope_razas';
        if ($n === 'Adaptación Rápida') $tabla = 'ope_dotes';
        if ($n === 'Reserva Menguada') $tabla = 'ope_defectos';
        if ($n === 'Ambicioso' || $n === 'Vengativo') $tabla = 'ope_rasgos';
        if ($n === 'Armas de filo' || $n === 'Cuerpo a cuerpo') $tabla = 'ope_dominios';
        $q = $db->simple_select($tabla, 'id', "nombre = '" . $db->escape_string($n) . "'", array('limit' => 1));
        $out[$n] = (int) $db->fetch_field($q, 'id');
    }
    return $out;
}

function ope7_test_post($payload)
{
    global $tmp;
    $pf = $tmp . '/wiz-payload-' . getmypid() . '.json';
    $of = $tmp . '/wiz-out-' . getmypid() . '.txt';
    file_put_contents($pf, json_encode($payload, JSON_UNESCAPED_UNICODE));
    @unlink($of);
    $cmd = PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/_wizard-post-child.php') . ' ' . escapeshellarg($pf) . ' ' . escapeshellarg($of);
    $stdout = shell_exec($cmd . ' 2>&1');
    $loc = file_exists($of) ? (string) file_get_contents($of) : '';
    @unlink($pf);
    @unlink($of);
    return array('loc' => $loc, 'stdout' => (string) $stdout);
}

echo "=== Test F1.1 — flujo del wizard ===\n";
$ids = ope7_test_ids();
$err_ok = true;

// 1) POST válido → redirect a tramites.php + datos creados.
$base = array(
    'accion' => 'enviar',
    'nombre' => 'Prueba Wizard Mink', 'retrato' => '', 'bio' => '',
    'raza_id' => $ids['Mink'], 'raza_hibrida_id' => 0, 'tribu_id' => 0,
    'fue' => 14, 'des' => 12, 'agi' => 20, 'res' => 10, 'per' => 18, 'inte' => 14, 'car' => 12, 'vol' => 20,
    'dotes' => array($ids['Adaptación Rápida']),
    'defectos' => array($ids['Reserva Menguada']),
    'rasgos' => array($ids['Ambicioso'], $ids['Vengativo']),
    'dominios' => array($ids['Armas de filo'] => 1, $ids['Cuerpo a cuerpo'] => 1),
    'idea_tecnica' => 'Patada giratoria con chispas de la raza',
);
$r1 = ope7_test_post($base);
// En CLI no hay cabeceras: el éxito se verifica por efectos en BD (el redirect
// corta el render con exit; si hubiera errores, el HTML saldría con «flash warn»).
$sin_errores_html = strpos($r1['stdout'], 'flash warn') === false;
echo "[1] POST válido: " . ($sin_errores_html ? "OK — enviado (sin errores en el render)" : "FALLO — " . substr($r1['stdout'], 0, 300)) . "\n";

$q = $db->simple_select('ope_personajes', 'id, nombre, estado, pp_saldo', "uid = {$uid} AND nombre = 'Prueba Wizard Mink'", array('limit' => 1));
$pj = $db->fetch_array($q);
$pid = (int) ($pj['id'] ?? 0);
if ($pid > 0) {
    $limpiar_pids[] = $pid;
    $sec = $db->simple_select('ope_atributos_secundarios', 'pv, pe, pa', "personaje_id = {$pid}", array('limit' => 1));
    $srow = $db->fetch_array($sec);
    $tq = $db->simple_select('ope_tramites', 'id, numero, estado', "personaje_id = {$pid} AND numero = 3", array('limit' => 1));
    $trow = $db->fetch_array($tq);
    echo "    Personaje: id={$pid} estado={$pj['estado']} PP={$pj['pp_saldo']} · secundarios PV={$srow['pv']} PE={$srow['pe']} PA={$srow['pa']}\n";
    echo "    Trámite 3: " . ($trow ? "id={$trow['id']} estado={$trow['estado']}" : 'FALLO — sin trámite') . "\n";
    $a = ope7_pj_activo($uid);
    echo "    Puntero activo ope: " . ($a && $a['tabla'] === 'ope' && $a['id'] === $pid ? 'OK' : 'FALLO') . "\n";
    if ($trow) {
        $db->delete_query('ope_tramites_historico', "tramite_id = " . (int) $trow['id']);
        $db->delete_query('ope_tramites', "id = " . (int) $trow['id']);
    }
} else {
    echo "    FALLO: no se guardó el personaje\n";
}

// 2) POST roto (balanza ≠ 0) → render con errores y sin duplicar.
$roto = $base;
unset($roto['dotes']);
$r2 = ope7_test_post($roto);
$tiene_error = strpos($r2['stdout'], 'balanza') !== false;
$q2 = $db->simple_select('ope_personajes', 'COUNT(*) AS c', "uid = {$uid} AND nombre = 'Prueba Wizard Mink' AND estado = 'borrador' AND id <> {$pid}");
echo "[2] POST roto (balanza≠0): " . ($tiene_error ? 'OK — error mostrado' : 'FALLO — sin mensaje') . " · sin duplicar: " . ((int) $db->fetch_field($q2, 'c') === 0 ? 'OK' : 'FALLO') . "\n";

// 3) Limpieza.
foreach ($limpiar_pids as $lp) {
    $db->delete_query('ope_personajes', "id = {$lp}");
    $db->delete_query('ope_atributos_secundarios', "personaje_id = {$lp}");
    $db->delete_query('ope_personaje_dotes', "personaje_id = {$lp}");
    $db->delete_query('ope_personaje_rasgos', "personaje_id = {$lp}");
    $db->delete_query('ope_dominios_personaje', "personaje_id = {$lp}");
}
ope7_pj_set_activo($uid, 'rol', 0);
echo "[3] Limpieza: OK\n";

echo "\n=== DONE ===\n";
