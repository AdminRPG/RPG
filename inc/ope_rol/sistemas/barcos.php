<?php
/**
 * One Piece: 7 Seas · Barcos y astillero (F4.3, 5.17 / cap. 18)
 * -------------------------------------------------------------
 * Trámites 39–44:
 *   39 · Compra/adquisición (ligero): primer barco gratis (bote de remos) o
 *       compra; verificación tipo/nivel/madera (18.4/18.5).
 *   40 · Construcción (ia, Astillero): oficio Carpintero + materiales (5.8).
 *   41 · Mejora N1→N2→N3 (ia, Astillero): diferencia de precio + madera.
 *   42 · Módulos instalar/quitar (ia): ranuras (18.6) y requisitos de oficio.
 *   43 · Reparación (ia, Astillero): grados de daño (18.7) con materiales.
 *   44 · Venta/desguace/baja (ligero): fuera de flota; hundimiento con
 *       veredicto → suceso de mundo.
 * El barco es un aliado sin PA propio ni progreso (18.1): las mejoras son
 * módulos y madera, nunca niveles de personaje. Sin azar: veredicto.
 */

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

/** Barco por id, con tipo y madera decodificados (ficha 18.2). */
function ope7_barco_por_id($id)
{
    global $db;
    $id = (int) $id;
    if ($id < 1 || !ope7_tabla_existe('barcos')) {
        return null;
    }
    $q = $db->simple_select('ope_barcos', '*', "id = {$id}", array('limit' => 1));
    $b = $db->fetch_array($q);
    if (!$b) {
        return null;
    }
    $b['tipo'] = null;
    if ((int) $b['tipo_id'] > 0 && ope7_tabla_existe('tipos_barcos')) {
        $tq = $db->simple_select('ope_tipos_barcos', '*', 'id = ' . (int) $b['tipo_id'], array('limit' => 1));
        $b['tipo'] = $db->fetch_array($tq);
    }
    $b['madera'] = null;
    if ((int) $b['madera_id'] > 0 && ope7_tabla_existe('maderas_casco')) {
        $mq = $db->simple_select('ope_maderas_casco', '*', 'id = ' . (int) $b['madera_id'], array('limit' => 1));
        $b['madera'] = $db->fetch_array($mq);
    }
    // Módulos instalados (JSON ranuras) → array.
    $b['modulos'] = json_decode((string) ($b['ranuras'] ?? '[]'), true);
    if (!is_array($b['modulos'])) {
        $b['modulos'] = array();
    }
    return $b;
}

/** Flota de un personaje (barcos activos o dañados, no hundidos/venta). */
function ope7_barco_flota($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('barcos')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_barcos', '*', "dueno_id = {$pid} AND estado NOT IN ('hundido','vendido')",
        array('order_by' => 'id', 'order_dir' => 'ASC'));
    while ($b = $db->fetch_array($q)) {
        $out[] = ope7_barco_por_id((int) $b['id']);
    }
    return $out;
}

/** Valor (casco, maniobra, ranuras, cañones) de un tipo en un nivel (18.4). */
function ope7_barco_tipo_valores($tipo, $nivel)
{
    $nivel = (string) $nivel === 'N2' ? 1 : ((string) $nivel === 'N3' ? 2 : 0);
    $out = array(
        'plazas'   => (int) (ope7_json_valor($tipo, 'plazas', $nivel) ?? 0),
        'casco'    => (int) (ope7_json_valor($tipo, 'casco', $nivel) ?? 0),
        'maniobra' => (int) (ope7_json_valor($tipo, 'maniobra', $nivel) ?? 0),
        'ranuras'  => (int) (ope7_json_valor($tipo, 'ranuras', $nivel) ?? 0),
        'canones'  => (int) (ope7_json_valor($tipo, 'canones', $nivel) ?? 0),
        'precio'   => (int) (ope7_json_valor($tipo, 'precios', $nivel, 'precio') ?? 0),
    );
    return $out;
}

/** Valor en el índice de un JSON del tipo (casco/maniobra/… por N). */
function ope7_json_valor($tipo, $campo, $nivel, $alternativo = '')
{
    $raw = (string) ($tipo[$campo] ?? '');
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        // Columna escalar (p.ej. `precio`): se usa como está.
        return isset($tipo[$campo]) ? $tipo[$campo] : null;
    }
    return isset($arr[$nivel]) ? $arr[$nivel] : null;
}

/**
 * Precio de la madera para un barco (18.5): el catálogo es para barco medio
 * (6–8 plazas) — botes/balandros/goletas pagan la mitad, corbetas el doble,
 * galeones el triple y acorazados cinco veces más; +25 % mano de obra del
 * astillero si el carpintero no es de tu tripulación.
 */
