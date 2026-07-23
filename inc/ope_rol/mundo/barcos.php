<?php
/**
 * One Piece: Eternal · Barcos del Personaje (CRUD sobre rol_barcos)
 * -----------------------------------------------------------------
 * Gestiona la entidad barco vinculada a un personaje.
 * Cada PJ tiene al menos un barco (bote basico por defecto al crearse).
 *
 * Uso:
 *   $barcos = ope_barco_lista($pid);
 *   $barco  = ope_barco_obtener($barco_id);
 *   $r      = ope_barco_crear($pid, $uid, 'Mi Barco', 'balandra');
 *   $tipos  = ope_navegacion_barcos_tipos();  // en matriz_rutas.php
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Lista todos los barcos de un personaje.
 */
function ope_barco_lista($pid)
{
    global $db;
    $pid = (int)$pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('rol_barcos')) {
        return $out;
    }
    $q = $db->simple_select('rol_barcos', '*', "pid = {$pid}", array('order_by' => 'dateline', 'order_dir' => 'ASC'));
    while ($row = $db->fetch_array($q)) {
        $row['mejoras'] = json_decode((string)($row['mejoras_json'] ?? '{}'), true);
        if (!is_array($row['mejoras'])) $row['mejoras'] = array();
        // Inyectar vel desde tipo
        $tipos = ope_navegacion_barcos_tipos();
        $row['vel'] = isset($tipos[$row['tipo']]) ? (int)$tipos[$row['tipo']]['vel'] : 2;
        $row['tipo_label'] = isset($tipos[$row['tipo']]) ? $tipos[$row['tipo']]['label'] : $row['tipo'];
        $out[] = $row;
    }
    return $out;
}

/**
 * Obtiene un barco por su ID.
 */
