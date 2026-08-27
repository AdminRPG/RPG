<?php
/**
 * One Piece: 7 Seas · Navegación y travesías (F4.3, 5.16/17.x)
 * ------------------------------------------------------------
 * Trámite 38 (naturaleza ia): la travesía es un tema presente (5.6).
 *  · Validación: ubicación = isla de origen (no editable) y un-presente.
 *  · Límite de mar por barco + madera (18.5): la madera del casco habilita
 *    los mares; el tipo exige una madera mínima.
 *  · IRT interno (17.3): base del mar (Blue 1 … ZR 4) + peligrosidad del
 *    destino (1–50) + estado del Mundo Vivo (0–2) − mitigadores (Navegante,
 *    Timonel/Cartógrafo, barco, utensilio). Nunca se publica al jugador.
 *  · Oráculos (17.4): catálogo de 7 tipos, bandas del IRT, veredicto al cierre.
 *  · Tiempo off-roll (17.5): 72/48/36 h por tramo sumables −12 h utensilio
 *    −25 % Maestre + horas de incidentes +24 h transporte = plazo del tema.
 *  · Víveres (17.6): 1 ración/persona/día on-roll +1/+2/+3 por oráculo;
 *    se consumen al cierre; sin stock, el veredicto empeora.
 *  · Vencimiento (17.5): plazo agotado sin cierre → travesía vencida.
 * Sin dados: el IRT es cálculo interno de la skill, los oráculos son
 * propuesta editable y el veredicto lo firma el staff.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Regiones marítimas (5.14/17.3): nivel · nombre · horas off-roll · días on-roll. */
function ope7_nav_regiones()
{
    return array(
        1 => array('region' => 'Blue',            'horas' => 72, 'irt' => 1, 'dias_on_roll' => 6),
        2 => array('region' => 'Paraíso',         'horas' => 48, 'irt' => 2, 'dias_on_roll' => 4),
        3 => array('region' => 'Nuevo Mundo',     'horas' => 36, 'irt' => 3, 'dias_on_roll' => 3),
        4 => array('region' => 'Zona restringida', 'horas' => 36, 'irt' => 4, 'dias_on_roll' => 3),
    );
}

/** Nivel de región de un mar (fila de `ope_mares` o array con `nombre`). */
function ope7_nav_nivel_mar($mar)
{
    $nombre = (string) ($mar['nombre'] ?? '');
    if (strpos($nombre, 'Blue') === 0) {
        return 1;
    }
    if ($nombre === 'Paraíso') {
        return 2;
    }
    if ($nombre === 'Nuevo Mundo') {
        return 3;
    }
    if ($nombre === 'Zona restringida') {
        return 4;
    }
    return max(1, (int) ($mar['irt_base'] ?? 1));
}

/**
 * Ruta entre dos islas: tramos por región (Blue→Paraíso = 2 tramos, 72+48 h;
 * Blue→Nuevo Mundo cruza Paraíso, 3 tramos). Devuelve tramos, horas base,
 * días on-roll y el nivel máximo de mar (el más duro de la ruta).
 */
function ope7_travesia_ruta($origen_id, $destino_id)
{
    global $db;
    $regiones = ope7_nav_regiones();
    $o = ope7_isla_por_id((int) $origen_id);
    $d = ope7_isla_por_id((int) $destino_id);
    if (!$o || !$d) {
        return null;
    }
    $mo = ope7_mar_por_id((int) $o['mar_id']);
    $md = ope7_mar_por_id((int) $d['mar_id']);
    if (!$mo || !$md) {
        return null;
    }
    $na = ope7_nav_nivel_mar($mo);
    $nb = ope7_nav_nivel_mar($md);
    $lo = min($na, $nb);
    $hi = max($na, $nb);
    $tramos = array();
    for ($n = $lo; $n <= $hi; $n++) {
        $tramos[] = $regiones[$n];
    }
    $horas = 0;
    $dias = 0;
    foreach ($tramos as $t) {
        $horas += (int) $t['horas'];
        $dias += (int) $t['dias_on_roll'];
    }
    return array(
        'tramos'      => $tramos,
        'max_nivel'   => $hi,
        'horas_base'  => $horas,
        'dias_on_roll'=> $dias,
        'nombres'     => implode(' → ', array_column($tramos, 'region')),
        'origen_id'   => (int) $origen_id,
        'destino_id'  => (int) $destino_id,
    );
}

/** Nivel de mar máximo que habilita una madera (18.5: `mares` JSON). */
function ope7_nav_madera_max_nivel($madera)
{
    if (!$madera) {
        return 0;
    }
    $mares = json_decode((string) ($madera['mares'] ?? '[]'), true);
    if (!is_array($mares)) {
        $mares = array();
    }
    $max = 0;
    foreach ($mares as $m) {
        $n = ope7_nav_nivel_mar(array('nombre' => (string) $m));
        if ($n > $max) {
            $max = $n;
        }
    }
    return (int) $max;
}

/** Oficio Navegante del personaje (4.3): nivel, rama y si es Maestre (5.3). */
function ope7_nav_pj_navegante($pid)
{
    global $db;
    $out = array('nivel' => 0, 'rama' => '', 'maestre' => false);
    if ((int) $pid < 1 || !ope7_tabla_existe('dominios') || !ope7_tabla_existe('dominios_personaje')) {
        return $out;
    }
    $q = $db->query('SELECT dp.nivel, dp.rama FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
        . 'JOIN ' . ope7_tabla_full('dominios') . " d ON d.id = dp.dominio_id "
        . 'WHERE dp.personaje_id = ' . (int) $pid . " AND d.nombre = 'Navegante' LIMIT 1");
    $r = $db->fetch_array($q);
    if (!$r) {
        return $out;
    }
    $out['nivel'] = (int) $r['nivel'];
    $out['rama'] = (string) ($r['rama'] ?? '');
    // Maestría Suprema del oficio (5.3): hito nv5 de rama.
    $out['maestre'] = $out['nivel'] >= 5 || in_array($out['rama'], array('Timonel', 'Cartógrafo'), true);
    return $out;
}

