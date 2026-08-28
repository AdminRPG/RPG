<?php
/**
 * One Piece: 7 Seas · Bajo Mundo (zona staff — Anexo A.3, F6)
 * -----------------------------------------------------------
 * Panel «Bajo Mundo» (5.13/cap. 14): rumores activos por isla (ficha de 5
 * campos con veracidad solo-staff), redes y espías (capacidad, mantenimiento),
 * carteles de recompensa (vigentes, caducidad de paradero a 3 rondas, cobros)
 * y el histórico auditable de `rumor_operaciones`. Acción de escritura:
 * crear el trámite 30 (publicar cartel) que se firma en la bandeja.
 * Scope CSS: body.ope-pg-bajomundo-staff.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'bajomundo-staff.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl = htmlspecialchars_uni($mybb->settings['bburl']);
$uid   = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/member.php?action=login');
    exit;
}
if (!function_exists('ope7_es_staff') || !ope7_es_staff($uid)) {
    error('No tienes permisos para acceder al panel del Bajo Mundo.');
}

$flash = '';
if ($mybb->request_method === 'post' && verify_post_check($mybb->get_input('my_post_key'))) {
    $gaccion = (string) $mybb->get_input('gaccion');
    if ($gaccion === 'publicar_cartel') {
        // Trámite 30 (staff + firma): el staff fija cifra/paradero; la firma
        // aplica la emisión con caducidad a 3 rondas y el histórico.
        $pid_buscado = (int) $mybb->get_input('personaje_id', 1);
        $cifra = (int) $mybb->get_input('cifra', 1);
        $paradero = trim((string) $mybb->get_input('paradero'));
        if ($pid_buscado < 1 || $cifra < 100000 || $paradero === '') {
            $flash = 'Cartel incompleto: buscado, cifra (desde 100.000 ฿) y paradero publicado.';
        } else {
            $r = ope7_tramite_crear($uid, $pid_buscado, 30, 'Emisión de cartel desde el panel',
                array('personaje_id' => $pid_buscado, 'cifra' => $cifra, 'paradero' => $paradero),
                array('cifra' => $cifra, 'paradero_publicado' => $paradero));
            $flash = $r['ok'] ? 'Trámite 30 creado: revisa la ficha (fiabilidad del paradero) y firma la emisión en la bandeja.' : $r['msg'];
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
<title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · Bajo Mundo</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-bajomundo-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona staff</a><span class="sep">›</span>
  <b>Bajo Mundo</b>
</div></div>
<div class="wrap">
<?php if ($flash !== '') { ?><div class="flash"><?php echo htmlspecialchars_uni($flash); ?></div><?php } ?>
<?php echo ope7_bajomundo_panel_html(); ?>

<div class="plate"><div class="plate-h"><span class="t">Publicar cartel</span><span class="c">trámite 30 · 14.6 — cifra, paradero y caducidad a 3 rondas</span></div><div class="plate-b">
  <p class="zs-intro">Los carteles los propone `skill-mundo-vivo` a partir del Wanted (5.12) y los sucesos de la ronda; tú fijas la cifra (escala 5.9), el paradero publicado con su fiabilidad y el nivel aproximado. El paradero caduca a las 3 rondas sin avistamiento actualizado (14.6).</p>
  <form method="post" action="bajomundo-staff.php">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni((string) $mybb->get_input('my_post_key')); ?>">
    <input type="hidden" name="gaccion" value="publicar_cartel">
    <div class="zs-row"><div>
      <select class="zs-input" name="personaje_id" required>
        <option value="">— Personaje buscado —</option>
        <?php
        if (function_exists('ope7_tabla_existe') && ope7_tabla_existe('personajes')) {
            $qq = $db->simple_select('ope_personajes', 'id, nombre', "estado = 'aprobado'", array('order_by' => 'nombre', 'limit' => 100));
            while ($rr = $db->fetch_array($qq)) {
                echo '<option value="' . (int) $rr['id'] . '">' . htmlspecialchars_uni((string) $rr['nombre']) . '</option>';
            }
        }
        ?>
      </select>
      <input class="zs-input" type="number" name="cifra" min="100000" step="100000" placeholder="Cifra (฿), desde 100.000" required>
      <input class="zs-input" type="text" name="paradero" placeholder="Paradero publicado (o «paradero desconocido»)" required maxlength="160">
    </div>
    <button class="ope-btn" type="submit">Crear trámite 30</button></div>
  </form>
</div></div>
</div>
<?php include 'inc/footer_custom.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
});
</script>
</body>
</html>
