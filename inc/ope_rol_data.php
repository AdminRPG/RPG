<?php
/**
 * I-Forge · Catálogo del sistema de rol "One Piece Eternal"
 * -----------------------------------------------------------------
 * Fuente única de verdad para el wizard de creación de personaje
 * (crear-personaje.php) y su validación server-side. Transcrito de
 * one-piece-eternal-sistemas/{01,05,06,08,09}-*.md.
 *
 * Todo son arrays PHP planos (sin clases) para poder volcarlos a JSON
 * y reutilizarlos tanto en el render como en el cálculo JS en vivo,
 * y para poder recalcular en servidor (PC, stats) al validar el envío.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

if (!function_exists('ope_rol_stats')) {
    /** Las 12 stats, agrupadas en 3 pilares. */
    function ope_rol_stats()
    {
        return array(
            'cuerpo' => array(
                'label' => 'Cuerpo',
                'stats' => array(
                    'FUE' => 'Fuerza',
                    'DES' => 'Destreza',
                    'VIG' => 'Vigor',
                    'AGI' => 'Agilidad',
                ),
            ),
            'mente' => array(
                'label' => 'Mente',
                'stats' => array(
                    'INT' => 'Intelecto',
                    'ING' => 'Ingenio',
                    'CON' => 'Concentración',
                    'PER' => 'Percepción',
                ),
            ),
            'espiritu' => array(
                'label' => 'Espíritu',
                'stats' => array(
                    'CAR' => 'Carisma',
                    'CTR' => 'Control',
                    'VOL' => 'Voluntad',
                    'SEN' => 'Sensibilidad',
                ),
            ),
        );
    }

    /** Lista plana de las 12 siglas, en orden de presentación. */
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

    /** Escala de rangos F..M+ → valor numérico. */
    function ope_rol_rank_scale()
    {
        return array(
            'F' => 1, 'E' => 2, 'D' => 3, 'C' => 4, 'B' => 5,
            'A' => 6, 'S' => 7, 'SS' => 8, 'M' => 9, 'M+' => 10,
        );
    }

    /** Rango del personaje según la suma de las 12 stats efectivas (doc 05). */
    function ope_rol_rank_from_sum($sum)
    {
        $sum = (int)$sum;
        if ($sum >= 66) return 'M+';
        if ($sum >= 56) return 'M';
        if ($sum >= 47) return 'SS';
        if ($sum >= 39) return 'S';
        if ($sum >= 32) return 'A';
        if ($sum >= 26) return 'B';
        if ($sum >= 21) return 'C';
        if ($sum >= 17) return 'D';
        if ($sum >= 14) return 'E';
        return 'F';
    }
}

