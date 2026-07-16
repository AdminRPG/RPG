<?php
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tablon-misiones.php');
require_once './global.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid      = (int)($mybb->user['uid'] ?? 0);

$ciclo    = gbe_rol_mv_ciclo_actual();
$ciclo_id = $ciclo ? (int)$ciclo['ciclo_id'] : 0;
$todas    = $ciclo_id ? gbe_rol_mv_misiones($ciclo_id) : array();
$asignadas = gbe_rol_mv_asignaciones_map();
$misiones = array();
foreach ($todas as $m) {
    // Disponibles = en curso y que nadie haya cogido todavía.
    if ($m['estado'] === 'en_curso' && !isset($asignadas[(int)$m['mision_id']])) {
        $misiones[] = $m;
    }
}

// Personaje activo del jugador y catálogo de PJs aprobados para elegir
// compañeros en misiones de grupo.
$activo_pid = 0;
if ($loggedin && $db->table_exists('rol_cuentas')) {
    $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($cq)) $activo_pid = (int)$db->fetch_field($cq, 'personaje_activo');
}
$companeros_pool = array();
if ($loggedin && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', 'pid, nombre', "estado = 'aprobado' AND es_npc = 0", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
    while ($pr = $db->fetch_array($pq)) {
        if ((int)$pr['pid'] === $activo_pid) continue;
        $companeros_pool[] = array('pid' => (int)$pr['pid'], 'nombre' => htmlspecialchars_uni($pr['nombre']));
    }
}

$tm_flash = ''; $tm_flash_kind = 'ok';
$aviso = $mybb->get_input('a'); $error = $mybb->get_input('e');
if ($aviso === 'ok') { $tm_flash = 'Has cogido la misión. Aparecerá como "en proceso" para el staff.'; }
elseif ($error === 'sin_personaje') { $tm_flash = 'Necesitas un personaje activo para aceptar misiones.'; $tm_flash_kind = 'warn'; }
elseif ($error === 'ya_cogida') { $tm_flash = 'Esa misión ya la ha cogido otro personaje.'; $tm_flash_kind = 'warn'; }
elseif ($error === 'no_disponible') { $tm_flash = 'Esa misión ya no está disponible.'; $tm_flash_kind = 'warn'; }
elseif ($error === 'sesion') { $tm_flash = 'La sesión caducó. Vuelve a intentarlo.'; $tm_flash_kind = 'warn'; }

$zonas     = $db->table_exists('rol_mv_zonas') ? gbe_rol_mv_zonas() : array();
$facciones = $db->table_exists('rol_mv_facciones') ? gbe_rol_mv_facciones() : array();
$orden_fac = gbe_rol_mv_faccion_order();

$mes_label = is_array($ciclo) ? htmlspecialchars_uni($ciclo['periodo']) : '';

$rango_colors = array('S'=>'#dc2626','A'=>'#ea580c','B'=>'#ca8a04','C'=>'#2563eb','D'=>'#6b7280');
$modalidad_labels = array('solo'=>'Individual','grupo'=>'Grupo','cualquiera'=>'Cualquiera');

$fac_slugs = array();
foreach ($orden_fac as $f) {
    if (isset($facciones[$f])) $fac_slugs[] = $f;
}

$per_page = 12;
$total    = count($misiones);
$pages    = max(1, ceil($total / $per_page));
$page     = max(1, min($pages, (int)($mybb->input['p'] ?? 1)));
$offset   = ($page - 1) * $per_page;
$page_set = array_slice($misiones, $offset, $per_page);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tablón de Misiones</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-tramites gbe-pg-tablon-misiones">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a>
    <span class="sep">›</span>
    <b>Tablón de Misiones</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Tablón de Misiones</h1>
      <span class="code">// <?php echo $mes_label ?: 'mes actual'; ?></span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($tm_flash !== ''): ?>
  <section class="reveal"><div class="flash <?php echo $tm_flash_kind; ?>"><?php echo htmlspecialchars_uni($tm_flash); ?></div></section>
<?php endif; ?>

  <section class="reveal" id="tmApp">
    <div class="tm-filters">
      <div class="tm-fil-group">
        <span class="tm-fil-label">Lugar</span>
        <div class="tm-fil-chips" data-group="zona">
          <button type="button" class="tm-chip on" data-value="">Todos</button>
