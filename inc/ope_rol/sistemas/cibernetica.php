<?php
/**
 * One Piece: 7 Seas · Cibernética y Familias Legendarias (F5.4, 5.22/cap. 23)
 * ---------------------------------------------------------------------------
 * Trámites 56–61 (sistema opcional confirmado en Sesión 11):
 *  · 56 — Instalación de implante (ia): valida zona única + puerta de
 *    personaje + requisitos acumulativos (suma de todos los implantes) +
 *    balanza a 0 + cupo por zona + pago berries/PP + vara de Cirujano +
 *    Ingeniero; aplica defectos; revalida la ficha (23.2–23.3, 5.22 §A).
 *  · 57 — Retirada de implante (ligero/firma): libera el cupo de la zona y
 *    la balanza; las mejoras se pierden (23.2).
 *  · 58 — Mantenimiento/reparación (ligero, sin firma): pago por ronda
 *    (hook de 5.14) o reparación con Ingeniero (23.3).
 *  · 59 — Diseño de mejora a medida (staff): la ranura de habilidad
 *    especial calibrada — efecto del catálogo o no registrado con
 *    condiciones (guía 5.22 §4/§6).
 *  · 60 — Concesión de linaje (staff): cruza el expediente de fidelidad con
 *    el cupo (3–5); aplica dote/defecto «La sangre llama»; suceso de ronda.
 *  · 61 — Revocación de linaje (staff): retira dote/defecto, libera cupo,
 *    suceso de ronda; motivo obligatorio.
 * Sin dados: la instalación se resuelve por comparación de oficio (vara);
 * la IA propone, el staff firma, nada se decide solo (principio 1).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Tabla de calibración 5.22 §5 (números sagrados confirmados, Sesión 11). */
function ope7_implante_tabla($zona, $nivel)
{
    $zona = in_array((string) $zona, array('extremidades', 'torso', 'cabeza'), true) ? (string) $zona : 'extremidades';
    $nivel = in_array((string) $nivel, array('N1', 'N2', 'N3'), true) ? (string) $nivel : 'N1';
    $tabla = array(
        'extremidades' => array(
            'N1' => array('puerta' => 10, 'req' => array('res' => 30, 'vol' => 25), 'beries' => 100000, 'pp' => 200, 'mant' => 2500, 'vara' => 3, 'ranuras' => 2),
            'N2' => array('puerta' => 20, 'req' => array('res' => 45, 'vol' => 35), 'beries' => 500000, 'pp' => 400, 'mant' => 10000, 'vara' => 4, 'ranuras' => 3),
            'N3' => array('puerta' => 35, 'req' => array('res' => 65, 'vol' => 50), 'beries' => 2500000, 'pp' => 600, 'mant' => 40000, 'vara' => 5, 'ranuras' => 4),
        ),
        'torso' => array(
            'N1' => array('puerta' => 10, 'req' => array('res' => 35, 'vol' => 30), 'beries' => 100000, 'pp' => 200, 'mant' => 2500, 'vara' => 3, 'ranuras' => 2),
            'N2' => array('puerta' => 20, 'req' => array('res' => 55, 'vol' => 40), 'beries' => 500000, 'pp' => 400, 'mant' => 10000, 'vara' => 4, 'ranuras' => 3),
            'N3' => array('puerta' => 35, 'req' => array('res' => 75, 'vol' => 60), 'beries' => 2500000, 'pp' => 600, 'mant' => 40000, 'vara' => 5, 'ranuras' => 4),
        ),
        'cabeza' => array(
            'N1' => array('puerta' => 10, 'req' => array('res' => 30, 'vol' => 30, 'int' => 30), 'beries' => 100000, 'pp' => 200, 'mant' => 2500, 'vara' => 3, 'ranuras' => 2),
            'N2' => array('puerta' => 20, 'req' => array('res' => 50, 'vol' => 40, 'int' => 45), 'beries' => 500000, 'pp' => 400, 'mant' => 10000, 'vara' => 4, 'ranuras' => 3),
            'N3' => array('puerta' => 35, 'req' => array('res' => 70, 'vol' => 55, 'int' => 65), 'beries' => 2500000, 'pp' => 600, 'mant' => 40000, 'vara' => 5, 'ranuras' => 4),
        ),
    );
    return $tabla[$zona][$nivel];
}

/** Catálogo de defectos de implantes (5.22 §A.6, balanza a 0). */
function ope7_implante_defectos_catalogo()
{
    return array(
        'Cuerpo pesado'               => -1,
        'Mantenimiento oneroso'       => -2,
        'Vulnerabilidad al magnetismo'=> -2,
        'Ancla al agua'               => -1,
        'Rechazo social'              => -1,
        'El cuerpo manda'             => -3,
    );
}

/** Implantes activos del personaje (fila + tabla de calibración). */
function ope7_implantes_pj($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array();
    if ($pid < 1 || !ope7_tabla_existe('modificaciones_personaje') || !ope7_tabla_existe('implantes')) {
        return $out;
    }
    $q = $db->query('SELECT m.*, i.nombre, i.zona, i.nivel AS impl_nivel, i.requisitos, i.ranuras, i.precios, i.defectos '
        . 'FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
        . 'JOIN ' . ope7_tabla_full('implantes') . " i ON i.id = m.implante_id "
        . "WHERE m.personaje_id = {$pid} AND m.estado = 'activo' ORDER BY m.id");
    while ($r = $db->fetch_array($q)) {
        $r['tabla'] = ope7_implante_tabla($r['zona'], (string) $r['nivel']);
        $out[] = $r;
    }
    return $out;
}

/** Requisitos acumulativos (suma de todos los implantes activos, 5.22 §A.2). */
function ope7_implante_requisitos_acumulados($pid)
{
    $req = array('res' => 0, 'vol' => 0, 'int' => 0);
    foreach (ope7_implantes_pj($pid) as $m) {
        $t = $m['tabla'];
        foreach (array('res', 'vol', 'int') as $k) {
            $req[$k] += (int) ($t['req'][$k] ?? 0);
        }
    }
    return $req;
}

/** Mapea clave de requisito → columna de atributo del personaje (inte, no int). */
function ope7_implante_atributo_col($k)
{
    return $k === 'int' ? 'inte' : $k;
}

/** ¿El personaje cumple los requisitos acumulados + la puerta de nivel? */
function ope7_implante_cumple($pid, $zona, $nivel)
{
    $f = ope7_pj_get($pid);
    if (!$f) {
        return false;
    }
    $t = ope7_implante_tabla($zona, $nivel);
    if ((int) $f['nivel'] < (int) $t['puerta']) {
        return false;
    }
    $req = ope7_implante_requisitos_acumulados($pid);
    foreach (array('res', 'vol', 'int') as $k) {
        if (isset($t['req'][$k]) && (int) $f[ope7_implante_atributo_col($k)] < $req[$k] + (int) $t['req'][$k]) {
            return false;
        }
    }
    return true;
}

