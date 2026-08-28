<?php
/**
 * One Piece: 7 Seas · Seed de akumas y pool (F5) — 5.18
 * ------------------------------------------------------
 * Siembra:
 *   · akumas            — las 6 fichas canon de 19.8 (Gomu, Hana, Neko, Ryu,
 *                         Tori Tori Fénix, Mera) con la plantilla de 8 bloques,
 *                         + 12 frutas T1/T2 del pool ampliado (D5.1: sin ficha
 *                         completa; se completa con el trámite 49 bajo demanda).
 *   · akuma_pool_tirada — bandas por nivel (nv3+ T1–T2 · nv15+ T3 · nv30+ T4;
 *                         T5 nunca por tirada) — pool global (D5.1).
 *   · objetos           — un objeto «Fruta: X» por akuma (categoria 'akuma',
 *                         mediano 1 ranura, 19.7) para el inventario.
 *
 * Números cerrados del manual (matriz 19.7: 150/300/600/1.000/1.500 ×1/×2/×3).
 * Idempotente por nombre propio / nombre de objeto. No toca nada más.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-akumas.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$P = 'mybb_ope_';
$n = 0;

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

/** Upsert de akuma por nombre_propio. */
function ope7_akuma_upsert(mysqli $db, string $tabla, string $nombre, array $data): int
{
    return ope7_seed_upsert_key($db, $tabla, 'nombre_propio', $nombre, $data);
}

