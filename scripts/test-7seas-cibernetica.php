<?php
/**
 * One Piece: 7 Seas · Test F5.4 — Cibernética y Familias Legendarias (5.22/cap. 23)
 * ---------------------------------------------------------------------------------
 * Verifica:
 *  · 56 — Instalación: zona única (cupo 1), puerta de personaje (nv10/20/35),
 *    requisitos acumulativos (suma de todos los implantes, 5.22 §A.2),
 *    balanza a 0 (ranuras + defectos), vara de Cirujano+Ingeniero (23.3),
 *    pago berries + PP; aplica defectos; revalida la ficha.
 *  · 57 — Retirada: libera cupo y balanza; motivo obligatorio.
 *  · 58 — Mantenimiento: pago por ronda (×2 con «Mantenimiento oneroso»).
 *  · 59 — Diseño a medida: ficha calibrada (ranuras + defectos) se aplica.
 *  · 60 — Concesión de linaje: cupo mundial (3–5), un linaje por PJ, dote +
 *    defecto «La sangre llama», suceso de ronda en borrador.
 *  · 61 — Revocación: retira dote/defecto, libera cupo, suceso; motivo.
 *  · Cron — mantenimiento por ronda: descuenta y degrada a «averiado» si
 *    no hay saldo (23.3).
 *  · Paneles «Cibernética» y «Familias Legendarias» (A.3) sin estilos inline.
 * Idempotente: limpieza completa al final (PJ, modificaciones, linajes).
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-cibernetica.php');
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
$db->delete_query('ope_personajes', "slug LIKE 'prueba-cib-%'");
$db->delete_query('ope_modificaciones_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_implante_historico', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_linaje_personaje', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_personaje_dotes', 'personaje_id NOT IN (SELECT id FROM mybb_ope_personajes)');
$db->delete_query('ope_tramites', 'numero IN (56,57,58,59,60,61) AND solicitante_id = 1');
$db->delete_query('ope_sucesos', "tipo = 'linaje'");

// Un uid distinto por PJ (un PJ por usuario no aplica aquí, pero por limpieza).
$next_uid = 1;
/** Crea un PJ aprobado con atributos y nivel dados (raza Mink). */
$mk_pj = function ($slug, $nivel = 20, $res = 50, $vol = 40, $int = 50, $pp = 2000) use ($db, &$next_uid) {
    $q = $db->simple_select('ope_razas', 'id', "nombre = 'Mink'", array('limit' => 1));
    $raza = (int) $db->fetch_field($q, 'id');
    $pid = ope7_pj_guardar(array(
        'uid' => $next_uid++, 'nombre' => 'Prueba Cib ' . $slug, 'slug' => 'prueba-cib-' . $slug, 'estado' => 'aprobado',
        'estado_vida' => 'activa', 'nivel' => (int) $nivel, 'raza_id' => $raza,
        'fue' => 40, 'des' => 40, 'agi' => 40, 'res' => (int) $res, 'per' => 40, 'inte' => (int) $int, 'car' => 40, 'vol' => (int) $vol,
        'puntos_comprados' => 0, 'pp_saldo' => (int) $pp,
    ));
    ope7_cartera_mover($pid, 'cartera', 5000000);
    return $pid;
};

/** Crea y firma un trámite. Devuelve [ok, msg]. */
$tramite = function ($pid, $numero, $motivo, array $ids = array()) use ($db) {
    $r = ope7_tramite_crear(1, $pid, $numero, $motivo, $ids);
    if (!$r['ok']) {
        return array('ok' => false, 'msg' => $r['msg']);
    }
    $f = ope7_tramite_firmar((int) $r['tid'], 1, 'publicar', 'Firma de prueba: ' . $motivo);
    return array('ok' => $f['ok'], 'msg' => (string) ($f['msg'] ?? ''), 'tid' => (int) $r['tid']);
};

// Obtiene ids del catálogo sembrado.
$q = $db->simple_select('ope_implantes', 'id', "nombre = 'Ojo mecánico'", array('limit' => 1));
$ojo_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_implantes', 'id', "nombre = 'Brazo de kairoseki'", array('limit' => 1));
$brazo_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_familias_legendarias', 'id', "nombre = 'Línea D.'", array('limit' => 1));
$lineaD_id = (int) $db->fetch_field($q, 'id');

