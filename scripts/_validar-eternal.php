<?php
/**
 * Validador del Sistema Eternal
 *
 * Uso: php scripts/_validar-eternal.php
 *
 * Valida:
 *  - Todos los JSON en inc/ope_eternal/
 *  - Todos los MD en docs/11-SISTEMA ETERNAL/ (hermano en I-Forge-Sistema)
 */

declare(strict_types=1);

// ── Rutas ──────────────────────────────────────────────────────────────
$SCRIPT_DIR = dirname(__DIR__); // .../Op-Eternal/Eternal-RPG
$SISTEMA_DIR = dirname($SCRIPT_DIR) . '/Eternal-Sistema'; // .../Op-Eternal/Eternal-Sistema

$JSON_DIR        = $SCRIPT_DIR . '/inc/ope_eternal';
$MD_DIR          = $SISTEMA_DIR . '/docs/11-SISTEMA ETERNAL';

// ── Contadores ────────────────────────────────────────────────────────
$errores   = 0;
$ads       = 0;

// ── Funciones helper ──────────────────────────────────────────────────

function err(string $msg): void {
    global $errores;
    echo "  ERROR: $msg\n";
    $errores++;
}

function advertencia(string $msg): void {
    global $ads;
    echo "  ADVERTENCIA: $msg\n";
    $ads++;
}

function linea(string $msg): void {
    echo "$msg\n";
}

function json_id_from_filename(string $fname): string {
    return str_replace('.json', '', $fname);
}

function md_file_for_json(string $jsonFname): string {
    // arma-filo.json -> Arbol-Arma-Filo.md
    // identidad-fantasma.json -> Arbol-Identidad-Fantasma.md
    $parts = explode('-', str_replace('.json', '', $jsonFname), 2);
    if (count($parts) !== 2) {
        return '';
    }
    $type = $parts[0]; // arma or identidad
    $name = $parts[1]; // filo, fantasma, etc.
    $typeCap = ucfirst($type);
    $nameCap = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
    return "Arbol-$typeCap-$nameCap.md";
}

// ── Patrones de texto vago ────────────────────────────────────────────

$VAGUE_PATTERNS = [
    '/\bdaño\s+leve\b/i'               => '"daño leve" sin número',
    '/\bdefensa\s+leve\b/i'            => '"defensa leve" sin número',
    '/\bevasión\s+leve\b/i'           => '"evasión leve" sin número',
    '/daño\s+creciente(?!\s*(?:[:=]\s*)?[√⌊⌈+\d])/i' => '"daño creciente" sin fórmula',
    '/genera\s+(?:el\s+)?(?:un\s+)?(?:poco\s+de\s+)?(?:[a-záéíóúñ]+\s+)*[a-záéíóúñ]+\s+(?:sin\s+(?:una\s+)?cantidad|sin\s+número)/i' => 'genera recurso sin cantidad', // catch-all aproximado
];

$VAGUE_WORDS = [
    'rápidamente', 'lentamente', 'ligeramente', 'moderadamente',
    'considerablemente', 'breve', 'algo de', 'cierta cantidad',
];

// busca "reduce X" sin número a continuación
function encontrar_reduce_sin_cantidad(string $text, ?string &$match = null): bool {
    // Ignorar frases binarias: "no se reduce", "ya no reduce" (efecto sí/no)
    if (preg_match('/\bno\s+(se\s+)?reduce/i', $text)) {
        return false;
    }
    // Ignorar si hay número después (incluyendo − (Unicode minus) y %)
    if (preg_match('/\breduce\b.*?[\d\−]/i', $text)) {
        return false;
    }
    // Ignorar cantidades textuales válidas ("a la mitad", "al doble", etc.)
    if (preg_match('/\breduce\b[^.]*?\b(a\s+la\s+mitad|a\s+la\s+cuarta|a\s+un\s+tercio|al\s+doble)/i', $text)) {
        return false;
    }
    if (preg_match('/\breduce\b(?:\s+\w+){0,4}\s+(?!\d+)/i', $text, $m)) {
        $match = $m[0];
        return true;
    }
    return false;
}

// busca "una parte/porción" sin %
function encontrar_parte_sin_pct(string $text, ?string &$match = null): bool {
    if (preg_match('/\buna\s+(parte|porci[óo]n)\b/i', $text)
        && !preg_match('/\buna\s+(parte|porci[óo]n).*?%/i', $text)) {
        $match = 'una ' . (preg_match('/parte/i', $text) ? 'parte' : 'porción') . ' sin %';
        return true;
    }
    return false;
}

