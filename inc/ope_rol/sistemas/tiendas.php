<?php
/**
 * One Piece: 7 Seas · Tiendas y mercado (F3.3)
 * ---------------------------------------------
 * Cap. 10 (5.9): tiendas NPC (compran al 50 % del precio de mercado, 10.5) y
 * tiendas de jugador — oficio Comerciante OBLIGATORIO (10.6), local o módulo
 * de barco, capital y stock real. Surtido máx. 10 ítems · margen −20 %/+30 %
 * · stock 10 consumibles / 3 armas · zona libre (D3.2: 'mundo abierto' hasta
 * F4). Cada transacción se registra (anti-abuso: sin auto-compra).
 * Números cerrados del manual — no recalibrar.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Suspende las tiendas de un territorio cuando cambia de manos (5.15/16.6):
 * al conquistar una isla, las tiendas del bando anterior dejan de operar
 * (estado 'suspendida') hasta que el dueño recupere el control o las mueva.
 * Devuelve el número de tiendas suspendidas. Lo llama la conquista (F4.2).
 */
function ope7_tiendas_suspender_en_isla($isla_id)
{
    global $db;
    $isla_id = (int) $isla_id;
    if ($isla_id < 1 || !ope7_tabla_existe('tiendas')) {
        return 0;
    }
    $db->update_query('ope_tiendas', array('estado' => 'suspendida'), "zona_id = {$isla_id} AND estado = 'activa'");
    return (int) $db->affected_rows();
}

/** Nivel del personaje en un oficio (0 si no lo tiene). */
function ope7_pj_dominio_nivel($pid, $nombre)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('dominios') || !ope7_tabla_existe('dominios_personaje')) {
        return 0;
    }
    $q = $db->query('SELECT dp.nivel FROM ' . ope7_tabla_full('dominios_personaje') . ' dp '
        . 'JOIN ' . ope7_tabla_full('dominios') . ' d ON d.id = dp.dominio_id '
        . 'WHERE dp.personaje_id = ' . $pid . " AND d.nombre = '" . $db->escape_string((string) $nombre) . "' LIMIT 1");
    $r = $db->fetch_array($q);
    return $r ? (int) $r['nivel'] : 0;
}

/** Precio de mercado actual de un objeto (10.4): último de precios_mercado o base. */
function ope7_precio_mercado($objeto_id, $zona_id = 0)
{
    global $db;
    $objeto_id = (int) $objeto_id;
    $zona_id = (int) $zona_id;
    if ($objeto_id < 1) {
        return 0;
    }
    if (ope7_tabla_existe('precios_mercado')) {
        $q = $db->simple_select('ope_precios_mercado', 'precio_actual', "objeto_id = {$objeto_id} AND zona_id = {$zona_id}",
            array('limit' => 1, 'order_by' => 'id', 'order_dir' => 'DESC'));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'precio_actual');
        }
    }
    if (ope7_tabla_existe('objetos')) {
        $q = $db->simple_select('ope_objetos', 'precio_base', "id = {$objeto_id}", array('limit' => 1));
        return (int) $db->fetch_field($q, 'precio_base');
    }
    return 0;
}

/** ¿El objeto es bélico? (armas, armaduras, escudos, venenos — 10.6). */
function ope7_objeto_es_belico($obj)
{
    $cat = (string) ($obj['categoria'] ?? '');
    if (in_array($cat, array('arma', 'armadura', 'escudo'), true)) {
        return true;
    }
    $efecto = json_decode((string) ($obj['efecto_json'] ?? ''), true);
    return is_array($efecto) && isset($efecto['estado']) && strpos((string) $efecto['estado'], 'envenenado') !== false;
}

/**
 * Apertura de tienda (trámite 15, 10.6): Comerciante nv1+ (o nv3+ para
 * Mercader/Tasador), local declarado, capital/stock real y bélicos del
 * catálogo validado. Crea la fila en `tiendas`. Devuelve msg.
 */
