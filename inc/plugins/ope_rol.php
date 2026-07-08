<?php
/**
 * I-Forge · Rol (plugin de integración)
 * -------------------------------------
 * Expone a las plantillas MyBB el nivel de staff y el personaje activo del
 * sistema de rol, leídos de mybb_rol_cuentas / mybb_rol_personajes.
 *
 * - $mybb->user['ope_staff_level']  (int 0..3, acumulativo)
 * - $mybb->user['ope_active_pid']   (int pid del personaje activo)
 * - $ope_nav_staff  (string global): <a> "Zona Staff" solo si level >= 1,
 *   listo para insertar en la navbar con {$ope_nav_staff}. Vacío para
 *   invitados o cuando no hay permisos.
 *
 * Fail-safe: si las tablas no existen o es invitado, no rompe nada y deja
 * los valores en 0 / cadena vacía.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$plugins->add_hook('global_start', 'ope_rol_global');

// Posteo por personaje: estampa el pid del personaje activo en cada
// mensaje/hilo y propaga el "último posteo" a hilos y foros.
$plugins->add_hook('datahandler_post_insert_thread', 'ope_rol_stamp_thread');
$plugins->add_hook('datahandler_post_insert_thread_post', 'ope_rol_stamp_thread_post');
$plugins->add_hook('datahandler_post_insert_post', 'ope_rol_stamp_post');
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_after_thread');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_after_post');

// Restricción de posteo: un personaje EN REVISIÓN solo puede publicar en la
// zona Off Topic (crear tema o responder). Los aprobados, en cualquier foro.
$plugins->add_hook('newthread_do_newthread_start', 'ope_rol_guard_newthread');
$plugins->add_hook('newreply_do_newreply_start', 'ope_rol_guard_newreply');

// Época (pasado/presente) + etiqueta del tema para la línea de tiempo del rol.
$plugins->add_hook('newthread_do_newthread_end', 'ope_rol_save_thread_meta');
$plugins->add_hook('editpost_do_editpost_end', 'ope_rol_save_thread_meta_edit');

// Spoilers anidables [spoiler]/[spoiler=Título] en todo el foro (antes de nl2br).
$plugins->add_hook('parse_message', 'ope_rol_parse_spoilers');

// Insertador de plantillas de post del personaje activo en newthread/newreply.
$plugins->add_hook('newthread_end', 'ope_rol_tpl_inserter_newthread');
$plugins->add_hook('newreply_end', 'ope_rol_tpl_inserter_newreply');

// Muestra el personaje (no la cuenta) como autor visible del mensaje.
$plugins->add_hook('postbit', 'ope_rol_postbit');

// Lista de hilos: autor del hilo y último posteo mostrados como personaje.
$plugins->add_hook('forumdisplay_thread_end', 'ope_rol_forumdisplay_thread');

// Navbar única: se inyecta automáticamente en CUALQUIER página que use el
// pipeline estándar de MyBB (output_page) y que todavía no la traiga incluida
// en su propia plantilla. Así queda "estandarizada" en todas las zonas
// (foro, usercp, member.php, búsqueda, etc.) sin tocar decenas de plantillas.
$plugins->add_hook('pre_output_page', 'ope_rol_inject_navbar');

function ope_rol_info()
{
    return array(
        'name'          => 'I-Forge Rol',
        'description'   => 'Expone el nivel de staff y el personaje activo del sistema de rol a las plantillas (navbar Zona Staff).',
        'website'       => 'http://localhost/iforge',
        'author'        => 'I-Forge',
        'authorsite'    => 'http://localhost/iforge',
        'version'       => '1.0.0',
        'codename'      => 'ope_rol',
        'compatibility' => '18*',
    );
}

/**
 * La instalación real del esquema la hace scripts/migrate-rol-tables.php.
 * Aquí sólo declaramos las funciones que MyBB espera para gestionar el plugin.
 */
function ope_rol_install()
{
    // Sin cambios de esquema: las tablas mybb_rol_* se crean con la migración.
}

function ope_rol_is_installed()
{
    global $db;
    return $db->table_exists('rol_cuentas');
}

function ope_rol_uninstall()
{
    // No eliminamos tablas: preservamos los datos de personajes.
}

function ope_rol_activate()
{
    // Nada extra que hacer al activar.
}

function ope_rol_deactivate()
{
    // Nada extra que hacer al desactivar.
}

/**
 * Calcula y expone el nivel de staff y el personaje activo del usuario actual.
 */
/**
 * Rango numérico de un rol de staff jerárquico. El staff es POR PERSONAJE:
 * colaborador(1) < moderador(2) < administrador(3) < webmaster(4). Narrador NO
 * entra en esta escala (es un rol opcional e independiente). '' o desconocido => 0.
 */
function ope_rol_staff_rank($rol)
{
    switch ((string) $rol) {
        case 'colaborador':   return 1;
        case 'moderador':     return 2;
        case 'administrador': return 3;
        case 'webmaster':     return 4;
        default:              return 0;
    }
}

/** Etiqueta humana de un rol de staff. */
function ope_rol_staff_label($rol)
{
    switch ((string) $rol) {
        case 'colaborador':   return 'Colaborador';
        case 'moderador':     return 'Moderador';
        case 'administrador': return 'Administrador';
        case 'webmaster':     return 'Web Master';
        default:              return '';
    }
}

/**
 * Staff del PERSONAJE ACTIVO de una cuenta (no de la cuenta). Devuelve:
 *   ['pid','rol','narrador','rank','is_staff','nombre']
 * Si el personaje activo no es propio o no existe, todo queda a cero.
 */
