<?php
/**
 * One Piece: Eternal · Ficha de personaje ("Placa forjada")
 * ----------------------------------------------
 * Muestra el expediente real de un personaje (mybb_rol_personajes), leyendo
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
require_once MYBB_ROOT . 'inc/ope_rol_data.php';
require_once MYBB_ROOT . 'inc/ope_rol_system.php';

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
    // Personaje activo del usuario.
    if ($db->table_exists('rol_cuentas')) {
        $cq = $db->simple_select('rol_cuentas', 'personaje_activo', "uid = {$uid}", array('limit' => 1));
        if ($db->num_rows($cq)) {
            $pid = (int) $db->fetch_field($cq, 'personaje_activo');
        }
    }
}

$pj = null;
if ($pid > 0 && $db->table_exists('rol_personajes')) {
    $q = $db->simple_select('rol_personajes', '*', "pid = {$pid}", array('limit' => 1));
    if ($db->num_rows($q)) {
        $pj = $db->fetch_array($q);
    }
}

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
$gestion_ok = ($mybb->get_input('g') === '1');
$gestion_flash = trim((string) $mybb->get_input('fmsg'));
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'perfil') {

    $valid_url = static function ($u) {
        if ($u === '') return true;
        $parsed = parse_url($u);
        if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['scheme'])) return false;
        $scheme = strtolower($parsed['scheme']);
        $host   = strtolower($parsed['host']);
        if (!in_array($scheme, array('http', 'https'), true)) return false;
        if ($host === 'localhost' || $host === '0.0.0.0') return false;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($host);
            if ($ip === false) return false;
            if ($ip === ip2long('127.0.0.1')) return false;
            $long = sprintf('%u', $ip);
            if ($long >= ip2long('10.0.0.0') && $long <= ip2long('10.255.255.255')) return false;
            if ($long >= ip2long('172.16.0.0') && $long <= ip2long('172.31.255.255')) return false;
            if ($long >= ip2long('192.168.0.0') && $long <= ip2long('192.168.255.255')) return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = inet_pton($host);
            if ($ipv6 === false) return false;
            if ($ipv6 === inet_pton('::1')) return false;
        }
        return true;
    };

    $n_retrato = trim((string) $mybb->get_input('retrato'));
    $n_avatar = trim((string) $mybb->get_input('avatar'));
    $n_icono  = trim((string) $mybb->get_input('icono'));
    $n_firma  = (string) $mybb->get_input('firma');

    $datos_g = json_decode((string) $pj['datos'], true);
    if (!is_array($datos_g)) $datos_g = array();

    if (!$valid_url($n_retrato)) $n_retrato = (string) ($datos_g['retrato'] ?? '');
    if (!$valid_url($n_avatar)) $n_avatar = (string) $pj['avatar'];
    if (!$valid_url($n_icono))  $n_icono  = (string) ($pj['icono'] ?? '');
    $n_firma = function_exists('mb_substr') ? mb_substr($n_firma, 0, 3000) : substr($n_firma, 0, 3000);

    $datos_g['retrato'] = $n_retrato;

    $db->update_query('rol_personajes', array(
        'avatar'        => $db->escape_string($n_avatar),
        'icono'         => $db->escape_string($n_icono),
        'firma'         => $db->escape_string($n_firma),
        'datos'         => $db->escape_string(json_encode($datos_g, JSON_UNESCAPED_UNICODE)),
        'lastedit'      => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1');
    exit;
}

// ── Gestión (propietario): guardar descripciones de cronología ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'cronologia'
    && $db->table_exists('ope_cronologia')) {

    $pid_c   = (int) $pj['pid'];
    $descs   = $mybb->get_input('descripcion', MyBB::INPUT_ARRAY);
    if (is_array($descs)) {
        // TIDs válidos: temas donde el personaje realmente participó.
        $valid = array();
        $vq = $db->query("SELECT DISTINCT tid FROM " . TABLE_PREFIX . "posts
                          WHERE ope_pid = {$pid_c} AND visible = 1");
        while ($vr = $db->fetch_array($vq)) { $valid[(int) $vr['tid']] = true; }

        foreach ($descs as $tid_k => $txt) {
            $tid_k = (int) $tid_k;
            if ($tid_k < 1 || empty($valid[$tid_k])) continue;
            $txt = trim((string) $txt);
            $txt = function_exists('mb_substr') ? mb_substr($txt, 0, 2000) : substr($txt, 0, 2000);

            $ex = $db->simple_select('ope_cronologia', 'tid',
                "pid = {$pid_c} AND tid = {$tid_k}", array('limit' => 1));
            if ($txt === '') {
                if ($db->num_rows($ex)) {
                    $db->delete_query('ope_cronologia', "pid = {$pid_c} AND tid = {$tid_k}");
                }
                continue;
            }
            $row = array(
                'descripcion' => $db->escape_string($txt),
                'dateline'    => TIME_NOW,
            );
            if ($db->num_rows($ex)) {
                $db->update_query('ope_cronologia', $row, "pid = {$pid_c} AND tid = {$tid_k}");
            } else {
                $row['pid'] = $pid_c;
                $row['tid'] = $tid_k;
                $db->insert_query('ope_cronologia', $row);
            }
        }
    }

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#cronologia');
    exit;
}

// ── Gestión (propietario): mapa de relaciones (añadir/editar/borrar/posición) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('rel_add', 'rel_edit', 'rel_del', 'rel_pos'), true)
    && $db->table_exists('ope_relaciones')) {

    $pid_r    = (int) $pj['pid'];
    $gaccion  = $mybb->get_input('gaccion');
    $tipos_ok = array_keys(ope_rel_tipos());

    if ($gaccion === 'rel_add') {
        $destino = $mybb->get_input('destino_pid', MyBB::INPUT_INT);
        $etq     = trim((string) $mybb->get_input('etiqueta'));
        $tipo    = (string) $mybb->get_input('tipo');
        $desc    = trim((string) $mybb->get_input('descripcion'));
        if (!in_array($tipo, $tipos_ok, true)) $tipo = 'otro';
        $etq  = function_exists('mb_substr') ? mb_substr($etq, 0, 120) : substr($etq, 0, 120);
        $desc = function_exists('mb_substr') ? mb_substr($desc, 0, 1500) : substr($desc, 0, 1500);

        // Destino: personaje aprobado, distinto de sí mismo, sin duplicar.
        $ok = false;
        if ($destino > 0 && $destino !== $pid_r) {
            $dq = $db->simple_select('rol_personajes', 'pid',
                "pid = {$destino} AND estado = 'aprobado'", array('limit' => 1));
            if ($db->num_rows($dq)) {
                $ex = $db->simple_select('ope_relaciones', 'rid',
                    "pid = {$pid_r} AND destino_pid = {$destino}", array('limit' => 1));
                if (!$db->num_rows($ex)) $ok = true;
            }
        }
        if ($ok) {
            $db->insert_query('ope_relaciones', array(
                'pid'         => $pid_r,
                'destino_pid' => $destino,
                'etiqueta'    => $db->escape_string($etq),
                'tipo'        => $db->escape_string($tipo),
                'descripcion' => $db->escape_string($desc),
                'px'          => 0,
                'py'          => 0,
                'dateline'    => TIME_NOW,
            ));
        }
    } elseif ($gaccion === 'rel_edit') {
        $rid  = $mybb->get_input('rid', MyBB::INPUT_INT);
        $etq  = trim((string) $mybb->get_input('etiqueta'));
        $tipo = (string) $mybb->get_input('tipo');
        $desc = trim((string) $mybb->get_input('descripcion'));
        if (!in_array($tipo, $tipos_ok, true)) $tipo = 'otro';
        $etq  = function_exists('mb_substr') ? mb_substr($etq, 0, 120) : substr($etq, 0, 120);
        $desc = function_exists('mb_substr') ? mb_substr($desc, 0, 1500) : substr($desc, 0, 1500);
        if ($rid > 0) {
            $db->update_query('ope_relaciones', array(
                'etiqueta'    => $db->escape_string($etq),
                'tipo'        => $db->escape_string($tipo),
                'descripcion' => $db->escape_string($desc),
            ), "rid = {$rid} AND pid = {$pid_r}");
        }
    } elseif ($gaccion === 'rel_del') {
        $rid = $mybb->get_input('rid', MyBB::INPUT_INT);
        if ($rid > 0) {
            $db->delete_query('ope_relaciones', "rid = {$rid} AND pid = {$pid_r}");
        }
    } elseif ($gaccion === 'rel_pos') {
        $pxs = $mybb->get_input('px', MyBB::INPUT_ARRAY);
        $pys = $mybb->get_input('py', MyBB::INPUT_ARRAY);
        if (is_array($pxs)) {
            foreach ($pxs as $rid_k => $vx) {
                $rid_k = (int) $rid_k;
                if ($rid_k < 1) continue;
                $vx = (int) $vx;
                $vy = isset($pys[$rid_k]) ? (int) $pys[$rid_k] : 0;
                // Clamp al lienzo (viewBox 0..1000 x 0..640).
                if ($vx < 0) $vx = 0; if ($vx > 1000) $vx = 1000;
                if ($vy < 0) $vy = 0; if ($vy > 640)  $vy = 640;
                $db->update_query('ope_relaciones',
                    array('px' => $vx, 'py' => $vy),
                    "rid = {$rid_k} AND pid = {$pid_r}");
            }
        }
    }

    // rel_pos vuelve al mapa; el resto reabre el modal en la pestaña de relaciones.
    if ($gaccion === 'rel_pos') {
        header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_r . '#relaciones-map');
    } else {
        header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_r . '&g=1#relaciones');
    }
    exit;
}

// ── Gestión (propietario): plantillas de post (crear/editar/borrar) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('tpl_add', 'tpl_edit', 'tpl_del'), true)
    && $db->table_exists('ope_post_templates')) {

    $pid_t   = (int) $pj['pid'];
    $gaccion = $mybb->get_input('gaccion');

    if ($gaccion === 'tpl_del') {
        $tpl_id = $mybb->get_input('tpl_id', MyBB::INPUT_INT);
        if ($tpl_id > 0) {
            $db->delete_query('ope_post_templates', "tpl_id = {$tpl_id} AND pid = {$pid_t}");
        }
    } else {
        $nombre = trim((string) $mybb->get_input('nombre'));
        $cuerpo = (string) $mybb->get_input('cuerpo');
        $nombre = function_exists('mb_substr') ? mb_substr($nombre, 0, 120) : substr($nombre, 0, 120);
        $cuerpo = function_exists('mb_substr') ? mb_substr($cuerpo, 0, 20000) : substr($cuerpo, 0, 20000);
        if ($nombre === '') $nombre = 'Plantilla';

        if ($gaccion === 'tpl_add') {
            // disporder = último + 1
            $ord = 0;
            $oq = $db->simple_select('ope_post_templates', 'MAX(disporder) AS mx', "pid = {$pid_t}");
            if ($db->num_rows($oq)) $ord = (int) $db->fetch_field($oq, 'mx') + 1;
            $db->insert_query('ope_post_templates', array(
                'pid'      => $pid_t,
                'nombre'   => $db->escape_string($nombre),
                'cuerpo'   => $db->escape_string($cuerpo),
                'disporder'=> $ord,
                'dateline' => TIME_NOW,
            ));
        } else { // tpl_edit
            $tpl_id = $mybb->get_input('tpl_id', MyBB::INPUT_INT);
            if ($tpl_id > 0) {
                $db->update_query('ope_post_templates', array(
                    'nombre' => $db->escape_string($nombre),
                    'cuerpo' => $db->escape_string($cuerpo),
                ), "tpl_id = {$tpl_id} AND pid = {$pid_t}");
            }
        }
    }

    header('Location: ' . $bburl . '/ficha.php?pid=' . $pid_t . '&g=1#templates');
    exit;
}

// ── Gestión (propietario): equipo (encima / almacén) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('equip_move'), true)) {

    $inv = json_decode((string) $pj['inventario'], true);
    if (!is_array($inv)) $inv = array();
    if (!isset($inv['encima']) || !is_array($inv['encima']))   $inv['encima']  = array();
    if (!isset($inv['almacen']) || !is_array($inv['almacen'])) $inv['almacen'] = array();

    $gaccion = $mybb->get_input('gaccion');
    $valid_loc = static function ($l) { return $l === 'encima' || $l === 'almacen'; };
    // Suma de slots ocupados en un contenedor (respeta el tamaño de cada objeto).
    $inv_used = static function (array $list): int {
        $u = 0;
        foreach ($list as $it) {
            $s = is_array($it) ? (int) ($it['size'] ?? 1) : 1;
            $u += ($s < 1 ? 1 : $s);
        }
        return $u;
    };

    if ($gaccion === 'equip_move') {
        $from = (string) $mybb->get_input('from');
        $idx  = $mybb->get_input('idx', MyBB::INPUT_INT);
        if ($valid_loc($from) && isset($inv[$from][$idx])) {
            $to = $from === 'encima' ? 'almacen' : 'encima';
            $item = $inv[$from][$idx];
            $isz  = is_array($item) ? (int) ($item['size'] ?? 1) : 1;
            if ($isz < 1) $isz = 1;
            // Al pasar al inventario "encima" hay que respetar la capacidad de slots.
            if ($to === 'encima' && $inv_used($inv['encima']) + $isz > OPE_INV_CAP) {
                // No cabe: no se mueve (se queda en el almacén).
            } else {
                array_splice($inv[$from], $idx, 1);
                $inv[$to][] = $item;
            }
        }
    }

    // Reindexa para evitar huecos en los arrays.
    $inv['encima']  = array_values($inv['encima']);
    $inv['almacen'] = array_values($inv['almacen']);

    $db->update_query('rol_personajes', array(
        'inventario' => $db->escape_string(json_encode($inv, JSON_UNESCAPED_UNICODE)),
        'lastedit'   => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#equipo');
    exit;
}

// ── Gestión: comprar +1 stat con PP (STATS.md) ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && $mybb->get_input('gaccion') === 'buy_stat') {

    $fmsg = '';
    if (function_exists('ope_pp_buy_stat')) {
        $res = ope_pp_buy_stat((int) $pj['pid'], (string) $mybb->get_input('stat'));
        $fmsg = (string) ($res['msg'] ?? '');
    }
    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1&fmsg=' . rawurlencode($fmsg) . '#g-atributos');
    exit;
}

// ── Gestión: guardar elecciones de Clase / Arquetipo / Oficio ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('save_voc_eleccion', 'save_voc_arquetipo', 'save_voc_eleccion_oficio'), true)) {

    $res = array('msg' => '');
    if ($mybb->get_input('gaccion') === 'save_voc_eleccion' && function_exists('ope_rol_vocacion_guardar_eleccion')) {
        $g_nivel  = (int) $mybb->get_input('nivel');
        $g_opcion = (string) $mybb->get_input('opcion');
        $res = ope_rol_vocacion_guardar_eleccion((int) $pj['pid'], $g_nivel, $g_opcion);
    } elseif ($mybb->get_input('gaccion') === 'save_voc_arquetipo' && function_exists('ope_rol_vocacion_guardar_arquetipo')) {
        $g_segunda = (string) $mybb->get_input('segunda_clase');
        $res = ope_rol_vocacion_guardar_arquetipo((int) $pj['pid'], $g_segunda);
    } elseif ($mybb->get_input('gaccion') === 'save_voc_eleccion_oficio' && function_exists('ope_rol_vocacion_guardar_eleccion_oficio')) {
        $g_oficio = (string) $mybb->get_input('oficio');
        $g_nivel  = (int) $mybb->get_input('nivel');
        $g_opcion = (string) $mybb->get_input('opcion');
        $res = ope_rol_vocacion_guardar_eleccion_oficio((int) $pj['pid'], $g_oficio, $g_nivel, $g_opcion);
    }
    $fmsg = (string) ($res['msg'] ?? '');
    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1&fmsg=' . rawurlencode($fmsg) . '#g-talentos');
    exit;
}

// ── Gestión: subir nivel de Haki / Fruta ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('buy_haki', 'buy_fruta'), true)) {

    $fmsg = '';
    if ($mybb->get_input('gaccion') === 'buy_haki' && function_exists('ope_haki_buy_level')) {
        $res = ope_haki_buy_level((int) $pj['pid'], (string) $mybb->get_input('haki_tipo'));
        $fmsg = (string) ($res['msg'] ?? '');
    } elseif ($mybb->get_input('gaccion') === 'buy_fruta' && function_exists('ope_fruta_buy_level')) {
        $res = ope_fruta_buy_level((int) $pj['pid']);
        $fmsg = (string) ($res['msg'] ?? '');
    }
    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1&fmsg=' . rawurlencode($fmsg) . '#g-haki');
    exit;
}

// ── Gestión: inventario — añadir / eliminar objeto ──
if ($puede_gestionar && $mybb->request_method === 'post'
    && verify_post_check($mybb->get_input('my_post_key'), true)
    && in_array($mybb->get_input('gaccion'), array('equip_add', 'equip_del'), true)) {

    $inv = json_decode((string) $pj['inventario'], true);
    if (!is_array($inv)) $inv = array();
    if (!isset($inv['encima']) || !is_array($inv['encima']))   $inv['encima']  = array();
    if (!isset($inv['almacen']) || !is_array($inv['almacen'])) $inv['almacen'] = array();

    $gaccion = $mybb->get_input('gaccion');
    $valid_loc = static function ($l) { return $l === 'encima' || $l === 'almacen'; };

    if ($gaccion === 'equip_add') {
        $loc  = (string) $mybb->get_input('loc');
        $name = trim((string) $mybb->get_input('nombre'));
        $desc = trim((string) $mybb->get_input('desc'));
        $size = $mybb->get_input('size', MyBB::INPUT_INT);
        if ($size < 1) $size = 1;
        if ($size > OPE_INV_CAP) $size = OPE_INV_CAP;
        if (!$valid_loc($loc)) $loc = 'almacen';
        if ($name !== '') {
            $inv[$loc][] = array('n' => $name, 'd' => $desc, 'size' => $size);
        }
    } elseif ($gaccion === 'equip_del') {
        $loc = (string) $mybb->get_input('from');
        $idx = $mybb->get_input('idx', MyBB::INPUT_INT);
        if ($valid_loc($loc) && isset($inv[$loc][$idx])) {
            array_splice($inv[$loc], $idx, 1);
        }
    }

    $inv['encima']  = array_values($inv['encima']);
    $inv['almacen'] = array_values($inv['almacen']);

    $db->update_query('rol_personajes', array(
        'inventario' => $db->escape_string(json_encode($inv, JSON_UNESCAPED_UNICODE)),
        'lastedit'   => TIME_NOW,
    ), 'pid = ' . (int) $pj['pid']);

    header('Location: ' . $bburl . '/ficha.php?pid=' . (int) $pj['pid'] . '&g=1#g-equipo');
    exit;
}

// ── Datos de rol ──
$STAT_GROUPS = ope_rol_stats();
$RAZAS       = ope_rol_razas();
$FACCIONES   = ope_rol_facciones();
$ARMAS       = ope_rol_armas();
$PACKS       = ope_rol_packs_equipo();

/** Tipos de relación válidos (slug => etiqueta visible). */
function ope_rel_tipos(): array
{
    return array(
        'aliado'      => 'Aliado',
        'rival'       => 'Rival',
        'familia'     => 'Familia',
        'romance'     => 'Romance',
        'mentor'      => 'Mentor',
        'tripulacion' => 'Tripulación',
        'enemigo'     => 'Enemigo',
        'otro'        => 'Otro',
    );
}

