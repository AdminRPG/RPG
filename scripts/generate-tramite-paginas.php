<?php
/**
 * One Piece: 7 Seas · Generador de las páginas de trámite (tramite-NN.php)
 * -----------------------------------------------------------------------------
 * Idempotente: regenera los 56 ficheros tramite-NN.php (los 11 solo-staff no
 * tienen página) como wrappers finos que delegan en inc/ope_rol/tramites/paginas.php.
 * Ejecutar: php scripts/generate-tramite-paginas.php
 */

define('IN_MYBB', 1);
require_once __DIR__ . '/../inc/ope_rol/tramites/catalogo.php';

$cat = ope7_tramites_catalogo();
$solo_staff = array(18, 21, 24, 30, 36, 49, 54, 55, 59, 60, 61);
$raiz = dirname(__DIR__);
$n = 0;

foreach ($cat as $numero => $e) {
    if (in_array($numero, $solo_staff, true)) {
        continue;
    }
    $nombre = (string) $e['nombre'];
    $file = sprintf('tramite-%02d.php', $numero);

    $php = "<?php\n"
         . "/**\n"
         . " * One Piece: 7 Seas · Trámite {$numero} — {$nombre}\n"
         . " * -----------------------------------------------------------------------------\n"
         . " * Ventanilla del jugador: formulario + enrutado al motor\n"
         . " * (inc/ope_rol/tramites/paginas.php). Scope CSS: body.ope-pg-tramite.\n"
         . " */\n"
         . "define('IN_MYBB', 1);\n"
         . "define('THIS_SCRIPT', '{$file}');\n"
         . "require_once './global.php';\n"
         . "require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';\n"
         . "require_once MYBB_ROOT . 'inc/ope_rol/tramites/paginas.php';\n\n"
         . "ope7_tramite_pagina({$numero});\n";

    file_put_contents($raiz . '/' . $file, $php);
    echo "  {$file} — {$nombre}\n";
    $n++;
}

echo "Generadas {$n} páginas de trámite (los 11 solo-staff se quedan en el hub con badge).\n";
echo "=== DONE ===\n";
