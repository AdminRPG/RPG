<?php
/**
 * One Piece: Eternal · Catálogo del sistema de rol
 * ------------------------------------------------
 * Fuente única para wizard (crear-personaje.php) y validación server-side.
 * Canon: I-Forge-Sistema/docs/01-PERSONAJE (STATS, FACTOR-LINAJE, ARMAS)
 *        + 11-SISTEMA ETERNAL (Identidad + Familia Arma).
 *
 * Arrays PHP planos → JSON para UI; validación en servidor al enviar.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// Catálogos + reglas + render Eternal (rutas relativas a core/).
require_once __DIR__ . '/../catalogos/eternal.php';
require_once __DIR__ . '/../catalogos/pj.php';
require_once __DIR__ . '/../catalogos/linaje.php';
require_once __DIR__ . '/../dominio/creacion.php';
require_once __DIR__ . '/eternal.php';

if (!function_exists('ope_rol_stats')) {
    /** Las 8 stats OP, agrupadas en 3 pilares (STATS.md). */
    function ope_rol_stats()
    {
        return array(
            'cuerpo' => array(
                'label' => 'Cuerpo',
                'stats' => array(
                    'FUE' => 'Fuerza',
                    'RES' => 'Resistencia',
                    'AGI' => 'Agilidad',
                ),
            ),
            'mente' => array(
                'label' => 'Mente',
                'stats' => array(
                    'INT' => 'Intelecto',
                    'PER' => 'Percepción',
                ),
            ),
            'espiritu' => array(
                'label' => 'Espíritu',
                'stats' => array(
                    'TEM' => 'Temple',
                    'VOL' => 'Voluntad',
                    'CAR' => 'Carisma',
                ),
            ),
        );
    }

    /** Lista plana de las 8 siglas, en orden de presentación. */
    function ope_rol_stat_keys()
    {
        $keys = array();
        foreach (ope_rol_stats() as $pilar) {
            foreach ($pilar['stats'] as $k => $v) {
                $keys[] = $k;
            }
        }
        return $keys;
    }

    /**
     * Tramo de poder 1–5 a partir del nivel (1–50).
     * I=1–10, II=11–20, III=21–30, IV=31–40, V=41–50.
     */
    function ope_rol_tramo($nivel)
    {
        $n = max(1, min(50, (int) $nivel));
        return (int) floor(($n - 1) / 10) + 1;
    }

    function ope_rol_tramo_romano($tramo)
    {
        static $map = array(1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V');
        $t = max(1, min(5, (int) $tramo));
        return $map[$t];
    }

    /** Bonus de PA por tramo (STATS.md §3.C). */
    function ope_rol_tramo_pa_bonus($nivel)
    {
        return ope_rol_tramo($nivel) - 1; // I→0 … V→4
    }

    /** Coste en PP de +1 stat según el tramo actual (STATS.md). */
    function ope_rol_pp_cost_tramo($nivel)
    {
        static $cost = array(1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50);
        $t = ope_rol_tramo($nivel);
        return (int) ($cost[$t] ?? 50);
    }

    /**
     * Techo del valor INVERTIDO de un stat (base 1 + puntos de creación + comprado
     * con PP). STATS.md. El linaje, las dotes y los buffs de combate se suman
     * POR ENCIMA de este techo y sí pueden superar 99.
     */
    function ope_rol_stat_techo()
    {
        return 99;
    }

    /**
     * Tope del valor INVERTIDO (base + comprado) de un stat según el tramo (STATS.md).
     * El Tramo V llega al techo de inversión (99). El racial/dotes/buffs van aparte.
     */
    function ope_rol_stat_cap_tramo($nivel)
    {
        static $cap = array(1 => 20, 2 => 35, 3 => 55, 4 => 75, 5 => 99);
        $t = ope_rol_tramo($nivel);
        return (int) ($cap[$t] ?? ope_rol_stat_techo());
    }

    /** Puntos de stat comprados por nivel (STATS.md). */
    function ope_rol_stats_por_nivel()
    {
        return 5;
    }

    /**
     * Nivel desde stats comprados con PP (cap 50).
     * Nivel = min(50, 1 + floor(comprados / 5))
     */
    function ope_rol_nivel_from_stats_comprados($stats_comprados)
    {
        $c = max(0, (int) $stats_comprados);
        $span = ope_rol_stats_por_nivel();
        return (int) min(50, 1 + (int) floor($c / $span));
    }

    /** @deprecated Usar ope_rol_nivel_from_stats_comprados */
    function ope_rol_nivel_from_sum($sum)
    {
        // Compat: suma total ya no define nivel; devolver tramo aproximado sin romper callers.
        // 28 = 8 base + 20 de creación (antes de comprar con PP).
        return ope_rol_nivel_from_stats_comprados(max(0, (int) $sum - 28));
    }

    function ope_rol_nivel_label($nivel)
    {
        $n = max(1, (int) $nivel);
        if ($n >= 50) {
            return 'Nivel 50 · Prestigio';
        }
        $t = ope_rol_tramo($n);
        return 'Nivel ' . $n . ' · Tramo ' . ope_rol_tramo_romano($t);
    }

    /**
     * Stats comprados necesarios para ALCANZAR un nivel objetivo.
     * Nivel 1 → 0; Nivel N → (N-1)*5
     */
    function ope_rol_stats_para_nivel($nivel_objetivo)
    {
        $n = max(1, min(50, (int) $nivel_objetivo));
        return ($n - 1) * ope_rol_stats_por_nivel();
    }

    function ope_rol_nivel_maximo_stats($stats_ganados)
    {
        return ope_rol_nivel_from_stats_comprados($stats_ganados);
    }

    function ope_rol_puede_subir_stats($nivel_actual, $stats_ganados)
    {
        $n = max(1, (int) $nivel_actual);
        if ($n >= 50) {
            return false; // Prestigio: no más stats
        }
        return true;
    }

    function ope_rol_puede_subir_nivel($nivel_actual, $stats_ganados)
    {
        $n = max(1, (int) $nivel_actual);
        if ($n >= 50) {
            return false;
        }
        $necesario = ope_rol_stats_para_nivel($n + 1);
        return ((int) $stats_ganados) >= $necesario;
    }

    function ope_rol_stat_num($stats, $key, $default = 1)
    {
        if (!is_array($stats) || !array_key_exists($key, $stats)) {
            return max(0, (int) $default);
        }
        return max(0, (int) $stats[$key]);
    }

    /** Suma de las 8 stats efectivas. */
    function ope_rol_stat_sum($stats)
    {
        $sum = 0;
        foreach (ope_rol_stat_keys() as $sk) {
            $sum += ope_rol_stat_num($stats, $sk);
        }
        return $sum;
    }

    /**
     * Coste PP de +1 al stat = coste del tramo actual del PJ (no del valor del stat).
     * $current_val se ignora; se mantiene la firma por compatibilidad.
     * Preferir ope_rol_pp_cost_tramo($nivel).
     */
    function ope_rol_stat_upgrade_cost($current_val, $nivel = 1)
    {
        return ope_rol_pp_cost_tramo($nivel);
    }

    function ope_rol_stat_label($val)
    {
        $v = (int) $val;
        if ($v >= 90) return 'Leyenda';
        if ($v >= 75) return 'Cúspide';
        if ($v >= 55) return 'Élite';
        if ($v >= 40) return 'Maestro';
        if ($v >= 25) return 'Veterano';
        if ($v >= 15) return 'Sólido';
        if ($v >= 8)  return 'Competente';
        if ($v >= 3)  return 'Novato';
        return 'Básico';
    }
}

