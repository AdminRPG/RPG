<?php
/**
 * One Piece: 7 Seas · Motor de combate (5.10 — dominio puro)
 * -----------------------------------------------------------
 * Funciones PURAS (sin SQL): PA por turno, costes de acción, fórmula de daño,
 * bandas de delta, tablas 1/2/3 + choque + umbral del dolor, matices, estados,
 * umbrales de vida, 1 contra varios, sala y la resolución de cierre.
 *
 * Fuente: Manual del Jugador cap. 11 y Manual del Staff §11 (operativo).
 * Regla de oro del sistema: sin dados · no se dicta el resultado · el matiz
 * afina, no invalida · la resolución ocurre AL CIERRE (aquí se computa), en
 * vivo solo se valida (aviso, nunca bloqueo).
 *
 * Los valores efectivos que entran en los deltas ya incluyen raciales, dotes,
 * estados y Haki (los calcula dominio/personajes.php).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Atributos válidos del sistema (orden canónico). */
function ope7_combate_atributos()
{
    return array('fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol');
}

/**
 * PA por turno: 6 + AGI/10 + Nivel/5 (redondeo al entero más cercano, una sola
 * vez). Luego se aplican los modificadores «por turno» (se suman, no se
 * multiplican): 1vN +3 · dote Preparación +1 · Tambaleante −1 · Furioso −1 a
 * defensas (no al presupuesto)…
 *
 * @param array $mods claves: 'agi', 'nivel', 'solitario_contra' (int: nº de
 *                    oponentes si > 1), 'estados' (array de nombres), 'dotes'
 *                    (array de nombres), 'defectos' (array de nombres).
 */
function ope7_combate_pa_turno($agi, $nivel, array $mods = array())
{
    $pa = (int) round(6 + $agi / 10 + $nivel / 5);

    // 1 contra varios (P5): +3 PA frente a dos o más oponentes.
    if ((int) ($mods['solitario_contra'] ?? 0) >= 2) {
        $pa += 3;
    }
    // Dote Preparación (5.4, requisito AGI 40).
    if (in_array('Preparación', (array) ($mods['dotes'] ?? array()), true)) {
        $pa += 1;
    }
    // Tambaleante (umbral del dolor): −1 PA por turno.
    if (in_array('Tambaleante', (array) ($mods['estados'] ?? array()), true)) {
        $pa -= 1;
    }
    return max(0, $pa);
}

/** PA de una técnica: 2 + tier (Básica 3 … Épica 7). */
function ope7_combate_pa_tecnica($tier)
{
    return 2 + (int) $tier;
}

/**
 * Coste PA de una acción del catálogo (acciones_pa). Acepta el número directo
 * o la fórmula JSON sembrada: {"tipo":"tecnica","formula":"2+tier"} ·
 * {"tipo":"metros","por":2} · {"tipo":"consumible","fuente":"ficha"}.
 */
function ope7_combate_coste_pa($accion, $tier = 0)
{
    $coste = is_array($accion) ? ($accion['coste_pa'] ?? '') : (is_numeric($accion) ? $accion : '');
    if (is_string($coste) && $coste !== '' && $coste[0] === '{') {
        $f = json_decode($coste, true);
        if ($f) {
            if (($f['tipo'] ?? '') === 'tecnica') {
                $expr = (string) ($f['formula'] ?? '2+tier');
                return (int) eval('return ' . str_replace('tier', (int) $tier, $expr) . ';');
            }
            if (($f['tipo'] ?? '') === 'metros') {
                // Coste unitario por metro: el multiplicador lo aplica el validador
                // según los metros narrados (por = metros por PA).
                return null; // depende de la distancia declarada
            }
            if (($f['tipo'] ?? '') === 'consumible') {
                return null; // coste de la ficha del consumible (1–3 PA)
            }
        }
    }
    return is_numeric($coste) ? (int) $coste : null;
}

/**
 * Fórmula de daño (5.10). Cuando un golpe CONECTA:
 *   Cuerpo a cuerpo: FUE×0,2 + DES×0,1 + Nivel²×0,012
 *   A distancia:     DES×0,2 + FUE×0,1 + Nivel²×0,012
 * + bono de calidad del arma (5.8) o bono de desarmado FUE×0,06.
 * Mínimo 1 PV. El multiplicador de técnica lo aplica el llamador.
 */