function ope_barco_obtener($barco_id)
{
    global $db;
    $barco_id = (int)$barco_id;
    if ($barco_id < 1 || !$db->table_exists('rol_barcos')) {
        return null;
    }
    $q = $db->simple_select('rol_barcos', '*', "barco_id = {$barco_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $row = $db->fetch_array($q);
    $row['mejoras'] = json_decode((string)($row['mejoras_json'] ?? '{}'), true);
    if (!is_array($row['mejoras'])) $row['mejoras'] = array();
    $row['galeria_fotos'] = json_decode((string)($row['fotos_json'] ?? '[]'), true);
    if (!is_array($row['galeria_fotos'])) $row['galeria_fotos'] = array();
    if (empty($row['galeria_fotos']) && !empty($row['foto_url'])) {
        $row['galeria_fotos'] = array(
            array('url' => $row['foto_url'], 'caption' => 'Foto Principal del Navío')
        );
    }
    $tipos = ope_navegacion_barcos_tipos();
    $row['vel'] = isset($tipos[$row['tipo']]) ? (int)$tipos[$row['tipo']]['vel'] : 2;
    $row['tipo_label'] = isset($tipos[$row['tipo']]) ? $tipos[$row['tipo']]['label'] : $row['tipo'];
    return $row;
}

/**
 * Crea un barco para un personaje.
 */
function ope_barco_crear($pid, $uid, $nombre, $tipo = 'bote', $es_banda = 0)
{
    global $db;
    $pid    = (int)$pid;
    $uid    = (int)$uid;
    $nombre = trim((string)$nombre);
    $tipo   = (string)$tipo;
    $tipos  = ope_navegacion_barcos_tipos();

    if ($pid < 1) {
        return array('ok' => false, 'msg' => 'Personaje no valido.');
    }
    if ($nombre === '') {
        return array('ok' => false, 'msg' => 'El barco necesita un nombre.');
    }
    if (!isset($tipos[$tipo])) {
        return array('ok' => false, 'msg' => 'Tipo de barco no valido.');
    }
    if (!$db->table_exists('rol_barcos')) {
        return array('ok' => false, 'msg' => 'Sistema de barcos no instalado. Ejecuta migrate-navegacion.php.');
    }

    $db->insert_query('rol_barcos', array(
        'pid'          => $pid,
        'uid'          => $uid,
        'nombre'       => $db->escape_string($nombre),
        'tipo'         => $db->escape_string($tipo),
        'estadio'      => 'basico',
        'es_banda'     => (int)$es_banda,
        'mejoras_json' => '{}',
        'estado_casco' => 100,
        'notas'        => '',
        'dateline'     => defined('TIME_NOW') ? TIME_NOW : time(),
    ));
    $id = (int)$db->insert_id();
    return array('ok' => true, 'msg' => 'Barco creado.', 'barco_id' => $id);
}

/**
 * Crea el barco por defecto (bote basico) al crear un personaje.
 * Se llama desde el flujo de creacion de PJ.
 */
function ope_barco_crear_defecto($pid, $uid, $nombre_pj)
{
    $nombre_barco = 'Bote de ' . trim((string)$nombre_pj);
    return ope_barco_crear($pid, $uid, $nombre_barco, 'bote', 0);
}

/**
 * Actualiza el estado del casco de un barco.
 */
function ope_barco_actualizar_casco($barco_id, $nuevo_casco)
{
    global $db;
    $barco_id = (int)$barco_id;
    $nuevo_casco = max(0, min(100, (int)$nuevo_casco));
    if ($barco_id < 1 || !$db->table_exists('rol_barcos')) return;
    $db->update_query('rol_barcos', array('estado_casco' => $nuevo_casco), "barco_id = {$barco_id}");
}

/**
 * Lista las mejoras disponibles con su descripcion.
 */
function ope_barco_mejoras_catalogo()
{
    return array(
        'velamen_reforzado'  => array('nombre' => 'Velamen reforzado',       'efecto' => 'Velocidad +1, dias onrol -15%',       'estadio_min' => 'basico'),
        'casco_blindado'     => array('nombre' => 'Casco blindado',          'efecto' => 'Peligro -6, resistencia a tormentas',  'estadio_min' => 'basico'),
        'bodega_expandida'   => array('nombre' => 'Bodega expandida',        'efecto' => 'Capacidad de carga ampliada',          'estadio_min' => 'adaptado'),
        'canones'            => array('nombre' => 'Canones de costado',      'efecto' => 'Capacidad ofensiva naval',             'estadio_min' => 'adaptado'),
        'despensa_reforzada' => array('nombre' => 'Despensa reforzada',      'efecto' => '-1 tramo en viajes largos (>3)',        'estadio_min' => 'adaptado'),
        'taller'             => array('nombre' => 'Taller de a bordo',       'efecto' => 'Reparar +10% casco por tramo',         'estadio_min' => 'reforzado'),
        'cocina'             => array('nombre' => 'Cocina de a bordo',       'efecto' => 'Peligro -2 (moral tripulacion)',        'estadio_min' => 'reforzado'),
        'camuflaje'          => array('nombre' => 'Camuflaje / contrabando', 'efecto' => 'Encuentro -8 (menos hostilidad)',       'estadio_min' => 'avanzado'),
        'kairoseki'          => array('nombre' => 'Recubrimiento Kairoseki', 'efecto' => 'Permite cruzar Calm Belt',             'estadio_min' => 'avanzado'),
    );
}

/**
 * Comprueba si un personaje es el dueño único del barco.
 */
function ope_barco_es_dueno($barco_or_id, $pid)
{
    $barco = is_array($barco_or_id) ? $barco_or_id : ope_barco_obtener((int)$barco_or_id);
    if (!$barco || (int)$pid < 1) return false;
    return ((int)$barco['pid'] === (int)$pid);
}

/**
 * Marca un barco como el activo de un personaje y desactiva los demás.
 * (Regla OPE: Un personaje solo puede tener 1 barco activo).
 */
function ope_barco_set_activo($pid, $barco_id)
{
    global $db;
    $pid = (int)$pid;
    $barco_id = (int)$barco_id;
    if ($pid < 1 || $barco_id < 1 || !$db->table_exists('rol_barcos')) return false;

    // Desactivar todos los demás barcos del personaje
    $db->update_query('rol_barcos', array('activo' => 0), "pid = {$pid}");
    // Activar el barco seleccionado
    $db->update_query('rol_barcos', array('activo' => 1), "barco_id = {$barco_id} AND pid = {$pid}");
    return true;
}

/**
 * Actualiza nombre, foto_url, descripcion y galeria_fotos del barco (Gestión).
 */
function ope_barco_actualizar_datos($barco_id, $pid, $nombre, $foto_url = '', $descripcion = '', array $galeria_fotos = array())
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid = (int)$pid;
    $nombre = trim((string)$nombre);
    $foto_url = trim((string)$foto_url);
    $descripcion = trim((string)$descripcion);

    if (!ope_barco_es_dueno($barco_id, $pid)) {
        return array('ok' => false, 'msg' => 'No tienes permisos de gestión sobre esta embarcación.');
    }
    if ($nombre === '') {
        return array('ok' => false, 'msg' => 'El barco necesita un nombre.');
    }

    $upd = array(
        'nombre'      => $db->escape_string($nombre),
        'foto_url'    => $db->escape_string($foto_url),
        'descripcion' => $db->escape_string($descripcion),
    );

    if ($db->field_exists('fotos_json', 'rol_barcos')) {
        $upd['fotos_json'] = $db->escape_string(json_encode(array_values($galeria_fotos), JSON_UNESCAPED_UNICODE));
    }

    $db->update_query('rol_barcos', $upd, "barco_id = {$barco_id}");

    return array('ok' => true, 'msg' => 'Datos de la embarcación y galería de fotos actualizados.');
}

