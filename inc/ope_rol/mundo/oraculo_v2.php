<?php
/**
 * One Piece: Eternal · Oraculo de Viaje v2 (6 mesas D100 por tramo)
 * ------------------------------------------------------------------
 * Mesas: Clima, Encuentro, Peligro, Hallazgo, Misterio, Bonanza.
 * SIN EMOJIS — los iconos se manejan via CSS (clases .ope-vo-ico-*).
 *
 * Diferencias con oraculo.php (v1):
 *   - 6 mesas en vez de 4
 *   - Misterio se activa solo si peligro >= medio
 *   - Bonanza se activa solo si peligro <= medio O roll natural >= 95
 *   - Modificadores por macro-mar
 *   - Sin emojis en los datos; se usan clases CSS para iconos
 *
 * Uso:
 *   $oraculo = ope_oraculo_v2_viaje($tramos, $mods_total, $nivel_peligro_idx, $macro_origen, $macro_destino);
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ── MESA: CLIMA ────────────────────────────────────────────────

function ope_oraculo_v2_mesa_clima()
{
    return array(
        array('max' => 5,   'key' => 'tormenta_cat', 'nombre' => 'Tormenta catastrofica', 'ico' => 'tormenta-cat', 'efecto' => 'Casco -15%. Timon bloqueado 1 tramo. Tirada de supervivencia obligatoria.', 'tone' => 'crit'),
        array('max' => 12,  'key' => 'huracan',      'nombre' => 'Huracan menor',         'ico' => 'huracan',      'efecto' => 'Velocidad -50%. Ruta desviada +1 tramo off-rol.', 'tone' => 'bad'),
        array('max' => 22,  'key' => 'tormenta',     'nombre' => 'Tormenta electrica',    'ico' => 'tormenta',     'efecto' => 'Tripulacion Aturdida al iniciar combate si hay encuentro.', 'tone' => 'bad'),
        array('max' => 32,  'key' => 'lluvia',       'nombre' => 'Lluvia persistente',    'ico' => 'lluvia',       'efecto' => 'Visibilidad reducida. Vigia -2 en hallazgos este tramo.', 'tone' => 'warn'),
        array('max' => 40,  'key' => 'niebla',       'nombre' => 'Niebla densa',          'ico' => 'niebla',       'efecto' => 'Riesgo de encallar. Navegante evita con tirada.', 'tone' => 'warn'),
        array('max' => 55,  'key' => 'nublado',      'nombre' => 'Cielo encapotado',      'ico' => 'nublado',      'efecto' => 'Navegacion normal con ligera incomodidad.', 'tone' => 'neutral'),
        array('max' => 70,  'key' => 'parcial',      'nombre' => 'Cielo parcial',         'ico' => 'parcial',      'efecto' => 'Sin efectos notables.', 'tone' => 'neutral'),
        array('max' => 82,  'key' => 'brisa',        'nombre' => 'Brisa favorable',       'ico' => 'brisa',        'efecto' => 'Velocidad +25%. -1 dia de plazo off-rol.', 'tone' => 'good'),
        array('max' => 92,  'key' => 'calma',        'nombre' => 'Mar en calma',          'ico' => 'calma',        'efecto' => 'Viaje comodo. Moral +5 al llegar.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'viento_perf',  'nombre' => 'Viento perfecto',       'ico' => 'viento-perf',  'efecto' => 'Velocidad +40%. Hallazgos +1 tier este tramo.', 'tone' => 'great'),
    );
}

// ── MESA: ENCUENTRO ────────────────────────────────────────────

function ope_oraculo_v2_mesa_encuentros()
{
    return array(
        array('max' => 5,   'key' => 'yonko',       'nombre' => 'Flota de un Yonko',            'ico' => 'yonko',      'efecto' => 'Evasion o negociar. Combate naval PE 5+ si se enfrentan.', 'tone' => 'crit'),
        array('max' => 12,  'key' => 'almirante',   'nombre' => 'Patrulla de Almirante',        'ico' => 'almirante',  'efecto' => 'Inspeccion Marine. Wanted visible: combate probable.', 'tone' => 'bad'),
        array('max' => 22,  'key' => 'piratas',     'nombre' => 'Piratas hostiles',             'ico' => 'piratas',    'efecto' => 'Emboscada. Vigia puede detectar antes del abordaje.', 'tone' => 'bad'),
        array('max' => 32,  'key' => 'cazadores',   'nombre' => 'Cazarrecompensas',             'ico' => 'cazadores',  'efecto' => 'Persiguen al capitan con mayor bounty del grupo.', 'tone' => 'warn'),
        array('max' => 42,  'key' => 'marines',     'nombre' => 'Patrulla Marine estandar',     'ico' => 'marines',    'efecto' => 'Inspeccion rutinaria. Documentos en regla: sin problema.', 'tone' => 'warn'),
        array('max' => 55,  'key' => 'mercante',    'nombre' => 'Convoy mercante',              'ico' => 'mercante',   'efecto' => 'Comercio, informacion o saqueo (decision de la tripulacion).', 'tone' => 'neutral'),
        array('max' => 64,  'key' => 'naufrago',    'nombre' => 'Naufrago en tabla',            'ico' => 'naufrago',   'efecto' => 'Rescate posible. Pista de tesoro o mision secundaria.', 'tone' => 'good'),
        array('max' => 72,  'key' => 'pescadores',  'nombre' => 'Flota pesquera local',         'ico' => 'pescadores', 'efecto' => 'Suministros +20%. Rumores del destino.', 'tone' => 'good'),
        array('max' => 82,  'key' => 'ballena',     'nombre' => 'Ballena Islander amistosa',    'ico' => 'ballena',    'efecto' => 'Transporte seguro +1 tramo. Sin peligros este tramo.', 'tone' => 'good'),
        array('max' => 92,  'key' => 'aliado',      'nombre' => 'Barco aliado de la tripulacion','ico'=> 'aliado',     'efecto' => 'Apoyo en combate. Reparaciones menores gratis.', 'tone' => 'great'),
        array('max' => 100, 'key' => 'nada_e',      'nombre' => 'Horizonte despejado',          'ico' => 'despejado',  'efecto' => 'Sin encuentros significativos este tramo.', 'tone' => 'neutral'),
    );
}

// ── MESA: PELIGRO ──────────────────────────────────────────────

function ope_oraculo_v2_mesa_peligros()
{
    return array(
        array('max' => 5,   'key' => 'kraken',      'nombre' => 'Rey del Mar',             'ico' => 'kraken',       'efecto' => 'Casco -25%. Tirada grupal para evitar combate.', 'tone' => 'crit'),
        array('max' => 12,  'key' => 'via_agua',    'nombre' => 'Via de agua grave',       'ico' => 'via-agua',     'efecto' => 'Casco -10%. Carpintero debe reparar antes del siguiente tramo.', 'tone' => 'bad'),
        array('max' => 20,  'key' => 'motin',       'nombre' => 'Motin a bordo',           'ico' => 'motin',        'efecto' => 'Moral -15. Cocinero/Medico reducen efecto.', 'tone' => 'bad'),
        array('max' => 28,  'key' => 'enfermedad',  'nombre' => 'Epidemia a bordo',        'ico' => 'enfermedad',   'efecto' => '1D6 tripulantes Enfermos. Medico mitiga.', 'tone' => 'bad'),
        array('max' => 38,  'key' => 'incendio',    'nombre' => 'Incendio a bordo',        'ico' => 'incendio',     'efecto' => 'Casco -8%. Tirada para apagar antes de extenderse.', 'tone' => 'warn'),
        array('max' => 48,  'key' => 'timon',       'nombre' => 'Timon atascado',          'ico' => 'timon',        'efecto' => 'Timonel: 1 PA extra para desatascar. Sin timonel: +1 tramo.', 'tone' => 'warn'),
        array('max' => 58,  'key' => 'viento_c',    'nombre' => 'Viento en contra',        'ico' => 'viento-c',     'efecto' => 'Retraso. +1 dia de plazo off-rol.', 'tone' => 'warn'),
        array('max' => 68,  'key' => 'niebla_p',    'nombre' => 'Banco de niebla espesa',  'ico' => 'niebla-p',     'efecto' => 'Riesgo de encallar. Navegante evita con tirada.', 'tone' => 'warn'),
        array('max' => 78,  'key' => 'suministros', 'nombre' => 'Suministros daniados',    'ico' => 'suministros',  'efecto' => 'Perdida 10% comida/agua. Vigilar raciones.', 'tone' => 'neutral'),
        array('max' => 88,  'key' => 'mar_c',       'nombre' => 'Mar picado',              'ico' => 'mar-picado',   'efecto' => 'Mareos leves. Sin danio estructural.', 'tone' => 'neutral'),
        array('max' => 100, 'key' => 'nada_p',      'nombre' => 'Tramo sin incidentes',    'ico' => 'sin-peligro',  'efecto' => 'Navegacion limpia. Tripulacion descansa.', 'tone' => 'good'),
    );
}

// ── MESA: HALLAZGO ─────────────────────────────────────────────

function ope_oraculo_v2_mesa_hallazgos()
{
    return array(
        array('max' => 8,   'key' => 'poneglyph',   'nombre' => 'Fragmento de Poneglyph',    'ico' => 'poneglyph',   'efecto' => 'Historia antigua. Requiere Arqueologo/Navegante para descifrar.', 'tone' => 'great'),
        array('max' => 16,  'key' => 'isla_flot',   'nombre' => 'Isla flotante oculta',      'ico' => 'isla-flot',   'efecto' => 'Parada opcional. Botin o NPC unico.', 'tone' => 'great'),
        array('max' => 26,  'key' => 'cofre',       'nombre' => 'Cofre flotante sellado',    'ico' => 'cofre',       'efecto' => 'Berries o item de tienda aleatorio.', 'tone' => 'good'),
        array('max' => 36,  'key' => 'mapa',        'nombre' => 'Mapa nautico deteriorado',  'ico' => 'mapa',        'efecto' => 'Ventaja en proximo viaje entre estas islas.', 'tone' => 'good'),
        array('max' => 46,  'key' => 'mensaje',     'nombre' => 'Mensaje en botella',        'ico' => 'mensaje',     'efecto' => 'Pista de mision, tesoro o personaje.', 'tone' => 'good'),
        array('max' => 56,  'key' => 'recursos',    'nombre' => 'Banco de peces exoticos',   'ico' => 'peces',       'efecto' => 'Cocinero: comida premium. Suministros +15%.', 'tone' => 'good'),
        array('max' => 66,  'key' => 'flores',      'nombre' => 'Campo de flores raras',     'ico' => 'flores',      'efecto' => 'Material raro para medico/cocinero.', 'tone' => 'neutral'),
        array('max' => 76,  'key' => 'restos',      'nombre' => 'Restos de naufragio',       'ico' => 'naufragio',   'efecto' => 'Carpintero recupera madera rara (+reparacion).', 'tone' => 'neutral'),
        array('max' => 86,  'key' => 'medusas',     'nombre' => 'Medusas luminiscentes',     'ico' => 'medusas',     'efecto' => 'Escena narrativa. Sin botin mecanico.', 'tone' => 'neutral'),
        array('max' => 100, 'key' => 'nada_h',      'nombre' => 'Sin hallazgo',              'ico' => 'nada',        'efecto' => 'El mar no regala nada este tramo.', 'tone' => 'neutral'),
    );
}

// ── MESA: MISTERIO (solo si peligro >= medio) ──────────────────

function ope_oraculo_v2_mesa_misterio()
{
    return array(
        array('max' => 8,   'key' => 'barco_fant',  'nombre' => 'Barco fantasma',           'ico' => 'fantasma',     'efecto' => 'Embarcacion espectral. Dilema moral.', 'tone' => 'crit'),
        array('max' => 16,  'key' => 'anomalia',    'nombre' => 'Anomalia magnetica',       'ico' => 'anomalia',     'efecto' => 'Log Pose pierde rumbo 1 tramo.', 'tone' => 'bad'),
        array('max' => 26,  'key' => 'criatura',    'nombre' => 'Criatura del fondo',       'ico' => 'criatura',     'efecto' => 'Algo inmenso bajo el barco. Tension.', 'tone' => 'bad'),
        array('max' => 38,  'key' => 'luces',       'nombre' => 'Fenomeno luminoso',        'ico' => 'luces',        'efecto' => 'Luces bajo el agua. Atrae o repele criaturas.', 'tone' => 'warn'),
        array('max' => 50,  'key' => 'eclipse',     'nombre' => 'Eclipse repentino',        'ico' => 'eclipse',      'efecto' => 'Oscuridad total. Haki de Observacion util.', 'tone' => 'warn'),
        array('max' => 64,  'key' => 'canto',       'nombre' => 'Canto de sirena',          'ico' => 'sirena',       'efecto' => 'Test de voluntad (VOL/TEM) para la tripulacion.', 'tone' => 'neutral'),
        array('max' => 78,  'key' => 'ruinas_mar',  'nombre' => 'Ruinas emergentes',        'ico' => 'ruinas',       'efecto' => 'Estructuras suben del mar. Exploracion opcional.', 'tone' => 'good'),
        array('max' => 90,  'key' => 'mariposas',   'nombre' => 'Mariposas de mar',         'ico' => 'mariposas',    'efecto' => 'Criaturas que guian la ruta. Narrativo.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'estrella',    'nombre' => 'Estrella de navegacion',   'ico' => 'estrella',     'efecto' => '-1 tramo, +10% hallazgos.', 'tone' => 'great'),
    );
}

// ── MESA: BONANZA (solo si peligro <= medio O natural >= 95) ───

function ope_oraculo_v2_mesa_bonanza()
{
    return array(
        array('max' => 10,  'key' => 'tesoro',      'nombre' => 'Tesoro pirata hundido',    'ico' => 'tesoro',       'efecto' => '2.000-5.000 Berries.', 'tone' => 'great'),
        array('max' => 20,  'key' => 'ruta_sec',    'nombre' => 'Ruta secreta',             'ico' => 'ruta-sec',     'efecto' => 'Descubre atajo: -2 tramos futuros en esta ruta.', 'tone' => 'great'),
        array('max' => 35,  'key' => 'suministros_b','nombre'=> 'Suministros a la deriva',  'ico' => 'suministros-b','efecto' => 'Provisiones completas restauradas.', 'tone' => 'good'),
        array('max' => 50,  'key' => 'arcoiris',    'nombre' => 'Arcoiris doble',           'ico' => 'arcoiris',     'efecto' => 'Moral maxima. +5% hallazgos.', 'tone' => 'good'),
        array('max' => 65,  'key' => 'delfines',    'nombre' => 'Delfines guia',            'ico' => 'delfines',     'efecto' => '-1 dia off-rol.', 'tone' => 'good'),
        array('max' => 80,  'key' => 'festival',    'nombre' => 'Barco de festival',        'ico' => 'festival',     'efecto' => 'Descanso social. Rumores interesantes.', 'tone' => 'neutral'),
        array('max' => 95,  'key' => 'atardecer',   'nombre' => 'Atardecer legendario',     'ico' => 'atardecer',    'efecto' => 'Escena narrativa. Bonificacion moral.', 'tone' => 'neutral'),
        array('max' => 100, 'key' => 'golpe_doble', 'nombre' => 'Golpe de suerte doble',    'ico' => 'suerte',       'efecto' => 'Reroll: mejor resultado en 1 mesa a elegir.', 'tone' => 'great'),
    );
}

// ── MODIFICADORES POR MACRO-MAR ────────────────────────────────

/**
 * Modificadores base segun el macro-mar dominante del viaje.
 * Negativo en clima/peligro = EMPEORA (baja el roll hacia franjas malas).
 * Positivo en hallazgo = MEJORA (sube el roll hacia franjas buenas).
 */