function ope7_combate_dano($tipo, $fue, $des, $nivel, $bono_arma = 0)
{
    $tipo = $tipo === 'distancia' ? 'distancia' : 'cuerpo_a_cuerpo';
    if ($tipo === 'cuerpo_a_cuerpo') {
        $dano = $fue * 0.2 + $des * 0.1 + $nivel * $nivel * 0.012 + $bono_arma;
    } else {
        $dano = $des * 0.2 + $fue * 0.1 + $nivel * $nivel * 0.012 + $bono_arma;
    }
    return max(1, (int) round($dano));
}

/** Bono de desarmado: FUE×0,06 (se suma cuando peleas sin arma). */
function ope7_combate_bono_desarmado($fue)
{
    return (int) round($fue * 0.06);
}

/**
 * Banda del delta (unificada en las 3 tablas). El delta SIEMPRE se calcula
 * como atacante − defensor con valores efectivos.
 */
function ope7_combate_banda($delta)
{
    $delta = (int) $delta;
    if ($delta >= 20) {
        return array('clave' => 'domina', 'nombre' => 'Dominación', 'atacante' => true);
    }
    if ($delta >= 10) {
        return array('clave' => 'ventaja_clara', 'nombre' => 'Ventaja clara', 'atacante' => true);
    }
    if ($delta >= 5) {
        return array('clave' => 'ventaja_leve', 'nombre' => 'Ventaja leve', 'atacante' => true);
    }
    if ($delta >= -4) {
        return array('clave' => 'paridad', 'nombre' => 'Paridad', 'atacante' => true);
    }
    if ($delta >= -9) {
        return array('clave' => 'desventaja_leve', 'nombre' => 'Desventaja leve', 'atacante' => false);
    }
    if ($delta >= -19) {
        return array('clave' => 'desventaja_clara', 'nombre' => 'Desventaja clara', 'atacante' => false);
    }
    return array('clave' => 'domina_contra', 'nombre' => 'Dominación en contra', 'atacante' => false);
}

/**
 * Tabla 1 — ¿Lo ves venir? (DES/AGI del atacante vs PER+AGI del defensor).
 * Devuelve el veredicto narrativo + las consecuencias mecánicas estructuradas.
 * Claves de mecanica: esquiva (bool), bloqueo (bool), solo_tecnica_defensiva,
 * daño_mult (1/1.25/1.5), choque (bool), contraataque_mult, invalida_hasta_tier.
 */
function ope7_combate_tabla1($delta)
{
    $b = ope7_combate_banda($delta);
    switch ($b['clave']) {
        case 'domina':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'No lo ves venir: el ataque se mueve a una velocidad que tu cuerpo no registra.',
                'mecanica' => array('solo_tecnica_defensiva' => true, 'daño_mult' => 1.5, 'atacante_elige_zona' => true),
            );
        case 'ventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Reacción tardía: lo ves venir, pero tu cuerpo llega tarde.',
                'mecanica' => array('defensas_a_medias' => true, 'sin_esquiva_completa' => true, 'daño_mult' => 1.25),
            );
        case 'ventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Al límite: alcanzas a reaccionar, pero justo — la defensa es un volado.',
                'mecanica' => array('defensa_parcial' => true, 'choque_si_ambos_atacan' => true),
            );
        case 'paridad':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'narrativa',
                'veredicto' => 'Duelo de iguales: ambos se leen; lo que pasa depende de lo roleado.',
                'mecanica' => array('normal' => true, 'choque_por_defecto' => true),
            );
        case 'desventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Lo lee: ves venir el ataque y tienes tiempo de sobra.',
                'mecanica' => array('esquiva_basicos_sin_tecnica' => true, 'tecnicas_requieren_defensiva' => true, 'contraataca_sin_defensa' => true),
            );
        case 'desventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Lo lee y lo castiga: respondes en el mismo movimiento.',
                'mecanica' => array('invalida_hasta_tier' => 2, 'contraataque_mult' => 1.25),
            );
        case 'domina_contra':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Sabe lo que vas a hacer: la respuesta llega antes que el golpe.',
                'mecanica' => array('invalida_hasta_tier' => 3, 'contraataque_mult' => 1.5, 'solo_superiores_o_haki' => true),
            );
    }
    return null;
}

