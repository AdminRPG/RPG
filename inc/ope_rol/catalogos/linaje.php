<?php
if (!defined('IN_MYBB')) { die('Direct initialization of this file is not allowed.'); }

/**
 * Catálogo del Factor Linaje — One Piece: Eternal.
 * ------------------------------------------------
 * CAPA DE DATOS (pura): solo devuelve arrays. Sin SQL, sin HTML, sin validación.
 * Las reglas (PL suma cero, acceso por linaje, hibridación) viven en
 * inc/ope_rol/dominio/creacion.php. La UI vive en crear-personaje.php.
 *
 * Canon: I-Forge-Sistema/docs/01-PERSONAJE/FACTOR-LINAJE.md
 *   §3.3 Rasgos Raciales · §3.4 Rasgo Puro · §4.3 Rasgos Generales
 *   §4.4 Defectos · §4.5 Defectos de Hibridación · §6 Dotes Innatas
 *
 * Modelo: TODO se compra con Puntos de Linaje (PL). Los Rasgos suman +PL,
 * los Defectos suman −PL, y la ficha debe cerrar en 0 (suma cero).
 * 'pl'   = coste/valor en PL (positivo rasgos, negativo defectos).
 * 'spec' = true si requiere un texto libre de detalle.
 */

/** Presupuesto de PL recomendado en creación (no es un techo, solo guía). */
function ope_rol_pl_presupuesto()
{
    return array('recomendado' => 6, 'suma_objetivo' => 0);
}

/**
 * Rasgos Generales (§4.3). Cualquier personaje puede comprarlos con PL.
 * Agrupados por categoría para la UI.
 */
