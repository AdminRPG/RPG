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

// Catálogo del sistema de rol (facciones, stats, razas...): lo necesita el
// postbit para resolver etiquetas de facción y pilares de atributos sin
// depender de que la página actual sea ficha.php.
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

// Sistema "Mundo Vivo" (AV-13): capa de datos y lógica (tablero, ciclos,
// eventos, misiones, NPCs, prompt, publicación, noticias del index).
require_once MYBB_ROOT . 'inc/ope_rol_mundo.php';

// Catálogos gestionables por staff (tienda, tripulaciones, bibliotecas de
// akuma/bestiario/estilos) + asignación de misiones. Fuente única de lectura
// para las páginas públicas, que ya no llevan datos mockup.
require_once MYBB_ROOT . 'inc/ope_rol_catalogos.php';

// Sistema OP-Eternal + Motor de PP (Puntos de Progreso) — Oleada 1.
require_once MYBB_ROOT . 'inc/ope_rol_system.php';

// Oráculo de Viaje — Oleada 3.
require_once MYBB_ROOT . 'inc/ope_rol_oraculo.php';
require_once MYBB_ROOT . 'inc/ope_rol_oraculo_post.php';
require_once MYBB_ROOT . 'inc/ope_rol_viajes.php';

// Sistema de rachas diarias (AV-16).
require_once MYBB_ROOT . 'inc/ope_rol_rachas.php';

$plugins->add_hook('global_start', 'ope_rol_global');

// Posteo por personaje: estampa el pid del personaje activo en cada
// mensaje/hilo y propaga el "último posteo" a hilos y foros.
$plugins->add_hook('datahandler_post_insert_thread', 'ope_rol_stamp_thread');
$plugins->add_hook('datahandler_post_insert_thread_post', 'ope_rol_stamp_thread_post');
$plugins->add_hook('datahandler_post_insert_post', 'ope_rol_stamp_post');
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_after_thread');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_after_post');

// Snapshot histórico e inmutable (stats + objetos "encima") en el momento
// exacto en que se publica cada post: los modales Mochila/Atributos del
// postbit deben reflejar SIEMPRE ese estado, nunca el estado actual/en vivo.
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_snapshot_post');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_snapshot_post');

// PP automático por post: cuenta palabras y asigna Puntos de Progreso.
// Corre DESPUÉS de snapshot_post en el mismo hook (MyBB ejecuta en orden de registro).
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_pp_on_post');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope_pp_on_post');

// Restricción de posteo: un personaje EN REVISIÓN solo puede publicar en la
// zona Off Topic (crear tema o responder). Los aprobados, en cualquier foro.
$plugins->add_hook('newthread_do_newthread_start', 'ope_rol_guard_newthread');
$plugins->add_hook('newreply_do_newreply_start', 'ope_rol_guard_newreply');

// Época (pasado/presente) + etiqueta del tema para la línea de tiempo del rol.
$plugins->add_hook('newthread_do_newthread_end', 'ope_rol_save_thread_meta');
$plugins->add_hook('editpost_do_editpost_end', 'ope_rol_save_thread_meta_edit');

// Spoilers anidables [spoiler]/[spoiler=Título] en todo el foro (antes de nl2br).
$plugins->add_hook('parse_message', 'ope_rol_parse_spoilers');

// Bloques del RPG System ([combate], [accion], [tecnica], [estado], [dado]).
$plugins->add_hook('parse_message', 'ope_rol_parse_rpg');

// Oráculo de Viaje: [viaje=ID] y [viaje-cierre=ID] → HTML visual OP-Eternal.
$plugins->add_hook('parse_message', 'ope_rol_parse_viaje');

// Panel de viaje activo en showthread (cierre manual).
$plugins->add_hook('showthread_end', 'ope_rol_viaje_showthread_end');

// Insertador de plantillas de post del personaje activo en newthread/newreply.
$plugins->add_hook('newthread_end', 'ope_rol_tpl_inserter_newthread');
$plugins->add_hook('newreply_end', 'ope_rol_tpl_inserter_newreply');
$plugins->add_hook('editpost_end', 'ope_rol_tpl_inserter_newreply');

// Muestra el personaje (no la cuenta) como autor visible del mensaje.
$plugins->add_hook('postbit', 'ope_rol_postbit');

// Lista de hilos: autor del hilo y último posteo mostrados como personaje.
$plugins->add_hook('forumdisplay_thread_end', 'ope_rol_forumdisplay_thread');

// El staff es POR PERSONAJE, no por cuenta: aunque la cuenta tenga permisos de
// moderador/admin en MyBB, si el personaje activo no tiene rol de staff no debe
// ver el desplegable "Moderation Options" del tema.
$plugins->add_hook('showthread_end', 'ope_rol_hide_modtools_showthread');

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
    $links  .= '<a href="' . $bburl . '/tripulacion.php" class="ope-nav-link' . $isOn(array('tripulacion.php', 'tramite-tripulacion.php')) . '">Tripulaci&oacute;n</a>';
    $links  .= '<a href="' . $bburl . '/tramites.php" class="ope-nav-link' . $isOn(array('tramites.php', 'notificar-tema.php', 'tablon-misiones.php', 'aceptar-mision.php', 'tienda.php', 'viajes.php')) . '">Tr&aacute;mites</a>';
    // Mundo Vivo: sección desplegable (Periódicos + Estado del mundo).
    $mvOn = $isOn(array('periodicos.php', 'estado-mundo.php'));
    $links  .= '<div class="ope-nav-dd">'
             . '<button type="button" class="ope-nav-link ope-nav-dd-btn' . $mvOn . '" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-expanded="false">Mundo Vivo<span class="ope-dd-caret" aria-hidden="true">&#9662;</span></button>'
             . '<div class="ope-dropdown ope-nav-dd-menu">'
             . '<a href="' . $bburl . '/periodicos.php" class="ope-dropdown-item">Peri&oacute;dicos</a>'
             . '<a href="' . $bburl . '/estado-mundo.php" class="ope-dropdown-item">Estado del mundo</a>'
             . '</div></div>';
    $links  .= '<a href="' . $bburl . '/guias.php" class="ope-nav-link' . $isOn(array('guias.php')) . '">Gu&iacute;as</a>';
    $bibOn = $isOn(array('biblioteca-personajes.php','biblioteca-akuma.php','biblioteca-npc.php','biblioteca-estilos.php','biblioteca-bestiario.php'));
    $links  .= '<div class="ope-nav-dd">'
             . '<button type="button" class="ope-nav-link ope-nav-dd-btn' . $bibOn . '" onclick="this.nextElementSibling.classList.toggle(\'open\')" aria-expanded="false">Bibliotecas<span class="ope-dd-caret" aria-hidden="true">&#9662;</span></button>'
             . '<div class="ope-dropdown ope-nav-dd-menu">'
             . '<a href="' . $bburl . '/biblioteca-personajes.php" class="ope-dropdown-item">Biblioteca personajes</a>'
             . '<a href="' . $bburl . '/biblioteca-akuma.php" class="ope-dropdown-item">Biblioteca akuma no mi</a>'
             . '<a href="' . $bburl . '/biblioteca-npc.php" class="ope-dropdown-item">Biblioteca NPC</a>'
             . '<a href="' . $bburl . '/biblioteca-estilos.php" class="ope-dropdown-item">Biblioteca estilos</a>'
             . '<a href="' . $bburl . '/biblioteca-bestiario.php" class="ope-dropdown-item">Biblioteca bestiario</a>'
             . '</div></div>';
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

    // Selector de tema visual global: icono + popover de 7 puntos de color.
    // Preferencia de NAVEGADOR (cookie), visible igual logueado o invitado, y
    // por eso vive fuera del if/else de arriba. Se lee la cookie aquí también
    // (no solo en ope_rol_inject_navbar) porque páginas propias como ficha.php
    // llaman a esta función directamente, sin pasar por el hook.
    $ope_theme_labels = array(
        'eternal' => array('Eternal', '#FFCB93', '#41A4E0'),
        'rojo'    => array('Rojo',    '#FFB3A3', '#E0645A'),
        'azul'    => array('Azul',    '#9FD8FF', '#2F8FD1'),
        'verde'   => array('Verde',   '#9FEACB', '#2F9E6B'),
        'morado'  => array('Morado',  '#D8C2FF', '#8B5FBF'),
        'mostaza' => array('Mostaza', '#FFDD8A', '#C9962E'),
        'gris'    => array('Gris',    '#D6DEE6', '#7C8A99'),
    );
    $ope_theme_cookie  = isset($_COOKIE['ope_theme']) ? (string) $_COOKIE['ope_theme'] : '';
    $ope_theme_current = array_key_exists($ope_theme_cookie, $ope_theme_labels) ? $ope_theme_cookie : 'eternal';

    $themeDots = '';
    foreach ($ope_theme_labels as $slug => $info) {
        list($label, $swatchA, $swatchB) = $info;
        $activeCls = ($slug === $ope_theme_current) ? ' active' : '';
        $themeDots .= '<button type="button" class="ope-theme-dot' . $activeCls . '" data-theme="' . $slug . '"'
                    . ' title="' . htmlspecialchars_uni($label) . '" aria-label="' . htmlspecialchars_uni($label) . '"'
                    . ' style="background:linear-gradient(135deg,' . $swatchA . ' 50%,' . $swatchB . ' 50%)"></button>';
    }

    $themePicker  = '<div class="ope-theme-picker">';
    $themePicker .= '<button type="button" class="ope-nav-bell ope-theme-toggle" title="Tema visual" aria-expanded="false" onclick="event.stopPropagation();this.nextElementSibling.classList.toggle(\'open\')">';
    // Rueda de color con 4 gajos de tinta real (no monocromo): así se lee de
    // un vistazo como "selector de tema" y no como un círculo blanco suelto.
    $themePicker .= '<svg viewBox="0 0 24 24" width="20" height="20" stroke="none">'
                  . '<path d="M12 2A10 10 0 0 0 3.6 6.6L12 12Z" fill="#e0645a"/>'
                  . '<path d="M3.6 6.6A10 10 0 0 0 3.6 17.4L12 12Z" fill="#2f8fd1"/>'
                  . '<path d="M3.6 17.4A10 10 0 0 0 12 22L12 12Z" fill="#e3a836"/>'
                  . '<path d="M12 22A10 10 0 0 0 20.4 17.4L12 12Z" fill="#2f9e6b"/>'
                  . '<path d="M20.4 17.4A10 10 0 0 0 20.4 6.6L12 12Z" fill="#8b5fbf"/>'
                  . '<path d="M20.4 6.6A10 10 0 0 0 12 2L12 12Z" fill="#e0592f"/>'
                  . '<circle cx="12" cy="12" r="3.4" fill="var(--iron-edge)"/>'
                  . '</svg>';
    $themePicker .= '</button>';
    $themePicker .= '<div class="ope-theme-pop">' . $themeDots . '</div>';
    $themePicker .= '</div>';

    $right = $themePicker . $right;

    // Red de seguridad anti-flash: páginas propias en PHP puro (ficha.php y
    // similares) echan esta navbar directamente tras <body>, sin pasar por el
    // hook pre_output_page (que es quien pone data-site-theme server-side en
    // el pipeline estándar de MyBB). Este script, al ser lo primero que se
    // parsea dentro de <body>, aplica el atributo antes de que se pinte nada,
    // así que no hay flash visible aunque sea un fallback JS. En páginas del
    // pipeline es inofensivo: re-aplica el mismo valor que ya puso el server.
    $html  = '<script>document.body.setAttribute("data-site-theme",(function(){'
           . 'var m=document.cookie.match(/(?:^|; )ope_theme=([^;]+)/);'
           . 'var t=m?decodeURIComponent(m[1]):"";'
           . 'return /^(eternal|rojo|azul|verde|morado|mostaza|gris)$/.test(t)?t:"eternal";'
           . '})());</script>' . "\n";
    $html .= '<!-- ===== NAVBAR (fixed, iron-edge) · fuente única ===== -->' . "\n";
    $html .= ope_rol_navbar_css();
    $html .= '<nav id="ope-navbar"><div class="ope-nav">';
    $html .= '<a href="' . $bburl . '/index.php" class="ope-nav-logo">One Piece Eternal</a>';
    $html .= '<div class="ope-nav-links">' . $links . '</div>';
    $html .= '<div class="ope-nav-right">' . $right . '</div>';
    $html .= '</div></nav>';

    // JS del tema (aplicar al click en los puntos, cerrar popovers, spoilers)
    // va SIEMPRE pegado a la navbar: páginas propias en PHP puro (ficha.php,
    // personajes.php, etc.) llaman a esta función directamente y nunca pasan
    // por el hook pre_output_page, así que si el script no viaja aquí sus
    // puntos de color quedan decorativos (abren el popover pero no aplican
    // nada). ope_rol_inject_navbar() ya no lo inserta por separado para no
    // duplicarlo en las páginas del pipeline estándar.
    $html .= ope_rol_theme_js();

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
        // Reset explícito de <button>: páginas del pipeline MyBB heredan un
        // reset de botón desde global.css/css3.css, pero páginas propias en
        // PHP puro (ficha.php, personajes.php, etc.) SOLO cargan ope.css, así
        // que sin esto el navegador pinta el chrome nativo (fondo blanco,
        // borde "outset") en los botones de la navbar. Fuente única: no
        // depender de que cada página cargue el reset de MyBB.
        . '#ope-navbar button{background:none;border:none;padding:0;margin:0;font:inherit;color:inherit;cursor:pointer;-webkit-appearance:none;appearance:none}'
        . '#ope-navbar .ope-nav{max-width:1300px;margin:0 auto;height:100%;padding:0 18px;display:flex;align-items:center;justify-content:space-between;gap:14px}'
        . '#ope-navbar .ope-nav-logo{font-family:var(--disp);font-weight:900;font-size:1.45rem;letter-spacing:1px;color:var(--paper);text-transform:uppercase;line-height:1;display:flex;align-items:center;gap:9px;text-decoration:none}'
        . '#ope-navbar .ope-nav-logo::before{content:"";width:11px;height:11px;background:var(--ember);box-shadow:0 0 10px var(--ember);flex:0 0 auto}'
        . '#ope-navbar .ope-nav-logo:hover{color:#fff}'
        . '#ope-navbar .ope-nav-links{display:flex;gap:2px}'
        . '#ope-navbar .ope-nav-link{font-family:var(--mono);font-size:.72rem;font-weight:700;color:var(--paper-dim);text-transform:uppercase;letter-spacing:1px;padding:7px 11px;border:1px solid transparent;text-decoration:none;line-height:1}'
        . '#ope-navbar .ope-nav-link:hover,#ope-navbar .ope-nav-link.on{color:var(--iron);background:var(--ember);border-color:#000}'
        . '#ope-navbar .ope-nav-dd{position:relative;display:flex}'
        . '#ope-navbar .ope-nav-dd-btn{display:inline-flex;align-items:center;gap:5px;cursor:pointer}'
        . '#ope-navbar .ope-dd-caret{font-size:.6rem;line-height:1;transition:transform .12s}'
        . '#ope-navbar .ope-nav-dd-menu{left:0;right:auto;top:40px}'
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
        . '#ope-navbar .ope-theme-picker{position:relative}'
        . '#ope-navbar .ope-theme-toggle{opacity:.86;transition:opacity .12s,transform .12s}'
        . '#ope-navbar .ope-theme-toggle:hover{opacity:1;transform:scale(1.08);color:var(--paper-dim)}'
        . '#ope-navbar .ope-theme-pop{display:none;position:absolute;right:0;top:44px;background:var(--iron-plate);border:2px solid #000;padding:10px;z-index:100;width:118px;flex-wrap:wrap;gap:8px}'
        . '#ope-navbar .ope-theme-pop.open{display:flex}'
        . '#ope-navbar .ope-theme-dot{width:22px;height:22px;flex:0 0 auto;border-radius:50%;border:2px solid #000;padding:0;cursor:pointer;transition:transform .12s,box-shadow .12s}'
        . '#ope-navbar .ope-theme-dot:hover{transform:scale(1.15)}'
        . '#ope-navbar .ope-theme-dot.active{box-shadow:0 0 0 2px var(--iron-plate),0 0 0 4px var(--paper)}'
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
 * Devuelve la URL pública de una imagen decorativa si el archivo existe.
 * $rel es la ruta relativa SIN extensión desde images/ (p.ej. 'ope/deco/tramites').
 * Prueba webp/avif/jpg/jpeg/png y devuelve '' si no hay ninguna.
 */