/** Convierte texto con \n\n en párrafos <p> HTML, escapando y respetando saltos simples. */
function ope_nl2p(string $text): string
{
    $text = trim($text);
    if ($text === '') return '';
    $paras = preg_split('/\n\s*\n/', $text);
    $out = '';
    foreach ($paras as $p) {
        $p = trim($p);
        if ($p !== '') {
            $out .= '<p>' . nl2br(htmlspecialchars_uni($p)) . '</p>';
        }
    }
    return $out;
}

// Decodifica los JSON del personaje.
$datos      = $pj ? (json_decode((string) $pj['datos'], true) ?: array()) : array();
$inventario = $pj ? (json_decode((string) $pj['inventario'], true) ?: array()) : array();
$economia   = $pj ? (json_decode((string) $pj['economia'], true) ?: array()) : array();
$bio        = $pj ? (json_decode((string) $pj['bio'], true) ?: array()) : array();

// Color por facción: slug canónico para teñir el expediente (fuente: plugin ope_rol).
$fac_slug  = $pj ? ope_rol_faccion_slug($datos['faccion'] ?? '') : '';
$fac_class = $fac_slug !== '' ? ' fac-' . $fac_slug : '';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $bbname; ?> &middot; <?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></title>
<?php echo ope_rol_head_base(); ?>
<!-- estilos en docs/themes/ope.css (scope: ope-pg-ficha) -->
</head>
<body class="ope-pg-ficha<?php echo $fac_class; ?>" data-fac="<?php echo htmlspecialchars_uni($fac_slug); ?>">

<?php echo ope_rol_navbar_html(); ?>

<div class="breadcrumb">
  <div class="breadcrumb-in">
    <a href="<?php echo $bburl; ?>/index.php">Inicio</a>
    <span class="sep">&#8250;</span>
    <a href="<?php echo $bburl; ?>/personajes.php">Personaje</a>
    <span class="sep">&#8250;</span>
    <b><?php echo $pj ? htmlspecialchars_uni($pj['nombre']) : 'Ficha'; ?></b>
  </div>
</div>

<?php if (!$pj || !$puede_ver): ?>
<div class="wrap">
  <div class="pj-empty">
    <div class="big"><?php echo $pj ? 'Expediente no disponible' : 'Expediente no encontrado'; ?></div>
    <p>
<?php if (!$pj): ?>
      No hay ningún personaje con ese identificador. Puede que aún no lo hayas creado o que el enlace sea incorrecto.
<?php else: ?>
      Este expediente está en revisión o no es público. Solo su dueño y el staff pueden consultarlo.
<?php endif; ?>
    </p>
    <div class="acts">
      <a href="<?php echo $bburl; ?>/personajes.php" class="btn btn-hot">Mis personajes</a>
      <a href="<?php echo $bburl; ?>/index.php" class="btn btn-ghost">Volver al inicio</a>
    </div>
  </div>