function ope_rol_rasgos_generales()
{
    return array(
        'Armas y Técnica' => array(
            'versatilidad-arma'   => array('nombre' => 'Versatilidad de Arma', 'pl' => 2, 'efecto' => 'Dominas una segunda familia de arma a nivel básico (ataque básico + técnica base sin el −4 Gap de arma no entrenada). No ganas nodos Eternal de esa familia.'),
            'ambidiestro'         => array('nombre' => 'Ambidiestro', 'pl' => 2, 'efecto' => 'Empuñas un arma en cada mano sin penalización; +1 ataque básico ligero por combate con la mano secundaria.'),
            'empunadura-dos-manos'=> array('nombre' => 'Empuñadura a Dos Manos', 'pl' => 1, 'efecto' => 'Con arma a dos manos: +1 carga de Rotura de Guardia o +10% daño en el golpe cargado (eliges al declarar).'),
            'desarme-experto'     => array('nombre' => 'Desarme Experto', 'pl' => 2, 'efecto' => '1×/combate, intento de desarme por Gap de FUE o AGI; si ganas, el rival pierde su arma 1 turno.'),
            'contragolpe'         => array('nombre' => 'Contragolpe', 'pl' => 3, 'efecto' => '1×/combate, tras esquivar con éxito un ataque CcC, ejecutas un ataque básico gratis (0 PA).'),
            'guardia-ferrea'      => array('nombre' => 'Guardia Férrea', 'pl' => 2, 'efecto' => 'Al Defender activamente con arma o escudo, reduces un 10% adicional el daño del golpe bloqueado.'),
            'punteria-fina'       => array('nombre' => 'Puntería Fina', 'pl' => 2, 'efecto' => 'A distancia ignoras la cobertura ligera y reduces en 1 la penalización de rango.'),
            'lanzador-nato'       => array('nombre' => 'Lanzador Nato', 'pl' => 1, 'efecto' => 'Arrojar cualquier arma no sufre penalización de familia no entrenada.'),
            'carga-devastadora'   => array('nombre' => 'Carga Devastadora', 'pl' => 2, 'efecto' => 'Si te mueves ≥5 m en línea antes de atacar, el primer golpe gana +15% daño.'),
            'maestro-finta'       => array('nombre' => 'Maestro de la Finta', 'pl' => 2, 'efecto' => '1×/combate, tu siguiente ataque no puede esquivarse por Gap medio (solo por Gap real, golpe a golpe).'),
            'instinto-asesino'    => array('nombre' => 'Instinto Asesino', 'pl' => 3, 'efecto' => '1×/combate, si reduces a un enemigo bajo 20% PV, gastas 1 PA para repetir un ataque básico sin coste de EN.'),
            'voluntad-hierro'     => array('nombre' => 'Voluntad de Hierro', 'pl' => 2, 'efecto' => '1×/combate, resistes por completo un efecto de Haki o intimidación de Tramo ≤ el tuyo (incluye Haoshoku).'),
            'golpe-practica'      => array('nombre' => 'Golpe de Práctica', 'pl' => 1, 'efecto' => 'Tu técnica base de arma cuesta −3 EN (mín. 5).'),
            'reflejos-afinados'   => array('nombre' => 'Reflejos Afinados', 'pl' => 1, 'efecto' => '1×/combate, te adelantas un puesto en el orden de posteo.'),
            'vitalidad-acero'     => array('nombre' => 'Vitalidad de Acero', 'pl' => 2, 'efecto' => 'PV máximo +5%.'),
            'cyborg'              => array('nombre' => 'Cyborg: Miembro Mecánico', 'pl' => 5, 'sub' => 'cyborg', 'efecto' => 'Empiezas con una modificación cibernética Tier I (Brazo/Pierna/Ojo/Torso). Requiere trasfondo aprobado.'),
        ),
        'Bestias y Vínculos' => array(
            'domador-bestias'     => array('nombre' => 'Domador de Bestias', 'pl' => 4, 'efecto' => 'Domas una criatura de Tramo ≤ el tuyo (Gap de CAR o VOL). Funciona como NPC Menor sin consumir tu cupo de 3. Máx. 1 activa.'),
            'yugo-voluntad'       => array('nombre' => 'Yugo de Voluntad', 'pl' => 4, 'spec' => true, 'efecto' => 'Hasta 2 NPC Menores subyugados por coacción (no por pago). Lealtad frágil; bajo 30 puede rebelarse. Requiere trasfondo/facción aprobado.'),
        ),
        'Físico y Supervivencia' => array(
            'piel-curtida'        => array('nombre' => 'Piel Curtida', 'pl' => 2, 'spec' => true, 'efecto' => 'Sin Fatiga por climas extremos (elige calor, frío o ambos).'),
            'pulmon-buzo'         => array('nombre' => 'Pulmón de Buzo', 'pl' => 2, 'efecto' => 'Doble de apnea; asfixia sobre ti a la mitad de duración.'),
            'metabolismo-resistente' => array('nombre' => 'Metabolismo Resistente', 'pl' => 2, 'efecto' => 'Envenenado sobre ti −1 turno (mín. 1).'),
            'sangre-resistente'   => array('nombre' => 'Sangre Resistente', 'pl' => 2, 'spec' => true, 'efecto' => '1×/combate, −1 turno a Veneno o presión/Silencio de Haki (elige uno).'),
            'sangre-fria'         => array('nombre' => 'Sangre Fría (Climas)', 'pl' => 2, 'efecto' => 'Sin Fatiga ni Congelado por frío ambiental extremo.'),
            'reserva-oxigeno'     => array('nombre' => 'Reserva de Oxígeno', 'pl' => 3, 'efecto' => 'Respiras bajo el agua escenas cortas (≤10 min) sin Dial. (No Gyojin/Merfolk.)'),
            'cicatrizado'         => array('nombre' => 'Cicatrizado', 'pl' => 1, 'efecto' => 'Heridas narrativas sanan en la mitad de tiempo.'),
            'estomago-hierro'     => array('nombre' => 'Estómago de Hierro', 'pl' => 1, 'efecto' => 'Inmune a intoxicaciones leves ingeridas.'),
            'escalador-nato'      => array('nombre' => 'Escalador Nato', 'pl' => 1, 'efecto' => 'Trepas superficies difíciles sin penalización ni riesgo narrativo.'),
            'corredor-fondo'      => array('nombre' => 'Corredor de Fondo', 'pl' => 1, 'efecto' => 'No sufres Fatiga narrativa por marchas o persecuciones largas.'),
        ),
        'Social' => array(
            'sangre-lider'        => array('nombre' => 'Sangre de Líder', 'pl' => 3, 'efecto' => 'Tus NPCs suben Lealtad un nivel más rápido.'),
            'linaje-notable'      => array('nombre' => 'Linaje notable', 'pl' => 3, 'spec' => true, 'efecto' => 'Familia importante / descendiente legendario: contactos y acceso social. Requiere staff.'),
            'presencia-amenazante'=> array('nombre' => 'Presencia Amenazante', 'pl' => 2, 'efecto' => 'Wanted inicial +20%; NPCs de bajo rango te evitan.'),
            'rostro-anonimo'      => array('nombre' => 'Rostro Anónimo', 'pl' => 2, 'efecto' => 'Wanted inicial −30%; ventaja en infiltración.'),
            'encanto-universal'   => array('nombre' => 'Encanto Universal', 'pl' => 2, 'spec' => true, 'efecto' => 'Un colectivo declarado te trata con simpatía inicial.'),
            'red-rumores'         => array('nombre' => 'Red de Rumores', 'pl' => 2, 'efecto' => '1×/arco, pista gratuita del staff.'),
            'diplomatico-nato'    => array('nombre' => 'Diplomático Nato', 'pl' => 1, 'efecto' => 'Abres negociación con facción hostil sin combate previo (no garantiza éxito).'),
            'buena-estrella'      => array('nombre' => 'Buena estrella', 'pl' => 1, 'efecto' => 'Mejor trato inicial de NPCs neutrales.'),
        ),
        'Oficio' => array(
            'manos-artesano'      => array('nombre' => 'Manos de Artesano', 'pl' => 2, 'efecto' => '3 oficios de crafting activos en vez de 2.'),
            'farmaceutico-campo'  => array('nombre' => 'Farmacéutico de Campo', 'pl' => 2, 'efecto' => 'Creaciones de Medicina fuera de combate +20% PV/EN. Requiere Oficio Medicina.'),
            'oficio-adicional'    => array('nombre' => 'Oficio adicional', 'pl' => 2, 'efecto' => 'Empiezas con un segundo oficio.'),
            'recolector-nato'     => array('nombre' => 'Recolector Nato', 'pl' => 1, 'efecto' => 'Recolección +10% XP.'),
            'cocinero-tripulacion'=> array('nombre' => 'Cocinero de Tripulación', 'pl' => 1, 'efecto' => 'Tus platos duran 1 escena más. Requiere Oficio Cocina.'),
            'ojo-comerciante'     => array('nombre' => 'Ojo de Comerciante', 'pl' => 1, 'efecto' => '±10% mejor precio con mercantes.'),
        ),
        'Utilidad / General' => array(
            'corazonada'          => array('nombre' => 'Corazonada', 'pl' => 1, 'efecto' => '1×/escena fuera de combate, indicio de peligro.'),
            'ojo-vigilante'       => array('nombre' => 'Ojo Vigilante', 'pl' => 1, 'efecto' => '1×/escena, detectas trampa/emboscada obvia.'),
            'memoria-rutas'       => array('nombre' => 'Memoria de rutas', 'pl' => 1, 'efecto' => 'Ventaja en navegación; mejor tabla de eventos benignos.'),
            'mano-firme'          => array('nombre' => 'Mano firme', 'pl' => 1, 'efecto' => '+1 a la calidad descriptiva de tus objetos.'),
        ),
        'Linaje (mezclas)' => array(
            'experimento'         => array('nombre' => 'Experimento / Anomalía', 'pl' => 2, 'spec' => true, 'efecto' => 'Habilita las mezclas de Laboratorio/Anomalía (§2.2): tu Factor Linaje es fruto de intervención científica o herencia mística. Requiere aprobación de staff.'),
        ),
    );
}

