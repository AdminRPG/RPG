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
        case 25: // Solicitar rumor a la red (5.13/14.2.3)
            return "Trámite 25 · Solicitar rumor a la red (5.13, cap. 14.2.3).\n"
                 . "Personaje {$j('personaje_id')}; espía {$j('espia_id')}.\n"
                 . "La red investiga un rumor concreto según la CAPACIDAD del espía (Novato: Suspiro/Murmullo local · "
                 . "Avanzado: hasta Susurro regional · Experimentado: hasta Alto Susurro · Supremo: todas, mundial) y su tiempo "
                 . "(1 ronda Suspiro/Murmullo, 2 Susurro, 3 Alto Susurro — un Experimentado en Alto Susurro tarda 4). "
                 . "Devuelve la ficha del rumor: contenido (frase jugable), tipo (suceso/tesoro/persona/facción), alcance, "
                 . "categoría (suspiro/murmullo/susurro/alto_susurro), fiabilidad publicada (rumoroso/plausible/sólido), "
                 . "veracidad interna (verdadero/dudoso/falso según el origen: suceso real = verdadero; especulación = dudoso) "
                 . "y precio_base sugerido (14.2.2). Nunca tiradas: la capacidad decide qué puede y cuánto tarda.";
        case 27: // Contrastar rumor (5.13/14.4)
            return "Trámite 27 · Contrastar rumor (5.13, cap. 14.4).\n"
                 . "Rumor {$j('rumor_id')}; sensibilidad {$j('sensibilidad')}.\n"
                 . "Coste por alcance (Local 1.000–5.000 · Regional 5.000–25.000 · Mundial 50.000–250.000 ฿) × sensibilidad "
                 . "(común ×1 · figura pública ×2 · criminal ×3 · identidad oculta ×5 · entidad ×10). Tiempo: 1 ronda "
                 . "Local/Regional · 2 Mundial. Efecto: la fiabilidad afina un grado; en Sólido se revela la veracidad interna. "
                 . "Límites: lo que nadie sabe no se verifica (sin pistas no llega a Sólido); contrastar a ciegas un falso bien "
                 . "sembrado puede confirmarlo. Devuelve el veredicto editable para la firma.";
        case 30: // Publicar cartel (5.13/14.6, staff)
            return "Trámite 30 · Publicar cartel de recompensa (5.13, cap. 14.6).\n"
                 . "Personaje buscado {$j('personaje_id')}.\n"
                 . "Fija la cifra (escala 5.9: cientos de miles → 3.000M), el paradero publicado con su fiabilidad "
                 . "(rumoroso/plausible/sólido) y el nivel aproximado. El paradero caduca a las 3 rondas sin avistamiento "
                 . "actualizado (14.6). Devuelve el texto del cartel editable para la firma.";
        case 31: // Cobrar recompensa (5.13/14.6)
            return "Trámite 31 · Cobrar recompensa (5.13, cap. 14.6).\n"
                 . "Cazador {$j('personaje_id')}; cartel {$j('cartel_id')}.\n"
                 . "Verifica: cartel vigente, paradero no frío (menos de 3 rondas), entrega real (tema presente resuelto con "
                 . "veredicto de 5.10 — vivo o muerto según el cartel) y anti-abuso (sin entrega no hay cobro; autocaza es abuso). "
                 . "Devuelve el resumen de entrega para la firma y el cobro.";
        case 32: // Crear rumor falso (propaganda, 5.13/14.8)
            return "Trámite 32 · Crear rumor falso (propaganda, 5.13, cap. 14.8).\n"
                 . "Personaje {$j('personaje_id')}; isla {$j('isla_id')}.\n"
                 . "El jugador siembra un rumor FALSO (veracidad interna = falso, nunca se reescribe). Tú decides la fiabilidad "
                 . "publicada (cómo circula: un chisme de taberna nace rumoroso; bien sembrado puede parecer sólido) y el alcance. "
                 . "Reglas: un falso de gran alcance puede generar Wanted injusto o suceso — eso es trama y tú decides cómo se "
                 . "procesa (14.8). Devuelve la ficha del rumor editable para la firma.";
        case 33: // Ataque a una red (5.13/14.5)
            return "Trámite 33 · Ataque a una red (5.13, cap. 14.5).\n"
                 . "Atacante {$j('personaje_id')}; red objetivo {$j('red_id')}.\n"
                 . "El atacante declara su método (sabotaje, infiltración, delación…); tú decides con veredicto qué descubre o "
                 . "estropea según la capacidad de los espías y la narrativa. NUNCA tiradas. Devuelve: espías descubiertos "
                 . "(ids), si la red se desactiva y la trama resultante (un espía descubierto es delación/contrainformación/chantaje).";
        case 48: // Despertar de akuma (5.18/19.6)
            return "Trámite 48 · Despertar de akuma (5.18, cap. 19.6).\n"
                 . "Personaje {$j('personaje_id')}; fruta {$j('akuma_id')}.\n"
                 . "Requisitos (19.4/19.6): nivel alto por banda de tier/familia — T1–T2 desde nv25, T3–T4 desde nv32, "
                 . "Logia/mitológica desde nv40 —, antigüedad real como portador (meses on-roll, calendario 5.6), "
                 . "temas cerrados usándola (histórico) y Voluntad como moneda (VOL). "
                 . "Propón el despertar DE ESTA fruta según su ficha (bloque «despertar» de 5.18): qué cambia, qué sostiene "
                 . "(técnicas sin PE extra con mantenimiento de PE por turno, reposos/puertas intactos, 19.6) y si es un "
                 . "suceso de ronda (5.14) — la Logia siempre lo es. Devuelve el resumen para la firma.";
        case 49: // Adaptación de fruta bajo demanda (staff, skill-adaptacion-akumas)
            return "Trámite 49 · Adaptación de fruta bajo demanda (5.18, skill-adaptacion-akumas + guía maestra).\n"
                 . "Concepto del staff: {$j('concepto')}.\n"
                 . "Construye la ficha de 8 bloques desde el nombre+concepto canon: 1) identidad (nombre propio, familia, "
                 . "rareza, tier 1–5, aspecto), 2) mecánica base (pasivas, límites, rupturas con condición), 3) puertas "
                 . "(efectos del catálogo 5.7 + no registrados con calibración), 4) debilidades (enemigo natural), "
                 . "5) requisitos del portador, 6) influencia en la ficha (dotes/defectos — balanza a 0, con la dote "
                 . "exclusiva inventada), 7) despertar, 8) precio y vías (coste_pp matriz 19.7). "
                 . "Reglas: sin dados, sin personajes/eventos canon como contenido, cupo mundial (fruto único, 19.7), "
                 . "coherencia con lo cerrado (3.8) y anti-abuso. Devuelve la ficha completa en JSON para que el staff la revise y firme.";
        case 51: // Subida de nivel de Haki (5.19)
            return "Trámite 51 · Subida de nivel de Haki (5.19).\n"
                 . "Personaje {$j('personaje_id')}; tipo {$j('tipo')}.\n"
                 . "Valida contra la ficha: (a) el tipo está despierto (Armadura/Mantra automáticas a nv10; Conquistador solo por la tirada 50), "
                 . "(b) usos_acumulados — 1 por tipo y por tema, usos SATISFACTORIOS (nada decorativo, 20.2) —, "
                 . "(c) PP disponibles y (d) VOL efectiva (base + bonus de raza/dote por encima del techo, 20.3). "
                 . "Escalera N1→N5 (números cerrados): 6 usos+200 PP+VOL 55 · 8+300+70 · 10+400+85 · 12+500+95. "
                 . "La escalera se paga entera: la adaptabilidad humana NO aplica al Haki (20.2). "
                 . "Propón el escalón, confirma el requisito de Voluntad y devuelve el resumen para la firma (PP a descontar, nivel resultante).";
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
        case 52: // Solicitud de auto-narrada
        case 53: // Posteo de tramo
            return "Skill: skill-narracion-automatica.\n"
                 . "Misión {$j('mision_id')} (ficha de 6 bloques: identidad · objetivo con condiciones de victoria/fracaso · escenas en 3 actos con NPCs · recompensas · requisitos · secretos solo-staff).\n"
                 . "Oráculos del acto: {$j('oraculos')}. Posts de la ronda: {$j('posts')}. Contexto de isla (peligrosidad/sucesos/control) y ronda del calendario: {$j('ronda')}.\n"
                 . "Narra el tramo en prosa rica: los NPCs actúan según su ficha, se aplica el oráculo si procede, NO resuelves por los jugadores, y dejas la escena lista para el siguiente tramo "
                 . "(o verificas las condiciones si es el acto final). Resultado editable y firmado antes de publicarse.";
        case 54: // Apertura de misión
            return "Ficha de misión de 6 bloques (condiciones de victoria/fracaso explícitas y secretos solo-staff) para publicar en el tablón: {$j('concepto')}.\n"
                 . "Salida JSON: identidad (nombre, categoria faccion/reino_isla/profesional/bajo_mundo/especial, origen, dificultad, duracion) · condiciones (victoria Y fracaso explícitas) · "
                 . "escenas (acto1/acto2/acto3 con NPCs) · recompensas (berries/pp/fama/objetos/tasa) · requisitos (nivel_min/faccion/oficios/grupo_min) · secretos_json (texto). "
                 . "La validación dura la hace el hook; aquí revisa coherencia con el estado del mundo (5.14) y la categoría del catálogo (5.20).";
        case 55: // Cierre de misión
            return "Skill: skill-narracion-automatica + skill-cierre-temas.\n"
                 . "Misión {$j('mision_id')} ({$j('mision')}) — verifica las CONDICIONES del acto final contra lo roleado (21.3, sin dados): condiciones de victoria/fracaso de la ficha, posts de la ronda y oráculos usados.\n"
                 . "Salida: resultado (cumplida/fracasada/abandonada) · condiciones (qué se verificó) · recompensas aplicadas (berries/pp/fama/objetos con motivo) · resultado_txt.\n"
                 . "La IA propone, el staff firma con motivo; el cierre alimenta la ronda de Mundo Vivo (5.14) con un suceso en borrador.";
        case 56: // Instalación de implante
        case 59: // Diseño de mejora a medida
            return "Skill: skill-adaptacion-cibernetica (guía maestra: docs/sistema/diseno/5.22_guia_adaptacion_implantes.md).\n"
                 . "Concepto: {$j('concepto')} (zona, nivel N1–N3, ranuras, justificación).\n"
                 . "Genera la ficha calibrada: requisitos acumulativos (suma de todos los implantes), ranuras del catálogo + efectos 5.7, defectos exigidos con balanza a 0, "
                 . "precios (instalación 100.000/500.000/2.500.000 ฿ · PP 200/400/600 · mantenimiento 2.500/10.000/40.000 ฿/ronda) e incompatibilidades (frutas, kairoseki máx O).";
        case 57: // Retirada de implante
            return "Retirada de implante (5.22/23.2): modificación #{$j('modificacion_id')} — libera el cupo de la zona y la balanza; las mejoras se pierden.\n"
                 . "Confirma que el personaje tiene el implante activo y propone el motivo narrativo de la retirada (queda en el histórico).";
        case 58: // Mantenimiento/reparación
            return "Mantenimiento de implante (5.22/23.3): modificación #{$j('modificacion_id')}.\n"
                 . "Pago del mantenimiento por ronda (2.500/10.000/40.000 ฿ según nivel; ×2 si el defecto «Mantenimiento oneroso») o reparación con Ingeniero si está averiado.\n"
                 . "Confirma el saldo de la cartera y el estado del implante; el hook de ronda lo descuenta automáticamente.";
        case 60: // Concesión de linaje
            return "Concesión de linaje (5.22 §B/23.7): familia #{$j('familia_id')} para el personaje #{$j('personaje_id')}.\n"
                 . "Cruza el expediente de fidelidad (ponderado por skill-cierre-temas) con el cupo mundial (3–5) y propone la concesión con motivo.\n"
                 . "Salida: familia_id · personaje_id · motivo · dote/defecto aplicados (La sangre llama −1). La IA propone, el staff firma; suceso de ronda en borrador (5.14).";
        case 61: // Revocación de linaje
            return "Revocación de linaje (5.22 §B/23.7): linaje #{$j('linaje_id')}.\n"
                 . "Confirma la traición al nombre o las contradicciones de 5.5 y propone la revocación con motivo: se retira la dote/defecto, se libera el cupo y hay suceso de ronda en borrador.";
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

/** Etiqueta humana de una naturaleza (D6.1: sin jerga operativa — el jugador
 * nunca ve «IA»; el trabajo se atribuye al staff/foro). */
function ope7_naturaleza_label($nat)
{
    $labels = array(
        'ia'     => 'Revisión del staff (firma)',
        'ligero' => 'Automático',
        'staff'  => 'Solo staff',
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