</div>
<?php else:
    // ── Datos derivados para el render ──
    $nombre_e   = htmlspecialchars_uni($pj['nombre']);
    $rango      = (string) $pj['rango'];
    $stats_ganados_early = (int) ($pj['stats_ganados'] ?? ($datos['stats_ganados'] ?? 0));
    $nivel      = function_exists('ope_rol_nivel_from_stats_comprados')
        ? (int) ope_rol_nivel_from_stats_comprados($stats_ganados_early)
        : (int) $pj['nivel'];
    $rango_e    = htmlspecialchars_uni(function_exists('ope_rol_nivel_label')
        ? ope_rol_nivel_label($nivel)
        : $rango);
    $avatar     = trim((string) $pj['avatar']);
    $retrato    = trim((string) ($datos['retrato'] ?? ''));
    $av_initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($pj['nombre'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($pj['nombre'], 0, 1));

    $raza1_key = $datos['raza_principal'] ?? ($datos['raza'] ?? '');
    $raza2_key = $datos['raza_secundaria'] ?? '';
    $hibrido   = !empty($datos['hibrido']);
    $raza1_lbl = isset($RAZAS[$raza1_key]) ? $RAZAS[$raza1_key]['nombre'] : ucfirst((string) $raza1_key);
    $raza2_lbl = ($raza2_key && isset($RAZAS[$raza2_key])) ? $RAZAS[$raza2_key]['nombre'] : '';
    $raza_full = $hibrido && $raza2_lbl !== '' ? ($raza1_lbl . ' / ' . $raza2_lbl) : $raza1_lbl;

    $faccion_key = $datos['faccion'] ?? '';
    $faccion_lbl = isset($FACCIONES[$faccion_key]) ? $FACCIONES[$faccion_key]['nombre'] : ucfirst((string) $faccion_key);

    // Campos de One Piece: Eternal
    $rango_faccion = trim((string) ($pj['rango_faccion'] ?? ''));
    $from_fisico   = trim((string) ($bio['pb'] ?? $pj['from_fisico'] ?? $datos['pb'] ?? ''));
    $desc_fisica   = trim((string) ($bio['desc_fisica'] ?? $pj['desc_fisica'] ?? ''));
    $personalidad  = trim((string) ($bio['desc_psicologica'] ?? $pj['personalidad'] ?? ''));
    $notas         = trim((string) ($bio['notas'] ?? ''));
    $historia      = trim((string) ($bio['historia'] ?? $bio['pasado'] ?? ''));

    // Estado del personaje resumido a la dupla Aprobado / Pendiente para el tag.
    $es_aprobado   = ((string) $pj['estado'] === 'aprobado');
    $estado_tag    = $es_aprobado ? 'Aprobado' : 'Pendiente';

    // Rol en el foro (staff): solo se muestra si el personaje es staff.
    $staff_rol     = (string) ($pj['staff_rol'] ?? '');
    $staff_rol_lbl = function_exists('ope_rol_staff_label') ? ope_rol_staff_label($staff_rol) : '';

    $edad   = trim((string) ($bio['edad'] ?? $datos['edad'] ?? ''));
    $genero = trim((string) ($bio['genero'] ?? $datos['genero'] ?? ''));
    $apodo  = trim((string) ($bio['apodo'] ?? $datos['apodo'] ?? ''));

    // Lectura unificada de stats: prioriza la columna stats_json (fuente de
    // verdad OPE) y cae a datos.stats_efectivas para fichas legacy.
    $stats_json_d = json_decode((string) ($pj['stats_json'] ?? ''), true);
    $stats_ef = (is_array($stats_json_d) && !empty($stats_json_d))
        ? $stats_json_d
        : (is_array($datos['stats_efectivas'] ?? null) ? $datos['stats_efectivas'] : array());

    // Pack de Equipo Inicial (INI-01, Paso 6). Fichas viejas (previas a este
    // sistema) pueden seguir teniendo 'arma'/'objeto_personal' sueltos: se
    // muestran igualmente como texto libre para no perder esa información.
    $pack_key    = $inventario['pack_equipo'] ?? '';
    $pack_def    = isset($PACKS[$pack_key]) ? $PACKS[$pack_key] : null;
    $arma_legacy_key = trim((string) ($inventario['arma'] ?? ''));
    $arma_legacy = isset($ARMAS[$arma_legacy_key]) ? $ARMAS[$arma_legacy_key]['nombre'] : $arma_legacy_key;
    $obj_legacy  = trim((string) ($inventario['objeto_personal'] ?? ''));
    $berries     = (int) ($economia['rupies'] ?? $economia['berries'] ?? 0);

    $pp_disponible = 0;
    if ($puede_gestionar && function_exists('ope_pp_saldo')) {
        $pp_row = ope_pp_saldo((int) $pj['pid']);
        $pp_disponible = (int) ($pp_row['pp_disponible'] ?? 0);
    }
    $pv_max = (int) ($pj['pv_max'] ?? 0);
    $en_max = (int) ($pj['en_max'] ?? 0);
    $pa_turno = (int) ($pj['pa_por_turno'] ?? 0);
    $need_recalc = ((int) ($pj['nivel'] ?? 0) !== $nivel) || $pv_max < 1;
    if ($need_recalc && function_exists('ope_combat_recalc')) {
        $vit = ope_combat_recalc((int) $pj['pid']);
        if ($vit) {
            $pv_max = (int) $vit['pv_max'];
            $en_max = (int) $vit['en_max'];
            $pa_turno = (int) $vit['pa_por_turno'];
            $rango = (string) ($vit['rango'] ?? $rango);
            $rango_e = htmlspecialchars_uni(function_exists('ope_rol_nivel_label') ? ope_rol_nivel_label($nivel) : $rango);
        }
    }

    $fisicas = function_exists('ope_combat_calc_fisicas')
        ? ope_combat_calc_fisicas($stats_ef)
        : array(
            'movimiento' => 5, 'esprint' => 5, 'carrera_turno' => 10,
            'salto_v' => 1, 'salto_h' => 2, 'caida_segura' => 3,
            'carga_kg' => 30, 'levantamiento_kg' => 60, 'mitigacion_fis' => 1,
        );

    // Inventario libre: objetos "encima" y en "almacén" (defaults robustos).
    $norm_items = static function ($list) {
        $out = array();
        if (is_array($list)) {
            foreach ($list as $it) {
                if (is_array($it)) {
                    $n = trim((string) ($it['n'] ?? ''));
                    $d = trim((string) ($it['d'] ?? ''));
                    $s = (int) ($it['size'] ?? 1);
                } else {
                    $n = trim((string) $it);
                    $d = '';
                    $s = 1;
                }
                if ($s < 1) $s = 1; if ($s > OPE_INV_CAP) $s = OPE_INV_CAP;
                if ($n !== '') $out[] = array('n' => $n, 'd' => $d, 'size' => $s);
            }
        }
        return $out;
    };
    $inv_encima  = $norm_items($inventario['encima'] ?? null);
    $inv_almacen = $norm_items($inventario['almacen'] ?? null);

    // Medias por pilar (Cuerpo/Mente/Espíritu) para la pestaña Crisol.
    $group_calc = array();
    foreach ($STAT_GROUPS as $gkey => $grupo) {
        $vals = array();
        foreach ($grupo['stats'] as $ab => $nm) {
            $vals[] = (int) ($stats_ef[$ab] ?? 5);
        }
        $group_calc[$gkey] = array(
            'avg' => count($vals) ? array_sum($vals) / count($vals) : 1,
        );
    }

    // Pasivas raciales: la primaria de la raza principal siempre se aplica;
    // Factor Linaje: rasgos comprados con PL (ya no hay pasivas raciales gratis).
    $fl_seleccion = is_array($datos['factor_linaje'] ?? null)
        ? $datos['factor_linaje']
        : (is_array($datos['virtudes_defectos'] ?? null) ? $datos['virtudes_defectos'] : array());
    $rasgos_raciales_pj = array();
    $rasgos_generales_pj = array();
    $pl_total_ficha = 0;
    foreach ($fl_seleccion as $id => $it) {
        $valor = (int) ($it['valor'] ?? 0);
        $pl_total_ficha += $valor;
        $row = array(
            'tipo'   => $valor < 0 ? 'x' : 'v',
            'nombre' => $it['nombre'] ?? $id,
            'spec'   => trim((string) ($it['spec'] ?? '')),
            'desc'   => '',
            'badge'  => ($valor > 0 ? '+' : '') . $valor . ' PL',
            'kind'   => (string) ($it['tipo'] ?? ''),
        );
        $kind = $row['kind'];
        if (in_array($kind, array('rasgo_racial', 'rasgo_puro', 'dote_innata'), true)) {
            $rasgos_raciales_pj[] = $row;
        } else {
            $rasgos_generales_pj[] = $row;
        }
    }
    // Alias legacy usado más abajo en la UI de rasgos.
    $rasgos = array_merge($rasgos_raciales_pj, $rasgos_generales_pj);
    $pasivas = $rasgos_raciales_pj; // la UI de "pasivas" ahora lista raciales comprados

    // Deck de cartas de técnica (INI-03) del personaje.
    $deck_tecnicas = function_exists('ope_rol_char_tecnicas') ? ope_rol_char_tecnicas((int) $pj['pid']) : array();

    // Acompañantes NPC secundarios (máx. 2).
    $acompanantes = function_exists('ope_rol_char_acompanantes') ? ope_rol_char_acompanantes((int) $pj['pid']) : array();

    // Cronología: solo eventos reales (forjado + última edición si difiere).
    $timeline = array();
    $timeline[] = array('t' => 'Creado', 'd' => my_date('d M Y', (int) $pj['dateline']));
    $lastedit_ts = (int) ($pj['lastedit'] ?? 0);
    if ($lastedit_ts > 0 && $lastedit_ts !== (int) $pj['dateline']) {
        $timeline[] = array('t' => 'Última edición', 'd' => my_date('d M Y', $lastedit_ts));
    }

    // ── Línea de tiempo de ROL: temas donde participó el personaje ──
    // (posts.ope_pid = pid), agrupados por AÑO in-rol, excluyendo Off Topic.
    $TAG_LABELS   = function_exists('ope_rol_thread_tags') ? ope_rol_thread_tags() : array();
    $cron_years   = array();   // año => [entradas]  (para la timeline pública)
    $cron_flat    = array();   // lista plana ordenada (para el modal de gestión)
    $pid_tl       = (int) $pj['pid'];
    $has_meta     = $db->table_exists('ope_thread_meta');
    $has_cron     = $db->table_exists('ope_cronologia');
    $pref         = TABLE_PREFIX;

    $sel  = "SELECT t.tid, t.subject, t.fid, t.dateline AS tdate";
    $sel .= $has_meta ? ", m.era, m.fecha_rol, m.tag" : ", NULL AS era, NULL AS fecha_rol, '' AS tag";
    $sel .= $has_cron ? ", c.descripcion" : ", NULL AS descripcion";
    $from  = " FROM {$pref}posts p INNER JOIN {$pref}threads t ON t.tid = p.tid";
    if ($has_meta) $from .= " LEFT JOIN {$pref}ope_thread_meta m ON m.tid = t.tid";
    if ($has_cron) $from .= " LEFT JOIN {$pref}ope_cronologia c ON c.tid = t.tid AND c.pid = {$pid_tl}";
    $where = " WHERE p.ope_pid = {$pid_tl} AND p.visible = 1 AND t.visible = 1";
    $grp   = " GROUP BY t.tid, t.subject, t.fid, t.dateline";
    if ($has_meta) $grp .= ", m.era, m.fecha_rol, m.tag";
    if ($has_cron) $grp .= ", c.descripcion";

    $tq = $db->query($sel . $from . $where . $grp);
    while ($tr = $db->fetch_array($tq)) {
        $fid_t = (int) $tr['fid'];
        if (function_exists('ope_rol_is_offtopic_fid') && ope_rol_is_offtopic_fid($fid_t)) {
            continue;
        }
        $era  = ($tr['era'] === 'pasado') ? 'pasado' : (($tr['era'] === 'presente') ? 'presente' : '');
        $anio = (int) $tr['fecha_rol'];
        if ($anio <= 0) {
            // Temas sin metadata guardada (legado): calcula el año on-rol a partir
            // de la fecha real del post, en vez de mostrar el año gregoriano (2026).
            $anio = function_exists('ope_rol_onrol_calendar')
                ? (int) ope_rol_onrol_calendar((int) $tr['tdate'])['year']
                : (int) my_date('Y', (int) $tr['tdate']);
        }
        $entry = array(
            'tid'         => (int) $tr['tid'],
            'subject'     => (string) $tr['subject'],
            'era'         => $era,
            'anio'        => $anio,
            'tag'         => (string) $tr['tag'],
            'tdate'       => (int) $tr['tdate'],
            'descripcion' => (string) ($tr['descripcion'] ?? ''),
        );
        $cron_years[$anio][] = $entry;
        $cron_flat[] = $entry;
    }
    // Años de más reciente a más antiguo; dentro de cada año, por fecha real.
    krsort($cron_years);
    foreach ($cron_years as $yk => &$yarr) {
        usort($yarr, function ($a, $b) { return $a['tdate'] <=> $b['tdate']; });
    }
    unset($yarr);
    // Plano para el modal: por año desc y fecha asc.
    usort($cron_flat, function ($a, $b) {
        if ($a['anio'] !== $b['anio']) return $b['anio'] <=> $a['anio'];
        return $a['tdate'] <=> $b['tdate'];
    });

    // ── Mapa de relaciones: vínculos dirigidos desde este personaje ──
    $REL_TIPOS   = ope_rel_tipos();
    $relaciones  = array();
    $rel_pid     = (int) $pj['pid'];
    if ($db->table_exists('ope_relaciones')) {
        $rq = $db->simple_select('ope_relaciones', '*', "pid = {$rel_pid}", array('order_by' => 'dateline', 'order_dir' => 'asc'));
        while ($rr = $db->fetch_array($rq)) {
            $other = ope_rol_char((int) $rr['destino_pid']);
            if (!$other) continue; // destino borrado: se ignora
            $tipo = isset($REL_TIPOS[$rr['tipo']]) ? $rr['tipo'] : 'otro';
            // Mapa de relaciones: SIEMPRE el ICONO pequeño (fallback a inicial).
            $ico  = trim((string) ($other['icono'] ?? ''));
            $relaciones[] = array(
                'rid'      => (int) $rr['rid'],
                'dest_pid' => (int) $rr['destino_pid'],
                'nombre'   => (string) $other['nombre'],
                'icono'    => $ico,
                'inicial'  => function_exists('mb_substr') ? mb_strtoupper(mb_substr((string) $other['nombre'], 0, 1)) : strtoupper(substr((string) $other['nombre'], 0, 1)),
                'fac_slug' => (string) ($other['faccion_slug'] ?? ''),
                'etiqueta' => (string) $rr['etiqueta'],
                'tipo'     => $tipo,
                'tipo_lbl' => $REL_TIPOS[$tipo],
                'desc'     => (string) ($rr['descripcion'] ?? ''),
                'px'       => (int) $rr['px'],
                'py'       => (int) $rr['py'],
            );
        }
    }

    // Plantillas de post del personaje (para el editor del modal, solo propietario).
    $tpls_list = ($puede_gestionar && function_exists('ope_rol_char_templates'))
        ? ope_rol_char_templates((int) $pj['pid'])
        : array();

    // Personajes aprobados seleccionables como destino (excluye a sí mismo).
    $rel_choices = array();
    if ($puede_gestionar && $db->table_exists('rol_personajes')) {
        $cq = $db->simple_select('rol_personajes', 'pid, nombre',
            "estado = 'aprobado' AND pid <> {$rel_pid}", array('order_by' => 'nombre', 'order_dir' => 'asc'));
        while ($cr = $db->fetch_array($cq)) {
            $rel_choices[] = array('pid' => (int) $cr['pid'], 'nombre' => (string) $cr['nombre']);
        }
    }

    // Coordenadas de dibujo: usa px/py guardados; si están a 0, autodistribuye.
    $REL_CX = 500; $REL_CY = 320; $REL_R = 235;
    $rel_n = count($relaciones);
    foreach ($relaciones as $i => &$rl) {
        if ($rl['px'] > 0 || $rl['py'] > 0) {
            $rl['dx'] = $rl['px']; $rl['dy'] = $rl['py'];
        } else {
            $ang = ($rel_n > 0) ? (2 * M_PI * $i / $rel_n) - M_PI_2 : 0;
            $rl['dx'] = (int) round($REL_CX + $REL_R * cos($ang));
            $rl['dy'] = (int) round($REL_CY + $REL_R * sin($ang));
        }
    }
    unset($rl);

    // Crónica: solo se muestran los subtabs con contenido real.
    // 'relaciones' se muestra ahora en la pestaña Relaciones (como nota bajo el mapa).
    $bio_map = array(
        'pasado'     => 'Pasado',
        'motivacion' => 'Motivación',
    );
    $bio_sections = array();
    foreach ($bio_map as $bkey => $blabel) {
        $btext = trim((string) ($bio[$bkey] ?? ''));
        if ($btext !== '') {
            $bio_sections[$bkey] = array('label' => $blabel, 'text' => $btext);
        }
    }
    // Subsecciones nuevas (columnas propias): Descripción física y Personalidad.
    // La descripción física antepone el "From:" (referencia del físico) si existe.
    if ($desc_fisica !== '' || $from_fisico !== '') {
        $bio_sections['desc_fisica'] = array(
            'label' => 'Descripción física',
            'text'  => $desc_fisica,
            'from'  => $from_fisico,
        );
    }
    if ($personalidad !== '') {
        $bio_sections['personalidad'] = array('label' => 'Personalidad', 'text' => $personalidad);
    }

    list($est_lbl, $est_col) = (function ($e) {
        switch ($e) {
            case 'aprobado':  return array('Aprobado', 'var(--patina)');
            case 'revision':  return array('En revisión', 'var(--h6)');
            case 'rechazado': return array('Rechazado', 'var(--crack)');
            default:          return array('Borrador', 'var(--rivet)');
        }
    })((string) $pj['estado']);

    $stats_ganados = $stats_ganados_early;
    $xp_floor = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel(max(1, $nivel)) : ($nivel - 1) * 20;
    $xp_ceil  = function_exists('ope_rol_stats_para_nivel') ? (int) ope_rol_stats_para_nivel(max(1, $nivel) + 1) : $nivel * 20;
    $xp_span  = max(1, $xp_ceil - $xp_floor);
    $xp_into  = max(0, min($xp_span, $stats_ganados - $xp_floor));
    $xp_pct   = (int) round(100 * $xp_into / $xp_span);
    $xp_next  = max(0, $xp_ceil - $stats_ganados);
    $fue_stat = ope_rol_stat_num($stats_ef, 'FUE');
    $agi_stat = ope_rol_stat_num($stats_ef, 'AGI');
    $crit_pct = min(99, (int) floor($agi_stat / 10));
    $pwr      = $pv_max > 0 ? $pv_max : max(100, $nivel * 100 + $fue_stat);
    $ficha_art = $retrato !== '' ? $retrato : $avatar;
    $alias_bits = array();
    if ($apodo !== '') $alias_bits[] = '«' . $apodo . '»';
    if ($raza_full !== '') $alias_bits[] = $raza_full;
    if ($edad !== '') $alias_bits[] = $edad . (ctype_digit($edad) ? ' años' : '');
    $alias_line = implode(' · ', $alias_bits);

    // ── Vocaciones v4: Clase, Oficios, Arma e Hitos ──
    $vocaciones_data = function_exists('ope_rol_pj_vocaciones') ? ope_rol_pj_vocaciones((int) $pj['pid']) : array();
    $clase_key       = !empty($vocaciones_data['clase']) ? $vocaciones_data['clase'] : ($datos['clase'] ?? '');
    $oficios_keys    = !empty($vocaciones_data['oficios']) ? $vocaciones_data['oficios'] : ($datos['oficios'] ?? array());
    $arma_key        = !empty($vocaciones_data['arma']) ? $vocaciones_data['arma'] : ($datos['arma'] ?? '');
    $elecciones_voc  = $vocaciones_data['elecciones'] ?? array();
    $arquetipo_clase = $vocaciones_data['arquetipo_clase'] ?? '';

    $CLASES_VOC      = function_exists('ope_rol_clases') ? ope_rol_clases() : array();
    $OFICIOS_VOC     = function_exists('ope_rol_oficios') ? ope_rol_oficios() : array();
    $ARMAS_VOC       = function_exists('ope_rol_armas_vocacionales') ? ope_rol_armas_vocacionales() : array();
    $CADENCIA_VOC    = function_exists('ope_rol_voc_cadencia') ? ope_rol_voc_cadencia() : array();

    $clase_info      = isset($CLASES_VOC[$clase_key]) ? $CLASES_VOC[$clase_key] : null;
    $arma_info       = isset($ARMAS_VOC[$arma_key]) ? $ARMAS_VOC[$arma_key] : null;
    $arquetipo_info  = ($arquetipo_clase !== '' && isset($CLASES_VOC[$arquetipo_clase])) ? $CLASES_VOC[$arquetipo_clase] : null;

    $oficios_info = array();
    if (is_array($oficios_keys)) {
        foreach ($oficios_keys as $ok) {
            if (isset($OFICIOS_VOC[$ok])) {
                $oficios_info[$ok] = $OFICIOS_VOC[$ok];
            }
        }
    }
    $tiene_vocacion  = ($clase_info !== null);

    $haki_block = function_exists('ope_haki_ficha_block')
        ? ope_haki_ficha_block((int) $pj['pid'], $stats_ef, $nivel)
        : array();
    $fruta_block = function_exists('ope_fruta_ficha_block')
        ? ope_fruta_ficha_block((int) $pj['pid'], $stats_ef)
        : array('tiene' => false);
    $can_see_details = ($puede_gestionar || $staff_level >= 1);
    $fruta_norm = (!empty($fruta_block['tiene']) && !empty($fruta_block['fruta']) && function_exists('ope_fruta_norm'))
        ? ope_fruta_norm((array) $fruta_block['fruta'], $can_see_details)
        : null;

    // ── Datos de gestión (perfil/imágenes) — antes vivían en el modal ──
    $g_retrato = htmlspecialchars_uni((string) ($datos['retrato'] ?? ''));
    $g_avatar  = htmlspecialchars_uni((string) $pj['avatar']);
    $g_icono   = htmlspecialchars_uni((string) ($pj['icono'] ?? ''));
    $g_firma   = htmlspecialchars_uni((string) ($pj['firma'] ?? ''));
    // Stats comprables (PP): estado por atributo para la pestaña Gestión.
    $stat_keys_all = function_exists('ope_rol_stat_keys') ? ope_rol_stat_keys() : array();
    $stat_names = array();
    foreach ($STAT_GROUPS as $grp_n) {
        foreach ($grp_n['stats'] as $ab_n => $nm_n) { $stat_names[$ab_n] = $nm_n; }
    }
    $stat_cap_now  = function_exists('ope_rol_stat_cap_tramo') ? (int) ope_rol_stat_cap_tramo($nivel) : 6;
    $stat_cost_now = function_exists('ope_rol_pp_cost_tramo') ? (int) ope_rol_pp_cost_tramo($nivel) : 0;
    $stat_buy_lock = ($nivel >= 50); // Nivel 50 · Prestigio (STATS.md): los PP ya no suben stats.

    require_once MYBB_ROOT . 'inc/ope_rol_renombre.php';
    $renombre_pts = ope_renombre_get($pj['pid']);
    $renombre_rango = ope_renombre_rango($renombre_pts);
?>

<div class="wrap ope-ficha-wrap">
  <div class="ope-ficha-game" id="ope-inspector">

    <aside class="ope-ficha-stage" data-fac="<?php echo htmlspecialchars_uni($fac_slug); ?>">
      <div class="ope-ficha-stage-sky" aria-hidden="true"></div>
      <div class="ope-ficha-stage-top">
        <div class="ope-ficha-lv" title="Nivel">
          <span class="ope-ficha-lv-lbl">Lvl</span>
          <b><?php echo (int) $nivel; ?></b>
        </div>
        <div class="ope-ficha-stage-top-r">
<?php if ($faccion_lbl !== ''): ?>
          <span class="ope-ficha-facpill" title="Facción"><?php echo htmlspecialchars_uni($faccion_lbl); ?></span>
<?php endif; ?>
        </div>
      </div>

      <div class="ope-ficha-stage-art">
<?php if ($ficha_art !== ''): ?>
        <img src="<?php echo htmlspecialchars_uni($ficha_art); ?>" alt="<?php echo $nombre_e; ?>" loading="lazy">
<?php else: ?>
        <span class="ope-ficha-stage-ph"><?php echo htmlspecialchars_uni($av_initial); ?></span>
<?php endif; ?>
      </div>

      <div class="ope-ficha-stage-foot">
        <h1 class="ope-ficha-name"><?php echo $nombre_e; ?></h1>
<?php if ($alias_line !== ''): ?>
        <p class="ope-ficha-alias"><?php echo htmlspecialchars_uni($alias_line); ?></p>
<?php endif; ?>
<?php if ($puede_gestionar): ?>
        <div class="ope-ficha-stage-acts">
          <button type="button" class="btn btn-hot btn-sm" id="ope-gestion-open" data-goto-area="gestion" data-goto-mtab="atributos">Gestionar</button>
        </div>
<?php endif; ?>
      </div>
    </aside>

    <!-- ══ IZQUIERDA · PERFIL ══ -->
    <aside class="ope-col ope-col--left" aria-label="Perfil">
      <div class="rk-section">
        <header class="rk-section-h"><span>Perfil</span><small>#<?php echo str_pad((string) (int) $pj['pid'], 5, '0', STR_PAD_LEFT); ?></small></header>
        <div class="rk-section-b">
          <dl class="ope-deflist">
<?php if ($apodo !== ''): ?>            <div><dt>Apodo</dt><dd><?php echo htmlspecialchars_uni($apodo); ?></dd></div>
<?php endif; ?>            <div><dt>Raza</dt><dd><?php echo htmlspecialchars_uni($raza_full); ?></dd></div>
            <div><dt>Edad</dt><dd><?php echo htmlspecialchars_uni($edad !== '' ? $edad : '—'); ?></dd></div>
<?php if ($genero !== ''): ?>            <div><dt>Género</dt><dd><?php echo htmlspecialchars_uni($genero); ?></dd></div>
<?php endif; ?>            <div><dt>Facción</dt><dd><?php echo htmlspecialchars_uni($faccion_lbl !== '' ? $faccion_lbl : '—'); ?></dd></div>
            <div><dt>Renombre</dt><dd><?php echo htmlspecialchars_uni($renombre_rango); ?> · <?php echo ope_renombre_formatear($renombre_pts); ?></dd></div>
          </dl>
        </div>
      </div>

      <div class="rk-section">
        <header class="rk-section-h"><span>Haki</span></header>
        <div class="rk-section-b">
          <dl class="ope-deflist">
<?php foreach (array('ken' => 'Observación', 'buso' => 'Armadura', 'hao' => 'Rey') as $hk => $hlbl):
            $hb = $haki_block[$hk] ?? null;
            $hniv = $hb ? (int) $hb['nivel'] : 0;
            $hline = $hniv > 0
                ? ('Nv.' . $hniv . ' · ' . ($hb['nombre_nivel'] ?? '') . ' · Pot ' . (int) ($hb['potencia'] ?? 0))
                : (($hk === 'hao' && !empty($hb['despertado'])) ? 'Despertado (sin Nv.1)' : '—');
?>
            <div><dt><?php echo htmlspecialchars_uni($hlbl); ?></dt><dd class="<?php echo $hniv > 0 ? '' : 'c-dim'; ?>"><?php echo htmlspecialchars_uni($hline); ?></dd></div>
<?php endforeach; ?>
          </dl>
        </div>
      </div>

      <div class="rk-section rk-section--compact">
        <header class="rk-section-h"><span>Oficios</span></header>
        <div class="rk-section-b"><p class="ope-empty"><?php echo !empty($oficios_info) ? htmlspecialchars_uni(implode(' · ', array_map(function($o){ return $o['nombre']; }, $oficios_info))) : 'Sin oficios'; ?></p></div>
      </div>

      <div class="rk-section rk-section--compact">
        <header class="rk-section-h"><span>Clase</span></header>
        <div class="rk-section-b"><p class="ope-empty"><?php echo $tiene_vocacion ? htmlspecialchars_uni($clase_info['nombre']) : 'Sin clase'; ?></p></div>
      </div>
    </aside>

    <!-- ══ DERECHA · PODER ══ -->
    <aside class="ope-col ope-col--right" aria-label="Poder y combate">
      <div class="rk-section" id="ope-stat-switcher">
        <header class="rk-section-h rk-section-h--switch">
          <div class="ope-sswitch" role="tablist" aria-label="Vista de estadísticas">
            <button type="button" class="ope-ss-btn" role="tab" aria-selected="true" data-ss="attrs">Atributos</button>
            <button type="button" class="ope-ss-btn" role="tab" aria-selected="false" data-ss="deriv">Derivadas</button>
          </div>
        </header>

        <div class="rk-section-b ope-ss-pane" data-ss-pane="attrs">
<?php
      $stat_bar_cap = function_exists('ope_rol_stat_cap_tramo') ? max(1, (int) ope_rol_stat_cap_tramo($nivel)) : 15;
      foreach ($STAT_GROUPS as $gkey => $grupo):
            $rows = $grupo['stats'];
?>
          <div class="pgroup">
            <div class="pgroup-h">
              <span class="n"><?php echo htmlspecialchars_uni($grupo['label']); ?></span>
              <span class="bar"></span>
            </div>
<?php foreach ($rows as $ab => $nm):
              $v = ope_rol_stat_num($stats_ef, $ab);
              $pct = min(100, max(0, (int) round(($v / $stat_bar_cap) * 100)));
              $heat = $pct >= 80 ? '--h9' : ($pct >= 60 ? '--h8' : ($pct >= 40 ? '--h6' : ($pct >= 25 ? '--h4' : '--h2')));
?>
            <div class="stat">
              <span class="nm"><?php echo htmlspecialchars_uni($nm); ?></span>
              <div class="stat-bar-wrap">
                <div class="stat-bar" data-fill="<?php echo $pct; ?>" style="background:var(<?php echo $heat; ?>)"></div>
              </div>
              <span class="stat-num" style="color:var(<?php echo $heat; ?>)"><?php echo (int) $v; ?></span>
            </div>
<?php endforeach; ?>
          </div>
<?php endforeach; ?>
        </div>

        <div class="rk-section-b ope-ss-pane" data-ss-pane="deriv" hidden>
          <div class="ope-deriv">
            <div class="ope-deriv-row ope-deriv-row--vitals">
              <div class="ope-deriv-card ope-deriv-card--pv">
                <span class="ope-deriv-k">PV máx</span>
                <span class="ope-deriv-v"><?php echo (int) $pv_max; ?></span>
              </div>
              <div class="ope-deriv-card ope-deriv-card--en">
                <span class="ope-deriv-k">Energía</span>
                <span class="ope-deriv-v"><?php echo (int) $en_max; ?></span>
              </div>
              <div class="ope-deriv-card ope-deriv-card--pa">
                <span class="ope-deriv-k">PA / turno</span>
                <span class="ope-deriv-v"><?php echo (int) $pa_turno; ?></span>
              </div>
            </div>
            <div class="ope-deriv-row">
              <div class="ope-deriv-card"><span class="ope-deriv-k">Movimiento</span><span class="ope-deriv-v"><?php echo (int) $fisicas['movimiento']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Esprint</span><span class="ope-deriv-v">+<?php echo (int) $fisicas['esprint']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Carrera</span><span class="ope-deriv-v"><?php echo (int) $fisicas['carrera_turno']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Salto V</span><span class="ope-deriv-v"><?php echo (int) $fisicas['salto_v']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Salto H</span><span class="ope-deriv-v"><?php echo (int) $fisicas['salto_h']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Caída</span><span class="ope-deriv-v"><?php echo (int) $fisicas['caida_segura']; ?><small>m</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Carga</span><span class="ope-deriv-v"><?php echo number_format((int) $fisicas['carga_kg'], 0, ',', '.'); ?><small>kg</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Levante</span><span class="ope-deriv-v"><?php echo number_format((int) $fisicas['levantamiento_kg'], 0, ',', '.'); ?><small>kg</small></span></div>
              <div class="ope-deriv-card"><span class="ope-deriv-k">Mitigación</span><span class="ope-deriv-v"><?php echo (int) $fisicas['mitigacion_fis']; ?></span></div>
            </div>
          </div>
        </div>
      </div>

      <div class="rk-section rk-section--compact">
        <header class="rk-section-h"><span>Akuma no Mi</span></header>
        <div class="rk-section-b">
<?php if ($fruta_norm): ?>
          <button type="button" class="ope-akuma-card tipo-<?php echo htmlspecialchars_uni($fruta_norm['tipo_base']); ?>" data-fruta="<?php echo htmlspecialchars_uni(json_encode($fruta_norm, JSON_UNESCAPED_UNICODE)); ?>" aria-label="Ver detalle de <?php echo htmlspecialchars_uni($fruta_norm['nombre']); ?>">
            <span class="ope-akuma-thumb">
<?php if ($fruta_norm['imagen'] !== ''): ?>
              <img src="<?php echo htmlspecialchars_uni($fruta_norm['imagen']); ?>" alt="" loading="lazy" onerror="this.remove()">
<?php else: ?>
              <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 12c4-6 14-6 16 2 2 8-6 20-16 22C14 34 6 22 8 14c2-8 12-8 16-2z" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="M24 12c-1-3 1-6 4-7" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
<?php endif; ?>
            </span>
            <span class="ope-akuma-info">
              <span class="ope-akuma-kicker"><?php echo htmlspecialchars_uni($fruta_norm['tipo']); ?> · Tier <?php echo htmlspecialchars_uni($fruta_norm['tier_roman']); ?></span>
              <b class="ope-akuma-name"><?php echo htmlspecialchars_uni($fruta_norm['nombre']); ?></b>
              <span class="ope-akuma-sub">Nv.<?php echo (int) $fruta_block['nivel']; ?> <?php echo htmlspecialchars_uni((string) ($fruta_block['nombre_nivel'] ?? '')); ?> · Pot <?php echo (int) ($fruta_block['potencia'] ?? 1); ?> · CU <?php echo (int) ($fruta_block['cu'] ?? 0); ?><?php if ($fruta_block['cu_prox'] !== null): ?>/<?php echo (int) $fruta_block['cu_prox']; ?><?php endif; ?></span>
            </span>
            <span class="ope-akuma-go" aria-hidden="true">Ver ficha ›</span>
          </button>
<?php else: ?>
          <p class="ope-empty">Sin fruta</p>
<?php endif; ?>
        </div>
      </div>
    </aside>

    <!-- ══ ABAJO · DECK ══ -->
    <div class="ope-ficha-deck" id="ope-deck">
      <div class="ope-deck-tabs tabs" role="tablist" aria-label="Detalle del expediente">
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="true"  data-tab="eternal">Talentos</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="combate">Técnicas</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="equipo">Equipo</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="cronica">Historia</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="relaciones">Vínculos</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="aliados">Aliados</button>
        <button type="button" class="tab ope-dtab" role="tab" aria-selected="false" data-tab="rasgos">Rasgos</button>
      </div>
      <div class="ope-deck-body">

    <!-- CRÓNICA -->
    <section class="panel" id="tab-cronica" role="tabpanel">
        <div>
          <!-- Descripción física (siempre visible) -->
          <div class="plate">
            <div class="plate-h"><span class="t">Descripción física</span></div>
            <div class="plate-b prose">
<?php if ($from_fisico !== ''): ?>
              <p class="ope-from-line"><span class="l">Faceclaim (PB):</span> <?php echo htmlspecialchars_uni($from_fisico); ?></p>
<?php endif; ?>
<?php if ($desc_fisica !== ''): ?>
              <?php echo ope_nl2p($desc_fisica); ?>
<?php else: ?>
              <p class="mono c-dim">Sin descripción física todavía.<?php echo $puede_gestionar ? ' Edítala desde Gestión.' : ''; ?></p>
<?php endif; ?>
            </div>
          </div>

          <!-- Personalidad (siempre visible) -->
          <div class="plate">
            <div class="plate-h"><span class="t">Personalidad</span></div>
            <div class="plate-b prose">
<?php if ($personalidad !== ''): ?>
              <?php echo ope_nl2p($personalidad); ?>
<?php else: ?>
              <p class="mono c-dim">Sin personalidad registrada todavía.<?php echo $puede_gestionar ? ' Edítala desde Gestión.' : ''; ?></p>
<?php endif; ?>
            </div>
          </div>

          <!-- Historia -->
          <div class="plate">
            <div class="plate-h"><span class="t">Historia</span></div>
            <div class="plate-b prose">
<?php if ($historia !== ''): ?>
              <?php echo ope_nl2p($historia); ?>
<?php else: ?>
              <p class="mono c-dim">Sin historia registrada todavía.<?php echo $puede_gestionar ? ' Edítala desde Gestión.' : ''; ?></p>
<?php endif; ?>
            </div>
          </div>

          <!-- Notas -->
<?php if ($notas !== ''): ?>
          <div class="plate">
            <div class="plate-h"><span class="t">Notas</span></div>
            <div class="plate-b prose">
              <?php echo ope_nl2p($notas); ?>
            </div>
          </div>
<?php endif; ?>

          <div class="plate">
            <div class="plate-h"><span class="t">Línea de tiempo</span><span class="c">// historias por año</span></div>
            <div class="plate-b">
<?php if (empty($cron_years)): ?>
              <p class="mono fs-76 c-dim">Este personaje aún no ha participado en ninguna historia.</p>
<?php else: ?>
              <div class="tl-tabs" role="tablist">
<?php $tl_first = true; foreach ($cron_years as $y => $arr): ?>
                <button type="button" class="tl-tab" role="tab" aria-selected="<?php echo $tl_first ? 'true' : 'false'; ?>" data-year="<?php echo (int) $y; ?>">Año <?php echo htmlspecialchars_uni(ope_rol_year_label((int) $y)); ?></button>
<?php $tl_first = false; endforeach; ?>
              </div>
<?php $tl_first = true; foreach ($cron_years as $y => $arr): ?>
              <div class="tl-year" data-year-c="<?php echo (int) $y; ?>"<?php echo $tl_first ? '' : ' hidden'; ?>>
                <div class="tl">
<?php foreach ($arr as $e):
    $tag_lbl  = $e['tag'] !== '' ? ($TAG_LABELS[$e['tag']] ?? $e['tag']) : '';
    $tag_slug = $e['tag'] !== '' ? strtolower($e['tag']) : '';
?>
                  <div class="tl-i">
                    <a class="tl-title" href="<?php echo $bburl; ?>/showthread.php?tid=<?php echo (int) $e['tid']; ?>"><?php echo htmlspecialchars_uni($e['subject']); ?></a>
                    <span class="tl-badges">
<?php if ($e['era'] !== ''): ?><span class="tl-era tl-era-<?php echo $e['era']; ?>"><?php echo $e['era'] === 'pasado' ? 'Pasado' : 'Presente'; ?></span><?php endif; ?>
<?php if ($tag_lbl !== ''): ?><span class="tl-tag tl-tag-<?php echo $tag_slug; ?>"><?php echo htmlspecialchars_uni($tag_lbl); ?></span><?php endif; ?>
                    </span>
<?php if (trim($e['descripcion']) !== ''): ?>
                    <p class="tl-desc"><?php echo nl2br(htmlspecialchars_uni($e['descripcion'])); ?></p>
<?php endif; ?>
                  </div>
<?php endforeach; ?>
                </div>
              </div>
<?php $tl_first = false; endforeach; ?>
<?php endif; ?>
            </div>
          </div>
        </div>
    </section>

    <!-- RELACIONES (Vínculos) -->
    <section class="panel" id="tab-relaciones" role="tabpanel">
      <div class="plate">
        <div class="plate-h">
          <span class="t">Relaciones</span><span class="c">// mapa de vínculos</span>
<?php if ($puede_gestionar): ?>
          <button type="button" class="ope-rel-editbtn" id="ope-rel-edit-open">Editar relaciones</button>
<?php endif; ?>
        </div>
        <div class="plate-b">
<?php if (empty($relaciones)): ?>
          <div class="ope-rel-empty" id="relaciones-map">
            <div class="ope-rel-empty-ic" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><path d="M12 8.4l-5.6 6.7M12 8.4l5.6 6.7M7 17h10"/></svg>
            </div>
            <p>Aún no hay vínculos registrados en este expediente.</p>
<?php if ($puede_gestionar): ?>
            <button type="button" class="btn btn-hot" id="ope-rel-edit-open2">Añadir la primera relación</button>
<?php endif; ?>
          </div>
<?php else:
        $rel_svg_cx = $REL_CX; $rel_svg_cy = $REL_CY;
        // Nodo central: ICONO pequeño del personaje (fallback a inicial, no avatar).
        $c_ico = trim((string) ($pj['icono'] ?? ''));
?>
          <div class="ope-relmap-wrap" id="relaciones-map">
<?php if ($puede_gestionar): ?>
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" id="ope-relpos-form">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="rel_pos">
<?php endif; ?>
            <svg class="ope-relmap<?php echo $puede_gestionar ? ' is-editable' : ''; ?>" viewBox="0 0 1000 640" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Mapa de relaciones de <?php echo $nombre_e; ?>">
              <defs>
                <clipPath id="ope-rel-clip-c"><circle cx="0" cy="0" r="46"/></clipPath>
                <clipPath id="ope-rel-clip-n"><circle cx="0" cy="0" r="34"/></clipPath>
              </defs>
              <!-- Aristas centro -> nodo -->
<?php foreach ($relaciones as $rl): ?>
              <g class="ope-reledge rel-<?php echo $rl['tipo']; ?>">
                <line x1="<?php echo $rel_svg_cx; ?>" y1="<?php echo $rel_svg_cy; ?>" x2="<?php echo $rl['dx']; ?>" y2="<?php echo $rl['dy']; ?>"></line>
<?php
            $mx = (int) round(($rel_svg_cx + $rl['dx']) / 2);
            $my = (int) round(($rel_svg_cy + $rl['dy']) / 2);
            $lbl = trim($rl['etiqueta']) !== '' ? $rl['etiqueta'] : $rl['tipo_lbl'];
?>
                <g class="ope-rellabel" transform="translate(<?php echo $mx; ?>,<?php echo $my; ?>)">
                  <text text-anchor="middle" dy="4"><?php echo htmlspecialchars_uni($lbl); ?></text>
                </g>
              </g>
<?php endforeach; ?>
              <!-- Nodos de personajes relacionados -->
<?php foreach ($relaciones as $rl):
            $fc = $rl['fac_slug'] !== '' ? ' fac-' . $rl['fac_slug'] : '';
?>
              <g class="ope-relnode<?php echo $fc; ?>" data-rid="<?php echo $rl['rid']; ?>" transform="translate(<?php echo $rl['dx']; ?>,<?php echo $rl['dy']; ?>)"<?php if (!empty($rl['desc'])): ?> data-desc="<?php echo htmlspecialchars_uni($rl['desc']); ?>"<?php endif; ?>>
                <circle class="ope-relnode-bg" cx="0" cy="0" r="34"></circle>
<?php if ($rl['icono'] !== ''): ?>
                <image href="<?php echo htmlspecialchars_uni($rl['icono']); ?>" x="-34" y="-34" width="68" height="68" clip-path="url(#ope-rel-clip-n)" preserveAspectRatio="xMidYMid slice"></image>
<?php else: ?>
                <text class="ope-relnode-ini" text-anchor="middle" dy="9"><?php echo htmlspecialchars_uni($rl['inicial']); ?></text>
<?php endif; ?>
                <circle class="ope-relnode-ring" cx="0" cy="0" r="34" fill="none"></circle>
                <a href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $rl['dest_pid']; ?>" class="ope-relnode-link">
                  <text class="ope-relnode-name" text-anchor="middle" y="52"><?php echo htmlspecialchars_uni($rl['nombre']); ?></text>
                </a>
<?php if ($puede_gestionar): ?>
                <input type="hidden" name="px[<?php echo $rl['rid']; ?>]" value="<?php echo $rl['dx']; ?>">
                <input type="hidden" name="py[<?php echo $rl['rid']; ?>]" value="<?php echo $rl['dy']; ?>">
<?php endif; ?>
              </g>
<?php endforeach; ?>
              <!-- Nodo central: personaje actual -->
              <g class="ope-relnode ope-relnode-center<?php echo $fac_class; ?>" transform="translate(<?php echo $rel_svg_cx; ?>,<?php echo $rel_svg_cy; ?>)">
                <circle class="ope-relnode-bg" cx="0" cy="0" r="46"></circle>
<?php if ($c_ico !== ''): ?>
                <image href="<?php echo htmlspecialchars_uni($c_ico); ?>" x="-46" y="-46" width="92" height="92" clip-path="url(#ope-rel-clip-c)" preserveAspectRatio="xMidYMid slice"></image>
<?php else: ?>
                <text class="ope-relnode-ini big" text-anchor="middle" dy="11"><?php echo htmlspecialchars_uni($inicial ?? mb_substr($pj['nombre'], 0, 1)); ?></text>
<?php endif; ?>
                <circle class="ope-relnode-ring" cx="0" cy="0" r="46" fill="none"></circle>
                <text class="ope-relnode-name center" text-anchor="middle" y="66"><?php echo $nombre_e; ?></text>
              </g>
            </svg>
<?php if ($puede_gestionar): ?>
            <div class="ope-relmap-actions">
              <span class="ope-relmap-hint">Arrastra los nodos para recolocarlos y guarda.</span>
              <button type="submit" class="btn btn-hot" id="ope-relpos-save" disabled>Guardar posiciones</button>
            </div>
          </form>
<?php endif; ?>
          </div>
          <div class="ope-rel-legend">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
            <span class="ope-rel-leg rel-<?php echo $ts; ?>"><i></i><?php echo $tl; ?></span>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>
    </section>
    <!-- CLASE Y OFICIOS -->
    <section class="panel on" id="tab-eternal" role="tabpanel">
<?php if (!$tiene_vocacion): ?>
      <div class="plate"><div class="plate-b"><p class="mono fs-76 c-dim">Este personaje no tiene asignada una Clase Bélica ni Oficios.</p></div></div>
<?php else: ?>
      <div class="plate">
        <div class="plate-h"><span class="t"><?php echo htmlspecialchars_uni(strtoupper($clase_info['nombre'])); ?></span><span class="c">// <?php echo htmlspecialchars_uni($clase_info['prim'] . ' / ' . $clase_info['sec']); ?></span></div>
        <div class="plate-b">
          <ul class="ope-hitos-list">
<?php
    $hitos_clase = $clase_info['hitos'] ?? array();
    foreach ($CADENCIA_VOC as $nv_hito => $meta_cad):
        $desbloqueado = ($nivel >= $nv_hito);
        $hito_val = $hitos_clase[$nv_hito] ?? null;
        if (!$hito_val) continue;
        if (!$desbloqueado) continue;

        $badge_status = '<span class="badge cost">Nv. ' . $nv_hito . '</span>';
        
        if (is_array($hito_val) && isset($hito_val['eleccion'])) {
            $elec_hecha = $elecciones_voc[(string)$nv_hito] ?? null;
            $txt_elec = $elec_hecha ? ('<b>Elección:</b> ' . htmlspecialchars_uni($elec_hecha)) : '<em>Pendiente de elegir en Gestión</em>';
            echo '<li>' . $badge_status . ' ' . $txt_elec . '</li>';
        } elseif (is_array($hito_val) && !empty($hito_val['arquetipo'])) {
            $txt_arq = $arquetipo_info ? ('<b>Arquetipo Nv.30:</b> ' . htmlspecialchars_uni($arquetipo_info['nombre'])) : '<em>Desbloquea segunda clase a Nv.30</em>';
            echo '<li>' . $badge_status . ' ' . $txt_arq . '</li>';
        } else {
            echo '<li>' . $badge_status . ' ' . htmlspecialchars_uni((string)$hito_val) . '</li>';
        }
    endforeach;
?>
          </ul>
        </div>
      </div>
<?php foreach ($oficios_info as $oid => $o):
    if (empty($o['hitos']) || !is_array($o['hitos'])) continue;
    $elec_of = (isset($elecciones_voc['_oficios'][$oid]) && is_array($elecciones_voc['_oficios'][$oid])) ? $elecciones_voc['_oficios'][$oid] : array();
?>
      <div class="plate mt-12">
        <div class="plate-h"><span class="t"><?php echo htmlspecialchars_uni(strtoupper($o['nombre'])); ?></span><span class="c">// Oficio · <?php echo htmlspecialchars_uni($o['prim'] . ' / ' . $o['sec']); ?></span></div>
        <div class="plate-b">
          <ul class="ope-hitos-list">
<?php
    foreach ($CADENCIA_VOC as $nv_hito => $meta_cad):
        $desbloqueado = ($nivel >= $nv_hito);
        $hito_val = $o['hitos'][$nv_hito] ?? null;
        if (!$hito_val) continue;
        if (!$desbloqueado) continue;

        $badge_status = '<span class="badge cost">Nv. ' . $nv_hito . '</span>';

        if (is_array($hito_val) && isset($hito_val['eleccion'])) {
            $elec_hecha = $elec_of[(string)$nv_hito] ?? null;
            $txt_elec = $elec_hecha ? ('<b>Elección:</b> ' . htmlspecialchars_uni($elec_hecha)) : '<em>Pendiente de elegir en Gestión</em>';
            echo '<li>' . $badge_status . ' ' . $txt_elec . '</li>';
        } elseif (is_array($hito_val) && isset($hito_val['especializacion'])) {
            $esp_hecha = $elec_of[(string)$nv_hito] ?? null;
            $txt_esp = $esp_hecha ? ('<b>Especialización:</b> ' . htmlspecialchars_uni($esp_hecha)) : '<em>Pendiente de elegir en Gestión</em>';
            echo '<li>' . $badge_status . ' ' . $txt_esp . '</li>';
        } else {
            echo '<li>' . $badge_status . ' ' . htmlspecialchars_uni((string)$hito_val) . '</li>';
        }
    endforeach;
?>
          </ul>
        </div>
      </div>
<?php endforeach; ?>
<?php endif; ?>
    </section>

    <!-- RASGOS (Factor Linaje: solo los comprados) -->
    <section class="panel" id="tab-rasgos" role="tabpanel">
      <div class="plate">
        <div class="plate-h"><span class="t">Rasgos Raciales</span><span class="c">// comprados con PL</span></div>
        <div class="plate-b">
<?php if (empty($pasivas)): ?>
          <p class="mono fs-76 c-dim">Sin rasgos raciales comprados.</p>
<?php else: foreach ($pasivas as $pas): ?>
          <div class="trait">
            <span class="d v"></span>
            <div>
              <span class="b"><?php echo htmlspecialchars_uni($pas['nombre']); ?><?php echo $pas['spec'] !== '' ? ' — <em class="c-dim">' . htmlspecialchars_uni($pas['spec']) . '</em>' : ''; ?></span>
            </div>
            <span class="id"><?php echo htmlspecialchars_uni($pas['badge']); ?></span>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>

      <div class="plate">
        <div class="plate-h"><span class="t">Rasgos Generales y Defectos</span><span class="c">// balance PL <?php echo ($pl_total_ficha > 0 ? '+' : '') . (int) $pl_total_ficha; ?></span></div>
        <div class="plate-b">
<?php if (empty($rasgos_generales_pj)): ?>
          <p class="mono fs-76 c-dim">Sin rasgos generales ni defectos registrados.</p>
<?php else: foreach ($rasgos_generales_pj as $rasgo): ?>
          <div class="trait">
            <span class="d <?php echo $rasgo['tipo']; ?>"></span>
            <div>
              <span class="b"><?php echo htmlspecialchars_uni($rasgo['nombre']); ?><?php echo $rasgo['spec'] !== '' ? ' — <em class="c-dim">' . htmlspecialchars_uni($rasgo['spec']) . '</em>' : ''; ?></span>
            </div>
            <span class="id<?php echo $rasgo['tipo'] === 'x' ? ' x' : ''; ?>"><?php echo htmlspecialchars_uni($rasgo['badge']); ?></span>
          </div>
<?php endforeach; endif; ?>
        </div>
      </div>
    </section>

    <!-- ALIADOS (acompañantes NPC + tripulación) -->
    <section class="panel" id="tab-aliados" role="tabpanel">
      <!-- Acompañantes NPC (hasta 2) -->
      <div class="plate" id="acomp-plate">
        <div class="plate-h">
          <span class="t">Acompañantes</span><span class="c">// <?php echo count($acompanantes); ?>/<?php echo function_exists('ope_rol_acompanantes_max') ? ope_rol_acompanantes_max() : 2; ?></span>
        </div>
        <div class="plate-b">
<?php if (empty($acompanantes)): ?>
          <p class="mono fs-76 c-dim">Sin acompañantes NPC. Puedes solicitarlos en <a href="<?php echo $bburl; ?>/solicitar-acompanante.php">Trámites &rsaquo; Acompañante</a>.</p>
<?php else:
        echo ope_rol_npc_sec_card_css();
?>
          <div class="ons-deck">
<?php foreach ($acompanantes as $acomp):
        $npc_ac = $acomp['npc'] ?? null;
        if (!$npc_ac) continue;
?>
            <?php echo ope_rol_npc_sec_card_html($npc_ac); ?>
<?php endforeach; ?>
          </div>
<?php endif; ?>
        </div>
      </div>

    </section>

    <!-- TÉCNICAS (deck de cartas) -->
    <section class="panel" id="tab-combate" role="tabpanel">
      <!-- Deck de cartas de técnica (INI-03) -->
      <div class="plate" id="deck-plate">
        <div class="plate-h">
          <span class="t">Deck</span><span class="c">// <?php echo count($deck_tecnicas); ?> carta(s)</span>
          <button type="button" class="ope-deck-toggle" id="deck-toggle" aria-expanded="false">&#9660; Desplegar</button>
        </div>
        <div class="plate-b tal" id="deck-body" hidden>
<?php if (empty($deck_tecnicas)): ?>
          <div class="ope-soon-box">
            <span class="ope-soon-tag">Deck vacío</span>
            <p class="mono fs-76 c-dim mt-8">Este personaje aún no ha aprendido ninguna carta de técnica.</p>
          </div>
<?php else:
        // Extraer tags únicos para filtros
        $all_tags = array();
        foreach ($deck_tecnicas as $carta) {
            $ctags = is_array($carta['tags'] ?? null) ? $carta['tags'] : array();
            foreach ($ctags as $t) {
                $t = trim((string) $t);
                if ($t !== '') $all_tags[$t] = ($all_tags[$t] ?? 0) + 1;
            }
        }
        arsort($all_tags);
?>
          <div class="ope-deck-controls">
            <input type="search" id="deck-search" class="ope-deck-search" placeholder="Buscar carta...">
            <div class="ope-deck-tags" id="deck-tags">
<?php foreach ($all_tags as $tag_name => $tag_count): ?>
              <label class="ope-deck-tag" data-tag="<?php echo htmlspecialchars_uni($tag_name); ?>">
                <input type="checkbox" class="ope-deck-tag-cb" value="<?php echo htmlspecialchars_uni($tag_name); ?>">
                <?php echo htmlspecialchars_uni($tag_name); ?> <small>(<?php echo $tag_count; ?>)</small>
              </label>
<?php endforeach; ?>
            </div>
          </div>
<?php echo ope_rol_tecnica_card_css(); ?>
          <div class="ope-tk-deck" id="deck-grid">
<?php foreach ($deck_tecnicas as $carta): ?>
            <?php echo ope_rol_tecnica_card_html($carta); ?>
<?php endforeach; ?>
          </div>
          <div class="ope-deck-pag" id="deck-pagination"></div>
<?php endif; ?>
        </div>
      </div>

    </section>

    <!-- EQUIPO -->
<?php
      // Slots ocupados por lo que se lleva encima (respeta el tamaño de cada objeto).
      $slots_usados = 0;
      foreach ($inv_encima as $it) { $slots_usados += max(1, (int) $it['size']); }
      $slots_libres = max(0, OPE_INV_CAP - $slots_usados);
      // En la VISTA de la ficha los objetos NO se mueven: al pulsarlos despliegan
      // su información. Mover entre inventario/almacén vive solo en el modal.
      $inv_data_attrs = static function (array $it): string {
          $sz = max(1, (int) $it['size']);
          return ' data-name="' . htmlspecialchars_uni($it['n']) . '"'
               . ' data-size="' . $sz . '"'
               . ' data-desc="' . htmlspecialchars_uni($it['d']) . '"';
      };
?>
    <section class="panel" id="tab-equipo" role="tabpanel">
      <!-- Mochila: berries + capacidad de slots -->
      <div class="plate">
        <div class="plate-h"><span class="t">Mochila</span><span class="c">// <?php echo $slots_usados; ?>/<?php echo OPE_INV_CAP; ?> slots</span></div>
        <div class="plate-b">
          <div class="ope-berries">
            <span class="ope-berries-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8.5 9.5h5M8.5 12h5M11 7v10"/></svg></span>
            <span class="ope-berries-l">Berries</span>
            <b class="ope-berries-v"><?php echo number_format($berries, 0, ',', '.'); ?></b>
          </div>
<?php if ($pack_def !== null): ?>
          <div class="ope-pack-inicial mt-10 pt-10 bt-dash">
            <span class="mono fs-62 fw-700 ttu ls-5 c-dim"><?php echo htmlspecialchars_uni($pack_def['nombre']); ?></span>
            <p class="mono fs-7 c-ash lh-15 mt-4"><?php echo implode(' &middot; ', array_map('htmlspecialchars_uni', $pack_def['contenido'])); ?></p>
          </div>
<?php elseif ($arma_legacy !== '' || $obj_legacy !== ''): ?>
          <div class="ope-pack-inicial mt-10 pt-10 bt-dash">
            <span class="mono fs-62 fw-700 ttu ls-5 c-dim">Equipo inicial (ficha antigua)</span>
<?php if ($arma_legacy !== ''): ?><p class="mono fs-7 c-ash mt-4"><b class="c-dim">Arma:</b> <?php echo htmlspecialchars_uni($arma_legacy); ?></p><?php endif; ?>
<?php if ($obj_legacy !== ''): ?><p class="mono fs-7 c-ash"><b class="c-dim">Objeto:</b> <?php echo htmlspecialchars_uni($obj_legacy); ?></p><?php endif; ?>
          </div>
<?php endif; ?>
        </div>
      </div>

      <div class="ope-inv">
        <!-- Inventario (lo que llevas encima): cuadrícula de slots -->
        <div class="plate ope-inv-side">
          <div class="plate-h"><span class="t">Inventario</span><span class="c">// encima &middot; <?php echo count($inv_encima); ?> obj.</span></div>
          <div class="plate-b">
            <p class="ope-inv-hint">Pulsa un objeto para ver su información. Los objetos grandes ocupan varios slots.<?php echo $puede_gestionar ? ' Para mover objetos usa Gestión &rsaquo; Equipo.' : ''; ?></p>
<?php if (empty($inv_encima)): ?>
            <p class="mono fs-76 c-dim">No llevas nada encima.</p>
<?php endif; ?>
            <div class="ope-slotgrid">
<?php foreach ($inv_encima as $i => $it):
                $sz = max(1, (int) $it['size']);
?>
              <button type="button" class="ope-slot filled ope-invitem" style="grid-column:span <?php echo min($sz, 4); ?>"<?php echo $inv_data_attrs($it); ?>>
                <span class="ope-slot-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
<?php if ($sz > 1): ?><span class="ope-slot-sz"><?php echo $sz; ?></span><?php endif; ?>
              </button>
<?php endforeach; ?>
<?php for ($e = 0; $e < $slots_libres; $e++): ?>
              <div class="ope-slot empty" aria-hidden="true"></div>
<?php endfor; ?>
            </div>
          </div>
        </div>

        <!-- Almacén: lista de objetos guardados -->
        <div class="plate ope-inv-side">
          <div class="plate-h"><span class="t">Almacén</span><span class="c">// <?php echo count($inv_almacen); ?> obj.</span></div>
          <div class="plate-b">
            <p class="ope-inv-hint">Pulsa un objeto para ver su información.</p>
<?php if (empty($inv_almacen)): ?>
            <p class="mono fs-76 c-dim">Almacén vacío.</p>
<?php else: ?>
            <div class="ope-store">
<?php foreach ($inv_almacen as $i => $it):
                $sz = max(1, (int) $it['size']);
?>
              <button type="button" class="ope-store-item ope-invitem"<?php echo $inv_data_attrs($it); ?>>
                <span class="ope-store-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
                <span class="ope-store-sz"><?php echo $sz; ?> slot<?php echo $sz > 1 ? 's' : ''; ?></span>
              </button>
<?php endforeach; ?>
            </div>
<?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Detalle del objeto pulsado (se rellena por JS; no mueve nada) -->
      <div class="plate ope-inv-detail" id="ope-inv-detail" hidden>
        <div class="plate-h"><span class="t" id="ope-inv-detail-name">&mdash;</span><span class="c" id="ope-inv-detail-size"></span></div>
        <div class="plate-b prose"><p id="ope-inv-detail-desc" class="mono c-dim"></p></div>
      </div>
    </section>
      </div><!-- .ope-deck-body -->
    </div><!-- .ope-ficha-deck -->
  </div><!-- .ope-ficha-game -->

<?php if ($puede_gestionar): ?>
  <div class="ope-gview" id="ope-gestion-view" hidden>
    <div class="ope-gview-bar">
      <button type="button" class="btn btn-ghost btn-sm ope-gview-back" id="ope-gestion-close">&lsaquo; Volver a la ficha</button>
      <b class="ope-gview-title">Gestión del expediente</b>
    </div>
    <!-- GESTIÓN (dueño / staff) -->
    <section class="panel on" id="tab-gestion" role="tabpanel">
      <div class="ope-gestion">
        <nav class="ope-gestion-rail" role="tablist" aria-label="Herramientas de gestión">
          <button type="button" class="ope-mtab on" role="tab" aria-selected="true" data-mtab="atributos">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
            <span>Atributos y PT</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="talentos">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v6m0 0-3 3m3-3 3 3M6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm12 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg></span>
            <span>Clase y Arquetipo</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="haki">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.9 4.9l2.8 2.8M16.3 16.3l2.8 2.8M2 12h4M18 12h4M4.9 19.1l2.8-2.8M16.3 7.7l2.8-2.8"/></svg></span>
            <span>Haki y Fruta</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="perfil">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 12 0v1"/></svg></span>
            <span>Perfil e imágenes</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="templates">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16"/><path d="M8 9h8M8 13h5"/></svg></span>
            <span>Plantillas de post</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="equipo">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14h18V6l-3-4zM3 6h18M10 10a2 2 0 0 0 4 0"/></svg></span>
            <span>Inventario</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="cronologia">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></span>
            <span>Cronología</span>
          </button>
          <button type="button" class="ope-mtab" role="tab" aria-selected="false" data-mtab="relaciones">
            <span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="6" r="2.4"/><circle cx="5" cy="17" r="2.4"/><circle cx="19" cy="17" r="2.4"/><path d="M12 8.4l-5.6 6.7M12 8.4l5.6 6.7"/></svg></span>
            <span>Relaciones</span>
          </button>
        </nav>

        <div class="ope-gestion-content">
<?php if ($gestion_flash !== '' || $gestion_ok): ?>
          <div class="ope-mflash"><?php echo $gestion_flash !== '' ? htmlspecialchars_uni($gestion_flash) : 'Cambios guardados correctamente.'; ?></div>
<?php endif; ?>

        <!-- Panel: Atributos y PT -->
        <section class="ope-mpanel on" data-mpanel="atributos" role="tabpanel" id="g-atributos">
          <div class="ope-gprog">
            <div class="ope-gprog-head">
              <span>Nivel <?php echo (int) $nivel; ?><?php echo $nivel >= 50 ? ' · Prestigio' : ''; ?></span>
              <b><?php echo $nivel >= 50 ? (int) $stats_ganados . ' pts' : ((int) $xp_into . ' / ' . (int) $xp_span); ?></b>
            </div>
<?php if ($nivel < 50): ?>
            <div class="ope-gprog-bar" role="progressbar" aria-valuenow="<?php echo (int) $xp_into; ?>" aria-valuemin="0" aria-valuemax="<?php echo (int) $xp_span; ?>"><i style="width:<?php echo (int) $xp_pct; ?>%"></i></div>
<?php endif; ?>
          </div>
          <div class="ope-gstats-top">
            <div class="ope-gstat-kpi ope-gstat-kpi--hi"><span>Comprados</span><b><?php echo (int) $stats_ganados; ?></b></div>
            <div class="ope-gstat-kpi"><span>PP</span><b><?php echo (int) $pp_disponible; ?></b></div>
            <div class="ope-gstat-kpi"><span>Coste</span><b><?php echo $stat_buy_lock ? '—' : (int) $stat_cost_now; ?></b></div>
            <div class="ope-gstat-kpi"><span>Tope</span><b><?php echo (int) $stat_cap_now; ?></b></div>
          </div>
          <div class="ope-gstats-grid">
<?php foreach ($stat_keys_all as $sk):
            $cur    = ope_rol_stat_num($stats_ef, $sk, 1);
            $at_cap = $cur >= $stat_cap_now;
            $no_pp  = $pp_disponible < $stat_cost_now;
            $dis    = $stat_buy_lock || $at_cap || $no_pp;
?>
            <div class="ope-gstat<?php echo $at_cap ? ' is-max' : ''; ?>">
              <span class="ope-gstat-k"><?php echo htmlspecialchars_uni($sk); ?></span>
              <span class="ope-gstat-n"><?php echo htmlspecialchars_uni($stat_names[$sk] ?? $sk); ?></span>
              <span class="ope-gstat-v"><?php echo (int) $cur; ?><small>/<?php echo (int) $stat_cap_now; ?></small></span>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="buy_stat">
                <input type="hidden" name="stat" value="<?php echo htmlspecialchars_uni($sk); ?>">
                <button type="submit" class="ope-gstat-buy"<?php echo $dis ? ' disabled' : ''; ?> title="<?php echo $dis ? ($at_cap ? 'Tope' : ($stat_buy_lock ? 'Prestigio' : 'Sin PP')) : ('+' . (int) $stat_cost_now . ' PP'); ?>">+1</button>
              </form>
            </div>
<?php endforeach; ?>
          </div>
        </section>

        <!-- Panel: Elecciones de Clase y Arquetipo -->
        <section class="ope-mpanel" data-mpanel="talentos" role="tabpanel" id="g-talentos">
          <div class="ope-field-help mb-12">Gestiona las elecciones de hitos de tu Clase Bélica (Niveles 10, 20, 40, 50) y la selección de tu <b>Arquetipo / 2ª Clase</b> al alcanzar el Nivel 30.</div>
<?php if (!$tiene_vocacion): ?>
          <p class="mono fs-76 c-dim">Este personaje no tiene asignada una Clase Bélica.</p>
<?php else: ?>
          <h4 class="mono c-paper mb-8">Elecciones de Hito (Clase: <?php echo htmlspecialchars_uni($clase_info['nombre']); ?>)</h4>
<?php
    $hitos_clase = $clase_info['hitos'] ?? array();
    foreach ($CADENCIA_VOC as $nv_hito => $meta_cad):
        $hito_val = $hitos_clase[$nv_hito] ?? null;
        if (!is_array($hito_val) || empty($hito_val['eleccion']) || !is_array($hito_val['eleccion'])) continue;
        $desbloqueado = ($nivel >= $nv_hito);
        $opciones = $hito_val['eleccion'];
        $elec_actual = $elecciones_voc[(string)$nv_hito] ?? '';
        $titulo_hito = ($nv_hito === 1) ? 'Variante de Clase · Nivel 1' : ('Hito Nivel ' . (int)$nv_hito);
?>
          <div class="plate mb-8 p-12">
            <div class="plate-h">
              <span class="t"><?php echo $titulo_hito; ?></span>
              <span class="c">// <?php echo $desbloqueado ? 'Desbloqueado' : 'Requiere Nivel ' . (int)$nv_hito; ?></span>
            </div>
            <div class="plate-b">
<?php if (!$desbloqueado): ?>
              <p class="mono fs-76 c-dim mb-0">Alcanza el nivel <?php echo (int)$nv_hito; ?> para elegir entre:</p>
              <ul class="fs-76 c-dim mb-0 mt-4">
<?php foreach ($opciones as $op_nombre => $op_desc): ?>
                <li><b><?php echo htmlspecialchars_uni($op_nombre); ?>:</b> <?php echo htmlspecialchars_uni($op_desc); ?></li>
<?php endforeach; ?>
              </ul>
<?php else: ?>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int)$pj['pid']; ?>" class="flex-wrap gap-8 align-center">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="save_voc_eleccion">
                <input type="hidden" name="nivel" value="<?php echo (int)$nv_hito; ?>">
                <select name="opcion" class="form-control ope-select-mid" required>
                  <option value="">-- Selecciona una opción --</option>
