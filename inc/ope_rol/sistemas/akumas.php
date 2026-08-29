<?php
/**
 * One Piece: 7 Seas · Akumas y Haki (F5) — 5.18/5.19
 * --------------------------------------------------
 * Trámites automáticos de frutas y del Conquistador:
 *   · 45 — Tirada de akuma aleatoria (nv3+): pool por nivel, afinidad −10 % PE,
 *          anti-abuso nv7. 100 % automático (una de las 3 excepciones del §9).
 *   · 46 — Compra de fruta con PP: matriz de especificidad (×1 familia / ×2
 *          concepto / ×3 concreta). Familia → automático (pool); concepto y
 *          concreta → bandeja (la IA propone, el staff firma — D5.2).
 *   · 47 — Comer la fruta: consume el objeto, asigna el cupo mundial, aplica
 *          defectos exigidos y dotes exclusivas con balanza a 0 (D5.3).
 *   · 50 — Tirada del Conquistador (nv5+ cada 10): probabilidad 3→40 %, registra
 *          el intento, y si acierta crea el nivel 1 + suceso en borrador (D5.4).
 *
 * Números cerrados del manual (19.7/20.x) — no recalibrar.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Ficha de akuma (8 bloques decodificados) o null. */
function ope7_akuma_info($akuma_id)
{
    global $db;
    $akuma_id = (int) $akuma_id;
    if ($akuma_id < 1 || !ope7_tabla_existe('akumas')) {
        return null;
    }
    $q = $db->simple_select('ope_akumas', '*', "id = {$akuma_id}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return null;
    }
    foreach (array('mecanica_base', 'puertas', 'debilidades', 'requisitos_portador', 'influencia_ficha', 'despertar', 'coste_pp') as $j) {
        $f[$j] = !empty($f[$j]) ? json_decode((string) $f[$j], true) : null;
    }
    return $f;
}

/** Tier máximo del pool según nivel (19.7: nv3+ T1–T2 · nv15+ T3 · nv30+ T4). */
function ope7_akuma_tier_max($nivel)
{
    $nivel = (int) $nivel;
    if ($nivel >= 30) {
        return 4;
    }
    if ($nivel >= 15) {
        return 3;
    }
    return 2; // nv3+ (el efecto valida nivel ≥ 3)
}

/** Matriz de compra (19.7): base por tier y multiplicador de especificidad. */
function ope7_akuma_matriz($tier, $especificidad)
{
    $base = array(1 => 150, 2 => 300, 3 => 600, 4 => 1000, 5 => 1500);
    $mult = array('familia' => 1, 'concepto' => 2, 'concreta' => 3);
    $b = (int) ($base[(int) $tier] ?? 0);
    $m = (float) ($mult[$especificidad] ?? 1);
    return array('base' => $b, 'mult' => $m, 'coste' => (int) round($b * $m));
}

