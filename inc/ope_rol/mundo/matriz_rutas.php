<?php
/**
 * One Piece: Eternal · Matriz de Rutas Maritimas + Pathfinding
 * -------------------------------------------------------------
 * Grafo de conexiones entre las 44 islas. Calcula la ruta optima
 * entre origen y destino usando Dijkstra, determinando tramos,
 * peligro, barreras cruzadas, items requeridos y penalizaciones.
 *
 * Tipos de conexion:
 *   intra      Misma region (mismo Blue, misma zona GL)
 *   inter      Entre Blues distintos (requiere Brujula)
 *   reverse    Blues -> Paradise via Reverse Mountain (solo ida)
 *   gl_hop     Islas consecutivas en Grand Line (requiere Log Pose)
 *   cb_cross   Cruce del Calm Belt (requiere Kairoseki)
 *   sky        Acceso a Skypiea via Knock Up Stream
 *   sub        Descenso submarino Sabaody -> Gyojin (requiere Resina)
 *   nw_entry   Entrada al Nuevo Mundo desde Gyojin
 *   nw_hop     Entre islas del Nuevo Mundo (requiere Log Pose NW)
 *   red_line   Acceso a Marineford / Mary Geoise (evento especial)
 *
 * Uso:
 *   $ruta = ope_navegacion_calcular_ruta('isla_dawn', 'alabasta', $barco, $items, $trip, 5);
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Grafo completo de conexiones maritimas.
 * Cada arista: [isla_a, isla_b, tramos_base, peligro_transito, tipo, bidireccional]
 */