<?php foreach ($opciones as $op_nombre => $op_desc): ?>
                  <option value="<?php echo htmlspecialchars_uni($op_nombre); ?>"<?php echo ($elec_actual === $op_nombre) ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars_uni($op_nombre . ' — ' . $op_desc); ?>
                  </option>
<?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-hot">Guardar Elección</button>
              </form>
<?php endif; ?>
            </div>
          </div>
<?php endforeach; ?>

          <!-- Nivel 30: Arquetipo (Segunda Clase) -->
          <div class="plate mt-16 p-12">
            <div class="plate-h">
              <span class="t">Arquetipo (2ª Clase Bélica)</span>
              <span class="c">// Nivel 30+</span>
            </div>
            <div class="plate-b">
<?php if ($nivel < 30): ?>
              <p class="mono fs-76 c-dim mb-0">Al alcanzar el Nivel 30 podrás seleccionar una segunda Clase Bélica como tu Arquetipo para expandir tu versatilidad táctica.</p>
<?php else: ?>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int)$pj['pid']; ?>" class="flex-wrap gap-8 align-center">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="save_voc_arquetipo">
                <select name="segunda_clase" class="form-control ope-select-mid">
                  <option value="">-- Ninguna (Sin Arquetipo) --</option>
<?php foreach ($CLASES_VOC as $ck => $cd): if ($ck === $clase_key) continue; ?>
                  <option value="<?php echo htmlspecialchars_uni($ck); ?>"<?php echo ($arquetipo_clase === $ck) ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars_uni($cd['nombre'] . ' (' . $cd['prim'] . '/' . $cd['sec'] . ')'); ?>
                  </option>
