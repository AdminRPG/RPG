<?php
/**
 * One Piece: Eternal · Panel Staff: Tablon de Misiones (STF-07)
 * Catalogo de misiones (crear/editar/ocultar) + tomas de jugadores
 * (aprobar solicitudes, cerrar en curso como completada/fallida).
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-misiones.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

$staff = $uid > 0
    ? ope_rol_active_staff($uid)
    : array('pid' => 0, 'rol' => '', 'narrador' => 0, 'rank' => 0, 'is_staff' => false, 'nombre' => '');
$is_staff   = !empty($staff['is_staff']);
$staff_rank = (int) ($staff['rank'] ?? 0);

// Cualquier staff puede gestionar el tablon (rank >= 1)
if (!$is_staff || $staff_rank < 1) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$flash = '';
$flash_ok = false;

// Datos de catalogo para selects
$islas = array();
if (function_exists('ope_islas_catalogo')) {
    $islas = ope_islas_catalogo();
}

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesion del formulario caduco.';
    } else {
        $action = $mybb->get_input('action');

        if ($action === 'crear_mision') {
            $res = ope_mision_crear($uid, array(
                'titulo'            => $mybb->get_input('titulo'),
                'resumen'           => $mybb->get_input('resumen'),
                'descripcion_larga' => $mybb->get_input('descripcion_larga'),
                'zona_slug'         => $mybb->get_input('zona_slug'),
                'facciones'         => $mybb->get_input('facciones'),
                'recompensa'        => $mybb->get_input('recompensa'),
                'rango'             => $mybb->get_input('rango'),
                'peligrosidad'      => (int) $mybb->get_input('peligrosidad', MyBB::INPUT_INT),
                'modalidad'         => $mybb->get_input('modalidad'),
            ));
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'editar_mision') {
            $mision_id = (int) $mybb->get_input('mision_id', MyBB::INPUT_INT);
            $res = ope_mision_editar($mision_id, array(
                'titulo'            => $mybb->get_input('titulo'),
                'resumen'           => $mybb->get_input('resumen'),
                'descripcion_larga' => $mybb->get_input('descripcion_larga'),
                'zona_slug'         => $mybb->get_input('zona_slug'),
                'facciones'         => $mybb->get_input('facciones'),
                'recompensa'        => $mybb->get_input('recompensa'),
                'rango'             => $mybb->get_input('rango'),
                'peligrosidad'      => (int) $mybb->get_input('peligrosidad', MyBB::INPUT_INT),
                'modalidad'         => $mybb->get_input('modalidad'),
                'estado'            => $mybb->get_input('estado'),
            ));
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'set_estado') {
            $mision_id = (int) $mybb->get_input('mision_id', MyBB::INPUT_INT);
            $res = ope_mision_set_estado($mision_id, $mybb->get_input('estado'));
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'aprobar_toma') {
            $res = ope_mision_toma_aprobar((int) $mybb->get_input('toma_id', MyBB::INPUT_INT), $uid);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'aprobar_batch') {
            $ids = $mybb->get_input('toma_ids', MyBB::INPUT_ARRAY);
            $ids_arr = is_array($ids) ? array_map('intval', $ids) : array();
            $res = ope_mision_tomas_aprobar_batch($ids_arr, $uid);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'rechazar_toma') {
            $res = ope_mision_toma_rechazar(
                (int) $mybb->get_input('toma_id', MyBB::INPUT_INT),
                $uid,
                $mybb->get_input('motivo')
            );
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        } elseif ($action === 'cerrar_toma') {
            $res = ope_mision_toma_cerrar(
                (int) $mybb->get_input('toma_id', MyBB::INPUT_INT),
                $uid,
                $mybb->get_input('resultado'),
                $mybb->get_input('motivo')
            );
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
        }
    }
}

// Datos para pintar
$misiones = array();
if ($db->table_exists('rol_misiones')) {
    $q = $db->query("
        SELECT m.*
        FROM {$db->table_prefix}rol_misiones m
        ORDER BY m.estado ASC, m.rango ASC, m.dateline DESC
    ");
    while ($r = $db->fetch_array($q)) {
        $misiones[] = $r;
    }
}
$tomas_pendientes = ope_misiones_tomas('pendiente');
$tomas_curso = ope_misiones_tomas('en_proceso');
$tomas_cerradas = array_merge(
    ope_misiones_tomas('completada'),
    ope_misiones_tomas('fallida'),
    ope_misiones_tomas('rechazada'),
    ope_misiones_tomas('cancelada')
);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Tablon de Misiones</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Tablon de Misiones</b>
</div></div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Tablon de Misiones</h1>
      <span class="code">// panel STF-07</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'ok' : 'error'; ?>">
    <?php echo htmlspecialchars_uni($flash); ?>
  </div>
<?php endif; ?>

  <!-- CREAR MISION -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Nueva mision</span><span class="c">// escribir encargo</span></div>
      <div class="plate-b">
        <form method="post">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="action" value="crear_mision">
          <div class="zs-form-row">
            <div class="col-grow">
              <div class="zs-form-group">
                <label>Titulo *</label>
                <input type="text" name="titulo" class="zs-control" placeholder="Ej. La flota de los niños" required>
              </div>
            </div>
            <div class="col-grow">
              <div class="zs-form-group">
                <label>Zona / Isla</label>
                <select name="zona_slug" class="zs-control">
                  <option value="">— Sin zona fija —</option>
<?php foreach ($islas as $isl): ?>
                  <option value="<?php echo htmlspecialchars_uni($isl['slug']); ?>"><?php echo htmlspecialchars_uni($isl['nombre']); ?> (<?php echo htmlspecialchars_uni($isl['region']); ?>)</option>
<?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="zs-form-row">
            <div class="col-grow">
              <div class="zs-form-group">
                <label>Resumen (visto en el tablon)</label>
                <textarea name="resumen" rows="2" class="zs-control" placeholder="Gancho corto para el jugador"></textarea>
              </div>
            </div>
          </div>
          <div class="zs-form-row col-top">
            <div class="col-grow">
              <div class="zs-form-group">
                <label>Descripcion larga (opcional)</label>
                <textarea name="descripcion_larga" rows="3" class="zs-control" placeholder="Conflicto, NPCs clave, detalle narrativo"></textarea>
              </div>
            </div>
          </div>
          <div class="zs-form-row">
            <div class="col-narrow"><div class="zs-form-group"><label>Rango</label>
              <select name="rango" class="zs-control">
                <option value="S">S</option><option value="A">A</option><option value="B">B</option>
                <option value="C">C</option><option value="D" selected>D</option>
              </select>
            </div></div>
            <div class="col-narrow"><div class="zs-form-group"><label>Peligro</label>
              <select name="peligrosidad" class="zs-control">
<?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?>/5</option>
<?php endfor; ?>
              </select>
            </div></div>
            <div class="col-medium"><div class="zs-form-group"><label>Modalidad</label>
              <select name="modalidad" class="zs-control">
                <option value="cualquiera" selected>Solo o grupo</option>
                <option value="solo">Solo</option>
                <option value="grupo">Grupo</option>
              </select>
            </div></div>
            <div class="col-grow"><div class="zs-form-group"><label>Recompensa</label>
              <input type="text" name="recompensa" class="zs-control" placeholder="Ej. 1.200 Berries, +15 renombre">
            </div></div>
          </div>
          <div class="zs-form-row">
            <div class="col-grow"><div class="zs-form-group"><label>Facciones</label>
              <input type="text" name="facciones" class="zs-control" placeholder="Pirata, Marine, Revolucionario...">
            </div></div>
          </div>
          <button type="submit" class="btn btn-hot">Publicar mision</button>
        </form>
      </div>
    </div>
  </section>

  <!-- TOMAS PENDIENTES -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Solicitudes de toma (<?php echo count($tomas_pendientes); ?>)</span><span class="c">// pendientes de aprobar</span></div>
      <div class="plate-b">
<?php if (empty($tomas_pendientes)): ?>
        <p class="zs-empty-state-p">Sin solicitudes pendientes.</p>
<?php else: ?>
        <form method="post" id="batch-form">
        <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
        <input type="hidden" name="action" value="aprobar_batch">
        <div class="zs-form-row zs-batch-bar">
          <button type="button" class="btn btn-ghost btn-sm" onclick="document.querySelectorAll('#batch-form .zs-batch-check').forEach(c=>c.checked=true);">Marcar todo</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="document.querySelectorAll('#batch-form .zs-batch-check').forEach(c=>c.checked=false);">Desmarcar todo</button>
          <button type="submit" class="btn btn-hot btn-sm" onclick="return confirm('Aprobar las tomas seleccionadas? Se crearan los hilos y se generara la IA.');">Aprobar seleccion</button>
        </div>
        <div class="zs-stafftbl">
<?php foreach ($tomas_pendientes as $t): ?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow zs-batch-check-wrap">
              <input type="checkbox" name="toma_ids[]" value="<?php echo (int) $t['toma_id']; ?>" class="zs-batch-check">
              <div>
                <span class="zs-staffname"><?php echo htmlspecialchars_uni($t['mision_titulo'] ?? '?'); ?></span>
                <span class="zs-staffowner">Solicita: <?php echo htmlspecialchars_uni($t['pid_nombre']); ?> &middot; <?php echo my_date('relative', (int) $t['dateline']); ?></span>
              </div>
            </div>
            <div class="zs-pj-actions">
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="aprobar_toma">
                <input type="hidden" name="toma_id" value="<?php echo (int) $t['toma_id']; ?>">
                <button type="submit" class="btn btn-hot btn-sm" onclick="return confirm('Aprobar individual? Se creara hilo + IA.');">Aprobar</button>
              </form>
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="rechazar_toma">
                <input type="hidden" name="toma_id" value="<?php echo (int) $t['toma_id']; ?>">
                <input type="text" name="motivo" placeholder="Motivo..." class="zs-control zs-input-reject" required>
                <button type="submit" class="btn btn-ghost btn-sm zs-txt-danger">Rechazar</button>
              </form>
            </div>
          </div>
<?php endforeach; ?>
        </div>
        </form>
<?php endif; ?>
      </div>
    </div>
  </section>

  <!-- EN CURSO -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones en curso (<?php echo count($tomas_curso); ?>)</span><span class="c">// asignadas y jugandose</span></div>
      <div class="plate-b">
<?php if (empty($tomas_curso)): ?>
        <p class="zs-empty-state-p">Ninguna mision en curso.</p>
<?php else: ?>
        <div class="zs-stafftbl">
<?php foreach ($tomas_curso as $t): ?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname"><?php echo htmlspecialchars_uni($t['mision_titulo'] ?? '?'); ?></span>
              <span class="zs-staffowner">Juega: <?php echo htmlspecialchars_uni($t['pid_nombre']); ?> &middot; tomada <?php echo my_date('relative', (int) $t['dateline']); ?></span>
            </div>
            <div class="zs-pj-actions">
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="cerrar_toma">
                <input type="hidden" name="toma_id" value="<?php echo (int) $t['toma_id']; ?>">
                <input type="hidden" name="resultado" value="completada">
                <input type="text" name="motivo" placeholder="Nota..." class="zs-control zs-input-reject">
                <button type="submit" class="btn btn-hot btn-sm" onclick="return confirm('Marcar como completada?');">Completada</button>
              </form>
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="cerrar_toma">
                <input type="hidden" name="toma_id" value="<?php echo (int) $t['toma_id']; ?>">
                <input type="hidden" name="resultado" value="fallida">
                <button type="submit" class="btn btn-ghost btn-sm zs-txt-danger" onclick="return confirm('Marcar como fallida?');">Fallida</button>
              </form>
            </div>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CATALOGO / LISTADO -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Misiones del tablon (<?php echo count($misiones); ?>)</span><span class="c">// gestion</span></div>
      <div class="plate-b">
<?php if (empty($misiones)): ?>
        <p class="zs-empty-state-p">Sin misiones creadas. Usa el formulario de arriba.</p>
<?php else: ?>
        <div class="zs-stafftbl">
<?php foreach ($misiones as $m): ?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">
                <?php echo htmlspecialchars_uni($m['titulo']); ?>
                <?php if (($m['estado'] ?? '') === 'inactiva'): ?><span class="zs-badge zs-badge-inactive">Oculta</span><?php endif; ?>
              </span>
              <span class="zs-staffowner">Rango <?php echo ope_mision_rango_label((string) $m['rango']); ?> &middot; Peligro <?php echo (int) $m['peligrosidad']; ?>/5 &middot; <?php echo ope_mision_modalidad_label((string) $m['modalidad']); ?></span>
            </div>
            <div class="zs-pj-actions">
              <form method="post" class="zs-form-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="set_estado">
                <input type="hidden" name="mision_id" value="<?php echo (int) $m['mision_id']; ?>">
                <input type="hidden" name="estado" value="<?php echo ($m['estado'] ?? '') === 'inactiva' ? 'publicada' : 'inactiva'; ?>">
                <button type="submit" class="btn btn-ghost btn-sm"><?php echo ($m['estado'] ?? '') === 'inactiva' ? 'Publicar' : 'Ocultar'; ?></button>
              </form>
            </div>
          </div>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
    </div>
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