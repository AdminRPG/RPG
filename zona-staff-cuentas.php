<?php
/**
 * One Piece: Eternal · Panel Staff: Gestión de Cuentas y Narradores (STF-04)
 * Gestión de slots de personajes, rol de Narrador, rango Staff y personaje activo por cuenta.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'zona-staff-cuentas.php');
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

// Auto-check: asegurar que la columna 'narrador' existe en rol_cuentas
if ($db->table_exists('rol_cuentas') && !$db->field_exists('narrador', 'rol_cuentas')) {
    $db->write_query("ALTER TABLE " . TABLE_PREFIX . "rol_cuentas ADD COLUMN narrador TINYINT(1) NOT NULL DEFAULT 0");
}

$flash = '';
$flash_ok = false;

// Manejo de POST (Acciones de Gestión de Cuentas)
if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'La sesión del formulario caducó. Inténtalo de nuevo.';
    } else {
        $action = $mybb->get_input('action');
        $target_uid = (int) $mybb->get_input('target_uid', MyBB::INPUT_INT);

        if ($target_uid > 0 && $db->table_exists('rol_cuentas')) {
            // Asegurar que la fila existe en rol_cuentas (upsert)
            $cq = $db->simple_select('rol_cuentas', '*', "uid = {$target_uid}", array('limit' => 1));
            if (!$db->num_rows($cq)) {
                $db->insert_query('rol_cuentas', array(
                    'uid'              => $target_uid,
                    'staff_level'      => 0,
                    'slots'            => 1,
                    'personaje_activo' => 0,
                    'narrador'         => 0,
                    'dateline'         => TIME_NOW,
                ));
            }

            if ($action === 'guardar_cuenta') {
                $new_slots     = max(1, min(10, (int) $mybb->get_input('slots', MyBB::INPUT_INT)));
                $new_staff_lvl = max(0, min(4, (int) $mybb->get_input('staff_level', MyBB::INPUT_INT)));
                $new_narrador  = (int) $mybb->get_input('narrador', MyBB::INPUT_INT) ? 1 : 0;
                $new_activo    = (int) $mybb->get_input('personaje_activo', MyBB::INPUT_INT);

                $db->update_query('rol_cuentas', array(
                    'slots'            => $new_slots,
                    'staff_level'      => $new_staff_lvl,
                    'narrador'         => $new_narrador,
                    'personaje_activo' => $new_activo,
                ), "uid = {$target_uid}");

                // Si se cambió el staff_level de la cuenta, sincronizar con el personaje activo
                if ($new_activo > 0) {
                    $db->update_query('rol_personajes', array('staff_level' => $new_staff_lvl), "pid = {$new_activo}");
                }

                $flash = "Ajustes de la cuenta UID #{$target_uid} actualizados (Slots: {$new_slots}, Staff Nv.{$new_staff_lvl}, Narrador: " . ($new_narrador ? 'Sí' : 'No') . ").";
                $flash_ok = true;
            } elseif ($action === 'toggle_narrador') {
                $curr_narrador = $db->num_rows($cq) ? (int) $db->fetch_field($cq, 'narrador') : 0;
                $new_state = $curr_narrador ? 0 : 1;
                $db->update_query('rol_cuentas', array('narrador' => $new_state), "uid = {$target_uid}");
                $flash = "Rol de Narrador para UID #{$target_uid} " . ($new_state ? 'activado' : 'desactivado') . '.';
                $flash_ok = true;
            }
        }
    }
}

// Cargar todas las cuentas con datos de MyBB + rol_cuentas + recuento de PJs
$cuentas = array();
$stats_counts = array('total' => 0, 'staff' => 0, 'narradores' => 0);

if ($db->table_exists('users')) {
    $pref = TABLE_PREFIX;
    $sql = "SELECT u.uid, u.username, u.email, u.usergroup, u.avatar,
                   COALESCE(c.slots, 1) AS slots,
                   COALESCE(c.staff_level, 0) AS staff_level,
                   COALESCE(c.personaje_activo, 0) AS personaje_activo,
                   COALESCE(c.narrador, 0) AS narrador,
                   (SELECT COUNT(*) FROM {$pref}rol_personajes p WHERE p.uid = u.uid) AS total_pjs,
                   (SELECT p2.nombre FROM {$pref}rol_personajes p2 WHERE p2.pid = c.personaje_activo LIMIT 1) AS activo_nombre
            FROM {$pref}users u
            LEFT JOIN {$pref}rol_cuentas c ON c.uid = u.uid
            ORDER BY u.uid ASC";
    $q = $db->query($sql);
    while ($row = $db->fetch_array($q)) {
        $stats_counts['total']++;
        if ((int) $row['staff_level'] > 0) {
            $stats_counts['staff']++;
        }
        if ((int) $row['narrador'] > 0) {
            $stats_counts['narradores']++;
        }

        // Cargar PJs de esta cuenta para el selector en el modal
        $pjs_account = array();
        $pq = $db->simple_select('rol_personajes', 'pid, nombre, estado', "uid = " . (int) $row['uid'], array('order_by' => 'nombre'));
        while ($pr = $db->fetch_array($pq)) {
            $pjs_account[] = $pr;
        }
        $row['pjs_list'] = $pjs_account;
        $cuentas[] = $row;
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Gestión de Cuentas y Narradores</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <b>Gestión de Cuentas y Narradores</b>
</div></div>

<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Gestión de Cuentas y Narradores</h1>
      <span class="code">// panel de administración STF-04</span>
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
        <input type="text" id="accSearchInput" placeholder="Buscar por usuario, email o UID..." autocomplete="off">
      </div>
      <div class="zs-pills" id="accFilterPills">
        <button type="button" class="zs-pill on" data-filter="todas">Todas (<?php echo $stats_counts['total']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="staff">Staff (<?php echo $stats_counts['staff']; ?>)</button>
        <button type="button" class="zs-pill" data-filter="narrador">Narradores (<?php echo $stats_counts['narradores']; ?>)</button>
      </div>
    </div>

    <div class="zs-acc-grid" id="accGrid">
<?php foreach ($cuentas as $acc):
    $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($acc['username'], 0, 1)) : strtoupper(substr($acc['username'], 0, 1));
    $avatar = trim((string) ($acc['avatar'] ?? ''));
    $is_nar = (int) $acc['narrador'] > 0;
    $st_lvl = (int) $acc['staff_level'];
    $json_data = htmlspecialchars_uni(json_encode($acc, JSON_UNESCAPED_UNICODE));
?>
      <div class="zs-acc-card" data-uid="<?php echo (int) $acc['uid']; ?>" data-staff="<?php echo $st_lvl > 0 ? '1' : '0'; ?>" data-narrador="<?php echo $is_nar ? '1' : '0'; ?>" data-search="<?php echo htmlspecialchars_uni(mb_strtolower($acc['username'] . ' ' . $acc['email'])); ?>">
        <div class="zs-acc-head">
          <div class="zs-acc-avatar">
<?php if ($avatar !== ''): ?>
            <img src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="" loading="lazy" onerror="this.remove();">
<?php else: ?>
            <span><?php echo htmlspecialchars_uni($initial); ?></span>
<?php endif; ?>
          </div>
          <div class="zs-acc-info">
            <div class="zs-acc-name"><?php echo htmlspecialchars_uni($acc['username']); ?></div>
            <div class="zs-acc-sub">UID #<?php echo (int) $acc['uid']; ?> · <?php echo (int) $acc['total_pjs']; ?> personajes</div>
            <div style="margin-top:4px;display:flex;gap:4px;flex-wrap:wrap;">
<?php if ($st_lvl > 0): ?>
              <span class="zs-badge staff">Staff Nv.<?php echo $st_lvl; ?></span>
<?php endif; ?>
<?php if ($is_nar): ?>
              <span class="zs-badge narrador">Narrador</span>
<?php endif; ?>
            </div>
          </div>
        </div>

        <div class="zs-acc-meta">
          <div>Slots Máximos: <b><?php echo (int) $acc['slots']; ?></b></div>
          <div>Activo: <b><?php echo htmlspecialchars_uni(!empty($acc['activo_nombre']) ? $acc['activo_nombre'] : 'Ninguno'); ?></b></div>
        </div>

        <div class="zs-acc-actions">
          <button type="button" class="btn btn-hot btn-sm btn-edit-acc" data-acc="<?php echo $json_data; ?>">Ajustes Cuenta</button>
          <form method="post" style="display:inline;">
            <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
            <input type="hidden" name="action" value="toggle_narrador">
            <input type="hidden" name="target_uid" value="<?php echo (int) $acc['uid']; ?>">
            <button type="submit" class="btn btn-ghost btn-sm" title="Alternar Rol Narrador">
              <?php echo $is_nar ? 'Quitar Narrador' : 'Hacer Narrador'; ?>
            </button>
          </form>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>
</div>

<!-- Modal de Edición de Cuenta -->
<div class="zs-modal-overlay" id="accModalOverlay">
  <div class="zs-modal-box">
    <div class="zs-modal-h">
      <h3 id="accModalTitle">Ajustes de Cuenta</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('accModalOverlay')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="guardar_cuenta">
      <input type="hidden" name="target_uid" id="accTargetUid">

      <div class="zs-form-group">
        <label>Número Máximo de Slots de Personaje</label>
        <input type="number" name="slots" id="accSlots" min="1" max="10" required>
      </div>

      <div class="zs-form-group">
        <label>Nivel de Staff (Acceso al Foro/Paneles)</label>
        <select name="staff_level" id="accStaffLevel">
          <option value="0">0: Usuario Normal</option>
          <option value="1">1: Colaborador</option>
          <option value="2">2: Moderador</option>
          <option value="3">3: Administrador</option>
          <option value="4">4: Webmaster</option>
        </select>
      </div>

      <div class="zs-form-group">
        <label>Rol de Narrador / Game Master</label>
        <select name="narrador" id="accNarrador">
          <option value="0">No es Narrador</option>
          <option value="1">Narrador Oficial Activo</option>
        </select>
      </div>

      <div class="zs-form-group">
        <label>Personaje Activo de la Cuenta</label>
        <select name="personaje_activo" id="accPersonajeActivo">
          <option value="0">-- Ninguno (Sin personaje activo) --</option>
        </select>
      </div>

      <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
        <button type="button" class="btn btn-ghost" onclick="closeModal('accModalOverlay')">Cancelar</button>
        <button type="submit" class="btn btn-hot">Guardar Ajustes de Cuenta</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var currentFilter = 'todas';
  var searchInput = document.getElementById('accSearchInput');
  var cards = document.querySelectorAll('.zs-acc-card');

  function filterCards(){
    var q = searchInput.value.toLowerCase().trim();
    cards.forEach(function(card){
      var isStaff = card.getAttribute('data-staff') === '1';
      var isNarrador = card.getAttribute('data-narrador') === '1';
      var matchStatus = (currentFilter === 'todas') ||
                        (currentFilter === 'staff' && isStaff) ||
                        (currentFilter === 'narrador' && isNarrador);
      var matchSearch = !q || card.getAttribute('data-search').indexOf(q) !== -1;
      card.style.display = (matchStatus && matchSearch) ? 'flex' : 'none';
    });
  }

  document.querySelectorAll('#accFilterPills .zs-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      document.querySelectorAll('#accFilterPills .zs-pill').forEach(function(p){ p.classList.remove('on'); });
      pill.classList.add('on');
      currentFilter = pill.getAttribute('data-filter');
      filterCards();
    });
  });

  searchInput.addEventListener('input', filterCards);

  // Bind edit account modal
  document.querySelectorAll('.btn-edit-acc').forEach(function(btn){
    btn.addEventListener('click', function(){
      var data = JSON.parse(btn.getAttribute('data-acc'));
      document.getElementById('accTargetUid').value = data.uid;
      document.getElementById('accSlots').value = data.slots || 1;
      document.getElementById('accStaffLevel').value = data.staff_level || 0;
      document.getElementById('accNarrador').value = data.narrador || 0;
      document.getElementById('accModalTitle').textContent = 'Ajustes Cuenta: ' + data.username;

      // Populate personajes dropdown for this user
      var sel = document.getElementById('accPersonajeActivo');
      sel.innerHTML = '<option value="0">-- Ninguno --</option>';
      if (data.pjs_list && data.pjs_list.length) {
        data.pjs_list.forEach(function(p){
          var opt = document.createElement('option');
          opt.value = p.pid;
          opt.textContent = p.nombre + ' (' + p.estado + ')';
          if (parseInt(p.pid, 10) === parseInt(data.personaje_activo, 10)) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      }
      openModal('accModalOverlay');
    });
  });
})();

function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
</script>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  var io = new IntersectionObserver(function(es){ es.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('vis'); io.unobserve(e.target); }}); }, { threshold: .08 });
  document.querySelectorAll('.reveal').forEach(function(el){ io.observe(el); });
} else {
  document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('vis'); });
}
</script>
</body>
</html>