function ope_navegacion_grafo()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $edges = array(
        // ── EAST BLUE internas ─────────────────────────────────
        array('isla_dawn',    'shells_town',   1, 1, 'intra', true),
        array('shells_town',  'orange_town',   1, 1, 'intra', true),
        array('orange_town',  'syrup_village', 1, 1, 'intra', true),
        array('orange_town',  'loguetown',     1, 1, 'intra', true),
        array('syrup_village','islas_conomi',   1, 1, 'intra', true),
        array('islas_conomi', 'baratie',        1, 1, 'intra', true),
        array('baratie',      'loguetown',      1, 1, 'intra', true),
        array('loguetown',    'isla_gecko',     1, 2, 'intra', true),

        // ── WEST BLUE internas ─────────────────────────────────
        array('reino_sorbet',  'isla_kalimero',   1, 1, 'intra', true),
        array('isla_kalimero', 'puerto_neblina',  1, 1, 'intra', true),
        array('puerto_neblina','isla_verthom',    1, 2, 'intra', true),
        array('isla_verthom',  'bahia_rosclave',  1, 2, 'intra', true),
        array('bahia_rosclave','isla_fenrik',     1, 2, 'intra', true),
        array('isla_fenrik',   'costa_malmuerta', 1, 2, 'intra', true),
        array('costa_malmuerta','isla_corvenna',  1, 2, 'intra', true),
        array('reino_sorbet',  'isla_corvenna',   1, 1, 'intra', true),

        // ── NORTH BLUE internas ────────────────────────────────
        array('reino_germa',    'isla_thornveil',  1, 2, 'intra', true),
        array('isla_thornveil', 'puerto_grisalba', 1, 1, 'intra', true),
        array('puerto_grisalba','isla_kelthorn',   1, 2, 'intra', true),
        array('isla_kelthorn',  'bahia_escarcha',  1, 2, 'intra', true),
        array('bahia_escarcha', 'isla_draconis',   1, 3, 'intra', true),
        array('isla_draconis',  'puerto_nevado',   1, 2, 'intra', true),
        array('puerto_nevado',  'isla_varnholt',   1, 2, 'intra', true),
        array('isla_varnholt',  'reino_germa',     1, 2, 'intra', true),

        // ── SOUTH BLUE internas ────────────────────────────────
        array('isla_momoiro',    'isla_baterilla',  1, 2, 'intra', true),
        array('isla_baterilla',  'isla_sorenna',    1, 1, 'intra', true),
        array('isla_sorenna',    'puerto_ceniza',   1, 2, 'intra', true),
        array('puerto_ceniza',   'isla_corvalle',   1, 2, 'intra', true),
        array('isla_corvalle',   'bahia_marlot',    1, 2, 'intra', true),
        array('bahia_marlot',    'isla_tessaly',    1, 1, 'intra', true),
        array('isla_tessaly',    'puerto_serpiente', 1, 3, 'intra', true),
        array('puerto_serpiente','isla_momoiro',    1, 2, 'intra', true),

        // ── INTER-BLUE (cruces entre Blues, requiere Brujula) ──
        array('loguetown',       'puerto_grisalba',  2, 3, 'inter', true),
        array('loguetown',       'isla_baterilla',   2, 3, 'inter', true),
        array('isla_corvenna',   'puerto_serpiente', 2, 3, 'inter', true),
        array('isla_gecko',      'isla_momoiro',     2, 3, 'inter', true),
        array('puerto_neblina',  'bahia_escarcha',   2, 3, 'inter', true),
        array('reino_sorbet',    'isla_sorenna',     2, 3, 'inter', true),

        // ── REVERSE MOUNTAIN (Blues -> Paradise, solo ida) ──────
        array('loguetown',       'whiskey_peak',     2, 5, 'reverse', false),
        array('puerto_neblina',  'whiskey_peak',     2, 5, 'reverse', false),
        array('puerto_grisalba', 'whiskey_peak',     2, 5, 'reverse', false),
        array('isla_baterilla',  'whiskey_peak',     2, 5, 'reverse', false),

        // ── PARADISE — Grand Line hops (requiere Log Pose) ─────
        array('whiskey_peak',  'little_garden',  1, 4, 'gl_hop', true),
        array('little_garden', 'isla_drum',      1, 4, 'gl_hop', true),
        array('isla_drum',     'alabasta',        2, 4, 'gl_hop', true),
        array('alabasta',      'jaya',            2, 5, 'gl_hop', true),
        array('jaya',          'long_ring',       2, 4, 'gl_hop', true),
        array('long_ring',     'water_seven',     1, 4, 'gl_hop', true),
        array('water_seven',   'enies_lobby',     1, 6, 'gl_hop', true),
        array('water_seven',   'thriller_bark',   2, 5, 'gl_hop', true),
        array('thriller_bark', 'sabaody',         2, 5, 'gl_hop', true),

        // ── SKY ACCESS (Knock Up Stream) ───────────────────────
        array('jaya',          'skypiea',          1, 6, 'sky', true),

        // ── CALM BELT crossings (requiere Kairoseki) ───────────
        array('isla_gecko',    'amazon_lily',      2, 8, 'cb_cross', true),
        array('isla_momoiro',  'amazon_lily',      2, 8, 'cb_cross', true),

        // ── SABAODY -> GYOJIN (requiere Resina de Sabaody) ─────
        array('sabaody',       'isla_gyojin',      2, 7, 'sub', true),

        // ── GYOJIN -> NUEVO MUNDO ──────────────────────────────
        array('isla_gyojin',   'punk_hazard',      1, 6, 'nw_entry', true),

        // ── NUEVO MUNDO hops (requiere Log Pose NW) ────────────
        array('punk_hazard',   'dressrosa',        2, 7, 'nw_hop', true),
        array('dressrosa',     'zou',              2, 7, 'nw_hop', true),
        array('zou',           'wano',             2, 8, 'nw_hop', true),
        array('zou',           'whole_cake',       2, 8, 'nw_hop', true),
        array('whole_cake',    'elbaf',            2, 7, 'nw_hop', true),
        array('dressrosa',     'whole_cake',       3, 8, 'nw_hop', true),

        // ── RED LINE (acceso especial) ─────────────────────────
        array('sabaody',       'marineford',       3, 9, 'red_line', true),
        array('marineford',    'mary_geoise',      1, 9, 'red_line', true),
    );

    // Construir mapa de adyacencia
    $graph = array();
    foreach ($edges as $e) {
        $a   = $e[0];
        $b   = $e[1];
        $row = array('to' => $b, 'tramos' => (int)$e[2], 'peligro' => (int)$e[3], 'tipo' => $e[4]);
        $graph[$a][] = $row;

        if ($e[5]) { // bidireccional
            $row['to'] = $a;
            $graph[$b][] = $row;
        }
    }

    $cache = $graph;
    return $cache;
}