/**
 * Rasgos Raciales (§3.3), indexados por id de linaje. Se compran con PL y solo
 * son accesibles si tu linaje (o uno de tus dos, si híbrido) los tiene.
 */
function ope_rol_rasgos_raciales()
{
    return array(
        'humanos' => array(
            'improvisar'        => array('nombre' => 'Improvisar', 'pl' => 2, 'efecto' => '1×/combate, 0 PA: antes de resolver un Gap, usas un stat distinto al de la tabla (justificación narrativa; staff puede vetar).'),
            'resiliencia-mental'=> array('nombre' => 'Resiliencia Mental', 'pl' => 2, 'efecto' => 'Bajo Miedo/Terror/Intimidación, su duración se reduce a la mitad (mín. 1) y tu Voluntad efectiva recibe +3 mientras persista.'),
        ),
        'oni' => array(
            'sangre-hirviente'  => array('nombre' => 'Sangre Hirviente', 'pl' => 3, 'efecto' => 'Ganas 1 carga de Furia (máx. 3) al golpear/recibir ≥10% de tu PV máx (bruto). Al atacar gastas cargas: +8 daño bruto y −5% mitigación ese turno por carga.'),
            'sed-sake'          => array('nombre' => 'Sed de Sake', 'pl' => 1, 'req' => 'sangre-hirviente', 'efecto' => 'Ebrio sube tu máximo de Furia a 5 y elimina la penalización de mitigación al gastarla. Requiere Sangre Hirviente.'),
        ),
        'gigantes' => array(
            'peso-inamovible'   => array('nombre' => 'Peso Inamovible', 'pl' => 2, 'efecto' => 'No puedes ser derribado por Gap de Fuerza si tu Resistencia ≥ la del atacante.'),
            'paso-colosal'      => array('nombre' => 'Paso Colosal', 'pl' => 1, 'efecto' => 'Ignoras la penalización de Terreno difícil al calcular tu movimiento libre.'),
            'categoria-tamano'  => array('nombre' => 'Categoría de Tamaño', 'pl' => 3, 'spec' => true, 'efecto' => 'Declaras tu altura (15–30 m). Fija tu alcance CcC extra, bonus de Rotura de Guardia y la PA que pierde cualquier rival menor (ver sub-tabla §3.3).'),
        ),
        'buccaneers' => array(
            'cuerpo-no-rinde'   => array('nombre' => 'Cuerpo que no se Rinde', 'pl' => 3, 'efecto' => 'PV máx +10%. La 1ª vez que caes bajo 20% PV, recuperas 5% PV máx (1×/combate). Bajo 30% PV, el daño físico recibido −10% adicional.'),
            'aguante-imposible' => array('nombre' => 'Aguante ante lo Imposible', 'pl' => 2, 'efecto' => 'Contra un estado con Gap ≥+6, lo tratas como +1…+5 solo a efectos de duración (el ×1,25 de daño sí aplica).'),
        ),
        'minks' => array(
            'latido-salvaje'    => array('nombre' => 'Latido Salvaje', 'pl' => 2, 'efecto' => 'Al gastar EN en una técnica, recuperas 1 PV por cada 10 EN de esa técnica, máx. 10 PV por técnica.'),
            'sulong-incontrolado'=> array('nombre' => 'Sulong Incontrolado', 'pl' => 2, 'efecto' => 'Desde Nivel 21, bajo luna llena real te transformas de forma incontrolada: +30% FUE/AGI/RES, −25% VOL, sin cancelación voluntaria.'),
        ),
        'gyojins' => array(
            'piel-abismo'       => array('nombre' => 'Piel de Abismo', 'pl' => 3, 'efecto' => '+3 Armadura flat física permanente.'),
            'hijo-mar'          => array('nombre' => 'Hijo del Mar', 'pl' => 2, 'efecto' => 'Sumergido: respiras sin límite, sin penalización de movimiento/combate; +2 Gap de Fuerza en técnicas de Karate Gyojin.'),
        ),
        'lunarians' => array(
            'llama-dorsal'      => array('nombre' => 'Llama Dorsal', 'pl' => 3, 'efecto' => 'Encender/Apagar como acción libre (0 PA, 1×/turno). Encendida: −20% daño recibido, −2 AGI, 5 EN/turno. Apagada: +2 AGI de movimiento y vuelo sostenido Tier 2.'),
        ),
        'skypeans' => array(
            'dominio-dial'      => array('nombre' => 'Dominio del Dial', 'pl' => 2, 'efecto' => 'Cualquier Dial cuesta 1 EN menos (mín. 1) y no exige Oficio de Ingeniería para su carga básica.'),
            'dominio-caida'     => array('nombre' => 'Dominio de la Caída', 'pl' => 1, 'efecto' => 'Nunca sufres daño de caída, aterrizas de pie y desvías hasta 5 m horizontales por cada 10 m que caigas.'),
        ),
        'tontattas' => array(
            'sombra-diminuta'   => array('nombre' => 'Sombra Diminuta', 'pl' => 3, 'efecto' => '+2 Gap fijo de Agilidad al esquivar y al atacar; tu daño físico no se reduce por diferencia de tamaño/Tramo.'),
            'incalculable'      => array('nombre' => 'Incalculable', 'pl' => 2, 'efecto' => 'Nunca eres objetivo de Aplastamiento por mera diferencia de Tramo o Gap medio: el enemigo debe ganarte golpe a golpe.'),
        ),
        'merfolk' => array(
            'cuerpo-sin-ancla'  => array('nombre' => 'Cuerpo sin Ancla', 'pl' => 2, 'efecto' => 'Nadas al doble, respiras bajo el agua sin límite; +3 Gap fijo para resistir/esquivar Derribo, agarre o Enraizado (dentro o fuera del agua).'),
            'corriente-viva'    => array('nombre' => 'Corriente Viva', 'pl' => 1, 'efecto' => 'Bajo el agua tus ataques ignoran 10% de mitigación; puedes arrastrar a una persona nadando sin penalización.'),
        ),
    );
}