function ope7_efecto_abrir_tienda($pid, $res)
{
    global $db;
    $tipo = in_array((string) ($res['tipo'] ?? ''), array('oficio', 'reventa'), true) ? (string) $res['tipo'] : 'oficio';
    $local = trim((string) ($res['local'] ?? ''));
    $margen = isset($res['margen']) ? (float) $res['margen'] : 0.0;
    $isla_id = (int) ($res['isla_id'] ?? 0);
    if ($pid < 1 || !ope7_tabla_existe('tiendas')) {
        return 'Apertura de tienda: módulo no disponible (pendiente).';
    }
    // F4.1 — la tienda se ancla al territorio (D4): la zona es una isla del
    // catálogo (o zona clave). Sin isla declarada se rechaza (10.6: local en
    // territorio); el módulo de barco se declara en el local (5.17).
    if ($isla_id < 1 || !ope7_tabla_existe('islas')) {
        return 'Apertura BLOQUEADA: falta la isla del catálogo donde abres (D4: la zona de la tienda es su isla).';
    }
    $iq = $db->simple_select('ope_islas', 'id', "id = {$isla_id}", array('limit' => 1));
    if (!$db->fetch_field($iq, 'id')) {
        return 'Apertura BLOQUEADA: la isla ' . $isla_id . ' no está en el catálogo del mundo (5.14).';
    }
    // La tienda exige estar en el territorio (5.15/16.6): el personaje debe
    // estar anclado en esa isla (o tener el local en el barco con módulo).
    if (ope7_tabla_existe('personajes')) {
        $pq = $db->simple_select('ope_personajes', 'ubicacion_isla_id', "id = {$pid}", array('limit' => 1));
        $ubi = (int) $db->fetch_field($pq, 'ubicacion_isla_id');
        if ($ubi > 0 && $ubi !== $isla_id) {
            return 'Apertura BLOQUEADA: tu personaje está en otra isla — el local debe estar donde estás tú (o en tu barco con módulo de tienda).';
        }
    }
    $nv = ope7_pj_dominio_nivel($pid, 'Comerciante');
    if ($nv < 1) {
        return 'Apertura BLOQUEADA: se requiere el oficio Comerciante (10.6) — sin el oficio no hay tienda.';
    }
    if ($tipo === 'reventa' && $nv < 3) {
        return 'Apertura BLOQUEADA: la reventa pura exige Comerciante nv3+ (rama Mercader o Tasador, 10.6).';
    }
    if ($local === '') {
        return 'Apertura BLOQUEADA: falta el local (puesto narrado, local en territorio o módulo de barco — 10.6).';
    }
    if ($margen < -0.20 || $margen > 0.30) {
        return 'Apertura BLOQUEADA: el margen sale de la banda −20 %/+30 % sobre el mercado de la zona (10.4).';
    }
    // Los ítems bélicos deben existir en el catálogo validado.
    foreach ((array) ($res['items'] ?? array()) as $it) {
        $oid = (int) ($it['objeto_id'] ?? 0);
        if ($oid < 1) {
            continue;
        }
        $q = $db->simple_select('ope_objetos', '*', "id = {$oid} AND activo = 1", array('limit' => 1));
        $obj = $db->fetch_array($q);
        if (!$obj) {
            return 'Apertura BLOQUEADA: ítem ' . $oid . ' no está en el catálogo validado (10.6).';
        }
        if (ope7_objeto_es_belico($obj) && empty($obj['efecto_json'])) {
            return 'Apertura BLOQUEADA: ítem bélico sin ficha validada (nada de inventar efectos al vender).';
        }
    }
    // El stock sale del almacén real (10.6): comprobación de existencia.
    foreach ((array) ($res['items'] ?? array()) as $it) {
        $oid = (int) ($it['objeto_id'] ?? 0);
        $cant = max(1, (int) ($it['stock'] ?? 1));
        if ($oid < 1) {
            continue;
        }
        $aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$oid}", array('limit' => 1));
        if ((int) $db->fetch_field($aq, 'cantidad') < $cant) {
            return 'Apertura BLOQUEADA: sin ' . $cant . ' × «' . ope7_objeto_nombre($oid) . '» en tu almacén (el stock sale del inventario real).';
        }
    }
    $tienda_id = (int) $db->insert_query('ope_tiendas', array(
        'dueno_id' => $pid, 'zona_id' => $isla_id, 'tipo' => $tipo,
        'local' => $local, 'estado' => 'activa', 'capital' => (int) ($res['capital'] ?? 0),
        'banda_margen' => ($margen >= 0 ? '+' : '') . round($margen * 100) . '%',
        'notas' => 'Tienda anclada a la isla ' . $isla_id . ' (F4.1, D4): el territorio la sostiene.',
    ));
    $n_items = 0;
    foreach ((array) ($res['items'] ?? array()) as $it) {
        $oid = (int) ($it['objeto_id'] ?? 0);
        $stock = max(1, (int) ($it['stock'] ?? 1));
        if ($oid < 1) {
            continue;
        }
        $q = $db->simple_select('ope_objetos', '*', "id = {$oid}", array('limit' => 1));
        $obj = $db->fetch_array($q);
        // 10.4: el precio de venta se ancla al mercado de LA ZONA de la tienda
        // (su isla), no al mercado global — así apertura y compra validan la
        // misma referencia y el boletín de la isla manda.
        $precio = (int) round(ope7_precio_mercado($oid, $isla_id) * (1 + $margen));
        $clas = ope7_objeto_es_belico($obj) ? 'belico' : 'normal';
        $db->insert_query('ope_tienda_items', array(
            'tienda_id' => $tienda_id, 'objeto_id' => $oid, 'precio_venta' => $precio,
            'stock' => $stock, 'clasificacion' => $clas, 'origen' => $tipo === 'reventa' ? 'compra' : 'produccion',
        ));
        // 10.6: el stock expuesto sale del inventario real (almacén).
        $db->query('UPDATE ' . ope7_tabla_full('almacen') . ' SET cantidad = cantidad - ' . $stock . ' WHERE personaje_id = ' . $pid . ' AND objeto_id = ' . $oid);
        $db->delete_query('ope_almacen', "personaje_id = {$pid} AND objeto_id = {$oid} AND cantidad <= 0");
        $n_items++;
    }
    return 'Tienda abierta (Comerciante nv' . $nv . ', ' . $tipo . ') con ' . $n_items . ' ítems · margen ' . $res['margen'] . ' · local: ' . $local;
}

