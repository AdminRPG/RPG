<?php
/**
 * One Piece: Eternal · Acceso a datos de los catálogos gestionables por staff.
 * -----------------------------------------------------------------
 * Fuente única para leer de BD lo que antes eran arrays mockup en los .php
 * públicos (tienda, tripulaciones, bibliotecas de akuma/bestiario/estilos) y
 * las bibliotecas de personajes/NPC (fuente canónica mybb_ope_personajes).
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
        // Fuente canónica: mybb_ope_akumas (7 Seas, F5.1); si la corrida aún
        // no creó ope_akumas se usa el espejo ope_akuma (entorno en transición).
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
     * Biblioteca de personajes jugadores (fuente canónica mybb_ope_personajes).
     * Solo PJs aprobados y no NPC. Resuelve facción, raza y concepto del JSON
     * `datos`. No inventa nada: si un campo no existe queda vacío.
     */
    function ope_rol_cat_personajes_publicos()
    {
        return ope_rol_cat_fichas_query('es_npc = 0 AND estado = \'aprobado\'');
    }

    /** Biblioteca de NPCs: mybb_ope_personajes con es_NPC=1 (auto-aprobados). */
    function ope_rol_cat_npcs_publicos()
    {
        return ope_rol_cat_fichas_query('es_npc = 1');
    }

    /** Consulta común de fichas para las bibliotecas de personajes/NPC. */
    function ope_rol_cat_fichas_query($where)
    {
        global $db;
        $out = array();
        // Fuente canónica: mybb_ope_personajes (es_NPC, estado, nivel…).
        $canonico = function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes') && $db->table_exists('personajes');
        if (!$canonico) {
            return $out;
        }
        $razas = function_exists('ope_rol_razas') ? ope_rol_razas() : array();
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









if (!function_exists('ope_rol_cat_pj_cards')) {
    

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