function ope_rol_deco_url($rel)
{
    global $mybb;
    static $cache = array();
    $rel = trim((string) $rel, '/');
    if (isset($cache[$rel])) {
        return $cache[$rel];
    }
    $url = '';
    foreach (array('webp', 'avif', 'jpg', 'jpeg', 'png') as $ext) {
        if (@is_file(MYBB_ROOT . 'images/' . $rel . '.' . $ext)) {
            $url = rtrim((string) $mybb->settings['bburl'], '/') . '/images/' . $rel . '.' . $ext;
            break;
        }
    }
    return $cache[$rel] = $url;
}

/**
 * Renderiza un banner decorativo ancho (masthead) con fallback a placeholder.
 * Sin dependencias de iconos externos: usa un SVG inline.
 *
 * @param string $rel    ruta relativa sin extensión desde images/ (p.ej. 'ope/deco/tramites')
 * @param string $alt    texto alternativo de la imagen
 * @param string $kicker etiqueta mono opcional mostrada en el placeholder
 */
function ope_rol_deco_banner($rel, $alt = '', $kicker = '')
{
    $rel = trim((string) $rel, '/');
    $url = ope_rol_deco_url($rel);

    $out = '<figure class="ope-deco-banner' . ($url !== '' ? ' has-image' : '') . '">';
    if ($url !== '') {
        $out .= '<img src="' . htmlspecialchars_uni($url) . '" alt="' . htmlspecialchars_uni($alt) . '" loading="lazy">';
    } else {
        $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21 3H3a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1Zm-1 2v9.6l-3.3-3.3a1 1 0 0 0-1.4 0L11 15.6l-1.8-1.8a1 1 0 0 0-1.4 0L4 17.6V5ZM8.5 8.5A1.5 1.5 0 1 1 7 10a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>';
        $out .= '<div class="ope-deco-ph">' . $svg
              . '<span>images/' . htmlspecialchars_uni($rel) . '.webp</span>';
        if ($kicker !== '') {
            $out .= '<small>' . htmlspecialchars_uni($kicker) . '</small>';
        }
        $out .= '</div>';
    }
    $out .= '</figure>';
    return $out;
}

/**
 * Renderiza una imagen decorativa vertical 4:5 para usar como columna lateral
 * (patrón "imagen a la izquierda, información a la derecha"). Fallback a placeholder.
 *
 * @param string $rel    ruta relativa sin extensión desde images/ (p.ej. 'ope/deco/guias')
 * @param string $alt    texto alternativo de la imagen
 * @param string $kicker etiqueta mono opcional mostrada en el placeholder
 */