function ope_oraculo_v2_mods_macro($macro)
{
    $map = array(
        'east_blue'  => array('clima' => 0,   'encuentro' => 0,   'peligro' => 0,   'hallazgo' => 0,  'misterio' => -20),
        'west_blue'  => array('clima' => 0,   'encuentro' => 0,   'peligro' => 0,   'hallazgo' => 0,  'misterio' => -20),
        'north_blue' => array('clima' => -3,  'encuentro' => 0,   'peligro' => 0,   'hallazgo' => 0,  'misterio' => -18),
        'south_blue' => array('clima' => 0,   'encuentro' => 0,   'peligro' => 0,   'hallazgo' => 0,  'misterio' => -20),
        'paradise'   => array('clima' => -5,  'encuentro' => -5,  'peligro' => -5,  'hallazgo' => 5,  'misterio' => 0),
        'new_world'  => array('clima' => -15, 'encuentro' => -10, 'peligro' => -12, 'hallazgo' => 8,  'misterio' => 10),
        'calm_belt'  => array('clima' => -20, 'encuentro' => 10,  'peligro' => -20, 'hallazgo' => -10,'misterio' => 5),
        'red_line'   => array('clima' => -25, 'encuentro' => -15, 'peligro' => -15, 'hallazgo' => -5, 'misterio' => 15),
    );
    return isset($map[$macro]) ? $map[$macro] : array('clima' => 0, 'encuentro' => 0, 'peligro' => 0, 'hallazgo' => 0, 'misterio' => 0);
}

