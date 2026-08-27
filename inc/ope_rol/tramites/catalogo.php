<?php
/**
 * One Piece: 7 Seas · Catálogo de los 67 trámites (cap. 22.3 del Manual del Staff)
 * ---------------------------------------------------------------------------------
 * Datos puros (sin SQL ni HTML). El motor (motor.php) consume este catálogo:
 * naturaleza, skill, quién, firma, efecto al publicar y plantilla de prompt.
 *
 * Naturaleza:
 *   ia     → prompt generado → la IA (skill) propone → el staff firma.
 *   ligero → validación + hooks, 100 % automático (sin IA ni firma).
 *   staff  → solo el staff lo inicia (mismo motor: bandeja, firma, auditoría).
 *   hito   → hito narrativo con firma del staff.
 *
 * firma=true → el trámite exige firma con motivo antes de publicar.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Plantilla de prompt genérica (22.6). */
function ope7_prompt_generica($numero, $nombre, $skill, $contexto)
{
    $skill_line = $skill !== '' && $skill !== 'ia-general'
        ? "Skill del Anexo B: {$skill}."
        : "Skill del Anexo B: IA general (sin skill dedicada).";
    return "Actúas como personal de análisis del foro «One Piece: 7 Seas» (motor Eternal).\n"
         . "Trámite nº {$numero} — {$nombre}.\n"
         . $skill_line . "\n"
         . "Contexto del sistema (IDs y datos):\n{$contexto}\n"
         . "Salida esperada: el resultado en el formato del tipo de trámite, con motivo justificado y editable.\n"
         . "Nada se publica sin la firma del staff (la IA propone, el staff decide).\n";
}