if (!function_exists('ope_rol_razas')) {
    /**
     * 9 razas. 'mod' = deltas a las stats efectivas de la PASIVA PRIMARIA
     * (siempre se aplica, pura o híbrida). 'mod_secundaria' solo se aplica
     * si el personaje es PURO de esa raza (no híbrido).
     */
    function ope_rol_razas()
    {
        return array(
            'humano' => array(
                'nombre' => 'Humano',
                'resumen' => 'La raza más numerosa. Presente en los Cuatro Mares y la Grand Line. Su fuerza es la adaptabilidad.',
                'primaria_nombre' => 'Adaptabilidad',
                'primaria_desc' => 'Al crear el personaje, puedes subir DOS stats de F a E en lugar de una.',
                'secundaria_nombre' => 'Herencia Tribal',
                'secundaria_desc' => 'Elige: Humano Genérico (Mayoría Silenciosa) o una Tribu Real (Ashinaga, Tenaga, Kubinaga, Kuja).',
                'mod' => array(),
                'mod_secundaria' => array(),
                'extra_stat_bump' => true,
                // Elección obligatoria al crear (solo si es PURO, ver INI-01): sustituye
                // la pasiva secundaria genérica por la de la tribu elegida.
                'sub_opciones_label' => 'Herencia Tribal',
                'sub_opciones' => array(
                    'generico' => array(
                        'nombre' => 'Mayoría Silenciosa',
                        'desc' => '+10% a la ganancia de Reputación con cualquier facción.',
                        'mod' => array(),
                    ),
                    'ashinaga' => array(
                        'nombre' => 'Zancada Larga (Tribu Ashinaga)',
                        'desc' => '+50% velocidad de movimiento terrestre. +1 AGI efectiva en ataques de patada o salto.',
                        'mod' => array(),
                    ),
                    'tenaga' => array(
                        'nombre' => 'Doble Articulación (Tribu Tenaga)',
                        'desc' => '+1m de alcance cuerpo a cuerpo con armas de mano. +1 DES efectiva con herramientas de precisión, ganzúas o instrumentos.',
                        'mod' => array(),
                    ),
                    'kubinaga' => array(
                        'nombre' => 'Visión Elevada (Tribu Kubinaga)',
                        'desc' => '+1 PER efectiva para avistar amenazas a distancia. Inmune a penalizadores de flanqueo básico y -5 a la dificultad para detectar emboscadas por la espalda.',
                        'mod' => array(),
                    ),
                    'kuja' => array(
                        'nombre' => 'Haki de las Guerreras (Tribu Kuja)',
                        'desc' => 'Solo mujeres. Empiezas con la virtud "Haki de Armadura Latente" gratis (sin gastar PC). Tus flechas/arcos pueden canalizar VOL para daño espiritual menor (Tier I).',
                        'mod' => array(),
                        'solo_femenino' => true,
                    ),
                ),
            ),
            'skypiean' => array(
                'nombre' => 'Skypiean',
                'resumen' => 'Habitantes de las islas del cielo. Alas pequeñas para planear. Cultura del Dial.',
                'primaria_nombre' => 'Planeo Celestial',
                'primaria_desc' => 'Puedes planear distancias cortas y niegas el daño por caída. +1 AGI efectiva para movimiento.',
                'secundaria_nombre' => 'Herencia del Dial',
                'secundaria_desc' => 'Empiezas con un Dial básico a elección. +1 ING para crear/modificar Dials.',
                'mod' => array('AGI' => 1),
                'mod_secundaria' => array('ING' => 1),
            ),
            'gyojin' => array(
                'nombre' => 'Gyojin (Tritón)',
                'resumen' => 'Guerreros del mar. Velocidad y fuerza multiplicadas bajo el agua.',
                'primaria_nombre' => 'Sangre del Abismo',
                'primaria_desc' => 'Respiras bajo el agua. ×2 velocidad de movimiento en agua. +1 VIG permanente.',
                'secundaria_nombre' => 'Gyojin Karate Innato',
                'secundaria_desc' => 'Carta Tier I "Gyojin Karate: Puño de Agua" gratis. +1 FUE efectiva en contacto con agua.',
                'mod' => array('VIG' => 1),
                'mod_secundaria' => array('FUE' => 1),
            ),
            'gigante' => array(
                'nombre' => 'Gigante',
                'resumen' => 'Colosos de Elbaf. Cultura guerrera basada en el honor, el duelo y la fuerza.',
                'primaria_nombre' => 'Fuerza Colosal',
                'primaria_desc' => '+2 FUE permanente. ×1.5 daño cuerpo a cuerpo. ×2 alcance. -1 AGI permanente.',
                'secundaria_nombre' => 'Linaje Colosal',
                'secundaria_desc' => 'Elige: Gigante Común (Piel de Batalla) o Gigante Ancestral (Cuerpo Devastador).',
                'mod' => array('FUE' => 2, 'AGI' => -1),
                'mod_secundaria' => array('VIG' => 1),
                // Elección obligatoria al crear (solo si es PURO, ver INI-01).
                'sub_opciones_label' => 'Linaje Colosal',
                'sub_opciones' => array(
                    'comun' => array(
                        'nombre' => 'Piel de Batalla (Gigante Común)',
                        'desc' => '+1 VIG permanente. Las heridas leves no te afectan en combate.',
                        'mod' => array('VIG' => 1),
                    ),
                    'ancestral' => array(
                        'nombre' => 'Cuerpo Devastador (Gigante Ancestral)',
                        'desc' => 'Tamaño de 30 a 60 m, con cuernos. Tu penalización racial de AGI aumenta a -2 (en lugar de -1). Tu multiplicador de daño cuerpo a cuerpo sube a ×2.0 (en lugar de ×1.5) y tus ataques ignoran el 50% de la resistencia física de estructuras, defensas pesadas y barcos.',
                        // -1 AGI extra que se SUMA a la penalización -1 ya aplicada por la
                        // pasiva primaria: total efectivo -2 AGI para el Ancestral.
                        'mod' => array('AGI' => -1),
                    ),
                ),
            ),
            'mink' => array(
                'nombre' => 'Mink',
                'resumen' => 'Tribu animal de Zou. Leales hasta la muerte. Electro natural y Sulong bajo luna llena.',
                'primaria_nombre' => 'Electro',
                'primaria_desc' => 'Generas descargas eléctricas al contacto. +25% daño contra metal/armadura.',
                'secundaria_nombre' => 'Instinto Salvaje',
                'secundaria_desc' => '+1 PER efectiva para rastrear, detectar emboscadas y percibir intenciones hostiles.',
                'mod' => array(),
                'mod_secundaria' => array('PER' => 1),
            ),
            'lunarian' => array(
                'nombre' => 'Lunarian',
                'resumen' => 'Raza casi extinta. Genera fuego corporal. Perseguidos por el Gobierno Mundial.',
                'primaria_nombre' => 'Llamarada',
                'primaria_desc' => 'Envuelves partes del cuerpo en fuego (1 PA). +1 VIG efectiva mientras la llama está activa.',
                'secundaria_nombre' => 'Los Últimos',
                'secundaria_desc' => 'Wanted automático de 100M. +1 VOL efectiva vs Marines/Gobierno. -50% ganancia REP en territorio del Gobierno.',
                'mod' => array(),
                'mod_secundaria' => array('VOL' => 1),
            ),
            'sirena' => array(
                'nombre' => 'Sirena / Sireno (Ningyo)',
                'resumen' => 'Belleza submarina. Comunicación natural con criaturas marinas. Velocidad extrema en agua.',
                'primaria_nombre' => 'Gracia Marina',
                'primaria_desc' => '×3 velocidad de movimiento en agua. Telepatía con fauna marina no hostil. +1 SEN permanente.',
                'secundaria_nombre' => 'Canto Hipnótico',
                'secundaria_desc' => '+1 CAR efectiva para persuasión, seducción o distracción mediante tu voz.',
                'mod' => array('SEN' => 1),
                'mod_secundaria' => array('CAR' => 1),
            ),
            'bucaneer' => array(
                'nombre' => 'Bucaneer',
                'resumen' => 'Fuerza desproporcionada en cuerpo humano. Perseguidos por el Gobierno.',
                'primaria_nombre' => 'Sangre de Gigante',
                'primaria_desc' => '+1 FUE y +1 VIG permanentes. Empuñas armas de una categoría superior sin penalización.',
                'secundaria_nombre' => 'Estirpe Marcada',
                'secundaria_desc' => 'Wanted automático de 50M. +1 VOL efectiva contra cualquier forma de opresión o esclavitud.',
                'mod' => array('FUE' => 1, 'VIG' => 1),
                'mod_secundaria' => array('VOL' => 1),
            ),
            'tontatta' => array(
                'nombre' => 'Tontatta',
                'resumen' => 'La raza más pequeña del mundo. Velocidad y sigilo incomparables. Maestros artesanos.',
                'primaria_nombre' => 'Diminuto y Letal',
                'primaria_desc' => '-1 FUE, +2 AGI, +2 DES permanentes. Los enemigos tienen -10 a PER para detectarte.',
                'secundaria_nombre' => 'Manos de Artesano',
                'secundaria_desc' => 'Oficio gratis y su primera especialización. +1 ING efectiva para fabricar o reparar.',
                'mod' => array('FUE' => -1, 'AGI' => 2, 'DES' => 2),
                'mod_secundaria' => array('ING' => 1),
            ),
        );
    }
}

