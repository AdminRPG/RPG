<?php
/**
 * One Piece: 7 Seas · Test de integración F1.0 — dominio de personajes
 * ------------------------------------------------------------------
 * Verifica: secundarios con raciales (fórmulas confirmadas) · validación de
 * ficha (presupuesto 120, techos, balanzas a 0, tribus, híbridos) · guardado
 * en mybb_ope_personajes + materializada · puntero de personaje activo (D1.1).
 * Los personajes de prueba se limpian al final; el puntero del admin se restaura.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f1.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

echo "=== Test F1.0 — dominio de personajes 7 Seas ===\n";

// 1) Secundarios: Mink puro nv1, base 120 (fue14 des12 agi20 res10 per18 int14 car12 vol20)
//    Efectivos (Mink: agi+6 per+6 res−4): fue14 des12 agi26 res6 per24 int14 car12 vol20
$f = array(
    'nivel' => 1, 'raza_id' => 0, 'raza_hibrida_id' => 0,
    'fue' => 14, 'des' => 12, 'agi' => 20, 'res' => 10, 'per' => 18, 'inte' => 14, 'car' => 12, 'vol' => 20,
);
// Necesitamos el id real de Mink para que los raciales se apliquen.
$q = $db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1));
$f['raza_id'] = (int) $db->fetch_field($q, 'id');
$s = ope7_pj_secundarios($f);
$checks = array(
    'PV'   => array($s['pv'], 185),
    'PE'   => array($s['pe'], 184),
    'Vel'  => array($s['velocidad'], 5.37),
    'Sprint' => array($s['sprint'], 8.59),
    'SaltoV' => array($s['salto_v'], 0.9),
    'SaltoH' => array($s['salto_h'], 1.36),
    'Carga'  => array($s['carga'], 96),
    'CargaLev' => array($s['carga_levantar'], 240),
    'ResPasiva' => array($s['resistencia_pasiva'], 0.9),
    'Lanzamiento' => array($s['lanzamiento'], 8.0),
    'Recuperacion' => array($s['recuperacion'], 2.6),
    'PA' => array($s['pa'], 9),
);
$ok_sec = true;
foreach ($checks as $nom => $c) {
    $igual = abs($c[0] - $c[1]) < 0.011;
    if (!$igual) $ok_sec = false;
    echo sprintf("  %-14s %6s (esperado %s) %s\n", $nom, $c[0], $c[1], $igual ? 'OK' : '** FALLO **');
}
echo "[1] Secundarios Mink nv1: " . ($ok_sec ? 'OK' : 'FALLO') . "\n";

// 2) Techo por atributo (tabla §7.2)
$techos = array(1 => 20, 10 => 34, 20 => 50, 25 => 58, 30 => 66, 35 => 74, 45 => 90, 50 => 100);
$ok_techo = true;
foreach ($techos as $n => $t) {
    $v = ope7_pj_techo_atributo($n);
    if ($v !== $t) { $ok_techo = false; echo "  techo($n)=$v (esperado $t) ** FALLO **\n"; }
}
echo "[2] Techos por nivel: " . ($ok_techo ? 'OK' : 'FALLO') . "\n";

// 3) Guardar personaje VÁLIDO: Mink puro, balanzas a 0, dominios 1+1
$q = $db->simple_select('ope_dotes', 'id', "nombre = 'Adaptación Rápida'", array('limit' => 1));
$dote_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_defectos', 'id', "nombre = 'Reserva Menguada'", array('limit' => 1));
$defecto_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_rasgos', 'id', "nombre IN ('Ambicioso','Vengativo')");
$rasgos = array();
while ($r = $db->fetch_array($q)) $rasgos[] = (int) $r['id'];
$q = $db->simple_select('ope_dominios', 'id', "nombre IN ('Armas de filo','Cuerpo a cuerpo')");
$doms = array();
while ($r = $db->fetch_array($q)) $doms[(int) $r['id']] = 1;

$pid = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F1 Mink', 'slug' => 'prueba-f1-mink', 'estado' => 'borrador',
    'estado_vida' => 'activa', 'es_NPC' => 0, 'nivel' => 1,
    'raza_id' => $f['raza_id'], 'fue' => 14, 'des' => 12, 'agi' => 20, 'res' => 10,
    'per' => 18, 'inte' => 14, 'car' => 12, 'vol' => 20,
    'puntos_comprados' => 120, 'pp_saldo' => 0,
));
if ($pid > 0) {
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dote_id, 'origen' => 'creacion', 'fecha' => TIME_NOW));
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'defecto_id' => $defecto_id, 'origen' => 'creacion', 'fecha' => TIME_NOW));
    foreach ($rasgos as $rid) {
        $db->insert_query('ope_personaje_rasgos', array('personaje_id' => $pid, 'rasgo_id' => $rid, 'origen' => 'creacion', 'karma_acumulado' => 0, 'estado' => 'activo'));
    }
    foreach ($doms as $did => $nv) {
        $db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $did, 'nivel' => $nv, 'origen' => 'creacion'));
    }
    $ficha = ope7_pj_get($pid);
    $val = ope7_pj_validar_ficha($ficha, array(
        'dotes' => array($dote_id), 'defectos' => array($defecto_id),
        'rasgos' => $rasgos, 'dominios' => $doms, 'es_creacion' => true,
    ));
    echo "[3] Ficha válida: " . (count($val['errores']) === 0 ? 'OK (0 errores)' : 'FALLO: ' . implode(' | ', $val['errores'])) . "\n";
    $sec_db = $db->simple_select('ope_atributos_secundarios', '*', "personaje_id = {$pid}", array('limit' => 1));
    $secrow = $db->fetch_array($sec_db);
    echo "    Materializada: PV={$secrow['pv']} PE={$secrow['pe']} PA={$secrow['pa']} (calculado_en=" . (int) $secrow['calculado_en'] . ")\n";
} else {
    echo "[3] Ficha válida: FALLO al guardar (pid=0)\n";
    $pid = 0;
}

// 4) Ficha inválida: balanza de dotes ≠ 0 (defecto solo, sin dote)
if ($pid > 0) {
    $val2 = ope7_pj_validar_ficha($ficha, array(
        'dotes' => array(), 'defectos' => array($defecto_id),
        'rasgos' => $rasgos, 'dominios' => $doms, 'es_creacion' => true,
    ));
    $hay_balanza = false;
    foreach ($val2['errores'] as $e) { if (strpos($e, 'balanza') !== false) $hay_balanza = true; }
    echo "[4] Ficha rota (balanza≠0): " . ($hay_balanza ? 'OK — detectada' : 'FALLO — no se detectó') . "\n";
}

// 5) Puntero de personaje activo (D1.1): ope7_pj_set_activo + ope7_pj_activo
ope7_pj_set_activo(1, 'ope', $pid > 0 ? $pid : 0);
$act = ope7_pj_activo(1);
echo "[5] Puntero activo: " . ($act && $act['tabla'] === 'ope' && $act['id'] === $pid ? "OK (tabla={$act['tabla']} id={$act['id']})" : 'FALLO — ' . json_encode($act)) . "\n";

// 6) Limpieza
if ($pid > 0) {
    $db->delete_query('ope_personajes', "id = {$pid}");
    $db->delete_query('ope_atributos_secundarios', "personaje_id = {$pid}");
    $db->delete_query('ope_personaje_dotes', "personaje_id = {$pid}");
    $db->delete_query('ope_personaje_rasgos', "personaje_id = {$pid}");
    $db->delete_query('ope_dominios_personaje', "personaje_id = {$pid}");
}
ope7_pj_set_activo(1, 'rol', 0);
echo "[6] Limpieza: OK\n";

echo "\n=== DONE ===\n";