function ope_rol_deco_aside($rel, $alt = '', $kicker = '')
{
    $rel = trim((string) $rel, '/');
    $url = ope_rol_deco_url($rel);

    $out = '<figure class="ope-deco-aside' . ($url !== '' ? ' has-image' : '') . '">';
    if ($url !== '') {
        $out .= '<img src="' . htmlspecialchars_uni($url) . '" alt="' . htmlspecialchars_uni($alt) . '" loading="lazy">';
    } else {
        $svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21 3H3a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1Zm-1 2v9.6l-3.3-3.3a1 1 0 0 0-1.4 0L11 15.6l-1.8-1.8a1 1 0 0 0-1.4 0L4 17.6V5ZM8.5 8.5A1.5 1.5 0 1 1 7 10a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>';
        $out .= '<div class="ope-deco-ph">' . $svg
              . '<span>images/' . htmlspecialchars_uni($rel) . '.webp</span>';
        if ($kicker !== '') {
            $out .= '<small>' . htmlspecialchars_uni($kicker) . '</small>';
        }
        $out .= '</div>';
    }
    $out .= '</figure>';
    return $out;
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

    // Tema visual global (cookie del navegador; sin sesión ni BD). Se resuelve
    // ANTES de las dos ramas de abajo para que ambas hereden el atributo, y así
    // el CSS puede aplicar el acento correcto ya en el primer render (sin flash).
    if (stripos($contents, 'data-site-theme=') === false) {
        $ope_theme_whitelist = array('eternal', 'rojo', 'azul', 'verde', 'morado', 'mostaza', 'gris');
        $ope_theme_cookie    = isset($_COOKIE['ope_theme']) ? (string) $_COOKIE['ope_theme'] : '';
        $ope_theme_current   = in_array($ope_theme_cookie, $ope_theme_whitelist, true) ? $ope_theme_cookie : 'eternal';
        $contents_themed     = preg_replace('/<body([^>]*)>/i', '<body$1 data-site-theme="' . $ope_theme_current . '">', $contents, 1);
        if ($contents_themed !== null) {
            $contents = $contents_themed;
        }
    }

    // ope_rol_navbar_html() ya lleva pegado su propio <script id="ope-theme-js">
    // (ver esa función), así que no se inyecta aparte aquí: evita duplicar el
    // listener de click en las páginas del pipeline estándar.
    $navbar = ope_rol_navbar_html();
    $script = defined('THIS_SCRIPT') ? THIS_SCRIPT : '';

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
    // (aprobado o en revisión). Rechazados/borradores/eliminados no se firman.
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
        $q = $db->simple_select('rol_personajes', 'pid, uid, nombre, slug, rango, nivel, avatar, icono, firma, estado, datos, rango_faccion', "pid = {$pid}", array('limit' => 1));
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

/**
 * Captura un snapshot INMUTABLE (stats efectivas + objetos "encima") del
 * personaje activo justo después de insertarse el post real en BD. Se
 * registra en los hooks `datahandler_post_insert_thread_end` (hilo nuevo) y
 * `datahandler_post_insert_post_end` (respuesta): en ambos casos el
 * datahandler ya expone `$dh->pid` con el pid REAL del post insertado.
 * Los posts anteriores a esta funcionalidad se rellenan (aproximados, con el
 * estado actual del personaje) por scripts/migrate-post-snapshot.php.
 */
function ope_rol_snapshot_post(&$dh)
{
    global $db;

    if (!$db->table_exists('rol_post_snapshot') || !$db->table_exists('rol_personajes')) {
        return $dh;
    }

    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);
    if ($visible !== 1) {
        return $dh;
    }

    $post_pid = (int) ($dh->pid ?? 0);
    if ($post_pid < 1) {
        return $dh;
    }

    // Idempotente: un mismo pid de post nunca debe tener dos snapshots.
    $exists = $db->simple_select('rol_post_snapshot', 'pid', "pid = {$post_pid}", array('limit' => 1));
    if ($db->num_rows($exists)) {
        return $dh;
    }

    $uid      = (int) ($dh->data['uid'] ?? 0);
    $char_pid = ope_rol_active_pid_for($uid);
    if ($char_pid < 1) {
        return $dh;
    }

    $q = $db->simple_select('rol_personajes', 'datos, inventario, pv_max, en_max, pa_por_turno', "pid = {$char_pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return $dh;
    }
    $row = $db->fetch_array($q);

    $datos = json_decode((string) $row['datos'], true);
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();

    $inv    = json_decode((string) $row['inventario'], true);
    $encima = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();

    $pv_max       = (int) ($row['pv_max'] ?? 0);
    $en_max       = (int) ($row['en_max'] ?? 0);
    $pa_por_turno = (int) ($row['pa_por_turno'] ?? 2);

    // Valores por defecto: los máximos (primer post de combate).
    $pv_actual = $pv_max;
    $en_actual = $en_max;

    // Buscar el último snapshot del mismo personaje en el mismo hilo para
    // heredar PV/EN actuales (arrastre entre posts del mismo combate).
    $tid = (int) ($dh->data['tid'] ?? ($dh->tid ?? 0));
    if ($tid > 0 && $db->table_exists('posts')) {
        $subquery = "SELECT pid FROM {$db->table_prefix}posts WHERE tid = {$tid} AND pid != {$post_pid} ORDER BY dateline DESC LIMIT 5";
        $prev = $db->query("
            SELECT pv_actual, en_actual FROM {$db->table_prefix}rol_post_snapshot
            WHERE personaje_pid = {$char_pid} AND pid IN ({$subquery})
            ORDER BY dateline DESC LIMIT 1
        ");
        if ($prev && $db->num_rows($prev) > 0) {
            $prev_row = $db->fetch_array($prev);
            if (isset($prev_row['pv_actual']) && $prev_row['pv_actual'] !== null) {
                $pv_actual = (int) $prev_row['pv_actual'];
            }
            if (isset($prev_row['en_actual']) && $prev_row['en_actual'] !== null) {
                $en_actual = (int) $prev_row['en_actual'];
            }
        }
    }

    $snap_data = array(
        'pid'           => $post_pid,
        'personaje_pid' => $char_pid,
        'atributos'     => $db->escape_string(json_encode($stats, JSON_UNESCAPED_UNICODE)),
        'objetos'       => $db->escape_string(json_encode($encima, JSON_UNESCAPED_UNICODE)),
        'dateline'      => TIME_NOW,
        'pv_actual'     => $pv_actual,
        'en_actual'     => $en_actual,
        'pa_actual'     => $pa_por_turno,
        'estados_json'  => null,
        'stats_mod_json'=> null,
    );

    $db->insert_query('rol_post_snapshot', $snap_data);

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

    // Extrae el bloque RPG System del cuerpo del mensaje y lo expone como
    // {$post['ope_rpgsys']} para renderizarlo FUERA del post, pegado debajo.
    // Esto aplica a TODOS los posts (tengan o no personaje), y también al
    // preview (previewpost usa build_postbit).
    $post['ope_rpgsys'] = '';
    if (!empty($post['message']) && strpos($post['message'], '<!--OPERPGSYS-->') !== false) {
        if (preg_match('#<!--OPERPGSYS-->([\s\S]*?)<!--/OPERPGSYS-->#', $post['message'], $mm)) {
            $post['ope_rpgsys'] = $mm[1];
        }
        $post['message'] = preg_replace('#(?:<br\s*/?>\s*)*<!--OPERPGSYS-->[\s\S]*?<!--/OPERPGSYS-->#', '', $post['message']);
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

    // Rango como subtítulo bajo el nombre (el rango YA implica el nivel; no se
    // duplica mostrando "Nivel N" aparte).
    $post['usertitle'] = 'Rango ' . htmlspecialchars_uni($char['rango']);

    // Bloque de avatar del post: SIEMPRE el AVATAR grande del personaje (no el
    // icono, que se reserva para contextos pequeños). Muestra el nombre del
    // personaje como etiqueta y conserva el contenedor .ope-avatar del tema.
    $img_src = trim((string) $char['avatar']) !== '' ? $char['avatar'] : (string) ($post['avatar'] ?? '');
    $post['useravatar'] = '<div class="ope-avatar"><a href="' . $fichaurl . '"><img src="' . htmlspecialchars_uni($img_src) . '" alt="' . $nombre . '" onerror="this.remove()" /></a><span>' . $nombre . '</span></div>';

    // Bloque bajo el avatar: facción/rango de facción/tripulación + botones
    // Mochila/Atributos con el snapshot histórico e inmutable de ESTE post.
    $post['ope_char_side'] = ope_rol_postbit_side($char, $post);

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
// Postbit: facción/tripulación bajo el avatar + modales Mochila/Atributos
// con el snapshot histórico (inmutable) del personaje EN ESE POST concreto.
// ─────────────────────────────────────────────────────────────

/**
 * Snapshot de un post ya publicado: array('stats'=>.., 'items'=>.., 'approx'=>bool).
 * 'approx' = true cuando todavía no existe fila en rol_post_snapshot (post
 * anterior a esta funcionalidad y aún no procesado por el backfill de
 * scripts/migrate-post-snapshot.php): se recurre al estado ACTUAL del
 * personaje como mejor aproximación disponible, nunca se inventa histórico.
 */
function ope_rol_post_snapshot($post_pid, array $char)
{
    global $db;

    $post_pid = (int) $post_pid;
    if ($db->table_exists('rol_post_snapshot')) {
        $q = $db->simple_select('rol_post_snapshot', 'atributos, objetos, pv_actual, en_actual, pa_actual, estados_json, stats_mod_json', "pid = {$post_pid}", array('limit' => 1));
        if ($db->num_rows($q)) {
            $row   = $db->fetch_array($q);
            $stats = json_decode((string) $row['atributos'], true);
            $items = json_decode((string) $row['objetos'], true);
            return array(
                'stats'     => is_array($stats) ? $stats : array(),
                'items'     => is_array($items) ? $items : array(),
                'approx'    => false,
                'pv_actual' => $row['pv_actual'] ?? null,
                'en_actual' => $row['en_actual'] ?? null,
                'pa_actual' => $row['pa_actual'] ?? null,
            );
        }
    }

    $datos = json_decode((string) ($char['datos'] ?? ''), true);
    $stats = is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array();

    $items = array();
    $iq = $db->simple_select('rol_personajes', 'inventario', 'pid = ' . (int) $char['pid'], array('limit' => 1));
    if ($db->num_rows($iq)) {
        $inv   = json_decode((string) $db->fetch_field($iq, 'inventario'), true);
        $items = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();
    }

    return array('stats' => $stats, 'items' => $items, 'approx' => true,
                 'pv_actual' => null, 'en_actual' => null, 'pa_actual' => null);
}

/** Relación tipo "tripulación" de un personaje (el otro extremo del vínculo), o null. */
function ope_rol_char_tripulacion($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !$db->table_exists('rol_relaciones')) {
        return null;
    }
    $q = $db->simple_select('rol_relaciones', 'pid, destino_pid', "tipo = 'tripulacion' AND (pid = {$pid} OR destino_pid = {$pid})", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $rr        = $db->fetch_array($q);
    $other_pid = ((int) $rr['pid'] === $pid) ? (int) $rr['destino_pid'] : (int) $rr['pid'];
    return ope_rol_char($other_pid);
}

/** Construye un modal ope-modal-* genérico (snapshot de post), único por id. */
function ope_rol_snapshot_modal($id, $titulo, $post_pid, $body_html, $approx_note)
{
    return '<div class="ope-modal-ov" id="' . $id . '" hidden onclick="if(event.target===this)this.hidden=true">'
         .   '<div class="ope-modal ope-modal-sm">'
         .     '<div class="ope-modal-h">'
         .       '<div class="ope-modal-tt"><span class="ope-modal-eye">// Snapshot del post #' . (int) $post_pid . '</span><h2>' . htmlspecialchars_uni($titulo) . '</h2></div>'
         .       '<button type="button" class="ope-modal-x" onclick="document.getElementById(\'' . $id . '\').hidden=true">&times;</button>'
         .     '</div>'
         .     '<div class="ope-modal-content">' . $approx_note . $body_html . '</div>'
         .   '</div>'
         . '</div>';
}

/**
 * Bloque HTML bajo el avatar del postbit: facción, rango de facción y
 * tripulación (solo si existe la relación), más los botones "Mochila" y
 * "Atributos" y sus modales con el snapshot histórico e inmutable de ESTE
 * post concreto (nunca el estado en vivo del personaje).
 */
function ope_rol_postbit_side(array $char, array $post)
{
    global $mybb;

    $bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
    $pid_post = (int) $post['pid'];

    $facciones   = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
    $fac_slug    = (string) ($char['faccion_slug'] ?? '');
    $fac_lbl     = isset($facciones[$fac_slug]) ? $facciones[$fac_slug]['nombre'] : ucfirst($fac_slug);
    $rango_fac   = trim((string) ($char['rango_faccion'] ?? ''));
    $tripulacion = ope_rol_char_tripulacion((int) $char['pid']);

    $rows = '';
    if ($fac_slug !== '') {
        $rows .= '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Facci&oacute;n</span><span class="ope-pa-srow-v fac-' . $fac_slug . '">' . htmlspecialchars_uni($fac_lbl) . '</span></div>'
               . '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Rango facci&oacute;n</span><span class="ope-pa-srow-v">' . ($rango_fac !== '' ? htmlspecialchars_uni($rango_fac) : '&mdash;') . '</span></div>';
    }
    if ($tripulacion) {
        $trip_url = $bburl . '/ficha.php?pid=' . (int) $tripulacion['pid'];
        $rows .= '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Tripulaci&oacute;n</span><span class="ope-pa-srow-v"><a href="' . $trip_url . '" class="ope-char-link">' . htmlspecialchars_uni($tripulacion['nombre']) . '</a></span></div>';
    }
    $org_html = $rows !== '' ? '<div class="ope-pa-stats ope-pa-org">' . $rows . '</div>' : '';

    $snap = ope_rol_post_snapshot($pid_post, $char);

    // ── PV / EN bajo avatar ──
    $vitals_html = '';
    if (function_exists('ope_combat_calc_pv') && function_exists('ope_combat_calc_en')) {
        $snap_stats = $snap['stats'];
        $pv_max = ope_combat_calc_pv($snap_stats);
        $en_max = ope_combat_calc_en($snap_stats);
        $pv_cur = isset($snap['pv_actual']) && $snap['pv_actual'] !== null ? (int) $snap['pv_actual'] : $pv_max;
        $en_cur = isset($snap['en_actual']) && $snap['en_actual'] !== null ? (int) $snap['en_actual'] : $en_max;
        $vitals_html = '<div class="ope-post-vitals">'
            . '<span class="ope-post-vital ope-post-vital--pv"><b>' . $pv_cur . '</b>/' . $pv_max . ' PV</span>'
            . '<span class="ope-post-vital ope-post-vital--en"><b>' . $en_cur . '</b>/' . $en_max . ' EN</span>'
            . '</div>';
    }

    if (empty($snap['items'])) {
        $mochila_body = '<p class="mono fs-76 c-dim">No llevaba nada encima en este post.</p>';
    } else {
        $mochila_body = '<div class="ope-snap-items">';
        foreach ($snap['items'] as $it) {
            if (!is_array($it)) continue;
            $n = trim((string) ($it['n'] ?? ''));
            if ($n === '') continue;
            $d  = trim((string) ($it['d'] ?? ''));
            $sz = max(1, (int) ($it['size'] ?? 1));
            $mochila_body .= '<div class="ope-snap-item"><span class="ope-snap-item-n">' . htmlspecialchars_uni($n) . '</span>';
            if ($d !== '') {
                $mochila_body .= '<span class="ope-snap-item-d">' . htmlspecialchars_uni($d) . '</span>';
            }
            $mochila_body .= '<span class="ope-snap-item-sz">' . $sz . ' slot' . ($sz > 1 ? 's' : '') . '</span></div>';
        }
        $mochila_body .= '</div>';
    }

    $stat_groups = function_exists('ope_rol_stats') ? ope_rol_stats() : array();
    $atrib_body  = '<div class="ope-snap-stats">';
    foreach ($stat_groups as $grupo) {
        $atrib_body .= '<div class="ope-snap-pillar"><div class="ope-snap-pillar-h">' . htmlspecialchars_uni($grupo['label']) . '</div>';
        foreach ($grupo['stats'] as $ab => $nombre_stat) {
            $v     = ope_rol_stat_num($snap['stats'], $ab);
            $lbl   = ope_rol_stat_label($v);
            $atrib_body .= '<div class="ope-snap-stat-row"><span>' . htmlspecialchars_uni($nombre_stat) . '</span><b>' . $v . ' ' . htmlspecialchars_uni($lbl) . '</b></div>';
        }
        $atrib_body .= '</div>';
    }
    $atrib_body .= '</div>';

    $approx_note = !empty($snap['approx'])
        ? '<p class="ope-snap-approx">Aproximaci&oacute;n: post anterior al sistema de snapshots, se muestra el estado actual del personaje.</p>'
        : '';

    $mochila_id = 'ope-mochila-' . $pid_post;
    $atrib_id   = 'ope-atributos-' . $pid_post;

    $tools = '<div class="ope-pa-tools">'
           . '<button type="button" class="ope-btn ope-btn-sm ope-btn-ghost" onclick="document.getElementById(\'' . $mochila_id . '\').hidden=false">Mochila</button>'
           . '<button type="button" class="ope-btn ope-btn-sm ope-btn-ghost" onclick="document.getElementById(\'' . $atrib_id . '\').hidden=false">Atributos</button>'
           . '</div>';

    $modals = ope_rol_snapshot_modal($mochila_id, 'Mochila', $pid_post, $mochila_body, $approx_note)
            . ope_rol_snapshot_modal($atrib_id, 'Atributos', $pid_post, $atrib_body, $approx_note);

    return $org_html . $vitals_html . $tools . $modals;
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

/**
 * showthread_end: si la CUENTA es moderador/admin de MyBB pero el PERSONAJE
 * activo no tiene rol de staff, se vacía el desplegable de moderación ya
 * construido por el core (no se toca showthread.php). Corre justo antes del
 * eval() de la plantilla "showthread", así que el cambio siempre se refleja.
 */
function ope_rol_hide_modtools_showthread()
{
    global $mybb, $moderationoptions;
    if (empty($mybb->user['ope_is_staff'])) {
        $moderationoptions = '';
    }
}

// ─────────────────────────────────────────────────────────────
// Época (pasado/presente) + etiqueta del tema para la línea de tiempo.
// ─────────────────────────────────────────────────────────────

/** Epoch del calendario on-rol (datacache ope_home o 1 ene del año actual). */
function ope_rol_onrol_epoch()
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
        $epoch = mktime(0, 0, 0, 1, 1, (int) gmdate('Y', TIME_NOW));
    }
    return $epoch;
}

/**
 * Calendario on-rol actual (4 estaciones × 65 días; 1 día OOC = 2 días on-rol).
 * Misma lógica que el widget del index.php.
 */
function ope_rol_onrol_calendar(?int $now = null): array
{
    $now = $now ?? TIME_NOW;
    $epoch = ope_rol_onrol_epoch();
    $seasons = array('Primavera', 'Verano', 'Otoño', 'Invierno');
    $ooc_days = (int) floor(($now - $epoch) / 86400);
    if ($ooc_days < 0) {
        $ooc_days = 0;
    }
    $rol_day_index = $ooc_days * 2;
    $rol_year = (int) floor($rol_day_index / 260) + 1;
    $rol_doy = $rol_day_index % 260;
    $rol_season_idx = (int) floor($rol_doy / 65);
    if ($rol_season_idx > 3) {
        $rol_season_idx = 3;
    }
    return array(
        'year'        => $rol_year,
        'day'         => ($rol_doy % 65) + 1,
        'season'      => $seasons[$rol_season_idx],
        'season_idx'  => $rol_season_idx,
    );
}

/** Año in-rol "presente": nº de año del calendario on-rol (I, II, III...), NUNCA el año real. */
function ope_rol_present_year()
{
    $cal = ope_rol_onrol_calendar();
    return (int) $cal['year'];
}

/** Etiqueta de año in-rol: números romanos hasta X, luego el número tal cual. Fuente única (index.php la usa igual). */
function ope_rol_year_label($year)
{
    $year = (int) $year;
    $roman = array('I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X');
    if ($year >= 1 && $year <= 10) {
        return $roman[$year - 1];
    }
    return (string) $year;
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
 *  - presente  => fecha_rol = año gregoriano del epoch; día (1–65) y estación = calendario on-rol actual
 *  - pasado    => fecha_rol, día y estación los indica el jugador (día 1–65)
 *  - tag       => una de las válidas o '' (sin etiqueta)
 * No se aplica en foros Off Topic (esos temas no van en la línea de tiempo).
 */
function ope_rol_store_thread_meta($tid, $fid, $era_in, $fecha_in, $tag_in, $dia_in = null, $estacion_in = '')
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

    $estaciones_ok = array('Primavera', 'Verano', 'Otoño', 'Invierno');

    $era = ($era_in === 'pasado') ? 'pasado' : 'presente';
    if ($era === 'presente') {
        $fecha = ope_rol_present_year();
        $cal = ope_rol_onrol_calendar();
        $dia = (int) $cal['day'];
        $estacion = (string) $cal['season'];
    } else {
        $fecha = (int) $fecha_in;
        if ($fecha < 0) $fecha = 0;
        if ($fecha > 9999) $fecha = 9999;

        $dia_raw = $dia_in !== null ? (int) $dia_in : 0;
        $dia = ($dia_raw >= 1 && $dia_raw <= 65) ? $dia_raw : null;
        $estacion = in_array((string) $estacion_in, $estaciones_ok, true) ? (string) $estacion_in : '';
    }

    $tags_ok = array_keys(ope_rol_thread_tags());
    $tag = in_array((string) $tag_in, $tags_ok, true) ? (string) $tag_in : '';

    $data = array(
        'tid'       => $tid,
        'era'       => $db->escape_string($era),
        'fecha_rol' => $fecha,
        'tag'       => $db->escape_string($tag),
        'fecha_dia' => $dia,
        'estacion'  => $db->escape_string($estacion),
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
        (string) $mybb->get_input('ope_tag'),
        $mybb->get_input('ope_fecha_dia', MyBB::INPUT_INT),
        (string) $mybb->get_input('ope_estacion')
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
        (string) $mybb->get_input('ope_tag'),
        $mybb->get_input('ope_fecha_dia', MyBB::INPUT_INT),
        (string) $mybb->get_input('ope_estacion')
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

/**
 * Parser de bloques de rol (RPG System) en los mensajes:
 *   [combate=Título]…[/combate]  → panel de estado de combate
 *   [accion=Tipo]…[/accion]      → declaración de acción
 *   [tecnica=Nombre]…[/tecnica]  → carta de técnica en línea
 *   [estado=tipo]Nombre[/estado] → chip de estado (positivo/negativo/neutral)
 *   [dado]2d6+FUE[/dado]         → chip de tirada (visual)
 * Se ejecuta en el hook parse_message, igual que los spoilers.
 */
function ope_rol_parse_rpg($message)
{
    if (stripos($message, '[combate') === false
        && stripos($message, '[accion') === false
        && stripos($message, '[tecnica') === false
        && stripos($message, '[estado') === false
        && stripos($message, '[dado') === false
        && stripos($message, '[rpgsys') === false) {
        return $message;
    }

    // Bloque RPG SYSTEM bajo el post: [rpgsys]id,id,id[/rpgsys] → cartas del
    // personaje renderizadas como naipes. Zona extensible (más módulos vendrán).
    if (stripos($message, '[rpgsys') !== false) {
        $message = preg_replace_callback('#\[rpgsys\]([^\[]*)\[/rpgsys\]#is', function ($m) {
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $m[1]))));
            $ids = array_slice(array_values(array_unique($ids)), 0, 24);
            $cards = '';
            foreach ($ids as $cid) {
                $carta = function_exists('ope_rol_tecnica_by_id') ? ope_rol_tecnica_by_id($cid) : null;
                if ($carta) {
                    $cards .= ope_rol_tecnica_card_html($carta);
                }
            }
            if ($cards === '') {
                return '';
            }
            $ncards = count($ids);
            // Marcadores (sentinela) para que el hook `postbit` pueda sacar este
            // bloque FUERA del cuerpo del post y pegarlo debajo del <article>.
            // Colapsable y COLAPSADO por defecto (body con [hidden]).
            return '<!--OPERPGSYS-->'
                 . ope_rol_tecnica_card_css()
                 . '<div class="ope-rpgsys is-collapsed">'
                 . '<button type="button" class="ope-rpgsys-h" aria-expanded="false">'
                 . '<span class="ope-rpgsys-badge">RPG System</span>'
                 . '<span class="ope-rpgsys-meta">' . $ncards . ' carta' . ($ncards === 1 ? '' : 's') . '</span>'
                 . '<span class="ope-rpgsys-toggle" aria-hidden="true">Mostrar</span>'
                 . '</button>'
                 . '<div class="ope-rpgsys-b" hidden>'
                 . '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">Cartas usadas</div>'
                 . '<div class="ope-tk-deck">' . $cards . '</div></div>'
                 . '</div></div>'
                 . '<!--/OPERPGSYS-->';
        }, $message);
    }

    // Bloques con título opcional (combate / accion / tecnica).
    $blocks = array(
        'combate' => array('cls' => 'ope-cbt', 'def' => 'Estado de combate'),
        'accion'  => array('cls' => 'ope-accion', 'def' => 'Acci&oacute;n'),
        'tecnica' => array('cls' => 'ope-cbt-tk', 'def' => 'T&eacute;cnica'),
    );
    foreach ($blocks as $tag => $cfg) {
        $pattern = '#\[' . $tag . '(?:=([^\]]*))?\]((?:(?!\[/?' . $tag . ').)*?)\[/' . $tag . '\]#is';
        $guard = 0;
        while ($guard < 40 && preg_match($pattern, $message)) {
            $message = preg_replace_callback($pattern, function ($m) use ($cfg) {
                $title = isset($m[1]) ? trim(trim($m[1]), "\"'") : '';
                if ($title === '') $title = $cfg['def'];
                $body = trim($m[2]);
                return '<div class="' . $cfg['cls'] . '">'
                     . '<div class="' . $cfg['cls'] . '-h">' . $title . '</div>'
                     . '<div class="' . $cfg['cls'] . '-b">' . $body . '</div>'
                     . '</div>';
            }, $message);
            $guard++;
        }
    }

    // Chips inline: estado.
    $message = preg_replace_callback('#\[estado(?:=(positivo|negativo|neutral))?\]((?:(?!\[/?estado).)*?)\[/estado\]#is', function ($m) {
        $tipo = isset($m[1]) && $m[1] !== '' ? strtolower($m[1]) : 'negativo';
        return '<span class="ope-estado ope-estado--' . $tipo . '">' . trim($m[2]) . '</span>';
    }, $message);

    // Chips inline: dado.
    $message = preg_replace_callback('#\[dado\]((?:(?!\[/?dado).)*?)\[/dado\]#is', function ($m) {
        return '<span class="ope-dado">&#9860; ' . trim($m[1]) . '</span>';
    }, $message);

    return $message;
}

