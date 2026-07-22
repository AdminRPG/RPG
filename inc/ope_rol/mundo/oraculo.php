<?php
/**
 * One Piece: Eternal · Oráculo de Viaje (4 mesas D100 por tramo).
 * Clima · Encuentros · Hallazgos · Peligros — AV-02 / guías de viaje.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Oficios de a bordo reconocidos y sus bonificaciones por mesa. */
function ope_oraculo_oficios_config()
{
    return array(
        'navegante'  => array('clima' => -12, 'peligros' => -4),
        'timonel'    => array('peligros' => -10, 'encuentros' => -3),
        'vigia'      => array('hallazgos' => 12, 'encuentros' => -8),
        'carpintero' => array('peligros' => -6),
        'cocinero'   => array('peligros' => -4),
        'medico'     => array('peligros' => -5),
        'artillero'  => array('encuentros' => 4),
    );
}

/** Tipos de barco y sus modificadores base. */
function ope_oraculo_barcos_config()
{
    return array(
        'bote'     => array('label' => 'Bote / chalupa', 'clima' => -8, 'peligros' => 8,  'vel' => 1),
        'estandar' => array('label' => 'Barco pirata estándar', 'clima' => 0, 'peligros' => 0, 'vel' => 2),
        'veloz'    => array('label' => 'Goleta / barco rápido', 'clima' => 4, 'peligros' => 6,  'vel' => 3),
        'fragata'  => array('label' => 'Fragata / buque de guerra', 'clima' => -4, 'peligros' => -6, 'vel' => 2),
        'galeon'   => array('label' => 'Galeón pesado', 'clima' => -6, 'peligros' => -4, 'vel' => 1),
    );
}

function ope_oraculo_mesa_clima()
{
    return array(
        array('max' => 8,  'key' => 'tormenta_cat', 'nombre' => 'Tormenta catastrófica', 'icon' => '⛈', 'efecto' => 'Casco -15%. Timón bloqueado 1 tramo. Tirada de supervivencia obligatoria.', 'tone' => 'crit'),
        array('max' => 18, 'key' => 'huracan',      'nombre' => 'Huracán menor',         'icon' => '🌪', 'efecto' => 'Velocidad -50%. Ruta desviada +1 tramo off-rol.', 'tone' => 'bad'),
        array('max' => 32, 'key' => 'tormenta',     'nombre' => 'Tormenta eléctrica',    'icon' => '⚡', 'efecto' => 'Tripulación Aturdida al iniciar combate si hay encuentro.', 'tone' => 'bad'),
        array('max' => 48, 'key' => 'lluvia',       'nombre' => 'Lluvia persistente',    'icon' => '🌧', 'efecto' => 'Visibilidad reducida. Vigía -2 en hallazgos este tramo.', 'tone' => 'warn'),
        array('max' => 62, 'key' => 'nublado',      'nombre' => 'Cielo encapotado',      'icon' => '☁', 'efecto' => 'Navegación normal con ligera incomodidad.', 'tone' => 'neutral'),
        array('max' => 78, 'key' => 'brisa',        'nombre' => 'Brisa favorable',       'icon' => '🌬', 'efecto' => 'Velocidad +25%. -1 día de plazo off-rol.', 'tone' => 'good'),
        array('max' => 92, 'key' => 'calma',        'nombre' => 'Mar en calma',          'icon' => '🌊', 'efecto' => 'Viaje cómodo. Moral +5 al llegar.', 'tone' => 'good'),
        array('max' => 100,'key' => 'viento_perf',  'nombre' => 'Viento perfecto',       'icon' => '✨', 'efecto' => 'Velocidad +40%. Hallazgos +1 tier este tramo.', 'tone' => 'great'),
    );
}

