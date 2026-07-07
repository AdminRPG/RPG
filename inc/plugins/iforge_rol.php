<?php
/**
 * I-Forge · Rol (plugin de integración)
 * -------------------------------------
 * Expone a las plantillas MyBB el nivel de staff y el personaje activo del
 * sistema de rol, leídos de mybb_rol_cuentas / mybb_rol_personajes.
 *
 * - $mybb->user['iforge_staff_level']  (int 0..3, acumulativo)
 * - $mybb->user['iforge_active_pid']   (int pid del personaje activo)
 * - $iforge_nav_staff  (string global): <a> "Zona Staff" solo si level >= 1,
 *   listo para insertar en la navbar con {$iforge_nav_staff}. Vacío para
 *   invitados o cuando no hay permisos.
 *
 * Fail-safe: si las tablas no existen o es invitado, no rompe nada y deja
 * los valores en 0 / cadena vacía.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$plugins->add_hook('global_start', 'iforge_rol_global');

// Posteo por personaje: estampa el pid del personaje activo en cada
// mensaje/hilo y propaga el "último posteo" a hilos y foros.
$plugins->add_hook('datahandler_post_insert_thread', 'iforge_rol_stamp_thread');
$plugins->add_hook('datahandler_post_insert_thread_post', 'iforge_rol_stamp_thread_post');
$plugins->add_hook('datahandler_post_insert_post', 'iforge_rol_stamp_post');
$plugins->add_hook('datahandler_post_insert_thread_end', 'iforge_rol_after_thread');
$plugins->add_hook('datahandler_post_insert_post_end', 'iforge_rol_after_post');

// Muestra el personaje (no la cuenta) como autor visible del mensaje.
$plugins->add_hook('postbit', 'iforge_rol_postbit');

// Lista de hilos: autor del hilo y último posteo mostrados como personaje.
$plugins->add_hook('forumdisplay_thread_end', 'iforge_rol_forumdisplay_thread');

// Navbar única: se inyecta automáticamente en CUALQUIER página que use el
// pipeline estándar de MyBB (output_page) y que todavía no la traiga incluida
// en su propia plantilla. Así queda "estandarizada" en todas las zonas
// (foro, usercp, member.php, búsqueda, etc.) sin tocar decenas de plantillas.
$plugins->add_hook('pre_output_page', 'iforge_rol_inject_navbar');

function iforge_rol_info()
{
    return array(
        'name'          => 'I-Forge Rol',
        'description'   => 'Expone el nivel de staff y el personaje activo del sistema de rol a las plantillas (navbar Zona Staff).',
        'website'       => 'http://localhost/iforge',
        'author'        => 'I-Forge',
        'authorsite'    => 'http://localhost/iforge',
        'version'       => '1.0.0',
        'codename'      => 'iforge_rol',
        'compatibility' => '18*',
    );
}

/**
 * La instalación real del esquema la hace scripts/migrate-rol-tables.php.
 * Aquí sólo declaramos las funciones que MyBB espera para gestionar el plugin.
 */
function iforge_rol_install()
{
    // Sin cambios de esquema: las tablas mybb_rol_* se crean con la migración.
}

function iforge_rol_is_installed()
{
    global $db;
    return $db->table_exists('rol_cuentas');
}

function iforge_rol_uninstall()
{
    // No eliminamos tablas: preservamos los datos de personajes.
}

function iforge_rol_activate()
{
    // Nada extra que hacer al activar.
}

function iforge_rol_deactivate()
{
    // Nada extra que hacer al desactivar.
}

/**
 * Calcula y expone el nivel de staff y el personaje activo del usuario actual.
 */
