<?php
/**
 * One Piece: Eternal · Lógica de viajes: islas, tramos, solicitud y cierre manual.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_viaje_alta_mar_fid(string $macro_destino = '')
{
    global $mybb, $db;

    if ($macro_destino !== '' && $db->table_exists('forums')) {
        $macros_labels = array(
            'east_blue'  => 'East Blue',
            'west_blue'  => 'West Blue',
            'north_blue' => 'North Blue',
            'south_blue' => 'South Blue',
            'calm_belt'  => 'Calm Belt',
            'red_line'   => 'Red Line',
            'paradise'   => 'Paraíso',
            'new_world'  => 'New World',
        );
        $lbl = isset($macros_labels[$macro_destino]) ? $macros_labels[$macro_destino] : '';
        if ($lbl !== '') {
            $name = 'Mar Abierto (' . $lbl . ')';
            $q = $db->simple_select('forums', 'fid', "name = '" . $db->escape_string($name) . "'", array('limit' => 1));
            if ($db->num_rows($q)) {
                return (int) $db->fetch_field($q, 'fid');
            }
        }
    }

    $fid = (int) ($mybb->settings['ope_alta_mar_fid'] ?? 0);
    if ($fid > 0) {
        return $fid;
    }
    if ($db->table_exists('forums')) {
        $q = $db->simple_select('forums', 'fid', "name LIKE 'Mar Abierto%' OR name = 'Alta Mar'", array('limit' => 1));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'fid');
        }
    }
    return 0;
}

/** Islas navegables: catálogo de 44 islas o foros hoja. */
function ope_viaje_islas()
{
    // Retorna las 44 islas del catálogo oficial
    if (function_exists('ope_islas_catalogo')) {
        $cat = ope_islas_catalogo();
        $out = array();
        foreach ($cat as $i) {
            $out[] = array(
                'slug'        => $i['slug'],
                'fid'         => 0,
                'nombre'      => $i['nombre'],
                'region'      => $i['region'],
                'macro'       => $i['macro'],
                'tier'        => $i['tier'],
                'peligro_base'=> $i['peligro_base'],
                'description' => $i['region'] . ' (Tier ' . $i['tier'] . ')',
            );
        }
        return $out;
    }
    return array();
}

function ope_viaje_macro_region(string $region_name)
{
    $n = strtolower($region_name);
    if (strpos($n, 'blue') !== false) return 'blues';
    if (strpos($n, 'grand line') !== false) return 'grand_line';
    if (strpos($n, 'calm') !== false) return 'calm_belt';
    if (strpos($n, 'red line') !== false) return 'red_line';
    if (strpos($n, 'para') !== false || strpos($n, 'new world') !== false) return 'grand_line_plus';
    return 'otro';
}

function ope_viaje_isla_por_fid($fid)
{
    $fid = (int) $fid;
    foreach (ope_viaje_islas() as $i) {
        if ((int) $i['fid'] === $fid) {
            return $i;
        }
    }
    return null;
}

/** Tripulación activa del personaje + compañeros seleccionados. */
function ope_viaje_tripulantes_data($pid_capitan, array $extra_pids = array())
{
    global $db;
    $out = array();
    $pids = array_unique(array_merge(array((int) $pid_capitan), array_map('intval', $extra_pids)));
    $pids = array_filter($pids, function ($p) { return $p > 0; });

    $oficio_map = array(
        'capitan' => 'navegante', 'navegante' => 'navegante', 'timonel' => 'timonel',
        'vigia' => 'vigia', 'carpintero' => 'carpintero', 'cocinero' => 'cocinero',
        'medico' => 'medico', 'artillero' => 'artillero',
    );

    foreach ($pids as $pid) {
        if (!$db->table_exists('rol_personajes')) continue;
        $q = $db->simple_select('rol_personajes', 'pid, nombre', "pid = {$pid} AND estado = 'aprobado'", array('limit' => 1));
        if (!$db->num_rows($q)) continue;
        $row = $db->fetch_array($q);
        $oficio = 'tripulante';
        $rol = '';
        if (function_exists('ope_rol_cat_tripulacion_miembro')) {
            $m = ope_rol_cat_tripulacion_miembro($pid);
            if ($m) {
                $rol = strtolower(trim((string) ($m['rol'] ?? '')));
                $oficio = $oficio_map[$rol] ?? ($rol !== '' ? $rol : 'tripulante');
            }
        }
        if ((int) $pid === (int) $pid_capitan) {
            $oficio = 'navegante';
            $rol = $rol !== '' ? $rol : 'capitan';
        }
        $out[] = array(
            'pid'    => (int) $pid,
            'nombre' => (string) $row['nombre'],
            'rol'    => $rol,
            'oficio' => $oficio,
        );
    }
    return $out;
}