/**
 * Cierre/reapertura de tienda (trámite 16): al cerrar los ítems vuelven al
 * almacén; suspensión hasta reabrir con trámite (10.6).
 */
function ope7_efecto_tienda_cierre($pid, $res)
{
    global $db;
    $tienda_id = (int) ($res['tienda_id'] ?? 0);
    $modo = (string) ($res['modo'] ?? 'cerrar');
    if ($pid < 1 || $tienda_id < 1 || !ope7_tabla_existe('tiendas')) {
        return 'Cierre de tienda: datos incompletos.';
    }
    $q = $db->simple_select('ope_tiendas', 'dueno_id', "id = {$tienda_id}", array('limit' => 1));
    if ((int) $db->fetch_field($q, 'dueno_id') !== $pid) {
        return 'Cierre BLOQUEADO: no eres el dueño de esa tienda.';
    }
    if ($modo === 'cerrar') {
        // Ítems al almacén (10.6: al cerrar, el stock expuesto vuelve al inventario real).
        $iq = $db->simple_select('ope_tienda_items', 'objeto_id, stock', "tienda_id = {$tienda_id}");
        while ($it = $db->fetch_array($iq)) {
            $oid = (int) $it['objeto_id'];
            $stock = (int) $it['stock'];
            $aq = $db->simple_select('ope_almacen', 'id, cantidad', "personaje_id = {$pid} AND objeto_id = {$oid}", array('limit' => 1));
            if ($db->num_rows($aq)) {
                $row = $db->fetch_array($aq);
                $db->update_query('ope_almacen', array('cantidad' => (int) $row['cantidad'] + $stock), "id = " . (int) $row['id']);
            } else {
                $db->insert_query('ope_almacen', array('personaje_id' => $pid, 'objeto_id' => $oid, 'cantidad' => $stock));
            }
        }
        $db->delete_query('ope_tienda_items', "tienda_id = {$tienda_id}");
        $db->update_query('ope_tiendas', array('estado' => 'cerrada'), "id = {$tienda_id}");
        return 'Tienda ' . $tienda_id . ' cerrada: ítems devueltos al almacén.';
    }
    // Reapertura.
    $db->update_query('ope_tiendas', array('estado' => 'activa'), "id = {$tienda_id}");
    return 'Tienda ' . $tienda_id . ' reabierta.';
}