// busca "%" sin número antes
function encontrar_pct_sin_numero(string $text, ?string &$match = null): bool {
    // "% máximo", "% del total" sin número antes (ignorar "25%", "50 %", etc.)
    // 1. Find all "%" occurrences
    $found = false;
    $parts = explode('%', $text);
    $accum = 0;
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $accum += strlen($parts[$i]) + 1; // position just after this '%'
        $before = substr($text, max(0, $accum - 12), $accum - 1);
        if (!preg_match('/\d/', $before)) {
            // After %, check for keywords
            $after = substr($text, $accum, 20);
            if (preg_match('/^\s*(?:m[áa]ximo|total|del|de\s+la|de\s+el|del\s+total)/i', $after)) {
                $match = '"% ' . trim(substr($after, 0, 10)) . '" sin valor numérico';
                $found = true;
            }
        }
    }
    return $found;
}

function escanear_texto_vago(string $texto, array &$hallazgos, string $contexto): void {
    global $VAGUE_PATTERNS, $VAGUE_WORDS;

    foreach ($VAGUE_PATTERNS as $regex => $label) {
        if (preg_match($regex, $texto)) {
            $hallazgos[] = "$contexto: $label";
        }
    }

    foreach ($VAGUE_WORDS as $palabra) {
        if (mb_stripos($texto, $palabra) !== false) {
            $hallazgos[] = "$contexto: texto vague \"$palabra\"";
        }
    }

    $tmp = null;
    if (encontrar_reduce_sin_cantidad($texto, $tmp)) {
        $hallazgos[] = "$contexto: \"reduce\" sin cantidad";
    }
    if (encontrar_parte_sin_pct($texto, $tmp)) {
        $hallazgos[] = "$contexto: $tmp";
    }
    if (encontrar_pct_sin_numero($texto, $tmp)) {
        $hallazgos[] = "$contexto: $tmp";
    }
}

// ── Gap stats ─────────────────────────────────────────────────────────

function escanear_gap_stat(string $texto, string $nodeId, array &$hallazgos): void {
    $gapStats = ['derribo', 'empuje', 'control', 'esquivar'];
    $stats    = ['FUE', 'AGI', 'CON', 'VOL', 'PER', 'INT', 'fuerza', 'agilidad', 'constitucion', 'voluntad', 'percepcion', 'inteligencia'];
    // Skip core mechanics defined in §5 (Gap stats): esquivar=AGI, derribo=FUE
    // Only flag if the text describes a NEW application, not just referencing the mechanic

    foreach ($gapStats as $gs) {
        if (mb_stripos($texto, $gs) !== false) {
            $tieneStat = false;
            foreach ($stats as $st) {
                if (mb_stripos($texto, $st) !== false) {
                    $tieneStat = true;
                    break;
                }
            }
            if (!$tieneStat) {
                $hallazgos[] = "nodo [$nodeId]: menciona \"$gs\" pero no especifica qué estadística lo modifica";
            }
        }
    }
}

// ── Validar JSON ──────────────────────────────────────────────────────

