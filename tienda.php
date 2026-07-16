<?php
/**
 * I-Forge · Tienda del foro — Bazar Pirata
 * Productos poblados desde BD (rol_tienda_items). Sin datos mockup.
 * Estilos en docs/themes/gbe.css (scope: gbe-pg-tienda).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tienda.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$tiendas    = gbe_rol_cat_tiendas();
$cat_labels = gbe_rol_cat_categoria_labels();
$items      = gbe_rol_cat_tienda_items();

$productos = array();
foreach ($items as $p) {
    $productos[] = array(
        't'   => (string) $p['tienda'],
        'c'   => (string) $p['categoria'],
        'nom' => (string) $p['nombre'],
        'pre' => (int) $p['precio'],
        'dc'  => (string) $p['resumen'],
        'dl'  => (string) $p['descripcion'],
        'img' => (string) $p['imagen'],
        'det' => $p['detalles_arr'],
    );
}

$prods_json      = json_encode($productos, JSON_UNESCAPED_UNICODE);
$tiendas_json    = json_encode($tiendas, JSON_UNESCAPED_UNICODE);
$cat_labels_json = json_encode($cat_labels, JSON_UNESCAPED_UNICODE);

$primera_tienda = 'armeria';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tienda</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-tienda">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tramites.php">Trámites</a><span class="sep">›</span><b>Tienda</b></div></div>

<div class="wrap">

  <section class="reveal shop-wrap" id="shopApp">

    <div class="shop-layout">

      <aside class="shop-aside">
        <div class="shop-banner" id="shopBanner" data-tienda="<?php echo htmlspecialchars_uni($primera_tienda); ?>">
          <div class="shop-banner-media">
            <div class="shop-banner-bg" aria-hidden="true"></div>
            <img class="shop-banner-img" id="shopBannerImg" src="" alt="" loading="lazy">
          </div>
          <div class="shop-banner-text">
            <span class="shop-banner-kicker" id="shopTag">&nbsp;</span>
            <h1 class="shop-banner-nom" id="shopNom">&nbsp;</h1>
            <p class="shop-banner-lema" id="shopLema">&nbsp;</p>
          </div>
        </div>

        <div class="shop-tabs" id="shopTabs" role="tablist" aria-label="Secciones de la tienda">
<?php $first = true; foreach ($tiendas as $slug => $meta): ?>
          <button type="button" class="shop-tab<?php echo $first ? ' on' : ''; ?>" role="tab" data-tienda="<?php echo htmlspecialchars_uni($slug); ?>"><?php echo htmlspecialchars_uni($meta['nombre']); ?></button>
<?php $first = false; endforeach; ?>
        </div>
      </aside>

      <div class="shop-main">
        <div class="shop-main-head">
          <span class="shop-main-lbl">// catálogo de la sección</span>
          <button type="button" class="shop-cart-btn" id="shopCartBtn" onclick="toggleCart()" aria-label="Abrir carrito">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
            <span class="shop-cart-badge" id="shopCartBadge">0</span>
          </button>
        </div>
        <div class="shop-grid" id="shopGrid"></div>
      </div>

    </div>

  </section>

</div>

<div class="shop-modal" id="shopModal" hidden>
  <div class="shop-modal-bg" onclick="closeModal()"></div>
  <div class="shop-modal-box">
    <button class="shop-modal-x" onclick="closeModal()">✕</button>
    <div class="shop-modal-media" id="shopModalMedia"></div>
    <div class="shop-modal-info">
      <span class="shop-modal-cat" id="shopModalCat"></span>
      <h2 class="shop-modal-nom" id="shopModalNom"></h2>
      <span class="shop-modal-pre" id="shopModalPre"></span>
      <p class="shop-modal-desc" id="shopModalDesc"></p>
      <dl class="shop-modal-dets" id="shopModalDets"></dl>
      <div class="shop-modal-actions">
        <div class="shop-qty">
          <button type="button" onclick="qtyDelta(-1)">−</button>
          <input type="text" id="shopQty" value="1" readonly>
          <button type="button" onclick="qtyDelta(1)">+</button>
        </div>
        <button class="btn btn-hot" onclick="addToCart()">Añadir al carrito</button>
      </div>
    </div>
  </div>
</div>

<div class="shop-cart" id="shopCart" hidden>
  <div class="shop-cart-top">
    <span class="shop-cart-h">Carrito</span>
    <button class="shop-cart-x" onclick="toggleCart()">✕</button>
  </div>
  <div class="shop-cart-body" id="shopCartBody">
    <div class="shop-cart-empty">El carrito está vacío.</div>
  </div>
  <div class="shop-cart-foot">
    <span class="shop-cart-total" id="shopCartTotal">0 B</span>
    <button class="btn btn-hot" id="shopBuyBtn" disabled title="Próximamente">Comprar todo</button>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var prods = <?php echo $prods_json; ?>;
  var tiendas = <?php echo $tiendas_json; ?>;
  var catLabels = <?php echo $cat_labels_json; ?>;
  var BB = '<?php echo $bburl; ?>';
  var cart = [];
  var currentTienda = '<?php echo htmlspecialchars_uni($primera_tienda); ?>';
  var modalIdx = -1;

  var phSvg = '<span class="shop-ph" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M8 16l16-8 16 8-16 8-16-8z" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M8 16v16l16 8 16-8V16" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M24 24v16" stroke="currentColor" stroke-width="2.2"/></svg></span>';
  function esc(s){ return (s==null?'':String(s)).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  function media(p, cls){
    if (p.img) return '<img class="'+(cls||'')+'" src="'+esc(p.img)+'" alt="" loading="lazy" onerror="this.parentNode.classList.add(\'no-img\');this.outerHTML=\''+phSvg.replace(/'/g,"\\'")+'\'">';
    return phSvg;
  }

  function renderGrid() {
    var filtered = prods.filter(function(p){ return p.t === currentTienda; });
    if (!filtered.length){ document.getElementById('shopGrid').innerHTML = '<div class="shop-empty">No hay productos en esta tienda.</div>'; return; }
    document.getElementById('shopGrid').innerHTML = filtered.map(function(p){
      var realIdx = prods.indexOf(p);
      var catLb = catLabels[p.c] || p.c;
      return '<article class="shop-item" data-idx="'+realIdx+'" onclick="openModal('+realIdx+')">'
        + '<div class="shop-item-media">'+media(p)+'<span class="shop-item-cat">'+esc(catLb)+'</span></div>'
        + '<div class="shop-item-body">'
          + '<h3 class="shop-item-nom">'+esc(p.nom)+'</h3>'
          + '<p class="shop-item-dc">'+esc(p.dc)+'</p>'
          + '<div class="shop-item-foot"><span class="shop-item-pre">'+p.pre.toLocaleString('es-ES')+' B</span><span class="shop-item-cta">Ver</span></div>'
        + '</div></article>';
    }).join('');
  }

  function updateBanner(tiendaId) {
    var s = tiendas[tiendaId]; if(!s) return;
    var banner = document.getElementById('shopBanner');
    var img = document.getElementById('shopBannerImg');
    banner.setAttribute('data-tienda', tiendaId);
    document.getElementById('shopNom').textContent = s.nombre;
    document.getElementById('shopTag').textContent = s.tag;
    document.getElementById('shopLema').textContent = s.lema;
    if (s.imagen) {
      img.src = (s.imagen.indexOf('http') === 0 || s.imagen.indexOf('/') === 0) ? s.imagen : (BB + '/' + s.imagen);
      img.alt = s.nombre;
      banner.classList.remove('no-banner-img');
      img.onerror = function(){ banner.classList.add('no-banner-img'); };
    } else {
      img.removeAttribute('src');
      banner.classList.add('no-banner-img');
    }
  }

  window.openModal = function(idx) {
    modalIdx = idx;
    var p = prods[idx];
    document.getElementById('shopModalMedia').innerHTML = media(p, 'shop-modal-img');
    document.getElementById('shopModalNom').textContent = p.nom;
    document.getElementById('shopModalCat').textContent = catLabels[p.c] || p.c;
    document.getElementById('shopModalPre').textContent = p.pre.toLocaleString('es-ES') + ' B';
    document.getElementById('shopModalDesc').textContent = p.dl || p.dc;
    var detsHtml = '';
    (p.det||[]).forEach(function(d){
      var parts = String(d).split(': ');
      detsHtml += '<dt>' + esc(parts[0]) + '</dt><dd>' + esc(parts.slice(1).join(': ')) + '</dd>';
    });
    document.getElementById('shopModalDets').innerHTML = detsHtml;
    document.getElementById('shopQty').value = '1';
    document.getElementById('shopModal').removeAttribute('hidden');
    document.body.classList.add('shop-no-scroll');
  };
  window.closeModal = function() {
    document.getElementById('shopModal').setAttribute('hidden','');
    document.body.classList.remove('shop-no-scroll');
  };
  window.qtyDelta = function(d) {
    var inp = document.getElementById('shopQty');
    inp.value = Math.max(1, (parseInt(inp.value,10)||1) + d);
  };
  window.addToCart = function() {
    if (modalIdx < 0) return;
    var p = prods[modalIdx];
    var qty = parseInt(document.getElementById('shopQty').value, 10) || 1;
    var found = false;
    for (var i = 0; i < cart.length; i++) { if (cart[i].idx === modalIdx) { cart[i].qty += qty; found = true; break; } }
    if (!found) cart.push({idx: modalIdx, qty: qty});
    closeModal(); updateCartUI();
  };
  window.removeFromCart = function(idx) { cart = cart.filter(function(c){ return c.idx !== idx; }); updateCartUI(); };
  window.toggleCart = function() {
    var el = document.getElementById('shopCart');
    if (el.hasAttribute('hidden')) { el.removeAttribute('hidden'); document.body.classList.add('shop-no-scroll'); }
    else { el.setAttribute('hidden',''); document.body.classList.remove('shop-no-scroll'); }
  };

  function updateCartUI() {
    var badge = document.getElementById('shopCartBadge');
    var total = 0, count = 0;
    cart.forEach(function(c){ var p = prods[c.idx]; total += p.pre * c.qty; count += c.qty; });
    badge.textContent = count; badge.style.display = count > 0 ? 'inline' : 'none';
    var body = document.getElementById('shopCartBody');
    if (count === 0) { body.innerHTML = '<div class="shop-cart-empty">El carrito está vacío.</div>'; }
    else {
      body.innerHTML = cart.map(function(c){
        var p = prods[c.idx];
        return '<div class="shop-cart-item">'
          + '<div class="shop-cart-item-info"><div class="shop-cart-item-nom">'+esc(p.nom)+'</div>'
          + '<div class="shop-cart-item-meta">'+c.qty+' × '+p.pre.toLocaleString('es-ES')+' B</div></div>'
          + '<div class="shop-cart-item-sub">'+(p.pre*c.qty).toLocaleString('es-ES')+' B</div>'
          + '<button class="shop-cart-item-x" onclick="removeFromCart('+c.idx+')">✕</button></div>';
      }).join('');
    }
    document.getElementById('shopCartTotal').textContent = total.toLocaleString('es-ES') + ' B';
    document.getElementById('shopBuyBtn').disabled = count === 0;
  }

  document.getElementById('shopTabs').addEventListener('click', function(e){
    var tab = e.target.closest('.shop-tab');
    if (!tab || tab.classList.contains('on')) return;
    this.querySelectorAll('.shop-tab').forEach(function(t){ t.classList.remove('on'); });
    tab.classList.add('on');
    currentTienda = tab.getAttribute('data-tienda');
    updateBanner(currentTienda); renderGrid();
  });

  updateBanner(currentTienda); renderGrid(); updateCartUI();

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { closeModal(); if (!document.getElementById('shopCart').hasAttribute('hidden')) toggleCart(); }
  });
  document.getElementById('shopCart').addEventListener('click', function(e){ if (e.target === this) toggleCart(); });

  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .06 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
})();
</script>
</body>
</html>
