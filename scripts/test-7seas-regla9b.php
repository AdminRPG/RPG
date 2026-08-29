<?php
/**
 * One Piece: 7 Seas · Test de la regla 9b (D6.1) — IA invisible al jugador
 * -----------------------------------------------------------------------
 * Blindaje regresivo: ningún mensaje/título/texto visible al jugador puede
 * mencionar IA, skill o prompt. La jerga operativa vive SOLO en la Zona Staff
 * (páginas *-staff.php, bandeja, paneles) y en los comentarios internos.
 *
 * Qué barre este test (superficies jugables):
 *  · Páginas públicas de la raíz (ficha, wizard, hub, tiendas, bibliotecas,
 *    portada, barco, mapa, alertas, mensajes, resumen, gestión…).
 *  · La ficha 7 Seas renderizada por `inc/ope_rol/dominio/ficha.php`
 *    (bloques Fruta, Haki, Tripulación, Misión en curso, Linaje, implantes).
 *  · El Manual del Jugador (documento que ve el jugador).
 *  · Los mensajes de retorno del motor y de los efectos al publicar
 *    (`return … 'msg'`, `msg =>`) — excluye los internos de la bandeja staff.
 *
 * Patrones de jerga operativa (palabras completas o multipatrón; NO substrings
 * como «ia» que falsean con «material», «tienda», «ia» vocabulario normal).
 * Se permite: el comentario docblock de cabecera y las líneas de código que
 * sean comentarios (// … o /* … *​/).
 *
 * Uso: php scripts/test-7seas-regla9b.php  (exit 0 = limpio, exit 1 = fallo)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-regla9b.php');
require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/ope_rol/bootstrap.php';

$G = array('ok' => 0, 'fail' => 0);
$G['chk'] = function ($label, $cond) use (&$G) {
    if ($cond) {
        $G['ok']++;
        echo "  OK — {$label}\n";
    } else {
        $G['fail']++;
        echo "  FALLO — {$label}\n";
    }
};

// ────────────────────────────────────────────────────────────
// Patrones de jerga operativa (mayúsculas/minúsculas indistinto)
// ────────────────────────────────────────────────────────────
$patrones = array(
    '/\bla\s+ia\b/i',               // "la IA propone/firma/decide"
    '/\bia\b/i',                      // "IA" como palabra independiente (p. ej. "una IA que analiza")
    '/\b(ia|inteligencia\s+artificial)\s+(propone|decide|firma|genera|construye|elabora|procesa|analiza)/i',
    '/inteligencia\s+artificial/i',
    '/asistente\s+virtual/i',
    // skill: nunca debe aparecer como palabra visible con "skill-"/"la skill"/"skill del"
    '/\bskill-/i',
    '/\bla\s+skill\b/i',
    '/\bskill\s+del\s+(anexo|manual|catalogo)\b/i',
    // prompt
    '/\bprompt("/|\b)?[a-z]/i',                     // "prompt" (que no sea clase CSS .pj-form-prompts)
    '/\bp\s*rompt\s+generado/i',
    // frases de proceso automático que revelan maquinaria interna
    '/resultado\s+de\s+la\s+ia/i',
    '/genera(do|r|ndo)?\s+(la\s+)?ficha\s+(completa\s+)?automaticamente/i',
    '/lo\s+(procesa|analiza)\s+(el\s+)?sistema/i',
    '/la\s+maquina\s+[a-z]/i',
    '/motor\s+5\.21/i',
);

/** ¿Cadena contiene jerga operativa? */
function regla9b_tiene_jerga($texto, array $patrones)
{
    foreach ($patrones as $re) {
        if (preg_match($re, $texto)) {
            return true;
        }
    }
    return false;
}

/** Quita los comentarios de código (bloque // y /* … *​/) para no contar
 *  la jerga que solo vive en docblocks/notas internas (permitida por D6.1). */
function regla9b_sin_comentarios($src)
{
    // Quita docblocks /* … */ — incluidas las secuencias de cierre escapadas.
    $src = preg_replace('~/\*.*?\*/~s', '', $src);
    // Quita comentarios de línea // (precauchado para no romper URLs).
    $src = preg_replace('~\s*//[^\n]*~', '', $src);
    return $src;
}

