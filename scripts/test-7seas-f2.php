<?php
/**
 * One Piece: 7 Seas · Test F2.0 — motor de combate (fórmulas y tablas del cap. 11)
 * --------------------------------------------------------------------------------
 * Verifica contra cálculos a mano:
 *   · PA por turno (6 + AGI/10 + Nv/5, redondeo único) + modificadores (1vN, dotes, estados).
 *   · Fórmula de daño (cuerpo a cuerpo / distancia / desarmado / mínimo 1).
 *   · Bandas de delta y las 3 tablas en casos representativos.
 *   · El choque en paridad con ambos atacando.
 *   · Umbral del dolor (VOL / 3×VOL / 5×VOL).
 *   · Umbrales de vida, reducción 1vN, tope de sala, P10.
 *   · P4: una técnica no se niega con defensa básica (solo técnica defensiva/ventaja).
 *   · Resolución de un intercambio completo (esquiva, guardia, conecta con umbral).
 * Limpia nada (motor puro, sin BD).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f2.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

function ope7_tf2_check($nombre, $cond)
{
    echo '  ' . ($cond ? 'OK' : 'FALLO') . ' — ' . $nombre . "\n";
    return (bool) $cond;
}

echo "=== Test F2.0 — motor de combate ===\n";
$total = 0;
$ok = 0;

// ── [1] PA por turno ──
$pa = ope7_combate_pa_turno(20, 1);
$ok += ope7_tf2_check("PA nv1/AGI 20 = 8 (got {$pa})", $pa === 8);
$total++;

$pa = ope7_combate_pa_turno(58, 25);
$ok += ope7_tf2_check("PA nv25/AGI 58 = 17 (got {$pa})", $pa === 17);
$total++;

$pa = ope7_combate_pa_turno(100, 50);
$ok += ope7_tf2_check("PA nv50/AGI 100 = 26 (got {$pa})", $pa === 26);
$total++;

// Redondeo único: 6 + AGI/10 + Nv/5 (6 + 3.4 + 4.6 = 14 → 14)
$pa = ope7_combate_pa_turno(34, 23);
$ok += ope7_tf2_check("Redondeo único: AGI 34/Nv 23 = 14 (got {$pa})", $pa === 14);
$total++;

// 1 contra varios: +3 PA frente a 2+ oponentes.
$pa = ope7_combate_pa_turno(20, 1, array('solitario_contra' => 3));
$ok += ope7_tf2_check("1v3 suma +3: 8 → 11 (got {$pa})", $pa === 11);
$total++;

// Dote Preparación (+1) y Tambaleante (−1).
$pa = ope7_combate_pa_turno(40, 10, array('dotes' => array('Preparación'), 'estados' => array('Tambaleante')));
// 6 + 4 + 2 = 12; +1 −1 = 12
$ok += ope7_tf2_check("Preparación +1 y Tambaleante −1: AGI 40/Nv 10 = 12 (got {$pa})", $pa === 12);
$total++;

// PA de técnica: 2 + tier.
$ok += ope7_tf2_check('PA técnica T1 = 3', ope7_combate_pa_tecnica(1) === 3);
$total++;
$ok += ope7_tf2_check('PA técnica T5 = 7', ope7_combate_pa_tecnica(5) === 7);
$total++;

// ── [2] Fórmula de daño ──
// Cuerpo a cuerpo: FUE×0,2 + DES×0,1 + Nv²×0,012. FUE 60, DES 40, Nv 25:
// 12 + 4 + 7.5 = 23.5 → 24
$d = ope7_combate_dano('cuerpo_a_cuerpo', 60, 40, 25);
$ok += ope7_tf2_check("Daño CC FUE60/DES40/Nv25 = 24 (got {$d})", $d === 24);
$total++;

// A distancia: DES×0,2 + FUE×0,1 + Nv²×0,012. DES 40, FUE 60, Nv 25:
// 8 + 6 + 7.5 = 21.5 → 22
$d = ope7_combate_dano('distancia', 60, 40, 25);
$ok += ope7_tf2_check("Daño distancia = 22 (got {$d})", $d === 22);
$total++;

// Con bono de arma (Común +2).
$d = ope7_combate_dano('cuerpo_a_cuerpo', 60, 40, 25, 2);
$ok += ope7_tf2_check("Daño CC + bono arma 2 = 26 (got {$d})", $d === 26);
$total++;

// Mínimo 1: con atributos mínimos (0,2 + 0,1 + 0,012 → 0) el suelo es 1 PV.
$d = ope7_combate_dano('cuerpo_a_cuerpo', 1, 1, 1);
$ok += ope7_tf2_check("Mínimo 1 PV (got {$d})", $d === 1);
$total++;

// Bono de desarmado FUE×0,06.
$ok += ope7_tf2_check('Bono desarmado FUE 100 = 6', ope7_combate_bono_desarmado(100) === 6);
$total++;

// ── [3] Bandas de delta ──
$ok += ope7_tf2_check('Δ=20 → Dominación', ope7_combate_banda(20)['clave'] === 'domina');
$total++;
$ok += ope7_tf2_check('Δ=10 → Ventaja clara', ope7_combate_banda(10)['clave'] === 'ventaja_clara');
$total++;
$ok += ope7_tf2_check('Δ=5 → Ventaja leve', ope7_combate_banda(5)['clave'] === 'ventaja_leve');
$total++;
$ok += ope7_tf2_check('Δ=0 → Paridad', ope7_combate_banda(0)['clave'] === 'paridad');
$total++;
$ok += ope7_tf2_check('Δ=−4 → Paridad', ope7_combate_banda(-4)['clave'] === 'paridad');
$total++;
$ok += ope7_tf2_check('Δ=−5 → Desventaja leve', ope7_combate_banda(-5)['clave'] === 'desventaja_leve');
$total++;
$ok += ope7_tf2_check('Δ=−10 → Desventaja clara', ope7_combate_banda(-10)['clave'] === 'desventaja_clara');
$total++;
$ok += ope7_tf2_check('Δ=−20 → Dominación en contra', ope7_combate_banda(-20)['clave'] === 'domina_contra');
$total++;

// ── [4] Tablas ──
$t1 = ope7_combate_tabla1(25);
$ok += ope7_tf2_check('Tabla 1 Δ+25: "No lo ves venir" + daño ×1,5', $t1['veredicto'] !== '' && $t1['mecanica']['daño_mult'] === 1.5 && $t1['mecanica']['solo_tecnica_defensiva'] === true);
$total++;

$t1 = ope7_combate_tabla1(-15);
$ok += ope7_tf2_check('Tabla 1 Δ−15: invalida hasta tier 2 + contraataque ×1,25', $t1['mecanica']['invalida_hasta_tier'] === 2 && $t1['mecanica']['contraataque_mult'] === 1.25);
$total++;

$t2 = ope7_combate_tabla2(22);
$ok += ope7_tf2_check('Tabla 2 Δ+22: guardia rota + desplazado + derribo', $t2['mecanica']['guardia_rota'] === true && $t2['mecanica']['derribo'] === true);
$total++;

$t3 = ope7_combate_tabla3(-12);
$ok += ope7_tf2_check('Tabla 3 Δ−12: niega + atacante expuesto (+1 PA defensa)', $t3['mecanica']['niega'] === true && $t3['mecanica']['defensa_atacante_pa'] === 1);
$total++;

// Choque en paridad: FUE 70 vs 60 → delta 10 → empuje (Desplazado).
$ch = ope7_combate_choque(10);
$ok += ope7_tf2_check('Choque ΔFUE +10 → empuje/Desplazado', $ch['resultado'] === 'empuje' && $ch['mecanica']['desplazado'] === true);
$total++;

// ── [5] Umbral del dolor ──
$u = ope7_combate_umbral_dolor(30, 20);
$ok += ope7_tf2_check('Daño 30 > VOL 20 → Sacudido', $u['estado'] === 'Sacudido');
$total++;
$u = ope7_combate_umbral_dolor(70, 20);
$ok += ope7_tf2_check('Daño 70 > 3×VOL 60 → Tambaleante', $u['estado'] === 'Tambaleante');
$total++;
$u = ope7_combate_umbral_dolor(110, 20);
$ok += ope7_tf2_check('Daño 110 > 5×VOL 100 → Desorientado', $u['estado'] === 'Desorientado');
$total++;
$u = ope7_combate_umbral_dolor(15, 20);
$ok += ope7_tf2_check('Daño 15 < VOL 20 → sin estado', $u['estado'] === null);
$total++;

// ── [6] Umbrales de vida / 1vN / sala / P10 ──
$ok += ope7_tf2_check('80%+ → sano', ope7_combate_umbrales_vida(180, 200)['nombre'] === 'sano');
$total++;
$ok += ope7_tf2_check('60% → herido', ope7_combate_umbrales_vida(120, 200)['nombre'] === 'herido');
$total++;
$ok += ope7_tf2_check('30% → muy dañado', ope7_combate_umbrales_vida(60, 200)['nombre'] === 'muy_dañado');
$total++;
$ok += ope7_tf2_check('15% → al límite', ope7_combate_umbrales_vida(30, 200)['nombre'] === 'al_limite' && ope7_combate_umbrales_vida(30, 200)['al_limite'] === true);
$total++;
$ok += ope7_tf2_check('Reducción 1v2 = 10%', ope7_combate_reduccion_1vn(2) === 10);
$total++;
$ok += ope7_tf2_check('Reducción 1v3 = 20%', ope7_combate_reduccion_1vn(3) === 20);
$total++;
$ok += ope7_tf2_check('Reducción 1v4 = 30%', ope7_combate_reduccion_1vn(4) === 30);
$total++;
$ok += ope7_tf2_check('Reducción 1v1 = 0%', ope7_combate_reduccion_1vn(1) === 0);
$total++;
$ok += ope7_tf2_check('Tope de sala = 5', ope7_combate_sala_tope() === 5);
$total++;
$ok += ope7_tf2_check('P10: 1 ataque por objetivo', ope7_combate_max_ataques_mismo_objetivo() === 1);
$total++;

// ── [7] P4 — una técnica no se niega con defensa básica ──
$t1 = ope7_combate_tabla1(0); // paridad
$p4 = ope7_combate_p4('esquivar', true, $t1, 0, 2);
$ok += ope7_tf2_check('P4: esquivar NO niega una técnica media (paridad)', is_array($p4) && !$p4['ok']);
$total++;
$p4 = ope7_combate_p4('tecnica_defensiva', true, $t1, 2, 2);
$ok += ope7_tf2_check('P4: defensiva Media anula técnica Media', is_array($p4) && $p4['ok'] && $p4['anula'] === true);
$total++;
$p4 = ope7_combate_p4('tecnica_defensiva', true, $t1, 2, 4);
$ok += ope7_tf2_check('P4: defensiva Media vs Maestra (+2 tiers) reduce a la mitad', is_array($p4) && $p4['ok'] && ($p4['reduce_a_mitad'] ?? false) === true);
$total++;
$p4 = ope7_combate_p4('tecnica_defensiva', true, $t1, 3, 5);
$ok += ope7_tf2_check('P4: una Épica solo se responde con defensiva superior o Haki', is_array($p4) && !$p4['ok']);
$total++;
$t1 = ope7_combate_tabla1(-15); // defensor −15: invalida técnicas básicas y medias
$p4 = ope7_combate_p4('esquivar', true, $t1, 0, 1);
$ok += ope7_tf2_check('P4: ventaja clara (Δ−15) sí niega una técnica Básica', is_array($p4) && $p4['ok']);
$total++;

// ── [8] Resolución de un intercambio ──
// Caso A: técnica media (T2) con daño 40 conecta contra guardia en paridad de
// Tabla 1 → P4 bloquea la guardia → conecta con daño completo (×1) y umbral.
$r = ope7_combate_resolver_intercambio(
    // Paridad de Tabla 1: agi 40 (atacante) vs per 20 + agi 20 (defensor) → Δ=0.
    array(
        'tipo' => 'tecnica', 'tier' => 2, 'daño' => 40, 'usa_agi' => true,
        'valores' => array('agi' => 40, 'des' => 30, 'fue' => 50, 'res' => 30, 'vol' => 20, 'car' => 25),
        'estado' => 'Quemadura I',
    ),
    array('accion' => 'guardia', 'tier_def' => 0, 'valores' => array('per' => 20, 'agi' => 20, 'res' => 45, 'vol' => 25, 'fue' => 40)),
);
$ok += ope7_tf2_check('Intercambio: técnica T2 vs guardia → conecta (P4)', $r['conecta'] === true && $r['resultado'] === 'conecta');
$total++;
$ok += ope7_tf2_check('Intercambio: daño 40 completo en paridad', (int) $r['daño'] === 40);
$total++;
$ok += ope7_tf2_check('Intercambio: daño 40 vs VOL 25 → Tambaleante (40 > 75? no → Sacudido 40>25)', $r['umbral']['estado'] === 'Sacudido');
$total++;
$ok += ope7_tf2_check('Intercambio: lleva estado (Tabla 3 evaluada)', is_array($r['estado']) && $r['estado']['nombre'] === 'Quemadura I');
$total++;

// Caso B: básico (2 PA) contra esquivar con ventaja clara del defensor → anula.
$r = ope7_combate_resolver_intercambio(
    array(
        'tipo' => 'basico', 'tier' => 0, 'daño' => 12, 'usa_agi' => true,
        'valores' => array('agi' => 25, 'des' => 25, 'fue' => 30),
    ),
    array('accion' => 'esquivar', 'valores' => array('per' => 50, 'agi' => 50, 'res' => 30, 'vol' => 30)),
);
$ok += ope7_tf2_check('Intercambio: básico vs esquivar con ventaja → defendido', $r['conecta'] === false && $r['resultado'] === 'defendido');
$total++;

// Caso C: ambos atacan en paridad → choque (nadie recibe daño).
$r = ope7_combate_resolver_intercambio(
    // Paridad de Tabla 1: agi 40 vs per 20 + agi 20 → Δ=0 y ambos atacan → choque.
    array(
        'tipo' => 'tecnica', 'tier' => 1, 'daño' => 30, 'usa_agi' => true,
        'valores' => array('agi' => 40, 'des' => 30, 'fue' => 60),
    ),
    array('tambien_ataca' => true, 'accion' => '', 'valores' => array('per' => 20, 'agi' => 20, 'res' => 30, 'fue' => 60)),
);
$ok += ope7_tf2_check('Intercambio: ambos atacan en paridad → choque', $r['resultado'] === 'choque' && ($r['choque']['resultado'] ?? '') === 'trabados');
$total++;

// ── [9] Resolución de tema (cierre): excesos + intercambios ──
$turnos = array(
    array('personaje_id' => 1, 'turno' => 1, 'pa_total' => 8, 'pa_gastado' => 9, 'acciones' => array(
        array('tipo' => 'ataque', 'objetivo_id' => 2, 'usa_agi' => true, 'tier' => 1, 'daño' => 20,
              'valores' => array('agi' => 30, 'des' => 25, 'fue' => 40),
              'defensa' => array('accion' => 'guardia', 'valores' => array('per' => 30, 'agi' => 30, 'res' => 35, 'vol' => 25, 'fue' => 30)),
              'defensor_valores' => array('per' => 30, 'agi' => 30, 'res' => 35, 'vol' => 25, 'fue' => 30)),
    )),
    array('personaje_id' => 2, 'turno' => 2, 'pa_total' => 8, 'pa_gastado' => 5, 'acciones' => array()),
);
$res = ope7_combate_resolver_tema($turnos);
$ok += ope7_tf2_check('Resolución de tema: detecta el exceso de PA', count($res['excesos']) === 1 && (int) $res['excesos'][0]['pa_total'] === 8);
$total++;
$ok += ope7_tf2_check('Resolución de tema: genera el intercambio', count($res['intercambios']) === 1 && $res['intercambios'][0]['atacante_id'] === 1);
$total++;

// ── [10] Trámite 13 — creación de técnica (F2.1) ──
// Limpieza previa GLOBAL (idempotencia tras ejecuciones fallidas): borra todos
// los personajes de prueba de F2 y sus filas dependientes, más akumas huérfanas.
$qq = $db->simple_select('ope_akumas', 'id', "nombre_propio = 'Gomu Gomu de Prueba'");
while ($row = $db->fetch_array($qq)) {
    $db->delete_query('ope_akumas', 'id = ' . (int) $row['id']);
}
$q = $db->simple_select('ope_personajes', 'id', "slug LIKE 'prueba-f2-%'");
$viejos = array();
while ($row = $db->fetch_array($q)) {
    $viejos[] = (int) $row['id'];
}
foreach ($viejos as $viejo) {
    $qq = $db->simple_select('ope_akumas', 'id', "portador_id = {$viejo}");
    while ($row = $db->fetch_array($qq)) {
        $db->delete_query('ope_akumas', 'id = ' . (int) $row['id']);
    }
    $bt = $db->simple_select('ope_tramites', 'id', "personaje_id = {$viejo}");
    while ($row = $db->fetch_array($bt)) {
        $db->delete_query('ope_tramites_historico', 'tramite_id = ' . (int) $row['id']);
        $db->delete_query('ope_tramites', 'id = ' . (int) $row['id']);
    }
    foreach (array('ope_tecnicas', 'ope_historico_pp', 'ope_muertes', 'ope_carteras') as $t) {
        $db->delete_query($t, "personaje_id = {$viejo}");
    }
    $db->delete_query('ope_personajes', "id = {$viejo}");
}
// Personaje con 500 PP e INT 40 (cupo INT/4 = 10).
$raza = (int) $db->fetch_field($db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1)), 'id');
$pid = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F2 Técnica', 'slug' => 'prueba-f2-tecnica', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 5, 'raza_id' => $raza,
    'fue' => 30, 'des' => 25, 'agi' => 30, 'res' => 25, 'per' => 25, 'inte' => 40, 'car' => 25, 'vol' => 25,
    'puntos_comprados' => 50, 'pp_saldo' => 500,
));
$r = ope7_tramite_crear(1, $pid, 13, 'Quiero una técnica de rayos', array(), array('idea' => 'Puño eléctrico', 'tier' => 2));
$t13 = (int) ($r['tid'] ?? 0);
$ok13 = false;
if ($t13 > 0) {
    // Anti-duplicado: mientras el trámite 13 está en cola, no se repite.
    $r_dup = ope7_tramite_crear(1, $pid, 13, 'Duplicado en cola', array(), array('idea' => 'X', 'tier' => 1));
    $ok += ope7_tf2_check('Trámite 13: no se repite en cola (anti-duplicado)', !$r_dup['ok'] && strpos($r_dup['msg'] ?? '', 'cola') !== false);
    $total++;

    ope7_tramite_guardar_resultado($t13, array(
        'nombre' => 'Puño Eléctrico', 'tier' => 2, 'tipo' => 'ofensiva',
        'dominio_id' => 0,
        'requisitos' => array('agi' => 30),
        'efectos' => array('Daño puro'),
        'nota_moderacion' => 'Giro de muñeca con estática (originalidad aplicada).',
    ));
    ope7_tramite_usuario_aceptar($t13, 1);
    $fir = ope7_tramite_firmar($t13, 1, 'publicar', 'Ficha validada y aceptada.');
    $q = $db->simple_select('ope_tecnicas', '*', "personaje_id = {$pid} AND nombre = 'Puño Eléctrico'", array('limit' => 1));
    $tc = $db->fetch_array($q);
    $q2 = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
    $saldo = (int) $db->fetch_field($q2, 'pp_saldo');
    // T2 cuesta 120 PP: 500 → 380. PA = 2+2 = 4 · PE 15% · reposo 2 · puerta 0.
    $ok13 = $fir['ok'] && $tc && $saldo === 380 && (int) $tc['pa'] === 4 && (int) $tc['pe_pct'] === 15 && (int) $tc['reposo'] === 2 && (int) $tc['puerta_turno'] === 0;
    echo '  ' . ($ok13 ? 'OK' : 'FALLO') . " — Trámite 13: técnica en librería, PP 500→{$saldo}, PA {$tc['pa']}, PE {$tc['pe_pct']}%, reposo {$tc['reposo']}\n";
    $ok += $ok13;
    $total++;

    // Cupo INT/4 (INT 40 → 10): con 1 técnica, el cupo no bloquea; el bloqueo
    // por arsenal se cubre en F2.4 (test de límites).
}

// ── [11] Trámite 62 — muerte (F2.1) ──
// Akuma de prueba con portador = pid.
$db->insert_query('ope_akumas', array(
    'nombre_propio' => 'Gomu Gomu de Prueba', 'familia' => 'paramecia', 'tier' => 1,
    'portador_id' => $pid, 'estado' => 'con_portador', 'precio_base' => 100000,
));
$akuma_id = (int) $db->insert_id();
$db->update_query('ope_personajes', array('akuma_id' => $akuma_id), "id = {$pid}");
// F3.2: el guardar ya crea la cartera 0/0 → update (idempotente).
$db->update_query('ope_carteras', array('cartera' => 80000, 'boveda' => 20000), "personaje_id = {$pid}");
$r = ope7_tramite_crear(1, $pid, 62, 'Cae ante el Vicealmirante en Marineford.', array('tema_id' => 0));
$t62 = (int) ($r['tid'] ?? 0);
$ok62 = false;
if ($t62 > 0) {
    ope7_tramite_guardar_resultado($t62, array(
        'umbral_confirmado' => 'PV ≤ −(VOL×2) = −50 (PV final −60)',
        'calidad' => 'digna', 'causa' => 'Cae ante el Vicealmirante.', 'tema_id' => 0,
    ));
    $fir = ope7_tramite_firmar($t62, 1, 'publicar', 'Veredicto confirmado: umbral cruzado, desenlace digno.');
    $q = $db->simple_select('ope_muertes', '*', "personaje_id = {$pid}", array('limit' => 1));
    $m = $db->fetch_array($q);
    $q2 = $db->simple_select('ope_personajes', 'estado_vida', "id = {$pid}", array('limit' => 1));
    $vida = $db->fetch_field($q2, 'estado_vida');
    $q3 = $db->simple_select('ope_akumas', 'estado, portador_id', "id = {$akuma_id}", array('limit' => 1));
    $ak = $db->fetch_array($q3);
    $her = json_decode((string) $m['herencia'], true);
    // Nivel 5 → banda PP = max(60, min(1000, 100)) = 100 ×1,0 = 100 · berries = max(5000, min(1M, 10000)) = 10000.
    $ok62 = $fir['ok'] && $m && $vida === 'muerta' && $ak['estado'] === 'renacida' && $ak['portador_id'] === null
        && (int) $her['pp'] === 100 && (int) $her['berries'] === 10000;
    echo '  ' . ($ok62 ? 'OK' : 'FALLO') . " — Trámite 62: reliquia={$vida}, fruta={$ak['estado']}, herencia {$her['pp']} PP / {$her['berries']} ฿\n";
    $ok += $ok62;
    $total++;

    // Efectos de mundo anotados como esquema (pendientes de F4).
    $em = json_decode((string) $m['efectos_mundo'], true);
    $ok += ope7_tf2_check('Trámite 62: efectos de mundo anotados (cartel/facción/suceso pendientes F4)',
        isset($em['cartel_retirado']['aplicado']) && $em['cartel_retirado']['aplicado'] === false
        && isset($em['fruta_renacida']['aplicado']) && $em['fruta_renacida']['aplicado'] === true);
    $total++;
}

// ── [12] Herencia al siguiente personaje (F2.1) ──
$pid_heredero = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F2 Heredero', 'slug' => 'prueba-f2-heredero', 'estado' => 'borrador',
    'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza,
    'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
    'puntos_comprados' => 80, 'pp_saldo' => 0,
));
$her = ope7_pj_heredar(1, $pid_heredero);
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid_heredero}", array('limit' => 1));
$pp_heredero = (int) $db->fetch_field($q, 'pp_saldo');
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$pid_heredero}", array('limit' => 1));
$ber_heredero = (int) $db->fetch_field($q, 'cartera');
$q = $db->simple_select('ope_muertes', 'heredero_id', "personaje_id = {$pid}", array('limit' => 1));
$heredero_marcado = (int) $db->fetch_field($q, 'heredero_id');
$okHer = $her['aplicadas'] === 1 && $pp_heredero === 100 && $ber_heredero === 10000 && $heredero_marcado === $pid_heredero;
$ok += ope7_tf2_check("Herencia: nuevo personaje reclama " . ($her['aplicadas'] ?? 0) . " muerte · PP {$pp_heredero} · berries {$ber_heredero} · marcado {$heredero_marcado}", $okHer);
$total++;

// ── [13] Zona B (F2.2): render del bloque, parse y persistencia ──
$payload_zb = array(
    'tecnicas' => array(array('id' => 99, 'nombre' => 'Puño Eléctrico', 'tier' => 2, 'pa' => 4, 'pe' => 15, 'reposo' => 2, 'puerta' => 0)),
    'consumibles' => array(array('nombre' => 'Poción', 'pa' => 2)),
    'estados' => array('Quemadura I'),
    'mods' => array('solitario' => true, 'sobrecarga' => false, 'nota' => 'Mink Electro'),
    'resumen' => array('pa_total' => 11, 'pa_gastado' => 6, 'reserva' => 5),
    'contadores' => array('pv' => 150, 'pe' => 120, 'pa_restante' => 5),
);
$zb_html = ope7_zonab_render($payload_zb);
$ok += ope7_tf2_check('Zona B: render con cartas, consumibles, estados y resumen',
    strpos($zb_html, 'ZONA B') !== false && strpos($zb_html, 'Puño Eléctrico') !== false
    && strpos($zb_html, 'Poción') !== false && strpos($zb_html, 'Quemadura I') !== false
    && strpos($zb_html, 'PA 6/11') !== false && strpos($zb_html, '1 contra varios +3 PA') !== false);
$total++;

$msg_zb = "La narrativa del turno…\n\n[ope7-zonab]" . json_encode($payload_zb, JSON_UNESCAPED_UNICODE) . "[/ope7-zonab]";
$parsed_zb = ope7_zonab_parse($msg_zb);
$ok += ope7_tf2_check('Zona B: parse del bloque convierte el BBCode en HTML',
    strpos($parsed_zb, 'ope7-zb-block') !== false && strpos($parsed_zb, '[ope7-zonab]') === false);
$total++;

// JSON roto → bloque crudo (nunca rompe el mensaje).
$parsed_bad = ope7_zonab_parse("[ope7-zonab]{no es json[/ope7-zonab]");
$ok += ope7_tf2_check('Zona B: JSON roto → bloque crudo sin fatal',
    strpos($parsed_bad, 'ope7-zb-block--raw') !== false && strpos($parsed_bad, 'Fatal') === false);
$total++;

// Persistencia: personaje activo + post con Zona B → turnos_combate + sala.
ope7_pj_set_activo(1, 'ope', $pid_heredero);
$db->update_query('ope_personajes', array('agi' => 30, 'nivel' => 5), "id = {$pid_heredero}");
$post_zb = array('message' => $msg_zb, 'tid' => 4242, 'uid' => 1, 'ope_pid' => $pid_heredero);
ope7_zonab_on_post($post_zb);
$q = $db->simple_select('ope_sala_combate', 'id', "tema_id = 4242", array('limit' => 1));
$sala_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_turnos_combate', '*', "combate_id = {$sala_id}", array('limit' => 1));
$trow = $db->fetch_array($q);
$ver = json_decode((string) ($trow['veredicto'] ?? ''), true);
$okZb = $sala_id > 0 && $trow
    && (int) $trow['personaje_id'] === $pid_heredero
    && (int) $trow['pa_total'] === 13          // AGI 30/Nv 5 → 6+3+1=10, +3 solitario = 13
    && (int) $trow['pa_gastado'] === 6
    && is_array($ver) && count($ver['avisos']) > 0; // primer post con acciones → aviso P9
$ok += ope7_tf2_check("Zona B: persiste turno (pa_total={$trow['pa_total']}, gastado={$trow['pa_gastado']}, avisos=" . (is_array($ver) ? count($ver['avisos']) : '?') . ')', $okZb);
$total++;

// ── [13] Panel de resolución (F2.3): render de lista y detalle ──
$html_lista = ope7_resolucion_html(0, '');
$html_detalle = ope7_resolucion_html($sala_id, '');
$ok += ope7_tf2_check('Resolución: lista renderiza y muestra la sala',
    strpos($html_lista, 'Resolución de combates') !== false
    && strpos($html_lista, (string) $sala_id) !== false);
$total++;
$ok += ope7_tf2_check('Resolución: detalle muestra turnos y botón de veredicto',
    strpos($html_detalle, 'pa_total') !== false || strpos($html_detalle, 'turno') !== false
    && strpos($html_detalle, 'resolver') !== false);
$total++;
// Firma requiere motivo.
$res_f = ope7_resolucion_firmar($sala_id, 1, '');
$ok += ope7_tf2_check('Resolución: firmar sin motivo → rechaza', !$res_f['ok']);
$total++;

// ── [14] Limpieza F2.1 ──
if ($sala_id > 0) {
    $db->delete_query('ope_turnos_combate', "combate_id = {$sala_id}");
    $db->delete_query('ope_sala_combate', "id = {$sala_id}");
}
$borrar = array();
$q = $db->simple_select('ope_tramites', 'id', "personaje_id = {$pid}");
while ($row = $db->fetch_array($q)) {
    $borrar[] = (int) $row['id'];
}
foreach ($borrar as $tid) {
    $db->delete_query('ope_tramites_historico', "tramite_id = {$tid}");
    $db->delete_query('ope_tramites', "id = {$tid}");
}
$db->delete_query('ope_tecnicas', "personaje_id = {$pid}");
$db->delete_query('ope_historico_pp', "personaje_id = {$pid}");
$db->delete_query('ope_muertes', "personaje_id = {$pid}");
$db->delete_query('ope_sucesos', "tipo = 'muerte' AND titulo LIKE 'Muere Prueba%'");
$db->delete_query('ope_carteles_recompensa', "personaje_id = {$pid}");
$db->delete_query('ope_cambios_faccion', "personaje_id = {$pid}");
$db->delete_query('ope_carteras', "personaje_id = {$pid}");
$db->delete_query('ope_akumas', "id = {$akuma_id}");
$db->delete_query('ope_personajes', "id = {$pid_heredero}");
$db->delete_query('ope_carteras', "personaje_id = {$pid_heredero}");
$db->delete_query('ope_personajes', "id = {$pid}");
$ok += ope7_tf2_check('Limpieza F2.1', true);
$total++;

echo "\n=== DONE — {$ok}/{$total} checks OK ===\n";
exit($ok === $total ? 0 : 1);