/**
 * Items requeridos por tipo de conexion.
 * Devuelve el slug del item necesario o null.
 */
function ope_navegacion_item_requerido($tipo_conexion)
{
    $map = array(
        'inter'    => 'brujula',
        'gl_hop'   => 'log_pose',
        'nw_hop'   => 'log_pose_nw',
        'nw_entry' => 'log_pose',
        'cb_cross' => 'kairoseki',
        'sub'      => 'resina_sabaody',
        'sky'      => null,       // narrativo, sin item obligatorio
        'reverse'  => null,       // geografico
        'intra'    => 'brujula',  // brujula para navegar en Blues
        'red_line' => null,       // evento especial
    );
    return isset($map[$tipo_conexion]) ? $map[$tipo_conexion] : null;
}

/**
 * Penalizacion de peligro por carecer del item requerido.
 */
function ope_navegacion_penalizacion_sin_item($tipo_conexion)
{
    $map = array(
        'intra'    => 2,   // sin brujula en Blues
        'inter'    => 3,   // sin brujula entre Blues
        'gl_hop'   => 4,   // sin Log Pose en Grand Line
        'nw_hop'   => 5,   // sin Log Pose NW en Nuevo Mundo
        'nw_entry' => 4,   // sin Log Pose
        'cb_cross' => 5,   // sin Kairoseki en Calm Belt
        'sub'      => 99,  // sin Resina: bloqueo (unico caso)
        'sky'      => 4,   // Knock Up Stream sin preparacion
    );
    return isset($map[$tipo_conexion]) ? (int)$map[$tipo_conexion] : 0;
}

/**
 * Pathfinding Dijkstra. Encuentra ruta de menor coste entre origen y destino.
 *
 * @param string $origen_slug   Slug isla origen
 * @param string $destino_slug  Slug isla destino
 * @return array|null  Array con 'nodos' (slugs), 'aristas' (detalles de cada salto), 'coste_total'
 */
function ope_navegacion_pathfind($origen_slug, $destino_slug)
{
    $graph = ope_navegacion_grafo();
    $origen  = (string)$origen_slug;
    $destino = (string)$destino_slug;

    if ($origen === $destino) {
        return array('nodos' => array($origen), 'aristas' => array(), 'coste_total' => 0);
    }

    // Dijkstra
    $dist = array($origen => 0);
    $prev = array();
    $edge_used = array();
    $visited = array();

    // Cola de prioridad simple (array + sort)
    $queue = array(array(0, $origen));

    while (!empty($queue)) {
        // Extraer el nodo con menor distancia
        usort($queue, function ($a, $b) { return $a[0] - $b[0]; });
        $current = array_shift($queue);
        $cost_u = $current[0];
        $u = $current[1];

        if (isset($visited[$u])) {
            continue;
        }
        $visited[$u] = true;

        if ($u === $destino) {
            break;
        }

        if (!isset($graph[$u])) {
            continue;
        }

        foreach ($graph[$u] as $edge) {
            $v = $edge['to'];
            if (isset($visited[$v])) {
                continue;
            }
            // Coste: tramos + peligro*0.1 (favorece rutas cortas y seguras)
            $weight = $edge['tramos'] + $edge['peligro'] * 0.1;
            $new_dist = $cost_u + $weight;

            if (!isset($dist[$v]) || $new_dist < $dist[$v]) {
                $dist[$v] = $new_dist;
                $prev[$v] = $u;
                $edge_used[$v] = $edge;
                $queue[] = array($new_dist, $v);
            }
        }
    }

    if (!isset($prev[$destino]) && $origen !== $destino) {
        return null; // No hay ruta
    }

    // Reconstruir camino
    $path = array();
    $node = $destino;
    while ($node !== $origen) {
        $path[] = $node;
        $node = $prev[$node];
    }
    $path[] = $origen;
    $path = array_reverse($path);

    // Reconstruir aristas
    $aristas = array();
    for ($i = 1; $i < count($path); $i++) {
        $aristas[] = array(
            'desde'   => $path[$i - 1],
            'hasta'   => $path[$i],
            'tramos'  => (int)$edge_used[$path[$i]]['tramos'],
            'peligro' => (int)$edge_used[$path[$i]]['peligro'],
            'tipo'    => (string)$edge_used[$path[$i]]['tipo'],
        );
    }

    return array(
        'nodos'       => $path,
        'aristas'     => $aristas,
        'coste_total' => isset($dist[$destino]) ? $dist[$destino] : 999,
    );
}