if (!function_exists('ope_rol_razas')) {
    /**
     * Los 10 linajes OP (FACTOR-LINAJE.md §3). Perfil de stats FIJO sin elección del
     * jugador (positivos y negativos). Cada linaje se ajusta a su lugar canónico
     * (la mayoría neta +4, Gigantes neta +8, Humanos neto 0).
     * 'mods' = array stat => delta (aplicado automáticamente, sin radio de elección).
     * Fase 2: modificadores flotantes guiados (2 opciones por linaje).
     */
    function ope_rol_razas()
    {
        return array(
            'humanos' => array(
                'nombre' => 'Humanos',
                'resumen' => 'La raza más numerosa y diversa de los mares. Sin stats de raza, fuertes de espíritu.',
                'tamano' => '1,50–1,95 m',
                'cultura' => 'La civilización más extendida de los mares: nobles, piratas, científicos y comerciantes bajo una infinidad de valores distintos. No tienen una cultura única, sino la suma de todas.',
                'bonus' => array('label' => 'Sin bonus ni penalización de stats', 'mods' => array()),
                'rasgo' => array('nombre' => 'Improvisar y Resistir', 'efecto' => 'Improvisar (1×/combate, 0 PA): antes de resolver un Gap puedes usar un stat distinto al que marca la tabla (con justificación narrativa; el staff puede vetarlo). Resiliencia: bajo Miedo/Terror/Intimidación, duración a la mitad (mín. 1) y +3 Voluntad efectiva mientras dure.'),
            ),
            'oni' => array(
                'nombre' => 'Oni',
                'resumen' => 'El techo de fuerza física de los mares, que escala su propia violencia golpe a golpe.',
                'tamano' => '3–4 m',
                'cultura' => 'Clanes guerreros aislados o piratas solitarios del Nuevo Mundo. El honor se mide en combates sobrevividos; el sake y la celebración violenta forman parte de su ritual social.',
                'bonus' => array('label' => '+6 Fuerza, −2 Voluntad', 'mods' => array('FUE' => 6, 'VOL' => -2)),
                'rasgo' => array('nombre' => 'Sangre Hirviente', 'efecto' => 'Ganas 1 carga de Furia (máx. 3) al golpear o recibir ≥10% de tu PV máx. en daño bruto. Al atacar puedes gastar cargas: +8 daño bruto por carga, pero −5% mitigación ese turno por carga. Ebrio (haber bebido de verdad en la ficción): máx. Furia sube a 5 y desaparece la penalización de mitigación. Recurso propio, se resetea al fin del combate.'),
            ),
            'gigantes' => array(
                'nombre' => 'Gigantes',
                'resumen' => 'La raza físicamente más grande y dura del catálogo (neto +8); su tamaño cambia el campo.',
                'tamano' => '15–30 m (jugables); adultos de Elbaf 13–24 m',
                'cultura' => 'Orgullosos hasta el extremo, con un código de honor guerrero inquebrantable. Viven hasta 300 años. Mentir o huir de un duelo aceptado es la peor falta imaginable.',
                'bonus' => array('label' => '+6 Fuerza, +6 Resistencia, −4 Agilidad', 'mods' => array('FUE' => 6, 'RES' => 6, 'AGI' => -4)),
                'rasgo' => array('nombre' => 'Presencia Colosal', 'efecto' => 'No derribable por Gap de FUE si tu RES ≥ atacante. Ignoras la penalización de Terreno difícil en tu movimiento. Categoría de Tamaño (declaras altura 15–30 m en ficha): Pequeño (15–20 m) +2 m alcance / +1 Rotura / −1 PA rival; Mediano (21–25 m) +3 m / +1 / −2; Grande (26–30 m) +4 m / +2 / −3. La reducción de PA solo afecta a rivales menores y nunca los baja de 3 PA.'),
            ),
            'buccaneers' => array(
                'nombre' => 'Buccaneers',
                'resumen' => 'Sangre de gigante en cuerpo humano; un cuerpo que se niega a rendirse.',
                'tamano' => '2–6 m',
                'cultura' => 'Supervivientes estoicos y leales, históricamente perseguidos por su linaje. Hermandades pequeñas y muy unidas; generaciones ocultando su origen los han hecho difíciles de leer.',
                'bonus' => array('label' => '+6 Resistencia, −2 Carisma', 'mods' => array('RES' => 6, 'CAR' => -2)),
                'rasgo' => array('nombre' => 'Voluntad que no se Quiebra', 'efecto' => 'PV máximo +10%. Los estados que te aplican con Gap ≥+6 nunca duran más que su duración base contigo (el ×1,25 de daño sí aplica). Una vez por combate, al caer bajo 20% PV recuperas 5% PV máx. Bajo 30% PV, el daño físico recibido se reduce un 10% adicional.'),
            ),
            'minks' => array(
                'nombre' => 'Minks',
                'resumen' => 'Pueblo animal de Zou; Electro en la sangre y Sulong bajo la luna llena.',
                'tamano' => '1,50–2,00 m',
                'cultura' => 'Tribus profundamente unidas por especie/clan, liderazgo compartido (Guardianes). La luna llena es un evento espiritual y cultural central en su calendario.',
                'bonus' => array('label' => '+4 Agilidad, +4 Fuerza, −4 Voluntad', 'mods' => array('AGI' => 4, 'FUE' => 4, 'VOL' => -4)),
                'rasgo' => array('nombre' => 'Latido Salvaje', 'efecto' => 'Rasgos Raciales accesibles (se compran con PL): Latido Salvaje (EN→PV: 1 PV por cada 10 EN de una técnica, máx. 10) y Sulong Incontrolado (Nv.21+, luna llena real). Dote innata accesible: "Electro Bestial" (opcional, se compra con PL; ocupa 1 de las 4 dotes).'),
            ),
            'gyojins' => array(
                'nombre' => 'Gyojins',
                'resumen' => 'Hombres-pez; más fuertes que un humano, por debajo de un Oni, con piel-armadura de abismo.',
                'tamano' => '1,80–3,20 m (según especie de origen)',
                'cultura' => 'Tradiciones marciales muy ricas (Karate Gyojin en escuelas y dojos). Históricamente marginados en superficie; defienden con orgullo el Reino de Ryugu.',
                'bonus' => array('label' => '+6 Fuerza, −2 Percepción', 'mods' => array('FUE' => 6, 'PER' => -2)),
                'rasgo' => array('nombre' => 'Piel de Abismo', 'efecto' => 'Rasgos Raciales accesibles (se compran con PL): Piel de Abismo (+3 Armadura flat física) e Hijo del Mar (sumergido respiras sin límite y +2 Gap de FUE en Karate Gyojin). Dote innata accesible: "Karate Gyojin" (opcional, se compra con PL; ocupa 1 de las 4 dotes).'),
            ),
            'lunarians' => array(
                'nombre' => 'Lunarians',
                'resumen' => 'Llama dorsal que alterna entre tanque atado al suelo y cuerpo de vuelo.',
                'tamano' => '3–5 m',
                'cultura' => 'Raza casi extinta, perseguida por el Gobierno Mundial por los secretos de su linaje. Estoicos y misteriosos, a menudo ocultos bajo otra identidad.',
                'bonus' => array('label' => '+4 Resistencia, +3 Temple, −3 Agilidad', 'mods' => array('RES' => 4, 'TEM' => 3, 'AGI' => -3)),
                'rasgo' => array('nombre' => 'Llama Dorsal', 'efecto' => 'Rasgo Racial accesible (se compra con PL): Llama Dorsal — Encender/Apagar es acción libre (1×/turno). Encendida: −20% daño recibido, −2 AGI, 5 EN/turno. Apagada: +2 AGI de movimiento y vuelo sostenido Tier 2. Dote innata accesible: "Sangre de Lunarian" (opcional, se compra con PL; ocupa 1 de las 4 dotes).'),
            ),
            'skypeans' => array(
                'nombre' => 'Skypeans',
                'resumen' => 'Gente del cielo; ligeros y perceptivos, maestros de los Diales y de la caída.',
                'tamano' => '1,40–1,75 m',
                'cultura' => 'Habitantes de las islas del cielo (Skypiea, Birka). Guardianes de ruinas y templos de nubes; crecen desmontando, cargando y reparando Diales antes de leer y escribir.',
                'bonus' => array('label' => '+4 Agilidad, +3 Percepción, −3 Resistencia', 'mods' => array('AGI' => 4, 'PER' => 3, 'RES' => -3)),
                'rasgo' => array('nombre' => 'Maestros del Dial', 'efecto' => 'Rasgos Raciales accesibles (se compran con PL): Dominio del Dial (cualquier Dial cuesta 1 EN menos, mín. 1, sin Oficio de Ingeniería para su carga básica) y Dominio de la Caída (sin daño de caída, aterrizas de pie, desvías 5 m por cada 10 m). Dote innata accesible: "Maestro del Dial" (opcional, se compra con PL; ocupa 1 de las 4 dotes).'),
            ),
            'tontattas' => array(
                'nombre' => 'Tontattas',
                'resumen' => 'Enanos imposibles de calcular; nadie los anticipa bien, ni en la mesa ni en el papel.',
                'tamano' => '20–50 cm',
                'cultura' => 'Reinos ocultos (como Tontatta bajo Dressrosa), camuflados entre plantas y raíces. Expertos cultivadores; protegen ferozmente la naturaleza.',
                'bonus' => array('label' => '+4 Agilidad, +4 Fuerza, −4 Resistencia', 'mods' => array('AGI' => 4, 'FUE' => 4, 'RES' => -4)),
                'rasgo' => array('nombre' => 'Sombra Diminuta', 'efecto' => '+2 Gap fijo de AGI para esquivar y +2 Gap fijo de AGI al atacar. Tu daño físico no se reduce por diferencia de tamaño/Tramo contra oponentes superiores. Nunca puedes ser objetivo de Aplastamiento basado solo en diferencia de Tramo o Gap medio: el rival debe ganarte por Gap real, golpe a golpe.'),
            ),
            'merfolk' => array(
                'nombre' => 'Merfolk',
                'resumen' => 'Sirenas y tritones; los más veloces del océano, casi imposibles de sujetar.',
                'tamano' => '1,60–1,95 m (torso) + cola/piernas',
                'cultura' => 'Habitantes del Reino de Ryugu. Comparten isla, historia y escuelas de Karate Gyojin con los Gyojins. Comunicación y respeto únicos con la fauna marina.',
                'bonus' => array('label' => '+6 Agilidad, −2 Fuerza', 'mods' => array('AGI' => 6, 'FUE' => -2)),
                'rasgo' => array('nombre' => 'Cuerpo sin Ancla', 'efecto' => 'Rasgos Raciales accesibles (se compran con PL): Cuerpo sin Ancla (nadas al doble, respiras bajo el agua, +3 Gap anti-Derribo/agarre) y Corriente Viva (bajo el agua ignoras 10% de mitigación; arrastras a 1 persona). Dote innata accesible: "Karate Gyojin" (opcional, se compra con PL; ocupa 1 de las 4 dotes).'),
            ),
        );
    }
}

