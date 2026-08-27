<?php
/**
 * One Piece: 7 Seas · Seed de catálogos cerrados (F1)
 * ----------------------------------------------------------------------------------
 * Siembra en `mybb_ope_*` los catálogos CERRADOS de los manuales (decisión D1.3):
 *   · razas (11, con modificadores y físicos) · raciales · tribus (7)
 *   · dominios (6 bélicos + 12 oficios) · especializaciones (24 ramas)
 *   · dotes (12 generales + 16 raciales) · defectos (14)
 *   · rasgos (12 positivos + 12 negativos, con parejas antagónicas)
 *   · catálogo de efectos de técnicas (15, con puertas de tier)
 *
 * Idempotente: hace upsert por nombre (los ids se conservan entre ejecuciones).
 * No toca datos de personajes ni las tablas `mybb_rol_*` de la era anterior.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-catalogos.php
 *
 * Fuentes: docs/sistema/Manual_del_Jugador.md (caps. 2, 4, 5, 6, 8) y
 *          Manual_del_Staff.md (caps. 2–6, 8.3). Números sagrados — no recalibrar.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$P = 'mybb_ope_';

/** Upsert por nombre: conserva ids, idempotente. */
function ope7_seed_upsert(mysqli $db, string $tabla, string $nombre, array $data): int
{
    $q = $db->prepare("SELECT id FROM `$tabla` WHERE nombre = ? LIMIT 1");
    $q->bind_param('s', $nombre);
    $q->execute();
    $r = $q->get_result();
    $row = $r->fetch_assoc();
    $q->close();
    if ($row) {
        $id = (int) $row['id'];
        $set = array();
        foreach ($data as $k => $v) {
            $set[] = "`$k` = " . ($v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'");
        }
        $db->query("UPDATE `$tabla` SET " . implode(', ', $set) . " WHERE id = $id");
        return $id;
    }
    $data['nombre'] = $nombre;
    $cols = array_keys($data);
    $vals = array();
    foreach ($data as $v) {
        $vals[] = $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
    }
    $db->query("INSERT INTO `$tabla` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $vals) . ")");
    return (int) $db->insert_id;
}

/** Upsert de catálogos que NO tienen columna `nombre` (por otra clave única). */
function ope7_seed_upsert_key(mysqli $db, string $tabla, string $key_col, string $key_val, array $data): int
{
    $q = $db->prepare("SELECT id FROM `$tabla` WHERE `$key_col` = ? LIMIT 1");
    $q->bind_param('s', $key_val);
    $q->execute();
    $r = $q->get_result();
    $row = $r->fetch_assoc();
    $q->close();
    if ($row) {
        $id = (int) $row['id'];
        $set = array();
        foreach ($data as $k => $v) {
            $set[] = "`$k` = " . ($v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'");
        }
        $db->query("UPDATE `$tabla` SET " . implode(', ', $set) . " WHERE id = $id");
        return $id;
    }
    $data[$key_col] = $key_val;
    $cols = array_keys($data);
    $vals = array();
    foreach ($data as $v) {
        $vals[] = $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
    }
    $db->query("INSERT INTO `$tabla` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $vals) . ")");
    return (int) $db->insert_id;
}

$J = function ($v) { return json_encode($v, JSON_UNESCAPED_UNICODE); };
$raza_ids = array();

echo "=== Seed de catálogos cerrados 7 Seas (F1) ===\n";

// ─────────────────────────────────────────────────────────────
// 5.1 — Razas (11) · modificadores + físicos (Manual del Jugador cap. 2)
// ─────────────────────────────────────────────────────────────
$razas = array(
    'Humano'    => array('fue'=>0,'des'=>0,'agi'=>0,'res'=>0,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0,  'altura_min'=>1.50,'altura_max'=>2.50,'vida_media'=>80, 'edad_min'=>16, 'lore'=>'El lienzo en blanco: la medida de todas las cosas.'),
    'Mink'      => array('fue'=>0,'des'=>0,'agi'=>6,'res'=>-4,'per'=>6,'inte'=>0,'car'=>0,'vol'=>0, 'altura_min'=>1.00,'altura_max'=>3.00,'vida_media'=>50, 'edad_min'=>15, 'lore'=>'El pueblo bestia de Zou: velocidad y sentidos, cuerpo ligero.'),
    'Gyojin'    => array('fue'=>6,'des'=>0,'agi'=>0,'res'=>4,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0,  'altura_min'=>1.50,'altura_max'=>15.00,'vida_media'=>70,'edad_min'=>16, 'lore'=>'Hijos del mar: fuerza y resistencia de las profundidades.'),
    'Sirena'    => array('fue'=>-4,'des'=>0,'agi'=>0,'res'=>0,'per'=>4,'inte'=>0,'car'=>6,'vol'=>0, 'altura_min'=>1.00,'altura_max'=>20.00,'vida_media'=>50,'edad_min'=>15, 'lore'=>'La voz del abismo: presencia y percepción, fuerza baja.'),
    'Tontatta'  => array('fue'=>-6,'des'=>6,'agi'=>8,'res'=>0,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0,  'altura_min'=>0.10,'altura_max'=>0.30,'vida_media'=>170,'edad_min'=>50, 'lore'=>'Los diminutos: agilidad y destreza extremas, fuerza proporcional.'),
    'Skypiean'  => array('fue'=>0,'des'=>0,'agi'=>5,'res'=>-3,'per'=>5,'inte'=>0,'car'=>0,'vol'=>0, 'altura_min'=>1.50,'altura_max'=>2.50,'vida_media'=>60, 'edad_min'=>16, 'lore'=>'Hijos del cielo: cuerpos ligeros y afinidad con los Diales.'),
    'Lunarian'  => array('fue'=>0,'des'=>0,'agi'=>-4,'res'=>6,'per'=>0,'inte'=>0,'car'=>0,'vol'=>5, 'altura_min'=>1.80,'altura_max'=>2.50,'vida_media'=>100,'edad_min'=>16, 'lore'=>'Un pueblo casi extinguido: resistencia y voluntad de hierro.'),
    'Gigante'   => array('fue'=>10,'des'=>0,'agi'=>-8,'res'=>6,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0, 'altura_min'=>20.00,'altura_max'=>30.00,'vida_media'=>150,'edad_min'=>40, 'lore'=>'Los colosos de Elbaf: fuerza y resistencia colosales, agilidad mínima.'),
    'Oni'       => array('fue'=>7,'des'=>0,'agi'=>0,'res'=>5,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0,  'altura_min'=>2.00,'altura_max'=>10.00,'vida_media'=>120,'edad_min'=>25, 'lore'=>'Los demonios de Wano: el martillo del mundo.'),
    'Bucaner'   => array('fue'=>8,'des'=>0,'agi'=>-5,'res'=>4,'per'=>0,'inte'=>0,'car'=>0,'vol'=>0, 'altura_min'=>3.00,'altura_max'=>7.00,'vida_media'=>120,'edad_min'=>16, 'lore'=>'Titanes cazados: herencia titánica, fortaleza ambulante.'),
    'Híbrido'   => null, // fila conceptual: se resuelve con raza_id + raza_hibrida_id
);
foreach ($razas as $nombre => $m) {
    $es_hib = $m === null;
    $id = ope7_seed_upsert($db, $P . 'razas', $nombre, array(
        'lore'         => $es_hib ? 'Dos sangres, un cuerpo: media de atributos y primarias de ambas razas.' : ($m['lore'] ?? ''),
        'altura_min'   => $es_hib ? null : $m['altura_min'],
        'altura_max'   => $es_hib ? null : $m['altura_max'],
        'vida_media'   => $es_hib ? null : $m['vida_media'],
        'edad_min'     => $es_hib ? null : $m['edad_min'],
        'modificadores'=> $es_hib ? null : $J(array(
            'fue'=>$m['fue'],'des'=>$m['des'],'agi'=>$m['agi'],'res'=>$m['res'],
            'per'=>$m['per'],'inte'=>$m['inte'],'car'=>$m['car'],'vol'=>$m['vol'],
        )),
        'es_hibrido'   => $es_hib ? 1 : 0,
        'activo'       => 1,
    ));
    $raza_ids[$nombre] = $id;
}
echo "  [OK] razas (" . count($razas) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.1 — Raciales (primaria + secundaria por raza)
// ─────────────────────────────────────────────────────────────
$raciales = array(
    'Humano'   => array('p' => array('Potencial ilimitado', 'Al crear, eliges un atributo que recibe un pequeño empujón.'),
                        's' => array('Adaptabilidad', 'Descuento al adquirir dominios o crear técnicas: aprendes más fácil fuera de tu terreno.')),
    'Mink'     => array('p' => array('Sentidos bestiales', 'Percibes el mundo a otro nivel: oyes pasos a cien metros, hueles el miedo, detectas la emboscada. De las pocas razas que puede «ver venir» a un rival rapidísimo.'),
                        's' => array('Electro', 'Canalizas electricidad por tu cuerpo: descarga en tus ataques naturales con posibilidad de entumecer al rival.')),
    'Gyojin'   => array('p' => array('Fisiología marina', 'Respiras, ves y te mueves bajo el agua con total naturalidad, con bonus a tus atributos físicos sumergido; en tierra no pierdes nada.'),
                        's' => array('Karate Gyojin', 'Acceso al arte marcial ancestral que lanza el agua como proyectiles: técnicas de agua sin necesidad del dominio equivalente.')),
    'Sirena'   => array('p' => array('Voz encantadora', 'Tu voz calma multitudes, atrae auditorios y persuade donde las palabras fracasan.'),
                        's' => array('Canto hipnótico', 'Una melodía que arrebata brevemente el control de un objetivo que pueda oírte. Un solo objetivo, el canto debe mantenerse y la VOL del rival puede resistirlo.')),
    'Tontatta' => array('p' => array('Minúsculos pero letales', 'Esquiva natural ante ataques individuales, y te ocultas y cuelas por cualquier rendija. Un ataque de área se sufre igual.'),
                        's' => array('Fuerza proporcional', 'Tu tamaño no limita tu golpe: daño extra en cuerpo a cuerpo y capacidad de carga desproporcionada.')),
    'Skypiean' => array('p' => array('Afinidad con los Diales', 'Costes reducidos y efectos mejorados con Diales, y planeo: caídas controladas sin daño y saltos largos.'),
                        's' => array('Mantra innato', 'Afinidad natural con la observación: recorres el camino del Haki de Observación con ventaja real.')),
    'Lunarian' => array('p' => array('Llama de linaje', 'Llamas encendidas: gran reducción de daño a cambio de velocidad. Llamas apagadas: velocidad plena a cambio de protección. Cambiar cuesta una acción breve.'),
                        's' => array('Carne de dios', 'Reducción de daño física permanente.')),
    'Gigante'  => array('p' => array('Escala colosal', 'Tus ataques impactan zonas, no personas; intimidas a NPCs menores; es imposible de ocultar.'),
                        's' => array('Piel de Elbaf', 'Bonus importante de Vida y resistencia pasiva al daño físico.')),
    'Oni'      => array('p' => array('Constitución demoníaca', 'Bonus a Fuerza y Resistencia, y resistencia natural al dolor y al miedo.'),
                        's' => array('Furia de batalla', 'Al caer por debajo de un umbral de vida, entras en una furia controlada que aumenta tu daño (trigger declarado en el post).')),
    'Bucaner'  => array('p' => array('Herencia titánica', 'Bonus a Fuerza y a Vida.'),
                        's' => array('Eco del Dios Perdido', 'Una vez por tema-trama, sobrevives a un efecto que causaría tu derrota automática (KO, conversión, manipulación). No lo anula: lo sobrevives.')),
);
foreach ($raciales as $raza => $par) {
    $rid = $raza_ids[$raza];
    ope7_seed_upsert_key($db, $P . 'raciales', 'nombre', $par['p'][0], array(
        'raza_id' => $rid, 'tipo' => 'primaria', 'descripcion' => $par['p'][1],
        'efecto' => $J(array('tipo' => 'racional', 'mecanica' => $par['p'][1], 'formato_completo' => '5.8/5.10')),
    ));
    ope7_seed_upsert_key($db, $P . 'raciales', 'nombre', $par['s'][0], array(
        'raza_id' => $rid, 'tipo' => 'secundaria', 'descripcion' => $par['s'][1],
        'efecto' => $J(array('tipo' => 'racional', 'mecanica' => $par['s'][1], 'formato_completo' => '5.8/5.10')),
    ));
}
echo "  [OK] raciales (" . (count($raciales) * 2) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.1-bis — Tribus (7 · 3 razas) — sustituyen la secundaria estándar
// ─────────────────────────────────────────────────────────────
$tribus = array(
    array('raza' => 'Humano',   'nombre' => 'La Kuja',            'racial' => 'Corazón de Serpiente',  'sustituye' => 'Adaptabilidad',    'efecto' => 'Resistes Miedo y Terror con tu VOL efectiva +3 y tu PER sube +3 en combate.'),
    array('raza' => 'Humano',   'nombre' => 'Las Piernas Largas', 'racial' => 'Patada Larga',          'sustituye' => 'Adaptabilidad',    'efecto' => 'Tus patadas (cuerpo a cuerpo) hacen +3 de daño y ganan +1 de alcance; tu sprint sube un 10%.'),
    array('raza' => 'Humano',   'nombre' => 'Los Brazos Largos',  'racial' => 'Doble Codo',            'sustituye' => 'Adaptabilidad',    'efecto' => 'Tus ataques cuerpo a cuerpo con brazos o armas de una mano ganan +1 de alcance; desarmarte cuesta al rival −3 efectivo.'),
    array('raza' => 'Gigante',  'nombre' => 'Los Ancestrales',    'racial' => 'Coloso Ancestral',      'sustituye' => 'Piel de Elbaf',    'efecto' => 'Tu Vida es +45% (más que la Piel de Elbaf) pero tu velocidad efectiva baja un 10%.'),
    array('raza' => 'Skypiean', 'nombre' => 'Los Shandia',        'racial' => 'Corazón de Guerrera',   'sustituye' => 'Mantra innato',    'efecto' => 'Tu PER sube +2 en combate y tus ataques a distancia ganan +1 de alcance.'),
    array('raza' => 'Skypiean', 'nombre' => 'Los Birka',          'racial' => 'Manos de Birka',        'sustituye' => 'Mantra innato',    'efecto' => 'Fabricas Diales y artilugios un grado antes de lo normal; el mantenimiento de tus Diales cuesta la mitad.'),
    array('raza' => 'Skypiean', 'nombre' => 'Los Caminantes de Nubes', 'racial' => 'Caminante de Nubes', 'sustituye' => 'Mantra innato', 'efecto' => 'Caminas sobre nubes densas y el Mar de Nubes; caes sin daño desde cualquier altura (mejora el planeo de tu primaria).'),
);
foreach ($tribus as $t) {
    ope7_seed_upsert($db, $P . 'tribus', $t['nombre'], array(
        'raza_id'       => $raza_ids[$t['raza']],
        'descripcion'   => 'Tribu de ' . $t['raza'] . 's. Sustituye la racial secundaria ' . $t['sustituye'] . '.',
        'racial_nombre' => $t['racial'],
        'racial_efecto' => $J(array('mecanica' => $t['efecto'])),
        'sustituye_a'   => $t['sustituye'],
    ));
}
echo "  [OK] tribus (" . count($tribus) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.3 — Dominios (6 bélicos + 12 oficios) · atributo rey
// ─────────────────────────────────────────────────────────────
$dominios = array(
    // Bélicos (4.2)
    array('nombre' => 'Armas de filo',        'tipo' => 'belico', 'rey' => 'des', 'desc' => 'Sables, dagas, katanas, mandobles, espadas. Tier de técnica = nivel. +1 DES efectiva por nivel. −1 PA en básicos desde nv2.'),
    array('nombre' => 'Armas contundentes',   'tipo' => 'belico', 'rey' => 'fue', 'desc' => 'Martillos de guerra, garrotes, kanabōs. Tier de técnica = nivel. +1 DES efectiva por nivel.'),
    array('nombre' => 'Armas a distancia',    'tipo' => 'belico', 'rey' => 'des', 'desc' => 'Arcos, ballestas, tirachinas, pistolas, rifles y armas arrojadizas (cuchillos, shurikens, agujas, bombas de mano).'),
    array('nombre' => 'Armas de asta',        'tipo' => 'belico', 'rey' => 'des', 'desc' => 'Lanzas, tridentes y variantes.'),
    array('nombre' => 'Armas flexibles',      'tipo' => 'belico', 'rey' => 'des', 'desc' => 'Báculos, látigos y variantes.'),
    array('nombre' => 'Cuerpo a cuerpo',      'tipo' => 'belico', 'rey' => 'fue', 'desc' => 'Puños, patadas, codo, rodilla: el cuerpo como arma. Atributo rey FUE (con AGI como secundario).'),
    // Oficios (4.3) — Comerciante (n.º 12) obligatorio para tiendas de jugador
    array('nombre' => 'Cocinero',    'tipo' => 'oficio', 'rey' => 'int', 'desc' => 'Raciones (10% PV/PE nv1, recetas 20% nv2). Ramas: Chef de Batalla / Guisador.'),
    array('nombre' => 'Herrero',     'tipo' => 'oficio', 'rey' => 'fue',  'desc' => 'Mantenimiento y afilado (nv1), forja de calidad superior (nv2). Ramas: Armero / Forjador de Espadas.'),
    array('nombre' => 'Ingeniero',   'tipo' => 'oficio', 'rey' => 'int', 'desc' => 'Reparaciones y mecanismos (nv1), modificación de equipos y trampas (nv2). Ramas: Inventor / Maquinista Naval.'),
    array('nombre' => 'Químico',     'tipo' => 'oficio', 'rey' => 'int', 'desc' => 'Pociones al 15% PV y antídotos (nv1), pociones de energía (nv2). Ramas: Alquimista / Toxicólogo.'),
    array('nombre' => 'Médico',      'tipo' => 'oficio', 'rey' => 'int', 'desc' => 'Primeros auxilios al 15% (nv1), cirugía de campo al 25% (nv2). Ramas: Cirujano / Farmacéutico.'),
    array('nombre' => 'Artista',     'tipo' => 'oficio', 'rey' => 'car',  'desc' => 'Actuaciones que mejoran el ánimo (nv1), melodías que restauran PE o mitigan el miedo (nv2). Ramas: Bardo / Hipnotizador.'),
    array('nombre' => 'Domador',     'tipo' => 'oficio', 'rey' => 'car',  'desc' => 'Animales pequeños (nv1), depredadores medianos (nv2). Ramas: Criador / Señor de las Bestias.'),
    array('nombre' => 'Arqueólogo',  'tipo' => 'oficio', 'rey' => 'int', 'desc' => 'Textos antiguos (nv1), reliquias y mitología (nv2). Ramas: Erudito / Descifrador.'),
    array('nombre' => 'Navegante',   'tipo' => 'oficio', 'rey' => 'per',  'desc' => 'Navegar los Blues con bonus (nv1), el Paraíso (nv2). Ramas: Timonel / Cartógrafo.'),
    array('nombre' => 'Ladrón',      'tipo' => 'oficio', 'rey' => 'des',  'desc' => 'Ganzúas y sombras (nv1), hurto y falsificación (nv2). Ramas: Espía / Saboteador.'),
    array('nombre' => 'Carpintero',  'tipo' => 'oficio', 'rey' => 'fue',  'desc' => 'Reparar botes (nv1), construir veleros (nv2). Ramas: Astillero / Constructor.'),
    array('nombre' => 'Comerciante', 'tipo' => 'oficio', 'rey' => 'car',  'desc' => 'Evaluar mercancías y negociar (nv1), redes de clientes y mejor margen (nv2). Ramas: Mercader / Tasador. OBLIGATORIO para abrir tienda de jugador (5.9).'),
);
$dom_ids = array();
foreach ($dominios as $d) {
    $id = ope7_seed_upsert($db, $P . 'dominios', $d['nombre'], array(
        'tipo' => $d['tipo'], 'atributo_rey' => $d['rey'], 'descripcion' => $d['desc'], 'activo' => 1,
    ));
    $dom_ids[$d['nombre']] = $id;
}
echo "  [OK] dominios (" . count($dominios) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.3 — Especializaciones (2 ramas por oficio · efectos nv3/nv4/nv5)
// ─────────────────────────────────────────────────────────────
$ramas = array(
    'Cocinero'    => array(
        array('Chef de Batalla',  'Platos que dan +3 a un atributo durante el tema.', 'Recetas de mayor alcance.', 'El banquete del capitán restaura el 50% del PE del grupo.'),
        array('Guisador',         'Platos que protegen de estados alterados.', 'Recetas contra estados graves.', 'El guiso del milagro cura un estado en pleno combate.'),
    ),
    'Herrero'     => array(
        array('Armero',           'Armas de fuego y cañones.', 'Armamento naval.', 'Armamento naval avanzado.'),
        array('Forjador de Espadas', 'Hojas de grado.', 'Hojas de grado superiores.', 'Forjar una hoja legendaria (hito de personaje).'),
    ),
    'Ingeniero'   => array(
        array('Inventor',         'Artilugios de combate.', 'Artilugios avanzados.', 'El invento definitivo.'),
        array('Maquinista Naval', 'Propulsión y velas mecánicas.', 'Sistemas navales avanzados.', 'Reparaciones en pleno combate naval.'),
    ),
    'Químico'     => array(
        array('Alquimista',       'Buffs y elixires.', 'Elixires superiores.', 'La fórmula rara.'),
        array('Toxicólogo',       'Venenos que inducen estados.', 'Venenos de grado superior.', 'El antídoto universal.'),
    ),
    'Médico'      => array(
        array('Cirujano',         'Heridas críticas.', 'Cirugía de campo avanzada.', 'La operación imposible.'),
        array('Farmacéutico',     'Fármacos.', 'Fármacos superiores.', 'La cura que no existe.'),
    ),
    'Artista'     => array(
        array('Bardo',            'Canciones de apoyo en combate.', 'Canciones superiores.', 'La obertura legendaria.'),
        array('Hipnotizador',     'El sonido como influencia.', 'Influencia superior.', 'La melodía del trance.'),
    ),
    'Domador'     => array(
        array('Criador',          'Monturas y compañeros de combate.', 'Compañeros superiores.', 'La bestia de guerra.'),
        array('Señor de las Bestias', 'Reyes marinos.', 'Reyes marinos superiores.', 'El pacto del coloso.'),
    ),
    'Arqueólogo'  => array(
        array('Erudito',          'Conocimiento profundo.', 'Conocimiento experto.', 'La fuente viva.'),
        array('Descifrador',      'Lenguas perdidas.', 'Lenguas antiguas.', 'El lector de Poneglyphs — habilidad única del sistema.'),
    ),
    'Navegante'   => array(
        array('Timonel',          'Maniobras del barco.', 'Maniobras superiores.', 'El timón perfecto.'),
        array('Cartógrafo',       'Mapas y clima.', 'Mapas detallados.', 'El mapa del mundo.'),
    ),
    'Ladrón'      => array(
        array('Espía',            'Infiltración e información.', 'Infiltración superior.', 'La identidad perfecta.'),
        array('Saboteador',       'Trampas y golpes sigilosos.', 'Trampas superiores.', 'El sabotaje maestro.'),
    ),
    'Carpintero'  => array(
        array('Astillero',        'Mejoras navales.', 'Mejoras superiores.', 'El astillero legendario.'),
        array('Constructor',      'Fortalezas.', 'Fortalezas superiores.', 'La fortaleza inexpugnable.'),
    ),
    'Comerciante' => array(
        array('Mercader',         'Rutas comerciales propias y monopolios locales.', 'Rutas superiores.', 'El imperio comercial.'),
        array('Tasador',          'Tasación experta de armas de grado.', 'Tasación superior.', 'El catálogo del mundo.'),
    ),
);
foreach ($ramas as $oficio => $lista) {
    $oid = $dom_ids[$oficio];
    foreach ($lista as $r) {
        ope7_seed_upsert($db, $P . 'especializaciones', $r[0], array(
            'dominio_id' => $oid,
            'efectos_n3' => $J(array('desc' => $r[1])),
            'efectos_n4' => $J(array('desc' => $r[2])),
            'efectos_n5' => $J(array('desc' => $r[3], 'maestria_suprema' => true)),
        ));
    }
}
echo "  [OK] especializaciones (" . (count($ramas) * 2) . " ramas)\n";

// ─────────────────────────────────────────────────────────────
// 5.4 — Dotes generales (12) y raciales (16)
// ─────────────────────────────────────────────────────────────
$dotes = array(
    // Generales (5.3)
    array('nombre' => 'Estilo Exótico',        'pts' => 2, 'tipo' => 'general', 'req' => array('dominio_belico' => 2), 'ef' => 'Usar dos armas del mismo tipo a la vez, con el coste de PA de una sola por turno.'),
    array('nombre' => 'Empuñadura de Titán',   'pts' => 3, 'tipo' => 'general', 'req' => array('o' => array(array('raza' => 'Gigante'), array('fue' => 60)), 'dominio_belico' => 2), 'ef' => 'Usar un arma de dos manos con una sola, dejando la otra libre (la ranura del arma baja a 1).'),
    array('nombre' => 'Piel de Hierro',        'pts' => 3, 'tipo' => 'general', 'req' => array('vol' => 30), 'ef' => 'Declarado en tu post: reduces 3 + VOL×0,1 de daño físico este turno (coste 3 PE).'),
    array('nombre' => 'Segunda Oportunidad',   'pts' => 3, 'tipo' => 'general', 'req' => array('vol' => 40), 'ef' => '1 vez por tema-trama, un golpe que debería tumbarte (0 PV = KO) te deja al 5% de PV y con un escape posible.'),
    array('nombre' => 'Músculo Entrenado',     'pts' => 2, 'tipo' => 'general', 'req' => array('dominio_belico' => 3), 'ef' => 'Los reposos de tus técnicas de un dominio bajan 1 turno (mínimo 1).', 'incomp' => array('Curva Lenta')),
    array('nombre' => 'Preparación',           'pts' => 2, 'tipo' => 'general', 'req' => array('agi' => 40), 'ef' => '+1 PA por turno: una maniobra extra en combate.'),
    array('nombre' => 'Oportunista',           'pts' => 2, 'tipo' => 'general', 'req' => array(), 'ef' => 'Tus recompensas y salarios suben un 25%.', 'incomp' => array('Recompensa Menguada')),
    array('nombre' => 'Cazador de Cabezas',    'pts' => 2, 'tipo' => 'general', 'req' => array(), 'ef' => 'Cobras +50% por presas vivas entregadas: cambia cómo capturas (vivo, no muerto).'),
    array('nombre' => 'Intercambio',           'pts' => 3, 'tipo' => 'general', 'req' => array(), 'ef' => '1 vez por personaje, redistribuir puntos entre dos atributos primarios (respetando techos).'),
    array('nombre' => 'Estratega',             'pts' => 3, 'tipo' => 'general', 'req' => array('inte' => 45), 'ef' => 'Los ataques enemigos contra ti cuestan +1 PE y sus técnicas +1 turno de reposo si te aciertan.'),
    array('nombre' => 'Bolsillos Profundos',   'pts' => 1, 'tipo' => 'general', 'req' => array(), 'ef' => '+3 espacios de objeto en tu inventario.'),
    array('nombre' => 'Labia de Capitán',      'pts' => 2, 'tipo' => 'general', 'req' => array('car' => 35), 'ef' => '+3 a tu CAR efectiva en influencia verbal (negociar, liderar).', 'incomp' => array('Torpeza Social')),
    // Raciales (5.4)
    array('nombre' => 'Adaptación Rápida',     'pts' => 2, 'tipo' => 'racial', 'raza' => 'Humano',   'req' => array(), 'ef' => 'La segunda vez que un mismo estado te afecta en un tema, lo resistes.'),
    array('nombre' => 'Ambición Sin Límites',  'pts' => 2, 'tipo' => 'racial', 'raza' => 'Humano',   'req' => array(), 'ef' => 'Tus hitos de trama rinden +10% de PP.'),
    array('nombre' => 'Sulong I — Chispa Lunar', 'pts' => 2, 'tipo' => 'racial', 'raza' => 'Mink',   'req' => array('raza' => 'Mink'), 'ef' => 'Bajo luna llena, entras en forma Sulong: +FUE/AGI efectivas y pierdes parcialmente el control.'),
    array('nombre' => 'Sulong II — Luna Controlada', 'pts' => 2, 'tipo' => 'racial', 'raza' => 'Mink', 'req' => array('raza' => 'Mink', 'dote' => 'Sulong I — Chispa Lunar'), 'ef' => 'Activas el Sulong sin luna llena y conservas el control (coste de PE por turno).'),
    array('nombre' => 'Sulong III — Rey de Zou', 'pts' => 3, 'tipo' => 'racial', 'raza' => 'Mink',   'req' => array('raza' => 'Mink', 'dote' => 'Sulong II — Luna Controlada'), 'pura' => true, 'ef' => 'El Sulong maestro, 1 vez por tema-trama.'),
    array('nombre' => 'Piel Marina',           'pts' => 1, 'tipo' => 'racial', 'raza' => 'Gyojin',   'req' => array('raza' => 'Gyojin'), 'ef' => 'Anulas el daño eléctrico extra de tu raza.'),
    array('nombre' => 'Ola Interior',          'pts' => 2, 'tipo' => 'racial', 'raza' => 'Gyojin',   'req' => array('raza' => 'Gyojin'), 'ef' => 'Tus técnicas de agua ganan +1 de alcance efectivo y −1 PE.'),
    array('nombre' => 'Voz Profunda',          'pts' => 2, 'tipo' => 'racial', 'raza' => 'Sirena',   'req' => array('raza' => 'Sirena'), 'ef' => 'Tus efectos de voz/canto suben un escalón de eficacia.'),
    array('nombre' => 'Sombra Diminuta',       'pts' => 3, 'tipo' => 'racial', 'raza' => 'Tontatta', 'req' => array('raza' => 'Tontatta'), 'ef' => '1 vez por tema, esquivas en automático un ataque individual sin gastar defensa.'),
    array('nombre' => 'Mantra Natural',        'pts' => 2, 'tipo' => 'racial', 'raza' => 'Skypiean', 'req' => array('raza' => 'Skypiean'), 'ef' => 'El Mantra te cuesta −1 PE y dura 1 turno más.'),
    array('nombre' => 'Llama Eterna',          'pts' => 2, 'tipo' => 'racial', 'raza' => 'Lunarian', 'req' => array('raza' => 'Lunarian'), 'ef' => 'Con las llamas encendidas, la penalización de velocidad se reduce a la mitad.'),
    array('nombre' => 'Rugido Colosal',        'pts' => 2, 'tipo' => 'racial', 'raza' => 'Gigante',  'req' => array('raza' => 'Gigante'), 'ef' => '1 vez por tema, tu rugido impone miedo a los rivales menores.'),
    array('nombre' => 'Furia Desatada',        'pts' => 2, 'tipo' => 'racial', 'raza' => 'Oni',      'req' => array('raza' => 'Oni', 'racial' => 'Furia de batalla'), 'ef' => 'Evolución de Furia de batalla: bajo 30% PV, el +25% de daño sube a +35%.'),
    array('nombre' => 'Aliento de Demonio',    'pts' => 1, 'tipo' => 'racial', 'raza' => 'Oni',      'req' => array('raza' => 'Oni'), 'ef' => 'Intimidas con tu FUE en lugar de tu CAR.'),
    array('nombre' => 'Mártir',                'pts' => 3, 'tipo' => 'racial', 'raza' => 'Bucaner',  'req' => array('raza' => 'Bucaner', 'vol' => 40), 'ef' => 'Al caer a 0 PV (KO), ejecutas una última acción (una técnica) antes de caer.'),
    array('nombre' => 'Heredero de la Devoción', 'pts' => 2, 'tipo' => 'racial', 'raza' => 'Bucaner', 'req' => array('raza' => 'Bucaner'), 'ef' => 'Te interpones por un aliado y recibes el daño de su ataque (1 vez por tema-trama).'),
);
foreach ($dotes as $d) {
    ope7_seed_upsert($db, $P . 'dotes', $d['nombre'], array(
        'efecto'               => $J(array('mecanica' => $d['ef'])),
        'puntuacion'           => $d['pts'],
        'tipo'                 => $d['tipo'],
        'raza_id'              => isset($d['raza']) ? $raza_ids[$d['raza']] : null,
        'requiere_raza_pura'   => !empty($d['pura']) ? 1 : 0,
        'requisitos'           => $J($d['req']),
        'prerrequisitos'       => $J(array('dote' => ($d['req']['dote'] ?? null), 'racial' => ($d['req']['racial'] ?? null))),
        'incompatibilidades'   => $J($d['incomp'] ?? array()),
        'activo'               => 1,
    ));
}
echo "  [OK] dotes (" . count($dotes) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.4 — Defectos (14)
// ─────────────────────────────────────────────────────────────
$defectos = array(
    array('nombre' => 'Recompensa Menguada', 'pts' => -2, 'incomp' => array('Oportunista'), 'ef' => 'Cobras un 25% menos en salarios y recompensas.'),
    array('nombre' => 'Herida Permanente',   'pts' => -2, 'ef' => 'Una cicatriz que te pesa: +1 PA en una acción concreta o −1 efectivo en un secundario (eliges al crear). Variante fuerte −3.'),
    array('nombre' => 'Reserva Menguada',    'pts' => -2, 'ef' => '−15% de tu Energía máxima.'),
    array('nombre' => 'Curva Lenta',         'pts' => -2, 'incomp' => array('Músculo Entrenado'), 'ef' => 'Tus técnicas cuestan +1 turno de reposo.'),
    array('nombre' => 'Sin Letras',          'pts' => -1, 'ef' => 'No lees ni escribes; los pergaminos y mapas te son ajenos.'),
    array('nombre' => 'Lengua Extranjera',   'pts' => -1, 'ef' => 'Solo hablas tu lengua materna; el idioma común te entiende a medias. Variante fuerte −2.'),
    array('nombre' => 'Pánico a X',          'pts' => -2, 'ef' => 'Un miedo concreto te impone estados de miedo el doble de fuertes.'),
    array('nombre' => 'Endeble',             'pts' => -2, 'ef' => '−10% de tu Vida máxima.'),
    array('nombre' => 'Fama Buscada',        'pts' => -2, 'ef' => 'Un poder te persigue: cazadores y agentes aparecen en tu vida. Variante fuerte −3.'),
    array('nombre' => 'Deuda de Honor',      'pts' => -1, 'ef' => 'Una promesa que no puedes romper; el staff puede usarla para crear tensión.'),
    array('nombre' => 'Debilidad Elemental', 'pts' => -2, 'ef' => 'Recibes +25% de daño de un elemento concreto.'),
    array('nombre' => 'Torpeza Social',      'pts' => -2, 'incomp' => array('Labia de Capitán'), 'ef' => '−3 a tu CAR efectiva en influencia verbal.'),
    array('nombre' => 'Secreto',             'pts' => -2, 'ef' => 'Guardas un secreto que te avergüenza. Si se descubre, sufres una consecuencia mecánica temporal (un estado o −VOL efectiva) hasta resolverlo por trama; el staff define el secreto y su consecuencia al crear. Variante fuerte −3.'),
);
foreach ($defectos as $d) {
    ope7_seed_upsert($db, $P . 'defectos', $d['nombre'], array(
        'efecto'             => $J(array('mecanica' => $d['ef'])),
        'puntuacion'         => $d['pts'],
        'requisitos'         => $J(array()),
        'incompatibilidades' => $J($d['incomp'] ?? array()),
        'activo'             => 1,
    ));
}
echo "  [OK] defectos (" . count($defectos) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.5 — Rasgos (12 positivos + 12 negativos) + parejas antagónicas
// ─────────────────────────────────────────────────────────────
$rasgos = array(
    array('Valiente',    'positivo',  2, 'No retrocede ante el peligro, aunque podría. Se planta, y el miedo no dicta sus pies.'),
    array('Honesto',     'positivo',  1, 'La verdad siempre, aunque duela. Mentir le cuesta más que callar.'),
    array('Leal',        'positivo',  2, 'Su palabra, su tripulación, su causa: por encima de sí mismo. No abandona, no traiciona.'),
    array('Generoso',    'positivo',  1, 'Da lo que tiene sin esperar nada a cambio. Comparte hasta lo último.'),
    array('Curioso',     'positivo',  2, 'El saber lo puede todo. Se mete donde no debe por saber qué hay dentro.'),
    array('Ambicioso',   'positivo',  3, 'Un sueño que no admite límites. Todo lo subordina a él, y lo persigue sin descanso.'),
    array('Compasivo',   'positivo',  1, 'No puede ver sufrir a otro sin intervenir, aunque le cueste.'),
    array('Protector',   'positivo',  3, 'Se interpone entre el peligro y los suyos. Su cuerpo es un escudo antes que una pregunta.'),
    array('Calculador',  'positivo',  3, 'Frío y paciente. Las emociones no mandan en sus decisiones; la jugada, sí.'),
    array('Idealista',   'positivo',  2, 'Cree que el mundo puede cambiar y actúa en consecuencia, cueste lo que cueste.'),
    array('Alegre',      'positivo',  1, 'Su buen humor no se apaga ni en la tormenta. Contagia, y eso también es liderazgo.'),
    array('Justiciero',  'positivo',  2, 'No tolera la injusticia y la persigue, aunque no vaya con él.'),
    array('Cobarde',     'negativo', -2, 'Huye ante el peligro real, aunque luego se avergüence. La huida es su primera respuesta.'),
    array('Mentiroso',   'negativo', -1, 'La verdad es una herramienta, no un deber. Miente con soltura y sin remordimiento.'),
    array('Colérico',    'negativo', -2, 'Pierde el control ante la provocación. La ira le precede y le delata.'),
    array('Vengativo',   'negativo', -3, 'No olvida ni perdona una afrenta. La deuda se cobra, tarde o temprano.'),
    array('Soberbio',    'negativo', -3, 'Se cree por encima de los demás y lo demuestra. Pedir perdón no está en su vocabulario.'),
    array('Impulsivo',   'negativo', -2, 'Actúa antes de pensar. El freno no existe, y las consecuencias llegan después.'),
    array('Desconfiado', 'negativo', -1, 'Nadie se acerca sin pasar por su muro. La amistad se gana con años, no con palabras.'),
    array('Tacaño',      'negativo', -1, 'Cada moneda pesa. Dar le duele, y prestar ni se menciona.'),
    array('Pesimista',   'negativo', -1, 'Todo lo ve gris. El esfuerzo le parece inútil y lo dice.'),
    array('Fanático',    'negativo', -3, 'Su lealtad a una causa o a una persona no conoce límites — ni moral.'),
    array('Cruel',       'negativo', -3, 'El sufrimiento ajeno no le incomoda; a veces lo disfruta.'),
    array('Obstinado',   'negativo', -1, 'No da su brazo a torcer, ni cuando conviene. Razonar con él es perder el tiempo.'),
);
$rasgo_ids = array();
foreach ($rasgos as $r) {
    $id = ope7_seed_upsert($db, $P . 'rasgos', $r[0], array(
        'tipo' => $r[1], 'puntuacion' => $r[2], 'descripcion' => $r[3], 'activo' => 1,
    ));
    $rasgo_ids[$r[0]] = $id;
}
$antagonicas = array(
    'Valiente' => 'Cobarde', 'Honesto' => 'Mentiroso', 'Generoso' => 'Tacaño',
    'Compasivo' => 'Cruel', 'Calculador' => 'Impulsivo', 'Idealista' => 'Pesimista',
);
foreach ($antagonicas as $a => $b) {
    $db->query("UPDATE {$P}rasgos SET pareja_incompatible_id = " . (int) $rasgo_ids[$b] . " WHERE id = " . (int) $rasgo_ids[$a]);
    $db->query("UPDATE {$P}rasgos SET pareja_incompatible_id = " . (int) $rasgo_ids[$a] . " WHERE id = " . (int) $rasgo_ids[$b]);
}
echo "  [OK] rasgos (" . count($rasgos) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5.7 — Catálogo de efectos de técnicas (15 · puertas de tier) — Manual del Jugador 8.3
// ─────────────────────────────────────────────────────────────
$efectos = array(
    array('Daño puro',       1, 'Sube el multiplicador un escalón (a cambio de más PE y reposo).', array('ofensiva','mixta')),
    array('Aplicar estado',  2, 'Un estado del catálogo de 5.10 (grado I→III; los controles solo desde T4).', array('ofensiva','mixta')),
    array('Daño en área',    3, 'Afecta a todos en la zona (cada uno se defiende aparte; +1 reposo).', array('ofensiva','mixta')),
    array('Curar PV',        2, '10/15/20% de tu PV máxima (o de un aliado) según el tier.', array('apoyo','mixta')),
    array('Restaurar PE',    3, '10/15% de la Energía máxima.', array('apoyo')),
    array('Quitar estado',   2, 'Limpia un estado (físico → mental → veneno, según el tier).', array('apoyo','mixta')),
    array('Defensa',         1, 'Técnica defensiva: bloquea ataques hasta +1 tier por encima.', array('defensiva')),
    array('Movilidad',       2, 'Te mueves sin gastar PA de desplazamiento este turno.', array('apoyo','mixta')),
    array('Buff',            2, 'Un estado positivo (Motivado, Acelerado, Coraje...) a ti, un aliado o un grupo.', array('apoyo')),
    array('Control físico',  3, 'Agarrado, Desplazado, Parálisis — la Tabla de Fuerza/Resistencia decide.', array('ofensiva','mixta')),
    array('Control mental',  3, 'Miedo, Confundido, Terror, Encantado, Dormido — la Tabla de Mente/Voluntad decide.', array('ofensiva','mixta')),
    array('Terreno',         4, 'Modificas el entorno 2–3 turnos: muro, niebla, zona ardiente, trampa.', array('apoyo','mixta')),
    array('Sigilo',          3, 'Te ocultas y preparas una emboscada.', array('apoyo')),
    array('Carga',           2, 'Técnica de 2 turnos con el efecto mejorado; se interrumpe con dolor.', array('ofensiva','mixta')),
    array('Estética',        1, 'Gratis en cualquier tier: pétalos, chispas, aura — puro sabor, sin mecánica.', array('ofensiva','defensiva','apoyo','mixta')),
);
foreach ($efectos as $e) {
    ope7_seed_upsert($db, $P . 'catalogo_efectos', $e[0], array(
        'puerta_tier'            => $e[1],
        'slots'                  => 1,
        'tipo_tecnica_permitido' => $J($e[3]),
        'descripcion'            => $e[2],
        'activo'                 => 1,
    ));
}
echo "  [OK] catálogo de efectos (" . count($efectos) . ")\n";

echo "\n=== DONE — catálogos cerrados sembrados (ids conservados) ===\n";
