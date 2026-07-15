<?php
/**
 * I-Forge · Helpers compartidos (index.php + forumdisplay.php)
 * --------------------------------------------------------------
 * Funciones puras de presentación reutilizadas por varias páginas:
 *   - ope_reltime()          tiempo relativo ("hace 5 min")
 *   - ope_heat()             badge de calor E..SS según nº de posts
 *   - ope_forum_image()      localiza images/foros/{fid}.{ext}
 *   - ope_sector_art()       arte SVG procedural (fallback sin foto)
 *   - ope_render_region_cards()  tarjetas grandes con foto para los
 *                                    hijos directos de un foro/categoría
 *   - ope_world_root_name()  nombre de la categoría raíz de un foro,
 *                                    para saber si pertenece a "El Mundo"
 */

if (!defined('IN_MYBB')) {
    exit('Direct access not permitted.');
}

if (!function_exists('ope_reltime')) {
    function ope_reltime($ts) {
        $d = TIME_NOW - (int)$ts;
        if ($d < 0) $d = 0;
        if ($d < 60) return 'ahora';
        if ($d < 3600) return 'hace '.floor($d / 60).' min';
        if ($d < 86400) return 'hace '.floor($d / 3600).' h';
        if ($d < 2592000) return 'hace '.floor($d / 86400).' d';
        return date('d/m/y', $ts);
    }
}

if (!function_exists('ope_heat')) {
    function ope_heat($posts) {
        $posts = (int)$posts;
        if ($posts >= 1500) return ['SS', 'var(--h8)', 'var(--iron)'];
        if ($posts >= 700)  return ['S',  'var(--h7)', 'var(--iron)'];
        if ($posts >= 300)  return ['A',  'var(--h5)', 'var(--iron)'];
        if ($posts >= 100)  return ['B',  'var(--h4)', 'var(--iron)'];
        if ($posts >= 20)   return ['C',  'var(--h3)', 'var(--paper)'];
        if ($posts >= 1)    return ['D',  'var(--h2)', 'var(--paper)'];
        return ['E', 'var(--h1)', 'var(--paper)'];
    }
}

if (!function_exists('ope_forum_image')) {
    function ope_forum_image($fid) {
        global $mybb;
        static $cache = [];
        $fid = (int)$fid;
        if (isset($cache[$fid])) return $cache[$fid];
        $exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        foreach ($exts as $ext) {
            $rel = 'images/foros/'.$fid.'.'.$ext;
            if (@is_file(MYBB_ROOT.$rel)) {
                return $cache[$fid] = $mybb->settings['bburl'].'/'.$rel;
            }
        }
        return $cache[$fid] = null;
    }
}

if (!function_exists('ope_sector_art')) {
    /** Arte SVG procedural determinista por fid (fallback cuando no hay foto).
     *  Estética GBF: cielo, nubes, éter, islas flotantes. */
    function ope_sector_art($fid) {
        static $art = [
            // 0 · Phantagrande — puerto verde con brisa
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g0" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9bbed8"/><stop offset="1" stop-color="#e7f0f7"/></linearGradient></defs><rect width="200" height="168" fill="url(#g0)"/><path d="M0 110 Q50 96 100 110 T200 110 V168 H0Z" fill="#3a6b56"/><path d="M0 124 Q60 114 120 124 T200 124 V168 H0Z" fill="#2d5642"/><circle cx="160" cy="36" r="14" fill="#fff" opacity=".7"/><circle cx="180" cy="48" r="10" fill="#fff" opacity=".55"/></svg>',
            // 1 · Nalhegrande — niebla dorada entre montañas
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c9a86b"/><stop offset="1" stop-color="#e8d3a5"/></linearGradient></defs><rect width="200" height="168" fill="url(#g1)"/><polygon points="0,168 36,90 70,128 102,68 134,118 168,80 200,138 200,168" fill="#7a5837" opacity=".7"/><path d="M0 130 Q60 118 120 130 T200 130 V168 H0Z" fill="#d8be7e" opacity=".55"/></svg>',
            // 2 · Zeephone — hielo azul
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7eb8d8"/><stop offset="1" stop-color="#cee8f1"/></linearGradient></defs><rect width="200" height="168" fill="url(#g2)"/><polygon points="20,60 36,32 50,58" fill="#fff" opacity=".9"/><polygon points="100,40 122,12 142,38" fill="#fff" opacity=".9"/><polygon points="170,72 186,46 200,70" fill="#fff" opacity=".9"/><path d="M0 124 Q60 116 120 124 T200 124 V168 H0Z" fill="#5a9fc4" opacity=".5"/></svg>',
            // 3 · Auguste — volcán
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g3" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d99a5a"/><stop offset="1" stop-color="#f4c987"/></linearGradient></defs><rect width="200" height="168" fill="url(#g3)"/><polygon points="60,168 100,52 140,168" fill="#8b3a25"/><polygon points="100,52 92,68 108,68" fill="#1a0a06"/><path d="M0 132 Q60 120 120 132 T200 132 V168 H0Z" fill="#7a2a18" opacity=".6"/></svg>',
            // 4 · Estalucia — isla legendaria dorada
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g4" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d4c4a8"/><stop offset="1" stop-color="#f3e5c4"/></linearGradient></defs><rect width="200" height="168" fill="url(#g4)"/><polygon points="40,120 100,30 160,120" fill="#8f7a5a" opacity=".7"/><polygon points="100,30 88,52 112,52" fill="#4a3a26"/><circle cx="100" cy="58" r="4" fill="#b7924e"/><path d="M0 124 Q60 116 120 124 T200 124 V168 H0Z" fill="#a08a64" opacity=".6"/></svg>',
            // 5 · Reserva — altamar
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="g5" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#5ba3d0"/><stop offset="1" stop-color="#a8d4e8"/></linearGradient></defs><rect width="200" height="168" fill="url(#g5)"/><path d="M0 100 Q50 84 100 100 T200 100 V168 H0Z" fill="#2f6fa0"/><path d="M0 124 Q50 110 100 124 T200 124 V168 H0Z" fill="#3f8fc0" opacity=".7"/></svg>',
        ];
        return $art[(int)$fid % count($art)];
    }
}

