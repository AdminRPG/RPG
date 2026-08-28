<?php
/**
 * Helper temporal (F6.4): flipea las referencias del front a las tablas
 * espejo mybb_ope_* (mismo esquema). Solo tablas de la lista MIRROR — las
 * que tienen equivalente real ope_* distinto se migran a mano.
 */
$map = array(
    'rol_alertas'                 => 'ope_alertas',
    'rol_mensajes'                => 'ope_mensajes',
    'rol_cronologia'              => 'ope_cronologia',
    'rol_relaciones'              => 'ope_relaciones',
    'rol_post_templates'          => 'ope_post_templates',
    'rol_thread_meta'             => 'ope_thread_meta',
    'rol_estilos'                 => 'ope_estilos',
    'rol_lore'                    => 'ope_lore',
    'rol_npcs_secundarios'        => 'ope_npcs_secundarios',
    'rol_acompanantes'            => 'ope_acompanantes',
    'rol_acompanante_solicitudes' => 'ope_acompanante_solicitudes',
    'rol_mv_noticias'             => 'ope_mv_noticias',
    'rol_mv_mision_asignaciones'  => 'ope_mv_mision_asignaciones',
    'rol_pp_saldo'                => 'ope_pp_saldo',
    'rol_pp_log'                  => 'ope_pp_log',
    'rol_pj_vocaciones'           => 'ope_pj_vocaciones',
);

$files = array_merge(
    glob('*.php'),
    glob('inc/ope_rol/*.php'),
    glob('inc/ope_rol/*/*.php'),
    glob('inc/plugins/*.php')
);
foreach ($files as $f) {
    $src = file_get_contents($f);
    $new = strtr($src, $map);
    if ($new !== $src) {
        file_put_contents($f, $new);
        echo "  flipeado: {$f}\n";
    }
}
echo "OK\n";
