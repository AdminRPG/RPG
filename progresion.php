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
require_once MYBB_ROOT . 'inc/gbe_rol_data.php';
require_once MYBB_ROOT . 'inc/gbe_rol_haki.php';

$bburl     = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname    = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin  = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid       = (int) ($mybb->user['uid'] ?? 0);
$username  = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Personaje activo
$active_pid = 0;
if ($loggedin) {
    if (isset($mybb->user['gbe_active_pid']) && (int) $mybb->user['gbe_active_pid'] > 0) {
        $active_pid = (int) $mybb->user['gbe_active_pid'];
    } elseif ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $active_pid = (int) $db->fetch_field($cq, 'personaje_activo');
        }
    }
}

require_once MYBB_ROOT . 'inc/gbe_user_init.php';

// Staff level
$staff_level = gbe_get_staff_level($uid, $active_pid);

// Nombre a mostrar en navbar
$display_name   = gbe_get_display_name();
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
$pp_data = function_exists('gbe_pp_saldo') ? gbe_pp_saldo($active_pid) : array('pp_total' => 0, 'pp_gastado' => 0, 'pp_disponible' => 0);

// ── Procesar gasto de PP ──
$flash = '';
$flash_kind = 'ok';
if ($mybb->get_input('up') !== '') {
    $up_stat = strtoupper(trim((string) $mybb->get_input('up')));
    if (in_array($up_stat, gbe_rol_stat_keys(), true)) {
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
    $puede = function_exists('gbe_rol_puede_subir_nivel') ? gbe_rol_puede_subir_nivel($nivel_actual_n, $stats_ganados_n) : false;

    if ($puede) {
        $nuevo_nivel = $nivel_actual_n + 1;
        $db->update_query('rol_personajes', array('nivel' => $nuevo_nivel), "pid = {$active_pid}");
        if (function_exists('gbe_combat_recalc')) {
            gbe_combat_recalc($active_pid);
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
    if (!function_exists('gbe_rol_puede_subir_stats')) {
require_once MYBB_ROOT . 'inc/gbe_rol_data.php';
require_once MYBB_ROOT . 'inc/gbe_rol_haki.php';
    }
    if (!gbe_rol_puede_subir_stats($nivel_check, $stats_ganados_check)) {
        $flash = '¡Has alcanzado el límite de tu nivel! Sube de nivel para poder seguir mejorando tus stats.';
        $flash_kind = 'error';
        // No procesar el gasto
        $mybb->set_input('gastar', '0');
    } else {
        $stat_key = strtoupper(trim((string) $mybb->get_input('stat')));
    $stat_keys = gbe_rol_stat_keys();
    if (in_array($stat_key, $stat_keys, true)) {
        $current_val = gbe_rol_stat_num($pj_stats, $stat_key);
        $coste = gbe_rol_stat_upgrade_cost($current_val);

        if ($coste === false) {
            $flash = 'Esa stat ya está al máximo (M+).';
            $flash_kind = 'error';
        } elseif ($pp_data['pp_disponible'] < $coste) {
            $flash = "No tienes suficientes PP. Necesitas {$coste} PP pero solo tienes {$pp_data['pp_disponible']}.";
            $flash_kind = 'error';
        } else {
            $ok = gbe_pp_spend($active_pid, $coste, 'gasto_stat', 'Subir ' . $stat_key . ' de ' . $current_val . ' a ' . ($current_val + 1));
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

                if (function_exists('gbe_combat_recalc')) {
                    gbe_combat_recalc($active_pid);
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

// ── Procesar subida de Haki ──
if ($loggedin && $active_pid > 0 && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('haki_tipo')) {
    $tipo = $mybb->get_input('haki_tipo');
    $resultado = function_exists('gbe_haki_subir') ? gbe_haki_subir($active_pid, $tipo) : 'Sistema Haki no disponible.';
    if ($resultado === '') {
        $flash = '¡Haki mejorado!';
        $flash_kind = 'ok';
    } else {
        $flash = $resultado;
        $flash_kind = 'error';
    }
    $pp_data = function_exists('gbe_pp_saldo') ? gbe_pp_saldo($active_pid) : $pp_data;
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
    $pp_data = function_exists('gbe_pp_saldo') ? gbe_pp_saldo($active_pid) : $pp_data;
}

// ── PP Log (últimos 20) ──
$pp_log = array();
if ($active_pid > 0 && $db->table_exists('rol_pp_log')) {
    $lq = $db->simple_select('rol_pp_log', '*', "pid = {$active_pid}", array('order_by' => 'dateline', 'order_dir' => 'DESC', 'limit' => 20));
    while ($lr = $db->fetch_array($lq)) {
        $pp_log[] = $lr;
    }
}

// ── Haki ──
$haki = function_exists('gbe_haki_get') ? gbe_haki_get($active_pid) : array();
$haki_tipos = function_exists('gbe_haki_tipos') ? gbe_haki_tipos() : array();
$haki_niveles = function_exists('gbe_haki_niveles') ? gbe_haki_niveles() : array();

// ── Función local para formatear stats ──
// Ya no se usa RANK_LABELS; se usará gbe_rol_stat_label() donde se necesite

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
<?php echo gbe_rol_head_base(); ?>
</head>
<body class="gbe-pg-progresion">

<?php echo gbe_rol_navbar_html(); ?>

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
    $suma_actual = gbe_rol_stat_sum($pj_stats);
    $nivel_actual = (int)($pj['nivel'] ?? 1);
    $nivel_label = gbe_rol_nivel_label($nivel_actual);
    $stats_ganados = (int)($pj['stats_ganados'] ?? 0);

    $puede_subir_nivel = function_exists('gbe_rol_puede_subir_nivel') ? gbe_rol_puede_subir_nivel($nivel_actual, $stats_ganados) : false;
    $puede_subir_stats = function_exists('gbe_rol_puede_subir_stats') ? gbe_rol_puede_subir_stats($nivel_actual, $stats_ganados) : true;
    $pts_necesarios = function_exists('gbe_rol_stats_para_nivel') ? gbe_rol_stats_para_nivel($nivel_actual + 1) : 999;

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
    <div class="gbe-prog-hero">
      <div class="gbe-prog-hero-rank">
        <span class="gbe-prog-hero-rank-val">Nv. <?php echo (int) $nivel_actual; ?></span>
        <span class="gbe-prog-hero-rank-lbl"><?php echo htmlspecialchars_uni($nivel_label); ?></span>
      </div>
      <div class="gbe-prog-hero-track">
        <div class="gbe-prog-hero-track-top">
          <span>Suma de stats: <b><?php echo (int) $suma_actual; ?></b></span>
          <span><b><?php echo (int) $faltan; ?></b> pts &rarr; Nivel <?php echo (int)($nivel_actual + 1); ?></span>
        </div>
        <div class="gbe-prog-hero-bar"><span style="width:<?php echo (int) $prog_pct; ?>%"></span></div>
        <p class="gbe-prog-hero-note">Gasta <b>PP</b> en tus atributos para hacerlos crecer. El coste sube por tramos &mdash; consulta la tabla de <b>costes</b> más abajo.</p>
      </div>
      <div class="gbe-prog-hero-pp">
        <span class="gbe-prog-hero-pp-val"><?php echo (int) $pp_data['pp_disponible']; ?></span>
        <span class="gbe-prog-hero-pp-lbl">PP disponibles</span>
      </div>
    </div>
  </section>

<?php if ($flash !== ''): ?>
  <div class="flash <?php echo $flash_kind; ?>"><?php echo $flash; ?></div>
<?php endif; ?>

  <!-- ── Subida de Nivel ── -->
  <?php if ($puede_subir_nivel): ?>
  <section class="reveal">
    <div class="plate gbe-lvl-plate">
      <div class="plate-h">
        <span class="t">¡Puedes subir de nivel!</span>
        <span class="c">// Nivel <?php echo $nivel_actual; ?> → <?php echo $nivel_actual + 1; ?></span>
      </div>
      <div class="plate-b">
        <p>Has acumulado <b><?php echo $stats_ganados; ?> puntos de stat</b> subidos con PP. ¡Desbloquea el siguiente tramo!</p>
        <form method="post" action="progresion.php" class="gbe-lvl-form">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="subir_nivel" value="1">
          <button type="submit" class="btn btn-hot">Subir a Nivel <?php echo $nivel_actual + 1; ?></button>
        </form>
      </div>
    </div>
  </section>
  <?php elseif (!$puede_subir_stats): ?>
  <section class="reveal">
    <div class="plate gbe-lvl-plate gbe-lvl-plate--warn">
      <div class="plate-h">
        <span class="t">¡Límite de nivel alcanzado!</span>
        <span class="c">// Nivel <?php echo $nivel_actual; ?></span>
      </div>
      <div class="plate-b">
        <p>Has llegado al máximo de puntos de stat para tu nivel actual (<b><?php echo $pts_necesarios; ?> pts</b>). Sube de nivel para seguir mejorando.</p>
        <p class="gbe-prog-muted">Puntos acumulados: <b><?php echo $stats_ganados; ?></b> / <?php echo $pts_necesarios; ?></p>
        <form method="post" action="progresion.php" class="gbe-lvl-form">
          <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
          <input type="hidden" name="subir_nivel" value="1">
          <button type="submit" class="btn btn-hot">Subir a Nivel <?php echo $nivel_actual + 1; ?></button>
        </form>
      </div>
    </div>
  </section>
  <?php else: ?>
  <section class="reveal">
    <div class="gbe-prog-ppbar">
      <div class="gbe-prog-ppbar-total gbe-prog-ppbar-total--full">
        <span class="gbe-prog-ppbar-label">Progreso de nivel: <?php echo $stats_ganados; ?> / <?php echo $pts_necesarios; ?> pts para Nivel <?php echo $nivel_actual + 1; ?></span>
        <div class="gbe-prog-hero-bar gbe-prog-hero-bar--mt"><span style="width:<?php echo min(100, (int)(($stats_ganados / max(1, $pts_necesarios)) * 100)); ?>%"></span></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Vitales de combate ── -->
  <?php
    $pv = (int) ($pj['pv_max'] ?? 0);
    $en = (int) ($pj['en_max'] ?? 0);
    $pa = (int) ($pj['pa_por_turno'] ?? 0);
    if ($pv < 1 && function_exists('gbe_combat_recalc')) {
        $recalc = gbe_combat_recalc($active_pid);
        if ($recalc) {
            $pv = $recalc['pv_max'];
            $en = $recalc['en_max'];
            $pa = $recalc['pa_por_turno'];
        }
    }
  ?>
  <section class="reveal">
    <div class="gbe-prog-vitals">
      <div class="gbe-prog-vital gbe-prog-vital--pv">
        <span class="gbe-prog-vital-val"><?php echo $pv; ?></span>
        <span class="gbe-prog-vital-label">PV max</span>
      </div>
      <div class="gbe-prog-vital gbe-prog-vital--en">
        <span class="gbe-prog-vital-val"><?php echo $en; ?></span>
        <span class="gbe-prog-vital-label">EN max</span>
      </div>
      <div class="gbe-prog-vital gbe-prog-vital--pa">
        <span class="gbe-prog-vital-val"><?php echo $pa; ?></span>
        <span class="gbe-prog-vital-label">PA / turno</span>
      </div>
      <div class="gbe-prog-vital gbe-prog-vital--rango">
        <span class="gbe-prog-vital-val">Nv. <?php echo (int)($pj['nivel'] ?? $nivel_actual); ?></span>
        <span class="gbe-prog-vital-label">Nivel</span>
      </div>
    </div>
  </section>

  <!-- ═══ ATRIBUTOS (acción principal) ═══ -->
  <section class="reveal">
    <div class="shead shead-sec"><h2>Atributos</h2><span class="code">// gasta PP para mejorar</span><span class="rule"></span></div>

    <?php
    $stats_catalogo = gbe_rol_stats();
    foreach ($stats_catalogo as $pilar_key => $pilar):
    ?>
    <div class="gbe-prog-pilar">
      <h3 class="gbe-prog-pilar-titulo"><?php echo htmlspecialchars_uni($pilar['label']); ?></h3>
      <div class="gbe-prog-stats-grid">
      <?php foreach ($pilar['stats'] as $sk => $sn):
        $val = gbe_rol_stat_num($pj_stats, $sk);
        $label = gbe_rol_stat_label($val);
        $coste = gbe_rol_stat_upgrade_cost($val);
        $bloqueado_nivel = !$puede_subir_stats;
        $puede_subir = ($coste !== false) && $pp_data['pp_disponible'] >= $coste && !$bloqueado_nivel;
        $stat_class = 'gbe-prog-stat';
        if ($puede_subir) $stat_class .= ' gbe-prog-stat--up';
      ?>
        <div class="<?php echo $stat_class; ?>">
          <div class="gbe-prog-stat-head">
            <span class="gbe-prog-stat-key"><?php echo $sk; ?></span>
            <span class="gbe-prog-stat-name"><?php echo htmlspecialchars_uni($sn); ?></span>
          </div>
          <div class="gbe-prog-stat-body">
            <div class="gbe-prog-stat-rank">
              <span class="gbe-prog-stat-rangochar"><?php echo (int) $val; ?></span>
              <span class="gbe-prog-stat-rangolabel"><?php echo htmlspecialchars_uni($label); ?></span>
            </div>
            <div class="gbe-prog-stat-coste">
              <?php if ($coste === false): ?>
                <span class="gbe-prog-stat-max">MAX</span>
              <?php else: ?>
                <span class="gbe-prog-stat-next">→ <?php echo (int)($val + 1); ?></span>
                <span class="gbe-prog-stat-pp"><?php echo (int) $coste; ?> PP</span>
                <?php if ($bloqueado_nivel): ?>
                <span class="gbe-prog-stat-nopp gbe-prog-stat-nopp--lvl">¡Necesitas subir de nivel!</span>
                <?php elseif ($puede_subir): ?>
                <form method="post" action="progresion.php" class="gbe-prog-form-inline">
                  <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                  <input type="hidden" name="stat" value="<?php echo $sk; ?>">
                  <input type="hidden" name="gastar" value="1">
                  <button type="submit" class="gbe-btn gbe-btn-sm gbe-btn-hot gbe-prog-btn-up">Subir (+1)</button>
                </form>
                <?php else: ?>
                <span class="gbe-prog-stat-nopp">(necesitas <?php echo $coste; ?> PP)</span>
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

  <!-- ═══ COLUMNAS: HAKI | (RACHA + PP) ═══ -->
  <?php
  if (function_exists('gbe_racha_get')) {
      require_once MYBB_ROOT . 'inc/gbe_rol_rachas.php';
  }
  $racha_data = function_exists('gbe_racha_get') ? gbe_racha_get($active_pid) : array('racha_dias' => 0);
  $racha_dias = (int)($racha_data['racha_dias'] ?? 0);
  $racha_hitos = array(7, 14, 21, 30);
  ?>
  <section class="reveal">
    <div class="gbe-prog-cols">

      <!-- Haki -->
      <div class="gbe-prog-col">
        <div class="shead shead-sec"><h2>Haki</h2><span class="code">// voluntad</span><span class="rule"></span></div>
        <?php if (!empty($haki_tipos)): ?>
        <?php foreach ($haki_tipos as $tipo_key => $tipo_info):
          $nivel = (int)($haki[$tipo_key]['nivel'] ?? 0);
          $siguiente = $nivel + 1;
          $puede_subir = ($nivel < 4) && ($tipo_key !== 'haoshoku' || $nivel === 0);
          $info_nivel = isset($haki_niveles[$siguiente]) ? $haki_niveles[$siguiente] : null;
          $coste = $info_nivel ? (int)$info_nivel['coste_pp'] : 0;
          $requiere = $info_nivel ? (int)$info_nivel['requiere_nivel'] : 0;
          $bloqueado = $info_nivel && $nivel_actual < $requiere;
          $sin_pp = $pp_data['pp_disponible'] < $coste;
        ?>
        <div class="plate">
          <div class="plate-h">
            <span class="t"><?php echo htmlspecialchars_uni($tipo_info['nombre']); ?></span>
            <span class="c">// Nivel <?php echo $nivel; ?>/4</span>
          </div>
          <div class="plate-b">
            <p class="mb-8"><?php echo htmlspecialchars_uni($tipo_info['desc']); ?></p>
            <div class="gbe-haki-bar">
              <?php for ($i = 1; $i <= 4; $i++): ?>
              <span class="gbe-haki-dot<?php echo $i <= $nivel ? ' gbe-haki-dot--on' : ''; ?>">Nv.<?php echo $i; ?></span>
              <?php endfor; ?>
            </div>
            <?php if ($nivel >= 4): ?>
              <p class="gbe-haki-max">&#9733; Nivel Supremo alcanzado</p>
            <?php elseif ($tipo_key === 'haoshoku' && $nivel >= 1): ?>
              <p class="gbe-haki-pl">Haoshoku se mejora con Puntos de Leyenda (PL), no con PP.</p>
            <?php elseif ($bloqueado): ?>
              <p class="gbe-haki-locked">Requiere nivel <?php echo $requiere; ?> (tienes <?php echo $nivel_actual; ?>)</p>
            <?php elseif ($sin_pp): ?>
              <p class="gbe-haki-locked">Necesitas <?php echo $coste; ?> PP (tienes <?php echo (int)$pp_data['pp_disponible']; ?>)</p>
            <?php else: ?>
              <form method="post" action="progresion.php">
                <input type="hidden" name="my_post_key" value="<?php echo $mybb->post_code; ?>">
                <input type="hidden" name="haki_tipo" value="<?php echo $tipo_key; ?>">
                <p>Subir a nivel <?php echo $siguiente; ?> (<?php echo htmlspecialchars_uni($info_nivel['nombre']); ?>): <b><?php echo $coste; ?> PP</b></p>
                <button type="submit" class="btn btn-hot">Subir Haki</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="plate"><div class="plate-b"><p class="gbe-prog-muted">El sistema de Haki no está disponible.</p></div></div>
        <?php endif; ?>
      </div>

      <!-- Racha diaria + Puntos de Progreso -->
      <div class="gbe-prog-col">
        <div class="shead shead-sec"><h2>Racha diaria</h2><span class="code">// <?php echo $racha_dias; ?> días</span><span class="rule"></span></div>
        <div class="plate"><div class="plate-b">
          <div class="gbe-racha-bar">
            <?php foreach ($racha_hitos as $hito):
              $alcanzado = $racha_dias >= $hito;
              $flag = "recompensa_dia{$hito}";
              $cobrado = $racha_data[$flag] ?? 0;
              $cls = $cobrado ? 'gbe-racha-dot--cobrado' : ($alcanzado ? 'gbe-racha-dot--on' : '');
            ?>
            <div class="gbe-racha-dot <?php echo $cls; ?>">
              <span class="gbe-racha-dot-dia">Día <?php echo $hito; ?></span>
              <span class="gbe-racha-dot-estado"><?php echo $cobrado ? '✓ Cobrado' : ($alcanzado ? 'Disponible' : ''); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <p class="gbe-racha-note">Postea al menos una vez cada 48h para mantener la racha. Si fallas, vuelve a 0.</p>
        </div></div>

        <div class="shead shead-sec"><h2>Puntos de Progreso</h2><span class="code">// desglose</span><span class="rule"></span></div>
        <div class="gbe-prog-ppbar gbe-prog-ppbar--stack">
          <div class="gbe-prog-ppbar-total">
            <span class="gbe-prog-ppbar-val"><?php echo $pp_data['pp_disponible']; ?></span>
            <span class="gbe-prog-ppbar-label">PP disponibles</span>
          </div>
          <div class="gbe-prog-ppbar-detail">
            <div class="gbe-prog-ppbar-stat">
              <span class="gbe-prog-ppbar-num"><?php echo $pp_data['pp_total']; ?></span>
              <span class="gbe-prog-ppbar-lbl">Total ganados</span>
            </div>
            <div class="gbe-prog-ppbar-stat">
              <span class="gbe-prog-ppbar-num"><?php echo $pp_data['pp_gastado']; ?></span>
              <span class="gbe-prog-ppbar-lbl">Gastados</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ═══ COLUMNAS: HISTORIAL | COSTES ═══ -->
  <section class="reveal">
    <div class="gbe-prog-cols">

      <!-- Historial -->
      <div class="gbe-prog-col">
        <div class="shead shead-sec"><h2>Historial</h2><span class="code">// últ. movimientos</span><span class="rule"></span></div>
        <?php if (!empty($pp_log)): ?>
        <div class="plate">
          <div class="plate-b gbe-prog-plate-nopad">
            <table class="gbe-prog-log">
              <thead>
                <tr><th>Fecha</th><th>Tipo</th><th>PP</th><th>Detalle</th></tr>
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
                <tr class="<?php echo $cambio > 0 ? 'gbe-prog-log-gan' : 'gbe-prog-log-gas'; ?>">
                  <td><?php echo date('d/m/Y', (int) $log['dateline']); ?></td>
                  <td><?php echo htmlspecialchars_uni($tipo_str); ?></td>
                  <td class="gbe-prog-log-pp"><?php echo $cambio > 0 ? '+' . $cambio : $cambio; ?></td>
                  <td><?php echo htmlspecialchars_uni($log['notas'] . $palabras_str); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php else: ?>
        <div class="plate"><div class="plate-b"><p class="gbe-prog-muted">Aún no hay movimientos de PP registrados.</p></div></div>
        <?php endif; ?>
      </div>

      <!-- Costes de Stats -->
      <div class="gbe-prog-col">
        <div class="shead shead-sec"><h2>Costes de Stats</h2><span class="code">// referencia INI-04</span><span class="rule"></span></div>
        <div class="plate">
          <div class="plate-b gbe-prog-plate-nopad">
            <table class="gbe-prog-log">
              <thead>
                <tr><th>Tramo</th><th>Coste por punto</th><th>Ejemplo</th></tr>
              </thead>
              <tbody>
                <tr><td>5 – 20</td><td class="gbe-prog-log-pp">1 PP</td><td>Subir de 12 a 13 cuesta 1 PP</td></tr>
                <tr><td>21 – 40</td><td class="gbe-prog-log-pp">2 PP</td><td>Subir de 25 a 26 cuesta 2 PP</td></tr>
                <tr><td>41 – 60</td><td class="gbe-prog-log-pp">3 PP</td><td>Subir de 50 a 51 cuesta 3 PP</td></tr>
                <tr><td>61 – 80</td><td class="gbe-prog-log-pp">5 PP</td><td>Subir de 70 a 71 cuesta 5 PP</td></tr>
                <tr><td>81 – 100</td><td class="gbe-prog-log-pp">8 PP</td><td>Subir de 90 a 91 cuesta 8 PP</td></tr>
                <tr><td>101+</td><td class="gbe-prog-log-pp">12 PP</td><td>Subir de 105 a 106 cuesta 12 PP</td></tr>
              </tbody>
            </table>
          </div>
        </div>
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