function ope_rol_active_staff($uid)
{
    global $db;
    $out = array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
    $uid = (int) $uid;
    if ($uid < 1 || !$db->table_exists('rol_cuentas')) {
        return $out;
    }
    $q = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    $pid = $db->num_rows($q) ? (int) $db->fetch_field($q, 'personaje_activo') : 0;
    if ($pid < 1 || !$db->table_exists('rol_personajes')) {
        return $out;
    }
    $pq = $db->simple_select('rol_personajes', 'nombre, staff_rol, staff_narrador', "pid = {$pid} AND uid = {$uid}", array('limit' => 1));
    if (!$db->num_rows($pq)) {
        return $out;
    }
    $row = $db->fetch_array($pq);
    $out['pid']      = $pid;
    $out['nombre']   = (string) $row['nombre'];
    $out['rol']      = (string) $row['staff_rol'];
    $out['narrador'] = (int) $row['staff_narrador'];
    $out['rank']     = ope_rol_staff_rank($out['rol']);
    $out['is_staff'] = ($out['rank'] > 0 || $out['narrador'] === 1);
    return $out;
}

function ope_rol_global()
{
    global $mybb, $db, $ope_nav_staff, $ope_active_pid, $ope_active_nombre;

    // Valores por defecto seguros (invitados incluidos).
    $ope_nav_staff     = '';
    $ope_active_pid    = 0;
    $ope_active_nombre = '';
    $mybb->user['ope_staff_level']    = 0;   // = rank del personaje activo (compat)
    $mybb->user['ope_staff_rol']      = '';
    $mybb->user['ope_staff_narrador'] = 0;
    $mybb->user['ope_staff_rank']     = 0;
    $mybb->user['ope_is_staff']       = 0;
    $mybb->user['ope_active_pid']     = 0;
    $mybb->user['ope_active_nombre']  = '';
    // Nombre a mostrar en la navbar: personaje activo o, en su defecto, la cuenta.
    $mybb->user['ope_display_name']  = (string) ($mybb->user['username'] ?? '');

    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return;
    }

    // Fail-safe: si aún no se ha corrido la migración, no hacemos nada.
    if (!$db->table_exists('rol_cuentas')) {
        return;
    }

    $activo = 0;
    $query  = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($query)) {
        $activo = (int) $db->fetch_field($query, 'personaje_activo');
    }

    $mybb->user['ope_active_pid'] = $activo;
    $ope_active_pid               = $activo;

    // Datos del personaje activo: nombre + STAFF (el staff es por personaje, así
    // que si tienes activo un personaje sin rol, NO eres staff aunque otro de tus
    // personajes lo sea).
    if ($activo > 0 && $db->table_exists('rol_personajes')) {
        $pq = $db->simple_select(
            'rol_personajes',
            'nombre, staff_rol, staff_narrador',
            "pid = {$activo} AND uid = {$uid}",
            array('limit' => 1)
        );
        if ($db->num_rows($pq)) {
            $row = $db->fetch_array($pq);
            $ope_active_nombre               = (string) $row['nombre'];
            $mybb->user['ope_active_nombre'] = $ope_active_nombre;
            if ($ope_active_nombre !== '') {
                $mybb->user['ope_display_name'] = $ope_active_nombre;
            }

            $rank     = ope_rol_staff_rank((string) $row['staff_rol']);
            $narrador = (int) $row['staff_narrador'];
            $mybb->user['ope_staff_rol']      = (string) $row['staff_rol'];
            $mybb->user['ope_staff_narrador'] = $narrador;
            $mybb->user['ope_staff_rank']     = $rank;
            $mybb->user['ope_staff_level']    = $rank;
            $mybb->user['ope_is_staff']       = ($rank > 0 || $narrador === 1) ? 1 : 0;
        }
    }

    // Enlace "Zona Staff" en navbar: si el PERSONAJE ACTIVO es staff (rol o narrador).
    if (!empty($mybb->user['ope_is_staff'])) {
        $bburl         = htmlspecialchars_uni($mybb->settings['bburl']);
        $ope_nav_staff = '<a href="' . $bburl . '/zona-staff.php" class="ope-nav-link">Zona Staff</a>';
    }
}

// ─────────────────────────────────────────────────────────────
// Helpers: contadores de alertas y mensajes sin leer para la navbar.
// ─────────────────────────────────────────────────────────────

/**
 * Alertas sin leer del PERSONAJE ACTIVO (no de la cuenta). Las notificaciones
 * son por personaje: cada cuenta solo ve las alertas del personaje que tiene
 * activo en ese momento. Sin personaje activo → 0.
 */
function ope_rol_alertas_no_leidas(int $pid): int
{
    global $db;
    if ($pid <= 0 || !$db->table_exists('rol_alertas')) return 0;
    $q = $db->simple_select('rol_alertas', 'COUNT(*) as cnt', "pid = {$pid} AND leido = 0");
    return (int)$db->fetch_field($q, 'cnt');
}