/** Plantillas específicas ya definidas por sistema (22.6). */
function ope7_prompt_especifica($numero, array $ctx)
{
    $j = function ($k) use ($ctx) {
        return isset($ctx[$k]) ? (string) $ctx[$k] : '';
    };

    switch ((int) $numero) {
        case 2: // Cierre de temas
            return "Skill: skill-cierre-temas.\n"
                 . "IDs: tema {$j('tema_id')}; participantes {$j('participantes')}.\n"
                 . "Computa PP = Base(T) × 7 factores (fidelidad, peso, calidad, extensión ≥350 palabras, presente/pasado F_tiempo, riesgo, perfil), "
                 . "informe de rasgos por participante (jugado/no jugado/contradicho), fama propuesta y peso en la matriz 5.14. "
                 . "Base(T) por tramo: 50/75/125/200/300 PP. Techo 2×, suelo 0,5×. Redondeo al entero más cercano (mitades a favor del jugador).";
        case 3: // Validación de ficha
            return "Skill: skill-validacion-personajes.\n"
                 . "Ficha: personaje {$j('personaje_id')}.\n"
                 . "Verifica: raza(s) en catálogo, híbridos con media bien calculada y sin secundarias, tribu si aplica (pureza/unicidad/sustitución), "
                 . "balanza dotes/defectos = 0 exacto, balanza de rasgos = 0, sin parejas antagónicas, requisitos y cadenas, sin redundancias, "
                 . "techos por nivel (20 + 1,6×(L−1)), secundarios con las fórmulas de 5.2, físicos coherentes, cupo INT. "
                 . "Devuelve informe checklist con rojos/verdes (ciclo con el usuario).";
        case 13: // Creación de técnica
            return "Skill: skill-creacion-tecnicas.\n"
                 . "Personaje {$j('personaje_id')}; idea: «{$j('idea')}»; tier deseado {$j('tier')}.\n"
                 . "Genera la ficha completa (requisitos escalados T1:25→T5:70/55/40, dominio ≥ tier, efectos dentro del presupuesto del tier, tipo, PA 2+tier, PE %, reposo, puerta de turno) "
                 . "aplicando el criterio de originalidad: la justificación narrativa se integra en la ficha. Presupuesto T1:1 · T2:1 · T3:2 · T4:2 · T5:3 efectos (+25% coste, máx 1 extra). "
                 . "Sin duplicados con la librería del personaje. (Ciclo con el usuario.)";
        case 20: // Ascenso de facción
            return "Skill: skill-cierre-temas (anexo).\n"
                 . "Personaje {$j('personaje_id')}; facción {$j('faccion')}; rango actual {$j('rango_actual')}.\n"
                 . "Cruza el expediente de fama (4 capas de 5.12), el termómetro de la facción (§13.2) y el umbral del rango. Propuesta: procede / no procede todavía / espera de cupo, con motivo.";
        case 38: // Navegación
            return "Skill: skill-navegacion.\n"
                 . "Trámite de travesía: origen {$j('origen')} → destino {$j('destino')}; barco {$j('barco')}; acompañantes {$j('acompanantes')}; utensilio {$j('utensilio')}.\n"
                 . "Calcula el IRT interno (base del mar + peligrosidad del destino 1–50 + estado del Mundo Vivo − mitigadores) y produce: narrativa inicial, tiempo off-roll (72/48/36 h por tramo), "
                 . "oráculos (catálogo de 7 tipos × gravedad) y gasto de víveres. El desglose del IRT NO se publica; la ficha de travesía sí (editable).";
        case 34: // Anuncio de conquista
            return "Trámite de conquista (cap. 16): isla {$j('isla')} (control previo {$j('control_previo')}, fuerza defensiva {$j('fuerza_defensiva')}); atacante {$j('atacante')}; bando {$j('bando')}.\n"
                 . "Propuesta: objetivo (isla o zona), motivo y justificación de presencia (16.2), rondas de asedio requeridas según 16.3 (salvaje 0 · nv1–15: 1 · nv16–30: 2 · nv31–45: 3 · nv46–50: 4+, fortificaciones +1), invitación al defensor (participación garantizada, 16.4) y suceso público. La IA propone; el staff firma.";
        case 37: // Declarar reconquista
            return "Reconquista (16.5): isla {$j('isla')} con conquista previa registrada.\n"
                 . "Propuesta: nueva disputa con las mismas cinco fases (anuncio → asedio → resolución → registro → ocupación). La ventaja del defensor es su fuerza defensiva ya instalada — sin bonus artificiales. Veredicto con motivo; la IA propone, el staff firma.";
        case 40: // Construcción de barco (Astillero)
            return "Astillero (5.3, Carpintero rama Astillero): construir {$j('tipo')} (N1) para {$j('personaje_id')} con madera {$j('madera')}.\n"
                 . "Verifica el oficio, el coste de materiales (madera 5.8 por clase 18.5) y produce la narrativa de construcción. El barco se construye a N1; la IA propone, el staff firma.";
        case 41: // Mejora N1→N2→N3
            return "Astillero: mejorar el barco {$j('barco')} a {$j('nivel')} (18.4).\n"
                 . "Coste = diferencia de precio del tipo + madera (5.8 por clase 18.5). Un paso a la vez (N1→N2→N3), sin saltos. Propuesta editable; la IA propone, el staff firma.";
        case 42: // Módulos instalar/quitar
            return "Módulo de barco (18.6): {$j('accion')} «{$j('modulo')}» en {$j('barco')}.\n"
                 . "Verifica ranuras libres (máx del tipo/nivel) y el requisito de oficio del módulo (tienda→Comerciante · resina→Astillero nv4 · kairoseki→Mercado Negro). Los personalizados del Carpintero se calibran contra el catálogo (5.7). La IA propone, el staff firma.";
        case 43: // Reparación
            return "Astillero: reparar {$j('barco')} ({$j('grado')}) — 18.7.\n"
                 . "Verifica el oficio Carpintero (rama Astillero) y el coste en materiales (madera 5.8 por grado). Log en reparaciones con materiales, coste y veredicto. La IA propone, el staff firma.";
        case 49: // Adaptación de fruta bajo demanda
            return "Skill: skill-adaptacion-akumas (guía maestra: docs/sistema/diseno/5.18_guia_adaptacion_frutas.md).\n"
                 . "Concepto canon: «{$j('concepto')}» (nombre + familia/rareza/tier propuestos).\n"
                 . "Genera la ficha de 8 bloques: identidad · mecánica base con límites · puertas del catálogo 5.7 + efectos no registrados con calibración y condición · debilidades (enemigo natural) · "
                 . "requisitos del portador · influencia en la ficha (dotes/defectos, balanza a 0) · despertar · fruto en el mundo (tier, precio 5.9, vías, cupo).";
        case 52: // Solicitud de auto-narrada
        case 53: // Posteo de tramo
            return "Skill: skill-narracion-automatica.\n"
                 . "Misión {$j('mision_id')} (ficha de 6 bloques: identidad · objetivo con condiciones de victoria/fracaso · escenas en 3 actos con NPCs · recompensas · requisitos · secretos solo-staff).\n"
                 . "Oráculos del acto: {$j('oraculos')}. Posts de la ronda: {$j('posts')}. Contexto de isla (peligrosidad/sucesos/control) y ronda del calendario: {$j('ronda')}.\n"
                 . "Narra el tramo en prosa rica: los NPCs actúan según su ficha, se aplica el oráculo si procede, NO resuelves por los jugadores, y dejas la escena lista para el siguiente tramo "
                 . "(o verificas las condiciones si es el acto final). Resultado editable y firmado antes de publicarse.";
        case 54: // Apertura de misión
            return "Ficha de misión de 6 bloques (condiciones de victoria/fracaso explícitas y secretos solo-staff) para publicar en el tablón: {$j('concepto')}.\n"
                 . "La validación dura la hace el hook (requisitos); aquí revisa coherencia con el estado del mundo (5.14) y categoría del catálogo (5.20).";
        case 56: // Instalación de implante
        case 59: // Diseño de mejora a medida
            return "Skill: skill-adaptacion-cibernetica (guía maestra: docs/sistema/diseno/5.22_guia_adaptacion_implantes.md).\n"
                 . "Concepto: {$j('concepto')} (zona, nivel N1–N3, ranuras, justificación).\n"
                 . "Genera la ficha calibrada: requisitos acumulativos (suma de todos los implantes), ranuras del catálogo + efectos 5.7, defectos exigidos con balanza a 0, "
                 . "precios (instalación 100.000/500.000/2.500.000 ฿ · PP 200/400/600 · mantenimiento 2.500/10.000/40.000 ฿/ronda) e incompatibilidades (frutas, kairoseki máx O).";
        case 62: // Muerte de personaje
            return "Skill: skill-cierre-temas (calidad del desenlace).\n"
                 . "Personaje {$j('personaje_id')}; causa: {$j('causa')}; tema {$j('tema_id')}.\n"
                 . "Confirma el umbral mecánico de 5.10 (PV ≤ −(VOL×2) o PE ≤ −RES), fija la banda de calidad (descuidada/digna/leyenda), propone efectos de mundo "
                 . "(fruta renacida 5.18 · cartel retirado 5.13 · baja de facción 5.12 · suceso de ronda 5.14) y calcula la herencia (PP 60→1.000 · berries 5.000→1M × 0,5/×1/×1,5). "
                 . "Veredicto con motivo; la IA propone, el staff firma.";
        case 63: // Fundación de tripulación
            return "Fundación de tripulación: ficha (nombre/bandera/propósito) + capitán {$j('capitan')} + fundadores + barco con plazas de 5.17.\n"
                 . "Valida mínimo 2, plazas del barco (solo PJs), un PJ por usuario, y propone el tema de fundación.";
        case 66: // Cambio de capitán
            return "Cambio de capitán: cesión o motín con veredicto de 5.10/5.14. Personajes implicados: {$j('implicados')}.\n"
                 . "Propone el sucesor, el traslado del cofre común y el suceso de ronda si cambia el nombre de la tripulación.";
        default:
            return '';
    }
}