/**
 * Mitigadores por oficios de navegación (17.3): Navegante nv1 −1, nv2 −2,
 * Timonel nv3+ −1, Cartógrafo nv4 −1. Suma PJ + acompañantes.
 */
function ope7_nav_mitigadores($pid, array $acomp)
{
    $mit = 0;
    foreach (array_merge(array($pid), array_map('intval', $acomp)) as $pj) {
        $nav = ope7_nav_pj_navegante($pj);
        if ($nav['nivel'] >= 1) {
            $mit += $nav['nivel'] >= 2 ? 2 : 1;
        }
        if ($nav['nivel'] >= 3 && $nav['rama'] === 'Timonel') {
            $mit += 1;
        }
        if ($nav['nivel'] >= 4 && $nav['rama'] === 'Cartógrafo') {
            $mit += 1;
        }
    }
    return $mit;
}

/** ¿El utensilio declarado existe en el inventario/almacén? (17.7). */
function ope7_nav_utensilio_valido($pid, $utensilio_id)
{
    global $db;
    $utensilio_id = (int) $utensilio_id;
    if ($utensilio_id < 1 || !ope7_tabla_existe('objetos')) {
        return false;
    }
    $q = $db->simple_select('ope_objetos', 'nombre', "id = {$utensilio_id}", array('limit' => 1));
    $o = $db->fetch_array($q);
    if (!$o) {
        return false;
    }
    $nombre = (string) $o['nombre'];
    $es_utensilio = strpos($nombre, 'Log Pose') !== false
        || strpos($nombre, 'Eternal Pose') !== false
        || strpos($nombre, 'Brújula') !== false;
    if (!$es_utensilio) {
        return false;
    }
    if (ope7_tabla_existe('inventario_personaje')) {
        $q = $db->simple_select('ope_inventario_personaje', 'COUNT(*) AS n', "personaje_id = {$pid} AND objeto_id = {$utensilio_id} AND cantidad > 0");
        if ((int) $db->fetch_field($q, 'n') > 0) {
            return true;
        }
    }
    if (ope7_tabla_existe('almacen')) {
        $q = $db->simple_select('ope_almacen', 'COUNT(*) AS n', "personaje_id = {$pid} AND objeto_id = {$utensilio_id} AND cantidad > 0");
        if ((int) $db->fetch_field($q, 'n') > 0) {
            return true;
        }
    }
    return false;
}

/**
 * IRT interno (17.3): base del mar (1–4) + peligrosidad del destino (1–50)
 * + estado del Mundo Vivo (0–2, techo) − mitigadores. Solo-staff.
 */
function ope7_travesia_irt_calcular($pid, $ruta, $destino_ficha, $tipo_barco, $utensilio_ok, $mit_oficios, $estado_mundo = 0)
{
    $base = (int) $ruta['max_nivel'];
    $pel = (int) ($destino_ficha['peligrosidad'] ?? 0);
    $pel_add = $pel <= 10 ? 0 : ($pel <= 25 ? 1 : ($pel <= 40 ? 2 : 3));
    $estado = max(0, min(2, (int) $estado_mundo));
    $irt = $base + $pel_add + $estado;
    $mit = (int) $mit_oficios;
    if ($tipo_barco) {
        $mit += (int) ($tipo_barco['mitigador_irt'] ?? 0); // −1 a −3 (18.4)
    }
    if ($utensilio_ok) {
        $mit += 1;
    }
    $irt = max(0, $irt - $mit);
    return array(
        'irt' => $irt, 'base' => $base,
        'peligrosidad' => $pel, 'peligrosidad_add' => $pel_add,
        'estado' => $estado, 'mitigadores' => $mit,
    );
}

/** Banda de oráculos por IRT (17.3): nº de incidentes y gravedad máxima. */
function ope7_irt_banda_oraculos($irt)
{
    if ($irt <= 1) {
        return array('min' => 0, 'max' => 0, 'max_g' => 1); // tranquila
    }
    if ($irt <= 2) {
        return array('min' => 1, 'max' => 1, 'max_g' => 1); // 0–2 → 0–1 menor
    }
    if ($irt <= 4) {
        return array('min' => 1, 'max' => 1, 'max_g' => 2); // 3–5 → uno o dos
    }
    if ($irt <= 5) {
        return array('min' => 2, 'max' => 2, 'max_g' => 2);
    }
    if ($irt <= 7) {
        return array('min' => 2, 'max' => 2, 'max_g' => 3); // 6–8 → dos o tres (alguno grave)
    }
    if ($irt <= 8) {
        return array('min' => 3, 'max' => 3, 'max_g' => 3);
    }
    return array('min' => 3, 'max' => 4, 'max_g' => 3); // 9+ → 3+, daño asegurado
}

/**
 * Oráculos de la travesía (17.4): si el staff propuso una lista, se valida
 * (catálogo + banda); si no, se genera de forma determinista según la banda
 * del IRT. El transporte (ruta segura) limita a 0–1 incidente menor (17.6).
 */