<?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-hot">Guardar Arquetipo</button>
              </form>
<?php if ($arquetipo_info): ?>
              <div class="mono fs-76 c-ember mt-8"><b>Arquetipo Actual:</b> <?php echo htmlspecialchars_uni($arquetipo_info['nombre']); ?> — <?php echo htmlspecialchars_uni($arquetipo_info['filosofia']); ?></div>
<?php endif; ?>
<?php endif; ?>
            </div>
          </div>

<?php if (!empty($oficios_info)): ?>
          <h4 class="mono c-paper mb-8 mt-16">Elecciones y Especialización de Oficios</h4>
<?php foreach ($oficios_info as $oid => $o):
    if (empty($o['hitos']) || !is_array($o['hitos'])) continue;
    $elec_of = (isset($elecciones_voc['_oficios'][$oid]) && is_array($elecciones_voc['_oficios'][$oid])) ? $elecciones_voc['_oficios'][$oid] : array();
    $tiene_opciones_of = false;
    foreach ($o['hitos'] as $hv) { if (is_array($hv) && (isset($hv['eleccion']) || isset($hv['especializacion']))) { $tiene_opciones_of = true; break; } }
    if (!$tiene_opciones_of) continue;
?>
          <div class="plate mt-12 p-12">
            <div class="plate-h">
              <span class="t"><?php echo htmlspecialchars_uni($o['nombre']); ?></span>
              <span class="c">// Oficio · Especialización a Nv.30</span>
            </div>
            <div class="plate-b">