/**
 * Reposición de stock (trámite 17, 10.6): hasta 10 ítems activos y límites por
 * ítem (10 consumibles / 3 armas); el stock sale del almacén.
 */
function ope7_efecto_tienda_reponer($pid, $res)
{
    global $db;
    $tienda_id = (int) ($res['tienda_id'] ?? 0);
    $items = (array) ($res['items'] ?? array());
    if ($pid < 1 || $tienda_id < 1 || !ope7_tabla_existe('tiendas') || !ope7_tabla_existe('tienda_items')) {
        return 'Reposición: datos incompletos.';
    }
    $q = $db->simple_select('ope_tiendas', 'dueno_id', "id = {$tienda_id}", array('limit' => 1));
    if ((int) $db->fetch_field($q, 'dueno_id') !== $pid) {
        return 'Reposición BLOQUEADA: no eres el dueño de esa tienda.';
    }
    $activos = 0;
    $qq = $db->simple_select('ope_tienda_items', 'COUNT(*) AS c', "tienda_id = {$tienda_id}");
    $activos = (int) $db->fetch_field($qq, 'c');
    foreach ($items as $it) {
        $oid = (int) ($it['objeto_id'] ?? 0);
        $stock = max(1, (int) ($it['stock'] ?? 1));
        if ($oid < 1) {
            continue;
        }
        $q = $db->simple_select('ope_objetos', '*', "id = {$oid}", array('limit' => 1));
        $obj = $db->fetch_array($q);
        if (!$obj) {
            return 'Reposición BLOQUEADA: ítem ' . $oid . ' no está en el catálogo.';
        }
        $aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$oid}", array('limit' => 1));
        $disponible = (int) $db->fetch_field($aq, 'cantidad');
        if ($disponible < $stock) {
            return 'Reposición BLOQUEADA: sin ' . $stock . ' × «' . ope7_objeto_nombre($oid) . '» en el almacén (tienes ' . $disponible . ').';
        }
        $limite = ope7_objeto_es_belico($obj) ? 3 : 10;
        if ($stock > $limite) {
            return 'Reposición BLOQUEADA: stock máx. ' . $limite . ' por ítem (10.6).';
        }
        $lq = $db->simple_select('ope_tienda_items', 'id', "tienda_id = {$tienda_id} AND objeto_id = {$oid}", array('limit' => 1));
        if ($db->num_rows($lq)) {
            $db->update_query('ope_tienda_items', array('stock' => $stock), "tienda_id = {$tienda_id} AND objeto_id = {$oid}");
        } else {
            if ($activos >= 10) {
                return 'Reposición BLOQUEADA: máximo 10 ítems activos (10.6) — retira uno para vender algo nuevo.';
            }
            $db->insert_query('ope_tienda_items', array(
                'tienda_id' => $tienda_id, 'objeto_id' => $oid,
                'precio_venta' => (int) round(ope7_precio_mercado($oid)),
                'stock' => $stock,
                'clasificacion' => ope7_objeto_es_belico($obj) ? 'belico' : 'normal',
                'origen' => 'produccion',
            ));
            $activos++;
        }
        $db->query('UPDATE ' . ope7_tabla_full('almacen') . ' SET cantidad = cantidad - ' . $stock . ' WHERE personaje_id = ' . $pid . ' AND objeto_id = ' . $oid);
        $db->delete_query('ope_almacen', "personaje_id = {$pid} AND objeto_id = {$oid} AND cantidad <= 0");
    }
    return 'Stock repuesto (' . count($items) . ' ítem(s)) desde el almacén.';
}

/**
 * Boletín de precios (trámite 18, staff, 10.2): registra el precio de mercado
 * por zona con factores, motivo e histórico (precios_mercado). Nunca sale de
 * la banda 0,5×–2× sobre el precio base.
 */