// Facciones, armas, packs de equipo, virtudes/rasgos, defectos, elementos,
// enlace inicial, rupies y find_* viven en inc/ope_rol/catalogos/pj.php
// (cargado arriba). No se redeclaran aquí.

if (!function_exists('ope_rol_ps_iniciales')) {
    /** 20 Puntos de Stat para distribuir en creación OPE. */
    function ope_rol_ps_iniciales($raza = '') {
        return 20;
    }
}

if (!function_exists('ope_rol_aplicar_pasivas')) {
    /** OPE: no hay multiplicadores. El +1 racial se aplica en wizard. */
    function ope_rol_aplicar_pasivas($stats_base, $raza_data) {
        return $stats_base;
    }
}

if (!function_exists('ope_rol_stats_base')) {
    /** 8 stats OPE, todas base 1. */
    function ope_rol_stats_base() {
        $base = array();
        foreach (ope_rol_stat_keys() as $sk) {
            $base[$sk] = 1;
        }
        return $base;
    }
}

if (!function_exists('ope_rol_pc_iniciales')) {
    /** No usado en OPE (dotes suma 0). Se mantiene por compatibilidad. */
    function ope_rol_pc_iniciales() { return 0; }
}

// ---------------------------------------------------------------------------
// CARTAS DE TÉCNICA (INI-03) — catálogo de las 6 categorías de tags y de los
// 5 tiers. Fuente única de verdad para el creador de cartas y el render del
// deck en la ficha.
// ---------------------------------------------------------------------------

