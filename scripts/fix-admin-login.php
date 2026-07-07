<?php
/**
 * Repara acceso Admin: limpia bloqueos de login y restablece contraseña.
 * Ejecutar una vez: php scripts/fix-admin-login.php [nueva_contraseña]
 */
define('IN_MYBB', 1);
require __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/functions_user.php';

$uid = 1;
$newpass = $argv[1] ?? 'Admin2026!';

if (strlen($newpass) < 6) {
    fwrite(STDERR, "La contraseña debe tener al menos 6 caracteres.\n");
    exit(1);
}

$fields = create_password($newpass);
$loginkey = generate_loginkey();

$db->update_query('users', array(
    'password' => $fields['password'],
    'salt' => $fields['salt'],
    'loginkey' => $loginkey,
    'loginattempts' => 0,
    'loginlockoutexpiry' => 0,
), "uid = '{$uid}'");

$db->delete_query('awaitingactivation', "uid = '{$uid}' AND type = 'l'");

if ($db->table_exists('adminoptions')) {
    $db->update_query('adminoptions', array(
        'loginattempts' => 0,
        'loginlockoutexpiry' => 0,
    ), "uid = '{$uid}'");
}

echo "Admin (uid=1) reparado.\n";
echo "Usuario: Admin\n";
echo "Contraseña: {$newpass}\n";
echo "Intentos de login y bloqueos reseteados.\n";