/** Vara de instalación (23.3): Médico-Cirujano + Ingeniero al nivel de rama. */
function ope7_implante_vara($pid, $zona, $nivel, $autocirugia = false)
{
    global $db;
    $pid = (int) $pid;
    $t = ope7_implante_tabla($zona, $nivel);
    $vara = (int) $t['vara'];
    if ($autocirugia) {
        $vara = max(1, $vara - 1); // autocirugía: +1 (nivel exigido −1)
    }
    if ($pid < 1 || !ope7_tabla_existe('dominios') || !ope7_tabla_existe('dominios_personaje')) {
        return false;
    }
    $q = $db->query('SELECT dp.nivel, dp.rama FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
        . 'JOIN ' . ope7_tabla_full('dominios') . " d ON d.id = dp.dominio_id "
        . "WHERE dp.personaje_id = {$pid} AND d.nombre IN ('Médico','Ingeniero')");
    $medico = $ingeniero = 0;
    while ($r = $db->fetch_array($q)) {
        if ((string) $r['rama'] === 'Cirujano') {
            $medico = (int) $r['nivel'];
        }
        if ((string) $r['rama'] === 'Inventor' || (string) $r['rama'] === 'Maquinista Naval') {
            $ingeniero = max($ingeniero, (int) $r['nivel']);
        }
    }
    return $medico >= $vara && $ingeniero >= $vara;
}

/** ¿El personaje ya tiene implante en esa zona? (cupo 1 por zona, 23.2). */
function ope7_implante_zona_ocupada($pid, $zona)
{
    global $db;
    $pid = (int) $pid;
    $zona = (string) $zona;
    if ($pid < 1 || !ope7_tabla_existe('modificaciones_personaje') || !ope7_tabla_existe('implantes')) {
        return false;
    }
    $q = $db->query('SELECT m.id FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
        . 'JOIN ' . ope7_tabla_full('implantes') . " i ON i.id = m.implante_id "
        . "WHERE m.personaje_id = {$pid} AND m.estado = 'activo' AND i.zona = '{$zona}' LIMIT 1");
    return $db->num_rows($q) > 0;
}

/** Histórico de implantes (auditable, con firma). */
function ope7_implante_hist($mod_id, $pid, $evento, $motivo, $firmado_por = 0)
{
    global $db;
    if ((int) $mod_id < 1 || (int) $pid < 1 || !ope7_tabla_existe('implante_historico')) {
        return;
    }
    $db->insert_query('ope_implante_historico', array(
        'modificacion_id' => (int) $mod_id,
        'personaje_id'    => (int) $pid,
        'evento'          => (string) $evento,
        'motivo'          => (string) $motivo,
        'firmado_por'     => (int) $firmado_por,
        'fecha'           => TIME_NOW,
    ));
}

/**
 * Efecto 56 · Instalación de implante (23.2–23.3): valida zona única,
 * puerta de personaje, requisitos acumulativos, balanza a 0 (ranuras +
 * defectos = 0), cupo por zona, pago berries/PP y vara de Cirujano +
 * Ingeniero; aplica los defectos y registra la modificación + histórico.
 */