<?php foreach ($zonas as $z): ?>
          <button type="button" class="tm-chip" data-value="<?php echo htmlspecialchars_uni($z['slug']); ?>"><?php echo htmlspecialchars_uni($z['nombre']); ?></button>
<?php endforeach; ?>
        </div>
      </div>
      <div class="tm-fil-group">
        <span class="tm-fil-label">Facción</span>
        <div class="tm-fil-chips" data-group="faccion">
          <button type="button" class="tm-chip on" data-value="">Todas</button>
<?php foreach ($fac_slugs as $fs): $fn = htmlspecialchars_uni($facciones[$fs]['nombre'] ?? $fs); ?>
          <button type="button" class="tm-chip" data-value="<?php echo htmlspecialchars_uni($fs); ?>"><?php echo $fn; ?></button>
<?php endforeach; ?>
        </div>
      </div>
    </div>

<?php if (empty($misiones)): ?>
    <div class="tm-empty">
      <div class="tm-empty-icon"><svg viewBox="0 0 24 24" width="48" height="48"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
<?php if (!$ciclo): ?>
      <p>No hay ciclo activo. El staff debe crear un ciclo de misiones en el panel de administraci&oacute;n.</p>
<?php else: ?>
      <p>No hay misiones publicadas este mes. Vuelve cuando el staff publique el siguiente ciclo.</p>
<?php endif; ?>
    </div>
<?php else: ?>
    <div class="tm-board" id="tmBoard">
<?php
$rots = array(-2.5, 1.5, -1, 3, -3, 2, -0.5, 0.5, -1.8, 2.8, -2.2, 1);
$i = 0;
foreach ($page_set as $m):
    $mid     = (int)$m['mision_id'];
    $titulo  = htmlspecialchars_uni($m['titulo']);
    $corta   = htmlspecialchars_uni((string)$m['resumen']);
    $larga   = htmlspecialchars_uni((string)$m['descripcion_larga']);
    $zslug   = htmlspecialchars_uni($m['zona_slug']);
    $zname   = isset($zonas[$m['zona_slug']]) ? htmlspecialchars_uni($zonas[$m['zona_slug']]['nombre']) : $zslug;
    $fac     = htmlspecialchars_uni($m['facciones']);
    $rango   = htmlspecialchars_uni($m['rango'] ?: 'D');
    $pel     = min(5, max(1, (int)($m['peligrosidad'] ?? 1)));
    $rec     = htmlspecialchars_uni((string)$m['recompensa']);
    $mod_lbl = $modalidad_labels[$m['modalidad']] ?? 'Cualquiera';
    $rc      = $rango_colors[$m['rango']] ?? '#6b7280';
    $rot     = $rots[$i % count($rots)];
    $i++;
?>
      <article class="tm-card" data-zona="<?php echo $zslug ?: '—'; ?>" data-faccion="<?php echo $fac ?: '—'; ?>" data-mid="<?php echo $mid; ?>" style="--rot:<?php echo $rot; ?>deg">
        <div class="tm-pin"></div>
        <div class="tm-card-hd">
          <span class="tm-rank" style="--rc:<?php echo $rc; ?>"><?php echo $rango; ?></span>
          <span class="tm-hd-t"><?php echo $titulo; ?></span>
          <span class="tm-hd-zone"><?php echo $zname; ?></span>
        </div>
        <div class="tm-card-desc"><?php echo $corta ?: '<span class="tm-dim">Sin descripción.</span>'; ?></div>
      </article>
<?php endforeach; ?>
    </div>

<?php if ($pages > 1): ?>
    <div class="tm-pages">
      <a href="?p=<?php echo max(1,$page-1); ?>" class="tm-page<?php if ($page<=1) echo ' tm-page-na'; ?>">‹ Anterior</a>
<?php for ($pg=1;$pg<=$pages;$pg++): ?>
      <a href="?p=<?php echo $pg; ?>" class="tm-page<?php if ($pg===$page) echo ' on'; ?>"><?php echo $pg; ?></a>