/**
 * Renderiza shortcodes del Oráculo de Viaje al mostrar posts.
 *   [viaje=123]         → bloque completo del oráculo (primer post)
 *   [viaje-cierre=123]  → post de llegada solicitada por el jugador
 */
function ope_rol_parse_viaje($message)
{
    if (stripos($message, '[viaje') === false) {
        return $message;
    }

    $message = preg_replace_callback('#\[viaje=(\d+)\]#i', function ($m) {
        $vid = (int) $m[1];
        if ($vid < 1 || !function_exists('ope_viaje_por_id')) {
            return '';
        }
        $v = ope_viaje_por_id($vid);
        if (!$v) {
            return '';
        }
        $oracle = json_decode((string) ($v['resultado_json'] ?? ''), true);
        if (!is_array($oracle)) {
            $oracle = array('tramos' => array(), 'mods' => array());
        }
        return ope_oraculo_post_html($v, $oracle);
    }, $message);

    $message = preg_replace_callback('#\[viaje-cierre=(\d+)\]#i', function ($m) {
        $vid = (int) $m[1];
        if ($vid < 1 || !function_exists('ope_viaje_por_id')) {
            return '';
        }
        $v = ope_viaje_por_id($vid);
        if (!$v) {
            return '';
        }
        $cap = 'Capitán';
        global $db;
        if ($db->table_exists('rol_personajes')) {
            $pq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . (int) ($v['pid_capitan'] ?? 0), array('limit' => 1));
            if ($db->num_rows($pq)) {
                $cap = (string) $db->fetch_field($pq, 'nombre');
            }
        }
        return ope_oraculo_cierre_post_html($v, $cap);
    }, $message);

    return $message;
}

