<?php
/**
 * One Piece: Eternal · Rol (plugin de integración)
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

// Backend de rol: catálogos, Eternal, Haki, Frutas, Mundo Vivo, Viajes…
// Fuente canónica: inc/ope_rol/ (ver README). Stubs en inc/ope_rol_*.php
// siguen válidos para páginas que cargan módulos sueltos.
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

// Zona B 7 Seas (F2.2): panel del editor, parser y persistencia de turnos.
require_once MYBB_ROOT . 'inc/ope_rol/sistemas/combate_ui.php';

$plugins->add_hook('global_start', 'ope_rol_global');

// Progresión 7 Seas (F3.0): calendario on-roll perezoso + entrenamientos vencidos.
require_once MYBB_ROOT . 'inc/ope_rol/sistemas/progresion.php';
$plugins->add_hook('global_start', 'ope7_progresion_cron');

// Posteo por personaje: estampa el pid del personaje activo en cada
// mensaje/hilo y propaga el "último posteo" a hilos y foros.
$plugins->add_hook('datahandler_post_insert_thread', 'ope_rol_stamp_thread');
$plugins->add_hook('datahandler_post_insert_thread_post', 'ope_rol_stamp_thread_post');
$plugins->add_hook('datahandler_post_insert_post', 'ope_rol_stamp_post');
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_after_thread');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_after_post');

// D6.3: hooks legacy de posteo DESACTIVADOS — escriben en tablas mybb_rol_*
// retiradas (rol_post_snapshot, ope_pp_saldo, rol_haki/rol_tecnicas). El motor
// 7 Seas hace el conteo de usos de Haki al CIERRE de tema (5.19, D5.3) y el
// PP por cierre (trámite 2), no por post. El postbit degrada al estado actual
// del personaje (mybb_ope_personajes). Las funciones se conservan en el plugin
// (código legacy con guards) por si se quieren restaurar tras migrar los datos.
//$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_snapshot_post');
//$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_snapshot_post');
//$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_pp_on_post');
//$plugins->add_hook('datahandler_post_insert_post_end', 'ope_pp_on_post');
//$plugins->add_hook('datahandler_post_insert_thread_end', 'ope_rol_cu_on_post');
//$plugins->add_hook('datahandler_post_insert_post_end', 'ope_rol_cu_on_post');

// Restricción de posteo: un personaje EN REVISIÓN solo puede publicar en la
// zona Off Topic (crear tema o responder). Los aprobados, en cualquier foro.
$plugins->add_hook('newthread_do_newthread_start', 'ope_rol_guard_newthread');
$plugins->add_hook('newreply_do_newreply_start', 'ope_rol_guard_newreply');

// D6.3: metadata de época legacy DESACTIVADA (escribe en ope_thread_meta,
// retirada). El motor 7 Seas ancla la época en ope_temas (fecha_foro/tipo) y
// vincula el hilo real con mybb_tid (D1.8).
//$plugins->add_hook('newthread_do_newthread_end', 'ope_rol_save_thread_meta');
//$plugins->add_hook('editpost_do_editpost_end', 'ope_rol_save_thread_meta_edit');

// Spoilers anidables [spoiler]/[spoiler=Título] en todo el foro (antes de nl2br).
$plugins->add_hook('parse_message', 'ope_rol_parse_spoilers');

// D6.3: parse del RPG System legacy DESACTIVADO (leía rol_tecnicas/rol_cartas/
// ope_npcs_secundarios, retiradas; sin posts con esos tags). El 7 Seas usa
// [ope7-zonab] (Zona B) y sus propios bloques.
//$plugins->add_hook('parse_message', 'ope_rol_parse_rpg');

// Zona B 7 Seas (F2.2): [ope7-zonab]{json}[/ope7-zonab] → cartas del turno.
$plugins->add_hook('parse_message', 'ope7_zonab_parse');

// Persistencia de la Zona B en turnos_combate + sala_combate (F2.2).
$plugins->add_hook('datahandler_post_insert_thread_end', 'ope7_zonab_on_post');
$plugins->add_hook('datahandler_post_insert_post_end', 'ope7_zonab_on_post');

// D6.3: parse de viaje legacy DESACTIVADO (leía rol_personajes/viajes, retiradas).
//$plugins->add_hook('parse_message', 'ope_rol_parse_viaje');

// D6.3: hooks de misión/viaje legacy y plantillas de post DESACTIVADOS — leían
// rol_mision_tomas / rol_viajes / ope_post_templates (retiradas, sin datos). El
// motor 7 Seas gestiona misiones con ope_misiones (trámites 52–55) y travesías
// con ope_travesias (trámite 38).
//$plugins->add_hook('parse_message', 'ope_rol_parse_mision');
//$plugins->add_hook('showthread_end', 'ope_rol_mision_showthread_end');
//$plugins->add_hook('showthread_end', 'ope_rol_viaje_showthread_end');
//$plugins->add_hook('newthread_end', 'ope_rol_tpl_inserter_newthread');
//$plugins->add_hook('newreply_end', 'ope_rol_tpl_inserter_newreply');
//$plugins->add_hook('editpost_end', 'ope_rol_tpl_inserter_newreply');

// Muestra el personaje (no la cuenta) como autor visible del mensaje.
$plugins->add_hook('postbit', 'ope_rol_postbit');

// Thread review en newreply: no inyectar el pie RPG System (no hay postbit/flip).
$plugins->add_hook('newreply_threadreview_post', 'ope_rol_threadreview_post');

// Lista de hilos: autor del hilo y último posteo mostrados como personaje.
$plugins->add_hook('forumdisplay_thread_end', 'ope_rol_forumdisplay_thread');

// El staff es POR PERSONAJE, no por cuenta: aunque la cuenta tenga permisos de
// moderador/admin en MyBB, si el personaje activo no tiene rol de staff no debe
// ver el desplegable "Moderation Options" del tema.
$plugins->add_hook('showthread_end', 'ope_rol_hide_modtools_showthread');
$plugins->add_hook('showthread_end', 'ope_rol_showthread_tags');

// Navbar única: se inyecta automáticamente en CUALQUIER página que use el
// pipeline estándar de MyBB (output_page) y que todavía no la traiga incluida
// en su propia plantilla. Así queda "estandarizada" en todas las zonas
// (foro, usercp, member.php, búsqueda, etc.) sin tocar decenas de plantillas.
$plugins->add_hook('pre_output_page', 'ope_rol_inject_navbar');

// Zona B (F2.2): el panel de cartas de combate se inyecta bajo el editor
// (newreply/newthread/editpost) cuando el usuario tiene un personaje 7 Seas
// activo. El resto del ciclo (parse en post, persistencia) lo gestionan
// ope7_zonab_parse / ope7_zonab_on_post registrados más arriba.
$plugins->add_hook('pre_output_page', 'ope_rol_inject_zonab_editor');

function ope_rol_info()
{
    return array(
        'name'          => 'One Piece: Eternal Rol',
        'description'   => 'Expone el nivel de staff y el personaje activo del sistema de rol a las plantillas (navbar Zona Staff).',
        'website'       => '',
        'author'        => 'One Piece: Eternal',
        'authorsite'    => '',
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
    // D6.3: el puntero canónico vive en mybb_ope_cuentas (rol_cuentas está retirada).
    return $db->table_exists('ope_cuentas');
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
 * ¿Es administrador del foro MyBB (cuenta), independiente del personaje?
 * uid=1 o usergroup 4 (Administrators) → staff de cuenta.
 */
function ope_rol_is_board_admin($uid)
{
    global $mybb, $db;
    $uid = (int) $uid;
    if ($uid < 1) {
        return false;
    }
    if ($uid === 1) {
        return true;
    }
    $gid = (int) ($mybb->user['usergroup'] ?? 0);
    if ($gid === 4) {
        return true;
    }
    // Fallback si se llama fuera de global_start con otro uid.
    if (!empty($db) && (int) ($mybb->user['uid'] ?? 0) !== $uid && $db->table_exists('users')) {
        $q = $db->simple_select('users', 'usergroup', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($q) && (int) $db->fetch_field($q, 'usergroup') === 4) {
            return true;
        }
    }
    return false;
}

/**
 * Staff del PERSONAJE ACTIVO de una cuenta (no de la cuenta). Devuelve:
 *   ['pid','rol','narrador','rank','is_staff','nombre']
 * Si el personaje activo no es propio o no existe, todo queda a cero —
 * salvo bypass Admin MyBB (uid=1 / usergroup 4), que es staff sin PJ.
 */