/**
 * Rasgo Puro (§3.4), indexado por id de linaje. Solo comprable por Sangre Pura.
 */
function ope_rol_rasgo_puro()
{
    return array(
        'humanos'    => array('id' => 'puro-humanos',    'nombre' => 'Voluntad sin Techo', 'pl' => 3, 'efecto' => '1×/combate, ignoras por completo un efecto de estado mental o de Haki.'),
        'oni'        => array('id' => 'puro-oni',         'nombre' => 'Furia Verdadera', 'pl' => 3, 'efecto' => 'Tu tope de cargas de Furia sube a 5 sin necesidad de estar Ebrio.'),
        'gigantes'   => array('id' => 'puro-gigantes',    'nombre' => 'Sangre de Elbaf', 'pl' => 3, 'efecto' => 'Tu Categoría de Tamaño cuenta un escalón más alto para Rotura de Guardia e intimidación.'),
        'buccaneers' => array('id' => 'puro-buccaneers',  'nombre' => 'Herencia Titánica', 'pl' => 3, 'efecto' => 'La recuperación de 5% PV bajo 20% pasa a 2×/combate.'),
        'minks'      => array('id' => 'puro-minks',       'nombre' => 'Corazón Eléctrico', 'pl' => 3, 'efecto' => 'Tu tope de EN→PV sube a 15 PV por técnica.'),
        'gyojins'    => array('id' => 'puro-gyojins',     'nombre' => 'Presión Abisal', 'pl' => 3, 'efecto' => 'Tu Armadura flat física pasa a +5.'),
        'lunarians'  => array('id' => 'puro-lunarians',   'nombre' => 'Llama Ancestral Pura', 'pl' => 3, 'efecto' => 'Encender/Apagar sin límite por turno; Encendida, la reducción sube a 25%.'),
        'skypeans'   => array('id' => 'puro-skypeans',    'nombre' => 'Hijo del Cielo', 'pl' => 3, 'efecto' => 'Diales 2 EN menos (mín. 1) y desvías 8 m horizontales por 10 m de caída.'),
        'tontattas'  => array('id' => 'puro-tontattas',   'nombre' => 'Invisibilidad Diminuta', 'pl' => 3, 'efecto' => 'Tu Gap fijo de esquiva sube a +3.'),
        'merfolk'    => array('id' => 'puro-merfolk',     'nombre' => 'Fluidez Absoluta', 'pl' => 3, 'efecto' => 'Tu Gap anti-Derribo/agarre sube a +4 y arrastras a 2 personas nadando.'),
    );
}

