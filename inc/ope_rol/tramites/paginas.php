<?php
/**
 * One Piece: 7 Seas · Páginas de trámite (tramite-NN.php)
 * -----------------------------------------------------------------------------
 * Motor compartido de las ventanillas del jugador: cada trámite es un fichero
 * tramite-NN.php que delega aquí. El formulario se genera desde una
 * configuración por trámite (campos + opciones dinámicas por personaje) y al
 * enviar se enruta a ope7_tramite_crear:
 *
 *   · ligeros implementados (1, 4, 14, 17) → se ejecutan al instante;
 *   · el resto → la solicitud va a la bandeja (la IA propone, el staff firma).
 *
 * Los 11 trámites solo-staff NO tienen página: badge en el hub → bandeja.
 * Scope CSS: body.ope-pg-tramite. Sin estilos inline en el HTML.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Las 6 áreas del hub (agrupación cerrada de los 67). */
function ope7_tramites_areas()
{
    return array(
        array('Personaje y progreso', 'Tu ficha, tus historias, tus técnicas y tu equipo.', range(1, 14)),
        array('Economía', 'Tiendas, precios, reposiciones y personal no jugador.', range(15, 19)),
        array('Mundo Vivo', 'Facciones, bajo mundo y conquista territorial.', range(20, 37)),
        array('Viaje', 'Navegación entre mares y tu barco.', range(38, 44)),
        array('Poderes', 'Akumas, Haki y cibernética.', array_merge(range(45, 51), range(56, 61))),
        array('Grupos', 'Misiones, la muerte y tripulaciones.', array_merge(range(52, 55), range(62, 67))),
    );
}

/** Trámites que solo inicia el staff (sin página propia). */
function ope7_tramites_solo_staff()
{
    return array(18, 21, 24, 30, 36, 49, 54, 55, 59, 60, 61);
}

/** ¿Este trámite tiene página de jugador? (quien incluye a un jugador). */
function ope7_tramite_tiene_pagina($numero)
{
    $info = ope7_tramite_info($numero);
    if (!$info) {
        return false;
    }
    return in_array((string) $info['quien'], array('jugador', 'jugador-staff', 'capitan', 'capitan-staff', 'staff-jugador'), true);
}

/** Ligeros 100 % automáticos con efecto implementado (se ejecutan al instante). */
function ope7_tramite_es_auto($numero)
{
    $info = ope7_tramite_info($numero);
    return $info && $info['naturaleza'] === 'ligero' && !$info['firma']
        && in_array((int) $numero, array(1, 4, 14, 17), true);
}

/** Nota «qué pasa al pedir» según naturaleza. */
function ope7_tramite_pagina_nota($numero, $info)
{
    if (ope7_tramite_es_auto($numero)) {
        return 'Se ejecuta <b>al instante</b>: validación + efectos automáticos (sin esperas).';
    }
    if ($info['naturaleza'] === 'ia') {
        return 'Va a la <b>bandeja del staff</b>: la IA propone y el staff firma. En validación de ficha (3) y técnica (13) el resultado vuelve a ti para confirmarlo.';
    }
    if ($info['naturaleza'] === 'hito') {
        return 'Hito narrativo: <b>requiere firma del staff</b> con motivo. Cuenta qué pasó en tus historias.';
    }
    return 'Va a la <b>bandeja del staff</b>, que valida y firma antes de aplicar nada.';
}

/**
 * Campos del formulario por trámite. Tipos:
 *   personaje · select (estático) · dyn (dinámico por personaje: fuente)
 *   texto · area · number · checkbox
 * Fuentes dyn: barcos, tiendas, dominios, objetos_mochila, objetos_almacen, utensilios.
 */