/** Frutas sin portador del pool (opcional filtro de familia y techo de tier). */
function ope7_akuma_pool_disponibles($tier_max = 5, $familia = '', array $excluir = array())
{
    global $db;
    $out = array();
    if (!ope7_tabla_existe('akumas')) {
        return $out;
    }
    $where = 'portador_id IS NULL AND tier <= ' . (int) $tier_max;
    if ($familia !== '' && in_array($familia, array('paramecia', 'zoan', 'logia'), true)) {
        $where .= " AND familia = '" . $familia . "'";
    }
    if ($excluir) {
        $where .= ' AND id NOT IN (' . implode(',', array_map('intval', $excluir)) . ')';
    }
    $q = $db->simple_select('ope_akumas', '*', $where, array('order_by' => 'tier, id'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Elige una fruta al azar del pool (única tirada del sistema de frutas: solo decide qué obtienes). */
function ope7_akuma_aleatoria($tier_max, $familia = '')
{
    $pool = ope7_akuma_pool_disponibles($tier_max, $familia);
    if (!$pool) {
        return null;
    }
    return $pool[array_rand($pool)];
}

/** Objeto «Fruta: X» del catálogo para un akuma (o 0). */
function ope7_akuma_objeto_id($akuma_id)
{
    global $db;
    if (!ope7_tabla_existe('objetos')) {
        return 0;
    }
    // JSON_EXTRACT: inmune al formato del JSON almacenado (MySQL normaliza espacios/orden).
    $q = $db->query('SELECT id FROM ' . ope7_tabla_full('objetos') . " WHERE categoria = 'akuma' AND JSON_EXTRACT(efecto_json, '$.akuma_id') = " . (int) $akuma_id . ' LIMIT 1');
    $id = (int) $db->fetch_field($q, 'id');
    return $id > 0 ? $id : 0;
}

/** Reserva el cupo mundial y entrega el fruto en el inventario (mediano, 1 ranura). */
function ope7_akuma_entregar($pid, $akuma, $via)
{
    global $db;
    $akuma_id = (int) $akuma['id'];
    if (ope7_tabla_existe('akumas')) {
        $db->update_query('ope_akumas', array(
            'portador_id' => (int) $pid,
            'origen'      => $via,
            'estado'      => 'con_portador',
        ), "id = {$akuma_id}");
    }
    $objeto_id = ope7_akuma_objeto_id($akuma_id);
    if ($objeto_id > 0 && ope7_tabla_existe('inventario_personaje')) {
        $db->insert_query('ope_inventario_personaje', array(
            'personaje_id' => (int) $pid,
            'objeto_id'    => $objeto_id,
            'zona'         => 'mochila',
            'cantidad'     => 1,
        ));
    }
    if (ope7_tabla_existe('akuma_historico')) {
        $db->insert_query('ope_akuma_historico', array(
            'akuma_id'    => $akuma_id,
            'portador_id' => (int) $pid,
            'via'         => $via === 'tirada' ? 'tirada' : ($via === 'compra' ? 'compra' : 'recompensa'),
            // Sin coste (JSON): el default de la columna es NULL.
            'tipo_evento' => 'obtencion',
            'fecha'       => TIME_NOW,
        ));
    }
    return true;
}

/** ¿El PJ ya tiene fruta (comida o reservada sin comer, cupo mundial)? */
function ope7_akuma_tiene_fruta($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return false;
    }
    $q = $db->simple_select('ope_personajes', 'akuma_id', "id = {$pid}", array('limit' => 1));
    if ((int) $db->fetch_field($q, 'akuma_id') > 0) {
        return true;
    }
    if (ope7_tabla_existe('akumas')) {
        $q = $db->simple_select('ope_akumas', 'id', "portador_id = {$pid} AND estado = 'con_portador'", array('limit' => 1));
        return $db->num_rows($q) > 0;
    }
    return false;
}

/** Defectos exigidos / dotes exclusivas de la ficha (nombres). */
function ope7_akuma_influencia($akuma)
{
    $inf = is_array($akuma['influencia_ficha'] ?? null) ? $akuma['influencia_ficha'] : array();
    return array(
        'dotes'    => (array) ($inf['dotes'] ?? array()),
        'defectos' => (array) ($inf['defectos'] ?? array()),
        'balanza'  => (int) ($inf['balanza'] ?? 0),
    );
}

/** Efecto 45 · Tirada de akuma aleatoria (100 % automático, 19.7.1). */
function ope7_efecto_tirada_akuma($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('akumas')) {
        return 'Tirada de fruta: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Tirada de fruta: personaje no encontrado.';
    }
    $nivel = (int) $f['nivel'];
    if ($nivel < 3) {
        return 'Tirada de fruta BLOQUEADA: necesitas nivel 3+ (19.7).';
    }
    if (ope7_akuma_tiene_fruta($pid)) {
        return 'Tirada de fruta BLOQUEADA: ya tienes una fruta asignada o en el inventario (cupo mundial, 19.5 — una sola).';
    }
    // Anti-abuso (19.7.1): morir para volver a tirar → sin tirada hasta nv7.
    if (ope7_tabla_existe('muertes')) {
        $mq = $db->simple_select('ope_muertes', 'id', "personaje_id = {$pid}", array('limit' => 1));
        if ($db->num_rows($mq) && $nivel < 7) {
            return 'Tirada de fruta BLOQUEADA (anti-abuso, 19.7.1): si matas a tu personaje para volver a tirar, no vuelves a usar la tirada hasta el nivel 7.';
        }
    }
    $tier_max = ope7_akuma_tier_max($nivel);
    $akuma = ope7_akuma_aleatoria($tier_max);
    if (!$akuma) {
        return 'Tirada de fruta BLOQUEADA: no hay frutas libres en el pool de tu nivel (nv' . $nivel . ' → T1–T' . $tier_max . '). Avísanos y ampliamos el catálogo.';
    }
    ope7_akuma_entregar($pid, $akuma, 'tirada');
    $db->update_query('ope_personajes', array('akuma_afinidad' => 1), "id = {$pid}");

    return 'Tirada aceptada: el mundo te ha concedido la «' . $akuma['nombre_propio'] . '» (T' . (int) $akuma['tier']
        . ', ' . $akuma['familia'] . '). La tienes en tu mochila como objeto: cómela con el trámite 47 (una mordida basta, 19.7). '
        . 'Afinidad natural −10 % PE en las técnicas de esta fruta. Compromiso: no se revende ni se regala (debe comerse).';
}

/** Efecto 46 · Compra de fruta con PP (19.7.2; D5.2: familia auto, resto a bandeja). */
function ope7_efecto_compra_akuma($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('akumas')) {
        return 'Compra de fruta: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Compra de fruta: personaje no encontrado.';
    }
    if ((int) $f['nivel'] < 3) {
        return 'Compra de fruta BLOQUEADA: necesitas nivel 3+ (19.7.2).';
    }
    if (ope7_akuma_tiene_fruta($pid)) {
        return 'Compra de fruta BLOQUEADA: ya tienes una fruta asignada o en el inventario (cupo mundial, 19.5 — una sola).';
    }

    $especificidad = (string) ($ids['especificidad'] ?? 'familia');
    $tier = (int) ($ids['tier'] ?? 0);
    if (!in_array($especificidad, array('familia', 'concepto', 'concreta'), true) || $tier < 1 || $tier > 5) {
        return 'Compra de fruta BLOQUEADA: indica especificidad (familia/concepto/concreta) y tier 1–5.';
    }
    $matriz = ope7_akuma_matriz($tier, $especificidad);

    // D5.2: concepto y fruta concreta pasan por el staff (propone el staff/foro, firma con el cupo mundial).
    if ($especificidad !== 'familia') {
        return '[PENDIENTE] Compra por ' . ($especificidad === 'concepto' ? 'concepto' : 'fruta concreta')
            . ' (×' . number_format($matriz['mult'], 1, ',', '') . '): el foro estudia la fruta que encaja y el staff firma el cupo mundial. '
            . 'Coste estimado ' . $matriz['coste'] . ' PP (T' . $tier . ', matriz 19.7).';
    }

    $saldo = (int) ($f['pp_saldo'] ?? 0);
    if ($saldo < $matriz['coste']) {
        return 'Compra de fruta BLOQUEADA: necesitas ' . $matriz['coste'] . ' PP y tienes ' . $saldo . ' (saldo en `pp_saldo`).';
    }
    $familia = (string) ($ids['familia'] ?? '');
    $akuma = ope7_akuma_aleatoria($tier, $familia !== '' ? $familia : '');
    if (!$akuma) {
        return 'Compra de fruta BLOQUEADA: no hay frutas libres de ese tier/familia en el catálogo. Prueba otra familia o espera una recompensa.';
    }

    // Pago (irreversible, 19.7.2) + entrega.
    $db->update_query('ope_personajes', array('pp_saldo' => $saldo - $matriz['coste']), "id = {$pid}");
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => (int) $pid,
            'cantidad'     => -$matriz['coste'],
            'concepto'     => 'Compra de fruta (T' . $tier . ', familia ' . $familia . ' — matriz ×1): «' . $akuma['nombre_propio'] . '»',
            'tramite_id'   => (int) ($tr['id'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }
    ope7_akuma_entregar($pid, $akuma, 'compra');

    return 'Compra aceptada: «' . $akuma['nombre_propio'] . '» (T' . (int) $akuma['tier'] . ', ' . $akuma['familia']
        . ') por ' . $matriz['coste'] . ' PP. La tienes en tu mochila: cómela con el trámite 47. '
        . 'La compra es irreversible (no devuelve PP si la fruta se pierde o cambia, 19.7.2).';
}

/** Efecto 47 · Comer la fruta (19.7: una mordida transfiere el poder; D5.3). */
function ope7_efecto_comer_akuma($pid, $ids, $tr)
{
    global $db;
    $akuma_id = (int) ($ids['akuma_id'] ?? 0);
    if ($pid < 1 || $akuma_id < 1 || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('akumas')) {
        return 'Comer la fruta BLOQUEADO: faltan datos (personaje o fruta).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Comer la fruta BLOQUEADO: personaje no encontrado.';
    }
    if ((int) ($f['akuma_id'] ?? 0) > 0) {
        return 'Comer la fruta BLOQUEADO: ya eres portador de una fruta (una sola, cupo mundial).';
    }
    $akuma = ope7_akuma_info($akuma_id);
    if (!$akuma) {
        return 'Comer la fruta BLOQUEADO: la fruta no existe en el catálogo.';
    }
    if ((int) ($akuma['portador_id'] ?? 0) !== (int) $pid) {
        return 'Comer la fruta BLOQUEADO: esta fruta no está asignada a tu personaje (la recibes por tirada 45, compra 46 o recompensa).';
    }

    // Consume el objeto del inventario (19.7: tamaño mediano, 1 ranura).
    $objeto_id = ope7_akuma_objeto_id($akuma_id);
    if ($objeto_id > 0 && ope7_tabla_existe('inventario_personaje')) {
        $db->delete_query('ope_inventario_personaje', "personaje_id = {$pid} AND objeto_id = {$objeto_id} AND cantidad > 0", '', 1);
    }

    // Asigna el poder (cupo mundial consumado).
    $db->update_query('ope_personajes', array('akuma_id' => $akuma_id), "id = {$pid}");

    // D5.3: defectos exigidos y dotes exclusivas de la ficha (balanza a 0).
    $inf = ope7_akuma_influencia($akuma);
    $notas = array();
    if ($inf['dotes'] || $inf['defectos']) {
        $datos = json_decode((string) ($f['datos'] ?? 'null'), true);
        if (!is_array($datos)) {
            $datos = array();
        }
        $datos['dotes_akuma']    = array_values(array_unique(array_merge((array) ($datos['dotes_akuma'] ?? array()), $inf['dotes'])));
        $datos['defectos_akuma'] = array_values(array_unique(array_merge((array) ($datos['defectos_akuma'] ?? array()), $inf['defectos'])));
        $db->update_query('ope_personajes', array('datos' => json_encode($datos, JSON_UNESCAPED_UNICODE)), "id = {$pid}");
        if ($inf['defectos']) {
            $notas[] = 'defectos exigidos: ' . implode(', ', $inf['defectos']);
        }
        if ($inf['dotes']) {
            $notas[] = 'dotes exclusivas: ' . implode(', ', $inf['dotes']);
        }
        if ($inf['balanza'] !== 0) {
            $notas[] = 'AVISO: la balanza de dotes/defectos de esta fruta no suma 0 (' . $inf['balanza'] . ') — revísalo en la ficha.';
        }
    }

    return 'Fruta comida: «' . $akuma['nombre_propio'] . '» (T' . (int) $akuma['tier'] . ', ' . $akuma['familia']
        . ') asignada a tu ficha. ' . ($notas ? implode(' · ', $notas) . '.' : '')
        . ' La ficha de 8 bloques de la fruta (puertas, debilidades, despertar) rige desde ahora — léela en tu hoja.';
}

/** Ventana del Conquistador pendiente (nv5/15/25/35/45) o 0 si no hay. */
function ope7_conquistador_ventana($nivel, $personaje_id = 0)
{
    global $db;
    $nivel = (int) $nivel;
    $personaje_id = (int) $personaje_id;
    $ventanas = array(45, 35, 25, 15, 5);
    foreach ($ventanas as $w) {
        if ($nivel >= $w) {
            $prob = array(5 => 3, 15 => 8, 25 => 15, 35 => 25, 45 => 40);
            if ($personaje_id > 0 && ope7_tabla_existe('haki_conquistador')) {
                $q = $db->simple_select('ope_haki_conquistador', 'id', "personaje_id = {$personaje_id} AND intento_nivel = {$w}", array('limit' => 1));
                if ($db->num_rows($q)) {
                    continue; // ventana ya intentada
                }
            }
            return array('nivel' => $w, 'prob' => $prob[$w]);
        }
    }
    return null;
}

/** Efecto 50 · Tirada del Conquistador (nv5+ cada 10 niveles; 100 % automático, 20.1.2). */
function ope7_efecto_tirada_conquistador($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes')) {
        return 'Tirada del Conquistador: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Tirada del Conquistador: personaje no encontrado.';
    }
    $nivel = (int) $f['nivel'];
    $ventana = ope7_conquistador_ventana($nivel, $pid);
    if (!$ventana) {
        return 'Tirada del Conquistador BLOQUEADA: es a partir de nv5 y una vez por ventana (nv5/15/25/35/45). Ya agotaste la tuya o aún no toca.';
    }
    $w = (int) $ventana['nivel'];
    $prob = (int) $ventana['prob'];

    // La única tirada del sistema de Haki: solo decide qué obtienes (20.1.2).
    $exito = mt_rand(1, 100) <= $prob;

    if (ope7_tabla_existe('haki_conquistador')) {
        $db->insert_query('ope_haki_conquistador', array(
            'personaje_id' => (int) $pid,
            'intento_nivel' => $w,
            'prob'          => $prob,
            'exito'         => $exito ? 1 : 0,
            'tramite_id'    => (int) ($tr['id'] ?? 0),
            'fecha'         => TIME_NOW,
        ));
    }

    if (!$exito) {
        return 'La voluntad aún no despierta: tirada del Conquistador en la ventana nv' . $w . ' fallida (' . $prob . ' %). '
            . 'Intento registrado — tu próxima ventana es nv' . ($w + 10) . '. El Haki del Rey no se ordena: se demuestra.';
    }

    // Éxito: nivel 1 + histórico + suceso de Mundo Vivo en borrador (D5.4).
    // INSERT IGNORE (uq_pj_tipo): idempotente ante doble envío — mismo patrón
    // que el cron de auto-despertar (duplicate entry en concurrencia).
    if (ope7_tabla_existe('haki')) {
        $db->write_query("INSERT IGNORE INTO " . ope7_tabla_full('haki')
            . " (personaje_id, tipo, nivel, usos_acumulados, pp_invertidos, activo)"
            . " VALUES (" . (int) $pid . ", 'conquistador', 1, 0, 0, 1)");
    }
    if (ope7_tabla_existe('haki_historico')) {
        $db->insert_query('ope_haki_historico', array(
            'personaje_id' => (int) $pid,
            'tipo'         => 'conquistador',
            'nivel_desde'  => 0,
            'nivel_hasta'  => 1,
            'usos'         => 0,
            'pp'           => 0,
            'motivo'       => 'Obtención por tirada del Conquistador (ventana nv' . $w . ', ' . $prob . ' %).',
            // Sin tema_cierre_id: el default de la columna es NULL (MyBB null → '').
            'firmado_por'    => (int) ($tr['firma_staff'] ?? 0),
            'fecha'          => TIME_NOW,
        ));
    }
    // Suceso de Mundo Vivo en borrador (activo=0): el staff lo publica cuando toca la ronda.
    if (ope7_tabla_existe('sucesos')) {
        $ronda = 0;
        if (function_exists('ope7_ronda_activa')) {
            $ra = ope7_ronda_activa();
            $ronda = (int) ($ra['numero'] ?? 0);
        }
        $db->insert_query('ope_sucesos', array(
            'isla_id'     => (int) ($f['ubicacion_isla_id'] ?? 0),
            'ronda'       => $ronda,
            'tipo'        => 'haki',
            'titulo'      => 'El mundo oye al Rey: despierta el Haki del Conquistador',
            'descripcion' => '«' . $f['nombre'] . '» ha despertado el Haki del Conquistador (nv' . $w . ', tirada del ' . $prob . ' %). Su voluntad barre la masa — suceso en borrador: publícalo cuando toque la ronda.',
            'impacto'     => json_encode(array('F_suceso' => 1, 'borrador' => 1), JSON_UNESCAPED_UNICODE),
            'activo'      => 0,
        ));
    }

    return 'EL MUNDO OYE AL REY: tirada del Conquistador en la ventana nv' . $w . ' acertada (' . $prob . ' %). '
        . 'Nivel 1 de Haki del Conquistador creado en tu ficha (Pulso del Rey N1, 20.4). '
        . 'Suceso de Mundo Vivo generado en borrador — el staff lo publica cuando toque la ronda.';
}

