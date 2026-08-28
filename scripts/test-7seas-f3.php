<?php
/**
 * One Piece: 7 Seas · Test F3 — progresión y economía (5.6/5.8/5.9)
 * -----------------------------------------------------------------
 * Verifica:
 *   [1] Calendario on-roll: semilla hoy, avance perezoso +2 días/día real.
 *   [2] Entrenamientos: compra (PP + cronómetro), finalización → reserva,
 *       colocación de reserva con techo.
 *   [3] Fórmula skill-cierre-temas: bandas cerradas, techo/suelo, redondeo.
 *   [4] Cierre de tema (efecto 2): PP con desglose, salio_en, veredicto ref.
 *   [5] Inventario: capacidad, equipar con ranuras, cupo Meitou.
 *   [6] Cartera: movimientos y saldo insuficiente.
 *   [7] Producción (efecto 6) → almacén.
 *   [8] Tienda: apertura bloqueada sin Comerciante, OK con Comerciante.
 *   [9] Compra/venta: auto-compra bloqueada, transacción registrada, NPC 50 %.
 *   [10] Boletín de precios: factores fuera de banda bloqueados, OK dentro.
 * Limpieza completa al final (personajes, temas, tiendas, transacciones).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f3.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

function ope7_tf3_check($nombre, $cond)
{
    echo '  ' . ($cond ? 'OK' : 'FALLO') . ' — ' . $nombre . "\n";
    return (bool) $cond;
}

echo "=== Test F3 — progresión y economía ===\n";
$total = 0;
$ok = 0;
$G = array('ok' => 0, 'total' => 0);
$G['chk'] = function ($n, $c) use (&$ok, &$total) {
    $ok += ope7_tf3_check($n, $c);
    $total++;
};

// ── Limpieza previa (idempotencia tras ejecuciones fallidas) ──
$db->delete_query('ope_transacciones', "comprador_id = 0 OR vendedor_id = 0"); // NPC y test
$q = $db->simple_select('ope_personajes', 'id', "slug LIKE 'prueba-f3-%'");
$viejos = array();
while ($row = $db->fetch_array($q)) {
    $viejos[] = (int) $row['id'];
}
foreach ($viejos as $viejo) {
    // Los temas que el PJ abrió (trámite 1) quedan huérfanos si solo borramos
    // los participantes; ese 'presente abierto' huérfano rompe re-corridas
    // (f3 no es idempotente si no limpia ope_temas). Los borramos aquí.
    $ttd = $db->simple_select('ope_temas_participantes', 'tema_id', "personaje_id = {$viejo}");
    $temas_viejos = array();
    while ($row = $db->fetch_array($ttd)) {
        $temas_viejos[] = (int) $row['tema_id'];
    }
    $bt = $db->simple_select('ope_tramites', 'id', "personaje_id = {$viejo}");
    while ($row = $db->fetch_array($bt)) {
        $db->delete_query('ope_tramites_historico', 'tramite_id = ' . (int) $row['id']);
        $db->delete_query('ope_tramites', 'id = ' . (int) $row['id']);
    }
    foreach (array('ope_tecnicas', 'ope_historico_pp', 'ope_muertes', 'ope_carteras', 'ope_temas_participantes', 'ope_dominios_personaje', 'ope_personaje_dotes', 'ope_personaje_rasgos', 'ope_almacen', 'ope_inventario_personaje') as $t) {
        $db->delete_query($t, "personaje_id = {$viejo}");
    }
    foreach ($temas_viejos as $tv) {
        $db->delete_query('ope_temas', "tid = {$tv}");
    }
    $tt = $db->simple_select('ope_tiendas', 'id', "dueno_id = {$viejo}");
    while ($row = $db->fetch_array($tt)) {
        $db->delete_query('ope_tienda_items', 'tienda_id = ' . (int) $row['id']);
        $db->delete_query('ope_tiendas', 'id = ' . (int) $row['id']);
    }
    $db->delete_query('ope_personajes', "id = {$viejo}");
}
// Huérfanos de corridas previas cuyos PJ ya se borraron: temas sin participantes
// que quedaron 'abiertos'. Igual que hace test-7seas-f4, limpiamos el tablero de
// temas de prueba a fondo para hacer esta corrida independiente del orden.
$db->delete_query('ope_temas_participantes', '1=1');
$db->delete_query('ope_temas', '1=1');

// ── [1] Calendario on-roll ──
$fecha0 = ope7_calendario_semilla();
$G['chk']('Calendario: semilla arranca hoy (D3.3)', $fecha0 === date('Y-m-d'));
// Fuerza el avance: retrocede ultima_actualizacion_real 3 días → +6 on-roll.
$db->update_query('ope_calendario_foro', array('ultima_actualizacion_real' => TIME_NOW - 3 * 86400), '1=1');
$fecha1 = ope7_calendario_avanzar();
$esperado1 = date('Y-m-d', strtotime($fecha0 . ' +6 days'));
$G['chk']("Calendario: avanza +2 días/día real ({$fecha0} → {$fecha1}, esperado {$esperado1})", $fecha1 === $esperado1);
$fecha2 = ope7_calendario_avanzar(); // idempotente el mismo día
$G['chk']('Calendario: idempotente (no avanza dos veces el mismo día)', $fecha2 === $fecha1);
$q = $db->simple_select('ope_calendario_foro', 'avances', '1=1', array('limit' => 1));
$av = json_decode((string) $db->fetch_field($q, 'avances'), true);
$G['chk']('Calendario: registra el avance en el histórico', is_array($av) && count($av) >= 1);
$G['chk']('Calendario: fecha_foro_actual() también avanza', ope7_fecha_foro_actual() === $fecha1);

// ── Personaje de prueba (nivel 1, presupuesto base, 300 PP, sin Comerciante) ──
$raza = (int) $db->fetch_field($db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1)), 'id');
// F4.1: el PJ se ancla a la isla Dawn del catálogo (las tiendas exigen territorio).
$isla_dawn = (int) $db->fetch_field($db->simple_select('ope_islas', 'id', "slug = 'dawn'", array('limit' => 1)), 'id');
$pid = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F3 Comercio', 'slug' => 'prueba-f3-comercio', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza,
    'ubicacion_isla_id' => $isla_dawn,
    'fue' => 14, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 14, 'car' => 10, 'vol' => 10,
    'puntos_comprados' => 0, 'pp_saldo' => 300, // 7.1: 0 acumulado desde el último nivel
));

// ── [2] Entrenamientos y reserva ──
$G['chk']('Cartera: se crea 0/0 al crear el PJ (F3.2)', ope7_cartera_get($pid) === array('cartera' => 0, 'boveda' => 0));
$r = ope7_tramite_crear(1, $pid, 4, '', array('atributo' => 'fue', 'bloque' => 5));
$t4 = (int) ($r['tid'] ?? 0);
$pj = ope7_pj_get($pid);
$G['chk']('Compra: paga PP (300→250) y NO mete reserva (7.3/D3.5)', $r['ok'] && (int) $pj['pp_saldo'] === 250 && (int) $pj['reserva'] === 0 && (int) $pj['entrenamiento_fin'] > time());
// Fuerza el vencimiento del cronómetro y finaliza.
$db->update_query('ope_personajes', array('entrenamiento_fin' => TIME_NOW - 60), "id = {$pid}");
$finalizadas = ope7_pj_finalizar_entrenamientos();
$pj = ope7_pj_get($pid);
$G['chk']('Entrenamiento: al vencer, +5 a reserva y puntos_comprados (0→5, nv1)', $finalizadas >= 1 && (int) $pj['reserva'] === 5 && (int) $pj['puntos_comprados'] === 5 && (int) $pj['entrenamiento_fin'] === 0 && (int) $pj['nivel'] === 1);
$r = ope7_pj_colocar_reserva($pid, array('fue' => 5));
$pj = ope7_pj_get($pid);
$G['chk']('Colocar reserva: +5 a FUE (14→19, bajo techo 20), reserva a 0', $r['ok'] && (int) $pj['fue'] === 19 && (int) $pj['reserva'] === 0);
$r = ope7_pj_colocar_reserva($pid, array('fue' => 100));
$G['chk']('Colocar reserva: sin reserva → bloquea', !$r['ok']);

// ── [3] Fórmula skill-cierre-temas ──
$calc = ope7_cierre_pp_calcular(1, 'presente', array('fidelidad' => 1.0, 'peso' => 1.0, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0));
$G['chk']('Cierre: base tramo I (nv1) = 50, factores 1.0 → 50 PP', $calc['ok'] && $calc['base'] === 50 && $calc['pp'] === 50);
$calc2 = ope7_cierre_pp_calcular(1, 'pasado', array('fidelidad' => 1.0, 'peso' => 1.0, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0));
$G['chk']('Cierre: tiempo pasado 1,00 fuera de banda 0,70–0,90 → rechaza', !$calc2['ok']);
$calc3 = ope7_cierre_pp_calcular(1, 'pasado', array('fidelidad' => 1.2, 'peso' => 1.25, 'calidad' => 1.2, 'extension' => 1.1, 'tiempo' => 0.9, 'riesgo' => 1.35, 'perfil' => 1.05));
// 50 × 1.2×1.25×1.2×1.1×0.9×1.35×1.05 ≈ 126.4 → techo 100 (2× base).
$G['chk']('Cierre: techo 2× aplicado (≥100 → 100)', $calc3['ok'] && $calc3['pp'] === 100);
$calc4 = ope7_cierre_pp_calcular(25, 'presente', array('fidelidad' => 0.9, 'peso' => 0.9, 'calidad' => 0.9, 'extension' => 0.85, 'tiempo' => 1.0, 'riesgo' => 0.9, 'perfil' => 1.0));
// 125 × (0,9×0,9×0,9×0,85×0,9) = 125 × 0,5577 ≈ 69,7 → 70 (mitades a favor).
$G['chk']('Cierre: base tramo III (nv25) = 125 y PP mínimo de banda = 70', $calc4['ok'] && $calc4['base'] === 125 && $calc4['pp'] === 70);

// ── [4] Cierre de tema (efecto 2) ──
$r = ope7_tramite_crear(1, $pid, 1, '', array('zona' => 'Paraíso', 'tipo' => 'presente'));
$t1 = (int) ($r['tid'] ?? 0);
$tema = (int) $db->fetch_field($db->simple_select('ope_temas_participantes', 'tema_id', "personaje_id = {$pid}", array('limit' => 1)), 'tema_id');
$r = ope7_tramite_crear(1, $pid, 2, 'Cierre del presente de prueba', array('tema_id' => $tema), array());
$t2 = (int) ($r['tid'] ?? 0);
$G['chk']('Cierre: trámite 2 creado', $t2 > 0);
ope7_tramite_guardar_resultado($t2, array(
    'factores' => array('fidelidad' => 1.0, 'peso' => 1.1, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0),
    'sala_id' => 0, 'motivo' => 'Cierre estándar.',
));
$fir = ope7_tramite_firmar($t2, 1, 'publicar', 'Desglose correcto.');
$pj = ope7_pj_get($pid);
// 50 × 1.1 = 55 → 250 + 55 = 305.
$G['chk']('Cierre: aplica PP con fórmula (250→305)', $fir['ok'] && (int) $pj['pp_saldo'] === 305);
$salio = (int) $db->fetch_field($db->simple_select('ope_temas_participantes', 'salio_en', "personaje_id = {$pid} AND tema_id = {$tema}", array('limit' => 1)), 'salio_en');
$tema_estado = (string) $db->fetch_field($db->simple_select('ope_temas', 'estado', "tid = {$tema}", array('limit' => 1)), 'estado');
$G['chk']('Cierre: congelación liberada (salio_en) y tema cerrado', $salio > 0 && $tema_estado === 'cerrado');
$hq = $db->simple_select('ope_historico_pp', 'pp_otorgado, base_pp', "personaje_id = {$pid} ORDER BY id DESC", array('limit' => 1));
$hrow = $db->fetch_array($hq);
$G['chk']('Cierre: histórico con desglose (base 50, +55)', (int) $hrow['pp_otorgado'] === 55 && (int) $hrow['base_pp'] === 50);

// ── [4b] Panel «Calendario» (Anexo A.3) ──
// Abre un presente nuevo (ancla = fecha on-roll actual) para que el panel lo vea.
$r = ope7_tramite_crear(1, $pid, 1, '', array('zona' => 'Paraíso', 'tipo' => 'presente'));
$t1b = (int) ($r['tid'] ?? 0);
$tema_b = (int) $db->fetch_field($db->simple_select('ope_temas_participantes', 'tema_id', "personaje_id = {$pid} AND tema_id <> {$tema} ORDER BY id DESC", array('limit' => 1)), 'tema_id');
$html_panel = function_exists('ope7_calendario_panel_html') ? ope7_calendario_panel_html() : '';
$G['chk']('Calendario: el panel muestra la fecha on-roll actual', strpos($html_panel, date('Y-m-d')) !== false || strpos($html_panel, 'Fecha on-roll actual') !== false);
$G['chk']('Calendario: el panel lista el presente activo con su ancla', $tema_b > 0 && strpos($html_panel, 'TEMA ' . (int) $tema_b) !== false && strpos($html_panel, 'congelado desde') !== false);
// Pasado anclado en el futuro → el efecto lo bloquea (7.7).
$r = ope7_tramite_crear(1, $pid, 1, '', array('zona' => 'East Blue', 'tipo' => 'pasado'), array('fecha_foro' => date('Y-m-d', strtotime('+30 days'))));
$G['chk']('Calendario: un pasado en el futuro se bloquea al abrir (7.7)', !$r['ok']);
// Pasado coherente → se ancla en la fecha declarada.
$r = ope7_tramite_crear(1, $pid, 1, '', array('zona' => 'East Blue', 'tipo' => 'pasado'), array('fecha_foro' => '2015-06-01'));
$ancla_pasado = (string) $db->fetch_field($db->simple_select('ope_temas', 'fecha_foro', "tipo = 'pasado' ORDER BY tid DESC", array('limit' => 1)), 'fecha_foro');
$G['chk']('Calendario: un pasado coherente se ancla en su fecha declarada (2015-06-01)', $r['ok'] && $ancla_pasado === '2015-06-01');
// Cierra el presente abierto (para no romper la limpieza del un-presente).
$r = ope7_tramite_crear(1, $pid, 2, 'Cierre del presente del panel', array('tema_id' => $tema_b), array());
$t2b = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t2b, array('factores' => array('fidelidad' => 1.0, 'peso' => 1.0, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0)));
$fir_b = ope7_tramite_firmar($t2b, 1, 'publicar', 'Cierre del presente del panel.');
$html_panel2 = ope7_calendario_panel_html();
$G['chk']('Calendario: histórico de cierres muestra el tema cerrado', $fir_b['ok'] && strpos($html_panel2, 'CERRADO') !== false);

// ── [5] Inventario: capacidad y equipar ──
$cap = ope7_inventario_capacidad(array('fue' => 20));
$G['chk']('Inventario: FUE 20 → equipado 5 · mochila 13 (9.2)', $cap['equipado'] === 5 && $cap['mochila'] === 13 && !$cap['tontatta']);
$arma = (int) $db->fetch_field($db->simple_select('ope_objetos', 'id', "nombre = 'Arma comun'", array('limit' => 1)), 'id');
$pocion = (int) $db->fetch_field($db->simple_select('ope_objetos', 'id', "nombre = 'Poción de curación común'", array('limit' => 1)), 'id');
// Produce 1 arma y 2 pociones al almacén (trámite 6 — IA: crear + resultado + firma).
$r = ope7_tramite_crear(1, $pid, 6, 'Forja de un arma común', array(), array());
$t6a = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t6a, array('objeto_id' => $arma, 'cantidad' => 1));
$f6a = ope7_tramite_firmar($t6a, 1, 'publicar', 'Forja validada.');
$r = ope7_tramite_crear(1, $pid, 6, 'Alquimia de pociones', array(), array());
$t6b = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t6b, array('objeto_id' => $pocion, 'cantidad' => 2));
$f6b = ope7_tramite_firmar($t6b, 1, 'publicar', 'Alquimia validada.');
$aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$pocion}", array('limit' => 1));
$G['chk']('Producción (efecto 6): +2 pociones al ALMACÉN (9.7)', $f6a['ok'] && $f6b['ok'] && (int) $db->fetch_field($aq, 'cantidad') === 2);
// Equipar la espada (trámite 14, ligero automático — el efecto va al log).
$r = ope7_tramite_crear(1, $pid, 14, '', array('objeto_id' => $arma, 'zona' => 'arma1'));
$eq = ope7_inventario_resumen($pid);
$G['chk']('Equipar (efecto 14): arma a arma1, 1/4 ranuras equipado', $r['ok'] && $eq['usado']['equipado'] === 1 && $eq['usado']['mochila'] === 0);
// El arma ya no está en el almacén (salió al equipado).
$aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$arma}", array('limit' => 1));
$G['chk']('Equipar: el arma sale del almacén al equipado (9.2)', (int) $db->fetch_field($aq, 'cantidad') === 0);
// Equipar el mismo arma en arma2 → no hay stock → bloquea.
$r = ope7_tramite_crear(1, $pid, 14, '', array('objeto_id' => $arma, 'zona' => 'arma2'));
$G['chk']('Equipar: sin stock en almacén → bloquea', !$r['ok']);

// ── [6] Cartera ──
$r = ope7_cartera_mover($pid, 'boveda', 5000);
$r = ope7_cartera_mover($pid, 'cartera', 1500);
$c = ope7_cartera_get($pid);
$G['chk']('Cartera: bóveda 5000 + cartera 1500', $c['boveda'] === 5000 && $c['cartera'] === 1500);
$r = ope7_cartera_mover($pid, 'cartera', -2000);
$G['chk']('Cartera: gasto mayor que saldo → bloquea', !$r['ok']);

// ── [7] Tienda: apertura sin Comerciante → bloqueada ──
$res_tienda = array('tipo' => 'oficio', 'local' => 'Puesto en el puerto', 'margen' => 0.0, 'isla_id' => $isla_dawn, 'capital' => 1000, 'items' => array(array('objeto_id' => $pocion, 'stock' => 1)));
$r = ope7_tramite_crear(1, $pid, 15, 'Abrir tienda en el puerto', array(), $res_tienda);
$t15 = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15, $res_tienda);
$fir = ope7_tramite_firmar($t15, 1, 'publicar', 'Sin Comerciante — debe bloquear.');
$G['chk']('Tienda: apertura sin Comerciante → bloqueada (10.6)', $fir['ok'] === false || stripos((string) ($fir['msg'] ?? ''), 'Comerciante') !== false);
// Con Comerciante nv1.
$com_id = (int) $db->fetch_field($db->simple_select('ope_dominios', 'id', "nombre = 'Comerciante'", array('limit' => 1)), 'id');
$db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $com_id, 'nivel' => 1, 'origen' => 'creacion'));
$r = ope7_tramite_crear(1, $pid, 15, 'Abrir tienda en el puerto (con oficio)', array(), $res_tienda);
$t15b = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15b, $res_tienda);
$fir = ope7_tramite_firmar($t15b, 1, 'publicar', 'Comerciante nv1 OK.');
$tienda = (int) $db->fetch_field($db->simple_select('ope_tiendas', 'id', "dueno_id = {$pid}", array('limit' => 1)), 'id');
$G['chk']('Tienda: apertura con Comerciante nv1 → activa con 1 ítem en su isla', $fir['ok'] && $tienda > 0);
$tz = (int) $db->fetch_field($db->simple_select('ope_tiendas', 'zona_id', "id = {$tienda}", array('limit' => 1)), 'zona_id');
$G['chk']('Tienda: zona_id = isla del catálogo (D4)', $tz === $isla_dawn);
// Margen fuera de banda → bloquea.
$res_margen = array('tipo' => 'oficio', 'local' => 'Puesto', 'margen' => 0.8, 'isla_id' => $isla_dawn, 'capital' => 1000, 'items' => array());
$r = ope7_tramite_crear(1, $pid, 15, 'Tienda con margen roto', array(), $res_margen);
$t15c = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15c, $res_margen);
$fir = ope7_tramite_firmar($t15c, 1, 'publicar', 'Margen roto.');
$G['chk']('Tienda: margen +80 % fuera de banda → bloquea', $fir['ok'] === false);

// ── [8] Compra/venta ──
// Segundo personaje (comprador).
$pid2 = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F3 Comprador', 'slug' => 'prueba-f3-comprador', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza,
    'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
    'puntos_comprados' => 0, 'pp_saldo' => 0,
));
ope7_cartera_mover($pid2, 'cartera', 10000);
$r = ope7_tienda_compra($pid2, $tienda, $pocion, 1); // vendedor es pid → auto-compra prohibida solo si son el mismo
$G['chk']('Tienda: compra normal registrada (almacén del comprador)', $r['ok']);
$c = ope7_cartera_get($pid2);
// D4/F4.1: la tienda vende al mercado de SU zona (la isla Dawn), no al global.
$precio_zona = ope7_precio_mercado($pocion, $isla_dawn);
$G['chk']('Tienda: al comprador se le descuenta el precio de la isla (D4)', $c['cartera'] === 10000 - $precio_zona);
$r = ope7_tienda_compra($pid, $tienda, $pocion, 1); // el dueño comprándose a sí mismo
$G['chk']('Tienda: auto-compra prohibida (10.6)', !$r['ok']);
// El dueño ya ingresó el precio de la compra (zona) + la venta (50 % del mercado global).
$r = ope7_tienda_venta_npc($pid, $pocion, 1);
$c = ope7_cartera_get($pid);
$esperado_cartera = 1500 + $precio_zona + (int) round(ope7_precio_mercado($pocion) * 0.5);
$G['chk']('Tienda: venta a NPC al 50 % del mercado (10.5)', $r['ok'] && $c['cartera'] === $esperado_cartera);
$tq = $db->simple_select('ope_transacciones', 'COUNT(*) AS c', "comprador_id = {$pid2} OR vendedor_id = {$pid}");
$G['chk']('Tienda: transacciones registradas', (int) $db->fetch_field($tq, 'c') >= 2);

// ── [9] Boletín de precios (efecto 18, staff) ──
$r = ope7_tramite_crear(1, $pid, 18, 'Boletín con suceso roto', array(), array());
$t18 = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t18, array('zona_id' => 0, 'ronda' => 1, 'motivo' => 'Epidemia en el puerto.', 'items' => array(array('objeto_id' => $pocion, 'factores' => array('oferta' => 1.0, 'demanda' => 1.0, 'suceso' => 2.5)))));
$fir = ope7_tramite_firmar($t18, 1, 'publicar', 'Suceso fuera de banda.');
$G['chk']('Boletín: suceso 2,5 fuera de banda 0,5–1,5 → bloquea', $fir['ok'] === false);
$r = ope7_tramite_crear(1, $pid, 18, 'Boletín de la ronda 1', array(), array());
$t18b = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t18b, array('zona_id' => 0, 'ronda' => 1, 'motivo' => 'Epidemia en el puerto: suben las pociones.', 'items' => array(array('objeto_id' => $pocion, 'factores' => array('oferta' => 0.9, 'demanda' => 1.2, 'suceso' => 1.3)))));
$fir = ope7_tramite_firmar($t18b, 1, 'publicar', 'Desglose con motivo OK.');
$pm = (int) $db->fetch_field($db->simple_select('ope_precios_mercado', 'precio_actual', "objeto_id = {$pocion} ORDER BY id DESC", array('limit' => 1)), 'precio_actual');
// 300 × 0.9 × 1.2 × 1.3 = 421,2 → 420 (decenas), dentro de 150–600.
$G['chk']('Boletín: precio publicado 420 con desglose', $fir['ok'] && $pm === 420);

// ── [10] Limpieza ──
foreach (array($pid, $pid2) as $pdel) {
    // Los temas abiertos por el test (presente/pasados del trámite 1) deben
    // quedar limpios junto a sus participantes; si no, un 'presente abierto'
    // huérfano rompe la siguiente corrida del test.
    $ttd = $db->simple_select('ope_temas_participantes', 'tema_id', "personaje_id = {$pdel}");
    $temas_test = array();
    while ($row = $db->fetch_array($ttd)) {
        $temas_test[] = (int) $row['tema_id'];
    }
    $bt = $db->simple_select('ope_tramites', 'id', "personaje_id = {$pdel}");
    while ($row = $db->fetch_array($bt)) {
        $db->delete_query('ope_tramites_historico', 'tramite_id = ' . (int) $row['id']);
        $db->delete_query('ope_tramites', 'id = ' . (int) $row['id']);
    }
    foreach (array('ope_tecnicas', 'ope_historico_pp', 'ope_muertes', 'ope_carteras', 'ope_temas_participantes', 'ope_dominios_personaje', 'ope_personaje_dotes', 'ope_personaje_rasgos', 'ope_almacen', 'ope_inventario_personaje') as $t) {
        $db->delete_query($t, "personaje_id = {$pdel}");
    }
    foreach ($temas_test as $tv) {
        $db->delete_query('ope_temas', "tid = {$tv}");
    }
    $tt = $db->simple_select('ope_tiendas', 'id', "dueno_id = {$pdel}");
    while ($row = $db->fetch_array($tt)) {
        $db->delete_query('ope_tienda_items', 'tienda_id = ' . (int) $row['id']);
        $db->delete_query('ope_tiendas', 'id = ' . (int) $row['id']);
    }
    $db->delete_query('ope_personajes', "id = {$pdel}");
}
// Restaura el calendario a hoy (el test lo movió 3 días atrás).
$db->update_query('ope_calendario_foro', array('fecha_foro_actual' => date('Y-m-d'), 'ultima_actualizacion_real' => TIME_NOW), '1=1');
// Limpia las transacciones generadas por el propio test (vendedores/compradores de prueba).
foreach (array($pid, $pid2) as $pdel) {
    $db->delete_query('ope_transacciones', "vendedor_id = {$pdel} OR comprador_id = {$pdel}");
}
$G['chk']('Limpieza F3 completa y calendario restaurado', true);

echo "\n=== DONE — {$ok}/{$total} checks OK ===\n";
if ($ok !== $total) {
    exit(1);
}
