<?php
/**
 * Seed script: resetea Mundo Vivo a estado estándar y puebla datos demo.
 * Ejecutar: php scripts/seed-mv-demo.php
 */

define('IN_MYBB', 1);
require_once __DIR__ . '/../inc/init.php';

$PREFIX = TABLE_PREFIX;

echo "=== RESETEANDO MUNDO VIVO ===\n\n";

// ──────────────────────────────────────────────────────
// 1. Reset zonas a valores base
// ──────────────────────────────────────────────────────
echo "1. Reset zonas -> base\n";
$zbase = array(
    'east-blue'  => array('cli'=>65,'pel'=>20,'riq'=>55,'civ'=>60,'mar'=>50,'pir'=>35,'rev'=>15,'inf'=>15,'est'=>60,'ten'=>25),
    'west-blue'  => array('cli'=>60,'pel'=>30,'riq'=>50,'civ'=>50,'mar'=>45,'pir'=>30,'rev'=>30,'inf'=>25,'est'=>58,'ten'=>30),
    'north-blue' => array('cli'=>55,'pel'=>35,'riq'=>55,'civ'=>60,'mar'=>55,'pir'=>40,'rev'=>20,'inf'=>20,'est'=>62,'ten'=>30),
    'south-blue' => array('cli'=>60,'pel'=>45,'riq'=>40,'civ'=>35,'mar'=>35,'pir'=>55,'rev'=>35,'inf'=>35,'est'=>54,'ten'=>40),
    'calm-belt'  => array('cli'=>80,'pel'=>95,'riq'=>20,'civ'=>25,'mar'=>10,'pir'=>20,'rev'=>5,'inf'=>10,'est'=>50,'ten'=>15),
    'red-line'   => array('cli'=>50,'pel'=>40,'riq'=>70,'civ'=>80,'mar'=>80,'pir'=>10,'rev'=>10,'inf'=>15,'est'=>70,'ten'=>20),
    'paraiso'    => array('cli'=>55,'pel'=>55,'riq'=>60,'civ'=>45,'mar'=>55,'pir'=>50,'rev'=>25,'inf'=>30,'est'=>55,'ten'=>45),
    'new-world'  => array('cli'=>35,'pel'=>85,'riq'=>55,'civ'=>25,'mar'=>25,'pir'=>80,'rev'=>40,'inf'=>45,'est'=>45,'ten'=>55),
);
foreach ($zbase as $slug => $m) {
    $s = $db->escape_string($slug);
    $db->write_query("UPDATE {$PREFIX}rol_mv_zonas SET cli={$m['cli']}, pel={$m['pel']}, riq={$m['riq']}, civ={$m['civ']}, mar={$m['mar']}, pir={$m['pir']}, rev={$m['rev']}, inf={$m['inf']}, est={$m['est']}, ten={$m['ten']}, notas='' WHERE slug='$s'");
}
echo "  8 zonas reseteadas.\n";

// ──────────────────────────────────────────────────────
// 2. Reset facciones a valores base
// ──────────────────────────────────────────────────────
echo "2. Reset facciones -> base\n";
$fbase = array(
    'marine'           => array('rep'=>40,'coh'=>80,'mil'=>85,'pol'=>80,'eco'=>75,'mor'=>70,'alc'=>80),
    'pirata'           => array('rep'=>-10,'coh'=>50,'mil'=>65,'pol'=>25,'eco'=>50,'mor'=>65,'alc'=>60),
    'revolucionario'   => array('rep'=>20,'coh'=>65,'mil'=>55,'pol'=>45,'eco'=>40,'mor'=>70,'alc'=>40),
    'gobierno'         => array('rep'=>10,'coh'=>70,'mil'=>80,'pol'=>95,'eco'=>85,'mor'=>65,'alc'=>85),
    'cazarrecompensas' => array('rep'=>30,'coh'=>50,'mil'=>50,'pol'=>20,'eco'=>55,'mor'=>60,'alc'=>35),
    'civil'            => array('rep'=>40,'coh'=>55,'mil'=>10,'pol'=>30,'eco'=>50,'mor'=>60,'alc'=>50),
);
foreach ($fbase as $slug => $m) {
    $s = $db->escape_string($slug);
    $db->write_query("UPDATE {$PREFIX}rol_mv_facciones SET rep={$m['rep']}, coh={$m['coh']}, mil={$m['mil']}, pol={$m['pol']}, eco={$m['eco']}, mor={$m['mor']}, alc={$m['alc']}, notas='' WHERE slug='$s'");
}
echo "  6 facciones reseteadas.\n";

