<?php
/**
 * Seed: Misiones de ejemplo S1-S3 (East Blue) para el Tablon.
 * ---------------------------------------------------------
 * Basadas en MUNDO-VIVO.md §2.2. Cursores: staff (uid 1).
 * Idempotente: no duplica si ya existen (marca por titulo).
 *
 * Ejecutar: php scripts/seed-misiones-ejemplo.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!defined('IN_MYBB')) {
    define('IN_MYBB', 1);
}
require __DIR__ . '/../global.php';
require MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$uid_autor = 1;
$semillas = array(
    array(
        'titulo'            => 'El legajo del contrabandista',
        'resumen'           => 'Un anciano moribundo te confia un mapa arrugado en Isla Dawn. Los Marines no deben verlo. (S1)',
        'descripcion_larga' => "El mapa lleva a un almacen sellado con los bienes de un noble caido. La Teniente Kairi Himura investiga el caso y Donny el Tuerto, informante del puerto, podria tener pistas. El legajo es moneda de cambio demasiado valiosa: decidir si devolverlo, saquearlo o usarlo para negociar.",
        'zona_slug'         => 'isla_dawn',
        'facciones'         => 'Marina',
        'recompensa'        => '800-1200 Berries, 1 material raro, +10 renombre',
        'rango'             => 'C',
        'peligrosidad'      => 2,
        'modalidad'         => 'grupo',
    ),
    array(
        'titulo'            => 'La cancion de la sirena',
        'resumen'           => 'En Loguetown, pescadores reportan una figura cantando en los acantilados. Marineros han desaparecido. (S2)',
        'descripcion_larga' => "La figura no es una sirena: es Lyra, una nina Gyojin varada que huye de traficantes. Gunter, mercader de esclavos, la persigue. Protegerla, entregarla o usarla para negociar cambiara el rumbo de la zona.",
        'zona_slug'         => 'loguetown',
        'facciones'         => 'Traficantes',
        'recompensa'        => '1000 Berries, +15 renombre, posible contacto Gyojin',
        'rango'             => 'C',
        'peligrosidad'      => 3,
        'modalidad'        => 'solo',
    ),
    array(
        'titulo'            => 'El acero de la montana',
        'resumen'           => 'Un herrero enloquecido forja armas con un metal que absorbe el sonido en una isla del East Blue. (S3)',
        'descripcion_larga' => "Malstrom, herrero y antiguo cientifico de MADS, usa kairoseki de contrabando. El Capitan Aldrich Drakos lo quiere para la Marina. Las rutas estan envenenadas: detenerlo, aliarse con el o robar el mineral son las vias posibles.",
        'zona_slug'         => 'isla_gecko',
        'facciones'         => 'Marina, MADS',
        'recompensa'        => '1200 Berries, posible Tag de arma con kairoseki, +20 renombre',
        'rango'             => 'B',
        'peligrosidad'      => 4,
        'modalidad'         => 'grupo',
    ),
);

$insertadas = 0;
$existentes = 0;
foreach ($semillas as $s) {
    $existe = $db->fetch_array($db->simple_select('rol_misiones', 'mision_id', "titulo = '" . $db->escape_string($s['titulo']) . "'", array('limit' => 1)));
    if ($existe) {
        echo "  [=] ya existe: {$s['titulo']}\n";
        $existentes++;
        continue;
    }
    $r = ope_mision_crear($uid_autor, $s);
    if ($r['ok']) {
        echo "  [OK] {$s['titulo']} (id {$r['id']})\n";
        $insertadas++;
    } else {
        echo "  [FAIL] {$s['titulo']}: {$r['msg']}\n";
    }
}

echo "\nInsertadas: {$insertadas} | existentes: {$existentes}\n";
echo "DONE\n";