function ope7_barco_precio_madera($tipo, $madera, $pid = 0)
{
    $base = (int) ($madera['precio'] ?? 0);
    if ($base < 1) {
        return 0; // Pino de marea: incluida (la de serie).
    }
    $nombre = (string) ($tipo['nombre'] ?? '');
    if (in_array($nombre, array('Bote de remos', 'Balandro', 'Goleta'), true)) {
        $base = (int) round($base * 0.5);
    } elseif ($nombre === 'Corbeta de guerra') {
        $base *= 2;
    } elseif ($nombre === 'Galeón pesado') {
        $base *= 3;
    } elseif ($nombre === 'Acorazado insignia') {
        $base *= 5;
    }
    // Mano de obra: si el carpintero de la tripulación tiene Astillero, sin recargo.
    $pid = (int) $pid;
    $tiene_astillero = $pid > 0 && ope7_pj_dominio_nivel($pid, 'Carpintero') >= 1
        && ope7_pj_rama_dominio($pid, 'Carpintero') === 'Astillero';
    if (!$tiene_astillero) {
        $base = (int) round($base * 1.25);
    }
    return $base;
}

/** Nivel del personaje en la rama Astillero de Carpintero (0 si no). */
function ope7_pj_rama_dominio($pid, $nombre)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('dominios') || !ope7_tabla_existe('dominios_personaje')) {
        return '';
    }
    $q = $db->query('SELECT dp.rama FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
        . 'JOIN ' . ope7_tabla_full('dominios') . " d ON d.id = dp.dominio_id "
        . 'WHERE dp.personaje_id = ' . $pid . " AND d.nombre = '" . $db->escape_string((string) $nombre) . "' LIMIT 1");
    $r = $db->fetch_array($q);
    return $r ? (string) ($r['rama'] ?? '') : '';
}

/**
 * Espacio que ocupa un personaje a bordo (18.3): Tontatta 0 · Humana/Kuja/
 * Piernas Largas/Brazos Largos/Skypiean/Mink 1 · Gyojin/Sirena 0 en el agua
 * / 1 a bordo · Lunarian 2 · Oni 3 · Bucaner 3 · Gigante 5.
 */
function ope7_barco_espacio_raza($f)
{
    $raza = (string) ($f['raza_nombre'] ?? '');
    if (in_array($raza, array('Tontatta'), true)) {
        return 0;
    }
    if (in_array($raza, array('Lunarian'), true)) {
        return 2;
    }
    if (in_array($raza, array('Oni', 'Bucaner'), true)) {
        return 3;
    }
    if ($raza === 'Gigante') {
        return 5;
    }
    return 1;
}

/** ¿El PJ puede adquirir un acorazado? (D4.10, 18.4: patrimonio de imperio). */
function ope7_barco_puede_acorazado($pid)
{
    return ope7_conquista_puede_mandar_ejercito($pid); // cúspide o nv ≥ 30
}

/**
 * Crea un barco con su ficha calculada del tipo/nivel (18.2). Devuelve el id
 * o 0. `estado` según integridad; `pv_actual` = casco.
 */
function ope7_barco_crear($pid, $tipo_id, $nivel, $madera_id, $nombre)
{
    global $db;
    $pid = (int) $pid;
    $tipo_id = (int) $tipo_id;
    $madera_id = (int) $madera_id;
    $nivel = in_array((string) $nivel, array('N1', 'N2', 'N3'), true) ? (string) $nivel : 'N1';
    if (!ope7_tabla_existe('barcos') || !ope7_tabla_existe('tipos_barcos')) {
        return 0;
    }
    $q = $db->simple_select('ope_tipos_barcos', '*', "id = {$tipo_id}", array('limit' => 1));
    $tipo = $db->fetch_array($q);
    if (!$tipo) {
        return 0;
    }
    $v = ope7_barco_tipo_valores($tipo, $nivel);
    $id = (int) $db->insert_query('ope_barcos', array(
        'nombre'      => $db->escape_string(trim((string) $nombre)),
        'tipo_id'     => $tipo_id,
        'nivel'       => $nivel,
        'madera_id'   => $madera_id,
        'casco_pv'    => $v['casco'],
        'pv_actual'   => $v['casco'],
        'maniobra'    => $v['maniobra'],
        'armamento'   => json_encode(array('canones' => $v['canones'], 'danio' => ope7_barco_danio_canon($tipo)), JSON_UNESCAPED_UNICODE),
        'espacio_max' => $v['plazas'],
        'ranuras'     => json_encode(array()),
        'dueno_id'    => $pid,
        'estado'      => 'activo',
    ));
    return $id;
}

/** Daño por cañón del tipo (18.4: ×30–×150 según el tipo). */
function ope7_barco_danio_canon($tipo)
{
    $nombre = (string) ($tipo['nombre'] ?? '');
    $map = array('Bote de remos' => 0, 'Balandro' => 30, 'Goleta' => 40, 'Carabela' => 50,
                 'Velero' => 60, 'Corbeta de guerra' => 80, 'Galeón pesado' => 100, 'Acorazado insignia' => 150);
    return (int) ($map[$nombre] ?? 30);
}

