<?php
/**
 * One Piece: Eternal · Funciones compartidas de inicialización de usuario
 *
 * Centraliza el boilerplate de $staff_level, $initials y $display_name
 * que se repite en 15+ páginas de front-end.
 *
 * Uso:
 *   require_once MYBB_ROOT . 'inc/ope_user_init.php';
 *   $staff_level  = ope_get_staff_level($uid, $active_pid ?? 0);
 *   $initials     = ope_get_initials($mybb->user['username'] ?? '');
 *   $display_name = ope_get_display_name();
 */

/**
 * Devuelve el nivel de staff (0 = usuario normal, 1+ = staff).
 *
 * @param int $uid         ID del usuario MyBB.
 * @param int $active_pid  Si > 0, además se requiere que rol_personajes exista.
 * @return int
 */
function ope_get_staff_level($uid, $active_pid = 0) {
    global $db, $mybb;
    $loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
    if (!$loggedin) return 0;

    if (isset($mybb->user['ope_staff_level'])) {
        return (int) $mybb->user['ope_staff_level'];
    }
    // D6.3: fuente canónica mybb_ope_cuentas (rol_cuentas está retirada).
    if ($db->table_exists('ope_cuentas')) {
        $cq = $db->simple_select('ope_cuentas', 'staff_level', 'uid = ' . (int)$uid, array('limit' => 1));
        if ($db->num_rows($cq)) {
            return (int) $db->fetch_field($cq, 'staff_level');
        }
    }
    return 0;
}

/**
 * Calcula las iniciales a partir de un nombre (hasta 2 letras, mayúsculas).
 * Con guardas para mbstring.
 *
 * @param string $name  Nombre del usuario o personaje.
 * @return string       Iniciales (máx. 2 caracteres, mayúsculas).
 */
function ope_get_initials($name) {
    if (empty($name)) return '';
    $parts = preg_split('/\s+/', trim((string)$name));
    $initials = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    return $initials;
}

/**
 * Nombre a mostrar en la navbar: personaje activo o, en su defecto, la cuenta.
 *
 * @return string
 */
function ope_get_display_name() {
    global $mybb;
    return (string) ($mybb->user['ope_display_name'] ?? ($mybb->user['username'] ?? ''));
}
