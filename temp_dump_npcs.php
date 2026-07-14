<?php
define('IN_MYBB', 1);
require_once './global.php';

$query = $db->simple_select('rol_personajes', 'pid, nombre, es_npc, rango, nivel, datos_publicos, datos_internos', 'es_npc = 1');
$report = "";
while ($row = $db->fetch_array($query)) {
    $report .= "=========================================\n";
    $report .= "NPC: {$row['nombre']} (PID: {$row['pid']})\n";
    $report .= "=========================================\n";
    $dp = json_decode($row['datos_publicos'], true);
    $di = json_decode($row['datos_internos'], true);
    
    $report .= "--- DATOS PÚBLICOS ---\n";
    $report .= "Título: " . ($dp['titulo'] ?? 'N/A') . "\n";
    $report .= "Recompensa: " . ($dp['recompensa'] ?? 'N/A') . "\n";
    $report .= "Ubicación Pública: " . ($dp['ubicacion_publica'] ?? 'N/A') . "\n";
    $report .= "Ocupación: " . ($dp['ocupacion'] ?? 'N/A') . "\n";
    $report .= "Lema: " . ($dp['lema'] ?? 'N/A') . "\n";
    $report .= "Descripción:\n" . ($dp['descripcion'] ?? 'N/A') . "\n\n";
    $report .= "Personalidad Pública:\n" . ($dp['personalidad_publica'] ?? 'N/A') . "\n\n";
    
    $report .= "--- DATOS INTERNOS ---\n";
    $report .= "Personalidad (Ejes): " . json_encode($di['personalidad'] ?? [], JSON_UNESCAPED_UNICODE) . "\n";
    $report .= "Personalidad Detallada:\n" . ($di['personalidad_detallada'] ?? 'N/A') . "\n\n";
    $report .= "Metas:\n";
    if (isset($di['metas']) && is_array($di['metas'])) {
        foreach ($di['metas'] as $m) {
            $report .= "- " . (is_array($m) ? json_encode($m, JSON_UNESCAPED_UNICODE) : $m) . "\n";
        }
    } else {
        $report .= "N/A\n";
    }
    $report .= "\n";
}

file_put_contents('C:\Users\Fgonz\.gemini\antigravity-ide\brain\21524eee-c9e4-4ec5-a1b8-9363e512c0ac\dump_actual_npcs.txt', $report);
echo "DUMP COMPLETADO EN dump_actual_npcs.txt\n";