function ope_rol_mensajes_no_leidos(int $pid): int
{
    global $db;
    if (!$db->table_exists('rol_mensajes') || $pid <= 0) return 0;
    $q = $db->query("
        SELECT COUNT(DISTINCT thread_id) as cnt
        FROM " . TABLE_PREFIX . "rol_mensajes
        WHERE destino_pid = {$pid} AND leido = 0
    ");
    return (int)$db->fetch_field($q, 'cnt');
}

// ─────────────────────────────────────────────────────────────
// Navbar única del sitio (fuente de verdad para TODAS las zonas).
// La construyen tanto el hook pre_output_page (páginas MyBB estándar:
// index, forumdisplay, showthread, usercp, member.php, búsqueda, MP...)
// como las páginas propias en PHP puro (personajes.php, ficha.php,
// tramites.php, guias.php, zona-staff.php, crear-personaje.php), que la
// invocan directamente con echo ope_rol_navbar_html().
// ─────────────────────────────────────────────────────────────
function ope_rol_navbar_html()
{
    global $mybb;
    static $html = null;

    if ($html !== null) {
        return $html;
    }

    $bburl       = htmlspecialchars_uni((string) $mybb->settings['bburl']);
    $uid         = (int) ($mybb->user['uid'] ?? 0);
    $loggedin    = $uid > 0;
    $activePid   = (int) ($mybb->user['ope_active_pid'] ?? 0);
    $staffLevel  = (int) ($mybb->user['ope_staff_level'] ?? 0);
    $isStaff     = !empty($mybb->user['ope_is_staff']);
    $username    = htmlspecialchars_uni((string) ($mybb->user['username'] ?? ''));
    $displayName = htmlspecialchars_uni((string) ($mybb->user['ope_display_name'] ?? $username));
    $script      = defined('THIS_SCRIPT') ? THIS_SCRIPT : '';

    $isOn = function (array $scripts) use ($script) {
        return in_array($script, $scripts, true) ? ' on' : '';
    };

    $links   = '<a href="' . $bburl . '/personajes.php" class="ope-nav-link' . $isOn(array('personajes.php', 'ficha.php', 'crear-personaje.php')) . '">Personaje</a>';
    $links  .= '<a href="' . $bburl . '/tramites.php" class="ope-nav-link' . $isOn(array('tramites.php')) . '">Tr&aacute;mites</a>';
    $links  .= '<a href="' . $bburl . '/guias.php" class="ope-nav-link' . $isOn(array('guias.php')) . '">Gu&iacute;as</a>';
    if ($isStaff) {
        $links .= '<a href="' . $bburl . '/zona-staff.php" class="ope-nav-link' . $isOn(array('zona-staff.php')) . '">Zona Staff</a>';
    }

    if ($loggedin) {
        $logoutkey = htmlspecialchars_uni((string) ($mybb->user['logoutkey'] ?? ''));

        // Campana de alertas (del personaje activo, no de la cuenta)
        $alertas_count = ope_rol_alertas_no_leidas($activePid);
        $right  = '<a href="' . $bburl . '/alertas.php" class="ope-nav-bell" title="Alertas">';
        $right .= '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
        if ($alertas_count > 0) {
            $right .= '<span class="ope-bell-badge">' . $alertas_count . '</span>';
        }
        $right .= '</a>';

        // Menú de usuario
        $right .= '<div class="ope-user-menu">';
        $right .= '<button type="button" class="ope-user-name" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-expanded="false">' . $displayName . '</button>';
        $right .= '<div class="ope-dropdown">';

        // Mensajes (solo si hay personaje activo)
        if ($activePid > 0) {
            $msgs_count = ope_rol_mensajes_no_leidos($activePid);
            $msg_label = 'Mensajes';
            if ($msgs_count > 0) $msg_label .= ' (' . $msgs_count . ')';
            $right .= '<a href="' . $bburl . '/mensajes.php" class="ope-dropdown-item">' . $msg_label . '</a>';
        }

        $right .= '<a href="' . $bburl . '/usercp.php" class="ope-dropdown-item">Panel</a>';
        $right .= '<a href="' . $bburl . '/member.php?action=profile&amp;uid=' . $uid . '" class="ope-dropdown-item">Perfil</a>';
        $right .= '<hr class="ope-dropdown-divider">';
        $right .= '<a href="' . $bburl . '/member.php?action=logout&amp;logoutkey=' . $logoutkey . '" class="ope-dropdown-item">Salir</a>';
        $right .= '</div></div>';
    } else {
        $right  = '<a href="' . $bburl . '/member.php?action=register" class="ope-nav-cta">Reg&iacute;strate</a>';
        $right .= '<a href="' . $bburl . '/member.php?action=login" class="ope-btn-ghost ope-btn-sm">Acceder</a>';
    }

    $html  = '<!-- ===== NAVBAR (fixed, iron-edge) · fuente única ===== -->' . "\n";
    $html .= ope_rol_navbar_css();
    $html .= '<nav id="ope-navbar"><div class="ope-nav">';
    $html .= '<a href="' . $bburl . '/index.php" class="ope-nav-logo">One Piece Eternal</a>';
    $html .= '<div class="ope-nav-links">' . $links . '</div>';
    $html .= '<div class="ope-nav-right">' . $right . '</div>';
    $html .= '</div></nav>';

    return $html;
}

/**
 * CSS canónico de la navbar. Se emite UNA sola vez junto al propio nav (viaja
 * con él), de modo que la barra se ve idéntica en TODAS las zonas sin depender
 * del stylesheet de cada página. Va scopeado bajo #ope-navbar para ganar por
 * especificidad a cualquier regla suelta (.ope-nav-*) que quede en páginas
 * antiguas. Fuente ÚNICA de verdad para el diseño de la navbar.
 */
function ope_rol_navbar_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;

    return '<style id="ope-navbar-css">'
        . 'body{padding-top:52px}'
        . '#ope-navbar{position:fixed;inset:0 0 auto 0;height:52px;z-index:1000;background:var(--iron-edge);border-bottom:2px solid #000;font-size:15px}'
        . '#ope-navbar *{box-sizing:border-box}'
        . '#ope-navbar .ope-nav{max-width:1300px;margin:0 auto;height:100%;padding:0 18px;display:flex;align-items:center;justify-content:space-between;gap:14px}'
        . '#ope-navbar .ope-nav-logo{font-family:var(--disp);font-weight:900;font-size:1.45rem;letter-spacing:1px;color:var(--paper);text-transform:uppercase;line-height:1;display:flex;align-items:center;gap:9px;text-decoration:none}'
        . '#ope-navbar .ope-nav-logo::before{content:"";width:11px;height:11px;background:var(--ember);box-shadow:0 0 10px var(--ember);flex:0 0 auto}'
        . '#ope-navbar .ope-nav-logo:hover{color:#fff}'
        . '#ope-navbar .ope-nav-links{display:flex;gap:2px}'
        . '#ope-navbar .ope-nav-link{font-family:var(--mono);font-size:.72rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;letter-spacing:1px;padding:7px 11px;border:1px solid transparent;text-decoration:none;line-height:1}'
        . '#ope-navbar .ope-nav-link:hover,#ope-navbar .ope-nav-link.on{color:var(--iron);background:var(--ember);border-color:#000}'
        . '#ope-navbar .ope-nav-right{display:flex;align-items:center;gap:10px}'
        . '#ope-navbar .ope-nav-cta{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--iron);background:var(--paper);padding:7px 12px;border:2px solid #000;text-decoration:none;transition:transform .12s,box-shadow .12s;line-height:1}'
        . '#ope-navbar .ope-nav-cta:hover{transform:translate(-1px,-1px);box-shadow:2px 2px 0 #000}'
        . '#ope-navbar .ope-user-menu{position:relative}'
        . '#ope-navbar .ope-user-name{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper);background:var(--iron-plate);border:2px solid #000;padding:7px 12px;cursor:pointer;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;line-height:1}'
        . '#ope-navbar .ope-user-name:hover{border-color:var(--ember)}'
        . '#ope-navbar .ope-dropdown{display:none;position:absolute;right:0;top:44px;background:var(--iron-plate);border:2px solid #000;min-width:200px;z-index:100}'
        . '#ope-navbar .ope-dropdown.open{display:block}'
        . '#ope-navbar .ope-dropdown-item{display:block;padding:10px 14px;font-family:var(--mono);font-size:.68rem;color:var(--paper-dim);border-bottom:1px solid var(--iron-edge);text-decoration:none;transition:background .12s,color .12s}'
        . '#ope-navbar .ope-dropdown-item:last-child{border-bottom:none}'
        . '#ope-navbar .ope-dropdown-item:hover{background:var(--iron-hi);color:var(--paper)}'
        . '#ope-navbar .ope-dropdown-divider{border:none;border-top:1px solid var(--iron-edge);margin:0}'
        . '#ope-navbar .ope-btn-ghost{font-family:var(--mono);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:7px 13px;border:2px solid var(--rivet);background:transparent;color:var(--paper);text-decoration:none;transition:transform .12s,box-shadow .12s;line-height:1}'
        . '#ope-navbar .ope-btn-ghost:hover{color:var(--iron);background:var(--paper);border-color:#000;transform:translate(-1px,-1px);box-shadow:2px 2px 0 #000}'
        . '#ope-navbar .ope-nav-bell{position:relative;display:flex;align-items:center;justify-content:center;padding:6px 8px;color:var(--paper-dim);transition:color .12s}'
        . '#ope-navbar .ope-nav-bell:hover{color:var(--ember-hi)}'
        . '#ope-navbar .ope-nav-bell svg{fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}'
        . '#ope-navbar .ope-bell-badge{position:absolute;top:0;right:2px;min-width:16px;height:16px;padding:0 4px;border-radius:8px;background:var(--crack);color:#fff;font-family:var(--mono);font-size:.58rem;font-weight:700;line-height:16px;text-align:center}'
        . '@media(max-width:640px){#ope-navbar .ope-nav-links{display:none}#ope-navbar .ope-nav-logo{font-size:1.2rem}}'
        . '</style>';
}