function ope7_efecto_boletin_precios($pid, $res)
{
    global $db;
    if (!ope7_tabla_existe('precios_mercado') || !ope7_tabla_existe('objetos')) {
        return 'Boletín de precios: módulo no disponible (pendiente).';
    }
    $zona_id = (int) ($res['zona_id'] ?? 0);
    $ronda = (int) ($res['ronda'] ?? 0);
    $motivo = trim((string) ($res['motivo'] ?? ''));
    $items = (array) ($res['items'] ?? array());
    if ($motivo === '' || !$items) {
        return 'Boletín BLOQUEADO: falta el motivo narrativo o los ítems (10.2 — nunca hay un precio misterioso).';
    }
    $n = 0;
    foreach ($items as $it) {
        $oid = (int) ($it['objeto_id'] ?? 0);
        $factores = (array) ($it['factores'] ?? array());
        $q = $db->simple_select('ope_objetos', 'precio_base', "id = {$oid}", array('limit' => 1));
        $base = (int) $db->fetch_field($q, 'precio_base');
        if ($oid < 1 || $base < 1) {
            continue;
        }
        $fo = (float) ($factores['oferta'] ?? 1.0);
        $fd = (float) ($factores['demanda'] ?? 1.0);
        $fs = (float) ($factores['suceso'] ?? 1.0);
        if ($fo < 0.80 || $fo > 1.20 || $fd < 0.80 || $fd > 1.20 || $fs < 0.50 || $fs > 1.50) {
            return 'Boletín BLOQUEADO: factor fuera de banda (oferta 0,80–1,20 · demanda 0,80–1,20 · suceso 0,50–1,50).';
        }
        $precio = (int) round($base * $fo * $fd * $fs);
        $precio = max((int) round($base * 0.5), min($precio, (int) round($base * 2.0))); // techo/suelo 0,5×–2×
        $precio = (int) round($precio / 10) * 10; // redondeo a la decena (10.4)
        $db->insert_query('ope_precios_mercado', array(
            'zona_id' => $zona_id, 'objeto_id' => $oid, 'precio_actual' => $precio,
            'factores' => json_encode($factores, JSON_UNESCAPED_UNICODE),
            'motivo' => $motivo, 'ronda' => $ronda, 'fecha_foro' => ope7_fecha_foro_actual(),
        ));
        $n++;
    }
    return 'Boletín publicado: ' . $n . ' precio(s) de mercado con motivo e histórico.';
}

/**
 * Compra en una tienda (jugador → tienda de jugador o NPC): valida saldo en
 * cartera, stock, banda de margen del vendedor y registra la transacción.
 * Prohibido comprarse a uno mismo (10.6).
 */
function ope7_tienda_compra($comprador_pid, $tienda_id, $objeto_id, $cantidad = 1)
{
    global $db;
    $comprador_pid = (int) $comprador_pid;
    $tienda_id = (int) $tienda_id;
    $objeto_id = (int) $objeto_id;
    $cantidad = max(1, (int) $cantidad);
    if ($comprador_pid < 1 || $tienda_id < 1 || $objeto_id < 1 || !ope7_tabla_existe('tiendas') || !ope7_tabla_existe('tienda_items') || !ope7_tabla_existe('transacciones')) {
        return array('ok' => false, 'msg' => 'Compra: módulo no disponible.');
    }
    $tq = $db->simple_select('ope_tiendas', 'dueno_id, estado, zona_id', "id = {$tienda_id}", array('limit' => 1));
    $t = $db->fetch_array($tq);
    if (!$t || (string) $t['estado'] !== 'activa') {
        return array('ok' => false, 'msg' => 'La tienda no está activa.');
    }
    if ((int) $t['dueno_id'] === $comprador_pid) {
        return array('ok' => false, 'msg' => 'Auto-compra prohibida (10.6).');
    }
    $iq = $db->simple_select('ope_tienda_items', '*', "tienda_id = {$tienda_id} AND objeto_id = {$objeto_id}", array('limit' => 1));
    $it = $db->fetch_array($iq);
    if (!$it || (int) $it['stock'] < $cantidad) {
        return array('ok' => false, 'msg' => 'Stock insuficiente en la tienda.');
    }
    $precio_unit = (int) $it['precio_venta'];
    $mercado = ope7_precio_mercado($objeto_id, (int) ($t['zona_id'] ?? 0));
    // Banda de margen del vendedor −20 %/+30 % sobre el mercado de la zona.
    if ($mercado > 0 && ($precio_unit < (int) round($mercado * 0.8) || $precio_unit > (int) round($mercado * 1.3))) {
        return array('ok' => false, 'msg' => 'Precio fuera de banda de margen (−20 %/+30 %).');
    }
    $total = $precio_unit * $cantidad;
    $c = ope7_cartera_get($comprador_pid);
    if ($c['cartera'] < $total) {
        return array('ok' => false, 'msg' => 'Cartera insuficiente: ' . $total . ' ฿ (tienes ' . $c['cartera'] . ' — mueve berries de la bóveda).');
    }
    // Pagar, entregar, registrar.
    ope7_cartera_mover($comprador_pid, 'cartera', -$total);
    ope7_cartera_mover((int) $t['dueno_id'], 'cartera', $total);
    $db->update_query('ope_tienda_items', array('stock' => (int) $it['stock'] - $cantidad), "id = " . (int) $it['id']);
    $aq = $db->simple_select('ope_almacen', 'id, cantidad', "personaje_id = {$comprador_pid} AND objeto_id = {$objeto_id}", array('limit' => 1));
    if ($db->num_rows($aq)) {
        $row = $db->fetch_array($aq);
        $db->update_query('ope_almacen', array('cantidad' => (int) $row['cantidad'] + $cantidad), "id = " . (int) $row['id']);
    } else {
        $db->insert_query('ope_almacen', array('personaje_id' => $comprador_pid, 'objeto_id' => $objeto_id, 'cantidad' => $cantidad));
    }
    $db->insert_query('ope_transacciones', array(
        'fecha' => TIME_NOW, 'zona_id' => (int) ($t['zona_id'] ?? 0),
        'vendedor_id' => (int) $t['dueno_id'], 'comprador_id' => $comprador_pid,
        'tipo_contraparte' => 'jugador', 'objeto_id' => $objeto_id,
        'cantidad' => $cantidad, 'precio_unitario' => $precio_unit, 'tienda_id' => $tienda_id,
    ));
    return array('ok' => true, 'msg' => 'Compra: ' . $cantidad . ' × «' . ope7_objeto_nombre($objeto_id) . '» por ' . $total . ' ฿ (a tu almacén).');
}

