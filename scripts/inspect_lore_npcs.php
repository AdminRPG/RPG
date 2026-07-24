<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$dir = 'C:/Users/Fgonz/Documents/Proyectos/Op-Eternal/Eternal-Lore/NPC_Mayores/';
$files = glob($dir . '*.json');

foreach ($files as $f) {
    if (basename($f) === 'plantilla_npc.json') continue;
    $raw = file_get_contents($f);
    $data = json_decode($raw, true);
    echo "=========================================\n";
    echo "FILE: " . basename($f) . "\n";
    echo "Nombre: " . ($data['nombre'] ?? 'N/A') . "\n";
    echo "Slug: " . ($data['slug'] ?? 'N/A') . "\n";
    echo "Rango: " . ($data['rango'] ?? 'N/A') . "\n";
    echo "Facción / Rango Facción: " . ($data['rango_faccion'] ?? $data['datos_publicos']['ocupacion'] ?? 'N/A') . "\n";
    echo "Raza: " . ($data['datos']['raza_principal'] ?? 'N/A') . "\n";
    echo "Fruta: " . ($data['datos']['fruta_nombre'] ?? $data['datos_publicos']['fruta'] ?? 'N/A') . "\n";
    echo "Identidad: " . ($data['datos']['identidad'] ?? 'N/A') . " | Árbol: " . ($data['datos']['arbol_identidad'] ?? 'N/A') . "\n";
    echo "Arma: " . ($data['datos']['arma'] ?? 'N/A') . " | Árbol Arma: " . ($data['datos']['arbol_arma'] ?? 'N/A') . "\n";
    echo "Concepto: " . ($data['datos']['concepto'] ?? $data['datos_publicos']['descripcion'] ?? 'N/A') . "\n";
    echo "\n";
}
