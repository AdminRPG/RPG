<?php
/**
 * One Piece: Eternal · Catálogo de Vocaciones (Clases Bélicas + Oficios) y Armas.
 * ----------------------------------------------------------------
 * Diseño v4 (sin Sistema Eternal de árboles). Cada PJ elige en CREATE:
 *   1 Clase Bélica + hasta 2 Oficios + 1 arma PROHIBIDA según la Clase.
 * Las Clases/Oficios NUNCA dan activas con coste EN/PA: solo Mecánicos pasivos,
 * Permisos de Pool y desbloqueos narrativos. Cadencia única:
 *   hitos fijos 1·3·5·15·25·35·45  y  elecciones 10·20·30·40·50 (30 = Arquetipo).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Hitos de bonificación por Clase/Oficio.
 * tipo: 'fijo' = se concede siempre al alcanzar el nivel.
 *       'eleccion' = se elige una de las dos opciones al alcanzar el nivel.
 *       'arquetipo' = nivel 30, segunda Clase (opcional).
 */
function ope_rol_voc_cadencia()
{
    return array(
        1  => array('tipo' => 'fijo'),
        3  => array('tipo' => 'fijo'),
        5  => array('tipo' => 'fijo'),
        10 => array('tipo' => 'eleccion'),
        15 => array('tipo' => 'fijo'),
        20 => array('tipo' => 'eleccion'),
        25 => array('tipo' => 'fijo'),
        30 => array('tipo' => 'arquetipo'),
        35 => array('tipo' => 'fijo'),
        40 => array('tipo' => 'eleccion'),
        45 => array('tipo' => 'fijo'),
        50 => array('tipo' => 'eleccion'),
    );
}

/*
 * Las 9 Clases Bélicas.
 *   prim = stat primario · sec = secundario (stats base del foro).
 *   pool = lista de tags con los que el jugador construye técnicas propias.
 *   armas = ids de armas permitidas (1–2, nunca "todas").
 *   hitos = [ nivel => 'bonificación' ] para fijos / [ 'eleccion' => [...] ] para elecciones.
 */
