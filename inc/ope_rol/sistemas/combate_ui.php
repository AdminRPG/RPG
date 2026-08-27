<?php
/**
 * One Piece: 7 Seas · Zona B del posteo de combate (F2.2, 5.10)
 * -----------------------------------------------------------------
 * El formato de posteo en dos zonas (11.12): la Zona A es la narrativa
 * (mín. 350 palabras si aspira a puntuar) y la Zona B son las CARTAS del
 * turno — técnicas, consumibles, estados, modificadores y contadores.
 *
 * Este módulo:
 *   1. ope7_zonab_editor_html()   → panel JS bajo el editor MyBB (newreply/
 *      newthread): compone las cartas en vivo, valida PA con AVISO (nunca
 *      bloquea) y al enviar apenda [ope7-zonab]{json}[/ope7-zonab] al mensaje.
 *   2. ope7_zonab_parse()         → hook parse_message: convierte el bloque en
 *      el HTML de la Zona B que ve el rival bajo el post.
 *   3. ope7_zonab_on_post()       → hooks datahandler_post_insert_*_end:
 *      persiste turnos_combate + sala_combate con los avisos del turno
 *      (presupuesto, P10, puertas/reposos, primer post, tope de sala).
 *
 * Regla de oro: la validación avisa, nunca bloquea la narrativa; el veredicto
 * final se computa al cierre (resolución de sistemas/combate.php) y lo firma
 * el staff en el panel de resolución (F2.3).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/**
 * Datos del personaje activo para el panel (técnicas, estados, contadores).
 */
function ope7_zonab_contexto()
{
    global $mybb, $db;
    $uid = (int) ($mybb->user['uid'] ?? 0);
    if ($uid < 1) {
        return null;
    }
    $act = ope7_pj_activo($uid);
    if (!$act || ($act['tabla'] ?? '') !== 'ope' || (int) ($act['id'] ?? 0) < 1) {
        return null; // solo personajes del esquema 7 Seas
    }
    $pid = (int) $act['id'];
    $f = ope7_pj_get($pid);
    if (!$f || $f['estado_vida'] !== 'activa') {
        return null;
    }
    $sec = ope7_pj_secundarios($f);

    $tecnicas = array();
    if (ope7_tabla_existe('tecnicas')) {
        $q = $db->simple_select('ope_tecnicas', '*', "personaje_id = {$pid} AND activa = 1", array('order_by' => 'tier DESC, id ASC'));
        while ($r = $db->fetch_array($q)) {
            $tecnicas[] = array(
                'id'      => (int) $r['id'],
                'nombre'  => (string) $r['nombre'],
                'tier'    => (int) $r['tier'],
                'tipo'    => (string) $r['tipo'],
                'pa'      => (int) $r['pa'],
                'pe_pct'  => (int) $r['pe_pct'],
                'reposo'  => (int) $r['reposo'],
                'puerta'  => (int) $r['puerta_turno'],
                'efectos' => json_decode((string) ($r['efectos'] ?? ''), true) ?: array(),
            );
        }
    }

    $estados = array();
    if (ope7_tabla_existe('estados')) {
        $q = $db->simple_select('ope_estados', 'id, nombre, grado, categoria', 'activo = 1', array('order_by' => 'categoria ASC, nombre ASC'));
        while ($r = $db->fetch_array($q)) {
            $estados[] = array('id' => (int) $r['id'], 'nombre' => (string) $r['nombre'], 'grado' => (int) $r['grado'], 'categoria' => (string) $r['categoria']);
        }
    }

    $pa = ope7_combate_pa_turno((int) ($f['agi'] ?? 0), (int) ($f['nivel'] ?? 1));

    return array(
        'uid' => $uid, 'pid' => $pid,
        'nombre' => (string) $f['nombre'],
        'nivel'  => (int) $f['nivel'],
        'agi'    => (int) ($f['agi'] ?? 0),
        'secundarios' => array('pv' => (int) $sec['pv'], 'pe' => (int) $sec['pe'], 'pa' => (int) $sec['pa']),
        'pa_base' => $pa,
        'tecnicas' => $tecnicas,
        'estados'  => $estados,
        'tid'  => (int) ($mybb->input['tid'] ?? 0),
        'fid'  => (int) ($mybb->input['fid'] ?? 0),
    );
}

/**
 * Panel Zona B bajo el editor (newreply/newthread). Vanilla JS (sin jQuery),
 * siguiendo el patrón del panel RPG de la era anterior pero con datos 7 Seas.
 */