/**
 * Compra a tienda NPC (10.5): al precio de mercado de la zona, sin banda de
 * margen (los NPC no especulan). Paga con la cartera; el objeto va al almacén.
 */
function ope7_tienda_compra_npc($comprador_pid, $objeto_id, $cantidad = 1)
{
    global $db;
    $comprador_pid = (int) $comprador_pid;
    $objeto_id = (int) $objeto_id;
    $cantidad = max(1, (int) $cantidad);
    if ($comprador_pid < 1 || $objeto_id < 1 || !ope7_tabla_existe('objetos') || !ope7_tabla_existe('transacciones')) {
        return array('ok' => false, 'msg' => 'Compra: módulo no disponible.');
    }
    $q = $db->simple_select('ope_objetos', '*', "id = {$objeto_id} AND activo = 1", array('limit' => 1));
    $obj = $db->fetch_array($q);
    if (!$obj || (int) $obj['precio_base'] < 1) {
        return array('ok' => false, 'msg' => 'Ese objeto no se vende en tienda (solo tasación o trama).');
    }
    // Wazamono+ no se venden en tiendas (10.3): la compra máxima es Superior.
    if (!empty($obj['cupo_mundial']) || in_array((string) $obj['calidad'], array('wazamono', 'ryo', 'o', 'saijo'), true)) {
        return array('ok' => false, 'msg' => 'Las armas de grado no se venden en tiendas: forja, recompensa o trama (10.3).');
    }
    $precio = ope7_precio_mercado($objeto_id) * $cantidad;
    $c = ope7_cartera_get($comprador_pid);
    if ($c['cartera'] < $precio) {
        return array('ok' => false, 'msg' => 'Cartera insuficiente: ' . $precio . ' ฿ (tienes ' . $c['cartera'] . ' — mueve berries de la bóveda).');
    }
    ope7_cartera_mover($comprador_pid, 'cartera', -$precio);
    $aq = $db->simple_select('ope_almacen', 'id, cantidad', "personaje_id = {$comprador_pid} AND objeto_id = {$objeto_id}", array('limit' => 1));
    if ($db->num_rows($aq)) {
        $row = $db->fetch_array($aq);
        $db->update_query('ope_almacen', array('cantidad' => (int) $row['cantidad'] + $cantidad), "id = " . (int) $row['id']);
    } else {
        $db->insert_query('ope_almacen', array('personaje_id' => $comprador_pid, 'objeto_id' => $objeto_id, 'cantidad' => $cantidad));
    }
    $db->insert_query('ope_transacciones', array(
        'fecha' => TIME_NOW, 'zona_id' => 0,
        'vendedor_id' => 0, 'comprador_id' => $comprador_pid,
        'tipo_contraparte' => 'npc', 'objeto_id' => $objeto_id,
        'cantidad' => $cantidad, 'precio_unitario' => (int) round($precio / $cantidad), 'tienda_id' => 0,
    ));
    return array('ok' => true, 'msg' => 'Compra: ' . $cantidad . ' × «' . $obj['nombre'] . '» por ' . $precio . ' ฿ (a tu almacén).');
}

