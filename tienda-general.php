<?php
/**
 * One Piece: 7 Seas · Tienda General (F3.3)
 * ------------------------------------------
 * Tienda NPC (10.5): vende del catálogo validado al precio de mercado de la
 * zona (sin banda de margen) y compra al 50 %. Paga con la cartera; los
 * objetos van al almacén. Las armas de grado (Wazamono+) no se venden aquí.
 * Scope CSS: body.ope-pg-tienda-general.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tienda-general.php');
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
if ($mybb->request_method === 'post') {
    $accion = (string) $mybb->get_input('accion');
    $objeto_id = (int) $mybb->get_input('objeto_id', MyBB::INPUT_INT);
    $cantidad = max(1, (int) $mybb->get_input('cantidad', MyBB::INPUT_INT));
    if ($pid < 1) {
        $flash = '<div class="flash warn">Necesitas un personaje activo aprobado para comprar.</div>';
    } elseif ($accion === 'comprar') {
        $r = ope7_tienda_compra_npc($pid, $objeto_id, $cantidad);
        $flash = '<div class="flash ' . ($r['ok'] ? 'ok' : 'warn') . '">' . htmlspecialchars_uni($r['msg']) . '</div>';
    } elseif ($accion === 'vender') {
        $r = ope7_tienda_venta_npc($pid, $objeto_id, $cantidad);
        $flash = '<div class="flash ' . ($r['ok'] ? 'ok' : 'warn') . '">' . htmlspecialchars_uni($r['msg']) . '</div>';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tienda General</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tienda-general">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/tiendas.php">Tiendas</a><span class="sep">›</span><b>Tienda General</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Tienda General</h1>
      <span class="code">// mercado de la zona · 10.5</span>
      <span class="rule"></span>
    </div>
    <p class="tienda-intro">La tienda NPC vende al precio de mercado de la zona y compra tu botín al 50 %. Los objetos entran en tu almacén (seguro); pagas con la cartera.</p>
    <?php
    if ($flash !== '') {
        echo $flash;
    }
    $inv = $pid > 0 ? ope7_inventario_resumen($pid) : array('cartera' => array('cartera' => 0, 'boveda' => 0), 'almacen' => array());
    echo '<div class="f7-sec">'
        . '<div class="f7-sec-v">' . (int) ($inv['cartera']['cartera'] ?? 0) . ' <span>฿ cartera</span></div>'
        . '<div class="f7-sec-v">' . (int) ($inv['cartera']['boveda'] ?? 0) . ' <span>฿ bóveda</span></div>'
        . '</div>';
    ?>
  </section>

  <section class="reveal">
    <div class="plate"><div class="plate-h"><span class="t">Catálogo de la tienda</span><span class="c">compra máxima: calidad Superior</span></div><div class="plate-b">
    <?php
    global $db;
    $q = $db->query('SELECT * FROM ' . ope7_tabla_full('objetos') . ' WHERE activo = 1 AND (categoria = "arma" OR categoria = "consumible" OR categoria = "herramienta" OR categoria = "dial") AND calidad IN ("inferior","comun","superior") OR (rareza IS NOT NULL AND rareza != "mercado_negro") ORDER BY categoria, calidad');
    $n = 0;
    while ($o = $db->fetch_array($q)) {
        $precio = ope7_precio_mercado((int) $o['id']);
        $n++;
        echo '<div class="tienda-item f7-row">'
            . '<div class="tienda-item-info"><h4>' . htmlspecialchars_uni((string) $o['nombre']) . '</h4>'
            . '<span class="tienda-item-meta">' . htmlspecialchars_uni((string) $o['categoria']) . (($o['calidad'] !== null && $o['calidad'] !== '') ? ' · ' . htmlspecialchars_uni((string) $o['calidad']) : '') . (($o['coste_pa'] ?? '') !== '' ? ' · PA ' . htmlspecialchars_uni((string) $o['coste_pa']) : '') . '</span></div>'
            . '<div class="tienda-item-price">' . (int) $precio . ' ฿</div>'
            . ($pid > 0
                ? '<form method="post" action="' . $bburl . '/tienda-general.php" class="tienda-buy">'
                    . '<input type="hidden" name="accion" value="comprar">'
                    . '<input type="hidden" name="objeto_id" value="' . (int) $o['id'] . '">'
                    . '<input type="number" name="cantidad" value="1" min="1" max="10" class="tienda-qty">'
                    . '<button type="submit" class="btn">Comprar</button>'
                  . '</form>'
                : '<span class="tienda-item-meta">Crea y aprueba tu personaje para comprar.</span>')
            . '</div>';
    }
    if ($n === 0) {
        echo '<div class="f7-empty">Catálogo vacío — ejecuta php scripts/seed-7seas-progresion.php.</div>';
    }
    ?>
    </div></div>
  </section>

  <section class="reveal">
    <div class="plate"><div class="plate-h"><span class="t">Vender al tendero</span><span class="c">compra al 50 % del mercado</span></div><div class="plate-b">
    <?php
    if (!$inv['almacen']) {
        echo '<div class="f7-empty">Tu almacén está vacío.</div>';
    }
    foreach ($inv['almacen'] as $a) {
        if (in_array((string) $a['categoria'], array('arma', 'armadura', 'escudo'), true) && in_array((string) $a['calidad'], array('wazamono', 'ryo', 'o', 'saijo'), true)) {
            continue; // las de grado no se venden en tiendas
        }
        $p50 = (int) round(ope7_precio_mercado((int) $a['id']) * 0.5);
        echo '<div class="tienda-item f7-row">'
            . '<div class="tienda-item-info"><h4>' . htmlspecialchars_uni((string) $a['nombre']) . '</h4>'
            . '<span class="tienda-item-meta">×' . (int) $a['cantidad'] . ' en almacén · vende a ' . $p50 . ' ฿/ud.</span></div>'
            . ($pid > 0
                ? '<form method="post" action="' . $bburl . '/tienda-general.php" class="tienda-buy">'
                    . '<input type="hidden" name="accion" value="vender">'
                    . '<input type="hidden" name="objeto_id" value="' . (int) $a['id'] . '">'
                    . '<input type="number" name="cantidad" value="1" min="1" max="' . (int) $a['cantidad'] . '" class="tienda-qty">'
                    . '<button type="submit" class="btn">Vender</button>'
                  . '</form>'
                : '')
            . '</div>';
    }
    ?>
    </div></div>
  </section>
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
