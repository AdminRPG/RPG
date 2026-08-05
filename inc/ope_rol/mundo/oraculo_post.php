<?php
/**
 * One Piece: Eternal · Generador HTML del post automático del Oráculo de Viaje (OPE Eternal).
 *
 * Versión 2.0: Soporta las 6 mesas D100 (Clima, Encuentro, Peligro, Hallazgo, Misterio, Bonanza),
 * nivel de peligro, días onrol, indicación de ruta temeraria e ítems equipados.
 * SIN EMOJIS — los elementos visuales se maquetan con CSS (.ope-vo-*).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function ope_oraculo_post_html(array $viaje, array $oraculo)
{
    $origen  = htmlspecialchars_uni((string) ($viaje['origen_nombre'] ?? ''));
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));
    $barco   = htmlspecialchars_uni((string) ($viaje['barco_nombre'] ?? 'Sin nombre'));
    $posts   = (int) ($viaje['posts_min'] ?? 6);
    $plazo   = (int) ($viaje['plazo_dias'] ?? 5);
    $dias_onrol = (int) ($viaje['dias_onrol'] ?? 2);
    $nivel_peligro = htmlspecialchars_uni((string) ($viaje['nivel_peligro'] ?? 'bajo'));
    $es_temeraria  = !empty($viaje['es_temeraria']);

    $cal = function_exists('ope_rol_onrol_calendar') ? ope_rol_onrol_calendar() : array('season' => '', 'day' => 0);
    $estacion = htmlspecialchars_uni((string) ($cal['season'] ?? ''));
    $dia      = (int) ($cal['day'] ?? 0);

    // Tripulantes
    $trip_html = '';
    $trip = json_decode((string) ($viaje['tripulantes_json'] ?? '[]'), true);
    if (is_array($trip)) {
        foreach ($trip as $t) {
            $nom = htmlspecialchars_uni((string) ($t['nombre'] ?? ''));
            $ofi = htmlspecialchars_uni((string) ($t['oficio'] ?? 'tripulante'));
            $trip_html .= '<span class="ope-vo-trip">' . $nom . ' <small>(' . $ofi . ')</small></span>';
        }
    }

    // Modificadores globales
    $oficios_html = '';
    $mods = is_array($oraculo['mods'] ?? null) ? $oraculo['mods'] : array();
    $labels = array(
        'clima'     => 'Clima',
        'encuentro' => 'Encuentros',
        'peligro'   => 'Peligros',
        'hallazgo'  => 'Hallazgos',
        'misterio'  => 'Misterios',
        'bonanza'   => 'Bonanzas'
    );
    foreach ($labels as $k => $lbl) {
        $v = (int) ($mods[$k] ?? 0);
        $sign = $v > 0 ? '+' : '';
        $oficios_html .= '<div class="ope-vo-oficio"><span class="ope-vo-oficio-l">' . $lbl . '</span>'
                       . '<span class="ope-vo-oficio-v">' . $sign . $v . '</span></div>';
    }

    // Cartas del oraculo (unico tramo con base + extras)
    $tramos_html = '';
    $map = array(
        'clima'     => array('cls' => 'clima',     'label' => 'Clima'),
        'encuentro' => array('cls' => 'encuentro', 'label' => 'Encuentro'),
        'peligro'   => array('cls' => 'peligro',   'label' => 'Peligro'),
        'hallazgo'  => array('cls' => 'hallazgo',  'label' => 'Hallazgo'),
        'misterio'  => array('cls' => 'misterio',  'label' => 'Misterio'),
        'bonanza'   => array('cls' => 'bonanza',   'label' => 'Bonanza'),
    );

    foreach ($oraculo['tramos'] ?? array() as $tr) {
        $tramos_html .= '<section class="ope-vo-tramo" data-tramo="1">';
        $tramos_html .= '<div class="ope-vo-grid">';

        foreach ($map as $key => $meta) {
            if (empty($tr['cartas'][$key])) continue;
            $c = $tr['cartas'][$key];
            $ico_cls = htmlspecialchars_uni((string) ($c['ico'] ?? $key));
            $nom     = htmlspecialchars_uni((string) ($c['nombre'] ?? '—'));
            $efe     = htmlspecialchars_uni((string) ($c['efecto'] ?? ''));
            $tone    = preg_replace('/[^a-z]/', '', (string) ($c['tone'] ?? 'neutral'));

            $tramos_html .= '<div class="ope-vo-card ope-vo-' . $meta['cls'] . ' tone-' . $tone . '">';
            $tramos_html .= '<div class="ope-vo-card-icon-box"><span class="ope-vo-ico ope-vo-ico-' . $ico_cls . '"></span></div>';
            $tramos_html .= '<div class="ope-vo-card-label">' . $meta['label'] . '</div>';
            $tramos_html .= '<div class="ope-vo-card-valor">' . $nom . '</div>';
            if ($efe !== '') {
                $tramos_html .= '<div class="ope-vo-card-efecto">' . $efe . '</div>';
            }
            $tramos_html .= '<div class="ope-vo-card-roll">D100: ' . (int) ($c['roll_adj'] ?? $c['roll'] ?? 0) . '</div>';
            $tramos_html .= '</div>';
        }

        // Cartas extra (una por cada tramo original adicional)
        foreach ($tr['cartas'] as $k => $c) {
            if (!is_array($c)) continue;
            if (strpos($k, 'extra_') !== 0) continue;
            $mesa_key = (string) ($c['mesa'] ?? '');
            $meta_extra = isset($map[$mesa_key]) ? $map[$mesa_key] : array('cls' => 'hallazgo', 'label' => $mesa_key ?: 'Extra');
            $ico_cls = htmlspecialchars_uni((string) ($c['ico'] ?? $mesa_key));
            $nom     = htmlspecialchars_uni((string) ($c['nombre'] ?? '—'));
            $efe     = htmlspecialchars_uni((string) ($c['efecto'] ?? ''));
            $tone    = preg_replace('/[^a-z]/', '', (string) ($c['tone'] ?? 'neutral'));

            $tramos_html .= '<div class="ope-vo-card ope-vo-' . $meta_extra['cls'] . ' ope-vo-card--extra tone-' . $tone . '">';
            $tramos_html .= '<div class="ope-vo-card-icon-box"><span class="ope-vo-ico ope-vo-ico-' . $ico_cls . '"></span></div>';
            $tramos_html .= '<div class="ope-vo-card-label">' . $meta_extra['label'] . '</div>';
            $tramos_html .= '<div class="ope-vo-card-valor">' . $nom . '</div>';
            if ($efe !== '') {
                $tramos_html .= '<div class="ope-vo-card-efecto">' . $efe . '</div>';
            }
            $tramos_html .= '<div class="ope-vo-card-roll">D100: ' . (int) ($c['roll_adj'] ?? $c['roll'] ?? 0) . '</div>';
            $tramos_html .= '</div>';
        }

        $tramos_html .= '</div>';
        $nar = htmlspecialchars_uni((string) ($tr['narrativa'] ?? ''));
        if ($nar !== '') {
            $tramos_html .= '<div class="ope-vo-narrativa"><p>' . $nar . '</p></div>';
        }
        $tramos_html .= '</section>';
    }

    $nivel_peligro_clean = str_replace('_', ' ', (string) ($viaje['nivel_peligro'] ?? 'bajo'));
    $peligro_class       = htmlspecialchars_uni((string) ($viaje['nivel_peligro'] ?? 'bajo'));
    $peligro_label       = htmlspecialchars_uni(strtoupper($nivel_peligro_clean));

    $html  = '<div class="ope-viaje-oraculo' . ($es_temeraria ? ' is-temeraria' : '') . '">';
    $html .= '<header class="ope-vo-head">';
    $html .= '<div class="ope-vo-kicker">Oráculo de Viaje &middot; El Narrador</div>';
    $html .= '<h2 class="ope-vo-titulo">Travesía: ' . $origen . ' &rarr; ' . $destino . '</h2>';
    
    if ($es_temeraria) {
        $html .= '<div class="ope-vo-temeraria-badge">Ruta Temeraria — Peligro Incrementado</div>';
    }

    $html .= '<div class="ope-vo-meta">';
    $html .= '<span class="ope-vo-meta-item"><strong class="meta-label">Barco:</strong> ' . $barco . '</span>';
    $html .= '<span class="ope-vo-meta-item"><strong class="meta-label">Peligro:</strong> <span class="peligro-tag danger-' . $peligro_class . '">' . $peligro_label . '</span></span>';
    if ($estacion !== '') {
        $html .= '<span class="ope-vo-meta-item"><strong class="meta-label">Estación:</strong> ' . $estacion . ($dia > 0 ? (', Día ' . $dia) : '') . '</span>';
    }
    $html .= '<span class="ope-vo-meta-item"><strong class="meta-label">Plazo:</strong> ' . $plazo . ' días off-rol</span>';
    $html .= '</div>';

    if ($trip_html !== '') {
        $html .= '<div class="ope-vo-trip-row"><span class="meta-label">Tripulación:</span> ' . $trip_html . '</div>';
    }
    $html .= '</header>';

    // Introducción narrativa generada por IA (best effort)
    $intro = trim((string) ($viaje['introduccion_api'] ?? ''));
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
            $html .= '<div class="ope-vo-introduccion">' . $intro_html . '</div>';
        }
    }

    $html .= $tramos_html;

    $html .= '<footer class="ope-vo-footer">';
    $html .= '<div class="ope-vo-reglas"><h4>Reglas de la Travesía</h4><ul>';
    $html .= '<li>Posts sugeridos: <strong>' . $posts . '</strong> (mínimo 1 por jugador activo)</li>';
    $html .= '<li>Duración estimada en rol: <strong>' . $dias_onrol . ' días</strong> | Plazo off-rol: <strong>' . $plazo . ' días</strong></li>';
    $html .= '<li>Rolean la travesía en este hilo. Cuando quieran llegar, el <strong>capitán</strong> solicita el cierre desde el planificador de viajes.</li>';
    $html .= '<li>El Narrador confirmará la llegada a <strong>' . $destino . '</strong> y actualizará la isla actual de los tripulantes.</li>';
    $html .= '</ul></div>';
    $html .= '<div class="ope-vo-oficios"><h4>Modificadores Activos</h4><div class="ope-vo-oficios-grid">' . $oficios_html . '</div></div>';
    $html .= '</footer></div>';

    return $html;
}

/** Post de cierre cuando el jugador solicita llegada. */
function ope_oraculo_cierre_post_html(array $viaje, string $capitan_nombre)
{
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));
    $origen  = htmlspecialchars_uni((string) ($viaje['origen_nombre'] ?? ''));
    $cap     = htmlspecialchars_uni($capitan_nombre);

    return '<div class="ope-viaje-oraculo ope-vo-cierre">'
         . '<header class="ope-vo-head ope-vo-head--cierre">'
         . '<div class="ope-vo-kicker">Llegada confirmada &middot; El Narrador</div>'
         . '<h2 class="ope-vo-titulo">Puerto de Destino: ' . $destino . '</h2>'
         . '<p class="ope-vo-cierre-lead">A solicitud del capitán <strong>' . $cap . '</strong>, '
         . 'la tripulación completa la travesía desde <em>' . $origen . '</em> y amarra exitosamente en '
         . '<em>' . $destino . '</em>. El viento amaina; las anclas tocan tierra firme.</p>'
         . '</header>'
         . '<div class="ope-vo-narrativa ope-vo-narrativa--cierre">'
         . '<p>Los personajes participantes quedan oficialmente ubicados en <strong>' . $destino . '</strong>. '
         . 'Podéis iniciar vuestras tramas en presente en el foro correspondiente a esta isla.</p>'
         . '</div></div>';
}

