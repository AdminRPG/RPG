<?php
/**
 * Plugin puente I-Forge-RPG
 * Conecta MyBB con la API de rol (autenticacion JWT, personajes, widgets)
 *
 * Hooks implementados:
 *  - member_do_login_end:     genera JWT + asegura cuenta de rol
 *  - member_do_logout_end:    elimina cookie rol_token
 *  - global_end:              inyecta rol-widgets.js
 *  - postbit:                 inyecta widget de personaje en cada post
 *  - member_profile_end:      inyecta selector de personajes en el perfil
 *  - newreply_start:          inyecta selector de personaje activo al responder
 *  - newthread_start:         inyecta selector de personaje activo al crear hilo
 *  - datahandler_post_insert: vincula el post al personaje activo
 */

defined('IN_MYBB') or die('Acceso directo no permitido');

define('ROL_API_URL', 'http://localhost:8080/api/v1');
define('ROL_JWT_COOKIE', 'rol_token');
define('ROL_CHAR_COOKIE', 'rol_char_id');

// ─── Login: generar JWT + asegurar cuenta de rol ───
function rolbridge_login_end()
{
    global $mybb;

    if (!$mybb->user['uid']) return;

    $payload = [
        'mybb_user_id' => (int) $mybb->user['uid'],
        'username'     => $mybb->user['username'],
        'usergroup'    => (int) $mybb->user['usergroup'],
        'iat'          => time(),
        'exp'          => time() + (int) (getenv('ROL_JWT_EXPIRY') ?: 3600),
    ];

    $secret = getenv('ROL_JWT_SECRET');
    if (!$secret || $secret === 'change-this-to-a-random-secret') {
        error_log('ROL_JWT_SECRET not properly configured');
        return;
    }

    $jwt = \App\Auth\JWTService::encode($payload, $secret);
    my_setcookie(ROL_JWT_COOKIE, $jwt, time() + 3600, true);

    // Asegurar que existe la cuenta de rol
    $ch = curl_init(ROL_API_URL . '/cuenta/mi-cuenta');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $jwt],
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400 || $response === false) {
        error_log("ROL API error: HTTP {$httpCode} for /cuenta/mi-cuenta. Response: " . ($response ?: 'false'));
        return;
    }
}
$plugins->add_hook('member_do_login_end', 'rolbridge_login_end');

// ─── Logout: eliminar cookies ───
function rolbridge_logout_end()
{
    my_setcookie(ROL_JWT_COOKIE, '', time() - 3600, true);
    my_setcookie(ROL_CHAR_COOKIE, '', time() - 3600, true);
}
$plugins->add_hook('member_do_logout_end', 'rolbridge_logout_end');

// ─── Global: inyectar JS y variables ───
function rolbridge_global_end()
{
    global $mybb, $templates;

    if (!$mybb->user['uid']) return;

    echo '<script src="' . $mybb->settings['bburl'] . '/jscripts/rol-widgets.js"></script>';
    echo '<script>var ROL_API_URL = "' . ROL_API_URL . '";</script>';
}
$plugins->add_hook('global_end', 'rolbridge_global_end');

// ─── Postbit: mostrar personaje activo del autor ───
function rolbridge_postbit(&$post)
{
    if (!empty($post['uid'])) {
        $post['rol_widget'] = '<div class="rol-ficha-widget" data-user="' . (int) $post['uid'] . '"></div>';
    }
}
$plugins->add_hook('postbit', 'rolbridge_postbit');
$plugins->add_hook('postbit_prev', 'rolbridge_postbit');

// ─── Perfil: selector de personajes y ficha completa ───
function rolbridge_member_profile_end()
{
    global $memprofile;

    if ($memprofile['uid']) {
        echo '<div id="rol-perfil-personajes" data-user="' . (int) $memprofile['uid'] . '"></div>';
    }
}
$plugins->add_hook('member_profile_end', 'rolbridge_member_profile_end');

// ─── Nuevo post/respuesta: selector de personaje activo ───
function rolbridge_newreply_start()
{
    global $mybb, $templates;

    if ($mybb->user['uid']) {
        echo '<div id="rol-char-selector" class="rol-char-selector">
            <label><strong>Publicando como:</strong></label>
            <select id="rol-active-char" name="rol_char_id">
                <option value="">Cargando personajes...</option>
            </select>
        </div>';
    }
}
$plugins->add_hook('newreply_start', 'rolbridge_newreply_start');

function rolbridge_newthread_start()
{
    rolbridge_newreply_start();
}
$plugins->add_hook('newthread_start', 'rolbridge_newthread_start');

// ─── Guardar post: vincular al personaje seleccionado ───
function rolbridge_datahandler_post_insert(&$post)
{
    $charId = (int) ($_POST['rol_char_id'] ?? 0);
    if ($charId > 0) {
        global $db;
        $check = $db->simple_select('rol_personajes', 'pid', "pid = {$charId} AND uid = " . (int)$mybb->user['uid']);
        if (!$check || $db->num_rows($check) === 0) {
            $charId = 0;
        }
    }
    if ($charId > 0) {
        global $mybb;
        my_setcookie(ROL_CHAR_COOKIE, $charId, time() + 86400 * 30, true);
    }
}
$plugins->add_hook('datahandler_post_insert', 'rolbridge_datahandler_post_insert');

// ─── Info del plugin en ACP ───
function rolbridge_info()
{
    return [
        'name' => 'I-Forge-RPG Bridge',
        'description' => 'Puente entre MyBB y la API de rol. Proporciona autenticacion JWT, gestion de personajes y widgets. Requiere \App\Auth\JWTService.',
        'website' => '',
        'author' => 'I-Forge-RPG',
        'authorsite' => '',
        'version' => '0.1.0',
        'compatibility' => '18*',
        'codename' => 'rolbridge',
    ];
}