/**
 * Tabla 2 — ¿Lo aguantas? (FUE del atacante vs RES del defensor).
 * Se consulta cuando un golpe CONECTA. Mecánica: guardia rota/abierta,
 * desplazado, derribo, retroceso, plantarse, inamovible, como una roca.
 */
function ope7_combate_tabla2($delta)
{
    $b = ope7_combate_banda($delta);
    switch ($b['clave']) {
        case 'domina':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Lo parte en dos: el impacto es de otra escala.',
                'mecanica' => array('guardia_rota' => true, 'desplazado' => true, 'derribo' => true, 'remate_mult' => 1.25),
            );
        case 'ventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Lo empuja: el golpe conecta y empuja; los pies arrastran.',
                'mecanica' => array('desplazado' => true, 'guardia_abierta' => true),
            );
        case 'ventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Lo tambalea: retrocedes un paso; recuperas el equilibrio con esfuerzo.',
                'mecanica' => array('recuperar_posicion_pa' => 1),
            );
        case 'paridad':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'narrativa',
                'veredicto' => 'Aguanta: el golpe conecta y se siente, pero mantienes tu sitio.',
                'mecanica' => array('sin_penalizacion' => true),
            );
        case 'desventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Se planta: aguanta incluso lo que debería moverte.',
                'mecanica' => array('sin_retroceso_tecnicas_medias' => true),
            );
        case 'desventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Inamovible: tu cuerpo es una muralla; los golpes comunes te resbalan.',
                'mecanica' => array('ignora_retrocesos_derribos' => true, 'solo_avanzadas_o_haki_mueven' => true),
            );
        case 'domina_contra':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Como una roca: los básicos no te inmutan; aguantar no te cuesta nada.',
                'mecanica' => array('básicos_sin_efecto' => true, 'solo_maestra_epica_o_haki_mueven' => true),
            );
    }
    return null;
}

/**
 * Tabla 3 — ¿Te afecta la mente? (CAR/INT del atacante vs VOL del defensor).
 * Se consulta cuando un estado MENTAL intenta aplicarse. Los estados mentales
 * no se esquivan: se resisten con la mente.
 */
function ope7_combate_tabla3($delta)
{
    $b = ope7_combate_banda($delta);
    switch ($b['clave']) {
        case 'domina':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Lo quiebra: tu voluntad se dobla por completo.',
                'mecanica' => array('entrada' => 'plena', 'exige_2_concentraciones' => true),
            );
        case 'ventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Entra de lleno: la resistencia se supera con claridad.',
                'mecanica' => array('entrada' => 'plena'),
            );
        case 'ventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'atacante',
                'veredicto' => 'Entra a medias: el efecto se cuela pero no entero.',
                'mecanica' => array('entrada' => 'media', 'duracion' => '1 turno'),
            );
        case 'paridad':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'narrativa',
                'veredicto' => 'La mente duda: los dos espíritus se miden.',
                'mecanica' => array('entrada' => '1 turno debil', 'o_sacudido_mental' => true),
            );
        case 'desventaja_leve':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Lo resiste: tu voluntad se impone; el efecto rebota.',
                'mecanica' => array('niega' => true, 'insistir_cuesta_doble_pe' => true),
            );
        case 'desventaja_clara':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Lo ignora: ni te enteras; el efecto no te llega.',
                'mecanica' => array('niega' => true, 'atacante_expuesto' => true, 'defensa_atacante_pa' => 1),
            );
        case 'domina_contra':
            return array(
                'banda' => $b['nombre'], 'a_favor' => 'defensor',
                'veredicto' => 'Le da igual: tu espíritu está en otra escala.',
                'mecanica' => array('inmune_mientras_brecha' => true, 'intento_se_vuelve' => array('estado' => 'Sacudido', 'nota' => '1 turno sin técnicas de concentración')),
            );
    }
    return null;
}

/**
 * El choque (resultado por defecto en paridad de Tabla 1 si AMBOS atacan).
 * Comparación de FUE (Tabla 2, ambos como atacantes): quien gana por +10
 * empuja (Desplazado 1 turno); en paridad total quedan trabados.
 */