function ope_rol_clases()
{
    return array(
        'guerrero' => array(
            'nombre'   => 'Guerrero',
            'prim'     => 'FUE',
            'sec'      => 'RES',
            'filosofia'=> 'Fuerza bruta y dominio de las armas pesadas de impacto. Rompe guardias y sostiene la vanguardia.',
            'pool'     => array('Daño', 'Rotura de Guardia', 'Derribo', 'Mitigación'),
            'armas'    => array('espada_dos_manos', 'hacha', 'hacha_dos_manos', 'maza', 'martillo', 'martillo_dos_manos', 'kanabo', 'mangual', 'escudo', 'lanza', 'tridente', 'alabarda', 'guantes'),
            'hitos'    => array(
                1  => 'Empuña armas pesadas o contundentes sin penalizador de agilidad.',
                3  => '+1 daño con armas contundentes o pesadas.',
                5  => 'Mitigación +1 plana al empuñar escudo o arma pesada.',
                10 => array('eleccion' => array(
                    'Rompeguardias' => 'sus impactos ignoran 10% de la mitigación o escudo rival.',
                    'Embate'        => 'carga contra la línea enemiga aplicando Derribo.',
                )),
                15 => 'Sus técnicas con "Rotura de Guardia" duran 1 turno adicional.',
                20 => array('eleccion' => array(
                    'Postura Firme'   => 'no puede ser derribado mientras mantenga la guardia.',
                    'Furia Impactante' => '+1 a la potencia de impacto al romper guardia.',
                )),
                25 => 'Piel de batalla: +1 a soportar / resistir.',
                30 => array('arquetipo' => true),
                35 => '1×/escena ignora un estado de aturdimiento o derribo físico.',
                40 => array('eleccion' => array(
                    'Impacto Demoledor' => 'sus ataques ignoran la cobertura física.',
                    'Fuerza Imparable'  => 'descuento de Pool en un golpe pesado.',
                )),
                45 => 'Señor de la guerra: reputación entre combatientes de vanguardia.',
                50 => array('eleccion' => array(
                    'Capstone: Rompefortalezas' => 'potencia capaz de quebrar defensas y batirse en la vanguardia.',
                )),
            ),
        ),
        'luchador' => array(
            'nombre'   => 'Luchador',
            'prim'     => 'FUE',
            'sec'      => 'RES',
            'filosofia'=> 'Combate cuerpo a cuerpo desarmado. Vanguardia nacida para pelear sin armas.',
            'pool'     => array('Daño', 'Derribo', 'Control de Movimiento', 'Coste de EN'),
            'armas'    => array('guantes', 'garras', 'punteras', 'tonfa', 'nunchaku', 'bo', 'escudo'),
            'hitos'    => array(
                1  => 'Peleas desarmado sin penalización.',
                3  => '+1 daño cuerpo a cuerpo por tramo.',
                5  => 'Mitigación +1 plana.',
                10 => array('eleccion' => array(
                    'Demolición' => 'sus golpes ignoran 10% de mitigación rival.',
                    'Estilo Fluido' => 'encadena golpes sin penalizar su Agilidad.',
                )),
                15 => 'Sus técnicas con "Derribo" duran 1:turno adicional.',
                20 => array('eleccion' => array(
                    'Impetuoso' => '+1 de movimiento las rondas que no ataca.',
                    'Sólido'    => 'sus presas ignoran la esquiva del rival.',
                )),
                25 => 'Cuerpo de acero: +1 a soportar / resistir.',
                30 => array('arquetipo' => true),
                35 => '+5% de PV máximo.',
                40 => array('eleccion' => array(
                    'Inquebrantable' => 'ignora una duración de derribo/estado.',
                    'Ventobruto' => 'descuento de Pool en un golpe.',
                )),
                45 => 'Coloso: su cuerpo escala frente a adversarios fuera de rango.',
                50 => array('eleccion' => array(
                    'Capstone: Coloso' => 'capaz de medirse con un Vicealmirante.',
                )),
            ),
        ),
        'espadachin' => array(
            'nombre'   => 'Espadachín',
            'prim'     => 'AGI',
            'sec'      => 'FUE',
            'filosofia'=> 'El filo que decide duelos. Relación personal con su hoja.',
            'pool'     => array('Daño', 'Sangrado', 'Aturdimiento', 'Contraataque'),
            'armas'    => array('katana', 'espada', 'espada_dos_manos', 'bokken', 'tanto', 'hacha'),
            'hitos'    => array(
                1  => 'Para o desciende un ataque de cuerpo a cuerpo sin gastar PA (1×/combate).',
                3  => 'Sus cortes causan +1 de daño.',
                5  => 'En el primer intercambio de un duelo gana +1 de control efectivo.',
                10 => array('eleccion' => array(
                    'Corte Único' => 'Un corte perfecto sin señal previa.',
                    'Multi-hoja'  => 'Empuña dos armas sin penalización de coordinación.',
                )),
                15 => 'El Sangrado de sus técnicas dura 1 turno más.',
                20 => array('eleccion' => array(
                    'Ojo de la Hoja' => 'Detecta una apertura 1×/turno sin coste.',
                    'Resolución'     => 'Bloquea varios ataques juntos como una sola defensa.',
                )),
                25 => 'Sus técnicas de espada cuestan 1 de EN menos en el primer uso.',
                30 => array('arquetipo' => true),
                35 => '1×/escena su arma no muestra trayectoria (PER no la anticipa).',
                40 => array('eleccion' => array(
                    'Guardia Inquebrantable' => 'No cae a una posición rota.',
                    'Cesión'                 => 'Transfiere un bono de su Pool a un aliado.',
                )),
                45 => 'Sus cortes marcan duelos de honor: reputación entre espadachines.',
                50 => array('eleccion' => array(
                    'Capstone: Gran Hoja' => 'Se codea con los grandes espadachines.',
                )),
            ),
        ),
        'tirador' => array(
            'nombre'   => 'Tirador',
            'prim'     => 'PER',
            'sec'      => 'AGI',
            'filosofia'=> 'Distancia precisa. Gana el terreno antes de que el rival llegue.',
            'pool'     => array('Daño', 'Información', 'Alcance extendido', 'Marcado'),
            'armas'    => array('arco', 'arco_largo', 'ballesta', 'rifle', 'escopeta', 'shuriken', 'tirachinas', 'pistola'),
            'hitos'    => array(
                1  => 'Ignora los penalizadores menores de distancia en el primer disparo.',
                3  => '+1 a puntería (PER) al apuntar.',
                5  => 'Al abrir, ve las posiciones de los rivales antes que el resto.',
                10 => array('eleccion' => array(
                    'Francotirador'   => 'Disparo que ignora la cobertura.',
                    'Tiro de Cobertura'=> 'Satura una zona con fuego.',
                )),
                15 => 'Alcance extendido +1 tramo.',
                20 => array('eleccion' => array(
                    'Ratería' => 'Dispara en movimiento sin castigo.',
                    'Zona de Silencio' => 'Niega la esquiva a quien dispara.',
                )),
                25 => 'Ojo de halcón: +1 percepción.',
                30 => array('arquetipo' => true),
                35 => '1×/escena dispara sin revelar su posición.',
                40 => array('eleccion' => array(
                    'Tiro Certero' => 'Reduce 20% de PV a un blanco fijo.',
                    'Ráfaga Táctica' => 'Marca dos objetivos sin coste extra.',
                )),
                45 => 'Enum arma desde el barco sin pena al moverse.',
                50 => array('eleccion' => array(
                    'Capstone: Gran Tiro' => 'Entre los grandes de largo alcance.',
                )),
            ),
        ),
        'artillero' => array(
            'nombre'   => 'Artillero',
            'prim'     => 'FUE',
            'sec'      => 'PER',
            'filosofia'=> 'Armamento pesado y destrucción calculada.',
            'pool'     => array('Daño', 'Área', 'Ignora cobertura', 'Energía externa'),
            'armas'    => array('escopeta', 'rifle', 'ballesta'),
            'hitos'    => array(
                1  => 'Prepara un proyectil de gran calibre sin tiempo extra.',
                3  => '+1 de daño en Área.',
                5  => 'Desactiva sus explosivos sin exponerse.',
                10 => array('eleccion' => array(
                    'Demolitorio'  => 'Daño destructivo en ruinas.',
                    'Artillería Naval' => 'Rendimiento reforzado sobre el barco.',
                )),
                15 => 'Área cuesta 1 PA menos.',
                20 => array('eleccion' => array(
                    'Zona de Riesgo' => 'Niega la cobertura en su zona.',
                    'Apoyos' => 'Coordina varias piezas en una sola acción.',
                )),
                25 => 'Su energía externa recalga mejor.',
                30 => array('arquetipo' => true),
                35 => '1×/escena un explosivo no falla por causas externas.',
                40 => array('eleccion' => array(
                    'Ejecución Espiral' => 'Área +1.',
                    'Carga Simetría'    => '+10% daño en el punto débil.',
                )),
                45 => 'Reputación de artillero de combate naval.',
                50 => array('eleccion' => array(
                    'Capstone: Fuego que Decide' => 'Potencia de fuego capaz de inclinar un combate naval.',
                )),
            ),
        ),
        'duelista' => array(
            'nombre'   => 'Duelista',
            'prim'     => 'AGI',
            'sec'      => 'INT',
            'filosofia'=> 'Tempo, esgrima técnica y castigo inmediato del error rival.',
            'pool'     => array('Daño', 'Contraataque', 'Derribo', 'Requiere condición'),
            'armas'    => array('estoque', 'espada', 'katana', 'bokken', 'daga', 'kusarigama', 'pistola', 'tonfa', 'nunchaku', 'bo', 'latigo', 'lanza', 'tridente'),
            'hitos'    => array(
                1  => 'Reduce el daño del primer golpe recibido por escena.',
                3  => 'Contraataques +1 daño.',
                5  => 'Contraataque se declara con 1 PA menos.',
                10 => array('eleccion' => array(
                    'Contragolpe'       => 'Castigo directo tras desvío.',
                    'Presión Constante' => 'Provoca el error del adversario mediante fintas.',
                )),
                15 => 'Contraataque no pierde guardia.',
                20 => array('eleccion' => array(
                    'Ley del Intercambio' => 'Aplica intercambio pleno en ventaja.',
                    'Un Objetivo'         => 'Prevé el tropiezo del rival.',
                )),
                25 => 'Jugada de duelo: cierre de combate más sólido.',
                30 => array('arquetipo' => true),
                35 => '1×/escena ninguna acción puede romper su guardia.',
                40 => array('eleccion' => array(
                    'Guardia Alta' => 'Guarda y contraataca en el mismo movimiento.',
                    'Inevitable'   => 'En 1vs1 gana bonus de duelo.',
                )),
                45 => 'Reputación de duelo de honor.',
                50 => array('eleccion' => array(
                    'Capstone: Imbatible' => 'Casi imbatible en combate directo único.',
                )),
            ),
        ),
        'domador' => array(
            'nombre'   => 'Domador',
            'prim'     => 'CAR',
            'sec'      => 'FUE',
            'filosofia'=> 'Vínculo con bestias de combate y armas de guía, látigo o mando.',
            'pool'     => array('Daño', 'Buff', 'Control de Movimiento'),
            'armas'    => array('latigo', 'lanza', 'tridente', 'bo', 'arco'),
            'hitos'    => array(
                1  => array('eleccion' => array(
                    'Vínculo Bestial'   => 'Bestia o criatura animal externa con PV/EN/PA propios criada en lealtad natural.',
                    'Dominio Esclavista' => 'NPC menor subordinado (miembro de una raza del foro) sometido como sirviente de combate.',
                )),
                3  => '+1 al buff de tu bestia o subordinado.',
                5  => 'Vínculo de Mando: la criatura o sirviente no huye ni rompe la formación mientras estás en combate.',
                10 => array('eleccion' => array(
                    'Manada'           => 'Coordina varias bestias o sirvientes como un solo bloque.',
                    'Bestia de Guerra' => 'Potencia una sola criatura colosal al límite.',
                )),
                15 => 'Vínculo táctico de ataque conjunto.',
                20 => array('eleccion' => array(
                    'Líder de Manada'  => 'Manada o cuadrilla ampliada.',
                    'Furia Bestial'    => 'Estado de combate superior de la criatura principal.',
                )),
                25 => 'Tus buff de mando/bestia duran 1 turno más.',
                30 => array('arquetipo' => true),
                35 => 'Voz de mando: orden compleja en una sola acción.',
                40 => array('eleccion' => array(
                    'Avatar' => 'Criatura inmune a aturdimiento.',
                    'Dominio Conjunto' => 'Golpe conjunto en perfecta sincronía.',
                )),
                45 => 'Vínculo o dominio indestructible.',
                50 => array('eleccion' => array(
                    'Capstone: Señor de las Bestias' => 'Criatura o horda al máximo de su condición.',
                )),
            ),
        ),
        'estratega' => array(
            'nombre'   => 'Estratega',
            'prim'     => 'INT',
            'sec'      => 'CAR',
            'filosofia'=> 'Lectura del campo de batalla, órdenes de mando y apoyo táctico.',
            'pool'     => array('Buff', 'Debuff', 'Información', 'Área'),
            'armas'    => array('ballesta', 'pistola', 'tirachinas'),
            'hitos'    => array(
                1  => 'Al abrir combate, información de posiciones 1 post antes.',
                3  => 'Buff a aliados bajo tu orden.',
                5  => 'Coordina 1 aliado con una orden.',
                10 => array('eleccion' => array(
                    'Comando'       => 'Amplifica buffs de escuadra.',
                    'Contra-táctico' => 'Aplica debuffs y detecta debilidades rivales.',
                )),
                15 => 'Órdenes de mando con +1 tramo de alcance.',
                20 => array('eleccion' => array(
                    'Doble Orden'  => 'Órdenes a 2 aliados simultáneamente.',
                    'Previsión'    => 'Prevé la jugada del rival.',
                )),
                25 => 'Buffs tácticos no se pierden al recibir daño.',
                30 => array('arquetipo' => true),
                35 => 'Dominio del plan: prevé la intención del rival.',
                40 => array('eleccion' => array(
                    'Mando Colectivo' => 'Ronda extra de orden.',
                    'Punto Débil'     => 'Identifica la debilidad del enemigo.',
                )),
                45 => 'Reputación de gran estratega.',
                50 => array('eleccion' => array(
                    'Capstone: Mente Maestra' => 'Plan que decide combates sin dar el golpe final.',
                )),
            ),
        ),
        'asesino' => array(
            'nombre'   => 'Asesino',
            'prim'     => 'AGI',
            'sec'      => 'PER',
            'filosofia'=> 'Ocultación, movimiento silencioso y ejecución letal.',
            'pool'     => array('Daño', 'Sin Tell', 'Ocultación', 'Condición previa'),
            'armas'    => array('daga', 'tanto', 'kusarigama', 'garras', 'shuriken', 'punteras', 'espada'),
            'hitos'    => array(
                1  => 'Mueves sin hacer ruido perceptible.',
                3  => 'Sin Tell en tus técnicas.',
                5  => 'Sigilo activo en escena abierta.',
                10 => array('eleccion' => array(
                    'Sombra'  => 'Dominio total de la ocultación.',
                    'Verdugo' => 'Rematador letal a objetivos debilitados o marcados.',
                )),
                15 => 'Ocultación se mantiene tras ejecutar acción.',
                20 => array('eleccion' => array(
                    'Sombra Absoluta' => 'Sin pistas de paso o rastro.',
                    'Sentencia'       => 'Ejecución sobre blancos marcados.',
                )),
                25 => 'Ocultación mantenible en combate activo.',
                30 => array('arquetipo' => true),
                35 => '1×/escena se mueve y ataca sin ser detectado.',
                40 => array('eleccion' => array(
                    'Regreso a la Sombra' => 'Vuelve a sigilo inmediatamente.',
                    'Marca de Muerte'     => 'Aplica Marcado automático.',
                )),
                45 => 'Reputación de asesino silencioso.',
                50 => array('eleccion' => array(
                    'Capstone: Sombra Letal' => 'Resolución de objetivo sin opción de réplica.',
                )),
            ),
        ),
    );
}

