<?php
/**
 * I-Forge · Helpers compartidos (index.php + forumdisplay.php)
 * --------------------------------------------------------------
 * Funciones puras de presentación reutilizadas por varias páginas:
 *   - iforge_reltime()          tiempo relativo ("hace 5 min")
 *   - iforge_heat()             badge de calor E..SS según nº de posts
 *   - iforge_forum_image()      localiza images/foros/{fid}.{ext}
 *   - iforge_sector_art()       arte SVG procedural (fallback sin foto)
 *   - iforge_render_region_cards()  tarjetas grandes con foto para los
 *                                    hijos directos de un foro/categoría
 *   - iforge_world_root_name()  nombre de la categoría raíz de un foro,
 *                                    para saber si pertenece a "El Mundo"
 */

if (!defined('IN_MYBB')) {
    exit('Direct access not permitted.');
}

if (!function_exists('iforge_reltime')) {
    function iforge_reltime($ts) {
        $d = TIME_NOW - (int)$ts;
        if ($d < 0) $d = 0;
        if ($d < 60) return 'ahora';
        if ($d < 3600) return 'hace '.floor($d / 60).' min';
        if ($d < 86400) return 'hace '.floor($d / 3600).' h';
        if ($d < 2592000) return 'hace '.floor($d / 86400).' d';
        return date('d/m/y', $ts);
    }
}

if (!function_exists('iforge_heat')) {
    function iforge_heat($posts) {
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

if (!function_exists('iforge_forum_image')) {
    function iforge_forum_image($fid) {
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

if (!function_exists('iforge_sector_art')) {
    /** Arte SVG procedural determinista por fid (fallback cuando no hay foto). */
    function iforge_sector_art($fid) {
        static $art = [
            // 0 · fundición / ciudad
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#2a2d33"/><polygon points="0,168 46,64 92,120 128,50 176,120 200,72 200,168" fill="#1b1d22"/><rect x="150" y="30" width="16" height="14" fill="#e0641f"/><rect x="120" y="40" width="10" height="24" fill="#31353d"/></svg>',
            // 1 · bosque
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#20281e"/><polygon points="0,168 24,96 52,138 78,70 104,130 132,58 164,138 190,92 200,168" fill="#141a12"/></svg>',
            // 2 · sierra
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#3a3e46"/><polygon points="0,168 44,52 74,100 104,26 134,94 166,46 200,110 200,168" fill="#24272e"/><polygon points="104,26 118,52 90,52" fill="#c9c4b4"/></svg>',
            // 3 · costa
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#1a2a33"/><path d="M0 112 Q50 96 100 112 T200 112 V168 H0Z" fill="#22414d"/><path d="M0 136 Q50 122 100 136 T200 136 V168 H0Z" fill="#2f5866"/></svg>',
            // 4 · prohibido
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#0f1013"/><polygon points="0,168 30,92 60,146 90,64 120,138 150,84 200,168" fill="#171922"/><circle cx="100" cy="52" r="3" fill="#e0641f"/><circle cx="140" cy="36" r="2" fill="#f4b02f"/></svg>',
            // 5 · refugio
            '<svg viewBox="0 0 200 168" preserveAspectRatio="xMidYMid slice"><rect width="200" height="168" fill="#42382a"/><polygon points="0,168 200,168 200,124 150,134 100,104 50,134 0,124" fill="#2e281d"/><rect x="84" y="52" width="34" height="80" fill="#5a4a35"/><polygon points="74,52 128,52 100,26" fill="#3a3020"/></svg>',
        ];
        return $art[(int)$fid % count($art)];
    }
}

if (!function_exists('iforge_render_region_cards')) {
    /**
     * Construye tarjetas grandes con foto para los foros hijos directos de
     * $pid (usado tanto para "El Mundo" en el índice como para una región
     * concreta en forumdisplay.php, donde $pid es la propia región y los
     * hijos son sus islas).
     */
    function iforge_render_region_cards($pid, array $forumpermissions) {
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
            $img = iforge_forum_image($forum['fid']);
            $art = $img !== null
                ? '<img src="'.$img.'" alt="'.$forumName.'" loading="lazy">'
                : iforge_sector_art($forum['fid']);

            if ($childCount > 0) {
                $meta = '<b>'.$childCount.'</b> islas &middot; '.my_number_format($childThreads).' temas';
            } else {
                $meta = '<b>'.my_number_format($forum['threads']).'</b> temas &middot; '.my_number_format($forum['posts']).' msgs';
            }
            $descHtml = $forumDesc !== '' ? '<div class="iforge-region-d">'.$forumDesc.'</div>' : '';

            $cards .= '
            <a href="'.$bburl.'/forumdisplay.php?fid='.$forum['fid'].'" class="iforge-region">
                <div class="iforge-region-art">'.$art.'</div>
                <div class="iforge-region-veil"></div>
                <div class="iforge-region-in">
                    <div class="iforge-region-n">'.$forumName.'</div>
                    '.$descHtml.'
                    <div class="iforge-region-m">'.$meta.'</div>
                </div>
            </a>';
        }
        return $cards;
    }
}

if (!function_exists('iforge_forum_meta')) {
    /**
     * Ficha enriquecida de una región/isla (dueño actual, clima, zonas,
     * anotaciones), guardada en mybb_rol_forum_meta (1:1 por fid).
     * Devuelve un array con claves 'dueno','clima','zonas' (array) y
     * 'anotaciones', todas vacías si no hay tabla o no hay fila.
     */
    function iforge_forum_meta($fid) {
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

if (!function_exists('iforge_world_root_name')) {
    /**
     * Devuelve el nombre de la categoría raíz (nivel 0) a la que pertenece
     * un foro, usando su `parentlist`. Sirve para saber si un foro vive
     * bajo "El Mundo" y debe usar el estilo rico (tarjetas + cabecera foto).
     */
    function iforge_world_root_name(array $foruminfo) {
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