/**
 * Dotes Innatas de Linaje (§6), indexadas por id de linaje. Opcionales: se
 * compran con PL, conceden Nv.1 sin PD y ocupan 1 de los 4 slots de dotes.
 */
function ope_rol_dotes_innatas()
{
    return array(
        'gyojins'   => array('id' => 'dote-karate-gyojin',  'nombre' => 'Karate Gyojin', 'pl' => 4),
        'merfolk'   => array('id' => 'dote-karate-gyojin',  'nombre' => 'Karate Gyojin', 'pl' => 4),
        'minks'     => array('id' => 'dote-electro-bestial','nombre' => 'Electro Bestial', 'pl' => 3),
        'lunarians' => array('id' => 'dote-sangre-lunarian','nombre' => 'Sangre de Lunarian', 'pl' => 4),
        'skypeans'  => array('id' => 'dote-maestro-dial',   'nombre' => 'Maestro del Dial', 'pl' => 3),
    );
}

/** Defectos generales (§4.4). Dan PL (valor negativo). */
function ope_rol_fl_defectos()
{
    return array(
        'manco'               => array('nombre' => 'Manco / Mutilado', 'pl' => -3, 'categoria' => 'Combate', 'spec' => true, 'efecto' => 'Pierdes el uso de una extremidad; técnicas que la requieran −3 Gap o inaccesibles. (Normalmente sobrevenido.)'),
        'agotamiento-espiritual'=> array('nombre' => 'Agotamiento Espiritual', 'pl' => -3, 'categoria' => 'Combate', 'efecto' => 'EN máximo −10%.'),
        'deuda-destino'       => array('nombre' => 'Deuda con el Destino', 'pl' => -3, 'categoria' => 'General', 'efecto' => 'El staff fuerza una complicación mayor 1×/temporada.'),
        'punto-debil'         => array('nombre' => 'Punto Débil', 'pl' => -2, 'categoria' => 'Combate', 'spec' => true, 'efecto' => 'Zona declarada; ataques que la exploten ganan +1 turno de estado.'),
        'cuerpo-fragil'       => array('nombre' => 'Cuerpo frágil', 'pl' => -2, 'categoria' => 'Combate', 'efecto' => 'PV máximo −5%.'),
        'buscado-marina'      => array('nombre' => 'Buscado por la Marina', 'pl' => -2, 'categoria' => 'Social', 'efecto' => 'Hostilidad y controles en puertos del Gobierno Mundial.'),
        'enemigo-jurado'      => array('nombre' => 'Enemigo jurado', 'pl' => -2, 'categoria' => 'Social', 'spec' => true, 'efecto' => 'Un NPC/grupo te busca activamente.'),
        'marca-traidor'       => array('nombre' => 'Marca del Traidor', 'pl' => -2, 'categoria' => 'Social', 'efecto' => 'Personajes con honor alto desconfían de ti.'),
        'deuda-alma'          => array('nombre' => 'Deuda del Alma', 'pl' => -2, 'categoria' => 'Social', 'spec' => true, 'efecto' => 'Debes tu vida o promesa a un pirata de alto rango.'),
        'reputacion-manchada' => array('nombre' => 'Reputación Manchada', 'pl' => -2, 'categoria' => 'Social', 'efecto' => 'Un rumor te sigue; tu propia facción te recela.'),
        'cicatriz-abierta'    => array('nombre' => 'Cicatriz Abierta', 'pl' => -2, 'categoria' => 'Supervivencia', 'efecto' => 'Tras 3 rondas de combate intenso, pierdes 5% PV máx hasta curarte.'),
        'metabolismo-lento'   => array('nombre' => 'Metabolismo Lento', 'pl' => -2, 'categoria' => 'Supervivencia', 'efecto' => 'Regeneración de EN entre escenas −10%.'),
        'maldicion-menor'     => array('nombre' => 'Maldición Menor', 'pl' => -2, 'categoria' => 'General', 'efecto' => 'El staff empeora tiradas de eventos de viaje/recolección.'),
        'golpeador-lento'     => array('nombre' => 'Golpeador Lento', 'pl' => -1, 'categoria' => 'Combate', 'efecto' => 'Técnica base +5 EN.'),
        'grito-batalla'       => array('nombre' => 'Grito de Batalla Involuntario', 'pl' => -1, 'categoria' => 'Combate', 'efecto' => 'Pierdes sigilo/emboscada al iniciar combate.'),
        'sin-natacion'        => array('nombre' => 'Peso Muerto (Sin Natación)', 'pl' => -1, 'categoria' => 'Combate', 'efecto' => 'No sabes nadar; peligro narrativo en agua profunda.'),
        'lengua-torpe'        => array('nombre' => 'Lengua torpe', 'pl' => -1, 'categoria' => 'Social', 'efecto' => 'Desventaja en negociación/persuasión.'),
        'deuda-pendiente'     => array('nombre' => 'Deuda pendiente', 'pl' => -1, 'categoria' => 'Social', 'efecto' => 'Debes Berries a un broker peligroso.'),
        'enemigo-cuna'        => array('nombre' => 'Enemigo de Cuna', 'pl' => -1, 'categoria' => 'Social', 'spec' => true, 'efecto' => 'Una facción/isla te recibe mal desde el inicio.'),
        'bocazas'             => array('nombre' => 'Bocazas', 'pl' => -1, 'categoria' => 'Social', 'efecto' => 'Hablas de más en escenas de tensión (rol obligado).'),
        'vulnerabilidad-clima'=> array('nombre' => 'Vulnerabilidad al Frío/Calor', 'pl' => -1, 'categoria' => 'Supervivencia', 'spec' => true, 'efecto' => 'Fatiga automática tras 1 escena en ese clima sin protección.'),
        'alergia'             => array('nombre' => 'Alergia', 'pl' => -1, 'categoria' => 'Supervivencia', 'spec' => true, 'efecto' => 'Una sustancia común te sienta muy mal (Envenenado leve).'),
        'mareo-cronico'       => array('nombre' => 'Mareo Crónico', 'pl' => -1, 'categoria' => 'Supervivencia', 'efecto' => 'Penalización en mar agitado.'),
        'manos-torpes'        => array('nombre' => 'Manos Torpes', 'pl' => -1, 'categoria' => 'Oficio', 'efecto' => '−1 nivel de calidad de crafting salvo tiempo extra.'),
        'sin-oficio'          => array('nombre' => 'Sin Oficio', 'pl' => -1, 'categoria' => 'Oficio', 'efecto' => 'Sin oficio activo al crear.'),
        'aprendiz-eterno'     => array('nombre' => 'Aprendiz Eterno', 'pl' => -1, 'categoria' => 'Oficio', 'efecto' => 'Oficio activo −10% XP.'),
        'supersticion'        => array('nombre' => 'Superstición', 'pl' => -1, 'categoria' => 'General', 'spec' => true, 'efecto' => 'Rehúsas cierta acción por creencia (rol obligado).'),
        'fobia'               => array('nombre' => 'Fobia', 'pl' => -1, 'categoria' => 'General', 'spec' => true, 'efecto' => 'Detonante declarado; penalización narrativa en escenas con él.'),
        'analfabeto'          => array('nombre' => 'Analfabeto', 'pl' => -1, 'categoria' => 'General', 'efecto' => 'No lees ni escribes.'),
        'mala-memoria'        => array('nombre' => 'Mala Memoria', 'pl' => -1, 'categoria' => 'General', 'efecto' => 'Olvidas detalles menores (rol obligado).'),
    );
}

