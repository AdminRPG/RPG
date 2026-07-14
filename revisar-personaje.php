<?php
/**
 * I-Forge · Revisión de expediente (Staff)
 * Vista detallada de ficha con botones Aprobar / Moderar / Rechazar.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'revisar-personaje.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int)($mybb->user['uid'] ?? 0) > 0;
$uid       = (int)($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');
$pid       = (int)($mybb->get_input('pid', MyBB::INPUT_INT));

require_once MYBB_ROOT . 'inc/ope_user_init.php';

// Staff del PERSONAJE ACTIVO. Aprobar/moderar/rechazar expedientes requiere
// rol >= Colaborador (rank 1). El staff va por personaje, no por cuenta.
$staff_level = ope_get_staff_level($uid);

// Acceso: Colaborador o superior (con el personaje activo).
if (!$loggedin || $staff_level < 1) {
    header('Location: ' . $bburl . '/index.php');
    exit;
}

// Cargar personaje concreto (si se ha pedido uno con ?pid=).
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    $pj = $db->fetch_array($q);
}

// Sin personaje concreto → cola de revisión + búsqueda de expedientes.
$queue = array();
$buscar = trim((string) $mybb->get_input('q'));
$filtro_est = trim((string) $mybb->get_input('estado'));
if (!$pj && $db->table_exists('rol_personajes')) {
    $npc_filter = $db->field_exists('es_npc', 'rol_personajes') ? ' AND es_npc = 0' : '';
    $qq = $db->simple_select('rol_personajes', 'pid, nombre, uid, rango, dateline', "estado = 'revision'{$npc_filter}", array('order_by' => 'dateline', 'order_dir' => 'ASC', 'limit' => 100));
    while ($qr = $db->fetch_array($qq)) {
        $qr['owner'] = '?';
        if ((int)$qr['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int)$qr['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) $qr['owner'] = $db->fetch_field($uq, 'username');
        }
        $queue[] = $qr;
    }

    // Búsqueda de todos los expedientes (gestión completa).
    $browse = array();
    $where = "estado <> 'eliminado'{$npc_filter}";
    if ($buscar !== '') {
        $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
    }
    if ($filtro_est !== '' && in_array($filtro_est, array('borrador', 'revision', 'aprobado', 'rechazado', 'eliminado'), true)) {
        $where = "estado = '" . $db->escape_string($filtro_est) . "'{$npc_filter}";
        if ($buscar !== '') {
            $where .= " AND nombre LIKE '%" . $db->escape_string_like($buscar) . "%'";
        }
    }
    $bq = $db->simple_select('rol_personajes', 'pid, nombre, uid, rango, estado, dateline', $where, array('order_by' => 'nombre', 'order_dir' => 'ASC', 'limit' => 150));
    while ($br = $db->fetch_array($bq)) {
        $br['owner'] = '?';
        if ((int)$br['uid'] > 0) {
            $uq = $db->simple_select('users', 'username', 'uid = ' . (int)$br['uid'], array('limit' => 1));
            if ($db->num_rows($uq)) $br['owner'] = $db->fetch_field($uq, 'username');
        }
        $browse[] = $br;
    }
}

$datos     = $pj['datos'] ? json_decode($pj['datos'], true) : array();
$inventario = $pj['inventario'] ? json_decode($pj['inventario'], true) : array();
$economia   = $pj['economia'] ? json_decode($pj['economia'], true) : array();
$bio        = $pj['bio'] ? json_decode($pj['bio'], true) : array();

// Dueño
$owner_name = '?';
if ($pj['uid'] > 0) {
    $uq = $db->simple_select('users', 'username', "uid = " . (int)$pj['uid'], array('limit' => 1));
    $owner_name = htmlspecialchars_uni($db->fetch_field($uq, 'username'));
}

// POST: aprobar / rechazar / moderar
$flash = ''; $flash_kind = 'ok';
if ($pj && $loggedin && $staff_level >= 1 && $mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesión caducada. Recarga la página.';
        $flash_kind = 'warn';
    } else {
        $action = $mybb->get_input('action');
        $mensaje_staff = trim($mybb->get_input('mensaje_staff'));

        if ($action === 'approve') {
            $db->update_query('rol_personajes', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'aprobado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            // Alerta para el jugador
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_aprobado',
                    'titulo' => 'Personaje aprobado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido aprobado. Ya puedes activarlo desde la página de Personaje.',
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" aprobada.';
            // Recargar estado
            $pj['estado'] = 'aprobado';

        } elseif ($action === 'reject') {
            $db->update_query('rol_personajes', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ($db->table_exists('rol_tramites')) {
                $db->update_query('rol_tramites', array('estado' => 'rechazado', 'lastedit' => TIME_NOW), "pid = {$pid} AND tipo = 'crear_personaje'");
            }
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_rechazado',
                    'titulo' => 'Personaje rechazado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido rechazado.' . ($mensaje_staff !== '' ? ' Motivo: ' . $db->escape_string($mensaje_staff) : ''),
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha "' . htmlspecialchars_uni($pj['nombre']) . '" rechazada.';
            $pj['estado'] = 'rechazado';

        } elseif ($action === 'moderate' && $mensaje_staff !== '') {
            // Moderar: enviar MD al personaje
            if ($db->table_exists('rol_mensajes')) {
                // Buscar staff pid del narrador
                $staff_pid = 0;
                $sq = $db->simple_select('rol_personajes', 'pid', "uid = {$uid} AND activo = 1", array('limit' => 1));
                if ($db->num_rows($sq)) $staff_pid = (int)$db->fetch_field($sq, 'pid');
                
                // Crear hilo
                $thread_id = TIME_NOW; // Simple unique thread ID
                $db->insert_query('rol_mensajes', array(
                    'thread_id' => $thread_id,
                    'origen_pid' => $staff_pid,
                    'destino_pid' => $pid,
                    'asunto' => 'Moderación: ' . $db->escape_string($pj['nombre']),
                    'cuerpo' => $db->escape_string($mensaje_staff),
                    'leido' => 0,
                    'dateline' => TIME_NOW
                ));
            }
            // Alerta
            if ($db->table_exists('rol_alertas')) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_moderado',
                    'titulo' => 'Ficha moderada: cambios solicitados',
                    'cuerpo' => 'El staff ha solicitado cambios en tu personaje "' . $db->escape_string($pj['nombre']) . '". Revisa tus mensajes para ver los detalles.',
                    'link' => $bburl . '/mensajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Mensaje de moderación enviado a ' . htmlspecialchars_uni($pj['nombre']) . '.';
            $pj['estado'] = 'revision'; // Sigue en revisión

        } elseif ($action === 'return_revision' && $pj['estado'] !== 'revision' && $pj['estado'] !== 'eliminado') {
            $db->update_query('rol_personajes', array('estado' => 'revision', 'activo' => 0, 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ((int)$pj['uid'] > 0 && $db->table_exists('rol_cuentas')) {
                $db->update_query('rol_cuentas', array('personaje_activo' => 0), "uid = " . (int)$pj['uid'] . " AND personaje_activo = {$pid}");
            }
            if ($db->table_exists('rol_alertas') && (int)$pj['uid'] > 0) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_revision',
                    'titulo' => 'Expediente devuelto a revisión',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido devuelto a revisión por el staff.' . ($mensaje_staff !== '' ? ' Motivo: ' . $db->escape_string($mensaje_staff) : ''),
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha devuelta a revisión.';
            $pj['estado'] = 'revision';
            $pj['activo'] = 0;

        } elseif ($action === 'soft_delete' && $pj['estado'] !== 'eliminado') {
            $db->update_query('rol_personajes', array('estado' => 'eliminado', 'activo' => 0, 'lastedit' => TIME_NOW), "pid = {$pid}");
            if ((int)$pj['uid'] > 0 && $db->table_exists('rol_cuentas')) {
                $db->update_query('rol_cuentas', array('personaje_activo' => 0), "uid = " . (int)$pj['uid'] . " AND personaje_activo = {$pid}");
            }
            if ($db->table_exists('rol_alertas') && (int)$pj['uid'] > 0) {
                $db->insert_query('rol_alertas', array(
                    'pid' => $pid, 'uid' => (int)$pj['uid'],
                    'tipo' => 'personaje_eliminado',
                    'titulo' => 'Expediente eliminado',
                    'cuerpo' => 'Tu personaje "' . $db->escape_string($pj['nombre']) . '" ha sido eliminado. Se ha liberado un hueco en tu cuenta.' . ($mensaje_staff !== '' ? ' Motivo: ' . $db->escape_string($mensaje_staff) : ''),
                    'link' => $bburl . '/personajes.php',
                    'leido' => 0, 'dateline' => TIME_NOW
                ));
            }
            $flash = 'Ficha eliminada (soft-delete). Se ha liberado el hueco.';
            $pj['estado'] = 'eliminado';
            $pj['activo'] = 0;
        }
    }
}

// Función local para heat
function ope_heat_var($rango) {
    $map = ['F'=>'--h1','E'=>'--h1','D'=>'--h2','C'=>'--h3','B'=>'--h4','A'=>'--h5','S'=>'--h6','SS'=>'--h7','M'=>'--h8','M+'=>'--h9'];
    return $map[$rango] ?? '--h1';
}

// Función local para label de estado
function _estado_label($estado) {
    switch ($estado) {
        case 'aprobado': return ['Aprobado', 'var(--patina-hi)'];
        case 'revision': return ['En revisión', 'var(--h6)'];
        case 'rechazado': return ['Rechazado', 'var(--crack)'];
        case 'eliminado': return ['Eliminado', 'var(--ash)'];
        default: return ['Borrador', 'var(--rivet)'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · <?php echo $pj ? 'Revisar: ' . htmlspecialchars_uni($pj['nombre']) : 'Cola de revisión'; ?></title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-revisar-personaje) -->
</head>
<body class="ope-pg-revisar-personaje">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
    <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
    <b><?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Cola de revisión'; ?></b>
  </div>
</div>

<div class="wrap">
  <div class="shead">
    <h1><?php echo $pj ? 'Revisar expediente' : 'Cola de revisión'; ?></h1>
    <span class="code">// staff</span>
    <span class="rule"></span>
  </div>

<?php if (!$pj): ?>
  <!-- COLA DE EXPEDIENTES EN REVISIÓN -->
  <?php if (empty($queue)): ?>
    <div class="empty-state">
      <div class="big">No hay expedientes pendientes</div>
      <p>Cuando un jugador envíe una ficha a revisión aparecerá aquí para que la apruebes, moderes o rechaces.</p>
    </div>
  <?php else: ?>
    <div class="rp-queue">
      <div class="rp-queue-h"><b><?php echo count($queue); ?></b> expediente(s) pendiente(s) de revisión</div>
      <?php foreach ($queue as $qr):
        $q_ini = function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($qr['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($qr['nombre'], 0, 1)); ?>
        <div class="rp-queue-item">
          <span class="rp-q-av"><?php echo htmlspecialchars_uni($q_ini); ?></span>
          <div class="rp-q-info">
            <span class="rp-q-name"><?php echo htmlspecialchars_uni($qr['nombre']); ?></span>
            <span class="rp-q-meta">// <?php echo htmlspecialchars_uni($qr['owner']); ?> &middot; enviado <?php echo date('d/m/Y', (int)$qr['dateline']); ?></span>
          </div>
          <a href="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo (int)$qr['pid']; ?>" class="btn btn-hot btn-sm">Revisar</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- BÚSQUEDA / GESTIÓN DE TODOS LOS EXPEDIENTES -->
  <div class="shead mt-28">
    <h2 class="fw-800 fs-14 ttu c-paper">Todos los expedientes</h2>
    <span class="rule"></span>
  </div>
  <form method="get" action="<?php echo $bburl; ?>/revisar-personaje.php" class="rp-search">
    <input type="text" name="q" value="<?php echo htmlspecialchars_uni($buscar); ?>" placeholder="Buscar por nombre&hellip;">
    <select name="estado">
      <option value="">Activos (no eliminados)</option>
      <option value="revision"<?php echo $filtro_est === 'revision' ? ' selected' : ''; ?>>En revisión</option>
      <option value="aprobado"<?php echo $filtro_est === 'aprobado' ? ' selected' : ''; ?>>Aprobados</option>
      <option value="rechazado"<?php echo $filtro_est === 'rechazado' ? ' selected' : ''; ?>>Rechazados</option>
      <option value="borrador"<?php echo $filtro_est === 'borrador' ? ' selected' : ''; ?>>Borradores</option>
      <option value="eliminado"<?php echo $filtro_est === 'eliminado' ? ' selected' : ''; ?>>Eliminados</option>
    </select>
    <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
  </form>
  <?php if (!empty($browse)): ?>
    <div class="rp-queue">
      <div class="rp-queue-h"><b><?php echo count($browse); ?></b> expediente(s) encontrado(s)</div>
      <?php foreach ($browse as $br):
        $est = _estado_label($br['estado']);
        $b_ini = function_exists('mb_strtoupper') ? mb_strtoupper(mb_substr($br['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($br['nombre'], 0, 1)); ?>
        <div class="rp-queue-item">
          <span class="rp-q-av"><?php echo htmlspecialchars_uni($b_ini); ?></span>
          <div class="rp-q-info">
            <span class="rp-q-name"><?php echo htmlspecialchars_uni($br['nombre']); ?></span>
            <span class="rp-q-meta">// <?php echo htmlspecialchars_uni($br['owner']); ?> &middot; <span style="color:<?php echo $est[1]; ?>"><?php echo $est[0]; ?></span> &middot; <?php echo htmlspecialchars_uni($br['rango']); ?></span>
          </div>
          <a href="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo (int)$br['pid']; ?>" class="btn btn-ghost btn-sm">Gestionar</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($buscar !== '' || $filtro_est !== ''): ?>
    <div class="empty-state"><div class="big">Sin resultados</div></div>
  <?php endif; ?>
<?php else: ?>

  <?php if ($flash !== ''): ?>
    <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
  <?php endif; ?>

  <!-- HEADER FICHA -->
  <div class="sheet">
    <div class="sheet-h">
      <div class="sheet-h-left">
        <div class="sheet-av"><?php echo htmlspecialchars_uni(mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8')); ?></div>
        <div>
          <div class="sheet-name"><?php echo htmlspecialchars_uni($pj['nombre']); ?></div>
          <div class="sheet-owner">de <?php echo $owner_name; ?> · creado <?php echo date('d/m/Y', (int)$pj['dateline']); ?></div>
        </div>
      </div>
      <?php $est = _estado_label($pj['estado']); ?>
      <span class="sheet-badge" style="background:<?php echo $est[1]; ?>;color:var(--iron)"><?php echo $est[0]; ?></span>
    </div>

    <div class="sheet-b">
      <div class="sheet-grid">
        <!-- COL IZQUIERDA -->
        <div>
          <!-- IDENTIDAD -->
          <div class="sheet-block mb-14">
            <div class="sheet-block-h">Identidad</div>
            <div class="sheet-block-b">
              <div class="stat-row"><span class="sn">Raza</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['raza_principal'] ?? '?')); ?><?php echo !empty($datos['hibrido']) ? ' / ' . htmlspecialchars_uni(ucfirst($datos['raza_secundaria'] ?? '')) : ''; ?></span></div>
              <?php if (!empty($datos['apodo'])): ?>
                <div class="stat-row"><span class="sn">Apodo</span><span class="sl"><?php echo htmlspecialchars_uni($datos['apodo']); ?></span></div>
              <?php endif; ?>
              <div class="stat-row"><span class="sn">Edad</span><span class="sl"><?php echo htmlspecialchars_uni($datos['edad'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Género</span><span class="sl"><?php echo htmlspecialchars_uni($datos['genero'] ?? '?'); ?></span></div>
              <div class="stat-row"><span class="sn">Facción</span><span class="sl"><?php echo htmlspecialchars_uni(ucfirst($datos['faccion'] ?? '?')); ?></span></div>
              <?php if (!empty($datos['virtudes']['V-LIN-01']) || !empty($datos['tiene_d'])): ?>
                <div class="stat-row"><span class="sn">D.</span><span class="sl c-ember fw-700">Portador de la D.</span></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- STATS -->
          <div class="sheet-block mb-14">
            <div class="sheet-block-h">Stats · Rango <?php echo htmlspecialchars_uni($pj['rango'] ?? '?'); ?></div>
            <div class="sheet-block-b">
              <?php 
              $pilares = array('Cuerpo'=>['FUE','DES','VIG','AGI'], 'Mente'=>['INT','ING','CON','PER'], 'Espíritu'=>['CAR','CTR','VOL','SEN']);
              $labels = array('FUE'=>'Fuerza','DES'=>'Destreza','VIG'=>'Vigor','AGI'=>'Agilidad','INT'=>'Intelecto','ING'=>'Ingenio','CON'=>'Concentración','PER'=>'Percepción','CAR'=>'Carisma','CTR'=>'Control','VOL'=>'Voluntad','SEN'=>'Sensibilidad');
              $stats = $datos['stats_efectivas'] ?? $datos['stats_base'] ?? array();
              if (!empty($stats)):
                foreach ($pilares as $pilarName => $keys): ?>
                  <div class="rp-pilar-label fs-58 fw-700 ttu c-ash"><?php echo $pilarName; ?></div>
                  <?php foreach ($keys as $k):
                    $v = (int)($stats[$k] ?? 0);
                    $rangos = ['F','E','D','C','B','A','S','SS','M','M+'];
                    $rl = $rangos[max(0, min(9, $v))] ?? '?';
                    $hv = ope_heat_var($rl);
                  ?>
                    <div class="stat-row">
                      <span class="sn"><?php echo $k; ?></span>
                      <span class="sl"><?php echo $labels[$k] ?? $k; ?></span>
                      <span class="sv" style="color:var(<?php echo $hv; ?>)"><?php echo $rl; ?></span>
                    </div>
                  <?php endforeach;
                endforeach;
              else: ?>
                <p class="c-dim fs-82">Sin datos de stats.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- COL DERECHA -->
        <div>
          <!-- VIRTUDES Y DEFECTOS -->
          <div class="sheet-block mb-14">
            <div class="sheet-block-h">Virtudes <?php echo !empty($datos['pc_gastado']) ? '(+' . (int)$datos['pc_gastado'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['virtudes'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['virtudes'] as $v): ?>
                    <span class="chip-item good"><?php echo htmlspecialchars_uni(is_array($v) ? ($v['nombre'] ?? '?') : $v); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="c-dim fs-78">Sin virtudes.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="sheet-block mb-14">
            <div class="sheet-block-h">Defectos <?php echo !empty($datos['pc_devuelto']) ? '(+' . (int)$datos['pc_devuelto'] . ' PC)' : ''; ?></div>
            <div class="sheet-block-b">
              <?php if (!empty($datos['defectos'])): ?>
                <div class="chip-list">
                  <?php foreach ($datos['defectos'] as $d): ?>
                    <span class="chip-item bad"><?php echo htmlspecialchars_uni(is_array($d) ? ($d['nombre'] ?? '?') : $d); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="c-dim fs-78">Sin defectos.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- EQUIPO -->
          <div class="sheet-block mb-14">
            <div class="sheet-block-h">Equipo · <?php echo htmlspecialchars_uni(number_format((int)($economia['berries'] ?? 0))); ?> berries</div>
            <div class="sheet-block-b">
              <?php
                $rp_packs   = ope_rol_packs_equipo();
                $rp_pack    = $rp_packs[$inventario['pack_equipo'] ?? ''] ?? null;
              ?>
              <?php if ($rp_pack !== null): ?>
                <div class="stat-row"><span class="sl">Pack</span><span class="sv c-dim"><?php echo htmlspecialchars_uni($rp_pack['nombre']); ?></span></div>
                <p class="c-dim fs-72 mt-6"><?php echo htmlspecialchars_uni(implode(' · ', $rp_pack['contenido'])); ?></p>
              <?php else: ?>
                <?php if (!empty($inventario['arma'])): ?>
                  <div class="stat-row"><span class="sl">Arma</span><span class="sv c-dim"><?php echo htmlspecialchars_uni($inventario['arma']); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($inventario['objeto_personal'])): ?>
                  <div class="stat-row"><span class="sl">Objeto personal</span><span class="sv c-dim"><?php echo htmlspecialchars_uni($inventario['objeto_personal']); ?></span></div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- BIO -->
      <?php if (!empty($bio)): ?>
        <div class="sheet-block mt-14">
          <div class="sheet-block-h">Historia y personalidad</div>
          <div class="sheet-block-b">
            <div class="text-block">
              <?php if (!empty($bio['concepto'])): ?><p><strong>Concepto:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['concepto'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['historia_pasado'])): ?><p><strong>Pasado:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['historia_pasado'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['motivacion'])): ?><p><strong>Motivación:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['motivacion'])); ?></p><?php endif; ?>
              <?php if (!empty($bio['relaciones'])): ?><p><strong>Relaciones:</strong> <?php echo nl2br(htmlspecialchars_uni($bio['relaciones'])); ?></p><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- BARRA DE ACCIONES DE REVISIÓN -->
  <?php if ($pj['estado'] === 'revision'): ?>
  <div class="actions-bar">
    <form method="post" action="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo $pid; ?>">
      <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
      <div class="actions-bar-in">
        <div>
          <div class="ab-label">Aprobar ficha</div>
          <button type="submit" name="action" value="approve" class="btn btn-hot btn-sm">Aprobar</button>
        </div>
        <div>
          <div class="ab-label">Rechazar ficha</div>
          <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Rechazar</button>
        </div>
        <div class="flex-1 minw-250">
          <div class="ab-label">Moderar (enviar cambios solicitados por MD)</div>
          <textarea name="mensaje_staff" placeholder="Describe los cambios necesarios..."></textarea>
        </div>
        <button type="submit" name="action" value="moderate" class="btn btn-ghost btn-sm">Enviar moderación</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- GESTIÓN DEL CICLO DE VIDA (aprobados, rechazados, etc.) -->
  <?php if ($pj['estado'] !== 'eliminado'): ?>
  <div class="actions-bar rp-lifecycle">
    <form method="post" action="<?php echo $bburl; ?>/revisar-personaje.php?pid=<?php echo $pid; ?>">
      <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
      <div class="actions-bar-in">
        <div class="ab-label fw mb-6">Gesti&oacute;n del expediente</div>
        <div class="flex-1 minw-220">
          <div class="ab-label">Motivo (opcional, para devolver o eliminar)</div>
          <textarea name="mensaje_staff" placeholder="Motivo..." rows="2"></textarea>
        </div>
<?php if ($pj['estado'] !== 'revision'): ?>
        <button type="submit" name="action" value="return_revision" class="btn btn-ghost btn-sm">Devolver a revisi&oacute;n</button>
<?php endif; ?>
        <button type="submit" name="action" value="soft_delete" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este expediente? Se liberará el hueco del jugador.');">Eliminar</button>
      </div>
    </form>
  </div>
  <?php else: ?>
  <div class="flash warn">Este expediente est&aacute; eliminado. El hueco del jugador ha sido liberado.</div>
  <?php endif; ?>

<?php endif; /* fin: detalle vs cola */ ?>

</div>

<footer class="foot">
  <div class="foot-in">
    <div class="foot-b">One Piece Eternal</div>
  </div>
</footer>

</body>
</html>