function ope7_combate_choque($delta_fue)
{
    $b = ope7_combate_banda($delta_fue);
    if ($delta_fue >= 10) {
        return array('resultado' => 'empuje', 'veredicto' => 'Tu empuje gana el choque: el rival retrocede (Desplazado 1 turno).', 'mecanica' => array('desplazado' => true));
    }
    if ($delta_fue >= -9) {
        return array('resultado' => 'trabados', 'veredicto' => 'Choque en paridad total: las armas quedan trabadas (puedes empujar, romper o desengancharte gastando tu acción).', 'mecanica' => array('trabados' => true));
    }
    return array('resultado' => 'retrocede', 'veredicto' => 'El rival te empuja en el choque (Desplazado 1 turno).', 'mecanica' => array('desplazado' => true));
}

/**
 * La cuarta pregunta — umbral del dolor (fijo, daño del golpe vs VOL):
 *   daño > VOL     → Sacudido (interrumpe cargas, instantáneo)
 *   daño > 3×VOL   → Tambaleante (−1 PA por turno)
 *   daño > 5×VOL   → Desorientado (Confundido de pleno)
 */
function ope7_combate_umbral_dolor($dano, $vol)
{
    $dano = (int) $dano;
    $vol = max(1, (int) $vol);
    if ($dano > 5 * $vol) {
        return array('estado' => 'Desorientado', 'veredicto' => 'Estado Confundido de pleno (no encadenas técnicas, esquivar con −AGI).');
    }
    if ($dano > 3 * $vol) {
        return array('estado' => 'Tambaleante', 'veredicto' => '−1 PA por turno (el cuerpo no responde como debe).');
    }
    if ($dano > $vol) {
        return array('estado' => 'Sacudido', 'veredicto' => 'Interrumpe acciones de carga y concentración (instantáneo).');
    }
    return array('estado' => null, 'veredicto' => 'El golpe se siente y se sigue.');
}

/** Umbrales de vida: 80%+ sano · 50-79% herido · 20-49% muy dañado · <20% al límite. */
function ope7_combate_umbrales_vida($pv, $pv_max)
{
    $pv_max = max(1, (int) $pv_max);
    $pct = (int) $pv * 100 / $pv_max;
    if ($pct >= 80) {
        return array('nombre' => 'sano', 'al_limite' => false);
    }
    if ($pct >= 50) {
        return array('nombre' => 'herido', 'al_limite' => false);
    }
    if ($pct >= 20) {
        return array('nombre' => 'muy_dañado', 'al_limite' => false);
    }
    return array('nombre' => 'al_limite', 'al_limite' => true); // técnicas +1 turno de reposo
}

/** Reducción del asediado (1 contra varios): solo al daño que RECIBE el solitario. */
function ope7_combate_reduccion_1vn($n_enemigos)
{
    if ($n_enemigos >= 4) {
        return 30;
    }
    if ($n_enemigos === 3) {
        return 20;
    }
    if ($n_enemigos === 2) {
        return 10;
    }
    return 0;
}

/** Tope de sala: máximo 5 combatientes en cualquier reparto. */
function ope7_combate_sala_tope()
{
    return 5;
}

/** Máximo un ataque a un mismo objetivo por turno (P10). */
function ope7_combate_max_ataques_mismo_objetivo()
{
    return 1;
}

/**
 * Daño residual por turno (estados en % de PV máx, ignoran reducciones planas).
 * Tres fuentes a la vez suman hasta 3% por turno.
 *
 * @param array $estados_fila filas de mybb_ope_estados con su 'efecto' decodificado.
 */
function ope7_combate_dano_residual(array $estados_fila)
{
    $pct = 0;
    foreach ($estados_fila as $e) {
        $fx = is_array($e) && isset($e['efecto']) ? $e['efecto'] : array();
        if (isset($fx['daño_residual']['pct'])) {
            $pct += (int) $fx['daño_residual']['pct'];
        }
    }
    return min(3, $pct);
}

/**
 * Aplica los matices a los valores efectivos ANTES de calcular el delta
 * (regla de oro: el matiz afina, nunca invalida — puede cambiar la banda, no
 * fabricar un resultado que la banda no permita).
 *
 * @param array $matices lista de filas de matices_combate ('efecto' decodificado)
 * @param array $v       valores: per, agi, des, fue, res, vol, car, inte
 * @return array valores ajustados
 */
