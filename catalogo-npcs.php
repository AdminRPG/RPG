<?php
/**
 * One Piece: Eternal · Catálogo de Personajes Mayores (NPCs)
 * Página independiente que muestra todas las figuras legendarias, almirantes,
 * Yonkou y agentes del Gobierno Mundial registrados en el universo de One Piece: Eternal.
 * 
 * Acceso público: catalogo-npcs.php
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'catalogo-npcs.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

// Consulta dinámica a la base de datos: todos los personajes oficiales con es_npc = 1
$all_npcs_db = array();
if ($db->table_exists('rol_personajes')) {
    $nq = $db->simple_select('rol_personajes', '*', "es_npc = 1 AND estado = 'aprobado'", array('order_by' => 'nivel', 'order_dir' => 'desc'));
    while ($nr = $db->fetch_array($nq)) {
        $nr['datos_json'] = json_decode((string)$nr['datos'], true) ?: array();
        $nr['bio_json']   = json_decode((string)$nr['bio'], true) ?: array();
        $nr['fac_slug']   = function_exists('ope_rol_faccion_slug') ? ope_rol_faccion_slug($nr['datos_json']['faccion'] ?? '') : '';
        $all_npcs_db[] = $nr;
    }
}

// Conteo por facciones
$counts = array('all' => count($all_npcs_db), 'marines' => 0, 'gobierno-mundial' => 0, 'piratas' => 0, 'cazarrecompensas' => 0, 'revolucionarios' => 0);
foreach ($all_npcs_db as $n) {
    $fc = $n['fac_slug'];
    if ($fc === 'marine' || $fc === 'marines') $counts['marines']++;
    elseif ($fc === 'gobierno' || $fc === 'gobierno-mundial') $counts['gobierno-mundial']++;
    elseif ($fc === 'pirata' || $fc === 'piratas') $counts['piratas']++;
    elseif ($fc === 'cazarrecompensas') $counts['cazarrecompensas']++;
    elseif ($fc === 'revolucionarios' || $fc === 'revolucionario') $counts['revolucionarios']++;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Catálogo de Personajes (NPCs)</title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: body.ope-pg-npcs) -->
</head>
<body class="ope-pg-guias ope-pg-npcs">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <b>Catálogo de Personajes (NPCs)</b>
  </div>
</div>

<div class="wrap">

<!-- CABECERA PRINCIPAL -->
<section class="reveal npc-page-hero">
  <div class="shead">
    <h1>Catálogo de Personajes Mayores</h1>
    <span class="code">// archivo de figuras legendarias · <?php echo count($all_npcs_db); ?> registros</span>
    <span class="rule"></span>
  </div>
  <p class="guia-intro">
    Registro oficial de los <b>personajes mayores no jugadores (NPCs)</b> de <i>One Piece: Eternal</i>. 
    Aquí figuran los Almirantes de la Marina, los Cuatro Emperadores (Yonkou), los Inquisidores del Gobierno Mundial y los maestros del Gremio de Cazarrecompensas.
  </p>
</section>

<!-- BARRA DE FILTROS POR FACCIÓN -->
<section class="reveal">
  <div class="npc-cat-toolbar">
    <button class="npc-cat-btn active" data-filter="all">Todos los Personajes (<?php echo $counts['all']; ?>)</button>
    <button class="npc-cat-btn" data-filter="marines">Marina (<?php echo $counts['marines']; ?>)</button>
    <button class="npc-cat-btn" data-filter="gobierno-mundial">Gobierno Mundial (<?php echo $counts['gobierno-mundial']; ?>)</button>
    <button class="npc-cat-btn" data-filter="piratas">Piratas / Yonkou (<?php echo $counts['piratas']; ?>)</button>
    <button class="npc-cat-btn" data-filter="cazarrecompensas">Cazarrecompensas (<?php echo $counts['cazarrecompensas']; ?>)</button>
  </div>
</section>

<!-- GRID DE FICHAS DE NPCS -->
<section class="reveal">
  <div class="npc-grid">
<?php foreach ($all_npcs_db as $npc):
    $d = $npc['datos_json'];
    $b = $npc['bio_json'];
    $fc = $npc['fac_slug'];
    $avatar = trim((string)$npc['avatar']);
    $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($npc['nombre'], 0, 1, 'UTF-8')) : strtoupper(substr($npc['nombre'], 0, 1));
    $apodo = trim((string)($b['apodo'] ?? $d['apodo'] ?? ''));
    $title = $apodo !== '' ? '«' . $apodo . '»' : ($npc['rango_faccion'] ?: 'NPC Oficial');
    $fruta_n = $d['fruta_nombre'] ?? null;
    $raza_n = ucfirst($d['raza_principal'] ?? 'Humano');
    $level = (int)$npc['nivel'];
    $desc_f = trim((string)($npc['desc_fisica'] ?: ($b['desc_fisica'] ?? '')));
    $desc_p = trim((string)($npc['personalidad'] ?: ($b['desc_psicologica'] ?? '')));
?>
    <div class="npc-card fac-<?php echo htmlspecialchars($fc); ?>" data-faccion="<?php echo htmlspecialchars($fc); ?>">
      <div class="npc-card-head">
        <div class="npc-avatar-box">
<?php if ($avatar !== ''): ?>
          <img src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="<?php echo htmlspecialchars_uni($npc['nombre']); ?>">
<?php else: ?>
          <span class="npc-avatar-ph"><?php echo $initial; ?></span>
<?php endif; ?>
        </div>
        <div class="npc-meta">
          <div class="npc-name"><?php echo htmlspecialchars_uni($npc['nombre']); ?></div>
          <div class="npc-rank"><?php echo htmlspecialchars_uni($title); ?></div>
          <div class="npc-badges">
            <span class="npc-badge">Lvl <?php echo $level; ?></span>
            <span class="npc-badge"><?php echo htmlspecialchars_uni($raza_n); ?></span>
<?php if ($fruta_n): ?>
            <span class="npc-badge"><?php echo htmlspecialchars_uni($fruta_n); ?></span>
<?php endif; ?>
          </div>
        </div>
      </div>

      <div class="npc-card-body">
<?php if ($desc_f !== ''): ?>
        <div class="npc-desc-sec">
          <div class="npc-desc-sec-h">Descripción Física</div>
          <p class="npc-desc-sec-p"><?php echo htmlspecialchars_uni(function_exists('mb_substr') ? mb_substr($desc_f, 0, 210) . '...' : substr($desc_f, 0, 210) . '...'); ?></p>
        </div>
<?php endif; ?>

<?php if ($desc_p !== ''): ?>
        <div class="npc-desc-sec">
          <div class="npc-desc-sec-h">Personalidad & Ideología</div>
          <p class="npc-desc-sec-p"><?php echo htmlspecialchars_uni(function_exists('mb_substr') ? mb_substr($desc_p, 0, 210) . '...' : substr($desc_p, 0, 210) . '...'); ?></p>
        </div>
<?php endif; ?>
      </div>

      <div class="npc-card-foot">
        <span class="npc-rank"><?php echo htmlspecialchars_uni($npc['rango_faccion'] ?: 'NPC'); ?></span>
        <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int)$npc['pid']; ?>" class="npc-btn-link">Ver Ficha Completa →</a>
      </div>
    </div>
<?php endforeach; ?>
  </div>
</section>

</div><!-- /.wrap -->

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target);} }); }, { threshold:.08 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }

  // Filtros interactivos por Facción
  var filterBtns = document.querySelectorAll('.npc-cat-btn');
  var npcCards = document.querySelectorAll('.npc-card');
  filterBtns.forEach(function(btn){
    btn.addEventListener('click', function(){
      var f = btn.getAttribute('data-filter');
      filterBtns.forEach(function(b){ b.classList.toggle('active', b === btn); });
      npcCards.forEach(function(card){
        var fac = card.getAttribute('data-faccion');
        if (f === 'all' || fac === f || (f === 'piratas' && (fac === 'piratas' || fac === 'pirata')) || (f === 'marines' && (fac === 'marines' || fac === 'marine')) || (f === 'gobierno-mundial' && (fac === 'gobierno' || fac === 'gobierno-mundial'))) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
})();
</script>
</body>
</html>