/** Módulos instalados (array de nombres). */
function ope7_barco_modulos($barco)
{
    if (!$barco) {
        return array();
    }
    $m = json_decode((string) ($barco['ranuras'] ?? '[]'), true);
    return is_array($m) ? $m : array();
}

/** Módulo del catálogo por id. */
function ope7_modulo_barco_por_id($id)
{
    global $db;
    $id = (int) $id;
    if ($id < 1 || !ope7_tabla_existe('modulos_barcos')) {
        return null;
    }
    $q = $db->simple_select('ope_modulos_barcos', '*', "id = {$id}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? $r : null;
}

/** Catálogo de módulos de barco (para el panel y la instalación). */
function ope7_modulos_barcos_lista()
{
    global $db;
    if (!ope7_tabla_existe('modulos_barcos')) {
        return array();
    }
    $out = array();
    $q = $db->simple_select('ope_modulos_barcos', '*', '1=1', array('order_by' => 'precio', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Grado de daño actual de un barco (leve/moderado/grave) según PV. */
function ope7_barco_grado_danio($barco)
{
    $casco = max(1, (int) ($barco['casco_pv'] ?? 1));
    $pv = (int) ($barco['pv_actual'] ?? $casco);
    $pct = $pv / $casco;
    if ($pct >= 0.75) {
        return '';
    }
    if ($pct >= 0.5) {
        return 'leve';
    }
    if ($pct >= 0.25) {
        return 'moderado';
    }
    return 'grave';
}

/** Aplica un daño (leve/moderado/grave) al casco: −10/25/50 % PV (18.7). */
function ope7_barco_aplicar_danio($barco_id, $grado)
{
    global $db;
    $barco_id = (int) $barco_id;
    $grado = in_array((string) $grado, array('leve', 'moderado', 'grave'), true) ? (string) $grado : 'leve';
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return array('ok' => false, 'msg' => 'Barco no encontrado.');
    }
    $perdida = array('leve' => 10, 'moderado' => 25, 'grave' => 50);
    $casco = max(1, (int) $b['casco_pv']);
    $pv = max(0, (int) $b['pv_actual'] - (int) round($casco * $perdida[$grado] / 100));
    $estado = $pv <= 0 ? 'hundido' : 'danado_' . $grado;
    $db->update_query('ope_barcos', array('pv_actual' => $pv, 'estado' => $estado), "id = {$barco_id}");
    return array('ok' => true, 'pv' => $pv, 'estado' => $estado, 'msg' => 'Barco dañado: ' . $grado . ' (PV ' . $pv . '/' . $casco . ').');
}

/**
 * Reparación (18.7): con Carpintero (rama Astillero) y materiales (madera
 * 5.8) se recupera integridad; log en `reparaciones` con materiales y coste.
 */
function ope7_barco_reparar($barco_id, $pid, $grado = '')
{
    global $db;
    $barco_id = (int) $barco_id;
    $pid = (int) $pid;
    if (!ope7_tabla_existe('reparaciones')) {
        return array('ok' => false, 'msg' => 'Reparaciones no migradas (pendiente).');
    }
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return array('ok' => false, 'msg' => 'Barco no encontrado.');
    }
    if ((int) $b['dueno_id'] !== $pid) {
        return array('ok' => false, 'msg' => 'Solo el dueño repara su barco (18.7).');
    }
    $astillero = ope7_pj_dominio_nivel($pid, 'Carpintero');
    if ($astillero < 1 || ope7_pj_rama_dominio($pid, 'Carpintero') !== 'Astillero') {
        return array('ok' => false, 'msg' => 'Reparar exige el oficio Carpintero con la rama Astillero (18.7, 5.3).');
    }
    $grado = (string) $grado === '' ? ope7_barco_grado_danio($b) : (string) $grado;
    if ($grado === '') {
        return array('ok' => false, 'msg' => 'El barco no tiene daños que reparar.');
    }
    // Coste en materiales: madera del catálogo por el grado (5.8).
    $madera = $b['madera'] ? $b['madera'] : array('precio' => 0, 'nombre' => 'Pino de marea');
    $factor = array('leve' => 1, 'moderado' => 2, 'grave' => 3);
    $coste = (int) ($madera['precio'] ?? 0) * $factor[$grado];
    if ($coste > 0) {
        $mov = ope7_cartera_mover($pid, 'cartera', -$coste);
        if (!$mov['ok']) {
            return array('ok' => false, 'msg' => 'Materiales insuficientes: reparar ' . $grado . ' cuesta ' . number_format($coste, 0, ',', '.') . ' ฿ en madera (5.8).');
        }
    }
    // Recupera a 100 % del casco (reparación completa del grado).
    $casco = (int) $b['casco_pv'];
    $db->update_query('ope_barcos', array('pv_actual' => $casco, 'estado' => 'activo'), "id = {$barco_id}");
    $db->insert_query('ope_reparaciones', array(
        'barco_id'   => $barco_id,
        'grado'      => $grado,
        'materiales' => json_encode(array('madera' => (string) ($madera['nombre'] ?? ''), 'coste' => $coste), JSON_UNESCAPED_UNICODE),
        'coste'      => $coste,
        'oficio'     => 'Carpintero/Astillero',
        'veredicto'  => json_encode(array('pv' => $casco, 'estado' => 'activo'), JSON_UNESCAPED_UNICODE),
        'fecha'      => TIME_NOW,
    ));
    return array('ok' => true, 'msg' => 'Barco reparado (' . $grado . ' → activo, PV ' . $casco . ') · log en reparaciones · coste ' . number_format($coste, 0, ',', '.') . ' ฿.');
}

/**
 * Venta/desguace (44, D4.9): venta al 50 % del valor de compra (precio del
 * nivel actual del tipo); desguace entrega el 50 % en madera (cartera, a
 * precio de catálogo de la madera). El barco sale de flota.
 */
function ope7_barco_vender($barco_id, $pid, $modo = 'venta')
{
    global $db;
    $barco_id = (int) $barco_id;
    $pid = (int) $pid;
    $modo = (string) $modo === 'desguace' ? 'desguace' : 'venta';
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return array('ok' => false, 'msg' => 'Barco no encontrado.');
    }
    if ((int) $b['dueno_id'] !== $pid) {
        return array('ok' => false, 'msg' => 'Solo el dueño vende su barco (18.7).');
    }
    $tipo = $b['tipo'];
    $v = $tipo ? ope7_barco_tipo_valores($tipo, (string) $b['nivel']) : array('precio' => 0);
    $valor = (int) ($v['precio'] ?? 0);
    $ingreso = (int) round($valor * 0.5); // D4.9: 50 % del valor de compra.
    if ($modo === 'desguace') {
        $madera_precio = (int) ($b['madera']['precio'] ?? 0);
        $ingreso = (int) round($madera_precio * 0.5); // la mitad en madera (material)
        $concepto = 'Desguace del ' . (string) ($tipo['nombre'] ?? 'barco') . ' — materiales de madera (D4.9).';
    } else {
        $concepto = 'Venta del ' . (string) ($tipo['nombre'] ?? 'barco') . ' al 50 % (D4.9).';
    }
    if ($ingreso > 0) {
        ope7_cartera_mover($pid, 'cartera', $ingreso);
    }
    $db->update_query('ope_barcos', array('estado' => 'vendido'), "id = {$barco_id}");
    return array('ok' => true, 'msg' => ($modo === 'venta' ? 'Barco vendido' : 'Barco desguazado') . ': +' . number_format($ingreso, 0, ',', '.') . ' ฿ (' . $concepto . ')');
}

/** Hundimiento (18.7): veredicto → suceso de mundo + aviso de transporte. */
function ope7_barco_hundir($barco_id, $motivo, $ronda = 0)
{
    global $db;
    $barco_id = (int) $barco_id;
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return false;
    }
    $db->update_query('ope_barcos', array('estado' => 'hundido', 'pv_actual' => 0), "id = {$barco_id}");
    // Suceso de mundo (18.8): el hundimiento es noticia.
    if (ope7_tabla_existe('sucesos')) {
        $db->insert_query('ope_sucesos', array(
            'isla_id'     => null,
            'ronda'       => (int) $ronda,
            'tipo'        => 'naufragio',
            'titulo'      => 'Naufragio: ' . (string) ($b['nombre'] ?? ''),
            'descripcion' => (string) $motivo,
            'impacto'     => json_encode(array('F_suceso' => 1), JSON_UNESCAPED_UNICODE),
            'activo'      => 1,
        ));
    }
    return true;
}