/**
 * Venta a tienda NPC (10.5): compran al 50 % del precio de mercado; el objeto
 * sale del almacén. La cartera del vendedor es robable; la bóveda no.
 */
function ope7_tienda_venta_npc($vendedor_pid, $objeto_id, $cantidad = 1)
{
    global $db;
    $vendedor_pid = (int) $vendedor_pid;
    $objeto_id = (int) $objeto_id;
    $cantidad = max(1, (int) $cantidad);
    if ($vendedor_pid < 1 || $objeto_id < 1 || !ope7_tabla_existe('transacciones')) {
        return array('ok' => false, 'msg' => 'Venta: módulo no disponible.');
    }
    $aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$vendedor_pid} AND objeto_id = {$objeto_id}", array('limit' => 1));
    if ((int) $db->fetch_field($aq, 'cantidad') < $cantidad) {
        return array('ok' => false, 'msg' => 'No tienes ' . $cantidad . ' × «' . ope7_objeto_nombre($objeto_id) . '» en el almacén.');
    }
    $precio = (int) round(ope7_precio_mercado($objeto_id) * 0.5) * $cantidad; // 50 % (10.5)
    $db->query('UPDATE ' . ope7_tabla_full('almacen') . ' SET cantidad = cantidad - ' . $cantidad . ' WHERE personaje_id = ' . $vendedor_pid . ' AND objeto_id = ' . $objeto_id);
    $db->delete_query('ope_almacen', "personaje_id = {$vendedor_pid} AND objeto_id = {$objeto_id} AND cantidad <= 0");
    ope7_cartera_mover($vendedor_pid, 'cartera', $precio);
    $db->insert_query('ope_transacciones', array(
        'fecha' => TIME_NOW, 'zona_id' => 0,
        'vendedor_id' => $vendedor_pid, 'comprador_id' => 0,
        'tipo_contraparte' => 'npc', 'objeto_id' => $objeto_id,
        'cantidad' => $cantidad, 'precio_unitario' => (int) round($precio / $cantidad), 'tienda_id' => 0,
    ));
    return array('ok' => true, 'msg' => 'Venta: ' . $cantidad . ' × «' . ope7_objeto_nombre($objeto_id) . '» por ' . $precio . ' ฿ (50 % del mercado).');
}

// ─────────────────────────────────────────────────────────────
// Panel staff «Mercado / Economía» (A.3, 5.9): fluctuación por zona
// y ronda con motivo, carteras y transacciones. Vista honesta: muestra
// lo que hay; el cron y el cierre de ronda alimentan las tablas.
// ─────────────────────────────────────────────────────────────

