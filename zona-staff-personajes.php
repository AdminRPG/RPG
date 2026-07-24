<?php
/**
 * One Piece: Eternal · Panel Staff: Gestión de Personajes (STF-03)
 * Permite buscar, filtrar, editar estado, rol de staff y borrar personajes en formato Tabla.
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
                $pq = $db->simple_select('rol_personajes', 'datos', "pid = {$target_pid}", array('limit' => 1));
                $d_obj = array();
                if ($db->num_rows($pq)) {
                    $d_obj = json_decode((string)$db->fetch_field($pq, 'datos'), true) ?: array();
                }
                $d_obj['faccion'] = $nueva_faccion;

                $update_data = array(
                    'nombre'   => $db->escape_string($nuevo_nombre),
                    'nivel'    => $nuevo_nivel,
                    'datos'    => $db->escape_string(json_encode($d_obj, JSON_UNESCAPED_UNICODE)),
                    'lastedit' => TIME_NOW,
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
.reveal {
  opacity: 1 !important;
  transform: none !important;
  visibility: visible !important;
}
.zs-table-wrapper {
  width: 100%;
  overflow-x: auto;
  margin-top: 20px;
}
.zs-pj-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 8px;
  font-family: inherit;
  font-size: 14px;
}
.zs-pj-table th {
  padding: 12px 16px;
  color: var(--gold-light, #d4af37);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 11px;
  letter-spacing: 1px;
  border-bottom: 2px solid rgba(212, 175, 55, 0.2);
  text-align: left;
}
.zs-pj-row {
  background: var(--iron-hi, rgba(255, 255, 255, 0.04));
  border: 1px solid var(--rivet, rgba(255, 255, 255, 0.08));
  transition: all 0.2s ease;
}
.zs-pj-row:hover {
  background: rgba(212, 175, 55, 0.08);
  transform: translateY(-1px);
}
.zs-pj-row td {
  padding: 14px 16px;
  vertical-align: middle;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.zs-pj-row td:first-child {
  border-top-left-radius: 10px;
  border-bottom-left-radius: 10px;
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}
.zs-pj-row td:last-child {
  border-top-right-radius: 10px;
  border-bottom-right-radius: 10px;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}
.zs-tbl-pj-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.zs-tbl-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: 2px solid var(--gold, #c5a059);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 18px;
  color: var(--paper, #f0f0f0);
  flex-shrink: 0;
}
.zs-tbl-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.zs-tbl-pj-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.zs-tbl-pj-name {
  font-weight: 700;
  font-size: 15px;
  color: var(--paper, #ffffff);
}
.zs-tbl-pj-sub {
  font-size: 12px;
  color: var(--gold-light, #d4af37);
  font-style: italic;
}
.zs-tag-fac {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
  letter-spacing: 0.3px;
}
.fac-marines { background: rgba(30, 144, 255, 0.18); border: 1px solid #1e90ff; color: #70a1ff; }
.fac-piratas { background: rgba(220, 20, 60, 0.18); border: 1px solid #dc143c; color: #ff6b81; }
.fac-gobierno-mundial { background: rgba(212, 175, 55, 0.18); border: 1px solid #d4af37; color: #eccc68; }
.fac-cazarrecompensas { background: rgba(255, 127, 80, 0.18); border: 1px solid #ff7f50; color: #ffa502; }
.fac-civiles { background: rgba(169, 169, 169, 0.18); border: 1px solid #a9a9a9; color: #ced6e0; }

.zs-level-badge {
  display: inline-block;
  padding: 4px 8px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 6px;
  font-weight: 700;
  font-size: 13px;
  color: var(--paper, #f0f0f0);
}
.zs-tag-fruit {
  display: inline-block;
  padding: 4px 10px;
  background: rgba(155, 89, 182, 0.18);
  border: 1px solid #9b59b6;
  border-radius: 20px;
  font-size: 12px;
  color: #d8a7ca;
  font-weight: 600;
}
.zs-tag-nofruit {
  color: rgba(255, 255, 255, 0.4);
  font-size: 12px;
  font-style: italic;
}
.zs-tbl-owner {
  display: flex;
  flex-direction: column;
  font-size: 13px;
}
.zs-tbl-pid {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
}
.zs-tbl-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}
.btn-xs {
  padding: 5px 10px;
  font-size: 12px;
  border-radius: 6px;
}
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

    <div class="zs-table-wrapper">
      <table class="zs-pj-table" id="pjTable">
        <thead>
          <tr>
            <th>Personaje</th>
            <th>Facción</th>
            <th>Nivel</th>
            <th>Akuma no Mi</th>
            <th>Usuario / PID</th>
            <th>Estado</th>
            <th style="text-align:right;">Acciones</th>
          </tr>
        </thead>
        <tbody id="pjTableBody">
<?php if (empty($personajes)): ?>
          <tr>
            <td colspan="7">
              <div class="zs-empty-state">
                <div class="zs-empty-state-h">No hay personajes registrados en la base de datos</div>
                <p class="zs-empty-state-p">Acabas de limpiar los personajes de prueba o aún no se ha creado ninguna ficha.</p>
                <a href="<?php echo $bburl; ?>/crear-personaje.php" class="btn btn-hot btn-sm">+ Crear nuevo personaje</a>
              </div>
            </td>
          </tr>
<?php endif; ?>
<?php foreach ($personajes as $pj):
    $st = (string) ($pj['estado'] ?? 'borrador');
    $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1)) : strtoupper(substr($pj['nombre'], 0, 1));
    $avatar = trim((string) ($pj['avatar'] ?? ''));

    // Extract faccion from datos JSON
    $datos_pj = json_decode((string) ($pj['datos'] ?? ''), true) ?: array();
    $raw_fac = trim((string) ($pj['faccion_slug'] ?? ($datos_pj['faccion'] ?? '')));
    $aliases = array(
        'marine' => 'marines', 'marina' => 'marines',
        'pirata' => 'piratas',
        'gobierno' => 'gobierno-mundial',
        'civil' => 'civiles'
    );
    if (isset($aliases[$raw_fac])) {
        $raw_fac = $aliases[$raw_fac];
    }
    
    $f_map = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
    if (isset($f_map[$raw_fac])) {
        $fac_label_disp = $f_map[$raw_fac]['nombre'];
    } elseif ($raw_fac !== '') {
        $fac_label_disp = ucfirst($raw_fac);
    } elseif (!empty($pj['rango_faccion'])) {
        $fac_label_disp = (string) $pj['rango_faccion'];
    } else {
        $fac_label_disp = '—';
    }

    $pj['faccion_slug'] = $raw_fac;
    $json_data = htmlspecialchars_uni(json_encode($pj, JSON_UNESCAPED_UNICODE));
?>
          <tr class="zs-pj-row" data-pid="<?php echo (int) $pj['pid']; ?>" data-estado="<?php echo $st; ?>" data-search="<?php echo htmlspecialchars_uni(mb_strtolower($pj['nombre'] . ' ' . $pj['owner_name'] . ' ' . $raw_fac . ' ' . $fac_label_disp)); ?>">
            <td>
              <div class="zs-tbl-pj-cell">
                <div class="zs-tbl-avatar">
<?php if ($avatar !== ''): ?>
                  <img src="<?php echo htmlspecialchars_uni($avatar); ?>" alt="" loading="lazy" onerror="this.remove();">
<?php else: ?>
                  <span><?php echo htmlspecialchars_uni($initial); ?></span>
<?php endif; ?>
                </div>
                <div class="zs-tbl-pj-info">
                  <div class="zs-tbl-pj-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></div>
<?php if (!empty($pj['rango_faccion'])): ?>
                  <div class="zs-tbl-pj-sub"><?php echo htmlspecialchars_uni($pj['rango_faccion']); ?></div>
<?php endif; ?>
                </div>
              </div>
            </td>
            <td>
              <span class="zs-tag-fac fac-<?php echo htmlspecialchars_uni($raw_fac); ?>">
                <?php echo htmlspecialchars_uni($fac_label_disp); ?>
              </span>
            </td>
            <td>
              <span class="zs-level-badge">Nv. <?php echo (int) ($pj['nivel'] ?? 1); ?></span>
            </td>
            <td>
<?php if (!empty($pj['fruta_nombre'])): ?>
              <span class="zs-tag-fruit" title="<?php echo htmlspecialchars_uni($pj['fruta_nombre']); ?>">
                🍇 <?php echo htmlspecialchars_uni($pj['fruta_nombre']); ?>
              </span>
<?php else: ?>
              <span class="zs-tag-nofruit">Sin Fruta</span>
<?php endif; ?>
            </td>
            <td>
              <div class="zs-tbl-owner">
                <b><?php echo htmlspecialchars_uni($pj['owner_name']); ?></b>
                <span class="zs-tbl-pid">PID #<?php echo (int) $pj['pid']; ?></span>
              </div>
            </td>
            <td>
              <span class="zs-badge <?php echo $st; ?>"><?php echo htmlspecialchars_uni($st); ?></span>
<?php if ((int) ($pj['staff_level'] ?? 0) > 0): ?>
              <span class="zs-badge zs-badge-staff">Staff Nv.<?php echo (int) $pj['staff_level']; ?></span>
<?php endif; ?>
            </td>
            <td style="text-align:right;">
              <div class="zs-tbl-actions">
                <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="Ver Expediente">Ver Ficha</a>
                <button type="button" class="btn btn-hot btn-xs btn-edit-pj" data-pj="<?php echo $json_data; ?>">Gestionar</button>
                <button type="button" class="btn btn-ghost btn-xs btn-del-pj zs-txt-danger" data-pid="<?php echo (int) $pj['pid']; ?>" data-nombre="<?php echo htmlspecialchars_uni($pj['nombre']); ?>">Borrar</button>
              </div>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody>
      </table>
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

      <div class="zs-form-row">
        <div class="col-grow">
          <label>Nivel (1-100)</label>
          <input type="number" name="nivel" id="editNivel" min="1" max="100" required>
        </div>
        <div class="col-grow">
          <label>Facción</label>
          <select name="faccion_slug" id="editFaccion">
            <option value="piratas">Piratas</option>
            <option value="marines">Marina</option>
            <option value="revolucionarios">Revolucionarios</option>
            <option value="gobierno-mundial">Gobierno Mundial</option>
            <option value="cazarrecompensas">Cazarrecompensas</option>
            <option value="civiles">Civil / Independiente</option>
          </select>
        </div>
      </div>

      <div class="zs-form-group">
        <label>Estado de la Ficha</label>
        <div class="zs-modal-actions">
          <select name="nuevo_estado" id="editEstado" class="col-grow">
            <option value="aprobado">Aprobado</option>
            <option value="revision">En revisión</option>
            <option value="rechazado">Rechazado</option>
            <option value="borrador">Borrador</option>
          </select>
          <button type="button" class="btn btn-ghost btn-sm" onclick="submitEstado()">Cambiar Estado</button>
        </div>
      </div>

      <div class="zs-form-group zs-section-divider">
        <label>Asignación de Rango Staff</label>
        <div class="zs-modal-actions">
          <select name="staff_level" id="editStaffLevel" class="col-grow">
            <option value="0">0: Sin Rango Staff</option>
            <option value="1">1: Colaborador</option>
            <option value="2">2: Moderador</option>
            <option value="3">3: Administrador</option>
            <option value="4">4: Webmaster</option>
          </select>
          <input type="text" name="staff_rol" id="editStaffRol" placeholder="Rol / Cargo (ej. Árbitro)" class="col-grow">
          <button type="button" class="btn btn-ghost btn-sm" onclick="submitStaff()">Guardar Staff</button>
        </div>
      </div>

      <div class="zs-modal-footer">
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
      <h3 class="zs-txt-danger">⚠️ Confirmar Borrado de Personaje</h3>
      <button type="button" class="zs-modal-close" onclick="closeModal('delModalOverlay')">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
      <input type="hidden" name="action" value="borrar_personaje">
      <input type="hidden" name="pid" id="delPid">

      <p class="zs-text-lead">
        ¿Estás seguro de que deseas borrar permanentemente el personaje <b id="delNombre" class="zs-text-hi"></b>?
      </p>
      <p class="zs-text-sub">
        Se liberará automáticamente su Akuma no Mi (si poseía una) y se borrarán todos sus registros asociados. Esta acción no se puede deshacer.
      </p>

      <div class="zs-modal-footer-lg">
        <button type="button" class="btn btn-ghost" onclick="closeModal('delModalOverlay')">Cancelar</button>
        <button type="submit" class="btn btn-hot zs-btn-danger">Sí, Eliminar Definitivamente</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
(function(){
  var currentFilter = 'todos';
  var searchInput = document.getElementById('pjSearchInput');
  var rows = document.querySelectorAll('.zs-pj-row');

  function filterRows(){
    var q = searchInput.value.toLowerCase().trim();
    rows.forEach(function(row){
      var matchStatus = (currentFilter === 'todos' || row.getAttribute('data-estado') === currentFilter);
      var matchSearch = !q || row.getAttribute('data-search').indexOf(q) !== -1;
      row.style.display = (matchStatus && matchSearch) ? 'table-row' : 'none';
    });
  }

  document.querySelectorAll('#pjFilterPills .zs-pill').forEach(function(pill){
    pill.addEventListener('click', function(){
      document.querySelectorAll('#pjFilterPills .zs-pill').forEach(function(p){ p.classList.remove('on'); });
      pill.classList.add('on');
      currentFilter = pill.getAttribute('data-filter');
      filterRows();
    });
  });

  searchInput.addEventListener('input', filterRows);

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

  document.querySelectorAll('.reveal').forEach(function(el) { el.classList.add('vis'); });
})();

function submitEstado() {
  document.getElementById('editAction').value = 'cambiar_estado';
  document.getElementById('editModalForm').submit();
}
function submitStaff() {
  document.getElementById('editAction').value = 'cambiar_staff';
  document.getElementById('editModalForm').submit();
}
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
</script>
</body>
</html>
