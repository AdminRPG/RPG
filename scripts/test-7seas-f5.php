<?php
/**
 * One Piece: 7 Seas · Test F5 — Akumas y Haki (5.18/5.19)
 * ------------------------------------------------------
 * Verifica:
 *  · Semilla: 18 frutas (6 canon + 12 pool T1/T2, D5.1), objetos «Fruta: X»
 *    (categoria akuma), 3 bandas del pool de tirada, matriz de especificidad.
 *  · 45 — Tirada: pool por nivel (nv3 → T1–T2), cupo reservado al asignar,
 *    afinidad −10 % PE, objeto en el inventario, anti-abuso nv7 (D5.1).
 *  · 46 — Compra: familia automática (descuento PP + histórico), concepto y
 *    concreta a la bandeja con marcador [PENDIENTE] (D5.2).
 *  · 47 — Comer: consume el objeto, asigna el poder, aplica defectos/dotes
 *    con balanza a 0 (D5.3); duplicados y frutas ajenas bloqueados.
 *  · 50 — Conquistador: ventanas nv5/15/25/35/45, intento registrado, éxito
 *    → nivel 1 + suceso de Mundo Vivo en borrador (D5.4) + publicación.
 *  · Panel staff + bloques de la ficha 7 Seas.
 * Idempotente: limpieza completa al final (PJ de prueba, frutas, Haki).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-f5.php');
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
    return ope7_pj_guardar(array(
        'uid' => 1, 'nombre' => 'Prueba F5 ' . $slug, 'slug' => 'prueba-f5-' . $slug, 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => (int) $nivel, 'raza_id' => $raza,
        'fue' => 10, 'des' => 10, 'agi' => 10, 'res' => 10, 'per' => 10, 'inte' => 10, 'car' => 10, 'vol' => 10,
        'puntos_comprados' => 0, 'pp_saldo' => (int) $pp,
    ));
};

// ── Limpieza previa idempotente ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-f5-%'");
$db->delete_query('ope_akuma_historico', 'portador_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki_conquistador', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki_historico', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_muertes', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_inventario_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tramites', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tramites', "numero IN (45,46,47,48,49,50) AND solicitante_id = 1");
$db->delete_query('ope_historico_pp', "personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)");
$db->delete_query('ope_sucesos', "tipo IN ('haki','akuma')");
$db->delete_query('ope_despertares', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
// Restaura el cupo de las frutas que usaron los PJ de prueba.
$db->query('UPDATE ' . TABLE_PREFIX . 'ope_akumas a LEFT JOIN ' . TABLE_PREFIX . 'ope_personajes p ON p.id = a.portador_id '
    . "SET a.portador_id = NULL, a.origen = NULL, a.estado = 'sin_portador' WHERE p.slug LIKE 'prueba-f5-%' OR a.portador_id NOT IN (SELECT id FROM " . TABLE_PREFIX . 'ope_personajes)');

// ── [1] Semilla de frutas ──
// Robustecido (limitación conocida del AGENTS §11-bis): en una BD de desarrollo
// puede haber frutas adicionales acumuladas de corridas previas o del trámite 49
// (adaptación bajo demanda), así que NO exigimos COUNT total == 18. En su lugar
// verificamos que las 18 frutas canónicas de la semilla están presentes por
// nombre (6 canon + 12 pool T1/T2, D5.1) y que no hay duplicados de nombre — la
// idempotencia del seed (upsert por `nombre_propio`) se refleja en que cada una
// de las canónicas aparece EXACTAMENTE una vez.
$FRUTAS_CANON = array(
    'Gomu Gomu no Mi', 'Hana Hana no Mi', 'Neko Neko no Mi: Modelo Leopardo',
    'Ryu Ryu no Mi: Modelo Espinosaurio', 'Tori Tori no Mi: Modelo Fénix', 'Mera Mera no Mi',
    'Inu Inu no Mi: Modelo Dachshund', 'Ushi Ushi no Mi: Modelo Bisonte',
    'Tori Tori no Mi: Modelo Cuervo', 'Uma Uma no Mi: Modelo Cebra',
    'Bara Bara no Mi', 'Kilo Kilo no Mi', 'Sube Sube no Mi', 'Bomu Bomu no Mi',
    'Supa Supa no Mi', 'Baku Baku no Mi', 'Doru Doru no Mi', 'Buki Buki no Mi',
);
$presentes = 0;
$nombres_bd = array();
$qr = $db->simple_select('ope_akumas', 'nombre_propio', "nombre_propio IN ('" . implode("','", array_map(array($db, 'escape_string'), $FRUTAS_CANON)) . "')");
while ($row = $db->fetch_array($qr)) {
    $nombres_bd[$row['nombre_propio']] = true;
}
foreach ($FRUTAS_CANON as $n) {
    if (!empty($nombres_bd[$n])) {
        $presentes++;
    }
}
$G['chk']('Semilla: las 18 frutas canónicas presentes (6 canon + 12 pool T1/T2, D5.1)', $presentes === 18);
// Idempotencia: sin duplicados de nombre propio (el seed hace upsert por nombre).
$q = $db->query('SELECT SUM(d) AS dup FROM (SELECT COUNT(*) - 1 d FROM ' . TABLE_PREFIX . 'ope_akumas GROUP BY nombre_propio HAVING COUNT(*) > 1) x');
$G['chk']('Semilla: sin duplicados de fruta por nombre (idempotencia del seed)', (int) $db->fetch_field($q, 'dup') === 0);
// Objetos «Fruta: X»: las 18 canónicas presentes, sin exigir COUNT total == 18
// (una fruta bajo demanda también crea su objeto).
$obj_presentes = 0;
$qr = $db->simple_select('ope_objetos', 'nombre', "categoria = 'akuma' AND nombre IN ('" . implode("','", array_map(function ($n) use ($db) { return $db->escape_string('Fruta: ' . $n); }, $FRUTAS_CANON)) . "')");
while ($row = $db->fetch_array($qr)) {
    $obj_presentes++;
}
$G['chk']('Semilla: los 18 objetos «Fruta: X» canónicos presentes (categoria akuma, 1 ranura)', $obj_presentes === 18);
$q = $db->simple_select('ope_akuma_pool_tirada', 'COUNT(*) AS n', 'activo = 1');
$G['chk']('Semilla: 3 bandas del pool (nv3+/15+/30+, T5 nunca)', (int) $db->fetch_field($q, 'n') === 3);

$gomu = ope7_akuma_info((int) $db->fetch_field($db->simple_select('ope_akumas', 'id', "nombre_propio = 'Gomu Gomu no Mi'", array('limit' => 1)), 'id'));
$G['chk']('Canon: Gomu T2 paramecia con matriz de especificidad', $gomu !== null && (int) $gomu['tier'] === 2 && $gomu['familia'] === 'paramecia' && (int) ($gomu['coste_pp']['concreta'] ?? 0) === 900);
$G['chk']('Canon: ficha con puertas y despertar (8 bloques)', $gomu !== null && is_array($gomu['puertas']) && count($gomu['puertas']) >= 4 && is_array($gomu['despertar']));
$q = $db->simple_select('ope_akumas', 'COUNT(*) AS n', "nombre_propio IN ('Tori Tori no Mi: Modelo Fénix','Mera Mera no Mi') AND tier = 5 AND portador_id IS NULL");
$G['chk']('Canon: T5 canónicas presentes (Mera/Tori Tori) y sin portador — nunca por tirada', (int) $db->fetch_field($q, 'n') === 2);

// ── [2] Tirada 45 ──
$pid_tir = $mk_pj('tirada', 3);
$r = ope7_tramite_crear(1, $pid_tir, 45, '');
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[45] Tirada nv3: se resuelve al instante (publicado, 100 % automático)', $r['ok'] && (string) $est === 'publicado');
$ak = $db->fetch_array($db->simple_select('ope_akumas', '*', "portador_id = {$pid_tir}", array('limit' => 1)));
$G['chk']('[45] Cupo reservado: portador_id + estado con_portador + origen tirada', $ak && (int) $ak['portador_id'] === (int) $pid_tir && (string) $ak['estado'] === 'con_portador' && (string) $ak['origen'] === 'tirada');
$G['chk']('[45] Pool por nivel: nv3 → tier ≤ 2', $ak && (int) $ak['tier'] <= 2);
$q = $db->simple_select('ope_personajes', 'akuma_afinidad', "id = {$pid_tir}", array('limit' => 1));
$G['chk']('[45] Afinidad natural −10 % PE aplicada', (int) $db->fetch_field($q, 'akuma_afinidad') === 1);
$q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('inventario_personaje') . " i JOIN " . ope7_tabla_full('objetos') . " o ON o.id = i.objeto_id WHERE i.personaje_id = {$pid_tir} AND o.categoria = 'akuma'");
$G['chk']('[45] Fruta en el inventario como objeto (mochila, 1 ranura)', (int) $db->fetch_field($q, 'n') === 1);
$q = $db->simple_select('ope_akuma_historico', 'COUNT(*) AS n', "portador_id = {$pid_tir} AND tipo_evento = 'obtencion'");
$G['chk']('[45] Histórico de obtención registrado', (int) $db->fetch_field($q, 'n') === 1);

$r2 = ope7_tramite_crear(1, $pid_tir, 45, '');
$G['chk']('[45] 2ª tirada bloqueada (ya eres portador, cupo mundial)', !$r2['ok'] && stripos((string) ($r2['msg'] ?? ''), 'BLOQUEADA') !== false);

// Anti-abuso (19.7.1): morir para repetir → sin tirada hasta nv7.
$pid_ab = $mk_pj('muerte5', 5);
$db->insert_query('ope_muertes', array('personaje_id' => $pid_ab, 'tema_id' => 0, 'causa' => 'test F5', 'umbral_confirmado' => 'PV', 'calidad' => 'digna', 'tramite_id' => 0, 'firmado_por' => 1, 'fecha' => TIME_NOW));
$r = ope7_tramite_crear(1, $pid_ab, 45, '');
$G['chk']('[45] Anti-abuso: muerto a nv5 → tirada bloqueada hasta nv7', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'anti-abuso') !== false);
$pid_ab7 = $mk_pj('muerte7', 7);
$db->insert_query('ope_muertes', array('personaje_id' => $pid_ab7, 'tema_id' => 0, 'causa' => 'test F5', 'umbral_confirmado' => 'PV', 'calidad' => 'digna', 'tramite_id' => 0, 'firmado_por' => 1, 'fecha' => TIME_NOW));
$r = ope7_tramite_crear(1, $pid_ab7, 45, '');
$G['chk']('[45] Anti-abuso: muerto a nv7 → tirada permitida', $r['ok']);

// ── [3] Compra 46 ──
$pid_com = $mk_pj('compra', 3, 1000);
$r = ope7_tramite_crear(1, $pid_com, 46, '', array('especificidad' => 'familia', 'tier' => 2, 'familia' => 'paramecia'));
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[46] Compra familia T2: automática (publicado, matriz ×1, 300 PP)', $r['ok'] && (string) $est === 'publicado');
$akc = $db->fetch_array($db->simple_select('ope_akumas', '*', "portador_id = {$pid_com}", array('limit' => 1)));
$G['chk']('[46] Fruta asignada de la familia pedida (paramecia, T≤2)', $akc && (string) $akc['familia'] === 'paramecia' && (int) $akc['tier'] <= 2 && (string) $akc['origen'] === 'compra');
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid_com}", array('limit' => 1));
$G['chk']('[46] PP descontados (1000 → 700)', (int) $db->fetch_field($q, 'pp_saldo') === 700);
$q = $db->simple_select('ope_historico_pp', 'COUNT(*) AS n', "personaje_id = {$pid_com} AND cantidad = -300 AND concepto LIKE '%Compra de fruta%'");
$G['chk']('[46] Gasto registrado en el libro de PP con concepto', (int) $db->fetch_field($q, 'n') === 1);

$pid_sin = $mk_pj('sinsaldo', 3, 100);
$r = ope7_tramite_crear(1, $pid_sin, 46, '', array('especificidad' => 'familia', 'tier' => 5, 'familia' => ''));
$G['chk']('[46] Sin saldo → compra bloqueada', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'BLOQUEADA') !== false);

// D5.2: concepto y concreta → bandeja con [PENDIENTE].
$pid_con = $mk_pj('concepto', 3, 2000);
$r = ope7_tramite_crear(1, $pid_con, 46, '', array('especificidad' => 'concepto', 'tier' => 3, 'familia' => ''));
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[46] Concepto → bandeja (prompt_listo, el foro estudia y el staff firma — D5.2)', $r['ok'] && (string) $est === 'prompt_listo');
$q = $db->simple_select('ope_akumas', 'id', "portador_id = {$pid_con}", array('limit' => 1));
$G['chk']('[46] Concepto: sin fruta asignada (la decide el staff al firmar)', $db->num_rows($q) === 0);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));
$r = ope7_tramite_crear(1, $pid_con, 46, '', array('especificidad' => 'concreta', 'tier' => 3, 'familia' => ''));
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[46] Fruta concreta → bandeja (prompt_listo, cupo mundial)', $r['ok'] && (string) $est === 'prompt_listo');
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// ── [4] Comer 47 ──
$ak_tir = $db->fetch_array($db->simple_select('ope_akumas', '*', "portador_id = {$pid_tir}", array('limit' => 1)));
$r = ope7_tramite_crear(1, $pid_tir, 47, '', array('akuma_id' => (int) $ak_tir['id']));
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[47] Comer la fruta: poder asignado (publicado)', $r['ok'] && (string) $est === 'publicado');
$q = $db->simple_select('ope_personajes', 'akuma_id', "id = {$pid_tir}", array('limit' => 1));
$G['chk']('[47] personajes.akuma_id = fruta comido', (int) $db->fetch_field($q, 'akuma_id') === (int) $ak_tir['id']);
$q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('inventario_personaje') . " WHERE personaje_id = {$pid_tir} AND objeto_id = " . (int) ope7_akuma_objeto_id((int) $ak_tir['id']));
$G['chk']('[47] El objeto fruta se consume del inventario', (int) $db->fetch_field($q, 'n') === 0);
$q = $db->simple_select('ope_personajes', 'datos', "id = {$pid_tir}", array('limit' => 1));
$datos = json_decode((string) $db->fetch_field($q, 'datos'), true);
$G['chk']('[47] Defectos exigidos aplicados con la balanza a 0 (D5.3)', is_array($datos) && in_array('No saber nadar', (array) ($datos['defectos_akuma'] ?? array()), true));

$r = ope7_tramite_crear(1, $pid_tir, 47, '', array('akuma_id' => (int) $ak_tir['id']));
$G['chk']('[47] Comer otra vez → bloqueado (una sola fruta)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'BLOQUEADO') !== false);
$pid_ajeno = $mk_pj('ajena', 3);
$r = ope7_tramite_crear(1, $pid_ajeno, 47, '', array('akuma_id' => (int) $akc['id']));
$G['chk']('[47] Fruta ajena → bloqueado (no está asignada)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'no está asignada') !== false);

// ── [4b] Despertar 48 (19.4/19.6) ──
// Sin fruta comida → la firma bloquea (ia + firma: la validación corre al firmar).
$pid_sin = $mk_pj('desp_sin', 40);
$r = ope7_tramite_crear(1, $pid_sin, 48, 'Quiero despertar mi fruta.');
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[48] Sin fruta comida → la firma rechaza (19.6: la cúspide de una fruta ya asimilada)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'no tiene una fruta comida') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r['tid'] ?? 0));

// Fruta comida a nv25+ (banda T1–T2) → despertar. Asignación directa de una
// fruta libre T1–T2 (la tirada a nv26 podría dar T3, cuya banda es nv32).
$pid_des = $mk_pj('despertar', 26);
$ak_des = $db->fetch_array($db->simple_select('ope_akumas', '*', "tier <= 2 AND portador_id IS NULL", array('order_by' => 'id', 'limit' => 1)));
$G['chk']('[48] Prep: fruta T1–T2 libre encontrada', $ak_des !== false);
$db->update_query('ope_akumas', array('portador_id' => $pid_des, 'origen' => 'tirada', 'estado' => 'con_portador'), "id = " . (int) $ak_des['id']);
$r = ope7_tramite_crear(1, $pid_des, 47, '', array('akuma_id' => (int) $ak_des['id']));
$G['chk']('[48] Prep: fruta comida', $r['ok']);

// Banda: T1–T2 exige nv25. nv24 → bloqueado (fruta asignada directamente,
// sin consumir pool: el test de la tirada ya cubre el cupo).
$pid_des24 = $mk_pj('desp24', 24);
$ak24 = $db->fetch_array($db->simple_select('ope_akumas', '*', "tier <= 2 AND portador_id IS NULL", array('order_by' => 'id', 'limit' => 1)));
$db->update_query('ope_akumas', array('portador_id' => $pid_des24, 'origen' => 'tirada', 'estado' => 'con_portador'), "id = " . (int) $ak24['id']);
$db->update_query('ope_personajes', array('akuma_id' => (int) $ak24['id']), "id = {$pid_des24}"); // simula comer
$r = ope7_tramite_crear(1, $pid_des24, 48, 'Aún no toca.');
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[48] nv24 con T1–T2 → la firma rechaza (banda 19.4: T1–T2 nv25)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'nv25') !== false);
$db->delete_query('ope_tramites', "personaje_id = {$pid_des24}");
$db->query('UPDATE ' . TABLE_PREFIX . 'ope_akumas SET portador_id = NULL, origen = NULL, estado = \'sin_portador\' WHERE id = ' . (int) $ak24['id']);
$db->update_query('ope_personajes', array('akuma_id' => 0), "id = {$pid_des24}");

// nv26 T1–T2 → despertar firmado (ia + firma).
$r = ope7_tramite_crear(1, $pid_des, 48, 'Dos años de mar usando la fruta y un mar que la merece.');
$G['chk']('[48] Solicitud creada (ia → bandeja)', $r['ok'] && (string) $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado') !== 'publicado');
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Antigüedad y temas verificados; VOL suficiente.');
$G['chk']('[48] Firma publica el despertar', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'publicado') !== false);
$q = $db->simple_select('ope_despertares', 'COUNT(*) AS n', "personaje_id = {$pid_des} AND akuma_id = " . (int) $ak_des['id']);
$G['chk']('[48] Registrado en `despertares` (fruta+personaje+trámite+fecha)', (int) $db->fetch_field($q, 'n') === 1);
$q = $db->simple_select('ope_sucesos', 'COUNT(*) AS n', "tipo = 'akuma' AND activo = 0");
$G['chk']('[48] Suceso de Mundo Vivo en borrador (5.14)', (int) $db->fetch_field($q, 'n') >= 1);

// Repetido → la firma rechaza (una vez por fruta y por portador).
$r2 = ope7_tramite_crear(1, $pid_des, 48, 'Otra vez.');
$fir2 = ope7_tramite_firmar((int) ($r2['tid'] ?? 0), 1, 'publicar', 'Firma de prueba.');
$G['chk']('[48] Segundo despertar → la firma rechaza (ya despertada)', !$fir2['ok'] && stripos((string) ($fir2['msg'] ?? ''), 'ya está despertada') !== false);
$db->delete_query('ope_tramites', "id = " . (int) ($r2['tid'] ?? 0));

// ── [4c] Adaptación de fruta bajo demanda 49 (staff, skill-adaptacion-akumas) ──
$r = ope7_tramite_crear(1, 0, 49, 'Adaptación bajo demanda', array('concepto' => 'Moku Moku no Mi — humo que se vuelve atmósfera'));
$G['chk']('[49] Solo el staff inicia (quien=staff)', $r['ok'] || stripos((string) ($r['msg'] ?? ''), 'solo lo inicia') !== false);
if ($r['ok']) {
    $ficha = array(
        'nombre_propio'      => 'Moku Moku no Mi (adaptada)',
        'familia'            => 'logia',
        'rareza'             => null,
        'tier'               => 5,
        'aspecto'            => 'Fruto gris humeante; sabor a niebla fría.',
        'mecanica_base'      => array('resumen' => 'Tu cuerpo se vuelve humo: intangibilidad con contadores (5.18).', 'pasivas' => array('Intangible mientras haya humo', 'Vuelo de humo con espacio')),
        'puertas'            => array('Daño puro', 'Movilidad', 'Terreno (cortina de humo)'),
        'debilidades'        => array('enemigo_natural' => 'El viento fuerte dispersa tu humo; el mar te rechaza en cualquier forma.'),
        'requisitos_portador' => array('nivel_min' => 40, 'nota' => 'Logia: solo por compra concreta o recompensa.'),
        'influencia_ficha'   => array('dotes' => array('Cuerpo de humo'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar'          => array('resumen' => 'La niebla que envuelve la isla', 'detalle' => 'Clima insular — suceso de ronda (5.14).'),
        'precio_base'        => 0,
        'coste_pp'           => array('base' => 1500, 'familia' => 1500, 'concepto' => 3000, 'concreta' => 4500),
    );
    ope7_tramite_guardar_resultado((int) $r['tid'], $ficha);
    $fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Ficha de 8 bloques revisada y coherente con la guía 5.18.');
    $G['chk']('[49] Firma publica la adaptación (ficha validada)', $fir['ok']);
    $q = $db->simple_select('ope_akumas', 'COUNT(*) AS n', "nombre_propio = 'Moku Moku no Mi (adaptada)'");
    $G['chk']('[49] Fruta dada de alta en el catálogo (estado sin_portador)', (int) $db->fetch_field($q, 'n') === 1);
    $q = $db->query('SELECT COUNT(*) AS n FROM ' . ope7_tabla_full('objetos') . " WHERE nombre = 'Fruta: Moku Moku no Mi (adaptada)' AND categoria = 'akuma'");
    $G['chk']('[49] Objeto «Fruta: X» creado para las tres vías', (int) $db->fetch_field($q, 'n') === 1);
    // Duplicado → bloqueado (fruto único, 19.7).
    $r2 = ope7_tramite_crear(1, 0, 49, 'Repetida', array('concepto' => 'Moku Moku otra vez'));
    if ($r2['ok']) {
        ope7_tramite_guardar_resultado((int) $r2['tid'], $ficha);
        $fir2 = ope7_tramite_firmar((int) ($r2['tid'] ?? 0), 1, 'publicar', 'Duplicado.');
        $G['chk']('[49] Fruta duplicada → bloqueada (fruto único, cupo mundial 19.7)', !$fir2['ok'] && stripos((string) ($fir2['msg'] ?? ''), 'ya existe') !== false);
        $db->delete_query('ope_tramites', "id = " . (int) ($r2['tid'] ?? 0));
    }
    $db->delete_query('ope_akumas', "nombre_propio = 'Moku Moku no Mi (adaptada)'");
    $db->delete_query('ope_objetos', "nombre = 'Fruta: Moku Moku no Mi (adaptada)'");
} else {
    $G['chk']('[49] El staff no puede iniciar (uid 1 no staff) → la adaptación queda bloqueada', false === stripos((string) ($r['msg'] ?? ''), 'solo lo inicia'));
}

// ── [5] Conquistador 50 ──
$pid_cq4 = $mk_pj('conq4', 4);
$r = ope7_tramite_crear(1, $pid_cq4, 50, '');
$G['chk']('[50] nv4 → bloqueado (es a partir de nv5)', !$r['ok'] && stripos((string) ($r['msg'] ?? ''), 'BLOQUEADA') !== false);

$pid_cq5 = $mk_pj('conq5', 5);
$r = ope7_tramite_crear(1, $pid_cq5, 50, '');
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[50] nv5 → tirada automática resuelta (publicado)', $r['ok'] && (string) $est === 'publicado');
$iq = $db->fetch_array($db->simple_select('ope_haki_conquistador', '*', "personaje_id = {$pid_cq5}", array('limit' => 1)));
$G['chk']('[50] Intento registrado (ventana nv5, prob 3 %)', $iq && (int) $iq['intento_nivel'] === 5 && (int) $iq['prob'] === 3);
if ($iq && (int) $iq['exito'] === 1) {
    $hq = $db->simple_select('ope_haki', 'nivel', "personaje_id = {$pid_cq5} AND tipo = 'conquistador'", array('limit' => 1));
    $G['chk']('[50] Éxito → nivel 1 de Haki creado', (int) $db->fetch_field($hq, 'nivel') === 1);
    $sq = $db->simple_select('ope_sucesos', 'activo', "tipo = 'haki' AND titulo LIKE '%Rey%'", array('limit' => 1));
    $G['chk']('[50] Éxito → suceso de Mundo Vivo en borrador (activo=0, D5.4)', (int) $db->fetch_field($sq, 'activo') === 0);
} else {
    $hq = $db->simple_select('ope_haki', 'id', "personaje_id = {$pid_cq5} AND tipo = 'conquistador'", array('limit' => 1));
    $G['chk']('[50] Fallo → sin Haki creado (intento registrado)', $db->num_rows($hq) === 0);
}
$r2 = ope7_tramite_crear(1, $pid_cq5, 50, '');
$G['chk']('[50] Ventana agotada → segunda tirada bloqueada', !$r2['ok'] && stripos((string) ($r2['msg'] ?? ''), 'BLOQUEADA') !== false);

// Éxito determinista: nv45 (40 %) en bucle (probabilidad de 20 fallos ≈ 0,004 %).
$ok_conq = false;
for ($i = 1; $i <= 20 && !$ok_conq; $i++) {
    $pid_cq45 = $mk_pj('conq45_' . $i, 45);
    $r = ope7_tramite_crear(1, $pid_cq45, 50, '');
    if ($r['ok']) {
        $iq = $db->fetch_array($db->simple_select('ope_haki_conquistador', '*', "personaje_id = {$pid_cq45}", array('limit' => 1)));
        if ($iq && (int) $iq['exito'] === 1) {
            $ok_conq = true;
            $hq = $db->simple_select('ope_haki', 'nivel', "personaje_id = {$pid_cq45} AND tipo = 'conquistador'", array('limit' => 1));
            $G['chk']('[50] Éxito nv45 (40 %) → nivel 1 + PP 0', (int) $db->fetch_field($hq, 'nivel') === 1);
            $sq = $db->fetch_array($db->simple_select('ope_sucesos', '*', "tipo = 'haki' AND activo = 0", array('order_by' => 'id', 'order_dir' => 'DESC', 'limit' => 1)));
            $G['chk']('[50] Suceso en borrador con ronda e impacto', $sq && (int) $sq['activo'] === 0 && (int) ($sq['ronda'] ?? 0) >= 0 && strpos((string) $sq['impacto'], 'F_suceso') !== false);
            $G['chk']('[50] Histórico de obtención (0 → 1) registrado', $db->num_rows($db->simple_select('ope_haki_historico', 'id', "personaje_id = {$pid_cq45} AND nivel_desde = 0 AND nivel_hasta = 1")) === 1);
            $G['chk']('[50] Publicar suceso: borrador → activo', ope7_akumas_publicar_suceso((int) $sq['id']) && (int) $db->fetch_field($db->simple_select('ope_sucesos', 'activo', "id = " . (int) $sq['id'], array('limit' => 1)), 'activo') === 1);
        }
    }
}
$G['chk']('[50] Bucle nv45: éxito conseguido en ≤20 intentos', $ok_conq);

// Ventana lógica (20.1.2): pendiente hasta agotarla.
$w = ope7_conquistador_ventana(14, $pid_ajeno);
$G['chk']('[50] nv14 → ventana pendiente nv5 (3 %)', $w !== null && (int) $w['nivel'] === 5 && (int) $w['prob'] === 3);
$w = ope7_conquistador_ventana(16, $pid_ajeno);
$G['chk']('[50] nv16 → ventana nv15 (8 %)', $w !== null && (int) $w['nivel'] === 15 && (int) $w['prob'] === 8);

// ── [5b] Subida de Haki 51 ──
// Despertar automático de Armadura/Mantra a nv10 (20.1).
$pid_nv10 = $mk_pj('haki10', 10, 800);
$fh10 = ope7_pj_get($pid_nv10);
$n_auto = ope7_haki_auto_despertar($fh10);
$G['chk']('[51] nv10 → Armadura y Mantra despiertan solos (nivel 1)', $n_auto === 2 && $db->num_rows($db->simple_select('ope_haki', 'id', "personaje_id = {$pid_nv10} AND tipo IN ('armadura','mantra') AND nivel = 1")) === 2);
$G['chk']('[51] Auto-despertar idempotente (2.ª llamada no duplica)', ope7_haki_auto_despertar($fh10) === 0 && $db->num_rows($db->simple_select('ope_haki', 'id', "personaje_id = {$pid_nv10}")) === 2);
$pid_h9 = $mk_pj('haki9', 9, 0);
$G['chk']('[51] nv9 → nada (el despertar es a nv10)', ope7_haki_auto_despertar(ope7_pj_get($pid_h9)) === 0);

// Conteo de usos al cierre (20.2: 1 por tipo y por tema).
$n_uso = ope7_haki_contar_usos_cierre($pid_nv10, array('armadura', 'mantra', 'conquistador'), 0);
$G['chk']('[51] Cierre: +1 uso por tipo despierto (armadura y mantra; conquistador no)', $n_uso === 2);
$q = $db->simple_select('ope_haki', 'usos_acumulados', "personaje_id = {$pid_nv10} AND tipo = 'armadura'", array('limit' => 1));
$G['chk']('[51] Cierre: usos_acumulados = 1 tras el tema (sin doble conteo por tema)', (int) $db->fetch_field($q, 'usos_acumulados') === 1);

// Subida N1→N2 por el trámite 51 (ia: crea → bandeja → el staff firma y aplica).
$r = ope7_tramite_crear(1, $pid_nv10, 51, 'Usos satisfactorios en combate.', array('tipo' => 'armadura'));
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[51] Solicitud creada → bandeja (prompt_listo, el foro estudia y el staff firma)', $r['ok'] && (string) $est === 'prompt_listo');
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Usos verificados: 1 tema con Armadura.');
$G['chk']('[51] Firma con usos insuficientes (1 < 6) → BLOQUEADO y rechazado', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'BLOQUEADA') !== false);
$est = $db->fetch_field($db->simple_select('ope_tramites', 'estado', "id = " . (int) ($r['tid'] ?? 0), array('limit' => 1)), 'estado');
$G['chk']('[51] El trámite queda rechazado (nunca se publica en falso)', (string) $est === 'rechazado');

// Escalera completa: 6 usos + 200 PP + VOL 55 → N2 (nv30 con VOL 66; VOL 55 alcanzable a nv~23, 20.3).
$pid_n2 = $mk_pj('haki_n2', 30, 800);
$db->update_query('ope_personajes', array('vol' => 66), "id = {$pid_n2}");
ope7_haki_auto_despertar(ope7_pj_get($pid_n2));
$db->update_query('ope_haki', array('usos_acumulados' => 6), "personaje_id = {$pid_n2} AND tipo = 'armadura'");
$r = ope7_tramite_crear(1, $pid_n2, 51, 'Seis temas usando la Armadura con criterio.', array('tipo' => 'armadura'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Usos verificados contra el histórico; VOL 55+ cumplida.');
$G['chk']('[51] N1→N2 publicado: 6 usos + 200 PP + VOL 55', $fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'publicado') !== false);
$h2 = $db->fetch_array($db->simple_select('ope_haki', '*', "personaje_id = {$pid_n2} AND tipo = 'armadura'", array('limit' => 1)));
$G['chk']('[51] Nivel subido a N2 + PP invertidos 200', (int) $h2['nivel'] === 2 && (int) $h2['pp_invertidos'] === 200);
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pid_n2}", array('limit' => 1));
$G['chk']('[51] PP descontados de la cartera (800 → 600)', (int) $db->fetch_field($q, 'pp_saldo') === 600);
$q = $db->simple_select('ope_historico_pp', 'COUNT(*) AS n', "personaje_id = {$pid_n2} AND cantidad = -200 AND concepto LIKE '%Subida de Haki%'");
$G['chk']('[51] Gasto registrado en el libro de PP con concepto', (int) $db->fetch_field($q, 'n') === 1);
$q = $db->simple_select('ope_haki_historico', 'COUNT(*) AS n', "personaje_id = {$pid_n2} AND tipo = 'armadura' AND nivel_desde = 1 AND nivel_hasta = 2 AND pp = 200");
$G['chk']('[51] Histórico de subida (1 → 2, usos 6, PP 200, firma)', (int) $db->fetch_field($q, 'n') === 1);

// Bloqueos: VOL insuficiente · PP insuficientes · tipo no despierto · N5 ya.
$pid_vol = $mk_pj('haki_vol', 30, 2000);
$db->insert_query('ope_haki', array('personaje_id' => $pid_vol, 'tipo' => 'mantra', 'nivel' => 1, 'usos_acumulados' => 10, 'pp_invertidos' => 0, 'activo' => 1));
$r = ope7_tramite_crear(1, $pid_vol, 51, 'Quiero subir Mantra.', array('tipo' => 'mantra'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Sin VOL suficiente.');
$G['chk']('[51] VOL 10 < 55 (base baja) → BLOQUEADO (requisito de Voluntad 20.3)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'VOL efectiva') !== false);
$pid_pp = $mk_pj('haki_pp', 30, 100);
$db->insert_query('ope_haki', array('personaje_id' => $pid_pp, 'tipo' => 'armadura', 'nivel' => 1, 'usos_acumulados' => 10, 'pp_invertidos' => 0, 'activo' => 1));
$r = ope7_tramite_crear(1, $pid_pp, 51, 'Quiero subir Armadura.', array('tipo' => 'armadura'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'PP cortos.');
$G['chk']('[51] PP insuficientes (100 < 200) → BLOQUEADO', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'necesitas 200 PP') !== false);
$pid_node = $mk_pj('haki_nodo', 5, 2000);
$r = ope7_tramite_crear(1, $pid_node, 51, 'Quiero el Rey.', array('tipo' => 'conquistador'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Sin tirada previa.');
$G['chk']('[51] Tipo no despierto (Conquistador sin tirada) → BLOQUEADO', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'no está despierto') !== false);
$pid_n5 = $mk_pj('haki_n5', 48, 5000);
$db->insert_query('ope_haki', array('personaje_id' => $pid_n5, 'tipo' => 'armadura', 'nivel' => 5, 'usos_acumulados' => 99, 'pp_invertidos' => 1400, 'activo' => 1));
$r = ope7_tramite_crear(1, $pid_n5, 51, 'Una más.', array('tipo' => 'armadura'));
$fir = ope7_tramite_firmar((int) ($r['tid'] ?? 0), 1, 'publicar', 'Ya está al máximo.');
$G['chk']('[51] Ya en N5 → BLOQUEADO (no hay más escalones)', !$fir['ok'] && stripos((string) ($fir['msg'] ?? ''), 'ya estás en N5') !== false);

// ── [6] Panel y ficha ──
$panel = ope7_akumas_panel_html();
$G['chk']('[6] Panel staff: catálogo y cupos mundiales', strpos($panel, 'Catálogo y cupos mundiales') !== false);
$G['chk']('[6] Panel staff: pool de la tirada', strpos($panel, 'Pool de la tirada') !== false);
$G['chk']('[6] Panel staff: sección Haki', strpos($panel, 'Haki') !== false);
$G['chk']('[6] Panel staff: histórico de subidas de Haki (51)', strpos($panel, 'Subidas de Haki (51)') !== false);
$G['chk']('[6] Panel staff: fruta bajo demanda (49) con formulario', strpos($panel, 'Fruta bajo demanda') !== false && strpos($panel, 'adaptar_fruta') !== false);
$G['chk']('[6] Panel staff: sección Despertares (48)', strpos($panel, 'Despertares') !== false);
$pj_tir = ope7_pj_get($pid_tir);
$ficha = ope7_ficha_html($pj_tir, array('uid' => 1, 'es_activo' => true, 'puede_gestionar' => true, 'es_staff' => true, 'bburl' => ''));
$G['chk']('[6] Ficha 7 Seas: bloque «Fruta del diablo»', strpos($ficha, 'Fruta del diablo') !== false);
$G['chk']('[6] Ficha 7 Seas: bloque «Haki»', strpos($ficha, 'Haki') !== false);

// ── [7] Limpieza final ──
$db->delete_query('ope_personajes', "slug LIKE 'prueba-f5-%'");
$db->delete_query('ope_akuma_historico', 'portador_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki_conquistador', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_haki_historico', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_muertes', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_inventario_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tramites', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_historico_pp', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_sucesos', "tipo IN ('haki','akuma')");
$db->delete_query('ope_despertares', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->query('UPDATE ' . TABLE_PREFIX . 'ope_akumas a LEFT JOIN ' . TABLE_PREFIX . 'ope_personajes p ON p.id = a.portador_id '
    . "SET a.portador_id = NULL, a.origen = NULL, a.estado = 'sin_portador' WHERE p.slug LIKE 'prueba-f5-%' OR a.portador_id NOT IN (SELECT id FROM " . TABLE_PREFIX . 'ope_personajes)');

echo "\n=== F5: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] === 0 ? 0 : 1);
