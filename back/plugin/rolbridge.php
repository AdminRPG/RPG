<?php
/**
 * Plugin puente MyBB ↔ API de Rol
 * Hooks: login, logout, postbit, member_profile
 */

function rolbridge_info(): array
{
    return [
        'name'          => 'Roleo Bridge',
        'description'   => 'Puente de autenticación y widgets de rol entre MyBB y la API propia.',
        'website'       => '',
        'author'        => '',
        'version'       => '1.0',
        'compatibility' => '18xx',
    ];
}

function rolbridge_activate(): void
{
    global $db;
    // Crear tabla de sesiones puente si no existe
}

function rolbridge_deactivate(): void
{
    // Limpieza si es necesaria
}

// --- Hooks ---

// Login: emitir JWT tras login exitoso
$plugins->add_hook('member_do_login_end', 'rolbridge_login_end');

function rolbridge_login_end(): void
{
    global $mybb;
    // Generar JWT con mybb_user_id, username, usergroup
    // Guardar en cookie rol_token
}

// Logout: eliminar cookie rol_token
$plugins->add_hook('member_do_logout_end', 'rolbridge_logout_end');

function rolbridge_logout_end(): void
{
    // Eliminar cookie rol_token
}

// Postbit: widget de ficha resumida
$plugins->add_hook('postbit', 'rolbridge_postbit');

function rolbridge_postbit(array &$post): void
{
    $post['rol_widget'] = '<div class="rol-ficha-widget" data-user="' . (int)$post['uid'] . '"></div>';
}

// Perfil: widget de ficha completa
$plugins->add_hook('member_profile_end', 'rolbridge_member_profile');

function rolbridge_member_profile(): void
{
    global $memprofile;
    // Inyectar contenedor para ficha completa
}

// Inyectar JS en todas las páginas
$plugins->add_hook('global_end', 'rolbridge_inject_scripts');

function rolbridge_inject_scripts(): void
{
    global $templates;
    // Cargar jscripts/rol-widgets.js
}