function ope7_zonab_editor_html()
{
    $c = ope7_zonab_contexto();
    if (!$c) {
        return '';
    }
    $json = json_encode($c, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $json_html = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

    $tecs = $c['tecnicas'];
    $tecs_html = '';
    if ($tecs) {
        $tiers = array('', 'Básica', 'Media', 'Avanzada', 'Maestra', 'Épica');
        foreach ($tecs as $t) {
            $tecs_html .= '<button type="button" class="ope7-zb-tec" data-id="' . (int) $t['id'] . '" data-nombre="' . htmlspecialchars($t['nombre'], ENT_QUOTES) . '"'
                . ' data-tier="' . (int) $t['tier'] . '" data-pa="' . (int) $t['pa'] . '" data-pe="' . (int) $t['pe_pct'] . '"'
                . ' data-reposo="' . (int) $t['reposo'] . '" data-puerta="' . (int) $t['puerta'] . '" aria-pressed="false">'
                . '<b>' . htmlspecialchars($t['nombre']) . '</b>'
                . '<small>T' . (int) $t['tier'] . ' ' . $tiers[(int) $t['tier']] . ' · ' . htmlspecialchars($t['tipo']) . ' · ' . (int) $t['pa'] . ' PA · ' . (int) $t['pe_pct'] . '% PE'
                . ((int) $t['reposo'] > 1 ? ' · reposo ' . (int) $t['reposo'] : '')
                . ((int) $t['puerta'] > 0 ? ' · puerta turno ' . (int) $t['puerta'] : '')
                . '</small></button>';
        }
    } else {
        $tecs_html = '<p class="ope7-zb-empty">Sin técnicas en la librería. Créalas por el trámite 13.</p>';
    }

    $est_html = '';
    if ($c['estados']) {
        foreach ($c['estados'] as $e) {
            $est_html .= '<button type="button" class="ope7-zb-est" data-nombre="' . htmlspecialchars($e['nombre'], ENT_QUOTES) . '"'
                . ' data-grado="' . (int) $e['grado'] . '" data-categoria="' . htmlspecialchars($e['categoria'], ENT_QUOTES) . '" aria-pressed="false">'
                . htmlspecialchars($e['nombre']) . '</button>';
        }
    }

    $pa = (int) $c['pa_base'];

    return '<div class="ope7-zb" id="ope7-zb" data-context="' . $json_html . '">'
        . '<div class="ope7-zb-head"><span class="ope7-zb-badge">ZONA B</span>'
        . '<span class="ope7-zb-hint">Cartas del turno — se publican bajo tu post y el rival las lee. Valida con aviso, nunca bloquea (5.10).</span></div>'
        . '<div class="ope7-zb-pj">Turno de <b>' . htmlspecialchars($c['nombre']) . '</b> · Nv ' . (int) $c['nivel'] . ' · AGI ' . (int) $c['agi']
        . ' · PA base <b id="ope7-zb-pabase">' . $pa . '</b> (6 + AGI/10 + Nv/5)</div>'

        . '<div class="ope7-zb-cols">'

        // ── Columna 1: técnicas ──
        . '<div class="ope7-zb-col">'
        . '<h4 class="ope7-zb-tit">Técnicas jugadas <small>(click para añadir)</small></h4>'
        . '<div class="ope7-zb-tecs">' . $tecs_html . '</div>'
        . '<div class="ope7-zb-seleccion" id="ope7-zb-tecs-sel" data-count="0"><span class="ope7-zb-empty">Ninguna técnica en el turno.</span></div>'
        . '</div>'

        // ── Columna 2: estados + consumibles ──
        . '<div class="ope7-zb-col">'
        . '<h4 class="ope7-zb-tit">Estados activos</h4>'
        . '<div class="ope7-zb-ests">' . $est_html . '</div>'
        . '<h4 class="ope7-zb-tit">Consumibles <small>(coste de PA de su ficha)</small></h4>'
        . '<div class="ope7-zb-consumible-row"><input type="text" class="ope7-zb-input" id="ope7-zb-consumible-nombre" placeholder="Poción, botiquín…" maxlength="60">'
        . '<input type="number" class="ope7-zb-input ope7-zb-input--pa" id="ope7-zb-consumible-pa" placeholder="PA" min="0" max="7" value="2">'
        . '<button type="button" class="ope7-zb-add" id="ope7-zb-consumible-add">+</button></div>'
        . '<div class="ope7-zb-seleccion" id="ope7-zb-cons-sel"></div>'
        . '</div>'

        // ── Columna 3: modificadores + contadores ──
        . '<div class="ope7-zb-col">'
        . '<h4 class="ope7-zb-tit">Modificadores del turno</h4>'
        . '<label class="ope7-zb-check"><input type="checkbox" id="ope7-zb-solitario"> 1 contra varios (+3 PA)</label>'
        . '<label class="ope7-zb-check"><input type="checkbox" id="ope7-zb-sobrecarga"> Sobrecarga (2×PE, +25% daño — 1/tema por cada 50 INT)</label>'
        . '<input type="text" class="ope7-zb-input" id="ope7-zb-mods-nota" placeholder="Raciales, dotes, Haki, naval…" maxlength="160">'
        . '<h4 class="ope7-zb-tit">Contadores del turno</h4>'
        . '<div class="ope7-zb-counts">'
        . '<label>PV <input type="number" class="ope7-zb-input" id="ope7-zb-pv" value="' . (int) $c['secundarios']['pv'] . '" min="0"></label>'
        . '<label>PE <input type="number" class="ope7-zb-input" id="ope7-zb-pe" value="' . (int) $c['secundarios']['pe'] . '" min="0"></label>'
        . '<label>PA restante <input type="number" class="ope7-zb-input" id="ope7-zb-pa-rest" value="' . $pa . '" min="0" readonly></label>'
        . '</div>'
        . '<div class="ope7-zb-result" id="ope7-zb-result">PA 0/' . $pa . ' · reserva ' . $pa . '</div>'
        . '<button type="button" class="ope7-zb-btn" id="ope7-zb-validar">Validar turno</button>'
        . '<p class="ope7-zb-aviso" id="ope7-zb-aviso">Los excesos se marcan como aviso para el staff; no bloquean tu post.</p>'
        . '</div>'
        . '</div>'
        . '<script>'
        . '(()=>{' . "\n"
        . 'var C={};try{C=JSON.parse(document.getElementById("ope7-zb").getAttribute("data-context")||"{}");}catch(x){}'
        . 'var root=document.getElementById("ope7-zb");if(!root)return;'
        . 'var selT=[],selC=[],selE=[],solitario=false,sobrecarga=false;'
        . 'function paTotal(){var b=C.pa_base||0;if(solitario)b+=3;return b;}'
        . 'function costeTecs(){return selT.reduce(function(s,t){return s+(t.pa||0);},0);}'
        . 'function costeCons(){return selC.reduce(function(s,c){return s+(parseInt(c.pa)||0);},0);}'
        . 'function gastado(){return costeTecs()+costeCons();}'
        . 'function renderSel(){'
        . '  var w=document.getElementById("ope7-zb-tecs-sel");if(!w)return;'
        . '  var h=[];selT.forEach(function(t,i){h.push(\'<span class="ope7-zb-chip">\'+t.nombre+\' <i>T\'+t.tier+\' · \'+t.pa+\' PA · \'+t.pe+\'% PE\';'
        . '    if(t.puerta>0)h.push(\' · puerta \'+t.puerta);h.push(\'</i> <button type="button" data-rm="t\'+i+\'">×</button></span>\');});'
        . '  w.innerHTML=h.join("")||\'<span class="ope7-zb-empty">Ninguna técnica en el turno.</span>\';'
        . '  w.setAttribute("data-count",selT.length);'
        . '}'
        . 'function renderCons(){'
        . '  var w=document.getElementById("ope7-zb-cons-sel");if(!w)return;'
        . '  var h=[];selC.forEach(function(c,i){h.push(\'<span class="ope7-zb-chip">\'+c.nombre+\' <i>\'+c.pa+\' PA</i> <button type="button" data-rm="c\'+i+\'">×</button></span>\');});'
        . '  w.innerHTML=h.join("")||"";'
        . '}'
        . 'function renderEst(){'
        . '  var w=document.getElementById("ope7-zb-ests");if(!w)return;'
        . '  Array.prototype.forEach.call(w.querySelectorAll("button"),function(b){b.setAttribute("aria-pressed",selE.indexOf(b.getAttribute("data-nombre"))>-1?"true":"false");});'
        . '}'
        . 'function refresh(){'
        . '  var tot=paTotal(),gas=gastado(),res=Math.max(0,tot-gas);'
        . '  var r=document.getElementById("ope7-zb-result");if(r)r.textContent="PA "+gas+"/"+tot+" · reserva "+res;'
        . '  var pr=document.getElementById("ope7-zb-pa-rest");if(pr)pr.value=res;'
        . '}'
        . 'root.addEventListener("click",function(ev){'
        . '  var rm=ev.target.getAttribute&&ev.target.getAttribute("data-rm");'
        . '  if(rm){var ix=parseInt(rm.slice(1),10);if(rm[0]==="t"){selT.splice(ix,1);renderSel();}else if(rm[0]==="c"){selC.splice(ix,1);renderCons();}refresh();return;}'
        . '  var tec=ev.target.closest(".ope7-zb-tec");'
        . '  if(tec){var id=tec.getAttribute("data-id");var ex=selT.filter(function(t){return String(t.id)===String(id);}).length;'
        . '    if(ex>0){selT=selT.filter(function(t){return String(t.id)!==String(id);});tec.setAttribute("aria-pressed","false");}'
        . '    else{selT.push({id:id,nombre:tec.getAttribute("data-nombre"),tier:parseInt(tec.getAttribute("data-tier"),10),pa:parseInt(tec.getAttribute("data-pa"),10),pe:parseInt(tec.getAttribute("data-pe"),10),reposo:parseInt(tec.getAttribute("data-reposo"),10),puerta:parseInt(tec.getAttribute("data-puerta"),10)});tec.setAttribute("aria-pressed","true");}'
        . '    renderSel();refresh();return;}'
        . '  var est=ev.target.closest(".ope7-zb-est");'
        . '  if(est){var n=est.getAttribute("data-nombre");var ix=selE.indexOf(n);if(ix>-1){selE.splice(ix,1);}else{selE.push(n);}renderEst();return;}'
        . '});'
        . 'var addC=document.getElementById("ope7-zb-consumible-add");'
        . 'if(addC){addC.addEventListener("click",function(){'
        . '  var n=document.getElementById("ope7-zb-consumible-nombre").value.trim();'
        . '  var p=parseInt(document.getElementById("ope7-zb-consumible-pa").value,10)||0;'
        . '  if(!n)return;selC.push({nombre:n,pa:p});renderCons();refresh();'
        . '  document.getElementById("ope7-zb-consumible-nombre").value="";document.getElementById("ope7-zb-consumible-pa").value="2";'
        . '});}'
        . 'var sol=document.getElementById("ope7-zb-solitario");if(sol)sol.addEventListener("change",function(){solitario=sol.checked;refresh();});'
        . 'var sob=document.getElementById("ope7-zb-sobrecarga");if(sob)sob.addEventListener("change",function(){sobrecarga=sob.checked;refresh();});'
        . 'var val=document.getElementById("ope7-zb-validar");'
        . 'if(val){val.addEventListener("click",function(){'
        . '  var tot=paTotal(),gas=gastado(),avisos=[];'
        . '  if(gas>tot)avisos.push("Presupuesto EXCEDIDO: "+gas+" PA de "+tot+" (aviso para el staff).");'
        . '  else avisos.push("Presupuesto OK: "+gas+"/"+tot+" PA, reserva "+(tot-gas)+".");'
        . '  var obj={};selT.forEach(function(t){obj[t.id]=(obj[t.id]||0)+1;});'
        . '  Object.keys(obj).forEach(function(k){if(obj[k]>1)avisos.push("Técnica repetida en el turno: revisa su reposo (aviso).");});'
        . '  if(sobrecarga)avisos.push("Sobrecarga declarada: 2×PE y +25% daño (1/tema por cada 50 INT).");'
        . '  var a=document.getElementById("ope7-zb-aviso");if(a){a.textContent=avisos.join(" | ");a.classList.add("is-on");}'
        . '});}'
        . 'var ta=document.querySelector("textarea[name=\'message\']");'
        . 'function applyBlock(){'
        . '  var payload={tecnicas:selT,consumibles:selC,estados:selE,mods:{solitario:solitario,sobrecarga:sobrecarga,nota:(document.getElementById("ope7-zb-mods-nota")||{}).value||""},'
        . '    contadores:{pv:parseInt((document.getElementById("ope7-zb-pv")||{}).value,10)||0,pe:parseInt((document.getElementById("ope7-zb-pe")||{}).value,10)||0,pa_restante:paTotal()-gastado()},'
        . '    resumen:{pa_total:paTotal(),pa_gastado:gastado(),reserva:paTotal()-gastado()}};'
        . '  if(!selT.length&&!selC.length&&!selE.length)return;'
        . '  var ed=window.MyBBEditor;var cur=(ed&&typeof ed.val==="function")?ed.val():(ta?ta.value:"");'
        . '  cur=cur.replace(/\\n*\\[ope7-zonab\\][\\s\\S]*?\\[\\/ope7-zonab\\]/gi,"");'
        . '  cur=cur.replace(/\\s+$/,"")+"\\n\\n[ope7-zonab]"+JSON.stringify(payload)+"[/ope7-zonab]";'
        . '  if(ed&&typeof ed.val==="function"){try{ed.val(cur);}catch(x){}}'
        . '  if(ta){ta.value=cur;}'
        . '}'
        . 'var form=ta?ta.form:null;if(form){form.addEventListener("submit",applyBlock,true);form.addEventListener("submit",applyBlock);}'
        . '})();'
        . '</script>'
        . '</div>';
}

/**
 * Hook parse_message: convierte [ope7-zonab]{json}[/ope7-zonab] en el HTML de
 * la Zona B que el rival ve bajo el post. Si el JSON no decodifica, deja el
 * contenido crudo en un bloque (nunca rompe el mensaje).
 */
function ope7_zonab_parse($message)
{
    if (stripos($message, '[ope7-zonab]') === false) {
        return $message;
    }
    return preg_replace_callback('#\[ope7-zonab\]([\s\S]*?)\[/ope7-zonab\]#i', function ($m) {
        $payload = json_decode(trim($m[1]), true);
        if (!is_array($payload)) {
            return '<div class="ope7-zb-block ope7-zb-block--raw"><div class="ope7-zb-block-h">Zona B</div><div class="ope7-zb-block-b">' . htmlspecialchars(trim($m[1])) . '</div></div>';
        }
        return ope7_zonab_render($payload);
    }, $message);
}

/** Render de la Zona B desde el payload (cartas + contadores). */
function ope7_zonab_render(array $p)
{
    $h = '<div class="ope7-zb-block">';
    $h .= '<div class="ope7-zb-block-h">ZONA B · Cartas del turno</div>';
    $h .= '<div class="ope7-zb-block-b">';

    $tecs = (array) ($p['tecnicas'] ?? array());
    if ($tecs) {
        $tiers = array('', 'Básica', 'Media', 'Avanzada', 'Maestra', 'Épica');
        $h .= '<div class="ope7-zb-sec"><b>Técnicas</b><div class="ope7-zb-cards">';
        foreach ($tecs as $t) {
            $h .= '<div class="ope7-zb-card ope7-zb-card--t' . (int) ($t['tier'] ?? 1) . '">'
                . '<div class="ope7-zb-card-n">' . htmlspecialchars((string) ($t['nombre'] ?? 'Técnica')) . '</div>'
                . '<div class="ope7-zb-card-m">T' . (int) ($t['tier'] ?? 1) . ' ' . $tiers[(int) ($t['tier'] ?? 1)] . ' · ' . (int) ($t['pa'] ?? 0) . ' PA · ' . (int) ($t['pe'] ?? 0) . '% PE'
                . ((int) ($t['reposo'] ?? 0) > 1 ? ' · reposo ' . (int) $t['reposo'] : '')
                . ((int) ($t['puerta'] ?? 0) > 0 ? ' · puerta turno ' . (int) $t['puerta'] : '')
                . '</div></div>';
        }
        $h .= '</div></div>';
    }
    $cons = (array) ($p['consumibles'] ?? array());
    if ($cons) {
        $h .= '<div class="ope7-zb-sec"><b>Consumibles</b><div class="ope7-zb-cards">';
        foreach ($cons as $c) {
            $h .= '<div class="ope7-zb-card"><div class="ope7-zb-card-n">' . htmlspecialchars((string) ($c['nombre'] ?? 'Consumible')) . '</div>'
                . '<div class="ope7-zb-card-m">' . (int) ($c['pa'] ?? 0) . ' PA (coste de ficha)</div></div>';
        }
        $h .= '</div></div>';
    }
    $ests = (array) ($p['estados'] ?? array());
    if ($ests) {
        $h .= '<div class="ope7-zb-sec"><b>Estados</b><div class="ope7-zb-chips">';
        foreach ($ests as $e) {
            $h .= '<span class="ope7-zb-chip ope7-zb-chip--est">' . htmlspecialchars((string) $e) . '</span>';
        }
        $h .= '</div></div>';
    }
    $mods = (array) ($p['mods'] ?? array());
    if (!empty($mods['solitario']) || !empty($mods['sobrecarga']) || trim((string) ($mods['nota'] ?? '')) !== '') {
        $h .= '<div class="ope7-zb-sec"><b>Modificadores</b><div class="ope7-zb-chips">';
        if (!empty($mods['solitario'])) {
            $h .= '<span class="ope7-zb-chip">1 contra varios +3 PA</span>';
        }
        if (!empty($mods['sobrecarga'])) {
            $h .= '<span class="ope7-zb-chip">Sobrecarga (2×PE, +25%)</span>';
        }
        if (trim((string) ($mods['nota'] ?? '')) !== '') {
            $h .= '<span class="ope7-zb-chip">' . htmlspecialchars(trim((string) $mods['nota'])) . '</span>';
        }
        $h .= '</div></div>';
    }
    $sum = (array) ($p['resumen'] ?? array());
    $cnt = (array) ($p['contadores'] ?? array());
    $h .= '<div class="ope7-zb-sec ope7-zb-sec--resumen"><b>Contadores del turno</b> '
        . '<span>PV ' . (int) ($cnt['pv'] ?? 0) . '</span> · <span>PE ' . (int) ($cnt['pe'] ?? 0) . '</span> · '
        . '<span>PA ' . (int) ($sum['pa_gastado'] ?? 0) . '/' . (int) ($sum['pa_total'] ?? 0) . '</span> · '
        . '<span>Reserva ' . (int) ($sum['reserva'] ?? 0) . '</span></div>';

    $h .= '</div></div>';
    return $h;
}

/**
 * F2.3 — Panel de resolución (A.3 «Combate»). Lista de salas con turnos,
 * excesos marcados y generación/regeneración de veredictos con matices.
 */
function ope7_resolucion_lista()
{
    global $db;
    if (!ope7_tabla_existe('sala_combate')) {
        return array();
    }
    $q = $db->query("SELECT s.*, (SELECT COUNT(*) FROM " . ope7_tabla_full('turnos_combate') . " t WHERE t.combate_id = s.id) AS n_turnos "
        . "FROM " . ope7_tabla_full('sala_combate') . " s ORDER BY s.id DESC");
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Turnos de una sala (para el detalle y la resolución). */
function ope7_resolucion_turnos($sala_id)
{
    global $db;
    $sala_id = (int) $sala_id;
    if ($sala_id < 1 || !ope7_tabla_existe('turnos_combate')) {
        return array();
    }
    $q = $db->simple_select('ope_turnos_combate', '*', "combate_id = {$sala_id}", array('order_by' => 'turno', 'order_dir' => 'ASC'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $r['acciones_arr'] = json_decode((string) ($r['acciones'] ?? ''), true) ?: array();
        $r['veredicto_arr'] = json_decode((string) ($r['veredicto'] ?? ''), true) ?: array();
        $out[] = $r;
    }
    return $out;
}

/** Matices activos del catálogo (para ajustar antes de regenerar). */
function ope7_resolucion_matices()
{
    global $db;
    if (!ope7_tabla_existe('matices_combate')) {
        return array();
    }
    $q = $db->simple_select('ope_matices_combate', '*', 'activo = 1', array('order_by' => 'id ASC'));
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $r['efecto_arr'] = json_decode((string) ($r['efecto'] ?? ''), true) ?: array();
        $out[] = $r;
    }
    return $out;
}

/**
 * Genera (o regenera) los veredictos de una sala: empareja los ataques de los
 * turnos con el motor puro y los persiste en resoluciones_combate. Devuelve
 * el resumen para el histórico.
 */
function ope7_resolucion_generar($sala_id, $matices_ids = array())
{
    global $db;
    $sala_id = (int) $sala_id;
    if ($sala_id < 1 || !ope7_tabla_existe('resoluciones_combate')) {
        return array('ok' => false, 'msg' => 'Motor de resolución no disponible.');
    }
    $turnos = ope7_resolucion_turnos($sala_id);
    if (!$turnos) {
        return array('ok' => false, 'msg' => 'La sala no tiene turnos registrados.');
    }

    // Matices seleccionados (del catálogo).
    $matices = array();
    foreach ((array) $matices_ids as $mid) {
        $q = $db->simple_select('ope_matices_combate', '*', "id = " . (int) $mid, array('limit' => 1));
        $m = $db->fetch_array($q);
        if ($m) {
            $m['efecto'] = json_decode((string) $m['efecto'], true) ?: array();
            $matices[] = $m;
        }
    }

    // Convertir turnos → estructura del motor (acciones con valores del personaje).
    $turnos_motor = array();
    foreach ($turnos as $t) {
        $pj = ope7_pj_get((int) $t['personaje_id']);
        $v = $pj ? array(
            'fue' => (int) $pj['fue'], 'des' => (int) $pj['des'], 'agi' => (int) $pj['agi'],
            'res' => (int) $pj['res'], 'per' => (int) $pj['per'], 'inte' => (int) $pj['inte'],
            'car' => (int) $pj['car'], 'vol' => (int) $pj['vol'],
        ) : array();
        $acciones = array();
        foreach ((array) ($t['acciones_arr']['tecnicas'] ?? array()) as $tk) {
            $acciones[] = array(
                'tipo' => 'tecnica', 'tier' => (int) ($tk['tier'] ?? 1),
                'daño' => (int) round(ope7_combate_dano('cuerpo_a_cuerpo', (int) ($v['fue'] ?? 0), (int) ($v['des'] ?? 0), (int) ($pj['nivel'] ?? 1)) * (array(1 => 1.5, 2 => 2, 3 => 3, 4 => 4.5, 5 => 6)[(int) ($tk['tier'] ?? 1)] ?? 1.5)),
                'usa_agi' => true, 'objetivo_id' => (int) ($tk['objetivo'] ?? 0),
                'valores' => $v,
                'matices' => $matices,
                'defensor_valores' => $v, // se ajusta con el objetivo real en la vista
            );
        }
        foreach ((array) ($t['acciones_arr']['consumibles'] ?? array()) as $ck) {
            $acciones[] = array('tipo' => 'consumible', 'nombre' => (string) ($ck['nombre'] ?? ''), 'pa' => (int) ($ck['pa'] ?? 0));
        }
        $turnos_motor[] = array(
            'personaje_id' => (int) $t['personaje_id'], 'turno' => (int) $t['turno'],
            'pa_total' => (int) $t['pa_total'], 'pa_gastado' => (int) $t['pa_gastado'],
            'acciones' => $acciones,
        );
    }

    $res = ope7_combate_resolver_tema($turnos_motor);

    // Persistir (regeneración = borrar y volcar).
    $db->delete_query('ope_resoluciones_combate', "combate_id = {$sala_id}");
    foreach ($res['intercambios'] as $i) {
        $r = $i['resolucion'];
        $db->insert_query('ope_resoluciones_combate', array(
            'combate_id'          => $sala_id,
            'turno'               => (int) ($i['turno'] ?? 0),
            'atacante_id'         => (int) ($i['atacante_id'] ?? 0),
            'defensor_id'         => (int) ($i['defensor_id'] ?? 0),
            'tabla'               => (int) ($r['tabla'] ?? 1),
            'delta'               => (int) ($r['delta1'] ?? $r['delta'] ?? 0),
            'banda'               => (string) ($r['banda'] ?? ''),
            'resultado'           => (string) ($r['resultado'] ?? ''),
            'veredicto_narrativo' => (string) ($r['veredicto'] ?? ''),
            'matices'             => json_encode($r['matices'] ?? $matices, JSON_UNESCAPED_UNICODE),
        ));
    }
    return array('ok' => true, 'msg' => count($res['intercambios']) . ' intercambios resueltos' . (count($res['excesos']) ? ' · ' . count($res['excesos']) . ' exceso(s) de PA' : ''), 'intercambios' => count($res['intercambios']), 'excesos' => count($res['excesos']));
}

/** Cierra y firma la resolución de una sala (veredicto firmado, histórico). */
function ope7_resolucion_firmar($sala_id, $staff_uid, $motivo)
{
    global $db;
    $sala_id = (int) $sala_id;
    $staff_uid = (int) $staff_uid;
    $motivo = trim((string) $motivo);
    if ($sala_id < 1) {
        return array('ok' => false, 'msg' => 'Sala no válida.');
    }
    if ($motivo === '') {
        return array('ok' => false, 'msg' => 'La firma requiere un motivo.');
    }
    $db->update_query('ope_sala_combate', array(
        'estado'          => 'cerrada',
        'resuelto_por'    => $staff_uid,
        'resuelto_fecha'  => TIME_NOW,
        'nota_resolucion' => $db->escape_string($motivo),
    ), "id = {$sala_id}");
    return array('ok' => true, 'msg' => 'Veredicto firmado y sala cerrada.');
}

/** Histórico de bandas (salud del foro): recuento por banda de delta. */
function ope7_resolucion_historico_bandas()
{
    global $db;
    if (!ope7_tabla_existe('resoluciones_combate')) {
        return array();
    }
    $q = $db->query("SELECT banda, COUNT(*) AS n FROM " . ope7_tabla_full('resoluciones_combate') . " GROUP BY banda ORDER BY n DESC");
    $out = array();
    while ($r = $db->fetch_array($q)) {
        $out[] = $r;
    }
    return $out;
}

/** Render del panel de resolución (vista staff). */
function ope7_resolucion_html($detalle_id = 0, $flash = '')
{
    global $mybb, $db;
    $detalle_id = (int) $detalle_id;
    $html = '';

    if ($flash !== '') {
        $html .= $flash . "\n";
    }

    if ($detalle_id > 0) {
        $q = $db->simple_select('ope_sala_combate', '*', "id = {$detalle_id}", array('limit' => 1));
        $sala = $db->fetch_array($q);
        if (!$sala) {
            $html .= '<p class="tram-empty">Sala no encontrada.</p>';
            return $html;
        }
        $turnos = ope7_resolucion_turnos($detalle_id);
        $html .= '<div class="shead"><h1>' . htmlspecialchars_uni($sala['nombre']) . '</h1>'
            . '<span class="code">RESOLUCIÓN · TEMA ' . (int) $sala['tema_id'] . '</span>'
            . '<span class="rule"></span>'
            . '<a class="btn btn-ghost" href="resolucion-combate.php">← Salas</a></div>';
        $html .= '<div class="plate"><div class="plate-h">Sala · ' . htmlspecialchars_uni($sala['tipo']) . ' · ' . htmlspecialchars_uni($sala['estado'])
            . ($sala['resuelto_por'] > 0 ? ' · firmada por #' . (int) $sala['resuelto_por'] . ' (' . date('d/m/Y H:i', (int) $sala['resuelto_fecha']) . ')' : '') . '</div>';
        $html .= '<div class="plate-b"><div class="tram-note">' . htmlspecialchars_uni((string) ($sala['nota_resolucion'] ?? '')) . '</div></div></div>';

        // Turnos
        $html .= '<div class="plate"><div class="plate-h">Turnos (' . count($turnos) . ')</div><div class="plate-b">';
        if (!$turnos) {
            $html .= '<p class="tram-empty">Sin turnos registrados.</p>';
        } else {
            foreach ($turnos as $t) {
                $avisos = (array) ($t['veredicto_arr']['avisos'] ?? array());
                $exceso = (int) $t['pa_gastado'] > (int) $t['pa_total'];
                $html .= '<div class="res-turno' . ($exceso ? ' res-turno--exceso' : '') . '">'
                    . '<div class="res-turno-h">Turno ' . (int) $t['turno'] . ' · PJ #' . (int) $t['personaje_id']
                    . ' · PA ' . (int) $t['pa_gastado'] . '/' . (int) $t['pa_total'] . ' · reserva ' . (int) $t['reserva'] . '</div>';
                $det = '';
                foreach ((array) ($t['acciones_arr']['tecnicas'] ?? array()) as $tk) {
                    $det .= '<span class="res-chip res-chip--tec">' . htmlspecialchars((string) ($tk['nombre'] ?? '')) . ' (T' . (int) ($tk['tier'] ?? 1) . ' · ' . (int) ($tk['pa'] ?? 0) . ' PA)</span>';
                }
                foreach ((array) ($t['acciones_arr']['consumibles'] ?? array()) as $ck) {
                    $det .= '<span class="res-chip">' . htmlspecialchars((string) ($ck['nombre'] ?? '')) . ' (' . (int) ($ck['pa'] ?? 0) . ' PA)</span>';
                }
                foreach ((array) ($t['acciones_arr']['estados'] ?? array()) as $ek) {
                    $det .= '<span class="res-chip res-chip--est">' . htmlspecialchars((string) $ek) . '</span>';
                }
                if ($det !== '') {
                    $html .= '<div class="res-chips">' . $det . '</div>';
                }
                if ($avisos) {
                    $html .= '<div class="res-avisos">';
                    foreach ($avisos as $a) {
                        $html .= '<div>⚠ ' . htmlspecialchars($a) . '</div>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
        }
        $html .= '</div></div>';

        // Resolución con matices
        $html .= '<div class="plate"><div class="plate-h">Resolución al cierre (tablas de delta)</div><div class="plate-b">';
        $html .= '<form method="post" action="resolucion-combate.php?accion=resolver">'
            . '<input type="hidden" name="sala_id" value="' . (int) $detalle_id . '">'
            . '<p class="tram-note">Ajusta los matices (afinan los valores efectivos antes del delta) y genera los veredictos. El trámite 2 (cierre de temas) los referencia.</p>'
            . '<div class="res-matices">';
        foreach (ope7_resolucion_matices() as $m) {
            $html .= '<label class="res-matiz"><input type="checkbox" name="matices[]" value="' . (int) $m['id'] . '"> '
                . htmlspecialchars((string) $m['nombre']) . '</label>';
        }
        $html .= '</div><div class="tram-actions">'
            . '<button type="submit" class="btn btn-hot">Generar veredictos</button></div></form>';

        // Veredictos generados
        $q = $db->simple_select('ope_resoluciones_combate', '*', "combate_id = {$detalle_id}", array('order_by' => 'id', 'order_dir' => 'ASC'));
        $veredictos = array();
        while ($r = $db->fetch_array($q)) {
            $veredictos[] = $r;
        }
        if ($veredictos) {
            $html .= '<div class="res-veredictos"><h4>Veredictos generados (' . count($veredictos) . ')</h4>';
            foreach ($veredictos as $v) {
                $html .= '<div class="res-ver"><b>#' . (int) $v['atacante_id'] . ' → #' . (int) $v['defensor_id'] . '</b> · T' . (int) $v['tabla']
                    . ' · Δ' . (int) $v['delta'] . ' · ' . htmlspecialchars($v['banda']) . ' · <span class="res-resultado">' . htmlspecialchars($v['resultado']) . '</span>'
                    . '<div class="res-ver-txt">' . htmlspecialchars($v['veredicto_narrativo']) . '</div></div>';
            }
            $html .= '</div>';
            $html .= '<form method="post" action="resolucion-combate.php?accion=firmar" class="tram-actions">'
                . '<input type="hidden" name="sala_id" value="' . (int) $detalle_id . '">'
                . '<input type="text" name="motivo" class="tram-ciclo-in" placeholder="Motivo del veredicto (firma)" required maxlength="255">'
                . '<button type="submit" class="btn btn-hot">Firmar y cerrar sala</button></form>';
        } else {
            $html .= '<p class="tram-empty">Aún no hay veredictos: genera la resolución con los matices de arriba.</p>';
        }
        $html .= '</div></div>';
        return $html;
    }

    // Lista de salas
    $html .= '<div class="shead"><h1>Resolución de combates</h1><span class="code">F2 · 5.10</span><span class="rule"></span></div>';
    $salas = ope7_resolucion_lista();
    if (!$salas) {
        $html .= '<div class="plate"><div class="plate-b"><p class="tram-empty">No hay salas de combate todavía. Se crean automáticamente al postear con la Zona B.</p></div></div>';
    } else {
        foreach ($salas as $s) {
            $html .= '<div class="plate"><div class="plate-h"><a href="resolucion-combate.php?sala=' . (int) $s['id'] . '">' . htmlspecialchars_uni($s['nombre']) . '</a>'
                . '<span class="code">TEMA ' . (int) $s['tema_id'] . '</span><span class="rule"></span>'
                . '<span class="res-estado res-estado--' . htmlspecialchars_uni($s['estado']) . '">' . htmlspecialchars_uni($s['estado']) . '</span></div>';
            $html .= '<div class="plate-b"><span class="tram-note">' . htmlspecialchars_uni($s['tipo']) . ' · ' . (int) $s['n_turnos'] . ' turnos · máx ' . (int) $s['max_combatientes'] . '</span></div></div>';
        }
    }

    // Histórico de bandas
    $bandas = ope7_resolucion_historico_bandas();
    if ($bandas) {
        $html .= '<div class="plate"><div class="plate-h">Histórico de bandas (salud del foro)</div><div class="plate-b"><div class="res-chips">';
        foreach ($bandas as $b) {
            $html .= '<span class="res-chip">' . htmlspecialchars($b['banda']) . ' × ' . (int) $b['n'] . '</span>';
        }
        $html .= '</div></div></div>';
    }
    return $html;
}

/**
 * Hooks datahandler_post_insert_*_end: persiste la Zona B en turnos_combate +
 * sala_combate con los avisos del turno. En MyBB el hook recibe el
 * PostDataHandler (objeto); los tests llaman con un array. Se normaliza y el
 * personaje se resuelve por el uid del autor (personaje activo).
 */
function ope7_zonab_on_post($dh)
{
    global $db;
    if (!ope7_tabla_existe('turnos_combate') || !ope7_tabla_existe('sala_combate')) {
        return;
    }
    if (is_object($dh)) {
        $post = array(
            'message' => (string) ($dh->data['message'] ?? ''),
            'tid'     => (int) ($dh->data['tid'] ?? ($dh->post_insert_data['tid'] ?? 0)),
            'uid'     => (int) ($dh->data['uid'] ?? 0),
        );
    } else {
        $post = (array) $dh;
    }
    $message = (string) ($post['message'] ?? '');
    if (stripos($message, '[ope7-zonab]') === false) {
        return;
    }
    if (!preg_match('#\[ope7-zonab\]([\s\S]*?)\[/ope7-zonab\]#i', $message, $m)) {
        return;
    }
    $payload = json_decode(trim($m[1]), true);
    if (!is_array($payload)) {
        return;
    }
    $uid = (int) ($post['uid'] ?? 0);
    $activo = $uid > 0 ? ope7_pj_activo($uid) : null;
    $pid = (int) ($post['ope_pid'] ?? ($activo['id'] ?? 0));
    $tid = (int) ($post['tid'] ?? 0);
    if ($pid < 1 || $tid < 1) {
        return;
    }

    // Sala get-or-create por tema.
    $q = $db->simple_select('ope_sala_combate', 'id, estado', "tema_id = {$tid}", array('limit' => 1));
    $sala_id = 0;
    $sala_estado = 'abierta';
    if ($db->num_rows($q)) {
        $srow = $db->fetch_array($q);
        $sala_id = (int) $srow['id'];
        $sala_estado = (string) $srow['estado'];
    } else {
        $sala_id = (int) $db->insert_query('ope_sala_combate', array(
            'tema_id' => $tid, 'nombre' => 'Combate del tema ' . $tid, 'tipo' => 'duelo',
            'estado' => 'abierta', 'max_combatientes' => ope7_combate_sala_tope(), 'creado_por' => $uid,
        ));
    }

    // Avisos del turno.
    $avisos = array();

    // Tope de sala (5): la sexta entrada de un personaje NUEVO en sala abierta es aviso (11.8).
    if ($sala_estado === 'abierta') {
        $q = $db->simple_select('ope_turnos_combate', 'DISTINCT personaje_id', "combate_id = {$sala_id}");
        $participantes = array();
        while ($row = $db->fetch_array($q)) {
            $participantes[(int) $row['personaje_id']] = true;
        }
        if (!isset($participantes[$pid]) && count($participantes) >= ope7_combate_sala_tope()) {
            $avisos[] = 'Sala completa (' . ope7_combate_sala_tope() . '): esta entrada se registra como aviso — el staff decide (sala paralela o invasión).';
        }
    }

    // Primer post de combate: sin acciones bélicas (P9).
    $n_posts = 0;
    $q = $db->simple_select('ope_turnos_combate', 'COUNT(*) AS c', "combate_id = {$sala_id}");
    $n_posts = (int) $db->fetch_field($q, 'c');
    $tiene_acciones = !empty($payload['tecnicas']) || !empty($payload['consumibles']);
    if (ope7_combate_es_primer_post($n_posts) && $tiene_acciones) {
        $avisos[] = 'Primer post de combate: no lleva acciones bélicas (P9) — aviso para el staff.';
    }

    // Presupuesto de PA (motor): pa_total con modificadores (1vN +3) y gastado.
    $f = ope7_pj_get($pid);
    $mods = (array) ($payload['mods'] ?? array());
    $pa_total = ope7_combate_pa_turno(
        (int) ($f['agi'] ?? 0),
        (int) ($f['nivel'] ?? 1),
        array('solitario_contra' => !empty($mods['solitario']) ? 2 : 0)
    );
    $pa_gastado = (int) ($payload['resumen']['pa_gastado'] ?? 0);
    if ($pa_gastado > $pa_total) {
        $avisos[] = 'Presupuesto excedido: ' . $pa_gastado . ' PA de ' . $pa_total . ' (se corrige al cierre).';
    }

    // P10: máximo un ataque por objetivo (cuenta técnicas repetidas al mismo objetivo).
    $objetivos = array();
    foreach ((array) ($payload['tecnicas'] ?? array()) as $t) {
        $k = (string) ($t['objetivo'] ?? '');
        $objetivos[$k] = ($objetivos[$k] ?? 0) + 1;
    }
    foreach ($objetivos as $k => $n) {
        if ($k !== '' && $n > ope7_combate_max_ataques_mismo_objetivo()) {
            $avisos[] = 'P10: más de un ataque al mismo objetivo en el turno (aviso).';
        }
    }

    $turno = $n_posts + 1;
    $db->insert_query('ope_turnos_combate', array(
        'combate_id'   => $sala_id,
        'personaje_id' => $pid,
        'turno'        => $turno,
        'pa_total'     => $pa_total,
        'pa_gastado'   => $pa_gastado,
        'acciones'     => json_encode(array(
            'tecnicas' => $payload['tecnicas'] ?? array(),
            'consumibles' => $payload['consumibles'] ?? array(),
            'estados'  => $payload['estados'] ?? array(),
            'mods'     => $mods,
        ), JSON_UNESCAPED_UNICODE),
        'reserva'      => max(0, $pa_total - $pa_gastado),
        'veredicto'    => json_encode(array('avisos' => $avisos), JSON_UNESCAPED_UNICODE),
    ));
}
