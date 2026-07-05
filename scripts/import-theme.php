<?php
/**
 * Import the I-Forge RPG theme from XML into MyBB database.
 * Run: php scripts/import-theme.php
 * Source: docs/themes/iforge-child-theme.xml
 */

define('SOURCE', __DIR__ . '/../docs/themes/iforge-child-theme.xml');

if (!file_exists(SOURCE)) {
    die("XML no encontrado: " . SOURCE . "\n");
}

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    die("Error de conexión: " . $db->connect_error . "\n");
}

$xml = simplexml_load_file(SOURCE);
if (!$xml) {
    die("Error al parsear XML\n");
}

$themeName = (string)$xml['name'];
echo "Importando tema: $themeName\n";

// ─── Find or create theme ───
$result = $db->query("SELECT tid, properties FROM mybb_themes WHERE name = 'I-Forge RPG' OR name = 'RPG' ORDER BY tid DESC LIMIT 1");
$theme = $result->fetch_assoc();

if ($theme) {
    $tid = (int)$theme['tid'];
    echo "Actualizando tema existente (tid=$tid)\n";
} else {
    // Find parent (Default)
    $r = $db->query("SELECT tid FROM mybb_themes WHERE def = 1 AND pid = 0");
    $parent = $r->fetch_assoc();
    $pid = $parent ? (int)$parent['tid'] : 1;
    
    // Create new templateset
    $db->query("INSERT INTO mybb_templatesets (title) VALUES ('I-Forge RPG')");
    $sid = $db->insert_id;
    echo "Nuevo templateset creado: sid=$sid\n";
    
    // Create theme
    $props = serialize(['templateset' => $sid, 'imgdir' => 'images', 'logo' => 'images/logo.png', 'tablespace' => 5, 'borderwidth' => 0, 'editortheme' => 'mybb.css']);
    $stmt = $db->prepare("INSERT INTO mybb_themes (name, pid, def, properties, stylesheets, allowedgroups) VALUES ('I-Forge RPG', ?, 1, ?, '', 'all')");
    $stmt->bind_param('is', $pid, $props);
    $stmt->execute();
    $tid = $stmt->insert_id;
    $stmt->close();
    echo "Nuevo tema creado: tid=$tid\n";
}

// ─── Import stylesheets ───
if ($xml->stylesheets && $xml->stylesheets->stylesheet) {
    foreach ($xml->stylesheets->stylesheet as $sheet) {
        $name = (string)$sheet['name'];
        $css = (string)$sheet;
        
        $stmt = $db->prepare("SELECT sid FROM mybb_themestylesheets WHERE tid = ? AND name = ?");
        $stmt->bind_param('is', $tid, $name);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $up = $db->prepare("UPDATE mybb_themestylesheets SET stylesheet = ?, lastmodified = ? WHERE tid = ? AND name = ?");
            $now = time();
            $up->bind_param('siis', $css, $now, $tid, $name);
            $up->execute();
            echo "  CSS actualizado: $name\n";
            $up->close();
        } else {
            $stmt->close();
            $in = $db->prepare("INSERT INTO mybb_themestylesheets (name, tid, stylesheet, attachedto, lastmodified) VALUES (?, ?, ?, '', ?)");
            $now = time();
            $in->bind_param('sisi', $name, $tid, $css, $now);
            $in->execute();
            echo "  CSS insertado: $name\n";
            $in->close();
        }
    }
}

// ─── Import templates ───
$templateset = 0;
$r = $db->query("SELECT properties FROM mybb_themes WHERE tid = $tid");
$t = $r->fetch_assoc();
if ($t) {
    $p = @unserialize($t['properties']);
    $templateset = (int)($p['templateset'] ?? 0);
}

if ($templateset > 0 && $xml->templates && $xml->templates->template) {
    foreach ($xml->templates->template as $tpl) {
        $title = (string)$tpl['name'];
        $content = (string)$tpl;
        $version = (string)($tpl['version'] ?? '1839');
        
        $stmt = $db->prepare("SELECT tid FROM mybb_templates WHERE title = ? AND sid = ?");
        $stmt->bind_param('si', $title, $templateset);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            $up = $db->prepare("UPDATE mybb_templates SET template = ?, version = ? WHERE title = ? AND sid = ?");
            $up->bind_param('sssi', $content, $version, $title, $templateset);
            $up->execute();
            echo "  Template actualizado: $title\n";
            $up->close();
        } else {
            $stmt->close();
            $in = $db->prepare("INSERT INTO mybb_templates (title, template, sid, version, status, dateline) VALUES (?, ?, ?, ?, 1, ?)");
            $now = time();
            $in->bind_param('ssisi', $title, $content, $templateset, $version, $now);
            $in->execute();
            echo "  Template insertado: $title\n";
            $in->close();
        }
    }
}

// ─── Clear caches ───
$db->query("UPDATE mybb_datacache SET cache = '' WHERE title IN ('themes', 'themestylesheets', 'templates', 'default_theme')");
echo "Cache limpiada.\n";

echo "\nImportación completa. Recarga la página para ver los cambios.\n";
$db->close();
