<?php
/**
 * I-Forge · Gestionar Catálogos (Zona Staff · Administrador)
 * CRUD unificado de todos los catálogos del foro en una sola página con
 * subsecciones: Tienda, Tripulaciones, Pactos Primarios, Bestiario y Estilos.
 *
 * El comportamiento es genérico y está dirigido por $CATALOGOS: añadir un
 * catálogo nuevo es declarar su tabla y sus campos.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-catalogos.php');
require_once './global.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int)($mybb->user['uid'] ?? 0) > 0;
$uid = (int)($mybb->user['uid'] ?? 0);

$staff = $loggedin ? gbe_rol_active_staff($uid) : array('rank' => 0);
$is_admin = ((int)$staff['rank'] >= 3);

// ── Opciones de selects reutilizables ──
$fac_opts = array();
foreach (gbe_rol_facciones() as $slug => $f) { $fac_opts[$slug] = $f['nombre']; }
$tienda_opts = array();
foreach (gbe_rol_cat_tiendas() as $slug => $t) { $tienda_opts[$slug] = $t['nombre']; }
$cat_prod_opts = gbe_rol_cat_categoria_labels();
$rareza_opts = array('Común' => 'Común', 'Raro' => 'Raro', 'Épico' => 'Épico', 'Legendario' => 'Legendario');
$dif_opts = array('Baja' => 'Baja', 'Media' => 'Media', 'Alta' => 'Alta', 'Legendaria' => 'Legendaria');
$peligro_opts = array('Bajo' => 'Bajo', 'Moderado' => 'Moderado', 'Alto' => 'Alto', 'Extremo' => 'Extremo');
$tipo_akuma_opts = array('paramecia' => 'Conceptual (Paramecia)', 'zoa' => 'Bestia Primal (Zoan)', 'logia' => 'Elemental (Logia)');
$est_cat_opts = array('Combate' => 'Combate', 'Defensa' => 'Defensa', 'Percepción' => 'Percepción', 'Apoyo' => 'Apoyo');

// ── Definición de catálogos ──
// type: text | number | textarea | select | list(JSON) ; act = campo activo
$CATALOGOS = array(
    'tienda' => array(
        'table' => 'rol_tienda_items', 'label' => 'Tienda', 'sing' => 'producto',
        'sub' => array('resumen'),
        'fields' => array(
            'nombre'      => array('t' => 'text', 'l' => 'Nombre', 'req' => true, 'w' => 2),
            'tienda'      => array('t' => 'select', 'l' => 'Sección', 'opts' => $tienda_opts),
            'categoria'   => array('t' => 'select', 'l' => 'Categoría', 'opts' => $cat_prod_opts),
            'precio'      => array('t' => 'number', 'l' => 'Precio (B)'),
            'resumen'     => array('t' => 'text', 'l' => 'Resumen (tarjeta)', 'w' => 2),
            'descripcion' => array('t' => 'textarea', 'l' => 'Descripción larga', 'w' => 2),
            'detalles'    => array('t' => 'list', 'l' => 'Detalles (uno por línea, "Clave: Valor")', 'w' => 2),
            'imagen'      => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'       => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
    'tripulaciones' => array(
        'table' => 'rol_tripulaciones', 'label' => 'Tripulaciones', 'sing' => 'tripulación',
        'sub' => array('capitan', 'faccion'),
        'fields' => array(
            'nombre'      => array('t' => 'text', 'l' => 'Nombre', 'req' => true, 'w' => 2),
            'faccion'     => array('t' => 'select', 'l' => 'Facción', 'opts' => $fac_opts),
            'capitan'     => array('t' => 'text', 'l' => 'Capitán'),
            'nivel'       => array('t' => 'number', 'l' => 'Nivel'),
            'miembros'    => array('t' => 'number', 'l' => 'Miembros'),
            'lema'        => array('t' => 'text', 'l' => 'Lema', 'w' => 2),
            'descripcion' => array('t' => 'textarea', 'l' => 'Descripción', 'w' => 2),
            'imagen'      => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'       => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
    'akuma' => array(
        'table' => 'rol_akuma', 'label' => 'Pactos Primarios', 'sing' => 'pacto',
        'sub' => array('tipo', 'usuario'),
        'fields' => array(
            'nombre'      => array('t' => 'text', 'l' => 'Nombre', 'req' => true, 'w' => 2),
            'tipo'        => array('t' => 'select', 'l' => 'Tipo', 'opts' => $tipo_akuma_opts),
            'rareza'      => array('t' => 'select', 'l' => 'Rareza', 'opts' => $rareza_opts),
            'usuario'     => array('t' => 'text', 'l' => 'Usuario actual', 'w' => 2),
            'descripcion' => array('t' => 'textarea', 'l' => 'Pacto / descripción', 'w' => 2),
            'despertar'   => array('t' => 'textarea', 'l' => 'Trascendencia', 'w' => 2),
            'debilidad'   => array('t' => 'text', 'l' => 'Debilidad', 'w' => 2),
            'imagen'      => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'       => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
    'bestiario' => array(
        'table' => 'rol_bestiario', 'label' => 'Bestiario', 'sing' => 'criatura',
        'sub' => array('habitat', 'peligro'),
        'fields' => array(
            'nombre'      => array('t' => 'text', 'l' => 'Nombre', 'req' => true, 'w' => 2),
            'rareza'      => array('t' => 'select', 'l' => 'Rareza', 'opts' => $rareza_opts),
            'peligro'     => array('t' => 'select', 'l' => 'Peligrosidad', 'opts' => $peligro_opts),
            'habitat'     => array('t' => 'text', 'l' => 'Hábitat'),
            'tamano'      => array('t' => 'text', 'l' => 'Tamaño'),
            'dieta'       => array('t' => 'text', 'l' => 'Dieta'),
            'descripcion' => array('t' => 'textarea', 'l' => 'Descripción', 'w' => 2),
            'imagen'      => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'       => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
    'estilos' => array(
        'table' => 'rol_estilos', 'label' => 'Estilos', 'sing' => 'estilo',
        'sub' => array('categoria', 'dificultad'),
        'fields' => array(
            'nombre'      => array('t' => 'text', 'l' => 'Nombre', 'req' => true, 'w' => 2),
            'categoria'   => array('t' => 'select', 'l' => 'Categoría', 'opts' => $est_cat_opts),
            'dificultad'  => array('t' => 'select', 'l' => 'Dificultad', 'opts' => $dif_opts),
            'usuarios'    => array('t' => 'text', 'l' => 'Practicantes', 'w' => 2),
            'descripcion' => array('t' => 'textarea', 'l' => 'Descripción', 'w' => 2),
            'tecnicas'    => array('t' => 'textarea', 'l' => 'Técnicas destacadas', 'w' => 2),
            'imagen'      => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'       => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
    'lore' => array(
        'table' => 'rol_lore', 'label' => 'Lore', 'sing' => 'artículo',
        'sub' => array('categoria', 'subcategoria'),
        'fields' => array(
            'nombre'        => array('t' => 'text', 'l' => 'Título', 'req' => true, 'w' => 2),
            'slug'          => array('t' => 'text', 'l' => 'Slug', 'w' => 2),
            'categoria'     => array('t' => 'select', 'l' => 'Categoría', 'opts' => array(
                'historia' => 'Historia', 'eras' => 'Eras', 'personajes' => 'Personajes',
                'facciones' => 'Facciones', 'ubicaciones' => 'Ubicaciones',
                'sistemas' => 'Sistemas', 'cronologia' => 'Cronología',
            )),
            'subcategoria' => array('t' => 'text', 'l' => 'Subcategoría', 'w' => 2),
            'resumen'       => array('t' => 'textarea', 'l' => 'Resumen (tarjeta)', 'w' => 2),
            'contenido'     => array('t' => 'textarea', 'l' => 'Contenido (HTML)', 'w' => 2),
            'imagen'        => array('t' => 'text', 'l' => 'URL de imagen', 'w' => 2),
            'orden'         => array('t' => 'number', 'l' => 'Orden'),
        ),
    ),
);

$cat = $mybb->get_input('cat');
if (!isset($CATALOGOS[$cat])) { $cat = 'tienda'; }
$conf  = $CATALOGOS[$cat];
$table = $conf['table'];

$flash = ''; $flash_kind = 'ok';
$edit = null;

// ── POST ──
if ($is_admin && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada.'; $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $id = (int) $mybb->get_input('id', MyBB::INPUT_INT);
        if ($action === 'save') {
            $nombre = trim($mybb->get_input('nombre'));
            if ($nombre === '') {
                $flash = 'El nombre es obligatorio.'; $flash_kind = 'warn';
            } else {
                $data = array();
                foreach ($conf['fields'] as $fn => $fd) {
                    $raw = $mybb->get_input($fn);
                    if ($fd['t'] === 'number') {
                        $data[$fn] = (int) $mybb->get_input($fn, MyBB::INPUT_INT);
                    } elseif ($fd['t'] === 'list') {
                        $lines = preg_split('/\r?\n/', (string) $raw);
                        $lines = array_values(array_filter(array_map('trim', $lines), function ($s) { return $s !== ''; }));
                        $data[$fn] = $db->escape_string(json_encode($lines, JSON_UNESCAPED_UNICODE));
                    } else {
                        $data[$fn] = $db->escape_string((string) $raw);
                    }
                }
                $data['activo'] = $mybb->get_input('activo', MyBB::INPUT_INT) ? 1 : 0;
                if ($id > 0) {
                    $db->update_query($table, $data, 'id = ' . $id);
                    $flash = ucfirst($conf['sing']) . ' actualizado.';
                } else {
                    $data['dateline'] = (int) TIME_NOW;
                    $db->insert_query($table, $data);
                    $flash = ucfirst($conf['sing']) . ' creado.';
                }
            }
        } elseif ($action === 'delete' && $id > 0) {
            $db->delete_query($table, 'id = ' . $id);
            $flash = ucfirst($conf['sing']) . ' eliminado.';
        } elseif ($action === 'toggle' && $id > 0) {
            $rq = $db->simple_select($table, 'activo', 'id = ' . $id, array('limit' => 1));
            if ($db->num_rows($rq)) {
                $nuevo = ((int)$db->fetch_field($rq, 'activo')) ? 0 : 1;
                $db->update_query($table, array('activo' => $nuevo), 'id = ' . $id);
                $flash = $nuevo ? 'Elemento activado.' : 'Elemento ocultado.';
            }
        }
    }
}

$eid = (int) $mybb->get_input('edit', MyBB::INPUT_INT);
if ($is_admin && $eid > 0) {
    $eq = $db->simple_select($table, '*', 'id = ' . $eid, array('limit' => 1));
    if ($db->num_rows($eq)) $edit = $db->fetch_array($eq);
}

$rows = array();
if ($is_admin && $db->table_exists($table)) {
    $q = $db->simple_select($table, '*', '', array('order_by' => 'orden, id', 'order_dir' => 'ASC'));
    while ($r = $db->fetch_array($q)) $rows[] = $r;
}

// ── Contadores por catálogo (para las pestañas) ──
$counts = array();
foreach ($CATALOGOS as $ck => $cc) {
    $counts[$ck] = $db->table_exists($cc['table']) ? (int)$db->fetch_field($db->simple_select($cc['table'], 'COUNT(*) c'), 'c') : 0;
}

$pk = htmlspecialchars_uni($mybb->post_code);

/** Valor actual de un campo (edición o vacío). */
function gc_val($edit, $fn, $fd)
{
    if (!$edit || !isset($edit[$fn])) return $fd['t'] === 'number' && $fn === 'orden' ? '0' : '';
    if ($fd['t'] === 'list') {
        $arr = json_decode((string)$edit[$fn], true);
        return is_array($arr) ? implode("\n", $arr) : '';
    }
    return (string) $edit[$fn];
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Gestionar catálogos</title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-gestionar-catalogos gbe-pg-mundo-vivo">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">›</span>
    <b>Gestionar catálogos</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead"><h1>Gestionar catálogos</h1><span class="code">// tienda · tripulaciones · bibliotecas</span><span class="rule"></span></div>
  </section>

<?php if (!$is_admin): ?>
  <section class="reveal"><div class="plate"><div class="plate-b">
    <div class="noperm"><div class="big">Zona reservada a Administradores</div>
    <a href="<?php echo $bburl; ?>/zona-staff.php" class="btn btn-ghost">Volver a Zona Staff</a></div>
  </div></div></section>
<?php else: ?>

  <section class="reveal">
    <div class="gc-tabs">
<?php foreach ($CATALOGOS as $ck => $cc): ?>
      <a href="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $ck; ?>" class="gc-tab<?php echo $ck === $cat ? ' on' : ''; ?>"><?php echo htmlspecialchars_uni($cc['label']); ?> <span class="gc-tab-n"><?php echo (int)$counts[$ck]; ?></span></a>
<?php endforeach; ?>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="mv-flash mv-<?php echo $flash_kind; ?>"><?php echo htmlspecialchars_uni($flash); ?></div></section>
<?php endif; ?>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t"><?php echo $edit ? 'Editar ' . htmlspecialchars_uni($conf['sing']) : 'Nuevo ' . htmlspecialchars_uni($conf['sing']); ?></span><span class="c">// <?php echo $edit ? '#' . (int)$edit['id'] : 'crear'; ?></span></div>
      <div class="plate-b">
        <form method="post" class="gc-form" action="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $cat; ?>">
          <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
          <input type="hidden" name="action" value="save">
<?php if ($edit): ?><input type="hidden" name="id" value="<?php echo (int)$edit['id']; ?>"><?php endif; ?>

          <div class="gc-grid">
<?php foreach ($conf['fields'] as $fn => $fd): $val = gc_val($edit, $fn, $fd); $wide = !empty($fd['w']) && $fd['w'] >= 2; ?>
            <div class="gc-field<?php echo $wide ? ' gc-wide' : ''; ?>">
              <label class="mv-lbl"><?php echo htmlspecialchars_uni($fd['l']); ?><?php echo !empty($fd['req']) ? ' *' : ''; ?></label>
<?php if ($fd['t'] === 'textarea'): ?>
              <textarea name="<?php echo $fn; ?>" class="mv-input" rows="4"><?php echo htmlspecialchars_uni($val); ?></textarea>
<?php elseif ($fd['t'] === 'list'): ?>
              <textarea name="<?php echo $fn; ?>" class="mv-input mv-mono" rows="4" placeholder="Daño base: 1d8&#10;Peso: 2.5 kg"><?php echo htmlspecialchars_uni($val); ?></textarea>
<?php elseif ($fd['t'] === 'select'): ?>
              <select name="<?php echo $fn; ?>" class="mv-input">
<?php foreach ($fd['opts'] as $ov => $ol): ?>
                <option value="<?php echo htmlspecialchars_uni($ov); ?>"<?php echo ((string)$val === (string)$ov) ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($ol); ?></option>
<?php endforeach; ?>
              </select>
<?php elseif ($fd['t'] === 'number'): ?>
              <input type="number" name="<?php echo $fn; ?>" class="mv-input" value="<?php echo htmlspecialchars_uni($val === '' ? '0' : $val); ?>">
<?php else: ?>
              <input type="text" name="<?php echo $fn; ?>" class="mv-input" value="<?php echo htmlspecialchars_uni($val); ?>"<?php echo !empty($fd['req']) ? ' required' : ''; ?>>
<?php endif; ?>
            </div>
<?php endforeach; ?>
            <div class="gc-field">
              <label class="mv-lbl">Visible</label>
              <label class="gc-check"><input type="checkbox" name="activo" value="1"<?php echo (!$edit || (int)$edit['activo'] === 1) ? ' checked' : ''; ?>> Mostrar en la web</label>
            </div>
          </div>

          <div class="mv-save-bar">
            <button class="btn btn-primary"><?php echo $edit ? 'Guardar cambios' : 'Crear ' . htmlspecialchars_uni($conf['sing']); ?></button>
<?php if ($edit): ?><a href="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $cat; ?>" class="btn btn-ghost btn-sm">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t"><?php echo htmlspecialchars_uni($conf['label']); ?></span><span class="c">// <?php echo count($rows); ?> registro(s)</span></div>
      <div class="plate-b">
<?php if (empty($rows)): ?>
        <p class="mv-empty">Todavía no hay registros en este catálogo.</p>
<?php else: foreach ($rows as $r): $id = (int)$r['id'];
        $subs = array();
        foreach ($conf['sub'] as $sf) { if (!empty($r[$sf])) $subs[] = htmlspecialchars_uni((string)$r[$sf]); }
?>
        <div class="mv-row<?php echo ((int)$r['activo'] === 0) ? ' gc-inactivo' : ''; ?>">
          <div class="mv-ev-main">
            <span class="mv-mis-t"><?php echo htmlspecialchars_uni($r['nombre']); ?><?php if ((int)$r['activo'] === 0): ?> <span class="gc-oculto">oculto</span><?php endif; ?></span>
<?php if ($subs): ?><span class="mv-ev-meta"><?php echo implode(' · ', $subs); ?></span><?php endif; ?>
          </div>
          <div class="mv-ev-acts">
            <form method="post" action="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $cat; ?>">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo $id; ?>">
              <button class="btn btn-sm btn-ghost"><?php echo ((int)$r['activo'] === 1) ? 'Ocultar' : 'Mostrar'; ?></button>
            </form>
            <a class="btn btn-sm" href="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $cat; ?>&edit=<?php echo $id; ?>">Editar</a>
            <form method="post" action="<?php echo $bburl; ?>/gestionar-catalogos.php?cat=<?php echo $cat; ?>" onsubmit="return confirm('¿Eliminar este registro?');">
              <input type="hidden" name="my_post_key" value="<?php echo $pk; ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $id; ?>">
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