// ──────────────────────────────────────────────────────
// 3. Limpiar tension y sembrar pares base
// ──────────────────────────────────────────────────────
echo "3. Reset tension -> base\n";
$db->write_query("DELETE FROM {$PREFIX}rol_mv_tension WHERE 1=1");
$tpares = array(
    array('east-blue',  'marine|pirata',        'marine','pirata', 65, 'Piratas del East Blue hostigando rutas comerciales; la Marina responde con patrullas'),
    array('west-blue',  'marine|pirata',        'marine','pirata', 45, 'Piratas menores operan en islas perifericas; tension moderada'),
    array('north-blue', 'gobierno|revolucionario','gobierno','revolucionario', 55, 'Celulas revolucionarias activas en varias islas'),
    array('south-blue', 'pirata|cazarrecompensas','pirata','cazarrecompensas', 50, 'Cazarrecompensas acechan a piratas sure?os con recompensas crecientes'),
    array('calm-belt',  'pirata|marine',        'pirata','marine', 30, 'Zona de calma relativa; pocos incidentes reportados'),
    array('red-line',   'gobierno|marine',      'gobierno','marine', 20, 'La Gobernanza Mundial mantiene control ferreo; tension baja'),
    array('paraiso',    'pirata|marine',        'pirata','marine', 60, 'Piratas novatos chocan con la Marina en la primera mitad de la Grand Line'),
    array('new-world',  'pirata|marine',        'pirata','marine', 75, 'Los emperadores piratas compiten por territorio; la Marina despliega fuerzas'),
);
foreach ($tpares as $p) {
    $db->insert_query('rol_mv_tension', array(
        'zona_slug' => $p[0],
        'par'       => $p[1],
        'a_slug'    => $p[2],
        'b_slug'    => $p[3],
        'valor'     => $p[4],
        'notas'     => $p[5],
    ));
}
echo '  ' . count($tpares) . " pares de tension sembrados.\n";

// ──────────────────────────────────────────────────────
// 4. Resetear ciclo actual
// ──────────────────────────────────────────────────────
echo "4. Reset ciclo actual -> abierto\n";

// Borrar noticia asociada al ciclo publicado
$db->write_query("DELETE FROM {$PREFIX}rol_mv_noticias WHERE 1=1");

$periodo = date('Y-m');
$esc_periodo = $db->escape_string($periodo);

// Buscar ciclo existente para este periodo
$existente = $db->fetch_array($db->simple_select('rol_mv_ciclos', 'ciclo_id, estado', "periodo='$esc_periodo'"));
if ($existente) {
    // Reutilizar: resetear a abierto y limpiar
    $db->write_query("UPDATE {$PREFIX}rol_mv_ciclos SET estado='abierto', indicaciones='', prompt='', resultado_raw='', periodico_html='', estado_json='', noticia_titulo='', noticia_html='', imagenes_json='', threads_json='', nav_resumen='', published_at=0 WHERE periodo='$esc_periodo'");
    echo "  Ciclo $periodo reutilizado ($existente[estado] -> abierto).\n";
} else {
    // Crear nuevo
    $db->write_query("UPDATE {$PREFIX}rol_mv_ciclos SET estado='archivado' WHERE estado='abierto' OR estado='preview' OR estado='publicado'");
    $db->insert_query('rol_mv_ciclos', array(
        'periodo' => $esc_periodo,
        'estado'  => 'abierto',
        'dateline' => (int)TIME_NOW,
    ));
    echo "  Nuevo ciclo $periodo (abierto) creado.\n";
}