function validar_json(string $filePath): bool {
    global $errores, $ads;
    $basename  = basename($filePath);
    $fileId    = json_id_from_filename($basename);
    $pass      = true;
    $jsonIssues = [];

    // 1. Parse
    $data = json_decode(file_get_contents($filePath), true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        err("$basename: JSON inválido — " . json_last_error_msg());
        return false;
    }

    // 2. ID match
    $actualId = $data['id'] ?? null;
    if ($actualId !== $fileId) {
        err("$basename: id \"$actualId\" no coincide con el nombre del archivo (\"$fileId\")");
        $pass = false;
    }

    // 3. Required fields
    $required = ['id', 'nombre', 'tipo', 'version', 'recurso_secundario', 'resumen_arquetipo', 'rol_mecanico', 'focos', 'nodos'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $data)) {
            err("$basename: falta campo requerido \"$field\"");
            $pass = false;
        }
    }

    $focos = $data['focos'] ?? [];
    $nodos = $data['nodos'] ?? [];

    if (!is_array($nodos) || count($nodos) === 0) {
        err("$basename: \"nodos\" debe ser un array no vacío");
        return false;
    }

    // ── Node checks ──
    $nodeIds = [];
    foreach ($nodos as $i => $nodo) {
        $idx     = $i + 1;
        $nodeId  = $nodo['id'] ?? "(sin id en nodo #$idx)";

        // Required node fields (v3 "Forja")
        $nodeRequired = ['id', 'nombre', 'tier', 'foco', 'tipo', 'coste_pt', 'efecto'];
        foreach ($nodeRequired as $nf) {
            if (!array_key_exists($nf, $nodo)) {
                err("$basename / nodo [$nodeId]: falta campo \"$nf\"");
                $pass = false;
            }
        }

        // tier 1-5
        if (isset($nodo['tier']) && ($nodo['tier'] < 1 || $nodo['tier'] > 5)) {
            err("$basename / nodo [$nodeId]: tier debe estar entre 1 y 5 (actual: {$nodo['tier']})");
            $pass = false;
        }

        // foco debe estar en focos (v3: no hay columna "General")
        $foco = $nodo['foco'] ?? '';
        if (!in_array($foco, $focos)) {
            err("$basename / nodo [$nodeId]: foco \"$foco\" no está en el array de corrientes del árbol");
            $pass = false;
        }

        // tipo v3: solo pasiva | habilitador | mini-sistema
        $tipo = $nodo['tipo'] ?? '';
        if (!in_array($tipo, ['pasiva', 'habilitador', 'mini-sistema'])) {
            err("$basename / nodo [$nodeId]: tipo \"$tipo\" no es válido v3 (pasiva|habilitador|mini-sistema)");
            $pass = false;
        }

        // coste_pt debe ser 1 en v3
        if (array_key_exists('coste_pt', $nodo) && (int) $nodo['coste_pt'] !== 1) {
            advertencia("$basename / nodo [$nodeId]: coste_pt debería ser 1 en v3 (actual: {$nodo['coste_pt']})");
        }

        // efecto debe ser string; "efectos"/"rango" son legacy v2
        if (array_key_exists('efectos', $nodo) || array_key_exists('rango', $nodo)) {
            err("$basename / nodo [$nodeId]: contiene campos legacy v2 (\"efectos\"/\"rango\"); v3 usa un único \"efecto\" string");
            $pass = false;
        }
        if (array_key_exists('efecto', $nodo) && !is_string($nodo['efecto'])) {
            err("$basename / nodo [$nodeId]: \"efecto\" debe ser un string");
            $pass = false;
        }

        // Pináculos (tier 5): deben marcar pinaculo=true, tener afinidad y excluir
        if (($nodo['tier'] ?? 0) === 5) {
            if (empty($nodo['pinaculo'])) {
                err("$basename / nodo [$nodeId]: nodo de Tier 5 debe tener \"pinaculo\": true");
                $pass = false;
            }
            if (empty($nodo['afinidad'])) {
                advertencia("$basename / nodo [$nodeId]: pináculo sin campo \"afinidad\"");
            }
            if (empty($nodo['excluye'])) {
                err("$basename / nodo [$nodeId]: pináculo debe excluir a los otros pináculos");
                $pass = false;
            }
        }

        // 5. Duplicate IDs
        if (in_array($nodeId, $nodeIds)) {
            err("$basename / nodo [$nodeId]: ID duplicado");
            $pass = false;
        }
        $nodeIds[] = $nodeId;

        // ── Vague text scan on efecto + profundidad + afinidad_bonus ──
        $textos = [];
        if (isset($nodo['efecto']) && is_string($nodo['efecto'])) {
            $textos[] = $nodo['efecto'];
        }
        if (!empty($nodo['profundidad']) && is_string($nodo['profundidad'])) {
            $textos[] = $nodo['profundidad'];
        }
        if (!empty($nodo['afinidad_bonus']) && is_string($nodo['afinidad_bonus'])) {
            $textos[] = $nodo['afinidad_bonus'];
        }
        foreach ($textos as $txt) {
            escanear_texto_vago($txt, $jsonIssues, "$basename / nodo [$nodeId]");
        }
    }

    // ── Estructura v3: 15 nodos, 5 tiers × 3 corrientes ──
    if (count($nodos) !== 15) {
        err("$basename: v3 requiere exactamente 15 nodos (actual: " . count($nodos) . ")");
        $pass = false;
    }
    if (count($focos) !== 3) {
        err("$basename: v3 requiere exactamente 3 corrientes en \"focos\" (actual: " . count($focos) . ")");
        $pass = false;
    }
    $porTier = [];
    foreach ($nodos as $nodo) {
        $t = $nodo['tier'] ?? 0;
        $porTier[$t] = ($porTier[$t] ?? 0) + 1;
    }
    foreach (range(1, 5) as $t) {
        $cnt = $porTier[$t] ?? 0;
        if ($cnt !== 3) {
            err("$basename: el Tier $t debe tener 3 nodos (uno por corriente); tiene $cnt");
            $pass = false;
        }
    }

    // 6. Orphaned prereqs
    foreach ($nodos as $nodo) {
        $nodeId = $nodo['id'] ?? '';
        $prereqs = $nodo['prereq'] ?? [];
        foreach ($prereqs as $pr) {
            if (!in_array($pr, $nodeIds)) {
                err("$basename / nodo [$nodeId]: prereq \"$pr\" no existe en este árbol");
                $pass = false;
            }
        }
    }

    // 7. excluye references
    foreach ($nodos as $nodo) {
        $nodeId = $nodo['id'] ?? '';
        $excluye = $nodo['excluye'] ?? [];
        foreach ($excluye as $ex) {
            if (!in_array($ex, $nodeIds)) {
                err("$basename / nodo [$nodeId]: excluye \"$ex\" no existe en este árbol");
                $pass = false;
            }
        }
    }

    // Print issues
    foreach ($jsonIssues as $issue) {
        advertencia($issue);
    }

    return $pass;
}

