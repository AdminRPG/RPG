<?php
/**
 * One Piece: Eternal · Tablon de Misiones (backend)
 * -----------------------------------------------------------------
 * Catalogo de misiones escritas por el staff + tomas por PJ (exclusivas).
 *
 * Tablas: rol_misiones, rol_mision_tomas (ver scripts/migrate-misiones.php)
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Catalogo de misiones publicadas. Filtro opcional por estado o zona.
 * Marca cada mision con su toma activa y si esta exclusiva/tomada.
 */
function ope_misiones_catalogo(array $filtros = array())
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_misiones')) {
        return $out;
    }

    $sql_where = "m.estado = 'publicada'";
    if (!empty($filtros['estado'])) {
        $sql_where = "m.estado = '" . $db->escape_string((string) $filtros['estado']) . "'";
    }
    if (!empty($filtros['zona_slug'])) {
        $sql_where .= " AND m.zona_slug = '" . $db->escape_string((string) $filtros['zona_slug']) . "'";
    }

    $q = $db->query("
        SELECT m.*
        FROM {$db->table_prefix}rol_misiones m
        WHERE {$sql_where}
        ORDER BY m.rango ASC, m.dateline DESC
    ");
    $rows = array();
    while ($r = $db->fetch_array($q)) {
        $rows[(int) $r['mision_id']] = $r;
    }

    if (empty($rows)) {
        return array();
    }

    // Toma activa de cada mision (exclusividad: pendiente o en_proceso)
    $tomas_activas = array();
    if ($db->table_exists('rol_mision_tomas')) {
        $ids = implode(',', array_map('intval', array_keys($rows)));
        $qa = $db->query("
            SELECT t.*
            FROM {$db->table_prefix}rol_mision_tomas t
            WHERE t.mision_id IN ({$ids})
              AND t.estado IN ('pendiente','en_proceso')
            ORDER BY t.dateline ASC
        ");
        while ($ta = $db->fetch_array($qa)) {
            $tomas_activas[(int) $ta['mision_id']] = $ta;
        }
    }

    foreach ($rows as $mid => $m) {
        $m['companeros_arr'] = array();
        $m['titular_pid']  = 0;
        $m['titular_nombre'] = '';
        $m['toma_estado']  = '';
        $m['puede_tomar']  = false;
        if (isset($tomas_activas[$mid])) {
            $t = $tomas_activas[$mid];
            $m['titular_pid'] = (int) $t['pid'];
            $m['titular_nombre'] = function_exists('ope_rol_cat_nombre_pid') ? ope_rol_cat_nombre_pid((int) $t['pid']) : '?';
            $m['toma_estado'] = (string) $t['estado'];
            $m['companeros_arr'] = ope_misiones_companeros($t);
        }
        $out[] = $m;
    }
    return $out;
}

/** Mision por id (sin filtro de estado) o null. */
function ope_mision_por_id($mision_id)
{
    global $db;
    $mision_id = (int) $mision_id;
    if ($mision_id < 1 || !$db->table_exists('rol_misiones')) {
        return null;
    }
    $q = $db->simple_select('rol_misiones', '*', "mision_id = {$mision_id}", array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** Busca una toma por su tid (id de hilo en MyBB). */
function ope_mision_por_tid($tid)
{
    global $db;
    $tid = (int) $tid;
    if ($tid < 1 || !$db->table_exists('rol_mision_tomas')) {
        return null;
    }
    $q = $db->simple_select('rol_mision_tomas', '*', "tid = {$tid}", array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** Crea una mision (staff). */
function ope_mision_crear($uid, array $datos)
{
    global $db;
    if (!$db->table_exists('rol_misiones')) {
        return array('ok' => false, 'msg' => 'Las misiones no estan habilitadas.');
    }
    $titulo = trim((string) ($datos['titulo'] ?? ''));
    if ($titulo === '') {
        return array('ok' => false, 'msg' => 'La mision necesita un titulo.');
    }

    $db->insert_query('rol_misiones', array(
        'titulo'            => $db->escape_string($titulo),
        'resumen'           => $db->escape_string((string) ($datos['resumen'] ?? '')),
        'descripcion_larga' => $db->escape_string((string) ($datos['descripcion_larga'] ?? '')),
        'zona_slug'         => $db->escape_string((string) ($datos['zona_slug'] ?? '')),
        'facciones'         => $db->escape_string((string) ($datos['facciones'] ?? '')),
        'recompensa'        => $db->escape_string((string) ($datos['recompensa'] ?? '')),
        'rango'             => $db->escape_string((string) ($datos['rango'] ?? 'D')),
        'peligrosidad'      => (int) ($datos['peligrosidad'] ?? 1),
        'modalidad'         => $db->escape_string((string) ($datos['modalidad'] ?? 'cualquiera')),
        'estado'            => 'publicada',
        'uid_autor'         => (int) $uid,
        'dateline'          => TIME_NOW,
        'lastedit'          => TIME_NOW,
    ));
    return array('ok' => true, 'msg' => 'Mision creada.', 'id' => (int) $db->insert_id());
}

/** Edita una mision existente (staff). */
function ope_mision_editar($mision_id, array $datos)
{
    global $db;
    $mision_id = (int) $mision_id;
    if ($mision_id < 1 || !$db->table_exists('rol_misiones')) {
        return array('ok' => false, 'msg' => 'Mision invalida.');
    }
    $titulo = trim((string) ($datos['titulo'] ?? ''));
    if ($titulo === '') {
        return array('ok' => false, 'msg' => 'La mision necesita un titulo.');
    }
    $db->update_query('rol_misiones', array(
        'titulo'            => $db->escape_string($titulo),
        'resumen'           => $db->escape_string((string) ($datos['resumen'] ?? '')),
        'descripcion_larga' => $db->escape_string((string) ($datos['descripcion_larga'] ?? '')),
        'zona_slug'         => $db->escape_string((string) ($datos['zona_slug'] ?? '')),
        'facciones'         => $db->escape_string((string) ($datos['facciones'] ?? '')),
        'recompensa'        => $db->escape_string((string) ($datos['recompensa'] ?? '')),
        'rango'             => $db->escape_string((string) ($datos['rango'] ?? 'D')),
        'peligrosidad'      => (int) ($datos['peligrosidad'] ?? 1),
        'modalidad'         => $db->escape_string((string) ($datos['modalidad'] ?? 'cualquiera')),
        'estado'            => $db->escape_string((string) ($datos['estado'] ?? 'publicada')),
        'lastedit'          => TIME_NOW,
    ), "mision_id = {$mision_id}");
    return array('ok' => true, 'msg' => 'Mision actualizada.');
}

/** Cambia el estado publicada/inactiva de una mision (staff). */
function ope_mision_set_estado($mision_id, $estado)
{
    global $db;
    $mision_id = (int) $mision_id;
    $estado = in_array((string) $estado, array('publicada', 'inactiva'), true) ? $estado : 'publicada';
    if ($mision_id < 1 || !$db->table_exists('rol_misiones')) {
        return array('ok' => false, 'msg' => 'Mision invalida.');
    }
    $db->update_query('rol_misiones', array('estado' => $estado, 'lastedit' => TIME_NOW), "mision_id = {$mision_id}");
    return array('ok' => true, 'msg' => $estado === 'publicada' ? 'Mision publicada.' : 'Mision oculta.');
}

/**
 * Solicita tomar una mision. Crea una toma 'pendiente'.
 * Exclusiva: no puede existir otra toma pendiente/en_proceso para esa mision
 * si la modalidad es unica, y el PJ no puede tener dos tomas activas.
 */
function ope_mision_solicitar_toma($mision_id, $pid, $uid, array $companeros = array())
{
    global $db;
    if (!$db->table_exists('rol_misiones') || !$db->table_exists('rol_mision_tomas')) {
        return array('ok' => false, 'msg' => 'El sistema de misiones no esta habilitado.');
    }

    $mision = ope_mision_por_id($mision_id);
    if (!$mision) {
        return array('ok' => false, 'msg' => 'Mision no encontrada.');
    }
    if (($mision['estado'] ?? '') !== 'publicada') {
        return array('ok' => false, 'msg' => 'Esta mision no esta abierta.');
    }

    // Exclusividad: ya existe toma activa (pendiente o en_proceso) de esta mision.
    $qa = $db->simple_select(
        'rol_mision_tomas',
        'toma_id',
        "mision_id = " . (int) $mision_id . " AND estado IN ('pendiente','en_proceso')",
        array('limit' => 1)
    );
    if ($db->num_rows($qa)) {
        return array('ok' => false, 'msg' => 'Esta mision ya esta siendo jugada. Es exclusiva.');
    }

    // El PJ no puede tener dos tomas activas a la vez.
    $qp = $db->simple_select(
        'rol_mision_tomas',
        'toma_id',
        "pid = " . (int) $pid . " AND estado IN ('pendiente','en_proceso')",
        array('limit' => 1)
    );
    if ($db->num_rows($qp)) {
        return array('ok' => false, 'msg' => 'Tu personaje ya tiene una mision en curso o en espera.');
    }

    $db->insert_query('rol_mision_tomas', array(
        'mision_id'   => (int) $mision_id,
        'pid'         => (int) $pid,
        'uid'         => (int) $uid,
        'companeros'  => $db->escape_string(json_encode(array_values($companeros), JSON_UNESCAPED_UNICODE)),
        'estado'      => 'pendiente',
        'motivo'      => '',
        'uid_staff'   => 0,
        'dateline'    => TIME_NOW,
        'lastedit'    => TIME_NOW,
    ));
    return array('ok' => true, 'msg' => 'Solicitud enviada. El staff la revisara.');
}

/** Lista de tomas segun estado, con datos de mision y PJ. */
function ope_misiones_tomas($estado = 'pendiente')
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mision_tomas') || !$db->table_exists('rol_misiones')) {
        return $out;
    }
    $q = $db->query("
        SELECT t.*, m.titulo AS mision_titulo, m.zona_slug AS mision_zona
        FROM {$db->table_prefix}rol_mision_tomas t
        LEFT JOIN {$db->table_prefix}rol_misiones m ON m.mision_id = t.mision_id
        WHERE t.estado = '" . $db->escape_string((string) $estado) . "'
        ORDER BY t.dateline ASC
    ");
    while ($r = $db->fetch_array($q)) {
        $r['companeros_arr'] = ope_misiones_companeros($r);
        $r['pid_nombre'] = function_exists('ope_rol_cat_nombre_pid') ? ope_rol_cat_nombre_pid((int) $r['pid']) : '';
        $out[] = $r;
    }
    return $out;
}

/** Cuenta tomas pendientes (para el badge de zona staff). */
function ope_misiones_tomas_pendientes_count()
{
    global $db;
    if (!$db->table_exists('rol_mision_tomas')) {
        return 0;
    }
    $q = $db->simple_select('rol_mision_tomas', 'COUNT(*) AS c', "estado = 'pendiente'");
    return (int) $db->fetch_field($q, 'c');
}

/**
 * Resuelve el fid del foro donde publicar la mision.
 * Busca el foro de la isla por nombre. Si no existe, fallback al setting.
 */
function ope_mision_fid($zona_slug)
{
    global $mybb, $db;

    $zona_slug = (string) $zona_slug;

    // 1. Buscar foro de la isla por nombre
    if ($zona_slug !== '' && $db->table_exists('forums')) {
        $isla_nombre = '';
        if (function_exists('ope_isla_nombre')) {
            $isla_nombre = ope_isla_nombre($zona_slug);
        }
        // Tambien probar con el slug en snake_case convertido a nombre
        if ($isla_nombre === '' || $isla_nombre === $zona_slug) {
            // Intentar matching directo con variaciones del nombre
            $candidatos = array($isla_nombre);
            // Algunos foros tienen nombres ligeramente distintos al catalogo
            if ($isla_nombre !== '') $candidatos[] = $isla_nombre;
            foreach ($candidatos as $cn) {
                if ($cn === '') continue;
                $q = $db->simple_select('forums', 'fid', "name = '" . $db->escape_string($cn) . "' AND type = 'f'", array('limit' => 1));
                if ($db->num_rows($q)) {
                    return (int) $db->fetch_field($q, 'fid');
                }
            }
        }
        // Busqueda LIKE como fallback para emparejar nombres parciales
        $q = $db->simple_select('forums', 'fid', "name LIKE '%" . $db->escape_string($isla_nombre) . "%' AND type = 'f'", array('limit' => 1));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'fid');
        }
    }

    // 2. Fallback: setting configurable
    $fid = (int) ($mybb->settings['ope_misiones_fid'] ?? 0);
    if ($fid > 0) {
        return $fid;
    }

    // 3. Ultimo recurso: foro generico "Misiones"
    if ($db->table_exists('forums')) {
        $q = $db->simple_select('forums', 'fid', "name LIKE '%Mision%' AND type = 'f'", array('limit' => 1));
        if ($db->num_rows($q)) {
            return (int) $db->fetch_field($q, 'fid');
        }
    }

    return 0;
}

/** Datos enriquecidos de una toma para alimentar IA y creacion de hilo. */
function _ope_mision_toma_data_ia($toma)
{
    global $db;
    $mid = (int) ($toma['mision_id'] ?? 0);
    $m   = ope_mision_por_id($mid);
    $zona_slug   = (string) ($m['zona_slug'] ?? '');
    $zona_nombre = $zona_slug !== '' && function_exists('ope_isla_nombre')
                   ? ope_isla_nombre($zona_slug) : $zona_slug;
    $pj_nombre = function_exists('ope_rol_cat_nombre_pid')
                 ? ope_rol_cat_nombre_pid((int) ($toma['pid'] ?? 0)) : '';

    // Determinar macro de la isla para los mods del oraculo
    $macro = '';
    if ($zona_slug !== '' && function_exists('ope_isla_por_slug')) {
        $isla = ope_isla_por_slug($zona_slug);
        $macro = is_array($isla) ? (string) ($isla['macro'] ?? '') : '';
    }

    return array(
        'mision'       => $m,
        'zona_slug'    => $zona_slug,
        'zona_nombre'  => $zona_nombre,
        'pj_nombre'    => $pj_nombre,
        'macro'        => $macro,
        'toma'         => $toma,
    );
}

/** Crea el hilo de una mision y guarda tid + oraculo + intro IA en la toma. */
function _ope_mision_crear_hilo($toma_id, array $data_ia, array $oraculo, string $intro, string $modelo, $staff_uid)
{
    global $db;
    $toma_id = (int) $toma_id;

    $fid = ope_mision_fid((string) ($data_ia['zona_slug'] ?? ''));
    if ($fid < 1) {
        return array('ok' => false, 'msg' => 'Foro de la isla "' . ($data_ia['zona_nombre'] ?? '?') . '" no encontrado. Crea el foro o define ope_misiones_fid.');
    }

    $titulo = htmlspecialchars_uni((string) ($data_ia['mision']['titulo'] ?? 'Mision'));
    $subject = 'Mision: ' . $titulo . ' · ' . $data_ia['pj_nombre'];
    $tid = ope_system_create_thread($fid, $subject, '[mision=0]', 'Mision');
    if ($tid < 1) {
        return array('ok' => false, 'msg' => 'El Narrador no pudo crear el hilo de la mision.');
    }

    // Actualizar la toma con tid, oraculo e IA
    $db->update_query('rol_mision_tomas', array(
        'tid'                    => $tid,
        'oraculo_json'           => $db->escape_string(json_encode($oraculo, JSON_UNESCAPED_UNICODE)),
        'introduccion_api'       => $db->escape_string($intro),
        'introduccion_ai_modelo' => $db->escape_string($modelo),
        'uid_staff'              => (int) $staff_uid,
        'estado'                 => 'en_proceso',
        'lastedit'               => TIME_NOW,
    ), "toma_id = {$toma_id}");

    // Actualizar el primer post con el shortcode correcto
    if ($db->table_exists('posts')) {
        $first_pid = 0;
        $q = $db->simple_select('posts', 'pid', "tid = {$tid}", array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => 1));
        if ($db->num_rows($q)) {
            $first_pid = (int) $db->fetch_field($q, 'pid');
            $db->update_query('posts', array('message' => '[mision=' . $toma_id . ']'), "pid = {$first_pid}");
        }
    }

    return array('ok' => true, 'msg' => 'Mision aprobada e hilo creado.', 'tid' => $tid);
}

/**
 * Aprueba UNA toma individual: valida, tira oraculo, llama IA y crea hilo.
 * Si ya hay llamada batch en curso, usa el resultado pre-generado.
 */
function ope_mision_toma_aprobar($toma_id, $staff_uid)
{
    global $db;
    $toma_id = (int) $toma_id;
    if ($toma_id < 1 || !$db->table_exists('rol_mision_tomas')) {
        return array('ok' => false, 'msg' => 'Toma invalida.');
    }
    $q = $db->simple_select('rol_mision_tomas', '*', "toma_id = {$toma_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    $t = $db->fetch_array($q);
    if (($t['estado'] ?? '') !== 'pendiente') {
        return array('ok' => false, 'msg' => 'Esta solicitud ya fue resuelta.');
    }

    $qa = $db->simple_select(
        'rol_mision_tomas',
        'toma_id',
        "mision_id = " . (int) $t['mision_id'] . " AND toma_id <> {$toma_id} AND estado IN ('pendiente','en_proceso')",
        array('limit' => 1)
    );
    if ($db->num_rows($qa)) {
        return array('ok' => false, 'msg' => 'Ya hay otra toma activa para esta mision.');
    }

    $data = _ope_mision_toma_data_ia($t);
    $oraculo = ope_oraculo_mision_generar(
        (int) ($data['mision']['peligrosidad'] ?? 1),
        $data['macro']
    );
    $data['resumen_oraculo'] = ope_oraculo_mision_resumen($oraculo);
    $data['descripcion_larga'] = (string) ($data['mision']['descripcion_larga'] ?? '');
    $data['facciones'] = (string) ($data['mision']['facciones'] ?? '');
    $data['recompensa'] = (string) ($data['mision']['recompensa'] ?? '');
    $data['rango'] = (string) ($data['mision']['rango'] ?? 'D');

    $intro = '';
    $modelo = '';
    if (function_exists('ope_mision_ai_generar')) {
        $res = ope_mision_ai_generar(array(
            'titulo'            => (string) ($data['mision']['titulo'] ?? ''),
            'resumen'           => (string) ($data['mision']['resumen'] ?? ''),
            'descripcion_larga' => $data['descripcion_larga'],
            'zona_slug'         => $data['zona_slug'],
            'zona_nombre'       => $data['zona_nombre'],
            'facciones'         => $data['facciones'],
            'recompensa'        => $data['recompensa'],
            'rango'             => $data['rango'],
            'peligrosidad'      => (int) ($data['mision']['peligrosidad'] ?? 1),
            'pj_nombre'         => $data['pj_nombre'],
            'resumen_oraculo'   => $data['resumen_oraculo'],
        ));
        if ($res['ok']) {
            $intro  = $res['texto'];
            $modelo = $res['modelo'];
        }
    }

    return _ope_mision_crear_hilo($toma_id, $data, $oraculo, $intro, $modelo, $staff_uid);
}

/**
 * Aprueba VARIAS tomas en batch.
 * - Valida todas primero.
 * - Genera oraculo para cada una.
 * - Hace UNA sola llamada a la IA con todas juntas.
 * - Crea un hilo independiente por cada toma.
 *
 * @param int[] $toma_ids  Array de ids de toma pendientes
 * @param int   $staff_uid UID del staff que aprueba
 * @return array ['ok'=>bool, 'msg'=>string, 'resultados'=>array]
 */
function ope_mision_tomas_aprobar_batch(array $toma_ids, $staff_uid)
{
    global $db;
    $toma_ids = array_values(array_unique(array_map('intval', $toma_ids)));
    if (empty($toma_ids)) {
        return array('ok' => false, 'msg' => 'No se seleccionaron tomas.');
    }

    // Cargar y validar todas
    $tomas_ok = array();
    $errores  = array();
    foreach ($toma_ids as $tid) {
        $q = $db->simple_select('rol_mision_tomas', '*', "toma_id = {$tid}", array('limit' => 1));
        if (!$db->num_rows($q)) {
            $errores[] = "Toma #{$tid} no encontrada.";
            continue;
        }
        $t = $db->fetch_array($q);
        if (($t['estado'] ?? '') !== 'pendiente') {
            $errores[] = "Toma #{$tid} ya fue resuelta.";
            continue;
        }
        $qa = $db->simple_select('rol_mision_tomas', 'toma_id',
            "mision_id = " . (int) $t['mision_id'] . " AND toma_id <> {$tid} AND estado IN ('pendiente','en_proceso')",
            array('limit' => 1));
        if ($db->num_rows($qa)) {
            $errores[] = "Toma #{$tid}: ya hay otra activa para esa mision.";
            continue;
        }
        $tomas_ok[] = $t;
    }

    if (empty($tomas_ok)) {
        return array('ok' => false, 'msg' => 'Ninguna solicitud valida para aprobar. ' . implode(' ', $errores), 'resultados' => array());
    }

    // Preparar datos + oraculo para cada toma
    $batch_data = array();
    $oraculos   = array();
    foreach ($tomas_ok as $idx => $t) {
        $data = _ope_mision_toma_data_ia($t);
        $oraculo = ope_oraculo_mision_generar(
            (int) ($data['mision']['peligrosidad'] ?? 1),
            $data['macro']
        );
        $oraculos[] = $oraculo;
        $data['resumen_oraculo'] = ope_oraculo_mision_resumen($oraculo);
        $data['descripcion_larga'] = (string) ($data['mision']['descripcion_larga'] ?? '');
        $data['facciones'] = (string) ($data['mision']['facciones'] ?? '');
        $data['recompensa'] = (string) ($data['mision']['recompensa'] ?? '');
        $data['rango'] = (string) ($data['mision']['rango'] ?? 'D');

        $batch_data[] = array(
            'titulo'            => (string) ($data['mision']['titulo'] ?? ''),
            'resumen'           => (string) ($data['mision']['resumen'] ?? ''),
            'descripcion_larga' => $data['descripcion_larga'],
            'zona_slug'         => $data['zona_slug'],
            'zona_nombre'       => $data['zona_nombre'],
            'facciones'         => $data['facciones'],
            'recompensa'        => $data['recompensa'],
            'rango'             => $data['rango'],
            'peligrosidad'      => (int) ($data['mision']['peligrosidad'] ?? 1),
            'pj_nombre'         => $data['pj_nombre'],
            'resumen_oraculo'   => $data['resumen_oraculo'],
        );
    }

    // UNA sola llamada a la IA
    $textos_ia = array();
    $modelo    = '';
    if (function_exists('ope_mision_ai_generar_batch')) {
        $res_ai = ope_mision_ai_generar_batch($batch_data);
        if ($res_ai['ok']) {
            $textos_ia = $res_ai['textos'];
            $modelo    = $res_ai['modelo'];
        }
    }

    // Crear hilos independientes
    $resultados = array();
    $ok_count   = 0;
    foreach ($tomas_ok as $idx => $t) {
        $intro = isset($textos_ia[$idx]) ? $textos_ia[$idx] : '';
        $res   = _ope_mision_crear_hilo(
            (int) $t['toma_id'],
            _ope_mision_toma_data_ia($t),
            $oraculos[$idx],
            $intro,
            $modelo,
            $staff_uid
        );
        if ($res['ok']) {
            $ok_count++;
            $resultados[] = array('toma_id' => (int) $t['toma_id'], 'tid' => $res['tid'], 'ok' => true);
        } else {
            $resultados[] = array('toma_id' => (int) $t['toma_id'], 'tid' => 0, 'ok' => false, 'msg' => $res['msg']);
        }
    }

    $msg = "{$ok_count} de " . count($tomas_ok) . " mision(es) aprobada(s).";
    if ($errores) {
        $msg .= ' Con advertencias: ' . implode(' ', $errores);
    }
    return array('ok' => $ok_count > 0, 'msg' => $msg, 'resultados' => $resultados);
}

/** Rechaza una solicitud de toma. */
function ope_mision_toma_rechazar($toma_id, $staff_uid, $motivo)
{
    global $db;
    $toma_id = (int) $toma_id;
    if ($toma_id < 1 || !$db->table_exists('rol_mision_tomas')) {
        return array('ok' => false, 'msg' => 'Toma invalida.');
    }
    $motivo = trim((string) $motivo);
    if ($motivo === '') {
        return array('ok' => false, 'msg' => 'Debes indicar un motivo.');
    }
    $q = $db->simple_select('rol_mision_tomas', 'estado', "toma_id = {$toma_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    if ((string) $db->fetch_field($q, 'estado') !== 'pendiente') {
        return array('ok' => false, 'msg' => 'Esta solicitud ya fue resuelta.');
    }
    $db->update_query('rol_mision_tomas', array(
        'estado'    => 'rechazada',
        'motivo'    => $db->escape_string($motivo),
        'uid_staff' => (int) $staff_uid,
        'lastedit'  => TIME_NOW,
    ), "toma_id = {$toma_id}");
    return array('ok' => true, 'msg' => 'Solicitud rechazada.');
}

/** Cierra una mision en curso: completada o fallida (staff). */
function ope_mision_toma_cerrar($toma_id, $staff_uid, $resultado, $motivo = '')
{
    global $db;
    $toma_id = (int) $toma_id;
    $resultado = ($resultado === 'fallida') ? 'fallida' : 'completada';
    if ($toma_id < 1 || !$db->table_exists('rol_mision_tomas')) {
        return array('ok' => false, 'msg' => 'Toma invalida.');
    }
    $q = $db->simple_select('rol_mision_tomas', 'estado', "toma_id = {$toma_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Toma no encontrada.');
    }
    if ((string) $db->fetch_field($q, 'estado') !== 'en_proceso') {
        return array('ok' => false, 'msg' => 'Solo se pueden cerrar misiones en curso.');
    }
    $db->update_query('rol_mision_tomas', array(
        'estado'    => $resultado,
        'motivo'    => $db->escape_string((string) ($motivo ?? '')),
        'uid_staff' => (int) $staff_uid,
        'lastedit'  => TIME_NOW,
    ), "toma_id = {$toma_id}");
    return array('ok' => true, 'msg' => $resultado === 'completada' ? 'Mision marcada como completada.' : 'Mision marcada como fallida.');
}

/** Deserializa companeros de una fila de toma. */
function ope_misiones_companeros(array $row)
{
    $raw = (string) ($row['companeros'] ?? '');
    if ($raw === '') {
        return array();
    }
    $arr = json_decode($raw, true);
    return is_array($arr) ? array_values($arr) : array();
}

/** Etiquetas de rango legibles. */
function ope_mision_rango_label($rango)
{
    $map = array('S' => 'S', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D');
    return $map[strtoupper((string) $rango)] ?? 'D';
}

/** Etiqueta de modalidad legible. */
function ope_mision_modalidad_label($modalidad)
{
    $map = array('solo' => 'Solo', 'grupo' => 'Grupo', 'cualquiera' => 'Solo o grupo');
    return $map[(string) $modalidad] ?? 'Solo o grupo';
}