function ope7_efecto_instalar_implante($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    $implante_id = (int) ($ids['implante_id'] ?? $res['implante_id'] ?? 0);
    if ($pid < 1 || $implante_id < 1 || !ope7_tabla_existe('implantes') || !ope7_tabla_existe('modificaciones_personaje')) {
        return 'Instalación BLOQUEADA: faltan datos (personaje o implante).';
    }
    $q = $db->simple_select('ope_implantes', '*', "id = {$implante_id}", array('limit' => 1));
    $impl = $db->fetch_array($q);
    if (!$impl || (int) $impl['activo'] !== 1) {
        return 'Instalación BLOQUEADA: el implante no existe o está desactivado.';
    }
    $f = ope7_pj_get($pid);
    if (!$f) {
        return 'Instalación BLOQUEADA: personaje no encontrado.';
    }
    $zona = (string) $impl['zona'];
    $nivel = (string) $impl['nivel'];
    $t = ope7_implante_tabla($zona, $nivel);

    // 1) Cupo por zona (1 por zona, máx. 3; 23.2).
    if (ope7_implante_zona_ocupada($pid, $zona)) {
        return 'Instalación BLOQUEADA: ya tienes un implante activo en ' . $zona . ' (cupo 1 por zona, 23.2).';
    }
    // 2) Puerta de personaje (nv10/20/35).
    if ((int) $f['nivel'] < (int) $t['puerta']) {
        return 'Instalación BLOQUEADA: el implante ' . $nivel . ' exige nivel ' . $t['puerta'] . ' y tienes ' . (int) $f['nivel'] . ' (puerta, 23.2).';
    }
    // 3) Requisitos acumulativos (suma de todos los implantes, 5.22 §A.2).
    $req = ope7_implante_requisitos_acumulados($pid);
    $faltan = array();
    foreach (array('res', 'vol', 'int') as $k) {
        $col = ope7_implante_atributo_col($k);
        $necesario = $req[$k] + (int) ($t['req'][$k] ?? 0);
        if ((int) $f[$col] < $necesario) {
            $faltan[] = strtoupper($k) . ' ' . (int) $f[$col] . '/' . $necesario;
        }
    }
    if ($faltan) {
        return 'Instalación BLOQUEADA: requisitos acumulativos no cumplidos (' . implode(', ', $faltan) . ') — la suma de todos tus implantes (5.22 §A.2).';
    }
    // 4) Balanza a 0: ranuras (positivas) + defectos (negativas) = 0 (23.2).
    $ranuras = json_decode((string) ($impl['ranuras'] ?? '{}'), true);
    $defectos = json_decode((string) ($impl['defectos'] ?? '[]'), true);
    $balanza = 0;
    foreach ((array) $ranuras as $r) {
        $balanza += (int) ($r['puntos'] ?? 0);
    }
    $defectos_aplicar = array();
    foreach ((array) $defectos as $d) {
        $nombre = (string) ($d['nombre'] ?? '');
        $pts = ope7_implante_defectos_catalogo()[$nombre] ?? (int) ($d['puntos'] ?? 0);
        $balanza += $pts;
        if ($nombre !== '') {
            $defectos_aplicar[] = array('nombre' => $nombre, 'puntos' => $pts);
        }
    }
    if ($balanza !== 0) {
        return 'Instalación BLOQUEADA: la balanza del implante suma ' . $balanza . ' (debe ser exactamente 0: ranuras + defectos, 23.2).';
    }
    // 5) Vara de instalación (23.3): Médico-Cirujano + Ingeniero al nivel de rama.
    $autocirugia = (int) ($ids['autocirugia'] ?? 0) === 1;
    if (!ope7_implante_vara($pid, $zona, $nivel, $autocirugia)) {
        return 'Instalación BLOQUEADA: se necesita Médico con rama Cirujano + Ingeniero a nivel ' . $t['vara']
            . ($autocirugia ? ' (autocirugía: ' . max(1, $t['vara'] - 1) . ')' : '') . ' (vara de instalación, 23.3).';
    }
    // 6) Pago: berries (instalación) + PP del implante completo (200/400/600).
    $pago_cartera = ope7_cartera_mover($pid, 'cartera', -(int) $t['beries']);
    if (!$pago_cartera['ok']) {
        return 'Instalación BLOQUEADA: ' . $pago_cartera['msg'];
    }
    $pp = (int) $t['pp'];
    $saldo_pp = (int) ($f['pp_saldo'] ?? 0);
    if ($saldo_pp < $pp) {
        ope7_cartera_mover($pid, 'cartera', (int) $t['beries']); // devuelve (no se cobró si no hay PP)
        return 'Instalación BLOQUEADA: necesitas ' . $pp . ' PP y tienes ' . $saldo_pp . ' (PP del implante completo, 23.3).';
    }
    $db->update_query('ope_personajes', array('pp_saldo' => $saldo_pp - $pp), "id = {$pid}");
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => $pid,
            'cantidad'     => -$pp,
            'concepto'     => 'Instalación de implante: «' . (string) $impl['nombre'] . '» (' . $zona . ' ' . $nivel . ')',
            'tramite_id'   => (int) ($tr['id'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }

    // 7) Registra la modificación + aplica defectos + histórico.
    $mod_id = (int) $db->insert_query('ope_modificaciones_personaje', array(
        'implante_id'   => $implante_id,
        'personaje_id'  => $pid,
        'ranuras'       => json_encode($ranuras, JSON_UNESCAPED_UNICODE),
        'nivel'         => $nivel,
        'estado'        => 'activo',
        'daño'          => json_encode(array(), JSON_UNESCAPED_UNICODE),
    ));
    foreach ($defectos_aplicar as $d) {
        // Busca el defecto en el catálogo de 5.4 (por nombre); si no existe, lo crea el seed.
        $q = $db->simple_select('ope_defectos', 'id', "nombre = '" . $db->escape_string($d['nombre']) . "'", array('limit' => 1));
        $def_id = (int) $db->fetch_field($q, 'id');
        if ($def_id > 0) {
            $db->insert_query('ope_personaje_dotes', array(
                'personaje_id' => $pid,
                'defecto_id'   => $def_id,
                'origen'       => 'narrativo',
                'fecha'        => TIME_NOW,
            ));
        }
    }
    ope7_implante_hist($mod_id, $pid, 'instalacion', 'Instalado «' . (string) $impl['nombre'] . '» (' . $zona . ' ' . $nivel . ') por '
        . number_format((int) $t['beries']) . ' ฿ + ' . $pp . ' PP' . ($autocirugia ? ' (autocirugía)' : '') . '.', (int) ($tr['_staff_uid'] ?? 0));

    return 'Implante «' . (string) $impl['nombre'] . '» instalado (' . $zona . ' ' . $nivel . '): ' . number_format((int) $t['beries']) . ' ฿ + ' . $pp . ' PP cobrados, '
        . count($defectos_aplicar) . ' defecto(s) aplicados (balanza a 0), vara de Cirujano+Ingeniero cumplida. '
        . 'Mantenimiento ' . number_format((int) $t['mant']) . ' ฿/ronda (hook de 5.14).';
}

/**
 * Efecto 57 · Retirada de implante (23.2, ligero/firma): libera el cupo de
 * la zona y la balanza; las mejoras se pierden.
 */
function ope7_efecto_retirar_implante($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    $mod_id = (int) ($ids['modificacion_id'] ?? $res['modificacion_id'] ?? 0);
    if ($pid < 1 || $mod_id < 1 || !ope7_tabla_existe('modificaciones_personaje')) {
        return 'Retirada BLOQUEADA: faltan datos (personaje o implante).';
    }
    $q = $db->simple_select('ope_modificaciones_personaje', '*', "id = {$mod_id} AND personaje_id = {$pid} AND estado = 'activo'", array('limit' => 1));
    $mod = $db->fetch_array($q);
    if (!$mod) {
        return 'Retirada BLOQUEADA: el implante no está activo en este personaje.';
    }
    $motivo = trim((string) ($res['motivo'] ?? $tr['motivo'] ?? $tr['_firma_motivo'] ?? ''));
    if ($motivo === '') {
        return 'Retirada BLOQUEADA: se requiere un motivo escrito (queda en el histórico).';
    }
    $db->update_query('ope_modificaciones_personaje', array('estado' => 'retirado'), "id = {$mod_id}");
    ope7_implante_hist($mod_id, $pid, 'retirada', 'Retirado: ' . $motivo, (int) ($tr['_staff_uid'] ?? 0));

    return 'Implante retirado (#' . $mod_id . '): cupo de zona y balanza liberados; las mejoras se pierden (23.2).';
}

/**
 * Efecto 58 · Mantenimiento/reparación (23.3, ligero sin firma): pago por
 * ronda (hook de 5.14) o reparación con Ingeniero (grados de daño).
 */
function ope7_efecto_mantenimiento_implante($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    $mod_id = (int) ($ids['modificacion_id'] ?? $res['modificacion_id'] ?? 0);
    if ($pid < 1 || $mod_id < 1 || !ope7_tabla_existe('modificaciones_personaje')) {
        return 'Mantenimiento BLOQUEADO: faltan datos (personaje o implante).';
    }
    $q = $db->query('SELECT m.*, i.nombre, i.zona, i.nivel AS impl_nivel, i.defectos FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
        . 'JOIN ' . ope7_tabla_full('implantes') . " i ON i.id = m.implante_id "
        . "WHERE m.id = {$mod_id} AND m.personaje_id = {$pid} AND m.estado = 'activo' LIMIT 1");
    $mod = $db->fetch_array($q);
    if (!$mod) {
        return 'Mantenimiento BLOQUEADO: el implante no está activo en este personaje.';
    }
    $t = ope7_implante_tabla((string) $mod['zona'], (string) $mod['nivel']);
    $mant = (int) $t['mant'];
    // Defecto «Mantenimiento oneroso» duplica el coste (23.3).
    $defectos = json_decode((string) ($mod['defectos'] ?? '[]'), true);
    foreach ((array) $defectos as $d) {
        if ((string) ($d['nombre'] ?? '') === 'Mantenimiento oneroso') {
            $mant *= 2;
        }
    }
    // Reparación (estado averiado): Ingeniero + materiales; aquí solo el pago.
    $pago = ope7_cartera_mover($pid, 'cartera', -$mant);
    if (!$pago['ok']) {
        return 'Mantenimiento BLOQUEADO: ' . $pago['msg'];
    }
    $db->update_query('ope_modificaciones_personaje', array('estado' => 'activo'), "id = {$mod_id}");
    ope7_implante_hist($mod_id, $pid, 'mantenimiento', 'Mantenimiento pagado: ' . number_format($mant) . ' ฿ (ronda).', (int) ($tr['_staff_uid'] ?? 0));

    return 'Mantenimiento de «' . (string) $mod['nombre'] . '» pagado: ' . number_format($mant) . ' ฿ (ronda, 23.3).';
}

/**
 * Efecto 59 · Diseño de mejora a medida (staff, 23.6): la ranura de
 * habilidad especial calibrada — efecto del catálogo o no registrado con
 * condiciones. La IA propone, el staff firma la ficha.
 */
function ope7_efecto_diseno_implante($tr, $pid, $res, $ids)
{
    global $db;
    $implante_id = (int) ($ids['implante_id'] ?? $res['implante_id'] ?? 0);
    if ($implante_id < 1 || !ope7_tabla_existe('implantes')) {
        return 'Diseño BLOQUEADO: falta el implante a mejorar.';
    }
    // La ficha calibrada viaja en el resultado editable de la bandeja.
    $ficha = (array) ($res['ficha'] ?? $ids['ficha'] ?? array());
    if (!$ficha || !isset($ficha['ranuras']) || !isset($ficha['defectos'])) {
        return 'Diseño BLOQUEADO: la ficha calibrada (ranuras + defectos) debe venir del resultado del staff para poder aplicarse.';
    }
    $db->update_query('ope_implantes', array(
        'ranuras'  => json_encode($ficha['ranuras'], JSON_UNESCAPED_UNICODE),
        'defectos' => json_encode($ficha['defectos'], JSON_UNESCAPED_UNICODE),
    ), "id = {$implante_id}");
    ope7_implante_hist(0, (int) ($tr['personaje_id'] ?? 0), 'diseno', 'Mejora a medida calibrada y firmada por el staff.', (int) ($tr['_staff_uid'] ?? 0));

    return 'Mejora a medida calibrada y firmada: la ficha del implante #' . $implante_id . ' queda actualizada (23.6).';
}

/**
 * Efecto 60 · Concesión de linaje (staff, 23.7): cruza el expediente de
 * fidelidad con el cupo (3–5); aplica dote/defecto «La sangre llama»;
 * suceso de ronda.
 */
function ope7_efecto_conceder_linaje($tr, $pid, $res, $ids)
{
    global $db;
    $familia_id = (int) ($ids['familia_id'] ?? $res['familia_id'] ?? 0);
    $personaje_id = (int) ($ids['personaje_id'] ?? $res['personaje_id'] ?? 0);
    if ($familia_id < 1 || $personaje_id < 1 || !ope7_tabla_existe('familias_legendarias') || !ope7_tabla_existe('linaje_personaje')) {
        return 'Concesión de linaje BLOQUEADA: faltan datos (familia o personaje).';
    }
    $q = $db->simple_select('ope_familias_legendarias', '*', "id = {$familia_id}", array('limit' => 1));
    $fam = $db->fetch_array($q);
    if (!$fam) {
        return 'Concesión de linaje BLOQUEADA: la familia no existe.';
    }
    // Cupo mundial (3–5): activos < cupo.
    $q = $db->simple_select('ope_linaje_personaje', 'COUNT(*) AS c', "familia_id = {$familia_id} AND estado = 'activo'");
    $ocupados = (int) $db->fetch_field($q, 'c');
    if ($ocupados >= (int) $fam['cupo']) {
        return 'Concesión de linaje BLOQUEADA: cupo mundial lleno (' . $ocupados . '/' . (int) $fam['cupo'] . ', 23.7).';
    }
    // Un linaje por personaje.
    $q = $db->simple_select('ope_linaje_personaje', 'id', "personaje_id = {$personaje_id} AND estado = 'activo'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Concesión de linaje BLOQUEADA: ese personaje ya tiene un linaje activo (uno por PJ, 23.7).';
    }
    $motivo = trim((string) ($res['motivo'] ?? $tr['motivo'] ?? $tr['_firma_motivo'] ?? ''));
    if ($motivo === '') {
        return 'Concesión de linaje BLOQUEADA: se requiere un motivo (expediente de fidelidad, 23.7).';
    }
    // Aplica la dote de linaje + el defecto «La sangre llama» (los crea si el
    // catálogo no los tiene: el linaje trae SU dote/defecto, 23.7).
    $dote_nombre = trim((string) ($fam['dote'] ?? ''));
    $defecto_nombre = trim((string) ($fam['defecto'] ?? 'La sangre llama'));
    if ($dote_nombre !== '') {
        $q = $db->simple_select('ope_dotes', 'id', "nombre = '" . $db->escape_string($dote_nombre) . "'", array('limit' => 1));
        $dote_id = (int) $db->fetch_field($q, 'id');
        if ($dote_id < 1) {
            $dote_id = (int) $db->insert_query('ope_dotes', array(
                'nombre'             => $db->escape_string($dote_nombre),
                'efecto'             => json_encode(array('mecanica' => 'Dote de linaje (23.7): ' . (string) $fam['nombre']), JSON_UNESCAPED_UNICODE),
                'puntuacion'         => 1,
                'tipo'               => 'general',
                'requisitos'         => json_encode(array()),
                'incompatibilidades' => json_encode(array()),
                'activo'             => 1,
            ));
        }
        $db->insert_query('ope_personaje_dotes', array('personaje_id' => $personaje_id, 'dote_id' => $dote_id, 'origen' => 'narrativo', 'fecha' => TIME_NOW));
    }
    $q = $db->simple_select('ope_defectos', 'id', "nombre = '" . $db->escape_string($defecto_nombre) . "'", array('limit' => 1));
    $defecto_id = (int) $db->fetch_field($q, 'id');
    if ($defecto_id < 1) {
        $defecto_id = (int) $db->insert_query('ope_defectos', array(
            'nombre'             => $db->escape_string($defecto_nombre),
            'efecto'             => json_encode(array('mecanica' => 'Defecto de linaje (23.7): la sangre llama — el legado pesa.'), JSON_UNESCAPED_UNICODE),
            'puntuacion'         => -1,
            'requisitos'         => json_encode(array()),
            'incompatibilidades' => json_encode(array()),
            'activo'             => 1,
        ));
    }
    $db->insert_query('ope_personaje_dotes', array('personaje_id' => $personaje_id, 'defecto_id' => $defecto_id, 'origen' => 'narrativo', 'fecha' => TIME_NOW));
    $db->insert_query('ope_linaje_personaje', array(
        'familia_id'    => $familia_id,
        'personaje_id'  => $personaje_id,
        'estado'        => 'activo',
        'motivo'        => $db->escape_string($motivo),
        'concedido_por' => (int) ($tr['_staff_uid'] ?? 0),
        'fecha'         => TIME_NOW,
    ));
    // Suceso de ronda (5.14).
    if (ope7_tabla_existe('sucesos')) {
        $q = $db->simple_select('ope_personajes', 'nombre', "id = {$personaje_id}", array('limit' => 1));
        $pj_nombre = (string) $db->fetch_field($q, 'nombre');
        $db->insert_query('ope_sucesos', array(
            'isla_id'     => 0,
            'ronda'       => 0,
            'tipo'        => 'linaje',
            'titulo'      => 'La sangre llama: ' . $pj_nombre . ' y la ' . (string) $fam['nombre'],
            'descripcion' => 'El expediente de fidelidad habló: se concede el linaje «' . (string) $fam['nombre'] . '» a ' . $pj_nombre . '. Suceso en borrador: publícalo cuando toque la ronda.',
            'activo'      => 0,
        ));
    }

    return 'Linaje «' . (string) $fam['nombre'] . '» concedido a #' . $personaje_id . ' (cupo ' . ($ocupados + 1) . '/' . (int) $fam['cupo'] . '): '
        . 'dote y defecto aplicados, suceso de ronda en borrador (23.7).';
}

/**
 * Efecto 61 · Revocación de linaje (staff, 23.7): retira dote/defecto,
 * libera cupo, suceso de ronda; motivo obligatorio.
 */
function ope7_efecto_revocar_linaje($tr, $pid, $res, $ids)
{
    global $db;
    $linaje_id = (int) ($ids['linaje_id'] ?? $res['linaje_id'] ?? 0);
    if ($linaje_id < 1 || !ope7_tabla_existe('linaje_personaje')) {
        return 'Revocación de linaje BLOQUEADA: falta el linaje.';
    }
    $q = $db->simple_select('ope_linaje_personaje', '*', "id = {$linaje_id} AND estado = 'activo'", array('limit' => 1));
    $lin = $db->fetch_array($q);
    if (!$lin) {
        return 'Revocación de linaje BLOQUEADA: el linaje no está activo.';
    }
    $motivo = trim((string) ($res['motivo'] ?? $tr['motivo'] ?? $tr['_firma_motivo'] ?? ''));
    if ($motivo === '') {
        return 'Revocación de linaje BLOQUEADA: se requiere un motivo escrito (23.7).';
    }
    $q = $db->simple_select('ope_familias_legendarias', 'nombre, dote, defecto', "id = " . (int) $lin['familia_id'], array('limit' => 1));
    $fam = $db->fetch_array($q);
    $personaje_id = (int) $lin['personaje_id'];
    // Retira la dote y el defecto del personaje (origen narrativo del linaje).
    if ($fam) {
        foreach (array('dote' => 'dote_id', 'defecto' => 'defecto_id') as $campo => $col) {
            $nombre = trim((string) ($fam[$campo] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $q = $db->simple_select('ope_dotes', 'id', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            $q2 = $db->simple_select('ope_defectos', 'id', "nombre = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
            $dote_id = (int) $db->fetch_field($q, 'id');
            $defecto_id = (int) $db->fetch_field($q2, 'id');
            if ($col === 'dote_id' && $dote_id > 0) {
                $db->delete_query('ope_personaje_dotes', "personaje_id = {$personaje_id} AND dote_id = {$dote_id} AND origen = 'narrativo'");
            } elseif ($col === 'defecto_id' && $defecto_id > 0) {
                $db->delete_query('ope_personaje_dotes', "personaje_id = {$personaje_id} AND defecto_id = {$defecto_id} AND origen = 'narrativo'");
            }
        }
    }
    $db->update_query('ope_linaje_personaje', array(
        'estado'  => 'revocado',
        'motivo'  => $db->escape_string($motivo),
    ), "id = {$linaje_id}");
    if (ope7_tabla_existe('sucesos')) {
        $q = $db->simple_select('ope_personajes', 'nombre', "id = {$personaje_id}", array('limit' => 1));
        $pj_nombre = (string) $db->fetch_field($q, 'nombre');
        $db->insert_query('ope_sucesos', array(
            'isla_id'     => 0,
            'ronda'       => 0,
            'tipo'        => 'linaje',
            'titulo'      => 'Linaje revocado: ' . $pj_nombre,
            'descripcion' => 'Se revoca el linaje «' . (string) ($fam['nombre'] ?? '') . '» a ' . $pj_nombre . ': ' . $motivo . ' Suceso en borrador: publícalo cuando toque la ronda.',
            'activo'      => 0,
        ));
    }

    return 'Linaje revocado (#' . $linaje_id . '): dote/defecto retirados, cupo liberado, suceso de ronda en borrador (23.7).';
}

/**
 * Cron de ronda (5.14/23.3): mantenimiento por ronda — descuenta de la
 * cartera; si no hay saldo, el implante pasa a «averiado» (degradación).
 * Idempotente; integrado en ope7_progresion_cron.
 */
function ope7_implantes_ronda_mantenimiento()
{
    global $db;
    if (!ope7_tabla_existe('modificaciones_personaje') || !ope7_tabla_existe('implantes') || !ope7_tabla_existe('carteras')) {
        return 0;
    }
    $q = $db->query('SELECT m.id, m.personaje_id, i.nombre, i.zona, m.nivel, i.defectos, c.cartera '
        . 'FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
        . 'JOIN ' . ope7_tabla_full('implantes') . ' i ON i.id = m.implante_id '
        . 'LEFT JOIN ' . ope7_tabla_full('carteras') . " c ON c.personaje_id = m.personaje_id "
        . "WHERE m.estado = 'activo' LIMIT 100");
    $n = 0;
    while ($r = $db->fetch_array($q)) {
        $t = ope7_implante_tabla((string) $r['zona'], (string) $r['nivel']);
        $mant = (int) $t['mant'];
        $defectos = json_decode((string) ($r['defectos'] ?? '[]'), true);
        foreach ((array) $defectos as $d) {
            if ((string) ($d['nombre'] ?? '') === 'Mantenimiento oneroso') {
                $mant *= 2;
            }
        }
        $saldo = (int) ($r['cartera'] ?? 0);
        if ($saldo >= $mant) {
            ope7_cartera_mover((int) $r['personaje_id'], 'cartera', -$mant);
            ope7_implante_hist((int) $r['id'], (int) $r['personaje_id'], 'mantenimiento', 'Mantenimiento por ronda: ' . number_format($mant) . ' ฿ (hook 5.14).', 0);
        } else {
            $db->update_query('ope_modificaciones_personaje', array('estado' => 'averiado'), "id = " . (int) $r['id']);
            ope7_implante_hist((int) $r['id'], (int) $r['personaje_id'], 'averia', 'Sin saldo para el mantenimiento (' . number_format($mant) . ' ฿/ronda): el implante queda AVERIADO hasta pagar (23.3).', 0);
        }
        $n++;
    }
    return $n;
}

/**
 * Panel staff «Cibernética» (Anexo A.3, 5.22): implantes por personaje con
 * zona/nivel/estado, requisitos acumulados, mantenimientos pendientes e
 * histórico con firma. Devuelve HTML sin <style> ni estilos inline.
 */
function ope7_cibernetica_panel_html()
{
    global $db, $mybb;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $h = array();
    $h[] = '<div class="shead"><h1>Cibernética</h1><span class="code">A.3 · 5.22/23</span><span class="rule"></span></div>';
    $h[] = '<p class="zs-intro">Implantes de 3 zonas (extremidades/torso/cabeza) × niveles N1–N3: <b>requisitos acumulativos</b> (suma de todos los implantes, 5.22 §A.2), <b>balanza a 0</b> (ranuras + defectos), vara de <b>Cirujano + Ingeniero</b> y mantenimiento por ronda (23.3). Nada se instala sin firma.</p>';

    // ── Implantes activos por personaje ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Implantes activos</span><span class="c">zona · nivel · estado · requisitos</span></div><div class="plate-b">';
    $mods = array();
    if (ope7_tabla_existe('modificaciones_personaje')) {
        $q = $db->query('SELECT m.*, i.nombre, i.zona, i.nivel AS impl_nivel, p.nombre AS pj_nombre '
            . 'FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
            . 'JOIN ' . ope7_tabla_full('implantes') . ' i ON i.id = m.implante_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = m.personaje_id "
            . "WHERE m.estado IN ('activo','averiado') ORDER BY m.personaje_id, m.id");
        while ($r = $db->fetch_array($q)) {
            $mods[] = $r;
        }
    }
    if (!$mods) {
        $h[] = '<p class="pj-empty">Sin implantes instalados (se instalan con el trámite 56).</p>';
    } else {
        foreach ($mods as $r) {
            $t = ope7_implante_tabla((string) $r['zona'], (string) $r['nivel']);
            $estado = (string) $r['estado'];
            $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) $r['pj_nombre']) . '</b>'
                . ' <span class="ms-chip">' . $e((string) $r['nombre']) . '</span>'
                . ' <span class="ms-chip">' . $e((string) $r['zona']) . ' ' . $e((string) $r['nivel']) . '</span>'
                . ($estado === 'averiado' ? ' <span class="ms-secret">⚠ AVERIADO — sin mantenimiento</span>' : '')
                . '<div class="zs-mut">Requisitos ' . implode(' · ', array_map(function ($k, $v) { return strtoupper($k) . ' ' . $v; }, array_keys($t['req']), $t['req']))
                . ' · Mantenimiento ' . number_format((int) $t['mant']) . ' ฿/ronda · ' . (int) $t['ranuras'] . ' ranuras</div></div></div>';
        }
    }
    $h[] = '</div></div>';

    // ── Mantenimiento por ronda (A.3: «mantenimientos pendientes por ronda») ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Mantenimiento por ronda</span><span class="c">23.3 · pendiente de pago</span></div><div class="plate-b">';
    $ronda_inicio = 0;
    if (function_exists('ope7_ronda_activa')) {
        $ra = ope7_ronda_activa();
        $ronda_inicio = (int) ($ra['inicio'] ?? 0);
    }
    $mants = array();
    if (ope7_tabla_existe('modificaciones_personaje') && ope7_tabla_existe('implantes') && ope7_tabla_existe('implante_historico')) {
        $q = $db->query('SELECT m.id, m.personaje_id, m.estado, i.nombre, i.zona, m.nivel, i.defectos, p.nombre AS pj_nombre, '
            . '(SELECT MAX(h.fecha) FROM ' . ope7_tabla_full('implante_historico') . " h WHERE h.modificacion_id = m.id AND h.evento = 'mantenimiento') AS ultimo_pago "
            . 'FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
            . 'JOIN ' . ope7_tabla_full('implantes') . ' i ON i.id = m.implante_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = m.personaje_id "
            . "WHERE m.estado IN ('activo','averiado') ORDER BY m.personaje_id, m.id");
        while ($r = $db->fetch_array($q)) {
            $mants[] = $r;
        }
    }
    if (!$mants) {
        $h[] = '<p class="pj-empty">Sin implantes instalados: no hay mantenimientos que cobrar (23.3).</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Personaje</th><th>Implante</th><th>Estado</th><th>Último pago</th><th>Situación</th></tr></thead><tbody>';
        foreach ($mants as $r) {
            $t = ope7_implante_tabla((string) $r['zona'], (string) $r['nivel']);
            $mant = (int) $t['mant'];
            $defectos = json_decode((string) ($r['defectos'] ?? '[]'), true);
            foreach ((array) $defectos as $d) {
                if ((string) ($d['nombre'] ?? '') === 'Mantenimiento oneroso') {
                    $mant *= 2;
                }
            }
            $ultimo = (int) ($r['ultimo_pago'] ?? 0);
            if ((string) $r['estado'] === 'averiado') {
                $situacion = '<b class="zs-ok">AVERIADO — paga ' . number_format($mant, 0, ',', '.') . ' ฿ para reparar (58)</b>';
            } elseif ($ronda_inicio > 0 && $ultimo < $ronda_inicio) {
                $situacion = 'Pendiente de pago: ' . number_format($mant, 0, ',', '.') . ' ฿/ronda (se cobra al cierre)';
            } else {
                $situacion = 'Al día: ' . number_format($mant, 0, ',', '.') . ' ฿/ronda';
            }
            $h[] = '<tr><td>' . $e((string) $r['pj_nombre']) . '</td>'
                . '<td>' . $e((string) $r['nombre']) . ' <span class="zs-mut">(' . $e((string) $r['zona']) . ' ' . $e((string) $r['nivel']) . ')</span></td>'
                . '<td>' . $e((string) $r['estado']) . '</td>'
                . '<td>' . ($ultimo > 0 ? gmdate('d/m/Y', $ultimo) : '—') . '</td>'
                . '<td>' . $situacion . '</td></tr>';
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    // ── Acciones del staff: instalar (56), retirar (57), diseñar (59) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Acciones del staff</span><span class="c">56 · instalar · 57 · retirar · 59 · diseñar</span></div><div class="plate-b">';
    // Opciones de implantes del catálogo y de personajes aprobados.
    $opts_impl = '';
    if (ope7_tabla_existe('implantes')) {
        $q = $db->simple_select('ope_implantes', '*', 'activo = 1', array('order_by' => 'zona, nivel, nombre'));
        while ($r = $db->fetch_array($q)) {
            $opts_impl .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['nombre']) . ' (' . $e((string) $r['zona']) . ' ' . $e((string) $r['nivel']) . ')</option>';
        }
    }
    $opts_pj = '';
    if (ope7_tabla_existe('personajes')) {
        $q = $db->simple_select('ope_personajes', 'id, nombre', "estado = 'aprobado'", array('order_by' => 'nombre'));
        while ($r = $db->fetch_array($q)) {
            $opts_pj .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['nombre']) . '</option>';
        }
    }
    if ($opts_impl === '' || $opts_pj === '') {
        $h[] = '<p class="pj-empty">Sin catálogo de implantes o personajes aprobados (ejecuta el seed de 5.22).</p>';
    } else {
        // 56 · Instalación
        $h[] = '<form method="post" action="cibernetica-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="instalar">'
            . '<div class="zs-row"><div class="ms-grow"><b>Instalación (56)</b>'
            . '<div class="zs-mut">Valida zona única, puerta, requisitos acumulativos, balanza a 0, vara Cirujano+Ingeniero y pago (23.2–23.3).</div></div></div>'
            . '<div class="zs-grid2"><label class="flabel">Personaje<select name="personaje_id" class="tp-dyn">' . $opts_pj . '</select></label>'
            . '<label class="flabel">Implante<select name="implante_id" class="tp-dyn">' . $opts_impl . '</select></label></div>'
            . '<label class="flabel">Autocirugía<select name="autocirugia" class="tp-dyn"><option value="0">No (Cirujano + Ingeniero)</option><option value="1">Sí (vara −1)</option></select></label>'
            . '<label class="flabel">Concepto / motivo<textarea name="motivo" maxlength="2000" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 56</button></form>';
        // 57 · Retirada
        $opts_mod = '';
        if (ope7_tabla_existe('modificaciones_personaje')) {
            $q = $db->query('SELECT m.id, m.personaje_id, i.nombre, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('modificaciones_personaje') . ' m '
                . 'JOIN ' . ope7_tabla_full('implantes') . ' i ON i.id = m.implante_id '
                . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = m.personaje_id "
                . "WHERE m.estado = 'activo' ORDER BY m.id");
            while ($r = $db->fetch_array($q)) {
                $opts_mod .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['pj_nombre']) . ' — ' . $e((string) $r['nombre']) . '</option>';
            }
        }
        $h[] = '<form method="post" action="cibernetica-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="retirar">'
            . '<div class="zs-row"><div class="ms-grow"><b>Retirada (57)</b>'
            . '<div class="zs-mut">Libera el cupo de la zona y la balanza; las mejoras se pierden (23.2).</div></div></div>'
            . '<label class="flabel">Implante activo<select name="modificacion_id" class="tp-dyn">' . ($opts_mod !== '' ? $opts_mod : '<option value="">— sin implantes activos —</option>') . '</select></label>'
            . '<label class="flabel">Motivo (obligatorio)<textarea name="motivo" maxlength="1000" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 57</button></form>';
        // 59 · Diseño a medida
        $h[] = '<form method="post" action="cibernetica-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="disenar">'
            . '<div class="zs-row"><div class="ms-grow"><b>Diseño de mejora a medida (59)</b>'
            . '<div class="zs-mut">La ranura de habilidad especial calibrada: efecto del catálogo de 5.7 o no registrado con condiciones (guía 5.22 §4/§6).</div></div></div>'
            . '<label class="flabel">Implante a mejorar<select name="implante_id" class="tp-dyn">' . $opts_impl . '</select></label>'
            . '<label class="flabel">Concepto de la mejora<textarea name="concepto" maxlength="2000" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 59</button></form>';
    }
    $h[] = '</div></div>';

    // ── Histórico ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Histórico de implantes</span><span class="c">auditable · motivo y firma</span></div><div class="plate-b">';
    if (ope7_tabla_existe('implante_historico')) {
        $q = $db->query('SELECT h.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('implante_historico') . ' h '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = h.personaje_id '
            . 'ORDER BY h.fecha DESC LIMIT 20');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin eventos registrados todavía.</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Personaje</th><th>Evento</th><th>Motivo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                    . '<td>' . $e((string) $r['pj_nombre']) . '</td>'
                    . '<td>' . $e((string) $r['evento']) . '</td>'
                    . '<td>' . $e((string) $r['motivo']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de histórico no migrada.</p>';
    }
    $h[] = '</div></div>';

    return implode("\n", $h);
}

/**
 * Panel staff «Familias Legendarias» (Anexo A.3, 5.22 §B): catálogo con
 * cupos, portadores, expediente de fidelidad y bandeja de concesión/
 * revocación (60–61). Devuelve HTML sin <style> ni estilos inline.
 */
function ope7_familias_panel_html()
{
    global $db, $mybb;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $h = array();
    $h[] = '<div class="shead"><h1>Familias Legendarias</h1><span class="code">A.3 · 5.22 §B/23.7</span><span class="rule"></span></div>';
    $h[] = '<p class="zs-intro">3 linajes con cupo mundial (3–5): Línea D. · Los Vientomar · La Casa Cindral. La herencia se <b>juega</b> (expediente de fidelidad), no se demuestra: la IA propone, tú decides y firmas con motivo (23.7).</p>';

    // ── Catálogo con cupos y portadores ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Catálogo</span><span class="c">cupo mundial · portadores</span></div><div class="plate-b">';
    $familias = array();
    if (ope7_tabla_existe('familias_legendarias')) {
        $q = $db->query('SELECT f.*, (SELECT COUNT(*) FROM ' . ope7_tabla_full('linaje_personaje') . " l WHERE l.familia_id = f.id AND l.estado = 'activo') AS ocupados "
            . 'FROM ' . ope7_tabla_full('familias_legendarias') . ' f ORDER BY f.nombre');
        while ($r = $db->fetch_array($q)) {
            $familias[] = $r;
        }
    }
    if (!$familias) {
        $h[] = '<p class="pj-empty">Catálogo vacío — ejecuta el seed de 5.22.</p>';
    } else {
        foreach ($familias as $r) {
            $h[] = '<div class="zs-row"><div class="ms-grow"><b>' . $e((string) $r['nombre']) . '</b>'
                . ' <span class="ms-chip">' . (int) $r['ocupados'] . '/' . (int) $r['cupo'] . ' cupo</span>'
                . '<div class="zs-mut">Dote: ' . $e((string) $r['dote']) . ' · Defecto: ' . $e((string) $r['defecto']) . '</div>'
                . ($r['lore'] ? '<div class="zs-mut">' . $e((string) $r['lore']) . '</div>' : '')
                . '</div></div>';
        }
    }
    $h[] = '</div></div>';

    // ── Acciones del staff: concesión (60) y revocación (61) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Acciones del staff</span><span class="c">60 · conceder · 61 · revocar</span></div><div class="plate-b">';
    // Opciones de familias con cupo libre y de personajes sin linaje.
    $opts_fam = '';
    if (ope7_tabla_existe('familias_legendarias')) {
        $q = $db->query('SELECT f.*, (SELECT COUNT(*) FROM ' . ope7_tabla_full('linaje_personaje') . " l WHERE l.familia_id = f.id AND l.estado = 'activo') AS ocupados "
            . 'FROM ' . ope7_tabla_full('familias_legendarias') . ' f ORDER BY f.nombre');
        while ($r = $db->fetch_array($q)) {
            $libre = (int) $r['ocupados'] < (int) $r['cupo'];
            $opts_fam .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['nombre']) . ' (' . (int) $r['ocupados'] . '/' . (int) $r['cupo'] . ($libre ? '' : ' · lleno') . ')</option>';
        }
    }
    $opts_pj = '';
    if (ope7_tabla_existe('personajes') && ope7_tabla_existe('linaje_personaje')) {
        $q = $db->query('SELECT p.id, p.nombre FROM ' . ope7_tabla_full('personajes') . " p WHERE p.estado = 'aprobado' AND p.id NOT IN ("
            . 'SELECT l.personaje_id FROM ' . ope7_tabla_full('linaje_personaje') . " l WHERE l.estado = 'activo') ORDER BY p.nombre");
        while ($r = $db->fetch_array($q)) {
            $opts_pj .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['nombre']) . '</option>';
        }
    }
    $opts_lin = '';
    if (ope7_tabla_existe('linaje_personaje')) {
        $q = $db->query('SELECT l.id, f.nombre AS fam_nombre, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('linaje_personaje') . ' l '
            . 'JOIN ' . ope7_tabla_full('familias_legendarias') . ' f ON f.id = l.familia_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = l.personaje_id "
            . "WHERE l.estado = 'activo' ORDER BY f.nombre");
        while ($r = $db->fetch_array($q)) {
            $opts_lin .= '<option value="' . (int) $r['id'] . '">' . $e((string) $r['pj_nombre']) . ' — ' . $e((string) $r['fam_nombre']) . '</option>';
        }
    }
    if ($opts_fam === '') {
        $h[] = '<p class="pj-empty">Sin familias en el catálogo (ejecuta el seed de 5.22).</p>';
    } else {
        // 60 · Concesión
        $h[] = '<form method="post" action="familias-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="conceder">'
            . '<div class="zs-row"><div class="ms-grow"><b>Concesión (60)</b>'
            . '<div class="zs-mut">Cruza el expediente de fidelidad con el cupo (3–5): dote + «La sangre llama» + suceso de ronda (23.7).</div></div></div>'
            . '<div class="zs-grid2"><label class="flabel">Familia<select name="familia_id" class="tp-dyn">' . $opts_fam . '</select></label>'
            . '<label class="flabel">Personaje<select name="personaje_id" class="tp-dyn">' . ($opts_pj !== '' ? $opts_pj : '<option value="">— sin candidatos —</option>') . '</select></label></div>'
            . '<label class="flabel">Expediente / motivo (obligatorio)<textarea name="motivo" maxlength="1500" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 60</button></form>';
    }
    if ($opts_lin !== '') {
        // 61 · Revocación
        $h[] = '<form method="post" action="familias-staff.php" class="zs-form"><input type="hidden" name="my_post_key" value="' . $e($mybb->get_input('my_post_key')) . '">'
            . '<input type="hidden" name="gaccion" value="revocar">'
            . '<div class="zs-row"><div class="ms-grow"><b>Revocación (61)</b>'
            . '<div class="zs-mut">Traición al nombre o contradicciones de 5.5: retira dote/defecto, libera cupo, suceso de ronda (23.7).</div></div></div>'
            . '<label class="flabel">Linaje activo<select name="linaje_id" class="tp-dyn">' . $opts_lin . '</select></label>'
            . '<label class="flabel">Motivo (obligatorio)<textarea name="motivo" maxlength="1500" required></textarea></label>'
            . '<button type="submit" class="btn btn-ghost btn-sm">Crear trámite 61</button></form>';
    }
    $h[] = '</div></div>';

    // ── Portadores activos ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Portadores</span><span class="c">activos · revocados</span></div><div class="plate-b">';
    if (ope7_tabla_existe('linaje_personaje')) {
        $q = $db->query('SELECT l.*, f.nombre AS fam_nombre, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('linaje_personaje') . ' l '
            . 'JOIN ' . ope7_tabla_full('familias_legendarias') . ' f ON f.id = l.familia_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.id = l.personaje_id "
            . 'ORDER BY l.fecha DESC LIMIT 20');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin linajes concedidos todavía (trámite 60).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Personaje</th><th>Familia</th><th>Estado</th><th>Motivo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                    . '<td>' . $e((string) $r['pj_nombre']) . '</td>'
                    . '<td>' . $e((string) $r['fam_nombre']) . '</td>'
                    . '<td>' . $e((string) $r['estado']) . '</td>'
                    . '<td>' . $e((string) $r['motivo']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de linajes no migrada.</p>';
    }
    $h[] = '</div></div>';

    return implode("\n", $h);
}