// ── MOTOR DE ORACULO ───────────────────────────────────────────

/** Busca entrada de mesa por D100 (tabla acumulativa por max). */
function ope_oraculo_v2_lookup(array $mesa, $roll)
{
    $roll = max(1, min(100, (int)$roll));
    foreach ($mesa as $row) {
        if ($roll <= (int)$row['max']) {
            return $row;
        }
    }
    $last = end($mesa);
    return $last ? $last : array('nombre' => 'Resultado desconocido', 'ico' => 'unknown', 'efecto' => '', 'tone' => 'neutral');
}

/** Aplica mod a un d100. */
function ope_oraculo_v2_roll_mod($base, $mod)
{
    return max(1, min(100, (int)$base + (int)$mod));
}

/**
 * Genera un tramo del Oraculo.
 *
 * @param int   $num              Numero de tramo (1-based)
 * @param array $mods_total       Modificadores acumulados (barco + trip + items)
 * @param int   $nivel_peligro_idx Indice de peligro (0=muy_bajo ... 6=mortal)
 * @param string $macro_dominante  Macro-mar dominante del viaje
 */
function ope_oraculo_v2_tramo($num, array $mods_total, $nivel_peligro_idx, $macro_dominante)
{
    $mesas = array(
        'clima'      => ope_oraculo_v2_mesa_clima(),
        'encuentro'  => ope_oraculo_v2_mesa_encuentros(),
        'peligro'    => ope_oraculo_v2_mesa_peligros(),
        'hallazgo'   => ope_oraculo_v2_mesa_hallazgos(),
    );

    // Mods por macro-mar
    $macro_mods = ope_oraculo_v2_mods_macro((string)$macro_dominante);

    $out = array('num' => (int)$num, 'cartas' => array(), 'narrativa' => '');
    $any_natural_95 = false;

    foreach ($mesas as $key => $mesa) {
        $raw = random_int(1, 100);
        $mod_trip = (int)($mods_total[$key] ?? 0);
        $mod_macro = (int)($macro_mods[$key] ?? 0);
        $total_mod = $mod_trip + $mod_macro;

        // Clima/peligro: mod negativo empeora (baja roll). Hallazgo: mod positivo mejora (sube roll).
        if ($key === 'hallazgo') {
            $adj = ope_oraculo_v2_roll_mod($raw, $total_mod);
        } else {
            $adj = ope_oraculo_v2_roll_mod($raw, -$total_mod);
        }

        $hit = ope_oraculo_v2_lookup($mesa, $adj);
        $out['cartas'][$key] = array(
            'roll'     => $raw,
            'roll_adj' => $adj,
            'mod'      => $total_mod,
            'key'      => (string)($hit['key'] ?? ''),
            'nombre'   => (string)($hit['nombre'] ?? ''),
            'ico'      => (string)($hit['ico'] ?? ''),
            'efecto'   => (string)($hit['efecto'] ?? ''),
            'tone'     => (string)($hit['tone'] ?? 'neutral'),
        );

        if ($raw >= 95) $any_natural_95 = true;
    }

    // MISTERIO: solo si peligro >= medio (idx >= 2)
    if ((int)$nivel_peligro_idx >= 2) {
        $raw = random_int(1, 100);
        $mod_m = (int)($mods_total['misterio'] ?? 0) + (int)($macro_mods['misterio'] ?? 0);
        $adj = ope_oraculo_v2_roll_mod($raw, $mod_m);
        $hit = ope_oraculo_v2_lookup(ope_oraculo_v2_mesa_misterio(), $adj);
        $out['cartas']['misterio'] = array(
            'roll' => $raw, 'roll_adj' => $adj, 'mod' => $mod_m,
            'key' => (string)($hit['key'] ?? ''), 'nombre' => (string)($hit['nombre'] ?? ''),
            'ico' => (string)($hit['ico'] ?? ''), 'efecto' => (string)($hit['efecto'] ?? ''),
            'tone' => (string)($hit['tone'] ?? 'neutral'),
        );
    } else {
        $out['cartas']['misterio'] = null;
    }

    // BONANZA: solo si peligro <= medio (idx <= 2) O algun natural >= 95
    if ((int)$nivel_peligro_idx <= 2 || $any_natural_95) {
        $raw = random_int(1, 100);
        $mod_b = (int)($mods_total['bonanza'] ?? 0);
        $adj = ope_oraculo_v2_roll_mod($raw, $mod_b);
        $hit = ope_oraculo_v2_lookup(ope_oraculo_v2_mesa_bonanza(), $adj);
        $out['cartas']['bonanza'] = array(
            'roll' => $raw, 'roll_adj' => $adj, 'mod' => $mod_b,
            'key' => (string)($hit['key'] ?? ''), 'nombre' => (string)($hit['nombre'] ?? ''),
            'ico' => (string)($hit['ico'] ?? ''), 'efecto' => (string)($hit['efecto'] ?? ''),
            'tone' => (string)($hit['tone'] ?? 'neutral'),
        );
    } else {
        $out['cartas']['bonanza'] = null;
    }

    // Narrativa
    $out['narrativa'] = ope_oraculo_v2_narrativa_tramo($num, $out['cartas']);

    return $out;
}