/** Inyecta panel de viaje activo en plantilla showthread. */
function ope_rol_viaje_showthread_end()
{
    global $tid, $mybb, $thread, $posts;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    $active_pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
    if ($active_pid < 1 && $uid > 0 && function_exists('ope_rol_active_pid_for')) {
        $active_pid = ope_rol_active_pid_for($uid);
    }

    $GLOBALS['ope_viaje_panel'] = '';
    $GLOBALS['ope_viaje_scripts'] = '';

    $v = function_exists('ope_viaje_por_tid') ? ope_viaje_por_tid((int) $tid) : null;
    if (!$v) {
        return;
    }

    // Tarjeta del Oráculo como cabecera del tema (no como post).
    $card = '';
    if (function_exists('ope_oraculo_post_html')) {
        $oracle = json_decode((string) ($v['resultado_json'] ?? ''), true);
        if (!is_array($oracle)) {
            $oracle = array('tramos' => array(), 'mods' => array());
        }
        $card = '<div class="ope-viaje-header">' . ope_oraculo_post_html($v, $oracle) . '</div>';
    }

    $panel = function_exists('ope_viaje_panel_showthread')
        ? ope_viaje_panel_showthread((int) $tid, $uid, $active_pid)
        : '';

    $GLOBALS['ope_viaje_panel'] = $card . $panel;

    // Oculta el primer post (OP-Eternal) para que el oráculo no se vea como post.
    $first_pid = (int) ($thread['firstpost'] ?? 0);
    if ($first_pid > 0 && !empty($posts) && is_string($posts)) {
        $posts = preg_replace(
            '#<a name="pid' . $first_pid . '"[^>]*></a>\s*<article\b[^>]*id="post_' . $first_pid . '"[\s\S]*?</article>#',
            '',
            $posts,
            1
        );
    }

    if (function_exists('ope_oraculo_showthread_scripts')) {
        $GLOBALS['ope_viaje_scripts'] = ope_oraculo_showthread_scripts();
    }
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
        // Toggle del bloque RPG System en los posts (colapsado por defecto).
        . "(function(){document.addEventListener('click',function(e){"
        . "var h=e.target.closest&&e.target.closest('.ope-rpgsys-h');if(!h)return;"
        . "e.preventDefault();var box=h.parentNode;var b=box.querySelector('.ope-rpgsys-b');if(!b)return;"
        . "var t=h.querySelector('.ope-rpgsys-toggle');var open=b.hasAttribute('hidden');"
        . "if(open){b.removeAttribute('hidden');box.classList.remove('is-collapsed');h.setAttribute('aria-expanded','true');if(t)t.textContent='Ocultar';}"
        . "else{b.setAttribute('hidden','');box.classList.add('is-collapsed');h.setAttribute('aria-expanded','false');if(t)t.textContent='Mostrar';}});})();\n"
        . "(function(){"
        . "document.addEventListener('click',function(e){"
        . "var dot=e.target.closest&&e.target.closest('.ope-theme-dot');"
        . "if(dot){"
        . "var slug=dot.getAttribute('data-theme');if(!slug)return;"
        . "document.body.setAttribute('data-site-theme',slug);"
        . "document.cookie='ope_theme='+slug+'; path=/; max-age=31536000; SameSite=Lax';"
        . "var pop=dot.closest('.ope-theme-pop');"
        . "if(pop){var dots=pop.querySelectorAll('.ope-theme-dot');"
        . "for(var i=0;i<dots.length;i++){dots[i].classList.remove('active');}"
        . "dot.classList.add('active');pop.classList.remove('open');}"
        . "return;"
        . "}"
        . "var openPop=document.querySelector('#ope-navbar .ope-theme-pop.open');"
        . "if(openPop&&!openPop.contains(e.target)&&!(e.target.closest&&e.target.closest('.ope-theme-toggle'))){openPop.classList.remove('open');}"
        . "var openDrops=document.querySelectorAll('#ope-navbar .ope-dropdown.open');"
        . "for(var k=0;k<openDrops.length;k++){var od=openDrops[k];"
        . "if(!od.contains(e.target)&&!(e.target.closest&&e.target.closest('.ope-user-name'))&&!(e.target.closest&&e.target.closest('.ope-nav-dd-btn'))){od.classList.remove('open');}}"
        . "});"
        . "})();\n"
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

/**
 * Panel "RPG System" del editor. Vive DEBAJO del textarea del post y agrupa,
 * por ahora, dos pestañas:
 *   · Plantillas → inserta en el mensaje la plantilla elegida del personaje.
 *   · Cartas     → muestra el deck del personaje; las cartas que se marquen se
 *                  adjuntan al post (bloque [rpgsys]) y se renderizan bajo él.
 * Es una zona extensible: aquí se irán añadiendo más módulos de rol.
 */
function ope_rol_tpl_inserter_html()
{
    global $mybb, $db;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return '';
    }

    $pid = ope_rol_active_pid_for($uid);

    // ── Plantillas del personaje ──
    $tpls = $pid > 0 ? ope_rol_char_templates($pid) : array();
    $tpl_bodies = array();
    $tpl_buttons = '';
    foreach ($tpls as $i => $t) {
        $tpl_bodies[] = $t['cuerpo'];
        $tpl_buttons .= '<button type="button" class="ope-rpg-chip" data-tpl="' . $i . '">'
                     . htmlspecialchars_uni($t['nombre']) . '</button>';
    }
    if ($tpl_buttons === '') {
        $tpl_buttons = '<span class="ope-rpg-empty">No tienes plantillas. Cr&eacute;alas en tu ficha &rsaquo; Gesti&oacute;n.</span>';
    }
    $tpl_json = json_encode($tpl_bodies, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    // ── Cartas del personaje: naipe completo, seleccionable ──
    $card_css = '';
    $card_tiles = '';
    if ($pid > 0 && function_exists('ope_rol_char_tecnicas')) {
        $decks = ope_rol_char_tecnicas($pid);
        if ($decks) {
            $card_css = ope_rol_tecnica_card_css();
        }
        foreach ($decks as $tk) {
            $cid = (int) ($tk['id'] ?? 0);
            $nombre = (string) ($tk['nombre'] ?? '');
            if ($cid < 1 || $nombre === '') continue;
            $insig = !empty($tk['es_insignia']);
            $card_tiles .= '<div class="ope-rpg-cardpick" data-card-id="' . $cid . '" role="button" tabindex="0" aria-pressed="false">'
                . '<span class="ope-rpg-cardpick-check" aria-hidden="true">&#10003;</span>'
                . '<span class="ope-rpg-cardpick-name">' . ($insig ? '&#9733; ' : '') . htmlspecialchars_uni($nombre) . '</span>'
                . '<span class="ope-rpg-cardpick-pop">' . ope_rol_tecnica_card_html($tk) . '</span>'
                . '</div>';
        }
    }
    if ($card_tiles === '') {
        $card_tiles = '<span class="ope-rpg-empty">Este personaje a&uacute;n no tiene cartas de t&eacute;cnica.</span>';
    }

    // ── Ensamblado ──
    $html  = '<div class="ope-rpg" id="ope-rpg">';
    $html .= '<div class="ope-rpg-head"><span class="ope-rpg-badge">RPG System</span>'
           . '<span class="ope-rpg-hint">Se mostrar&aacute; justo debajo de tu post</span></div>';
    $html .= '<div class="ope-rpg-tabs" role="tablist">'
           . '<button type="button" class="ope-rpg-tab is-on" data-tab="plantillas">Plantillas</button>'
           . '<button type="button" class="ope-rpg-tab" data-tab="cartas">Cartas</button>'
           . ($pid > 0 ? '<button type="button" class="ope-rpg-tab" data-tab="combate">Combate</button>' : '')
           . '</div>';

    // Panel Plantillas
    $html .= '<div class="ope-rpg-panel is-on" data-panel="plantillas">';
    $html .= '<div class="ope-rpg-chips">' . $tpl_buttons . '</div>';
    $html .= '</div>';

    // Panel Cartas
    $html .= '<div class="ope-rpg-panel" data-panel="cartas">';
    $html .= $card_css;
    $html .= '<p class="ope-rpg-note">Haz clic para adjuntar una carta; pasa el cursor por encima para verla completa. Al publicar aparecer&aacute;n en un bloque <b>RPG SYSTEM</b> pegado debajo de tu post.</p>';
    $html .= '<div class="ope-rpg-cards">' . $card_tiles . '</div>';
    $html .= '<div class="ope-rpg-selinfo" data-count="0">Ninguna carta seleccionada.</div>';
    $html .= '</div>';

    // ── Panel Combate (AV-01) ──
    if ($pid > 0) {
        $cbt_pv = 0;
        $cbt_en = 0;
        $cbt_pa = 2;
        $cbtq = $db->simple_select('rol_personajes', 'pv_max, en_max, pa_por_turno', "pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($cbtq)) {
            $cbt_row = $db->fetch_array($cbtq);
            $cbt_pv = (int) ($cbt_row['pv_max'] ?? 0);
            $cbt_en = (int) ($cbt_row['en_max'] ?? 0);
            $cbt_pa = (int) ($cbt_row['pa_por_turno'] ?? 2);
        }

        // Buscar último snapshot del personaje en este hilo para PV/EN actuales
        $tid = (int) ($mybb->input['tid'] ?? 0);
        if ($tid < 1 && isset($mybb->input['pid'])) {
            $ep = $db->simple_select('posts', 'tid', 'pid = ' . (int) $mybb->input['pid'], array('limit' => 1));
            if ($db->num_rows($ep)) {
                $tid = (int) $db->fetch_field($ep, 'tid');
            }
        }
        if ($tid > 0) {
            $prev_snap = $db->query("
                SELECT s.pv_actual, s.en_actual FROM {$db->table_prefix}rol_post_snapshot s
                JOIN {$db->table_prefix}posts p ON s.pid = p.pid
                WHERE s.personaje_pid = {$pid} AND p.tid = {$tid}
                ORDER BY p.dateline DESC LIMIT 1
            ");
            if ($prev_snap && $db->num_rows($prev_snap) > 0) {
                $ps_row = $db->fetch_array($prev_snap);
                if (isset($ps_row['pv_actual']) && $ps_row['pv_actual'] !== null) {
                    $cbt_pv = (int) $ps_row['pv_actual'];
                }
                if (isset($ps_row['en_actual']) && $ps_row['en_actual'] !== null) {
                    $cbt_en = (int) $ps_row['en_actual'];
                }
            }
        }

        // Estados alterados
        $estados_html = '';
        if (function_exists('ope_combat_estados')) {
            $estados_cat = ope_combat_estados();
            foreach ($estados_cat as $ek => $ev) {
                $enom = htmlspecialchars_uni((string) ($ev['nombre'] ?? $ek));
                $estados_html .= '<label><input type="checkbox" class="ope-rpg-cbt-est" value="' . $ek . '"> ' . $enom . '</label>';
            }
        }

        // Modificadores de stats
        $stats_html = '';
        if (function_exists('ope_rol_stats')) {
            $stat_groups = ope_rol_stats();
            foreach ($stat_groups as $grupo) {
                $stats_html .= '<div class="ope-rpg-cbt-modgroup">';
                $stats_html .= '<span class="ope-rpg-cbt-sub">' . htmlspecialchars_uni($grupo['label']) . '</span>';
                foreach ($grupo['stats'] as $ab => $nombre_stat) {
                    $stats_html .= '<label>' . htmlspecialchars_uni($ab)
                        . ' <input type="number" class="ope-rpg-cbt-mod" data-stat="' . $ab . '" value="0" step="1"></label>';
                }
                $stats_html .= '</div>';
            }
        }

        $html .= '<div class="ope-rpg-panel" data-panel="combate">';
        $html .= '<div class="ope-rpg-cbt-stats">';
        $html .= '<div class="ope-rpg-cbt-row">';
        $html .= '<label>PV <input type="number" id="ope_cbt_pv" value="' . $cbt_pv . '" min="0"></label>';
        $html .= '<label>EN <input type="number" id="ope_cbt_en" value="' . $cbt_en . '" min="0"></label>';
        $html .= '<label class="ope-rpg-cbt-pa">PA/turno <span id="ope_cbt_pa">' . $cbt_pa . '</span></label>';
        $html .= '</div>';
        $html .= '<div class="ope-rpg-cbt-estados">';
        $html .= '<span class="ope-rpg-cbt-label">Estados <em>(m&aacute;x 3)</em></span>';
        $html .= $estados_html;
        $html .= '</div>';
        $html .= '<div class="ope-rpg-cbt-mods">';
        $html .= '<span class="ope-rpg-cbt-label">Modificadores</span>';
        $html .= $stats_html;
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<p class="ope-rpg-cbt-note">Los valores se guardan en el snapshot de este post.</p>';
        $html .= '</div>';
    }

    $html .= '</div>'; // .ope-rpg

    // ── Script ──
    $html .= '<script>(function(){'
        . 'var root=document.getElementById("ope-rpg");if(!root)return;'
        . 'var TPL=' . $tpl_json . ';'
        . 'var ta=document.getElementById("message");'
        . 'function ins(pre,post){post=post||"";var ed=window.MyBBEditor;'
        . 'if(ed&&typeof ed.insert==="function"){if(post){ed.insert(pre,post);}else{ed.insert(pre);}return;}'
        . 'if(!ta)return;'
        . 'var s=ta.selectionStart||0,e=ta.selectionEnd||0,v=ta.value,sel=v.slice(s,e);'
        . 'var t=pre+sel+post;ta.value=v.slice(0,s)+t+v.slice(e);ta.focus();'
        . 'var c=s+pre.length+sel.length;try{ta.setSelectionRange(c,c);}catch(x){}}'
        // ── selección de cartas ──
        . 'var info=root.querySelector(".ope-rpg-selinfo");'
        . 'function selIds(){var out=[];root.querySelectorAll(".ope-rpg-cardpick.is-sel").forEach(function(c){out.push(c.getAttribute("data-card-id"));});return out;}'
        . 'function refresh(){var n=selIds().length;if(info){info.setAttribute("data-count",n);info.textContent=n?(n+" carta"+(n>1?"s":"")+" se mostrar"+(n>1?"\u00e1n":"\u00e1")+" bajo tu post."):"Ninguna carta seleccionada.";}}'
        // ── delegación de clicks ──
        . 'root.addEventListener("click",function(ev){'
        . 'var tab=ev.target.closest(".ope-rpg-tab");'
        . 'if(tab){var name=tab.getAttribute("data-tab");'
        . 'root.querySelectorAll(".ope-rpg-tab").forEach(function(t){t.classList.toggle("is-on",t===tab);});'
        . 'root.querySelectorAll(".ope-rpg-panel").forEach(function(p){p.classList.toggle("is-on",p.getAttribute("data-panel")===name);});return;}'
        . 'var card=ev.target.closest(".ope-rpg-cardpick");'
        . 'if(card&&card.hasAttribute("data-card-id")){var on=!card.classList.contains("is-sel");card.classList.toggle("is-sel",on);card.setAttribute("aria-pressed",on?"true":"false");refresh();return;}'
        . 'var chip=ev.target.closest(".ope-rpg-chip");if(!chip)return;'
        . 'if(chip.hasAttribute("data-tpl")){var b=TPL[chip.getAttribute("data-tpl")];if(b!=null)ins(b,"");return;}'
        . 'var t=chip.getAttribute("data-insert");if(t!=null)ins(t,"");});'
        // ── estados: máximo 3 activos ──
        . 'root.addEventListener("change",function(ev){'
        . 'var cb=ev.target.closest(".ope-rpg-cbt-est");'
        . 'if(!cb)return;'
        . 'var all=root.querySelectorAll(".ope-rpg-cbt-est:checked");'
        . 'if(all.length>3){cb.checked=false;alert("M\u00e1ximo 3 estados activos.");}'
        . '});'
        // ── preselección desde un [rpgsys] ya existente (editar post) ──
        . 'function editorVal(){var ed=window.MyBBEditor;if(ed&&typeof ed.val==="function"){try{return ed.val();}catch(x){}}return ta?ta.value:"";}'
        . 'var m=editorVal().match(/\\[rpgsys\\]([^\\[]*)\\[\\/rpgsys\\]/i);'
        . 'if(m){var ids=m[1].split(",").map(function(s){return s.trim();});'
        . 'root.querySelectorAll(".ope-rpg-cardpick").forEach(function(c){if(ids.indexOf(c.getAttribute("data-card-id"))>-1){c.classList.add("is-sel");c.setAttribute("aria-pressed","true");}});}'
        . 'refresh();'
        // ── al enviar: reconstruir el bloque [rpgsys] con la selección ──
        . 'var form=ta?ta.form:null;'
        . 'function applyBlock(){'
        . 'var ids=selIds();var ed=window.MyBBEditor;'
        . 'var cur=(ed&&typeof ed.val==="function")?ed.val():(ta?ta.value:"");'
        . 'cur=cur.replace(/\\n*\\[rpgsys\\][\\s\\S]*?\\[\\/rpgsys\\]/gi,"");'
        . 'if(ids.length){cur=cur.replace(/\\s+$/,"")+"\\n\\n[rpgsys]"+ids.join(",")+"[/rpgsys]";}'
        // Escribe en el editor SCEditor (si existe) y SIEMPRE en el textarea real,
        // que es el campo que se envía; así preview y publicación llevan el bloque.
        . 'if(ed&&typeof ed.val==="function"){try{ed.val(cur);}catch(x){}}'
        . 'if(ta){ta.value=cur;}'
        . '}'
        . 'if(form){form.addEventListener("submit",applyBlock,true);form.addEventListener("submit",applyBlock);}'
        . '})();</script>';

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

// ─────────────────────────────────────────────────────────────
// Cartas de Técnica (INI-03): lectura del deck + render de carta.
// ─────────────────────────────────────────────────────────────

/**
 * Deck de cartas de técnica de un personaje, ordenado (insignia primero,
 * luego por tier descendente y orden manual). Devuelve array de filas con
 * `tags` ya decodificado a array.
 */
function ope_rol_char_tecnicas($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('rol_tecnicas')) {
        return $out;
    }
    $q = $db->simple_select(
        'rol_tecnicas',
        '*',
        "pid = {$pid}",
        array('order_by' => 'es_insignia DESC, tier DESC, disporder ASC, id ASC')
    );
    while ($r = $db->fetch_array($q)) {
        $tags = json_decode((string) $r['tags'], true);
        $r['tags'] = is_array($tags) ? $tags : array();
        $out[] = $r;
    }
    return $out;
}

/**
 * Una carta de técnica por su id (rol_tecnicas.id), con `tags` decodificado.
 * Se usa al renderizar el bloque [rpgsys] bajo los posts.
 */
function ope_rol_tecnica_by_id($id)
{
    global $db;
    $id = (int) $id;
    if ($id < 1 || !$db->table_exists('rol_tecnicas')) {
        return null;
    }
    $q = $db->simple_select('rol_tecnicas', '*', "id = {$id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $r = $db->fetch_array($q);
    $tags = json_decode((string) $r['tags'], true);
    $r['tags'] = is_array($tags) ? $tags : array();
    return $r;
}

/**
 * HTML de una carta de técnica (formato "naipe" reutilizable en la ficha y
 * en el creador del staff). $carta['tags'] debe venir ya como array.
 */
function ope_rol_tecnica_card_html(array $carta)
{
    $tiers  = function_exists('ope_rol_tecnica_tiers') ? ope_rol_tecnica_tiers() : array();
    $tier   = (int) ($carta['tier'] ?? 1);
    $romano = isset($tiers[$tier]) ? $tiers[$tier]['romano'] : (string) $tier;

    $nombre    = htmlspecialchars_uni((string) ($carta['nombre'] ?? 'Sin nombre'));
    $insignia  = !empty($carta['es_insignia']);
    $pa        = (int) ($carta['coste_pa'] ?? 0);
    $en        = (int) ($carta['coste_en'] ?? 0);
    $reposo    = (int) ($carta['reposo'] ?? 0);
    $dados     = trim((string) ($carta['dados'] ?? ''));
    $req       = trim((string) ($carta['requisito_stats'] ?? ''));
    $desc      = trim((string) ($carta['descripcion'] ?? ''));

    $flat = function_exists('ope_rol_tecnica_tags_flat')
        ? ope_rol_tecnica_tags_flat(is_array($carta['tags'] ?? null) ? $carta['tags'] : array())
        : array();

    $chips = '';
    foreach ($flat as $f) {
        $chips .= '<span class="ope-tk-chip" style="--tk:' . $f['accent'] . '">'
                . htmlspecialchars_uni($f['texto']) . '</span>';
    }

    $html  = '<article class="ope-tk ope-tk-t' . $tier . ($insignia ? ' is-insignia' : '') . '">';
    $html .= '<div class="ope-tk-h">';
    $html .= '<span class="ope-tk-tier" title="Tier ' . $romano . '">' . $romano . '</span>';
    $html .= '<div class="ope-tk-tt"><h4 class="ope-tk-name">' . $nombre . '</h4>';
    if ($insignia) {
        $html .= '<span class="ope-tk-badge">&#9733; Insignia</span>';
    }
    $html .= '</div></div>';

    if ($chips !== '') {
        $html .= '<div class="ope-tk-chips">' . $chips . '</div>';
    }

    if ($desc !== '') {
        $html .= '<p class="ope-tk-desc">' . nl2br(htmlspecialchars_uni($desc)) . '</p>';
    }

    $html .= '<div class="ope-tk-stats">';
    $html .= '<span class="ope-tk-stat"><b>' . $pa . '</b><small>PA</small></span>';
    $html .= '<span class="ope-tk-stat"><b>' . $en . '</b><small>EN</small></span>';
    $html .= '<span class="ope-tk-stat"><b>' . $reposo . '</b><small>Reposo</small></span>';
    if ($dados !== '') {
        $html .= '<span class="ope-tk-stat ope-tk-dice"><b>' . htmlspecialchars_uni($dados) . '</b><small>Dados</small></span>';
    }
    $html .= '</div>';

    if ($req !== '') {
        $html .= '<div class="ope-tk-req"><span class="ope-tk-req-l">Requisitos</span> '
               . htmlspecialchars_uni($req) . '</div>';
    }

    $html .= '</article>';
    return $html;
}

/**
 * Biblioteca de cartas (mybb_rol_cartas) — cartas creadas sin personaje.
 * Devuelve filas con `tags` ya decodificado. Admite búsqueda y filtro de tier.
 */
function ope_rol_cartas_lib($buscar = '', $tier = 0)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_cartas')) {
        return $out;
    }
    $where = '1=1';
    $buscar = trim((string) $buscar);
    if ($buscar !== '') {
        $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    }
    $tier = (int) $tier;
    if ($tier >= 1 && $tier <= 5) {
        $where .= " AND tier = {$tier}";
    }
    $q = $db->simple_select('rol_cartas', '*', $where, array('order_by' => 'tier DESC, nombre ASC', 'limit' => 500));
    while ($r = $db->fetch_array($q)) {
        $tags = json_decode((string) $r['tags'], true);
        $r['tags'] = is_array($tags) ? $tags : array();
        $out[] = $r;
    }
    return $out;
}

/**
 * Prompt maestro (Markdown) para que una IA aprenda TODO el sistema de Cartas
 * de Técnica (INI-03) y devuelva una carta en el YAML que el creador entiende
 * y autorrellena. Fuente única: se usa en el modal de ayuda de crear-cartas.php.
 */
function ope_rol_tecnica_ia_prompt()
{
    static $md = null;
    if ($md !== null) {
        return $md;
    }
    $md = <<<'MD'
# GUÍA MAESTRA — Diseño de Cartas de Técnica · One Piece Eternal (INI-03)

> ROL: Actúas como diseñador oficial de mecánicas del foro de rol *One Piece Eternal*.
> OBJETIVO: A partir del concepto que te describa el jugador, diseñas UNA Carta de
> Técnica coherente, equilibrada y evocadora, y la devuelves SIEMPRE en el bloque
> YAML del apartado 12 (es lo único que el sistema sabe leer y autorrellenar).
> No inventes tags, valores ni categorías fuera de las listadas aquí.

---

## 1. Contexto del sistema (imprescindible)
One Piece Eternal es un foro de rol por turnos ambientado en el mundo de One Piece.
Cada personaje tiene un **rango** (de F a M+) que resume su poder global, y **12
estadísticas** repartidas en 3 pilares. En combate, cada post equivale a un turno y
el personaje gasta **PA (Puntos de Acción)** y **EN (Energía)** para ejecutar cartas.

Una **Carta de Técnica** representa un movimiento, ataque o habilidad CON NOMBRE del
personaje (un tajo, un disparo de fruta, una postura defensiva, un impulso de
velocidad...). No son habilidades genéricas: son el repertorio firmado del personaje,
lo que "invoca" al postear en combate. Todo lo mecánico de la carta se define con
**6 categorías de tags** + un puñado de valores numéricos (Tier, PA, EN, Reposo,
Requisitos, Dados). Nada más.

Reglas de oro que nunca debes romper:
- Mínimo **un tag de Tipo y uno de Alcance** en cada carta (son obligatorios).
- Máximo **3 tags de Estilo** y máximo **3 tags de Estado Alterado** por carta.
- Si una categoría no aplica, usa el tag `Ninguno` (existe en Elemento, Estado y Ejecución).
- Ajusta SIEMPRE PA/EN/Reposo/Dados al presupuesto del Tier elegido (apartado 8).
- El staff veta combinaciones incoherentes: sé lógico (no pongas `Curado` en una carta `Ofensiva` de fuego, por ejemplo).

---

## 2. Las 12 estadísticas (usa estas siglas en requisitos y dados)
Pilar CUERPO: **FUE** (Fuerza), **DES** (Destreza), **VIG** (Vigor), **AGI** (Agilidad).
Pilar MENTE: **INT** (Intelecto), **ING** (Ingenio), **CON** (Concentración), **PER** (Percepción).
Pilar ESPÍRITU: **CAR** (Carisma), **CTR** (Control), **VOL** (Voluntad), **FE** (Fe/Determinación).

Guía rápida de qué stat suele escalar cada carta:
- Golpes físicos / cuerpo a cuerpo brutos → FUE.
- Esgrima, tiro de precisión, técnica fina → DES.
- Aguante, defensas corporales, resistencia → VIG.
- Velocidad, esquivas, movilidad, ataques rápidos → AGI.
- Tácticas, trampas, ingenio de combate → INT / ING.
- Control de poderes (frutas Logia/Paramecia proyectadas), puntería sostenida → CTR / CON.
- Percepción, Haki de Observación, reacción → PER.
- Haki del Conquistador, presencia, intimidación → CAR / VOL.

---

## 3. CATEGORÍA 1 — ESTILO (1 a 3 tags; ¿de dónde nace el poder?)
Se pueden combinar si el personaje canaliza varias fuentes a la vez.
- `Propio`: destreza física directa del personaje — artes marciales propias, esgrima, tiro, acrobacias, habilidades mundanas.
- `Haki`: canaliza Haki de Armadura (Busoshoku), de Observación (Kenbunshoku) o del Conquistador (Haoshoku).
- `Akuma`: emplea transformaciones o poderes de una Fruta del Diablo.

Ejemplo de combинación: un puñetazo de fruta imbuido en Haki = `estilo: ["Akuma", "Haki"]`.
(Estilos avanzados del canon como Rokushiki, Gyojin Karate o Electro requieren aprobación aparte; NO los uses aquí.)

---

## 4. CATEGORÍA 2 — TIPO (exactamente 1; función táctica)
- `Ofensiva`: causa daño directo a uno o varios oponentes.
- `Defensiva`: reduce, mitiga o anula daño recibido.
- `Soporte`: beneficia a aliados (curación, limpieza de estados, buffs de stats).
- `Control`: altera el campo o restringe al enemigo sin daño directo (empujes, inmovilizaciones).
- `Movilidad`: mejora el desplazamiento (saltos enormes, impulsos veloces).
- `Utilidad`: acciones no combativas (rastreo, iluminación, creación de objetos, comunicación).

---

## 5. CATEGORÍA 3 — ALCANCE (exactamente 1; ¿hasta dónde llega?)
- `Cuerpo a Cuerpo`: contacto directo (0–2 metros).
- `Corto Alcance`: hasta la distancia terrestre de tu AGI.
- `Medio Alcance`: hasta el doble de la distancia terrestre de tu AGI.
- `Largo Alcance`: hasta el cuádruple de la distancia terrestre de tu AGI.
- `Área`: afecta a todos los objetivos dentro de un radio.
- `Línea`: afecta a los objetivos en una trayectoria recta.
- `Personal`: solo afecta al propio usuario (típico de buffs, defensas y movilidad).

---

## 6. CATEGORÍA 4 — ELEMENTO (exactamente 1; afinidad; usa `Ninguno` si es impacto físico)
`Ninguno`, `Fuego`, `Hielo`, `Electricidad`, `Agua`, `Tierra`, `Aire / Viento`, `Luz`, `Oscuridad`, `Planta`, `Veneno`, `Sónico`, `Espiritual`.

Coherencia elemento ⇄ estado sugerida:
- Fuego → `Quemado`; Hielo/Agua → `Inmovilizado`/`Paralizado`; Electricidad → `Paralizado`/`Aturdido`;
- Veneno → `Sangrado`/daño continuo; Sónico → `Aturdido`/`Confuso`; Luz → `Cegado`; Oscuridad → `Cegado`/`Confuso`.

---

## 7. CATEGORÍA 5 — ESTADO ALTERADO (0 a 3; efectos al impactar; usa `Ninguno` si no aplica)
NEGATIVOS (debuffs):
- `Aturdido`: el objetivo pierde -1 PA en su próximo post.
- `Quemado`: sufre daño por fuego al inicio de cada turno.
- `Paralizado`: sus PA de movimiento se reducen a 0 durante 1 post.
- `Sangrado`: daño leve continuo por cortes.
- `Cegado`: penalizador descriptivo a percepción y precisión.
- `Confuso`: sus cartas de técnica cuestan +1 PA temporalmente.
- `Derribado`: cae al suelo (gasta 1 PA para levantarse).
- `Inmovilizado`: no puede abandonar su posición actual.
POSITIVOS (buffs; propios de cartas Defensiva/Soporte):
- `Fortalecido`: incrementa temporalmente el daño infligido.
- `Protegido`: reduce el daño recibido en un valor plano.
- `Acelerado`: otorga +1 PA en el próximo turno.
- `Curado`: restaura PV o limpia estados negativos.
- `Revitalizado`: recupera EN (energía).

---

## 8. CATEGORÍA 6 — EJECUCIÓN (0 a varios; método/propiedad; usa `Ninguno` si es estándar)
- `Ninguno`: se activa y resuelve normal en tu turno.
- `Cargada`: declaras la carga en un post y resuelves en el siguiente. Pega más y es difícil de bloquear. Justifica dados por encima de la media del Tier.
- `Instantánea`: se usa fuera de tu turno como reacción inmediata a un ataque enemigo.
- `Canalizada`: exige mantener concentración (invertir PA residuales en posts consecutivos) para extender el efecto.
- `Reacción`: solo se activa como respuesta a una condición del rival (ej. contraataque tras bloqueo exitoso).
- `Combo`: mejora mucho si se usa inmediatamente después de otra carta declarada.
- `Perforante`: ignora parte del blindaje/bloqueo del oponente.
- `Frenesí`: múltiples golpes rápidos de bajo daño, útiles contra objetivos muy evasivos.

---

## 9. TIER — potencia de la carta (elige I a V y ajusta el presupuesto)
El Tier fija coste en PP (Puntos de Progresión) y el presupuesto de poder. Mantén PA/EN/Reposo/Dados dentro de su fila:

| Tier | Rango recom. | PP | PA típico | EN típico | Reposo | Dados de daño |
|------|--------------|----|-----------|-----------|--------|----------------|
| I    | F – D        | 5  | 1 – 2     | 5 – 10    | 0 posts | 1d6 a 1d8 + stat |
| II   | D – C        | 8  | 2         | 10 – 15   | 1 post  | 1d10 a 2d6 + stat |
| III  | B – A        | 12 | 2 – 3     | 15 – 25   | 1 – 2 posts | 2d8 + stat |
| IV   | S            | 18 | 3 – 4     | 25 – 40   | 2 posts | 3d8 a 4d6 + stat |
| V    | SS – M+      | 25 | 4 – 5     | 40 – 60   | 3 posts / Escena | 4d8 a 5d6 + stat |

Heurística de subida de coste:
- Más alcance (Área/Línea/Largo) ⇒ sube EN y/o Reposo.
- Más estados alterados o buffs potentes ⇒ sube EN y/o Reposo.
- `Cargada`/`Perforante`/`Frenesí` ⇒ suele subir EN o Reposo.
- El PA nunca debe superar el máximo del Tier.
- Cartas `Personal`/`Soporte`/`Movilidad`/`Utilidad` normalmente NO llevan dados de daño (deja `dados` acorde al efecto, o algo tipo `—`).

---

## 10. REQUISITOS y equivalencias numéricas
- El requisito indica el **rango MÍNIMO** de una stat (ej. `FUE C`). Un rango mayor cumple de sobra.
- Escribe los requisitos como texto: `"AGI D, FUE E"`. Puedes añadir requisitos narrativos separados por comas: `Fruta de Fuego activa`, `Busoshoku Haki despertado`, `Arma de fuego equipada`, `Espada de rango F`.
- Equivalencia letra→número (para calcular los dados `NdX + stat`): **F=1, E=2, D=3, C=4, B=5, A=6, S=7, SS=8, M=9, M+=10**.
  - Ejemplo: técnica `4d6 + FUE` con `FUE A` (=6) se tira en el foro como `4d6 + 6`.
- Coherencia de estilo → requisito: si usas `estilo: ["Akuma"]` añade el requisito de fruta; si usas `["Haki"]`, el requisito de Haki correspondiente.

---

## 11. EVOLUCIÓN, INSIGNIA y APRENDIZAJE (contexto de diseño)
- Los personajes empiezan con **0 cartas**; todas se compran con PP.
- Una técnica común evoluciona **una única vez** (subir 1 Tier, añadir un tag, o reducir EN/Reposo).
- Cada personaje tiene UNA **Técnica Insignia** con evolución ilimitada (esto lo marca el staff al asignar, no va en el YAML).
- No diseñes cartas que dependan de otras cartas salvo con el tag `Combo`.

---

## 12. FORMATO DE SALIDA OBLIGATORIO (devuélvelo TAL CUAL, en un bloque ```yaml```)
Rellena TODOS los campos. `tier` como número romano o arábigo. Los tags multi como lista `["A","B"]`; los single como texto entre comillas. Usa EXACTAMENTE los nombres de tag de esta guía.

```yaml
nombre: "Nombre evocador de la técnica"
tier: II
tags:
  estilo: ["Propio"]           # 1 a 3 de: Propio, Haki, Akuma
  tipo: "Ofensiva"             # 1 de: Ofensiva, Defensiva, Soporte, Control, Movilidad, Utilidad
  alcance: "Cuerpo a Cuerpo"   # 1 de: Cuerpo a Cuerpo, Corto Alcance, Medio Alcance, Largo Alcance, Área, Línea, Personal
  elemento: "Ninguno"          # 1 de la lista de elementos (o Ninguno)
  estado: ["Ninguno"]          # 0 a 3 de la lista de estados (o Ninguno)
  ejecucion: ["Ninguno"]       # 0 a varios de la lista de ejecución (o Ninguno)
coste_pa: 2                    # entero dentro del rango del Tier
coste_en: 12                   # entero dentro del rango del Tier
reposo: 1                      # posts de reposo antes de reutilizarla
requisito_stats: "DES E, FUE E"
dados: "1d10 + DES"            # o "—" si no inflige daño
descripcion: "Descripción narrativa y estética: qué se ve, cómo se ejecuta y qué sensación transmite (2 a 4 frases)."
```

---

## 13. EJEMPLOS RESUELTOS

### Ejemplo A — Tier I, físico repetible
```yaml
nombre: "Tajo Directo"
tier: I
tags:
  estilo: ["Propio"]
  tipo: "Ofensiva"
  alcance: "Cuerpo a Cuerpo"
  elemento: "Ninguno"
  estado: ["Ninguno"]
  ejecucion: ["Ninguno"]
coste_pa: 1
coste_en: 5
reposo: 0
requisito_stats: "DES E, FUE E"
dados: "1d6 + DES"
descripcion: "Un corte frontal de arriba a abajo. Sencillo, pero rápido y efectivo para mantener la presión sobre el rival."
```

### Ejemplo B — Tier II, fruta de fuego cargada
```yaml
nombre: "Proyectil Ígneo"
tier: II
tags:
  estilo: ["Akuma"]
  tipo: "Ofensiva"
  alcance: "Corto Alcance"
  elemento: "Fuego"
  estado: ["Quemado"]
  ejecucion: ["Cargada"]
coste_pa: 2
coste_en: 12
reposo: 1
requisito_stats: "CTR D, INT E, Fruta de Fuego activa"
dados: "1d10 + CTR"
descripcion: "El usuario concentra fuego en la palma durante un instante y lo dispara como una esfera de calor que estalla al impacto, prendiendo al objetivo."
```

### Ejemplo C — Tier III, Akuma + Haki, control de área
```yaml
nombre: "Impacto Terrestre Imbuido"
tier: III
tags:
  estilo: ["Akuma", "Haki"]
  tipo: "Control"
  alcance: "Área"
  elemento: "Tierra"
  estado: ["Derribado"]
  ejecucion: ["Ninguno"]
coste_pa: 3
coste_en: 22
reposo: 2
requisito_stats: "FUE C, VOL D, Busoshoku Haki despertado"
dados: "2d6 + FUE"
descripcion: "Golpea el suelo imbuyendo la fuerza del Haki, provocando una onda expansiva que fractura el terreno y derriba a los oponentes adyacentes."
```

### Ejemplo D — Tier I, defensa personal (sin daño)
```yaml
nombre: "Guardia de Acero"
tier: I
tags:
  estilo: ["Propio"]
  tipo: "Defensiva"
  alcance: "Personal"
  elemento: "Ninguno"
  estado: ["Protegido"]
  ejecucion: ["Instantánea"]
coste_pa: 1
coste_en: 6
reposo: 1
requisito_stats: "VIG E"
dados: "—"
descripcion: "El personaje planta los pies y cruza los brazos para absorber un golpe entrante, reduciendo el daño recibido en el intercambio."
```

### Ejemplo E — Tier IV, velocidad y frenesí
```yaml
nombre: "Danza de las Mil Cuchillas"
tier: IV
tags:
  estilo: ["Propio", "Haki"]
  tipo: "Ofensiva"
  alcance: "Corto Alcance"
  elemento: "Ninguno"
  estado: ["Sangrado"]
  ejecucion: ["Frenesí", "Perforante"]
coste_pa: 4
coste_en: 34
reposo: 2
requisito_stats: "AGI S, DES A, Busoshoku Haki despertado"
dados: "3d8 + AGI"
descripcion: "El espadachín se convierte en un borrón de acero, encadenando decenas de cortes imposibles de seguir que atraviesan la guardia enemiga y abren heridas sangrantes."
```

---

Ahora espera a que el jugador te describa el concepto de su técnica y devuelve la
carta cumpliendo TODO lo anterior, únicamente en el bloque ```yaml``` del apartado 12.
MD;
    return $md;
}

/**
 * CSS del creador/asignador de cartas (chips de tags, tier, preview, modal
 * de ayuda IA). Scopeado a body.ope-pg-gestionar-cartas para reutilizarlo en
 * crear-cartas.php y asignar-cartas.php. Se emite una sola vez.
 */
function ope_rol_tecnica_forge_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    $s = 'body.ope-pg-gestionar-cartas ';
    return '<style id="ope-forge-css">'
        . $s . '.gc-help-btn{display:inline-flex;align-items:center;gap:7px}'
        . $s . '.gc-layout{display:grid;grid-template-columns:1fr;gap:16px}'
        . '@media(min-width:1080px){' . $s . '.gc-layout{grid-template-columns:minmax(0,1.35fr) minmax(300px,.65fr);align-items:start}}'
        . $s . '.gc-preview-col{position:sticky;top:66px}'
        . $s . '.gc-preview-lbl{font-family:var(--mono);font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--paper-dim);margin:0 0 8px}'
        . $s . '.tk-catrow{border:2px solid #000;background:var(--iron);margin:0 0 12px}'
        . $s . '.tk-cathead{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;padding:8px 12px;border-bottom:2px solid #000;background:var(--iron-edge)}'
        . $s . '.tk-cathead .cn{font-family:var(--disp);font-weight:800;font-size:.98rem;text-transform:uppercase;color:var(--paper);letter-spacing:.4px}'
        . $s . '.tk-cathead .cq{font-family:var(--mono);font-size:.58rem;color:var(--paper-dim)}'
        . $s . '.tk-cathead .cmax{margin-left:auto;font-family:var(--mono);font-size:.55rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--iron);background:var(--tk,var(--ember));border:1px solid #000;padding:1px 7px}'
        . $s . '.tk-chips{display:flex;flex-wrap:wrap;gap:7px;padding:11px 12px}'
        . $s . '.tk-chip{position:relative;cursor:pointer;user-select:none}'
        . $s . '.tk-chip input{position:absolute;opacity:0;pointer-events:none}'
        . $s . '.tk-chip span{display:inline-block;font-family:var(--mono);font-size:.68rem;font-weight:700;letter-spacing:.3px;color:var(--paper);background:var(--iron-plate);border:2px solid #000;padding:5px 11px;transition:transform .1s,box-shadow .1s,background .1s,color .1s}'
        . $s . '.tk-chip:hover span{transform:translate(-1px,-1px);box-shadow:2px 2px 0 #000}'
        . $s . '.tk-chip input:checked+span{background:var(--tk,var(--ember));color:var(--iron);box-shadow:2px 2px 0 #000}'
        . $s . '.tk-chip input:disabled+span{opacity:.4;cursor:not-allowed}'
        . $s . '.tier-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}'
        . $s . '.tier-chip{cursor:pointer;user-select:none}'
        . $s . '.tier-chip input{position:absolute;opacity:0;pointer-events:none}'
        . $s . '.tier-chip .tc{display:flex;flex-direction:column;align-items:center;gap:2px;padding:9px 4px;border:2px solid #000;background:var(--iron-plate);transition:transform .1s,box-shadow .1s}'
        . $s . '.tier-chip .tc b{font-family:var(--disp);font-weight:900;font-size:1.25rem;color:var(--paper);line-height:1}'
        . $s . '.tier-chip .tc small{font-family:var(--mono);font-size:.5rem;font-weight:700;text-transform:uppercase;color:var(--ash);text-align:center;line-height:1.2}'
        . $s . '.tier-chip:hover .tc{transform:translate(-1px,-1px);box-shadow:2px 2px 0 #000}'
        . $s . '.tier-chip input:checked+.tc{background:var(--ember);box-shadow:3px 3px 0 #000}'
        . $s . '.tier-chip input:checked+.tc b,' . $s . '.tier-chip input:checked+.tc small{color:var(--iron)}'
        . $s . '.gc-insignia{display:flex;align-items:center;gap:9px;padding:10px 12px;border:2px dashed var(--ember);background:var(--iron)}'
        . $s . '.gc-insignia input{width:17px;height:17px;accent-color:var(--ember)}'
        . $s . '.gc-insignia span{font-family:var(--mono);font-size:.66rem;color:var(--paper-dim);line-height:1.4}'
        . $s . '.gc-insignia b{color:var(--ember-hi)}'
        . $s . '.deck-item{position:relative}'
        . $s . '.deck-tools{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}'
        . $s . '.deck-tools form{margin:0}'
        . $s . '.tier-hint{font-family:var(--mono);font-size:.6rem;color:var(--paper-dim);padding:2px 0 0;line-height:1.4}'
        . $s . '.btn-danger{background:var(--crack);color:#fff;border-color:#000}'
        . $s . '.btn-danger:hover{background:var(--red-hi,var(--crack));transform:translate(-2px,-2px);box-shadow:4px 4px 0 #000}'
        . $s . '.gc-warn{margin:0 0 16px;padding:12px 16px;border:2px solid var(--crack);background:var(--iron-plate);font-family:var(--mono);font-size:.74rem;color:var(--paper);line-height:1.5;box-shadow:3px 3px 0 #000}'
        . $s . '.gc-modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:2000;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;overflow:auto}'
        . $s . '.gc-modal-ov[hidden]{display:none}'
        . $s . '.gc-modal{width:min(880px,100%);background:var(--iron-plate);border:2px solid #000;box-shadow:8px 8px 0 #000}'
        . $s . '.gc-modal-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:2px solid #000;background:var(--iron-edge)}'
        . $s . '.gc-modal-h h2{margin:0;font-family:var(--disp);font-weight:800;font-size:1.25rem;text-transform:uppercase;color:var(--paper);letter-spacing:.4px}'
        . $s . '.gc-modal-h .x{background:transparent;border:2px solid var(--rivet);color:var(--paper);font-size:1.1rem;line-height:1;width:34px;height:34px;cursor:pointer}'
        . $s . '.gc-modal-h .x:hover{background:var(--paper);color:var(--iron);border-color:#000}'
        . $s . '.gc-modal-b{padding:16px}'
        . $s . '.gc-tabs{display:flex;gap:6px;margin-bottom:12px}'
        . $s . '.gc-tab{font-family:var(--mono);font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--paper-dim);background:var(--iron);border:2px solid #000;padding:7px 13px;cursor:pointer}'
        . $s . '.gc-tab.on{background:var(--ember);color:var(--iron)}'
        . $s . '.gc-tabpane{display:none}' . $s . '.gc-tabpane.on{display:block}'
        . $s . '.gc-modal-b p.lead{font-family:var(--mono);font-size:.72rem;color:var(--paper-dim);line-height:1.55;margin:0 0 12px}'
        . $s . '.gc-md{width:100%;min-height:320px;max-height:52vh;background:var(--iron);color:var(--paper);border:2px solid #000;padding:12px;font-family:var(--mono);font-size:.72rem;line-height:1.5;resize:vertical;white-space:pre}'
        . $s . '#gcYaml{white-space:pre;min-height:260px}'
        . $s . '.gc-copybar{display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap}'
        . $s . '.gc-copied{font-family:var(--mono);font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--patina-hi);opacity:0;transition:opacity .2s}'
        . $s . '.gc-copied.show{opacity:1}'
        . $s . '.gc-fillerr{font-family:var(--mono);font-size:.64rem;font-weight:700;color:var(--crack)}'
        . $s . '.gc-libgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:14px}'
        . $s . '.gc-assigned{outline:3px solid var(--patina);outline-offset:-3px}'
        . '</style>';
}

