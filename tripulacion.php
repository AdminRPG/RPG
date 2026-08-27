<?php
/**
 * One Piece: Eternal · Tripulación
 * - Si el personaje activo tiene membresía → vista "Mi tripulación"
 * - Si no → listado de tripulaciones disponibles para aplicar
 *
 * Esquema real:
 *   rol_tripulaciones       : id, nombre, faccion, capitan, lema, descripcion, nivel, miembros, imagen, activo, orden
 *   rol_tripulacion_miembros: id, tripulacion_id, pid, uid, rol, estado, dateline
 *   rol_personajes          : pid, uid, nombre, avatar, nivel, estado, ...
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'tripulacion.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl  = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname = htmlspecialchars_uni($mybb->settings['bbname']);
$uid    = (int) ($mybb->user['uid'] ?? 0);

if ($uid < 1) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

// ── Nombre del personaje activo ──
$pj_name = 'Sin personaje activo';
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $qp = $db->simple_select('rol_personajes', 'nombre', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($qp)) $pj_name = $db->fetch_field($qp, 'nombre');
}

// ── ¿Tiene tripulación activa? ──
$mi_trip = null;
$mi_trip_id = 0;
$mi_rol = 'tripulante';
$mi_members = array();

if ($pid > 0 && $db->table_exists('rol_tripulacion_miembros')) {
    $qm = $db->simple_select('rol_tripulacion_miembros', '*', "pid = {$pid} AND estado = 'activo'", array('limit' => 1));
    if ($db->num_rows($qm)) {
        $miembro = $db->fetch_array($qm);
        $mi_trip_id = (int) $miembro['tripulacion_id'];
        $mi_rol = (string) ($miembro['rol'] ?? 'tripulante');
    }
}

if ($mi_trip_id > 0 && $db->table_exists('rol_tripulaciones')) {
    $qt = $db->simple_select('rol_tripulaciones', '*', "id = {$mi_trip_id} AND activo = 1", array('limit' => 1));
    if ($db->num_rows($qt)) {
        $mi_trip = $db->fetch_array($qt);

        // Cargar miembros via JOIN
        $pref = TABLE_PREFIX;
        $qq = $db->query("
            SELECT rp.pid, rp.nombre, rp.avatar, rp.nivel, rp.estado,
                   tm.rol AS rol_trip, tm.estado AS miembro_estado
            FROM {$pref}rol_tripulacion_miembros tm
            INNER JOIN {$pref}rol_personajes rp ON (rp.pid = tm.pid)
            WHERE tm.tripulacion_id = {$mi_trip_id}
              AND tm.estado = 'activo'
              AND rp.estado = 'aprobado'
            ORDER BY FIELD(tm.rol, 'capitan', 'tripulante'), rp.nombre ASC
        ");
        while ($row = $db->fetch_array($qq)) $mi_members[] = $row;
    }
}

// ── Si no tiene tripulación: listar disponibles ──
$trips_disponibles = array();
if (!$mi_trip && $db->table_exists('rol_tripulaciones')) {
    $qall = $db->simple_select('rol_tripulaciones', '*', "activo = 1", array('order_by' => 'nombre', 'order_dir' => 'ASC'));
    while ($row = $db->fetch_array($qall)) {
        $trips_disponibles[] = $row;
    }
}

// ── POST: aplicar a una tripulación ──
$flash_msg = '';
$flash_type = 'info';
if ($mybb->request_method === 'post' && $pid > 0 && !$mi_trip) {
    verify_post_check($mybb->get_input('my_post_key'));
    $action = $mybb->get_input('action');
    if ($action === 'aplicar' && $db->table_exists('rol_tripulacion_miembros')) {
        $apply_tid = (int) $mybb->get_input('tripulacion_id');
        if ($apply_tid > 0) {
            // Verificar solicitud pendiente
            $cs = $db->simple_select('rol_tripulacion_miembros', 'COUNT(*) AS cnt',
                "pid = {$pid} AND tripulacion_id = {$apply_tid} AND estado = 'pendiente'");
            if ((int) $db->fetch_array($cs)['cnt'] > 0) {
                $flash_msg = 'Ya tienes una solicitud pendiente para esta tripulación.';
            } else {
                $db->insert_query('rol_tripulacion_miembros', array(
                    'tripulacion_id' => $apply_tid,
                    'pid'            => $pid,
                    'uid'            => $uid,
                    'rol'            => 'tripulante',
                    'estado'         => 'pendiente',
                    'dateline'       => time(),
                ));
                $flash_msg = '¡Solicitud enviada! Espera la respuesta del capitán.';
                $flash_type = 'ok';
            }
        }
    }
}

$page_title = $bbname . ' · Tripulación';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-tripulacion">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><a href="<?php echo $bburl; ?>/gestion.php">Gestión</a><span class="sep">›</span><b>Tripulación</b>
</div></div>
<div class="wrap">
<?php if ($flash_msg): ?>
  <div class="ope-toasts tp-flash"><div class="ope-toast ope-toast-<?php echo $flash_type; ?>"><span class="ope-toast-msg"><?php echo htmlspecialchars_uni($flash_msg); ?></span></div></div>
<?php endif; ?>

  <section class="reveal">
    <div class="shead">
      <h1><?php echo $mi_trip ? htmlspecialchars_uni($mi_trip['nombre']) : 'Tripulaciones'; ?></h1>
      <span class="code"><?php echo $mi_trip ? '// mi tripulación' : '// busca tu banda'; ?></span>
      <span class="rule"></span>
    </div>
    <?php if ($mi_trip): ?>
      <p class="trip-subtitle"><?php echo htmlspecialchars_uni($mi_trip['lema'] ?? ''); ?></p>
    <?php else: ?>
      <p class="trip-subtitle">Elige una tripulación para unirte. Envía una solicitud y espera la aprobación del capitán.</p>
    <?php endif; ?>
  </section>

<?php if ($mi_trip): ?>
  <!-- ═══════ MI TRIPULACIÓN ═══════ -->
  <div class="trip-layout reveal">
    <div class="trip-panel trip-info">
      <div class="trip-info-header">
        <div class="trip-crest">
          <?php if (!empty($mi_trip['imagen'])): ?>
            <img src="<?php echo htmlspecialchars_uni($mi_trip['imagen']); ?>" alt="Escudo" class="trip-crest-img" width="64" height="64">
          <?php else: ?>
            <svg viewBox="0 0 80 80" width="64" height="64">
              <circle cx="40" cy="40" r="38" fill="none" stroke="var(--ope-gold)" stroke-width="2"/>
              <text x="40" y="48" text-anchor="middle" font-family="var(--ope-disp)" font-size="28" font-weight="900" fill="var(--ope-gold)">⚓</text>
            </svg>
          <?php endif; ?>
        </div>
        <div class="trip-name-block">
          <h2 class="trip-crew-name"><?php echo htmlspecialchars_uni($mi_trip['nombre']); ?></h2>
          <?php if (!empty($mi_trip['lema'])): ?>
            <span class="trip-motto">"<?php echo htmlspecialchars_uni($mi_trip['lema']); ?>"</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="trip-stats-grid">
        <div class="trip-stat">
          <span class="trip-stat-val"><?php echo (int) ($mi_trip['miembros'] ?? count($mi_members)); ?></span>
          <span class="trip-stat-lbl">Miembros</span>
        </div>
        <div class="trip-stat">
          <span class="trip-stat-val"><?php echo htmlspecialchars_uni($mi_trip['faccion'] ?? '—'); ?></span>
          <span class="trip-stat-lbl">Facción</span>
        </div>
        <div class="trip-stat">
          <span class="trip-stat-val"><?php echo (int) ($mi_trip['nivel'] ?? 1); ?></span>
          <span class="trip-stat-lbl">Nivel</span>
        </div>
      </div>
      <?php if (!empty($mi_trip['descripcion'])): ?>
        <p class="trip-desc"><?php echo htmlspecialchars_uni($mi_trip['descripcion']); ?></p>
      <?php endif; ?>
      <div class="trip-meta-row">
        <span class="trip-meta-chip">Tu rol: <b><?php echo htmlspecialchars_uni(ucfirst($mi_rol)); ?></b></span>
        <?php if (!empty($mi_trip['capitan'])): ?>
          <span class="trip-meta-chip">Capitán: <b><?php echo htmlspecialchars_uni($mi_trip['capitan']); ?></b></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="trip-panel trip-roster">
      <h3 class="trip-roster-title">Tripulantes <span class="trip-roster-count"><?php echo count($mi_members); ?></span></h3>
      <div class="trip-roster-list">
        <?php if (empty($mi_members)): ?>
          <div class="trip-empty"><span>No hay miembros</span></div>
        <?php else: ?>
          <?php foreach ($mi_members as $m): ?>
            <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $m['pid']; ?>" class="trip-member">
              <div class="trip-member-avatar">
                <?php if (!empty($m['avatar'])): ?>
                  <img src="<?php echo htmlspecialchars_uni($m['avatar']); ?>" alt="" width="36" height="36">
                <?php else: ?>
                  <span><?php echo strtoupper(mb_substr($m['nombre'] ?? '?', 0, 1)); ?></span>
                <?php endif; ?>
              </div>
              <div class="trip-member-info">
                <span class="trip-member-name"><?php echo htmlspecialchars_uni($m['nombre'] ?? ''); ?></span>
                <span class="trip-member-meta"><?php echo htmlspecialchars_uni(ucfirst($m['rol_trip'] ?? 'tripulante')); ?> · Nv.<?php echo (int) ($m['nivel'] ?? 1); ?></span>
              </div>
              <?php if (($m['rol_trip'] ?? '') === 'capitan'): ?>
                <span class="trip-member-badge">★</span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- ═══════ TRIPULACIONES DISPONIBLES ═══════ -->
  <?php if (empty($trips_disponibles)): ?>
    <div class="trip-list-empty reveal">
      <svg viewBox="0 0 24 24" width="48" height="48" class="empty-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
      <h3>No hay tripulaciones disponibles</h3>
      <p>Crea una o espera a que se abran nuevas bandas en el Grand Line.</p>
    </div>
  <?php else: ?>
    <div class="trip-cards">
      <?php foreach ($trips_disponibles as $t):
        $tid = (int) $t['id'];
        $cap_e = htmlspecialchars_uni($t['capitan'] ?? '—');
        $faction_e = htmlspecialchars_uni(mb_strtolower($t['faccion'] ?? 'pirata'));
      ?>
        <div class="trip-card reveal">
          <div class="trip-card-header">
            <div class="trip-card-crest">
              <?php if (!empty($t['imagen'])): ?>
                <img src="<?php echo htmlspecialchars_uni($t['imagen']); ?>" alt="" width="48" height="48">
              <?php else: ?>
                <svg viewBox="0 0 60 60" width="48" height="48"><circle cx="30" cy="30" r="28" fill="none" stroke="var(--ope-gold)" stroke-width="1.5"/><text x="30" y="37" text-anchor="middle" font-size="22" fill="var(--ope-gold)">⚓</text></svg>
              <?php endif; ?>
            </div>
            <div class="trip-card-name">
              <h3><?php echo htmlspecialchars_uni($t['nombre']); ?></h3>
              <?php if (!empty($t['lema'])): ?>
                <span class="trip-card-motto">"<?php echo htmlspecialchars_uni($t['lema']); ?>"</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="trip-card-meta">
            <span class="trip-card-meta-item">
              <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="2"/></svg>
              <?php echo (int) ($t['miembros'] ?? 0); ?> miembros
            </span>
            <span class="trip-card-meta-item trip-card-faction <?php echo $faction_e; ?>">
              <?php echo htmlspecialchars_uni($t['faccion'] ?? 'pirata'); ?>
            </span>
            <?php if (!empty($t['capitan'])): ?>
              <span class="trip-card-meta-item">
                Capitán: <?php echo $cap_e; ?>
              </span>
            <?php endif; ?>
          </div>
          <?php if (!empty($t['descripcion'])): ?>
            <p class="trip-card-desc"><?php echo htmlspecialchars_uni(mb_substr($t['descripcion'], 0, 140)); ?><?php echo mb_strlen($t['descripcion']) > 140 ? '…' : ''; ?></p>
          <?php endif; ?>
          <div class="trip-card-actions">
            <form method="post" class="trip-card-form">
              <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
              <input type="hidden" name="action" value="aplicar">
              <input type="hidden" name="tripulacion_id" value="<?php echo $tid; ?>">
              <button type="button" class="ope-btn ope-btn-ghost trip-apply-btn" onclick="document.getElementById('trip-modal-<?php echo $tid; ?>').classList.add('open')">Aplicar</button>
            </form>
          </div>
          <!-- Modal -->
          <div class="trip-modal" id="trip-modal-<?php echo $tid; ?>">
            <div class="trip-modal-inner">
              <h4>Enviar solicitud a <?php echo htmlspecialchars_uni($t['nombre']); ?></h4>
              <p>Escribe un mensaje para el capitán:</p>
              <form method="post">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="aplicar">
                <input type="hidden" name="tripulacion_id" value="<?php echo $tid; ?>">
                <textarea name="mensaje" class="trip-modal-textarea" rows="3" placeholder="¿Por qué quieres unirte?"></textarea>
                <div class="trip-modal-actions">
                  <button type="button" class="ope-btn ope-btn-ghost" onclick="this.closest('.trip-modal').classList.remove('open')">Cancelar</button>
                  <button type="submit" class="ope-btn ope-btn-hot">Enviar solicitud</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
/* Cerrar modales al clic fuera */
document.querySelectorAll('.trip-modal').forEach(function(m){
  m.addEventListener('click',function(e){ if(e.target===m) m.classList.remove('open'); });
});
/* Reveal + stagger */
if('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches){
  var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');io.unobserve(e.target);}});},{threshold:.08});
  document.querySelectorAll('.reveal,.trip-card').forEach(function(el){io.observe(el);});
} else { document.querySelectorAll('.reveal,.trip-card').forEach(function(el){el.classList.add('vis');}); }
</script>
</body>
</html>
