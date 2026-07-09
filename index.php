<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'index.php');

$templatelist = "index,index_whosonline,index_whosonline_memberbit,forumbit_depth1_cat,forumbit_depth2_cat,forumbit_depth2_forum,forumbit_depth1_forum_lastpost,forumbit_depth2_forum_lastpost,forumbit_moderators";
$templatelist .= ",index_birthdays_birthday,index_birthdays,index_logoutlink,index_statspage,index_stats,forumbit_depth3,forumbit_depth3_statusicon,index_boardstats,forumbit_depth2_forum_lastpost_never,forumbit_depth2_forum_viewers";
$templatelist .= ",forumbit_moderators_group,forumbit_moderators_user,forumbit_depth2_forum_lastpost_hidden,forumbit_subforums,forumbit_depth2_forum_unapproved_posts,forumbit_depth2_forum_unapproved_threads";

require_once './global.php';
require_once MYBB_ROOT.'inc/functions_forumlist.php';
require_once MYBB_ROOT.'inc/class_parser.php';
require_once MYBB_ROOT.'inc/ope_functions.php';
$parser = new postParser;

// Load global language phrases
$lang->load('index');

$plugins->run_hooks('index_start');

$logoutlink = '';
if($mybb->user['uid'] != 0)
{
	eval('$logoutlink = "'.$templates->get('index_logoutlink').'";');
}

$statspage = '';
if($mybb->settings['statsenabled'] != 0)
{
	$stats_page_separator = '';
	if(!empty($logoutlink))
	{
		$stats_page_separator = $lang->board_stats_link_separator;
	}
	eval('$statspage = "'.$templates->get('index_statspage').'";');
}

