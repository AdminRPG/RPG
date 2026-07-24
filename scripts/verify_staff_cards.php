<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$pref = TABLE_PREFIX;
$sql = "SELECT p.*, u.username AS owner_name, ak.nombre AS fruta_nombre, f.fruta_id
        FROM {$pref}rol_personajes p
        LEFT JOIN {$pref}users u ON u.uid = p.uid
        LEFT JOIN {$pref}rol_pj_fruta f ON f.pid = p.pid
        LEFT JOIN {$pref}rol_akuma ak ON ak.id = f.fruta_id
        WHERE p.es_npc = 1
        ORDER BY p.pid ASC";
$q = $db->query($sql);

echo "=== VERIFICACIÓN TARJETAS STAFF (NPCS) ===\n\n";
while ($pj = $db->fetch_array($q)) {
    $datos = json_decode((string) $pj['datos'], true) ?: array();
    $fac_slug_raw = trim((string) ($pj['faccion_slug'] ?? ''));
    if ($fac_slug_raw === '') {
        $fac_slug_raw = function_exists('ope_rol_faccion_slug') ? ope_rol_faccion_slug($datos['faccion'] ?? '') : '';
    }
    if ($fac_slug_raw === '' && !empty($pj['rango_faccion'])) {
        $fac_label_disp = (string) $pj['rango_faccion'];
    } else {
        $f_map = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
        $fac_label_disp = isset($f_map[$fac_slug_raw]) ? $f_map[$fac_slug_raw]['nombre'] : ($fac_slug_raw !== '' ? ucfirst($fac_slug_raw) : '—');
    }

    echo "PID #{$pj['pid']}: {$pj['nombre']}\n";
    echo "  - Nivel: {$pj['nivel']}\n";
    echo "  - Facción Renderizada: {$fac_label_disp} (Raw: '{$datos['faccion']}')\n";
    echo "  - Fruta Renderizada: " . ($pj['fruta_nombre'] ?: 'Sin Fruta') . "\n";
    
    // Eternal picks
    $eq = $db->simple_select('rol_pj_eternal', 'arbol, nodo_id', "pid = {$pj['pid']}");
    $nodos = array();
    while ($er = $db->fetch_array($eq)) { $nodos[] = $er['nodo_id']; }
    echo "  - Total Nodos Eternal: " . count($nodos) . "\n";
    echo "  - Longitud Desc. Física: " . strlen($pj['desc_fisica']) . " chars\n";
    echo "  - Longitud Personalidad: " . strlen($pj['personalidad']) . " chars\n";
    echo "\n";
}