// Obtener el ciclo actual (recien creado o reutilizado)
$ciclo = ope_rol_mv_ciclo_actual();
$cid = (int)($ciclo['ciclo_id'] ?? 0);
echo "  Ciclo ID=$cid periodo={$ciclo['periodo']}\n";

// ──────────────────────────────────────────────────────
// 5. Crear NPCs mayores
// ──────────────────────────────────────────────────────
echo "5. Crear NPCs mayores demo\n";
// Primero, eliminar NPCs demo anteriores
$db->write_query("DELETE FROM {$PREFIX}rol_personajes WHERE es_npc=1 AND nombre LIKE '[Demo]%'");

$npcs = array(
    array(
        'nombre' => '[Demo] Almirante Morgan Drakos',
        'rango' => 'Alm.',
        'mundo_zona' => 'east-blue',
        'mundo_ubic' => 'Cuartel General Marineford',
        'mundo_accion' => 'Supervisando patrullas en East Blue',
        'mundo_estado_np' => 'Activo',
        'faccion' => 'marine',
        'datos_publicos' => array(
            'titulos' => array('Almirante de Flota', 'Pu?o de Acero'),
            'descripcion' => 'Veterano de guerra con mas de 30 a?os de servicio. Su presencia impone respeto en cualquier puerto.',
            'historia' => 'Comenzo su carrera como soldado raso en East Blue y fue ascendiendo hasta Almirante.',
            'relaciones' => array('Garp - mentor', 'Sengoku - superior'),
            'ubicacion_visible' => 'Marineford',
        ),
        'datos_internos' => array(
            'personalidad' => array('justicia'=>85,'astucia'=>70,'agresividad'=>60,'honor'=>90,'ambicion'=>50,'empatia'=>40),
            'metas' => array('Erradicar la pirateria en East Blue', 'Formar una nueva generacion de marines'),
            'tracking' => array('salud'=>95,'moral'=>80,'plan_activo'=>'Coordinar patrullas navales','meta_actual'=>'Reducir la actividad pirata un 30%'),
        ),
    ),
    array(
        'nombre' => '[Demo] Capitana Elara Ventisca',
        'rango' => 'Cap.',
        'mundo_zona' => 'new-world',
        'mundo_ubic' => 'Isla Bruma, Nuevo Mundo',
        'mundo_accion' => 'Reclutando tripulacion en tabernas locales',
        'mundo_estado_np' => 'Activo, precaucion',
        'faccion' => 'pirata',
        'datos_publicos' => array(
            'titulos' => array('La Bruja del Norte', 'Capitana de los Lobos de Escarcha'),
            'descripcion' => 'Pirata astuta y carismatica. Su tripulacion es leal hasta la muerte.',
            'historia' => 'Navegante nata, zarpo desde el North Blue buscando el One Piece.',
            'relaciones' => array('Shanks - respetado', 'Kaido - enemistad'),
            'ubicacion_visible' => 'Isla Bruma, Nuevo Mundo',
        ),
        'datos_internos' => array(
            'personalidad' => array('justicia'=>50,'astucia'=>85,'agresividad'=>65,'honor'=>75,'ambicion'=>80,'empatia'=>60),
            'metas' => array('Llegar a Laugh Tale', 'Proteger a su tripulacion'),
            'tracking' => array('salud'=>100,'moral'=>75,'plan_activo'=>'Reclutar nuevos miembros en Isla Bruma','meta_actual'=>'Encontrar un Navegante experimentado'),
        ),
    ),
    array(
        'nombre' => '[Demo] Comandante Artorius',
        'rango' => 'Cdor',
        'mundo_zona' => 'south-blue',
        'mundo_ubic' => 'Base Revolucionaria, South Blue',
        'mundo_accion' => 'Entrenando reclutas para la causa',
        'mundo_estado_np' => 'Activo, herido leve',
        'faccion' => 'revolucionario',
        'datos_publicos' => array(
            'titulos' => array('Pu?o de la Liberacion', 'Comandante del Sur'),
            'descripcion' => 'Antiguo noble que abandono su titulo para unirse a los revolucionarios.',
            'historia' => 'Nacio en una familia noble del South Blue pero lo abandono todo al presenciar la crueldad del Gobierno Mundial.',
            'relaciones' => array('Dragon - lider', 'Ivonkov - aliado'),
            'ubicacion_visible' => 'Base Revolucionaria del Sur',
        ),
        'datos_internos' => array(
            'personalidad' => array('justicia'=>95,'astucia'=>60,'agresividad'=>55,'honor'=>80,'ambicion'=>40,'empatia'=>85),
            'metas' => array('Liberar los reinos oprimidos del South Blue', 'Reclutar simpatizantes'),
            'tracking' => array('salud'=>75,'moral'=>70,'plan_activo'=>'Preparar incursion en isla bajo regimen tirano','meta_actual'=>'Reunir informacion sobre el Gobierno'),
        ),
    ),
    array(
        'nombre' => '[Demo] Sombra Nocturna',
        'rango' => 'Inf.',
        'mundo_zona' => 'north-blue',
        'mundo_ubic' => 'Distrito del Puerto, Ciudad Esmeralda',
        'mundo_accion' => 'Tejiendo redes de informacion',
        'mundo_estado_np' => 'Oculto',
        'faccion' => 'civil',
        'datos_publicos' => array(
            'titulos' => array('La Sombra', 'Ojos del Norte'),
            'descripcion' => 'Misteriosa figura del inframundo cuyos contactos llegan a todos los rincones.',
            'historia' => 'Resurgio como la mayor traficante de informacion del North Blue.',
            'relaciones' => array('JP Morgans - aliado comercial', 'CP-0 - interes mutuo'),
            'ubicacion_visible' => 'North Blue (ubicacion exacta desconocida)',
        ),
        'datos_internos' => array(
            'personalidad' => array('justicia'=>30,'astucia'=>95,'agresividad'=>20,'honor'=>35,'ambicion'=>70,'empatia'=>25),
            'metas' => array('Acumular poder a traves de la informacion', 'Controlar el mercado negro del Norte'),
            'tracking' => array('salud'=>100,'moral'=>85,'plan_activo'=>'Negociar venta de informacion sobre la Marina','meta_actual'=>'Averiguar el paradero de un ex agente del CP-9'),
        ),
    ),
);

