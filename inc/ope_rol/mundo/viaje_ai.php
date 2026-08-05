<?php
/**
 * One Piece: Eternal · Generador de introducción narrativa de viajes (NVIDIA NIM).
 *
 * Best effort: si la API no responde, el viaje se crea igual (sin introducción).
 * Los modelos se prueban en cadena ('perfiles', cada uno con su propia key):
 * si uno devuelve 429 (rate limit) o falla, se prueba el siguiente de la lista.
 * El resultado se guarda en rol_viajes (introduccion_api).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Lee la config del módulo (perfiles desde env o archivo local no versionado). */
function ope_viaje_ai_config()
{
    $cfg = array();
    $env = getenv('NVIDIA_API_KEY');
    if ($env !== false && $env !== '') {
        $cfg['api_key'] = $env;
    }
    $file = __DIR__ . '/../config/viaje_ai.php';
    if (is_file($file)) {
        $f = include $file;
        if (is_array($f)) {
            $cfg = array_merge($cfg, $f);
        }
    }
    return $cfg;
}

/** Devuelve la lista de perfiles (key + modelo) a probar, en orden de prioridad. */
function ope_viaje_ai_perfiles(array $cfg)
{
    if (!empty($cfg['perfiles']) && is_array($cfg['perfiles'])) {
        $out = array();
        foreach ($cfg['perfiles'] as $p) {
            if (is_array($p) && !empty($p['key']) && !empty($p['modelo'])) {
                $out[] = $p;
            }
        }
        if ($out) {
            return $out;
        }
    }
    // Legacy: api_key única + lista de modelos
    $key = (string) ($cfg['api_key'] ?? '');
    $out = array();
    foreach ((array) ($cfg['modelos'] ?? array()) as $m) {
        $out[] = array('key' => $key, 'modelo' => (string) $m);
    }
    return $out;
}

/** True si hay al menos un perfil con key válida. */
function ope_viaje_ai_activo()
{
    $cfg = ope_viaje_ai_config();
    foreach (ope_viaje_ai_perfiles($cfg) as $p) {
        $k = (string) ($p['key'] ?? '');
        if ($k !== '' && $k !== 'TU_API_KEY_NVIDIA_AQUI') {
            return true;
        }
    }
    return false;
}

/** Llama a un modelo NVIDIA (OpenAI-compatible) en streaming y devuelve SOLO el content. */
function ope_viaje_ai_llamar(array $cfg, array $perfil, array $messages)
{
    $endpoint = (string) ($cfg['endpoint'] ?? 'https://integrate.api.nvidia.com/v1/chat/completions');
    $payload  = array(
        'model'       => (string) $perfil['modelo'],
        'messages'    => $messages,
        'temperature' => (float) ($cfg['temperature'] ?? 0.8),
        'max_tokens'  => (int) ($cfg['max_tokens'] ?? 700),
        'top_p'       => 0.95,
        'stream'      => true,
    );
    // Modelos Nemotron-3: si el perfil pide 'no_thinking' se fuerza enable_thinking=false
    // (si no, estos modelos razonan inline y meten el razonamiento en el content).
    if (!empty($perfil['no_thinking'])) {
        $payload['chat_template_kwargs'] = array('enable_thinking' => false);
    } elseif (!empty($perfil['enable_thinking'])) {
        $payload['chat_template_kwargs'] = array('enable_thinking' => true);
        $payload['reasoning_budget']      = (int) ($perfil['reasoning_budget'] ?? 2048);
    }
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = array(
        'Authorization: Bearer ' . $perfil['key'],
        'Content-Type: application/json',
    );
    $timeout = (int) ($cfg['timeout'] ?? 45);
    $ssl_ok  = !empty($cfg['ssl_verify']);

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => $ssl_ok,
            CURLOPT_SSL_VERIFYHOST => $ssl_ok ? 2 : 0,
        ));
        $resp  = curl_exec($ch);
        $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr  = (string) curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            return array('http' => $http, 'error' => $cerr !== '' ? $cerr : 'Sin respuesta de la API.', 'contenido' => null);
        }
        if ($http >= 200 && $http < 300) {
            $contenido = ope_viaje_ai_parse_sse_content($resp);
            return array('http' => $http, 'error' => '', 'contenido' => $contenido);
        }
        $dec = json_decode($resp, true);
        $msg = is_array($dec) ? (string) ($dec['error']['message'] ?? $resp) : $resp;
        return array('http' => $http, 'error' => $msg, 'contenido' => null);
    }

    // Fallback sin curl: llamada no-stream
    $payload['stream'] = false;
    $body2 = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ctx = stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers) . "\r\n",
            'content' => $body2,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ),
        'ssl'  => array('verify_peer' => $ssl_ok, 'verify_peer_name' => $ssl_ok),
    ));
    $resp = @file_get_contents($endpoint, false, $ctx);
    $http = 0;
    if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
        $http = (int) $m[1];
    }
    if ($resp === false) {
        return array('http' => $http, 'error' => 'Sin respuesta de la API.', 'contenido' => null);
    }
    $dec = json_decode($resp, true);
    if ($http >= 200 && $http < 300 && is_array($dec)) {
        $txt = trim((string) ($dec['choices'][0]['message']['content'] ?? ''));
        return array('http' => $http, 'error' => '', 'contenido' => $txt);
    }
    $msg = is_array($dec) ? (string) ($dec['error']['message'] ?? $resp) : $resp;
    return array('http' => $http, 'error' => $msg, 'contenido' => null);
}

