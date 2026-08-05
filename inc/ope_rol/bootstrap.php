<?php
/**
 * Bootstrap del backend One Piece: Eternal (ope_rol).
 * --------------------------------------------------
 * Punto de entrada canónico. El plugin y las páginas pueden cargar esto
 * en lugar de una lista larga de requires. Los stubs en inc/ope_rol_*.php
 * siguen funcionando para compatibilidad.
 *
 * Uso:
 *   require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
 */
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$dir = __DIR__;

// Core (data arrastra catalogos + dominio/creacion)
require_once $dir . '/core/data.php';
require_once $dir . '/core/system.php';

// Catálogos de gestión staff (tienda, tripulaciones, bibliotecas…)
require_once $dir . '/catalogos/gestion.php';

// Sistemas de progresión / fama
require_once $dir . '/sistemas/haki.php';
require_once $dir . '/sistemas/frutas.php';
require_once $dir . '/sistemas/enlace.php';
require_once $dir . '/sistemas/renombre.php';
require_once $dir . '/sistemas/pl.php';
require_once $dir . '/sistemas/rachas.php';

// Mundo vivo + viajes
require_once $dir . '/mundo/mundo.php';
require_once $dir . '/mundo/oraculo.php';
require_once $dir . '/mundo/oraculo_post.php';
require_once $dir . '/mundo/viajes.php';

// Navegacion: catalogo de islas, matriz de rutas, barcos, items, oraculo v2
require_once $dir . '/mundo/islas_cat.php';
require_once $dir . '/mundo/matriz_rutas.php';
require_once $dir . '/mundo/barcos.php';
require_once $dir . '/mundo/nav_items.php';
require_once $dir . '/mundo/oraculo_v2.php';
require_once $dir . '/mundo/viaje_ai.php';
require_once $dir . '/mundo/viaje_cola.php';
require_once $dir . '/mundo/viaje_revision.php';
require_once $dir . '/mundo/misiones.php';
require_once $dir . '/mundo/mision_oraculo.php';
require_once $dir . '/mundo/mision_ai.php';
require_once $dir . '/mundo/mision_post.php';
require_once $dir . '/mundo/viaje_revision_ai.php';

// Trámites
require_once $dir . '/tramites/tramites.php';