/**
 * Fuentes + hoja de estilo global del tema (docs/themes/ope.css desplegada).
 * ÚNICA fuente de verdad para :root, body/fondo, resets y utilidades base.
 * Las páginas PHP propias deben llamar esto en <head> y NO duplicar esas reglas.
 */
function ope_rol_head_base()
{
    static $html = null;
    if ($html !== null) {
        return $html;
    }

    global $mybb;
    $bburl     = rtrim((string) $mybb->settings['bburl'], '/');
    $css_file  = null;
    $css_mtime = time();

    foreach (glob(MYBB_ROOT . 'cache/themes/theme*/ope.css') ?: [] as $f) {
        $mt = @filemtime($f);
        if ($css_file === null || ($mt !== false && $mt >= $css_mtime)) {
            $css_file  = $f;
            $css_mtime = $mt !== false ? $mt : time();
        }
    }
    if ($css_file === null) {
        $fallback = MYBB_ROOT . 'docs/themes/ope.css';
        if (is_file($fallback)) {
            $css_file  = $fallback;
            $css_mtime = filemtime($fallback);
        }
    }

    $href = $css_file
        ? $bburl . '/' . str_replace('\\', '/', ltrim(str_replace(MYBB_ROOT, '', $css_file), '/\\'))
        : $bburl . '/cache/themes/theme13/ope.css';
    $href .= '?v=' . (int) $css_mtime;

    $html  = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    $html .= '<link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,400;0,700;1,400&amp;family=Big+Shoulders+Display:wght@600;700;800;900&amp;family=Space+Mono:wght@400;700&amp;display=swap" rel="stylesheet">' . "\n";
    $html .= '<link rel="stylesheet" href="' . htmlspecialchars_uni($href) . '">' . "\n";

    return $html;
}

/**
 * Inyecta la navbar única justo tras <body> en cualquier página que pase por
 * el pipeline estándar de MyBB (output_page) y que aún no la traiga en su
 * propia plantilla. Evita duplicados comprobando el id del nav.
 */
