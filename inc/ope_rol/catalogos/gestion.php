<?php
/**
 * One Piece: Eternal · Acceso a datos de los catálogos gestionables por staff.
 * -----------------------------------------------------------------
 * Fuente única para leer de BD lo que antes eran arrays mockup en los .php
 * públicos (tienda, tripulaciones, bibliotecas de akuma/bestiario/estilos) y
 * las bibliotecas de personajes/NPC (que reutilizan rol_personajes).
 *
 * Todas las funciones devuelven arrays PHP planos, listos para volcar a JSON
 * y pintar en el cliente. Requiere el $db global de MyBB.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

if (!function_exists('ope_rol_cat_tiendas')) {
    /** Secciones fijas del Bazar (metadatos de presentación + banner 4:5). */
    function ope_rol_cat_tiendas()
    {
        return array(
            'armeria'       => array(
                'nombre' => 'Armería',
                'tag'    => 'Armas y Armaduras',
                'lema'   => 'El filo de la justicia y el acero del Nuevo Mundo',
                'imagen' => 'images/foros/16.jpg',
            ),
            'astilleros'    => array(
                'nombre' => 'Astilleros',
                'tag'    => 'Barcos y Piezas',
                'lema'   => 'Donde hasta el barco más humilde se convierte en leyenda',
                'imagen' => 'images/foros/9.jpg',
            ),
            'general'       => array(
                'nombre' => 'General',
                'tag'    => 'Consumibles y mejoras',
                'lema'   => 'Si existe, lo tenemos. Si no, pregúntale al vendedor',
                'imagen' => 'images/foros/10.jpg',
            ),
            'mercado_negro' => array(
                'nombre' => 'Mercado Negro',
                'tag'    => 'Lo prohibido tiene precio',
                'lema'   => 'No preguntes de dónde viene. No preguntes a quién se lo vendimos.',
                'imagen' => 'images/foros/23.jpg',
            ),
        );
    }

    /** Etiquetas de categorías de producto. */
    function ope_rol_cat_categoria_labels()
    {
        return array(
            'armas' => 'Armas', 'armaduras' => 'Armaduras', 'barcos' => 'Barcos',
            'piezas' => 'Piezas', 'consumibles' => 'Consumibles',
            'mejoras' => 'Mejoras', 'especiales' => 'Especiales',
        );
    }
}

