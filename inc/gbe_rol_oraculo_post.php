<?php
/**
 * I-Forge · Generador HTML del post automático del Oráculo de Viaje (GBEternal).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function gbe_oraculo_post_html(array $viaje, array $oraculo)
{
    $origen  = htmlspecialchars_uni((string) ($viaje['origen_nombre'] ?? ''));
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));
    $barco   = htmlspecialchars_uni((string) ($viaje['barco_nombre'] ?? 'Sin nombre'));
    $tramos  = (int) ($viaje['tramos'] ?? 1);
    $posts   = (int) ($viaje['posts_min'] ?? 6);
    $plazo   = (int) ($viaje['plazo_dias'] ?? 5);

    $cal = function_exists('gbe_rol_onrol_calendar') ? gbe_rol_onrol_calendar() : array('season' => '', 'day' => 0);
    $estacion = htmlspecialchars_uni((string) ($cal['season'] ?? ''));
    $dia      = (int) ($cal['day'] ?? 0);

    $trip_html = '';
    $trip = json_decode((string) ($viaje['tripulantes_json'] ?? '[]'), true);
    if (is_array($trip)) {
        foreach ($trip as $t) {
            $nom = htmlspecialchars_uni((string) ($t['nombre'] ?? ''));
            $ofi = htmlspecialchars_uni((string) ($t['oficio'] ?? 'tripulante'));
            $trip_html .= '<span class="gbe-vo-trip">' . $nom . ' <small>(' . $ofi . ')</small></span>';
        }
    }

    $oficios_html = '';
    $mods = is_array($oraculo['mods'] ?? null) ? $oraculo['mods'] : array();
    $labels = array('clima' => 'Clima', 'encuentros' => 'Encuentros', 'hallazgos' => 'Hallazgos', 'peligros' => 'Peligros');
    foreach ($labels as $k => $lbl) {
        $v = (int) ($mods[$k] ?? 0);
        $sign = $v > 0 ? '+' : '';
        $oficios_html .= '<div class="gbe-vo-oficio"><span class="gbe-vo-oficio-l">' . $lbl . '</span>'
                       . '<span class="gbe-vo-oficio-v">' . $sign . $v . '</span></div>';
    }

    $tramos_html = '';
    foreach ($oraculo['tramos'] ?? array() as $tr) {
        $n = (int) ($tr['num'] ?? 1);
        $tramos_html .= '<section class="gbe-vo-tramo" data-tramo="' . $n . '">';
        $tramos_html .= '<h3 class="gbe-vo-tramo-titulo">Tramo ' . $n . '</h3>';
        $tramos_html .= '<div class="gbe-vo-grid">';
        $map = array(
            'clima'      => array('cls' => 'clima', 'label' => 'Clima'),
            'encuentros' => array('cls' => 'encuentro', 'label' => 'Encuentro'),
            'hallazgos'  => array('cls' => 'hallazgo', 'label' => 'Hallazgo'),
            'peligros'   => array('cls' => 'peligro', 'label' => 'Peligro'),
        );
        foreach ($map as $key => $meta) {
            $c = $tr['cartas'][$key] ?? array();
            $icon = htmlspecialchars_uni((string) ($c['icon'] ?? '?'));
            $nom  = htmlspecialchars_uni((string) ($c['nombre'] ?? '—'));
            $efe  = htmlspecialchars_uni((string) ($c['efecto'] ?? ''));
            $tone = preg_replace('/[^a-z]/', '', (string) ($c['tone'] ?? 'neutral'));
            $tramos_html .= '<div class="gbe-vo-card gbe-vo-' . $meta['cls'] . ' tone-' . $tone . '">';
            $tramos_html .= '<div class="gbe-vo-card-icon">' . $icon . '</div>';
            $tramos_html .= '<div class="gbe-vo-card-label">' . $meta['label'] . '</div>';
            $tramos_html .= '<div class="gbe-vo-card-valor">' . $nom . '</div>';
            if ($efe !== '') {
                $tramos_html .= '<div class="gbe-vo-card-efecto">' . $efe . '</div>';
            }
            $tramos_html .= '<div class="gbe-vo-card-roll">D100: ' . (int) ($c['roll_adj'] ?? 0) . '</div>';
            $tramos_html .= '</div>';
        }
        $tramos_html .= '</div>';
        $nar = htmlspecialchars_uni((string) ($tr['narrativa'] ?? ''));
        $tramos_html .= '<div class="gbe-vo-narrativa"><p>' . $nar . '</p></div>';
        $tramos_html .= '</section>';
    }

    $html  = '<div class="gbe-viaje-oraculo">';
    $html .= '<header class="gbe-vo-head">';
    $html .= '<div class="gbe-vo-kicker">Oráculo de Viaje &middot; Lyria</div>';
    $html .= '<h2 class="gbe-vo-titulo">Travesía: ' . $origen . ' → ' . $destino . '</h2>';
    $html .= '<div class="gbe-vo-meta">';
    $html .= '<span>⛵ ' . $barco . '</span>';
    $html .= '<span>📏 ' . $tramos . ' tramo' . ($tramos === 1 ? '' : 's') . '</span>';
    if ($estacion !== '') {
        $html .= '<span>📅 ' . $estacion . ($dia > 0 ? (', Día ' . $dia) : '') . '</span>';
    }
    $html .= '<span>⏱ Plazo sugerido: ' . $plazo . ' días off-rol</span>';
    $html .= '</div>';
    if ($trip_html !== '') {
        $html .= '<div class="gbe-vo-trip-row">' . $trip_html . '</div>';
    }
    $html .= '</header>';
    $html .= $tramos_html;
    $html .= '<footer class="gbe-vo-footer">';
    $html .= '<div class="gbe-vo-reglas"><h4>Reglas del viaje</h4><ul>';
    $html .= '<li>Posts sugeridos: <strong>' . $posts . '</strong> (mínimo 1 por jugador activo)</li>';
    $html .= '<li>Plazo orientativo: <strong>' . $plazo . ' días</strong> off-rol</li>';
    $html .= '<li>Rolean la travesía en este hilo. Cuando quieran llegar, el <strong>capitán</strong> solicita el cierre desde el panel del viaje.</li>';
    $html .= '<li>Lyria publicará la llegada a <strong>' . $destino . '</strong> al confirmar.</li>';
    $html .= '</ul></div>';
    $html .= '<div class="gbe-vo-oficios"><h4>Modificadores activos</h4><div class="gbe-vo-oficios-grid">' . $oficios_html . '</div></div>';
    $html .= '</footer></div>';

    return $html;
}

/** Post de cierre cuando el jugador solicita llegada. */
function gbe_oraculo_cierre_post_html(array $viaje, string $capitan_nombre)
{
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));
    $origen  = htmlspecialchars_uni((string) ($viaje['origen_nombre'] ?? ''));
    $cap     = htmlspecialchars_uni($capitan_nombre);

    return '<div class="gbe-viaje-oraculo gbe-vo-cierre">'
         . '<header class="gbe-vo-head gbe-vo-head--cierre">'
         . '<div class="gbe-vo-kicker">Llegada confirmada &middot; Lyria</div>'
         . '<h2 class="gbe-vo-titulo">⚓ ' . $destino . '</h2>'
         . '<p class="gbe-vo-cierre-lead">A solicitud del capitán <strong>' . $cap . '</strong>, '
         . 'la tripulación completa la travesía desde <em>' . $origen . '</em> y amarra en '
         . '<em>' . $destino . '</em>. El viento amaina; el Log Pose marca tierra firme.</p>'
         . '</header>'
         . '<div class="gbe-vo-narrativa gbe-vo-narrativa--cierre">'
         . '<p>Los personajes participantes quedan ubicados en <strong>' . $destino . '</strong>. '
         . 'Podéis abrir tramas en presente en el foro de la isla cuando queráis.</p>'
         . '</div></div>';
}

