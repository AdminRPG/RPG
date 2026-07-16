<?php
/**
 * I-Forge · Gestionar personaje (Administrador+)
 * Edición completa on-rol de cualquier personaje, usando los MISMOS catálogos
 * y selectores reales que el wizard de creación (razas, facciones, armas,
 * virtudes, defectos). No permite cambiar uid/propietario.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'gestionar-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/gbe_rol_data.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

require_once MYBB_ROOT . 'inc/gbe_user_init.php';

$rank = gbe_get_staff_level($uid);

if (!$loggedin || $rank < 3) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

$STAT_KEYS  = gbe_rol_stat_keys();
$RAZAS      = gbe_rol_razas();
$FACCIONES  = gbe_rol_facciones();
$ARMAS      = gbe_rol_armas();
$PACKS      = gbe_rol_packs_equipo();
$VIRTUDES   = gbe_rol_virtudes();
$DEFECTOS   = gbe_rol_defectos();
$PC_BASE    = gbe_rol_pc_iniciales();
$ESTADOS    = array('borrador', 'revision', 'aprobado', 'rechazado', 'eliminado');
$RANGOS     = array('F', 'E', 'D', 'C', 'B', 'A', 'S', 'SS', 'M', 'M+');
$pid        = (int) $mybb->get_input('pid', MyBB::INPUT_INT);
$buscar     = trim((string) $mybb->get_input('q'));
$filtro_est = trim((string) $mybb->get_input('estado'));
$flash      = '';
$flash_kind = 'ok';

function gp_clean($s, $max = 4000)
{
    $s = trim((string) $s);
    if (function_exists('mb_substr')) {
        return mb_substr($s, 0, $max, 'UTF-8');
    }
    return substr($s, 0, $max);
}

function gp_estado_label($estado)
{
    switch ($estado) {
        case 'aprobado':  return 'Aprobado';
        case 'revision':  return 'En revisión';
        case 'rechazado': return 'Rechazado';
        case 'eliminado': return 'Eliminado';
        default:          return 'Borrador';
    }
}

// ── POST: acciones sobre un personaje concreto ──
if ($pid > 0 && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $db->table_exists('rol_personajes')) {

    $action = $mybb->get_input('action');

    // ---- Guardar ficha completa ----
    if ($action === 'save') {
        $pq = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
        if (!$db->num_rows($pq)) {
            $flash = 'Personaje no encontrado.';
            $flash_kind = 'warn';
        } else {
            $pj = $db->fetch_array($pq);
            $datos      = $pj['datos'] ? json_decode($pj['datos'], true) : array();
            $inventario = $pj['inventario'] ? json_decode($pj['inventario'], true) : array();
            $economia   = $pj['economia'] ? json_decode($pj['economia'], true) : array();
            $bio        = $pj['bio'] ? json_decode($pj['bio'], true) : array();
            if (!is_array($datos))      $datos = array();
            if (!is_array($inventario)) $inventario = array();
            if (!is_array($economia))   $economia = array();
            if (!is_array($bio))        $bio = array();

            $nombre = gp_clean($mybb->get_input('nombre'), 120);
            if ($nombre === '') {
                $flash = 'El nombre es obligatorio.';
                $flash_kind = 'warn';
            } else {
                $estado = (string) $mybb->get_input('estado');
                if (!in_array($estado, $ESTADOS, true)) {
                    $estado = (string) $pj['estado'];
                }

                $nivel = strtoupper(trim((string) $mybb->get_input('rango')));
                if (!in_array($nivel, $RANGOS, true)) {
                    $nivel = (string) $pj['rango'];
                }

                $nivel  = max(1, (int) $mybb->get_input('nivel', MyBB::INPUT_INT));
                $activo = $mybb->get_input('activo', MyBB::INPUT_INT) ? 1 : 0;

                $avatar = gp_clean($mybb->get_input('avatar'), 255);
                $icono  = gp_clean($mybb->get_input('icono'), 255);
                $firma  = gp_clean($mybb->get_input('firma'), 3000);
                if ($avatar !== '' && !preg_match('~^https?://~i', $avatar)) $avatar = (string) $pj['avatar'];
                if ($icono !== '' && !preg_match('~^https?://~i', $icono))  $icono  = (string) ($pj['icono'] ?? '');

                $rango_faccion = gp_clean($mybb->get_input('rango_faccion'), 60);
                $from_fisico   = gp_clean($mybb->get_input('from_fisico'), 160);
                $desc_fisica   = gp_clean($mybb->get_input('desc_fisica'), 8000);
                $personalidad  = gp_clean($mybb->get_input('personalidad'), 8000);

                // ---- Raza (selector real, con híbrido) ----
                $raza1   = (string) $mybb->get_input('raza_principal');
                $hibrido = $mybb->get_input('hibrido', MyBB::INPUT_INT) ? true : false;
                $raza2   = (string) $mybb->get_input('raza_secundaria');
                if (!isset($RAZAS[$raza1])) $raza1 = (string) ($datos['raza_principal'] ?? '');
                if (!$hibrido || !isset($RAZAS[$raza2]) || $raza2 === $raza1) {
                    $raza2 = '';
                    if (!$hibrido) { /* no-op */ } else { $hibrido = false; }
                }
                $datos['raza_principal']  = $raza1;
                $datos['raza_secundaria'] = $raza2 !== '' ? $raza2 : null;
                $datos['hibrido']         = $hibrido;

                $datos['apodo']   = gp_clean($mybb->get_input('apodo'), 60);
                $datos['edad']    = gp_clean($mybb->get_input('edad'), 40);
                $datos['genero']  = gp_clean($mybb->get_input('genero'), 40);

                // ---- Facción (selector real) ----
                $faccion = (string) $mybb->get_input('faccion');
                $datos['faccion'] = isset($FACCIONES[$faccion]) ? $faccion : (string) ($datos['faccion'] ?? '');

                // ---- Stats efectivas ----
                $stats_in = $mybb->get_input('stats_efectivas');
                if (is_array($stats_in)) {
                    if (!isset($datos['stats_efectivas']) || !is_array($datos['stats_efectivas'])) {
                        $datos['stats_efectivas'] = array();
                    }
                    foreach ($STAT_KEYS as $sk) {
                        if (isset($stats_in[$sk])) {
                            $datos['stats_efectivas'][$sk] = max(5, (int) $stats_in[$sk]);
                        }
                    }
                    $datos['rango_suma'] = gbe_rol_stat_sum($datos['stats_efectivas']);
                    $nivel = gbe_rol_nivel_from_sum((int) ($datos['rango_suma'] ?? 0));
                }

                // ---- Virtudes y defectos (checkboxes reales del catálogo) ----
                $virtudes_in = $mybb->get_input('virtudes', MyBB::INPUT_ARRAY);
                $defectos_in = $mybb->get_input('defectos', MyBB::INPUT_ARRAY);
                if (!is_array($virtudes_in)) $virtudes_in = array();
                if (!is_array($defectos_in)) $defectos_in = array();

                $pc_gastado = 0;
                $virtudes_sel = array();
                foreach ($virtudes_in as $vid) {
                    $v = gbe_rol_find_virtud($vid);
                    if ($v === null) continue;
                    $spec = !empty($v['spec']) ? gp_clean($mybb->get_input('virtud_spec_' . $vid), 200) : '';
                    $pc_gastado += (int) $v['coste'];
                    $virtudes_sel[$vid] = array('nombre' => $v['nombre'], 'coste' => (int) $v['coste'], 'spec' => $spec);
                }
                $pc_devuelto = 0;
                $defectos_sel = array();
                foreach ($defectos_in as $did) {
                    $d = gbe_rol_find_defecto($did);
                    if ($d === null) continue;
                    $spec = !empty($d['spec']) ? gp_clean($mybb->get_input('defecto_spec_' . $did), 200) : '';
                    $pc_devuelto += (int) $d['devuelve'];
                    $defectos_sel[$did] = array('nombre' => $d['nombre'], 'devuelve' => (int) $d['devuelve'], 'spec' => $spec);
                }
                $datos['virtudes']    = $virtudes_sel;
                $datos['defectos']    = $defectos_sel;
                $datos['pc_gastado']  = $pc_gastado;
                $datos['pc_devuelto'] = $pc_devuelto;
                $datos['pc_balance']  = $PC_BASE - $pc_gastado + $pc_devuelto;

                // ---- Equipo (Pack de Equipo Inicial, INI-01 Paso 6) ----
                $pack_equipo = (string) $mybb->get_input('pack_equipo');
                if (isset($PACKS[$pack_equipo])) {
                    $inventario['pack_equipo'] = $pack_equipo;
                }
                $rupies = max(0, (int) $mybb->get_input('rupies', MyBB::INPUT_INT));
                $economia['rupies'] = $rupies;
                $economia['berries'] = $rupies;

                $bio['concepto']   = gp_clean($mybb->get_input('bio_concepto'), 4000);
                $bio['pasado']     = gp_clean($mybb->get_input('bio_pasado'), 8000);
                $bio['motivacion'] = gp_clean($mybb->get_input('bio_motivacion'), 4000);
                $bio['relaciones'] = gp_clean($mybb->get_input('bio_relaciones'), 4000);

                if ($activo && (int) $pj['uid'] > 0) {
                    $db->update_query('rol_personajes', array('activo' => 0), "uid = " . (int) $pj['uid']);
                }

                $slug = gp_clean($mybb->get_input('slug'), 150);
                if ($slug === '') {
                    $slug = function_exists('my_strtolower')
                        ? preg_replace('/[^a-z0-9]+/', '-', my_strtolower($nombre))
                        : preg_replace('/[^a-z0-9]+/', '-', strtolower($nombre));
                    $slug = trim($slug, '-');
                }

                $db->update_query('rol_personajes', array(
                    'nombre'        => $db->escape_string($nombre),
                    'slug'          => $db->escape_string($slug),
                    'estado'        => $db->escape_string($estado),
                    'activo'        => $activo,
                    'rango'         => $db->escape_string($nivel),
                    'nivel'         => $nivel,
                    'avatar'        => $db->escape_string($avatar),
                    'icono'         => $db->escape_string($icono),
                    'firma'         => $db->escape_string($firma),
                    'rango_faccion' => $db->escape_string($rango_faccion),
                    'from_fisico'   => $db->escape_string($from_fisico),
                    'desc_fisica'   => $db->escape_string($desc_fisica),
                    'personalidad'  => $db->escape_string($personalidad),
                    'datos'         => $db->escape_string(json_encode($datos, JSON_UNESCAPED_UNICODE)),
                    'inventario'    => $db->escape_string(json_encode($inventario, JSON_UNESCAPED_UNICODE)),
                    'economia'      => $db->escape_string(json_encode($economia, JSON_UNESCAPED_UNICODE)),
                    'bio'           => $db->escape_string(json_encode($bio, JSON_UNESCAPED_UNICODE)),
                    'lastedit'      => TIME_NOW,
                ), "pid = {$pid}");

                if ($activo && (int) $pj['uid'] > 0 && $db->table_exists('rol_cuentas')) {
                    $exists = $db->simple_select('rol_cuentas', 'uid', 'uid = ' . (int) $pj['uid'], array('limit' => 1));
                    if ($db->num_rows($exists)) {
                        $db->update_query('rol_cuentas', array('personaje_activo' => $pid), 'uid = ' . (int) $pj['uid']);
                    }
                }

                if (function_exists('gbe_combat_recalc')) {
                    gbe_combat_recalc($pid);
                }

                header('Location: ' . $bburl . '/gestionar-personaje.php?pid=' . $pid . '&ok=1#top');
                exit;
            }
        }

    // ---- Añadir PP manual (misiones, arcos, eventos) ----
    } elseif ($action === 'pp_add' && function_exists('gbe_pp_add')) {
        $pp_amt = (int) $mybb->get_input('pp_amount', MyBB::INPUT_INT);
        $pp_tipo = (string) $mybb->get_input('pp_tipo');
        $pp_notas = gp_clean($mybb->get_input('pp_notas'), 500);
        $tipos_ok = array('mision', 'arco', 'evento', 'staff');
        if ($pp_amt > 0 && in_array($pp_tipo, $tipos_ok, true)) {
            gbe_pp_add($pid, $pp_amt, $pp_tipo, 0, 0, 0, $pp_notas, $uid);
        }
        header('Location: ' . $bburl . '/gestionar-personaje.php?pid=' . $pid . '&ok=1#pp');
        exit;

    // ---- Añadir objeto al inventario (encima/almacén) ----
    } elseif ($action === 'inv_add') {
        $loc = $mybb->get_input('item_loc') === 'almacen' ? 'almacen' : 'encima';
        $nom = gp_clean($mybb->get_input('item_nombre'), 100);
        $desc = gp_clean($mybb->get_input('item_desc'), 300);
        $size = (int) $mybb->get_input('item_size', MyBB::INPUT_INT);
        if ($size < 1) $size = 1;
        if ($size > 12) $size = 12;

        if ($nom !== '') {
            $pq = $db->simple_select('rol_personajes', 'inventario', "pid = {$pid}", array('limit' => 1));
            if ($db->num_rows($pq)) {
                $inv = json_decode((string) $db->fetch_field($pq, 'inventario'), true);
                if (!is_array($inv)) $inv = array();
                if (!isset($inv[$loc]) || !is_array($inv[$loc])) $inv[$loc] = array();
                $inv[$loc][] = array('n' => $nom, 'd' => $desc, 'size' => $size);
                $db->update_query('rol_personajes', array(
                    'inventario' => $db->escape_string(json_encode($inv, JSON_UNESCAPED_UNICODE)),
                    'lastedit'   => TIME_NOW,
                ), "pid = {$pid}");
            }
        }
        header('Location: ' . $bburl . '/gestionar-personaje.php?pid=' . $pid . '&ok=1#equipo');
        exit;

    // ---- Quitar objeto del inventario ----
    } elseif ($action === 'inv_remove') {
        $loc = $mybb->get_input('item_loc') === 'almacen' ? 'almacen' : 'encima';
        $idx = $mybb->get_input('item_idx', MyBB::INPUT_INT);
        $pq = $db->simple_select('rol_personajes', 'inventario', "pid = {$pid}", array('limit' => 1));
        if ($db->num_rows($pq)) {
            $inv = json_decode((string) $db->fetch_field($pq, 'inventario'), true);
            if (is_array($inv) && isset($inv[$loc]) && is_array($inv[$loc]) && isset($inv[$loc][$idx])) {
                array_splice($inv[$loc], $idx, 1);
                $inv[$loc] = array_values($inv[$loc]);
                $db->update_query('rol_personajes', array(
                    'inventario' => $db->escape_string(json_encode($inv, JSON_UNESCAPED_UNICODE)),
                    'lastedit'   => TIME_NOW,
                ), "pid = {$pid}");
            }
        }
        header('Location: ' . $bburl . '/gestionar-personaje.php?pid=' . $pid . '&ok=1#equipo');
        exit;
    }
}

