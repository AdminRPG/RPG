<?php
/**
 * One Piece: Eternal · Akuma no Mi (fruta por PJ)
 * Canon: Eternal-Sistema/docs/02-HAKI-Y-FRUTAS/SISTEMA-DE-FRUTAS.md
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** PP por subir un nivel (0→1, 1→2) según tramo; despertar (2→3) aparte. */
function ope_fruta_pp_nivel($tramo, $es_despertar = false)
{
    static $norm = array(1 => 60, 2 => 120, 3 => 180, 4 => 250, 5 => 320);
    static $desp = array(1 => 100, 2 => 200, 3 => 300, 4 => 400, 5 => 500);
    $t = max(1, min(5, (int) $tramo));
    return $es_despertar ? (int) ($desp[$t] ?? 500) : (int) ($norm[$t] ?? 320);
}

function ope_fruta_cu_req($nivel_destino)
{
    static $map = array(1 => 20, 2 => 55, 3 => 120);
    return (int) ($map[(int) $nivel_destino] ?? 9999);
}

function ope_fruta_tramo_min_despertar($tier)
{
    $tier = max(1, min(5, (int) $tier));
    if ($tier <= 2) {
        return 3;
    }
    if ($tier <= 4) {
        return 4;
    }
    return 5;
}

function ope_fruta_rareza_a_tier($rareza)
{
    $r = mb_strtolower(trim((string) $rareza), 'UTF-8');
    if (strpos($r, 'legend') !== false) {
        return 5;
    }
    if (strpos($r, 'épic') !== false || strpos($r, 'epic') !== false) {
        return 4;
    }
    if (strpos($r, 'rar') !== false) {
        return 3;
    }
    if (strpos($r, 'poco') !== false) {
        return 2;
    }
    return 1;
}

function ope_fruta_pd_cost($tier, $concreta = false)
{
    static $rand = array(1 => 4, 2 => 6, 3 => 10, 4 => 14, 5 => 20);
    static $fix = array(1 => 6, 2 => 10, 3 => 14, 4 => 20, 5 => 28);
    $t = max(1, min(5, (int) $tier));
    return $concreta ? (int) ($fix[$t] ?? 28) : (int) ($rand[$t] ?? 20);
}

function ope_fruta_by_id($fruta_id)
{
    global $db;
    $fruta_id = (int) $fruta_id;
    if ($fruta_id < 1 || !function_exists('ope7_tabla_existe') || !ope7_tabla_existe('akumas')) {
        return null;
    }
    $q = $db->simple_select('ope_akumas', '*', "id = {$fruta_id}", array('limit' => 1));
    if (!$db->num_rows($q)) {
        return null;
    }
    $r = $db->fetch_array($q);
    $tier = (int) ($r['tier'] ?? 0);
    if ($tier < 1) {
        $tier = ope_fruta_rareza_a_tier((string) ($r['rareza'] ?? 'Común'));
    }
    // D6.3: mapeo del esquema canónico mybb_ope_akumas al que consume el HUD.
    return array(
        'id'           => (int) $r['id'],
        'nombre'       => (string) ($r['nombre_propio'] ?? ''),
        'modelo'       => '',
        'tipo'         => (string) ($r['familia'] ?? 'paramecia'),
        'rareza'       => (string) ($r['rareza'] ?? ''),
        'tier'         => $tier,
        'imagen'       => '',
        'descripcion'  => is_array($r['mecanica_base'] ?? null) ? json_encode($r['mecanica_base'], JSON_UNESCAPED_UNICODE) : (string) ($r['mecanica_base'] ?? ''),
        'activo'       => 1,
        'ocupada_pid'  => (int) ($r['portador_id'] ?? 0),
        'estado'       => (string) ($r['estado'] ?? 'sin_portador'),
        'slug'         => '',
        'secundario'   => '',
    );
}