function ope_rol_inject_navbar($contents)
{
    if (defined('IN_ADMINCP') || !is_string($contents) || $contents === '') {
        return $contents;
    }
    if (stripos($contents, 'id="ope-navbar"') !== false) {
        return $contents;
    }
    if (stripos($contents, '<body') === false) {
        return $contents;
    }

    $navbar = ope_rol_navbar_html();
    $script = defined('THIS_SCRIPT') ? THIS_SCRIPT : '';
    $themejs = ope_rol_theme_js();

    // JS global del tema (toggle de spoilers, etc.) antes de </body>.
    if ($themejs !== '' && stripos($contents, 'id="ope-theme-js"') === false && stripos($contents, '</body>') !== false) {
        $contents = preg_replace('/(<\/body>)/i', $themejs . '$1', $contents, 1);
    }

    // Páginas con plantilla propia full-bleed (index, forumdisplay, showthread):
    // ya auto-limitan su contenido (.ope-wrap / secciones max-width:1300) y
    // comparten el footer full-bleed, así que NO deben envolverse en el
    // contenedor de páginas de fábrica (lo estrecharía y dejaría hueco bajo el
    // footer). Sólo se inyecta la navbar.
    $custom_layout = in_array($script, array('index.php', 'forumdisplay.php', 'showthread.php'), true);
    if ($custom_layout) {
        $new = preg_replace('/(<body[^>]*>)/i', '$1' . "\n" . $navbar, $contents, 1);
        return $new !== null ? $new : $contents;
    }

    // Páginas "de fábrica" de MyBB (usercp, member.php, search.php, etc.):
    // además de la navbar, envolvemos el contenido en un contenedor con el
    // ancho/paddings del tema para que no quede pegado a los bordes.
    $new    = preg_replace('/(<body[^>]*>)/i', '$1' . "\n" . $navbar . '<div id="ope-stock-wrap">', $contents, 1);
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

/**
 * pid del personaje activo y propio de una cuenta, o 0.
 * Cuenta tanto los aprobados como los que están EN REVISIÓN (a un personaje en
 * revisión se le permite postear solo en Off Topic; el estampado del pid debe
 * funcionar igual para firmar esos mensajes). El bloqueo por zona lo aplican
 * los hooks de newthread/newreply.
 */
function ope_rol_active_pid_for($uid)
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

    // Sólo cuenta si sigue siendo un personaje propio y no descartado
    // (aprobado o en revisión). Rechazados/borradores no se firman.
    if ($pid > 0 && $db->table_exists('rol_personajes')) {
        $vq = $db->simple_select('rol_personajes', 'pid', "pid = {$pid} AND uid = {$uid} AND estado IN ('aprobado','revision')", array('limit' => 1));
        if (!$db->num_rows($vq)) {
            $pid = 0;
        }
    }

    $cache[$uid] = $pid;
    return $pid;
}

/**
 * Estado del personaje activo de una cuenta: 'aprobado' | 'revision' | ''.
 * '' significa que no hay personaje activo válido (o no es propio).
 */
function ope_rol_active_char_estado($uid)
{
    global $db;
    $uid = (int) $uid;
    if ($uid < 1 || !$db->table_exists('rol_cuentas')) {
        return '';
    }
    $pid = 0;
    $q = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pid = (int) $db->fetch_field($q, 'personaje_activo');
    }
    if ($pid <= 0 || !$db->table_exists('rol_personajes')) {
        return '';
    }
    $vq = $db->simple_select('rol_personajes', 'estado', "pid = {$pid} AND uid = {$uid}", array('limit' => 1));
    if (!$db->num_rows($vq)) {
        return '';
    }
    return (string) $db->fetch_field($vq, 'estado');
}

/** fid de la categoría "Off Topic" (type='c'), cacheado. 0 si no existe. */
function ope_rol_offtopic_cat_fid()
{
    global $db;
    static $cid = null;
    if ($cid !== null) {
        return $cid;
    }
    $cid = 0;
    $q = $db->simple_select('forums', 'fid', "type = 'c' AND name LIKE '%Off Topic%'", array('limit' => 1));
    if ($db->num_rows($q)) {
        $cid = (int) $db->fetch_field($q, 'fid');
    }
    return $cid;
}

/** ¿El foro $fid pertenece a la categoría Off Topic (o es esa categoría)? */
function ope_rol_is_offtopic_fid($fid)
{
    global $db;
    $fid = (int) $fid;
    $cat = ope_rol_offtopic_cat_fid();
    if ($cat <= 0 || $fid <= 0) {
        return false;
    }
    if ($fid === $cat) {
        return true;
    }
    // parentlist de MyBB = lista CSV de ancestros (incluye la categoría raíz).
    $q = $db->simple_select('forums', 'parentlist', "fid = {$fid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return false;
    }
    $parents = array_map('trim', explode(',', (string) $db->fetch_field($q, 'parentlist')));
    return in_array((string) $cat, $parents, true);
}

/**
 * Motivo por el que NO se puede postear en $fid con el personaje activo, o ''.
 * Regla: un personaje EN REVISIÓN solo puede publicar en la zona Off Topic.
 * Los aprobados publican en cualquier foro; sin personaje activo no se aplica
 * esta restricción (rigen los permisos nativos de MyBB).
 */
function ope_rol_post_block_reason($fid)
{
    global $mybb;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return '';
    }
    $estado = ope_rol_active_char_estado($uid);
    if ($estado === 'revision' && !ope_rol_is_offtopic_fid($fid)) {
        return 'Tu personaje está <b>en revisión</b>. Hasta que el staff lo apruebe, solo puedes publicar en la zona <b>Off Topic</b>.';
    }
    return '';
}

/** Hook newthread: bloquea crear tema fuera de Off Topic si estás en revisión. */
function ope_rol_guard_newthread()
{
    global $fid;
    $reason = ope_rol_post_block_reason((int) $fid);
    if ($reason !== '') {
        error($reason);
    }
}

/** Hook newreply: bloquea responder fuera de Off Topic si estás en revisión. */
function ope_rol_guard_newreply()
{
    global $fid, $thread;
    $f = (int) ($fid ?? 0);
    if ($f <= 0 && !empty($thread['fid'])) {
        $f = (int) $thread['fid'];
    }
    $reason = ope_rol_post_block_reason($f);
    if ($reason !== '') {
        error($reason);
    }
}

/**
 * Normaliza el nombre de una facción a su slug canónico (una de las 6 válidas):
 * pirata · marine · revolucionario · gobierno · cazarrecompensas · civil.
 * Devuelve '' si no coincide con ninguna. Es la fuente única PHP del mapeo.
 */
function ope_rol_faccion_slug($faccion)
{
    $s = strtolower(trim((string) $faccion));
    // quita acentos comunes
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'));
    $validas = array('pirata', 'marine', 'revolucionario', 'gobierno', 'cazarrecompensas', 'civil');
    if (in_array($s, $validas, true)) {
        return $s;
    }
    // alias tolerantes
    if ($s === 'cazador' || $s === 'cazadores' || $s === 'bounty' || $s === 'bountyhunter') {
        return 'cazarrecompensas';
    }
    if ($s === 'gov' || $s === 'gobierno mundial' || $s === 'cipher pol' || $s === 'cp') {
        return 'gobierno';
    }
    return '';
}