foreach ($npcs as $npc) {
    $datos = json_encode(array('faccion' => $npc['faccion']), JSON_UNESCAPED_UNICODE);
    $datos_publicos = json_encode($npc['datos_publicos'], JSON_UNESCAPED_UNICODE);
    $datos_internos = json_encode($npc['datos_internos'], JSON_UNESCAPED_UNICODE);
    $db->insert_query('rol_personajes', array(
        'nombre'         => $db->escape_string($npc['nombre']),
        'rango'          => $db->escape_string($npc['rango']),
        'es_npc'         => 1,
        'mundo_zona'     => $db->escape_string($npc['mundo_zona']),
        'mundo_ubic'     => $db->escape_string($npc['mundo_ubic']),
        'mundo_accion'   => $db->escape_string($npc['mundo_accion']),
        'mundo_estado_np' => $db->escape_string($npc['mundo_estado_np']),
        'datos'          => $db->escape_string($datos),
        'datos_publicos' => $db->escape_string($datos_publicos),
        'datos_internos' => $db->escape_string($datos_internos),
        'uid'            => 0,
        'estado'         => 'aprobado',
    ));
    echo "  + {$npc['nombre']}\n";
}

// ──────────────────────────────────────────────────────
// 6. Crear misiones en curso demo (con campos del tablón)
// ──────────────────────────────────────────────────────
echo "6. Crear misiones demo\n";
$db->write_query("DELETE FROM {$PREFIX}rol_mv_misiones WHERE 1=1");