if ($mybb->get_input('ok')) {
    $flash = 'Personaje guardado correctamente.';
    $flash_kind = 'ok';
}

// ── Cargar personaje a editar ──
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

$datos      = $pj && $pj['datos'] ? json_decode($pj['datos'], true) : array();
$inventario = $pj && $pj['inventario'] ? json_decode($pj['inventario'], true) : array();
$economia   = $pj && $pj['economia'] ? json_decode($pj['economia'], true) : array();
$bio        = $pj && $pj['bio'] ? json_decode($pj['bio'], true) : array();
if (!is_array($datos))      $datos = array();
if (!is_array($inventario)) $inventario = array();
if (!is_array($economia))   $economia = array();
if (!is_array($bio))        $bio = array();

$owner_name = '—';
if ($pj && (int) $pj['uid'] > 0) {
    $uq = $db->simple_select('users', 'username', 'uid = ' . (int) $pj['uid'], array('limit' => 1));
    if ($db->num_rows($uq)) {
        $owner_name = $db->fetch_field($uq, 'username');
    }
} elseif ($pj && (int) ($pj['es_npc'] ?? 0) === 1) {
    $owner_name = 'NPC sin asignar';
}

// ── Listado de búsqueda ──
$listado = array();
if (!$pj && $db->table_exists('rol_personajes')) {
    $where = '1=1';
    if ($buscar !== '') {
        $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    }
    if ($filtro_est !== '' && in_array($filtro_est, $ESTADOS, true)) {
        $where .= " AND estado = '" . $db->escape_string($filtro_est) . "'";
    }
    $lq = $db->simple_select(
        'rol_personajes',
        'pid, nombre, uid, estado, rango, nivel, es_npc',
        $where,
        array('order_by' => 'nombre', 'order_dir' => 'ASC', 'limit' => 200)
    );
    while ($lrow = $db->fetch_array($lq)) {
        $lrow['owner'] = '?';
        if ((int) $lrow['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int) $lrow['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) {
                $lrow['owner'] = $db->fetch_field($uq, 'username');
            }
        } elseif ((int) ($lrow['es_npc'] ?? 0) === 1) {
            $lrow['owner'] = 'NPC';
        }
        $listado[] = $lrow;
    }
}