function iforge_rol_global()
{
    global $mybb, $db, $iforge_nav_staff, $iforge_active_pid, $iforge_active_nombre;

    // Valores por defecto seguros (invitados incluidos).
    $iforge_nav_staff     = '';
    $iforge_active_pid    = 0;
    $iforge_active_nombre = '';
    $mybb->user['iforge_staff_level']   = 0;
    $mybb->user['iforge_active_pid']    = 0;
    $mybb->user['iforge_active_nombre'] = '';
    // Nombre a mostrar en la navbar: personaje activo o, en su defecto, la cuenta.
    $mybb->user['iforge_display_name']  = (string) ($mybb->user['username'] ?? '');

    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return;
    }

    // Fail-safe: si aún no se ha corrido la migración, no hacemos nada.
    if (!$db->table_exists('rol_cuentas')) {
        return;
    }

    $staff_level = 0;
    $activo      = 0;

    $query = $db->simple_select(
        'rol_cuentas',
        'staff_level, personaje_activo',
        "uid = {$uid}",
        array('limit' => 1)
    );
    if ($db->num_rows($query)) {
        $row         = $db->fetch_array($query);
        $staff_level = (int) $row['staff_level'];
        $activo      = (int) $row['personaje_activo'];
    }

    $mybb->user['iforge_staff_level'] = $staff_level;
    $mybb->user['iforge_active_pid']  = $activo;
    $iforge_active_pid                = $activo;

    // Nombre del personaje activo (si existe y pertenece al usuario).
    if ($activo > 0 && $db->table_exists('rol_personajes')) {
        $pq = $db->simple_select(
            'rol_personajes',
            'nombre',
            "pid = {$activo} AND uid = {$uid}",
            array('limit' => 1)
        );
        if ($db->num_rows($pq)) {
            $iforge_active_nombre               = (string) $db->fetch_field($pq, 'nombre');
            $mybb->user['iforge_active_nombre'] = $iforge_active_nombre;
            if ($iforge_active_nombre !== '') {
                $mybb->user['iforge_display_name'] = $iforge_active_nombre;
            }
        }
    }

    // Enlace "Zona Staff" para la navbar: sólo si el nivel es narrador (1) o superior.
    if ($staff_level >= 1) {
        $bburl            = htmlspecialchars_uni($mybb->settings['bburl']);
        $iforge_nav_staff = '<a href="' . $bburl . '/zona-staff.php" class="iforge-nav-link">Zona Staff</a>';
    }
}

// ─────────────────────────────────────────────────────────────
// Navbar única del sitio (fuente de verdad para TODAS las zonas).
// La construyen tanto el hook pre_output_page (páginas MyBB estándar:
// index, forumdisplay, showthread, usercp, member.php, búsqueda, MP...)
// como las páginas propias en PHP puro (personajes.php, ficha.php,
// tramites.php, guias.php, zona-staff.php, crear-personaje.php), que la
// invocan directamente con echo iforge_rol_navbar_html().
// ─────────────────────────────────────────────────────────────
function iforge_rol_navbar_html()
{
    global $mybb;
    static $html = null;

    if ($html !== null) {
        return $html;
    }

    $bburl       = htmlspecialchars_uni((string) $mybb->settings['bburl']);
    $uid         = (int) ($mybb->user['uid'] ?? 0);
    $loggedin    = $uid > 0;
    $staffLevel  = (int) ($mybb->user['iforge_staff_level'] ?? 0);
    $username    = htmlspecialchars_uni((string) ($mybb->user['username'] ?? ''));
    $displayName = htmlspecialchars_uni((string) ($mybb->user['iforge_display_name'] ?? $username));
    $script      = defined('THIS_SCRIPT') ? THIS_SCRIPT : '';

    $isOn = function (array $scripts) use ($script) {
        return in_array($script, $scripts, true) ? ' on' : '';
    };

    $links   = '<a href="' . $bburl . '/personajes.php" class="iforge-nav-link' . $isOn(array('personajes.php', 'ficha.php', 'crear-personaje.php')) . '">Personaje</a>';
    $links  .= '<a href="' . $bburl . '/tramites.php" class="iforge-nav-link' . $isOn(array('tramites.php')) . '">Tr&aacute;mites</a>';
    $links  .= '<a href="' . $bburl . '/guias.php" class="iforge-nav-link' . $isOn(array('guias.php')) . '">Gu&iacute;as</a>';
    if ($staffLevel >= 1) {
        $links .= '<a href="' . $bburl . '/zona-staff.php" class="iforge-nav-link' . $isOn(array('zona-staff.php')) . '">Zona Staff</a>';
    }

    if ($loggedin) {
        $logoutkey = htmlspecialchars_uni((string) ($mybb->user['logoutkey'] ?? ''));
        $right  = '<div class="iforge-user-menu">';
        $right .= '<button type="button" class="iforge-user-name" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-haspopup="true" aria-label="Men&uacute; de cuenta" title="' . $username . '">' . $displayName . '</button>';
        $right .= '<div class="iforge-dropdown">';
        $right .= '<a href="' . $bburl . '/personajes.php" class="iforge-dropdown-item">Mis personajes</a>';
        $right .= '<a href="' . $bburl . '/usercp.php" class="iforge-dropdown-item">Panel</a>';
        $right .= '<a href="' . $bburl . '/member.php?action=profile&amp;uid=' . $uid . '" class="iforge-dropdown-item">Perfil</a>';
        $right .= '<hr class="iforge-dropdown-divider">';
        $right .= '<a href="' . $bburl . '/member.php?action=logout&amp;logoutkey=' . $logoutkey . '" class="iforge-dropdown-item">Salir</a>';
        $right .= '</div></div>';
    } else {
        $right  = '<a href="' . $bburl . '/member.php?action=register" class="iforge-nav-cta">Forjarse</a>';
        $right .= '<a href="' . $bburl . '/member.php?action=login" class="iforge-btn-ghost iforge-btn-sm">Acceder</a>';
    }

    $html  = '<!-- ===== NAVBAR (fixed, iron-edge) ===== -->' . "\n";
    $html .= '<nav id="iforge-navbar"><div class="iforge-nav">';
    $html .= '<a href="' . $bburl . '/index.php" class="iforge-nav-logo">I-Forge</a>';
    $html .= '<div class="iforge-nav-links">' . $links . '</div>';
    $html .= '<div class="iforge-nav-right">' . $right . '</div>';
    $html .= '</div></nav>';

    return $html;
}

