<?php
/**
 * One Piece: Eternal · Planificador de Navegación y Rutas
 * --------------------------------------------------------
 * Sistema automático de navegación entre 44 islas.
 * Permite seleccionar destino, barco, ítems equipados y tripulantes,
 * calculando la ruta óptima, tramos, días en rol, escala de peligro y Oráculo v2.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'viajes.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);

if (!$loggedin) {
    header('Location: ' . $bburl . '/member.php?action=login');
    exit;
}

$pid = (int) ($mybb->user['ope_active_pid'] ?? 0);
if ($pid < 1 && function_exists('ope_rol_active_pid_for')) {
    $pid = ope_rol_active_pid_for($uid);
}

// Cargar personaje activo
$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

$origen_slug = (string) ($pj['isla_actual'] ?? 'isla_dawn');
if (empty($origen_slug)) {
    $origen_slug = 'isla_dawn';
}
$isla_origen = ope_isla_por_slug($origen_slug);
if (!$isla_origen) {
    $origen_slug = 'isla_dawn';
    $isla_origen = ope_isla_por_slug('isla_dawn');
}

// Comprobar viaje activo
$viaje_activo    = $pid > 0 ? ope_viaje_por_capitan_activo($pid) : null;
$viaje_pendiente = null;
if ($pid > 0 && !$viaje_activo && $db->table_exists('rol_viajes')) {
    $q_pend = $db->simple_select('rol_viajes', '*', "pid_capitan = {$pid} AND estado = 'pendiente_cierre'", array('limit' => 1));
    if ($db->num_rows($q_pend)) {
        $viaje_pendiente = $db->fetch_array($q_pend);
    }
}

// Barcos del personaje
$barcos = $pid > 0 ? ope_barco_lista($pid) : array();
// Items del personaje
$items_pj = $pid > 0 ? ope_nav_item_lista($pid) : array();
// Tripulantes candidatos
$tripulantes_candidatos = array();
if ($pid > 0 && function_exists('ope_rol_cat_tripulacion_miembros')) {
    $m_list = ope_rol_cat_tripulacion_miembros($pid);
    if (is_array($m_list)) {
        foreach ($m_list as $m) {
            if ((int)($m['pid'] ?? 0) !== $pid) {
                $tripulantes_candidatos[] = $m;
            }
        }
    }
}

// Procesar Acciones POST
$flash_error = '';
$flash_ok = '';

if ($mybb->request_method === 'post') {
    verify_post_check($mybb->get_input('my_post_key'));
    $action = $mybb->get_input('action');

    if ($action === 'cerrar') {
        $viaje_id = (int) $mybb->get_input('viaje_id');
        $res = ope_viaje_cerrar($viaje_id, $uid, $pid);
        if ($res['ok']) {
            header('Location: ' . $res['url']);
            exit;
        } else {
            $flash_error = $res['msg'];
        }
    } elseif ($action === 'zarpar') {
        if ($viaje_activo || $viaje_pendiente) {
            $flash_error = 'Ya tienes un viaje activo o pendiente de revision. Debes llegar a tu destino antes de zarpar de nuevo.';
        } elseif ($pid < 1) {
            $flash_error = 'Debes tener un personaje activo para zarpar.';
        } else {
            $destino_slug = trim((string) $mybb->get_input('destino_slug'));
            $barco_id    = (int) $mybb->get_input('barco_id');
            $items_sel   = $mybb->get_input('items', MyBB::INPUT_ARRAY);
            $trip_sel    = $mybb->get_input('tripulantes', MyBB::INPUT_ARRAY);
            $suministros = trim((string) $mybb->get_input('suministros'));
            $notas       = trim((string) $mybb->get_input('notas'));

            $data_sol = array(
                'pid_capitan'  => $pid,
                'uid'          => $uid,
                'origen_slug'  => $origen_slug,
                'destino_slug' => $destino_slug,
                'barco_id'     => $barco_id,
                'items'        => is_array($items_sel) ? $items_sel : array(),
                'tripulantes'  => is_array($trip_sel) ? $trip_sel : array(),
                'suministros'  => $suministros,
                'notas'        => $notas,
            );

            // Publicación ASÍNCRONA: se encola, se vuelve al índice al instante y
            // una tarea programada publica el hilo y avisa por la campana.
            $res = ope_viaje_encolar($data_sol);
            if ($res['ok']) {
                ope_flash_set($uid, 'ok', $res['msg']);
                header('Location: ' . $bburl . '/index.php');
                exit;
            } else {
                $flash_error = $res['msg'];
            }
        }
    }
}

// Vista previa de ruta por GET/AJAX
$preview_destino = trim((string) $mybb->get_input('preview_destino'));
$preview_barco   = (int) $mybb->get_input('preview_barco');
$preview_ruta    = null;

if ($preview_destino !== '' && $preview_destino !== $origen_slug) {
    $b_data = array();
    if ($preview_barco > 0) {
        $b_data = ope_barco_obtener($preview_barco);
    }
    if (empty($b_data) && !empty($barcos)) {
        $b_data = $barcos[0];
    }
    $items_slugs = ope_nav_item_slugs($pid);
    $pj_nivel = (int) ($pj['nivel'] ?? 1);
    $trip_data = ope_viaje_tripulantes_data($pid, array());
    $preview_ruta = ope_navegacion_calcular_ruta($origen_slug, $preview_destino, $b_data, $items_slugs, $trip_data, $pj_nivel);
}

$islas_por_region = ope_islas_por_region();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Planificador de Navegación</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-viajes">
<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a>
    <span class="sep">&#8250;</span>
    <b>Navegación y Rutas</b>
  </div>
</div>

<div class="wrap">
  <section class="reveal">
    <div class="shead">
      <h1>Planificador de Navegación</h1>
      <span class="code">// rutas marítimas</span>
      <span class="rule"></span>
    </div>
    <p class="viajes-intro">
      Sistema automático de cartas de navegación para las 44 islas del mundo. Elige tu destino, equipa tus ítems náuticos y selecciona tu barco para calcular tramos, escala de peligro y lanzar el Oráculo de Viaje en Alta Mar.
<?php if ($pj): ?>
      Personaje activo: <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b> &middot; Ubicación actual: <b><?php echo htmlspecialchars_uni($isla_origen['nombre']); ?></b> (<?php echo htmlspecialchars_uni($isla_origen['region']); ?>).
<?php else: ?>
      <span class="c-ember">Activa un personaje</span> para planificar una travesía.
<?php endif; ?>
    </p>
  </section>

<?php if ($flash_error !== ''): ?>
  <div class="flash error"><?php echo htmlspecialchars_uni($flash_error); ?></div>
<?php endif; ?>
<?php if ($flash_ok !== ''): ?>
  <div class="flash ok"><?php echo htmlspecialchars_uni($flash_ok); ?></div>
<?php endif; ?>

<?php if ($viaje_activo): ?>
  <!-- PANEL DE VIAJE ACTIVO -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Travesía Activa en Curso</span>
        <span class="c">// estado: activo</span>
      </div>
      <div class="plate-b">
        <div class="viaje-activo-card">
          <h3><?php echo htmlspecialchars_uni($viaje_activo['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($viaje_activo['destino_nombre']); ?></h3>
          <p>Actualmente te encuentras navegando a bordo de <strong><?php echo htmlspecialchars_uni($viaje_activo['barco_nombre']); ?></strong>.</p>
          <div class="viaje-stats-grid">
            <div class="vstat"><span class="vval"><?php echo (int)($viaje_activo['dias_onrol'] ?? 2); ?>d</span><span class="vlbl">Días On-Rol</span></div>
            <div class="vstat"><span class="vval"><?php echo htmlspecialchars_uni(ucfirst($viaje_activo['nivel_peligro'] ?? 'bajo')); ?></span><span class="vlbl">Escala Peligro</span></div>
            <div class="vstat"><span class="vval"><?php echo (int)$viaje_activo['posts_min']; ?></span><span class="vlbl">Posts Mínimos</span></div>
          </div>
          <div class="viaje-actions">
            <a href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int)$viaje_activo['tid']; ?>" class="btn btn-hot">Ir al Hilo en Alta Mar</a>
<?php if (ope_viaje_puede_cerrar($viaje_activo, $uid, $pid)): ?>
            <form method="post" action="<?php echo $bburl; ?>/viajes.php" class="inline-form">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="action" value="cerrar">
              <input type="hidden" name="viaje_id" value="<?php echo (int)$viaje_activo['viaje_id']; ?>">
              <button type="submit" class="btn btn-ghost" onclick="return confirm('¿Confirmar la llegada al puerto de destino?');">Solicitar Llegada</button>
            </form>
<?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php elseif ($viaje_pendiente): ?>
  <!-- PANEL DE VIAJE PENDIENTE DE REVISIÓN -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h">
        <span class="t">Travesía en Revisión</span>
        <span class="c">// estado: pendiente de cierre</span>
      </div>
      <div class="plate-b">
        <div class="viaje-activo-card">
          <h3><?php echo htmlspecialchars_uni($viaje_pendiente['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($viaje_pendiente['destino_nombre']); ?></h3>
          <p>Has solicitado el cierre de esta travesía a bordo de <strong><?php echo htmlspecialchars_uni($viaje_pendiente['barco_nombre']); ?></strong>. El staff está revisando el roleo.</p>
          <div class="viaje-stats-grid">
            <div class="vstat"><span class="vval"><?php echo (int)($viaje_pendiente['dias_onrol'] ?? 2); ?>d</span><span class="vlbl">Días On-Rol</span></div>
            <div class="vstat"><span class="vval"><?php echo htmlspecialchars_uni(ucfirst($viaje_pendiente['nivel_peligro'] ?? 'bajo')); ?></span><span class="vlbl">Escala Peligro</span></div>
            <div class="vstat"><span class="vval"><?php echo (int)$viaje_pendiente['posts_min']; ?></span><span class="vlbl">Posts Mínimos</span></div>
          </div>
          <div class="viaje-actions">
            <a href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int)$viaje_pendiente['tid']; ?>" class="btn btn-hot">Ir al Hilo en Alta Mar</a>
            <p class="viaje-pending-note">Recibirás una notificación cuando el staff apruebe o rechace el cierre. Si es rechazado, podrás corregir el roleo y volver a solicitar el cierre.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php elseif ($pid < 1): ?>
  <div class="plate">
    <div class="plate-b">
      <p class="pj-empty">Debes seleccionar o activar un personaje en tu ficha para planificar un viaje marítimo.</p>
    </div>
  </div>
<?php else: ?>

  <!-- FORMULARIO DE PLANIFICACIÓN -->
  <form method="post" action="<?php echo $bburl; ?>/viajes.php" class="viajes-form">
    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
    <input type="hidden" name="action" value="zarpar">

    <div class="viajes-grid-layout">
      <!-- Columna Izquierda: Parámetros del Viaje -->
      <div class="viajes-main-col">
        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">1. Definir Ruta</span>
            <span class="c">// origen &amp; destino</span>
          </div>
          <div class="plate-b">
            <div class="form-row">
              <label class="form-label">Isla de Origen (Actual)</label>
              <div class="isla-chip-fixed">
                <span class="isla-name"><?php echo htmlspecialchars_uni($isla_origen['nombre']); ?></span>
                <span class="isla-region"><?php echo htmlspecialchars_uni($isla_origen['region']); ?> &middot; Tier <?php echo (int)$isla_origen['tier']; ?></span>
              </div>
            </div>

            <div class="form-row">
              <label for="destino_slug" class="form-label">Isla de Destino</label>
              <select name="destino_slug" id="destino_slug" class="ope-select" required onchange="actualizarPreview(this.value)">
                <option value="">-- Selecciona una isla de destino --</option>
<?php foreach ($islas_por_region as $region_name => $islas_list): ?>
                <optgroup label="<?php echo htmlspecialchars_uni($region_name); ?>">
<?php foreach ($islas_list as $isl): ?>
<?php if ($isl['slug'] === $origen_slug) continue; ?>
                  <option value="<?php echo htmlspecialchars_uni($isl['slug']); ?>" <?php if ($preview_destino === $isl['slug']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars_uni($isl['nombre']); ?> (Tier <?php echo (int)$isl['tier']; ?>, Peligro Base: <?php echo (int)$isl['peligro_base']; ?>)
                  </option>
<?php endforeach; ?>
                </optgroup>
<?php endforeach; ?>
              </select>
            </div>
          </div>
        </section>

        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">2. Selección de Embarcación</span>
            <span class="c">// rol_barcos</span>
          </div>
          <div class="plate-b">
<?php if (empty($barcos)): ?>
            <p class="pj-empty">No tienes barcos registrados. Se utilizará un bote básico por defecto.</p>
            <input type="hidden" name="barco_id" value="0">
<?php else: ?>
            <div class="barcos-radio-list">
<?php foreach ($barcos as $idx => $b): ?>
              <label class="barco-radio-card">
                <input type="radio" name="barco_id" value="<?php echo (int)$b['barco_id']; ?>" <?php if ($idx === 0) echo 'checked'; ?> onchange="actualizarBarcoPreview(this.value)">
                <div class="barco-info">
                  <div class="barco-name"><?php echo htmlspecialchars_uni($b['nombre']); ?> <span class="barco-badge"><?php echo htmlspecialchars_uni($b['tipo_label']); ?></span></div>
                  <div class="barco-meta">Estado Casco: <?php echo (int)$b['estado_casco']; ?>% &middot; Velocidad: x<?php echo (int)$b['vel']; ?></div>
                </div>
              </label>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </section>

        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">3. Ítems de Navegación Equipados</span>
            <span class="c">// rol_nav_items</span>
          </div>
          <div class="plate-b">
<?php if (empty($items_pj)): ?>
            <p class="pj-empty">Sin ítems náuticos especiales en el inventario. Precaución en aguas de Grand Line.</p>
<?php else: ?>
            <div class="items-check-grid">
<?php foreach ($items_pj as $it): ?>
              <label class="item-check-card">
                <input type="checkbox" name="items[]" value="<?php echo htmlspecialchars_uni($it['slug']); ?>" checked>
                <div class="item-details">
                  <span class="item-title"><?php echo htmlspecialchars_uni($it['nombre']); ?></span>
                  <span class="item-desc"><?php echo htmlspecialchars_uni($it['desc']); ?></span>
                </div>
              </label>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </section>

        <section class="plate reveal">
          <div class="plate-h">
            <span class="t">4. Acompañantes y Detalles</span>
            <span class="c">// tripulantes &amp; notas</span>
          </div>
          <div class="plate-b">
<?php if (!empty($tripulantes_candidatos)): ?>
            <div class="form-row">
              <label class="form-label">Tripulantes Acompañantes</label>
              <div class="tripulantes-check-list">
<?php foreach ($tripulantes_candidatos as $tc): ?>
                <label class="trip-check-item">
                  <input type="checkbox" name="tripulantes[]" value="<?php echo (int)$tc['pid']; ?>">
                  <span><?php echo htmlspecialchars_uni($tc['nombre']); ?> (<?php echo htmlspecialchars_uni($tc['rol'] ?: 'Tripulante'); ?>)</span>
                </label>
<?php endforeach; ?>
              </div>
            </div>
<?php endif; ?>

            <div class="form-row">
              <label for="suministros" class="form-label">Suministros especiales (opcional)</label>
              <input type="text" name="suministros" id="suministros" class="ope-input" placeholder="Ej: Raciones para 10 días, Barriles de agua de manantial">
            </div>

            <div class="form-row">
              <label for="notas" class="form-label">Notas de travesía (opcional)</label>
              <textarea name="notas" id="notas" class="ope-textarea" rows="2" placeholder="Notas sobre la intención del viaje o circunstancias especiales..."></textarea>
            </div>
          </div>
        </section>
      </div>

      <!-- Columna Derecha: Vista Previa y Cálculo -->
      <div class="viajes-sidebar-col">
        <div class="plate reveal sticky-panel">
          <div class="plate-h">
            <span class="t">Cálculo de Travesía</span>
            <span class="c">// motor de rutas</span>
          </div>
          <div class="plate-b">
            <div id="preview-container">
<?php if ($preview_ruta && $preview_ruta['ok']): ?>
              <div class="route-summary">
                <div class="route-header">
                  <div class="route-tramos"><?php echo htmlspecialchars_uni($preview_ruta['nivel_peligro_label']); ?></div>
                  <div class="route-dias"><?php echo (int)$preview_ruta['dias_onrol']; ?> Días On-Rol</div>
                </div>

                <div class="route-peligro danger-box-<?php echo htmlspecialchars_uni($preview_ruta['nivel_peligro']); ?>">
                  <span class="peligro-lbl">Escala de Peligro:</span>
                  <span class="peligro-val"><?php echo htmlspecialchars_uni($preview_ruta['nivel_peligro_label']); ?></span>
                  <small>(Peligro acumulado: <?php echo (int)$preview_ruta['peligro_acumulado']; ?>)</small>
                </div>

<?php if (!empty($preview_ruta['es_temeraria'])): ?>
                <div class="alert-temeraria">
                  <strong>Precaución: Ruta Temeraria</strong>
                  <p>Faltan ítems de navegación requeridos o tu nivel es inferior al tier de destino. La probabilidad de peligro en el Oráculo se ha incrementado.</p>
                </div>
<?php endif; ?>

                <div class="route-path-list">
                  <div class="path-title">Escalas previas de la ruta:</div>
                  <ol class="path-steps">
<?php foreach ($preview_ruta['nodos'] as $step_slug): ?>
                    <li><?php echo htmlspecialchars_uni(ope_isla_nombre($step_slug)); ?></li>
<?php endforeach; ?>
                  </ol>
                </div>

                <div class="route-meta">
                  <div><span>Posts sugeridos:</span> <strong><?php echo (int)$preview_ruta['posts_sugeridos']; ?></strong></div>
                  <div><span>Plazo off-rol:</span> <strong><?php echo (int)$preview_ruta['plazo_offrol_dias']; ?> días</strong></div>
                </div>
              </div>
<?php else: ?>
              <div class="preview-placeholder">
                <p>Selecciona una isla de destino para calcular la ruta, el peligro y la duración estimada del viaje.</p>
              </div>
<?php endif; ?>
            </div>

            <div class="zarpar-btn-box">
              <button type="submit" class="btn btn-hot btn-block">Zarpar en Alta Mar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
function actualizarPreview(destinoSlug) {
  if (!destinoSlug) return;
  const barcoId = document.querySelector('input[name="barco_id"]:checked')?.value || 0;
  window.location.href = 'viajes.php?preview_destino=' + encodeURIComponent(destinoSlug) + '&preview_barco=' + barcoId;
}
function actualizarBarcoPreview(barcoId) {
  const destinoSlug = document.getElementById('destino_slug')?.value;
  if (destinoSlug) {
    window.location.href = 'viajes.php?preview_destino=' + encodeURIComponent(destinoSlug) + '&preview_barco=' + barcoId;
  }
}

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
