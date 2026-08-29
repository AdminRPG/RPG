<?php
/**
 * One Piece: Eternal · Resumen — Dashboard completo del personaje
 * ──────────────────────────────────────────────────────────────
 * Muestra TODO: avatar, stats, vitales, identidad, ubicación,
 * tripulación, barco, economía, inventario, vocaciones, actividad, pendientes.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'resumen.php');
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

// ── Cargar personaje completo (D6.3: fuente canónica mybb_ope_personajes) ──
$pj = null;
if ($pid > 0 && $db->table_exists('ope_personajes')) {
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

if (!$pj) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Resumen</title><?php echo ope_rol_head_base(); ?></head>
<body class="ope-pg-resumen">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in"><a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Resumen</b></div></div>
<div class="wrap"><div class="pj-empty"><div class="big">Sin personaje activo</div><p>Activa un personaje para ver tu dashboard.</p>
<div class="acts"><a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a></div></div></div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?></body></html>
<?php exit; }

// ── Decodificar JSONs ──
$datos      = json_decode((string) ($pj['datos'] ?? ''), true) ?: array();
$inventario = array();
$economia   = array();
$bio        = json_decode((string) ($pj['bio'] ?? ''), true) ?: array();

// ── Derivar datos ──
$nombre_e = htmlspecialchars_uni($pj['nombre']);
$avatar_src = trim((string) ($datos['retrato'] ?? ''));
if ($avatar_src === '') $avatar_src = trim((string) ($pj['avatar'] ?? ''));
if ($avatar_src === '') $avatar_src = trim((string) ($pj['icono'] ?? ''));
$av_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));

// D6.3: atributos base = columnas canónicas de ope_personajes.
$stats = array();
foreach (array('FUE' => 'fue', 'DES' => 'des', 'AGI' => 'agi', 'RES' => 'res', 'PER' => 'per', 'INT' => 'inte', 'CAR' => 'car', 'VOL' => 'vol') as $k => $col) {
    $stats[$k] = (int) ($pj[$col] ?? 0);
}
$stats_json_d = json_decode((string) ($pj['datos'] ?? ''), true);
if (is_array($stats_json_d) && !empty($stats_json_d['stats_efectivas'] ?? null)) {
    $stats = array_merge($stats, $stats_json_d['stats_efectivas']);
}

$nivel = max(1, (int) ($pj['nivel'] ?? 1));
$rango_lbl = function_exists('ope_rol_nivel_label') ? ope_rol_nivel_label($nivel) : ('Nivel ' . $nivel);

// XP progress (el motor 7 Seas no guarda stats_ganados: barra por nivel).
$stats_ganados = max(0, ($nivel - 1) * 10);
$floor_fn = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel($nivel) : ($nivel - 1) * 20;
$ceil_fn  = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel($nivel + 1) : $nivel * 20;
$span  = max(1, $ceil_fn - $floor_fn);
$into  = max(0, min($span, $stats_ganados - $floor_fn));
$xppct = (int) round(100 * $into / $span);
$next  = max(0, $ceil_fn - $stats_ganados);

// PV / EN / PA
$pv = (int) ($pj['pv_max'] ?? 0);
$en = (int) ($pj['en_max'] ?? 0);
$pa = (int) ($pj['pa_por_turno'] ?? 0);
if ($pv < 1 && function_exists('ope_combat_calc_pv') && !empty($stats)) $pv = (int) ope_combat_calc_pv($stats, $nivel);
if ($en < 1 && function_exists('ope_combat_calc_en') && !empty($stats)) $en = (int) ope_combat_calc_en($stats, $nivel);
if ($pa < 1 && function_exists('ope_combat_calc_pa') && !empty($stats)) $pa = (int) ope_combat_calc_pa($stats, $nivel);

// Facción (canónica: faccion_id → ope_facciones, o fallback datos['faccion'])
$faccion_key = (string) ($datos['faccion'] ?? '');
$faccion_lbl = '';
$faccion_slug = '';
if ((int) ($pj['faccion_id'] ?? 0) > 0 && $db->table_exists('ope_facciones')) {
    $fq = $db->simple_select('ope_facciones', 'nombre, slug', "id = " . (int) $pj['faccion_id'], array('limit' => 1));
    if ($db->num_rows($fq)) {
        $ff = $db->fetch_array($fq);
        $faccion_lbl  = (string) $ff['nombre'];
        $faccion_slug = (string) $ff['slug'];
    }
}
if ($faccion_lbl === '' && $faccion_key !== '') {
    $facciones_catalogo = function_exists('ope_rol_facciones') ? ope_rol_facciones() : array();
    $faccion_lbl  = isset($facciones_catalogo[$faccion_key]) ? $facciones_catalogo[$faccion_key]['nombre'] : ucfirst($faccion_key);
    $faccion_slug = function_exists('ope_rol_faccion_slug') ? ope_rol_faccion_slug($faccion_key) : $faccion_key;
}
$rango_faccion = '';
if ((int) ($pj['rango_id'] ?? 0) > 0 && $db->table_exists('ope_rangos_faccion')) {
    $rq = $db->simple_select('ope_rangos_faccion', 'nombre', "id = " . (int) $pj['rango_id'], array('limit' => 1));
    if ($db->num_rows($rq)) {
        $rango_faccion = trim((string) $db->fetch_field($rq, 'nombre'));
    }
}
if ($rango_faccion === '') {
    $rango_faccion = trim((string) ($datos['rango'] ?? ''));
}

// Raza (canónica: raza_id → ope_razas)
$raza1_lbl = '';
if ((int) ($pj['raza_id'] ?? 0) > 0 && $db->table_exists('ope_razas')) {
    $rz = $db->simple_select('ope_razas', 'nombre', "id = " . (int) $pj['raza_id'], array('limit' => 1));
    if ($db->num_rows($rz)) {
        $raza1_lbl = (string) $db->fetch_field($rz, 'nombre');
    }
}
if ($raza1_lbl === '') {
    $raza1_key = $datos['raza_principal'] ?? ($datos['raza'] ?? '');
    $raza1_lbl = ucfirst((string) $raza1_key);
}

// Ubicación (canónica: ubicacion_isla_id → ope7_isla_por_id)
$isla_nombre = '';
$isla_region = '';
if ((int) ($pj['ubicacion_isla_id'] ?? 0) > 0 && function_exists('ope7_isla_por_id')) {
    $isla_data = ope7_isla_por_id((int) $pj['ubicacion_isla_id']);
    if ($isla_data) {
        $isla_nombre = htmlspecialchars_uni((string) ($isla_data['nombre'] ?? ''));
        $isla_region = htmlspecialchars_uni((string) ($isla_data['region'] ?? ''));
    }
}

// Economía (canónica: cartera en mybb_ope_carteras)
$berries = 0;
if (function_exists('ope7_cartera_get') && $pid > 0) {
    $car = ope7_cartera_get($pid);
    $berries = (int) ($car['cartera'] ?? 0) + (int) ($car['boveda'] ?? 0);
}

// PP (puntos de progreso)
$pp_disponible = 0;
if (function_exists('ope_pp_saldo')) {
    $pp_row = ope_pp_saldo($pid);
    $pp_disponible = (int) ($pp_row['pp_disponible'] ?? 0);
}

// Tripulación (canónica: ope_tripulantes → ope_tripulaciones)
$tripulacion = null;
$es_capitan = false;
if ($db->table_exists('ope_tripulantes') && $db->table_exists('ope_tripulaciones')) {
    $tq = $db->query("SELECT t.*, tm.rol, tm.estado
        FROM " . TABLE_PREFIX . "ope_tripulantes tm
        JOIN " . TABLE_PREFIX . "ope_tripulaciones t ON t.id = tm.tripulacion_id
        WHERE tm.personaje_id = {$pid} AND tm.estado = 'activo' AND t.estado = 'activa'
        LIMIT 1");
    if ($db->num_rows($tq)) {
        $tripulacion = $db->fetch_array($tq);
        $es_capitan = (strtolower($tripulacion['rol'] ?? '') === 'capitan');
    }
}
$miembros_trip = array();
if ($tripulacion) {
    $mq = $db->query("SELECT p.nombre, p.nivel, p.avatar, p.id AS pid, tm.rol
        FROM " . TABLE_PREFIX . "ope_tripulantes tm
        JOIN " . TABLE_PREFIX . "ope_personajes p ON p.id = tm.personaje_id
        WHERE tm.tripulacion_id = " . (int)$tripulacion['id'] . " AND tm.estado = 'activo'
        ORDER BY tm.rol = 'capitan' DESC, p.nombre ASC");
    while ($mrow = $db->fetch_array($mq)) {
        $miembros_trip[] = $mrow;
    }
}

// Barco (canónico: mybb_ope_barcos)
$barco = null;
if ($pid > 0 && function_exists('ope7_barco_flota') && ope7_tabla_existe('barcos')) {
    $barcos = ope7_barco_flota($pid);
    if (!empty($barcos)) $barco = $barcos[0];
}

// Inventario (canónico: ope_inventario_personaje → ope_objetos)
$inv_items = array();
$inv_count = 0;
$inv_en_venta = 0;
$inv_legendarias = 0;
$inv_raras = 0;
if ($db->table_exists('ope_inventario_personaje') && $db->table_exists('ope_objetos')) {
    $iq = $db->query("SELECT o.nombre, o.rareza, o.categoria, i.zona, i.cantidad
        FROM " . TABLE_PREFIX . "ope_inventario_personaje i
        JOIN " . TABLE_PREFIX . "ope_objetos o ON o.id = i.objeto_id
        WHERE i.personaje_id = {$pid}");
    while ($irow = $db->fetch_array($iq)) {
        $irow['pid'] = $pid;
        $inv_items[] = $irow;
        $inv_count++;
        if ((string) ($irow['zona'] ?? '') === 'venta') $inv_en_venta++;
        $rz = strtolower((string) ($irow['rareza'] ?? ''));
        if ($rz === 'legendaria' || $rz === 'legendary') $inv_legendarias++;
        elseif ($rz === 'rara' || $rz === 'rare') $inv_raras++;
    }
}

// Vocaciones
$vocaciones = function_exists('ope_rol_pj_vocaciones') ? ope_rol_pj_vocaciones($pid) : array();

// Trámites pendientes (canónico: ope_tramites del motor 5.21)
$tramites_pend = 0;
$tramites_list = array();
if ($db->table_exists('ope_tramites')) {
    $tq = $db->simple_select('ope_tramites', 'id AS tid, numero AS tipo, estado, fecha_creacion AS dateline', "personaje_id = {$pid} AND estado = 'pendiente'", array('order_by' => 'fecha_creacion', 'order_dir' => 'DESC', 'limit' => 5));
    while ($trow = $db->fetch_array($tq)) {
        $trow['tipo'] = '#' . (int) ($trow['tipo'] ?? 0);
        $tramites_list[] = $trow;
        $tramites_pend++;
    }
}

// D6.3: el motor 7 Seas no tiene solicitudes de tripulación pendientes (el
// ingreso lo cursa el capitán por trámite 64).
$sol_trip_pend = 0;

// Mensajes no leídos
$msgs_no_leidos = function_exists('ope_rol_mensajes_no_leidos') ? ope_rol_mensajes_no_leidos($pid) : 0;

// Alertas no leídas
$alertas_no_leidas = function_exists('ope_rol_alertas_no_leidas') ? ope_rol_alertas_no_leidas($pid) : 0;

// Stats labels
$stat_labels = array(
    'FUE' => array('Fuerza', 'Das-Stärke'),
    'DES' => array('Destreza', 'Das-Geschick'),
    'CON' => array('Constitución', 'Die-Konstitution'),
    'INT' => array('Inteligencia', 'Die-Intelligenz'),
    'PER' => array('Percepción', 'Die-Wahrnehmung'),
    'VOL' => array('Voluntad', 'Der-Wille'),
);
$stat_max = function_exists('ope_rol_stat_techo') ? ope_rol_stat_techo() : 30;

// Bio data
$edad   = trim((string) ($bio['edad'] ?? $datos['edad'] ?? ''));
$genero = trim((string) ($bio['genero'] ?? $datos['genero'] ?? ''));
$apodo  = trim((string) ($bio['apodo'] ?? $datos['apodo'] ?? ''));
$personalidad = trim((string) ($bio['desc_psicologica'] ?? $pj['personalidad'] ?? ''));
$historia = trim((string) ($bio['historia'] ?? $bio['pasado'] ?? ''));
$desc_fisica = trim((string) ($bio['desc_fisica'] ?? $pj['desc_fisica'] ?? ''));

// Virtudes y defectos
$virtudes = is_array($datos['virtudes'] ?? null) ? $datos['virtudes'] : array();
$defectos = is_array($datos['defectos'] ?? null) ? $datos['defectos'] : array();

$fac_slug_e = htmlspecialchars_uni($faccion_slug);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · <?php echo $nombre_e; ?> — Resumen</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-resumen" data-fac="<?php echo $fac_slug_e; ?>">
<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span><b>Resumen</b>
</div></div>

<div class="wrap resumen-wrap">

  <!-- ════════════════════════════════════════════════════════════
       HERO: Avatar + Identidad + XP
       ════════════════════════════════════════════════════════════ -->
  <section class="resumen-hero reveal">
    <div class="res-hero-left">
      <div class="res-hero-avatar">
        <?php if ($avatar_src !== ''): ?>
          <img src="<?php echo htmlspecialchars_uni($avatar_src); ?>" alt="<?php echo $nombre_e; ?>" onerror="this.parentElement.innerHTML='<span><?php echo htmlspecialchars_uni($av_initial); ?></span>'">
        <?php else: ?>
          <span><?php echo htmlspecialchars_uni($av_initial); ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="res-hero-right">
      <div class="res-hero-top">
        <div class="res-hero-id">
          <span class="res-kicker"><?php echo htmlspecialchars_uni($rango_lbl); ?></span>
          <h1 class="res-hero-name"><?php echo $nombre_e; ?></h1>
          <div class="res-hero-meta">
            <?php if ($apodo !== ''): ?><span class="res-meta-chip">"<?php echo htmlspecialchars_uni($apodo); ?>"</span><?php endif; ?>
            <?php if ($faccion_lbl !== ''): ?><span class="res-meta-chip res-fac-chip fac-<?php echo $fac_slug_e; ?>"><?php echo htmlspecialchars_uni($faccion_lbl); ?></span><?php endif; ?>
            <?php if ($rango_faccion !== ''): ?><span class="res-meta-chip"><?php echo htmlspecialchars_uni($rango_faccion); ?></span><?php endif; ?>
            <?php if ($raza1_lbl !== '' && $raza1_lbl !== 'Array'): ?><span class="res-meta-chip"><?php echo htmlspecialchars_uni($raza1_lbl); ?></span><?php endif; ?>
          </div>
        </div>
        <div class="res-hero-level">
          <span class="res-lv-num"><?php echo $nivel; ?></span>
          <span class="res-lv-lbl">NIVEL</span>
        </div>
      </div>
      <!-- XP bar -->
      <div class="res-xp-bar">
        <div class="res-xp-track"><span style="width:<?php echo $xppct; ?>%"></span></div>
        <span class="res-xp-label">NEXT <?php echo $next; ?> pts · <?php echo $xppct; ?>%</span>
      </div>
      <!-- Quick stats row -->
      <div class="res-hero-quick">
        <span class="res-quick-pill">PV <b><?php echo $pv; ?></b></span>
        <span class="res-quick-pill">EN <b><?php echo $en; ?></b></span>
        <span class="res-quick-pill">PA <b><?php echo $pa; ?></b></span>
        <span class="res-quick-pill">PP <b><?php echo $pp_disponible; ?></b></span>
        <?php if ($isla_nombre !== ''): ?>
          <a href="<?php echo $bburl; ?>/mapa.php" class="res-quick-pill res-quick-location"><?php echo $isla_nombre; ?></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       GRID 2 COLS: Stats + Datos personales
       ════════════════════════════════════════════════════════════ -->
  <div class="res-grid-2col">

    <!-- STATS -->
    <section class="res-section reveal">
      <div class="res-section-head">
        <h2>Atributos</h2>
        <span class="res-section-sub">// <?php echo $stats_ganados; ?> pts gastados</span>
      </div>
      <div class="res-stats-grid">
        <?php foreach ($stat_labels as $key => $meta): ?>
          <?php $val = (int) ($stats[$key] ?? 5); $pct = (int) round(100 * min($val, $stat_max) / $stat_max); ?>
          <div class="res-stat-row">
            <span class="res-stat-key"><?php echo $key; ?></span>
            <div class="res-stat-bar"><span style="width:<?php echo $pct; ?>%" class="res-stat-fill res-stat-<?php echo strtolower($key); ?>"></span></div>
            <span class="res-stat-val"><?php echo $val; ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- DATOS PERSONALES -->
    <section class="res-section reveal">
      <div class="res-section-head">
        <h2>Ficha Personal</h2>
        <span class="res-section-sub">// expediente</span>
      </div>
      <div class="res-info-grid">
        <?php if ($edad !== ''): ?>
          <div class="res-info-item"><span class="res-info-label">Edad</span><span class="res-info-val"><?php echo htmlspecialchars_uni($edad); ?></span></div>
        <?php endif; ?>
        <?php if ($genero !== ''): ?>
          <div class="res-info-item"><span class="res-info-label">Género</span><span class="res-info-val"><?php echo htmlspecialchars_uni($genero); ?></span></div>
        <?php endif; ?>
        <?php if ($raza1_lbl !== '' && $raza1_lbl !== 'Array'): ?>
          <div class="res-info-item"><span class="res-info-label">Raza</span><span class="res-info-val"><?php echo htmlspecialchars_uni($raza1_lbl); ?></span></div>
        <?php endif; ?>
        <?php if ($faccion_lbl !== ''): ?>
          <div class="res-info-item"><span class="res-info-label">Facción</span><span class="res-info-val"><?php echo htmlspecialchars_uni($faccion_lbl); ?></span></div>
        <?php endif; ?>
        <?php if ($isla_nombre !== ''): ?>
          <div class="res-info-item"><span class="res-info-label">Ubicación</span><span class="res-info-val"><a href="<?php echo $bburl; ?>/mapa.php"><?php echo $isla_nombre; ?></a><?php if ($isla_region !== ''): ?> · <small><?php echo $isla_region; ?></small><?php endif; ?></span></div>
        <?php endif; ?>
        <?php if ($rango_faccion !== ''): ?>
          <div class="res-info-item"><span class="res-info-label">Rango</span><span class="res-info-val"><?php echo htmlspecialchars_uni($rango_faccion); ?></span></div>
        <?php endif; ?>
        <div class="res-info-item"><span class="res-info-label">Estado</span><span class="res-info-val"><span class="res-status res-status--<?php echo htmlspecialchars_uni($pj['estado']); ?>"><?php echo ucfirst(htmlspecialchars_uni($pj['estado'])); ?></span></span></div>
      </div>

      <?php if (!empty($virtudes) || !empty($defectos)): ?>
      <div class="res-virtues-row">
        <?php if (!empty($virtudes)): ?>
          <div class="res-virtue-box">
            <span class="res-virtue-title">Virtudes</span>
            <div class="res-virtue-tags">
              <?php foreach (array_slice($virtudes, 0, 5) as $v): ?>
                <span class="res-vtag res-vtag--virtue"><?php echo htmlspecialchars_uni(is_array($v) ? ($v['nombre'] ?? $v) : $v); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if (!empty($defectos)): ?>
          <div class="res-virtue-box">
            <span class="res-virtue-title">Defectos</span>
            <div class="res-virtue-tags">
              <?php foreach (array_slice($defectos, 0, 5) as $d): ?>
                <span class="res-vtag res-vtag--defect"><?php echo htmlspecialchars_uni(is_array($d) ? ($d['nombre'] ?? $d) : $d); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- ════════════════════════════════════════════════════════════
       ECONOMÍA + INVENTARIO + VOCACIONES
       ════════════════════════════════════════════════════════════ -->
  <div class="res-grid-3col">

    <!-- Economía -->
    <section class="res-section res-section--compact reveal">
      <div class="res-section-head">
        <h2>Economía</h2>
        <span class="res-section-sub">// fortuna</span>
      </div>
      <div class="res-eco-main">
        <div class="res-eco-berry-icon">₿</div>
        <div class="res-eco-berry-val"><?php echo number_format($berries, 0, ',', '.'); ?></div>
        <div class="res-eco-berry-lbl">berries</div>
      </div>
      <a href="<?php echo $bburl; ?>/tienda-personal.php" class="res-eco-link">Ir a Mi Tienda →</a>
    </section>

    <!-- Inventario -->
    <section class="res-section res-section--compact reveal">
      <div class="res-section-head">
        <h2>Inventario</h2>
        <span class="res-section-sub">// <?php echo $inv_count; ?> objetos</span>
      </div>
      <div class="res-inv-stats">
        <div class="res-inv-pill"><span class="res-inv-dot res-inv-dot--all"></span><?php echo $inv_count; ?> total</div>
        <div class="res-inv-pill"><span class="res-inv-dot res-inv-dot--venta"></span><?php echo $inv_en_venta; ?> en venta</div>
        <div class="res-inv-pill"><span class="res-inv-dot res-inv-dot--rare"></span><?php echo $inv_raras; ?> raras</div>
        <div class="res-inv-pill"><span class="res-inv-dot res-inv-dot--legend"></span><?php echo $inv_legendarias; ?> legendarias</div>
      </div>
      <a href="<?php echo $bburl; ?>/tienda-personal.php" class="res-eco-link">Gestionar inventario →</a>
    </section>

    <!-- Vocaciones -->
    <section class="res-section res-section--compact reveal">
      <div class="res-section-head">
        <h2>Vocación</h2>
        <span class="res-section-sub">// clase & oficio</span>
      </div>
      <?php if (!empty($vocaciones)): ?>
        <div class="res-voc-list">
          <?php foreach ($vocaciones as $voc): ?>
            <div class="res-voc-item">
              <span class="res-voc-level">Lv.<?php echo (int) ($voc['nivel'] ?? 0); ?></span>
              <span class="res-voc-name"><?php echo htmlspecialchars_uni($voc['nombre'] ?? $voc['tipo'] ?? ''); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="res-empty-text">Sin vocaciones registradas.</p>
      <?php endif; ?>
    </section>
  </div>

  <!-- ════════════════════════════════════════════════════════════
       TRIPULACIÓN + BARCO
       ════════════════════════════════════════════════════════════ -->
  <div class="res-grid-2col">

    <!-- Tripulación -->
    <section class="res-section reveal">
      <div class="res-section-head">
        <h2>Tripulación</h2>
        <?php if ($tripulacion): ?>
          <a href="<?php echo $bburl; ?>/tripulacion.php" class="res-section-link">Ver →</a>
        <?php endif; ?>
      </div>
      <?php if ($tripulacion): ?>
        <div class="res-trip-header">
          <div class="res-trip-emblem">⚓</div>
          <div class="res-trip-info">
            <h3 class="res-trip-name"><?php echo htmlspecialchars_uni($tripulacion['nombre']); ?></h3>
            <?php if (!empty($tripulacion['lema'])): ?>
              <p class="res-trip-motto">"<?php echo htmlspecialchars_uni($tripulacion['lema']); ?>"</p>
            <?php endif; ?>
            <div class="res-trip-meta">
              <span class="res-meta-chip"><?php echo $es_capitan ? 'Capitán' : ucfirst(htmlspecialchars_uni($tripulacion['rol'] ?? '')); ?></span>
              <span class="res-meta-chip"><?php echo count($miembros_trip); ?> miembros</span>
            </div>
          </div>
        </div>
        <div class="res-trip-roster">
          <?php foreach (array_slice($miembros_trip, 0, 8) as $m): ?>
            <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int)$m['pid']; ?>" class="res-trip-member">
              <div class="res-trip-mavatar">
                <?php $mav = trim((string) ($m['avatar'] ?? '')); $minit = function_exists('mb_substr') ? mb_strtoupper(mb_substr($m['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($m['nombre'], 0, 1)); ?>
                <?php if ($mav !== ''): ?>
                  <img src="<?php echo htmlspecialchars_uni($mav); ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
                  <span class="res-hidden-init"><?php echo htmlspecialchars_uni($minit); ?></span>
                <?php else: ?>
                  <span><?php echo htmlspecialchars_uni($minit); ?></span>
                <?php endif; ?>
              </div>
              <div class="res-trip-mname"><?php echo htmlspecialchars_uni($m['nombre']); ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="res-empty-state">
          <p>Sin tripulación activa.</p>
          <a href="<?php echo $bburl; ?>/tripulacion.php" class="btn btn-ghost btn-sm">Explorar tripulaciones</a>
        </div>
      <?php endif; ?>
    </section>

    <!-- Barco -->
    <section class="res-section reveal">
      <div class="res-section-head">
        <h2>Embarcación</h2>          <?php if ($barco): ?>
            <a href="<?php echo $bburl; ?>/barco.php" class="res-section-link">Ver →</a>
          <?php endif; ?>
        </div>
      <?php if ($barco): ?>
        <div class="res-ship-card">
          <div class="res-ship-hero">
            <div class="res-ship-ph">⛵</div>
          </div>
          <div class="res-ship-info">
            <h3 class="res-ship-name"><?php echo htmlspecialchars_uni($barco['nombre']); ?></h3>
            <div class="res-ship-meta">
              <span class="res-meta-chip"><?php echo htmlspecialchars_uni($barco['tipo']['nombre'] ?? ''); ?></span>
              <span class="res-meta-chip">Nivel <?php echo htmlspecialchars_uni($barco['nivel'] ?? 'N1'); ?></span>
              <span class="res-meta-chip"><?php echo (int) ($barco['pv_actual'] ?? 0); ?>/<?php echo (int) ($barco['casco_pv'] ?? 0); ?> PV</span>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="res-empty-state">
          <p>Sin embarcación registrada.</p>
          <a href="<?php echo $bburl; ?>/barco.php" class="btn btn-ghost btn-sm">Solicitar barco</a>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- ════════════════════════════════════════════════════════════
       PERSONALIDAD + HISTORIA
       ════════════════════════════════════════════════════════════ -->
  <?php if ($personalidad !== '' || $historia !== '' || $desc_fisica !== ''): ?>
  <section class="res-section res-section--full reveal">
    <div class="res-section-head">
      <h2>Biografía</h2>
      <span class="res-section-sub">// perfil</span>
    </div>
    <div class="res-bio-grid">
      <?php if ($desc_fisica !== ''): ?>
        <div class="res-bio-block">
          <h4 class="res-bio-label">Apariencia</h4>
          <p class="res-bio-text"><?php echo nl2br(htmlspecialchars_uni(mb_strimwidth($desc_fisica, 0, 500, '...'))); ?></p>
        </div>
      <?php endif; ?>
      <?php if ($personalidad !== ''): ?>
        <div class="res-bio-block">
          <h4 class="res-bio-label">Personalidad</h4>
          <p class="res-bio-text"><?php echo nl2br(htmlspecialchars_uni(mb_strimwidth($personalidad, 0, 500, '...'))); ?></p>
        </div>
      <?php endif; ?>
      <?php if ($historia !== ''): ?>
        <div class="res-bio-block">
          <h4 class="res-bio-label">Historia</h4>
          <p class="res-bio-text"><?php echo nl2br(htmlspecialchars_uni(mb_strimwidth($historia, 0, 600, '...'))); ?></p>
        </div>
      <?php endif; ?>
    </div>
    <div class="res-bio-cta">
      <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $pid; ?>" class="btn btn-ghost btn-sm">Ver ficha completa →</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- ════════════════════════════════════════════════════════════
       PENDIENTES: Trámites + Solicitudes + Mensajes + Alertas
       ════════════════════════════════════════════════════════════ -->
  <?php if ($tramites_pend > 0 || $sol_trip_pend > 0 || $msgs_no_leidos > 0 || $alertas_no_leidas > 0): ?>
  <section class="res-section res-section--full reveal">
    <div class="res-section-head">
      <h2>Pendientes</h2>
      <span class="res-section-sub">// atención requerida</span>
    </div>
    <div class="res-pend-grid">
      <?php if ($tramites_pend > 0): ?>
        <a href="<?php echo $bburl; ?>/tramites.php" class="res-pend-card res-pend--tramites">
          <div class="res-pend-count"><?php echo $tramites_pend; ?></div>
          <div class="res-pend-label">Trámites pendientes</div>
          <div class="res-pend-detail">Revisa el estado de tus solicitudes al staff</div>
        </a>
      <?php endif; ?>
      <?php if ($sol_trip_pend > 0): ?>
        <a href="<?php echo $bburl; ?>/tripulacion.php" class="res-pend-card res-pend--trip">
          <div class="res-pend-count"><?php echo $sol_trip_pend; ?></div>
          <div class="res-pend-label">Solicitudes de tripulación</div>
          <div class="res-pend-detail">Tienes propuestas de embarque esperando</div>
        </a>
      <?php endif; ?>
      <?php if ($msgs_no_leidos > 0): ?>
        <a href="<?php echo $bburl; ?>/mensajes.php" class="res-pend-card res-pend--msgs">
          <div class="res-pend-count"><?php echo $msgs_no_leidos; ?></div>
          <div class="res-pend-label">Mensajes sin leer</div>
          <div class="res-pend-detail">Tienes mensajes nuevos de otros personajes</div>
        </a>
      <?php endif; ?>
      <?php if ($alertas_no_leidas > 0): ?>
        <a href="<?php echo $bburl; ?>/alertas.php" class="res-pend-card res-pend--alertas">
          <div class="res-pend-count"><?php echo $alertas_no_leidas; ?></div>
          <div class="res-pend-label">Alertas</div>
          <div class="res-pend-detail">Notificaciones del sistema pendientes</div>
        </a>
      <?php endif; ?>
    </div>
    <?php if (!empty($tramites_list)): ?>
    <div class="res-tramites-mini">
      <h4 class="res-mini-title">Últimos trámites</h4>
      <?php foreach ($tramites_list as $tr): ?>
        <div class="res-tramite-row">
          <span class="res-tramite-type"><?php echo htmlspecialchars_uni(ucfirst(str_replace('_', ' ', $tr['tipo'] ?? ''))); ?></span>
          <span class="res-tramite-date"><?php echo my_date('j M Y', (int)$tr['dateline']); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

</div><!-- /resumen-wrap -->

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('vis'); io.unobserve(e.target); }
  }), { threshold: .06 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
} else {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('vis'));
}
</script>
</body>
</html>