function ope_fruta_libres($tier = 0)
{
    global $db;
    $out = array();
    if (!function_exists('ope7_tabla_existe') || !ope7_tabla_existe('akumas')) {
        return $out;
    }
    $where = "estado = 'sin_portador'";
    if ((int) $tier >= 1 && (int) $tier <= 5) {
        $where .= ' AND tier = ' . (int) $tier;
    }
    $q = $db->simple_select('ope_akumas', '*', $where, array('order_by' => 'tier DESC, nombre_propio ASC'));
    while ($r = $db->fetch_array($q)) {
        $r['nombre']      = (string) $r['nombre_propio'];
        $r['tipo']        = (string) $r['familia'];
        $r['imagen']      = '';
        $r['activo']      = 1;
        $r['ocupada_pid'] = 0;
        $out[] = $r;
    }
    return $out;
}

function ope_fruta_pj($pid)
{
    global $db;
    $pid = (int) $pid;
    if ($pid < 1 || !function_exists('ope7_tabla_existe') || !ope7_tabla_existe('personajes') || !ope7_tabla_existe('akumas')) {
        return null;
    }
    // D6.3: la fruta canónica vive en ope_personajes.akuma_id (comida, 47) y en
    // ope_akumas.portador_id (asignada por tirada 45 / compra 46, aún sin comer).
    $pq = $db->simple_select('ope_personajes', 'akuma_id', "id = {$pid}", array('limit' => 1));
    $akuma_id = $db->num_rows($pq) ? (int) $db->fetch_field($pq, 'akuma_id') : 0;
    if ($akuma_id < 1) {
        $aq = $db->simple_select('ope_akumas', 'id', "portador_id = {$pid} AND estado = 'con_portador'", array('limit' => 1));
        $akuma_id = $db->num_rows($aq) ? (int) $db->fetch_field($aq, 'id') : 0;
    }
    if ($akuma_id < 1) {
        return null;
    }
    return array(
        'pid'            => $pid,
        'fruta_id'       => $akuma_id,
        'nivel'          => 0,
        'cu'             => 0,
        'pp_gastado'     => 0,
        'origen'         => '',
        'potencia_sec'   => '',
        'fecha_despertar'=> 0,
        'dateline'       => TIME_NOW,
        'lastedit'       => TIME_NOW,
    );
}

function ope_fruta_secundario_default($tipo_fruta)
{
    $t = mb_strtolower((string) $tipo_fruta, 'UTF-8');
    if (strpos($t, 'logia') !== false) {
        return 'VOL';
    }
    if (strpos($t, 'zo') !== false) {
        return 'FUE';
    }
    return 'INT';
}

function ope_fruta_potencia($stats, $secundario = 'INT')
{
    $tem = function_exists('ope_rol_stat_num') ? ope_rol_stat_num($stats, 'TEM', 1) : 1;
    $sec = function_exists('ope_rol_stat_num') ? ope_rol_stat_num($stats, $secundario, 1) : 1;
    return max(1, (int) floor(($tem + $sec) / 8));
}

/**
 * Asigna fruta a PJ (unicidad). Origen: roll|pd|trama|staff
 */
function ope_fruta_asignar($pid, $fruta_id, $origen = 'roll')
{
    global $db;
    $pid = (int) $pid;
    $fruta_id = (int) $fruta_id;
    if ($pid < 1 || $fruta_id < 1) {
        return array('ok' => false, 'msg' => 'Datos inválidos.');
    }
    if (!function_exists('ope7_tabla_existe') || !ope7_tabla_existe('akumas')) {
        return array('ok' => false, 'msg' => 'Sistema de frutas no disponible.');
    }
    if (ope_fruta_pj($pid)) {
        return array('ok' => false, 'msg' => 'Este personaje ya tiene una Akuma no Mi.');
    }
    $fruta = ope_fruta_by_id($fruta_id);
    if (!$fruta || (int) ($fruta['ocupada_pid'] ?? 0) > 0) {
        return array('ok' => false, 'msg' => 'Fruta no disponible.');
    }

    // D6.3: el cupo se reserva en ope_akumas (portador_id) — el efecto 47
    // (comer) fija ope_personajes.akuma_id y consume el objeto del inventario.
    $db->update_query('ope_akumas', array(
        'portador_id' => $pid,
        'estado'      => 'con_portador',
        'origen'      => $db->escape_string((string) $origen),
    ), "id = {$fruta_id}");
    return array(
        'ok' => true,
        'msg' => 'Akuma asignada: ' . (string) ($fruta['nombre'] ?? 'la fruta') . '.',
        'fruta' => $fruta,
    );
}