/**
 * Escala de peligro: de peligro acumulado a nivel legible y indice.
 */
function ope_navegacion_nivel_peligro($peligro_total)
{
    $p = (int)$peligro_total;
    if ($p <= 4)  return array('nivel' => 'muy_bajo', 'label' => 'Muy Bajo',  'idx' => 0);
    if ($p <= 8)  return array('nivel' => 'bajo',     'label' => 'Bajo',      'idx' => 1);
    if ($p <= 14) return array('nivel' => 'medio',    'label' => 'Medio',     'idx' => 2);
    if ($p <= 20) return array('nivel' => 'alto',     'label' => 'Alto',      'idx' => 3);
    if ($p <= 28) return array('nivel' => 'muy_alto', 'label' => 'Muy Alto',  'idx' => 4);
    if ($p <= 36) return array('nivel' => 'extremo',  'label' => 'Extremo',   'idx' => 5);
    return array('nivel' => 'mortal', 'label' => 'Mortal', 'idx' => 6);
}

/**
 * Factor de velocidad del barco.
 * vel 1 = x1.5, vel 2 = x1.0, vel 3 = x0.75, vel 4 = x0.6
 */
function ope_navegacion_factor_vel($vel)
{
    $map = array(1 => 1.5, 2 => 1.0, 3 => 0.75, 4 => 0.6);
    $v = max(1, min(4, (int)$vel));
    return $map[$v];
}

/**
 * Calcula la ruta completa entre dos islas con todos los factores.
 *
 * @param string $origen_slug   Slug de isla origen (isla_actual del PJ)
 * @param string $destino_slug  Slug de isla destino
 * @param array  $barco         Datos del barco (de rol_barcos) o array vacio
 * @param array  $items_slugs   Slugs de items equipados (ej: ['brujula','log_pose'])
 * @param array  $tripulantes   Array de tripulantes con 'oficio' (futuro: + NPCs)
 * @param int    $pj_nivel      Nivel del PJ capitan
 * @return array Resultado completo de la ruta
 */
