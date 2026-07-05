<?php
/**
 * Export the I-Forge RPG child theme to XML for version control.
 * Run: php scripts/export-theme.php
 * Ouput: docs/themes/iforge-child-theme.xml
 */

define('THEME_TID', 8);
define('TEMPLATESET_SID', 6);
define('OUTPUT', __DIR__ . '/../docs/themes/iforge-child-theme.xml');

$db = new mysqli('127.0.0.1', 'root', '', 'mybb_foro');
if ($db->connect_error) {
    die("Error de conexión: " . $db->connect_error . "\n");
}

// ─── Theme properties ───
$result = $db->query("SELECT name, pid, def, properties FROM mybb_themes WHERE tid = " . THEME_TID);
$theme = $result->fetch_assoc();
if (!$theme) {
    die("Theme tid=" . THEME_TID . " no encontrado\n");
}

// ─── Templates ───
$templates = $db->query("SELECT title, template, version FROM mybb_templates WHERE sid = " . TEMPLATESET_SID . " ORDER BY title");

// ─── Stylesheets ───
$stylesheets = $db->query("SELECT name, stylesheet, attachedto FROM mybb_themestylesheets WHERE tid = " . THEME_TID . " ORDER BY name");

// ─── Build XML ───
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$themeEl = $xml->createElement('theme');
$themeEl->setAttribute('name', $theme['name']);
$themeEl->setAttribute('version', '1839');

// Properties
$props = $xml->createElement('properties');
$propData = unserialize($theme['properties']);
foreach ($propData as $key => $value) {
    if ($key === 'inherited') continue;
    if (is_array($value)) {
        $sub = $xml->createElement($key);
        foreach ($value as $k => $v) {
            $el = $xml->createElement($k, $v);
            $sub->appendChild($el);
        }
        $props->appendChild($sub);
    } else {
        $el = $xml->createElement($key, $value);
        $props->appendChild($el);
    }
}
$themeEl->appendChild($props);

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