if (!function_exists('ope_rol_cat_tienda_items')) {
    /** Productos de tienda. $solo_activos filtra activo=1. */
    function ope_rol_cat_tienda_items($solo_activos = true)
    {
        global $db;
        $out = array();
        // D6.3: el catálogo de productos canónico es mybb_ope_objetos (F3);
        // rol_tienda_items está retirada.
        if (!$db->table_exists('ope_objetos')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('ope_objetos', '*', $where, array('order_by' => 'nombre', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $r['detalles_arr'] = array();
            $r['detalles'] = (string) ($r['efecto_json'] ?? '');
            $r['precio'] = (int) ($r['precio_base'] ?? 0);
            $r['categoria'] = (string) ($r['categoria'] ?? '');
            $r['rareza'] = (string) ($r['rareza'] ?? '');
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_tripulaciones')) {
    function ope_rol_cat_tripulaciones($solo_activos = true)
    {
        global $db;
        $out = array();
        // D6.3: fuente canónica mybb_ope_tripulaciones (F5.3).
        if (!$db->table_exists('ope_tripulaciones')) {
            return $out;
        }
        $where = $solo_activos ? "estado = 'activa'" : '';
        $q = $db->simple_select('ope_tripulaciones', '*', $where, array('order_by' => 'nombre', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $r['lema'] = (string) ($r['proposito'] ?? '');
            $r['imagen'] = (string) ($r['bandera'] ?? '');
            $r['activo'] = ((string) ($r['estado'] ?? '') === 'activa') ? 1 : 0;
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_akuma')) {
    function ope_rol_cat_akuma($solo_activos = true)
    {
        global $db;
        $out = array();
        // F6.4: fuente canónica mybb_ope_akumas (7 Seas, F5.1). El catálogo
        // viejo rol_akuma se retiró; el espejo mantiene el esquema histórico
        // si la corrida aún no creó ope_akumas (entorno en transición).
        $tabla = 'ope_akumas';
        if (!$db->table_exists($tabla)) {
            if ($db->table_exists('ope_akuma')) {
                $tabla = 'ope_akuma';
            } else {
                return $out;
            }
        }
        $where = $solo_activos ? "estado IN ('libre','ocupada')" : '';
        $q = $db->simple_select($tabla, '*', $where, array('order_by' => 'tier, nombre_propio', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            // Mapeo a la forma histórica (lo consume ope_fruta_norm/biblioteca).
            $out[] = array(
                'id'                => (int) $r['id'],
                'nombre'            => (string) ($r['nombre_propio'] ?? $r['nombre'] ?? ''),
                'tipo'              => (string) ($r['familia'] ?? $r['tipo'] ?? ''),
                'rareza'            => (string) ($r['rareza'] ?? ''),
                'tier'              => (int) ($r['tier'] ?? 0),
                'secundario'        => (string) ($r['tier_roman'] ?? ''),
                'descripcion'       => (string) ($r['aspecto'] ?? ''),
                'descripcion_breve' => (string) ($r['aspecto'] ?? ''),
                'efecto_general'    => (string) ($r['mecanica_base'] ?? ''),
                'debilidad'         => (string) ($r['debilidades'] ?? ''),
                'despertar'         => (string) ($r['despertar'] ?? ''),
                'potencia_formula'  => '',
                'caps_json'         => '',
                'usuario'           => '',
                'ocupada_pid'       => (int) ($r['portador_id'] ?? 0),
                'imagen'            => '',
                'origen'            => (string) ($r['origen'] ?? ''),
            );
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_bestiario')) {
    function ope_rol_cat_bestiario($solo_activos = true)
    {
        global $db;
        $out = array();
        // D6.3: fuente canónica mybb_ope_bestiario (F6.2).
        if (!$db->table_exists('ope_bestiario')) {
            return $out;
        }
        $where = '';
        $q = $db->simple_select('ope_bestiario', '*', $where, array('order_by' => 'nombre', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $r['activo'] = 1;
            $r['orden'] = 0;
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_estilos')) {
    function ope_rol_cat_estilos($solo_activos = true)
    {
        global $db;
        $out = array();
        if (!$db->table_exists('ope_estilos')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('ope_estilos', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_personajes_publicos')) {
    /**
     * Biblioteca de personajes jugadores: reutiliza rol_personajes.
     * Solo PJs aprobados y no NPC. Resuelve facción, raza y concepto del JSON
     * `datos`. No inventa nada: si un campo no existe queda vacío.
     */
    function ope_rol_cat_personajes_publicos()
    {
        return ope_rol_cat_fichas_query('es_npc = 0 AND estado = \'aprobado\'');
    }

    /** Biblioteca de NPCs: rol_personajes con es_npc=1 (auto-aprobados). */
    function ope_rol_cat_npcs_publicos()
    {
        return ope_rol_cat_fichas_query('es_npc = 1');
    }

    /** Consulta común de fichas para las bibliotecas de personajes/NPC. */
    function ope_rol_cat_fichas_query($where)
    {
        global $db;
        $out = array();
        // F6.3: fuente canónica mybb_ope_personajes; el legado rol_personajes
        // solo se usa si el esquema nuevo no existe todavía (transición).
        $canonico = function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes') && $db->table_exists('personajes');
        if (!$canonico && !$db->table_exists('rol_personajes')) {
            return $out;
        }
        $razas = function_exists('ope_rol_razas') ? ope_rol_razas() : array();
        if ($canonico) {
            // ope_personajes: raza_id → razas.nombre; facción/rango por id.
            $q = $db->query('SELECT p.id AS pid, p.nombre, p.slug, \'\' AS rango, p.nivel, '
                . 'p.avatar, p.icono, \'\' AS rango_faccion, p.personalidad, p.datos, p.es_NPC, '
                . 'r.nombre AS raza_nombre, f.nombre AS faccion_nombre, fa.nombre AS rango_faccion_nombre '
                . 'FROM ' . ope7_tabla_full('personajes') . ' p '
                . 'LEFT JOIN ' . ope7_tabla_full('razas') . ' r ON r.id = p.raza_id '
                . 'LEFT JOIN ' . ope7_tabla_full('facciones') . ' f ON f.id = p.faccion_id '
                . 'LEFT JOIN ' . ope7_tabla_full('rangos_faccion') . ' fa ON fa.id = p.rango_id '
                . 'WHERE ' . ($where === 'es_npc = 1' ? 'p.es_NPC = 1' : "p.es_NPC = 0 AND p.estado = 'aprobado'") . ' ORDER BY p.nombre ASC');
            while ($r = $db->fetch_array($q)) {
                $datos = json_decode((string) ($r['datos'] ?? ''), true) ?: array();
                $out[] = array(
                    'pid'          => (int) $r['pid'],
                    'nombre'       => (string) $r['nombre'],
                    'slug'         => (string) $r['slug'],
                    'rango'        => (string) ($r['rango'] ?? ''),
                    'nivel'        => (int) $r['nivel'],
                    'imagen'       => trim((string) ($r['icono'] ?: $r['avatar'])),
                    'faccion'      => (string) ($r['faccion_nombre'] ?? ''),
                    'faccion_slug' => function_exists('ope_rol_faccion_slug') ? ope_rol_faccion_slug((string) ($r['faccion_nombre'] ?? '')) : strtolower((string) ($r['faccion_nombre'] ?? '')),
                    'rango_faccion' => (string) ($r['rango_faccion_nombre'] ?? ''),
                    'raza'         => (string) ($r['raza_nombre'] ?? ''),
                    'concepto'     => (string) ($datos['concepto'] ?? ''),
                    'apodo'        => (string) ($datos['apodo'] ?? ''),
                    'edad'         => (string) ($datos['edad'] ?? ''),
                    'genero'       => (string) ($datos['genero'] ?? ''),
                    'personalidad' => (string) ($r['personalidad'] ?? ''),
                    'apariencia'   => '',
                );
            }
            return $out;
        }
        // D6.3: rol_personajes está retirada — solo existe la rama canónica.
        return $out;
    }
}

if (!function_exists('ope_rol_cat_json_list')) {
    /** Normaliza un campo JSON a lista de strings (para 'detalles'). */
    function ope_rol_cat_json_list($raw)
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw), function ($s) { return trim($s) !== ''; }));
        }
        $dec = json_decode((string) $raw, true);
        if (!is_array($dec)) {
            return array();
        }
        return array_values(array_filter(array_map('strval', $dec), function ($s) { return trim($s) !== ''; }));
    }
}

if (!function_exists('ope_rol_cat_rareza_tier')) {
    /** Mapea una rareza textual a la clase de tier de color (t2..t5). */
    function ope_rol_cat_rareza_tier($rareza)
    {
        $map = array(
            'Legendario' => 't5', 'Legendaria' => 't5',
            'Épico' => 't4', 'Epico' => 't4', 'Alta' => 't4',
            'Raro' => 't3', 'Rara' => 't3', 'Media' => 't3',
            'Común' => 't2', 'Comun' => 't2', 'Baja' => 't2',
        );
        return $map[trim((string) $rareza)] ?? 't2';
    }
}

if (!function_exists('ope_rol_mv_mision_asignacion')) {
    /** Devuelve la asignación (quién cogió) de una misión, o null. */
    function ope_rol_mv_mision_asignacion($mid)
    {
        global $db;
        $mid = (int) $mid;
        if ($mid < 1 || !$db->table_exists('ope_mv_mision_asignaciones')) {
            return null;
        }
        $q = $db->simple_select('ope_mv_mision_asignaciones', '*', "mision_id = {$mid}", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return null;
        }
        $row = $db->fetch_array($q);
        $row['companeros_arr'] = ope_rol_cat_json_list($row['companeros'] ?? '');
        return $row;
    }

    /** Mapa mision_id => asignación, para pintar listados sin N consultas. */
    function ope_rol_mv_asignaciones_map()
    {
        global $db;
        $out = array();
        if (!$db->table_exists('ope_mv_mision_asignaciones')) {
            return $out;
        }
        $q = $db->simple_select('ope_mv_mision_asignaciones', '*');
        while ($r = $db->fetch_array($q)) {
            $r['companeros_arr'] = ope_rol_cat_json_list($r['companeros'] ?? '');
            $out[(int) $r['mision_id']] = $r;
        }
        return $out;
    }

    /** Nombre de un personaje por pid (con caché estática). */
    function ope_rol_cat_nombre_pid($pid)
    {
        global $db;
        static $cache = array();
        $pid = (int) $pid;
        if ($pid < 1) {
            return '';
        }
        if (isset($cache[$pid])) {
            return $cache[$pid];
        }
        $nombre = '';
        if (ope7_tabla_existe('personajes')) {
            $q = $db->simple_select(ope7_tabla('personajes'), 'nombre', "id = {$pid}", array('limit' => 1));
            if ($db->num_rows($q)) {
                $nombre = (string) $db->fetch_field($q, 'nombre');
            }
        }
        $cache[$pid] = $nombre;
        return $nombre;
    }
}

if (!function_exists('ope_rol_pid_activo')) {
    /** pid del personaje activo de la cuenta, o 0. */
    function ope_rol_pid_activo($uid)
    {
        global $db, $mybb;
        $uid = (int) $uid;
        if ($uid < 1) {
            return 0;
        }
        if (isset($mybb->user['ope_active_pid']) && (int) $mybb->user['uid'] === $uid) {
            $ap = (int) $mybb->user['ope_active_pid'];
            if ($ap > 0) {
                return $ap;
            }
        }
        // D6.3: fuente canónica mybb_ope_cuentas.
        if (!$db->table_exists('ope_cuentas')) {
            return 0;
        }
        $q = $db->simple_select('ope_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        return $db->num_rows($q) ? (int) $db->fetch_field($q, 'personaje_activo') : 0;
    }
}

if (!function_exists('ope_rol_cat_tripulacion_miembros')) {
    /**
     * Tripulantes compañeros del personaje (otros miembros aprobados en su misma tripulación).
     * Devuelve array con claves: pid, nombre, avatar, nivel, isla_actual.
     */
    function ope_rol_cat_tripulacion_miembros($pid)
    {
        global $db;
        $pid = (int) $pid;
        $out = array();
        // D6.3: fuente canónica ope_tripulantes → ope_personajes.
        if ($pid < 1 || !$db->table_exists('ope_tripulantes') || !$db->table_exists('ope_personajes')) {
            return $out;
        }
        $q = $db->simple_select('ope_tripulantes', 'tripulacion_id', "personaje_id = {$pid} AND estado = 'activo'", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return $out;
        }
        $tid = (int) $db->fetch_field($q, 'tripulacion_id');
        if ($tid < 1) {
            return $out;
        }
        $pref = TABLE_PREFIX;
        $q2 = $db->query("
            SELECT rp.id AS pid, rp.nombre, rp.avatar, rp.nivel, '' AS isla_actual
            FROM {$pref}ope_tripulantes tm
            INNER JOIN {$pref}ope_personajes rp ON (rp.id = tm.personaje_id)
            WHERE tm.tripulacion_id = {$tid}
              AND tm.personaje_id != {$pid}
              AND tm.estado = 'activo'
              AND rp.estado = 'aprobado'
            ORDER BY rp.nivel DESC, rp.nombre ASC
        ");
        while ($row = $db->fetch_array($q2)) {
            $out[] = array(
                'pid'         => (int) $row['pid'],
                'nombre'      => (string) $row['nombre'],
                'avatar'      => (string) $row['avatar'],
                'nivel'       => (int) $row['nivel'],
                'isla_actual' => (string) $row['isla_actual'],
            );
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_tripulacion_miembro')) {
    /** Membresía activa de un personaje, o null. */
    function ope_rol_cat_tripulacion_miembro($pid)
    {
        global $db;
        $pid = (int) $pid;
        if ($pid < 1 || !$db->table_exists('ope_tripulantes')) {
            return null;
        }
        $q = $db->simple_select('ope_tripulantes', '*', "personaje_id = {$pid} AND estado = 'activo'", array('limit' => 1));
        return $db->num_rows($q) ? $db->fetch_array($q) : null;
    }

    /** Tripulación + rol del personaje (vista “mi tripulación”), o null. */
    function ope_rol_cat_tripulacion_de_personaje($pid)
    {
        global $db;
        $m = ope_rol_cat_tripulacion_miembro($pid);
        if (!$m || !$db->table_exists('ope_tripulaciones')) {
            return null;
        }
        $tid = (int) $m['tripulacion_id'];
        $q = $db->simple_select('ope_tripulaciones', '*', "id = {$tid} AND estado = 'activa'", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return null;
        }
        $trip = $db->fetch_array($q);
        $trip['lema'] = (string) ($trip['proposito'] ?? '');
        $trip['imagen'] = (string) ($trip['bandera'] ?? '');
        return array(
            'miembro'     => $m,
            'tripulacion' => $trip,
            'rol'         => (string) $m['rol'],
        );
    }

    /** Recalcula el contador de miembros activos en rol_tripulaciones. */
    function ope_rol_cat_tripulacion_sync_conteo($tripulacion_id)
    {
        global $db;
        $tripulacion_id = (int) $tripulacion_id;
        if ($tripulacion_id < 1 || !$db->table_exists('rol_tripulacion_miembros') || !$db->table_exists('rol_tripulaciones')) {
            return 0;
        }
        $c = (int) $db->fetch_field(
            $db->simple_select('rol_tripulacion_miembros', 'COUNT(*) c', "tripulacion_id = {$tripulacion_id} AND estado = 'activo'"),
            'c'
        );
        $db->update_query('rol_tripulaciones', array('miembros' => $c), "id = {$tripulacion_id}");
        return $c;
    }

    /** ¿Tiene un trámite de tripulación pendiente este personaje? */
    function ope_rol_cat_tripulacion_tramite_pendiente($pid)
    {
        global $db;
        $pid = (int) $pid;
        if ($pid < 1 || !$db->table_exists('rol_tramites')) {
            return null;
        }
        $q = $db->simple_select(
            'rol_tramites',
            '*',
            "pid = {$pid} AND estado = 'pendiente' AND tipo IN ('fundar_tripulacion','unirse_tripulacion')",
            array('order_by' => 'tid', 'order_dir' => 'DESC', 'limit' => 1)
        );
        return $db->num_rows($q) ? $db->fetch_array($q) : null;
    }

    /**
     * Aprueba un trámite de tripulación (fundar o unirse).
     * Devuelve array('ok'=>bool, 'msg'=>string).
     */
    function ope_rol_cat_tripulacion_aprobar_tramite($tid)
    {
        global $db;
        $tid = (int) $tid;
        if ($tid < 1 || !$db->table_exists('rol_tramites')) {
            return array('ok' => false, 'msg' => 'Trámite no encontrado.');
        }
        $q = $db->simple_select('rol_tramites', '*', "tid = {$tid}", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return array('ok' => false, 'msg' => 'Trámite no encontrado.');
        }
        $t = $db->fetch_array($q);
        if ($t['estado'] !== 'pendiente') {
            return array('ok' => false, 'msg' => 'Ese trámite ya fue gestionado.');
        }
        $pid = (int) $t['pid'];
        $uid = (int) $t['uid'];
        if ($pid < 1) {
            return array('ok' => false, 'msg' => 'Trámite sin personaje asociado.');
        }
        if (ope_rol_cat_tripulacion_miembro($pid)) {
            return array('ok' => false, 'msg' => 'Ese personaje ya pertenece a una tripulación.');
        }
        $datos = json_decode((string) ($t['datos'] ?? ''), true);
        if (!is_array($datos)) {
            $datos = array();
        }
        $now = (int) TIME_NOW;
        $nombre_pj = ope_rol_cat_nombre_pid($pid);

        if ($t['tipo'] === 'fundar_tripulacion') {
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            if ($nombre === '') {
                return array('ok' => false, 'msg' => 'Falta el nombre de la tripulación.');
            }
            if (!$db->table_exists('rol_tripulaciones')) {
                return array('ok' => false, 'msg' => 'Tabla de tripulaciones no disponible.');
            }
            $db->insert_query('rol_tripulaciones', array(
                'nombre'      => $db->escape_string($nombre),
                'faccion'     => $db->escape_string((string) ($datos['faccion'] ?? 'pirata')),
                'capitan'     => $db->escape_string($nombre_pj !== '' ? $nombre_pj : '—'),
                'lema'        => $db->escape_string((string) ($datos['lema'] ?? '')),
                'descripcion' => $db->escape_string((string) ($datos['descripcion'] ?? '')),
                'nivel'       => 1,
                'miembros'    => 1,
                'imagen'      => $db->escape_string((string) ($datos['imagen'] ?? '')),
                'activo'      => 1,
                'orden'       => 0,
                'dateline'    => $now,
            ));
            $trip_id = (int) $db->insert_id();
            if ($trip_id < 1) {
                return array('ok' => false, 'msg' => 'No se pudo crear la tripulación.');
            }
            $db->insert_query('rol_tripulacion_miembros', array(
                'tripulacion_id' => $trip_id,
                'pid'            => $pid,
                'uid'            => $uid,
                'rol'            => 'capitan',
                'estado'         => 'activo',
                'dateline'       => $now,
            ));
        } elseif ($t['tipo'] === 'unirse_tripulacion') {
            $trip_id = (int) ($datos['tripulacion_id'] ?? 0);
            if ($trip_id < 1) {
                return array('ok' => false, 'msg' => 'Tripulación destino no válida.');
            }
            $tq = $db->simple_select('rol_tripulaciones', 'id', "id = {$trip_id} AND activo = 1", array('limit' => 1));
            if (!$db->num_rows($tq)) {
                return array('ok' => false, 'msg' => 'La tripulación ya no existe o está oculta.');
            }
            $db->insert_query('rol_tripulacion_miembros', array(
                'tripulacion_id' => $trip_id,
                'pid'            => $pid,
                'uid'            => $uid,
                'rol'            => 'tripulante',
                'estado'         => 'activo',
                'dateline'       => $now,
            ));
            ope_rol_cat_tripulacion_sync_conteo($trip_id);
        } else {
            return array('ok' => false, 'msg' => 'Tipo de trámite no soportado.');
        }

        $db->update_query('rol_tramites', array('estado' => 'aprobado', 'lastedit' => $now), "tid = {$tid}");
        return array('ok' => true, 'msg' => 'Trámite aprobado.');
    }

if (!function_exists('ope_rol_cat_lore')) {
    /** Biblioteca de Lore: devuelve todos los artículos activos de ope_lore. */
    function ope_rol_cat_lore($solo_activos = true)
    {
        global $db;
        $out = array();
        if (!$db->table_exists('ope_lore')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('ope_lore', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $out[] = $r;
        }
        return $out;
    }

    /** Etiquetas legibles para categorías de lore. */
    function ope_rol_cat_lore_categoria_labels()
    {
        return array(
            'historia'   => 'Historia',
            'eras'        => 'Eras',
            'personajes'  => 'Personajes',
            'facciones'   => 'Facciones',
            'ubicaciones' => 'Ubicaciones',
            'sistemas'    => 'Sistemas',
            'cronologia'  => 'Cronología',
        );
    }

    /** Slug de categoría de lore a color de acento (CSS var). */
    function ope_rol_cat_lore_categoria_color($cat)
    {
        $map = array(
            'historia'   => 'var(--ember-hi)',
            'eras'        => 'var(--fac-revolucionario-hi)',
            'personajes'  => 'var(--ember)',
            'facciones'   => 'var(--fac-marine)',
            'ubicaciones' => 'var(--fac-cazarrecompensas-hi)',
            'sistemas'    => 'var(--fac-pirata)',
            'cronologia'  => 'var(--fac-revolucionario)',
        );
        return $map[$cat] ?? 'var(--ember)';
    }
}

    /** Rechaza un trámite de tripulación. */
    function ope_rol_cat_tripulacion_rechazar_tramite($tid)
    {
        global $db;
        $tid = (int) $tid;
        if ($tid < 1 || !$db->table_exists('rol_tramites')) {
            return array('ok' => false, 'msg' => 'Trámite no encontrado.');
        }
        $db->update_query('rol_tramites', array(
            'estado'   => 'rechazado',
            'lastedit' => (int) TIME_NOW,
        ), "tid = {$tid} AND estado = 'pendiente'");
        return array('ok' => true, 'msg' => 'Trámite rechazado.');
    }
}

if (!function_exists('ope_rol_cat_slugify')) {
    function ope_rol_cat_slugify($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return 'sin-titulo';
        }
        $text = strtr($text, array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
        ));
        $text = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $text);
        $text = preg_replace('/[\s\-]+/', '-', $text);
        $text = trim($text, '-');
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        return $text === '' ? 'sin-titulo' : $text;
    }
}

if (!function_exists('ope_rol_cat_cards_setup')) {
    /** Crea las tablas rol_cards y rol_pj_cards si no existen. */
    function ope_rol_cat_cards_setup()
    {
        global $db;
        $pref = TABLE_PREFIX;

        if (!$db->table_exists('rol_cards')) {
            $db->write_query("CREATE TABLE {$pref}rol_cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(200) NOT NULL,
                slug VARCHAR(200) NOT NULL,
                tipo VARCHAR(50) NOT NULL DEFAULT 'tecnica',
                descripcion TEXT,
                contenido TEXT,
                icono VARCHAR(255) DEFAULT '',
                estadisticas TEXT,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                orden INT NOT NULL DEFAULT 0,
                dateline INT NOT NULL DEFAULT 0,
                lastedit INT NOT NULL DEFAULT 0,
                UNIQUE KEY slug_idx (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        if (!$db->table_exists('rol_pj_cards')) {
            $db->write_query("CREATE TABLE {$pref}rol_pj_cards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pid INT NOT NULL,
                card_id INT NOT NULL,
                slot VARCHAR(50) NOT NULL DEFAULT 'misc',
                datos TEXT,
                orden INT NOT NULL DEFAULT 0,
                dateline INT NOT NULL DEFAULT 0,
                KEY pid_idx (pid),
                KEY card_idx (card_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }
}

if (!function_exists('ope_rol_cat_card_tipos')) {
    /** Etiquetas para tipos de card. */
    function ope_rol_cat_card_tipos()
    {
        return array(
            'tecnica' => 'Técnica custom',
            'haki'    => 'Haki',
            'fruta'   => 'Akuma no Mi',
            'arma'    => 'Técnica de arma',
            'eternal' => 'Activo Eternal',
            'item'    => 'Objeto / Ítem',
            'lore'    => 'Lore / Conocimiento',
            'npc'     => 'PNJ / Aliado',
            'misc'    => 'Otro / Miscelánea',
        );
    }
}

if (!function_exists('ope_rol_cat_pj_card_slots')) {
    /** Etiquetas para slots de asignación a personaje. */
    function ope_rol_cat_pj_card_slots()
    {
        return array(
            'descripcion' => 'Descripción',
            'inventario'  => 'Inventario',
            'tecnicas'    => 'Técnicas',
            'poderes'     => 'Poderes',
            'historia'    => 'Historia / Trasfondo',
            'relaciones'  => 'Relaciones',
            'misc'        => 'General / Otro',
        );
    }
}

if (!function_exists('ope_rol_cat_cards')) {
    /** Lista todas las cards del catálogo. */
    function ope_rol_cat_cards($solo_activos = true)
    {
        global $db;
        $out = array();
        if (!$db->table_exists('rol_cards')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_cards', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $r['estadisticas_arr'] = ope_rol_cat_json_list($r['estadisticas'] ?? '');
            $out[] = $r;
        }
        return $out;
    }

    /** Devuelve una card por ID. */
    function ope_rol_cat_card_por_id($id)
    {
        global $db;
        $id = (int) $id;
        if ($id < 1 || !$db->table_exists('rol_cards')) {
            return null;
        }
        $q = $db->simple_select('rol_cards', '*', "id = {$id}", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return null;
        }
        $r = $db->fetch_array($q);
        $r['estadisticas_arr'] = ope_rol_cat_json_list($r['estadisticas'] ?? '');
        return $r;
    }

    /** Crea una nueva card. Devuelve array('ok'=>bool, 'msg'=>string, 'id'=>int). */
    function ope_rol_cat_card_crear(array $data)
    {
        global $db;
        $now = (int) TIME_NOW;

        if (empty($data['nombre'])) {
            return array('ok' => false, 'msg' => 'El nombre es obligatorio.', 'id' => 0);
        }

        // Slug auto-generado
        $slug = !empty($data['slug']) ? ope_rol_cat_slugify($data['slug']) : ope_rol_cat_slugify($data['nombre']);

        // Evitar slugs duplicados
        $base_slug = $slug;
        $counter = 1;
        while ($db->table_exists('rol_cards') && $db->fetch_field($db->simple_select('rol_cards', 'COUNT(*) AS c', "slug = '{$db->escape_string($slug)}'"), 'c') > 0) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }

        $id = $db->insert_query('rol_cards', array(
            'nombre'       => $db->escape_string($data['nombre']),
            'slug'         => $db->escape_string($slug),
            'tipo'         => $db->escape_string($data['tipo'] ?? 'tecnica'),
            'descripcion'  => $db->escape_string($data['descripcion'] ?? ''),
            'contenido'    => $db->escape_string($data['contenido'] ?? ''),
            'icono'        => $db->escape_string($data['icono'] ?? ''),
            'estadisticas' => $db->escape_string($data['estadisticas'] ?? ''),
            'activo'       => isset($data['activo']) ? (int) $data['activo'] : 1,
            'orden'        => (int) ($data['orden'] ?? 0),
            'dateline'     => $now,
            'lastedit'     => $now,
        ));

        return array('ok' => true, 'msg' => "Card \"{$data['nombre']}\" creada.", 'id' => (int) $id);
    }

    /** Edita una card existente. Devuelve array('ok'=>bool, 'msg'=>string). */
    function ope_rol_cat_card_editar($id, array $data)
    {
        global $db;
        $id = (int) $id;
        if ($id < 1 || !$db->table_exists('rol_cards')) {
            return array('ok' => false, 'msg' => 'Card no encontrada.');
        }

        $q = $db->simple_select('rol_cards', 'id', "id = {$id}", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return array('ok' => false, 'msg' => 'Card no encontrada.');
        }

        $update = array('lastedit' => (int) TIME_NOW);

        if (isset($data['nombre'])) {
            $update['nombre'] = $db->escape_string($data['nombre']);
        }
        if (isset($data['tipo'])) {
            $update['tipo'] = $db->escape_string($data['tipo']);
        }
        if (isset($data['descripcion'])) {
            $update['descripcion'] = $db->escape_string($data['descripcion']);
        }
        if (isset($data['contenido'])) {
            $update['contenido'] = $db->escape_string($data['contenido']);
        }
        if (isset($data['icono'])) {
            $update['icono'] = $db->escape_string($data['icono']);
        }
        if (isset($data['estadisticas'])) {
            $update['estadisticas'] = $db->escape_string($data['estadisticas']);
        }
        if (isset($data['activo'])) {
            $update['activo'] = (int) $data['activo'];
        }
        if (isset($data['orden'])) {
            $update['orden'] = (int) $data['orden'];
        }
        // Slug regenerado si cambia nombre
        if (isset($data['nombre'])) {
            $slug = ope_rol_cat_slugify($data['nombre']);
            $base_slug = $slug;
            $counter = 1;
            while ($db->fetch_field($db->simple_select('rol_cards', 'COUNT(*) AS c', "slug = '{$db->escape_string($slug)}' AND id != {$id}"), 'c') > 0) {
                $slug = $base_slug . '-' . $counter;
                $counter++;
            }
            $update['slug'] = $db->escape_string($slug);
        }

        $db->update_query('rol_cards', $update, "id = {$id}");
        return array('ok' => true, 'msg' => 'Card actualizada.');
    }

    /** Borrado lógico (activo = 0) o físico si se fuerza. Devuelve array('ok'=>bool, 'msg'=>string). */
    function ope_rol_cat_card_borrar($id, $force = false)
    {
        global $db;
        $id = (int) $id;
        if ($id < 1 || !$db->table_exists('rol_cards')) {
            return array('ok' => false, 'msg' => 'Card no encontrada.');
        }

        if ($force) {
            // Quitar asignaciones primero
            if ($db->table_exists('rol_pj_cards')) {
                $db->delete_query('rol_pj_cards', "card_id = {$id}");
            }
            $db->delete_query('rol_cards', "id = {$id}");
            return array('ok' => true, 'msg' => 'Card eliminada permanentemente.');
        }

        $db->update_query('rol_cards', array('activo' => 0, 'lastedit' => (int) TIME_NOW), "id = {$id}");
        return array('ok' => true, 'msg' => 'Card desactivada.');
    }
}

if (!function_exists('ope_rol_cat_pj_cards')) {
    /** Cards asignadas a un personaje. */
    function ope_rol_cat_pj_cards($pid)
    {
        global $db;
        $pid = (int) $pid;
        $out = array();
        if ($pid < 1 || !$db->table_exists('rol_pj_cards') || !$db->table_exists('rol_cards')) {
            return $out;
        }

        $pref = TABLE_PREFIX;
        $q = $db->query("SELECT pc.*, c.nombre AS card_nombre, c.tipo AS card_tipo, c.slug AS card_slug, c.icono AS card_icono, c.descripcion AS card_desc, c.contenido AS card_contenido
            FROM {$pref}rol_pj_cards pc
            JOIN {$pref}rol_cards c ON c.id = pc.card_id AND c.activo = 1
            WHERE pc.pid = {$pid}
            ORDER BY pc.orden, pc.id ASC");

        while ($r = $db->fetch_array($q)) {
            $out[] = $r;
        }
        return $out;
    }

    /** Asigna una card a un personaje. Devuelve array('ok'=>bool, 'msg'=>string, 'id'=>int). */
    function ope_rol_cat_pj_card_asignar($pid, $card_id, $slot = 'misc', $datos = '')
    {
        global $db;
        $pid = (int) $pid;
        $card_id = (int) $card_id;
        $slot = $db->escape_string(trim((string) $slot) !== '' ? $slot : 'misc');

        if ($pid < 1 || $card_id < 1) {
            return array('ok' => false, 'msg' => 'Personaje o card inválidos.', 'id' => 0);
        }

        if (!$db->table_exists('rol_pj_cards') || !$db->table_exists('rol_cards')) {
            return array('ok' => false, 'msg' => 'Tablas no disponibles.', 'id' => 0);
        }

        // Verificar que la card existe
        $cq = $db->simple_select('rol_cards', 'id, nombre', "id = {$card_id} AND activo = 1", array('limit' => 1));
        if (!$db->num_rows($cq)) {
            return array('ok' => false, 'msg' => 'Card no encontrada o inactiva.', 'id' => 0);
        }
        $card_nombre = $db->fetch_field($cq, 'nombre');

        // Verificar duplicado
        $dup = $db->simple_select('rol_pj_cards', 'COUNT(*) AS c', "pid = {$pid} AND card_id = {$card_id}", array('limit' => 1));
        if ($db->num_rows($dup) && (int) $db->fetch_field($dup, 'c') > 0) {
            return array('ok' => false, 'msg' => 'Esa card ya está asignada a este personaje.', 'id' => 0);
        }

        $id = $db->insert_query('rol_pj_cards', array(
            'pid'       => $pid,
            'card_id'   => $card_id,
            'slot'      => $slot,
            'datos'     => $db->escape_string((string) $datos),
            'orden'     => 0,
            'dateline'  => (int) TIME_NOW,
        ));

        return array('ok' => true, 'msg' => "Card \"{$card_nombre}\" asignada al personaje.", 'id' => (int) $id);
    }

    /** Desasigna una card de un personaje. */
    function ope_rol_cat_pj_card_desasignar($id)
    {
        global $db;
        $id = (int) $id;
        if ($id < 1 || !$db->table_exists('rol_pj_cards')) {
            return array('ok' => false, 'msg' => 'Asignación no encontrada.');
        }
        $db->delete_query('rol_pj_cards', "id = {$id}");
        return array('ok' => true, 'msg' => 'Card desasignada del personaje.');
    }

    /** Lista de personajes (pid + nombre) para selects. */
    function ope_rol_cat_personajes_lista()
    {
        global $db;
        $out = array();
        if (!$db->table_exists('ope_personajes')) {
            return $out;
        }
        $q = $db->simple_select('ope_personajes', 'id AS pid, nombre', "estado = 'aprobado'", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $out[] = $r;
        }
        return $out;
    }
}
