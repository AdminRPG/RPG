<?php
/**
 * One Piece: Eternal · Ficha de personaje ("Placa forjada")
 * ----------------------------------------------
 * Muestra el expediente real de un personaje (mybb_ope_personajes), leyendo
 * los datos guardados por el wizard crear-personaje.php. Dirección visual
 * "One Piece: Eternal", coherente con personajes.php.
 *
 * Acceso:
 *   ficha.php?pid=N   → ficha del personaje N
 *   ficha.php         → ficha del personaje ACTIVO del usuario autenticado
 *
 * Visibilidad:
 *   - Los expedientes APROBADOS son públicos.
 *   - El dueño ve siempre los suyos (aunque estén en revisión/rechazados).
 *   - El staff ve cualquiera.
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'ficha.php');
require_once './global.php';
require_once MYBB_ROOT . 'inc/ope_rol/core/data.php';
require_once MYBB_ROOT . 'inc/ope_rol/core/system.php';

// Capacidad del inventario que se lleva "encima" (nº de slots de la mochila).
define('OPE_INV_CAP', 12);

$bburl    = htmlspecialchars_uni($mybb->settings['bburl']);
$bbname   = htmlspecialchars_uni($mybb->settings['bbname']);
$loggedin = (int) ($mybb->user['uid'] ?? 0) > 0;
$uid      = (int) ($mybb->user['uid'] ?? 0);
$username = htmlspecialchars_uni($mybb->user['username'] ?? '');

// Staff del PERSONAJE ACTIVO (el staff es por personaje). Un colaborador+ puede
// ver expedientes no aprobados; con un personaje sin rol activo, no.
$staff_arr   = $loggedin ? ope_rol_active_staff($uid) : array('rank' => 0);
$staff_level = (int) $staff_arr['rank'];

// Iniciales para el botón de usuario (navbar).
$display_name = (string) ($mybb->user['ope_display_name'] ?? ($mybb->user['username'] ?? ''));
$display_name_e = htmlspecialchars_uni($display_name);

// ── Resolver el personaje a mostrar ──
$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
if ($pid < 1 && $loggedin) {
    // Personaje activo del usuario (D6.3: fuente canónica ope_cuentas).
    if (function_exists('ope7_pj_activo')) {
        $act = ope7_pj_activo($uid);
        if ($act && $act['tabla'] === 'ope') {
            $pid = $act['id'];
        }
    }
}

// El expediente 7 Seas lo resuelve la fuente canónica (mybb_ope_personajes).
$pj = null;

// ── Control de acceso ──
$puede_ver = false;
if ($pj) {
    if ($pj['estado'] === 'eliminado') {
        $puede_ver = $loggedin && ($staff_level >= 1 || (int) $pj['uid'] === $uid);
    } elseif ($pj['estado'] === 'aprobado') {
        $puede_ver = true;
    } elseif ($loggedin && ((int) $pj['uid'] === $uid || $staff_level >= 1)) {
        $puede_ver = true;
    }
}
// Gestión permitida al DUEÑO del personaje (pj.uid == uid) o al STAFF. Gobierna
// tanto la pestaña de Gestión como la autorización de todos los POST. Antes se
// exigía que el personaje fuera el ACTIVO; ahora basta con ser su dueño (o staff)
// para poder gestionarlo aunque no lo tengas activado.
$puede_gestionar = $pj && $loggedin && ((int) $pj['uid'] === $uid || $staff_level >= 1);
// El personaje activo del visitante (para acciones que aún dependan de "activo").
$es_activo = $pj && $loggedin && (int) ($mybb->user['ope_active_pid'] ?? 0) === (int) $pj['pid'];

// ── Rama 7 Seas (F1.2): personajes del esquema mybb_ope_* ──
require_once MYBB_ROOT . 'inc/ope_rol/bootstrap.php';
$ope_pj = null;
$ope_pid = 0;
if ($pid > 0 && !$pj && $db->table_exists('ope_personajes')) {
    $oq = $db->simple_select('ope_personajes', 'id', "id = {$pid}", array('limit' => 1));
    if ($db->num_rows($oq)) {
        $ope_pid = $pid;
    }
} elseif ($pid < 1 && $loggedin) {
    $act = ope7_pj_activo($uid);
    if ($act && $act['tabla'] === 'ope') {
        $ope_pid = $act['id'];
        $pid = $ope_pid;
    }
}
if ($ope_pid > 0) {
    $ope_pj = ope7_pj_get($ope_pid);
    $puede_ver_ope = false;
    if ($ope_pj) {
        $est = (string) $ope_pj['estado'];
        if ($est === 'aprobado') {
            $puede_ver_ope = true;
        } elseif ($loggedin && ((int) $ope_pj['uid'] === $uid || $staff_level >= 1)) {
            $puede_ver_ope = true;
        }
    }
    if (!$puede_ver_ope) {
        $ope_pj = null;
        $pid = 0;
    }
}
// ── Rama 7 Seas: colocar reserva de puntos (7.3, F4.2) ──
$reserva_flash = '';
if ($ope_pj && $loggedin && ((int) $ope_pj['uid'] === $uid || $staff_level >= 1)
    && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'reserva') {
    $dist = array();
    foreach (array('fue', 'des', 'agi', 'res', 'per', 'inte', 'car', 'vol') as $atr) {
        $v = $mybb->get_input('res_' . $atr, MyBB::INPUT_INT);
        if ($v > 0) {
            $dist[$atr] = $v;
        }
    }
    $r = ope7_pj_colocar_reserva($ope_pid, $dist);
    $reserva_flash = (string) ($r['msg'] ?? 'Reserva no válida.');
    if (!empty($r['ok'])) {
        header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $ope_pid . '&r=1&fmsg=' . urlencode($reserva_flash));
        exit;
    }
}

// ── Rama 7 Seas: entrenar dominio (5.3, ligero automático) ──
$dom_flash = '';
if ($ope_pj && $loggedin && ((int) $ope_pj['uid'] === $uid || $staff_level >= 1)
    && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'dominio') {
    $sel = explode(':', (string) $mybb->get_input('dom_dominio_id'));
    $dom_did = (int) ($sel[0] ?? 0);
    $dom_nivel = (int) ($sel[1] ?? 0);
    $r = ope7_tramite_crear($uid, $ope_pid, 4, '', array('dominio_id' => $dom_did, 'nivel' => $dom_nivel));
    $dom_flash = (string) ($r['msg'] ?? 'Entrenamiento de dominio no válido.');
    if (!empty($r['ok'])) {
        header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $ope_pid . '&r=1&dmsg=' . urlencode($dom_flash));
        exit;
    }
}

if ($ope_pj) {
    header('Content-Type: text/html; charset=utf-8');
    $ctx = array(
        'uid' => $uid,
        'es_activo' => (int) $ope_pj['uid'] === $uid,
        'puede_gestionar' => $loggedin && ((int) $ope_pj['uid'] === $uid || $staff_level >= 1),
        'es_staff' => function_exists('ope7_es_staff') && ope7_es_staff($uid),
        'bburl' => $bburl,
        // Flash de la colocación de reserva (7.3) y del entrenamiento de dominio (5.3).
        'reserva_flash' => $reserva_flash !== '' ? $reserva_flash : trim((string) $mybb->get_input('fmsg')),
        'dom_flash' => $dom_flash !== '' ? $dom_flash : trim((string) $mybb->get_input('dmsg')),
    );
    ?><!DOCTYPE html>
    <html lang="es">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars_uni($mybb->settings['bbname']); ?> · <?php echo htmlspecialchars_uni($ope_pj['nombre']); ?></title>
    <?php echo ope_rol_head_base(); ?>
    </head>
    <body class="ope-pg-ficha">
    <?php echo ope_rol_navbar_html(); ?>
    <div class="breadcrumb"><div class="breadcrumb-in">
      <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
      <a href="<?php echo $bburl; ?>/personajes.php">Personajes</a><span class="sep">›</span><b><?php echo htmlspecialchars_uni($ope_pj['nombre']); ?></b>
    </div></div>
    <div class="wrap">
    <?php echo ope7_ficha_html($ope_pj, $ctx); ?>
    </div>
    <?php include __DIR__ . '/inc/footer_custom.php'; ?>
    <script>
    (function () {
      var io = new IntersectionObserver(function (es) {
        es.forEach(function (en) { if (en.isIntersecting) en.target.classList.add('revealed'); });
      }, { threshold: 0.12 });
      document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
    })();
    // Reserva de puntos (7.3): steppers + suma live contra la reserva.
    (function () {
      var suma = document.getElementById('f7-reserva-suma');
      var inputs = document.querySelectorAll('.f7-step-input');
      if (!suma || !inputs.length) { return; }
      var total = 0, reserva = 0;
      var meta = document.querySelector('.f7-reserva-total');
      var m = meta && meta.textContent.match(/de (\d+)/);
      if (m) { reserva = parseInt(m[1], 10); }
      function actualizar() {
        total = 0;
        inputs.forEach(function (inp) { total += parseInt(inp.value || '0', 10) || 0; });
        suma.textContent = total;
        if (meta) {
          meta.classList.toggle('f7-reserva-over', total > reserva);
        }
        var btn = document.querySelector('.f7-reserva-actions .btn-hot');
        if (btn) { btn.disabled = total < 1 || total > reserva; }
      }
      function clamp(inp) {
        var v = parseInt(inp.value || '0', 10) || 0;
        var max = parseInt(inp.getAttribute('data-max') || '0', 10);
        inp.value = Math.max(0, Math.min(max, v));
      }
      inputs.forEach(function (inp) {
        var row = inp.closest('.f7-reserva-row');
        inp.addEventListener('change', function () { clamp(inp); actualizar(); });
        var menos = row && row.querySelector('.f7-step-menos');
        var mas = row && row.querySelector('.f7-step-mas');
        if (menos) { menos.addEventListener('click', function () { inp.value = Math.max(0, (parseInt(inp.value||'0',10)||0) - 1); actualizar(); }); }
        if (mas) { mas.addEventListener('click', function () { clamp(inp); inp.value = Math.min(parseInt(inp.getAttribute('data-max')||'0',10), (parseInt(inp.value||'0',10)||0) + 1); actualizar(); }); }
      });
      actualizar();
    })();
    </script>
    </body>
    </html>
    <?php
    exit;
}

// ── Gestión (propietario): guardar Avatar / Icono / Firma ──

// ── Sin personaje 7 Seas válido: estado vacío honesto ──
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars_uni($bbname); ?> · Ficha</title>
<?php echo ope_rol_head_base(); ?>
</head>
<body class="ope-pg-ficha">
<?php echo ope_rol_navbar_html(); ?>
<div class="breadcrumb"><div class="breadcrumb-in">
  <a href="<?php echo $bburl; ?>/index.php">Inicio</a><span class="sep">›</span>
  <a href="<?php echo $bburl; ?>/personajes.php">Personajes</a><span class="sep">›</span><b>Ficha</b>
</div></div>
<div class="wrap">
  <div class="pj-empty">
    <div class="big">Expediente no encontrado</div>
    <p>No hay ningún personaje con ese identificador. Puede que aún no lo hayas creado o que el enlace sea incorrecto.</p>
    <div class="acts">
      <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a>
      <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/inc/footer_custom.php'; ?>
</body>
</html>