$stats_ef  = $datos['stats_efectivas'] ?? $datos['stats_base'] ?? array();
$pp_saldo  = ($pj && function_exists('gbe_pp_saldo'))
    ? gbe_pp_saldo((int) $pj['pid'])
    : array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);
$virtudes_sel_ids = is_array($datos['virtudes'] ?? null) ? $datos['virtudes'] : array();
$defectos_sel_ids = is_array($datos['defectos'] ?? null) ? $datos['defectos'] : array();
$pc_balance = (int) ($datos['pc_balance'] ?? $PC_BASE);

$inv_encima  = is_array($inventario['encima'] ?? null) ? $inventario['encima'] : array();
$inv_almacen = is_array($inventario['almacen'] ?? null) ? $inventario['almacen'] : array();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; <?php echo $pj ? 'Editar: ' . htmlspecialchars_uni($pj['nombre']) : 'Gestionar personaje'; ?></title>
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-zona-staff gbe-pg-crear-personaje gbe-pg-gestionar-personaje">

<?php echo gbe_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a>
    <span class="sep">&#8250;</span>
<?php if ($pj): ?>
    <a href="<?php echo $bburl; ?>/gestionar-personaje.php">Gestionar personaje</a>
    <span class="sep">&#8250;</span>
    <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
<?php else: ?>
    <b>Gestionar personaje</b>
