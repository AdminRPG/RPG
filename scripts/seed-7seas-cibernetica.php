<?php
/**
 * One Piece: 7 Seas · Seed de cibernética y familias legendarias (F5.4) — 5.22
 * ----------------------------------------------------------------------------
 * Siembra:
 *   · implantes            — 3 fichas resueltas de la guía maestra (5.22 §8):
 *                            «Brazo de kairoseki» (N2, extremidades) · «Ojo
 *                            mecánico» (N1, cabeza) · «Exoesqueleto de asedio»
 *                            (N3, torso) — con ranuras, defectos (balanza a 0),
 *                            precios y disponibilidad. El resto de conceptos se
 *                            adapta bajo demanda (trámites 56/59, skill).
 *   · defectos             — los 6 de la tabla 5.22 §A.6 (puntuaciones −1..−3).
 *   · familias_legendarias — los 3 linajes de 23.7 (Línea D. · Los Vientomar ·
 *                            La Casa Cindral) con dote, defecto «La sangre
 *                            llama», cupo y lore.
 *
 * Números cerrados del manual (calibración 5.22 §5, Sesión 11) — no recalibrar.
 * Idempotente por nombre. No toca nada más.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-cibernetica.php
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

$J = function ($v) { return json_encode($v, JSON_UNESCAPED_UNICODE); };

// ── Defectos de implantes (5.22 §A.6, balanza a 0) ──
$defectos = array(
    array('Cuerpo pesado',                -1, 'El miembro mecánico pesa: −1 a maniobras ágiles con esa zona.'),
    array('Mantenimiento oneroso',        -2, 'El mantenimiento por ronda cuesta el doble (23.3).'),
    array('Vulnerabilidad al magnetismo', -2, 'El magnetismo fuerte te desestabiliza: estados de parálisis/agarre con +1 grado.'),
    array('Ancla al agua',                -1, 'El mar apaga la electrónica: sumergido, el implante no funciona (23.4).'),
    array('Rechazo social',               -1, 'La gente te mira y te esquiva: −1 a influencia social con desconocidos.'),
    array('El cuerpo manda',              -3, 'El implante te domina en los momentos de tensión: el staff puede imponerte una acción instintiva 1 vez por tema.'),
);
foreach ($defectos as $d) {
    ope7_seed_upsert($db, $P . 'defectos', $d[0], array(
        'efecto'             => $J(array('mecanica' => $d[2])),
        'puntuacion'         => $d[1],
        'requisitos'         => $J(array()),
        'incompatibilidades' => $J(array()),
        'activo'             => 1,
    ));
    $n++;
}
echo "  [OK] defectos de implante (" . count($defectos) . ")\n";

// ── Implantes (guía 5.22 §8, ejemplos resueltos) ──
$implantes = array(
    array(
        'nombre'    => 'Brazo de kairoseki',
        'zona'      => 'extremidades',
        'nivel'     => 'N2',
        'puerta'    => 20,
        'requisitos'=> array('res' => 45, 'vol' => 35),
        'ranuras'   => array(
            array('tipo' => 'material', 'detalle' => 'kairoseki (máx. O Wazomono, 5.18): contacto → anula intangibilidad y debilita', 'puntos' => 0),
            array('tipo' => 'armamento', 'detalle' => 'cañón de dial de impacto — Daño puro (PA de equipo)', 'puntos' => 1),
            array('tipo' => 'bonificador', 'detalle' => 'FUE +3 (ligado a la función del brazo)', 'puntos' => 3),
        ),
        'precios'   => array('instalacion' => 500000, 'pp' => 400, 'mantenimiento' => 10000),
        'defectos'  => array(
            array('nombre' => 'Cuerpo pesado', 'puntos' => -1),
            array('nombre' => 'Vulnerabilidad al magnetismo', 'puntos' => -2),
            array('nombre' => 'Ancla al agua', 'puntos' => -1),
        ),
        'disponibilidad' => array('isla' => 'Ciudad/Reino o peligrosidad 30+', 'suceso' => 'mercado negro / científico (5.13/5.14/5.20)', 'nota' => 'El kairoseki no se vende en tiendas: viene del suceso.'),
    ),
    array(
        'nombre'    => 'Ojo mecánico',
        'zona'      => 'cabeza',
        'nivel'     => 'N1',
        'puerta'    => 10,
        'requisitos'=> array('res' => 30, 'vol' => 30, 'int' => 30),
        'ranuras'   => array(
            array('tipo' => 'material', 'detalle' => 'Bueno (+2 al bono, 5.8 — el bono de calidad va en el detalle, no en la balanza)', 'puntos' => 0),
            array('tipo' => 'bonificador', 'detalle' => 'PER +3 (ligado a la visión)', 'puntos' => 3),
            array('tipo' => 'habilidad', 'detalle' => 'Sigilo (T3, detección): visión térmica niega emboscadas — PER rival vs tu PER, Tabla 1 de 5.10; 1 vez por combate', 'puntos' => 0),
        ),
        'precios'   => array('instalacion' => 100000, 'pp' => 200, 'mantenimiento' => 2500),
        'defectos'  => array(
            array('nombre' => 'Rechazo social', 'puntos' => -1),
            array('nombre' => 'Mantenimiento oneroso', 'puntos' => -2),
        ),
        'disponibilidad' => array('isla' => 'Ciudad/Reino o peligrosidad 30+', 'suceso' => '', 'nota' => 'Prótesis de reemplazo: al restaurar una pérdida real, la mitad del coste se considera reemplazo (decisión narrativa del staff). El mantenimiento oneroso duplica el coste por ronda (23.3).'),
        'disponibilidad' => array('isla' => 'Ciudad/Reino o peligrosidad 30+', 'suceso' => '', 'nota' => 'Prótesis de reemplazo: al restaurar una pérdida real, la mitad del coste se considera reemplazo (decisión narrativa del staff).'),
    ),
    array(
        'nombre'    => 'Exoesqueleto de asedio',
        'zona'      => 'torso',
        'nivel'     => 'N3',
        'puerta'    => 35,
        'requisitos'=> array('res' => 75, 'vol' => 60),
        'ranuras'   => array(
            array('tipo' => 'material', 'detalle' => 'Acero Superior (+5, 5.8 — el bono de calidad va en el detalle, no en la balanza) o Madera de Adán (+PV al armazón, 5.17)', 'puntos' => 0),
            array('tipo' => 'bonificador', 'detalle' => 'FUE +5 (tope, 5.22 §A.4 — ligado al esfuerzo del exoesqueleto)', 'puntos' => 5),
            array('tipo' => 'habilidad', 'detalle' => 'Movilidad (T2): reposicionarse sin PA de desplazamiento', 'puntos' => 0),
            array('tipo' => 'habilidad', 'detalle' => 'Daño de Ruptura (5.14, no registrado con condiciones): golpes de derribo +1 grado de ruptura contra estructuras; no toca el daño a personas', 'puntos' => 1),
        ),
        'precios'   => array('instalacion' => 2500000, 'pp' => 600, 'mantenimiento' => 40000),
        'defectos'  => array(
            array('nombre' => 'Mantenimiento oneroso', 'puntos' => -2),
            array('nombre' => 'El cuerpo manda', 'puntos' => -3),
            array('nombre' => 'Cuerpo pesado', 'puntos' => -1),
        ),
        'disponibilidad' => array('isla' => 'Ciudad/Reino o peligrosidad 30+', 'suceso' => 'subasta / cargamento (5.13/5.14)', 'nota' => 'Con Logia/Zoan, no aporta pasivas en forma elemental/total (5.22 §A.7).'),
    ),
);
foreach ($implantes as $i) {
    ope7_seed_upsert($db, $P . 'implantes', $i['nombre'], array(
        'zona'           => $i['zona'],
        'nivel'          => $i['nivel'],
        'puerta_nivel'   => $i['puerta'],
        'requisitos'     => $J($i['requisitos']),
        'ranuras'        => $J($i['ranuras']),
        'precios'        => $J($i['precios']),
        'defectos'       => $J($i['defectos']),
        'disponibilidad' => $J($i['disponibilidad']),
        'activo'         => 1,
    ));
    $n++;
}
echo "  [OK] implantes (" . count($implantes) . ")\n";

// ── Familias Legendarias (23.7) ──
$familias = array(
    array('Línea D.',        'D. de la Voluntad', 'La sangre llama', 5, 'La voluntad heredada: VOL +5 efectiva para estados y puertas de Haki (5.19). Quienes la portan sonríen ante la muerte.'),
    array('Los Vientomar',   'Sangre de los mares', 'La sangre llama', 4, 'La mar en la sangre: IRT −1 en travesías (17.3) y anticipar un oráculo (5.16).'),
    array('La Casa Cindral', 'Herederos del fuego', 'La sangre llama', 3, 'El fuego de la casa: técnicas de fuego con +1 tier efectivo de requisito, sin subir daño (5.7).'),
);
foreach ($familias as $f) {
    ope7_seed_upsert($db, $P . 'familias_legendarias', $f[0], array(
        'dote'    => $f[1],
        'defecto' => $f[2],
        'cupo'    => $f[3],
        'lore'    => $f[4],
    ));
    $n++;
}
echo "  [OK] familias legendarias (" . count($familias) . ")\n";

echo "\n=== Seed 5.22 cibernética: {$n} filas (upsert idempotente) ===\n";