if (!function_exists('ope_rol_tecnica_tags')) {
    /**
     * Las 6 categorías de tags de una carta de técnica.
     *   key      â†’ identificador de la categoría (se guarda en JSON)
     *   nombre   â†’ etiqueta visible
     *   pregunta â†’ ayuda contextual
     *   multi    â†’ true = varios tags; false = uno solo (radio)
     *   max      â†’ tope de tags cuando multi (0 = sin tope)
     *   accent   â†’ variable CSS de color de acento
     *   tags     â†’ id => etiqueta visible
     */
    function ope_rol_tecnica_tags()
    {
        return array(
            'estilo' => array(
                'nombre'   => 'Estilo',
                'pregunta' => '¿De dónde proviene la técnica?',
                'multi'    => true,
                'max'      => 3,
                'accent'   => 'var(--crack)',
                'tags'     => array(
                    'Propio' => 'Propio',
                    'Haki'   => 'Haki',
                    'Akuma'  => 'Akuma',
                ),
            ),
            'tipo' => array(
                'nombre'   => 'Tipo',
                'pregunta' => '¿Qué función táctica cumple?',
                'multi'    => false,
                'max'      => 1,
                'accent'   => 'var(--ember-hi)',
                'tags'     => array(
                    'Ofensiva'  => 'Ofensiva',
                    'Defensiva' => 'Defensiva',
                    'Soporte'   => 'Soporte',
                    'Control'   => 'Control',
                    'Movilidad' => 'Movilidad',
                    'Utilidad'  => 'Utilidad',
                ),
            ),
            'alcance' => array(
                'nombre'   => 'Alcance',
                'pregunta' => '¿Hasta dónde llega el efecto?',
                'multi'    => false,
                'max'      => 1,
                'accent'   => 'var(--patina-hi)',
                'tags'     => array(
                    'Cuerpo a Cuerpo' => 'Cuerpo a Cuerpo',
                    'Corto Alcance'   => 'Corto Alcance',
                    'Medio Alcance'   => 'Medio Alcance',
                    'Largo Alcance'   => 'Largo Alcance',
                    'Área'            => 'Área',
                    'Línea'           => 'Línea',
                    'Personal'        => 'Personal',
                ),
            ),
            'elemento' => array(
                'nombre'   => 'Elemento',
                'pregunta' => '¿Tiene afinidad elemental?',
                'multi'    => false,
                'max'      => 1,
                'accent'   => 'var(--h6)',
                'tags'     => array(
                    'Ninguno'       => 'Ninguno',
                    'Fuego'         => 'Fuego',
                    'Hielo'         => 'Hielo',
                    'Electricidad'  => 'Electricidad',
                    'Agua'          => 'Agua',
                    'Tierra'        => 'Tierra',
                    'Aire / Viento' => 'Aire / Viento',
                    'Luz'           => 'Luz',
                    'Oscuridad'     => 'Oscuridad',
                    'Planta'        => 'Planta',
                    'Veneno'        => 'Veneno',
                    'Sónico'        => 'Sónico',
                    'Espiritual'    => 'Espiritual',
                ),
            ),
            'estado' => array(
                'nombre'   => 'Estado Alterado',
                'pregunta' => '¿Qué efectos secundarios aplica al impactar?',
                'multi'    => true,
                'max'      => 3,
                'accent'   => 'var(--ember)',
                'tags'     => array(
                    'Ninguno'      => 'Ninguno',
                    'Aturdido'     => 'Aturdido',
                    'Quemado'      => 'Quemado',
                    'Paralizado'   => 'Paralizado',
                    'Sangrado'     => 'Sangrado',
                    'Cegado'       => 'Cegado',
                    'Confuso'      => 'Confuso',
                    'Derribado'    => 'Derribado',
                    'Inmovilizado' => 'Inmovilizado',
                    'Fortalecido'  => 'Fortalecido',
                    'Protegido'    => 'Protegido',
                    'Acelerado'    => 'Acelerado',
                    'Curado'       => 'Curado',
                    'Revitalizado' => 'Revitalizado',
                ),
            ),
            'ejecucion' => array(
                'nombre'   => 'Ejecución',
                'pregunta' => '¿Cómo se activa o qué propiedad especial tiene?',
                'multi'    => true,
                'max'      => 0,
                'accent'   => 'var(--patina)',
                'tags'     => array(
                    'Ninguno'     => 'Ninguno',
                    'Cargada'     => 'Cargada',
                    'Instantánea' => 'Instantánea',
                    'Canalizada'  => 'Canalizada',
                    'Reacción'    => 'Reacción',
                    'Combo'       => 'Combo',
                    'Perforante'  => 'Perforante',
                    'Frenesí'     => 'Frenesí',
                ),
            ),
        );
    }
}

