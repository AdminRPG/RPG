<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'aceptar-mision.php');
require_once './global.php';

$uid = (int)($mybb->user['uid'] ?? 0);
if ($uid < 1 || !verify_post_check($mybb->get_input('my_post_key'))) {
    header('Location: ' . $mybb->settings['bburl'] . '/tablon-misiones.php');
    exit;
}

$mid = (int)($mybb->get_input('mision_id'));
if ($mid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/tablon-misiones.php');
    exit;
}

$q = $db->simple_select('rol_mv_misiones', '*', 'mision_id = ' . $mid, array('limit' => 1));
$mision = $db->fetch_array($q);
if (!$mision || $mision['estado'] !== 'en_curso') {
    header('Location: ' . $mybb->settings['bburl'] . '/tablon-misiones.php?e=no_available');
    exit;
}

// TODO: registrar aceptación en tabla dedicada cuando exista
header('Location: ' . $mybb->settings['bburl'] . '/tablon-misiones.php?a=ok');
exit;