/** Tirada aleatoria entre frutas libres (cualquier tier). */
function ope_fruta_roll_aleatoria($pid)
{
    $libres = ope_fruta_libres(0);
    if (empty($libres)) {
        return array('ok' => false, 'msg' => 'No hay frutas libres en el catálogo.');
    }
    $pick = $libres[array_rand($libres)];
    return ope_fruta_asignar($pid, (int) $pick['id'], 'roll');
}

function ope_fruta_liberar($pid)
{
    global $db;
    $pid = (int) $pid;
    $row = ope_fruta_pj($pid);
    if (!$row) {
        return false;
    }
    $fid = (int) $row['fruta_id'];
    $db->delete_query('rol_pj_fruta', "pid = {$pid}");
    if ($fid > 0 && $db->table_exists('rol_akuma') && $db->field_exists('ocupada_pid', 'rol_akuma')) {
        $db->update_query('rol_akuma', array('ocupada_pid' => 0), "id = {$fid} AND ocupada_pid = {$pid}");
    }
    return true;
}

function ope_fruta_add_cu($pid, $n = 1)
{
    global $db;
    $pid = (int) $pid;
    $n = max(1, (int) $n);
    $row = ope_fruta_pj($pid);
    if (!$row) {
        return false;
    }
    $cu = (int) $row['cu'] + $n;
    $db->update_query('rol_pj_fruta', array('cu' => $cu, 'lastedit' => TIME_NOW), "pid = {$pid}");
    return true;
}

/**
 * Autoservicio Nv.0→1 y Nv.1→2. Nv.3 = trámite.
 */
function ope_fruta_can_level($pid)
{
    $row = ope_fruta_pj((int) $pid);
    if (!$row) {
        return array('ok' => false, 'msg' => 'Sin fruta.');
    }
    // D6.3: la progresión de la fruta (niveles, despertar) vive en los trámites
    // 45–51 del motor 7 Seas; este autoservicio del motor anterior se desactiva.
    return array('ok' => false, 'msg' => 'La progresión de la fruta se gestiona por trámite (45–51).');
}

function ope_fruta_buy_level($pid)
{
    global $db;
    $check = ope_fruta_can_level($pid);
    if (empty($check['ok'])) {
        return $check;
    }
    $pid = (int) $pid;
    $pp = (int) $check['pp'];
    $siguiente = (int) $check['siguiente'];
    if (!ope_pp_spend($pid, $pp, 'gasto_fruta', "Fruta Nv.{$siguiente}")) {
        return array('ok' => false, 'msg' => 'No se pudo gastar PP.');
    }
    $row = ope_fruta_pj($pid);
    $db->update_query('rol_pj_fruta', array(
        'nivel' => $siguiente,
        'pp_gastado' => (int) $row['pp_gastado'] + $pp,
        'lastedit' => TIME_NOW,
    ), "pid = {$pid}");
    return array('ok' => true, 'msg' => "Fruta → Nv.{$siguiente} (−{$pp} PP).", 'nivel' => $siguiente);
}

/**
 * Staff aprueba despertar Nv.3 (tras hito). Cobra PP del tramo actual.
 */