/**
 * Actualiza el nivel de despensa (0-100%).
 */
function ope_barco_actualizar_despensa($barco_id, $nivel_despensa)
{
    global $db;
    $barco_id = (int)$barco_id;
    $val = max(0, min(100, (int)$nivel_despensa));
    if ($barco_id < 1 || !$db->table_exists('rol_barcos')) return;
    $db->update_query('rol_barcos', array('despensa' => $val), "barco_id = {$barco_id}");
}

/**
 * Invita a un personaje a embarcar en la nave.
 */
function ope_barco_invitar($barco_id, $pid_dueno, $target_pj_or_id, $puesto = 'tripulante')
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid_dueno = (int)$pid_dueno;
    $puesto = trim((string)$puesto);
    if ($puesto === '') $puesto = 'tripulante';

    if (!ope_barco_es_dueno($barco_id, $pid_dueno)) {
        return array('ok' => false, 'msg' => 'Solo el dueño de la embarcación puede enviar invitaciones.');
    }

    $pid_invitado = 0;
    $target_nombre = '';

    if (is_numeric($target_pj_or_id) && (int)$target_pj_or_id > 0) {
        $pid_invitado = (int)$target_pj_or_id;
        $q = $db->simple_select('rol_personajes', 'pid, nombre', "pid = {$pid_invitado}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $target_pj = $db->fetch_array($q);
            $target_nombre = $target_pj['nombre'];
        }
    } else {
        $nombre_invitado = trim((string)$target_pj_or_id);
        $q = $db->simple_select('rol_personajes', 'pid, nombre', "LOWER(nombre) = " . $db->escape_string(strtolower($nombre_invitado)), array('limit' => 1));
        if ($db->num_rows($q)) {
            $target_pj = $db->fetch_array($q);
            $pid_invitado = (int)$target_pj['pid'];
            $target_nombre = $target_pj['nombre'];
        }
    }

    if ($pid_invitado < 1) {
        return array('ok' => false, 'msg' => 'No se encontró ningún personaje válido para invitar.');
    }

    if ($pid_invitado === $pid_dueno) {
        return array('ok' => false, 'msg' => 'No puedes invitarte a ti mismo.');
    }

    if (!$db->table_exists('rol_barco_invitaciones')) {
        return array('ok' => false, 'msg' => 'Sistema de invitaciones no instalado.');
    }

    // Verificar si ya existe invitación
    $chk = $db->simple_select('rol_barco_invitaciones', 'invitacion_id, estado', "barco_id = {$barco_id} AND pid_invitado = {$pid_invitado}", array('limit' => 1));
    if ($db->num_rows($chk)) {
        $exist = $db->fetch_array($chk);
        if ($exist['estado'] === 'aceptado') {
            return array('ok' => false, 'msg' => 'Ese personaje ya está embarcado en este barco.');
        } elseif ($exist['estado'] === 'pendiente') {
            return array('ok' => false, 'msg' => 'Ya existe una invitación pendiente para este personaje.');
        }
    }

    $now = defined('TIME_NOW') ? TIME_NOW : time();
    $db->insert_query('rol_barco_invitaciones', array(
        'barco_id'      => $barco_id,
        'pid_invitado'  => $pid_invitado,
        'pid_invitador' => $pid_dueno,
        'puesto'        => $db->escape_string($puesto),
        'estado'        => 'pendiente',
        'dateline'      => $now,
    ));

    return array('ok' => true, 'msg' => "Invitación enviada a {$target_pj['nombre']}.");
}