/** Extrae solo el content de un body SSE (ignora reasoning_content y heartbeats). */
function ope_viaje_ai_parse_sse_content(string $body)
{
    $out = '';
    $lines = preg_split('/\R/', $body);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'data:') !== 0) {
            continue;
        }
        $data = trim(substr($line, 5));
        if ($data === '' || $data === '[DONE]') {
            continue;
        }
        $d = json_decode($data, true);
        if (!is_array($d) || !isset($d['choices'][0])) {
            continue;
        }
        $delta = $d['choices'][0]['delta'] ?? null;
        if (is_array($delta) && isset($delta['content']) && $delta['content'] !== null) {
            $out .= $delta['content'];
        }
    }
    return trim($out);
}

/** Convierte el resultado del oráculo en un resumen legible para la IA. */
function ope_viaje_ai_resumen_oraculo(array $oraculo)
{
    $lineas = array();
    $mapa   = array('clima', 'encuentro', 'peligro', 'hallazgo', 'misterio', 'bonanza');
    foreach ($oraculo['tramos'] ?? array() as $tr) {
        $cartas = is_array($tr['cartas'] ?? null) ? $tr['cartas'] : array();
        $bits   = array();
        foreach ($mapa as $k) {
            if (!empty($cartas[$k]['nombre'])) {
                $bits[] = $k . ': ' . (string) $cartas[$k]['nombre'];
            }
        }
        // Cartas extra
        foreach ($cartas as $k => $c) {
            if (!is_array($c)) continue;
            if (strpos($k, 'extra_') === 0 && !empty($c['nombre'])) {
                $bits[] = ($c['mesa'] ?? 'extra') . ': ' . (string) $c['nombre'];
            }
        }
        if ($bits) {
            $lineas[] = 'Viaje: ' . implode(' | ', $bits);
        }
    }
    return implode("\n", $lineas);
}

/**
 * Genera la introducción narrativa de una travesía.
 *
 * @param array $origen   Isla de origen (nombre, macro)
 * @param array $destino  Isla de destino (nombre, macro)
 * @param array $oraculo  Resultado de ope_oraculo_v2_viaje()
 * @param array $extra    nivel_peligro, macro, barco, tripulacion (opcional)
 * @return array ['ok'=>bool, 'texto'=>string, 'modelo'=>string, 'msg'=>string]
 */