// ─────────────────────────────────────────────────────────────
// Efectos de los trámites 39–44
// ─────────────────────────────────────────────────────────────

/**
 * Efecto 39 · Compra/adquisición (18.4/18.5, ligero): primer barco gratis
 * (bote de remos — o una pinaza mejorada si la historia lo justifica);
 * después, compra con el precio N1 del tipo + madera. Verifica tipo, madera
 * mínima y el acceso a navíos de guerra (18.4, D4.10).
 */
function ope7_efecto_comprar_barco($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    if (!ope7_tabla_existe('barcos') || !ope7_tabla_existe('tipos_barcos')) {
        return 'Compra BLOQUEADA: barcos no migrados (pendiente).';
    }
    $tipo_id = (int) ($res['tipo_id'] ?? 0);
    $madera_id = (int) ($res['madera_id'] ?? 0);
    $nombre = trim((string) ($res['nombre'] ?? ''));
    if ($tipo_id < 1 || $nombre === '') {
        return 'Compra BLOQUEADA: elige el tipo de barco y dale nombre (18.4).';
    }
    $q = $db->simple_select('ope_tipos_barcos', '*', "id = {$tipo_id}", array('limit' => 1));
    $tipo = $db->fetch_array($q);
    if (!$tipo) {
        return 'Compra BLOQUEADA: tipo de barco no catalogado (18.4).';
    }
    // Madera: por defecto la mínima del tipo (18.5).
    if ($madera_id < 1) {
        $min = (string) ($tipo['madera_minima'] ?? 'Pino de marea');
        $mq = $db->simple_select('ope_maderas_casco', 'id', "nombre = '" . $db->escape_string($min) . "'", array('limit' => 1));
        $madera_id = (int) $db->fetch_field($mq, 'id');
    }
    $mq = $db->simple_select('ope_maderas_casco', '*', "id = {$madera_id}", array('limit' => 1));
    $madera = $db->fetch_array($mq);
    if (!$madera) {
        return 'Compra BLOQUEADA: madera no catalogada (18.5).';
    }
    // El tipo exige una madera mínima (18.4): la elegida debe habilitar el mar
    // del tipo. `madera_minima` es el NOMBRE de la madera del catálogo (18.5),
    // no un mar: se resuelve contra maderas_casco para su nivel de mar.
    $mad_max = ope7_nav_madera_max_nivel($madera);
    $tipo_min = 0;
    if ((string) ($tipo['madera_minima'] ?? '') !== '') {
        $tmq = $db->simple_select('ope_maderas_casco', '*', "nombre = '" . $db->escape_string((string) $tipo['madera_minima']) . "'", array('limit' => 1));
        $tm = $db->fetch_array($tmq);
        $tipo_min = $tm ? ope7_nav_madera_max_nivel($tm) : 0;
    }
    if ($tipo_min > 0 && $mad_max < $tipo_min) {
        return 'Compra BLOQUEADA: el tipo exige al menos ' . (string) ($tipo['madera_minima'] ?? '?') . ' y la madera elegida es ' . (string) $madera['nombre'] . ' (18.5).';
    }
    // Acceso a navíos de guerra (18.4): acorazado solo con imperio (D4.10).
    if ((int) ($tipo['es_faccion_npc'] ?? 0) === 1 && !ope7_barco_puede_acorazado($pid)) {
        return 'Compra BLOQUEADA: el ' . (string) $tipo['nombre'] . ' es patrimonio de facciones e imperios (18.4 — D4.10).';
    }
    // Primer barco gratis: bote de remos (18.4) o el que justifique su historia.
    $flota = ope7_barco_flota($pid);
    $primero_gratis = count($flota) === 0;
    $v = ope7_barco_tipo_valores($tipo, 'N1');
    $precio_barco = (int) ($v['precio'] ?? 0);
    $precio_madera = ope7_barco_precio_madera($tipo, $madera, $pid);
    $gratis = $primero_gratis && $precio_barco <= 0 && $precio_madera <= 0;
    $total = $precio_barco + $precio_madera;
    if (!$gratis && $total > 0) {
        $mov = ope7_cartera_mover($pid, 'cartera', -$total);
        if (!$mov['ok']) {
            return 'Compra BLOQUEADA: saldo insuficiente (' . number_format($total, 0, ',', '.') . ' ฿: barco ' . number_format($precio_barco, 0, ',', '.') . ' + madera ' . number_format($precio_madera, 0, ',', '.') . ').';
        }
    }
    $id = ope7_barco_crear($pid, $tipo_id, 'N1', $madera_id, $nombre);
    if ($id < 1) {
        return 'Compra BLOQUEADA: no se pudo crear el barco.';
    }
    return 'Barco adquirido: ' . $nombre . ' (' . (string) $tipo['nombre'] . ' N1 · casco ' . (string) $tipo['casco'] . ' · ' . (string) $madera['nombre'] . ')'
        . ($gratis ? ' — primer barco gratis (18.4).' : ' — ' . number_format($total, 0, ',', '.') . ' ฿ pagados (barco + madera).');
}

