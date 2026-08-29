<?php
/**
 * One Piece: Eternal · Mapa del Mundo (mapa.php)
 * -------------------------------------------------------------------------
 * Carta náutica mundial en SVG: la Red Line parte el mundo en dos, la Grand
 * Line lo cruza (Paradise al este, New World al oeste) y los cuatro Blues
 * ocupan las esquinas. Cada isla es un punto clicable que abre su ficha
 * (región, macro-mar, tier, peligro y descripción).
 *
 * Autocontenido: el catálogo de islas del mapa vive en este archivo (sin BD ni
 * módulos externos), junto con posiciones y descripciones.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'mapa.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

// Catálogo estático de islas para la carta náutica (prototipo): fuente única
// del mapa, sin BD ni módulos externos. Cada isla: slug, nombre, region,
// macro-mar, tier (1-5) y peligro_base (1-10).
$islas_cat = array(
    // East Blue
    'isla_dawn'      => array('slug' => 'isla_dawn',      'nombre' => 'Isla Dawn',       'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
    'shells_town'    => array('slug' => 'shells_town',    'nombre' => 'Shells Town',     'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
    'orange_town'    => array('slug' => 'orange_town',    'nombre' => 'Orange Town',     'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
    'syrup_village'  => array('slug' => 'syrup_village',  'nombre' => 'Syrup Village',   'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
    'islas_conomi'   => array('slug' => 'islas_conomi',   'nombre' => 'Islas Conomi',    'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
    'baratie'        => array('slug' => 'baratie',        'nombre' => 'Baratie',         'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
    'loguetown'      => array('slug' => 'loguetown',      'nombre' => 'Loguetown',       'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
    'isla_gecko'     => array('slug' => 'isla_gecko',     'nombre' => 'Isla Gecko',      'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
    // West Blue
    'reino_sorbet'   => array('slug' => 'reino_sorbet',   'nombre' => 'Reino de Sorbet', 'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 1),
    'isla_kalimero'  => array('slug' => 'isla_kalimero',  'nombre' => 'Isla Kalimero',   'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 1),
    'puerto_neblina' => array('slug' => 'puerto_neblina', 'nombre' => 'Puerto Neblina',  'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    'isla_verthom'   => array('slug' => 'isla_verthom',   'nombre' => 'Isla Verthom',    'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    'bahia_rosclave' => array('slug' => 'bahia_rosclave', 'nombre' => 'Bahia Rosclave',  'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    'isla_fenrik'    => array('slug' => 'isla_fenrik',    'nombre' => 'Isla Fenrik',     'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    'costa_malmuerta'=> array('slug' => 'costa_malmuerta','nombre' => 'Costa Malmuerta', 'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    'isla_corvenna'  => array('slug' => 'isla_corvenna',  'nombre' => 'Isla Corvenna',   'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
    // North Blue
    'reino_germa'    => array('slug' => 'reino_germa',    'nombre' => 'Reino de Germa',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 3),
    'isla_thornveil' => array('slug' => 'isla_thornveil', 'nombre' => 'Isla Thornveil',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
    'puerto_grisalba'=> array('slug' => 'puerto_grisalba','nombre' => 'Puerto Grisalba','region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 1),
    'isla_kelthorn'  => array('slug' => 'isla_kelthorn',  'nombre' => 'Isla Kelthorn',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
    'bahia_escarcha' => array('slug' => 'bahia_escarcha', 'nombre' => 'Bahia Escarcha',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
    'isla_draconis'  => array('slug' => 'isla_draconis',  'nombre' => 'Isla Draconis',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 3),
    'puerto_nevado'  => array('slug' => 'puerto_nevado',  'nombre' => 'Puerto Nevado',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 1),
    'isla_varnholt'  => array('slug' => 'isla_varnholt',  'nombre' => 'Isla Varnholt',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
    // South Blue
    'isla_momoiro'   => array('slug' => 'isla_momoiro',   'nombre' => 'Isla Momoiro',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
    'isla_baterilla' => array('slug' => 'isla_baterilla', 'nombre' => 'Isla Baterilla',  'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
    'isla_sorenna'   => array('slug' => 'isla_sorenna',   'nombre' => 'Isla Sorenna',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 1),
    'puerto_ceniza'  => array('slug' => 'puerto_ceniza',  'nombre' => 'Puerto Ceniza',   'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
    'isla_corvalle'  => array('slug' => 'isla_corvalle',  'nombre' => 'Isla Corvalle',   'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
    'bahia_marlot'   => array('slug' => 'bahia_marlot',   'nombre' => 'Bahia Marlot',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
    'isla_tessaly'   => array('slug' => 'isla_tessaly',   'nombre' => 'Isla Tessaly',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 1),
    'puerto_serpiente'=>array('slug' => 'puerto_serpiente','nombre'=> 'Puerto Serpiente','region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 3),
    // Calm Belt
    'amazon_lily'    => array('slug' => 'amazon_lily',    'nombre' => 'Amazon Lily',     'region' => 'Calm Belt',  'macro' => 'calm_belt',  'tier' => 3, 'peligro_base' => 7),
    'isla_gyojin'    => array('slug' => 'isla_gyojin',    'nombre' => 'Isla Gyojin',     'region' => 'Calm Belt',  'macro' => 'calm_belt',  'tier' => 3, 'peligro_base' => 8),
    // Red Line
    'marineford'     => array('slug' => 'marineford',     'nombre' => 'Marineford',      'region' => 'Red Line',   'macro' => 'red_line',   'tier' => 4, 'peligro_base' => 9),
    'mary_geoise'    => array('slug' => 'mary_geoise',    'nombre' => 'Mary Geoise',     'region' => 'Red Line',   'macro' => 'red_line',   'tier' => 4, 'peligro_base' => 9),
    // Paradise (Grand Line primera mitad)
    'whiskey_peak'   => array('slug' => 'whiskey_peak',   'nombre' => 'Whiskey Peak',    'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 2, 'peligro_base' => 3),
    'little_garden'  => array('slug' => 'little_garden',  'nombre' => 'Little Garden',   'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 2, 'peligro_base' => 4),
    'isla_drum'      => array('slug' => 'isla_drum',      'nombre' => 'Isla Drum',       'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 2, 'peligro_base' => 3),
    'alabasta'       => array('slug' => 'alabasta',       'nombre' => 'Alabasta',        'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 2, 'peligro_base' => 4),
    'jaya'           => array('slug' => 'jaya',           'nombre' => 'Jaya',            'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 2, 'peligro_base' => 4),
    'skypiea'        => array('slug' => 'skypiea',        'nombre' => 'Skypiea',         'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 3, 'peligro_base' => 5),
    'long_ring'      => array('slug' => 'long_ring',      'nombre' => 'Long Ring Long Land', 'region' => 'Paradise', 'macro' => 'paradise', 'tier' => 2, 'peligro_base' => 3),
    'water_seven'    => array('slug' => 'water_seven',    'nombre' => 'Water Seven',     'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 3, 'peligro_base' => 4),
    'enies_lobby'    => array('slug' => 'enies_lobby',    'nombre' => 'Enies Lobby',     'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 3, 'peligro_base' => 6),
    'thriller_bark'  => array('slug' => 'thriller_bark',  'nombre' => 'Thriller Bark',   'region' => 'Paradise',   'macro' => 'paradise',   'tier' => 3, 'peligro_base' => 5),
    'sabaody'        => array('slug' => 'sabaody',        'nombre' => 'Archipielago Sabaody', 'region' => 'Paradise', 'macro' => 'paradise', 'tier' => 3, 'peligro_base' => 5),
    // New World (Grand Line segunda mitad)
    'punk_hazard'    => array('slug' => 'punk_hazard',    'nombre' => 'Punk Hazard',     'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
    'dressrosa'      => array('slug' => 'dressrosa',      'nombre' => 'Dressrosa',       'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 6),
    'zou'            => array('slug' => 'zou',            'nombre' => 'Zou',             'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
    'wano'           => array('slug' => 'wano',           'nombre' => 'Wano Country',    'region' => 'New World',  'macro' => 'new_world',  'tier' => 5, 'peligro_base' => 8),
    'elbaf'          => array('slug' => 'elbaf',          'nombre' => 'Elbaf',           'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
    'whole_cake'     => array('slug' => 'whole_cake',     'nombre' => 'Whole Cake Island','region'=> 'New World',  'macro' => 'new_world',  'tier' => 5, 'peligro_base' => 8),
);
$macros    = array('east_blue' => 'East Blue', 'west_blue' => 'West Blue', 'north_blue' => 'North Blue',
    'south_blue' => 'South Blue', 'calm_belt' => 'Calm Belt', 'red_line' => 'Red Line',
    'paradise' => 'Paradise', 'new_world' => 'New World');

// ── Posiciones (x, y) en el viewBox 1200×760 ─────────────────────────────
$mapa_pos = array(
    // East Blue (noreste)
    'isla_dawn'       => array(775, 130),
    'shells_town'     => array(860, 100),
    'orange_town'     => array(940, 138),
    'syrup_village'   => array(1025, 105),
    'islas_conomi'    => array(1100, 152),
    'baratie'         => array(890, 208),
    'loguetown'       => array(718, 282),
    'isla_gecko'      => array(1000, 235),
    // West Blue (suroeste)
    'reino_sorbet'    => array(128, 520),
    'isla_kalimero'   => array(208, 578),
    'puerto_neblina'  => array(288, 505),
    'isla_verthom'    => array(158, 648),
    'bahia_rosclave'  => array(268, 668),
    'isla_fenrik'     => array(378, 558),
    'costa_malmuerta' => array(432, 628),
    'isla_corvenna'   => array(468, 505),
    // North Blue (noroeste)
    'reino_germa'     => array(140, 122),
    'isla_thornveil'  => array(238, 96),
    'puerto_grisalba' => array(318, 152),
    'isla_kelthorn'   => array(208, 208),
    'bahia_escarcha'  => array(298, 252),
    'isla_draconis'   => array(398, 112),
    'puerto_nevado'   => array(458, 188),
    'isla_varnholt'   => array(368, 272),
    // South Blue (sureste)
    'isla_momoiro'    => array(770, 505),
    'isla_baterilla'  => array(850, 558),
    'isla_sorenna'    => array(920, 488),
    'puerto_ceniza'   => array(990, 538),
    'isla_corvalle'   => array(890, 638),
    'bahia_marlot'    => array(1015, 628),
    'isla_tessaly'    => array(1082, 558),
    'puerto_serpiente'=> array(745, 648),
    // Calm Belt
    'amazon_lily'     => array(1000, 336),
    'isla_gyojin'     => array(615, 500),
    // Red Line
    'mary_geoise'     => array(600, 380),
    'marineford'      => array(658, 366),
    // Paradise (Grand Line, primera mitad)
    'whiskey_peak'    => array(700, 388),
    'little_garden'   => array(772, 374),
    'isla_drum'       => array(838, 396),
    'alabasta'        => array(902, 370),
    'jaya'            => array(958, 386),
    'skypiea'         => array(958, 296),
    'long_ring'       => array(1020, 378),
    'water_seven'     => array(1060, 392),
    'enies_lobby'     => array(1090, 362),
    'thriller_bark'   => array(1108, 386),
    'sabaody'         => array(1160, 372),
    // New World (Grand Line, segunda mitad)
    'punk_hazard'     => array(508, 386),
    'dressrosa'       => array(448, 366),
    'zou'             => array(372, 390),
    'whole_cake'      => array(280, 372),
    'wano'            => array(172, 388),
    'elbaf'           => array(62, 372),
);

// Radio del punto por tier (islas de tier alto = más prominentes)
$mapa_radio = array(1 => 3.6, 2 => 4.3, 3 => 5.1, 4 => 6.0, 5 => 6.8);

// ── Descripciones (prueba; lore canónico pendiente) ──────────────────────
$mapa_desc = array(
    // East Blue
    'isla_dawn'       => 'Cuna de la leyenda: el pueblo donde un niño juró ante un pirata de paja convertirse en el Rey de los Piratas.',
    'shells_town'     => 'Puerto del East Blue vigilado por un capitán cruel de la Marina; su campana aún recuerda la sentencia.',
    'orange_town'     => 'Pueblo de naranjos asolado por un pirata payaso; sus habitantes aprendieron a reírse del miedo.',
    'syrup_village'   => 'Villa apacible sobre una cala en forma de jarabe; bajo su tranquilidad se esconde una ambición de hierro.',
    'islas_conomi'    => 'Archipiélago pesquero que vivió años de terror bajo un pez gigante; su gente hoy mira el mar sin temor.',
    'baratie'         => 'Restaurante flotante donde los chefs defienden cada plato como un tesoro y el mar paga la cuenta.',
    'loguetown'       => 'La ciudad del inicio y del final: el patíbulo donde la Gran Era comenzó con una sonrisa.',
    'isla_gecko'      => 'Isla menor del East Blue; refugio de contrabandistas entre las rutas comerciales del mar este.',
    // West Blue
    'reino_sorbet'    => 'Reino agrícola del West Blue; sus campos de cítricos doran el horizonte al atardecer.',
    'isla_kalimero'   => 'Isla de calas ocultas donde el viento esculpe acantilados de tiza blanca.',
    'puerto_neblina'  => 'Puerto envuelto en niebla perpetua; ningún capitán entra sin un práctico local.',
    'isla_verthom'    => 'Colinas esmeralda y puertos de madera; los rumores hablan de tesoros hundidos en su bahía.',
    'bahia_rosclave'  => 'Bahía de aguas rosadas al atardecer; mercado de especias, naufragios y secretos bien guardados.',
    'isla_fenrik'     => 'Isla volcánica del oeste; sus aguas termales curan a los marineros que llegan entumecidos.',
    'costa_malmuerta' => 'Costa inhóspita de pecios encallados; los naufragios son su única cosecha y su única historia.',
    'isla_corvenna'   => 'Isla de acantilados negros coronados de cuervos; los supersticiosos la evitan al caer la noche.',
    // North Blue
    'reino_germa'     => 'Reino del North Blue; la ciencia marcial de sus príncipes resuena en cada puerto del norte.',
    'isla_thornveil'  => 'Isla de pinos grises y vientos cortantes; famosa por su sal y por su hospitalidad escasa.',
    'puerto_grisalba' => 'Puerto de piedra gris donde el hielo retrocede solo para abrir un mercado bullicioso.',
    'isla_kelthorn'   => 'Isla de marismas y ciervos; sus lagunas reflejan auroras boreales la mitad del año.',
    'bahia_escarcha'  => 'Bahía congelada durante meses; los trineos de vela son su único medio de vida.',
    'isla_draconis'   => 'Isla de picos escarpados; una vieja leyenda habla del último dragón del mar del norte.',
    'puerto_nevado'   => 'Puerto blanco de tejados inclinados; su faro es el más alto de todo el North Blue.',
    'isla_varnholt'   => 'Isla montañosa de minas de hierro; los herreros de Varnholt firman las hojas más afiladas.',
    // South Blue
    'isla_momoiro'    => 'Isla del sur bañada en luz de melocotón al amanecer; sus huertos florecen todo el año.',
    'isla_baterilla'  => 'Isla tranquila que un día vio arder un fuego que el mundo apagó demasiado pronto.',
    'isla_sorenna'    => 'Isla de arrecifes de cristal; los buzos vuelven con historias de sirenas entre las manos.',
    'puerto_ceniza'   => 'Puerto volcánico de arenas oscuras; sus astilleros reparan cualquier casco del mar sur.',
    'isla_corvalle'   => 'Valle costero de vinos y aceitunas; los banquetes en Corvalle duran hasta el alba.',
    'bahia_marlot'    => 'Bahía de mareas suaves; santuario de manatíes y de pescadores que saben esperar.',
    'isla_tessaly'    => 'Isla de viñedos en terrazas; su cosecha viaja a los cuatro mares en toneles de roble.',
    'puerto_serpiente'=> 'Puerto tortuoso de canales estrechos; dicen que un rey serpiente duerme bajo sus muelles.',
    // Calm Belt
    'amazon_lily'     => 'Reino del Calm Belt gobernado por las guerreras kuja; ni las bestias del mar osan acercarse.',
    'isla_gyojin'     => 'Reino submarino de los gyojin, anclado bajo la Red Line entre dos mundos.',
    // Red Line
    'mary_geoise'     => 'La Tierra Santa: la cima de la Red Line desde donde el Gobierno Mundial observa el mundo.',
    'marineford'      => 'Fortaleza de la Marina frente a la Tierra Santa; escenario de la guerra que estremeció la Gran Era.',
    // Paradise
    'whiskey_peak'    => 'Ciudad de bienvenida en Paradise... demasiado hospitalaria como para ser cierta.',
    'little_garden'   => 'Isla prehistórica donde gigantes libran una batalla que lleva décadas sin decidirse.',
    'isla_drum'       => 'Reino de invierno eterno; en su cima vivió el doctor más grande que el mundo haya conocido.',
    'alabasta'        => 'Reino del desierto marcado por una sequía que nadie quería explicar.',
    'jaya'            => 'Isla de Paradise mitad gloria, mitad leyenda: bajo sus aguas duerme una ciudad dorada.',
    'skypiea'         => 'Isla del cielo sobre Jaya; en el mar de nubes resuena una campana de oro.',
    'long_ring'       => 'Cadena de islas planas donde los juegos deciden el destino de los viajeros.',
    'water_seven'     => 'La ciudad del agua: canales, acueductos y el mejor astillero del mundo.',
    'enies_lobby'     => 'La isla judicial: una fortaleza a la deriva donde el Gobierno juzga sin piedad.',
    'thriller_bark'   => 'Isla fantasma que navega sola; su bosque solo florece de noche.',
    'sabaody'         => 'Archipiélago de manglares gigantes al pie de la Red Line; la puerta a lo desconocido.',
    // New World
    'punk_hazard'     => 'Isla partida en fuego y hielo por un experimento que salió mal.',
    'dressrosa'       => 'Reino de la felicidad y las flores; bajo su brillo laten mil recuerdos de juguete.',
    'zou'             => 'La isla elefante: una bestia milenaria que camina el Nuevo Mundo con una civilización a la espalda.',
    'whole_cake'      => 'El reino del dulce de una Emperatriz; cada rincón está hecho de comida y de sueños.',
    'wano'            => 'País del samurái, cerrado al mundo desde hace décadas; su kimono no se rinde ante nadie.',
    'elbaf'           => 'La isla de los guerreros gigantes; en su salón de banquetes se cantan las sagas más antiguas.',
    // Hito (no es isla del catálogo)
    'reverse_mountain'=> 'La montaña invertida: el río que asciende hasta las nubes y la puerta de entrada a la Grand Line.',
);

// ── Construcción de datos para el mapa y el JS ───────────────────────────
$regiones_orden = array('East Blue', 'West Blue', 'North Blue', 'South Blue', 'Calm Belt', 'Red Line', 'Paradise', 'New World');
$regiones_set   = array_flip($regiones_orden);

$map_data = array();
foreach ($islas_cat as $slug => $isla) {
    $pos = isset($mapa_pos[$slug]) ? $mapa_pos[$slug] : array(0, 0);
    $map_data[] = array(
        'slug'    => $slug,
        'nombre'  => $isla['nombre'],
        'region'  => $isla['region'],
        'macro'   => isset($macros[$isla['macro']]) ? $macros[$isla['macro']] : $isla['macro'],
        'tier'    => (int) $isla['tier'],
        'peligro' => (int) $isla['peligro_base'],
        'desc'    => isset($mapa_desc[$slug]) ? $mapa_desc[$slug] : 'Isla de ' . $isla['region'] . '.',
        'x'       => (int) $pos[0],
        'y'       => (int) $pos[1],
    );
}
// Hito especial: Reverse Mountain (puerta de entrada a la Grand Line)
$map_data[] = array(
    'slug'    => 'reverse_mountain',
    'nombre'  => 'Reverse Mountain',
    'region'  => 'Red Line',
    'macro'   => 'Red Line',
    'tier'    => 0,
    'peligro' => 0,
    'desc'    => $mapa_desc['reverse_mountain'],
    'x'       => 600,
    'y'       => 90,
);
$total_islas = count($map_data);

$esc = function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Mapa del Mundo</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-mapa">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Mapa del Mundo</b>
</div></div>
<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Mapa del Mundo</h1>
      <span class="code">// carta náutica · prototipo</span>
      <span class="rule"></span>
    </div>
    <p class="mapa-intro">La Red Line parte el mundo en dos, la Grand Line lo cruza —<b>Paradise</b> a un lado, el <b>New World</b> al otro— y los cuatro Blues ocupan las esquinas. Toca una isla para ver su ficha.</p>
  </section>

  <section class="reveal">
    <div class="map-wrap">

      <div class="map-col">
        <div class="map-frame">
          <svg id="world-map" viewBox="0 0 1200 760" role="group" aria-label="Mapa mundial de One Piece: Eternal">
            <defs>
              <linearGradient id="g-ocean" x1="0" y1="0" x2="0" y2="1">
                <stop class="grad-ocean-1" offset="0"/>
                <stop class="grad-ocean-2" offset="1"/>
              </linearGradient>
              <linearGradient id="g-redline" x1="0" y1="0" x2="1" y2="0">
                <stop class="grad-red-1" offset="0"/>
                <stop class="grad-red-2" offset=".5"/>
                <stop class="grad-red-1" offset="1"/>
              </linearGradient>
              <pattern id="p-dots" width="26" height="26" patternUnits="userSpaceOnUse">
                <circle class="p-dot" cx="2" cy="2" r="1"/>
              </pattern>
              <pattern id="p-hatch" width="12" height="12" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                <line class="p-hatch-line" x1="0" y1="0" x2="0" y2="12"/>
              </pattern>
              <filter id="f-glow" x="-60%" y="-60%" width="220%" height="220%">
                <feGaussianBlur stdDeviation="3.2" result="b"/>
                <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
              </filter>
            </defs>

            <!-- Océano -->
            <rect class="map-ocean" x="0" y="0" width="1200" height="760" fill="url(#g-ocean)"/>
            <rect class="map-ocean-dots" x="0" y="0" width="1200" height="760" fill="url(#p-dots)"/>

            <!-- Graticula (meridianos/paralelos de carta) -->
            <g class="graticule">
              <path d="M0,120 Q600,190 1200,120"/>
              <path d="M0,260 Q600,330 1200,260"/>
              <path d="M0,500 Q600,430 1200,500"/>
              <path d="M0,640 Q600,570 1200,640"/>
              <path d="M120,0 Q190,380 120,760"/>
              <path d="M300,0 Q370,380 300,760"/>
              <path d="M900,0 Q830,380 900,760"/>
              <path d="M1080,0 Q1010,380 1080,760"/>
            </g>

            <!-- Calm Belt -->
            <rect class="map-calma" x="0" y="322" width="1200" height="28"/>
            <rect class="map-calma-hatch" x="0" y="322" width="1200" height="28" fill="url(#p-hatch)"/>
            <rect class="map-calma" x="0" y="410" width="1200" height="28"/>
            <rect class="map-calma-hatch" x="0" y="410" width="1200" height="28" fill="url(#p-hatch)"/>

            <!-- Grand Line (banda) -->
            <rect class="map-gline" x="0" y="350" width="1200" height="60"/>

            <!-- Rutas de navegación (dash) -->
            <path class="map-route" d="M668,382 C700,370 728,394 758,380 C790,366 822,398 858,382 C892,366 924,398 960,382 C992,368 1024,398 1056,384 C1086,370 1116,396 1144,382 C1158,374 1170,378 1178,376"/>
            <path class="map-route" d="M52,376 C80,390 110,366 140,384 C172,400 204,366 236,384 C268,400 300,366 332,384 C364,400 396,366 428,384 C460,400 492,370 524,386 C536,390 544,388 552,386"/>

            <!-- Red Line (banda rocosa vertical) -->
            <g class="map-redline-wrap">
              <path class="map-redline" d="M555,0 L645,0 L645,76 L553,76 L553,132 L647,132 L647,198 L551,198 L551,262 L645,262 L645,330 L553,330 L553,396 L647,396 L647,470 L551,470 L551,536 L645,536 L645,610 L553,610 L553,676 L647,676 L647,760 L555,760 Z"/>
              <path class="map-redline-ridge" d="M568,0 L568,760 M582,0 L582,760 M596,0 L596,760 M610,0 L610,760 M624,0 L624,760 M636,0 L636,760"/>
            </g>

            <!-- Etiquetas de regiones y mares -->
            <g class="map-tags">
              <text class="tag-region" x="110"  y="52" text-anchor="middle">◆ North Blue</text>
              <text class="tag-region" x="990"  y="52" text-anchor="middle">East Blue ◆</text>
              <text class="tag-region" x="110"  y="468" text-anchor="middle">◆ West Blue</text>
              <text class="tag-region" x="990"  y="468" text-anchor="middle">South Blue ◆</text>
              <text class="tag-sea"   x="135"  y="339" text-anchor="middle">CALM BELT</text>
              <text class="tag-sea"   x="1060" y="339" text-anchor="middle">CALM BELT</text>
              <text class="tag-sea"   x="135"  y="427" text-anchor="middle">CALM BELT</text>
              <text class="tag-sea"   x="1060" y="427" text-anchor="middle">CALM BELT</text>
              <text class="tag-sea"   x="910"  y="343" text-anchor="middle">PARADISE — primera mitad</text>
              <text class="tag-sea"   x="250"  y="343" text-anchor="middle">NEW WORLD — segunda mitad</text>
              <text class="tag-redline" transform="rotate(-90 600 560)" x="600" y="560" text-anchor="middle">RED LINE</text>
            </g>

            <!-- Reverse Mountain (hito de entrada) -->
            <g class="feat" data-slug="reverse_mountain" data-region="Red Line" role="button" tabindex="0" aria-label="Reverse Mountain">
              <title>Reverse Mountain — puerta de entrada a la Grand Line</title>
              <path class="feat-mtn" d="M600,44 L566,90 L580,90 L580,104 L600,104 L620,104 L620,90 L634,90 Z"/>
              <path class="feat-river" d="M600,100 C598,86 604,74 600,60"/>
              <circle class="feat-ring" cx="600" cy="90" r="17"/>
              <text class="isle-lbl" x="612" y="84">Reverse Mountain</text>
            </g>

            <!-- Islas -->
            <?php foreach ($map_data as $m): if ($m['slug'] === 'reverse_mountain') { continue; } ?>
            <?php
                $t  = $m['tier'];
                $r  = isset($mapa_radio[$t]) ? $mapa_radio[$t] : 4.5;
                $x  = $m['x'];
                $y  = $m['y'];
                $lblLeft = ($x > 1060 || in_array($m['slug'], array('mary_geoise', 'punk_hazard'), true));
                $lblX  = $lblLeft ? -10 : 10;
                $anchor = $lblLeft ? 'end' : 'start';
                $extra = ($m['slug'] === 'skypiea') ? ' is-sky' : '';
                $sp    = in_array($m['slug'], array('mary_geoise', 'marineford'), true) ? ' is-sp' : '';
            ?>
            <g class="isle isle-t<?php echo (int) $t . $extra . $sp; ?>" data-slug="<?php echo $esc($m['slug']); ?>" data-region="<?php echo $esc($m['region']); ?>" role="button" tabindex="0" aria-label="<?php echo $esc($m['nombre']); ?>" transform="translate(<?php echo (int) $x; ?> <?php echo (int) $y; ?>)">
              <title><?php echo $esc($m['nombre']); ?></title>
              <?php if ($m['slug'] === 'skypiea'): ?>
                <ellipse class="isle-cloud" cx="0" cy="0" rx="20" ry="9"/>
                <path class="isle-cloud-line" d="M-26,14 C-20,20 20,20 26,14"/>
              <?php endif; ?>
              <circle class="isle-hit" r="16"/>
              <circle class="isle-halo" r="<?php echo $r + 4; ?>"/>
              <circle class="isle-dot" r="<?php echo $r; ?>"/>
              <text class="isle-lbl" x="<?php echo (int) $lblX; ?>" y="3.5" text-anchor="<?php echo $anchor; ?>"><?php echo $esc($m['nombre']); ?></text>
            </g>
            <?php endforeach; ?>

            <!-- Rosa de los vientos -->
            <g class="compass" transform="translate(92 690)">
              <circle class="compass-ring" r="30"/>
              <circle class="compass-ring2" r="24"/>
              <path class="compass-n" d="M0,-26 L5,0 L-5,0 Z"/>
              <path class="compass-s" d="M0,26 L5,0 L-5,0 Z"/>
              <path class="compass-e" d="M26,0 L0,5 L0,-5 Z"/>
              <path class="compass-w" d="M-26,0 L0,5 L0,-5 Z"/>
              <text class="compass-n-lbl" x="0" y="-34" text-anchor="middle">N</text>
              <text class="compass-caption" x="42" y="4">OCÉANO MUNDIAL</text>
              <text class="compass-caption2" x="42" y="16">CARTA N.º 1 · MAR DEL MUNDO</text>
            </g>
          </svg>
        </div>

        <!-- Leyenda -->
        <div class="map-legend">
          <div class="lg-block">
            <span class="lg-title">Tier de poder</span>
            <div class="lg-tier">
              <?php for ($t = 1; $t <= 5; $t++): ?>
              <span class="lg-tier-item"><i class="lg-dot t<?php echo $t; ?>"></i>T<?php echo $t; ?></span>
              <?php endfor; ?>
            </div>
          </div>
          <div class="lg-block">
            <span class="lg-title">Filtros por mar</span>
            <div class="lg-chips">
              <button type="button" class="map-chip on" data-region="__all__">Todas</button>
              <?php foreach ($regiones_orden as $reg): ?>
              <button type="button" class="map-chip" data-region="<?php echo $esc($reg); ?>"><?php echo $esc($reg); ?></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <aside class="map-side">
        <div class="plate map-info">
          <div class="plate-h"><span class="t">Ficha de isla</span><span class="c">// selecciona un punto</span></div>
          <div class="plate-b">
            <div class="mi-empty" id="map-info-empty">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="12 6 13.8 10.2 18 12 13.8 13.8 12 18 10.2 13.8 6 12 10.2 10.2"/></svg>
              <div class="mi-empty-t">Toca una isla en la carta</div>
              <p>Se mostrará aquí su región, tier, peligro y descripción.</p>
            </div>
            <div class="mi-body" id="map-info-body">
              <div class="mi-name" id="mi-name"></div>
              <div class="mi-chips" id="mi-chips"></div>
              <div class="mi-danger">
                <span class="mi-danger-lbl">Peligro <b id="mi-danger-num"></b></span>
                <div class="mi-danger-bar" id="mi-danger-bar"></div>
              </div>
              <p class="mi-desc" id="mi-desc"></p>
            </div>
          </div>
        </div>

        <div class="plate map-index">
          <div class="plate-h"><span class="t">Índice de islas</span><span class="c">// <?php echo (int) $total_islas; ?> puntos</span></div>
          <div class="plate-b">
            <ul class="mi-list" id="mi-list"></ul>
          </div>
        </div>
      </aside>

    </div>
  </section>

</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
<script>
window.OPE_ISLAS = <?php echo json_encode($map_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
(function () {
  var ISLAS = window.OPE_ISLAS || [];
  var bySlug = {};
  ISLAS.forEach(function (i) { bySlug[i.slug] = i; });

  var infoEmpty = document.getElementById('map-info-empty');
  var infoBody  = document.getElementById('map-info-body');
  var nameEl    = document.getElementById('mi-name');
  var chipsEl   = document.getElementById('mi-chips');
  var dangerBar = document.getElementById('mi-danger-bar');
  var dangerNum = document.getElementById('mi-danger-num');
  var descEl    = document.getElementById('mi-desc');
  var listEl    = document.getElementById('mi-list');

  function dangerCells(n) {
    var h = '';
    for (var k = 1; k <= 10; k++) {
      h += '<i class="' + (k <= n ? 'on' : '') + (n >= 8 ? ' hot' : '') + '"></i>';
    }
    return h;
  }

  function selectIsla(slug) {
    var i = bySlug[slug];
    if (!i) { return; }
    document.querySelectorAll('.isle, .feat').forEach(function (g) {
      g.classList.toggle('sel', g.getAttribute('data-slug') === slug);
    });
    document.querySelectorAll('.mi-list li').forEach(function (li) {
      li.classList.toggle('on', li.getAttribute('data-slug') === slug);
    });
    infoEmpty.classList.add('hide');
    infoBody.classList.add('show');
    nameEl.textContent = i.nombre;
    var chips = '<span class="tag">' + i.region + '</span>';
    if (i.macro && i.macro !== i.region) { chips += '<span class="tag line">Mar: ' + i.macro + '</span>'; }
    chips += (i.tier > 0 ? '<span class="tag rank">Tier ' + i.tier + '</span>' : '<span class="tag act">Hito</span>');
    chipsEl.innerHTML = chips;
    dangerBar.innerHTML = dangerCells(i.peligro);
    dangerNum.textContent = i.peligro > 0 ? (i.peligro + ' / 10') : '—';
    descEl.textContent = i.desc;
  }

  function setFilter(region) {
    var all = (region === '__all__');
    document.querySelectorAll('.isle, .feat').forEach(function (g) {
      g.classList.toggle('dim', !all && g.getAttribute('data-region') !== region);
    });
    document.querySelectorAll('.mi-list li').forEach(function (li) {
      li.classList.toggle('hide', !all && li.getAttribute('data-region') !== region);
    });
    document.querySelectorAll('.map-chip').forEach(function (c) {
      c.classList.toggle('on', c.getAttribute('data-region') === region);
    });
  }

  // Clic / teclado sobre puntos del mapa
  var svg = document.getElementById('world-map');
  svg.addEventListener('click', function (e) {
    var g = e.target.closest ? e.target.closest('.isle, .feat') : null;
    if (g) { selectIsla(g.getAttribute('data-slug')); }
  });
  svg.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      var t = e.target;
      if (t && (t.classList.contains('isle') || t.classList.contains('feat'))) {
        e.preventDefault();
        selectIsla(t.getAttribute('data-slug'));
      }
    }
  });

  // Chips de filtro
  document.querySelectorAll('.map-chip').forEach(function (c) {
    c.addEventListener('click', function () { setFilter(c.getAttribute('data-region')); });
  });

  // Índice de islas
  ISLAS.forEach(function (i) {
    var li = document.createElement('li');
    li.className = 'mi-li';
    li.setAttribute('data-slug', i.slug);
    li.setAttribute('data-region', i.region);
    li.innerHTML = '<span class="mi-li-dot t' + i.tier + '"></span>'
                 + '<span class="mi-li-name"></span>'
                 + '<span class="mi-li-meta"></span>';
    li.querySelector('.mi-li-name').textContent = i.nombre;
    li.querySelector('.mi-li-meta').textContent = i.region + ' · Tier ' + (i.tier || '—');
    li.addEventListener('click', function () { selectIsla(i.slug); });
    listEl.appendChild(li);
  });
})();
</script>
</body>
</html>