function ope_viaje_ai_generar(array $origen, array $destino, array $oraculo, array $extra = array())
{
    $cfg = ope_viaje_ai_config();
    $perfiles = ope_viaje_ai_perfiles($cfg);
    if (!$perfiles) {
        return array('ok' => false, 'texto' => '', 'modelo' => '', 'msg' => 'API AI no configurada.');
    }

    $max     = max(1, (int) ($cfg['max_intentos'] ?? count($perfiles)));
    $perfiles = array_slice($perfiles, 0, $max);

    $origen_nom  = (string) ($origen['nombre'] ?? 'un puerto lejano');
    $destino_nom = (string) ($destino['nombre'] ?? 'horizonte desconocido');
    $nivel       = str_replace('_', ' ', (string) ($extra['nivel_peligro'] ?? 'bajo'));
    $macro       = trim((string) ($extra['macro'] ?? ''));
    $barco       = trim((string) ($extra['barco'] ?? ''));
    $trip        = trim((string) ($extra['tripulacion'] ?? ''));
    $resumen     = ope_viaje_ai_resumen_oraculo($oraculo);
    if ($resumen === '') {
        $resumen = 'El oráculo no arrojó cartas destacables para este tramo.';
    }

    $system = 'Eres "El Narrador", el cronista del foro de rol por post One Piece: Eternal. '
        . 'Escribes las introducciones de travesía con la voz de un cronista del Grand Line: '
        . 'salitre, urgencia, camaradería y la promesa del mar abierto. Debe sentirse One Piece, '
        . 'no otro juego con piel de piratas. '
        . 'REGLAS OBLIGATORIAS: escribe SIEMPRE en español, en prosa literaria de 2 a 4 párrafos '
        . 'y entre 120 y 220 palabras. Sin listas, sin viñetas, sin markdown, sin encabezados. '
        . 'Sin emojis ni emoticonos. No menciones que eres una IA, ni "Narrador", ni reglas ni mecánicas. '
        . 'No uses personajes canónicos de One Piece; el elenco es 100% original del foro. '
        . 'Usa solo los nombres de islas, barco y tripulación que se te den. '
        . 'Siembra el texto con las pistas del oráculo (clima, encuentros, peligros, hallazgos, misterios, bonanzas) '
        . 'como anticipación de lo que aguarda, sin enumerarlas como listas. '
        . 'Tono: libertad y ambición. Viento, olas, horizonte y las decisiones de la tripulación. '
        . 'MODO DE NARRAR ABIERTO (crítico): la narración describe el ESCENARIO y las CIRCUNSTANCIAS, '
        . 'pero NUNCA da por hecho qué hacen, deciden o sienten los personajes de los jugadores. '
        . 'Prohibido decir que un personaje actúa ("Kailo ajusta el timón", "Mira pone la mano en el arma", '
        . '"la tripulación decide..."): eso lo narra cada jugador. Los personajes pueden estar presentes '
        . 'a bordo, pero su respuesta queda SIEMPRE abierta, como un escenario que espera la decisión de su dueño. '
        . 'Cierra cada párrafo dejando un gancho abierto: una sensación, una amenaza, una pregunta o una elección '
        . 'pendiente que invite a los jugadores a narrar cómo responden. '
        . 'Entrega SOLO el texto narrativo final, sin preámbulo ni notas.';

    $user = 'Travesía del foro One Piece: Eternal.' . "\n"
        . 'Origen: ' . $origen_nom . "\n"
        . 'Destino: ' . $destino_nom . "\n"
        . ($macro !== '' ? 'Zona del mar: ' . $macro . "\n" : '')
        . 'Nivel de peligro: ' . $nivel . "\n"
        . ($barco !== '' ? 'Barco: ' . $barco . "\n" : '')
        . ($trip !== '' ? 'Tripulación: ' . $trip . "\n" : '')
        . 'Resultado del oráculo de la travesía:' . "\n" . $resumen . "\n\n"
        . 'Escribe la introducción narrativa que abre la travesía, integrando esas pistas como anticipación del camino.';

    $messages = array(
        array('role' => 'system', 'content' => $system),
        array('role' => 'user',   'content' => $user),
    );

    $ultimo_error = '';
    foreach ($perfiles as $perfil) {
        $r = ope_viaje_ai_llamar($cfg, $perfil, $messages);
        if ($r['http'] >= 200 && $r['http'] < 300 && $r['contenido'] !== null && $r['contenido'] !== '') {
            return array(
                'ok'    => true,
                'texto' => $r['contenido'],
                'modelo' => (string) $perfil['modelo'],
                'msg'   => '',
            );
        }
        $ultimo_error = $r['error'] !== '' ? $r['error'] : ('HTTP ' . $r['http']);
    }

    return array('ok' => false, 'texto' => '', 'modelo' => '', 'msg' => $ultimo_error);
}