function ope_oraculo_mesa_encuentros()
{
    return array(
        array('max' => 10, 'key' => 'yonko',       'nombre' => 'Flota de un Yonko',           'icon' => '👑', 'efecto' => 'Evitar o negociar. Combate naval PE 5+ si se enfrentan.', 'tone' => 'crit'),
        array('max' => 20, 'key' => 'almirante',   'nombre' => 'Patrulla de Almirante',       'icon' => '⚓', 'efecto' => 'Inspección Marine. Wanted visible → combate probable.', 'tone' => 'bad'),
        array('max' => 32, 'key' => 'piratas',     'nombre' => 'Barco pirata hostil',         'icon' => '🏴‍☠️', 'efecto' => 'Emboscada. Iniciativa enemiga si no detecta el Vigía.', 'tone' => 'bad'),
        array('max' => 44, 'key' => 'cazadores',   'nombre' => 'Cazarrecompensas',            'icon' => '🎯', 'efecto' => 'Persiguen al capitán con mayor bounty del grupo.', 'tone' => 'warn'),
        array('max' => 56, 'key' => 'mercante',    'nombre' => 'Convoy mercante',             'icon' => '📦', 'efecto' => 'Comercio, información o saqueo (decisión de tripulación).', 'tone' => 'neutral'),
        array('max' => 66, 'key' => 'naufrago',    'nombre' => 'Náufrago en tabla',           'icon' => '🆘', 'efecto' => 'Rescate posible. Pista de tesoro o misión secundaria.', 'tone' => 'good'),
        array('max' => 76, 'key' => 'ballena',     'nombre' => 'Ballena Islander amistosa',   'icon' => '🐋', 'efecto' => 'Transporte seguro +1 tramo. Sin peligros este tramo.', 'tone' => 'good'),
        array('max' => 86, 'key' => 'pescadores',  'nombre' => 'Flota pesquera local',        'icon' => '🎣', 'efecto' => 'Suministros +20%. Rumores del destino.', 'tone' => 'good'),
        array('max' => 94, 'key' => 'aliado',      'nombre' => 'Barco aliado de la tripulación','icon' => '🤝', 'efecto' => 'Apoyo en combate. Reparaciones menores gratis.', 'tone' => 'great'),
        array('max' => 100,'key' => 'nada',        'nombre' => 'Horizonte despejado',         'icon' => '🌅', 'efecto' => 'Sin encuentros significativos este tramo.', 'tone' => 'neutral'),
    );
}

function ope_oraculo_mesa_hallazgos()
{
    return array(
        array('max' => 12, 'key' => 'poneglyph',   'nombre' => 'Fragmento de ruina astral',    'icon' => '🗿', 'efecto' => 'Historia antigua. Requiere Arqueólogo/Navegante para descifrar.', 'tone' => 'great'),
        array('max' => 24, 'key' => 'isla_flot',   'nombre' => 'Isla flotante oculta',        'icon' => '🏝', 'efecto' => 'Parada opcional. Botín o NPC único.', 'tone' => 'great'),
        array('max' => 36, 'key' => 'cofre',       'nombre' => 'Cofre flotante sellado',      'icon' => '💰', 'efecto' => 'Rupias o objeto de tienda (tirada D100).', 'tone' => 'good'),
        array('max' => 48, 'key' => 'mapa',        'nombre' => 'Mapa náutico deteriorado',    'icon' => '🗺', 'efecto' => 'Ventaja en próximo viaje entre estas islas.', 'tone' => 'good'),
        array('max' => 58, 'key' => 'mensaje',     'nombre' => 'Mensaje en botella',          'icon' => '📜', 'efecto' => 'Pista de misión, tesoro o personaje.', 'tone' => 'good'),
        array('max' => 68, 'key' => 'recursos',    'nombre' => 'Banco de peces exóticos',     'icon' => '🐟', 'efecto' => 'Cocinero: comida premium. Suministros +15%.', 'tone' => 'good'),
        array('max' => 78, 'key' => 'flores',      'nombre' => 'Campo de flores de invierno', 'icon' => '❄', 'efecto' => 'Material raro para médico/cocinero.', 'tone' => 'neutral'),
        array('max' => 88, 'key' => 'restos',      'nombre' => 'Restos de naufragio antiguo', 'icon' => '⚙', 'efecto' => 'Carpintero recupera madera rara (+reparación).', 'tone' => 'neutral'),
        array('max' => 96, 'key' => 'medusas',     'nombre' => 'Banco de medusas luminiscentes','icon' => '✨', 'efecto' => 'Escena narrativa. Sin botín mecánico.', 'tone' => 'neutral'),
        array('max' => 100,'key' => 'nada_h',      'nombre' => 'Sin hallazgo',                'icon' => '—', 'efecto' => 'El mar no regala nada este tramo.', 'tone' => 'neutral'),
    );
}