/**
 * Efecto 40 · Construcción (Astillero, 18.4): oficio Carpintero con rama
 * Astillero + materiales (madera 5.8). Construye a N1.
 */
function ope7_efecto_construir_barco($tr, $pid, $res)
{
    $pid = (int) $pid;
    $astillero = ope7_pj_dominio_nivel($pid, 'Carpintero');
    if ($astillero < 1 || ope7_pj_rama_dominio($pid, 'Carpintero') !== 'Astillero') {
        return 'Construcción BLOQUEADA: exige el oficio Carpintero con la rama Astillero (18.4, 5.3).';
    }
    $msg = ope7_efecto_comprar_barco($tr, $pid, $res);
    if (strpos((string) $msg, 'Barco adquirido') === 0) {
        // La IA firmó el veredicto de construcción; el barco se construye a N1.
        return 'Construido por el astillero: ' . $msg;
    }
    return $msg;
}

/**
 * Efecto 41 · Mejora N1→N2→N3 (18.4): la construye el Astillero por la
 * diferencia de precio + materiales (madera 5.8). Actualiza la ficha.
 */
function ope7_efecto_mejorar_barco($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $barco_id = (int) ($res['barco_id'] ?? 0);
    $nivel_nuevo = (string) ($res['nivel'] ?? '');
    if (!in_array($nivel_nuevo, array('N2', 'N3'), true)) {
        return 'Mejora BLOQUEADA: elige el nivel objetivo (N2 o N3).';
    }
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return 'Mejora BLOQUEADA: barco no encontrado.';
    }
    if ((int) $b['dueno_id'] !== $pid) {
        return 'Mejora BLOQUEADA: solo el dueño mejora su barco (18.4).';
    }
    $astillero = ope7_pj_dominio_nivel($pid, 'Carpintero');
    if ($astillero < 1 || ope7_pj_rama_dominio($pid, 'Carpintero') !== 'Astillero') {
        return 'Mejora BLOQUEADA: exige el oficio Carpintero con la rama Astillero (18.4, 5.3).';
    }
    $orden = array('N1' => 0, 'N2' => 1, 'N3' => 2);
    $actual = (string) $b['nivel'];
    if ($orden[$nivel_nuevo] <= $orden[$actual]) {
        return 'Mejora BLOQUEADA: solo se mejora N1→N2→N3 (sin saltos hacia atrás).';
    }
    if ($orden[$nivel_nuevo] - $orden[$actual] > 1) {
        return 'Mejora BLOQUEADA: la mejora es un paso a la vez (N1→N2→N3, 18.4).';
    }
    $tipo = $b['tipo'];
    $v_actual = ope7_barco_tipo_valores($tipo, $actual);
    $v_nuevo = ope7_barco_tipo_valores($tipo, $nivel_nuevo);
    $diferencia = max(0, (int) $v_nuevo['precio'] - (int) $v_actual['precio']);
    $madera = $b['madera'] ? $b['madera'] : array('precio' => 0);
    $precio_madera = ope7_barco_precio_madera($tipo, $madera, $pid);
    $total = $diferencia + $precio_madera;
    if ($total > 0) {
        $mov = ope7_cartera_mover($pid, 'cartera', -$total);
        if (!$mov['ok']) {
            return 'Mejora BLOQUEADA: saldo insuficiente (' . number_format($total, 0, ',', '.') . ' ฿: diferencia ' . number_format($diferencia, 0, ',', '.') . ' + madera ' . number_format($precio_madera, 0, ',', '.') . ').';
        }
    }
    $db->update_query('ope_barcos', array(
        'nivel'       => $nivel_nuevo,
        'casco_pv'    => $v_nuevo['casco'],
        'pv_actual'   => $v_nuevo['casco'],
        'maniobra'    => $v_nuevo['maniobra'],
        'espacio_max' => $v_nuevo['plazas'],
        'armamento'   => json_encode(array('canones' => $v_nuevo['canones'], 'danio' => ope7_barco_danio_canon($tipo)), JSON_UNESCAPED_UNICODE),
    ), "id = {$barco_id}");
    return 'Barco mejorado: ' . (string) $b['nombre'] . ' → ' . $nivel_nuevo . ' (casco ' . $v_nuevo['casco'] . ' · ranuras ' . $v_nuevo['ranuras'] . ') · ' . number_format($total, 0, ',', '.') . ' ฿ (diferencia + madera).';
}