/**
 * Responde a una invitación de embarque (aceptar o rechazar).
 */
function ope_barco_responder_invitacion($invitacion_id, $pid_invitado, $aceptar = true)
{
    global $db;
    $invitacion_id = (int)$invitacion_id;
    $pid_invitado = (int)$pid_invitado;
    if ($invitacion_id < 1 || $pid_invitado < 1 || !$db->table_exists('rol_barco_invitaciones')) {
        return array('ok' => false, 'msg' => 'Invitación no válida.');
    }

    $q = $db->simple_select('rol_barco_invitaciones', '*', "invitacion_id = {$invitacion_id} AND pid_invitado = {$pid_invitado} AND estado = 'pendiente'", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'No tienes una invitación pendiente con ese ID.');
    }

    $nuevo_estado = $aceptar ? 'aceptado' : 'rechazado';
    $db->update_query('rol_barco_invitaciones', array('estado' => $nuevo_estado), "invitacion_id = {$invitacion_id}");

    $msg = $aceptar ? '¡Has aceptado la invitación y ahora estás embarcado!' : 'Has rechazado la invitación.';
    return array('ok' => true, 'msg' => $msg);
}

/**
 * Expulsa / desembarca a un miembro de la tripulación.
 */
function ope_barco_desembarcar($barco_id, $pid_dueno, $pid_expulsar)
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid_dueno = (int)$pid_dueno;
    $pid_expulsar = (int)$pid_expulsar;

    if (!ope_barco_es_dueno($barco_id, $pid_dueno)) {
        return array('ok' => false, 'msg' => 'Sin permisos para desembarcar tripulantes.');
    }

    if ($db->table_exists('rol_barco_invitaciones')) {
        $db->delete_query('rol_barco_invitaciones', "barco_id = {$barco_id} AND pid_invitado = {$pid_expulsar}");
    }
    return array('ok' => true, 'msg' => 'Tripulante desembarcado.');
}

/**
 * Obtiene las invitaciones pendientes para un personaje.
 */
