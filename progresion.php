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
$stats_ganados = 0;
if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', '*', "pid = {$active_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj = $db->fetch_array($pq);
        $pj_datos = json_decode((string) $pj['datos'], true);
        if (!is_array($pj_datos)) $pj_datos = array();
        $pj_stats = is_array($pj_datos['stats_efectivas'] ?? null) ? $pj_datos['stats_efectivas'] : array();
        $stats_ganados = (int)($pj['stats_ganados'] ?? 0);
    }
}

// ── Cargar PP ──
$pp_data = function_exists('ope_pp_saldo') ? ope_pp_saldo($active_pid) : array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);

// ── Procesar gasto de PP ──
$flash = '';
$flash_kind = 'ok';
if ($mybb->get_input('up') !== '') {
    $up_stat = strtoupper(trim((string) $mybb->get_input('up')));
    if (in_array($up_stat, ope_rol_stat_keys(), true)) {
        $flash = "¡{$up_stat} mejorada! Los PP se han descontado de tu saldo.";
    } else {
        $flash = 'Progresión actualizada.';
    }
    $flash_kind = 'ok';
}
// Procesar subida de nivel
if ($loggedin && $active_pid > 0 && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('subir_nivel') === '1') {

    $stats_ganados_n = (int)($pj['stats_ganados'] ?? 0);
    $nivel_actual_n = (int)($pj['nivel'] ?? 1);
    $puede = function_exists('ope_rol_puede_subir_nivel') ? ope_rol_puede_subir_nivel($nivel_actual_n, $stats_ganados_n) : false;

    if ($puede) {
        $nuevo_nivel = $nivel_actual_n + 1;
        $db->update_query('rol_personajes', array('nivel' => $nuevo_nivel), "pid = {$active_pid}");
        if (function_exists('ope_combat_recalc')) {
            ope_combat_recalc($active_pid);
        }
        header('Location: ' . $bburl . '/progresion.php?subio_nivel=1');
        exit;
    }
}

if ($loggedin && $active_pid > 0 && $pj && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gastar') === '1') {

    // Verificar bloqueo por nivel antes de permitir subir stats
    $stats_ganados_check = (int)($pj['stats_ganados'] ?? 0);
    $nivel_check = (int)($pj['nivel'] ?? 1);
    if (!function_exists('ope_rol_puede_subir_stats')) {
        require_once MYBB_ROOT . 'inc/ope_rol_data.php';
    }
    if (!ope_rol_puede_subir_stats($nivel_check, $stats_ganados_check)) {
        $flash = '¡Has alcanzado el límite de tu nivel! Sube de nivel para poder seguir mejorando tus stats.';
        $flash_kind = 'error';
        // No procesar el gasto
        $mybb->set_input('gastar', '0');
    } else {
        $stat_key = strtoupper(trim((string) $mybb->get_input('stat')));
    $stat_keys = ope_rol_stat_keys();
    if (in_array($stat_key, $stat_keys, true)) {
        $current_val = ope_rol_stat_num($pj_stats, $stat_key);
        $coste = ope_rol_stat_upgrade_cost($current_val);

        if ($coste === false) {
            $flash = 'Esa stat ya está al máximo (M+).';
            $flash_kind = 'error';
        } elseif ($pp_data['pp_disponible'] < $coste) {
            $flash = "No tienes suficientes PP. Necesitas {$coste} PP pero solo tienes {$pp_data['pp_disponible']}.";
            $flash_kind = 'error';
        } else {
            $ok = ope_pp_spend($active_pid, $coste, 'gasto_stat', 'Subir ' . $stat_key . ' de ' . $current_val . ' a ' . ($current_val + 1));
            if ($ok) {
                $pj_stats[$stat_key] = $current_val + 1;
                $pj_datos['stats_efectivas'] = $pj_stats;

                // Incrementar contador de stats ganados
                $db->update_query('rol_personajes', array(
                    'stats_ganados' => $stats_ganados_check + 1,
                ), "pid = {$active_pid}");

                $db->update_query('rol_personajes', array(
                    'datos'    => $db->escape_string(json_encode($pj_datos, JSON_UNESCAPED_UNICODE)),
                    'lastedit' => TIME_NOW,
                ), "pid = {$active_pid}");

                if (function_exists('ope_combat_recalc')) {
                    ope_combat_recalc($active_pid);
                }

                header('Location: ' . $bburl . '/progresion.php?up=' . rawurlencode($stat_key));
                exit;
            }
            $flash = 'Error al procesar el gasto de PP.';
            $flash_kind = 'error';
        }
    } else {
        $flash = 'Stat no válida.';
        $flash_kind = 'error';
    }
    } // fin else (bloqueo nivel)
}