$misiones = array(
    array(
        'titulo'           => 'El cargamento perdido de la Marine',
        'resumen'          => 'Un barco de suministros de la Marine desapareció cerca de las islas del East Blue.',
        'descripcion_larga' => 'El HMS Cobalto, un transporte de suministros con destino a Marineford, desapareció sin dejar rastro cerca del archipiélago de las Nieblas. La inteligencia de la Marine sospecha que los piratas del filibustero «Garrapata» Rhodes lo abordaron. La misión consiste en localizar los restos del barco, recuperar la carga (armas, medicinas y correspondencia clasificada) y, si es posible, capturar a Rhodes. Cualquier tripulación que preste ayuda recibirá el agradecimiento formal de la Marina y una condecoración al mérito.',
        'zona_slug'        => 'east-blue',
        'facciones'        => 'marine',
        'rango'            => 'C',
        'peligrosidad'     => 2,
        'recompensa'       => '80.000 berries + condecoración',
        'modalidad'        => 'cualquiera',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'Cacería del Kraken de Calm Belt',
        'resumen'          => 'Una criatura masiva está atacando los barcos que cruzan el Calm Belt.',
        'descripcion_larga' => 'Los pocos supervivientes que han escapado con vida describen una bestia con tentáculos gruesos como mástiles que emerge de las aguas muertas del Calm Belt. El gremio de cazarrecompensas ha puesto precio a su cráneo. No bastará con una tripulación pequeña: hará falta coordinación, un navío resistente y agallas para adentrarse en la zona más silenciosa del mundo. Quien lo derrote se ganará un nombre entre los cazadores más temidos.',
        'zona_slug'        => 'calm-belt',
        'facciones'        => 'cazarrecompensas',
        'rango'            => 'A',
        'peligrosidad'     => 5,
        'recompensa'       => '350.000 berries + trofeo',
        'modalidad'        => 'grupo',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'Infiltración en el Gobierno Mundial',
        'resumen'          => 'Los revolucionarios necesitan información sobre una nueva arma secreta en la Red Line.',
        'descripcion_larga' => 'La Inteligencia Revolucionaria ha confirmado la existencia del Proyecto Aegis: un arma de destrucción masiva que el Gobierno Mundial está desarrollando en una base encubierta bajo la Red Line. Acceder a los planos requiere burlar seguridad de nivel CP-0, esquivar rastreadores de frutas del diablo y extraer la información sin dejar rastro. Esta misión es solo para los más sigilosos; un error significaría ejecución sin juicio.',
        'zona_slug'        => 'red-line',
        'facciones'        => 'revolucionario',
        'rango'            => 'S',
        'peligrosidad'     => 5,
        'recompensa'       => '500.000 berries + rango revolucionario',
        'modalidad'        => 'solo',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'Ruta comercial del West Blue',
        'resumen'          => 'Ataques piratas están interrumpiendo el comercio en el West Blue.',
        'descripcion_larga' => 'La Cámara de Comercio de Crescent City ha perdido tres cargamentos en las últimas dos semanas. Los atacantes—una banda pirata liderada por «Dientes de Plata» Morgan—operan desde una base oculta en el archipiélago Coral. Escolta los mercantes hasta su destino o, mejor aún, localiza la base y acaba con la amenaza de raíz. Los comerciantes pagan bien y rápido.',
        'zona_slug'        => 'west-blue',
        'facciones'        => 'civil',
        'rango'            => 'B',
        'peligrosidad'     => 3,
        'recompensa'       => '150.000 berries',
        'modalidad'        => 'cualquiera',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'Tormenta de fuego en el Nuevo Mundo',
        'resumen'          => 'Una isla del Nuevo Mundo sufre fenómenos climáticos extremos.',
        'descripcion_larga' => 'El volcán de la Isla Pyroclasto ha despertado y con él, tormentas de ceniza y relámpagos que están devastando la costa. Los ancianos del lugar hablan de un «corazón de magma» que debe ser apaciguado en la cima del volcán. El sabio Kael, un anciano conocedor de los antiguos rituales, necesita escolta hasta el santuario cumbre. El camino está lleno de criaturas enloquecidas por la erupción. Lleguen antes de que la isla entera se hunda.',
        'zona_slug'        => 'new-world',
        'facciones'        => 'pirata',
        'rango'            => 'A',
        'peligrosidad'     => 4,
        'recompensa'       => '280.000 berries + fruta del diablo menor',
        'modalidad'        => 'grupo',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'El tesoro del capitán sombra',
        'resumen'          => 'Un mapa lleva al tesoro del legendario Capitán Sombra en el North Blue.',
        'descripcion_larga' => 'El mapa, garabateado en piel de sirena, marca una ruta tortuosa a través de los bancos de niebla del North Blue hasta la Cueva del Cráneo. El Capitán Sombra—un pirata que aterrorizó estas aguas hace cuarenta años—escondió su botín allí. Pero la cueva está protegida por trampas, corrientes traicioneras y, según dicen, el espíritu del propio Sombra. Piratas y cazarrecompensas ya han empezado a moverse. El que llegue primero se lleva todo.',
        'zona_slug'        => 'north-blue',
        'facciones'        => 'pirata,cazarrecompensas',
        'rango'            => 'B',
        'peligrosidad'     => 3,
        'recompensa'       => '200.000 berries + mapa del tesoro',
        'modalidad'        => 'cualquiera',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'Paz en South Blue',
        'resumen'          => 'La Marine busca mediadores para evitar una guerra civil en South Blue.',
        'descripcion_larga' => 'La isla de Valdoria está partida en dos: los Lealistas, fieles al rey títere del Gobierno Mundial, y los Libres, que exigen autogobierno. La tensión estallará en guerra abierta de un momento a otro. La Marine necesita interlocutores neutrales—que no pertenezcan a ninguna facción beligerante—para sentar a ambas partes en una mesa de negociación. La misión es diplomática, no combate, pero en una isla al borde de la guerra civil, la diplomacia puede ser más peligrosa que una espada.',
        'zona_slug'        => 'south-blue',
        'facciones'        => 'marine',
        'rango'            => 'C',
        'peligrosidad'     => 2,
        'recompensa'       => '100.000 berries',
        'modalidad'        => 'cualquiera',
        'estado'           => 'en_curso',
    ),
    array(
        'titulo'           => 'El laboratorio secreto de Paraíso',
        'resumen'          => 'Científicos del Gobierno experimentan ilegalmente con frutas del diablo en Paraíso.',
        'descripcion_larga' => 'Un encapuchado ha dejado un mensaje cifrado en la taberna del Puerto Loto: «En la isla sin nombre, al este de la Séptima Estación, están jugando a ser dioses. Vienen y van en barcos sin bandera. He visto jaulas. He oído gritos.» El laboratorio del Dr. Vyeril—un científico caído en desgracia—opera fuera de la jurisdicción de la Marine. Su paradero exacto es desconocido. Se necesita a alguien que investigue, documente y, si es posible, libere a los sujetos de prueba.',
        'zona_slug'        => 'paraiso',
        'facciones'        => 'gobierno',
        'rango'            => 'D',
        'peligrosidad'     => 1,
        'recompensa'       => '50.000 berries + información clasificada',
        'modalidad'        => 'solo',
        'estado'           => 'en_curso',
    ),
);