// ── Las 6 fichas canon (19.8) con la plantilla de 8 bloques. ──
// Bloque 8: precio_base 0 (sin tienda: se obtiene por tirada/PP/eventos, 5.18);
// coste_pp = matriz de especificidad (base ×1/×2/×3).
$canon = array(
    'Gomu Gomu no Mi' => array(
        'familia' => 'paramecia', 'rareza' => null, 'tier' => 2,
        'aspecto' => 'Fruto morado con espirales que parece goma; sabor repugnante, gomoso y amargo.',
        'mecanica_base' => array(
            'resumen' => 'Tu cuerpo es goma: estiras, comprimes y rebotas.',
            'pasivas' => array('Estiramiento natural del cuerpo', 'Rebote de impactos contundentes'),
            'rupturas' => array(array('regla' => 'Alcance', 'condicion' => 'Tus puños viajan en línea recta y un corte los detiene')),
        ),
        'puertas' => array('Daño puro', 'Movilidad (balanceo y rebote)', 'Desplazado (el impacto que manda a volar)', 'Carga', 'Defensa (absorber contundente)'),
        'debilidades' => array(
            'enemigo_natural' => 'La goma se corta (Hemorragia normal), el calor la funde y la electricidad la conduce (un rayo te atraviesa).',
            'mar' => 'El agua de mar en cantidad suficiente te deja sin fuerzas (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 3, 'nota' => 'Piso bajo: cualquier ficha puede portarla con provecho.'),
        'influencia_ficha' => array('dotes' => array('Elástico (cuerpo de goma)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'Transmuta el entorno en goma', 'detalle' => 'Suelos que rebotan, golpes que vuelven a quien los dio.'),
        'coste_pp' => array('base' => 300, 'familia' => 300, 'concepto' => 600, 'concreta' => 900),
    ),
    'Hana Hana no Mi' => array(
        'familia' => 'paramecia', 'rareza' => null, 'tier' => 2,
        'aspecto' => 'Fruto rosado con pequeñas flores en la piel; sabor a pétalos rancios.',
        'mecanica_base' => array(
            'resumen' => 'Floreces partes de tu cuerpo en cualquier superficie: brazos de un muro, ojos donde nadie mira.',
            'pasivas' => array('Sentir a través de tus brotes', 'Dolor en la parte adyacente cuando dañan tus brotes'),
        ),
        'puertas' => array('Daño puro', 'Agarrado (manos que sujetan desde el suelo)', 'Sigilo (espiar con ojos y oídos florecidos)', 'Terreno (red de brazos que hace una zona intransitable)'),
        'debilidades' => array(
            'enemigo_natural' => 'Sin superficies (mar abierto, cielo) tus brotes no tienen dónde florecer.',
            'mar' => 'El agua de mar te deja sin fuerzas (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 3),
        'influencia_ficha' => array('dotes' => array('Brotes (florecer en superficies)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'Floreces tu cuerpo completo en todas las superficies', 'detalle' => 'Estás en todas partes a la vez.'),
        'coste_pp' => array('base' => 300, 'familia' => 300, 'concepto' => 600, 'concreta' => 900),
    ),
    'Neko Neko no Mi: Modelo Leopardo' => array(
        'familia' => 'zoan', 'rareza' => 'comun', 'tier' => 1,
        'aspecto' => 'Fruto ocre con manchas de leopardo; sabor a carne cruda.',
        'mecanica_base' => array(
            'resumen' => 'El cazador: forma híbrida (garras, ojos felinos, bonos de AGI y FUE) y completa (el leopardo entero, velocidad de emboscada).',
            'pasivas' => array('Garras como arma natural (cap. 9)', 'Sentidos de cazador (PER efectiva +)', 'Las heridas persisten entre formas', 'Cambiar de forma cuesta PA (cap. 11)'),
        ),
        'puertas' => array('Daño puro', 'Movilidad', 'Sigilo (el acecho que niega el primer golpe)', 'Agarrado (derribo y presa)'),
        'debilidades' => array(
            'enemigo_natural' => 'En forma completa eres un cuerpo animal: el veneno y el fuego te alcanzan sin tu AGI de híbrido.',
            'mar' => 'El mar te rechaza en cualquier forma (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 3),
        'influencia_ficha' => array('dotes' => array('Forma animal (híbrida/completa)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'Forma colosal con regeneración', 'detalle' => 'Un rugido que aplica Miedo (5.10).'),
        'coste_pp' => array('base' => 150, 'familia' => 150, 'concepto' => 300, 'concreta' => 450),
    ),
    'Ryu Ryu no Mi: Modelo Espinosaurio' => array(
        'familia' => 'zoan', 'rareza' => 'ancestral', 'tier' => 3,
        'aspecto' => 'Fruto escamoso con una cresta dorsal; sabor a pantano.',
        'mecanica_base' => array(
            'resumen' => 'El coloso de una era extinta: híbrida (tres metros, garras de medio metro, piel de cuero — FUE/RES enormes, AGI reducida) y completa (el dinosaurio).',
            'pasivas' => array('Piel de cuero = reducción natural', 'Lento a propósito (AGI reducida en híbrida)', 'Matiz: animal de río, pero el mar te rechaza en cualquier forma', 'Las heridas persisten entre formas'),
        ),
        'puertas' => array('Daño puro (+1 escalón)', 'Defensa', 'Agarrado', 'Terreno (devastación: rompes el suelo, tumbas muros)'),
        'debilidades' => array(
            'enemigo_natural' => 'El mar: animal de río, pero la sal te rechaza en cualquier forma.',
            'mar' => 'El mar te rechaza en cualquier forma (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 8, 'nota' => 'Forma colosal: RES y FUE altas para sostenerla.'),
        'influencia_ficha' => array('dotes' => array('Forma colosal (dinosaurio)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'Ignoras la mitad de las reducciones planas', 'detalle' => 'Tu forma completa rompe defensas pasivas.'),
        'coste_pp' => array('base' => 600, 'familia' => 600, 'concepto' => 1200, 'concreta' => 1800),
    ),
    'Tori Tori no Mi: Modelo Fénix' => array(
        'familia' => 'zoan', 'rareza' => 'mitologica', 'tier' => 5,
        'aspecto' => 'Fruto azul con alas diminutas; sabor a ceniza dulce.',
        'mecanica_base' => array(
            'resumen' => 'El mito: alas de llamas azules que regeneran — curas en forma de fénix y tus llamas no queman a quien no quieres que queme.',
            'pasivas' => array('Regeneración en forma de fénix', 'Llamas que envuelven y sanan a un aliado', 'Vuelo de verdad (con espacio)'),
        ),
        'puertas' => array('Daño puro', 'Movilidad', 'Curar PV', 'Quitar estado (las llamas purifican)', 'Buff (llamas de Coraje)', 'Terreno (mar de llamas azules que cura aliados y daña enemigos — rompe la regla del terreno dañino)'),
        'debilidades' => array(
            'enemigo_natural' => 'El espacio cerrado anula el vuelo; el agua apaga las llamas; la regeneración no te levanta del KO (cap. 11).',
            'mar' => 'El mar te rechaza en cualquier forma (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 20, 'nota' => 'T5: solo por compra concreta o recompensa de evento — nunca por tirada.'),
        'influencia_ficha' => array('dotes' => array('Fénix (regeneración y vuelo)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'Fénix Mayor', 'detalle' => 'Una vez por tema-trama, sus llamas devuelven la vida a un aliado caído.'),
        'coste_pp' => array('base' => 1500, 'familia' => 1500, 'concepto' => 3000, 'concreta' => 4500),
    ),
    'Mera Mera no Mi' => array(
        'familia' => 'logia', 'rareza' => null, 'tier' => 5,
        'aspecto' => 'Fruto bermellón que vibra con calor; sabor a brasa.',
        'mecanica_base' => array(
            'resumen' => 'El fuego: creas, controlas y te transformas en llama. Intangible con contadores publicados.',
            'pasivas' => array('Intangibilidad Logia con contadores (19.5)', 'El fuego que ya arde te obedece (control del elemento presente)', 'No creas seres vivos', 'Imbuir tu fuego en un arma exige O Wazamono+ (cap. 9)'),
            'contadores' => array('Kairoseki (5.8)', 'Haki Armadura — Toque sólido N1 (5.19)', 'Elemento antagónico (agua)', 'Efectos indirectos: terreno ardiente, voluntad del Conquistador'),
        ),
        'puertas' => array('Daño puro', 'Quemadura (grados I–III)', 'Movilidad (propulsión de llamas: te deshaces y reapareces)', 'Terreno (zona ardiente)', 'Defensa (muro de fuego)'),
        'debilidades' => array(
            'enemigo_natural' => 'El agua te apaga (no intangible bajo el agua o la lluvia) y el magma te supera (escala de poder entre Logias).',
            'mar' => 'El agua de mar te apaga y te deja sin fuerzas (te hundes).',
        ),
        'requisitos_portador' => array('nivel_min' => 20, 'nota' => 'T5: solo por compra concreta o recompensa de evento — nunca por tirada.'),
        'influencia_ficha' => array('dotes' => array('Cuerpo de llama (intangibilidad con contadores)'), 'defectos' => array('No saber nadar'), 'balanza' => 0),
        'despertar' => array('resumen' => 'El cielo en llamas', 'detalle' => 'El clima de la isla cambia y el fuego del ambiente te obedece — suceso de ronda (5.14).'),
        'coste_pp' => array('base' => 1500, 'familia' => 1500, 'concepto' => 3000, 'concreta' => 4500),
    ),
);

foreach ($canon as $nombre => $f) {
    $id = ope7_akuma_upsert($db, $P . 'akumas', $nombre, array(
        'familia'   => $f['familia'],
        'rareza'    => $f['rareza'],
        'tier'      => (int) $f['tier'],
        'aspecto'   => $f['aspecto'],
        'mecanica_base'       => json_encode($f['mecanica_base'], JSON_UNESCAPED_UNICODE),
        'puertas'             => json_encode($f['puertas'], JSON_UNESCAPED_UNICODE),
        'debilidades'         => json_encode($f['debilidades'], JSON_UNESCAPED_UNICODE),
        'requisitos_portador' => json_encode($f['requisitos_portador'], JSON_UNESCAPED_UNICODE),
        'influencia_ficha'    => json_encode($f['influencia_ficha'], JSON_UNESCAPED_UNICODE),
        'despertar'           => json_encode($f['despertar'], JSON_UNESCAPED_UNICODE),
        'precio_base' => 0,
        'coste_pp'    => json_encode($f['coste_pp'], JSON_UNESCAPED_UNICODE),
        'origen'      => null,
        'estado'      => 'sin_portador',
    ));
    ope7_seed_upsert($db, $P . 'objetos', 'Fruta: ' . $nombre, array(
        'categoria' => 'akuma',
        'efecto_json' => json_encode(array('akuma_id' => $id, 'tipo' => 'fruta_diablo'), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '',
        'reutilizable' => 0,
        'precio_base' => 0,
        'dureza' => 1,
        'ranuras' => 1,
        'notas' => 'Fruta del diablo (5.18): una mordida basta para que el poder se transfiera (trámite 47). Obtención por tirada (45), compra con PP (46) o eventos (5.14/5.12/5.15/5.13). No se vende en tiendas.',
    ));
    $n++;
}

// ── Pool ampliado T1/T2 (D5.1): nombres conocidos con ficha básica.
// La ficha completa (8 bloques) se hace bajo demanda con el trámite 49.
$pool = array(
    // T1 — Zoan comunes
    array('Inu Inu no Mi: Modelo Dachshund', 'zoan', 'comun', 1, 'Fruto color canela con forma de perro salchicha.', 'Forma híbrida y completa del perro salchicha: olfato fino y cuerpo bajo y veloz.'),
    array('Ushi Ushi no Mi: Modelo Bisonte', 'zoan', 'comun', 1, 'Fruto marrón con cuernos diminutos.', 'Forma híbrida y completa del bisonte: embestida y resistencia física.'),
    array('Tori Tori no Mi: Modelo Cuervo', 'zoan', 'comun', 1, 'Fruto negro con plumas rígidas.', 'Forma híbrida y completa del cuervo: vuelo corto y vista aguda.'),
    array('Uma Uma no Mi: Modelo Cebra', 'zoan', 'comun', 1, 'Fruto blanco con rayas negras.', 'Forma híbrida y completa de la cebra: galope veloz y patadas potentes.'),
    // T2 — Paramecia de cuerpo
    array('Bara Bara no Mi', 'paramecia', null, 2, 'Fruto verde partido en segmentos.', 'Tu cuerpo se divide en partes que flotan a distancia controlada (1–10 m según práctica).'),
    array('Kilo Kilo no Mi', 'paramecia', null, 2, 'Fruto gris pesado.', 'Cambias tu peso entre 1 y 10.000 kg: ligereza para saltar o peso aplastante.'),
    array('Sube Sube no Mi', 'paramecia', null, 2, 'Fruto rosa liso.', 'Tu piel es resbaladiza: los golpes y agarres se deslizan (no te sostienen las cuerdas).'),
    array('Bomu Bomu no Mi', 'paramecia', null, 2, 'Fruto granate con mecha.', 'Haces explotar cualquier parte de tu cuerpo al ritmo que marcas (contando en voz alta).'),
    array('Supa Supa no Mi', 'paramecia', null, 2, 'Fruto metálico con filo.', 'Tu cuerpo se vuelve acero cortante: cualquier parte puede ser una hoja.'),
    array('Baku Baku no Mi', 'paramecia', null, 2, 'Fruto púrpura con boca dibujada.', 'Comes cualquier cosa y la asimilas: objetos, materiales, incluso poderes de objetos.'),
    array('Doru Doru no Mi', 'paramecia', null, 2, 'Fruto dorado y pegajoso.', 'Generas cera moldeable que se endurece: estructuras, llaves y protección.'),
    array('Buki Buki no Mi', 'paramecia', null, 2, 'Fruto gris con cañones.', 'Conviertes partes de tu cuerpo en armas (cuchillas, cañones, cadenas).'),
);

foreach ($pool as $p) {
    list($nombre, $familia, $rareza, $tier, $aspecto, $mecanica) = $p;
    $id = ope7_akuma_upsert($db, $P . 'akumas', $nombre, array(
        'familia'   => $familia,
        'rareza'    => $rareza,
        'tier'      => (int) $tier,
        'aspecto'   => $aspecto,
        'mecanica_base' => json_encode(array('resumen' => $mecanica, 'pasivas' => array(), 'rupturas' => array()), JSON_UNESCAPED_UNICODE),
        'puertas'             => json_encode(array('Ficha pendiente — completa con el trámite 49 (adaptación bajo demanda)'), JSON_UNESCAPED_UNICODE),
        'debilidades'         => json_encode(array('enemigo_natural' => 'Ficha pendiente', 'mar' => 'El agua de mar te deja sin fuerzas (te hundes).'), JSON_UNESCAPED_UNICODE),
        'requisitos_portador' => json_encode(array('nivel_min' => 3), JSON_UNESCAPED_UNICODE),
        'influencia_ficha'    => json_encode(array('dotes' => array(), 'defectos' => array('No saber nadar'), 'balanza' => 0), JSON_UNESCAPED_UNICODE),
        'despertar'           => json_encode(array('resumen' => 'Ficha pendiente'), JSON_UNESCAPED_UNICODE),
        'precio_base' => 0,
        'coste_pp'    => json_encode(array('base' => $tier === 1 ? 150 : 300, 'familia' => $tier === 1 ? 150 : 300, 'concepto' => $tier === 1 ? 300 : 600, 'concreta' => $tier === 1 ? 450 : 900), JSON_UNESCAPED_UNICODE),
        'origen'      => null,
        'estado'      => 'sin_portador',
    ));
    ope7_seed_upsert($db, $P . 'objetos', 'Fruta: ' . $nombre, array(
        'categoria' => 'akuma',
        'efecto_json' => json_encode(array('akuma_id' => $id, 'tipo' => 'fruta_diablo'), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '',
        'reutilizable' => 0,
        'precio_base' => 0,
        'dureza' => 1,
        'ranuras' => 1,
        'notas' => 'Fruta del diablo (5.18): una mordida basta para que el poder se transfiera (trámite 47). Obtención por tirada (45), compra con PP (46) o eventos. No se vende en tiendas.',
    ));
    $n++;
}

// ── akuma_pool_tirada (19.7): bandas por nivel, pool global (D5.1). ──
$bandas = array(
    array(2, 'nv3+ T1–T2', 'global'),
    array(3, 'nv15+ T1–T3', 'global'),
    array(4, 'nv30+ T1–T4 (T5 nunca por tirada)', 'global'),
);
foreach ($bandas as $b) {
    ope7_seed_upsert_key($db, $P . 'akuma_pool_tirada', 'tier_max', (string) $b[0], array(
        'mar_region' => $b[2],
        'afinidad'   => $b[1],
        'activo'     => 1,
    ));
    $n++;
}

echo "Seed akumas: {$n} filas (6 canon + 12 pool + 3 bandas + objetos fruta).\n";
echo "=== DONE ===\n";
$db->close();