<?php endif; ?>
  </div>
</div>

<div class="wrap" id="top">

  <section class="reveal">
    <div class="shead">
      <h1><?php echo $pj ? 'Editar personaje' : 'Gestionar personaje'; ?></h1>
      <span class="code">// administraci&oacute;n on-rol</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <section class="reveal"><div class="zs-flash" style="<?php echo $flash_kind === 'warn' ? 'background:var(--h6)' : ''; ?>"><?php echo $flash; ?></div></section>
<?php endif; ?>

<?php if (!$pj): ?>
  <section class="reveal">
    <p class="zs-intro">Busca cualquier personaje del foro y edita <b>toda</b> su informaci&oacute;n on-rol con los mismos selectores del wizard de creaci&oacute;n. El propietario (<code>uid</code>) no es editable desde aqu&iacute;.</p>
    <form method="get" action="<?php echo $bburl; ?>/gestionar-personaje.php" class="zs-search">
      <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar por nombre&hellip;">
      <select name="estado" class="zs-staffsel">
        <option value="">Todos los estados</option>
<?php foreach ($ESTADOS as $e): ?>
        <option value="<?php echo $e; ?>"<?php echo $filtro_est === $e ? ' selected' : ''; ?>><?php echo gp_estado_label($e); ?></option>
<?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-hot btn-sm">Buscar</button>
    </form>
  </section>

  <section class="zs-group reveal">
    <div class="zs-group-h">
      <span class="lbl">Personajes</span>
      <span class="need bg-h6"><?php echo count($listado); ?> resultado(s)</span>
      <span class="rule"></span>
    </div>
<?php if (empty($listado)): ?>
    <div class="empty-state"><div class="big">Sin resultados</div><p>Prueba con otro nombre o quita el filtro de estado.</p></div>
<?php else: ?>
    <div class="zs-stafftbl">
<?php foreach ($listado as $row): ?>
      <div class="zs-staffrow">
        <div class="zs-staffwho">
          <span class="zs-staffname"><a href="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $row['pid']; ?>"><?php echo htmlspecialchars_uni($row['nombre']); ?></a></span>
          <span class="zs-staffowner">// <?php echo htmlspecialchars_uni($row['owner']); ?> &middot; <?php echo gp_estado_label($row['estado']); ?> &middot; <?php echo htmlspecialchars_uni($row['rango']); ?><?php if ((int)($row['es_npc'] ?? 0) === 1): ?> &middot; NPC<?php endif; ?></span>
        </div>
        <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $row['pid']; ?>" class="btn btn-ghost btn-sm">Ver ficha</a>
        <a href="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $row['pid']; ?>" class="btn btn-hot btn-sm">Editar</a>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </section>

