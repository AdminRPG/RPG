<?php
/**
 * One Piece: Eternal · Ficha del Barco (barco.php)
 * -------------------------------------------------------------------------
 * Visor de flota sobre la fuente canónica (mybb_ope_barcos, 5.17/cap. 18).
 * Muestra la flota del personaje activo y la ficha del barco seleccionado
 * (tipo, madera, nivel, casco, maniobra, módulos, tripulación vinculada).
 * La gestión (compra, construcción, mejora, módulos, reparación, venta) se
 * hace desde el Astillero (astillero.php) y los trámites 39–44.
 * Fuente canónica: mybb_ope_barcos.
 */
define('IN_MYBB', 1);
define('THIS_SCRIPT', 'barco.php');
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

// Personaje activo (fuente canónica mybb_ope_personajes)
$pj = null;
if ($pid > 0 && ope7_tabla_existe('personajes')) {
    $q = $db->simple_select('ope_personajes', '*', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

// Flota canónica del personaje
$barcos = array();
if ($pid > 0 && ope7_tabla_existe('barcos')) {
    $barcos = ope7_barco_flota($pid);
}

// Barco seleccionado (por parámetro o el primero de la flota)
$selected = (int) $mybb->get_input('barco_id');
$barco = null;
foreach ($barcos as $b) {
    if ((int) $b['id'] === $selected) {
        $barco = $b;
        break;
    }
}
if (!$barco && !empty($barcos)) {
    $barco = $barcos[0];
}

// Datos derivados del barco seleccionado
$tipo    = $barco['tipo'] ?? null;    // fila de ope_tipos_barcos
$madera  = $barco['madera'] ?? null;  // fila de ope_maderas_casco
$nivel   = (string) ($barco['nivel'] ?? 'N1');
$nivel_i = array('N1' => 0, 'N2' => 1, 'N3' => 2);
$idx     = $nivel_i[$nivel] ?? 0;

$casco_stats = $tipo ? (array) json_decode((string) ($tipo['casco'] ?? '[]'), true) : array();
$maniobra_stats = $tipo ? (array) json_decode((string) ($tipo['maniobra'] ?? '[]'), true) : array();
$canones_stats = $tipo ? (array) json_decode((string) ($tipo['canones'] ?? '[]'), true) : array();
$ranuras_stats = $tipo ? (array) json_decode((string) ($tipo['ranuras'] ?? '[]'), true) : array();

$casco_max = (int) ($casco_stats[$idx] ?? (int) ($barco['casco_pv'] ?? 0));
$casco_pv  = (int) ($barco['pv_actual'] ?? $casco_max);
$maniobra  = (int) ($maniobra_stats[$idx] ?? 0);
$canones   = (int) ($canones_stats[$idx] ?? 0);
$ranuras   = (int) ($ranuras_stats[$idx] ?? 0);

$grado = $barco ? ope7_barco_grado_danio($barco) : '';
$estados = array(
    'activo'          => 'En servicio',
    'danado_leve'     => 'Daño leve',
    'danado_moderado' => 'Daño moderado',
    'danado_grave'    => 'Daño grave',
    'hundido'         => 'Hundido',
    'en_reparacion'   => 'En reparación',
    'vendido'         => 'Vendido',
);
$estado_label = $barco ? ($estados[$barco['estado']] ?? $barco['estado']) : '';

// Módulos instalados (nombres del catálogo canónico)
$modulos = array();
if ($barco) {
    foreach (ope7_barco_modulos($barco) as $mid) {
        $m = ope7_modulo_barco_por_id((int) $mid);
        if ($m) {
            $modulos[] = $m;
        }
    }
}

// Tripulación vinculada (5.21-ter)
$trip = null;
if ($barco && (int) ($barco['tripulacion_id'] ?? 0) > 0 && function_exists('ope7_trip_get')) {
    $trip = ope7_trip_get((int) $barco['tripulacion_id']);
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> · Ficha del Barco</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-barco">
<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b>Ficha del Barco</b>
  </div>
</div>

<div class="wrap">

  <section class="reveal">
    <div class="shead">
      <h1>Ficha del Barco</h1>
      <span class="code">FLOTA · 18</span>
      <div class="rule"></div>
      <a href="<?php echo $bburl; ?>/astillero.php" class="btn btn-ghost btn-sm">Astillero →</a>
    </div>
  </section>

  <?php if (empty($barcos)): ?>
    <section class="plate reveal">
      <div class="plate-h">Sin embarcación registrada</div>
      <div class="plate-b">
        <p>Tu personaje aún no tiene un barco en la flota. Compra o construye tu primera nave en el Astillero
           (trámites 39–44 del foro): elige tipo y madera, paga la mano de obra y ¡a navegar por los Siete Mares!</p>
        <p>
          <a href="<?php echo $bburl; ?>/astillero.php" class="btn btn-hot">Ir al Astillero</a>
          <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-ghost">Ventanillas</a>
        </p>
      </div>
    </section>
  <?php else: ?>

    <?php if (count($barcos) > 1): ?>
    <div class="barco-switcher-bar reveal">
      <span class="switcher-lbl">Tu flota</span>
      <div class="switcher-btns">
        <?php foreach ($barcos as $b): ?>
          <a href="<?php echo $bburl; ?>/barco.php?barco_id=<?php echo (int) $b['id']; ?>"
             class="btn btn-sm <?php echo ((int) $b['id'] === (int) $barco['id']) ? 'btn-hot' : 'btn-ghost'; ?>">
            <?php echo htmlspecialchars_uni($b['nombre']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <section class="barco-hero-plate reveal">
      <div class="barco-hero-header">
        <div>
          <span class="barco-type-chip"><?php echo htmlspecialchars_uni($tipo['nombre'] ?? 'Embarcación'); ?></span>
          <h2 class="barco-hero-title"><?php echo htmlspecialchars_uni($barco['nombre']); ?></h2>
          <div class="barco-hero-meta">
            <span>Nivel <strong><?php echo htmlspecialchars_uni($nivel); ?></strong></span>
            <span>Madera <strong><?php echo htmlspecialchars_uni($madera['nombre'] ?? '—'); ?></strong></span>
            <span>Estado <strong><?php echo htmlspecialchars_uni($estado_label); ?></strong></span>
            <?php if ($trip): ?>
              <span>Tripulación <strong><?php echo htmlspecialchars_uni($trip['nombre'] ?? '—'); ?></strong></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="barco-hero-actions">
          <?php if ($grado !== ''): ?>
            <a href="<?php echo $bburl; ?>/tramites.php" class="btn btn-hot btn-block btn-sm">Reparar (trámite 43)</a>
          <?php endif; ?>
          <a href="<?php echo $bburl; ?>/astillero.php" class="btn btn-ghost btn-block btn-sm">Gestionar en el Astillero</a>
          <?php if ($trip): ?>
            <a href="<?php echo $bburl; ?>/tripulacion.php" class="btn btn-ghost btn-block btn-sm">Tripulación</a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <div class="barco-dashboard-grid">
      <div class="barco-left-col">

        <section class="barco-casco-widget reveal">
          <div class="casco-lbl-row">
            <span class="casco-title">Integridad del casco</span>
            <span class="casco-val"><?php echo $casco_pv; ?> / <?php echo $casco_max; ?> PV</span>
          </div>
          <div class="casco-meter-bg">
            <div class="casco-meter-fill" style="width:<?php echo $casco_max > 0 ? max(0, min(100, round($casco_pv * 100 / $casco_max))) : 0; ?>%"></div>
          </div>
          <div class="casco-status-note">
            <?php
            if ($grado === '') {
                echo 'Casco en plena forma. Navega sin contratiempos.';
            } else {
                echo 'El casco muestra daño ' . $grado . '. Repáralo en el Astillero (trámite 43) antes de zarpar.';
            }
            ?>
          </div>
        </section>

        <section class="reveal">
          <div class="attr-stats-grid">
            <div class="attr-card">
              <span class="attr-lbl">Maniobra</span>
              <span class="attr-val"><?php echo $maniobra; ?></span>
              <span class="attr-sub">Base del tipo en nivel <?php echo htmlspecialchars_uni($nivel); ?></span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Cañones</span>
              <span class="attr-val"><?php echo $canones; ?></span>
              <span class="attr-sub">Batería de combate naval</span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Tripulantes</span>
              <span class="attr-val"><?php echo (int) $barco['espacio_max']; ?></span>
              <span class="attr-sub">Plazas de la nave</span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Ranuras</span>
              <span class="attr-val"><?php echo count($modulos); ?> / <?php echo $ranuras; ?></span>
              <span class="attr-sub">Módulos instalados</span>
            </div>
          </div>
        </section>

        <?php if ($tipo): ?>
        <section class="reveal">
          <div class="shead">
            <h2>Ficha de la nave</h2>
            <div class="rule"></div>
          </div>
          <div class="attr-stats-grid">
            <div class="attr-card">
              <span class="attr-lbl">Tipo</span>
              <span class="attr-val"><?php echo htmlspecialchars_uni($tipo['nombre']); ?></span>
              <span class="attr-sub">Precio base <?php echo number_format((int) ($tipo['precio'] ?? 0)); ?> ฿</span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Madera mínima</span>
              <span class="attr-val"><?php echo htmlspecialchars_uni($tipo['madera_minima'] ?? '—'); ?></span>
              <span class="attr-sub">Requisito de construcción (18.5)</span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Madera del casco</span>
              <span class="attr-val"><?php echo htmlspecialchars_uni($madera['nombre'] ?? '—'); ?></span>
              <span class="attr-sub"><?php echo $madera ? ucfirst(htmlspecialchars_uni(str_replace('_', ' ', $madera['rareza']))) : ''; ?></span>
            </div>
            <div class="attr-card">
              <span class="attr-lbl">Mitigador de travesía</span>
              <span class="attr-val"><?php echo (int) ($tipo['mitigador_irt'] ?? 0); ?></span>
              <span class="attr-sub">Influencia en el riesgo de ruta (17.4)</span>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <section class="reveal">
          <div class="shead">
            <h2>Módulos instalados</h2>
            <div class="rule"></div>
          </div>
          <?php if (empty($modulos)): ?>
            <div class="plate">
              <div class="plate-b">Sin módulos. Instala mejoras desde el Astillero (trámite 42).</div>
            </div>
          <?php else: ?>
            <div class="mejoras-grid">
              <?php foreach ($modulos as $m): ?>
                <div class="mejora-card">
                  <div class="mejora-h">
                    <span class="mejora-name"><?php echo htmlspecialchars_uni($m['nombre']); ?></span>
                    <span class="mejora-tag">Ranura <?php echo (int) ($m['ranura'] ?? 1); ?></span>
                  </div>
                  <div class="mejora-efecto"><?php echo htmlspecialchars_uni((string) json_encode(json_decode((string) ($m['efecto'] ?? '[]'), true), JSON_UNESCAPED_UNICODE)); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

      </div>

      <div class="barco-right-col">
        <section class="plate reveal">
          <div class="plate-h">Navegación y mundo</div>
          <div class="plate-b">
            <ul>
              <li>El barco define los mares que puedes surcar según su tipo y madera (18.5).</li>
              <li>Los viajes y travesías se solicitan por la ventanilla de Navegación (trámite 38).</li>
              <li>En combate naval, el casco absorbe el daño de cañón (18.6).</li>
            </ul>
          </div>
        </section>
      </div>
    </div>

  <?php endif; ?>

</div>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>
<script>
(function () {
  var io = new IntersectionObserver(function (es) {
    es.forEach(function (e) { if (e.isIntersecting) e.target.classList.add('in'); });
  }, { threshold: 0.08 });
  document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
})();
</script>
</body>
</html>