/**
 * Las 7 Oficios. Misma cadencia que las Clases. Lapecifics:
 * 1 Clase + hasta 2 Oficios activos por PJ.
 */
function ope_rol_oficios()
{
    return array(
        'cocinero' => array(
            'nombre' => 'Cocinero',
            'prim'   => 'INT',
            'sec'    => 'RES',
            'pool'   => array('Buff', 'Curación', 'Información'),
            'armas'  => array(), // ningún arma: los Oficios no restringen arma
            'desc'   => 'Sustento y Cocina de Combate. Alimenta en descanso, buffs de pel y cocina ofensiva.',
        ),
        'medico' => array(
            'nombre' => 'Médico',
            'prim'   => 'INT',
            'sec'    => 'PER',
            'pool'   => array('Curación', 'Información', 'Veneno'),
            'armas'  => array(),
            'desc'   => 'Cirujano y farmacéutico. Estabiliza en combate, antídotos y venenos.',
        ),
        'navegante' => array(
            'nombre' => 'Navegante',
            'prim'   => 'INT',
            'sec'    => 'PER',
            'pool'   => array('Información', 'Clima', 'Control del barco'),
            'armas'  => array(),
            'desc'   => 'Meteorología, cartografía y navegación. Pilota, lee el mar y avisa de tormentas.',
        ),
        'carpintero' => array(
            'nombre' => 'Carpintero',
            'prim'   => 'FUE',
            'sec'    => 'INT',
            'pool'   => array('Reparación', 'Modular'),
            'armas'  => array(),
            'desc'   => 'Reparación e ingeniería. Salva el barco en combate naval y construye.',
        ),
        'armero' => array(
            'nombre' => 'Armero / Artesano',
            'prim'   => 'FUE',
            'sec'    => 'AGI',
            'pool'   => array('Buff (equipo)', 'Daño', 'Información'),
            'armas'  => array(),
            'desc'   => 'Forja y artefactos. Aplica o cambia tags de arma y modifica el equipo.',
        ),
        'arqueologo' => array(
            'nombre' => 'Arqueólogo / Historiador',
            'prim'   => 'INT',
            'sec'    => 'CAR',
            'pool'   => array('Información', 'Marcado', 'Buff'),
            'armas'  => array(),
            'desc'   => 'Lee la escritura antigua, encuentra pistas ocultas y estudia Poneglyphs.',
        ),
        'musico' => array(
            'nombre' => 'Músico',
            'prim'   => 'CAR',
            'sec'    => 'RES',
            'pool'   => array('Buff', 'Información', 'Debuff (música)'),
            'armas'  => array(),
            'desc'   => 'Moral de la tripulación y señales. Afecta la moral colectiva y comunica a distancia.',
        ),
    );
}

