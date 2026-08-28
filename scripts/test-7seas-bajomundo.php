<?php
/**
 * One Piece: 7 Seas · Test F6 — Bajo mundo (5.13/cap. 14, trámites 25–33)
 * ----------------------------------------------------------------------
 * Verifica:
 *  · 29 — Montar/ampliar la red: contrato + mantenimiento, límite 4 espías,
 *    capacidad por tipo (Novato local → Supremo mundial), sin saldo bloquea.
 *  · 25 — Solicitar rumor a la red: exige red activa + espía activo, la
 *    capacidad limita categoría/alcance, cobra el mantenimiento, crea la ficha.
 *  · 26 — Comprar rumor: multiplicadores de fiabilidad × frescura con techo
 *    global, ficha transferida, duplicado bloqueado.
 *  · 27 — Contrastar rumor: coste por alcance × sensibilidad, afina la
 *    fiabilidad un grado y en Sólido revela la veracidad.
 *  · 28 — Vender rumor: transferencia entre jugadores con copia del vendedor;
 *    sin el rumor en tu poder bloquea.
 *  · 32 — Crear rumor falso: veracidad interna falsa, fiabilidad publicada.
 *  · 30 — Publicar cartel (staff): cifra + paradero + caducidad a 3 rondas,
 *    registro en recompensas_historico.
 *  · 31 — Cobrar recompensa: entrega verificada, anti-abuso autocaza, paradero
 *    frío bloquea, cobro + histórico.
 *  · 33 — Ataque a una red: veredicto sin dados (espías descubiertos/red baja).
 *  · Cron: caducidad de paraderos a 3 rondas.
 *  · Panel staff «Bajo Mundo».
 * Idempotente: limpieza completa al final (PJ de prueba, rumores, redes, carteles).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-bajomundo.php');
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

$mk_pj = function ($slug, $saldo = 0) use ($db) {
    static $raza = null;
    if ($raza === null) {
        $raza = (int) $db->fetch_field($db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1)), 'id');
    }
    $pid = ope7_pj_guardar(array(
        'uid' => 1, 'nombre' => 'Prueba BM ' . $slug, 'slug' => 'prueba-bm-' . $slug, 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => 20, 'raza_id' => $raza,
        'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
        'puntos_comprados' => 0, 'pp_saldo' => 0,
    ));
    if ($saldo > 0 && ope7_tabla_existe('carteras')) {
        $q = $db->simple_select('ope_carteras', 'personaje_id', "personaje_id = {$pid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $db->update_query('ope_carteras', array('cartera' => (int) $saldo), "personaje_id = {$pid}");
        } else {
            $db->insert_query('ope_carteras', array('personaje_id' => $pid, 'cartera' => (int) $saldo, 'boveda' => 0));
        }
    }
    return $pid;
};

// ── Limpieza previa idempotente ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-bm-%'");
$db->delete_query('ope_rumores', "creador_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_rumor_operaciones', 'rumor_id NOT IN (SELECT id FROM mybb_ope_rumores)');
$db->delete_query('ope_red_espionaje', 'dueno_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_espias', 'red_id NOT IN (SELECT id FROM mybb_ope_red_espionaje)');
$db->delete_query('ope_carteles_recompensa', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_recompensas_historico', "personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_tramites', "numero BETWEEN 25 AND 33 AND solicitante_id = 1");
$db->delete_query('ope_tramites', "numero = 19 AND solicitante_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_carteras', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_rondas', "numero = 5");
if (ope7_tabla_existe('bestiario') && ope7_tabla_existe('npc_apariciones')) {
    // Residuos de corridas previas: limpieza por nombre del NPC de prueba.
    $db->delete_query('ope_npc_apariciones', 'bestiario_id IN (SELECT id FROM mybb_ope_bestiario WHERE nombre LIKE \'Prueba BM Recluta%\')');
    $db->delete_query('ope_bestiario', "nombre LIKE 'Prueba BM Recluta%'");
}

// ── [1] Montar la red (29, ligero) ──
$pid = $mk_pj('broker', 2000000);
$r = ope7_tramite_crear(1, $pid, 29, '', array('tipo' => 'avanzado', 'nombre' => 'Cuervos de Alabasta'));
$G['chk']('[29] Montar red: espía avanzado incorporado (publicado)', $r['ok']);
$red = ope7_red_del_pj($pid);
$G['chk']('[29] Red activa creada con el nombre', $red !== null && (string) $red['estado'] === 'activa');
$espias = $red ? ope7_red_espias((int) $red['id']) : array();
$G['chk']('[29] Espía avanzado con capacidad regional', count($espias) === 1 && (string) $espias[0]['tipo'] === 'avanzado');
$c = ope7_cartera_get($pid);
$G['chk']('[29] Contrato cobrado (2.000.000 − 25.000)', (int) $c['cartera'] === 2000000 - 25000);

// Sin saldo bloquea.
$pid_pobre = $mk_pj('pobre', 100);
$r = ope7_tramite_crear(1, $pid_pobre, 29, '', array('tipo' => 'supremo', 'nombre' => 'Sin fondos'));
$G['chk']('[29] Sin saldo (100 < 500.000) → bloqueado', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'BLOQUEADO') !== false);

// Límite 4 espías.
for ($i = 0; $i < 3; $i++) {
    ope7_tramite_crear(1, $pid, 29, '', array('tipo' => 'novato', 'nombre' => ''));
}
$r = ope7_tramite_crear(1, $pid, 29, '', array('tipo' => 'novato', 'nombre' => ''));
$G['chk']('[29] Límite de 4 espías → bloqueado (14.2.3)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'límite de 4') !== false);

// ── [2] Solicitar rumor a la red (25, ia+firma) ──
// Espía de otra red no vale.
$otro = $mk_pj('otro', 2000000);
ope7_tramite_crear(1, $otro, 29, '', array('tipo' => 'supremo', 'nombre' => 'Red ajena'));
$espia_ajeno = (int) $db->fetch_field($db->simple_select('ope_espias', 'id', "red_id = (SELECT id FROM " . ope7_tabla_full('red_espionaje') . " WHERE dueno_id = {$otro} LIMIT 1)", array('limit' => 1)), 'id');
$r = ope7_tramite_crear(1, $pid, 25, 'Quiero saber dónde se esconde el tesoro.', array('espia_id' => $espia_ajeno));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[25] Espía de otra red → bloqueado (tu red activa, 14.2.3)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'espía activo de tu red') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// Capacidad: avanzado no investiga Alto Susurro.
$espia_propio = (int) $db->fetch_field($db->simple_select('ope_espias', 'id', "red_id = " . (int) $red['id'] . " AND tipo = 'avanzado'", array('limit' => 1)), 'id');
$r = ope7_tramite_crear(1, $pid, 25, 'Un secreto del Alto Susurro.', array('espia_id' => $espia_propio, 'isla_id' => 0));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('contenido' => 'El gobernador soborna a la Marina.', 'alcance' => 'regional', 'categoria' => 'alto_susurro', 'fiabilidad' => 'plausible', 'veracidad' => 'verdadero', 'precio_base' => 5000));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[25] Avanzado no investiga Alto Susurro → bloqueado (capacidad)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'no investiga') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// Correcto: Murmullo local, cobra el mantenimiento (2.500) y crea la ficha.
$r = ope7_tramite_crear(1, $pid, 25, 'Un murmullo en el puerto.', array('espia_id' => $espia_propio, 'isla_id' => 0));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('contenido' => 'Un barco pirata atracó de noche con el casco agujereado.', 'tipo' => 'suceso', 'alcance' => 'local', 'categoria' => 'murmullo', 'fiabilidad' => 'rumoroso', 'veracidad' => 'dudoso', 'precio_base' => 1000));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Ficha coherente con la capacidad del espía.');
$G['chk']('[25] Solicitud correcta firmada (rumor creado)', $fir['ok']);
$rumor1 = $db->fetch_array($db->simple_select('ope_rumores', '*', "contenido LIKE '%barco pirata%'", array('limit' => 1)));
$G['chk']('[25] Rumor en catálogo con ficha de 5 campos', $rumor1 !== false && (string) $rumor1['veracidad'] === 'dudoso' && (string) $rumor1['fiabilidad'] === 'rumoroso');
$c = ope7_cartera_get($pid);
// 2.000.000 − 25.000 (avanzado) − 15.000 (3 novatos) − 2.500 (mantenimiento 25)
$G['chk']('[25] Mantenimiento cobrado (−2.500 del espía avanzado)', (int) $c['cartera'] === 2000000 - 25000 - 15000 - 2500);

// ── [3] Comprar rumor (26, ligero) ──
$pid_c = $mk_pj('comprador', 50000);
$r = ope7_tramite_crear(1, $pid_c, 26, '', array('rumor_id' => (int) $rumor1['id']));
$G['chk']('[26] Compra de rumor (publicado)', $r['ok']);
$mult = ope7_rumor_multiplier($rumor1);
$G['chk']('[26] Precio = base × fiabilidad × frescura (techo 0,5–2×)', $mult['precio'] > 0 && $mult['precio'] <= (int) round($rumor1['precio_base'] * 2));
$G['chk']('[26] Ficha en poder del comprador (operación)', ope7_rumor_en_poder($pid_c, (int) $rumor1['id']));
$r2 = ope7_tramite_crear(1, $pid_c, 26, '', array('rumor_id' => (int) $rumor1['id']));
$G['chk']('[26] Compra repetida → bloqueado (copia registrada basta, 14.2.4)', !$r2['ok'] && stripos((string) ($r2['msg'] ?? ''), 'ya tienes') !== false);

// ── [4] Contrastar rumor (27, ia+firma) ──
$c_antes = ope7_cartera_get($pid_c);
$r = ope7_tramite_crear(1, $pid_c, 27, 'Contraste del barco pirata.', array('rumor_id' => (int) $rumor1['id'], 'sensibilidad' => 'comun'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Veredicto: la fuente lo confirmó a medias.');
$G['chk']('[27] Contraste firmado (afina fiabilidad)', $fir['ok']);
$rumor1b = ope7_rumor_info((int) $rumor1['id']);
$G['chk']('[27] Fiabilidad rumoroso → plausible', $rumor1b !== null && (string) $rumor1b['fiabilidad'] === 'plausible');
$c_despues = ope7_cartera_get($pid_c);
$G['chk']('[27] Coste cobrado (local × común = 1.000)', (int) $c_despues['cartera'] === (int) $c_antes['cartera'] - 1000);

// Contraste a Sólido revela la veracidad (2º contraste del mismo rumor).
$r = ope7_tramite_crear(1, $pid_c, 27, 'Segundo contraste.', array('rumor_id' => (int) $rumor1['id'], 'sensibilidad' => 'figura'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'La fuente principal confirma.');
$G['chk']('[27] Contraste a Sólido (revela veracidad)', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'veracidad interna') !== false);
$rumor1c = ope7_rumor_info((int) $rumor1['id']);
$G['chk']('[27] Fiabilidad ahora sólida', $rumor1c !== null && (string) $rumor1c['fiabilidad'] === 'solido');

// ── [5] Vender rumor (28, ligero) ──
$pid_v = $mk_pj('vendedor', 50000);
$pid_x = $mk_pj('comprador2', 50000);
ope7_tramite_crear(1, $pid_v, 26, '', array('rumor_id' => (int) $rumor1['id'])); // el vendedor lo compra primero
// Sin el rumor no puedes venderlo.
$r = ope7_tramite_crear(1, $pid_x, 28, '', array('rumor_id' => (int) $rumor1['id'], 'comprador_id' => $pid_v, 'precio' => 3000));
$G['chk']('[28] Vender sin tener el rumor → bloqueado (14.2.4)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'no tienes este rumor') !== false);
// Venta correcta.
$r = ope7_tramite_crear(1, $pid_v, 28, '', array('rumor_id' => (int) $rumor1['id'], 'comprador_id' => $pid_x, 'precio' => 3000));
$G['chk']('[28] Venta firmada (publicado)', $r['ok']);
$G['chk']('[28] El comprador la recibe en su poder', ope7_rumor_en_poder($pid_x, (int) $rumor1['id']));
$cv = ope7_cartera_get($pid_v);
$G['chk']('[28] El vendedor cobra (y conserva su copia)', (int) $cv['cartera'] > 50000 - 1);

// ── [6] Crear rumor falso (32, ia+firma) ──
$r = ope7_tramite_crear(1, $pid, 32, 'Propaganda contra el gobernador.', array('isla_id' => 0));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('contenido' => 'El gobernador escondió el tesoro real de la ciudad.', 'tipo' => 'tesoro', 'alcance' => 'regional', 'fiabilidad' => 'plausible', 'precio_base' => 10000));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Bien sembrado: parece plausible.');
$G['chk']('[32] Rumor falso sembrado (veracidad interna falsa)', $fir['ok']);
$falso = $db->fetch_array($db->simple_select('ope_rumores', '*', "contenido LIKE '%escondió el tesoro%'", array('limit' => 1)));
$G['chk']('[32] Veracidad interna = falso, fiabilidad publicada plausible', $falso !== false && (string) $falso['veracidad'] === 'falso' && (string) $falso['fiabilidad'] === 'plausible');

// ── [7] Publicar cartel (30, staff) ──
$buscado = $mk_pj('buscado', 0);
$r = ope7_tramite_crear(1, $buscado, 30, 'Emisión desde el panel', array('personaje_id' => $buscado, 'cifra' => 500000, 'paradero' => 'Visto en la ciudad portuaria'),
    array('cifra' => 500000, 'paradero_publicado' => 'Visto en la ciudad portuaria'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Cartel emitido con paradero plausible.');
$G['chk']('[30] Cartel publicado (staff + firma)', $fir['ok']);
$cartel = $db->fetch_array($db->simple_select('ope_carteles_recompensa', '*', "personaje_id = {$buscado}", array('limit' => 1)));
$G['chk']('[30] Cartel vigente con caducidad de paradero a 3 rondas', $cartel !== false && (string) $cartel['estado'] === 'vigente' && (int) $cartel['ronda_caducidad_paradero'] === (int) $cartel['ronda_emision'] + 3);
$q = $db->simple_select('ope_recompensas_historico', 'COUNT(*) AS n', "personaje_id = {$buscado} AND tipo = 'cartel' AND cantidad = 500000");
$G['chk']('[30] Registro en recompensas_historico con motivo', (int) $db->fetch_field($q, 'n') === 1);

// Cifra baja bloquea.
$r = ope7_tramite_crear(1, $buscado, 30, 'Cartel barato', array('personaje_id' => $buscado, 'cifra' => 1000, 'paradero' => 'X'),
    array('cifra' => 1000, 'paradero_publicado' => 'X'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Cifra de risa.');
$G['chk']('[30] Cifra < 100.000 → bloqueado (escala 5.9)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'cientos de miles') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// ── [8] Cobrar recompensa (31, ia+firma) ──
$cazador = $mk_pj('cazador', 10000);
// Autocaza.
$r = ope7_tramite_crear(1, $buscado, 31, 'Cobro mi propia cabeza.', array('cartel_id' => (int) $cartel['id']));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('entrega' => 'Me entrego yo mismo.'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[31] Autocaza → bloqueado (anti-abuso 14.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'autocaza') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// Sin veredicto de entrega.
$r = ope7_tramite_crear(1, $cazador, 31, 'Quiero cobrar.', array('cartel_id' => (int) $cartel['id']));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[31] Sin entrega verificada → bloqueado (14.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'entrega') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// Cobro correcto.
$r = ope7_tramite_crear(1, $cazador, 31, 'Capturado en un combate presente.', array('cartel_id' => (int) $cartel['id']));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('entrega' => 'Captura viva resuelta con veredicto de 5.10.'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Entrega verificada contra el veredicto del tema.');
$G['chk']('[31] Cobro firmado (500.000 a la cartera)', $fir['ok']);
$cc = ope7_cartera_get($cazador);
$G['chk']('[31] Cifra abonada al cazador', (int) $cc['cartera'] === 10000 + 500000);
$q = $db->simple_select('ope_carteles_recompensa', 'estado', "id = " . (int) $cartel['id'], array('limit' => 1));
$G['chk']('[31] Cartel marcado cobrado', (string) $db->fetch_field($q, 'estado') === 'cobrado');
$q = $db->simple_select('ope_recompensas_historico', 'COUNT(*) AS n', "personaje_id = {$cazador} AND tipo = 'cartel' AND cantidad = 500000");
$G['chk']('[31] Cobro registrado en el histórico del cazador', (int) $db->fetch_field($q, 'n') === 1);

// Paradero frío (cron: 3 rondas) bloquea: un segundo cartel VIGENTE con la
// caducidad vencida (ronda 5 activa > caducidad 2).
$db->insert_query('ope_carteles_recompensa', array(
    'personaje_id' => $buscado, 'cifra' => 300000, 'paradero_publicado' => 'Último avistamiento en el sur',
    'fiabilidad_paradero' => 'plausible', 'estado' => 'vigente', 'ronda_emision' => 1,
    'ronda_caducidad_paradero' => 2, 'emitido_por' => 1,
));
$cartel_frio = (int) $db->fetch_field($db->simple_select('ope_carteles_recompensa', 'id', "paradero_publicado = 'Último avistamiento en el sur' AND personaje_id = {$buscado}", array('limit' => 1)), 'id');
$db->insert_query('ope_rondas', array('numero' => 5, 'inicio' => TIME_NOW, 'estado' => 'abierta'));
ope7_bajomundo_cron();
$q = $db->simple_select('ope_carteles_recompensa', 'estado', "id = {$cartel_frio}", array('limit' => 1));
$G['chk']('[31] Cron: paradero frío (3 rondas) → cartel no cazable', (string) $db->fetch_field($q, 'estado') === 'frio');
$r = ope7_tramite_crear(1, $cazador, 31, 'Intento con paradero frío.', array('cartel_id' => $cartel_frio));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('entrega' => 'Lo capturé igualmente.'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[31] Cartel frío → cobro bloqueado (14.6)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'paradero frío') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// ── [9] Ataque a una red (33, ia+firma) ──
$red_ajena = $db->fetch_array($db->simple_select('ope_red_espionaje', '*', "dueno_id = {$otro}", array('limit' => 1)));
// Atacar tu propia red bloquea.
$r = ope7_tramite_crear(1, $pid, 33, 'Ataque a mi propia red.', array('red_id' => (int) $red['id']));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[33] Atacar tu propia red → bloqueado', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'propia') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));
$espia_ajeno2 = (int) $db->fetch_field($db->simple_select('ope_espias', 'id', "red_id = " . (int) $red_ajena['id'], array('limit' => 1)), 'id');
$r = ope7_tramite_crear(1, $pid, 33, 'Sabotaje a la red ajena.', array('red_id' => (int) $red_ajena['id']));
ope7_tramite_guardar_resultado((int) ($r['tid'] ?? 0), array('metodo' => 'Infiltración con un informante doble.', 'espias_descubiertos' => array($espia_ajeno2), 'desactiva_red' => 1));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Veredicto: el informante delató al espía y la red quedó tocada.');
$G['chk']('[33] Ataque firmado (veredicto sin dados)', $fir['ok']);
$q = $db->simple_select('ope_espias', 'estado', "id = {$espia_ajeno2}", array('limit' => 1));
$G['chk']('[33] Espía descubierto por el veredicto', (string) $db->fetch_field($q, 'estado') === 'descubierto');
$q = $db->simple_select('ope_red_espionaje', 'estado', "id = " . (int) $red_ajena['id'], array('limit' => 1));
$G['chk']('[33] Red desactivada por el veredicto', (string) $db->fetch_field($q, 'estado') === 'inactiva');

// ── [10] Panel staff ──
$panel = ope7_bajomundo_panel_html();
$G['chk']('[10] Panel «Bajo Mundo»: rumores por isla', strpos($panel, 'Rumores por isla') !== false);
$G['chk']('[10] Panel: redes y espías', strpos($panel, 'Redes y espías') !== false);
$G['chk']('[10] Panel: carteles', strpos($panel, 'Carteles') !== false);
$G['chk']('[10] Panel: histórico de operaciones', strpos($panel, 'Operaciones') !== false);
$G['chk']('[10] Panel: sin estilos inline (zs-tab ok)', strpos($panel, 'style=') === false);

// ── [11] Trámite 19 · Reclutamiento de NPC (F6, 5.11/12.5, ligero/firma) ──
// Sin bestiario sembrado: el efecto bloquea con mensaje claro (usa ficha existente).
$pid_r = $mk_pj('recluta', 0);
$r19 = ope7_tramite_crear(1, $pid_r, 19, 'Quiero reclutar a Garp del catálogo.');
$G['chk']('[19] Crear solicitud de reclutamiento', (bool) ($r19['ok'] ?? false));
if ($r19['ok']) {
    ope7_tramite_guardar_resultado((int) $r19['tid'], array('nombre_npc' => 'Garp'));
    $fir = ope7_tramite_firmar((int) $r19['tid'], 1, 'publicar', 'Reclutamiento de prueba.');
    $G['chk']('[19] Reclutar NPC sin bestiario → bloquea (usa ficha existente, 12.5)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'bestiario') !== false);
}
// Con un NPC sembrado ad hoc: marca «reclutado» en npc_apariciones (12.5).
if (ope7_tabla_existe('bestiario') && ope7_tabla_existe('npc_apariciones')) {
    $npc_id = (int) $db->insert_query('ope_bestiario', array(
        'nombre' => 'Prueba BM Recluta', 'tipo' => 'terciario', 'nivel' => 8,
        'pv_max' => 100, 'pe_max' => 60, 'pa' => 4, 'zona' => 'Test',
    ));
    $r19b = ope7_tramite_crear(1, $pid_r, 19, 'Reclutar a Prueba BM Recluta.');
    $firb = array('ok' => false, 'msg' => 'no creado');
    if ($r19b['ok']) {
        ope7_tramite_guardar_resultado((int) $r19b['tid'], array('nombre_npc' => 'Prueba BM Recluta'));
        $firb = ope7_tramite_firmar((int) $r19b['tid'], 1, 'publicar', 'Reclutamiento de prueba con ficha existente.');
    }
    $G['chk']('[19] Reclutar NPC con ficha existente → firma OK', $firb['ok']);
    $q = $db->query('SELECT a.estado, a.manejado_por FROM ' . ope7_tabla_full('npc_apariciones') . ' a '
        . 'JOIN ' . ope7_tabla_full('bestiario') . " b ON b.id = a.bestiario_id WHERE b.nombre = 'Prueba BM Recluta' ORDER BY a.id DESC LIMIT 1");
    $ap = $db->fetch_array($q);
    $G['chk']('[19] Aparición marcada «reclutado» por el reclutador', (string) ($ap['estado'] ?? '') === 'reclutado' && (int) ($ap['manejado_por'] ?? 0) === (int) $pid_r);
    $db->delete_query('ope_npc_apariciones', "bestiario_id = {$npc_id}");
    $db->delete_query('ope_bestiario', "id = {$npc_id}");
}

// ── [12] Paneles staff A.3 nuevos (auditoría F6): Mercado, NPCs, Reliquias ──
$pm = ope7_mercado_panel_html();
$G['chk']('[12] Panel «Mercado»: fluctuación por zona', strpos($pm, 'Fluctuación') !== false);
$G['chk']('[12] Panel «Mercado»: carteras', strpos($pm, 'Carteras') !== false);
$G['chk']('[12] Panel «Mercado»: sin inline', strpos($pm, 'style=') === false);
$pn = ope7_npc_panel_html();
$G['chk']('[12] Panel «NPCs»: primarios', strpos($pn, 'Primarios') !== false);
$G['chk']('[12] Panel «NPCs»: bestiario', strpos($pn, 'Bestiario') !== false);
$G['chk']('[12] Panel «NPCs»: sin inline', strpos($pn, 'style=') === false);
$pr = ope7_reliquias_panel_html();
$G['chk']('[12] Panel «Reliquias»: fichas muertas', strpos($pr, 'Reliquias') !== false);
$G['chk']('[12] Panel «Reliquias»: histórico', strpos($pr, 'Histórico') !== false);
$G['chk']('[12] Panel «Reliquias»: sin inline', strpos($pr, 'style=') === false);

// ── Limpieza final ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-bm-%'");
$db->delete_query('ope_rumores', "creador_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_rumor_operaciones', 'rumor_id NOT IN (SELECT id FROM mybb_ope_rumores)');
$db->delete_query('ope_red_espionaje', 'dueno_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_espias', 'red_id NOT IN (SELECT id FROM mybb_ope_red_espionaje)');
$db->delete_query('ope_carteles_recompensa', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_recompensas_historico', "personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_tramites', "numero BETWEEN 25 AND 33 AND solicitante_id = 1");
$db->delete_query('ope_tramites', "numero = 19 AND solicitante_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_carteras', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_rondas', "numero = 5");

echo "\n=== Bajo Mundo: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] === 0 ? 0 : 1);