<?php foreach ($CADENCIA_VOC as $nv_hito => $meta_cad):
        $hito_val = $o['hitos'][$nv_hito] ?? null;
        if (!is_array($hito_val)) continue;
        $es_esp = isset($hito_val['especializacion']);
        $es_elec = isset($hito_val['eleccion']);
        if (!$es_esp && !$es_elec) continue;
        $opciones = $es_esp ? $hito_val['especializacion'] : $hito_val['eleccion'];
        $desbloqueado = ($nivel >= $nv_hito);
        $elec_actual = $elec_of[(string)$nv_hito] ?? '';
        $lbl_tipo = $es_esp ? 'Especialización' : 'Elección';
?>
              <div class="plate mt-8">
                <div class="plate-h">
                  <span class="t"><?php echo $lbl_tipo; ?> · Nivel <?php echo (int)$nv_hito; ?></span>
                  <span class="c">// <?php echo $desbloqueado ? 'Desbloqueado' : 'Requiere Nivel ' . (int)$nv_hito; ?></span>
                </div>
                <div class="plate-b">
<?php if (!$desbloqueado): ?>
                  <p class="mono fs-76 c-dim mb-0">Alcanza el nivel <?php echo (int)$nv_hito; ?> para elegir entre:</p>
                  <ul class="fs-76 c-dim mb-0 mt-4">
<?php foreach ($opciones as $op_nombre => $op_desc): ?>
                    <li><b><?php echo htmlspecialchars_uni($op_nombre); ?>:</b> <?php echo htmlspecialchars_uni($op_desc); ?></li>
<?php endforeach; ?>
                  </ul>
<?php else: ?>
                  <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int)$pj['pid']; ?>" class="flex-wrap gap-8 align-center">
                    <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                    <input type="hidden" name="gaccion" value="save_voc_eleccion_oficio">
                    <input type="hidden" name="oficio" value="<?php echo htmlspecialchars_uni($oid); ?>">
                    <input type="hidden" name="nivel" value="<?php echo (int)$nv_hito; ?>">
                    <select name="opcion" class="form-control ope-select-mid" required>
                      <option value="">-- Selecciona una opción --</option>
<?php foreach ($opciones as $op_nombre => $op_desc): ?>
                      <option value="<?php echo htmlspecialchars_uni($op_nombre); ?>"<?php echo ($elec_actual === $op_nombre) ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars_uni($op_nombre . ' — ' . $op_desc); ?>
                      </option>
<?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-hot">Guardar <?php echo $lbl_tipo; ?></button>
                  </form>
<?php endif; ?>
                </div>
              </div>
<?php endforeach; ?>
            </div>
          </div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
        </section>

        <!-- Panel: Haki y Fruta -->
        <section class="ope-mpanel" data-mpanel="haki" role="tabpanel" id="g-haki">
          <div class="ope-field-help mb-12">Sube Ken/Buso (y Hao tras despertar) con PP + CU. El Despertar de Haoshoku, Ken T1 y Fruta Nv.3 van por <a href="<?php echo $bburl; ?>/tramites.php">Trámites</a>. Cartas en posts: <code>[carta=haki.buso.imbuir]</code> o dentro de <code>[rpgsys]</code>.</div>
<?php foreach (array('ken', 'buso', 'hao') as $hk):
            $hb = $haki_block[$hk] ?? array();
            $dom = $hb['dominio'] ?? array();
            $can = !empty($dom['ok']);
            $tram = (string) ($dom['tramite'] ?? '');
?>
          <div class="plate mb-12">
            <div class="plate-h"><span class="t"><?php echo htmlspecialchars_uni((string) ($hb['label'] ?? $hk)); ?></span>
              <span class="c">Nv.<?php echo (int) ($hb['nivel'] ?? 0); ?><?php if (!empty($hb['nombre_nivel']) && ($hb['nombre_nivel'] !== '—')): ?> · <?php echo htmlspecialchars_uni($hb['nombre_nivel']); ?><?php endif; ?></span></div>
            <div class="plate-b">
              <p class="mono fs-76">Potencia <?php echo (int) ($hb['potencia'] ?? 0); ?>
                · CU <?php echo (int) ($hb['cu'] ?? 0); ?><?php if (isset($hb['cu_prox']) && $hb['cu_prox'] !== null): ?> / <?php echo (int) $hb['cu_prox']; ?><?php endif; ?>
                · PP invertidos <?php echo (int) ($hb['pp_gastado'] ?? 0); ?>
<?php if ($hk === 'hao'): ?> · <?php echo !empty($hb['despertado']) ? 'Despertado' : 'Sin despertar'; ?><?php endif; ?>
              </p>
<?php if ($can): ?>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="mt-8">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="buy_haki">
                <input type="hidden" name="haki_tipo" value="<?php echo htmlspecialchars_uni($hk); ?>">
                <button type="submit" class="btn btn-primary btn-sm">Comprar Nv.<?php echo (int) ($dom['siguiente'] ?? 0); ?> (−<?php echo (int) ($dom['pp'] ?? 0); ?> PP)</button>
              </form>
<?php elseif ($tram !== ''): ?>
              <p class="mono fs-76 c-dim mt-8"><?php echo htmlspecialchars_uni((string) ($dom['msg'] ?? '')); ?>
                <a href="<?php echo $bburl; ?>/tramites.php#<?php echo htmlspecialchars_uni($tram); ?>">Abrir trámite</a></p>
<?php else: ?>
              <p class="mono fs-76 c-dim mt-8"><?php echo htmlspecialchars_uni((string) ($dom['msg'] ?? 'Sin dominio disponible.')); ?></p>
<?php endif; ?>
            </div>
          </div>
<?php endforeach; ?>

          <div class="plate">
            <div class="plate-h"><span class="t">Akuma no Mi</span>
              <span class="c"><?php echo !empty($fruta_block['tiene']) ? ('Nv.' . (int) $fruta_block['nivel']) : 'sin fruta'; ?></span></div>
            <div class="plate-b">
<?php if (!empty($fruta_block['tiene'])): ?>
              <p><b><?php echo htmlspecialchars_uni((string) ($fruta_block['fruta']['nombre'] ?? '')); ?></b>
                · <?php echo htmlspecialchars_uni((string) ($fruta_block['nombre_nivel'] ?? '')); ?></p>
              <p class="mono fs-76">CU <?php echo (int) $fruta_block['cu']; ?><?php if ($fruta_block['cu_prox'] !== null): ?> / <?php echo (int) $fruta_block['cu_prox']; ?><?php endif; ?>
                · Pot <?php echo (int) $fruta_block['potencia']; ?> (TEM+<?php echo htmlspecialchars_uni($fruta_block['secundario']); ?>)</p>
