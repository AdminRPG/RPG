<?php
/**
 * One Piece: Eternal · Analisis IA de cierres de viaje.
 * -----------------------------------------------------
 * Usa la misma infraestructura NVIDIA NIM que viaje_ai.php para analizar
 * el contenido del hilo de viaje contra los resultados del oraculo.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Analiza un hilo de viaje con IA para verificar el roleo.
 * Devuelve JSON con el analisis completo.
 */
function ope_viaje_ia_analizar(array $viaje, array $posts)
{
    // Reusar la config de viaje_ai
    if (!function_exists('ope_viaje_ai_config')) {
        return array('ok' => false, 'error' => 'Modulo AI no disponible.', 'analisis' => null);
    }

    $cfg = ope_viaje_ai_config();
    if (!function_exists('ope_viaje_ai_activo') || !ope_viaje_ai_activo()) {
        return array('ok' => false, 'error' => 'API no configurada. Configura viaje_ai.php.', 'analisis' => null);
    }

    // Construir el prompt
    $origen       = (string) ($viaje['origen_nombre'] ?? '');
    $destino      = (string) ($viaje['destino_nombre'] ?? '');
    $peligro      = (string) ($viaje['nivel_peligro'] ?? 'bajo');
    $es_temeraria = !empty($viaje['es_temeraria']);
    $barco        = (string) ($viaje['barco_nombre'] ?? 'Desconocido');
    $posts_min    = (int) ($viaje['posts_min'] ?? 3);
    $trip_json    = (string) ($viaje['tripulantes_json'] ?? '[]');
    $trip         = is_array($trip_json) ? $trip_json : json_decode($trip_json, true);
    if (!is_array($trip)) $trip = array();

    // Oráculo
    $oraculo = json_decode((string) ($viaje['resultado_json'] ?? '{}'), true);
    $oraculo_resumen = '';
    if (is_array($oraculo) && !empty($oraculo['tramos'])) {
        foreach ($oraculo['tramos'] as $tr) {
            $cartas = $tr['cartas'] ?? array();
            foreach ($cartas as $k => $c) {
                if (!is_array($c) || empty($c['nombre'])) continue;
                $oraculo_resumen .= "$k: {$c['nombre']} (efecto: {$c['efecto']})\n";
            }
        }
    }

    // Tripulación
    $trip_nombres = array();
    $oficios = array();
    foreach ($trip as $tm) {
        $trip_nombres[] = (string) ($tm['nombre'] ?? '');
        $ofi = (string) ($tm['oficio'] ?? 'tripulante');
        if ($ofi !== 'tripulante' && $ofi !== '') $oficios[] = (string) ($tm['nombre'] ?? '') . " ($ofi)";
    }
    $tiene_cocinero   = in_array('cocinero', array_map(function($t){return strtolower((string)($t['oficio']??''));}, $trip));
    $tiene_carpintero = in_array('carpintero', array_map(function($t){return strtolower((string)($t['oficio']??''));}, $trip));

    // Posts del hilo
    $posts_txt = '';
    $total_posts = count($posts);
    foreach ($posts as $i => $p) {
        $posts_txt .= "--- Post #" . ($i+1) . " (" . $p['autor'] . ") ---\n" . $p['contenido'] . "\n\n";
    }

    $prompt = "Eres el Narrador de One Piece: Eternal, un foro de rol PBP. Debes analizar un hilo de viaje marítimo y emitir un veredicto estructurado en JSON.\n\n"
        . "=== DATOS DEL VIAJE ===\n"
        . "Origen: $origen\n"
        . "Destino: $destino\n"
        . "Barco: $barco\n"
        . "Peligro de ruta: $peligro\n"
        . "Ruta temeraria: " . ($es_temeraria ? 'Si' : 'No') . "\n"
        . "Posts minimos sugeridos: $posts_min\n"
        . "Cocinero a bordo: " . ($tiene_cocinero ? 'Si' : 'No') . "\n"
        . "Carpintero a bordo: " . ($tiene_carpintero ? 'Si' : 'No') . "\n"
        . "Tripulacion: " . implode(', ', $trip_nombres) . "\n"
        . "Oficios: " . (count($oficios) ? implode(', ', $oficios) : 'ninguno') . "\n\n"
        . "=== RESULTADOS DEL ORACULO (eventos que DEBERIAN rolearse) ===\n"
        . ($oraculo_resumen ?: 'Sin datos de oraculo.') . "\n\n"
        . "=== POSTS DEL HILO ($total_posts posts) ===\n"
        . $posts_txt . "\n"
        . "=== INSTRUCCIONES ===\n"
        . "Analiza los posts y responde UNICAMENTE con un objeto JSON (sin markdown, sin comillas triples) con esta estructura:\n"
        . "{\n"
        . '  "aprobado": true/false,' . "\n"
        . '  "posts_reales": <numero de posts roleados por jugadores (sin contar los del Narrador/Oráculo)>,' . "\n"
        . '  "posts_minimos_cumplidos": true/false,' . "\n"
        . '  "participacion_tripulantes": ["nombre1","nombre2"],' . "\n"
        . '  "oraculo_roleado": [{"evento":"nombre del evento","roleado":true/false,"calidad":"buena/regular/mala/ausente"}],' . "\n"
        . '  "desgaste_casco_estimado": <porcentaje 0-100, basado en eventos del oraculo (tormentas, combates, etc.) y si la ruta es temeraria>,' . "\n"
        . '  "consumo_despensa_estimado": <porcentaje 0-100, basado en la longitud del viaje>,' . "\n"
        . '  "resumen": "breve resumen del roleo en 2-3 frases",' . "\n"
        . '  "problemas": ["lista de problemas detectados, vacia si todo ok"],' . "\n"
        . '  "sugerencias": ["sugerencias para el staff"]' . "\n"
        . "}\n\n"
        . "REGLAS:\n"
        . "- Si el numero de posts reales no alcanza los minimos, 'aprobado' debe ser false.\n"
        . "- Si los eventos principales del oraculo (clima, encuentro, peligro) no fueron roleados, 'aprobado' debe ser false.\n"
        . "- El desgaste de casco debe considerar si la ruta es temeraria (+5% extra) y si hay carpintero (mitad).\n"
        . "- El consumo de despensa debe considerar si hay cocinero (mitad).\n"
        . "- Se objetivo pero constructivo. Los 'problemas' y 'sugerencias' deben ser utiles.\n"
        . "- NO incluyas markdown, solo el JSON puro.";

    // Llamada a la IA (reusando la infraestructura de viaje_ai)
    if (!function_exists('ope_viaje_ai_perfiles')) {
        return array('ok' => false, 'error' => 'Funcion de perfiles AI no disponible.', 'analisis' => null);
    }

    $perfiles = ope_viaje_ai_perfiles($cfg);
    if (empty($perfiles)) {
        return array('ok' => false, 'error' => 'No hay perfiles AI configurados.', 'analisis' => null);
    }

    $cfg_analisis = array_merge($cfg, array(
        'temperature' => 0.3,
        'max_tokens'  => 2000,
    ));

    $messages = array(
        array('role' => 'system', 'content' => 'Eres el Narrador de One Piece: Eternal. Responde solo con JSON valido, sin markdown.'),
        array('role' => 'user', 'content' => $prompt),
    );

    $mejor_error = '';
    foreach ($perfiles as $perfil) {
        $k = (string) ($perfil['key'] ?? '');
        if ($k === '' || $k === 'TU_API_KEY_NVIDIA_AQUI' || strpos($k, 'TU_KEY_') === 0) {
            continue;
        }
        $res = ope_viaje_ai_llamar($cfg_analisis, $perfil, $messages);
        if (!empty($res['contenido'])) {
            $txt = trim($res['contenido']);
            // Limpiar posibles markdown code blocks
            $txt = preg_replace('/^```(?:json)?\s*/i', '', $txt);
            $txt = preg_replace('/\s*```$/', '', $txt);
            $analisis = json_decode($txt, true);
            if (is_array($analisis) && isset($analisis['aprobado'])) {
                return array(
                    'ok' => true,
                    'error' => '',
                    'modelo' => (string) $perfil['modelo'],
                    'analisis' => $analisis,
                );
            }
            // Guardar el contenido por si es un JSON parcialmente valido
            $mejor_error = 'JSON invalido en respuesta: ' . substr($txt, 0, 200);
        } else {
            $mejor_error = (string) ($res['error'] ?? 'Error desconocido');
        }
    }

    return array('ok' => false, 'error' => $mejor_error ?: 'Ningun modelo respondio.', 'analisis' => null);
}