/** Catálogo completo de los 67 trámites (listado CERRADO — cap. 22.3). */
function ope7_tramites_catalogo()
{
    static $cat = null;
    if ($cat !== null) {
        return $cat;
    }

    $t = array(
        // numero, sistema, nombre, skill, quien, naturaleza, firma, efecto
        array(1,  'Transv.',   'Apertura de tema (presente/pasado)', '', 'jugador', 'ligero', false, 'Ancla temporal, instantánea, bloqueo un-presente (5.6)'),
        array(2,  'Transv.',   'Cierre de temas', 'cierre-temas', 'jugador-staff', 'ia', true, 'PP, karma, fama, peso 5.14'),
        array(3,  'Transv.',   'Validación de ficha (crear/editar)', 'validacion-personajes', 'jugador', 'ia', true, 'Ficha aprobada; balanza 0, híbridos, prerrequisitos'),
        array(4,  '5.2/5.3/5.6', 'Compra de PP (atributos, dominios)', '', 'jugador', 'ligero', false, 'Descuenta PP, techo por nivel, cronómetros, cupo INT — 100 % automático'),
        array(5,  '5.3',       'Maestría Suprema (hito nv5 de rama)', '', 'jugador', 'hito', true, 'Oficio a Maestría Suprema con firma'),
        array(6,  '5.3/5.8/5.9', 'Producción de oficio (forja, cocina…)', 'ia-general', 'jugador', 'ia', true, 'Ítem a inventario/almacén; stock de tienda; atributo rey como vara; recetas de los catálogos menores'),
        array(7,  '5.4',       'Dote/defecto por hito narrativo', '', 'jugador', 'hito', true, 'Adquisición 0 PP, sin tocar balanza; requisitos verificados'),
        array(8,  '5.4',       'Genética Alterada (híbrido)', '', 'jugador', 'hito', true, 'UNA dote racial de la 2ª raza, sin combinar con la dominante'),
        array(9,  '5.5',       'Evolución por hito (arraigo positivo → dote)', '', 'jugador', 'hito', true, 'Rasgo arraigado → dote; origen hito; recalcula balanza'),
        array(10, '5.5',       'Superación de rasgo negativo', '', 'jugador', 'hito', true, 'Abandona el rasgo; sustituye por equivalente o antagónico'),
        array(11, '5.5',       'Pérdida/cambio por contradicciones', '', 'jugador', 'hito', true, 'Rebalanceo con motivo (5 contradicciones)'),
        array(12, '5.5',       'Justificación de contradicción', '', 'jugador', 'hito', true, 'La contradicción no cuenta (hito validado)'),
        array(13, '5.7',       'Creación de técnica', 'creacion-tecnicas', 'jugador', 'ia', true, 'Ficha completa; PP 60–600, cupo INT/4, postea en la hoja'),
        array(14, '5.8',       'Equipar/cambiar equipo', '', 'jugador', 'ligero', false, 'Ranuras, cupos Meito, duplicados bloqueados'),
        array(15, '5.9',       'Apertura de tienda', 'ia-general', 'jugador', 'ia', true, 'Tienda activa (Comerciante + local/módulo + capital + bélicos)'),
        array(16, '5.9',       'Cierre/reapertura de tienda', '', 'jugador', 'ligero', true, 'Ítems al almacén; suspensión hasta reabrir'),
        array(17, '5.9',       'Reposición de stock', '', 'jugador', 'ligero', false, 'Stock desde producción o almacén'),
        array(18, '5.9',       'Boletín de precios', '', 'staff', 'staff', true, 'Precios con banda de margen'),
        array(19, '5.11',      'Reclutamiento de NPC', '', 'jugador', 'ligero', true, 'Uso de ficha existente; tripulante sin ficha de combate'),
        array(20, '5.12',      'Ascenso de facción', 'cierre-temas', 'jugador-staff', 'ia', true, 'Propuesta fama/termómetro/umbral; staff firma; sueldo y rango'),
        array(21, '5.12',      'Concesión de subfacción élite', 'ia-general', 'staff', 'staff', true, 'Nombramiento con cupo y firma'),
        array(22, '5.12',      'Cambio de facción', '', 'jugador', 'hito', true, 'Transición narrada, equivalencia, cambios_faccion'),
        array(23, '5.12',      'Deserción', '', 'jugador', 'hito', true, 'Baja hostil → criminal/Wanted; o baja legal'),
        array(24, '5.12',      'Infiltración', '', 'staff', 'staff', true, 'Capa oculta, rango honorario, firma'),
        array(25, '5.13',      'Solicitar rumor a la red', 'ia-general', 'jugador', 'ia', true, 'Ficha del rumor según capacidad y tiempo'),
        array(26, '5.13',      'Comprar rumor', '', 'jugador', 'ligero', false, 'Pago de cartera, ficha transferida'),
        array(27, '5.13',      'Contrastar rumor', 'ia-general', 'jugador', 'ia', true, 'Veredicto: afina fiabilidad/veracidad con coste'),
        array(28, '5.13',      'Vender rumor', '', 'jugador', 'ligero', false, 'Transferencia + copia opcional'),
        array(29, '5.13',      'Montar/ampliar la red', '', 'jugador', 'ligero', false, 'Espías, mantenimiento, límite 4'),
        array(30, '5.13',      'Publicar cartel', 'ia-general', 'staff', 'staff', true, 'Cartel con caducidad de paradero (3 rondas)'),
        array(31, '5.13',      'Cobrar recompensa', 'ia-general', 'jugador', 'ia', true, 'Entrega verificada + histórico'),
        array(32, '5.13',      'Crear rumor falso (propaganda)', 'ia-general', 'jugador', 'ia', true, 'Veracidad falsa; fiabilidad del staff'),
        array(33, '5.13',      'Ataque a una red', 'ia-general', 'jugador', 'ia', true, 'Veredicto sin dados'),
        array(34, '5.15',      'Anuncio de conquista', 'ia-general', 'jugador', 'staff', true, 'Público; invita al defensor; rumores y periódico'),
        array(35, '5.15',      'Responder al asedio (defensor)', '', 'jugador', 'ligero', true, 'Defensa activa'),
        array(36, '5.15',      'Resolver/registrar conquista', 'mundo-vivo', 'staff', 'staff', true, 'Veredicto; afiliación/fuerza defensiva con motivo'),
        array(37, '5.15',      'Declarar reconquista', 'ia-general', 'jugador', 'staff', true, 'Nueva disputa con las mismas fases'),
        array(38, '5.16',      'Navegación (travesía)', 'navegacion', 'jugador', 'ia', true, 'Ficha de travesía; abre el tema; cierre → víveres, daños, ubicacion'),
        array(39, '5.17',      'Compra/adquisición de barco', '', 'jugador', 'ligero', true, 'Barco en flota; verificación tipo/nivel/madera'),
        array(40, '5.17',      'Construcción de barco (Astillero)', 'ia-general', 'jugador', 'ia', true, 'Barco construido con oficio y materiales'),
        array(41, '5.17',      'Mejora N1→N2→N3', 'ia-general', 'jugador', 'ia', true, 'Mejora por diferencia de precio + madera'),
        array(42, '5.17',      'Módulos (instalar/quitar)', 'ia-general', 'jugador', 'ia', true, 'Ranuras y efectos; personalizados calibrados'),
        array(43, '5.17',      'Reparación', 'ia-general', 'jugador', 'ia', true, 'Grados de daño con oficio; reparaciones'),
        array(44, '5.17',      'Venta/desguace/baja', '', 'jugador', 'ligero', true, 'Barco fuera de flota; hundimiento con veredicto'),
        array(45, '5.18',      'Tirada de akuma aleatoria', '', 'jugador', 'ligero', false, 'Pool por nivel, afinidad −10 % PE; anti-abuso nv7 — 100 % automático (azar de obtención)'),
        array(46, '5.18',      'Compra de fruta con PP', '', 'jugador', 'ligero', false, 'Descuento matriz de especificidad; cupo mundial'),
        array(47, '5.18',      'Comer la fruta', '', 'jugador', 'ligero', false, 'Asignación, defectos exigidos, dotes exclusivas'),
        array(48, '5.18',      'Despertar', 'ia-general', 'jugador', 'ia', true, 'Requisitos (nivel, antigüedad, temas, VOL); despertares'),
        array(49, '5.18',      'Adaptación de fruta bajo demanda', 'adaptacion-akumas', 'staff', 'staff', true, 'Ficha de 8 bloques desde nombre+concepto canon'),
        array(50, '5.19',      'Tirada del Conquistador (nv5+ cada 10 niveles)', 'ia-general', 'jugador', 'ligero', false, 'Valida nivel e intentos, aplica probabilidad 3→40 %, registra; si acierta: nivel 1 + suceso 5.14 — 100 % automático'),
        array(51, '5.19',      'Subida de nivel de Haki', 'ia-general', 'jugador', 'ia', true, 'Valida usos (1/tipo/tema) + PP (200/300/400/500) + VOL mínima (55/70/85/95); descuenta y postea en la hoja'),
        array(52, '5.20',      'Solicitud de auto-narrada', 'narracion-automatica', 'jugador', 'ia', true, 'Elige misión de la ficha pública, confirma requisitos, paga la tasa del tablón; hook: valida ficha completa, oráculo del acto 1, prompt, tema presente + primer tramo'),
        array(53, '5.20',      'Posteo de tramo (siguiente tramo de la ronda)', 'narracion-automatica', 'jugador', 'ia', true, 'Hook: recoge los posts de la ronda, lanza el siguiente oráculo, genera el prompt y marca el tramo pendiente de firma'),
        array(54, '5.20',      'Apertura de misión (tablón)', 'ia-general', 'staff', 'staff', true, 'Publica la misión en el tablón con ficha completa (condiciones de victoria/fracaso explícitas + secretos solo-staff)'),
        array(55, '5.20',      'Cierre de misión (veredicto + recompensas)', 'narracion-automatica', 'staff', 'staff', true, 'Verifica las condiciones del acto final, aplica recompensas (berries/PP/fama/objetos) con motivo y alimenta el análisis de ronda'),
        array(56, '5.22',      'Instalación de implante', 'adaptacion-cibernetica', 'jugador', 'ia', true, 'Valida requisitos acumulativos + balanza a 0 + cupo por zona + pago; vara de Cirujano+Ingeniero; aplica defectos; revalida la ficha'),
        array(57, '5.22',      'Retirada de implante', '', 'jugador', 'ligero', true, 'Libera el cupo de la zona y la balanza; las mejoras se pierden'),
        array(58, '5.22',      'Mantenimiento / reparación', '', 'jugador', 'ligero', false, 'Pago por ronda (hook de 5.14) o reparación con Ingeniero'),
        array(59, '5.22',      'Diseño de mejora a medida', 'adaptacion-cibernetica', 'staff', 'staff', true, 'La ranura de habilidad especial calibrada (efecto del catálogo o no registrado con condiciones)'),
        array(60, '5.22',      'Concesión de linaje', 'ia-general', 'staff', 'staff', true, 'Cruza el expediente de fidelidad con el cupo (3–5); aplica dote/defecto «La sangre llama»; suceso de ronda'),
        array(61, '5.22',      'Revocación de linaje', '', 'staff', 'staff', true, 'Retira dote/defecto, libera cupo, suceso de ronda; motivo obligatorio'),
        array(62, '5.21-bis',  'Muerte de personaje', 'cierre-temas', 'jugador-staff', 'staff', true, 'Veredicto con motivo; ficha a reliquia; fruta renace; cartel retirado; baja de facción; suceso de ronda; herencia por bandas de nivel × calidad'),
        array(63, '5.21-ter',  'Fundación de tripulación', 'ia-general', 'capitan', 'ia', true, 'Entidad creada; valida mínimo 2, ficha, plazas del barco, un PJ por usuario; abre el tema de fundación'),
        array(64, '5.21-ter',  'Ingreso en tripulación', '', 'capitan', 'ligero', true, 'Verifica espacio del barco (solo PJs) y un PJ por usuario; fecha de ingreso'),
        array(65, '5.21-ter',  'Baja / expulsión', '', 'capitan', 'ligero', true, 'Libera plaza; reparto de la parte del cofre con registro'),
        array(66, '5.21-ter',  'Cambio de capitán', 'ia-general', 'staff-jugador', 'staff', true, 'Cesión o motín con veredicto (5.10/5.14); mueve el cofre; suceso de ronda si cambia el nombre'),
        array(67, '5.21-ter',  'Disolución', '', 'capitan-staff', 'staff', true, 'Reparte el cofre, devuelve objetos, barco al último capitán; cierra la entidad (automática <2 activos)'),
    );

    $cat = array();
    foreach ($t as $row) {
        $cat[$row[0]] = array(
            'numero'      => (int) $row[0],
            'sistema'     => (string) $row[1],
            'nombre'      => (string) $row[2],
            'skill'       => (string) $row[3],
            'quien'       => (string) $row[4],
            'naturaleza'  => (string) $row[5],
            'firma'       => (bool) $row[6],
            'efecto'      => (string) $row[7],
        );
    }
    return $cat;
}

