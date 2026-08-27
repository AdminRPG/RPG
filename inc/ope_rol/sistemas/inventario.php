<?php
/**
 * One Piece: 7 Seas · Inventario, equipo y cartera (F3.2/F3.3)
 * -------------------------------------------------------------
 * Capas del cap. 9: equipado (solo usable/robable), mochila, almacén (seguro).
 * Carga solo por ranuras: equipado `3 + FUE/10`, mochila `8 + FUE/4`
 * (Tontatta ×2 en mochila). Equipar valida ranuras y cupos de Meitou.
 * Cartera (robable) y bóveda (segura) con movimientos (5.9).
 * Números cerrados del manual — no recalibrar.
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** ¿La raza del personaje es Tontatta? (mochila ×2, 9.2). */
function ope7_pj_es_tontatta($f)
{
    global $db;
    if (!ope7_tabla_existe('razas')) {
        return false;
    }
    foreach (array('raza_id', 'raza_hibrida_id') as $k) {
        $rid = (int) ($f[$k] ?? 0);
        if ($rid > 0) {
            $q = $db->simple_select('ope_razas', 'nombre', "id = {$rid}", array('limit' => 1));
            $nombre = (string) $db->fetch_field($q, 'nombre');
            if (mb_stripos($nombre, 'tontatta') !== false) {
                return true;
            }
        }
    }
    return false;
}

/** Ranuras de carga (9.2): equipado 3+FUE/10 · mochila 8+FUE/4 (Tontatta ×2). */
function ope7_inventario_capacidad($f)
{
    $fue = (int) ($f['fue'] ?? 10);
    $equipado = 3 + (int) floor($fue / 10);
    $mochila = 8 + (int) floor($fue / 4);
    $tontatta = ope7_pj_es_tontatta($f);
    if ($tontatta) {
        $mochila *= 2;
    }
    return array('equipado' => $equipado, 'mochila' => $mochila, 'tontatta' => $tontatta);
}

/** Zonas que cuentan como «equipado» (únicas robables/usable en combate). */
function ope7_inventario_zonas_equipado()
{
    return array('arma1', 'arma2', 'armadura', 'escudo', 'cinturon');
}

/**
 * Uso actual de ranuras por bolsa, calculado desde objetos.ranuras
 * (1 normal · 2 arma de dos manos · 3 objeto grande).
 */