/**
 * Efecto 42 · Módulos instalar/quitar (18.6): cada módulo ocupa 1 ranura;
 * se instala/desinstala con oficio. El efecto del módulo calibrado contra el
 * catálogo (los personalizados del Carpintero pasan por 5.7).
 */
function ope7_efecto_modulo_barco($tr, $pid, $res)
{
    global $db;
    $pid = (int) $pid;
    $barco_id = (int) ($res['barco_id'] ?? 0);
    $modulo_id = (int) ($res['modulo_id'] ?? 0);
    $accion = (string) ($res['accion'] ?? '') === 'quitar' ? 'quitar' : 'instalar';
    $b = ope7_barco_por_id($barco_id);
    if (!$b) {
        return 'Módulo BLOQUEADO: barco no encontrado.';
    }
    if ((int) $b['dueno_id'] !== $pid) {
        return 'Módulo BLOQUEADO: solo el dueño instala módulos (18.6).';
    }
    $modulo = ope7_modulo_barco_por_id($modulo_id);
    if (!$modulo) {
        return 'Módulo BLOQUEADO: módulo no catalogado (18.6).';
    }
    $instalados = ope7_barco_modulos($b);
    $max_ranuras = (int) ($b['tipo'] ? ope7_barco_tipo_valores($b['tipo'], (string) $b['nivel'])['ranuras'] : 0);
    if ($accion === 'instalar') {
        if (in_array((string) $modulo['nombre'], $instalados, true)) {
            return 'Módulo BLOQUEADO: ya está instalado (18.6).';
        }
        if (count($instalados) >= $max_ranuras) {
            return 'Módulo BLOQUEADO: sin ranuras libres (' . $max_ranuras . ' máx para ' . (string) $b['tipo']['nombre'] . ' ' . (string) $b['nivel'] . ', 18.6).';
        }
        // Requisito de oficio (18.6): tienda→Comerciante · resina→Astillero nv4
        // · kairoseki→Mercado Negro · el resto Astillero/Maquinista/Carpintero.
        $req = (string) ($modulo['requisito_oficio'] ?? '');
        if ($req !== '' && !ope7_barco_oficio_cumple($pid, $req)) {
            return 'Módulo BLOQUEADO: requisito de oficio «' . $req . '» (18.6).';
        }
        $precio = (int) ($modulo['precio'] ?? 0);
        if ($precio > 0) {
            $mov = ope7_cartera_mover($pid, 'cartera', -$precio);
            if (!$mov['ok']) {
                return 'Módulo BLOQUEADO: saldo insuficiente (' . number_format($precio, 0, ',', '.') . ' ฿).';
            }
        }
        $instalados[] = (string) $modulo['nombre'];
        $db->update_query('ope_barcos', array('ranuras' => json_encode($instalados, JSON_UNESCAPED_UNICODE)), "id = {$barco_id}");
        return 'Módulo instalado: ' . (string) $modulo['nombre'] . ' (' . count($instalados) . '/' . $max_ranuras . ' ranuras) · ' . number_format($precio, 0, ',', '.') . ' ฿.';
    }
    // Quitar: libera la ranura (los personalizados del Carpintero también).
    $idx = array_search((string) $modulo['nombre'], $instalados, true);
    if ($idx === false) {
        return 'Módulo BLOQUEADO: ese módulo no está instalado.';
    }
    unset($instalados[$idx]);
    $db->update_query('ope_barcos', array('ranuras' => json_encode(array_values($instalados), JSON_UNESCAPED_UNICODE)), "id = {$barco_id}");
    return 'Módulo retirado: ' . (string) $modulo['nombre'] . ' (ranura liberada).';
}

