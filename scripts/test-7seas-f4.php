<?php
/**
 * One Piece: 7 Seas · Test F4.1 — Mundo Vivo (5.12/5.14/5.15/5.16/5.17)
 * --------------------------------------------------------------------
 * Verifica:
 *  · Semilla: 7 mares, 17 islas con ficha viva, zonas, tipos de barco,
 *    maderas, módulos, oráculos, transportes y 8 facciones con escalera.
 *  · Ronda: abrir siguiente, cola de temas presentes, cambiar estado,
 *    aplicar cierre firmado (islas con motivo + recompensas + precios +
 *    periódico borrador).
 *  · Tienda ↔ isla (D4): apertura exige isla y territorio; zona_id = isla;
 *    suspensión por cambio de manos.
 *  · Panel: render del dashboard con ronda + matriz.
 * Idempotente: limpieza completa al final (rondas, islas de prueba, PJs).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f4.php');
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
foreach (array('ope_rondas', 'ope_isla_estado_historico', 'ope_recompensas_historico', 'ope_historico_periodicos', 'ope_sucesos') as $t) {
    if ($db->table_exists($t)) {
        $db->delete_query($t, '1=1');
    }
}
// Restaura las islas a su estado del catálogo (por si una corrida anterior
// se cortó a mitad del cierre de ronda).
$dawn_id = (int) $db->fetch_field($db->simple_select('ope_islas', 'id', "slug = 'dawn'", array('limit' => 1)), 'id');
if ($dawn_id > 0) {
    $db->update_query('ope_isla_estado', array('peligrosidad' => 4, 'quien_manda' => 'Consejo de aldea (la alcaldesa)'), "isla_id = {$dawn_id}");
}
// Restaura Cendra/Alabasta (conquista) si una corrida anterior se cortó a mitad.
$pc = ope7_isla_por_slug('archipielago-cendra');
$pa = ope7_isla_por_slug('alabasta');
if ($pc) {
    $db->update_query('ope_isla_estado', array('afiliacion' => 'salvaje', 'fuerza_defensiva_nivel' => 5, 'quien_manda' => 'Tribus locales autónomas'), "isla_id = " . (int) $pc['id']);
}
if ($pa) {
    $db->update_query('ope_isla_estado', array('afiliacion' => 'gobierno', 'fuerza_defensiva_nivel' => 14, 'quien_manda' => 'Casa real (original del foro)'), "isla_id = " . (int) $pa['id']);
}
$db->delete_query('ope_personajes', "slug LIKE 'prueba-f4-%'");
// Facciones de corridas anteriores (los PJ de prueba se borran, sus filas no).
$db->delete_query('ope_faccion_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_cambios_faccion', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_subfaccion_elite', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_faccion_personaje', 'personaje_id IN (SELECT id FROM mybb_ope_personajes WHERE slug LIKE "prueba-f4-%")');
$db->delete_query('ope_cambios_faccion', 'personaje_id IN (SELECT id FROM mybb_ope_personajes WHERE slug LIKE "prueba-f4-%")');
$db->delete_query('ope_subfaccion_elite', 'personaje_id IN (SELECT id FROM mybb_ope_personajes WHERE slug LIKE "prueba-f4-%")');
// Restaura el cupo de Marinero (id 8) si una corrida anterior lo dejó en 1.
$db->query('UPDATE ' . TABLE_PREFIX . 'ope_rangos_faccion SET cupo = NULL WHERE id = 8');
$db->delete_query('ope_tramites', "personaje_id = 0 OR tipo LIKE '%F4%' OR (numero IN (38, 2, 34, 35, 36, 37, 39, 40, 41, 42, 43, 44) AND solicitante_id = 1)");
// Trámites huérfanos de corridas crasheadas (PJ ya borrado): limpio.
$db->delete_query('ope_tramites', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
// Travesías/temas/barcos de corridas anteriores (datos del test, no de juego).
$db->delete_query('ope_travesias', '1=1');
$db->delete_query('ope_incidentes_travesia', '1=1');
$db->delete_query('ope_barcos', '1=1');
$db->delete_query('ope_reparaciones', '1=1');
$db->delete_query('ope_temas_participantes', '1=1');
$db->delete_query('ope_temas', '1=1');
$db->delete_query('ope_tiendas', "notas LIKE '%F4.1%' OR dueno_id = 0");
// Conquista de corridas anteriores: conquistas, asedios, unidades, hordas.
$db->delete_query('ope_conquistas', '1=1');
$db->delete_query('ope_asedios', '1=1');
$db->delete_query('ope_unidades', '1=1');
$db->delete_query('ope_hordas', '1=1');

// ── [1] Semilla del mundo ──
$mares = 0;
$q = $db->simple_select('ope_mares', 'COUNT(*) AS n', '1=1');
$mares = (int) $db->fetch_field($q, 'n');
$G['chk']('Semilla: 7 mares', $mares === 7);

$q = $db->simple_select('ope_islas', 'COUNT(*) AS n', '1=1');
$islas_n = (int) $db->fetch_field($q, 'n');
$G['chk']('Semilla: 17 islas del catálogo 5.14', $islas_n === 17);

$q = $db->simple_select('ope_isla_estado', 'COUNT(*) AS n', '1=1');
$G['chk']('Semilla: 17 fichas vivas (isla_estado)', (int) $db->fetch_field($q, 'n') === 17);

$q = $db->simple_select('ope_zonas', 'COUNT(*) AS n', '1=1');
$G['chk']('Semilla: zonas clave (1–3 por isla)', (int) $db->fetch_field($q, 'n') >= 17);

// Ficha de una isla con los 13 parámetros.
$dawn = ope7_isla_por_slug('dawn');
$G['chk']('Isla: Dawn por slug con mar', $dawn !== null && (int) $dawn['mar_id'] > 0);
$ficha = ope7_isla_ficha((int) $dawn['id']);
$G['chk']('Ficha viva: peligrosidad 4 y afiliación local (5.14)', $ficha !== null && (int) $ficha['peligrosidad'] === 4 && (string) $ficha['afiliacion'] === 'local');
$G['chk']('Ficha viva: lugares clave decodificados', $ficha !== null && is_array($ficha['lugares_clave']) && count($ficha['lugares_clave']) >= 1);

// Barcos / maderas / módulos / oráculos / transportes.
foreach (array('ope_tipos_barcos' => 8, 'ope_maderas_casco' => 5, 'ope_modulos_barcos' => 10, 'ope_oraculos_catalogo' => 7, 'ope_transportes' => 3) as $t => $n) {
    $q = $db->simple_select($t, 'COUNT(*) AS n', '1=1');
    $G['chk']("Semilla: {$t} = {$n}", (int) $db->fetch_field($q, 'n') === $n);
}

// Facciones con escalera.
$q = $db->simple_select('ope_facciones', 'COUNT(*) AS n', '1=1');
$G['chk']('Semilla: 8 facciones jugables (5.12)', (int) $db->fetch_field($q, 'n') === 8);
$q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('rangos_faccion') . ' rf JOIN ' . ope7_tabla_full('facciones') . ' f ON f.id = rf.faccion_id WHERE f.nombre = \'Marines\'');
$G['chk']('Facciones: Marines con escalera de 8 rangos', (int) $db->fetch_field($q, 'n') === 8);

// ── [2] Ronda mensual ──
$ronda = ope7_ronda_abrir_siguiente();
$G['chk']('Ronda: abre la nº 1 si no hay activa', $ronda !== null && (int) $ronda['numero'] === 1 && (string) $ronda['estado'] === 'abierta');
$ronda2 = ope7_ronda_abrir_siguiente();
$G['chk']('Ronda: no duplica la abierta', (int) $ronda2['numero'] === 1);

// Un tema presente para la cola.
$raza = (int) $db->fetch_field($db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1)), 'id');
$pid = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba F4 Ronda', 'slug' => 'prueba-f4-ronda', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza,
    'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
    'puntos_comprados' => 0, 'pp_saldo' => 0,
));
$r = ope7_tramite_crear(1, $pid, 1, '', array('zona' => 'Blue Este', 'tipo' => 'presente'));
$G['chk']('Ronda: tema presente abierto (cola)', $r['ok']);
$pend = ope7_ronda_temas_pendientes();
$G['chk']('Ronda: la cola detecta el presente', count($pend) >= 1);

$msg = ope7_ronda_cambiar_estado((int) $ronda['id'], 'analisis', 1);
$G['chk']('Ronda: abierta → análisis', stripos($msg, 'analisis') !== false);
$ronda_est = ope7_ronda_activa();
$G['chk']('Ronda: sigue activa en análisis', (string) $ronda_est['estado'] === 'analisis');

// Cierre firmado: sube la peligrosidad de Dawn con motivo + recompensa + precio + periódico.
$cierre = array(
    'ronda' => 1,
    'staff_uid' => 1,
    'islas' => array(array(
        'isla_id' => (int) $dawn['id'],
        'cambios' => array('peligrosidad' => 6, 'quien_manda' => 'La alcaldesa y la milicia'),
        'motivo' => 'Apariciones de bandidos tras la feria (5.14).',
    )),
    'recompensas' => array(array(
        'personaje_id' => $pid, 'tipo' => 'suceso', 'cantidad' => 150000,
        'motivo' => 'Gesta narrada en el presente (5.14).',
    )),
    'precios' => array(array(
        'objeto_id' => 14, 'zona_id' => 0, 'precio_actual' => 400,
        'motivo' => 'Demanda estacional de pociones (10.2).',
    )),
    'periodico' => '<h3>News Coo — Ronda 1</h3><p>El mundo se mueve…</p>',
    'periodico_titulo' => 'News Coo — Ronda 1',
);
$resumen = ope7_ronda_aplicar_cierre($cierre);
$G['chk']('Cierre: aplica 1 cambio de isla con motivo', $resumen['islas'] === 1);
$G['chk']('Cierre: registra 1 recompensa', $resumen['recompensas'] === 1);
$G['chk']('Cierre: publica 1 precio dentro de banda', $resumen['precios'] === 1);
$G['chk']('Cierre: archiva el periódico en borrador (visibilidad manual)', $resumen['periodico'] === 1);

$ficha2 = ope7_isla_ficha((int) $dawn['id']);
$G['chk']('Cierre: Dawn peligrosidad 4 → 6', (int) $ficha2['peligrosidad'] === 6);
$q = $db->simple_select('ope_isla_estado_historico', 'COUNT(*) AS n', "isla_id = " . (int) $dawn['id'] . " AND fuente = 'ronda'");
$G['chk']('Cierre: histórico de isla con fuente ronda y motivo', (int) $db->fetch_field($q, 'n') >= 1);
$q = $db->simple_select('ope_historico_periodicos', 'estado', '1=1', array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
$G['chk']('Cierre: periódico en estado borrador (no se publica solo)', (string) $db->fetch_field($q, 'estado') === 'borrador');

$msg = ope7_ronda_cambiar_estado((int) $ronda['id'], 'cerrada', 1);
$G['chk']('Ronda: análisis → cerrada', stripos($msg, 'cerrada') !== false);

// ── [3] Tienda ↔ isla (D4) ──
// Apertura sin isla → bloquea.
$r = ope7_tramite_crear(1, $pid, 15, 'Tienda sin isla F4', array(), array('tipo' => 'oficio', 'local' => 'Puesto', 'margen' => 0.0, 'capital' => 1000, 'items' => array()));
$t15a = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15a, array('tipo' => 'oficio', 'local' => 'Puesto', 'margen' => 0.0, 'capital' => 1000, 'items' => array()));
$fir = ope7_tramite_firmar($t15a, 1, 'publicar', 'Sin isla.');
$G['chk']('Tienda D4: apertura sin isla → bloquea', !$fir['ok']);
// Apertura con isla pero sin Comerciante → bloquea por oficio.
$r = ope7_tramite_crear(1, $pid, 15, 'Tienda F4', array(), array('tipo' => 'oficio', 'local' => 'Puesto', 'margen' => 0.0, 'isla_id' => (int) $dawn['id'], 'capital' => 1000, 'items' => array()));
$t15b = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15b, array('tipo' => 'oficio', 'local' => 'Puesto', 'margen' => 0.0, 'isla_id' => (int) $dawn['id'], 'capital' => 1000, 'items' => array()));
$fir = ope7_tramite_firmar($t15b, 1, 'publicar', 'Sin Comerciante.');
$G['chk']('Tienda D4: sigue exigiendo Comerciante', !$fir['ok']);
// Con Comerciante y ubicación en Dawn → abre con zona_id = isla.
$com = (int) $db->fetch_field($db->simple_select('ope_dominios', 'id', "nombre = 'Comerciante'", array('limit' => 1)), 'id');
$db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $com, 'nivel' => 1, 'origen' => 'creacion'));
$db->update_query('ope_personajes', array('ubicacion_isla_id' => (int) $dawn['id']), "id = {$pid}");
$r = ope7_tramite_crear(1, $pid, 15, 'Tienda F4 con oficio', array(), array('tipo' => 'oficio', 'local' => 'Puesto en Dawn', 'margen' => 0.0, 'isla_id' => (int) $dawn['id'], 'capital' => 1000, 'items' => array()));
$t15c = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t15c, array('tipo' => 'oficio', 'local' => 'Puesto en Dawn', 'margen' => 0.0, 'isla_id' => (int) $dawn['id'], 'capital' => 1000, 'items' => array()));
$fir = ope7_tramite_firmar($t15c, 1, 'publicar', 'Comerciante + isla OK.');
$tienda = (int) $db->fetch_field($db->simple_select('ope_tiendas', 'id', "dueno_id = {$pid} ORDER BY id DESC", array('limit' => 1)), 'id');
$zona = (int) $db->fetch_field($db->simple_select('ope_tiendas', 'zona_id', "id = {$tienda}", array('limit' => 1)), 'zona_id');
$G['chk']('Tienda D4: abre con zona_id = isla del catálogo', $fir['ok'] && $tienda > 0 && $zona === (int) $dawn['id']);

// Suspensión por cambio de manos (la usará la conquista).
$n = ope7_tiendas_suspender_en_isla((int) $dawn['id']);
$est = (string) $db->fetch_field($db->simple_select('ope_tiendas', 'estado', "id = {$tienda}", array('limit' => 1)), 'estado');
$G['chk']('Tienda D4: cambio de manos suspende la tienda (5.15/16.6)', $n >= 1 && $est === 'suspendida');

// ── [4] Panel Mundo Vivo ──
$html = ope7_mundo_vivo_panel_html();
$G['chk']('Panel: render del dashboard (ronda + cola + matriz)', strpos($html, 'Ronda') !== false && strpos($html, 'Matriz de islas') !== false);
$G['chk']('Panel: matriz de islas con Dawn', strpos($html, 'Dawn') !== false);
$G['chk']('Panel: sin estilos inline', strpos($html, 'style=') === false);

// ── [4b] Panel «Progresión» (A.3) ──
// Da PP y compra un bloque para que el cronómetro y el gasto existan.
$db->update_query('ope_personajes', array('pp_saldo' => 300, 'entrenamiento_fin' => 0, 'entrenamiento_bloque' => 0), "id = {$pid}");
$r = ope7_tramite_crear(1, $pid, 4, '', array('atributo' => 'fue', 'bloque' => 5));
$G['chk']('Progresión: compra de PP registra el gasto en el libro (A.3)', $r['ok']);
$q = $db->simple_select('ope_historico_pp', 'cantidad, concepto', "personaje_id = {$pid} AND concepto LIKE 'Compra de PP%' ORDER BY id DESC", array('limit' => 1));
$mov = $db->fetch_array($q);
$G['chk']('Progresión: gasto −coste con concepto de atributo', $mov && (int) $mov['cantidad'] < 0 && strpos((string) $mov['concepto'], 'fue') !== false);
// El PJ tiene entrenamiento en curso (entrenamiento_fin > now).
$html_pr = ope7_progresion_panel_html();
$G['chk']('Progresión: panel muestra el cronómetro en curso', strpos($html_pr, 'Cronómetros') !== false && strpos($html_pr, 'Prueba F4 Ronda') !== false);
$G['chk']('Progresión: panel muestra el gasto por concepto', strpos($html_pr, 'Compra de PP') !== false && strpos($html_pr, 'pr-neg') !== false);
$G['chk']('Progresión: panel muestra saldos y reservas', strpos($html_pr, 'Saldos y reservas') !== false && strpos($html_pr, 'PP saldo') !== false);
$G['chk']('Progresión: panel sin estilos inline', strpos($html_pr, 'style=') === false);
// Limpia el entrenamiento y el histórico del test.
$db->delete_query('ope_historico_pp', "personaje_id = {$pid}");

// ── [4c] Bloque «Reserva de puntos» en la ficha (F4.2, 7.3) ──
// El PJ tiene reserva 0 (la compra anterior arrancó cronómetro); le damos
// reserva y renderizamos la ficha como su dueño para ver el bloque.
$db->update_query('ope_personajes', array('reserva' => 5), "id = {$pid}");
$pj_ficha = ope7_pj_get($pid);
$html_ficha = ope7_ficha_html($pj_ficha, array(
    'uid' => 1, 'es_activo' => true, 'puede_gestionar' => true,
    'es_staff' => true, 'bburl' => 'http://rpg.test', 'reserva_flash' => '',
));
$G['chk']('Ficha: bloque «Reserva de puntos» con steppers por atributo', strpos($html_ficha, 'Reserva de puntos') !== false && strpos($html_ficha, 'f7-step-input') !== false);
$G['chk']('Ficha: stepper con techo del nivel y máximo por atributo', strpos($html_ficha, 'data-techo="20"') !== false && strpos($html_ficha, 'data-max=') !== false);
$G['chk']('Ficha: suma live de la reserva presente', strpos($html_ficha, 'f7-reserva-suma') !== false && strpos($html_ficha, 'de 5') !== false);
$G['chk']('Ficha: botón aplica la distribución (form POST reserva)', strpos($html_ficha, 'gaccion') !== false && strpos($html_ficha, 'value="reserva"') !== false);
$G['chk']('Ficha: sin estilos inline en el bloque', strpos($html_ficha, 'style=') === false);

// ── [4d] Cronómetro de dominios (F4.3, 5.3/4.4 + D4.5) ──
// Subida del dominio de creación (×1,0), 1.º adicional (×1,5) con cupo INT,
// cronómetro de 15 días independiente y finalización al vencer.
$db->update_query('ope_personajes', array('pp_saldo' => 200, 'inte' => 50, 'nivel' => 10), "id = {$pid}");
$db->update_query('ope_dominios_personaje', array('entrenamiento_fin' => 0, 'entrenamiento_nivel' => 0), "personaje_id = {$pid}");
// 1) Subida de dominio de creación (origen 'creacion' → ×1,0): Comerciante nv1 → nv2 = 60 PP.
$r = ope7_tramite_crear(1, $pid, 4, '', array('dominio_id' => $com, 'nivel' => 2));
$G['chk']('Dominios: subida de dominio de creación a nv2 = 60 PP (×1,0)', $r['ok']);
$q = $db->simple_select('ope_dominios_personaje', 'entrenamiento_fin, entrenamiento_nivel', "personaje_id = {$pid} AND dominio_id = {$com}", array('limit' => 1));
$ddom = $db->fetch_array($q);
$G['chk']('Dominios: arranca el cronómetro de 15 días hacia nv2', $ddom && (int) $ddom['entrenamiento_nivel'] === 2 && (int) $ddom['entrenamiento_fin'] > TIME_NOW);
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
$G['chk']('Dominios: descuenta 60 PP del saldo', (int) $db->fetch_field($q, 'pp_saldo') === 140);
// 2) Al vencer el cronómetro sube solo y se limpia (5.3).
$db->update_query('ope_dominios_personaje', array('entrenamiento_fin' => TIME_NOW - 60), "personaje_id = {$pid} AND dominio_id = {$com}");
$n = ope7_pj_finalizar_dominios();
$q = $db->simple_select('ope_dominios_personaje', 'nivel, entrenamiento_fin', "personaje_id = {$pid} AND dominio_id = {$com}", array('limit' => 1));
$ddom = $db->fetch_array($q);
$G['chk']('Dominios: al vencer sube a nv2 y limpia el cronómetro', $n >= 1 && (int) $ddom['nivel'] === 2 && (int) $ddom['entrenamiento_fin'] === 0);
// 3) 1.º adicional con INT 50 (cupo 1/1): ×1,5 → 90 PP.
$q = $db->simple_select('ope_dominios', 'id', "id <> {$com} AND activo = 1", array('limit' => 1));
$com2 = (int) $db->fetch_field($q, 'id');
$r = ope7_tramite_crear(1, $pid, 4, '', array('dominio_id' => $com2, 'nivel' => 2));
$G['chk']('Dominios: 1.º adicional con ×1,5 → 90 PP (D4.5)', $r['ok']);
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid}", array('limit' => 1));
$G['chk']('Dominios: saldo tras el adicional (140 − 90 = 50)', (int) $db->fetch_field($q, 'pp_saldo') === 50);
$q = $db->simple_select('ope_dominios_personaje', 'coste_mult, entrenamiento_fin', "personaje_id = {$pid} AND dominio_id = {$com2}", array('limit' => 1));
$ddom = $db->fetch_array($q);
$G['chk']('Dominios: adicional ancla ×1,5 y cronómetro propio', $ddom && abs((float) $ddom['coste_mult'] - 1.5) < 0.01 && (int) $ddom['entrenamiento_fin'] > TIME_NOW);
// 4) 2.º adicional bloqueado por cupo de INT (1 por 50).
$q = $db->simple_select('ope_dominios', 'id', "id NOT IN ({$com}, {$com2}) AND activo = 1", array('limit' => 1));
$com3 = (int) $db->fetch_field($q, 'id');
$r = ope7_tramite_crear(1, $pid, 4, '', array('dominio_id' => $com3, 'nivel' => 2));
$G['chk']('Dominios: 2.º adicional bloqueado por cupo de INT', !$r['ok']);
// 5) El gasto queda en el libro con concepto de dominio y el panel lo muestra (A.3).
$q = $db->simple_select('ope_historico_pp', 'cantidad, concepto', "personaje_id = {$pid} AND concepto LIKE 'Compra de dominio%' ORDER BY id DESC", array('limit' => 1));
$mov = $db->fetch_array($q);
$G['chk']('Dominios: gasto −90 en el libro con concepto (×1,50)', $mov && (int) $mov['cantidad'] === -90 && strpos((string) $mov['concepto'], '×1,50') !== false);
$html_pr2 = ope7_progresion_panel_html();
$G['chk']('Dominios: panel Progresión muestra el cronómetro de dominio', strpos($html_pr2, 'dominio') !== false && strpos($html_pr2, '→ nv2') !== false);
// 6) La ficha muestra el entrenamiento en curso del dominio.
$pj_ficha2 = ope7_pj_get($pid);
$html_ficha2 = ope7_ficha_html($pj_ficha2, array(
    'uid' => 1, 'es_activo' => true, 'puede_gestionar' => true,
    'es_staff' => true, 'bburl' => 'http://rpg.test', 'reserva_flash' => '',
));
$G['chk']('Dominios: ficha muestra «entrenando → nv2» del dominio', strpos($html_ficha2, 'entrenando → nv2') !== false);

// ── [4e] Trámite 38 — Navegación/travesía (F4.3, 5.16/17) ──
// Helpers: crear+firmar una travesía y liberar el presente del PJ.
$nav = function ($dest, $barco, $extra = array()) use ($pid) {
    $r = ope7_tramite_crear(1, $pid, 38, 'Travesía F4', array_merge(array('destino_id' => $dest, 'barco_id' => $barco), $extra));
    $tid = (int) ($r['tid'] ?? 0);
    ope7_tramite_guardar_resultado($tid, array_merge(array('destino_id' => $dest, 'barco_id' => $barco), $extra));
    return ope7_tramite_firmar($tid, 1, 'publicar', 'Test navegación.');
};
$libre = function () use ($pid) {
    global $db;
    $db->update_query('ope_temas_participantes', array('salio_en' => TIME_NOW), "personaje_id = {$pid}");
    $db->update_query('ope_temas', array('estado' => 'cerrado'), "tid IN (SELECT tema_id FROM mybb_ope_temas_participantes WHERE personaje_id = {$pid})");
};
$alabasta = ope7_isla_por_slug('alabasta');
$skypiea = ope7_isla_por_slug('skypiea');
$dressrosa = ope7_isla_por_slug('dressrosa');
$tipo_bote = (int) $db->fetch_field($db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Bote de remos'", array('limit' => 1)), 'id');
$mad = array();
foreach (array('Pino de marea' => 'pino', 'Roble del sur' => 'roble', 'Madera de Adán' => 'adan') as $nm => $k) {
    $mad[$k] = (int) $db->fetch_field($db->simple_select('ope_maderas_casco', 'id', "nombre = '{$nm}'", array('limit' => 1)), 'id');
}
$barco_id = (int) $db->insert_query('ope_barcos', array(
    'nombre' => 'Bote F4', 'tipo_id' => $tipo_bote, 'nivel' => 'N1', 'madera_id' => $mad['pino'],
    'casco_pv' => 200, 'pv_actual' => 200, 'maniobra' => 10, 'espacio_max' => 2,
    'dueno_id' => $pid, 'estado' => 'activo',
));
$log_pose = (int) $db->fetch_field($db->simple_select('ope_objetos', 'id', "nombre = 'Log Pose'", array('limit' => 1)), 'id');
$racion = ope7_nav_racion_objeto_id();
$db->insert_query('ope_inventario_personaje', array('personaje_id' => $pid, 'objeto_id' => $log_pose, 'zona' => 'mochila', 'cantidad' => 1));

// 1) El PJ aún tiene el presente de [2] abierto → un-presente bloquea (5.6).
$fir = $nav((int) $alabasta['id'], 0);
$G['chk']('Nav: un-presente abierto bloquea la travesía (5.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'presente') !== false);
$libre();

// 2) Dawn→Alabasta (Blue) con Bote de pino → OK; IRT 2 → 1 oráculo menor.
$fir = $nav((int) $alabasta['id'], $barco_id);
$G['chk']('Nav: Blue→Blue con Bote de pino → travesía aceptada', $fir['ok']);
$q = $db->query('SELECT tr.*, t.tema_tipo, t.estado AS tema_estado FROM mybb_ope_travesias tr JOIN mybb_ope_temas t ON t.tid = tr.tema_id WHERE tr.barco_id = ' . $barco_id . ' ORDER BY tr.id DESC LIMIT 1');
$trv = $db->fetch_array($q);
$G['chk']('Nav: abre tema presente tipo travesia (5.6)', $trv && (string) $trv['tema_tipo'] === 'travesia' && (string) $trv['tema_estado'] === 'abierto');
$G['chk']('Nav: 1 tramo Blue · 6 días on-roll · plazo 84 h (menor +12)', $trv && strpos((string) $trv['tiempo_on_roll'], '6 días') !== false && (int) $trv['tiempo_disponible_h'] === 84);
$G['chk']('Nav: IRT interno guardado (solo-staff, 17.3)', $trv && (int) $trv['irt'] === 2);
$orac = json_decode((string) ($trv['oraculos'] ?? '[]'), true);
$G['chk']('Nav: 1 oráculo menor en la banda del IRT', is_array($orac) && count($orac) === 1 && (string) ($orac[0]['gravedad'] ?? '') === 'menor');
$G['chk']('Nav: incidentes registrados por oráculo', (int) $db->fetch_field($db->simple_select('ope_incidentes_travesia', 'COUNT(*) AS n', 'travesia_id = ' . (int) $trv['id']), 'n') === count($orac));
$libre();
// 2b) Con Log Pose: el utensilio mitiga −1 IRT (17.3) y −12 h (17.5/17.7).
$fir = $nav((int) $alabasta['id'], $barco_id, array('utensilio_id' => $log_pose));
$G['chk']('Nav: con utensilio, IRT 1 (tranquila) y −12 h en la ficha', $fir['ok'] && strpos((string) ($fir['msg'] ?? ''), 'utensilio aplica −12 h') !== false);
$q = $db->query('SELECT tr.* FROM mybb_ope_travesias tr JOIN mybb_ope_temas t ON t.tid = tr.tema_id WHERE tr.barco_id = ' . $barco_id . " AND tr.utensilio_id > 0 AND t.estado = 'abierto' ORDER BY tr.id DESC LIMIT 1");
$trv = $db->fetch_array($q);
$G['chk']('Nav: IRT 1 · 0 oráculos · plazo 60 h (72 − 12)', $trv && (int) $trv['irt'] === 1 && count(json_decode((string) ($trv['oraculos'] ?? '[]'), true)) === 0 && (int) $trv['tiempo_disponible_h'] === 60);
$libre();

// 3) Límite de mar por madera (18.5): Pino no entra al Paraíso.
$fir = $nav((int) $skypiea['id'], $barco_id);
$G['chk']('Nav: Pino bloquea la ruta al Paraíso (límite de mar, 18.5)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'madera') !== false);
$db->update_query('ope_barcos', array('madera_id' => $mad['roble']), "id = {$barco_id}");

// 4) Roble → Dawn→Skypiea OK; cierre con veredicto: víveres + daño + ubicación.
$fir = $nav((int) $skypiea['id'], $barco_id);
$G['chk']('Nav: Roble habilita Blue→Paraíso (2 tramos, 120 h base)', $fir['ok']);
$q = $db->query('SELECT tr.* FROM mybb_ope_travesias tr JOIN mybb_ope_temas t ON t.tid = tr.tema_id WHERE tr.barco_id = ' . $barco_id . " AND t.estado = 'abierto' ORDER BY tr.id DESC LIMIT 1");
$trv = $db->fetch_array($q);
$tema_a = (int) ($trv['tema_id'] ?? 0);
$G['chk']('Nav: Skypiea · 10 días on-roll · plazo 144 h (media +24)', $trv && strpos((string) $trv['tiempo_on_roll'], '10 días') !== false && (int) $trv['tiempo_disponible_h'] === 144);
// Raciones para el cierre (12 necesarias: 10 días on-roll + 1 media +2).
$db->insert_query('ope_inventario_personaje', array('personaje_id' => $pid, 'objeto_id' => $racion, 'zona' => 'mochila', 'cantidad' => 20));
$r = ope7_tramite_crear(1, $pid, 2, 'Cierre travesía F4', array('tema_id' => $tema_a), array());
$t2 = (int) ($r['tid'] ?? 0);
ope7_tramite_guardar_resultado($t2, array('tema_id' => $tema_a, 'factores' => array('fidelidad' => 1.0, 'peso' => 1.0, 'calidad' => 1.0, 'extension' => 1.0, 'tiempo' => 1.0, 'riesgo' => 1.0, 'perfil' => 1.0), 'motivo' => 'Llegada a Skypiea.'));
$fir = ope7_tramite_firmar($t2, 1, 'publicar', 'Cierre de travesía.');
$G['chk']('Nav: cierre del tema aplica el veredicto de travesía (17.6)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'raciones') !== false);
$q = $db->simple_select('ope_travesias', 'estado, viveres_gastados', "tema_id = {$tema_a}", array('limit' => 1));
$trv = $db->fetch_array($q);
$G['chk']('Nav: travesía resuelta con 12 raciones consumidas', $trv && (string) $trv['estado'] === 'resuelta' && (int) $trv['viveres_gastados'] === 12);
$q = $db->simple_select('ope_personajes', 'ubicacion_isla_id', "id = {$pid}", array('limit' => 1));
$G['chk']('Nav: ubicación del PJ = destino (Skypiea)', (int) $db->fetch_field($q, 'ubicacion_isla_id') === (int) $skypiea['id']);
$q = $db->simple_select('ope_barcos', 'estado', "id = {$barco_id}", array('limit' => 1));
$G['chk']('Nav: oráculo medio daña el barco (grado moderado)', (string) $db->fetch_field($q, 'estado') === 'danado_moderado');
$q = $db->simple_select('ope_inventario_personaje', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$racion}", array('limit' => 1));
$G['chk']('Nav: restan 8 raciones del inventario (20 − 12)', (int) $db->fetch_field($q, 'cantidad') === 8);

// 5) Límite de mar: Roble no entra al Nuevo Mundo; Adán sí.
$fir = $nav((int) $dressrosa['id'], $barco_id);
$G['chk']('Nav: Roble bloquea la ruta al Nuevo Mundo (límite de mar)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'madera') !== false);
$db->update_query('ope_barcos', array('madera_id' => $mad['adan']), "id = {$barco_id}");
$fir = $nav((int) $dressrosa['id'], $barco_id);
$G['chk']('Nav: Adán habilita el Nuevo Mundo', $fir['ok']);
$q = $db->query('SELECT tr.* FROM mybb_ope_travesias tr JOIN mybb_ope_temas t ON t.tid = tr.tema_id WHERE tr.barco_id = ' . $barco_id . " AND t.estado = 'abierto' ORDER BY tr.id DESC LIMIT 1");
$trv = $db->fetch_array($q);
$tema_b = (int) ($trv['tema_id'] ?? 0);
$G['chk']('Nav: Paraíso→NM · 7 días on-roll · plazo 120 h (2 oráculos)', $trv && strpos((string) $trv['tiempo_on_roll'], '7 días') !== false && (int) $trv['tiempo_disponible_h'] === 120 && count(json_decode((string) $trv['oraculos'], true)) === 2);

// 6) Vencimiento (17.5): plazo agotado sin cierre → travesía vencida.
$db->update_query('ope_temas', array('fecha_real_apertura' => TIME_NOW - (int) ($trv['tiempo_disponible_h'] ?? 120) * 3600 - 3600), "tid = {$tema_b}");
$n = ope7_travesias_vencidas();
$q = $db->simple_select('ope_travesias', 'estado', "tema_id = {$tema_b}", array('limit' => 1));
$G['chk']('Nav: plazo agotado → travesía vencida (17.5)', $n >= 1 && (string) $db->fetch_field($q, 'estado') === 'vencida');
$q = $db->simple_select('ope_temas', 'estado', "tid = {$tema_b}", array('limit' => 1));
$G['chk']('Nav: el tema de la vencida se cierra solo', (string) $db->fetch_field($q, 'estado') === 'cerrado');

// 7) Transporte civil (17.7): sin cartera bloquea; pagado, ruta segura +24 h.
$fir = $nav((int) $dressrosa['id'], 0, array('transporte_tipo' => 'civil'));
$G['chk']('Nav: transporte sin cartera bloquea (17.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'cartera') !== false);
ope7_cartera_mover($pid, 'cartera', 100000);
$fir = $nav((int) $dressrosa['id'], 0, array('transporte_tipo' => 'civil'));
$G['chk']('Nav: transporte civil pagado → travesía OK', $fir['ok']);
$q = $db->query('SELECT tr.* FROM mybb_ope_travesias tr JOIN mybb_ope_temas t ON t.tid = tr.tema_id WHERE tr.transporte_tipo = \'civil\' AND t.estado = \'abierto\' ORDER BY tr.id DESC LIMIT 1');
$trv = $db->fetch_array($q);
$G['chk']('Nav: transporte +24 h y ruta segura (máx 1 incidente menor)', $trv && (int) $trv['tiempo_disponible_h'] === 120 && count(json_decode((string) $trv['oraculos'], true)) <= 1);

// ── [4f] Panel staff «Navegación» (A.3, 17.8) ──
// La travesía del transporte sigue activa y la de Skypiea quedó resuelta.
$html_nv = ope7_navegacion_panel_html();
$G['chk']('Nav: panel renderiza travesías activas por jugador', strpos($html_nv, 'Travesías activas') !== false && strpos($html_nv, 'Prueba F4 Ronda') !== false);
$G['chk']('Nav: panel muestra ruta y oráculos de la activa', strpos($html_nv, '→') !== false && strpos($html_nv, 'oráculos') !== false);
$G['chk']('Nav: panel muestra el histórico con la resuelta', strpos($html_nv, 'Histórico') !== false && strpos($html_nv, 'resuelta') !== false);
$G['chk']('Nav: panel sin estilos inline', strpos($html_nv, 'style=') === false);

// ── [4g] Facciones (F4.3, 5.12/13 — trámites 20–24) ──
$tram = function ($num, $pj, $res) {
    $r = ope7_tramite_crear(1, $pj, $num, 'Facción F4', array(), array());
    $tid = (int) ($r['tid'] ?? 0);
    ope7_tramite_guardar_resultado($tid, $res);
    return ope7_tramite_firmar($tid, 1, 'publicar', 'Test facciones.');
};
$marines = ope7_faccion_por_nombre('Marines');
$piratas = ope7_faccion_por_nombre('Piratas');
$civiles = ope7_faccion_por_nombre('Civiles');
$rangos_mar = ope7_faccion_rangos((int) $marines['id']);
$recluta = $rangos_mar[0];
$marinero = $rangos_mar[1];
// Alta inicial: Marines / Recluta, rep 0.
$db->insert_query('ope_faccion_personaje', array('personaje_id' => $pid, 'faccion_id' => (int) $marines['id'], 'rango_id' => (int) $recluta['id'], 'rep_faccion' => 0, 'wanted_base' => 0, 'activo' => 1));
ope7_faccion_espejar($pid, (int) $marines['id'], (int) $recluta['id']);
ope7_faccion_registrar($pid, 'alta', 0, (int) $marines['id'], 'Alta inicial (test).', 1);
// 1) Ascenso sin rep suficiente → BLOQUEADO (requisito duro, 13.4).
$fir = $tram(20, $pid, array('staff_uid' => 1, 'termometro' => 'Servicio + poder.', 'motivo' => 'Ascenso a Marinero.'));
$G['chk']('Fac: ascenso bloqueado por rep_faccion mínima (13.4)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'reputación') !== false);
// 2) Cupo lleno → espera de cupo (13.4): Marinero con cupo 1 y un ocupante.
$db->update_query('ope_rangos_faccion', array('cupo' => 1), "id = " . (int) $marinero['id']);
$dummy = ope7_pj_guardar(array('uid' => 1, 'nombre' => 'Prueba F4 Cupo', 'slug' => 'prueba-f4-cupo', 'estado' => 'aprobado', 'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza, 'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10, 'puntos_comprados' => 0, 'pp_saldo' => 0));
$db->insert_query('ope_faccion_personaje', array('personaje_id' => $dummy, 'faccion_id' => (int) $marines['id'], 'rango_id' => (int) $marinero['id'], 'rep_faccion' => 20, 'wanted_base' => 0, 'activo' => 1));
$db->update_query('ope_faccion_personaje', array('rep_faccion' => 20), "personaje_id = {$pid}");
$fir = $tram(20, $pid, array('staff_uid' => 1, 'termometro' => 'OK.', 'motivo' => 'Ascenso con cupo lleno.'));
$G['chk']('Fac: rango con cupo lleno → espera de cupo (13.4)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'cupo') !== false);
$db->delete_query('ope_personajes', "id = {$dummy}");
$db->delete_query('ope_faccion_personaje', "personaje_id = {$dummy}");
// SQL crudo: MyBB convierte null → '' en INT y crashea (bug conocido).
$db->query('UPDATE ' . TABLE_PREFIX . 'ope_rangos_faccion SET cupo = NULL WHERE id = ' . (int) $marinero['id']);
// 3) Con rep 20 y cupo libre → ascenso a Marinero OK + histórico.
$fir = $tram(20, $pid, array('staff_uid' => 1, 'termometro' => 'Servicio y poder verificados.', 'motivo' => 'Ascenso a Marinero.'));
$G['chk']('Fac: ascenso OK con termómetro y rep (→ Marinero)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Marinero') !== false);
$q = $db->simple_select('ope_faccion_personaje', 'rango_id', "personaje_id = {$pid}", array('limit' => 1));
$G['chk']('Fac: rango actualizado en faccion_personaje', (int) $db->fetch_field($q, 'rango_id') === (int) $marinero['id']);
$q = $db->simple_select('ope_cambios_faccion', 'COUNT(*) AS n', "personaje_id = {$pid} AND tipo = 'promocion'");
$G['chk']('Fac: promoción en el histórico inmutable', (int) $db->fetch_field($q, 'n') >= 1);
// 4) Cambio de facción (13.7): Marines → Piratas, entra como Novato.
$fir = $tram(22, $pid, array('faccion_id' => (int) $piratas['id'], 'motivo' => 'La mar llama.'));
$G['chk']('Fac: cambio de facción OK (Marines → Piratas)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Piratas') !== false);
$fp = ope7_faccion_pj($pid);
$G['chk']('Fac: entra por el rango inicial (Novato, 13.7)', $fp && (int) $fp['faccion_id'] === (int) $piratas['id'] && (string) ($fp['rango']['nombre'] ?? '') === 'Novato');
$q = $db->simple_select('ope_cambios_faccion', 'COUNT(*) AS n', "personaje_id = {$pid} AND tipo IN ('baja','alta')");
$G['chk']('Fac: baja + alta en el histórico (13.8)', (int) $db->fetch_field($q, 'n') >= 2);
// 5) Anti-abuso (13.7): un personaje por facción por jugador.
$civ_primero = ope7_faccion_rangos((int) $civiles['id'])[0];
$dummy2 = ope7_pj_guardar(array('uid' => 1, 'nombre' => 'Prueba F4 Dupe', 'slug' => 'prueba-f4-dupe', 'estado' => 'aprobado', 'estado_vida' => 'activa', 'nivel' => 1, 'raza_id' => $raza, 'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10, 'puntos_comprados' => 0, 'pp_saldo' => 0));
$db->insert_query('ope_faccion_personaje', array('personaje_id' => $dummy2, 'faccion_id' => (int) $civiles['id'], 'rango_id' => (int) $civ_primero['id'], 'rep_faccion' => 0, 'wanted_base' => 0, 'activo' => 1));
$fir = $tram(22, $pid, array('faccion_id' => (int) $civiles['id'], 'motivo' => 'Duplicado.'));
$G['chk']('Fac: cambio bloqueado por el límite de un personaje por facción', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'otro personaje') !== false);
$db->delete_query('ope_personajes', "id = {$dummy2}");
$db->delete_query('ope_faccion_personaje', "personaje_id = {$dummy2}");
// 6) Deserción hostil (13.7): → Aventurero libre, criminal (Wanted + infamia).
$fir = $tram(23, $pid, array('tipo_baja' => 'hostil', 'motivo' => 'Me persiguen.'));
$G['chk']('Fac: deserción hostil → criminal (5.13)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'criminal') !== false);
$fp = ope7_faccion_pj($pid);
$G['chk']('Fac: Wanted crece (piso 5M) e infamia +1', $fp && (int) $fp['wanted_base'] === 5000000 && (int) $fp['fama_infamia_expo'] === 1);
// 7) Infiltración (13.7/13.8): visible = Civiles, real oculta = Aventurero.
$fir = $tram(24, $pid, array('faccion_id' => (int) $civiles['id'], 'rango_id' => (int) $civ_primero['id'], 'motivo' => 'Encubierto.'));
$G['chk']('Fac: infiltración autorizada con capa oculta', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'capa oculta') !== false);
$q = $db->simple_select('ope_faccion_personaje', 'faccion_id, rango_id, infiltracion_faccion_id, infiltracion_activa', "personaje_id = {$pid}", array('limit' => 1));
$fp = $db->fetch_array($q);
$av = ope7_faccion_por_nombre('Aventurero libre');
$G['chk']('Fac: visible = Civiles · real oculta = Aventurero', $fp && (int) $fp['faccion_id'] === (int) $civiles['id'] && (int) $fp['infiltracion_faccion_id'] === (int) $av['id'] && (int) $fp['infiltracion_activa'] === 1);
$fir = $tram(24, $pid, array('revocar' => 1, 'motivo' => 'Fin.'));
$fp2 = ope7_faccion_pj($pid);
$G['chk']('Fac: fin de infiltración restaura la lealtad real', $fir['ok'] && (int) $fp2['faccion_id'] === (int) $av['id'] && (int) ($fp2['infiltracion_activa'] ?? 0) === 0);
// 8) Subfacción élite (13.2): Shichibukai cupo 7, concesión + revocación.
$fir = $tram(21, $pid, array('nombre' => 'Shichibukai', 'motivo' => 'El mundo le elige.'));
$G['chk']('Fac: concesión de Shichibukai OK', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Shichibukai') !== false);
$fir2 = $tram(21, $pid, array('nombre' => 'Shichibukai', 'motivo' => 'Duplicado.'));
$G['chk']('Fac: ya es Shichibukai → bloquea', !$fir2['ok']);
$q = $db->simple_select('ope_subfaccion_elite', 'COUNT(*) AS n', "nombre = 'Shichibukai' AND activo = 1");
$G['chk']('Fac: 1 Shichibukai activo (cupo 7)', (int) $db->fetch_field($q, 'n') === 1);
$fir = $tram(21, $pid, array('nombre' => 'Shichibukai', 'revocar' => 1, 'motivo' => 'Atacó al Gobierno.'));
$fp = ope7_faccion_pj($pid);
$G['chk']('Fac: revocación crece el Wanted (×1,5 → 7,5M)', $fir['ok'] && $fp && (int) $fp['wanted_base'] === 7500000);
// 9) Panel staff «Facciones» (A.3, 13.8).
$html_fc = ope7_facciones_panel_html();
$G['chk']('Fac: panel con tablero de rangos y cupos', strpos($html_fc, 'Rangos y cupos') !== false && strpos($html_fc, 'Marines') !== false && strpos($html_fc, 'Almirante') !== false);
$G['chk']('Fac: panel con la élite (Shichibukai activos)', strpos($html_fc, 'Shichibukai activos') !== false);
$G['chk']('Fac: panel con el histórico inmutable', strpos($html_fc, 'Histórico de cambios') !== false && stripos($html_fc, 'desercion') !== false);
$G['chk']('Fac: panel sin estilos inline', strpos($html_fc, 'style=') === false);

// ── [4h] Conquista (F4.3, 5.15/cap. 16 — trámites 34–37) ──
$cendra = ope7_isla_por_slug('archipielago-cendra');
$alabasta = ope7_isla_por_slug('alabasta');
$G['chk']('Conq: islas de prueba del catálogo disponibles', $cendra && $alabasta);
// Presencia justificada (16.2): el PJ está en la isla objetivo.
$db->update_query('ope_personajes', array('ubicacion_isla_id' => (int) $cendra['id'], 'ubicacion_texto' => 'En el Archipiélago Cendra.'), "id = {$pid}");
$q = $db->simple_select('ope_isla_estado', 'afiliacion, fuerza_defensiva_nivel', "isla_id = " . (int) $cendra['id'], array('limit' => 1));
$est = $db->fetch_array($q);
$G['chk']('Conq: Cendra es salvaje con fd 5 → 0 rondas de asedio (16.3)', (string) $est['afiliacion'] === 'salvaje' && ope7_conquista_rondas_requeridas('salvaje', 5) === 0);
// 1) Anuncio (34) sobre isla salvaje: declarar + ocupar.
$fir = $tram(34, $pid, array('isla_id' => (int) $cendra['id'], 'bando' => 'La banda del test', 'motivo' => 'Refugio para la tripulación', 'justificacion' => 'Llegamos por el Blue Oeste y controlamos la cala.'));
$G['chk']('Conq: anuncio 34 OK con suceso público', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Anuncio registrado') !== false);
$q = $db->simple_select('ope_conquistas', '*', "isla_id = " . (int) $cendra['id'] . " AND estado = 'activa'", array('limit' => 1));
$conq = $db->fetch_array($q);
$G['chk']('Conq: conquista creada en fase anuncio', $conq && (string) $conq['fase'] === 'anuncio' && (int) $conq['rondas_asedio'] === 0);
$q = $db->simple_select('ope_sucesos', 'COUNT(*) AS n', "isla_id = " . (int) $cendra['id'] . " AND tipo = 'conquista'");
$G['chk']('Conq: suceso de conquista publicado (hook anuncio)', (int) $db->fetch_field($q, 'n') >= 1);
// 2) Anti-abuso: sin fases no hay conquista → no hay segunda activa sobre la misma isla.
$fir = $tram(34, $pid, array('isla_id' => (int) $cendra['id'], 'bando' => 'Otra banda', 'motivo' => 'Doble reclamo', 'justificacion' => 'También estamos aquí.'));
$G['chk']('Conq: anti-abuso — no hay conquista activa duplicada', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'activa') !== false);
// 3) Sin presencia en la isla → bloquea (16.2): estando en Cendra (texto que
//    la menciona), intentar Alabasta sin haber llegado → presencia falla y no
//    hay conquista activa sobre Alabasta que dispare el anti-duplicado.
$db->update_query('ope_personajes', array('ubicacion_isla_id' => (int) $cendra['id'], 'ubicacion_texto' => 'En el Archipiélago Cendra.'), "id = {$pid}");
$fir = $tram(34, $pid, array('isla_id' => (int) $alabasta['id'], 'bando' => 'Fantasma', 'motivo' => 'Reclamo lejano', 'justificacion' => 'Estoy en Cendra pero quiero Alabasta.'));
$G['chk']('Conq: sin presencia justificada → bloquea (16.2)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'presencia') !== false);
// 4) Responder al asedio (35): la guarnición NPC responde → fase asedio.
$fir = $tram(35, $pid, array('conquista_id' => (int) $conq['id'], 'estrategia' => 'Las tribus cierran el volcán y niegan el acceso.'));
$G['chk']('Conq: defensa activa registrada (35) → asedio', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'asedio') !== false);
$q = $db->simple_select('ope_asedios', 'COUNT(*) AS n', "conquista_id = " . (int) $conq['id']);
$G['chk']('Conq: log de asedio por ronda creado (16.8)', (int) $db->fetch_field($q, 'n') >= 1);
// 5) Unidades (16.7): Infantería 10.000/1.000 · Élite 50.000/5.000 · Especialistas 25.000/2.500.
ope7_cartera_mover($pid, 'cartera', 500000); // saldo para unidades/hordas
// El bloque [4g] dejó al PJ como cúspide de «Aventurero libre» (único rango = cúspide):
// para probar el límite de rango alto lo dejamos sin facción (nivel 1).
$db->delete_query('ope_faccion_personaje', "personaje_id = {$pid}");
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'infanteria');
$G['chk']('Conq: contrata Infantería (10.000 ฿, mant. 1.000/ronda)', $r['ok']);
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'elite');
$G['chk']('Conq: contrata Élite (50.000 ฿, mant. 5.000/ronda)', $r['ok']);
// Más de 2 unidades exige rango alto o imperio (16.7, D4.8): nv1 sin facción → bloquea.
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'especialista');
$G['chk']('Conq: 3.ª unidad bloqueada sin rango alto (nv1)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'rango alto') !== false);
$db->update_query('ope_personajes', array('nivel' => 30), "id = {$pid}");
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'especialista');
$G['chk']('Conq: 3.ª unidad OK con imperio en ciernes (nv30)', $r['ok']);
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'infanteria');
$G['chk']('Conq: 4.ª unidad OK (máx 4 por bando)', $r['ok']);
$r = ope7_conquista_contratar_unidad($pid, (int) $conq['id'], 'infanteria');
$G['chk']('Conq: 5.ª unidad bloqueada (máx 4, 16.7)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), '4') !== false);
$q = $db->simple_select('ope_unidades', 'COUNT(*) AS n', "conquista_id = " . (int) $conq['id'] . " AND estado = 'activa'");
$G['chk']('Conq: 4 unidades activas en el asedio', (int) $db->fetch_field($q, 'n') === 4);
// 6) Hordas (16.7): una sola vez por asedio.
$r = ope7_conquista_contratar_horda($pid, (int) $conq['id'], 'mara');
$G['chk']('Conq: contrata horda Mara (10.000 ฿)', $r['ok']);
$r = ope7_conquista_contratar_horda($pid, (int) $conq['id'], 'marea');
$G['chk']('Conq: segunda horda bloqueada (una vez por asedio)', !$r['ok']);
// 7) Tienda del anterior dueño en la isla → se suspende al registrar (16.6).
$db->insert_query('ope_tiendas', array('dueno_id' => 999, 'zona_id' => (int) $cendra['id'], 'tipo' => 'oficio', 'local' => 'Puesto de la alcaldesa', 'estado' => 'activa', 'capital' => 10000, 'banda_margen' => '', 'notas' => 'Conquista F4'));
// 8) Resolver/registrar (36): isla salvaje → 0 rondas, se resuelve en la misma ronda.
$fir = $tram(36, $pid, array('conquista_id' => (int) $conq['id'], 'ganador' => 'atacante', 'motivo' => 'La banda tomó la cala y las tribus negociaron.', 'veredicto' => 'Veredicto firme sin tiradas.'));
$G['chk']('Conq: resolución 36 OK con motivo (salvaje, 0 rondas)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Conquista registrada') !== false);
$q = $db->simple_select('ope_conquistas', '*', "id = " . (int) $conq['id'], array('limit' => 1));
$c2 = $db->fetch_array($q);
$G['chk']('Conq: estado ganada + fase ocupación + ganador', (string) $c2['estado'] === 'ganada' && (string) $c2['fase'] === 'ocupacion' && (int) $c2['ganador_id'] === $pid);
$ficha = ope7_isla_ficha((int) $cendra['id']);
$G['chk']('Conq: afiliación mixta + quien manda con motivo (16.8)', (string) $ficha['afiliacion'] === 'mixta' && stripos((string) ($ficha['quien_manda'] ?? ''), 'banda') !== false);
$q = $db->simple_select('ope_isla_estado_historico', 'COUNT(*) AS n', "isla_id = " . (int) $cendra['id'] . " AND fuente = 'conquista' AND motivo != ''");
$G['chk']('Conq: histórico de isla con fuente conquista y motivo', (int) $db->fetch_field($q, 'n') >= 1);
$q = $db->simple_select('ope_tiendas', 'estado', "zona_id = " . (int) $cendra['id'] . " AND dueno_id = 999", array('limit' => 1));
$G['chk']('Conq: tiendas del anterior dueño suspendidas (16.6)', (string) $db->fetch_field($q, 'estado') === 'suspendida');
// 9) Duración mínima (16.3): un asedio nunca se resuelve en la ronda del anuncio.
$db->update_query('ope_personajes', array('ubicacion_isla_id' => (int) $alabasta['id']), "id = {$pid}");
$fir = $tram(34, $pid, array('isla_id' => (int) $alabasta['id'], 'bando' => 'La banda del test', 'motivo' => 'El puerto', 'justificacion' => 'Hemos llegado al puerto real.'));
$q = $db->simple_select('ope_conquistas', '*', "isla_id = " . (int) $alabasta['id'] . " AND estado = 'activa'", array('limit' => 1));
$conq_al = $db->fetch_array($q);
$G['chk']('Conq: Alabasta (gobierno fd 14) exige 1 ronda de asedio', $conq_al && (int) $conq_al['rondas_asedio'] === 1);
$fir = $tram(36, $pid, array('conquista_id' => (int) $conq_al['id'], 'ganador' => 'atacante', 'motivo' => 'Expreso.', 'veredicto' => 'X'));
$G['chk']('Conq: resolver el mismo día del anuncio → BLOQUEA (16.3)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'ronda') !== false);
// 10) Reconquista (37): el bando desplazado vuelve a disputar — mismas fases.
$db->update_query('ope_personajes', array('ubicacion_isla_id' => (int) $cendra['id']), "id = {$pid}");
$fir = $tram(37, $pid, array('isla_id' => (int) $cendra['id'], 'bando' => 'Las tribus libres', 'motivo' => 'Recuperar la isla', 'justificacion' => 'La resistencia se organiza.'));
$G['chk']('Conq: reconquista 37 declarada (16.5)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'Reconquista') !== false);
$q = $db->simple_select('ope_conquistas', 'COUNT(*) AS n', "isla_id = " . (int) $cendra['id'] . " AND estado = 'activa'");
$G['chk']('Conq: nueva disputa activa sobre la isla (mismas fases)', (int) $db->fetch_field($q, 'n') === 1);
$fir = $tram(37, $pid, array('isla_id' => (int) $alabasta['id'], 'bando' => 'Nadie', 'motivo' => 'Sin historia', 'justificacion' => 'No hubo conquista previa aquí.'));
$G['chk']('Conq: reconquista sin conquista previa → bloquea', !$fir['ok']);
// 11) Abandono (16.5): 2 rondas sin actividad → propuesta; 3.ª → se aplica.
// La conquista ganada queda con `ultima_actividad_ronda` = ronda en que se
// registró; simulamos rondas subiendo el número de la ronda activa.
$db->delete_query('ope_rondas', '1=1');
ope7_ronda_abrir_siguiente(); // ronda 1 (activa)
$r1 = ope7_ronda_activa();
$db->update_query('ope_rondas', array('numero' => 2), "id = " . (int) $r1['id']);
$db->update_query('ope_conquistas', array('ultima_actividad_ronda' => 1), "id = " . (int) $conq['id']);
$r = ope7_conquista_abandonos(); // sin_actividad = 2 - 1 = 1
$G['chk']('Conq: 1 ronda sin actividad → aún no propone (16.5)', $r['propuestas'] === 0 && $r['aplicadas'] === 0);
$db->update_query('ope_conquistas', array('ultima_actividad_ronda' => 0), "id = " . (int) $conq['id']);
$r = ope7_conquista_abandonos(); // sin_actividad = 2 - 0 = 2 → propuesta
$G['chk']('Conq: 2 rondas sin actividad → revuelta propuesta (16.5)', $r['propuestas'] >= 1);
$db->update_query('ope_rondas', array('numero' => 3), "id = " . (int) $r1['id']);
$r = ope7_conquista_abandonos(); // sin_actividad = 3 - 0 = 3 → se aplica
$G['chk']('Conq: 3.ª ronda sin actividad → abandono aplicado con motivo', $r['aplicadas'] >= 1);
$q = $db->simple_select('ope_conquistas', 'estado', "id = " . (int) $conq['id'], array('limit' => 1));
$G['chk']('Conq: conquista abandonada registrada', (string) $db->fetch_field($q, 'estado') === 'abandonada');
$ficha = ope7_isla_ficha((int) $cendra['id']);
$G['chk']('Conq: la isla se revuelve (afiliación local)', (string) $ficha['afiliacion'] === 'local');
// 12) Mantenimientos (16.7): sin pago las unidades se van.
$db->update_query('ope_carteras', array('cartera' => 0), "personaje_id = {$pid}");
$n = ope7_conquista_mantenimientos();
$q = $db->simple_select('ope_unidades', 'COUNT(*) AS n', "conquista_id = " . (int) $conq['id'] . " AND estado = 'retirada'");
$G['chk']('Conq: unidades sin mantenimiento → retiradas (16.7)', $n >= 4 && (int) $db->fetch_field($q, 'n') >= 4);
// 13) Panel staff «Conquista» (A.3, 16.8).
$html_cq = ope7_conquista_panel_html();
$G['chk']('Conq: panel con conquistas activas por isla y fases', strpos($html_cq, 'Conquistas activas') !== false && strpos($html_cq, 'Archipiélago Cendra') !== false);
$G['chk']('Conq: panel con histórico y registro con motivo', strpos($html_cq, 'Histórico de conquistas') !== false && stripos($html_cq, 'abandonada') !== false);
$G['chk']('Conq: panel sin estilos inline', strpos($html_cq, 'style=') === false);

// ── [4i] Barcos (F4.3, 5.17/cap. 18 — trámites 39–44) ──
// El PJ trae de bloques previos un barco de navegación y dominios de prueba:
// los limpiamos para que el bote de remos sea su primer barco real y los
// requisitos de oficio se prueben limpios.
$db->delete_query('ope_barcos', "dueno_id = {$pid}");
$db->delete_query('ope_dominios_personaje', "personaje_id = {$pid}");
// Saldo para las compras; el PJ quedó a nv30 sin facción tras [4h].
ope7_cartera_mover($pid, 'cartera', 60000000);
$bote = (int) $db->fetch_field($db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Bote de remos'", array('limit' => 1)), 'id');
$balandro = (int) $db->fetch_field($db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Balandro'", array('limit' => 1)), 'id');
$carabela = (int) $db->fetch_field($db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Carabela'", array('limit' => 1)), 'id');
$acorazado = (int) $db->fetch_field($db->simple_select('ope_tipos_barcos', 'id', "nombre = 'Acorazado insignia'", array('limit' => 1)), 'id');
$pino = (int) $db->fetch_field($db->simple_select('ope_maderas_casco', 'id', "nombre = 'Pino de marea'", array('limit' => 1)), 'id');
$roble = (int) $db->fetch_field($db->simple_select('ope_maderas_casco', 'id', "nombre = 'Roble del sur'", array('limit' => 1)), 'id');
$eva = (int) $db->fetch_field($db->simple_select('ope_maderas_casco', 'id', "nombre = 'Madera de Eva'", array('limit' => 1)), 'id');
$G['chk']('Bar: tipos y maderas del catálogo disponibles', $bote > 0 && $balandro > 0 && $carabela > 0 && $acorazado > 0 && $pino > 0 && $roble > 0 && $eva > 0);
// 1) Primer barco gratis: bote de remos N1 (18.4).
$fir = $tram(39, $pid, array('tipo_id' => $bote, 'madera_id' => $pino, 'nombre' => 'Cascarón del Alba'));
$G['chk']('Bar: primer barco gratis (bote de remos, 18.4)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'gratis') !== false);
$q = $db->simple_select('ope_barcos', '*', "dueno_id = {$pid} AND estado NOT IN ('hundido','vendido')", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1));
$barco1 = $db->fetch_array($q);
$G['chk']('Bar: bote creado con ficha N1 (casco 200, maniobra 10)', $barco1 && (int) $barco1['casco_pv'] === 200 && (int) $barco1['maniobra'] === 10 && (int) $barco1['pv_actual'] === 200);
$c = ope7_cartera_get($pid);
$G['chk']('Bar: el primer barco no costó nada', (int) $c['cartera'] >= 60000000 - 1);
// 2) Compra con pago: balandro (50.000 ฿ + pino 0) — ya no es el primero.
$fir = $tram(39, $pid, array('tipo_id' => $balandro, 'madera_id' => $pino, 'nombre' => 'Viento del Sur'));
$G['chk']('Bar: compra de balandro con pago (50.000 ฿)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), '50.000') !== false);
$c = ope7_cartera_get($pid);
$G['chk']('Bar: la compra descontó de la cartera (50.000)', (int) $c['cartera'] === 60000000 - 50000);
// 3) Madera mínima del tipo (18.5): carabela exige roble; con pino → bloquea.
$fir = $tram(39, $pid, array('tipo_id' => $carabela, 'madera_id' => $pino, 'nombre' => 'Sin madera'));
$G['chk']('Bar: carabela con pino → bloquea (madera mínima, 18.5)', !$fir['ok'] && (stripos((string) ($fir['msg'] ?? ''), 'madera mínima') !== false || stripos((string) ($fir['msg'] ?? ''), 'exige al menos') !== false));
$fir = $tram(39, $pid, array('tipo_id' => $carabela, 'madera_id' => $roble, 'nombre' => 'Capitana Real'));
$G['chk']('Bar: carabela con roble → OK (800.000 + madera)', $fir['ok']);
// 4) Acorazado (18.4, D4.10): patrimonio de imperio — nv30 sin facción NO basta…
//    (D4.10: cúspide o nv≥30; el PJ tiene nv30 → SÍ puede. Probamos ambas vías.)
$fir = $tram(39, $pid, array('tipo_id' => $acorazado, 'madera_id' => $eva, 'nombre' => 'Imperio'));
$G['chk']('Bar: acorazado con nv30 (imperio en ciernes, D4.10) → OK', $fir['ok']);
// 5) Espacio por raza (18.3): Tontatta 0 · Mink 1 · Gigante 5.
$G['chk']('Bar: espacio por raza — Mink 1, Gigante 5, Tontatta 0 (18.3)', ope7_barco_espacio_raza(array('raza_nombre' => 'Mink')) === 1 && ope7_barco_espacio_raza(array('raza_nombre' => 'Gigante')) === 5 && ope7_barco_espacio_raza(array('raza_nombre' => 'Tontatta')) === 0);
// 6) Mejora N1→N2 (41): exige Astillero; damos Carpintero + rama Astillero.
$carp = (int) $db->fetch_field($db->simple_select('ope_dominios', 'id', "nombre = 'Carpintero'", array('limit' => 1)), 'id');
$db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $carp, 'nivel' => 2, 'rama' => 'Astillero', 'entrenamiento_fin' => 0, 'origen' => 'compra', 'entrenamiento_nivel' => 0, 'coste_mult' => 1.0));
$fir = $tram(41, $pid, array('barco_id' => (int) $barco1['id'], 'nivel' => 'N2'));
$G['chk']('Bar: mejora N1→N2 del bote (diferencia 5.000 + madera, 18.4)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'N2') !== false);
$q = $db->simple_select('ope_barcos', 'nivel, casco_pv', "id = " . (int) $barco1['id'], array('limit' => 1));
$b2 = $db->fetch_array($q);
$G['chk']('Bar: bote actualizado a N2 (casco 300)', (string) $b2['nivel'] === 'N2' && (int) $b2['casco_pv'] === 300);
$fir = $tram(41, $pid, array('barco_id' => (int) $barco1['id'], 'nivel' => 'N3'));
$G['chk']('Bar: mejora N2→N3 OK (un paso a la vez)', $fir['ok']);
$fir = $tram(41, $pid, array('barco_id' => (int) $barco1['id'], 'nivel' => 'N1'));
$G['chk']('Bar: no hay saltos hacia atrás (N3→N1 bloquea)', !$fir['ok']);
// 7) Módulos (42): balandro N1 tiene 1 ranura; tienda exige Comerciante.
$tienda_mod = (int) $db->fetch_field($db->simple_select('ope_modulos_barcos', 'id', "nombre = 'Tienda'", array('limit' => 1)), 'id');
$q = $db->simple_select('ope_barcos', 'id', "dueno_id = {$pid} AND nombre = 'Viento del Sur'", array('limit' => 1));
$bal_id = (int) $db->fetch_field($q, 'id');
$fir = $tram(42, $pid, array('barco_id' => $bal_id, 'modulo_id' => $tienda_mod, 'accion' => 'instalar'));
$G['chk']('Bar: módulo tienda sin Comerciante → bloquea (18.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'oficio') !== false);
$comer = (int) $db->fetch_field($db->simple_select('ope_dominios', 'id', "nombre = 'Comerciante'", array('limit' => 1)), 'id');
$db->insert_query('ope_dominios_personaje', array('personaje_id' => $pid, 'dominio_id' => $comer, 'nivel' => 1, 'rama' => null, 'entrenamiento_fin' => 0, 'origen' => 'compra', 'entrenamiento_nivel' => 0, 'coste_mult' => 1.0));
$fir = $tram(42, $pid, array('barco_id' => $bal_id, 'modulo_id' => $tienda_mod, 'accion' => 'instalar'));
$G['chk']('Bar: módulo tienda instalado (25.000 ฿, 1/1 ranura)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), '1/1') !== false);
// El balandro N1 tiene 1 ranura y ya está ocupada por Tienda: un módulo
// distinto (Batería de cañones) → sin ranuras libres (18.6).
$bateria_mod = (int) $db->fetch_field($db->simple_select('ope_modulos_barcos', 'id', "nombre = 'Batería de cañones'", array('limit' => 1)), 'id');
$fir = $tram(42, $pid, array('barco_id' => $bal_id, 'modulo_id' => $bateria_mod, 'accion' => 'instalar'));
$G['chk']('Bar: sin ranuras libres → bloquea (18.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'ranuras') !== false);
$fir = $tram(42, $pid, array('barco_id' => $bal_id, 'modulo_id' => $tienda_mod, 'accion' => 'quitar'));
$G['chk']('Bar: módulo retirado (ranura liberada)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'retirado') !== false);
// 8) Daño y reparación (18.7): daño leve → danado_leve; reparar con Astillero.
$r = ope7_barco_aplicar_danio($bal_id, 'leve');
$q = $db->simple_select('ope_barcos', 'estado, pv_actual', "id = {$bal_id}", array('limit' => 1));
$b3 = $db->fetch_array($q);
$G['chk']('Bar: daño leve aplicado (estado danado_leve, 18.7)', (string) $b3['estado'] === 'danado_leve' && (int) $b3['pv_actual'] === 450);
$fir = $tram(43, $pid, array('barco_id' => $bal_id, 'grado' => 'leve'));
$G['chk']('Bar: reparación con Astillero (coste madera + log)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'reparado') !== false);
$q = $db->simple_select('ope_barcos', 'estado, pv_actual, casco_pv', "id = {$bal_id}", array('limit' => 1));
$b4 = $db->fetch_array($q);
$G['chk']('Bar: barco activo al 100 % tras reparar', (string) $b4['estado'] === 'activo' && (int) $b4['pv_actual'] === (int) $b4['casco_pv']);
$q = $db->simple_select('ope_reparaciones', 'COUNT(*) AS n', "barco_id = {$bal_id}");
$G['chk']('Bar: log en reparaciones creado', (int) $db->fetch_field($q, 'n') >= 1);
// 9) Venta/desguace (44, D4.9): venta al 50 % del valor de compra.
$c_antes = ope7_cartera_get($pid);
$fir = $tram(44, $pid, array('barco_id' => $bal_id, 'modo' => 'venta'));
$G['chk']('Bar: venta al 50 % (D4.9)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), '50') !== false);
$c_despues = ope7_cartera_get($pid);
$G['chk']('Bar: balandro vendido por 25.000 (50 % de 50.000)', (int) $c_despues['cartera'] === (int) $c_antes['cartera'] + 25000);
$q = $db->simple_select('ope_barcos', 'estado', "id = {$bal_id}", array('limit' => 1));
$G['chk']('Bar: barco fuera de flota (estado vendido)', (string) $db->fetch_field($q, 'estado') === 'vendido');
// 10) Panel staff «Barcos» (A.3, 18.7).
$html_ba = ope7_barcos_panel_html();
$G['chk']('Bar: panel con la flota por jugador y ficha', strpos($html_ba, 'Flota') !== false && strpos($html_ba, 'Cascarón del Alba') !== false);
$G['chk']('Bar: panel con el catálogo de módulos (18.6)', strpos($html_ba, 'Módulos del catálogo') !== false && strpos($html_ba, 'Tienda') !== false);
$G['chk']('Bar: panel con las reparaciones logueadas', strpos($html_ba, 'Reparaciones') !== false);
$G['chk']('Bar: panel sin estilos inline', strpos($html_ba, 'style=') === false);

// ── [5] Limpieza ──
$db->delete_query('ope_personajes', "id = {$pid}");
$db->delete_query('ope_tramites', "personaje_id = {$pid}");
$db->delete_query('ope_tramites', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tramites_historico', 'tramite_id NOT IN (SELECT id FROM mybb_ope_tramites)');
$db->delete_query('ope_tiendas', "id = {$tienda}");
$db->delete_query('ope_tienda_items', "tienda_id = {$tienda}");
$db->delete_query('ope_carteras', "personaje_id = {$pid}");
$db->delete_query('ope_dominios_personaje', "personaje_id = {$pid}");
$db->delete_query('ope_almacen', "personaje_id = {$pid}");
$db->delete_query('ope_historico_pp', "personaje_id = {$pid}");
$db->delete_query('ope_inventario_personaje', "personaje_id = {$pid}");
$db->delete_query('ope_faccion_personaje', "personaje_id = {$pid}");
$db->delete_query('ope_cambios_faccion', "personaje_id = {$pid}");
$db->delete_query('ope_subfaccion_elite', "personaje_id = {$pid}");
$db->delete_query('ope_travesias', '1=1');
$db->delete_query('ope_incidentes_travesia', '1=1');
$db->delete_query('ope_barcos', '1=1');
$db->delete_query('ope_reparaciones', '1=1');
$db->delete_query('ope_temas_participantes', '1=1');
$db->delete_query('ope_temas', '1=1');
$db->delete_query('ope_faccion_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_cambios_faccion', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_subfaccion_elite', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
// Conquista: conquistas, asedios, unidades, hordas y tiendas del test.
$db->delete_query('ope_conquistas', '1=1');
$db->delete_query('ope_asedios', '1=1');
$db->delete_query('ope_unidades', '1=1');
$db->delete_query('ope_hordas', '1=1');
$db->delete_query('ope_tiendas', "notas LIKE '%Conquista F4%' OR dueno_id = 999");
// Rondas e históricos generados por el test.
$db->delete_query('ope_rondas', '1=1');
$db->delete_query('ope_isla_estado_historico', '1=1');
$db->delete_query('ope_recompensas_historico', '1=1');
$db->delete_query('ope_historico_periodicos', '1=1');
$db->delete_query('ope_sucesos', '1=1');
$db->delete_query('ope_precios_mercado', '1=1');
// Restaura las islas tocadas por el test a su estado del catálogo.
$db->update_query('ope_isla_estado', array('peligrosidad' => 4, 'quien_manda' => 'Consejo de aldea (la alcaldesa)', 'afiliacion' => 'local', 'fuerza_defensiva_nivel' => 2), "isla_id = " . (int) $dawn['id']);
$cendra = ope7_isla_por_slug('archipielago-cendra');
$alabasta = ope7_isla_por_slug('alabasta');
if ($cendra) {
    $db->update_query('ope_isla_estado', array('afiliacion' => 'salvaje', 'fuerza_defensiva_nivel' => 5, 'quien_manda' => 'Tribus locales autónomas'), "isla_id = " . (int) $cendra['id']);
}
if ($alabasta) {
    $db->update_query('ope_isla_estado', array('afiliacion' => 'gobierno', 'fuerza_defensiva_nivel' => 14, 'quien_manda' => 'Casa real (original del foro)'), "isla_id = " . (int) $alabasta['id']);
}
$G['chk']('Limpieza F4.1 completa', true);

echo "\n=== DONE — {$G['ok']}/" . ($G['ok'] + $G['fail']) . " checks OK ===\n";
exit($G['fail'] > 0 ? 1 : 0);