function ope_navegacion_calcular_ruta($origen_slug, $destino_slug, array $barco = array(), array $items_slugs = array(), array $tripulantes = array(), $pj_nivel = 1)
{
    $origen_slug  = (string)$origen_slug;
    $destino_slug = (string)$destino_slug;

    $isla_origen  = ope_isla_por_slug($origen_slug);
    $isla_destino = ope_isla_por_slug($destino_slug);

    if (!$isla_origen || !$isla_destino) {
        return array('ok' => false, 'msg' => 'Isla de origen o destino no valida.');
    }
    if ($origen_slug === $destino_slug) {
        return array('ok' => false, 'msg' => 'Origen y destino son la misma isla.');
    }

    // 1. Pathfinding
    $path = ope_navegacion_pathfind($origen_slug, $destino_slug);
    if (!$path || empty($path['aristas'])) {
        return array('ok' => false, 'msg' => 'No hay ruta navegable entre esas islas.');
    }

    // 2. Acumular tramos y peligro, detectar barreras e items
    $tramos_total     = 0;
    $peligro_acum     = 0;
    $barreras         = array();
    $items_requeridos = array();
    $items_faltantes  = array();
    $penalizaciones   = array();
    $es_temeraria     = false;
    $ruta_detalle     = array();

    foreach ($path['aristas'] as $arista) {
        $tramos_total += $arista['tramos'];
        $peligro_acum += $arista['peligro'];

        // Barreras
        $tipo = $arista['tipo'];
        if (in_array($tipo, array('reverse', 'cb_cross', 'sub', 'sky', 'red_line'), true)) {
            $barreras[] = $tipo;
        }

        // Items requeridos
        $item_req = ope_navegacion_item_requerido($tipo);
        if ($item_req !== null) {
            if (!in_array($item_req, $items_requeridos, true)) {
                $items_requeridos[] = $item_req;
            }
            // Tiene el item?
            if (!in_array($item_req, $items_slugs, true)) {
                if (!in_array($item_req, $items_faltantes, true)) {
                    $items_faltantes[] = $item_req;
                }
                $pen = ope_navegacion_penalizacion_sin_item($tipo);
                if ($pen >= 99) {
                    // Bloqueo total (solo Resina de Sabaody)
                    return array(
                        'ok' => false,
                        'msg' => 'Necesitas Resina de Sabaody para descender a Isla Gyojin. Consiguela en el Archipielago Sabaody.',
                    );
                }
                $peligro_acum += $pen;
                $es_temeraria = true;
                $penalizaciones[] = 'Sin ' . $item_req . ': peligro +' . $pen;
            }
        }

        $ruta_detalle[] = array(
            'desde'  => $arista['desde'],
            'hasta'  => $arista['hasta'],
            'tipo'   => $tipo,
            'tramos' => $arista['tramos'],
            'peligro'=> $arista['peligro'],
        );
    }

    // 3. Modificadores de barco
    $barco_vel  = (int)($barco['vel'] ?? 2);
    $barco_mods = ope_navegacion_mods_barco($barco);

    // Mejoras del barco
    $mejoras = array();
    if (!empty($barco['mejoras_json'])) {
        $mejoras = is_string($barco['mejoras_json']) ? json_decode($barco['mejoras_json'], true) : $barco['mejoras_json'];
        if (!is_array($mejoras)) $mejoras = array();
    }
    if (!empty($mejoras['velamen_reforzado'])) {
        $barco_vel = min(4, $barco_vel + 1);
    }
    if (!empty($mejoras['despensa_reforzada']) && $tramos_total > 3) {
        $tramos_total = max(1, $tramos_total - 1);
    }
    if (!empty($mejoras['kairoseki'])) {
        // Recubrimiento del barco sirve como item kairoseki
        $items_faltantes = array_diff($items_faltantes, array('kairoseki'));
    }

    // 4. Modificadores de tripulacion (futuro: + NPCs)
    $trip_mods = ope_navegacion_mods_tripulacion($tripulantes);

    // 5. Modificadores de items
    $item_mods = ope_navegacion_mods_items($items_slugs, $destino_slug);

    // 6. Mods totales
    $mods_total = array('clima' => 0, 'encuentro' => 0, 'peligro' => 0, 'hallazgo' => 0, 'misterio' => 0, 'bonanza' => 0);
    foreach (array($barco_mods, $trip_mods, $item_mods) as $m) {
        foreach ($mods_total as $k => $v) {
            $mods_total[$k] += (int)($m[$k] ?? 0);
        }
    }

    // 7. Nivel de peligro
    $nivel_info = ope_navegacion_nivel_peligro($peligro_acum);

    // 8. Días off-rol y días on-rol (Regla OPE: 1 día off-rol = 1.5 días on-rol)
    $plazo_base     = max(3, ceil($tramos_total * 2 * $factor_vel));
    $plazo_mod_item = 0;
    foreach ($items_slugs as $is) {
        if ($is === 'eternal_pose') $plazo_mod_item -= 1;
        if ($is === 'mapa_nautico')  $plazo_mod_item -= 1;
    }
    $plazo_offrol     = max(3, $plazo_base + $plazo_mod_item);
    $dias_onrol       = (float) round($plazo_offrol * 1.5, 1);
    $posts_sugeridos  = $tramos_total * 3 + $nivel_info['idx'] * 2;

    // 10. Temeraria por nivel bajo?
    $tier_destino = $isla_destino['tier'];
    $pj_tramo     = (int)floor(max(0, (int)$pj_nivel - 1) / 10) + 1;
    if ($pj_tramo < $tier_destino) {
        $es_temeraria = true;
        $diff = $tier_destino - $pj_tramo;
        $peligro_acum += $diff * 3;
        $penalizaciones[] = 'Nivel bajo para esta zona: peligro +' . ($diff * 3);
        // Recalcular nivel
        $nivel_info = ope_navegacion_nivel_peligro($peligro_acum);
    }

    return array(
        'ok'               => true,
        'ruta'             => $ruta_detalle,
        'nodos'            => $path['nodos'],
        'tramos_total'     => $tramos_total,
        'peligro_acumulado'=> $peligro_acum,
        'nivel_peligro'    => $nivel_info['nivel'],
        'nivel_peligro_label' => $nivel_info['label'],
        'nivel_peligro_idx'=> $nivel_info['idx'],
        'dias_onrol'       => $dias_onrol,
        'posts_sugeridos'  => $posts_sugeridos,
        'plazo_offrol_dias'=> $plazo_offrol,
        'barreras'         => array_unique($barreras),
        'items_requeridos' => $items_requeridos,
        'items_faltantes'  => $items_faltantes,
        'es_temeraria'     => $es_temeraria,
        'penalizaciones'   => $penalizaciones,
        'mods_barco'       => $barco_mods,
        'mods_tripulacion' => $trip_mods,
        'mods_items'       => $item_mods,
        'mods_total'       => $mods_total,
        'barco_vel'        => $barco_vel,
        'origen'           => $isla_origen,
        'destino'          => $isla_destino,
    );
}

