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
        if (!$db->table_exists('rol_tienda_items')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_tienda_items', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $r['detalles_arr'] = ope_rol_cat_json_list($r['detalles'] ?? '');
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
        if (!$db->table_exists('rol_tripulaciones')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_tripulaciones', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
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
        if (!$db->table_exists('rol_akuma')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_akuma', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
            $out[] = $r;
        }
        return $out;
    }
}

if (!function_exists('ope_rol_cat_bestiario')) {
    function ope_rol_cat_bestiario($solo_activos = true)
    {
        global $db;
        $out = array();
        if (!$db->table_exists('rol_bestiario')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_bestiario', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
        while ($r = $db->fetch_array($q)) {
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
        if (!$db->table_exists('rol_estilos')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_estilos', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
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
        if (!$db->table_exists('rol_personajes')) {
            return $out;
        }
        $razas = function_exists('ope_rol_razas') ? ope_rol_razas() : array();
        $q = $db->simple_select(
            'rol_personajes',
            'pid, nombre, slug, rango, nivel, avatar, icono, rango_faccion, desc_fisica, personalidad, datos, es_npc',
            $where,
            array('order_by' => 'nombre', 'order_dir' => 'ASC')
        );
        while ($r = $db->fetch_array($q)) {
            $datos = json_decode((string) ($r['datos'] ?? ''), true) ?: array();
            $fac   = (string) ($datos['faccion'] ?? '');
            $raza1 = (string) ($datos['raza_principal'] ?? '');
            $raza2 = (string) ($datos['raza_secundaria'] ?? '');
            $hib   = !empty($datos['hibrido']);
            $raza1_lbl = isset($razas[$raza1]) ? $razas[$raza1]['nombre'] : ucfirst($raza1);
            $raza2_lbl = ($raza2 && isset($razas[$raza2])) ? $razas[$raza2]['nombre'] : '';
            $out[] = array(
                'pid'          => (int) $r['pid'],
                'nombre'       => (string) $r['nombre'],
                'slug'         => (string) $r['slug'],
                'rango'        => (string) $r['rango'],
                'nivel'        => (int) $r['nivel'],
                'imagen'       => trim((string) ($r['icono'] ?: $r['avatar'])),
                'faccion'      => $fac,
                'faccion_slug' => function_exists('ope_rol_faccion_slug') ? ope_rol_faccion_slug($fac) : strtolower($fac),
                'rango_faccion' => (string) $r['rango_faccion'],
                'raza'         => $hib && $raza2_lbl !== '' ? ($raza1_lbl . ' / ' . $raza2_lbl) : $raza1_lbl,
                'concepto'     => (string) ($datos['concepto'] ?? ''),
                'apodo'        => (string) ($datos['apodo'] ?? ''),
                'edad'         => (string) ($datos['edad'] ?? ''),
                'genero'       => (string) ($datos['genero'] ?? ''),
                'personalidad' => (string) ($r['personalidad'] ?? ''),
                'apariencia'   => (string) ($r['desc_fisica'] ?? ''),
            );
        }
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
        if ($mid < 1 || !$db->table_exists('rol_mv_mision_asignaciones')) {
            return null;
        }
        $q = $db->simple_select('rol_mv_mision_asignaciones', '*', "mision_id = {$mid}", array('limit' => 1));
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
        if (!$db->table_exists('rol_mv_mision_asignaciones')) {
            return $out;
        }
        $q = $db->simple_select('rol_mv_mision_asignaciones', '*');
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
        if ($db->table_exists('rol_personajes')) {
            $q = $db->simple_select('rol_personajes', 'nombre', "pid = {$pid}", array('limit' => 1));
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
        if (!$db->table_exists('rol_cuentas')) {
            return 0;
        }
        $q = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        return $db->num_rows($q) ? (int) $db->fetch_field($q, 'personaje_activo') : 0;
    }
}

if (!function_exists('ope_rol_cat_tripulacion_miembro')) {
    /** Membresía activa de un personaje, o null. */
    function ope_rol_cat_tripulacion_miembro($pid)
    {
        global $db;
        $pid = (int) $pid;
        if ($pid < 1 || !$db->table_exists('rol_tripulacion_miembros')) {
            return null;
        }
        $q = $db->simple_select('rol_tripulacion_miembros', '*', "pid = {$pid} AND estado = 'activo'", array('limit' => 1));
        return $db->num_rows($q) ? $db->fetch_array($q) : null;
    }

    /** Tripulación + rol del personaje (vista “mi tripulación”), o null. */
    function ope_rol_cat_tripulacion_de_personaje($pid)
    {
        global $db;
        $m = ope_rol_cat_tripulacion_miembro($pid);
        if (!$m || !$db->table_exists('rol_tripulaciones')) {
            return null;
        }
        $tid = (int) $m['tripulacion_id'];
        $q = $db->simple_select('rol_tripulaciones', '*', "id = {$tid} AND activo = 1", array('limit' => 1));
        if (!$db->num_rows($q)) {
            return null;
        }
        $trip = $db->fetch_array($q);
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
    /** Biblioteca de Lore: devuelve todos los artículos activos de rol_lore. */
    function ope_rol_cat_lore($solo_activos = true)
    {
        global $db;
        $out = array();
        if (!$db->table_exists('rol_lore')) {
            return $out;
        }
        $where = $solo_activos ? 'activo = 1' : '';
        $q = $db->simple_select('rol_lore', '*', $where, array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
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