function ope7_travesia_generar_oraculos($irt, array $propuestos, $transporte = '')
{
    global $db;
    $banda = ope7_irt_banda_oraculos($irt);
    $max_g = (int) $banda['max_g'];
    if ($transporte !== '') {
        $max_g = 1;
    }
    $cat = array(1 => array(), 2 => array(), 3 => array());
    if (ope7_tabla_existe('oraculos_catalogo')) {
        $q = $db->simple_select('ope_oraculos_catalogo', '*', 'activo = 1', array('order_by' => 'id'));
        while ($r = $db->fetch_array($q)) {
            $g = (string) $r['gravedad'];
            $gv = $g === 'menor' ? 1 : ($g === 'media' ? 2 : 3);
            $cat[$gv][] = $r;
        }
    }
    $nombre_por_tipo = array();
    foreach (array(1, 2, 3) as $gv) {
        foreach ($cat[$gv] as $r) {
            $nombre_por_tipo[(string) $r['tipo']] = array(
                'gravedad' => $gv,
                'efectos'  => json_decode((string) ($r['efectos'] ?? '{}'), true),
                'oraculo_id' => (int) $r['id'],
            );
        }
    }

    // Composición determinista por IRT (sin dados): menor=1, media=2, grave=3.
    $comp = array(
        0 => array(), 1 => array(),
        2 => array(1),
        3 => array(2), 4 => array(2),
        5 => array(2, 1),
        6 => array(2, 2),
        7 => array(2, 3),
        8 => array(2, 2, 3),
        9 => array(3, 2, 3),
    );
    $comp = $irt >= 10 ? array(3, 2, 3, 1) : ($comp[(int) $irt] ?? array());

    // Si hay propuesta del staff (ficha editable), validar y acotar a la banda.
    $out = array();
    if ($propuestos) {
        $contados = 0;
        foreach ($propuestos as $p) {
            if ($contados >= (int) $banda['max']) {
                break;
            }
            $tipo = trim((string) ($p['tipo'] ?? ''));
            if ($tipo === '' || !isset($nombre_por_tipo[$tipo])) {
                continue;
            }
            $gv = (int) $nombre_por_tipo[$tipo]['gravedad'];
            if ($gv > $max_g) {
                continue;
            }
            $ef = $nombre_por_tipo[$tipo]['efectos'];
            $out[] = array(
                'tipo' => $tipo,
                'gravedad' => $gv === 1 ? 'menor' : ($gv === 2 ? 'media' : 'grave'),
                'momento' => trim((string) ($p['momento'] ?? '')),
                'horas' => (int) ($ef['horas'] ?? 0),
                'viveres' => (int) ($ef['viveres'] ?? 0),
                'danio' => (string) ($ef['danio'] ?? 'ninguno'),
                'desvio' => !empty($ef['desvio']),
                'oraculo_id' => (int) $nombre_por_tipo[$tipo]['oraculo_id'],
            );
            $contados++;
        }
    }
    // Relleno determinista con el catálogo (rotando por gravedad).
    $usados = array();
    $i = 0;
    foreach ($comp as $gv) {
        if (count($out) >= (int) $banda['max'] || ($transporte !== '' && count($out) >= 1)) {
            break;
        }
        $gv = min($gv, $max_g);
        $elegido = null;
        foreach ($cat[$gv] as $r) {
            $t = (string) $r['tipo'];
            if (!isset($usados[$t])) {
                $elegido = $r;
                $usados[$t] = true;
                break;
            }
        }
        if (!$elegido) {
            continue; // catálogo sin tipos de esa gravedad
        }
        $ef = json_decode((string) ($elegido['efectos'] ?? '{}'), true);
        if (!is_array($ef)) {
            $ef = array();
        }
        $out[] = array(
            'tipo' => (string) $elegido['tipo'],
            'gravedad' => $gv === 1 ? 'menor' : ($gv === 2 ? 'media' : 'grave'),
            'momento' => 'Tramo ' . ($i + 1),
            'horas' => (int) ($ef['horas'] ?? 0),
            'viveres' => (int) ($ef['viveres'] ?? 0),
            'danio' => (string) ($ef['danio'] ?? 'ninguno'),
            'desvio' => !empty($ef['desvio']),
            'oraculo_id' => (int) $elegido['id'],
        );
        $i++;
    }
    return $out;
}

/** Tiempo off-roll de la travesía (17.5): plazo real para cerrar el tema. */
function ope7_travesia_tiempo_horas($ruta, array $oraculos, $utensilio_ok, $maestre, $transporte = '')
{
    $h = (int) $ruta['horas_base'];
    if ($utensilio_ok) {
        $h -= 12;
    }
    if ($maestre) {
        $h = (int) round($h * 0.75);
    }
    foreach ($oraculos as $o) {
        $h += (int) ($o['horas'] ?? 0);
    }
    if ($transporte !== '') {
        $h += 24;
    }
    return max(1, $h);
}

/** Raciones de víveres (17.6): 1 por persona y día on-roll +1/+2/+3 por oráculo. */
function ope7_travesia_raciones($personas, $dias_on_roll, array $oraculos)
{
    $extra = 0;
    foreach ($oraculos as $o) {
        $extra += (int) ($o['viveres'] ?? 0);
    }
    return max(0, (int) $personas * ((int) $dias_on_roll + $extra));
}

/** Id del consumible «Ración de viaje» en el catálogo de objetos. */
function ope7_nav_racion_objeto_id()
{
    global $db;
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $id = 0;
    if (ope7_tabla_existe('objetos')) {
        $q = $db->simple_select('ope_objetos', 'id', "nombre = 'Ración de viaje'", array('limit' => 1));
        $id = (int) $db->fetch_field($q, 'id');
    }
    return $id;
}

/** Consume raciones del inventario (mochila) y del almacén. Devuelve cuántas. */
function ope7_nav_consumir_raciones($pid, $n)
{
    global $db;
    $rid = ope7_nav_racion_objeto_id();
    if ($rid < 1 || (int) $n < 1) {
        return 0;
    }
    $consumidas = 0;
    foreach (array('ope_inventario_personaje', 'ope_almacen') as $tabla) {
        if (!ope7_tabla_existe(str_replace('ope_', '', $tabla))) {
            continue;
        }
        $q = $db->simple_select($tabla, 'id, cantidad', "personaje_id = {$pid} AND objeto_id = {$rid} AND cantidad > 0");
        while ($fila = $db->fetch_array($q)) {
            if ($consumidas >= (int) $n) {
                break;
            }
            $tomar = min((int) $fila['cantidad'], (int) $n - $consumidas);
            $db->update_query($tabla, array('cantidad' => (int) $fila['cantidad'] - $tomar), 'id = ' . (int) $fila['id']);
            $consumidas += $tomar;
        }
    }
    return $consumidas;
}

