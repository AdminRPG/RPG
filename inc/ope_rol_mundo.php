<?php
/**
 * I-Forge · Mundo Vivo (AV-13) — capa de datos y lógica.
 *
 * Incluido desde inc/plugins/ope_rol.php. Todas las funciones usan el prefijo
 * ope_rol_mv_ y trabajan con $db (MyBB) contra las tablas mybb_rol_mv_*.
 *
 * IMPORTANTE: $db->insert_query / update_query NO escapan valores; hay que
 * escapar con $db->escape_string() antes de pasarlos.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ─────────────────────────────────────────────────────────────────────────
// Definiciones estáticas
// ─────────────────────────────────────────────────────────────────────────

/** Nombres de las 8 regiones-foro que actúan como zonas del Tablero. */
function ope_rol_mv_region_map()
{
    return array(
        'East Blue'  => 'east-blue',
        'West Blue'  => 'west-blue',
        'North Blue' => 'north-blue',
        'South Blue' => 'south-blue',
        'Calm Belt'  => 'calm-belt',
        'Red Line'   => 'red-line',
        'Paraíso'    => 'paraiso',
        'Paraiso'    => 'paraiso',
        'New World'  => 'new-world',
    );
}

/** Orden canónico de facciones (para pares de tensión). */
function ope_rol_mv_faccion_order()
{
    return array('marine', 'pirata', 'revolucionario', 'gobierno', 'cazarrecompensas', 'civil');
}

// ─────────────────────────────────────────────────────────────────────────
// Lectura del Tablero
// ─────────────────────────────────────────────────────────────────────────

/** Zonas ordenadas: slug => fila. */
function ope_rol_mv_zonas()
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_zonas')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_zonas', '*', '', array('order_by' => 'orden', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[$r['slug']] = $r;
    }
    return $out;
}

/** Facciones ordenadas: slug => fila. */
function ope_rol_mv_facciones()
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_facciones')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_facciones', '*', '', array('order_by' => 'orden', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[$r['slug']] = $r;
    }
    return $out;
}

/**
 * Tensión POR MAR. Devuelve: zona_slug => par "a|b" => ['valor'=>int,'notas'=>str].
 * Si se pasa $zona, devuelve solo el mapa par=>[...] de esa zona.
 */
function ope_rol_mv_tension($zona = null)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_tension')) {
        return $out;
    }
    $where = ($zona !== null) ? ("zona_slug = '" . $db->escape_string((string) $zona) . "'") : '';
    $q = $db->simple_select('rol_mv_tension', '*', $where);
    while ($r = $db->fetch_array($q)) {
        $entry = array('valor' => (int) $r['valor'], 'notas' => (string) ($r['notas'] ?? ''), 'a' => $r['a_slug'], 'b' => $r['b_slug']);
        if ($zona !== null) {
            $out[$r['par']] = $entry;
        } else {
            $out[$r['zona_slug']][$r['par']] = $entry;
        }
    }
    return $out;
}