/** HTML del panel: precios con motivo, carteras, transacciones. */
function ope7_mercado_panel_html()
{
    global $db;
    $out = '';

    // ── Fluctuación por zona y ronda ──
    $out .= '<div class="plate"><div class="plate-h">Fluctuación por zona y ronda (5.9)</div><div class="plate-b">';
    if (ope7_tabla_existe('precios_mercado') && ope7_tabla_existe('objetos')) {
        $q = $db->query('SELECT pm.*, o.nombre AS objeto_nombre, z.nombre AS zona_nombre, z.isla_id, i.nombre AS isla_nombre '
            . 'FROM ' . ope7_tabla_full('precios_mercado') . ' pm '
            . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = pm.objeto_id '
            . 'LEFT JOIN ' . ope7_tabla_full('zonas') . ' z ON z.id = pm.zona_id '
            . 'LEFT JOIN ' . ope7_tabla_full('islas') . ' i ON i.id = z.isla_id '
            . 'ORDER BY pm.ronda DESC, pm.zona_id, pm.objeto_id LIMIT 40');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin fluctuaciones registradas (el cierre de ronda 15.2 las archiva aquí con su motivo y ronda).</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>Zona</th><th>Objeto</th><th>Precio</th><th>Ronda</th><th>Motivo</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $zona = (string) ($r['isla_nombre'] ?? '') . ((string) ($r['zona_nombre'] ?? '') !== '' ? ' · ' . $r['zona_nombre'] : '');
                $out .= '<tr><td>' . htmlspecialchars($zona !== '' ? $zona : 'zona #' . (int) $r['zona_id']) . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['objeto_nombre']) . '</td>'
                    . '<td>' . number_format((int) $r['precio_actual'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . (int) $r['ronda'] . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['motivo']) . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (precios_mercado).</p>';
    }
    $out .= '</div></div>';

    // ── Carteras (top por saldo total) ──
    $out .= '<div class="plate"><div class="plate-h">Carteras (5.9: cartera robable / bóveda segura)</div><div class="plate-b">';
    if (ope7_tabla_existe('carteras') && ope7_tabla_existe('personajes')) {
        $q = $db->query('SELECT c.*, p.nombre AS pj_nombre FROM ' . ope7_tabla_full('carteras') . ' c '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = c.personaje_id '
            . 'ORDER BY (c.cartera + c.boveda) DESC LIMIT 15');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin carteras todavía (se crean al crear el personaje).</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>Personaje</th><th>Cartera</th><th>Bóveda</th><th>Total</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $out .= '<tr><td>' . htmlspecialchars((string) $r['pj_nombre']) . '</td>'
                    . '<td>' . number_format((int) $r['cartera'], 0, ',', '.') . ' ฿</td>'
                    . '<td>' . number_format((int) $r['boveda'], 0, ',', '.') . ' ฿</td>'
                    . '<td><b>' . number_format((int) $r['cartera'] + (int) $r['boveda'], 0, ',', '.') . ' ฿</b></td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (carteras).</p>';
    }
    $out .= '</div></div>';

    // ── Transacciones recientes ──
    $out .= '<div class="plate"><div class="plate-h">Transacciones recientes (compra/venta)</div><div class="plate-b">';
    if (ope7_tabla_existe('transacciones') && ope7_tabla_existe('objetos')) {
        $q = $db->query('SELECT t.*, o.nombre AS objeto_nombre, v.nombre AS vendedor_nombre, c.nombre AS comprador_nombre '
            . 'FROM ' . ope7_tabla_full('transacciones') . ' t '
            . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = t.objeto_id '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' v ON v.id = t.vendedor_id '
            . 'LEFT JOIN ' . ope7_tabla_full('personajes') . ' c ON c.id = t.comprador_id '
            . 'ORDER BY t.id DESC LIMIT 25');
        if ($db->num_rows($q) === 0) {
            $out .= '<p class="zs-mut">Sin transacciones todavía.</p>';
        } else {
            $out .= '<table class="zs-tab"><thead><tr><th>Fecha</th><th>Objeto</th><th>Qty</th><th>P. unit.</th><th>Vendedor</th><th>Comprador</th></tr></thead><tbody>';
            while ($r = $db->fetch_array($q)) {
                $out .= '<tr><td>' . date('d/m/y', (int) $r['fecha']) . '</td>'
                    . '<td>' . htmlspecialchars((string) $r['objeto_nombre']) . '</td>'
                    . '<td>×' . (int) $r['cantidad'] . '</td>'
                    . '<td>' . number_format((int) $r['precio_unitario'], 0, ',', '.') . '</td>'
                    . '<td>' . htmlspecialchars((string) ($r['vendedor_nombre'] ?? ($r['vendedor_id'] > 0 ? '#' . $r['vendedor_id'] : 'NPC'))) . '</td>'
                    . '<td>' . htmlspecialchars((string) ($r['comprador_nombre'] ?? ($r['comprador_id'] > 0 ? '#' . $r['comprador_id'] : 'NPC'))) . '</td></tr>';
            }
            $out .= '</tbody></table>';
        }
    } else {
        $out .= '<p class="zs-mut">Tablas no migradas (transacciones).</p>';
    }
    $out .= '</div></div>';

    $out .= '</div>';
    return $out;
}
