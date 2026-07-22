<?php
/**
 * One Piece: Eternal · Panel Staff: Gestión de Personajes (STF-03)
 * Permite buscar, filtrar, editar estado, rol de staff y borrar personajes sin escribir IDs a mano.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-personajes.php');
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

if (!$is_staff && $uid !== 1) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$flash = '';
$flash_ok = false;

// Manejo de POST (Acciones de Gestión)
if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó. Inténtalo de nuevo.';
    } else {
        $action = $mybb->get_input('action');
        $target_pid = (int) $mybb->get_input('pid', MyBB::INPUT_INT);

        if ($action === 'borrar_personaje' && $target_pid > 0) {
            // 1. Obtener datos del personaje antes de borrar
            $pq = $db->simple_select('rol_personajes', '*', "pid = {$target_pid}", array('limit' => 1));
            if ($db->num_rows($pq)) {
                $pj = $db->fetch_array($pq);
                $pj_name = (string) $pj['nombre'];
                $owner_uid = (int) $pj['uid'];

                // 2. Liberar Akuma no Mi si tenía una
                if (function_exists('ope_fruta_liberar')) {
                    ope_fruta_liberar($target_pid);
                }

                // 3. Borrar registros asociados
                if ($db->table_exists('rol_pj_fruta')) {
                    $db->delete_query('rol_pj_fruta', "pid = {$target_pid}");
                }
                if ($db->table_exists('rol_tramites')) {
                    $db->delete_query('rol_tramites', "pid = {$target_pid}");
                }
                if ($db->table_exists('rol_pj_eternal')) {
                    $db->delete_query('rol_pj_eternal', "pid = {$target_pid}");
                }

                // 4. Borrar personaje principal
                $db->delete_query('rol_personajes', "pid = {$target_pid}");

                // 5. Ajustar personaje activo en la cuenta si era el activo
                if ($db->table_exists('rol_cuentas') && $owner_uid > 0) {
                    $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$owner_uid}", array('limit' => 1));
                    if ($db->num_rows($cq)) {
                        $curr_active = (int) $db->fetch_field($cq, 'personaje_activo');
                        if ($curr_active === $target_pid) {
                            $next_q = $db->simple_select('rol_personajes', 'pid', "uid = {$owner_uid} AND estado = 'aprobado'", array('limit' => 1));
                            $next_pid = $db->num_rows($next_q) ? (int) $db->fetch_field($next_q, 'pid') : 0;
                            $db->update_query('rol_cuentas', array('personaje_activo' => $next_pid), "uid = {$owner_uid}");
                        }
                    }
                }

                $flash = "Personaje \"{$pj_name}\" (PID #{$target_pid}) eliminado correctamente.";
                $flash_ok = true;
            } else {
                $flash = "Personaje PID #{$target_pid} no encontrado.";
            }
        } elseif ($action === 'cambiar_estado' && $target_pid > 0) {
            $nuevo_estado = (string) $mybb->get_input('nuevo_estado');
            if (in_array($nuevo_estado, array('aprobado', 'revision', 'rechazado', 'borrador'), true)) {
                $db->update_query('rol_personajes', array('estado' => $nuevo_estado, 'lastedit' => TIME_NOW), "pid = {$target_pid}");
                $flash = "Estado del personaje PID #{$target_pid} actualizado a \"{$nuevo_estado}\".";
                $flash_ok = true;
            }
        } elseif ($action === 'cambiar_staff' && $target_pid > 0) {
            $new_rank = max(0, min(4, (int) $mybb->get_input('staff_level', MyBB::INPUT_INT)));
            $new_rol  = trim((string) $mybb->get_input('staff_rol'));
            $db->update_query('rol_personajes', array(
                'staff_level' => $new_rank,
                'staff_rol'   => $db->escape_string($new_rol),
                'lastedit'    => TIME_NOW,
            ), "pid = {$target_pid}");

            // Sincronizar nivel de staff en rol_cuentas para el dueño
            $pq = $db->simple_select('rol_personajes', 'uid', "pid = {$target_pid}", array('limit' => 1));
            if ($db->num_rows($pq)) {
                $owner_uid = (int) $db->fetch_field($pq, 'uid');
                if ($owner_uid > 0 && $db->table_exists('rol_cuentas')) {
                    $db->update_query('rol_cuentas', array('staff_level' => $new_rank), "uid = {$owner_uid}");
                }
            }
            $flash = "Rol de Staff del personaje PID #{$target_pid} actualizado (Rank {$new_rank}).";
            $flash_ok = true;
        } elseif ($action === 'editar_rapido' && $target_pid > 0) {
            $nuevo_nombre = trim((string) $mybb->get_input('nombre'));
            $nuevo_nivel  = max(1, min(100, (int) $mybb->get_input('nivel', MyBB::INPUT_INT)));
            $nueva_faccion = trim((string) $mybb->get_input('faccion_slug'));

            if ($nuevo_nombre !== '') {
                $update_data = array(
                    'nombre'       => $db->escape_string($nuevo_nombre),
                    'nivel'        => $nuevo_nivel,
                    'faccion_slug' => $db->escape_string($nueva_faccion),
                    'lastedit'     => TIME_NOW,
                );
                $db->update_query('rol_personajes', $update_data, "pid = {$target_pid}");
                $flash = "Datos del personaje PID #{$target_pid} (\"{$nuevo_nombre}\") actualizados.";
                $flash_ok = true;
            }
        }
    }
}

// Cargar todos los personajes para el listado interactivo
$personajes = array();
$stats_counts = array('total' => 0, 'aprobado' => 0, 'revision' => 0, 'rechazado' => 0, 'borrador' => 0);

if ($db->table_exists('rol_personajes')) {
    $pref = TABLE_PREFIX;
    $sql = "SELECT p.*, u.username AS owner_name, ak.nombre AS fruta_nombre, f.fruta_id, ak.imagen AS fruta_imagen, ak.tipo AS fruta_tipo
            FROM {$pref}rol_personajes p
            LEFT JOIN {$pref}users u ON u.uid = p.uid
            LEFT JOIN {$pref}rol_pj_fruta f ON f.pid = p.pid
            LEFT JOIN {$pref}rol_akuma ak ON ak.id = f.fruta_id
            ORDER BY p.dateline DESC";
    $q = $db->query($sql);
    while ($row = $db->fetch_array($q)) {
        $st = (string) ($row['estado'] ?? 'borrador');
        if (isset($stats_counts[$st])) {
            $stats_counts[$st]++;
        }
        $stats_counts['total']++;
        $row['owner_name'] = (string) ($row['owner_name'] ?? ('UID #' . $row['uid']));
        $personajes[] = $row;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Gestión de Personajes</title>
<?php echo ope_rol_head_base(); ?>
<style>
.zs-mgr-toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; margin-bottom:20px; background:var(--iron-hi); padding:12px 16px; border-radius:12px; border:1px solid var(--rivet); }
.zs-mgr-search { flex:1; min-width:240px; }
.zs-mgr-search input { width:100%; padding:8px 14px; border-radius:8px; background:var(--iron); border:1px solid var(--rivet); color:var(--paper); font-size:.9rem; }
.zs-pills { display:flex; gap:6px; flex-wrap:wrap; }
.zs-pill { padding:6px 12px; border-radius:20px; font-size:.78rem; font-family:var(--mono); background:var(--iron); border:1px solid var(--rivet); color:var(--paper-dim); cursor:pointer; transition:all .15s; }
.zs-pill.on, .zs-pill:hover { background:var(--gold-dim); border-color:var(--gold); color:var(--paper-hi); }
.zs-pj-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px; }
.zs-pj-card { background:var(--iron-hi); border:1px solid var(--rivet); border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:12px; position:relative; transition:border-color .15s, transform .15s; }
.zs-pj-card:hover { border-color:var(--gold-dim); transform:translateY(-2px); }
.zs-pj-head { display:flex; gap:12px; align-items:center; }
.zs-pj-avatar { width:48px; height:48px; border-radius:10px; background:var(--concrete); display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:1.2rem; overflow:hidden; flex-shrink:0; border:1px solid var(--rivet); }
.zs-pj-avatar img { width:100%; height:100%; object-fit:cover; }
.zs-pj-info { flex:1; min-width:0; }
.zs-pj-name { font-family:var(--disp); font-weight:700; font-size:1.05rem; color:var(--paper); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.zs-pj-owner { font-size:.75rem; font-family:var(--mono); color:var(--paper-dim); }
.zs-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:.7rem; font-family:var(--mono); text-transform:uppercase; font-weight:bold; }
.zs-badge.aprobado { background:rgba(46,160,67,0.2); color:#3fb950; border:1px solid rgba(46,160,67,0.4); }
.zs-badge.revision { background:rgba(210,153,34,0.2); color:#d29922; border:1px solid rgba(210,153,34,0.4); }
.zs-badge.rechazado { background:rgba(248,81,73,0.2); color:#f85149; border:1px solid rgba(248,81,73,0.4); }
.zs-badge.borrador { background:rgba(139,148,158,0.2); color:#8b949e; border:1px solid rgba(139,148,158,0.4); }
.zs-pj-meta { display:flex; gap:12px; font-size:.8rem; color:var(--paper-dim); border-top:1px solid var(--rivet); border-bottom:1px solid var(--rivet); padding:8px 0; }
.zs-pj-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

/* Modal Drawer / Dialog Styles */
.zs-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.75); backdrop-filter:blur(4px); z-index:9999; display:flex; align-items:center; justify-content:center; padding:16px; opacity:0; pointer-events:none; transition:opacity .2s; }
.zs-modal-overlay.open { opacity:1; pointer-events:auto; }
.zs-modal-box { background:var(--iron-hi); border:1px solid var(--rivet); border-radius:14px; max-width:540px; width:100%; max-height:90vh; overflow-y:auto; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.5); }
.zs-modal-h { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--rivet); padding-bottom:12px; }
.zs-modal-h h3 { margin:0; font-family:var(--disp); font-size:1.2rem; color:var(--paper); }
.zs-modal-close { background:none; border:none; color:var(--paper-dim); font-size:1.4rem; cursor:pointer; }
.zs-form-group { margin-bottom:14px; }
.zs-form-group label { display:block; font-size:.8rem; font-family:var(--mono); color:var(--paper-dim); margin-bottom:6px; }
.zs-form-group input, .zs-form-group select { width:100%; padding:8px 12px; border-radius:8px; background:var(--iron); border:1px solid var(--rivet); color:var(--paper); }
</style>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Gestión de Personajes</b>
</div></div>