function ope7_combate_aplicar_matices(array $matices, array $v)
{
    foreach ($matices as $m) {
        $fx = is_array($m) && isset($m['efecto']) ? $m['efecto'] : array();
        foreach ($fx as $k => $val) {
            if ($k === 'condicion' || $k === 'nota' || $k === 'rango' || $k === 'no_tabla_1' || $k === 'por_enemigo_inactivo') {
                continue;
            }
            if (array_key_exists($k, $v) && is_numeric($val)) {
                $v[$k] += (int) $val;
            }
        }
    }
    return $v;
}

/**
 * P4 — ¿una defensa básica puede negar una técnica? Solo técnica defensiva,
 * racial potente o Haki (salvo ventaja clara de velocidad en Tabla 1:
 * defensor −10/−20). Devuelve true si la defensa declarada puede negociar
 * la técnica.
 *
 * @param string $defensa   clave de defensa (aguantar/guardia/parar/desviar/
 *                          esquivar/evadir/escudo/tecnica_defensiva)
 * @param bool   $es_tecnica true si el ataque es una técnica
 * @param array  $tabla1    resultado de ope7_combate_tabla1
 * @param int    $tier_def  tier de la técnica defensiva (si aplica)
 * @param int    $tier_atq  tier del ataque
 */
function ope7_combate_p4($defensa, $es_tecnica, $tabla1, $tier_def = 0, $tier_atq = 0)
{
    if (!$es_tecnica) {
        return true; // los básicos se negocian con cualquier defensa
    }
    if ($defensa === 'tecnica_defensiva') {
        // Anula hasta +1 tier por encima; +2 tiers reduce a la mitad; más apenas;
        // una Épica solo se responde con defensiva superior o Haki.
        $diff = $tier_atq - $tier_def;
        if ($tier_atq >= 5 && $tier_def < 5) {
            return array('ok' => false, 'nota' => 'Una técnica Épica solo se responde con defensiva superior o Haki.');
        }
        if ($diff <= 1) {
            return array('ok' => true, 'anula' => true);
        }
        if ($diff === 2) {
            return array('ok' => true, 'anula' => false, 'reduce_a_mitad' => true);
        }
        return array('ok' => false, 'nota' => 'La diferencia de tiers es demasiado grande.');
    }
    // Defensas básicas contra técnicas: solo la ventaja clara de velocidad.
    $ventaja = isset($tabla1['mecanica']['invalida_hasta_tier'])
        ? (int) $tabla1['mecanica']['invalida_hasta_tier']
        : 0;
    if ($ventaja >= $tier_atq && $tier_atq <= 2) {
        return array('ok' => true, 'anula' => true, 'nota' => 'Ventaja clara de velocidad (Tabla 1 defensor −10/−20).');
    }
    return array('ok' => false, 'nota' => 'P4: una técnica no se niega con una defensa básica — técnica defensiva, racial potente o Haki.');
}

/**
 * Resolución de un intercambio (el corazón del cierre). Entrada:
 *   $ataque = array(
 *       'des'|'agi' (valor efectivo de precisión/velocidad), 'per'|'agi' del
 *       defensor, 'fue', 'res', 'car'|'inte', 'vol',
 *       'tipo' => 'cuerpo_a_cuerpo'|'distancia'|'tecnica'|'estado_mental',
 *       'tier' => int (0 para básicos), 'daño' => int ya calculado,
 *       'estado' => nombre de estado que porta (opcional),
 *       'area' => bool
 *   )
 *   $defensa = array('accion' => clave de defensa, 'tier_def' => int,
 *                    'es_tecnica' => bool, 'valores' => array(per, agi, res, vol))
 * Devuelve el veredicto estructurado del intercambio (para resoluciones_combate).
 */