if (!function_exists('ope_rol_facciones')) {
    function ope_rol_facciones()
    {
        return array(
            'marine' => array(
                'nombre' => 'Marine',
                'desc' => 'Soldado del Gobierno Mundial. Justicia, orden, jerarquía.',
                'ventaja' => '+1 rango de facción (partes como Recluta en lugar de Aspirante). Acceso a recursos Marines.',
            ),
            'pirata' => array(
                'nombre' => 'Pirata',
                'desc' => 'Libre en el mar. Tripulación, bandera, sueños.',
                'ventaja' => 'Empiezas con un bote/barco pequeño. +10% ganancia de Wanted.',
            ),
            'revolucionario' => array(
                'nombre' => 'Revolucionario',
                'desc' => 'Miembro del Ejército Revolucionario. Lucha en las sombras.',
                'ventaja' => 'Identidad secreta gratuita. Acceso a red de contactos revolucionarios.',
            ),
            'gobierno' => array(
                'nombre' => 'Gobierno Mundial',
                'desc' => 'Agente, burócrata o oficial gubernamental de baja escala: Cipher Pol, burocracia y operaciones encubiertas.',
                'ventaja' => '+1 rango inicial de Gobierno. Empiezas con una Acreditación Gubernamental oficial que permite libre tránsito por reinos afiliados.',
            ),
            'cazarrecompensas' => array(
                'nombre' => 'Cazarrecompensas',
                'desc' => 'Independiente. Caza piratas y forajidos por dinero.',
                'ventaja' => '+1 nivel en el Gremio de Cazadores. +10% berries por capturas.',
            ),
            'civil' => array(
                'nombre' => 'Civil',
                'desc' => 'Comerciante, artesano, médico, erudito...',
                'ventaja' => '+1 Oficio gratuito. Sin Wanted inicial. Sin enemigos declarados.',
            ),
        );
    }
}

if (!function_exists('ope_rol_armas')) {
    function ope_rol_armas()
    {
        return array(
            'contundente' => array('nombre' => 'Arma contundente (palo, maza)', 'detalle' => '1d6 + FUE · cuerpo a cuerpo. Puede noquear.'),
            'cortante' => array('nombre' => 'Arma cortante (espada, hacha)', 'detalle' => '1d8 + DES · cuerpo a cuerpo. Puede causar hemorragia.'),
            'fuego' => array('nombre' => 'Arma de fuego (pistola)', 'detalle' => '1d6 + DES · 20m. Munición limitada (6 disparos).'),
            'rifle' => array('nombre' => 'Rifle / Francotirador', 'detalle' => '1d10 + DES · 100m. Munición limitada (1 disparo).'),
            'improvisada' => array('nombre' => 'Arma improvisada', 'detalle' => '1d4 + FUE · lo que tengas a mano.'),
        );
    }
}

if (!function_exists('ope_rol_packs_equipo')) {
    /**
     * Packs de Equipo Inicial (INI-01, Paso 6). Sustituyen la vieja pareja
     * "arma a elección + objeto personal libre": ahora se elige UN pack
     * cerrado que se adapta al concepto del personaje.
     *
     * Todos incluyen vestimenta básica de viaje, raciones para 5 días y
     * 50,000 berries iniciales (ver ope_rol_berries_iniciales()).
     *
     * NOTA: 'contenido' es solo texto descriptivo por ahora. Falta la
     * segunda fase (pendiente, fácil de añadir después): convertir cada
     * entrada de 'contenido' en objetos concretos que se insertan
     * automáticamente en el inventario ('encima'/'almacen') al crear el
     * personaje. Por ahora el pack elegido solo queda registrado en el
     * inventario, para no perder la elección hasta que se implemente eso.
     */
    function ope_rol_packs_equipo()
    {
        return array(
            'combatiente' => array(
                'nombre' => 'Pack del Combatiente',
                'resumen' => 'Para quien resuelve las cosas con las manos (o un arma).',
                'contenido' => array(
                    '1 arma básica a elección de Rango F (espada de práctica, pistola simple, bastón reforzado)',
                    'Armadura ligera de cuero (protección básica)',
                    '2 vendas / artículos médicos de primeros auxilios',
                ),
            ),
            'navegante' => array(
                'nombre' => 'Pack del Navegante',
                'resumen' => 'Para quien vive para el mar y la ruta hacia el siguiente horizonte.',
                'contenido' => array(
                    '1 Log Pose básica para la navegación en tu mar inicial',
                    'Cartas marítimas locales',
                    '1 catalejo de latón',
                ),
            ),
            'erudito' => array(
                'nombre' => 'Pack del Erudito / Médico',
                'resumen' => 'Para quien cura o para quien investiga.',
                'contenido' => array(
                    '1 kit médico básico de campaña (antiséptico, suturas, vendajes) o un compendio de libros de investigación histórica/científica',
                    'Diario de notas, tintero y pluma',
                ),
            ),
            'artesano' => array(
                'nombre' => 'Pack del Artesano / Ingeniero',
                'resumen' => 'Para quien construye, repara o forja con sus propias manos.',
                'contenido' => array(
                    'Juego de herramientas especializado de tu oficio principal (carpintería, forja, cocina...)',
                    'Algunos materiales básicos de trabajo o reparación',
                ),
            ),
            'espia' => array(
                'nombre' => 'Pack del Espía / Agente',
                'resumen' => 'Para quien se mueve entre las sombras o entre la alta sociedad.',
                'contenido' => array(
                    'Ropa formal o atuendo de incógnito de alta calidad',
                    'Un den den mushi portátil',
                    'Un kit de ganzúas de precisión',
                ),
            ),
        );
    }
}