function ope7_inventario_usado($pid)
{
    global $db;
    $usado = array('equipado' => 0, 'mochila' => 0);
    if (!ope7_tabla_existe('inventario_personaje') || !ope7_tabla_existe('objetos')) {
        return $usado;
    }
    $zonas_eq = ope7_inventario_zonas_equipado();
    $q = $db->query('SELECT i.zona, o.ranuras FROM ' . ope7_tabla_full('inventario_personaje') . ' i '
        . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = i.objeto_id WHERE i.personaje_id = ' . (int) $pid);
    while ($r = $db->fetch_array($q)) {
        $bolsa = in_array((string) $r['zona'], $zonas_eq, true) ? 'equipado' : 'mochila';
        $usado[$bolsa] += (int) ($r['ranuras'] ?? 1);
    }
    return $usado;
}

/**
 * Equipa un objeto (trámite 14): valida ranuras y cupos de Meitou, y mueve el
 * objeto del almacén al equipado. Devuelve array('ok' => bool, 'msg' => ...).
 */
function ope7_inventario_equipar($pid, $objeto_id, $zona)
{
    global $db;
    $pid = (int) $pid;
    $objeto_id = (int) $objeto_id;
    $zonas_eq = ope7_inventario_zonas_equipado();
    $zona = (string) $zona;
    if (!in_array($zona, $zonas_eq, true)) {
        return array('ok' => false, 'msg' => 'Zona de equipado inválida.');
    }
    if ($pid < 1 || $objeto_id < 1 || !ope7_tabla_existe('objetos') || !ope7_tabla_existe('inventario_personaje') || !ope7_tabla_existe('almacen')) {
        return array('ok' => false, 'msg' => 'Módulo de inventario no disponible.');
    }
    $q = $db->simple_select('ope_objetos', '*', "id = {$objeto_id} AND activo = 1", array('limit' => 1));
    $obj = $db->fetch_array($q);
    if (!$obj) {
        return array('ok' => false, 'msg' => 'Objeto no existe en el catálogo validado.');
    }
    // El objeto debe estar en el almacén (9.2: el equipado sale del inventario real).
    $aq = $db->simple_select('ope_almacen', 'cantidad', "personaje_id = {$pid} AND objeto_id = {$objeto_id}", array('limit' => 1));
    if ((int) $db->fetch_field($aq, 'cantidad') < 1) {
        return array('ok' => false, 'msg' => 'No tienes ese objeto en tu almacén.');
    }
    // Cupos mundiales (9.3): las Wazamono+ no se duplican.
    if (!empty($obj['cupo_mundial'])) {
        $m = $db->simple_select('ope_arma_meito', 'id', "objeto_id = {$objeto_id} AND portador_id IS NOT NULL AND portador_id <> {$pid}", array('limit' => 1));
        if ($db->num_rows($m)) {
            return array('ok' => false, 'msg' => 'Cupo mundial ocupado: esa arma de grado ya tiene portador (9.3).');
        }
    }
    $ranuras = max(1, (int) ($obj['ranuras'] ?? 1));
    $f = ope7_pj_get($pid);
    $cap = ope7_inventario_capacidad($f);
    $usado = ope7_inventario_usado($pid);

    // Si ya hay algo en la zona objetivo, se libera (vuelve al almacén) y se
    // descuentan sus ranuras antes de validar.
    $liberar = 0;
    $old = $db->simple_select('ope_inventario_personaje', 'id, objeto_id', "personaje_id = {$pid} AND zona = '{$zona}'", array('limit' => 1));
    if ($db->num_rows($old)) {
        $old_row = $db->fetch_array($old);
        $oq = $db->simple_select('ope_objetos', 'ranuras', "id = " . (int) $old_row['objeto_id'], array('limit' => 1));
        $liberar = max(1, (int) $db->fetch_field($oq, 'ranuras'));
        $db->insert_query('ope_almacen', array('personaje_id' => $pid, 'objeto_id' => (int) $old_row['objeto_id'], 'cantidad' => 1));
        $db->delete_query('ope_inventario_personaje', "id = " . (int) $old_row['id']);
    }
    if ($usado['equipado'] - $liberar + $ranuras > $cap['equipado']) {
        return array('ok' => false, 'msg' => 'No caben ' . $ranuras . ' ranuras en el equipado (techo ' . $cap['equipado'] . ' = 3+FUE/10).');
    }
    $db->insert_query('ope_inventario_personaje', array(
        'personaje_id' => $pid, 'objeto_id' => $objeto_id, 'zona' => $zona, 'cantidad' => 1,
    ));
    // Descuenta del almacén (si había más de una unidad).
    $db->query('UPDATE ' . ope7_tabla_full('almacen') . ' SET cantidad = cantidad - 1 WHERE personaje_id = ' . $pid . ' AND objeto_id = ' . $objeto_id);
    $db->delete_query('ope_almacen', "personaje_id = {$pid} AND objeto_id = {$objeto_id} AND cantidad <= 0");
    // Registra el portador si es Meitou.
    if (!empty($obj['cupo_mundial'])) {
        $mm = $db->simple_select('ope_arma_meito', 'id', "objeto_id = {$objeto_id}", array('limit' => 1));
        if ($db->num_rows($mm)) {
            $db->update_query('ope_arma_meito', array('portador_id' => $pid), "objeto_id = {$objeto_id}");
        } else {
            $db->insert_query('ope_arma_meito', array(
                'objeto_id' => $objeto_id, 'nombre_propio' => (string) $obj['nombre'], 'portador_id' => $pid,
                'cupo' => (string) $obj['cupo_mundial'],
            ));
        }
    }
    return array('ok' => true, 'msg' => 'Equipado: «' . $obj['nombre'] . '» en ' . $zona . ' (' . $ranuras . ' ranura(s)).');
}

/** Cartera del personaje (cartera robable + bóveda segura). */
function ope7_cartera_get($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !ope7_tabla_existe('carteras')) {
        return array('cartera' => 0, 'boveda' => 0);
    }
    $q = $db->simple_select('ope_carteras', 'cartera, boveda', "personaje_id = {$pid}", array('limit' => 1));
    $r = $db->fetch_array($q);
    return $r ? array('cartera' => (int) $r['cartera'], 'boveda' => (int) $r['boveda']) : array('cartera' => 0, 'boveda' => 0);
}

/**
 * Mueve berries entre cartera y bóveda (10.2) o aplica un ingreso/gasto.
 * $destino: 'cartera' | 'boveda' · $cantidad > 0 ingreso, < 0 gasto.
 * Devuelve array('ok', 'msg', 'saldo').
 */