<?php else: ?>

  <section class="reveal">
    <p class="zs-intro">
      Editando <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b>
      (pid <?php echo (int) $pj['pid']; ?>) &middot; propietario: <b><?php echo htmlspecialchars_uni($owner_name); ?></b>
      (uid <?php echo (int) $pj['uid']; ?>, no editable)
<?php if ((int)($pj['es_npc'] ?? 0) === 1): ?> &middot; <b>NPC</b><?php endif; ?>
    </p>
    <div class="gp-actions">
      <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="btn btn-ghost btn-sm">Ver ficha p&uacute;blica</a>
      <a href="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo (int) $pj['pid']; ?>" class="btn btn-ghost btn-sm">Gesti&oacute;n expediente</a>
      <a href="<?php echo $bburl; ?>/gestionar-personaje.php" class="btn btn-ghost btn-sm">Volver al listado</a>
    </div>
  </section>

  <form method="post" action="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $pj['pid']; ?>" class="gp-form" id="gpForm">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
    <input type="hidden" name="action" value="save">

    <div class="gp-section">
      <div class="gp-section-h">Expediente</div>
      <div class="gp-grid">
        <label class="gp-field"><span>Nombre</span><input type="text" name="nombre" value="<?php echo htmlspecialchars_uni($pj['nombre']); ?>" required></label>
        <label class="gp-field"><span>Slug</span><input type="text" name="slug" value="<?php echo htmlspecialchars_uni($pj['slug']); ?>"></label>
        <label class="gp-field"><span>Estado</span>
          <select name="estado">
<?php foreach ($ESTADOS as $e): ?>
            <option value="<?php echo $e; ?>"<?php echo $pj['estado'] === $e ? ' selected' : ''; ?>><?php echo gp_estado_label($e); ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="gp-field"><span>Rango</span>
          <select name="rango">
<?php foreach ($RANGOS as $r): ?>
            <option value="<?php echo $r; ?>"<?php echo $pj['rango'] === $r ? ' selected' : ''; ?>><?php echo $r; ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="gp-field"><span>Nivel</span><input type="number" name="nivel" min="1" value="<?php echo (int) $pj['nivel']; ?>"></label>
        <label class="gp-field gp-check"><input type="checkbox" name="activo" value="1"<?php echo (int)$pj['activo'] === 1 ? ' checked' : ''; ?>> <span>Personaje activo de la cuenta</span></label>
      </div>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Perfil visual</div>
      <div class="gp-grid">
        <label class="gp-field gp-wide"><span>Avatar (URL)</span><input type="url" name="avatar" value="<?php echo htmlspecialchars_uni($pj['avatar']); ?>"></label>
        <label class="gp-field gp-wide"><span>Icono (URL)</span><input type="url" name="icono" value="<?php echo htmlspecialchars_uni($pj['icono'] ?? ''); ?>"></label>
        <label class="gp-field gp-full"><span>Firma (BBCode)</span><textarea name="firma" rows="3"><?php echo htmlspecialchars_uni($pj['firma'] ?? ''); ?></textarea></label>
      </div>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Crónica (apariencia y personalidad)</div>
      <div class="gp-grid">
        <label class="gp-field"><span>Rango en facci&oacute;n</span><input type="text" name="rango_faccion" value="<?php echo htmlspecialchars_uni($pj['rango_faccion'] ?? ''); ?>"></label>
        <label class="gp-field"><span>From (f&iacute;sico)</span><input type="text" name="from_fisico" value="<?php echo htmlspecialchars_uni($pj['from_fisico'] ?? ''); ?>"></label>
        <label class="gp-field gp-full"><span>Descripci&oacute;n f&iacute;sica (apariencia)</span><textarea name="desc_fisica" rows="4"><?php echo htmlspecialchars_uni($pj['desc_fisica'] ?? ''); ?></textarea></label>
        <label class="gp-field gp-full"><span>Personalidad</span><textarea name="personalidad" rows="4"><?php echo htmlspecialchars_uni($pj['personalidad'] ?? ''); ?></textarea></label>
      </div>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Identidad</div>
      <div class="gp-grid">
        <label class="gp-field"><span>Raza principal</span>
          <select name="raza_principal" id="gpRaza1">
            <option value="">&mdash; elige &mdash;</option>
<?php foreach ($RAZAS as $rid => $r): ?>
            <option value="<?php echo $rid; ?>"<?php echo ($datos['raza_principal'] ?? '') === $rid ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="gp-field gp-check"><input type="checkbox" name="hibrido" value="1" id="gpHibrido"<?php echo !empty($datos['hibrido']) ? ' checked' : ''; ?>> <span>H&iacute;brido</span></label>
        <label class="gp-field" id="gpRaza2Wrap"><span>Raza secundaria</span>
          <select name="raza_secundaria" id="gpRaza2">
            <option value="">&mdash; elige &mdash;</option>
<?php foreach ($RAZAS as $rid => $r): ?>
            <option value="<?php echo $rid; ?>"<?php echo ($datos['raza_secundaria'] ?? '') === $rid ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($r['nombre']); ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="gp-field"><span>Apodo</span><input type="text" name="apodo" value="<?php echo htmlspecialchars_uni($datos['apodo'] ?? ''); ?>"></label>
        <label class="gp-field"><span>Edad</span><input type="text" name="edad" value="<?php echo htmlspecialchars_uni($datos['edad'] ?? ''); ?>"></label>
        <label class="gp-field"><span>G&eacute;nero</span><input type="text" name="genero" value="<?php echo htmlspecialchars_uni($datos['genero'] ?? ''); ?>"></label>
        <label class="gp-field"><span>Facci&oacute;n</span>
          <select name="faccion">
            <option value="">&mdash; elige &mdash;</option>
