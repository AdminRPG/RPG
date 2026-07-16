<?php
/**
 * I-Forge · Gestionar Noticias (Zona Staff · Administrador)
 * CRUD del feed de "Últimas noticias" de la portada. Las noticias de Mundo Vivo
 * se generan automáticamente al publicar; aquí se crean/editan las manuales y se
 * activa/desactiva la rotación.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-noticias.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid = (int)($mybb->user['uid'] ?? 0);

$staff = $loggedin ? gbe_rol_active_staff($uid) : array('rank' => 0);
$rank  = (int)$staff['rank'];
$is_admin = ($rank >= 3);

$flash = ''; $flash_kind = 'ok';
$edit = null;

if ($is_admin && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.'; $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        if ($action === 'save') {
            $nid     = (int)$mybb->get_input('noticia_id', MyBB::INPUT_INT);
            $titulo  = trim($mybb->get_input('titulo'));
            $resumen = trim($mybb->get_input('resumen'));
            $cuerpo  = $mybb->get_input('cuerpo_html');
            $activa  = $mybb->get_input('activa', MyBB::INPUT_INT) ? 1 : 0;
            $orden   = (int)$mybb->get_input('orden', MyBB::INPUT_INT);
            if ($titulo === '') {
                $flash = 'El título es obligatorio.'; $flash_kind = 'warn';
            } else {
                $data = array(
                    'titulo'      => $db->escape_string($titulo),
                    'resumen'     => $db->escape_string($resumen),
                    'cuerpo_html' => $db->escape_string($cuerpo),
                    'activa'      => $activa,
                    'orden'       => $orden,
                );
                if ($nid > 0) {
                    $db->update_query('rol_mv_noticias', $data, 'noticia_id = ' . $nid);
                    $flash = 'Noticia actualizada.';
                } else {
                    $data['origen'] = 'manual';
                    $data['ciclo_id'] = 0;
                    $data['uid_autor'] = $uid;
                    $data['dateline'] = (int)TIME_NOW;
                    $db->insert_query('rol_mv_noticias', $data);
                    $flash = 'Noticia creada.';
                }
            }
        } elseif ($action === 'toggle') {
            $nid = (int)$mybb->get_input('noticia_id', MyBB::INPUT_INT);
            if ($nid > 0) {
                $cur = (int)$db->fetch_field($db->simple_select('rol_mv_noticias', 'activa', 'noticia_id = ' . $nid), 'activa');
                $db->update_query('rol_mv_noticias', array('activa' => $cur ? 0 : 1), 'noticia_id = ' . $nid);
                $flash = 'Estado de rotación cambiado.';
            }
        } elseif ($action === 'delete') {
            $nid = (int)$mybb->get_input('noticia_id', MyBB::INPUT_INT);
            if ($nid > 0) { $db->delete_query('rol_mv_noticias', 'noticia_id = ' . $nid); $flash = 'Noticia eliminada.'; }
        }
    }
}

// Cargar noticia a editar
$eid = (int)$mybb->get_input('edit', MyBB::INPUT_INT);
if ($is_admin && $eid > 0) {
    $eq = $db->simple_select('rol_mv_noticias', '*', 'noticia_id = ' . $eid, array('limit' => 1));
    if ($db->num_rows($eq)) $edit = $db->fetch_array($eq);
}

// Listado
$noticias = array();
if ($is_admin && $db->table_exists('rol_mv_noticias')) {
    $q = $db->simple_select('rol_mv_noticias', '*', '', array('order_by' => 'activa DESC, orden ASC, dateline', 'order_dir' => 'DESC'));
    while ($r = $db->fetch_array($q)) { $noticias[] = $r; }
}
$pk = htmlspecialchars_uni($mybb->post_code);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Noticias</title>
<?php echo gbe_rol_head_base(); ?>
<!-- estilos en docs/themes/gbe.css (scope: gbe-pg-gestionar-noticias) -->
</head>
<body class="gbe-pg-gestionar-noticias">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
    <b>Noticias</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Noticias de portada</h1><span class="code">// feed rotatorio &middot; últimas noticias</span><span class="rule"></span></div>
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
      <div class="plate-h"><span class="t"><?php echo $edit ? 'Editar noticia' : 'Nueva noticia'; ?></span><span class="c">// manual</span></div>
      <div class="plate-b">
        <form method="post" class="gn-form">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="noticia_id" value="<?php echo (int)$edit['noticia_id']; ?>"><?php endif; ?>
          <label class="mv-lbl">Título</label>
          <input type="text" name="titulo" class="mv-input" value="<?php echo $edit ? htmlspecialchars_uni($edit['titulo']) : ''; ?>" required>
          <label class="mv-lbl">Resumen (texto que rota en portada)</label>
          <input type="text" name="resumen" class="mv-input" value="<?php echo $edit ? htmlspecialchars_uni($edit['resumen']) : ''; ?>">
          <label class="mv-lbl">Cuerpo (HTML que se despliega al hacer clic)</label>
          <textarea name="cuerpo_html" class="mv-input mv-mono" rows="8"><?php echo $edit ? htmlspecialchars_uni($edit['cuerpo_html']) : ''; ?></textarea>
          <div class="gn-row2">
            <label class="mv-check"><input type="checkbox" name="activa" value="1" <?php echo (!$edit || (int)$edit['activa'] === 1) ? 'checked' : ''; ?>> En rotación</label>
            <label class="mv-lbl mv-lbl-inline">Orden <input type="number" name="orden" class="mv-input mv-input-sm" value="<?php echo $edit ? (int)$edit['orden'] : 0; ?>"></label>
          </div>
          <div class="mv-save-bar">
            <button class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Crear noticia'; ?></button>
<?php if ($edit): ?><a href="<?php echo $bburl; ?>/gestionar-noticias.php" class="btn btn-ghost btn-sm">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Noticias</span><span class="c">// <?php echo count($noticias); ?> total</span></div>
      <div class="plate-b">
<?php if (empty($noticias)): ?>
        <p class="mv-empty">No hay noticias todavía.</p>
<?php else: foreach ($noticias as $n): ?>
        <div class="mv-row">
          <div class="mv-mis-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($n['titulo']); ?> <small>[<?php echo $n['origen'] === 'mundo_vivo' ? 'Mundo Vivo' : 'manual'; ?>]</small></span>
            <span class="mv-ev-meta"><?php echo ((int)$n['activa'] === 1) ? 'En rotación' : 'Oculta'; ?> &middot; orden <?php echo (int)$n['orden']; ?></span>
            <?php if (trim((string)$n['resumen']) !== ''): ?><p class="mv-ev-res"><?php echo htmlspecialchars_uni($n['resumen']); ?></p><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <a class="btn btn-sm" href="<?php echo $bburl; ?>/gestionar-noticias.php?edit=<?php echo (int)$n['noticia_id']; ?>">Editar</a>
            <form method="post"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="noticia_id" value="<?php echo (int)$n['noticia_id']; ?>"><button class="btn btn-sm btn-ghost"><?php echo ((int)$n['activa'] === 1) ? 'Ocultar' : 'Mostrar'; ?></button></form>
            <form method="post" onsubmit="return confirm('¿Eliminar noticia?');"><input type="hidden" name="my_post_key" value="<?php echo $pk; ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="noticia_id" value="<?php echo (int)$n['noticia_id']; ?>"><button class="btn btn-sm btn-danger">×</button></form>
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