/** ¿El PJ cumple el requisito de oficio del módulo? (18.6). */
function ope7_barco_oficio_cumple($pid, $req)
{
    $req = trim((string) $req);
    if ($req === '') {
        return true;
    }
    if ($req === 'Astillero nv4') {
        return ope7_pj_dominio_nivel($pid, 'Carpintero') >= 4 && ope7_pj_rama_dominio($pid, 'Carpintero') === 'Astillero';
    }
    if ($req === 'Mercado Negro') {
        // Acceso al bajo mundo: facción o la red (5.13). Simplificación: facción.
        return in_array(ope7_pj_faccion_nombre($pid), array('Bajo Mundo', 'Piratas', 'Revolucionarios'), true);
    }
    if ($req === 'Comerciante' || $req === 'Carpintero' || $req === 'Cocinero'
        || $req === 'Químico' || $req === 'Médico' || $req === 'Maquinista Naval') {
        return ope7_pj_dominio_nivel($pid, $req) >= 1;
    }
    if ($req === 'Astillero') {
        return ope7_pj_dominio_nivel($pid, 'Carpintero') >= 1 && ope7_pj_rama_dominio($pid, 'Carpintero') === 'Astillero';
    }
    return true;
}

/**
 * Efecto 43 · Reparación (18.7): grados de daño con oficio Carpintero
 * (Astillero) y materiales; log en `reparaciones`.
 */
function ope7_efecto_reparar_barco($tr, $pid, $res)
{
    $r = ope7_barco_reparar((int) ($res['barco_id'] ?? 0), (int) $pid, (string) ($res['grado'] ?? ''));
    return $r['ok'] ? 'Reparación: ' . $r['msg'] : $r['msg'];
}

/**
 * Efecto 44 · Venta/desguace/baja (18.7, D4.9): el barco sale de flota.
 * Hundimiento por veredicto (5.10/5.14) → suceso de mundo.
 */
function ope7_efecto_vender_barco($tr, $pid, $res)
{
    $modo = (string) ($res['modo'] ?? '') === 'desguace' ? 'desguace' : 'venta';
    $r = ope7_barco_vender((int) ($res['barco_id'] ?? 0), (int) $pid, $modo);
    return $r['ok'] ? $r['msg'] : $r['msg'];
}

// ─────────────────────────────────────────────────────────────
// Panel staff «Barcos» (Anexo A.3, 18.7)
// ─────────────────────────────────────────────────────────────