/**
 * Catálogo canónico de Armas (33 armas reales con acceso compartido entre clases).
 * Cada arma define las clases que pueden equiparla.
 */
function ope_rol_armas_vocacionales()
{
    return array(
        'guantes'            => array('clases' => array('luchador', 'guerrero'), 'nombre' => 'Guantes / Guanteletes', 'escala' => array('FUE'), 'tags' => array('Derribo', 'Daño'), 'efecto' => 'Golpes directos y empujones marciales.'),
        'garras'             => array('clases' => array('luchador', 'asesino'), 'nombre' => 'Garras de acero / Nudilleras de garras', 'escala' => array('AGI', 'FUE'), 'tags' => array('Daño', 'Sangrado'), 'efecto' => 'Hojas cortas acopladas a las manos para desgarres rápidos.'),
        'punteras'           => array('clases' => array('luchador', 'asesino'), 'nombre' => 'Punteras / Calzado reforzado', 'escala' => array('AGI', 'FUE'), 'tags' => array('Daño', 'Combo'), 'efecto' => 'Patadas fluidas y ataques veloz de extremidades inferiores.'),
        'tonfa'              => array('clases' => array('luchador', 'duelista'), 'nombre' => 'Tonfas de combate', 'escala' => array('AGI', 'FUE'), 'tags' => array('Contraataque', 'Control'), 'efecto' => 'Defensa lateral y guardias de desvío rápido.'),
        'nunchaku'           => array('clases' => array('luchador', 'duelista'), 'nombre' => 'Nunchaku', 'escala' => array('AGI', 'FUE'), 'tags' => array('Combo', 'Daño'), 'efecto' => 'Inercia de golpes encadenados y ritmo ágil.'),
        'bo'                 => array('clases' => array('luchador', 'domador', 'duelista'), 'nombre' => 'Bastón Bo', 'escala' => array('AGI', 'FUE'), 'tags' => array('Control', 'Desvío'), 'efecto' => 'Alcance medio, barridos y desvíos de equilibrio.'),
        'espada'             => array('clases' => array('espadachin', 'duelista', 'asesino'), 'nombre' => 'Espada corta / Sable', 'escala' => array('AGI', 'FUE'), 'tags' => array('Corte', 'Sangrado'), 'efecto' => 'Corte rápido con equilibrio entre ataque y parada.'),
        'katana'             => array('clases' => array('espadachin', 'duelista'), 'nombre' => 'Katana', 'escala' => array('AGI', 'FUE'), 'tags' => array('Corte', 'Sangrado'), 'efecto' => 'Hoja curva tradicional para tajos limpios y precisos.'),
        'bokken'             => array('clases' => array('espadachin', 'duelista'), 'nombre' => 'Bokken (Espada de madera)', 'escala' => array('AGI', 'FUE'), 'tags' => array('Contundente', 'Control'), 'efecto' => 'Entrenamiento y duelos sin causar cortes mortales.'),
        'espada_dos_manos'   => array('clases' => array('espadachin', 'guerrero'), 'nombre' => 'Espada a dos manos / Mandoble', 'escala' => array('FUE', 'AGI'), 'tags' => array('Corte', 'Daño'), 'efecto' => 'Hoja pesada de gran envergadura y golpes demoledores.'),
        'estoque'            => array('clases' => array('duelista', 'espadachin'), 'nombre' => 'Estoque / Florete', 'escala' => array('AGI', 'INT'), 'tags' => array('Precisión', 'Contraataque'), 'efecto' => 'Estocadas finas en busca de aperturas defensivas.'),
        'tanto'              => array('clases' => array('asesino', 'espadachin'), 'nombre' => 'Tantō', 'escala' => array('AGI', 'PER'), 'tags' => array('Sin Tell', 'Corte'), 'efecto' => 'Daga oriental de tajo letal en distancias cortas.'),
        'daga'               => array('clases' => array('asesino', 'duelista'), 'nombre' => 'Daga / Cuchillo', 'escala' => array('AGI', 'PER'), 'tags' => array('Sin Tell', 'Sangrado'), 'efecto' => 'Arma corta y ligera para estocadas sigilosas.'),
        'kusarigama'         => array('clases' => array('asesino', 'duelista'), 'nombre' => 'Kusarigama (Hoz con cadena)', 'escala' => array('AGI', 'PER'), 'tags' => array('Control', 'Alcance', 'Corte'), 'efecto' => 'Hoz con cadena pesada para atar y cortar en movimiento.'),
        'hacha'              => array('clases' => array('guerrero', 'espadachin'), 'nombre' => 'Hacha de una mano', 'escala' => array('FUE', 'AGI'), 'tags' => array('Corte', 'Daño'), 'efecto' => 'Impacto cortante de peso frontal.'),
        'hacha_dos_manos'    => array('clases' => array('guerrero'), 'nombre' => 'Hacha a dos manos', 'escala' => array('FUE', 'RES'), 'tags' => array('Corte', 'Rotura de guardia'), 'efecto' => 'Tajos gigantescos capaces de partir escudos.'),
        'maza'               => array('clases' => array('guerrero', 'luchador'), 'nombre' => 'Maza de una mano / Garrote', 'escala' => array('FUE', 'RES'), 'tags' => array('Contundente', 'Rotura de guardia'), 'efecto' => 'Cabezal pesado para aplastar defensas.'),
        'martillo'           => array('clases' => array('guerrero'), 'nombre' => 'Martillo de guerra', 'escala' => array('FUE', 'RES'), 'tags' => array('Contundente', 'Aturdimiento'), 'efecto' => 'Impacto directo con cabeza de forja o piedra.'),
        'martillo_dos_manos'  => array('clases' => array('guerrero'), 'nombre' => 'Martillo pesado a dos manos', 'escala' => array('FUE', 'RES'), 'tags' => array('Contundente', 'Área', 'Derribo'), 'efecto' => 'Mazazo sísmico que hace retumbar el suelo.'),
        'kanabo'             => array('clases' => array('guerrero'), 'nombre' => 'Kanabō (Garrote tachonado)', 'escala' => array('FUE', 'RES'), 'tags' => array('Contundente', 'Daño pesado'), 'efecto' => 'Gran garrote de hierro tachonado de fuerza bruta.'),
        'mangual'            => array('clases' => array('guerrero'), 'nombre' => 'Mangual / Maza con cadena', 'escala' => array('FUE', 'AGI'), 'tags' => array('Contundente', 'Ignora cobertura'), 'efecto' => 'Esfera pesada con cadena que rodea los bloqueos.'),
        'escudo'             => array('clases' => array('guerrero', 'luchador'), 'nombre' => 'Escudo de combate', 'escala' => array('RES', 'FUE'), 'tags' => array('Bloqueo', 'Derribo'), 'efecto' => 'Protección sólida para embestir y frenar ataques.'),
        'lanza'              => array('clases' => array('guerrero', 'duelista', 'domador'), 'nombre' => 'Lanza', 'escala' => array('FUE', 'AGI'), 'tags' => array('Alcance', 'Perforación'), 'efecto' => 'Punta estilizada para mantener a raya al adversario.'),
        'tridente'           => array('clases' => array('guerrero', 'domador'), 'nombre' => 'Tridente', 'escala' => array('FUE', 'AGI'), 'tags' => array('Alcance', 'Control'), 'efecto' => 'Tres puntas para enganchar y controlar el arma o cuerpo rival.'),
        'alabarda'           => array('clases' => array('guerrero'), 'nombre' => 'Alabarda', 'escala' => array('FUE', 'RES'), 'tags' => array('Alcance', 'Corte', 'Perforación'), 'efecto' => 'Astil largo con hacha y lanza para combate de formación.'),
        'latigo'             => array('clases' => array('domador', 'duelista'), 'nombre' => 'Látigo', 'escala' => array('AGI', 'CAR'), 'tags' => array('Control', 'Enraizar'), 'efecto' => 'Tira de cuero o metal para envolver y dirigir a distancia.'),
        'arco'               => array('clases' => array('tirador', 'domador'), 'nombre' => 'Arco corto / recurvo', 'escala' => array('PER', 'AGI'), 'tags' => array('Distancia', 'Precisión'), 'efecto' => 'Disparo rápido e intuitivo a media/larga distancia.'),
        'arco_largo'         => array('clases' => array('tirador'), 'nombre' => 'Arco largo', 'escala' => array('PER', 'AGI'), 'tags' => array('Distancia', 'Alcance extendido'), 'efecto' => 'Tensión máxima para disparos de gran alcance.'),
        'ballesta'           => array('clases' => array('tirador', 'estratega', 'artillero'), 'nombre' => 'Ballesta', 'escala' => array('PER', 'FUE'), 'tags' => array('Distancia', 'Perforación'), 'efecto' => 'Mecanismo de tensión para virates de alta potencia.'),
        'shuriken'           => array('clases' => array('asesino', 'tirador'), 'nombre' => 'Shuriken / Arrojadizas', 'escala' => array('PER', 'AGI'), 'tags' => array('Sin Tell', 'Distancia'), 'efecto' => 'Projectiles metálicos pequeños de lanzamiento veloz.'),
        'tirachinas'         => array('clases' => array('tirador', 'estratega'), 'nombre' => 'Tirachinas', 'escala' => array('PER', 'AGI'), 'tags' => array('Distancia', 'Efecto especial'), 'efecto' => 'Lanza proyectiles variados (semillas, fuego, humo).'),
        'escopeta'           => array('clases' => array('artillero', 'tirador'), 'nombre' => 'Escopeta de mecha', 'escala' => array('PER', 'FUE'), 'tags' => array('Distancia', 'Área', 'Daño'), 'efecto' => 'Perdigonada de dispersión a corta/media distancia.'),
        'rifle'              => array('clases' => array('tirador', 'artillero'), 'nombre' => 'Rifle / Fusil', 'escala' => array('PER', 'FUE'), 'tags' => array('Distancia', 'Perforación'), 'efecto' => 'Fusil de cañón largo para disparos contundentes.'),
        'pistola'            => array('clases' => array('tirador', 'duelista', 'estratega'), 'nombre' => 'Pistola de chispa', 'escala' => array('PER', 'AGI'), 'tags' => array('Distancia', 'Instantánea'), 'efecto' => 'Arma de fuego corta para disparos de reacción rápida.'),
    );
}