// ─────────────────────────────────────────────────────────────
// 5.19 — Subida de Haki (trámite 51)
// ─────────────────────────────────────────────────────────────

/** VOL efectiva (20.3): base + bonus de raza por encima del techo (las dotes del seed no suman atributo numérico). */
function ope7_haki_vol_efectiva($f)
{
    $vol = (int) ($f['vol'] ?? 0);
    if (function_exists('ope7_pj_modificadores_efectivos')) {
        $mods = ope7_pj_modificadores_efectivos($f);
        $vol += (int) ($mods['vol'] ?? 0);
    }
    return max(0, $vol);
}

/** Despertar automático de Armadura/Mantra a nv10 (20.1). Idempotente. Devuelve cuántas creó. */
function ope7_haki_auto_despertar($f)
{
    global $db;
    if (!ope7_tabla_existe('haki')) {
        return 0;
    }
    $pid = (int) ($f['id'] ?? 0);
    if ($pid < 1 || (int) ($f['nivel'] ?? 0) < 10) {
        return 0;
    }
    $n = 0;
    foreach (array('armadura', 'mantra') as $tipo) {
        // Carrera real corregida: el cron corre en cada global_start y dos
        // peticiones concurrentes podían COMPROBAR la fila y luego INSERTARLA a
        // la vez → duplicate entry en uq_pj_tipo (17881-mantra). INSERT IGNORE
        // absorbe el duplicado y affected_rows() distingue el insert real.
        $db->write_query("INSERT IGNORE INTO " . ope7_tabla_full('haki')
            . " (personaje_id, tipo, nivel, usos_acumulados, pp_invertidos, activo)"
            . " VALUES ({$pid}, '{$tipo}', 1, 0, 0, 1)");
        if ($db->affected_rows() > 0) {
            $n++;
        }
    }
    return $n;
}