function ope7_combate_resolver_intercambio(array $ataque, array $defensa, array $matices = array())
{
    $v_atk = $ataque['valores'];
    $v_def = $defensa['valores'];

    // 1) ¿Lo ves venir? (Tabla 1): DES o AGI del atacante vs PER + AGI del defensor.
    $prec_atk = (int) ($v_atk['des'] ?? 0);
    if (($ataque['usa_agi'] ?? false) && isset($v_atk['agi'])) {
        $prec_atk = (int) $v_atk['agi'];
    }
    $reac_def = (int) ($v_def['per'] ?? 0) + (int) ($v_def['agi'] ?? 0);
    if (($defensa['matices'] ?? false)) {
        $ajustados = ope7_combate_aplicar_matices($matices, array('per' => $v_def['per'] ?? 0, 'agi' => $v_def['agi'] ?? 0));
        $reac_def = $ajustados['per'] + $ajustados['agi'];
    }
    $delta1 = $prec_atk - $reac_def;
    $t1 = ope7_combate_tabla1($delta1);
    $es_tecnica = ($ataque['tipo'] === 'tecnica') || ($ataque['tier'] > 0);
    $m1 = $t1['mecanica'];

    // Choque: paridad de Tabla 1 y ambos declararon ataque.
    if (($defensa['tambien_ataca'] ?? false) && ($m1['choque_por_defecto'] ?? false)) {
        $delta_fue = (int) ($v_atk['fue'] ?? 0) - (int) ($v_def['fue'] ?? 0);
        return array(
            'tabla' => 1, 'delta' => $delta1, 'banda' => $t1['banda'],
            'resultado' => 'choque', 'veredicto' => ope7_combate_choque($delta_fue)['veredicto'],
            'matices' => $matices, 'choque' => ope7_combate_choque($delta_fue),
        );
    }

    // P4: técnica atacante → defensa básica no la niega salvo ventaja clara.
    $p4 = ope7_combate_p4($defensa['accion'], $es_tecnica, $t1, (int) ($defensa['tier_def'] ?? 0), (int) ($ataque['tier'] ?? 0));
    $p4_bloquea_basica = false;
    if (is_array($p4) && !$p4['ok']) {
        // El golpe conecta: se registra el veredicto P4 y se sigue con el daño.
        $p4_bloquea_basica = true;
    }
    // Dominación de Tabla 1: ninguna defensa básica negocia el ataque (P4).
    if (($m1['solo_tecnica_defensiva'] ?? false)
        && $defensa['accion'] !== 'tecnica_defensiva' && !($defensa['racial_o_haki'] ?? false)) {
        $p4_bloquea_basica = true;
    }

    // La defensa se evalúa según la banda (esquiva/parcial/a_medias).
    $anula = false;
    $reduce = 0;
    if (!$p4_bloquea_basica
        && (($m1['normal'] ?? false) || ($m1['esquiva_basicos_sin_tecnica'] ?? false) || ($m1['invalida_hasta_tier'] ?? 0))) {
        if (is_array($p4) && ($p4['anula'] ?? false)) {
            $anula = true;
        } elseif ($defensa['accion'] !== 'aguantar' && $defensa['accion'] !== '') {
            if (($m1['defensa_parcial'] ?? false) || ($m1['defensas_a_medias'] ?? false)) {
                $reduce = 50; // defensa parcial: reduce, no anula
            } else {
                $anula = true;
            }
        }
    } elseif (!$p4_bloquea_basica && ($m1['defensa_parcial'] ?? false)) {
        $reduce = 50;
    }

    // Aguantar (0 PA): sin Tabla 1 — el golpe conecta siempre (daño completo − reducciones).
    if (!$p4_bloquea_basica && ($defensa['accion'] === 'aguantar' || $defensa['accion'] === '')) {
        $anula = false;
        $reduce = 0;
    }

    // ¿Conecta? Si la defensa anuló, no hay daño (ni Tabla 2 ni umbral).
    if ($anula) {
        return array(
            'tabla' => 1, 'delta' => $delta1, 'banda' => $t1['banda'],
            'resultado' => 'defendido', 'a_favor' => 'defensor',
            'veredicto' => $t1['veredicto'] . ' La defensa (' . $defensa['accion'] . ') anula el ataque.',
            'conecta' => false, 'daño' => 0, 'matices' => $matices,
        );
    }

    // 2) ¿Lo aguantas? (Tabla 2) cuando conecta.
    $delta2 = (int) ($v_atk['fue'] ?? 0) - (int) ($v_def['res'] ?? 0);
    $t2 = ope7_combate_tabla2($delta2);
    $nota_p4 = $p4_bloquea_basica && is_array($p4) ? ' ' . $p4['nota'] : ($p4_bloquea_basica ? ' Solo técnica defensiva, racial potente o Haki (P4).' : '');

    // 3) Daño final: base × mult de Tabla 1 × (reducción de defensa / estados).
    $dano = (int) round((int) ($ataque['daño'] ?? 0) * (float) ($m1['daño_mult'] ?? 1.0));
    if ($reduce > 0) {
        $dano = (int) round($dano * (100 - $reduce) / 100);
    }
    if (($defensa['reduccion_estados'] ?? 0) > 0) {
        $dano = (int) round($dano * (100 - (int) $defensa['reduccion_estados']) / 100);
    }
    // Reducción 1vN (solo al daño que recibe el solitario).
    if (($defensa['reduccion_1vn'] ?? 0) > 0) {
        $dano = (int) round($dano * (100 - (int) $defensa['reduccion_1vn']) / 100);
    }
    $dano = max(0, $dano);

    // 4) Umbral del dolor (pregunta fija): daño real vs VOL.
    $umbral = ope7_combate_umbral_dolor($dano, (int) ($v_def['vol'] ?? 1));

    // Estado mental del ataque → Tabla 3 (CAR/INT vs VOL).
    $estado = null;
    if (($ataque['estado'] ?? '') !== '') {
        $delta3 = (int) ($v_atk['car'] ?? $v_atk['inte'] ?? 0) - (int) ($v_def['vol'] ?? 0);
        $t3 = ope7_combate_tabla3($delta3);
        $estado = array('nombre' => $ataque['estado'], 'tabla3' => $t3, 'delta' => $delta3);
    }

    return array(
        'tabla' => 2, 'delta1' => $delta1, 'delta2' => $delta2, 'banda' => $t1['banda'],
        'resultado' => 'conecta', 'a_favor' => 'atacante',
        'veredicto' => $t1['veredicto'] . $nota_p4 . ' ' . $t2['veredicto'] . ' Daño: ' . $dano . ' PV (' . $umbral['veredicto'] . ')',
        'conecta' => true, 'p4_bloqueada' => $p4_bloquea_basica, 'daño' => $dano, 't2' => $t2, 'umbral' => $umbral, 'estado' => $estado,
        'matices' => $matices,
    );
}

