<?php
/**
 * I-Forge · Crear Cartas de Técnica (Administrador+)
 * --------------------------------------------------
 * BIBLIOTECA de cartas: se crean SIN asociarlas a ningún personaje. Después,
 * desde "Asignar cartas" (asignar-cartas.php), se copian al deck del personaje
 * que corresponda.
 *
 * Incluye:
 *   - Formulario con las 6 categorías de tags (INI-03), tier y presupuesto.
 *   - Vista previa en vivo del naipe.
 *   - Modal de ayuda con un prompt Markdown extenso para IA + caja para pegar
 *     el YAML generado y autorrellenar el formulario.
 *   - Biblioteca de cartas creadas con editar / borrar.
 *
 * Requiere la tabla mybb_rol_cartas (scripts/migrate-rol-tecnicas.php).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-cartas.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

$staff = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0);
$rank  = (int) $staff['rank'];
if (!$loggedin || $rank < 3) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

$TAGS     = ope_rol_tecnica_tags();
$TIERS    = ope_rol_tecnica_tiers();
$buscar   = trim((string) $mybb->get_input('q'));
$ftier    = (int) $mybb->get_input('tier_f', MyBB::INPUT_INT);
$flash    = '';
$flash_kind = 'ok';
$table_ok = $db->table_exists('rol_cartas');

function cc_clean($s, $max = 6000)
{
    $s = trim((string) $s);
    return function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8') : substr($s, 0, $max);
}

// ── POST ──
if ($mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $table_ok) {

    $action = $mybb->get_input('action');

    if ($action === 'save_carta') {
        $carta_id = (int) $mybb->get_input('carta_id', MyBB::INPUT_INT);
        $nombre   = cc_clean($mybb->get_input('nombre'), 160);
        $tags_in  = $mybb->get_input('tags', MyBB::INPUT_ARRAY);
        if (!is_array($tags_in)) $tags_in = array();
        $tags     = ope_rol_tecnica_valida_tags($tags_in);

        $tier = (int) $mybb->get_input('tier', MyBB::INPUT_INT);
        if (!isset($TIERS[$tier])) $tier = 1;

        $coste_pa = max(0, min(9, (int) $mybb->get_input('coste_pa', MyBB::INPUT_INT)));
        $coste_en = max(0, min(200, (int) $mybb->get_input('coste_en', MyBB::INPUT_INT)));
        $reposo   = max(0, min(9, (int) $mybb->get_input('reposo', MyBB::INPUT_INT)));
        $req      = cc_clean($mybb->get_input('requisito_stats'), 255);
        $dados    = cc_clean($mybb->get_input('dados'), 60);
        $desc     = cc_clean($mybb->get_input('descripcion'), 4000);

        if ($nombre === '') {
            $flash = 'La carta necesita un nombre.';
            $flash_kind = 'warn';
        } elseif ($tags['tipo'] === '' || $tags['alcance'] === '') {
            $flash = 'Elige al menos un Tipo y un Alcance para la carta.';
            $flash_kind = 'warn';
        } else {
            $data = array(
                'nombre'          => $db->escape_string($nombre),
                'tier'            => $tier,
                'tags'            => $db->escape_string(json_encode($tags, JSON_UNESCAPED_UNICODE)),
                'coste_pa'        => $coste_pa,
                'coste_en'        => $coste_en,
                'reposo'          => $reposo,
                'requisito_stats' => $db->escape_string($req),
                'dados'           => $db->escape_string($dados),
                'descripcion'     => $db->escape_string($desc),
                'lastedit'        => TIME_NOW,
            );
            if ($carta_id > 0
                && $db->num_rows($db->simple_select('rol_cartas', 'id', "id = {$carta_id}", array('limit' => 1)))) {
                $db->update_query('rol_cartas', $data, "id = {$carta_id}");
                $anchor = 'carta-' . $carta_id;
            } else {
                $data['creador_uid'] = $uid;
                $data['dateline']    = TIME_NOW;
                $db->insert_query('rol_cartas', $data);
                $anchor = 'biblioteca';
            }
            header('Location: ' . $bburl . '/crear-cartas.php?ok=1#' . $anchor);
            exit;
        }

    } elseif ($action === 'delete_carta') {
        $carta_id = (int) $mybb->get_input('carta_id', MyBB::INPUT_INT);
        if ($carta_id > 0) {
            $db->delete_query('rol_cartas', "id = {$carta_id}");
        }
        header('Location: ' . $bburl . '/crear-cartas.php?ok=del#biblioteca');
        exit;
    }
}

$okp = $mybb->get_input('ok');
if ($okp === '1')       { $flash = 'Carta guardada en la biblioteca.'; $flash_kind = 'ok'; }
elseif ($okp === 'del') { $flash = 'Carta eliminada de la biblioteca.'; $flash_kind = 'ok'; }

// ── Carta en edición ──
$edit_id = (int) $mybb->get_input('edit', MyBB::INPUT_INT);
$edit = null;
if ($edit_id > 0 && $table_ok) {
    $eq = $db->simple_select('rol_cartas', '*', "id = {$edit_id}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $edit = $db->fetch_array($eq);
        $edt = json_decode((string) $edit['tags'], true);
        $edit['tags'] = is_array($edt) ? $edt : array();
    }
}
$f_tags = $edit ? $edit['tags'] : array();
$f_get_multi  = function ($ck) use ($f_tags) { return isset($f_tags[$ck]) && is_array($f_tags[$ck]) ? $f_tags[$ck] : array(); };
$f_get_single = function ($ck) use ($f_tags) { return isset($f_tags[$ck]) ? (string) $f_tags[$ck] : ''; };

// ── Biblioteca ──
$lib = $table_ok ? ope_rol_cartas_lib($buscar, $ftier) : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Crear cartas</title>
<?php echo ope_rol_head_base(); ?>
<?php echo ope_rol_tecnica_card_css(); ?>
<?php echo ope_rol_tecnica_forge_css(); ?>
</head>
<body class="ope-pg-zona-staff ope-pg-crear-personaje ope-pg-gestionar-personaje ope-pg-gestionar-cartas">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Crear cartas</b>
  </div>
</div>

<div class="wrap" id="top">

  <section class="reveal">
    <div class="shead">
      <h1>Crear cartas</h1>
      <span class="code">// biblioteca de t&eacute;cnicas &middot; INI-03</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$table_ok): ?>
  <section class="reveal">
    <div class="gc-warn">Falta la tabla <b>mybb_rol_cartas</b>. Ejec&uacute;tala una vez con:<br>
      <code class="c-ember">php scripts/migrate-rol-tecnicas.php</code></div>
  </section>
<?php endif; ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="zs-flash" style="<?php echo $flash_kind === 'warn' ? 'background:var(--crack);color:#fff' : ''; ?>"><?php echo $flash; ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <p class="zs-intro">Aqu&iacute; <b>creas cartas</b> para la biblioteca com&uacute;n: no pertenecen a ning&uacute;n personaje todav&iacute;a. Una vez creadas, se asignan al deck de un personaje desde <a href="<?php echo $bburl; ?>/asignar-cartas.php">Asignar cartas</a>.</p>
    <div class="gp-actions">
      <a href="<?php echo $bburl; ?>/asignar-cartas.php" class="btn btn-ghost btn-sm">Ir a asignar cartas</a>
      <button type="button" class="btn btn-hot btn-sm gc-help-btn" id="gcHelpOpen">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/></svg>
        Ayuda IA &middot; crear t&eacute;cnicas / pegar YAML
      </button>
    </div>
  </section>

  <div class="gc-layout">
    <!-- Formulario -->
    <div>
      <form method="post" action="<?php echo $bburl; ?>/crear-cartas.php" class="gp-form mb-0" id="gcForm">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="save_carta">
        <input type="hidden" name="carta_id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">

        <div class="gp-section" id="editor">
          <div class="gp-section-h"><?php echo $edit ? 'Editar carta: ' . htmlspecialchars_uni($edit['nombre']) : 'Forjar carta nueva'; ?> <span class="gp-hint">// 6 categor&iacute;as de tags + presupuesto</span></div>
          <div class="gp-grid">
            <label class="gp-field gp-full"><span>Nombre de la t&eacute;cnica</span>
              <input type="text" name="nombre" id="fNombre" maxlength="160" required value="<?php echo $edit ? htmlspecialchars_uni($edit['nombre']) : ''; ?>" placeholder="Ej. Tajo Directo">
            </label>
          </div>

          <div class="gp-grid">
            <div class="gp-field gp-full">
              <span>Tier (potencia)</span>
              <div class="tier-grid">
<?php foreach ($TIERS as $tn => $t): $sel = $edit ? ((int)$edit['tier'] === $tn) : ($tn === 1); ?>
                <label class="tier-chip">
                  <input type="radio" name="tier" value="<?php echo $tn; ?>" data-pa="<?php echo (int)$t['pa_def']; ?>" data-en="<?php echo (int)$t['en_def']; ?>" data-reposo="<?php echo (int)$t['reposo_def']; ?>" data-dados="<?php echo htmlspecialchars_uni($t['dados_def']); ?>" data-info="Tier <?php echo $t['romano']; ?> &middot; rango <?php echo htmlspecialchars_uni($t['rango']); ?> &middot; <?php echo (int)$t['pp']; ?> PP &middot; PA <?php echo htmlspecialchars_uni($t['pa']); ?> &middot; EN <?php echo htmlspecialchars_uni($t['en']); ?> &middot; reposo <?php echo htmlspecialchars_uni($t['reposo']); ?> &middot; <?php echo htmlspecialchars_uni($t['dados']); ?>"<?php echo $sel ? ' checked' : ''; ?>>
                  <span class="tc"><b><?php echo $t['romano']; ?></b><small><?php echo htmlspecialchars_uni($t['rango']); ?></small></span>
                </label>
<?php endforeach; ?>
              </div>
              <p class="tier-hint" id="tierHint"></p>
            </div>
          </div>

          <div class="gp-grid">
            <div class="gp-field gp-full">
<?php foreach ($TAGS as $ck => $c): ?>
              <div class="tk-catrow" style="--tk:<?php echo $c['accent']; ?>">
                <div class="tk-cathead">
                  <span class="cn"><?php echo htmlspecialchars_uni($c['nombre']); ?></span>
                  <span class="cq"><?php echo htmlspecialchars_uni($c['pregunta']); ?></span>
                  <span class="cmax"><?php echo $c['multi'] ? ($c['max'] > 0 ? 'hasta ' . (int)$c['max'] : 'varios') : 'elige 1'; ?></span>
                </div>
                <div class="tk-chips">
<?php if ($c['multi']):
        $selected = $f_get_multi($ck);
        foreach ($c['tags'] as $tid => $tlbl): $on = in_array($tid, $selected, true); ?>
                  <label class="tk-chip">
                    <input type="checkbox" name="tags[<?php echo $ck; ?>][]" value="<?php echo htmlspecialchars_uni($tid); ?>" data-cat="<?php echo $ck; ?>" data-max="<?php echo (int)$c['max']; ?>"<?php echo $on ? ' checked' : ''; ?>>
                    <span><?php echo htmlspecialchars_uni($tlbl); ?></span>
                  </label>
<?php endforeach; else:
        $selval = $f_get_single($ck);
        if ($selval === '' && $ck === 'elemento') $selval = 'Ninguno';
        foreach ($c['tags'] as $tid => $tlbl): $on = ($selval === $tid); ?>
                  <label class="tk-chip">
                    <input type="radio" name="tags[<?php echo $ck; ?>]" value="<?php echo htmlspecialchars_uni($tid); ?>" data-cat="<?php echo $ck; ?>"<?php echo $on ? ' checked' : ''; ?>>
                    <span><?php echo htmlspecialchars_uni($tlbl); ?></span>
                  </label>
<?php endforeach; endif; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>

          <div class="gp-grid">
            <label class="gp-field"><span>Coste PA</span><input type="number" name="coste_pa" id="fPa" min="0" max="9" value="<?php echo $edit ? (int)$edit['coste_pa'] : 1; ?>"></label>
            <label class="gp-field"><span>Coste EN</span><input type="number" name="coste_en" id="fEn" min="0" max="200" value="<?php echo $edit ? (int)$edit['coste_en'] : 8; ?>"></label>
            <label class="gp-field"><span>Reposo (posts)</span><input type="number" name="reposo" id="fReposo" min="0" max="9" value="<?php echo $edit ? (int)$edit['reposo'] : 0; ?>"></label>
            <label class="gp-field gp-wide"><span>Dados de da&ntilde;o</span><input type="text" name="dados" id="fDados" maxlength="60" value="<?php echo $edit ? htmlspecialchars_uni($edit['dados']) : '1d8 + FUE'; ?>" placeholder="1d8 + FUE"></label>
            <label class="gp-field gp-full"><span>Requisitos (rango m&iacute;nimo / equipo / estilo)</span><input type="text" name="requisito_stats" id="fReq" maxlength="255" value="<?php echo $edit ? htmlspecialchars_uni($edit['requisito_stats']) : ''; ?>" placeholder="Ej. AGI D, FUE E"></label>
            <label class="gp-field gp-full"><span>Descripci&oacute;n narrativa</span><textarea name="descripcion" id="fDesc" rows="4" maxlength="4000" placeholder="Qu&eacute; se ve, c&oacute;mo se ejecuta, qu&eacute; sensaci&oacute;n transmite&hellip;"><?php echo $edit ? htmlspecialchars_uni($edit['descripcion']) : ''; ?></textarea></label>
          </div>

          <div class="gp-submit df gap-10 fww">
            <button type="submit" class="btn btn-hot"><?php echo $edit ? 'Guardar cambios' : 'Crear carta en biblioteca'; ?></button>
<?php if ($edit): ?>
            <a href="<?php echo $bburl; ?>/crear-cartas.php#editor" class="btn btn-ghost">Cancelar edici&oacute;n</a>
<?php endif; ?>
          </div>
        </div>
      </form>
    </div>

    <!-- Vista previa -->
    <div class="gc-preview-col">
      <p class="gc-preview-lbl">// vista previa en vivo</p>
      <div class="ope-tk-deck gc-deck-single"><div id="gcPreview"></div></div>
    </div>
  </div>

  <!-- Biblioteca -->
  <section class="zs-group reveal mt-26" id="biblioteca">
    <div class="zs-group-h">
      <span class="lbl">Biblioteca de cartas</span>
      <span class="need bg-patina"><?php echo count($lib); ?> carta(s)</span>
      <span class="rule"></span>
    </div>
    <form method="get" action="<?php echo $bburl; ?>/crear-cartas.php" class="zs-search">
      <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar carta por nombre&hellip;">
      <select name="tier_f" class="zs-staffsel">
        <option value="0">Todos los tiers</option>
<?php foreach ($TIERS as $tn => $t): ?>
        <option value="<?php echo $tn; ?>"<?php echo $ftier === $tn ? ' selected' : ''; ?>>Tier <?php echo $t['romano']; ?></option>
<?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-hot btn-sm">Filtrar</button>
    </form>
<?php if (empty($lib)): ?>
    <div class="empty-state"><div class="big">Biblioteca vac&iacute;a</div><p>Crea tu primera carta con el formulario de arriba. Luego podr&aacute;s asignarla a los personajes.</p></div>
<?php else: ?>
    <div class="ope-tk-deck">
<?php foreach ($lib as $carta): ?>
      <div class="deck-item" id="carta-<?php echo (int)$carta['id']; ?>">
        <?php echo ope_rol_tecnica_card_html($carta); ?>
        <div class="deck-tools">
          <a href="<?php echo $bburl; ?>/crear-cartas.php?edit=<?php echo (int)$carta['id']; ?>#editor" class="btn btn-ghost btn-sm">Editar</a>
          <a href="<?php echo $bburl; ?>/asignar-cartas.php?carta=<?php echo (int)$carta['id']; ?>" class="btn btn-ghost btn-sm">Asignar</a>
          <form method="post" action="<?php echo $bburl; ?>/crear-cartas.php" onsubmit="return confirm('¿Eliminar esta carta de la biblioteca? (No afecta a los decks ya asignados.)');">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="delete_carta">
            <input type="hidden" name="carta_id" value="<?php echo (int)$carta['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
          </form>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

  <!-- Modal ayuda IA -->
  <div class="gc-modal-ov" id="gcHelp" hidden>
    <div class="gc-modal">
      <div class="gc-modal-h">
        <h2>Crear t&eacute;cnicas con IA</h2>
        <button type="button" class="x" id="gcHelpClose">&times;</button>
      </div>
      <div class="gc-modal-b">
        <div class="gc-tabs">
          <button type="button" class="gc-tab on" data-gctab="prompt">1 &middot; Copiar prompt</button>
          <button type="button" class="gc-tab" data-gctab="paste">2 &middot; Pegar YAML</button>
        </div>

        <div class="gc-tabpane on" data-gcpane="prompt">
          <p class="lead">Copia este prompt y p&eacute;galo en tu IA (ChatGPT, Claude, Gemini&hellip;). Le da TODO el sistema INI-03 para dise&ntilde;ar cartas equilibradas. Luego describe tu concepto y te devolver&aacute; una carta en YAML.</p>
          <textarea class="gc-md" id="gcMd" readonly spellcheck="false"><?php echo htmlspecialchars_uni(ope_rol_tecnica_ia_prompt()); ?></textarea>
          <div class="gc-copybar">
            <button type="button" class="btn btn-hot btn-sm" id="gcCopy">Copiar prompt</button>
            <span class="gc-copied" id="gcCopied">&#10003; Copiado</span>
          </div>
        </div>

        <div class="gc-tabpane" data-gcpane="paste">
          <p class="lead">Pega aqu&iacute; el bloque <b>YAML</b> que te devolvi&oacute; la IA y pulsa <b>Rellenar formulario</b>. Se autocompletan nombre, tier, los 6 grupos de tags, costes, dados, requisitos y descripci&oacute;n. Rev&iacute;salo y guarda.</p>
          <textarea class="gc-md" id="gcYaml" spellcheck="false" placeholder="nombre: &quot;...&quot;&#10;tier: II&#10;tags:&#10;  estilo: [&quot;Propio&quot;]&#10;  ..."></textarea>
          <div class="gc-copybar">
            <button type="button" class="btn btn-hot btn-sm" id="gcFill">Rellenar formulario</button>
            <span class="gc-copied" id="gcFilled">&#10003; Formulario rellenado</span>
            <span class="gc-fillerr" id="gcFillErr"></span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var TAGS_META = <?php echo json_encode(array_map(function($c){ return array('nombre'=>$c['nombre'],'multi'=>$c['multi'],'accent'=>$c['accent']); }, $TAGS), JSON_UNESCAPED_UNICODE); ?>;
  var TIER_ROMAN = {1:'I',2:'II',3:'III',4:'IV',5:'V'};
  var ROMAN_TIER = {'I':1,'II':2,'III':3,'IV':4,'V':5};

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .06 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }

  var form = document.getElementById('gcForm');
  if (!form) return;

  function enforceMax(cat){
    var boxes = form.querySelectorAll('input[type=checkbox][data-cat="'+cat+'"]');
    if (!boxes.length) return;
    var max = parseInt(boxes[0].getAttribute('data-max'),10) || 0;
    if (max <= 0) return;
    var checked = form.querySelectorAll('input[type=checkbox][data-cat="'+cat+'"]:checked');
    boxes.forEach(function(g){ g.disabled = (checked.length >= max && !g.checked); });
  }
  form.querySelectorAll('input[type=checkbox][data-max]').forEach(function(chk){
    chk.addEventListener('change', function(){ enforceMax(chk.getAttribute('data-cat')); renderPreview(); });
  });

  var hint = document.getElementById('tierHint');
  var IS_EDIT = <?php echo $edit ? 'true' : 'false'; ?>;
  function applyTier(radio, forceFill){
    if (hint) hint.innerHTML = radio.getAttribute('data-info') || '';
    if (forceFill){
      document.getElementById('fPa').value = radio.getAttribute('data-pa');
      document.getElementById('fEn').value = radio.getAttribute('data-en');
      document.getElementById('fReposo').value = radio.getAttribute('data-reposo');
      document.getElementById('fDados').value = radio.getAttribute('data-dados');
    }
  }
  form.querySelectorAll('input[name=tier]').forEach(function(r){ r.addEventListener('change', function(){ applyTier(r, !IS_EDIT); renderPreview(); }); });
  var tc = form.querySelector('input[name=tier]:checked'); if (tc) applyTier(tc, false);

  function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }
  function collectTags(){
    var out = [];
    Object.keys(TAGS_META).forEach(function(ck){
      var meta = TAGS_META[ck];
      if (meta.multi){
        form.querySelectorAll('input[type=checkbox][data-cat="'+ck+'"]:checked').forEach(function(c){ out.push({txt:'['+meta.nombre+': '+c.value+']', accent:meta.accent}); });
      } else {
        var r = form.querySelector('input[type=radio][data-cat="'+ck+'"]:checked');
        if (r) out.push({txt:'['+meta.nombre+': '+r.value+']', accent:meta.accent});
      }
    });
    return out;
  }
  function renderPreview(){
    var box = document.getElementById('gcPreview'); if (!box) return;
    var nombre = (document.getElementById('fNombre').value || 'Sin nombre');
    var tierR = form.querySelector('input[name=tier]:checked'); var tier = tierR ? parseInt(tierR.value,10) : 1;
    var pa = document.getElementById('fPa').value || 0, en = document.getElementById('fEn').value || 0,
        rp = document.getElementById('fReposo').value || 0, dd = document.getElementById('fDados').value.trim(),
        req = document.getElementById('fReq').value.trim(), desc = document.getElementById('fDesc').value.trim();
    var tags = collectTags();
    var chips = tags.map(function(t){ return '<span class="ope-tk-chip" style="--tk:'+t.accent+'">'+esc(t.txt)+'</span>'; }).join('');
    var html = '<article class="ope-tk ope-tk-t'+tier+'">';
    html += '<div class="ope-tk-h"><span class="ope-tk-tier">'+TIER_ROMAN[tier]+'</span><div class="ope-tk-tt"><h4 class="ope-tk-name">'+esc(nombre)+'</h4></div></div>';
    if (chips) html += '<div class="ope-tk-chips">'+chips+'</div>';
    if (desc) html += '<p class="ope-tk-desc">'+esc(desc).replace(/\n/g,'<br>')+'</p>';
    html += '<div class="ope-tk-stats"><span class="ope-tk-stat"><b>'+esc(pa)+'</b><small>PA</small></span><span class="ope-tk-stat"><b>'+esc(en)+'</b><small>EN</small></span><span class="ope-tk-stat"><b>'+esc(rp)+'</b><small>Reposo</small></span>';
    if (dd) html += '<span class="ope-tk-stat ope-tk-dice"><b>'+esc(dd)+'</b><small>Dados</small></span>';
    html += '</div>';
    if (req) html += '<div class="ope-tk-req"><span class="ope-tk-req-l">Requisitos</span> '+esc(req)+'</div>';
    html += '</article>';
    box.innerHTML = html;
  }
  form.addEventListener('input', renderPreview);
  form.addEventListener('change', renderPreview);
  Object.keys(TAGS_META).forEach(function(ck){ if (TAGS_META[ck].multi) enforceMax(ck); });
  renderPreview();

  // ---- Modal ----
  var help = document.getElementById('gcHelp');
  document.getElementById('gcHelpOpen').addEventListener('click', function(){ help.hidden = false; });
  document.getElementById('gcHelpClose').addEventListener('click', function(){ help.hidden = true; });
  help.addEventListener('click', function(e){ if (e.target === help) help.hidden = true; });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !help.hidden) help.hidden = true; });

  document.querySelectorAll('.gc-tab').forEach(function(t){
    t.addEventListener('click', function(){
      document.querySelectorAll('.gc-tab').forEach(function(x){ x.classList.remove('on'); });
      document.querySelectorAll('.gc-tabpane').forEach(function(x){ x.classList.remove('on'); });
      t.classList.add('on');
      document.querySelector('.gc-tabpane[data-gcpane="'+t.getAttribute('data-gctab')+'"]').classList.add('on');
    });
  });

  var copyBtn = document.getElementById('gcCopy');
  copyBtn.addEventListener('click', function(){
    var ta = document.getElementById('gcMd');
    var done = function(){ var c = document.getElementById('gcCopied'); c.classList.add('show'); setTimeout(function(){ c.classList.remove('show'); }, 1800); };
    if (navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(ta.value).then(done, function(){ ta.select(); document.execCommand('copy'); done(); }); }
    else { ta.select(); document.execCommand('copy'); done(); }
  });

  // ---- Parser YAML → autorrelleno ----
  function stripQuotes(s){ s = String(s).trim(); if ((s[0]==='"'&&s[s.length-1]==='"')||(s[0]==="'"&&s[s.length-1]==="'")) s = s.slice(1,-1); return s.trim(); }
  function stripComment(s){ // quita comentario " # ..." fuera de comillas
    var inQ=false, q='';
    for (var i=0;i<s.length;i++){ var ch=s[i]; if (inQ){ if(ch===q) inQ=false; } else { if(ch==='"'||ch==="'"){inQ=true;q=ch;} else if(ch==='#'&&(i===0||s[i-1]===' ')) return s.slice(0,i); } }
    return s;
  }
  function parseList(val){
    val = stripComment(val).trim();
    if (val==='') return [];
    if (val[0]==='['){ val = val.replace(/^\[/,'').replace(/\]$/,''); }
    if (val==='') return [];
    return val.split(',').map(function(x){ return stripQuotes(x); }).filter(function(x){ return x!==''; });
  }
  function parseCardYaml(raw){
    raw = raw.replace(/```[a-zA-Z]*/g,'').replace(/```/g,'');
    var lines = raw.split(/\r?\n/);
    var obj = { tags:{} };
    var inTags = false;
    for (var i=0;i<lines.length;i++){
      var line = lines[i]; if (!line.trim()) continue;
      var indent = (line.match(/^\s*/)||[''])[0].length;
      var m = line.match(/^\s*([A-Za-z_]+)\s*:\s*(.*)$/); if (!m) continue;
      var key = m[1].toLowerCase(); var val = m[2];
      if (key==='tags' && stripComment(val).trim()===''){ inTags = true; continue; }
      var subKeys = ['estilo','tipo','alcance','elemento','estado','ejecucion'];
      if (inTags && indent >= 2 && subKeys.indexOf(key)>=0){ obj.tags[key] = val; continue; }
      if (indent === 0) inTags = false;
      obj[key] = val;
    }
    return obj;
  }
  function setMulti(cat, values){
    var boxes = form.querySelectorAll('input[type=checkbox][data-cat="'+cat+'"]');
    var want = values.map(function(v){ return v.toLowerCase(); });
    boxes.forEach(function(b){ b.disabled=false; b.checked = want.indexOf(b.value.toLowerCase())>=0; });
    enforceMax(cat);
  }
  function setSingle(cat, value){
    if (value==null || value==='') return;
    var v = value.toLowerCase();
    form.querySelectorAll('input[type=radio][data-cat="'+cat+'"]').forEach(function(r){ if (r.value.toLowerCase()===v) r.checked = true; });
  }

  document.getElementById('gcFill').addEventListener('click', function(){
    var errBox = document.getElementById('gcFillErr'); errBox.textContent='';
    var raw = document.getElementById('gcYaml').value;
    if (!raw.trim()){ errBox.textContent='Pega primero el YAML.'; return; }
    var o;
    try { o = parseCardYaml(raw); } catch(e){ errBox.textContent='No se pudo interpretar el YAML.'; return; }

    if (o.nombre!=null) document.getElementById('fNombre').value = stripQuotes(stripComment(o.nombre));
    if (o.tier!=null){
      var tv = stripQuotes(stripComment(o.tier)).toUpperCase();
      var tn = ROMAN_TIER[tv] || parseInt(tv,10) || 0;
      if (tn>=1 && tn<=5){ var r = form.querySelector('input[name=tier][value="'+tn+'"]'); if (r){ r.checked=true; applyTier(r,false); } }
    }
    var tags = o.tags||{};
    if (tags.estilo!=null)    setMulti('estilo', parseList(tags.estilo));
    if (tags.estado!=null)    setMulti('estado', parseList(tags.estado));
    if (tags.ejecucion!=null) setMulti('ejecucion', parseList(tags.ejecucion));
    if (tags.tipo!=null)      setSingle('tipo', stripQuotes(stripComment(tags.tipo)));
    if (tags.alcance!=null)   setSingle('alcance', stripQuotes(stripComment(tags.alcance)));
    if (tags.elemento!=null)  setSingle('elemento', stripQuotes(stripComment(tags.elemento)));
    if (o.coste_pa!=null) document.getElementById('fPa').value = parseInt(stripComment(o.coste_pa),10)||0;
    if (o.coste_en!=null) document.getElementById('fEn').value = parseInt(stripComment(o.coste_en),10)||0;
    if (o.reposo!=null)   document.getElementById('fReposo').value = parseInt(stripComment(o.reposo),10)||0;
    if (o.dados!=null)    document.getElementById('fDados').value = stripQuotes(stripComment(o.dados));
    if (o.requisito_stats!=null) document.getElementById('fReq').value = stripQuotes(stripComment(o.requisito_stats));
    if (o.descripcion!=null) document.getElementById('fDesc').value = stripQuotes(stripComment(o.descripcion));

    renderPreview();
    var c = document.getElementById('gcFilled'); c.classList.add('show'); setTimeout(function(){ c.classList.remove('show'); }, 2200);
    help.hidden = true;
    var ed = document.getElementById('editor'); if (ed) window.scrollTo({top: ed.offsetTop - 66, behavior:'smooth'});
  });
})();
</script>
</body>
</html>