if (!function_exists('ope_rol_tecnica_tiers')) {
    /**
     * Los 5 tiers de carta. Cada uno trae su presupuesto de poder
     * recomendado (para pistas y autocompletado en el creador).
     */
    function ope_rol_tecnica_tiers()
    {
        return array(
            1 => array('romano' => 'I',   'rango' => 'F â€“ D',    'pp' => 5,  'pa' => '1 â€“ 2', 'en' => '5 â€“ 10',  'reposo' => '0',            'dados' => '1d6 a 1d8 + stat', 'pa_def' => 1, 'en_def' => 8,  'reposo_def' => 0, 'dados_def' => '1d8 + FUE'),
            2 => array('romano' => 'II',  'rango' => 'D â€“ C',    'pp' => 8,  'pa' => '2',     'en' => '10 â€“ 15', 'reposo' => '1',            'dados' => '1d10 a 2d6 + stat', 'pa_def' => 2, 'en_def' => 12, 'reposo_def' => 1, 'dados_def' => '1d10 + FUE'),
            3 => array('romano' => 'III', 'rango' => 'B â€“ A',    'pp' => 12, 'pa' => '2 â€“ 3', 'en' => '15 â€“ 25', 'reposo' => '1 â€“ 2',        'dados' => '2d8 + stat', 'pa_def' => 3, 'en_def' => 20, 'reposo_def' => 2, 'dados_def' => '2d8 + FUE'),
            4 => array('romano' => 'IV',  'rango' => 'S',        'pp' => 18, 'pa' => '3 â€“ 4', 'en' => '25 â€“ 40', 'reposo' => '2',            'dados' => '3d8 a 4d6 + stat', 'pa_def' => 4, 'en_def' => 32, 'reposo_def' => 2, 'dados_def' => '3d8 + FUE'),
            5 => array('romano' => 'V',   'rango' => 'SS â€“ M+',  'pp' => 25, 'pa' => '4 â€“ 5', 'en' => '40 â€“ 60', 'reposo' => '3 / Escena',   'dados' => '4d8 a 5d6 + stat', 'pa_def' => 5, 'en_def' => 50, 'reposo_def' => 3, 'dados_def' => '4d8 + FUE'),
        );
    }
}