function ope_fruta_despertar($pid)
{
    global $db;
    $pid = (int) $pid;
    $row = ope_fruta_pj($pid);
    if (!$row || (int) $row['nivel'] !== 2) {
        return array('ok' => false, 'msg' => 'Requiere fruta en Nv.2.');
    }
    $pq = $db->simple_select('rol_personajes', 'nivel, estado', "pid = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($pq);
    if (!$pj || (string) ($pj['estado'] ?? '') !== 'aprobado') {
        return array('ok' => false, 'msg' => 'PJ no válido.');
    }
    $fruta = ope_fruta_by_id((int) $row['fruta_id']);
    $tier = (int) ($fruta['tier'] ?? 1);
    $nivel_pj = max(1, (int) ($pj['nivel'] ?? 1));
    $tramo = ope_rol_tramo($nivel_pj);
    if ($tramo < ope_fruta_tramo_min_despertar($tier)) {
        return array('ok' => false, 'msg' => 'Tramo insuficiente para despertar este Tier.');
    }
    if ((int) $row['cu'] < ope_fruta_cu_req(3)) {
        return array('ok' => false, 'msg' => 'CU insuficiente para despertar.');
    }
    $pp = ope_fruta_pp_nivel($tramo, true);
    if (!ope_pp_spend($pid, $pp, 'gasto_fruta', 'Fruta Despertar Nv.3')) {
        return array('ok' => false, 'msg' => "Necesitas {$pp} PP.");
    }
    $db->update_query('rol_pj_fruta', array(
        'nivel' => 3,
        'pp_gastado' => (int) $row['pp_gastado'] + $pp,
        'fecha_despertar' => TIME_NOW,
        'lastedit' => TIME_NOW,
    ), "pid = {$pid}");
    return array('ok' => true, 'msg' => "¡Despertar! Fruta Nv.3 (−{$pp} PP).");
}

function ope_fruta_ficha_block($pid, $stats)
{
    $row = ope_fruta_pj($pid);
    if (!$row) {
        return array('tiene' => false);
    }
    $fruta = ope_fruta_by_id((int) $row['fruta_id']);
    $sec = (string) ($row['potencia_sec'] ?: ope_fruta_secundario_default($fruta['tipo'] ?? ''));
    $nivel = (int) $row['nivel'];
    $nombres = array(0 => 'Manifestación', 1 => 'Control', 2 => 'Maestría', 3 => 'Despertar');
    return array(
        'tiene' => true,
        'fruta' => $fruta,
        'nivel' => $nivel,
        'nombre_nivel' => $nombres[$nivel] ?? ('Nv.' . $nivel),
        'cu' => (int) $row['cu'],
        'cu_prox' => $nivel < 3 ? ope_fruta_cu_req($nivel + 1) : null,
        'pp_gastado' => (int) $row['pp_gastado'],
        'potencia' => ope_fruta_potencia($stats, $sec),
        'secundario' => $sec,
        'origen' => (string) $row['origen'],
        'dominio' => ope_fruta_can_level($pid),
    );
}

/**
 * Carta genérica de fruta en pie de post (tag fruta.* o fuente=fruta).
 */
function ope_fruta_carta_html($nombre, $carta_id = 'fruta.uso')
{
    $nombre = htmlspecialchars_uni((string) $nombre);
    $id = htmlspecialchars_uni((string) $carta_id);
    return '<div class="ope-tk-card ope-tk-card--fruta" data-carta="' . $id . '">'
        . '<div class="ope-tk-card-h"><span class="ope-tk-tier">Fruta</span><b>' . $nombre . '</b></div>'
        . '<div class="ope-tk-card-b"><span class="ope-tk-cost">fuente=fruta</span></div>'
        . '</div>';
}

// ─────────────────────────────────────────────────────────────
// Presentación del catálogo (biblioteca + ficha) · modal compartido
// Canon rareza/tier: SISTEMA-DE-FRUTAS.md §2 (I Común … V Legendaria).
// ─────────────────────────────────────────────────────────────

/** Rareza canónica por tier (I–V). */
function ope_fruta_tier_rareza($tier)
{
    static $map = array(1 => 'Común', 2 => 'Poco común', 3 => 'Rara', 4 => 'Épica', 5 => 'Legendaria');
    return $map[max(1, min(5, (int) $tier))];
}

/** Numeral romano del tier. */
function ope_fruta_tier_roman($tier)
{
    static $map = array(1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V');
    return $map[max(1, min(5, (int) $tier))];
}

/** Familia base (paramecia|zoa|logia) a partir del tipo verboso del catálogo. */
function ope_fruta_tipo_base($tipo)
{
    $t = mb_strtolower((string) $tipo, 'UTF-8');
    if (strpos($t, 'logia') !== false) {
        return 'logia';
    }
    if (strpos($t, 'zoa') !== false || strpos($t, 'zoan') !== false) {
        return 'zoa';
    }
    return 'paramecia';
}

/**
 * Selecciona aleatoriamente una fruta disponible (libre y activa) de rol_akuma.
 * @return int ID de la fruta sorteada, o 0 si no hay stock disponible.
 */
function ope_fruta_sortear_aleatoria()
{
    global $db;
    if (!$db->table_exists('rol_akuma')) {
        return 0;
    }
    $where = 'activo = 1';
    if ($db->field_exists('ocupada_pid', 'rol_akuma')) {
        $where .= ' AND (ocupada_pid = 0 OR ocupada_pid IS NULL)';
    }
    $q = $db->simple_select('rol_akuma', 'id', $where);
    $libres = array();
    while ($r = $db->fetch_array($q)) {
        $libres[] = (int) $r['id'];
    }
    if (empty($libres)) {
        return 0;
    }
    $idx = array_rand($libres);
    return $libres[$idx];
}

/**
 * Normaliza una fila de rol_akuma al shape que consumen la tarjeta y el modal.
 * Privacy-gate: $can_see_details ($is_owner || $is_staff) controla la visibilidad de $caps.
 * Las notas de staff y notas de diseño quedan siempre purgadas.
 */
function ope_fruta_norm(array $r, $can_see_details = false)
{
    $tier = (int) ($r['tier'] ?? 0);
    if ($tier < 1 || $tier > 5) {
        $tier = ope_fruta_rareza_a_tier($r['rareza'] ?? '');
    }

    // Capacidades: caps_json.raw (esquema rico) o capacidades_raw (JSON seed).
    $caps   = '';
    $origen = trim((string) ($r['origen'] ?? ''));
    if ($can_see_details) {
        if (!empty($r['caps_json'])) {
            $c = is_string($r['caps_json']) ? json_decode($r['caps_json'], true) : $r['caps_json'];
            if (is_array($c)) {
                $caps = (string) ($c['raw'] ?? '');
                if ($origen === '') {
                    $origen = (string) ($c['origen'] ?? '');
                }
            }
        }
        if ($caps === '' && !empty($r['capacidades_raw'])) {
            $caps = (string) $r['capacidades_raw'];
        }
    }

    $usuario = trim((string) ($r['usuario'] ?? ''));
    if ($usuario === '' || mb_strtolower($usuario, 'UTF-8') === 'nadie') {
        $usuario = '';
    }

    $ocupada_pid = (int) ($r['ocupada_pid'] ?? 0);
    if ($ocupada_pid > 0 && $usuario === '') {
        global $db;
        if (isset($db) && ope7_tabla_existe('personajes')) {
            $pq = $db->simple_select(ope7_tabla('personajes'), 'nombre', "id = {$ocupada_pid}", array('limit' => 1));
            if ($pq && $db->num_rows($pq)) {
                $usuario = (string) $db->fetch_field($pq, 'nombre');
            }
        }
    }

    return array(
        'id'          => (int) ($r['id'] ?? 0),
        'nombre'      => (string) ($r['nombre'] ?? ''),
        'tipo'        => (string) ($r['tipo'] ?? ''),
        'tipo_base'   => ope_fruta_tipo_base($r['tipo'] ?? ''),
        'tier'        => $tier,
        'tier_roman'  => ope_fruta_tier_roman($tier),
        'rareza'      => ope_fruta_tier_rareza($tier),
        'secundario'  => (string) ($r['secundario'] ?? ''),
        'potencia'    => (string) ($r['potencia_formula'] ?? ''),
        'desc'        => (string) ($r['descripcion_breve'] ?? ($r['descripcion'] ?? '')),
        'efecto'      => (string) ($r['efecto_general'] ?? ''),
        'debilidad'   => (string) ($r['debilidad'] ?? ''),
        'despertar'   => (string) ($r['despertar'] ?? ''),
        'caps'        => $caps,
        'origen'      => $origen,
        'ocupada_pid' => $ocupada_pid,
        'usuario'     => $usuario,
        'imagen'      => (string) ($r['imagen'] ?? ''),
    );
}

/**
 * Overlay + JS del modal de Akuma no Mi. Fuente ÚNICA: lo usan tanto la
 * biblioteca (grid de tarjetas) como la ficha (mini-carta). Idempotente: solo
 * emite una vez por request. Expone window.OPEFruta.open(data) / .close().
 * Los estilos viven en docs/themes/ope.css (clases .bib-overlay/.bib-d-*).
 */
function ope_fruta_modal_assets($bburl = '')
{
    static $done = false;
    if ($done) {
        return '';
    }
    $done = true;
    $bb = htmlspecialchars_uni((string) $bburl);

    // Parser de capacidades_raw → tarjetas por nivel con etiqueta de tipo.
    // NOWDOC: JS literal, sin interpolación ni escapes de PHP.
    $helpers = <<<'JS'
function typeCls(t){t=(t||"").toLowerCase();if(t.indexOf("propiedad")>=0)return"tp-prop";if(t.indexOf("habilitador")>=0)return"tp-hab";if(t.indexOf("mini")>=0)return"tp-mini";if(t.indexOf("pasiva")>=0)return"tp-pas";return"tp-oth";}
function capB(s){return esc(s).replace(/(Techos?:|Estados:|Techo hasta|Techo por objetivo hasta|Costes?:)/g,"<b>$1</b>");}
function capsHtml(raw){if(!raw||!String(raw).trim())return"";var L=String(raw).split("\n"),o='<div class="bib-caps">',op=false;
for(var i=0;i<L.length;i++){var ln=L[i].trim();if(!ln)continue;
var mL=ln.match(/^Nv\.(\d)\s*[\u2014-]\s*(.+)$/);
if(mL){if(op)o+="</div>";o+='<div class="bib-caps-lvl l'+mL[1]+'"><div class="bib-caps-lvlh"><span class="bib-caps-lvln">Nv.'+mL[1]+'</span>'+esc(mL[2])+"</div>";op=true;continue;}
var mC=ln.match(/^-\s*(CAP-0\d)\s+(.+?)\s*\(([^)]+)\)\s*:\s*([\s\S]+)$/);
if(mC){o+='<div class="bib-cap"><div class="bib-cap-h"><span class="bib-cap-id">'+esc(mC[1])+'</span><span class="bib-cap-nm">'+esc(mC[2])+'</span><span class="bib-cap-tp '+typeCls(mC[3])+'">'+esc(mC[3])+'</span></div><div class="bib-cap-b">'+capB(mC[4])+'</div></div>';continue;}
var mP=ln.match(/^-\s*Pasiva\s*:\s*([\s\S]+)$/);
if(mP){o+='<div class="bib-cap bib-cap-pas"><div class="bib-cap-h"><span class="bib-cap-tp tp-pas">Pasiva</span></div><div class="bib-cap-b">'+capB(mP[1])+'</div></div>';continue;}
var m2=ln.match(/^-\s*(CAP-0\d)\s+(.+?)\s*:\s*([\s\S]+)$/);
if(m2){o+='<div class="bib-cap"><div class="bib-cap-h"><span class="bib-cap-id">'+esc(m2[1])+'</span><span class="bib-cap-nm">'+esc(m2[2])+'</span></div><div class="bib-cap-b">'+capB(m2[3])+'</div></div>';continue;}
o+='<div class="bib-cap-b">'+capB(ln.replace(/^-\s*/,""))+'</div>';}
if(op)o+="</div>";o+="</div>";
return '<div class="bib-d-block"><span class="bib-d-h">Capacidades y pasivas</span>'+o+'</div>';}
JS;

    return '<div class="bib-overlay fruta-overlay" id="frutaOverlay" hidden>'
        . '<div class="bib-detail" id="frutaDetail" role="dialog" aria-modal="true" aria-label="Detalle de Akuma no Mi"></div></div>'
        . '<script id="ope-fruta-modal">' . "\n"
        . '(function(){' . "\n"
        . 'var BB=' . json_encode($bb) . ';' . "\n"
        . 'var overlay=document.getElementById("frutaOverlay"),detail=document.getElementById("frutaDetail");' . "\n"
        . 'if(!overlay||!detail)return;' . "\n"
        . 'function esc(s){return (s==null?"":String(s)).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");}' . "\n"
        . 'var LBL={paramecia:"Paramecia",zoa:"Zoan",logia:"Logia"};' . "\n"
        . 'function fruitSvg(){return \'<span class="bib-media-fruit" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 12c4-6 14-6 16 2 2 8-6 20-16 22C14 34 6 22 8 14c2-8 12-8 16-2z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M24 12c-1-3 1-6 4-7" fill="none" stroke="currentColor" stroke-width="2.5"/></svg></span>\';}' . "\n"
        . 'function media(p){return p.imagen?\'<img src="\'+esc(p.imagen)+\'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\\\'no-img\\\');this.remove()">\':fruitSvg();}' . "\n"
        . 'function row(l,v){return v?\'<div class="bib-d-row"><span class="bib-d-l">\'+esc(l)+\'</span><span class="bib-d-v">\'+v+\'</span></div>\':"";}' . "\n"
        . 'function block(l,v,pre){if(!v||!String(v).trim())return "";var body=pre?\'<pre class="bib-d-caps">\'+esc(v)+"</pre>":"<p>"+esc(v).replace(/\\n/g,"<br>")+"</p>";return \'<div class="bib-d-block"><span class="bib-d-h">\'+esc(l)+"</span>"+body+"</div>";}' . "\n"
        . $helpers . "\n"
        . 'function open(p){' . "\n"
        . 'var poss;if(p.ocupada_pid>0){poss=\'<a href="\'+BB+"/ficha.php?pid="+p.ocupada_pid+\'">\'+esc(p.usuario||("Personaje #"+p.ocupada_pid))+"</a>";}'
        . 'else if(p.usuario){poss=esc(p.usuario);}'
        . 'else{poss=\'<span class="fruta-libre">Libre</span>\';}' . "\n"
        . 'var grid=row("Tipo",esc(p.tipo||LBL[p.tipo_base]||""))+row("Stat de Potencia","TEM + "+esc(p.secundario||"—"))+row("Poseedor",poss);' . "\n"
        . 'var blocks=block("Concepto",p.desc)+block("Efecto general",p.efecto)+block("Fórmula de Potencia",p.potencia,true)+block("Debilidades",p.debilidad)+block("Despertar",p.despertar)+(p.caps?capsHtml(p.caps):"");' . "\n"
        . 'detail.innerHTML=\'<button type="button" class="bib-d-close" aria-label="Cerrar">\\u2715</button>\''
        . '+\'<div class="bib-d-head tipo-\'+esc(p.tipo_base)+\'"><div class="bib-d-media">\'+media(p)+\'</div>\''
        . '+\'<div class="bib-d-title"><span class="bib-d-kicker">\'+esc(LBL[p.tipo_base]||p.tipo_base)+\'</span><h2>\'+esc(p.nombre)+\'</h2>\''
        . '+\'<div class="bib-d-tags"><span class="bib-d-tier t\'+esc(p.tier)+\'">Tier \'+esc(p.tier_roman)+\' · \'+esc(p.rareza)+\'</span>\'+(p.origen?\'<span class="bib-d-origen">\'+esc(p.origen)+\'</span>\':"")+\'</div></div></div>\''
        . '+\'<div class="bib-d-grid">\'+grid+\'</div>\'+blocks;' . "\n"
        . 'overlay.hidden=false;document.body.classList.add("bib-no-scroll");requestAnimationFrame(function(){detail.classList.add("in");});}' . "\n"
        . 'function close(){detail.classList.remove("in");overlay.hidden=true;document.body.classList.remove("bib-no-scroll");}' . "\n"
        . 'overlay.addEventListener("click",function(e){if(e.target===overlay||(e.target.closest&&e.target.closest(".bib-d-close")))close();});' . "\n"
        . 'document.addEventListener("keydown",function(e){if(e.key==="Escape"&&!overlay.hidden)close();});' . "\n"
        . 'window.OPEFruta={open:open,close:close};' . "\n"
        . '})();' . "\n"
        . '</script>';
}