<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Gestión de Personajes</h1>
      <span class="code">// panel de administración STF-03</span>
      <span class="rule"></span>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'flash-ok' : 'flash-err'; ?>" style="margin-bottom:20px;padding:12px 16px;border-radius:10px;background:<?php echo $flash_ok ? 'rgba(46,160,67,0.15)' : 'rgba(248,81,73,0.15)'; ?>;border:1px solid <?php echo $flash_ok ? '#3fb950' : '#f85149'; ?>;color:var(--paper);">
    <?php echo htmlspecialchars_uni($flash); ?>
  </div>
<?php endif; ?>

  <section class="reveal">
    <div class="zs-mgr-toolbar">
      <div class="zs-mgr-search">
        <input type="text" id="pjSearchInput" placeholder="Buscar por nombre, usuario, facción..." autocomplete="off">
      </div>
      <div class="zs-pills" id="pjFilterPills">
        <button type="button" class="zs-pill on" data-filter="todos">Todos (<?php echo $stats_counts['total']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="aprobado">Aprobados (<?php echo $stats_counts['aprobado']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="revision">En revisión (<?php echo $stats_counts['revision']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="rechazado">Rechazados (<?php echo $stats_counts['rechazado']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="borrador">Borradores (<?php echo $stats_counts['borrador']; ?>)</button>
      </div>
    </div>

    <div class="zs-pj-grid" id="pjGrid">
<?php if (empty($personajes)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:40px 20px;background:var(--iron-hi);border:1px dashed var(--rivet);border-radius:12px;">
        <div style="font-size:2.5rem;margin-bottom:8px;">🏴‍☠️</div>
        <div style="font-family:var(--disp);font-size:1.1rem;color:var(--paper);font-weight:bold;margin-bottom:6px;">No hay personajes registrados en la base de datos</div>
        <p style="color:var(--paper-dim);font-size:.88rem;margin-bottom:16px;">Acabas de limpiar los personajes de prueba o aún no se ha creado ninguna ficha.</p>
        <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">+ Crear nuevo personaje</a>
      </div>
<?php endif; ?>
<?php foreach ($personajes as $pj):
    $st = (string) ($pj['estado'] ?? 'borrador');
    $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1)) : strtoupper(substr($pj['nombre'], 0, 1));
    $avatar = trim((string) ($pj['avatar'] ?? ''));
    $json_data = htmlspecialchars_uni(json_encode($pj, JSON_UNESCAPED_UNICODE));
?>
      <div class="zs-pj-card" data-pid="<?php echo (int) $pj['pid']; ?>" data-estado="<?php echo $st; ?>" data-search="<?php echo htmlspecialchars_uni(mb_strtolower($pj['nombre'] . ' ' . $pj['owner_name'] . ' ' . $pj['faccion_slug'])); ?>">
        <div class="zs-pj-head">
          <div class="zs-pj-avatar">
<?php if ($avatar !== ''): ?>
            <img src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="" loading="lazy" onerror="this.remove();">
<?php else: ?>
            <span><?php echo htmlspecialchars_uni($initial); ?></span>
<?php endif; ?>
          </div>
          <div class="zs-pj-info">
            <div class="zs-pj-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></div>
            <div class="zs-pj-owner">Usuario: <b><?php echo htmlspecialchars_uni($pj['owner_name']); ?></b> (PID #<?php echo (int) $pj['pid']; ?>)</div>
            <div style="margin-top:4px;">
              <span class="zs-badge <?php echo $st; ?>"><?php echo htmlspecialchars_uni($st); ?></span>
<?php if ((int) ($pj['staff_level'] ?? 0) > 0): ?>
              <span class="zs-badge" style="background:rgba(187,134,252,0.2);color:#bb86fc;border:1px solid rgba(187,134,252,0.4);">Staff Nv.<?php echo (int) $pj['staff_level']; ?></span>
<?php endif; ?>
            </div>
          </div>
        </div>

        <div class="zs-pj-meta">
          <div>Nivel: <b><?php echo (int) ($pj['nivel'] ?? 1); ?></b></div>
          <div>Facción: <b><?php echo htmlspecialchars_uni($pj['faccion_slug'] !== '' ? ucfirst($pj['faccion_slug']) : '—'); ?></b></div>
<?php if (!empty($pj['fruta_nombre'])): ?>
          <div>Fruta: <b><?php echo htmlspecialchars_uni($pj['fruta_nombre']); ?></b></div>
<?php endif; ?>
        </div>

        <div class="zs-pj-actions">
          <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" target="_blank" class="btn btn-ghost btn-sm" title="Ver Expediente Público">👁️ Ver Ficha</a>
          <button type="button" class="btn btn-hot btn-sm btn-edit-pj" data-pj="<?php echo $json_data; ?>">✏️ Gestionar</button>
          <button type="button" class="btn btn-ghost btn-sm btn-del-pj" data-pid="<?php echo (int) $pj['pid']; ?>" data-nombre="<?php echo htmlspecialchars_uni($pj['nombre']); ?>" style="color:#f85149;">🗑️ Borrar</button>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>
</div>

<!-- Modal de Edición de Personaje -->
<div class="zs-modal-overlay" id="editModalOverlay">
  <div class="zs-modal-box">
    <div class="zs-modal-h">
      <h3 id="editModalTitle">Gestionar Personaje</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('editModalOverlay')">✕</button>
    </div>
    <form method="post" id="editModalForm">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="pid" id="editPid">
      <input type="hidden" name="action" id="editAction" value="editar_rapido">

      <div class="zs-form-group">
        <label>Nombre del Personaje</label>
        <input type="text" name="nombre" id="editNombre" required>
      </div>

      <div class="zs-form-group" style="display:flex;gap:12px;">
        <div style="flex:1;">
          <label>Nivel (1-100)</label>
          <input type="number" name="nivel" id="editNivel" min="1" max="100" required>
        </div>
        <div style="flex:1;">
          <label>Facción</label>
          <select name="faccion_slug" id="editFaccion">
            <option value="piratas">Piratas</option>
            <option value="marina">Marina</option>
            <option value="revolucionarios">Revolucionarios</option>
            <option value="gobierno">Gobierno Mundial</option>
            <option value="cazarrecompensas">Cazarrecompensas</option>
            <option value="civil">Civil / Independiente</option>
          </select>
        </div>
      </div>

      <div class="zs-form-group">
        <label>Estado de la Ficha</label>
        <div style="display:flex;gap:8px;">
          <select name="nuevo_estado" id="editEstado" style="flex:1;">
            <option value="aprobado">Aprobado</option>
            <option value="revision">En revisión</option>
            <option value="rechazado">Rechazado</option>
            <option value="borrador">Borrador</option>
          </select>
          <button type="button" class="btn btn-ghost btn-sm" onclick="submitEstado()">Cambiar Estado</button>
        </div>
      </div>

      <div class="zs-form-group" style="border-top:1px solid var(--rivet);padding-top:12px;margin-top:16px;">
        <label>Asignación de Rango Staff</label>
        <div style="display:flex;gap:8px;">
          <select name="staff_level" id="editStaffLevel" style="flex:1;">
            <option value="0">0: Sin Rango Staff</option>
            <option value="1">1: Colaborador</option>
            <option value="2">2: Moderador</option>
            <option value="3">3: Administrador</option>
            <option value="4">4: Webmaster</option>
          </select>
          <input type="text" name="staff_rol" id="editStaffRol" placeholder="Rol / Cargo (ej. Árbitro)" style="flex:1;">
          <button type="button" class="btn btn-ghost btn-sm" onclick="submitStaff()">Guardar Staff</button>
        </div>
      </div>

      <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editModalOverlay')">Cancelar</button>
        <button type="submit" class="btn btn-hot">Guardar Cambios Rápido</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Confirmación de Borrado -->
<div class="zs-modal-overlay" id="delModalOverlay">
  <div class="zs-modal-box">
    <div class="zs-modal-h">
      <h3 style="color:#f85149;">⚠️ Confirmar Borrado de Personaje</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('delModalOverlay')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="borrar_personaje">
      <input type="hidden" name="pid" id="delPid">

      <p style="color:var(--paper);font-size:.95rem;line-height:1.5;">
        ¿Estás seguro de que deseas borrar permanentemente el personaje <b id="delNombre" style="color:var(--paper-hi);"></b>?
      </p>
      <p style="font-size:.85rem;color:var(--paper-dim);">
        Se liberará automáticamente su Akuma no Mi (si poseía una) y se borrarán todos sus registros asociados. Esta acción no se puede deshacer.
      </p>

      <div style="margin-top:24px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('delModalOverlay')">Cancelar</button>
        <button type="submit" class="btn btn-hot" style="background:#f85149;border-color:#f85149;">Sí, Eliminar Definitivamente</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var currentFilter = 'todos';
  var searchInput = document.getElementById('pjSearchInput');
  var cards = document.querySelectorAll('.zs-pj-card');

  function filterCards(){
    var q = searchInput.value.toLowerCase().trim();
    cards.forEach(function(card){
      var matchStatus = (currentFilter === 'todos' || card.getAttribute('data-estado') === currentFilter);
      var matchSearch = !q || card.getAttribute('data-search').indexOf(q) !== -1;
      card.style.display = (matchStatus && matchSearch) ? 'flex' : 'none';
    });
  }

  document.querySelectorAll('#pjFilterPills .zs-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      document.querySelectorAll('#pjFilterPills .zs-pill').forEach(function(p){ p.classList.remove('on'); });
      pill.classList.add('on');
      currentFilter = pill.getAttribute('data-filter');
      filterCards();
    });
  });

  searchInput.addEventListener('input', filterCards);

  // Bind edit modal
  document.querySelectorAll('.btn-edit-pj').forEach(function(btn){
    btn.addEventListener('click', function(){
      var data = JSON.parse(btn.getAttribute('data-pj'));
      document.getElementById('editPid').value = data.pid;
      document.getElementById('editNombre').value = data.nombre;
      document.getElementById('editNivel').value = data.nivel || 1;
      document.getElementById('editFaccion').value = data.faccion_slug || 'piratas';
      document.getElementById('editEstado').value = data.estado || 'borrador';
      document.getElementById('editStaffLevel').value = data.staff_level || 0;
      document.getElementById('editStaffRol').value = data.staff_rol || '';
      document.getElementById('editModalTitle').textContent = 'Gestionar: ' + data.nombre;
      openModal('editModalOverlay');
    });
  });

  // Bind delete modal
  document.querySelectorAll('.btn-del-pj').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.getElementById('delPid').value = btn.getAttribute('data-pid');
      document.getElementById('delNombre').textContent = btn.getAttribute('data-nombre');
      openModal('delModalOverlay');
    });
  });
})();

function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }

function submitEstado(){
  document.getElementById('editAction').value = 'cambiar_estado';
  document.getElementById('editModalForm').submit();
}
function submitStaff(){
  document.getElementById('editAction').value = 'cambiar_staff';
  document.getElementById('editModalForm').submit();
}
</script>
</body>
</html>