/** Elimina el cuerpo (llave equilibrada) de cada función *_panel_html (staff). */
function regla9b_quitar_paneles($src)
{
    $out = $src;
    $re = '/function \w*_panel_html\s*\([^)]*\)\s*\{/';
    $guard = 0;
    while (preg_match($re, $out, $m, PREG_OFFSET_CAPTURE) && $guard++ < 60) {
        $ini = $m[0][1];
        // a partir del '{' final encuentra el '{' real de apertura de cuerpo
        $ob = strrpos($m[0][0], '{');
        $bodyStart = $ini + $ob;
        $prof = 1;
        $len = strlen($out);
        $fin = $len;
        for ($i = $bodyStart + 1; $i < $len; $i++) {
            if ($out[$i] === '{') {
                $prof++;
            } elseif ($out[$i] === '}') {
                $prof--;
                if ($prof === 0) {
                    $fin = $i + 1;
                    break;
                }
            }
        }
        // Sustituye el tramo (declaración + cuerpo) por vacío.
        $out = substr($out, 0, $ini) . substr($out, $fin);
    }
    return $out;
}

echo "=== Test regla 9b: IA/skill/prompt nunca visibles al jugador ===\n";

// Raíz del repo, independiente del cwd (este test debe funcionar aunque se
// invoque desde scripts/ o desde la raíz; glob relativo sería un bug latente).
$RAIZ = dirname(__DIR__);
$archivos_jugables = array_merge(
    // Páginas públicas de la raíz (excluye toda la zona staff y paneles).
    // Además de *-staff.php y bandeja.php, hay páginas legacy de staff que no
    // siguen ese patrón de nombre pero están blindadas en bloque con un guard
    // ope7_es_staff / ope_rol_active_staff (no llegan al jugador y conservan
    // jerga legítima según D6.1): resolucion-combate y mundo-vivo (legacy de
    // cierre de combate previos a los paneles A.3; revision-viaje fue eliminada
    // en el drop físico del legado). ficha.php y biblioteca-akuma.php NO se
    // excluyen: son páginas jugables que solo añaden un extra al staff, y su
    // texto visible debe seguir la regla 9b.
    array_filter(glob($RAIZ . '/*.php'), function ($f) use ($RAIZ) {
        $base = basename($f);
        return !preg_match('/\-staff\.php$|bandeja\.php$/', $base)
            && !in_array($base, array('resolucion-combate.php', 'mundo-vivo.php'), true);
    }),
    // Ficha 7 Seas (bloques con texto visible al jugador)
    array($RAIZ . '/inc/ope_rol/dominio/ficha.php')
);

// ── [1] Páginas públicas + ficha 7 Seas ──
$fallos_arch = array();
foreach ($archivos_jugables as $f) {
    if (!is_file($f)) {
        continue;
    }
    $src = (string) file_get_contents($f);
    $sin_coment = regla9b_sin_comentarios($src);
    foreach ($patrones as $re) {
        if (preg_match_all($re, $sin_coment, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $hit) {
                // Localiza la línea para el mensaje
                $linea = substr_count(substr($src, 0, $hit[1]), "\n") + 1;
                $fallos_arch[] = $f . ':' . $linea . ' — «' . trim($hit[0]) . '»';
            }
        }
    }
}
$G['chk']('[1] Páginas públicas y ficha 7 Seas sin jerga IA', empty($fallos_arch));
foreach (array_slice($fallos_arch, 0, 15) as $b) {
    echo "        " . $b . "\n";
}

// ── [2] Manual del Jugador (documento visible al jugador) ──
$manual_jug = __DIR__ . '/../docs/sistema/Manual_del_Jugador.md';
$fallos_manual = array();
if (is_file($manual_jug)) {
    $lines = file($manual_jug);
    foreach ($lines as $i => $ln) {
        if (regla9b_tiene_jerga($ln, $patrones)) {
            $fallos_manual[] = 'Manual_del_Jugador.md:' . ($i + 1) . ' — «' . trim($ln) . '»';
        }
    }
}
$G['chk']('[2] Manual del Jugador sin jerga IA/skill/prompt', empty($fallos_manual));
foreach (array_slice($fallos_manual, 0, 15) as $b) {
    echo "        " . $b . "\n";
}

