<?php
/**
 * One Piece: Eternal · Oraculo de Mision (5 mesas D100)
 * ------------------------------------------------------
 * Mesas: Entorno, Encuentro, Aliado, Complicacion, Revelacion.
 * Diferentes al oraculo de viaje: centradas en la isla y la mision.
 *
 * Las mesas usan el mismo patron de lookup D100 acumulativo que oraculo_v2.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/* ── MESA: ENTORNO (condiciones de la isla/zona) ───────────────── */

function ope_oraculo_mision_mesa_entorno()
{
    return array(
        array('max' => 5,   'key' => 'desastre',    'nombre' => 'Desastre natural',        'ico' => 'ent-desastre',   'efecto' => 'Terremoto, tsunami o erupcion. Prioridad: sobrevivir.', 'tone' => 'crit'),
        array('max' => 12,  'key' => 'asedio',      'nombre' => 'Zona sitiada',            'ico' => 'ent-asedio',     'efecto' => 'Marines o bandidos tienen tomada la zona. Moverse es peligroso.', 'tone' => 'bad'),
        array('max' => 24,  'key' => 'tormenta_i',  'nombre' => 'Tormenta en la isla',     'ico' => 'ent-tormenta',   'efecto' => 'Lluvia torrencial. Visibilidad y movilidad reducidas.', 'tone' => 'bad'),
        array('max' => 36,  'key' => 'tension',     'nombre' => 'Tension en las calles',   'ico' => 'ent-tension',    'efecto' => 'La poblacion esta alterada. Las interacciones sociales tienen -2.', 'tone' => 'warn'),
        array('max' => 50,  'key' => 'mercado',     'nombre' => 'Dia de mercado',          'ico' => 'ent-mercado',    'efecto' => 'Multitud y ruido. Facil camuflarse, dificil buscar a alguien.', 'tone' => 'neutral'),
        array('max' => 62,  'key' => 'festival',    'nombre' => 'Festival local',          'ico' => 'ent-festival',   'efecto' => 'Celebracion en la isla. Oportunidad para obtener informacion.', 'tone' => 'good'),
        array('max' => 74,  'key' => 'atardecer',   'nombre' => 'Atardecer dorado',        'ico' => 'ent-atardecer',  'efecto' => 'Luz favorable. Las tiradas de Percepcion tienen +1.', 'tone' => 'good'),
        array('max' => 86,  'key' => 'niebla',      'nombre' => 'Niebla costera',          'ico' => 'ent-niebla',     'efecto' => 'Niebla densa al amanecer. Sigilo facilitado.', 'tone' => 'neutral'),
        array('max' => 95,  'key' => 'calma',       'nombre' => 'Isla en calma',           'ico' => 'ent-calma',      'efecto' => 'Sin alteraciones. Todo transcurre con normalidad.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'luna_llena',  'nombre' => 'Noche de luna llena',     'ico' => 'ent-luna-llena', 'efecto' => 'Visibilidad nocturna perfecta. Bonificacion a tiradas nocturnas.', 'tone' => 'great'),
    );
}

/* ── MESA: ENCUENTRO (durante la mision) ───────────────────────── */

function ope_oraculo_mision_mesa_encuentro()
{
    return array(
        array('max' => 5,   'key' => 'bestia',      'nombre' => 'Bestia salvaje',         'ico' => 'enc-bestia',     'efecto' => 'Criatura nativa de la isla ataca. Combate obligatorio.', 'tone' => 'crit'),
        array('max' => 14,  'key' => 'emboscada',   'nombre' => 'Emboscada tendida',      'ico' => 'enc-emboscada',  'efecto' => 'Alguien esperaba al grupo. Iniciativa del enemigo.', 'tone' => 'bad'),
        array('max' => 24,  'key' => 'rivales',     'nombre' => 'Grupo rival',            'ico' => 'enc-rivales',    'efecto' => 'Otro grupo quiere el mismo objetivo. Confrontacion o carrera.', 'tone' => 'bad'),
        array('max' => 36,  'key' => 'autoridad',   'nombre' => 'Intervencion de la ley',  'ico' => 'enc-autoridad',  'efecto' => 'Marines o guardias locales interrogan al grupo.', 'tone' => 'warn'),
        array('max' => 48,  'key' => 'informante',  'nombre' => 'Informante inesperado',  'ico' => 'enc-informante', 'efecto' => 'Un lugareno ofrece un dato clave... por un precio.', 'tone' => 'neutral'),
        array('max' => 58,  'key' => 'distraccion', 'nombre' => 'Distraccion fortuita',   'ico' => 'enc-distraccion','efecto' => 'Algo desvia la atencion de los guardias. Ventana de oportunidad.', 'tone' => 'good'),
        array('max' => 68,  'key' => 'viajero',     'nombre' => 'Viajero misterioso',     'ico' => 'enc-viajero',    'efecto' => 'Forastero con informacion de otras islas.', 'tone' => 'neutral'),
        array('max' => 78,  'key' => 'niño',        'nombre' => 'Niño curioso',           'ico' => 'enc-nino',       'efecto' => 'Un crio local ha visto algo importante.', 'tone' => 'good'),
        array('max' => 90,  'key' => 'contacto',    'nombre' => 'Contacto esperando',     'ico' => 'enc-contacto',   'efecto' => 'Alguien afable ofrece ayuda sin condiciones.', 'tone' => 'great'),
        array('max' => 100, 'key' => 'vacio',       'nombre' => 'Camino despejado',       'ico' => 'enc-vacio',      'efecto' => 'Sin encuentros fortuitos. El grupo avanza sin interrupcion.', 'tone' => 'neutral'),
    );
}

/* ── MESA: ALIADO (posible ayuda) ──────────────────────────────── */

function ope_oraculo_mision_mesa_aliado()
{
    return array(
        array('max' => 8,   'key' => 'mentor',      'nombre' => 'Viejo maestro',          'ico' => 'ali-mentor',     'efecto' => 'Experto retirado conoce los secretos de la zona.', 'tone' => 'great'),
        array('max' => 18,  'key' => 'desertor',    'nombre' => 'Desertor arrepentido',   'ico' => 'ali-desertor',   'efecto' => 'Antiguo miembro de una faccion enemiga quiere redimirse.', 'tone' => 'good'),
        array('max' => 30,  'key' => 'medico',      'nombre' => 'Medico local',           'ico' => 'ali-medico',     'efecto' => 'Ofrece curacion y suministros a cambio de proteccion.', 'tone' => 'good'),
        array('max' => 42,  'key' => 'tabernero',   'nombre' => 'Tabernero bien informado','ico'=> 'ali-tabernero',  'efecto' => 'Sabe todos los chismes de la isla. Pista gratuita.', 'tone' => 'good'),
        array('max' => 55,  'key' => 'artesano',    'nombre' => 'Artesano habilidoso',    'ico' => 'ali-artesano',   'efecto' => 'Puede reparar o fabricar equipo.', 'tone' => 'neutral'),
        array('max' => 68,  'key' => 'mensajero',   'nombre' => 'Mensajero apresurado',   'ico' => 'ali-mensajero',  'efecto' => 'Lleva un mensaje urgente relacionado con la mision.', 'tone' => 'neutral'),
        array('max' => 80,  'key' => 'animal',      'nombre' => 'Animal guia',            'ico' => 'ali-animal',     'efecto' => 'Un animal local muestra el camino.', 'tone' => 'neutral'),
        array('max' => 92,  'key' => 'marinero',    'nombre' => 'Marinero retirado',      'ico' => 'ali-marinero',   'efecto' => 'Conoce las mareas y escondites costeros.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'sin_aliado',  'nombre' => 'Sin aliados a la vista', 'ico' => 'ali-nada',       'efecto' => 'Nadie relevante se cruza en el camino este dia.', 'tone' => 'neutral'),
    );
}

/* ── MESA: COMPLICACION (giro o contratiempo) ──────────────────── */

function ope_oraculo_mision_mesa_complicacion()
{
    return array(
        array('max' => 6,   'key' => 'traicion',    'nombre' => 'Traicion desde dentro',  'ico' => 'com-traicion',   'efecto' => 'Alguien en quien confiaban vende la informacion.', 'tone' => 'crit'),
        array('max' => 14,  'key' => 'cambio',      'nombre' => 'Cambio de planes',       'ico' => 'com-cambio',     'efecto' => 'El objetivo ya no esta donde debia. La mision se complica.', 'tone' => 'bad'),
        array('max' => 24,  'key' => 'refuerzos',   'nombre' => 'Refuerzos enemigos',     'ico' => 'com-refuerzos',  'efecto' => 'Llegan mas enemigos de los previstos.', 'tone' => 'bad'),
        array('max' => 36,  'key' => 'clima_ad',    'nombre' => 'Cambio climatico brusco','ico'=> 'com-clima',       'efecto' => 'El tiempo empeora de golpe. La ruta se vuelve peligrosa.', 'tone' => 'warn'),
        array('max' => 48,  'key' => 'testigo',     'nombre' => 'Testigo inoportuno',     'ico' => 'com-testigo',    'efecto' => 'Un civil presencia algo que no debia. Hay que decidir que hacer.', 'tone' => 'warn'),
        array('max' => 60,  'key' => 'cerrado',     'nombre' => 'Acceso bloqueado',       'ico' => 'com-cerrado',    'efecto' => 'La entrada principal esta sellada. Buscar ruta alternativa.', 'tone' => 'warn'),
        array('max' => 72,  'key' => 'despiste',    'nombre' => 'Pista falsa',            'ico' => 'com-despiste',   'efecto' => 'La pista lleva a un callejon sin salida.', 'tone' => 'neutral'),
        array('max' => 84,  'key' => 'contratiempo','nombre' => 'Contratiempo menor',     'ico' => 'com-menor',      'efecto' => 'Un retraso molesto pero superable.', 'tone' => 'neutral'),
        array('max' => 94,  'key' => 'oportunidad', 'nombre' => 'Giro afortunado',        'ico' => 'com-oportunidad','efecto' => 'Lo que parecia un problema abre una nueva via.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'sin_giro',    'nombre' => 'Sin complicaciones',     'ico' => 'com-nada',       'efecto' => 'Todo marcha segun lo previsto.', 'tone' => 'good'),
    );
}

/* ── MESA: REVELACION (descubrimiento o pista) ─────────────────── */

function ope_oraculo_mision_mesa_revelacion()
{
    return array(
        array('max' => 8,   'key' => 'secreto',     'nombre' => 'Secreto enterrado',      'ico' => 'rev-secreto',    'efecto' => 'Descubren algo que cambia el proposito de la mision.', 'tone' => 'great'),
        array('max' => 18,  'key' => 'mapa_oculto', 'nombre' => 'Mapa oculto',            'ico' => 'rev-mapa',       'efecto' => 'Plano secreto de la zona con rutas alternativas.', 'tone' => 'good'),
        array('max' => 30,  'key' => 'diario',      'nombre' => 'Diario revelador',       'ico' => 'rev-diario',     'efecto' => 'Bitacora con informacion comprometedora.', 'tone' => 'good'),
        array('max' => 42,  'key' => 'testigo_clave','nombre'=> 'Testigo clave aparece',   'ico' => 'rev-testigo',    'efecto' => 'Alguien con la pieza que falta del puzzle.', 'tone' => 'good'),
        array('max' => 55,  'key' => 'objeto',      'nombre' => 'Objeto inesperado',      'ico' => 'rev-objeto',     'efecto' => 'Encuentran algo que no esperaban. Valor narrativo.', 'tone' => 'neutral'),
        array('max' => 68,  'key' => 'rumor',       'nombre' => 'Rumor en la taberna',    'ico' => 'rev-rumor',      'efecto' => 'Las conversaciones locales revelan un dato util.', 'tone' => 'neutral'),
        array('max' => 80,  'key' => 'conexion',    'nombre' => 'Conexion inesperada',    'ico' => 'rev-conexion',   'efecto' => 'La mision se relaciona con otra trama del foro.', 'tone' => 'good'),
        array('max' => 92,  'key' => 'vision',      'nombre' => 'Vision o corazonada',    'ico' => 'rev-vision',     'efecto' => 'Intuicion o Haki de Observacion muestra un camino.', 'tone' => 'good'),
        array('max' => 100, 'key' => 'sin_rev',     'nombre' => 'Nada nuevo bajo el sol', 'ico' => 'rev-nada',       'efecto' => 'No hay revelaciones especiales este dia.', 'tone' => 'neutral'),
    );
}

/* ── MODIFICADORES POR MACRO-MAR (herencia del viaje) ──────────── */

function ope_oraculo_mision_mods_macro($macro)
{
    $map = array(
        'east_blue'  => array('entorno' => 5, 'encuentro' => 5, 'aliado' => 0, 'complicacion' => 5, 'revelacion' => -5),
        'west_blue'  => array('entorno' => 3, 'encuentro' => 3, 'aliado' => 0, 'complicacion' => 2, 'revelacion' => -3),
        'north_blue' => array('entorno' => 0, 'encuentro' => 0, 'aliado' => -3, 'complicacion' => 0, 'revelacion' => 0),
        'south_blue' => array('entorno' => 0, 'encuentro' => 0, 'aliado' => 2,  'complicacion' => -3, 'revelacion' => 0),
        'paradise'   => array('entorno' => -5, 'encuentro' => -5, 'aliado' => -5, 'complicacion' => -5, 'revelacion' => 5),
        'new_world'  => array('entorno' => -12,'encuentro' => -10,'aliado' => -8, 'complicacion' => -10,'revelacion' => 10),
        'calm_belt'  => array('entorno' => -15,'encuentro' => -5, 'aliado' => -10,'complicacion' => -5, 'revelacion' => 5),
        'red_line'   => array('entorno' => -20,'encuentro' => -10,'aliado' => -15,'complicacion' => -10,'revelacion' => 15),
    );
    return isset($map[$macro]) ? $map[$macro] : array('entorno' => 0, 'encuentro' => 0, 'aliado' => 0, 'complicacion' => 0, 'revelacion' => 0);
}

/* ── LOOKUP D100 ───────────────────────────────────────────────── */

function ope_oraculo_mision_lookup(array $mesa, $roll)
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

/**
 * Genera el oraculo completo de una mision: 5 cartas.
 *
 * @param int    $peligrosidad   1-5 de la mision
 * @param string $macro_dominante Macro-mar de la isla (east_blue, etc.)
 * @return array Con 'cartas' y 'narrativa'
 */
function ope_oraculo_mision_generar($peligrosidad, $macro_dominante = '')
{
    $macro  = (string)$macro_dominante;
    $macro_mods = ope_oraculo_mision_mods_macro($macro);

    $mesas = array(
        'entorno'       => ope_oraculo_mision_mesa_entorno(),
        'encuentro'     => ope_oraculo_mision_mesa_encuentro(),
        'aliado'        => ope_oraculo_mision_mesa_aliado(),
        'complicacion'  => ope_oraculo_mision_mesa_complicacion(),
        'revelacion'    => ope_oraculo_mision_mesa_revelacion(),
    );

    $cartas = array();
    $any_natural_95 = false;

    // Peligrosidad actua como malus: cuanto mas alto, peores tiradas
    // 1 -> -2, 2 -> -5, 3 -> -10, 4 -> -15, 5 -> -20 (penalizacion base por peligrosidad)
    $penalizacion = array(1 => -2, 2 => -5, 3 => -10, 4 => -15, 5 => -20);
    $pen_base = $penalizacion[(int)$peligrosidad] ?? -5;

    foreach ($mesas as $key => $mesa) {
        $raw = random_int(1, 100);
        $mod_macro = (int)($macro_mods[$key] ?? 0);
        // entorno/encuentro/complicacion: negativo = peor (baja roll). aliado/revelacion: positivo = mejor (sube roll).
        if (in_array($key, array('aliado', 'revelacion'), true)) {
            $adj = max(1, min(100, $raw + $mod_macro + (int)abs($pen_base / 2)));
        } else {
            $adj = max(1, min(100, $raw + $pen_base + $mod_macro));
        }

        $hit = ope_oraculo_mision_lookup($mesa, $adj);
        $cartas[$key] = array(
            'roll'     => $raw,
            'roll_adj' => $adj,
            'mod'      => ($key === 'aliado' || $key === 'revelacion') ? $mod_macro + (int)abs($pen_base / 2) : $pen_base + $mod_macro,
            'key'      => (string)($hit['key'] ?? ''),
            'nombre'   => (string)($hit['nombre'] ?? ''),
            'ico'      => (string)($hit['ico'] ?? ''),
            'efecto'   => (string)($hit['efecto'] ?? ''),
            'tone'     => (string)($hit['tone'] ?? 'neutral'),
        );
        if ($raw >= 95) $any_natural_95 = true;
    }

    $narrativa = '';
    $e  = $cartas['entorno']['nombre'] ?? 'condiciones impredecibles';
    $en = $cartas['encuentro']['nombre'] ?? 'silencio sospechoso';
    $c  = $cartas['complicacion']['nombre'] ?? 'sin contratiempos';
    $narrativa = "La isla presenta {$e}. En el camino surge {$en}. "
               . "La mision se tuerce con {$c}.";

    if (!empty($cartas['aliado']['nombre']) && $cartas['aliado']['key'] !== 'sin_aliado') {
        $narrativa .= " Un posible aliado: {$cartas['aliado']['nombre']}.";
    }
    if (!empty($cartas['revelacion']['nombre']) && $cartas['revelacion']['key'] !== 'sin_rev') {
        $narrativa .= " Se revela {$cartas['revelacion']['nombre']}.";
    }

    return array('cartas' => $cartas, 'narrativa' => $narrativa);
}

/**
 * Resumen del oraculo para alimentar la IA (formato texto).
 */
function ope_oraculo_mision_resumen(array $oraculo)
{
    $map  = array('entorno', 'encuentro', 'aliado', 'complicacion', 'revelacion');
    $bits = array();
    foreach ($map as $k) {
        if (!empty($oraculo['cartas'][$k]['nombre'])) {
            $bits[] = $k . ': ' . $oraculo['cartas'][$k]['nombre'];
        }
    }
    return $bits ? implode(' | ', $bits) : 'El oraculo no mostro cartas destacables.';
}