foreach ($misiones as $m) {
    $db->insert_query('rol_mv_misiones', array(
        'ciclo_id'        => $cid,
        'titulo'          => $db->escape_string($m['titulo']),
        'resumen'         => $db->escape_string($m['resumen']),
        'descripcion_larga' => $db->escape_string($m['descripcion_larga'] ?? $m['resumen']),
        'zona_slug'       => $db->escape_string($m['zona_slug']),
        'facciones'       => $db->escape_string($m['facciones']),
        'rango'           => $db->escape_string($m['rango']),
        'peligrosidad'    => (int)$m['peligrosidad'],
        'recompensa'      => $db->escape_string($m['recompensa']),
        'modalidad'       => $db->escape_string($m['modalidad']),
        'estado'          => $db->escape_string($m['estado']),
        'dateline'        => (int)TIME_NOW,
    ));
    echo "  + {$m['titulo']}\n";
}

// ──────────────────────────────────────────────────────
// 7. Crear eventos (presentes notificados)
// ──────────────────────────────────────────────────────
echo "7. Crear eventos demo\n";
$db->write_query("DELETE FROM {$PREFIX}rol_mv_eventos WHERE 1=1");

$eventos = array(
    array(
        'titulo' => 'Avistamiento de una nave fantasma en East Blue',
        'zona_slug' => 'east-blue',
        'resumen' => 'Varios pescadores reportaron haber visto un barco sin tripulacion navegando a la deriva cerca de la isla Foosha. Al abordarlo, encontraron pertenencias del famoso pirata desaparecido "Barbablanca Jr."',
        'tipo_suceso' => 'S-02',
        'pe_estimado' => 5,
    ),
    array(
        'titulo' => 'Reunion secreta de revolucionarios en South Blue',
        'zona_slug' => 'south-blue',
        'resumen' => 'Agentes del Gobierno Mundial descubrieron una reunion de celulas revolucionarias en una isla remota del South Blue. Planean una operacion a gran escala.',
        'tipo_suceso' => 'S-04',
        'pe_estimado' => 7,
    ),
    array(
        'titulo' => 'Tormenta inusual en Calm Belt desvia barcos',
        'zona_slug' => 'calm-belt',
        'resumen' => 'Una tormenta inusualmente violenta en el Calm Belt ha desviado varias rutas comerciales. Los mares tranquilos se han vuelto traicioneros.',
        'tipo_suceso' => 'S-01',
        'pe_estimado' => 3,
    ),
    array(
        'titulo' => 'El gremio de cazarrecompensas ofrece nuevas recompensas',
        'zona_slug' => 'paraiso',
        'resumen' => 'El gremio de cazarrecompensas ha publicado una nueva lista de objetivos en Paradise. Incluye varios piratas novatos con recompensas entre 10 y 50 millones de berries.',
        'tipo_suceso' => 'S-06',
        'pe_estimado' => 4,
    ),
);