/** Nombre de la facción del personaje (para acceso a transportes, 17.7). */
function ope7_pj_faccion_nombre($pid)
{
    global $db;
    if (!ope7_tabla_existe('facciones')) {
        return '';
    }
    $q = $db->query('SELECT f.nombre FROM ' . ope7_tabla_full('facciones') . ' f '
        . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.faccion_id = f.id WHERE p.id = " . (int) $pid . ' LIMIT 1');
    return (string) $db->fetch_field($q, 'nombre');
}

/** Familia de la facción del personaje (institucional/libre/pirata…). */
function ope7_pj_faccion_familia($pid)
{
    global $db;
    if (!ope7_tabla_existe('facciones')) {
        return '';
    }
    $q = $db->query('SELECT f.familia FROM ' . ope7_tabla_full('facciones') . ' f '
        . 'JOIN ' . ope7_tabla_full('personajes') . " p ON p.faccion_id = f.id WHERE p.id = " . (int) $pid . ' LIMIT 1');
    return (string) $db->fetch_field($q, 'familia');
}

/**
 * Efecto 38 · Navegación (travesía): valida ubicación/un-presente/límite de
 * mar, calcula IRT interno + oráculos + tiempo + víveres, paga el transporte
 * si aplica, abre el tema presente y registra la travesía. Devuelve la ficha
 * pública (el desglose del IRT queda solo-staff).
 */
function ope7_efecto_navegacion($tr, $pid, $res, $ids)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return 'Navegación: sin personaje asociado (pendiente).';
    }

    // ── Validación de ubicación (17.2): origen = ubicación actual, no editable.
    $q = $db->simple_select('ope_personajes', 'nombre, ubicacion_isla_id, ubicacion_texto, wanted_base, faccion_id', "id = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($q);
    if (!$pj) {
        return 'Navegación: personaje no encontrado.';
    }
    $origen_id = (int) ($pj['ubicacion_isla_id'] ?? 0);
    if ($origen_id < 1) {
        return 'Navegación BLOQUEADA: el personaje no tiene isla de origen registrada (ubicación).';
    }
    $origen = ope7_isla_por_id($origen_id);
    if (!$origen) {
        return 'Navegación BLOQUEADA: la isla de origen no está en el catálogo (5.14).';
    }

    // ── Un presente a la vez (5.6): la travesía ES el presente.
    if (ope7_pj_tiene_presente_abierto($pid)) {
        return 'Navegación BLOQUEADA: el personaje ya tiene un tema presente abierto (un-presente, 5.6) — la travesía es su presente.';
    }

    // ── Destino (catálogo 5.14) y ruta.
    $destino_id = (int) ($res['destino_id'] ?? $ids['destino_id'] ?? 0);
    if ($destino_id < 1) {
        return 'Navegación BLOQUEADA: falta la isla de destino (catálogo 5.14).';
    }
    $destino = ope7_isla_por_id($destino_id);
    if (!$destino) {
        return 'Navegación BLOQUEADA: la isla de destino no existe en el catálogo (5.14).';
    }
    if ($destino_id === $origen_id) {
        return 'Navegación BLOQUEADA: el destino no puede ser la isla de origen.';
    }
    $ruta = ope7_travesia_ruta($origen_id, $destino_id);
    if (!$ruta) {
        return 'Navegación BLOQUEADA: no se pudo calcular la ruta (mares no catalogados).';
    }

    // ── Barco (con límite de mar por madera, 18.5) o transporte (17.7).
    $barco = null;
    $tipo_barco = null;
    $barco_id = (int) ($res['barco_id'] ?? $ids['barco_id'] ?? 0);
    $transporte_tipo = (string) ($res['transporte_tipo'] ?? $ids['transporte_tipo'] ?? '');
    if ($barco_id > 0 && $transporte_tipo === '') {
        if (!ope7_tabla_existe('barcos')) {
            return 'Navegación BLOQUEADA: sin flota registrada (pendiente).';
        }
        $q = $db->simple_select('ope_barcos', '*', "id = {$barco_id}", array('limit' => 1));
        $barco = $db->fetch_array($q);
        if (!$barco) {
            return 'Navegación BLOQUEADA: el barco no existe.';
        }
        if ((int) ($barco['dueno_id'] ?? 0) !== $pid) {
            return 'Navegación BLOQUEADA: el barco no es de este personaje (17.2).';
        }
        if ((string) ($barco['estado'] ?? '') === 'hundido') {
            return 'Navegación BLOQUEADA: el barco está hundido (18.7).';
        }
        $q = $db->simple_select('ope_tipos_barcos', '*', 'id = ' . (int) $barco['tipo_id'], array('limit' => 1));
        $tipo_barco = $db->fetch_array($q);
        $q = $db->simple_select('ope_maderas_casco', '*', 'id = ' . (int) $barco['madera_id'], array('limit' => 1));
        $madera = $db->fetch_array($q);
        if (!$madera) {
            return 'Navegación BLOQUEADA: la madera del casco no está en el catálogo (18.5).';
        }
        $mad_max = ope7_nav_madera_max_nivel($madera);
        // El tipo exige una madera mínima (18.4). `madera_minima` es el NOMBRE
        // de la madera del catálogo (18.5), no un mar: se resuelve contra
        // maderas_casco para su nivel de mar.
        $tipo_min = 0;
        if ($tipo_barco && (string) ($tipo_barco['madera_minima'] ?? '') !== '') {
            $tmq = $db->simple_select('ope_maderas_casco', '*', "nombre = '" . $db->escape_string((string) $tipo_barco['madera_minima']) . "'", array('limit' => 1));
            $tm = $db->fetch_array($tmq);
            $tipo_min = $tm ? ope7_nav_madera_max_nivel($tm) : 0;
        }
        if ($tipo_min > 0 && $tipo_min > $mad_max) {
            return 'Navegación BLOQUEADA: el tipo de barco exige al menos ' . (string) ($tipo_barco['madera_minima'] ?? '?') . ' y el casco es de ' . (string) $madera['nombre'] . ' (18.5).';
        }
        // Límite de mar por casco: la madera habilita la región (18.5).
        if ((int) $ruta['max_nivel'] > $mad_max) {
            return 'Navegación BLOQUEADA: la madera ' . (string) $madera['nombre'] . ' no habilita el mar de esta ruta (' . $ruta['nombres'] . ') — límite de mar por casco (18.5).';
        }
    } elseif ($transporte_tipo !== '' && $barco_id < 1) {
        if (!in_array($transporte_tipo, array('civil', 'clandestino', 'gobierno'), true)) {
            return 'Navegación BLOQUEADA: tipo de transporte no válido (17.7).';
        }
        // Acceso por afiliación (17.7).
        if ($transporte_tipo === 'clandestino') {
            if (!in_array(ope7_pj_faccion_nombre($pid), array('Piratas', 'Revolucionarios'), true)) {
                return 'Navegación BLOQUEADA: el transporte clandestino es solo para piratas y revolucionarios (17.7).';
            }
        }
        if ($transporte_tipo === 'gobierno' && ope7_pj_faccion_familia($pid) !== 'institucional') {
            return 'Navegación BLOQUEADA: los navíos del Gobierno son gratis solo en servicio (17.7) — un buscado no sube sin soborno (trámite de Ladrón).';
        }
    } else {
        return 'Navegación BLOQUEADA: indica un barco de tu flota o un transporte (17.2/17.6).';
    }

    // ── Utensilio (17.7): si se declara, debe estar en el inventario.
    $utensilio_id = (int) ($res['utensilio_id'] ?? $ids['utensilio_id'] ?? 0);
    $utensilio_ok = $utensilio_id > 0 && ope7_nav_utensilio_valido($pid, $utensilio_id);
    if ($utensilio_id > 0 && !$utensilio_ok) {
        return 'Navegación BLOQUEADA: el utensilio declarado no está en el inventario del personaje (17.7).';
    }

    // ── Tripulación (acompañantes con oficios, 17.2).
    $acomp = (array) ($ids['tripulacion'] ?? $res['tripulacion'] ?? array());
    $acomp = array_values(array_filter(array_map('intval', $acomp)));
    $personas = 1 + count($acomp);

    // ── Pago de transporte (17.6): tarifa por persona y tramo + recargo Wanted.
    $pago_msg = '';
    if ($transporte_tipo !== '') {
        $coste = 0;
        if (ope7_tabla_existe('transportes')) {
            $q = $db->simple_select('ope_transportes', 'tarifa', "tipo = '{$transporte_tipo}'", array('limit' => 1));
            $tp = $db->fetch_array($q);
            $tar = $tp ? json_decode((string) ($tp['tarifa'] ?? '{}'), true) : null;
            if (is_array($tar)) {
                foreach ($ruta['tramos'] as $t) {
                    $clave = $t['region'] === 'Blue' ? 'Blue Este' : $t['region'];
                    $coste += (int) ($tar[$clave] ?? 0);
                }
            }
        }
        $coste *= $personas;
        if ($transporte_tipo === 'civil') {
            $recargo = (int) floor((int) ($pj['wanted_base'] ?? 0) / 1000000) * 1000 * count($ruta['tramos']) * $personas;
            $coste += $recargo;
        }
        if ($coste > 0) {
            $c = ope7_cartera_get($pid);
            if ((int) $c['cartera'] < $coste) {
                return 'Navegación BLOQUEADA: cartera insuficiente para el transporte (' . number_format($coste) . ' ฿ · tienes ' . number_format((int) $c['cartera']) . ').';
            }
            ope7_cartera_mover($pid, 'cartera', -$coste);
            $pago_msg = 'pago −' . number_format($coste) . ' ฿';
        }
    }

    // ── Cálculos internos (17.3–17.6).
    $ficha_destino = ope7_isla_ficha($destino_id) ?: array('peligrosidad' => 0);
    $mit_oficios = ope7_nav_mitigadores($pid, $acomp);
    $estado_mundo = (int) ($res['estado_mundo'] ?? 0); // +0..+2 (techo), propuesta staff
    $irt_calc = ope7_travesia_irt_calcular($pid, $ruta, $ficha_destino, $tipo_barco, $utensilio_ok, $mit_oficios, $estado_mundo);
    $irt = (int) $irt_calc['irt'];
    $oraculos = ope7_travesia_generar_oraculos($irt, (array) ($res['oraculos'] ?? array()), $transporte_tipo);
    $maestre = ope7_nav_pj_navegante($pid)['maestre'];
    $horas = ope7_travesia_tiempo_horas($ruta, $oraculos, $utensilio_ok, $maestre, $transporte_tipo);
    $raciones = ope7_travesia_raciones($personas, (int) $ruta['dias_on_roll'], $oraculos);

    // ── Abre el tema presente (5.6): travesía invadible, tema_tipo travesia.
    $ids_abrir = array('tipo' => 'presente', 'tema_tipo' => 'travesia', 'zona' => (string) $origen['nombre'] . ' → ' . (string) $destino['nombre']);
    $res_abrir = array('tipo' => 'presente', 'tema_tipo' => 'travesia');
    ope7_efecto_apertura_tema($tr, $pid, $res_abrir, $ids_abrir);
    $q = $db->query('SELECT tp.tema_id FROM ' . ope7_tabla_full('temas_participantes') . ' tp '
        . 'WHERE tp.personaje_id = ' . $pid . ' ORDER BY tp.id DESC LIMIT 1');
    $tema_id = (int) $db->fetch_field($q, 'tema_id');
    if ($tema_id < 1) {
        return 'Navegación BLOQUEADA: no se pudo abrir el tema presente (5.6).';
    }

    // ── Registra la travesía (17.8) y sus incidentes.
    // Ojo: MyBB convierte null → '' en insert_query y las columnas INT lo
    // rechazan (bug conocido F4.1): los opcionales INT van como 0.
    $travesia_id = (int) $db->insert_query('ope_travesias', array(
        'tema_id'           => $tema_id,
        'origen_isla_id'    => $origen_id,
        'destino_isla_id'   => $destino_id,
        'ruta'              => json_encode($ruta['tramos'], JSON_UNESCAPED_UNICODE),
        'barco_id'          => $barco_id > 0 ? $barco_id : 0,
        'transporte_tipo'   => $transporte_tipo !== '' ? $transporte_tipo : '',
        'utensilio_id'      => $utensilio_id > 0 ? $utensilio_id : 0,
        'tripulacion'       => json_encode($acomp, JSON_UNESCAPED_UNICODE),
        'irt'               => $irt,
        'oraculos'          => json_encode($oraculos, JSON_UNESCAPED_UNICODE),
        'tiempo_disponible_h' => $horas,
        'tiempo_on_roll'    => (string) $ruta['dias_on_roll'] . ' días on-roll (' . $ruta['nombres'] . ')',
        'viveres_gastados'  => 0,
        'estado'            => 'en_travesia',
        'veredicto'         => json_encode(array(
            'ficha' => array(
                'narrativa' => (string) ($res['narrativa'] ?? ''),
                'oraculos'  => count($oraculos),
                'tiempo_h'  => $horas,
                'raciones'  => $raciones,
            ),
        ), JSON_UNESCAPED_UNICODE),
    ));
    if (ope7_tabla_existe('incidentes_travesia')) {
        foreach ($oraculos as $o) {
            $db->insert_query('ope_incidentes_travesia', array(
                'travesia_id' => $travesia_id,
                'oraculo_id'  => (int) ($o['oraculo_id'] ?? 0),
                'momento'     => (string) ($o['momento'] ?? ''),
            ));
        }
    }

    // ── Ficha pública (el desglose del IRT NO se publica, 17.3).
    $ficha = '⚓ Travesía ' . (string) $origen['nombre'] . ' → ' . (string) $destino['nombre']
        . ' · ruta ' . $ruta['nombres'] . ' · plazo ' . $horas . ' h reales (' . (int) $ruta['dias_on_roll'] . ' días on-roll)'
        . ($barco ? ' · barco «' . (string) $barco['nombre'] . '»' : ' · transporte ' . $transporte_tipo . ($pago_msg !== '' ? " ({$pago_msg})" : ''))
        . ($utensilio_ok ? ' · utensilio aplica −12 h' : '')
        . ' · oráculos: ' . count($oraculos)
        . (count($oraculos) ? ' — ' . implode(', ', array_map(function ($o) {
            return (string) $o['tipo'] . ' [' . (string) $o['gravedad'] . ']';
        }, $oraculos)) : '')
        . ' · víveres estimados: ' . $raciones . ' raciones para ' . $personas . ' persona(s).';
    return 'Tema presente ' . $tema_id . ' abierto (travesía, 5.6). ' . $ficha . ' · IRT interno ' . $irt . ' (solo-staff).';
}