if (!function_exists('ope_rol_tecnica_valida_tags')) {
    /**
     * Normaliza y valida un conjunto de tags contra el catálogo.
     * Devuelve array('estilo'=>[...], 'tipo'=>'', ...) saneado y respetando
     * multi/single y los topes 'max'. Descarta ids desconocidos.
     */
    function ope_rol_tecnica_valida_tags($in)
    {
        $cat = ope_rol_tecnica_tags();
        $out = array();
        foreach ($cat as $ck => $c) {
            $valid = $c['tags'];
            if ($c['multi']) {
                $sel = isset($in[$ck]) && is_array($in[$ck]) ? $in[$ck] : array();
                $clean = array();
                foreach ($sel as $t) {
                    if (isset($valid[$t]) && !in_array($t, $clean, true)) {
                        $clean[] = $t;
                    }
                }
                if ($c['max'] > 0) {
                    $clean = array_slice($clean, 0, $c['max']);
                }
                $out[$ck] = $clean;
            } else {
                $t = isset($in[$ck]) ? (string) $in[$ck] : '';
                $out[$ck] = isset($valid[$t]) ? $t : '';
            }
        }
        return $out;
    }
}

if (!function_exists('ope_rol_tecnica_tags_flat')) {
    /**
     * Convierte la estructura de tags en una lista plana con formato
     * "[Categoría: Valor]" (como en la guía INI-03), lista para pintar chips.
     * Cada item: array('cat'=>ck, 'accent'=>css, 'texto'=>'[Estilo: Propio]', 'valor'=>'Propio').
     */
    function ope_rol_tecnica_tags_flat($tags)
    {
        $cat = ope_rol_tecnica_tags();
        $flat = array();
        foreach ($cat as $ck => $c) {
            $etq = $c['nombre'];
            if ($c['multi']) {
                $vals = isset($tags[$ck]) && is_array($tags[$ck]) ? $tags[$ck] : array();
                foreach ($vals as $v) {
                    $flat[] = array('cat' => $ck, 'accent' => $c['accent'], 'valor' => $v, 'texto' => '[' . $etq . ': ' . $v . ']');
                }
            } else {
                $v = isset($tags[$ck]) ? (string) $tags[$ck] : '';
                if ($v !== '') {
                    $flat[] = array('cat' => $ck, 'accent' => $c['accent'], 'valor' => $v, 'texto' => '[' . $etq . ': ' . $v . ']');
                }
            }
        }
        return $flat;
    }
}
