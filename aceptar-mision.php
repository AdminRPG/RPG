<?php
/**
 * I-Forge · Aceptar misión del Tablón
 * Registra qué personaje ha "cogido" una misión. Modalidad 'solo' guarda solo
 * al líder; 'grupo'/'cualquiera' permite elegir compañeros. La misión pasa a
 * estar "en proceso" y deja de aparecer como disponible en el tablón.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'aceptar-mision.php');
require_once './global.php';

$bburl = $mybb->settings['bburl'];
$uid   = (int) ($mybb->user['uid'] ?? 0);

function gbe_tm_back($qs = '')
{
    global $bburl;
    header('Location: ' . $bburl . '/tablon-misiones.php' . ($qs !== '' ? ('?' . $qs) : ''));
    exit;
}

if ($uid < 1 || !verify_post_check($mybb->get_input('my_post_key'), true)) {
    gbe_tm_back('e=sesion');
}

$mid = (int) $mybb->get_input('mision_id', MyBB::INPUT_INT);
if ($mid < 1) {
    gbe_tm_back();
}

// Personaje activo del jugador (con el que "coge" la misión).
$pid = 0;
if ($db->table_exists('rol_cuentas')) {
    $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($cq)) {
        $pid = (int) $db->fetch_field($cq, 'personaje_activo');
    }
}
if ($pid < 1) {
    gbe_tm_back('e=sin_personaje');
}

// La misión debe existir, estar en curso y no estar ya asignada.
$q = $db->simple_select('rol_mv_misiones', '*', 'mision_id = ' . $mid, array('limit' => 1));
$mision = $db->num_rows($q) ? $db->fetch_array($q) : null;
if (!$mision || $mision['estado'] !== 'en_curso') {
    gbe_tm_back('e=no_disponible');
}
if (gbe_rol_mv_mision_asignacion($mid)) {
    gbe_tm_back('e=ya_cogida');
}

$modalidad = (string) $mision['modalidad'];

// Compañeros (solo si no es 'solo'): pids de PJs aprobados, distintos del líder.
$companeros = array();
if ($modalidad !== 'solo') {
    $raw = $mybb->get_input('companeros');
    $ids = is_array($raw) ? $raw : array();
    foreach ($ids as $cid) {
        $cid = (int) $cid;
        if ($cid < 1 || $cid === $pid || in_array($cid, $companeros, true)) {
            continue;
        }
        $vq = $db->simple_select('rol_personajes', 'pid', "pid = {$cid} AND estado = 'aprobado' AND es_npc = 0", array('limit' => 1));
        if ($db->num_rows($vq)) {
            $companeros[] = $cid;
        }
    }
}

$db->insert_query('rol_mv_mision_asignaciones', array(
    'mision_id'  => $mid,
    'pid'        => $pid,
    'uid'        => $uid,
    'modalidad'  => $db->escape_string($modalidad === 'solo' ? 'solo' : 'grupo'),
    'companeros' => $db->escape_string(json_encode(array_values($companeros), JSON_UNESCAPED_UNICODE)),
    'estado'     => 'en_proceso',
    'dateline'   => (int) TIME_NOW,
));

gbe_tm_back('a=ok');