/**
 * Modificadores del barco para las mesas del Oraculo.
 * Usa la config de tipos de barco.
 */
function ope_navegacion_mods_barco(array $barco)
{
    $mods = array('clima' => 0, 'encuentro' => 0, 'peligro' => 0, 'hallazgo' => 0, 'misterio' => 0, 'bonanza' => 0);
    $tipos = ope_navegacion_barcos_tipos();
    $tipo = (string)($barco['tipo'] ?? 'bote');
    if (isset($tipos[$tipo])) {
        $t = $tipos[$tipo];
        $mods['clima']     = (int)($t['mod_clima'] ?? 0);
        $mods['peligro']   = (int)($t['mod_peligro'] ?? 0);
        $mods['encuentro'] = (int)($t['mod_encuentro'] ?? 0);
    }

    // Mejoras
    $mejoras = array();
    if (!empty($barco['mejoras_json'])) {
        $mejoras = is_string($barco['mejoras_json']) ? json_decode($barco['mejoras_json'], true) : $barco['mejoras_json'];
        if (!is_array($mejoras)) $mejoras = array();
    }
    if (!empty($mejoras['casco_blindado']))  $mods['peligro']   -= 6;
    if (!empty($mejoras['camuflaje']))       $mods['encuentro'] -= 8;
    if (!empty($mejoras['cocina']))          $mods['peligro']   -= 2;

    return $mods;
}