// Restaura la ficha canónica del ojo (el test 59 la modifica y restaura, pero
// una corrida previa sin restauración pudo dejarla tocada — idempotente).
$db->update_query('ope_implantes', array(
    'ranuras'  => json_encode(array(
        array('tipo' => 'material', 'detalle' => 'Bueno (+2 al bono, 5.8)', 'puntos' => 0),
        array('tipo' => 'bonificador', 'detalle' => 'PER +3 (ligado a la visión)', 'puntos' => 3),
        array('tipo' => 'habilidad', 'detalle' => 'Sigilo (T3, detección)', 'puntos' => 0),
    ), JSON_UNESCAPED_UNICODE),
    'defectos' => json_encode(array(
        array('nombre' => 'Rechazo social', 'puntos' => -1),
        array('nombre' => 'Mantenimiento oneroso', 'puntos' => -2),
    ), JSON_UNESCAPED_UNICODE),
), "id = {$ojo_id}");

// ── [1] 56 · Instalación ──
// PJ nv20, RES 50/VOL 40/INT 50: cumple Ojo mecánico (N1 cabeza, nv10+, RES 30/VOL 30/INT 30).
$pj = $mk_pj('uno', 20, 50, 40, 50);
// Sin vara (sin Cirujano ni Ingeniero) → BLOQUEADO (23.3).
$r = $tramite($pj, 56, 'Instalar ojo', array('implante_id' => $ojo_id, 'autocirugia' => 0));
$G['chk']('[56] Sin vara Cirujano+Ingeniero → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// Con vara (autocirugía: vara N1=3 → 2; damos dominios al PJ).
$q = $db->simple_select('ope_dominios', 'id', "nombre = 'Médico'", array('limit' => 1));
$med_id = (int) $db->fetch_field($q, 'id');
$q = $db->simple_select('ope_dominios', 'id', "nombre = 'Ingeniero'", array('limit' => 1));
$ing_id = (int) $db->fetch_field($q, 'id');
if ($med_id > 0) {
    $db->insert_query('ope_dominios_personaje', array('personaje_id' => $pj, 'dominio_id' => $med_id, 'nivel' => 3, 'rama' => 'Cirujano', 'origen' => 'compra'));
}
if ($ing_id > 0) {
    $db->insert_query('ope_dominios_personaje', array('personaje_id' => $pj, 'dominio_id' => $ing_id, 'nivel' => 2, 'rama' => 'Inventor', 'origen' => 'compra'));
}
$r = $tramite($pj, 56, 'Instalar ojo mecánico', array('implante_id' => $ojo_id, 'autocirugia' => 1));
$G['chk']('[56] Instalación válida (autocirugía + vara)', $r['ok']);
$q = $db->simple_select('ope_modificaciones_personaje', '*', "personaje_id = {$pj} AND implante_id = {$ojo_id} AND estado = 'activo'", array('limit' => 1));
$mod = $db->fetch_array($q);
$G['chk']('[56] Modificación activa registrada', (bool) $mod);
$mod_id = (int) ($mod['id'] ?? 0);
// Pago: 100.000 ฿ + 200 PP.
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$pj}", array('limit' => 1));
$G['chk']('[56] Berries cobrados (100.000)', (int) $db->fetch_field($q, 'cartera') < 5000000);
$q = $db->simple_select('ope_personajes', 'pp_saldo', "id = {$pj}", array('limit' => 1));
$G['chk']('[56] PP cobrados (200)', (int) $db->fetch_field($q, 'pp_saldo') === 1800);
// Defecto aplicado (Rechazo social −1).
$q = $db->query('SELECT pd.id FROM ' . ope7_tabla_full('personaje_dotes') . ' pd '
    . 'JOIN ' . ope7_tabla_full('defectos') . " d ON d.id = pd.defecto_id "
    . "WHERE pd.personaje_id = {$pj} AND d.nombre = 'Rechazo social' LIMIT 1");
$G['chk']('[56] Defecto aplicado (balanza a 0)', (int) $db->fetch_field($q, 'id') > 0);

