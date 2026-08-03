<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';

ob_start();
try {
    include __DIR__ . '/../biblioteca-lore.php';
    $out = ob_get_clean();
    echo "SUCCESS! Rendered " . strlen($out) . " bytes for biblioteca-lore.php.\n";
    echo "=== VERIFICACIONES DE RENDERIZADO ===\n";
    echo "Sigrun D. Basterra: " . (strpos($out, 'Sigrun D. Basterra') !== false ? 'YES' : 'NO') . "\n";
    echo "Vaelen: " . (strpos($out, 'Vaelen') !== false ? 'YES' : 'NO') . "\n";
    echo "7 capítulos en navbar: " . (substr_count($out, 'guide-nav-item') === 7 ? 'YES (7)' : 'NO (' . substr_count($out, 'guide-nav-item') . ')') . "\n";
    echo "Capítulo IV (Gran Reino): " . (strpos($out, 'El Gran Reino y los Poneglyphs') !== false ? 'YES' : 'NO') . "\n";
    echo "Capítulo V (Facciones): " . (strpos($out, 'Las Facciones del Poder Mundial') !== false ? 'YES' : 'NO') . "\n";
    echo "Capítulo VI (Yonkou): " . (strpos($out, 'Los Señores del Nuevo Mundo') !== false ? 'YES' : 'NO') . "\n";
    echo "Capítulo VII (Equilibrio): " . (strpos($out, 'El Equilibrio y el Futuro') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Vaelgor: " . (strpos($out, 'Kaiser Vaelgor') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Brogaz: " . (strpos($out, 'Jarl Brogaz') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Rosette: " . (strpos($out, 'Princesa Rosette') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Sylphira: " . (strpos($out, 'Sylphira') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Valerius: " . (strpos($out, 'Valerius') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Morgana: " . (strpos($out, 'Morgana') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Cassandra: " . (strpos($out, 'Casandra') !== false || strpos($out, 'Cassandra') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Gideon: " . (strpos($out, 'Gideon') !== false ? 'YES' : 'NO') . "\n";
    echo "NPC link Kaelen: " . (strpos($out, 'Kaelen') !== false ? 'YES' : 'NO') . "\n";
    echo "=== FIN ===\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "FATAL EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