function ope_viaje_por_tid($tid)
{
    global $db;
    $tid = (int) $tid;
    if ($tid < 1 || !$db->table_exists('rol_viajes')) {
        return null;
    }
    $q = $db->simple_select('rol_viajes', '*', "tid = {$tid}", array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

function ope_viaje_por_id($viaje_id)
{
    global $db;
    $viaje_id = (int) $viaje_id;
    if ($viaje_id < 1 || !$db->table_exists('rol_viajes')) {
        return null;
    }
    $q = $db->simple_select('rol_viajes', '*', "viaje_id = {$viaje_id}", array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** ¿Puede este usuario solicitar cierre del viaje? Capitán o staff. */
function ope_viaje_puede_cerrar(array $viaje, $uid, $active_pid)
{
    $uid = (int) $uid;
    $active_pid = (int) $active_pid;
    if (($viaje['estado'] ?? '') !== 'activo') {
        return false;
    }
    if ((int) ($viaje['pid_capitan'] ?? 0) === $active_pid && $active_pid > 0) {
        return true;
    }
    if ((int) ($viaje['uid_solicitante'] ?? 0) === $uid && $uid > 0) {
        return true;
    }
    $staff = (int) ($GLOBALS['mybb']->user['ope_staff_level'] ?? 0);
    return $staff >= 1;
}

/** Solicitar viaje: utiliza la matriz de rutas, barcos, items y oráculo v2. */
function ope_viaje_solicitar(array $data)
{
    global $db, $mybb;

    if (!$db->table_exists('rol_viajes')) {
        return array('ok' => false, 'msg' => 'Sistema de viajes no instalado. Ejecuta migrate-navegacion.php.');
    }

    $pid_capitan = (int) ($data['pid_capitan'] ?? 0);
    $uid         = (int) ($data['uid'] ?? 0);
    $barco_id    = (int) ($data['barco_id'] ?? 0);

    // Cargar personaje capitán para obtener nivel e isla_actual
    $pj_nivel = 1;
    $origen_slug = 'isla_dawn';
    $pj_estado = '';
    if ($db->table_exists('rol_personajes') && $pid_capitan > 0) {
        $pq = $db->simple_select('rol_personajes', 'nivel, isla_actual, estado', "pid = {$pid_capitan}", array('limit' => 1));
        if ($db->num_rows($pq)) {
            $prow = $db->fetch_array($pq);
            $pj_nivel = (int) ($prow['nivel'] ?? 1);
            $pj_estado = (string) ($prow['estado'] ?? '');
            if (!empty($prow['isla_actual'])) {
                $origen_slug = (string) $prow['isla_actual'];
            }
        }
    }
    if ($pj_estado !== '' && $pj_estado !== 'aprobado') {
        $estados = array('borrador' => 'en borrador', 'revision' => 'en revisión', 'rechazado' => 'rechazado', 'eliminado' => 'eliminado');
        $lbl = $estados[$pj_estado] ?? $pj_estado;
        return array('ok' => false, 'msg' => 'Tu personaje no está aprobado para navegar (estado: ' . $lbl . '). Espera a que el Staff lo valide.');
    }

    // Permitir sobreescribir origen si viene especificado como slug
    if (!empty($data['origen_slug'])) {
        $origen_slug = (string) $data['origen_slug'];
    }

    $destino_slug = (string) ($data['destino_slug'] ?? '');
    if ($destino_slug === '' && !empty($data['fid_destino'])) {
        $d_isla = ope_viaje_isla_por_fid($data['fid_destino']);
        if ($d_isla) $destino_slug = $d_isla['slug'];
    }

    $isla_origen  = function_exists('ope_isla_por_slug') ? ope_isla_por_slug($origen_slug) : null;
    $isla_destino = function_exists('ope_isla_por_slug') ? ope_isla_por_slug($destino_slug) : null;

    if (!$isla_origen || !$isla_destino) {
        return array('ok' => false, 'msg' => 'Isla de origen o destino no válidas.');
    }
    if ($origen_slug === $destino_slug) {
        return array('ok' => false, 'msg' => 'Origen y destino no pueden ser la misma isla.');
    }

    // Cargar barco del personaje
    $barco = array();
    if ($barco_id > 0 && function_exists('ope_barco_obtener')) {
        $barco = ope_barco_obtener($barco_id);
    }
    if (empty($barco) && function_exists('ope_barco_lista')) {
        $barcos_pj = ope_barco_lista($pid_capitan);
        if (!empty($barcos_pj)) {
            $barco = $barcos_pj[0];
            $barco_id = (int) $barco['barco_id'];
        }
    }
    $barco_nom  = trim((string) ($barco['nombre'] ?? ($data['barco_nombre'] ?? 'Bote estándar')));
    $barco_tipo = trim((string) ($barco['tipo'] ?? ($data['barco_tipo'] ?? 'bote')));

    // Cargar items de navegación equipados
    $items_slugs = function_exists('ope_nav_item_slugs') ? ope_nav_item_slugs($pid_capitan) : array();
    if (!empty($data['items']) && is_array($data['items'])) {
        $items_slugs = array_unique(array_merge($items_slugs, $data['items']));
    }

    $extra_pids  = is_array($data['tripulantes'] ?? null) ? $data['tripulantes'] : array();
    $suministros = trim((string) ($data['suministros'] ?? ''));
    $notas       = trim((string) ($data['notas'] ?? ''));

    $activo = ope_viaje_por_capitan_activo($pid_capitan);
    if ($activo) {
        return array('ok' => false, 'msg' => 'Ya tienes un viaje activo. Ciérralo antes de iniciar otro.');
    }

    // Cargar tripulación
    $trip = ope_viaje_tripulantes_data($pid_capitan, $extra_pids);
    if (empty($trip)) {
        return array('ok' => false, 'msg' => 'No se pudo cargar la tripulación.');
    }

    // Calcular la ruta marítima completa
    if (function_exists('ope_navegacion_calcular_ruta')) {
        $ruta_res = ope_navegacion_calcular_ruta($origen_slug, $destino_slug, $barco, $items_slugs, $trip, $pj_nivel);
        if (!$ruta_res['ok']) {
            return array('ok' => false, 'msg' => $ruta_res['msg']);
        }
    } else {
        return array('ok' => false, 'msg' => 'Motor de rutas no cargado.');
    }

    $tramos_orig    = (int) $ruta_res['tramos_total'];
    $tramos         = 1;
    $nivel_peligro  = (string) $ruta_res['nivel_peligro'];
    $peligro_idx    = (int) $ruta_res['nivel_peligro_idx'];
    $peligro_total  = (int) $ruta_res['peligro_acumulado'];
    $es_temeraria   = !empty($ruta_res['es_temeraria']);

    // Posts y plazo según nivel de peligro (ya no dependen de tramos)
    $posts_sugeridos = max(3, $peligro_idx * 3 + 3);
    $plazo_offrol    = max(3, $peligro_idx * 2 + 3);
    $dias_onrol      = (int) round($plazo_offrol * 1.5, 1);

    // Generar Oráculo v2: 1 tramo con 6 cartas base + 1 extra por tramo original
    if (function_exists('ope_oraculo_v2_viaje')) {
        $oraculo = ope_oraculo_v2_viaje($tramos_orig, $ruta_res['mods_total'], $peligro_idx, $isla_origen['macro']);
    } else {
        $oraculo = array('mods' => array(), 'tramos' => array());
    }

    // Introducción narrativa generada por IA (best effort, cadena de modelos con fallback en 429)
    $viaje_intro_texto  = '';
    $viaje_intro_modelo = '';
    if (function_exists('ope_viaje_ai_generar') && ope_viaje_ai_activo()) {
        $trip_nombres = array();
        foreach ($trip as $tm) {
            if (!empty($tm['nombre'])) $trip_nombres[] = (string) $tm['nombre'];
        }
        $res_ai = ope_viaje_ai_generar($isla_origen, $isla_destino, $oraculo, array(
            'nivel_peligro' => $nivel_peligro,
            'macro'         => $isla_origen['macro'] . ' → ' . $isla_destino['macro'],
            'barco'         => $barco_nom,
            'tripulacion'   => implode(', ', $trip_nombres),
        ));
        if (!empty($res_ai['ok']) && $res_ai['texto'] !== '') {
            $viaje_intro_texto  = (string) $res_ai['texto'];
            $viaje_intro_modelo = (string) ($res_ai['modelo'] ?? '');
        }
    }

    $fid_alta = ope_viaje_alta_mar_fid($isla_destino['macro']);
    if ($fid_alta < 1) {
        return array('ok' => false, 'msg' => 'Foro Alta Mar no configurado.');
    }

    $subject = 'Travesía: ' . $isla_origen['nombre'] . ' → ' . $isla_destino['nombre'] . ' · ' . $barco_nom;
    $tid = ope_system_create_thread($fid_alta, $subject, '[viaje=0]', 'Viaje');
    if ($tid < 1) {
        return array('ok' => false, 'msg' => 'El Narrador no pudo crear el hilo en Alta Mar.');
    }

    $db->insert_query('rol_viajes', array(
        'tid'              => $tid,
        'fid_alta_mar'     => $fid_alta,
        'pid_capitan'      => $pid_capitan,
        'uid_solicitante'  => $uid,
        'fid_origen'       => 0,
        'fid_destino'      => 0,
        'origen_slug'      => $db->escape_string($origen_slug),
        'destino_slug'     => $db->escape_string($destino_slug),
        'origen_nombre'    => $db->escape_string($isla_origen['nombre']),
        'destino_nombre'   => $db->escape_string($isla_destino['nombre']),
        'barco_id'         => $barco_id,
        'barco_nombre'     => $db->escape_string($barco_nom),
        'barco_tipo'       => $db->escape_string($barco_tipo),
        'tripulantes_json' => $db->escape_string(json_encode($trip, JSON_UNESCAPED_UNICODE)),
        'items_json'       => $db->escape_string(json_encode($items_slugs, JSON_UNESCAPED_UNICODE)),
        'ruta_json'        => $db->escape_string(json_encode($ruta_res, JSON_UNESCAPED_UNICODE)),
        'tramos'           => $tramos,
        'peligro_total'    => $peligro_total,
        'nivel_peligro'    => $db->escape_string($nivel_peligro),
        'dias_onrol'       => $dias_onrol,
        'es_temeraria'     => $es_temeraria ? 1 : 0,
        'posts_min'        => $posts_sugeridos,
        'plazo_dias'       => $plazo_offrol,
        'estado'           => 'activo',
        'resultado_json'   => $db->escape_string(json_encode($oraculo, JSON_UNESCAPED_UNICODE)),
        'introduccion_api' => $db->escape_string($viaje_intro_texto),
        'introduccion_ai_modelo' => $db->escape_string($viaje_intro_modelo),
        'mods_json'        => $db->escape_string(json_encode($ruta_res['mods_total'] ?? array(), JSON_UNESCAPED_UNICODE)),
        'suministros'      => $db->escape_string($suministros),
        'notas'            => $db->escape_string($notas),
        'dateline'         => TIME_NOW,
    ));
    $viaje_id = (int) $db->insert_id();

    // Sustituir placeholder del primer post por el shortcode real.
    $pq = $db->simple_select('posts', 'pid', "tid = {$tid}", array('order_by' => 'dateline', 'order_dir' => 'asc', 'limit' => 1));
    if ($db->num_rows($pq)) {
        $first_pid = (int) $db->fetch_field($pq, 'pid');
        $db->update_query('posts', array(
            'message' => $db->escape_string('[viaje=' . $viaje_id . ']'),
        ), "pid = {$first_pid}");
    }

    // Actualizar estado 'En tránsito' sin cambiar la isla_actual todavía (se cambia al cerrar)
    ope_viaje_actualizar_ubicacion_trip($trip, $isla_origen['nombre'], 'En tránsito hacia ' . $isla_destino['nombre']);

    $bburl = rtrim((string) $mybb->settings['bburl'], '/');
    return array(
        'ok'       => true,
        'msg'      => 'Viaje iniciado. Lyria ha publicado el Oráculo.',
        'tid'      => $tid,
        'viaje_id' => $viaje_id,
        'url'      => $bburl . '/showthread.php?tid=' . $tid,
    );
}

function ope_viaje_por_capitan_activo($pid_capitan)
{
    global $db;
    $pid_capitan = (int) $pid_capitan;
    if ($pid_capitan < 1 || !$db->table_exists('rol_viajes')) {
        return null;
    }
    $q = $db->simple_select('rol_viajes', '*', "pid_capitan = {$pid_capitan} AND estado = 'activo'", array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** Solicitar cierre del viaje (jugador). Marca el viaje como pendiente de revision staff. */
function ope_viaje_cerrar($viaje_id, $uid, $active_pid)
{
    global $db;

    $viaje = ope_viaje_por_id($viaje_id);
    if (!$viaje) {
        return array('ok' => false, 'msg' => 'Viaje no encontrado.');
    }
    if (!ope_viaje_puede_cerrar($viaje, $uid, $active_pid)) {
        return array('ok' => false, 'msg' => 'No puedes cerrar este viaje.');
    }
    if (($viaje['estado'] ?? '') !== 'activo') {
        return array('ok' => false, 'msg' => 'Este viaje ya esta cerrado o pendiente de revision.');
    }

    $cap_nombre = 'Capitan';
    if ($db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . (int) $viaje['pid_capitan'], array('limit' => 1));
        if ($db->num_rows($pq)) {
            $cap_nombre = (string) $db->fetch_field($pq, 'nombre');
        }
    }

    $intentos = (int) ($viaje['revision_intentos'] ?? 0) + 1;
    $bburl = rtrim((string) $GLOBALS['mybb']->settings['bburl'], '/');

    // Publicar post de solicitud de cierre
    $viajes_url = $bburl . '/viajes.php';
    $post_msg = '[viaje-solicitud-cierre=' . (int) $viaje_id . ']';
    $pid_post = ope_system_create_post((int) $viaje['tid'], $post_msg);
    if ($pid_post < 1) {
        return array('ok' => false, 'msg' => 'No se pudo publicar la solicitud de cierre.');
    }

    $db->update_query('rol_viajes', array(
        'estado'            => 'pendiente_cierre',
        'revision_intentos' => $intentos,
        'cierre_pid'        => (int) $active_pid,
        'cierre_dateline'   => TIME_NOW,
    ), 'viaje_id = ' . (int) $viaje_id);

    return array(
        'ok'  => true,
        'msg' => 'Solicitud de cierre enviada. El staff revisara tu travesia y confirmara la llegada.',
        'url' => $bburl . '/showthread.php?tid=' . (int) $viaje['tid'],
    );
}

/** Ejecuta el cierre real del viaje (llamado por el staff tras aprobar la revision). */
function ope_viaje_cerrar_ejecutar(array $viaje)
{
    global $db;

    $viaje_id = (int) ($viaje['viaje_id'] ?? 0);
    $cap_nombre = 'Capitan';
    if ($db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . (int) $viaje['pid_capitan'], array('limit' => 1));
        if ($db->num_rows($pq)) {
            $cap_nombre = (string) $db->fetch_field($pq, 'nombre');
        }
    }

    // Post de llegada
    $pid_post = ope_system_create_post((int) $viaje['tid'], '[viaje-cierre=' . (int) $viaje_id . ']');
    if ($pid_post < 1) {
        return array('ok' => false, 'msg' => 'No se pudo publicar el post de llegada.');
    }

    $db->update_query('rol_viajes', array(
        'estado' => 'cerrado',
    ), 'viaje_id = ' . (int) $viaje_id);

    // Actualizar despensa y estado del casco del barco
    $barco_id  = (int) ($viaje['barco_id'] ?? 0);
    $ruta_data = json_decode((string) ($viaje['ruta_json'] ?? '{}'), true);
    $tramos    = max(1, (int) ($ruta_data['tramos_total'] ?? 1));
    $trip      = json_decode((string) ($viaje['tripulantes_json'] ?? '[]'), true);
    if (!is_array($trip)) $trip = array();

    $tiene_cocinero   = false;
    $tiene_carpintero = false;
    foreach ($trip as $t_member) {
        $ofi = strtolower((string)($t_member['oficio'] ?? ''));
        if ($ofi === 'cocinero')   $tiene_cocinero = true;
        if ($ofi === 'carpintero') $tiene_carpintero = true;
    }

    if ($barco_id > 0 && function_exists('ope_barco_obtener')) {
        $barco_data = ope_barco_obtener($barco_id);
        if ($barco_data) {
            $consumo_por_tramo = $tiene_cocinero ? 5 : 10;
            $despensa_actual   = (int) ($barco_data['despensa'] ?? 100);
            $nueva_despensa    = max(0, $despensa_actual - ($tramos * $consumo_por_tramo));

            $desgaste_base   = !empty($viaje['es_temeraria']) ? 10 : 5;
            $desgaste_total  = $tramos * $desgaste_base;
            if ($tiene_carpintero) {
                $desgaste_total = (int) ceil($desgaste_total * 0.5);
            }
            $casco_actual = (int) ($barco_data['estado_casco'] ?? 100);
            $nuevo_casco  = max(0, $casco_actual - $desgaste_total);

            if (function_exists('ope_barco_actualizar_despensa')) {
                ope_barco_actualizar_despensa($barco_id, $nueva_despensa);
            }
            if (function_exists('ope_barco_actualizar_casco')) {
                ope_barco_actualizar_casco($barco_id, $nuevo_casco);
            }
        }
    }

    if (is_array($trip)) {
        $destino_slug = (string) ($viaje['destino_slug'] ?? '');
        ope_viaje_actualizar_ubicacion_trip(
            $trip,
            (string) $viaje['destino_nombre'],
            'Amarrado en ' . $viaje['destino_nombre'],
            $destino_slug
        );
    }

    return array('ok' => true);
}

function ope_viaje_actualizar_ubicacion_trip(array $trip, string $ubic, string $accion, string $isla_slug = '')
{
    global $db;
    if (!$db->table_exists('rol_personajes')) {
        return;
    }
    foreach ($trip as $t) {
        $pid = (int) ($t['pid'] ?? 0);
        if ($pid < 1) continue;
        $upd = array();
        if ($db->field_exists('mundo_ubic', 'rol_personajes')) {
            $upd['mundo_ubic'] = $db->escape_string($ubic);
        }
        if ($db->field_exists('mundo_accion', 'rol_personajes')) {
            $upd['mundo_accion'] = $db->escape_string($accion);
        }
        if ($isla_slug !== '' && $db->field_exists('isla_actual', 'rol_personajes')) {
            $upd['isla_actual'] = $db->escape_string($isla_slug);
        }
        if ($upd) {
            $db->update_query('rol_personajes', $upd, "pid = {$pid}");
        }
    }
}

/** Panel HTML en showthread para viajes activos. */
function ope_viaje_panel_showthread($tid, $uid, $active_pid)
{
    $viaje = ope_viaje_por_tid($tid);
    if (!$viaje) {
        return '';
    }

    $estado = (string) ($viaje['estado'] ?? '');
    $bburl  = htmlspecialchars_uni(rtrim((string) $GLOBALS['mybb']->settings['bburl'], '/'));
    $dest   = htmlspecialchars_uni((string) $viaje['destino_nombre']);
    $orig   = htmlspecialchars_uni((string) $viaje['origen_nombre']);
    $posts  = (int) ($viaje['posts_min'] ?? 6);
    $dias_onrol = (int) ($viaje['dias_onrol'] ?? 2);
    $peligro    = htmlspecialchars_uni((string) ($viaje['nivel_peligro'] ?? 'bajo'));

    $html  = '<aside class="ope-viaje-panel" id="ope-viaje-panel">';
    $html .= '<div class="ope-viaje-panel-h"><span class="ope-viaje-panel-badge">Viaje en curso</span>';
    $html .= '<span class="ope-viaje-panel-route">' . $orig . ' &rarr; ' . $dest . '</span></div>';
    $html .= '<div class="ope-viaje-panel-b">';
    $html .= '<div class="ope-viaje-panel-stats">';
    $html .= '<span><b>' . $dias_onrol . 'd</b><small>Días on-rol</small></span>';
    $html .= '<span><b>' . ucfirst($peligro) . '</b><small>Peligro</small></span>';
    $html .= '<span class="ope-viaje-st-' . ($estado === 'activo' ? 'on' : 'off') . '"><b>' . strtoupper($estado) . '</b><small>Estado</small></span>';
    $html .= '</div>';

    if ($estado === 'activo') {
        $html .= '<p class="ope-viaje-panel-note">Narra con libertad como se desenvuelve la travesia. Cuando la tripulacion haya roleado lo suficiente, solicita la <strong>llegada</strong> desde el <a href="' . $bburl . '/viajes.php">Planificador de Navegacion</a>.</p>';
    } elseif ($estado === 'pendiente_cierre') {
        $html .= '<p class="ope-viaje-panel-note ope-viaje-panel-note--pending">Cierre solicitado. El staff esta revisando la travesia. Recibiras una notificacion cuando se apruebe o rechace.</p>';
    } elseif ($estado === 'cerrado') {
        $html .= '<p class="ope-viaje-panel-note ope-viaje-panel-note--done">Travesia completada. Los personajes estan amarrados en <strong>' . $dest . '</strong>.</p>';
    }

    $html .= '<a href="' . $bburl . '/tramites.php" class="ope-btn ope-btn-ghost ope-btn-sm">Trámites</a>';
    $html .= '</div></aside>';
    return $html;
}

function ope_viaje_historial_por_barco($barco_id, $limit = 15)
{
    global $db;
    $barco_id = (int)$barco_id;
    $limit = (int)$limit;
    $out = array();
    if ($barco_id < 1 || !$db->table_exists('rol_viajes')) return $out;

    $q = $db->query("
        SELECT viaje_id, origen_nombre, destino_nombre, estado, tramos, nivel_peligro, dateline
        FROM {$db->table_prefix}rol_viajes
        WHERE barco_id = {$barco_id}
        ORDER BY dateline DESC
        LIMIT {$limit}
    ");
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    return $out;
}
