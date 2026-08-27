<?php
/**
 * One Piece: 7 Seas · Seed de catálogos de progresión y economía (F3.2)
 * ---------------------------------------------------------------------
 * Siembra los catálogos cerrados del cap. 9/10 del Manual del Jugador:
 *   · objetos          — armas por calidad (bono + precio del 10.3), armaduras y
 *                        escudos (arma ×2,5 / ≈arma), consumibles con su coste de
 *                        PA y efecto, herramientas de oficio, diales T1–T4 y
 *                        objetos por rareza (200/1.000/5.000/20.000 ฿).
 *   · economia_config  — moneda, bandas (0,5×–2× / −20%–+30%), límites de stock.
 *
 * Números cerrados del manual (números sagrados) — no recalibrar.
 * Idempotente por nombre. No toca nada más.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-progresion.php
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

echo "=== Seed F3.2 — objetos y economía ===\n";
$n = 0;

// ── Armas por calidad (9.3/10.3): bono de daño + precio (tasación desde Wazamono). ──
$armas = array(
    // calidad, bono, precio, cupo, ranuras
    array('inferior', 0, 100, null),
    array('comun', 2, 500, null),
    array('superior', 5, 2500, null),
    array('wazamono', 9, 25000, null),   // tasación — no se vende en tienda
    array('ryo', 13, 100000, 50),
    array('o', 18, 400000, 21),
    array('saijo', 25, 1500000, 12),
);
foreach ($armas as $a) {
    $efecto = array('bono_dano' => (int) $a[1], 'tipo_efecto' => 'ninguno');
    if ($a[0] === 'superior') {
        $efecto['tipo_efecto'] = 'cortante_hemorragia_I'; // Superior: Hemorragia I (9.4)
    }
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => 'Arma ' . ucfirst($a[0]),
        'categoria' => 'arma',
        'calidad' => $a[0],
        'efecto_json' => json_encode($efecto, JSON_UNESCAPED_UNICODE),
        'coste_pa' => '',
        'reutilizable' => 1,
        'precio_base' => (int) $a[2],
        'cupo_mundial' => $a[3],
        'dureza' => $a[0] === 'inferior' ? 1 : ($a[0] === 'comun' ? 2 : ($a[0] === 'superior' ? 3 : 8)),
        'ranuras' => 1,
        'notas' => $a[3] !== null ? 'Cupo mundial ' . $a[3] . ' — solo se tasa, no se vende.' : '',
    ));
    $n++;
}

// ── Armaduras y escudos (10.3): precio = arma ×2,5 · escudo ≈ arma. ──
$defensas = array(
    // nombre, categoria, calidad, precio, reduccion, integridad
    array('Armadura inferior', 'armadura', 'inferior', 250, 1, 3),
    array('Armadura común', 'armadura', 'comun', 1250, 2, 4),
    array('Armadura superior', 'armadura', 'superior', 6250, 3, 6),
    array('Escudo inferior', 'escudo', 'inferior', 100, 1, 3),
    array('Escudo común', 'escudo', 'comun', 500, 2, 4),
    array('Escudo superior', 'escudo', 'superior', 2500, 3, 6),
);
foreach ($defensas as $d) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $d[0],
        'categoria' => $d[1],
        'calidad' => $d[2],
        'efecto_json' => json_encode(array('reduccion' => (int) $d[4], 'integridad_max' => (int) $d[5]), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '',
        'reutilizable' => 1,
        'precio_base' => (int) $d[3],
        'dureza' => 3,
        'ranuras' => 1,
        'notas' => 'La calidad también protege (−' . $d[4] . ' de daño, integridad ' . $d[5] . ').',
    ));
    $n++;
}

// ── Consumibles (9.5/10.3): efecto mecánico + coste de PA + precio. ──
$consumibles = array(
    // nombre, efecto, pa, precio, notas
    array('Poción de curación común', array('cura_pct' => 10, 'tipo' => 'pv'), 2, 300, 'Cura 10% PV máx.'),
    array('Poción de curación superior', array('cura_pct' => 15, 'tipo' => 'pv'), 2, 800, 'Cura 15% PV máx.'),
    array('Elixir de energía común', array('cura_pct' => 10, 'tipo' => 'pe'), 2, 350, 'Restaura 10% PE máx.'),
    array('Elixir de energía superior', array('cura_pct' => 15, 'tipo' => 'pe'), 2, 900, 'Restaura 15% PE máx.'),
    array('Vendaje', array('estado' => 'detiene_hemorragia'), 1, 150, 'Detiene Hemorragia (1 turno de presión).'),
    array('Antídoto I', array('cura' => 'envenenado_I'), 1, 400, 'Cura Envenenado I y su estado extra.'),
    array('Estimulante', array('estado' => 'acelerado', 'turnos' => 2), 2, 600, 'Acelerado (Vel ×1,25, −1 PA en desplazamientos).'),
    array('Somnífero', array('estado' => 'dormido', 'antispam' => 1), 3, 1000, 'Intenta aplicar Dormido (anti-spam 1/combate).'),
    array('Bomba de humo', array('estado' => 'cegado', 'turnos' => 2), 2, 500, 'Cegado en la zona (PER a 0).'),
    array('Veneno de arma I', array('estado' => 'envenenado_I', 'cobertura' => 'filo'), 2, 700, 'El siguiente golpe aplica Envenenado I + estado extra.'),
    array('Ración de viaje', array('cura_pct' => 5, 'tipo' => 'pv_pe', 'fuera_combate' => 1), 1, 50, 'Alimento para travesías; 5% PV/PE fuera de combate (ración 17.6, 50 ฿ — D4.6).'),
);
foreach ($consumibles as $c) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $c[0],
        'categoria' => 'consumible',
        'efecto_json' => json_encode($c[1], JSON_UNESCAPED_UNICODE),
        'coste_pa' => (string) $c[2],
        'reutilizable' => 0,
        'precio_base' => (int) $c[3],
        'dureza' => 1,
        'ranuras' => 1,
        'notas' => (string) $c[4],
    ));
    $n++;
}

// ── Herramientas de oficio (9.6/10.3). ──
$herr = array(
    array('Kit de oficio básico', 1000, 'Necesario para ejercer el oficio (1 ranura).'),
    array('Kit de oficio gama alta', 10000, 'Niveles 4–5 de oficio (1 ranura).'),
);
foreach ($herr as $h) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $h[0], 'categoria' => 'herramienta',
        'efecto_json' => json_encode(array('requisito_oficio' => 1), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '', 'reutilizable' => 1, 'precio_base' => (int) $h[1],
        'dureza' => 2, 'ranuras' => 1, 'notas' => (string) $h[2],
    ));
    $n++;
}

// ── Dials de las islas del cielo (9.8). ──
$dials = array(
    array('Dial T1 utilidad', 750, 'Bola · Luz · Nubes · Sonido · Imagen (común).'),
    array('Dial T2 elemental', 12500, 'Viento · Agua · Fuego · Calor · Frío · Relámpagos · Corte · Impacto (integridad 300–1.000).'),
    array('Dial T3 táctico', 50000, 'Propulsión · Hierro (raro).'),
    array('Dial T4 Rechazo', 500000, 'Devuelve el daño ×2 con retroceso (Mercado Negro).'),
);
foreach ($dials as $d) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $d[0], 'categoria' => 'dial',
        'efecto_json' => json_encode(array('reutilizable' => 1, 'integridad' => $d[2] === 'Dial T2 elemental' ? 600 : 100), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '1-2', 'reutilizable' => 1, 'precio_base' => (int) $d[1],
        'dureza' => 2, 'ranuras' => 1, 'notas' => (string) $d[2],
    ));
    $n++;
}

// ── Objetos por rareza (10.3): 200/1.000/5.000/20.000 ฿. ──
$rareza = array(
    array('Artefacto común', 'comun', 200),
    array('Artefacto poco común', 'poco_comun', 1000),
    array('Artefacto raro', 'raro', 5000),
    array('Artefacto mercado negro', 'mercado_negro', 20000),
);
foreach ($rareza as $r) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $r[0], 'categoria' => 'herramienta', 'rareza' => $r[1],
        'efecto_json' => json_encode(array('tipo' => 'rareza'), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '', 'reutilizable' => 1, 'precio_base' => (int) $r[2],
        'dureza' => 1, 'ranuras' => 1, 'notas' => 'Rareza ' . $r[1] . ' — el Mercado Negro no se vende en tiendas NPC.',
    ));
    $n++;
}

// ── Utensilios de navegación (17.7): el trámite 38 valida el utensilio declarado. ──
$utensilios = array(
    array('Brújula Común', 'comun', 200, 'Navegar los Blues (sin ella pierdes ventaja ahí).'),
    array('Log Pose', 'poco_comun', 1000, 'El utensilio adecuado: navegar por el clima correcto de la isla (15.4).'),
    array('Eternal Pose', 'raro', 5000, 'Fija una isla: permite volver sobre tus pasos (17.7).'),
    array('Log Pose del Nuevo Mundo', 'raro', 5000, 'Necesario en el Nuevo Mundo: fija hasta tres islas en su memoria (17.7).'),
);
foreach ($utensilios as $u) {
    ope7_seed_upsert($db, 'objetos', array(
        'nombre' => $u[0], 'categoria' => 'herramienta', 'rareza' => $u[1],
        'efecto_json' => json_encode(array('tipo' => 'navegacion', 'mitigador_irt' => -1, 'menos_horas' => 12), JSON_UNESCAPED_UNICODE),
        'coste_pa' => '', 'reutilizable' => 1, 'precio_base' => (int) $u[2],
        'dureza' => 2, 'ranuras' => 1, 'notas' => (string) $u[3],
    ));
    $n++;
}

// ── economia_config (10.5). ──
$q = $db->query("SELECT id FROM {$P}economia_config LIMIT 1");
if (!$q || $q->num_rows === 0) {
    $db->query("INSERT INTO {$P}economia_config
        (moneda, banda_min, banda_max, margen_min, margen_max, stock_items, stock_consumibles, stock_armas, redondeo)
        VALUES ('berries', 0.50, 2.00, -0.20, 0.30, 10, 10, 3, 'decenas')");
    echo "  economia_config: sembrada (bandas 0,5×–2× · margen −20%/+30% · stock 10/10/3)\n";
} else {
    echo "  economia_config: ya existe (no se toca)\n";
}

echo "  objetos sembrados/actualizados: {$n}\n";
echo "=== DONE — seed F3.2 ===\n";
