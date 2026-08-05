<?php
/**
 * One Piece: Eternal · Revision de cierre de viaje (Admin)
 * Muestra el viaje, los posts del hilo, permite analizar con IA y aprobar/rechazar.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'revision-viaje.php');
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

if (!$is_staff || $staff_rank < 3) {
    header('Location: ' . $bburl . '/zona-staff.php');
    exit;
}

$viaje_id = (int) $mybb->get_input('viaje_id', MyBB::INPUT_INT);
if ($viaje_id < 1) {
    header('Location: ' . $bburl . '/zona-staff-viajes.php');
    exit;
}

if (!function_exists('ope_viaje_por_id')) {
    die('Motor de viajes no cargado.');
}
require_once MYBB_ROOT . 'inc/ope_rol/mundo/viaje_revision.php';

$viaje = ope_viaje_por_id($viaje_id);
if (!$viaje) {
    die('Viaje no encontrado.');
}

$flash = '';
$flash_ok = false;

if ($mybb->request_method === 'post') {
    if (!verify_post_check($mybb->get_input('my_post_key'), true)) {
        $flash = 'Sesion caducada.';
    } else {
        $action = $mybb->get_input('action');

        if ($action === 'analizar_ia') {
            require_once MYBB_ROOT . 'inc/ope_rol/mundo/viaje_revision_ai.php';
            $posts = ope_viaje_revision_obtener_posts((int) $viaje['tid']);
            $res_ai = ope_viaje_ia_analizar($viaje, $posts);
            if ($res_ai['ok']) {
                ope_viaje_revision_guardar_ai($viaje_id, $res_ai);
                $flash = 'Analisis completado con ' . $res_ai['modelo'] . '.';
                $flash_ok = true;
                $viaje = ope_viaje_por_id($viaje_id);
            } else {
                $flash = 'Error IA: ' . ($res_ai['error'] ?? 'Desconocido');
            }
        } elseif ($action === 'aprobar') {
            $res = ope_viaje_revision_aprobar($viaje_id, $uid);
            $flash = $res['msg'];
            $flash_ok = $res['ok'];
            if ($res['ok']) {
                header('Location: ' . $bburl . '/zona-staff-viajes.php?ok=1');
                exit;
            }
        } elseif ($action === 'rechazar') {
            $motivo = trim((string) $mybb->get_input('motivo'));
            if ($motivo === '') {
                $flash = 'Debes escribir un motivo para el rechazo.';
            } else {
                $res = ope_viaje_revision_rechazar($viaje_id, $uid, $motivo);
                $flash = $res['msg'];
                $flash_ok = $res['ok'];
                if ($res['ok']) {
                    header('Location: ' . $bburl . '/zona-staff-viajes.php?ok=1');
                    exit;
                }
            }
        }
    }
}

$posts = ope_viaje_revision_obtener_posts((int) $viaje['tid']);
$trip  = json_decode((string) ($viaje['tripulantes_json'] ?? '[]'), true);
if (!is_array($trip)) $trip = array();

$ai_json = null;
$ai_raw  = (string) ($viaje['revision_ai_json'] ?? '');
if ($ai_raw !== '') {
    $ai_decoded = json_decode($ai_raw, true);
    if (is_array($ai_decoded)) $ai_json = $ai_decoded;
}
$ai_analisis = $ai_json['analisis'] ?? null;

$oraculo = json_decode((string) ($viaje['resultado_json'] ?? '{}'), true);
$oraculo_cartas = array();
if (is_array($oraculo) && !empty($oraculo['tramos'])) {
    foreach ($oraculo['tramos'] as $tr) {
        foreach ($tr['cartas'] ?? array() as $k => $c) {
            if (!is_array($c) || empty($c['nombre'])) continue;
            $oraculo_cartas[] = $c;
        }
    }
}

$intentos     = (int) ($viaje['revision_intentos'] ?? 1);
$es_temeraria = !empty($viaje['es_temeraria']);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Revisar Viaje #<?php echo $viaje_id; ?></title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-zona-staff">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff.php">Zona Staff</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/zona-staff-viajes.php">Revision de Viajes</a><span class="sep">›</span>
  <b>Viaje #<?php echo $viaje_id; ?></b>
</div></div>

<div class="wrap">

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_ok ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars_uni($flash); ?></div>
<?php endif; ?>

  <section class="reveal">
    <div class="shead">
      <h1>Revisar Viaje #<?php echo $viaje_id; ?></h1>
      <span class="code">// <?php echo htmlspecialchars_uni($viaje['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($viaje['destino_nombre']); ?></span>
      <span class="rule"></span>
    </div>
  </section>

  <!-- DATOS DEL VIAJE -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Datos del Viaje</span><span class="c">// resumen</span></div>
      <div class="plate-b">
        <div class="zs-stafftbl">
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname"><?php echo htmlspecialchars_uni($viaje['origen_nombre']); ?> &rarr; <?php echo htmlspecialchars_uni($viaje['destino_nombre']); ?></span>
              <span class="zs-staffowner">Barco: <?php echo htmlspecialchars_uni($viaje['barco_nombre']); ?> &middot; Viaje #<?php echo $viaje_id; ?> &middot; Solicitado <?php echo my_date('relative', (int) $viaje['cierre_dateline']); ?></span>
            </div>
            <a href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int) $viaje['tid']; ?>" target="_blank" class="btn btn-ghost btn-sm">Ver hilo</a>
          </div>
        </div>

        <div class="zs-pj-meta zs-ut-mt12">
          <span>Peligro: <strong><?php echo htmlspecialchars_uni(ucfirst($viaje['nivel_peligro'] ?? 'bajo')); ?></strong></span>
          <?php if ($es_temeraria): ?><span><span class="zs-badge rechazado">Ruta Temeraria</span></span><?php endif; ?>
          <span>Posts min: <strong><?php echo (int) $viaje['posts_min']; ?></strong></span>
          <span>Plazo: <strong><?php echo (int) $viaje['plazo_dias']; ?>d</strong></span>
          <span>Dias on-rol: <strong><?php echo (int) ($viaje['dias_onrol'] ?? 0); ?>d</strong></span>
          <span>Intento: <strong><?php echo $intentos; ?>/2</strong></span>
          <?php if ($intentos >= 2): ?><span class="zs-badge rechazado">Ultimo intento</span><?php endif; ?>
        </div>

        <div class="zs-ut-mt8">
          <strong>Tripulacion:</strong>
<?php foreach ($trip as $tm): ?>
          <span class="zs-badge"><?php echo htmlspecialchars_uni($tm['nombre'] ?? '?'); ?> (<?php echo htmlspecialchars_uni($tm['oficio'] ?? 'trip'); ?>)</span>
<?php endforeach; ?>
        </div>

        <h4 class="zs-ut-title">Cartas del Oraculo (<?php echo count($oraculo_cartas); ?>)</h4>
        <div class="zs-pj-grid">
<?php foreach ($oraculo_cartas as $c): ?>
          <div class="zs-pj-card">
            <div class="zs-pj-head">
              <div class="zs-pj-avatar zs-avatar-lg"><?php echo htmlspecialchars_uni($c['ico'] ?? '◈'); ?></div>
              <div class="zs-pj-info">
                <div class="zs-pj-name"><?php echo htmlspecialchars_uni($c['nombre'] ?? '???'); ?></div>
                <div class="zs-pj-owner"><?php echo htmlspecialchars_uni($c['efecto'] ?? ''); ?></div>
              </div>
            </div>
            <?php if (!empty($c['mesa']) && !in_array($c['mesa'] ?? '', array('clima','encuentro','peligro','hallazgo','misterio','bonanza'), true)): ?>
            <div class="zs-pj-meta"><span class="zs-badge revision">Extra: <?php echo htmlspecialchars_uni($c['mesa']); ?></span></div>
            <?php endif; ?>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- POSTS DEL HILO -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Posts del Hilo (<?php echo count($posts); ?>)</span><span class="c">// contenido roleado</span></div>
      <div class="plate-b">
<?php if (empty($posts)): ?>
        <p class="zs-empty-state-p">No hay posts visibles en este hilo.</p>
<?php else: ?>
<?php foreach ($posts as $p): ?>
        <div class="zs-staffrow zs-ut-stack">
          <div class="zs-staffname"><?php echo htmlspecialchars_uni($p['autor']); ?></div>
          <div class="zs-staffowner zs-ut-txt-xs"><?php echo htmlspecialchars_uni($p['contenido']); ?></div>
        </div>
<?php endforeach; ?>
<?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ANALISIS IA -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Analisis de IA</span><span class="c">// evaluacion automatica</span></div>
      <div class="plate-b">
<?php if ($ai_analisis): ?>
        <?php $aprob = !empty($ai_analisis['aprobado']); ?>
        <div class="zs-ut-mb16">
          <span class="zs-badge <?php echo $aprob ? 'aprobado' : 'rechazado'; ?> zs-ut-badge-lg">
            <?php echo $aprob ? 'APROBADO POR IA' : 'RECHAZADO POR IA'; ?>
          </span>
        </div>

        <div class="zs-stafftbl">
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">Resumen</span>
              <span class="zs-staffowner zs-ut-txt-xm"><?php echo htmlspecialchars_uni($ai_analisis['resumen'] ?? 'Sin resumen.'); ?></span>
            </div>
          </div>

          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">Posts roleados: <?php echo (int) ($ai_analisis['posts_reales'] ?? 0); ?> / <?php echo (int) $viaje['posts_min']; ?> minimos</span>
              <span class="zs-staffowner">Minimos cumplidos: <?php echo !empty($ai_analisis['posts_minimos_cumplidos']) ? 'Si' : 'No'; ?> &middot; Participaron: <?php echo htmlspecialchars_uni(implode(', ', (array) ($ai_analisis['participacion_tripulantes'] ?? array()))); ?></span>
            </div>
          </div>

          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">Estimaciones de Consumo</span>
              <span class="zs-staffowner">Desgaste casco: <?php echo (int) ($ai_analisis['desgaste_casco_estimado'] ?? 0); ?>% &middot; Consumo despensa: <?php echo (int) ($ai_analisis['consumo_despensa_estimado'] ?? 0); ?>%</span>
            </div>
          </div>

<?php if (!empty($ai_analisis['oraculo_roleado'])): ?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">Eventos del Oraculo</span>
              <span class="zs-staffowner">
<?php foreach ($ai_analisis['oraculo_roleado'] as $ev): ?>
                <?php echo htmlspecialchars_uni($ev['evento'] ?? '?'); ?>: <?php echo !empty($ev['roleado']) ? 'Roleado' : 'No roleado'; ?> <small>(<?php echo htmlspecialchars_uni($ev['calidad'] ?? '?'); ?>)</small><br>
<?php endforeach; ?>
              </span>
            </div>
          </div>
<?php endif; ?>

<?php if (!empty($ai_analisis['problemas'])): ?>
          <div class="zs-staffrow zs-ut-danger-row">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname zs-ut-danger">Problemas Detectados</span>
              <span class="zs-staffowner zs-ut-danger zs-ut-txt-sm">
<?php foreach ($ai_analisis['problemas'] as $prob): ?>
                &bull; <?php echo htmlspecialchars_uni($prob); ?><br>
<?php endforeach; ?>
              </span>
            </div>
          </div>
<?php endif; ?>

<?php if (!empty($ai_analisis['sugerencias'])): ?>
          <div class="zs-staffrow">
            <div class="zs-staffwho col-grow">
              <span class="zs-staffname">Sugerencias</span>
              <span class="zs-staffowner zs-ut-txt-sm">
<?php foreach ($ai_analisis['sugerencias'] as $sug): ?>
                &bull; <?php echo htmlspecialchars_uni($sug); ?><br>
<?php endforeach; ?>
              </span>
            </div>
          </div>
<?php endif; ?>
        </div>

        <?php if (!empty($ai_json['modelo'])): ?>
        <div class="zs-staffowner zs-ut-mt12">Analizado con: <?php echo htmlspecialchars_uni($ai_json['modelo']); ?></div>
        <?php endif; ?>

<?php else: ?>
        <p class="zs-empty-state-p">No se ha realizado ningun analisis IA todavia.</p>
<?php endif; ?>

        <form method="post" class="zs-ut-mt16">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="action" value="analizar_ia">
          <button type="submit" class="btn btn-hot"><?php echo $ai_analisis ? 'Re-analizar con IA' : 'Analizar con IA'; ?></button>
        </form>
      </div>
    </div>
  </section>

  <!-- ACCIONES -->
  <section class="reveal">
    <div class="plate">
      <div class="plate-h"><span class="t">Acciones de Staff</span><span class="c">// aprobar o rechazar</span></div>
      <div class="plate-b">
        <div class="zs-stafftbl">
          <div class="zs-staffrow">
            <div class="zs-pj-actions">
              <form method="post" class="zs-ut-inline">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="aprobar">
                <button type="submit" class="btn btn-hot" onclick="return confirm('Confirmar la llegada y ejecutar todos los efectos automaticos?');">Aprobar y Completar Viaje</button>
              </form>

              <form method="post" class="zs-ut-row-form">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="action" value="rechazar">
                <input type="text" name="motivo" placeholder="Motivo del rechazo (obligatorio)..." required
                       class="zs-ut-input">
                <button type="submit" class="btn btn-ghost zs-txt-danger"
                        onclick="return confirm('Rechazar el cierre? El viaje <?php echo $intentos >= 2 ? 'se CANCELARA' : 'volvera a activo'; ?>.');">
                  Rechazar (<?php echo $intentos >= 2 ? 'Cancelar viaje' : 'Volver a activo'; ?>)
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
    var io = new IntersectionObserver(function(es) {
        es.forEach(function(e) { if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); } });
    }, { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });
} else {
    document.querySelectorAll('.reveal').forEach(function(el) { el.classList.add('vis'); });
}
</script>
</body>
</html>
