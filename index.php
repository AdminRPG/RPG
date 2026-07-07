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
$iforge_register_link = '';
if ($mybb->user['uid'] == 0) {
    $iforge_register_link = '<a href="'.$mybb->settings['bburl'].'/member.php?action=register" class="iforge-nav-link" style="color:var(--color-accent)">Registrarse</a>';
}

// I-Forge navbar: Zona Privada link visibility
$iforge_zona_privada_link = '';
if ($mybb->usergroup['cancp'] == 1 || $mybb->usergroup['issupermod'] == 1) {
    $iforge_zona_privada_link = '<a href="'.$mybb->settings['bburl'].'/private.php" class="iforge-nav-link">Zona Privada</a>';
}

// I-Forge navbar: User menu
if ($mybb->user['uid']) {
    $iforge_user_menu = '
    <div class="iforge-user-menu">
      <button class="iforge-user-btn" id="iforge-user-btn">
        <img src="'.$mybb->settings['bburl'].'/images/nav-icon.svg" width="28" height="28" alt="Personaje">
      </button>
      <div class="iforge-dropdown" id="iforge-dropdown">
        <a href="'.$mybb->settings['bburl'].'/mensajes.php" class="iforge-dropdown-item">Mensajería</a>
        <a href="'.$mybb->settings['bburl'].'/configuracion.php" class="iforge-dropdown-item">Configuración</a>
        <hr class="iforge-dropdown-divider">
        <a href="'.$mybb->settings['bburl'].'/member.php?action=logout&amp;logoutkey='.$mybb->user['logoutkey'].'" class="iforge-dropdown-item">Cerrar sesión</a>
      </div>
    </div>';
} else {
    $iforge_user_menu = '
    <a href="'.$mybb->settings['bburl'].'/member.php?action=login" class="iforge-nav-link">Iniciar sesión</a>
    '.$iforge_register_link;
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

// I-Forge: Latest posts
$iforge_latest_posts = '';
$q = $db->query("
    SELECT p.pid, p.subject, p.tid, p.uid, p.dateline, u.username
    FROM ".TABLE_PREFIX."posts p
    LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid = p.uid)
    WHERE p.visible = 1
    ORDER BY p.dateline DESC
    LIMIT 5
");
while ($post = $db->fetch_array($q)) {
    $date = date('d/m', $post['dateline']);
    $iforge_latest_posts .= '
    <a href="'.$mybb->settings['bburl'].'/showthread.php?tid='.$post['tid'].'&pid='.$post['pid'].'" class="iforge-card-item">
        '.htmlspecialchars_uni($post['subject']).'
        <div class="iforge-card-item-meta">'.$post['username'].' · '.$date.'</div>
    </a>';
}

// I-Forge: Active searches (static placeholder)
$iforge_active_searches = '';

// I-Forge: News (static placeholder)
$iforge_news = '';

// I-Forge: Curiosidades
$curiosidades = [];
$iforge_curiosidad = '';
$curiosidades_json = '[]';

// I-Forge: Staff list
$iforge_staff_list = '';
$staffQuery = $db->query("
    SELECT u.uid, u.username, u.usergroup, g.title AS grouptitle
    FROM ".TABLE_PREFIX."users u
    LEFT JOIN ".TABLE_PREFIX."usergroups g ON (g.gid = u.usergroup)
    WHERE u.usergroup IN (SELECT gid FROM ".TABLE_PREFIX."usergroups WHERE issupermod = 1 OR cancp = 1)
       OR u.additionalgroups LIKE '%4%'
    LIMIT 10
");
$roleIcons = [
    'Administrator' => '<img src="'.$mybb->settings['bburl'].'/images/icons/seal.svg" class="icon" alt="">',
    'Super Moderators' => '<img src="'.$mybb->settings['bburl'].'/images/icons/shield.svg" class="icon" alt="">',
];
while ($staff = $db->fetch_array($staffQuery)) {
    $icon = $roleIcons[$staff['grouptitle']] ?? '<img src="'.$mybb->settings['bburl'].'/images/icons/users.svg" class="icon" alt="">';
    $iforge_staff_list .= '
    <div class="iforge-staff-item">
        '.$icon.'
        <span class="iforge-staff-name">'.htmlspecialchars_uni($staff['username']).'</span>
        <a href="'.$mybb->settings['bburl'].'/private.php?action=send&uid='.$staff['uid'].'" class="iforge-staff-mp">[MP]</a>
    </div>';
}

// I-Forge: Category sections with forum grids (One Piece Gaiden style)
$iforge_categories = '';
$catQuery = $db->query("
    SELECT fid, name, description
    FROM ".TABLE_PREFIX."forums
    WHERE type = 'c'
    ORDER BY disporder ASC
");
while ($cat = $db->fetch_array($catQuery)) {
    $catName = htmlspecialchars_uni($cat['name']);
    $catDesc = htmlspecialchars_uni($cat['description']);

    $forumQuery = $db->query("
        SELECT f.fid, f.name, f.description, f.threads, f.posts, f.lastpost, f.lastpostsubject, f.lastposter, f.lastposteruid,
               t.tid AS lastpost_tid
        FROM ".TABLE_PREFIX."forums f
        LEFT JOIN ".TABLE_PREFIX."threads t ON (t.fid = f.fid AND t.lastpost = f.lastpost)
        WHERE f.type = 'f' AND f.pid = '{$cat['fid']}' AND f.active = 1
        ORDER BY f.disporder ASC
    ");

    $forumsHtml = '';
    $hasForums = false;
    while ($forum = $db->fetch_array($forumQuery)) {
        if (isset($forumpermissions[$forum['fid']]) && $forumpermissions[$forum['fid']]['canview'] != 1) {
            continue;
        }
        $hasForums = true;

        $forumName = htmlspecialchars_uni($forum['name']);
        $forumDesc = htmlspecialchars_uni($forum['description']);
        $threads = my_number_format($forum['threads']);
        $posts = my_number_format($forum['posts']);

        $lastPostHtml = '';
        if ($forum['lastpost'] != 0 && trim($forum['lastposter']) != '') {
            $lastDate = date('d/m/Y', $forum['lastpost']);
            $lastSubject = htmlspecialchars_uni($forum['lastpostsubject']);
            $lastPoster = htmlspecialchars_uni($forum['lastposter']);
            $lastPostUrl = !empty($forum['lastpost_tid'])
                ? $mybb->settings['bburl'].'/showthread.php?tid='.(int)$forum['lastpost_tid']
                : $mybb->settings['bburl'].'/forumdisplay.php?fid='.$forum['fid'];
            $lastPostHtml = '<div class="iforge-forum-last"><span class="iforge-forum-last-label">Último:</span> <a href="'.$lastPostUrl.'">'.$lastSubject.'</a> por '.$lastPoster.' · '.$lastDate.'</div>';
        } elseif ($forum['lastpost'] == 0) {
            $lastPostHtml = '<div class="iforge-forum-last">Sin posts aún</div>';
        }

        $forumsHtml .= '
        <div class="iforge-forum-card">
            <div class="iforge-forum-card-body">
                <a href="'.$mybb->settings['bburl'].'/forumdisplay.php?fid='.$forum['fid'].'" class="iforge-forum-card-title-link"><h3 class="iforge-forum-card-title">'.$forumName.'</h3></a>
                <p class="iforge-forum-card-desc">'.$forumDesc.'</p>
                '.$lastPostHtml.'
            </div>
            <div class="iforge-forum-card-stats">
                <span class="iforge-forum-stat"><strong>'.$threads.'</strong> temas</span>
                <span class="iforge-forum-stat"><strong>'.$posts.'</strong> posts</span>
            </div>
        </div>';
    }

    if ($hasForums) {
        $iforge_categories .= '
        <section class="iforge-category-section">
            <header class="iforge-category-section-header">
                <h2 class="iforge-category-section-title">'.$catName.'</h2>
                '.($catDesc ? '<p class="iforge-category-section-desc">'.$catDesc.'</p>' : '').'
            </header>
            <div class="iforge-forums-grid">
                '.$forumsHtml.'
            </div>
        </section>';
    }
}
$forums = $iforge_categories;

eval('$index = "'.$templates->get('index').'";');
output_page($index);
