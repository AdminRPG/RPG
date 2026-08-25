<?php
/**
 * One Piece: Eternal · Generador de introduccion narrativa de misiones (NVIDIA NIM).
 *
 * Mismos perfiles y endpoint que viaje_ai.php.
 * Prompt especifico: la DESCRIPCION de la mision y QUIEN la toma son los motores narrativos.
 * El oraculo y los datos de isla/facciones anaden sabor.
 *
 * Soporte batch: una llamada IA para N misiones.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_mision_ai_activo()
{
    return function_exists('ope_viaje_ai_activo') && ope_viaje_ai_activo();
}

/**
 * Genera introducciones narrativas para VARIAS misiones en batch (UNA llamada API).
 */
function ope_mision_ai_generar_batch(array $misiones)
{
    if (!function_exists('ope_viaje_ai_activo') || !ope_viaje_ai_activo()) {
        return array('ok' => false, 'textos' => array(), 'modelo' => '', 'msg' => 'API AI no configurada.');
    }

    $cfg      = ope_viaje_ai_config();
    $perfiles = ope_viaje_ai_perfiles($cfg);
    if (!$perfiles) {
        return array('ok' => false, 'textos' => array(), 'modelo' => '', 'msg' => 'API AI no configurada.');
    }

    $max      = max(1, (int) ($cfg['max_intentos'] ?? count($perfiles)));
    $perfiles = array_slice($perfiles, 0, $max);

    $n = count($misiones);
    if ($n < 1) {
        return array('ok' => false, 'textos' => array(), 'modelo' => '', 'msg' => 'Sin misiones que generar.');
    }

    $system = 'Eres la voz del Tablon de Misiones de One Piece: 7 Seas, un foro de rol por post en el universo One Piece. '
        . 'Tu trabajo es escribir el ARRANQUE narrativo de una mision: la escena que abre el hilo. '
        . 'Debe sentirse One Piece: peligro, mar, islas unicas, facciones con agenda propia, y la promesa del botin. '
        . 'CADA MISION es un micro-mundo. Su DESCRIPCION (gancho, conflicto, NPCs) y la identidad de QUIEN LA TOMA '
        . 'son el CENTRO de la narracion. Las cartas del oraculo son sabor ambiental, no el protagonista. '
        . 'REGLAS OBLIGATORIAS: '
        . '1) Espanol siempre. '
        . '2) 1 a 2 parrafos, entre 100 y 180 palabras por mision. '
        . '3) Sin markdown, sin listas, sin vinetas, sin emojis. '
        . '4) No menciones que eres una IA, ni "Narrador", ni reglas, ni mecanicas. '
        . '5) NUNCA uses personajes canonicos de One Piece; todo el elenco es original del foro. '
        . '6) Usa SOLO los nombres que se te dan (isla, facciones, PJ). No inventes nombres nuevos. '
        . 'MODO NARRATIVO: '
        . '- La DESCRIPCION de la mision es el motor. Si habla de un almacen sellado, empieza EN el almacen. '
        . '  Si habla de una nina Gyojin, empieza CON la nina o cerca de ella. '
        . '- El PJ que toma la mision entra en escena como presencia (no como accion): '
        . '  "Frente al muelle, la silueta de Gael Thorne se recorta contra la niebla..." NO "Gael avanza y...". '
        . '- Las cartas del oraculo (entorno, encuentro, aliado, complicacion, revelacion) se INTEGRAN '
        . '  como detalle organico. "La niebla costera..." (entorno), "un chasquido entre las cajas..." (encuentro). '
        . '  No las enumeres, no las nombres. Son textura. '
        . '- Cada parrafo cierra con un gancho abierto: un sonido, una sombra, una pregunta sin responder. '
        . '  El jugador debe sentir que el siguiente movimiento es suyo. '
        . 'SEPARADOR: entrega cada introduccion separada por ===MISION=== en linea propia. '
        . 'Sin texto antes ni despues.';

    $user = "{$n} misiones del Tablon de Misiones de One Piece: 7 Seas.\n\n";
    foreach ($misiones as $i => $m) {
        $num     = $i + 1;
        $titulo  = (string)($m['titulo'] ?? "Mision {$num}");
        $resumen = (string)($m['resumen'] ?? '');
        $desc    = trim((string)($m['descripcion_larga'] ?? ''));
        $zona    = (string)($m['zona_nombre'] ?? $m['zona_slug'] ?? 'isla desconocida');
        $fac     = (string)($m['facciones'] ?? '');
        $rec     = (string)($m['recompensa'] ?? '');
        $rango   = (string)($m['rango'] ?? 'D');
        $pelig   = (int)($m['peligrosidad'] ?? 1);
        $pj      = (string)($m['pj_nombre'] ?? 'aventurero');
        $orac    = (string)($m['resumen_oraculo'] ?? '');

        $user .= "===MISION {$num}===\n";
        $user .= "TITULO: {$titulo}\n";
        $user .= "GANCHO: {$resumen}\n";
        if ($desc !== '') {
            $user .= "DETALLES DE LA MISION: {$desc}\n";
        }
        $user .= "ISLA: {$zona}\n";
        if ($fac !== '') $user .= "FACCIONES: {$fac}\n";
        $user .= "RANGO: {$rango} | PELIGRO: {$pelig}/5\n";
        if ($rec !== '') $user .= "RECOMPENSA: {$rec}\n";
        $user .= "AVENTURERO QUE LA TOMA: {$pj}\n";
        if ($orac !== '') $user .= "AMBIENTE (oraculo): {$orac}\n";
        $user .= "\n";
    }

    $user .= "ESCRIBE el arranque narrativo de cada mision. La DESCRIPCION y el AVENTURERO son el centro. "
           . "Las cartas del oraculo son textura ambiental. "
           . "Separa con ===MISION=== en linea propia. Sin texto adicional.";

    $messages = array(
        array('role' => 'system', 'content' => $system),
        array('role' => 'user',   'content' => $user),
    );

    $ultimo_error = '';
    foreach ($perfiles as $perfil) {
        $r = ope_viaje_ai_llamar($cfg, $perfil, $messages);
        if ($r['http'] >= 200 && $r['http'] < 300 && $r['contenido'] !== null && $r['contenido'] !== '') {
            $textos = ope_mision_ai_parse_batch($r['contenido'], $n);
            return array(
                'ok'     => count($textos) === $n,
                'textos' => $textos,
                'modelo' => (string)$perfil['modelo'],
                'msg'    => '',
            );
        }
        $ultimo_error = $r['error'] !== '' ? $r['error'] : ('HTTP ' . $r['http']);
    }

    return array('ok' => false, 'textos' => array(), 'modelo' => '', 'msg' => $ultimo_error);
}

function ope_mision_ai_parse_batch(string $respuesta, int $esperadas)
{
    $respuesta = trim($respuesta);
    $respuesta = preg_replace('/^```[a-z]*\s*/i', '', $respuesta);
    $respuesta = preg_replace('/\s*```$/i', '', $respuesta);

    $bloques = preg_split('/\s*===MISION===\s*/', $respuesta);
    $bloques = array_map('trim', $bloques);
    $bloques = array_filter($bloques, function($b) { return $b !== ''; });
    $bloques = array_values($bloques);

    if (count($bloques) > $esperadas) {
        $bloques = array_slice($bloques, 0, $esperadas);
    }

    return $bloques;
}

function ope_mision_ai_generar(array $mision_data)
{
    $res = ope_mision_ai_generar_batch(array($mision_data));
    if (!$res['ok'] || empty($res['textos'])) {
        return array('ok' => false, 'texto' => '', 'modelo' => $res['modelo'], 'msg' => $res['msg'] ?: 'Sin respuesta de la IA.');
    }
    return array('ok' => true, 'texto' => $res['textos'][0], 'modelo' => $res['modelo'], 'msg' => '');
}
