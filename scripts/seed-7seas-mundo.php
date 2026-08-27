<?php
/**
 * One Piece: 7 Seas · Seed del mundo (F4.1) — 5.12/5.14/5.16/5.17
 * -----------------------------------------------------------------
 * Siembra los catálogos del mundo vivo y sus sistemas hermanos:
 *   · mares             — los 7 mares con orden, peligrosidad base e IRT (5.14).
 *   · islas + isla_estado — las 17 islas del catálogo 5.14 con ficha viva
 *                          (13 parámetros) y su histórico de arranque.
 *   · zonas             — 1–3 zonas clave por isla (5.15/16.6).
 *   · tipos_barcos      — 8 tipos × N1–N3 (5.17/18.4).
 *   · maderas_casco     — 5 calidades (5.17/18.5).
 *   · modulos_barcos    — 10 módulos del catálogo (5.17/18.6).
 *   · oraculos_catalogo — 7 tipos de incidente de travesía (5.16/17.4).
 *   · transportes       — civil/clandestino/gobierno (5.16/17.6).
 *   · facciones + rangos_faccion — 8 facciones jugables con escalera (5.12).
 *
 * Números cerrados del manual (números sagrados) — no recalibrar.
 * Idempotente por nombre/clave única. No toca nada más.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-mundo.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$P = 'mybb_ope_';

/** Upsert por nombre único. */
function ope7_seed_upsert(mysqli $db, string $tabla, array $fila): void
{
    $tbl = $GLOBALS['P'] . $tabla;
    $nombre = $db->real_escape_string((string) $fila['nombre']);
    $q = $db->query("SELECT id FROM {$tbl} WHERE nombre = '{$nombre}' LIMIT 1");
    $existe = $q && $q->num_rows > 0;
    $id = $existe ? (int) $q->fetch_assoc()['id'] : 0;
    if ($id > 0) {
        $sets = array();
        foreach ($fila as $k => $v) {
            if ($k === 'nombre') {
                continue;
            }
            $sets[] = "`{$k}` = " . ($v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'");
        }
        $db->query("UPDATE {$tbl} SET " . implode(', ', $sets) . " WHERE id = {$id}");
    } else {
        $cols = array();
        $vals = array();
        foreach ($fila as $k => $v) {
            $cols[] = "`{$k}`";
            $vals[] = $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
        }
        $db->query("INSERT INTO {$tbl} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
    }
}

echo "=== Seed F4.1 — mundo vivo (mares, islas, barcos, facciones) ===\n";

// ── 1. Mares (5.14): orden de región · peligrosidad base · IRT (Blue 1…ZR 4). ──
$mares = array(
    array('nombre' => 'Blue Este',        'orden' => 1, 'region' => 'Este',    'peligrosidad_base' => 6,  'irt_base' => 1, 'descripcion' => 'El mar más tranquilo; puerta de entrada para nuevos jugadores.'),
    array('nombre' => 'Blue Sur',         'orden' => 2, 'region' => 'Sur',     'peligrosidad_base' => 10, 'irt_base' => 1, 'descripcion' => 'Mares cálidos con rutas comerciales y desiertos costeros.'),
    array('nombre' => 'Blue Norte',       'orden' => 3, 'region' => 'Norte',   'peligrosidad_base' => 9,  'irt_base' => 1, 'descripcion' => 'Aguas frías, puertos mercantes y astilleros.'),
    array('nombre' => 'Blue Oeste',       'orden' => 4, 'region' => 'Oeste',   'peligrosidad_base' => 12, 'irt_base' => 1, 'descripcion' => 'Volcanes, acantilados y tierra de nadie.'),
    array('nombre' => 'Paraíso',          'orden' => 5, 'region' => 'Paraíso', 'peligrosidad_base' => 21, 'irt_base' => 2, 'descripcion' => 'La mitad de la Grand Line: cielos, ciudades de agua y reinos submarinos.'),
    array('nombre' => 'Nuevo Mundo',      'orden' => 6, 'region' => 'Nuevo Mundo', 'peligrosidad_base' => 34, 'irt_base' => 3, 'descripcion' => 'La segunda mitad: forja, gigantes y la ley del más fuerte.'),
    array('nombre' => 'Zona restringida', 'orden' => 7, 'region' => 'Zona restringida', 'peligrosidad_base' => 45, 'irt_base' => 4, 'descripcion' => 'Más allá del faro final; solo llegan los que pueden pagar el precio.'),
);
$marId = array();
foreach ($mares as $m) {
    ope7_seed_upsert($db, 'mares', $m);
    $r = $db->query("SELECT id FROM {$P}mares WHERE nombre = '" . $db->real_escape_string($m['nombre']) . "' LIMIT 1");
    $marId[$m['nombre']] = (int) $r->fetch_assoc()['id'];
}
echo "  mares: " . count($mares) . " ✓\n";

// ── 2. Islas del catálogo 5.14 (ficha viva, 13 parámetros). ──
//    Cada ficha: mar, nombre, slug, canon, peligrosidad, afiliación,
//    fuerza defensiva (nivel + quien manda), desarrollo, población/orden,
//    recursos, oferta/demanda, clima/log pose, lugares clave, sucesos,
//    hitos, recompensas/tesoros, facciones, modo de viaje.
$islas = array(
    array('mar' => 'Blue Este', 'nombre' => 'Isla Dawn', 'slug' => 'dawn', 'canon' => 1,
        'peligrosidad' => 4, 'afiliacion' => 'local', 'fd_nivel' => 2, 'quien_manda' => 'Consejo de aldea (la alcaldesa)',
        'desarrollo' => 'Aldea', 'poblacion' => 'Pueblo pequeño · estable',
        'recursos' => 'Grano, huerta, pesca; importa herramientas y telas',
        'od' => 'Oferta media en grano · demanda media en utensilios',
        'clima' => 'Templado, estaciones suaves; Log Pose fácil',
        'lugares' => array('El pueblo costero', 'Un viejo molino', 'La colina del faro'),
        'sucesos' => 'Ninguno (isla de arranque tranquila)',
        'hitos' => 'Una tormenta hace décadas cambió la línea de costa',
        'tesoros' => 'Un naufragio modesto en un arrecife',
        'facciones' => 'Presencia local (bajo mundo ínfimo); oportunidad para reclutar gente',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Este', 'nombre' => 'Vila Seleno', 'slug' => 'vila-seleno', 'canon' => 0,
        'peligrosidad' => 6, 'afiliacion' => 'local', 'fd_nivel' => 1, 'quien_manda' => 'Consejo de pescadores',
        'desarrollo' => 'Aldea', 'poblacion' => 'Pueblo pequeño · estable/tenso en invernada',
        'recursos' => 'Pesca de luna, sal, algas; importa madera y metal',
        'od' => 'Alta oferta de pescado salado · demanda de herramientas navales',
        'clima' => 'Nieblas súbitas; Log Pose estabiliza con paciencia',
        'lugares' => array('El muelle', 'El salazón', 'La cueva de las luces'),
        'sucesos' => 'Un banco de peces-luna atrae a cazadores de toda la región',
        'hitos' => 'Una disputa de pesca ganada por la aldea',
        'tesoros' => 'Perlas negras escondidas por un pescador viejo',
        'facciones' => 'Un broker de North Blue busca llevar los tesoros al mercado',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Sur', 'nombre' => 'Alabasta', 'slug' => 'alabasta', 'canon' => 1,
        'peligrosidad' => 22, 'afiliacion' => 'gobierno', 'fd_nivel' => 14, 'quien_manda' => 'Casa real (original del foro)',
        'desarrollo' => 'Reino', 'poblacion' => 'Reino grande · tenso (sequía)',
        'recursos' => 'Cereales de oasis, minerales, especias; importa agua y carnes',
        'od' => 'Alta oferta de especias · demanda crítica de agua en la estación seca',
        'clima' => 'Desierto, tormentas de arena; Log Pose de estación seca difícil',
        'lugares' => array('La capital oasis', 'El puerto fluvial', 'Un templo hundido en la arena'),
        'sucesos' => 'Una banda de forajidos corta las caravanas de agua',
        'hitos' => 'Una guerra civil terminada hace años por un pacto entre facciones',
        'tesoros' => 'Un santuario subterráneo con ofrendas antiguas',
        'facciones' => 'Marines presentes, Revolución infiltrada, mafia local',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Sur', 'nombre' => 'Isla Baterilla', 'slug' => 'isla-baterilla', 'canon' => 1,
        'peligrosidad' => 10, 'afiliacion' => 'local', 'fd_nivel' => 4, 'quien_manda' => 'Tribus de aldea',
        'desarrollo' => 'Aldea', 'poblacion' => 'Pueblo pequeño · estable',
        'recursos' => 'Fruta, miel, madera blanda; importa metal',
        'od' => 'Oferta de miel y fruta estacional · demanda de herramientas',
        'clima' => 'Tropical, selva densa; Log Pose fácil',
        'lugares' => array('La selva', 'Un enorme árbol ancestral', 'Una fuente de agua dulce'),
        'sucesos' => 'Un animal de presa nuevo ataca los sembrados',
        'hitos' => 'Descubrimiento de una caverna con pinturas antiguas',
        'tesoros' => 'Un tesoro escondido por un pirata enterrado en la selva',
        'facciones' => 'Presencia tribal, sin Marina',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Norte', 'nombre' => 'Reino Lvneel', 'slug' => 'reino-lvneel', 'canon' => 1,
        'peligrosidad' => 12, 'afiliacion' => 'gobierno', 'fd_nivel' => 9, 'quien_manda' => 'Casa mercantil reinante (original del foro)',
        'desarrollo' => 'Reino (capital ciudad)', 'poblacion' => 'Reino comercial · tenso por aduanas',
        'recursos' => 'Mercancías del Norte, talento naviero; importa casi todo lo no producido',
        'od' => 'Oferta media-alta en bienes manufacturados · demanda de materias primas',
        'clima' => 'Frío húmedo, puertos abiertos; Log Pose medio',
        'lugares' => array('El puerto mercante', 'La lonja', 'El astillero'),
        'sucesos' => 'Un contrabandista famoso juega al ratón y el gato con la aduana',
        'hitos' => 'Una gran feria que enriqueció al reino',
        'tesoros' => 'Un barco hundido cargado de mercancía valiosa',
        'facciones' => 'Marina activa, comerciantes influyentes, bajo mundo medio',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Norte', 'nombre' => 'Puerto Gavia', 'slug' => 'puerto-gavia', 'canon' => 0,
        'peligrosidad' => 18, 'afiliacion' => 'mixta', 'fd_nivel' => 12, 'quien_manda' => 'Alcalde + capitán de la base naval',
        'desarrollo' => 'Ciudad', 'poblacion' => 'Ciudad media · estable (el bajo mundo bulle)',
        'recursos' => 'Astilleros, carpintería naval, vela y cordelería; importa madera dura',
        'od' => 'Alta oferta de servicios navales · demanda de madera',
        'clima' => 'Ventoso, corrientes del Norte; Log Pose medio-alto',
        'lugares' => array('El astillero principal', 'La academia naval', 'El mercado flotante'),
        'sucesos' => 'Un pedido gigante de fragatas de la Marina a la vez que un alijo clandestino prepara la salida',
        'hitos' => 'Un famoso motín naval aplastado por la guarnición',
        'tesoros' => 'Planos de un barco de diseño raro',
        'facciones' => 'Marina fuerte, Astilleros y Maquinistas navales, contrabando',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Oeste', 'nombre' => 'Archipiélago Cendra', 'slug' => 'archipielago-cendra', 'canon' => 0,
        'peligrosidad' => 15, 'afiliacion' => 'salvaje', 'fd_nivel' => 5, 'quien_manda' => 'Tribus locales autónomas',
        'desarrollo' => 'Aldea (territorios dispersos)', 'poblacion' => 'Varias aldeas · estable',
        'recursos' => 'Obsidiana, sal, ceniza volcánica, minerales; importa metal',
        'od' => 'Alta oferta de obsidiana · demanda de herramientas forjadas',
        'clima' => 'Caluroso, vientos de ceniza; Log Pose se desestabiliza cerca de los volcanes',
        'lugares' => array('El volcán dormido', 'Un mercado de obsidiana', 'Las cuevas de obsidiana'),
        'sucesos' => 'Un extraño incendio mina la salina',
        'hitos' => 'Una alianza de tribus contra un intento de conquista',
        'tesoros' => 'Una veta de obsidiana rara (material para armas de calidad)',
        'facciones' => 'Bajo mundo (compra la obsidiana), sin Marina',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Blue Oeste', 'nombre' => 'Península Cóncava', 'slug' => 'peninsula-concava', 'canon' => 0,
        'peligrosidad' => 20, 'afiliacion' => 'salvaje', 'fd_nivel' => 6, 'quien_manda' => 'Bandas locales que se disputan refugios',
        'desarrollo' => 'Aldeas dispersas', 'poblacion' => 'Asentamientos pocos · tenso',
        'recursos' => 'Madera de roca, maleza, caballos salvajes; rumores de vetas de hierro',
        'od' => 'Oferta baja y cara · demanda alta de seguridad y armas',
        'clima' => 'Azotado por vientos; Log Pose difícil en la costa (arrecifes)',
        'lugares' => array('El bosque de piedra', 'La cala de los naufragios', 'Un fortín abandonado'),
        'sucesos' => 'Una banda nueva desafía a la que controla la cala',
        'hitos' => 'Un salvador local unió los refugios contra un ataque pirata',
        'tesoros' => 'Hierro de buena calidad oculto en la península',
        'facciones' => 'Sin gobierno, cazadores de recompensas merodean',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Paraíso', 'nombre' => 'Skypiea', 'slug' => 'skypiea', 'canon' => 1,
        'peligrosidad' => 26, 'afiliacion' => 'local', 'fd_nivel' => 16, 'quien_manda' => 'Consejo de ancianos de las nubes',
        'desarrollo' => 'Ciudad del cielo', 'poblacion' => 'Población grande · estable/tenso (límites con los mares del cielo)',
        'recursos' => 'Diales, cultivos de nube-densos, joyería de cristal de nube',
        'od' => 'Alta oferta de diales · demanda de viajeros/mercancías de abajo',
        'clima' => 'Imposible de alcanzar por mar; se llega por corriente ascendente o balsa (Log Pose de nube)',
        'lugares' => array('El mercado del cielo', 'La plaza del campanario', 'El borde de las nubes'),
        'sucesos' => 'Una incursión de hombres de abajo busca los diales',
        'hitos' => 'La «Caída», un antiguo conflicto grabado en las nubes',
        'tesoros' => 'Una forja celestial de diales raros',
        'facciones' => 'El consejo, mercaderes de diales, sin Marina',
        'modo_viaje' => 'skypiea', 'utensilio' => 'corriente ascendente o balsa (diale)'),
    array('mar' => 'Paraíso', 'nombre' => 'Water Seven', 'slug' => 'water-seven', 'canon' => 1,
        'peligrosidad' => 24, 'afiliacion' => 'local', 'fd_nivel' => 11, 'quien_manda' => 'Alcalde y cuerpos de agua',
        'desarrollo' => 'Ciudad', 'poblacion' => 'Ciudad grande · estable',
        'recursos' => 'Astilleros de élite, carpintería, velas; importa madera y metal de calidad',
        'od' => 'Alta oferta de barcos/carpintería · demanda de madera dura y herramientas',
        'clima' => 'Mar calmo y canalizado; Log Pose medio',
        'lugares' => array('El gran astillero', 'El coliseo de la acuática', 'Los muelles bajos'),
        'sucesos' => 'Un gran encargo de un barco insignia atrae a astilleros rivales',
        'hitos' => 'Una gran marejada que reconstruyó la ciudad sobre pilotes',
        'tesoros' => 'Un barco de modelo antiguo (posible Meitou naval)',
        'facciones' => 'Astilleros poderosos, Marina con base, contrabando en los muelles',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Paraíso', 'nombre' => 'Isla Gyojin', 'slug' => 'isla-gyojin', 'canon' => 1,
        'peligrosidad' => 30, 'afiliacion' => 'mixta', 'fd_nivel' => 18, 'quien_manda' => 'Reino submarino (casa real original)',
        'desarrollo' => 'Reino', 'poblacion' => 'Reino grande · tenso (presión del exterior)',
        'recursos' => 'Perlas, corales, criaturas marinas domesticadas, bioluminiscencia',
        'od' => 'Oferta alta en perlas/corales · demanda de alimentos de superficie y selva',
        'clima' => 'Inaccesible salvo por burbuja o submarino; navegación especial',
        'lugares' => array('El palacio de la concha', 'El mercado del fondo', 'Los jardines de coral'),
        'sucesos' => 'Un pez cartógrafo descubre una bolsa de cría amenazada',
        'hitos' => 'Un conflicto con el exterior que dejó marcas, y que el reino aún recuerda',
        'tesoros' => 'Perla negra de gran tamaño; criaturas marinas para domesticar',
        'facciones' => 'El reino, comerciantes de perlas, bajo mundo (traficantes de criaturas raras)',
        'modo_viaje' => 'burbuja', 'utensilio' => 'burbuja o submarino'),
    array('mar' => 'Paraíso', 'nombre' => 'Archipiélago Coro', 'slug' => 'archipielago-coro', 'canon' => 0,
        'peligrosidad' => 21, 'afiliacion' => 'salvaje', 'fd_nivel' => 7, 'quien_manda' => 'Grupos autónomos de la isla',
        'desarrollo' => 'Aldea/pueblo disperso', 'poblacion' => 'Varios asentamientos · estable',
        'recursos' => 'Hierbas medicinales, pájaros, madera rara',
        'od' => 'Oferta en hierbas raras · demanda de alimentos perecederos',
        'clima' => 'Marejada de clima inestable; Log Pose difícil',
        'lugares' => array('El santuario de la colina', 'El mercado de hierbas', 'Una garganta cubierta de musgo'),
        'sucesos' => 'Un sanador famoso prepara una cura y atrae pacientes',
        'hitos' => 'La erección del santuario por un viejo pueblo',
        'tesoros' => 'Plantas medicinales raras (material de alquimista)',
        'facciones' => 'Intermitente presencia de la Marina; curiosidad de la Corona',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Nuevo Mundo', 'nombre' => 'Dressrosa', 'slug' => 'dressrosa', 'canon' => 1,
        'peligrosidad' => 34, 'afiliacion' => 'gobierno', 'fd_nivel' => 20, 'quien_manda' => 'Casa reinante original del foro',
        'desarrollo' => 'Reino', 'poblacion' => 'Reino grande · estable',
        'recursos' => 'Forja, piedra, vino de especias; importa madera y fruta',
        'od' => 'Alta oferta de armas y forja · demanda de alimentos y madera',
        'clima' => 'Seco y cálido, meseta; Log Pose medio',
        'lugares' => array('El coliseo', 'La meseta del martillo', 'Los barrios de forjadores'),
        'sucesos' => 'Un torneo de forjadores atrae a maestros de todo el mundo',
        'hitos' => 'Un famoso forjador tiene su taller en la meseta',
        'tesoros' => 'Materiales de forja nobles (adán; armas de calidad)',
        'facciones' => 'Marina presente, titanes de la forja, bajo mundo (compra de armas)',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Nuevo Mundo', 'nombre' => 'Wano', 'slug' => 'wano', 'canon' => 1,
        'peligrosidad' => 38, 'afiliacion' => 'mixta', 'fd_nivel' => 26, 'quien_manda' => 'Casas del acero (originales del foro)',
        'desarrollo' => 'Reino (autogobierno feudal)', 'poblacion' => 'Reino grande · tenso (facciones internas)',
        'recursos' => 'Acero de calidad, hierro, cultivos de arroz; importa casi todo lo exterior',
        'od' => 'Oferta alta en acero de élite · demanda de alimentos y pertrechos',
        'clima' => 'Cuesta llegar (costas altas); Log Pose difícil, mares de acceso cargados',
        'lugares' => array('El castillo del alto', 'Los valles de forja', 'Las tierras bajas'),
        'sucesos' => 'Una disputa interna entre casas por el control del acero',
        'hitos' => 'El cierre del país hace generaciones y su pacto con las facciones',
        'tesoros' => 'El legendario «acero del país» (material para Meitou)',
        'facciones' => 'Las casas del acero, Marina al borde, resistencia contra el aislamiento',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Nuevo Mundo', 'nombre' => 'Elbaf', 'slug' => 'elbaf', 'canon' => 1,
        'peligrosidad' => 40, 'afiliacion' => 'local', 'fd_nivel' => 28, 'quien_manda' => 'Consejo de clanes gigantes',
        'desarrollo' => 'Aldea (reino clánico)', 'poblacion' => 'Clan grande · estable',
        'recursos' => 'Madera de Elbaf (de alta calidad), caza, miel; importa metal y telas',
        'od' => 'Oferta alta en maderas nobles · demanda de bienes hechos a escala',
        'clima' => 'Bosque frío y húmedo, montañas; Log Pose medio',
        'lugares' => array('El gran roble', 'El salón de los clanes', 'El bosque-laberinto'),
        'sucesos' => 'Un clan desafía al consejo por el acceso al gran roble',
        'hitos' => 'Una gran batalla entre clanes sellada por un pacto de honor',
        'tesoros' => 'Ramas del gran roble (para maderas de barco)',
        'facciones' => 'Clanes gigantes, comerciantes de maderas raras',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Nuevo Mundo', 'nombre' => 'Isla Rei', 'slug' => 'isla-rei', 'canon' => 0,
        'peligrosidad' => 28, 'afiliacion' => 'gobierno', 'fd_nivel' => 19, 'quien_manda' => 'Rey elegido en el banquete de los rebaños',
        'desarrollo' => 'Reino (capital ciudad)', 'poblacion' => 'Reino medio · tenso (cambio de gobernante)',
        'recursos' => 'Vino, especias, información; el secreto: el mercado de la información',
        'od' => 'Oferta en vino/especias · demanda de información (contraste)',
        'clima' => 'Mediterráneo, puerto noble; Log Pose medio',
        'lugares' => array('El palacio de las embajadas', 'La lonja del vino', 'El laberinto de los espías'),
        'sucesos' => 'La corona está por decidir y las embajadas se esfuerzan por influir',
        'hitos' => 'El tratado que fundó la isla como sede diplomática',
        'tesoros' => 'Secretos de Estado (valiosos en el bajo mundo)',
        'facciones' => 'Todas las grandes potencias con embajada; el mejor caldo de cultivo de espías',
        'modo_viaje' => 'normal', 'utensilio' => ''),
    array('mar' => 'Zona restringida', 'nombre' => 'Isla Celeste-Faro', 'slug' => 'isla-celeste-faro', 'canon' => 0,
        'peligrosidad' => 45, 'afiliacion' => 'gobierno', 'fd_nivel' => 32, 'quien_manda' => 'Capitán de la fortaleza (guarnición de élite)',
        'desarrollo' => 'Ciudad-fortaleza', 'poblacion' => 'Guarnición y colonos · tenso',
        'recursos' => 'Materiales de guerra, defensas, información estratégica',
        'od' => 'Oferta en defensas y pertrechos · demanda de víveres y relevos',
        'clima' => 'Mar agresivo, corrientes extremas; requiere Log Pose de élite o barco de Zona restringida',
        'lugares' => array('El faro', 'La muralla del mar', 'El arsenal'),
        'sucesos' => 'Se rumorea una expedición perdida más allá del faro',
        'hitos' => 'La defensa que detuvo una incursión masiva del mundo desconocido',
        'tesoros' => 'Mapas de la Zona restringida y coordenadas secretas',
        'facciones' => 'Marina de élite, exploradores, cazadores de lo desconocido',
        'modo_viaje' => 'restringido', 'utensilio' => 'Log Pose de élite o barco de Zona restringida'),
);

$islaId = array();
foreach ($islas as $isla) {
    $mar = $isla['mar'];
    $estado = array(
        'peligrosidad'          => $isla['peligrosidad'],
        'afiliacion'            => $isla['afiliacion'],
        'fuerza_defensiva_nivel'=> $isla['fd_nivel'],
        'quien_manda'           => $isla['quien_manda'],
        'guarnicion'            => json_encode(array('nivel' => $isla['fd_nivel'], 'quien' => $isla['quien_manda']), JSON_UNESCAPED_UNICODE),
        'fortificaciones'       => json_encode(array(), JSON_UNESCAPED_UNICODE),
        'desarrollo'            => $isla['desarrollo'],
        'poblacion_orden'       => json_encode(array('poblacion' => $isla['poblacion']), JSON_UNESCAPED_UNICODE),
        'recursos'              => json_encode(array('texto' => $isla['recursos']), JSON_UNESCAPED_UNICODE),
        'oferta_demanda'        => json_encode(array('texto' => $isla['od']), JSON_UNESCAPED_UNICODE),
        'clima_logpose'         => $isla['clima'],
        'lugares_clave'         => json_encode($isla['lugares'], JSON_UNESCAPED_UNICODE),
        'sucesos'               => json_encode(array($isla['sucesos']), JSON_UNESCAPED_UNICODE),
        'hitos'                 => json_encode(array($isla['hitos']), JSON_UNESCAPED_UNICODE),
        'recompensas_tesoros'   => json_encode(array($isla['tesoros']), JSON_UNESCAPED_UNICODE),
        'presencia_facciones'   => json_encode(array($isla['facciones']), JSON_UNESCAPED_UNICODE),
        'updated'               => time(),
    );

    // Upsert de la isla.
    $slug = $db->real_escape_string($isla['slug']);
    $q = $db->query("SELECT id FROM {$P}islas WHERE slug = '{$slug}' LIMIT 1");
    $existe = $q && $q->num_rows > 0;
    $id = $existe ? (int) $q->fetch_assoc()['id'] : 0;
    $filaIsla = array(
        'mar_id' => $marId[$mar],
        'nombre' => $isla['nombre'],
        'slug'   => $isla['slug'],
        'es_canon' => $isla['canon'],
        'descripcion' => '',
        'modo_viaje' => $isla['modo_viaje'],
        'utensilio_requerido' => $isla['utensilio'],
    );
    if ($id > 0) {
        $sets = array();
        foreach ($filaIsla as $k => $v) {
            if ($k === 'slug') {
                continue;
            }
            $sets[] = "`{$k}` = '" . $db->real_escape_string((string) $v) . "'";
        }
        $db->query("UPDATE {$P}islas SET " . implode(', ', $sets) . " WHERE id = {$id}");
    } else {
        $cols = array();
        $vals = array();
        foreach ($filaIsla as $k => $v) {
            $cols[] = "`{$k}`";
            $vals[] = "'" . $db->real_escape_string((string) $v) . "'";
        }
        $db->query("INSERT INTO {$P}islas (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")");
        $id = (int) $db->insert_id;
    }
    $islaId[$isla['slug']] = $id;

    // Upsert del estado vivo.
    $sets = array();
    foreach ($estado as $k => $v) {
        $sets[] = "`{$k}` = " . ($v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'");
    }
    $db->query("INSERT INTO {$P}isla_estado (isla_id, " . implode(', ', array_keys($estado)) . ")
                VALUES ({$id}, " . implode(', ', array_map(function ($v) use ($db) {
                    return $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
                }, array_values($estado))) . ")
                ON DUPLICATE KEY UPDATE " . implode(', ', $sets));

    // Histórico de arranque (fuente 'arranque').
    $db->query("INSERT IGNORE INTO {$P}isla_estado_historico
        (isla_id, ronda, campo, de_valor, a_valor, motivo, fuente, fecha)
        VALUES ({$id}, 0, 'arranque', '', '" . $db->real_escape_string($isla['afiliacion']) . "',
                'Catálogo 5.14 — lote inicial de 17 islas', 'arranque', " . time() . ")");
}
echo "  islas: " . count($islas) . " ✓\n";

// ── 3. Zonas clave (1–3 por isla, 5.15/16.6). ──
$zonasPorIsla = array(
    'dawn'               => array('El pueblo costero', 'La colina del faro'),
    'vila-seleno'        => array('El muelle', 'La cueva de las luces'),
    'alabasta'           => array('La capital oasis', 'El puerto fluvial'),
    'isla-baterilla'     => array('La selva', 'El árbol ancestral'),
    'reino-lvneel'       => array('El puerto mercante', 'La lonja'),
    'puerto-gavia'       => array('El astillero principal', 'El mercado flotante'),
    'archipielago-cendra'=> array('El volcán dormido', 'El mercado de obsidiana'),
    'peninsula-concava'  => array('La cala de los naufragios', 'El fortín abandonado'),
    'skypiea'            => array('El mercado del cielo', 'El borde de las nubes'),
    'water-seven'        => array('El gran astillero', 'Los muelles bajos'),
    'isla-gyojin'        => array('El palacio de la concha', 'El mercado del fondo'),
    'archipielago-coro'  => array('El santuario de la colina', 'El mercado de hierbas'),
    'dressrosa'          => array('El coliseo', 'Los barrios de forjadores'),
    'wano'               => array('Los valles de forja', 'El castillo del alto'),
    'elbaf'              => array('El gran roble', 'El salón de los clanes'),
    'isla-rei'           => array('El palacio de las embajadas', 'El laberinto de los espías'),
    'isla-celeste-faro'  => array('El faro', 'El arsenal'),
);
$nZonas = 0;
foreach ($zonasPorIsla as $slug => $zonas) {
    $isla = isset($islaId[$slug]) ? $islaId[$slug] : 0;
    if (!$isla) {
        continue;
    }
    foreach ($zonas as $z) {
        $zname = $db->real_escape_string($z);
        $q = $db->query("SELECT id FROM {$P}zonas WHERE isla_id = {$isla} AND nombre = '{$zname}' LIMIT 1");
        if ($q && $q->num_rows > 0) {
            continue;
        }
        $db->query("INSERT INTO {$P}zonas (isla_id, nombre, afiliacion, recursos, fuerza_defensiva)
                    VALUES ({$isla}, '{$zname}', 'local', NULL, NULL)");
        $nZonas++;
    }
}
echo "  zonas: {$nZonas} nuevas ✓\n";

// ── 4. Tipos de barco × N1–N3 (5.17/18.4). ──
$tipos = array(
    array('nombre' => 'Bote de remos',     'plazas' => array(2, 2, 2),   'casco' => array(200, 300, 400),  'maniobra' => array(10, 12, 14), 'ranuras' => array(0, 0, 0),   'canones' => array(0, 0, 0),    'mitigador_irt' => 0,  'precio' => array(0, 5000, 10000),     'madera_minima' => 'Pino de marea', 'es_faccion_npc' => 0),
    array('nombre' => 'Balandro',          'plazas' => array(4, 4, 4),   'casco' => array(500, 700, 900),  'maniobra' => array(20, 25, 30), 'ranuras' => array(1, 2, 3),   'canones' => array(2, 2, 2),    'mitigador_irt' => -1, 'precio' => array(50000, 80000, 120000),  'madera_minima' => 'Pino de marea', 'es_faccion_npc' => 0),
    array('nombre' => 'Goleta',            'plazas' => array(6, 6, 6),   'casco' => array(1000, 1300, 1600), 'maniobra' => array(30, 35, 40), 'ranuras' => array(2, 3, 4),   'canones' => array(4, 4, 4),    'mitigador_irt' => -1, 'precio' => array(200000, 300000, 450000), 'madera_minima' => 'Pino de marea', 'es_faccion_npc' => 0),
    array('nombre' => 'Carabela',          'plazas' => array(8, 8, 8),   'casco' => array(1800, 2200, 2600), 'maniobra' => array(25, 30, 35), 'ranuras' => array(3, 4, 5),   'canones' => array(6, 6, 6),    'mitigador_irt' => -2, 'precio' => array(800000, 1200000, 1800000), 'madera_minima' => 'Roble del sur', 'es_faccion_npc' => 0),
    array('nombre' => 'Velero',            'plazas' => array(12, 12, 12), 'casco' => array(2400, 2900, 3400), 'maniobra' => array(40, 45, 50), 'ranuras' => array(4, 5, 6),   'canones' => array(6, 6, 6),    'mitigador_irt' => -2, 'precio' => array(1500000, 2500000, 3500000), 'madera_minima' => 'Roble del sur', 'es_faccion_npc' => 0),
    array('nombre' => 'Corbeta de guerra', 'plazas' => array(16, 16, 16), 'casco' => array(4000, 5000, 6000), 'maniobra' => array(35, 40, 45), 'ranuras' => array(5, 7, 9),   'canones' => array(10, 10, 10), 'mitigador_irt' => -2, 'precio' => array(5000000, 8000000, 12000000), 'madera_minima' => 'Corazón de tormenta', 'es_faccion_npc' => 0),
    array('nombre' => 'Galeón pesado',     'plazas' => array(25, 25, 25), 'casco' => array(7000, 8500, 10000), 'maniobra' => array(30, 35, 40), 'ranuras' => array(7, 9, 11),  'canones' => array(14, 14, 14), 'mitigador_irt' => -3, 'precio' => array(15000000, 25000000, 35000000), 'madera_minima' => 'Madera de Adán', 'es_faccion_npc' => 0),
    array('nombre' => 'Acorazado insignia','plazas' => array(40, 40, 40), 'casco' => array(12000, 15000, 18000), 'maniobra' => array(35, 40, 45), 'ranuras' => array(10, 13, 16), 'canones' => array(20, 20, 20), 'mitigador_irt' => -3, 'precio' => array(50000000, 80000000, 120000000), 'madera_minima' => 'Madera de Eva', 'es_faccion_npc' => 1),
);
foreach ($tipos as $t) {
    ope7_seed_upsert($db, 'tipos_barcos', array(
        'nombre' => $t['nombre'],
        'plazas' => json_encode($t['plazas'], JSON_UNESCAPED_UNICODE),
        'casco' => json_encode($t['casco'], JSON_UNESCAPED_UNICODE),
        'maniobra' => json_encode($t['maniobra'], JSON_UNESCAPED_UNICODE),
        'ranuras' => json_encode($t['ranuras'], JSON_UNESCAPED_UNICODE),
        'canones' => json_encode($t['canones'], JSON_UNESCAPED_UNICODE),
        'mitigador_irt' => $t['mitigador_irt'],
        'precio' => $t['precio'][0],
        'precios' => json_encode($t['precio'], JSON_UNESCAPED_UNICODE),
        'madera_minima' => $t['madera_minima'],
        'es_faccion_npc' => $t['es_faccion_npc'],
    ));
}
echo "  tipos_barcos: " . count($tipos) . " ✓\n";

// ── 5. Maderas de casco (5.17/18.5). ──
$maderas = array(
    array('nombre' => 'Pino de marea',          'mares' => array('Blue Este', 'Blue Sur', 'Blue Norte', 'Blue Oeste'), 'precio' => 0,      'rareza' => 'comun'),
    array('nombre' => 'Roble del sur',          'mares' => array('Blue Este', 'Blue Sur', 'Blue Norte', 'Blue Oeste', 'Paraíso'), 'precio' => 5000, 'rareza' => 'poco_comun'),
    array('nombre' => 'Corazón de tormenta',    'mares' => array('Paraíso'), 'precio' => 15000, 'rareza' => 'raro'),
    array('nombre' => 'Madera de Adán',         'mares' => array('Nuevo Mundo'), 'precio' => 50000, 'rareza' => 'raro'),
    array('nombre' => 'Madera de Eva',          'mares' => array('Zona restringida'), 'precio' => 200000, 'rareza' => 'mercado_negro'),
);
foreach ($maderas as $m) {
    ope7_seed_upsert($db, 'maderas_casco', array(
        'nombre' => $m['nombre'],
        'mares' => json_encode($m['mares'], JSON_UNESCAPED_UNICODE),
        'precio' => $m['precio'],
        'rareza' => $m['rareza'],
    ));
}
echo "  maderas_casco: " . count($maderas) . " ✓\n";

// ── 6. Módulos de barco (5.17/18.6). ──
$modulos = array(
    array('nombre' => 'Tienda',               'efecto' => 'Habilita vender desde el barco', 'precio' => 25000, 'requisito_oficio' => 'Comerciante'),
    array('nombre' => 'Batería de cañones',   'efecto' => '+2 cañones o +25% daño de salva', 'precio' => 20000, 'requisito_oficio' => 'Astillero'),
    array('nombre' => 'Bodega de carga',      'efecto' => '+50% espacio para mercancías',    'precio' => 15000, 'requisito_oficio' => 'Carpintero'),
    array('nombre' => 'Cocina de a bordo',    'efecto' => '+1 ración por persona y oráculo de clima −1 grado', 'precio' => 10000, 'requisito_oficio' => 'Cocinero'),
    array('nombre' => 'Laboratorio',          'efecto' => 'Fabricar consumibles en travesía (reduce víveres de un oráculo)', 'precio' => 10000, 'requisito_oficio' => 'Químico'),
    array('nombre' => 'Enfermería',           'efecto' => 'Reduce un grado la gravedad de un estado por tema-trama', 'precio' => 10000, 'requisito_oficio' => 'Médico'),
    array('nombre' => 'Refuerzo de casco',    'efecto' => '+500 PV al casco', 'precio' => 30000, 'requisito_oficio' => 'Astillero'),
    array('nombre' => 'Revestimiento de resina', 'efecto' => 'Inmersión: llegas a islas submarinas', 'precio' => 40000, 'requisito_oficio' => 'Astillero nv4'),
    array('nombre' => 'Fondo de kairoseki',   'efecto' => 'Oráculos de criaturas marinas −1 grado', 'precio' => 50000, 'requisito_oficio' => 'Mercado Negro'),
    array('nombre' => 'Velas mecánicas',      'efecto' => 'Navegar en calmas (Calm Belt, viento nulo)', 'precio' => 60000, 'requisito_oficio' => 'Maquinista Naval'),
);
foreach ($modulos as $m) {
    ope7_seed_upsert($db, 'modulos_barcos', array(
        'nombre' => $m['nombre'],
        'efecto' => json_encode(array('texto' => $m['efecto']), JSON_UNESCAPED_UNICODE),
        'ranura' => 1,
        'precio' => $m['precio'],
        'requisito_oficio' => $m['requisito_oficio'],
    ));
}
echo "  modulos_barcos: " . count($modulos) . " ✓\n";

// ── 7. Oráculos de travesía (5.16/17.4). ──
$oraculos = array(
    array('tipo' => 'Ala de tormenta',       'gravedad' => 'menor', 'efectos' => array('tipo' => 'encuentro', 'horas' => 12, 'viveres' => 1, 'danio' => 'leve')),
    array('tipo' => 'Asalto',                'gravedad' => 'media', 'efectos' => array('tipo' => 'encuentro', 'horas' => 24, 'viveres' => 2, 'danio' => 'moderado')),
    array('tipo' => 'Patrulla del Gobierno', 'gravedad' => 'menor', 'efectos' => array('tipo' => 'encuentro', 'horas' => 12, 'viveres' => 1, 'danio' => 'ninguno')),
    array('tipo' => 'Coloso del abismo',     'gravedad' => 'grave', 'efectos' => array('tipo' => 'encuentro', 'horas' => 48, 'viveres' => 3, 'danio' => 'grave')),
    array('tipo' => 'Maremoto',              'gravedad' => 'media', 'efectos' => array('tipo' => 'clima', 'horas' => 24, 'viveres' => 2, 'danio' => 'leve')),
    array('tipo' => 'Remolino',              'gravedad' => 'media', 'efectos' => array('tipo' => 'clima', 'horas' => 24, 'viveres' => 2, 'danio' => 'leve', 'desvio' => true)),
    array('tipo' => 'Huracán',               'gravedad' => 'grave', 'efectos' => array('tipo' => 'clima', 'horas' => 48, 'viveres' => 3, 'danio' => 'grave', 'desvio' => true)),
);
foreach ($oraculos as $o) {
    // `oraculos_catalogo` no tiene `nombre`: la clave única es `tipo`.
    $tbl = $P . 'oraculos_catalogo';
    $tipo = $db->real_escape_string($o['tipo']);
    $q = $db->query("SELECT id FROM {$tbl} WHERE tipo = '{$tipo}' LIMIT 1");
    $existe = $q && $q->num_rows > 0;
    $id = $existe ? (int) $q->fetch_assoc()['id'] : 0;
    $efectos = json_encode($o['efectos'], JSON_UNESCAPED_UNICODE);
    if ($id > 0) {
        $db->query("UPDATE {$tbl} SET gravedad = '{$o['gravedad']}', efectos = '{$efectos}', activo = 1 WHERE id = {$id}");
    } else {
        $db->query("INSERT INTO {$tbl} (tipo, gravedad, efectos, activo) VALUES ('{$tipo}', '{$o['gravedad']}', '{$efectos}', 1)");
    }
}
echo "  oraculos_catalogo: " . count($oraculos) . " ✓\n";

// ── 8. Transportes (5.16/17.6). ──
$transportes = array(
    array('nombre' => 'Pasajeros civiles',   'tipo' => 'civil',       'tarifa' => array('Blue Este' => 1000, 'Blue Sur' => 1000, 'Blue Norte' => 1000, 'Blue Oeste' => 1000, 'Paraíso' => 5000, 'Nuevo Mundo' => 15000, 'Zona restringida' => 0), 'reglas_acceso' => array('wanted_recargo' => '+1000 por cada millón de recompensa')),
    array('nombre' => 'Clandestino',         'tipo' => 'clandestino', 'tarifa' => array('Blue Este' => 2000, 'Blue Sur' => 2000, 'Blue Norte' => 2000, 'Blue Oeste' => 2000, 'Paraíso' => 10000, 'Nuevo Mundo' => 30000, 'Zona restringida' => 0), 'reglas_acceso' => array('solo' => 'piratas y revolucionarios', 'sin_recargo' => true)),
    array('nombre' => 'Navíos del Gobierno', 'tipo' => 'gobierno',    'tarifa' => array('Blue Este' => 0, 'Blue Sur' => 0, 'Blue Norte' => 0, 'Blue Oeste' => 0, 'Paraíso' => 0, 'Nuevo Mundo' => 0, 'Zona restringida' => 0), 'reglas_acceso' => array('gratis' => 'en servicio', 'buscados' => 'soborno 5000 + 500 por millón o engaño')),
);
foreach ($transportes as $t) {
    // `transportes` no tiene `nombre`: la clave es el ENUM `tipo`.
    $tbl = $P . 'transportes';
    $tipo = $db->real_escape_string($t['tipo']);
    $q = $db->query("SELECT id FROM {$tbl} WHERE tipo = '{$tipo}' LIMIT 1");
    $existe = $q && $q->num_rows > 0;
    $id = $existe ? (int) $q->fetch_assoc()['id'] : 0;
    $tarifa = json_encode($t['tarifa'], JSON_UNESCAPED_UNICODE);
    $reglas = json_encode($t['reglas_acceso'], JSON_UNESCAPED_UNICODE);
    if ($id > 0) {
        $db->query("UPDATE {$tbl} SET tarifa = '{$tarifa}', reglas_acceso = '{$reglas}' WHERE id = {$id}");
    } else {
        $db->query("INSERT INTO {$tbl} (tipo, tarifa, reglas_acceso) VALUES ('{$tipo}', '{$tarifa}', '{$reglas}')");
    }
}
echo "  transportes: " . count($transportes) . " ✓\n";

// ── 9. Facciones (5.12): 8 jugables + escalera de rangos. ──
// D4.7: cupos de cúspide (13.3) y requisito duro de rep_faccion por rango
// (rep_min = (orden−1)×15, termómetro 13.4). Ajustable en este seed.
$facciones = array(
    array('nombre' => 'Piratas', 'familia' => 'pirata', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Novato', 'Tripulante', 'Oficial', 'Capitán', 'Comodoro', 'Emperador del Mar'),
        'cupos' => array('Emperador del Mar' => 4)),
    array('nombre' => 'Marines', 'familia' => 'institucional', 'tiene_sueldo' => 1, 'cupo_max' => null,
        'rangos' => array('Recluta', 'Marinero', 'Sargento', 'Teniente', 'Capitán', 'Comodoro', 'Vicealmirante', 'Almirante'),
        'cupos' => array('Almirante' => 3)),
    array('nombre' => 'Gobierno Mundial', 'familia' => 'institucional', 'tiene_sueldo' => 1, 'cupo_max' => null,
        'rangos' => array('Funcionario', 'Agente', 'Analista', 'Oficial de enlace', 'Delegado', 'Director de sección'),
        'cupos' => array('Director de sección' => 5)),
    array('nombre' => 'Revolucionarios', 'familia' => 'libre', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Simpatizante', 'Militante', 'Célula', 'Oficial de célula', 'Comandante', 'Estado Mayor'),
        'cupos' => array('Estado Mayor' => 5)),
    array('nombre' => 'Bajo Mundo', 'familia' => 'criminal', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Informante', 'Corredor', 'Broker', 'Capo local', 'Capo regional', 'Señor del bajo mundo'),
        'cupos' => array('Señor del bajo mundo' => 3)),
    array('nombre' => 'Cazadores', 'familia' => 'libre', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Novato', 'Cazador', 'Cazador veterano', 'Maestro cazador', 'Leyenda'),
        'cupos' => array('Leyenda' => 5)),
    array('nombre' => 'Civiles', 'familia' => 'civil', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Aldeano', 'Ciudadano', 'Comerciante', 'Notable', 'Noble', 'Cabeza de casa noble'),
        'cupos' => array('Cabeza de casa noble' => 6)),
    array('nombre' => 'Aventurero libre', 'familia' => 'libre', 'tiene_sueldo' => 0, 'cupo_max' => null,
        'rangos' => array('Aventurero'), 'cupos' => array()),
);
$termometro_nota = array(
    'pirata' => 'Infamia + proezas (13.3)', 'institucional' => 'Servicio + poder (Marines) / resultados + confianza (Gobierno)',
    'libre' => 'Lealtad + liberaciones (revolucionarios) / cobras + capturas (cazadores)',
    'criminal' => 'Red + solvencia (5.13)', 'civil' => 'Influencia + economía (nobleza también el favor de los reinos)',
);
foreach ($facciones as $f) {
    $fila = array(
        'nombre' => $f['nombre'],
        'familia' => $f['familia'],
        'tiene_sueldo' => $f['tiene_sueldo'],
        'cupo_max' => $f['cupo_max'],
        'coeficientes_mv' => json_encode(array('peso_base' => 1.0), JSON_UNESCAPED_UNICODE),
    );
    ope7_seed_upsert($db, 'facciones', $fila);
    $r = $db->query("SELECT id FROM {$P}facciones WHERE nombre = '" . $db->real_escape_string($f['nombre']) . "' LIMIT 1");
    $fid = (int) $r->fetch_assoc()['id'];
    foreach ($f['rangos'] as $i => $rn) {
        $rnE = $db->real_escape_string($rn);
        $cupo = isset($f['cupos'][$rn]) ? (int) $f['cupos'][$rn] : null;
        $req = json_encode(array(
            'rep_min' => ($i) * 15, // rango orden i+1 → rep_min = i×15 (D4.7)
            'nota' => 'Termómetro de la facción: ' . ($termometro_nota[$f['familia']] ?? ''),
        ), JSON_UNESCAPED_UNICODE);
        $q = $db->query("SELECT id FROM {$P}rangos_faccion WHERE faccion_id = {$fid} AND nombre = '{$rnE}' LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $rid = (int) $q->fetch_assoc()['id'];
            $cupoSql = $cupo === null ? 'NULL' : $cupo;
            $db->query("UPDATE {$P}rangos_faccion SET orden = " . ($i + 1) . ", es_cuspide = " . (($i + 1) === count($f['rangos']) ? 1 : 0) . ", requisitos = '{$req}', cupo = {$cupoSql} WHERE id = {$rid}");
            continue;
        }
        $cupoSql = $cupo === null ? 'NULL' : $cupo;
        $db->query("INSERT INTO {$P}rangos_faccion (faccion_id, nombre, orden, requisitos, beneficios, cupo, es_cuspide)
                    VALUES ({$fid}, '{$rnE}', " . ($i + 1) . ", '{$req}', NULL, {$cupoSql}, " . (($i + 1) === count($f['rangos']) ? 1 : 0) . ")");
    }
    // Rango inicial = primero.
    $db->query("UPDATE {$P}facciones SET rango_inicial = (SELECT MIN(id) FROM {$P}rangos_faccion WHERE faccion_id = {$fid}) WHERE id = {$fid}");
}
echo "  facciones: " . count($facciones) . " con escaleras, cupos y requisitos ✓\n";

echo "\nSeed F4.1 completo.\n";