/**
 * Resolución de cierre de un tema de combate: toma los turnos declarados
 * (turnos_combate), empareja ataques vs defensas en orden de posteo y genera
 * los veredictos por intercambio. PURO: recibe los arrays, devuelve el log.
 *
 * @param array $turnos  lista de turnos: cada uno con personaje_id, turno,
 *                       pa_total, pa_gastado, acciones (JSON decodificado).
 * @return array{intercambios:array, excesos:array, resumen:array}
 */
function ope7_combate_resolver_tema(array $turnos)
{
    $intercambios = array();
    $excesos = array();

    // 1) Excesos de presupuesto (aviso para el staff, no bloqueo).
    foreach ($turnos as $t) {
        if ((int) $t['pa_gastado'] > (int) $t['pa_total']) {
            $excesos[] = array(
                'personaje_id' => (int) $t['personaje_id'], 'turno' => (int) $t['turno'],
                'pa_total' => (int) $t['pa_total'], 'pa_gastado' => (int) $t['pa_gastado'],
                'nota' => 'Presupuesto excedido: el sistema marca el post para revisión; el staff confirma al cierre.',
            );
        }
    }

    // 2) Intercambios: las acciones que exigen reacción (ataques) se resuelven
    //    antes que las que no la exigen, por pares en orden de posteo.
    $ataques = array();
    foreach ($turnos as $t) {
        $acciones = is_array($t['acciones']) ? $t['acciones'] : (array) json_decode((string) ($t['acciones'] ?? ''), true);
        foreach ($acciones as $a) {
            if (($a['tipo'] ?? '') === 'ataque') {
                $ataques[] = array('turno' => $t, 'ataque' => $a);
            }
        }
    }
    foreach ($ataques as $i => $par) {
        $atk = $par['ataque'];
        $def = $atk['defensa'] ?? array('accion' => '', 'valores' => $atk['defensor_valores'] ?? array());
        $intercambios[] = array(
            'turno' => (int) $par['turno']['turno'],
            'atacante_id' => (int) $par['turno']['personaje_id'],
            'defensor_id' => (int) ($atk['objetivo_id'] ?? 0),
            'resolucion' => ope7_combate_resolver_intercambio($atk, $def, $atk['matices'] ?? array()),
        );
    }

    return array('intercambios' => $intercambios, 'excesos' => $excesos);
}

/** ¿El primer post de un tema de combate? (P9: introductorio, sin acciones ni daño). */
function ope7_combate_es_primer_post($n_posts_previos)
{
    return (int) $n_posts_previos <= 1;
}