/** Despertar automático masivo para el cron (todos los aprobados nv≥10). */
function ope7_haki_auto_despertar_cron()
{
    global $db;
    if (!ope7_tabla_existe('haki') || !ope7_tabla_existe('personajes')) {
        return 0;
    }
    $q = $db->simple_select('ope_personajes', '*', "estado = 'aprobado' AND estado_vida = 'activa' AND nivel >= 10", array('limit' => 200));
    $n = 0;
    while ($f = $db->fetch_array($q)) {
        $n += ope7_haki_auto_despertar($f);
    }
    return $n;
}

/** Escalera de subida (20.1/20.2): usos + PP + VOL por nivel objetivo. Números cerrados. */
function ope7_haki_escalera($nivel_objetivo)
{
    $tabla = array(
        2 => array('usos' => 6, 'pp' => 200, 'vol' => 55),
        3 => array('usos' => 8, 'pp' => 300, 'vol' => 70),
        4 => array('usos' => 10, 'pp' => 400, 'vol' => 85),
        5 => array('usos' => 12, 'pp' => 500, 'vol' => 95),
    );
    return $tabla[(int) $nivel_objetivo] ?? null;
}

/** Efecto 51 · Subida de nivel de Haki (ia: el staff firma; el efecto valida y aplica). */
function ope7_efecto_subida_haki($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('haki')) {
        return 'Subida de Haki: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Subida de Haki: personaje no encontrado.';
    }
    $tipo = (string) ($ids['tipo'] ?? '');
    if (!in_array($tipo, array('armadura', 'mantra', 'conquistador'), true)) {
        return 'Subida de Haki BLOQUEADA: indica un tipo válido (armadura/mantra/conquistador).';
    }
    // Armadura/Mantra se despiertan solos a nv10 (20.1); el Conquistador solo por tirada 50.
    if ($tipo !== 'conquistador') {
        ope7_haki_auto_despertar($f);
    }
    $hq = $db->simple_select('ope_haki', '*', "personaje_id = {$pid} AND tipo = '{$tipo}'", array('limit' => 1));
    $h = $db->fetch_array($hq);
    if (!$h) {
        return 'Subida de Haki BLOQUEADA: el tipo «' . $tipo . '» no está despierto en tu ficha (Armadura/Mantra se despiertan solos a nv10; el Conquistador solo por la tirada 50).';
    }
    $nivel = (int) $h['nivel'];
    if ($nivel >= 5) {
        return 'Subida de Haki BLOQUEADA: ya estás en N5 (' . $tipo . ').';
    }
    $req = ope7_haki_escalera($nivel + 1);
    if (!$req) {
        return 'Subida de Haki BLOQUEADA: no existe el escalón N' . ($nivel + 1) . '.';
    }
    if ((int) $h['usos_acumulados'] < (int) $req['usos']) {
        return 'Subida de Haki BLOQUEADA: necesitas ' . $req['usos'] . ' usos satisfactoria (1 por tipo y por tema, 20.2) y tienes ' . (int) $h['usos_acumulados'] . '.';
    }
    $saldo = (int) ($f['pp_saldo'] ?? 0);
    if ($saldo < (int) $req['pp']) {
        return 'Subida de Haki BLOQUEADA: necesitas ' . $req['pp'] . ' PP y tienes ' . $saldo . ' (la escalera 200/300/400/500 se paga entera — la adaptabilidad humana no aplica, 20.2).';
    }
    $vol = ope7_haki_vol_efectiva($f);
    if ($vol < (int) $req['vol']) {
        return 'Subida de Haki BLOQUEADA: VOL efectiva ' . $vol . ' < ' . $req['vol'] . ' (requisito de Voluntad 20.3 — solo cuentan los bonus de raza/dote por encima del techo).';
    }

    // Aplicar: descuento + nivel + libro de PP + histórico (posteo en la hoja = la ficha lo muestra).
    $db->update_query('ope_personajes', array('pp_saldo' => $saldo - (int) $req['pp']), "id = {$pid}");
    $db->update_query('ope_haki', array('nivel' => $nivel + 1, 'pp_invertidos' => (int) $h['pp_invertidos'] + (int) $req['pp']), "id = " . (int) $h['id']);
    if (ope7_tabla_existe('historico_pp')) {
        $db->insert_query('ope_historico_pp', array(
            'personaje_id' => (int) $pid,
            'cantidad'     => -(int) $req['pp'],
            'concepto'     => 'Subida de Haki: ' . $tipo . ' → N' . ($nivel + 1) . ' (' . (int) $req['usos'] . ' usos, VOL ' . (int) $req['vol'] . ')',
            'tramite_id'   => (int) ($tr['id'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }
    if (ope7_tabla_existe('haki_historico')) {
        $db->insert_query('ope_haki_historico', array(
            'personaje_id' => (int) $pid,
            'tipo'         => $tipo,
            'nivel_desde'  => $nivel,
            'nivel_hasta'  => $nivel + 1,
            'usos'         => (int) $h['usos_acumulados'],
            'pp'           => (int) $req['pp'],
            'motivo'       => $db->escape_string((string) ($tr['motivo'] ?? '')),
            'firmado_por'  => (int) ($tr['firma_staff'] ?? 0),
            'fecha'        => TIME_NOW,
        ));
    }
    return 'Subida aceptada: ' . $tipo . ' N' . $nivel . ' → N' . ($nivel + 1) . ' por ' . (int) $req['pp'] . ' PP ('
        . (int) $req['usos'] . ' usos acumulados, VOL ' . (int) $req['vol'] . '). Publicado en tu hoja.';
}

/** Conteo de usos de Haki al cierre de tema (20.2): +1 por tipo despierto y por tema. */
function ope7_haki_contar_usos_cierre($pid, array $tipos, $tema_id = 0)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('haki')) {
        return 0;
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 0;
    }
    ope7_haki_auto_despertar($f);
    $n = 0;
    foreach ($tipos as $tipo) {
        if (!in_array($tipo, array('armadura', 'mantra', 'conquistador'), true)) {
            continue;
        }
        $hq = $db->simple_select('ope_haki', 'id, usos_acumulados', "personaje_id = {$pid} AND tipo = '{$tipo}'", array('limit' => 1));
        $h = $db->fetch_array($hq);
        if (!$h) {
            continue;
        }
        $db->update_query('ope_haki', array('usos_acumulados' => (int) $h['usos_acumulados'] + 1), "id = " . (int) $h['id']);
        $n++;
    }
    return $n;
}

// ─────────────────────────────────────────────────────────────
// 5.18 — Despertar (trámite 48) y adaptación bajo demanda (49)
// ─────────────────────────────────────────────────────────────

/** Banda de nivel mínima para despertar (19.4/19.6): T1–T2 nv25 · T3–T4 nv32 · Logia/mitológica nv40. */
function ope7_akuma_nivel_despertar($akuma)
{
    $tier = (int) ($akuma['tier'] ?? 0);
    $familia = (string) ($akuma['familia'] ?? '');
    $rareza = (string) ($akuma['rareza'] ?? '');
    if ($familia === 'logia' || $rareza === 'mitologica' || $tier >= 5) {
        return 40;
    }
    if ($tier >= 3) {
        return 32;
    }
    return 25;
}

/** Antigüedad on-roll (meses) como portador desde la obtención (19.6, ratio del calendario 5.6). */
function ope7_akuma_meses_portador($pid, $akuma_id)
{
    global $db;
    $pid = (int) $pid;
    $akuma_id = (int) $akuma_id;
    if ($pid < 1 || $akuma_id < 1 || !ope7_tabla_existe('akuma_historico') || !ope7_tabla_existe('calendario_foro')) {
        return 0;
    }
    $q = $db->simple_select('ope_akuma_historico', 'fecha', "akuma_id = {$akuma_id} AND portador_id = {$pid} AND tipo_evento = 'obtencion'", array('order_by' => 'fecha', 'order_dir' => 'ASC', 'limit' => 1));
    $fecha = (int) $db->fetch_field($q, 'fecha');
    if ($fecha < 1) {
        return 0;
    }
    $q = $db->simple_select('ope_calendario_foro', 'ratio', '1=1', array('limit' => 1));
    $ratio = max(1, (float) $db->fetch_field($q, 'ratio'));
    $segundos_onroll = max(0, TIME_NOW - $fecha) * $ratio;
    return (int) floor($segundos_onroll / (86400 * 30.44));
}

/** Temas cerrados del personaje (trámite 2 publicado = cierre con efecto). */
function ope7_akuma_temas_cerrados($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('tramites')) {
        return 0;
    }
    $q = $db->simple_select('ope_tramites', 'COUNT(*) AS n', "personaje_id = {$pid} AND numero = 2 AND estado = 'publicado'");
    return (int) $db->fetch_field($q, 'n');
}