if (!function_exists('ope_rol_virtudes')) {
    /**
     * Catálogo de virtudes (coste en PC). 'spec' = true si el ítem requiere
     * que el jugador especifique algo en un campo de texto libre.
     */
    function ope_rol_virtudes()
    {
        return array(
            'A) Linaje e Identidad' => array(
                'V-LIN-01' => array('nombre' => 'Voluntad de D.', 'coste' => 3, 'desc' => 'Tienes una "D." en tu nombre. +10% de que tus eventos afecten a los planes de los Dragones Celestiales.'),
                'V-LIN-02' => array('nombre' => 'Descendiente de los Dioses', 'coste' => 4, 'desc' => 'Sangre de Dragón Celestial en tu linaje. +20 Reputación inicial con el Gobierno Mundial. -30 con Revolucionarios. No puedes ser Revolucionario.'),
                'V-LIN-03' => array('nombre' => 'Tribu Racial', 'coste' => 2, 'desc' => 'Sub-tribu con rasgos físicos distintivos. Sin ventaja mecánica.', 'spec' => true),
                'V-LIN-04' => array('nombre' => 'Diferente', 'coste' => 1, 'desc' => 'Característica física distinta a lo común de tu raza. Puramente estético.', 'spec' => true),
            ),
            'B) Facción y Reputación' => array(
                'V-FAC-01' => array('nombre' => 'Acto Triunfal', 'coste' => 2, 'desc' => '+50 Reputación inicial y rango inmediatamente superior. (Marine, Revolucionario, Cipher Pol, Gremio de Cazadores.)', 'spec' => true),
                'V-FAC-02' => array('nombre' => 'Mancha en el Pasado', 'coste' => 2, 'desc' => '+50 Reputación Negativa y rango inmediatamente superior. (Pirata, Cazarrecompensas, Inframundo.)', 'spec' => true),
                'V-FAC-03' => array('nombre' => 'Líder Nato', 'coste' => 1, 'desc' => '+1 CAR efectiva para arengas y liderar grupos. +1 iniciativa a aliados en el primer turno.'),
                'V-FAC-04' => array('nombre' => 'Carismático', 'coste' => 1, 'desc' => '+1 CAR efectiva para diplomacia, persuasión y salir de problemas hablando.'),
                'V-FAC-05' => array('nombre' => 'Intimidante', 'coste' => 1, 'desc' => '+1 CAR efectiva para intimidación e interrogatorios.'),
                'V-FAC-06' => array('nombre' => 'Fama', 'coste' => 1, 'desc' => '+10% a todas las ganancias de Reputación (positiva o negativa).'),
                'V-FAC-07' => array('nombre' => 'Desapercibido', 'coste' => 1, 'desc' => '-10% al crecimiento de tu Wanted.'),
                'V-FAC-08' => array('nombre' => 'El Más Buscado', 'coste' => 3, 'desc' => '+15% al crecimiento de tu Wanted. Los cazarrecompensas priorizan tu captura.'),
                'V-FAC-09' => array('nombre' => 'Eres del Inframundo', 'coste' => 3, 'desc' => 'Rango de Operativo en la red del Inframundo. Acceso a mercados negros e información.'),
                'V-FAC-10' => array('nombre' => 'Doble Vida', 'coste' => 2, 'desc' => 'Dos apartados de Reputación independientes. Si una identidad cae, la otra corre peligro.', 'spec' => true),
                'V-FAC-11' => array('nombre' => 'Vieja Amistad', 'coste' => 2, 'desc' => 'Amigo de un NPC de hasta rango C. [Consensuar con staff.]', 'spec' => true),
                'V-FAC-12' => array('nombre' => 'Vieja Amistad 2', 'coste' => 3, 'desc' => 'Como Vieja Amistad, pero el NPC es de hasta rango B.', 'spec' => true),
                'V-FAC-13' => array('nombre' => 'Vínculo Familiar', 'coste' => 1, 'desc' => 'Familia prestigiosa, sin contacto directo. [Consensuar con staff.]', 'spec' => true),
                'V-FAC-14' => array('nombre' => 'Vínculo Familiar 2', 'coste' => 3, 'desc' => 'Como Vínculo Familiar, pero de rango mayor.', 'spec' => true),
            ),
            'C) Físicas' => array(
                'V-FIS-01' => array('nombre' => 'Belleza', 'coste' => 1, 'desc' => '+1 CAR efectiva donde la apariencia importe. No apilable con Fealdad.'),
                'V-FIS-02' => array('nombre' => 'Grandullón', 'coste' => 1, 'desc' => '+25% altura máxima.'),
                'V-FIS-03' => array('nombre' => 'El Más Grande', 'coste' => 3, 'desc' => '+50% altura máxima. +1 FUE efectiva para intimidación física y combate cuerpo a cuerpo.'),
                'V-FIS-04' => array('nombre' => 'Pequeñín', 'coste' => 1, 'desc' => '-25% altura mínima. +1 AGI efectiva para esquivas.'),
                'V-FIS-05' => array('nombre' => 'La Pulga', 'coste' => 3, 'desc' => '-50% altura mínima. +2 AGI efectiva para esquivas. -1 FUE permanente.'),
                'V-FIS-06' => array('nombre' => 'Nadador Nato', 'coste' => 1, 'desc' => '+10 a tiradas de natación. No disponible para Gyojin ni Sirenas.'),
                'V-FIS-07' => array('nombre' => 'Buenos Pulmones', 'coste' => 1, 'desc' => 'Aguantas la respiración 3 turnos adicionales.'),
                'V-FIS-08' => array('nombre' => 'Sentidos Aumentados 1', 'coste' => 2, 'desc' => 'Un sentido excepcional (vista, oído, olfato o tacto). +5 PER efectiva con ese sentido.', 'spec' => true),
                'V-FIS-09' => array('nombre' => 'Sentidos Aumentados 2', 'coste' => 5, 'desc' => 'Dos sentidos excepcionales.', 'spec' => true),
                'V-FIS-10' => array('nombre' => 'Fortaleza al Calor', 'coste' => 1, 'desc' => '+25 a Resistencia contra efectos de calor. Incompatible con Debilidad al Calor.'),
                'V-FIS-11' => array('nombre' => 'Fortaleza al Frío', 'coste' => 1, 'desc' => '+25 a Resistencia contra efectos de frío. Incompatible con Debilidad al Frío.'),
                'V-FIS-12' => array('nombre' => 'Fortaleza al Veneno', 'coste' => 4, 'desc' => 'Grado de Envenenamiento -1 nivel. -20% daño por veneno.'),
            ),
            'D) Supervivencia' => array(
                'V-SUP-01' => array('nombre' => 'Sueño Ligero', 'coste' => 2, 'desc' => 'Reaccionas a una ofensiva mientras duermes, con -20 a REF.'),
                'V-SUP-02' => array('nombre' => 'Sin Hambre', 'coste' => 1, 'desc' => '3 días sin comer ni beber sin penalización. Incompatible con Gula.'),
                'V-SUP-03' => array('nombre' => 'Buen Apetito', 'coste' => 2, 'desc' => 'Los efectos beneficiosos de las comidas aumentan un 25%.'),
                'V-SUP-04' => array('nombre' => 'Afinidad Animal', 'coste' => 1, 'desc' => 'Los animales salvajes no hostiles no te atacan.'),
                'V-SUP-05' => array('nombre' => 'Orientación', 'coste' => 1, 'desc' => '+5 a tiradas de navegación.'),
                'V-SUP-06' => array('nombre' => 'Optimista', 'coste' => 3, 'desc' => '+5 VOL permanente. Inmune a efectos que alteren tu estado de ánimo.'),
            ),
            'E) Progresión y Aprendizaje' => array(
                'V-PRO-01' => array('nombre' => 'Entrenamiento Intensivo', 'coste' => 3, 'desc' => '+25% PP ganado por entrenamiento.'),
                'V-PRO-02' => array('nombre' => 'Gran Trabajador', 'coste' => 4, 'desc' => '+50% Puntos de Oficio por entrenamiento.'),
                'V-PRO-03' => array('nombre' => 'Erudito', 'coste' => 4, 'desc' => 'Puedes llevar al máximo todas las especializaciones de tus dos Oficios. +25 Puntos de Oficio. -10% tiempo de creación.'),
                'V-PRO-04' => array('nombre' => 'Estudioso', 'coste' => 2, 'desc' => 'Especialización extra de tu Oficio gratis. +20% velocidad de creación.'),
                'V-PRO-05' => array('nombre' => 'Polivalente', 'coste' => 2, 'desc' => 'Oficio extra y su primera especialización. +50 Puntos de Oficio al entrenar.'),
                'V-PRO-06' => array('nombre' => 'Iron Heart', 'coste' => 2, 'desc' => '3 Huecos de Mejora adicionales para implantes.'),
            ),
            'F) Riqueza y Posesiones' => array(
                'V-RIQ-01' => array('nombre' => 'Adinerado 1', 'coste' => 1, 'desc' => '+1,000,000 berries iniciales.'),
                'V-RIQ-02' => array('nombre' => 'Adinerado 2', 'coste' => 2, 'desc' => '+3,000,000 berries iniciales. Requiere Adinerado 1.'),
                'V-RIQ-03' => array('nombre' => 'Adinerado 3', 'coste' => 3, 'desc' => '+10,000,000 berries iniciales. Requiere Adinerado 2.'),
                'V-RIQ-04' => array('nombre' => 'Reliquia Familiar', 'coste' => 2, 'desc' => 'Objeto especial heredado: Caja de Artefacto Tier 1 (contenido determinado por staff).'),
                'V-RIQ-05' => array('nombre' => 'Mascota', 'coste' => 3, 'desc' => 'Animal de compañía Tier 2.', 'spec' => true),
            ),
            'G) Especiales (sujetas a aprobación)' => array(
                'V-ESP-01' => array('nombre' => 'Voz de Todas las Cosas', 'coste' => 5, 'desc' => 'Sientes objetos, criaturas y Poneglyphs. Comunicación con Sea Kings. Requiere aprobación de staff.'),
                'V-ESP-03' => array('nombre' => 'Potencial de Fruta', 'coste' => 1, 'desc' => '+20% de que una Fruta encontrada sea compatible con tu estilo. No es una fruta gratis.'),
                'V-ESP-04' => array('nombre' => 'Fértil', 'coste' => 1, 'desc' => 'Doble (Minks/Gyojin/Sirenas: triple) probabilidad de mellizos o más. Puramente narrativo.'),
            ),
        );
    }
}