function ope7_tramite_pagina_campos($numero)
{
    $atributos = array(
        'fue' => 'FUE · Fuerza', 'des' => 'DES · Destreza', 'agi' => 'AGI · Agilidad',
        'res' => 'RES · Resistencia', 'per' => 'PER · Percepción', 'inte' => 'INT · Intelecto',
        'car' => 'CAR · Carisma', 'vol' => 'VOL · Voluntad',
    );
    $personaje = array('tipo' => 'personaje', 'name' => 'personaje_id', 'label' => 'Personaje', 'required' => true);

    $campos = array(
        $personaje,
        array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto para el staff', 'required' => true,
              'maxlength' => 2000,
              'hint' => 'Cuenta qué necesitas y por qué: alimenta la propuesta de la IA y la firma del staff.'),
    );

    switch ((int) $numero) {
        case 1:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'tema_titulo', 'label' => 'Título del tema', 'required' => true, 'maxlength' => 120, 'hint' => 'Con qué nombre abrirás el hilo.'),
                array('tipo' => 'select', 'name' => 'tipo', 'label' => 'Tipo de tema', 'required' => true,
                      'opciones' => array('presente' => 'Presente (se juega ahora)', 'pasado' => 'Pasado (flashback, no afecta a tu presente)')),
                array('tipo' => 'texto', 'name' => 'fecha_foro', 'label' => 'Fecha on-roll (solo pasados)', 'required' => false, 'maxlength' => 40,
                      'hint' => 'Si es un tema pasado, la fecha on-roll en la que sucede (el staff la valida).'),
            );
            break;
        case 4:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'dominio_id', 'label' => 'Subir un dominio (opcional)', 'required' => false, 'fuente' => 'dominios',
                      'hint' => 'Si eliges dominio se ignora el atributo: sube UN nivel (15 días, ×1,5 el 1.º adicional / ×2 el 2.º+, D4.5).'),
                array('tipo' => 'select', 'name' => 'atributo', 'label' => 'Atributo a entrenar', 'required' => true, 'opciones' => $atributos,
                      'hint' => 'Solo si no has elegido dominio.'),
                array('tipo' => 'select', 'name' => 'bloque', 'label' => 'Bloque de entrenamiento', 'required' => true,
                      'opciones' => array('5' => '5 puntos (200 PP) — entra en reserva a los 5 días', '10' => '10 puntos (400 PP) — entra en reserva a los 10 días')),
            );
            break;
        case 13:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'idea', 'label' => 'Idea de la técnica', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'Nombre/descripción de lo que imaginas. La skill genera la ficha completa (requisitos, efectos, PA/PE) y el resultado vuelve a ti.'),
                array('tipo' => 'select', 'name' => 'tier', 'label' => 'Tier deseado', 'required' => true,
                      'opciones' => array('T1' => 'T1 · 60 PP', 'T2' => 'T2 · 120 PP', 'T3' => 'T3 · 250 PP', 'T4' => 'T4 · 400 PP', 'T5' => 'T5 · 600 PP')),
            );
            break;
        case 14:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'objeto_id', 'label' => 'Objeto del almacén', 'required' => true, 'fuente' => 'objetos_almacen'),
                array('tipo' => 'select', 'name' => 'zona', 'label' => 'Dónde equiparlo', 'required' => true,
                      'opciones' => array('arma1' => 'Arma principal', 'arma2' => 'Arma secundaria', 'armadura' => 'Armadura', 'escudo' => 'Escudo', 'cinturon' => 'Cinturón')),
            );
            break;
        case 15:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'tienda_nombre', 'label' => 'Nombre de la tienda', 'required' => true, 'maxlength' => 120),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Local, capital y plan (contexto)', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Dónde está el local (isla/zona), capital disponible y qué venderás. El staff valida Comerciante + capital + bélicos.'),
            );
            break;
        case 16:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tienda_id', 'label' => 'Tienda', 'required' => true, 'fuente' => 'tiendas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Motivo', 'required' => true, 'maxlength' => 1000),
            );
            break;
        case 17:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'tienda_id', 'label' => 'Tienda a reponer', 'required' => true, 'fuente' => 'tiendas'),
                array('tipo' => 'dyn', 'name' => 'objeto_id', 'label' => 'Objeto (desde tu almacén)', 'required' => true, 'fuente' => 'objetos_almacen'),
                array('tipo' => 'number', 'name' => 'cantidad', 'label' => 'Cantidad', 'required' => true, 'min' => 1, 'max' => 99, 'value' => 1),
            );
            break;
        case 19:
            $campos = array(
                $personaje,
                array('tipo' => 'texto', 'name' => 'nombre_npc', 'label' => 'Nombre del NPC', 'required' => true, 'maxlength' => 120),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Perfil del NPC (contexto)', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'Rol que cumplirá en tu tripulación. Usa fichas existentes; los NPC no tienen ficha de combate.'),
            );
            break;
        case 20:
        case 22:
        case 23:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto narrativo', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Cuenta qué has hecho en tus historias (fama, misiones, temas cerrados): la skill cruza tu expediente y propone.'),
            );
            break;
        case 34:
        case 37:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'isla_id', 'label' => 'Isla objetivo', 'required' => true, 'fuente' => 'islas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Justificación de presencia (16.2) y plan', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Cómo llegas a la isla, por qué la reclamas y con qué fuerzas. El staff valida el control previo y abre las fases del asedio.'),
            );
            break;
        case 35:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Cómo organizas la defensa', 'required' => true, 'maxlength' => 2000,
                      'hint' => 'Defensa activa del asedio: qué tropas y fortificaciones usas. Sin respuesta del defensor no hay veredicto.'),
            );
            break;
        case 38:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco / transporte', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'dyn', 'name' => 'destino_id', 'label' => 'Isla de destino', 'required' => true, 'fuente' => 'islas'),
                array('tipo' => 'dyn', 'name' => 'utensilio_id', 'label' => 'Utensilio (opcional, −12 h por tramo)', 'required' => false, 'fuente' => 'utensilios'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Acompañantes y ruta (contexto)', 'required' => false, 'maxlength' => 1000,
                      'hint' => 'Quién viaja contigo y si hay algo especial en la ruta. La skill calcula el IRT interno, el tiempo off-roll y los oráculos.'),
            );
            break;
        case 39:
        case 40:
            $campos = array(
                $personaje,
                array('tipo' => 'select', 'name' => 'tipo_id', 'label' => 'Tipo de barco', 'required' => true, 'fuente' => 'tipos_barco'),
                array('tipo' => 'select', 'name' => 'madera_id', 'label' => 'Madera del casco', 'required' => true, 'fuente' => 'maderas'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto (compra/construcción)', 'required' => false, 'maxlength' => 1000),
            );
            break;
        case 41:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'nivel', 'label' => 'Nivel objetivo', 'required' => true,
                      'opciones' => array('N2' => 'N2', 'N3' => 'N3'), 'hint' => 'Un paso a la vez (N1→N2→N3): el staff valida el coste (diferencia + madera).'),
            );
            break;
        case 42:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'accion', 'label' => 'Acción', 'required' => true,
                      'opciones' => array('instalar' => 'Instalar módulo', 'quitar' => 'Quitar módulo')),
                array('tipo' => 'select', 'name' => 'modulo_id', 'label' => 'Módulo', 'required' => true, 'fuente' => 'modulos'),
            );
            break;
        case 43:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'grado', 'label' => 'Grado de daño', 'required' => true,
                      'opciones' => array('leve' => 'Leve', 'moderado' => 'Moderado', 'grave' => 'Grave')),
            );
            break;
        case 44:
            $campos = array(
                $personaje,
                array('tipo' => 'dyn', 'name' => 'barco_id', 'label' => 'Barco', 'required' => true, 'fuente' => 'barcos'),
                array('tipo' => 'select', 'name' => 'accion', 'label' => 'Acción', 'required' => true,
                      'opciones' => array('vender' => 'Vender (50 % del precio)', 'desguace' => 'Desguazar (materiales)')),
            );
            break;
        case 62:
            $campos = array(
                $personaje,
                array('tipo' => 'area', 'name' => 'causa', 'label' => 'Causa de la muerte', 'required' => true, 'maxlength' => 1000,
                      'hint' => 'PV ≤ −(VOL×2) o PE ≤ −RES en combate, o desenlace narrativo cerrado. La skill valida el umbral y propone herencia y efectos de mundo.'),
                array('tipo' => 'area', 'name' => 'motivo', 'label' => 'Contexto del desenlace', 'required' => false, 'maxlength' => 1000),
            );
            break;
        default:
            break;
    }
    return $campos;
}