<?php
            $fdom = $fruta_block['dominio'] ?? array();
            if (!empty($fdom['ok'])):
?>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="mt-8">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="buy_fruta">
                <button type="submit" class="btn btn-primary btn-sm">Subir a Nv.<?php echo (int) ($fdom['siguiente'] ?? 0); ?> (−<?php echo (int) ($fdom['pp'] ?? 0); ?> PP)</button>
              </form>
<?php elseif (!empty($fdom['tramite'])): ?>
              <p class="mono fs-76 c-dim mt-8"><?php echo htmlspecialchars_uni((string) ($fdom['msg'] ?? '')); ?>
                <a href="<?php echo $bburl; ?>/tramites.php#fruta_despertar">Abrir trámite</a></p>
<?php else: ?>
              <p class="mono fs-76 c-dim mt-8"><?php echo htmlspecialchars_uni((string) ($fdom['msg'] ?? '')); ?></p>
<?php endif; ?>
<?php else: ?>
              <p class="ope-empty">Sin fruta. Puedes pedir una con PD en <a href="<?php echo $bburl; ?>/tramites.php#akuma_pd">Trámites</a>.</p>
<?php endif; ?>
            </div>
          </div>
        </section>

        <!-- Panel: Avatar / Icono / Firma -->
        <section class="ope-mpanel" data-mpanel="perfil" role="tabpanel">
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="perfil">

            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Retrato PNG (cuerpo completo)</label>
                <div class="ope-field-help">Imagen del personaje en la ficha (columna izquierda, formación). <b>No</b> es el avatar de los posts. <b>Tamaño recomendado:</b> 600&ndash;900px de alto, <b>PNG con fondo transparente</b> (nunca JPG).</div>
                <input type="url" name="retrato" value="<?php echo $g_retrato; ?>" placeholder="https://...">
              </div>
              <div class="ope-mprev ope-mprev-tall">
<?php if ($g_retrato !== ''): ?>
                <img src="<?php echo $g_retrato; ?>" alt="Retrato" id="ope-prev-retrato">
<?php else: ?>
                <div class="ope-mprev-empty" id="ope-prev-retrato-empty"><?php echo htmlspecialchars_uni($inicial ?? mb_substr($pj['nombre'], 0, 1)); ?></div>
<?php endif; ?>
              </div>
            </div>
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Avatar (posts del foro)</label>
                <div class="ope-field-help">Imagen que aparece junto a cada mensaje de este personaje en el foro (postbit). <b>Tamaño recomendado:</b> 280&times;450px (ratio 3:4), PNG o JPG.</div>
                <input type="url" name="avatar" value="<?php echo $g_avatar; ?>" placeholder="https://...">
              </div>
              <div class="ope-mprev">
<?php if ($g_avatar !== ''): ?>
                <img src="<?php echo $g_avatar; ?>" alt="Avatar" id="ope-prev-avatar">
<?php else: ?>
                <div class="ope-mprev-empty" id="ope-prev-avatar-empty"><?php echo htmlspecialchars_uni($inicial ?? mb_substr($pj['nombre'], 0, 1)); ?></div>
<?php endif; ?>
              </div>
            </div>
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Icono de post</label>
                <div class="ope-field-help">Imagen pequeña opcional junto al nombre en cada mensaje. <b>Tamaño recomendado:</b> 64&times;64px, PNG cuadrado (con o sin transparencia).</div>
                <input type="url" name="icono" value="<?php echo $g_icono; ?>" placeholder="https://...">
              </div>
              <div class="ope-mprev">
<?php if ($g_icono !== ''): ?>
                <img src="<?php echo $g_icono; ?>" alt="Icono">
<?php else: ?>
                <div class="ope-mprev-empty small">64&times;64</div>