if (!function_exists('ope_rol_defectos')) {
    /** Catálogo de defectos (PC que devuelven). */
    function ope_rol_defectos()
    {
        return array(
            'H) Salud y Cuerpo' => array(
                'D-SAL-01' => array('nombre' => 'Ceguera', 'devuelve' => 5, 'desc' => 'Ceguera total. -25 REF efectiva. Incompatible con Tuerto.'),
                'D-SAL-02' => array('nombre' => 'Tuerto', 'devuelve' => 3, 'desc' => 'Pierdes un ojo. -15 REF desde tu lado ciego. Incompatible con Ceguera.'),
                'D-SAL-03' => array('nombre' => 'Sordera', 'devuelve' => 5, 'desc' => 'Pérdida total de audición. -20 REF efectiva.'),
                'D-SAL-04' => array('nombre' => 'Mudez', 'devuelve' => 3, 'desc' => 'No puedes hablar. No lanzas técnicas con incantación verbal.'),
                'D-SAL-05' => array('nombre' => 'Sentidos Disminuidos 1', 'devuelve' => 2, 'desc' => 'Un sentido pobre (vista u oído). -5 REF efectiva.', 'spec' => true),
                'D-SAL-06' => array('nombre' => 'Sentidos Disminuidos 2', 'devuelve' => 5, 'desc' => 'Vista y oído pobres. -5 REF por cada uno.'),
                'D-SAL-07' => array('nombre' => 'Amputación de Brazo', 'devuelve' => 4, 'desc' => '-20 DES efectiva para acciones a dos manos.', 'spec' => true),
                'D-SAL-08' => array('nombre' => 'Amputación de Pierna', 'devuelve' => 5, 'desc' => '-20 AGI efectiva para movimiento.', 'spec' => true),
                'D-SAL-09' => array('nombre' => 'Desplumado', 'devuelve' => 3, 'desc' => 'Sin alas (Skypiean pierde Planeo Celestial). Requiere aprobación de staff.'),
                'D-SAL-10' => array('nombre' => 'Narcoléptico', 'devuelve' => 4, 'desc' => 'Cada 5 posts fuera de combate, te duermes 1 post. En combate, -30 REF cada 5 posts.'),
                'D-SAL-11' => array('nombre' => 'Endeble', 'devuelve' => 3, 'desc' => '+50% distancia al ser empujado. +25% daño por empujes y caídas.'),
                'D-SAL-12' => array('nombre' => 'Anticoagulante', 'devuelve' => 2, 'desc' => 'Las hemorragias duran 1 turno adicional.'),
            ),
            'I) Salud Mental y Personalidad' => array(
                'D-PSI-01' => array('nombre' => 'Fobia 1 (poco común)', 'devuelve' => 1, 'desc' => '-20 VOL en presencia de tu miedo.', 'spec' => true),
                'D-PSI-02' => array('nombre' => 'Fobia 2 (común)', 'devuelve' => 2, 'desc' => '-20 VOL en presencia de tu miedo.', 'spec' => true),
                'D-PSI-03' => array('nombre' => 'Fobia 3 (muy común)', 'devuelve' => 3, 'desc' => '-20 VOL en presencia de tu miedo.', 'spec' => true),
                'D-PSI-04' => array('nombre' => 'Adicción', 'devuelve' => 3, 'desc' => 'Cada 5 turnos sin consumir: -25 VOL acumulativo.', 'spec' => true),
                'D-PSI-05' => array('nombre' => 'Gula', 'devuelve' => 3, 'desc' => 'Cada 5 turnos sin comer: -25 VOL acumulativo. Incompatible con Sin Hambre.'),
                'D-PSI-06' => array('nombre' => 'Vicio', 'devuelve' => 1, 'desc' => 'Cada 5 posts sin satisfacerlo: -10 VOL (no acumulativo). Incompatible con Adicción.', 'spec' => true),
                'D-PSI-07' => array('nombre' => 'TOC', 'devuelve' => 3, 'desc' => 'Acto repetitivo cada 3 posts. Si no: -10 VOL acumulativo.', 'spec' => true),
                'D-PSI-08' => array('nombre' => 'TOC 2', 'devuelve' => 4, 'desc' => 'Como TOC, pero incapacitante e interrumpe tu acción actual.', 'spec' => true),
                'D-PSI-09' => array('nombre' => 'Múltiple Personalidad', 'devuelve' => 1, 'desc' => 'Un disparador cambia tu personalidad.', 'spec' => true),
                'D-PSI-10' => array('nombre' => 'Personalidad Opuesta', 'devuelve' => 2, 'desc' => 'Un suceso común activa una personalidad opuesta.', 'spec' => true),
                'D-PSI-11' => array('nombre' => 'Trastorno Histriónico', 'devuelve' => 2, 'desc' => 'Necesidad constante de ser el centro de atención.'),
                'D-PSI-12' => array('nombre' => 'Pesimista', 'devuelve' => 3, 'desc' => '-10 VOL permanente. Inmune a efectos que alteren tu ánimo.'),
                'D-PSI-13' => array('nombre' => 'Crédulo', 'devuelve' => 3, 'desc' => 'Te crees casi todo lo que te dicen.'),
            ),
            'J) Comportamiento y Convicción' => array(
                'D-COM-01' => array('nombre' => 'Héroe', 'devuelve' => 5, 'desc' => 'No puedes evitar ayudar a quien lo necesita. Sin excepciones.'),
                'D-COM-02' => array('nombre' => 'Nunca Rendirse', 'devuelve' => 3, 'desc' => 'Jamás huyes de un combate que tú empezaste.'),
                'D-COM-03' => array('nombre' => 'Piadoso', 'devuelve' => 3, 'desc' => 'No puedes matar. Si matas por accidente: -20 VOL.'),
                'D-COM-04' => array('nombre' => 'Caballerosidad', 'devuelve' => 4, 'desc' => 'Incapaz de agredir a un género, bajo ninguna circunstancia.', 'spec' => true),
                'D-COM-05' => array('nombre' => 'Lealtad a la Facción', 'devuelve' => 2, 'desc' => 'No puedes cambiar de facción. -10 VOL permanente si te expulsan o traicionas.'),
                'D-COM-06' => array('nombre' => 'Nakama', 'devuelve' => 1, 'desc' => 'Fidelidad extrema a tu primera banda pirata. Solo para Piratas.'),
                'D-COM-07' => array('nombre' => 'Enemigo Declarado', 'devuelve' => 4, 'desc' => 'Odio visceral hacia una facción, sin poder contener tus impulsos.', 'spec' => true),
                'D-COM-08' => array('nombre' => 'Esclavo de los Dioses', 'devuelve' => 2, 'desc' => 'Serviste como esclavo de los Dragones Celestiales. Portas su marca.'),
                'D-COM-09' => array('nombre' => 'Excluyente', 'devuelve' => 1, 'desc' => 'Este personaje consume toda tu atención: no puedes tener multicuenta mientras exista.'),
                'D-COM-10' => array('nombre' => 'Voto de Silencio', 'devuelve' => 3, 'desc' => 'No puedes decir ninguna palabra bajo ningún concepto.'),
            ),
            'K) Sociales y Económicos' => array(
                'D-SOC-01' => array('nombre' => 'Fealdad', 'devuelve' => 1, 'desc' => '-1 CAR efectiva donde la apariencia importe. Incompatible con Belleza.'),
                'D-SOC-02' => array('nombre' => 'Bocazas', 'devuelve' => 2, 'desc' => 'No puedes dejar pasar una ofensa sin responder.'),
                'D-SOC-03' => array('nombre' => 'Irritante', 'devuelve' => 2, 'desc' => '-1 CAR efectiva en primeras impresiones.'),
                'D-SOC-04' => array('nombre' => 'Don Nadie', 'devuelve' => 2, 'desc' => '-10% a todas las ganancias de Reputación. Incompatible con Fama.'),
                'D-SOC-05' => array('nombre' => 'Analfabeto', 'devuelve' => 3, 'desc' => 'No sabes leer ni escribir. Sin acceso a información escrita.'),
                'D-SOC-06' => array('nombre' => 'Ignorante', 'devuelve' => 2, 'desc' => 'Sin conocimientos básicos de historia, geografía o facciones.'),
                'D-SOC-07' => array('nombre' => 'Olvidadizo', 'devuelve' => 2, 'desc' => 'Una vez por tema/día, 1d6: con un 1 olvidas un dato crucial.'),
                'D-SOC-08' => array('nombre' => 'Tic Verbal', 'devuelve' => 1, 'desc' => 'Todas tus frases terminan con una muletilla.', 'spec' => true),
            ),
            'L) Económicos y de Progresión' => array(
                'D-ECO-01' => array('nombre' => 'Pobre 1', 'devuelve' => 1, 'desc' => 'Todo lo que compras cuesta +5%. Incompatible con Adinerado.'),
                'D-ECO-02' => array('nombre' => 'Pobre 2', 'devuelve' => 2, 'desc' => '+10% al coste de todo. Incompatible con Adinerado.'),
                'D-ECO-03' => array('nombre' => 'Pobre 3', 'devuelve' => 3, 'desc' => '+15% al coste de todo. Incompatible con Adinerado.'),
                'D-ECO-04' => array('nombre' => 'Sin Oficio', 'devuelve' => 5, 'desc' => 'No puedes aprender ningún Oficio.'),
                'D-ECO-05' => array('nombre' => 'Cuerpo Puro', 'devuelve' => 4, 'desc' => 'Tu cuerpo rechaza implantes cyborg y biológicos.'),
                'D-ECO-06' => array('nombre' => 'Incompatible', 'devuelve' => 2, 'desc' => 'Pierdes 3 Huecos de Mejora. Incompatible con Cuerpo Puro.'),
                'D-ECO-07' => array('nombre' => 'Desganado', 'devuelve' => 1, 'desc' => '-5 Puntos de Energía máxima por Nivel de personaje.'),
                'D-ECO-08' => array('nombre' => 'Vinimos a Jugar', 'devuelve' => 3, 'desc' => 'Al morir, 20% menos de recursos con el Tutorial de Muerte.'),
            ),
            'M) Salud Física' => array(
                'D-FIS-01' => array('nombre' => 'Debilidad al Calor', 'devuelve' => 1, 'desc' => '-25 a Resistencia contra efectos de calor. Incompatible con Fortaleza al Calor.'),
                'D-FIS-02' => array('nombre' => 'Debilidad al Frío', 'devuelve' => 1, 'desc' => '-25 a Resistencia contra efectos de frío. Incompatible con Fortaleza al Frío.'),
                'D-FIS-03' => array('nombre' => 'Debilidad al Veneno', 'devuelve' => 4, 'desc' => '+1 nivel a los Envenenamientos. +20% daño por veneno.'),
                'D-FIS-04' => array('nombre' => 'Alergia 1 (poco común)', 'devuelve' => 2, 'desc' => 'Al contacto: -10 REF, luego -10 VOL por turno.', 'spec' => true),
                'D-FIS-05' => array('nombre' => 'Alergia 2 (común)', 'devuelve' => 3, 'desc' => 'Al contacto: -10 REF, luego -10 VOL por turno.', 'spec' => true),
                'D-FIS-06' => array('nombre' => 'Alergia 3 (muy común)', 'devuelve' => 4, 'desc' => 'Al contacto: -10 REF, luego -10 VOL por turno.', 'spec' => true),
                'D-FIS-07' => array('nombre' => 'Desorientación', 'devuelve' => 1, 'desc' => '-3 a tiradas de navegación y viajes navales.'),
                'D-FIS-08' => array('nombre' => 'Somnoliento', 'devuelve' => 2, 'desc' => 'Al despertar, Entumecimiento durante 3 posts.'),
                'D-FIS-09' => array('nombre' => 'Mal Paladar', 'devuelve' => 2, 'desc' => 'Los efectos beneficiosos de las comidas se reducen un 50%.'),
                'D-FIS-10' => array('nombre' => 'Ausencia de Tacto', 'devuelve' => 3, 'desc' => 'Sin sensibilidad en parte del cuerpo. Puramente narrativo.', 'spec' => true),
            ),
        );
    }
}