function ope_oraculo_mesa_peligros()
{
    return array(
        array('max' => 8,  'key' => 'kraken',      'nombre' => 'Rey del Mar menor',           'icon' => '🦑', 'efecto' => 'Casco -25%. Evitar combate requiere tirada grupal.', 'tone' => 'crit'),
        array('max' => 18, 'key' => 'via_agua',    'nombre' => 'Vía de agua grave',           'icon' => '💧', 'efecto' => 'Casco -10%. Carpintero debe reparar antes del siguiente tramo.', 'tone' => 'bad'),
        array('max' => 30, 'key' => 'motin',       'nombre' => 'Motín a bordo',               'icon' => '⚔', 'efecto' => 'Moral -15. Cocinero/Médico reducen efecto.', 'tone' => 'bad'),
        array('max' => 42, 'key' => 'enfermedad',  'nombre' => 'Epidemia a bordo',            'icon' => '🤒', 'efecto' => '1D6 tripulantes Enfermos. Médico mitiga.', 'tone' => 'bad'),
        array('max' => 54, 'key' => 'timon',       'nombre' => 'Timón atascado',              'icon' => '🛞', 'efecto' => 'Timonel: 1 PA extra para desatascar. Sin él: +1 tramo.', 'tone' => 'warn'),
        array('max' => 64, 'key' => 'viento_c',    'nombre' => 'Viento en contra',            'icon' => '💨', 'efecto' => 'Retraso. +1 día de plazo off-rol.', 'tone' => 'warn'),
        array('max' => 74, 'key' => 'niebla',      'nombre' => 'Banco de niebla espesa',      'icon' => '🌫', 'efecto' => 'Riesgo de encallar. Navegante evita con tirada.', 'tone' => 'warn'),
        array('max' => 84, 'key' => 'suministros', 'nombre' => 'Suministros dañados',         'icon' => '📦', 'efecto' => 'Pérdida 10% comida/agua. Vigilar raciones.', 'tone' => 'neutral'),
        array('max' => 94, 'key' => 'mar_c',       'nombre' => 'Mar picado',                  'icon' => '🌊', 'efecto' => 'Mareos leves. Sin daño estructural.', 'tone' => 'neutral'),
        array('max' => 100,'key' => 'nada_p',      'nombre' => 'Tramo sin incidentes',        'icon' => '✓', 'efecto' => 'Navegación limpia. Tripulación descansa.', 'tone' => 'good'),
    );
}

/** Busca entrada de mesa por D100 (tabla acumulativa por max). */
function ope_oraculo_lookup(array $mesa, int $roll)
{
    $roll = max(1, min(100, $roll));
    foreach ($mesa as $row) {
        if ($roll <= (int) $row['max']) {
            return $row;
        }
    }
    return end($mesa) ?: array('nombre' => 'Resultado desconocido', 'icon' => '?', 'efecto' => '', 'tone' => 'neutral');
}

/** Calcula modificadores acumulados de oficios + barco. */
function ope_oraculo_calc_mods(array $tripulantes, string $barco_tipo)
{
    $mods = array('clima' => 0, 'encuentros' => 0, 'hallazgos' => 0, 'peligros' => 0);
    $cfg  = ope_oraculo_oficios_config();
    foreach ($tripulantes as $t) {
        $of = strtolower(trim((string) ($t['oficio'] ?? '')));
        if ($of === '' || !isset($cfg[$of])) continue;
        foreach ($cfg[$of] as $mesa => $val) {
            $mods[$mesa] = ($mods[$mesa] ?? 0) + (int) $val;
        }
    }
    $barcos = ope_oraculo_barcos_config();
    $bt = isset($barcos[$barco_tipo]) ? $barcos[$barco_tipo] : $barcos['estandar'];
    $mods['clima']    += (int) ($bt['clima'] ?? 0);
    $mods['peligros'] += (int) ($bt['peligros'] ?? 0);
    return $mods;
}