<?php endfor; ?>
      <a href="?p=<?php echo min($pages,$page+1); ?>" class="tm-page<?php if ($page>=$pages) echo ' tm-page-na'; ?>">Siguiente ›</a>
    </div>
<?php endif; ?>

<?php endif; ?>
  </section>

  <!-- Overlay de detalle (se llena por JS) -->
  <div class="tm-overlay" id="tmOverlay" hidden>
    <div class="tm-detail" id="tmDetail"></div>
  </div>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var groups = document.querySelectorAll('.tm-fil-chips');
  var cards  = document.querySelectorAll('.tm-card');
  var overlay = document.getElementById('tmOverlay');
  var detail  = document.getElementById('tmDetail');

  function applyFilters() {
    var filters = {};
    groups.forEach(function(g){
      var key = g.getAttribute('data-group') || 'default';
      var active = g.querySelector('.tm-chip.on');
      filters[key] = active ? active.getAttribute('data-value') : '';
    });
    var visible = 0;
    cards.forEach(function(c){
      var match = true;
      if (filters['zona'] !== '') {
        if (c.getAttribute('data-zona') !== filters['zona']) match = false;
      }
      if (match && filters['faccion'] !== '') {
        var facs = c.getAttribute('data-faccion').split(/[,\s]+/);
        if (facs.indexOf(filters['faccion']) === -1) match = false;
      }
      c.classList.toggle('hidden', !match);
      if (match) visible++;
    });
  }
  groups.forEach(function(g){
    g.addEventListener('click', function(e){
      var chip = e.target.closest('.tm-chip');
      if (!chip) return;
      g.querySelectorAll('.tm-chip').forEach(function(c){ c.classList.toggle('on', c === chip); });
      applyFilters();
    });
  });

  // Cargar detalle de misión via JSON o嵌入 datos
  var misionesData = <?php echo json_encode(array_map(function($m) use($zonas, $rango_colors, $modalidad_labels, $bburl, $loggedin, $mybb) {
    $pel = min(5, max(1, (int)($m['peligrosidad'] ?? 1)));
    $dots = '';
    for ($d=1;$d<=5;$d++) $dots .= '<span class="tm-dot'.($d<=$pel?' on':'').'"></span>';
    return array(
      'id'       => (int)$m['mision_id'],
      'titulo'   => htmlspecialchars_uni($m['titulo']),
      'corta'    => htmlspecialchars_uni((string)$m['resumen']),
      'larga'    => htmlspecialchars_uni((string)$m['descripcion_larga']),
      'zona'     => isset($zonas[$m['zona_slug']]) ? htmlspecialchars_uni($zonas[$m['zona_slug']]['nombre']) : htmlspecialchars_uni($m['zona_slug']),
      'faccion'  => htmlspecialchars_uni($m['facciones']),
      'rango'    => htmlspecialchars_uni($m['rango'] ?: 'D'),
      'rcolor'   => $rango_colors[$m['rango']] ?? '#6b7280',
      'peligro'  => $pel,
      'dots'     => $dots,
      'rec'      => htmlspecialchars_uni((string)$m['recompensa']),
      'mod'      => (string)$m['modalidad'],
      'mod_lbl'  => $modalidad_labels[$m['modalidad']] ?? 'Cualquiera',
      'log'      => $loggedin,
      'pkey'     => htmlspecialchars_uni($mybb->post_code),
      'bburl'    => $bburl,
    );
  }, $misiones)); ?>;
  var companerosPool = <?php echo json_encode($companeros_pool, JSON_UNESCAPED_UNICODE); ?>;
  var tienePersonaje = <?php echo $activo_pid > 0 ? 'true' : 'false'; ?>;

  // Click en card -> animación "arrancar" y mostrar detalle
  document.addEventListener('click', function(e){
    var card = e.target.closest('.tm-card');
    if (!card || overlay.hidden !== true) return;
    if (e.target.closest('.tm-chip')) return;
    var mid = parseInt(card.getAttribute('data-mid'));
    var d = misionesData.find(function(x){ return x.id === mid; });
    if (!d) return;

    // Posición actual de la card
    var rect = card.getBoundingClientRect();
    var board = document.getElementById('tmBoard');
    var boardRect = board.getBoundingClientRect();

    // Render detalle
    detail.innerHTML =
      '<button type="button" class="tm-d-close" id="tmDClose">✕</button>' +
      '<div class="tm-d-header">' +
        '<span class="tm-rank tm-d-rank" style="--rc:' + d.rcolor + '">' + d.rango + '</span>' +
        '<div class="tm-d-hd-text">' +
          '<div class="tm-d-titulo">' + d.titulo + '</div>' +
          '<div class="tm-d-meta">' + d.zona + (d.faccion ? ' · ' + d.faccion : '') + '</div>' +
        '</div>' +
      '</div>' +
      '<div class="tm-d-desc">' +
        '<span class="tm-bg-lbl">Descripción</span>' +
        '<p>' + (d.larga || d.corta || '<span class="tm-dim">Sin descripción.</span>') + '</p>' +
      '</div>' +
      '<div class="tm-d-grid">' +
        '<div class="tm-d-item"><span class="tm-bg-lbl">Rango</span><span class="tm-d-val fw-800" style="color:' + d.rcolor + '">' + d.rango + '</span></div>' +
        '<div class="tm-d-item"><span class="tm-bg-lbl">Peligrosidad</span><span class="tm-d-val tm-dots">' + d.dots + '</span></div>' +
        '<div class="tm-d-item"><span class="tm-bg-lbl">Modalidad</span><span class="tm-d-val">' + d.mod_lbl + '</span></div>' +
        '<div class="tm-d-item"><span class="tm-bg-lbl">Recompensa</span><span class="tm-d-val">' + (d.rec || '<span class="tm-dim">—</span>') + '</span></div>' +
      '</div>' +
      (d.log ? (
        !tienePersonaje ?
          '<div class="tm-d-note">Necesitas un personaje activo para coger esta misión.</div>' :
          '<form method="post" action="' + d.bburl + '/aceptar-mision.php" class="tm-d-frm">' +
            '<input type="hidden" name="mision_id" value="' + d.id + '">' +
            '<input type="hidden" name="my_post_key" value="' + d.pkey + '">' +
            (d.mod !== 'solo' && companerosPool.length ?
              '<div class="tm-d-crew"><span class="tm-bg-lbl">Compañeros de misión (opcional)</span>' +
              '<div class="tm-d-crew-list">' +
                companerosPool.map(function(c){
                  return '<label class="tm-d-crew-opt"><input type="checkbox" name="companeros[]" value="' + c.pid + '"><span>' + c.nombre + '</span></label>';
                }).join('') +
              '</div></div>' : '') +
            '<button type="submit" class="btn btn-hot">' + (d.mod !== 'solo' ? 'Coger misión en grupo' : 'Coger misión') + '</button>' +
          '</form>'
        ) :
        '<div class="tm-d-note">Inicia sesión para aceptar esta misión.</div>'
      );

    overlay.hidden = false;
    document.body.classList.add('tm-no-scroll');

    // Posicionar detalle desde la card
    var cardCx = rect.left + rect.width/2;
    var cardCy = rect.top + rect.height/2;
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var fromX = (cardCx / vw * 100) + '%';
    var fromY = (cardCy / vh * 100) + '%';

    detail.style.setProperty('--from-x', fromX);
    detail.style.setProperty('--from-y', fromY);
    detail.classList.remove('tm-d-in');
    void detail.offsetWidth;
    detail.classList.add('tm-d-in');
  });

  // Cerrar detalle
  document.addEventListener('click', function(e){
    if (e.target.id === 'tmDClose' || e.target === overlay) {
      detail.classList.remove('tm-d-in');
      detail.addEventListener('transitionend', function(){
        overlay.hidden = true;
        document.body.classList.remove('tm-no-scroll');
      }, { once: true });
    }
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && !overlay.hidden) {
      detail.classList.remove('tm-d-in');
      detail.addEventListener('transitionend', function(){
        overlay.hidden = true;
        document.body.classList.remove('tm-no-scroll');
      }, { once: true });
    }
  });

})();
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(function(es){ es.forEach(function(e){
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }); }, { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
}
</script>
</body>
</html>