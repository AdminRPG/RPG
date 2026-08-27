<?php
/**
 * One Piece: 7 Seas · Subproceso de test del wizard (F1.1)
 * ---------------------------------------------------------
 * Uso: php scripts/_wizard-post-child.php <payload.json> <out.txt>
 * Aplica el payload como $_POST, captura la cabecera Location (si hay) en
 * out.txt vía shutdown (el redirect hace exit) y requiere crear-personaje.php.
 */
$payload = json_decode(file_get_contents($argv[1]), true);
$_POST = $payload;
$_REQUEST = $payload;
$_SERVER['REQUEST_METHOD'] = 'POST';

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-personaje.php');
require __DIR__ . '/../global.php';

$mybb->user['uid'] = 1;
$mybb->user['username'] = 'admin';
$mybb->request_method = 'post';

register_shutdown_function(function () use ($argv) {
    $loc = '';
    foreach (headers_list() as $h) {
        if (stripos($h, 'Location:') === 0) {
            $loc = trim(substr($h, 9));
        }
    }
    file_put_contents($argv[2], $loc);
});

require __DIR__ . '/../crear-personaje.php';