/** Aplica mod a un d100 (negativo = mejor suerte en mesas malas). */
function ope_oraculo_roll_mod(int $base, int $mod)
{
    return max(1, min(100, $base + $mod));
}

/** Un tramo completo: 4 tiradas + narrativa. */
function ope_oraculo_tramo(int $num, array $mods, ?array $forced = null)
{
    $mesas = array(
        'clima'      => ope_oraculo_mesa_clima(),
        'encuentros' => ope_oraculo_mesa_encuentros(),
        'hallazgos'  => ope_oraculo_mesa_hallazgos(),
        'peligros'   => ope_oraculo_mesa_peligros(),
    );
    $out = array('num' => $num, 'cartas' => array(), 'narrativa' => '');
    $parts = array();
    foreach ($mesas as $key => $mesa) {
        $raw = isset($forced[$key]) ? (int) $forced[$key] : random_int(1, 100);
        $mod = (int) ($mods[$key] ?? 0);
        // Clima/peligros: mod negativo mejora (baja el roll). Hallazgos/encuentros hostiles: positivo sube.
        $adj = ($key === 'hallazgos') ? ope_oraculo_roll_mod($raw, $mod) : ope_oraculo_roll_mod($raw, -$mod);
        $hit = ope_oraculo_lookup($mesa, $adj);
        $out['cartas'][$key] = array(
            'roll'    => $raw,
            'roll_adj'=> $adj,
            'mod'     => $mod,
            'key'     => (string) ($hit['key'] ?? ''),
            'nombre'  => (string) ($hit['nombre'] ?? ''),
            'icon'    => (string) ($hit['icon'] ?? ''),
            'efecto'  => (string) ($hit['efecto'] ?? ''),
            'tone'    => (string) ($hit['tone'] ?? 'neutral'),
        );
        $parts[] = $hit['nombre'];
    }
    $out['narrativa'] = ope_oraculo_narrativa_tramo($num, $out['cartas']);
    return $out;
}

/** Texto narrativo combinado para un tramo. */
function ope_oraculo_narrativa_tramo(int $num, array $cartas)
{
    $c = $cartas['clima']['nombre'] ?? 'mar impredecible';
    $e = $cartas['encuentros']['nombre'] ?? 'silencio en el horizonte';
    $h = $cartas['hallazgos']['nombre'] ?? 'nada destacable';
    $p = $cartas['peligros']['nombre'] ?? 'calma relativa';
    return "Tramo {$num}: el cielo trae {$c}. En el horizonte aparece {$e}. "
         . "Los vigías reportan {$h}, mientras a bordo acecha {$p}. "
         . "La tripulación debe decidir cómo responder en sus posts.";
}

/** Ejecuta el oráculo para N tramos. */
function ope_oraculo_viaje(int $tramos, array $tripulantes, string $barco_tipo)
{
    $tramos = max(1, min(5, $tramos));
    $mods   = ope_oraculo_calc_mods($tripulantes, $barco_tipo);
    $result = array('mods' => $mods, 'tramos' => array());
    for ($i = 1; $i <= $tramos; $i++) {
        $result['tramos'][] = ope_oraculo_tramo($i, $mods);
    }
    return $result;
}

/** Posts mínimos y plazo según tramos (INI guía viajes). */
function ope_oraculo_posts_plazo(int $tramos)
{
    $map = array(1 => array(6, 5), 2 => array(12, 10), 3 => array(18, 15), 4 => array(24, 20), 5 => array(30, 25));
    $t = max(1, min(5, $tramos));
    return array('posts_min' => $map[$t][0], 'plazo_dias' => $map[$t][1]);
}
