<?php
/**
 * I-Forge · Crear NPCs Secundarios (Administrador+)
 * -------------------------------------------------
 * Crea fichas simplificadas de NPCs secundarios (no-jugadores de apoyo, sin
 * ficha completa). Cada entrada tiene nombre, descripcion, imagen y una lista
 * de tecnicas representativas con dados y descripcion propias.
 *
 * Incluye:
 *   - Formulario con vista previa en vivo de la carta apaisada.
 *   - Editor de tecnicas por filas (nombre + dados + efecto).
 *   - Biblioteca con busqueda avanzada (nombre + tecnica).
 *
 * Requiere la tabla mybb_rol_npcs_secundarios (scripts/migrate-npc-secundarios.php).
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'crear-npc-secundario.php');
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

function ons_clean($s, $max = 6000)
{
    $s = trim((string) $s);
    return function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8') : substr($s, 0, $max);
}

$flash       = '';
$flash_kind  = 'ok';
$table_ok    = $db->table_exists('rol_npcs_secundarios');
$buscar      = trim((string) $mybb->get_input('q'));
$tec_buscar  = trim((string) $mybb->get_input('qt'));

// ── POST ──
if ($mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $table_ok) {

    $action = $mybb->get_input('action');

    if ($action === 'save_npc') {
        $npc_id      = (int) $mybb->get_input('npc_id', MyBB::INPUT_INT);
        $nombre      = ons_clean($mybb->get_input('nombre'), 160);
        $imagen      = ons_clean($mybb->get_input('imagen'), 500);
        $descripcion = ons_clean($mybb->get_input('descripcion'), 4000);

        $tn = $mybb->get_input('tn', MyBB::INPUT_ARRAY);
        $td = $mybb->get_input('td', MyBB::INPUT_ARRAY);
        $te = $mybb->get_input('te', MyBB::INPUT_ARRAY);
        $tecnicas = array();
        if (is_array($tn)) {
            foreach ($tn as $i => $n) {
                $n = trim((string) $n);
                if ($n === '') continue;
                $tecnicas[] = array(
                    'n' => ons_clean($n, 160),
                    'd' => ons_clean((string) ($td[$i] ?? ''), 60),
                    'e' => ons_clean((string) ($te[$i] ?? ''), 300),
                );
            }
        }

        if ($nombre === '') {
            $flash = 'El NPC necesita un nombre.';
            $flash_kind = 'warn';
        } else {
            $data = array(
                'nombre'      => $db->escape_string($nombre),
                'imagen'      => $db->escape_string($imagen),
                'descripcion' => $db->escape_string($descripcion),
                'tecnicas'    => $db->escape_string(json_encode($tecnicas, JSON_UNESCAPED_UNICODE)),
                'lastedit'    => TIME_NOW,
            );
            if ($npc_id > 0
                && $db->num_rows($db->simple_select('rol_npcs_secundarios', 'id', "id = {$npc_id}", array('limit' => 1)))) {
                $db->update_query('rol_npcs_secundarios', $data, "id = {$npc_id}");
                $anchor = 'npc-' . $npc_id;
            } else {
                $data['creador_uid'] = $uid;
                $data['dateline']    = TIME_NOW;
                $npc_id = $db->insert_query('rol_npcs_secundarios', $data);
                $anchor = 'biblioteca';
            }
            header('Location: ' . $bburl . '/crear-npc-secundario.php?ok=1#' . $anchor);
            exit;
        }

    } elseif ($action === 'delete_npc') {
        $npc_id = (int) $mybb->get_input('npc_id', MyBB::INPUT_INT);
        if ($npc_id > 0) {
            $db->delete_query('rol_npcs_secundarios', "id = {$npc_id}");
            if ($db->table_exists('rol_acompanantes')) {
                $db->delete_query('rol_acompanantes', "npc_id = {$npc_id}");
            }
            if ($db->table_exists('rol_acompanante_solicitudes')) {
                $db->delete_query('rol_acompanante_solicitudes', "npc_id = {$npc_id} AND estado = 'pendiente'");
            }
        }
        header('Location: ' . $bburl . '/crear-npc-secundario.php?ok=del#biblioteca');
        exit;
    }
}

$okp = $mybb->get_input('ok');
if ($okp === '1')       { $flash = 'NPC secundario guardado en la biblioteca.'; $flash_kind = 'ok'; }
elseif ($okp === 'del') { $flash = 'NPC secundario eliminado de la biblioteca.'; $flash_kind = 'ok'; }

// ── NPC en edicion ──
$edit_id = (int) $mybb->get_input('edit', MyBB::INPUT_INT);
$edit = null;
if ($edit_id > 0 && $table_ok) {
    $eq = $db->simple_select('rol_npcs_secundarios', '*', "id = {$edit_id}", array('limit' => 1));
    if ($db->num_rows($eq)) {
        $edit = $db->fetch_array($eq);
        $edt = json_decode((string) $edit['tecnicas'], true);
        $edit['tecnicas'] = is_array($edt) ? $edt : array();
    }
}

// ── Biblioteca ──
$lib = $table_ok ? ope_rol_npc_sec_lib($buscar, $tec_buscar) : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; NPCs Secundarios</title>
<?php echo ope_rol_head_base(); ?>
<?php echo ope_rol_npc_sec_card_css(); ?>
<?php echo ope_rol_npc_sec_forge_css(); ?>
</head>
<body class="ope-pg-zona-staff ope-pg-crear-personaje ope-pg-gestionar-personaje ope-pg-crear-npc-sec">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>NPCs Secundarios</b>
  </div>
</div>

<div class="wrap" id="top">

  <section class="reveal">
    <div class="shead">
      <h1>NPCs Secundarios</h1>
      <span class="code">// fichas simplificadas &middot; STF-14</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if (!$table_ok): ?>
  <section class="reveal">
    <div class="gc-warn">Falta la tabla <b>mybb_rol_npcs_secundarios</b>. Ejecutala una vez con:<br>
      <code class="c-ember">php scripts/migrate-npc-secundarios.php</code></div>
  </section>
<?php endif; ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="zs-flash" style="<?php echo $flash_kind === 'warn' ? 'background:var(--crack);color:#fff' : ''; ?>"><?php echo $flash; ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <p class="zs-intro">Aqui <b>creas NPCs secundarios</b> para la biblioteca: fichas simplificadas con imagen, nombre, descripcion y tecnicas con sus propios dados y descripcion. Cada tecnica puede detallar su formula de dados y un breve efecto narrativo.</p>
  </section>

  <div class="ons-layout">
    <!-- Formulario -->
    <div>
      <form method="post" action="<?php echo $bburl; ?>/crear-npc-secundario.php" class="gp-form mb-0" id="onsForm">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="save_npc">
        <input type="hidden" name="npc_id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">

        <div class="gp-section" id="editor">
          <div class="gp-section-h"><?php echo $edit ? 'Editar NPC: ' . htmlspecialchars_uni($edit['nombre']) : 'Crear NPC secundario'; ?> <span class="gp-hint">// nombre + imagen + descripcion + tecnicas con dados</span></div>

          <div class="gp-grid">
            <label class="gp-field gp-full"><span>Nombre del NPC</span>
              <input type="text" name="nombre" id="fNombre" maxlength="160" required value="<?php echo $edit ? htmlspecialchars_uni($edit['nombre']) : ''; ?>" placeholder="Ej. Marine de la Unidad 7">
            </label>
          </div>

          <div class="gp-grid">
            <label class="gp-field gp-full"><span>Imagen (URL)</span>
              <input type="text" name="imagen" id="fImagen" maxlength="500" value="<?php echo $edit ? htmlspecialchars_uni($edit['imagen']) : ''; ?>" placeholder="https://...">
            </label>
          </div>

          <div class="gp-grid">
            <label class="gp-field gp-full"><span>Descripcion narrativa</span>
              <textarea name="descripcion" id="fDesc" rows="4" maxlength="4000" placeholder="Quien es, que rol cumple, que le motiva..."><?php echo $edit ? htmlspecialchars_uni($edit['descripcion']) : ''; ?></textarea>
            </label>
          </div>

          <div class="gp-grid">
            <div class="gp-field gp-full" id="onsTecnicasField">
              <span>Tecnicas representativas <span class="gp-hint">(nombre, dados, efecto breve)</span></span>
              <div class="ons-tec-inline" id="onsTecnicasTags"></div>
              <div class="ons-tec-add" id="onsTecAddRow">
                <input type="text" id="onsTin" class="ons-tec-rname" maxlength="160" placeholder="Nombre de la tecnica...">
                <input type="text" id="onsTid" class="ons-tec-rdice" maxlength="60" placeholder="Dados: 2d8+FUE">
                <input type="text" id="onsTie" class="ons-tec-refect" maxlength="300" placeholder="Efecto breve...">
                <button type="button" id="onsTecnicaAdd" title="Añadir tecnica">+ Añadir</button>
              </div>
              <p class="gp-hint">Hasta 20 tecnicas. El campo de nombre es obligatorio para cada una; dados y efecto son opcionales.</p>
            </div>
          </div>

          <div class="gp-submit df gap-10 fww">
            <button type="submit" class="btn btn-hot"><?php echo $edit ? 'Guardar cambios' : 'Crear NPC secundario'; ?></button>
<?php if ($edit): ?>
            <a href="<?php echo $bburl; ?>/crear-npc-secundario.php#editor" class="btn btn-ghost">Cancelar edicion</a>
<?php endif; ?>
          </div>
        </div>
      </form>
    </div>

    <!-- Vista previa -->
    <div class="ons-preview-col">
      <p class="ons-preview-lbl">// vista previa en vivo</p>
      <div class="ons-deck ons-deck-single"><div id="onsPreview"></div></div>
    </div>
  </div>

  <!-- Biblioteca -->
  <section class="zs-group reveal mt-26" id="biblioteca">
    <div class="zs-group-h">
      <span class="lbl">Biblioteca de NPCs secundarios</span>
      <span class="need bg-patina"><?php echo count($lib); ?> NPC(s)</span>
      <span class="rule"></span>
    </div>
    <form method="get" action="<?php echo $bburl; ?>/crear-npc-secundario.php" class="ons-lib-search">
      <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar por nombre de NPC...">
      <input type="text" name="qt" value="<?php echo htmlspecialchars_uni($tec_buscar); ?>" placeholder="Buscar por tecnica...">
      <button type="submit" class="btn btn-hot btn-sm">Filtrar</button>
      <?php if ($buscar !== '' || $tec_buscar !== ''): ?>
      <a href="<?php echo $bburl; ?>/crear-npc-secundario.php#biblioteca" class="btn btn-ghost btn-sm">Limpiar</a>
      <?php endif; ?>
    </form>
<?php if (empty($lib)): ?>
    <div class="empty-state"><div class="big"><?php echo ($buscar !== '' || $tec_buscar !== '') ? 'Sin resultados' : 'Biblioteca vacia'; ?></div><p><?php echo ($buscar !== '' || $tec_buscar !== '') ? 'Ningun NPC coincide con los filtros.' : 'Crea tu primer NPC secundario con el formulario de arriba.'; ?></p></div>
<?php else: ?>
    <div class="ons-deck">
<?php foreach ($lib as $npc): ?>
      <div class="ons-deck-item" id="npc-<?php echo (int)$npc['id']; ?>">
        <?php echo ope_rol_npc_sec_card_html($npc); ?>
        <div class="ons-deck-tools">
          <a href="<?php echo $bburl; ?>/crear-npc-secundario.php?edit=<?php echo (int)$npc['id']; ?>#editor" class="btn btn-ghost btn-sm">Editar</a>
          <form method="post" action="<?php echo $bburl; ?>/crear-npc-secundario.php" onsubmit="return confirm('Eliminar este NPC secundario de la biblioteca?');">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="delete_npc">
            <input type="hidden" name="npc_id" value="<?php echo (int)$npc['id']; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
          </form>
        </div>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var tecnicas = <?php
    // Serializar para JS: array de objetos {n,d,e}
    $js = array();
    if ($edit && is_array($edit['tecnicas'])) {
        foreach ($edit['tecnicas'] as $t) {
            if (is_string($t)) {
                $js[] = array('n' => $t, 'd' => '', 'e' => '');
            } elseif (is_array($t)) {
                $js[] = array(
                    'n' => (string) ($t['n'] ?? $t['nombre'] ?? ''),
                    'd' => (string) ($t['d'] ?? $t['dados'] ?? ''),
                    'e' => (string) ($t['e'] ?? $t['descripcion'] ?? '')
                );
            }
        }
    }
    echo json_encode($js, JSON_UNESCAPED_UNICODE);
  ?>;
  var form = document.getElementById('onsForm');
  var MAX_TEC = 20;

  function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;'}[c];}); }

  function serializeTecs(){
    var rows = document.getElementById('onsTecnicasTags').querySelectorAll('.ons-tec-row');
    var out = [];
    rows.forEach(function(r){
      var n = (r.querySelector('.ons-tec-rname')?.value || '').trim();
      if (n === '') return;
      out.push({
        n: n,
        d: (r.querySelector('.ons-tec-rdice')?.value || '').trim(),
        e: (r.querySelector('.ons-tec-refect')?.value || '').trim()
      });
    });
    tecnicas = out;
  }

  function renderTecnicasTags(){
    var box = document.getElementById('onsTecnicasTags');
    var html = '';
    tecnicas.forEach(function(t,i){
      html += '<div class="ons-tec-row">';
      html += '<input type="text" class="ons-tec-rname" value="'+esc(t.n)+'" placeholder="Nombre tecnica" data-idx="'+i+'" data-field="n">';
      html += '<input type="text" class="ons-tec-rdice" value="'+esc(t.d)+'" placeholder="Dados: 2d8+FUE" data-idx="'+i+'" data-field="d">';
      html += '<input type="text" class="ons-tec-refect" value="'+esc(t.e)+'" placeholder="Efecto breve..." data-idx="'+i+'" data-field="e">';
      html += '<button type="button" data-remove="'+i+'" title="Quitar">&times;</button>';
      html += '</div>';
    });
    box.innerHTML = html;
    renderPreview();
  }

  // Delegacion de eventos en el contenedor de tecnicas
  document.getElementById('onsTecnicasTags').addEventListener('input', function(e){
    if (e.target.dataset.idx !== undefined && e.target.dataset.field) {
      var idx = parseInt(e.target.dataset.idx, 10);
      var field = e.target.dataset.field;
      if (idx >= 0 && idx < tecnicas.length) {
        tecnicas[idx][field] = e.target.value;
        renderPreview();
      }
    }
  });

  document.getElementById('onsTecnicasTags').addEventListener('click', function(e){
    var btn = e.target.closest('[data-remove]');
    if (btn) {
      var idx = parseInt(btn.getAttribute('data-remove'), 10);
      if (!isNaN(idx) && idx >= 0 && idx < tecnicas.length) {
        tecnicas.splice(idx, 1);
        renderTecnicasTags();
      }
    }
  });

  function addTecnica(){
    var inpN = document.getElementById('onsTin');
    var inpD = document.getElementById('onsTid');
    var inpE = document.getElementById('onsTie');
    var n = inpN.value.trim();
    var d = inpD.value.trim();
    var e = inpE.value.trim();
    if (n === '') { inpN.focus(); return; }
    if (tecnicas.length >= MAX_TEC) { alert('Maximo '+MAX_TEC+' tecnicas.'); return; }
    tecnicas.push({n:n, d:d, e:e});
    inpN.value = ''; inpD.value = ''; inpE.value = '';
    renderTecnicasTags();
    inpN.focus();
  }

  document.getElementById('onsTecnicaAdd').addEventListener('click', addTecnica);
  document.getElementById('onsTin').addEventListener('keydown', function(e){
    if (e.key === 'Enter') { e.preventDefault(); addTecnica(); }
  });
  document.getElementById('onsTid').addEventListener('keydown', function(e){
    if (e.key === 'Enter') { e.preventDefault(); addTecnica(); }
  });
  document.getElementById('onsTie').addEventListener('keydown', function(e){
    if (e.key === 'Enter') { e.preventDefault(); addTecnica(); }
  });

  function renderPreview(){
    var box = document.getElementById('onsPreview'); if (!box) return;
    var nombre = (document.getElementById('fNombre').value || 'Sin nombre');
    var img = document.getElementById('fImagen').value.trim();
    var desc = document.getElementById('fDesc').value.trim();
    var html = '<article class="ons-card">';
    html += '<div class="ons-img-box">';
    if (img) {
      html += '<div class="ons-img"><img src="'+esc(img)+'" alt="'+esc(nombre)+'" onerror="this.style.display=\'none\';this.parentElement.classList.add(\'on-empty\');"></div>';
    } else {
      html += '<div class="ons-img on-empty"></div>';
    }
    html += '</div>';
    html += '<div class="ons-body">';
    html += '<h4 class="ons-name">'+esc(nombre)+'</h4>';
    if (desc) html += '<p class="ons-desc">'+esc(desc).replace(/\n/g,'<br>')+'</p>';
    if (tecnicas.length > 0) {
      html += '<div class="ons-tec"><span class="ons-tec-lbl">Tecnicas</span><div class="ons-tec-list">';
      tecnicas.forEach(function(t){
        html += '<div class="ons-tec-item">';
        html += '<span class="ons-tec-iname">'+esc(t.n)+'</span>';
        if (t.d) html += '<span class="ons-tec-idice">'+esc(t.d)+'</span>';
        if (t.e) html += '<span class="ons-tec-ief">'+esc(t.e)+'</span>';
        html += '</div>';
      });
      html += '</div></div>';
    }
    html += '</div></article>';
    box.innerHTML = html;
  }

  form.addEventListener('input', function(e){
    if (e.target.closest('#onsTecnicasTags')) return; // handled by delegation
    renderPreview();
  });
  renderTecnicasTags();

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .06 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
})();
</script>
</body>
</html>