foreach ($eventos as $e) {
    $db->insert_query('rol_mv_eventos', array(
        'ciclo_id'     => $cid,
        'titulo'       => $db->escape_string($e['titulo']),
        'zona_slug'    => $db->escape_string($e['zona_slug']),
        'resumen'      => $db->escape_string($e['resumen']),
        'tipo_suceso'  => $e['tipo_suceso'],
        'pe_estimado'  => $e['pe_estimado'],
        'estado'       => 'pendiente',
        'dateline'     => (int)TIME_NOW,
    ));
    echo "  + {$e['titulo']}\n";
}

// ──────────────────────────────────────────────────────
// 8. Crear hilos narrativos (threads) en estado_json
// ──────────────────────────────────────────────────────
echo "8. Crear hilos narrativos demo\n";
$periodo = date('Y-m');
$threads = array(
    array(
        'id' => 'th-001',
        'titulo' => 'El legado de Barbablanca Jr.',
        'estado' => 'activo',
        'tipo' => 'persona',
        'zonas' => array('east-blue', 'new-world'),
        'npc_implicados' => array('[Demo] Almirante Morgan Drakos'),
        'pj_implicados' => array(),
        'facciones_implicadas' => array('marine', 'pirata'),
        'primer_avistamiento' => $periodo,
        'ultima_evolucion' => $periodo,
        'ultimo_periodico' => $periodo,
        'descripcion' => 'El avistamiento de la nave fantasma de Barbablanca Jr. ha reavivado viejas leyendas. Que secretos escondia este pirata? Su tesoro sigue ahi fuera?',
        'proxima_evolucion' => 'Un tripulante superviviente aparece con informacion crucial',
        'posible_cierre' => false,
        'historial_evolucion' => array(array('fecha' => $periodo, 'evento' => 'Nave fantasma avistada en East Blue', 'periodico' => $periodo)),
    ),
    array(
        'id' => 'th-002',
        'titulo' => 'La conspiracion revolucionaria del Sur',
        'estado' => 'activo',
        'tipo' => 'conflicto',
        'zonas' => array('south-blue'),
        'npc_implicados' => array('[Demo] Comandante Artorius'),
        'pj_implicados' => array(),
        'facciones_implicadas' => array('revolucionario', 'gobierno', 'marine'),
        'primer_avistamiento' => $periodo,
        'ultima_evolucion' => $periodo,
        'ultimo_periodico' => $periodo,
        'descripcion' => 'Las celulas revolucionarias del South Blue se estan reorganizando tras anos de silencio. El Gobierno Mundial ha enviado agentes.',
        'proxima_evolucion' => 'Un enfrentamiento entre revolucionarios y la Marina en una isla neutral',
        'posible_cierre' => false,
        'historial_evolucion' => array(array('fecha' => $periodo, 'evento' => 'Reunion secreta descubierta por el Gobierno Mundial', 'periodico' => $periodo)),
    ),
    array(
        'id' => 'th-003',
        'titulo' => 'Cazarrecompensas en ascenso',
        'estado' => 'activo',
        'tipo' => 'exploracion',
        'zonas' => array('paraiso', 'east-blue'),
        'npc_implicados' => array(),
        'pj_implicados' => array(),
        'facciones_implicadas' => array('cazarrecompensas'),
        'primer_avistamiento' => $periodo,
        'ultima_evolucion' => $periodo,
        'ultimo_periodico' => $periodo,
        'descripcion' => 'El gremio de cazarrecompensas ha expandido sus operaciones. Nuevas recompensas, cazadores mas ambiciosos... y un misterioso benefactor.',
        'proxima_evolucion' => 'Un cazarrecompensas de renombre llega a la zona',
        'posible_cierre' => false,
        'historial_evolucion' => array(array('fecha' => $periodo, 'evento' => 'Nuevas recompensas publicadas', 'periodico' => $periodo)),
    ),
);

