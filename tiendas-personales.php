<?php
/**
 * One Piece: 7 Seas · Tiendas Personales (F3.3)
 * ----------------------------------------------
 * Lista las tiendas del personaje activo (estado, ítems, stock) y ofrece
 * abrir una nueva (trámite 15 — Comerciante + local + capital + bélicos del
 * catálogo; el staff valida). Cierre/reapertura por el trámite 16.
 * Scope CSS: body.ope-pg-tienda-personal.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tiendas-personales.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
$activo = $uid > 0 ? ope7_pj_activo($uid) : null;
$pid = (int) ($activo['id'] ?? 0);

$flash = '';
if ($mybb->request_method === 'post' && $pid > 0) {
    $accion = (string) $mybb->get_input('accion');
    if ($accion === 'abrir') {
        $tipo = (string) $mybb->get_input('tipo') === 'reventa' ? 'reventa' : 'oficio';
        $local = trim((string) $mybb->get_input('local'));
        $margen = (float) $mybb->get_input('margen');
        $res = array(
            'tipo' => $tipo, 'local' => $local, 'margen' => $margen,
            'isla_id' => (int) $mybb->get_input('isla_id', MyBB::INPUT_INT),
            'capital' => (int) $mybb->get_input('capital', MyBB::INPUT_INT),
            'items' => array(),
        );
        $oids = (array) $mybb->get_input('objeto_id', MyBB::INPUT_ARRAY);
        $stocks = (array) $mybb->get_input('stock', MyBB::INPUT_ARRAY);
        foreach ($oids as $i => $oid) {
            $res['items'][] = array('objeto_id' => (int) $oid, 'stock' => max(1, (int) ($stocks[$i] ?? 1)));
        }
        $tr = ope7_tramite_crear($uid, $pid, 15, 'Apertura de tienda (' . $tipo . ') en ' . ($local !== '' ? $local : '(sin local)'), array(), $res);
        if ($tr['ok']) {
            $flash = '<div class="flash ok">Trámite 15 enviado a la bandeja del staff: apertura de tienda.</div>';
        } else {
            $flash = '<div class="flash warn">' . htmlspecialchars_uni($tr['msg']) . '</div>';
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tiendas Personales</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tienda-personal">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tiendas.php">Tiendas</a><span class="sep">›</span><b>Tiendas Personales</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tiendas Personales</h1>
      <span class="code">// tu negocio en el mar · 10.6</span>
      <span class="rule"></span>
    </div>
    <p class="tienda-intro">Tu tienda vende <b>desde tu almacén</b> (stock real), con margen dentro de la banda −20 %/+30 % sobre el mercado de la zona. Requisitos duros: <b>Comerciante</b> (nv1 estándar · nv3+ reventa/Mercader/Tasador) + local (puesto, territorio o módulo de barco) + capital.</p>
    <?php
    if ($flash !== '') {
        echo $flash;
    }
    if ($pid < 1) {
        echo '<div class="plate"><div class="plate-b"><p class="f7-empty">Necesitas un personaje activo aprobado.</p></div></div>';
    }
    ?>
  </section>

  <?php if ($pid > 0): ?>
  <section class="reveal">
    <div class="plate"><div class="plate-h"><span class="t">Tus tiendas</span><span class="c">estado y surtido</span></div><div class="plate-b">
    <?php
    global $db;
    $tq = $db->simple_select('ope_tiendas', '*', "dueno_id = {$pid} ORDER BY id DESC");
    $n_tiendas = 0;
    while ($t = $db->fetch_array($tq)) {
        $n_tiendas++;
        echo '<div class="tienda-item">'
            . '<div class="tienda-item-info"><h4>Tienda #' . (int) $t['id'] . ' — ' . htmlspecialchars_uni((string) $t['tipo']) . '</h4>'
            . '<span class="tienda-item-meta">' . htmlspecialchars_uni((string) $t['local']) . ' · margen ' . htmlspecialchars_uni((string) $t['banda_margen']) . ' · estado ' . htmlspecialchars_uni((string) $t['estado']) . '</span></div>'
            . '<span class="tienda-item-price">' . (string) $t['estado'] . '</span></div>';
        $iq = $db->query('SELECT ti.stock, ti.precio_venta, o.nombre FROM ' . ope7_tabla_full('tienda_items') . ' ti '
            . 'JOIN ' . ope7_tabla_full('objetos') . ' o ON o.id = ti.objeto_id WHERE ti.tienda_id = ' . (int) $t['id'] . ' ORDER BY o.nombre');
        while ($it = $db->fetch_array($iq)) {
            echo '<div class="tienda-item tienda-item--sub">'
                . '<div class="tienda-item-info"><h4>' . htmlspecialchars_uni((string) $it['nombre']) . '</h4></div>'
                . '<span class="tienda-item-meta">×' . (int) $it['stock'] . ' · ' . (int) $it['precio_venta'] . ' ฿</span></div>';
        }
    }
    if ($n_tiendas === 0) {
        echo '<div class="f7-empty">Aún no tienes tiendas — abre la primera con el formulario de abajo (trámite 15).</div>';
    }
    ?>
    </div></div>
  </section>

  <section class="reveal">
    <div class="plate"><div class="plate-h"><span class="t">Abrir tienda</span><span class="c">trámite 15 · validación del staff</span></div><div class="plate-b">
    <?php
    $nv_com = ope7_pj_dominio_nivel($pid, 'Comerciante');
    if ($nv_com < 1) {
        echo '<div class="flash warn">No tienes el oficio <b>Comerciante</b> — requisito duro para abrir tienda (10.6). Adquiérelo por el trámite 4 (dominios) o por hito.</div>';
    } else {
        echo '<div class="flash ok">Comerciante nv' . $nv_com . ' detectado' . ($nv_com >= 3 ? ' — habilitas Mercader/Tasador (nv3+).' : ' — tienda estándar y reventa básica.') . '</div>';
    }
    ?>
    <form method="post" action="<?php echo $bburl; ?>/tiendas-personales.php" class="tienda-form">
      <input type="hidden" name="accion" value="abrir">
      <div class="tienda-form-row">
        <label>Tipo</label>
        <select name="tipo" class="zs-control">
          <option value="oficio">Oficio (vendes lo que produces)</option>
          <option value="reventa" <?php echo $nv_com < 3 ? 'disabled' : ''; ?>>Reventa pura (Comerciante nv3+)</option>
        </select>
      </div>
      <div class="tienda-form-row">
        <label>Isla (tu local está en su territorio)</label>
        <select name="isla_id" class="zs-control" required>
          <option value="">Elige la isla del catálogo…</option>
          <?php
          if (function_exists('ope7_islas_lista')) {
              foreach (ope7_islas_lista() as $isl) {
                  echo '<option value="' . (int) $isl['id'] . '">' . htmlspecialchars_uni((string) $isl['nombre']) . ' (' . htmlspecialchars_uni((string) $isl['mar_nombre']) . ')</option>';
              }
          }
          ?>
        </select>
      </div>
      <div class="tienda-form-row">
        <label>Local</label>
        <input type="text" name="local" class="zs-control" placeholder="Puesto en la isla, local en territorio o barco con módulo de tienda" required>
      </div>
      <div class="tienda-form-row">
        <label>Margen (banda −20 %…+30 %)</label>
        <input type="number" name="margen" class="zs-control" step="0.05" min="-0.20" max="0.30" value="0.00" required>
      </div>
      <div class="tienda-form-row">
        <label>Capital (฿ en cartera)</label>
        <input type="number" name="capital" class="zs-control" min="0" value="0" required>
      </div>
      <div class="tienda-form-row">
        <label>Ítems (objeto y stock; salen de tu almacén)</label>
        <select name="objeto_id[]" class="zs-control">
          <?php
          $oq = $db->simple_select('ope_objetos', 'id, nombre, categoria, calidad', "activo = 1 AND (categoria = 'arma' OR categoria = 'consumible' OR categoria = 'herramienta') ORDER BY nombre");
          while ($o = $db->fetch_array($oq)) {
              echo '<option value="' . (int) $o['id'] . '">' . htmlspecialchars_uni((string) $o['nombre']) . '</option>';
          }
          ?>
        </select>
        <input type="number" name="stock[]" class="zs-control tienda-qty" min="1" max="10" value="1">
      </div>
      <button type="submit" class="btn">Enviar apertura (trámite 15)</button>
    </form>
    </div></div>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