/** Arcos abiertos (lista de filas). */
function ope_rol_mv_arcos()
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_arcos')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_arcos', '*', '', array('order_by' => 'arco_id', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Snapshot completo del tablero (para prompt / almacenamiento). */
function ope_rol_mv_tablero()
{
    return array(
        'zonas'     => ope_rol_mv_zonas(),
        'facciones' => ope_rol_mv_facciones(),
        'tension'   => ope_rol_mv_tension(),
        'arcos'     => ope_rol_mv_arcos(),
    );
}

// ─────────────────────────────────────────────────────────────────────────
// Ciclos (mes natural)
// ─────────────────────────────────────────────────────────────────────────

/** Devuelve (creando si hace falta) el ciclo abierto del mes en curso. */
function ope_rol_mv_ciclo_actual()
{
    global $db;
    if (!$db->table_exists('rol_mv_ciclos')) {
        return null;
    }
    $periodo = date('Y-m');
    $q = $db->simple_select('rol_mv_ciclos', '*', "periodo = '" . $db->escape_string($periodo) . "'", array('limit' => 1));
    if ($db->num_rows($q)) {
        return $db->fetch_array($q);
    }
    // No existe: abrir el ciclo del mes.
    $id = $db->insert_query('rol_mv_ciclos', array(
        'periodo'      => $db->escape_string($periodo),
        'estado'       => 'abierto',
        'indicaciones' => '',
        'dateline'     => (int) TIME_NOW,
    ));
    $q = $db->simple_select('rol_mv_ciclos', '*', 'ciclo_id = ' . (int) $id, array('limit' => 1));
    return $db->fetch_array($q);
}

function ope_rol_mv_ciclo_by_id($ciclo_id)
{
    global $db;
    $ciclo_id = (int) $ciclo_id;
    if ($ciclo_id < 1 || !$db->table_exists('rol_mv_ciclos')) {
        return null;
    }
    $q = $db->simple_select('rol_mv_ciclos', '*', 'ciclo_id = ' . $ciclo_id, array('limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** Último ciclo publicado (para páginas públicas). */
function ope_rol_mv_ultimo_publicado()
{
    global $db;
    if (!$db->table_exists('rol_mv_ciclos')) {
        return null;
    }
    $q = $db->simple_select('rol_mv_ciclos', '*', "estado = 'publicado' OR published_at > 0", array('order_by' => 'published_at', 'order_dir' => 'DESC', 'limit' => 1));
    return $db->num_rows($q) ? $db->fetch_array($q) : null;
}

/** Lista de periódicos publicados (más recientes primero). */
function ope_rol_mv_periodicos($limit = 60)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_ciclos')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_ciclos', 'ciclo_id, periodo, noticia_titulo, published_at', "published_at > 0", array('order_by' => 'published_at', 'order_dir' => 'DESC', 'limit' => (int) $limit));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

// ─────────────────────────────────────────────────────────────────────────
// Eventos, misiones, NPCs
// ─────────────────────────────────────────────────────────────────────────

function ope_rol_mv_eventos($ciclo_id, $estado = null)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_eventos')) {
        return $out;
    }
    $where = 'ciclo_id = ' . (int) $ciclo_id;
    if ($estado !== null) {
        $where .= " AND estado = '" . $db->escape_string($estado) . "'";
    }
    $q = $db->simple_select('rol_mv_eventos', '*', $where, array('order_by' => 'dateline', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

function ope_rol_mv_misiones($ciclo_id)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_misiones')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_misiones', '*', 'ciclo_id = ' . (int) $ciclo_id, array('order_by' => 'dateline', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** NPCs mayores: personajes con ficha marcados es_npc. */
function ope_rol_mv_npc_mayores()
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_personajes') || !$db->field_exists('es_npc', 'rol_personajes')) {
        return $out;
    }
    $q = $db->simple_select('rol_personajes', 'pid, nombre, rango, datos, mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np', "es_npc = 1 AND estado <> 'eliminado'", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $faccion = '';
        $d = @json_decode((string) $r['datos'], true);
        if (is_array($d) && !empty($d['faccion'])) {
            $faccion = (string) $d['faccion'];
        }
        $r['faccion'] = $faccion;
        $out[] = $r;
    }
    return $out;
}

function ope_rol_mv_npc_menores($ciclo_id = 0)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_npc_menores')) {
        return $out;
    }
    $where = $ciclo_id > 0 ? ('ciclo_id = ' . (int) $ciclo_id) : '';
    $q = $db->simple_select('rol_mv_npc_menores', '*', $where, array('order_by' => 'dateline', 'order_dir' => 'DESC'));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

// ─────────────────────────────────────────────────────────────────────────
// Zona a partir de un foro (fid)
// ─────────────────────────────────────────────────────────────────────────

/** Dado un fid, sube por su parentlist hasta encontrar una de las 8 regiones. */
function ope_rol_mv_zona_from_fid($fid)
{
    global $db;
    $fid = (int) $fid;
    if ($fid < 1 || !$db->table_exists('forums')) {
        return '';
    }
    $map = ope_rol_mv_region_map();
    $q = $db->simple_select('forums', 'parentlist', 'fid = ' . $fid, array('limit' => 1));
    if (!$db->num_rows($q)) {
        return '';
    }
    $parentlist = (string) $db->fetch_field($q, 'parentlist');
    $ids = array_reverse(array_filter(array_map('intval', explode(',', $parentlist))));
    foreach ($ids as $aid) {
        $nq = $db->simple_select('forums', 'name', 'fid = ' . (int) $aid, array('limit' => 1));
        if ($db->num_rows($nq)) {
            $name = trim((string) $db->fetch_field($nq, 'name'));
            if (isset($map[$name])) {
                return $map[$name];
            }
        }
    }
    return '';
}

// ─────────────────────────────────────────────────────────────────────────
// Traducción de valores a lenguaje natural
// ─────────────────────────────────────────────────────────────────────────

function ope_rol_mv_est_label($v)
{
    $v = (int) $v;
    if ($v <= 10) return 'Colapso';
    if ($v <= 25) return 'Crisis';
    if ($v <= 45) return 'Inestable';
    if ($v <= 65) return 'Tensa';
    if ($v <= 85) return 'Estable';
    return 'Próspera';
}

function ope_rol_mv_mar_label($v)
{
    $v = (int) $v;
    if ($v <= 15) return 'Nula';
    if ($v <= 35) return 'Escasa';
    if ($v <= 55) return 'Moderada';
    if ($v <= 75) return 'Fuerte';
    return 'Dominante';
}

function ope_rol_mv_pir_label($v)
{
    $v = (int) $v;
    if ($v <= 15) return 'Insignificante';
    if ($v <= 35) return 'Baja';
    if ($v <= 55) return 'Notable';
    if ($v <= 75) return 'Alta';
    return 'Dominante';
}

function ope_rol_mv_rep_label($v)
{
    $v = (int) $v;
    if ($v <= -61) return 'Odiada';
    if ($v <= -21) return 'Mal vista';
    if ($v <= 20) return 'Neutral';
    if ($v <= 60) return 'Respetada';
    return 'Admirada';
}

function ope_rol_mv_coh_label($v)
{
    $v = (int) $v;
    if ($v <= 25) return 'Fracturada';
    if ($v <= 50) return 'Débil';
    if ($v <= 75) return 'Sólida';
    return 'Monolítica';
}

function ope_rol_mv_tension_label($v)
{
    $v = (int) $v;
    if ($v <= 20) return 'Paz';
    if ($v <= 40) return 'Tensión baja';
    if ($v <= 60) return 'Tensión media';
    if ($v <= 80) return 'Tensión alta';
    if ($v <= 95) return 'Al borde de la guerra';
    return 'Guerra total';
}

/** Mapea 0-100 en 5 bandas (0-19,20-39,40-59,60-79,80-100). */
function ope_rol_mv_band5($v, array $labels)
{
    $v = max(0, min(100, (int) $v));
    $i = (int) floor($v / 20);
    if ($i > 4) $i = 4;
    return $labels[$i];
}

/**
 * Metadatos de las métricas de ZONA (orden de render).
 * Cada una: label, icono (emoji), 5 etiquetas de banda, color base para la barra.
 */
function ope_rol_mv_zona_metrics()
{
    return array(
        'est' => array('label' => 'Estabilidad',              'bands' => array('Colapso', 'Inestable', 'Tensa', 'Estable', 'Próspera'),           'col' => 'var(--patina)'),
        'mar' => array('label' => 'Presencia Marine',         'bands' => array('Nula', 'Escasa', 'Moderada', 'Fuerte', 'Dominante'),               'col' => 'var(--fac-marine)'),
        'pir' => array('label' => 'Actividad pirata',         'bands' => array('Insignificante', 'Baja', 'Notable', 'Alta', 'Dominante'),         'col' => 'var(--fac-pirata)'),
        'rev' => array('label' => 'Influencia revolucionaria','bands' => array('Nula', 'Escasa', 'Moderada', 'Fuerte', 'Dominante'),               'col' => 'var(--fac-revolucionario)'),
        'eco' => array('label' => 'Prosperidad económica',    'bands' => array('Miseria', 'Precaria', 'Modesta', 'Próspera', 'Opulenta'),          'col' => 'var(--ember)'),
        'civ' => array('label' => 'Orden civil',              'bands' => array('Anarquía', 'Caótico', 'Frágil', 'Ordenado', 'Férreo'),            'col' => 'var(--fac-cazarrecompensas)'),
        'pel' => array('label' => 'Nivel de peligro',         'bands' => array('Seguro', 'Bajo', 'Moderado', 'Alto', 'Mortal'),                   'col' => 'var(--crack)'),
    );
}

/**
 * Metadatos de las métricas de FACCIÓN. 'rep' es especial (-100..100).
 */
function ope_rol_mv_faccion_metrics()
{
    return array(
        'rep' => array('label' => 'Reputación pública',   'special' => 'rep',                                                                        'col' => 'var(--patina)'),
        'coh' => array('label' => 'Cohesión interna',     'bands' => array('Fracturada', 'Débil', 'Sólida', 'Firme', 'Monolítica'),                 'col' => 'var(--h4)'),
        'mil' => array('label' => 'Poder militar',        'bands' => array('Ínfimo', 'Débil', 'Medio', 'Fuerte', 'Supremo'),                        'col' => 'var(--crack)'),
        'inf' => array('label' => 'Influencia política',  'bands' => array('Nula', 'Escasa', 'Moderada', 'Fuerte', 'Dominante'),                    'col' => 'var(--fac-civil)'),
        'eco' => array('label' => 'Recursos económicos',  'bands' => array('Miseria', 'Precaria', 'Modesta', 'Próspera', 'Opulenta'),               'col' => 'var(--ember)'),
        'mor' => array('label' => 'Moral',                'bands' => array('Rota', 'Baja', 'Firme', 'Alta', 'Fervorosa'),                          'col' => 'var(--fac-revolucionario)'),
    );
}

/** Etiqueta de una métrica de zona. */
function ope_rol_mv_zona_metric_label($key, $v)
{
    $m = ope_rol_mv_zona_metrics();
    return isset($m[$key]) ? ope_rol_mv_band5($v, $m[$key]['bands']) : (string) (int) $v;
}

/** Etiqueta de una métrica de facción (rep es especial). */
function ope_rol_mv_faccion_metric_label($key, $v)
{
    $m = ope_rol_mv_faccion_metrics();
    if (!isset($m[$key])) return (string) (int) $v;
    if (!empty($m[$key]['special']) && $m[$key]['special'] === 'rep') return ope_rol_mv_rep_label($v);
    return ope_rol_mv_band5($v, $m[$key]['bands']);
}

// ─────────────────────────────────────────────────────────────────────────
// Generación del super-prompt
// ─────────────────────────────────────────────────────────────────────────

/** Construye el super-prompt autocontenido para la IA externa. */
function ope_rol_mv_build_prompt($ciclo)
{
    global $db;
    if (!is_array($ciclo)) {
        return '';
    }
    $tablero   = ope_rol_mv_tablero();
    $eventos   = ope_rol_mv_eventos((int) $ciclo['ciclo_id'], 'incluido');
    if (empty($eventos)) {
        // Si el staff no ha marcado ninguno, incluir los pendientes por defecto.
        $eventos = ope_rol_mv_eventos((int) $ciclo['ciclo_id']);
    }
    $misiones  = ope_rol_mv_misiones((int) $ciclo['ciclo_id']);
    $npcs      = ope_rol_mv_npc_mayores();
    $menores   = ope_rol_mv_npc_menores((int) $ciclo['ciclo_id']);

    $L = array();
    $L[] = "###############################################################################";
    $L[] = "#  MUNDO VIVO · \"LA BALANZA\"  —  MOTOR NARRATIVO DE ONE PIECE ETERNAL";
    $L[] = "###############################################################################";
    $L[] = "";
    $L[] = "Eres el MOTOR NARRATIVO y el CRONISTA del mundo del foro de rol \"One Piece Eternal\".";
    $L[] = "Trabajas sobre un mundo persistente inspirado en One Piece pero con su propia continuidad (no copies la trama del manga; respeta el TONO: aventura, mar, libertad, Marines vs piratas, revolucionarios, Gobierno Mundial, Reyes del Mar, islas peligrosas).";
    $L[] = "Recibes: (a) el ESTADO ACTUAL del mundo (el Tablero de La Balanza), (b) todo lo ocurrido ESTE MES (temas notificados por jugadores, misiones, movimientos de NPCs) y (c) las INDICACIONES del staff.";
    $L[] = "";
    $L[] = "TU TRABAJO — produce, en este orden, CUATRO entregables (formato exacto al final):";
    $L[] = "  1) NUEVO ESTADO DEL MUNDO — recalcula todas las métricas del Tablero de forma coherente con lo ocurrido (ESTADO_JSON).";
    $L[] = "  2) PERIÓDICO MENSUAL \"Eternal News\" — un periódico in-character en HTML, al estilo de la prensa del mundo de One Piece, que narra el mes (PERIODICO_HTML).";
    $L[] = "  3) NOTICIA DE PORTADA — un titular + resumen + cuerpo breve para la home del foro (NOTICIA).";
    $L[] = "  4) PROMPTS DE IMAGEN — descripciones en inglés para ilustrar el periódico (IMAGENES).";
    $L[] = "";
    $L[] = "== PRINCIPIOS DE LA BALANZA ==";
    $L[] = "· LA PAZ ES EL ESTADO NORMAL DEL MUNDO. Un mundo en guerra total es la EXCEPCIÓN rarísima, no la norma. La mayoría de los meses el mundo está en calma o con conflictos pequeños y localizados. Si un mes no pasa gran cosa, el mundo se ESTABILIZA, no se incendia.";
    $L[] = "· El mundo es una BALANZA que TIENDE AL EQUILIBRIO: por sí sola, la balanza empuja hacia la calma. Hace falta una fuerza sostenida y grande para inclinarla hacia la guerra, y esa fuerza debe venir de EVENTOS CONCRETOS de este mes (o de indicaciones del staff), nunca de la nada.";
    $L[] = "· Causa y efecto PROPORCIONADO: una victoria pirata sonada sube un poco PIR y baja un poco MAR en ESA zona y tensa a las facciones implicadas SOLO en ese mar. Sin un evento que lo justifique, NO subas tensiones ni bajes estabilidad.";
    $L[] = "· Los NPCs están VIVOS: se mueven y reaccionan con mesura acorde a su facción; la mayoría busca sus intereses SIN desatar guerras.";
    $L[] = "· Continuidad: respeta el estado previo, las notas y los arcos abiertos. Evoluciónalos poco a poco, no los reinicies ni los dispares.";
    $L[] = "";
    $L[] = "== REGRESIÓN A LA CALMA (aplícala SIEMPRE, primero) ==";
    $L[] = "Antes de sumar el impacto de los eventos, RELAJA el mundo hacia su reposo:";
    $L[] = "  · Toda TENSIÓN de un par sin un evento que la alimente este mes BAJA 8-15 puntos hacia su base de paz (~15-30). La calma vuelve rápido.";
    $L[] = "  · EST, CIV y ECO de una zona sin conflicto se RECUPERAN 3-8 puntos hacia su base saludable (~55-70).";
    $L[] = "  · PIR y PEL sin sucesos que los sostengan BAJAN 3-8 puntos.";
    $L[] = "  · La COH y la MOR de las facciones tienden lentamente a un punto medio (~50-65) salvo evento.";
    $L[] = "Solo DESPUÉS de relajar, aplica las subidas por los eventos concretos del mes. Resultado esperado: un mes tranquilo deja el mundo IGUAL o MÁS EN PAZ que el anterior.";
    $L[] = "";
    $L[] = "== CÓMO PONDERAR EL IMPACTO (guía; TÚ decides los números finales, siempre CONSERVADOR) ==";
    $L[] = "Para cada evento estima su IMPACTO combinando escala, rango y acumulación:";
    $L[] = "  · Escala PE 0-10: 0 anecdótico · 2 personal · 3 local (aldea/barco) · 5 insular (isla/base) · 7 regional (un mar) · 9 global · 10 cataclísmico.";
    $L[] = "  · Rango del personaje: F/E débiles · D/C medios · B/A fuertes · S/S+ élite · M/M+ trascendentes.";
    $L[] = "  · ACUMULACIÓN: varios eventos pequeños conectados en un mismo mar durante VARIOS meses pesan como uno grande. Un solo evento aislado pesa poco.";
    $L[] = "TOPES POR CICLO (respétalos salvo evento cataclísmico o indicación de staff):";
    $L[] = "  · Subidas: local ±1..3 · insular/regional +4..8 · global +9..15. Bajar hacia la calma puede ser algo mayor.";
    $L[] = "  · Una TENSIÓN no puede subir más de +15 en un solo mes. Pasar de calma (~20) a guerra abierta (>80) exige 4-5 meses de escalada sostenida, no un mes.";
    $L[] = "  · Como mucho UN mar debería acercarse a conflicto serio en un mes normal; el resto, en calma.";
    $L[] = "";
    $L[] = "== MÉTRICAS DEL TABLERO ==";
    $L[] = "ZONA (mar), 0-100 cada una (base de paz entre paréntesis):";
    $L[] = "  EST estabilidad (~60) · MAR presencia Marine/Gobierno (~50) · PIR actividad pirata (~30) · REV influencia revolucionaria (~20) · ECO prosperidad económica (~55) · CIV orden civil (~60) · PEL peligro (~30; clima, Reyes del Mar, criaturas).";
    $L[] = "FACCIÓN:";
    $L[] = "  REP reputación pública (-100..100) · COH cohesión interna · MIL poder militar · INF influencia política · ECO recursos económicos · MOR moral (todas 0-100 salvo REP).";
    $L[] = "TENSIÓN entre facciones POR MAR (0-100), UMBRALES:";
    $L[] = "  · 0-30 = PAZ / roces normales (lo habitual en casi todos los mares).";
    $L[] = "  · 31-55 = fricción / rivalidad fría.";
    $L[] = "  · 56-75 = conflicto localizado (escaramuzas, no guerra).";
    $L[] = "  · 76-89 = guerra inminente (raro; solo tras meses de escalada).";
    $L[] = "  · 90-100 = GUERRA ABIERTA (excepcional; casi nunca, y como mucho en un mar).";
    $L[] = "Cada par tiene un valor DISTINTO en cada mar y una NOTA que explica el porqué EN ESE MAR. Si no hay motivo este mes, la tensión debe estar en calma.";
    $L[] = "";
    $L[] = "== REGLAS DE ESCRITURA (periódico y noticia) ==";
    $L[] = "· NUNCA muestres números, métricas, slugs ni terminología de sistema. Todo se traduce a lenguaje natural in-world ('la presencia de la Marina se desploma en el West Blue').";
    $L[] = "· Voz de prensa del mundo: titulares llamativos, tono periodístico con color local; puede haber sesgo pro-Gobierno velado, rumores y columnas de opinión.";
    $L[] = "· Cita a personajes y NPCs por su nombre cuando aparezcan en los eventos; da protagonismo a lo que hicieron los jugadores.";
    $L[] = "· No inventes contradicciones con el estado ni reveles secretos de staff/metajuego.";
    $L[] = "· Idioma: español.";
    $L[] = "";
    $L[] = "== PERIODO ==";
    $L[] = "Mes que se cierra: " . $ciclo['periodo'];
    $L[] = "";

    $zMetrics = ope_rol_mv_zona_metrics();
    $fMetrics = ope_rol_mv_faccion_metrics();

    // Estado actual
    $L[] = "== ESTADO ACTUAL DEL MUNDO ==";
    $L[] = "Métricas de ZONA (0-100): " . implode(', ', array_map(function ($k) use ($zMetrics) { return strtoupper($k) . '=' . $zMetrics[$k]['label']; }, array_keys($zMetrics)));
    $L[] = "Zonas (slug | nombre | métricas | notas):";
    foreach ($tablero['zonas'] as $z) {
        $vals = array();
        foreach ($zMetrics as $k => $meta) { $vals[] = strtoupper($k) . ' ' . (int) ($z[$k] ?? 0); }
        $L[] = "- {$z['slug']} | {$z['nombre']} | " . implode(' ', $vals) . " | " . trim(preg_replace('/\s+/', ' ', (string) $z['notas']));
    }
    $L[] = "";
    $L[] = "Métricas de FACCIÓN: REP=-100..100 (reputación); resto 0-100: " . implode(', ', array_map(function ($k) use ($fMetrics) { return strtoupper($k) . '=' . $fMetrics[$k]['label']; }, array_keys($fMetrics)));
    $L[] = "Facciones (slug | nombre | métricas | notas):";
    foreach ($tablero['facciones'] as $f) {
        $vals = array();
        foreach ($fMetrics as $k => $meta) { $vals[] = strtoupper($k) . ' ' . (int) ($f[$k] ?? 0); }
        $L[] = "- {$f['slug']} | {$f['nombre']} | " . implode(' ', $vals) . " | " . trim(preg_replace('/\s+/', ' ', (string) $f['notas']));
    }
    $L[] = "";
    $L[] = "Tensión entre facciones POR MAR (0 paz .. 100 guerra). Por cada zona, pares y su nota del porqué:";
    foreach ($tablero['zonas'] as $z) {
        $zt = isset($tablero['tension'][$z['slug']]) ? $tablero['tension'][$z['slug']] : array();
        if (empty($zt)) continue;
        $L[] = "  [{$z['slug']}]";
        foreach ($zt as $par => $info) {
            $nota = trim(preg_replace('/\s+/', ' ', (string) $info['notas']));
            $L[] = "    - {$par} = {$info['valor']}" . ($nota !== '' ? (" | " . $nota) : '');
        }
    }
    $L[] = "";
    if (!empty($tablero['arcos'])) {
        $L[] = "Arcos abiertos:";
        foreach ($tablero['arcos'] as $a) {
            $L[] = "- {$a['nombre']} [{$a['estado']}] zonas: {$a['zonas']} | facciones: {$a['facciones']} | " . trim(preg_replace('/\s+/', ' ', (string) $a['descripcion']));
        }
        $L[] = "";
    }

    // Eventos
    $L[] = "== EVENTOS NOTIFICADOS ESTE MES ==";
    if (empty($eventos)) {
        $L[] = "(Ninguno.)";
    } else {
        foreach ($eventos as $e) {
            $rango = '';
            if ((int) $e['pid'] > 0 && $db->field_exists('rango', 'rol_personajes')) {
                $rq = $db->simple_select('rol_personajes', 'rango, nombre', 'pid = ' . (int) $e['pid'], array('limit' => 1));
                if ($db->num_rows($rq)) {
                    $rr = $db->fetch_array($rq);
                    $rango = ' | personaje: ' . $rr['nombre'] . ' (rango ' . ($rr['rango'] !== '' ? $rr['rango'] : '?') . ')';
                }
            }
            $L[] = "- [" . ($e['zona_slug'] !== '' ? $e['zona_slug'] : 'zona?') . "] " . $e['titulo'] . $rango;
            $L[] = "  Enlace: " . $e['enlace'];
            $L[] = "  Resumen: " . trim(preg_replace('/\s+/', ' ', (string) $e['resumen']));
        }
    }
    $L[] = "";

    // Misiones
    $L[] = "== MISIONES DEL MES ==";
    if (empty($misiones)) {
        $L[] = "(Ninguna.)";
    } else {
        foreach ($misiones as $m) {
            $L[] = "- [" . strtoupper(str_replace('_', ' ', $m['estado'])) . "] " . $m['titulo'] . " (" . ($m['zona_slug'] !== '' ? $m['zona_slug'] : 'zona?') . ")";
            if (trim((string) $m['resumen']) !== '') {
                $L[] = "  " . trim(preg_replace('/\s+/', ' ', (string) $m['resumen']));
            }
        }
    }
    $L[] = "";

    // NPCs mayores
    $L[] = "== NPCs MAYORES (con ficha) ==";
    if (empty($npcs)) {
        $L[] = "(Ninguno registrado.)";
    } else {
        foreach ($npcs as $n) {
            $L[] = "- " . $n['nombre'] . " | facción: " . ($n['faccion'] !== '' ? $n['faccion'] : '?') . " | rango: " . ($n['rango'] !== '' ? $n['rango'] : '?') . " | zona: " . ($n['mundo_zona'] !== '' ? $n['mundo_zona'] : '?') . " | ubicación: " . ($n['mundo_ubic'] !== '' ? $n['mundo_ubic'] : '?') . " | estado: " . ($n['mundo_estado_np'] !== '' ? $n['mundo_estado_np'] : '?') . " | acción: " . $n['mundo_accion'];
        }
    }
    $L[] = "";
    if (!empty($menores)) {
        $L[] = "== NPCs MENORES (relleno de este mes) ==";
        foreach ($menores as $mn) {
            $L[] = "- " . $mn['nombre'] . " (" . ($mn['zona_slug'] !== '' ? $mn['zona_slug'] : 'zona?') . "): " . trim(preg_replace('/\s+/', ' ', (string) $mn['descripcion']));
        }
        $L[] = "";
    }

    // Indicaciones del staff
    $L[] = "== INDICACIONES DEL STAFF (obligatorio seguirlas) ==";
    $ind = trim((string) $ciclo['indicaciones']);
    $L[] = $ind !== '' ? $ind : "(Sin indicaciones especiales este mes.)";
    $L[] = "";

    // Contrato de salida
    $L[] = "###############################################################################";
    $L[] = "==  FORMATO DE RESPUESTA (OBLIGATORIO)  ==";
    $L[] = "###############################################################################";
    $L[] = "Responde EXACTAMENTE con estos CUATRO bloques, cada uno entre sus marcadores ===X=== ... ===FIN===, y SIN ningún texto fuera de ellos (ni saludos ni explicaciones).";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 1 — ESTADO_JSON (el nuevo Tablero). JSON válido, sin comentarios ni comas colgantes.";
    $L[] = "Reglas: usa EXACTAMENTE los mismos slugs de zona y facción y las mismas claves de métrica del estado actual. Incluye TODAS las zonas y TODAS las facciones aunque no cambien. Métricas 0-100 salvo REP (-100..100). La tensión es POR MAR. Redacta las 'notas' in-world y coherentes con el periódico.";
    $L[] = "===ESTADO_JSON===";
    $L[] = "{";
    $L[] = "  \"zonas\": { \"east-blue\": {\"est\":58,\"mar\":55,\"pir\":35,\"rev\":15,\"eco\":55,\"civ\":60,\"pel\":20,\"notas\":\"...\"}, \"...\": {} },";
    $L[] = "  \"facciones\": { \"marine\": {\"rep\":40,\"coh\":80,\"mil\":85,\"inf\":80,\"eco\":75,\"mor\":70,\"notas\":\"...\"}, \"...\": {} },";
    $L[] = "  \"tension\": { \"east-blue\": { \"marine|pirata\": {\"valor\":76,\"notas\":\"por qué en este mar\"}, \"...\": {} }, \"...\": {} },";
    $L[] = "  \"arcos\": [ {\"nombre\":\"...\",\"estado\":\"Activo|Latente|Cerrado\",\"zonas\":\"east-blue\",\"facciones\":\"marine,pirata\",\"descripcion\":\"...\"} ]";
    $L[] = "}";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 2 — PERIODICO_HTML. Solo el contenido interior (sin <html>, <head>, <style> ni <script>). Se inserta dentro de un contenedor .ope-per-body ya existente.";
    $L[] = "CLASES DISPONIBLES (úsalas tal cual; COMBÍNALAS para dar VARIEDAD visual, que no todas las secciones se vean igual):";
    $L[] = "  · <section class=\"ope-per-lead\"> ... </section>  → apertura/portada: un <h2> con el gran titular del mes y 1-2 <p> de entradilla.";
    $L[] = "  · <section class=\"ope-per-sec\"> ... </section>   → una sección temática o por mar; empieza con <h3>TÍTULO</h3>.";
    $L[] = "  · <div class=\"ope-per-cols\"> <p>..</p> <p>..</p> </div>  → cuerpo a DOS COLUMNAS dentro de una sección.";
    $L[] = "  · <div class=\"ope-per-longread\"> <p>..</p> </div>  → reportaje/columna a UNA sola columna con texto grande (para piezas de mucho texto, entrevistas u opinión).";
    $L[] = "  · <figure class=\"ope-per-fig\" data-img=\"ID\"><figcaption>PIE</figcaption></figure>  → imagen pequeña (encaja dentro de columnas).";
    $L[] = "  · <figure class=\"ope-per-figwide\" data-img=\"ID\"><figcaption>PIE</figcaption></figure>  → imagen ANCHA CENTRADA, colócala EN MEDIO de una sección para romper el ritmo.";
    $L[] = "  · <aside class=\"ope-per-aside\"><h4>RUMORES / SE DICE</h4><p>..</p></aside>  → recuadro de rumores, breves o cotizaciones.";
    $L[] = "  · <blockquote class=\"ope-per-pull\">frase destacada</blockquote>  → cita grande para destacar una declaración.";
    $L[] = "  · Anuncios/clasificados: <div class=\"ope-per-ads\"> con varias <div class=\"ope-per-ad\"><span class=\"ope-per-ad-tag\">ANUNCIO</span><h4>Título</h4><p>texto</p></div>. Para un cartel de recompensa usa <div class=\"ope-per-ad ope-per-ad--wanted\"><span class=\"ope-per-ad-tag\">SE BUSCA</span><h4>NOMBRE</h4><figure class=\"ope-per-fig\" data-img=\"ID\"></figure><p class=\"bounty\">Recompensa: ...</p></div>.";
    $L[] = "ESTRUCTURA RECOMENDADA (adáptala a lo que pasó; ~600-900 palabras). BUSCA VARIEDAD, que parezca un periódico real y no una lista de secciones iguales:";
    $L[] = "  1. .ope-per-lead con el titular del mes + figure data-img=\"portada\".";
    $L[] = "  2. 2-4 .ope-per-sec sobre lo relevante del mes. Alterna formatos: alguna a dos columnas (.ope-per-cols), alguna a texto corrido (.ope-per-longread), con al menos una imagen ancha centrada (.ope-per-figwide) en medio de una sección.";
    $L[] = "  3. Incluye SIEMPRE algo de color de mundo aunque haya paz: 'Economía y puertos', 'Vida en las islas', 'Ecos del mar', una entrevista o una columna de opinión firmada (usa .ope-per-longread + .ope-per-pull).";
    $L[] = "  4. Incluye una tira de ANUNCIOS/CLASIFICADOS (.ope-per-ads) con 2-4 anuncios in-world (negocios, tabernas, barcos en venta, tripulaciones que reclutan) y, si procede, algún cartel de SE BUSCA.";
    $L[] = "  5. Al menos un .ope-per-aside de rumores. Cita por su nombre a los personajes/NPCs de los eventos y dales protagonismo.";
    $L[] = "  6. TONO SEGÚN EL MUNDO: si el mundo está en paz (lo normal), el periódico es costumbrista, comercial y de sucesos menores; los grandes titulares de guerra SOLO cuando de verdad hay guerra.";
    $L[] = "===PERIODICO_HTML===";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 3 — NOTICIA para la portada del foro (más breve y directa que el periódico).";
    $L[] = "===NOTICIA===";
    $L[] = "titulo: (titular corto y con gancho para la home)";
    $L[] = "resumen: (una sola frase que se ve en el panel de portada)";
    $L[] = "cuerpo: (2-4 párrafos en HTML simple con <p>, lo que se despliega al hacer clic; enlaza con el periódico)";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 4 — MISIONES que SURGEN de lo ocurrido este mes (ganchos para que los jugadores actúen el mes que viene). Propón 2-5, coherentes con los eventos, los arcos y las tensiones. El staff decidirá cuáles publicar.";
    $L[] = "Un guion por línea, con estos campos separados por ' | ':";
    $L[] = "===MISIONES===";
    $L[] = "- titulo: (título corto) | zona: east-blue | facciones: marine,pirata | dificultad: (baja|media|alta) | resumen: (qué ocurre y qué se pide a los jugadores)";
    $L[] = "- titulo: ... | zona: ... | facciones: ... | dificultad: ... | resumen: ...";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 5 — IMAGENES. Un id por línea. Prompts en INGLÉS, estilo ilustración/anime One Piece, cinematográficos. Si intervienen personajes de jugador o NPCs concretos, añade '(use reference image of <nombre>)'. Incluye SIEMPRE 'portada' y una imagen por cada figure que uses en el periódico. El id debe COINCIDIR con el data-img del figure correspondiente.";
    $L[] = "===IMAGENES===";
    $L[] = "- id: portada | tamaño: 1200x675 | prompt: ...";
    $L[] = "- id: (otro-id-usado-en-figure) | tamaño: 800x600 | prompt: ...";
    $L[] = "===FIN===";

    return implode("\n", $L);
}

// ─────────────────────────────────────────────────────────────────────────
// Parseo del resultado de la IA
// ─────────────────────────────────────────────────────────────────────────

/** Extrae el contenido entre ===MARCA=== y ===FIN===. */
function ope_rol_mv_extract_block($raw, $mark)
{
    if (preg_match('/===' . preg_quote($mark, '/') . '===\s*(.*?)\s*===FIN===/s', $raw, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Parsea el bloque ===MISIONES===. Cada línea:
 *   - titulo: ... | zona: east-blue | facciones: marine,pirata | dificultad: baja|media|alta | resumen: ...
 * Devuelve array de {titulo, zona, facciones, dificultad, resumen}.
 */
function ope_rol_mv_parse_misiones($txt)
{
    $out = array();
    $txt = (string) $txt;
    if ($txt === '') {
        return $out;
    }
    foreach (preg_split('/\r?\n/', $txt) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        // Quitar viñeta inicial "- " o "* "
        $line = preg_replace('/^[\-\*\x{2022}]\s*/u', '', $line);
        if ($line === '') continue;
        $m = array('titulo' => '', 'zona' => '', 'facciones' => '', 'dificultad' => '', 'resumen' => '');
        // Partir por " | " en pares clave: valor
        foreach (explode('|', $line) as $seg) {
            $seg = trim($seg);
            if ($seg === '' || strpos($seg, ':') === false) continue;
            list($k, $v) = explode(':', $seg, 2);
            $k = strtolower(trim($k));
            $v = trim($v);
            switch ($k) {
                case 'titulo': case 'título': $m['titulo'] = $v; break;
                case 'zona': $m['zona'] = $v; break;
                case 'facciones': $m['facciones'] = $v; break;
                case 'dificultad': $m['dificultad'] = strtolower($v); break;
                case 'resumen': $m['resumen'] = $v; break;
            }
        }
        if ($m['titulo'] !== '' || $m['resumen'] !== '') {
            $out[] = $m;
        }
    }
    return $out;
}

/**
 * Parsea el bloque ===IMAGENES===. Cada línea:
 *   - id: portada | tamaño: 1200x675 | prompt: ...
 * Devuelve array de {id, tamano, prompt}.
 */
function ope_rol_mv_parse_imagenes($txt)
{
    $out = array();
    $txt = (string) $txt;
    if ($txt === '') {
        return $out;
    }
    foreach (preg_split('/\r?\n/', $txt) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $line = preg_replace('/^[\-\*\x{2022}]\s*/u', '', $line);
        if ($line === '') continue;
        $img = array('id' => '', 'tamano' => '', 'prompt' => '');
        foreach (explode('|', $line) as $seg) {
            $seg = trim($seg);
            if ($seg === '' || strpos($seg, ':') === false) continue;
            list($k, $v) = explode(':', $seg, 2);
            $k = strtolower(trim($k));
            $v = trim($v);
            switch ($k) {
                case 'id': $img['id'] = $v; break;
                case 'tamano': case 'tamaño': case 'size': $img['tamano'] = $v; break;
                case 'prompt': $img['prompt'] = $v; break;
            }
        }
        if ($img['id'] !== '') {
            $out[] = $img;
        }
    }
    return $out;
}

/** Parsea el resultado pegado. Devuelve array con estado/periodico/noticia/imagenes + errores. */
function ope_rol_mv_parse_resultado($raw)
{
    $raw = (string) $raw;
    $res = array(
        'estado'        => null,
        'periodico'     => '',
        'noticia'       => array('titulo' => '', 'resumen' => '', 'cuerpo' => ''),
        'imagenes'      => '',
        'imagenes_list' => array(),
        'misiones'      => array(),
        'errores'       => array(),
    );

    $estadoRaw = ope_rol_mv_extract_block($raw, 'ESTADO_JSON');
    if ($estadoRaw === '') {
        $res['errores'][] = 'No se encontró el bloque ===ESTADO_JSON===.';
    } else {
        $json = json_decode($estadoRaw, true);
        if (!is_array($json)) {
            $res['errores'][] = 'El bloque ESTADO_JSON no es JSON válido.';
        } else {
            $res['estado'] = $json;
        }
    }

    $res['periodico'] = ope_rol_mv_extract_block($raw, 'PERIODICO_HTML');
    if ($res['periodico'] === '') {
        $res['errores'][] = 'No se encontró el bloque ===PERIODICO_HTML===.';
    }

    $noticiaRaw = ope_rol_mv_extract_block($raw, 'NOTICIA');
    if ($noticiaRaw === '') {
        $res['errores'][] = 'No se encontró el bloque ===NOTICIA===.';
    } else {
        // titulo:, resumen:, cuerpo: (cuerpo puede ser multilínea, va al final)
        if (preg_match('/titulo:\s*(.*)/i', $noticiaRaw, $m)) {
            $res['noticia']['titulo'] = trim($m[1]);
        }
        if (preg_match('/resumen:\s*(.*)/i', $noticiaRaw, $m)) {
            $res['noticia']['resumen'] = trim($m[1]);
        }
        if (preg_match('/cuerpo:\s*(.*)/is', $noticiaRaw, $m)) {
            $res['noticia']['cuerpo'] = trim($m[1]);
        }
    }

    $res['imagenes'] = ope_rol_mv_extract_block($raw, 'IMAGENES');
    $res['imagenes_list'] = ope_rol_mv_parse_imagenes($res['imagenes']);

    $res['misiones'] = ope_rol_mv_parse_misiones(ope_rol_mv_extract_block($raw, 'MISIONES'));

    return $res;
}

/**
 * Calcula un diff legible entre el tablero ACTUAL y el estado nuevo devuelto por la IA.
 * $actual: snapshot de ope_rol_mv_tablero(). $nuevo: $parsed['estado'].
 * Devuelve array con claves 'zonas', 'facciones', 'tension' — cada una lista de cambios.
 * Cada cambio de métrica: {slug, nombre, metrica, label, antes, despues, antes_lbl, despues_lbl, dir}.
 * dir: 1 sube, -1 baja.
 */
function ope_rol_mv_diff_estado($actual, $nuevo)
{
    $out = array('zonas' => array(), 'facciones' => array(), 'tension' => array());
    if (!is_array($nuevo)) {
        return $out;
    }
    $zMetrics = ope_rol_mv_zona_metrics();
    $fMetrics = ope_rol_mv_faccion_metrics();

    // ── Zonas ──
    $zonasActual = array();
    foreach (($actual['zonas'] ?? array()) as $slug => $z) {
        $zonasActual[$slug] = $z;
    }
    if (!empty($nuevo['zonas']) && is_array($nuevo['zonas'])) {
        foreach ($nuevo['zonas'] as $slug => $z) {
            if (!is_array($z)) continue;
            $base = $zonasActual[$slug] ?? array();
            $nombre = isset($base['nombre']) ? $base['nombre'] : $slug;
            $cambios = array();
            foreach ($zMetrics as $k => $meta) {
                if (!isset($z[$k])) continue;
                $antes = (int) ($base[$k] ?? 0);
                $despues = max(0, min(100, (int) $z[$k]));
                if ($antes === $despues) continue;
                $cambios[] = array(
                    'metrica'     => strtoupper($k),
                    'label'       => $meta['label'],
                    'antes'       => $antes,
                    'despues'     => $despues,
                    'antes_lbl'   => ope_rol_mv_zona_metric_label($k, $antes),
                    'despues_lbl' => ope_rol_mv_zona_metric_label($k, $despues),
                    'dir'         => $despues > $antes ? 1 : -1,
                );
            }
            if (!empty($cambios)) {
                $out['zonas'][] = array('slug' => $slug, 'nombre' => $nombre, 'cambios' => $cambios);
            }
        }
    }

    // ── Facciones ──
    $facActual = array();
    foreach (($actual['facciones'] ?? array()) as $slug => $f) {
        $facActual[$slug] = $f;
    }
    if (!empty($nuevo['facciones']) && is_array($nuevo['facciones'])) {
        foreach ($nuevo['facciones'] as $slug => $f) {
            if (!is_array($f)) continue;
            $base = $facActual[$slug] ?? array();
            $nombre = isset($base['nombre']) ? $base['nombre'] : $slug;
            $cambios = array();
            foreach ($fMetrics as $k => $meta) {
                if (!isset($f[$k])) continue;
                $antes = (int) ($base[$k] ?? 0);
                $despues = ($k === 'rep') ? max(-100, min(100, (int) $f[$k])) : max(0, min(100, (int) $f[$k]));
                if ($antes === $despues) continue;
                $cambios[] = array(
                    'metrica'     => strtoupper($k),
                    'label'       => $meta['label'],
                    'antes'       => $antes,
                    'despues'     => $despues,
                    'antes_lbl'   => ope_rol_mv_faccion_metric_label($k, $antes),
                    'despues_lbl' => ope_rol_mv_faccion_metric_label($k, $despues),
                    'dir'         => $despues > $antes ? 1 : -1,
                );
            }
            if (!empty($cambios)) {
                $out['facciones'][] = array('slug' => $slug, 'nombre' => $nombre, 'cambios' => $cambios);
            }
        }
    }

    // ── Tensión POR MAR ──
    $tenActual = $actual['tension'] ?? array();
    if (!empty($nuevo['tension']) && is_array($nuevo['tension'])) {
        foreach ($nuevo['tension'] as $zslug => $pares) {
            if (!is_array($pares)) continue;
            $nombreZona = isset($zonasActual[$zslug]['nombre']) ? $zonasActual[$zslug]['nombre'] : $zslug;
            foreach ($pares as $par => $info) {
                $despues = is_array($info) ? (isset($info['valor']) ? (int) $info['valor'] : null) : (is_numeric($info) ? (int) $info : null);
                if ($despues === null) continue;
                $despues = max(0, min(100, $despues));
                $antes = isset($tenActual[$zslug][$par]['valor']) ? (int) $tenActual[$zslug][$par]['valor'] : 0;
                if ($antes === $despues) continue;
                $out['tension'][] = array(
                    'zona_slug'   => $zslug,
                    'zona_nombre' => $nombreZona,
                    'par'         => $par,
                    'antes'       => $antes,
                    'despues'     => $despues,
                    'antes_lbl'   => ope_rol_mv_tension_label($antes),
                    'despues_lbl' => ope_rol_mv_tension_label($despues),
                    'dir'         => $despues > $antes ? 1 : -1,
                );
            }
        }
    }

    return $out;
}

/**
 * Inyecta las URLs de imagen en el HTML del periódico reemplazando los placeholders.
 * $urls: map id => url. Para cada <figure ... data-img="ID" ...>: si hay URL http(s) válida,
 * inserta tras la etiqueta de apertura un <img src="URL"> y renombra data-img a data-loaded
 * para que el placeholder CSS no se muestre. Si no hay URL, deja el figure intacto.
 */
function ope_rol_mv_inject_imagenes($html, $urls)
{
    $html = (string) $html;
    if ($html === '' || empty($urls) || !is_array($urls)) {
        return $html;
    }
    return preg_replace_callback('/<figure\b[^>]*\bdata-img="([^"]+)"[^>]*>/i', function ($m) use ($urls) {
        $tag = $m[0];
        $id  = $m[1];
        $url = isset($urls[$id]) ? trim((string) $urls[$id]) : '';
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return $tag; // Sin URL válida: dejar placeholder.
        }
        // Renombrar data-img="ID" -> data-loaded="1" para desactivar el placeholder CSS.
        $newTag = preg_replace('/\bdata-img="[^"]*"/i', 'data-loaded="1"', $tag);
        $img = '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="">';
        return $newTag . $img;
    }, $html);
}

// ─────────────────────────────────────────────────────────────────────────
// Publicación
// ─────────────────────────────────────────────────────────────────────────

/** Aplica el estado parseado al tablero. */
function ope_rol_mv_aplicar_estado($estado)
{
    global $db;
    if (!is_array($estado)) {
        return;
    }
    $zMetricKeys = array_keys(ope_rol_mv_zona_metrics());
    $fMetricKeys = array_keys(ope_rol_mv_faccion_metrics());

    // Zonas
    if (!empty($estado['zonas']) && is_array($estado['zonas'])) {
        foreach ($estado['zonas'] as $slug => $z) {
            if (!is_array($z)) continue;
            $upd = array();
            foreach ($zMetricKeys as $k) {
                if (isset($z[$k])) $upd[$k] = max(0, min(100, (int) $z[$k]));
            }
            if (isset($z['notas'])) $upd['notas'] = $db->escape_string((string) $z['notas']);
            if (!empty($upd)) {
                $db->update_query('rol_mv_zonas', $upd, "slug = '" . $db->escape_string((string) $slug) . "'");
            }
        }
    }
    // Facciones
    if (!empty($estado['facciones']) && is_array($estado['facciones'])) {
        foreach ($estado['facciones'] as $slug => $f) {
            if (!is_array($f)) continue;
            $upd = array();
            foreach ($fMetricKeys as $k) {
                if (!isset($f[$k])) continue;
                $upd[$k] = ($k === 'rep') ? max(-100, min(100, (int) $f[$k])) : max(0, min(100, (int) $f[$k]));
            }
            if (isset($f['notas'])) $upd['notas'] = $db->escape_string((string) $f['notas']);
            if (!empty($upd)) {
                $db->update_query('rol_mv_facciones', $upd, "slug = '" . $db->escape_string((string) $slug) . "'");
            }
        }
    }
    // Tensión POR MAR: { zona: { par: {valor,notas} | valor } }
    // Tope anti-escalada: la tensión no puede SUBIR más de OPE_MV_TENSION_MAX_UP en un
    // solo ciclo (la guerra no estalla de golpe), pero SÍ puede bajar sin límite (la paz
    // vuelve rápido). Así la guerra requiere varios meses de escalada sostenida.
    $capUp = defined('OPE_MV_TENSION_MAX_UP') ? (int) OPE_MV_TENSION_MAX_UP : 20;
    if (!empty($estado['tension']) && is_array($estado['tension'])) {
        foreach ($estado['tension'] as $zslug => $pares) {
            if (!is_array($pares)) continue;
            foreach ($pares as $par => $info) {
                $upd = array();
                $notas = null;
                if (is_array($info)) {
                    if (isset($info['valor'])) $upd['valor'] = max(0, min(100, (int) $info['valor']));
                    if (isset($info['notas'])) { $notas = (string) $info['notas']; }
                } elseif (is_numeric($info)) {
                    $upd['valor'] = max(0, min(100, (int) $info));
                }
                // Aplicar tope de subida sobre el valor actual
                if (isset($upd['valor'])) {
                    $cq = $db->simple_select('rol_mv_tension', 'valor', "zona_slug = '" . $db->escape_string((string) $zslug) . "' AND par = '" . $db->escape_string((string) $par) . "'", array('limit' => 1));
                    if ($db->num_rows($cq)) {
                        $cur = (int) $db->fetch_field($cq, 'valor');
                        if ($upd['valor'] > $cur + $capUp) {
                            $upd['valor'] = $cur + $capUp;
                        }
                    }
                }
                if ($notas !== null) $upd['notas'] = $db->escape_string($notas);
                if (!empty($upd)) {
                    $db->update_query('rol_mv_tension', $upd, "zona_slug = '" . $db->escape_string((string) $zslug) . "' AND par = '" . $db->escape_string((string) $par) . "'");
                }
            }
        }
    }
    // Arcos: reemplazo completo si se proporcionan
    if (isset($estado['arcos']) && is_array($estado['arcos'])) {
        $db->delete_query('rol_mv_arcos');
        foreach ($estado['arcos'] as $a) {
            if (!is_array($a) || empty($a['nombre'])) continue;
            $db->insert_query('rol_mv_arcos', array(
                'nombre'      => $db->escape_string((string) $a['nombre']),
                'estado'      => $db->escape_string((string) ($a['estado'] ?? 'Activo')),
                'zonas'       => $db->escape_string((string) ($a['zonas'] ?? '')),
                'facciones'   => $db->escape_string((string) ($a['facciones'] ?? '')),
                'descripcion' => $db->escape_string((string) ($a['descripcion'] ?? '')),
                'dateline'    => (int) TIME_NOW,
            ));
        }
    }
}

/**
 * Publica un ciclo: aplica el tablero, guarda periódico/noticia, crea la noticia
 * de portada, archiva el ciclo y abre el mes siguiente.
 */
function ope_rol_mv_publicar($ciclo_id, $parsed, $raw = '', $imgUrls = array())
{
    global $db;
    $ciclo = ope_rol_mv_ciclo_by_id($ciclo_id);
    if (!$ciclo) {
        return array('ok' => false, 'error' => 'Ciclo no encontrado.');
    }
    if (!empty($parsed['errores'])) {
        return array('ok' => false, 'error' => implode(' ', $parsed['errores']));
    }

    // 0) Inyectar los links de imagen pegados por el staff en el HTML antes de guardar,
    //    reemplazando los placeholders <figure data-img="ID"> por la imagen real.
    if (!empty($imgUrls) && is_array($imgUrls)) {
        $parsed['periodico'] = ope_rol_mv_inject_imagenes($parsed['periodico'], $imgUrls);
        $parsed['noticia']['cuerpo'] = ope_rol_mv_inject_imagenes($parsed['noticia']['cuerpo'], $imgUrls);
    }

    // 1) Aplicar estado al tablero
    ope_rol_mv_aplicar_estado($parsed['estado']);

    // 2) Snapshot del tablero ya actualizado
    $snapshot = ope_rol_mv_tablero();
    $snapshot_json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

    // 3) Guardar en el ciclo
    $db->update_query('rol_mv_ciclos', array(
        'estado'         => 'publicado',
        'resultado_raw'  => $db->escape_string((string) $raw),
        'periodico_html' => $db->escape_string((string) $parsed['periodico']),
        'estado_json'    => $db->escape_string((string) $snapshot_json),
        'noticia_titulo' => $db->escape_string((string) $parsed['noticia']['titulo']),
        'noticia_html'   => $db->escape_string((string) $parsed['noticia']['cuerpo']),
        'imagenes_json'  => $db->escape_string((string) $parsed['imagenes']),
        'published_at'   => (int) TIME_NOW,
    ), 'ciclo_id = ' . (int) $ciclo_id);

    // 4) Crear la noticia de portada (auto, origen mundo_vivo)
    $titulo = trim((string) $parsed['noticia']['titulo']);
    if ($titulo === '') {
        $titulo = 'El mundo ha cambiado — ' . $ciclo['periodo'];
    }
    $db->insert_query('rol_mv_noticias', array(
        'titulo'      => $db->escape_string($titulo),
        'resumen'     => $db->escape_string((string) $parsed['noticia']['resumen']),
        'cuerpo_html' => $db->escape_string((string) $parsed['noticia']['cuerpo']),
        'origen'      => 'mundo_vivo',
        'ciclo_id'    => (int) $ciclo_id,
        'activa'      => 1,
        'orden'       => 0,
        'uid_autor'   => (int) ($GLOBALS['mybb']->user['uid'] ?? 0),
        'dateline'    => (int) TIME_NOW,
    ));

    // 5) Abrir el mes siguiente si no existe
    ope_rol_mv_ciclo_actual();

    return array('ok' => true);
}

// ─────────────────────────────────────────────────────────────────────────
// Noticias (index)
// ─────────────────────────────────────────────────────────────────────────

/** Convierte "2026-07" en "Julio 2026" (etiqueta OOC / tiempo real). */
function ope_rol_mv_periodo_label($periodo)
{
    $periodo = (string) $periodo;
    if (preg_match('/^(\d{4})-(\d{2})$/', $periodo, $m)) {
        $meses = array(1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $mi = (int) $m[2];
        if (isset($meses[$mi])) {
            return $meses[$mi] . ' ' . $m[1];
        }
    }
    return $periodo;
}

/**
 * Fecha in-rol (on-rol) legible para una marca temporal. Reutiliza el calendario
 * on-rol del plugin (4 estaciones × 65 días; año en números romanos).
 * Ej.: "Día 12 · Verano · Año III".
 */
function ope_rol_mv_fecha_onrol($ts = null)
{
    if (!function_exists('ope_rol_onrol_calendar')) {
        return '';
    }
    $cal = ope_rol_onrol_calendar($ts ? (int) $ts : null);
    $anio = function_exists('ope_rol_year_label') ? ope_rol_year_label($cal['year']) : (string) $cal['year'];
    return 'Día ' . (int) $cal['day'] . ' · ' . $cal['season'] . ' · Año ' . $anio;
}

function ope_rol_mv_noticias_activas($limit = 8)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_noticias')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_noticias', '*', 'activa = 1', array('order_by' => 'orden ASC, dateline', 'order_dir' => 'DESC', 'limit' => (int) $limit));
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}
