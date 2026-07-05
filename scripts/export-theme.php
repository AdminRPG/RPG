<?php
/**
 * Export the I-Forge RPG theme to XML for version control.
 * Run: php scripts/export-theme.php
 * Output: docs/themes/iforge-child-theme.xml
 */

define('OUTPUT', __DIR__ . '/../docs/themes/iforge-child-theme.xml');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    die("Error de conexión: " . $db->connect_error . "\n");
}

// ─── Auto-detect active child theme ───
$result = $db->query("SELECT tid, name, pid, def, properties FROM mybb_themes WHERE def = 1 ORDER BY tid DESC LIMIT 1");
$theme = $result->fetch_assoc();
if (!$theme) {
    die("No se encontró tema activo por defecto\n");
}
$theme_tid = (int)$theme['tid'];
echo "Exportando tema activo: tid=$theme_tid ({$theme['name']})\n";

// ─── Templates ───
$props = @unserialize($theme['properties']);
$templateset = (int)($props['templateset'] ?? 1);
$templates = $db->query("SELECT title, template, version FROM mybb_templates WHERE sid = $templateset ORDER BY title");

// ─── Stylesheets ───
$stylesheets = $db->query("SELECT name, stylesheet, attachedto FROM mybb_themestylesheets WHERE tid = $theme_tid ORDER BY name");

// ─── Build XML ───
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$themeEl = $xml->createElement('theme');
$themeEl->setAttribute('name', 'I-Forge RPG');
$themeEl->setAttribute('version', '1839');

// Properties
$propsEl = $xml->createElement('properties');
if (is_array($props)) {
    foreach ($props as $key => $value) {
        if ($key === 'inherited') continue;
        if (is_array($value)) {
            $sub = $xml->createElement($key);
            foreach ($value as $k => $v) {
                $el = $xml->createElement($k, $v);
                $sub->appendChild($el);
            }
            $propsEl->appendChild($sub);
        } else {
            $el = $xml->createElement($key, $value);
            $propsEl->appendChild($el);
        }
    }
}
$themeEl->appendChild($propsEl);

// Stylesheets
$sheetsEl = $xml->createElement('stylesheets');
while ($sheet = $stylesheets->fetch_assoc()) {
    $s = $xml->createElement('stylesheet');
    $s->setAttribute('name', $sheet['name']);
    $s->setAttribute('version', '1839');
    $cdata = $xml->createCDATASection($sheet['stylesheet']);
    $s->appendChild($cdata);
    $sheetsEl->appendChild($s);
}
$themeEl->appendChild($sheetsEl);

// Templates
$tempsEl = $xml->createElement('templates');
while ($tpl = $templates->fetch_assoc()) {
    $t = $xml->createElement('template');
    $t->setAttribute('name', $tpl['title']);
    $t->setAttribute('version', $tpl['version'] ?? '1823');
    $cdata = $xml->createCDATASection($tpl['template']);
    $t->appendChild($cdata);
    $tempsEl->appendChild($t);
}
$themeEl->appendChild($tempsEl);

$xml->appendChild($themeEl);
$xml->save(OUTPUT);

$db->close();

echo "Theme exportado a: " . OUTPUT . "\n";
echo "  Templates: " . $templates->num_rows . "\n";
echo "  Stylesheets: " . $stylesheets->num_rows . "\n";