/** Opciones de un select dinámico (dyn) para TODOS los PJ del usuario (data-pj). */
function ope7_tramite_pagina_dyn($fuente, $uid)
{
    global $db;
    $out = array(); // [value => [label, pid]]
    $pjs = ope7_tramite_pj_opciones($uid);
    if (!$pjs) {
        return $out;
    }
    $pids = array_keys($pjs);

    switch ($fuente) {
        case 'islas':
            if (ope7_tabla_existe('islas')) {
                $q = $db->simple_select('ope_islas', '*', '1=1', array('order_by' => 'mar_id, nombre'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'], 0);
                }
            }
            break;
        case 'tipos_barco':
            if (ope7_tabla_existe('tipos_barcos')) {
                $q = $db->simple_select('ope_tipos_barcos', '*', '1=1', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' · ' . ope7_objeto_precio_barco_txt($r), 0);
                }
            }
            break;
        case 'maderas':
            if (ope7_tabla_existe('maderas_casco')) {
                $q = $db->simple_select('ope_maderas_casco', '*', '1=1', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'], 0);
                }
            }
            break;
        case 'modulos':
            if (ope7_tabla_existe('modulos_barcos')) {
                $q = $db->simple_select('ope_modulos_barcos', '*', '1=1', array('order_by' => 'precio'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ($r['requisito_oficio'] ? ' (' . $r['requisito_oficio'] . ')' : ''), 0);
                }
            }
            break;
        case 'barcos':
            if ($pids && ope7_tabla_existe('barcos') && ope7_tabla_existe('tipos_barcos')) {
                $q = $db->query("SELECT b.id, b.nombre, b.nivel, b.dueno_id, t.nombre AS tipo
                    FROM " . ope7_tabla_full('barcos') . " b
                    JOIN " . ope7_tabla_full('tipos_barcos') . " t ON t.id = b.tipo_id
                    WHERE b.dueno_id IN (" . implode(',', $pids) . ") AND b.estado NOT IN ('hundido','vendido')
                    ORDER BY b.id");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['tipo'] . ' ' . $r['nivel'] . ')', (int) $r['dueno_id']);
                }
            }
            break;
        case 'tiendas':
            if ($pids && ope7_tabla_existe('tiendas')) {
                $q = $db->simple_select('ope_tiendas', '*', 'personaje_id IN (' . implode(',', $pids) . ')', array('order_by' => 'id'));
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (' . $r['estado'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'dominios':
            if ($pids && ope7_tabla_existe('dominios') && ope7_tabla_existe('dominios_personaje')) {
                $q = $db->query("SELECT dp.personaje_id, d.id, d.nombre, dp.nivel
                    FROM " . ope7_tabla_full('dominios_personaje') . " dp
                    JOIN " . ope7_tabla_full('dominios') . " d ON d.id = dp.dominio_id
                    WHERE dp.personaje_id IN (" . implode(',', $pids) . ")
                    ORDER BY d.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['id']] = array($r['nombre'] . ' (nv ' . (int) $r['nivel'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'objetos_almacen':
            if ($pids && ope7_tabla_existe('almacen') && ope7_tabla_existe('objetos')) {
                $q = $db->query("SELECT a.personaje_id, a.objeto_id, a.cantidad, o.nombre
                    FROM " . ope7_tabla_full('almacen') . " a
                    JOIN " . ope7_tabla_full('objetos') . " o ON o.id = a.objeto_id
                    WHERE a.personaje_id IN (" . implode(',', $pids) . ") AND a.cantidad > 0
                    ORDER BY o.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['objeto_id']] = array($r['nombre'] . ' (×' . (int) $r['cantidad'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
        case 'utensilios':
            if ($pids && ope7_tabla_existe('almacen') && ope7_tabla_existe('objetos')) {
                $q = $db->query("SELECT a.personaje_id, a.objeto_id, a.cantidad, o.nombre
                    FROM " . ope7_tabla_full('almacen') . " a
                    JOIN " . ope7_tabla_full('objetos') . " o ON o.id = a.objeto_id
                    WHERE a.personaje_id IN (" . implode(',', $pids) . ") AND a.cantidad > 0
                      AND (o.nombre LIKE '%Log Pose%' OR o.nombre LIKE '%Brújula%' OR o.nombre LIKE '%Eternal%')
                    ORDER BY o.nombre");
                while ($r = $db->fetch_array($q)) {
                    $out[(int) $r['objeto_id']] = array($r['nombre'] . ' (×' . (int) $r['cantidad'] . ')', (int) $r['personaje_id']);
                }
            }
            break;
    }
    return $out;
}

/** PJs del usuario (para el selector de personaje). */
function ope7_tramite_pj_opciones($uid)
{
    global $db;
    $out = array();
    if (!ope7_tabla_existe('personajes')) {
        return $out;
    }
    $q = $db->simple_select('ope_personajes', 'id, nombre, nivel, estado', "uid = " . (int) $uid . " AND estado != 'rechazado'", array('order_by' => 'id'));
    while ($r = $db->fetch_array($q)) {
        $out[(int) $r['id']] = $r['nombre'] . ' (nv ' . (int) $r['nivel'] . ')';
    }
    return $out;
}

/** Precio por nivel del tipo de barco (para el label de la compra). */
function ope7_objeto_precio_barco_txt($tipo)
{
    $precios = $tipo['precios'] ? json_decode($tipo['precios'], true) : array();
    $p = (int) ($precios[0] ?? $tipo['precio'] ?? 0);
    return $p > 0 ? number_format($p) . ' ฿' : '—';
}

/**
 * Construye ids/datos/motivo desde el POST según el trámite.
 * Devuelve array('ok', 'msg'|'ids', 'datos', 'motivo', 'pid').
 */
function ope7_tramite_pagina_procesar($numero, $uid, array $info, array $campos)
{
    global $mybb, $db;

    $pid = (int) $mybb->get_input('personaje_id', 1);
    $motivo = trim((string) $mybb->get_input('motivo'));

    // Validar campos requeridos.
    foreach ($campos as $c) {
        if (empty($c['required'])) {
            continue;
        }
        $v = trim((string) $mybb->get_input($c['name']));
        if ($v === '') {
            return array('ok' => false, 'msg' => 'Falta un campo obligatorio: «' . $c['label'] . '».');
        }
    }
    if ($pid < 1) {
        return array('ok' => false, 'msg' => 'Elige un personaje (necesitas crear uno primero).');
    }

    $ids = array('personaje_id' => $pid);
    $datos = array();

    switch ((int) $numero) {
        case 1:
            $ids['tipo'] = (string) $mybb->get_input('tipo') === 'pasado' ? 'pasado' : 'presente';
            $ids['tema_id'] = 0;
            $ids['zona'] = trim((string) $mybb->get_input('tema_titulo'));
            $ids['fecha_foro'] = trim((string) $mybb->get_input('fecha_foro'));
            break;
        case 4:
            $dom_id = (int) $mybb->get_input('dominio_id', 1);
            if ($dom_id > 0) {
                $nivel_act = 0;
                $dq = $db->simple_select('ope_dominios_personaje', 'nivel', "personaje_id = {$pid} AND dominio_id = {$dom_id}", array('limit' => 1));
                if ($db->num_rows($dq)) {
                    $nivel_act = (int) $db->fetch_field($dq, 'nivel');
                }
                $ids = array('personaje_id' => $pid, 'dominio_id' => $dom_id, 'nivel' => $nivel_act + 1);
            } else {
                $ids = array('personaje_id' => $pid, 'atributo' => (string) $mybb->get_input('atributo'), 'bloque' => (int) $mybb->get_input('bloque', 1));
            }
            break;
        case 13:
            $ids['idea'] = trim((string) $mybb->get_input('idea'));
            $ids['tier'] = (string) $mybb->get_input('tier');
            $datos['idea'] = $ids['idea'];
            $datos['tier'] = $ids['tier'];
            break;
        case 14:
            $ids['objeto_id'] = (int) $mybb->get_input('objeto_id', 1);
            $ids['zona'] = (string) $mybb->get_input('zona');
            break;
        case 15:
            $ids['tienda_nombre'] = trim((string) $mybb->get_input('tienda_nombre'));
            break;
        case 16:
            $ids['tienda_id'] = (int) $mybb->get_input('tienda_id', 1);
            break;
        case 17:
            $ids['tienda_id'] = (int) $mybb->get_input('tienda_id', 1);
            $ids['objeto_id'] = (int) $mybb->get_input('objeto_id', 1);
            $ids['cantidad'] = max(1, (int) $mybb->get_input('cantidad', 1));
            // El efecto automático lee $res (= $datos): tienda + items a reponer.
            $datos = array('tienda_id' => $ids['tienda_id'], 'items' => array(array('objeto_id' => $ids['objeto_id'], 'stock' => $ids['cantidad'])));
            break;
        case 19:
            $ids['nombre_npc'] = trim((string) $mybb->get_input('nombre_npc'));
            break;
        case 34:
        case 37:
            $isla_id = (int) $mybb->get_input('isla_id', 1);
            $isla = $isla_id > 0 && ope7_tabla_existe('islas') ? ope7_isla_por_id($isla_id) : null;
            $ids['isla_id'] = $isla_id;
            $ids['isla'] = $isla ? $isla['nombre'] : ('isla #' . $isla_id);
            break;
        case 38:
            $destino_id = (int) $mybb->get_input('destino_id', 1);
            $barco_id = (int) $mybb->get_input('barco_id', 1);
            $utensilio_id = (int) $mybb->get_input('utensilio_id', 1);
            $ids['destino_id'] = $destino_id;
            $ids['barco_id'] = $barco_id;
            $ids['utensilio_id'] = $utensilio_id;
            $pj = $pid > 0 ? ope7_pj_get($pid) : null;
            $origen = 'sin ubicación';
            if ($pj) {
                $oi = (int) ($pj['ubicacion_isla_id'] ?? 0);
                $origen = $oi > 0 && ope7_tabla_existe('islas') ? (ope7_isla_por_id($oi)['nombre'] ?? 'isla #' . $oi) : 'sin ubicación';
            }
            $dest = $destino_id > 0 && ope7_tabla_existe('islas') ? ope7_isla_por_id($destino_id) : null;
            $barco = $barco_id > 0 && ope7_tabla_existe('barcos') ? ope7_barco_por_id($barco_id) : null;
            $ut = $utensilio_id > 0 && ope7_tabla_existe('objetos') ? ope7_objeto_nombre($utensilio_id) : '';
            $ids['origen'] = $origen;
            $ids['destino'] = $dest ? $dest['nombre'] : ('isla #' . $destino_id);
            $ids['barco'] = $barco ? $barco['nombre'] : ('barco #' . $barco_id);
            $ids['utensilio'] = $ut;
            $ids['acompanantes'] = $motivo;
            break;
        case 39:
        case 40:
            $ids['tipo_id'] = (int) $mybb->get_input('tipo_id', 1);
            $ids['madera_id'] = (int) $mybb->get_input('madera_id', 1);
            $tipo = null;
            $mad = '';
            if (ope7_tabla_existe('tipos_barcos')) {
                $tq = $db->simple_select('ope_tipos_barcos', '*', 'id = ' . $ids['tipo_id'], array('limit' => 1));
                $tipo = $db->fetch_array($tq);
            }
            if (ope7_tabla_existe('maderas_casco')) {
                $mq = $db->simple_select('ope_maderas_casco', 'nombre', 'id = ' . $ids['madera_id'], array('limit' => 1));
                $mad = $db->fetch_field($mq, 'nombre') ?: '';
            }
            $ids['tipo'] = $tipo ? $tipo['nombre'] : '';
            $ids['madera'] = $mad;
            break;
        case 41:
        case 42:
        case 43:
        case 44:
            $ids['barco_id'] = (int) $mybb->get_input('barco_id', 1);
            $ids['nivel'] = (string) $mybb->get_input('nivel');
            $ids['accion'] = (string) $mybb->get_input('accion');
            $ids['grado'] = (string) $mybb->get_input('grado');
            $ids['modulo_id'] = (int) $mybb->get_input('modulo_id', 1);
            $barco = $ids['barco_id'] > 0 && ope7_tabla_existe('barcos') ? ope7_barco_por_id($ids['barco_id']) : null;
            $ids['barco'] = $barco ? $barco['nombre'] : '';
            if ($numero === 42 && ope7_tabla_existe('modulos_barcos')) {
                $mq = $db->simple_select('ope_modulos_barcos', 'nombre', 'id = ' . $ids['modulo_id'], array('limit' => 1));
                $ids['modulo'] = $db->fetch_field($mq, 'nombre') ?: '';
            }
            break;
        case 62:
            $ids['causa'] = trim((string) $mybb->get_input('causa'));
            $ids['tema_id'] = 0;
            break;
        default:
            break;
    }

    return array('ok' => true, 'ids' => $ids, 'datos' => $datos, 'motivo' => $motivo, 'pid' => $pid);
}

/** Campo → HTML (opciones dinámicas con data-pj para filtrar por personaje). */
function ope7_tramite_pagina_field_html(array $c, $uid, array $dyn)
{
    $e = function ($s) { return htmlspecialchars_uni((string) $s); };
    $name = $c['name'];
    $req = !empty($c['required']) ? ' <span class="tp-req">*</span>' : '';
    $hint = !empty($c['hint']) ? '<span class="fl-hint">' . $c['hint'] . '</span>' : '';
    $req_attr = !empty($c['required']) ? ' required' : '';

    if ($c['tipo'] === 'personaje') {
        $opts = ope7_tramite_pj_opciones($uid);
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-pj" data-pjsel="1"' . $req_attr . '>';
        if (!$opts) {
            $html .= '<option value="">— no tienes personajes —</option>';
        } else {
            $html .= '<option value="">— elige —</option>';
            foreach ($opts as $pid => $lab) {
                $html .= '<option value="' . (int) $pid . '" data-pj="' . (int) $pid . '">' . $e($lab) . '</option>';
            }
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'select' && !empty($c['opciones'])) {
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($c['opciones'] as $v => $lab) {
            $html .= '<option value="' . $e($v) . '">' . $e($lab) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'select' && !empty($c['fuente'])) {
        $opts = $dyn[$c['fuente']] ?? array();
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-dyn" data-fuente="' . $e($c['fuente']) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($opts as $v => $info) {
            $html .= '<option value="' . (int) $v . '" data-pj="' . (int) $info[1] . '">' . $e($info[0]) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'dyn') {
        $opts = $dyn[$c['fuente']] ?? array();
        $html = '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
              . '<select id="f-' . $e($name) . '" name="' . $e($name) . '" class="tp-dyn" data-fuente="' . $e($c['fuente']) . '"' . $req_attr . '>';
        $html .= '<option value="">— elige —</option>';
        foreach ($opts as $v => $info) {
            $html .= '<option value="' . (int) $v . '" data-pj="' . (int) $info[1] . '">' . $e($info[0]) . '</option>';
        }
        $html .= '</select>' . $hint . '</div>';
        return $html;
    }

    if ($c['tipo'] === 'area') {
        $max = (int) ($c['maxlength'] ?? 2000);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<textarea id="f-' . $e($name) . '" name="' . $e($name) . '" maxlength="' . $max . '" class="tp-area"' . $req_attr . '></textarea>' . $hint . '</div>';
    }

    if ($c['tipo'] === 'texto') {
        $max = (int) ($c['maxlength'] ?? 120);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<input type="text" id="f-' . $e($name) . '" name="' . $e($name) . '" maxlength="' . $max . '"' . $req_attr . '>' . $hint . '</div>';
    }

    if ($c['tipo'] === 'number') {
        $min = (int) ($c['min'] ?? 1);
        $max = (int) ($c['max'] ?? 99);
        $val = (int) ($c['value'] ?? 1);
        return '<div class="field"><label class="flabel" for="f-' . $e($name) . '">' . $e($c['label']) . $req . '</label>'
             . '<input type="number" id="f-' . $e($name) . '" name="' . $e($name) . '" min="' . $min . '" max="' . $max . '" value="' . $val . '" class="tp-num"' . $req_attr . '>' . $hint . '</div>';
    }

    return '';
}

/**
 * Página completa de un trámite (POST + render). La usan los 56 tramite-NN.php.
 */
function ope7_tramite_pagina($numero)
{
    global $mybb, $db;

    $numero = (int) $numero;
    $info = ope7_tramite_info($numero);
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    $bbname = htmlspecialchars_uni($mybb->settings['bbname']);
    $uid = (int) ($mybb->user['uid'] ?? 0);
    $es_staff = function_exists('ope7_es_staff') && ope7_es_staff($uid);

    if ($uid < 1) {
        header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
        exit;
    }
    if (!$info) {
        echo 'Trámite no encontrado.';
        exit;
    }
    // Solo-staff: sin página de jugador (el hub no enlaza; acceso directo → bandeja).
    if ($info['quien'] === 'staff' && !$es_staff) {
        header('Location: ' . $mybb->settings['bburl'] . '/tramites.php');
        exit;
    }

    $campos = ope7_tramite_pagina_campos($numero);
    $flash = '';
    $ok_msg = '';

    if ($mybb->request_method === 'post') {
        $r = ope7_tramite_pagina_procesar($numero, $uid, $info, $campos);
        if (!$r['ok']) {
            $flash = '<div class="flash warn">' . htmlspecialchars_uni($r['msg']) . '</div>';
        } else {
            $creado = ope7_tramite_crear($uid, $r['pid'], $numero, $r['motivo'], $r['ids'], $r['datos']);
            if (!$creado['ok']) {
                $flash = '<div class="flash warn">' . htmlspecialchars_uni($creado['msg']) . '</div>';
            } else {
                $ok_msg = ope7_tramite_es_auto($numero)
                    ? 'Trámite ejecutado: ' . htmlspecialchars_uni((string) ($creado['msg'] ?? 'efectos aplicados.'))
                    : 'Solicitud creada (#' . (int) ($creado['tid'] ?? 0) . '): la revisa el staff. Sigue su estado en tus trámites.';
                $flash = '<div class="flash ok">' . $ok_msg . '</div>';
            }
        }
    }

    // Opciones dinámicas (todos los PJ del usuario, filtradas por data-pj).
    $dyn = array('islas' => array(), 'barcos' => array(), 'tiendas' => array(), 'dominios' => array(),
                 'objetos_almacen' => array(), 'objetos_mochila' => array(), 'utensilios' => array());
    foreach ($campos as $c) {
        if ($c['tipo'] === 'dyn' || ($c['tipo'] === 'select' && !empty($c['fuente']))) {
            $dyn[$c['fuente']] = ope7_tramite_pagina_dyn($c['fuente'], $uid);
        }
    }
    $dyn['tipos_barco'] = ope7_tramite_pagina_dyn('tipos_barco', $uid);
    $dyn['maderas'] = ope7_tramite_pagina_dyn('maderas', $uid);
    $dyn['modulos'] = ope7_tramite_pagina_dyn('modulos', $uid);

    $nat_label = ope7_naturaleza_label($info['naturaleza']);
    $quien = ope7_quien_label($info['quien']);
    $nota = ope7_tramite_pagina_nota($numero, $info);
    $es_auto = ope7_tramite_es_auto($numero);

    $html = '';
    foreach ($campos as $c) {
        $html .= ope7_tramite_pagina_field_html($c, $uid, $dyn);
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Trámite <?php echo (int) $numero; ?> — <?php echo htmlspecialchars_uni($info['nombre']); ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tramite">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a><span class="sep">›</span><b>#<?php echo (int) $numero; ?> <?php echo htmlspecialchars_uni($info['nombre']); ?></b>
</div></div>
<div class="wrap">
  <div class="shead"><h1><?php echo htmlspecialchars_uni($info['nombre']); ?></h1><span class="code">VENTANILLA #<?php echo (int) $numero; ?></span><span class="rule"></span></div>
  <?php echo $flash; ?>
  <div class="plate">
    <div class="plate-h"><span class="t">Pedir este trámite</span><span class="c"><?php echo htmlspecialchars_uni($nat_label); ?> · <?php echo htmlspecialchars_uni($quien); ?></span></div>
    <div class="plate-b">
      <p class="tp-nota"><?php echo $nota; ?></p>
      <?php if ($info['efecto'] !== ''): ?>
      <p class="tp-efecto">Efecto: <?php echo htmlspecialchars_uni($info['efecto']); ?></p>
      <?php endif; ?>
      <form method="post" action="<?php echo $bburl; ?>/tramite-<?php echo str_pad((string) $numero, 2, '0', STR_PAD_LEFT); ?>.php" class="tp-form">
        <?php echo $html; ?>
        <div class="tp-actions">
          <button type="submit" class="btn btn-hot"><?php echo $es_auto ? 'Ejecutar ahora' : 'Enviar solicitud'; ?></button>
          <a class="btn btn-ghost" href="<?php echo $bburl; ?>/tramites.php">Volver a mis trámites</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include MYBB_ROOT . 'footer_custom.php'; ?>
<script>
(function () {
  // Los selectores dinámicos (barco, tienda, dominio, objeto…) se filtran por
  // el personaje elegido: cada opción lleva data-pj con su dueño.
  var pjSel = document.querySelector('.tp-pj');
  var dyns = Array.prototype.slice.call(document.querySelectorAll('.tp-dyn'));
  if (pjSel) {
    var aplicar = function () {
      var pid = pjSel.value;
      dyns.forEach(function (sel) {
        Array.prototype.forEach.call(sel.options, function (o) {
          if (!o.getAttribute('data-pj')) { return; } // placeholder
          var ok = o.getAttribute('data-pj') === pid || o.getAttribute('data-pj') === '0';
          o.disabled = !ok;
          if (!ok && o.selected) { sel.value = ''; }
        });
      });
    };
    pjSel.addEventListener('change', aplicar);
    aplicar();
  }
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    const io = new IntersectionObserver(es => es.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
    }), { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
  }
})();
</script>
</body>
</html>
<?php
    exit;
}
