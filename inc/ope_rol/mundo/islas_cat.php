<?php
/**
 * One Piece: Eternal · Catálogo de Islas (44 islas)
 * --------------------------------------------------
 * Fuente única de verdad para todas las islas navegables del mundo.
 * Cada isla tiene: slug, nombre, region, macro (macro-mar), tier, peligro_base.
 *
 * Uso:
 *   $islas   = ope_islas_catalogo();          // array indexado por slug
 *   $isla    = ope_isla_por_slug('loguetown');
 *   $region  = ope_islas_por_region();        // agrupadas por region
 *   $macros  = ope_islas_macro_list();        // lista de macro-mares
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Catálogo completo de las 44 islas.
 * Devuelve array asociativo slug => datos.
 *
 * Campos por isla:
 *   slug         string  Identificador unico (clave del array)
 *   nombre       string  Nombre visible
 *   region       string  Region geografica (East Blue, West Blue, ...)
 *   macro        string  Macro-mar para calculo de rutas
 *   tier         int     Tramo de poder (1-5)
 *   peligro_base int     Peligro intrinseco de la isla/zona (1-10)
 */
function ope_islas_catalogo()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = array(

        // ── EAST BLUE ──────────────────────────────────────────
        'isla_dawn'      => array('slug' => 'isla_dawn',      'nombre' => 'Isla Dawn',       'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
        'shells_town'    => array('slug' => 'shells_town',    'nombre' => 'Shells Town',     'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
        'orange_town'    => array('slug' => 'orange_town',    'nombre' => 'Orange Town',     'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
        'syrup_village'  => array('slug' => 'syrup_village',  'nombre' => 'Syrup Village',   'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 1),
        'islas_conomi'   => array('slug' => 'islas_conomi',   'nombre' => 'Islas Conomi',    'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
        'baratie'        => array('slug' => 'baratie',        'nombre' => 'Baratie',         'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
        'loguetown'      => array('slug' => 'loguetown',      'nombre' => 'Loguetown',       'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),
        'isla_gecko'     => array('slug' => 'isla_gecko',     'nombre' => 'Isla Gecko',      'region' => 'East Blue',  'macro' => 'east_blue',  'tier' => 1, 'peligro_base' => 2),

        // ── WEST BLUE ──────────────────────────────────────────
        'reino_sorbet'   => array('slug' => 'reino_sorbet',   'nombre' => 'Reino de Sorbet', 'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 1),
        'isla_kalimero'  => array('slug' => 'isla_kalimero',  'nombre' => 'Isla Kalimero',   'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 1),
        'puerto_neblina' => array('slug' => 'puerto_neblina', 'nombre' => 'Puerto Neblina',  'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
        'isla_verthom'   => array('slug' => 'isla_verthom',   'nombre' => 'Isla Verthom',    'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
        'bahia_rosclave' => array('slug' => 'bahia_rosclave', 'nombre' => 'Bahia Rosclave',  'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
        'isla_fenrik'    => array('slug' => 'isla_fenrik',    'nombre' => 'Isla Fenrik',     'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
        'costa_malmuerta'=> array('slug' => 'costa_malmuerta','nombre' => 'Costa Malmuerta', 'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),
        'isla_corvenna'  => array('slug' => 'isla_corvenna',  'nombre' => 'Isla Corvenna',   'region' => 'West Blue',  'macro' => 'west_blue',  'tier' => 1, 'peligro_base' => 2),

        // ── NORTH BLUE ─────────────────────────────────────────
        'reino_germa'    => array('slug' => 'reino_germa',    'nombre' => 'Reino de Germa',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 3),
        'isla_thornveil' => array('slug' => 'isla_thornveil', 'nombre' => 'Isla Thornveil',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
        'puerto_grisalba'=> array('slug' => 'puerto_grisalba','nombre' => 'Puerto Grisalba', 'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 1),
        'isla_kelthorn'  => array('slug' => 'isla_kelthorn',  'nombre' => 'Isla Kelthorn',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
        'bahia_escarcha' => array('slug' => 'bahia_escarcha', 'nombre' => 'Bahia Escarcha',  'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),
        'isla_draconis'  => array('slug' => 'isla_draconis',  'nombre' => 'Isla Draconis',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 3),
        'puerto_nevado'  => array('slug' => 'puerto_nevado',  'nombre' => 'Puerto Nevado',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 1),
        'isla_varnholt'  => array('slug' => 'isla_varnholt',  'nombre' => 'Isla Varnholt',   'region' => 'North Blue', 'macro' => 'north_blue', 'tier' => 1, 'peligro_base' => 2),

        // ── SOUTH BLUE ─────────────────────────────────────────
        'isla_momoiro'   => array('slug' => 'isla_momoiro',   'nombre' => 'Isla Momoiro',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
        'isla_baterilla' => array('slug' => 'isla_baterilla', 'nombre' => 'Isla Baterilla',  'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
        'isla_sorenna'   => array('slug' => 'isla_sorenna',   'nombre' => 'Isla Sorenna',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 1),
        'puerto_ceniza'  => array('slug' => 'puerto_ceniza',  'nombre' => 'Puerto Ceniza',   'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
        'isla_corvalle'  => array('slug' => 'isla_corvalle',  'nombre' => 'Isla Corvalle',   'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
        'bahia_marlot'   => array('slug' => 'bahia_marlot',   'nombre' => 'Bahia Marlot',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 2),
        'isla_tessaly'   => array('slug' => 'isla_tessaly',   'nombre' => 'Isla Tessaly',    'region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 1),
        'puerto_serpiente'=> array('slug' => 'puerto_serpiente','nombre'=> 'Puerto Serpiente','region' => 'South Blue', 'macro' => 'south_blue', 'tier' => 1, 'peligro_base' => 3),

        // ── CALM BELT ──────────────────────────────────────────
        'amazon_lily'    => array('slug' => 'amazon_lily',    'nombre' => 'Amazon Lily',     'region' => 'Calm Belt',  'macro' => 'calm_belt',  'tier' => 3, 'peligro_base' => 7),
        'isla_gyojin'    => array('slug' => 'isla_gyojin',    'nombre' => 'Isla Gyojin',     'region' => 'Calm Belt',  'macro' => 'calm_belt',  'tier' => 3, 'peligro_base' => 8),

        // ── RED LINE ───────────────────────────────────────────
        'marineford'     => array('slug' => 'marineford',     'nombre' => 'Marineford',      'region' => 'Red Line',   'macro' => 'red_line',   'tier' => 4, 'peligro_base' => 9),
        'mary_geoise'    => array('slug' => 'mary_geoise',    'nombre' => 'Mary Geoise',     'region' => 'Red Line',   'macro' => 'red_line',   'tier' => 4, 'peligro_base' => 9),

        // ── PARADISE (Grand Line primera mitad) ────────────────
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
        'sabaody'        => array('slug' => 'sabaody',        'nombre' => 'Archipielago Sabaody', 'region' => 'Paradise', 'macro' => 'paradise','tier' => 3, 'peligro_base' => 5),

        // ── NEW WORLD (Grand Line segunda mitad) ───────────────
        'punk_hazard'    => array('slug' => 'punk_hazard',    'nombre' => 'Punk Hazard',     'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
        'dressrosa'      => array('slug' => 'dressrosa',      'nombre' => 'Dressrosa',       'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 6),
        'zou'            => array('slug' => 'zou',            'nombre' => 'Zou',             'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
        'wano'           => array('slug' => 'wano',           'nombre' => 'Wano Country',    'region' => 'New World',  'macro' => 'new_world',  'tier' => 5, 'peligro_base' => 8),
        'elbaf'          => array('slug' => 'elbaf',          'nombre' => 'Elbaf',           'region' => 'New World',  'macro' => 'new_world',  'tier' => 4, 'peligro_base' => 7),
        'whole_cake'     => array('slug' => 'whole_cake',     'nombre' => 'Whole Cake Island','region'=> 'New World',  'macro' => 'new_world',  'tier' => 5, 'peligro_base' => 8),
    );

    return $cache;
}

/** Busca una isla por su slug. Devuelve null si no existe. */
function ope_isla_por_slug($slug)
{
    $cat = ope_islas_catalogo();
    $slug = (string) $slug;
    return isset($cat[$slug]) ? $cat[$slug] : null;
}

/** Nombre legible de una isla por slug. */
function ope_isla_nombre($slug)
{
    $isla = ope_isla_por_slug($slug);
    return $isla ? (string) $isla['nombre'] : (string) $slug;
}

/** Devuelve las islas agrupadas por region. */
function ope_islas_por_region()
{
    $out = array();
    foreach (ope_islas_catalogo() as $isla) {
        $out[$isla['region']][] = $isla;
    }
    return $out;
}

/** Devuelve las islas agrupadas por macro-mar. */
function ope_islas_por_macro()
{
    $out = array();
    foreach (ope_islas_catalogo() as $isla) {
        $out[$isla['macro']][] = $isla;
    }
    return $out;
}

/** Lista de macro-mares con label legible. */
function ope_islas_macro_list()
{
    return array(
        'east_blue'  => 'East Blue',
        'west_blue'  => 'West Blue',
        'north_blue' => 'North Blue',
        'south_blue' => 'South Blue',
        'calm_belt'  => 'Calm Belt',
        'red_line'   => 'Red Line',
        'paradise'   => 'Paradise',
        'new_world'  => 'New World',
    );
}

/** Devuelve true si el macro-mar es un Blue. */
function ope_isla_es_blue($macro)
{
    return in_array((string) $macro, array('east_blue', 'west_blue', 'north_blue', 'south_blue'), true);
}

/** Devuelve true si el macro-mar es parte de Grand Line (Paradise o New World). */
function ope_isla_es_grand_line($macro)
{
    return in_array((string) $macro, array('paradise', 'new_world'), true);
}

/** Todos los slugs de islas. */
function ope_islas_slugs()
{
    return array_keys(ope_islas_catalogo());
}