if (!function_exists('ope_render_region_cards')) {
    /**
     * Construye tarjetas grandes con foto para los foros hijos directos de
     * $pid (usado tanto para "El Mundo" en el índice como para una región
     * concreta en forumdisplay.php, donde $pid es la propia región y los
     * hijos son sus islas).
     */
    function ope_render_region_cards($pid, array $forumpermissions) {
        global $db;
        $bburl = $GLOBALS['mybb']->settings['bburl'];
        $cards = '';

        $forumQuery = $db->query("
            SELECT f.fid, f.name, f.description, f.threads, f.posts, f.lastpost, f.lastpostsubject, f.lastposter
            FROM ".TABLE_PREFIX."forums f
            WHERE f.type = 'f' AND f.pid = '".(int)$pid."' AND f.active = 1
            ORDER BY f.disporder ASC
        ");
        while ($forum = $db->fetch_array($forumQuery)) {
            if (isset($forumpermissions[$forum['fid']]) && $forumpermissions[$forum['fid']]['canview'] != 1) {
                continue;
            }

            $childCount = 0; $childThreads = 0;
            $subQuery = $db->query("
                SELECT fid, threads FROM ".TABLE_PREFIX."forums
                WHERE type = 'f' AND pid = '".(int)$forum['fid']."' AND active = 1
            ");
            while ($sub = $db->fetch_array($subQuery)) {
                if (isset($forumpermissions[$sub['fid']]) && $forumpermissions[$sub['fid']]['canview'] != 1) {
                    continue;
                }
                $childCount++;
                $childThreads += (int)$sub['threads'];
            }

            $forumName = htmlspecialchars_uni($forum['name']);
            $forumDesc = htmlspecialchars_uni($forum['description']);
            $img = ope_forum_image($forum['fid']);
            $art = $img !== null
                ? '<img src="'.$img.'" alt="'.$forumName.'" loading="lazy">'
                : ope_sector_art($forum['fid']);

            if ($childCount > 0) {
                $meta = '<b>'.$childCount.'</b> islas &middot; '.my_number_format($childThreads).' temas';
            } else {
                $meta = '<b>'.my_number_format($forum['threads']).'</b> temas &middot; '.my_number_format($forum['posts']).' msgs';
            }
            $descHtml = $forumDesc !== '' ? '<div class="ope-region-d">'.$forumDesc.'</div>' : '';

            // Slug estable del nombre (east-blue, calm-belt, paraiso, new-world...)
            // usado por el bento de "El Mundo" para asignar cada panel a su celda.
            $slug = strtolower($forum['name']);
            $slug = strtr($slug, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');

            $cards .= '
            <a href="'.$bburl.'/forumdisplay.php?fid='.$forum['fid'].'" class="ope-region ope-region--'.$slug.'">
                <div class="ope-region-art">'.$art.'</div>
                <div class="ope-region-veil"></div>
                <div class="ope-region-in">
                    <div class="ope-region-n">'.$forumName.'</div>
                    '.$descHtml.'
                    <div class="ope-region-m">'.$meta.'</div>
                </div>
            </a>';
        }
        return $cards;
    }
}

if (!function_exists('ope_forum_meta')) {
    /**
     * Ficha enriquecida de una región/isla (dueño actual, clima, zonas,
     * anotaciones), guardada en mybb_rol_forum_meta (1:1 por fid).
     * Devuelve un array con claves 'dueno','clima','zonas' (array) y
     * 'anotaciones', todas vacías si no hay tabla o no hay fila.
     */
    function ope_forum_meta($fid) {
        global $db;
        static $cache = [];
        $fid = (int)$fid;
        if (isset($cache[$fid])) return $cache[$fid];

        $empty = ['dueno' => '', 'clima' => '', 'zonas' => [], 'anotaciones' => ''];
        if (!$db->table_exists('rol_forum_meta')) {
            return $cache[$fid] = $empty;
        }
        $row = $db->fetch_array($db->simple_select('rol_forum_meta', '*', "fid='{$fid}'"));
        if (!$row) {
            return $cache[$fid] = $empty;
        }
        $zonas = json_decode((string)$row['zonas'], true);
        return $cache[$fid] = [
            'dueno' => (string)$row['dueno'],
            'clima' => (string)$row['clima'],
            'zonas' => is_array($zonas) ? $zonas : [],
            'anotaciones' => (string)$row['anotaciones'],
        ];
    }
}

if (!function_exists('ope_world_root_name')) {
    /**
     * Devuelve el nombre de la categoría raíz (nivel 0) a la que pertenece
     * un foro, usando su `parentlist`. Sirve para saber si un foro vive
     * bajo "El Mundo" y debe usar el estilo rico (tarjetas + cabecera foto).
     */
    function ope_world_root_name(array $foruminfo) {
        global $db;
        $parentlist = trim($foruminfo['parentlist'] ?? '', ', ');
        if ($parentlist === '') {
            return '';
        }
        $ids = array_filter(array_map('intval', explode(',', $parentlist)));
        if (empty($ids)) {
            return '';
        }
        $rootFid = min($ids);
        $row = $db->fetch_array($db->simple_select('forums', 'name', "fid='".(int)$rootFid."' AND type='c'"));
        return $row ? $row['name'] : '';
    }
}