// ── Validar MD ───────────────────────────────────────────────────────

function validar_md(string $filePath, string $label): bool {
    global $errores;
    $basename = basename($filePath);
    $pass     = true;

    if (!file_exists($filePath)) {
        err("MD: Falta archivo \"$basename\" (esperado para $label)");
        return false;
    }

    $content = file_get_contents($filePath);

    // 3. Section completeness: Tiers 1-5
    foreach (range(1, 5) as $tier) {
        if (!preg_match('/##\s*' . $tier . '\.\s*TIER\s+' . $tier . '/i', $content)) {
            err("$basename: falta sección TIER $tier");
            $pass = false;
        }
    }

    // Check each tier section is not blank
    $sections = preg_split('/##\s*\d+\.\s*TIER\s+\d+/i', $content);
    // sections[0] is before first tier; sections[1..5] are tier 1-5
    for ($i = 1; $i <= 5; $i++) {
        if (!isset($sections[$i]) || trim(strip_tags($sections[$i])) === '') {
            err("$basename: TIER $i está vacío");
            $pass = false;
        }
    }

    // 2. Generic pattern scan on table rows
    $lines = explode("\n", $content);
    $mdIssues = [];
    foreach ($lines as $line) {
        // only check table rows (lines starting with |)
        if (!str_starts_with(trim($line), '|')) continue;
        $cells = explode('|', $line);
        // The effect cell is typically the last one (index 4 or 5 depending on columns)
        // Columns vary: Nodo|Tipo|Notación|Prerreq.|Efecto (5 columns = cells[1..5])
        // or Pináculo|Predecesor(es)|Camino|Efecto (4 columns = cells[1..4])
        // We'll check all cells
        for ($c = 1; $c < count($cells); $c++) {
            $cell = trim($cells[$c]);
            if ($cell === '' || $cell === '---|---|---|---|---') continue;
            escanear_texto_vago($cell, $mdIssues, "$basename");
        }
    }

    foreach ($mdIssues as $issue) {
        advertencia($issue);
    }

    return $pass;
}

// ══════════════════════════════════════════════════════════════════════
//  MAIN
// ══════════════════════════════════════════════════════════════════════

linea("=== VALIDACIÓN DEL SISTEMA ETERNAL ===");
linea("");

// ── JSON files ──
linea("--- Validando JSON (" . realpath($JSON_DIR) . ") ---");
$jsonFiles = glob($JSON_DIR . '/*.json');
sort($jsonFiles);
$jsonPassCount = 0;
$jsonFailCount = 0;

foreach ($jsonFiles as $jf) {
    $basename = basename($jf);
    linea("  [$basename]");
    $ok = validar_json($jf);
    if ($ok) {
        linea("    -> OK\n");
        $jsonPassCount++;
    } else {
        linea("    -> FALLO\n");
        $jsonFailCount++;
    }
}

// ── MD files ──
linea("--- Validando MD (" . realpath($MD_DIR) . ") ---");
$mdPassCount = 0;
$mdFailCount = 0;

foreach ($jsonFiles as $jf) {
    $basename = basename($jf);
    $mdName   = md_file_for_json($basename);
    $label    = str_replace('.json', '', $basename);
    $mdPath   = $MD_DIR . '/' . $mdName;
    linea("  [$mdName]");
    $ok = validar_md($mdPath, $label);
    if ($ok) {
        linea("    -> OK\n");
        $mdPassCount++;
    } else {
        linea("    -> FALLO\n");
        $mdFailCount++;
    }
}

// ── Summary ──
linea("=== RESUMEN ===");
linea("JSON: $jsonPassCount OK, $jsonFailCount fallos");
linea("MD:   $mdPassCount OK, $mdFailCount fallos");

$totalErrores   = $errores;
$totalAds       = $ads;

linea("");
if ($totalErrores === 0 && $totalAds === 0) {
    linea("0 errores, 0 advertencias");
    exit(0);
} elseif ($totalErrores === 0) {
    linea("0 errores, $totalAds advertencias");
    exit(0);
} else {
    linea("$totalErrores errores, $totalAds advertencias");
    exit(1);
}