// Refrescar personaje tras POST redirect o recalc lazy
if ($active_pid > 0 && $db->table_exists('rol_personajes')) {
    $pq = $db->simple_select('rol_personajes', '*', "pid = {$active_pid} AND uid = {$uid}", array('limit' => 1));
    if ($db->num_rows($pq)) {
        $pj = $db->fetch_array($pq);
        $pj_datos = json_decode((string) $pj['datos'], true);
        if (!is_array($pj_datos)) $pj_datos = array();
        $pj_stats = is_array($pj_datos['stats_efectivas'] ?? null) ? $pj_datos['stats_efectivas'] : array();
        $stats_ganados = (int)($pj['stats_ganados'] ?? 0);
    }
    $pp_data = function_exists('ope_pp_saldo') ? ope_pp_saldo($active_pid) : $pp_data;
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
// Ya no se usa RANK_LABELS; se usará ope_rol_stat_label() donde se necesite

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

  <?php
    $suma_actual = ope_rol_stat_sum($pj_stats);
    $nivel_actual = (int)($pj['nivel'] ?? 1);
    $nivel_label = ope_rol_nivel_label($nivel_actual);
    $stats_ganados = (int)($pj['stats_ganados'] ?? 0);

    $puede_subir_nivel = function_exists('ope_rol_puede_subir_nivel') ? ope_rol_puede_subir_nivel($nivel_actual, $stats_ganados) : false;
    $puede_subir_stats = function_exists('ope_rol_puede_subir_stats') ? ope_rol_puede_subir_stats($nivel_actual, $stats_ganados) : true;
    $pts_necesarios = function_exists('ope_rol_stats_para_nivel') ? ope_rol_stats_para_nivel($nivel_actual + 1) : 999;

    $meta_nivel = ($nivel_actual + 1) * 10;
    $base_nivel = $nivel_actual * 10;
    $span = max(1, $meta_nivel - $base_nivel);
    $prog_pct = max(0, min(100, (int) round((($suma_actual - $base_nivel) / $span) * 100)));
    $faltan = max(0, $meta_nivel - $suma_actual);
  ?>
  <section class="reveal">
    <div class="shead">
      <h1>Forja del Espíritu</h1>
      <span class="code">// <?php echo htmlspecialchars_uni($pj['nombre']); ?></span>
      <span class="rule"></span>
    </div>
    <div class="ope-prog-hero">
      <div class="ope-prog-hero-rank">
        <span class="ope-prog-hero-rank-val">Nv. <?php echo (int) $nivel_actual; ?></span>
        <span class="ope-prog-hero-rank-lbl"><?php echo htmlspecialchars_uni($nivel_label); ?></span>
      </div>
      <div class="ope-prog-hero-track">
        <div class="ope-prog-hero-track-top">
          <span>Suma de stats: <b><?php echo (int) $suma_actual; ?></b></span>
          <span><b><?php echo (int) $faltan; ?></b> pts &rarr; Nivel <?php echo (int)($nivel_actual + 1); ?></span>
        </div>
        <div class="ope-prog-hero-bar"><span style="width:<?php echo (int) $prog_pct; ?>%"></span></div>
        <p class="ope-prog-hero-note">Gasta <b>PP</b> en tus atributos. El coste aumenta por tramos: 1 PP/pto (5-20), 2 PP/pto (21-40), 3 PP/pto (41-60), 5 PP/pto (61-80), 8 PP/pto (81-100), 12 PP/pto (101+).</p>
      </div>
      <div class="ope-prog-hero-pp">
        <span class="ope-prog-hero-pp-val"><?php echo (int) $pp_data['pp_disponible']; ?></span>
        <span class="ope-prog-hero-pp-lbl">PP disponibles</span>
      </div>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
<?php endif; ?>

  <!-- ── Subida de Nivel ── -->
  <?php if ($puede_subir_nivel): ?>
  <section class="reveal">
    <div class="plate" style="border-color: var(--ember);">
      <div class="plate-h">
        <span class="t">¡Puedes subir de nivel!</span>
        <span class="c">// Nivel <?php echo $nivel_actual; ?> → <?php echo $nivel_actual + 1; ?></span>
      </div>
      <div class="plate-b">
        <p>Has acumulado <b><?php echo $stats_ganados; ?> puntos de stat</b> subidos con PP. ¡Desbloquea el siguiente tramo!</p>
        <form method="post" action="progresion.php" style="margin-top:1rem;">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="subir_nivel" value="1">
          <button type="submit" class="btn btn-hot">Subir a Nivel <?php echo $nivel_actual + 1; ?></button>
        </form>
      </div>
    </div>
  </section>
  <?php elseif (!$puede_subir_stats): ?>
  <section class="reveal">
    <div class="plate" style="border-color: var(--ember);">
      <div class="plate-h">
        <span class="t">¡Límite de nivel alcanzado!</span>
        <span class="c">// Nivel <?php echo $nivel_actual; ?></span>
      </div>
      <div class="plate-b">
        <p>Has llegado al máximo de puntos de stat para tu nivel actual (<b><?php echo $pts_necesarios; ?> pts</b>). Sube de nivel para seguir mejorando.</p>
        <p style="color:var(--ink-dim);">Puntos acumulados: <b><?php echo $stats_ganados; ?></b> / <?php echo $pts_necesarios; ?></p>
        <form method="post" action="progresion.php" style="margin-top:1rem;">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="subir_nivel" value="1">
          <button type="submit" class="btn btn-hot">Subir a Nivel <?php echo $nivel_actual + 1; ?></button>
        </form>
      </div>
    </div>
  </section>
  <?php else: ?>
  <section class="reveal">
    <div class="ope-prog-ppbar">
      <div class="ope-prog-ppbar-total" style="flex-basis:100%;text-align:center;">
        <span class="ope-prog-ppbar-label">Progreso de nivel: <?php echo $stats_ganados; ?> / <?php echo $pts_necesarios; ?> pts para Nivel <?php echo $nivel_actual + 1; ?></span>
        <div class="ope-prog-hero-bar" style="margin-top:0.5rem;"><span style="width:<?php echo min(100, (int)(($stats_ganados / max(1, $pts_necesarios)) * 100)); ?>%"></span></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Vitales de combate ── -->
  <?php
    $pv = (int) ($pj['pv_max'] ?? 0);
    $en = (int) ($pj['en_max'] ?? 0);
    $pa = (int) ($pj['pa_por_turno'] ?? 0);
    if ($pv < 1 && function_exists('ope_combat_recalc')) {
        $recalc = ope_combat_recalc($active_pid);
        if ($recalc) {
            $pv = $recalc['pv_max'];
            $en = $recalc['en_max'];
            $pa = $recalc['pa_por_turno'];
        }
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
        <span class="ope-prog-vital-val">Nv. <?php echo (int)($pj['nivel'] ?? $nivel_actual); ?></span>
        <span class="ope-prog-vital-label">Nivel</span>
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
    foreach ($stats_catalogo as $pilar_key => $pilar):
    ?>
    <div class="ope-prog-pilar">
      <h3 class="ope-prog-pilar-titulo"><?php echo htmlspecialchars_uni($pilar['label']); ?></h3>
      <div class="ope-prog-stats-grid">
      <?php foreach ($pilar['stats'] as $sk => $sn):
        $val = ope_rol_stat_num($pj_stats, $sk);
        $label = ope_rol_stat_label($val);
        $coste = ope_rol_stat_upgrade_cost($val);
        $bloqueado_nivel = !$puede_subir_stats;
        $puede_subir = ($coste !== false) && $pp_data['pp_disponible'] >= $coste && !$bloqueado_nivel;
        $stat_class = 'ope-prog-stat';
        if ($puede_subir) $stat_class .= ' ope-prog-stat--up';
      ?>
        <div class="<?php echo $stat_class; ?>">
          <div class="ope-prog-stat-head">
            <span class="ope-prog-stat-key"><?php echo $sk; ?></span>
            <span class="ope-prog-stat-name"><?php echo htmlspecialchars_uni($sn); ?></span>
          </div>
          <div class="ope-prog-stat-body">
            <div class="ope-prog-stat-rank">
              <span class="ope-prog-stat-rangochar"><?php echo (int) $val; ?></span>
              <span class="ope-prog-stat-rangolabel"><?php echo htmlspecialchars_uni($label); ?></span>
            </div>
            <div class="ope-prog-stat-coste">
              <?php if ($coste === false): ?>
                <span class="ope-prog-stat-max">MAX</span>
              <?php else: ?>
                <span class="ope-prog-stat-next">→ <?php echo (int)($val + 1); ?></span>
                <span class="ope-prog-stat-pp"><?php echo (int) $coste; ?> PP</span>
                <?php if ($bloqueado_nivel): ?>
                <span class="ope-prog-stat-nopp" style="color:var(--ember);">¡Necesitas subir de nivel!</span>
                <?php elseif ($puede_subir): ?>
                <form method="post" action="progresion.php" class="ope-prog-form-inline">
                  <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                  <input type="hidden" name="stat" value="<?php echo $sk; ?>">
                  <input type="hidden" name="gastar" value="1">
                  <button type="submit" class="ope-btn ope-btn-sm ope-btn-hot ope-prog-btn-up">Subir (+1)</button>
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
            <tr><th>Tramo</th><th>Coste por punto</th><th>Ejemplo</th></tr>
          </thead>
          <tbody>
            <tr><td>5 – 20</td><td class="ope-prog-log-pp">1 PP</td><td>Subir de 12 a 13 cuesta 1 PP</td></tr>
            <tr><td>21 – 40</td><td class="ope-prog-log-pp">2 PP</td><td>Subir de 25 a 26 cuesta 2 PP</td></tr>
            <tr><td>41 – 60</td><td class="ope-prog-log-pp">3 PP</td><td>Subir de 50 a 51 cuesta 3 PP</td></tr>
            <tr><td>61 – 80</td><td class="ope-prog-log-pp">5 PP</td><td>Subir de 70 a 71 cuesta 5 PP</td></tr>
            <tr><td>81 – 100</td><td class="ope-prog-log-pp">8 PP</td><td>Subir de 90 a 91 cuesta 8 PP</td></tr>
            <tr><td>101+</td><td class="ope-prog-log-pp">12 PP</td><td>Subir de 105 a 106 cuesta 12 PP</td></tr>
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