/**
 * Efecto 48 · Despertar de akuma (19.4/19.6, ia + firma).
 * Requisitos cerrados: fruta comida, banda de nivel por tier/familia (T1–T2 nv25 ·
 * T3–T4 nv32 · Logia/mitológica nv40) y no despertada antes. La antigüedad, los
 * temas cerrados y la VOL se reportan al staff (la IA propone, el staff firma).
 */
function ope7_efecto_despertar_akuma($pid, $ids, $tr)
{
    global $db;
    if ($pid < 1 || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('akumas') || !ope7_tabla_existe('despertares')) {
        return 'Despertar: tablas no migradas (pendiente).';
    }
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    $f = $db->fetch_array($q);
    if (!$f) {
        return 'Despertar: personaje no encontrado.';
    }
    $akuma_id = (int) ($ids['akuma_id'] ?? (int) ($f['akuma_id'] ?? 0));
    if ($akuma_id < 1) {
        return 'Despertar BLOQUEADO: tu ficha no tiene una fruta comida (trámite 47). El despertar es la cúspide de una fruta ya asimilada (19.6).';
    }
    $akuma = ope7_akuma_info($akuma_id);
    if (!$akuma) {
        return 'Despertar BLOQUEADO: la fruta no está en el catálogo.';
    }
    // Ya despertada (histórico auditable `despertares`).
    $dq = $db->simple_select('ope_despertares', 'id', "akuma_id = {$akuma_id} AND personaje_id = {$pid}", array('limit' => 1));
    if ($db->num_rows($dq)) {
        return 'Despertar BLOQUEADO: esta fruta ya está despertada por tu personaje (una vez por fruta y por portador, 19.6).';
    }
    // Banda de nivel por tier/familia (números cerrados, 19.4).
    $nivel = (int) $f['nivel'];
    $nivel_min = ope7_akuma_nivel_despertar($akuma);
    if ($nivel < $nivel_min) {
        return 'Despertar BLOQUEADO: la «' . $akuma['nombre_propio'] . '» (T' . (int) $akuma['tier'] . ' ' . $akuma['familia']
            . ') exige nv' . $nivel_min . '+ y tienes nv' . $nivel . ' (bandas 19.4: T1–T2 nv25 · T3–T4 nv32 · Logia/mitológica nv40).';
    }

    // Datos para la firma (la skill propone, el staff decide con criterio).
    $meses = ope7_akuma_meses_portador($pid, $akuma_id);
    $temas = ope7_akuma_temas_cerrados($pid);
    $vol = (int) ($f['vol'] ?? 0);

    $db->insert_query('ope_despertares', array(
        'akuma_id'    => $akuma_id,
        'personaje_id' => $pid,
        'tramite_id'  => (int) ($tr['id'] ?? 0),
        'fecha'       => TIME_NOW,
    ));

    // Suceso de Mundo Vivo en borrador: el despertar de una Logia es historia
    // del periódico (19.4 → 5.14); Zoan colosal / Paramecia transmuta también
    // se anotan como rumor de ronda si el staff lo considera.
    if (ope7_tabla_existe('sucesos')) {
        $ronda = 0;
        if (function_exists('ope7_ronda_activa')) {
            $ra = ope7_ronda_activa();
            $ronda = (int) ($ra['numero'] ?? 0);
        }
        $desp = is_array($akuma['despertar'] ?? null) ? $akuma['despertar'] : array();
        $db->insert_query('ope_sucesos', array(
            'isla_id'     => (int) ($f['ubicacion_isla_id'] ?? 0),
            'ronda'       => $ronda,
            'tipo'        => 'akuma',
            'titulo'      => 'La cúspide del poder: despierta «' . $akuma['nombre_propio'] . '»',
            'descripcion' => '«' . $f['nombre'] . '» ha despertado su fruta (T' . (int) $akuma['tier'] . ' ' . $akuma['familia']
                . '). ' . (string) ($desp['resumen'] ?? 'El despertar sostiene sus técnicas sin PE extra, con mantenimiento por turno (19.6).')
                . ' — suceso en borrador: publícalo cuando toque la ronda.',
            'impacto'     => json_encode(array('F_suceso' => 1, 'borrador' => 1), JSON_UNESCAPED_UNICODE),
            'activo'      => 0,
        ));
    }

    return 'Despertar firmado: «' . $akuma['nombre_propio'] . '» despierta para «' . $f['nombre']
        . '». Antigüedad como portador ≈ ' . $meses . ' mes(es) on-roll · temas cerrados: ' . $temas . ' · VOL ' . $vol
        . '. El despertar sostiene tus técnicas de fruta (sin PE extra) con su mantenimiento de PE por turno y reposos/puertas intactos (19.6).'
        . ($akuma['familia'] === 'logia' ? ' Suceso de Mundo Vivo generado en borrador (el periódico lo contará).' : '');
}

