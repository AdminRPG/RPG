<?php
if (!defined('IN_MYBB')) { die('Direct initialization of this file is not allowed.'); }

/**
 * Reglas de creación de personaje — capa de DOMINIO / USE-CASE.
 * ------------------------------------------------------------
 * Funciones PURAS: reciben input (arrays), devuelven errores + selección
 * normalizada. No tocan BD ni imprimen HTML. Testeables por CLI
 * (scripts/_test-factor-linaje.php).
 *
 * Modelo Factor Linaje: TODO se compra con Puntos de Linaje (PL) a suma cero.
 * Canon: Eternal-Sistema/docs/01-PERSONAJE/FACTOR-LINAJE.md.
 *
 * Depende de la capa de datos: inc/ope_rol/catalogos/linaje.php.
 */

/**
 * Reglas interinas de compatibilidad de híbridos (matriz completa = Fase 3).
 * v1: bloqueo por diferencia de Categoría de Tamaño ≥ 3 (§2.3) y marca de
 * "Laboratorio/Anomalía" (§2.2) para mezclas con sangre Lunarian o Mink.
 *
 * @return array{ok:bool, motivo:string, requiere_experimento:bool}
 */
function ope_pj_hibridacion($lin_a, $lin_b)
{
    $out = array('ok' => true, 'motivo' => '', 'requiere_experimento' => false);

    if ($lin_a === '' || $lin_b === '' || $lin_a === $lin_b) {
        return array('ok' => false, 'motivo' => 'Un híbrido combina dos linajes distintos.', 'requiere_experimento' => false);
    }

    $razas = ope_rol_razas();
    if (!isset($razas[$lin_a]) || !isset($razas[$lin_b])) {
        return array('ok' => false, 'motivo' => 'Linaje de híbrido no válido.', 'requiere_experimento' => false);
    }

    // Bloqueo por escala (§2.3): diferencia de categoría de tamaño ≥ 3.
    $tam = ope_rol_linaje_tamano_idx();
    $da = isset($tam[$lin_a]) ? $tam[$lin_a] : 2;
    $db = isset($tam[$lin_b]) ? $tam[$lin_b] : 2;
    if (abs($da - $db) >= 3) {
        return array(
            'ok' => false,
            'motivo' => 'Incompatibilidad física: la diferencia de tamaño entre ' . $razas[$lin_a]['nombre'] . ' y ' . $razas[$lin_b]['nombre'] . ' es demasiado grande.',
            'requiere_experimento' => false,
        );
    }

    // Laboratorio/Anomalía (§2.2): sangre Lunarian o Mink en mezcla → requiere
    // el rasgo "Experimento / Anomalía".
    $lab = array('lunarians', 'minks');
    if (in_array($lin_a, $lab, true) || in_array($lin_b, $lab, true)) {
        $out['requiere_experimento'] = true;
        $out['motivo'] = 'Mezcla de Laboratorio/Anomalía: requiere el rasgo "Experimento / Anomalía".';
    }

    return $out;
}

/**
 * Índice id-de-rasgo-racial => id-de-linaje, restringido a los linajes dados.
 */
function ope_pj_mapa_raciales(array $linajes)
{
    $todos = ope_rol_rasgos_raciales();
    $map = array();
    foreach ($linajes as $lin) {
        if (!isset($todos[$lin])) { continue; }
        foreach ($todos[$lin] as $rid => $r) {
            $map[$rid] = array('linaje' => $lin) + $r;
        }
    }
    return $map;
}

/** Índice id-de-dote-innata => datos, restringido a los linajes dados. */
function ope_pj_mapa_dotes_innatas(array $linajes)
{
    $todas = ope_rol_dotes_innatas();
    $map = array();
    foreach ($linajes as $lin) {
        if (isset($todas[$lin])) {
            $map[$todas[$lin]['id']] = array('linaje' => $lin) + $todas[$lin];
        }
    }
    return $map;
}

/** Aplana el catálogo de Rasgos Generales (todas las categorías) a id => datos. */
function ope_pj_rasgos_generales_flat()
{
    $flat = array();
    foreach (ope_rol_rasgos_generales() as $cat => $items) {
        foreach ($items as $id => $r) {
            $flat[$id] = $r + array('categoria' => $cat);
        }
    }
    return $flat;
}

