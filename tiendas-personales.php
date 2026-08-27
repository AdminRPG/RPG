<?php
/**
 * One Piece: Eternal · Tiendas Personales
 * Lista de personajes con sus tiendas privadas + tiendas de ejemplo.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tiendas-personales.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

// ── Cargar personajes con tienda abierta ──
$tiendas = array();
if ($db->table_exists('rol_personajes') && $db->field_exists('tienda_abierta', 'rol_personajes')) {
    $qt = $db->simple_select(
        'rol_personajes p LEFT JOIN mybb_users u ON u.uid = p.uid',
        'p.pid, p.nombre, p.apellido, p.faccion, p.nivel, p.avatar_url, p.recompensa_total, u.username',
        "p.tienda_abierta = 1 AND p.estado = 'aprobado' AND p.pid != " . (int) $pid . "
         AND EXISTS (SELECT 1 FROM rol_inventario i WHERE i.pid = p.pid AND i.cantidad > 0 AND i.en_venta = 1)",
        array('order_by' => 'p.recompensa_total', 'order_dir' => 'DESC', 'limit' => 30)
    );
    while ($row = $db->fetch_array($qt)) {
        // Contar objetos en venta
        $cv = $db->simple_select('rol_inventario', 'COUNT(*) AS cnt, SUM(precio_venta) AS valor',
            "pid = {$row['pid']} AND cantidad > 0 AND en_venta = 1");
        $v = $db->fetch_array($cv);
        $row['_objetos'] = (int) ($v['cnt'] ?? 0);
        $row['_valor'] = (int) ($v['valor'] ?? 0);
        $tiendas[] = $row;
    }
}

// ── Datos demo si la tabla no existe o está vacía ──
if (empty($tiendas) && !$db->field_exists('tienda_abierta', 'rol_personajes')) {
    $tiendas = array(
        array(
            'pid' => 9001, 'nombre' => 'Roronoa', 'apellido' => 'Zoro', 'faccion' => 'Pirata',
            'nivel' => 12, 'avatar_url' => '', 'username' => 'zoro', 'recompensa_total' => 1101000000,
            '_objetos' => 8, '_valor' => 250000,
            '_demo' => true, '_tienda_nombre' => 'dojo de la pobreza',
        ),
        array(
            'pid' => 9002, 'nombre' => 'Nami', 'apellido' => '', 'faccion' => 'Pirata',
            'nivel' => 10, 'avatar_url' => '', 'username' => 'nami', 'recompensa_total' => 366000000,
            '_objetos' => 15, '_valor' => 890000,
            '_demo' => true, '_tienda_nombre' => 'la tangerina',
        ),
        array(
            'pid' => 9003, 'nombre' => 'Usopp', 'apellido' => '', 'faccion' => 'Pirata',
            'nivel' => 8, 'avatar_url' => '', 'username' => 'usopp', 'recompensa_total' => 200000000,
            '_objetos' => 12, '_valor' => 420000,
            '_demo' => true, '_tienda_nombre' => 'el arsenal',
        ),
        array(
            'pid' => 9004, 'nombre' => 'Tony', 'apellido' => 'Tony Chopper', 'faccion' => 'Pirata',
            'nivel' => 6, 'avatar_url' => '', 'username' => 'chopper', 'recompensa_total' => 1000,
            '_objetos' => 10, '_valor' => 150000,
            '_demo' => true, '_tienda_nombre' => 'la botica',
        ),
        array(
            'pid' => 9005, 'nombre' => 'Franky', 'apellido' => '', 'faccion' => 'Pirata',
            'nivel' => 9, 'avatar_url' => '', 'username' => 'franky', 'recompensa_total' => 394000000,
            '_objetos' => 6, '_valor' => 680000,
            '_demo' => true, '_tienda_nombre' => 'super tienda',
        ),
        array(
            'pid' => 9006, 'nombre' => 'Brook', 'apellido' => '', 'faccion' => 'Pirata',
            'nivel' => 7, 'avatar_url' => '', 'username' => 'brook', 'recompensa_total' => 383000000,
            '_objetos' => 5, '_valor' => 95000,
            '_demo' => true, '_tienda_nombre' => 'la música y más',
        ),
    );
}

$page_title = $bbname . ' · Tiendas Personales';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>
<?php echo ope_rol_head_base(); ?>
<style>
.tp-search{position:relative;max-width:420px;margin-bottom:22px}
.tp-search-input{width:100%;font-family:var(--body);font-size:.92rem;padding:11px 14px 11px 42px;border:1px solid var(--ope-line);border-radius:10px;background:var(--ope-card);color:var(--ope-ink);transition:border-color .15s,box-shadow .15s}
.tp-search-input:focus{outline:none;border-color:var(--ope-gold);box-shadow:0 0 0 3px color-mix(in srgb,var(--ope-gold) 14%,transparent)}
.tp-search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ope-ink-dim);pointer-events:none}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;padding-bottom:48px}
.tp-empty{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;gap:8px;padding:48px 10px;text-align:center}
.tp-empty .empty-icon{opacity:.35}
.tp-empty span{font-size:.92rem;color:var(--ope-ink-soft)}
.tp-empty small{font-size:.76rem;color:var(--ope-ink-dim)}
/* Tarjeta de tienda */
.tp-card{display:flex;flex-direction:column;background:var(--ope-card);border:1px solid var(--ope-line);border-radius:14px;text-decoration:none;color:var(--ope-ink);transition:transform .18s,border-color .18s,box-shadow .18s;overflow:hidden}
.tp-card:hover{transform:translateY(-3px);border-color:var(--ope-gold);box-shadow:var(--ope-shadow)}
.tp-card-top{display:flex;align-items:center;gap:14px;padding:18px 18px 0}
.tp-card-avatar{width:48px;height:48px;border-radius:50%;background:var(--ope-card-2);border:2px solid var(--ope-line-soft);display:flex;align-items:center;justify-content:center;font-family:var(--ope-disp);font-weight:700;font-size:1.05rem;color:var(--ope-gold-deep);flex:0 0 auto;overflow:hidden;transition:border-color .15s}
.tp-card:hover .tp-card-avatar{border-color:var(--ope-gold)}
.tp-card-avatar img{width:100%;height:100%;object-fit:cover}
.tp-card-top-info{flex:1;min-width:0}
.tp-card-top-info h4{font-family:var(--ope-disp);font-weight:700;font-size:.98rem;color:var(--ope-ink);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tp-card-store{font-family:var(--mono);font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--ope-sky-deep);margin-top:1px}
.tp-card-body{padding:10px 18px 18px}
.tp-card-stats{display:flex;gap:16px;margin-bottom:10px}
.tp-card-stat{display:flex;flex-direction:column}
.tp-card-stat-val{font-family:var(--mono);font-size:.78rem;font-weight:700;color:var(--ope-ink)}
.tp-card-stat-lbl{font-family:var(--mono);font-size:.52rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--ope-ink-dim)}
.tp-card-footer{display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-top:1px solid var(--ope-line-soft);background:var(--ope-card-2)}
.tp-card-faction{font-family:var(--mono);font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
.tp-card-faction.pirata{color:var(--crack)}
.tp-card-faction.marine{color:var(--ope-sky-deep)}
.tp-card-faction.revolucionario{color:var(--patina-deep)}
.tp-card-faction.civil{color:var(--ope-ink-dim)}
.tp-card-view{font-family:var(--mono);font-size:.62rem;font-weight:700;color:var(--ope-gold-deep);letter-spacing:.3px}
html[data-theme="noche"] .tp-card{background:rgba(14,23,48,.72);border-color:rgba(185,198,221,.12)}
html[data-theme="noche"] .tp-card-footer{background:rgba(185,198,221,.04)}
html[data-theme="noche"] .tp-search-input{background:rgba(14,23,48,.72);border-color:rgba(185,198,221,.12)}
@media(max-width:600px){.tp-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="ope-pg-tiendas-personales">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tiendas.php">Tiendas</a><span class="sep">›</span><b>Tiendas Personales</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tiendas Personales</h1>
      <span class="code">// comercio entre personajes</span>
      <span class="rule"></span>
    </div>
    <p class="tiendas-intro">Las tiendas privadas de otros personajes. Selecciona uno para ver qué tiene a la venta.</p>
  </section>

  <!-- Buscador -->
  <div class="tp-search reveal">
    <input type="text" class="tp-search-input" placeholder="Buscar personaje o tienda..." id="tp-search" autocomplete="off">
    <svg viewBox="0 0 24 24" width="18" height="18" class="tp-search-icon"><circle cx="11" cy="11" r="8" fill="none" stroke="currentColor" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2"/></svg>
  </div>

  <!-- Lista de tiendas -->
  <?php if (empty($tiendas)): ?>
    <div class="tp-empty reveal">
      <svg viewBox="0 0 24 24" width="48" height="48" class="empty-icon"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
      <span>No hay tiendas abiertas</span>
      <small>Cuando otros personajes abran sus tiendas, aparecerán aquí.</small>
    </div>
  <?php else: ?>
    <div class="tp-grid reveal">
      <?php foreach ($tiendas as $t):
        $fullname = trim(($t['nombre'] ?? '') . ' ' . ($t['apellido'] ?? ''));
        $iniciales = strtoupper(mb_substr($t['nombre'] ?? '?', 0, 1));
        $fname_e = htmlspecialchars_uni($fullname);
        $faction_e = htmlspecialchars_uni(mb_strtolower($t['faccion'] ?? 'civil'));
        $store_name = !empty($t['_tienda_nombre']) ? htmlspecialchars_uni($t['_tienda_nombre']) : 'Tienda de ' . $fname_e;
        $objs = (int) ($t['_objetos'] ?? 0);
        $valor = (int) ($t['_valor'] ?? 0);
        $nivel = (int) ($t['nivel'] ?? 1);
        $reward = (int) ($t['recompensa_total'] ?? 0);
        $reward_str = number_format($reward, 0, ',', '.');
        $valor_str = number_format($valor, 0, ',', '.') . ' berries';
      ?>
      <div class="tp-card" data-name="<?php echo $fname_e . ' ' . $store_name; ?>">
        <div class="tp-card-top">
          <div class="tp-card-avatar">
            <?php if (!empty($t['avatar_url'])): ?>
              <img src="<?php echo htmlspecialchars_uni($t['avatar_url']); ?>" alt="<?php echo $fname_e; ?>">
            <?php else: ?>
              <?php echo $iniciales; ?>
            <?php endif; ?>
          </div>
          <div class="tp-card-top-info">
            <h4><?php echo $fname_e; ?></h4>
            <div class="tp-card-store"><?php echo $store_name; ?></div>
          </div>
        </div>
        <div class="tp-card-body">
          <div class="tp-card-stats">
            <div class="tp-card-stat">
              <span class="tp-card-stat-val"><?php echo $objs; ?></span>
              <span class="tp-card-stat-lbl">En venta</span>
            </div>
            <div class="tp-card-stat">
              <span class="tp-card-stat-val"><?php echo $valor_str; ?></span>
              <span class="tp-card-stat-lbl">Valor total</span>
            </div>
            <div class="tp-card-stat">
              <span class="tp-card-stat-val">Nv.<?php echo $nivel; ?></span>
              <span class="tp-card-stat-lbl">Nivel</span>
            </div>
          </div>
        </div>
        <div class="tp-card-footer">
          <span class="tp-card-faction <?php echo $faction_e; ?>"><?php echo htmlspecialchars_uni($t['faccion'] ?? '—'); ?></span>
          <span class="tp-card-view">Ver tienda →</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
/* Búsqueda en tiempo real */
(function(){
  var input = document.getElementById('tp-search');
  if (!input) return;
  input.addEventListener('input', function(){
    var q = this.value.toLowerCase();
    document.querySelectorAll('.tp-card').forEach(function(c){
      var name = (c.getAttribute('data-name') || '').toLowerCase();
      c.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
    });
  });
})();
/* Reveal observer */
if('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches){
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');io.unobserve(e.target);}});},{threshold:.08});
  document.querySelectorAll('.reveal,.tp-card').forEach(function(el){io.observe(el);});
} else { document.querySelectorAll('.reveal,.tp-card').forEach(function(el){el.classList.add('vis');}); }
</script>
</body>
</html>
