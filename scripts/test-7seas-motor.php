<?php
/**
 * One Piece: 7 Seas · Test de integración del motor de trámites (F0)
 * ------------------------------------------------------------------
 * Verifica: catálogo de 67 · creación de trámite ligero (auto) e IA (prompt) ·
 * guardado de resultado · firma con motivo · histórico auditable ·
 * posteo de prueba del bot «OPE Eternal».
 * Los trámites de prueba se limpian al final; el post del bot se conserva (hito F0).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'test-7seas-motor.php');
require_once __DIR__ . '/../global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

echo "=== Test motor de trámites 7 Seas ===\n";

// 1) Catálogo de 67
$cat = ope7_tramites_catalogo();
echo "[1] Catálogo: " . count($cat) . " trámites (esperado 67)\n";
$naturaleza = ope7_tramite_resumen_catalogo();
echo "    Naturaleza: IA={$naturaleza['ia']} staff={$naturaleza['staff']} hito={$naturaleza['hito']} ligero={$naturaleza['ligero']} "
     . "(automáticos: " . implode(',', $naturaleza['automaticos']) . ")\n";

// 2) Trámite ligero (nº 1 · apertura de tema) → se publica al instante
$r1 = ope7_tramite_crear(1, 0, 1, '', array('tema_id' => 0));
echo "[2] Ligero nº1: " . ($r1['ok'] ? 'OK' : 'ERROR') . " — " . $r1['msg'] . "\n";
if ($r1['ok']) {
    $t1 = ope7_tramite_get((int) $r1['tid']);
    echo "    estado: " . $t1['estado'] . " (esperado publicado)\n";
}

// 3) Trámite IA (nº 13 · creación de técnica) → prompt_listo con prompt
$r2 = ope7_tramite_crear(1, 0, 13, 'Quiero una técnica de fuego para mi espadachín', array('personaje_id' => 0), array(
    'idea' => 'Patada giratoria que incendia la pierna con cada giro',
    'tier' => 2,
));
echo "[3] IA nº13: " . ($r2['ok'] ? 'OK' : 'ERROR') . " — " . $r2['msg'] . "\n";
$tid = (int) ($r2['tid'] ?? 0);
if ($tid > 0) {
    $t2 = ope7_tramite_get($tid);
    echo "    estado: " . $t2['estado'] . " (esperado prompt_listo)\n";
    echo "    skill: " . $t2['skill'] . "\n";
    echo "    prompt (primeras 120): " . mb_substr((string) $t2['prompt'], 0, 120) . "…\n";

    // 4) Guardar resultado (editable) + firmar con motivo
    $res = ope7_tramite_guardar_resultado($tid, array('ficha' => 'Golpe de llama giratorio', 'tier' => 2, 'coste_pp' => 120, 'pa' => 4));
    echo "[4] Resultado: " . ($res['ok'] ? 'OK' : 'ERROR') . "\n";
    $fir = ope7_tramite_firmar($tid, 1, 'publicar', 'Ficha validada contra 5.7: efectos dentro del presupuesto T2.');
    echo "[5] Firma: " . ($fir['ok'] ? 'OK' : 'ERROR') . " — " . $fir['msg'] . "\n";

    // 5) Histórico auditable
    echo "[6] Histórico del #" . $tid . ":\n";
    foreach (ope7_tramite_historico($tid) as $h) {
        echo "    - [" . $h['estado'] . "] actor=" . $h['actor_id'] . " — " . $h['motivo'] . "\n";
    }
}

// 6) Posteo de prueba del bot «OPE Eternal» (hito F0) en Dudas de sistema (fid 109)
$mensaje = "[center][b]News Coo · Aviso del sistema[/b][/center]\n\n"
    . "El periódico del mundo abre sus puertas: este es un mensaje de prueba del bot del sistema (OPE Eternal).\n\n"
    . "El motor de trámites está operativo con su catálogo de 67 trámites y la zona staff dispone de la bandeja transversal (la IA propone, el staff decide).\n\n"
    . "— La redacción";
$sys_tid = ope7_bot_post_thread(109, 'News Coo — Aviso del sistema (prueba)', $mensaje, 'aviso');
echo "[7] Bot: thread de prueba tid=" . $sys_tid . " (esperado > 0)\n";

// 7) Limpiar los trámites de prueba por id exacto (la bandeja queda pristina; el post del bot se conserva)
$borrar = array();
if (!empty($r1['ok']) && !empty($r1['tid'])) {
    $borrar[] = (int) $r1['tid'];
}
if ($tid > 0) {
    $borrar[] = $tid;
}
if (!empty($borrar)) {
    $db->delete_query(ope7_tabla('tramites'), 'id IN (' . implode(',', $borrar) . ')');
    $db->delete_query(ope7_tabla('tramites_historico'), 'tramite_id IN (' . implode(',', $borrar) . ')');
    echo "[8] Trámites de prueba limpiados.\n";
}

echo "\n=== DONE ===\n";