$estado_json = array(
    'threads' => $threads,
    'npc_tracking' => array(),
);
// Add NPC tracking for the demo NPCs
$npcq = $db->simple_select('rol_personajes', 'pid, nombre', "es_npc=1 AND nombre LIKE '[Demo]%'");
while ($nr = $db->fetch_array($npcq)) {
    $estado_json['npc_tracking'][(int)$nr['pid']] = array(
        'salud' => 100,
        'moral' => 80,
        'plan_activo' => '',
        'ubicacion_zona' => '',
        'meta_actual' => '',
    );
}

$db->update_query('rol_mv_ciclos', array(
    'estado_json' => $db->escape_string(json_encode($estado_json, JSON_UNESCAPED_UNICODE)),
), "periodo='" . $db->escape_string($periodo) . "' AND estado='abierto'");

echo "  3 hilos creados.\n";

// ──────────────────────────────────────────────────────
// 9. Noticia inactiva (placeholder)
// ──────────────────────────────────────────────────────
echo "9. Crear noticia placeholder\n";
$db->insert_query('rol_mv_noticias', array(
    'titulo'    => 'Un mes de calma tensa en los mares',
    'resumen'   => 'El mundo respira mientras las facciones se rearman. Nuevos movimientos en las sombras, viejas leyendas que resurgen.',
    'cuerpo_html' => '<p>El primer periodico del mes traera noticias frescas desde todos los mares.</p>',
    'origen'    => 'mundo_vivo',
    'ciclo_id'  => $cid,
    'activa'    => 0,
    'dateline'  => (int)TIME_NOW,
));
echo "  Noticia placeholder (inactiva).\n";

echo "\n=== SEMILLA COMPLETADA! ===\n";
echo "Ya puedes ir a Mundo Vivo -> Staff, copiar el prompt y generar.\n";