/** Tipos de barco con sus atributos de navegacion. */
function ope_navegacion_barcos_tipos()
{
    return array(
        'bote'      => array('label' => 'Bote / chalupa',          'vel' => 1, 'mod_clima' => -8,  'mod_peligro' => 8,   'mod_encuentro' => 0,  'capacidad' => 1),
        'balandra'  => array('label' => 'Balandra',                'vel' => 2, 'mod_clima' => -4,  'mod_peligro' => 4,   'mod_encuentro' => 0,  'capacidad' => 2),
        'goleta'    => array('label' => 'Goleta ligera',           'vel' => 3, 'mod_clima' => 4,   'mod_peligro' => 6,   'mod_encuentro' => 0,  'capacidad' => 2),
        'cuter'     => array('label' => 'Cuter de combate',        'vel' => 2, 'mod_clima' => 0,   'mod_peligro' => 2,   'mod_encuentro' => 6,  'capacidad' => 1),
        'bergantin' => array('label' => 'Bergantin',               'vel' => 2, 'mod_clima' => 0,   'mod_peligro' => -4,  'mod_encuentro' => 0,  'capacidad' => 6),
        'fragata'   => array('label' => 'Fragata',                 'vel' => 3, 'mod_clima' => -4,  'mod_peligro' => -6,  'mod_encuentro' => -2, 'capacidad' => 10),
        'galeon'    => array('label' => 'Galeon pesado',           'vel' => 1, 'mod_clima' => -6,  'mod_peligro' => -4,  'mod_encuentro' => -4, 'capacidad' => 12),
        'navio'     => array('label' => 'Navio de linea',          'vel' => 1, 'mod_clima' => -8,  'mod_peligro' => -8,  'mod_encuentro' => -6, 'capacidad' => 15),
        'especial'  => array('label' => 'Barco especial / legado', 'vel' => 2, 'mod_clima' => 0,   'mod_peligro' => -4,  'mod_encuentro' => 0,  'capacidad' => 8),
    );
}

/**
 * Modificadores de la tripulacion para las mesas del Oraculo.
 * Usa la config de oficios existente pero extendida.
 */
function ope_navegacion_mods_tripulacion(array $tripulantes)
{
    $mods = array('clima' => 0, 'encuentro' => 0, 'peligro' => 0, 'hallazgo' => 0, 'misterio' => 0, 'bonanza' => 0);
    $cfg  = array(
        'navegante'  => array('clima' => -12, 'peligro' => -4),
        'timonel'    => array('peligro' => -10, 'encuentro' => -3),
        'vigia'      => array('hallazgo' => 12, 'encuentro' => -8),
        'carpintero' => array('peligro' => -6),
        'cocinero'   => array('peligro' => -4, 'bonanza' => 4),
        'medico'     => array('peligro' => -5),
        'artillero'  => array('encuentro' => 4),
    );
    foreach ($tripulantes as $t) {
        $of = strtolower(trim((string)($t['oficio'] ?? '')));
        if ($of === '' || !isset($cfg[$of])) continue;
        foreach ($cfg[$of] as $mesa => $val) {
            $mods[$mesa] = ($mods[$mesa] ?? 0) + (int)$val;
        }
    }
    return $mods;
}

/**
 * Modificadores de items para las mesas del Oraculo.
 */
function ope_navegacion_mods_items(array $items_slugs, $destino_slug = '')
{
    $mods = array('clima' => 0, 'encuentro' => 0, 'peligro' => 0, 'hallazgo' => 0, 'misterio' => 0, 'bonanza' => 0);

    if (in_array('eternal_pose', $items_slugs, true)) {
        $mods['peligro'] -= 2;
        $mods['clima']   -= 2;
    }
    if (in_array('mapa_nautico', $items_slugs, true)) {
        $mods['hallazgo'] += 5;
    }
    if (in_array('vivre_card', $items_slugs, true)) {
        $mods['peligro'] -= 1;
    }

    return $mods;
}
