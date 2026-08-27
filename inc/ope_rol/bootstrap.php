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

// Dominio 7 Seas (F1): personajes — puntero activo, secundarios, validación
require_once $dir . '/dominio/personajes.php';

// Ficha 7 Seas (F1.2): renderer del expediente mybb_ope_*
require_once $dir . '/dominio/ficha.php';

// Catálogos de gestión staff (tienda, tripulaciones, bibliotecas…)
require_once $dir . '/catalogos/gestion.php';

// Sistemas de progresión / fama
require_once $dir . '/sistemas/haki.php';
require_once $dir . '/sistemas/frutas.php';
require_once $dir . '/sistemas/enlace.php';
require_once $dir . '/sistemas/renombre.php';
require_once $dir . '/sistemas/pl.php';
require_once $dir . '/sistemas/rachas.php';

// Progresión y calendario on-roll (F3.0, 5.6/7.3/7.7): avance perezoso ×2,
// finalización de entrenamientos, colocación de reserva
require_once $dir . '/sistemas/progresion.php';

// Panel staff «Progresión» (F4.2, Anexo A.3): cronómetros, subidas, gastos
require_once $dir . '/sistemas/progresion_panel.php';

// Inventario, equipo y cartera (F3.2/F3.3, 5.8/5.9): ranuras, cupos Meitou, saldos
require_once $dir . '/sistemas/inventario.php';

// Tiendas y mercado (F3.3, 5.9): tiendas de jugador, boletín, compra/venta
require_once $dir . '/sistemas/tiendas.php';

// Mundo Vivo (F4.1, 5.14/5.15): mares, islas, zonas, ronda mensual, panel staff
require_once $dir . '/sistemas/mundo.php';

// Navegación y travesías (F4.3, 5.16/17): trámite 38 — IRT, oráculos, tiempo,
// víveres, límite de mar por barco+madera, cierre y vencimiento
require_once $dir . '/sistemas/navegacion.php';

// Facciones (F4.3, 5.12/13): trámites 20–24 — ascenso con cupos/termómetro,
// élite, cambio, deserción, infiltración + panel staff
require_once $dir . '/sistemas/facciones.php';

// Conquista y control territorial (F4.3, 5.15/cap.16): trámites 34–37 — anuncio,
// asedio, resolución con registro y suspensión de tiendas, reconquista;
// unidades/hordas (16.7), abandono (16.5) + panel staff
require_once $dir . '/sistemas/conquista.php';

// Barcos y astillero (F4.3, 5.17/cap.18): trámites 39–44 — compra/construcción/
// mejora N1–N3/módulos/reparación/venta, ficha 18.2, madera por clase (18.5),
// espacio por raza (18.3) + panel staff
require_once $dir . '/sistemas/barcos.php';

// Combate 7 Seas (F2, 5.10): motor puro — PA, daño, deltas, tablas, estados, sala
require_once $dir . '/sistemas/combate.php';

// Zona B (F2.2): panel del editor, parser del bloque y persistencia de turnos
require_once $dir . '/sistemas/combate_ui.php';

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

// Motor 7 Seas (5.21): catálogo de 67, motor transversal, bandeja y permisos
require_once $dir . '/tramites/catalogo.php';
require_once $dir . '/tramites/motor.php';
require_once $dir . '/tramites/bandeja.php';
require_once $dir . '/tramites/paginas.php';
require_once $dir . '/core/permisos.php';

// Bot «OPE Eternal» (News Coo, sucesos, rumores, avisos)
require_once $dir . '/core/bot.php';
