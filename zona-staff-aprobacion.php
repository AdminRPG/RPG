<?php
/**
 * Zona Staff · Aprobación de personajes
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-aprobacion.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_system.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);

if (!$is_staff || $staff_rank < 1) {
    header('Location: ' . $mybb->settings['bburl'] . '/zona-staff.php');
    exit;
}

$flash = '';
$flash_ok = false;
$view_pid = (int) $mybb->get_input('pid', MyBB::INPUT_INT);

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó. Vuelve a intentarlo.';
    } else {
        $action = $mybb->get_input('zs_action');
        $pid_act = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
        if ($action === 'aprobar') {
            $cambiar_fruta_id = $mybb->get_input('cambiar_fruta_id', MyBB::INPUT_INT);
            if ($cambiar_fruta_id > 0 && function_exists('ope_fruta_asignar')) {
                $fruta_actual = function_exists('ope_fruta_pj') ? ope_fruta_pj($pid_act) : null;
                if ($fruta_actual && (int) ($fruta_actual['fruta_id'] ?? 0) !== $cambiar_fruta_id) {
                    if (function_exists('ope_fruta_liberar')) {
                        ope_fruta_liberar($pid_act);
                    }
                    ope_fruta_asignar($pid_act, $cambiar_fruta_id, 'staff');
                } elseif (!$fruta_actual) {
                    ope_fruta_asignar($pid_act, $cambiar_fruta_id, 'staff');
                }
            }
            $res = ope_rol_pj_aprobar($pid_act, $uid);
            $flash = $res['msg'];
            $flash_ok = !empty($res['ok']);
            $view_pid = 0;
        } elseif ($action === 'rechazar') {
            $res = ope_rol_pj_rechazar($pid_act, $uid, $mybb->get_input('motivo'));
            $flash = $res['msg'];
            $flash_ok = !empty($res['ok']);
            $view_pid = 0;
        }
    }
}

$cola = array();
if ($db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "estado = 'revision'", array('order_by' => 'dateline', 'order_dir' => 'ASC'));
    while ($row = $db->fetch_array($q)) {
        $cola[] = $row;
    }
}

$detail = null;
$detail_datos = array();
$detail_bio = array();
$detail_stats = array();
$owner_name = '';
if ($view_pid > 0) {
    foreach ($cola as $row) {
        if ((int) $row['pid'] === $view_pid) {
            $detail = $row;
            break;
        }
    }
    if ($detail === null && $db->table_exists('rol_personajes')) {
        $dq = $db->simple_select('rol_personajes', '*', "pid = {$view_pid} AND estado = 'revision'", array('limit' => 1));
        if ($db->num_rows($dq)) {
            $detail = $db->fetch_array($dq);
        }
    }
    if ($detail) {
        $detail_datos = json_decode((string) ($detail['datos'] ?? ''), true);
        if (!is_array($detail_datos)) {
            $detail_datos = array();
        }
        $detail_bio = json_decode((string) ($detail['bio'] ?? ''), true);
        if (!is_array($detail_bio)) {
            $detail_bio = array();
        }
        $detail_stats = json_decode((string) ($detail['stats_json'] ?? ''), true);
        if (!is_array($detail_stats)) {
            $detail_stats = array();
        }
        $ouid = (int) ($detail['uid'] ?? 0);
        if ($ouid > 0) {
            $uq = $db->simple_select('users', 'username', "uid = {$ouid}", array('limit' => 1));
            if ($db->num_rows($uq)) {
                $owner_name = (string) $db->fetch_field($uq, 'username');
            }
        }
    }
}

$RAZAS = ope_rol_razas();
$CLASES = ope_rol_clases();
$OFICIOS = ope_rol_oficios();
$ARMAS_VOC = ope_rol_armas_vocacionales();
$FACCIONES = ope_rol_facciones();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Aprobación de personajes</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Aprobación</b>
</div></div>
<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Aprobación de personajes</h1>
      <span class="code">// STF-01</span>
      <span class="rule"></span>
    </div>
    <p class="zs-intro">Cola de <b>personajes nuevos</b> en revisión. Al aprobar se otorga <b>1 PT</b>.</p>
    <p><a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/zona-staff.php">&larr; Paneles</a></p>
  </section>
<?php if ($flash !== ''): ?>
  <div class="zs-flash<?php echo $flash_ok ? '' : ' warn'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">En cola</span><span class="c">// revision → aprobado</span></div>
      <div class="plate-b zs-plate-left">
<?php if (empty($cola)): ?>
        <p class="pj-empty">No hay personajes pendientes de revisión.</p>
<?php else: ?>
        <div class="zs-stafftbl">
<?php foreach ($cola as $row):
    $pid = (int) $row['pid'];
    $datos = json_decode((string) ($row['datos'] ?? ''), true);
    if (!is_array($datos)) {
        $datos = array();
    }
    $id_key = (string) ($datos['identidad'] ?? '');
    $fam_key = (string) ($datos['familia_arma'] ?? '');
    $id_lbl = isset($IDENTIDADES[$id_key]) ? $IDENTIDADES[$id_key]['nombre'] : ($id_key !== '' ? $id_key : '—');
    $fam_lbl = isset($FAMILIAS[$fam_key]) ? $FAMILIAS[$fam_key]['nombre'] : ($fam_key !== '' ? $fam_key : '—');
    $raza_key = (string) ($datos['raza'] ?? '');
    $raza_lbl = isset($RAZAS[$raza_key]) ? $RAZAS[$raza_key]['nombre'] : ($raza_key !== '' ? $raza_key : '—');
?>
          <div class="zs-staffrow">
            <div class="zs-staffwho">
              <span class="zs-staffname"><?php echo htmlspecialchars_uni($row['nombre']); ?></span>
              <span class="zs-staffowner"><?php echo htmlspecialchars_uni($raza_lbl); ?> · <?php echo htmlspecialchars_uni($id_lbl); ?> / <?php echo htmlspecialchars_uni($fam_lbl); ?> · #<?php echo $pid; ?></span>
            </div>
            <a class="btn btn-ghost btn-sm" href="<?php echo $bburl; ?>/zona-staff-aprobacion.php?pid=<?php echo $pid; ?>">Revisar</a>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </div>
  </section>

<?php if ($detail):
    $pid = (int) $detail['pid'];
    $clase_key = (string) ($detail_datos['clase'] ?? '');
    $oficios_keys = isset($detail_datos['oficios']) && is_array($detail_datos['oficios']) ? $detail_datos['oficios'] : array();
    $arma_key = (string) ($detail_datos['arma'] ?? '');
    $raza_key = (string) ($detail_datos['raza'] ?? '');
    $fac_key = (string) ($detail_datos['faccion'] ?? '');

    $oficios_names = array();
    foreach ($oficios_keys as $ok) {
        if (isset($OFICIOS[$ok])) {
            $oficios_names[] = $OFICIOS[$ok]['nombre'];
        }
    }
    $oficios_str = !empty($oficios_names) ? implode(', ', $oficios_names) : 'Ninguno';

    $dotes = isset($detail_datos['virtudes_defectos']) && is_array($detail_datos['virtudes_defectos'])
        ? $detail_datos['virtudes_defectos']
        : array();
?>
  <section class="reveal" id="zs-detail">
    <div class="plate">
      <div class="plate-h">
        <span class="t"><?php echo htmlspecialchars_uni($detail['nombre']); ?></span>
        <span class="c">// ficha en revisión</span>
      </div>
      <div class="plate-b zs-plate-left">
        <p class="zs-meta">
          Jugador: <b><?php echo htmlspecialchars_uni($owner_name !== '' ? $owner_name : 'uid ' . (int)$detail['uid']); ?></b>
          · Raza: <b><?php echo htmlspecialchars_uni(isset($RAZAS[$raza_key]) ? $RAZAS[$raza_key]['nombre'] : $raza_key); ?></b>
          · Clase: <b><?php echo htmlspecialchars_uni(isset($CLASES[$clase_key]) ? $CLASES[$clase_key]['nombre'] : $clase_key); ?></b>
          · Oficios: <b><?php echo htmlspecialchars_uni($oficios_str); ?></b>
          · Arma: <b><?php echo htmlspecialchars_uni(isset($ARMAS_VOC[$arma_key]) ? $ARMAS_VOC[$arma_key]['nombre'] : $arma_key); ?></b>
          · Facción: <b><?php echo htmlspecialchars_uni(isset($FACCIONES[$fac_key]) ? $FACCIONES[$fac_key]['nombre'] : $fac_key); ?></b>
        </p>
        <h3 class="zs-subh">Stats</h3>
        <div class="zs-stats">
<?php foreach ($detail_stats as $sk => $sv): ?>
          <span class="zs-stat"><b><?php echo htmlspecialchars_uni($sk); ?></b> <?php echo (int)$sv; ?></span>
<?php endforeach; ?>
        </div>
        <h3 class="zs-subh">Historia</h3>
        <p class="zs-bio"><?php echo nl2br(htmlspecialchars_uni((string)($detail_bio['historia'] ?? ''))); ?></p>
<?php if (!empty($dotes)): ?>
        <h3 class="zs-subh">Virtudes / Defectos</h3>
        <ul class="zs-dotes">
<?php foreach ($dotes as $did => $d): ?>
          <li>
            <b><?php echo htmlspecialchars_uni($d['nombre'] ?? $did); ?></b>
            (<?php echo (int)($d['valor'] ?? 0); ?><?php echo !empty($d['tipo']) ? ' · ' . htmlspecialchars_uni($d['tipo']) : ''; ?>)
            <?php if (!empty($d['spec'])): ?> — <?php echo htmlspecialchars_uni($d['spec']); ?><?php endif; ?>
          </li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
<?php if (!empty($detail_datos['cyborg'])): ?>
        <p class="zs-cyborg-flag">Mecánica <b>Cyborg</b> activa · slot Tier I: <b><?php echo htmlspecialchars_uni((string)($detail_datos['cyborg_slot'] ?? '—')); ?></b></p>
<?php endif; ?>
<?php
        $pj_fruta = function_exists('ope_fruta_pj') ? ope_fruta_pj($pid) : null;
        $fruta_info = ($pj_fruta && function_exists('ope_fruta_by_id')) ? ope_fruta_by_id((int) $pj_fruta['fruta_id']) : null;
        $frutas_libres = function_exists('ope_fruta_libres') ? ope_fruta_libres(0) : array();
?>
        <h3 class="zs-subh">🍇 Akuma no Mi (Sorteo / Asignación)</h3>
        <p class="zs-meta mb-8">
<?php if ($fruta_info): ?>
          Fruta sorteada / asignada: <b><?php echo htmlspecialchars_uni($fruta_info['nombre'] ?? 'Fruta'); ?></b>
          (<?php echo htmlspecialchars_uni($fruta_info['tipo'] ?? 'Desconocido'); ?> · Tier <?php echo (int) ($fruta_info['tier'] ?? 1); ?>)
<?php else: ?>
          <i>Este personaje no solicitó fruta o no había stock disponible en el momento del sorteo.</i>
<?php endif; ?>
        </p>
        <div class="mb-12">
          <label class="zs-lbl-subtle">
            Modificar / Reasignar fruta libre (Staff):
            <select name="cambiar_fruta_id" form="zs-form-aprobar-main" class="zs-select-sm">
              <option value="0">-- Mantener actual / Sin cambio --</option>
<?php foreach ($frutas_libres as $flib): ?>
              <option value="<?php echo (int) $flib['id']; ?>">
                <?php echo htmlspecialchars_uni($flib['nombre']); ?> (<?php echo htmlspecialchars_uni($flib['tipo'] ?? ''); ?> · Tier <?php echo (int) ($flib['tier'] ?? 1); ?>)
              </option>
<?php endforeach; ?>
            </select>
          </label>
        </div>
        <div class="zs-actions">
          <form method="post" class="zs-form-inline" id="zs-form-aprobar-main">
            <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
            <input type="hidden" name="pid" value="<?php echo $pid; ?>">
            <input type="hidden" name="zs_action" value="aprobar">
            <button type="submit" class="btn btn-hot">Aprobar (+1 PT)</button>
          </form>
          <form method="post" class="zs-form-reject">
            <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
            <input type="hidden" name="pid" value="<?php echo $pid; ?>">
            <input type="hidden" name="zs_action" value="rechazar">
            <label class="zs-motivo-lbl">Motivo de rechazo
              <textarea name="motivo" rows="2" required placeholder="Qué debe corregir el jugador…"></textarea>
            </label>
            <button type="submit" class="btn btn-ghost">Rechazar</button>
          </form>
        </div>
      </div>
    </div>
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