if (!function_exists('ope_rol_pc_iniciales')) {
    function ope_rol_pc_iniciales() { return 6; }
}

if (!function_exists('ope_rol_berries_iniciales')) {
    function ope_rol_berries_iniciales() { return 50000; }
}

if (!function_exists('ope_rol_find_virtud')) {
    function ope_rol_find_virtud($id)
    {
        foreach (ope_rol_virtudes() as $cat) {
            if (isset($cat[$id])) return $cat[$id];
        }
        return null;
    }
}

if (!function_exists('ope_rol_find_defecto')) {
    function ope_rol_find_defecto($id)
    {
        foreach (ope_rol_defectos() as $cat) {
            if (isset($cat[$id])) return $cat[$id];
        }
        return null;
    }
}

// ─────────────────────────────────────────────────────────────
// CARTAS DE TÉCNICA (INI-03) — catálogo de las 6 categorías de tags
// y de los 5 tiers. Fuente única de verdad para el creador de cartas
// (gestionar-cartas.php) y el render del deck en la ficha.
// ─────────────────────────────────────────────────────────────

if (!function_exists('ope_rol_tecnica_tags')) {
    /**
     * Las 6 categorías de tags de una carta de técnica.
     *   key      → identificador de la categoría (se guarda en JSON)
     *   nombre   → etiqueta visible
     *   pregunta → ayuda contextual
     *   multi    → true = varios tags; false = uno solo (radio)
     *   max      → tope de tags cuando multi (0 = sin tope)
     *   accent   → variable CSS de color de acento
     *   tags     → id => etiqueta visible
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
            1 => array('romano' => 'I',   'rango' => 'F – D',    'pp' => 5,  'pa' => '1 – 2', 'en' => '5 – 10',  'reposo' => '0',            'dados' => '1d6 a 1d8 + stat', 'pa_def' => 1, 'en_def' => 8,  'reposo_def' => 0, 'dados_def' => '1d8 + FUE'),
            2 => array('romano' => 'II',  'rango' => 'D – C',    'pp' => 8,  'pa' => '2',     'en' => '10 – 15', 'reposo' => '1',            'dados' => '1d10 a 2d6 + stat', 'pa_def' => 2, 'en_def' => 12, 'reposo_def' => 1, 'dados_def' => '1d10 + FUE'),
            3 => array('romano' => 'III', 'rango' => 'B – A',    'pp' => 12, 'pa' => '2 – 3', 'en' => '15 – 25', 'reposo' => '1 – 2',        'dados' => '2d8 + stat', 'pa_def' => 3, 'en_def' => 20, 'reposo_def' => 2, 'dados_def' => '2d8 + FUE'),
            4 => array('romano' => 'IV',  'rango' => 'S',        'pp' => 18, 'pa' => '3 – 4', 'en' => '25 – 40', 'reposo' => '2',            'dados' => '3d8 a 4d6 + stat', 'pa_def' => 4, 'en_def' => 32, 'reposo_def' => 2, 'dados_def' => '3d8 + FUE'),
            5 => array('romano' => 'V',   'rango' => 'SS – M+',  'pp' => 25, 'pa' => '4 – 5', 'en' => '40 – 60', 'reposo' => '3 / Escena',   'dados' => '4d8 a 5d6 + stat', 'pa_def' => 5, 'en_def' => 50, 'reposo_def' => 3, 'dados_def' => '4d8 + FUE'),
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