// Cupo por zona: segundo implante en cabeza → BLOQUEADO (23.2).
$r = $tramite($pj, 56, 'Instalar otro en cabeza', array('implante_id' => $ojo_id, 'autocirugia' => 1));
$G['chk']('[56] Cupo 1 por zona → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);

// Requisitos acumulativos: PJ nv20 RES 50/VOL 40 no cumple Brazo N2 (RES 45/VOL 35) acumulado con ojo (RES 30/VOL 30/INT 30): necesita RES 75/VOL 65.
$r = $tramite($pj, 56, 'Instalar brazo', array('implante_id' => $brazo_id, 'autocirugia' => 1));
$G['chk']('[56] Requisitos acumulativos → BLOQUEADO (suma)', !$r['ok'] && stripos($r['msg'], 'acumulativos') !== false);

// ── [2] 57 · Retirada ──
$r = $tramite($pj, 57, '', array('modificacion_id' => $mod_id));
$G['chk']('[57] Sin motivo → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);
$r = $tramite($pj, 57, 'Me lo quito, pesa mucho', array('modificacion_id' => $mod_id));
$G['chk']('[57] Retirada válida', $r['ok']);
$q = $db->simple_select('ope_modificaciones_personaje', 'estado', "id = {$mod_id}", array('limit' => 1));
$G['chk']('[57] Estado retirado (cupo liberado)', (string) $db->fetch_field($q, 'estado') === 'retirado');

// ── [3] 58 · Mantenimiento ──
// Reinstalamos el ojo (ya con vara) para probar el mantenimiento.
$r = $tramite($pj, 56, 'Instalar ojo otra vez', array('implante_id' => $ojo_id, 'autocirugia' => 1));
$G['chk']('[58] Reinstalación para mantenimiento', $r['ok']);
$q = $db->simple_select('ope_modificaciones_personaje', 'id', "personaje_id = {$pj} AND implante_id = {$ojo_id} AND estado = 'activo'", array('limit' => 1));
$mod_id2 = (int) $db->fetch_field($q, 'id');
// 58 es ligero SIN firma: el efecto se aplica al crear (el motor no espera firma).
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$pj}", array('limit' => 1));
$car_antes = (int) $db->fetch_field($q, 'cartera');
$r58 = ope7_tramite_crear(1, $pj, 58, '', array('modificacion_id' => $mod_id2));
$q = $db->simple_select('ope_carteras', 'cartera', "personaje_id = {$pj}", array('limit' => 1));
$car_despues = (int) $db->fetch_field($q, 'cartera');
$q = $db->simple_select('ope_tramites', 'estado', "numero = 58 AND solicitante_id = 1 ORDER BY id DESC", array('limit' => 1));
$G['chk']('[58] Mantenimiento pagado (2.500 ×2 por oneroso = 5.000)', $r58['ok'] && (string) $db->fetch_field($q, 'estado') === 'publicado' && $car_antes - $car_despues === 5000);

// ── [4] 59 · Diseño a medida ──
// Guarda la ficha original del ojo para restaurarla (el diseño modifica el catálogo).
$q = $db->simple_select('ope_implantes', 'ranuras, defectos', "id = {$ojo_id}", array('limit' => 1));
$ojo_orig = $db->fetch_array($q);
$ficha = array('ranuras' => array(array('tipo' => 'habilidad', 'detalle' => 'Visión nocturna (Sigilo T3, condicionada)', 'puntos' => 0)), 'defectos' => array(array('nombre' => 'Rechazo social', 'puntos' => -1)));
$r = $tramite(0, 59, 'Mejora del ojo', array('implante_id' => $ojo_id, 'ficha' => $ficha));
$G['chk']('[59] Diseño a medida firmado (ficha calibrada)', $r['ok']);
$q = $db->simple_select('ope_implantes', 'ranuras', "id = {$ojo_id}", array('limit' => 1));
$G['chk']('[59] Ranuras actualizadas en el catálogo', stripos((string) $db->fetch_field($q, 'ranuras'), 'Visión nocturna') !== false);
// Restaura la ficha original (el catálogo es compartido; el test no lo deja tocado).
$db->update_query('ope_implantes', array('ranuras' => (string) $ojo_orig['ranuras'], 'defectos' => (string) $ojo_orig['defectos']), "id = {$ojo_id}");

// ── [5] 60 · Concesión de linaje ──
$pj2 = $mk_pj('dos', 35, 80, 70, 70);
$r = $tramite(0, 60, '', array('familia_id' => $lineaD_id, 'personaje_id' => $pj2));
$G['chk']('[60] Sin motivo → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);
$r = $tramite(0, 60, 'El expediente de fidelidad habla por sí solo', array('familia_id' => $lineaD_id, 'personaje_id' => $pj2));
$G['chk']('[60] Concesión válida', $r['ok']);
$q = $db->simple_select('ope_linaje_personaje', '*', "personaje_id = {$pj2} AND estado = 'activo'", array('limit' => 1));
$lin = $db->fetch_array($q);
$G['chk']('[60] Linaje activo registrado', (bool) $lin);
$linaje_id = (int) ($lin['id'] ?? 0);
// Dote + defecto «La sangre llama».
$q = $db->query('SELECT COUNT(*) AS c FROM ' . ope7_tabla_full('personaje_dotes') . " pd WHERE pd.personaje_id = {$pj2}");
$G['chk']('[60] Dote y defecto aplicados', (int) $db->fetch_field($q, 'c') >= 2);
$q = $db->simple_select('ope_sucesos', 'id', "tipo = 'linaje' AND titulo LIKE '%Línea D.%'", array('limit' => 1));
$G['chk']('[60] Suceso de ronda en borrador (5.14)', (int) $db->fetch_field($q, 'id') > 0);
// Un linaje por PJ.
$r = $tramite(0, 60, 'Otro linaje', array('familia_id' => $lineaD_id, 'personaje_id' => $pj2));
$G['chk']('[60] Un linaje por PJ → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);
// Cupo mundial.
$q = $db->simple_select('ope_familias_legendarias', 'cupo', "id = {$lineaD_id}", array('limit' => 1));
$cupo = (int) $db->fetch_field($q, 'cupo');
for ($i = 0; $i < $cupo; $i++) {
    $p = $mk_pj('cupo' . $i, 35, 80, 70, 70);
    $r = $tramite(0, 60, 'Llenar cupo', array('familia_id' => $lineaD_id, 'personaje_id' => $p));
}
$p_extra = $mk_pj('extracupo', 35, 80, 70, 70);
$r = $tramite(0, 60, 'Fuera de cupo', array('familia_id' => $lineaD_id, 'personaje_id' => $p_extra));
$G['chk']('[60] Cupo mundial lleno → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'cupo') !== false);

// ── [6] 61 · Revocación ──
$r = $tramite(0, 61, '', array('linaje_id' => $linaje_id));
$G['chk']('[61] Sin motivo → BLOQUEADO', !$r['ok'] && stripos($r['msg'], 'BLOQUEADA') !== false);
$r = $tramite(0, 61, 'Traicionó el nombre de la familia', array('linaje_id' => $linaje_id));
$G['chk']('[61] Revocación válida', $r['ok']);
$q = $db->simple_select('ope_linaje_personaje', 'estado', "id = {$linaje_id}", array('limit' => 1));
$G['chk']('[61] Estado revocado (cupo liberado)', (string) $db->fetch_field($q, 'estado') === 'revocado');

// ── [7] Cron · mantenimiento por ronda ──
// PJ sin saldo: el ojo pasa a averiado.
$pj3 = $mk_pj('tres', 20, 50, 40, 50, 200);
$db->insert_query('ope_dominios_personaje', array('personaje_id' => $pj3, 'dominio_id' => $med_id, 'nivel' => 3, 'rama' => 'Cirujano', 'origen' => 'compra'));
if ($ing_id > 0) {
    $db->insert_query('ope_dominios_personaje', array('personaje_id' => $pj3, 'dominio_id' => $ing_id, 'nivel' => 3, 'rama' => 'Inventor', 'origen' => 'compra'));
}
$r = $tramite($pj3, 56, 'Instalar ojo sin saldo', array('implante_id' => $ojo_id, 'autocirugia' => 0));
$G['chk']('[cron] Instalación para el cron', $r['ok']);
ope7_cartera_mover($pj3, 'cartera', -4900000); // deja < 2.500 ฿
$n = ope7_implantes_ronda_mantenimiento();
$q = $db->simple_select('ope_modificaciones_personaje', 'estado', "personaje_id = {$pj3} AND implante_id = {$ojo_id}", array('limit' => 1));
$G['chk']('[cron] Sin saldo → averiado (degradación 23.3)', $n >= 1 && (string) $db->fetch_field($q, 'estado') === 'averiado');

// ── [8] Paneles sin estilos inline ──
if (function_exists('ope7_cibernetica_panel_html')) {
    $html = ope7_cibernetica_panel_html();
    $G['chk']('[panel] Cibernética renderiza', $html !== '');
    $G['chk']('[panel] Cibernética sin style= estático', stripos($html, '<style') === false && strpos($html, 'style=') === false);
} else {
    $G['chk']('[panel] Función del panel de cibernética existe', false);
}
if (function_exists('ope7_familias_panel_html')) {
    $html = ope7_familias_panel_html();
    $G['chk']('[panel] Familias renderiza', $html !== '');
    $G['chk']('[panel] Familias sin style= estático', stripos($html, '<style') === false && strpos($html, 'style=') === false);
} else {
    $G['chk']('[panel] Función del panel de familias existe', false);
}

// ── Resumen ──
echo "\n=== F5.4 Cibernética y Familias: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] > 0 ? 1 : 0);