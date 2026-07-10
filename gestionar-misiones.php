<?php
/**
 * I-Forge · Gestionar Misiones (Zona Staff · Administrador)
 * CRUD completo del Tablón de Misiones.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-misiones.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid = (int)($mybb->user['uid'] ?? 0);

$staff = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0);
$rank  = (int)$staff['rank'];
$is_admin = ($rank >= 3);

$flash = ''; $flash_kind = 'ok';
$edit = null;

$zonas     = $db->table_exists('rol_mv_zonas') ? ope_rol_mv_zonas() : array();
$facciones = $db->table_exists('rol_mv_facciones') ? ope_rol_mv_facciones() : array();
$orden_fac = ope_rol_mv_faccion_order();
$ciclo     = ope_rol_mv_ciclo_actual();
$ciclo_id  = $ciclo ? (int)$ciclo['ciclo_id'] : 0;

if ($is_admin && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.'; $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        if ($action === 'save') {
            $mid    = (int)$mybb->get_input('mision_id', MyBB::INPUT_INT);
            $titulo = trim($mybb->get_input('titulo'));
            if ($titulo === '') {
                $flash = 'El título es obligatorio.'; $flash_kind = 'warn';
            } else {
                $data = array(
                    'titulo'           => $db->escape_string($titulo),
                    'resumen'          => $db->escape_string($mybb->get_input('resumen')),
                    'descripcion_larga' => $db->escape_string($mybb->get_input('descripcion_larga')),
                    'rango'            => $db->escape_string($mybb->get_input('rango')),
                    'peligrosidad'     => min(5, max(1, (int)$mybb->get_input('peligrosidad', MyBB::INPUT_INT))),
                    'zona_slug'        => $db->escape_string($mybb->get_input('zona_slug')),
                    'facciones'        => $db->escape_string($mybb->get_input('facciones')),
                    'recompensa'       => $db->escape_string($mybb->get_input('recompensa')),
                    'modalidad'        => $db->escape_string($mybb->get_input('modalidad')),
                    'estado'           => $db->escape_string($mybb->get_input('estado')),
                );
                if ($mid > 0) {
                    $db->update_query('rol_mv_misiones', $data, 'mision_id = ' . $mid);
                    $flash = 'Misión actualizada.';
                } else {
                    $data['ciclo_id'] = $ciclo_id > 0 ? $ciclo_id : 0;
                    $data['dateline'] = (int)TIME_NOW;
                    $db->insert_query('rol_mv_misiones', $data);
                    $flash = 'Misión creada.';
                }
            }
        } elseif ($action === 'delete') {
            $mid = (int)$mybb->get_input('mision_id', MyBB::INPUT_INT);
            if ($mid > 0) {
                $db->delete_query('rol_mv_misiones', 'mision_id = ' . $mid);
                if ($db->table_exists('rol_mv_mision_asignaciones')) {
                    $db->delete_query('rol_mv_mision_asignaciones', 'mision_id = ' . $mid);
                }
                $flash = 'Misión eliminada.';
            }
        }
    }
}

$eid = (int)$mybb->get_input('edit', MyBB::INPUT_INT);
if ($is_admin && $eid > 0) {
    $eq = $db->simple_select('rol_mv_misiones', '*', 'mision_id = ' . $eid, array('limit' => 1));
    if ($db->num_rows($eq)) $edit = $db->fetch_array($eq);
}

// Misiones para el listado de gestión: las COMPLETADAS ya no se muestran.
$misiones = array();
if ($is_admin && $db->table_exists('rol_mv_misiones')) {
    $q = $db->simple_select('rol_mv_misiones', '*', "estado <> 'completada'", array('order_by' => 'mision_id', 'order_dir' => 'DESC'));
    while ($r = $db->fetch_array($q)) $misiones[] = $r;
}
// Asignaciones (quién ha cogido cada misión) para marcar las "en proceso".
$asignaciones = ope_rol_mv_asignaciones_map();

$pk = htmlspecialchars_uni($mybb->post_code);

$rango_opts = array('S','A','B','C','D');
$modalidad_opts = array('solo'=>'Individual','grupo'=>'Grupo','cualquiera'=>'Cualquiera');
$estado_opts = array('en_curso'=>'En curso','completada'=>'Completada','fallida'=>'Fallida');

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Gestionar misiones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-gestionar-misiones ope-pg-mundo-vivo">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">›</span>
    <b>Gestionar misiones</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Gestionar misiones</h1><span class="code">// tablón · ciclo <?php echo $ciclo_id ? htmlspecialchars_uni($ciclo['periodo']) : 'inactivo'; ?></span><span class="rule"></span></div>
  </section>

<?php if (!$is_admin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b">
    <div class="noperm"><div class="big">Zona reservada a Administradores</div>
    <a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver a Zona Staff</a></div>
  </div></div></section>
<?php else: ?>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t"><?php echo $edit ? 'Editar misión' : 'Nueva misión'; ?></span><span class="c">// <?php echo $edit ? '#' . (int)$edit['mision_id'] : 'crear'; ?></span></div>
      <div class="plate-b">
        <form method="post" class="gn-form">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
          <input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="mision_id" value="<?php echo (int)$edit['mision_id']; ?>"><?php endif; ?>

          <label class="mv-lbl">Título *</label>
          <input type="text" name="titulo" class="mv-input" value="<?php echo $edit ? htmlspecialchars_uni($edit['titulo']) : ''; ?>" required>

          <div class="gn-row2">
            <div class="flex-1">
              <label class="mv-lbl">Rango</label>
              <select name="rango" class="mv-input">
<?php foreach ($rango_opts as $r): ?>
                <option value="<?php echo $r; ?>"<?php if ($edit && $edit['rango'] === $r) echo ' selected'; ?>><?php echo $r; ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div class="flex-1">
              <label class="mv-lbl">Peligrosidad (1-5)</label>
              <input type="number" name="peligrosidad" class="mv-input" min="1" max="5" value="<?php echo $edit ? (int)$edit['peligrosidad'] : 1; ?>">
            </div>
            <div class="flex-1">
              <label class="mv-lbl">Modalidad</label>
              <select name="modalidad" class="mv-input">
<?php foreach ($modalidad_opts as $mv => $ml): ?>
                <option value="<?php echo $mv; ?>"<?php if ($edit && $edit['modalidad'] === $mv) echo ' selected'; ?>><?php echo $ml; ?></option>
<?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="gn-row2">
            <div class="flex-1">
              <label class="mv-lbl">Estado</label>
              <select name="estado" class="mv-input">
<?php foreach ($estado_opts as $ev => $el): ?>
                <option value="<?php echo $ev; ?>"<?php if ($edit && $edit['estado'] === $ev) echo ' selected'; ?>><?php echo $el; ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div class="flex-1">
              <label class="mv-lbl">Zona</label>
              <select name="zona_slug" class="mv-input">
                <option value="">—</option>
<?php foreach ($zonas as $zs => $zn): ?>
                <option value="<?php echo htmlspecialchars_uni($zs); ?>"<?php if ($edit && $edit['zona_slug'] === $zs) echo ' selected'; ?>><?php echo htmlspecialchars_uni($zn['nombre']); ?></option>
<?php endforeach; ?>
              </select>
            </div>
            <div class="flex-1">
              <label class="mv-lbl">Facción(es)</label>
              <select name="facciones" class="mv-input">
                <option value="">—</option>
<?php foreach ($orden_fac as $fs): if (!isset($facciones[$fs])) continue; ?>
                <option value="<?php echo htmlspecialchars_uni($fs); ?>"<?php if ($edit && $edit['facciones'] === $fs) echo ' selected'; ?>><?php echo htmlspecialchars_uni($facciones[$fs]['nombre']); ?></option>
<?php endforeach; ?>
              </select>
            </div>
          </div>

          <label class="mv-lbl">Recompensa</label>
          <input type="text" name="recompensa" class="mv-input" value="<?php echo $edit ? htmlspecialchars_uni($edit['recompensa']) : ''; ?>">

          <label class="mv-lbl">Resumen (texto corto visible en el tablón)</label>
          <textarea name="resumen" class="mv-input" rows="2"><?php echo $edit ? htmlspecialchars_uni($edit['resumen']) : ''; ?></textarea>

          <label class="mv-lbl">Descripción larga (detalle completo al abrir la misión)</label>
          <textarea name="descripcion_larga" class="mv-input mv-mono" rows="6"><?php echo $edit ? htmlspecialchars_uni($edit['descripcion_larga']) : ''; ?></textarea>

          <div class="mv-save-bar">
            <button class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Crear misión'; ?></button>
<?php if ($edit): ?><a href="<?php echo $bburl; ?>/gestionar-misiones.php" class="btn btn-ghost btn-sm">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones</span><span class="c">// <?php echo count($misiones); ?> total</span></div>
      <div class="plate-b">
<?php if (empty($misiones)): ?>
        <p class="mv-empty">No hay misiones todavía.</p>
<?php else: foreach ($misiones as $m):
        $mid = (int)$m['mision_id'];
        $zn = isset($zonas[$m['zona_slug']]) ? htmlspecialchars_uni($zonas[$m['zona_slug']]['nombre']) : htmlspecialchars_uni($m['zona_slug']);
        $asig = $asignaciones[$mid] ?? null;
        $en_proceso = ($asig && $m['estado'] === 'en_curso');
        // Nombres del líder y compañeros que han cogido la misión.
        $lider_n = $asig ? ope_rol_cat_nombre_pid((int)$asig['pid']) : '';
        $comp_names = array();
        if ($asig) { foreach ($asig['companeros_arr'] as $cpid) { $n = ope_rol_cat_nombre_pid((int)$cpid); if ($n !== '') $comp_names[] = $n; } }
        $estado_txt = $en_proceso ? 'En proceso' : ($estado_opts[$m['estado']] ?? htmlspecialchars_uni($m['estado']));
?>
        <div class="mv-row mv-mis-<?php echo $en_proceso ? 'en_proceso' : htmlspecialchars_uni($m['estado']); ?>">
          <div class="mv-ev-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($m['titulo']); ?> <small>[<?php echo $m['rango'] ?: 'D'; ?> · <?php echo htmlspecialchars_uni($m['modalidad']); ?>]</small>
              <?php if ($en_proceso): ?><span class="mv-mis-badge">En proceso</span><?php endif; ?></span>
            <span class="mv-ev-meta"><?php echo $zn ? $zn . ' · ' : ''; ?><?php echo $m['facciones'] ? htmlspecialchars_uni($m['facciones']) . ' · ' : ''; ?>pelig. <?php echo (int)$m['peligrosidad']; ?>/5 · <?php echo $estado_txt; ?></span>
            <?php if ($en_proceso && $lider_n !== ''): ?>
            <p class="mv-mis-cogida">Cogida por <b><?php echo htmlspecialchars_uni($lider_n); ?></b><?php if ($comp_names): ?> junto a <?php echo htmlspecialchars_uni(implode(', ', $comp_names)); ?><?php endif; ?></p>
            <?php endif; ?>
            <?php if (trim((string)$m['resumen']) !== ''): ?><p class="mv-ev-res"><?php echo htmlspecialchars_uni($m['resumen']); ?></p><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <a class="btn btn-sm" href="<?php echo $bburl; ?>/gestionar-misiones.php?edit=<?php echo $mid; ?>">Editar</a>
            <form method="post" onsubmit="return confirm('¿Eliminar esta misión?');">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="mision_id" value="<?php echo $mid; ?>">
              <button class="btn btn-sm btn-danger">×</button>
            </form>
          </div>
        </div>
<?php endforeach; endif; ?>
      </div>
    </div>
  </section>

<?php endif; ?>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } }), { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else { document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis')); }
</script>
</body>
</html>