// ── [3] Strings de retorno del motor/efectos (visibles al jugador) ──
// EXCLUYE el cuerpo de las funciones *_panel_html (zona staff), que según D6.1
// SÍ conservan jerga operativa. Solo valida los strings de "msg" que devuelven
// los efectos al publicar y llegan al flash/hub del jugador.
$motor = MYBB_ROOT . 'inc/ope_rol/tramites/motor.php';
$sistemas = MYBB_ROOT . 'inc/ope_rol/sistemas/';
$archivos_motor = array_merge(
    array($motor),
    glob($sistemas . '*.php') ?: array()
);
$fallos_msg = array();
foreach ($archivos_motor as $f) {
    if (!is_file($f)) {
        continue;
    }
    $src = (string) file_get_contents($f);
    $sin = regla9b_sin_comentarios($src);
    // Quita el cuerpo de cada *_panel_html (zona staff): desde su decl hasta
    // la llave equilibrada. Esto evita marcar la jerga legítima del panel.
    $sin_paneles = preg_replace_callback(
        '/function \w*panel_html\s*\([^)]*\)\s*\{/',
        function ($m) {
            return ''; // la declaración se elimina con su cuerpo vía balance abajo
        },
        $sin
    );
    // (La simple sustitución anterior no borra el cuerpo; reforzamos usando
    // balance de llaves por cada función *_panel_html dentro del fuente.
    $sin2 = regla9b_quitar_paneles($src);
    $sin2 = regla9b_sin_comentarios($sin2);
    // Recolecta strings entre comillas (simples) que sean/estén junto a msg.
    $sin2 = str_replace(array('"', '='), "'", $sin2); // normaliza comillas
    preg_match_all("/'([^']*)'/u", $sin2, $strs, PREG_OFFSET_CAPTURE);
    foreach ($strs[1] as $s) {
        $texto = trim($s[0]);
        if (!regla9b_tiene_jerga($texto, $patrones)) {
            continue;
        }
        // Solo nos interesan strings que son/parecen un mensaje de retorno.
        // Salta fragmentos cortos tipo etiqueta clave ("ia", "skill").
        if (preg_match('/^(ia|skill|prompt)$/i', $texto)) {
            continue;
        }
        $linea = substr_count(substr($src, 0, $s[1]), "\n") + 1;
        $fallos_msg[] = basename($f) . ':' . $linea . ' — «' . mb_substr($texto, 0, 90) . (mb_strlen($texto) > 90 ? '…' : '') . '»';
    }
}
$G['chk']('[3] Strings de retorno del motor/efectos sin jerga IA', empty($fallos_msg));
foreach (array_slice($fallos_msg, 0, 15) as $b) {
    echo "        " . $b . "\n";
}

// ── [4] Vista jugador del hub (ope7_tramites_jugador_html) no expone jerga ──
// Solo la función del JUGADOR (la vista operativa staff es bandeja, excluida por
// estar en *-staff/bandeja; aquí el chip filtro ya usa «Con firma del staff»).
$bandeja = MYBB_ROOT . 'inc/ope_rol/tramites/bandeja.php';
$fallos_hub = array();
if (is_file($bandeja)) {
    $src = (string) file_get_contents($bandeja);
    if (preg_match('/function ope7_tramites_jugador_html\([^)]*\)\s*\{/', $src, $m2, PREG_OFFSET_CAPTURE)) {
        $ini = $m2[0][1];
        // Cierra la llave de la función (balance de {} desde el ábrir).
        $prof = 0;
        $len = strlen($src);
        $fin2 = $len;
        for ($i = $ini; $i < $len; $i++) {
            $c = $src[$i];
            if ($c === '{') {
                $prof++;
            } elseif ($c === '}') {
                $prof--;
                if ($prof === 0) {
                    $fin2 = $i + 1;
                    break;
                }
            }
        }
        $segmento = substr($src, $ini, $fin2 - $ini);
        $sin_coment = regla9b_sin_comentarios($segmento);
        // El chip de filtro usa el valor interno "ia" (no visible); lo neutraliza.
        $sin_coment = str_replace('data-filtro="ia"', 'data-filtro="auto"', $sin_coment);
        foreach ($patrones as $re) {
            if (preg_match($re, $sin_coment)) {
                $fallos_hub[] = 'bandeja.php (vista jugador) con jerga';
                break;
            }
        }
    }
}
$G['chk']('[4] Vista jugador del hub de trámites sin jerga IA', empty($fallos_hub));
foreach ($fallos_hub as $b) {
    echo "        " . $b . "\n";
}

echo "\n=== Regla 9b: {$G['ok']} OK / {$G['fail']} FALLO ===\n";
exit($G['fail'] === 0 ? 0 : 1);