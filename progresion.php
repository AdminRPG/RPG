<?php
/**
 * One Piece: Eternal · Progresión (fusionada con Gestión)
 *
 * La progresión (PP, compra de stats, PT) vive ahora dentro de la Gestión de la
 * ficha. Esta página solo resuelve el personaje y redirige a la ficha abriendo
 * la vista de Gestión en la sub-pestaña de Atributos.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'progresion.php');
require_once './global.php';

$bburl = $mybb->settings['bburl'];
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

$pid = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
if ($pid < 1) {
    $pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
}
if ($pid < 1 && function_exists('ope7_pj_activo')) {
    $act = ope7_pj_activo($uid);
    if ($act && $act['tabla'] === 'ope') {
        $pid = (int) $act['id'];
    }
}

if ($pid > 0) {
    header('Location: ' . $bburl . '/ficha.php?pid=' . $pid . '&g=1#g-atributos');
} else {
    header('Location: ' . $bburl . '/personajes.php');
}
exit;
