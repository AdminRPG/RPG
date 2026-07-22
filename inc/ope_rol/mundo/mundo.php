<?php
/**
 * One Piece: Eternal · Mundo Vivo (AV-13) — capa de datos y lógica.
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

/**
 * Devuelve (creando si hace falta) el ciclo abierto del mes en curso.
 *
 * @todo Add UNIQUE INDEX on rol_mv_ciclos.periodo to prevent duplicate cycles:
 *       ALTER TABLE rol_mv_ciclos ADD UNIQUE INDEX idx_periodo (periodo);
 */
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
    try {
        $id = $db->insert_query('rol_mv_ciclos', array(
            'periodo'      => $db->escape_string($periodo),
            'estado'       => 'abierto',
            'indicaciones' => '',
            'dateline'     => (int) TIME_NOW,
        ));
        $q = $db->simple_select('rol_mv_ciclos', '*', 'ciclo_id = ' . (int) $id, array('limit' => 1));
        return $db->fetch_array($q);
    } catch (Exception $e) {
        $q = $db->simple_select('rol_mv_ciclos', '*', "periodo = '" . $db->escape_string($periodo) . "'", array('order_by' => 'ciclo_id', 'order_dir' => 'DESC', 'limit' => 1));
        return $db->fetch_array($q);
    }
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
    $q = $db->simple_select('rol_personajes', 'pid, nombre, rango, datos, mundo_zona, mundo_ubic, mundo_accion, mundo_estado_np, datos_publicos, datos_internos', "es_npc = 1 AND estado <> 'eliminado'", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) {
        $faccion = '';
        $d = @json_decode((string) $r['datos'], true);
        if (is_array($d) && !empty($d['faccion'])) {
            $faccion = (string) $d['faccion'];
        }
        $r['faccion'] = $faccion;
        $dp_raw = (string) ($r['datos_publicos'] ?? '');
        $di_raw = (string) ($r['datos_internos'] ?? '');
        $r['datos_publicos'] = array();
        $r['datos_internos'] = array();
        $dp = @json_decode($dp_raw, true);
        $di = @json_decode($di_raw, true);
        if (is_array($dp)) $r['datos_publicos'] = $dp;
        if (is_array($di)) $r['datos_internos'] = $di;
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
// Hilos narrativos, periódicos y tracking (v3)
// ─────────────────────────────────────────────────────────────────────────

function ope_rol_mv_threads_activos() {
    global $db;
    $ultimo = ope_rol_mv_ultimo_publicado();
    if (!$ultimo || empty($ultimo['estado_json'])) return array();
    $ej = json_decode($ultimo['estado_json'], true);
    if (!is_array($ej) || !isset($ej['threads']) || !is_array($ej['threads'])) return array();
    return $ej['threads'];
}

function ope_rol_mv_ultimos_periodicos($n = 3) {
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_ciclos')) return $out;
    $q = $db->simple_select('rol_mv_ciclos', 'ciclo_id, periodo, noticia_titulo, periodico_html, published_at', "published_at > 0", array('order_by' => 'published_at', 'order_dir' => 'DESC', 'limit' => (int)$n));
    while ($r = $db->fetch_array($q)) {
        $r['periodico_resumen'] = mb_substr(strip_tags((string)$r['periodico_html']), 0, 500);
        unset($r['periodico_html']);
        $out[] = $r;
    }
    return $out;
}

function ope_rol_mv_npc_tracking_from_db() {
    global $db;
    $out = array();
    $q = $db->simple_select('rol_personajes', 'pid, datos_internos', "es_npc = 1 AND estado <> 'eliminado'");
    while ($r = $db->fetch_array($q)) {
        $di = json_decode((string)$r['datos_internos'], true);
        if (!is_array($di) || !isset($di['tracking'])) continue;
        $out[(int)$r['pid']] = $di['tracking'];
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
        'cli' => array('label'=>'Clima',              'bands'=>array('Tormentoso','Inestable','Variable','Bonancible','Calma'),              'col'=>'var(--h4)'),
        'pel' => array('label'=>'Nivel de peligro',   'bands'=>array('Seguro','Bajo','Moderado','Alto','Mortal'),                          'col'=>'var(--crack)'),
        'riq' => array('label'=>'Riqueza',            'bands'=>array('Miseria','Precaria','Modesta','Próspera','Opulenta'),                'col'=>'var(--ember)'),
        'civ' => array('label'=>'Orden civil',        'bands'=>array('Anarquía','Caótico','Frágil','Ordenado','Férreo'),                  'col'=>'var(--fac-cazarrecompensas)'),
        'mar' => array('label'=>'Presencia Marine',   'bands'=>array('Nula','Escasa','Moderada','Fuerte','Dominante'),                     'col'=>'var(--fac-marine)'),
        'pir' => array('label'=>'Actividad pirata',   'bands'=>array('Insignificante','Baja','Notable','Alta','Dominante'),               'col'=>'var(--fac-pirata)'),
        'rev' => array('label'=>'Influencia revolucionaria','bands'=>array('Nula','Escasa','Moderada','Fuerte','Dominante'),              'col'=>'var(--fac-revolucionario)'),
        'inf' => array('label'=>'Inframundo',         'bands'=>array('Inexistente','Bajo','Notable','Extendido','Dominante'),              'col'=>'var(--crack)'),
        'est' => array('label'=>'Estabilidad',        'bands'=>array('Colapso','Inestable','Tensa','Estable','Próspera'),                 'col'=>'var(--patina)'),
        'ten' => array('label'=>'Tensión General',    'bands'=>array('Paz','Leve','Notable','Alta','Crítica'),                            'col'=>'var(--danger)'),
    );
}

/**
 * Metadatos de las métricas de FACCIÓN. 'rep' es especial (-100..100).
 */
function ope_rol_mv_faccion_metrics()
{
    return array(
        'rep' => array('label'=>'Reputación pública', 'special'=>'rep',                                                                           'col'=>'var(--patina)'),
        'coh' => array('label'=>'Cohesión interna',   'bands'=>array('Fracturada','Débil','Sólida','Firme','Monolítica'),                              'col'=>'var(--h4)'),
        'mil' => array('label'=>'Poder militar',      'bands'=>array('Ínfimo','Débil','Medio','Fuerte','Supremo'),                                    'col'=>'var(--crack)'),
        'pol' => array('label'=>'Influencia política','bands'=>array('Nula','Escasa','Moderada','Fuerte','Dominante'),                                'col'=>'var(--fac-civil)'),
        'eco' => array('label'=>'Recursos económicos','bands'=>array('Miseria','Precaria','Modesta','Próspera','Opulenta'),                            'col'=>'var(--ember)'),
        'mor' => array('label'=>'Moral',              'bands'=>array('Rota','Baja','Firme','Alta','Fervorosa'),                                       'col'=>'var(--fac-revolucionario)'),
        'alc' => array('label'=>'Alcance',            'bands'=>array('Local','Regional','Multimar','Global','Mundial'),                                'col'=>'var(--h4)'),
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
// Auto-clasificación de eventos por palabras clave
// ─────────────────────────────────────────────────────────────────────────

/**
 * Clasifica automáticamente un evento según su título y resumen, mediante SCORING
 * (cuenta coincidencias por categoría y elige la de mayor puntuación) en vez de la
 * primera que matchee — evita el sesgo hacia las categorías que se comprueban antes
 * cuando un evento toca varios temas a la vez (p. ej. "pirata" + "fiesta").
 *
 * El PE (Peso del Evento) es DETERMINISTA, no aleatorio: se deriva de señales
 * objetivas del propio texto (cuántas palabras clave coinciden, si aparecen términos
 * de escala grande/pequeña). Así, el mismo evento siempre produce el mismo resultado.
 *
 * IMPORTANTE: esto NO se envía a la IA en el prompt (ver ope_rol_mv_build_prompt) —
 * un resumen de un jugador es demasiado variable para que un scoring por palabras
 * clave lo pondere de forma fiable. Solo se usa para persistir un valor de referencia
 * en `rol_mv_eventos` que el panel de staff muestra como pista visual orientativa. La
 * IA clasifica cada evento por su cuenta a partir del resumen (y del hilo original si
 * lo necesita), sin conocer ni depender de esta estimación mecánica.
 *
 * @param string $titulo  Título del evento.
 * @param string $resumen Resumen del evento.
 * @return array ['tipo_suceso' => 'S-XX', 'pe_estimado' => int]
 */
function ope_rol_mv_auto_classify_evento($titulo, $resumen)
{
    $tituloN = mb_strtolower((string) ($titulo ?? ''), 'UTF-8');
    $resumenN = mb_strtolower((string) ($resumen ?? ''), 'UTF-8');
    $tr = 'áéíóúüñÁÉÍÓÚÜÑ';
    $to = 'aeiouunaeiouun';
    $tituloN = strtr($tituloN, $tr, $to);
    $resumenN = strtr($resumenN, $tr, $to);
    $texto = $tituloN . ' ' . $resumenN;

    $reglas = array(
        'S-01' => array('tormenta','clima','maremoto','huracan','tsunami','calma','viento','marea','vendaval','tifon','monzon','tempestad','lluvia'),
        'S-02' => array('barco','nave','pirata','abordaje','naval','marino','galon','navio','bergantin','corbeta','fragata','galeon','buque','flota','tripulacion'),
        'S-03' => array('tesoro','mapa','ruinas','antiguo','reliquia','descubrimiento','excavacion','artefacto','tumba','templo'),
        'S-04' => array('reunion','revolucionario','gobierno','politico','conspiracion','secreto','levantamiento','sublevacion','golpe','tirania','opresion','libertad'),
        'S-05' => array('cazarrecompensas','recompensa','cazar','cazador','bounty','caza','captura','prisionero'),
        'S-06' => array('torneo','competencia','combate','duelo','pelea','lucha','campeonato','justa','desafio','contienda'),
        'S-07' => array('enfermedad','plaga','medicina','doctor','curandero','veneno','peste','bacteria','virus','sanacion','hospital'),
        'S-08' => array('fiesta','celebracion','festival','feria','banquete','mercado','verbena','concierto','espectaculo','boda'),
        'S-09' => array('criatura','monstruo','bestia','marina','gigante','animal','leviatan','dragon','kaiju','ser','depredador'),
        'S-10' => array('profecia','oraculo','vision','augurio','leyenda','mitico','presagio','destino','maldicion','bendicion'),
        'S-11' => array('invento','cientifico','experimento','tecnologia','arma','ingenio','artilugio','mecanismo','laboratorio'),
        'S-12' => array('desastre','catastrofe','incendio','terremoto','erupcion','hundimiento','explosion','colapso'),
    );

    // Puntuar cada categoría: coincidencia en el título pesa el doble que en el resumen.
    $scores = array();
    foreach ($reglas as $codigo => $palabras) {
        $score = 0;
        foreach ($palabras as $p) {
            if (mb_strpos($tituloN, $p) !== false) $score += 2;
            if (mb_strpos($resumenN, $p) !== false) $score += 1;
        }
        if ($score > 0) $scores[$codigo] = $score;
    }

    if (empty($scores)) {
        // Sin ninguna coincidencia: categoría neutra por defecto, PE mínimo (evento anecdótico).
        return array('tipo_suceso' => 'S-02', 'pe_estimado' => 2);
    }

    arsort($scores); // mayor puntuación primero; en caso de empate, gana la de código más bajo (orden estable de $reglas)
    reset($scores);
    $codigo = key($scores);
    $mejorScore = current($scores);

    // Señales de escala: términos que indican que el suceso es grande/pequeño (PE 1-10).
    $palabrasGrandes = array('mundial','global','masacre','invasion','guerra','imperio','flota entera','cataclismo','yonko','almirante','emperador','revolucion total');
    $palabrasChicas  = array('rumor','anecdota','pequeno','menor','local','taberna','discusion');
    $bonusGrande = 0;
    foreach ($palabrasGrandes as $pg) { if (mb_strpos($texto, $pg) !== false) { $bonusGrande = 2; break; } }
    $bonusChico = 0;
    foreach ($palabrasChicas as $pc) { if (mb_strpos($texto, $pc) !== false) { $bonusChico = -1; break; } }

    // PE = base 3 + (score de la categoría ganadora, con tope) + bonus de escala.
    $pe = 3 + min(3, $mejorScore - 1) + $bonusGrande + $bonusChico;
    $pe = max(1, min(10, $pe));

    return array('tipo_suceso' => $codigo, 'pe_estimado' => $pe);
}

/**
 * Clasifica y persiste en DB todos los eventos sin clasificar de un ciclo.
 * @param int $ciclo_id
 */
function ope_rol_mv_auto_classify_pendientes($ciclo_id)
{
    global $db;
    $PREFIX = TABLE_PREFIX;
    $q = $db->simple_select('rol_mv_eventos', 'evento_id, titulo, resumen', "ciclo_id=" . (int)$ciclo_id . " AND (tipo_suceso IS NULL OR tipo_suceso='')");
    while ($r = $db->fetch_array($q)) {
        $cl = ope_rol_mv_auto_classify_evento($r['titulo'], $r['resumen']);
        $db->update_query('rol_mv_eventos', array(
            'tipo_suceso' => $db->escape_string($cl['tipo_suceso']),
            'pe_estimado' => $cl['pe_estimado'],
        ), 'evento_id=' . (int)$r['evento_id']);
    }
}

/**
 * Resuelve un slug de zona a partir de lo que la IA devuelva en 'ubicacion_zona'
 * (puede venir como slug 'east-blue' o como nombre 'East Blue'). Si no se reconoce,
 * devuelve '' y NO se sobrescribe la zona actual del NPC (mejor no tocar que borrar
 * un dato bueno con uno irreconocible).
 */
function ope_rol_mv_resolver_zona_slug($valor)
{
    global $db;
    $valor = trim((string) $valor);
    if ($valor === '') return '';
    $zonas = ope_rol_mv_zonas();
    if (isset($zonas[$valor])) return $valor; // ya es un slug válido
    $norm = mb_strtolower($valor, 'UTF-8');
    foreach ($zonas as $slug => $z) {
        if (mb_strtolower((string) $z['nombre'], 'UTF-8') === $norm) return $slug;
    }
    return '';
}

/**
 * Sincroniza la ubicación/acción PÚBLICA de un NPC mayor (mundo_zona, mundo_accion,
 * mundo_estado_np) a partir del tracking devuelto por la IA. Sin esto, el panel de
 * "Estado del Mundo" y ope_rol_mv_auto_nav_resumen() muestran SIEMPRE la ubicación
 * con la que se creó el NPC, aunque la IA lo mueva por el mundo cada mes.
 */
function ope_rol_mv_sync_npc_ubicacion($pid, array $tracking)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1) return;
    $upd = array();
    $slug = ope_rol_mv_resolver_zona_slug($tracking['ubicacion_zona'] ?? '');
    if ($slug !== '') $upd['mundo_zona'] = $db->escape_string($slug);
    if (isset($tracking['plan_activo']) && trim((string) $tracking['plan_activo']) !== '') {
        $upd['mundo_accion'] = $db->escape_string(trim((string) $tracking['plan_activo']));
    }
    $salud = isset($tracking['salud']) ? (int) $tracking['salud'] : 100;
    $moral = isset($tracking['moral']) ? (int) $tracking['moral'] : 100;
    if ($salud <= 0) {
        $upd['mundo_estado_np'] = 'Muerto';
    } elseif ($salud < 40) {
        $upd['mundo_estado_np'] = 'Herido';
    } elseif ($moral < 30) {
        $upd['mundo_estado_np'] = 'Moral baja';
    } else {
        $upd['mundo_estado_np'] = 'Activo';
    }
    if (!empty($upd)) {
        $db->update_query('rol_personajes', $upd, "pid = {$pid}");
    }
}

/**
 * Genera automáticamente el resumen de navegación a partir de los NPCs mayores.
 * @return string
 */
function ope_rol_mv_auto_nav_resumen()
{
    global $db;
    $navegantes = array();
    $q = $db->simple_select('rol_personajes', 'nombre, mundo_zona, mundo_ubic, mundo_accion', "es_npc=1 AND estado<>'eliminado' AND (mundo_zona!='' OR mundo_ubic!='' OR mundo_accion!='')", array('order_by' => 'nombre'));
    while ($r = $db->fetch_array($q)) {
        $linea = $r['nombre'];
        if (!empty($r['mundo_zona'])) $linea .= ' | ' . $r['mundo_zona'];
        if (!empty($r['mundo_ubic'])) $linea .= ', ' . $r['mundo_ubic'];
        if (!empty($r['mundo_accion'])) $linea .= ' - ' . $r['mundo_accion'];
        $navegantes[] = $linea;
    }
    if (empty($navegantes)) {
        return "Sin movimientos relevantes este mes.";
    }
    return "Personajes en movimiento:\n" . implode("\n", $navegantes) . "\n\nViajes y rutas maritimas transcurren con normalidad en la mayoria de los mares, salvo donde la tension o los fenomenos naturales alteran las travesias.";
}

// ─────────────────────────────────────────────────────────────────────────
// Generación del super-prompt
// ─────────────────────────────────────────────────────────────────────────

/** Construye el super-prompt autocontenido para la IA externa (v3). */
function ope_rol_mv_build_prompt($ciclo)
{
    global $db;
    if (!is_array($ciclo)) {
        return '';
    }
    $tablero   = ope_rol_mv_tablero();
    // v4: se incluyen TODOS los eventos notificados del ciclo, sin curación manual de
    // "incluir/descartar" ni preclasificación mecánica — la IA recibe solo título,
    // enlace y resumen de cada uno, y decide ella misma tipo/peso (ver instrucción
    // en la sección "EVENTOS NOTIFICADOS ESTE MES" más abajo).
    $eventos   = ope_rol_mv_eventos((int) $ciclo['ciclo_id']);
    $misiones  = ope_rol_mv_misiones((int) $ciclo['ciclo_id']);
    $misionesEnCurso = array_values(array_filter($misiones, function ($m) { return $m['estado'] === 'en_curso'; }));
    $npcs      = ope_rol_mv_npc_mayores();
    $menores   = ope_rol_mv_npc_menores((int) $ciclo['ciclo_id']);
    $threads   = ope_rol_mv_threads_activos();
    $periodicos = ope_rol_mv_ultimos_periodicos(3);

    $L = array();
    $L[] = "###############################################################################";
    $L[] = "#  MUNDO VIVO · \"LA BALANZA\" v3  —  MOTOR NARRATIVO DE GRANBLUE FANTASY: ETERNAL";
    $L[] = "###############################################################################";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 1 · QUIÉN ERES Y QUÉ RECIBES";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "Eres el MOTOR NARRATIVO del foro de rol \"One Piece: Eternal\". Trabajas sobre un mundo";
    $L[] = "persistente inspirado en One Piece pero con su propia continuidad (NO copies la trama";
    $L[] = "del manga; respeta el TONO: aventura, mar, libertad, Marines vs piratas, revolucionarios,";
    $L[] = "Gobierno Mundial, Reyes del Mar, islas peligrosas).";
    $L[] = "";
    $L[] = "Recibes tres cosas cada mes:";
    $L[] = "  (a) el ESTADO ACTUAL del mundo completo (métricas de cada mar, facciones, tensiones,";
    $L[] = "      arcos narrativos, hilos en curso, NPCs con su ubicación y plan).";
    $L[] = "  (b) TODO lo ocurrido ESTE MES: eventos notificados por jugadores (con resumen,";
    $L[] = "      zona y personaje), misiones en curso/completadas/fallidas, movimientos de NPCs,";
    $L[] = "      y el resumen de navegación (viajes, naufragios, descubrimientos).";
    $L[] = "  (c) las INDICACIONES del staff (si las hay), que tienes OBLIGACIÓN de seguir.";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 2 · TIENES ACCESO (LECTURA) A LA BASE DE DATOS POR MCP";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "Si tienes herramientas MCP conectadas con acceso a esta base de datos, puedes";
    $L[] = "consultarla en tiempo real (SELECT) sobre estas tablas (prefijo: mybb_) para";
    $L[] = "verificar o ampliar cualquier dato de este prompt — por ejemplo si algo te parece";
    $L[] = "desactualizado, ambiguo, o quieres más contexto histórico del que cabe aquí abajo.";
    $L[] = "No hace falta que consultes todo cada vez — solo lo que necesites para resolver dudas.";
    $L[] = "IMPORTANTE: es acceso de SOLO LECTURA a efectos de este ejercicio — NUNCA ejecutes";
    $L[] = "INSERT/UPDATE/DELETE aunque técnicamente puedas, y NUNCA asumas que ya está \"guardado\"";
    $L[] = "algo que no hayas devuelto también en los bloques de la sección 6. El único canal";
    $L[] = "oficial de salida es tu respuesta de texto con los 6 bloques; el staff la pega en el";
    $L[] = "panel y el sistema aplica los cambios él mismo (con topes de seguridad incluidos).";
    $L[] = "Si NO tienes acceso MCP a esta base de datos, ignora esta sección y trabaja solo con";
    $L[] = "los datos ya incluidos más abajo — están completos y son suficientes.";
    $L[] = "";
    $L[] = "  Tabla                     | Para qué consultarla";
    $L[] = "  -------------------------|------------------------------------------------------";
    $L[] = "  rol_mv_ciclos            | Ciclos pasados: periodico_html, estado_json (threads,";
    $L[] = "                          |   npc_tracking), noticia_titulo, indicaciones, nav_resumen";
    $L[] = "  rol_mv_zonas            | Métricas actuales de cada zona (cli, pel, riq, civ, mar,";
    $L[] = "                          |   pir, rev, inf, est, ten + notas)";
    $L[] = "  rol_mv_facciones        | Métricas actuales de cada facción (rep, coh, mil, pol,";
    $L[] = "                          |   eco, mor, alc + notas)";
    $L[] = "  rol_mv_tension          | Tensión entre facciones por mar (valor 0-100 + notas)";
    $L[] = "  rol_mv_arcos            | Arcos narrativos abiertos (nombre, estado, zonas,";
    $L[] = "                          |   facciones, descripción)";
    $L[] = "  rol_mv_eventos          | Eventos notificados este ciclo por los jugadores";
    $L[] = "  rol_mv_misiones         | Misiones del ciclo actual (en curso/completadas/fallidas)";
    $L[] = "  rol_mv_npc_menores      | NPCs menores (relleno) registrados este ciclo";
    $L[] = "  rol_personajes (es_npc=1)| NPCs mayores con ficha: datos_publicos (JSON visible para";
    $L[] = "                          |   jugadores), datos_internos (JSON solo staff/IA con";
    $L[] = "                          |   personalidad, metas, tracking: salud, moral, plan, ubicación)";
    $L[] = "  rol_mv_periodicos       | Periódicos anteriores (para mantener continuidad narrativa)";
    $L[] = "  threads                 | El hilo COMPLETO de cada evento (JOIN por tid). Consúltala";
    $L[] = "                          |   cuando el resumen de un evento no te baste para juzgar su";
    $L[] = "                          |   peso real (ver sección 4 más abajo, es importante)";
    $L[] = "  posts (WHERE tid=...)   | Los mensajes de ese hilo, en orden, para leer qué pasó de";
    $L[] = "                          |   verdad antes de clasificar el evento";
    $L[] = "";
    $L[] = "Cuándo consultar cada tabla:";
    $L[] = "  · Si dudas del histórico: rol_mv_ciclos WHERE estado='publicado' ORDER BY ciclo_id DESC LIMIT 3";
    $L[] = "  · Si quieres los valores exactos de métricas: rol_mv_zonas";
    $L[] = "  · Si quieres tracking detallado de un NPC: rol_personajes WHERE es_npc=1 AND pid=X";
    $L[] = "  · Si quieres ver cómo se redactó un periódico anterior: rol_mv_ciclos.periodico_html";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 3 · FLUJO DE TRABAJO (sigue estos pasos en orden)";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "PASO 1 — ABSORBE el contexto completo:";
    $L[] = "  · Lee el estado actual del mundo (secciones abajo)";
    $L[] = "  · Lee los periódicos de los últimos 3 meses (incluidos en las secciones) para";
    $L[] = "    mantener el tono y estilo";
    $L[] = "  · Lee los hilos narrativos abiertos del último ciclo";
    $L[] = "  · Si hay indicaciones del staff, intégralas AHORA en tu razonamiento";
    $L[] = "";
    $L[] = "PASO 2 — CLASIFICA cada evento y misión:";
    $L[] = "  · Asigna a cada suceso un tipo S-01 a S-12 (ver tabla en sección 5)";
    $L[] = "  · Estima su Peso de Evento (PE 1-10) basado en la escala";
    $L[] = "  · Identifica el rango más alto entre participantes para calcular MR";
    $L[] = "  · Determina FR (relevancia narrativa: 0.5-2.0)";
    $L[] = "  · Calcula FA (acumulación: si hay múltiples sucesos similares en la misma zona)";
    $L[] = "";
    $L[] = "PASO 3 — APLICA REGRESIÓN A LA CALMA:";
    $L[] = "  · Aplica la tabla de regresión (sección 6) a TODAS las métricas de zona y facción";
    $L[] = "  · La regresión se aplica INCLUSO si hay eventos — primero regresa, luego suma";
    $L[] = "  · Respeta las excepciones (arcos activos, inercia narrativa, indicaciones staff)";
    $L[] = "";
    $L[] = "PASO 4 — CALCULA IMPACTOS:";
    $L[] = "  · Para cada suceso: IMPACTO_NETO = (PE × MR × FR × FA) / 10";
    $L[] = "  · Distribuye según la huella de su tipo S-XX (ppal ×1.0, sec ×0.5, ter ×0.3)";
    $L[] = "  · Aplica los topes anti-escalada (ver sección 5.3) — NUNCA los superes";
    $L[] = "  · Si el IMPACTO_NETO es 0 tras redondeo, el suceso no mueve métricas";
    $L[] = "";
    $L[] = "PASO 5 — EVOLUCIONA LOS NPCs:";
    $L[] = "  · Para cada NPC mayor listado, evalúa el MOTOR DE DECISIÓN (sección 8)";
    $L[] = "  · Actualiza salud, moral, plan_activo, ubicacion_zona, meta_actual";
    $L[] = "  · Si el NPC inicia una acción, clasifícala como suceso (vuelve al paso 2)";
    $L[] = "";
    $L[] = "PASO 6 — EVOLUCIONA LOS HILOS NARRATIVOS:";
    $L[] = "  · Revisa los threads del ciclo anterior (se abajo, sección HILOS)";
    $L[] = "  · Decide: evolucionar, mantener latente, cerrar, o fusionar";
    $L[] = "  · Como mínimo 1 thread debe evolucionar o cerrarse este ciclo";
    $L[] = "  · Máximo 12 threads activos";
    $L[] = "  · Si un thread no ha cambiado en 3 ciclos, ciérralo como latente";
    $L[] = "  · Los threads nuevos surgen de eventos importantes (PE 5+ o que involucren NPCs mayores)";
    $L[] = "";
    $L[] = "PASO 7 — GENERA LOS 5 ENTREGABLES:";
    $L[] = "  · ESTADO_JSON con el nuevo tablero + threads + npc_tracking";
    $L[] = "  · PERIODICO_HTML en formato periódico in-world";
    $L[] = "  · NOTICIA de portada (titular + resumen + cuerpo)";
    $L[] = "  · MISIONES (2-5 ganchos narrativos para el próximo mes)";
    $L[] = "  · IMAGENES (prompts en inglés para las ilustraciones del periódico)";
    $L[] = "  · Incluye SIEMPRE los 5 bloques aunque algún bloque tenga contenido mínimo";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 4 · PRINCIPIOS RECTORES (v3) — NUNCA LOS VIOLES";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "1. LA PAZ ES EL ESTADO NORMAL. La mayoría de los meses el mundo está en calma o con";
    $L[] = "   conflictos pequeños y localizados. Solo excepcionalmente hay guerras.";
    $L[] = "2. REGRESIÓN A LA CALMA. El mundo siempre tiende a sus valores base. Sin eventos, se";
    $L[] = "   pacifica automáticamente cada mes.";
    $L[] = "3. NO ESCALADA SIN CAUSA SOSTENIDA. Ningún evento aislado causa una guerra. Se necesitan";
    $L[] = "   4-5 meses de tensión alimentada para llegar a un conflicto abierto entre facciones.";
    $L[] = "4. PROPORCIONALIDAD. Evento pequeño = impacto pequeño. PE 1-3 apenas mueven métricas.";
    $L[] = "   Solo eventos PE 8+ mueven múltiples métricas significativamente.";
    $L[] = "5. CONTINUIDAD NARRATIVA. El periódico de este mes NO olvida los anteriores. Los hilos";
    $L[] = "   abiertos evolucionan. Las noticias pasadas tienen consecuencias futuras.";
    $L[] = "6. EL STAFF SOLO PONE NOTAS/INDICACIONES. Todo lo demás (clasificación, impacto,";
    $L[] = "   cálculo, generación del periódico) lo haces TÚ como IA.";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 5 · SISTEMA DE IMPACTO v3 — CLASIFICACIÓN, FÓRMULA, TOPES";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "5.1 — CLASIFICACIÓN S-01 a S-12";
    $L[] = "";
    $L[] = "Cada suceso (evento notificado, misión completada, acción de NPC) se clasifica en";
    $L[] = "UNO de estos 12 tipos. La clasificación determina qué métricas se modifican:";
    $L[] = "";
    $L[] = "  S-01 Combate (gana Marine/Gob)  → MAR +, PIR -, CIV +";
    $L[] = "    Ej: patrulla Marine captura piratas, base pirate destruida";
    $L[] = "";
    $L[] = "  S-02 Combate (gana Pirata)       → PIR +, TEN +, MAR -";
    $L[] = "    Ej: barco Marine hundido, base Marine asaltada, marine derrotado";
    $L[] = "";
    $L[] = "  S-03 Combate (gana Rev)          → REV +, MAR -, EST -";
    $L[] = "    Ej: gobierno expuesto, arsenal robado, presos liberados";
    $L[] = "";
    $L[] = "  S-04 Combate (gana Caza)         → REP cazarrecompensas +";
    $L[] = "    Ej: recompensa cobrada, pirata entregado a la Marina";
    $L[] = "";
    $L[] = "  S-05 Diplomacia/Paz              → TEN -, EST +, CIV +";
    $L[] = "    Ej: alianza firmada, tratado de paz, mediación, tregua, festival";
    $L[] = "";
    $L[] = "  S-06 Crimen/Inframundo           → INF +, CIV -, EST -";
    $L[] = "    Ej: robo, asesinato, mercado negro, soborno, contrabando";
    $L[] = "";
    $L[] = "  S-07 Exploración                 → RIQ +, PEL -";
    $L[] = "    Ej: isla descubierta, ruina explorada, tesoro encontrado";
    $L[] = "";
    $L[] = "  S-08 Catástrofe Natural          → EST -×2, CIV -, PEL +";
    $L[] = "    Ej: tormenta, tsunami, erupción, plaga, incendio";
    $L[] = "";
    $L[] = "  S-09 Construcción/Mejora         → RIQ +, CIV +";
    $L[] = "    Ej: base construida, barco mejorado, comercio abierto";
    $L[] = "";
    $L[] = "  S-10 Narrativo/Trama             → variable ±2 (según contexto)";
    $L[] = "    Ej: revelación, giro argumental, encuentro con NPC clave";
    $L[] = "";
    $L[] = "  S-11 Derrota/Huida               → facción afectada: MOR -, REP -";
    $L[] = "    Ej: personaje huye, pierde combate, barco gravemente dañado";
    $L[] = "";
    $L[] = "  S-12 Evento Social               → CIV +, TEN -, EST +";
    $L[] = "    Ej: fiesta, torneo, boda, funeral, subasta";
    $L[] = "";
    $L[] = "  Huella por suceso (cómo se distribuye el impacto entre las métricas):";
    $L[] = "    · Principal (ppal): ×1.0 del IMPACTO_NETO";
    $L[] = "    · Secundaria (sec): ×0.5 del IMPACTO_NETO";
    $L[] = "    · Terciaria (ter): ×0.3 del IMPACTO_NETO";
    $L[] = "  Además, efectos en facción: MOR/REP según tabla específica (S-01 sube MOR Marine,";
    $L[] = "  S-02 sube MOR Pirata, S-08 baja REP Gobierno, etc.)";
    $L[] = "";
    $L[] = "  Un suceso con PE ≤ 3 SOLO afecta a la zona donde ocurrió.";
    $L[] = "  Un suceso con PE ≥ 4 puede afectar zonas adyacentes con 50% de intensidad.";
    $L[] = "  Tensión (TEN) solo sube si el suceso enfrenta directamente a dos facciones.";
    $L[] = "";
    $L[] = "5.2 — FÓRMULA DE IMPACTO";
    $L[] = "";
    $L[] = "  IMPACTO_BRUTO = PE × MR × FR × FA";
    $L[] = "  IMPACTO_NETO  = redondear(IMPACTO_BRUTO / 10), mínimo 0";
    $L[] = "";
    $L[] = "  PE — Peso del Evento (1-10):";
    $L[] = "     1 anecdótico (pelea de taberna) · 2 personal (duelo 1v1)";
    $L[] = "     3 local (afecta aldea) · 4 insular-bajo (afecta isla)";
    $L[] = "     5 insular-alto (conmociona isla entera) · 6 regional-bajo (varias islas)";
    $L[] = "     7 regional-alto (todo un mar) · 8 global-bajo (múltiples mares)";
    $L[] = "     9 global-alto (sacude el mundo, muerte figura mayor) · 10 cataclísmico";
    $L[] = "";
    $L[] = "  MR — Multiplicador de Rango (del participante de mayor rango):";
    $L[] = "     F ×0.3 · E ×0.5 · D ×0.7 · C ×1.0 · B ×1.3 · A ×1.6";
    $L[] = "     S ×2.0 · SS ×2.5 · M ×3.0 · M+ ×4.0";
    $L[] = "";
    $L[] = "  FR — Factor de Relevancia:";
    $L[] = "     0.5 = aislado (sin conexión con tramas activas)";
    $L[] = "     1.0 = conectado (involucra facción, zona o personaje relevante)";
    $L[] = "     1.5 = NPC mayor o arco activo";
    $L[] = "     2.0 = CLAVE para un arco (punto de inflexión narrativa)";
    $L[] = "";
    $L[] = "  FA — Factor de Acumulación (sucesos similares en misma zona este ciclo):";
    $L[] = "     1 suceso → ×1.0 · 2-3 sucesos → ×1.3 · 4-6 → ×1.7 · 7+ → ×2.0";
    $L[] = "     Si los sucesos forman parte de la misma cadena narrativa: +0.5 extra";
    $L[] = "";
    $L[] = "  Ejemplo práctico: un combate de un personaje rango A (MR=1.6) que derrota a un";
    $L[] = "  pirata (S-02), con PE=4 (insular-bajo), FR=1.0 (conectado), FA=1.0 (único):";
    $L[] = "  IMPACTO_BRUTO = 4 × 1.6 × 1.0 × 1.0 = 6.4";
    $L[] = "  IMPACTO_NETO = 6.4 / 10 = 0.64 → redondeado = 1";
    $L[] = "  → PIR += 1, TEN += 0 (0.5 redondeado a 0), MAR -= 0 (0.3 redondeado a 0)";
    $L[] = "";
    $L[] = "5.3 — TOPES ANTI-ESCALADA (OBLIGATORIOS, aplican SIEMPRE)";
    $L[] = "";
    $L[] = "  POR CICLO (por zona/métrica):";
    $L[] = "    · Cualquier métrica individual: máximo ±15 por ciclo";
    $L[] = "    · Tensión General (TEN): máximo +12 por ciclo";
    $L[] = "";
    $L[] = "  POR SUCESO INDIVIDUAL:";
    $L[] = "    · Ningún suceso puede cambiar una métrica más de ±6";
    $L[] = "    · PE 1-3: máximo ±2 en una métrica";
    $L[] = "    · PE 4-6: máximo ±4 en una métrica";
    $L[] = "    · PE 7-10: máximo ±6 en una métrica";
    $L[] = "";
    $L[] = "  REGLA DE GUERRA:";
    $L[] = "    · Tensión > 80 en un par requiere 3+ ciclos consecutivos con tensión > 70 en ese par";
    $L[] = "    · Si la tensión baja de 70 en algún ciclo, el contador se reinicia";
    $L[] = "    · Guerra abierta (tensión > 90) SOLO si tensión > 80 el ciclo anterior";
    $L[] = "";
    $L[] = "  REGLA DE MÍNIMO IMPACTO:";
    $L[] = "    · Si no hubo eventos en una zona: -3 a -8 de regresión obligatoria";
    $L[] = "    · PE muy bajo (1-2) con rango bajo (F-D), FR y FA mínimos:";
    $L[] = "      IMPACTO_NETO = 0. Es intencional. Acciones triviales no mueven el mundo.";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 6 · REGRESIÓN A LA CALMA (aplícala SIEMPRE, es el PRIMER paso del cálculo)";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "Antes de sumar NINGÚN impacto, cada métrica regresa hacia su valor base.";
    $L[] = "Esto evita que el mundo se desvíe permanentemente por un solo ciclo de eventos.";
    $L[] = "";
    $L[] = "6.1 — REGRESIÓN POR MÉTRICA DE ZONA (hacia su valor base):";
    $L[] = "";
    $L[] = "  Métrica  | Regresión mensual    | Velocidad | Significado narrativo";
    $L[] = "  ---------|---------------------|-----------|--------------------------------";
    $L[] = "  CLI      | vuelve 5-10 a 60    | Rápida    | El clima se normaliza solo";
    $L[] = "  PEL      | baja 3-8 a 30       | Media     | Los peligros marinos disminuyen";
    $L[] = "  RIQ      | sube 2-5 a 50       | Lenta     | La economía se recupera despacio";
    $L[] = "  CIV      | sube 3-8 a 55       | Media     | El orden civil se recompone";
    $L[] = "  MAR      | tiende 3-6 a 45     | Media     | La Marina se redistribuye";
    $L[] = "  PIR      | baja 3-8 a 25       | Media-ráp | Piratas se cansan o se van";
    $L[] = "  REV      | baja 2-5 a 15       | Lenta     | Células durmientes se repliegan";
    $L[] = "  INF      | baja 2-5 a 20       | Lenta     | El crimen se repliega";
    $L[] = "  EST      | sube 4-8 a 55       | Media     | La calma y estabilidad vuelven";
    $L[] = "  TEN      | baja 5-12 a 20      | Rápida    | Las tensiones se enfrían rápido";
    $L[] = "";
    $L[] = "  NOTA: los valores de regresión son un RANGO (ej: 5-10). Elige un valor dentro";
    $L[] = "  del rango según qué tan extrema esté la métrica. Si CLI=90, regresa más (10)";
    $L[] = "  hacia 60. Si CLI=65 (cerca de base), regresa menos (5).";
    $L[] = "";
    $L[] = "6.2 — REGRESIÓN POR MÉTRICA DE FACCIÓN (global):";
    $L[] = "";
    $L[] = "  REP | tiende 3-5 hacia su base de facción | Lenta";
    $L[] = "  COH | tiende 2-4 hacia 50                | Muy lenta";
    $L[] = "  MIL | se mantiene ±1 (solo cambia por   | Muy lenta";
    $L[] = "       | eventos masivos PE 7+)";
    $L[] = "  POL | tiende 2-3 hacia su base de facción| Lenta";
    $L[] = "  ECO | sube 1-3 hacia 50                  | Muy lenta";
    $L[] = "  MOR | tiende 3-6 hacia 50                | Media";
    $L[] = "  ALC | se mantiene (solo cambia por       | Estática";
    $L[] = "       | guerras/conquistas)";
    $L[] = "";
    $L[] = "6.3 — EXCEPCIONES A LA REGRESIÓN";
    $L[] = "";
    $L[] = "  La regresión NO se aplica (o al 50%) si:";
    $L[] = "  · Hay un arco activo en la zona que justifique mantener la tensión";
    $L[] = "    (ej: \"La Guerra del West Blue\" mantiene TEN elevada)";
    $L[] = "  · Las indicaciones del staff especifican mantener ciertas métricas";
    $L[] = "  · La métrica está dentro de ±5 de su valor base (ya está en equilibrio)";
    $L[] = "";
    $L[] = "  INERCIA NARRATIVA (el mundo tiene memoria):";
    $L[] = "  · Si una métrica ha estado >70 durante 3+ ciclos → regresión al 50%";
    $L[] = "    (el mundo se ha \"acostumbrado\" a ese estado)";
    $L[] = "  · Si una métrica ha estado <30 durante 3+ ciclos → regresión ×1.5";
    $L[] = "    (el mundo \"quiere\" normalizarse con más fuerza)";
    $L[] = "";
    $L[] = "  DURACIÓN MÁXIMA DE SUSPENSIÓN:";
    $L[] = "  · Un arco no puede suspender la regresión en una misma zona por más de";
    $L[] = "    6 ciclos consecutivos. Pasado ese límite, la regresión se reanuda al 100%.";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 7 · MÉTRICAS DEL TABLERO (guía de referencia)";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "7.1 — MÉTRICAS DE ZONA (0-100 cada una, por cada uno de los 8 mares)";
    $L[] = "";
    $L[] = "  CLI (Clima) — base 60. Mide la calidad del clima predominante.";
    $L[] = "    0 = tormentas perpetuas, 100 = calma absoluta.";
    $L[] = "    Afecta a: navegación (tirada de clima).";
    $L[] = "";
    $L[] = "  PEL (Peligro Marítimo) — base 30. Peligros del mar (Sea Kings, corrientes).";
    $L[] = "    0 = mar seguro, 100 = muerte casi segura al zarpar.";
    $L[] = "    Afecta a: navegación (tirada de peligros).";
    $L[] = "";
    $L[] = "  RIQ (Riqueza) — base 50. Recursos naturales y económicos de la zona.";
    $L[] = "    0 = pobreza extrema, 100 = el Dorado.";
    $L[] = "    Afecta a: hallazgos en viajes, recompensas de misiones.";
    $L[] = "";
    $L[] = "  CIV (Orden Civil) — base 55. Control y legalidad en las islas.";
    $L[] = "    0 = anarquía total, 100 = régimen férreo.";
    $L[] = "    Afecta a: eventos en isla, probabilidad de incidentes.";
    $L[] = "";
    $L[] = "  MAR (Presión Marine) — base 45. Presencia e influencia de la Marina.";
    $L[] = "    0 = sin Marines, 100 = cuartel general en cada isla.";
    $L[] = "    Afecta a: encuentros en viaje, libertad de movimiento pirata.";
    $L[] = "";
    $L[] = "  PIR (Actividad Pirata) — base 25. Nivel de actividad pirata.";
    $L[] = "    0 = mares limpios, 100 = invasión pirata.";
    $L[] = "    Afecta a: encuentros en viaje, misiones generadas.";
    $L[] = "";
    $L[] = "  REV (Influencia Revolucionaria) — base 15. Penetración del Ejército Rev.";
    $L[] = "    0 = sin revolucionarios, 100 = bastión rebelde.";
    $L[] = "    Afecta a: tramas secretas, misiones de facción.";
    $L[] = "";
    $L[] = "  INF (Influencia del Inframundo) — base 20. Poder del crimen organizado.";
    $L[] = "    0 = crimen inexistente, 100 = paraíso del hampa.";
    $L[] = "    Afecta a: misiones de inframundo, eventos ilegales.";
    $L[] = "";
    $L[] = "  EST (Estabilidad General) — base ~55. Salud global de la zona.";
    $L[] = "    Fórmula: (CLI×0.5 - PEL×0.5 + RIQ×1 + CIV×2 + MAR×1 - PIR×1 - REV×0.5 - INF×0.5) / 7";
    $L[] = "    0 = colapso, 100 = utopía. NO se modifica directamente por eventos";
    $L[] = "    (se recalcula automáticamente con la fórmula al cambiar las demás métricas).";
    $L[] = "";
    $L[] = "  TEN (Tensión General) — base 20. Tensión global entre facciones en el mar.";
    $L[] = "    0 = paz absoluta, 100 = guerra total entre todos.";
    $L[] = "    Afecta a: umbral de guerra, misiones de conflicto.";
    $L[] = "";
    $L[] = "7.2 — MÉTRICAS DE FACCIÓN (globales, 7 por cada una de las 6 facciones)";
    $L[] = "";
    $L[] = "  REP (Reputación) — Rango: -100 a 100. Percepción pública mundial.";
    $L[] = "    Negativa = odiada. Positiva = querida/respetada.";
    $L[] = "";
    $L[] = "  COH (Cohesión) — 0-100. Unidad interna, lealtad entre miembros.";
    $L[] = "    0 = facción fracturada, 100 = lealtad absoluta.";
    $L[] = "";
    $L[] = "  MIL (Poder Militar) — 0-100. Capacidad bélica.";
    $L[] = "    0 = desarmados, 100 = superpotencia militar.";
    $L[] = "";
    $L[] = "  POL (Influencia Política) — 0-100. Poder diplomático/administrativo.";
    $L[] = "    0 = sin voz, 100 = maneja gobiernos.";
    $L[] = "";
    $L[] = "  ECO (Recursos Económicos) — 0-100. Finanzas, suministros.";
    $L[] = "    0 = en bancarrota, 100 = cofre del tesoro infinito.";
    $L[] = "";
    $L[] = "  MOR (Moral) — 0-100. Moral de tropas/miembros.";
    $L[] = "    0 = depresión/deserción, 100 = euforia/combatividad.";
    $L[] = "";
    $L[] = "  ALC (Alcance) — 0-100. Presencia en cuántos mares del mundo.";
    $L[] = "    0 = confinados a una isla, 100 = presentes en los 8 mares.";
    $L[] = "";
    $L[] = "7.3 — TENSIÓN ENTRE FACCIONES (por mar)";
    $L[] = "";
    $L[] = "  Cada uno de los 15 pares canónicos tiene un valor DISTINTO (0-100) en CADA mar,";
    $L[] = "  con NOTAS que explican el porqué de esa tensión en ese mar específico.";
    $L[] = "";
    $L[] = "  Pares canónicos (orden): marine|pirata, marine|revolucionario, marine|gobierno,";
    $L[] = "  marine|cazarrecompensas, marine|civil, pirata|revolucionario, pirata|gobierno,";
    $L[] = "  pirata|cazarrecompensas, pirata|civil, revolucionario|gobierno,";
    $L[] = "  revolucionario|cazarrecompensas, revolucionario|civil, gobierno|cazarrecompensas,";
    $L[] = "  gobierno|civil, cazarrecompensas|civil";
    $L[] = "";
    $L[] = "  UMBRALES DE TENSIÓN:";
    $L[] = "    0-30  = PAZ (relaciones normales o indiferencia)";
    $L[] = "    31-55 = FRICCIÓN (roces, desconfianza, incidentes menores)";
    $L[] = "    56-75 = CONFLICTO LOCALIZADO (escaramuzas, patrullas hostiles)";
    $L[] = "    76-89 = GUERRA INMINENTE (movilizaciones, ataques directos)";
    $L[] = "    90-100 = GUERRA ABIERTA (conflicto declarado, sin cuartel)";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 8 · NPCs MAYORES — MOTOR DE DECISIÓN (cómo decidir qué hace cada NPC)";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "Cada NPC mayor tiene una personalidad definida por 6 ejes (0-100):";
    $L[] = "  AGR (Agresividad) - tendencia a iniciar conflicto";
    $L[] = "  VAL (Valentía) - disposición a enfrentar peligro";
    $L[] = "  HON (Honor) - código moral, lealtad a principios";
    $L[] = "  LEA (Lealtad) - lealtad a su facción/superiores";
    $L[] = "  AMB (Ambición) - impulso por ascender, acumular poder";
    $L[] = "  INT (Inteligencia) - capacidad estratégica";
    $L[] = "";
    $L[] = "Cada NPC tiene 1-3 METAS activas y un TRACKING por ciclo:";
    $L[] = "  salud (0-100, 100=sano), moral (0-100, 100=alta)";
    $L[] = "  plan_activo (texto: qué está haciendo ahora)";
    $L[] = "  ubicacion_zona (dónde está)";
    $L[] = "  meta_actual (qué meta persigue ahora)";
    $L[] = "";
    $L[] = "8.1 — TRIGGERS DE ACCIÓN (evalúalos EN ORDEN para cada NPC)";
    $L[] = "";
    $L[] = "  #1  Amenaza directa: NPC enemigo en misma zona con AGR > 60 → atacar/emboscar";
    $L[] = "  #2  Oportunidad de meta: métrica clave para su meta subió/bajó >10 → actuar";
    $L[] = "  #3  Vacío de poder: MAR o CIV < 25 en su zona → ocupar/declarar control";
    $L[] = "  #4  Crisis facción: MIL o MOR de su facción < 30 → reagrupar/pedir refuerzos";
    $L[] = "  #5  Tensión crítica: TEN > 70 entre su facción y otra en su zona → preparar guerra";
    $L[] = "  #6  Hilo activo: thread que le involucra con estado=activo → avanzar el hilo";
    $L[] = "  #7  Invitación jugador: jugador le contactó este ciclo → responder según personalidad";
    $L[] = "  #8  Evento externo: suceso PE ≥ 5 en su zona → reaccionar acorde";
    $L[] = "  #9  Descanso/pasivo: ningún trigger anterior → entrenar, desplazarse, reunir info";
    $L[] = "  #10 Meta completada/fallida → elegir nueva meta, actualizar plan";
    $L[] = "";
    $L[] = "8.2 — PRIORIZACIÓN (qué trigger gana si se activan varios)";
    $L[] = "";
    $L[] = "  · Si INT > 70: trigger que mejor sirva a su meta (#2 prioritario)";
    $L[] = "  · Si AGR > 70: trigger de conflicto (#1, #5, #8)";
    $L[] = "  · Si HON > 70: trigger que cumpla su código (#4, #6, #10)";
    $L[] = "  · Si AMB > 70: trigger que le beneficie personalmente (#2, #3)";
    $L[] = "";
    $L[] = "8.3 — CÓMO REGISTRAR LA ACCIÓN DEL NPC";
    $L[] = "";
    $L[] = "  · Si el NPC actúa (triggers 1-8), genera un suceso S-XX y calcula su impacto";
    $L[] = "    como en el paso 4 del flujo de trabajo";
    $L[] = "  · Actualiza npc_tracking en ESTADO_JSON con los nuevos valores de salud, moral,";
    $L[] = "    plan_activo, ubicacion_zona y meta_actual";
    $L[] = "  · Si la acción inicia una trama nueva, crea un nuevo thread para darle continuidad";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 9 · HILOS NARRATIVOS — SISTEMA DE CONTINUIDAD";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "Los hilos narrativos (threads) garantizan que la historia del mundo tenga";
    $L[] = "continuidad de un mes a otro. Sin ellos, cada periódico empezaría de cero.";
    $L[] = "";
    $L[] = "  · Se almacenan como un array dentro de ESTADO_JSON → 'threads'";
    $L[] = "  · Cada thread tiene: id, titulo, estado, tipo, zonas, npc_implicados,";
    $L[] = "    pj_implicados, facciones_implicadas, descripcion, proxima_evolucion,";
    $L[] = "    posible_cierre, historial_evolucion";
    $L[] = "";
    $L[] = "  Ciclo de vida de un thread:";
    $L[] = "    1. NACE: un suceso importante (PE 5+ o que involucre NPC mayor) genera un nuevo thread";
    $L[] = "    2. EVOLUCIONA: cada ciclo que aparece en el periódico, su estado avanza";
    $L[] = "       (ultima_evolucion se actualiza, se añade entrada al historial)";
    $L[] = "    3. LATENTE: si no hay novedades, el thread se marca latente (no se menciona";
    $L[] = "       en el periódico pero puede reabrirse)";
    $L[] = "    4. CIERRE: cuando se resuelve, se marca como cerrado con un artículo de despedida";
    $L[] = "";
    $L[] = "  REGLAS:";
    $L[] = "  · Máximo 12 threads activos. Si hay más, priorizar por relevancia.";
    $L[] = "  · Un thread sin evoluciones en 3 ciclos → cerrar como latente.";
    $L[] = "  · Un thread que aparece 3+ ciclos seguidos → debe cerrarse o evolucionar";
    $L[] = "    significativamente (no puede ser siempre la misma noticia).";
    $L[] = "  · Al menos 1 thread debe evolucionar o cerrarse en cada periódico.";
    $L[] = "  · Los threads pueden fusionarse si dos hilos convergen narrativamente.";
    $L[] = "";
    $L[] = "======================================================================";
    $L[] = " 10 · REGLAS DE ESCRITURA (periódico y noticia) — OBLIGATORIAS";
    $L[] = "======================================================================";
    $L[] = "";
    $L[] = "REGLAS ABSOLUTAS (no negociables):";
    $L[] = "";
    $L[] = "  1. NUNCA muestres números, métricas, slugs ni terminología de sistema en el";
    $L[] = "     periódico o la noticia. Todo se traduce a lenguaje natural in-world:";
    $L[] = "     ✓ 'La presencia de la Marina se desploma en el West Blue'";
    $L[] = "     ✗ 'MAR bajó de 65 a 42 en West Blue'";
    $L[] = "";
    $L[] = "  2. Voz de prensa del mundo: titulares llamativos, tono periodístico con color";
    $L[] = "     local. Sesgo pro-Gobierno velado (normal en el mundo de One Piece).";
    $L[] = "     Rumores, columnas de opinión y entrevistas son bienvenidos.";
    $L[] = "";
    $L[] = "  3. Cita a personajes y NPCs por su nombre cuando aparezcan en los eventos.";
    $L[] = "     Da protagonismo a lo que hicieron los JUGADORES.";
    $L[] = "";
    $L[] = "  4. No inventes contradicciones con el estado del mundo.";
    $L[] = "     No reveles secretos de staff ni información metajuego.";
    $L[] = "";
    $L[] = "  5. Idioma: español.";
    $L[] = "";
    $L[] = "GUÍA DE TONO:";
    $L[] = "";
    $L[] = "  · Mundo en paz (lo normal): periódico costumbrista, comercial, de sucesos menores,";
    $L[] = "    vida en las islas, economía y puertos, ecos del mar.";
    $L[] = "    Grandes titulares de guerra SOLO cuando de verdad hay guerra.";
    $L[] = "";
    $L[] = "  · Artículos de continuación: 'En la edición pasada...' para hilos activos";
    $L[] = "    que evolucionan. NO repitas la misma noticia si no ha cambiado nada.";
    $L[] = "";
    $L[] = "  · Variedad de secciones: no todas las secciones deben verse igual. Alterna";
    $L[] = "    reportajes, entrevistas, columnas de opinión, anuncios clasificados, rumores.";
    $L[] = "";
    $L[] = "  · Si un hilo se cierra: artículo de despedida. Referencia cruzada a la";
    $L[] = "    edición donde empezó ('Como informamos en marzo...').";
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

    // Hilos narrativos
    $L[] = "== HILOS NARRATIVOS ABIERTOS (del ciclo anterior) ==";
    if (empty($threads)) {
        $L[] = "(Ninguno.)";
    } else {
        foreach ($threads as $th) {
            $thZonas = is_array($th['zonas']) ? implode(',', $th['zonas']) : (string)($th['zonas'] ?? '');
            $thFacc = is_array($th['facciones_implicadas']) ? implode(',', $th['facciones_implicadas']) : (string)($th['facciones_implicadas'] ?? '');
            $L[] = "- [{$th['estado']}] {$th['titulo']} (id: {$th['id']})";
            $L[] = "  Tipo: {$th['tipo']} | Zonas: {$thZonas} | Facciones: {$thFacc}";
            if (!empty($th['npc_implicados'])) $L[] = "  NPCs: " . (is_array($th['npc_implicados']) ? implode(',', $th['npc_implicados']) : $th['npc_implicados']);
            if (!empty($th['pj_implicados'])) $L[] = "  PJs: " . (is_array($th['pj_implicados']) ? implode(',', $th['pj_implicados']) : $th['pj_implicados']);
            $L[] = "  Descripción: " . trim(preg_replace('/\s+/', ' ', (string)$th['descripcion']));
            $L[] = "  Próxima evolución: " . ($th['proxima_evolucion'] ?? '—');
            if (!empty($th['posible_cierre'])) $L[] = "  POSIBLE CIERRE este ciclo.";
        }
    }
    $L[] = "";

    // Últimos periódicos
    $L[] = "== ÚLTIMOS PERIÓDICOS (continuidad narrativa) ==";
    if (empty($periodicos)) {
        $L[] = "(No hay periódicos anteriores.)";
    } else {
        foreach ($periodicos as $p) {
            $L[] = "- {$p['periodo']}: {$p['noticia_titulo']}";
            $L[] = "  " . $p['periodico_resumen'];
        }
    }
    $L[] = "";

    // Eventos
    $L[] = "== EVENTOS NOTIFICADOS ESTE MES ==";
    $L[] = "";
    $L[] = "CÓMO PONDERAR ESTOS EVENTOS (léelo antes de clasificar):";
    $L[] = "Cada evento viene de un trámite donde UN JUGADOR pega el enlace de su hilo y escribe";
    $L[] = "un resumen libre de lo que pasó. Ese resumen puede ser parco, exagerado, quitarle";
    $L[] = "importancia a algo grave, o simplemente no reflejar bien la escala real de lo ocurrido";
    $L[] = "en el hilo. NO existe ninguna clasificación automática fiable de este sistema — cualquier";
    $L[] = "cosa que se calcule por palabras clave es una heurística mecánica y puede estar mal.";
    $L[] = "TÚ eres quien de verdad clasifica cada evento (tipo S-01 a S-12, PE 1-10), usando tu";
    $L[] = "propio criterio narrativo. Si el resumen te basta para juzgar el peso con confianza,";
    $L[] = "clasifica directamente. Si el resumen es ambiguo, muy corto, o notas que puede haber";
    $L[] = "más contexto relevante (varios participantes, giros a mitad de hilo, un desenlace que";
    $L[] = "no se cuenta bien) y tienes forma de comprobarlo — acceso MCP de lectura a las tablas";
    $L[] = "`threads`/`posts` filtrando por el `tid` del evento, o capacidad de abrir la URL del";
    $L[] = "enlace — HAZLO antes de decidir. Es preferible que te tomes ese paso extra a que un";
    $L[] = "evento importante quede infravalorado (o uno menor, sobrevalorado) por un resumen flojo.";
    $L[] = "Los eventos son la materia prima del periódico: deben influir en las métricas de la";
    $L[] = "zona y facción correspondientes, y ser la base del contenido. Si hay pocos eventos,";
    $L[] = "genera contenido de relleno coherente (vida en las islas, economía, rumores).";
    $L[] = "";
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
            $L[] = "- [" . ($e['zona_slug'] !== '' ? $e['zona_slug'] : 'zona?') . "] " . $e['titulo'] . $rango . " (tid:" . (int) $e['tid'] . ")";
            $L[] = "  Enlace: " . $e['enlace'];
            $L[] = "  Resumen (del jugador): " . trim(preg_replace('/\s+/', ' ', (string) $e['resumen']));
        }
    }
    $L[] = "";

    // Misiones
    $L[] = "== MISIONES DEL MES ==";
    if (empty($misiones)) {
        $L[] = "(Ninguna.)";
    } else {
        foreach ($misiones as $m) {
            $L[] = "- (id:" . (int) $m['mision_id'] . ") [" . strtoupper(str_replace('_', ' ', $m['estado'])) . "] " . $m['titulo'] . " (" . ($m['zona_slug'] !== '' ? $m['zona_slug'] : 'zona?') . ")";
            if (trim((string) $m['resumen']) !== '') {
                $L[] = "  " . trim(preg_replace('/\s+/', ' ', (string) $m['resumen']));
            }
        }
    }
    $L[] = "";
    $L[] = "INSTRUCCIÓN: Analiza el estado de cada misión. Las COMPLETADAS han tenido un impacto";
    $L[] = "directo en el mundo: ajusta las métricas de zonas y facciones afectadas, genera";
    $L[] = "consecuencias narrativas coherentes y menciónalas en el periódico. Las FALLIDAS también";
    $L[] = "dejan huella (tensión, bajas, oportunidades perdidas).";
    $L[] = "";
    if (!empty($misionesEnCurso)) {
        $L[] = "IMPORTANTE — RESOLUCIÓN OBLIGATORIA: el staff YA NO marca a mano si una misión EN CURSO";
        $L[] = "se completó o falló. TÚ debes decidirlo leyendo los eventos, hilos y periódicos de este";
        $L[] = "ciclo, y devolver tu decisión para CADA una de estas misiones EN CURSO en el bloque";
        $L[] = "===MISIONES_RESUELTAS=== (ver formato de respuesta). Si de verdad no hay ninguna pista";
        $L[] = "sobre una misión concreta, mantenla 'en_curso' explícitamente (no la omitas).";
        $L[] = "Misiones EN CURSO que requieren resolución este ciclo:";
        foreach ($misionesEnCurso as $m) {
            $L[] = "  - id:" . (int) $m['mision_id'] . " — " . $m['titulo'];
        }
        $L[] = "";
    }

    // NPCs mayores
    $L[] = "== NPCs MAYORES (con ficha) ==";
    if (empty($npcs)) {
        $L[] = "(Ninguno registrado.)";
    } else {
        foreach ($npcs as $n) {
            $line = "- " . $n['nombre'] . " | facción: " . ($n['faccion'] !== '' ? $n['faccion'] : '?') . " | rango: " . ($n['rango'] !== '' ? $n['rango'] : '?') . " | zona: " . ($n['mundo_zona'] !== '' ? $n['mundo_zona'] : '?') . " | ubicación: " . ($n['mundo_ubic'] !== '' ? $n['mundo_ubic'] : '?') . " | estado: " . ($n['mundo_estado_np'] !== '' ? $n['mundo_estado_np'] : '?') . " | acción: " . $n['mundo_accion'];
            if (!empty($n['datos_publicos']) && is_array($n['datos_publicos'])) {
                $dp = $n['datos_publicos'];
                if (!empty($dp['titulo'])) $line .= " | título: " . $dp['titulo'];
                if (!empty($dp['descripcion'])) $line .= " | desc: " . trim(preg_replace('/\s+/', ' ', $dp['descripcion']));
            }
            $L[] = $line;
            if (!empty($n['datos_internos']) && is_array($n['datos_internos'])) {
                $di = $n['datos_internos'];
                if (!empty($di['personalidad'])) {
                    $pers = array();
                    foreach ($di['personalidad'] as $pk => $pv) { $pers[] = "$pk: $pv"; }
                    $L[] = "  Personalidad: " . implode(', ', $pers);
                }
                if (!empty($di['metas'])) {
                    $L[] = "  Metas: " . implode('; ', $di['metas']);
                }
                if (!empty($di['tracking'])) {
                    $tr = $di['tracking'];
                    $trLine = "  Tracking: salud={$tr['salud']} moral={$tr['moral']}";
                    if (!empty($tr['plan_activo'])) $trLine .= " plan={$tr['plan_activo']}";
                    if (!empty($tr['ubicacion_zona'])) $trLine .= " ubic={$tr['ubicacion_zona']}";
                    if (!empty($tr['meta_actual'])) $trLine .= " meta={$tr['meta_actual']}";
                    $L[] = $trLine;
                }
            }
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

    // Navegación del mes (auto-generada desde NPCs si vacía)
    $nav = trim((string) ($ciclo['nav_resumen'] ?? ''));
    if ($nav === '') {
        $nav = ope_rol_mv_auto_nav_resumen();
    }
    $L[] = "== NAVEGACIÓN DEL MES ==";
    $L[] = $nav;
    $L[] = "";

    $L[] = "== INDICACIONES DEL STAFF (obligatorio seguirlas) ==";
    $ind = trim((string) $ciclo['indicaciones']);
    $L[] = $ind !== '' ? $ind : "(Sin indicaciones especiales este mes.)";
    $L[] = "";

    // Contrato de salida
    $L[] = "###############################################################################";
    $L[] = "==  FORMATO DE RESPUESTA (OBLIGATORIO)  ==";
    $L[] = "###############################################################################";
    $L[] = "Responde EXACTAMENTE con estos SEIS bloques, cada uno entre sus marcadores ===X=== ... ===FIN===, y SIN ningún texto fuera de ellos (ni saludos ni explicaciones).";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 1 — ESTADO_JSON (el nuevo Tablero). JSON válido, sin comentarios ni comas colgantes.";
    $L[] = "Reglas: usa EXACTAMENTE los mismos slugs de zona y facción y las mismas claves de métrica del estado actual. Incluye TODAS las zonas y TODAS las facciones aunque no cambien. Métricas 0-100 salvo REP (-100..100). La tensión es POR MAR. Redacta las 'notas' in-world y coherentes con el periódico.";
    $L[] = "INCLUYE los arrays 'threads' (hilos narrativos evolucionados) y 'npc_tracking' (tracking actualizado de cada NPC mayor).";
    $L[] = "===ESTADO_JSON===";
    $L[] = "{";
    $L[] = "  \"zonas\": { \"east-blue\": {\"cli\":65,\"pel\":20,\"riq\":55,\"civ\":60,\"mar\":50,\"pir\":35,\"rev\":15,\"inf\":15,\"est\":60,\"ten\":25,\"notas\":\"...\"}, \"...\": {} },";
    $L[] = "  \"facciones\": { \"marine\": {\"rep\":40,\"coh\":80,\"mil\":85,\"pol\":80,\"eco\":75,\"mor\":70,\"alc\":80,\"notas\":\"...\"}, \"...\": {} },";
    $L[] = "  \"tension\": { \"east-blue\": { \"marine|pirata\": {\"valor\":76,\"notas\":\"...\"}, \"...\": {} }, \"...\": {} },";
    $L[] = "  \"arcos\": [ {\"nombre\":\"...\",\"estado\":\"Activo|Latente|Cerrado\",\"zonas\":\"...\",\"facciones\":\"...\",\"descripcion\":\"...\"} ],";
    $L[] = "  \"threads\": [ {\"id\":\"th-001\",\"titulo\":\"...\",\"estado\":\"activo\",\"tipo\":\"...\",\"zonas\":[],\"npc_implicados\":[],\"facciones_implicadas\":[],\"descripcion\":\"...\",\"proxima_evolucion\":\"...\",\"posible_cierre\":false} ],";
    $L[] = "  \"npc_tracking\": { \"42\": {\"salud\":95,\"moral\":80,\"plan_activo\":\"...\",\"ubicacion_zona\":\"East Blue\",\"meta_actual\":\"...\"} }";
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
    $L[] = "BLOQUE 4 — MISIONES_RESUELTAS. Resuelve el estado de CADA misión EN CURSO listada arriba";
    $L[] = "en '== MISIONES DEL MES ==' con id numérico. Decide 'completada', 'fallida' o 'en_curso'";
    $L[] = "(si sigue igual) para cada una, basándote en los eventos/hilos de este ciclo. Si no hay";
    $L[] = "ninguna misión EN CURSO este ciclo, deja el bloque vacío (solo los marcadores).";
    $L[] = "Un guion por línea, con estos campos separados por ' | ':";
    $L[] = "===MISIONES_RESUELTAS===";
    $L[] = "- id: 12 | estado: completada | resumen: (qué ocurrió, en 1-2 frases, para guardarlo en el historial)";
    $L[] = "- id: 13 | estado: en_curso | resumen: (por qué sigue abierta / qué falta)";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 5 — MISIONES que SURGEN de lo ocurrido este mes (ganchos para que los jugadores actúen el mes que viene). Propón 2-5, coherentes con los eventos, los arcos y las tensiones. El staff YA NO las filtra: al publicar, TODAS las de este bloque se crean automáticamente como misiones 'en_curso' del próximo ciclo — no propongas más de las que de verdad tengan sentido.";
    $L[] = "Un guion por línea, con estos campos separados por ' | ':";
    $L[] = "===MISIONES===";
    $L[] = "- titulo: (título corto) | zona: east-blue | facciones: marine,pirata | dificultad: (baja|media|alta) | resumen: (qué ocurre y qué se pide a los jugadores)";
    $L[] = "- titulo: ... | zona: ... | facciones: ... | dificultad: ... | resumen: ...";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "BLOQUE 6 — IMAGENES. Un id por línea. Prompts en INGLÉS, estilo ilustración/anime One Piece, cinematográficos. Si intervienen personajes de jugador o NPCs concretos, añade '(use reference image of <nombre>)'. Incluye SIEMPRE 'portada' y una imagen por cada figure que uses en el periódico. El id debe COINCIDIR con el data-img del figure correspondiente.";
    $L[] = "===IMAGENES===";
    $L[] = "- id: portada | tamaño: 1200x675 | prompt: ...";
    $L[] = "- id: (otro-id-usado-en-figure) | tamaño: 800x600 | prompt: ...";
    $L[] = "===FIN===";
    $L[] = "";
    $L[] = "-------------------------------------------------------------------------------";
    $L[] = "INSTRUCCIONES ESPECÍFICAS ADICIONALES:";
    $L[] = "  a) NO puedes negarte a generar ninguno de los 6 bloques. Si no hay datos suficientes, genera ese bloque con contenido mínimo coherente (o vacío en el caso de MISIONES_RESUELTAS si no hay nada que resolver).";
    $L[] = "  b) Si el staff incluye la palabra \"REGENERAR\" en las indicaciones, debes IGNORAR el contexto de generaciones anteriores y regenerar todo desde cero.";
    $L[] = "  c) Ante cualquier ambigüedad entre estas instrucciones y las indicaciones del staff, las indicaciones del staff tienen prioridad.";
    $L[] = "  d) TÚ NO ESCRIBES EN LA BASE DE DATOS. Aunque tengas herramientas MCP con acceso de lectura";
    $L[] = "     (sección 2) para verificar cualquier dato en tiempo real, tu única salida es el texto de";
    $L[] = "     estos 6 bloques: el staff pegará tu respuesta en el panel de Mundo Vivo y el propio sistema";
    $L[] = "     aplicará los cambios (con topes anti-escalada de seguridad). No ejecutes INSERT/UPDATE/DELETE.";
    $L[] = "";

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
 * Parsea el bloque ===MISIONES_RESUELTAS===. Cada línea:
 *   - id: 12 | estado: completada|fallida|en_curso | resumen: ...
 * Devuelve array de {id, estado, resumen}.
 */
function ope_rol_mv_parse_misiones_resueltas($txt)
{
    $out = array();
    $txt = (string) $txt;
    if ($txt === '') {
        return $out;
    }
    $validos = array('completada', 'fallida', 'en_curso');
    foreach (preg_split('/\r?\n/', $txt) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $line = preg_replace('/^[\-\*\x{2022}]\s*/u', '', $line);
        if ($line === '') continue;
        $r = array('id' => 0, 'estado' => '', 'resumen' => '');
        foreach (explode('|', $line) as $seg) {
            $seg = trim($seg);
            if ($seg === '' || strpos($seg, ':') === false) continue;
            list($k, $v) = explode(':', $seg, 2);
            $k = strtolower(trim($k));
            $v = trim($v);
            switch ($k) {
                case 'id': $r['id'] = (int) preg_replace('/[^0-9]/', '', $v); break;
                case 'estado': $r['estado'] = strtolower(str_replace(' ', '_', $v)); break;
                case 'resumen': $r['resumen'] = $v; break;
            }
        }
        if ($r['id'] > 0 && in_array($r['estado'], $validos, true)) {
            $out[] = $r;
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
        'misiones_resueltas' => array(),
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
    $res['misiones_resueltas'] = ope_rol_mv_parse_misiones_resueltas(ope_rol_mv_extract_block($raw, 'MISIONES_RESUELTAS'));

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

/**
 * Tope duro de variación por ciclo para métricas de zona/facción (AV-13 §5.3 / §2.4).
 * Ninguna métrica puede moverse más de esto en un solo ciclo, sin importar lo que
 * devuelva la IA. Es un guardarraíl de código, no una instrucción de texto: por eso
 * funciona igual aunque la IA se equivoque, alucine o el staff no revise los números.
 */
if (!defined('OPE_MV_METRIC_MAX_DELTA')) {
    define('OPE_MV_METRIC_MAX_DELTA', 15);
}

/**
 * Calcula el nuevo valor de una métrica aplicando el tope de variación por ciclo.
 * Devuelve array [nuevo_valor, delta_aplicado, delta_propuesto, capado(bool)].
 */
function ope_rol_mv_clamp_metric($actual, $propuesto, $min, $max, $maxDelta = OPE_MV_METRIC_MAX_DELTA)
{
    $actual = (int) $actual;
    $propuesto = max($min, min($max, (int) $propuesto));
    $deltaProp = $propuesto - $actual;
    $deltaApl = max(-$maxDelta, min($maxDelta, $deltaProp));
    $nuevo = max($min, min($max, $actual + $deltaApl));
    return array($nuevo, $deltaApl, $deltaProp, ($deltaApl !== $deltaProp));
}

/**
 * Calcula, SIN escribir nada en la base de datos, qué topes se aplicarían si se
 * publicara $estado tal cual. Se usa en la vista previa para que el staff vea ANTES
 * de publicar si la IA se ha salido de rango en algo (mismo cálculo que
 * ope_rol_mv_aplicar_estado(), pero de solo lectura).
 */
function ope_rol_mv_calcular_caps_previstos($estado)
{
    global $db;
    $caps = array();
    if (!is_array($estado)) return $caps;
    $zMetricKeys = array_keys(ope_rol_mv_zona_metrics());
    $fMetricKeys = array_keys(ope_rol_mv_faccion_metrics());
    $zActual = ope_rol_mv_zonas();
    $fActual = ope_rol_mv_facciones();

    if (!empty($estado['zonas']) && is_array($estado['zonas'])) {
        foreach ($estado['zonas'] as $slug => $z) {
            if (!is_array($z)) continue;
            $base = $zActual[$slug] ?? array();
            foreach ($zMetricKeys as $k) {
                if (!isset($z[$k])) continue;
                list(, $deltaApl, $deltaProp, $capado) = ope_rol_mv_clamp_metric($base[$k] ?? 0, $z[$k], 0, 100);
                if ($capado) $caps[] = array('ambito' => 'zona', 'slug' => $slug, 'metrica' => strtoupper($k), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $deltaApl);
            }
        }
    }
    if (!empty($estado['facciones']) && is_array($estado['facciones'])) {
        foreach ($estado['facciones'] as $slug => $f) {
            if (!is_array($f)) continue;
            $base = $fActual[$slug] ?? array();
            foreach ($fMetricKeys as $k) {
                if (!isset($f[$k])) continue;
                $min = ($k === 'rep') ? -100 : 0;
                list(, $deltaApl, $deltaProp, $capado) = ope_rol_mv_clamp_metric($base[$k] ?? 0, $f[$k], $min, 100);
                if ($capado) $caps[] = array('ambito' => 'faccion', 'slug' => $slug, 'metrica' => strtoupper($k), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $deltaApl);
            }
        }
    }
    $capUp = defined('OPE_MV_TENSION_MAX_UP') ? (int) OPE_MV_TENSION_MAX_UP : 15;
    if (!empty($estado['tension']) && is_array($estado['tension'])) {
        foreach ($estado['tension'] as $zslug => $pares) {
            if (!is_array($pares)) continue;
            foreach ($pares as $par => $info) {
                $propuesto = is_array($info) ? (isset($info['valor']) ? (int) $info['valor'] : null) : (is_numeric($info) ? (int) $info : null);
                if ($propuesto === null) continue;
                $propuesto = max(0, min(100, $propuesto));
                $cq = $db->simple_select('rol_mv_tension', 'valor', "zona_slug = '" . $db->escape_string((string) $zslug) . "' AND par = '" . $db->escape_string((string) $par) . "'", array('limit' => 1));
                if (!$db->num_rows($cq)) continue;
                $cur = (int) $db->fetch_field($cq, 'valor');
                $deltaProp = $propuesto - $cur;
                if ($propuesto > $cur + $capUp) {
                    $caps[] = array('ambito' => 'tension', 'slug' => $zslug, 'metrica' => str_replace('|', ' vs ', $par), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $capUp);
                }
            }
        }
    }
    return $caps;
}

/**
 * Aplica el estado parseado al tablero, con topes anti-escalada aplicados en código
 * (no solo como instrucción de texto en el prompt). Si se pasa $capsLog (array por
 * referencia), se rellena con cada tope que haya recortado la propuesta de la IA,
 * para poder mostrarlo en la vista previa / auditoría.
 */
function ope_rol_mv_aplicar_estado($estado, &$capsLog = null)
{
    global $db;
    if (!is_array($capsLog)) $capsLog = array();
    if (!is_array($estado)) {
        return;
    }
    $zMetricKeys = array_keys(ope_rol_mv_zona_metrics());
    $fMetricKeys = array_keys(ope_rol_mv_faccion_metrics());
    $zActual = ope_rol_mv_zonas();
    $fActual = ope_rol_mv_facciones();

    // Zonas
    if (!empty($estado['zonas']) && is_array($estado['zonas'])) {
        foreach ($estado['zonas'] as $slug => $z) {
            if (!is_array($z)) continue;
            $base = $zActual[$slug] ?? array();
            $upd = array();
            foreach ($zMetricKeys as $k) {
                if (!isset($z[$k])) continue;
                list($nuevo, $deltaApl, $deltaProp, $capado) = ope_rol_mv_clamp_metric($base[$k] ?? 0, $z[$k], 0, 100);
                $upd[$k] = $nuevo;
                if ($capado) {
                    $capsLog[] = array('ambito' => 'zona', 'slug' => $slug, 'metrica' => strtoupper($k), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $deltaApl);
                }
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
            $base = $fActual[$slug] ?? array();
            $upd = array();
            foreach ($fMetricKeys as $k) {
                if (!isset($f[$k])) continue;
                $min = ($k === 'rep') ? -100 : 0;
                list($nuevo, $deltaApl, $deltaProp, $capado) = ope_rol_mv_clamp_metric($base[$k] ?? 0, $f[$k], $min, 100);
                $upd[$k] = $nuevo;
                if ($capado) {
                    $capsLog[] = array('ambito' => 'faccion', 'slug' => $slug, 'metrica' => strtoupper($k), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $deltaApl);
                }
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
    $capUp = defined('OPE_MV_TENSION_MAX_UP') ? (int) OPE_MV_TENSION_MAX_UP : 15;
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
                        $deltaProp = $upd['valor'] - $cur;
                        if ($upd['valor'] > $cur + $capUp) {
                            $capsLog[] = array('ambito' => 'tension', 'slug' => $zslug, 'metrica' => str_replace('|', ' vs ', $par), 'propuesto_delta' => $deltaProp, 'aplicado_delta' => $capUp);
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
    // Arcos: reemplazo completo si se proporcionan (fin de aplicar caps de tensión, sigue igual)
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
 * Aplica las resoluciones de misiones EN CURSO que la IA devolvió en
 * ===MISIONES_RESUELTAS===. El staff ya no elige a mano "completada/fallida": lo
 * decide la IA leyendo los eventos del ciclo, y esto solo escribe su decisión.
 * Devuelve el número de misiones resueltas.
 */
function ope_rol_mv_aplicar_misiones_resueltas(array $resoluciones)
{
    global $db;
    $n = 0;
    foreach ($resoluciones as $r) {
        $mid = (int) ($r['id'] ?? 0);
        $estado = (string) ($r['estado'] ?? '');
        if ($mid < 1 || !in_array($estado, array('completada', 'fallida', 'en_curso'), true)) continue;
        $upd = array('estado' => $estado);
        if (trim((string) ($r['resumen'] ?? '')) !== '') {
            $upd['notas_resolucion'] = $db->escape_string(trim((string) $r['resumen']));
        }
        $db->update_query('rol_mv_misiones', $upd, "mision_id = {$mid}");
        $n++;
    }
    return $n;
}

/**
 * Inserta como misiones 'en_curso' del ciclo indicado las propuestas nuevas que la
 * IA devolvió en ===MISIONES===. Antes este paso estaba deshabilitado ("Disponible
 * en una fase posterior") y las misiones no se creaban de ninguna forma automática;
 * ahora se crean solas al publicar, sin que el staff tenga que redactarlas ni pegarlas.
 * Devuelve el número de misiones creadas.
 */
function ope_rol_mv_crear_misiones_nuevas(array $misiones, $ciclo_id)
{
    global $db;
    $ciclo_id = (int) $ciclo_id;
    if ($ciclo_id < 1) return 0;
    $zonas = ope_rol_mv_zonas();
    $n = 0;
    foreach ($misiones as $m) {
        $titulo = trim((string) ($m['titulo'] ?? ''));
        if ($titulo === '') continue;
        $zonaSlug = ope_rol_mv_resolver_zona_slug((string) ($m['zona'] ?? ''));
        if ($zonaSlug === '' && isset($zonas[(string) ($m['zona'] ?? '')])) {
            $zonaSlug = (string) $m['zona'];
        }
        $rango = in_array((string)($m['rango'] ?? ''), array('S','A','B','C','D'), true) ? $m['rango'] : 'D';
        $pel = min(5, max(1, (int)($m['peligrosidad'] ?? 1)));
        $mod = in_array((string)($m['modalidad'] ?? ''), array('solo','grupo','cualquiera'), true) ? $m['modalidad'] : 'cualquiera';
        $db->insert_query('rol_mv_misiones', array(
            'ciclo_id'        => $ciclo_id,
            'titulo'          => $db->escape_string($titulo),
            'resumen'         => $db->escape_string((string) ($m['resumen'] ?? '')),
            'descripcion_larga' => $db->escape_string((string) ($m['descripcion_larga'] ?? $m['resumen'] ?? '')),
            'rango'           => $db->escape_string($rango),
            'peligrosidad'    => $pel,
            'zona_slug'       => $db->escape_string($zonaSlug),
            'facciones'       => $db->escape_string((string) ($m['facciones'] ?? '')),
            'recompensa'      => $db->escape_string((string) ($m['recompensa'] ?? '')),
            'modalidad'       => $db->escape_string($mod),
            'enlace'          => '',
            'estado'          => 'en_curso',
            'dateline'        => (int) TIME_NOW,
        ));
        $n++;
    }
    return $n;
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

    // 1) Aplicar estado al tablero (con topes anti-escalada server-side; $capsLog
    //    recoge cualquier valor que la IA haya propuesto y que se haya recortado).
    $capsLog = array();
    ope_rol_mv_aplicar_estado($parsed['estado'], $capsLog);

    // 1b) Threads y navegación (v3)
    $threads_json = '';
    if (isset($parsed['estado']['threads'])) {
        $threads_json = json_encode($parsed['estado']['threads'], JSON_UNESCAPED_UNICODE);
    }
    $nav_resumen = (string)($parsed['nav_resumen'] ?? '');

    // 1c) Actualizar NPC tracking desde npc_tracking (v3) + sincronizar su ubicación
    //     pública (mundo_zona/mundo_accion/mundo_estado_np) para que de verdad se
    //     "muevan" por el mundo sin que el staff tenga que tocar nada.
    if (isset($parsed['estado']['npc_tracking']) && is_array($parsed['estado']['npc_tracking'])) {
        foreach ($parsed['estado']['npc_tracking'] as $pid => $tracking) {
            $pid = (int)$pid;
            if ($pid < 1 || !is_array($tracking)) continue;
            $q = $db->simple_select('rol_personajes', 'datos_internos', "pid = $pid", array('limit' => 1));
            if (!$db->num_rows($q)) continue;
            $di = json_decode((string)$db->fetch_field($q, 'datos_internos'), true);
            if (!is_array($di)) $di = array('personalidad' => array(), 'metas' => array(), 'meta_actual' => '', 'tracking' => array());
            $di['tracking']['salud'] = isset($tracking['salud']) ? (int)$tracking['salud'] : ($di['tracking']['salud'] ?? 100);
            $di['tracking']['moral'] = isset($tracking['moral']) ? (int)$tracking['moral'] : ($di['tracking']['moral'] ?? 100);
            $di['tracking']['plan_activo'] = (string)($tracking['plan_activo'] ?? $di['tracking']['plan_activo'] ?? '');
            $di['tracking']['ubicacion_zona'] = (string)($tracking['ubicacion_zona'] ?? $di['tracking']['ubicacion_zona'] ?? '');
            $di['tracking']['meta_actual'] = (string)($tracking['meta_actual'] ?? $di['tracking']['meta_actual'] ?? '');
            $di['tracking']['ultimo_ciclo'] = $ciclo['periodo'];
            $db->update_query('rol_personajes', array('datos_internos' => $db->escape_string(json_encode($di, JSON_UNESCAPED_UNICODE))), "pid = $pid");
            ope_rol_mv_sync_npc_ubicacion($pid, $di['tracking']);
        }
    }

    // 2) Snapshot del tablero ya actualizado. IMPORTANTE: incluye 'threads' dentro del
    //    propio snapshot, porque ope_rol_mv_threads_activos() los lee desde
    //    estado_json['threads'] del último ciclo publicado. Guardarlos SOLO en la columna
    //    threads_json (aparte) los deja invisibles para el siguiente ciclo.
    $snapshot = ope_rol_mv_tablero();
    $snapshot['threads'] = is_array($parsed['estado']['threads'] ?? null) ? $parsed['estado']['threads'] : array();
    // También se archiva el npc_tracking devuelto este ciclo (aunque la fuente de verdad
    // "viva" es rol_personajes.datos_internos): así el histórico de un ciclo pasado es
    // autocontenido y consultable por MCP sin tener que cruzar tablas.
    $snapshot['npc_tracking'] = is_array($parsed['estado']['npc_tracking'] ?? null) ? $parsed['estado']['npc_tracking'] : array();
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
        'threads_json'   => $db->escape_string($threads_json),
        'nav_resumen'    => $db->escape_string($nav_resumen),
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

    // 5) Misiones: la IA resuelve las EN CURSO de este ciclo (===MISIONES_RESUELTAS===)...
    $resueltas = ope_rol_mv_aplicar_misiones_resueltas($parsed['misiones_resueltas'] ?? array());

    // 6) Abrir el mes siguiente si no existe
    $siguiente = ope_rol_mv_ciclo_actual();

    // ...y las nuevas que propone (===MISIONES===) se crean solas para el mes que
    //    viene: ya no hace falta un botón de "Publicar misiones" ni que el staff las
    //    redacte o filtre a mano.
    $creadas = 0;
    if ($siguiente && !empty($parsed['misiones'])) {
        $creadas = ope_rol_mv_crear_misiones_nuevas($parsed['misiones'], (int) $siguiente['ciclo_id']);
    }

    // 7) Auditoría: registrar qué se aplicó, qué se recortó por topes, y qué pasó con
    //    las misiones — para poder revisar cualquier publicación después sin tener que
    //    fiarse solo de la memoria del staff que la hizo.
    ope_rol_mv_audit_log($ciclo_id, $capsLog, $resueltas, $creadas);

    // 8) Avisar a los Web Masters de que hay periódico nuevo (best-effort: si falla el
    //    envío de MP, la publicación ya se ha guardado igualmente).
    ope_rol_mv_notificar_publicacion($ciclo, $capsLog);

    return array('ok' => true, 'caps' => $capsLog, 'misiones_resueltas' => $resueltas, 'misiones_creadas' => $creadas);
}

// ─────────────────────────────────────────────────────────────────────────
// Fase 3 — Auditoría y red de seguridad para publicación desatendida
// ─────────────────────────────────────────────────────────────────────────

/**
 * Registra cada publicación en rol_mv_audit: quién publicó, qué topes se aplicaron
 * (si la IA propuso algo fuera de rango) y qué pasó con las misiones. Es el rastro
 * que permite revisar cualquier ciclo pasado sin depender de que alguien se acuerde.
 * No lanza error si la tabla no existe todavía (instalaciones sin migrar a v4).
 */
function ope_rol_mv_audit_log($ciclo_id, array $capsLog, $misionesResueltas = 0, $misionesCreadas = 0)
{
    global $db, $mybb;
    if (!$db->table_exists('rol_mv_audit')) {
        return;
    }
    $db->insert_query('rol_mv_audit', array(
        'ciclo_id'            => (int) $ciclo_id,
        'uid_publicador'      => (int) ($mybb->user['uid'] ?? 0),
        'caps_aplicados_json' => $db->escape_string(json_encode($capsLog, JSON_UNESCAPED_UNICODE)),
        'caps_aplicados_n'    => count($capsLog),
        'misiones_resueltas'  => (int) $misionesResueltas,
        'misiones_creadas'    => (int) $misionesCreadas,
        'dateline'            => (int) TIME_NOW,
    ));
}

/** Lista de entradas de auditoría, más recientes primero. */
function ope_rol_mv_audit_list($limit = 20)
{
    global $db;
    $out = array();
    if (!$db->table_exists('rol_mv_audit')) {
        return $out;
    }
    $q = $db->simple_select('rol_mv_audit', '*', '', array('order_by' => 'audit_id', 'order_dir' => 'DESC', 'limit' => (int) $limit));
    while ($row = $db->fetch_array($q)) {
        $row['caps_aplicados'] = json_decode((string) $row['caps_aplicados_json'], true);
        if (!is_array($row['caps_aplicados'])) $row['caps_aplicados'] = array();
        $out[] = $row;
    }
    return $out;
}

/**
 * Envía un mensaje privado a todos los Web Masters avisando de que hay periódico
 * nuevo, con un resumen de si la IA se salió de los topes en algo. Es "best effort":
 * cualquier fallo (datahandler no disponible, sin destinatarios, etc.) se ignora en
 * silencio porque la publicación en sí YA se ha guardado y no debe bloquearse por esto.
 */
function ope_rol_mv_notificar_publicacion($ciclo, array $capsLog = array())
{
    global $db, $mybb;
    if (!function_exists('is_moderator')) {
        // fuera de contexto normal de foro (p.ej. CLI); no hay nada que notificar.
        return false;
    }
    if (!$db->table_exists('rol_personajes')) {
        return false;
    }
    $uids = array();
    $q = $db->simple_select('rol_personajes', 'DISTINCT uid', "staff_rol = 'webmaster' AND uid > 0");
    while ($row = $db->fetch_array($q)) {
        $uids[] = (int) $row['uid'];
    }
    $uids = array_values(array_unique(array_diff($uids, array((int) ($mybb->user['uid'] ?? 0)))));
    if (empty($uids)) {
        return false;
    }

    $pmFile = MYBB_ROOT . 'inc/datahandlers/pm.php';
    if (!is_file($pmFile)) {
        return false;
    }
    require_once $pmFile;
    if (!class_exists('PM_DataHandler')) {
        return false;
    }

    $periodo = ope_rol_mv_periodo_label($ciclo['periodo'] ?? '');
    $subject = 'Mundo Vivo publicado — ' . $periodo;
    $msg = "Se ha publicado el periódico de {$periodo}.\n\n";
    if (!empty($capsLog)) {
        $msg .= "⚠ Se aplicaron " . count($capsLog) . " tope(s) anti-escalada porque la IA propuso cambios mayores de lo permitido en un ciclo:\n";
        foreach ($capsLog as $c) {
            $msg .= " - [{$c['ambito']}] {$c['slug']} · {$c['metrica']}: propuesto " . ($c['propuesto_delta'] >= 0 ? '+' : '') . $c['propuesto_delta'] . ", aplicado " . ($c['aplicado_delta'] >= 0 ? '+' : '') . "{$c['aplicado_delta']}\n";
        }
        $msg .= "\nRevisa el periódico y el panel de Mundo Vivo si algo no cuadra.\n";
    } else {
        $msg .= "Sin avisos: todos los cambios estaban dentro de los topes normales.\n";
    }

    try {
        $pmhandler = new PM_DataHandler();
        $pmhandler->admin_override = true;
        $pmhandler->set_data(array(
            'subject' => $subject,
            'message' => $msg,
            'touid'   => $uids,
            'toid'    => array(),
            'fromid'  => (int) ($mybb->user['uid'] ?? 0),
            'do'      => '',
            'pmid'    => 0,
            'options' => array('signature' => 0, 'disablesmilies' => 0, 'savecopy' => 1, 'readreceipt' => 0),
        ));
        if ($pmhandler->validate_pm()) {
            $pmhandler->insert_pm();
            return true;
        }
    } catch (\Throwable $e) {
        // silencioso a propósito: la publicación ya se guardó, esto es solo un aviso.
    }
    return false;
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