<?php endif; ?>
              </div>
            </div>
            <div class="ope-field">
              <label>Firma</label>
              <div class="ope-field-help">Admite BBCode: <code>[b]</code>, <code>[i]</code>, <code>[img]url[/img]</code>, <code>[color=#41A4E0]</code>&hellip; Si incluyes una imagen, <b>máximo recomendado 500&times;150px</b> para no romper la maquetación del hilo. Aparecerá bajo cada mensaje de este personaje con un separador <b>One Piece: 7 Seas</b>.</div>
              <textarea name="firma" rows="6" placeholder="[b]Dorr Kaskan[/b] &mdash; herrero de Elbaf&#10;[img]https://...[/img]"><?php echo $g_firma; ?></textarea>
            </div>

            <div class="ope-msep" aria-hidden="true"><span>One Piece: 7 Seas</span></div>
            <div class="ope-modal-actions">
              <button type="submit" class="btn btn-hot">Guardar cambios</button>
            </div>
          </form>
        </section>

        <!-- Panel: Templates -->
        <section class="ope-mpanel" data-mpanel="templates" role="tabpanel">
          <div class="ope-field-help mb-12">Crea plantillas reutilizables (BBCode y spoilers anidados). Aparecerán como botones sobre el editor al crear temas o responder, y se insertan en la posición del cursor.</div>

          <!-- Crear nueva plantilla -->
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-tpl-form" data-tpl-form>
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="tpl_add">
            <div class="ope-field">
              <label>Nombre de la plantilla</label>
              <input type="text" name="nombre" maxlength="120" placeholder="Ej.: Ficha de combate" required>
            </div>
            <div class="ope-field">
              <label>Cuerpo</label>
              <div class="ope-tpl-tools">
                <button type="button" class="ope-tpl-tool" data-ins="[spoiler=Título][/spoiler]">+ Spoiler</button>
                <button type="button" class="ope-tpl-tool" data-ins="[b][/b]">B</button>
                <button type="button" class="ope-tpl-tool" data-ins="[i][/i]"><em>i</em></button>
                <button type="button" class="ope-tpl-tool" data-ins="[img][/img]">Imagen</button>
              </div>
              <textarea name="cuerpo" rows="7" placeholder="[spoiler=Estado]HP: 100/100&#10;[spoiler=Detalles]...[/spoiler][/spoiler]"></textarea>
            </div>
            <div class="ope-modal-actions"><button type="submit" class="btn btn-hot">Añadir plantilla</button></div>
          </form>

          <div class="ope-msep" aria-hidden="true"><span>Mis plantillas</span></div>
<?php if (empty($tpls_list)): ?>
          <p class="mono fs-76 c-dim">Aún no tienes plantillas. Crea la primera arriba.</p>
<?php else: foreach ($tpls_list as $tp): ?>
          <div class="ope-tpl-item">
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-tpl-form" data-tpl-form>
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="tpl_edit">
              <input type="hidden" name="tpl_id" value="<?php echo (int) $tp['tpl_id']; ?>">
              <div class="ope-field">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="120" value="<?php echo htmlspecialchars_uni($tp['nombre']); ?>">
              </div>
              <div class="ope-field">
                <label>Cuerpo</label>
                <div class="ope-tpl-tools">
                  <button type="button" class="ope-tpl-tool" data-ins="[spoiler=Título][/spoiler]">+ Spoiler</button>
                  <button type="button" class="ope-tpl-tool" data-ins="[b][/b]">B</button>
                  <button type="button" class="ope-tpl-tool" data-ins="[i][/i]"><em>i</em></button>
                  <button type="button" class="ope-tpl-tool" data-ins="[img][/img]">Imagen</button>
                </div>
                <textarea name="cuerpo" rows="6"><?php echo htmlspecialchars_uni($tp['cuerpo']); ?></textarea>
              </div>
              <div class="ope-rel-item-acts"><button type="submit" class="btn btn-ghost">Guardar</button></div>
            </form>
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" onsubmit="return confirm('¿Eliminar esta plantilla?');">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="tpl_del">
              <input type="hidden" name="tpl_id" value="<?php echo (int) $tp['tpl_id']; ?>">
              <button type="submit" class="ope-rel-del">Eliminar</button>
            </form>
          </div>
<?php endforeach; endif; ?>
        </section>

        <!-- Panel: Equipo -->
        <section class="ope-mpanel" data-mpanel="equipo" role="tabpanel" id="g-equipo">
          <div class="ope-field-help mb-12">Gestiona qué objetos llevas <b>encima</b> y cuáles dejas en el <b>almacén</b>. Lo que llevas encima ocupa slots (máx. <?php echo OPE_INV_CAP; ?>); el almacén no tiene tope.</div>

          <!-- Añadir objeto -->
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-equip-addform">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="equip_add">
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Objeto</label>
                <input type="text" name="nombre" maxlength="120" placeholder="Ej.: Catalejo de latón" required>
              </div>
              <div class="ope-field">
                <label>Destino</label>
                <select name="loc">
                  <option value="almacen">Almacén</option>
                  <option value="encima">Encima</option>
                </select>
              </div>
            </div>
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Descripción</label>
                <input type="text" name="desc" maxlength="240" placeholder="Opcional">
              </div>
              <div class="ope-field">
                <label>Slots</label>
                <input type="number" name="size" min="1" max="<?php echo OPE_INV_CAP; ?>" value="1">
              </div>
            </div>
            <div class="ope-modal-actions"><button type="submit" class="btn btn-hot">Añadir objeto</button></div>
          </form>

<?php
          $equip_cols = array(
              'encima'  => array('Lleva encima', $inv_encima,  'Al almacén'),
              'almacen' => array('Almacén', $inv_almacen, 'Sacar (llevar)'),
          );
          foreach ($equip_cols as $loc => $col):
              list($col_lbl, $col_items, $move_lbl) = $col;
?>
          <div class="ope-msep" aria-hidden="true"><span><?php echo $col_lbl; ?></span></div>
<?php if (empty($col_items)): ?>
          <p class="mono fs-76 c-dim">Vacío.</p>
<?php else: foreach ($col_items as $i => $it): ?>
          <div class="ope-equip-item">
            <div class="ope-equip-item-b">
              <span class="ope-equip-n"><?php echo htmlspecialchars_uni($it['n']); ?></span>
<?php if ($it['d'] !== ''): ?><span class="ope-equip-d"><?php echo htmlspecialchars_uni($it['d']); ?></span><?php endif; ?>
            </div>
            <div class="ope-equip-acts">
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="equip_move">
                <input type="hidden" name="from" value="<?php echo $loc; ?>">
                <input type="hidden" name="idx" value="<?php echo (int) $i; ?>">
                <button type="submit" class="btn btn-ghost"><?php echo $move_lbl; ?></button>
              </form>
              <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" onsubmit="return confirm('¿Eliminar este objeto?');">
                <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
                <input type="hidden" name="gaccion" value="equip_del">
                <input type="hidden" name="from" value="<?php echo $loc; ?>">
                <input type="hidden" name="idx" value="<?php echo (int) $i; ?>">
                <button type="submit" class="ope-rel-del">Eliminar</button>
              </form>
            </div>
          </div>
<?php endforeach; endif; ?>
<?php endforeach; ?>
        </section>

        <!-- Panel: Cronología -->
        <section class="ope-mpanel" data-mpanel="cronologia" role="tabpanel">
<?php if (empty($cron_flat)): ?>
          <div class="ope-msoon">
            <h3>Gestionar cronología</h3>
            <p>Todavía no has participado en ninguna historia. Cuando publiques o respondas temas (fuera de Off Topic), aparecerán aquí para que les pongas una descripción.</p>
          </div>
<?php else: ?>
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="cronologia">
            <div class="ope-field-help mb-12">Añade una nota personal a cada historia de tu línea de tiempo. Se mostrará bajo el título del tema en la ficha.</div>
            <div class="ope-cron-list">
<?php foreach ($cron_flat as $e):
    $tag_lbl2  = $e['tag'] !== '' ? ($TAG_LABELS[$e['tag']] ?? $e['tag']) : '';
    $tag_slug2 = $e['tag'] !== '' ? strtolower($e['tag']) : '';
?>
              <div class="ope-cron-item">
                <div class="ope-cron-head">
                  <span class="ope-cron-t"><?php echo htmlspecialchars_uni($e['subject']); ?></span>
                  <span class="ope-cron-y">Año <?php echo htmlspecialchars_uni(ope_rol_year_label((int) $e['anio'])); ?></span>
<?php if ($e['era'] !== ''): ?><span class="tl-era tl-era-<?php echo $e['era']; ?>"><?php echo $e['era'] === 'pasado' ? 'Pasado' : 'Presente'; ?></span><?php endif; ?>
<?php if ($tag_lbl2 !== ''): ?><span class="tl-tag tl-tag-<?php echo $tag_slug2; ?>"><?php echo htmlspecialchars_uni($tag_lbl2); ?></span><?php endif; ?>
                </div>
                <textarea name="descripcion[<?php echo (int) $e['tid']; ?>]" rows="3" placeholder="Describe qué ocurrió en esta historia..."><?php echo htmlspecialchars_uni($e['descripcion']); ?></textarea>
              </div>
<?php endforeach; ?>
            </div>
            <div class="ope-modal-actions mt-14">
              <button type="submit" class="btn btn-hot">Guardar cronología</button>
            </div>
          </form>
<?php endif; ?>
        </section>

        <!-- Panel: Relaciones -->
        <section class="ope-mpanel" data-mpanel="relaciones" role="tabpanel">
          <div class="ope-field-help mb-12">Vincula a este personaje con otros. El nodo se colorea con la facción del otro personaje y la línea con el tipo de vínculo.</div>

<?php if (empty($rel_choices)): ?>
          <div class="ope-msoon"><p>No hay otros personajes aprobados con los que crear vínculos todavía.</p></div>
<?php else: ?>
          <!-- Añadir relación -->
          <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" class="ope-rel-addform">
            <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
            <input type="hidden" name="gaccion" value="rel_add">
            <div class="ope-mgrid">
              <div class="ope-field">
                <label>Personaje</label>
                <select name="destino_pid" required>
                  <option value="">&mdash; elige un personaje &mdash;</option>
<?php foreach ($rel_choices as $rc): ?>
                  <option value="<?php echo $rc['pid']; ?>"><?php echo htmlspecialchars_uni($rc['nombre']); ?></option>
<?php endforeach; ?>
                </select>
              </div>
              <div class="ope-field">
                <label>Tipo de vínculo</label>
                <select name="tipo">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
                  <option value="<?php echo $ts; ?>"<?php echo $ts === 'aliado' ? ' selected' : ''; ?>><?php echo $tl; ?></option>
<?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="ope-field">
              <label>Nombre de la relación</label>
              <div class="ope-field-help">Ej.: «Capitán», «Hermano de sangre», «Rival eterno».</div>
              <input type="text" name="etiqueta" maxlength="120" placeholder="Capitán">
            </div>
            <div class="ope-field">
              <label>Descripción</label>
              <textarea name="descripcion" rows="3" placeholder="Historia o matiz de esta relación..."></textarea>
            </div>
            <div class="ope-modal-actions"><button type="submit" class="btn btn-hot">Añadir relación</button></div>
          </form>
<?php endif; ?>

          <!-- Relaciones existentes -->
          <div class="ope-msep" aria-hidden="true"><span>Vínculos actuales</span></div>
<?php if (empty($relaciones)): ?>
          <p class="mono fs-76 c-dim">Sin vínculos registrados todavía.</p>
<?php else: foreach ($relaciones as $rl): ?>
          <div class="ope-rel-item rel-<?php echo $rl['tipo']; ?>">
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="rel_edit">
              <input type="hidden" name="rid" value="<?php echo $rl['rid']; ?>">
              <div class="ope-rel-item-h">
                <span class="ope-rel-item-node<?php echo $rl['fac_slug'] !== '' ? ' fac-' . $rl['fac_slug'] : ''; ?>"><?php echo htmlspecialchars_uni($rl['inicial']); ?></span>
                <a class="ope-rel-item-name" href="<?php echo $bburl; ?>/ficha.php?pid=<?php echo $rl['dest_pid']; ?>"><?php echo htmlspecialchars_uni($rl['nombre']); ?></a>
              </div>
              <div class="ope-mgrid">
                <div class="ope-field">
                  <label>Nombre de la relación</label>
                  <input type="text" name="etiqueta" maxlength="120" value="<?php echo htmlspecialchars_uni($rl['etiqueta']); ?>">
                </div>
                <div class="ope-field">
                  <label>Tipo</label>
                  <select name="tipo">
<?php foreach ($REL_TIPOS as $ts => $tl): ?>
                    <option value="<?php echo $ts; ?>"<?php echo $ts === $rl['tipo'] ? ' selected' : ''; ?>><?php echo $tl; ?></option>
<?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="ope-field">
                <label>Descripción</label>
                <textarea name="descripcion" rows="2"><?php echo htmlspecialchars_uni($rl['desc']); ?></textarea>
              </div>
              <div class="ope-rel-item-acts">
                <button type="submit" class="btn btn-ghost">Guardar</button>
              </div>
            </form>
            <form method="post" action="<?php echo $bburl; ?>/ficha.php?pid=<?php echo (int) $pj['pid']; ?>" onsubmit="return confirm('¿Eliminar este vínculo?');">
              <input type="hidden" name="my_post_key" value="<?php echo htmlspecialchars_uni($mybb->post_code); ?>">
              <input type="hidden" name="gaccion" value="rel_del">
              <input type="hidden" name="rid" value="<?php echo $rl['rid']; ?>">
              <button type="submit" class="ope-rel-del">Eliminar</button>
            </form>
          </div>
<?php endforeach; endif; ?>
        </section>
        </div><!-- .ope-gestion-content -->
      </div><!-- .ope-gestion -->
    </section><!-- #tab-gestion -->
  </div><!-- .ope-gview -->
<?php endif; ?>
</div><!-- .ope-ficha-wrap -->
<?php endif; ?>

<?php include __DIR__ . '/inc/footer_custom.php'; ?>

<script>
// ── Switcher Atributos ↔ Derivadas (columna derecha) ──
(function () {
  var root = document.getElementById('ope-stat-switcher');
  if (!root) return;
  var btns = root.querySelectorAll('.ope-ss-btn');
  var panes = root.querySelectorAll('[data-ss-pane]');
  function show(key) {
    btns.forEach(function (b) { b.setAttribute('aria-selected', b.dataset.ss === key ? 'true' : 'false'); });
    panes.forEach(function (p) { p.hidden = p.dataset.ssPane !== key; });
  }
  btns.forEach(function (b) {
    b.addEventListener('click', function () { show(b.dataset.ss); });
  });
})();

// ── Pestañas de la franja inferior (deck) ──
document.querySelectorAll('.ope-dtab').forEach(function (t) {
  t.addEventListener('click', function () {
    var deck = t.closest('.ope-ficha-deck');
    if (!deck) return;
    deck.querySelectorAll('.ope-dtab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    t.setAttribute('aria-selected', 'true');
    deck.querySelectorAll('.ope-deck-body > .panel').forEach(function (p) { p.classList.remove('on'); });
    var panel = deck.querySelector('#tab-' + t.dataset.tab);
    if (panel) panel.classList.add('on');
  });
});

// ── Vista Gestión a pantalla completa (reemplaza al visor) ──
(function () {
  var gview = document.getElementById('ope-gestion-view');
  var inspector = document.getElementById('ope-inspector');
  function selectMtab(key) {
    if (!gview || !key) return;
    var btn = gview.querySelector('.ope-mtab[data-mtab="' + key + '"]');
    if (btn) btn.click();
  }
  function openGestion(mtab) {
    if (!gview) return;
    gview.hidden = false;
    if (inspector) inspector.style.display = 'none';
    if (typeof mtab === 'string') selectMtab(mtab);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  function closeGestion() {
    if (!gview) return;
    gview.hidden = true;
    if (inspector) inspector.style.display = '';
  }
  document.querySelectorAll('[data-goto-area="gestion"], #ope-gestion-open').forEach(function (b) {
    b.addEventListener('click', function () { openGestion(b.dataset.gotoMtab); });
  });
  var back = document.getElementById('ope-gestion-close');
  if (back) back.addEventListener('click', closeGestion);
  // Compatibilidad con llamadas previas.
  window.opeShowArea = function (a) { if (a === 'gestion') { openGestion(); } else { closeGestion(); } };
})();

document.querySelectorAll('.subtab').forEach(function (s) {
  s.addEventListener('click', function () {
    document.querySelectorAll('.subtab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    s.setAttribute('aria-selected', 'true');
    document.querySelectorAll('[data-bio-c]').forEach(function (c) { c.hidden = (c.dataset.bioC !== s.dataset.bio); });
  });
});
// Pestañas por año de la línea de tiempo.
document.querySelectorAll('.tl-tab').forEach(function (s) {
  s.addEventListener('click', function () {
    document.querySelectorAll('.tl-tab').forEach(function (x) { x.setAttribute('aria-selected', 'false'); });
    s.setAttribute('aria-selected', 'true');
    document.querySelectorAll('[data-year-c]').forEach(function (c) { c.hidden = (c.dataset.yearC !== s.dataset.year); });
  });
});

// ── Equipo (vista de ficha): pulsar un objeto despliega su info (no lo mueve) ──
(function () {
  var box = document.getElementById('ope-inv-detail');
  if (!box) return;
  var elName = document.getElementById('ope-inv-detail-name');
  var elSize = document.getElementById('ope-inv-detail-size');
  var elDesc = document.getElementById('ope-inv-detail-desc');
  var current = null;
  document.querySelectorAll('.ope-invitem').forEach(function (it) {
    it.addEventListener('click', function () {
      document.querySelectorAll('.ope-invitem.is-open').forEach(function (x) { if (x !== it) x.classList.remove('is-open'); });
      if (current === it) { it.classList.remove('is-open'); box.hidden = true; current = null; return; }
      current = it;
      it.classList.add('is-open');
      var n = it.getAttribute('data-name') || '';
      var s = parseInt(it.getAttribute('data-size') || '1', 10) || 1;
      var d = it.getAttribute('data-desc') || '';
      elName.textContent = n;
      elSize.textContent = s + ' slot' + (s > 1 ? 's' : '');
      if (d) { elDesc.textContent = d; elDesc.style.color = 'var(--paper)'; }
      else { elDesc.textContent = 'Sin descripción.'; elDesc.style.color = 'var(--paper-dim)'; }
      box.hidden = false;
    });
  });
})();

// ── Gestión (panel): sub-pestañas, previews, plantillas, Eternal, deep-link ──
(function () {
  var panel = document.getElementById('tab-gestion');
  if (!panel) return;

  function openTab(name) {
    var found = false;
    panel.querySelectorAll('.ope-mtab').forEach(function (x) {
      var on = x.dataset.mtab === name;
      x.setAttribute('aria-selected', on ? 'true' : 'false');
      x.classList.toggle('on', on);
      if (on) found = true;
    });
    panel.querySelectorAll('.ope-mpanel').forEach(function (p) {
      p.classList.toggle('on', p.dataset.mpanel === name);
    });
    return found;
  }
  panel.querySelectorAll('.ope-mtab').forEach(function (t) {
    t.addEventListener('click', function () { openTab(t.dataset.mtab); });
  });

  // Previsualización en vivo del retrato (ficha) y del avatar (posts).
  function bindPrev(inputName, imgId, emptyId, altLabel) {
    var inp = panel.querySelector('input[name="' + inputName + '"]');
    var img = document.getElementById(imgId);
    var empty = document.getElementById(emptyId);
    if (!inp) return;
    inp.addEventListener('input', function () {
      var v = inp.value.trim();
      if (!/^https?:\/\//i.test(v)) return;
      if (!img && empty) {
        img = document.createElement('img');
        img.id = imgId;
        img.alt = altLabel;
        empty.replaceWith(img);
        empty = null;
      }
      if (img) img.src = v;
    });
  }
  bindPrev('retrato', 'ope-prev-retrato', 'ope-prev-retrato-empty', 'Retrato');
  bindPrev('avatar', 'ope-prev-avatar', 'ope-prev-avatar-empty', 'Avatar');

  // Botones "Editar relaciones" (cabecera del mapa y estado vacío).
  ['ope-rel-edit-open', 'ope-rel-edit-open2'].forEach(function (id) {
    var b = document.getElementById(id);
    if (b) b.addEventListener('click', function () {
      if (window.opeShowArea) window.opeShowArea('gestion');
      openTab('relaciones');
    });
  });

  // Herramientas de inserción BBCode/spoiler en los editores de plantillas.
  panel.addEventListener('click', function (e) {
    var t = e.target.closest('.ope-tpl-tool');
    if (!t) return;
    var form = t.closest('form');
    var ta = form && form.querySelector('textarea[name="cuerpo"]');
    if (!ta) return;
    var ins = t.getAttribute('data-ins') || '';
    var s = ta.selectionStart || 0, en = ta.selectionEnd || 0, v = ta.value;
    var m = /^(\[[^\]]*\])(\[\/[^\]]*\])$/.exec(ins);
    var caret;
    if (m) {
      var sel = v.slice(s, en);
      ta.value = v.slice(0, s) + m[1] + sel + m[2] + v.slice(en);
      caret = s + m[1].length + sel.length;
    } else {
      ta.value = v.slice(0, s) + ins + v.slice(en);
      caret = s + ins.length;
    }
    ta.focus();
    try { ta.setSelectionRange(caret, caret); } catch (x) {}
  });



  // Si venimos de guardar (?g=1), abre el área Gestión y la sub-pestaña del hash.
  if (/[?&]g=1(&|$)/.test(location.search)) {
    if (window.opeShowArea) window.opeShowArea('gestion');
    var map = {
      '#g-atributos': 'atributos', '#g-talentos': 'talentos', '#g-haki': 'haki', '#g-equipo': 'equipo',
      '#cronologia': 'cronologia', '#relaciones': 'relaciones', '#templates': 'templates',
      '#equipo': 'equipo', '#perfil': 'perfil'
    };
    openTab(map[location.hash] || 'atributos');
  }
})();

// ── Mapa de relaciones: activar pestaña + arrastre de nodos ──
(function () {
  var svg = document.querySelector('.ope-relmap');
  if (!svg) return;

  // Si venimos de guardar posiciones, muestra la pestaña Relaciones.
  if (location.hash === '#relaciones-map') {
    var relTab = document.querySelector('.tab[data-tab="relaciones"]');
    if (relTab) relTab.click();
  }

  if (!svg.classList.contains('is-editable')) return;
  var saveBtn = document.getElementById('ope-relpos-save');
  var pt = svg.createSVGPoint();
  var dragging = null;

  function toSvg(evt) {
    pt.x = evt.clientX; pt.y = evt.clientY;
    var m = svg.getScreenCTM();
    if (!m) return { x: 0, y: 0 };
    var p = pt.matrixTransform(m.inverse());
    return { x: p.x, y: p.y };
  }
  function clamp(v, min, max) { return v < min ? min : (v > max ? max : v); }

  svg.querySelectorAll('.ope-relnode[data-rid]').forEach(function (node) {
    node.addEventListener('pointerdown', function (e) {
      // No arrastres al pulsar el enlace del nombre.
      if (e.target.closest('.ope-relnode-link')) return;
      e.preventDefault();
      dragging = node;
      node.classList.add('is-dragging');
      node.setPointerCapture(e.pointerId);
    });
  });

  svg.addEventListener('pointermove', function (e) {
    if (!dragging) return;
    var p = toSvg(e);
    var x = Math.round(clamp(p.x, 40, 960));
    var y = Math.round(clamp(p.y, 40, 600));
    dragging.setAttribute('transform', 'translate(' + x + ',' + y + ')');
    var rid = dragging.getAttribute('data-rid');
    var hx = dragging.querySelector('input[name="px[' + rid + ']"]');
    var hy = dragging.querySelector('input[name="py[' + rid + ']"]');
    if (hx) hx.value = x;
    if (hy) hy.value = y;
    updateEdges();
    if (saveBtn) saveBtn.disabled = false;
  });
  function endDrag(e) {
    if (!dragging) return;
    dragging.classList.remove('is-dragging');
    dragging = null;
  }
  svg.addEventListener('pointerup', endDrag);
  svg.addEventListener('pointercancel', endDrag);

  // Recalcula posición de aristas/etiquetas a partir de los nodos.
  function updateEdges() {
    var cx = 500, cy = 320;
    var nodes = svg.querySelectorAll('.ope-relnode[data-rid]');
    var edges = svg.querySelectorAll('.ope-reledge');
    nodes.forEach(function (node, i) {
      var t = node.getAttribute('transform') || '';
      var m = /translate\(\s*(-?\d+\.?\d*)[ ,]+(-?\d+\.?\d*)\)/.exec(t);
      if (!m) return;
      var x = parseFloat(m[1]), y = parseFloat(m[2]);
      var edge = edges[i];
      if (!edge) return;
      var ln = edge.querySelector('line');
      if (ln) { ln.setAttribute('x2', x); ln.setAttribute('y2', y); }
      var lbl = edge.querySelector('.ope-rellabel');
      if (lbl) lbl.setAttribute('transform', 'translate(' + Math.round((cx + x) / 2) + ',' + Math.round((cy + y) / 2) + ')');
    });
  }
})();

// ── Deck: desplegable, filtros, búsqueda, paginado ──
(function () {
  var toggle = document.getElementById('deck-toggle');
  var body = document.getElementById('deck-body');
  if (!toggle || !body) return;

  toggle.addEventListener('click', function () {
    var open = body.hidden;
    body.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.innerHTML = open ? '&#9650; Contraer' : '&#9660; Desplegar';
    if (open) doFilter();
  });

  var grid = document.getElementById('deck-grid');
  var search = document.getElementById('deck-search');
  var pagDiv = document.getElementById('deck-pagination');
  var cards = grid ? grid.querySelectorAll('.ope-tk') : [];
  var perPage = 6;
  var currentPage = 1;
  var activeTag = '';

  if (!grid || !cards.length) return;

  if (search) search.addEventListener('input', function () { doFilter(); });
  var tagCbs = document.querySelectorAll('.ope-deck-tag-cb');
  tagCbs.forEach(function (cb) {
    cb.addEventListener('change', function () {
      if (cb.checked) {
        tagCbs.forEach(function (x) { if (x !== cb) x.checked = false; });
        activeTag = cb.value;
      } else {
        activeTag = '';
      }
      doFilter();
    });
  });

  function doFilter() {
    var q = search ? search.value.toLowerCase().trim() : '';
    var visible = [];
    cards.forEach(function (c) {
      var name = (c.getAttribute('data-name') || '').toLowerCase();
      var desc = (c.getAttribute('data-desc') || '').toLowerCase();
      var tags = c.getAttribute('data-tags') || '';
      var matchSearch = !q || name.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
      var matchTag = !activeTag || tags.indexOf(activeTag) !== -1;
      c.style.display = (matchSearch && matchTag) ? '' : 'none';
      if (matchSearch && matchTag) visible.push(c);
    });
    renderPages(visible);
  }

  function renderPages(visible) {
    var total = visible.length;
    var pages = Math.max(1, Math.ceil(total / perPage));
    if (currentPage > pages) currentPage = pages;
    visible.forEach(function (c, i) {
      c.style.display = (i >= (currentPage - 1) * perPage && i < currentPage * perPage) ? '' : 'none';
    });
    if (pages <= 1) { pagDiv.innerHTML = ''; return; }
    var h = '';
    for (var p = 1; p <= pages; p++) {
      h += '<button type="button" class="ope-deck-pg' + (p === currentPage ? ' on' : '') + '" data-p="' + p + '">' + p + '</button>';
    }
    pagDiv.innerHTML = h;
    pagDiv.querySelectorAll('.ope-deck-pg').forEach(function (b) {
      b.addEventListener('click', function () {
        currentPage = parseInt(b.dataset.p, 10);
        renderPages(visible);
      });
    });
  }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
(function() {
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  function fillBars(scope) {
    (scope || document).querySelectorAll('[data-fill]').forEach(function(i) {
      if (i.dataset.done) return;
      i.dataset.done = 1;
      var v = i.getAttribute('data-fill') + '%';
      if (REDUCED) {
        i.style.width = v;
        return;
      }
      setTimeout(function() {
        i.style.width = v;
      }, 80);
    });
  }

  window.addEventListener('load', function() {
    fillBars(document.querySelector('.ope-ficha-wrap'));
    if (window.gsap && window.ScrollTrigger) {
      gsap.registerPlugin(ScrollTrigger);
      document.querySelectorAll('[data-count]').forEach(function(el) {
        var end = +el.getAttribute('data-count'), o = { v: 0 };
        if (REDUCED) {
          el.textContent = end.toLocaleString('es');
          return;
        }
        gsap.to(o, {
          v: end,
          duration: 1.4,
          ease: 'power2.out',
          scrollTrigger: { trigger: el, start: 'top 92%' },
          onUpdate: function() {
            el.textContent = Math.floor(o.v).toLocaleString('es');
          }
        });
      });
    }
  });

  // Listen to tab clicks to re-fill bars in new panes
  document.querySelectorAll('.tab').forEach(function(t) {
    t.addEventListener('click', function() {
      var paneId = 'tab-' + t.dataset.tab;
      var pane = document.getElementById(paneId);
      if (pane) {
        fillBars(pane);
      }
    });
  });
})();
</script>

<?php echo ope_fruta_modal_assets($bburl); ?>
<script>
(function(){
  var card = document.querySelector('.ope-akuma-card[data-fruta]');
  if (!card) return;
  function abrir(){ if(!window.OPEFruta) return; try { OPEFruta.open(JSON.parse(card.getAttribute('data-fruta'))); } catch(e){} }
  card.addEventListener('click', abrir);
  card.addEventListener('keydown', function(e){ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); abrir(); } });
})();
</script>

</body>
</html>