$onlinecount = null;
$whosonline = '';
if($mybb->settings['showwol'] != 0 && $mybb->usergroup['canviewonline'] != 0)
{
	// Get the online users.
	if($mybb->settings['wolorder'] == 'username')
	{
		$order_by = 'u.username ASC';
		$order_by2 = 's.time DESC';
	}
	else
	{
		$order_by = 's.time DESC';
		$order_by2 = 'u.username ASC';
	}

	$timesearch = TIME_NOW - (int)$mybb->settings['wolcutoff'];

	$membercount = $guestcount = $anoncount = $botcount = 0;
	$forum_viewers = $doneusers = $onlinemembers = $onlinebots = array();

	if($mybb->settings['showforumviewing'] != 0)
	{
		$query = $db->query("
			SELECT
				location1, COUNT(DISTINCT ip) AS guestcount
			FROM
				".TABLE_PREFIX."sessions
			WHERE uid = 0 AND location1 != 0 AND SUBSTR(sid,4,1) != '=' AND time > $timesearch
			GROUP BY location1
		");

		while($location = $db->fetch_array($query))
		{
			if(isset($forum_viewers[$location['location1']]))
			{
				$forum_viewers[$location['location1']] += $location['guestcount'];
			}
			else
			{
				$forum_viewers[$location['location1']] = $location['guestcount'];
			}
		}
	}

	$query = $db->simple_select("sessions", "COUNT(DISTINCT ip) AS guestcount", "uid = 0 AND SUBSTR(sid,4,1) != '=' AND time > $timesearch");
	$guestcount = $db->fetch_field($query, "guestcount");

	$query = $db->query("
		SELECT
			s.sid, s.ip, s.uid, s.time, s.location, s.location1, u.username, u.invisible, u.usergroup, u.displaygroup
		FROM
			".TABLE_PREFIX."sessions s
			LEFT JOIN ".TABLE_PREFIX."users u ON (s.uid=u.uid)
		WHERE (s.uid != 0 OR SUBSTR(s.sid,4,1) = '=') AND s.time > $timesearch
		ORDER BY {$order_by}, {$order_by2}
	");

	// Fetch spiders
	$spiders = $cache->read('spiders');

	// Loop through all users and spiders.
	while($user = $db->fetch_array($query))
	{
		// Create a key to test if this user is a search bot.
		$botkey = my_strtolower(str_replace('bot=', '', $user['sid']));

		// Decide what type of user we are dealing with.
		if($user['uid'] > 0)
		{
			// The user is registered.
			if(empty($doneusers[$user['uid']]) || $doneusers[$user['uid']] < $user['time'])
			{
				// If the user is logged in anonymously, update the count for that.
				if($user['invisible'] == 1)
				{
					++$anoncount;
				}
				++$membercount;
				if($user['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1 || $user['uid'] == $mybb->user['uid'])
				{
					// If this usergroup can see anonymously logged-in users, mark them.
					if($user['invisible'] == 1)
					{
						$invisiblemark = '*';
					}
					else
					{
						$invisiblemark = '';
					}

					// Properly format the username and assign the template.
					$user['username'] = format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']);
					$user['profilelink'] = build_profile_link($user['username'], $user['uid']);
					eval('$onlinemembers[] = "'.$templates->get('index_whosonline_memberbit', 1, 0).'";');
				}
				// This user has been handled.
				$doneusers[$user['uid']] = $user['time'];
			}
		}
		elseif(my_strpos($user['sid'], 'bot=') !== false && $spiders[$botkey] && $mybb->settings['woldisplayspiders'] == 1)
		{
			if($mybb->settings['wolorder'] == 'username')
			{
				$key = $spiders[$botkey]['name'];
			}
			else
			{
				$key = $user['time'];
			}

			// The user is a search bot.
			$onlinebots[$key] = format_name($spiders[$botkey]['name'], $spiders[$botkey]['usergroup']);
			++$botcount;
		}

		if($user['location1'])
		{
			if(isset($forum_viewers[$user['location1']]))
			{
				++$forum_viewers[$user['location1']];
			}
			else
			{
				$forum_viewers[$user['location1']] = 1;
			}
		}
	}

	if($mybb->settings['wolorder'] == 'activity')
	{
		// activity ordering is DESC, username is ASC
		krsort($onlinebots);
	}
	else
	{
		ksort($onlinebots);
	}

	$onlinemembers = array_merge($onlinebots, $onlinemembers);
	if(!empty($onlinemembers))
	{
		$comma = $lang->comma." ";
		$onlinemembers = implode($comma, $onlinemembers);
	}
	else
	{
		$onlinemembers = "";
	}

	// Build the who's online bit on the index page.
	$onlinecount = $membercount + $guestcount + $botcount;

	if($onlinecount != 1)
	{
		$onlinebit = $lang->online_online_plural;
	}
	else
	{
		$onlinebit = $lang->online_online_singular;
	}
	if($membercount != 1)
	{
		$memberbit = $lang->online_member_plural;
	}
	else
	{
		$memberbit = $lang->online_member_singular;
	}
	if($anoncount != 1)
	{
		$anonbit = $lang->online_anon_plural;
	}
	else
	{
		$anonbit = $lang->online_anon_singular;
	}
	if($guestcount != 1)
	{
		$guestbit = $lang->online_guest_plural;
	}
	else
	{
		$guestbit = $lang->online_guest_singular;
	}
	$lang->online_note = $lang->sprintf($lang->online_note, my_number_format($onlinecount), $onlinebit, $mybb->settings['wolcutoffmins'], my_number_format($membercount), $memberbit, my_number_format($anoncount), $anonbit, my_number_format($guestcount), $guestbit);
	eval('$whosonline = "'.$templates->get('index_whosonline').'";');
}

// Build the birthdays for to show on the index page.
$bdays = $birthdays = '';
if($mybb->settings['showbirthdays'] != 0)
{
	// First, see what day this is.
	$bdaycount = $bdayhidden = 0;
	$bdaydate = my_date('j-n', TIME_NOW, '', 0);
	$year = my_date('Y', TIME_NOW, '', 0);

	$bdaycache = $cache->read('birthdays');

	if(!is_array($bdaycache))
	{
		$cache->update_birthdays();
		$bdaycache = $cache->read('birthdays');
	}

	$hiddencount = 0;
	$today_bdays = array();
	if(isset($bdaycache[$bdaydate]))
	{
		if(isset($bdaycache[$bdaydate]['hiddencount']))
		{
			$hiddencount = $bdaycache[$bdaydate]['hiddencount'];
		}
		if(isset($bdaycache[$bdaydate]['users']))
		{
			$today_bdays = $bdaycache[$bdaydate]['users'];
		}
	}

	$comma = '';
	if(!empty($today_bdays))
	{
		if((int)$mybb->settings['showbirthdayspostlimit'] > 0)
		{
			$bdayusers = array();
			foreach($today_bdays as $key => $bdayuser_pc)
			{
				$bdayusers[$bdayuser_pc['uid']] = $key;
			}

			if(!empty($bdayusers))
			{
				// Find out if our users have enough posts to be seen on our birthday list
				$bday_sql = implode(',', array_keys($bdayusers));
				$query = $db->simple_select('users', 'uid, postnum', "uid IN ({$bday_sql})");

				while($bdayuser = $db->fetch_array($query))
				{
					if($bdayuser['postnum'] < $mybb->settings['showbirthdayspostlimit'])
					{
						unset($today_bdays[$bdayusers[$bdayuser['uid']]]);
					}
				}
			}
		}

		// We still have birthdays - display them in our list!
		if(!empty($today_bdays))
		{
			foreach($today_bdays as $bdayuser)
			{
				if($bdayuser['displaygroup'] == 0)
				{
					$bdayuser['displaygroup'] = $bdayuser['usergroup'];
				}

				// If this user's display group can't be seen in the birthday list, skip it
				if(isset($groupscache[$bdayuser['displaygroup']]) && $groupscache[$bdayuser['displaygroup']]['showinbirthdaylist'] != 1)
				{
					continue;
				}

				$age = '';
				$bday = explode('-', $bdayuser['birthday']);
				if($year > $bday['2'] && $bday['2'] != '')
				{
					$age = ' ('.($year - $bday['2']).')';
				}

				$bdayuser['username'] = format_name(htmlspecialchars_uni($bdayuser['username']), $bdayuser['usergroup'], $bdayuser['displaygroup']);
				$bdayuser['profilelink'] = build_profile_link($bdayuser['username'], $bdayuser['uid']);
				eval('$bdays .= "'.$templates->get('index_birthdays_birthday', 1, 0).'";');
				++$bdaycount;
				$comma = $lang->comma;
			}
		}
	}

	if($hiddencount > 0)
	{
		if($bdaycount > 0)
		{
			$bdays .= ' - ';
		}

		$bdays .= "{$hiddencount} {$lang->birthdayhidden}";
	}

	// If there are one or more birthdays, show them.
	if($bdaycount > 0 || $hiddencount > 0)
	{
		eval('$birthdays = "'.$templates->get('index_birthdays').'";');
	}
}

// Build the forum statistics to show on the index page.
$forumstats = '';
if($mybb->settings['showindexstats'] != 0)
{
	// First, load the stats cache.
	$stats = $cache->read('stats');

	// Check who's the newest member.
	if(!$stats['lastusername'])
	{
		$newestmember = $lang->nobody;;
	}
	else
	{
		$newestmember = build_profile_link($stats['lastusername'], $stats['lastuid']);
	}

	// Format the stats language.
	$lang->stats_posts_threads = $lang->sprintf($lang->stats_posts_threads, my_number_format($stats['numposts']), my_number_format($stats['numthreads']));
	$lang->stats_numusers = $lang->sprintf($lang->stats_numusers, my_number_format($stats['numusers']));
	$lang->stats_newestuser = $lang->sprintf($lang->stats_newestuser, $newestmember);

	// Find out what the highest users online count is.
	$mostonline = $cache->read('mostonline');
	if($onlinecount !== null && $onlinecount > $mostonline['numusers'])
	{
		$time = TIME_NOW;
		$mostonline['numusers'] = $onlinecount;
		$mostonline['time'] = $time;
		$cache->update('mostonline', $mostonline);
	}
	$recordcount = $mostonline['numusers'];
	$recorddate = my_date($mybb->settings['dateformat'], $mostonline['time']);
	$recordtime = my_date($mybb->settings['timeformat'], $mostonline['time']);

	// Then format that language string.
	$lang->stats_mostonline = $lang->sprintf($lang->stats_mostonline, my_number_format($recordcount), $recorddate, $recordtime);

	eval('$forumstats = "'.$templates->get('index_stats').'";');
}

// Show the board statistics table only if one or more index statistics are enabled.
$boardstats = '';
if(($mybb->settings['showwol'] != 0 && $mybb->usergroup['canviewonline'] != 0) || $mybb->settings['showindexstats'] != 0 || ($mybb->settings['showbirthdays'] != 0 && $bdaycount > 0))
{
	if(!isset($stats) || isset($stats) && !is_array($stats))
	{
		// Load the stats cache.
		$stats = $cache->read('stats');
	}

	if(!isset($collapsedthead['boardstats']))
	{
		$collapsedthead['boardstats'] = '';
	}
	if(!isset($collapsedimg['boardstats']))
	{
		$collapsedimg['boardstats'] = '';
	}
	if(!isset($collapsed['boardstats_e']))
	{
		$collapsed['boardstats_e'] = '';
	}

	$expaltext = (in_array("boardstats", $collapse)) ? $lang->expcol_expand : $lang->expcol_collapse;
	eval('$boardstats = "'.$templates->get('index_boardstats').'";');
}

if($mybb->user['uid'] == 0)
{
	// Build a forum cache.
	$query = $db->simple_select('forums', '*', 'active!=0', array('order_by' => 'pid, disporder'));

	$forumsread = array();
	if(isset($mybb->cookies['mybb']['forumread']))
	{
		$forumsread = my_unserialize($mybb->cookies['mybb']['forumread'], false);
	}
}
else
{
	// Build a forum cache.
	$query = $db->query("
		SELECT f.*, fr.dateline AS lastread
		FROM ".TABLE_PREFIX."forums f
		LEFT JOIN ".TABLE_PREFIX."forumsread fr ON (fr.fid = f.fid AND fr.uid = '{$mybb->user['uid']}')
		WHERE f.active != 0
		ORDER BY pid, disporder
	");
}

while($forum = $db->fetch_array($query))
{
	if($mybb->user['uid'] == 0)
	{
		if(!empty($forumsread[$forum['fid']]))
		{
			$forum['lastread'] = $forumsread[$forum['fid']];
		}
	}
	$fcache[$forum['pid']][$forum['disporder']][$forum['fid']] = $forum;
}
$forumpermissions = forum_permissions();

// Get the forum moderators if the setting is enabled.
$moderatorcache = array();
if($mybb->settings['modlist'] != 0 && $mybb->settings['modlist'] != 'off')
{
	$moderatorcache = $cache->read('moderators');
}

$excols = 'index';
$permissioncache = null;
$bgcolor = 'trow1';

// Decide if we're showing first-level subforums on the index page.
$showdepth = 2;
if($mybb->settings['subforumsindex'] != 0)
{
	$showdepth = 3;
}

$forum_list = build_forumbits();
$forums = $forum_list['forum_list'];

$plugins->run_hooks('index_end');

// I-Forge: Register link for guests
$ope_register_link = '';
if ($mybb->user['uid'] == 0) {
    $ope_register_link = '<a href="'.$mybb->settings['bburl'].'/member.php?action=register" class="ope-nav-link" style="color:var(--color-accent)">Registrarse</a>';
}

// I-Forge navbar: Zona Privada link visibility
$ope_zona_privada_link = '';
if ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1) {
    $ope_zona_privada_link = '<a href="'.$mybb->settings['bburl'].'/private.php" class="ope-nav-link">Zona Privada</a>';
}

// I-Forge navbar: User menu
if ($mybb->user['uid']) {
    $ope_user_menu = '
    <div class="ope-user-menu">
      <button class="ope-user-btn" id="ope-user-btn">
        <img src="'.$mybb->settings['bburl'].'/images/nav-icon.svg" width="28" height="28" alt="Personaje">
      </button>
      <div class="ope-dropdown" id="ope-dropdown">
        <a href="'.$mybb->settings['bburl'].'/mensajes.php" class="ope-dropdown-item">Mensajería</a>
        <a href="'.$mybb->settings['bburl'].'/configuracion.php" class="ope-dropdown-item">Configuración</a>
        <hr class="ope-dropdown-divider">
        <a href="'.$mybb->settings['bburl'].'/member.php?action=logout&amp;logoutkey='.$mybb->user['logoutkey'].'" class="ope-dropdown-item">Cerrar sesión</a>
      </div>
    </div>';
} else {
    $ope_user_menu = '
    <a href="'.$mybb->settings['bburl'].'/member.php?action=login" class="ope-nav-link">Iniciar sesión</a>
    '.$ope_register_link;
}

// I-Forge: Random banner
$banner_url = $mybb->settings['bburl'] . '/images/banners/default-banner.svg';
$bannerDir = MYBB_ROOT . 'images/banners/';
$banners = glob($bannerDir . '*.{svg,jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (!empty($banners)) {
    $randomBanner = $banners[array_rand($banners)];
    $banner_url = $mybb->settings['bburl'] . '/images/banners/' . basename($randomBanner);
}

// I-Forge: Calendario (placeholder until real calendar is built)
$calendario_texto = 'DÍA 1 · PRIMAVERA · AÑO I';

// I-Forge: Latest posts (feed with avatar initial + relative time)
// El autor mostrado es el PERSONAJE que posteó (ope_pid), no la cuenta.
$ope_latest_posts = '';
$ope_has_rol = $db->table_exists('rol_personajes');
$feedSelect = "p.pid, p.subject, p.tid, p.uid, p.dateline, u.username";
$feedJoin   = "";
if ($ope_has_rol) {
    $feedSelect .= ", p.ope_pid, rp.nombre AS char_name";
    $feedJoin    = "LEFT JOIN ".TABLE_PREFIX."rol_personajes rp ON (rp.pid = p.ope_pid)";
}
$q = $db->query("
    SELECT {$feedSelect}
    FROM ".TABLE_PREFIX."posts p
    LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = p.uid)
    {$feedJoin}
    WHERE p.visible = 1
    ORDER BY p.dateline DESC
    LIMIT 6
");
while ($post = $db->fetch_array($q)) {
    $charPid  = (int) ($post['ope_pid'] ?? 0);
    $charName = trim((string) ($post['char_name'] ?? ''));
    if ($charPid > 0 && $charName !== '') {
        $author   = $charName;
        $linkHref = $mybb->settings['bburl'].'/ficha.php?pid='.$charPid;
    } else {
        $author   = trim($post['username']) !== '' ? $post['username'] : 'Invitado';
        $linkHref = '';
    }
    $initial = htmlspecialchars_uni(my_strtoupper(my_substr($author, 0, 1)));
    $ope_latest_posts .= '
    <a href="'.$mybb->settings['bburl'].'/showthread.php?tid='.$post['tid'].'&amp;pid='.$post['pid'].'#pid'.$post['pid'].'" class="ope-feed-i">
        <span class="ope-feed-av">'.$initial.'</span>
        <span class="ope-feed-main">
            <span class="ope-feed-t">'.htmlspecialchars_uni($post['subject']).'</span>
            <span class="ope-feed-m">'.htmlspecialchars_uni($author).' &middot; '.ope_reltime($post['dateline']).'</span>
        </span>
    </a>';
}

// I-Forge: Home dynamic content (curiosidades + lore) — stored as JSON in datacache, admin-editable
$ope_home = $cache->read('ope_home');
if (!is_array($ope_home) || empty($ope_home)) {
    $ope_home = [
        'curiosidades' => [
            'En el Grand Line las brújulas normales no sirven: solo un Log Pose marca el rumbo entre islas.',
            'La escala de poder mide fuerza, no tamaño. Un rango alto impone aunque el personaje parezca inofensivo.',
            'Un personaje sin rumbo se estanca; el mundo premia a quien sigue navegando y arriesgando.',
            'Cada historia deja huella en el mundo. Ninguna aventura pasa sin dejar marca.',
        ],
        'lore' => [
            'titulo' => 'La aventura continúa',
            'texto'  => 'One Piece Eternal abre sus puertas a una nueva generación de aventureros. El mundo, sus reglas y su historia se escriben aquí, historia a historia. Crea tu personaje y zárpate a descubrir el mundo.',
        ],
        // Instante OOC en el que arranca el día 1 · Primavera · Año I del calendario on-rol.
        'rol_epoch' => mktime(0, 0, 0, 1, 1, 2026),
        'discord_url' => 'https://discord.gg/',
    ];
    $cache->update('ope_home', $ope_home);
}
// Afiliados (admin-editable). 'hermanos' = botones grandes; 'afiliados' = botones 88x31.
// Cada entrada: ['url' => ..., 'img' => ..., 'nombre' => ...].
if (!isset($ope_home['afiliados'])) { $ope_home['afiliados'] = []; }
if (!isset($ope_home['hermanos'])) { $ope_home['hermanos'] = []; }
$ope_hermanos = is_array($ope_home['hermanos']) ? $ope_home['hermanos'] : [];
$ope_afiliados = is_array($ope_home['afiliados']) ? $ope_home['afiliados'] : [];

// I-Forge: banner del hero — resuelve cualquier imagen images/ope/banner.* (jpg/png/webp…)
$ope_hero_banner = '';
$heroDir = MYBB_ROOT . 'images/ope/';
$heroCandidates = glob($heroDir . 'banner.{jpg,jpeg,png,webp,gif,avif}', GLOB_BRACE);
if (!empty($heroCandidates)) {
    // Prioriza el más reciente para que un archivo recién subido gane.
    usort($heroCandidates, static function ($a, $b) { return filemtime($b) <=> filemtime($a); });
    $ope_hero_banner = $mybb->settings['bburl'] . '/images/ope/' . basename($heroCandidates[0]);
}

// Construye los botones de afiliados/hermanos (HTML) para el template.
$ope_hermanos_html = '';
foreach ($ope_hermanos as $af) {
    $url = htmlspecialchars_uni((string)($af['url'] ?? '#'));
    $img = trim((string)($af['img'] ?? ''));
    $nombre = htmlspecialchars_uni((string)($af['nombre'] ?? 'Afiliado hermano'));
    if ($img !== '') {
        $inner = '<img src="'.htmlspecialchars_uni($img).'" alt="'.$nombre.'" loading="lazy">';
    } else {
        $inner = '<span>'.$nombre.'</span>';
    }
    $ope_hermanos_html .= '<a href="'.$url.'" class="ope-afil-hermano" title="'.$nombre.'" target="_blank" rel="noopener">'.$inner.'</a>';
}
$ope_afiliados_html = '';
foreach ($ope_afiliados as $af) {
    $url = htmlspecialchars_uni((string)($af['url'] ?? '#'));
    $img = trim((string)($af['img'] ?? ''));
    $nombre = htmlspecialchars_uni((string)($af['nombre'] ?? 'Afiliado'));
    if ($img !== '') {
        $inner = '<img src="'.htmlspecialchars_uni($img).'" alt="'.$nombre.'" loading="lazy">';
    } else {
        $inner = '<span>'.$nombre.'</span>';
    }
    $ope_afiliados_html .= '<a href="'.$url.'" class="ope-afil-btn" title="'.$nombre.'" target="_blank" rel="noopener">'.$inner.'</a>';
}

// I-Forge: calendario on-rol (4 estaciones × 65 días; 1 día OOC = 2 días on-rol)
$rol_epoch = isset($ope_home['rol_epoch']) ? (int)$ope_home['rol_epoch'] : mktime(0, 0, 0, 1, 1, 2026);
$rol_seasons = [
    ['Primavera', 'var(--patina-hi)'],
    ['Verano',    'var(--ember)'],
    ['Otoño',     'var(--h4)'],
    ['Invierno',  'var(--h1)'],
];
$ooc_days = (int)floor((TIME_NOW - $rol_epoch) / 86400);
if ($ooc_days < 0) { $ooc_days = 0; }
$rol_day_index = $ooc_days * 2;                 // días on-rol transcurridos (0-based)
$rol_year = (int)floor($rol_day_index / 260) + 1;
$rol_doy = $rol_day_index % 260;                // día dentro del año (0..259)
$rol_season_idx = (int)floor($rol_doy / 65);    // 0..3
$rol_day_in_season = ($rol_doy % 65) + 1;       // 1..65
$ope_rol_season = $rol_seasons[$rol_season_idx][0];
$ope_rol_season_color = $rol_seasons[$rol_season_idx][1];
$ope_rol_day = $rol_day_in_season;
$ope_rol_year = $rol_year;
$ope_rol_progress = round(($rol_day_in_season / 65) * 100);
$ope_rol_year_label = function_exists('ope_rol_year_label') ? ope_rol_year_label($rol_year) : (string)$rol_year;

$curiosidades = (isset($ope_home['curiosidades']) && is_array($ope_home['curiosidades'])) ? array_values($ope_home['curiosidades']) : [];
$lore = (isset($ope_home['lore']) && is_array($ope_home['lore'])) ? $ope_home['lore'] : ['titulo' => '', 'texto' => ''];

$curiosidades_json = json_encode($curiosidades, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($curiosidades_json === false) {
    $curiosidades_json = '[]';
}
$ope_curiosidad = !empty($curiosidades) ? htmlspecialchars_uni($curiosidades[0]) : '';

// I-Forge: Últimas noticias (Mundo Vivo + manuales). Rotación + clic despliega.
$ope_noticias = function_exists('ope_rol_mv_noticias_activas') ? ope_rol_mv_noticias_activas(8) : array();
$ope_noticias_data = array();
foreach ($ope_noticias as $n) {
    $ope_noticias_data[] = array(
        'titulo'  => (string)$n['titulo'],
        'resumen' => (string)($n['resumen'] !== '' ? $n['resumen'] : $n['titulo']),
        'cuerpo'  => (string)$n['cuerpo_html'],
    );
}
$ope_has_news = !empty($ope_noticias_data);
if (!$ope_has_news) {
    foreach ($curiosidades as $c) {
        $ope_noticias_data[] = array('titulo' => '', 'resumen' => (string)$c, 'cuerpo' => '');
    }
}
$ope_noticias_json = json_encode($ope_noticias_data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($ope_noticias_json === false) { $ope_noticias_json = '[]'; }
$ope_noticia_kicker = $ope_has_news ? '// últimas noticias' : '// curiosidad';
$ope_noticia_first_title = !empty($ope_noticias_data) ? htmlspecialchars_uni($ope_noticias_data[0]['titulo']) : '';
$ope_noticia_first_text = !empty($ope_noticias_data) ? htmlspecialchars_uni($ope_noticias_data[0]['resumen']) : '';

$ope_lore_title = htmlspecialchars_uni($lore['titulo'] ?? '');
$ope_lore_text = htmlspecialchars_uni($lore['texto'] ?? '');
$ope_discord_url = htmlspecialchars_uni((string)($ope_home['discord_url'] ?? 'https://discord.gg/'));

// I-Forge: Presence — active now + last 24h (real MyBB session/user data)
$ope_online_now = '';
$ope_online_now_count = 0;
$ope_online_24h = '';
$ope_online_24h_count = 0;

$now_cut = TIME_NOW - (int)$mybb->settings['wolcutoff'];
$q_now = $db->query("
    SELECT u.uid, u.username, u.usergroup, u.displaygroup, u.invisible, MAX(s.time) AS lasttime
    FROM ".TABLE_PREFIX."sessions s
    INNER JOIN ".TABLE_PREFIX."users u ON (u.uid = s.uid)
    WHERE s.uid > 0 AND s.time > {$now_cut}
    GROUP BY u.uid, u.username, u.usergroup, u.displaygroup, u.invisible
    ORDER BY u.username ASC
");
while ($ou = $db->fetch_array($q_now)) {
    if ($ou['invisible'] == 1 && $mybb->usergroup['canviewwolinvis'] != 1 && $ou['uid'] != $mybb->user['uid']) {
        continue;
    }
    $name = format_name(htmlspecialchars_uni($ou['username']), $ou['usergroup'], $ou['displaygroup']);
    $link = build_profile_link($name, $ou['uid']);
    $ope_online_now .= '<span class="ope-ou">'.$link.'</span>';
    $ope_online_now_count++;
}

$day_cut = TIME_NOW - 86400;
$q_day = $db->query("
    SELECT uid, username, usergroup, displaygroup
    FROM ".TABLE_PREFIX."users
    WHERE lastactive > {$day_cut}
    ORDER BY lastactive DESC
    LIMIT 80
");
while ($du = $db->fetch_array($q_day)) {
    $name = format_name(htmlspecialchars_uni($du['username']), $du['usergroup'], $du['displaygroup']);
    $link = build_profile_link($name, $du['uid']);
    $ope_online_24h .= '<span class="ope-ou">'.$link.'</span>';
    $ope_online_24h_count++;
}

// I-Forge: Staff list (portraits with role + heat ring)
$ope_staff_list = '';
$staffQuery = $db->query("
    SELECT u.uid, u.username, u.usergroup, u.displaygroup, g.title AS grouptitle, g.issupermod, g.cancp
    FROM ".TABLE_PREFIX."users u
    LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid = u.usergroup)
    WHERE u.usergroup IN (SELECT gid FROM ".TABLE_PREFIX."usergroups WHERE issupermod = 1 OR cancp = 1)
       OR u.additionalgroups LIKE '%4%'
    ORDER BY g.cancp DESC, g.issupermod DESC, u.username ASC
    LIMIT 8
");
while ($staff = $db->fetch_array($staffQuery)) {
    if ((int)$staff['cancp'] === 1) {
        $role = 'Administración'; $ring = 'var(--ember)';
    } elseif ((int)$staff['issupermod'] === 1) {
        $role = 'Moderación'; $ring = 'var(--h6)';
    } else {
        $role = $staff['grouptitle'] !== '' ? $staff['grouptitle'] : 'Narración'; $ring = 'var(--patina-hi)';
    }
    $uname = htmlspecialchars_uni($staff['username']);
    $initial = htmlspecialchars_uni(my_strtoupper(my_substr($staff['username'], 0, 1)));
    $ope_staff_list .= '
    <a href="'.$mybb->settings['bburl'].'/member.php?action=profile&amp;uid='.$staff['uid'].'" class="ope-staff-p" title="'.htmlspecialchars_uni($role).'">
        <span class="ope-staff-av" style="--ring:'.$ring.'">'.$initial.'</span>
        <span class="ope-staff-meta"><span class="ope-staff-n">'.$uname.'</span><span class="ope-staff-r">'.htmlspecialchars_uni($role).'</span></span>
    </a>';
}

// I-Forge: Categorías con dos estilos.
//   · "El Mundo" (o cualquier categoría cuyos foros tengan subforos/islas) => tarjetas-región con foto.
//   · Resto ("Off Topic", etc.) => lista de foros estilo placa (concrete slab).
$bburl = $mybb->settings['bburl'];
$ope_categories = '';
$catQuery = $db->query("
    SELECT fid, name, description
    FROM ".TABLE_PREFIX."forums
    WHERE type = 'c' AND active = 1
    ORDER BY disporder ASC
");
while ($cat = $db->fetch_array($catQuery)) {
    $catName = htmlspecialchars_uni($cat['name']);

    // Recolectar foros de la categoría + detectar si alguno tiene islas (subforos)
    $forumQuery = $db->query("
        SELECT f.fid, f.name, f.description, f.threads, f.posts, f.lastpost, f.lastpostsubject, f.lastposter, f.lastposteruid,
               f.ope_lastpid, rp.nombre AS lastchar_name,
               t.tid AS lastpost_tid
        FROM ".TABLE_PREFIX."forums f
        LEFT JOIN ".TABLE_PREFIX."threads t ON (t.fid = f.fid AND t.lastpost = f.lastpost)
        LEFT JOIN ".TABLE_PREFIX."rol_personajes rp ON (rp.pid = f.ope_lastpid)
        WHERE f.type = 'f' AND f.pid = '{$cat['fid']}' AND f.active = 1
        ORDER BY f.disporder ASC
    ");
    $catForums = [];
    $catHasIslas = false;
    while ($forum = $db->fetch_array($forumQuery)) {
        if (isset($forumpermissions[$forum['fid']]) && $forumpermissions[$forum['fid']]['canview'] != 1) {
            continue;
        }
        $islas = (int)$db->fetch_field($db->simple_select(
            'forums', 'COUNT(*) AS c',
            "type = 'f' AND pid = '{$forum['fid']}' AND active = 1"
        ), 'c');
        if ($islas > 0) { $catHasIslas = true; }
        $catForums[] = $forum;
    }
    if (empty($catForums)) { continue; }

    $isWorld = (mb_stripos($cat['name'], 'mundo') !== false) || $catHasIslas;

    if ($isWorld) {
        // ---- Estilo REGIÓN: tarjetas grandes con foto que llevan a las islas ----
        $catDesc = trim($cat['description']) !== '' ? htmlspecialchars_uni($cat['description']) : 'regiones &middot; navega para ver las islas';
        $cards = ope_render_region_cards($cat['fid'], $forumpermissions);
        $ope_categories .= '
        <section class="ope-block-cat" id="cat_'.$cat['fid'].'">
            <div class="ope-shead"><h2>'.$catName.'</h2><span class="ope-shead-code">// '.$catDesc.'</span><span class="ope-shead-rule"></span></div>
            <div class="ope-regions ope-world-bento">
                '.$cards.'
            </div>
        </section>';
    } else {
        // ---- Estilo LISTA (Off Topic): filas de foro sobre placa de hormigón ----
        $catDesc = trim($cat['description']) !== '' ? htmlspecialchars_uni($cat['description']) : 'charla libre &middot; fuera de rol';
        $rows = '';
        foreach ($catForums as $forum) {
            $forumName = htmlspecialchars_uni($forum['name']);
            $forumDesc = htmlspecialchars_uni($forum['description']);
            $threads = my_number_format($forum['threads']);
            $posts = my_number_format($forum['posts']);
            $initial = htmlspecialchars_uni(my_strtoupper(my_substr($forum['name'], 0, 1)));
            if ($forum['lastpost'] != 0 && trim($forum['lastposter']) != '') {
                $lastAuthor = trim((string) ($forum['lastchar_name'] ?? '')) !== ''
                    ? $forum['lastchar_name']
                    : $forum['lastposter'];
                $lastMeta = '<b>'.htmlspecialchars_uni($forum['lastpostsubject']).'</b> &middot; '.htmlspecialchars_uni($lastAuthor).' &middot; '.ope_reltime($forum['lastpost']);
            } else {
                $lastMeta = 'Sin mensajes a&uacute;n';
            }
            $descHtml = $forumDesc !== '' ? '<div class="ope-forum-d">'.$forumDesc.'</div>' : '';
            $rows .= '
            <a href="'.$bburl.'/forumdisplay.php?fid='.$forum['fid'].'" class="ope-forum">
                <div class="ope-forum-ic"><span>'.$initial.'</span></div>
                <div>
                    <div class="ope-forum-n">'.$forumName.' <span class="ope-forum-fid">FID-'.$forum['fid'].'</span></div>
                    '.$descHtml.'
                    <div class="ope-forum-last">'.$lastMeta.'</div>
                </div>
                <div class="ope-forum-stat"><b>'.$threads.'</b><i>temas</i> '.$posts.' msgs</div>
            </a>';
        }
        $ope_categories .= '
        <section class="ope-block-cat" id="cat_'.$cat['fid'].'">
            <div class="ope-shead"><h2>'.$catName.'</h2><span class="ope-shead-code">// '.$catDesc.'</span><span class="ope-shead-rule"></span></div>
            <div class="ope-slab">
                '.$rows.'
            </div>
        </section>';
    }
}
$forums = $ope_categories;

// I-Forge: último personaje creado (para el censo del pie de portada)
$ope_last_char = '';
if ($db->table_exists('rol_personajes')) {
    $lcq = $db->simple_select('rol_personajes', 'pid, nombre', "estado = 'aprobado'", array('order_by' => 'pid', 'order_dir' => 'DESC', 'limit' => 1));
    if ($db->num_rows($lcq)) {
        $lcRow = $db->fetch_array($lcq);
        $ope_last_char = '<a href="'.$mybb->settings['bburl'].'/ficha.php?pid='.(int)$lcRow['pid'].'">'.htmlspecialchars_uni($lcRow['nombre']).'</a>';
    }
}
if ($ope_last_char === '') {
    $laststats = $cache->read('stats');
    if (!empty($laststats['lastusername'])) {
        $ope_last_char = build_profile_link($laststats['lastusername'], $laststats['lastuid']);
    } else {
        $ope_last_char = 'Nadie a&uacute;n';
    }
}

eval('$index = "'.$templates->get('index').'";');
output_page($index);
