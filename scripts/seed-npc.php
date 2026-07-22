<?php
/**
 * One Piece: Eternal · Seed de NPCs desde One Piece: Eternal-Sistema
 * Crea 15 NPCs en rol_personajes con la información de lore de One Piece: Eternal.
 * Idempotente: salta si el slug ya existe.
 *
 * Ejecutar: php scripts/seed-npc.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) { fwrite(STDERR, "DB error: " . $db->connect_error . "\n"); exit(1); }
$db->set_charset('utf8mb4');

$TABLE = 'mybb_rol_personajes';
$now = time();

function seed_npc_slug(string $name): string {
    $name = mb_strtolower(trim($name), 'UTF-8');
    $trans = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'];
    $name = strtr($name, $trans);
    $name = preg_replace('/[^a-z0-9]+/', '-', $name);
    return trim($name, '-');
}

function seed_npc_insert($db, $table, $data, $now) {
    $slug = $data['slug'];
    $check = $db->query("SELECT pid FROM `{$table}` WHERE slug = '{$db->real_escape_string($slug)}' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        echo "  [skip] {$data['nombre']} (ya existe)\n";
        return;
    }
    $cols = [];
    $vals = [];
    foreach ($data as $k => $v) {
        $cols[] = "`{$k}`";
        $vals[] = "'" . $db->real_escape_string((string)$v) . "'";
    }
    $cols[] = '`dateline`'; $vals[] = (string)$now;
    $cols[] = '`lastedit`';  $vals[] = (string)$now;
    $sql = "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] {$data['nombre']}: " . $db->error . "\n");
    } else {
        echo "  [OK] {$data['rango']} {$data['nombre']}\n";
    }
}

echo "=== Sembrando NPCs de Lore ===\n\n";

$npcs = [
    [
        'nombre' => 'Isabella D. Vega',
        'slug' => 'isabella-d-vega',
        'rango' => 'SS', 'rango_faccion' => 'Reina Pirata', 'nivel' => 99,
        'desc_fisica' => 'Estatura imponente (2.10 m), figura atlética marcada por cicatrices de mil batallas. Su rasgo distintivo son sus intensos ojos carmesí que parecen brillar cuando usa Haki del Conquistador. Viste un abrigo de capitán rasgado, pantalones oscuros de cuero y pesadas botas de hierro. En su brazo derecho lleva enrollada su cadena de Kairoseki.',
        'personalidad' => 'Temeraria, libre, y con una risa estridente ("¡Gahahahaha!"). Desprecia la autoridad y a los Tenryubitos por encima de todo. A pesar de ser despiadada en combate, es intensamente leal a su antigua tripulación y siente compasión por los esclavos y oprimidos. Tiene el defecto de confiar demasiado en los suyos. Tono autoritario, pero cálido.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'pirata','apodo'=>'Ojos Carmesí · La Reina Pirata','concepto'=>'La Reina de los Piratas, maestra del Haki sin Fruta del Diablo, capturada por traición. Espera su ejecución en Marineford.','edad'=>'38','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'Reina Pirata forjada a pura fuerza de voluntad','pasado'=>'Nacida en la pobreza extrema bajo la tiranía de un reino afiliado al Gobierno Mundial, escapó al mar de niña, formó los Piratas Carmesí y conquistó Grand Line. Descubrió el secreto del Siglo Vacío en La Última Isla. Traicionada por Balgor, fue capturada por la Almirante Valyria tras un duelo legendario.','motivacion'=>'Sobrevivir a la ejecución y derrocar a Imu para liberar al mundo. Perdonarse por la destrucción de su familia.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Almirante de Flota Valyria',
        'slug' => 'almirante-de-flota-valyria',
        'rango' => 'SS', 'rango_faccion' => 'Almirante de Flota', 'nivel' => 99,
        'desc_fisica' => 'Una mujer altísima, fría y majestuosa. Lleva un parche en el ojo. Considerada la mejor espadachina del mundo, empuña una Odachi legendaria cruzada a la espalda. Basada en Sephiroth.',
        'personalidad' => 'El Filo de la Marina. Implacable, fría y majestuosa. Su sola presencia impone respeto absoluto. Capturó personalmente a Isabella D. Vega tras un duelo de espadas legendario.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'marine','apodo'=>'El Filo de la Marina','concepto'=>'Almirante de Flota de la Marina. La mejor espadachina del mundo. Lidera la defensa de Marineford para la ejecución de Isabella.','edad'=>'Desconocida','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'La espada más letal al servicio de la Justicia','pasado'=>'Ascendió meteóricamente a Almirante de Flota tras cortar una isla por la mitad. Es hermana de Isabella D. Vega, a quien capturó personalmente.','motivacion'=>'Garantizar la ejecución de Isabella y demostrar el poder absoluto de la Marina.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Shura "Dios de la Ira"',
        'slug' => 'shura-dios-de-la-ira',
        'rango' => 'S', 'rango_faccion' => 'Yonko', 'nivel' => 95,
        'desc_fisica' => 'Una imponente mujer Oni de 4 metros, de piel rojiza y cuernos dentados, que viste kimonos andrajosos. Empuña una odachi gigantesca y corroída.',
        'personalidad' => 'Una terrorífica Oni que combina su monstruosidad demoníaca con transformaciones de un Buda dorado. Consumió la Zoan Mítica del Buda, lo que le permite alternar entre furia destructiva y calma divina para aplastar islas enteras.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'pirata','apodo'=>'Dios de la Ira · Oni Iluminada','concepto'=>'Yonko. Una Oni de 4 metros con la Zoan Mítica del Buda. Alterna entre furia demoníaca y calma divina dorada.','edad'=>'Desconocida','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'La Oni Iluminada, Yonko del Nuevo Mundo','pasado'=>'Emergió en el Nuevo Mundo hace 20 años junto a Sekhmet. Se consolidó como uno de los Cuatro Emperadores gracias a su Zoan Mítica del Buda, que le permite alternar entre formas demoníacas y divinas.','motivacion'=>'Reclamar el trono de Reina Pirata ahora que Isabella está cautiva.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Sekhmet "Reina Leona"',
        'slug' => 'sekhmet-reina-leona',
        'rango' => 'S', 'rango_faccion' => 'Yonko', 'nivel' => 94,
        'desc_fisica' => 'Una majestuosa Mink Leona. Físicamente imponente, camina con realeza. Su pelaje dorado brilla cubierto de cicatrices victoriosas.',
        'personalidad' => 'Majestuosa y regia. Lucha usando un Electro tan poderoso que parece magia pura, sumado a un Haki de Armadura capaz de destrozar acero marino con sus garras.',
        'datos' => json_encode(['raza_principal'=>'mink','faccion'=>'pirata','apodo'=>'Reina Leona','concepto'=>'Yonko. Una majestuosa Mink Leona de pelaje dorado. Su Electro y Haki de Armadura son devastadores.','edad'=>'Desconocida','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'La Reina Leona, Emperatriz del Nuevo Mundo','pasado'=>'Emergió en el Nuevo Mundo junto a Shura hace 20 años, consolidándose como Yonko. Su tribu Mink la venera como a una diosa guerrera.','motivacion'=>'Proteger a los suyos y decidir el futuro del trono pirata.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Ezekiel "El Arcángel"',
        'slug' => 'ezekiel-el-arcangel',
        'rango' => 'S', 'rango_faccion' => 'Yonko', 'nivel' => 93,
        'desc_fisica' => 'Un joven híbrido hermoso y angelical. Tiene alitas Skypiean, alas negras y llama perpetua Lunarian. Viste ropas prístinas y sagradas.',
        'personalidad' => 'No tiene Fruta del Diablo. Pelea a kilómetros de distancia con un inmenso Rifle de Francotirador cargado con Diales de Skypiea y cubierto de Haki del Conquistador. Un ángel de la muerte que caza desde los cielos.',
        'datos' => json_encode(['raza_principal'=>'lunarian','raza_secundaria'=>'skypiean','hibrido'=>true,'faccion'=>'pirata','apodo'=>'El Arcángel','concepto'=>'Yonko. Híbrido Skypiean/Lunarian. Francotirador angelical sin Fruta del Diablo. Caza piratas desde los cielos con su rifle de Diales.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Arcángel, el cuarto Emperador que caza desde los cielos','pasado'=>'Catalogado como el cuarto Emperador hace 10 años. Su habilidad para eliminar objetivos a kilómetros de distancia lo convierte en el Yonko más escurridizo.','motivacion'=>'Mantener el equilibrio del Nuevo Mundo desde las alturas.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Balgor "Titán de Chatarra"',
        'slug' => 'balgor-titan-de-chatarra',
        'rango' => 'S', 'rango_faccion' => 'Yonko', 'nivel' => 92,
        'desc_fisica' => 'Un Gigante colosal de Elbaf con su brazo derecho y gran parte del cráneo cibernéticos, soltando humo constantemente.',
        'personalidad' => 'Posee la Gasha Gasha no Mi. Asimila flotas de la Marina para transformar su cuerpo en un Mecha destructivo del tamaño de una isla. Traicionó a Isabella vendiendo sus coordenadas a cambio de armamento.',
        'datos' => json_encode(['raza_principal'=>'gigante','sub_opcion_racial'=>'ancestral','faccion'=>'pirata','apodo'=>'Titán de Chatarra','concepto'=>'Yonko. Gigante cyborg de Elbaf con la Gasha Gasha no Mi. Asimila flotas para convertirse en un Mecha del tamaño de una isla. Traicionó a Isabella.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Titán de Chatarra, el Yonko que traicionó a la Reina','pasado'=>'Antiguo aliado de Isabella. Desertó hace 10 años asimilando flotas enteras para convertirse en un Mecha gigante, coronándose Yonko. Hace un mes vendió las coordenadas de Isabella a la Marina.','motivacion'=>'Expandir su ejército de chatarra hasta dominar el Nuevo Mundo.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Almirante Ken "Dragón Azul"',
        'slug' => 'almirante-ken-dragon-azul',
        'rango' => 'S', 'rango_faccion' => 'Almirante', 'nivel' => 90,
        'desc_fisica' => 'Un humano karateka que viste ropas marciales tradicionales y una larguísima coleta.',
        'personalidad' => 'Justicia Heroica. Pelea con patadas ciclónicas a velocidad supersónica, tan demoledoras que cortan el aire. Protege a los inocentes por encima de todo.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'marine','apodo'=>'Dragón Azul','concepto'=>'Almirante de la Marina. Karateka de Justicia Heroica. Sus patadas supersónicas cortan el aire.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Dragón Azul, Almirante de Justicia Heroica','pasado'=>'Uno de los tres Almirantes bajo el mando de Valyria. Representa la Justicia Heroica, protegiendo a los civiles por encima de las órdenes.','motivacion'=>'Defender los ideales de la Marina y proteger a los inocentes del caos pirata.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Almirante Flint "Balas de Plata"',
        'slug' => 'almirante-flint-balas-de-plata',
        'rango' => 'S', 'rango_faccion' => 'Almirante', 'nivel' => 89,
        'desc_fisica' => 'Un humano bribón, desaliñado pero carismático. Siempre fumando un cigarrillo.',
        'personalidad' => 'Justicia Perezosa. Pelea exclusivamente con dos revólveres de chispa modificados y un Haki de Observación insuperable. Nunca se toma nada en serio.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'marine','apodo'=>'Balas de Plata','concepto'=>'Almirante de la Marina. Tirador de Justicia Perezosa con dos revólveres y Haki de Observación insuperable.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'Balas de Plata, el Almirante que nunca se toma nada en serio','pasado'=>'Uno de los tres Almirantes. Su Haki de Observación es legendario, permitiéndole predecir movimientos con precisión absoluta.','motivacion'=>'Cumplir con su deber sin esforzarse más de lo necesario.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Almirante Nereida "El Abismo"',
        'slug' => 'almirante-nereida-el-abismo',
        'rango' => 'S', 'rango_faccion' => 'Almirante', 'nivel' => 91,
        'desc_fisica' => 'Una Sirena (Ningyo) aterradora e implacable.',
        'personalidad' => 'Justicia Absoluta. Su odio por los piratas es infinito. Domina el Gyojin Karate a un nivel catastrófico, creando tsunamis gigantescos para hundir flotas sin moverse de su asiento.',
        'datos' => json_encode(['raza_principal'=>'sirena','faccion'=>'marine','apodo'=>'El Abismo','concepto'=>'Almirante de la Marina. Sirena de Justicia Absoluta. Su Gyojin Karate crea tsunamis capaces de hundir flotas enteras.','edad'=>'Desconocida','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Abismo, la Almirante que hunde flotas con un gesto','pasado'=>'La única Almirante no humana. Su odio por los piratas la llevó a desarrollar un Gyojin Karate de poder catastrófico.','motivacion'=>'Erradicar la piratería del mundo, sin piedad ni excepción.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Jack "El Inmortal"',
        'slug' => 'jack-el-inmortal',
        'rango' => 'A', 'rango_faccion' => 'Vice-Capitán', 'nivel' => 80,
        'desc_fisica' => 'Un hombre recubierto de cicatrices de pies a cabeza, testimonio de incontables batallas.',
        'personalidad' => 'El actual Vice-Capitán de los Piratas Carmesí tras la traición de Velvet. Se niega a morir hasta rescatar a Isabella. Leal hasta la muerte.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'pirata','apodo'=>'El Inmortal','concepto'=>'Vice-Capitán de los Piratas Carmesí. Un hombre cubierto de cicatrices que se niega a morir hasta rescatar a Isabella.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Inmortal, Vice-Capitán de los Piratas Carmesí','pasado'=>'Ascendió a Vice-Capitán tras la traición de Velvet. Ha sobrevivido a heridas que habrían matado a cualquiera.','motivacion'=>'Rescatar a Isabella D. Vega de Marineford o morir en el intento.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Comandante Ignis "Llama del Sur"',
        'slug' => 'comandante-ignis-llama-del-sur',
        'rango' => 'A', 'rango_faccion' => 'Comandante', 'nivel' => 78,
        'desc_fisica' => 'Un revolucionario de presencia ardiente e imponente.',
        'personalidad' => 'Fogoso y determinado. Planea infiltrarse en Marineford durante la ejecución para asesinar a tantos Nobles Mundiales como sea posible. Usuario de Paramecia.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'revolucionario','apodo'=>'Llama del Sur','concepto'=>'Comandante del Ejército Revolucionario. Usuario de Paramecia. Planea infiltrarse en Marineford para asesinar Nobles.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'Llama del Sur, la antorcha de la revolución','pasado'=>'Alto mando del Ejército Revolucionario. Ha liberado múltiples reinos oprimidos por el Gobierno Mundial.','motivacion'=>'Aprovechar el caos de la ejecución para asestar un golpe mortal a los Tenryubitos.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Dra. Aurelian Lira',
        'slug' => 'dra-aurelian-lira',
        'rango' => 'B', 'rango_faccion' => 'Médica', 'nivel' => 65,
        'desc_fisica' => 'Una mujer de porte aristocrático que oculta su origen noble bajo una bata médica.',
        'personalidad' => 'Nacida en Mary Geoise en la poderosa Familia Aurelian, huyó asqueada de los nobles. Ahora es la brillante y cínica médica de los Piratas Carmesí.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'pirata','apodo'=>'','concepto'=>'Médica de los Piratas Carmesí. Nacida en Mary Geoise, huyó del mundo de los Tenryubitos. Brillante y cínica.','edad'=>'Desconocida','genero'=>'Femenino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'La médica que cambió Mary Geoise por el mar','pasado'=>'Nació en la Familia Aurelian, una de las 16 Familias Fundadoras. Asqueada por la crueldad de los Dragones Celestiales, escapó y se unió a los Piratas Carmesí.','motivacion'=>'Mantener con vida a los suyos y redimirse del pecado de su linaje.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => '"Perro Rabioso" Varg',
        'slug' => 'perro-rabioso-varg',
        'rango' => 'B', 'rango_faccion' => 'Cazarrecompensas', 'nivel' => 60,
        'desc_fisica' => 'Un cyborg callejero con implantes visibles y mirada de depredador.',
        'personalidad' => 'Un cazador de recompensas sin moral que viaja por el Paraíso emboscando a piratas debilitados. Amoral, oportunista y peligroso.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'cazarrecompensas','apodo'=>'Perro Rabioso','concepto'=>'Cazarrecompensas cyborg sin moral. Embosca piratas debilitados en el Paraíso.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Perro Rabioso, cazador de piratas heridos','pasado'=>'Un cyborg que se gana la vida cobrando recompensas de piratas que sobreviven a sus batallas. Sin código de honor.','motivacion'=>'Cobrar la mayor recompensa posible durante el caos de la ejecución.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => '"Cara de Moneda" Gils',
        'slug' => 'cara-de-moneda-gils',
        'rango' => 'C', 'rango_faccion' => 'Corredor del Mercado Negro', 'nivel' => 40,
        'desc_fisica' => 'Un hombre de negocios con una sonrisa de comerciante y una moneda que nunca deja de girar entre sus dedos.',
        'personalidad' => 'El principal corredor del Mercado Negro bajo el mando de Velvet. Famoso por vender armas a ambos bandos en cualquier guerra. Sin lealtades, solo negocios.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'civil','apodo'=>'Cara de Moneda','concepto'=>'Corredor principal del Mercado Negro. Vende armas a ambos bandos sin lealtades.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El broker que vende a todos y no le debe lealtad a nadie','pasado'=>'Ascendió en el inframundo bajo la protección de Velvet. Ha amasado una fortuna vendiendo armamento a piratas y marines por igual.','motivacion'=>'Maximizar beneficios durante la Guerra Total que se avecina.'], JSON_UNESCAPED_UNICODE),
    ],
    [
        'nombre' => 'Príncipe Oakhaven',
        'slug' => 'principe-oakhaven',
        'rango' => 'D', 'rango_faccion' => 'Dragón Celestial', 'nivel' => 5,
        'desc_fisica' => 'Un joven de porte aristocrático con el característico traje de los Dragones Celestiales y una burbuja de aire sobre la cabeza.',
        'personalidad' => 'Un joven y caprichoso Dragón Celestial. Obcecado con comprar a un guerrero de Elbaf como esclavo personal. Representa todo lo que el mundo odia de los Tenryubitos.',
        'datos' => json_encode(['raza_principal'=>'humano','faccion'=>'gobierno','apodo'=>'','concepto'=>'Dragón Celestial (Tenryubito). Caprichoso y obsesionado con comprar un guerrero de Elbaf como esclavo.','edad'=>'Desconocida','genero'=>'Masculino'], JSON_UNESCAPED_UNICODE),
        'bio' => json_encode(['concepto'=>'El Dragón Celestial que quiere un gigante como mascota','pasado'=>'Miembro de una de las 16 Familias Fundadoras. Ha vivido toda su vida en el lujo absoluto de Mary Geoise, aislado de la realidad del mundo.','motivacion'=>'Adquirir un esclavo gigante de Elbaf para su colección personal.'], JSON_UNESCAPED_UNICODE),
    ],
];

foreach ($npcs as $npc) {
    $npc['uid'] = '0';
    $npc['estado'] = 'aprobado';
    $npc['es_npc'] = '1';
    $npc['activo'] = '0';
    $npc['avatar'] = '';
    $npc['icono'] = '';
    $npc['inventario'] = '[]';
    $npc['economia'] = '{"berries":100000}';
    seed_npc_insert($db, $TABLE, $npc, $now);
}

$count = $db->query("SELECT COUNT(*) AS c FROM `{$TABLE}` WHERE es_npc = 1 AND uid = 0 AND slug LIKE '%-%'");
echo "\n=== DONE ===\n";
echo "NPCs de lore actuales: " . $count->fetch_assoc()['c'] . "\n";
$db->close();
