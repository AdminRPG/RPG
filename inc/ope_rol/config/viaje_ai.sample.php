<?php
/**
 * One Piece: Eternal · Config (plantilla versionable) del generador de introducciones de viaje.
 * Copia a viaje_ai.php y rellena cada 'perfiles[].key' con tu key NVIDIA de cada modelo.
 */
return array(
    'perfiles'      => array(
        // Nemotron-3 razonan inline si no se fuerza 'no_thinking' → el razonamiento
        // se cuela en el content. Usa 'no_thinking' => true para narrativa limpia.
        array('key' => 'TU_KEY_NEMOTRON3_ULTRA', 'modelo' => 'nvidia/nemotron-3-ultra-550b-a55b', 'no_thinking' => true),
        array('key' => 'TU_KEY_GENERICA',         'modelo' => 'nvidia/llama-3.3-nemotron-super-49b-v1'),
        array('key' => 'TU_KEY_GENERICA',         'modelo' => 'meta/llama-3.3-70b-instruct'),
        array('key' => 'TU_KEY_MINIMAX',          'modelo' => 'minimaxai/minimax-m3'),
        // Opcional: nvidia/nemotron-3-super-120b-a12b vierte razonamiento al content en no-stream.
        // array('key' => 'TU_KEY_NEMOTRON3_SUPER', 'modelo' => 'nvidia/nemotron-3-super-120b-a12b'),
    ),
    'max_intentos'  => 4,
    'timeout'       => 45,
    'ssl_verify'    => true,
    'temperature'   => 0.8,
    'max_tokens'    => 700,
);