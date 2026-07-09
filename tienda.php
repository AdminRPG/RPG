<?php
/**
 * I-Forge · Tienda del foro
 * Compra objetos, consumibles y mejoras para tu personaje.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tienda.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);

$tiendas = [
    'armeria'   => ['nombre' => 'Armería',   'tag' => 'Armas y Armaduras'],
    'astilleros'=> ['nombre' => 'Astilleros', 'tag' => 'Barcos y Piezas'],
    'general'   => ['nombre' => 'General',    'tag' => 'Todo lo demás'],
];

$cat_labels = [
    'armas' => 'Armas', 'armaduras' => 'Armaduras', 'barcos' => 'Barcos',
    'piezas' => 'Piezas', 'consumibles' => 'Consumibles',
    'mejoras' => 'Mejoras', 'especiales' => 'Especiales',
];

$productos = [
    ['t'=>'armeria','c'=>'armas','nom'=>'Espada de acero','pre'=>45000,'dc'=>'Espada recta de acero forjado.','dl'=>'Forjada en los hornos de Sunacma por el herrero Dorn. Acero al carbono con filo tratado. No es legendaria, pero jamás te fallará.','det'=>['Daño base: 1d8','Peso: 2.5 kg','Tipo: Corte','Durabilidad: 60/60']],
    ['t'=>'armeria','c'=>'armas','nom'=>'Katana oscura','pre'=>120000,'dc'=>'Hoja negra templada en magma.','dl'=>'Forjada en las profundidades del Volcán de la Luna. Su filo absorbe calor durante el temple, dándole un brillo cobrizo.','det'=>['Daño base: 2d6','Peso: 1.8 kg','Tipo: Corte','Durabilidad: 45/45','Req. Nivel: 15']],
    ['t'=>'armeria','c'=>'armas','nom'=>'Hacha de guerra','pre'=>78000,'dc'=>'Bipenne tallada en hierro del Norte.','dl'=>'Dos filos tallados a mano que permiten alternar el golpe sin perder equilibrio. Peso descomunal, lenta pero devastadora.','det'=>['Daño base: 1d12','Peso: 4.2 kg','Tipo: Contundente','Durabilidad: 70/70','Requiere 2 manos']],
    ['t'=>'armeria','c'=>'armas','nom'=>'Pistola de chispa','pre'=>55000,'dc'=>'Arma de fuego de un cañón.','dl'=>'Réplica de los diseños del Oeste. Cañón de bronce con mecanismo de rueda. Recargar es lento, el fogonazo inicial lo vale.','det'=>['Daño base: 1d10','Alcance: 20 m','Recarga: 2 turnos','Munición: Balas de plomo']],
    ['t'=>'armeria','c'=>'armas','nom'=>'Bastón de hierro','pre'=>32000,'dc'=>'Barra de hierro macizo. Contundente.','dl'=>'Hierro negro sin adornos. Arma de dotación Marine. Sin filo, pero con fuerza suficiente parte cualquier defensa de madera.','det'=>['Daño base: 1d6','Peso: 3.0 kg','Tipo: Contundente','Durabilidad: 80/80']],
    ['t'=>'armeria','c'=>'armaduras','nom'=>'Pechera de cuero','pre'=>38000,'dc'=>'Armadura ligera de cuero endurecido.','dl'=>'Coraza de cuero hervido y endurecido al sol. Flexible, ligera, sorprendentemente resistente. Ideal para exploradores.','det'=>['Defensa: +2','Peso: 1.5 kg','Tipo: Ligera','Penalización: -']],
    ['t'=>'armeria','c'=>'armaduras','nom'=>'Cota de malla','pre'=>95000,'dc'=>'Anillos de acero entrelazados.','dl'=>'Cota forjada anillo a anillo, entrelazados y remachados. El estándar de Marines y cazarecompensas profesionales.','det'=>['Defensa: +4','Peso: 8.0 kg','Tipo: Media','Penalización: -1 AGI']],
    ['t'=>'armeria','c'=>'armaduras','nom'=>'Escudo de roble','pre'=>28000,'dc'=>'Madera reforzada con flejes de hierro.','dl'=>'Escudo redondo de roble macizo con refuerzos de hierro y umbo central de acero. Efectivo contra ataques cuerpo a cuerpo.','det'=>['Defensa: +2 al bloquear','Peso: 3.5 kg','Cobertura: 1/4 cuerpo','Durabilidad: 50/50']],
    ['t'=>'astilleros','c'=>'barcos','nom'=>'Bote a vela','pre'=>250000,'dc'=>'Embarcación para 2-3 personas.','dl'=>'Bote de pino con vela cuadrada. Sin cubierta ni camarote. Suficiente para navegar entre islas con buen tiempo.','det'=>['Tripulación: 2-3','Velocidad: 6 nudos','Autonomía: 3 días','Bodega: 500 kg']],
    ['t'=>'astilleros','c'=>'barcos','nom'=>'Goleta ligera','pre'=>800000,'dc'=>'Dos palos, rápida y maniobrable.','dl'=>'Diseño ágil con velas cangrejas. Cubierta corrida y camarote. Popular entre corsarios por equilibrio velocidad/carga.','det'=>['Tripulación: 6-10','Velocidad: 9 nudos','Autonomía: 10 días','Bodega: 5 tn','Cañones: 4']],
    ['t'=>'astilleros','c'=>'piezas','nom'=>'Timón reforzado','pre'=>45000,'dc'=>'Hierro y roble. Resiste tormentas.','dl'=>'Fabricado por carpinteros de Water Seven. Eje de hierro forjado y pala de roble encapado. Soporta embestidas.','det'=>['Resistencia: +40 %','Peso: 120 kg','Compatibilidad: Goletas']],
    ['t'=>'astilleros','c'=>'piezas','nom'=>'Velas de repuesto','pre'=>22000,'dc'=>'Juego de 3 velas de lona.','dl'=>'Cosidas a mano con hilo de cáñamo encerado. Incluyen cabos y ojetes de bronce. 6×4 metros cada una.','det'=>['Cantidad: 3','Material: Lona encerada','Tamaño: 6×4 m']],
    ['t'=>'general','c'=>'consumibles','nom'=>'Poción de vida','pre'=>8000,'dc'=>'Recupera 50 HP al instante.','dl'=>'Infusión de hierbas de la Isa Toroa. Brebaje denso y amargo que cierra heridas superficiales en segundos.','det'=>['Curación: 50 HP','Uso: 1 acción','Stock máx: 5/mes']],
    ['t'=>'general','c'=>'consumibles','nom'=>'Elixir de energía','pre'=>15000,'dc'=>'Ignora la fatiga 24 h.','dl'=>'Destilado de fruta del diablo fragmentada (variedad inerte). Vigilia que permite ignorar la fatiga durante 24 h.','det'=>['Duración: 24 h','Efecto: Ignorar fatiga','Stock máx: 3/mes']],
    ['t'=>'general','c'=>'consumibles','nom'=>'Antídoto universal','pre'=>6000,'dc'=>'Neutraliza venenos comunes.','dl'=>'Suero preparado por la rama médica Marine. Neutraliza venenos de origen animal y vegetal del East Blue.','det'=>['Cobertura: Venenos comunes','Efectividad: 95 %','Uso: Ingerir']],
    ['t'=>'general','c'=>'consumibles','nom'=>'Bomba de humo','pre'=>4000,'dc'=>'Cortina de humo táctico.','dl'=>'Esfera de hierro con pólvora y resina. Genera nube de humo de 6m de radio durante 1d4 turnos.','det'=>['Radio: 6 m','Duración: 1d4 turnos','Uso: 1 acción menor']],
    ['t'=>'general','c'=>'mejoras','nom'=>'Gema de fuerza','pre'=>200000,'dc'=>'+10 % daño físico.','dl'=>'Cuarzo imbuido con energía de fruta del diablo. Al engarzarla en un arma, el filo brilla y los golpes duelen más.','det'=>['Efecto: +10 % daño físico','Tipo: Encantamiento','Duración: Permanente']],
    ['t'=>'general','c'=>'mejoras','nom'=>'Pergamino blindaje','pre'=>180000,'dc'=>'+1 defensa permanente.','dl'=>'Pergamino de piel de dragón marino con runas. Al leerlo, la armadura del portador brilla y su resistencia aumenta.','det'=>['Efecto: +1 defensa','Tipo: Mejora de armadura','Duración: Permanente']],
    ['t'=>'general','c'=>'mejoras','nom'=>'Manual combate','pre'=>65000,'dc'=>'+1 al primer ataque.','dl'=>'Diagramas de llaves, derribos y contraataques de un instructor Marine. Requiere una semana de estudio.','det'=>['Efecto: +1 primer ataque/combate','Estudio: 7 días','Uso: Una vez']],
    ['t'=>'general','c'=>'especiales','nom'=>'Mapa del tesoro','pre'=>250000,'dc'=>'Coordenadas de un tesoro.','dl'=>'Pergamino envejecido con marcas de una cala secreta. La tinta ha corrido. El tesoro puede ser real o una broma.','det'=>['Tipo: Tesoro enterrado','Dificultad: Media','Garantía: Ninguna']],
    ['t'=>'general','c'=>'especiales','nom'=>'Licencia de caza','pre'=>150000,'dc'=>'Cazar recompensas legal.','dl'=>'Documento sellado por la sede Marine de Loguetown. Sin este permiso, cobrar recompensas en East Blue es ilegal.','det'=>['Jurisdicción: East Blue','Duración: 1 año','Renovable: Sí']],
];

// Placeholder SVG (generic item box)
function placeholder_svg($size = 40) {
    return '<svg viewBox="0 0 100 100" width="'.$size.'" height="'.$size.'"><rect x="15" y="15" width="70" height="70" rx="6" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="50" cy="42" r="12" fill="none" stroke="currentColor" stroke-width="3"/><path d="M22 80c0-16 12-28 28-28s28 12 28 28" fill="none" stroke="currentColor" stroke-width="3"/></svg>';
}

$prods_json = json_encode(array_map(function($p) {
    return ['t'=>$p['t'],'c'=>$p['c'],'nom'=>$p['nom'],'pre'=>$p['pre'],'dc'=>$p['dc'],'dl'=>$p['dl'],'det'=>$p['det']??[]];
}, $productos), JSON_UNESCAPED_UNICODE);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tienda</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tienda">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a><span class="sep">›</span>
    <b>Tienda</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Tienda</h1>
      <span class="code">// equipa a tu personaje</span>
      <span class="rule"></span>
    </div>
  </section>

  <section class="reveal ts-shop" id="tsApp">

    <!-- LEFT: storefront image -->
    <aside class="ts-front" id="tsFront" data-tienda="armeria">
      <div class="ts-front-bg"></div>
      <div class="ts-front-in">
        <div class="ts-front-picto" id="tsFrontPicto"><?php echo placeholder_svg(72); ?></div>
        <div class="ts-front-nom" id="tsFrontNom">Armería</div>
        <div class="ts-front-tag" id="tsFrontTag">Armas y Armaduras</div>
      </div>
    </aside>

    <!-- RIGHT: products + cart -->
    <div class="ts-right">

      <!-- top bar: tabs + cart -->
      <div class="ts-topbar">
        <div class="ts-tabs" id="tsTabs">
<?php $first_t = true; foreach ($tiendas as $tid => $t): ?>
          <button class="ts-tab<?php if ($first_t) echo ' on'; ?>" data-tienda="<?php echo $tid; ?>"><?php echo $t['nombre']; ?></button>
<?php $first_t = false; endforeach; ?>
        </div>
        <button class="ts-cart-btn" id="tsCartBtn" onclick="toggleCart()">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
          <span class="ts-cart-badge" id="tsCartBadge">0</span>
        </button>
      </div>

      <!-- product grid (3 cols) -->
      <div class="ts-grid" id="tsGrid"></div>
    </div>
  </section>

  <section class="reveal">
    <div class="plate plate-warn">
      <div class="plate-b ti-notice">Las imágenes reales se añadirán cuando el equipo de arte defina los assets. Precios ilustrativos. Sistema de compra próximo.</div>
    </div>
  </section>

</div>

<!-- Product detail modal -->
<div class="ts-modal" id="tsModal" hidden>
  <div class="ts-modal-bg" onclick="closeModal()"></div>
  <div class="ts-modal-box">
    <button class="ts-modal-x" onclick="closeModal()">✕</button>
    <div class="ts-modal-icon" id="tsModalIcon"></div>
    <h2 class="ts-modal-nom" id="tsModalNom"></h2>
    <span class="ts-modal-pre" id="tsModalPre"></span>
    <p class="ts-modal-desc" id="tsModalDesc"></p>
    <dl class="ts-modal-dets" id="tsModalDets"></dl>
    <div class="ts-modal-actions">
      <div class="ts-qty">
        <button type="button" onclick="qtyDelta(-1)">−</button>
        <input type="text" id="tsQty" value="1" readonly>
        <button type="button" onclick="qtyDelta(1)">+</button>
      </div>
      <button class="btn btn-hot" onclick="addToCart()">Añadir al carrito</button>
    </div>
  </div>
</div>

<!-- Cart slide-out -->
<div class="ts-cart" id="tsCart" hidden>
  <div class="ts-cart-top">
    <span class="ts-cart-h">Carrito</span>
    <button class="ts-cart-x" onclick="toggleCart()">✕</button>
  </div>
  <div class="ts-cart-body" id="tsCartBody">
    <div class="ts-cart-empty">El carrito está vacío.</div>
  </div>
  <div class="ts-cart-foot">
    <span class="ts-cart-total" id="tsCartTotal">0 B</span>
    <button class="btn btn-hot" id="tsBuyBtn" disabled title="Próximamente">Comprar todo</button>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var prods = <?php echo $prods_json; ?>;
  var cart = [];
  var currentTienda = 'armeria';
  var modalIdx = -1;

  // Placeholder SVG icon
  var phSvg = '<svg viewBox="0 0 100 100"><rect x="15" y="15" width="70" height="70" rx="6" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="50" cy="42" r="12" fill="none" stroke="currentColor" stroke-width="3"/><path d="M22 80c0-16 12-28 28-28s28 12 28 28" fill="none" stroke="currentColor" stroke-width="3"/></svg>';
  function ph(w){ return '<svg viewBox="0 0 100 100" width="'+w+'" height="'+w+'"><rect x="15" y="15" width="70" height="70" rx="6" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="50" cy="42" r="12" fill="none" stroke="currentColor" stroke-width="3"/><path d="M22 80c0-16 12-28 28-28s28 12 28 28" fill="none" stroke="currentColor" stroke-width="3"/></svg>'; }

  // ── Render grid ──
  function renderGrid() {
    var filtered = prods.filter(function(p){ return p.t === currentTienda; });
    var html = '';
    filtered.forEach(function(p, i){
      html += '<div class="ts-item" data-idx="' + i + '" onclick="openModal(' + i + ')">' +
        '<div class="ts-item-icon">' + ph(40) + '</div>' +
        '<div class="ts-item-nom">' + esc(p.nom) + '</div>' +
      '</div>';
    });
    document.getElementById('tsGrid').innerHTML = html || '<div class="ts-empty">No hay productos en esta tienda.</div>';
  }

  function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  // ── Modal ──
  window.openModal = function(idx) {
    modalIdx = idx;
    var p = prods[idx];
    document.getElementById('tsModalIcon').innerHTML = '<div class="ts-modal-icon-in">' + ph(100) + '</div>';
    document.getElementById('tsModalNom').textContent = p.nom;
    document.getElementById('tsModalPre').textContent = p.pre.toLocaleString('es-ES') + ' B';
    document.getElementById('tsModalDesc').textContent = p.dl;
    var detsHtml = '';
    (p.det||[]).forEach(function(d){
      var parts = d.split(': ');
      detsHtml += '<dt>' + esc(parts[0]) + '</dt><dd>' + esc(parts.slice(1).join(': ')) + '</dd>';
    });
    document.getElementById('tsModalDets').innerHTML = detsHtml;
    document.getElementById('tsQty').value = '1';
    document.getElementById('tsModal').removeAttribute('hidden');
    document.body.classList.add('ts-no-scroll');
  };

  window.closeModal = function() {
    document.getElementById('tsModal').setAttribute('hidden','');
    document.body.classList.remove('ts-no-scroll');
  };

  window.qtyDelta = function(d) {
    var inp = document.getElementById('tsQty');
    var v = Math.max(1, (parseInt(inp.value,10)||1) + d);
    inp.value = v;
  };

  // ── Cart ──
  window.addToCart = function() {
    if (modalIdx < 0) return;
    var p = prods[modalIdx];
    var qty = parseInt(document.getElementById('tsQty').value, 10) || 1;
    // Find existing
    var found = false;
    for (var i = 0; i < cart.length; i++) {
      if (cart[i].idx === modalIdx) { cart[i].qty += qty; found = true; break; }
    }
    if (!found) cart.push({idx: modalIdx, qty: qty, nom: p.nom, pre: p.pre});
    closeModal();
    updateCartUI();
  };

  window.removeFromCart = function(idx) {
    cart = cart.filter(function(c){ return c.idx !== idx; });
    updateCartUI();
  };

  window.toggleCart = function() {
    var el = document.getElementById('tsCart');
    if (el.hasAttribute('hidden')) {
      el.removeAttribute('hidden');
      document.body.classList.add('ts-no-scroll');
    } else {
      el.setAttribute('hidden','');
      document.body.classList.remove('ts-no-scroll');
    }
  };

  function updateCartUI() {
    var badge = document.getElementById('tsCartBadge');
    var total = 0;
    var count = 0;
    cart.forEach(function(c){ total += c.pre * c.qty; count += c.qty; });
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline' : 'none';

    var body = document.getElementById('tsCartBody');
    if (count === 0) {
      body.innerHTML = '<div class="ts-cart-empty">El carrito está vacío.</div>';
    } else {
      var html = '';
      cart.forEach(function(c){
        var p = prods[c.idx];
        html += '<div class="ts-cart-item">' +
          '<div class="ts-cart-item-icon">' + ph(28) + '</div>' +
          '<div class="ts-cart-item-info">' +
            '<div class="ts-cart-item-nom">' + esc(p.nom) + '</div>' +
            '<div class="ts-cart-item-meta">' + c.qty + ' × ' + p.pre.toLocaleString('es-ES') + ' B</div>' +
          '</div>' +
          '<div class="ts-cart-item-sub">' + (p.pre * c.qty).toLocaleString('es-ES') + ' B</div>' +
          '<button class="ts-cart-item-x" onclick="removeFromCart(' + c.idx + ')">✕</button>' +
        '</div>';
      });
      body.innerHTML = html;
    }

    document.getElementById('tsCartTotal').textContent = total.toLocaleString('es-ES') + ' B';
    document.getElementById('tsBuyBtn').disabled = count === 0;
  }

  // ── Store tabs ──
  document.getElementById('tsTabs').addEventListener('click', function(e){
    var tab = e.target.closest('.ts-tab');
    if (!tab || tab.classList.contains('on')) return;
    document.querySelectorAll('.ts-tab').forEach(function(t){ t.classList.remove('on'); });
    tab.classList.add('on');
    currentTienda = tab.getAttribute('data-tienda');
    // Update storefront
    var front = document.getElementById('tsFront');
    front.setAttribute('data-tienda', currentTienda);
    var storeData = <?php echo json_encode($tiendas, JSON_UNESCAPED_UNICODE); ?>;
    var s = storeData[currentTienda];
    document.getElementById('tsFrontNom').textContent = s.nombre;
    document.getElementById('tsFrontTag').textContent = s.tag;
    document.getElementById('tsFrontPicto').innerHTML = '<?php echo placeholder_svg(72); ?>';
    renderGrid();
  });

  // ── Init ──
  renderGrid();
  updateCartUI();

  // Close modal on Escape
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') { closeModal(); if (!document.getElementById('tsCart').hasAttribute('hidden')) toggleCart(); }
  });

  // Click outside cart to close
  document.getElementById('tsCart').addEventListener('click', function(e){
    if (e.target === this) toggleCart();
  });

  // Reveal
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }); }, { threshold: .06 });
    document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
  } else { document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); }); }
})();
</script>
</body>
</html>
