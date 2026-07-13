<?php
/**
 * I-Forge · Oráculo de Viaje
 * Formulario multi-paso: origen, destino, barco, tripulación → OP-Eternal crea el hilo.
 * El viaje se cierra cuando el capitán lo solicita (no automático por posts).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'viajes.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

$active_pid = 0;
$pj_nombre  = '';
if ($loggedin) {
    $active_pid = function_exists('ope_rol_active_pid_for') ? ope_rol_active_pid_for($uid) : (int) ($mybb->user['ope_active_pid'] ?? 0);
    if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
        $pq = $db->simple_select('rol_personajes', 'nombre, mundo_ubic', "pid = {$active_pid} AND uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($pq)) {
            $pr = $db->fetch_array($pq);
            $pj_nombre = (string) $pr['nombre'];
        }
    }
}

$flash = '';
$flash_kind = 'ok';

// POST: cerrar viaje
if ($loggedin && $mybb->request_method === 'post' && $mybb->get_input('action') === 'cerrar') {
    verify_post_check($mybb->get_input('my_post_key'));
    $vid = (int) $mybb->get_input('viaje_id');
    $res = ope_viaje_cerrar($vid, $uid, $active_pid);
    if ($res['ok']) {
        header('Location: ' . $res['url'] . '&viaje=cerrado');
        exit;
    }
    $flash = $res['msg'];
    $flash_kind = 'error';
}

// POST: solicitar viaje
if ($loggedin && $active_pid > 0 && $mybb->request_method === 'post' && $mybb->get_input('action') === 'solicitar') {
    verify_post_check($mybb->get_input('my_post_key'));
    $extra = $mybb->get_input('tripulantes', MyBB::INPUT_ARRAY);
    if (!is_array($extra)) {
        $extra = array();
    }
    $res = ope_viaje_solicitar(array(
        'pid_capitan'   => $active_pid,
        'uid'           => $uid,
        'fid_origen'    => (int) $mybb->get_input('fid_origen'),
        'fid_destino'   => (int) $mybb->get_input('fid_destino'),
        'barco_nombre'  => $mybb->get_input('barco_nombre'),
        'barco_tipo'    => $mybb->get_input('barco_tipo'),
        'tripulantes'   => $extra,
        'suministros'   => $mybb->get_input('suministros'),
        'notas'         => $mybb->get_input('notas'),
    ));
    if ($res['ok']) {
        header('Location: ' . $res['url'] . '&viaje=nuevo');
        exit;
    }
    $flash = $res['msg'];
    $flash_kind = 'error';
}

$islas = function_exists('ope_viaje_islas') ? ope_viaje_islas() : array();
$barcos = function_exists('ope_oraculo_barcos_config') ? ope_oraculo_barcos_config() : array();
$viaje_activo = ($active_pid > 0 && function_exists('ope_viaje_por_capitan_activo')) ? ope_viaje_por_capitan_activo($active_pid) : null;

// Compañeros de tripulación (misma crew)
$crew_pids = array();
if ($active_pid > 0 && function_exists('ope_rol_cat_tripulacion_de_personaje')) {
    $mi = ope_rol_cat_tripulacion_de_personaje($active_pid);
    if ($mi && $db->table_exists('rol_tripulacion_miembros')) {
        $tid_c = (int) $mi['tripulacion']['id'];
        $mq = $db->simple_select('rol_tripulacion_miembros', 'pid', "tripulacion_id = {$tid_c} AND estado = 'activo' AND pid != {$active_pid}");
        while ($mr = $db->fetch_array($mq)) {
            $crew_pids[] = (int) $mr['pid'];
        }
    }
}
$companeros = array();
if ($crew_pids && $db->table_exists('rol_personajes')) {
    $in = implode(',', array_map('intval', $crew_pids));
    $cq = $db->simple_select('rol_personajes', 'pid, nombre', "pid IN ({$in}) AND estado = 'aprobado'", array('order_by' => 'nombre'));
    while ($cr = $db->fetch_array($cq)) {
        $companeros[] = array('pid' => (int) $cr['pid'], 'nombre' => htmlspecialchars_uni($cr['nombre']));
    }
}

// Agrupar islas por región para el selector
$islas_por_region = array();
foreach ($islas as $isla) {
    $islas_por_region[$isla['region']][] = $isla;
}
$islas_json = json_encode($islas, JSON_UNESCAPED_UNICODE);

$hero_image_url = '';
foreach (array('webp', 'avif', 'jpg', 'jpeg', 'png') as $hero_ext) {
    $hero_path = MYBB_ROOT . 'images/ope/oraculo-viaje.' . $hero_ext;
    if (is_file($hero_path)) {
        $hero_image_url = rtrim((string) $mybb->settings['bburl'], '/') . '/images/ope/oraculo-viaje.' . $hero_ext;
        break;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Oráculo de Viaje</title>
<?php echo ope_rol_head_base(); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" defer></script>
</head>
<body class="ope-pg-tramites ope-pg-viajes">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a>
    <span class="sep">›</span>
    <b>Oráculo de Viaje</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal vis">
    <div class="shead">
      <h1>Oráculo de Viaje</h1>
      <span class="code">// navegación · 4 mesas D100</span>
      <span class="rule"></span>
    </div>
    <p class="ope-vj-lead">Traza una travesía entre islas y deja que <strong>OP-Eternal</strong> lea el mar: clima, encuentros, hallazgos y peligros formarán el hilo de <em>Alta Mar</em>. La tripulación decide cuánto dura la historia: <strong>el viaje solo termina cuando solicitas la llegada</strong>.</p>
    <div class="ope-vj-facts" aria-label="Características del viaje">
      <span><i class="ph ph-dice-five" aria-hidden="true"></i><b>4 mesas</b>D100 por tramo</span>
      <span><i class="ph ph-map-trifold" aria-hidden="true"></i><b>1–5 tramos</b>según la ruta</span>
      <span><i class="ph ph-anchor" aria-hidden="true"></i><b>Cierre manual</b>por el capitán</span>
    </div>
  </section>

  <?php if ($flash !== ''): ?>
  <div class="ope-flash ope-flash--<?php echo $flash_kind === 'error' ? 'err' : 'ok'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
  <?php endif; ?>

  <?php if (!$loggedin || $active_pid < 1): ?>
  <div class="ope-plate ope-vj-gate">
    <div class="ope-plate-b">
      <p>Necesitas iniciar sesión y tener un <strong>personaje activo</strong> para solicitar un viaje.</p>
      <a href="<?php echo $bburl; ?>/member.php?action=login" class="ope-btn ope-btn-hot">Entrar</a>
    </div>
  </div>
  <?php elseif ($viaje_activo): ?>
  <div class="ope-plate ope-vj-active">
    <div class="ope-plate-h"><span class="ope-plate-h-t">Viaje en curso</span></div>
    <div class="ope-plate-b">
      <p>Tu personaje <strong><?php echo htmlspecialchars_uni($pj_nombre); ?></strong> ya navega hacia
        <strong><?php echo htmlspecialchars_uni($viaje_activo['destino_nombre']); ?></strong>.</p>
      <a href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int) $viaje_activo['tid']; ?>" class="ope-btn ope-btn-hot">Ir al hilo de travesía</a>
    </div>
  </div>
  <?php else: ?>

  <div class="ope-vj-layout">
    <figure class="ope-vj-aside<?php echo $hero_image_url !== '' ? ' has-image' : ''; ?>">
      <?php if ($hero_image_url !== ''): ?>
      <img src="<?php echo htmlspecialchars_uni($hero_image_url); ?>" alt="Navío adentrándose en los mares del Oráculo de Viaje" width="800" height="1000">
      <?php else: ?>
      <div class="ope-vj-aside-placeholder" aria-hidden="true">
        <i class="ph ph-compass-rose"></i>
        <span>images/ope/oraculo-viaje.webp</span>
      </div>
      <?php endif; ?>
      <figcaption><span>Bitácora de navegación</span>El horizonte nunca promete un mar en calma.</figcaption>
    </figure>

  <form class="ope-vj-wizard" id="ope-vj-form" method="post" action="<?php echo $bburl; ?>/viajes.php">
    <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
    <input type="hidden" name="action" value="solicitar">

    <div class="ope-vj-steps" role="tablist">
      <button type="button" class="ope-vj-step is-on" data-step="1" role="tab" aria-selected="true"><span>01</span>Ruta</button>
      <button type="button" class="ope-vj-step" data-step="2" role="tab" aria-selected="false"><span>02</span>Barco</button>
      <button type="button" class="ope-vj-step" data-step="3" role="tab" aria-selected="false"><span>03</span>Tripulación</button>
      <button type="button" class="ope-vj-step" data-step="4" role="tab" aria-selected="false"><span>04</span>Confirmar</button>
    </div>

    <!-- Paso 1: Ruta -->
    <div class="ope-vj-panel is-on" data-panel="1" role="tabpanel">
      <div class="ope-vj-panel-head">
        <h2>Traza la ruta</h2>
        <p>El origen y el destino determinan la distancia, los tramos y cuántas veces hablará el oráculo.</p>
      </div>
      <div class="ope-vj-route-grid">
        <div class="ope-vj-field">
          <label for="fid_origen">Origen</label>
          <select name="fid_origen" id="fid_origen" required class="ope-vj-sel">
            <option value="">— Isla de salida —</option>
            <?php foreach ($islas_por_region as $reg => $lista): ?>
            <optgroup label="<?php echo htmlspecialchars_uni($reg); ?>">
              <?php foreach ($lista as $isla): ?>
              <option value="<?php echo (int) $isla['fid']; ?>"><?php echo htmlspecialchars_uni($isla['nombre']); ?></option>
              <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="ope-vj-route-arrow" aria-hidden="true">→</div>
        <div class="ope-vj-field">
          <label for="fid_destino">Destino</label>
          <select name="fid_destino" id="fid_destino" required class="ope-vj-sel">
            <option value="">— Isla de llegada —</option>
            <?php foreach ($islas_por_region as $reg => $lista): ?>
            <optgroup label="<?php echo htmlspecialchars_uni($reg); ?>">
              <?php foreach ($lista as $isla): ?>
              <option value="<?php echo (int) $isla['fid']; ?>"><?php echo htmlspecialchars_uni($isla['nombre']); ?></option>
              <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="ope-vj-preview" id="ope-vj-route-preview">
        <span class="ope-vj-preview-k">Tramos estimados</span>
        <span class="ope-vj-preview-v" id="ope-vj-tramos">—</span>
      </div>
      <button type="button" class="ope-btn ope-btn-hot ope-vj-next" data-next="2">Continuar</button>
    </div>

    <!-- Paso 2: Barco -->
    <div class="ope-vj-panel" data-panel="2" role="tabpanel" hidden>
      <div class="ope-vj-panel-head">
        <h2>Prepara el navío</h2>
        <p>El tipo de barco altera las tiradas de clima y peligros. Elige la nave que de verdad llevará la tripulación.</p>
      </div>
      <div class="ope-vj-field">
        <label for="barco_nombre">Nombre del barco</label>
        <input type="text" name="barco_nombre" id="barco_nombre" required maxlength="120" class="ope-vj-inp" placeholder="p. ej. Going Merry">
      </div>
      <div class="ope-vj-barco-grid">
        <?php foreach ($barcos as $key => $b): ?>
        <label class="ope-vj-barco-card">
          <input type="radio" name="barco_tipo" value="<?php echo htmlspecialchars_uni($key); ?>" <?php echo $key === 'estandar' ? 'checked' : ''; ?>>
          <span class="ope-vj-barco-in">
            <strong><?php echo htmlspecialchars_uni($b['label']); ?></strong>
            <small>Mod. clima <?php echo (int) $b['clima']; ?> · peligros <?php echo (int) $b['peligros']; ?></small>
          </span>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="ope-vj-field">
        <label for="suministros">Suministros (opcional)</label>
        <input type="text" name="suministros" id="suministros" class="ope-vj-inp" placeholder="Comida, agua, madera…">
      </div>
      <div class="ope-vj-nav-row">
        <button type="button" class="ope-btn ope-btn-ghost ope-vj-prev" data-prev="1">Atrás</button>
        <button type="button" class="ope-btn ope-btn-hot ope-vj-next" data-next="3">Continuar</button>
      </div>
    </div>

    <!-- Paso 3: Tripulación -->
    <div class="ope-vj-panel" data-panel="3" role="tabpanel" hidden>
      <div class="ope-vj-panel-head">
        <h2>Forma la tripulación</h2>
        <p>Los oficios a bordo modifican el oráculo. Solo aparecerán compañeros registrados en tu tripulación.</p>
      </div>
      <p class="ope-vj-note">Capitán: <strong><?php echo htmlspecialchars_uni($pj_nombre); ?></strong> (Navegante). Marca compañeros de tu tripulación a bordo:</p>
      <?php if ($companeros): ?>
      <div class="ope-vj-crew">
        <?php foreach ($companeros as $c): ?>
        <label class="ope-vj-crew-chip">
          <input type="checkbox" name="tripulantes[]" value="<?php echo $c['pid']; ?>">
          <span><?php echo $c['nombre']; ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="ope-vj-empty">Viajas solo o sin tripulación registrada. Los oficios extra no aplicarán modificadores.</p>
      <?php endif; ?>
      <div class="ope-vj-field">
        <label for="notas">Notas para el narrador (opcional)</label>
        <textarea name="notas" id="notas" rows="3" class="ope-vj-ta" placeholder="Ruta preferida, carga especial…"></textarea>
      </div>
      <div class="ope-vj-nav-row">
        <button type="button" class="ope-btn ope-btn-ghost ope-vj-prev" data-prev="2">Atrás</button>
        <button type="button" class="ope-btn ope-btn-hot ope-vj-next" data-next="4">Revisar</button>
      </div>
    </div>

    <!-- Paso 4: Confirmar -->
    <div class="ope-vj-panel" data-panel="4" role="tabpanel" hidden>
      <div class="ope-vj-panel-head">
        <h2>Revisa la bitácora</h2>
        <p>Comprueba la ruta y el barco. Al zarpar, OP-Eternal abrirá el hilo y revelará las tiradas.</p>
      </div>
      <div class="ope-vj-summary" id="ope-vj-summary"></div>
      <p class="ope-vj-warn">Al confirmar, OP-Eternal creará el hilo con el Oráculo resuelto. Podréis rolear hasta que <strong>tú</strong> solicites la llegada.</p>
      <div class="ope-vj-nav-row">
        <button type="button" class="ope-btn ope-btn-ghost ope-vj-prev" data-prev="3">Atrás</button>
        <button type="submit" class="ope-btn ope-btn-hot ope-vj-submit">Invocar Oráculo y zarpar</button>
      </div>
    </div>
  </form>
  </div>
  <?php endif; ?>
</div>

<script>
(function(){
  var ISLAS = <?php echo $islas_json ?: '[]'; ?>;
  var form = document.getElementById('ope-vj-form');
  if (!form) return;

  function islaName(fid) {
    fid = parseInt(fid, 10);
    for (var i = 0; i < ISLAS.length; i++) {
      if (ISLAS[i].fid === fid) return ISLAS[i].nombre + ' (' + ISLAS[i].region + ')';
    }
    return '—';
  }
  function calcTramos(o, d) {
    o = parseInt(o, 10); d = parseInt(d, 10);
    if (!o || !d || o === d) return 0;
    var O, D, i;
    for (i = 0; i < ISLAS.length; i++) {
      if (ISLAS[i].fid === o) O = ISLAS[i];
      if (ISLAS[i].fid === d) D = ISLAS[i];
    }
    if (!O || !D) return 2;
    if (O.region_fid === D.region_fid) return 1;
    if (O.macro === D.macro) return 2;
    var t = 3;
    var hard = ['calm_belt','red_line','grand_line_plus'];
    if (hard.indexOf(O.macro) > -1 || hard.indexOf(D.macro) > -1) t++;
    if (O.macro === 'grand_line_plus' || D.macro === 'grand_line_plus') t++;
    return Math.min(5, Math.max(1, t));
  }
  function postsFor(t) {
    var m = {1:[6,5],2:[12,10],3:[18,15],4:[24,20],5:[30,25]};
    return m[t] || m[2];
  }

  var oSel = document.getElementById('fid_origen');
  var dSel = document.getElementById('fid_destino');
  var trEl = document.getElementById('ope-vj-tramos');
  function updRoute() {
    var t = calcTramos(oSel.value, dSel.value);
    if (trEl) trEl.textContent = t ? (t + ' tramo' + (t>1?'s':'')) : '—';
  }
  if (oSel) oSel.addEventListener('change', updRoute);
  if (dSel) dSel.addEventListener('change', updRoute);

  function goStep(n) {
    form.querySelectorAll('.ope-vj-step').forEach(function(s){
      var selected = s.getAttribute('data-step') === String(n);
      s.classList.toggle('is-on', selected);
      s.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    form.querySelectorAll('.ope-vj-panel').forEach(function(p){
      var on = p.getAttribute('data-panel') === String(n);
      p.classList.toggle('is-on', on);
      p.hidden = !on;
      if (on && window.gsap && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        gsap.fromTo(p, {opacity:0, x:12}, {opacity:1, x:0, duration:0.22, ease:'power2.out'});
      }
    });
    if (n === 4) buildSummary();
  }
  form.querySelectorAll('.ope-vj-next').forEach(function(b){
    b.addEventListener('click', function(){
      var cur = b.closest('.ope-vj-panel');
      if (cur && cur.getAttribute('data-panel') === '1') {
        if (!oSel.value || !dSel.value) { alert('Elige origen y destino.'); return; }
        if (oSel.value === dSel.value) { alert('Origen y destino deben ser distintos.'); return; }
      }
      if (cur && cur.getAttribute('data-panel') === '2') {
        if (!form.querySelector('[name="barco_nombre"]').value.trim()) { alert('Pon nombre al barco.'); return; }
      }
      goStep(b.getAttribute('data-next'));
    });
  });
  form.querySelectorAll('.ope-vj-prev').forEach(function(b){ b.addEventListener('click', function(){ goStep(b.getAttribute('data-prev')); }); });
  form.querySelectorAll('.ope-vj-step').forEach(function(s){ s.addEventListener('click', function(){ goStep(s.getAttribute('data-step')); }); });

  function buildSummary() {
    var box = document.getElementById('ope-vj-summary');
    if (!box) return;
    var t = calcTramos(oSel.value, dSel.value);
    var pp = postsFor(t);
    var barco = form.querySelector('[name="barco_nombre"]').value || '—';
    var tipo = form.querySelector('[name="barco_tipo"]:checked');
    box.innerHTML = '<div class="ope-vj-sum-row"><span>Ruta</span><b>' + islaName(oSel.value) + ' → ' + islaName(dSel.value) + '</b></div>'
      + '<div class="ope-vj-sum-row"><span>Tramos / posts sug.</span><b>' + t + ' · ' + pp[0] + ' posts</b></div>'
      + '<div class="ope-vj-sum-row"><span>Barco</span><b>' + barco + (tipo ? ' (' + tipo.value + ')' : '') + '</b></div>';
  }

  form.addEventListener('submit', function(e){
    if (!oSel.value || !dSel.value || oSel.value === dSel.value) {
      e.preventDefault(); alert('Revisa la ruta antes de zarpar.'); return;
    }
    if (window.gsap) gsap.to('.ope-vj-submit', {scale:0.96, duration:0.1, yoyo:true, repeat:1});
  });
})();
</script>
</body>
</html>
