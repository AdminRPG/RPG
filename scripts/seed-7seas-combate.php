<?php
/**
 * One Piece: 7 Seas · Seed de catálogos cerrados de combate (F2)
 * -----------------------------------------------------------------
 * Siembra los catálogos del cap. 11 del Manual del Jugador/Staff:
 *   · acciones_pa    — catálogo de acciones (11.3): coste y regla por categoría.
 *   · estados        — catálogo de estados alterados (11.8): física/mental/veneno/
 *                      control/positivo, con efecto JSON consumible por el motor
 *                      de combate (sistemas/combate.php).
 *   · matices_combate— matices narrativos (11.6): afinan los valores efectivos
 *                      ANTES del delta (regla de oro: el matiz no invalida la tabla).
 *
 * Idempotente por nombre (y por nombre+grado en estados). No toca nada más.
 *
 * Ejecutar:
 *   php scripts/seed-7seas-combate.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$P = 'mybb_ope_';

/** Upsert por nombre único. */
function ope7_seed_upsert(mysqli $db, string $tabla, array $fila): void
{
    $campo_id = $tabla === 'estados' ? 'nombre' : 'nombre';
    $nombre = $db->real_escape_string((string) $fila['nombre']);
    $tbl = $GLOBALS['P'] . $tabla;
    $q = $db->query("SELECT id FROM {$tbl} WHERE {$campo_id} = '{$nombre}' LIMIT 1");
    $existe = $q && $q->num_rows > 0;
    $id = $existe ? (int) $q->fetch_assoc()['id'] : 0;

    // Estados: el nombre se repite por grado (Quemadura I/II/III) → clave nombre+grado.
    if ($tabla === 'estados' && isset($fila['grado'])) {
        $grado = (int) $fila['grado'];
        $q2 = $db->query("SELECT id FROM {$tbl} WHERE nombre = '{$nombre}' AND grado = {$grado} LIMIT 1");
        $existe = $q2 && $q2->num_rows > 0;
        $id = $existe ? (int) $q2->fetch_assoc()['id'] : 0;
    }

    $campos = array();
    $vals = array();
    foreach ($fila as $k => $v) {
        $campos[] = "`{$k}`";
        $vals[] = is_null($v) ? 'NULL' : "'" . $db->real_escape_string(is_array($v) || is_object($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v) . "'";
    }
    if ($existe) {
        $sets = array();
        foreach ($fila as $k => $v) {
            $sets[] = "`{$k}` = " . (is_null($v) ? 'NULL' : "'" . $db->real_escape_string(is_array($v) || is_object($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v) . "'");
        }
        $db->query("UPDATE {$tbl} SET " . implode(', ', $sets) . " WHERE id = {$id}");
        echo "  [upd] {$tabla}: {$fila['nombre']}" . (isset($fila['grado']) ? " (grado {$fila['grado']})" : '') . "\n";
    } else {
        $db->query("INSERT INTO {$tbl} (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $vals) . ")");
        echo "  [add] {$tabla}: {$fila['nombre']}" . (isset($fila['grado']) ? " (grado {$fila['grado']})" : '') . "\n";
    }
}

echo "=== Seed de catálogos de combate 7 Seas (F2) ===\n";

// ─────────────────────────────────────────────────────────────
// acciones_pa — cap. 11.3 del Manual del Jugador
// coste_pa: número o fórmula JSON:
//   {"tipo":"metros","por":2}          → 1 PA cada 2 m
//   {"tipo":"tecnica","formula":"2+tier"}  → 2 + tier
//   {"tipo":"consumible","fuente":"ficha"} → coste de la ficha (1-3 PA)
// ─────────────────────────────────────────────────────────────
$acciones = array(
    // Movimiento
    array('nombre' => 'Moverse', 'categoria' => 'movimiento', 'coste_pa' => json_encode(array('tipo' => 'metros', 'por' => 2), JSON_UNESCAPED_UNICODE), 'regla' => json_encode(array('nota' => '1 PA cada 2 metros narrados')), 'notas' => 'Se paga proporcional a los metros que narres.'),
    array('nombre' => 'Esprintar', 'categoria' => 'movimiento', 'coste_pa' => json_encode(array('tipo' => 'metros', 'por' => 1, 'esprintar' => true), JSON_UNESCAPED_UNICODE), 'regla' => json_encode(array('nota' => '2 PA por metro; no puedes atacar esprintando')), 'notas' => 'Máxima velocidad; no puedes atacar esprintando.'),
    array('nombre' => 'Saltar', 'categoria' => 'movimiento', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Un salto normal hasta tu capacidad; los espectaculares son técnicas')), 'notas' => ''),
    array('nombre' => 'Levantarse', 'categoria' => 'movimiento', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Tras un derribo; caer al suelo te deja vulnerable')), 'notas' => ''),
    // Ataque
    array('nombre' => 'Básico (cuerpo a cuerpo o distancia)', 'categoria' => 'ataque', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Herramienta de emergencia; −1 PA con dominio bélico nv2')), 'notas' => ''),
    array('nombre' => 'Recargar', 'categoria' => 'ataque', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Pólvora y ballestas recargan como acción aparte')), 'notas' => ''),
    array('nombre' => 'Técnica', 'categoria' => 'ataque', 'coste_pa' => json_encode(array('tipo' => 'tecnica', 'formula' => '2+tier'), JSON_UNESCAPED_UNICODE), 'regla' => json_encode(array('nota' => 'Básica 3 · Media 4 · Avanzada 5 · Maestra 6 · Épica 7')), 'notas' => ''),
    // Defensa
    array('nombre' => 'Aguantar', 'categoria' => 'defensa', 'coste_pa' => '0', 'regla' => json_encode(array('nota' => 'Recibes el golpe a propósito; daño completo menos reducciones; sin Tabla 1')), 'notas' => 'El tanque y el que va a por todas.'),
    array('nombre' => 'Guardia', 'categoria' => 'defensa', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Reduce el daño a la mitad; contra técnicas no vale (P4) salvo ventaja clara')), 'notas' => 'Si el golpe es mucho más pesado, la guardia se abre o se rompe.'),
    array('nombre' => 'Parar', 'categoria' => 'defensa', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Anula el básico con DES; el peso (Tabla 2) puede empujar o trabar armas')), 'notas' => 'El luchador técnico.'),
    array('nombre' => 'Desviar', 'categoria' => 'defensa', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Anula el básico sin absorber nada; contra empujes y agarres')), 'notas' => 'Si fallas, el golpe te pilla con el arma fuera de sitio.'),
    array('nombre' => 'Esquivar', 'categoria' => 'defensa', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Anula el básico si tu reacción (PER+AGI) gana; contra áreas no basta')), 'notas' => 'La defensa universal.'),
    array('nombre' => 'Evadir', 'categoria' => 'defensa', 'coste_pa' => '3', 'regla' => json_encode(array('nota' => 'Anula y te desplazas 2–4 m; la única que saca de un ataque de área')), 'notas' => 'La más cara.'),
    array('nombre' => 'Bloquear con escudo', 'categoria' => 'defensa', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'El escudo absorbe según su ficha; un golpe enorme lo daña/rompe')), 'notas' => 'Puede detener técnicas arriesgando su integridad.'),
    array('nombre' => 'Técnica defensiva', 'categoria' => 'defensa', 'coste_pa' => json_encode(array('tipo' => 'tecnica', 'formula' => '2+tier'), JSON_UNESCAPED_UNICODE), 'regla' => json_encode(array('nota' => 'Anula hasta +1 tier; +2 reduce a la mitad; la vía estándar contra técnicas (P4)')), 'notas' => ''),
    // Objeto
    array('nombre' => 'Usar consumible', 'categoria' => 'objeto', 'coste_pa' => json_encode(array('tipo' => 'consumible', 'fuente' => 'ficha'), JSON_UNESCAPED_UNICODE), 'regla' => json_encode(array('nota' => 'Coste de su ficha (1–3 PA); si te interrumpen se gasta sin efecto')), 'notas' => 'Acción que el rival puede interrumpir.'),
    array('nombre' => 'Interactuar', 'categoria' => 'objeto', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Abrir, coger, soltar: manipulación breve del entorno')), 'notas' => ''),
    array('nombre' => 'Recoger / cambiar de arma', 'categoria' => 'objeto', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'Desenfundar y guardar en un movimiento')), 'notas' => ''),
    array('nombre' => 'Cambiar de modo racial', 'categoria' => 'objeto', 'coste_pa' => '1', 'regla' => json_encode(array('nota' => 'La acción breve de tu racial (p. ej. Lunarian)')), 'notas' => ''),
    // Mente
    array('nombre' => 'Concentración', 'categoria' => 'mente', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Sacudir un estado mental, canalizar, cargar')), 'notas' => ''),
    array('nombre' => 'Analizar / inspeccionar', 'categoria' => 'mente', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'Leer al rival, estudiar el terreno, buscar una debilidad')), 'notas' => ''),
    array('nombre' => 'Mantener / romper agarre', 'categoria' => 'mente', 'coste_pa' => '2', 'regla' => json_encode(array('nota' => 'El agarrador gasta su acción cada turno; romperlo es acción completa (FUE)')), 'notas' => ''),
    // Gratuitas
    array('nombre' => 'Hablar / declarar', 'categoria' => 'gratuita', 'coste_pa' => '0', 'regla' => json_encode(array('nota' => 'El diálogo y la declaración son el alma del duelo')), 'notas' => ''),
    array('nombre' => 'Soltar objeto / cambiar de objetivo', 'categoria' => 'gratuita', 'coste_pa' => '0', 'regla' => json_encode(array('nota' => 'Cambiar de objetivo es gratuito')), 'notas' => ''),
);
foreach ($acciones as $a) {
    ope7_seed_upsert($db, 'acciones_pa', $a);
}

// ─────────────────────────────────────────────────────────────
// estados — cap. 11.8 (físicos, mentales, positivos) + veredictos anotados
// efecto JSON consumible por el motor (ver cabecera de combate.php):
//   {"daño_residual":{"pct":1,"ignora_reducciones":true}}
//   {"pa":{"por_turno":-1} | {"por_accion":1}}
//   {"atributos":{"des":-20,"agi":-20} } (pct sobre el efectivo)
//   {"velocidad":{"pct":-30}} · {"pe":{"al_recibir":-1}}
//   {"reduccion":{"pct":10}} · {"daño":{"pct":25}}
//   {"restriccion":{"no_ataca":true,"no_canaliza":true,"solo_defiende":true,...}}
//   {"esquiva":{"agi":-20}} (Confundido: esquivar con −AGI)
// anti_spam: 1 = control (Dormido, Parálisis total, Encantado) 1 vez/combate/técnica
// ─────────────────────────────────────────────────────────────
$estados = array(
    // Físicos
    array('nombre' => 'Quemadura I', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('daño_residual' => array('pct' => 1, 'ignora_reducciones' => true)), 'duracion' => '2 turnos', 'sacudida' => 'Acción de apagarse (1 turno), agua, técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Quemadura II', 'grado' => 2, 'categoria' => 'fisico', 'efecto' => array('daño_residual' => array('pct' => 2, 'ignora_reducciones' => true)), 'duracion' => '3 turnos', 'sacudida' => 'Acción de apagarse (1 turno), agua, técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Quemadura III', 'grado' => 3, 'categoria' => 'fisico', 'efecto' => array('daño_residual' => array('pct' => 3, 'ignora_reducciones' => true)), 'duracion' => '4 turnos', 'sacudida' => 'Acción de apagarse (1 turno), agua, técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Congelación I', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('velocidad' => array('pct' => -30), 'pa' => array('por_accion' => 1)), 'duracion' => '2 turnos', 'sacudida' => 'Calor, técnica de descongelación', 'anti_spam' => 0),
    array('nombre' => 'Congelación II', 'grado' => 2, 'categoria' => 'fisico', 'efecto' => array('velocidad' => array('pct' => -50), 'pa' => array('por_accion' => 2)), 'duracion' => '2 turnos', 'sacudida' => 'Calor, técnica de descongelación', 'anti_spam' => 0),
    array('nombre' => 'Congelación III', 'grado' => 3, 'categoria' => 'fisico', 'efecto' => array('velocidad' => array('pct' => -50), 'pa' => array('por_accion' => 2), 'al_expirar' => array('estado' => 'Parálisis total', 'turnos' => 1)), 'duracion' => '3 turnos', 'sacudida' => 'Calor, técnica de descongelación', 'anti_spam' => 0),
    array('nombre' => 'Entumecido I', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('pe' => array('al_recibir' => -1), 'atributos' => array('des' => -20, 'agi' => -20)), 'duracion' => '1 turno', 'sacudida' => 'Recuperación natural al terminar; técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Entumecido II', 'grado' => 2, 'categoria' => 'fisico', 'efecto' => array('pe' => array('al_recibir' => -2), 'atributos' => array('des' => -30, 'agi' => -30)), 'duracion' => '2 turnos', 'sacudida' => 'Recuperación natural al terminar; técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Entumecido III', 'grado' => 3, 'categoria' => 'fisico', 'efecto' => array('pe' => array('al_recibir' => -2), 'atributos' => array('des' => -30, 'agi' => -30), 'al_expirar' => array('estado' => 'Parálisis', 'turnos' => 1)), 'duracion' => '1 turno', 'sacudida' => 'Técnica de limpieza', 'anti_spam' => 0),
    array('nombre' => 'Parálisis parcial', 'grado' => 1, 'categoria' => 'control', 'efecto' => array('restriccion' => array('no_canaliza' => true), 'pa' => array('por_accion' => 2)), 'duracion' => '2 turnos', 'sacudida' => 'Esperar o antídoto', 'anti_spam' => 1),
    array('nombre' => 'Parálisis total', 'grado' => 1, 'categoria' => 'control', 'efecto' => array('restriccion' => array('solo_defiende' => true)), 'duracion' => '1 turno', 'sacudida' => 'Esperar (anti-spam) o antídoto', 'anti_spam' => 1),
    array('nombre' => 'Envenenado I', 'grado' => 1, 'categoria' => 'veneno', 'efecto' => array('daño_residual' => array('pct' => 1, 'ignora_reducciones' => true)), 'duracion' => '3 turnos', 'sacudida' => 'Nunca se sacude: antídoto o purificación', 'anti_spam' => 0),
    array('nombre' => 'Envenenado II', 'grado' => 2, 'categoria' => 'veneno', 'efecto' => array('daño_residual' => array('pct' => 2, 'ignora_reducciones' => true)), 'duracion' => '3 turnos', 'sacudida' => 'Nunca se sacude: antídoto o purificación', 'anti_spam' => 0),
    array('nombre' => 'Envenenado III', 'grado' => 3, 'categoria' => 'veneno', 'efecto' => array('daño_residual' => array('pct' => 3, 'ignora_reducciones' => true)), 'duracion' => '3 turnos', 'sacudida' => 'Nunca se sacude: antídoto o purificación', 'anti_spam' => 0),
    array('nombre' => 'Hemorragia', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('daño_residual' => array('pct' => 1, 'ignora_reducciones' => true, 'indefinida' => true)), 'duracion' => 'Indefinida (hasta tratar)', 'sacudida' => 'Acción de presión/vendaje (1 turno); un Médico la cierra gratis', 'anti_spam' => 0),
    array('nombre' => 'Agarrado', 'grado' => 1, 'categoria' => 'control', 'efecto' => array('restriccion' => array('retenido' => true)), 'duracion' => 'Mientras el agarrador mantenga o 1 turno', 'sacudida' => 'Romper el agarre (comparación FUE, 1 turno) o que el agarrador suelte', 'anti_spam' => 0),
    array('nombre' => 'Desplazado', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('restriccion' => array('no_ataca_cuerpo_a_cuerpo' => true, 'no_se_desplaza' => true), 'pa' => array('recuperar_posicion' => 1)), 'duracion' => '1 turno', 'sacudida' => 'Moverse de vuelta; rompe agarres del mismo nivel o superior', 'anti_spam' => 0),
    array('nombre' => 'Cegado', 'grado' => 1, 'categoria' => 'fisico', 'efecto' => array('restriccion' => array('sin_ataques_individuales' => true, 'sin_defensas_direccionales' => true), 'percepcion' => 0), 'duracion' => '1-2 turnos', 'sacudida' => 'Mantra, técnica de limpieza, esperar', 'anti_spam' => 0),
    // Mentales (umbral del dolor y Tabla 3)
    array('nombre' => 'Sacudido', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('restriccion' => array('interrumpe_cargas' => true)), 'duracion' => 'Instantáneo', 'sacudida' => '—', 'anti_spam' => 0),
    array('nombre' => 'Tambaleante', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('pa' => array('por_turno' => -1)), 'duracion' => '1 turno', 'sacudida' => 'Acción de concentración (VOL)', 'anti_spam' => 0),
    array('nombre' => 'Desorientado', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('aplica' => array('estado' => 'Confundido', 'pleno' => true)), 'duracion' => '1 turno', 'sacudida' => 'Acción de concentración (VOL)', 'anti_spam' => 0),
    array('nombre' => 'Miedo', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('restriccion' => array('no_atacar_fuente' => true)), 'duracion' => '1-2 turnos', 'sacudida' => 'VOL (resistir al aplicarse); acción de concentración', 'anti_spam' => 0),
    array('nombre' => 'Terror', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('restriccion' => array('no_actuar_contra_fuente' => true, 'tecnicas_requieren_vol' => true)), 'duracion' => '1 turno', 'sacudida' => 'VOL superior o un aliado que te sacuda', 'anti_spam' => 0),
    array('nombre' => 'Confundido', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('restriccion' => array('no_encadena_tecnicas' => true, 'max_1_tecnica_turno' => true), 'esquiva' => array('agi' => -20)), 'duracion' => '1-2 turnos', 'sacudida' => 'VOL (acción de concentración)', 'anti_spam' => 0),
    array('nombre' => 'Encantado', 'grado' => 1, 'categoria' => 'control', 'efecto' => array('restriccion' => array('actua_a_favor_del_encantador' => true)), 'duracion' => 'Variable (hasta romperse)', 'sacudida' => 'Daño recibido, sacudida de aliado; control: 1 vez/combate/técnica', 'anti_spam' => 1),
    array('nombre' => 'Dormido', 'grado' => 1, 'categoria' => 'control', 'efecto' => array('restriccion' => array('no_actua' => true, 'no_se_puede_matar' => true)), 'duracion' => 'Hasta despertar', 'sacudida' => 'Daño o sacudida; control: 1 vez/combate/técnica', 'anti_spam' => 1),
    array('nombre' => 'Emboscado', 'grado' => 1, 'categoria' => 'mental', 'efecto' => array('restriccion' => array('sin_esquivas_ni_defensas' => true, 'primer_golpe' => true)), 'duracion' => '1 golpe', 'sacudida' => 'Solo aplica con presencia totalmente oculta; PER muy alta o Mantra lo niegan', 'anti_spam' => 0),
    // Positivos
    array('nombre' => 'Motivado', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('atributos' => array('_dos_primarios' => 3), 'daño' => array('pct' => 5)), 'duracion' => '2 turnos en combate; tema completo fuera', 'sacudida' => '—', 'anti_spam' => 0),
    array('nombre' => 'Concentrado', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('inmune' => array('Sacudido'), 'pa' => array('tecnica_preparada' => 1), 'restriccion' => array('no_defiende' => true)), 'duracion' => '1 turno', 'sacudida' => '—', 'anti_spam' => 0),
    array('nombre' => 'En guardia', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('reduccion' => array('pct' => 10)), 'duracion' => 'Se termina si atacas', 'sacudida' => '—', 'anti_spam' => 0),
    array('nombre' => 'Furioso', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('daño' => array('pct' => 25), 'defensas' => array('pct' => -1)), 'duracion' => 'Mientras estés bajo 30% PV', 'sacudida' => 'Se declara en el post (no-crunch); Furia Desatada lo sube a +35%', 'anti_spam' => 0),
    array('nombre' => 'Acelerado', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('velocidad' => array('mult' => 1.25), 'pa' => array('desplazamientos' => -1)), 'duracion' => '1-2 turnos', 'sacudida' => '—', 'anti_spam' => 0),
    array('nombre' => 'Coraje', 'grado' => 1, 'categoria' => 'positivo', 'efecto' => array('inmune' => array('Miedo'), 'terror' => array('baja_un_escalon' => true)), 'duracion' => '2 turnos o hasta que la fuente lo rompa', 'sacudida' => '—', 'anti_spam' => 0),
);
foreach ($estados as $e) {
    $e['efecto'] = json_encode($e['efecto'], JSON_UNESCAPED_UNICODE);
    ope7_seed_upsert($db, 'estados', $e);
}

// ─────────────────────────────────────────────────────────────
// matices_combate — cap. 11.6 (el matiz afina, nunca invalida)
// efecto JSON: {"per":-3,"agi":-2,"des":-2,"tabla":1,...}
// ─────────────────────────────────────────────────────────────
$matices = array(
    array('nombre' => 'Distancia cuerpo a cuerpo', 'efecto' => json_encode(array('per' => -3, 'condicion' => 'atacante a < 3 m'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Distancia larga', 'efecto' => json_encode(array('per' => 3, 'agi_esquivar_proyectiles' => -2, 'condicion' => '> 15 m'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Entorno: niebla/humo/oscuridad', 'efecto' => json_encode(array('per' => -4, 'condicion' => 'Cegado directo si es total'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Entorno: terreno inestable', 'efecto' => json_encode(array('agi' => -2, 'condicion' => 'hielo, cubierta mojada'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Entorno: viento fuerte', 'efecto' => json_encode(array('des' => -2, 'condicion' => 'a proyectiles'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Estados del defensor', 'efecto' => json_encode(array('nota' => 'Tambaleante −1 PA · Confundido −AGI · En guardia +defensa · Cegado: PER a 0 (no hay Tabla 1)'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Sorpresa (emboscada)', 'efecto' => json_encode(array('no_tabla_1' => true, 'condicion' => 'solo técnica defensiva/Haki/Mantra niega el primer golpe'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Ventaja numérica 1vN', 'efecto' => json_encode(array('per' => 2, 'agi' => 2, 'por_enemigo_inactivo' => true, 'condicion' => 'el solitario gana +2 PER/AGI por cada enemigo que no le ataca ese turno'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Terreno táctico (altura, cobertura, ángulo ciego)', 'efecto' => json_encode(array('per' => 2, 'des' => 2, 'rango' => '±2 a ±5 según lo roleado', 'condicion' => 'atacante en altura suma DES; defensor tras cobertura suma PER para reaccionar'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
    array('nombre' => 'Cubierta (combate naval)', 'efecto' => json_encode(array('agi' => -2, 'condicion' => 'a quien no está acostumbrado a luchar sobre las olas'), JSON_UNESCAPED_UNICODE), 'tabla' => 1),
);
foreach ($matices as $m) {
    ope7_seed_upsert($db, 'matices_combate', $m);
}

echo "=== Seed F2 completado ===\n";