function ope7_cartera_mover($pid, $destino, $cantidad)
{
    global $db;
    $pid = (int) $pid;
    $cantidad = (int) $cantidad;
    $destino = in_array((string) $destino, array('cartera', 'boveda'), true) ? (string) $destino : 'cartera';
    if ($pid < 1 || $cantidad === 0 || !ope7_tabla_existe('carteras')) {
        return array('ok' => false, 'msg' => 'Movimiento de cartera inválido.');
    }
    $c = ope7_cartera_get($pid);
    $nuevo = $c[$destino] + $cantidad;
    if ($nuevo < 0) {
        return array('ok' => false, 'msg' => 'Saldo insuficiente en ' . $destino . ' (' . $c[$destino] . ' ฿).');
    }
    $q = $db->simple_select('ope_carteras', 'personaje_id', "personaje_id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $db->update_query('ope_carteras', array($destino => $nuevo), "personaje_id = {$pid}");
    } else {
        $db->insert_query('ope_carteras', array('personaje_id' => $pid, 'cartera' => $destino === 'cartera' ? $nuevo : 0, 'boveda' => $destino === 'boveda' ? $nuevo : 0));
    }
    return array('ok' => true, 'msg' => ($cantidad > 0 ? '+' : '') . $cantidad . ' ฿ a ' . $destino . ' (saldo ' . $nuevo . ').', 'saldo' => $nuevo);
}

/** Resumen completo para la ficha (bloque «Equipo y cartera»): saldos,
 * ranuras usadas/capacidad, equipado, mochila y almacén. */
function ope7_inventario_resumen($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array('cartera' => 0, 'boveda' => 0, 'capacidad' => array('equipado' => 0, 'mochila' => 0), 'usado' => array('equipado' => 0, 'mochila' => 0), 'equipado' => array(), 'mochila' => array(), 'almacen' => array());
    if ($pid < 1) {
        return $out;
    }
    $f = ope7_pj_get($pid);
    if (!$f) {
        return $out;
    }
    $out['cartera'] = ope7_cartera_get($pid);
    $out['capacidad'] = ope7_inventario_capacidad($f);
    $out['usado'] = ope7_inventario_usado($pid);
    $zonas_eq = ope7_inventario_zonas_equipado();
    if (ope7_tabla_existe('inventario_personaje') && ope7_tabla_existe('objetos')) {
        $q = $db->query('SELECT i.zona, i.cantidad, i.vinculado, o.id AS oid, o.nombre, o.categoria, o.calidad, o.ranuras, o.efecto_json '
            . 'FROM ' . ope7_tabla_full('inventario_personaje') . ' i '
            . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = i.objeto_id WHERE i.personaje_id = ' . $pid . ' ORDER BY i.zona');
        while ($r = $db->fetch_array($q)) {
            $item = array('id' => (int) $r['oid'], 'nombre' => (string) $r['nombre'], 'categoria' => (string) $r['categoria'], 'calidad' => (string) $r['calidad'], 'ranuras' => (int) $r['ranuras'], 'vinculado' => (string) $r['vinculado']);
            if (in_array((string) $r['zona'], $zonas_eq, true)) {
                $item['zona'] = (string) $r['zona'];
                $out['equipado'][] = $item;
            } else {
                $out['mochila'][] = $item;
            }
        }
    }
    if (ope7_tabla_existe('almacen') && ope7_tabla_existe('objetos')) {
        $q = $db->query('SELECT a.cantidad, o.id AS oid, o.nombre, o.categoria, o.calidad '
            . 'FROM ' . ope7_tabla_full('almacen') . ' a '
            . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = a.objeto_id WHERE a.personaje_id = ' . $pid . ' ORDER BY o.nombre');
        while ($r = $db->fetch_array($q)) {
            $out['almacen'][] = array('id' => (int) $r['oid'], 'nombre' => (string) $r['nombre'], 'categoria' => (string) $r['categoria'], 'calidad' => (string) $r['calidad'], 'cantidad' => (int) $r['cantidad']);
        }
    }
    return $out;
}

/** Nombre de un objeto del catálogo (caché ligera). */
function ope7_objeto_nombre($objeto_id)
{
    global $db;
    static $cache = array();
    $objeto_id = (int) $objeto_id;
    if ($objeto_id < 1) {
        return '#?' . $objeto_id;
    }
    if (!isset($cache[$objeto_id])) {
        if (!ope7_tabla_existe('objetos')) {
            return '?';
        }
        $q = $db->simple_select('ope_objetos', 'nombre', "id = {$objeto_id}", array('limit' => 1));
        $cache[$objeto_id] = (string) $db->fetch_field($q, 'nombre');
    }
    return $cache[$objeto_id];
}