/** Armas permitidas para una clase concreta (array id => arma). */
function ope_rol_armas_de_clase($clase)
{
    $todas = ope_rol_armas_vocacionales();
    $out = array();
    foreach ($todas as $aid => $arma) {
        $clases = isset($arma['clases']) ? (array)$arma['clases'] : (isset($arma['clase']) ? array($arma['clase']) : array());
        if (in_array($clase, $clases, true)) {
            $out[$aid] = $arma;
        }
    }
    return $out;
}

/** Hito(s) desbloqueado(s) en un nivel dado para una Clase/Oficio. */
function ope_rol_vocacion_hito($hitos, $nivel)
{
    if (isset($hitos[$nivel])) {
        return $hitos[$nivel];
    }
    return null;
}

/**
 * Obtiene los datos de vocaciones del personaje desde DB (o un fallback predeterminado).
 */
function ope_rol_pj_vocaciones($pid)
{
    global $db;
    $pid = (int)$pid;
    if ($pid <= 0 || !$db->table_exists('rol_pj_vocaciones')) {
        return array('clase' => '', 'oficios' => array(), 'arma' => '', 'elecciones' => array(), 'arquetipo_clase' => '');
    }

    $q = $db->simple_select('rol_pj_vocaciones', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q) > 0) {
        $row = $db->fetch_array($q);
        $oficios = @json_decode($row['oficios'] ?? '[]', true);
        $elecciones = @json_decode($row['elecciones'] ?? '{}', true);
        return array(
            'pid' => $pid,
            'clase' => (string)($row['clase'] ?? ''),
            'oficios' => is_array($oficios) ? $oficios : array(),
            'arma' => (string)($row['arma'] ?? ''),
            'elecciones' => is_array($elecciones) ? $elecciones : array(),
            'arquetipo_clase' => (string)($row['arquetipo_clase'] ?? ''),
        );
    }
    return array('pid' => $pid, 'clase' => '', 'oficios' => array(), 'arma' => '', 'elecciones' => array(), 'arquetipo_clase' => '');
}