/**
 * Inyecta la navbar única justo tras <body> en cualquier página que pase por
 * el pipeline estándar de MyBB (output_page) y que aún no la traiga en su
 * propia plantilla. Evita duplicados comprobando el id del nav.
 */
function iforge_rol_inject_navbar($contents)
{
    if (defined('IN_ADMINCP') || !is_string($contents) || $contents === '') {
        return $contents;
    }
    if (stripos($contents, 'id="iforge-navbar"') !== false) {
        return $contents;
    }
    if (stripos($contents, '<body') === false) {
        return $contents;
    }

    // Páginas "de fábrica" de MyBB (usercp, member.php, search.php, etc.):
    // además de la navbar, envolvemos el contenido en un contenedor con el
    // ancho/paddings del tema para que no quede pegado a los bordes.
    $navbar = iforge_rol_navbar_html();
    $new    = preg_replace('/(<body[^>]*>)/i', '$1' . "\n" . $navbar . '<div id="iforge-stock-wrap">', $contents, 1);
    if ($new === null) {
        return $contents;
    }
    $new2 = preg_replace('/(<\/body>)/i', '</div>$1', $new, 1);

    return $new2 !== null ? $new2 : $new;
}

// ─────────────────────────────────────────────────────────────
// Helpers: personaje activo por cuenta y ficha resumida por pid
// (con caché estática para no repetir consultas dentro de la misma página).
// ─────────────────────────────────────────────────────────────

/** pid del personaje activo (aprobado y propio) de una cuenta, o 0. */
function iforge_rol_active_pid_for($uid)
{
    global $db;
    static $cache = array();

    $uid = (int) $uid;
    if ($uid < 1) {
        return 0;
    }
    if (isset($cache[$uid])) {
        return $cache[$uid];
    }

    $pid = 0;
    if ($db->table_exists('rol_cuentas')) {
        $q = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $pid = (int) $db->fetch_field($q, 'personaje_activo');
        }
    }

    // Sólo cuenta si sigue siendo un personaje aprobado y propiedad de la cuenta.
    if ($pid > 0 && $db->table_exists('rol_personajes')) {
        $vq = $db->simple_select('rol_personajes', 'pid', "pid = {$pid} AND uid = {$uid} AND estado = 'aprobado'", array('limit' => 1));
        if (!$db->num_rows($vq)) {
            $pid = 0;
        }
    }

    $cache[$uid] = $pid;
    return $pid;
}

/** Ficha resumida (pid, uid, nombre, slug, rango, nivel, avatar) o null. */
function iforge_rol_char($pid)
{
    global $db;
    static $cache = array();

    $pid = (int) $pid;
    if ($pid < 1) {
        return null;
    }
    if (array_key_exists($pid, $cache)) {
        return $cache[$pid];
    }

    $row = null;
    if ($db->table_exists('rol_personajes')) {
        $q = $db->simple_select('rol_personajes', 'pid, uid, nombre, slug, rango, nivel, avatar, estado', "pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $row = $db->fetch_array($q);
        }
    }

    $cache[$pid] = $row;
    return $row;
}

/** Enlace HTML al expediente del personaje (nombre enlazado a ficha.php). */
function iforge_rol_char_link($pid, $fallback_name = '')
{
    global $mybb;
    $char = iforge_rol_char($pid);
    if (!$char) {
        return $fallback_name !== '' ? htmlspecialchars_uni($fallback_name) : '';
    }
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    return '<a href="' . $bburl . '/ficha.php?pid=' . (int) $char['pid'] . '" class="iforge-char-link">' . htmlspecialchars_uni($char['nombre']) . '</a>';
}