/** GSAP + confetti para hilos de viaje (showthread). */
function gbe_oraculo_showthread_scripts()
{
    $nuevo = isset($_GET['viaje']) && $_GET['viaje'] === 'nuevo';
    $cerrado = isset($_GET['viaje']) && $_GET['viaje'] === 'cerrado';

    $js  = 'document.addEventListener("DOMContentLoaded",function(){';
    $js .= 'function run(){var root=document.querySelector(".gbe-viaje-oraculo");if(!root||root.dataset.voReady)return;';
    $js .= 'if(!window.gsap){setTimeout(run,80);return;}root.dataset.voReady="1";';
    $js .= 'var cards=root.querySelectorAll(".gbe-vo-card");';
    $js .= 'if(cards.length){gsap.set(cards,{transition:"none"});}';
    $js .= 'var tl=gsap.timeline({onComplete:function(){if(cards.length){gsap.set(cards,{clearProps:"transform,opacity,transition"});}}});';
    $js .= 'tl.from(root.querySelector(".gbe-vo-head"),{opacity:0,y:-28,duration:.85,ease:"power3.out"});';
    $js .= 'tl.from(root.querySelectorAll(".gbe-vo-tramo"),{opacity:0,x:-36,stagger:.18,duration:.55,delay:.25,ease:"power2.out"},">-0.15");';
    $js .= 'tl.from(cards,{opacity:0,scale:.72,rotationY:75,stagger:.06,duration:.45,delay:.15,ease:"back.out(1.6)"},">-0.1");';
    $js .= 'tl.from(root.querySelector(".gbe-vo-footer"),{opacity:0,y:20,duration:.5},">-0.1");';
    if ($nuevo && $cerrado === false) {
        $js .= 'if(window.confetti){setTimeout(function(){confetti({particleCount:90,spread:70,origin:{y:.55},colors:["#FFCB93","#41A4E0","#FFE9A3","#10477B"]});},600);}';
    }
    if ($cerrado) {
        $js .= 'if(window.confetti){setTimeout(function(){confetti({particleCount:60,spread:55,origin:{y:.65},scalar:.9,colors:["#FFCB93","#458CC5"]});},400);}';
    }
    $js .= '}run();});';

    return '<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>'
         . '<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" defer></script>'
         . '<script defer>' . $js . '</script>';
}