/**
 * Veredicto al cierre del tema (17.6): consume víveres (ración/día on-roll +
 * oráculos), aplica el daño al barco por grado, mueve la ubicación al destino
 * y marca la travesía resuelta. Devuelve '' si el tema no es una travesía.
 */
function ope7_travesia_cierre_veredicto($tema_id, $pid, $res)
{
    global $db;
    $tema_id = (int) $tema_id;
    $pid = (int) $pid;
    if ($tema_id < 1 || $pid < 1 || !ope7_tabla_existe('travesias') || !ope7_tabla_existe('personajes')) {
        return '';
    }
    $q = $db->simple_select('ope_travesias', '*', "tema_id = {$tema_id} AND estado IN ('en_travesia','planificada')", array('limit' => 1));
    $trv = $db->fetch_array($q);
    if (!$trv) {
        return '';
    }
    $notas = array();
    $oraculos = json_decode((string) ($trv['oraculos'] ?? '[]'), true);
    if (!is_array($oraculos)) {
        $oraculos = array();
    }
    $tripulacion = json_decode((string) ($trv['tripulacion'] ?? '[]'), true);
    $personas = 1 + (is_array($tripulacion) ? count($tripulacion) : 0);
    $ruta = ope7_travesia_ruta((int) $trv['origen_isla_id'], (int) $trv['destino_isla_id']);
    $dias = $ruta ? (int) $ruta['dias_on_roll'] : 0;
    $necesarias = ope7_travesia_raciones($personas, $dias, $oraculos);
    $consumidas = $necesarias > 0 ? ope7_nav_consumir_raciones($pid, $necesarias) : 0;
    $notas[] = 'Travesía: ' . $personas . ' persona(s) · ' . $dias . ' días on-roll · ' . $necesarias . ' raciones necesarias, ' . $consumidas . ' consumidas';
    if ($consumidas < $necesarias) {
        $notas[] = 'SIN víveres suficientes: el veredicto empeora (desvío +24 h o incidente mayor, 17.6).';
    }
    // Daño al barco por grado máximo de los oráculos (17.4/17.6).
    $grado = 0;
    foreach ($oraculos as $o) {
        $dg = (string) ($o['danio'] ?? 'ninguno');
        $g = $dg === 'grave' ? 3 : ($dg === 'moderado' ? 2 : ($dg === 'leve' ? 1 : 0));
        if ($g > $grado) {
            $grado = $g;
        }
    }
    $barco_id = (int) ($trv['barco_id'] ?? 0);
    if ($barco_id > 0 && $grado > 0 && ope7_tabla_existe('barcos')) {
        $estados = array(1 => 'danado_leve', 2 => 'danado_moderado', 3 => 'danado_grave');
        $db->update_query('ope_barcos', array('estado' => $estados[$grado]), "id = {$barco_id}");
        $notas[] = 'Barco dañado: grado ' . ($grado === 3 ? 'grave' : ($grado === 2 ? 'moderado' : 'leve')) . ' por los oráculos (17.6).';
    }
    // Cambio de ubicación (17.6): personajes.ubicacion = destino.
    $destino_id = (int) $trv['destino_isla_id'];
    $dest = ope7_isla_por_id($destino_id);
    $db->update_query('ope_personajes', array(
        'ubicacion_isla_id' => $destino_id,
        'ubicacion_texto'   => $dest ? (string) $dest['nombre'] : '',
    ), "id = {$pid}");
    $notas[] = 'Nueva ubicación: ' . ($dest ? (string) $dest['nombre'] : 'isla ' . $destino_id) . '.';
    // Travesía resuelta + log de incidentes.
    $db->update_query('ope_travesias', array(
        'estado'          => 'resuelta',
        'viveres_gastados'=> $consumidas,
        'veredicto'       => json_encode(array(
            'raciones_necesarias' => $necesarias,
            'raciones_consumidas' => $consumidas,
            'danio'               => $grado,
            'ubicacion'           => $destino_id,
            'notas'               => $notas,
        ), JSON_UNESCAPED_UNICODE),
    ), 'id = ' . (int) $trv['id']);
    if (ope7_tabla_existe('incidentes_travesia')) {
        $db->update_query('ope_incidentes_travesia', array(
            'resolucion' => json_encode(array('veredicto' => (string) ($res['motivo'] ?? '')), JSON_UNESCAPED_UNICODE),
        ), 'travesia_id = ' . (int) $trv['id']);
    }
    return implode(' · ', $notas);
}

