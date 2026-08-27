<?php
/**
 * One Piece: 7 Seas · Capa de permisos (decisión D0.5)
 * -----------------------------------------------------------------------------
 * Base: mybb_rol_cuentas (staff_level 0-3, staff_rol por personaje, staff_narrador)
 * + bypass admin MyBB (uid=1 / usergroup 4). Envoltorios `ope7_*` para que el
 * motor 7 Seas nunca dependa del código viejo directamente.
 *
 * Roles:
 *   staff    → rank ≥ 1 (colaborador/moderador/administrador/webmaster) o bypass admin.
 *   narrador → staff_narrador = 1 en el personaje activo (narrador habilitado).
 *   jugador  → resto (logueado con personaje activo).
 *
 * Protección de campos solo-staff (secretos_json, veracidad de rumores,
 * npc_primario): cualquier consulta que devuelva esos campos debe pasar por
 * ope7_es_staff_o_narrador() y no servirlos al público.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Devuelve el contexto de permisos del uid actual (o de un uid dado). */
function ope7_permisos($uid = 0)
{
    global $mybb;
    $uid = (int) $uid;
    if ($uid < 1) {
        $uid = (int) ($mybb->user['uid'] ?? 0);
    }
    $out = array('uid' => $uid, 'rank' => 0, 'narrador' => 0, 'is_staff' => false, 'is_narrador' => false);

    if ($uid < 1) {
        return $out;
    }

    if (function_exists('ope_rol_active_staff')) {
        $st = ope_rol_active_staff($uid);
        $out['rank']      = (int) ($st['rank'] ?? 0);
        $out['narrador']  = (int) ($st['narrador'] ?? 0);
        $out['is_staff']  = (bool) ($st['is_staff'] ?? false);
        $out['is_narrador'] = $out['is_staff'] && (int) ($st['narrador'] ?? 0) === 1;
    }

    // Bypass admin MyBB (cuenta, no personaje).
    if (!$out['is_staff'] && function_exists('ope_rol_is_board_admin') && ope_rol_is_board_admin($uid)) {
        $out['rank']      = 4;
        $out['is_staff']  = true;
    }

    return $out;
}

/** ¿Es staff (personaje con rol o cuenta admin)? */
function ope7_es_staff($uid = 0)
{
    $p = ope7_permisos($uid);
    return $p['is_staff'];
}

/** ¿Es narrador habilitado (staff_narrador) o staff? */
function ope7_es_staff_o_narrador($uid = 0)
{
    $p = ope7_permisos($uid);
    return $p['is_staff'] || $p['is_narrador'];
}

/** ¿Es narrador habilitado (sin ser staff)? */
function ope7_es_narrador($uid = 0)
{
    $p = ope7_permisos($uid);
    return $p['is_narrador'];
}

/** ¿Tiene personaje activo (jugador válido)? */
function ope7_tiene_personaje($uid = 0)
{
    global $mybb;
    $uid = (int) $uid;
    if ($uid < 1) {
        $uid = (int) ($mybb->user['uid'] ?? 0);
    }
    if ($uid < 1) {
        return false;
    }
    return (int) ($mybb->user['ope_active_pid'] ?? 0) > 0;
}

/** Guard de página: redirige a login si no hay sesión, error si no es staff. */
function ope7_guard_pagina_staff()
{
    global $mybb;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
        exit;
    }
    if (!ope7_es_staff($uid)) {
        error('No tienes permisos para acceder a la zona staff.');
    }
}