<?php foreach ($FACCIONES as $fid => $f): ?>
            <option value="<?php echo $fid; ?>"<?php echo ($datos['faccion'] ?? '') === $fid ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($f['nombre']); ?></option>
<?php endforeach; ?>
          </select>
        </label>
      </div>
      <p class="mono fs-68 c-dim mt-8">La "D." en el nombre se otorga eligiendo la virtud <b class="c-paper">Voluntad de D.</b> en Virtudes y Defectos, no es un campo aparte.</p>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Stats efectivas</div>
      <div class="gp-grid gp-stats">
<?php foreach ($STAT_KEYS as $sk): ?>
        <label class="gp-field"><span><?php echo $sk; ?></span><input type="number" name="stats_efectivas[<?php echo $sk; ?>]" min="5" value="<?php echo (int)($stats_ef[$sk] ?? 5); ?>"></label>
<?php endforeach; ?>
      </div>
    </div>

    <div class="gp-section" id="pp">
      <div class="gp-section-h">Puntos de Progreso (PP)</div>
      <p class="gp-hint">Saldo: <b><?php echo (int) $pp_saldo['pp_disponible']; ?></b> disponibles &middot; <?php echo (int) $pp_saldo['pp_total']; ?> ganados &middot; <?php echo (int) $pp_saldo['pp_gastado']; ?> gastados</p>
      <form method="post" action="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $pj['pid']; ?>" class="gp-grid mt-10">
        <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
        <input type="hidden" name="action" value="pp_add">
        <label class="gp-field"><span>Cantidad</span><input type="number" name="pp_amount" min="1" max="999" value="3" required></label>
        <label class="gp-field"><span>Tipo</span>
          <select name="pp_tipo">
            <option value="mision">Misi&oacute;n</option>
            <option value="arco">Arco narrativo</option>
            <option value="evento">Evento</option>
            <option value="staff" selected>Staff / ajuste</option>
          </select>
        </label>
        <label class="gp-field gp-wide"><span>Notas</span><input type="text" name="pp_notas" maxlength="500" placeholder="Motivo del ajuste"></label>
        <div class="gp-field jc-fe">
          <button type="submit" class="btn btn-hot btn-sm">A&ntilde;adir PP</button>
        </div>
      </form>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Virtudes y defectos <span class="gp-hint">// cat&aacute;logo real, igual que en la creaci&oacute;n</span></div>
      <div class="gp-grid">
        <div class="gp-field gp-full">
          <div class="pc-bar" id="pcBar">Balance PC: <span class="pc-num" id="pcNum"><?php echo $pc_balance; ?></span> <span class="mono fs-62 c-ash">(<?php echo (int)$PC_BASE; ?> base &minus; coste virtudes + devuelto por defectos &middot; el staff puede saltarse el l&iacute;mite)</span></div>

          <div class="vd-grid">
            <div class="vd-col" data-vdcol="virtudes">
              <div class="vd-col-h">Virtudes</div>
              <input type="search" class="vd-search" placeholder="Buscar virtud&hellip;" autocomplete="off">
              <div class="vd-empty">Ninguna virtud coincide con la b&uacute;squeda.</div>
<?php $vcat_i = 0; foreach ($VIRTUDES as $cat => $items):
    $cat_has_checked = false;
    foreach ($items as $vid => $v) { if (isset($virtudes_sel_ids[$vid])) { $cat_has_checked = true; break; } }
    $cat_open = ($vcat_i === 0) || $cat_has_checked;
    $vcat_i++;
?>
              <div class="cat-group<?php echo $cat_open ? ' cat-open' : ''; ?>">
                <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?> <span class="cat-count">(<?php echo count($items); ?>)</span></span><span class="chev">&#9662;</span></div>
                <div class="cat-body">