/**
 * Panel staff «Navegación» (Anexo A.3, 17.8): travesías activas por isla y
 * jugador con oráculos/tiempos/vencimientos, víveres pendientes al cierre y
 * avisos de plazo; histórico de travesías resueltas/vencidas. Devuelve HTML
 * con cero <style> y cero estilos inline estáticos.
 */
function ope7_navegacion_panel_html()
{
    global $db;
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };

    $html = '<div class="shead"><h1>Navegación</h1><span class="code">A.3 · 5.16/17</span><span class="rule"></span></div>';

    // ── Travesías activas (17.8): por jugador, ruta, plazo y oráculos ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Travesías activas</span><span class="c">en_travesia · presente 5.6</span></div><div class="plate-b">';
    $activas = array();
    if (ope7_tabla_existe('travesias') && ope7_tabla_existe('temas') && ope7_tabla_existe('temas_participantes')) {
        $q = $db->query('SELECT tr.*, t.fecha_real_apertura, p.id AS pj_id, p.nombre AS pj_nombre '
            . 'FROM ' . ope7_tabla_full('travesias') . ' tr '
            . 'JOIN ' . ope7_tabla_full('temas') . ' t ON t.tid = tr.tema_id '
            . 'JOIN ' . ope7_tabla_full('temas_participantes') . ' tp ON tp.tema_id = tr.tema_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tp.personaje_id '
            . "WHERE tr.estado = 'en_travesia' AND t.estado = 'abierto' ORDER BY tr.id DESC LIMIT 50");
        while ($r = $db->fetch_array($q)) {
            $activas[] = $r;
        }
    }
    if (!$activas) {
        $html .= '<p class="nv-empty">Sin travesías activas (17.5). El plazo es el del tema: si vence sin cierre, se resuelve por veredicto.</p>';
    } else {
        $html .= '<table class="nv-table"><thead><tr>'
            . '<th>Jugador</th><th>Ruta</th><th>Medio</th><th>Plazo</th><th>Vence</th><th>Oráculos</th><th>Víveres al cierre</th>'
            . '</tr></thead><tbody>';
        foreach ($activas as $r) {
            $o = ope7_isla_por_id((int) $r['origen_isla_id']);
            $d = ope7_isla_por_id((int) $r['destino_isla_id']);
            $ruta = ope7_travesia_ruta((int) $r['origen_isla_id'], (int) $r['destino_isla_id']);
            $orac = json_decode((string) ($r['oraculos'] ?? '[]'), true);
            if (!is_array($orac)) {
                $orac = array();
            }
            $trip = json_decode((string) ($r['tripulacion'] ?? '[]'), true);
            $personas = 1 + (is_array($trip) ? count($trip) : 0);
            $raciones = ope7_travesia_raciones($personas, $ruta ? (int) $ruta['dias_on_roll'] : 0, $orac);
            $vence = (int) ($r['fecha_real_apertura'] ?? 0) + (int) $r['tiempo_disponible_h'] * 3600;
            $restante_h = (int) round(($vence - TIME_NOW) / 3600);
            $clase_aviso = $restante_h < 48 ? ' nv-warn' : '';
            $medios = array();
            if ((int) ($r['barco_id'] ?? 0) > 0) {
                $bq = $db->simple_select('ope_barcos', 'nombre', 'id = ' . (int) $r['barco_id'], array('limit' => 1));
                $medios[] = 'barco «' . $e((string) $db->fetch_field($bq, 'nombre')) . '»';
            }
            if ((string) ($r['transporte_tipo'] ?? '') !== '') {
                $medios[] = 'transporte ' . $e((string) $r['transporte_tipo']);
            }
            $html .= '<tr><td>' . $e((string) $r['pj_nombre']) . ' <span class="nv-dim">#' . (int) $r['pj_id'] . '</span></td>'
                . '<td>' . $e((string) ($o['nombre'] ?? '?')) . ' → ' . $e((string) ($d['nombre'] ?? '?')) . ' <span class="nv-dim">(' . $e((string) ($ruta['nombres'] ?? '?')) . ')</span></td>'
                . '<td>' . (implode(' · ', $medios) ?: '<span class="nv-dim">—</span>') . '</td>'
                . '<td>' . (int) $r['tiempo_disponible_h'] . ' h · ' . $e((string) $r['tiempo_on_roll']) . '</td>'
                . '<td class="' . trim('nv-vence' . $clase_aviso) . '">' . date('d/m H:i', max(TIME_NOW, $vence)) . ($restante_h > 0 ? ' <span class="nv-dim">(' . $restante_h . ' h)</span>' : ' <span class="nv-dim">vencida</span>') . '</td>'
                . '<td>' . count($orac) . ($orac ? ' — ' . $e(implode(', ', array_map(function ($x) { return (string) $x['tipo'] . ' [' . (string) $x['gravedad'] . ']'; }, $orac))) : '') . '</td>'
                . '<td>' . $raciones . ' raciones (' . $personas . ' pers.)</td></tr>';
        }
        $html .= '</tbody></table>';
    }
    $html .= '<p class="nv-foot">La ficha se edita y firma en la bandeja (trámite 38, skill-navegacion): la IA propone oráculos y tiempo, el staff matiza y firma; el IRT queda solo-staff (17.3).</p>';
    $html .= '</div></div>';

    // ── Histórico de travesías resueltas/vencidas (17.8) ──
    $html .= '<div class="plate"><div class="plate-h"><span class="t">Histórico de travesías</span><span class="c">resueltas · vencidas</span></div><div class="plate-b">';
    $hist = array();
    if (ope7_tabla_existe('travesias') && ope7_tabla_existe('temas_participantes')) {
        $q = $db->query('SELECT tr.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('travesias') . ' tr '
            . 'JOIN ' . ope7_tabla_full('temas_participantes') . ' tp ON tp.tema_id = tr.tema_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tp.personaje_id '
            . "WHERE tr.estado IN ('resuelta','vencida','abortada') ORDER BY tr.id DESC LIMIT 10");
        while ($r = $db->fetch_array($q)) {
            $hist[] = $r;
        }
    }
    if (!$hist) {
        $html .= '<p class="nv-empty">Sin travesías cerradas todavía.</p>';
    } else {
        foreach ($hist as $r) {
            $o = ope7_isla_por_id((int) $r['origen_isla_id']);
            $d = ope7_isla_por_id((int) $r['destino_isla_id']);
            $v = json_decode((string) ($r['veredicto'] ?? '{}'), true);
            if (!is_array($v)) {
                $v = array();
            }
            $grado = (int) ($v['danio'] ?? 0);
            $html .= '<div class="nv-mov"><div class="nv-mov-h"><b>' . $e((string) $r['pj_nombre']) . '</b> · '
                . $e((string) ($o['nombre'] ?? '?')) . ' → ' . $e((string) ($d['nombre'] ?? '?'))
                . ' · <span class="' . ((string) $r['estado'] === 'resuelta' ? 'nv-pos' : 'nv-warn') . '">' . $e((string) $r['estado']) . '</span></div>'
                . '<div class="nv-mov-meta">víveres: ' . (int) $r['viveres_gastados'] . ' raciones' . ($grado > 0 ? ' · daño al barco: ' . ($grado === 3 ? 'grave' : ($grado === 2 ? 'moderado' : 'leve')) : '') . ' · IRT ' . (int) $r['irt'] . ' (solo-staff)</div>'
                . ($v['notas'] ? '<div class="nv-mov-meta">' . $e(implode(' · ', (array) $v['notas'])) . '</div>' : '')
                . '</div>';
        }
    }
    $html .= '</div></div>';

    return $html;
}