/** GSAP + confetti para hilos de viaje (showthread). */
function ope_oraculo_showthread_scripts()
{
    $nuevo = isset($_GET['viaje']) && $_GET['viaje'] === 'nuevo';
    $cerrado = isset($_GET['viaje']) && $_GET['viaje'] === 'cerrado';

    $js  = 'document.addEventListener("DOMContentLoaded",function(){';
    $js .= 'function run(){var root=document.querySelector(".ope-viaje-oraculo");if(!root||root.dataset.voReady)return;';
    $js .= 'if(!window.gsap){setTimeout(run,80);return;}root.dataset.voReady="1";';
    $js .= 'var cards=root.querySelectorAll(".ope-vo-card");';
    $js .= 'if(cards.length){gsap.set(cards,{transition:"none"});}';
    $js .= 'var tl=gsap.timeline({onComplete:function(){if(cards.length){gsap.set(cards,{clearProps:"transform,opacity,transition"});}}});';
    $js .= 'tl.from(root.querySelector(".ope-vo-head"),{opacity:0,y:-28,duration:.85,ease:"power3.out"});';
    $js .= 'tl.from(root.querySelectorAll(".ope-vo-tramo"),{opacity:0,x:-36,stagger:.18,duration:.55,delay:.25,ease:"power2.out"},">-0.15");';
    $js .= 'tl.from(cards,{opacity:0,scale:.72,rotationY:75,stagger:.06,duration:.45,delay:.15,ease:"back.out(1.6)"},">-0.1");';
    $js .= 'tl.from(root.querySelector(".ope-vo-footer"),{opacity:0,y:20,duration:.5},">-0.1");';
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

/** Post HTML para solicitud de cierre pendiente de revision. */
function ope_viaje_revision_solicitud_post_html(array $viaje)
{
    $cap = 'Capitan';
    global $db;
    if ($db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre', 'pid = ' . (int) ($viaje['pid_capitan'] ?? 0), array('limit' => 1));
        if ($db->num_rows($pq)) {
            $cap = (string) $db->fetch_field($pq, 'nombre');
        }
    }
    $cap = htmlspecialchars_uni($cap);
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));

    return '<div class="ope-viaje-oraculo ope-vo-cierre ope-vo-pending">'
         . '<header class="ope-vo-head ope-vo-head--cierre">'
         . '<div class="ope-vo-kicker">Solicitud de cierre &middot; El Narrador</div>'
         . '<h2 class="ope-vo-titulo">Cierre pendiente de revision</h2>'
         . '<p class="ope-vo-cierre-lead">El capitan <strong>' . $cap . '</strong> ha solicitado el cierre de esta travesia con destino a '
         . '<em>' . $destino . '</em>. El staff revisara el roleo y confirmara la llegada en breve.</p>'
         . '</header><div class="ope-vo-narrativa ope-vo-narrativa--pending">'
         . '<p>Un Narrador revisara que los eventos del Oraculo se hayan roleado correctamente. '
         . 'Si todo esta en orden, la llegada se confirmara automaticamente. '
         . 'Si hay problemas, el viaje volvera a estado activo para que podais corregirlo.</p>'
         . '</div></div>';
}