// ─────────────────────────────────────────────────────────────
// Estampado del personaje activo al crear hilos y mensajes.
// ─────────────────────────────────────────────────────────────

function iforge_rol_stamp_thread(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->thread_insert_data['iforge_pid'] = iforge_rol_active_pid_for($uid);
    return $dh;
}

function iforge_rol_stamp_thread_post(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->post_insert_data['iforge_pid'] = iforge_rol_active_pid_for($uid);
    return $dh;
}

function iforge_rol_stamp_post(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->post_insert_data['iforge_pid'] = iforge_rol_active_pid_for($uid);
    return $dh;
}

/** Tras crear un hilo: el personaje del primer mensaje es el "último" de hilo y foro. */
function iforge_rol_after_thread(&$dh)
{
    global $db;
    $uid = (int) ($dh->data['uid'] ?? 0);
    $pid = iforge_rol_active_pid_for($uid);
    $tid = (int) ($dh->tid ?? 0);
    $fid = (int) ($dh->data['fid'] ?? 0);
    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);

    if ($tid > 0) {
        $db->update_query('threads', array('iforge_lastpid' => $pid), "tid = {$tid}");
    }
    if ($fid > 0 && $visible === 1) {
        $db->update_query('forums', array('iforge_lastpid' => $pid), "fid = {$fid}");
    }
    return $dh;
}

/** Tras crear un mensaje: si es visible, pasa a ser el "último" de hilo y foro. */
function iforge_rol_after_post(&$dh)
{
    global $db;
    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);
    if ($visible !== 1) {
        return $dh;
    }
    $uid = (int) ($dh->data['uid'] ?? 0);
    $pid = iforge_rol_active_pid_for($uid);
    $tid = (int) ($dh->data['tid'] ?? ($dh->post_insert_data['tid'] ?? 0));
    $fid = (int) ($dh->data['fid'] ?? ($dh->post_insert_data['fid'] ?? 0));

    if ($tid > 0) {
        $db->update_query('threads', array('iforge_lastpid' => $pid), "tid = {$tid}");
    }
    if ($fid > 0) {
        $db->update_query('forums', array('iforge_lastpid' => $pid), "fid = {$fid}");
    }
    return $dh;
}

// ─────────────────────────────────────────────────────────────
// Postbit: el autor visible del mensaje es el personaje, no la cuenta.
// ─────────────────────────────────────────────────────────────
function iforge_rol_postbit($post)
{
    global $mybb;

    if (empty($post['iforge_pid'])) {
        return $post;
    }
    $char = iforge_rol_char((int) $post['iforge_pid']);
    if (!$char) {
        return $post;
    }

    $bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
    $nombre   = htmlspecialchars_uni($char['nombre']);
    $fichaurl = $bburl . '/ficha.php?pid=' . (int) $char['pid'];

    // Nombre del personaje enlazado a su expediente.
    $post['profilelink']       = '<a href="' . $fichaurl . '" class="iforge-char-link">' . $nombre . '</a>';
    $post['profilelink_plain'] = $fichaurl;

    // Rango y nivel como subtítulo bajo el nombre.
    $post['usertitle'] = 'Rango ' . htmlspecialchars_uni($char['rango']) . ' &middot; Nivel ' . (int) $char['nivel'];

    // Bloque de avatar: usa el retrato del personaje si lo tiene, y muestra el
    // nombre del personaje como etiqueta (no la cuenta). Mantiene el contenedor
    // .iforge-avatar del tema para conservar el estilo.
    $img_src = trim((string) $char['avatar']) !== '' ? $char['avatar'] : (string) ($post['avatar'] ?? '');
    $post['useravatar'] = '<div class="iforge-avatar"><a href="' . $fichaurl . '"><img src="' . htmlspecialchars_uni($img_src) . '" alt="' . $nombre . '" onerror="this.remove()" /></a><span>' . $nombre . '</span></div>';

    return $post;
}

// ─────────────────────────────────────────────────────────────
// forumdisplay: el autor del hilo y el último posteo son el personaje.
// ─────────────────────────────────────────────────────────────
function iforge_rol_forumdisplay_thread()
{
    global $thread, $lastposterlink;

    if (!is_array($thread)) {
        return;
    }
    if (!empty($thread['iforge_pid'])) {
        $link = iforge_rol_char_link((int) $thread['iforge_pid']);
        if ($link !== '') {
            $thread['profilelink'] = $link;
        }
    }
    if (!empty($thread['iforge_lastpid'])) {
        $link = iforge_rol_char_link((int) $thread['iforge_lastpid']);
        if ($link !== '') {
            $lastposterlink = $link;
        }
    }
}