/** Ficha resumida (pid, uid, nombre, slug, rango, nivel, avatar, faccion) o null. */
function ope_rol_char($pid)
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
        $q = $db->simple_select('rol_personajes', 'pid, uid, nombre, slug, rango, nivel, avatar, icono, firma, estado, datos', "pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $row = $db->fetch_array($q);
            // Facción: vive dentro del JSON `datos`. La exponemos ya resuelta.
            $datos = json_decode((string) ($row['datos'] ?? ''), true) ?: array();
            $row['faccion']      = (string) ($datos['faccion'] ?? '');
            $row['faccion_slug'] = ope_rol_faccion_slug($row['faccion']);
        }
    }

    $cache[$pid] = $row;
    return $row;
}

/** Enlace HTML al expediente del personaje (nombre enlazado a ficha.php). */
function ope_rol_char_link($pid, $fallback_name = '')
{
    global $mybb;
    $char = ope_rol_char($pid);
    if (!$char) {
        return $fallback_name !== '' ? htmlspecialchars_uni($fallback_name) : '';
    }
    $bburl = htmlspecialchars_uni($mybb->settings['bburl']);
    return '<a href="' . $bburl . '/ficha.php?pid=' . (int) $char['pid'] . '" class="ope-char-link">' . htmlspecialchars_uni($char['nombre']) . '</a>';
}

// ─────────────────────────────────────────────────────────────
// Estampado del personaje activo al crear hilos y mensajes.
// ─────────────────────────────────────────────────────────────

function ope_rol_stamp_thread(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->thread_insert_data['ope_pid'] = ope_rol_active_pid_for($uid);
    return $dh;
}

function ope_rol_stamp_thread_post(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->post_insert_data['ope_pid'] = ope_rol_active_pid_for($uid);
    return $dh;
}

function ope_rol_stamp_post(&$dh)
{
    $uid = (int) ($dh->data['uid'] ?? 0);
    $dh->post_insert_data['ope_pid'] = ope_rol_active_pid_for($uid);
    return $dh;
}

/** Tras crear un hilo: el personaje del primer mensaje es el "último" de hilo y foro. */
function ope_rol_after_thread(&$dh)
{
    global $db;
    $uid = (int) ($dh->data['uid'] ?? 0);
    $pid = ope_rol_active_pid_for($uid);
    $tid = (int) ($dh->tid ?? 0);
    $fid = (int) ($dh->data['fid'] ?? 0);
    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);

    if ($tid > 0) {
        $db->update_query('threads', array('ope_lastpid' => $pid), "tid = {$tid}");
    }
    if ($fid > 0 && $visible === 1) {
        $db->update_query('forums', array('ope_lastpid' => $pid), "fid = {$fid}");
    }
    return $dh;
}

/** Tras crear un mensaje: si es visible, pasa a ser el "último" de hilo y foro. */
function ope_rol_after_post(&$dh)
{
    global $db;
    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);
    if ($visible !== 1) {
        return $dh;
    }
    $uid = (int) ($dh->data['uid'] ?? 0);
    $pid = ope_rol_active_pid_for($uid);
    $tid = (int) ($dh->data['tid'] ?? ($dh->post_insert_data['tid'] ?? 0));
    $fid = (int) ($dh->data['fid'] ?? ($dh->post_insert_data['fid'] ?? 0));

    if ($tid > 0) {
        $db->update_query('threads', array('ope_lastpid' => $pid), "tid = {$tid}");
    }
    if ($fid > 0) {
        $db->update_query('forums', array('ope_lastpid' => $pid), "fid = {$fid}");
    }
    return $dh;
}

// ─────────────────────────────────────────────────────────────
// Postbit: el autor visible del mensaje es el personaje, no la cuenta.
// ─────────────────────────────────────────────────────────────
function ope_rol_postbit($post)
{
    global $mybb;

    // Siempre definido para la plantilla postbit ({$post['ope_fac_class']}).
    if (!isset($post['ope_fac_class'])) {
        $post['ope_fac_class'] = '';
    }

    if (empty($post['ope_pid'])) {
        return $post;
    }
    $char = ope_rol_char((int) $post['ope_pid']);
    if (!$char) {
        return $post;
    }

    $bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
    $nombre   = htmlspecialchars_uni($char['nombre']);
    $fichaurl = $bburl . '/ficha.php?pid=' . (int) $char['pid'];

    // Color por FACCIÓN: clase reutilizable para la cajetilla y el nombre.
    $fac_slug  = (string) ($char['faccion_slug'] ?? '');
    $fac_class = $fac_slug !== '' ? 'fac-' . $fac_slug : '';
    $post['ope_fac_class'] = $fac_class;

    // Nombre del personaje enlazado a su expediente, teñido por facción.
    $post['profilelink']       = '<span class="ope-pa-fac ' . $fac_class . '"><a href="' . $fichaurl . '" class="ope-char-link">' . $nombre . '</a></span>';
    $post['profilelink_plain'] = $fichaurl;

    // Rango y nivel como subtítulo bajo el nombre.
    $post['usertitle'] = 'Rango ' . htmlspecialchars_uni($char['rango']) . ' &middot; Nivel ' . (int) $char['nivel'];

    // Bloque de avatar: usa el retrato del personaje si lo tiene, y muestra el
    // nombre del personaje como etiqueta (no la cuenta). Mantiene el contenedor
    // .ope-avatar del tema para conservar el estilo.
    $img_src = trim((string) $char['avatar']) !== '' ? $char['avatar'] : (string) ($post['avatar'] ?? '');
    $icono   = trim((string) ($char['icono'] ?? ''));
    $icono_html = $icono !== '' ? '<img class="ope-post-ico" src="' . htmlspecialchars_uni($icono) . '" alt="" onerror="this.remove()" />' : '';
    $post['useravatar'] = '<div class="ope-avatar"><a href="' . $fichaurl . '"><img src="' . htmlspecialchars_uni($img_src) . '" alt="' . $nombre . '" onerror="this.remove()" /></a>' . $icono_html . '<span>' . $nombre . '</span></div>';

    // Firma POR PERSONAJE: sustituye la firma de la cuenta. Si el personaje tiene
    // firma configurada en su ficha, se muestra con un separador "One Piece Eternal".
    // Si no tiene, no se muestra firma (aunque la cuenta tenga una).
    $firma_raw = trim((string) ($char['firma'] ?? ''));
    if ($firma_raw !== '') {
        $post['signature'] = ope_rol_render_firma($firma_raw);
    } else {
        $post['signature'] = '';
    }

    return $post;
}