<?php foreach ($items as $vid => $v): $sel = $virtudes_sel_ids[$vid] ?? null; $checked = $sel !== null; ?>
                  <div class="item-row">
                    <input type="checkbox" name="virtudes[]" value="<?php echo $vid; ?>" id="chk_<?php echo $vid; ?>" data-coste="<?php echo (int)$v['coste']; ?>"<?php echo !empty($v['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                    <div class="item-txt">
                      <label for="chk_<?php echo $vid; ?>" class="item-name"><?php echo htmlspecialchars_uni($v['nombre']); ?> <span class="badge cost">-<?php echo (int)$v['coste']; ?> PC</span></label>
                      <div class="item-desc"><?php echo htmlspecialchars_uni($v['desc']); ?></div>
<?php if (!empty($v['spec'])): ?>
                      <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="virtud_spec_<?php echo $vid; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($sel['spec'] ?? ''); ?>"></div>
<?php endif; ?>
                    </div>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>

            <div class="vd-col" data-vdcol="defectos">
              <div class="vd-col-h">Defectos</div>
              <input type="search" class="vd-search" placeholder="Buscar defecto&hellip;" autocomplete="off">
              <div class="vd-empty">Ning&uacute;n defecto coincide con la b&uacute;squeda.</div>
<?php $dcat_i = 0; foreach ($DEFECTOS as $cat => $items):
    $cat_has_checked = false;
    foreach ($items as $did => $d) { if (isset($defectos_sel_ids[$did])) { $cat_has_checked = true; break; } }
    $cat_open = ($dcat_i === 0) || $cat_has_checked;
    $dcat_i++;
?>
              <div class="cat-group<?php echo $cat_open ? ' cat-open' : ''; ?>">
                <div class="cat-h" data-toggle><span><?php echo htmlspecialchars_uni($cat); ?> <span class="cat-count">(<?php echo count($items); ?>)</span></span><span class="chev">&#9662;</span></div>
                <div class="cat-body">
<?php foreach ($items as $did => $d): $sel = $defectos_sel_ids[$did] ?? null; $checked = $sel !== null; ?>
                  <div class="item-row">
                    <input type="checkbox" name="defectos[]" value="<?php echo $did; ?>" id="chk_<?php echo $did; ?>" data-devuelve="<?php echo (int)$d['devuelve']; ?>"<?php echo !empty($d['spec']) ? ' data-spec="1"' : ''; ?><?php echo $checked ? ' checked' : ''; ?>>
                    <div class="item-txt">
                      <label for="chk_<?php echo $did; ?>" class="item-name"><?php echo htmlspecialchars_uni($d['nombre']); ?> <span class="badge back">+<?php echo (int)$d['devuelve']; ?> PC</span></label>
                      <div class="item-desc"><?php echo htmlspecialchars_uni($d['desc']); ?></div>
<?php if (!empty($d['spec'])): ?>
                      <div class="item-spec<?php echo $checked ? ' show' : ''; ?>"><input type="text" name="defecto_spec_<?php echo $did; ?>" maxlength="200" placeholder="Especifica..." value="<?php echo htmlspecialchars_uni($sel['spec'] ?? ''); ?>"></div>
<?php endif; ?>
                    </div>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Equipo y econom&iacute;a</div>
      <div class="gp-grid">
        <label class="gp-field"><span>Pack de Equipo Inicial</span>
          <select name="pack_equipo">
            <option value="">&mdash; elige &mdash;</option>
<?php foreach ($PACKS as $pid => $p): ?>
            <option value="<?php echo $pid; ?>"<?php echo ($inventario['pack_equipo'] ?? '') === $pid ? ' selected' : ''; ?>><?php echo htmlspecialchars_uni($p['nombre']); ?></option>
<?php endforeach; ?>
          </select>
        </label>
        <label class="gp-field"><span>Rupias</span><input type="number" name="rupies" min="0" value="<?php echo (int)($economia['rupies'] ?? $economia['berries'] ?? 0); ?>"></label>
      </div>
<?php if (!empty($inventario['arma']) || !empty($inventario['objeto_personal'])): ?>
      <p class="mono fs-68 c-dim mt-8">Equipo heredado del sistema antiguo (solo lectura): <?php echo htmlspecialchars_uni(trim(($inventario['arma'] ?? '') . ' — ' . ($inventario['objeto_personal'] ?? ''), ' —')); ?></p>
<?php endif; ?>
    </div>

    <div class="gp-section">
      <div class="gp-section-h">Historia</div>
      <div class="gp-grid">
        <label class="gp-field gp-full"><span>Concepto</span><textarea name="bio_concepto" rows="3"><?php echo htmlspecialchars_uni($bio['concepto'] ?? ''); ?></textarea></label>
        <label class="gp-field gp-full"><span>Pasado</span><textarea name="bio_pasado" rows="4"><?php echo htmlspecialchars_uni($bio['pasado'] ?? ($bio['historia_pasado'] ?? '')); ?></textarea></label>
        <label class="gp-field gp-full"><span>Motivaci&oacute;n</span><textarea name="bio_motivacion" rows="3"><?php echo htmlspecialchars_uni($bio['motivacion'] ?? ''); ?></textarea></label>
        <label class="gp-field gp-full"><span>Relaciones</span><textarea name="bio_relaciones" rows="3"><?php echo htmlspecialchars_uni($bio['relaciones'] ?? ''); ?></textarea></label>
      </div>
    </div>

    <div class="gp-submit">
      <button type="submit" class="btn btn-hot">Guardar cambios</button>
    </div>
  </form>

  <!-- INVENTARIO: añadir / quitar objetos (fuera del form principal: acción propia) -->
  <section class="gp-section reveal" id="equipo">
    <div class="gp-section-h">Inventario <span class="gp-hint">// objetos que lleva encima o en el almac&eacute;n</span></div>
    <div class="gp-grid">
<?php
      $inv_cols = array(
          'encima'  => array('Lleva encima', $inv_encima),
          'almacen' => array('Almac&eacute;n', $inv_almacen),
      );
      foreach ($inv_cols as $loc => $col):
          list($col_lbl, $col_items) = $col;
?>
      <div class="gp-field gp-wide">
        <span><?php echo $col_lbl; ?> (<?php echo count($col_items); ?>)</span>
<?php if (empty($col_items)): ?>
        <p class="mono fs-74 c-dim my-6">Vac&iacute;o.</p>
<?php else: foreach ($col_items as $i => $it): ?>
        <div class="gp-inv-item">
          <div class="gp-inv-item-b">
            <span class="gp-inv-n"><?php echo htmlspecialchars_uni($it['n'] ?? ''); ?></span>
<?php if (!empty($it['d'])): ?><span class="gp-inv-d"><?php echo htmlspecialchars_uni($it['d']); ?></span><?php endif; ?>
            <span class="gp-inv-size">size <?php echo (int)($it['size'] ?? 1); ?></span>
          </div>
          <form method="post" action="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="action" value="inv_remove">
            <input type="hidden" name="item_loc" value="<?php echo $loc; ?>">
            <input type="hidden" name="item_idx" value="<?php echo (int) $i; ?>">
            <button type="submit" class="btn btn-danger btn-sm">Quitar</button>
          </form>
        </div>
<?php endforeach; endif; ?>
      </div>
<?php endforeach; ?>
    </div>

    <form method="post" action="<?php echo $bburl; ?>/gestionar-personaje.php?pid=<?php echo (int) $pj['pid']; ?>" class="gp-grid mt-10 gp-form-inv-add">
      <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
      <input type="hidden" name="action" value="inv_add">
      <label class="gp-field"><span>Nombre del objeto</span><input type="text" name="item_nombre" maxlength="100" required></label>
      <label class="gp-field"><span>Descripci&oacute;n</span><input type="text" name="item_desc" maxlength="300"></label>
      <label class="gp-field"><span>Tama&ntilde;o (slots)</span><input type="number" name="item_size" min="1" max="12" value="1"></label>
      <label class="gp-field"><span>Ubicaci&oacute;n</span>
        <select name="item_loc">
          <option value="encima">Lleva encima</option>
          <option value="almacen">Almac&eacute;n</option>
        </select>
      </label>
      <div class="gp-field jc-fe">
        <button type="submit" class="btn btn-hot btn-sm">A&ntilde;adir objeto</button>
      </div>
    </form>
    <p class="gp-hint mt-10">Sistema de cartas/t&eacute;cnicas: pendiente de implementar. Cuando exista, se gestionar&aacute; tambi&eacute;n desde aqu&iacute;.</p>
  </section>

<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  // ---- Híbrido: bloquea raza secundaria si no hay híbrido ----
  var hib = document.getElementById('gpHibrido');
  var r2wrap = document.getElementById('gpRaza2Wrap');
  var r1 = document.getElementById('gpRaza1');
  var r2 = document.getElementById('gpRaza2');
  function syncHibrido(){
    if (!hib || !r2wrap) return;
    r2wrap.style.opacity = hib.checked ? '1' : '.45';
    if (r2) r2.disabled = !hib.checked;
  }
  if (hib) { hib.addEventListener('change', syncHibrido); syncHibrido(); }

  // ---- PC bar (virtudes/defectos) ----
  var PC_BASE = <?php echo (int)$PC_BASE; ?>;
  function recomputePc(){
    var bar = document.getElementById('pcBar');
    if (!bar) return;
    var gastado = 0, devuelto = 0;
    document.querySelectorAll('input[data-coste]:checked').forEach(function(c){ gastado += parseInt(c.dataset.coste,10)||0; });
    document.querySelectorAll('input[data-devuelve]:checked').forEach(function(c){ devuelto += parseInt(c.dataset.devuelve,10)||0; });
    var balance = PC_BASE - gastado + devuelto;
    document.getElementById('pcNum').textContent = balance;
    bar.classList.toggle('bad', balance < 0);

    var r1c = document.getElementById('chk_V-RIQ-01');
    var r2c = document.getElementById('chk_V-RIQ-02');
    var r3c = document.getElementById('chk_V-RIQ-03');
    if (r2c) r2c.disabled = !(r1c && r1c.checked) && !r2c.checked;
    if (r3c) r3c.disabled = !(r2c && r2c.checked) && !r3c.checked;
  }
  document.querySelectorAll('input[data-coste],input[data-devuelve]').forEach(function(c){
    c.addEventListener('change', function(){
      if (c.dataset.spec === '1'){
        var specBox = c.closest('.item-row').querySelector('.item-spec');
        if (specBox) specBox.classList.toggle('show', c.checked);
      }
      recomputePc();
    });
  });
  recomputePc();

  // ---- Categorías colapsables (Virtudes/Defectos) ----
  document.querySelectorAll('[data-toggle]').forEach(function(h){
    h.addEventListener('click', function(){
      h.closest('.cat-group').classList.toggle('cat-open');
    });
  });

  // ---- Búsqueda dinámica dentro de cada columna (Virtudes / Defectos) ----
  document.querySelectorAll('.vd-search').forEach(function(inp){
    var col = inp.closest('.vd-col');
    var empty = col.querySelector('.vd-empty');
    inp.addEventListener('input', function(){
      var q = inp.value.trim().toLowerCase();
      var anyVisible = false;
      col.querySelectorAll('.cat-group').forEach(function(g){
        var groupMatch = false;
        g.querySelectorAll('.item-row').forEach(function(row){
          var name = row.querySelector('.item-name').textContent.toLowerCase();
          var match = q === '' || name.indexOf(q) !== -1;
          row.style.display = match ? '' : 'none';
          if (match) groupMatch = true;
        });
        g.style.display = groupMatch ? '' : 'none';
        if (q !== '') g.classList.toggle('cat-open', groupMatch);
        if (groupMatch) anyVisible = true;
      });
      empty.style.display = anyVisible ? 'none' : 'block';
    });
  });
})();

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