function ope_barco_obtener_invitaciones_pendientes($pid)
{
    global $db;
    $pid = (int)$pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('rol_barco_invitaciones')) {
        return $out;
    }
    $q = $db->query("
        SELECT i.*, b.nombre AS barco_nombre, b.tipo AS barco_tipo, rp.nombre AS capitana_nombre
        FROM {$db->table_prefix}rol_barco_invitaciones i
        LEFT JOIN {$db->table_prefix}rol_barcos b ON (b.barco_id = i.barco_id)
        LEFT JOIN {$db->table_prefix}rol_personajes rp ON (rp.pid = i.pid_invitador)
        WHERE i.pid_invitado = {$pid} AND i.estado = 'pendiente'
        ORDER BY i.dateline DESC
    ");
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * Obtiene la lista de tripulantes embarcados aceptados en un barco.
 */
function ope_barco_obtener_tripulacion_embarcada($barco_id)
{
    global $db;
    $barco_id = (int)$barco_id;
    $out = array();
    if ($barco_id < 1 || !$db->table_exists('rol_barco_invitaciones')) {
        return $out;
    }
    $q = $db->query("
        SELECT i.*, rp.nombre AS pj_nombre, rp.avatar AS pj_avatar, rp.nivel AS pj_nivel
        FROM {$db->table_prefix}rol_barco_invitaciones i
        INNER JOIN {$db->table_prefix}rol_personajes rp ON (rp.pid = i.pid_invitado)
        WHERE i.barco_id = {$barco_id} AND i.estado = 'aceptado'
        ORDER BY i.dateline ASC
    ");
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    return $out;
}

/**
 * Deposita Berries en el Cofre de la Nave (Cualquier tripulante embarcado o dueño).
 */
function ope_barco_cofre_depositar_berries($barco_id, $pid, $monto)
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid = (int)$pid;
    $monto = max(1, (int)$monto);

    $barco = ope_barco_obtener($barco_id);
    if (!$barco) return array('ok' => false, 'msg' => 'Barco no encontrado.');

    // Verificar si el PJ tiene berries suficientes
    $q = $db->simple_select('rol_personajes', 'pid, nombre, berries', "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) return array('ok' => false, 'msg' => 'Personaje no encontrado.');
    $pj = $db->fetch_array($q);

    if ((int)$pj['berries'] < $monto) {
        return array('ok' => false, 'msg' => "No tienes suficientes Berries. Posees " . number_format((int)$pj['berries']) . " B.");
    }

    // Descontar al PJ
    $db->update_query('rol_personajes', array('berries' => (int)$pj['berries'] - $monto), "pid = {$pid}");

    // Sumar al cofre del barco
    $nuevos_berries = (int)($barco['berries_cofre'] ?? 0) + $monto;
    $db->update_query('rol_barcos', array('berries_cofre' => $nuevos_berries), "barco_id = {$barco_id}");

    // Registrar log
    if ($db->table_exists('rol_barco_cofre_logs')) {
        $db->insert_query('rol_barco_cofre_logs', array(
            'barco_id'      => $barco_id,
            'pid'           => $pid,
            'tipo'          => 'depositar_berries',
            'monto_berries' => $monto,
            'dateline'      => defined('TIME_NOW') ? TIME_NOW : time(),
        ));
    }

    return array('ok' => true, 'msg' => "¡Has depositado " . number_format($monto) . " Berries en el Cofre de la nave!");
}

/**
 * Retira Berries del Cofre de la Nave (Solo el Capitán / Dueño).
 */
function ope_barco_cofre_retirar_berries($barco_id, $pid_dueno, $monto)
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid_dueno = (int)$pid_dueno;
    $monto = max(1, (int)$monto);

    if (!ope_barco_es_dueno($barco_id, $pid_dueno)) {
        return array('ok' => false, 'msg' => 'Solo el Capitán puede retirar fondos del Cofre.');
    }

    $barco = ope_barco_obtener($barco_id);
    $berries_disponibles = (int)($barco['berries_cofre'] ?? 0);

    if ($berries_disponibles < $monto) {
        return array('ok' => false, 'msg' => "El Cofre no dispone de suficientes Berries (Saldo: " . number_format($berries_disponibles) . " B).");
    }

    // Descontar del cofre
    $db->update_query('rol_barcos', array('berries_cofre' => $berries_disponibles - $monto), "barco_id = {$barco_id}");

    // Sumar al Capitán
    $q = $db->simple_select('rol_personajes', 'pid, berries', "pid = {$pid_dueno}", array('limit' => 1));
    $cap = $db->fetch_array($q);
    $db->update_query('rol_personajes', array('berries' => (int)$cap['berries'] + $monto), "pid = {$pid_dueno}");

    // Log
    if ($db->table_exists('rol_barco_cofre_logs')) {
        $db->insert_query('rol_barco_cofre_logs', array(
            'barco_id'      => $barco_id,
            'pid'           => $pid_dueno,
            'tipo'          => 'retirar_berries',
            'monto_berries' => $monto,
            'dateline'      => defined('TIME_NOW') ? TIME_NOW : time(),
        ));
    }

    return array('ok' => true, 'msg' => "Has retirado " . number_format($monto) . " Berries del Cofre a tu bolsa personal.");
}

/**
 * Reparte Berries del Cofre directamente a un tripulante embarcado (Solo el Capitán / Dueño).
 */
function ope_barco_cofre_repartir_berries($barco_id, $pid_dueno, $target_pid, $monto)
{
    global $db;
    $barco_id = (int)$barco_id;
    $pid_dueno = (int)$pid_dueno;
    $target_pid = (int)$target_pid;
    $monto = max(1, (int)$monto);

    if (!ope_barco_es_dueno($barco_id, $pid_dueno)) {
        return array('ok' => false, 'msg' => 'Solo el Capitán puede repartir fondos del Cofre.');
    }

    $barco = ope_barco_obtener($barco_id);
    $berries_disponibles = (int)($barco['berries_cofre'] ?? 0);

    if ($berries_disponibles < $monto) {
        return array('ok' => false, 'msg' => "El Cofre no dispone de suficientes Berries para repartir.");
    }

    // Buscar tripulante objetivo
    $q = $db->simple_select('rol_personajes', 'pid, nombre, berries', "pid = {$target_pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Tripulante destino no encontrado.');
    }
    $target_pj = $db->fetch_array($q);

    // Descontar del cofre
    $db->update_query('rol_barcos', array('berries_cofre' => $berries_disponibles - $monto), "barco_id = {$barco_id}");

    // Sumar al tripulante
    $db->update_query('rol_personajes', array('berries' => (int)$target_pj['berries'] + $monto), "pid = {$target_pid}");

    // Log
    if ($db->table_exists('rol_barco_cofre_logs')) {
        $db->insert_query('rol_barco_cofre_logs', array(
            'barco_id'      => $barco_id,
            'pid'           => $pid_dueno,
            'tipo'          => 'repartir_berries',
            'monto_berries' => $monto,
            'target_pid'    => $target_pid,
            'dateline'      => defined('TIME_NOW') ? TIME_NOW : time(),
        ));
    }

    return array('ok' => true, 'msg' => "Has repartido " . number_format($monto) . " Berries del Cofre a {$target_pj['nombre']}.");
}

/**
 * Obtiene los logs de movimiento del Cofre.
 */
function ope_barco_cofre_obtener_logs($barco_id, $limit = 15)
{
    global $db;
    $barco_id = (int)$barco_id;
    $limit = (int)$limit;
    $out = array();
    if ($barco_id < 1 || !$db->table_exists('rol_barco_cofre_logs')) return $out;

    $q = $db->query("
        SELECT l.*, p1.nombre AS actor_nombre, p2.nombre AS target_nombre
        FROM {$db->table_prefix}rol_barco_cofre_logs l
        LEFT JOIN {$db->table_prefix}rol_personajes p1 ON (p1.pid = l.pid)
        LEFT JOIN {$db->table_prefix}rol_personajes p2 ON (p2.pid = l.target_pid)
        WHERE l.barco_id = {$barco_id}
        ORDER BY l.dateline DESC
        LIMIT {$limit}
    ");
    while ($row = $db->fetch_array($q)) {
        $out[] = $row;
    }
    return $out;
}
