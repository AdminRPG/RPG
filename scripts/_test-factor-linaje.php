<?php
/**
 * Test CLI de la capa de reglas del Factor Linaje (suma cero PL).
 * Uso: php scripts/_test-factor-linaje.php
 * No toca BD ni MyBB: stub mínimo de ope_rol_razas().
 */
define('IN_MYBB', 1);

if (!function_exists('ope_rol_razas')) {
    function ope_rol_razas()
    {
        $ids = array('humanos', 'oni', 'gigantes', 'buccaneers', 'minks', 'gyojins', 'lunarians', 'skypeans', 'tontattas', 'merfolk');
        $out = array();
        foreach ($ids as $id) { $out[$id] = array('nombre' => ucfirst($id)); }
        return $out;
    }
}

require __DIR__ . '/../inc/ope_rol/catalogos/linaje.php';
require __DIR__ . '/../inc/ope_rol/dominio/creacion.php';

$fallos = 0;
function check($nombre, $cond)
{
    global $fallos;
    if ($cond) {
        echo "  OK   $nombre\n";
    } else {
        echo "  FAIL $nombre\n";
        $fallos++;
    }
}

// 1) Pura humano, suma 0 exacta.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'pura', 'linaje' => 'humanos',
    'rasgos_generales' => array('voluntad-hierro'),      // +2
    'rasgos_raciales'  => array('improvisar'),           // +2
    'rasgo_puro'       => 'puro-humanos',                // +3
    'defectos'         => array('agotamiento-espiritual', 'cuerpo-fragil', 'golpeador-lento', 'bocazas'), // -3-2-1-1
));
check('Pura humano suma 0 sin errores', empty($r['errores']) && $r['pl_total'] === 0);

// 2) Pura no puede comprar racial de otro linaje.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'pura', 'linaje' => 'humanos',
    'rasgos_raciales' => array('sangre-hirviente'), // oni
));
check('Pura rechaza racial ajeno', !empty($r['errores']));

// 3) Híbrido humanos+gyojins válido (canónico, no lab).
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'hibrida', 'linaje' => 'humanos', 'linaje2' => 'gyojins',
    'rasgos_raciales' => array('piel-abismo', 'improvisar'), // +3 +2
    'defectos' => array('bocazas'),                          // -1
    'defectos_hibridacion' => array('desequilibrio-celular', 'rechazo-sangre'), // -2 -2
));
check('Híbrido humanos+gyojins suma 0', empty($r['errores']) && $r['pl_total'] === 0);

// 4) Híbrido gigante+tontatta bloqueado por escala.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'hibrida', 'linaje' => 'gigantes', 'linaje2' => 'tontattas',
));
check('Híbrido gigante+tontatta bloqueado', !empty($r['errores']));

// 5) Híbrido con Mink requiere Experimento.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'hibrida', 'linaje' => 'humanos', 'linaje2' => 'minks',
    'defectos_hibridacion' => array('desequilibrio-celular', 'rechazo-sangre'),
));
check('Híbrido con Mink exige Experimento', in_array('Esta mezcla (Laboratorio/Anomalía) requiere el rasgo "Experimento / Anomalía".', $r['errores'], true));

// 6) Híbrido sin defecto de hibridación suficiente.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'hibrida', 'linaje' => 'humanos', 'linaje2' => 'gyojins',
));
check('Híbrido exige >= -2 hibridación', !empty($r['errores']));

// 7) Rasgo con spec obligatorio sin detalle -> error.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'pura', 'linaje' => 'humanos',
    'rasgos_generales' => array('piel-curtida'), // spec obligatorio
    'defectos' => array('cuerpo-fragil'),
));
check('Spec obligatorio detectado', !empty($r['errores']));

// 8) Suma distinta de 0 -> error.
$r = ope_pj_validar_factor_linaje(array(
    'pureza' => 'pura', 'linaje' => 'humanos',
    'rasgos_generales' => array('voluntad-hierro'), // +2, sin defectos
));
check('Desbalance detectado', !empty($r['errores']) && $r['pl_total'] === 2);

echo "\n" . ($fallos === 0 ? "TODOS OK" : "$fallos FALLOS") . "\n";
exit($fallos === 0 ? 0 : 1);
