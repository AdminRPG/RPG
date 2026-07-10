<?php
/**
 * I-Forge · Progresión ("Forja del Espíritu")
 * -------------------------------------------------
 * Panel del jugador: ve sus Puntos de Progreso (PP), consulta sus stats
 * actuales, y gasta PP para subir atributos.
 *
 * Acceso: solo usuarios autenticados con personaje activo.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'progresion.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol_data.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid       = (int) ($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Personaje activo
$active_pid = 0;
if ($loggedin) {
    if (isset($mybb->user['ope_active_pid']) && (int) $mybb->user['ope_active_pid'] > 0) {
        $active_pid = (int) $mybb->user['ope_active_pid'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $active_pid = (int) $db->fetch_field($cq, 'personaje_activo');
        }
    }
}

// Staff level
$staff_level = 0;
if ($loggedin && $active_pid > 0 && $db->table_exists('rol_personajes')) {
    $staff_level = (int) ($mybb->user['ope_staff_level'] ?? 0);
    if ($staff_level < 1) {
        $sq = $db->simple_select('rol_cuentas', 'staff_level', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($sq)) {
            $staff_level = (int) $db->fetch_field($sq, 'staff_level');
        }
    }
}

// Nombre a mostrar en navbar
$display_name = (string) ($mybb->user['ope_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Cargar personaje ──
$pj = null;
$pj_datos = array();
$pj_stats = array();
if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', '*', "pid = {$active_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj = $db->fetch_array($pq);
        $pj_datos = json_decode((string) $pj['datos'], true);
        if (!is_array($pj_datos)) $pj_datos = array();
        $pj_stats = is_array($pj_datos['stats_efectivas'] ?? null) ? $pj_datos['stats_efectivas'] : array();
    }
}

// ── Cargar PP ──
$pp_data = function_exists('ope_pp_saldo') ? ope_pp_saldo($active_pid) : array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);

// ── Procesar gasto de PP ──
$flash = '';
$flash_kind = 'ok';
if ($loggedin && $active_pid > 0 && $pj && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gastar') === '1') {

    $stat_key = strtoupper(trim((string) $mybb->get_input('stat')));
    $stat_keys = ope_rol_stat_keys();
    if (in_array($stat_key, $stat_keys, true) && function_exists('ope_pp_stat_upgrade_cost') && function_exists('ope_pp_spend')) {
        $current_val = (int) ($pj_stats[$stat_key] ?? 1);
        $coste = ope_pp_stat_upgrade_cost($current_val);

        if ($coste === false) {
            $flash = 'Esa stat ya está al máximo (M+).';
            $flash_kind = 'error';
        } elseif ($pp_data['pp_disponible'] < $coste) {
            $flash = "No tienes suficientes PP. Necesitas {$coste} PP pero solo tienes {$pp_data['pp_disponible']}.";
            $flash_kind = 'error';
        } else {
            // Gastar PP
            $ok = ope_pp_spend($active_pid, $coste, 'gasto_stat', "Subir {$stat_key} de " . ope_pp_rank_from_val($current_val) . ' a ' . ope_pp_rank_from_val($current_val + 1));
            if ($ok) {
                // Actualizar stat en datos JSON
                $pj_stats[$stat_key] = $current_val + 1;
                $pj_datos['stats_efectivas'] = $pj_stats;

                // Recalcular suma y rango
                $suma = array_sum($pj_stats);
                $nuevo_rango = ope_pp_rank_from_sum($suma);

                $db->update_query('rol_personajes', array(
                    'datos'    => $db->escape_string(json_encode($pj_datos, JSON_UNESCAPED_UNICODE)),
                    'rango'    => $db->escape_string($nuevo_rango),
                    'lastedit' => TIME_NOW,
                ), "pid = {$active_pid}");

                // Recalcular PV, EN, PA tras cambio de stats
                if (function_exists('ope_combat_recalc')) {
                    ope_combat_recalc($active_pid);
                }

                // Refrescar datos
                $pp_data = ope_pp_saldo($active_pid);
                $flash = "¡{$stat_key} subida a rango " . ope_pp_rank_from_val($pj_stats[$stat_key]) . "! Has gastado {$coste} PP.";
                $flash_kind = 'ok';
            } else {
                $flash = 'Error al procesar el gasto de PP.';
                $flash_kind = 'error';
            }
        }
    } else {
        $flash = 'Stat no válida.';
        $flash_kind = 'error';
    }
}

// ── PP Log (últimos 20) ──
$pp_log = array();
if ($active_pid > 0 && $db->table_exists('rol_pp_log')) {
    $lq = $db->simple_select('rol_pp_log', '*', "pid = {$active_pid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 20));
    while ($lr = $db->fetch_array($lq)) {
        $pp_log[] = $lr;
    }
}

// ── Función local para formatear stats ──
$RANK_LABELS = array('F' => 'Pésimo', 'E' => 'Muy bajo', 'D' => 'Bajo', 'C' => 'Normal', 'B' => 'Bueno', 'A' => 'Notable', 'S' => 'Excepcional', 'SS' => 'Legendario', 'M' => 'Máximo', 'M+' => 'Trascendente');

// Iniciales para navbar
$initials = '';
if ($loggedin) {
    $parts = preg_split('/\s+/', trim((string) $mybb->user['username']));
    foreach ($parts as $p) {
        if ($p !== '') {
            $initials .= function_exists('mb_substr') ? mb_substr($p, 0, 1, 'UTF-8') : substr($p, 0, 1);
        }
    }
    $initials = function_exists('mb_substr') ? mb_substr($initials, 0, 2, 'UTF-8') : substr($initials, 0, 2);
    $initials = function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
}
$initials_e = htmlspecialchars_uni($initials);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; Progresión</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-progresion">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/ficha.php">Ficha</a>
    <span class="sep">&#8250;</span>
    <b>Progresión</b>
  </div>
</div>

<div class="wrap">

<?php if (!$loggedin || $active_pid < 1): ?>
  <section class="reveal">
    <div class="shead"><h1>Progresión</h1><span class="code">// PP &amp; Stats</span><span class="rule"></span></div>
    <div class="plate"><div class="plate-b">
      <p class="pj-empty"><?php echo $loggedin ? 'Selecciona un <b>personaje activo</b> en tu <a href="'.$bburl.'/personajes.php">expediente</a> para ver tu progresión.' : 'Necesitas <a href="'.$bburl.'/member.php?action=login">acceder</a> para ver tu progresión.'; ?></p>
    </div></div>
  </section>

<?php else: ?>

  <!-- ── Cabecera ── -->
  <section class="reveal">
    <div class="shead">
      <h1>Forja del Espíritu</h1>
      <span class="code">// progresión</span>
      <span class="rule"></span>
    </div>
    <p class="pj-intro">Gasta tus <b>Puntos de Progreso (PP)</b> para mejorar las stats de <b><?php echo htmlspecialchars_uni($pj['nombre']); ?></b> (Rango <?php echo htmlspecialchars_uni($pj['rango']); ?>). Cada subida cuesta más que la anterior: el camino al poder es largo.</p>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
<?php endif; ?>

  <!-- ── Vitales de combate ── -->
  <?php
    $pv = (int) ($pj['pv_max'] ?? 0);
    $en = (int) ($pj['en_max'] ?? 0);
    $pa = (int) ($pj['pa_por_turno'] ?? 0);
    if ($pv < 1 && function_exists('ope_combat_recalc')) {
        $recalc = ope_combat_recalc($active_pid);
        if ($recalc) { $pv = $recalc['pv_max']; $en = $recalc['en_max']; $pa = $recalc['pa_por_turno']; }
    }
  ?>
  <section class="reveal">
    <div class="ope-prog-vitals">
      <div class="ope-prog-vital ope-prog-vital--pv">
        <span class="ope-prog-vital-val"><?php echo $pv; ?></span>
        <span class="ope-prog-vital-label">PV max</span>
      </div>
      <div class="ope-prog-vital ope-prog-vital--en">
        <span class="ope-prog-vital-val"><?php echo $en; ?></span>
        <span class="ope-prog-vital-label">EN max</span>
      </div>
      <div class="ope-prog-vital ope-prog-vital--pa">
        <span class="ope-prog-vital-val"><?php echo $pa; ?></span>
        <span class="ope-prog-vital-label">PA / turno</span>
      </div>
      <div class="ope-prog-vital ope-prog-vital--rango">
        <span class="ope-prog-vital-val"><?php echo htmlspecialchars_uni($pj['rango']); ?></span>
        <span class="ope-prog-vital-label">Rango</span>
      </div>
    </div>
  </section>

  <!-- ── Barra de PP ── -->
  <section class="reveal">
    <div class="ope-prog-ppbar">
      <div class="ope-prog-ppbar-total">
        <span class="ope-prog-ppbar-val"><?php echo $pp_data['pp_disponible']; ?></span>
        <span class="ope-prog-ppbar-label">PP disponibles</span>
      </div>
      <div class="ope-prog-ppbar-detail">
        <div class="ope-prog-ppbar-stat">
          <span class="ope-prog-ppbar-num"><?php echo $pp_data['pp_total']; ?></span>
          <span class="ope-prog-ppbar-lbl">Total ganados</span>
        </div>
        <div class="ope-prog-ppbar-stat">
          <span class="ope-prog-ppbar-num"><?php echo $pp_data['pp_gastado']; ?></span>
          <span class="ope-prog-ppbar-lbl">Gastados</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Stats y costes ── -->
  <section class="reveal">
    <div class="shead shead-sec"><h2>Atributos</h2><span class="code">// stats</span><span class="rule"></span></div>

    <?php
    $stats_catalogo = ope_rol_stats();
    $rank_scale = ope_rol_rank_scale();
    foreach ($stats_catalogo as $pilar_key => $pilar):
    ?>
    <div class="ope-prog-pilar">
      <h3 class="ope-prog-pilar-titulo"><?php echo htmlspecialchars_uni($pilar['label']); ?></h3>
      <div class="ope-prog-stats-grid">
      <?php foreach ($pilar['stats'] as $sk => $sn):
        $val = (int) ($pj_stats[$sk] ?? 1);
        $rank = ope_pp_rank_from_val($val);
        $rank_label = $RANK_LABELS[$rank] ?? '';
        $coste = function_exists('ope_pp_stat_upgrade_cost') ? ope_pp_stat_upgrade_cost($val) : null;
        $puede_subir = ($coste !== false) && $coste !== null && $pp_data['pp_disponible'] >= $coste;
        $next_rank = ($coste !== false && $coste !== null) ? ope_pp_rank_from_val($val + 1) : '—';
      ?>
        <div class="ope-prog-stat<?php echo $puede_subir ? ' ope-prog-stat--up' : ''; ?>">
          <div class="ope-prog-stat-head">
            <span class="ope-prog-stat-key"><?php echo $sk; ?></span>
            <span class="ope-prog-stat-name"><?php echo htmlspecialchars_uni($sn); ?></span>
          </div>
          <div class="ope-prog-stat-body">
            <div class="ope-prog-stat-rank">
              <span class="ope-prog-stat-rangochar"><?php echo htmlspecialchars_uni($rank); ?></span>
              <span class="ope-prog-stat-rangolabel"><?php echo htmlspecialchars_uni($rank_label); ?></span>
            </div>
            <div class="ope-prog-stat-bar">
              <?php for ($i = 1; $i <= 10; $i++): ?>
              <span class="ope-prog-stat-dot<?php echo $i <= $val ? ' ope-prog-stat-dot--on' : ''; ?>"></span>
              <?php endfor; ?>
            </div>
            <div class="ope-prog-stat-coste">
              <?php if ($coste === false): ?>
                <span class="ope-prog-stat-max">MAX</span>
              <?php elseif ($coste !== null): ?>
                <span class="ope-prog-stat-next">→ <?php echo $next_rank; ?></span>
                <span class="ope-prog-stat-pp"><?php echo $coste; ?> PP</span>
                <?php if ($puede_subir): ?>
                <form method="post" action="progresion.php" class="ope-prog-form-inline">
                  <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                  <input type="hidden" name="stat" value="<?php echo $sk; ?>">
                  <input type="hidden" name="gastar" value="1">
                  <button type="submit" class="ope-btn ope-btn-sm ope-btn-hot ope-prog-btn-up">Subir</button>
                </form>
                <?php else: ?>
                <span class="ope-prog-stat-nopp">(necesitas <?php echo $coste; ?> PP)</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </section>

  <!-- ── Historial de PP ── -->
  <?php if (!empty($pp_log)): ?>
  <section class="reveal">
    <div class="shead shead-sec"><h2>Historial</h2><span class="code">// últ. movimientos</span><span class="rule"></span></div>
    <div class="plate">
      <div class="plate-b ope-prog-plate-nopad">
        <table class="ope-prog-log">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>PP</th>
              <th>Detalle</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pp_log as $log):
            $cambio = (int) $log['pp_cambio'];
            $tipo_label = array(
              'post' => 'Post', 'mision' => 'Misión', 'arco' => 'Arco',
              'evento' => 'Evento', 'staff' => 'Staff',
              'gasto_stat' => 'Subir stat', 'gasto_carta' => 'Carta', 'gasto_haki' => 'Haki',
            );
            $tipo_str = $tipo_label[$log['tipo']] ?? $log['tipo'];
            $palabras_str = $log['palabras'] > 0 ? " ({$log['palabras']} palabras)" : '';
          ?>
            <tr class="<?php echo $cambio > 0 ? 'ope-prog-log-gan' : 'ope-prog-log-gas'; ?>">
              <td><?php echo date('d/m/Y', (int) $log['dateline']); ?></td>
              <td><?php echo htmlspecialchars_uni($tipo_str); ?></td>
              <td class="ope-prog-log-pp"><?php echo $cambio > 0 ? '+' . $cambio : $cambio; ?></td>
              <td><?php echo htmlspecialchars_uni($log['notas'] . $palabras_str); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Tabla de referencia de costes ── -->
  <section class="reveal">
    <div class="shead shead-sec"><h2>Costes de Stats</h2><span class="code">// referencia INI-04</span><span class="rule"></span></div>
    <div class="plate">
      <div class="plate-b ope-prog-plate-nopad">
        <table class="ope-prog-log">
          <thead>
            <tr><th>De</th><th>A</th><th>Coste PP</th><th>Acumulado desde F</th></tr>
          </thead>
          <tbody>
          <?php foreach (ope_pp_stat_cost_table() as $r): ?>
            <tr>
              <td><?php echo $r[0]; ?></td>
              <td><?php echo $r[1]; ?></td>
              <td class="ope-prog-log-pp"><?php echo $r[2]; ?></td>
              <td><?php echo $r[3]; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

<?php endif; ?>
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