/** Defectos de Hibridación (§4.5). Obligatorios para híbridos (≥ −2 en total). */
function ope_rol_defectos_hibridacion()
{
    return array(
        'desequilibrio-celular'  => array('nombre' => 'Desequilibrio Celular', 'pl' => -2, 'efecto' => '1×/combate (o azar de viaje), en el peor momento tu cuerpo falla: pierdes 1 PA ese turno o sufres Fatiga 1 turno.'),
        'rechazo-sangre'         => array('nombre' => 'Rechazo de Sangre', 'pl' => -2, 'efecto' => 'La curación externa (Medicina, Cirujano de Mar) es −20% efectiva sobre ti.'),
        'anatomia-contradictoria'=> array('nombre' => 'Anatomía Contradictoria', 'pl' => -3, 'spec' => true, 'efecto' => 'Vulnerabilidad ambiental fija (agua de mar, altura, presión…) declarada con staff, con penalización recurrente.'),
        'estigma-hibrido'        => array('nombre' => 'Estigma del Híbrido', 'pl' => -2, 'efecto' => 'Penalización social por defecto con NPCs de ambos pueblos de origen.'),
    );
}

/**
 * Categoría de tamaño por linaje (§5.1), como índice 0..4
 * (Diminuto=0, Pequeño=1, Mediano=2, Grande=3, Colosal=4). Interino Fase 2/3:
 * se usa un valor por defecto por linaje para el bloqueo de híbridos (diff ≥3).
 */
function ope_rol_linaje_tamano_idx()
{
    return array(
        'tontattas' => 0,
        'skypeans'  => 2,
        'humanos'   => 2,
        'minks'     => 2,
        'gyojins'   => 2,
        'merfolk'   => 2,
        'oni'       => 3,
        'buccaneers'=> 3,
        'lunarians' => 3,
        'gigantes'  => 4,
    );
}