/** Post HTML para rechazo de cierre (1er intento) o cancelacion (2do intento). */
function ope_viaje_revision_rechazo_post_html(array $viaje, $cancelado = false)
{
    $motivo = htmlspecialchars_uni((string) ($viaje['revision_motivo'] ?? 'No se ha especificado un motivo.'));
    $destino = htmlspecialchars_uni((string) ($viaje['destino_nombre'] ?? ''));

    if ($cancelado) {
        return '<div class="ope-viaje-oraculo ope-vo-cierre ope-vo-cancelado">'
             . '<header class="ope-vo-head ope-vo-head--cierre">'
             . '<div class="ope-vo-kicker">Viaje cancelado &middot; El Narrador</div>'
             . '<h2 class="ope-vo-titulo">Travesia cancelada</h2>'
             . '<p class="ope-vo-cierre-lead">Tras dos revisiones, el cierre de esta travesia a <em>' . $destino . '</em> ha sido rechazado definitivamente.</p>'
             . '</header><div class="ope-vo-narrativa ope-vo-narrativa--rechazo">'
             . '<h4>Motivo del staff:</h4><p>' . $motivo . '</p>'
             . '<p>La tripulacion debera planificar un nuevo viaje para llegar a su destino.</p>'
             . '</div></div>';
    }

    return '<div class="ope-viaje-oraculo ope-vo-cierre ope-vo-rechazado">'
         . '<header class="ope-vo-head ope-vo-head--cierre">'
         . '<div class="ope-vo-kicker">Revision &middot; El Narrador</div>'
         . '<h2 class="ope-vo-titulo">Cierre rechazado — Viaje reactivado</h2>'
         . '<p class="ope-vo-cierre-lead">El cierre solicitado para la travesia a <em>' . $destino . '</em> no ha pasado la revision del staff.</p>'
         . '</header><div class="ope-vo-narrativa ope-vo-narrativa--rechazo">'
         . '<h4>Motivo:</h4><p>' . $motivo . '</p>'
         . '<p>El viaje vuelve a estar <strong>activo</strong>. Corregid lo indicado y volved a solicitar el cierre cuando esteis listos.</p>'
         . '</div></div>';
}
