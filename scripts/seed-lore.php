<?php
/**
 * One Piece: Eternal · Seed de Biblioteca de Lore
 * ---------------------------------------------------------------
 * Lee todos los archivos .md de One Piece: Eternal-Sistema y los inserta
 * en la tabla mybb_rol_lore con categorías y subcategorías.
 *
 * Requisito previo: ejecutar php scripts/migrate-lore.php
 * Ejecutar:        php scripts/seed-lore.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/_db-config.php';

$PREFIX = 'mybb_';
$SISTEMA_ROOT = realpath(__DIR__ . '/../../One Piece: Eternal-Sistema');
if (!$SISTEMA_ROOT || !is_dir($SISTEMA_ROOT)) {
    die("Error: One Piece: Eternal-Sistema directory not found at expected location.\n");
}
$TABLE = "{$PREFIX}rol_lore";

// ── Funciones helper ──

function seed_md_escape(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function seed_flush_p(string &$p, string &$out): void {
    $p = trim($p);
    if ($p !== '' && strpos($p, '<h') !== 0 && strpos($p, '<table') !== 0
        && strpos($p, '<tr') !== 0 && strpos($p, '<hr') !== 0
        && strpos($p, '<ul') !== 0 && strpos($p, '<ol') !== 0
        && strpos($p, '<pre') !== 0 && strpos($p, '<block') !== 0) {
        $p = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $p);
        $p = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $p);
        $p = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $p);
        $p = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $p);
        $p = preg_replace('/`([^`]+)`/', '<code>$1</code>', $p);
        $p = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $p);
        $out .= '<p>' . $p . '</p>' . "\n";
    }
    $p = '';
}

function seed_md_to_html(string $md): string {
    $md = preg_replace('/^---\R.*?\R---\R/s', '', $md);
    $lines = explode("\n", $md);
    $out = '';
    $inTable = false;
    $inList = false;
    $inCode = false;
    $paragraph = '';

    for ($i = 0; $i < count($lines); $i++) {
        $line = rtrim($lines[$i]);

        if ($inTable && strpos($line, '|') !== 0) {
            $out .= "</tbody></table>\n";
            $inTable = false;
        }
        if ($inList && !preg_match('/^[\*\-\+]\s/', $line) && !preg_match('/^\d+\.\s/', $line) && trim($line) !== '') {
            $out .= "</{$inList}>\n";
            $inList = false;
        }
        if ($inCode && strpos($line, '```') === 0) {
            $out .= "</code></pre>\n";
            $inCode = false;
            continue;
        }
        if ($inCode) {
            $out .= seed_md_escape($line) . "\n";
            continue;
        }

        if (strpos($line, '```') === 0) {
            seed_flush_p($paragraph, $out);
            $inCode = true;
            $out .= '<pre><code>';
            continue;
        }

        if (trim($line) === '') {
            seed_flush_p($paragraph, $out);
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            seed_flush_p($paragraph, $out);
            $level = strlen($m[1]);
            $text = $m[2];
            $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
            $out .= "<h{$level}>" . $text . "</h{$level}>\n";
            continue;
        }

        if (preg_match('/^[-*_]{3,}\s*$/', $line)) {
            seed_flush_p($paragraph, $out);
            $out .= "<hr>\n";
            continue;
        }

        if (strpos($line, '|') === 0) {
            seed_flush_p($paragraph, $out);
            $cells = array_map('trim', explode('|', trim($line, '|')));
            $isSep = true;
            foreach ($cells as $c) { if (!preg_match('/^[-:]+$/', $c)) { $isSep = false; break; } }
            if ($isSep) continue;
            if (!$inTable) { $out .= "<table>\n"; $inTable = true; }
            $row = '';
            foreach ($cells as $c) {
                $c = seed_md_escape($c);
                $row .= "<td>" . $c . "</td>";
            }
            $out .= "<tr>{$row}</tr>\n";
            continue;
        }

        if (preg_match('/^[\*\-\+]\s+(.+)$/', $line, $m)) {
            seed_flush_p($paragraph, $out);
            if ($inList !== 'ul') { if ($inList) $out .= "</{$inList}>\n"; $out .= "<ul>\n"; $inList = 'ul'; }
            $item = $m[1];
            $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
            $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
            $out .= '<li>' . $item . "</li>\n";
            continue;
        }
        if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
            seed_flush_p($paragraph, $out);
            if ($inList !== 'ol') { if ($inList) $out .= "</{$inList}>\n"; $out .= "<ol>\n"; $inList = 'ol'; }
            $item = $m[1];
            $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
            $item = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $item);
            $out .= '<li>' . $item . "</li>\n";
            continue;
        }

        if (preg_match('/^>\s?(.*)$/', $line, $m)) {
            seed_flush_p($paragraph, $out);
            $out .= '<blockquote><p>' . seed_md_escape($m[1]) . '</p></blockquote>' . "\n";
            continue;
        }

        $paragraph .= ($paragraph ? ' ' : '') . $line;
    }

    seed_flush_p($paragraph, $out);
    if ($inList) $out .= "</{$inList}>\n";
    if ($inTable) $out .= "</tbody></table>\n";
    if ($inCode) $out .= "</code></pre>\n";

    return trim($out);
}

function seed_extract_summary(string $html, int $maxlen = 300): string {
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (mb_strlen($text) > $maxlen) {
        $text = mb_substr($text, 0, $maxlen) . '…';
    }
    return $text;
}

function seed_slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $trans = array(
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
        'ò' => 'o', 'ù' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i',
        'ö' => 'o', 'ö' => 'o', 'â' => 'a', 'ê' => 'e', 'î' => 'i',
        'ô' => 'o', 'û' => 'u',
    );
    $text = strtr($text, $trans);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function seed_insert_lore(mysqli $db, string $table, array $data): void {
    $now = time();
    $cols = array();
    $vals = array();
    foreach ($data as $k => $v) {
        $cols[] = "`{$k}`";
        $vals[] = "'" . $db->real_escape_string((string)$v) . "'";
    }
    $cols[] = '`dateline`';
    $vals[] = (string)$now;
    $sql = "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
    if ($db->query($sql) === false) {
        fwrite(STDERR, "  [ERROR] insert {$data['nombre']}: " . $db->error . "\n");
    } else {
        echo "  [OK] {$data['categoria']}: {$data['nombre']}\n";
    }
}

// ── Verificar que la tabla existe ──

$check = $db->query("SHOW TABLES LIKE '{$TABLE}'");
if ($check->num_rows === 0) {
    fwrite(STDERR, "ERROR: La tabla {$TABLE} no existe. Ejecuta primero php scripts/migrate-lore.php\n");
    exit(1);
}

echo "WARNING: This will DELETE all data from {$TABLE}. Continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);
if (strtolower($line) !== 'yes') {
    echo "Aborted.\n";
    exit(0);
}
$db->query("DELETE FROM `{$TABLE}`");
echo "\n=== Sembrando Biblioteca de Lore ===\n\n";

$orden = 0;

// ── 1. HISTORIA ──
$file = $SISTEMA_ROOT . '/one-piece-eternal-lore/story.md';
if (is_file($file)) {
    $raw = file_get_contents($file);
    $html = seed_md_to_html($raw);
    seed_insert_lore($db, $TABLE, array(
        'nombre' => 'One Piece: Eternal: Sinopsis',
        'slug' => 'sinopsis-one-piece-eternal',
        'categoria' => 'historia',
        'subcategoria' => 'sinopsis',
        'resumen' => 'La indiscutible Reina de los Piratas, Isabella D. Vega, ha sido capturada tras la traición de su confidente. Faltan 30 días para su ejecución pública en Marineford.',
        'contenido' => $html,
        'imagen' => '',
        'orden' => ++$orden,
        'activo' => 1,
    ));
}

// ── 2. CRONOLOGÍA ──
$file = $SISTEMA_ROOT . '/one-piece-eternal-lore/plot/timeline.md';
if (is_file($file)) {
    $raw = file_get_contents($file);
    $html = seed_md_to_html($raw);
    seed_insert_lore($db, $TABLE, array(
        'nombre' => 'Cronología Completa',
        'slug' => 'cronologia-completa',
        'categoria' => 'cronologia',
        'subcategoria' => 'linea-temporal',
        'resumen' => 'Línea de tiempo oficial desde la caída del Gran Reino (hace 900 años) hasta la inminente ejecución de la Reina Pirata en Marineford.',
        'contenido' => $html,
        'imagen' => '',
        'orden' => ++$orden,
        'activo' => 1,
    ));
}

// ── 3. ERAS ──
$eras_map = array(
    array('Era 1: El Gran Olvido', 'era-1-gran-olvido', 'La Gran Guerra destruye el Gran Reino. El Siglo Vacío borra un siglo de historia. Las 16 Familias Fundadoras establecen el Gobierno Mundial.',
        '<p>Hace 900 años, estalla la Gran Guerra entre el Gran Reino y una alianza de reinos menores por razones perdidas en el tiempo. Un siglo después, el Gran Reino es completamente borrado de la historia en lo que se conoce como el <strong>Siglo Vacío</strong>.</p><p>Las <strong>16 Familias Fundadoras</strong> suben al Red Line y establecen <strong>Mary Geoise</strong>, coronándose como Dioses Mundiales y censurando todo el conocimiento antiguo. Los <strong>Poneglyphs</strong> son creados como única fuente de verdad histórica indestructible, dispersados por todo el mundo para preservar lo que el Gobierno Mundial intentó destruir.</p>'),
    array('Era 2: La Edad de las Bestias', 'era-2-edad-bestias', 'Navegantes legendarios cartografían el Grand Line. Grog "Rompe-Cielos" se erige como el guerrero más formidable de Elbaf.',
        '<p>Hace 400 años, la <strong>Gran Exploración</strong> cartografía el Grand Line, estableciendo las rutas y la geografía actual del mundo que todos conocen. Los Log Pose se convierten en la herramienta indispensable de navegación.</p><p>Hace 50 años, <strong>Grog "Rompe-Cielos"</strong> despierta como el guerrero más formidable y aterrador de la nueva generación de Elbaf, llevando la gloria a la raza de los gigantes y demostrando que el poder bruto puede rivalizar con cualquier Fruta del Diablo.</p>'),
    array('Era 3: El Ascenso de los Nuevos Emperadores', 'era-3-ascenso-emperadores', 'Isabella escapa y funda los Piratas Carmesí. Los cuatro Yonko emergen. Valyria se convierte en Almirante de Flota.',
        '<p>Hace 30 años, <strong>Isabella D. Vega</strong> escapa de la tiranía de su isla natal, forjando un odio eterno hacia el mundo noble. Cinco años después funda los <strong>Piratas Carmesí</strong> y se asocia temporalmente con el colosal gigante Balgor.</p><p>Hace 20 años se consolida como amenaza mundial al chocar contra otros monstruos, mientras <strong>Sekhmet "Reina Leona"</strong> —una majestuosa Mink leona— y <strong>Shura "Oni Iluminada"</strong> —una terrorífica Oni— emergen en el Nuevo Mundo.</p><p>Hace 15 años, <strong>Valyria</strong> asciende meteóricamente a Almirante de Flota tras cortar una isla por la mitad, imponiendo el respeto absoluto. Hace 10 años, <strong>Balgor</strong> asimila varias flotas enteras para convertirse en un Mecha gigante, deserta y se corona Yonko como <strong>"Titán de Chatarra"</strong>. Ese mismo año, <strong>Ezekiel</strong> —un híbrido Skypiean/Lunarian— comienza a cazar piratas desde los cielos con su rifle de Diales, siendo catalogado como el cuarto Emperador.</p>'),
    array('Era 4: La Caída de la Reina', 'era-4-caida-reina', 'Isabella descubre la verdad en La Última Isla. Balgor la traiciona. Valyria la captura. Faltan 30 días para su ejecución.',
        '<p>Hace 5 años, Isabella y sus leales llegan a <strong>La Última Isla</strong>. Descubre la verdad del mundo pero opta por no atacar aún, siendo coronada <strong>"Reina de los Piratas"</strong>.</p><p>Hace un mes, <strong>Balgor</strong> traiciona su antiguo pacto vendiendo las coordenadas de Isabella a cambio de armamento. La Almirante de Flota <strong>Valyria</strong> intercepta a Isabella y, tras un duelo de espadas legendario, la captura.</p><p><strong>En la actualidad (Día 0):</strong> Faltan exactamente <strong>30 días</strong> para la ejecución pública de Isabella D. Vega en Marineford. Las fuerzas mundiales se preparan para la Guerra Total. Los Cuatro Emperadores mueven ficha. El mundo entra en su era más caótica y decisiva.</p>'),
);
foreach ($eras_map as $era) {
    seed_insert_lore($db, $TABLE, array(
        'nombre' => $era[0], 'slug' => $era[1], 'categoria' => 'eras',
        'subcategoria' => 'era-historica', 'resumen' => $era[2],
        'contenido' => $era[3], 'imagen' => '', 'orden' => ++$orden, 'activo' => 1,
    ));
}

// ── 4. PERSONAJES ──
$chars_dir = $SISTEMA_ROOT . '/one-piece-eternal-lore/characters/';
if (is_dir($chars_dir)) {
    foreach (glob($chars_dir . '*.md') as $file) {
        if (basename($file) === '_index.md') continue;
        $raw = file_get_contents($file);
        $html = seed_md_to_html($raw);
        // Extract name: try frontmatter name:, then H1, then basename
        $name = '';
        if (preg_match('/^---\R(.*?)\R---/s', $raw, $fm) && preg_match('/^name:\s*"(.+)"$/m', $fm[1], $nm)) {
            $name = trim($nm[1]);
        }
        if ($name === '' && preg_match('/^#\s+(.+)$/m', $raw, $m)) {
            $name = $m[1];
        }
        if ($name === '') $name = basename($file, '.md');
        preg_match('/^---\R(.*?)\R---/s', $raw, $fm);
        $subcat = '';
        if ($fm && preg_match('/role:\s*(.+)/', $fm[1], $rm)) $subcat = trim($rm[1]);
        seed_insert_lore($db, $TABLE, array(
            'nombre' => $name, 'slug' => seed_slugify($name), 'categoria' => 'personajes',
            'subcategoria' => $subcat ?: 'personaje', 'resumen' => seed_extract_summary($html),
            'contenido' => $html, 'imagen' => '', 'orden' => ++$orden, 'activo' => 1,
        ));
    }
}

// ── 5. FACCIONES ──
$fac_dir = $SISTEMA_ROOT . '/one-piece-eternal-lore/worldbuilding/factions/';
if (is_dir($fac_dir)) {
    foreach (glob($fac_dir . '*.md') as $file) {
        if (basename($file) === '_index.md') continue;
        $raw = file_get_contents($file);
        $html = seed_md_to_html($raw);
        $name = '';
        if (preg_match('/^---\R(.*?)\R---/s', $raw, $fm) && preg_match('/^name:\s*"(.+)"$/m', $fm[1], $nm)) {
            $name = trim($nm[1]);
        }
        if ($name === '' && preg_match('/^#\s+(.+)$/m', $raw, $m)) $name = $m[1];
        if ($name === '') $name = basename($file, '.md');
        preg_match('/^---\R(.*?)\R---/s', $raw, $fm);
        $subcat = '';
        if ($fm && preg_match('/type:\s*(.+)/', $fm[1], $rm)) $subcat = trim($rm[1]);
        seed_insert_lore($db, $TABLE, array(
            'nombre' => $name, 'slug' => seed_slugify($name), 'categoria' => 'facciones',
            'subcategoria' => $subcat ?: 'facción', 'resumen' => seed_extract_summary($html),
            'contenido' => $html, 'imagen' => '', 'orden' => ++$orden, 'activo' => 1,
        ));
    }
}

// ── 6. UBICACIONES ──
$loc_dir = $SISTEMA_ROOT . '/one-piece-eternal-lore/worldbuilding/locations/';
if (is_dir($loc_dir)) {
    foreach (glob($loc_dir . '*.md') as $file) {
        if (basename($file) === '_index.md') continue;
        $raw = file_get_contents($file);
        $html = seed_md_to_html($raw);
        $name = '';
        if (preg_match('/^---\R(.*?)\R---/s', $raw, $fm) && preg_match('/^name:\s*"(.+)"$/m', $fm[1], $nm)) {
            $name = trim($nm[1]);
        }
        if ($name === '' && preg_match('/^#\s+(.+)$/m', $raw, $m)) $name = $m[1];
        if ($name === '') $name = basename($file, '.md');
        preg_match('/^---\R(.*?)\R---/s', $raw, $fm);
        $subcat = '';
        if ($fm && preg_match('/region:\s*(.+)/', $fm[1], $rm)) $subcat = trim($rm[1]);
        seed_insert_lore($db, $TABLE, array(
            'nombre' => $name, 'slug' => seed_slugify($name), 'categoria' => 'ubicaciones',
            'subcategoria' => $subcat ?: 'ubicacion', 'resumen' => seed_extract_summary($html),
            'contenido' => $html, 'imagen' => '', 'orden' => ++$orden, 'activo' => 1,
        ));
    }
}

// ── 7. SISTEMAS ──
$sys_dir = $SISTEMA_ROOT . '/one-piece-eternal-lore/worldbuilding/systems/';
if (is_dir($sys_dir)) {
    foreach (glob($sys_dir . '*.md') as $file) {
        if (basename($file) === '_index.md') continue;
        $raw = file_get_contents($file);
        $html = seed_md_to_html($raw);
        $name = '';
        if (preg_match('/^---\R(.*?)\R---/s', $raw, $fm) && preg_match('/^name:\s*"(.+)"$/m', $fm[1], $nm)) {
            $name = trim($nm[1]);
        }
        if ($name === '' && preg_match('/^#\s+(.+)$/m', $raw, $m)) $name = $m[1];
        if ($name === '') $name = basename($file, '.md');
        preg_match('/^---\R(.*?)\R---/s', $raw, $fm);
        $subcat = '';
        if ($fm && preg_match('/type:\s*(.+)/', $fm[1], $rm)) $subcat = trim($rm[1]);
        seed_insert_lore($db, $TABLE, array(
            'nombre' => $name, 'slug' => seed_slugify($name), 'categoria' => 'sistemas',
            'subcategoria' => $subcat ?: 'sistema-de-poder', 'resumen' => seed_extract_summary($html),
            'contenido' => $html, 'imagen' => '', 'orden' => ++$orden, 'activo' => 1,
        ));
    }
}

// ── Resumen ──
$count = $db->query("SELECT COUNT(*) AS c FROM `{$TABLE}`");
$row = $count->fetch_assoc();
echo "\n=== DONE ===\n";
echo "Total de artículos insertados: " . (int)$row['c'] . "\n\nPor categoría:\n";
$cat_counts = $db->query("SELECT categoria, COUNT(*) AS c FROM `{$TABLE}` GROUP BY categoria ORDER BY categoria");
while ($r = $cat_counts->fetch_assoc()) {
    echo "  {$r['categoria']}: {$r['c']}\n";
}

$db->close();