/**
 * Guarda la elección de un hito opcional (nivel 10, 20, 40, 50).
 */
function ope_rol_vocacion_guardar_eleccion($pid, $nivel, $opcion)
{
    global $db;
    $pid = (int)$pid;
    $nivel = (int)$nivel;
    $opcion = trim((string)$opcion);
    if ($pid <= 0 || !$db->table_exists('rol_pj_vocaciones')) {
        return array('ok' => false, 'msg' => 'Personaje o tabla no encontrada.');
    }

    $data = ope_rol_pj_vocaciones($pid);
    $elecciones = $data['elecciones'];
    $elecciones[(string)$nivel] = $opcion;

    $db->update_query('rol_pj_vocaciones', array(
        'elecciones' => $db->escape_string(json_encode($elecciones, JSON_UNESCAPED_UNICODE))
    ), "pid = {$pid}");

    return array('ok' => true, 'msg' => 'Elección guardada correctamente.');
}

/**
 * Guarda la elección de la segunda clase (Arquetipo) para nivel 30+.
 */
function ope_rol_vocacion_guardar_arquetipo($pid, $segunda_clase)
{
    global $db;
    $pid = (int)$pid;
    $segunda_clase = trim((string)$segunda_clase);
    $clases = ope_rol_clases();

    if ($segunda_clase !== '' && !isset($clases[$segunda_clase])) {
        return array('ok' => false, 'msg' => 'Clase de arquetipo no válida.');
    }

    $data = ope_rol_pj_vocaciones($pid);
    if (!empty($data['clase']) && $data['clase'] === $segunda_clase) {
        return array('ok' => false, 'msg' => 'No puedes elegir tu clase primaria como segunda clase.');
    }

    $db->update_query('rol_pj_vocaciones', array(
        'arquetipo_clase' => $db->escape_string($segunda_clase)
    ), "pid = {$pid}");

    return array('ok' => true, 'msg' => 'Arquetipo guardado correctamente.');
}