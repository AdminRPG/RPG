<?php
/**
 * One Piece: Eternal · Renderizador HTML del panel de Mision (showthread).
 *
 * Diseño oscuro integrado con el tema del foro (azul marino / tierra / oro).
 * Sin blanco/crema. Usa las mismas variables CSS que el resto del foro.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_mision_post_html(array $toma, array $oraculo)
{
    $titulo     = htmlspecialchars_uni((string)($toma['mision_titulo'] ?? 'Mision desconocida'));
    $zona       = htmlspecialchars_uni((string)($toma['mision_zona'] ?? ''));
    $pj_nombre  = htmlspecialchars_uni((string)($toma['pj_nombre'] ?? ''));
    $rango      = htmlspecialchars_uni((string)($toma['rango'] ?? 'D'));
    $pelig      = (int)($toma['peligrosidad'] ?? 1);

    $map = array(
        'entorno'       => array('cls' => 'entorno',      'label' => 'Entorno'),
        'encuentro'     => array('cls' => 'encuentro',    'label' => 'Encuentro'),
        'aliado'        => array('cls' => 'aliado',       'label' => 'Aliado'),
        'complicacion'  => array('cls' => 'complicacion', 'label' => 'Complicacion'),
        'revelacion'    => array('cls' => 'revelacion',   'label' => 'Revelacion'),
    );

    $peligro_barras = '';
    for ($i = 1; $i <= 5; $i++) {
        $on = $i <= $pelig ? ' on' : '';
        $peligro_barras .= '<span class="om-bar' . $on . '"></span>';
    }

    $cartas_html = '';
    foreach ($map as $key => $meta) {
        if (empty($oraculo['cartas'][$key])) continue;
        $c = $oraculo['cartas'][$key];
        $nom     = htmlspecialchars_uni((string)($c['nombre'] ?? '—'));
        $efe     = htmlspecialchars_uni((string)($c['efecto'] ?? ''));
        $tone    = htmlspecialchars_uni((string)($c['tone'] ?? 'neutral'));
        $roll    = (int)($c['roll_adj'] ?? $c['roll'] ?? 0);

        $cartas_html .= '<div class="omc-col"><div class="omc omc--' . $tone . '">';
        $cartas_html .= '<div class="omc-label">' . $meta['label'] . '</div>';
        $cartas_html .= '<div class="omc-name">' . $nom . '</div>';
        if ($efe !== '') {
            $cartas_html .= '<div class="omc-fx">' . $efe . '</div>';
        }
        $cartas_html .= '<div class="omc-roll">' . $roll . '</div>';
        $cartas_html .= '</div></div>';
    }

    $nar = htmlspecialchars_uni((string)($oraculo['narrativa'] ?? ''));
    $nar_html = '';
    if ($nar !== '') {
        $nar_html = '<div class="om-narrativa"><p>' . $nar . '</p></div>';
    }

    $html  = '<div class="ope-mision-oraculo">';
    $html .= '<header class="om-head">';
    $html .= '<div class="om-kicker">Mision del Tablon</div>';
    $html .= '<h2 class="om-titulo">' . $titulo . '</h2>';

    $html .= '<div class="om-meta">';
    if ($zona !== '') {
        $html .= '<span class="om-meta-item"><span class="om-meta-k">Isla</span><span class="om-meta-v">' . $zona . '</span></span>';
    }
    $html .= '<span class="om-meta-item"><span class="om-meta-k">Rango</span><span class="om-meta-v om-rango">' . $rango . '</span></span>';
    $html .= '<span class="om-meta-item"><span class="om-meta-k">Peligro</span><span class="om-peligro">' . $peligro_barras . '</span></span>';
    $html .= '</div>';

    if ($pj_nombre !== '') {
        $html .= '<div class="om-aventurero"><span class="om-meta-k">Aventurero</span><span class="om-av-name">' . $pj_nombre . '</span></div>';
    }
    $html .= '</header>';

    // Introduccion generada por IA
    $intro = trim((string)($toma['introduccion_api'] ?? ''));
    if ($intro !== '') {
        $parrafos = preg_split('/\R{2,}/', $intro);
        $intro_html = '';
        foreach ($parrafos as $p) {
            $p = trim($p);
            if ($p !== '') {
                $intro_html .= '<p>' . htmlspecialchars_uni($p) . '</p>';
            }
        }
        if ($intro_html !== '') {
            $html .= '<div class="om-intro">' . $intro_html . '</div>';
        }
    }

    $html .= '<div class="om-grid">' . $cartas_html . '</div>';
    $html .= $nar_html;

    $html .= '<footer class="om-foot">';
    $html .= '<p>Rolea la mision en este hilo. El staff la cerrara al terminar.</p>';
    $html .= '</footer></div>';

    return $html;
}