/** Panel «Barcos»: flota por jugador, estados de daño, astillero y módulos. */
function ope7_barcos_panel_html()
{
    global $db;
    $h = array();
    $h[] = '<div class="shead"><h1>Barcos y astillero</h1><span class="sub">A.3 · 18.x — flota, daños y mejoras</span></div>';
    $h[] = '<p class="zs-intro">El barco es un aliado sin PA propio ni progreso (18.1): las mejoras son <b>módulos y madera</b>, nunca niveles. La madera del casco marca el límite de mar (18.5) y la navegación (17.2) lo verifica. Reparar y mejorar exige Carpintero (rama Astillero); los veredictos de daño/hundimiento se firman aquí.</p>';

    // Flota por jugador.
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Flota</span><span class="c">por jugador</span></div><div class="plate-b">';
    $q = $db->query('SELECT b.id, b.nombre, b.nivel, b.estado, b.pv_actual, b.casco_pv, b.maniobra, b.espacio_max, '
        . 't.nombre AS tipo_nombre, m.nombre AS madera_nombre, p.nombre AS pj_nombre, '
        . 'JSON_LENGTH(b.ranuras) AS n_modulos '
        . 'FROM ' . ope7_tabla_full('barcos') . ' b '
        . 'JOIN ' . ope7_tabla_full('tipos_barcos') . ' t ON t.id = b.tipo_id '
        . 'JOIN ' . ope7_tabla_full('maderas_casco') . ' m ON m.id = b.madera_id '
        . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = b.dueno_id '
        . 'ORDER BY p.nombre, b.id');
    $filas = 0;
    if ($db->num_rows($q) === 0) {
        $h[] = '<p class="pj-empty">Sin barcos registrados. El primer barco (bote de remos) es gratis (18.4).</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Dueño</th><th>Barco</th><th>Tipo</th><th>Nivel</th><th>Madera</th><th>Casco</th><th>Estado</th><th>Módulos</th></tr></thead><tbody>';
        while ($r = $db->fetch_array($q)) {
            $grado = ope7_barco_grado_danio(array('casco_pv' => $r['casco_pv'], 'pv_actual' => $r['pv_actual']));
            $estado = (string) $r['estado'] . ($grado !== '' && (string) $r['estado'] === 'activo' ? '' : '');
            if ((string) $r['estado'] === 'activo' && $grado !== '') {
                $estado = 'activo (' . $grado . ')';
            }
            $h[] = '<tr><td>' . htmlspecialchars_uni((string) $r['pj_nombre']) . '</td>'
                . '<td><b>' . htmlspecialchars_uni((string) $r['nombre']) . '</b></td>'
                . '<td>' . htmlspecialchars_uni((string) $r['tipo_nombre']) . '</td>'
                . '<td>' . (string) $r['nivel'] . '</td>'
                . '<td>' . htmlspecialchars_uni((string) $r['madera_nombre']) . '</td>'
                . '<td>' . (int) $r['pv_actual'] . '/' . (int) $r['casco_pv'] . '</td>'
                . '<td>' . htmlspecialchars_uni($estado) . '</td>'
                . '<td>' . (int) ($r['n_modulos'] ?? 0) . '</td></tr>';
            $filas++;
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    // Catálogo de módulos (18.6) con requisitos.
    $modulos = ope7_modulos_barcos_lista();
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Módulos del catálogo</span><span class="c">10 · 1 ranura c/u</span></div><div class="plate-b">';
    if (!$modulos) {
        $h[] = '<p class="pj-empty">Sin módulos sembrados (pendiente).</p>';
    } else {
        $h[] = '<table class="zs-tab"><thead><tr><th>Módulo</th><th>Efecto</th><th>Coste</th><th>Requisito</th></tr></thead><tbody>';
        foreach ($modulos as $m) {
            $efecto = json_decode((string) ($m['efecto'] ?? '{}'), true);
            $texto = is_array($efecto) ? (string) ($efecto['texto'] ?? (string) $m['efecto']) : (string) $m['efecto'];
            $h[] = '<tr><td><b>' . htmlspecialchars_uni((string) $m['nombre']) . '</b></td>'
                . '<td>' . htmlspecialchars_uni($texto) . '</td>'
                . '<td>' . number_format((int) $m['precio'], 0, ',', '.') . ' ฿</td>'
                . '<td>' . htmlspecialchars_uni((string) ($m['requisito_oficio'] ?? '')) . '</td></tr>';
        }
        $h[] = '</tbody></table>';
    }
    $h[] = '</div></div>';

    // Reparaciones recientes (18.7).
    if (ope7_tabla_existe('reparaciones')) {
        $h[] = '<div class="plate"><div class="plate-h"><span class="t">Reparaciones</span><span class="c">log con materiales</span></div><div class="plate-b">';
        $q = $db->query('SELECT r.grado, r.coste, r.fecha, b.nombre AS barco_nombre '
            . 'FROM ' . ope7_tabla_full('reparaciones') . ' r '
            . 'JOIN ' . ope7_tabla_full('barcos') . ' b ON b.id = r.barco_id '
            . 'ORDER BY r.id DESC LIMIT 10');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin reparaciones registradas.</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Barco</th><th>Grado</th><th>Coste</th><th>Fecha</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . htmlspecialchars_uni((string) $r['barco_nombre']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['grado']) . '</td>'
                    . '<td>' . number_format((int) $r['coste'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . date('d/m/Y', (int) $r['fecha']) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
        $h[] = '</div></div>';
    }

    $h[] = '<p class="zs-intro"><b>Flujo:</b> compra (39, primer barco gratis) · construcción (40, Astillero) · mejora N1→N3 (41, diferencia + madera) · módulos (42, ranuras y oficios) · reparación (43, grados con materiales) · venta/desguace (44, 50 %, D4.9).</p>';
    return implode("\n", $h);
}