/**
 * Efecto 49 · Adaptación de fruta bajo demanda (staff, skill-adaptacion-akumas).
 * El staff firma la ficha de 8 bloques (resultado editado en la bandeja) y este
 * efecto da de alta la fruta en el catálogo + su objeto «Fruta: X» para que
 * circule por las tres vías (45/46/recompensas) con el cupo mundial.
 */
function ope7_efecto_adaptar_akuma($tr, $pid, $res, $ids)
{
    global $db;
    if (!ope7_tabla_existe('akumas') || !ope7_tabla_existe('objetos')) {
        return 'Adaptación de fruta: tablas no migradas (pendiente).';
    }
    $ficha = is_array($res) ? $res : array();
    $nombre = trim((string) ($ficha['nombre_propio'] ?? ''));
    if ($nombre === '') {
        return 'Adaptación de fruta BLOQUEADA: falta el nombre propio (bloque 1 de la ficha, 5.18).';
    }
    $familia = (string) ($ficha['familia'] ?? '');
    if (!in_array($familia, array('paramecia', 'zoan', 'logia'), true)) {
        return 'Adaptación de fruta BLOQUEADA: indica una familia válida (paramecia/zoan/logia).';
    }
    $tier = (int) ($ficha['tier'] ?? 0);
    if ($tier < 1 || $tier > 5) {
        return 'Adaptación de fruta BLOQUEADA: tier 1–5 (matriz 19.7).';
    }
    // Cupo mundial por nombre: el fruto es único (19.7).
    $q = $db->simple_select('ope_akumas', 'id', "nombre_propio = '" . $db->escape_string($nombre) . "'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return 'Adaptación de fruta BLOQUEADA: «' . $nombre . '» ya existe en el catálogo (fruto único, 19.7).';
    }

    $rareza = (string) ($ficha['rareza'] ?? '');
    if ($rareza !== '' && !in_array($rareza, array('comun', 'ancestral', 'mitologica'), true)) {
        $rareza = '';
    }
    $enc = function ($v) use ($db) {
        if ($v === null || $v === '') {
            return 'NULL';
        }
        $j = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
        return "'" . $db->escape_string($j) . "'";
    };
    // Raw SQL (como el seed): MyBB convierte null → '' y el ENUM `rareza` no lo acepta.
    $cols = array(
        'nombre_propio'       => "'" . $db->escape_string($nombre) . "'",
        'familia'             => "'" . $db->escape_string($familia) . "'",
        'rareza'              => $rareza !== '' ? "'" . $db->escape_string($rareza) . "'" : 'NULL',
        'tier'                => (int) $tier,
        'aspecto'             => "'" . $db->escape_string((string) ($ficha['aspecto'] ?? '')) . "'",
        'mecanica_base'       => $enc($ficha['mecanica_base'] ?? null),
        'puertas'             => $enc($ficha['puertas'] ?? null),
        'debilidades'         => $enc($ficha['debilidades'] ?? null),
        'requisitos_portador' => $enc($ficha['requisitos_portador'] ?? null),
        'influencia_ficha'    => $enc($ficha['influencia_ficha'] ?? null),
        'despertar'           => $enc($ficha['despertar'] ?? null),
        'precio_base'         => (int) ($ficha['precio_base'] ?? 0),
        'coste_pp'            => $enc($ficha['coste_pp'] ?? null),
        'portador_id'         => 'NULL',
        'origen'              => 'NULL',
        'estado'              => "'sin_portador'",
    );
    $db->query('INSERT INTO ' . ope7_tabla_full('akumas') . ' (' . implode(', ', array_keys($cols)) . ') VALUES (' . implode(', ', array_values($cols)) . ')');
    $nuevo_id = (int) $db->insert_id;
    // Objeto «Fruta: X» (categoria akuma) para que circule por las tres vías.
    $db->insert_query('ope_objetos', array(
        'nombre'       => 'Fruta: ' . $nombre,
        'categoria'    => 'akuma',
        'efecto_json'  => json_encode(array('akuma_id' => (int) $nuevo_id, 'tipo' => 'fruta_diablo'), JSON_UNESCAPED_UNICODE),
        'coste_pa'     => '',
        'reutilizable' => 0,
        'precio_base'  => (int) ($ficha['precio_base'] ?? 0),
        'dureza'       => 1,
        'ranuras'      => 1,
        'notas'        => 'Fruta del diablo (5.18): adaptación bajo demanda (49). Obtención por tirada (45), compra con PP (46) o eventos. No se vende en tiendas.',
    ));

    return 'Fruta «' . $nombre . '» (T' . $tier . ' ' . $familia . ') dada de alta en el catálogo con su ficha de 8 bloques'
        . ' y su objeto en el inventario. Ya puede circular por las tres vías (45/46/recompensas) con el cupo mundial.';
}

/** Publica un suceso del Conquistador o de despertar en borrador (activo 0 → 1). */
function ope7_akumas_publicar_suceso($suceso_id)
{
    global $db;
    $suceso_id = (int) $suceso_id;
    if ($suceso_id < 1 || !ope7_tabla_existe('sucesos')) {
        return false;
    }
    $db->update_query('ope_sucesos', array('activo' => 1), "id = {$suceso_id} AND tipo IN ('haki','akuma')");
    return true;
}