/**
 * Valida la compra del Factor Linaje (suma cero PL, acceso por linaje, pureza/
 * hibridación) y normaliza la selección para persistir.
 *
 * @param array $in {
 *   pureza: 'pura'|'hibrida',
 *   linaje: string, linaje2: string,
 *   rasgos_generales: string[], rasgos_raciales: string[],
 *   rasgo_puro: bool|string, dotes_innatas: string[],
 *   defectos: string[], defectos_hibridacion: string[],
 *   specs: array<string,string>  // detalle libre por id
 * }
 * @return array{errores:string[], seleccion:array, pl_total:int, linajes:string[], pureza:string}
 */
function ope_pj_validar_factor_linaje(array $in)
{
    $errores = array();
    $seleccion = array();
    $pl_total = 0;

    $pureza = ($in['pureza'] ?? 'pura') === 'hibrida' ? 'hibrida' : 'pura';
    $lin1 = (string)($in['linaje'] ?? '');
    $lin2 = (string)($in['linaje2'] ?? '');
    $specs = isset($in['specs']) && is_array($in['specs']) ? $in['specs'] : array();

    $razas = ope_rol_razas();
    if (!isset($razas[$lin1])) {
        $errores[] = 'Elige un linaje válido.';
    }

    $linajes = array($lin1);
    if ($pureza === 'hibrida') {
        $hib = ope_pj_hibridacion($lin1, $lin2);
        if (!$hib['ok']) {
            $errores[] = $hib['motivo'];
        } else {
            $linajes[] = $lin2;
        }
    }
    $linajes = array_values(array_filter(array_unique($linajes), function ($l) use ($razas) {
        return isset($razas[$l]);
    }));

    $arr = function ($k) use ($in) {
        return isset($in[$k]) && is_array($in[$k]) ? array_values(array_unique($in[$k])) : array();
    };

    // --- Rasgos Generales (§4.3) ---
    $gen = ope_pj_rasgos_generales_flat();
    $sel_generales = $arr('rasgos_generales');
    foreach ($sel_generales as $id) {
        if (!isset($gen[$id])) { $errores[] = 'Rasgo general no válido: ' . $id; continue; }
        $r = $gen[$id];
        $spec = ope_pj_spec($r, $specs, $id, $errores, 'rasgo');
        $seleccion[$id] = array('id' => $id, 'nombre' => $r['nombre'], 'valor' => (int)$r['pl'], 'tipo' => 'rasgo_general', 'spec' => $spec);
        $pl_total += (int)$r['pl'];
    }

    // --- Rasgos Raciales (§3.3), con acceso por linaje ---
    $map_rac = ope_pj_mapa_raciales($linajes);
    $sel_raciales = $arr('rasgos_raciales');
    foreach ($sel_raciales as $id) {
        if (!isset($map_rac[$id])) {
            $errores[] = 'Rasgo racial fuera de tu linaje: ' . $id;
            continue;
        }
        $r = $map_rac[$id];
        if (!empty($r['req']) && !in_array($r['req'], $sel_raciales, true)) {
            $errores[] = 'El rasgo "' . $r['nombre'] . '" requiere otro rasgo racial previo.';
        }
        $spec = ope_pj_spec($r, $specs, $id, $errores, 'rasgo');
        $seleccion[$id] = array('id' => $id, 'nombre' => $r['nombre'], 'valor' => (int)$r['pl'], 'tipo' => 'rasgo_racial', 'linaje' => $r['linaje'], 'spec' => $spec);
        $pl_total += (int)$r['pl'];
    }

    // --- Rasgo Puro (§3.4): solo Sangre Pura ---
    $puro_in = $in['rasgo_puro'] ?? '';
    if ($puro_in) {
        if ($pureza !== 'pura') {
            $errores[] = 'Solo la Sangre Pura puede comprar un Rasgo Puro.';
        } else {
            $puros = ope_rol_rasgo_puro();
            if (isset($puros[$lin1])) {
                $p = $puros[$lin1];
                $seleccion[$p['id']] = array('id' => $p['id'], 'nombre' => $p['nombre'], 'valor' => (int)$p['pl'], 'tipo' => 'rasgo_puro', 'linaje' => $lin1, 'spec' => '');
                $pl_total += (int)$p['pl'];
            }
        }
    }

    // --- Dotes Innatas (§6), opcionales, acceso por linaje ---
    $map_dote = ope_pj_mapa_dotes_innatas($linajes);
    foreach ($arr('dotes_innatas') as $id) {
        if (!isset($map_dote[$id])) {
            $errores[] = 'Dote innata fuera de tu linaje: ' . $id;
            continue;
        }
        $d = $map_dote[$id];
        $seleccion[$id] = array('id' => $id, 'nombre' => $d['nombre'], 'valor' => (int)$d['pl'], 'tipo' => 'dote_innata', 'linaje' => $d['linaje'], 'spec' => '');
        $pl_total += (int)$d['pl'];
    }

    // --- Defectos generales (§4.4) ---
    $defs = ope_rol_fl_defectos();
    foreach ($arr('defectos') as $id) {
        if (!isset($defs[$id])) { $errores[] = 'Defecto no válido: ' . $id; continue; }
        $d = $defs[$id];
        $spec = ope_pj_spec($d, $specs, $id, $errores, 'defecto');
        $seleccion[$id] = array('id' => $id, 'nombre' => $d['nombre'], 'valor' => (int)$d['pl'], 'tipo' => 'defecto', 'spec' => $spec);
        $pl_total += (int)$d['pl'];
    }

    // --- Defectos de Hibridación (§4.5) ---
    $def_hib = ope_rol_defectos_hibridacion();
    $suma_hib = 0;
    $sel_hib = $arr('defectos_hibridacion');
    foreach ($sel_hib as $id) {
        if (!isset($def_hib[$id])) { $errores[] = 'Defecto de hibridación no válido: ' . $id; continue; }
        $d = $def_hib[$id];
        $spec = ope_pj_spec($d, $specs, $id, $errores, 'defecto');
        $seleccion[$id] = array('id' => $id, 'nombre' => $d['nombre'], 'valor' => (int)$d['pl'], 'tipo' => 'defecto_hibridacion', 'spec' => $spec);
        $pl_total += (int)$d['pl'];
        $suma_hib += (int)$d['pl'];
    }

    // --- Reglas de pureza/hibridación ---
    if ($pureza === 'hibrida') {
        if ($suma_hib > -2) {
            $errores[] = 'Un híbrido debe tomar al menos −2 en Defectos de Hibridación (§4.5).';
        }
        $hib = ope_pj_hibridacion($lin1, $lin2);
        if ($hib['ok'] && $hib['requiere_experimento'] && !isset($seleccion['experimento'])) {
            $errores[] = 'Esta mezcla (Laboratorio/Anomalía) requiere el rasgo "Experimento / Anomalía".';
        }
    } else {
        if (!empty($sel_hib)) {
            $errores[] = 'Los Defectos de Hibridación solo aplican a Sangre Híbrida.';
        }
    }

    // --- Suma cero ---
    if (empty($errores) && $pl_total !== 0) {
        $errores[] = 'El balance de Puntos de Linaje debe cerrar en 0 (actual: ' . ($pl_total > 0 ? '+' : '') . $pl_total . ').';
    }

    return array(
        'errores'   => $errores,
        'seleccion' => $seleccion,
        'pl_total'  => $pl_total,
        'linajes'   => $linajes,
        'pureza'    => $pureza,
    );
}

/** Helper interno: valida y devuelve el texto libre de detalle si el rasgo lo pide. */
function ope_pj_spec(array $item, array $specs, $id, array &$errores, $tipo)
{
    if (empty($item['spec'])) { return ''; }
    $val = isset($specs[$id]) ? trim((string)$specs[$id]) : '';
    if ($val === '') {
        $errores[] = 'El ' . $tipo . ' "' . $item['nombre'] . '" requiere un detalle.';
    }
    return $val;
}