/**
 * Parsea la firma BBCode de un personaje y la envuelve con el separador
 * aesthetic "One Piece Eternal". Devuelve el HTML listo para el postbit.
 */
function ope_rol_render_firma($firma_raw)
{
    global $parser;
    if (!is_object($parser)) {
        require_once MYBB_ROOT . 'inc/class_parser.php';
        $parser = new postParser;
    }
    $parsed = $parser->parse_message($firma_raw, array(
        'allow_html'     => 0,
        'allow_mycode'   => 1,
        'allow_smilies'  => 1,
        'allow_imgcode'  => 1,
        'filter_badwords'=> 1,
        'nl2br'          => 1,
    ));
    return '<div class="ope-post-sig ope-sig-char">'
         . '<div class="ope-sig-sep" aria-hidden="true"><span>One Piece Eternal</span></div>'
         . '<div class="ope-sig-body">' . $parsed . '</div>'
         . '</div>';
}

// ─────────────────────────────────────────────────────────────
// forumdisplay: el autor del hilo y el último posteo son el personaje.
// ─────────────────────────────────────────────────────────────
function ope_rol_forumdisplay_thread()
{
    global $thread, $lastposterlink;

    if (!is_array($thread)) {
        return;
    }
    if (!empty($thread['ope_pid'])) {
        $link = ope_rol_char_link((int) $thread['ope_pid']);
        if ($link !== '') {
            $thread['profilelink'] = $link;
        }
    }
    if (!empty($thread['ope_lastpid'])) {
        $link = ope_rol_char_link((int) $thread['ope_lastpid']);
        if ($link !== '') {
            $lastposterlink = $link;
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Época (pasado/presente) + etiqueta del tema para la línea de tiempo.
// ─────────────────────────────────────────────────────────────

/** Año in-rol "presente": del epoch guardado en datacache ope_home, o el actual. */
function ope_rol_present_year()
{
    global $cache;
    $epoch = 0;
    if (is_object($cache)) {
        $home = $cache->read('ope_home');
        if (is_array($home) && !empty($home['rol_epoch'])) {
            $epoch = (int) $home['rol_epoch'];
        }
    }
    if ($epoch <= 0) {
        $epoch = TIME_NOW;
    }
    return (int) gmdate('Y', $epoch);
}

/** Etiquetas de cronología válidas (valor almacenado => etiqueta visible). */
function ope_rol_thread_tags()
{
    return array(
        'Mision' => 'Misión',
        'Trama'  => 'Trama',
        'Viaje'  => 'Viaje',
        'Fic'    => 'Fic',
    );
}

/**
 * Guarda/actualiza rol_thread_meta para un tema. Reglas:
 *  - presente  => fecha_rol = año in-rol actual (INALTERABLE, ignora input usuario)
 *  - pasado    => fecha_rol = año que indique el jugador (saneado a int)
 *  - tag       => una de las válidas o '' (sin etiqueta)
 * No se aplica en foros Off Topic (esos temas no van en la línea de tiempo).
 */
function ope_rol_store_thread_meta($tid, $fid, $era_in, $fecha_in, $tag_in)
{
    global $db;
    $tid = (int) $tid;
    $fid = (int) $fid;
    if ($tid < 1 || !$db->table_exists('rol_thread_meta')) {
        return;
    }
    // En Off Topic no se guarda metadata de época.
    if (function_exists('ope_rol_is_offtopic_fid') && ope_rol_is_offtopic_fid($fid)) {
        return;
    }

    $era = ($era_in === 'pasado') ? 'pasado' : 'presente';
    if ($era === 'presente') {
        $fecha = ope_rol_present_year();
    } else {
        $fecha = (int) $fecha_in;
        if ($fecha < 0) $fecha = 0;
        if ($fecha > 9999) $fecha = 9999;
    }

    $tags_ok = array_keys(ope_rol_thread_tags());
    $tag = in_array((string) $tag_in, $tags_ok, true) ? (string) $tag_in : '';

    $data = array(
        'tid'       => $tid,
        'era'       => $db->escape_string($era),
        'fecha_rol' => $fecha,
        'tag'       => $db->escape_string($tag),
        'dateline'  => TIME_NOW,
    );

    $ex = $db->simple_select('rol_thread_meta', 'tid', "tid = {$tid}", array('limit' => 1));
    if ($db->num_rows($ex)) {
        unset($data['tid']);
        $db->update_query('rol_thread_meta', $data, "tid = {$tid}");
    } else {
        $db->insert_query('rol_thread_meta', $data);
    }
}

/** Hook newthread: lee los inputs de época/etiqueta y persiste la metadata. */
function ope_rol_save_thread_meta()
{
    global $mybb, $tid, $fid;
    ope_rol_store_thread_meta(
        (int) $tid,
        (int) $fid,
        (string) $mybb->get_input('ope_era'),
        $mybb->get_input('ope_fecha_rol', MyBB::INPUT_INT),
        (string) $mybb->get_input('ope_tag')
    );
}

/** Hook editpost: permite corregir época/etiqueta si el formulario los envía. */
function ope_rol_save_thread_meta_edit()
{
    global $mybb, $db;
    // Solo actúa sobre el primer post (tema) y si vienen los campos.
    if ($mybb->get_input('ope_era') === '' && $mybb->get_input('ope_tag') === '') {
        return;
    }
    $pid = $mybb->get_input('pid', MyBB::INPUT_INT);
    if ($pid < 1) return;
    $q = $db->simple_select('posts', 'tid, fid', "pid = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) return;
    $row = $db->fetch_array($q);
    ope_rol_store_thread_meta(
        (int) $row['tid'],
        (int) $row['fid'],
        (string) $mybb->get_input('ope_era'),
        $mybb->get_input('ope_fecha_rol', MyBB::INPUT_INT),
        (string) $mybb->get_input('ope_tag')
    );
}

// ─────────────────────────────────────────────────────────────
// Spoilers anidables [spoiler] / [spoiler=Título]
// ─────────────────────────────────────────────────────────────

/**
 * Convierte [spoiler]...[/spoiler] y [spoiler=Título]...[/spoiler] en cajas
 * plegables. Se ejecuta en el hook `parse_message` (tras mycode, antes de
 * nl2br y del reensamblado de [code], por lo que no toca spoilers dentro de
 * bloques de código). Procesa de dentro hacia fuera para soportar anidamiento.
 */
function ope_rol_parse_spoilers($message)
{
    if (stripos($message, '[spoiler') === false) {
        return $message;
    }
    // Cuerpo = todo lo que no contenga otra apertura/cierre de spoiler (innermost).
    // El título no puede contener ']' para no tragarse spoilers anidados.
    $pattern = '#\[spoiler(?:=([^\]]*))?\]((?:(?!\[/?spoiler).)*?)\[/spoiler\]#is';
    $guard = 0;
    while ($guard < 60 && preg_match($pattern, $message)) {
        $message = preg_replace_callback($pattern, function ($m) {
            $title = isset($m[1]) ? trim($m[1]) : '';
            $title = trim($title, "\"'");
            if ($title === '') {
                $title = 'Spoiler';
            }
            $body = $m[2];
            return '<div class="ope-spoiler">'
                 . '<button type="button" class="ope-spoiler-head"><span class="ope-spoiler-ic" aria-hidden="true"></span><span class="ope-spoiler-tt">' . $title . '</span></button>'
                 . '<div class="ope-spoiler-body" hidden>' . $body . '</div>'
                 . '</div>';
        }, $message);
        $guard++;
    }
    return $message;
}

/** JS global del tema (toggle de spoilers). Delegado: funciona anidado. */
function ope_rol_theme_js()
{
    static $js = null;
    if ($js !== null) {
        return $js;
    }
    $js = "\n<script id=\"ope-theme-js\">\n"
        . "(function(){document.addEventListener('click',function(e){"
        . "var h=e.target.closest&&e.target.closest('.ope-spoiler-head');if(!h)return;"
        . "e.preventDefault();var b=h.nextElementSibling;if(!b||!b.classList.contains('ope-spoiler-body'))return;"
        . "var open=b.hasAttribute('hidden');if(open){b.removeAttribute('hidden');h.classList.add('is-open');}"
        . "else{b.setAttribute('hidden','');h.classList.remove('is-open');}});})();\n"
        . "</script>\n";
    return $js;
}

// ─────────────────────────────────────────────────────────────
// Plantillas de post por personaje + insertador en newthread/newreply
// ─────────────────────────────────────────────────────────────

/** Devuelve las plantillas (nombre, cuerpo) del personaje dado, ordenadas. */
function ope_rol_char_templates($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('rol_post_templates')) {
        return $out;
    }
    $q = $db->simple_select('rol_post_templates', 'tpl_id, nombre, cuerpo',
        "pid = {$pid}", array('order_by' => 'disporder, tpl_id', 'order_dir' => 'asc'));
    while ($r = $db->fetch_array($q)) {
        $out[] = array(
            'tpl_id' => (int) $r['tpl_id'],
            'nombre' => (string) $r['nombre'],
            'cuerpo' => (string) $r['cuerpo'],
        );
    }
    return $out;
}

/** HTML+JSON+JS del insertador de plantillas para el textarea #message. */
function ope_rol_tpl_inserter_html()
{
    global $mybb;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return '';
    }
    $pid = ope_rol_active_pid_for($uid);
    if ($pid < 1) {
        return '';
    }
    $tpls = ope_rol_char_templates($pid);
    if (empty($tpls)) {
        return '';
    }

    $bodies = array();
    $buttons = '';
    foreach ($tpls as $i => $t) {
        $bodies[] = $t['cuerpo'];
        $buttons .= '<button type="button" class="ope-tplbtn" data-tpl="' . $i . '">'
                  . htmlspecialchars_uni($t['nombre']) . '</button>';
    }
    $json = json_encode($bodies, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $html  = '<div class="ope-tplbar" id="ope-tplbar">';
    $html .= '<span class="ope-tplbar-l">// insertar plantilla</span>';
    $html .= $buttons;
    $html .= '</div>';
    $html .= '<script>(function(){var D=' . $json . ';'
           . 'var bar=document.getElementById("ope-tplbar");if(!bar)return;'
           . 'var ta=document.getElementById("message");'
           . 'bar.addEventListener("click",function(e){var b=e.target.closest(".ope-tplbtn");if(!b||!ta)return;'
           . 'var body=D[b.getAttribute("data-tpl")];if(body==null)return;'
           . 'var s=ta.selectionStart||0,en=ta.selectionEnd||0,v=ta.value;'
           . 'ta.value=v.slice(0,s)+body+v.slice(en);ta.focus();var p=s+body.length;'
           . 'try{ta.setSelectionRange(p,p);}catch(x){}});})();</script>';
    return $html;
}

function ope_rol_tpl_inserter_newthread()
{
    $GLOBALS['ope_tpl_inserter'] = ope_rol_tpl_inserter_html();
}

function ope_rol_tpl_inserter_newreply()
{
    $GLOBALS['ope_tpl_inserter'] = ope_rol_tpl_inserter_html();
}