/** CSS del "naipe" de carta de técnica (una sola vez por página). */
function ope_rol_tecnica_card_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;

    return '<style id="ope-tk-css">'
        . '.ope-tk-deck{display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:14px}'
        . '.ope-tk{position:relative;display:flex;flex-direction:column;gap:10px;border:2px solid #000;background:var(--iron-plate);box-shadow:4px 4px 0 #000;padding:13px 14px 14px;overflow:hidden}'
        . '.ope-tk::before{content:"";position:absolute;inset:0 auto 0 0;width:5px;background:var(--tk-accent,var(--ember))}'
        . '.ope-tk.ope-tk-t1{--tk-accent:var(--patina-hi)}.ope-tk.ope-tk-t2{--tk-accent:var(--h6)}.ope-tk.ope-tk-t3{--tk-accent:var(--ember-hi)}.ope-tk.ope-tk-t4{--tk-accent:var(--ember)}.ope-tk.ope-tk-t5{--tk-accent:var(--crack)}'
        . '.ope-tk.is-insignia{box-shadow:4px 4px 0 #000,0 0 0 2px var(--ember) inset}'
        . '.ope-tk-h{display:flex;align-items:center;gap:11px;padding-left:4px}'
        . '.ope-tk-tier{flex:0 0 auto;width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:var(--tk-accent);color:var(--iron);border:2px solid #000;font-family:var(--disp);font-weight:900;font-size:1.15rem;line-height:1}'
        . '.ope-tk-tt{min-width:0;display:flex;flex-direction:column;gap:2px}'
        . '.ope-tk-name{margin:0;font-family:var(--disp);font-weight:800;font-size:1.12rem;text-transform:uppercase;letter-spacing:.3px;color:var(--paper);line-height:1.05;word-break:break-word}'
        . '.ope-tk-badge{align-self:flex-start;font-family:var(--mono);font-size:.54rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--iron);background:var(--ember);border:1px solid #000;padding:1px 6px}'
        . '.ope-tk-chips{display:flex;flex-wrap:wrap;gap:5px;padding-left:4px}'
        . '.ope-tk-chip{font-family:var(--mono);font-size:.56rem;font-weight:700;letter-spacing:.2px;color:var(--paper);background:var(--iron);border:1px solid #000;border-left:3px solid var(--tk,var(--ember));padding:2px 6px;white-space:nowrap}'
        . '.ope-tk-desc{margin:0;padding-left:4px;font-family:var(--body,inherit);font-size:.82rem;line-height:1.5;color:var(--paper-dim)}'
        . '.ope-tk-stats{display:flex;flex-wrap:wrap;gap:6px;padding-left:4px;margin-top:auto}'
        . '.ope-tk-stat{flex:1 1 auto;min-width:52px;display:flex;flex-direction:column;align-items:center;gap:1px;background:var(--iron);border:1px solid #000;padding:5px 6px}'
        . '.ope-tk-stat b{font-family:var(--disp);font-weight:800;font-size:1.05rem;color:var(--paper);line-height:1}'
        . '.ope-tk-stat small{font-family:var(--mono);font-size:.5rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ash)}'
        . '.ope-tk-dice{flex:1 1 100%;background:var(--iron-edge)}.ope-tk-dice b{font-size:.92rem;color:var(--ember-hi)}'
        . '.ope-tk-req{padding:6px 8px;background:var(--iron);border:1px dashed var(--rivet);font-family:var(--mono);font-size:.62rem;color:var(--paper-dim);line-height:1.4}'
        . '.ope-tk-req-l{font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--ash)}'
        . '</style>';
}
