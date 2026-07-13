<?php
/**
 * I-Forge · Lógica de viajes: islas, tramos, solicitud y cierre manual.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_viaje_alta_mar_fid()
{
    global $mybb, $db;
    $fid = (int) ($mybb->settings['ope_alta_mar_fid'] ?? 0);
    if ($fid > 0) {
        return $fid;
    }
    if (!$db->table_exists('forums')) {
        return 0;
    }
    $q = $db->simple_select('forums', 'fid', "name = 'Alta Mar'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return (int) $db->fetch_field($q, 'fid');
    }
    return 0;
}

/** Islas navegables: foros hoja bajo "El Mundo" (regiones → islas). */
function ope_viaje_islas()
{
    global $db;
    $out = array();
    if (!$db->table_exists('forums')) {
        return $out;
    }
    $elMundo = 0;
    $q = $db->simple_select('forums', 'fid', "name = 'El Mundo' AND type = 'c'", array('limit' => 1));
    if ($db->num_rows($q)) {
        $elMundo = (int) $db->fetch_field($q, 'fid');
    }
    if ($elMundo < 1) {
        return $out;
    }
    $regions = array();
    $rq = $db->simple_select('forums', 'fid, name', "pid = {$elMundo} AND type = 'f'", array('order_by' => 'disporder'));
    while ($r = $db->fetch_array($rq)) {
        $regions[(int) $r['fid']] = (string) $r['name'];
    }
    foreach ($regions as $rfid => $rname) {
        $iq = $db->simple_select('forums', 'fid, name, description', "pid = {$rfid} AND type = 'f'", array('order_by' => 'disporder'));
        while ($isla = $db->fetch_array($iq)) {
            $child = $db->fetch_field($db->simple_select('forums', 'COUNT(*) c', "pid = " . (int) $isla['fid']), 'c');
            if ((int) $child > 0) {
                continue;
            }
            $out[] = array(
                'fid'         => (int) $isla['fid'],
                'nombre'      => (string) $isla['name'],
                'region'      => $rname,
                'region_fid'  => $rfid,
                'macro'       => ope_viaje_macro_region($rname),
                'description' => (string) ($isla['description'] ?? ''),
            );
        }
    }
    return $out;
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

/** Calcula tramos entre dos islas (guía AV-02 simplificada). */
function ope_viaje_calc_tramos($fid_origen, $fid_destino)
{
    $o = ope_viaje_isla_por_fid($fid_origen);
    $d = ope_viaje_isla_por_fid($fid_destino);
    if (!$o || !$d) {
        return 2;
    }
    if ((int) $o['fid'] === (int) $d['fid']) {
        return 0;
    }
    if ((int) $o['region_fid'] === (int) $d['region_fid']) {
        return 1;
    }
    if ($o['macro'] === $d['macro']) {
        return 2;
    }
    $tramos = 3;
    $hard = array('calm_belt', 'red_line', 'grand_line_plus');
    if (in_array($o['macro'], $hard, true) || in_array($d['macro'], $hard, true)) {
        $tramos++;
    }
    if ($o['macro'] === 'grand_line_plus' || $d['macro'] === 'grand_line_plus') {
        $tramos++;
    }
    return min(5, max(1, $tramos));
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
        'medico' => 'medico', 'medico' => 'medico', 'artillero' => 'artillero',
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

/** Solicitar viaje: crea hilo OP-Eternal + registro rol_viajes. */
function ope_viaje_solicitar(array $data)
{
    global $db, $mybb;

    if (!$db->table_exists('rol_viajes')) {
        return array('ok' => false, 'msg' => 'Sistema de viajes no instalado. Ejecuta migrate-oleada3.php.');
    }

    $pid_capitan = (int) ($data['pid_capitan'] ?? 0);
    $uid         = (int) ($data['uid'] ?? 0);
    $fid_origen  = (int) ($data['fid_origen'] ?? 0);
    $fid_destino = (int) ($data['fid_destino'] ?? 0);
    $barco_nom   = trim((string) ($data['barco_nombre'] ?? ''));
    $barco_tipo  = trim((string) ($data['barco_tipo'] ?? 'estandar'));
    $extra_pids  = is_array($data['tripulantes'] ?? null) ? $data['tripulantes'] : array();
    $suministros = trim((string) ($data['suministros'] ?? ''));
    $notas       = trim((string) ($data['notas'] ?? ''));

    $origen  = ope_viaje_isla_por_fid($fid_origen);
    $destino = ope_viaje_isla_por_fid($fid_destino);
    if (!$origen || !$destino) {
        return array('ok' => false, 'msg' => 'Origen o destino no válidos.');
    }
    if ($fid_origen === $fid_destino) {
        return array('ok' => false, 'msg' => 'Origen y destino no pueden ser la misma isla.');
    }
    if ($barco_nom === '') {
        return array('ok' => false, 'msg' => 'Indica el nombre del barco.');
    }

    $activo = ope_viaje_por_capitan_activo($pid_capitan);
    if ($activo) {
        return array('ok' => false, 'msg' => 'Ya tienes un viaje activo. Ciérralo antes de iniciar otro.');
    }

    $tramos = ope_viaje_calc_tramos($fid_origen, $fid_destino);
    $trip   = ope_viaje_tripulantes_data($pid_capitan, $extra_pids);
    if (empty($trip)) {
        return array('ok' => false, 'msg' => 'No se pudo cargar la tripulación.');
    }

    $oraculo = ope_oraculo_viaje($tramos, $trip, $barco_tipo);
    $pp      = ope_oraculo_posts_plazo($tramos);

    $viaje_row = array(
        'origen_nombre'  => $origen['nombre'],
        'destino_nombre' => $destino['nombre'],
        'barco_nombre'   => $barco_nom,
        'barco_tipo'     => $barco_tipo,
        'tripulantes_json' => json_encode($trip, JSON_UNESCAPED_UNICODE),
        'tramos'         => $tramos,
        'posts_min'      => $pp['posts_min'],
        'plazo_dias'     => $pp['plazo_dias'],
        'suministros'    => $suministros,
        'notas'          => $notas,
    );

    $fid_alta = ope_viaje_alta_mar_fid();
    if ($fid_alta < 1) {
        return array('ok' => false, 'msg' => 'Foro Alta Mar no configurado.');
    }

    $subject = '🌊 ' . $origen['nombre'] . ' → ' . $destino['nombre'] . ' · ' . $barco_nom;
    // Mensaje corto: el HTML del oráculo se renderiza vía [viaje=ID] en parse_message.
    $tid = ope_system_create_thread($fid_alta, $subject, '[viaje=0]', 'Viaje');
    if ($tid < 1) {
        return array('ok' => false, 'msg' => 'OP-Eternal no pudo crear el hilo. ¿Está activo el bot?');
    }

    $db->insert_query('rol_viajes', array(
        'tid'              => $tid,
        'fid_alta_mar'     => $fid_alta,
        'pid_capitan'      => $pid_capitan,
        'uid_solicitante'  => $uid,
        'fid_origen'       => $fid_origen,
        'fid_destino'      => $fid_destino,
        'origen_nombre'    => $db->escape_string($origen['nombre']),
        'destino_nombre'   => $db->escape_string($destino['nombre']),
        'barco_nombre'     => $db->escape_string($barco_nom),
        'barco_tipo'       => $db->escape_string($barco_tipo),
        'tripulantes_json' => $db->escape_string(json_encode($trip, JSON_UNESCAPED_UNICODE)),
        'tramos'           => $tramos,
        'posts_min'        => (int) $pp['posts_min'],
        'plazo_dias'       => (int) $pp['plazo_dias'],
        'estado'           => 'activo',
        'resultado_json'   => $db->escape_string(json_encode($oraculo, JSON_UNESCAPED_UNICODE)),
        'mods_json'        => $db->escape_string(json_encode($oraculo['mods'] ?? array(), JSON_UNESCAPED_UNICODE)),
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

    ope_viaje_actualizar_ubicacion_trip($trip, $destino['nombre'], 'En tránsito hacia ' . $destino['nombre']);

    $bburl = rtrim((string) $mybb->settings['bburl'], '/');
    return array(
        'ok'       => true,
        'msg'      => 'Viaje iniciado. OP-Eternal ha publicado el Oráculo.',
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

/** Cierre manual a petición del jugador. */
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
        return array('ok' => false, 'msg' => 'Este viaje ya está cerrado.');
    }

    $cap_nombre = 'Capitán';
    if ($db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . (int) $viaje['pid_capitan'], array('limit' => 1));
        if ($db->num_rows($pq)) {
            $cap_nombre = (string) $db->fetch_field($pq, 'nombre');
        }
    }

    $cierre_html = ope_oraculo_cierre_post_html($viaje, $cap_nombre);
    unset($cierre_html);
    $pid_post = ope_system_create_post((int) $viaje['tid'], '[viaje-cierre=' . (int) $viaje_id . ']');
    if ($pid_post < 1) {
        return array('ok' => false, 'msg' => 'No se pudo publicar el post de llegada.');
    }

    $db->update_query('rol_viajes', array(
        'estado'          => 'cerrado',
        'cierre_dateline' => TIME_NOW,
        'cierre_pid'      => (int) $active_pid,
    ), 'viaje_id = ' . (int) $viaje_id);

    $trip = json_decode((string) $viaje['tripulantes_json'], true);
    if (is_array($trip)) {
        ope_viaje_actualizar_ubicacion_trip($trip, (string) $viaje['destino_nombre'], 'Amarrado en ' . $viaje['destino_nombre']);
    }

    $bburl = rtrim((string) $GLOBALS['mybb']->settings['bburl'], '/');
    return array(
        'ok'  => true,
        'msg' => 'Llegada confirmada. OP-Eternal ha publicado el cierre.',
        'url' => $bburl . '/showthread.php?tid=' . (int) $viaje['tid'],
    );
}

function ope_viaje_actualizar_ubicacion_trip(array $trip, string $ubic, string $accion)
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
    $tramos = (int) ($viaje['tramos'] ?? 1);
    $posts  = (int) ($viaje['posts_min'] ?? 6);

    $html  = '<aside class="ope-viaje-panel" id="ope-viaje-panel">';
    $html .= '<div class="ope-viaje-panel-h"><span class="ope-viaje-panel-badge">Viaje en curso</span>';
    $html .= '<span class="ope-viaje-panel-route">' . $orig . ' → ' . $dest . '</span></div>';
    $html .= '<div class="ope-viaje-panel-b">';
    $html .= '<div class="ope-viaje-panel-stats">';
    $html .= '<span><b>' . $tramos . '</b><small>Tramos</small></span>';
    $html .= '<span><b>' . $posts . '</b><small>Posts sug.</small></span>';
    $html .= '<span class="ope-viaje-st-' . ($estado === 'activo' ? 'on' : 'off') . '"><b>' . strtoupper($estado) . '</b><small>Estado</small></span>';
    $html .= '</div>';

    if ($estado === 'activo' && ope_viaje_puede_cerrar($viaje, $uid, $active_pid)) {
        global $mybb;
        $html .= '<form class="ope-viaje-cerrar-form" method="post" action="' . $bburl . '/viajes.php">';
        $html .= '<input type="hidden" name="my_post_key" value="' . htmlspecialchars_uni($mybb->post_code) . '">';
        $html .= '<input type="hidden" name="action" value="cerrar">';
        $html .= '<input type="hidden" name="viaje_id" value="' . (int) $viaje['viaje_id'] . '">';
        $html .= '<p class="ope-viaje-panel-note">Cuando la tripulación haya roteado lo suficiente, solicita la <strong>llegada a ' . $dest . '</strong>. OP-Eternal publicará el cierre.</p>';
        $html .= '<button type="submit" class="ope-btn ope-btn-hot">Solicitar llegada</button>';
        $html .= '</form>';
    } elseif ($estado === 'cerrado') {
        $html .= '<p class="ope-viaje-panel-note ope-viaje-panel-note--done">Travesía completada. Los personajes están en <strong>' . $dest . '</strong>.</p>';
    }

    $html .= '<a href="' . $bburl . '/tramites.php" class="ope-btn ope-btn-ghost ope-btn-sm">Trámites</a>';
    $html .= '</div></aside>';
    return $html;
}
