<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

$query = $db->simple_select('rol_personajes', '*', 'es_npc = 1');
echo "TOTAL NPCS EN BD: " . $db->num_rows($query) . "\n\n";

while ($r = $db->fetch_array($query)) {
    echo "=========================================\n";
    echo "PID: {$r['pid']} | Nombre: {$r['nombre']} | Slug: {$r['slug']}\n";
    echo "Rango: {$r['rango']} | Nivel: {$r['nivel']} | Facción: {$r['rango_faccion']}\n";
    echo "-----------------------------------------\n";
    $datos = json_decode($r['datos'], true) ?: array();
    echo "Raza Principal: " . ($datos['raza_principal'] ?? 'N/A') . "\n";
    echo "Arbol Identidad: " . ($datos['arbol_identidad'] ?? 'N/A') . "\n";
    echo "Nodos Identidad: " . json_encode($datos['arbol_identidad_nodos'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    echo "Arbol Arma: " . ($datos['arbol_arma'] ?? 'N/A') . "\n";
    echo "Nodos Arma: " . json_encode($datos['arbol_arma_nodos'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    echo "Fruta Slug: " . ($datos['fruta_slug'] ?? 'N/A') . " | Fruta Nombre: " . ($datos['fruta_nombre'] ?? 'N/A') . "\n";
    echo "Factor Linaje / Rasgos: " . json_encode($datos['factor_linaje'] ?? $datos['linaje'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    echo "Virtudes: " . json_encode($datos['virtudes'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    echo "Defectos: " . json_encode($datos['defectos'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

    // Check rol_eternal_picks table
    if ($db->table_exists('rol_eternal_picks')) {
        $pq = $db->simple_select('rol_eternal_picks', '*', "pid = {$r['pid']}");
        $picks = array();
        while ($pr = $db->fetch_array($pq)) {
            $picks[] = $pr['arbol'] . ':' . $pr['nodo_id'];
        }
        echo "Picks en BD (rol_eternal_picks): " . json_encode($picks) . "\n";
    }

    // Check rol_frutas / rol_haki if existing
    if ($db->table_exists('rol_frutas_personaje')) {
        $fq = $db->simple_select('rol_frutas_personaje', '*', "pid = {$r['pid']}");
        if ($db->num_rows($fq)) {
            $fr = $db->fetch_array($fq);
            echo "Fruta en rol_frutas_personaje: " . json_encode($fr) . "\n";
        } else {
            echo "Fruta en rol_frutas_personaje: NINGUNA\n";
        }
    }
    echo "\n";
}