/** Texto narrativo combinado para un tramo (sin emojis). */
function ope_oraculo_v2_narrativa_tramo($num, array $cartas)
{
    $c = isset($cartas['clima']) ? $cartas['clima']['nombre'] : 'mar impredecible';
    $e = isset($cartas['encuentro']) ? $cartas['encuentro']['nombre'] : 'silencio en el horizonte';
    $h = isset($cartas['hallazgo']) ? $cartas['hallazgo']['nombre'] : 'nada destacable';
    $p = isset($cartas['peligro']) ? $cartas['peligro']['nombre'] : 'calma relativa';

    $txt = "Tramo {$num}: el cielo trae {$c}. En el horizonte aparece {$e}. "
         . "Los vigias reportan {$h}, mientras a bordo acecha {$p}. "
         . "La tripulacion debe decidir como responder en sus posts.";

    // Misterio
    if (!empty($cartas['misterio'])) {
        $txt .= " Algo extranio ocurre: {$cartas['misterio']['nombre']}.";
    }
    // Bonanza
    if (!empty($cartas['bonanza'])) {
        $txt .= " Un golpe de suerte: {$cartas['bonanza']['nombre']}.";
    }

    return $txt;
}

/**
 * Ejecuta el Oraculo v2 para un viaje completo.
 *
 * @param int    $tramos             Numero de tramos del viaje (1-6)
 * @param array  $mods_total         Modificadores totales (barco + trip + items)
 * @param int    $nivel_peligro_idx  Indice de peligro (0-6)
 * @param string $macro_dominante    Macro-mar predominante
 * @return array Con 'mods', 'tramos' (array de resultados por tramo)
 */
function ope_oraculo_v2_viaje($tramos, array $mods_total, $nivel_peligro_idx, $macro_dominante)
{
    $tramos = max(1, min(6, (int)$tramos));
    $result = array('mods' => $mods_total, 'tramos' => array());
    for ($i = 1; $i <= $tramos; $i++) {
        $result['tramos'][] = ope_oraculo_v2_tramo($i, $mods_total, $nivel_peligro_idx, $macro_dominante);
    }
    return $result;
}

/** Posts minimos y plazo segun tramos (mejorado). */
function ope_oraculo_v2_posts_plazo($tramos, $nivel_idx = 0)
{
    $t = max(1, min(6, (int)$tramos));
    $posts = $t * 3 + (int)$nivel_idx * 2;
    $plazo = max(5, $t * 3);
    return array('posts_min' => $posts, 'plazo_dias' => $plazo);
}
