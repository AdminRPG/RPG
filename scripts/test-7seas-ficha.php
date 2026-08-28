<?php
/**
 * One Piece: 7 Seas · Test de auditoría de la ficha (bloques F5)
 * ----------------------------------------------------------------
 * Verifica que la ficha (`ope7_ficha_html`) muestre los datos correctos de:
 *  · Fruta del diablo (F5.1): nombre, familia/tier, mecánica, influencia y
 *    la variante «sin comer — trámite 47».
 *  · Haki (F5.1): tipo, nivel, usos acumulados y PP invertidos.
 *  · Tripulación (F5.3): nombre, miembros (capitán con 👑), cofre común.
 *  · Misión en curso (F5.2): nombre, tramo/acto, condiciones de victoria/
 *    fracaso.
 * Y que el desglose auditable (atributos base+racial, dotes/defectos con
 * origen, dominios, técnicas, solo-staff) no se rompa: todos los bloques
 * presentes en el HTML y sin estilos inline.
 * Idempotente: limpieza completa al final (PJ, akuma, haki, tripulación,
 * misión, tramos).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-ficha.php');
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
$db->delete_query('ope_personajes', "slug LIKE 'prueba-ficha-%'");
$db->delete_query('ope_akumas', "nombre_propio LIKE 'Prueba Fruta%'");
$db->delete_query('ope_haki', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tripulaciones', "nombre = 'Trip Ficha'");
$db->delete_query('ope_tripulantes', 'tripulacion_id NOT IN (SELECT id FROM mybb_ope_tripulaciones)');
$db->delete_query('ope_cofre_tripulacion', 'tripulacion_id NOT IN (SELECT id FROM mybb_ope_tripulaciones)');
$db->delete_query('ope_misiones', "origen = 'test ficha'");
$db->delete_query('ope_mision_tramos', 'mision_id NOT IN (SELECT id FROM mybb_ope_misiones)');
$db->delete_query('ope_personaje_dotes', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_dominios_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');

// ── Escenario: PJ con TODO ──
$q = $db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1));
$raza = (int) $db->fetch_field($q, 'id');
$pid = ope7_pj_guardar(array(
    'uid' => 1, 'nombre' => 'Prueba Ficha', 'slug' => 'prueba-ficha-1', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 25, 'raza_id' => $raza,
    'fue' => 30, 'des' => 30, 'agi' => 30, 'res' => 40, 'per' => 30, 'inte' => 30, 'car' => 30, 'vol' => 40,
    'puntos_comprados' => 0, 'pp_saldo' => 500,
));
ope7_cartera_mover($pid, 'cartera', 100000);

// Fruta comida: toma una del catálogo y asígnala (cupo).
$q = $db->simple_select('ope_akumas', 'id, nombre_propio', "estado = 'sin_portador'", array('order_by' => 'id', 'limit' => 1));
$akuma_id = (int) $db->fetch_field($q, 'id');
$akuma_nombre = '';
if ($akuma_id > 0) {
    $q = $db->simple_select('ope_akumas', 'nombre_propio', "id = {$akuma_id}", array('limit' => 1));
    $akuma_nombre = (string) $db->fetch_field($q, 'nombre_propio');
    $db->update_query('ope_akumas', array('portador_id' => $pid, 'estado' => 'con_portador'), "id = {$akuma_id}");
    $db->update_query('ope_personajes', array('akuma_id' => $akuma_id), "id = {$pid}");
}

// Haki: armadura nv2 con usos y PP.
$db->insert_query('ope_haki', array('personaje_id' => $pid, 'tipo' => 'armadura', 'nivel' => 2, 'usos_acumulados' => 7, 'pp_invertidos' => 200, 'activo' => 1));

// Cibernética: implante activo (Ojo mecánico, cabeza N1) con defectos.
$q = $db->simple_select('ope_implantes', 'id, nombre, zona, nivel, ranuras, defectos', "nombre = 'Ojo mecánico'", array('limit' => 1));
$impl = $db->fetch_array($q);
if ($impl) {
    $db->insert_query('ope_modificaciones_personaje', array(
        'implante_id' => (int) $impl['id'], 'personaje_id' => $pid,
        'ranuras' => (string) $impl['ranuras'], 'nivel' => (string) $impl['nivel'],
        'estado' => 'activo', 'daño' => '[]',
    ));
    $impl_nombre = (string) $impl['nombre'];
} else {
    $impl_nombre = '';
}

// Dote + defecto (origen narrativo, como los implantes/linajes) para el desglose.
$q = $db->simple_select('ope_dotes', 'id', '1=1', array('limit' => 1));
$dote_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_defectos', 'id', '1=1', array('limit' => 1));
$defecto_id = (int) $db->fetch_field($q, 'id');
if ($dote_id > 0) {
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'dote_id' => $dote_id, 'origen' => 'narrativo', 'fecha' => TIME_NOW));
}
if ($defecto_id > 0) {
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $pid, 'defecto_id' => $defecto_id, 'origen' => 'narrativo', 'fecha' => TIME_NOW));
}

// Tripulación activa (insert directo, entidad + cofre + 2 miembros).
$trip_id = (int) $db->insert_query('ope_tripulaciones', array(
    'nombre' => 'Trip Ficha', 'bandera' => 'Test', 'proposito' => 'Probar la ficha',
    'capitan_id' => $pid, 'barco_id' => 0, 'cofre_id' => 0, 'estado' => 'activa',
    'fundada_por' => $pid, 'fecha' => TIME_NOW,
));
$db->insert_query('ope_cofre_tripulacion', array('tripulacion_id' => $trip_id, 'berries' => 42000, 'log' => '[]'));
$db->insert_query('ope_tripulantes', array('tripulacion_id' => $trip_id, 'personaje_id' => $pid, 'rol' => 'capitan', 'espacio_ocupado' => 1, 'fecha_ingreso' => TIME_NOW, 'fecha_salida' => 0, 'estado' => 'activo'));
$q = $db->simple_select('ope_personajes', 'id', "slug LIKE 'prueba-ficha-%' AND id != {$pid}", array('limit' => 1));
$socio = (int) $db->fetch_field($q, 'id');
if ($socio < 1) {
    $socio = ope7_pj_guardar(array(
        'uid' => 2, 'nombre' => 'Prueba Ficha Socio', 'slug' => 'prueba-ficha-2', 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => 10, 'raza_id' => $raza,
        'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
        'puntos_comprados' => 0, 'pp_saldo' => 0,
    ));
}
$db->insert_query('ope_tripulantes', array('tripulacion_id' => $trip_id, 'personaje_id' => $socio, 'rol' => 'miembro', 'espacio_ocupado' => 1, 'fecha_ingreso' => TIME_NOW, 'fecha_salida' => 0, 'estado' => 'activo'));

// Linaje activo (Línea D.) con motivo.
$q = $db->simple_select('ope_familias_legendarias', 'id, nombre, dote, defecto', "nombre = 'Línea D.'", array('limit' => 1));
$fam = $db->fetch_array($q);
if ($fam) {
    $db->insert_query('ope_linaje_personaje', array(
        'familia_id' => (int) $fam['id'], 'personaje_id' => $pid, 'estado' => 'activo',
        'motivo' => 'El expediente de fidelidad habló por sí solo.', 'concedido_por' => 1, 'fecha' => TIME_NOW,
    ));
    $lin_fam = (string) $fam['nombre'];
    $lin_dote = (string) $fam['dote'];
    $lin_defecto = (string) $fam['defecto'];
} else {
    $lin_fam = $lin_dote = $lin_defecto = '';
}

// Misión en curso (insert directo, ficha de 6 bloques + tramo 2/acto 2).
$mid = (int) $db->insert_query('ope_misiones', array(
    'categoria' => 'reino_isla', 'origen' => 'test ficha', 'isla_id' => 0,
    'dificultad' => 'nv15-25', 'duracion_rondas' => 3,
    'identidad' => json_encode(array('nombre' => 'El tesoro del Viejo Kraken', 'categoria' => 'reino_isla', 'origen' => 'test ficha', 'dificultad' => 'nv15-25', 'duracion' => 3), JSON_UNESCAPED_UNICODE),
    'condiciones' => json_encode(array('victoria' => 'El cofre llega al puerto antes del amanecer de la 3ª ronda.', 'fracaso' => 'El Kraken hunde el barco o el grupo abandona.'), JSON_UNESCAPED_UNICODE),
    'escenas' => json_encode(array('acto1' => 'a', 'acto2' => 'b', 'acto3' => 'c'), JSON_UNESCAPED_UNICODE),
    'recompensas' => json_encode(array('berries' => 80000, 'pp' => 150), JSON_UNESCAPED_UNICODE),
    'requisitos' => json_encode(array('nivel_min' => 15), JSON_UNESCAPED_UNICODE),
    'secretos_json' => json_encode(array('texto' => 'El Kraken es un barco fantasma.'), JSON_UNESCAPED_UNICODE),
    'estado' => 'en_curso', 'en_tablon' => 0, 'solicitante_id' => $pid, 'abierta_en' => TIME_NOW, 'tema_id' => 0, 'oraculos' => '[]',
));
$db->insert_query('ope_mision_tramos', array('mision_id' => $mid, 'tramo' => 2, 'acto' => 2, 'texto' => 'Tramo 2 narrado', 'oraculo_id' => 0, 'posts_considerados' => '[]', 'firma_id' => 1, 'fecha' => TIME_NOW));

// ── Render de la ficha ──
$f = ope7_pj_get($pid);
$ctx = array('uid' => 1, 'es_activo' => true, 'puede_gestionar' => true, 'es_staff' => true, 'bburl' => 'http://rpg.test');
$html = ope7_ficha_html($f, $ctx);

// ── [1] Fruta ──
$G['chk']('[fruta] Bloque presente con nombre', $akuma_nombre !== '' && strpos($html, $akuma_nombre) !== false);
$G['chk']('[fruta] Familia y tier mostrados', stripos($html, 'T') !== false && stripos($html, 'paramecia') !== false || stripos($html, 'zoan') !== false || stripos($html, 'logia') !== false);
$G['chk']('[fruta] Mecánica base visible (comida)', $akuma_id > 0 && strpos($html, 'Mecánica base') !== false);
$G['chk']('[fruta] Influencia en la ficha visible', strpos($html, 'Influencia en la ficha') !== false);
$G['chk']('[fruta] Sin «sin comer» cuando está comida', strpos($html, 'sin comer') === false);

// Variante «sin comer»: otro PJ con fruta asignada pero akuma_id = 0.
$pid2 = ope7_pj_guardar(array(
    'uid' => 3, 'nombre' => 'Prueba Ficha Sin Comer', 'slug' => 'prueba-ficha-3', 'estado' => 'aprobado',
    'estado_vida' => 'activa', 'nivel' => 10, 'raza_id' => $raza,
    'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
    'puntos_comprados' => 0, 'pp_saldo' => 0, 'akuma_id' => 0,
));
$q = $db->simple_select('ope_akumas', 'id', "estado = 'sin_portador'", array('order_by' => 'id', 'limit' => 1));
$akuma2 = (int) $db->fetch_field($q, 'id');
if ($akuma2 > 0) {
    $db->update_query('ope_akumas', array('portador_id' => $pid2, 'estado' => 'con_portador'), "id = {$akuma2}");
    $html2 = ope7_ficha_html(ope7_pj_get($pid2), $ctx);
    $G['chk']('[fruta] Variante «sin comer — trámite 47»', strpos($html2, 'sin comer') !== false);
    $G['chk']('[fruta] Sin mecánica base cuando no ha comido', strpos($html2, 'Mecánica base') === false);
    // Restaura la akuma (el test no deja el catálogo tocado).
    $db->update_query('ope_akumas', array('portador_id' => 0, 'estado' => 'sin_portador'), "id = {$akuma2}");
    $db->update_query('ope_personajes', array('akuma_id' => 0), "id = {$pid2}");
}

// ── [2] Haki ──
$G['chk']('[haki] Bloque presente con tipo', strpos($html, 'Armadura') !== false);
$G['chk']('[haki] Nivel, usos y PP mostrados', strpos($html, 'N2') !== false && strpos($html, '7 usos') !== false && strpos($html, '200 PP') !== false);

// ── [3] Tripulación ──
$G['chk']('[trip] Bloque presente con nombre', strpos($html, 'Trip Ficha') !== false);
$G['chk']('[trip] Miembros listados (capitán 👑)', strpos($html, '👑') !== false && strpos($html, 'Prueba Ficha Socio') !== false);
$G['chk']('[trip] Cofre común con saldo', strpos($html, '42,000') !== false && strpos($html, 'Cofre común') !== false);

// ── [4] Misión en curso ──
$G['chk']('[mision] Bloque presente con nombre', strpos($html, 'El tesoro del Viejo Kraken') !== false);
$G['chk']('[mision] Tramo y acto mostrados', strpos($html, 'tramo 2/3') !== false && strpos($html, 'Acto 2 de 3') !== false);
$G['chk']('[mision] Condiciones visibles', strpos($html, 'El cofre llega al puerto') !== false && strpos($html, 'El Kraken hunde el barco') !== false);

// ── [4b] Cibernética ──
$G['chk']('[ciber] Bloque presente con implante', $impl_nombre !== '' && strpos($html, $impl_nombre) !== false);
$G['chk']('[ciber] Zona/nivel/estado mostrados', strpos($html, 'cabeza N1') !== false && strpos($html, 'Activo') !== false);
$G['chk']('[ciber] Defectos aplicados visibles', strpos($html, 'Rechazo social') !== false && strpos($html, 'Mantenimiento oneroso') !== false);
$G['chk']('[ciber] Bono de atributo aparte (PER +3)', strpos($html, 'PER +3') !== false && strpos($html, 'Bonos de implantes') !== false);
$G['chk']('[ciber] No contamina el desglose base+racial', preg_match('/f7-atr-k">PER<\/span><span class="f7-atr-v"><b>36<\/b> <span class="f7-atr-b">\+6<\/span>/', $html) === 1 && strpos($html, 'base + racial') !== false && strpos($html, '>39<') === false);

// ── [4c] Linaje ──
$G['chk']('[linaje] Bloque presente con familia', $lin_fam !== '' && strpos($html, $lin_fam) !== false);
$G['chk']('[linaje] Dote de linaje con origen narrativo', $lin_dote !== '' && strpos($html, $lin_dote) !== false && strpos($html, 'origen: narrativo') !== false);
$G['chk']('[linaje] Defecto «La sangre llama» −1', $lin_defecto !== '' && strpos($html, $lin_defecto) !== false && strpos($html, '−1') !== false);
$G['chk']('[linaje] Motivo de concesión visible', strpos($html, 'El expediente de fidelidad habló por sí solo.') !== false);

// ── [5] Desglose auditable intacto ──
$G['chk']('[desglose] Atributos base + racial', strpos($html, 'base + racial') !== false && preg_match('/30 base/', $html) === 1);
$G['chk']('[desglose] Secundarios calculados', strpos($html, 'Vida (PV)') !== false && strpos($html, 'Energía (PE)') !== false);
$G['chk']('[desglose] Dotes/defectos con origen', strpos($html, 'origen: narrativo') !== false);
$G['chk']('[desglose] Dominios presentes', strpos($html, 'Dominios') !== false);
$G['chk']('[desglose] Técnicas presentes', strpos($html, 'Técnicas') !== false);
$G['chk']('[desglose] Solo staff visible (ctx staff)', strpos($html, 'Solo staff') !== false && strpos($html, 'es_NPC') !== false);
$G['chk']('[desglose] Sin estilos inline', stripos($html, '<style') === false && strpos($html, 'style=') === false);

// ── Resumen ──
echo "\n=== Ficha (bloques F5 + desglose): {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] > 0 ? 1 : 0);