/** Panel «Akumas y Haki» (Anexo A.3, 19.7/20.5). */
function ope7_akumas_panel_html()
{
    global $db, $mybb;
    $h = array();
    $h[] = '<div class="shead"><h1>Akumas y Haki</h1><span class="sub">A.3 · 5.18/5.19 — frutas, cupos y Conquistador</span></div>';
    $h[] = '<p class="zs-intro">La fruta es un <b>bien único</b> (cupo mundial: un fruto, un portador, 19.7) y su ficha de 8 bloques es el contrato. '
        . 'Las tres vías de obtención son la tirada (45, automática), la compra con PP (46, familia automática; concepto/concreta firmadas) y las recompensas (5.14/5.12/5.15/5.13). '
        . 'Al morir un portador (62) la fruta renace y libera el cupo. El Conquistador (50) es automático: los sucesos quedan <b>en borrador</b> hasta que los publicas aquí.</p>';

    // ── Sucesos en borrador (Conquistador 50 + Despertar 48) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Sucesos en borrador</span><span class="c">Conquistador (50) · Despertar (48)</span></div><div class="plate-b">';
    if (ope7_tabla_existe('sucesos')) {
        $pend = false;
        $q = $db->query("SELECT s.*, i.nombre AS isla_nombre FROM " . ope7_tabla_full('sucesos') . " s "
            . "LEFT JOIN " . ope7_tabla_full('islas') . " i ON i.id = s.isla_id WHERE s.tipo IN ('haki','akuma') AND s.activo = 0 ORDER BY s.id DESC");
        while ($r = $db->fetch_array($q)) {
            $pend = true;
            $h[] = '<div class="zs-row"><div><b>' . htmlspecialchars_uni((string) $r['titulo']) . '</b>'
                . '<div class="zs-mut">' . htmlspecialchars_uni((string) $r['descripcion']) . '</div></div>'
                . '<form method="post" action="akumas-staff.php"><input type="hidden" name="my_post_key" value="' . htmlspecialchars_uni((string) $mybb->get_input('my_post_key')) . '">'
                . '<input type="hidden" name="gaccion" value="publicar_suceso">'
                . '<input type="hidden" name="suceso_id" value="' . (int) $r['id'] . '">'
                . '<button class="ope-btn" type="submit">Publicar</button></form></div>';
        }
        if (!$pend) {
            $h[] = '<p class="pj-empty">Sin sucesos en borrador (Conquistador o despertar de fruta).</p>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de sucesos no migrada.</p>';
    }
    $h[] = '</div></div>';

    // ── Adaptación de fruta bajo demanda (49, staff) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Fruta bajo demanda</span><span class="c">trámite 49 · skill-adaptacion-akumas</span></div><div class="plate-b">';
    $h[] = '<p class="zs-intro">Adapta una fruta de la obra desde nombre+concepto canon: la skill construye la ficha de 8 bloques '
        . '(guía maestra 5.18), tú la revisas, editas y firmas en la bandeja. Reglas: fruto único (cupo mundial 19.7), sin personajes/eventos canon como contenido, balanza a 0.</p>';
    $h[] = '<form method="post" action="akumas-staff.php">'
        . '<input type="hidden" name="my_post_key" value="' . htmlspecialchars_uni((string) $mybb->get_input('my_post_key')) . '">'
        . '<input type="hidden" name="gaccion" value="adaptar_fruta">'
        . '<div class="zs-row"><div><input class="zs-input" type="text" name="concepto" placeholder="Ej.: Moku Moku no Mi — humo que se vuelve atmósfera" required></div>'
        . '<button class="ope-btn" type="submit">Crear trámite 49</button></div></form>';
    $h[] = '</div></div>';

    // ── Catálogo y cupos mundiales ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Catálogo y cupos mundiales</span><span class="c">19.7 — un fruto, un portador</span></div><div class="plate-b">';
    if (ope7_tabla_existe('akumas')) {
        $q = $db->query('SELECT a.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('akumas') . ' a '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = a.portador_id ORDER BY a.tier, a.nombre_propio');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin frutas en el catálogo (ejecuta scripts/seed-7seas-akumas.php).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fruta</th><th>Familia</th><th>Rareza</th><th>Tier</th><th>Estado</th><th>Portador</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $rareza = (string) ($r['rareza'] ?? '');
                $h[] = '<tr><td><b>' . htmlspecialchars_uni((string) $r['nombre_propio']) . '</b></td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['familia']) . '</td>'
                    . '<td>' . ($rareza !== '' ? htmlspecialchars_uni($rareza) : '—') . '</td>'
                    . '<td>T' . (int) $r['tier'] . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['estado']) . '</td>'
                    . '<td>' . ((int) ($r['portador_id'] ?? 0) > 0 && $r['pj_nombre'] ? htmlspecialchars_uni((string) $r['pj_nombre']) : '—') . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de akumas no migrada.</p>';
    }
    $h[] = '</div></div>';

    // ── Plantilla de 8 bloques por fruta (A.3: «con la plantilla de 8 bloques») ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Ficha de 8 bloques</span><span class="c">plantilla 5.18 · contrato de la fruta</span></div><div class="plate-b">';
    if (ope7_tabla_existe('akumas')) {
        $q = $db->query('SELECT * FROM ' . ope7_tabla_full('akumas') . ' ORDER BY tier, nombre_propio');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin frutas (ejecuta scripts/seed-7seas-akumas.php).</p>';
        } else {
            $h[] = '<div class="zs-sub">Cada fruta muestra sus 8 bloques: identidad · mecánica base · puertas · debilidades · requisitos · influencia en la ficha · despertar · precio/vías.</div>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<details class="zs-ficha"><summary><b>' . htmlspecialchars_uni((string) $r['nombre_propio']) . '</b>'
                    . ' <span class="zs-mut">' . htmlspecialchars_uni((string) $r['familia']) . ' · T' . (int) $r['tier'] . ' · ' . htmlspecialchars_uni((string) $r['estado']) . '</span></summary>';
                $h[] = '<div class="zs-ficha-b">';
                $bloques = array(
                    'Aspecto'     => (string) $r['aspecto'],
                    'Mecánica'    => json_encode(json_decode((string) $r['mecanica_base'], true) ?: array()),
                    'Puertas'     => json_encode(json_decode((string) $r['puertas'], true) ?: array()),
                    'Debilidades' => json_encode(json_decode((string) $r['debilidades'], true) ?: array()),
                    'Requisitos'  => json_encode(json_decode((string) $r['requisitos_portador'], true) ?: array()),
                    'Influencia'  => json_encode(json_decode((string) $r['influencia_ficha'], true) ?: array()),
                    'Despertar'   => json_encode(json_decode((string) $r['despertar'], true) ?: array()),
                    'Precio'      => number_format((int) $r['precio_base'], 0, ',', '.') . ' ฿ · PP ' . (string) ($r['coste_pp'] ?? '—'),
                );
                foreach ($bloques as $k => $v) {
                    $h[] = '<div class="zs-bloque"><b>' . htmlspecialchars_uni($k) . ':</b> <span class="zs-mut">' . htmlspecialchars_uni((string) $v) . '</span></div>';
                }
                $h[] = '</div></details>';
            }
        }
    } else {
        $h[] = '<p class="pj-empty">Tabla de akumas no migrada.</p>';
    }
    $h[] = '</div></div>';

    // ── Pool de la tirada ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Pool de la tirada (45)</span><span class="c">19.7.1 — bandas por nivel</span></div><div class="plate-b">';
    if (ope7_tabla_existe('akuma_pool_tirada')) {
        $q = $db->simple_select('ope_akuma_pool_tirada', '*', '1=1', array('order_by' => 'tier_max'));
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Pool vacío (seed pendiente).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Nivel</th><th>Bandas</th><th>Mar/región</th><th>Activo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>nv3+</td><td>T1–T' . (int) $r['tier_max'] . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['mar_region']) . '</td>'
                    . '<td>' . ((int) $r['activo'] ? 'sí' : 'no') . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    }
    $h[] = '</div></div>';

    // ── Histórico de frutas (obtenciones/renacimientos) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Histórico de frutas</span><span class="c">akuma_historico</span></div><div class="plate-b">';
    if (ope7_tabla_existe('akuma_historico') && ope7_tabla_existe('akumas')) {
        $q = $db->query('SELECT h.*, a.nombre_propio, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('akuma_historico') . ' h '
            . 'JOIN ' . ope7_tabla_full('akumas') . ' a ON a.id = h.akuma_id '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = h.portador_id '
            . 'ORDER BY h.fecha DESC LIMIT 20');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Sin movimientos todavía (las tiradas 45 y compras 46 registran aquí su obtención).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Fruta</th><th>Evento</th><th>Vía</th><th>Personaje</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['nombre_propio']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['tipo_evento']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) ($r['via'] ?? '—')) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) ($r['pj_nombre'] ?? '—')) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    }
    $h[] = '</div></div>';

    // ── Despertares (48) ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Despertares</span><span class="c">trámite 48 · 19.6 — la cúspide</span></div><div class="plate-b">';
    if (ope7_tabla_existe('despertares') && ope7_tabla_existe('akumas') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT d.*, a.nombre_propio, a.tier, a.familia, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('despertares') . ' d '
            . 'JOIN ' . ope7_tabla_full('akumas') . ' a ON a.id = d.akuma_id '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = d.personaje_id ORDER BY d.fecha DESC LIMIT 20');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Nadie ha despertado su fruta todavía (trámite 48: nv25/32/40 por banda de tier/familia, 19.4).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Fruta</th><th>Portador</th><th>Trámite</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                    . '<td><b>' . htmlspecialchars_uni((string) $r['nombre_propio']) . '</b> (T' . (int) $r['tier'] . ' ' . htmlspecialchars_uni((string) $r['familia']) . ')</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['pj_nombre']) . '</td>'
                    . '<td>#' . (int) ($r['tramite_id'] ?? 0) . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
    } else {
        $h[] = '<p class="pj-empty">Tablas de despertares no migradas.</p>';
    }
    $h[] = '</div></div>';

    // ── Haki: niveles, intentos del Conquistador y subidas ──
    $h[] = '<div class="plate"><div class="plate-h"><span class="t">Haki</span><span class="c">20.5 — niveles, usos e intentos</span></div><div class="plate-b">';
    if (ope7_tabla_existe('haki') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT h.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('haki') . ' h '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = h.personaje_id ORDER BY p.nombre, h.tipo');
        if ($db->num_rows($q) === 0) {
            $h[] = '<p class="pj-empty">Nadie ha despertado Haki todavía (Armadura/Mantra se despiertan solos a nv10; el Conquistador por tirada 50).</p>';
        } else {
            $h[] = '<table class="zs-tab"><thead><tr><th>Personaje</th><th>Tipo</th><th>Nivel</th><th>Usos</th><th>PP invertidos</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $h[] = '<tr><td>' . htmlspecialchars_uni((string) $r['pj_nombre']) . '</td>'
                    . '<td>' . htmlspecialchars_uni((string) $r['tipo']) . '</td>'
                    . '<td>N' . (int) $r['nivel'] . '</td>'
                    . '<td>' . (int) $r['usos_acumulados'] . '</td>'
                    . '<td>' . (int) $r['pp_invertidos'] . '</td></tr>';
            }
            $h[] = '</tbody></table>';
        }
        if (ope7_tabla_existe('haki_conquistador')) {
            $q = $db->query('SELECT c.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('haki_conquistador') . ' c '
                . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = c.personaje_id ORDER BY c.fecha DESC LIMIT 20');
            if ($db->num_rows($q)) {
                $h[] = '<div class="zs-sub">Intentos del Conquistador (50)</div>';
                $h[] = '<table class="zs-tab"><thead><tr><th>Personaje</th><th>Ventana</th><th>Prob.</th><th>Resultado</th></tr></thead><tbody>';
                while ($r = $db->fetch_array($q)) {
                    $h[] = '<tr><td>' . htmlspecialchars_uni((string) $r['pj_nombre']) . '</td>'
                        . '<td>nv' . (int) $r['intento_nivel'] . '</td>'
                        . '<td>' . (int) $r['prob'] . ' %</td>'
                        . '<td>' . ((int) $r['exito'] ? '<b class="zs-ok">acierto</b>' : '<span class="zs-mut">fallo</span>') . '</td></tr>';
                }
                $h[] = '</tbody></table>';
            }
        }
        if (ope7_tabla_existe('haki_historico')) {
            $q = $db->query('SELECT h.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('haki_historico') . ' h '
                . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = h.personaje_id ORDER BY h.fecha DESC LIMIT 20');
            if ($db->num_rows($q)) {
                $h[] = '<div class="zs-sub">Subidas de Haki (51) — usos, PP y firma</div>';
                $h[] = '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Personaje</th><th>Tipo</th><th>Nivel</th><th>Usos</th><th>PP</th><th>Motivo</th></tr></thead><tbody>';
                while ($r = $db->fetch_array($q)) {
                    $h[] = '<tr><td>' . gmdate('d/m/Y', (int) $r['fecha']) . '</td>'
                        . '<td>' . htmlspecialchars_uni((string) $r['pj_nombre']) . '</td>'
                        . '<td>' . htmlspecialchars_uni((string) $r['tipo']) . '</td>'
                        . '<td>N' . (int) $r['nivel_desde'] . ' → N' . (int) $r['nivel_hasta'] . '</td>'
                        . '<td>' . (int) $r['usos'] . '</td>'
                        . '<td>' . (int) $r['pp'] . '</td>'
                        . '<td>' . htmlspecialchars_uni((string) $r['motivo']) . '</td></tr>';
                }
                $h[] = '</tbody></table>';
            }
        }
    } else {
        $h[] = '<p class="pj-empty">Tablas de Haki no migradas.</p>';
    }
    $h[] = '</div></div>';

    return implode("\n", $h);
}