function ope_rol_active_staff($uid)
{
    global $db, $mybb;
    $out = array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
    $uid = (int) $uid;
    if ($uid < 1) {
        return $out;
    }
    // D6.3: fuente canónica mybb_ope_cuentas (staff_level/staff_rol/staff_narrador).
    if (function_exists('ope7_permisos')) {
        $p = ope7_permisos($uid);
        $out['rank']     = (int) ($p['rank'] ?? 0);
        $out['rol']      = (string) ($p['rol'] ?? '');
        $out['narrador'] = (int) ($p['narrador'] ?? 0);
        $out['is_staff'] = (bool) ($p['is_staff'] ?? false);
        if ($out['is_staff'] || $out['narrador'] === 1) {
            $a = ope7_pj_activo($uid);
            if ($a && $a['tabla'] === 'ope') {
                $out['pid'] = $a['id'];
                $pq = $db->simple_select(ope7_tabla('personajes'), 'nombre', "id = " . $a['id'], array('limit' => 1));
                if ($db->num_rows($pq)) {
                    $out['nombre'] = (string) $db->fetch_field($pq, 'nombre');
                }
            }
        }
    }
    if (!$out['is_staff'] && ope_rol_is_board_admin($uid)) {
        $out['rol']      = 'webmaster';
        $out['rank']     = 4;
        $out['is_staff'] = true;
        if ($out['nombre'] === '') {
            $out['nombre'] = (string) ($mybb->user['username'] ?? 'Admin');
        }
    }
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

    // D6.3: fuente canónica mybb_ope_cuentas + mybb_ope_personajes.
    // Fail-safe: sin motor 7 Seas, solo bypass Admin MyBB.
    if (!function_exists('ope7_tabla_existe') || !ope7_tabla_existe('cuentas') || !ope7_tabla_existe('personajes')) {
        if (ope_rol_is_board_admin($uid)) {
            $mybb->user['ope_staff_rol']   = 'webmaster';
            $mybb->user['ope_staff_rank']  = 4;
            $mybb->user['ope_staff_level'] = 4;
            $mybb->user['ope_is_staff']    = 1;
            $bburl         = htmlspecialchars_uni($mybb->settings['bburl']);
            $ope_nav_staff = '<a href="' . $bburl . '/zona-staff.php" class="ope-nav-link">Zona Staff</a>';
        }
        return;
    }

    $activo = 0;
    $a = ope7_pj_activo($uid);
    $activo = ($a && $a['tabla'] === 'ope') ? $a['id'] : 0;

    $mybb->user['ope_active_pid'] = $activo;
    $ope_active_pid               = $activo;

    // Datos del personaje activo: nombre + STAFF (el staff es por personaje, así
    // que si tienes activo un personaje sin rol, NO eres staff aunque otro de tus
    // personajes lo sea).
    if ($activo > 0) {
        $staff_rol      = '';
        $staff_narrador = 0;
        $p = ope7_permisos($uid);
        $staff_rol      = (string) ($p['rol'] ?? '');
        $staff_narrador = (int) ($p['narrador'] ?? 0);
        $pq = $db->simple_select(
            ope7_tabla('personajes'),
            'nombre',
            "id = {$activo} AND uid = {$uid}",
            array('limit' => 1)
        );
        if ($db->num_rows($pq)) {
            $row = $db->fetch_array($pq);
            $ope_active_nombre               = (string) ($row['nombre'] ?? '');
            $mybb->user['ope_active_nombre'] = $ope_active_nombre;
            if ($ope_active_nombre !== '') {
                $mybb->user['ope_display_name'] = $ope_active_nombre;
            }

            $rank     = ope_rol_staff_rank($staff_rol);
            $narrador = (int) $staff_narrador;
            $mybb->user['ope_staff_rol']      = (string) $staff_rol;
            $mybb->user['ope_staff_narrador'] = $narrador;
            $mybb->user['ope_staff_rank']     = $rank;
            $mybb->user['ope_staff_level']    = $rank;
            $mybb->user['ope_is_staff']       = ($rank > 0 || $narrador === 1) ? 1 : 0;
        }
    }

    // Bypass cuenta Admin MyBB: staff sin personaje activo (uid=1 o usergroup admin).
    if (empty($mybb->user['ope_is_staff']) && ope_rol_is_board_admin($uid)) {
        $mybb->user['ope_staff_rol']   = 'webmaster';
        $mybb->user['ope_staff_rank']  = 4;
        $mybb->user['ope_staff_level'] = 4;
        $mybb->user['ope_is_staff']    = 1;
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
    if ($pid <= 0 || !$db->table_exists('ope_alertas')) return 0;
    $q = $db->simple_select('ope_alertas', 'COUNT(*) as cnt', "pid = {$pid} AND leido = 0");
    return (int)$db->fetch_field($q, 'cnt');
}

function ope_rol_mensajes_no_leidos(int $pid): int
{
    global $db;
    if (!$db->table_exists('ope_mensajes') || $pid <= 0) return 0;
    $q = $db->query("
        SELECT COUNT(DISTINCT thread_id) as cnt
        FROM " . TABLE_PREFIX . "ope_mensajes
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
    global $mybb, $db;
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

    // Sidebar icons (SVG inline — siempre visibles, texto se oculta colapsado)
    $iconPersonaje = '<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    $iconBarco      = '<svg viewBox="0 0 24 24"><path d="M2 20l2-4h16l2 4"/><path d="M4 16l2-8h12l2 8"/><path d="M12 4v4"/><path d="M9 4h6"/></svg>';
    $iconTramites   = '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
    $iconMapa       = '<svg viewBox="0 0 24 24"><path d="M9 3L1 6v15l8-3 6 3 8-3V3l-8 3-6-3z"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>';
    $iconGestion    = '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>';
    $iconTiendas    = '<svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
    $iconCatalogo   = '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>';
    $iconGuias      = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    $iconStaff      = '<svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';

    // Sidebar: Gestión · Trámites · Tiendas · Mapa · Catálogo (dropdown) · Guías · Zona Staff
    $iconResumen = '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>';
    $gestionScripts = array('gestion.php', 'tripulacion.php', 'tienda-personal.php');
    $links   = '<a href="' . $bburl . '/resumen.php" class="ope-nav-link' . $isOn(array('resumen.php')) . '">' . $iconResumen . '<span>Resumen</span></a>';
    $links  .= '<a href="' . $bburl . '/gestion.php" class="ope-nav-link' . $isOn($gestionScripts) . '">' . $iconGestion . '<span>Gestión</span></a>';
    $links  .= '<a href="' . $bburl . '/tramites.php" class="ope-nav-link' . $isOn(array('tramites.php')) . (strpos($script, 'tramite-') === 0 ? ' on' : '') . '">' . $iconTramites . '<span>Trámites</span></a>';
    $tiendasScripts = array('tiendas.php', 'tienda-general.php', 'astillero.php', 'mercado-negro.php', 'tiendas-personales.php');
    $links  .= '<a href="' . $bburl . '/tiendas.php" class="ope-nav-link' . $isOn($tiendasScripts) . '">' . $iconTiendas . '<span>Tiendas</span></a>';
    $links  .= '<a href="' . $bburl . '/mapa.php" class="ope-nav-link' . $isOn(array('mapa.php')) . '">' . $iconMapa . '<span>Mapa</span></a>';

    // Zona Catálogo (dropdown): Akuma no Mi, Lore & Cronología, Catálogo de NPCs.
    $catScripts = array('biblioteca-akuma.php', 'biblioteca-lore.php', 'catalogo-npcs.php');
    $links  .= '<div class="ope-nav-drop">'
             . '<button type="button" class="ope-nav-link ope-nav-dd-btn' . $isOn($catScripts) . '"'
             . ' aria-haspopup="true" aria-expanded="false"'
             . ' onclick="var d=this.nextElementSibling;var o=d.classList.toggle(\'open\');this.setAttribute(\'aria-expanded\',o?\'true\':\'false\');">' . $iconCatalogo . '<span>Catálogo</span></button>'
             . '<div class="ope-dropdown ope-nav-dropdown">'
             . '<a href="' . $bburl . '/biblioteca-akuma.php" class="ope-dropdown-item">Akuma no Mi</a>'
             . '<a href="' . $bburl . '/biblioteca-lore.php" class="ope-dropdown-item">Lore y Cronología</a>'
             . '<a href="' . $bburl . '/catalogo-npcs.php" class="ope-dropdown-item">Catálogo de NPCs</a>'
             . '</div></div>';

    $links  .= '<a href="' . $bburl . '/guias.php" class="ope-nav-link' . $isOn(array('guias.php')) . '">' . $iconGuias . '<span>Guías</span></a>';
    if ($isStaff) {
        $links .= '<a href="' . $bburl . '/zona-staff.php" class="ope-nav-link' . $isOn(array('zona-staff.php')) . '">' . $iconStaff . '<span>Zona Staff</span></a>';
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
        $right  = '<a href="' . $bburl . '/member.php?action=register" class="ope-nav-cta">Regístrate</a>';
        $right .= '<a href="' . $bburl . '/member.php?action=login" class="ope-btn-ghost ope-btn-sm">Acceder</a>';
    }

    // Toggle de tema cielo/noche global: botón único que alterna entre temas.
    // Cookie de navegador (sin sesión ni BD), visible igual logueado o invitado.
    $ope_theme_cookie = isset($_COOKIE['ope_theme']) ? (string) $_COOKIE['ope_theme'] : '';
    $ope_theme_current = ($ope_theme_cookie === 'noche') ? 'noche' : 'cielo';
    $themeToggleLabel = ($ope_theme_current === 'noche') ? 'Cielo' : 'Noche';

    $themeToggle  = '<button type="button" class="ope-theme-toggle-btn" id="ope-theme-toggle" title="Cambiar tema"'
                  . ' data-theme="' . $ope_theme_current . '"'
                  . ' aria-label="Cambiar a modo ' . ($ope_theme_current === 'noche' ? 'cielo' : 'noche') . '">'
                  . $themeToggleLabel
                  . '</button>';

    $right = $themeToggle . $right;

    // Anti-flash: aplica data-theme al <html> antes de que se pinte nada.
    // En páginas del pipeline, ope_rol_inject_navbar() ya lo pone server-side;
    // este fallback JS cubre páginas propias en PHP puro (ficha.php, etc.).
    $html  = '<script>(function(){'
           . 'var t=null;'
           . 'try{t=localStorage.getItem("ope_theme");}catch(e){}'
           . 'if(!t){var m=document.cookie.match(/(?:^|; )ope_theme=([^;]+)/);t=m?decodeURIComponent(m[1]):"";}'
           . 'document.documentElement.setAttribute("data-theme",t==="noche"?"noche":"cielo");'
           . '})()</script>' . "\n";
    $html .= '<!-- ===== SIDEBAR (fixed) · fuente única ===== -->' . "\n";
    $html .= ope_rol_navbar_css();
    $sidebarCookie = isset($_COOKIE['ope_sidebar']) ? (string) $_COOKIE['ope_sidebar'] : '';
    $sidebarOpen = ($sidebarCookie === 'open');
    $html .= '<nav id="ope-navbar" class="ope-sidebar' . ($sidebarOpen ? ' is-open' : '') . '">';
    $html .= '<div class="ope-sidebar-head">';
    $html .= '<button type="button" class="ope-sidebar-toggle" id="ope-sidebar-toggle" aria-label="Abrir/Cerrar menú" aria-expanded="' . ($sidebarOpen ? 'true' : 'false') . '">';
    $html .= '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
    $html .= '</button>';
    $html .= '<a href="' . $bburl . '/index.php" class="ope-nav-logo">One Piece: <b>7 Seas</b></a>';
    $html .= '</div>';
    $html .= '<div class="ope-sidebar-body">';

    // ── Zona resumen: avatar + nombre + ubicación (solo logged-in, solo expandido) ──
    if ($loggedin) {
        $resume_pid = $activePid;
        $resume_pj = null;
        $resume_nombre = '';
        $resume_nivel = 1;
        $resume_avatar = '';
        $resume_initials = '';
        if ($resume_pid > 0 && function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes')) {
            $rq = $db->simple_select(ope7_tabla('personajes'), 'id AS pid, nombre, nivel, avatar, icono, datos', "id = {$resume_pid}", array('limit' => 1));
            if ($db->num_rows($rq)) {
                $resume_pj = $db->fetch_array($rq);
                $resume_nombre = htmlspecialchars_uni($resume_pj['nombre']);
                $resume_nivel = (int) ($resume_pj['nivel'] ?? 1);
                $rd = json_decode((string) ($resume_pj['datos'] ?? ''), true);
                if (!is_array($rd)) $rd = array();
                $resume_avatar = trim((string) ($rd['retrato'] ?? ''));
                if ($resume_avatar === '') $resume_avatar = trim((string) ($resume_pj['avatar'] ?? ''));
                if ($resume_avatar === '') $resume_avatar = trim((string) ($resume_pj['icono'] ?? ''));
                $resume_avatar_e = htmlspecialchars_uni($resume_avatar);
                $resume_initials = function_exists('mb_substr') ? mb_strtoupper(mb_substr($resume_pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($resume_pj['nombre'], 0, 1));
            }
        }
        // Berries
        $resume_berries = 0;
        if ($resume_pj && function_exists('ope_eco_berries')) {
            $resume_berries = (int) ope_eco_berries($resume_pid);
        }
        // Ubicación insular (D6.3: fuente canónica ope7_ — medio viejo retirado)
        $resume_isla = '';
        if ($resume_pj && function_exists('ope7_isla_por_slug')) {
            $isla_slug = (string) ($resume_pj['isla_actual'] ?? '');
            if ($isla_slug !== '') {
                $isla_data = ope7_isla_por_slug($isla_slug);
                if ($isla_data) $resume_isla = htmlspecialchars_uni($isla_data['nombre'] ?? '');
            }
        }
        if ($resume_nombre !== '') {
            $html .= '<div class="ope-sidebar-summary">';
            $html .= '<a href="' . $bburl . '/resumen.php" class="ope-summary-avatar">';
            if ($resume_avatar !== '') {
                $html .= '<img src="' . $resume_avatar_e . '" alt="">';
            } else {
                $html .= '<span>' . htmlspecialchars_uni($resume_initials) . '</span>';
            }
            $html .= '</a>';
            $html .= '<div class="ope-summary-info">';
            $html .= '<a href="' . $bburl . '/resumen.php" class="ope-summary-name">' . $resume_nombre . '</a>';
            $html .= '<span class="ope-summary-meta">Lv.' . $resume_nivel;
            if ($resume_isla !== '') $html .= ' · ' . $resume_isla;
            $html .= '</span>';
            if ($resume_berries > 0) {
                $html .= '<span class="ope-summary-berries">' . number_format($resume_berries, 0, ',', '.') . ' berries</span>';
            }
            $html .= '</div></div>';
        }
    }

    $html .= '<div class="ope-nav-links">' . $links . '</div>';
    $html .= '<div class="ope-nav-right">' . $right . '</div>';
    $html .= '</div>';
    $html .= '</nav>';

    // Toasts flash one-time (p.ej. "¡Te avisaremos cuando zarpe!" tras encolar el viaje).
    if ($loggedin) {
        $toasts = function_exists('ope_flash_pull') ? ope_flash_pull($uid) : array();
        if (!empty($toasts)) {
            $html .= '<div class="ope-toasts" role="status" aria-live="polite">';
            foreach ($toasts as $tf) {
                $html .= '<div class="ope-toast ope-toast-' . htmlspecialchars_uni((string) ($tf['tipo'] ?? 'ok')) . '">'
                       . '<span class="ope-toast-msg">' . htmlspecialchars_uni((string) ($tf['mensaje'] ?? '')) . '</span>'
                       . '<button type="button" class="ope-toast-close" aria-label="Cerrar">&times;</button>'
                       . '</div>';
            }
            $html .= '</div><script>(function(){var t=document.querySelectorAll(".ope-toasts .ope-toast");t.forEach(function(el){var kill=function(){el.classList.add("out");setTimeout(function(){el.remove();},400);};el.querySelector(".ope-toast-close").addEventListener("click",kill);setTimeout(kill,8000);});})();</script>';
        }
    }

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

    return '';
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
    $html .= '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;900&amp;family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&amp;family=Spectral:ital,wght@0,400;0,500;0,600;1,400&amp;family=Space+Mono:wght@400;700&amp;display=swap" rel="stylesheet">' . "\n";
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
    if (stripos($contents, '<html') === false) {
        return $contents;
    }

    // Tema cielo/noche global (cookie del navegador; sin sesión ni BD).
    // Se inyecta SIEMPRE (incluso si la navbar ya está presente en templates
    // como ope-index.xml), para que el CSS aplique tokens sin flash.
    if (stripos($contents, 'data-theme=') === false) {
        $ope_theme_cookie  = isset($_COOKIE['ope_theme']) ? (string) $_COOKIE['ope_theme'] : '';
        $ope_theme_current = ($ope_theme_cookie === 'noche') ? 'noche' : 'cielo';
        $contents_themed   = preg_replace('/<html([^>]*)>/i', '<html$1 data-theme="' . $ope_theme_current . '">', $contents, 1);
        if ($contents_themed !== null) {
            $contents = $contents_themed;
        }
    }

    if (stripos($contents, 'id="ope-navbar"') !== false) {
        return $contents;
    }
    if (stripos($contents, '<body') === false) {
        return $contents;
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
    $custom_layout = in_array($script, array(
        'index.php', 'forumdisplay.php', 'showthread.php',
        'newthread.php', 'newreply.php', 'editpost.php',
    ), true);
    $pageSlug = str_replace('.php', '', $script);
    if ($custom_layout) {
        if (stripos($contents, 'data-ope-page=') === false) {
            $new = preg_replace('/(<body)([^>]*)>/i', '$1$2 data-ope-page="' . $pageSlug . '">' . "\n" . $navbar, $contents, 1);
        } else {
            $new = preg_replace('/(<body[^>]*>)/i', '$0' . "\n" . $navbar, $contents, 1);
        }
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

    // D6.3: fuente canónica mybb_ope_cuentas → mybb_ope_personajes.
    $pid = 0;
    if (function_exists('ope7_pj_activo')) {
        $a = ope7_pj_activo($uid);
        if ($a && $a['tabla'] === 'ope' && $a['id'] > 0) {
            $pid = $a['id'];
        }
    }

    // Sólo cuenta si sigue siendo un personaje propio y no descartado
    // (aprobado o en revisión). Rechazados/borradores/eliminados no se firman.
    if ($pid > 0 && function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes')) {
        $vq = $db->simple_select(ope7_tabla('personajes'), 'id', "id = {$pid} AND uid = {$uid} AND estado IN ('aprobado','revision')", array('limit' => 1));
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
    if ($uid < 1) {
        return '';
    }
    $pid = 0;
    if (!function_exists('ope7_pj_activo') || !ope7_tabla_existe('cuentas') || !ope7_tabla_existe('personajes')) {
        return '';
    }
    $a = ope7_pj_activo($uid);
    $pid = ($a && $a['tabla'] === 'ope') ? $a['id'] : 0;
    if ($pid <= 0) {
        return '';
    }
    $vq = $db->simple_select(ope7_tabla('personajes'), 'estado', "id = {$pid} AND uid = {$uid}", array('limit' => 1));
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
    $s = strtr($s, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'));
    
    if (in_array($s, array('pirata', 'piratas'), true)) {
        return 'pirata';
    }
    if (in_array($s, array('marine', 'marines', 'marina'), true)) {
        return 'marine';
    }
    if (in_array($s, array('revolucionario', 'revolucionarios'), true)) {
        return 'revolucionario';
    }
    if (in_array($s, array('gobierno', 'gobierno-mundial', 'gobierno mundial', 'gov', 'cipher pol', 'cp'), true)) {
        return 'gobierno';
    }
    if (in_array($s, array('cazarrecompensas', 'cazador', 'cazadores', 'bounty', 'bountyhunter'), true)) {
        return 'cazarrecompensas';
    }
    if (in_array($s, array('civil', 'civiles', 'independiente'), true)) {
        return 'civil';
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
    // D6.3: fuente canónica mybb_ope_personajes (columna id = pid legacy).
    if (!function_exists('ope7_tabla_existe') || !ope7_tabla_existe('personajes')) {
        $cache[$pid] = null;
        return null;
    }
    $q = $db->simple_select(ope7_tabla('personajes'), 'id AS pid, uid, nombre, slug, faccion_id, nivel, avatar, icono, bio AS firma, estado, datos, desc_fisica', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $row = $db->fetch_array($q);
        // Facción: en el esquema nuevo es faccion_id → nombre; el postbit
        // legacy lee 'faccion' (nombre) + 'rango'/'rango_faccion'.
        $datos = json_decode((string) ($row['datos'] ?? ''), true) ?: array();
        $fid = (int) ($row['faccion_id'] ?? 0);
        $faccion_nombre = '';
        if ($fid > 0 && ope7_tabla_existe('facciones')) {
            $fq = $db->simple_select(ope7_tabla('facciones'), 'nombre', "id = {$fid}", array('limit' => 1));
            if ($db->num_rows($fq)) {
                $faccion_nombre = (string) $db->fetch_field($fq, 'nombre');
            }
        }
        $row['faccion']      = $faccion_nombre !== '' ? $faccion_nombre : (string) ($datos['faccion'] ?? '');
        $row['faccion_slug'] = ope_rol_faccion_slug($row['faccion']);
        $row['rango']        = (string) ($datos['rango'] ?? '');
        $row['rango_faccion'] = $faccion_nombre;
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
    global $mybb, $db;
    $uid = (int) ($dh->data['uid'] ?? 0);
    $active_pid = ope_rol_active_pid_for($uid);
    $dh->thread_insert_data['ope_pid'] = $active_pid;

    // Capturar etiquetas de Hilo: temporal_tipo, temporal_fecha, tema_tipo
    $temp_tipo  = strtolower(trim((string)$mybb->get_input('temporal_tipo')));
    if ($temp_tipo !== 'pasado') {
        $temp_tipo = 'presente';
    }
    
    $tema_tipo  = strtolower(trim((string)$mybb->get_input('tema_tipo')));
    $validos_tema = array('travesia', 'aventura', 'fic', 'combate', 'entrenamiento', 'social', 'trama');
    if (!in_array($tema_tipo, $validos_tema, true)) {
        $tema_tipo = 'social';
    }

    $temp_fecha = '';
    if ($temp_tipo === 'pasado') {
        $dia      = max(1, min(65, (int)$mybb->get_input('temporal_dia')));
        $estacion = trim((string)$mybb->get_input('temporal_estacion'));
        $validas_est = array('Primavera', 'Verano', 'Otoño', 'Invierno');
        if (!in_array($estacion, $validas_est, true)) {
            $estacion = 'Primavera';
        }
        $ano = (int)$mybb->get_input('temporal_ano');
        if ($ano < 1000 || $ano > 3000) {
            $ano = 1518;
        }
        $temp_fecha = 'Día ' . $dia . ' · ' . $estacion . ' · Año ' . $ano;
    } else {
        $temp_fecha = function_exists('ope_rol_mv_fecha_onrol')
            ? ope_rol_mv_fecha_onrol(TIME_NOW)
            : my_date('j F Y', TIME_NOW);
    }

    if ($db->field_exists('temporal_tipo', 'threads')) {
        $dh->thread_insert_data['temporal_tipo']  = $db->escape_string($temp_tipo);
        $dh->thread_insert_data['temporal_fecha'] = $db->escape_string($temp_fecha);
        $dh->thread_insert_data['tema_tipo']      = $db->escape_string($tema_tipo);
    }

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
    // D1.8: el hilo real recién creado se vincula al presente abierto del
    // personaje que aún no tenía hilo (apertura de tema, misión o tripulación).
    // Se salta Off Topic: esos hilos no son temas de rol.
    if ($tid > 0 && $pid > 0
        && function_exists('ope7_tema_vincular_mybb_por_pj')
        && !ope_rol_is_offtopic_fid($fid)) {
        ope7_tema_vincular_mybb_por_pj($pid, $tid);
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
    if (!isset($post['ope_post_back_html'])) {
        $post['ope_post_back_html'] = '';
    }
    if (!isset($post['ope_char_side'])) {
        $post['ope_char_side'] = '';
    }

    // Extrae el bloque RPG System del cuerpo y lo expone como {$post['ope_rpgsys']}
    // para embeberlo en el reverso HUD (3D flip), no debajo del post.
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

    // Bloque bajo el avatar: facción/rango de facción/tripulación + botón 3D Card Flip
    $post['ope_char_side'] = ope_rol_postbit_side($char, $post);

    // Ficha congelada del post (Espalda de la tarjeta 3D Flip)
    $post['ope_post_back_html'] = ope_rol_build_post_back_html($char, $post);

    // Firma POR PERSONAJE: sustituye la firma de la cuenta. Si el personaje tiene
    // firma configurada en su ficha, se muestra con un separador "One Piece: Eternal".
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
 * aesthetic "One Piece: Eternal". Devuelve el HTML listo para el postbit.
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
         . '<div class="ope-sig-sep" aria-hidden="true"><span>One Piece: 7 Seas</span></div>'
         . '<div class="ope-sig-body">' . $parsed . '</div>'
         . '</div>';
}

/**
 * Payload de fruta para snapshots (misma fuente que la ficha: rol_pj_fruta).
 */
function ope_rol_fruta_snapshot_payload($char_pid, array $stats = array())
{
    if (!function_exists('ope_fruta_ficha_block')) {
        return array();
    }
    $block = ope_fruta_ficha_block((int) $char_pid, $stats);
    if (empty($block['tiene']) || empty($block['fruta'])) {
        return array();
    }
    $f = (array) $block['fruta'];
    return array(
        'id'           => (int) ($f['id'] ?? 0),
        'nombre'       => (string) ($f['nombre'] ?? ''),
        'modelo'       => (string) ($f['modelo'] ?? ''),
        'tipo'         => (string) ($f['tipo'] ?? ''),
        'imagen'       => (string) ($f['imagen'] ?? ''),
        'nivel'        => (int) ($block['nivel'] ?? 0),
        'nombre_nivel' => (string) ($block['nombre_nivel'] ?? ''),
        'potencia'     => (int) ($block['potencia'] ?? 1),
        'cu'           => (int) ($block['cu'] ?? 0),
    );
}

/**
 * Normaliza mods del payload RPG (pueden venir como int o {val,pct}).
 * @return array{val:int,pct:bool}
 */
function ope_rol_mod_entry($mods, $key)
{
    if (!is_array($mods)) {
        return array('val' => 0, 'pct' => false);
    }
    $k = strtoupper((string) $key);
    $raw = $mods[$k] ?? ($mods[strtolower($k)] ?? null);
    if ($raw === null) {
        return array('val' => 0, 'pct' => false);
    }
    if (is_array($raw)) {
        return array(
            'val' => (int) ($raw['val'] ?? 0),
            'pct' => !empty($raw['pct']),
        );
    }
    return array('val' => (int) $raw, 'pct' => false);
}

/**
 * Carga la fila completa del personaje (stats_json, stats_ganados, economía…).
 */
function ope_rol_char_row($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) {
        return null;
    }
    // D6.3: fuente canónica mybb_ope_personajes (columna id = pid legacy).
    if (!function_exists('ope7_tabla_existe') || !ope7_tabla_existe('personajes')) {
        return null;
    }
    $q = $db->simple_select(ope7_tabla('personajes'), '*', "id = {$pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $row = $db->fetch_array($q);
    // Mapeo de columnas al esquema viejo que el postbit legacy consume.
    $row['pid'] = (int) $row['id'];
    unset($row['id']);
    if (!isset($row['firma'])) {
        $row['firma'] = (string) ($row['bio'] ?? '');
    }
    if (!isset($row['rango'])) {
        $row['rango'] = (string) ($row['rango_faccion'] ?? '');
    }
    if (!isset($row['rango_faccion'])) {
        $row['rango_faccion'] = (string) ($row['faccion_id'] ?? '');
    }
    $datos = json_decode((string) ($row['datos'] ?? ''), true) ?: array();
    $faccion_nombre = (string) ($datos['faccion'] ?? '');
    if (empty($faccion_nombre) && (int) ($row['faccion_id'] ?? 0) > 0 && ope7_tabla_existe('facciones')) {
        $fq = $db->simple_select(ope7_tabla('facciones'), 'nombre', "id = " . (int) $row['faccion_id'], array('limit' => 1));
        if ($db->num_rows($fq)) {
            $faccion_nombre = (string) $db->fetch_field($fq, 'nombre');
        }
    }
    $row['faccion']      = $faccion_nombre;
    $row['faccion_slug'] = ope_rol_faccion_slug($row['faccion']);
    return $row;
}

/**
 * Misma verdad de ficha.php: nivel desde stats_ganados + stats_json.
 */
function ope_rol_ficha_truth(array $pj)
{
    $datos = json_decode((string) ($pj['datos'] ?? ''), true) ?: array();
    $stats_ganados = (int) ($pj['stats_ganados'] ?? ($datos['stats_ganados'] ?? 0));
    $nivel = function_exists('ope_rol_nivel_from_stats_comprados')
        ? (int) ope_rol_nivel_from_stats_comprados($stats_ganados)
        : max(1, (int) ($pj['nivel'] ?? 1));

    $stats_json_d = json_decode((string) ($pj['stats_json'] ?? ''), true);
    $stats = (is_array($stats_json_d) && !empty($stats_json_d))
        ? $stats_json_d
        : (is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array());

    $inv = json_decode((string) ($pj['inventario'] ?? ''), true) ?: array();
    $items = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();

    $eco = json_decode((string) ($pj['economia'] ?? ''), true) ?: array();
    $berries = (int) ($eco['berries'] ?? ($eco['rupies'] ?? 0));

    $facciones = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
    $fac_slug  = (string) ($pj['faccion_slug'] ?? ope_rol_faccion_slug((string) ($datos['faccion'] ?? '')));
    $fac_lbl   = isset($facciones[$fac_slug]) ? $facciones[$fac_slug]['nombre'] : ucfirst($fac_slug);

    $tramo = function_exists('ope_rol_tramo') ? ope_rol_tramo($nivel) : 1;
    $label = function_exists('ope_rol_nivel_label')
        ? ope_rol_nivel_label($nivel)
        : ('Nivel ' . $nivel . ' · Tramo ' . (function_exists('ope_rol_tramo_romano') ? ope_rol_tramo_romano($tramo) : 'I'));

    return array(
        'nivel'         => $nivel,
        'tramo'         => $tramo,
        'nivel_label'   => $label,
        'stats'         => $stats,
        'stats_ganados' => $stats_ganados,
        'items'         => $items,
        'berries'       => $berries,
        'fac_slug'      => $fac_slug,
        'fac_lbl'       => $fac_lbl,
        'rango'         => (string) ($pj['rango'] ?? ''),
        'rango_faccion' => trim((string) ($pj['rango_faccion'] ?? '')),
        'nombre'        => (string) ($pj['nombre'] ?? ''),
        'avatar'        => trim((string) ($pj['avatar'] ?? '')),
        'datos'         => $datos,
    );
}

/**
 * Overlay de combate de un post: mods/estados/pv/en desde snapshot o [rpgsys] crudo.
 */
function ope_rol_post_combat_overlay($post_pid)
{
    global $db;
    $post_pid = (int) $post_pid;
    $out = array(
        'mods'    => array(),
        'estados' => array(),
        'pv'      => null,
        'en'      => null,
        'meta'    => '',
    );

    // D6.3: rol_post_snapshot retirada — los mods/estados/pv se reparsean del
    // bloque [rpgsys] del mensaje crudo del post (o se dejan vacíos).

    // Si faltan mods/estados/pv, reparsea el mensaje crudo del post.
    $need_msg = empty($out['mods']) || empty($out['estados']) || $out['pv'] === null;
    if ($need_msg && $post_pid > 0 && $db->table_exists('posts')) {
        $mq = $db->simple_select('posts', 'message', "pid = {$post_pid}", array('limit' => 1));
        if ($db->num_rows($mq)) {
            $msg = (string) $db->fetch_field($mq, 'message');
            if ($msg !== '' && preg_match('#\[rpgsys\]([^\[]*)\[/rpgsys\]#i', $msg, $mm)) {
                $pl = ope_rol_parse_cbt_payload($mm[1]);
                if (empty($out['mods']) && !empty($pl['mods'])) {
                    $out['mods'] = $pl['mods'];
                }
                if (empty($out['estados']) && !empty($pl['estados'])) {
                    $out['estados'] = $pl['estados'];
                }
                if ($out['pv'] === null && $pl['pv'] !== null) {
                    $out['pv'] = (int) $pl['pv'];
                }
                if ($out['en'] === null && $pl['en'] !== null) {
                    $out['en'] = (int) $pl['en'];
                }
            }
        }
    }

    $meta = array();
    $n_est = is_array($out['estados']) ? count($out['estados']) : 0;
    $n_mod = is_array($out['mods']) ? count($out['mods']) : 0;
    if ($n_est) {
        $meta[] = $n_est . ' estado' . ($n_est === 1 ? '' : 's');
    }
    if ($n_mod) {
        $meta[] = $n_mod . ' mod' . ($n_mod === 1 ? '' : 's');
    }
    $out['meta'] = $meta ? implode(' · ', $meta) : '';

    return $out;
}

/**
 * Cuerpo interno del bloque RPG System sin chrome anidado ni vitals duplicados.
 */
function ope_rol_rpgsys_embed_body($html)
{
    $html = (string) $html;
    if (trim($html) === '') {
        return '';
    }

    $css = '';
    if (preg_match_all('#<style\b[^>]*>[\s\S]*?</style>#i', $html, $sm)) {
        $css = implode('', $sm[0]);
        $html = preg_replace('#<style\b[^>]*>[\s\S]*?</style>#i', '', $html);
    }

    $body = $html;
    if (preg_match('#<div class="ope-rpgsys-b"[^>]*>([\s\S]*)</div>\s*</div>\s*$#i', $html, $m)) {
        $body = $m[1];
    } elseif (preg_match('#class="ope-rpgsys-b"[^>]*>([\s\S]*?)</div>#i', $html, $m)) {
        $body = $m[1];
    } else {
        $body = preg_replace('#<div class="ope-rpgsys[^"]*"[^>]*>\s*<button\b[\s\S]*?</button>#i', '', $html);
        $body = preg_replace('#</div>\s*$#', '', $body);
    }

    // Vitals ya viven arriba en el HUD.
    $body = preg_replace('#<div class="ope-rpgsys-vitals">[\s\S]*?</div>#i', '', $body);

    return $css . trim($body);
}

/**
  * Genera el HTML HUD de la espalda 3D Card Flip (ETERNAL SYSTEM).
 * Identidad/atributos = misma verdad que ficha.php; vitals/mods/acciones = del post.
 */
function ope_rol_build_post_back_html(array $char, array $post)
{
    global $db, $tid, $mybb;

    $pid_post = (int) $post['pid'];
    $char_pid = (int) ($char['pid'] ?? 0);
    $pj = ope_rol_char_row($char_pid) ?: $char;
    $truth = ope_rol_ficha_truth($pj);
    $snap = ope_rol_post_snapshot($pid_post, $pj);
    $combat = ope_rol_post_combat_overlay($pid_post);

    $stats = $truth['stats'];
    $items = $truth['items'];
    $nivel = (int) $truth['nivel'];
    $tramo = (int) $truth['tramo'];
    $tramo_rom = function_exists('ope_rol_tramo_romano') ? ope_rol_tramo_romano($tramo) : 'I';
    $nivel_label = $truth['nivel_label'];
    $mods_list = is_array($combat['mods']) ? $combat['mods'] : array();
    $estados_list = is_array($combat['estados']) ? $combat['estados'] : array();
    $approx_snap = !empty($snap['approx']);

    $stats_clean = array();
    $stat_map = array();
    if (function_exists('ope_rol_stats')) {
        foreach (ope_rol_stats() as $grupo) {
            foreach ($grupo['stats'] as $k => $lbl) {
                $stat_map[$k] = $lbl;
            }
        }
    } else {
        $stat_map = array(
            'FUE' => 'Fuerza', 'RES' => 'Resistencia', 'AGI' => 'Agilidad', 'INT' => 'Intelecto',
            'PER' => 'Percepción', 'TEM' => 'Temple', 'VOL' => 'Voluntad', 'CAR' => 'Carisma',
        );
    }
    foreach ($stat_map as $k => $lbl) {
        $val = function_exists('ope_rol_stat_num')
            ? ope_rol_stat_num($stats, $k, 1)
            : max(1, (int) ($stats[$k] ?? ($stats[strtolower($k)] ?? 1)));
        $stats_clean[$k] = $val;
    }

    $pv_max = function_exists('ope_combat_calc_pv') ? (int) ope_combat_calc_pv($stats_clean) : 100;
    $en_max = function_exists('ope_combat_calc_en') ? (int) ope_combat_calc_en($stats_clean) : 100;
    $pa_max = function_exists('ope_combat_calc_pa') ? (int) ope_combat_calc_pa($stats_clean, $nivel) : 3;

    // Vitales de ficha; el PV/EN del payload [rpgsys] no sustituye el techo
    // (evita el 540/300 de prueba encima del 80/55 real).
    $pv_cur = $pv_max;
    $en_cur = $en_max;
    if (!$approx_snap && isset($snap['pv_actual']) && $snap['pv_actual'] !== null) {
        $cand = (int) $snap['pv_actual'];
        if ($cand >= 0 && $cand <= max($pv_max, 1) * 2) {
            $pv_cur = $cand;
        }
    }
    if (!$approx_snap && isset($snap['en_actual']) && $snap['en_actual'] !== null) {
        $cand = (int) $snap['en_actual'];
        if ($cand >= 0 && $cand <= max($en_max, 1) * 2) {
            $en_cur = $cand;
        }
    }
    $pa_cur = $pa_max;

    $pv_pct = $pv_max > 0 ? max(0, min(100, (int) round(($pv_cur / $pv_max) * 100))) : 0;
    $en_pct = $en_max > 0 ? max(0, min(100, (int) round(($en_cur / $en_max) * 100))) : 0;

    $fruta_block = function_exists('ope_fruta_ficha_block')
        ? ope_fruta_ficha_block($char_pid, $stats_clean)
        : array('tiene' => false);
    $fruta_name = '';
    $fruta_sub  = '';
    $fruta_img  = '';
    $fruta_tipo = '';
    if (!empty($fruta_block['tiene']) && !empty($fruta_block['fruta'])) {
        $f = (array) $fruta_block['fruta'];
        $fruta_name = (string) ($f['nombre'] ?? '');
        if (!empty($f['modelo'])) {
            $fruta_name .= ' (' . $f['modelo'] . ')';
        }
        $fruta_img  = (string) ($f['imagen'] ?? '');
        $fruta_tipo = function_exists('ope_fruta_tipo_base') ? ope_fruta_tipo_base((string) ($f['tipo'] ?? '')) : '';
        $fruta_sub  = 'Nv.' . (int) ($fruta_block['nivel'] ?? 0)
            . ' ' . (string) ($fruta_block['nombre_nivel'] ?? '')
            . ' · Pot ' . (int) ($fruta_block['potencia'] ?? 1);
    }

    // D6.3: rol_post_snapshot retirada — la fruta del HUD sale del bloque
    // canónico (ope_fruta_ficha_block → ope_akumas) y los NPCs del post ya no
    // se congelan (el motor 7 Seas no escribe snapshots).
    $npcs_txt = '';

    $haki_block = function_exists('ope_haki_ficha_block')
        ? ope_haki_ficha_block($char_pid, $stats_clean, $nivel)
        : array();

    $rpgsys_body = ope_rol_rpgsys_embed_body(!empty($post['ope_rpgsys']) ? $post['ope_rpgsys'] : '');
    $bburl = htmlspecialchars_uni($mybb->settings['bburl'] ?? '');
    $fichaurl = $bburl . '/ficha.php?pid=' . $char_pid;
    $nombre = htmlspecialchars_uni($truth['nombre'] !== '' ? $truth['nombre'] : (string) ($char['nombre'] ?? ''));
    $avatar = $truth['avatar'] !== '' ? $truth['avatar'] : (string) ($char['avatar'] ?? '');
    $fac_slug = htmlspecialchars_uni($truth['fac_slug']);
    $fac_lbl  = htmlspecialchars_uni($truth['fac_lbl']);
    $rango_raw = trim((string) $truth['rango']);
    // Evita el "1" suelto cuando rango BD = nivel numérico basura.
    $rango_show = ($rango_raw !== '' && !ctype_digit($rango_raw) && $rango_raw !== (string) $nivel)
        ? htmlspecialchars_uni($rango_raw)
        : '';
    $berries  = (int) $truth['berries'];
    $stat_cap = function_exists('ope_rol_stat_cap_tramo') ? max(1, (int) ope_rol_stat_cap_tramo($nivel)) : 15;
    $approx   = $approx_snap;
    $av_initial = function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($truth['nombre'] !== '' ? $truth['nombre'] : '?', 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($truth['nombre'] !== '' ? $truth['nombre'] : '?', 0, 1));

    $html = '<div class="ope-hud" data-pid="' . $pid_post . '">'
          .   '<header class="ope-hud-hero">'
          .     '<div class="ope-hud-identity">'
          .       '<div class="ope-hud-avatar">'
          .         ($avatar !== ''
                    ? '<img src="' . htmlspecialchars_uni($avatar) . '" alt="" loading="lazy" onerror="this.remove()">'
                    : '<span class="ope-hud-avatar-ph">' . htmlspecialchars_uni($av_initial) . '</span>')
          .       '</div>'
          .       '<div class="ope-hud-idtext">'
           .         '<span class="ope-hud-kicker">ETERNAL SYSTEM · Post #' . $pid_post . ($approx ? ' · approx' : '') . '</span>'
          .         '<h3 class="ope-hud-name">' . $nombre . '</h3>'
          .         '<div class="ope-hud-meta">'
          .           '<span class="ope-hud-pill ope-hud-pill--lvl">' . htmlspecialchars_uni($nivel_label) . '</span>'
          .           ($fac_lbl !== '' ? '<span class="ope-hud-pill">' . $fac_lbl . '</span>' : '')
          .           ($rango_show !== '' ? '<span class="ope-hud-pill ope-hud-pill--dim">' . $rango_show . '</span>' : '')
          .         '</div>'
          .       '</div>'
          .     '</div>'
          .     '<button type="button" class="btn btn-sm btn-hot ope-hud-backbtn" onclick="opeFlipPostCard(' . $pid_post . ')">Volver al Post</button>'
          .   '</header>'

          .   '<section class="ope-hud-vitals" aria-label="Vitales">'
          .     '<div class="ope-hud-vital ope-hud-vital--pv">'
          .       '<div class="ope-hud-vital-top"><span>PV</span><b>' . $pv_cur . '</b><i>/' . $pv_max . '</i></div>'
          .       '<div class="ope-hud-vital-track"><span style="width:' . $pv_pct . '%"></span></div>'
          .     '</div>'
          .     '<div class="ope-hud-vital ope-hud-vital--en">'
          .       '<div class="ope-hud-vital-top"><span>EN</span><b>' . $en_cur . '</b><i>/' . $en_max . '</i></div>'
          .       '<div class="ope-hud-vital-track"><span style="width:' . $en_pct . '%"></span></div>'
          .     '</div>'
          .     '<div class="ope-hud-vital ope-hud-vital--pa">'
          .       '<div class="ope-hud-vital-top"><span>PA</span><b>' . $pa_cur . '</b></div>'
          .       '<div class="ope-hud-pa-dots" aria-hidden="true">';
    for ($i = 1; $i <= max(1, $pa_max); $i++) {
        $html .= '<i class="' . ($i <= $pa_cur ? 'on' : '') . '"></i>';
    }
    $html .=      '</div></div></section>';

    $html .= '<section class="ope-hud-panel ope-hud-stats">'
          .    '<header class="ope-hud-panel-h"><span>Atributos</span><span class="ope-hud-panel-sub">Tramo ' . $tramo_rom . ' · cap ' . $stat_cap . '</span></header>'
          .    '<div class="ope-hud-stat-grid">';
    foreach ($stat_map as $k => $lbl) {
        $base = $stats_clean[$k];
        $mod = ope_rol_mod_entry($mods_list, $k);
        $total = $base;
        $mod_html = '';
        if ($mod['val'] !== 0 && empty($mod['pct'])) {
            $total = $base + $mod['val'];
            $sign = $mod['val'] > 0 ? '+' : '';
            $mod_html = '<em class="ope-hud-mod' . ($mod['val'] > 0 ? ' up' : ' down') . '">'
                      . $sign . $mod['val'] . '</em>';
        } elseif ($mod['val'] !== 0) {
            $sign = $mod['val'] > 0 ? '+' : '';
            $mod_html = '<em class="ope-hud-mod' . ($mod['val'] > 0 ? ' up' : ' down') . '">'
                      . $sign . $mod['val'] . '%</em>';
        }
        $pct = min(100, max(0, (int) round((max(0, $total) / $stat_cap) * 100)));
        $heat = $pct >= 80 ? 'h9' : ($pct >= 60 ? 'h8' : ($pct >= 40 ? 'h6' : ($pct >= 25 ? 'h4' : 'h2')));
        $html .= '<div class="ope-hud-stat heat-' . $heat . '">'
              .    '<div class="ope-hud-stat-top"><abbr title="' . htmlspecialchars_uni($lbl) . '">' . $k . '</abbr>'
              .      '<span class="ope-hud-stat-val">' . $total . $mod_html . '</span></div>'
              .    '<div class="ope-hud-stat-bar"><i style="width:' . $pct . '%"></i></div>'
              .  '</div>';
    }
    $html .= '</div></section>';

    $html .= '<section class="ope-hud-powers">'
          .    '<div class="ope-hud-panel ope-hud-fruta">'
          .      '<header class="ope-hud-panel-h"><span>Akuma no Mi</span></header>';
    if ($fruta_name !== '') {
        $html .= '<div class="ope-hud-fruta-card tipo-' . htmlspecialchars_uni($fruta_tipo) . '">'
              .    '<div class="ope-hud-fruta-thumb">';
        if ($fruta_img !== '') {
            $html .= '<img src="' . htmlspecialchars_uni($fruta_img) . '" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'is-empty\');this.remove()">';
        } else {
            $html .= '<span class="ope-hud-fruta-ph" aria-hidden="true">果</span>';
        }
        $html .=   '</div>'
              .    '<div class="ope-hud-fruta-body">'
              .      '<b>' . htmlspecialchars_uni($fruta_name) . '</b>'
              .      ($fruta_sub !== '' ? '<span>' . htmlspecialchars_uni($fruta_sub) . '</span>' : '')
              .    '</div></div>';
    } else {
        $html .= '<p class="ope-hud-empty">Sin fruta del diablo</p>';
    }
    $html .= '</div>';

    $html .= '<div class="ope-hud-panel ope-hud-haki">'
          .    '<header class="ope-hud-panel-h"><span>Haki</span></header>'
          .    '<div class="ope-hud-haki-row">';
    foreach (array('ken' => 'Ken', 'buso' => 'Busō', 'hao' => 'Haō') as $hk => $hlbl) {
        $hb = is_array($haki_block[$hk] ?? null) ? $haki_block[$hk] : null;
        $hn = $hb ? (int) ($hb['nivel'] ?? 0) : 0;
        $on = $hn >= 1 || ($hk === 'hao' && !empty($hb['despertado']));
        $html .= '<div class="ope-hud-haki-chip' . ($on ? ' is-on' : '') . ' ope-hud-haki--' . $hk . '">'
              .    '<b>' . $hlbl . '</b>'
              .    '<span>' . ($on ? ('Nv.' . $hn) : '—') . '</span>'
              .  '</div>';
    }
    $html .= '</div></div></section>';

    $html .= '<section class="ope-hud-sidegrid">'
          .    '<div class="ope-hud-panel">'
          .      '<header class="ope-hud-panel-h"><span>Recursos</span></header>'
          .      '<div class="ope-hud-berries"><b>' . number_format($berries, 0, ',', '.') . '</b> <span>฿ Berries</span></div>';
    if (empty($items)) {
        $html .= '<p class="ope-hud-empty">Mochila vacía</p>';
    } else {
        $html .= '<ul class="ope-hud-loot">';
        foreach (array_slice($items, 0, 8) as $it) {
            $n = is_array($it) ? (string) ($it['n'] ?? '') : (string) $it;
            if ($n === '') {
                continue;
            }
            $html .= '<li>' . htmlspecialchars_uni($n) . '</li>';
        }
        $html .= '</ul>';
    }
    $html .=   '</div>'
          .    '<div class="ope-hud-panel">'
          .      '<header class="ope-hud-panel-h"><span>Afiliación</span></header>'
          .      '<div class="ope-hud-kv"><span>Facción</span><b>' . ($fac_lbl !== '' ? $fac_lbl : '—') . '</b></div>'
          .      '<div class="ope-hud-kv"><span>Nivel</span><b>' . htmlspecialchars_uni($nivel_label) . '</b></div>';
    if ($rango_show !== '') {
        $html .= '<div class="ope-hud-kv"><span>Rango</span><b>' . $rango_show . '</b></div>';
    }
    if ($truth['rango_faccion'] !== '') {
        $html .= '<div class="ope-hud-kv"><span>Rango facción</span><b>' . htmlspecialchars_uni($truth['rango_faccion']) . '</b></div>';
    }
    if ($npcs_txt !== '') {
        $html .= '<div class="ope-hud-kv"><span>Acompañantes</span><b>' . htmlspecialchars_uni($npcs_txt) . '</b></div>';
    }
    $html .=   '</div></section>';

    // Acciones del post = el expandible (sin caja RPG SYSTEM anidada).
    if ($rpgsys_body !== '' || !empty($estados_list) || !empty($mods_list)) {
        $actions_inner = $rpgsys_body;
        if ($actions_inner === '') {
            $actions_inner = '<div class="ope-hud-tags">';
            foreach ($estados_list as $est) {
                $cat = function_exists('ope_combat_estados') ? ope_combat_estados() : array();
                $info = $cat[$est] ?? null;
                $nom = htmlspecialchars_uni((string) ($info['nombre'] ?? $est));
                $tipo = htmlspecialchars_uni((string) ($info['tipo'] ?? 'negativo'));
                $actions_inner .= '<span class="ope-estado ope-estado--' . $tipo . '">' . $nom . '</span>';
            }
            foreach ($mods_list as $m_k => $m_raw) {
                $me = ope_rol_mod_entry(array($m_k => $m_raw), $m_k);
                if ($me['val'] === 0) {
                    continue;
                }
                $sign = $me['val'] > 0 ? '+' : '';
                $dir = $me['val'] >= 0 ? 'up' : 'down';
                $actions_inner .= '<span class="ope-rpgsys-mod ope-rpgsys-mod--' . $dir . '"><b>'
                    . htmlspecialchars_uni(strtoupper((string) $m_k)) . '</b> '
                    . $sign . $me['val'] . ($me['pct'] ? '%' : '') . '</span>';
            }
            $actions_inner .= '</div>';
        }
        $meta_txt = $combat['meta'] !== '' ? $combat['meta'] : 'Sistema de rol';
        $html .= '<section class="ope-hud-actions is-collapsed">'
              .    '<button type="button" class="ope-hud-actions-h" aria-expanded="false">'
              .      '<span class="ope-hud-actions-title">Acciones del post</span>'
              .      '<span class="ope-hud-actions-meta">' . htmlspecialchars_uni($meta_txt) . '</span>'
              .      '<span class="ope-hud-actions-toggle">Mostrar</span>'
              .    '</button>'
              .    '<div class="ope-hud-actions-b" hidden>' . $actions_inner . '</div>'
              .  '</section>';
    }

    $html .= '<footer class="ope-hud-foot">'
          .    '<a class="ope-hud-ficha" href="' . $fichaurl . '">Abrir ficha completa</a>'
          .    '<div class="ope-hud-foot-acts">'
          .      '<a href="' . htmlspecialchars_uni($post['postlink'] ?? '') . '#pid' . $pid_post . '" class="ope-btn ope-btn-sm ope-btn-ghost">#' . (int) ($post['postnum'] ?? 0) . '</a>'
          .      '<a href="newreply.php?tid=' . (int) $tid . '&amp;pid=' . $pid_post . '" class="ope-btn ope-btn-sm ope-btn-ghost">Citar</a>'
          .      '<a href="newreply.php?tid=' . (int) $tid . '" class="ope-btn ope-btn-sm ope-btn-ghost">Responder</a>'
          .    '</div>'
          .  '</footer>'
          . '</div>';

    return $html;
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
    // D6.3: la tabla rol_post_snapshot está retirada — el HUD usa el estado
    // ACTUAL del personaje como mejor aproximación (nunca se inventa histórico).

    $datos = json_decode((string) ($char['datos'] ?? ''), true);
    $stats_json_d = json_decode((string) ($char['stats_json'] ?? ''), true);
    $stats = (is_array($stats_json_d) && !empty($stats_json_d))
        ? $stats_json_d
        : (is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array());

    $items = array();
    if (function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes')) {
        $iq = $db->simple_select(ope7_tabla('personajes'), 'inventario', 'id = ' . (int) $char['pid'], array('limit' => 1));
        if ($db->num_rows($iq)) {
            $inv   = json_decode((string) $db->fetch_field($iq, 'inventario'), true);
            $items = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();
        }
    }

    return array('stats' => $stats, 'items' => $items, 'approx' => true,
                 'pv_actual' => null, 'en_actual' => null, 'pa_actual' => null);
}

/** Relación tipo "tripulación" de un personaje (el otro extremo del vínculo), o null. */
function ope_rol_char_tripulacion($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) {
        return null;
    }
    // D6.3: fuente canónica mybb_ope_tripulantes (relación por tripulación);
    // fallback al legado ope_relaciones (tipo = 'tripulacion').
    if (function_exists('ope7_tabla_existe') && ope7_tabla_existe('tripulantes') && ope7_tabla_existe('tripulaciones')) {
        $q = $db->query('SELECT p.id, p.nombre FROM ' . ope7_tabla_full('tripulantes') . ' tr '
            . 'JOIN ' . ope7_tabla_full('personajes') . ' p ON p.id = tr.personaje_id '
            . "WHERE tr.tripulacion_id IN (SELECT tripulacion_id FROM " . ope7_tabla_full('tripulantes') . " WHERE personaje_id = {$pid}) AND tr.personaje_id <> {$pid} LIMIT 1");
        if ($db->num_rows($q)) {
            $r = $db->fetch_array($q);
            return array('pid' => (int) $r['id'], 'nombre' => (string) $r['nombre']);
        }
        return null;
    }
    if (!$db->table_exists('ope_relaciones')) {
        return null;
    }
    $q = $db->simple_select('ope_relaciones', 'pid, destino_pid', "tipo = 'tripulacion' AND (pid = {$pid} OR destino_pid = {$pid})", array('limit' => 1));
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
    global $mybb, $db;

    $bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
    $pid_post = (int) $post['pid'];

    $facciones   = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
    $fac_slug    = (string) ($char['faccion_slug'] ?? '');
    $fac_lbl     = isset($facciones[$fac_slug]) ? $facciones[$fac_slug]['nombre'] : ucfirst($fac_slug);
    $rango_fac   = trim((string) ($char['rango_faccion'] ?? ''));
    $tripulacion = ope_rol_char_tripulacion((int) $char['pid']);

    $rows = '';
    if ($fac_slug !== '') {
        $rows .= '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Faccion</span><span class="ope-pa-srow-v fac-' . $fac_slug . '">' . htmlspecialchars_uni($fac_lbl) . '</span></div>'
               . '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Rango faccion</span><span class="ope-pa-srow-v">' . ($rango_fac !== '' ? htmlspecialchars_uni($rango_fac) : '&mdash;') . '</span></div>';
    }
    if ($tripulacion) {
        $trip_url = $bburl . '/ficha.php?pid=' . (int) $tripulacion['pid'];
        $rows .= '<div class="ope-pa-srow"><span class="ope-pa-srow-l">Tripulacion</span><span class="ope-pa-srow-v"><a href="' . $trip_url . '" class="ope-char-link">' . htmlspecialchars_uni($tripulacion['nombre']) . '</a></span></div>';
    }
    if ($rows === '') return '';

    return '<div class="ope-pa-stats ope-pa-org"><div class="ope-hud-kicker">AFILIACION</div>' . $rows . '</div>';
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
 * Guarda/actualiza ope_thread_meta para un tema. Reglas:
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
    if ($tid < 1 || !$db->table_exists('ope_thread_meta')) {
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

    $ex = $db->simple_select('ope_thread_meta', 'tid', "tid = {$tid}", array('limit' => 1));
    if ($db->num_rows($ex)) {
        unset($data['tid']);
        $db->update_query('ope_thread_meta', $data, "tid = {$tid}");
    } else {
        $db->insert_query('ope_thread_meta', $data);
    }
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
 * Marca el parseo del thread review (newreply) para omitir el pie RPG System.
 * Ese pie solo tiene sentido en postbit (flip HUD); en la revisión de hilo
 * quedaba el chrome viejo "RPG System" sin maquetar.
 */
function ope_rol_threadreview_post()
{
    global $ope_rol_hide_rpgsys_footer;
    $ope_rol_hide_rpgsys_footer = true;
}

/**
 * Parsea el contenido de un bloque [rpgsys]…[/rpgsys].
 * Soporta:
 *   - Legacy: "1,2,3" o "haki.buso.imbuir,haki.ken.mantener"
 *   - Combate: "c:1,haki.buso.imbuir|pv:120|en:45|est:veneno|mod:FUE=5"
 * Devuelve cards como lista mixta int|string.
 */
function ope_rol_parse_card_token($tok)
{
    $tok = trim((string) $tok);
    if ($tok === '') {
        return null;
    }
    if (preg_match('/^(haki|fruta)\.[a-z0-9_.\-]+$/i', $tok)) {
        return strtolower($tok);
    }
    $id = (int) $tok;
    return $id > 0 ? $id : null;
}

function ope_rol_parse_card_list($raw)
{
    $out = array();
    foreach (explode(',', (string) $raw) as $part) {
        $t = ope_rol_parse_card_token($part);
        if ($t !== null) {
            $out[] = $t;
        }
    }
    return $out;
}

function ope_rol_parse_cbt_payload($raw)
{
    $out = array('cards' => array(), 'pv' => null, 'en' => null, 'estados' => array(), 'mods' => array(), 'ons' => null);
    $raw = trim((string) $raw);
    if ($raw === '') {
        return $out;
    }

    if (strpos($raw, ':') === false) {
        $out['cards'] = ope_rol_parse_card_list($raw);
        return $out;
    }

    foreach (explode('|', $raw) as $seg) {
        $seg = trim($seg);
        if ($seg === '' || strpos($seg, ':') === false) {
            continue;
        }
        list($k, $v) = explode(':', $seg, 2);
        $k = trim($k);
        $v = trim($v);
        switch ($k) {
            case 'c':
                $out['cards'] = ope_rol_parse_card_list($v);
                break;
            case 'pv':
                $out['pv'] = max(0, (int) $v);
                break;
            case 'en':
                $out['en'] = max(0, (int) $v);
                break;
            case 'est':
                foreach (explode(',', $v) as $e) {
                    $e = preg_replace('/[^a-z0-9_\-]/i', '', trim($e));
                    if ($e !== '') {
                        $out['estados'][] = $e;
                    }
                }
                break;
            case 'mod':
                foreach (explode(',', $v) as $m) {
                    $m = trim($m);
                    if ($m === '' || strpos($m, '=') === false) {
                        continue;
                    }
                    list($stat, $val) = explode('=', $m, 2);
                    $stat = preg_replace('/[^A-Z]/', '', strtoupper(trim($stat)));
                    if ($stat === '') {
                        continue;
                    }
                    $pct = (substr(trim($val), -1) === '%');
                    $num = (int) $val;
                    if ($num === 0) {
                        continue;
                    }
                    $out['mods'][$stat] = array('val' => $num, 'pct' => $pct);
                }
                break;
            case 'ons':
                $parts = array_map('trim', explode(',', $v, 2));
                if (count($parts) >= 2) {
                    $out['ons'] = array(
                        'npc_id'  => (int) $parts[0],
                        'tec_idx' => (int) $parts[1],
                    );
                }
                break;
        }
    }
    return $out;
}

/**
 * Renderiza una carta (técnica numérica, haki.* o fruta.*) para el pie.

/**
 * Parser de bloques de rol (RPG System) en los mensajes.
 * Lo mecánico se envuelve en <!--OPERPGSYS-->…<!--/OPERPGSYS-->; el postbit
 * lo embebe en el reverso HUD. En thread review el pie se omite.

/**
 * Al publicar un post: suma CU de Haki/Fruta por cartas jugadas en [rpgsys]/[carta].

/**
 * Renderiza shortcodes del Oraculo de Viaje al mostrar posts.
 *   [viaje=123]                    → bloque completo del oraculo (primer post)
 *   [viaje-cierre=123]             → post de llegada solicitada por el jugador (aprobado)
 *   [viaje-solicitud-cierre=123]   → aviso de que el cierre esta pendiente de revision
 *   [viaje-rechazo=123]            → cierre rechazado (1er intento, vuelve a activo)
 *   [viaje-rechazo-cancelado=123]  → cierre rechazado (2do intento, viaje cancelado)

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
        // Toggle Acciones del post dentro del HUD flip.
        . "(function(){document.addEventListener('click',function(e){"
        . "var h=e.target.closest&&e.target.closest('.ope-hud-actions-h');if(!h)return;"
        . "e.preventDefault();var box=h.parentNode;var b=box.querySelector('.ope-hud-actions-b');if(!b)return;"
        . "var t=h.querySelector('.ope-hud-actions-toggle');var open=b.hasAttribute('hidden');"
        . "if(open){b.removeAttribute('hidden');box.classList.remove('is-collapsed');h.setAttribute('aria-expanded','true');if(t)t.textContent='Ocultar';}"
        . "else{b.setAttribute('hidden','');box.classList.add('is-collapsed');h.setAttribute('aria-expanded','false');if(t)t.textContent='Mostrar';}});})();\n"
        . "(function(){"
        . "var btn=document.getElementById('ope-theme-toggle');"
        . "if(btn){btn.addEventListener('click',function(e){e.stopPropagation();"
        . "var cur=btn.getAttribute('data-theme')||'cielo';"
        . "var next=cur==='noche'?'cielo':'noche';"
        . "btn.setAttribute('data-theme',next);"
        . "btn.textContent=next==='noche'?'Cielo':'Noche';"
        . "document.documentElement.setAttribute('data-theme',next);"
        . "try{localStorage.setItem('ope_theme',next);}catch(e){}"
        . "document.cookie='ope_theme='+next+'; path=/; max-age=31536000; SameSite=Lax';"
        . "});}"
        . "})();\n"
        . "(function(){"
        . "document.addEventListener('click',function(e){"
        . "var openDrops=document.querySelectorAll('#ope-navbar .ope-dropdown.open');"
        . "for(var k=0;k<openDrops.length;k++){var od=openDrops[k];"
        . "if(!od.contains(e.target)&&!(e.target.closest&&e.target.closest('.ope-user-name'))&&!(e.target.closest&&e.target.closest('.ope-nav-dd-btn'))){od.classList.remove('open');}}"
        . "});"
        . "})();\n"
        // Sidebar toggle — hamburger + cookie + body class
        . "(function(){"
        . "var btn=document.getElementById('ope-sidebar-toggle');"
        . "var sidebar=document.getElementById('ope-navbar');"
        . "if(!btn||!sidebar)return;"
        . "function toggle(){"
        . "var open=sidebar.classList.toggle('is-open');"
        . "btn.setAttribute('aria-expanded',open?'true':'false');"
        . "document.body.classList.toggle('ope-sidebar-open',open);"
        . "document.cookie='ope_sidebar='+(open?'open':'')+'; path=/; max-age=31536000; SameSite=Lax';"
        . "}"
        . "if(sidebar.classList.contains('is-open'))document.body.classList.add('ope-sidebar-open');"
        . "btn.addEventListener('click',function(e){e.stopPropagation();toggle();});"
        . "document.addEventListener('keydown',function(e){if(e.key==='Escape'&&sidebar.classList.contains('is-open'))toggle();});"
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
    if ($pid < 1 || !$db->table_exists('ope_post_templates')) {
        return $out;
    }
    $q = $db->simple_select('ope_post_templates', 'tpl_id, nombre, cuerpo',
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
 * Panel "RPG System" del editor. Vive bajo el textarea del post y agrupa,
 * por ahora, dos pestañas:
 *   · Plantillas → inserta en el mensaje la plantilla elegida del personaje.
 *   · Cartas     → muestra el deck del personaje; las cartas que se marquen se
 *                  adjuntan al post (bloque [rpgsys]) y se renderizan bajo él.
 * Es una zona extensible: aquí se irán añadiendo más módulos de rol.

// ─────────────────────────────────────────────────────────────
// Cartas de Técnica (INI-03): lectura del deck + render de carta.
// ─────────────────────────────────────────────────────────────

/**
 * Deck de cartas de técnica de un personaje, ordenado (insignia primero,
 * luego por tier descendente y orden manual). Devuelve array de filas con
 * `tags` ya decodificado a array.

/**
 * Una carta de técnica por su id (rol_tecnicas.id), con `tags` decodificado.
 * Se usa al renderizar el bloque [rpgsys] bajo los posts.

/**
 * HTML de una carta de técnica (formato "naipe" reutilizable en la ficha y
 * en el creador del staff). $carta['tags'] debe venir ya como array.

/**
 * Biblioteca de cartas (mybb_rol_cartas) — cartas creadas sin personaje.
 * Devuelve filas con `tags` ya decodificado. Admite búsqueda y filtro de tier.

/**
 * Prompt maestro (Markdown) para que una IA aprenda TODO el sistema de Cartas
 * de Técnica (INI-03) y devuelva una carta en el YAML que el creador entiende
 * y autorrellena. Fuente única: se usa en el modal de ayuda de crear-cartas.php.

/**
 * CSS del creador/asignador de cartas (chips de tags, tier, preview, modal
 * de ayuda IA). Scopeado a body.ope-pg-gestionar-cartas para reutilizarlo en
 * crear-cartas.php y asignar-cartas.php. Se emite una sola vez.

/**
 * HTML de una carta de NPC secundario (formato apaisado: imagen ¾ a la
 * izquierda, nombre + descripción + técnicas a la derecha).
 * $npc['tecnicas'] debe venir ya como array (o null).
 */
/**
 * Normaliza el JSON de técnicas de un NPC secundario a [{n,d,e}, ...].
 */
function ope_rol_npc_sec_norm_tecnicas($raw)
{
    $tecnicas = is_array($raw) ? $raw : array();
    if (!is_array($raw) && is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        $tecnicas = is_array($decoded) ? $decoded : array();
    }
    $out = array();
    foreach ($tecnicas as $t) {
        if (is_string($t)) {
            $out[] = array('n' => $t, 'd' => '', 'e' => '');
        } elseif (is_array($t) && isset($t['n'])) {
            $out[] = array(
                'n' => (string) ($t['n'] ?? ''),
                'd' => (string) ($t['d'] ?? ''),
                'e' => (string) ($t['e'] ?? ''),
            );
        } elseif (is_array($t) && isset($t['nombre'])) {
            $out[] = array(
                'n' => (string) ($t['nombre'] ?? ''),
                'd' => (string) ($t['dados'] ?? $t['d'] ?? ''),
                'e' => (string) ($t['descripcion'] ?? $t['e'] ?? ''),
            );
        }
    }
    return $out;
}

function ope_rol_npc_sec_card_html(array $npc, $highlight_tec = -1)
{
    $nombre    = htmlspecialchars_uni((string) ($npc['nombre'] ?? 'Sin nombre'));
    $desc      = trim((string) ($npc['descripcion'] ?? ''));
    $imagen    = trim((string) ($npc['imagen'] ?? ''));
    $tecnicas_norm = ope_rol_npc_sec_norm_tecnicas($npc['tecnicas'] ?? null);
    $highlight_tec = (int) $highlight_tec;

    $html  = '<article class="ons-card">';

    $html .= '<div class="ons-img-box">';
    if ($imagen !== '') {
        $html .= '<div class="ons-img"><img src="' . htmlspecialchars_uni($imagen) . '" alt="' . $nombre . '" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.classList.add(\'on-empty\');"></div>';
    } else {
        $html .= '<div class="ons-img on-empty"></div>';
    }
    $html .= '</div>';

    $html .= '<div class="ons-body">';
    $html .= '<h4 class="ons-name">' . $nombre . '</h4>';

    if ($desc !== '') {
        $html .= '<p class="ons-desc">' . nl2br(htmlspecialchars_uni($desc)) . '</p>';
    }

    if (!empty($tecnicas_norm)) {
        $html .= '<div class="ons-tec">';
        $html .= '<span class="ons-tec-lbl">Técnicas</span>';
        $html .= '<div class="ons-tec-list">';
        foreach ($tecnicas_norm as $ti => $t) {
            $used_cls = ($highlight_tec >= 0 && $ti === $highlight_tec) ? ' is-used' : '';
            $html .= '<div class="ons-tec-item' . $used_cls . '">';
            $html .= '<span class="ons-tec-iname">' . htmlspecialchars_uni($t['n']) . '</span>';
            if ($t['d'] !== '' && $t['d'] !== '—') {
                $html .= '<span class="ons-tec-idice">' . htmlspecialchars_uni($t['d']) . '</span>';
            }
            if ($t['e'] !== '') {
                $html .= '<span class="ons-tec-ief">' . htmlspecialchars_uni($t['e']) . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div></div>';
    }

    $html .= '</div>';
    $html .= '</article>';
    return $html;
}

/**
 * CSS de la carta de NPC secundario (formato "ons-card": apaisado con imagen).
 * Se emite una sola vez por página.
 */
function ope_rol_npc_sec_card_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    /* CSS en docs/themes/ope.css — .ons-card / .ons-deck */
    return '';
}

/**
 * CSS del forjador/creador de NPCs secundarios (formulario + preview).
 * Scopeado a body.ope-pg-crear-npc-sec para reutilizarlo.
 * Se emite una sola vez.
 */
function ope_rol_npc_sec_forge_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    /* CSS en docs/themes/ope.css — scope body.ope-pg-crear-npc-sec */
    return '';
}

/**
 * Biblioteca de NPCs secundarios de mybb_ope_npcs_secundarios.
 * Devuelve filas con `tecnicas` ya decodificado y normalizado.
 * Busca por nombre del NPC y/o por nombre de tecnica (en el JSON).
 */
function ope_rol_npc_sec_lib($buscar = '', $tec_buscar = '')
{
    global $db;
    $out = array();
    if (!$db->table_exists('ope_npcs_secundarios')) return $out;
    $where = '1=1';
    $buscar = trim((string) $buscar);
    $tec_buscar = trim((string) $tec_buscar);
    if ($buscar !== '') {
        $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    }
    if ($tec_buscar !== '') {
        $where .= " AND tecnicas LIKE '%" . $db->escape_string_like($tec_buscar) . "%'";
    }
    $q = $db->simple_select('ope_npcs_secundarios', '*', $where, array('order_by' => 'nombre ASC', 'limit' => 500));
    while ($r = $db->fetch_array($q)) {
        $r['tecnicas'] = ope_rol_npc_sec_norm_tecnicas($r['tecnicas'] ?? '');
        $out[] = $r;
    }
    return $out;
}

/**
 * Un NPC secundario por id, con técnicas normalizadas.
 */
function ope_rol_npc_sec_by_id($npc_id)
{
    global $db;
    $npc_id = (int) $npc_id;
    if ($npc_id < 1 || !$db->table_exists('ope_npcs_secundarios')) {
        return null;
    }
    $q = $db->simple_select('ope_npcs_secundarios', '*', "id = {$npc_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $r = $db->fetch_array($q);
    $r['tecnicas'] = ope_rol_npc_sec_norm_tecnicas($r['tecnicas'] ?? '');
    return $r;
}

/** Máximo de acompañantes NPC por personaje. */
function ope_rol_acompanantes_max()
{
    return 2;
}

/**
 * Acompañantes asignados a un personaje (slots 1–2), con datos del NPC.
 */
function ope_rol_char_acompanantes($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('ope_acompanantes') || !$db->table_exists('ope_npcs_secundarios')) {
        return $out;
    }
    $q = $db->simple_select('ope_acompanantes', '*', "pid = {$pid}", array('order_by' => 'slot ASC'));
    while ($row = $db->fetch_array($q)) {
        $npc = ope_rol_npc_sec_by_id((int) $row['npc_id']);
        if (!$npc) {
            continue;
        }
        $row['npc'] = $npc;
        $out[] = $row;
    }
    return $out;
}

/**
 * Asigna un NPC secundario a un slot del personaje (1 o 2).
 * Devuelve ['ok'=>bool, 'msg'=>string].
 */
function ope_rol_acompanante_asignar($pid, $npc_id, $slot)
{
    global $db;
    $pid = (int) $pid;
    $npc_id = (int) $npc_id;
    $slot = (int) $slot;
    $max = ope_rol_acompanantes_max();

    if ($pid < 1 || $npc_id < 1) {
        return array('ok' => false, 'msg' => 'Datos incompletos.');
    }
    if ($slot < 1 || $slot > $max) {
        return array('ok' => false, 'msg' => 'El slot debe ser 1 o 2.');
    }
    if (!$db->table_exists('ope_acompanantes') || !$db->table_exists('ope_npcs_secundarios')) {
        return array('ok' => false, 'msg' => 'Falta la tabla de acompañantes. Ejecuta scripts/migrate-acompanantes.php');
    }
    if (!$db->num_rows($db->simple_select('ope_npcs_secundarios', 'id', "id = {$npc_id}", array('limit' => 1)))) {
        return array('ok' => false, 'msg' => 'Ese NPC no existe en la biblioteca.');
    }

    // Mismo NPC en otro slot → mover.
    $dup = $db->simple_select('ope_acompanantes', 'id, slot', "pid = {$pid} AND npc_id = {$npc_id}", array('limit' => 1));
    if ($db->num_rows($dup)) {
        $ex = $db->fetch_array($dup);
        if ((int) $ex['slot'] === $slot) {
            return array('ok' => true, 'msg' => 'Ese acompañante ya está en ese slot.');
        }
        $db->delete_query('ope_acompanantes', 'id = ' . (int) $ex['id']);
    }

    // Slot ocupado por otro NPC → reemplazar.
    $occ = $db->simple_select('ope_acompanantes', 'id', "pid = {$pid} AND slot = {$slot}", array('limit' => 1));
    if ($db->num_rows($occ)) {
        $db->delete_query('ope_acompanantes', 'id = ' . (int) $db->fetch_field($occ, 'id'));
    }

    $db->insert_query('ope_acompanantes', array(
        'pid'      => $pid,
        'npc_id'   => $npc_id,
        'slot'     => $slot,
        'dateline' => TIME_NOW,
    ));

    return array('ok' => true, 'msg' => 'Acompañante asignado al slot ' . $slot . '.');
}

/**
 * Quita el acompañante de un slot (o por npc_id si slot = 0).
 */
function ope_rol_acompanante_quitar($pid, $slot = 0, $npc_id = 0)
{
    global $db;
    $pid = (int) $pid;
    $slot = (int) $slot;
    $npc_id = (int) $npc_id;
    if ($pid < 1 || !$db->table_exists('ope_acompanantes')) {
        return array('ok' => false, 'msg' => 'No se pudo quitar el acompañante.');
    }
    if ($slot > 0) {
        $db->delete_query('ope_acompanantes', "pid = {$pid} AND slot = {$slot}");
    } elseif ($npc_id > 0) {
        $db->delete_query('ope_acompanantes', "pid = {$pid} AND npc_id = {$npc_id}");
    } else {
        return array('ok' => false, 'msg' => 'Indica slot o NPC.');
    }
    return array('ok' => true, 'msg' => 'Acompañante retirado.');
}

/**
 * Primer slot libre (1..max) del personaje, o 0 si están todos ocupados.
 */
function ope_rol_acompanante_slot_libre($pid)
{
    global $db;
    $pid = (int) $pid;
    $max = ope_rol_acompanantes_max();
    if ($pid < 1 || !$db->table_exists('ope_acompanantes')) {
        return 1;
    }
    $ocupados = array();
    $q = $db->simple_select('ope_acompanantes', 'slot', "pid = {$pid}");
    while ($r = $db->fetch_array($q)) {
        $ocupados[(int) $r['slot']] = true;
    }
    for ($s = 1; $s <= $max; $s++) {
        if (empty($ocupados[$s])) {
            return $s;
        }
    }
    return 0;
}

// ─────────────────────────────────────────────────────────────
// Solicitudes de acompañante (jugador pide → staff aprueba/rechaza).
// ─────────────────────────────────────────────────────────────

/**
 * El jugador solicita un acompañante NPC para su personaje. Crea una solicitud
 * pendiente que el staff resuelve. Devuelve ['ok'=>bool, 'msg'=>string].
 */
function ope_rol_acompanante_solicitar($pid, $uid, $npc_id, $motivo = '')
{
    global $db;
    $pid = (int) $pid;
    $uid = (int) $uid;
    $npc_id = (int) $npc_id;
    $motivo = trim((string) $motivo);
    $max = ope_rol_acompanantes_max();

    if ($pid < 1 || $npc_id < 1) {
        return array('ok' => false, 'msg' => 'Elige un NPC de la biblioteca.');
    }
    if (!$db->table_exists('ope_acompanante_solicitudes') || !$db->table_exists('ope_npcs_secundarios')) {
        return array('ok' => false, 'msg' => 'Falta la tabla de solicitudes. Ejecuta scripts/migrate-acompanantes.php');
    }
    if (!$db->num_rows($db->simple_select('ope_npcs_secundarios', 'id', "id = {$npc_id}", array('limit' => 1)))) {
        return array('ok' => false, 'msg' => 'Ese NPC no existe en la biblioteca.');
    }
    // ¿Ya lo tiene asignado?
    if ($db->table_exists('ope_acompanantes')
        && $db->num_rows($db->simple_select('ope_acompanantes', 'id', "pid = {$pid} AND npc_id = {$npc_id}", array('limit' => 1)))) {
        return array('ok' => false, 'msg' => 'Ya tienes ese acompañante asignado.');
    }
    // ¿Slots llenos?
    $asignados = $db->table_exists('ope_acompanantes')
        ? (int) $db->fetch_field($db->simple_select('ope_acompanantes', 'COUNT(*) c', "pid = {$pid}"), 'c')
        : 0;
    // Solicitudes pendientes de este personaje.
    $pend = (int) $db->fetch_field(
        $db->simple_select('ope_acompanante_solicitudes', 'COUNT(*) c', "pid = {$pid} AND estado = 'pendiente'"),
        'c'
    );
    if ($asignados + $pend >= $max) {
        return array('ok' => false, 'msg' => 'Has alcanzado el máximo de acompañantes (contando solicitudes pendientes). Retira uno o espera la resolución.');
    }
    // ¿Ya hay una solicitud pendiente para el mismo NPC?
    if ($db->num_rows($db->simple_select('ope_acompanante_solicitudes', 'id', "pid = {$pid} AND npc_id = {$npc_id} AND estado = 'pendiente'", array('limit' => 1)))) {
        return array('ok' => false, 'msg' => 'Ya tienes una solicitud pendiente para ese NPC.');
    }

    $db->insert_query('ope_acompanante_solicitudes', array(
        'pid'      => $pid,
        'uid'      => $uid,
        'npc_id'   => $npc_id,
        'motivo'   => $db->escape_string(mb_substr($motivo, 0, 1000)),
        'estado'   => 'pendiente',
        'dateline' => TIME_NOW,
    ));

    return array('ok' => true, 'msg' => 'Solicitud enviada. El staff la revisará pronto.');
}

/**
 * Cancela una solicitud pendiente propia (por el jugador).
 */
function ope_rol_acompanante_solicitud_cancelar($sid, $pid)
{
    global $db;
    $sid = (int) $sid;
    $pid = (int) $pid;
    if ($sid < 1 || $pid < 1 || !$db->table_exists('ope_acompanante_solicitudes')) {
        return array('ok' => false, 'msg' => 'No se pudo cancelar.');
    }
    $db->delete_query('ope_acompanante_solicitudes', "id = {$sid} AND pid = {$pid} AND estado = 'pendiente'");
    return array('ok' => true, 'msg' => 'Solicitud cancelada.');
}

/**
 * Solicitudes de un personaje (todas, más recientes primero), con nombre NPC.
 */
function ope_rol_char_solicitudes_acompanante($pid)
{
    global $db;
    $pid = (int) $pid;
    $out = array();
    if ($pid < 1 || !$db->table_exists('ope_acompanante_solicitudes')) {
        return $out;
    }
    $q = $db->simple_select('ope_acompanante_solicitudes', '*', "pid = {$pid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 50));
    while ($r = $db->fetch_array($q)) {
        $npc = ope_rol_npc_sec_by_id((int) $r['npc_id']);
        $r['npc_nombre'] = $npc ? (string) ($npc['nombre'] ?? '') : 'NPC eliminado';
        $out[] = $r;
    }
    return $out;
}

/**
 * Solicitudes de acompañante pendientes (cola de staff), con datos del NPC y jugador.
 */
function ope_rol_acompanante_solicitudes_pendientes()
{
    global $db;
    $out = array();
    if (!$db->table_exists('ope_acompanante_solicitudes')) {
        return $out;
    }
    $q = $db->simple_select('ope_acompanante_solicitudes', '*', "estado = 'pendiente'", array('order_by' => 'dateline', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $r['npc'] = ope_rol_npc_sec_by_id((int) $r['npc_id']);
        $r['pj_nombre'] = function_exists('ope_rol_cat_nombre_pid')
            ? ope_rol_cat_nombre_pid((int) $r['pid'])
            : '';
        $r['owner'] = '';
        if ((int) $r['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int) $r['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) {
                $r['owner'] = (string) $db->fetch_field($uq, 'username');
            }
        }
        $r['asignados'] = $db->table_exists('ope_acompanantes')
            ? (int) $db->fetch_field($db->simple_select('ope_acompanantes', 'COUNT(*) c', 'pid = ' . (int) $r['pid']), 'c')
            : 0;
        $out[] = $r;
    }
    return $out;
}

/** Nº de solicitudes de acompañante pendientes (para badges). */
function ope_rol_acompanante_solicitudes_pend_count()
{
    global $db;
    if (!$db->table_exists('ope_acompanante_solicitudes')) {
        return 0;
    }
    return (int) $db->fetch_field(
        $db->simple_select('ope_acompanante_solicitudes', 'COUNT(*) c', "estado = 'pendiente'"),
        'c'
    );
}

/**
 * Aprueba una solicitud: asigna el NPC al primer slot libre del personaje.
 */
function ope_rol_acompanante_solicitud_aprobar($sid, $staff_uid, $nota = '')
{
    global $db;
    $sid = (int) $sid;
    $staff_uid = (int) $staff_uid;
    $nota = trim((string) $nota);
    if ($sid < 1 || !$db->table_exists('ope_acompanante_solicitudes')) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    $q = $db->simple_select('ope_acompanante_solicitudes', '*', "id = {$sid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    $sol = $db->fetch_array($q);
    if ((string) $sol['estado'] !== 'pendiente') {
        return array('ok' => false, 'msg' => 'Esa solicitud ya está resuelta.');
    }
    $pid = (int) $sol['pid'];
    $npc_id = (int) $sol['npc_id'];

    $slot = ope_rol_acompanante_slot_libre($pid);
    if ($slot < 1) {
        return array('ok' => false, 'msg' => 'El personaje ya tiene los slots llenos. No se puede aprobar hasta que libere uno.');
    }
    $res = ope_rol_acompanante_asignar($pid, $npc_id, $slot);
    if (!$res['ok']) {
        return $res;
    }
    $db->update_query('ope_acompanante_solicitudes', array(
        'estado'     => 'aprobada',
        'slot'       => $slot,
        'staff_uid'  => $staff_uid,
        'staff_nota' => $db->escape_string(mb_substr($nota, 0, 500)),
        'resolved'   => TIME_NOW,
    ), "id = {$sid}");

    return array('ok' => true, 'msg' => 'Solicitud aprobada: acompañante asignado al slot ' . $slot . '.');
}

/**
 * Rechaza una solicitud con nota opcional del staff.
 */
function ope_rol_acompanante_solicitud_rechazar($sid, $staff_uid, $nota = '')
{
    global $db;
    $sid = (int) $sid;
    $staff_uid = (int) $staff_uid;
    $nota = trim((string) $nota);
    if ($sid < 1 || !$db->table_exists('ope_acompanante_solicitudes')) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    $q = $db->simple_select('ope_acompanante_solicitudes', 'estado', "id = {$sid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return array('ok' => false, 'msg' => 'Solicitud no encontrada.');
    }
    if ((string) $db->fetch_field($q, 'estado') !== 'pendiente') {
        return array('ok' => false, 'msg' => 'Esa solicitud ya está resuelta.');
    }
    $db->update_query('ope_acompanante_solicitudes', array(
        'estado'     => 'rechazada',
        'staff_uid'  => $staff_uid,
        'staff_nota' => $db->escape_string(mb_substr($nota, 0, 500)),
        'resolved'   => TIME_NOW,
    ), "id = {$sid}");

    return array('ok' => true, 'msg' => 'Solicitud rechazada.');
}

/**
 * HTML de carta de NPC usado en un post (técnica resaltada).
 */
function ope_rol_npc_sec_used_html(array $npc, $tec_idx)
{
    $tec_idx = (int) $tec_idx;
    $tecnicas = ope_rol_npc_sec_norm_tecnicas($npc['tecnicas'] ?? null);
    if ($tec_idx < 0 || $tec_idx >= count($tecnicas)) {
        $tec_idx = -1;
    }
    $card = ope_rol_npc_sec_card_html($npc, $tec_idx);
    return str_replace('class="ons-card"', 'class="ons-card is-post-used"', $card);
}

/** CSS del "naipe" de carta de técnica (una sola vez por página). */
function ope_rol_tecnica_card_css()
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    /* CSS en docs/themes/ope.css — .ope-tk / .ope-tk-deck */
    return '';
}

/**
 * Inyecta las etiquetas de Época (Presente/Pasado) y Tipo de Tema en showthread.
 */
function ope_rol_showthread_tags()
{
    global $thread, $db;

    if (!is_array($thread) || empty($thread['tid'])) {
        return;
    }

    $temp_tipo  = !empty($thread['temporal_tipo']) ? strtolower($thread['temporal_tipo']) : 'presente';
    $temp_fecha = !empty($thread['temporal_fecha']) ? htmlspecialchars_uni($thread['temporal_fecha']) : my_date('j F Y', $thread['dateline']);
    $tema_tipo  = !empty($thread['tema_tipo']) ? strtoupper(htmlspecialchars_uni($thread['tema_tipo'])) : 'SOCIAL';

    $temp_badge_class = ($temp_tipo === 'pasado') ? 'ope-tag-pasado' : 'ope-tag-presente';
    $temp_label       = ($temp_tipo === 'pasado') ? 'PASADO' : 'PRESENTE';

    $thread['ope_tags_html'] = '<div class="ope-th-tags-row">'
        . '<span class="ope-tag-badge ' . $temp_badge_class . '">' . $temp_label . ' &middot; ' . $temp_fecha . '</span>'
        . '<span class="ope-tag-badge ope-tag-tipo">' . $tema_tipo . '</span>'
        . '</div>';
}

// ==== Funciones hooked restauradas (F6.3-código) ====


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

    $uid      = (int) ($dh->data['uid'] ?? 0);
    $char_pid = ope_rol_active_pid_for($uid);
    if ($char_pid < 1) {
        return $dh;
    }

    $tid = (int) ($dh->data['tid'] ?? ($dh->tid ?? 0));

    $q = $db->simple_select('rol_personajes', '*', "pid = {$char_pid}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return $dh;
    }
    $char = $db->fetch_array($q);
    $datos = json_decode((string) ($char['datos'] ?? ''), true) ?: array();

    // 1. LOCK CONGELACIÓN DE FICHA EN EL HILO (mybb_rol_thread_snapshots)
    $thread_snap = null;
    if ($tid > 0 && $db->table_exists('rol_thread_snapshots')) {
        $ts_q = $db->simple_select('rol_thread_snapshots', '*', "tid = {$tid} AND pid = {$char_pid}", array('limit' => 1));
        if ($db->num_rows($ts_q)) {
            $thread_snap = $db->fetch_array($ts_q);
        } else {
            // Crear congelación inicial para este hilo (misma verdad que ficha.php)
            $stats_json_d = json_decode((string) ($char['stats_json'] ?? ''), true);
            $stats_base = (is_array($stats_json_d) && !empty($stats_json_d))
                ? $stats_json_d
                : (is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array());
            $inv        = json_decode((string) ($char['inventario'] ?? ''), true) ?: array();
            $items      = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();
            $stats_ganados = (int) ($char['stats_ganados'] ?? ($datos['stats_ganados'] ?? 0));
            $nivel_truth = function_exists('ope_rol_nivel_from_stats_comprados')
                ? (int) ope_rol_nivel_from_stats_comprados($stats_ganados)
                : max(1, (int) ($char['nivel'] ?? 1));
            $fruta = ope_rol_fruta_snapshot_payload($char_pid, $stats_base);
            $npcs  = $char['npcs'] ?? '';
            $fac_raw = (string) ($datos['faccion'] ?? ($char['faccion'] ?? ''));

            $now = defined('TIME_NOW') ? TIME_NOW : time();
            $db->insert_query('rol_thread_snapshots', array(
                'tid'             => $tid,
                'pid'             => $char_pid,
                'nivel'           => $nivel_truth,
                'rango'           => $db->escape_string((string)($char['rango'] ?? 'Rango E')),
                'faccion'         => $db->escape_string($fac_raw !== '' ? $fac_raw : 'Pirata'),
                'stats_base_json' => $db->escape_string(json_encode($stats_base, JSON_UNESCAPED_UNICODE)),
                'mochila_json'    => $db->escape_string(json_encode($items, JSON_UNESCAPED_UNICODE)),
                'fruta_json'      => $db->escape_string(json_encode($fruta, JSON_UNESCAPED_UNICODE)),
                'npcs_json'       => $db->escape_string(json_encode($npcs, JSON_UNESCAPED_UNICODE)),
                'dateline'        => $now,
            ));

            $ts_q2 = $db->simple_select('rol_thread_snapshots', '*', "tid = {$tid} AND pid = {$char_pid}", array('limit' => 1));
            if ($db->num_rows($ts_q2)) {
                $thread_snap = $db->fetch_array($ts_q2);
            }
        }
    }

    // 2. STATS & VITALES DEL POST (stats_json = fuente de verdad OPE)
    $stats_json_d = json_decode((string) ($char['stats_json'] ?? ''), true);
    $stats = (is_array($stats_json_d) && !empty($stats_json_d))
        ? $stats_json_d
        : (is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array());
    if ($thread_snap && !empty($thread_snap['stats_base_json'])) {
        $ts_s = json_decode((string)$thread_snap['stats_base_json'], true);
        if (is_array($ts_s) && !empty($ts_s)) {
            $stats = $ts_s;
        }
    }

    $pv_max = function_exists('ope_combat_calc_pv') ? ope_combat_calc_pv($stats) : 100;
    $en_max = function_exists('ope_combat_calc_en') ? ope_combat_calc_en($stats) : 100;

    $pv_actual = $pv_max;
    $en_actual = $en_max;

    // Arrastre entre posts del mismo combate
    if ($tid > 0 && $db->table_exists('posts') && $db->table_exists('rol_post_snapshot')) {
        $prev = $db->query("
            SELECT s.pv_actual, s.en_actual
            FROM {$db->table_prefix}rol_post_snapshot s
            INNER JOIN {$db->table_prefix}posts p ON (p.pid = s.pid)
            WHERE s.personaje_pid = {$char_pid} AND p.tid = {$tid} AND p.pid != {$post_pid}
            ORDER BY s.dateline DESC LIMIT 1
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

    $estados_json = null;
    $stats_mod_json = null;
    $msg = (string) ($dh->data['message'] ?? '');
    if ($msg !== '' && stripos($msg, '[rpgsys') !== false
        && preg_match('#\[rpgsys\]([^\[]*)\[/rpgsys\]#i', $msg, $mm)) {
        $pl = ope_rol_parse_cbt_payload($mm[1]);
        if ($pl['pv'] !== null) {
            $pv_actual = (int) $pl['pv'];
        }
        if ($pl['en'] !== null) {
            $en_actual = (int) $pl['en'];
        }
        if (!empty($pl['estados'])) {
            $estados_json = $db->escape_string(json_encode(array_values($pl['estados']), JSON_UNESCAPED_UNICODE));
        }
        if (!empty($pl['mods'])) {
            $stats_mod_json = $db->escape_string(json_encode($pl['mods'], JSON_UNESCAPED_UNICODE));
        }
    }

    $mochila_items = json_decode((string)($thread_snap['mochila_json'] ?? ''), true);
    if (!is_array($mochila_items)) {
        $inv = json_decode((string) ($char['inventario'] ?? ''), true) ?: array();
        $mochila_items = is_array($inv['encima'] ?? null) ? $inv['encima'] : array();
    }

    $fruta_val = json_decode((string)($thread_snap['fruta_json'] ?? ''), true);
    if (!is_array($fruta_val) || empty($fruta_val['nombre'])) {
        $fruta_val = ope_rol_fruta_snapshot_payload($char_pid, $stats);
    }
    $npcs_val = json_decode((string)($thread_snap['npcs_json'] ?? ''), true);

    $now = defined('TIME_NOW') ? TIME_NOW : time();
    $snap_data = array(
        'pid'            => $post_pid,
        'personaje_pid'  => $char_pid,
        'atributos'      => $db->escape_string(json_encode($stats, JSON_UNESCAPED_UNICODE)),
        'objetos'        => $db->escape_string(json_encode($mochila_items, JSON_UNESCAPED_UNICODE)),
        'pv_actual'      => $pv_actual,
        'en_actual'      => $en_actual,
        'pa_actual'      => 2,
        'estados_json'   => $estados_json,
        'stats_mod_json' => $stats_mod_json,
        'dateline'       => $now,
    );

    if ($db->field_exists('stats_json', 'rol_post_snapshot')) {
        $snap_data['stats_json']   = $db->escape_string(json_encode($stats, JSON_UNESCAPED_UNICODE));
        $snap_data['mochila_json'] = $db->escape_string(json_encode($mochila_items, JSON_UNESCAPED_UNICODE));
        $snap_data['fruta_json']   = $db->escape_string(json_encode($fruta_val, JSON_UNESCAPED_UNICODE));
        $snap_data['npcs_json']    = $db->escape_string(json_encode($npcs_val, JSON_UNESCAPED_UNICODE));
        $snap_data['mods_json']    = $stats_mod_json;
    }

    $exists = $db->simple_select('rol_post_snapshot', 'pid', "pid = {$post_pid}", array('limit' => 1));
    if ($db->num_rows($exists)) {
        $db->update_query('rol_post_snapshot', $snap_data, "pid = {$post_pid}");
    } else {
        $db->insert_query('rol_post_snapshot', $snap_data);
    }

    return $dh;
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

/**
 * Parser de bloques de rol (RPG System) en los mensajes.
 * Lo mecánico se envuelve en <!--OPERPGSYS-->…<!--/OPERPGSYS-->; el postbit
 * lo embebe en el reverso HUD. En thread review el pie se omite.
 */
function ope_rol_parse_rpg($message)
{
    if (stripos($message, '[combate') === false
        && stripos($message, '[accion') === false
        && stripos($message, '[tecnica') === false
        && stripos($message, '[estado') === false
        && stripos($message, '[dado') === false
        && stripos($message, '[carta') === false
        && stripos($message, '[rpgsys') === false) {
        return $message;
    }

    $extra_sections = '';
    $extra_meta = array();
    $orphan_cards = '';

    // [carta=haki.buso.imbuir] o [carta]haki.buso.imbuir[/carta]
    $message = preg_replace_callback('#\[carta=([^\]]+)\]#i', function ($m) use (&$orphan_cards) {
        $tok = ope_rol_parse_card_token($m[1]);
        if ($tok !== null) {
            $orphan_cards .= ope_rol_render_card_token($tok);
        }
        return '';
    }, $message);
    $message = preg_replace_callback('#\[carta\]([^\[]*)\[/carta\]#is', function ($m) use (&$orphan_cards) {
        $tok = ope_rol_parse_card_token($m[1]);
        if ($tok !== null) {
            $orphan_cards .= ope_rol_render_card_token($tok);
        }
        return '';
    }, $message);

    // Bloques combate / accion / tecnica → secciones del pie (no inline).
    $blocks = array(
        'combate' => array('cls' => 'ope-cbt', 'def' => 'Estado de combate', 'label' => 'combate'),
        'accion'  => array('cls' => 'ope-accion', 'def' => 'Acción', 'label' => 'acción'),
        'tecnica' => array('cls' => 'ope-cbt-tk', 'def' => 'Técnica', 'label' => 'técnica'),
    );
    foreach ($blocks as $tag => $cfg) {
        $pattern = '#\[' . $tag . '(?:=([^\]]*))?\]((?:(?!\[/?' . $tag . ').)*?)\[/' . $tag . '\]#is';
        $guard = 0;
        while ($guard < 40 && preg_match($pattern, $message)) {
            $message = preg_replace_callback($pattern, function ($m) use ($cfg, &$extra_sections, &$extra_meta) {
                $title = isset($m[1]) ? trim(trim($m[1]), "\"'") : '';
                if ($title === '') {
                    $title = $cfg['def'];
                }
                $body = trim($m[2]);
                $extra_sections .= '<div class="' . $cfg['cls'] . '">'
                    . '<div class="' . $cfg['cls'] . '-h">' . htmlspecialchars_uni($title) . '</div>'
                    . '<div class="' . $cfg['cls'] . '-b">' . $body . '</div>'
                    . '</div>';
                $extra_meta[] = $cfg['label'];
                return '';
            }, $message);
            $guard++;
        }
    }

    // Chips estado / dado → pie.
    $chips = '';
    $message = preg_replace_callback('#\[estado(?:=(positivo|negativo|neutral))?\]((?:(?!\[/?estado).)*?)\[/estado\]#is', function ($m) use (&$chips, &$extra_meta) {
        $tipo = isset($m[1]) && $m[1] !== '' ? strtolower($m[1]) : 'negativo';
        $chips .= '<span class="ope-estado ope-estado--' . $tipo . '">' . trim($m[2]) . '</span>';
        $extra_meta[] = 'estado';
        return '';
    }, $message);
    $message = preg_replace_callback('#\[dado\]((?:(?!\[/?dado).)*?)\[/dado\]#is', function ($m) use (&$chips, &$extra_meta) {
        $chips .= '<span class="ope-dado">&#9860; ' . trim($m[1]) . '</span>';
        $extra_meta[] = 'dado';
        return '';
    }, $message);
    if ($chips !== '') {
        $extra_sections .= '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">Marcas</div>'
            . '<div class="ope-rpgsys-estados">' . $chips . '</div></div>';
    }
    if ($orphan_cards !== '') {
        $extra_sections .= '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">Cartas</div>'
            . '<div class="ope-tk-deck ope-tk-deck--mix">' . $orphan_cards . '</div></div>';
        $extra_meta[] = 'cartas';
    }

    // [rpgsys] principal
    if (stripos($message, '[rpgsys') !== false) {
        $message = preg_replace_callback('#\[rpgsys\]([^\[]*)\[/rpgsys\]#is', function ($m) use ($extra_sections, $extra_meta) {
            $pl = ope_rol_parse_cbt_payload($m[1]);
            $ids = array_slice(array_values(array_unique($pl['cards'], SORT_REGULAR)), 0, 24);
            $cards = '';
            foreach ($ids as $cid) {
                $cards .= ope_rol_render_card_token($cid);
            }
            $ncards = $cards !== '' ? count($ids) : 0;

            $nons = 0;
            if (!empty($pl['ons']) && is_array($pl['ons'])) {
                $ons_npc = function_exists('ope_rol_npc_sec_by_id') ? ope_rol_npc_sec_by_id((int) ($pl['ons']['npc_id'] ?? 0)) : null;
                if ($ons_npc) {
                    $tec_idx = (int) ($pl['ons']['tec_idx'] ?? -1);
                    $cards .= ope_rol_npc_sec_used_html($ons_npc, $tec_idx);
                    $nons = 1;
                }
            }

            $estados_html = '';
            $nest = 0;
            if (!empty($pl['estados'])) {
                $cat = function_exists('ope_combat_estados') ? ope_combat_estados() : array();
                $chiprow = '';
                foreach ($pl['estados'] as $ek) {
                    $info = $cat[$ek] ?? null;
                    $nom  = htmlspecialchars_uni((string) ($info['nombre'] ?? $ek));
                    $tipo = htmlspecialchars_uni((string) ($info['tipo'] ?? 'negativo'));
                    $chiprow .= '<span class="ope-estado ope-estado--' . $tipo . '">' . $nom . '</span>';
                    $nest++;
                }
                if ($chiprow !== '') {
                    $estados_html = '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">Estados alterados</div>'
                                  . '<div class="ope-rpgsys-estados">' . $chiprow . '</div></div>';
                }
            }

            $mods_html = '';
            $nmod = 0;
            if (!empty($pl['mods'])) {
                $rows = '';
                foreach ($pl['mods'] as $stat => $mv) {
                    $val = (int) $mv['val'];
                    $sign = $val > 0 ? '+' : '';
                    $suf  = !empty($mv['pct']) ? '%' : '';
                    $dir  = $val >= 0 ? 'up' : 'down';
                    $rows .= '<span class="ope-rpgsys-mod ope-rpgsys-mod--' . $dir . '">'
                           . '<b>' . htmlspecialchars_uni($stat) . '</b> ' . $sign . $val . $suf . '</span>';
                    $nmod++;
                }
                if ($rows !== '') {
                    $mods_html = '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">Modificadores</div>'
                               . '<div class="ope-rpgsys-mods">' . $rows . '</div></div>';
                }
            }

            $vitals_html = '';
            if ($pl['pv'] !== null || $pl['en'] !== null) {
                $vparts = '';
                if ($pl['pv'] !== null) {
                    $vparts .= '<span class="ope-rpgsys-vital ope-rpgsys-vital--pv">PV <b>' . (int) $pl['pv'] . '</b></span>';
                }
                if ($pl['en'] !== null) {
                    $vparts .= '<span class="ope-rpgsys-vital ope-rpgsys-vital--en">EN <b>' . (int) $pl['en'] . '</b></span>';
                }
                $vitals_html = '<div class="ope-rpgsys-vitals">' . $vparts . '</div>';
            }

            $sec_title = ($ncards && $nons) ? 'Cartas y acompañante'
                : ($nons ? 'Acompañante' : 'Cartas usadas');
            $cards_html = $cards !== ''
                ? '<div class="ope-rpgsys-sec"><div class="ope-rpgsys-sec-h">' . $sec_title . '</div><div class="ope-tk-deck ope-tk-deck--mix">' . $cards . '</div></div>'
                : '';

            $inner = $vitals_html . $estados_html . $cards_html . $mods_html . $extra_sections;
            if (trim($inner) === '') {
                return '';
            }

            $meta = array();
            if ($nest) {
                $meta[] = $nest . ' estado' . ($nest === 1 ? '' : 's');
            }
            if ($ncards) {
                $meta[] = $ncards . ' carta' . ($ncards === 1 ? '' : 's');
            }
            if ($nons) {
                $meta[] = '1 acompañante';
            }
            if ($nmod) {
                $meta[] = $nmod . ' mod' . ($nmod === 1 ? '' : 's');
            }
            foreach ($extra_meta as $em) {
                $meta[] = $em;
            }
            $meta_txt = $meta ? implode(' · ', array_unique($meta)) : 'Sistema de rol';

            return ope_rol_wrap_rpgsys_footer($inner, $meta_txt);
        }, $message);
    } elseif ($extra_sections !== '') {
        // Sin [rpgsys] pero hay bloques mecánicos sueltos → pie igualmente.
        $meta_txt = $extra_meta ? implode(' · ', array_unique($extra_meta)) : 'Sistema de rol';
        $message .= ope_rol_wrap_rpgsys_footer($extra_sections, $meta_txt);
    }

    return $message;
}

/**
 * Al publicar un post: suma CU de Haki/Fruta por cartas jugadas en [rpgsys]/[carta].
 */
function ope_rol_cu_on_post(&$dh)
{
    global $db;

    $visible = (int) ($dh->post_insert_data['visible'] ?? 1);
    if ($visible !== 1) {
        return $dh;
    }

    $uid = (int) ($dh->data['uid'] ?? 0);
    $char_pid = function_exists('ope_rol_active_pid_for') ? ope_rol_active_pid_for($uid) : 0;
    if ($char_pid < 1) {
        return $dh;
    }

    $msg = (string) ($dh->data['message'] ?? '');
    if ($msg === '') {
        return $dh;
    }

    $tokens = array();
    if (preg_match_all('#\[rpgsys\]([^\[]*)\[/rpgsys\]#is', $msg, $mm)) {
        foreach ($mm[1] as $raw) {
            $pl = ope_rol_parse_cbt_payload($raw);
            foreach ($pl['cards'] as $c) {
                $tokens[] = $c;
            }
        }
    }
    if (preg_match_all('#\[carta=([^\]]+)\]#i', $msg, $mm2)) {
        foreach ($mm2[1] as $raw) {
            $t = ope_rol_parse_card_token($raw);
            if ($t !== null) {
                $tokens[] = $t;
            }
        }
    }
    if (preg_match_all('#\[carta\]([^\[]*)\[/carta\]#is', $msg, $mm3)) {
        foreach ($mm3[1] as $raw) {
            $t = ope_rol_parse_card_token($raw);
            if ($t !== null) {
                $tokens[] = $t;
            }
        }
    }

    if (empty($tokens)) {
        return $dh;
    }

    $tokens = array_values($tokens);
    foreach ($tokens as $tok) {
        if (is_string($tok) && strpos($tok, 'haki.') === 0 && function_exists('ope_haki_add_cu')) {
            $parts = explode('.', $tok);
            $tipo = $parts[1] ?? '';
            if (in_array($tipo, array('ken', 'buso', 'hao'), true)) {
                // CU cuenta si tiene al menos Nv.1 de ese tipo
                $row = function_exists('ope_haki_row') ? ope_haki_row($char_pid, $tipo) : null;
                if ($row && (int) ($row['nivel'] ?? 0) >= 1) {
                    ope_haki_add_cu($char_pid, $tipo, 1);
                }
            }
        } elseif (is_string($tok) && strpos($tok, 'fruta.') === 0 && function_exists('ope_fruta_add_cu')) {
            ope_fruta_add_cu($char_pid, 1);
        } elseif (is_int($tok) || (is_numeric($tok) && (int) $tok > 0)) {
            // Técnica del deck: si tags.fuente = haki|fruta
            if (function_exists('ope_rol_tecnica_by_id')) {
                $carta = ope_rol_tecnica_by_id((int) $tok);
                if ($carta) {
                    $fuente = '';
                    $tags = is_array($carta['tags'] ?? null) ? $carta['tags'] : array();
                    if (is_string($carta['tags'] ?? null)) {
                        $tags = json_decode($carta['tags'], true) ?: array();
                    }
                    $fuente = strtolower((string) ($tags['fuente'] ?? ($carta['fuente'] ?? '')));
                    if ($fuente === 'fruta' && function_exists('ope_fruta_add_cu')) {
                        ope_fruta_add_cu($char_pid, 1);
                    } elseif (in_array($fuente, array('haki', 'ken', 'buso', 'hao'), true) && function_exists('ope_haki_add_cu')) {
                        $tipo = ($fuente === 'haki') ? (string) ($tags['haki_tipo'] ?? 'buso') : $fuente;
                        if (in_array($tipo, array('ken', 'buso', 'hao'), true)) {
                            ope_haki_add_cu($char_pid, $tipo, 1);
                        }
                    }
                }
            }
        }
    }

    return $dh;
}

/**
 * Renderiza shortcodes del Oraculo de Viaje al mostrar posts.
 *   [viaje=123]                    → bloque completo del oraculo (primer post)
 *   [viaje-cierre=123]             → post de llegada solicitada por el jugador (aprobado)
 *   [viaje-solicitud-cierre=123]   → aviso de que el cierre esta pendiente de revision
 *   [viaje-rechazo=123]            → cierre rechazado (1er intento, vuelve a activo)
 *   [viaje-rechazo-cancelado=123]  → cierre rechazado (2do intento, viaje cancelado)
 */
function ope_rol_parse_viaje($message)
{
    if (stripos($message, '[viaje') === false) {
        return $message;
    }

    $message = preg_replace_callback('#\[viaje=(\d+)\]#i', function ($m) {
        // En showthread, la tarjeta del Oráculo YA se muestra arriba en $ope_viaje_panel
        if (defined('THIS_SCRIPT') && THIS_SCRIPT === 'showthread.php') {
            return '';
        }
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

    // Solicitud de cierre pendiente
    $message = preg_replace_callback('#\[viaje-solicitud-cierre=(\d+)\]#i', function ($m) {
        $vid = (int) $m[1];
        if ($vid < 1 || !function_exists('ope_viaje_por_id')) return '';
        $v = ope_viaje_por_id($vid);
        if (!$v) return '';
        return ope_viaje_revision_solicitud_post_html($v);
    }, $message);

    // Rechazo primer intento
    $message = preg_replace_callback('#\[viaje-rechazo=(\d+)\]#i', function ($m) {
        $vid = (int) $m[1];
        if ($vid < 1 || !function_exists('ope_viaje_por_id')) return '';
        $v = ope_viaje_por_id($vid);
        if (!$v) return '';
        return ope_viaje_revision_rechazo_post_html($v, false);
    }, $message);

    // Rechazo definitivo (cancelado)
    $message = preg_replace_callback('#\[viaje-rechazo-cancelado=(\d+)\]#i', function ($m) {
        $vid = (int) $m[1];
        if ($vid < 1 || !function_exists('ope_viaje_por_id')) return '';
        $v = ope_viaje_por_id($vid);
        if (!$v) return '';
        return ope_viaje_revision_rechazo_post_html($v, true);
    }, $message);

    return $message;
}

/** Renderiza [mision=TOMA_ID] en posts. */
function ope_rol_parse_mision($message)
{
    if (stripos($message, '[mision') === false) {
        return $message;
    }
    $message = preg_replace_callback('#\[mision=(\d+)\]#i', function ($m) {
        if (defined('THIS_SCRIPT') && THIS_SCRIPT === 'showthread.php') {
            return '';
        }
        $toma_id = (int)$m[1];
        if ($toma_id < 1 || !function_exists('ope_mision_post_html')) return (string)$m[0];

        global $db;
        $q = $db->simple_select('rol_mision_tomas', '*', "toma_id = {$toma_id}", array('limit' => 1));
        if (!$db->num_rows($q)) return (string)$m[0];
        $toma = $db->fetch_array($q);

        // Enriquecer con datos de mision y PJ
        $pid = (int)($toma['pid'] ?? 0);
        $toma['pj_nombre'] = function_exists('ope_rol_cat_nombre_pid') ? ope_rol_cat_nombre_pid($pid) : '';
        $mid = (int)($toma['mision_id'] ?? 0);
        if ($mid > 0 && function_exists('ope_mision_por_id')) {
            $mision = ope_mision_por_id($mid);
            if ($mision) {
                $toma['mision_titulo'] = (string)($mision['titulo'] ?? '');
                $toma['rango'] = (string)($mision['rango'] ?? 'D');
                $toma['peligrosidad'] = (int)($mision['peligrosidad'] ?? 1);
                $zslug = (string)($mision['zona_slug'] ?? '');
                $toma['mision_zona'] = $zslug !== '' && function_exists('ope_isla_nombre')
                                       ? ope_isla_nombre($zslug) : $zslug;
            }
        }

        $oraculo = json_decode((string)($toma['oraculo_json'] ?? ''), true);
        if (!is_array($oraculo)) {
            $oraculo = array('cartas' => array(), 'narrativa' => '');
        }
        return ope_mision_post_html($toma, $oraculo);
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

    $GLOBALS['ope_viaje_panel'] = $card;

    // Oculta el primer post (OPE Eternal) para que el oráculo no se vea como post.
    $first_pid = (int) ($thread['firstpost'] ?? 0);
    if ($first_pid > 0 && !empty($posts) && is_string($posts)) {
        // Eliminar el primer post de la lista (Narrador) en hilos de viaje
        $pattern1 = '#<a\s+name="pid' . $first_pid . '"[^>]*></a>\s*<div\s+class="ope-postbit-container"\s+id="post-container-' . $first_pid . '">[\s\S]*?<!-- LADO TRASERO: FICHA CONGELADA DEL POST \(3D CARD FLIP\) -->[\s\S]*?</div>\s*</div>\s*</div>#i';
        $pattern2 = '#<a\s+name="pid' . $first_pid . '"[^>]*></a>\s*<div\s+class="ope-postbit-container"\s+id="post-container-' . $first_pid . '">[\s\S]*?</div>\s*</div>#i';
        $pattern3 = '#<a\s+name="pid' . $first_pid . '"[^>]*></a>\s*<article\b[^>]*id="post_' . $first_pid . '"[\s\S]*?</article>#i';
        
        $posts_new = preg_replace($pattern1, '', $posts, 1);
        if ($posts_new === $posts) {
            $posts_new = preg_replace($pattern2, '', $posts, 1);
        }
        if ($posts_new === $posts) {
            $posts_new = preg_replace($pattern3, '', $posts, 1);
        }
        $posts = $posts_new;
    }

    if (function_exists('ope_oraculo_showthread_scripts')) {
        $GLOBALS['ope_viaje_scripts'] = ope_oraculo_showthread_scripts();
    }
}

/** Panel de mision activo en showthread: cabecera + ocultar post Narrador. */
function ope_rol_mision_showthread_end()
{
    global $tid, $mybb, $thread, $posts;

    $GLOBALS['ope_mision_panel'] = '';

    $toma = function_exists('ope_mision_por_tid') ? ope_mision_por_tid((int) $tid) : null;
    if (!$toma) {
        return;
    }

    // Enriquecer con datos de mision y PJ
    $pid = (int)($toma['pid'] ?? 0);
    $toma['pj_nombre'] = function_exists('ope_rol_cat_nombre_pid') ? ope_rol_cat_nombre_pid($pid) : '';
    $mid = (int)($toma['mision_id'] ?? 0);
    if ($mid > 0 && function_exists('ope_mision_por_id')) {
        $mision_data = ope_mision_por_id($mid);
        if ($mision_data) {
            $toma['mision_titulo']  = (string)($mision_data['titulo'] ?? '');
            $toma['rango']          = (string)($mision_data['rango'] ?? 'D');
            $toma['peligrosidad']   = (int)($mision_data['peligrosidad'] ?? 1);
            $zslug = (string)($mision_data['zona_slug'] ?? '');
            $toma['mision_zona']    = $zslug !== '' && function_exists('ope_isla_nombre')
                                      ? ope_isla_nombre($zslug) : $zslug;
        }
    }

    $panel = '';
    if (function_exists('ope_mision_post_html')) {
        $oraculo = json_decode((string)($toma['oraculo_json'] ?? ''), true);
        if (!is_array($oraculo)) {
            $oraculo = array('cartas' => array(), 'narrativa' => '');
        }
        $panel = '<div class="ope-viaje-header ope-mision-header">' . ope_mision_post_html($toma, $oraculo) . '</div>';
    }
    $GLOBALS['ope_mision_panel'] = $panel;

    // Ocultar el primer post del Narrador
    $first_pid = (int)($thread['firstpost'] ?? 0);
    if ($first_pid > 0 && !empty($posts) && is_string($posts)) {
        $pattern = '#<a\s+name="pid' . $first_pid . '"[^>]*></a>\s*<div\s+class="ope-postbit-container"\s+id="post-container-' . $first_pid . '">[\s\S]*?</div>\s*</div>\s*</div>#i';
        $posts = preg_replace($pattern, '', $posts, 1);
    }
}

function ope_rol_inject_zonab_editor($contents)
{
    if (defined('IN_ADMINCP') || !is_string($contents) || $contents === '') {
        return $contents;
    }
    // Solo en las páginas con editor de mensaje (nuevo tema / respuesta / edición).
    $script = defined('THIS_SCRIPT') ? THIS_SCRIPT : '';
    if (!in_array($script, array('newthread.php', 'newreply.php', 'editpost.php'), true)) {
        return $contents;
    }
    if (!function_exists('ope7_zonab_editor_html')) {
        return $contents;
    }
    // El panel devuelve '' si el usuario no tiene personaje 7 Seas activo con vida.
    $html = (string) ope7_zonab_editor_html();
    if ($html === '') {
        return $contents;
    }
    // Incrustar justo después del textarea de mensaje (bajo el editor), sin tocar
    // el resto de la página. Si no hay textarea de mensaje, no se inyecta nada.
    $pos = stripos($contents, 'name="message"');
    if ($pos === false) {
        return $contents;
    }
    $tail = strpos($contents, '</textarea>', $pos);
    if ($tail === false) {
        return $contents;
    }
    $after = $tail + strlen('</textarea>');
    return substr($contents, 0, $after) . "\n" . $html . "\n" . substr($contents, $after);
}