/** Devuelve una entrada del catálogo o null. */
function ope7_tramite_info($numero)
{
    $cat = ope7_tramites_catalogo();
    return isset($cat[(int) $numero]) ? $cat[(int) $numero] : null;
}

/** Lista de los 67 trámites ordenada por número. */
function ope7_tramites_lista()
{
    $cat = ope7_tramites_catalogo();
    ksort($cat);
    return $cat;
}

/** Etiqueta humana de una naturaleza. */
function ope7_naturaleza_label($nat)
{
    $labels = array(
        'ia'     => 'IA + firma',
        'ligero' => 'Ligero (automático)',
        'staff'  => 'Staff',
        'hito'   => 'Hito (firma)',
    );
    return isset($labels[$nat]) ? $labels[$nat] : $nat;
}

/** Catálogo de las 8 skills del Anexo B (para documentación y filtros). */
function ope7_skills_catalogo()
{
    return array(
        'validacion-personajes'   => 'Valida fichas: balanza dotes/defectos = 0, atributos de híbridos, cadenas de prerrequisitos',
        'cierre-temas'            => 'PP al cerrar temas: Base(T) × 7 factores (fidelidad, peso, calidad, extensión, tiempo, riesgo, perfil) + anexo de ascenso de facción',
        'creacion-tecnicas'       => 'Ficha completa de técnica desde nombre+descripción+tier (requisitos, efectos del catálogo, tipo, PA/PE/reposo/puerta) con criterio de originalidad',
        'mundo-vivo'              => 'Análisis de ronda: dashboard KPIs, matriz de islas, recompensas, periódico «News Coo», rumores/carteles, fluctuación de mercado, hordas',
        'navegacion'              => 'Oráculos de travesía: narrativa inicial, tiempo off-roll, incidentes (daños al barco, víveres)',
        'narracion-automatica'    => 'Narrativa por rondas para misiones/aventuras auto-narradas (ficha de 6 bloques + oráculos + posts + Mundo Vivo)',
        'adaptacion-akumas'       => 'Ficha de fruta bajo demanda desde nombre+concepto canon (guía maestra diseno/5.18)',
        'adaptacion-cibernetica'  => 'Ficha de implante bajo demanda (guía maestra diseno/5.22)',
    );
}