/**
 * Vencimiento de travesías (17.5): el plazo es el del tema
 * (fecha_real_apertura + tiempo_disponible_h). Sin cierre → vencida.
 * Idempotente; integrado en el cron (ope7_progresion_cron).
 */
function ope7_travesias_vencidas()
{
    global $db;
    if (!ope7_tabla_existe('travesias') || !ope7_tabla_existe('temas')) {
        return 0;
    }
    $now = TIME_NOW;
    $q = $db->query('SELECT tr.id, tr.tema_id FROM ' . ope7_tabla_full('travesias') . ' tr '
        . 'JOIN ' . ope7_tabla_full('temas') . ' t ON t.tid = tr.tema_id '
        . "WHERE tr.estado = 'en_travesia' AND t.estado = 'abierto' AND t.fecha_real_apertura > 0 "
        . 'AND (t.fecha_real_apertura + tr.tiempo_disponible_h * 3600) < ' . $now . ' LIMIT 100');
    $n = 0;
    while ($f = $db->fetch_array($q)) {
        $db->update_query('ope_travesias', array(
            'estado' => 'vencida',
            'veredicto' => json_encode(array(
                'tipo' => 'vencimiento',
                'nota' => 'Plazo agotado sin cierre: la travesía se resuelve por veredicto (desvío, retraso o incidente no jugado, 17.5).',
            ), JSON_UNESCAPED_UNICODE),
        ), 'id = ' . (int) $f['id']);
        $db->update_query('ope_temas', array('estado' => 'cerrado'), 'tid = ' . (int) $f['tema_id']);
        if (ope7_tabla_existe('temas_participantes')) {
            $db->update_query('ope_temas_participantes', array('salio_en' => $now), 'tema_id = ' . (int) $f['tema_id']);
        }
        $n++;
    }
    return $n;
}
